<?php
/* Recibe el formulario de contacto (AJAX) y envía 2 correos con mail()/sendmail */
require __DIR__.'/includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jout(['ok'=>false,'msg'=>'Método no permitido.'], 405);
if (!csrf_ok()) jout(['ok'=>false,'msg'=>'La sesión expiró. Recarga la página e inténtalo de nuevo.'], 400);
if (!empty($_POST['website'])) jout(['ok'=>true]);                                   // honeypot (bots)
if (time() - ($_SESSION['form_t'] ?? time()) < 3) jout(['ok'=>false,'msg'=>'Envío demasiado rápido, inténtalo nuevamente.'], 429);
if (time() - ($_SESSION['last_send'] ?? 0) < 30) jout(['ok'=>false,'msg'=>'Espera unos segundos antes de enviar otro mensaje.'], 429);

if ($RECAPTCHA_ENABLED) {
  $token = $_POST['g-recaptcha-response'] ?? '';
  if (!is_string($token) || trim($token) === '') jout(['ok'=>false,'msg'=>'Completa la verificación reCAPTCHA.','captcha'=>true], 422);
  $context = stream_context_create([
    'http'=>[
      'method'=>'POST',
      'header'=>"Content-Type: application/x-www-form-urlencoded\r\n",
      'content'=>http_build_query(['secret'=>$RECAPTCHA['secret_key'], 'response'=>trim($token)]),
      'timeout'=>8,
      'ignore_errors'=>true,
    ],
    'ssl'=>['verify_peer'=>true, 'verify_peer_name'=>true],
  ]);
  $googleResponse = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
  if ($googleResponse === false) {
    $error = error_get_last();
    error_log('reCAPTCHA verification request failed: '.($error['message'] ?? 'unknown error'));
    jout(['ok'=>false,'msg'=>'No pudimos verificar reCAPTCHA. Inténtalo nuevamente.'], 503);
  }
  $verification = json_decode($googleResponse, true);
  if (!is_array($verification)) {
    error_log('reCAPTCHA returned an invalid response.');
    jout(['ok'=>false,'msg'=>'No pudimos verificar reCAPTCHA. Inténtalo nuevamente.'], 503);
  }
  $baseHost = strtolower((string)parse_url(BASE_URL, PHP_URL_HOST));
  $wwwHost = str_starts_with($baseHost, 'www.') ? substr($baseHost, 4) : 'www.'.$baseHost;
  $verifiedHost = strtolower((string)($verification['hostname'] ?? ''));
  if (empty($verification['success']) || !in_array($verifiedHost, [$baseHost, $wwwHost], true)) {
    jout(['ok'=>false,'msg'=>'La verificación reCAPTCHA no fue aprobada. Inténtalo nuevamente.','captcha'=>true], 422);
  }
} else {
  $cap = $_SESSION['captcha'] ?? null; unset($_SESSION['captcha']);
  if ($cap === null || (int)($_POST['captcha'] ?? -1) !== (int)$cap) jout(['ok'=>false,'msg'=>'La verificación es incorrecta.','captcha'=>true], 422);
}

$d = ['nombre'=>clean($_POST['nombre']??'',60), 'apellido'=>clean($_POST['apellido']??'',60), 'correo'=>clean($_POST['correo']??'',120),
      'telefono'=>clean($_POST['telefono']??'',15), 'mensaje'=>trim(mb_substr(str_replace("\0",'',$_POST['mensaje']??''),0,2000))];
if (in_array('', [$d['nombre'],$d['apellido'],$d['correo'],$d['telefono']], true) || mb_strlen($d['mensaje']) < 10) jout(['ok'=>false,'msg'=>'Completa todos los campos obligatorios.'], 422);
if (!filter_var($d['correo'], FILTER_VALIDATE_EMAIL)) jout(['ok'=>false,'msg'=>'El correo no es válido.'], 422);
if (!preg_match('/^[0-9+ ]{8,15}$/', $d['telefono'])) jout(['ok'=>false,'msg'=>'El teléfono no es válido.'], 422);

$datos = "Nombre: {$d['nombre']}\nApellido: {$d['apellido']}\nCorreo: {$d['correo']}\nTeléfono: {$d['telefono']}\nMensaje:\n{$d['mensaje']}\n";
$ok = send_mail(MAIL_TO, 'Mensaje desde la Web',
      "Se ha recibido un mensaje\n\n$datos\nFavor responder antes de 48 hrs.\n", $d['correo']);
if ($ok) send_mail($d['correo'], 'Mensaje desde la Web',
      "Tu correo ha sido enviado, se responderá en un plazo máximo de 48 hrs.\n\nCopia de los datos enviados:\n\n$datos");
if (!$ok) jout(['ok'=>false,'msg'=>'No pudimos enviar el mensaje. Inténtalo más tarde o escríbenos por WhatsApp.'], 500);
$_SESSION['last_send'] = time();
jout(['ok'=>true,'msg'=>'Tu correo ha sido enviado, se responderá en un plazo máximo de 48 hrs.']);
