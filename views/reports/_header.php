<?php
/**
 * Shared report header partial.
 * Requires: $reportData (array with 'period','from','to','year'), $reportTitle, $reportIcon, $reportBase
 * Optional: $showYearPicker = true — enables year select inside the "This Year" button
 */
$period         = $reportData['period'] ?? 'week';
$from           = $reportData['from']   ?? date('Y-m-d');
$to             = $reportData['to']     ?? date('Y-m-d');
$selectedYear   = (int)($reportData['year'] ?? date('Y'));
$showYearPicker = $showYearPicker ?? false;

/**
 * Returns a compact, human-readable period label for chart subtitles.
 * Examples:
 *   year,  2025           → "Year 2025"
 *   month, 2026-04-01..30 → "April 2026"
 *   week,  2026-04-07..13 → "7–13 Apr 2026"
 *   today, 2026-04-10     → "10 Apr 2026"
 *   custom, any range     → "1 Apr – 25 Apr 2026" or "1 Apr 2025 – 25 Apr 2026"
 */
function rPeriodLabel(string $period, string $from, string $to): string {
    switch ($period) {
        case 'year':
            return 'Year ' . date('Y', strtotime($from));
        case 'month':
            return date('F Y', strtotime($from));
        case 'week':
            $f = date('j M', strtotime($from));
            $t = date('j M Y', strtotime($to));
            return $f . ' – ' . $t;
        case 'today':
            return date('j F Y', strtotime($from));
        default: // custom
            $fy = date('Y', strtotime($from));
            $ty = date('Y', strtotime($to));
            if ($fy === $ty) {
                return date('j M', strtotime($from)) . ' – ' . date('j M Y', strtotime($to));
            }
            return date('j M Y', strtotime($from)) . ' – ' . date('j M Y', strtotime($to));
    }
}

$periodLabel = rPeriodLabel($period, $from, $to);

/**
 * Determine which granularity pills to show and what the default granularity is.
 * For custom ranges we restrict based on the span so the chart always has data.
 *   ≤ 14 days  → Daily only
 *   15–60 days → Daily + Weekly
 *   > 60 days  → Daily + Weekly + Monthly
 * For fixed periods the legacy rules apply.
 */
if ($period === 'custom') {
    $spanDays = (int)round((strtotime($to) - strtotime($from)) / 86400) + 1;
    if ($spanDays <= 14) {
        $availableG = ['day'];
        $defaultG   = 'day';
    } elseif ($spanDays <= 60) {
        $availableG = ['day', 'week'];
        $defaultG   = 'day';
    } else {
        $availableG = ['day', 'week', 'month'];
        $defaultG   = 'week';
    }
} else {
    $availableG = ['day', 'week', 'month'];
    $defaultG   = in_array($period, ['today', 'week']) ? 'day' : ($period === 'month' ? 'week' : 'month');
}
?>
<style>
/* ── Report shared styles ──────────────────────────────────────── */
.report-period-bar { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:18px; }
.period-btn { padding:5px 14px; border:2px solid #e5e7eb; border-radius:20px; background:#fff; font-size:12px; font-weight:600; color:#6b7280; cursor:pointer; transition:.15s; text-decoration:none; white-space:nowrap; }
.period-btn:hover { border-color:#93c5fd; color:var(--primary); }
.period-btn.active { border-color:var(--primary); background:var(--primary); color:#fff; }
/* Year btn: active state bleeds into the inline select */
.year-btn.active a { color:#fff; }
.year-btn.active select { color:#fff; border-left-color:rgba(255,255,255,.3); }
.year-btn.active select option { color:#374151; background:#fff; }
.border-primary { border-color:var(--primary) !important; }
.stat-box { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:14px 18px; }
.stat-box .sv { font-size:26px; font-weight:800; line-height:1; }
.stat-box .sl { font-size:11px; color:#6b7280; margin-top:3px; }
.chart-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:16px; margin-bottom:16px; }
.chart-card h6 { font-size:12px; font-weight:700; color:#374151; margin-bottom:2px; text-transform:uppercase; letter-spacing:.5px; }
.chart-period { font-size:11px; color:#9ca3af; font-weight:400; margin-bottom:12px; display:block; }
.report-grid-4 { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:16px; }
.report-grid-3 { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:16px; }
.report-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
@media(max-width:768px){
    .report-grid-4,.report-grid-3 { grid-template-columns:1fr 1fr; }
    .report-grid-2 { grid-template-columns:1fr; }
    .report-period-bar { gap:6px; }
    .period-btn { padding:4px 10px; font-size:11px; }
}
@media(max-width:480px){
    .report-grid-4,.report-grid-3 { grid-template-columns:1fr 1fr; }
    .stat-box .sv { font-size:20px; }
    /* Custom range stacks on small screens */
    .report-period-bar form { flex-wrap:wrap; gap:4px; }
    .report-period-bar form input[type=date] { width:100% !important; }
}
</style>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
    <h1 class="page-title" style="margin:0;">
        <i class="<?php echo $reportIcon; ?>"></i> <?php echo $reportTitle; ?>
    </h1>
</div>

<!--
  Period picker — three mutually exclusive modes, no URL conflicts:
  1. Quick buttons: Today / This Week / This Month  → period=today|week|month  (no year param)
  2. This Year button + inline year select           → period=year&year=YYYY
  3. Custom range toggle                             → period=custom&from=&to=  (no year param)
  Active mode is highlighted; other modes are inactive/collapsed.
-->
<div class="report-period-bar">

    <!-- Quick buttons -->
    <a href="<?php echo $reportBase; ?>?period=today"
       class="period-btn <?php echo $period==='today'?'active':''; ?>">Today</a>

    <a href="<?php echo $reportBase; ?>?period=week"
       class="period-btn <?php echo $period==='week'?'active':''; ?>">This Week</a>

    <a href="<?php echo $reportBase; ?>?period=month"
       class="period-btn <?php echo $period==='month'?'active':''; ?>">This Month</a>

    <!-- This Year button with inline year select — only these two share the year param -->
    <span class="period-btn year-btn <?php echo $period==='year'?'active':''; ?>"
          style="display:inline-flex;align-items:center;gap:0;padding:0;overflow:hidden;">
        <a href="<?php echo $reportBase; ?>?period=year&year=<?php echo $selectedYear; ?>"
           style="padding:5px 8px 5px 14px;color:inherit;text-decoration:none;display:block;">
            This Year
        </a>
        <?php if ($showYearPicker): ?>
        <select onchange="location='<?php echo $reportBase; ?>?period=year&year='+this.value"
                style="border:none;border-left:1px solid #e5e7eb;background:transparent;font-size:12px;
                       font-weight:600;color:<?php echo $period==='year'?'#fff':'#6b7280'; ?>;
                       padding:5px 6px 5px 4px;cursor:pointer;outline:none;appearance:auto;">
            <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 9; $y--): ?>
                <option value="<?php echo $y; ?>" <?php echo $y===$selectedYear?'selected':''; ?>
                        style="color:#374151;background:#fff;">
                    <?php echo $y; ?>
                </option>
            <?php endfor; ?>
        </select>
        <?php endif; ?>
    </span>

    <!-- Divider -->
    <span style="color:#e5e7eb;font-size:16px;margin:0 2px;">|</span>

    <!-- Custom range — always visible, activates on submit -->
    <form method="GET" action="<?php echo $reportBase; ?>"
          style="display:flex;align-items:center;gap:5px;">
        <input type="hidden" name="period" value="custom">
        <input type="date" name="from"
               value="<?php echo htmlspecialchars($period==='custom' ? $from : date('Y-m-01')); ?>"
               class="form-control form-control-sm <?php echo $period==='custom'?'border-primary':''; ?>"
               style="width:125px;font-size:12px;">
        <span style="color:#9ca3af;font-size:11px;">→</span>
        <input type="date" name="to"
               value="<?php echo htmlspecialchars($period==='custom' ? $to : date('Y-m-d')); ?>"
               class="form-control form-control-sm <?php echo $period==='custom'?'border-primary':''; ?>"
               style="width:125px;font-size:12px;">
        <button type="submit"
                class="btn btn-sm <?php echo $period==='custom'?'btn-primary':'btn-secondary'; ?>">
            <?php echo $period==='custom' ? 'Custom ✓' : 'Go'; ?>
        </button>
    </form>

    <!-- Active range label — right side -->
    <span style="margin-left:auto;font-size:11px;color:#9ca3af;white-space:nowrap;">
        <i class="fas fa-calendar-alt" style="margin-right:3px;"></i>
        <?php echo $periodLabel; ?>
    </span>

</div>

<!-- Chart.js (loaded once via flag) -->
<?php if (empty($GLOBALS['__chartjsLoaded'])): $GLOBALS['__chartjsLoaded'] = true; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'Inter','Segoe UI',sans-serif";
Chart.defaults.font.size   = 11;
Chart.defaults.color       = '#6b7280';

const CHART_COLORS = {
    primary : '#3b82f6',
    green   : '#22c55e',
    yellow  : '#f59e0b',
    red     : '#ef4444',
    purple  : '#8b5cf6',
    cyan    : '#06b6d4',
    gray    : '#9ca3af',
};

function makeGradient(ctx, color) {
    const g = ctx.createLinearGradient(0,0,0,200);
    g.addColorStop(0, color + '33');
    g.addColorStop(1, color + '00');
    return g;
}

/**
 * chartToggle — wire up Daily/Weekly/Monthly pills to a Chart.js instance.
 *
 * @param {string}   chartId   canvas element id
 * @param {object}   datasets  { day: {labels,values}, week: {labels,values}, month: {labels,values} }
 *                             values can be array (single dataset) or array-of-arrays (multi-dataset stacked)
 * @param {string}   active    initial granularity: 'day' | 'week' | 'month'
 */
function chartToggle(chartId, datasets, active) {
    const card   = document.getElementById(chartId).closest('.chart-card');
    const pills  = card.querySelectorAll('.ct-pill');
    const chart  = Chart.getChart(chartId);
    if (!chart) return;

    function apply(key) {
        const d = datasets[key];
        if (!d) return;
        chart.data.labels = d.labels;
        // single or multi dataset
        if (Array.isArray(d.values[0])) {
            d.values.forEach((vals, i) => { if (chart.data.datasets[i]) chart.data.datasets[i].data = vals; });
        } else {
            chart.data.datasets[0].data = d.values;
        }
        chart.update();
        pills.forEach(p => p.classList.toggle('ct-active', p.dataset.g === key));
    }

    pills.forEach(pill => pill.addEventListener('click', () => apply(pill.dataset.g)));
    apply(active);
}

/**
 * Build toggle pills HTML string for insertion into chart-card h6.
 * granularities: array of keys present, e.g. ['day','week','month']
 * active: which is default
 */
function buildTogglePills(granularities, active) {
    const labels = { day:'Daily', week:'Weekly', month:'Monthly' };
    return '<span class="ct-pills">' +
        granularities.map(g =>
            `<button class="ct-pill${g===active?' ct-active':''}" data-g="${g}">${labels[g]}</button>`
        ).join('') +
    '</span>';
}

/**
 * Shared Chart.js plugin: draws the value on top of each bar (vertical bars only).
 * Usage: add `plugins:[topLabelPlugin]` to any bar chart config.
 */
const topLabelPlugin = {
    id: 'topLabel',
    afterDatasetsDraw(chart) {
        const { ctx } = chart;
        chart.data.datasets.forEach((dataset, di) => {
            // Only draw on the last dataset (avoids duplicates in stacked charts)
            if (di !== chart.data.datasets.length - 1) return;
            chart.getDatasetMeta(di).data.forEach((bar, i) => {
                // For stacked charts, show the total across all datasets
                const total = chart.data.datasets.reduce((s, ds) => s + (Number(ds.data[i]) || 0), 0);
                if (!total) return;
                ctx.save();
                ctx.font = 'bold 10px Inter,sans-serif';
                ctx.fillStyle = '#374151';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'bottom';
                ctx.fillText(total.toLocaleString(), bar.x, bar.y - 2);
                ctx.restore();
            });
        });
    }
};
</script>
<style>
/* Per-chart granularity toggle pills */
.chart-card h6 { display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap; }
.ct-pills { display:flex; gap:3px; margin-left:auto; }
.ct-pill { padding:2px 9px; border:1.5px solid #e5e7eb; border-radius:12px; background:#fff;
           font-size:10px; font-weight:600; color:#6b7280; cursor:pointer; transition:.12s; line-height:1.6; }
.ct-pill:hover { border-color:#93c5fd; color:var(--primary); }
.ct-pill.ct-active { border-color:var(--primary); background:var(--primary); color:#fff; }
</style>
<?php endif; ?>
