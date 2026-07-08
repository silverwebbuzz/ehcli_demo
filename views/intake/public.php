<?php
/**
 * Public / in-clinic intake fill page (standalone — no app layout).
 *
 * Expects:
 *   $intake       — the intake row (with token, patient_name?, patient_gender?)
 *   $intakeError  — string|null (already-submitted / expired / invalid)
 *   $staffFill    — bool (true when a logged-in staff member is filling it)
 */
use App\Models\HomeoIntake;

$schema  = HomeoIntake::schema();
$token   = $intake['token'] ?? '';
$gender  = strtolower($intake['patient_gender'] ?? '');
$name    = $intake['patient_name'] ?? '';
// Hide the Female tab only when we positively know the patient is not female.
$showFemale = ($gender === '' || $gender === 'female' || $gender === 'f');

// Tabs actually shown
$tabs = array_values(array_filter($schema['tabs'], function ($t) use ($showFemale) {
    if (($t['when_gender'] ?? '') === 'female') return $showFemale;
    return true;
}));

function fieldInput(array $f): string {
    $name = htmlspecialchars($f['name']);
    $req  = !empty($f['required']) ? 'required' : '';
    switch ($f['type']) {
        case 'textarea':
            return "<textarea name=\"{$name}\" rows=\"2\" {$req}></textarea>";
        case 'text':
            return "<input type=\"text\" name=\"{$name}\" {$req}>";
        case 'select':
            $h = "<select name=\"{$name}\" {$req}><option value=\"\">— select —</option>";
            foreach ($f['options'] as $o) {
                $h .= '<option value="' . htmlspecialchars($o['value']) . '">' . htmlspecialchars($o['label']) . '</option>';
            }
            return $h . '</select>';
        case 'radio':
            $h = '<div class="opts">';
            foreach ($f['options'] as $i => $o) {
                $id = $name . '_' . $i;
                $h .= '<label class="opt" for="' . $id . '"><input type="radio" id="' . $id . '" name="' . $name . '" value="'
                    . htmlspecialchars($o['value']) . '" ' . $req . '><span>' . htmlspecialchars($o['label']) . '</span></label>';
            }
            return $h . '</div>';
        case 'checkbox':
            $h = '<div class="opts">';
            foreach ($f['options'] as $i => $o) {
                $id = $name . '_' . $i;
                $h .= '<label class="opt" for="' . $id . '"><input type="checkbox" id="' . $id . '" name="' . $name . '[]" value="'
                    . htmlspecialchars($o['value']) . '"><span>' . htmlspecialchars($o['label']) . '</span></label>';
            }
            return $h . '</div>';
    }
    return "<input type=\"text\" name=\"{$name}\">";
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Homeopathy Intake — Dr. Feelgood</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  :root { --green:#0f9d76; --green-d:#0b7d5e; --ink:#1f2937; --muted:#6b7280; --line:#e5e7eb; }
  * { box-sizing: border-box; }
  body { margin:0; font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif; color:var(--ink); background:#f3f4f6; }
  .wrap { max-width:760px; margin:0 auto; padding:16px; }
  .card { background:#fff; border:1px solid var(--line); border-radius:14px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.05); }
  .top { background:linear-gradient(135deg,var(--green),var(--green-d)); color:#fff; padding:22px 20px; text-align:center; }
  .top h1 { margin:0; font-size:1.35rem; }
  .top p { margin:6px 0 0; opacity:.9; font-size:.9rem; }
  .staff-note { background:#fffbeb; color:#92400e; border-bottom:1px solid #fde68a; padding:8px 16px; font-size:.85rem; text-align:center; }
  .tabs { display:flex; gap:6px; overflow-x:auto; padding:12px 12px 0; border-bottom:1px solid var(--line); background:#fafafa; -webkit-overflow-scrolling:touch; }
  .tab { flex:0 0 auto; border:1px solid var(--line); border-bottom:none; background:#fff; color:var(--muted);
         padding:9px 14px; border-radius:10px 10px 0 0; cursor:pointer; font-size:.85rem; font-weight:600; white-space:nowrap; }
  .tab.active { color:var(--green-d); border-color:var(--green); box-shadow:inset 0 -3px 0 var(--green); }
  .tab i { margin-right:5px; }
  .panel { display:none; padding:20px; }
  .panel.active { display:block; animation:fade .2s ease; }
  @keyframes fade { from{opacity:0;transform:translateY(4px)} to{opacity:1;transform:none} }
  .field { margin-bottom:16px; }
  .field > label.q { display:block; font-weight:600; margin-bottom:7px; font-size:.95rem; }
  .field .req { color:#dc2626; }
  input[type=text], textarea, select { width:100%; padding:10px 12px; border:1px solid var(--line); border-radius:9px; font-size:.95rem; font-family:inherit; }
  textarea { resize:vertical; }
  .opts { display:flex; flex-direction:column; gap:8px; }
  .opt { display:flex; align-items:flex-start; gap:9px; border:1px solid var(--line); border-radius:9px; padding:9px 11px; cursor:pointer; font-size:.92rem; }
  .opt:hover { border-color:var(--green); background:#f0fdf9; }
  .opt input { margin-top:3px; }
  .nav { display:flex; justify-content:space-between; gap:10px; padding:16px 20px; border-top:1px solid var(--line); background:#fafafa; }
  .btn { border:none; border-radius:9px; padding:11px 18px; font-size:.95rem; font-weight:600; cursor:pointer; }
  .btn-ghost { background:#fff; border:1px solid var(--line); color:var(--ink); }
  .btn-primary { background:var(--green); color:#fff; }
  .btn-primary:hover { background:var(--green-d); }
  .btn[disabled] { opacity:.6; cursor:not-allowed; }
  .msg { padding:40px 24px; text-align:center; }
  .msg i { font-size:2.6rem; margin-bottom:12px; }
  .msg.ok i { color:var(--green); }
  .msg.warn i { color:#d97706; }
  .progress { height:4px; background:var(--line); }
  .progress > div { height:100%; width:0; background:var(--green); transition:width .25s; }
  .err { display:none; background:#fef2f2; color:#b91c1c; border:1px solid #fca5a5; border-radius:9px; padding:10px 14px; margin:0 20px 4px; font-size:.9rem; }
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <div class="top">
      <h1><i class="fas fa-leaf"></i> Homeopathy Intake Questionnaire</h1>
      <p>Dr. Feelgood's Clinic Management</p>
    </div>

<?php if (!empty($intakeError)): ?>
    <div class="msg <?php echo str_contains($intakeError, 'submitted') ? 'ok' : 'warn'; ?>">
      <i class="fas <?php echo str_contains($intakeError, 'submitted') ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i>
      <p style="font-size:1.05rem;margin:0;"><?php echo htmlspecialchars($intakeError); ?></p>
    </div>
<?php else: ?>

    <?php if (!empty($staffFill)): ?>
      <div class="staff-note"><i class="fas fa-user-nurse"></i> Filling on behalf of the patient (staff mode) — or send them the link to fill it themselves.</div>
      <div style="display:flex;gap:8px;padding:12px 16px;border-bottom:1px solid var(--line);align-items:center;flex-wrap:wrap;">
        <a href="/queue" class="btn btn-ghost" style="text-decoration:none;"><i class="fas fa-arrow-left"></i> Back</a>
        <input type="text" readonly id="staffShareUrl" value="<?php echo htmlspecialchars($shareUrl ?? ''); ?>"
               style="flex:1;min-width:180px;padding:9px 12px;border:1px solid var(--line);border-radius:8px;font-size:.85rem;">
        <button type="button" class="btn btn-primary" id="copyLinkBtn"><i class="fas fa-copy"></i> Copy patient link</button>
      </div>
    <?php endif; ?>

    <div id="thanks" class="msg ok" style="display:none;">
      <i class="fas fa-circle-check"></i>
      <p style="font-size:1.1rem;margin:0;">Thank you! Your responses have been submitted.</p>
    </div>

    <form id="intakeForm" data-token="<?php echo htmlspecialchars($token); ?>">
      <div class="progress"><div id="progressBar"></div></div>
      <div class="tabs" id="tabs">
        <?php foreach ($tabs as $i => $t): ?>
          <button type="button" class="tab <?php echo $i === 0 ? 'active' : ''; ?>" data-i="<?php echo $i; ?>">
            <i class="fas <?php echo htmlspecialchars($t['icon']); ?>"></i><?php echo htmlspecialchars($t['label']); ?>
          </button>
        <?php endforeach; ?>
      </div>

      <div class="err" id="formErr"></div>

      <?php foreach ($tabs as $i => $t): ?>
        <div class="panel <?php echo $i === 0 ? 'active' : ''; ?>" data-i="<?php echo $i; ?>">
          <?php if (!empty($name) && $i === 0): ?>
            <p style="margin:0 0 16px;color:var(--muted);">Hello <strong><?php echo htmlspecialchars($name); ?></strong>, please answer as best you can. There are no wrong answers.</p>
          <?php endif; ?>
          <?php foreach ($t['fields'] as $f): ?>
            <div class="field">
              <label class="q"><?php echo htmlspecialchars($f['label']); ?><?php echo !empty($f['required']) ? ' <span class="req">*</span>' : ''; ?></label>
              <?php echo fieldInput($f); ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>

      <div class="nav">
        <button type="button" class="btn btn-ghost" id="prevBtn" style="visibility:hidden;"><i class="fas fa-arrow-left"></i> Back</button>
        <button type="button" class="btn btn-primary" id="nextBtn">Next <i class="fas fa-arrow-right"></i></button>
        <button type="submit" class="btn btn-primary" id="submitBtn" style="display:none;"><i class="fas fa-paper-plane"></i> Submit</button>
      </div>
    </form>
<?php endif; ?>
  </div>
</div>

<script>
(function () {
  var copyBtn = document.getElementById('copyLinkBtn');
  if (copyBtn) {
    copyBtn.addEventListener('click', function () {
      var input = document.getElementById('staffShareUrl');
      navigator.clipboard.writeText(input.value).then(function () {
        copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied';
        setTimeout(function () { copyBtn.innerHTML = '<i class="fas fa-copy"></i> Copy patient link'; }, 2000);
      });
    });
  }

  var form = document.getElementById('intakeForm');
  if (!form) return;
  var tabs   = Array.prototype.slice.call(document.querySelectorAll('.tab'));
  var panels = Array.prototype.slice.call(document.querySelectorAll('.panel'));
  var prev = document.getElementById('prevBtn'),
      next = document.getElementById('nextBtn'),
      submit = document.getElementById('submitBtn'),
      bar = document.getElementById('progressBar'),
      err = document.getElementById('formErr');
  var cur = 0, last = tabs.length - 1;

  function show(i) {
    cur = Math.max(0, Math.min(last, i));
    tabs.forEach(function (t, k) { t.classList.toggle('active', k === cur); });
    panels.forEach(function (p, k) { p.classList.toggle('active', k === cur); });
    prev.style.visibility = cur === 0 ? 'hidden' : 'visible';
    next.style.display   = cur === last ? 'none' : '';
    submit.style.display = cur === last ? '' : 'none';
    bar.style.width = ((cur + 1) / tabs.length * 100) + '%';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
  tabs.forEach(function (t) { t.addEventListener('click', function () { show(+t.dataset.i); }); });
  next.addEventListener('click', function () { show(cur + 1); });
  prev.addEventListener('click', function () { show(cur - 1); });
  show(0);

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    err.style.display = 'none';
    // native required validation
    if (!form.reportValidity()) return;
    submit.disabled = true;
    submit.innerHTML = 'Submitting…';
    fetch('/api/intake/' + form.dataset.token + '/submit', { method: 'POST', body: new FormData(form) })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.success) {
          form.style.display = 'none';
          document.getElementById('thanks').style.display = 'block';
        } else {
          err.textContent = res.message || 'Could not submit. Please try again.';
          err.style.display = 'block';
          submit.disabled = false;
          submit.innerHTML = '<i class="fas fa-paper-plane"></i> Submit';
        }
      })
      .catch(function () {
        err.textContent = 'Network error. Please try again.';
        err.style.display = 'block';
        submit.disabled = false;
        submit.innerHTML = '<i class="fas fa-paper-plane"></i> Submit';
      });
  });
})();
</script>
</body>
</html>
