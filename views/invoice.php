<?php
/**
 * Invoice view — standalone page (no layout.php wrapper)
 * Variables provided by index.php:
 *   $report   — progress_report row
 *   $patient  — patients row
 *   $s        — settings key=>value array
 */

// Guard
if (empty($report) || empty($patient)) {
    http_response_code(404);
    echo '<p style="font-family:sans-serif;padding:40px;">Invoice not found.</p>';
    exit;
}

// ── Helpers ─────────────────────────────────────────────────────────────────
function invFmt($v, $fb = '—') {
    $v = is_string($v) ? trim($v) : $v;
    return ($v === null || $v === '' || $v === '0000-00-00') ? $fb : $v;
}
function invFmtDate($v) {
    if (!$v || $v === '0000-00-00') return '—';
    $ts = strtotime($v);
    return $ts ? date('d M Y', $ts) : $v;
}
function invName($f, $l) {
    $n = trim(trim($f ?? '') . ' ' . trim($l ?? ''));
    return $n === '' ? 'Patient' : $n;
}

// ── Data ─────────────────────────────────────────────────────────────────────
$reportId   = (int)$report['id'];
$invoiceNo  = 'INV-' . str_pad($reportId, 5, '0', STR_PAD_LEFT);
$visitDate  = invFmtDate($report['date'] ?? '');
$medicines  = array_filter(array_map('trim', explode(',', $report['medicins'] ?? '')));
$baseAmt    = (float)($report['amt'] ?? 0);

// Payment
$payType    = ($report['payment_type'] ?? 'cash') === 'online' ? 'Online' : 'Cash';
$isPaid     = ($report['payment_status'] ?? 'paid') !== 'remaining';
$payLabel   = $isPaid ? 'PAID' : 'PAYMENT DUE';

// GST
$gstEnabled = ($s['inv_gst_enabled'] ?? '0') === '1';
$gstRate    = $gstEnabled ? (float)($s['inv_gst_rate'] ?? 18) : 0;
$gstAmt     = $gstEnabled ? round($baseAmt * $gstRate / 100, 2) : 0;
$totalAmt   = $baseAmt + $gstAmt;

// Clinic / Doctor info
$clinicName    = invFmt($s['inv_doctor_name'] ?? $s['clinic_name'] ?? '', 'Dr. Feelgood');
$qualification = invFmt($s['inv_qualification'] ?? '', '');
$clinicAddress = invFmt($s['inv_address'] ?? '', '');
$clinicPhone   = invFmt($s['inv_phone'] ?? $s['clinic_phone'] ?? '', '');
$clinicEmail   = invFmt($s['inv_email'] ?? '', '');
$showPan       = ($s['inv_show_pan'] ?? '0') === '1';
$pan           = invFmt($s['inv_pan'] ?? '', '');
$gstNumber     = invFmt($s['inv_gst_number'] ?? '', '');

// Patient
$patientName    = invName($patient['fname'] ?? '', $patient['lname'] ?? '');
$patientId      = $patient['patient_id'] ?? $patient['id'];
$patientAge     = invFmt($patient['age'] ?? null, '');
$patientGender  = match($patient['gender'] ?? '') { 'M' => 'Male', 'F' => 'Female', default => '' };
$patientContact = invFmt($patient['contact_no'] ?? null, '');
// Address: legacy free-text line + structured city/state/zip when present
$addrBits = array_filter([
    trim($patient['address'] ?? ''),
    trim($patient['city'] ?? ''),
    trim($patient['state'] ?? ''),
    trim($patient['zip'] ?? ''),
], fn($v) => $v !== '' && $v !== '0');
$patientAddress = invFmt(implode(', ', $addrBits), '');

// Notes
$visitNotes = trim($report['notes'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($invoiceNo); ?> — Invoice</title>
<style>
/* ── Reset ── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Arial', sans-serif;
    font-size: 13px;
    color: #111;
    background: #d1d5db;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* ── Screen print bar ── */
.print-bar {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    padding: 16px 0 10px;
    background: #d1d5db;
}
.btn-print {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 30px;
    background: #111; color: #fff;
    border: none; font-size: 13px; font-weight: 700;
    cursor: pointer; letter-spacing: .3px;
}
.btn-print:hover { background: #333; }
.btn-close {
    padding: 9px 20px;
    background: #fff; color: #111;
    border: 1px solid #999; font-size: 13px; font-weight: 600;
    cursor: pointer;
}
.btn-close:hover { background: #f3f4f6; }

/* ── A4 page wrapper ── */
.inv-page {
    width: 794px;
    margin: 0 auto 30px;
    background: #fff;
    padding: 48px 52px 52px;
    box-shadow: 0 3px 16px rgba(0,0,0,.18);
}

/* ══ HEADER ══ */
.inv-header {
    display: table;
    width: 100%;
    border-bottom: 2.5px solid #111;
    padding-bottom: 18px;
    margin-bottom: 22px;
}
.inv-header-left  { display: table-cell; vertical-align: top; width: 65%; }
.inv-header-right { display: table-cell; vertical-align: top; text-align: right; width: 35%; }

.inv-clinic-name  { font-size: 22px; font-weight: 800; color: #000; line-height: 1.15; }
.inv-qual         { font-size: 12px; color: #444; margin-top: 3px; font-style: italic; }

.inv-addr {
    font-size: 11.5px; color: #333;
    margin-top: 10px; line-height: 1.8;
    max-width: 380px;
}
.inv-addr .addr-line { display: block; }
.inv-contact {
    font-size: 11.5px; color: #333;
    margin-top: 6px; line-height: 1.9;
}

.inv-number  { font-size: 18px; font-weight: 800; color: #000; letter-spacing: .5px; }
.inv-date    { font-size: 12px; color: #333; margin-top: 5px; line-height: 1.8; }
.inv-date strong { color: #000; }

.inv-paid {
    display: inline-block;
    margin-top: 10px;
    border: 2px solid #000;
    padding: 2px 12px;
    font-size: 11px; font-weight: 800;
    letter-spacing: 1.5px;
    color: #000;
}
.inv-paid.inv-due {
    border-color: #b91c1c;
    color: #b91c1c;
}

/* ══ PAN / GST BAR ══ */
.inv-reg-bar {
    background: #f3f4f6;
    border: 1px solid #ddd;
    padding: 6px 14px;
    margin-bottom: 20px;
    font-size: 11px;
    color: #333;
    display: flex; gap: 30px;
}
.inv-reg-bar strong { color: #000; }

/* ══ BILLED TO / VISIT DETAILS ══ */
.inv-info-row {
    display: table;
    width: 100%;
    border: 1px solid #ccc;
    margin-bottom: 24px;
}
.inv-info-cell {
    display: table-cell;
    width: 50%;
    padding: 12px 16px;
    vertical-align: top;
}
.inv-info-cell + .inv-info-cell {
    border-left: 1px solid #ccc;
}
.inv-cell-title {
    font-size: 9.5px; font-weight: 800;
    text-transform: uppercase; letter-spacing: .8px;
    color: #666;
    padding-bottom: 7px;
    margin-bottom: 8px;
    border-bottom: 1px solid #e5e7eb;
}
.inv-cell-name {
    font-size: 15px; font-weight: 800;
    color: #000; margin-bottom: 6px;
    text-transform: uppercase; letter-spacing: .3px;
}
.inv-cell-detail {
    font-size: 11.5px; color: #333; line-height: 1.9;
}
.inv-cell-detail strong { color: #111; }

/* ══ LINE ITEMS TABLE ══ */
.inv-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
    margin-bottom: 0;
}
.inv-table thead tr {
    background: #111;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.inv-table thead th {
    padding: 9px 14px;
    color: #fff;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    text-align: left;
}
.inv-table thead th:last-child { text-align: right; }

.inv-table tbody td {
    padding: 14px 14px;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: top;
}
.inv-table tbody tr:last-child td { border-bottom: 2px solid #111; }
.inv-table tbody td:last-child {
    text-align: right;
    font-weight: 700;
    font-size: 13px;
    white-space: nowrap;
    vertical-align: middle;
}

.item-title   { font-size: 13px; font-weight: 700; color: #000; }
.item-sub     { font-size: 11px; color: #6b7280; margin-top: 3px; }
.item-meds    { font-size: 11.5px; color: #222; margin-top: 7px; line-height: 1.7; }
.item-meds-label { font-size: 10px; text-transform: uppercase; letter-spacing: .5px; color: #9ca3af; font-weight: 700; margin-bottom: 2px; }

/* ══ TOTALS ══ */
.inv-totals {
    width: 100%;
    border-collapse: collapse;
    margin-top: 0;
    margin-bottom: 24px;
}
.inv-totals td {
    padding: 6px 14px;
    font-size: 12px;
}
.inv-totals td:first-child { text-align: right; color: #6b7280; width: 85%; }
.inv-totals td:last-child  { text-align: right; white-space: nowrap; width: 15%; }
.totals-gst td:first-child { color: #6b7280; }
.totals-total td {
    font-size: 15px !important;
    font-weight: 800;
    color: #000 !important;
    border-top: 2.5px solid #111;
    padding-top: 10px !important;
    padding-bottom: 10px !important;
}

/* ══ NOTES ══ */
.inv-notes {
    background: #fafafa;
    border: 1px solid #e5e7eb;
    border-left: 3px solid #111;
    padding: 10px 14px;
    margin-bottom: 24px;
    font-size: 11.5px;
    color: #374151;
    line-height: 1.7;
}
.inv-notes strong { color: #000; font-size: 10px; text-transform: uppercase; letter-spacing: .5px; display: block; margin-bottom: 3px; }

/* ══ FOOTER ══ */
.inv-footer {
    display: table;
    width: 100%;
    border-top: 1px solid #ccc;
    padding-top: 14px;
    margin-top: 30px;
}
.inv-footer-note {
    display: table-cell;
    vertical-align: bottom;
    font-size: 10px;
    color: #6b7280;
    line-height: 1.7;
}
.inv-footer-sig {
    display: table-cell;
    vertical-align: bottom;
    text-align: center;
    width: 180px;
}
.inv-sig-line  { border-top: 1px solid #111; width: 160px; margin: 0 auto 5px; }
.inv-sig-label { font-size: 10.5px; color: #222; line-height: 1.6; }
.inv-sig-label.bold { font-weight: 700; font-size: 11px; }

/* ══ PRINT ══ */
@media print {
    body { background: #fff; }
    .print-bar { display: none !important; }
    .inv-page {
        width: 100%;
        margin: 0;
        padding: 20px 26px 26px;
        box-shadow: none;
    }
}
</style>
</head>
<body>

<!-- ── Screen toolbar ── -->
<div class="print-bar">
    <button class="btn-print" onclick="window.print()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Print / Save PDF
    </button>
    <button class="btn-close" onclick="window.close()">&#x2715; Close</button>
</div>

<!-- ── INVOICE PAGE ── -->
<div class="inv-page">

    <!-- ═══ HEADER ═══ -->
    <div class="inv-header">
        <div class="inv-header-left">
            <div class="inv-clinic-name"><?php echo htmlspecialchars($clinicName); ?></div>
            <?php if ($qualification !== '—' && $qualification !== ''): ?>
            <div class="inv-qual"><?php echo htmlspecialchars($qualification); ?></div>
            <?php endif; ?>

            <?php if ($clinicAddress !== '—' && $clinicAddress !== ''): ?>
            <div class="inv-addr">
                <?php
                // Break address into lines — split on comma for natural line breaks
                $addrParts = array_filter(array_map('trim', explode(',', $clinicAddress)));
                // Group into chunks of ~2 parts per line for readability
                $lines = [];
                $chunk = [];
                foreach ($addrParts as $i => $part) {
                    $chunk[] = $part;
                    if (count($chunk) === 2 || $i === count($addrParts) - 1) {
                        $lines[] = implode(', ', $chunk);
                        $chunk = [];
                    }
                }
                foreach ($lines as $line):
                ?>
                <span class="addr-line"><?php echo htmlspecialchars($line); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="inv-contact">
                <?php if ($clinicPhone !== '—' && $clinicPhone !== ''): ?>
                <span>&#9742;&nbsp;<?php echo htmlspecialchars($clinicPhone); ?></span><br>
                <?php endif; ?>
                <?php if ($clinicEmail !== '—' && $clinicEmail !== ''): ?>
                <span>&#9993;&nbsp;<?php echo htmlspecialchars($clinicEmail); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="inv-header-right">
            <div class="inv-number"><?php echo htmlspecialchars($invoiceNo); ?></div>
            <div class="inv-date">
                <strong>Date:</strong> <?php echo $visitDate; ?>
            </div>
            <div class="inv-date">
                <strong>Payment Mode:</strong> <?php echo htmlspecialchars($payType); ?>
            </div>
            <div><span class="inv-paid <?php echo $isPaid ? '' : 'inv-due'; ?>"><?php echo htmlspecialchars($payLabel); ?></span></div>
        </div>
    </div>

    <!-- ═══ PAN / GST ═══ -->
    <?php if (($showPan && $pan !== '—' && $pan !== '') || ($gstEnabled && $gstNumber !== '—' && $gstNumber !== '')): ?>
    <div class="inv-reg-bar">
        <?php if ($showPan && $pan !== '—' && $pan !== ''): ?>
            <span><strong>PAN:</strong>&nbsp;<?php echo htmlspecialchars($pan); ?></span>
        <?php endif; ?>
        <?php if ($gstEnabled && $gstNumber !== '—' && $gstNumber !== ''): ?>
            <span><strong>GSTIN:</strong>&nbsp;<?php echo htmlspecialchars($gstNumber); ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ═══ BILLED TO + VISIT DETAILS ═══ -->
    <div class="inv-info-row">
        <div class="inv-info-cell">
            <div class="inv-cell-title">Billed To</div>
            <div class="inv-cell-name"><?php echo htmlspecialchars($patientName); ?></div>
            <div class="inv-cell-detail">
                <strong>Patient ID:</strong>&nbsp;<?php echo htmlspecialchars($patientId); ?><br>
                <?php if ($patientAge !== '' || $patientGender !== ''): ?>
                <?php if ($patientAge !== ''): ?><strong>Age:</strong>&nbsp;<?php echo htmlspecialchars($patientAge); ?> yrs<?php endif; ?>
                <?php if ($patientAge !== '' && $patientGender !== ''): ?>&nbsp;&nbsp;|&nbsp;&nbsp;<?php endif; ?>
                <?php if ($patientGender !== ''): ?><strong>Gender:</strong>&nbsp;<?php echo htmlspecialchars($patientGender); ?><?php endif; ?><br>
                <?php endif; ?>
                <?php if ($patientContact !== '—' && $patientContact !== ''): ?>
                <strong>Contact:</strong>&nbsp;<?php echo htmlspecialchars($patientContact); ?><br>
                <?php endif; ?>
                <?php if ($patientAddress !== '—' && $patientAddress !== ''): ?>
                <?php echo htmlspecialchars($patientAddress); ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="inv-info-cell">
            <div class="inv-cell-title">Visit Details</div>
            <div class="inv-cell-detail">
                <strong>Invoice No.:</strong>&nbsp;<?php echo htmlspecialchars($invoiceNo); ?><br>
                <strong>Visit Date:</strong>&nbsp;<?php echo $visitDate; ?><br>
                <strong>Report Ref:</strong>&nbsp;#<?php echo $reportId; ?>
            </div>
        </div>
    </div>

    <!-- ═══ LINE ITEMS ═══ -->
    <table class="inv-table">
        <thead>
            <tr>
                <th>Description</th>
                <th style="width:130px;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div class="item-title">Consultation<?php echo !empty($medicines) ? ' &amp; Medicines' : ''; ?></div>
                    <div class="item-sub">Visit on <?php echo $visitDate; ?></div>
                </td>
                <td>&#8377;<?php echo number_format($baseAmt, 2); ?></td>
            </tr>
        </tbody>
    </table>

    <!-- ═══ TOTALS ═══ -->
    <table class="inv-totals">
        <tr>
            <td>Subtotal</td>
            <td>&#8377;<?php echo number_format($baseAmt, 2); ?></td>
        </tr>
        <?php if ($gstEnabled && $gstAmt > 0): ?>
        <tr class="totals-gst">
            <td>GST (<?php echo $gstRate; ?>%)</td>
            <td>&#8377;<?php echo number_format($gstAmt, 2); ?></td>
        </tr>
        <?php endif; ?>
        <tr class="totals-total">
            <td>Total</td>
            <td>&#8377;<?php echo number_format($totalAmt, 2); ?></td>
        </tr>
        <tr>
            <td>Payment Mode</td>
            <td><?php echo htmlspecialchars($payType); ?></td>
        </tr>
        <tr>
            <td>Payment Status</td>
            <td style="font-weight:700;color:<?php echo $isPaid ? '#047857' : '#b91c1c'; ?>;"><?php echo $isPaid ? 'Paid' : 'Due'; ?></td>
        </tr>
    </table>

    <!-- ═══ NOTES ═══ -->
    <?php if ($visitNotes !== ''): ?>
    <div class="inv-notes">
        <strong>Clinical Notes</strong>
        <?php echo htmlspecialchars($visitNotes); ?>
    </div>
    <?php endif; ?>

    <!-- ═══ FOOTER ═══ -->
    <div class="inv-footer">
        <div class="inv-footer-note">
            Thank you for visiting <?php echo htmlspecialchars($clinicName); ?>.<br>
            This is a computer-generated invoice. No signature required.
        </div>
        <div class="inv-footer-sig">
            <div class="inv-sig-line"></div>
            <div class="inv-sig-label bold"><?php echo htmlspecialchars($clinicName); ?></div>
            <?php if ($qualification !== '—' && $qualification !== ''): ?>
            <div class="inv-sig-label"><?php echo htmlspecialchars($qualification); ?></div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /inv-page -->

</body>
</html>
