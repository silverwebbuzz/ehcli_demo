<?php
/**
 * Expense Report — spend trend, category breakdown, and detail table.
 * Expects $reportData from ExpenseController::report().
 */
$page_title  = 'Expense Report';
$reportTitle = 'Clinic Expenses';
$reportIcon  = 'fas fa-wallet';
$reportBase  = '/reports/expenses';
ob_start();

$summary    = $reportData['summary']    ?? [];
$byCategory = $reportData['byCategory'] ?? [];
$byDay      = $reportData['byDay']      ?? [];
$byWeek     = $reportData['byWeek']     ?? [];
$byMonth    = $reportData['byMonth']    ?? [];
$recent     = $reportData['recent']     ?? [];
$period     = $reportData['period']     ?? 'month';
$year       = $reportData['year']       ?? date('Y');

$showYearPicker = true;
require __DIR__ . '/_header.php';
// $defaultG, $availableG, $periodLabel set by _header.php

function eFmt($n) { return '₹' . number_format((float)$n, 0); }

// Trend datasets
$dayLabels  = json_encode(array_column($byDay, 'day'));
$dayVals    = json_encode(array_map(fn($r)=>(int)$r['total'], $byDay));
$weekLabels = json_encode(array_map(fn($r)=>date('d M', strtotime($r['week_start'])), $byWeek));
$weekVals   = json_encode(array_map(fn($r)=>(int)$r['total'], $byWeek));

$allMonths   = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$monthTotals = array_fill(0, 12, 0);
foreach ($byMonth as $r) { $monthTotals[(int)explode('-', $r['month'])[1]-1] = (int)$r['total']; }
$monthLabels = json_encode($allMonths);
$monthVals   = json_encode($monthTotals);

// Category doughnut
$catLabels = json_encode(array_column($byCategory, 'category'));
$catVals   = json_encode(array_map(fn($r)=>(int)$r['total'], $byCategory));
$grandTotal = (float)($summary['total'] ?? 0);
?>

<!-- Summary cards -->
<div class="report-grid-4" style="margin-bottom:16px;">
    <div class="stat-box">
        <div class="sv" style="color:#dc2626;"><?php echo eFmt($summary['total'] ?? 0); ?></div>
        <div class="sl">Total Spend</div>
    </div>
    <div class="stat-box">
        <div class="sv" style="color:#2563eb;"><?php echo number_format((int)($summary['entries'] ?? 0)); ?></div>
        <div class="sl">No. of Entries</div>
    </div>
    <div class="stat-box">
        <div class="sv" style="color:#d97706;"><?php echo eFmt($summary['avg_amount'] ?? 0); ?></div>
        <div class="sl">Avg / Entry</div>
    </div>
    <div class="stat-box">
        <div class="sv" style="color:#7c3aed;"><?php echo eFmt($summary['max_amount'] ?? 0); ?></div>
        <div class="sl">Largest Expense</div>
    </div>
</div>

<!-- Spend trend chart with toggle -->
<div class="chart-card">
    <h6><i class="fas fa-chart-bar"></i> Spend Trend <span id="spendPills"></span></h6>
    <span class="chart-period"><?php echo $periodLabel; ?></span>
    <canvas id="chartSpend" height="80"></canvas>
</div>
<script>
(function(){
    const ctx = document.getElementById('chartSpend').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: { labels: [], datasets: [{
            label: 'Spend (₹)', data: [],
            backgroundColor: CHART_COLORS.red + 'bb', borderColor: CHART_COLORS.red,
            borderWidth: 1, borderRadius: 4,
        }]},
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero:true, grace:'15%', ticks:{ callback: v => '₹'+v.toLocaleString() } } }
        },
        plugins: [topLabelPlugin]
    });
    const datasets = {
        day:   { labels: <?php echo $dayLabels; ?>,   values: <?php echo $dayVals; ?> },
        week:  { labels: <?php echo $weekLabels; ?>,  values: <?php echo $weekVals; ?> },
        month: { labels: <?php echo $monthLabels; ?>, values: <?php echo $monthVals; ?> },
    };
    document.getElementById('spendPills').outerHTML = buildTogglePills(<?php echo json_encode($availableG); ?>, '<?php echo $defaultG; ?>');
    chartToggle('chartSpend', datasets, '<?php echo $defaultG; ?>');
})();
</script>

<div class="report-grid-2">

    <!-- Category breakdown doughnut -->
    <div class="chart-card">
        <h6><i class="fas fa-chart-pie"></i> By Category</h6>
        <span class="chart-period"><?php echo $periodLabel; ?></span>
        <?php if (empty($byCategory)): ?>
            <p style="color:#9ca3af;text-align:center;padding:30px;">No expenses in this period.</p>
        <?php else: ?>
        <canvas id="chartCategory" height="150"></canvas>
        <script>
        (function(){
            const palette = [CHART_COLORS.red, CHART_COLORS.primary, CHART_COLORS.yellow, CHART_COLORS.green,
                             CHART_COLORS.purple, CHART_COLORS.cyan, '#ec4899', '#f97316', '#14b8a6', CHART_COLORS.gray];
            new Chart(document.getElementById('chartCategory'), {
                type: 'doughnut',
                data: { labels: <?php echo $catLabels; ?>,
                    datasets: [{ data: <?php echo $catVals; ?>, backgroundColor: palette, borderWidth: 1, borderColor: '#fff' }] },
                options: { responsive:true, plugins:{ legend:{ position:'right', labels:{ boxWidth:12, font:{size:11} } },
                    tooltip:{ callbacks:{ label: c => c.label + ': ₹' + c.parsed.toLocaleString() } } } }
            });
        })();
        </script>
        <?php endif; ?>
    </div>

    <!-- Category table with share % -->
    <?php if (!empty($byCategory)): ?>
    <div class="chart-card" style="overflow-x:auto;">
        <h6><i class="fas fa-table"></i> Category Breakdown</h6>
        <span class="chart-period"><?php echo $periodLabel; ?></span>
        <table class="table" style="margin:0;font-size:12px;">
            <thead><tr><th>Category</th><th style="text-align:right;">Amount</th><th style="text-align:right;">Share</th><th style="text-align:right;">Entries</th></tr></thead>
            <tbody>
            <?php foreach ($byCategory as $c):
                $share = $grandTotal > 0 ? round((float)$c['total'] * 100 / $grandTotal) : 0; ?>
                <tr>
                    <td><?php echo htmlspecialchars($c['category']); ?></td>
                    <td style="text-align:right;font-weight:600;color:#dc2626;"><?php echo eFmt($c['total']); ?></td>
                    <td style="text-align:right;color:#6b7280;"><?php echo $share; ?>%</td>
                    <td style="text-align:right;"><?php echo (int)$c['entries']; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="border-top:2px solid #e5e7eb;font-weight:700;">
                    <td>Total</td>
                    <td style="text-align:right;color:#dc2626;"><?php echo eFmt($grandTotal); ?></td>
                    <td style="text-align:right;">100%</td>
                    <td style="text-align:right;"><?php echo (int)($summary['entries'] ?? 0); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>

</div>

<!-- Detail list -->
<div class="chart-card" style="overflow-x:auto;">
    <h6><i class="fas fa-receipt"></i> Expense Detail <span style="font-weight:400;color:#9ca3af;">(latest 100)</span></h6>
    <span class="chart-period"><?php echo $periodLabel; ?></span>
    <?php if (empty($recent)): ?>
        <p style="color:#9ca3af;text-align:center;padding:20px;">No expenses recorded in this period.</p>
    <?php else: ?>
    <table class="table" style="margin:0;font-size:12px;">
        <thead><tr>
            <th>Date</th><th>Category</th><th>Vendor / Note</th><th>Mode</th><th style="text-align:right;">Amount</th>
        </tr></thead>
        <tbody>
        <?php foreach ($recent as $e): ?>
            <tr>
                <td style="white-space:nowrap;"><?php echo date('d M Y', strtotime($e['expense_date'])); ?></td>
                <td><?php echo htmlspecialchars($e['category']); ?></td>
                <td style="color:#4b5563;"><?php echo htmlspecialchars(trim(($e['vendor'] ?? '') . ' ' . ($e['description'] ?? ''))); ?></td>
                <td style="text-transform:uppercase;color:#6b7280;"><?php echo htmlspecialchars($e['payment_mode']); ?></td>
                <td style="text-align:right;font-weight:600;">₹<?php echo number_format((float)$e['amount'], 2); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div style="margin-top:14px;">
    <a href="/expenses" class="btn btn-primary btn-sm"><i class="fas fa-plus-circle"></i> Manage Expenses</a>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
