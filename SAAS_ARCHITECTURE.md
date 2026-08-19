# SaaS / Multi-Tenant Architecture Plan

_Draft — 2026-07-16. Planning document only; no code changes yet._

Converts the current single-clinic app into a multi-tenant SaaS where many clinics
share one deployment and one database.

**Chosen approach (agreed):**
- **Isolation:** Shared database, every tenant-owned row carries a `clinic_id`.
- **Tenant routing:** Account-based — the logged-in user's row determines the clinic;
  the clinic id is pinned into the session. Single shared hostname (no subdomains).
- **This document is design-only.** Implementation happens in later, reviewed phases.

> ⚠️ **The defining risk of shared-DB multitenancy is data leakage between clinics.**
> A single query that forgets `clinic_id`, or a single id-addressed route that skips an
> ownership check, exposes one clinic's patient records to another. The enforcement
> strategy in §4 is therefore the heart of this plan, not an afterthought.

---

## 1. Target architecture at a glance

```
                       ┌─────────────── one app deployment ───────────────┐
  user logs in  ─────► │  session.clinic_id = user.clinic_id              │
                       │                                                  │
  every request  ────► │  Tenant context (clinic_id) set once per request │
                       │        │                                         │
                       │        ▼                                         │
                       │  BaseModel auto-scopes queries by clinic_id      │
                       │  Route guards verify id-addressed rows belong    │
                       │  to the current clinic                           │
                       └──────────────────┬───────────────────────────────┘
                                          ▼
                       one MySQL database, every tenant table has clinic_id
                          clinics ─┬─ user ─┬─ patient ─┬─ progress_report
                                   └─ settings, appointments, expense, ...
```

---

## 2. Data model changes

### 2.1 New tables

**`clinics`** — the tenant registry (one row per clinic).

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK AI | tenant id used everywhere as `clinic_id` |
| `name` | VARCHAR(150) | clinic display name |
| `slug` | VARCHAR(80) UNIQUE | url-safe id (future subdomain support) |
| `status` | ENUM('active','suspended','trial','cancelled') | gate access |
| `plan` | VARCHAR(40) | e.g. free / basic / pro |
| `trial_ends_at` | DATE NULL | |
| `created_at` / `updated_at` | TIMESTAMP | |

**`plans`** (optional, can start as a config array) — plan definitions & limits
(max users, max patients, features like GST/intake toggles).

**`subscriptions`** (deferred to billing phase) — clinic ↔ plan, billing status,
current period, gateway references.

**`migrations`** — versioned migration ledger (replaces runtime column-sniffing; see §8).

**`audit_log`** (recommended for health data) — who/what/when, incl. `clinic_id`.

### 2.2 Add `clinic_id` to every tenant-owned table

All get `clinic_id INT UNSIGNED NOT NULL`, an index on `clinic_id`, and (where the
table has a natural per-clinic uniqueness) a composite unique key.

| Table | Model | Notes |
|-------|-------|-------|
| `user` | User | `username`/`email` uniqueness becomes **per clinic** → `UNIQUE(clinic_id, username)` |
| `patient` | Patient | patient_id counter becomes per-clinic |
| `progress_report` | ProgressReport / Report | visits |
| `appointments` | Appointment | plus the closed-dates table |
| `expense` | Expense | |
| `homeo_intake` | HomeoIntake | token stays globally unique (random) |
| `additional_info` | AdditionalInfo | |
| `settings` | Setting | key uniqueness becomes `UNIQUE(clinic_id, key)` — **turns the singleton into per-clinic config** |
| `master_medicines` | Medicine | **Decision needed** — see §2.3 |

### 2.3 Open decision: medicines catalogue

`master_medicines` is a shared reference list with a per-use `usage_count`. Options:
- **A. Per-clinic (simplest, safest):** add `clinic_id`; each clinic grows its own list.
- **B. Global catalogue + per-clinic usage:** split into a shared `medicines` table and a
  `clinic_medicine_usage(clinic_id, medicine_id, count)`. More work, nicer data.

Recommend **A** for phase 1 (isolation-first), revisit later.

---

## 3. Tenant resolution & session

Because routing is **account-based**:

1. **Login** (`AuthController::login`) sets `$_SESSION['clinic_id'] = $user['clinic_id']`
   in addition to the existing session fields, and rejects login if the clinic status is
   `suspended`/`cancelled`.
2. A single **tenant bootstrap** early in `index.php` (right after auth, before routing)
   reads `$_SESSION['clinic_id']` and pins it as the request's tenant context
   (`TenantContext::set($clinicId)`).
3. Every model/query reads the current clinic id from that context — never from user input.
4. **Super-admin** (platform owner) is a special role that operates *outside* a single
   clinic (manage clinics, view usage). It must be handled deliberately (see §6).

No subdomains/DNS/SSL changes are required with this model.

---

## 4. Isolation enforcement — the core of the plan

Three layers, defence-in-depth. **All three are required**; any one alone is insufficient.

### Layer 1 — Auto-scoping in `BaseModel`
Give `BaseModel` a current `clinic_id` and make the generic helpers tenant-safe:
- `insert()` → automatically stamps `clinic_id` into every INSERT.
- `update($id)`, `delete($id)`, `getById($id)` → automatically add `AND clinic_id = ?`,
  so a row from another clinic simply isn't found/affected.
- Add `scopedWhere()` helper so custom queries append `clinic_id = ?` uniformly.

This closes the generic CRUD path with zero per-call effort.

### Layer 2 — Refactor every custom SQL query (~60 sites)
Models with hand-written SQL must add `clinic_id = ?` to each WHERE/JOIN. Approximate
surface to review (query counts):

| Model | ~queries | Priority |
|-------|----------|----------|
| Report | 16 | high (all analytics must scope) |
| Appointment | 14 | high |
| Patient | 11 | high |
| ProgressReport | 5 | high |
| Expense | 5 | medium |
| Setting | 4 | high (per-clinic config) |
| Medicine | 4 | medium |
| User | 3 | high |
| HomeoIntake | 2 | medium (token-addressed too) |
| AdditionalInfo | 1 | medium |

Each JOIN across tenant tables must also constrain `clinic_id` on **both** sides.

### Layer 3 — Route-level ownership checks (IDOR defence)
Every id-addressed route must confirm the target row belongs to the session clinic,
otherwise clinic A reads clinic B's data by guessing an integer id. Affected routes:

```
patient/{id}            api/patient/{id}/report      api/patient/{id}/update
api/patient/{id}/delete api/report/{id}/update       api/report/{id}/payment
api/appointment/{id}/status   api/expenses/{id}/update   api/expenses/{id}/delete
api/users/{id}/update   api/users/{id}/delete        intake/patient/{id}
intake/{id}/result      api/intake/{id}/create
```

With Layer 1 in place, the cleanest implementation is: `getById()` is already
clinic-scoped, so "not found for this clinic" → 404. Add an explicit guard helper for
readability. **Tokenized** intake routes (`/intake/{40-hex}`) are addressed by an
unguessable token, so they self-scope, but should still set the tenant context from the
intake row's `clinic_id`.

### Layer 4 (safety net) — automated isolation tests
A test suite that provisions **two** clinics and asserts that clinic A cannot read, list,
update, or delete any of clinic B's patients/reports/appointments/expenses/users via any
route. This is the regression guard that keeps future features from leaking data.

---

## 5. Settings & per-clinic assets

- **Settings** become per-clinic automatically once `settings` has `clinic_id`
  (`Setting::get/set` keyed by `clinic_id + key`). GST config, clinic name, hours, etc.
  are then naturally isolated.
- **Logo / branding:** store a per-clinic logo path (in `settings` or a `clinics.logo_path`
  column) instead of the single `assets/logo/app-logo.svg`. Serve per clinic. Upload flow
  needed (validate type/size, store outside webroot or in a per-clinic folder).
- **PWA / offline:** the service-worker cache name (`drfeelgood-v4`) and the IndexedDB
  outbox store are currently global; make them tenant-aware (include `clinic_id` in cache
  key / DB name) so two clinics on one device/browser don't collide.

---

## 6. Roles & access

- **Platform level:** new `super_admin` (SaaS operator) — manage clinics, plans, usage,
  impersonate for support. Operates across clinics; must bypass tenant scoping *only*
  through explicit, audited admin paths — never through the normal models.
- **Clinic level:** existing roles unchanged (`doctor`, `asst_doctor`, `reception`) but now
  scoped within a clinic. The clinic "owner" (first user created at signup) maps to `doctor`.
- Guard against privilege escalation: a clinic user must never be able to set/alter their
  own `clinic_id` or `role` beyond what `updateUser()` already prevents.

---

## 7. Onboarding / signup

New public, unauthenticated flow (CSRF-exempt like login/booking):
1. **Sign up** → create a `clinics` row (`status = trial`) + first `user` (owner) in one
   transaction; seed default `settings` for that clinic.
2. Email verification (recommended).
3. Redirect to login; session pins the new `clinic_id`.
4. Enforce plan limits on subsequent create actions (users/patients) per §2.1.

---

## 8. Migrations & rollout

### 8.1 Replace runtime column-sniffing with a migration runner
Today the app detects optional columns at runtime (`hasApplyGst()`, `hasClientUuid()`,
`hasMedicineDetails()`). That doesn't scale. Introduce:
- A `migrations` ledger table + numbered SQL files in `documentation/migrations/` run in
  order by a small CLI/bootstrap runner, recording applied versions.
- Fold the existing ad-hoc migrations (`apply_gst.sql`, etc.) into this scheme.

### 8.2 The tenant migration (single DB — one run)
1. Create `clinics`, `plans`, `migrations`, `audit_log`.
2. Insert **clinic #1** representing the current clinic.
3. Add `clinic_id` (nullable first) to every tenant table; **backfill all existing rows to
   clinic #1**; then set `NOT NULL` + indexes + composite unique keys.
4. Adjust unique constraints (`user.username`, `settings.key`, patient counter) to be
   per-clinic.

### 8.3 Rollout order (safe, incremental)
1. Ship schema + backfill (app still behaves as single-clinic; `clinic_id` always 1).
2. Land Layers 1–3 enforcement behind the always-present `clinic_id = 1`.
3. Add the tenant context/session pinning.
4. Add signup + a second real clinic in staging; run isolation tests (§4 Layer 4).
5. Only then open registration publicly.

---

## 9. Billing (later phase, design placeholder)

- Gateway: **Razorpay** (India) or **Stripe** (international) — depends on target market
  (still open, see §12).
- `subscriptions` table; webhook endpoint to sync payment status → `clinics.status`.
- Plan-limit enforcement middleware (block create actions past quota; show upgrade prompt).
- Dunning / grace period → `suspended` status gates login.

---

## 10. Security considerations specific to shared-DB

- **IDOR is the #1 threat** — see §4 Layer 3. Every id route needs ownership scoping.
- **Cross-tenant JOINs** — constrain `clinic_id` on all joined tenant tables.
- **Aggregate/report leakage** — analytics (`Report.php`, dashboards) must filter clinic_id
  or they'll sum across all clinics.
- **Backups/exports** — per-clinic export must filter clinic_id; a full DB dump now contains
  every clinic's data (encrypt at rest; restrict access).
- **Audit logging** — record clinic_id on sensitive actions (health-data compliance).
- Keep the recently-added protections: **CSRF**, **secure/HttpOnly session cookie**,
  **debug-off in prod**, **session regeneration on login**.

---

## 11. Phased execution plan

| Phase | Deliverable | Notes |
|-------|-------------|-------|
| **0** | Migration runner + `migrations` ledger | Prereq; retires column-sniffing |
| **1** | Schema: `clinics` + `clinic_id` everywhere + backfill to clinic #1 | App unchanged behaviourally |
| **2** | `BaseModel` auto-scoping (Layer 1) + tenant context + session pinning | Foundation |
| **3** | Refactor all custom SQL (Layer 2) + route ownership guards (Layer 3) | The bulk of the work |
| **4** | Per-clinic settings/logo + tenant-aware PWA cache | |
| **5** | Isolation test suite (Layer 4) + second staging clinic | Gate before go-live |
| **6** | Signup/onboarding + `super_admin` console | |
| **7** | Billing / plans / quotas | Market decision first |

---

## 12. Open decisions to confirm before build

1. **Medicines catalogue:** per-clinic (recommended) vs global+usage (§2.3).
2. **Target market:** India-only vs international → sets billing gateway & compliance
   (DPDP Act vs HIPAA/GDPR).
3. **Plan tiers & limits:** what distinguishes free/paid (user count, patient count,
   features like intake/GST/reports)?
4. **Super-admin scope:** support impersonation yes/no (affects audit design).
5. **Signup model:** open self-serve signup vs invite/manual clinic creation initially.

---

## 13. Effort & risk summary

- **Largest effort:** Phase 3 (retrofitting `clinic_id` into ~60 query sites + guarding
  ~14 id routes). Mechanical but must be exhaustive.
- **Largest risk:** a missed scope = cross-clinic health-data leak. Mitigated by Layer 1
  auto-scoping (reduces reliance on remembering) + Layer 4 isolation tests (catches misses).
- **Lowest risk / high value first:** Phases 0–2 are additive and behaviour-preserving
  (clinic_id is always 1 until a second clinic exists), so they can land safely on `main`.

---

_Next step: confirm the §12 decisions, then I can start with Phase 0 (migration runner) and
Phase 1 (schema + backfill) in a branch — both are safe, additive, and don't change current
single-clinic behaviour._
