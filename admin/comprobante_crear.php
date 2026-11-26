<?php
/* Valida (en el servidor) y guarda un comprobante; responde JSON con el id */
require __DIR__.'/../includes/bootstrap.php';
if (empty($_SESSION['uid'])) jout(['ok'=>false,'msg'=>'Sesión no válida.'], 401);
$activeAdmin = db()->prepare("SELECT must_change FROM users WHERE id=?"); $activeAdmin->execute([$_SESSION['uid']]);
$mustChange = $activeAdmin->fetchColumn();
if ($mustChange === false || $mustChange) jout(['ok'=>false,'msg'=>'Sesión no válida.'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok()) jout(['ok'=>false,'msg'=>'Solicitud inválida, recarga la página.'], 400);
function bad(string $m): never { jout(['ok'=>false,'msg'=>$m], 422); }

$concept = $_POST['concept'] ?? ''; if (!in_array($concept, ['Arriendo de Cancha','Arriendo de Sede','Otros Pagos'], true)) bad('Elige el concepto de pago.');
$name = clean($_POST['name'] ?? '', 60); $last = clean($_POST['lastname'] ?? '', 60);
$method = $_POST['method'] ?? ''; $special = !empty($_POST['special']) ? 1 : 0;
if (mb_strlen($name) < 2 || mb_strlen($last) < 2) bad('Ingresa nombre y apellido.');
if (!in_array($method, ['Efectivo','Transferencia'], true)) bad('Indica si pagó en efectivo o por transferencia.');
$phone = clean($_POST['phone'] ?? '', 20); $rut = null; $other = null; $hs = null; $hq = null; $date = null;
$manual = (int)preg_replace('/\D/', '', $_POST['amount'] ?? '0');

if ($concept !== 'Otros Pagos') {
  if (!preg_match('/^[0-9+ ]{8,15}$/', $phone)) bad('Ingresa un teléfono válido.');
  $d = DateTime::createFromFormat('Y-m-d', $_POST['event_date'] ?? ''); if (!$d || $d->format('Y-m-d') !== $_POST['event_date']) bad('Indica la fecha del evento.');
  $date = $d->format('Y-m-d');
} else { $phone = null; if (!empty($_POST['event_date'])) { $d = DateTime::createFromFormat('Y-m-d', $_POST['event_date']); $date = $d ? $d->format('Y-m-d') : null; } }

if ($concept === 'Arriendo de Cancha') {
  $hs = (int)($_POST['hour_start'] ?? 0); $hq = (int)($_POST['hour_qty'] ?? 0);
  if ($hs < CANCHA_HORA_INICIO || $hs > 23) bad('La cancha se arrienda desde las '.CANCHA_HORA_INICIO.':00 hrs.');
  if ($hq < 1 || $hq > 24 - $hs) bad('Cantidad de horas no válida.');
  $amount = $special ? $manual : PRECIO_CANCHA_HORA * $hq;
} elseif ($concept === 'Arriendo de Sede') {
  if (!rut_ok($_POST['rut'] ?? '')) bad('El RUT no es válido.'); $rut = rut_fmt($_POST['rut']);
  $amount = $special ? $manual : PRECIO_SEDE;
} else {
  $other = clean($_POST['other_concept'] ?? '', 150); if (mb_strlen($other) < 3) bad('Describe el concepto del pago.');
  $special = 1; $amount = $manual;
}
if ($amount < 1 || $amount > 20000000) bad('Ingresa un valor válido.');

$st = db(DB_REC)->prepare("INSERT INTO receipts(concept,name,lastname,phone,rut,other_concept,hour_start,hour_qty,method,special,amount,event_date,issued_at,issued_by)
                           VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
$st->execute([$concept,$name,$last,$phone,$rut,$other,$hs,$hq,$method,$special,$amount,$date,date('Y-m-d H:i:s'),$_SESSION['email']]);
jout(['ok'=>true,'id'=>(int)db(DB_REC)->lastInsertId()]);
