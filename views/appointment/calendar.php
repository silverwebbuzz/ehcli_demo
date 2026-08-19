<?php
/**
 * Appointments calendar — Practo-style day / week / month grid.
 *
 * The grid is rendered client-side from /api/calendar, which returns both the
 * booked appointments and the slot list clinic settings generate for each day.
 * Building the time axis from those slots keeps the calendar in step with the
 * configured clinic hours instead of hard-coding a time range here.
 */
$page_title = 'Calendar';
$today      = date('Y-m-d');
$calRole    = $_SESSION['role'] ?? 'doctor';
$canConsult = in_array($calRole, ['doctor', 'asst_doctor']);
ob_start();
?>
<style>
.cal-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:14px; }
.cal-toolbar { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }

.appt-view-tabs { display:flex; gap:6px; }
.appt-view-tab {
    padding:5px 16px; border-radius:20px; border:2px solid #e5e7eb;
    background:#fff; font-size:12px; font-weight:600; color:#6b7280;
    text-decoration:none; cursor:pointer; transition:.15s; white-space:nowrap;
}
.appt-view-tab:hover { border-color:#93c5fd; color:var(--primary); text-decoration:none; }
.appt-view-tab.active { border-color:var(--primary); background:var(--primary); color:#fff; }

.cal-nav { display:flex; align-items:center; gap:6px; }
.cal-range { font-size:13px; font-weight:700; color:#374151; min-width:190px; text-align:center; }

.cal-wrap { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; position:relative; min-height:240px; }
.cal-scroll { overflow:auto; max-height:calc(100vh - 240px); }

/* ── Time-grid (day / week) ─────────────────────────────── */
table.cal-grid { border-collapse:collapse; width:100%; min-width:760px; }
table.cal-grid th, table.cal-grid td { border:1px solid #eef0f3; }
table.cal-grid thead th {
    position:sticky; top:0; z-index:3; background:#f9fafb;
    padding:8px 4px; font-size:11px; font-weight:700; color:#6b7280;
    text-transform:uppercase; letter-spacing:.4px; text-align:center;
}
table.cal-grid thead th .dnum { display:block; font-size:17px; color:#111827; letter-spacing:0; margin-top:1px; }
table.cal-grid thead th.is-today { background:#eff6ff; color:var(--primary); }
table.cal-grid thead th.is-today .dnum { color:var(--primary); }
table.cal-grid thead th.col-time { width:76px; min-width:76px; }
td.time-cell {
    width:76px; min-width:76px; background:#fafbfc; vertical-align:top;
    padding:4px 6px; font-size:10.5px; font-weight:700; color:#9ca3af;
    text-align:right; white-space:nowrap;
}
td.slot-cell { vertical-align:top; padding:3px; height:40px; cursor:pointer; transition:background .12s; }
td.slot-cell:hover { background:#f5f9ff; }
td.slot-cell.off  { background:#fafafa; cursor:default; }
td.slot-cell.off:hover { background:#fafafa; }
td.slot-cell.full { cursor:default; }
td.slot-cell.past { background:#fbfbfb; }
td.slot-cell .add-hint { display:none; font-size:10px; color:var(--primary); font-weight:700; }
td.slot-cell:hover .add-hint { display:block; }
td.slot-cell.closed-cell { background:repeating-linear-gradient(45deg,#fafafa,#fafafa 6px,#f3f4f6 6px,#f3f4f6 12px); cursor:default; }

/* ── Appointment chip ───────────────────────────────────── */
.appt-chip {
    display:block; width:100%; text-align:left; border:none; border-left:3px solid #6b7280;
    background:#f3f4f6; color:#111827; border-radius:4px; padding:3px 6px;
    font-size:11px; line-height:1.25; margin-bottom:3px; cursor:pointer;
    overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
}
.appt-chip:hover { filter:brightness(.96); }
.appt-chip .chip-time { font-weight:700; margin-right:4px; font-size:10px; opacity:.85; }
.appt-chip .chip-name { font-weight:600; }
.chip-waiting         { background:#fef3c7; border-left-color:#f59e0b; }
.chip-arrived         { background:#dcfce7; border-left-color:#22c55e; }
.chip-in_consultation { background:#dbeafe; border-left-color:#3b82f6; }
.chip-completed       { background:#e5e7eb; border-left-color:#16a34a; color:#4b5563; }
.chip-cancelled       { background:#f3f4f6; border-left-color:#9ca3af; color:#9ca3af; text-decoration:line-through; }
.chip-no_show         { background:#fee2e2; border-left-color:#ef4444; color:#991b1b; }

/* ── Month grid ─────────────────────────────────────────── */
.cal-month { display:grid; grid-template-columns:repeat(7,1fr); }
.cal-month .m-dow {
    background:#f9fafb; padding:8px 6px; font-size:11px; font-weight:700; color:#6b7280;
    text-transform:uppercase; letter-spacing:.4px; text-align:center; border-bottom:1px solid #eef0f3;
}
.m-cell {
    min-height:104px; border-right:1px solid #eef0f3; border-bottom:1px solid #eef0f3;
    padding:5px 6px 6px; cursor:pointer; transition:background .12s;
}
.m-cell:nth-child(7n) { border-right:none; }
.m-cell:hover { background:#f5f9ff; }
.m-cell.outside { background:#fcfcfd; }
.m-cell.outside .m-date { color:#d1d5db; }
.m-cell.closed-cell { background:repeating-linear-gradient(45deg,#fafafa,#fafafa 6px,#f3f4f6 6px,#f3f4f6 12px); cursor:default; }
.m-date { font-size:12px; font-weight:700; color:#374151; margin-bottom:4px; display:flex; align-items:center; justify-content:space-between; }
.m-cell.is-today .m-date .dd {
    background:var(--primary); color:#fff; border-radius:50%;
    width:21px; height:21px; line-height:21px; text-align:center; display:inline-block;
}
.m-count { font-size:9.5px; font-weight:700; color:#6b7280; background:#f3f4f6; border-radius:9px; padding:1px 6px; }
.m-more { font-size:10px; color:var(--primary); font-weight:700; }

/* ── Legend / loader / empty ────────────────────────────── */
.cal-legend { display:flex; gap:14px; flex-wrap:wrap; margin-top:10px; font-size:11px; color:#6b7280; }
.cal-legend span i { width:9px; height:9px; border-radius:2px; display:inline-block; margin-right:4px; }
.cal-loading { position:absolute; inset:0; background:rgba(255,255,255,.7); display:none;
               align-items:center; justify-content:center; z-index:5; color:#6b7280; font-size:13px; }
.cal-loading.on { display:flex; }
.cal-empty { padding:40px; text-align:center; color:#9ca3af; }

.slot-pick { display:flex; flex-wrap:wrap; gap:6px; }
.slot-pick .sp {
    border:2px solid #e5e7eb; border-radius:8px; padding:5px 10px; font-size:12px;
    font-weight:600; color:#374151; cursor:pointer; background:#fff;
}
.slot-pick .sp:hover { border-color:#93c5fd; }
.slot-pick .sp.sel  { border-color:var(--primary); background:#eff6ff; color:var(--primary); }
.slot-pick .sp.full { opacity:.45; cursor:not-allowed; border-color:#f3f4f6; background:#f9fafb; }
.search-hit { padding:7px 10px; cursor:pointer; border-bottom:1px solid #f3f4f6; font-size:12.5px; }
.search-hit:hover { background:#eff6ff; }

@media(max-width:768px){
    .cal-range { min-width:0; font-size:12px; }
    .cal-scroll { max-height:none; }
    .m-cell { min-height:82px; }
    .appt-chip { font-size:10px; }
}
</style>

<div class="cal-header page-header" style="padding-bottom:12px;">
    <div>
        <h1 class="page-title" style="margin:0;"><i class="fas fa-calendar-alt"></i> Calendar</h1>
        <div id="calRangeSub" style="font-size:12px;color:#9ca3af;margin-top:2px;"></div>
    </div>
    <div class="cal-toolbar">
        <div class="appt-view-tabs">
            <a href="/queue" class="appt-view-tab"><i class="fas fa-list-ol"></i> List</a>
            <a class="appt-view-tab" data-view="day"><i class="fas fa-sun"></i> Day</a>
            <a class="appt-view-tab active" data-view="week"><i class="fas fa-calendar-week"></i> Week</a>
            <a class="appt-view-tab" data-view="month"><i class="fas fa-calendar-alt"></i> Month</a>
        </div>
        <div class="cal-nav">
            <button class="btn btn-secondary btn-sm" id="calPrev" title="Previous"><i class="fas fa-chevron-left"></i></button>
            <span class="cal-range" id="calRange">—</span>
            <button class="btn btn-secondary btn-sm" id="calNext" title="Next"><i class="fas fa-chevron-right"></i></button>
            <button class="btn btn-outline-primary btn-sm" id="calToday">Today</button>
        </div>
        <button class="btn btn-primary btn-sm" id="calBookBtn"><i class="fas fa-plus"></i> Book Appointment</button>
    </div>
</div>

<div class="cal-wrap">
    <div class="cal-loading" id="calLoading"><i class="fas fa-spinner fa-spin"></i>&nbsp; Loading…</div>
    <div class="cal-scroll"><div id="calBody"></div></div>
</div>

<div class="cal-legend">
    <span><i style="background:#f59e0b;"></i> Waiting</span>
    <span><i style="background:#22c55e;"></i> Arrived</span>
    <span><i style="background:#3b82f6;"></i> In Consult</span>
    <span><i style="background:#16a34a;"></i> Completed</span>
    <span><i style="background:#ef4444;"></i> Not Arrived</span>
    <span><i style="background:#9ca3af;"></i> Cancelled</span>
    <span style="margin-left:auto;"><i class="fas fa-info-circle"></i> Click any empty slot to book</span>
</div>

<!-- ── Book appointment modal ─────────────────────────────── -->
<div class="modal fade" id="bookModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="bookForm">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-calendar-plus"></i> Book Appointment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="bookAlert" class="alert" style="display:none;padding:7px 11px;font-size:12.5px;"></div>

          <ul class="nav nav-tabs" style="margin-bottom:12px;">
            <li class="nav-item"><a class="nav-link active" href="#" data-mode="existing">Existing Patient</a></li>
            <li class="nav-item"><a class="nav-link" href="#" data-mode="new">New Patient</a></li>
          </ul>

          <!-- Existing patient search -->
          <div id="modeExisting">
            <label class="form-label" style="font-size:12px;font-weight:600;">Search patient (name / phone / ID)</label>
            <input type="text" class="form-control form-control-sm" id="bookSearch" autocomplete="off" placeholder="Type at least 2 characters…">
            <div id="bookHits" style="display:none;border:1px solid #e5e7eb;border-radius:6px;margin-top:4px;max-height:180px;overflow:auto;"></div>
            <div id="bookPicked" style="display:none;margin-top:7px;font-size:12.5px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;padding:6px 9px;"></div>
          </div>

          <!-- New patient -->
          <div id="modeNew" style="display:none;">
            <div class="row g-2">
              <div class="col-7">
                <label class="form-label" style="font-size:12px;font-weight:600;">Patient name *</label>
                <input type="text" class="form-control form-control-sm" id="bookNewName">
              </div>
              <div class="col-5">
                <label class="form-label" style="font-size:12px;font-weight:600;">Phone</label>
                <input type="tel" class="form-control form-control-sm" id="bookNewPhone">
              </div>
            </div>
          </div>

          <hr style="margin:14px 0 12px;">

          <div class="row g-2">
            <div class="col-6">
              <label class="form-label" style="font-size:12px;font-weight:600;">Date *</label>
              <input type="date" class="form-control form-control-sm" id="bookDate" required>
            </div>
            <div class="col-6 d-flex align-items-end">
              <div class="form-check" style="font-size:12px;">
                <input class="form-check-input" type="checkbox" id="bookExtended" checked>
                <label class="form-check-label" for="bookExtended">Include extended hours</label>
              </div>
            </div>
          </div>

          <label class="form-label" style="font-size:12px;font-weight:600;margin-top:11px;">Time slot *</label>
          <div id="bookSlots" class="slot-pick"><span style="color:#9ca3af;font-size:12px;">Pick a date first.</span></div>

          <label class="form-label" style="font-size:12px;font-weight:600;margin-top:12px;">Chief complaint</label>
          <input type="text" class="form-control form-control-sm" id="bookComplaint" placeholder="Optional">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm" id="bookSubmit"><i class="fas fa-check"></i> Book</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── Appointment detail modal ───────────────────────────── -->
<div class="modal fade" id="apptModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" style="font-size:15px;"><i class="fas fa-user-clock"></i> Appointment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="apptBody" style="font-size:13px;"></div>
      <div class="modal-footer" id="apptActions" style="gap:6px;"></div>
    </div>
  </div>
</div>

<script>
(function () {
    const TODAY      = '<?php echo $today; ?>';
    const CAN_CONSULT = <?php echo $canConsult ? 'true' : 'false'; ?>;
    const DOW        = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    const MONTHS     = ['January','February','March','April','May','June',
                        'July','August','September','October','November','December'];
    const STATUS_LBL = {
        waiting:'Waiting', arrived:'Arrived', in_consultation:'In Consult',
        completed:'Completed', cancelled:'Cancelled', no_show:'Not Arrived'
    };

    // ── Date helpers (plain YYYY-MM-DD strings, no timezone drift) ──────────
    const pad = n => String(n).padStart(2, '0');
    const iso = d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    const parse = s => { const p = s.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); };
    const addDays = (s, n) => { const d = parse(s); d.setDate(d.getDate() + n); return iso(d); };
    const addMonths = (s, n) => { const d = parse(s); d.setDate(1); d.setMonth(d.getMonth() + n); return iso(d); };
    const mondayOf = s => { const d = parse(s); const w = (d.getDay() + 6) % 7; d.setDate(d.getDate() - w); return iso(d); };
    const fmtDay = s => { const d = parse(s); return DOW[d.getDay()] + ', ' + d.getDate() + ' ' + MONTHS[d.getMonth()].slice(0,3) + ' ' + d.getFullYear(); };
    const to12 = t => {
        if (!t) return '';
        const [h, m] = t.split(':').map(Number);
        const ap = h >= 12 ? 'PM' : 'AM';
        return ((h % 12) || 12) + ':' + pad(m) + ' ' + ap;
    };
    const esc = s => { const d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; };
    const nowIST = () => {
        const n = new Date();
        const i = new Date(n.getTime() + n.getTimezoneOffset() * 60000 + 5.5 * 3600000);
        return pad(i.getHours()) + ':' + pad(i.getMinutes());
    };

    // ── State ───────────────────────────────────────────────────────────────
    const params = new URLSearchParams(location.search);
    let view   = ['day','week','month'].includes(params.get('view')) ? params.get('view') : 'week';
    let anchor = /^\d{4}-\d{2}-\d{2}$/.test(params.get('date') || '') ? params.get('date') : TODAY;
    let feed   = { appointments: [], days: {}, max_per_slot: 1 };

    const bodyEl    = document.getElementById('calBody');
    const loadEl    = document.getElementById('calLoading');
    const rangeEl   = document.getElementById('calRange');
    const rangeSub  = document.getElementById('calRangeSub');
    const bookModal = new bootstrap.Modal(document.getElementById('bookModal'));
    const apptModal = new bootstrap.Modal(document.getElementById('apptModal'));

    function range() {
        if (view === 'day')  return { from: anchor, to: anchor };
        if (view === 'week') { const f = mondayOf(anchor); return { from: f, to: addDays(f, 6) }; }
        const d = parse(anchor);
        const first = iso(new Date(d.getFullYear(), d.getMonth(), 1));
        const last  = iso(new Date(d.getFullYear(), d.getMonth() + 1, 0));
        return { from: first, to: last };
    }

    // What to actually fetch. The month grid draws leading/trailing days from the
    // neighbouring months, so it needs a slightly wider window than range().
    function fetchRange() {
        const r = range();
        if (view !== 'month') return r;
        let from = mondayOf(r.from), to = r.to;
        while ((parse(to).getDay() + 6) % 7 !== 6) to = addDays(to, 1);
        return { from: from, to: to };
    }

    function rangeLabel() {
        const { from, to } = range();
        if (view === 'day')  return fmtDay(from);
        if (view === 'month') { const d = parse(from); return MONTHS[d.getMonth()] + ' ' + d.getFullYear(); }
        const a = parse(from), b = parse(to);
        return a.getDate() + ' ' + MONTHS[a.getMonth()].slice(0,3)
             + ' – ' + b.getDate() + ' ' + MONTHS[b.getMonth()].slice(0,3) + ' ' + b.getFullYear();
    }

    // Appointments grouped by date, and by date+time
    function indexAppts() {
        const byDate = {}, byCell = {};
        feed.appointments.forEach(a => {
            (byDate[a.date] = byDate[a.date] || []).push(a);
            const key = a.date + ' ' + (a.time || 'none');
            (byCell[key] = byCell[key] || []).push(a);
        });
        return { byDate, byCell };
    }

    function chipHtml(a, showTime) {
        return '<button type="button" class="appt-chip chip-' + a.status + '" data-appt="' + a.id + '">'
             + (showTime && a.time ? '<span class="chip-time">' + to12(a.time) + '</span>' : '')
             + '<span class="chip-name">' + esc(a.name) + '</span>'
             + '</button>';
    }

    // ── Renderers ───────────────────────────────────────────────────────────
    function render() {
        rangeEl.textContent  = rangeLabel();
        rangeSub.textContent = view === 'day' ? '' : (view === 'week' ? 'Week view' : 'Month view');
        document.querySelectorAll('.appt-view-tab[data-view]').forEach(t =>
            t.classList.toggle('active', t.dataset.view === view));
        bodyEl.innerHTML = view === 'month' ? renderMonth() : renderTimeGrid();
        wire();
    }

    // Day + week share the time-axis grid
    function renderTimeGrid() {
        const { from, to } = range();
        const days = [];
        for (let d = from; d <= to; d = addDays(d, 1)) days.push(d);

        // Time axis = every slot the visible days offer, plus any booked
        // off-slot time so nothing is ever hidden from the grid.
        const times = new Set();
        days.forEach(d => (feed.days[d]?.slots || []).forEach(t => times.add(t)));
        feed.appointments.forEach(a => { if (a.time) times.add(a.time); });
        const axis = [...times].sort();

        const { byCell, byDate } = indexAppts();

        // Walk-ins with no slot get their own row at the top
        const noSlot = feed.appointments.filter(a => !a.time);

        if (!axis.length && !noSlot.length) {
            return '<div class="cal-empty"><i class="fas fa-calendar-times" style="font-size:26px;display:block;margin-bottom:8px;"></i>'
                 + 'Clinic is closed for this ' + (view === 'day' ? 'day' : 'week') + '.</div>';
        }

        let h = '<table class="cal-grid"><thead><tr><th class="col-time">Time</th>';
        days.forEach(d => {
            const dt = parse(d);
            const n  = (byDate[d] || []).length;
            h += '<th class="' + (d === TODAY ? 'is-today' : '') + '">'
               + DOW[dt.getDay()] + '<span class="dnum">' + dt.getDate() + '</span>'
               + '<span style="font-size:10px;color:#9ca3af;letter-spacing:0;">'
               + (feed.days[d]?.closed ? 'Closed' : (n ? n + ' appt' + (n > 1 ? 's' : '') : '—'))
               + '</span></th>';
        });
        h += '</tr></thead><tbody>';

        if (noSlot.length) {
            h += '<tr><td class="time-cell">Walk-in</td>';
            days.forEach(d => {
                const list = noSlot.filter(a => a.date === d);
                h += '<td class="slot-cell off">' + list.map(a => chipHtml(a, false)).join('') + '</td>';
            });
            h += '</tr>';
        }

        const now = nowIST();
        axis.forEach(t => {
            h += '<tr><td class="time-cell">' + to12(t) + '</td>';
            days.forEach(d => {
                const day  = feed.days[d] || { closed: true, slots: [] };
                const list = byCell[d + ' ' + t] || [];
                const live = list.filter(a => a.status !== 'cancelled' && a.status !== 'no_show').length;
                const offered = (day.slots || []).includes(t);
                const isPast  = d < TODAY || (d === TODAY && t < now);
                const full    = live >= feed.max_per_slot;

                let cls = 'slot-cell';
                if (day.closed)          cls += ' closed-cell';
                else if (!offered)       cls += ' off';
                else if (full)           cls += ' full';
                if (isPast)              cls += ' past';

                const bookable = !day.closed && offered && !full;
                h += '<td class="' + cls + '"'
                   + (bookable ? ' data-book-date="' + d + '" data-book-time="' + t + '"' : '') + '>'
                   + list.map(a => chipHtml(a, false)).join('')
                   + (bookable && !list.length ? '<span class="add-hint"><i class="fas fa-plus"></i> Book</span>' : '')
                   + '</td>';
            });
            h += '</tr>';
        });
        return h + '</tbody></table>';
    }

    function renderMonth() {
        const { from, to } = range();
        const gridStart = mondayOf(from);
        // Always draw whole weeks so the month grid stays rectangular
        let gridEnd = to;
        while ((parse(gridEnd).getDay() + 6) % 7 !== 6) gridEnd = addDays(gridEnd, 1);

        const { byDate } = indexAppts();
        const month = parse(from).getMonth();

        let h = '<div class="cal-month">';
        ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'].forEach(d => h += '<div class="m-dow">' + d + '</div>');

        for (let d = gridStart; d <= gridEnd; d = addDays(d, 1)) {
            const dt      = parse(d);
            const outside = dt.getMonth() !== month;
            const day     = feed.days[d];
            const list    = (byDate[d] || []).sort((a, b) => (a.time || '~').localeCompare(b.time || '~'));
            const closed  = day ? day.closed : false;

            let cls = 'm-cell';
            if (outside) cls += ' outside';
            if (closed)  cls += ' closed-cell';
            if (d === TODAY) cls += ' is-today';

            h += '<div class="' + cls + '"' + (closed ? '' : ' data-book-date="' + d + '"') + '>'
               + '<div class="m-date"><span class="dd">' + dt.getDate() + '</span>'
               + (list.length ? '<span class="m-count">' + list.length + '</span>' : '')
               + '</div>';
            list.slice(0, 3).forEach(a => h += chipHtml(a, true));
            if (list.length > 3) {
                h += '<div class="m-more" data-goday="' + d + '">+' + (list.length - 3) + ' more</div>';
            }
            if (closed) h += '<div style="font-size:10px;color:#9ca3af;">Closed</div>';
            h += '</div>';
        }
        return h + '</div>';
    }

    // ── Interaction ─────────────────────────────────────────────────────────
    function wire() {
        bodyEl.querySelectorAll('.appt-chip').forEach(el =>
            el.addEventListener('click', e => {
                e.stopPropagation();
                showAppt(+el.dataset.appt);
            }));

        bodyEl.querySelectorAll('.m-more').forEach(el =>
            el.addEventListener('click', e => {
                e.stopPropagation();
                anchor = el.dataset.goday; view = 'day'; load();
            }));

        bodyEl.querySelectorAll('[data-book-date]').forEach(el =>
            el.addEventListener('click', () => openBook(el.dataset.bookDate, el.dataset.bookTime || '')));
    }

    function showAppt(id) {
        const a = feed.appointments.find(x => x.id === id);
        if (!a) return;
        const phone = a.phone ? '<a href="tel:' + esc(a.phone) + '">' + esc(a.phone) + '</a>' : '—';
        document.getElementById('apptBody').innerHTML =
            '<div style="font-size:15px;font-weight:700;margin-bottom:4px;">' + esc(a.name) + '</div>'
          + '<div style="color:#6b7280;margin-bottom:8px;">Token #' + a.token + ' · '
          + (a.type === 'walkin' ? 'Walk-in' : 'Booked') + '</div>'
          + '<div><strong>When:</strong> ' + fmtDay(a.date) + (a.time ? ' · ' + to12(a.time) : '') + '</div>'
          + '<div><strong>Phone:</strong> ' + phone + '</div>'
          + '<div><strong>Status:</strong> <span class="badge bg-secondary">'
          + (STATUS_LBL[a.status] || a.status) + '</span></div>'
          + (a.complaint ? '<div style="margin-top:6px;"><strong>Complaint:</strong> ' + esc(a.complaint) + '</div>' : '');

        const acts = [];
        if (a.status === 'waiting') {
            acts.push(btn('success', 'check-circle', 'Arrived', () => setStatus(a.id, 'arrived')));
            acts.push(btn('secondary', 'times', 'Cancel', () => setStatus(a.id, 'cancelled')));
        } else if (a.status === 'arrived' && CAN_CONSULT) {
            acts.push(btn('primary', 'stethoscope', 'Call', () => setStatus(a.id, 'in_consultation')));
        } else if (a.status === 'in_consultation' && CAN_CONSULT) {
            acts.push(btn('success', 'check', 'Finish', () => setStatus(a.id, 'completed')));
        }
        const box = document.getElementById('apptActions');
        box.innerHTML = '';
        acts.forEach(b => box.appendChild(b));
        if (a.patient_id) {
            const link = document.createElement('a');
            link.className = 'btn btn-secondary btn-sm';
            link.href = '/patient/' + a.patient_id;
            link.innerHTML = '<i class="fas fa-user"></i> Patient';
            box.appendChild(link);
        }
        apptModal.show();
    }

    function btn(kind, icon, label, fn) {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'btn btn-' + kind + ' btn-sm';
        b.innerHTML = '<i class="fas fa-' + icon + '"></i> ' + label;
        b.addEventListener('click', fn);
        return b;
    }

    function setStatus(id, status) {
        fetch('/api/appointment/' + id + '/status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'status=' + encodeURIComponent(status)
        })
        .then(r => r.json())
        .then(d => {
            if (!d.success) { alert('Error: ' + d.message); return; }
            apptModal.hide();
            if (status === 'in_consultation' && d.redirect) { location.href = d.redirect; return; }
            load();
        });
    }

    // ── Load feed ───────────────────────────────────────────────────────────
    function load() {
        const { from, to } = fetchRange();
        loadEl.classList.add('on');
        const url = new URL(location.href);
        url.searchParams.set('view', view);
        url.searchParams.set('date', anchor);
        history.replaceState(null, '', url);

        fetch('/api/calendar?from=' + from + '&to=' + to)
        .then(r => r.json())
        .then(d => {
            loadEl.classList.remove('on');
            if (!d.success) { bodyEl.innerHTML = '<div class="cal-empty">' + esc(d.message || 'Could not load calendar.') + '</div>'; return; }
            feed = d;
            render();
        })
        .catch(() => {
            loadEl.classList.remove('on');
            bodyEl.innerHTML = '<div class="cal-empty">Could not load the calendar. Check your connection and retry.</div>';
        });
    }

    // ── Toolbar ─────────────────────────────────────────────────────────────
    document.querySelectorAll('.appt-view-tab[data-view]').forEach(t =>
        t.addEventListener('click', () => { view = t.dataset.view; load(); }));
    document.getElementById('calPrev').addEventListener('click', () => {
        anchor = view === 'month' ? addMonths(anchor, -1) : addDays(anchor, view === 'day' ? -1 : -7);
        load();
    });
    document.getElementById('calNext').addEventListener('click', () => {
        anchor = view === 'month' ? addMonths(anchor, 1) : addDays(anchor, view === 'day' ? 1 : 7);
        load();
    });
    document.getElementById('calToday').addEventListener('click', () => { anchor = TODAY; load(); });
    document.getElementById('calBookBtn').addEventListener('click', () => openBook(anchor < TODAY ? TODAY : anchor, ''));

    // ── Booking modal ───────────────────────────────────────────────────────
    let bookMode = 'existing', pickedPatient = null, pickedSlot = '', wantSlot = '';

    function openBook(date, time) {
        pickedSlot = '';
        wantSlot   = time || '';
        document.getElementById('bookDate').value = date;
        document.getElementById('bookComplaint').value = '';
        alertBox('', '');
        setMode('existing');
        clearPatient();
        loadSlots();
        bookModal.show();
    }

    function alertBox(msg, kind) {
        const el = document.getElementById('bookAlert');
        if (!msg) { el.style.display = 'none'; return; }
        el.className = 'alert alert-' + kind;
        el.style.cssText = 'padding:7px 11px;font-size:12.5px;';
        el.textContent = msg;
        el.style.display = 'block';
    }

    function setMode(m) {
        bookMode = m;
        document.querySelectorAll('#bookModal .nav-link').forEach(n =>
            n.classList.toggle('active', n.dataset.mode === m));
        document.getElementById('modeExisting').style.display = m === 'existing' ? '' : 'none';
        document.getElementById('modeNew').style.display      = m === 'new' ? '' : 'none';
    }
    document.querySelectorAll('#bookModal .nav-link').forEach(n =>
        n.addEventListener('click', e => { e.preventDefault(); setMode(n.dataset.mode); }));

    function clearPatient() {
        pickedPatient = null;
        document.getElementById('bookSearch').value = '';
        document.getElementById('bookHits').style.display = 'none';
        document.getElementById('bookPicked').style.display = 'none';
        document.getElementById('bookNewName').value = '';
        document.getElementById('bookNewPhone').value = '';
    }

    let searchTimer;
    document.getElementById('bookSearch').addEventListener('input', function () {
        clearTimeout(searchTimer);
        const q = this.value.trim();
        if (q.length < 2) { document.getElementById('bookHits').style.display = 'none'; return; }
        searchTimer = setTimeout(() => {
            fetch('/api/patient/search?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(d => {
                const el = document.getElementById('bookHits');
                const list = (d.data || []).slice(0, 8);
                if (!d.success || !list.length) { el.style.display = 'none'; return; }
                el.innerHTML = '';
                list.forEach(p => {
                    const name = ((p.fname || '') + ' ' + (p.lname || '')).trim() || 'Unknown';
                    const row = document.createElement('div');
                    row.className = 'search-hit';
                    row.innerHTML = '<strong>' + esc(name) + '</strong> '
                                  + '<span style="color:#6b7280;">' + esc(p.contact_no || '') + '</span>'
                                  + '<span style="float:right;color:#9ca3af;">ID: ' + esc(p.patient_id || p.id) + '</span>';
                    row.addEventListener('click', () => {
                        pickedPatient = { id: p.id, name: name, phone: p.contact_no || '' };
                        const box = document.getElementById('bookPicked');
                        box.innerHTML = '<i class="fas fa-user-check" style="color:#16a34a;"></i> <strong>'
                                      + esc(name) + '</strong> &nbsp;' + esc(p.contact_no || '');
                        box.style.display = 'block';
                        el.style.display = 'none';
                        document.getElementById('bookSearch').value = name;
                    });
                    el.appendChild(row);
                });
                el.style.display = 'block';
            });
        }, 300);
    });

    function loadSlots() {
        const date = document.getElementById('bookDate').value;
        const area = document.getElementById('bookSlots');
        if (!date) { area.innerHTML = '<span style="color:#9ca3af;font-size:12px;">Pick a date first.</span>'; return; }
        area.innerHTML = '<span style="color:#9ca3af;font-size:12px;"><i class="fas fa-spinner fa-spin"></i> Loading slots…</span>';
        const ext = document.getElementById('bookExtended').checked ? '1' : '0';
        fetch('/api/slots?date=' + encodeURIComponent(date) + '&extended=' + ext)
        .then(r => r.json())
        .then(d => {
            if (d.closed) { area.innerHTML = '<span style="color:#ef4444;font-size:12px;"><i class="fas fa-ban"></i> Clinic is closed on this date.</span>'; return; }
            const isToday = date === TODAY, now = nowIST();
            const slots = (d.slots || []).filter(s => !(isToday && s.time <= now));
            if (!slots.length) { area.innerHTML = '<span style="color:#9ca3af;font-size:12px;">No slots left for this date.</span>'; return; }
            area.innerHTML = '';
            slots.forEach(s => {
                const b = document.createElement('div');
                b.className = 'sp' + (s.available ? '' : ' full');
                b.textContent = to12(s.time);
                if (s.available) {
                    b.addEventListener('click', () => {
                        area.querySelectorAll('.sp').forEach(x => x.classList.remove('sel'));
                        b.classList.add('sel');
                        pickedSlot = s.time;
                    });
                    if (wantSlot === s.time) { b.classList.add('sel'); pickedSlot = s.time; }
                }
                area.appendChild(b);
            });
            wantSlot = '';
        })
        .catch(() => { area.innerHTML = '<span style="color:#9ca3af;font-size:12px;">Could not load slots.</span>'; });
    }
    document.getElementById('bookDate').addEventListener('change', () => { pickedSlot = ''; loadSlots(); });
    document.getElementById('bookExtended').addEventListener('change', () => { pickedSlot = ''; loadSlots(); });

    document.getElementById('bookForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const date = document.getElementById('bookDate').value;
        if (!date)       { alertBox('Please choose a date.', 'danger'); return; }
        if (!pickedSlot) { alertBox('Please choose a time slot.', 'danger'); return; }

        const fd = new FormData();
        fd.append('appt_date', date);
        fd.append('slot_time', pickedSlot);
        fd.append('extended', document.getElementById('bookExtended').checked ? '1' : '0');
        fd.append('chief_complaint', document.getElementById('bookComplaint').value.trim());

        if (bookMode === 'existing') {
            if (!pickedPatient) { alertBox('Search and select a patient, or switch to New Patient.', 'danger'); return; }
            fd.append('patient_id', pickedPatient.id);
        } else {
            const name = document.getElementById('bookNewName').value.trim();
            if (!name) { alertBox('Patient name is required.', 'danger'); return; }
            fd.append('patient_name', name);
            fd.append('patient_phone', document.getElementById('bookNewPhone').value.trim());
        }

        const submit = document.getElementById('bookSubmit');
        submit.disabled = true;
        fetch('/api/appointment/book', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            submit.disabled = false;
            if (!d.success) { alertBox(d.message || 'Could not book this slot.', 'danger'); return; }
            bookModal.hide();
            load();
        })
        .catch(() => { submit.disabled = false; alertBox('Network error — please try again.', 'danger'); });
    });

    load();
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
