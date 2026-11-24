<?php
/* Paso 2 de recuperación: valida el enlace y fija la nueva clave */
require __DIR__.'/../includes/bootstrap.php'; require __DIR__.'/../includes/ui.php';
$e = strtolower(trim($_REQUEST['e'] ?? '')); $t = (string)($_REQUEST['t'] ?? ''); $err = ''; $user = null;
try {
  $s = db()->prepare("SELECT * FROM users WHERE email=? AND reset_hash IS NOT NULL AND reset_expires > NOW()"); $s->execute([$e]); $u = $s->fetch();
  if ($u && hash_equals($u['reset_hash'], hash('sha256', $t))) $user = $u;
} catch (Throwable $x) {}
if ($user && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_ok()) $err = 'La sesión expiró, recarga la página.';
  elseif (($_POST['np'] ?? '') !== ($_POST['np2'] ?? '')) $err = 'Las claves no coinciden.';
  elseif (!pass_ok($_POST['np'] ?? '')) $err = PASS_MSG;
  elseif (($_POST['np'] ?? '') === 'Brisas2016.') $err = 'Elige una clave distinta a la inicial.';
  else {
    db()->prepare("UPDATE users SET pass_hash=?, must_change=0, reset_hash=NULL, reset_expires=NULL WHERE id=?")
        ->execute([password_hash($_POST['np'], PASSWORD_DEFAULT), $user['id']]);
    flash('Clave actualizada. Ya puedes ingresar.'); header('Location: /admin/login.php'); exit;
  }
}
auth_head('Nueva clave');
if (!$user) { alert_box('El enlace no es válido o ya expiró.'); echo '<a href="/admin/forgot.php">Solicitar uno nuevo</a>'; }
else { alert_box($err); ?>
<form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="e" value="<?= e($e) ?>"><input type="hidden" name="t" value="<?= e($t) ?>">
<?php pass_fields(); ?><div class="d-grid"><button type="submit" class="btn btn-sea" disabled>Guardar nueva clave</button></div></form>
<?php } auth_foot();
