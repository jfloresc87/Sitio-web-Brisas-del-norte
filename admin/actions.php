<?php
/* Guarda / elimina Noticias y Servicios (solo administradores) */
require __DIR__.'/../includes/bootstrap.php'; require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok()) { http_response_code(400); exit('Solicitud inválida.'); }
$a = $_POST['action'] ?? ''; $pdo = db(); $back = '/admin/index.php?s=';
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

  default: throw new Exception('Acción desconocida.');
  }
} catch (Throwable $x) { flash($x->getMessage(), 'danger'); $back .= str_starts_with($a, 'svc') ? 'servicios' : 'noticias'; }
header('Location: '.$back); exit;
