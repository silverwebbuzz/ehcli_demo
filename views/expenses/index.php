<?php
/**
 * Clinic Expense Manager — add / edit / delete + filterable list.
 * Expects: $expenses (array), $_GET filters (from/to/category).
 */
use App\Models\Expense;

ob_start();
$page_title = 'Expense Manager';

$categories = Expense::CATEGORIES;
$modes      = Expense::PAYMENT_MODES;

$fFrom = htmlspecialchars($_GET['from'] ?? '');
$fTo   = htmlspecialchars($_GET['to'] ?? '');
$fCat  = $_GET['category'] ?? '';

$listTotal = 0.0;
foreach ($expenses as $e) { $listTotal += (float)$e['amount']; }

function expModeBadge($m) {
    $colors = ['cash'=>'#16a34a','card'=>'#2563eb','upi'=>'#7c3aed','bank'=>'#0891b2','cheque'=>'#d97706'];
    $c = $colors[$m] ?? '#6b7280';
    return '<span style="font-size:11px;font-weight:600;color:'.$c.';background:'.$c.'14;padding:2px 8px;border-radius:20px;text-transform:uppercase;">'.htmlspecialchars($m).'</span>';
}
?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
    <h1 class="page-title"><i class="fas fa-wallet"></i> Clinic Expense Manager</h1>
    <a href="/reports/expenses" class="btn btn-secondary btn-sm"><i class="fas fa-chart-pie"></i> Expense Reports</a>
</div>

<div id="expenseAlert" style="display:none;align-items:center;gap:10px;border-radius:8px;padding:11px 16px;margin-bottom:14px;font-size:.92rem;"></div>

<div class="row">
    <!-- ── Add / Edit form ── -->
    <div class="col-lg-4">
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header" id="formTitle"><i class="fas fa-plus-circle"></i> Add Expense</div>
            <div class="card-body">
                <form id="expenseForm">
                    <input type="hidden" id="expenseId" value="">
                    <div class="mb-3">
                        <label class="form-label">Date *</label>
                        <input type="date" class="form-control" id="expense_date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category *</label>
                        <select class="form-control" id="category" name="category" required>
                            <option value="">— select —</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (₹) *</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="amount" name="amount" placeholder="0.00" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Mode</label>
                        <select class="form-control" id="payment_mode" name="payment_mode">
                            <?php foreach ($modes as $m): ?>
                                <option value="<?php echo $m; ?>"><?php echo ucfirst($m); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Vendor / Paid to</label>
                        <input type="text" class="form-control" id="vendor" name="vendor" placeholder="Optional">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description / Note</label>
                        <textarea class="form-control" id="description" name="description" rows="2" placeholder="Optional"></textarea>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button type="submit" class="btn btn-primary" id="saveBtn" style="flex:1;"><i class="fas fa-save"></i> Save</button>
                        <button type="button" class="btn btn-secondary" id="cancelEdit" style="display:none;" onclick="resetExpenseForm()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ── List ── -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                <span><i class="fas fa-list"></i> Expenses</span>
                <span style="font-size:.9rem;color:#374151;">Total: <strong>₹<?php echo number_format($listTotal, 2); ?></strong> · <?php echo count($expenses); ?> entries</span>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <form method="GET" action="/expenses" style="display:flex;gap:8px;flex-wrap:wrap;align-items:end;margin-bottom:14px;">
                    <div>
                        <label class="form-label" style="font-size:11px;">From</label>
                        <input type="date" name="from" value="<?php echo $fFrom; ?>" class="form-control form-control-sm" style="width:150px;">
                    </div>
                    <div>
                        <label class="form-label" style="font-size:11px;">To</label>
                        <input type="date" name="to" value="<?php echo $fTo; ?>" class="form-control form-control-sm" style="width:150px;">
                    </div>
                    <div>
                        <label class="form-label" style="font-size:11px;">Category</label>
                        <select name="category" class="form-control form-control-sm" style="width:170px;">
                            <option value="">All</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $fCat === $c ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <?php if ($fFrom || $fTo || $fCat): ?><a href="/expenses" class="btn btn-secondary btn-sm">Clear</a><?php endif; ?>
                </form>

                <div style="overflow-x:auto;">
                <table class="table" style="width:100%;">
                    <thead>
                        <tr style="text-align:left;font-size:12px;color:#6b7280;border-bottom:2px solid #e5e7eb;">
                            <th style="padding:8px;">Date</th>
                            <th style="padding:8px;">Category</th>
                            <th style="padding:8px;">Details</th>
                            <th style="padding:8px;">Mode</th>
                            <th style="padding:8px;text-align:right;">Amount</th>
                            <th style="padding:8px;text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="expenseRows">
                        <?php if (empty($expenses)): ?>
                        <tr><td colspan="6" style="text-align:center;color:#9ca3af;padding:30px;">No expenses found for this filter.</td></tr>
                        <?php else: foreach ($expenses as $e): ?>
                        <tr data-id="<?php echo (int)$e['id']; ?>" style="border-bottom:1px solid #f3f4f6;font-size:.9rem;">
                            <td style="padding:8px;white-space:nowrap;"><?php echo date('d M Y', strtotime($e['expense_date'])); ?></td>
                            <td style="padding:8px;"><?php echo htmlspecialchars($e['category']); ?></td>
                            <td style="padding:8px;color:#4b5563;">
                                <?php echo htmlspecialchars($e['vendor'] ?? ''); ?>
                                <?php if (!empty($e['description'])): ?>
                                    <span style="color:#9ca3af;font-size:12px;"><?php echo ($e['vendor'] ? ' · ' : '') . htmlspecialchars($e['description']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:8px;"><?php echo expModeBadge($e['payment_mode']); ?></td>
                            <td style="padding:8px;text-align:right;font-weight:600;">₹<?php echo number_format((float)$e['amount'], 2); ?></td>
                            <td style="padding:8px;text-align:center;white-space:nowrap;">
                                <button class="btn btn-secondary btn-sm" title="Edit"
                                    onclick='editExpense(<?php echo json_encode($e, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" title="Delete" onclick="deleteExpense(<?php echo (int)$e['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function expenseAlert(msg, ok) {
    var a = document.getElementById('expenseAlert');
    a.style.display = 'flex';
    a.style.background = ok ? '#f0fdf4' : '#fef2f2';
    a.style.color = ok ? '#166534' : '#b91c1c';
    a.style.border = '1px solid ' + (ok ? '#86efac' : '#fca5a5');
    a.innerHTML = '<i class="fas fa-' + (ok ? 'check-circle' : 'exclamation-circle') + '"></i>' + msg;
    if (ok) setTimeout(function () { a.style.display = 'none'; }, 2500);
}

function resetExpenseForm() {
    document.getElementById('expenseForm').reset();
    document.getElementById('expenseId').value = '';
    document.getElementById('expense_date').value = '<?php echo date('Y-m-d'); ?>';
    document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Add Expense';
    document.getElementById('cancelEdit').style.display = 'none';
}

function editExpense(e) {
    document.getElementById('expenseId').value = e.id;
    document.getElementById('expense_date').value = e.expense_date;
    document.getElementById('category').value = e.category;
    document.getElementById('amount').value = e.amount;
    document.getElementById('payment_mode').value = e.payment_mode;
    document.getElementById('vendor').value = e.vendor || '';
    document.getElementById('description').value = e.description || '';
    document.getElementById('formTitle').innerHTML = '<i class="fas fa-pen"></i> Edit Expense #' + e.id;
    document.getElementById('cancelEdit').style.display = '';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

document.getElementById('expenseForm').addEventListener('submit', function (ev) {
    ev.preventDefault();
    var id = document.getElementById('expenseId').value;
    var url = id ? '/api/expenses/' + id + '/update' : '/api/expenses/create';
    var btn = document.getElementById('saveBtn');
    btn.disabled = true; btn.innerHTML = 'Saving…';
    fetch(url, { method: 'POST', body: new FormData(this) })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.success) { location.reload(); }
            else { expenseAlert(res.message || 'Could not save.', false); btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Save'; }
        })
        .catch(function () { expenseAlert('Network error.', false); btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Save'; });
});

function deleteExpense(id) {
    if (!confirm('Delete this expense?')) return;
    fetch('/api/expenses/' + id + '/delete', { method: 'POST' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.success) { location.reload(); }
            else { expenseAlert(res.message || 'Could not delete.', false); }
        });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
