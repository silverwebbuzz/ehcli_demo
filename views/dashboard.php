<?php
ob_start();
$page_title = 'Dashboard';

function dashFmt($v) {
    if ($v === null) return 'N/A';
    $v = is_string($v) ? trim($v) : $v;
    if ($v === '' || $v === '0000-00-00') return 'N/A';
    return $v;
}
function dashFmtName($f, $l) {
    $full = trim(trim($f ?? '') . ' ' . trim($l ?? ''));
    return $full === '' ? 'N/A' : $full;
}
function dashNum($n) {
    if ($n >= 1000000) return round($n/1000000, 1) . 'M';
    if ($n >= 1000)    return round($n/1000, 1) . 'K';
    return number_format($n);
}

$stats       = $dashStats        ?? [];
$todayQueue  = $todayQueueData['queue'] ?? [];
$todayStats  = $todayQueueData['stats'] ?? [];
// Dashboard shows latest record first (queue data comes ordered by token ascending)
$queue       = array_reverse($todayQueue);
$compact     = false;
$tableId     = 'dashQueueTable';
?>
<style>
.dash-stat-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:16px; }
.dash-stat { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:14px 16px; display:flex; align-items:center; gap:12px; }
.dash-stat-icon { width:42px; height:42px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:17px; flex-shrink:0; }
.dash-stat-icon.blue   { background:#eff6ff; color:#2563eb; }
.dash-stat-icon.green  { background:#f0fdf4; color:#16a34a; }
.dash-stat-icon.yellow { background:#fffbeb; color:#d97706; }
.dash-stat-icon.purple { background:#f5f3ff; color:#7c3aed; }
.dash-stat-val  { font-size:22px; font-weight:800; line-height:1; color:#111827; }
.dash-stat-lbl  { font-size:11px; color:#6b7280; margin-top:3px; }
.dash-stat-sub  { font-size:10px; color:#9ca3af; margin-top:2px; }

/* Quick actions */
.qa-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; }
.qa-btn { display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:10px;
          border:1.5px solid #e5e7eb; background:#fff; text-decoration:none; color:#374151;
          font-weight:600; font-size:13px; transition:.15s; }
.qa-btn:hover { border-color:var(--primary); background:#eff6ff; color:var(--primary); text-decoration:none; }
.qa-btn .qa-icon { width:34px; height:34px; border-radius:8px; display:flex; align-items:center;
                   justify-content:center; font-size:14px; flex-shrink:0; }
.qa-btn .qa-sub { font-size:11px; color:#9ca3af; font-weight:400; margin-top:1px; }

@media(max-width:900px){
    .dash-stat-grid { grid-template-columns:1fr 1fr; }
    .qa-grid { grid-template-columns:1fr 1fr; }
}
@media(max-width:540px){
    .dash-stat-grid { grid-template-columns:1fr 1fr; }
    .qa-grid { grid-template-columns:1fr 1fr; }
    .dash-stat-val { font-size:18px; }
}
@media(max-width:360px){
    .dash-stat-grid { grid-template-columns:1fr; }
    .qa-grid { grid-template-columns:1fr; }
}
</style>

<?php if (isset($recentPatients) && $recentPatients['success']): ?>

<!-- PAGE HEADER -->
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
    <h1 class="page-title" style="margin:0;">
        <i class="fas fa-th-large"></i> Dashboard
    </h1>
    <span style="font-size:12px;color:#9ca3af;">
        <i class="fas fa-clock"></i> <?php echo date('l, j F Y'); ?>
    </span>
</div>

<!-- LIVE STATS -->
<div class="dash-stat-grid">
    <div class="dash-stat">
        <div class="dash-stat-icon blue"><i class="fas fa-users"></i></div>
        <div>
            <div class="dash-stat-val"><?php echo dashNum($stats['total_patients'] ?? 0); ?></div>
            <div class="dash-stat-lbl">Total Patients</div>
            <div class="dash-stat-sub"><?php echo number_format($stats['total_patients'] ?? 0); ?> records</div>
        </div>
    </div>
    <div class="dash-stat">
        <div class="dash-stat-icon green"><i class="fas fa-file-medical"></i></div>
        <div>
            <div class="dash-stat-val"><?php echo dashNum($stats['total_reports'] ?? 0); ?></div>
            <div class="dash-stat-lbl">Progress Reports</div>
            <div class="dash-stat-sub">All time visits</div>
        </div>
    </div>
    <div class="dash-stat">
        <div class="dash-stat-icon yellow"><i class="fas fa-user-plus"></i></div>
        <div>
            <div class="dash-stat-val"><?php echo number_format($stats['new_this_month'] ?? 0); ?></div>
            <div class="dash-stat-lbl">New This Month</div>
            <div class="dash-stat-sub"><?php echo date('F Y'); ?></div>
        </div>
    </div>
    <div class="dash-stat">
        <div class="dash-stat-icon purple"><i class="fas fa-check-circle"></i></div>
        <div>
            <div class="dash-stat-val"><?php echo number_format($stats['completed_today'] ?? 0); ?></div>
            <div class="dash-stat-lbl">Seen Today</div>
            <div class="dash-stat-sub">
                <?php
                $waiting  = (int)($todayStats['waiting'] ?? 0);
                $arrived  = (int)($todayStats['arrived'] ?? 0);
                $inCons   = (int)($todayStats['in_consultation'] ?? 0);
                $pending  = $waiting + $arrived + $inCons;
                echo $pending > 0 ? $pending . ' still pending' : 'Queue clear';
                ?>
            </div>
        </div>
    </div>
</div>

<!-- TODAY'S APPOINTMENTS -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
        <span>
            <i class="fas fa-calendar-check"></i> Today's Appointments
            <span style="font-size:11px;color:#9ca3af;margin-left:6px;"><?php echo date('d M Y'); ?></span>
        </span>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <span style="font-size:12px;color:#6b7280;white-space:nowrap;">
                <span style="color:#f59e0b;font-weight:700;"><?php echo (int)($todayStats['waiting'] ?? 0); ?></span> waiting &nbsp;
                <span style="color:#16a34a;font-weight:700;"><?php echo (int)($todayStats['arrived'] ?? 0); ?></span> in clinic &nbsp;
                <span style="color:#2563eb;font-weight:700;"><?php echo (int)($todayStats['in_consultation'] ?? 0); ?></span> in consult &nbsp;
                <span style="color:#9ca3af;font-weight:700;"><?php echo (int)($todayStats['completed'] ?? 0); ?></span> done
            </span>
            <a href="/queue" class="btn btn-secondary btn-sm"><i class="fas fa-expand-alt"></i> All Appointments</a>
            <a href="/walkin" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Walk-in / Old Case</a>
            <a href="/walkin?new=1" class="btn btn-success btn-sm"><i class="fas fa-user-plus"></i> New Patient</a>
        </div>
    </div>
    <div class="card-body" style="padding:0;">
        <style>
        .token-badge { display:inline-block;width:32px;height:32px;border-radius:50%;background:var(--primary);color:#fff;font-weight:700;font-size:12px;line-height:32px;text-align:center; }
        .queue-row td { vertical-align:middle; }
        .status-btns .btn { padding:3px 8px;font-size:11px; }
        .queue-row[data-status="in_consultation"] { background:#eff6ff; }
        .queue-row[data-status="completed"] { opacity:.75; }
        </style>
        <?php require __DIR__ . '/appointment/_queue_table.php'; ?>
    </div>
    <?php if (!empty($todayQueue)): ?>
    <div style="padding:10px 16px;text-align:right;border-top:1px solid #f3f4f6;">
        <a href="/queue" style="font-size:12px;color:var(--primary);text-decoration:none;">
            <i class="fas fa-arrow-right"></i> View all appointments
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- TODAY VISITED PATIENTS -->
<?php
$visited     = $visitedToday ?? [];
$visitedSum  = 0;
foreach ($visited as $vRow) { $visitedSum += (int)($vRow['amt'] ?? 0); }
// Roles allowed to record a payment (mirrors /api/report/{id}/payment auth)
$canRecordPay = in_array($_SESSION['role'] ?? 'doctor', ['reception', 'doctor', 'asst_doctor']);
?>
<div class="card" style="margin-bottom:20px;">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
        <span>
            <i class="fas fa-user-check"></i> Today Visited Patients
            <span style="font-size:11px;color:#9ca3af;margin-left:6px;"><?php echo date('d M Y'); ?></span>
        </span>
        <span style="font-size:12px;color:#6b7280;white-space:nowrap;">
            <span style="color:#16a34a;font-weight:700;"><?php echo count($visited); ?></span> visited &nbsp;
            <span style="color:#2563eb;font-weight:700;">₹<?php echo number_format($visitedSum); ?></span> collected
        </span>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (!empty($visited)): ?>
        <div class="table-responsive">
            <table class="table" style="margin:0;">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Contact</th>
                        <th>Medicines</th>
                        <th>Amount</th>
                        <th>Time</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($visited as $v): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars(dashFmtName($v['fname'] ?? '', $v['lname'] ?? '')); ?></strong>
                        <div style="font-size:10px;color:#9ca3af;">ID: <?php echo $v['patient_id'] ?? $v['p_id']; ?></div>
                    </td>
                    <td style="font-size:13px;"><?php echo htmlspecialchars(dashFmt($v['contact_no'] ?? null)); ?></td>
                    <td style="font-size:12px;color:#6b7280;max-width:220px;">
                        <?php
                        $meds = trim($v['medicins'] ?? '');
                        echo $meds === '' ? '<span style="color:#d1d5db;">—</span>' : htmlspecialchars(mb_strimwidth($meds, 0, 55, '…'));
                        ?>
                    </td>
                    <td style="font-size:13px;white-space:nowrap;">
                        <?php if (!empty($v['amt']) && (int)$v['amt'] > 0): ?>
                            ₹<?php echo number_format((int)$v['amt']); ?>
                            <?php $due = ($v['payment_status'] ?? 'paid') === 'remaining'; ?>
                            <?php if ($due && $canRecordPay && !empty($v['id'])): ?>
                                <button type="button"
                                        style="background:none;border:none;padding:0;cursor:pointer;"
                                        title="Click to record payment"
                                        onclick="openPayModal(<?php echo (int)$v['id']; ?>, <?php echo (int)$v['amt']; ?>, '<?php echo htmlspecialchars($v['payment_type'] ?? 'cash', ENT_QUOTES); ?>', '<?php echo htmlspecialchars(addslashes(dashFmtName($v['fname'] ?? '', $v['lname'] ?? '')), ENT_QUOTES); ?>')">
                                    <span style="font-size:9px;font-weight:700;padding:1px 6px;border-radius:10px;background:#fef2f2;color:#dc2626;">Due</span>
                                </button>
                            <?php else: ?>
                                <span style="font-size:9px;font-weight:700;padding:1px 6px;border-radius:10px;<?php echo $due ? 'background:#fef2f2;color:#dc2626;' : 'background:#f0fdf4;color:#16a34a;'; ?>">
                                    <?php echo $due ? 'Due' : 'Paid'; ?>
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color:#d1d5db;">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:#9ca3af;white-space:nowrap;">
                        <?php
                        $vd = $v['date'] ?? '';
                        echo ($vd && strtotime($vd)) ? date('h:i A', strtotime($vd)) : '—';
                        ?>
                    </td>
                    <td>
                        <a href="/patient/<?php echo $v['p_id']; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:40px 20px;color:#9ca3af;">
            <i class="fas fa-user-clock" style="font-size:2.5rem;margin-bottom:12px;display:block;"></i>
            No patients visited yet today
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- QUICK ACTIONS -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><i class="fas fa-bolt"></i> Quick Actions</div>
    <div class="card-body">
        <div class="qa-grid">

            <a href="/walkin" class="qa-btn">
                <div class="qa-icon" style="background:#eff6ff;color:#2563eb;"><i class="fas fa-calendar-check"></i></div>
                <div>
                    <div>Walk-in Booking</div>
                    <div class="qa-sub">Add patient to today's appointments</div>
                </div>
            </a>

            <a href="/patients" class="qa-btn">
                <div class="qa-icon" style="background:#f0fdf4;color:#16a34a;"><i class="fas fa-search"></i></div>
                <div>
                    <div>Search Patient</div>
                    <div class="qa-sub">Find by name or phone</div>
                </div>
            </a>

            <a href="/patient/create" class="qa-btn">
                <div class="qa-icon" style="background:#fffbeb;color:#d97706;"><i class="fas fa-user-plus"></i></div>
                <div>
                    <div>New Patient</div>
                    <div class="qa-sub">Register a new patient</div>
                </div>
            </a>

            <a href="/queue" class="qa-btn">
                <div class="qa-icon" style="background:#fdf4ff;color:#9333ea;"><i class="fas fa-calendar-check"></i></div>
                <div>
                    <div>Appointments</div>
                    <div class="qa-sub">Today's queue &amp; schedule</div>
                </div>
            </a>

            <?php if (in_array($_SESSION['role'] ?? 'doctor', ['doctor','asst_doctor'])): ?>
            <a href="/reports/income" class="qa-btn">
                <div class="qa-icon" style="background:#fff7ed;color:#ea580c;"><i class="fas fa-chart-bar"></i></div>
                <div>
                    <div>Reports</div>
                    <div class="qa-sub">Income, patients & more</div>
                </div>
            </a>
            <?php endif; ?>

            <?php if (($_SESSION['role'] ?? 'doctor') === 'doctor'): ?>
            <a href="/clinic-settings" class="qa-btn">
                <div class="qa-icon" style="background:#f1f5f9;color:#475569;"><i class="fas fa-cog"></i></div>
                <div>
                    <div>Settings</div>
                    <div class="qa-sub">Slots, holidays, clinic info</div>
                </div>
            </a>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- RECENT PATIENTS -->
<div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
        <span><i class="fas fa-history"></i> Recently Registered Patients</span>
        <a href="/patients" class="btn btn-secondary btn-sm"><i class="fas fa-users"></i> All Patients</a>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (!empty($recentPatients['data'])): ?>
        <div class="table-responsive">
            <table class="table" style="margin:0;">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Contact</th>
                        <th>Gender</th>
                        <th>Age</th>
                        <th>Chief Complaint</th>
                        <th>Registered</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recentPatients['data'] as $p): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars(dashFmtName($p['fname'] ?? '', $p['lname'] ?? '')); ?></strong>
                        <div style="font-size:10px;color:#9ca3af;">ID: <?php echo $p['patient_id'] ?? $p['id']; ?></div>
                    </td>
                    <td style="font-size:13px;"><?php echo htmlspecialchars(dashFmt($p['contact_no'] ?? null)); ?></td>
                    <td>
                        <?php if (($p['gender'] ?? '') === 'M'): ?>
                            <span class="badge badge-male"><i class="fas fa-mars"></i> M</span>
                        <?php elseif (($p['gender'] ?? '') === 'F'): ?>
                            <span class="badge badge-female"><i class="fas fa-venus"></i> F</span>
                        <?php else: ?>
                            <span style="color:#d1d5db;">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:13px;"><?php echo dashFmt($p['age'] ?? null); ?></td>
                    <td style="font-size:12px;color:#6b7280;max-width:180px;">
                        <?php
                        $chief = trim($p['chief'] ?? '');
                        echo $chief === '' ? '<span style="color:#d1d5db;">—</span>' : htmlspecialchars(mb_strimwidth($chief, 0, 45, '…'));
                        ?>
                    </td>
                    <td style="font-size:12px;color:#9ca3af;white-space:nowrap;">
                        <?php
                        $dor = $p['dor'] ?? '';
                        echo ($dor && $dor !== '0000-00-00') ? date('d M Y', strtotime($dor)) : '—';
                        ?>
                    </td>
                    <td>
                        <a href="/patient/<?php echo $p['id']; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="padding:10px 16px;text-align:right;border-top:1px solid #f3f4f6;">
            <a href="/patients" style="font-size:12px;color:var(--primary);text-decoration:none;">
                <i class="fas fa-arrow-right"></i> View all patients
            </a>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:40px 20px;color:#9ca3af;">
            <i class="fas fa-inbox" style="font-size:2.5rem;margin-bottom:12px;display:block;"></i>
            No patients found
        </div>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle"></i> Error loading dashboard data
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
?>
