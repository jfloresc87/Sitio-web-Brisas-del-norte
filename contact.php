<?php
/* Recibe el formulario de contacto (AJAX) y envía 2 correos con mail()/sendmail */
require __DIR__.'/includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jout(['ok'=>false,'msg'=>'Método no permitido.'], 405);
if (!csrf_ok()) jout(['ok'=>false,'msg'=>'La sesión expiró. Recarga la página e inténtalo de nuevo.'], 400);
if (!empty($_POST['website'])) jout(['ok'=>true]);                                   // honeypot (bots)
if (time() - ($_SESSION['form_t'] ?? time()) < 3) jout(['ok'=>false,'msg'=>'Envío demasiado rápido, inténtalo nuevamente.'], 429);
if (time() - ($_SESSION['last_send'] ?? 0) < 30) jout(['ok'=>false,'msg'=>'Espera unos segundos antes de enviar otro mensaje.'], 429);

$cap = $_SESSION['captcha'] ?? null; unset($_SESSION['captcha']);
if ($cap === null || (int)($_POST['captcha'] ?? -1) !== (int)$cap) jout(['ok'=>false,'msg'=>'La verificación es incorrecta.','captcha'=>true], 422);

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
