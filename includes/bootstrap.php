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
function ensure_admin_profile_schema(): void {
  $pdo = db();
  foreach (['name'=>'VARCHAR(120) NOT NULL DEFAULT \'\'', 'profile_photo'=>'VARCHAR(255) NULL'] as $column=>$definition) {
    $check = $pdo->query("SHOW COLUMNS FROM users LIKE ".$pdo->quote($column));
    if (!$check->fetch()) $pdo->exec("ALTER TABLE users ADD COLUMN `$column` $definition");
  }
}
function is_superuser(?string $email = null): bool {
  return strtolower(trim($email ?? (string)($_SESSION['email'] ?? ''))) === ADMIN_SUPERUSER_EMAIL;
}
function current_user_is_superuser(): bool {
  if (empty($_SESSION['uid'])) return false;
  $q = db()->prepare("SELECT email FROM users WHERE id=?");
  $q->execute([$_SESSION['uid']]);
  return is_superuser($q->fetchColumn() ?: '');
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
  ensure_admin_profile_schema();
  $q = db()->prepare("SELECT email,name,profile_photo,must_change FROM users WHERE id=?");
  $q->execute([$_SESSION['uid']]); $user = $q->fetch();
  if (!$user) { $_SESSION = []; session_destroy(); header('Location: /admin/login.php'); exit; }
  $_SESSION['email'] = $user['email']; $_SESSION['name'] = $user['name'];
  $_SESSION['profile_photo'] = $user['profile_photo']; $_SESSION['must_change'] = (int)$user['must_change'];
  if (!empty($_SESSION['must_change'])) { header('Location: /admin/cambiar_clave.php'); exit; }
}

function image_has_animation(string $path, int $type): bool {
  if (!in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) return false;
  $handle = fopen($path, 'rb');
  if ($handle === false) throw new RuntimeException('No se pudo leer la imagen para optimizarla.');
  try {
    if ($type === IMAGETYPE_PNG) {
      if (fread($handle, 8) !== "\x89PNG\r\n\x1a\n") return false;
      while (!feof($handle)) {
        $chunk = fread($handle, 8);
        if (strlen($chunk) !== 8) break;
        $length = unpack('N', substr($chunk, 0, 4))[1];
        $name = substr($chunk, 4, 4);
        if ($name === 'acTL') return true;
        if ($name === 'IDAT' || fseek($handle, $length + 4, SEEK_CUR) !== 0) break;
      }
      return false;
    }

    $header = fread($handle, 12);
    if (strlen($header) !== 12 || substr($header, 0, 4) !== 'RIFF' || substr($header, 8, 4) !== 'WEBP') return false;
    $remaining = unpack('V', substr($header, 4, 4))[1] - 4;
    while ($remaining >= 8 && !feof($handle)) {
      $chunk = fread($handle, 8);
      if (strlen($chunk) !== 8) break;
      $length = unpack('V', substr($chunk, 4, 4))[1];
      $remaining -= 8;
      if (in_array(substr($chunk, 0, 4), ['ANIM', 'ANMF'], true)) return true;
      $skip = $length + ($length % 2);
      if ($skip > $remaining || fseek($handle, $skip, SEEK_CUR) !== 0) break;
      $remaining -= $skip;
    }
    return false;
  } finally {
    fclose($handle);
  }
}

function optimize_image_file(string $source, string $destination): bool {
  if (!function_exists('imagecreatefromstring') || !function_exists('imagescale')) {
    throw new RuntimeException('El servidor necesita la extensión GD de PHP para optimizar imágenes.');
  }
  $info = getimagesize($source);
  if ($info === false) throw new RuntimeException('El archivo de imagen no es válido.');
  [$width, $height] = $info;
  $type = $info[2] ?? 0;
  if (!in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)
      || ($type === IMAGETYPE_WEBP && !function_exists('imagewebp'))) return false;
  if (image_has_animation($source, $type)) return false;

  $originalSize = filesize($source);
  if ($originalSize === false) throw new RuntimeException('No se pudo determinar el tamaño de la imagen.');
  $contents = file_get_contents($source);
  if ($contents === false) throw new RuntimeException('No se pudo leer la imagen para optimizarla.');
  $image = @imagecreatefromstring($contents);
  unset($contents);
  if ($image === false) throw new RuntimeException('No se pudo procesar la imagen.');

  try {
    if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
      $metadata = @exif_read_data($source);
      $orientation = (int)($metadata['Orientation'] ?? 1);
      $rotate = static function (int $degrees) use (&$image): void {
        $rotated = imagerotate($image, $degrees, 0);
        if ($rotated === false) throw new RuntimeException('No se pudo orientar correctamente la imagen.');
        imagedestroy($image);
        $image = $rotated;
      };
      switch ($orientation) {
        case 2: imageflip($image, IMG_FLIP_HORIZONTAL); break;
        case 3: $rotate(180); break;
        case 4: imageflip($image, IMG_FLIP_VERTICAL); break;
        case 5: $rotate(90); imageflip($image, IMG_FLIP_HORIZONTAL); break;
        case 6: $rotate(270); break;
        case 7: $rotate(270); imageflip($image, IMG_FLIP_HORIZONTAL); break;
        case 8: $rotate(90); break;
      }
      $width = imagesx($image);
      $height = imagesy($image);
    }

    $maxDimension = 1600;
    if (max($width, $height) > $maxDimension) {
      $scaled = imagescale($image, $width >= $height ? $maxDimension : (int)round($width * $maxDimension / $height));
      if ($scaled === false) throw new RuntimeException('No se pudo redimensionar la imagen.');
      imagedestroy($image);
      $image = $scaled;
    }
    imagealphablending($image, false);
    imagesavealpha($image, true);
    $saved = match ($type) {
      IMAGETYPE_JPEG => imagejpeg($image, $destination, 90),
      IMAGETYPE_PNG => imagepng($image, $destination, 9),
      IMAGETYPE_WEBP => imagewebp($image, $destination, 90),
      default => false,
    };
  } finally {
    imagedestroy($image);
  }

  if (!$saved) {
    @unlink($destination);
    throw new RuntimeException('No se pudo guardar la versión optimizada de la imagen.');
  }
  $optimizedSize = filesize($destination);
  if ($optimizedSize === false || $optimizedSize >= $originalSize) {
    @unlink($destination);
    return false;
  }
  return true;
}

function image_optimization_candidates(): array {
  $files = [];
  foreach (['assets/img', 'inicio', 'directiva', 'uploads'] as $directory) {
    $root = ROOT.'/'.$directory;
    if (!is_dir($root)) continue;
    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
      if (!$file->isFile() || $file->isLink()
          || !in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp'], true)) continue;
      $files[] = $file->getPathname();
    }
  }
  sort($files, SORT_STRING);
  return $files;
}

function optimize_existing_images(int $offset, int $limit = 10): array {
  if ($offset < 0 || $limit < 1 || $limit > 20) throw new InvalidArgumentException('El lote de optimización no es válido.');
  $files = image_optimization_candidates();
  if ($offset > count($files)) throw new InvalidArgumentException('La posición del lote supera la cantidad de imágenes.');
  $batch = array_slice($files, $offset, $limit);
  $optimized = 0;
  $unchanged = 0;
  $alreadyBackedUp = 0;

  foreach ($batch as $source) {
    $relative = substr($source, strlen(ROOT) + 1);
    $backup = ROOT.'/includes/image-originals/'.$relative;
    if (is_file($backup)) {
      $alreadyBackedUp++;
      continue;
    }
    $temporary = dirname($source).'/.'.basename($source).'-'.bin2hex(random_bytes(6)).'.tmp';
    if (!optimize_image_file($source, $temporary)) {
      $unchanged++;
      continue;
    }

    $backupDirectory = dirname($backup);
    if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0755, true) && !is_dir($backupDirectory)) {
      @unlink($temporary);
      throw new RuntimeException('No se pudo crear la carpeta privada para respaldar las imágenes.');
    }
    $backupTemporary = $backup.'.'.bin2hex(random_bytes(6)).'.tmp';
    if (!copy($source, $backupTemporary) || !rename($backupTemporary, $backup)) {
      @unlink($temporary);
      @unlink($backupTemporary);
      throw new RuntimeException('No se pudo respaldar una imagen original; no se modificó.');
    }
    if (!rename($temporary, $source)) {
      @unlink($temporary);
      @unlink($backup);
      throw new RuntimeException('No se pudo reemplazar una imagen respaldada; el original se conservó.');
    }
    $optimized++;
  }
  return [
    'total'=>count($files),
    'next_offset'=>min($offset + count($batch), count($files)),
    'optimized'=>$optimized,
    'unchanged'=>$unchanged,
    'already_backed_up'=>$alreadyBackedUp,
  ];
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
  if ($type === 'image') {
    $optimized = $dest.'.tmp';
    if (optimize_image_file($f['tmp_name'], $optimized)) {
      if (!rename($optimized, $dest)) {
        @unlink($optimized);
        throw new Exception('No se pudo guardar la imagen optimizada.');
      }
      $ok = true;
    }
  }
  if (!$ok && !move_uploaded_file($f['tmp_name'], $dest)) throw new Exception('No se pudo guardar el archivo.');
  return "uploads/$sub/$name";
}
function del_file(?string $rel): void {
  if ($rel && str_starts_with($rel, 'uploads/') && !str_contains($rel, '..') && is_file(ROOT.'/'.$rel)) @unlink(ROOT.'/'.$rel);
}
function hero_slides(): array {
  $manifest = ROOT.'/uploads/hero-slides.json';
  if (!is_file($manifest)) {
    global $CARRUSEL;
    return array_values(array_map(
      static fn($image) => ['image'=>'inicio/'.$image],
      array_filter($CARRUSEL, static fn($image) => is_file(ROOT.'/inicio/'.$image))
    ));
  }
  $contents = file_get_contents($manifest);
  if ($contents === false) throw new RuntimeException('No se pudo leer la configuración del carrusel de Inicio.');
  $slides = json_decode($contents, true);
  if (!is_array($slides) || json_last_error() !== JSON_ERROR_NONE) throw new RuntimeException('La configuración del carrusel de Inicio no es válida.');
  $validSlides = [];
  foreach ($slides as $slide) {
    if (!is_array($slide)) throw new RuntimeException('Una entrada del carrusel de Inicio no es válida.');
    $image = $slide['image'] ?? '';
    if (!is_string($image) || (!str_starts_with($image, 'uploads/hero/') && !str_starts_with($image, 'inicio/'))
        || str_contains($image, '..') || !is_file(ROOT.'/'.$image)) {
      throw new RuntimeException('Una imagen del carrusel de Inicio no es válida.');
    }
    $validSlides[] = ['image'=>$image];
  }
  return array_values($validSlides);
}
function save_hero_slides(array $slides): void {
  if (count($slides) > 6) throw new RuntimeException('El carrusel puede tener un máximo de 6 imágenes.');
  foreach ($slides as &$slide) {
    $image = $slide['image'] ?? '';
    if (!is_string($image) || (!str_starts_with($image, 'uploads/hero/') && !str_starts_with($image, 'inicio/'))
        || str_contains($image, '..') || !is_file(ROOT.'/'.$image)) {
      throw new RuntimeException('Una imagen del carrusel no es válida.');
    }
    $slide = ['image'=>$image];
  }
  unset($slide);
  $encoded = json_encode($slides, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
  if ($encoded === false) throw new RuntimeException('No se pudo codificar la configuración del carrusel.');
  $directory = ROOT.'/uploads';
  if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
    throw new RuntimeException('No se pudo crear el directorio de carga.');
  }
  $temporary = $directory.'/.hero-slides-'.bin2hex(random_bytes(8)).'.tmp';
  if (file_put_contents($temporary, $encoded, LOCK_EX) === false) {
    @unlink($temporary);
    throw new RuntimeException('No se pudo guardar la configuración del carrusel.');
  }
  if (!rename($temporary, $directory.'/hero-slides.json')) {
    @unlink($temporary);
    throw new RuntimeException('No se pudo actualizar la configuración del carrusel.');
  }
}
function directiva_members(): array {
  $manifest = ROOT.'/uploads/directiva.json';
  if (!is_file($manifest)) {
    global $DIRECTIVA;
    return array_values($DIRECTIVA);
  }
  $contents = file_get_contents($manifest);
  if ($contents === false) throw new RuntimeException('No se pudo leer la configuración de la directiva.');
  $members = json_decode($contents, true);
  if (!is_array($members) || json_last_error() !== JSON_ERROR_NONE) throw new RuntimeException('La configuración de la directiva no es válida.');
  $validMembers = [];
  foreach ($members as $member) {
    if (!is_array($member)) throw new RuntimeException('Un perfil de la directiva no es válido.');
    $cargo = $member['cargo'] ?? null;
    $nombre = $member['nombre'] ?? null;
    $foto = $member['foto'] ?? null;
    $bio = $member['bio'] ?? null;
    if (!is_string($cargo) || !is_string($nombre) || !is_string($foto) || !is_string($bio)
        || (!str_starts_with($foto, 'uploads/directiva/') && !str_starts_with($foto, 'directiva/'))
        || str_contains($foto, '..') || !is_file(ROOT.'/'.$foto)) {
      throw new RuntimeException('Una foto o descripción de la directiva no es válida.');
    }
    $validMembers[] = ['cargo'=>$cargo, 'nombre'=>$nombre, 'foto'=>$foto, 'bio'=>$bio];
  }
  return array_values($validMembers);
}
function save_directiva_members(array $members): void {
  foreach ($members as &$member) {
    $cargo = $member['cargo'] ?? '';
    $nombre = $member['nombre'] ?? '';
    $foto = $member['foto'] ?? '';
    $bio = $member['bio'] ?? '';
    if (!is_string($cargo) || !is_string($nombre) || !is_string($foto) || !is_string($bio)
        || (!str_starts_with($foto, 'uploads/directiva/') && !str_starts_with($foto, 'directiva/'))
        || str_contains($foto, '..') || !is_file(ROOT.'/'.$foto)) {
      throw new RuntimeException('Una foto o descripción de la directiva no es válida.');
    }
    $member = ['cargo'=>$cargo, 'nombre'=>$nombre, 'foto'=>$foto, 'bio'=>clean($bio, 1200)];
  }
  unset($member);
  $encoded = json_encode($members, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
  if ($encoded === false) throw new RuntimeException('No se pudo codificar la configuración de la directiva.');
  $directory = ROOT.'/uploads';
  if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
    throw new RuntimeException('No se pudo crear el directorio de carga.');
  }
  $temporary = $directory.'/.directiva-'.bin2hex(random_bytes(8)).'.tmp';
  if (file_put_contents($temporary, $encoded, LOCK_EX) === false) {
    @unlink($temporary);
    throw new RuntimeException('No se pudo guardar la configuración de la directiva.');
  }
  if (!rename($temporary, $directory.'/directiva.json')) {
    @unlink($temporary);
    throw new RuntimeException('No se pudo actualizar la configuración de la directiva.');
  }
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
