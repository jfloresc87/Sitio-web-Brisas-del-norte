<?php
/* Cambio obligatorio de clave en el primer inicio de sesión */
require __DIR__.'/../includes/bootstrap.php'; require __DIR__.'/../includes/ui.php';
if (empty($_SESSION['uid'])) { header('Location: /admin/login.php'); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_ok()) $err = 'La sesión expiró, recarga la página.';
  elseif (($_POST['np'] ?? '') !== ($_POST['np2'] ?? '')) $err = 'Las claves no coinciden.';
  elseif (!pass_ok($_POST['np'] ?? '')) $err = PASS_MSG;
  elseif (($_POST['np'] ?? '') === 'Brisas2016.') $err = 'Elige una clave distinta a la inicial.';
  else {
    db()->prepare("UPDATE users SET pass_hash=?, must_change=0 WHERE id=?")->execute([password_hash($_POST['np'], PASSWORD_DEFAULT), $_SESSION['uid']]);
    $_SESSION['must_change'] = 0; flash('Clave actualizada correctamente.'); header('Location: /admin/index.php'); exit;
  }
}
auth_head('Crea tu nueva clave'); ?>
<p class="small text-muted">Por seguridad debes cambiar la clave inicial antes de continuar.</p><?php alert_box($err); ?>
<form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><?php pass_fields(); ?>
<div class="d-grid"><button type="submit" class="btn btn-sea" disabled>Guardar y continuar</button></div>
<div class="mt-3 small"><a href="/admin/logout.php">Cancelar y salir</a></div></form>
<?php auth_foot();
