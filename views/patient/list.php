<?php
ob_start();
$page_title = 'Patients - Dr. Feelgood';

function fmtDate($value) {
    if (!$value || $value === '0000-00-00' || strpos($value,'0000') === 0 || $value === '1970-01-01') return 'N/A';
    $ts = strtotime($value);
    return $ts ? date('d M Y', $ts) : 'N/A';
}
function fmtName($f, $l) {
    $full = trim(trim($f??'').' '.trim($l??''));
    return $full === '' ? 'N/A' : $full;
}

$mrgMap = ['S'=>'Single','M'=>'Married','D'=>'Divorced','W'=>'Widowed'];
?>

<!-- PAGE HEADER -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-users"></i> Patient Management
    </h1>
</div>

<?php if (($_GET['created'] ?? '') === '1'): ?>
<div id="patientCreatedBanner" style="display:flex;align-items:center;gap:10px;background:#dcfce7;color:#166534;border:1px solid #86efac;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:0.95rem;">
    <i class="fas fa-check-circle"></i>
    <span style="flex:1;">Patient created successfully.</span>
    <span onclick="document.getElementById('patientCreatedBanner').remove()" style="cursor:pointer;font-size:1.1rem;line-height:1;opacity:0.7;">&times;</span>
</div>
<script>
    // Clean the ?created=1 from the URL so a refresh doesn't re-show the banner
    if (window.history.replaceState) {
        window.history.replaceState({}, document.title, '/patients');
    }
</script>
<?php endif; ?>

<!-- DATATABLE SECTION -->
<div class="datatable-container">

    <!-- HEADER WITH SEARCH & CONTROLS -->
    <div class="datatable-header">
        <div class="datatable-search">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control" id="tableSearch"
                       placeholder="Search by name, contact, or patient ID...">
            </div>
        </div>
        <a href="/patient/create" class="btn btn-primary btn-sm" style="white-space:nowrap;">
            <i class="fas fa-user-plus"></i> New Patient
        </a>
    </div>

    <!-- TABLE WRAPPER -->
    <div class="datatable-table-wrapper">
        <table class="datatable-table" id="patientsTable">
            <thead>
                <tr>
                    <th class="sortable" data-col="patient_id">Patient ID</th>
                    <th class="sortable" data-col="name">Name</th>
                    <th class="sortable" data-col="contact_no">Contact</th>
                    <th class="sortable" data-col="gender">Gender</th>
                    <th class="sortable" data-col="age">Age</th>
                    <th class="sortable" data-col="mrg_status">Mrg. Status</th>
                    <th class="sortable" data-col="dor">Date of Reg.</th>
                    <th style="text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <!-- Initial rows rendered server-side — no flash -->
                <?php foreach ($initialRows as $p): ?>
                <tr>
                    <td><code><?php echo htmlspecialchars($p['patient_id'] ?? $p['id']); ?></code></td>
                    <td><strong><?php echo htmlspecialchars(fmtName($p['fname']??'',$p['lname']??'')); ?></strong></td>
                    <td>
                        <?php $c=trim($p['contact_no']??''); ?>
                        <?php if($c!==''): ?><a href="tel:<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></a>
                        <?php else: ?><span style="color:var(--gray-400);">N/A</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if(($p['gender']??'')==='M'): ?>
                            <span class="badge badge-male"><i class="fas fa-mars"></i> Male</span>
                        <?php elseif(($p['gender']??'')==='F'): ?>
                            <span class="badge badge-female"><i class="fas fa-venus"></i> Female</span>
                        <?php else: ?><span style="color:var(--gray-400);">N/A</span><?php endif; ?>
                    </td>
                    <td><?php $age=(int)($p['age']??0); echo $age>0 ? htmlspecialchars($age).' yrs' : '<span style="color:var(--gray-400);">N/A</span>'; ?></td>
                    <td><?php $m=$mrgMap[$p['mrg_status']??'']??''; echo $m!=='' ? htmlspecialchars($m) : '<span style="color:var(--gray-400);">N/A</span>'; ?></td>
                    <td><?php echo htmlspecialchars(fmtDate($p['dor']??'')); ?></td>
                    <td style="text-align:center;">
                        <a href="/patient/<?php echo $p['id']; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($initialRows)): ?>
                <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--gray-500);">No patients found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- FOOTER WITH PAGINATION & INFO -->
    <div class="datatable-footer">
        <div class="datatable-info">
            Showing <span id="startEntry">1</span>–<span id="endEntry"><?php echo min(10, $totalPatients); ?></span>
            of <span id="totalEntries"><?php echo $totalPatients; ?></span> patients
        </div>
        <div class="datatable-controls">
            <div class="datatable-entries-select">
                <label for="entriesPerPage">Show</label>
                <select id="entriesPerPage">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>entries</span>
            </div>
        </div>
        <div class="datatable-pagination" id="pagination"></div>
    </div>

</div>

<script>
(function () {

    // ── State ──────────────────────────────────────────────────────────
    let currentPage  = 1;
    let limit        = 10;
    let search       = '';
    let total        = <?php echo (int)$totalPatients; ?>;
    let searchTimer  = null;
    let loading      = false;

    const tbody      = document.getElementById('tableBody');
    const searchEl   = document.getElementById('tableSearch');
    const limitEl    = document.getElementById('entriesPerPage');
    const startEl    = document.getElementById('startEntry');
    const endEl      = document.getElementById('endEntry');
    const totalEl    = document.getElementById('totalEntries');
    const pagEl      = document.getElementById('pagination');

    const mrgMap = {S:'Single',M:'Married',D:'Divorced',W:'Widowed'};

    // ── Render initial pagination (no AJAX needed) ──────────────────
    renderPagination();

    // ── Events ─────────────────────────────────────────────────────────
    searchEl.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            search = searchEl.value.trim();
            currentPage = 1;
            fetchPage();
        }, 350); // debounce 350ms
    });

    limitEl.addEventListener('change', function () {
        limit = parseInt(this.value);
        currentPage = 1;
        fetchPage();
    });

    // ── Fetch page from server ──────────────────────────────────────────
    function fetchPage() {
        if (loading) return;
        loading = true;
        setLoading(true);

        const url = '/api/patients?page=' + currentPage +
                    '&limit=' + limit +
                    '&search=' + encodeURIComponent(search);

        fetch(url)
            .then(r => r.json())
            .then(function (res) {
                if (!res.success) throw new Error('Failed');
                total = res.total;
                renderRows(res.data);
                renderInfo();
                renderPagination();
            })
            .catch(function () {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:30px;color:var(--danger);">Error loading data. Please refresh.</td></tr>';
            })
            .finally(function () {
                loading = false;
                setLoading(false);
            });
    }

    // ── Render rows ─────────────────────────────────────────────────────
    function renderRows(rows) {
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--gray-500);">No patients found</td></tr>';
            return;
        }
        tbody.innerHTML = rows.map(function (p) {
            const name    = ((p.fname||'') + ' ' + (p.lname||'')).trim() || 'N/A';
            const contact = (p.contact_no||'').trim();
            const age     = parseInt(p.age) || 0;
            const mrg     = mrgMap[p.mrg_status] || 'N/A';
            const dor     = fmtDate(p.dor || '');
            const pid     = esc(p.patient_id || p.id);

            let genderBadge = '<span style="color:var(--gray-400);">N/A</span>';
            if (p.gender === 'M') genderBadge = '<span class="badge badge-male"><i class="fas fa-mars"></i> Male</span>';
            else if (p.gender === 'F') genderBadge = '<span class="badge badge-female"><i class="fas fa-venus"></i> Female</span>';

            return '<tr>' +
                '<td><code>' + pid + '</code></td>' +
                '<td><strong>' + esc(name) + '</strong></td>' +
                '<td>' + (contact ? '<a href="tel:' + esc(contact) + '">' + esc(contact) + '</a>' : '<span style="color:var(--gray-400);">N/A</span>') + '</td>' +
                '<td>' + genderBadge + '</td>' +
                '<td>' + (age > 0 ? age + ' yrs' : '<span style="color:var(--gray-400);">N/A</span>') + '</td>' +
                '<td>' + esc(mrg) + '</td>' +
                '<td>' + esc(dor) + '</td>' +
                '<td style="text-align:center;"><a href="/patient/' + p.id + '" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i> View</a></td>' +
                '</tr>';
        }).join('');
    }

    // ── Info bar ────────────────────────────────────────────────────────
    function renderInfo() {
        const start = total === 0 ? 0 : (currentPage - 1) * limit + 1;
        const end   = Math.min(currentPage * limit, total);
        startEl.textContent = start;
        endEl.textContent   = end;
        totalEl.textContent = total;
    }

    // ── Pagination ──────────────────────────────────────────────────────
    function renderPagination() {
        const totalPages = Math.max(1, Math.ceil(total / limit));
        let html = '';

        // Prev
        html += currentPage > 1
            ? '<a href="#" data-page="' + (currentPage-1) + '"><i class="fas fa-chevron-left"></i></a>'
            : '<span class="disabled"><i class="fas fa-chevron-left"></i></span>';

        // Pages with ellipsis
        for (let i = 1; i <= totalPages; i++) {
            if (i === currentPage) {
                html += '<span class="active">' + i + '</span>';
            } else if (i <= 2 || i > totalPages - 2 || (i >= currentPage - 1 && i <= currentPage + 1)) {
                html += '<a href="#" data-page="' + i + '">' + i + '</a>';
            } else if (i === 3 || i === totalPages - 2) {
                html += '<span>...</span>';
            }
        }

        // Next
        html += currentPage < totalPages
            ? '<a href="#" data-page="' + (currentPage+1) + '"><i class="fas fa-chevron-right"></i></a>'
            : '<span class="disabled"><i class="fas fa-chevron-right"></i></span>';

        pagEl.innerHTML = html;

        // Attach click handlers
        pagEl.querySelectorAll('a[data-page]').forEach(function (a) {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                const p = parseInt(this.dataset.page);
                if (p !== currentPage) {
                    currentPage = p;
                    fetchPage();
                }
            });
        });
    }

    // ── Loading state ───────────────────────────────────────────────────
    function setLoading(on) {
        tbody.style.opacity = on ? '0.4' : '1';
    }

    // ── Helpers ─────────────────────────────────────────────────────────
    function esc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function fmtDate(v) {
        if (!v || v === '0000-00-00' || v === '1970-01-01') return 'N/A';
        const d = new Date(v);
        if (isNaN(d)) return v;
        return d.getDate().toString().padStart(2,'0') + ' ' +
               ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][d.getMonth()] + ' ' +
               d.getFullYear();
    }

})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
