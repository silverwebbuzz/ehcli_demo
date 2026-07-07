<?php
$page_title  = 'Income Report';
$reportTitle = 'Income & Financial';
$reportIcon  = 'fas fa-rupee-sign';
$reportBase  = '/reports/income';
ob_start();

$summary = $reportData['summary'] ?? [];
$byDay   = $reportData['byDay']   ?? [];
$byWeek  = $reportData['byWeek']  ?? [];
$byMonth = $reportData['byMonth'] ?? [];
$period  = $reportData['period']  ?? 'week';
$year    = $reportData['year']    ?? date('Y');

$showYearPicker = true; // enables inline year select inside "This Year" button
require __DIR__ . '/_header.php';
// $defaultG and $availableG are set by _header.php

function rFmt($n) { return '₹' . number_format((float)$n, 0); }

// Build JS datasets
$dayLabels   = json_encode(array_column($byDay,   'day'));
$dayVals     = json_encode(array_map(fn($r)=>(int)$r['total'], $byDay));

$weekLabels  = json_encode(array_map(fn($r)=>date('d M', strtotime($r['week_start'])), $byWeek));
$weekVals    = json_encode(array_map(fn($r)=>(int)$r['total'], $byWeek));

$allMonths   = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$monthTotals = array_fill(0, 12, 0);
foreach ($byMonth as $r) { $monthTotals[(int)explode('-',$r['month'])[1]-1] = (int)$r['total']; }
$monthLabels = json_encode($allMonths);
$monthVals   = json_encode($monthTotals);
?>

<!-- Summary cards -->
<div class="report-grid-3" style="margin-bottom:16px;">
    <div class="stat-box">
        <div class="sv" style="color:#16a34a;"><?php echo rFmt($summary['total'] ?? 0); ?></div>
        <div class="sl">Total Revenue</div>
    </div>
    <div class="stat-box">
        <div class="sv" style="color:#2563eb;"><?php echo number_format((int)($summary['consultations'] ?? 0)); ?></div>
        <div class="sl">Consultations with Fee</div>
    </div>
    <div class="stat-box">
        <div class="sv" style="color:#d97706;"><?php echo rFmt($summary['avg_fee'] ?? 0); ?></div>
        <div class="sl">Avg Fee / Consultation</div>
    </div>
</div>

<!-- Revenue trend chart with toggle -->
<div class="chart-card">
    <h6><i class="fas fa-chart-bar"></i> Revenue Trend <span id="revenuePills"></span></h6>
    <span class="chart-period"><?php echo $periodLabel; ?></span>
    <canvas id="chartRevenue" height="80"></canvas>
</div>
<script>
(function(){
    const ctx = document.getElementById('chartRevenue').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Revenue (₹)',
                data: [],
                backgroundColor: CHART_COLORS.primary + 'bb',
                borderColor: CHART_COLORS.primary,
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
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

    document.getElementById('revenuePills').outerHTML = buildTogglePills(<?php echo json_encode($availableG); ?>, '<?php echo $defaultG; ?>');
    chartToggle('chartRevenue', datasets, '<?php echo $defaultG; ?>');
})();
</script>

<div class="report-grid-2">

    <!-- Weekly breakdown table -->
    <?php if (!empty($byWeek)): ?>
    <div class="chart-card" style="overflow-x:auto;">
        <h6><i class="fas fa-table"></i> Weekly Breakdown</h6>
        <span class="chart-period"><?php echo $periodLabel; ?></span>
        <table class="table" style="margin:0;font-size:12px;">
            <thead><tr><th>Week of</th><th style="text-align:right;">Revenue</th><th style="text-align:right;">Visits</th></tr></thead>
            <tbody>
            <?php foreach ($byWeek as $w): ?>
                <tr>
                    <td><?php echo date('d M', strtotime($w['week_start'])); ?></td>
                    <td style="text-align:right;font-weight:600;color:#16a34a;"><?php echo rFmt($w['total']); ?></td>
                    <td style="text-align:right;"><?php echo (int)$w['consultations']; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Monthly bar chart — full year, driven by year dropdown -->
    <div class="chart-card">
        <h6><i class="fas fa-calendar-alt"></i> Monthly Revenue</h6>
        <span class="chart-period">Year <?php echo $year; ?></span>
        <canvas id="chartMonth" height="120"></canvas>
        <script>
        (function(){
            new Chart(document.getElementById('chartMonth'), {
                type: 'bar',
                data: {
                    labels: <?php echo $monthLabels; ?>,
                    datasets: [{ label:'Revenue (₹)', data: <?php echo $monthVals; ?>,
                        backgroundColor:CHART_COLORS.green+'bb', borderColor:CHART_COLORS.green, borderWidth:1, borderRadius:4 }]
                },
                options: { responsive:true, plugins:{legend:{display:false}},
                    scales:{ y:{ beginAtZero:true, grace:'15%', ticks:{ callback: v=>'₹'+v.toLocaleString() } } } },
                plugins:[topLabelPlugin]
            });
        })();
        </script>
    </div>

</div>


<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
