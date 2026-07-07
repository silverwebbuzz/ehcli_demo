<?php
/**
 * Shared queue table partial — used by dashboard (compact) and queue page (full).
 * Requires: $queue array, $compact bool
 */

if (!function_exists('qFmt')) {
    function qFmt($v, $fallback = '—') {
        $v = trim((string)($v ?? ''));
        return ($v === '' || $v === '0000-00-00' || $v === '1970-01-01') ? $fallback : htmlspecialchars($v);
    }
    function qName($row) {
        $fn = trim(($row['fname'] ?? '') . ' ' . ($row['lname'] ?? ''));
        if ($fn !== '') return htmlspecialchars($fn);
        return htmlspecialchars($row['patient_name'] ?? 'Unknown');
    }
    function qTime($dt) {
        if (!$dt || $dt === '0000-00-00 00:00:00') return '<span style="color:#d1d5db;">—</span>';
        return '<span style="font-size:11px;">' . date('h:i A', strtotime($dt)) . '</span>';
    }
    function statusBadge($s, $isLate = false) {
        $map = [
            'waiting'         => ['warning',  'Waiting'],
            'arrived'         => ['info',     'Arrived'],
            'in_consultation' => ['primary',  'In Consult'],
            'completed'       => ['success',  'Completed'],
            'cancelled'       => ['secondary','Cancelled'],
            'no_show'         => ['danger',   'Not Arrived'],
        ];
        [$cls, $label] = $map[$s] ?? ['secondary', ucfirst($s)];
        $badge = "<span class=\"badge bg-{$cls}\">{$label}</span>";
        if ($isLate && in_array($s, ['arrived','waiting'])) {
            $badge .= ' <span class="badge-late"><i class="fas fa-clock"></i> Late</span>';
        }
        return $badge;
    }
}

$compact     = $compact ?? false;
$tableId     = $tableId ?? 'queueTable';
$qRole       = $_SESSION['role'] ?? 'doctor';
$qCanConsult = in_array($qRole, ['doctor', 'asst_doctor']);
$nowTime     = date('H:i');
$nowDate     = date('Y-m-d');
?>

<?php if (empty($queue)): ?>
    <div style="padding:32px;text-align:center;color:#9ca3af;">
        <i class="fas fa-calendar-day" style="font-size:28px;margin-bottom:8px;display:block;"></i>
        No appointments today
    </div>
<?php else: ?>

<div style="overflow-x:auto;">
<table class="table" style="margin:0;" id="<?php echo htmlspecialchars($tableId); ?>">
    <thead>
        <tr>
            <th style="width:42px;">#</th>
            <th>Patient</th>
            <?php if (!$compact): ?><th>Phone</th><?php endif; ?>
            <th>Type</th>
            <th>Slot</th>
            <?php if (!$compact): ?>
            <th style="min-width:60px;text-align:center;" title="Called in">In</th>
            <th style="min-width:60px;text-align:center;" title="Done">Out</th>
            <th>Complaint</th>
            <?php endif; ?>
            <th>Status</th>
            <th style="min-width:80px;text-align:center;">Payment</th>
            <th style="width:<?php echo $compact ? '130px' : '180px'; ?>;">Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($queue as $row):
        $s        = $row['status'];
        $id       = (int)$row['id'];
        $pid      = (int)($row['patient_id'] ?? 0);
        $isWalkin = ($row['type'] === 'walkin');
        $slotHHMM = $row['slot_time'] ? substr($row['slot_time'], 0, 5) : '';
        $rowDate  = $row['appt_date'] ?? $nowDate;
        $isLate   = ($row['type'] === 'prebooked')
                 && in_array($s, ['arrived','waiting'])
                 && $slotHHMM !== ''
                 && $rowDate === $nowDate
                 && $slotHHMM < $nowTime;
    ?>
        <tr class="queue-row <?php echo $isLate ? 'row-late' : ''; ?>"
            data-status="<?php echo htmlspecialchars($s); ?>" data-id="<?php echo $id; ?>">

            <td><span class="token-badge"><?php echo (int)$row['token_number']; ?></span></td>

            <td>
                <?php if ($pid): ?>
                    <a href="/patient/<?php echo $pid; ?>" style="font-weight:600;"><?php echo qName($row); ?></a>
                <?php else: ?>
                    <span style="font-weight:600;"><?php echo qName($row); ?></span>
                    <span class="badge bg-info" style="font-size:9px;">New</span>
                <?php endif; ?>
            </td>

            <?php if (!$compact): ?>
            <td><?php
                $ph = trim($row['patient_phone'] ?? $row['contact_no'] ?? '');
                if ($ph !== '') {
                    $telDigits = preg_replace('/[^0-9+]/', '', $ph);
                    echo '<a href="tel:' . htmlspecialchars($telDigits) . '" style="font-weight:600;white-space:nowrap;"><i class="fas fa-phone-alt" style="font-size:10px;margin-right:4px;color:#16a34a;"></i>' . htmlspecialchars($ph) . '</a>';
                } else {
                    echo '<span style="color:#d1d5db;">—</span>';
                }
            ?></td>
            <?php endif; ?>

            <td>
                <?php if ($isWalkin): ?>
                    <span class="badge bg-secondary"><i class="fas fa-walking"></i> Walk-in</span>
                <?php else: ?>
                    <span class="badge" style="background:#7c3aed;color:#fff;font-size:10px;"><i class="fas fa-calendar-check"></i> Booked</span>
                <?php endif; ?>
            </td>

            <td>
                <?php if ($slotHHMM): ?>
                    <?php echo date('h:i A', strtotime($slotHHMM)); ?>
                    <?php if ($isLate): ?><br><span class="badge-late"><i class="fas fa-clock"></i> Late</span><?php endif; ?>
                <?php else: ?>
                    <span style="color:#9ca3af;">—</span>
                <?php endif; ?>
            </td>

            <?php if (!$compact): ?>
            <td style="text-align:center;"><?php
                $ca = $row['called_at'] ?? null;
                echo ($ca && $ca !== '0000-00-00 00:00:00') ? '<span style="font-size:11px;">'.date('h:i A', strtotime($ca)).'</span>' : '<span style="color:#d1d5db;">—</span>';
            ?></td>
            <td style="text-align:center;"><?php
                $cp = $row['completed_at'] ?? null;
                echo ($cp && $cp !== '0000-00-00 00:00:00') ? '<span style="font-size:11px;">'.date('h:i A', strtotime($cp)).'</span>' : '<span style="color:#d1d5db;">—</span>';
            ?></td>
            <td style="font-size:12px;color:#6b7280;"><?php echo qFmt($row['chief_complaint'] ?? ''); ?></td>
            <?php endif; ?>

            <td><?php echo statusBadge($s, $isLate); ?></td>

            <td style="text-align:center;">
                <?php if ($s === 'completed' && !empty($row['report_id'])): ?>
                    <?php
                        $payStatus = $row['payment_status'] ?? 'paid';
                        $payAmt    = (int)($row['report_amt'] ?? 0);
                        $payType   = $row['payment_type']   ?? 'cash';
                        $rptId     = (int)$row['report_id'];
                    ?>
                    <?php if ($payStatus === 'paid'): ?>
                        <span class="pay-badge pay-paid">Paid</span>
                    <?php elseif ($qRole === 'reception'): ?>
                        <button type="button" class="pay-badge-btn"
                                onclick="openPayModal(<?php echo $rptId; ?>, <?php echo $payAmt; ?>, '<?php echo htmlspecialchars($payType); ?>', '<?php echo htmlspecialchars(addslashes(qName($row)), ENT_QUOTES); ?>')"
                                title="Click to record payment">
                            <span class="pay-badge pay-remaining">Due</span>
                        </button>
                    <?php else: ?>
                        <span class="pay-badge pay-remaining">Due</span>
                    <?php endif; ?>
                <?php else: ?>
                    <span style="color:#d1d5db;font-size:12px;">—</span>
                <?php endif; ?>
            </td>

            <td class="status-btns">

                <?php if ($s === 'waiting'): ?>

                    <?php if ($isWalkin): ?>
                        <button class="btn btn-success btn-sm" onclick="setStatus(<?php echo $id; ?>,'arrived')">
                            <i class="fas fa-check-circle"></i><?php echo $compact ? '' : ' Arrived'; ?>
                        </button>
                    <?php else: ?>
                        <button class="btn btn-success btn-sm" onclick="setStatus(<?php echo $id; ?>,'arrived')">
                            <i class="fas fa-check-circle"></i><?php echo $compact ? '' : ' Arrived'; ?>
                        </button>
                        <button class="btn btn-warning btn-sm" style="color:#fff;" onclick="setStatus(<?php echo $id; ?>,'no_show')" title="Not Arrived">
                            <i class="fas fa-user-slash"></i><?php echo $compact ? '' : ' Not Arrived'; ?>
                        </button>
                    <?php endif; ?>
                    <button class="btn btn-secondary btn-sm" onclick="setStatus(<?php echo $id; ?>,'cancelled')" title="Cancel">
                        <i class="fas fa-times"></i>
                    </button>

                <?php elseif ($s === 'arrived'): ?>

                    <?php if ($qCanConsult): ?>
                    <button class="btn btn-primary btn-sm" onclick="callPatient(<?php echo $id; ?>,<?php echo $pid; ?>)">
                        <i class="fas fa-stethoscope"></i><?php echo $compact ? '' : ' Call'; ?>
                    </button>
                    <?php else: ?>
                    <span style="color:#16a34a;font-size:11px;font-weight:600;"><i class="fas fa-user-check"></i> In Clinic</span>
                    <?php endif; ?>
                    <button class="btn btn-secondary btn-sm" onclick="setStatus(<?php echo $id; ?>,'cancelled')" title="Cancel">
                        <i class="fas fa-times"></i>
                    </button>

                <?php elseif ($s === 'in_consultation'): ?>

                    <?php if ($qCanConsult): ?>
                    <button class="btn btn-success btn-sm" onclick="finishConsult(<?php echo $id; ?>)">
                        <i class="fas fa-check"></i><?php echo $compact ? '' : ' Finish'; ?>
                    </button>
                    <?php if ($pid): ?>
                    <a href="/patient/<?php echo $pid; ?>" class="btn btn-secondary btn-sm" title="View Patient">
                        <i class="fas fa-user"></i>
                    </a>
                    <?php endif; ?>
                    <?php else: ?>
                    <span style="color:#2563eb;font-size:11px;font-weight:600;"><i class="fas fa-stethoscope"></i> With Doctor</span>
                    <?php endif; ?>

                <?php elseif ($s === 'no_show'): ?>

                    <button class="btn btn-outline-primary btn-sm" onclick="setStatus(<?php echo $id; ?>,'arrived')" title="Patient came late">
                        <i class="fas fa-undo"></i><?php echo $compact ? '' : ' Arrived Late'; ?>
                    </button>

                <?php elseif ($s === 'completed'): ?>

                    <span style="color:#9ca3af;font-size:11px;"><i class="fas fa-check-double"></i> Done</span>
                    <?php if ($qRole === 'reception' && !empty($row['report_id'])): ?>
                    <a href="/invoice/<?php echo (int)$row['report_id']; ?>" target="_blank" class="btn btn-primary btn-sm" title="Print Invoice">
                        <i class="fas fa-print"></i> Invoice
                    </a>
                    <?php endif; ?>
                    <?php if ($pid && $qCanConsult && !$compact): ?>
                    <a href="/patient/<?php echo $pid; ?>" class="btn btn-secondary btn-sm" title="View Patient">
                        <i class="fas fa-user"></i>
                    </a>
                    <?php endif; ?>

                <?php else: ?>
                    <span style="color:#9ca3af;font-size:11px;"><?php echo htmlspecialchars(ucfirst(str_replace('_',' ',$s))); ?></span>
                <?php endif; ?>

            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php endif; ?>

<?php
// JS output once per page
global $__queueJsLoaded;
if (empty($__queueJsLoaded)):
    $__queueJsLoaded = true;
?>
<!-- Payment modal (shared, rendered once) -->
<div class="pay-modal-overlay" id="payModalOverlay">
    <div class="pay-modal" role="dialog" aria-modal="true">
        <div class="pay-modal-head">
            <h3><i class="fas fa-rupee-sign"></i> Record Payment</h3>
            <button type="button" class="pay-modal-close" onclick="closePayModal()" aria-label="Close">&times;</button>
        </div>
        <div class="pay-modal-body">
            <div class="pay-modal-patient" id="payModalPatient"></div>
            <div class="pay-modal-amt" id="payModalAmt">&#8377;0</div>
            <label class="pay-modal-label" for="payModalType">Payment method</label>
            <select id="payModalType" class="pay-modal-select">
                <option value="cash">Cash</option>
                <option value="online">Online</option>
            </select>
        </div>
        <div class="pay-modal-foot">
            <button type="button" class="btn btn-secondary btn-sm" onclick="closePayModal()">Cancel</button>
            <button type="button" class="btn btn-success btn-sm" id="payModalSave" onclick="submitPayModal()">
                <i class="fas fa-check"></i> Mark Paid
            </button>
        </div>
    </div>
</div>
<style>
.queue-row[data-status="arrived"]         { background:#f0fdf4; }
.queue-row[data-status="in_consultation"] { background:#eff6ff; }
.queue-row[data-status="completed"]       { opacity:.75; }
.queue-row[data-status="no_show"]         { opacity:.6; }
.queue-row[data-status="cancelled"]       { opacity:.5; }
.queue-row.row-late                       { background:#fff7ed !important; border-left:3px solid #f97316; }
.badge-late {
    display:inline-block; font-size:9px; font-weight:700;
    background:#fff7ed; color:#c2410c; border:1px solid #fed7aa;
    border-radius:4px; padding:1px 5px; letter-spacing:.3px;
    text-transform:uppercase; white-space:nowrap;
}
.status-btns .btn { padding:3px 8px; font-size:11px; }
.pay-badge { display:inline-block; font-size:10px; font-weight:700; padding:1px 7px; border-radius:10px; text-transform:uppercase; letter-spacing:.3px; }
.pay-badge.pay-cash      { background:#ecfdf5; color:#047857; }
.pay-badge.pay-online    { background:#eff6ff; color:#1d4ed8; }
.pay-badge.pay-paid      { background:#ecfdf5; color:#047857; }
.pay-badge.pay-remaining { background:#fef2f2; color:#b91c1c; }
.pay-badge-btn { background:none; border:none; padding:0; cursor:pointer; }
.pay-modal-overlay {
    display:none; position:fixed; inset:0; z-index:1000;
    background:rgba(17,24,39,.5); align-items:center; justify-content:center;
}
.pay-modal-overlay.open { display:flex; }
.pay-modal {
    background:#fff; border-radius:12px; box-shadow:0 20px 60px rgba(0,0,0,.25);
    width:340px; max-width:92vw; padding:0; overflow:hidden;
}
.pay-modal-head {
    display:flex; align-items:center; justify-content:space-between;
    padding:14px 18px; border-bottom:1px solid #f0f0f0;
}
.pay-modal-head h3 { margin:0; font-size:15px; font-weight:700; color:#111827; }
.pay-modal-close { background:none; border:none; font-size:20px; line-height:1; color:#9ca3af; cursor:pointer; }
.pay-modal-body { padding:18px; }
.pay-modal-patient { font-size:13px; color:#6b7280; margin-bottom:6px; }
.pay-modal-amt { font-size:22px; font-weight:800; color:#111827; margin-bottom:14px; }
.pay-modal-label { display:block; font-size:12px; color:#6b7280; margin-bottom:5px; }
.pay-modal-select { width:100%; padding:9px 10px; font-size:14px; border:1px solid #d1d5db; border-radius:8px; }
.pay-modal-foot { display:flex; gap:8px; justify-content:flex-end; padding:14px 18px; border-top:1px solid #f0f0f0; }
</style>
<script>
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
var __payReportId = null;
function openPayModal(reportId, amount, payType, patientName) {
    __payReportId = reportId;
    document.getElementById('payModalAmt').innerHTML = '&#8377;' + Number(amount).toLocaleString('en-IN');
    document.getElementById('payModalPatient').textContent = patientName || '';
    document.getElementById('payModalType').value = (payType === 'online') ? 'online' : 'cash';
    document.getElementById('payModalOverlay').classList.add('open');
}
function closePayModal() {
    __payReportId = null;
    document.getElementById('payModalOverlay').classList.remove('open');
}
function submitPayModal() {
    if (!__payReportId) return;
    var payType = document.getElementById('payModalType').value;
    var btn = document.getElementById('payModalSave');
    btn.disabled = true;
    fetch('/api/report/' + __payReportId + '/payment', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'payment_status=paid&payment_type=' + encodeURIComponent(payType)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            btn.disabled = false;
            alert('Error: ' + (data.message || 'Failed to update payment status'));
        }
    })
    .catch(e => { btn.disabled = false; alert('Error: ' + e.message); });
}
// Close on overlay click / Esc
document.addEventListener('click', function(e) {
    if (e.target && e.target.id === 'payModalOverlay') closePayModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closePayModal();
});
</script>
<?php endif; ?>
