<?php
$page_title  = 'Doctor Productivity';
$reportTitle = 'Doctor Productivity';
$reportIcon  = 'fas fa-stethoscope';
$reportBase  = '/reports/productivity';
ob_start();

$summary      = $reportData['summary']      ?? [];
$byDay        = $reportData['byDay']        ?? [];
$byWeek       = $reportData['byWeek']       ?? [];
$byMonth      = $reportData['byMonth']      ?? [];
$consultTrend = $reportData['consultTrend'] ?? [];
$busyDays     = $reportData['busyDays']     ?? [];
$period       = $reportData['period']       ?? 'week';
$year         = $reportData['year']         ?? date('Y');

require __DIR__ . '/_header.php';
// $defaultG and $availableG are set by _header.php

// Patients seen datasets
$dayLabels   = json_encode(array_column($byDay,  'day'));
$dayVals     = json_encode(array_map(fn($r)=>(int)$r['seen'], $byDay));

$weekLabels  = json_encode(array_map(fn($r)=>date('d M', strtotime($r['week_start'])), $byWeek));
$weekVals    = json_encode(array_map(fn($r)=>(int)$r['seen'], $byWeek));

$allMonths   = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$mVals       = array_fill(0,12,0);
foreach ($byMonth as $r) { $mVals[(int)explode('-',$r['month'])[1]-1] = (int)$r['seen']; }
$monthLabels = json_encode($allMonths);
$monthVals   = json_encode($mVals);
?>

<!-- Summary cards -->
<div class="report-grid-4" style="margin-bottom:16px;">
    <div class="stat-box">
        <div class="sv" style="color:#2563eb;"><?php echo number_format((int)($summary['total_seen'] ?? 0)); ?></div>
        <div class="sl">Patients Seen</div>
    </div>
    <div class="stat-box">
        <div class="sv" style="color:#16a34a;"><?php echo (int)($summary['working_days'] ?? 0); ?></div>
        <div class="sl">Working Days</div>
    </div>
    <div class="stat-box">
        <div class="sv" style="color:#d97706;"><?php echo $summary['avg_per_day'] ?? '—'; ?></div>
        <div class="sl">Avg Patients / Day</div>
    </div>
    <div class="stat-box">
        <div class="sv" style="color:#7c3aed;font-size:18px;">
            <?php echo $summary['peak_count'] ? $summary['peak_count'].' on '.date('d M', strtotime($summary['peak_day'])) : '—'; ?>
        </div>
        <div class="sl">Peak Day</div>
    </div>
</div>

<!-- Patients seen chart with toggle -->
<div class="chart-card">
    <h6><i class="fas fa-chart-bar"></i> Patients Seen <span id="seenPills"></span></h6>
    <span class="chart-period"><?php echo $periodLabel; ?></span>
    <canvas id="chartSeen" height="80"></canvas>
</div>
<script>
(function(){
    const chart = new Chart(document.getElementById('chartSeen'), {
        type: 'bar',
        data: { labels:[], datasets:[{
            label:'Patients Seen', data:[],
            backgroundColor:CHART_COLORS.primary+'bb',
            borderColor:CHART_COLORS.primary, borderWidth:1, borderRadius:4,
        }]},
        options:{ responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,grace:'15%',ticks:{precision:0}}} },
        plugins:[topLabelPlugin]
    });

    const datasets = {
        day:   { labels:<?php echo $dayLabels;   ?>, values:<?php echo $dayVals;   ?> },
        week:  { labels:<?php echo $weekLabels;  ?>, values:<?php echo $weekVals;  ?> },
        month: { labels:<?php echo $monthLabels; ?>, values:<?php echo $monthVals; ?> },
    };
    document.getElementById('seenPills').outerHTML = buildTogglePills(<?php echo json_encode($availableG); ?>, '<?php echo $defaultG; ?>');
    chartToggle('chartSeen', datasets, '<?php echo $defaultG; ?>');
})();
</script>

<div class="report-grid-2">

    <!-- Consult time trend -->
    <?php if (!empty($consultTrend)): ?>
    <div class="chart-card">
        <h6><i class="fas fa-stopwatch"></i> Avg Consultation Time (min)</h6>
        <span class="chart-period"><?php echo $periodLabel; ?></span>
        <canvas id="chartConsult" height="120"></canvas>
    </div>
    <script>
    (function(){
        const labels = <?php echo json_encode(array_column($consultTrend,'day')); ?>;
        const mins   = <?php echo json_encode(array_map(fn($r)=>(float)$r['avg_minutes'], $consultTrend)); ?>;
        const ctx    = document.getElementById('chartConsult').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: { labels, datasets:[{
                label:'Avg Minutes', data:mins,
                borderColor:CHART_COLORS.yellow,
                backgroundColor:makeGradient(ctx, CHART_COLORS.yellow),
                fill:true, tension:0.4, pointRadius:3,
            }]},
            options:{ responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}} }
        });
    })();
    </script>
    <?php endif; ?>

    <!-- Load by day of week radar -->
    <?php if (!empty($busyDays)): ?>
    <div class="chart-card">
        <h6><i class="fas fa-calendar-week"></i> Load by Day of Week</h6>
        <span class="chart-period"><?php echo $periodLabel; ?></span>
        <canvas id="chartDow" height="120"></canvas>
    </div>
    <script>
    (function(){
        const raw = <?php echo json_encode($busyDays); ?>;
        new Chart(document.getElementById('chartDow'), {
            type: 'radar',
            data: {
                labels: raw.map(r=>r.day_name),
                datasets:[{
                    label:'Appointments',
                    data: raw.map(r=>parseInt(r.total)),
                    backgroundColor:CHART_COLORS.cyan+'33',
                    borderColor:CHART_COLORS.cyan,
                    pointBackgroundColor:CHART_COLORS.cyan,
                    borderWidth:2,
                }]
            },
            options:{ responsive:true, plugins:{legend:{display:false}}, scales:{r:{beginAtZero:true,ticks:{precision:0,stepSize:1}}} }
        });
    })();
    </script>
    <?php endif; ?>

</div>

<?php if (empty($byDay) && empty($byWeek)): ?>
<div style="text-align:center;padding:60px;color:#9ca3af;">
    <i class="fas fa-stethoscope" style="font-size:32px;display:block;margin-bottom:10px;"></i>
    No completed consultations found for this period.
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
