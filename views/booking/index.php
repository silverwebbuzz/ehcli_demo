<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment – Dr. Feelgood's</title>
    <link rel="icon" type="image/png" href="/assets/logo/favicon-32.png">
    <link rel="apple-touch-icon" href="/assets/logo/apple-touch-icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { box-sizing:border-box; }
        body { background:#f0f4f8; font-family:'Segoe UI',system-ui,sans-serif; font-size:13px; margin:0; padding:16px 0 40px; }

        .booking-card { max-width:520px; margin:0 auto; background:#fff; border-radius:14px; box-shadow:0 4px 24px rgba(0,0,0,.10); overflow:hidden; }
        .booking-header { background:linear-gradient(135deg,#2563eb,#1d4ed8); color:#fff; padding:18px 22px 16px; }
        .booking-header h1 { font-size:18px; font-weight:700; margin:0 0 2px; }
        .booking-header p  { margin:0; opacity:.8; font-size:12px; }
        .booking-body { padding:18px 22px 22px; }

        /* ── Progress ── */
        .prog { display:flex; align-items:flex-start; margin-bottom:20px; }
        .prog-step { flex:1; display:flex; flex-direction:column; align-items:center; position:relative; }
        .prog-step:not(:last-child)::after {
            content:''; position:absolute; top:11px; left:calc(50% + 12px);
            right:calc(-50% + 12px); height:2px; background:#e5e7eb;
        }
        .prog-step.done::after  { background:#2563eb; }
        .prog-circle { width:22px; height:22px; border-radius:50%; border:2px solid #e5e7eb; background:#fff;
                       display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700;
                       color:#9ca3af; position:relative; z-index:1; }
        .prog-step.done   .prog-circle { background:#2563eb; border-color:#2563eb; color:#fff; }
        .prog-step.active .prog-circle { border-color:#2563eb; color:#2563eb; }
        .prog-label { font-size:10px; color:#9ca3af; margin-top:3px; text-align:center; }
        .prog-step.done .prog-label,
        .prog-step.active .prog-label { color:#2563eb; font-weight:600; }

        /* ── Steps ── */
        .step { display:none; }
        .step.active { display:block; }
        .step-title { font-size:14px; font-weight:700; margin:0 0 12px; color:#111; }

        /* ── Date strip ── */
        .date-strip { display:flex; gap:7px; overflow-x:auto; padding-bottom:4px;
                      scroll-snap-type:x mandatory; -webkit-overflow-scrolling:touch; margin-bottom:16px; }
        .date-strip::-webkit-scrollbar { height:3px; }
        .date-strip::-webkit-scrollbar-thumb { background:#d1d5db; border-radius:2px; }
        .dc { min-width:56px; border:2px solid #e5e7eb; border-radius:10px; padding:7px 3px;
              text-align:center; cursor:pointer; scroll-snap-align:start; transition:.15s; flex-shrink:0; }
        .dc:hover { border-color:#93c5fd; background:#eff6ff; }
        .dc.sel { border-color:#2563eb; background:#eff6ff; }
        .dc .d-day { font-size:9px; color:#6b7280; text-transform:uppercase; font-weight:700; letter-spacing:.3px; }
        .dc .d-num { font-size:20px; font-weight:800; color:#111; line-height:1.1; }
        .dc .d-mon { font-size:9px; color:#6b7280; }
        .dc.sel .d-day, .dc.sel .d-num, .dc.sel .d-mon { color:#1d4ed8; }
        .dc.closed-day { opacity:.45; cursor:not-allowed; border-color:#f3f4f6; background:#f9fafb; }
        .d-closed { font-size:8px; color:#ef4444; font-weight:700; text-transform:uppercase; margin-top:1px; }

        /* ── Slot area ── */
        .slot-area { min-height:60px; }
        .slot-loading { text-align:center; padding:20px 0; color:#9ca3af; font-size:12px; }
        .slot-empty   { text-align:center; padding:20px 0; color:#9ca3af; font-size:12px; }
        .session-lbl { font-size:10px; font-weight:700; color:#9ca3af; text-transform:uppercase;
                       letter-spacing:.5px; margin:10px 0 6px; display:flex; align-items:center; gap:5px; }
        .slot-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:7px; }
        .sb { padding:8px 4px; border:2px solid #e5e7eb; border-radius:8px; text-align:center;
              cursor:pointer; font-size:11px; font-weight:600; transition:.15s; background:#fff; color:#374151; }
        .sb:hover { border-color:#93c5fd; background:#eff6ff; }
        .sb.sel { border-color:#2563eb; background:#2563eb; color:#fff; }

        /* ── Forms ── */
        .form-label { font-size:12px; font-weight:600; color:#374151; margin-bottom:3px; display:block; }
        .form-control { font-size:13px; padding:8px 11px; border:2px solid #e5e7eb; border-radius:8px;
                        width:100%; outline:none; transition:.15s; }
        .form-control:focus { border-color:#2563eb; }
        .mb-3 { margin-bottom:11px; }
        .found-box { background:#f0fdf4; border:2px solid #bbf7d0; border-radius:8px;
                     padding:9px 12px; font-size:12px; margin-bottom:10px; color:#15803d; }

        /* ── Buttons ── */
        .btn-main { background:#2563eb; border:none; border-radius:8px; padding:11px 20px;
                    font-size:13px; font-weight:700; color:#fff; cursor:pointer; width:100%; transition:.15s; }
        .btn-main:hover { background:#1d4ed8; }
        .btn-back { background:#f3f4f6; border:none; border-radius:8px; padding:11px 16px;
                    font-size:13px; color:#374151; cursor:pointer; flex-shrink:0; }
        .btn-row { display:flex; gap:8px; margin-top:14px; }
        .btn-row .btn-main { flex:1; }
        .err-msg { color:#dc2626; font-size:12px; margin-top:6px; display:none; }

        /* ── Confirmation ── */
        .conf-icon  { text-align:center; font-size:50px; color:#16a34a; margin-bottom:6px; }
        .conf-table { background:#f9fafb; border-radius:8px; overflow:hidden; margin-bottom:12px; }
        .conf-row   { display:flex; justify-content:space-between; padding:8px 14px; border-bottom:1px solid #f3f4f6; font-size:12px; }
        .conf-row:last-child { border:none; }
        .conf-row span { color:#6b7280; }
        .notice { background:#fef3c7; border-radius:6px; padding:9px 12px; font-size:11px; color:#92400e; margin-bottom:14px; }

        /* phone find btn */
        .find-btn { background:#2563eb; color:#fff; border:none; border-radius:8px;
                    padding:0 14px; font-size:12px; cursor:pointer; white-space:nowrap; flex-shrink:0; }
    </style>
</head>
<body>
<div class="booking-card">
    <div class="booking-header">
        <h1><i class="fas fa-calendar-plus"></i> Book Appointment</h1>
        <p>Dr. Feelgood`s Clinic &mdash; Online Booking</p>
    </div>
    <div class="booking-body">

        <!-- Progress -->
        <div class="prog">
            <div class="prog-step active" id="ps1">
                <div class="prog-circle">1</div>
                <div class="prog-label">Date &amp; Slot</div>
            </div>
            <div class="prog-step" id="ps2">
                <div class="prog-circle">2</div>
                <div class="prog-label">Your Details</div>
            </div>
            <div class="prog-step" id="ps3">
                <div class="prog-circle">3</div>
                <div class="prog-label">Confirmed</div>
            </div>
        </div>

        <!-- ── STEP 1: Date + Slot ── -->
        <div class="step active" id="step1">
            <div class="step-title">Select Date &amp; Time Slot</div>

            <!-- Date strip -->
            <div class="date-strip" id="dateStrip"></div>

            <!-- Slots load here -->
            <div class="slot-area" id="slotArea">
                <div class="slot-loading" id="slotLoading">
                    <i class="fas fa-spinner fa-spin"></i> Loading slots…
                </div>
            </div>

            <div class="err-msg" id="err1"></div>
            <div class="btn-row">
                <button class="btn-main" onclick="step1Next()">Next &rarr;</button>
            </div>
        </div>

        <!-- ── STEP 2: Phone + Details ── -->
        <div class="step" id="step2">
            <div class="step-title">Your Details</div>

            <div class="mb-3">
                <label class="form-label">Mobile Number <span style="color:#dc2626">*</span></label>
                <div style="display:flex;gap:7px;">
                    <input type="tel" id="phoneInput" class="form-control" placeholder="10-digit number" maxlength="15" style="flex:1;">
                    <button class="find-btn" onclick="lookupPhone()"><i class="fas fa-search"></i> Find</button>
                </div>
                <div style="font-size:11px;color:#9ca3af;margin-top:3px;">We'll check if you're already registered.</div>
            </div>

            <div class="found-box" id="foundBox" style="display:none;">
                <i class="fas fa-user-check"></i> Found: <strong id="foundName"></strong>
                <div style="font-size:11px;margin-top:2px;">Your details are pre-filled below.</div>
            </div>
            <input type="hidden" id="hiddenPid">

            <div class="mb-3" id="nameField">
                <label class="form-label">Full Name <span style="color:#dc2626">*</span></label>
                <input type="text" id="patientName" class="form-control" placeholder="First Last">
            </div>

            <div class="mb-3">
                <label class="form-label">Reason for Visit <span style="font-weight:400;color:#9ca3af;">(optional)</span></label>
                <input type="text" id="chiefComplaint" class="form-control" placeholder="e.g. Fever, Back pain">
            </div>

            <div class="mb-3">
                <label class="form-label">Visit Type</label>
                <select id="isFollowup" class="form-control">
                    <option value="0">New Patient / New Case</option>
                    <option value="1">Follow-up</option>
                </select>
            </div>

            <div class="err-msg" id="err2"></div>
            <div class="btn-row">
                <button class="btn-back" onclick="goStep(1)">← Back</button>
                <button class="btn-main" onclick="step2Next()">Confirm Booking &rarr;</button>
            </div>
        </div>

        <!-- ── STEP 3: Confirmation ── -->
        <div class="step" id="step3">
            <div class="conf-icon"><i class="fas fa-check-circle"></i></div>
            <div class="conf-table">
                <div class="conf-row"><span>Patient</span><strong id="confName"></strong></div>
                <div class="conf-row"><span>Date</span><strong id="confDate"></strong></div>
                <div class="conf-row"><span>Time Slot</span><strong id="confTime"></strong></div>
                <div class="conf-row"><span>Appointment ID</span><strong id="confId"></strong></div>
            </div>
            <div class="notice">
                <i class="fas fa-info-circle"></i>
                Please arrive 10 minutes before your slot and check in at reception.
            </div>
            <div class="notice">
                <i class="fas fa-clock"></i>
                Please note: your appointment time is approximate. Since some patients may need extra care, the doctor may run a little behind schedule. We appreciate your patience and understanding.
            </div>
            <button class="btn-main" onclick="location.reload()">Book Another Appointment</button>
        </div>

    </div>
</div>

<script>
// ── IST helpers ───────────────────────────────────────────────────────────────
function getIST() {
    const now = new Date();
    return new Date(now.getTime() + now.getTimezoneOffset() * 60000 + 5.5 * 3600000);
}
function todayIST() {
    const d = getIST();
    return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate());
}
function nowTimeIST() {
    const d = getIST();
    return pad(d.getHours()) + ':' + pad(d.getMinutes());
}
function pad(n) { return String(n).padStart(2,'0'); }
function to12(t) {
    const [h,m] = t.split(':').map(Number);
    return (h%12||12) + ':' + pad(m) + ' ' + (h<12?'AM':'PM');
}
function fmtLong(ymd) {
    const [y,mo,d] = ymd.split('-').map(Number);
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const days   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    const dt = new Date(y, mo-1, d);
    return days[dt.getDay()] + ', ' + d + ' ' + months[mo-1] + ' ' + y;
}

// ── State ────────────────────────────────────────────────────────────────────
const S = { date:'', slot:'', phone:'', pid:'', name:'', complaint:'', followup:'0' };

// ── Progress ─────────────────────────────────────────────────────────────────
function goStep(n) {
    document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
    document.getElementById('step'+n).classList.add('active');
    [1,2,3].forEach(i => {
        const ps = document.getElementById('ps'+i);
        ps.classList.toggle('done',   i < n);
        ps.classList.toggle('active', i === n);
    });
    window.scrollTo(0,0);
}

// ── STEP 1: Build date strip + auto-load today's slots ────────────────────────
const DAY_NAMES   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
const MON_NAMES   = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const DAYS_AHEAD  = <?php echo (int)($bookingDaysAhead ?? 15); ?>;
const CLOSED_DATES = <?php echo json_encode($unavailableDates ?? []); ?>; // closed + no-session days

document.addEventListener('DOMContentLoaded', function() {
    const strip = document.getElementById('dateStrip');
    let firstSelectable = null;

    for (let i = 0; i < DAYS_AHEAD; i++) {
        const ist = getIST();
        ist.setDate(ist.getDate() + i);
        const ymd = ist.getFullYear() + '-' + pad(ist.getMonth()+1) + '-' + pad(ist.getDate());
        const isClosed = CLOSED_DATES.indexOf(ymd) !== -1;

        const card = document.createElement('div');
        card.className   = 'dc' + (isClosed ? ' closed-day' : '');
        card.dataset.ymd = ymd;
        card.innerHTML   = `<div class="d-day">${DAY_NAMES[ist.getDay()]}</div>
                            <div class="d-num">${ist.getDate()}</div>
                            <div class="d-mon">${MON_NAMES[ist.getMonth()]}</div>
                            ${isClosed ? '<div class="d-closed">Closed</div>' : ''}`;
        if (!isClosed) {
            card.addEventListener('click', function() { selectDate(card, ymd); });
            if (!firstSelectable) firstSelectable = { card, ymd };
        }
        strip.appendChild(card);
    }

    // Auto-select first available date and load its slots
    if (firstSelectable) {
        firstSelectable.card.classList.add('sel');
        S.date = firstSelectable.ymd;
        loadSlots(firstSelectable.ymd);
    } else {
        document.getElementById('slotArea').innerHTML =
            '<div class="slot-empty">No available dates in the booking window.</div>';
    }
});

function selectDate(card, ymd) {
    document.querySelectorAll('.dc').forEach(c => c.classList.remove('sel'));
    card.classList.add('sel');
    S.date = ymd;
    S.slot = ''; // reset slot selection
    loadSlots(ymd);
}

// ── Slot loader ───────────────────────────────────────────────────────────────
var _slotReqDate = null; // track which date we last requested
function loadSlots(date) {
    _slotReqDate = date;
    const area = document.getElementById('slotArea');
    area.innerHTML = '<div class="slot-loading"><i class="fas fa-spinner fa-spin"></i> Loading slots…</div>';

    fetch('/api/slots?date=' + encodeURIComponent(date))
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (_slotReqDate !== date) return; // stale response, ignore
        renderSlots(data, date);
    })
    .catch(function() {
        area.innerHTML = '<div class="slot-empty">Could not load slots. Please try again.</div>';
    });
}

function renderSlots(data, date) {
    const area    = document.getElementById('slotArea');
    const isToday = (date === todayIST());
    const nowTime = nowTimeIST();

    if (data.closed) {
        area.innerHTML = '<div class="slot-empty"><i class="fas fa-ban" style="color:#ef4444;"></i> Clinic is closed on this date.</div>';
        return;
    }
    if (!data.success || !data.slots.length) {
        area.innerHTML = '<div class="slot-empty"><i class="fas fa-calendar-times"></i> No slots available for this date.</div>';
        return;
    }

    // Filter: hide fully booked & past slots
    const slots = data.slots.filter(s => {
        if (!s.available) return false;
        if (isToday && s.time <= nowTime) return false;
        return true;
    });

    if (!slots.length) {
        area.innerHTML = '<div class="slot-empty"><i class="fas fa-calendar-times"></i> No available slots for this date.</div>';
        return;
    }

    const morning = slots.filter(s => s.time < '13:00');
    const evening = slots.filter(s => s.time >= '13:00');
    let html = '';

    if (morning.length) {
        html += `<div class="session-lbl"><i class="fas fa-sun" style="color:#f59e0b;"></i> Morning</div>
                 <div class="slot-grid">`;
        morning.forEach(s => {
            html += `<div class="sb" data-time="${s.time}" onclick="selectSlot(this,'${s.time}')">${to12(s.time)}</div>`;
        });
        html += '</div>';
    }
    if (evening.length) {
        html += `<div class="session-lbl"><i class="fas fa-moon" style="color:#6366f1;"></i> Evening</div>
                 <div class="slot-grid">`;
        evening.forEach(s => {
            html += `<div class="sb" data-time="${s.time}" onclick="selectSlot(this,'${s.time}')">${to12(s.time)}</div>`;
        });
        html += '</div>';
    }
    area.innerHTML = html;
}

function selectSlot(el, time) {
    document.querySelectorAll('.sb').forEach(b => b.classList.remove('sel'));
    el.classList.add('sel');
    S.slot = time;
}

function step1Next() {
    const err = document.getElementById('err1');
    if (!S.date) { showErr('err1','Please select a date.'); return; }
    if (!S.slot) { showErr('err1','Please select a time slot.'); return; }
    err.style.display = 'none';
    goStep(2);
}

// ── STEP 2: Phone lookup + details ────────────────────────────────────────────
document.getElementById('phoneInput').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); lookupPhone(); }
});

function lookupPhone() {
    const phone = document.getElementById('phoneInput').value.trim();
    if (phone.length < 8) { showErr('err2','Enter a valid phone number first.'); return; }
    document.getElementById('err2').style.display = 'none';
    S.phone = phone;

    fetch('/api/patient/lookup?phone=' + encodeURIComponent(phone))
    .then(r => r.json())
    .then(data => {
        if (data.success && data.found) {
            const p    = data.patient;
            const name = ((p.fname||'') + ' ' + (p.lname||'')).trim();
            S.pid  = p.id;
            S.name = name;
            document.getElementById('hiddenPid').value     = p.id;
            document.getElementById('patientName').value   = name;
            document.getElementById('foundName').textContent = name;
            document.getElementById('foundBox').style.display = 'block';
            document.getElementById('nameField').style.opacity = '0.55';
        } else {
            S.pid = '';
            document.getElementById('hiddenPid').value      = '';
            document.getElementById('foundBox').style.display = 'none';
            document.getElementById('nameField').style.opacity = '1';
        }
    });
}

function step2Next() {
    const phone = document.getElementById('phoneInput').value.trim();
    const name  = document.getElementById('patientName').value.trim();
    if (phone.length < 8) { showErr('err2','Please enter a valid mobile number.'); return; }
    if (!name)             { showErr('err2','Please enter the patient name.'); return; }
    document.getElementById('err2').style.display = 'none';

    S.phone     = phone;
    S.name      = name;
    S.pid       = document.getElementById('hiddenPid').value;
    S.complaint = document.getElementById('chiefComplaint').value.trim();
    S.followup  = document.getElementById('isFollowup').value;

    const body = new URLSearchParams({
        appt_date:       S.date,
        slot_time:       S.slot,
        patient_id:      S.pid,
        patient_name:    S.name,
        patient_phone:   S.phone,
        chief_complaint: S.complaint,
        is_followup:     S.followup,
    });

    fetch('/api/booking', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('confName').textContent  = S.name;
            document.getElementById('confDate').textContent  = fmtLong(data.appt_date);
            document.getElementById('confTime').textContent  = to12(data.slot_time);
            document.getElementById('confId').textContent    = '#' + data.id;
            goStep(3);
        } else {
            showErr('err2', data.message || 'Booking failed. Please try again.');
        }
    })
    .catch(() => showErr('err2','Network error. Please try again.'));
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function showErr(id, msg) {
    const el = document.getElementById(id);
    el.textContent    = msg;
    el.style.display  = 'block';
}
</script>
</body>
</html>
