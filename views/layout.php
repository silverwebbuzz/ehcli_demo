<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'Dr. Feelgood'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/css/style.css?v=<?php echo @filemtime(dirname(__DIR__).'/css/style.css') ?: time(); ?>" rel="stylesheet">
    <link href="/css/datatable.css" rel="stylesheet">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/png" href="/assets/logo/favicon-32.png">
    <link rel="apple-touch-icon" href="/assets/logo/apple-touch-icon.png">
<meta name="theme-color" content="#0d6efd">
</head>
<body>
    <?php
    $layoutRole   = (isset($_SESSION['role']) && $_SESSION['role'] !== '') ? $_SESSION['role'] : 'doctor';
    $isDoctor     = $layoutRole === 'doctor';
    $isAsstDoctor = $layoutRole === 'asst_doctor';
    $isReception  = $layoutRole === 'reception';
    $canReports   = $isDoctor;
    $uri          = $_SERVER['REQUEST_URI'];
    ?>
    <div class="app-wrapper">

        <!-- ── HEADER ─────────────────────────────────── -->
        <header class="app-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="<?php echo $isAsstDoctor ? '/queue' : '/dashboard'; ?>" class="app-brand">
                    <img src="/assets/logo/app-logo.svg" alt="Homeopathy Clinic Management" class="app-brand-logo">
                    <span>Homeopathy Clinic Management</span>
                </a>
            </div>

            <div class="header-user">
                <!-- Offline sync status: hidden until there is something queued -->
                <button type="button" id="syncBadge" class="btn btn-sm" style="display:none;margin-right:8px;"
                        title="Records waiting to sync" data-bs-toggle="modal" data-bs-target="#syncModal">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span id="syncBadgeText">0</span>
                </button>
                <div class="header-name">
                    <span class="full-name"><?php echo htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username'] ?? 'User'); ?></span>
                    <span class="role-pill"><?php echo htmlspecialchars(\App\Models\User::roleLabel($layoutRole)); ?></span>
                </div>
                <a href="/logout" class="btn btn-secondary btn-sm">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="logout-label">Logout</span>
                </a>
            </div>
        </header>

        <!-- ── OVERLAY (mobile drawer backdrop) ──────── -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <div class="app-container">

            <!-- ── SIDEBAR ───────────────────────────── -->
            <aside class="app-sidebar" id="appSidebar">
                <nav>
                    <ul class="sidebar-menu">

                        <?php if (!$isAsstDoctor): ?>
                        <li>
                            <a href="/dashboard" class="<?php echo strpos($uri, 'dashboard') !== false ? 'active' : ''; ?>">
                                <i class="fas fa-th-large"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>

                        <li>
                            <a href="/patients" class="<?php echo (strpos($uri, 'patient') !== false && strpos($uri, 'reports') === false) ? 'active' : ''; ?>">
                                <i class="fas fa-users"></i>
                                <span>Patients</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <li>
                            <a href="/queue" class="<?php echo (strpos($uri, 'queue') !== false || strpos($uri, 'walkin') !== false) ? 'active' : ''; ?>">
                                <i class="fas fa-calendar-check"></i>
                                <span>Appointments</span>
                            </a>
                        </li>

                        <?php if ($canReports): ?>
                        <?php $onReports = strpos($uri, '/reports') !== false; ?>
                        <li class="has-submenu <?php echo $onReports ? 'open' : ''; ?>">
                            <a href="#" class="<?php echo $onReports ? 'active' : ''; ?>" onclick="toggleSubmenu(this);return false;">
                                <i class="fas fa-chart-bar"></i>
                                <span>Reports</span>
                                <i class="fas fa-chevron-down submenu-arrow"></i>
                            </a>
                            <ul class="submenu">
                                <li><a href="/reports/patients"     class="<?php echo strpos($uri,'/reports/patients')!==false?'active':''; ?>"><i class="fas fa-users"></i> Patients</a></li>
                                <li><a href="/reports/expenses"     class="<?php echo strpos($uri,'/reports/expenses')!==false?'active':''; ?>"><i class="fas fa-wallet"></i> Expenses</a></li>
                                <li><a href="/reports/gst"          class="<?php echo strpos($uri,'/reports/gst')!==false?'active':''; ?>"><i class="fas fa-file-invoice-dollar"></i> GST</a></li>
                                <!--li><a href="/reports/income"       class="<?php echo strpos($uri,'/reports/income')!==false?'active':''; ?>"><i class="fas fa-rupee-sign"></i> Income</a></li>
                                <li><a href="/reports/queue"        class="<?php echo strpos($uri,'/reports/queue')!==false?'active':''; ?>"><i class="fas fa-list-ol"></i> Queue / Ops</a></li>
                                <li><a href="/reports/medicines"    class="<?php echo strpos($uri,'/reports/medicines')!==false?'active':''; ?>"><i class="fas fa-pills"></i> Medicines</a></li>
                                <li><a href="/reports/productivity" class="<?php echo strpos($uri,'/reports/productivity')!==false?'active':''; ?>"><i class="fas fa-stethoscope"></i> Productivity</a></li-->
                            </ul>
                        </li>
                        <?php endif; ?>

                        <?php if ($isDoctor): ?>
                        <li>
                            <a href="/expenses" class="<?php echo (strpos($uri, '/expenses') !== false && strpos($uri, '/reports') === false) ? 'active' : ''; ?>">
                                <i class="fas fa-wallet"></i>
                                <span>Expenses</span>
                            </a>
                        </li>
                        <li>
                            <a href="/users" class="<?php echo strpos($uri, '/users') !== false ? 'active' : ''; ?>">
                                <i class="fas fa-users-cog"></i>
                                <span>Users</span>
                            </a>
                        </li>
                        <li>
                            <a href="/clinic-settings" class="<?php echo strpos($uri, 'clinic-settings') !== false ? 'active' : ''; ?>">
                                <i class="fas fa-cog"></i>
                                <span>Settings</span>
                            </a>
                        </li>
                        <li>
                            <a href="/help" class="<?php echo strpos($uri, '/help') !== false ? 'active' : ''; ?>">
                                <i class="fas fa-book-open"></i>
                                <span>Help</span>
                            </a>
                        </li>
                        <?php endif; ?>

                    </ul>
                </nav>
            </aside>

            <!-- ── MAIN CONTENT ──────────────────────── -->
            <main class="app-content">
                <?php echo $content; ?>
            </main>

        </div><!-- /.app-container -->
    </div><!-- /.app-wrapper -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    (function () {
        const sidebar = document.getElementById('appSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggle  = document.getElementById('sidebarToggle');

        function openSidebar() {
            sidebar.classList.add('open');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        toggle.addEventListener('click', function () {
            sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
        });

        overlay.addEventListener('click', closeSidebar);

        // Close drawer on nav link click (mobile)
        sidebar.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', function () {
                if (window.innerWidth <= 768) closeSidebar();
            });
        });

        // Close drawer on window resize to desktop
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) closeSidebar();
        });
    })();

    function toggleSubmenu(el) {
        el.closest('.has-submenu').classList.toggle('open');
    }
    </script>
    <!-- Offline-first: IndexedDB outbox + sync client (load before SW registration) -->
    <script src="/assets/js/offline/idb-core.js"></script>
    <script src="/assets/js/offline/offline-client.js"></script>

    <!-- ── Offline sync review modal ─────────────────────────── -->
    <div class="modal fade" id="syncModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-cloud-upload-alt"></i> Offline sync queue</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div id="syncQueueList" class="d-flex flex-column gap-2"></div>
            <p id="syncQueueEmpty" class="text-muted text-center my-3" style="display:none;">
              <i class="fas fa-check-circle text-success"></i> Everything is synced.
            </p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-primary btn-sm" id="syncRetryAllBtn">
              <i class="fas fa-sync"></i> Retry all
            </button>
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <script>
    (function () {
        if (!window.Offline) return;
        const badge = document.getElementById('syncBadge');
        const badgeText = document.getElementById('syncBadgeText');
        const listEl = document.getElementById('syncQueueList');
        const emptyEl = document.getElementById('syncQueueEmpty');

        function esc(s) { const d = document.createElement('div'); d.textContent = String(s == null ? '' : s); return d.innerHTML; }

        // One-line summary of what a queued record represents.
        function summarize(rec) {
            const d = rec.payload || {};
            if (rec.entity === 'report') {
                const meds = d.medicins ? d.medicins : (d.notes || d.reports_notes || 'Visit');
                return 'Visit — ' + esc(meds.slice(0, 60));
            }
            return esc(rec.entity);
        }

        function statusPill(rec) {
            const map = {
                pending: ['#0d6efd', 'Pending'],
                failed:  ['#dc3545', 'Failed'],
                synced:  ['#198754', 'Synced'],
            };
            const s = map[rec.status] || ['#6c757d', rec.status];
            return '<span style="background:' + s[0] + ';color:#fff;font-size:.68rem;font-weight:700;'
                 + 'padding:2px 8px;border-radius:10px;text-transform:uppercase;">' + s[1] + '</span>';
        }

        async function refresh() {
            const queue = await Offline.getQueue();
            const active = queue.filter(function (r) { return r.status !== 'synced'; });
            const failed = active.filter(function (r) { return r.status === 'failed'; });

            // Header badge
            if (active.length > 0) {
                badge.style.display = '';
                badgeText.textContent = active.length;
                // Amber when anything failed, otherwise blue.
                badge.className = 'btn btn-sm ' + (failed.length ? 'btn-warning' : 'btn-outline-primary');
            } else {
                badge.style.display = 'none';
            }

            // Modal list
            listEl.innerHTML = '';
            if (!active.length) { emptyEl.style.display = ''; return; }
            emptyEl.style.display = 'none';

            active.forEach(function (rec) {
                const when = new Date(rec.createdAt).toLocaleString('en-IN',
                    { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
                const errHtml = rec.lastError
                    ? '<div class="small text-danger mt-1">' + esc(rec.lastError)
                      + (rec.attempts ? ' (attempt ' + rec.attempts + ')' : '') + '</div>' : '';
                const row = document.createElement('div');
                row.className = 'border rounded p-2';
                row.innerHTML =
                    '<div class="d-flex justify-content-between align-items-start">'
                  +   '<div><div class="fw-semibold">' + summarize(rec) + '</div>'
                  +     '<div class="small text-muted">' + esc(when) + '</div>' + errHtml + '</div>'
                  +   '<div class="text-end">' + statusPill(rec) + '</div>'
                  + '</div>'
                  + '<div class="d-flex gap-2 mt-2">'
                  +   '<button class="btn btn-sm btn-primary js-retry"><i class="fas fa-sync"></i> Retry</button>'
                  +   '<button class="btn btn-sm btn-outline-danger js-discard"><i class="fas fa-trash"></i> Discard</button>'
                  + '</div>';
                row.querySelector('.js-retry').addEventListener('click', function () {
                    this.disabled = true; Offline.retry(rec.uuid);
                });
                row.querySelector('.js-discard').addEventListener('click', function () {
                    if (confirm('Discard this record? It will NOT be saved to the server.')) Offline.remove(rec.uuid);
                });
                listEl.appendChild(row);
            });
        }

        document.getElementById('syncRetryAllBtn').addEventListener('click', async function () {
            this.disabled = true;
            const queue = await Offline.getQueue();
            for (const r of queue) { if (r.status !== 'synced') await Offline.retry(r.uuid); }
            this.disabled = false;
        });

        window.addEventListener('outbox:changed', refresh);
        window.addEventListener('outbox:synced', refresh);
        window.addEventListener('load', refresh);
    })();
    </script>

    <script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('/service-worker.js');
    });
}
</script>
<button id="installBtn" style="display:none;">
Install App
</button>

<script>
let deferredPrompt;

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    document.getElementById('installBtn').style.display = 'block';
});

document.getElementById('installBtn').addEventListener('click', async () => {
    deferredPrompt.prompt();
});
</script>
</body>
</html>
