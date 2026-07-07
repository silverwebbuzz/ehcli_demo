<?php
$page_title = 'Appointments';

// Latest record first (queue data comes ordered by token ascending)
$queue   = array_reverse($queueData['queue'] ?? []);
$stats   = $queueData['stats'] ?? [];
$date    = $queueData['date']  ?? date('Y-m-d');
$view    = $queueData['view']  ?? 'today';
$from    = $queueData['from']  ?? $date;
$to      = $queueData['to']    ?? $date;
$today   = date('Y-m-d');
$tableId = 'queueTable';
$compact = false;
ob_start();

// Sub-label shown under the heading
$viewLabel = [
    'today' => ($date === $today ? 'Today — ' : '') . date('d M Y', strtotime($date)),
    'week'  => date('d M', strtotime($from)) . ' – ' . date('d M Y', strtotime($to)),
    'month' => date('F Y'),
][$view] ?? '';

$multiDay = ($view !== 'today');
?>
<style>
.appt-view-tabs { display:flex; gap:6px; }
.appt-view-tab {
    padding:5px 16px; border-radius:20px; border:2px solid #e5e7eb;
    background:#fff; font-size:12px; font-weight:600; color:#6b7280;
    text-decoration:none; transition:.15s; white-space:nowrap;
}
.appt-view-tab:hover { border-color:#93c5fd; color:var(--primary); text-decoration:none; }
.appt-view-tab.active { border-color:var(--primary); background:var(--primary); color:#fff; }

.queue-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:10px; margin-bottom:14px; }
.q-stat { background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:10px 14px; }
.q-stat .val { font-size:22px; font-weight:700; line-height:1; }
.q-stat .lbl { font-size:11px; color:#6b7280; margin-top:2px; }
.token-badge { display:inline-block; width:34px; height:34px; border-radius:50%; background:var(--primary); color:#fff; font-weight:700; font-size:12px; line-height:34px; text-align:center; }
.status-btns .btn { padding:3px 8px; font-size:11px; }
.date-nav { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.appt-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:14px; }
#filterTabs-queueTable .nav-link { padding:4px 10px; font-size:12px; }
.queue-row td { vertical-align:middle; }
.time-col { text-align:center; min-width:64px; }
.time-col .tlabel { font-size:10px; color:#9ca3af; display:block; }
.queue-row[data-status="arrived"]         { background:#f0fdf4; }
.queue-row[data-status="in_consultation"] { background:#eff6ff; }
.queue-row[data-status="completed"]       { opacity:.75; }
.queue-row[data-status="no_show"]         { opacity:.6; }
.queue-row[data-status="cancelled"]       { opacity:.5; }

/* Late arrival highlight — orange left border + warm tint */
.queue-row.row-late                       { background:#fff7ed !important; border-left:3px solid #f97316; }

/* Late / Overdue badge */
.badge-late {
    display:inline-block;
    font-size:9px; font-weight:700;
    background:#fff7ed; color:#c2410c;
    border:1px solid #fed7aa;
    border-radius:4px; padding:1px 6px;
    letter-spacing:.3px; text-transform:uppercase;
    white-space:nowrap;
}
.date-group-header td { background:#f9fafb; font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.5px; padding:6px 14px; border-top:2px solid #e5e7eb; }
.pay-badge { display:inline-block; font-size:10px; font-weight:700; padding:1px 7px; border-radius:10px; text-transform:uppercase; letter-spacing:.3px; }
.pay-badge.pay-cash      { background:#ecfdf5; color:#047857; }
.pay-badge.pay-online    { background:#eff6ff; color:#1d4ed8; }
.pay-badge.pay-paid      { background:#ecfdf5; color:#047857; }
.pay-badge.pay-remaining { background:#fef2f2; color:#b91c1c; }
@media(max-width:768px){
    .queue-grid { grid-template-columns:repeat(3,1fr); }
}
@media(max-width:500px){
    .queue-grid { grid-template-columns:repeat(2,1fr); }
    .q-stat .val { font-size:18px; }
    .appt-view-tabs { flex-wrap:wrap; }
}
</style>

<div class="appt-header page-header" style="padding-bottom:12px;">
    <div>
        <h1 class="page-title" style="margin:0;"><i class="fas fa-calendar-check"></i> Appointments</h1>
        <?php if ($viewLabel): ?>
        <div style="font-size:12px;color:#9ca3af;margin-top:2px;"><?php echo htmlspecialchars($viewLabel); ?></div>
        <?php endif; ?>
    </div>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">

        <!-- Today / This Week / This Month pills -->
        <div class="appt-view-tabs">
            <a href="/queue?view=today" class="appt-view-tab <?php echo $view==='today'?'active':''; ?>">
                <i class="fas fa-sun"></i> Today
            </a>
            <a href="/queue?view=week" class="appt-view-tab <?php echo $view==='week'?'active':''; ?>">
                <i class="fas fa-calendar-week"></i> This Week
            </a>
            <a href="/queue?view=month" class="appt-view-tab <?php echo $view==='month'?'active':''; ?>">
                <i class="fas fa-calendar-alt"></i> This Month
            </a>
        </div>

        <!-- Date prev/next (today view only) -->
        <?php if ($view === 'today'): ?>
        <div class="date-nav">
            <a href="/queue?view=today&date=<?php echo date('Y-m-d', strtotime($date . ' -1 day')); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-chevron-left"></i></a>
            <input type="date" id="dateJump" class="form-control form-control-sm" value="<?php echo $date; ?>" style="width:130px;"
                   onchange="location='/queue?view=today&date='+this.value">
            <?php if ($date !== $today): ?>
                <a href="/queue" class="btn btn-outline-primary btn-sm">Today</a>
            <?php endif; ?>
            <a href="/queue?view=today&date=<?php echo date('Y-m-d', strtotime($date . ' +1 day')); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-chevron-right"></i></a>
        </div>
        <?php endif; ?>

        <a href="/walkin" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Walk-in / Old Case</a>
        <a href="/walkin?new=1" class="btn btn-success btn-sm"><i class="fas fa-user-plus"></i> New Patient</a>
    </div>
</div>

<!-- Stats -->
<div class="queue-grid">
    <div class="q-stat">
        <div class="val"><?php echo (int)($stats['total'] ?? 0); ?></div>
        <div class="lbl">Total</div>
    </div>
    <?php if (!$multiDay): ?>
    <div class="q-stat" style="border-color:#f59e0b;">
        <div class="val" style="color:#d97706;"><?php echo (int)($stats['waiting'] ?? 0); ?></div>
        <div class="lbl">Waiting</div>
    </div>
    <div class="q-stat" style="border-color:#22c55e;">
        <div class="val" style="color:#16a34a;"><?php echo (int)($stats['arrived'] ?? 0); ?></div>
        <div class="lbl">In Clinic</div>
    </div>
    <div class="q-stat" style="border-color:#3b82f6;">
        <div class="val" style="color:#2563eb;"><?php echo (int)($stats['in_consultation'] ?? 0); ?></div>
        <div class="lbl">In Consult</div>
    </div>
    <?php else: ?>
    <div class="q-stat" style="border-color:#ef4444;">
        <div class="val" style="color:#ef4444;"><?php echo (int)($stats['no_show'] ?? 0); ?></div>
        <div class="lbl">No Show</div>
    </div>
    <div class="q-stat" style="border-color:#9ca3af;">
        <div class="val" style="color:#6b7280;"><?php echo (int)($stats['cancelled'] ?? 0); ?></div>
        <div class="lbl">Cancelled</div>
    </div>
    <?php endif; ?>
    <div class="q-stat" style="border-color:#22c55e;">
        <div class="val" style="color:#16a34a;"><?php echo (int)($stats['completed'] ?? 0); ?></div>
        <div class="lbl">Completed</div>
    </div>
</div>

<div class="card" style="margin-bottom:0;">
    <div class="card-body" style="padding:0;">

        <?php if (empty($queue)): ?>
        <div style="padding:40px;text-align:center;color:#9ca3af;">
            <i class="fas fa-calendar-check" style="font-size:28px;margin-bottom:8px;display:block;"></i>
            No appointments found
        </div>
        <?php else: ?>

        <!-- Filter tabs -->
        <ul class="nav nav-tabs" id="filterTabs-<?php echo $tableId; ?>" style="margin-bottom:10px;padding:0 8px;">
            <li class="nav-item"><a class="nav-link active" href="#" data-filter="all">All</a></li>
            <li class="nav-item"><a class="nav-link" href="#" data-filter="waiting">Waiting</a></li>
            <li class="nav-item"><a class="nav-link" href="#" data-filter="arrived">Arrived</a></li>
            <li class="nav-item"><a class="nav-link" href="#" data-filter="in_consultation">In Consult</a></li>
            <li class="nav-item"><a class="nav-link" href="#" data-filter="completed">Completed</a></li>
            <li class="nav-item"><a class="nav-link" href="#" data-filter="no_show">Not Arrived</a></li>
            <li class="nav-item"><a class="nav-link" href="#" data-filter="cancelled">Cancelled</a></li>
        </ul>

        <div style="overflow-x:auto;">
        <table class="table" style="margin:0;" id="<?php echo $tableId; ?>">
            <thead>
                <tr>
                    <th style="width:46px;">#</th>
                    <?php if ($multiDay): ?><th>Date</th><?php endif; ?>
                    <th>Patient</th>
                    <th>Phone</th>
                    <th>Type</th>
                    <th>Slot</th>
                    <th class="time-col" title="Called in">In</th>
                    <th class="time-col" title="Done">Out</th>
                    <th>Complaint</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th style="width:160px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $nowTime  = date('H:i');
            $nowDate  = date('Y-m-d');
            $lastDate = null;
            foreach ($queue as $row):
                $s   = $row['status'];
                $id  = (int)$row['id'];
                $pid = (int)($row['patient_id'] ?? 0);
                $rowDate = $row['appt_date'] ?? '';

                // Is this a late arrival?
                // Prebooked, has a slot, arrived/waiting, slot time already passed today
                $slotTime  = $row['slot_time'] ?? '';
                $slotHHMM  = $slotTime ? substr($slotTime, 0, 5) : ''; // HH:MM
                $isLate    = ($row['type'] === 'prebooked')
                          && in_array($s, ['arrived', 'waiting'])
                          && $slotHHMM !== ''
                          && $rowDate === $nowDate
                          && $slotHHMM < $nowTime;

                // Date group separator for week/month view
                if ($multiDay && $rowDate !== $lastDate):
                    $lastDate = $rowDate;
            ?>
            <tr class="date-group-header">
                <td colspan="12"><?php echo date('l, d M Y', strtotime($rowDate)); ?></td>
            </tr>
            <?php endif; ?>
            <tr class="queue-row <?php echo $isLate ? 'row-late' : ''; ?>" data-status="<?php echo htmlspecialchars($s); ?>" data-id="<?php echo $id; ?>">
                <td><span class="token-badge"><?php echo (int)$row['token_number']; ?></span></td>
                <?php if ($multiDay): ?>
                <td style="font-size:12px;color:#6b7280;white-space:nowrap;"><?php echo date('d M', strtotime($rowDate)); ?></td>
                <?php endif; ?>
                <td>
                    <?php if ($pid): ?>
                        <a href="/patient/<?php echo $pid; ?>" style="font-weight:600;"><?php
                            $fn = trim(($row['fname']??'').' '.($row['lname']??''));
                            echo htmlspecialchars($fn ?: ($row['patient_name'] ?? 'Unknown'));
                        ?></a>
                    <?php else: ?>
                        <span style="font-weight:600;"><?php
                            echo htmlspecialchars(trim(($row['fname']??'').' '.($row['lname']??'')) ?: ($row['patient_name'] ?? 'Unknown'));
                        ?></span>
                        <span class="badge bg-info" style="font-size:10px;">New</span>
                    <?php endif; ?>
                </td>
                <td><?php
                    $ph = trim($row['patient_phone'] ?? $row['contact_no'] ?? '');
                    if ($ph !== '') {
                        $telDigits = preg_replace('/[^0-9+]/', '', $ph);
                        echo '<a href="tel:' . htmlspecialchars($telDigits) . '" style="font-weight:600;white-space:nowrap;"><i class="fas fa-phone-alt" style="font-size:10px;margin-right:4px;color:#16a34a;"></i>' . htmlspecialchars($ph) . '</a>';
                    } else {
                        echo '<span style="color:#d1d5db;">—</span>';
                    }
                ?></td>
                <td>
                    <?php if ($row['type'] === 'walkin'): ?>
                        <span class="badge bg-secondary"><i class="fas fa-walking"></i> Walk-in</span>
                    <?php else: ?>
                        <span class="badge" style="background:#7c3aed;color:#fff;"><i class="fas fa-calendar-check"></i> Booked</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($slotHHMM): ?>
                        <?php echo date('h:i A', strtotime($slotHHMM)); ?>
                        <?php if ($isLate): ?>
                        <span class="badge-late"><i class="fas fa-clock"></i> Late</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color:#9ca3af;">—</span>
                    <?php endif; ?>
                </td>
                <td class="time-col"><?php
                    $ca = $row['called_at'] ?? null;
                    echo ($ca && $ca !== '0000-00-00 00:00:00') ? '<span style="font-size:11px;">'.date('h:i A', strtotime($ca)).'</span>' : '<span style="color:#d1d5db;">—</span>';
                ?></td>
                <td class="time-col"><?php
                    $cp = $row['completed_at'] ?? null;
                    echo ($cp && $cp !== '0000-00-00 00:00:00') ? '<span style="font-size:11px;">'.date('h:i A', strtotime($cp)).'</span>' : '<span style="color:#d1d5db;">—</span>';
                ?></td>
                <td><?php $cc=trim($row['chief_complaint']??''); echo $cc!==''?htmlspecialchars($cc):'<span style="color:#d1d5db;">—</span>'; ?></td>
                <td class="status-cell"><?php
                    $sMap = [
                        'waiting'         => ['warning',  'Waiting'],
                        'arrived'         => ['info',     'Arrived'],
                        'in_consultation' => ['primary',  'In Consult'],
                        'completed'       => ['success',  'Completed'],
                        'cancelled'       => ['secondary','Cancelled'],
                        'no_show'         => ['danger',   'Not Arrived'],
                    ];
                    [$cls,$lbl] = $sMap[$s] ?? ['secondary', ucfirst($s)];
                    if ($isLate && $s === 'arrived') {
                        echo "<span class=\"badge bg-{$cls}\">Arrived</span> <span class=\"badge-late\"><i class=\"fas fa-clock\"></i> Late</span>";
                    } elseif ($isLate && $s === 'waiting') {
                        echo "<span class=\"badge bg-{$cls}\">Waiting</span> <span class=\"badge-late\"><i class=\"fas fa-clock\"></i> Overdue</span>";
                    } else {
                        echo "<span class=\"badge bg-{$cls}\">{$lbl}</span>";
                    }
                ?></td>
                <td class="payment-cell"><?php
                    if ($s === 'completed' && !empty($row['report_id'])) {
                        $pt  = $row['payment_type']   ?? 'cash';
                        $ps  = $row['payment_status'] ?? 'paid';
                        $amt = (int)($row['report_amt'] ?? 0);
                        $ptLbl = $pt === 'online' ? 'Online' : 'Cash';
                        $psLbl = $ps === 'remaining' ? 'Due' : 'Paid';
                        // Payment method only matters once paid — hide it while Due
                        if ($ps !== 'remaining') {
                            echo '<span class="pay-badge pay-' . htmlspecialchars($pt) . '">' . $ptLbl . '</span> ';
                        }
                        echo '<span class="pay-badge pay-' . htmlspecialchars($ps) . '">' . $psLbl . '</span>';
                        if ($amt > 0) {
                            echo '<div style="font-size:11px;color:#6b7280;margin-top:2px;">&#8377;' . number_format($amt) . '</div>';
                        }
                    } else {
                        echo '<span style="color:#d1d5db;">—</span>';
                    }
                ?></td>
                <td class="status-btns">
                    <?php
                    $qRole       = $_SESSION['role'] ?? 'doctor';
                    $qCanConsult = in_array($qRole, ['doctor','asst_doctor']);
                    $isWalkin    = ($row['type'] === 'walkin');
                    ?>

                    <?php if ($s === 'waiting'): ?>
                        <?php if ($isWalkin): ?>
                            
                            <button class="btn btn-success btn-sm" onclick="setStatus(<?php echo $id; ?>,'arrived')">
                                <i class="fas fa-check-circle"></i> Arrived
                            </button>
                        <?php else: ?>
                            
                            <button class="btn btn-success btn-sm" onclick="setStatus(<?php echo $id; ?>,'arrived')">
                                <i class="fas fa-check-circle"></i> Arrived
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="setStatus(<?php echo $id; ?>,'no_show')" style="color:#fff;">
                                <i class="fas fa-user-slash"></i> Not Arrived
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-secondary btn-sm" onclick="setStatus(<?php echo $id; ?>,'cancelled')" title="Cancel">
                            <i class="fas fa-times"></i>
                        </button>

                    <?php elseif ($s === 'arrived'): ?>
                        
                        <?php if ($qCanConsult): ?>
                        <button class="btn btn-primary btn-sm" onclick="callPatient(<?php echo $id; ?>,<?php echo $pid; ?>)">
                            <i class="fas fa-stethoscope"></i> Call
                        </button>
                        <?php else: ?>
                        <span style="color:#16a34a;font-size:11px;font-weight:600;">
                            <i class="fas fa-user-check"></i> In Clinic
                        </span>
                        <?php endif; ?>
                        <button class="btn btn-secondary btn-sm" onclick="setStatus(<?php echo $id; ?>,'cancelled')" title="Cancel">
                            <i class="fas fa-times"></i>
                        </button>

                    <?php elseif ($s === 'in_consultation'): ?>
                        
                        <?php if ($qCanConsult): ?>
                        <button class="btn btn-success btn-sm" onclick="finishConsult(<?php echo $id; ?>)">
                            <i class="fas fa-check"></i> Finish
                        </button>
                        <?php if ($pid): ?>
                        <a href="/patient/<?php echo $pid; ?>" class="btn btn-secondary btn-sm" title="View Patient">
                            <i class="fas fa-user"></i>
                        </a>
                        <?php endif; ?>
                        <?php else: ?>
                        <span style="color:#2563eb;font-size:11px;font-weight:600;">
                            <i class="fas fa-stethoscope"></i> With Doctor
                        </span>
                        <?php endif; ?>

                    <?php elseif ($s === 'no_show'): ?>
                        
                        <button class="btn btn-outline-primary btn-sm" onclick="setStatus(<?php echo $id; ?>,'arrived')" title="Patient came late">
                            <i class="fas fa-undo"></i> Arrived Late
                        </button>

                    <?php elseif ($s === 'completed'): ?>
                        <?php if (!empty($row['report_id'])): ?>
                        <a href="/invoice/<?php echo (int)$row['report_id']; ?>" target="_blank" class="btn btn-primary btn-sm" title="Print Invoice">
                            <i class="fas fa-print"></i> Invoice
                        </a>
                        <?php else: ?>
                        <span style="color:#9ca3af;font-size:11px;">
                            <i class="fas fa-check-double"></i> Done
                        </span>
                        <?php endif; ?>
                        <?php if ($pid && $qCanConsult): ?>
                        <a href="/patient/<?php echo $pid; ?>" class="btn btn-secondary btn-sm" title="View Patient">
                            <i class="fas fa-user"></i>
                        </a>
                        <?php endif; ?>

                    <?php elseif ($s === 'cancelled'): ?>
                        <span style="color:#9ca3af;font-size:11px;">
                            <i class="fas fa-times-circle"></i> Cancelled
                        </span>

                    <?php endif; ?>

                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <?php endif; ?>
    </div>
</div>

<script>
// Filter tabs
document.querySelectorAll('#filterTabs-<?php echo $tableId; ?> .nav-link').forEach(tab => {
    tab.addEventListener('click', e => {
        e.preventDefault();
        document.querySelectorAll('#filterTabs-<?php echo $tableId; ?> .nav-link').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        const filter = tab.dataset.filter;
        document.querySelectorAll('#<?php echo $tableId; ?> tbody tr:not(.date-group-header)').forEach(row => {
            row.style.display = (filter === 'all' || row.dataset.status === filter) ? '' : 'none';
        });
        // Also hide date group headers if all rows under them are hidden
        document.querySelectorAll('.date-group-header').forEach(hdr => {
            let next = hdr.nextElementSibling;
            let hasVisible = false;
            while (next && !next.classList.contains('date-group-header')) {
                if (next.style.display !== 'none') { hasVisible = true; break; }
                next = next.nextElementSibling;
            }
            hdr.style.display = hasVisible ? '' : 'none';
        });
    });
});

function callPatient(id, patientId) {
    doStatus(id, 'in_consultation', function(data) {
        location.href = data.redirect || location.href;
    });
}
function finishConsult(id) {
    doStatus(id, 'completed', function() { location.reload(); });
}
function setStatus(id, status) {
    doStatus(id, status, function() { location.reload(); });
}
function doStatus(id, status, cb) {
    fetch('/api/appointment/' + id + '/status', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'status=' + encodeURIComponent(status)
    })
    .then(r => r.json())
    .then(data => { if (data.success) cb(data); else alert('Error: ' + data.message); });
}

<?php if ($view === 'today'): ?>
// Auto-refresh every 60s (today view only)
let refreshTimer = setTimeout(() => location.reload(), 60000);
document.addEventListener('visibilitychange', () => {
    if (document.hidden) clearTimeout(refreshTimer);
    else refreshTimer = setTimeout(() => location.reload(), 60000);
});
<?php endif; ?>
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
