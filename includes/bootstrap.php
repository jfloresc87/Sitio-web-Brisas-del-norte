<?php
/* Núcleo: sesión, BDD, CSRF, autenticación, subida de archivos, utilidades */
require_once __DIR__.'/config.php';
define('ROOT', dirname(__DIR__));
date_default_timezone_set('America/Santiago');
if (session_status() === PHP_SESSION_NONE) {
  session_name('JVSESS');
  session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])]);
  session_start();
}

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function db(string $n = DB_WEB): PDO {
  static $c = [];
  return $c[$n] ??= new PDO("mysql:host=".DB_HOST.";dbname=$n;charset=utf8mb4", DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
}
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_ok(): bool { return hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? ''); }
function clp($n): string { return '$'.number_format((int)$n, 0, ',', '.'); }
function jout(array $d, int $code = 200): void {
  http_response_code($code); header('Content-Type: application/json; charset=utf-8');
  echo json_encode($d, JSON_UNESCAPED_UNICODE); exit;
}
function flash(?string $m = null, string $t = 'success') {
  if ($m !== null) { $_SESSION['flash'] = [$m, $t]; return null; }
  $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}
function clean(string $s, int $max = 255): string {
  $s = trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $s)); return mb_substr($s, 0, $max);
}
/* Contraseña: 8+ caracteres, mayúscula, minúscula, número y especial */
function pass_ok(string $p): bool {
  return strlen($p) >= 8 && preg_match('/[A-Z]/', $p) && preg_match('/[a-z]/', $p)
      && preg_match('/\d/', $p) && preg_match('/[^A-Za-z0-9]/', $p);
}
const PASS_MSG = 'La clave debe tener mínimo 8 caracteres, 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial.';

function require_login(): void {
  if (empty($_SESSION['uid'])) {
    $n = isset($_GET['s']) && $_GET['s'] === 'comprobantes' ? '?next=comprobantes' : '';
    header('Location: /admin/login.php'.$n); exit;
  }
  if (!empty($_SESSION['must_change'])) { header('Location: /admin/cambiar_clave.php'); exit; }
}

/* Sube y valida un archivo (por contenido real, no por extensión). Devuelve ruta relativa. */
function save_upload(array $f, string $sub, array $kinds = ['image'], int $maxMB = 8): string {
  if ($f['error'] !== UPLOAD_ERR_OK) throw new Exception('No se pudo subir el archivo (revisa que pese menos de '.$maxMB.' MB).');
  if ($f['size'] > $maxMB * 1048576) throw new Exception("El archivo supera los $maxMB MB.");
  $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
  $map = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','video/mp4'=>'mp4','video/webm'=>'webm'];
  if (!isset($map[$mime])) throw new Exception('Formato no permitido (usa JPG, PNG, WEBP, MP4 o WEBM).');
  $type = str_starts_with($mime, 'video') ? 'video' : 'image';
  if (!in_array($type, $kinds, true)) throw new Exception('Este campo no acepta ese tipo de archivo.');
  $dir = ROOT."/uploads/$sub"; if (!is_dir($dir)) mkdir($dir, 0755, true);
  $name = bin2hex(random_bytes(8)).'.'.$map[$mime]; $dest = "$dir/$name"; $ok = false;
  if ($type === 'image' && function_exists('imagescale')) {      // reduce fotos grandes → sitio liviano
    [$w, $h] = getimagesize($f['tmp_name']); $max = 1600;
    if (max($w, $h) > $max && ($im = @imagecreatefromstring(file_get_contents($f['tmp_name'])))) {
      $im = imagescale($im, $w >= $h ? $max : (int)round($w * $max / $h)); imagesavealpha($im, true);
      $ok = match($map[$mime]) { 'jpg'=>imagejpeg($im,$dest,82), 'png'=>imagepng($im,$dest,7),
                                  'webp'=>function_exists('imagewebp') && imagewebp($im,$dest,82), default=>false };
    }
  }
  if (!$ok && !move_uploaded_file($f['tmp_name'], $dest)) throw new Exception('No se pudo guardar el archivo.');
  return "uploads/$sub/$name";
}
function del_file(?string $rel): void {
  if ($rel && str_starts_with($rel, 'uploads/') && !str_contains($rel, '..') && is_file(ROOT.'/'.$rel)) @unlink(ROOT.'/'.$rel);
}
/* Reordena $_FILES['campo'][] múltiples */
function files_list(string $k): array {
  $o = []; if (empty($_FILES[$k]['name'])) return $o;
  foreach ((array)$_FILES[$k]['name'] as $i => $n) {
    if ($_FILES[$k]['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
    $o[] = ['name'=>$n,'tmp_name'=>$_FILES[$k]['tmp_name'][$i],'error'=>$_FILES[$k]['error'][$i],'size'=>$_FILES[$k]['size'][$i]];
  }
  return $o;
}
function send_mail(string $to, string $subject, string $body, string $replyTo = ''): bool {
  $h = "From: ".MAIL_FROM."\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n";
  if ($replyTo) $h .= "Reply-To: ".str_replace(["\r","\n"], '', $replyTo)."\r\n";
  return mail($to, mb_encode_mimeheader($subject, 'UTF-8'), $body, $h, '-f'.MAIL_FROM);
}
function rut_ok(string $r): bool {
  $r = strtoupper(preg_replace('/[^0-9K]/i', '', $r)); if (strlen($r) < 8) return false;
  $d = substr($r, -1); $n = substr($r, 0, -1); $s = 0; $m = 2;
  for ($i = strlen($n)-1; $i >= 0; $i--) { $s += (int)$n[$i] * $m; $m = $m == 7 ? 2 : $m + 1; }
  $x = 11 - $s % 11; return ($x == 11 ? '0' : ($x == 10 ? 'K' : (string)$x)) === $d;
}
function rut_fmt(string $r): string {
  $r = strtoupper(preg_replace('/[^0-9K]/i', '', $r)); $d = substr($r, -1); $n = substr($r, 0, -1);
  return number_format((int)$n, 0, '', '.').'-'.$d;
}
