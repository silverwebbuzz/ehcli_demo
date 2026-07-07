<?php
$page_title = 'Clinic Settings';
$s = $clinicSettings ?? [];
ob_start();
?>
<style>
.settings-section { background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:14px 16px; margin-bottom:12px; }
.settings-section h3 { font-size:13px; font-weight:700; margin:0 0 12px; padding-bottom:8px; border-bottom:1px solid #f3f4f6; color:#374151; }
.session-grid { display:grid; grid-template-columns:auto 1fr 1fr; gap:8px; align-items:center; }
.session-grid label { font-size:12px; color:#6b7280; }
.settings-2col { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
@media(max-width:600px){
    .settings-2col { grid-template-columns:1fr; }
    .session-grid { grid-template-columns:auto 1fr; }
}
</style>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
    <h1 class="page-title" style="margin:0;">Clinic Settings</h1>
</div>

<div id="alertMsg" style="display:none;padding:8px 14px;border-radius:6px;font-size:12px;margin-bottom:12px;"></div>

<form id="settingsForm">

<div class="settings-section">
    <h3><i class="fas fa-hospital" style="color:var(--primary);margin-right:6px;"></i> Clinic Info</h3>
    <div class="settings-2col">
        <div>
            <label class="form-label">Clinic Name</label>
            <input type="text" name="clinic_name" class="form-control" value="<?php echo htmlspecialchars($s['clinic_name'] ?? 'Dr. Feelgood'); ?>">
        </div>
        <div>
            <label class="form-label">Phone</label>
            <input type="text" name="clinic_phone" class="form-control" value="<?php echo htmlspecialchars($s['clinic_phone'] ?? ''); ?>">
        </div>
        <div>
            <label class="form-label">Consultation Fee (₹)</label>
            <input type="number" name="consultation_fee" class="form-control" value="<?php echo htmlspecialchars($s['consultation_fee'] ?? ''); ?>">
        </div>
    </div>
</div>

<div class="settings-section">
    <h3><i class="fas fa-clock" style="color:var(--primary);margin-right:6px;"></i> Slot Duration</h3>
    <div style="display:flex;gap:16px;">
        <label style="font-size:12px;display:flex;align-items:center;gap:6px;">
            <input type="radio" name="slot_duration_min" value="15" <?php echo ($s['slot_duration_min'] ?? '30') === '15' ? 'checked' : ''; ?>>
            15 minutes
        </label>
        <label style="font-size:12px;display:flex;align-items:center;gap:6px;">
            <input type="radio" name="slot_duration_min" value="30" <?php echo ($s['slot_duration_min'] ?? '30') !== '15' ? 'checked' : ''; ?>>
            30 minutes
        </label>
    </div>
    <div style="font-size:11px;color:#9ca3af;margin-top:6px;">Max patients per slot:</div>
    <div style="margin-top:6px;">
        <input type="number" name="max_per_slot" class="form-control" style="width:100px;" value="<?php echo htmlspecialchars($s['max_per_slot'] ?? '1'); ?>" min="1" max="10">
    </div>
</div>

<div class="settings-section">
    <h3><i class="fas fa-sun" style="color:var(--primary);margin-right:6px;"></i> Monday – Saturday Sessions</h3>

    <div style="margin-bottom:12px;">
        <label style="font-size:12px;display:flex;align-items:center;gap:8px;margin-bottom:8px;">
            <input type="checkbox" name="mon_sat_morning_on" value="1" id="morningOn" <?php echo ($s['mon_sat_morning_on'] ?? '1') === '1' ? 'checked' : ''; ?>>
            <strong>Morning Session</strong>
        </label>
        <div class="settings-2col" style="padding-left:20px;">
            <div>
                <label class="form-label">Start</label>
                <input type="time" name="mon_sat_morning_start" class="form-control" value="<?php echo htmlspecialchars($s['mon_sat_morning_start'] ?? '09:30'); ?>">
            </div>
            <div>
                <label class="form-label">End</label>
                <input type="time" name="mon_sat_morning_end" class="form-control" value="<?php echo htmlspecialchars($s['mon_sat_morning_end'] ?? '13:30'); ?>">
            </div>
        </div>
    </div>

    <div>
        <label style="font-size:12px;display:flex;align-items:center;gap:8px;margin-bottom:8px;">
            <input type="checkbox" name="mon_sat_evening_on" value="1" id="eveningOn" <?php echo ($s['mon_sat_evening_on'] ?? '1') === '1' ? 'checked' : ''; ?>>
            <strong>Evening Session</strong>
        </label>
        <div class="settings-2col" style="padding-left:20px;">
            <div>
                <label class="form-label">Start</label>
                <input type="time" name="mon_sat_evening_start" class="form-control" value="<?php echo htmlspecialchars($s['mon_sat_evening_start'] ?? '16:30'); ?>">
            </div>
            <div>
                <label class="form-label">End</label>
                <input type="time" name="mon_sat_evening_end" class="form-control" value="<?php echo htmlspecialchars($s['mon_sat_evening_end'] ?? '20:30'); ?>">
            </div>
        </div>
    </div>
</div>

<div class="settings-section">
    <h3><i class="fas fa-calendar-day" style="color:var(--primary);margin-right:6px;"></i> Sunday</h3>
    <label style="font-size:12px;display:flex;align-items:center;gap:8px;margin-bottom:8px;">
        <input type="checkbox" name="sunday_on" value="1" <?php echo ($s['sunday_on'] ?? '1') === '1' ? 'checked' : ''; ?>>
        <strong>Open on Sunday</strong>
    </label>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;padding-left:20px;">
        <div>
            <label class="form-label">Start</label>
            <input type="time" name="sunday_start" class="form-control" value="<?php echo htmlspecialchars($s['sunday_start'] ?? '10:00'); ?>">
        </div>
        <div>
            <label class="form-label">End</label>
            <input type="time" name="sunday_end" class="form-control" value="<?php echo htmlspecialchars($s['sunday_end'] ?? '12:00'); ?>">
        </div>
    </div>
</div>

<div class="settings-section">
    <h3><i class="fas fa-clock" style="color:#ef4444;margin-right:6px;"></i> Extended Hours (Walk-in Admin Only)</h3>
    <div style="font-size:12px;color:#6b7280;margin-bottom:10px;">
        These extended end times are only visible in the admin walk-in form — never on the public booking page.
        Useful when doctor allows last-minute patients beyond normal hours.
    </div>
    <div class="settings-2col">
        <div>
            <label class="form-label">Morning Extended End <span style="color:#9ca3af;font-weight:400;">(after normal morning end)</span></label>
            <input type="time" name="extended_morning_end" class="form-control" value="<?php echo htmlspecialchars($s['extended_morning_end'] ?? '14:30'); ?>">
        </div>
        <div>
            <label class="form-label">Evening Extended End <span style="color:#9ca3af;font-weight:400;">(after normal evening end)</span></label>
            <input type="time" name="extended_evening_end" class="form-control" value="<?php echo htmlspecialchars($s['extended_evening_end'] ?? '23:30'); ?>">
        </div>
    </div>
</div>

<div class="settings-section">
    <h3><i class="fas fa-calendar-alt" style="color:var(--primary);margin-right:6px;"></i> Online Booking Window</h3>
    <div style="font-size:12px;color:#6b7280;margin-bottom:8px;">How many days ahead can patients book online?</div>
    <div style="display:flex;gap:12px;">
        <?php foreach ([7,15,30] as $d): ?>
        <label style="font-size:12px;display:flex;align-items:center;gap:6px;">
            <input type="radio" name="booking_days_ahead" value="<?php echo $d; ?>"
                <?php echo ((int)($s['booking_days_ahead'] ?? 15)) === $d ? 'checked' : ''; ?>>
            <?php echo $d; ?> days
        </label>
        <?php endforeach; ?>
    </div>
</div>

<div class="settings-section">
    <h3><i class="fas fa-file-invoice" style="color:var(--primary);margin-right:6px;"></i> Invoice / Billing Settings</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
        <div>
            <label class="form-label">Doctor Name <span style="color:#9ca3af;font-weight:400;">(appears on invoice)</span></label>
            <input type="text" name="inv_doctor_name" class="form-control" value="<?php echo htmlspecialchars($s['inv_doctor_name'] ?? ''); ?>" placeholder="Dr. Full Name">
        </div>
        <div>
            <label class="form-label">Qualification <span style="color:#9ca3af;font-weight:400;">(e.g. MBBS, MD)</span></label>
            <input type="text" name="inv_qualification" class="form-control" value="<?php echo htmlspecialchars($s['inv_qualification'] ?? ''); ?>" placeholder="MBBS, MD">
        </div>
        <div>
            <label class="form-label">Clinic Phone</label>
            <input type="text" name="inv_phone" class="form-control" value="<?php echo htmlspecialchars($s['inv_phone'] ?? $s['clinic_phone'] ?? ''); ?>" placeholder="+91 ...">
        </div>
        <div>
            <label class="form-label">Clinic Email <span style="color:#9ca3af;font-weight:400;">(optional)</span></label>
            <input type="text" name="inv_email" class="form-control" value="<?php echo htmlspecialchars($s['inv_email'] ?? ''); ?>" placeholder="clinic@example.com">
        </div>
    </div>
    <div style="margin-bottom:10px;">
        <label class="form-label">Clinic Address <span style="color:#9ca3af;font-weight:400;">(full address for invoice header)</span></label>
        <textarea name="inv_address" class="form-control" rows="2" placeholder="Street, City, State – PIN"><?php echo htmlspecialchars($s['inv_address'] ?? ''); ?></textarea>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
        <div>
            <label class="form-label" style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" name="inv_show_pan" value="1" <?php echo ($s['inv_show_pan'] ?? '0') === '1' ? 'checked' : ''; ?>>
                Show PAN / Tax Number on Invoice
            </label>
            <input type="text" name="inv_pan" class="form-control" style="margin-top:6px;"
                value="<?php echo htmlspecialchars($s['inv_pan'] ?? ''); ?>" placeholder="ABCDE1234F">
        </div>
        <div>
            <label class="form-label" style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" name="inv_gst_enabled" value="1" id="gstToggle" <?php echo ($s['inv_gst_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>>
                Enable GST on Invoice
            </label>
            <div style="display:grid;grid-template-columns:1fr 80px;gap:8px;margin-top:6px;">
                <input type="text" name="inv_gst_number" class="form-control"
                    value="<?php echo htmlspecialchars($s['inv_gst_number'] ?? ''); ?>" placeholder="GST Number">
                <input type="number" name="inv_gst_rate" class="form-control"
                    value="<?php echo htmlspecialchars($s['inv_gst_rate'] ?? '18'); ?>" placeholder="%" min="0" max="100">
            </div>
        </div>
    </div>
</div>

<div class="settings-section">
    <h3><i class="fas fa-ban" style="color:var(--primary);margin-right:6px;"></i> Closed / Holiday Dates</h3>
    <div style="font-size:12px;color:#6b7280;margin-bottom:10px;">
        Dates marked here will show no slots on the booking page.
    </div>
    <div style="display:flex;gap:8px;margin-bottom:10px;">
        <input type="date" id="newClosedDate" class="form-control" style="width:180px;">
        <input type="text" id="newClosedReason" class="form-control" placeholder="Reason (optional)" style="flex:1;">
        <button type="button" class="btn btn-primary btn-sm" onclick="addClosedDate()">
            <i class="fas fa-plus"></i> Add
        </button>
    </div>
    <div id="closedDatesList">
        <div style="color:#9ca3af;font-size:12px;" id="closedLoading">Loading…</div>
    </div>
</div>

<button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>

</form>

<script>
document.getElementById('settingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fetch('/clinic-settings', { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
        const el = document.getElementById('alertMsg');
        el.style.display = 'block';
        el.className = data.success ? 'alert alert-success' : 'alert alert-danger';
        el.textContent = data.message || (data.success ? 'Saved!' : 'Error');
        window.scrollTo(0,0);
        setTimeout(() => el.style.display='none', 3000);
    });
});

// ── Closed dates ──────────────────────────────────────────────────────────────
loadClosedDates();

function loadClosedDates() {
    fetch('/api/closed-dates')
    .then(r => r.json())
    .then(data => {
        const el = document.getElementById('closedDatesList');
        if (!data.success || !data.dates.length) {
            el.innerHTML = '<div style="color:#9ca3af;font-size:12px;">No closed dates added yet.</div>';
            return;
        }
        el.innerHTML = data.dates.map(d =>
            `<div style="display:flex;align-items:center;justify-content:space-between;padding:6px 10px;background:#f9fafb;border-radius:6px;margin-bottom:6px;font-size:12px;">
                <div>
                    <strong>${d.date}</strong>
                    ${d.reason ? ' <span style="color:#6b7280;">— ' + escHtml(d.reason) + '</span>' : ''}
                </div>
                <button onclick="removeClosedDate(${d.id})" style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:13px;" title="Remove">
                    <i class="fas fa-times"></i>
                </button>
            </div>`
        ).join('');
    });
}

function addClosedDate() {
    const date   = document.getElementById('newClosedDate').value;
    const reason = document.getElementById('newClosedReason').value.trim();
    if (!date) { alert('Please select a date.'); return; }
    fetch('/api/closed-dates/add', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'date=' + encodeURIComponent(date) + '&reason=' + encodeURIComponent(reason)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('newClosedDate').value = '';
            document.getElementById('newClosedReason').value = '';
            loadClosedDates();
        } else {
            alert(data.message || 'Error adding date.');
        }
    });
}

function removeClosedDate(id) {
    if (!confirm('Remove this closed date?')) return;
    fetch('/api/closed-dates/remove', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'id=' + id
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) loadClosedDates();
        else alert(data.message || 'Error removing date.');
    });
}

function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
