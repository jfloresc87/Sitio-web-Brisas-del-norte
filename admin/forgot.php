<?php
/* Paso 1 de recuperación: envía un enlace temporal (1 hora) al correo registrado */
require __DIR__.'/../includes/bootstrap.php'; require __DIR__.'/../includes/ui.php';
$done = false; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_ok()) $err = 'La sesión expiró, recarga la página.';
  elseif (time() - ($_SESSION['fg'] ?? 0) < 30) $err = 'Espera unos segundos antes de intentarlo otra vez.';
  else {
    $_SESSION['fg'] = time(); $email = strtolower(trim($_POST['email'] ?? ''));
    try {
      $s = db()->prepare("SELECT id FROM users WHERE email=?"); $s->execute([$email]);
      if ($id = $s->fetchColumn()) {
        $tok = bin2hex(random_bytes(32));
        db()->prepare("UPDATE users SET reset_hash=?, reset_expires=NOW() + INTERVAL 1 HOUR WHERE id=?")->execute([hash('sha256', $tok), $id]);
        $link = BASE_URL.'/admin/reset.php?e='.urlencode($email).'&t='.$tok;
        send_mail($email, 'Recuperar acceso – '.$JUNTA['nombre'],
          "Recibimos una solicitud para restablecer tu clave.\n\nAbre este enlace (válido por 1 hora):\n$link\n\nSi no fuiste tú, ignora este correo.\n");
      }
      $done = true;   // misma respuesta exista o no el correo (evita enumerar cuentas)
    } catch (Throwable $x) { $err = 'No se pudo procesar la solicitud.'; }
  }
}
auth_head('Recuperar acceso');
if ($done) { alert_box('Si el correo está registrado, te enviamos un enlace para crear una nueva clave. Revisa también la carpeta de spam.', 'success'); echo '<a href="/admin/login.php">Volver al ingreso</a>'; }
else { alert_box($err); ?>
<form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>">
  <p class="small text-muted">Ingresa tu correo de administrador y te enviaremos un enlace para crear una nueva clave.</p>
  <div class="mb-3"><label class="form-label" for="email">Correo</label><input type="email" class="form-control" id="email" name="email" required autofocus></div>
  <div class="d-grid"><button class="btn btn-sea">Enviar enlace</button></div><div class="mt-3 small"><a href="/admin/login.php">Volver</a></div></form>
<?php } auth_foot();
