<?php
$page_title  = 'Queue & Operations';
$reportTitle = 'Queue & Operations';
$reportIcon  = 'fas fa-list-ol';
$reportBase  = '/reports/queue';
ob_start();

$byDay       = $reportData['byDay']       ?? [];
$byWeek      = $reportData['byWeek']      ?? [];
$byMonth     = $reportData['byMonth']     ?? [];
$consultTime = $reportData['consultTime'] ?? [];
$busyDays    = $reportData['busyDays']    ?? [];
$busySlots   = $reportData['busySlots']   ?? [];
$noShow      = $reportData['noShow']      ?? [];
$period      = $reportData['period']      ?? 'week';
$year        = $reportData['year']        ?? date('Y');

require __DIR__ . '/_header.php';
// $defaultG and $availableG are set by _header.php

$total     = (int)($noShow['total']     ?? 0);
$completed = (int)($noShow['completed'] ?? 0);
$nsCount   = (int)($noShow['no_show']   ?? 0);
$nsRate    = $total > 0 ? round($nsCount/$total*100, 1) : 0;

// Build label/value arrays for each granularity
$dayLabels  = json_encode(array_column($byDay,  'day'));
$dayComp    = json_encode(array_map(fn($r)=>(int)$r['completed'], $byDay));
$dayNS      = json_encode(array_map(fn($r)=>(int)$r['no_show'],   $byDay));
$dayCan     = json_encode(array_map(fn($r)=>(int)$r['cancelled'], $byDay));

$weekLabels = json_encode(array_map(fn($r)=>date('d M', strtotime($r['week_start'])), $byWeek));
$weekComp   = json_encode(array_map(fn($r)=>(int)$r['completed'], $byWeek));
$weekNS     = json_encode(array_map(fn($r)=>(int)$r['no_show'],   $byWeek));
$weekCan    = json_encode(array_map(fn($r)=>(int)$r['cancelled'], $byWeek));

$allMonths  = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$mComp = $mNS = $mCan = array_fill(0,12,0);
foreach ($byMonth as $r) {
    $i = (int)explode('-',$r['month'])[1]-1;
    $mComp[$i] = (int)$r['completed'];
    $mNS[$i]   = (int)$r['no_show'];
    $mCan[$i]  = (int)$r['cancelled'];
}
$monthLabels = json_encode($allMonths);
$monthComp   = json_encode($mComp);
$monthNS     = json_encode($mNS);
$monthCan    = json_encode($mCan);
?>

<!-- Summary cards -->
<div class="report-grid-4" style="margin-bottom:16px;">
    <div class="stat-box">
        <div class="sv" style="color:#2563eb;"><?php echo number_format($total); ?></div>
        <div class="sl">Total Appointments</div>
    </div>
    <div class="stat-box">
        <div class="sv" style="color:#16a34a;"><?php echo number_format($completed); ?></div>
        <div class="sl">Completed</div>
    </div>
    <div class="stat-box">
        <div class="sv" style="color:#ef4444;"><?php echo $nsRate; ?>%</div>
        <div class="sl">No-Show Rate</div>
    </div>
    <div class="stat-box">
        <div class="sv" style="color:#7c3aed;"><?php echo $consultTime['avg_minutes'] ?? '—'; ?> min</div>
        <div class="sl">Avg Consult Time</div>
    </div>
</div>

<!-- Appointments stacked chart with toggle -->
<div class="chart-card">
    <h6><i class="fas fa-chart-bar"></i> Appointment Breakdown <span id="apptPills"></span></h6>
    <span class="chart-period"><?php echo $periodLabel; ?></span>
    <canvas id="chartAppt" height="80"></canvas>
</div>
<script>
(function(){
    const chart = new Chart(document.getElementById('chartAppt'), {
        type: 'bar',
        data: { labels:[], datasets:[
            { label:'Completed', data:[], backgroundColor:CHART_COLORS.green+'cc', borderRadius:3 },
            { label:'No Show',   data:[], backgroundColor:CHART_COLORS.red+'cc',   borderRadius:3 },
            { label:'Cancelled', data:[], backgroundColor:CHART_COLORS.gray+'cc',  borderRadius:3 },
        ]},
        options:{
            responsive:true,
            scales:{ x:{stacked:true}, y:{stacked:true,beginAtZero:true,grace:'12%',ticks:{precision:0}} },
            plugins:{ legend:{position:'bottom'} }
        },
        plugins:[topLabelPlugin]
    });

    const datasets = {
        day:   { labels:<?php echo $dayLabels;  ?>, values:[<?php echo $dayComp;  ?>,<?php echo $dayNS;  ?>,<?php echo $dayCan;  ?>] },
        week:  { labels:<?php echo $weekLabels; ?>, values:[<?php echo $weekComp; ?>,<?php echo $weekNS; ?>,<?php echo $weekCan; ?>] },
        month: { labels:<?php echo $monthLabels;?>, values:[<?php echo $monthComp;?>,<?php echo $monthNS;?>,<?php echo $monthCan;?>] },
    };
    document.getElementById('apptPills').outerHTML = buildTogglePills(<?php echo json_encode($availableG); ?>, '<?php echo $defaultG; ?>');
    chartToggle('chartAppt', datasets, '<?php echo $defaultG; ?>');
})();
</script>

<div class="report-grid-2">

    <!-- Busiest days of week -->
    <?php if (!empty($busyDays)): ?>
    <div class="chart-card">
        <h6><i class="fas fa-calendar-week"></i> Busiest Days of Week</h6>
        <span class="chart-period"><?php echo $periodLabel; ?></span>
        <canvas id="chartDow" height="120"></canvas>
    </div>
    <script>
    (function(){
        const raw = <?php echo json_encode($busyDays); ?>;
        new Chart(document.getElementById('chartDow'), {
            type: 'bar',
            data: {
                labels: raw.map(r=>r.day_name),
                datasets:[{ label:'Appointments', data:raw.map(r=>parseInt(r.total)),
                    backgroundColor:CHART_COLORS.yellow+'cc', borderColor:CHART_COLORS.yellow, borderWidth:1, borderRadius:4 }]
            },
            options:{ responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,grace:'15%',ticks:{precision:0}}} },
            plugins:[topLabelPlugin]
        });
    })();
    </script>
    <?php endif; ?>

    <!-- Busiest slots -->
    <?php if (!empty($busySlots)): ?>
    <div class="chart-card" style="overflow-x:auto;">
        <h6><i class="fas fa-clock"></i> Top Time Slots</h6>
        <span class="chart-period"><?php echo $periodLabel; ?></span>
        <?php
        function to12r($t){ [$h,$m]=explode(':',$t); $h=(int)$h; return ($h%12?:12).':'.str_pad($m,2,'0',STR_PAD_LEFT).' '.($h<12?'AM':'PM'); }
        $maxS = (int)($busySlots[0]['total'] ?? 1);
        ?>
        <?php foreach ($busySlots as $s): ?>
        <div style="margin-bottom:7px;">
            <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:2px;">
                <span><?php echo to12r($s['slot']); ?></span>
                <span style="font-weight:700;"><?php echo (int)$s['total']; ?> appts</span>
            </div>
            <div style="background:#f3f4f6;border-radius:4px;height:6px;">
                <div style="background:var(--primary);height:6px;border-radius:4px;width:<?php echo round($s['total']/$maxS*100); ?>%;"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<!-- Consult time summary -->
<?php if (!empty($consultTime['sample_count'])): ?>
<div class="chart-card" style="max-width:500px;">
    <h6><i class="fas fa-stopwatch"></i> Consultation Duration</h6>
    <span class="chart-period"><?php echo $periodLabel; ?></span>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;text-align:center;">
        <div><div style="font-size:22px;font-weight:800;color:#2563eb;"><?php echo $consultTime['avg_minutes']; ?> min</div><div style="font-size:11px;color:#9ca3af;">Average</div></div>
        <div><div style="font-size:22px;font-weight:800;color:#16a34a;"><?php echo $consultTime['min_minutes']; ?> min</div><div style="font-size:11px;color:#9ca3af;">Shortest</div></div>
        <div><div style="font-size:22px;font-weight:800;color:#d97706;"><?php echo $consultTime['max_minutes']; ?> min</div><div style="font-size:11px;color:#9ca3af;">Longest</div></div>
    </div>
    <div style="font-size:11px;color:#9ca3af;margin-top:8px;text-align:center;">Based on <?php echo number_format($consultTime['sample_count']); ?> completed consultations</div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
