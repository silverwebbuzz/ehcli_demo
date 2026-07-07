<?php
$page_title  = 'Patient Analytics';
$reportTitle = 'Patient Analytics';
$reportIcon  = 'fas fa-users';
$reportBase  = '/reports/patients';
ob_start();

$byDay        = $reportData['byDay']        ?? [];
$byWeek       = $reportData['byWeek']       ?? [];
$byMonth      = $reportData['byMonth']      ?? [];
$gender       = $reportData['gender']       ?? [];
$ageGroups    = $reportData['ageGroups']    ?? [];
$complaints   = $reportData['complaints']   ?? [];
$newReturning = $reportData['newReturning'] ?? [];
$period       = $reportData['period']       ?? 'week';
$year         = $reportData['year']         ?? date('Y');

$showYearPicker = true;
require __DIR__ . '/_header.php';
// $defaultG and $availableG are set by _header.php

$newPts   = (int)($newReturning['new_patients']       ?? 0);
$retPts   = (int)($newReturning['returning_patients'] ?? 0);
$totalPts = $newPts + $retPts;

// JS dataset prep
$dayLabels  = json_encode(array_column($byDay,  'day'));
$dayVals    = json_encode(array_map(fn($r)=>(int)$r['count'], $byDay));

$weekLabels = json_encode(array_map(fn($r)=>date('d M', strtotime($r['week_start'])), $byWeek));
$weekVals   = json_encode(array_map(fn($r)=>(int)$r['count'], $byWeek));

$allMonths  = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$mCounts    = array_fill(0, 12, 0);
foreach ($byMonth as $r) { $mCounts[(int)explode('-',$r['month'])[1]-1] = (int)$r['count']; }
$monthLabels = json_encode($allMonths);
$monthVals   = json_encode($mCounts);
?>

<!-- Summary cards -->
<div class="report-grid-4" style="margin-bottom:16px;">
    <div class="stat-box">
        <div class="sv" style="color:#2563eb;"><?php echo number_format($totalPts); ?></div>
        <div class="sl">Total Visits</div>
    </div>
    <div class="stat-box">
        <div class="sv" style="color:#16a34a;"><?php echo number_format($newPts); ?></div>
        <div class="sl">New Patients</div>
    </div>
    <div class="stat-box">
        <div class="sv" style="color:#d97706;"><?php echo number_format($retPts); ?></div>
        <div class="sl">Returning Patients</div>
    </div>
    <div class="stat-box">
        <div class="sv" style="color:#7c3aed;">
            <?php echo $totalPts > 0 ? round($newPts/$totalPts*100).'%' : '—'; ?>
        </div>
        <div class="sl">New Patient Rate</div>
    </div>
</div>

<!-- New registrations trend with toggle -->
<div class="chart-card">
    <h6><i class="fas fa-chart-line"></i> New Patient Registrations <span id="regPills"></span></h6>
    <span class="chart-period"><?php echo $periodLabel; ?></span>
    <canvas id="chartReg" height="80"></canvas>
</div>
<script>
(function(){
    const ctx = document.getElementById('chartReg').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'line',
        data: { labels:[], datasets:[{
            label:'New Patients', data:[],
            borderColor:CHART_COLORS.primary,
            backgroundColor: makeGradient(ctx, CHART_COLORS.primary),
            fill:true, tension:0.4, pointRadius:3,
        }]},
        options:{ responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{precision:0}}} }
    });

    const datasets = {
        day:   { labels: <?php echo $dayLabels; ?>,   values: <?php echo $dayVals; ?> },
        week:  { labels: <?php echo $weekLabels; ?>,  values: <?php echo $weekVals; ?> },
        month: { labels: <?php echo $monthLabels; ?>, values: <?php echo $monthVals; ?> },
    };
    document.getElementById('regPills').outerHTML = buildTogglePills(<?php echo json_encode($availableG); ?>, '<?php echo $defaultG; ?>');
    chartToggle('chartReg', datasets, '<?php echo $defaultG; ?>');
})();
</script>

<div class="report-grid-2">

    <!-- Gender doughnut -->
    <div class="chart-card">
        <h6><i class="fas fa-venus-mars"></i> Gender Distribution</h6>
        <span class="chart-period">All Time</span>
        <?php
        $genderMap   = ['M'=>'Male','F'=>'Female',''=>'Unknown'];
        $genderTotal = array_sum(array_column($gender, 'count'));
        ?>
        <canvas id="chartGender" height="140"></canvas>
        <!-- Counts table below chart -->
        <div style="display:flex;justify-content:center;gap:20px;margin-top:10px;flex-wrap:wrap;">
        <?php
        $gColors = ['#3b82f6','#ef4444','#9ca3af'];
        foreach ($gender as $i => $g):
            $lbl = $genderMap[$g['gender']] ?? ($g['gender'] ?: 'Unknown');
            $pct = $genderTotal > 0 ? round($g['count']/$genderTotal*100, 1) : 0;
        ?>
            <div style="text-align:center;">
                <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?php echo $gColors[$i%3]; ?>;margin-right:4px;vertical-align:middle;"></span>
                <span style="font-size:12px;color:#374151;"><?php echo $lbl; ?></span><br>
                <span style="font-size:18px;font-weight:800;color:<?php echo $gColors[$i%3]; ?>;"><?php echo number_format((int)$g['count']); ?></span>
                <span style="font-size:11px;color:#9ca3af;"> (<?php echo $pct; ?>%)</span>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
    <script>
    (function(){
        const raw = <?php echo json_encode($gender); ?>;
        const labelMap = { 'M':'Male','F':'Female','':'Unknown' };
        const total = raw.reduce((s,r)=>s+parseInt(r.count),0);
        new Chart(document.getElementById('chartGender'), {
            type: 'doughnut',
            data: {
                labels: raw.map(r => {
                    const lbl = labelMap[r.gender] || r.gender || 'Unknown';
                    const pct = total > 0 ? (parseInt(r.count)/total*100).toFixed(1) : 0;
                    return `${lbl} — ${parseInt(r.count).toLocaleString()} (${pct}%)`;
                }),
                datasets: [{ data: raw.map(r=>parseInt(r.count)),
                    backgroundColor:[CHART_COLORS.primary,CHART_COLORS.red,CHART_COLORS.gray], borderWidth:2 }]
            },
            options:{
                responsive:true,
                plugins:{
                    legend:{ position:'bottom', labels:{ font:{size:11}, padding:12 } },
                    tooltip:{ callbacks:{ label: ctx => ' '+ctx.formattedValue+' patients' } }
                }
            }
        });
    })();
    </script>

    <!-- Age groups with counts on bars -->
    <?php if (!empty($ageGroups)): ?>
    <div class="chart-card">
        <h6><i class="fas fa-chart-bar"></i> Age Groups</h6>
        <span class="chart-period">All Time</span>
        <canvas id="chartAge" height="140"></canvas>
        <!-- Counts summary row -->
        <div style="display:flex;justify-content:space-around;margin-top:10px;flex-wrap:wrap;gap:6px;">
        <?php foreach ($ageGroups as $ag): ?>
            <div style="text-align:center;">
                <div style="font-size:15px;font-weight:800;color:#8b5cf6;"><?php echo number_format((int)$ag['count']); ?></div>
                <div style="font-size:10px;color:#9ca3af;"><?php echo htmlspecialchars($ag['age_group']); ?> yrs</div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
    <script>
    (function(){
        const raw = <?php echo json_encode($ageGroups); ?>;
        new Chart(document.getElementById('chartAge'), {
            type: 'bar',
            data: {
                labels: raw.map(r=>r.age_group+' yrs'),
                datasets:[{ label:'Patients', data:raw.map(r=>parseInt(r.count)),
                    backgroundColor:CHART_COLORS.purple+'bb', borderColor:CHART_COLORS.purple, borderWidth:1, borderRadius:4 }]
            },
            options:{
                responsive:true,
                plugins:{ legend:{display:false} },
                scales:{ y:{
                    beginAtZero:true,
                    ticks:{ precision:0 },
                    // extra top padding so labels don't clip
                    grace: '15%'
                }}
            },
            plugins:[topLabelPlugin]
        });
    })();
    </script>
    <?php endif; ?>

</div>

<!-- Top complaints -->
<?php if (!empty($complaints)): ?>
<div class="chart-card" style="overflow-x:auto;">
    <h6><i class="fas fa-notes-medical"></i> Top Chief Complaints</h6>
    <span class="chart-period">All Time</span>
    <?php $maxC = (int)($complaints[0]['count'] ?? 1); ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px 24px;">
    <?php foreach ($complaints as $c): ?>
    <div style="margin-bottom:5px;">
        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:2px;">
            <span><?php echo htmlspecialchars(ucfirst($c['chief'])); ?></span>
            <span style="font-weight:700;"><?php echo (int)$c['count']; ?></span>
        </div>
        <div style="background:#f3f4f6;border-radius:4px;height:6px;">
            <div style="background:var(--primary);height:6px;border-radius:4px;width:<?php echo round($c['count']/$maxC*100); ?>%;"></div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>


<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
