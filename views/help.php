<?php
$page_title = 'Help & User Guide';
ob_start();
?>
<style>
/* ── Layout ── */
.help-layout { display:grid; grid-template-columns:230px 1fr; gap:24px; align-items:start; }
@media(max-width:900px){ .help-layout { grid-template-columns:1fr; } }

/* ── TOC ── */
.help-toc { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:16px 18px; position:sticky; top:16px; }
.help-toc .toc-title { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; color:#9ca3af; margin:0 0 8px; }
.help-toc a { display:block; font-size:12px; color:#4b5563; text-decoration:none; padding:4px 0 4px 10px; border-left:2px solid transparent; transition:.12s; line-height:1.4; }
.help-toc a:hover { color:var(--primary); border-left-color:#bfdbfe; }
.help-toc a.active { color:var(--primary); border-left-color:var(--primary); font-weight:600; }
.help-toc .toc-group { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#d1d5db; margin:12px 0 4px 10px; }
@media(max-width:900px){ .help-toc { position:static; } }

/* ── Sections ── */
.help-section { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:26px 30px; margin-bottom:20px; scroll-margin-top:72px; }
.help-section h2 { font-size:16px; font-weight:800; color:#111827; margin:0 0 14px; display:flex; align-items:center; gap:10px; padding-bottom:12px; border-bottom:2px solid #f3f4f6; }
.help-section h2 .sec-icon { width:34px; height:34px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:15px; flex-shrink:0; }
.help-section h3 { font-size:13px; font-weight:700; color:#1f2937; margin:20px 0 6px; padding-left:10px; border-left:3px solid var(--primary); }
.help-section h4 { font-size:12px; font-weight:700; color:#374151; margin:14px 0 4px; }
.help-section p { font-size:13px; color:#4b5563; line-height:1.75; margin:0 0 10px; }
.help-section ul, .help-section ol { padding-left:20px; margin:0 0 12px; }
.help-section li { font-size:13px; color:#4b5563; line-height:1.85; }
.help-section li strong { color:#111827; }
.help-section li + li { margin-top:1px; }

/* ── Badges ── */
.hb { display:inline-block; font-size:10px; font-weight:700; border-radius:4px; padding:2px 7px; vertical-align:middle; margin:0 1px; white-space:nowrap; }
.hb-doc   { background:#eff6ff; color:#1d4ed8; }
.hb-asst  { background:#f0fdf4; color:#15803d; }
.hb-rec   { background:#fefce8; color:#a16207; }
.hb-all   { background:#f5f3ff; color:#6d28d9; }

/* ── Status flow ── */
.flow { display:flex; align-items:center; flex-wrap:wrap; gap:6px; margin:10px 0 16px; }
.flow-step { background:#f9fafb; border:1.5px solid #e5e7eb; border-radius:6px; padding:5px 13px; font-size:12px; font-weight:600; color:#374151; }
.flow-step.green  { background:#f0fdf4; border-color:#86efac; color:#166534; }
.flow-step.blue   { background:#eff6ff; border-color:#93c5fd; color:#1e40af; }
.flow-step.orange { background:#fff7ed; border-color:#fdba74; color:#c2410c; }
.flow-step.purple { background:#f5f3ff; border-color:#c4b5fd; color:#5b21b6; }
.flow-step.red    { background:#fef2f2; border-color:#fca5a5; color:#991b1b; }
.flow-arrow { color:#9ca3af; font-size:13px; font-weight:700; }

/* ── Callout boxes ── */
.tip  { background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:11px 14px; font-size:12px; color:#92400e; margin:12px 0; display:flex; gap:9px; }
.note { background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:11px 14px; font-size:12px; color:#1e40af; margin:12px 0; display:flex; gap:9px; }
.warn { background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:11px 14px; font-size:12px; color:#991b1b; margin:12px 0; display:flex; gap:9px; }
.tip i, .note i, .warn i { margin-top:1px; flex-shrink:0; }
.tip p, .note p, .warn p { margin:0; line-height:1.6; }

/* ── Scenario box ── */
.scenario { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:14px 16px; margin:12px 0; }
.scenario .sc-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#64748b; margin-bottom:8px; display:flex; align-items:center; gap:6px; }
.scenario ol { margin:0; padding-left:18px; }
.scenario ol li { font-size:12px; color:#374151; line-height:1.8; }

/* ── Permission table ── */
.perm-table { width:100%; border-collapse:collapse; font-size:12px; margin:10px 0; }
.perm-table th { background:#f9fafb; padding:7px 12px; text-align:left; font-weight:700; color:#374151; border:1px solid #e5e7eb; }
.perm-table td { padding:7px 12px; border:1px solid #e5e7eb; color:#4b5563; vertical-align:middle; }
.perm-table td.yes { color:#16a34a; font-weight:700; text-align:center; }
.perm-table td.no  { color:#9ca3af; text-align:center; }
.perm-table td.part{ color:#d97706; font-weight:600; text-align:center; font-size:11px; }

/* ── Column explainer ── */
.col-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:8px; margin:10px 0; }
.col-item { background:#f9fafb; border:1px solid #e5e7eb; border-radius:7px; padding:9px 12px; }
.col-item .col-name { font-size:11px; font-weight:700; color:#111827; margin-bottom:3px; }
.col-item .col-desc { font-size:11px; color:#6b7280; line-height:1.5; }

/* ── FAQ ── */
.faq-item { border:1px solid #e5e7eb; border-radius:8px; margin-bottom:8px; overflow:hidden; }
.faq-q { padding:12px 16px; font-size:13px; font-weight:600; color:#1f2937; cursor:pointer; display:flex; justify-content:space-between; align-items:center; background:#fff; user-select:none; }
.faq-q:hover { background:#f9fafb; }
.faq-q .faq-arrow { color:#9ca3af; font-size:11px; transition:.2s; }
.faq-item.open .faq-q .faq-arrow { transform:rotate(180deg); }
.faq-a { display:none; padding:0 16px 14px; font-size:12px; color:#4b5563; line-height:1.75; border-top:1px solid #f3f4f6; background:#fff; }
.faq-item.open .faq-a { display:block; padding-top:12px; }

/* ── Step list ── */
.steps { counter-reset:step-counter; padding:0; margin:10px 0 14px; list-style:none; }
.steps li { display:flex; gap:12px; align-items:flex-start; margin-bottom:10px; font-size:13px; color:#4b5563; line-height:1.65; }
.steps li::before { counter-increment:step-counter; content:counter(step-counter); min-width:24px; height:24px; background:var(--primary); color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:800; flex-shrink:0; margin-top:1px; }
</style>

<!-- PAGE HEADER -->
<div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:22px;">
    <div>
        <h1 class="page-title" style="margin:0 0 4px;"><i class="fas fa-book-open"></i> Help &amp; User Guide</h1>
        <p style="margin:0;font-size:12px;color:#9ca3af;">Complete guide to using the Dr. Feelgood clinic portal</p>
    </div>
    <div style="font-size:11px;color:#6b7280;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:8px 14px;line-height:2;">
        <strong>Role legend:</strong><br>
        <span class="hb hb-doc">Doctor</span> Full access &nbsp;
        <span class="hb hb-asst">Asst. Doctor</span> Clinical access &nbsp;
        <span class="hb hb-rec">Reception</span> Front-desk only &nbsp;
        <span class="hb hb-all">All roles</span>
    </div>
</div>

<div class="help-layout">

<!-- ════ TOC ════ -->
<div>
<div class="help-toc">
    <div class="toc-title">Contents</div>
    <div class="toc-group">Getting Started</div>
    <a href="#overview">System Overview</a>
    <a href="#roles">Roles &amp; Permissions</a>
    <a href="#daily-workflow">Typical Clinic Day</a>
    <div class="toc-group">Pages</div>
    <a href="#dashboard">Dashboard</a>
    <a href="#appointments">Appointments / Queue</a>
    <a href="#walkin">Walk-in Token</a>
    <a href="#patients">Patient List</a>
    <a href="#patient-detail">Patient Detail &amp; History</a>
    <a href="#invoice">Invoice / Billing</a>
    <a href="#reports">Reports</a>
    <a href="#users">User Management</a>
    <a href="#settings">Clinic Settings</a>
    <div class="toc-group">Reference</div>
    <a href="#faq">FAQ</a>
</div>
</div>

<!-- ════ CONTENT ════ -->
<div>

<!-- ── System Overview ── -->
<div class="help-section" id="overview">
    <h2><span class="sec-icon" style="background:#eff6ff;color:#2563eb;"><i class="fas fa-info-circle"></i></span> System Overview</h2>

    <p>Dr. Feelgood is a clinic management portal that handles everything from the moment a patient books or walks in, through their consultation, to their invoice and visit history. It is designed for small to mid-size clinics with a doctor, assistant, and reception desk.</p>

    <p>The system has <strong>three core areas</strong>:</p>
    <ul>
        <li><strong>Appointment Queue</strong> — who is coming today, in what order, and what stage they are at</li>
        <li><strong>Patient Records</strong> — a permanent card for every patient: contact info, visit history, medicines, and invoices</li>
        <li><strong>Administration</strong> — managing staff logins, clinic hours, slot settings, and billing details</li>
    </ul>

    <div class="note"><i class="fas fa-lock"></i><p>This Help page is only visible to the <strong>Doctor</strong> role. Reception and Asst. Doctor staff will not see it in their sidebar.</p></div>

    <h3>How the system connects day-to-day</h3>
    <p>A patient either books a slot online (pre-booked) or walks into the clinic (walk-in). Reception adds them to the queue and marks them arrived when they come in. The doctor calls them from the queue, conducts the consultation, marks it finished, and the system opens their patient record to log medicines and the bill. The patient gets an invoice. That's the full loop.</p>
</div>

<!-- ── Roles ── -->
<div class="help-section" id="roles">
    <h2><span class="sec-icon" style="background:#f0fdf4;color:#16a34a;"><i class="fas fa-users-cog"></i></span> Roles &amp; Permissions</h2>

    <p>Every user has one of three roles. The role controls what pages they can see and what actions they can take.</p>

    <table class="perm-table">
        <thead>
            <tr>
                <th>Feature / Page</th>
                <th><span class="hb hb-doc">Doctor</span></th>
                <th><span class="hb hb-asst">Asst. Doctor</span></th>
                <th><span class="hb hb-rec">Reception</span></th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Dashboard</td><td class="yes">✓</td><td class="yes">✓</td><td class="yes">✓</td></tr>
            <tr><td>Appointments / Queue — view</td><td class="yes">✓</td><td class="yes">✓</td><td class="yes">✓</td></tr>
            <tr><td>Mark Arrived / Not Arrived / Cancel</td><td class="yes">✓</td><td class="yes">✓</td><td class="yes">✓</td></tr>
            <tr><td>Call patient (In Consultation)</td><td class="yes">✓</td><td class="yes">✓</td><td class="no">—</td></tr>
            <tr><td>Finish consultation</td><td class="yes">✓</td><td class="yes">✓</td><td class="no">—</td></tr>
            <tr><td>Walk-in Token</td><td class="yes">✓</td><td class="yes">✓</td><td class="yes">✓</td></tr>
            <tr><td>Patient List — view</td><td class="yes">✓</td><td class="yes">✓</td><td class="yes">✓</td></tr>
            <tr><td>Patient Detail — visit dates</td><td class="yes">✓</td><td class="yes">✓</td><td class="yes">✓</td></tr>
            <tr><td>Patient Detail — medicines &amp; amounts</td><td class="yes">✓</td><td class="yes">✓</td><td class="no">Hidden</td></tr>
            <tr><td>Add / edit visit records</td><td class="yes">✓</td><td class="yes">✓</td><td class="no">—</td></tr>
            <tr><td>Print Invoice</td><td class="yes">✓</td><td class="yes">✓</td><td class="no">—</td></tr>
            <tr><td>Reports</td><td class="yes">✓</td><td class="yes">✓</td><td class="no">—</td></tr>
            <tr><td>User Management</td><td class="yes">✓</td><td class="no">—</td><td class="no">—</td></tr>
            <tr><td>Clinic Settings</td><td class="yes">✓</td><td class="no">—</td><td class="no">—</td></tr>
            <tr><td>Help page</td><td class="yes">✓</td><td class="no">—</td><td class="no">—</td></tr>
        </tbody>
    </table>

    <div class="tip"><i class="fas fa-lightbulb"></i><p>Create a separate login for each staff member — never share the Doctor password. If a staff member leaves, you can deactivate their account without touching anyone else's access.</p></div>
</div>

<!-- ── Typical Clinic Day ── -->
<div class="help-section" id="daily-workflow">
    <h2><span class="sec-icon" style="background:#fefce8;color:#d97706;"><i class="fas fa-sun"></i></span> Typical Clinic Day — Step by Step</h2>

    <p>Here is how a normal working day flows through the system, from the perspective of each role.</p>

    <h3>Morning: Reception starts the queue</h3>
    <ol class="steps">
        <li>Reception logs in and opens the <strong>Dashboard</strong> or <strong>Appointments</strong> page — pre-booked patients for today are already listed with status <em>Waiting</em>.</li>
        <li>When a booked patient arrives, Reception clicks <strong>Arrived</strong> next to their name. The row turns green.</li>
        <li>For a patient who just walked in without a booking, Reception clicks <strong>Walk-in Token</strong>, searches for the patient (or types a new name), optionally picks a slot, and clicks <strong>Generate Token</strong>. The patient appears in the queue.</li>
        <li>If a booked patient has not arrived by their slot time, the row turns orange automatically with a <strong>Late</strong> badge. Reception can click <strong>Not Arrived</strong> to flag them, which lets the doctor skip to the next patient.</li>
        <li>If a "Not Arrived" patient shows up later, Reception clicks <strong>Arrived Late</strong> to bring them back into the queue.</li>
    </ol>

    <h3>During clinic: Doctor works the queue</h3>
    <ol class="steps">
        <li>Doctor opens <strong>Appointments</strong> and sees which patients are <em>Arrived</em> (green rows) — these are ready to be called in.</li>
        <li>Doctor clicks <strong>Call</strong> on the next patient. Status changes to <em>In Consultation</em> and the system opens that patient's record in a new tab.</li>
        <li>After the consultation, doctor clicks <strong>Finish</strong> in the queue. Status becomes <em>Completed</em>.</li>
        <li>Doctor fills in the visit record on the patient page — medicines prescribed, clinical notes, and the amount charged.</li>
        <li>If needed, doctor prints the <strong>Invoice</strong> from the visit record.</li>
    </ol>

    <h3>End of day</h3>
    <ol class="steps">
        <li>Doctor reviews the Dashboard — <em>Seen Today</em> card shows total completed. <em>Queue clear</em> means no patients are still pending.</li>
        <li>Any remaining <em>Waiting</em> or <em>Arrived</em> patients that never came in can be marked <em>Cancelled</em>.</li>
        <li>For weekly/monthly review, go to <strong>Reports → Patients</strong>.</li>
    </ol>

    <div class="scenario">
        <div class="sc-title"><i class="fas fa-play-circle"></i> Real example — evening clinic (5:00 PM)</div>
        <ol>
            <li>5:00 PM — Walk-in patient arrives. Reception adds walk-in token #1. Clicks Arrived.</li>
            <li>5:15 PM — Booked patient (slot 5:15) hasn't arrived yet. Row turns orange (Late badge). Doctor calls walk-in #1 instead.</li>
            <li>5:20 PM — Late patient arrives. Reception clicks <strong>Arrived Late</strong>. Patient joins queue with Late badge still visible so doctor knows they were delayed.</li>
            <li>5:25 PM — Doctor finishes with walk-in #1, clicks Finish. System opens patient record. Doctor logs medicines + ₹300.</li>
            <li>5:30 PM — Doctor calls the late booked patient. Consultation starts.</li>
        </ol>
    </div>
</div>

<!-- ── Dashboard ── -->
<div class="help-section" id="dashboard">
    <h2><span class="sec-icon" style="background:#eff6ff;color:#2563eb;"><i class="fas fa-th-large"></i></span> Dashboard</h2>
    <span class="hb hb-all">All roles</span>

    <h3>Stat Cards — top row</h3>
    <div class="col-grid">
        <div class="col-item">
            <div class="col-name"><i class="fas fa-users" style="color:#2563eb;"></i> Total Patients</div>
            <div class="col-desc">All registered patients ever. Includes inactive / old records.</div>
        </div>
        <div class="col-item">
            <div class="col-name"><i class="fas fa-file-medical" style="color:#16a34a;"></i> Progress Reports</div>
            <div class="col-desc">Total visit records logged across all patients and all time.</div>
        </div>
        <div class="col-item">
            <div class="col-name"><i class="fas fa-user-plus" style="color:#d97706;"></i> New This Month</div>
            <div class="col-desc">Patients first registered in the current calendar month.</div>
        </div>
        <div class="col-item">
            <div class="col-name"><i class="fas fa-check-circle" style="color:#7c3aed;"></i> Seen Today</div>
            <div class="col-desc">Consultations completed today. Sub-text shows how many are still pending (waiting + in clinic + with doctor).</div>
        </div>
    </div>

    <h3>Today's Appointments table</h3>
    <p>A live table of all appointments for today — same columns and same action buttons as the full Appointments page. The mini stats bar above it shows <strong>waiting / in clinic / in consult / done</strong> counts at a glance. All status buttons work here — you don't need to go to the Appointments page to manage the queue.</p>

    <h3>Quick Actions</h3>
    <p>Six shortcut cards for the most common tasks. Doctor sees all six including Reports and Settings. Reception sees Walk-in Token, Search Patient, New Patient, and Appointments.</p>

    <h3>Recently Registered Patients</h3>
    <p>The 10 most recently registered patients. Useful to quickly check if a new registration was saved correctly. Click the eye icon to open their record.</p>

    <div class="tip"><i class="fas fa-lightbulb"></i><p>The Dashboard is the best starting point each morning — you can see pending appointments and act on them without navigating away.</p></div>
</div>

<!-- ── Appointments ── -->
<div class="help-section" id="appointments">
    <h2><span class="sec-icon" style="background:#f5f3ff;color:#7c3aed;"><i class="fas fa-calendar-check"></i></span> Appointments / Queue</h2>
    <span class="hb hb-all">All roles</span>

    <h3>Three views</h3>
    <ul>
        <li><strong>Today</strong> — live queue for one specific date. Use the ← → arrows or date picker to move between days. Auto-refreshes every 60 seconds.</li>
        <li><strong>This Week</strong> — all appointments from Monday to Sunday of the current week. Rows are grouped by date.</li>
        <li><strong>This Month</strong> — all appointments in the current month. Good for reviewing no-shows and cancellations.</li>
    </ul>

    <h3>Appointment types</h3>
    <ul>
        <li><span style="background:#e5e7eb;color:#374151;border-radius:4px;padding:1px 7px;font-size:11px;font-weight:600;"><i class="fas fa-walking"></i> Walk-in</span> — added by Reception from the Walk-in Token page. Patient came without a prior booking.</li>
        <li><span style="background:#7c3aed;color:#fff;border-radius:4px;padding:1px 7px;font-size:11px;font-weight:600;"><i class="fas fa-calendar-check"></i> Booked</span> — patient booked a slot online in advance through the public booking page.</li>
    </ul>

    <h3>Full status flow — Walk-in</h3>
    <div class="flow">
        <span class="flow-step">Waiting</span>
        <span class="flow-arrow">→</span>
        <span class="flow-step green">Arrived</span>
        <span class="flow-arrow">→</span>
        <span class="flow-step blue">In Consultation</span>
        <span class="flow-arrow">→</span>
        <span class="flow-step purple">Completed</span>
    </div>
    <p>Walk-ins are added by Reception and the patient is already in clinic when the token is created, so Reception simply clicks <strong>Arrived</strong> straight away.</p>

    <h3>Full status flow — Pre-booked</h3>
    <div class="flow">
        <span class="flow-step">Waiting</span>
        <span class="flow-arrow">→</span>
        <span class="flow-step green">Arrived</span>
        <span class="flow-arrow">→</span>
        <span class="flow-step blue">In Consultation</span>
        <span class="flow-arrow">→</span>
        <span class="flow-step purple">Completed</span>
    </div>
    <div class="flow">
        <span class="flow-step">Waiting</span>
        <span class="flow-arrow">→</span>
        <span class="flow-step orange">Not Arrived</span>
        <span class="flow-arrow">→</span>
        <span class="flow-step green">Arrived Late</span>
        <span class="flow-arrow" style="color:#9ca3af;">→ then continue above</span>
    </div>
    <div class="flow">
        <span class="flow-step">Waiting / Arrived</span>
        <span class="flow-arrow">→</span>
        <span class="flow-step red">Cancelled</span>
    </div>

    <h3>What each action button does</h3>
    <table class="perm-table">
        <thead><tr><th>Status</th><th>Button</th><th>Who can click</th><th>What happens</th></tr></thead>
        <tbody>
            <tr><td>Waiting (Walk-in)</td><td>Arrived</td><td>All roles</td><td>Marks patient as in clinic, row turns green</td></tr>
            <tr><td>Waiting (Booked)</td><td>Arrived</td><td>All roles</td><td>Marks patient as in clinic, row turns green</td></tr>
            <tr><td>Waiting (Booked)</td><td>Not Arrived</td><td>All roles</td><td>Flags patient as absent; row fades. Doctor can skip to next patient.</td></tr>
            <tr><td>Waiting / Arrived</td><td>✕ Cancel</td><td>All roles</td><td>Cancels the appointment. Can't be undone — use carefully.</td></tr>
            <tr><td>Arrived</td><td>Call</td><td>Doctor, Asst.</td><td>Moves to In Consultation; system opens patient record</td></tr>
            <tr><td>In Consultation</td><td>Finish</td><td>Doctor, Asst.</td><td>Marks Completed; use to log medicines and generate invoice</td></tr>
            <tr><td>Not Arrived</td><td>Arrived Late</td><td>All roles</td><td>Brings patient back as Arrived (with Late badge still showing)</td></tr>
        </tbody>
    </table>

    <h3>Late badge — what it means</h3>
    <p>The system automatically shows an orange <strong>Late</strong> badge and highlights the row in orange when ALL of the following are true:</p>
    <ul>
        <li>The appointment is <em>Pre-booked</em> (not walk-in)</li>
        <li>The appointment date is today</li>
        <li>The patient's slot time has already passed</li>
        <li>The patient is still in <em>Waiting</em> or <em>Arrived</em> status (not yet called or completed)</li>
    </ul>
    <p>No action is needed — it is just a visual alert. If the patient hasn't come, click <strong>Not Arrived</strong>. If they came late, they already have the Late badge showing so the doctor is aware.</p>

    <h3>Queue table columns explained</h3>
    <div class="col-grid">
        <div class="col-item"><div class="col-name"># Token</div><div class="col-desc">Token number assigned in order — used to call patients out loud.</div></div>
        <div class="col-item"><div class="col-name">Patient</div><div class="col-desc">Clickable link to patient record. "New" badge = unregistered walk-in.</div></div>
        <div class="col-item"><div class="col-name">Phone</div><div class="col-desc">Patient's contact number.</div></div>
        <div class="col-item"><div class="col-name">Type</div><div class="col-desc">Walk-in (grey) or Booked (purple).</div></div>
        <div class="col-item"><div class="col-name">Slot</div><div class="col-desc">Assigned time slot. Walk-ins without a slot show —. Late badge appears here if overdue.</div></div>
        <div class="col-item"><div class="col-name">IN</div><div class="col-desc">Time when doctor clicked Call (consultation start).</div></div>
        <div class="col-item"><div class="col-name">OUT</div><div class="col-desc">Time when doctor clicked Finish (consultation end).</div></div>
        <div class="col-item"><div class="col-name">Complaint</div><div class="col-desc">Chief complaint entered during booking or walk-in.</div></div>
        <div class="col-item"><div class="col-name">Status</div><div class="col-desc">Current stage: Waiting, Arrived, In Consult, Completed, Not Arrived, Cancelled.</div></div>
        <div class="col-item"><div class="col-name">Actions</div><div class="col-desc">Status-change buttons. Change based on current status and your role.</div></div>
    </div>

    <h3>Filter tabs</h3>
    <p>Above the table: <strong>All / Waiting / Arrived / In Consult / Completed / Not Arrived / Cancelled</strong>. Click any tab to show only those rows — the page does not reload. Useful when the queue is long and you want to focus on a specific group.</p>

    <h3>Stats block (Today view only)</h3>
    <ul>
        <li><strong>Total</strong> — all appointments for the day</li>
        <li><strong>Waiting</strong> — booked but not yet arrived</li>
        <li><strong>In Clinic</strong> — arrived, waiting to be called</li>
        <li><strong>In Consult</strong> — currently with the doctor</li>
        <li><strong>Completed</strong> — finished for the day</li>
    </ul>

    <div class="tip"><i class="fas fa-sync"></i><p>The Today view auto-refreshes every 60 seconds. All staff — Reception, Doctor, Asst. Doctor — see the same live queue. No need to manually reload.</p></div>
</div>

<!-- ── Walk-in Token ── -->
<div class="help-section" id="walkin">
    <h2><span class="sec-icon" style="background:#fefce8;color:#d97706;"><i class="fas fa-ticket-alt"></i></span> Walk-in Token</h2>
    <span class="hb hb-all">All roles</span>

    <p>Use this to instantly add any patient to the queue — either someone who just walked in or an advance entry for a future date.</p>

    <h3>Step-by-step</h3>
    <ol class="steps">
        <li><strong>Search for the patient</strong> — type 2+ characters of the name or phone number. Select from the dropdown if the patient is already registered.</li>
        <li><strong>New patient?</strong> — leave the search empty and type the name and phone manually. A new patient record will be created automatically when you submit.</li>
        <li><strong>Set the date</strong> — defaults to today. Change to a future date to pre-book a walk-in.</li>
        <li><strong>Follow-up?</strong> — select Yes if this is a follow-up to a previous visit (affects how the visit is recorded).</li>
        <li><strong>Pick a slot (optional)</strong> — click a time slot to assign one. Greyed-out slots are full. Walk-ins without a slot join after all booked patients for any given time.</li>
        <li><strong>Extended Hours toggle</strong> — shows extra slots beyond normal clinic hours. Only visible to clinic staff, never to patients booking online.</li>
        <li><strong>Chief Complaint</strong> — brief reason for the visit. Appears in the queue table and helps the doctor prepare.</li>
        <li>Click <strong>Generate Token</strong>. A large token number appears — announce this to the patient so they know their queue position.</li>
    </ol>

    <div class="note"><i class="fas fa-user-plus"></i><p>If you type a new patient's name and phone and submit, the system creates a patient record automatically. You can later open that record to add their full details (age, gender, blood group, etc.).</p></div>

    <h3>After the token is generated</h3>
    <ul>
        <li>Click <strong>New Token</strong> to add another patient without losing your place</li>
        <li>Click <strong>View Queue</strong> to go back to the Appointments page</li>
        <li>Click <strong>View Patient</strong> (if shown) to open that patient's record directly</li>
    </ul>
</div>

<!-- ── Patients ── -->
<div class="help-section" id="patients">
    <h2><span class="sec-icon" style="background:#f0fdf4;color:#16a34a;"><i class="fas fa-users"></i></span> Patient List</h2>
    <span class="hb hb-all">All roles</span>

    <h3>Finding patients</h3>
    <ul>
        <li><strong>Search box</strong> — type any part of the patient's name, phone number, or patient ID. Results update as you type (after a brief pause). No need to press Enter.</li>
        <li><strong>Per page</strong> — choose 10, 25, 50, or 100 records per page using the dropdown on the right.</li>
        <li><strong>Pagination</strong> — use the page buttons below the table. Pages load instantly without refreshing the whole page.</li>
    </ul>

    <h3>Table columns</h3>
    <div class="col-grid">
        <div class="col-item"><div class="col-name">Patient ID</div><div class="col-desc">Unique clinic ID assigned at registration (e.g. P-00234).</div></div>
        <div class="col-item"><div class="col-name">Name</div><div class="col-desc">Full name. Click View to open their detail page.</div></div>
        <div class="col-item"><div class="col-name">Phone</div><div class="col-desc">Primary contact number.</div></div>
        <div class="col-item"><div class="col-name">Gender / Age</div><div class="col-desc">M/F badge and age at registration.</div></div>
        <div class="col-item"><div class="col-name">Chief Complaint</div><div class="col-desc">Primary reason for first visit.</div></div>
        <div class="col-item"><div class="col-name">Registered</div><div class="col-desc">Date the patient was first added to the system.</div></div>
    </div>

    <h3>Registering a new patient</h3>
    <p>Click the <strong>New Patient</strong> button (top right). Fill in at minimum:</p>
    <ul>
        <li><strong>First Name</strong> (required)</li>
        <li><strong>Gender</strong> (required)</li>
        <li>Phone, Date of Birth, Chief Complaint — strongly recommended</li>
    </ul>
    <p>The system assigns a unique Patient ID automatically.</p>

    <div class="note"><i class="fas fa-info-circle"></i><p>You do not need to register a patient before their first walk-in. The Walk-in Token form can create a basic record on the fly. Come back later to fill in the full details.</p></div>
</div>

<!-- ── Patient Detail ── -->
<div class="help-section" id="patient-detail">
    <h2><span class="sec-icon" style="background:#fff7ed;color:#d97706;"><i class="fas fa-user-circle"></i></span> Patient Detail &amp; Visit History</h2>
    <span class="hb hb-doc">Doctor</span>
    <span class="hb hb-asst">Asst. Doctor</span>
    <span class="hb hb-rec">Reception (limited view)</span>

    <h3>Patient info card</h3>
    <p>The top section shows the patient's full profile: name, age, gender, blood group, contact number, email, date of registration, and chief complaint. Doctor and Asst. Doctor can edit any of these fields using the Edit button.</p>

    <h3>Visit History</h3>
    <p>Below the profile is a chronological list of every visit to the clinic. Each visit card shows:</p>
    <ul>
        <li><strong>Visit Date</strong> — always visible to all roles</li>
        <li><strong>Medicines prescribed</strong> — name, dosage, frequency — Doctor &amp; Asst. Doctor only</li>
        <li><strong>Clinical notes / symptoms</strong> — Doctor &amp; Asst. Doctor only</li>
        <li><strong>Amount charged (₹)</strong> — Doctor &amp; Asst. Doctor only</li>
        <li><strong>Print Invoice</strong> button — Doctor &amp; Asst. Doctor only</li>
        <li><strong>Edit visit</strong> button — to correct medicines or notes after the fact</li>
    </ul>

    <div class="warn"><i class="fas fa-eye-slash"></i><p>Reception staff see <strong>only the visit date</strong> — no medicines, no amounts, no clinical notes. This is intentional to protect clinical and financial privacy.</p></div>

    <h3>Logging a new visit (after consultation)</h3>
    <ol class="steps">
        <li>In the Appointments queue, click <strong>Finish</strong> on the patient's row. The system automatically opens this patient's detail page.</li>
        <li>Scroll down to the <strong>Add Visit Record</strong> form.</li>
        <li>Enter the medicines prescribed (name, dosage, instructions).</li>
        <li>Add clinical notes — symptoms, diagnosis, follow-up instructions.</li>
        <li>Enter the amount charged for this visit.</li>
        <li>Click <strong>Save Visit</strong>. The record is added to the history list above.</li>
    </ol>

    <h3>Editing an existing visit</h3>
    <p>Click the <strong>Edit</strong> button on any visit card to correct or update that record. This is useful if you need to add a forgotten medicine or correct an amount. All changes are saved immediately.</p>
</div>

<!-- ── Invoice ── -->
<div class="help-section" id="invoice">
    <h2><span class="sec-icon" style="background:#f0fdf4;color:#16a34a;"><i class="fas fa-file-invoice"></i></span> Invoice / Billing</h2>
    <span class="hb hb-doc">Doctor</span>
    <span class="hb hb-asst">Asst. Doctor</span>

    <h3>Printing an invoice</h3>
    <p>On the patient detail page, each visit record has a <strong>Print Invoice</strong> button. Clicking it opens a professional A4-format invoice in a new tab. From there, click the <strong>Print</strong> button or press <kbd>Ctrl+P</kbd> / <kbd>⌘P</kbd>.</p>

    <h3>What appears on the invoice</h3>
    <ul>
        <li>Clinic name, full address, doctor name, qualification</li>
        <li>Clinic phone and email</li>
        <li>Patient name, age, gender</li>
        <li>Visit date and auto-generated invoice number</li>
        <li>One line item combining Consultation + Medicines</li>
        <li>Total amount, GST amount (if GST is enabled), and grand total</li>
        <li>PAN number (if enabled in settings)</li>
    </ul>

    <h3>Configuring invoice details</h3>
    <p>Go to <strong>Settings → Invoice / Billing Settings</strong> to set up:</p>
    <ul>
        <li>Doctor's full name and qualifications (e.g. MBBS, MD)</li>
        <li>Full clinic address — appears formatted on the invoice header</li>
        <li>Clinic phone and email</li>
        <li>PAN number (toggle to show/hide)</li>
        <li>GST — toggle on/off, enter GST number and rate (%)</li>
    </ul>

    <div class="tip"><i class="fas fa-lightbulb"></i><p>Set up the invoice details in Settings before generating your first invoice. If the doctor name or address is blank, the invoice header will look incomplete.</p></div>
</div>

<!-- ── Reports ── -->
<div class="help-section" id="reports">
    <h2><span class="sec-icon" style="background:#f5f3ff;color:#7c3aed;"><i class="fas fa-chart-bar"></i></span> Reports</h2>
    <span class="hb hb-doc">Doctor</span>
    <span class="hb hb-asst">Asst. Doctor</span>

    <h3>Patient Report</h3>
    <p>Found at <strong>Reports → Patients</strong>. Filter the full patient database by:</p>
    <ul>
        <li>Date range (registration date)</li>
        <li>Gender (Male / Female / All)</li>
        <li>Age group</li>
    </ul>
    <p>Use this to understand patient demographics, track new registrations month-over-month, and get a filtered list for follow-up.</p>

    <div class="note"><i class="fas fa-clock"></i><p>Income, Queue/Operations, Medicines, and Productivity reports are planned for a future update and will appear in the sidebar once enabled.</p></div>
</div>

<!-- ── User Management ── -->
<div class="help-section" id="users">
    <h2><span class="sec-icon" style="background:#eff6ff;color:#2563eb;"><i class="fas fa-users-cog"></i></span> User Management</h2>
    <span class="hb hb-doc">Doctor only</span>

    <p>Manage all login accounts for clinic staff. Only the Doctor role can access this page.</p>

    <h3>Adding a new user</h3>
    <ol class="steps">
        <li>Click <strong>Add User</strong> (top right).</li>
        <li>Fill in First Name, Last Name (optional), Username, Email, Contact Number.</li>
        <li>Choose a <strong>Role</strong> — Doctor, Asst. Doctor, or Reception.</li>
        <li>Set a password (minimum 6 characters).</li>
        <li>Click <strong>Save</strong>. The user can now log in immediately.</li>
    </ol>
    <div class="warn"><i class="fas fa-exclamation-triangle"></i><p>The <strong>username cannot be changed</strong> after creation — choose carefully. Use something simple like first name + role (e.g. <em>priya_rec</em>).</p></div>

    <h3>Editing a user</h3>
    <ul>
        <li>Click the <strong>pencil icon</strong> on the user row to open the edit form.</li>
        <li>Change name, email, contact, or role as needed.</li>
        <li>To reset their password, enter a new one. Leave it blank to keep the current password unchanged.</li>
        <li>Set <strong>Status to Inactive</strong> to block that user from logging in — useful when a staff member leaves. Their data is preserved.</li>
    </ul>

    <h3>Deleting a user</h3>
    <p>Click the <strong>trash icon</strong>. You will see a confirmation prompt. Note: you cannot delete your own account — the trash icon is hidden on your own row.</p>

    <div class="tip"><i class="fas fa-shield-alt"></i><p>Best practice: when a staff member leaves, set their account to <strong>Inactive</strong> rather than deleting it. This preserves any appointment and visit records linked to them while blocking their login.</p></div>
</div>

<!-- ── Settings ── -->
<div class="help-section" id="settings">
    <h2><span class="sec-icon" style="background:#f1f5f9;color:#475569;"><i class="fas fa-cog"></i></span> Clinic Settings</h2>
    <span class="hb hb-doc">Doctor only</span>

    <p>All clinic configuration lives here. Changes take effect immediately — no restart needed.</p>

    <h3>Clinic Info</h3>
    <ul>
        <li><strong>Clinic Name</strong> — appears on the public booking page and dashboard header</li>
        <li><strong>Phone</strong> — clinic contact number</li>
        <li><strong>Consultation Fee (₹)</strong> — default fee pre-filled when logging a new visit record</li>
    </ul>

    <h3>Slot Duration &amp; Max Per Slot</h3>
    <ul>
        <li><strong>Slot Duration</strong> — 15 minutes or 30 minutes. Determines how many slots fit in each session window.</li>
        <li><strong>Max Per Slot</strong> — how many patients can book the same time slot online. Set to 1 for one-at-a-time. Set higher if the doctor sees multiple patients simultaneously.</li>
    </ul>
    <div class="tip"><i class="fas fa-lightbulb"></i><p>Example: Evening session 5:00–8:00 PM with 30-min slots = 6 slots. With Max Per Slot = 2 the booking page allows up to 12 bookings in that session.</p></div>

    <h3>Monday – Saturday Sessions</h3>
    <p>You can have a Morning session, an Evening session, both, or neither on any given day. Check the box to enable a session, then set the start and end times. Slots are auto-generated between those times at the chosen interval.</p>

    <h3>Sunday</h3>
    <p>Toggle <strong>Open on Sunday</strong> to make Sunday available for bookings. Set the session times below. If unchecked, the booking page shows no slots on Sundays.</p>

    <h3>Extended Hours (Walk-in Admin Only)</h3>
    <p>These are extra slot times shown <em>only</em> in the admin Walk-in form — never on the patient-facing booking page. Use this when the doctor occasionally accepts patients beyond the normal session end time. Toggle <strong>Extended Hours</strong> in the Walk-in form to see these extra slots.</p>

    <h3>Online Booking Window</h3>
    <p>Controls how far ahead patients can book online. Options: 7, 15, or 30 days from today. Choosing 7 days means patients can only book within the next week.</p>

    <h3>Invoice / Billing Settings</h3>
    <p>Everything that appears on the printed invoice header:</p>
    <ul>
        <li>Doctor's name and qualifications</li>
        <li>Full clinic address (multi-line supported)</li>
        <li>Clinic phone and email</li>
        <li>PAN number (checkbox to show/hide on invoice)</li>
        <li>GST — enable toggle, then enter GST number and rate. GST amount will be calculated and shown as a separate line on every invoice.</li>
    </ul>

    <h3>Closed / Holiday Dates</h3>
    <p>Add specific dates when the clinic will not see patients — holidays, doctor travel, etc. When a date is marked closed:</p>
    <ul>
        <li>The online booking page shows <em>"Clinic closed on this date"</em> with no slots</li>
        <li>The Walk-in form also shows the clinic closed message for that date</li>
    </ul>
    <p>Add a reason (e.g. "Diwali holiday") for your own reference — it does not appear to patients. Remove closed dates any time by clicking the ✕ next to them.</p>
</div>

<!-- ── FAQ ── -->
<div class="help-section" id="faq">
    <h2><span class="sec-icon" style="background:#fef9c3;color:#ca8a04;"><i class="fas fa-question-circle"></i></span> Frequently Asked Questions</h2>

    <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">A patient walked in but I accidentally clicked "Not Arrived" — how do I fix it? <i class="fas fa-chevron-down faq-arrow"></i></div>
        <div class="faq-a">Click the <strong>Arrived Late</strong> button that appears on the Not Arrived row. This brings the patient back into the queue with an "Arrived" status (and a Late badge so the doctor knows they were delayed).</div>
    </div>

    <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">Can I cancel an appointment after the patient is already marked Arrived? <i class="fas fa-chevron-down faq-arrow"></i></div>
        <div class="faq-a">Yes — the red ✕ Cancel button is available in both Waiting and Arrived states. Once cancelled the appointment cannot be undone, but you can add a new walk-in token for the same patient if needed.</div>
    </div>

    <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">What happens when I click "Call" for a patient? <i class="fas fa-chevron-down faq-arrow"></i></div>
        <div class="faq-a">The appointment status changes to <strong>In Consultation</strong>, the "IN" time is recorded, and the system opens the patient's detail page in a new tab so you can view their history while in the consultation. The reception desk sees "With Doctor" on their screen.</div>
    </div>

    <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">I forgot to add medicines after finishing a consultation. Can I still do it? <i class="fas fa-chevron-down faq-arrow"></i></div>
        <div class="faq-a">Yes. Go to <strong>Patients → find the patient → open their record</strong>. Find the visit from today and click <strong>Edit</strong> on that visit card. Update the medicines and amount, then save.</div>
    </div>

    <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">The queue page shows a patient row in orange. What does that mean? <i class="fas fa-chevron-down faq-arrow"></i></div>
        <div class="faq-a">The patient had a booked slot time that has already passed, but they are still shown as Waiting or Arrived. This is the <strong>Late</strong> indicator — automatic, no action required. If they haven't shown up, click <strong>Not Arrived</strong>. If they came late, they already have the badge so you can see that context.</div>
    </div>

    <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">A new receptionist joined. How do I give them access? <i class="fas fa-chevron-down faq-arrow"></i></div>
        <div class="faq-a">Go to <strong>Users</strong> (sidebar) → <strong>Add User</strong> → fill in their details → set Role to <strong>Reception</strong> → set a password → Save. They can log in immediately. Give them their username and password directly — the system does not send any email notifications.</div>
    </div>

    <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">How do I mark a day as a holiday so patients can't book? <i class="fas fa-chevron-down faq-arrow"></i></div>
        <div class="faq-a">Go to <strong>Settings → Closed / Holiday Dates</strong>. Pick the date, add a reason (optional), and click Add. That date will show "Clinic closed" on the public booking page and no slots will be available.</div>
    </div>

    <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">Can Reception see what medicines were prescribed? <i class="fas fa-chevron-down faq-arrow"></i></div>
        <div class="faq-a">No. Reception sees only the visit date on the patient detail page. Medicines, amounts, and clinical notes are hidden for all Reception accounts. This is by design to protect patient and financial privacy.</div>
    </div>

    <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">The invoice doesn't show my clinic address. How do I fix it? <i class="fas fa-chevron-down faq-arrow"></i></div>
        <div class="faq-a">Go to <strong>Settings → Invoice / Billing Settings</strong>. Fill in your Doctor Name, Qualification, Clinic Address, Phone, and Email. Click Save. All invoices printed after that will include the updated details.</div>
    </div>

    <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">The queue doesn't update automatically — do I need to refresh? <i class="fas fa-chevron-down faq-arrow"></i></div>
        <div class="faq-a">The Today view auto-refreshes every 60 seconds. For immediate updates after an action (like clicking Arrived), the page reloads instantly after the status change. If you're on Week or Month view, those do not auto-refresh — navigate back to Today view for the live queue.</div>
    </div>

</div><!-- /.faq section -->

</div><!-- /.help-content -->
</div><!-- /.help-layout -->

<script>
// ── TOC active link on scroll ──
const sections = document.querySelectorAll('.help-section');
const tocLinks  = document.querySelectorAll('.help-toc a');
function setActiveToc() {
    let current = '';
    sections.forEach(s => { if (s.getBoundingClientRect().top <= 90) current = s.id; });
    tocLinks.forEach(a => a.classList.toggle('active', a.getAttribute('href') === '#' + current));
}
window.addEventListener('scroll', setActiveToc, { passive:true });
setActiveToc();

// ── FAQ accordion ──
function toggleFaq(btn) {
    btn.closest('.faq-item').classList.toggle('open');
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
