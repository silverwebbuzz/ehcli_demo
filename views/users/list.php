<?php
$page_title = 'User Management';
ob_start();

use App\Models\User;

$currentId = $_SESSION['user_id'] ?? 0;

$roleColors = [
    'doctor'      => ['bg'=>'#eff6ff','color'=>'#2563eb'],
    'asst_doctor' => ['bg'=>'#f0fdf4','color'=>'#16a34a'],
    'reception'   => ['bg'=>'#fefce8','color'=>'#ca8a04'],
];
?>
<style>
.um-table { width:100%; border-collapse:collapse; }
.um-table th { padding:10px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--gray-500); background:var(--gray-50); border-bottom:2px solid var(--gray-200); text-align:left; white-space:nowrap; }
.um-table td { padding:11px 14px; border-bottom:1px solid var(--gray-100); font-size:13px; vertical-align:middle; }
.um-table tr:last-child td { border-bottom:none; }
.um-table tr:hover td { background:var(--gray-50); }
.role-badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
.status-dot { display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:5px; }
.um-avatar { width:34px; height:34px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; color:#fff; flex-shrink:0; }
.you-tag { font-size:10px; background:#f3f4f6; color:#6b7280; border-radius:4px; padding:1px 6px; margin-left:5px; }
@media(max-width:600px){ .um-table th.hide-sm, .um-table td.hide-sm { display:none; } }
</style>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--gray-200);">
    <h1 class="page-title" style="margin:0;"><i class="fas fa-users-cog"></i> User Management</h1>
    <button class="btn btn-primary btn-sm" onclick="openModal()">
        <i class="fas fa-user-plus"></i> Add User
    </button>
</div>

<div id="alertBox" style="display:none;margin-bottom:12px;"></div>

<div class="card" style="margin-bottom:0;">
    <div style="overflow-x:auto;">
    <table class="um-table">
        <thead>
            <tr>
                <th style="width:40px;"></th>
                <th>Name</th>
                <th class="hide-sm">Username</th>
                <th class="hide-sm">Contact</th>
                <th>Role</th>
                <th>Status</th>
                <th style="width:110px;">Actions</th>
            </tr>
        </thead>
        <tbody id="userTableBody">
        <?php foreach ($users as $u):
            $isYou = (int)$u['id'] === (int)$currentId;
            $rc = $roleColors[$u['role']] ?? ['bg'=>'#f3f4f6','color'=>'#374151'];
            $initials = strtoupper(substr($u['fname'],0,1) . substr($u['lname'] ?? '',0,1));
            $avatarBg = ['doctor'=>'#2563eb','asst_doctor'=>'#16a34a','reception'=>'#d97706'][$u['role']] ?? '#6b7280';
            $active = (int)($u['is_active'] ?? 1) === 1;
        ?>
        <tr id="urow-<?php echo $u['id']; ?>">
            <td>
                <div class="um-avatar" style="background:<?php echo $avatarBg; ?>;"><?php echo htmlspecialchars($initials); ?></div>
            </td>
            <td>
                <div style="font-weight:600;color:var(--gray-900);">
                    <?php echo htmlspecialchars(trim($u['fname'].' '.($u['lname']??''))); ?>
                    <?php if ($isYou): ?><span class="you-tag">You</span><?php endif; ?>
                </div>
                <div style="font-size:11px;color:var(--gray-400);"><?php echo htmlspecialchars($u['email']); ?></div>
            </td>
            <td class="hide-sm" style="color:var(--gray-600);">@<?php echo htmlspecialchars($u['username']); ?></td>
            <td class="hide-sm" style="color:var(--gray-600);"><?php echo htmlspecialchars($u['contact_no'] ?? '—'); ?></td>
            <td>
                <span class="role-badge" style="background:<?php echo $rc['bg']; ?>;color:<?php echo $rc['color']; ?>;">
                    <?php echo htmlspecialchars(User::roleLabel($u['role'])); ?>
                </span>
            </td>
            <td>
                <span class="status-dot" style="background:<?php echo $active ? '#22c55e' : '#d1d5db'; ?>;"></span>
                <?php echo $active ? 'Active' : '<span style="color:#9ca3af;">Inactive</span>'; ?>
            </td>
            <td>
                <button class="btn btn-secondary btn-sm" onclick="openEdit(<?php echo htmlspecialchars(json_encode($u)); ?>)" title="Edit">
                    <i class="fas fa-pen"></i>
                </button>
                <?php if (!$isYou): ?>
                <button class="btn btn-danger btn-sm" onclick="deleteUser(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(addslashes(trim($u['fname'].' '.($u['lname']??'')))); ?>')" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- ── Modal ─────────────────────────────────────────────────────────────── -->
<div id="userModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;padding:16px;">
<div style="background:#fff;border-radius:10px;width:100%;max-width:500px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2);">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--gray-200);">
        <h3 id="modalTitle" style="margin:0;font-size:15px;font-weight:700;">Add User</h3>
        <button onclick="closeModal()" style="background:none;border:none;font-size:18px;color:var(--gray-400);cursor:pointer;">&times;</button>
    </div>
    <form id="userForm" style="padding:20px;">
        <input type="hidden" id="userId" name="user_id" value="">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
            <div>
                <label class="form-label">First Name *</label>
                <input type="text" name="fname" id="fname" class="form-control">
            </div>
            <div>
                <label class="form-label">Last Name</label>
                <input type="text" name="lname" id="lname" class="form-control">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
            <div>
                <label class="form-label">Username *</label>
                <input type="text" name="username" id="username" class="form-control" autocomplete="off">
            </div>
            <div>
                <label class="form-label">Contact No. *</label>
                <input type="text" name="contact_no" id="contact_no" class="form-control">
            </div>
        </div>

        <div style="margin-bottom:10px;">
            <label class="form-label">Email *</label>
            <input type="email" name="email" id="email" class="form-control">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
            <div>
                <label class="form-label">Role *</label>
                <select name="role" id="role" class="form-control">
                    <option value="doctor">Doctor</option>
                    <option value="asst_doctor">Asst. Doctor</option>
                    <option value="reception">Reception</option>
                </select>
            </div>
            <div id="activeRow" style="display:none;">
                <label class="form-label">Status</label>
                <select name="is_active" id="is_active" class="form-control">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>

        <div style="margin-bottom:10px;">
            <label class="form-label" id="pwLabel">Password * <span style="font-weight:400;color:var(--gray-400);font-size:11px;">(min 6 chars)</span></label>
            <div style="position:relative;">
                <input type="password" name="new_password" id="new_password" class="form-control" autocomplete="new-password" style="padding-right:38px;">
                <button type="button" id="pwToggle" onclick="togglePw()" tabindex="-1"
                    style="position:absolute;right:0;top:0;height:100%;width:36px;background:none;border:none;color:var(--gray-400);cursor:pointer;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-eye" id="pwEyeIcon"></i>
                </button>
            </div>
            <div id="pwHint" style="font-size:11px;color:var(--gray-400);margin-top:3px;display:none;">Leave blank to keep existing password</div>
        </div>

        <div id="formErr" style="display:none;padding:8px 12px;background:#fef2f2;color:#991b1b;border-radius:6px;font-size:12px;margin-bottom:10px;"></div>

        <div style="display:flex;gap:8px;justify-content:flex-end;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal()">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm" id="submitBtn">
                <i class="fas fa-save"></i> Save
            </button>
        </div>
    </form>
</div>
</div>

<script>
const modal = document.getElementById('userModal');
let _isEdit = false;

function resetPwEye() {
    const inp = document.getElementById('new_password');
    const ico = document.getElementById('pwEyeIcon');
    inp.type = 'password';
    ico.className = 'fas fa-eye';
}

function togglePw() {
    const inp = document.getElementById('new_password');
    const ico = document.getElementById('pwEyeIcon');
    if (inp.type === 'password') {
        inp.type = 'text';
        ico.className = 'fas fa-eye-slash';
    } else {
        inp.type = 'password';
        ico.className = 'fas fa-eye';
    }
}

function openModal() {
    _isEdit = false;
    document.getElementById('modalTitle').textContent = 'Add User';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('pwLabel').innerHTML = 'Password * <span style="font-weight:400;color:var(--gray-400);font-size:11px;">(min 6 chars)</span>';
    document.getElementById('pwHint').style.display = 'none';
    document.getElementById('activeRow').style.display = 'none';
    document.getElementById('username').readOnly = false;
    document.getElementById('formErr').style.display = 'none';
    resetPwEye();
    modal.style.display = 'flex';
}

function openEdit(u) {
    _isEdit = true;
    document.getElementById('modalTitle').textContent = 'Edit User';
    document.getElementById('userId').value      = u.id;
    document.getElementById('fname').value       = u.fname      || '';
    document.getElementById('lname').value       = u.lname      || '';
    document.getElementById('username').value    = u.username   || '';
    document.getElementById('username').readOnly = true;
    document.getElementById('email').value       = u.email      || '';
    document.getElementById('contact_no').value  = u.contact_no || '';
    document.getElementById('role').value        = u.role       || 'doctor';
    document.getElementById('is_active').value   = (u.is_active != null) ? u.is_active : 1;
    document.getElementById('new_password').value = '';
    document.getElementById('pwLabel').innerHTML = 'New Password <span style="font-weight:400;color:var(--gray-400);font-size:11px;">(optional — leave blank to keep current)</span>';
    document.getElementById('pwHint').style.display = 'none';
    document.getElementById('activeRow').style.display = 'block';
    document.getElementById('formErr').style.display = 'none';
    resetPwEye();
    modal.style.display = 'flex';
}

function closeModal() { modal.style.display = 'none'; }
modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

document.getElementById('userForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const errEl = document.getElementById('formErr');
    errEl.style.display = 'none';

    // JS-side validation
    const fname    = document.getElementById('fname').value.trim();
    const username = document.getElementById('username').value.trim();
    const email    = document.getElementById('email').value.trim();
    const contact  = document.getElementById('contact_no').value.trim();
    const pw       = document.getElementById('new_password').value;

    if (!fname)    { showFormErr('First name is required.');     return; }
    if (!username) { showFormErr('Username is required.');        return; }
    if (!email)    { showFormErr('Email is required.');           return; }
    if (!contact)  { showFormErr('Contact number is required.');  return; }
    if (!_isEdit && !pw) { showFormErr('Password is required.'); return; }
    if (pw && pw.length < 6) { showFormErr('Password must be at least 6 characters.'); return; }

    const fd  = new FormData(this);
    const uid = fd.get('user_id');
    const url = uid ? '/api/users/' + uid + '/update' : '/api/users/create';

    document.getElementById('submitBtn').disabled = true;

    fetch(url, { method:'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        document.getElementById('submitBtn').disabled = false;
        if (data.success) {
            closeModal();
            showAlert(data.message || 'Saved!', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showFormErr(data.message || 'Error saving user.');
        }
    })
    .catch(() => {
        document.getElementById('submitBtn').disabled = false;
        showFormErr('Network error. Please try again.');
    });
});

function showFormErr(msg) {
    const el = document.getElementById('formErr');
    el.textContent = msg;
    el.style.display = 'block';
}

function deleteUser(id, name) {
    if (!confirm('Delete user "' + name + '"? This cannot be undone.')) return;
    fetch('/api/users/' + id + '/delete', { method:'POST' })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('urow-' + id)?.remove();
            showAlert('User deleted.', 'success');
        } else {
            showAlert(data.message || 'Error deleting user.', 'danger');
        }
    });
}

function showAlert(msg, type) {
    const el = document.getElementById('alertBox');
    el.className = 'alert alert-' + type;
    el.textContent = msg;
    el.style.display = 'block';
    setTimeout(() => el.style.display = 'none', 3500);
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
