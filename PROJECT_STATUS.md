# Project Status & Remaining Work

_Last updated: 2026-07-16_

This document records what was recently changed, what must **stay in place**, and what
still **remains to be done** (action items + longer-term direction). It complements
`DEPLOYMENT_CHECKLIST.md`.

---

## 1. Recently completed

### 1.1 GST feature (billing)
- **Settings:** GST enable toggle, GST number, and GST rate already exist on the
  Clinic Settings page and are saved/whitelisted correctly.
- **Patient Visit page:** added a per-visit **"Add GST"** toggle + live breakdown
  (Subtotal / GST / Total payable). Default follows the clinic GST setting; a saved
  visit restores its own choice. The entered Amount stays the pre-tax base.
  → `views/patient/detail.php`
- **Invoice:** honours the per-visit choice (`apply_gst`) and falls back to the clinic
  setting when unset. → `views/invoice.php`
- **GST Report:** new report under Reports → **GST** (summary cards, Base+GST trend
  chart, per-visit line-item table for filing, GSTIN).
  → `views/reports/gst.php`, `src/Models/Report.php`, `src/Controllers/ReportController.php`,
  route in `index.php`, nav link in `views/layout.php`.
- The `apply_gst` column is detected at runtime, so the app works **with or without**
  the migration applied.

### 1.2 Logo
- Both logo references now point to `/assets/logo/app-logo.svg`
  (`views/layout.php`, `views/auth/login.php`).

### 1.3 Security hardening
- **Debug mode off by default** — `DEBUG_MODE` is now driven by the `APP_DEBUG` env
  var (unset/`0` = off). Stack traces no longer reach end users in production.
  → `config/constants.php`, documented in `.env.example`.
- **Secure session cookie** — the session cookie gets the `Secure` flag on HTTPS
  (direct TLS or proxy header). → `index.php`
- **Anti session-fixation** — `session_regenerate_id(true)` runs on successful login.
  → `src/Controllers/AuthController.php`

### 1.4 CSRF protection
- **Per-session token** generated at session start; verified on every state-changing
  request from a logged-in user (`X-CSRF-Token` header or `_csrf` field, `hash_equals`).
  → `index.php`
- **Global fetch wrapper** in `views/layout.php` attaches the token to all same-origin
  mutating `fetch()` calls — no individual call site needed changes.
- **Offline queue:** token is stored on each outbox record and replayed by the service
  worker (`X-CSRF-Token`). Cache bumped to `drfeelgood-v4`.
  → `assets/js/offline/idb-core.js`, `assets/js/offline/offline-client.js`, `service-worker.js`
- **Exempt** (public / tokenized, protected by their own URL token): intake submit,
  `/api/booking`, `/api/patient/lookup`. Login is exempt (unauthenticated).

---

## 2. What must stay in place (do not regress)

- **PDO prepared statements everywhere** — never build SQL by string-concatenating user
  input. Table/column names come from model properties and controller whitelists.
- **Passwords hashed with bcrypt** (`password_hash` / `password_verify`).
- **CSRF token flow** — keep the `<meta name="csrf-token">` tag and the fetch wrapper in
  `views/layout.php`; keep the router-level verification in `index.php`. Any new
  standalone page that makes authenticated POSTs must include the token.
- **`.env` stays out of git** (`.gitignore`) and blocked by `.htaccess`. Real DB
  credentials live only in server environment variables.
- **`DEBUG_MODE` off in production** — only set `APP_DEBUG=1` locally.
- **Secure/HttpOnly/SameSite session cookie** settings in `index.php`.

---

## 3. Remaining action items (near-term)

| # | Item | Where | Priority |
|---|------|-------|----------|
| 1 | **Run the GST migration** on the database | `documentation/migrations/apply_gst.sql` | High (for per-visit GST to reach invoices) |
| 2 | **Drop the new logo file** into place, or switch refs back to PNG and overwrite `app-logo.png` | `assets/logo/app-logo.svg` | High |
| 3 | **Smoke-test CSRF + offline sync** in a browser (login, save visit, payment, settings; one offline save → reconnect → syncs). Watch for "Invalid or missing security token" | — | High |
| 4 | **Restrict `api/intake/{id}/create` to POST-only** — it currently responds to any method, a CSRF gap the header check doesn't cover | `index.php` (~line 720) | Medium |
| 5 | **Remove plaintext-password fallback** and migrate any legacy rows to bcrypt | `src/Models/User.php` (~line 47) | Medium |
| 6 | **Add login rate-limiting / lockout** (brute-force protection) | login flow | Medium |
| 7 | (Optional) Regenerate favicon / apple-touch-icon from the new logo | `assets/logo/` | Low |
| 8 | Reduce login username-enumeration (uniform error message) | `src/Controllers/AuthController.php` | Low |

---

## 4. Longer-term: SaaS / multi-tenant direction

Feasible without a rewrite. The app has **one DB connection created at a single point**
(`index.php`, `new Database()->connect()`) injected into all models — the ideal chokepoint
for tenant routing. Today there is **no tenant scoping** and `settings` is a global singleton.

**Recommended model: database-per-tenant** (strongest isolation for health data, lowest
code churn — queries stay unchanged; a central registry DB maps subdomain/login → connection).

**Phased roadmap**
1. **Tenant foundation** — registry DB, host/subdomain resolution, connection routing at
   the existing chokepoint, tenant bound into the session. *(Core; unlocks the rest.)*
2. **Migration runner** — replace ad-hoc `.sql` files + runtime column-sniffing
   (`hasApplyGst()`, `hasClientUuid()`) with versioned migrations applied per tenant.
3. **Onboarding & billing** — signup, plans, payment gateway (Razorpay for India /
   Stripe intl.), plan-limit enforcement.
4. **Isolation hardening** — per-tenant assets (logo), tenant-scoped PWA cache & IndexedDB
   store keys, audit logging.
5. **Ops** — automated provisioning, per-tenant backups, monitoring.

**Compliance note:** multi-clinic health data triggers **India DPDP Act** (and HIPAA if US)
obligations — audit logs, data residency, per-tenant export/delete. Decide target market
(India-only vs international) before Phase 3, as it affects payment gateway and compliance.

---

## 5. Known verification limits

Changes in this cycle were validated with `php -l` (and `node --check` for JS), but **not
run end-to-end** — MySQL was not available locally. Before relying on the CSRF and GST work
in production, exercise the flows in a real browser against the live/staging database.
