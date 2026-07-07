<?php
$page_title = 'Access Denied';
ob_start();
?>
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:60vh;text-align:center;padding:40px 20px;">
    <div style="width:72px;height:72px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;margin-bottom:20px;">
        <i class="fas fa-lock" style="font-size:28px;color:#ef4444;"></i>
    </div>
    <h2 style="font-size:1.5rem;font-weight:700;color:#111827;margin-bottom:8px;">Access Denied</h2>
    <p style="color:#6b7280;font-size:0.95rem;max-width:380px;margin-bottom:6px;">
        Your account role (<strong><?php echo htmlspecialchars($roleName ?? 'Unknown'); ?></strong>)
        does not have permission to access this page.
    </p>
    <p style="color:#9ca3af;font-size:0.85rem;margin-bottom:28px;">
        Contact the Doctor if you think this is a mistake.
    </p>
    <?php $backIsQueue = (($_SESSION['role'] ?? '') === 'asst_doctor'); ?>
    <a href="<?php echo $backIsQueue ? '/queue' : '/dashboard'; ?>" class="btn btn-primary">
        <i class="fas fa-arrow-left"></i> <?php echo $backIsQueue ? 'Back to Appointments' : 'Back to Dashboard'; ?>
    </a>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
