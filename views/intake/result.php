<?php
/**
 * Doctor-facing intake result: scored case sheet.
 * Expects $intake (decoded row from IntakeController::getResult).
 */
use App\Models\HomeoIntake;

ob_start();
$page_title = 'Intake Result';

$schema = HomeoIntake::schema();
$answers = $intake['answers'] ?? [];
$scores  = $intake['miasm_scores'] ?? [];
$thermal = $intake['thermal'] ?? '';
$submitted = in_array($intake['status'], ['submitted', 'locked'], true);

// value -> label lookup for a field's options
function optLabel(array $field, $value): string {
    foreach (($field['options'] ?? []) as $o) {
        if ($o['value'] === $value) return $o['label'];
    }
    return (string)$value;
}
function answerText(array $field, $val): string {
    if ($val === null || $val === '' || $val === []) return '—';
    if (is_array($val)) {
        $labels = array_map(fn($v) => optLabel($field, $v), $val);
        return htmlspecialchars(implode(', ', $labels));
    }
    if (!empty($field['options'])) return htmlspecialchars(optLabel($field, $val));
    return nl2br(htmlspecialchars($val));
}

// leading miasm
$topMiasm = '';
if ($scores) { arsort($scores); $topMiasm = array_key_first($scores); }
$miasmColor = ['psora' => '#0f9d76', 'sycosis' => '#d97706', 'syphilis' => '#dc2626', 'tubercular' => '#7c3aed'];
?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
  <h1 class="page-title"><i class="fas fa-leaf"></i> Homeopathy Intake — Case Sheet</h1>
  <?php if (!empty($intake['patient_id'])): ?>
    <a href="/patient/<?php echo (int)$intake['patient_id']; ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Patient</a>
  <?php endif; ?>
</div>

<div class="row">
  <div class="col-lg-8 offset-lg-2">

    <div class="card" style="margin-bottom:16px;">
      <div class="card-body" style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;">
        <div>
          <div style="font-size:1.15rem;font-weight:700;"><?php echo htmlspecialchars($intake['patient_name'] ?? 'Patient'); ?></div>
          <div style="color:#6b7280;font-size:.9rem;"><?php echo htmlspecialchars($intake['patient_meta'] ?? ''); ?></div>
        </div>
        <div style="text-align:right;font-size:.85rem;color:#6b7280;">
          <?php if ($submitted): ?>
            <span class="badge badge-success" style="background:#dcfce7;color:#166534;padding:4px 10px;border-radius:20px;">Submitted</span><br>
            <?php echo htmlspecialchars($intake['submitted_at'] ?? ''); ?>
          <?php else: ?>
            <span class="badge" style="background:#fef3c7;color:#92400e;padding:4px 10px;border-radius:20px;">Awaiting patient</span>
          <?php endif; ?>
        </div>
      </div>
    </div>

<?php if (!$submitted): ?>
    <div class="card"><div class="card-body" style="text-align:center;padding:40px 20px;color:#6b7280;">
      <i class="fas fa-hourglass-half" style="font-size:2rem;color:#d97706;"></i>
      <p style="margin:12px 0 4px;font-size:1.05rem;color:#374151;">The patient hasn't submitted the questionnaire yet.</p>
      <p style="margin:0;">Share this link with them:</p>
      <div style="margin-top:12px;display:flex;gap:8px;max-width:520px;margin-left:auto;margin-right:auto;">
        <input type="text" readonly id="shareUrl" value="<?php
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            echo htmlspecialchars($scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '') . '/intake/' . $intake['token']);
        ?>" style="flex:1;padding:9px 12px;border:1px solid #e5e7eb;border-radius:8px;">
        <button class="btn btn-primary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('shareUrl').value);this.innerHTML='<i class=\'fas fa-check\'></i> Copied'"><i class="fas fa-copy"></i> Copy</button>
      </div>
    </div></div>
<?php else: ?>

    <!-- SCORE (suggestion only) -->
    <div class="card" style="margin-bottom:16px;">
      <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <span><i class="fas fa-chart-simple"></i> Constitutional tendency (auto)</span>
        <span style="font-size:.75rem;font-weight:600;color:#92400e;background:#fffbeb;border:1px solid #fde68a;padding:3px 9px;border-radius:20px;">
          <i class="fas fa-circle-info"></i> Suggestion only — not a diagnosis
        </span>
      </div>
      <div class="card-body">
        <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:16px;">
          <div style="flex:1;min-width:180px;">
            <div style="font-size:.8rem;color:#6b7280;">Leading miasm tendency</div>
            <div style="font-size:1.3rem;font-weight:700;color:<?php echo $miasmColor[$topMiasm] ?? '#111'; ?>;">
              <?php echo htmlspecialchars($schema['miasms'][$topMiasm] ?? '—'); ?>
            </div>
          </div>
          <div style="flex:1;min-width:180px;">
            <div style="font-size:.8rem;color:#6b7280;">Thermal</div>
            <div style="font-size:1.3rem;font-weight:700;">
              <?php echo $thermal === 'hot' ? '🔥 Hot' : ($thermal === 'chilly' ? '❄️ Chilly' : '—'); ?>
            </div>
          </div>
        </div>
        <?php foreach ($scores as $m => $pct): ?>
          <div style="margin-bottom:9px;">
            <div style="display:flex;justify-content:space-between;font-size:.85rem;margin-bottom:3px;">
              <span><?php echo htmlspecialchars($schema['miasms'][$m] ?? $m); ?></span><span><?php echo (int)$pct; ?>%</span>
            </div>
            <div style="height:8px;background:#f1f5f9;border-radius:6px;overflow:hidden;">
              <div style="height:100%;width:<?php echo (int)$pct; ?>%;background:<?php echo $miasmColor[$m] ?? '#0f9d76'; ?>;"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- CASE SHEET -->
    <?php foreach ($schema['tabs'] as $tab):
        // skip female section entirely if it has no answers
        $hasAny = false;
        foreach ($tab['fields'] as $f) { if (!empty($answers[$f['name']])) { $hasAny = true; break; } }
        if (($tab['when_gender'] ?? '') === 'female' && !$hasAny) continue;
    ?>
      <div class="card" style="margin-bottom:14px;">
        <div class="card-header"><i class="fas <?php echo htmlspecialchars($tab['icon']); ?>"></i> <?php echo htmlspecialchars($tab['label']); ?></div>
        <div class="card-body" style="padding:6px 0;">
          <?php foreach ($tab['fields'] as $f): ?>
            <div style="display:flex;gap:14px;padding:9px 18px;border-bottom:1px solid #f3f4f6;">
              <div style="flex:0 0 42%;color:#6b7280;font-size:.9rem;"><?php echo htmlspecialchars($f['label']); ?></div>
              <div style="flex:1;font-weight:500;font-size:.93rem;"><?php echo answerText($f, $answers[$f['name']] ?? null); ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>

<?php endif; ?>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
