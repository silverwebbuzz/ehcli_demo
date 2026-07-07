<?php
// New Patient mode — reached from the "New Patient" button on Today's Appointments.
// Captures fuller new-patient details AND books today's appointment in one step.
$newMode = ($_GET['new'] ?? '') === '1';
$page_title = $newMode ? 'New Patient — Book Appointment' : 'Book Appointment';
ob_start();
?>
<style>
.walkin-wrap { max-width:640px; margin:0 auto; }
.walkin-2col { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
@media(max-width:540px){ .walkin-2col { grid-template-columns:1fr; } }
.search-result-item { padding:7px 12px; cursor:pointer; border-bottom:1px solid #f3f4f6; font-size:12px; }
.search-result-item:hover { background:#f0f4ff; }
#patientResults { border:1px solid #d1d5db; border-radius:6px; background:#fff; max-height:200px; overflow-y:auto; display:none; position:relative; z-index:10; }

/* Slot grid */
.slot-section { margin-top:6px; }
.slot-session-lbl { font-size:10px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:.5px; margin:10px 0 5px; display:flex; align-items:center; gap:5px; }
.slot-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:6px; }
@media(max-width:400px){ .slot-grid { grid-template-columns:repeat(3,1fr); } }
.slot-pill { padding:7px 4px; border:2px solid #e5e7eb; border-radius:7px; text-align:center; cursor:pointer; font-size:11px; font-weight:600; color:#374151; background:#fff; transition:.15s; }
.slot-pill:hover { border-color:#93c5fd; background:#eff6ff; }
.slot-pill.selected { border-color:var(--primary); background:var(--primary); color:#fff; }
.slot-pill.full { opacity:.45; cursor:not-allowed; background:#f9fafb; border-color:#f3f4f6; color:#9ca3af; }
#slotLoading { font-size:12px; color:#9ca3af; padding:10px 0; }
#slotArea { min-height:40px; }
</style>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
    <h1 class="page-title" style="margin:0;"><?php echo $newMode ? '<i class="fas fa-user-plus"></i> New Patient — Book Appointment' : 'Book Appointment'; ?></h1>
    <a href="/queue" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Queue</a>
</div>

<div class="walkin-wrap">
<div class="card">
<div class="card-body">

<form id="walkinForm" data-new-mode="<?php echo $newMode ? '1' : '0'; ?>">

    <!-- Patient lookup (hidden in New Patient mode — the patient doesn't exist yet) -->
    <div class="mb-3" id="searchBlock" style="<?php echo $newMode ? 'display:none;' : ''; ?>">
        <label class="form-label fw-semibold">Patient Search</label>
        <div style="display:flex;gap:8px;margin-bottom:6px;">
            <input type="text" id="searchInput" class="form-control" placeholder="Search by name or phone..." autocomplete="off">
            <button type="button" class="btn btn-secondary btn-sm" onclick="clearPatient()">Clear</button>
        </div>
        <div id="patientResults"></div>
        <div id="selectedPatient" style="display:none;padding:7px 12px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:6px;font-size:12px;margin-top:4px;"></div>
        <div style="font-size:11px;color:#6b7280;margin-top:4px;">Leave empty for new / unregistered patient</div>
    </div>
    <input type="hidden" name="patient_id" id="patientId">

    <?php if ($newMode): ?>
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;border-radius:6px;padding:8px 12px;font-size:12px;margin-bottom:12px;">
        <i class="fas fa-user-plus"></i> Registering a <strong>new patient</strong> and booking their appointment together.
    </div>

    <!-- ── Full New Patient registration fields ── -->
    <div class="walkin-2col mb-3">
        <div>
            <label class="form-label">First Name *</label>
            <input type="text" name="fname" class="form-control" placeholder="First name" required>
        </div>
        <div>
            <label class="form-label">Last Name</label>
            <input type="text" name="lname" class="form-control" placeholder="Last name">
        </div>
    </div>
    <div class="walkin-2col mb-3">
        <div id="phoneRow">
            <label class="form-label">Contact Number *</label>
            <input type="text" name="patient_phone" id="patientPhoneInput" class="form-control" placeholder="Contact number" required>
        </div>
        <div>
            <label class="form-label">Date of Birth</label>
            <input type="date" name="dob" class="form-control">
        </div>
    </div>
    <div class="walkin-2col mb-3">
        <div>
            <label class="form-label">Age</label>
            <input type="number" name="age" id="patientAgeInput" class="form-control" placeholder="Age" min="0" max="150">
        </div>
        <div>
            <label class="form-label">Gender</label>
            <select name="gender" id="patientGenderInput" class="form-control">
                <option value="">-- Select --</option>
                <option value="M">Male</option>
                <option value="F">Female</option>
            </select>
        </div>
    </div>
    <div class="walkin-2col mb-3">
        <div>
            <label class="form-label">Marital Status</label>
            <select name="mrg_status" class="form-control">
                <option value="">-- Select --</option>
                <option value="S">Single</option>
                <option value="M">Married</option>
                <option value="D">Divorced</option>
                <option value="W">Widowed</option>
            </select>
        </div>
        <div>
            <label class="form-label">Diet</label>
            <select name="veg" class="form-control">
                <option value="">-- Select --</option>
                <option value="V">Vegetarian</option>
                <option value="NV">Non-Vegetarian</option>
                <option value="EV">Eggetarian</option>
            </select>
        </div>
    </div>
    <div class="walkin-2col mb-3">
        <div>
            <label class="form-label">Religion</label>
            <input type="text" name="religion" class="form-control">
        </div>
        <div>
            <label class="form-label">Referred By</label>
            <input type="text" name="refered_by" class="form-control">
        </div>
    </div>
    <div class="walkin-2col mb-3">
        <div>
            <label class="form-label">Occupation</label>
            <input type="text" name="occupation" class="form-control">
        </div>
        <div>
            <label class="form-label">Education</label>
            <input type="text" name="education" class="form-control">
        </div>
    </div>
    <div class="walkin-2col mb-3">
        <div>
            <label class="form-label">Address</label>
            <input type="text" name="address" class="form-control">
        </div>
        <div>
            <label class="form-label">City</label>
            <input type="text" name="city" class="form-control" value="Ahmedabad">
        </div>
    </div>
    <div class="walkin-2col mb-3">
        <div>
            <label class="form-label">State</label>
            <input type="text" name="state" class="form-control" value="Gujarat">
        </div>
        <div>
            <label class="form-label">ZIP Code</label>
            <input type="text" name="zip" class="form-control">
        </div>
    </div>

    <?php else: ?>
    <!-- Search-only walk-in: name, phone & ID come from the selected patient (read-only) -->
    <div class="walkin-2col mb-3">
        <div>
            <label class="form-label">Patient Name</label>
            <input type="text" name="patient_name" id="patientNameInput" class="form-control" placeholder="Select a patient above" readonly>
        </div>
        <div>
            <label class="form-label">Phone</label>
            <input type="text" name="patient_phone" id="patientPhoneInput" class="form-control" placeholder="—" readonly>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Patient ID</label>
        <input type="text" id="patientCodeInput" class="form-control" placeholder="—" readonly>
    </div>
    <?php endif; ?>

    <!-- Date -->
    <div class="mb-3">
        <label class="form-label">Date</label>
        <input type="date" name="appt_date" id="apptDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
    </div>

    <!-- Slot picker -->
    <div class="mb-3">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
            <label class="form-label fw-semibold" style="margin:0;">Time Slot <span style="font-weight:400;color:#9ca3af;font-size:11px;">(optional — assign a slot to avoid overlap)</span></label>
            <label style="font-size:12px;display:flex;align-items:center;gap:6px;cursor:pointer;color:#ef4444;font-weight:600;">
                <input type="checkbox" id="extendedHours" onchange="reloadSlots()">
                <i class="fas fa-clock"></i> Extended Hours
            </label>
        </div>
        <input type="hidden" name="slot_time" id="slotTimeInput">
        <div id="slotArea"><div id="slotLoading"><i class="fas fa-spinner fa-spin"></i> Loading slots…</div></div>
        <div style="font-size:11px;color:#6b7280;margin-top:4px;">
            Walk-ins without a slot join the queue after pre-booked patients for that time.
        </div>
    </div>

    <!-- Chief Complaint -->
    <div class="mb-3">
        <label class="form-label">Chief Complaint</label>
        <input type="text" name="chief_complaint" class="form-control" placeholder="Reason for visit">
    </div>

    <div id="formMsg" style="display:none;padding:8px 12px;border-radius:6px;font-size:12px;margin-bottom:10px;"></div>

    <div style="display:flex;gap:8px;">
        <button type="submit" class="btn btn-primary"><i class="fas fa-calendar-check"></i> <?php echo $newMode ? 'Register &amp; Book' : 'Book'; ?></button>
        <a href="/queue" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<!-- Booking confirmation -->
<div id="tokenDisplay" style="display:none;text-align:center;padding:24px 0;">
    <div style="width:64px;height:64px;border-radius:50%;background:#16a34a;color:#fff;display:flex;align-items:center;justify-content:center;font-size:30px;margin:0 auto 14px;">
        <i class="fas fa-check"></i>
    </div>
    <div style="font-size:16px;font-weight:700;color:#111827;">Appointment Booked</div>
    <div id="tokenName" style="font-size:14px;color:#374151;margin-top:4px;"></div>
    <div id="tokenSlot" style="font-size:13px;color:#6b7280;margin-top:2px;"></div>

    <div style="background:#fef3c7;color:#92400e;border-radius:6px;padding:9px 12px;font-size:12px;margin:16px auto 4px;max-width:420px;text-align:left;">
        <i class="fas fa-info-circle"></i>
        Please arrive 10 minutes before the slot and check in at reception.
    </div>
    <div style="background:#fef3c7;color:#92400e;border-radius:6px;padding:9px 12px;font-size:12px;margin:0 auto 4px;max-width:420px;text-align:left;">
        <i class="fas fa-clock"></i>
        Appointment time is approximate — the doctor may run a little behind if a patient needs extra care.
    </div>

    <div style="margin-top:20px;display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
        <button class="btn btn-primary" onclick="resetForm()"><i class="fas fa-plus"></i> New Booking</button>
        <a href="/queue" class="btn btn-secondary"><i class="fas fa-list"></i> View Queue</a>
        <a id="patientLink" href="#" class="btn btn-secondary" style="display:none;">
            <i class="fas fa-user"></i> View Patient
        </a>
    </div>
</div>

</div>
</div>
</div>

<script>
// ── Patient search ────────────────────────────────────────────────────────────
// The search UI is absent in New Patient mode — guard so this script still runs.
let searchTimeout;
const searchInputEl = document.getElementById('searchInput');
if (searchInputEl) {
    searchInputEl.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const q = this.value.trim();
        if (q.length < 2) { hideResults(); return; }
        searchTimeout = setTimeout(() => searchPatients(q), 300);
    });
}

function searchPatients(q) {
    fetch('/api/patient/search?q=' + encodeURIComponent(q))
    .then(r => r.json())
    .then(data => {
        const el = document.getElementById('patientResults');
        if (!data.success || !data.data.length) { el.style.display='none'; return; }
        el.innerHTML = data.data.slice(0,8).map(p => {
            const name = ((p.fname||'') + ' ' + (p.lname||'')).trim() || 'Unknown';
            const pcode = (p.patient_id || p.id || '').toString().replace(/'/g,"\\'");
            return `<div class="search-result-item" onclick="selectPatient(${p.id},'${name.replace(/'/g,"\\'")}','${(p.contact_no||'').replace(/'/g,"\\'")}','${pcode}')">
                <strong>${name}</strong> <span style="color:#6b7280;">${p.contact_no||''}</span>
                <span style="float:right;color:#9ca3af;">ID: ${p.patient_id||p.id}</span>
            </div>`;
        }).join('');
        el.style.display = 'block';
    });
}

function selectPatient(id, name, phone, pcode) {
    document.getElementById('patientId').value = id;
    document.getElementById('patientNameInput').value = name;
    document.getElementById('patientPhoneInput').value = phone;
    const codeEl = document.getElementById('patientCodeInput');
    if (codeEl) codeEl.value = pcode || '';
    document.getElementById('selectedPatient').innerHTML = `<i class="fas fa-user-check" style="color:#16a34a;"></i> <strong>${name}</strong> &nbsp; ${phone}${pcode ? ' &nbsp; <span style="color:#6b7280;">ID: '+pcode+'</span>' : ''}`;
    document.getElementById('selectedPatient').style.display = 'block';
    document.getElementById('searchInput').value = name;
    hideResults();
}

function clearPatient() {
    const set = (id, v) => { const el = document.getElementById(id); if (el) el.value = v; };
    const show = (id, d) => { const el = document.getElementById(id); if (el) el.style.display = d; };
    const op = (id, v) => { const el = document.getElementById(id); if (el) el.style.opacity = v; };
    set('patientId', '');
    set('patientNameInput', '');
    set('patientPhoneInput', '');
    set('patientCodeInput', '');
    show('selectedPatient', 'none');
    set('searchInput', '');
    hideResults();
}

function hideResults() {
    document.getElementById('patientResults').style.display = 'none';
}
document.addEventListener('click', e => {
    if (!e.target.closest('#patientResults') && !e.target.closest('#searchInput')) hideResults();
});

// ── Slot picker ───────────────────────────────────────────────────────────────
function pad(n) { return String(n).padStart(2,'0'); }
function to12(t) {
    const [h,m] = t.split(':').map(Number);
    return (h%12||12) + ':' + pad(m) + ' ' + (h<12?'AM':'PM');
}

// Load slots on page load for today
loadSlots(document.getElementById('apptDate').value);

// Reload slots when date changes
document.getElementById('apptDate').addEventListener('change', function() {
    document.getElementById('slotTimeInput').value = '';
    loadSlots(this.value);
});

function reloadSlots() {
    document.getElementById('slotTimeInput').value = '';
    loadSlots(document.getElementById('apptDate').value);
}

function loadSlots(date) {
    const area = document.getElementById('slotArea');
    area.innerHTML = '<div id="slotLoading"><i class="fas fa-spinner fa-spin"></i> Loading slots…</div>';
    document.getElementById('slotTimeInput').value = '';

    const extended = document.getElementById('extendedHours').checked ? '1' : '0';
    fetch('/api/slots?date=' + encodeURIComponent(date) + '&extended=' + extended)
    .then(r => r.json())
    .then(data => renderSlots(data, date))
    .catch(() => {
        area.innerHTML = '<div style="color:#9ca3af;font-size:12px;">Could not load slots.</div>';
    });
}

function getNowIST() {
    const now = new Date();
    const ist = new Date(now.getTime() + now.getTimezoneOffset()*60000 + 5.5*3600000);
    return String(ist.getHours()).padStart(2,'0') + ':' + String(ist.getMinutes()).padStart(2,'0');
}
function getTodayIST() {
    const now = new Date();
    const ist = new Date(now.getTime() + now.getTimezoneOffset()*60000 + 5.5*3600000);
    return ist.getFullYear()+'-'+String(ist.getMonth()+1).padStart(2,'0')+'-'+String(ist.getDate()).padStart(2,'0');
}

function renderSlots(data, date) {
    const area = document.getElementById('slotArea');

    if (data.closed) {
        area.innerHTML = '<div style="color:#ef4444;font-size:12px;"><i class="fas fa-ban"></i> Clinic is closed on this date.</div>';
        return;
    }

    const isToday = (date === getTodayIST());
    const nowTime = getNowIST();

    // Filter: hide past slots (if today); keep full slots visible but disabled
    const slots = (data.slots || []).filter(s => {
        if (isToday && s.time <= nowTime) return false; // past — hide
        return true; // show available AND full slots (full = greyed, not selectable)
    });

    if (!slots.length) {
        area.innerHTML = '<div style="color:#9ca3af;font-size:12px;">No slots configured for this date.</div>';
        return;
    }

    const morning = slots.filter(s => s.time < '13:00');
    const evening = slots.filter(s => s.time >= '13:00');
    let html = '<div class="slot-section">';

    if (morning.length) {
        html += `<div class="slot-session-lbl"><i class="fas fa-sun" style="color:#f59e0b;"></i> Morning</div><div class="slot-grid">`;
        morning.forEach(s => { html += slotPill(s); });
        html += '</div>';
    }
    if (evening.length) {
        html += `<div class="slot-session-lbl"><i class="fas fa-moon" style="color:#6366f1;"></i> Evening</div><div class="slot-grid">`;
        evening.forEach(s => { html += slotPill(s); });
        html += '</div>';
    }
    html += '</div>';
    area.innerHTML = html;
}

function slotPill(s) {
    if (!s.available) {
        // Full — show greyed, not clickable
        return `<div class="slot-pill full" title="Booked: ${s.booked}/${s.max}">${to12(s.time)}<br><span style="font-size:9px;font-weight:400;">Full</span></div>`;
    }
    return `<div class="slot-pill" data-time="${s.time}" onclick="selectSlot(this,'${s.time}')">${to12(s.time)}${s.booked>0 ? '<br><span style="font-size:9px;font-weight:400;color:#f59e0b;">'+s.booked+'/'+s.max+' booked</span>' : ''}</div>`;
}

function selectSlot(el, time) {
    document.querySelectorAll('.slot-pill').forEach(p => p.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('slotTimeInput').value = time;
}

// ── Form submit ───────────────────────────────────────────────────────────────
document.getElementById('walkinForm').addEventListener('submit', function(e) {
    e.preventDefault();
    // Search-only mode: a registered patient must be selected — no manual new patient here.
    if (this.dataset.newMode !== '1' && !document.getElementById('patientId').value) {
        showMsg('Please search and select a patient before booking.', 'danger');
        return;
    }
    const fd = new FormData(this);
    fetch('/api/appointment/walkin', { method:'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('walkinForm').style.display = 'none';
            const shownName = fd.get('patient_name')
                || ((fd.get('fname') || '') + ' ' + (fd.get('lname') || '')).trim()
                || 'Patient';
            document.getElementById('tokenName').textContent = shownName;
            const slot = fd.get('slot_time');
            document.getElementById('tokenSlot').textContent = slot ? 'Slot: ' + to12(slot) : 'Walk-in (no slot)';
            // Show patient link if auto-created
            if (data.patient_id) {
                const linkEl = document.getElementById('patientLink');
                linkEl.href = '/patient/' + data.patient_id;
                linkEl.style.display = 'inline-block';
            }
            document.getElementById('tokenDisplay').style.display = 'block';
        } else {
            showMsg(data.message || 'Error', 'danger');
        }
    });
});

function showMsg(msg, type) {
    const el = document.getElementById('formMsg');
    el.className = 'alert alert-' + type;
    el.textContent = msg;
    el.style.display = 'block';
}

function resetForm() {
    document.getElementById('walkinForm').reset();
    document.getElementById('walkinForm').style.display = 'block';
    document.getElementById('tokenDisplay').style.display = 'none';
    document.getElementById('formMsg').style.display = 'none';
    clearPatient();
    loadSlots(document.getElementById('apptDate').value);
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
