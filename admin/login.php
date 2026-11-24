<?php
require __DIR__.'/../includes/bootstrap.php'; require __DIR__.'/../includes/ui.php';
if (!empty($_SESSION['uid'])) { header('Location: /admin/index.php'); exit; }
$err = ''; $next = ($_GET['next'] ?? '') === 'comprobantes' ? 'comprobantes' : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $pdo = db(); $ip = $_SERVER['REMOTE_ADDR'] ?? '0';
    $pdo->exec("DELETE FROM login_attempts WHERE created_at < NOW() - INTERVAL 1 DAY");
    $c = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip=? AND created_at > NOW() - INTERVAL 15 MINUTE"); $c->execute([$ip]);
    if (!csrf_ok()) $err = 'La sesión expiró, recarga la página.';
    elseif ($c->fetchColumn() >= 5) $err = 'Demasiados intentos. Espera 15 minutos.';
    else {
      $u = $pdo->prepare("SELECT * FROM users WHERE email=?"); $u->execute([strtolower(trim($_POST['email'] ?? ''))]); $u = $u->fetch();
      if ($u && password_verify($_POST['pass'] ?? '', $u['pass_hash'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = $u['id']; $_SESSION['email'] = $u['email']; $_SESSION['must_change'] = (int)$u['must_change'];
        $pdo->prepare("UPDATE users SET last_login=NOW() WHERE id=?")->execute([$u['id']]);
        $pdo->prepare("DELETE FROM login_attempts WHERE ip=?")->execute([$ip]);
        header('Location: /admin/index.php'.(($_POST['next'] ?? '') === 'comprobantes' ? '?s=comprobantes' : '')); exit;
      }
      $pdo->prepare("INSERT INTO login_attempts(ip) VALUES(?)")->execute([$ip]);
      $err = 'Correo o clave incorrectos.';
    }
  } catch (Throwable $x) { $err = 'Error de conexión con la base de datos.'; }
}
auth_head('Acceso administración'); if ($f = flash()) alert_box($f[0], 'success'); alert_box($err); ?>
<form method="post" novalidate>
  <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="next" value="<?= e($next) ?>">
  <div class="mb-3"><label class="form-label" for="email">Correo</label><input type="email" class="form-control" id="email" name="email" required autocomplete="username" autofocus></div>
  <div class="mb-3"><label class="form-label" for="pass">Clave</label><input type="password" class="form-control" id="pass" name="pass" required autocomplete="current-password"></div>
  <div class="d-grid"><button class="btn btn-sea btn-lg">Ingresar</button></div>
  <div class="d-flex justify-content-between mt-3 small"><a href="/admin/forgot.php">Recuperar acceso</a><a href="/">Volver al sitio</a></div>
</form>
<?php auth_foot();
