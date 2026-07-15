<?php
$page_title  = 'GST Report';
$reportTitle = 'GST Report';
$reportIcon  = 'fas fa-file-invoice-dollar';
$reportBase  = '/reports/gst';
ob_start();

$summary   = $reportData['summary'] ?? [];
$byDay     = $reportData['byDay']   ?? [];
$byWeek    = $reportData['byWeek']  ?? [];
$byMonth   = $reportData['byMonth'] ?? [];
$detail    = $reportData['detail']  ?? [];
$period    = $reportData['period']  ?? 'week';
$year      = $reportData['year']    ?? date('Y');
$rate      = $reportData['rate']    ?? 18;
$settingOn = $reportData['settingOn'] ?? false;
$gstNumber = $reportData['gstNumber'] ?? '';

$showYearPicker = true; // enables inline year select inside "This Year" button
require __DIR__ . '/_header.php';
// $defaultG, $availableG, $periodLabel set by _header.php

function gFmt($n) { return '₹' . number_format((float)$n, 2); }
function gFmtDate($v) { $ts = strtotime($v); return $ts ? date('d M Y', $ts) : $v; }

// ── Build JS datasets (Base + GST, stacked) ──────────────────────────────────
$dayLabels  = json_encode(array_column($byDay, 'day'));
$dayBase    = json_encode(array_map(fn($r)=>round((float)$r['base'],2), $byDay));
$dayGst     = json_encode(array_map(fn($r)=>round((float)$r['gst'],2),  $byDay));

$weekLabels = json_encode(array_map(fn($r)=>date('d M', strtotime($r['week_start'])), $byWeek));
$weekBase   = json_encode(array_map(fn($r)=>round((float)$r['base'],2), $byWeek));
$weekGst    = json_encode(array_map(fn($r)=>round((float)$r['gst'],2),  $byWeek));

$allMonths  = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$monthBaseA = array_fill(0, 12, 0);
$monthGstA  = array_fill(0, 12, 0);
foreach ($byMonth as $r) {
    $mi = (int)explode('-', $r['month'])[1] - 1;
    $monthBaseA[$mi] = round((float)$r['base'], 2);
    $monthGstA[$mi]  = round((float)$r['gst'], 2);
}
$monthLabels = json_encode($allMonths);
$monthBase   = json_encode($monthBaseA);
$monthGst    = json_encode($monthGstA);
?>

<?php if (!$settingOn): ?>
<div style="background:#fef3c7;border:1px solid #fde68a;color:#92400e;border-radius:8px;padding:10px 14px;font-size:12px;margin-bottom:16px;">
    <i class="fas fa-circle-info"></i> GST is currently <strong>disabled</strong> in
    <a href="/clinic-settings" style="color:#92400e;text-decoration:underline;">Clinic Settings</a>.
    Figures below only include visits where GST was explicitly enabled on the visit page.
</div>
<?php endif; ?>

<!-- Summary cards -->
<div class="report-grid-4" style="margin-bottom:16px;">
    <div class="stat-box">
        <div class="sv" style="color:#2563eb;"><?php echo gFmt($summary['base'] ?? 0); ?></div>
        <div class="sl">Taxable Base</div>
    </div>
    <div class="stat-box">
        <div class="sv" style="color:#16a34a;"><?php echo gFmt($summary['gst'] ?? 0); ?></div>
        <div class="sl">GST Collected (<?php echo $rate; ?>%)</div>
    </div>
    <div class="stat-box">
        <div class="sv" style="color:#7c3aed;"><?php echo gFmt($summary['gross'] ?? 0); ?></div>
        <div class="sl">Gross (Base + GST)</div>
    </div>
    <div class="stat-box">
        <div class="sv" style="color:#d97706;"><?php echo number_format((int)($summary['visits'] ?? 0)); ?></div>
        <div class="sl">Taxable Visits</div>
    </div>
</div>

<?php if ($gstNumber !== ''): ?>
<div style="font-size:12px;color:#6b7280;margin-bottom:16px;">
    <i class="fas fa-id-card" style="margin-right:4px;"></i>
    <strong>GSTIN:</strong> <?php echo htmlspecialchars($gstNumber); ?>
</div>
<?php endif; ?>

<!-- GST trend chart with toggle (Base + GST stacked) -->
<div class="chart-card">
    <h6><i class="fas fa-chart-bar"></i> GST Trend <span id="gstPills"></span></h6>
    <span class="chart-period"><?php echo $periodLabel; ?></span>
    <canvas id="chartGst" height="80"></canvas>
</div>
<script>
(function(){
    const ctx = document.getElementById('chartGst').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [
                { label:'Taxable Base (₹)', data:[], backgroundColor: CHART_COLORS.primary + 'bb',
                  borderColor: CHART_COLORS.primary, borderWidth:1, borderRadius:4, stack:'s' },
                { label:'GST (₹)', data:[], backgroundColor: CHART_COLORS.green + 'cc',
                  borderColor: CHART_COLORS.green, borderWidth:1, borderRadius:4, stack:'s' },
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: true, position:'bottom' } },
            scales: {
                x: { stacked:true },
                y: { stacked:true, beginAtZero:true, grace:'15%', ticks:{ callback: v => '₹'+v.toLocaleString() } }
            }
        },
        plugins: [topLabelPlugin]
    });

    const datasets = {
        day:   { labels: <?php echo $dayLabels; ?>,   values: [<?php echo $dayBase; ?>,   <?php echo $dayGst; ?>] },
        week:  { labels: <?php echo $weekLabels; ?>,  values: [<?php echo $weekBase; ?>,  <?php echo $weekGst; ?>] },
        month: { labels: <?php echo $monthLabels; ?>, values: [<?php echo $monthBase; ?>, <?php echo $monthGst; ?>] },
    };

    document.getElementById('gstPills').outerHTML = buildTogglePills(<?php echo json_encode($availableG); ?>, '<?php echo $defaultG; ?>');
    chartToggle('chartGst', datasets, '<?php echo $defaultG; ?>');
})();
</script>

<!-- Per-visit GST detail (for filing) -->
<div class="chart-card" style="overflow-x:auto;">
    <h6><i class="fas fa-table"></i> GST Line Items</h6>
    <span class="chart-period"><?php echo $periodLabel; ?> — <?php echo count($detail); ?> visit(s)</span>
    <?php if (empty($detail)): ?>
        <div style="color:#9ca3af;font-size:13px;padding:12px 0;">No taxable visits in this period.</div>
    <?php else: ?>
        <table class="table" style="margin:0;font-size:12px;white-space:nowrap;">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Invoice</th>
                    <th>Patient</th>
                    <th style="text-align:right;">Base</th>
                    <th style="text-align:right;">GST (<?php echo $rate; ?>%)</th>
                    <th style="text-align:right;">Gross</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($detail as $d):
                $invNo = 'INV-' . str_pad((int)$d['id'], 5, '0', STR_PAD_LEFT);
                $gross = (float)$d['base'] + (float)$d['gst'];
            ?>
                <tr>
                    <td><?php echo gFmtDate($d['date']); ?></td>
                    <td style="font-family:monospace;color:#6b7280;"><?php echo $invNo; ?></td>
                    <td><?php echo htmlspecialchars(trim($d['patient_name']) ?: 'Patient'); ?></td>
                    <td style="text-align:right;"><?php echo gFmt($d['base']); ?></td>
                    <td style="text-align:right;color:#16a34a;font-weight:600;"><?php echo gFmt($d['gst']); ?></td>
                    <td style="text-align:right;font-weight:600;"><?php echo gFmt($gross); ?></td>
                    <td style="text-align:right;">
                        <a href="/invoice/<?php echo (int)$d['id']; ?>" target="_blank"
                           title="Open invoice" style="color:var(--primary);"><i class="fas fa-file-invoice"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="border-top:2px solid #111;font-weight:700;">
                    <td colspan="3" style="text-align:right;">Totals</td>
                    <td style="text-align:right;"><?php echo gFmt($summary['base'] ?? 0); ?></td>
                    <td style="text-align:right;color:#16a34a;"><?php echo gFmt($summary['gst'] ?? 0); ?></td>
                    <td style="text-align:right;"><?php echo gFmt($summary['gross'] ?? 0); ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
