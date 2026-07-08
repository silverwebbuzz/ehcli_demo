<?php
ob_start();
$page_title = 'Patient Profile - Dr. Feelgood';

function fmt($value, $fallback = 'N/A') {
    if ($value === null) return $fallback;
    $value = is_string($value) ? trim($value) : $value;
    if ($value === '' || $value === '0000-00-00' || $value === '1970-01-01') return $fallback;
    return $value;
}
function fmtDate($value) {
    if ($value === null || $value === '' || $value === '0000-00-00' || strpos((string)$value, '0000') === 0 || $value === '1970-01-01') return 'N/A';
    $ts = strtotime($value);
    return $ts ? date('d M Y', $ts) : 'N/A';
}
function fmtGender($g) {
    if ($g === 'M') return 'Male'; if ($g === 'F') return 'Female'; return 'N/A';
}
function fmtMrg($s) {
    return ['S'=>'Single','M'=>'Married','D'=>'Divorced','W'=>'Widowed'][$s] ?? 'N/A';
}
function fmtVeg($v) {
    return ['V'=>'Vegetarian','NV'=>'Non-Vegetarian','EV'=>'Eggetarian'][$v] ?? 'N/A';
}
function fmtName($f, $l) {
    $full = trim(trim($f??'').' '.trim($l??''));
    return $full==='' ? 'N/A' : $full;
}
?>

<?php if (isset($response) && $response['success']):
    $p = $response['patient'];
    $reports = $response['progress_reports'] ?? [];
    $totalReports = $response['total_reports'] ?? count($reports);
    $todayReport = $response['today_report'] ?? null; // in-progress visit started earlier today
    $activeAppt  = $response['active_appt'] ?? null;  // patient's in-consultation appointment today
    $pid = $p['id'];
    // Define role/permission here so it's available throughout the whole view
    $viewerRole = $_SESSION['role'] ?? 'doctor';
    $canVisit   = in_array($viewerRole, ['doctor', 'asst_doctor']);
    // Reception may edit patient demographics/information, but NOT visits or history.
    $canEditInfo = in_array($viewerRole, ['doctor', 'asst_doctor', 'reception']);
?>

<style>
/* ── Header ── */
.pt-header {
    display:flex; align-items:center; gap:12px; flex-wrap:wrap;
    padding:12px 16px; background:white; border-radius:8px;
    box-shadow:var(--shadow-sm); margin-bottom:14px;
}
.pt-avatar {
    width:50px; height:50px; border-radius:50%;
    background:var(--primary); color:white;
    display:flex; align-items:center; justify-content:center;
    font-size:1.3rem; font-weight:700; flex-shrink:0;
}
.pt-header-info h2 { margin:0; font-size:1.25rem; font-weight:700; color:var(--gray-900); }
.pt-meta { display:flex; gap:14px; flex-wrap:wrap; margin-top:3px; }
.pt-meta span { font-size:0.83rem; color:var(--gray-600); }
.pt-meta span i { margin-right:3px; color:var(--gray-400); }
.pt-meta a { color:var(--primary); text-decoration:none; }
.pt-header-actions { margin-left:auto; display:flex; gap:8px; flex-shrink:0; }

/* ── Info panel ── */
.info-panel-header {
    display:flex; align-items:center; justify-content:space-between;
    cursor:pointer; user-select:none;
    transition: background 0.15s;
    border-radius: 4px;
    margin: -2px -4px;
    padding: 2px 4px;
}
.info-panel-header:hover { background: var(--gray-50); }
.toggle-chevron {
    display:inline-flex; align-items:center; gap:5px;
    font-size:0.75rem; color:var(--gray-500);
    background: var(--gray-100);
    border: 1px solid var(--gray-200);
    border-radius: 5px;
    padding: 3px 10px;
    font-weight: 500;
    transition: all 0.15s;
    white-space: nowrap;
    pointer-events: none;
}
.info-panel-header:hover .toggle-chevron {
    background: var(--primary-light);
    border-color: var(--primary);
    color: var(--primary);
}
.toggle-chevron .chev {
    display:inline-block;
    transition: transform 0.25s;
    font-style: normal;
}
.info-card-open .toggle-chevron .chev { transform: rotate(180deg); }
.info-grid {
    display:grid;
    grid-template-columns:repeat(4, 1fr);
    gap:0;
    border-top:1px solid var(--gray-100);
}
@media(max-width:1100px){ .info-grid { grid-template-columns:repeat(3,1fr); } }
@media(max-width:760px){  .info-grid { grid-template-columns:repeat(2,1fr); } }
@media(max-width:480px){  .info-grid { grid-template-columns:1fr; } }

/* Section divider headers inside the grid */
.info-section {
    grid-column:1/-1;
    display:flex; align-items:center; gap:8px;
    padding:9px 14px;
    background:var(--gray-50, #f9fafb);
    border-bottom:1px solid var(--gray-100);
    font-size:0.7rem; font-weight:700; text-transform:uppercase;
    letter-spacing:0.6px; color:var(--gray-500);
}
.info-section i { color:var(--primary); font-size:0.8rem; }
.info-section:not(:first-child) { border-top:1px solid var(--gray-100); }

.info-item {
    padding:9px 14px;
    border-right:1px solid var(--gray-100);
    border-bottom:1px solid var(--gray-100);
    min-width:0;
}
.info-label {
    font-size:0.72rem; text-transform:uppercase; font-weight:700;
    letter-spacing:0.4px; color:var(--gray-800); margin-bottom:3px;
}
.info-value {
    font-size:0.9rem; font-weight:600; color:var(--gray-800);
    word-break:break-word;
}
.info-value.normal { font-weight:400; }
.info-full {
    grid-column:1/-1; padding:9px 14px;
    border-bottom:1px solid var(--gray-100);
}
/* Span helpers for cleaner row arrangement */
.info-item.span-2 { grid-column:span 2; }
@media(max-width:480px){ .info-item.span-2 { grid-column:span 1; } }

/* View / Edit toggle */
.edit-btn-sm {
    font-size:0.75rem; padding:3px 10px; border-radius:4px;
    border:1px solid var(--gray-300); background:white;
    color:var(--gray-600); cursor:pointer; transition:all 0.15s;
}
.edit-btn-sm:hover { border-color:var(--primary); color:var(--primary); }
.edit-btn-sm.active { background:var(--primary); color:white; border-color:var(--primary); }

/* Edit inputs inside info grid */
.field-edit-input {
    width:100%; font-size:0.9rem; font-weight:600; color:var(--gray-800);
    border:1px solid var(--primary); border-radius:4px;
    padding:3px 7px; font-family:inherit; background:#f0f7ff;
    box-sizing:border-box;
}
.field-edit-input:focus { outline:none; box-shadow:0 0 0 2px rgba(37,99,235,0.15); }
.field-edit-select { appearance:auto; cursor:pointer; }
textarea.field-edit-input { resize:vertical; min-height:70px; font-weight:400; }
.info-save-bar {
    display:none; padding:10px 14px; background:#eff6ff;
    border-top:1px solid var(--gray-200);
    gap:8px; align-items:center;
}
.info-save-bar.visible { display:flex; }

/* ── Workspace ── */
.workspace {
    display:grid; grid-template-columns:1fr 340px;
    gap:14px; align-items:start;
}
@media(max-width:900px){ .workspace{grid-template-columns:1fr;} }
/* On mobile, history panel loses sticky — just flows naturally */
@media(max-width:900px){ .history-panel { position:static; } }
/* Visit form: stack Notes/Reports-Notes and the payment row on narrow screens */
@media(max-width:560px){
    .visit-notes-grid { grid-template-columns:1fr !important; }
    .visit-pay-grid   { grid-template-columns:1fr !important; }
}

/* ── Add report form ── */
.report-form-card .card-header { background:var(--primary); color:white; }
.r-input {
    width:100%; padding:9px 12px;
    border:1px solid var(--gray-300); border-radius:6px;
    font-size:0.93rem; font-family:inherit;
    transition:border-color 0.2s; box-sizing:border-box;
}
.r-input:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(37,99,235,0.1); }
textarea.r-input { resize:vertical; }
.save-btn {
    width:100%; padding:11px; font-size:0.95rem; font-weight:600;
    background:var(--primary); color:white; border:none;
    border-radius:6px; cursor:pointer; transition:background 0.2s;
}
.save-btn:hover { background:#1d4ed8; }
.save-btn:disabled { opacity:0.6; cursor:not-allowed; }
.save-ok {
    display:none; background:#dcfce7; color:#166534;
    padding:8px 12px; border-radius:6px; font-size:0.88rem; margin-top:8px;
}

/* ── History ── */
.history-panel { position:sticky; top:14px; }
.history-list { max-height:calc(100vh - 270px); overflow-y:auto; }
.h-item {
    padding:11px 14px; border-bottom:1px solid var(--gray-100);
    transition:background 0.15s; position:relative;
}
.h-item:last-child { border-bottom:none; }
.h-item:hover { background:var(--gray-50); }
.h-item.new-entry { background:#eff6ff; border-left:3px solid var(--primary); }
.h-date { font-size:0.78rem; font-weight:700; color:var(--primary); margin-bottom:3px; }
.h-meds { font-size:0.88rem; color:var(--gray-800); font-weight:500; margin-bottom:2px; }
.h-notes { font-size:0.82rem; color:var(--gray-500); font-style:italic; margin-bottom:2px; }
.h-notes i { color:var(--warning); margin-right:3px; }
.h-amt { font-size:0.8rem; color:var(--gray-500); }
.pay-badge { display:inline-block; font-size:0.65rem; font-weight:700; padding:1px 7px; border-radius:10px; margin-left:4px; text-transform:uppercase; letter-spacing:.3px; vertical-align:middle; }
.pay-badge.pay-cash      { background:#ecfdf5; color:#047857; }
.pay-badge.pay-online    { background:#eff6ff; color:#1d4ed8; }
.pay-badge.pay-paid      { background:#ecfdf5; color:#047857; }
.pay-badge.pay-remaining { background:#fef2f2; color:#b91c1c; }
.h-num { float:right; font-size:0.72rem; color:var(--gray-400); }
.h-action-btns {
    display:none; position:absolute; right:10px; bottom:10px;
    gap:5px;
}
.h-item:hover .h-action-btns { display:flex; }
.h-edit-btn {
    font-size:0.72rem; padding:2px 8px; border-radius:3px;
    border:1px solid var(--gray-300); background:white;
    color:var(--gray-500); cursor:pointer;
}
.h-inv-btn {
    font-size:0.72rem; padding:2px 8px; border-radius:3px;
    border:1px solid #d1fae5; background:#f0fdf4;
    color:#16a34a; cursor:pointer; text-decoration:none;
    display:inline-flex; align-items:center; gap:3px;
}
.h-edit-form {
    display:none; margin-top:8px; padding-top:8px;
    border-top:1px solid var(--gray-200);
}
.h-edit-form.open { display:block; }
.h-edit-row { display:grid; grid-template-columns:1fr 90px; gap:8px; margin-bottom:6px; }
@media(max-width:480px){ .h-edit-row { grid-template-columns:1fr; } }
.h-edit-input {
    width:100%; padding:5px 8px; font-size:0.85rem;
    border:1px solid var(--primary); border-radius:4px;
    font-family:inherit; box-sizing:border-box;
}
.h-edit-actions { display:flex; gap:6px; }
.h-save-btn {
    padding:4px 12px; font-size:0.82rem; border:none;
    border-radius:4px; background:var(--primary); color:white; cursor:pointer;
}
.h-cancel-btn {
    padding:4px 10px; font-size:0.82rem; border:1px solid var(--gray-300);
    border-radius:4px; background:white; color:var(--gray-600); cursor:pointer;
}

/* ── Medicine Tag Picker ── */
.med-tag {
    display:inline-flex; align-items:center; gap:5px;
    background:var(--primary); color:white;
    padding:3px 9px; border-radius:20px;
    font-size:11px; font-weight:500;
    animation: tagPop 0.15s ease;
}
@keyframes tagPop {
    from { transform:scale(0.8); opacity:0; }
    to   { transform:scale(1);   opacity:1; }
}
.med-tag-x {
    cursor:pointer; font-size:13px; line-height:1;
    opacity:0.75; margin-left:2px;
}
.med-tag-x:hover { opacity:1; }
.med-drop-item {
    padding:7px 12px; cursor:pointer; font-size:12px;
    display:flex; justify-content:space-between; align-items:center;
    border-bottom:1px solid var(--gray-100);
    transition:background 0.1s;
}
.med-drop-item:last-child { border-bottom:none; }
.med-drop-item:hover { background:var(--primary-light); color:var(--primary); }
.med-drop-item.selected { opacity:0.4; cursor:default; }
.med-drop-item .med-count {
    font-size:10px; color:var(--gray-400); flex-shrink:0; margin-left:8px;
}
.med-drop-item:hover .med-count { color:var(--primary); }
.med-drop-add {
    padding:7px 12px; cursor:pointer; font-size:12px;
    color:var(--primary); font-weight:600;
    display:flex; align-items:center; gap:6px;
    border-top:1px solid var(--gray-200);
}
.med-drop-add:hover { background:var(--primary-light); }
.med-drop-empty {
    padding:12px; text-align:center; font-size:11px; color:var(--gray-400);
}

/* ── Medicine Rows (name + amount per line) ── */
.med-row { display:flex; align-items:center; gap:8px; margin-bottom:7px; }
.med-row-name { position:relative; flex:1; min-width:0; }
.med-row-name .r-input { width:100%; }
.med-row-amt { width:110px; flex-shrink:0; }
.med-row-del {
    flex-shrink:0; width:32px; height:34px; border:1px solid var(--gray-300);
    background:#fff; color:#ef4444; border-radius:6px; cursor:pointer;
    font-size:15px; line-height:1; display:flex; align-items:center; justify-content:center;
}
.med-row-del:hover { background:#fef2f2; border-color:#fca5a5; }
.med-row-drop {
    display:none; position:absolute; top:calc(100% + 2px); left:0; right:0; z-index:500;
    background:#fff; border:1.5px solid var(--gray-300); border-radius:6px;
    max-height:200px; overflow-y:auto; box-shadow:0 4px 16px rgba(0,0,0,.12);
}
.med-add-row-btn {
    display:inline-flex; align-items:center; gap:6px; margin-top:2px;
    padding:6px 12px; font-size:12px; font-weight:600;
    border:1.5px dashed var(--primary); color:var(--primary);
    background:#fff; border-radius:6px; cursor:pointer;
}
.med-add-row-btn:hover { background:var(--primary-light); }
.med-rows-total {
    margin-top:8px; font-size:12px; font-weight:600; color:var(--gray-600); text-align:right;
}
.med-rows-total strong { color:var(--primary); font-size:14px; }
.med-row-head { display:flex; gap:8px; font-size:10px; font-weight:700; letter-spacing:.04em;
    text-transform:uppercase; color:var(--gray-400); margin-bottom:4px; }
.med-row-head .h-name { flex:1; }
.med-row-head .h-amt { width:110px; }
.med-row-head .h-sp  { width:32px; }

/* Previous-visit medicines: locked reference block (not saved, not totaled) */
.med-prev-ref {
    background:var(--gray-50); border:1px dashed var(--gray-300);
    border-radius:8px; padding:8px 10px 4px; margin-bottom:10px;
    max-height:260px; overflow-y:auto;
}
.med-prev-label {
    font-size:10px; font-weight:700; letter-spacing:.04em; text-transform:uppercase;
    color:var(--gray-400); margin-bottom:6px;
}
.med-prev-label i { color:var(--gray-400); }
.med-prev-date {
    font-size:11px; font-weight:700; color:var(--primary);
    margin:8px 0 4px;
}
.med-prev-date:first-of-type { margin-top:2px; }
.med-prev-date i { margin-right:4px; }
.med-row-ro .r-input {
    background:var(--gray-100); color:var(--gray-500); cursor:default;
    border-color:var(--gray-200);
}
.med-row-lock {
    flex-shrink:0; width:32px; height:34px; color:var(--gray-400);
    display:flex; align-items:center; justify-content:center; font-size:12px;
}
</style>

<?php
$fromQueue = ($_GET['from'] ?? '') === 'queue';
$apptId    = (int)($_GET['appt'] ?? 0);
// Appointment to finish: the one we arrived with, or the patient's active
// in-consultation appointment today (so Finish works even opened directly).
$finishApptId = $apptId ?: (int)($activeAppt['id'] ?? 0);
?>

<?php if (($fromQueue && $apptId) || $activeAppt): ?>
<div style="background:#eff6ff;border:2px solid #2563eb;border-radius:8px;padding:10px 16px;margin-bottom:12px;">
    <div style="font-size:12px;color:#1d4ed8;">
        <i class="fas fa-stethoscope"></i> <strong>In Consultation</strong> — Add visit notes below, then Save Visit to finish.
    </div>
</div>
<?php endif; ?>

<!-- ── HEADER ── -->
<div class="pt-header">
    <div class="pt-avatar"><?php echo strtoupper(substr($p['fname']??'P',0,1)); ?></div>
    <div class="pt-header-info">
        <h2 id="ptHeaderName"><?php echo htmlspecialchars(fmtName($p['fname']??'',$p['lname']??'')); ?></h2>
        <div class="pt-meta">
            <span><i class="fas fa-id-badge"></i> ID: <?php echo htmlspecialchars($p['patient_id']??$p['id']); ?></span>
            <?php if(!empty($p['age'])&&$p['age']>0): ?>
            <span><i class="fas fa-birthday-cake"></i> <?php echo $p['age']; ?> yrs</span>
            <?php endif; ?>
            <span><i class="fas fa-venus-mars"></i> <?php echo fmtGender($p['gender']??''); ?></span>
            <?php $ct=trim($p['contact_no']??''); if($ct!==''): ?>
            <span><i class="fas fa-phone"></i> <a href="tel:<?php echo htmlspecialchars($ct); ?>"><?php echo htmlspecialchars($ct); ?></a></span>
            <?php endif; ?>
            <span><i class="fas fa-calendar-check"></i> Reg: <?php echo fmtDate($p['dor']??''); ?></span>
            <span><i class="fas fa-file-medical"></i> <?php echo $totalReports; ?> visit<?php echo $totalReports!=1?'s':''; ?></span>
        </div>
    </div>
    <div class="pt-header-actions">
        <?php if (in_array($viewerRole, ['doctor','asst_doctor'], true)): ?>
        <a href="/intake/patient/<?php echo (int)$p['id']; ?>" class="btn btn-success btn-sm" title="Homeopathy Intake Questionnaire">
            <i class="fas fa-leaf"></i> Intake
        </a>
        <?php endif; ?>
        <a href="/patients" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
        <?php if ($viewerRole === 'doctor'): ?>
        <button class="btn btn-danger btn-sm" id="deletePatientBtn"
                onclick="deletePatient(<?php echo (int)$p['id']; ?>, '<?php echo htmlspecialchars(addslashes(fmtName($p['fname']??'',$p['lname']??'')), ENT_QUOTES); ?>')">
            <i class="fas fa-trash"></i> Delete Patient
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- ── PATIENT INFORMATION CARD ── -->
<div class="card mb-16" id="infoCard">
    <div class="card-header" style="cursor:pointer;" onclick="toggleInfo()">
        <div class="info-panel-header">
            <span style="display:flex;align-items:center;gap:8px;">
                <i class="fas fa-id-card" style="color:var(--primary);"></i>
                <strong>Patient Information</strong>
            </span>
            <div style="display:flex;gap:8px;align-items:center;">
                <?php if ($canEditInfo): ?>
                <button class="edit-btn-sm" id="infoEditBtn" onclick="event.stopPropagation();toggleInfoEdit()">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <?php endif; ?>
                <span class="toggle-chevron" id="infoToggleHint">
                    <i class="chev fas fa-chevron-down"></i>
                    <span id="infoToggleLabel">Show</span>
                </span>
            </div>
        </div>
    </div>
    <div id="infoBody" style="display:none;">
        <div class="info-grid" id="infoGrid">

            <!-- ══ CONTACT & DEMOGRAPHICS ══ -->
            <div class="info-section"><i class="fas fa-user"></i> Contact &amp; Demographics</div>

            <div class="info-item">
                <div class="info-label">First Name</div>
                <div class="info-value" id="disp_fname"><?php echo htmlspecialchars(fmt($p['fname']??null)); ?></div>
                <input type="text" class="field-edit-input edit-mode" name="fname"
                    value="<?php echo htmlspecialchars($p['fname']??''); ?>" style="display:none;">
            </div>
            <div class="info-item">
                <div class="info-label">Last Name</div>
                <div class="info-value" id="disp_lname"><?php echo htmlspecialchars(fmt($p['lname']??null)); ?></div>
                <input type="text" class="field-edit-input edit-mode" name="lname"
                    value="<?php echo htmlspecialchars($p['lname']??''); ?>" style="display:none;">
            </div>
            <div class="info-item">
                <div class="info-label">Contact No.</div>
                <div class="info-value" id="disp_contact_no">
                    <?php $ct=trim($p['contact_no']??''); echo $ct!=='' ? '<a href="tel:'.htmlspecialchars($ct).'">'.htmlspecialchars($ct).'</a>' : 'N/A'; ?>
                </div>
                <input type="text" class="field-edit-input edit-mode" name="contact_no"
                    value="<?php echo htmlspecialchars(trim($p['contact_no']??'')); ?>" style="display:none;">
            </div>
            <div class="info-item">
                <div class="info-label">Age</div>
                <div class="info-value" id="disp_age">
                    <?php echo (!empty($p['age'])&&$p['age']>0) ? htmlspecialchars($p['age']).' yrs' : 'N/A'; ?>
                </div>
                <input type="number" class="field-edit-input edit-mode" name="age"
                    value="<?php echo htmlspecialchars($p['age']??''); ?>" min="0" max="150" style="display:none;">
            </div>
            <div class="info-item">
                <div class="info-label">DOB</div>
                <div class="info-value" id="disp_dob"><?php echo fmtDate($p['dob']??''); ?></div>
                <input type="date" class="field-edit-input edit-mode" name="dob"
                    value="<?php
                        $dv = $p['dob']??'';
                        echo ($dv&&$dv!=='0000-00-00'&&$dv!=='1970-01-01') ? htmlspecialchars($dv) : '';
                    ?>" style="display:none;">
            </div>
            <div class="info-item">
                <div class="info-label">Gender</div>
                <div class="info-value" id="disp_gender"><?php echo fmtGender($p['gender']??''); ?></div>
                <select class="field-edit-input field-edit-select edit-mode" name="gender" style="display:none;">
                    <option value="">-- Select --</option>
                    <option value="M" <?php echo ($p['gender']??'')==='M'?'selected':''; ?>>Male</option>
                    <option value="F" <?php echo ($p['gender']??'')==='F'?'selected':''; ?>>Female</option>
                </select>
            </div>
            <div class="info-item">
                <div class="info-label">Marital Status</div>
                <div class="info-value" id="disp_mrg_status"><?php echo fmtMrg($p['mrg_status']??''); ?></div>
                <select class="field-edit-input field-edit-select edit-mode" name="mrg_status" style="display:none;">
                    <option value="">-- Select --</option>
                    <option value="S" <?php echo ($p['mrg_status']??'')==='S'?'selected':''; ?>>Single</option>
                    <option value="M" <?php echo ($p['mrg_status']??'')==='M'?'selected':''; ?>>Married</option>
                    <option value="D" <?php echo ($p['mrg_status']??'')==='D'?'selected':''; ?>>Divorced</option>
                    <option value="W" <?php echo ($p['mrg_status']??'')==='W'?'selected':''; ?>>Widowed</option>
                </select>
            </div>
            <div class="info-item">
                <div class="info-label">Diet</div>
                <div class="info-value" id="disp_veg"><?php echo fmtVeg($p['veg']??''); ?></div>
                <select class="field-edit-input field-edit-select edit-mode" name="veg" style="display:none;">
                    <option value="">-- Select --</option>
                    <option value="V" <?php echo ($p['veg']??'')==='V'?'selected':''; ?>>Vegetarian</option>
                    <option value="NV" <?php echo ($p['veg']??'')==='NV'?'selected':''; ?>>Non-Vegetarian</option>
                    <option value="EV" <?php echo ($p['veg']??'')==='EV'?'selected':''; ?>>Eggetarian</option>
                </select>
            </div>
            <div class="info-item">
                <div class="info-label">Religion</div>
                <div class="info-value" id="disp_religion"><?php echo htmlspecialchars(fmt($p['religion']??null)); ?></div>
                <input type="text" class="field-edit-input edit-mode" name="religion"
                    value="<?php echo htmlspecialchars(trim($p['religion']??'')); ?>" style="display:none;">
            </div>
            <div class="info-item">
                <div class="info-label">Referred By</div>
                <div class="info-value" id="disp_refered_by"><?php echo htmlspecialchars(fmt($p['refered_by']??null)); ?></div>
                <input type="text" class="field-edit-input edit-mode" name="refered_by"
                    value="<?php echo htmlspecialchars(trim($p['refered_by']??'')); ?>" style="display:none;">
            </div>

            <!-- ══ PROFESSION ══ -->
            <div class="info-section"><i class="fas fa-briefcase"></i> Profession</div>

            <div class="info-item span-2">
                <div class="info-label">Occupation</div>
                <div class="info-value" id="disp_occupation"><?php echo htmlspecialchars(fmt($p['occupation']??null)); ?></div>
                <input type="text" class="field-edit-input edit-mode" name="occupation"
                    value="<?php echo htmlspecialchars(trim($p['occupation']??'')); ?>" style="display:none;">
            </div>
            <div class="info-item span-2">
                <div class="info-label">Education</div>
                <div class="info-value" id="disp_education"><?php echo htmlspecialchars(fmt($p['education']??null)); ?></div>
                <input type="text" class="field-edit-input edit-mode" name="education"
                    value="<?php echo htmlspecialchars(trim($p['education']??'')); ?>" style="display:none;">
            </div>

            <!-- ══ ADDRESS ══ -->
            <div class="info-section"><i class="fas fa-map-marker-alt"></i> Address</div>

            <div class="info-full">
                <div class="info-label">Street / Area</div>
                <div class="info-value normal" id="disp_address"><?php echo htmlspecialchars(fmt($p['address']??null)); ?></div>
                <input type="text" class="field-edit-input edit-mode" name="address"
                    value="<?php echo htmlspecialchars(trim($p['address']??'')); ?>" style="display:none;">
            </div>
            <div class="info-item">
                <div class="info-label">City</div>
                <div class="info-value normal" id="disp_city"><?php echo htmlspecialchars(fmt($p['city']??null)); ?></div>
                <input type="text" class="field-edit-input edit-mode" name="city"
                    value="<?php echo htmlspecialchars(trim($p['city']??'')); ?>" style="display:none;">
            </div>
            <div class="info-item">
                <div class="info-label">State</div>
                <div class="info-value normal" id="disp_state"><?php echo htmlspecialchars(fmt($p['state']??null)); ?></div>
                <input type="text" class="field-edit-input edit-mode" name="state"
                    value="<?php echo htmlspecialchars(trim($p['state']??'')); ?>" style="display:none;">
            </div>
            <div class="info-item">
                <div class="info-label">ZIP Code</div>
                <div class="info-value normal" id="disp_zip"><?php echo htmlspecialchars(fmt($p['zip']??null)); ?></div>
                <input type="text" class="field-edit-input edit-mode" name="zip"
                    value="<?php echo htmlspecialchars(trim($p['zip']??'')); ?>" style="display:none;">
            </div>

            <!-- ══ CLINICAL ══ -->
            <div class="info-section"><i class="fas fa-notes-medical"></i> Clinical</div>

            <div class="info-full">
                <div class="info-label">Chief Complaint / Case Notes</div>
                <div class="info-value normal" id="disp_chief" style="white-space:pre-line;"><?php echo htmlspecialchars(fmt($p['chief']??null)); ?></div>
                <textarea class="field-edit-input edit-mode" name="chief" rows="5" style="display:none;"><?php echo htmlspecialchars(trim($p['chief']??'')); ?></textarea>
            </div>

        </div><!-- /info-grid -->

        <!-- Save bar (visible only in edit mode) -->
        <div class="info-save-bar" id="infoSaveBar">
            <button class="save-btn" style="width:auto;padding:7px 24px;" onclick="saveInfo(<?php echo $pid; ?>)">
                <i class="fas fa-save"></i> Save Changes
            </button>
            <button class="edit-btn-sm" onclick="cancelInfoEdit()">Cancel</button>
            <span id="infoSaveMsg" style="font-size:0.85rem;color:#166534;display:none;">
                <i class="fas fa-check-circle"></i> Saved!
            </span>
        </div>
    </div><!-- /infoBody -->
</div>

<!-- ── WORKSPACE ── -->
<div class="workspace" style="<?php echo $canVisit ? '' : 'grid-template-columns:1fr;'; ?>">

    <!-- LEFT: Add Report (doctor + asst_doctor only) -->
    <?php if ($canVisit): ?>
    <div class="card report-form-card">
        <div class="card-header">
            <?php if ($todayReport): ?>
            <i class="fas fa-pen-to-square"></i> Continue Today's Visit
            <?php else: ?>
            <i class="fas fa-plus-circle"></i> Add Today's Visit
            <?php endif; ?>
        </div>
        <div class="card-body" style="padding:14px;">

            <?php if ($todayReport): ?>
            <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:9px 12px;margin-bottom:12px;font-size:12px;color:#92400e;">
                <i class="fas fa-circle-info"></i> A visit was already started today. The details below are loaded — review or update and save to continue the same visit.
            </div>
            <?php endif; ?>

            <!-- Date row -->
            <div style="margin-bottom:10px;">
                <label class="info-label" style="display:block;margin-bottom:4px;">Date</label>
                <input type="date" id="reportDate" class="r-input"
                    value="<?php echo $todayReport ? date('Y-m-d', strtotime($todayReport['date'])) : date('Y-m-d'); ?>" style="height:34px;">
            </div>

            <!-- Chief Complaint / Case Notes — prefilled from the patient record, editable during the visit -->
            <div style="margin-bottom:12px;">
                <label class="info-label" style="display:block;margin-bottom:4px;">
                    Chief Complaint / Case Notes
                    <span style="font-weight:400;color:var(--gray-400);">— update if it has changed</span>
                </label>
                <textarea id="reportChief" class="r-input" rows="5" placeholder="Main reason for visit / case notes..."><?php echo htmlspecialchars($p['chief'] ?? ''); ?></textarea>
            </div>

            <?php
                // Decode a report's medicines into [{name, amount}] rows: prefer the
                // structured breakdown, fall back to comma-separated names.
                $decodeMedRows = function ($report) {
                    $rows = [];
                    if (!$report) return $rows;
                    $raw = $report['medicine_details'] ?? '';
                    $decoded = $raw !== '' ? json_decode($raw, true) : null;
                    if (is_array($decoded)) {
                        foreach ($decoded as $d) {
                            $nm = trim($d['name'] ?? '');
                            if ($nm === '') continue;
                            $rows[] = ['name' => $nm, 'amount' => (float)($d['amount'] ?? 0)];
                        }
                    }
                    if (!$rows && trim($report['medicins'] ?? '') !== '') {
                        foreach (array_filter(array_map('trim', explode(',', $report['medicins']))) as $nm) {
                            $rows[] = ['name' => $nm, 'amount' => 0];
                        }
                    }
                    return $rows;
                };

                // Editable rows = today's in-progress visit (when continuing one).
                $seedRows = $decodeMedRows($todayReport);

                // Read-only reference = EVERY previous visit's medicines, grouped by
                // visit date. Shown locked so the doctor can see the full prescribing
                // history; not saved or counted again.
                $prevGroups = [];
                foreach ($reports as $r) {
                    if ($todayReport && (int)$r['id'] === (int)$todayReport['id']) continue;
                    $rows = $decodeMedRows($r);
                    if (!$rows) continue;
                    $prevGroups[] = ['date' => $r['date'] ?? '', 'rows' => $rows];
                }
            ?>

            <!-- Medicine rows: each medicine with its own amount -->
            <div style="margin-bottom:12px;">
                <label class="info-label" style="display:block;margin-bottom:4px;">
                    Medicines
                    <span style="font-weight:400;color:var(--gray-400);margin-left:6px;">— add medicine &amp; amount, click "Add New" for more</span>
                </label>

                <?php if (!empty($prevGroups)): ?>
                <!-- Previous visits' medicines: locked reference, not saved or counted -->
                <div class="med-prev-ref">
                    <div class="med-prev-label">
                        <i class="fas fa-lock"></i> Previous visits — for reference only
                    </div>
                    <?php foreach ($prevGroups as $g): ?>
                    <div class="med-prev-date"><i class="fas fa-calendar-day"></i> <?php echo htmlspecialchars(fmtDate($g['date'])); ?></div>
                    <?php foreach ($g['rows'] as $pr): ?>
                    <div class="med-row med-row-ro">
                        <div class="med-row-name">
                            <input type="text" class="r-input med-name" value="<?php echo htmlspecialchars($pr['name']); ?>" readonly tabindex="-1">
                        </div>
                        <input type="number" class="r-input med-row-amt med-amt" value="<?php echo $pr['amount'] > 0 ? htmlspecialchars($pr['amount']) : ''; ?>" placeholder="0" readonly tabindex="-1">
                        <span class="med-row-lock" title="From a previous visit"><i class="fas fa-lock"></i></span>
                    </div>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="med-row-head">
                    <span class="h-name">Medicine</span>
                    <span class="h-amt">Amount (₹)</span>
                    <span class="h-sp"></span>
                </div>
                <div id="medRows"></div>
                <button type="button" class="med-add-row-btn" onclick="MedRows.addRow()">
                    <i class="fas fa-plus"></i> Add New
                </button>
                <div class="med-rows-total">
                    Total: <strong>₹<span id="medRowsTotal">0</span></strong>
                    <span style="font-weight:400;color:var(--gray-400);font-size:11px;margin-left:4px;">— new medicines only</span>
                </div>
            </div>

            <!-- Notes + Reports Notes side by side (50 / 50) -->
            <div class="visit-notes-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">
                <div>
                    <label class="info-label" style="display:block;margin-bottom:4px;">
                        Notes
                        <span style="font-weight:400;color:var(--gray-400);">— follow-up, observations</span>
                    </label>
                    <textarea id="reportNotes" class="r-input" placeholder="e.g. Follow-up in 2 weeks, improvement noted..." rows="3"><?php echo $todayReport ? htmlspecialchars($todayReport['notes'] ?? '') : ''; ?></textarea>
                </div>
                <div>
                    <label class="info-label" style="display:block;margin-bottom:4px;">
                        Reports - Notes
                        <span style="font-weight:400;color:var(--gray-400);">— lab / investigation findings</span>
                    </label>
                    <textarea id="reportReportsNotes" class="r-input" placeholder="e.g. CBC normal, Vit-D low, X-ray clear..." rows="3"><?php echo $todayReport ? htmlspecialchars($todayReport['reports_notes'] ?? '') : ''; ?></textarea>
                </div>
            </div>

            <!-- Amount + Payment (bottom, grouped) -->
            <div style="background:var(--gray-50);border:1px solid var(--gray-200);border-radius:8px;padding:12px;margin-bottom:14px;">
                <div class="info-label" style="margin-bottom:8px;color:var(--gray-500);font-weight:700;">
                    <i class="fas fa-rupee-sign" style="color:var(--primary);"></i> Payment
                </div>
                <div class="visit-pay-grid" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;">
                    <div>
                        <label class="info-label" style="display:block;margin-bottom:4px;">Amount (₹)
                            <span style="font-weight:400;color:var(--gray-400);">— auto total, editable</span>
                        </label>
                        <input type="number" id="reportAmt" class="r-input" placeholder="0" min="0" value="<?php echo $todayReport && (int)$todayReport['amt'] > 0 ? (int)$todayReport['amt'] : ''; ?>">
                    </div>
                    <div>
                        <label class="info-label" style="display:block;margin-bottom:4px;">Payment Type</label>
                        <?php $tPayType = $todayReport['payment_type'] ?? ''; ?>
                        <select id="reportPaymentType" class="r-input">
                            <option value="cash" <?php echo $tPayType === 'cash' ? 'selected' : ''; ?>>Cash</option>
                            <option value="online" <?php echo $tPayType === 'online' ? 'selected' : ''; ?>>Online</option>
                        </select>
                    </div>
                    <div>
                        <label class="info-label" style="display:block;margin-bottom:4px;">Payment Status</label>
                        <?php $tPayStatus = $todayReport['payment_status'] ?? ''; ?>
                        <select id="reportPaymentStatus" class="r-input">
                            <option value="paid" <?php echo $tPayStatus === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="remaining" <?php echo $tPayStatus === 'remaining' ? 'selected' : ''; ?>>Due</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Hidden fields: clean medicine names + structured name/amount rows -->
            <textarea id="reportMedicins" style="display:none;"></textarea>
            <input type="hidden" id="reportMedicineDetails" value="">

            <!-- When set, Save updates this already-started visit instead of creating a new one -->
            <input type="hidden" id="editingReportId" value="<?php echo $todayReport ? (int)$todayReport['id'] : ''; ?>">

            <button class="save-btn" id="saveReportBtn" onclick="saveReport(<?php echo $pid; ?>)">
                <i class="fas fa-save"></i> <?php echo $todayReport ? 'Update Visit' : 'Save Visit'; ?>
            </button>
            <div class="save-ok" id="saveOk">
                <i class="fas fa-check-circle"></i> Visit saved!
            </div>
            <?php
                // Completion button: when this patient has a live queue appointment it
                // finishes the consultation and returns to the queue; otherwise it just
                // returns to the patient list. Shown on load if a visit already exists
                // today, and revealed by JS right after a new visit is saved.
                $showFinishOnLoad = $finishApptId || $todayReport;
            ?>
            <button class="btn btn-success" id="finishConsultBtn"
                    style="width:100%;margin-top:10px;padding:11px;font-size:15px;font-weight:600;<?php echo $showFinishOnLoad ? '' : 'display:none;'; ?>"
                    onclick="finishConsult(<?php echo $finishApptId; ?>)">
                <i class="fas fa-check"></i> <?php echo $finishApptId ? 'Finish &amp; Back to Queue' : 'Complete Visit'; ?>
            </button>
        </div>
    </div>
    <?php endif; // canVisit ?>

    <!-- RIGHT: History -->
    <div class="history-panel">
        <div class="card">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
                <span><i class="fas fa-history"></i> Visit History</span>
                <span style="font-size:0.8rem;color:var(--gray-400);" id="visitBadge"><?php echo $totalReports; ?> total</span>
            </div>
            <div class="history-list" id="historyList">
                <?php if(!empty($reports)): ?>
                    <?php foreach($reports as $idx => $r): ?>
                    <div class="h-item <?php echo $idx===0?'new-entry':''; ?>" id="hitem-<?php echo $r['id']; ?>">
                        <div class="h-date"><i class="fas fa-calendar-day"></i> <?php echo htmlspecialchars(fmtDate($r['date']??'')); ?></div>
                        <?php if ($canVisit): ?>
                        <div class="h-num">#<?php echo htmlspecialchars($r['id']); ?></div>
                        <div class="h-meds"><?php echo htmlspecialchars(fmt($r['medicins']??null,'—')); ?></div>
                        <?php if(!empty(trim($r['notes']??''))): ?>
                        <div class="h-notes"><i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars($r['notes']); ?></div>
                        <?php endif; ?>
                        <?php if(!empty(trim($r['reports_notes']??''))): ?>
                        <div class="h-notes"><i class="fas fa-flask"></i> <?php echo htmlspecialchars($r['reports_notes']); ?></div>
                        <?php endif; ?>
                        <?php if(!empty($r['amt'])&&$r['amt']>0): ?>
                        <div class="h-amt">₹<?php echo htmlspecialchars($r['amt']); ?>
                            <?php
                                $pt = $r['payment_type']   ?? 'cash';
                                $ps = $r['payment_status'] ?? 'paid';
                            ?>
                            <span class="pay-badge pay-<?php echo htmlspecialchars($pt); ?>"><?php echo $pt==='online'?'Online':'Cash'; ?></span>
                            <span class="pay-badge pay-<?php echo htmlspecialchars($ps); ?>"><?php echo $ps==='remaining'?'Due':'Paid'; ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="h-action-btns">
                            <a href="/invoice/<?php echo $r['id']; ?>" target="_blank" class="h-inv-btn">
                                <i class="fas fa-file-invoice"></i> Invoice
                            </a>
                            <button class="h-edit-btn" onclick="toggleHistEdit(<?php echo $r['id']; ?>)">
                                <i class="fas fa-pen"></i> Edit
                            </button>
                        </div>
                        <div class="h-edit-form" id="hedit-<?php echo $r['id']; ?>">
                            <div class="h-edit-row">
                                <input type="date" class="h-edit-input" id="he-date-<?php echo $r['id']; ?>"
                                    value="<?php
                                        $rd=$r['date']??'';
                                        echo ($rd&&$rd!=='0000-00-00')?htmlspecialchars($rd):'';
                                    ?>">
                                <input type="number" class="h-edit-input" id="he-amt-<?php echo $r['id']; ?>"
                                    placeholder="₹ Amount (auto total)" min="0"
                                    value="<?php echo htmlspecialchars($r['amt']??0); ?>">
                            </div>

                            <!-- Per-medicine rows with their own live total -->
                            <div class="med-row-head" style="margin-top:6px;">
                                <span class="h-name">Medicine</span>
                                <span class="h-amt">Amount (₹)</span>
                                <span class="h-sp"></span>
                            </div>
                            <div id="medRows-<?php echo $r['id']; ?>"></div>
                            <button type="button" class="med-add-row-btn" onclick="histMed(<?php echo $r['id']; ?>).addRow()">
                                <i class="fas fa-plus"></i> Add New
                            </button>
                            <div class="med-rows-total">
                                Total: <strong>₹<span id="medRowsTotal-<?php echo $r['id']; ?>">0</span></strong>
                            </div>

                            <textarea class="h-edit-input" id="he-notes-<?php echo $r['id']; ?>"
                                rows="2" placeholder="Notes (optional)..." style="margin:6px 0 5px;"><?php echo htmlspecialchars($r['notes']??''); ?></textarea>
                            <textarea class="h-edit-input" id="he-repnotes-<?php echo $r['id']; ?>"
                                rows="2" placeholder="Reports - Notes (optional)..." style="margin-bottom:6px;"><?php echo htmlspecialchars($r['reports_notes']??''); ?></textarea>

                            <div class="h-edit-row">
                                <?php $hpt=$r['payment_type']??'cash'; $hps=$r['payment_status']??'paid'; ?>
                                <select class="h-edit-input" id="he-paytype-<?php echo $r['id']; ?>">
                                    <option value="cash" <?php echo $hpt==='cash'?'selected':''; ?>>Cash</option>
                                    <option value="online" <?php echo $hpt==='online'?'selected':''; ?>>Online</option>
                                </select>
                                <select class="h-edit-input" id="he-paystatus-<?php echo $r['id']; ?>">
                                    <option value="paid" <?php echo $hps==='paid'?'selected':''; ?>>Paid</option>
                                    <option value="remaining" <?php echo $hps==='remaining'?'selected':''; ?>>Due</option>
                                </select>
                            </div>

                            <!-- Hidden structured fields, filled by the medicine rows on save -->
                            <input type="hidden" id="he-meds-<?php echo $r['id']; ?>">
                            <input type="hidden" id="he-medsdetails-<?php echo $r['id']; ?>">

                            <div class="h-edit-actions">
                                <button class="h-save-btn" onclick="saveHistEdit(<?php echo $r['id']; ?>)">
                                    <i class="fas fa-save"></i> Save
                                </button>
                                <button class="h-cancel-btn" onclick="toggleHistEdit(<?php echo $r['id']; ?>)">Cancel</button>
                            </div>
                        </div>
                        <?php endif; // canVisit ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align:center;padding:40px 20px;color:var(--gray-400);" id="noVisitsMsg">
                        <i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
                        No visits yet
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div><!-- /workspace -->

<script>
const PID = <?php echo $pid; ?>;

// ════════════════════════════════════════
// DELETE PATIENT (doctor only) — requires typed confirmation
// ════════════════════════════════════════
function deletePatient(id, name) {
    const warning =
        'PERMANENTLY DELETE this patient?\n\n' +
        '"' + name + '"\n\n' +
        'This removes the patient AND all related records — every visit/progress report, ' +
        'additional info, and appointments. This CANNOT be undone.\n\n' +
        'Type DELETE (in capitals) to confirm:';
    const typed = prompt(warning);
    if (typed === null) return;            // cancelled
    if (typed.trim() !== 'DELETE') {
        alert('Deletion cancelled — confirmation text did not match.');
        return;
    }

    const btn = document.getElementById('deletePatientBtn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...'; }

    fetch('/api/patient/' + id + '/delete', { method: 'POST' })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const d = data.deleted || {};
            alert('Patient deleted.\n\n' +
                  'Reports removed: ' + (d.reports ?? 0) + '\n' +
                  'Appointments removed: ' + (d.appointments ?? 0));
            window.location.href = '/patients';
        } else {
            alert('Error: ' + (data.message || 'Could not delete patient'));
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-trash"></i> Delete Patient'; }
        }
    })
    .catch(() => {
        alert('Network error while deleting patient');
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-trash"></i> Delete Patient'; }
    });
}

// ════════════════════════════════════════
// MEDICINE ROWS — reusable controller: one medicine + its amount per line, live total.
// Each instance owns its own container so the main visit form AND every
// History → Edit form total independently (no cross-visit amount bleed).
// ════════════════════════════════════════
function createMedRows(cfg) {
    const el = (v) => typeof v === 'string' ? document.getElementById(v) : v;
    const rowsEl    = el(cfg.rowsEl);
    const totalEl   = el(cfg.totalEl);
    const namesEl   = el(cfg.namesEl);
    const detailsEl = el(cfg.detailsEl);
    const amtEl     = el(cfg.amtEl);

    const ctrl = {
        debounceTimers: new WeakMap(),

        // Build one row node. data = { name, amount }
        buildRow(data) {
            data = data || {};
            const row = document.createElement('div');
            row.className = 'med-row';
            row.innerHTML =
                '<div class="med-row-name">' +
                    '<input type="text" class="r-input med-name" autocomplete="off" placeholder="Type medicine name...">' +
                    '<div class="med-row-drop"></div>' +
                '</div>' +
                '<input type="number" class="r-input med-row-amt med-amt" min="0" placeholder="0">' +
                '<button type="button" class="med-row-del" title="Remove">×</button>';

            const nameInput = row.querySelector('.med-name');
            const amtInput  = row.querySelector('.med-amt');
            const drop      = row.querySelector('.med-row-drop');
            const delBtn    = row.querySelector('.med-row-del');

            nameInput.value = data.name || '';
            amtInput.value  = (data.amount && parseFloat(data.amount) > 0) ? data.amount : '';

            nameInput.addEventListener('focus', () => ctrl.search(nameInput, drop));
            nameInput.addEventListener('input', () => {
                clearTimeout(ctrl.debounceTimers.get(nameInput));
                ctrl.debounceTimers.set(nameInput, setTimeout(() => ctrl.search(nameInput, drop), 180));
                ctrl.sync();
            });
            nameInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') { e.preventDefault(); drop.style.display = 'none'; amtInput.focus(); }
                if (e.key === 'Escape') { drop.style.display = 'none'; }
            });
            amtInput.addEventListener('input', () => ctrl.sync());
            delBtn.addEventListener('click', () => { row.remove(); ctrl.ensureOne(); ctrl.sync(); });

            return row;
        },

        addRow(data, focus) {
            const node = ctrl.buildRow(data);
            rowsEl.appendChild(node);
            if (focus !== false) node.querySelector('.med-name').focus();
            ctrl.sync();
            return node;
        },

        // Always keep at least one (empty) row on screen
        ensureOne() {
            if (!rowsEl.querySelector('.med-row')) ctrl.addRow(null, false);
        },

        search(input, drop) {
            const query = input.value.trim();
            const url = '/api/medicines' + (query ? '?q=' + encodeURIComponent(query) : '');
            fetch(url)
                .then(r => r.json())
                .then(data => { if (data.success) ctrl.renderDrop(input, drop, data.data, query); })
                .catch(() => {});
        },

        renderDrop(input, drop, items, query) {
            let html = '';
            (items || []).forEach(item => {
                const count = item.usage_count > 0 ? `<span class="med-count">×${item.usage_count}</span>` : '';
                html += `<div class="med-drop-item" data-name="${escHtml(item.name)}">
                    <span>${escHtml(item.name)}</span>${count}</div>`;
            });
            if (!html && !query) html = '<div class="med-drop-empty">Start typing to search medicines</div>';
            drop.innerHTML = html;
            // mousedown so the pick registers before the input blurs
            drop.querySelectorAll('.med-drop-item').forEach(el => {
                el.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    input.value = el.getAttribute('data-name');
                    drop.style.display = 'none';
                    ctrl.sync();
                    input.closest('.med-row').querySelector('.med-amt').focus();
                });
            });
            drop.style.display = html ? 'block' : 'none';
        },

        rows() {
            return Array.from(rowsEl.querySelectorAll('.med-row')).map(r => ({
                name:   r.querySelector('.med-name').value.trim(),
                amount: parseFloat(r.querySelector('.med-amt').value) || 0,
            }));
        },

        // Push clean names + structured rows into hidden fields and auto-total the amount.
        sync() {
            const rows = ctrl.rows().filter(r => r.name !== '');
            if (namesEl)   namesEl.value   = rows.map(r => r.name).join(', ');
            if (detailsEl) detailsEl.value = JSON.stringify(rows);

            const total = rows.reduce((s, r) => s + (r.amount || 0), 0);
            if (totalEl) totalEl.textContent =
                total ? (Number.isInteger(total) ? total : total.toFixed(2)) : '0';

            // Auto-fill the payment Amount with the medicine total (still editable).
            // Only when there is a total, so legacy visits without per-medicine
            // amounts keep their saved amount.
            if (amtEl && total > 0) amtEl.value = total;
        },

        seed(list) {
            rowsEl.innerHTML = '';
            (list || []).forEach(d => ctrl.addRow({ name: d.name, amount: d.amount }, false));
            ctrl.ensureOne();
            ctrl.sync();
        },

        clear() {
            rowsEl.innerHTML = '';
            ctrl.ensureOne();
            ctrl.sync();
        }
    };
    return ctrl;
}

// Close any open row dropdown when clicking outside a name field (global, once).
document.addEventListener('mousedown', (e) => {
    if (!e.target.closest('.med-row-name')) {
        document.querySelectorAll('.med-row-drop').forEach(d => d.style.display = 'none');
    }
});

<?php if ($canVisit): ?>
// Main visit form — seed from the in-progress visit (structured rows or names).
// Only initialised for roles that see the visit form (doctor / asst_doctor);
// the target elements don't exist for reception, which would throw and break
// the rest of this script (including patient-info editing).
const MedRows = createMedRows({
    rowsEl: 'medRows', totalEl: 'medRowsTotal',
    namesEl: 'reportMedicins', detailsEl: 'reportMedicineDetails', amtEl: 'reportAmt'
});
MedRows.seed(<?php echo json_encode($seedRows ?? []); ?>);
<?php endif; ?>

// Per-history-item medicine controllers, seeded lazily from each visit's saved rows.
<?php
    // Seed rows for each past visit's edit form: prefer the structured breakdown,
    // fall back to comma-separated names (mirrors the main form's seed logic).
    $histSeed = [];
    foreach ($reports as $hr) {
        $rows = [];
        $raw = $hr['medicine_details'] ?? '';
        $decoded = $raw !== '' ? json_decode($raw, true) : null;
        if (is_array($decoded)) {
            foreach ($decoded as $d) {
                $nm = trim($d['name'] ?? '');
                if ($nm === '') continue;
                $rows[] = ['name' => $nm, 'amount' => (float)($d['amount'] ?? 0)];
            }
        }
        if (!$rows && trim($hr['medicins'] ?? '') !== '') {
            foreach (array_filter(array_map('trim', explode(',', $hr['medicins']))) as $nm) {
                $rows[] = ['name' => $nm, 'amount' => 0];
            }
        }
        $histSeed[$hr['id']] = $rows;
    }
?>
const HIST_SEED = <?php echo json_encode((object)$histSeed); ?>;
const histMedRows = {};
function histMed(id) {
    if (!histMedRows[id]) {
        histMedRows[id] = createMedRows({
            rowsEl: 'medRows-' + id, totalEl: 'medRowsTotal-' + id,
            namesEl: 'he-meds-' + id, detailsEl: 'he-medsdetails-' + id, amtEl: 'he-amt-' + id
        });
        histMedRows[id].seed(HIST_SEED[id] || []);
    }
    return histMedRows[id];
}
// Report id of an already-started visit today (empty = create a new visit)
let editingReportId = document.getElementById('editingReportId') ? document.getElementById('editingReportId').value : '';

// ── Info panel toggle ──
function toggleInfo() {
    const b     = document.getElementById('infoBody');
    const card  = document.getElementById('infoCard');
    const label = document.getElementById('infoToggleLabel');
    const open  = b.style.display === 'none';
    b.style.display = open ? 'block' : 'none';
    label.textContent = open ? 'Hide' : 'Show';
    card.classList.toggle('info-card-open', open);
}

// ── Info edit mode ──
let infoEditing = false;
function toggleInfoEdit() {
    if (infoEditing) { cancelInfoEdit(); return; }
    // Expand panel if collapsed
    const b = document.getElementById('infoBody');
    if (b.style.display === 'none') {
        b.style.display = 'block';
        document.getElementById('infoToggleLabel').textContent = 'Hide';
        document.getElementById('infoCard').classList.add('info-card-open');
    }
    infoEditing = true;
    document.getElementById('infoEditBtn').classList.add('active');
    document.getElementById('infoEditBtn').innerHTML = '<i class="fas fa-times"></i> Cancel';
    document.getElementById('infoSaveBar').classList.add('visible');
    // Show inputs, hide display values
    document.querySelectorAll('#infoGrid .edit-mode').forEach(el => el.style.display = '');
    document.querySelectorAll('#infoGrid [id^="disp_"]').forEach(el => el.style.display = 'none');
}
function cancelInfoEdit() {
    infoEditing = false;
    document.getElementById('infoEditBtn').classList.remove('active');
    document.getElementById('infoEditBtn').innerHTML = '<i class="fas fa-edit"></i> Edit';
    document.getElementById('infoSaveBar').classList.remove('visible');
    document.querySelectorAll('#infoGrid .edit-mode').forEach(el => el.style.display = 'none');
    document.querySelectorAll('#infoGrid [id^="disp_"]').forEach(el => el.style.display = '');
    document.getElementById('infoSaveMsg').style.display = 'none';
}

function saveInfo(patientId) {
    const inputs = document.querySelectorAll('#infoGrid .edit-mode');
    const fd = new FormData();
    inputs.forEach(el => { if (el.name) fd.append(el.name, el.value); });

    const saveBtn = document.querySelector('#infoSaveBar .save-btn');
    saveBtn.disabled = true; saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    fetch(`/api/patient/${patientId}/update`, { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Update display values
            const displayMap = {
                'contact_no': v => v ? `<a href="tel:${escHtml(v)}">${escHtml(v)}</a>` : 'N/A',
                'age': v => v && v > 0 ? escHtml(v) + ' yrs' : 'N/A',
                'gender': v => ({M:'Male',F:'Female'})[v] || 'N/A',
                'mrg_status': v => ({S:'Single',M:'Married',D:'Divorced',W:'Widowed'})[v] || 'N/A',
                'veg': v => ({V:'Vegetarian',NV:'Non-Vegetarian',EV:'Eggetarian'})[v] || 'N/A',
                'dob': v => { if(!v) return 'N/A'; const d=new Date(v); return isNaN(d)?'N/A':d.toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'}); },
            };
            inputs.forEach(el => {
                const disp = document.getElementById('disp_' + el.name);
                if (!disp) return;
                const fn = displayMap[el.name];
                disp.innerHTML = fn ? fn(el.value) : (escHtml(el.value) || 'N/A');
            });
            // Keep the page header (name + avatar) in sync when the name is edited
            const fnameInput = document.querySelector('#infoGrid [name="fname"]');
            const lnameInput = document.querySelector('#infoGrid [name="lname"]');
            const headerEl = document.getElementById('ptHeaderName');
            if (headerEl && fnameInput) {
                const full = ((fnameInput.value || '') + ' ' + (lnameInput ? (lnameInput.value || '') : '')).trim();
                headerEl.textContent = full || 'Patient';
                const avatar = document.querySelector('.pt-avatar');
                if (avatar) avatar.textContent = (fnameInput.value || 'P').charAt(0).toUpperCase();
            }
            document.getElementById('infoSaveMsg').style.display = 'inline';
            setTimeout(() => { cancelInfoEdit(); }, 1200);
        } else {
            alert('Save failed: ' + (data.message || ''));
        }
    })
    .catch(() => alert('Network error'))
    .finally(() => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
    });
}

// Original chief complaint, so we only push an update to the patient record when it actually changes
let originalChief = document.getElementById('reportChief') ? document.getElementById('reportChief').value : '';

// Persist an edited Chief Complaint / Case Notes back to the patient record
function syncChiefComplaint(patientId) {
    const el = document.getElementById('reportChief');
    if (!el) return;
    const chief = el.value;
    if (chief === originalChief) return; // unchanged — nothing to do

    const fd = new FormData();
    fd.append('chief', chief);
    fetch('/api/patient/' + patientId + '/update', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            originalChief = chief;
            // Keep the Patient Information panel in sync if it's on the page
            const disp = document.getElementById('disp_chief');
            if (disp) disp.textContent = chief.trim() !== '' ? chief : 'N/A';
            const ta = document.querySelector('#infoGrid textarea[name="chief"]');
            if (ta) ta.value = chief;
        }
    })
    .catch(() => {});
}

// Outbox UUID of a visit queued offline this session but not yet synced.
// Lets a repeat "Save" (still offline) update the same queued record instead
// of creating a duplicate visit.
let pendingVisitUuid = null;

// Gather the visit form into a plain object for the sync payload.
function collectReportData(patientId) {
    return {
        p_id:           patientId,
        date:           document.getElementById('reportDate').value,
        medicins:       document.getElementById('reportMedicins').value.trim(),
        medicine_details: document.getElementById('reportMedicineDetails').value,
        notes:          document.getElementById('reportNotes').value.trim(),
        reports_notes:  document.getElementById('reportReportsNotes').value.trim(),
        amt:            document.getElementById('reportAmt').value || 0,
        payment_type:   document.getElementById('reportPaymentType').value,
        payment_status: document.getElementById('reportPaymentStatus').value,
    };
}

// Render (or update) a visit row in the history list.
// `key` is the server report id when synced, or the outbox UUID when pending.
function renderVisitItem(key, d, pending) {
    const list  = document.getElementById('historyList');
    const noMsg = document.getElementById('noVisitsMsg');
    if (noMsg) noMsg.remove();

    const dateStr   = new Date(d.date).toLocaleDateString('en-IN', {day:'2-digit', month:'short', year:'numeric'});
    const payBadges = `<span class="pay-badge pay-${d.payment_type}">${d.payment_type==='online'?'Online':'Cash'}</span>`
                    + `<span class="pay-badge pay-${d.payment_status}">${d.payment_status==='remaining'?'Due':'Paid'}</span>`;
    const amtHtml   = d.amt > 0        ? `<div class="h-amt">₹${escHtml(String(d.amt))} ${payBadges}</div>` : '';
    const notesHtml = d.notes          ? `<div class="h-notes"><i class="fas fa-sticky-note"></i> ${escHtml(d.notes)}</div>` : '';
    const repHtml   = d.reports_notes  ? `<div class="h-notes"><i class="fas fa-flask"></i> ${escHtml(d.reports_notes)}</div>` : '';
    const medsDisp  = d.medicins       ? escHtml(d.medicins) : '—';

    let html;
    if (pending) {
        // No invoice/edit buttons until the server assigns a real id.
        html = `
            <div class="h-num" style="color:#fd7e14"><i class="fas fa-clock"></i> Pending sync</div>
            <div class="h-date"><i class="fas fa-calendar-day"></i> ${dateStr}</div>
            <div class="h-meds">${medsDisp}</div>
            ${notesHtml}
            ${repHtml}
            ${amtHtml}`;
    } else {
        const rId = key;
        html = `
            <div class="h-num">#${rId}</div>
            <div class="h-date"><i class="fas fa-calendar-day"></i> ${dateStr}</div>
            <div class="h-meds">${medsDisp}</div>
            ${notesHtml}
            ${repHtml}
            ${amtHtml}
            <div class="h-action-btns">
                <a href="/invoice/${rId}" target="_blank" class="h-inv-btn"><i class="fas fa-file-invoice"></i> Invoice</a>
                <button class="h-edit-btn" onclick="toggleHistEdit(${rId})"><i class="fas fa-pen"></i> Edit</button>
            </div>
            <div class="h-edit-form" id="hedit-${rId}">
                <div class="h-edit-row">
                    <input type="date" class="h-edit-input" id="he-date-${rId}" value="${escHtml(d.date)}">
                    <input type="number" class="h-edit-input" id="he-amt-${rId}" placeholder="₹ Amount" value="${escHtml(String(d.amt))}">
                </div>
                <textarea class="h-edit-input" id="he-meds-${rId}" rows="2" placeholder="Medicines..." style="margin-bottom:5px;">${escHtml(d.medicins)}</textarea>
                <textarea class="h-edit-input" id="he-notes-${rId}" rows="2" placeholder="Notes (optional)..." style="margin-bottom:5px;">${escHtml(d.notes)}</textarea>
                <textarea class="h-edit-input" id="he-repnotes-${rId}" rows="2" placeholder="Reports - Notes (optional)..." style="margin-bottom:6px;">${escHtml(d.reports_notes)}</textarea>
                <div class="h-edit-actions">
                    <button class="h-save-btn" onclick="saveHistEdit(${rId})"><i class="fas fa-save"></i> Save</button>
                    <button class="h-cancel-btn" onclick="toggleHistEdit(${rId})">Cancel</button>
                </div>
            </div>`;
    }

    const domId = pending ? ('hitem-pending-' + key) : ('hitem-' + key);
    const existing = document.getElementById(domId);
    if (existing) {
        existing.innerHTML = html;           // re-saved offline → refresh in place
        return;
    }
    const el = document.createElement('div');
    el.className = 'h-item new-entry';
    el.id = domId;
    el.innerHTML = html;
    list.querySelectorAll('.new-entry').forEach(e => e.classList.remove('new-entry'));
    list.prepend(el);

    const badge = document.getElementById('visitBadge');
    if (badge) badge.textContent = ((parseInt(badge.textContent) || 0) + 1) + ' total';
}

// ── Save new report (offline-capable) ──
async function saveReport(patientId) {
    MedRows.sync();

    // Save any change to the chief complaint alongside the visit
    syncChiefComplaint(patientId);

    const d   = collectReportData(patientId);
    const btn = document.getElementById('saveReportBtn');
    const ok  = document.getElementById('saveOk');

    // Require at least medicines OR notes OR reports-notes
    if (!d.medicins && !d.notes && !d.reports_notes) {
        const firstName = document.querySelector('#medRows .med-name');
        if (firstName) {
            firstName.style.borderColor = '#ef4444';
            firstName.focus();
            setTimeout(() => firstName.style.borderColor = '', 2000);
        }
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    // Continuing a visit already persisted on the server (has a real id).
    // Route through the offline outbox too (with report_id → server updates
    // instead of creating), so a flaky network queues + retries instead of
    // failing with a "network error".
    if (editingReportId) {
        const upd = Object.assign({}, d, { report_id: editingReportId });
        const res = await Offline.saveOffline('report', '/api/sync', upd);
        if (res.synced) { location.reload(); return; }
        if (res.queued) {
            Offline.showToast('Saved locally — changes will sync automatically when back online.', 'info');
        } else {
            alert('Error: ' + (res.error || 'Could not update visit'));
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Update Visit';
        return;
    }

    // New visit → store locally first, sync when possible.
    let res;
    if (pendingVisitUuid) {
        res = await Offline.updatePending(pendingVisitUuid, 'report', '/api/sync', d);
    } else {
        res = await Offline.saveOffline('report', '/api/sync', d);
    }

    if (res.synced) {
        pendingVisitUuid = null;
        const rId = (res.result && (res.result.report_id || res.result.server_id)) || '';
        // Drop the optimistic "pending" row if there was one.
        const stale = document.getElementById('hitem-pending-' + res.uuid);
        if (stale) stale.remove();
        renderVisitItem(rId, d, false);

        // This visit is now today's in-progress report — keep editing it.
        editingReportId = String(rId);
        const editIdInput = document.getElementById('editingReportId');
        if (editIdInput) editIdInput.value = editingReportId;

        ok.style.display = 'block';
        setTimeout(() => ok.style.display = 'none', 3000);
        const finishBtn = document.getElementById('finishConsultBtn');
        if (finishBtn) finishBtn.style.display = 'block';
    } else if (res.queued) {
        pendingVisitUuid = res.uuid;
        renderVisitItem(res.uuid, d, true);
        Offline.showToast('Data saved locally and will sync automatically when online.', 'info');
        const finishBtn = document.getElementById('finishConsultBtn');
        if (finishBtn) finishBtn.style.display = 'block';
    } else {
        alert('Error: ' + (res.error || 'Could not save visit'));
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-save"></i> ' + (editingReportId ? 'Update Visit' : 'Save Visit');
}

// Background sync finished while this page is open → mark any pending rows synced.
window.addEventListener('outbox:synced', function (e) {
    if (!e.detail || !e.detail.synced) return;
    document.querySelectorAll('[id^="hitem-pending-"]').forEach(function (el) {
        const num = el.querySelector('.h-num');
        if (num) { num.style.color = '#198754'; num.innerHTML = '<i class="fas fa-check"></i> Synced'; }
    });
    pendingVisitUuid = null;
    Offline.showToast('Saved visit synced to server.', 'success');
});

// ── History edit ──
function toggleHistEdit(id) {
    const form = document.getElementById('hedit-' + id);
    const opening = !form.classList.contains('open');
    form.classList.toggle('open');
    // Build + seed this visit's medicine rows the first time its form opens.
    if (opening) histMed(id);
}
async function saveHistEdit(id) {
    histMed(id).sync();

    const upd = {
        report_id:      id,
        p_id:           PID,
        date:           document.getElementById('he-date-'     + id).value,
        medicins:       document.getElementById('he-meds-'     + id).value.trim(),
        medicine_details: document.getElementById('he-medsdetails-' + id).value,
        notes:          document.getElementById('he-notes-'    + id)?.value.trim() || '',
        reports_notes:  document.getElementById('he-repnotes-' + id)?.value.trim() || '',
        amt:            document.getElementById('he-amt-'      + id).value || 0,
        payment_type:   document.getElementById('he-paytype-'   + id)?.value || 'cash',
        payment_status: document.getElementById('he-paystatus-' + id)?.value || 'paid',
    };

    // Offline-capable: queues + retries on a flaky network instead of erroring.
    const res = await Offline.saveOffline('report', '/api/sync', upd);
    if (res.synced) {
        // Reload so notes + amount/badges re-render cleanly
        location.reload();
    } else if (res.queued) {
        Offline.showToast('Saved locally — changes will sync automatically when back online.', 'info');
    } else {
        alert('Save failed: ' + (res.error || ''));
    }
}

function fmtDateJS(v) {
    if (!v) return 'N/A';
    const d = new Date(v);
    return isNaN(d) ? v : d.toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'});
}
function escHtml(str) {
    const d = document.createElement('div'); d.textContent = String(str); return d.innerHTML;
}

// Finish consultation — mark the queue appointment completed and go back to queue.
// When the patient was opened directly (no live queue appointment), there's nothing
// to mark in the queue — just return to the patient list.
function finishConsult(apptId) {
    if (!apptId) { window.location.href = '/patients'; return; }
    fetch('/api/appointment/' + apptId + '/status', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'status=completed'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) window.location.href = '/queue';
        else alert('Error: ' + data.message);
    });
}
</script>

<?php else: ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i>
        <?php echo htmlspecialchars($response['message']??'Patient not found'); ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
