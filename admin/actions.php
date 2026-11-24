<?php
/* Guarda / elimina Noticias y Servicios (solo administradores) */
require __DIR__.'/../includes/bootstrap.php'; require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok()) { http_response_code(400); exit('Solicitud inválida.'); }
$a = $_POST['action'] ?? ''; $pdo = db(); $back = '/admin/index.php?s=';
$pendingUpload = null; $pendingUploads = [];
try {
  switch ($a) {
  case 'news_save':
    $id = (int)($_POST['id'] ?? 0); $type = $_POST['type'] ?? ''; $title = clean($_POST['title'] ?? '', 150);
    $seo = clean($_POST['seo'] ?? '', 160); $body = trim($_POST['body'] ?? '');
    if (!in_array($type, $TIPOS_NOTICIA, true)) throw new Exception('Elige un tipo de noticia.');
    if (mb_strlen($title) < 4 || mb_strlen($seo) < 10 || mb_strlen($body) < 10) throw new Exception('Completa título, resumen SEO (mín. 10 caracteres) y descripción.');
    $file = $_FILES['media'] ?? null; $has = $file && $file['error'] !== UPLOAD_ERR_NO_FILE;
    if (!$id && !$has) throw new Exception('Debes subir una imagen o un video.');
    $path = null; $mt = null;
    if ($has) { $path = save_upload($file, 'news', ['image','video'], 50); $mt = preg_match('/\.(mp4|webm)$/', $path) ? 'video' : 'image'; }
    if ($id) {
      $old = $pdo->prepare("SELECT media_path FROM news WHERE id=?"); $old->execute([$id]); $old = $old->fetchColumn();
      if ($old === false) throw new Exception('Noticia no encontrada.');
      $pdo->prepare("UPDATE news SET type=?,title=?,seo=?,body=? WHERE id=?")->execute([$type,$title,$seo,$body,$id]);
      if ($path) { $pdo->prepare("UPDATE news SET media_path=?,media_type=? WHERE id=?")->execute([$path,$mt,$id]); del_file($old); }
      flash('Noticia actualizada.');
    } else {
      $pdo->prepare("INSERT INTO news(type,title,seo,body,media_path,media_type,author) VALUES(?,?,?,?,?,?,?)")->execute([$type,$title,$seo,$body,$path,$mt,$_SESSION['email']]);
      flash('Noticia publicada.');
    }
    $back .= 'noticias'; break;

  case 'news_delete':
    $id = (int)$_POST['id']; $old = $pdo->prepare("SELECT media_path FROM news WHERE id=?"); $old->execute([$id]);
    del_file($old->fetchColumn() ?: null); $pdo->prepare("DELETE FROM news WHERE id=?")->execute([$id]); flash('Noticia eliminada.'); $back .= 'noticias'; break;

  case 'svc_save':
    $id = (int)($_POST['id'] ?? 0); $title = clean($_POST['title'] ?? '', 120);
    if (mb_strlen($title) < 3) throw new Exception('El servicio necesita un título.');
    $price = clean($_POST['price_text'] ?? '', 120); $desc = trim($_POST['description'] ?? ''); $wa = empty($_POST['whatsapp']) ? 0 : 1; $ord = (int)($_POST['sort_order'] ?? 0);
    $cur = ['cond_image'=>null,'gallery'=>'[]'];
    if ($id) { $q = $pdo->prepare("SELECT * FROM services WHERE id=?"); $q->execute([$id]); $cur = $q->fetch() ?: throw new Exception('Servicio no encontrado.'); }
    $gal = json_decode($cur['gallery'] ?: '[]', true) ?: [];
    foreach ((array)($_POST['rm_img'] ?? []) as $r) if (($k = array_search($r, $gal, true)) !== false) { del_file($r); unset($gal[$k]); }
    $gal = array_values($gal); $new = files_list('gallery');
    if (count($gal) + count($new) > 10) throw new Exception('Máximo 10 imágenes por servicio.');
    foreach ($new as $nf) $gal[] = save_upload($nf, 'services', ['image'], 8);
    $cond = $cur['cond_image'];
    if (!empty($_POST['rm_cond'])) { del_file($cond); $cond = null; }
    if (!empty($_FILES['cond']) && $_FILES['cond']['error'] !== UPLOAD_ERR_NO_FILE) { del_file($cond); $cond = save_upload($_FILES['cond'], 'services', ['image'], 8); }
    $v = [$title, $price, $desc, $wa, $cond, json_encode($gal), $ord];
    if ($id) $pdo->prepare("UPDATE services SET title=?,price_text=?,description=?,whatsapp=?,cond_image=?,gallery=?,sort_order=? WHERE id=?")->execute([...$v, $id]);
    else $pdo->prepare("INSERT INTO services(title,price_text,description,whatsapp,cond_image,gallery,sort_order) VALUES(?,?,?,?,?,?,?)")->execute($v);
    flash('Servicio guardado.'); $back .= 'servicios'; break;

  case 'svc_delete':
    $id = (int)$_POST['id']; $q = $pdo->prepare("SELECT * FROM services WHERE id=?"); $q->execute([$id]);
    if ($r = $q->fetch()) { foreach (json_decode($r['gallery'] ?: '[]', true) ?: [] as $p) del_file($p); del_file($r['cond_image']); $pdo->prepare("DELETE FROM services WHERE id=?")->execute([$id]); }
    flash('Servicio eliminado.'); $back .= 'servicios'; break;

  case 'hero_save':
    $back = '/admin/index.php?s=inicio';
    $slides = hero_slides();
    $remove = array_map('intval', array_filter((array)($_POST['remove_slide'] ?? []), 'is_scalar'));
    $updated = []; $removedFiles = [];
    foreach ($slides as $index=>$slide) {
      if (in_array($index, $remove, true)) {
        if (str_starts_with($slide['image'], 'uploads/hero/')) $removedFiles[] = $slide['image'];
        continue;
      }
      $updated[] = ['image'=>$slide['image']];
    }
    $heroFile = $_FILES['hero_image'] ?? null;
    if ($heroFile && $heroFile['error'] !== UPLOAD_ERR_NO_FILE) {
      if (count($updated) >= 6) throw new Exception('El carrusel puede tener un máximo de 6 imágenes.');
      $pendingUpload = save_upload($heroFile, 'hero', ['image'], 8);
      $updated[] = ['image'=>$pendingUpload];
    }
    save_hero_slides($updated);
    $pendingUpload = null;
    foreach ($removedFiles as $removedFile) {
      if (!in_array($removedFile, array_column($updated, 'image'), true)) del_file($removedFile);
    }
    flash('Carrusel de Inicio actualizado.'); break;

  case 'directiva_save':
    $back = '/admin/index.php?s=directiva';
    $members = directiva_members();
    $bios = $_POST['bio'] ?? [];
    if (!is_array($bios)) throw new Exception('Las descripciones de la directiva no son válidas.');
    $updated = []; $removedPhotos = [];
    foreach ($members as $index=>$member) {
      $bio = $bios[$index] ?? $member['bio'];
      if (!is_string($bio)) throw new Exception('Una descripción de la directiva no es válida.');
      $member['bio'] = clean($bio, 1200);
      $photo = $_FILES['photo_'.$index] ?? null;
      if ($photo && ($photo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $newPhoto = save_upload($photo, 'directiva', ['image'], 8);
        $pendingUploads[] = $newPhoto;
        if (str_starts_with($member['foto'], 'uploads/directiva/')) $removedPhotos[] = $member['foto'];
        $member['foto'] = $newPhoto;
      }
      $updated[] = $member;
    }
    save_directiva_members($updated);
    $pendingUploads = [];
    foreach ($removedPhotos as $removedPhoto) {
      if (!in_array($removedPhoto, array_column($updated, 'foto'), true)) del_file($removedPhoto);
    }
    flash('Directiva actualizada.'); break;

  case 'admin_save':
    if (!current_user_is_superuser()) { http_response_code(403); exit('Acceso denegado.'); }
    ensure_admin_profile_schema();
    $id = (int)($_POST['id'] ?? 0);
    $target = null;
    if ($id) {
      $q = $pdo->prepare("SELECT id,email,profile_photo FROM users WHERE id=?"); $q->execute([$id]); $target = $q->fetch();
      if (!$target) throw new Exception('La cuenta de administrador no existe.');
    }
    $name = clean($_POST['name'] ?? '', 120);
    $email = strtolower(clean($_POST['email'] ?? '', 190));
    $password = (string)($_POST['password'] ?? '');
    if ($name === '') throw new Exception('Ingresa el nombre del administrador.');
    if (mb_strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Ingresa un correo válido.');
    if ($target && is_superuser($target['email']) && $email !== ADMIN_SUPERUSER_EMAIL) throw new Exception('El correo del superusuario no se puede cambiar.');
    if (!$target && $password === '') throw new Exception('Define una clave inicial para el administrador.');
    if ($password !== '' && !pass_ok($password)) throw new Exception(PASS_MSG);
    $duplicate = $pdo->prepare("SELECT id FROM users WHERE email=? AND id<>?"); $duplicate->execute([$email, $id]);
    if ($duplicate->fetchColumn()) throw new Exception('Ya existe una cuenta con ese correo.');

    $photo = $target['profile_photo'] ?? null;
    if (!empty($_POST['rm_photo'])) $photo = null;
    $photoFile = $_FILES['photo'] ?? null;
    if ($photoFile && $photoFile['error'] !== UPLOAD_ERR_NO_FILE) {
      $pendingUpload = save_upload($photoFile, 'profiles', ['image'], 5);
      $photo = $pendingUpload;
    }
    if ($target) {
      if ($password !== '') {
        $pdo->prepare("UPDATE users SET name=?,email=?,profile_photo=?,pass_hash=?,must_change=1 WHERE id=?")
            ->execute([$name,$email,$photo,password_hash($password,PASSWORD_DEFAULT),$id]);
      } else {
        $pdo->prepare("UPDATE users SET name=?,email=?,profile_photo=? WHERE id=?")->execute([$name,$email,$photo,$id]);
      }
      if ($target['profile_photo'] && $target['profile_photo'] !== $photo) del_file($target['profile_photo']);
      if ($id === (int)$_SESSION['uid']) {
        $_SESSION['name'] = $name; $_SESSION['email'] = $email; $_SESSION['profile_photo'] = $photo;
        if ($password !== '') $_SESSION['must_change'] = 1;
      }
      $pendingUpload = null;
      flash('Administrador actualizado.');
    } else {
      $pdo->prepare("INSERT INTO users(email,name,profile_photo,pass_hash,must_change) VALUES(?,?,?,?,1)")
          ->execute([$email,$name,$photo,password_hash($password,PASSWORD_DEFAULT)]);
      $pendingUpload = null;
      flash('Administrador creado. Deberá cambiar su clave al iniciar sesión.');
    }
    $back = '/admin/index.php?s=administradores'; break;

  case 'admin_delete':
    if (!current_user_is_superuser()) { http_response_code(403); exit('Acceso denegado.'); }
    $id = (int)($_POST['id'] ?? 0);
    if ($id === (int)$_SESSION['uid']) throw new Exception('No puedes eliminar tu propia cuenta.');
    $q = $pdo->prepare("SELECT email,profile_photo FROM users WHERE id=?"); $q->execute([$id]); $target = $q->fetch();
    if (!$target) throw new Exception('La cuenta de administrador no existe.');
    if (is_superuser($target['email'])) throw new Exception('La cuenta del superusuario no se puede eliminar.');
    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    del_file($target['profile_photo']);
    flash('Administrador eliminado.'); $back = '/admin/index.php?s=administradores'; break;

  default: throw new Exception('Acción desconocida.');
  }
} catch (Throwable $x) {
  del_file($pendingUpload);
  foreach ($pendingUploads as $pending) del_file($pending);
  flash($x->getMessage(), 'danger');
  if ($a === 'hero_save') $back = '/admin/index.php?s=inicio';
  elseif ($a === 'directiva_save') $back = '/admin/index.php?s=directiva';
  elseif (str_starts_with($a, 'admin_')) $back = '/admin/index.php?s=administradores';
  else $back .= str_starts_with($a, 'svc') ? 'servicios' : 'noticias';
}
header('Location: '.$back); exit;
