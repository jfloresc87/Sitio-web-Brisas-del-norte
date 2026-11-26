<?php
require __DIR__.'/../includes/bootstrap.php'; require_login();
$s = $_GET['s'] ?? 'noticias'; if (!in_array($s, ['inicio','directiva','noticias','servicios','imagenes','comprobantes','administradores'], true)) $s = 'noticias';
$sub = ($_GET['sub'] ?? 'generar') === 'historial' ? 'historial' : 'generar';
$pdo = db(); ensure_admin_profile_schema();
$currentUserStmt = $pdo->prepare("SELECT id,email,name,profile_photo FROM users WHERE id=?");
$currentUserStmt->execute([$_SESSION['uid']]); $currentUser = $currentUserStmt->fetch();
if (!$currentUser) { $_SESSION = []; session_destroy(); header('Location: /admin/login.php'); exit; }
$_SESSION['email'] = $currentUser['email']; $_SESSION['name'] = $currentUser['name']; $_SESSION['profile_photo'] = $currentUser['profile_photo'];
$superuser = is_superuser($currentUser['email']);
if ($s === 'administradores' && !$superuser) { http_response_code(403); exit('Acceso denegado.'); }
$fl = flash();
$adminForm = is_array($_SESSION['admin_form'] ?? null) ? $_SESSION['admin_form'] : [];
unset($_SESSION['admin_form']);
$nav = fn($k, $label, $extra = '') => '<a class="nav-link'.($s === $k && !$extra ? ' active' : '').'" href="?s='.$k.'">'.$label.'</a>';
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title>Panel – <?= e($JUNTA['nombre']) ?></title>
<script>(()=>{let t='light';try{const s=localStorage.getItem('brisas-theme');if(s==='light'||s==='dark')t=s}catch(e){console.warn('No se pudo leer la preferencia de tema guardada.',e)}document.documentElement.dataset.theme=t;document.documentElement.dataset.bsTheme=t})();</script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="/assets/css/style.css?v=<?= filemtime(__DIR__.'/../assets/css/style.css') ?>" rel="stylesheet">
<style>.side{background:var(--sea-d)!important;color:#fff}.side .nav-link{color:rgba(255,255,255,.82);border-radius:.65rem}.side .nav-link.active,.side .nav-link:hover{background:rgba(255,255,255,.14);color:#fff}
.side .sub .nav-link{padding-left:2rem;font-size:.92rem}.admin-menu{--bs-offcanvas-width:290px}.admin-menu-toggle{position:fixed;top:16px;left:16px;z-index:1035;display:grid;width:44px;height:44px;place-items:center;border:1px solid rgba(var(--sea-rgb),.24);border-radius:14px;background:var(--surface);box-shadow:var(--shadow-md);color:var(--sea-d)}.admin-menu-toggle:hover{background:var(--foam)}.admin-menu-icon,.admin-menu-icon:before,.admin-menu-icon:after{position:relative;display:block;width:21px;height:2px;border-radius:2px;background:currentColor}.admin-menu-icon:before,.admin-menu-icon:after{position:absolute;left:0;content:""}.admin-menu-icon:before{top:-6px}.admin-menu-icon:after{top:6px}.admin-dashboard .admin-topbar{padding-left:60px}.thumb-sm{width:90px;height:60px;object-fit:cover;border-radius:.3rem}.gimg{position:relative;display:inline-block}.gimg img{width:100px;height:70px;object-fit:cover;border-radius:.3rem}
@media(min-width:768px){.admin-dashboard>.container-fluid{display:flex;align-items:stretch;padding:0}.admin-menu{position:sticky;top:0;left:auto;z-index:auto;display:flex;flex:0 0 250px;width:250px!important;height:100vh;max-width:none;visibility:visible!important;transform:none!important;transition:none;border:0}.admin-menu .offcanvas-body{overflow-y:auto}.admin-menu .btn-close,.admin-menu-toggle{display:none}.admin-main{flex:1;min-width:0}.admin-dashboard .admin-topbar{padding-left:1rem}}</style>
</head><body class="bg-light admin-dashboard">
<div class="container-fluid">
<button class="admin-menu-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminMenu" aria-controls="adminMenu" aria-label="Abrir menú de administración"><span class="admin-menu-icon" aria-hidden="true"></span></button>
<aside class="offcanvas offcanvas-start side admin-menu" tabindex="-1" id="adminMenu" aria-labelledby="adminMenuLabel">
  <div class="offcanvas-header">
    <div class="d-flex align-items-center gap-2 text-white"><img src="/<?= e($JUNTA['logo']) ?>" width="38" height="38" alt=""><strong class="small" id="adminMenuLabel"><?= e($JUNTA['nombre']) ?></strong></div>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Cerrar menú"></button>
  </div>
  <div class="offcanvas-body p-3">
  <div class="admin-theme-control d-flex align-items-center justify-content-between mb-3">
    <span class="small text-white-50">Apariencia</span>
    <button class="theme-toggle" id="themeToggle" type="button" aria-label="Cambiar a modo oscuro" title="Cambiar tema">
      <span class="theme-icon theme-icon-sun" aria-hidden="true">☀</span>
      <span class="theme-icon theme-icon-moon" aria-hidden="true">☾</span>
    </button>
  </div>
  <nav class="nav flex-column gap-1">
    <?= $nav('inicio', 'Inicio') ?>
    <?= $nav('directiva', 'Directiva') ?>
    <?= $nav('noticias', 'Noticias') ?><?= $nav('servicios', 'Servicios') ?>
    <?= $nav('imagenes', 'Optimizar imágenes') ?>
    <?= $superuser ? $nav('administradores', 'Administradores') : '' ?>
    <a class="nav-link<?= $s==='comprobantes' ? ' active' : '' ?>" href="?s=comprobantes&sub=generar">Comprobantes</a>
    <?php if ($s === 'comprobantes'): ?><div class="sub nav flex-column">
      <a class="nav-link<?= $sub==='historial' ? ' active' : '' ?>" href="?s=comprobantes&sub=historial">Historial de comprobantes</a>
      <a class="nav-link<?= $sub==='generar' ? ' active' : '' ?>" href="?s=comprobantes&sub=generar">Generar comprobante</a></div><?php endif; ?>
  </nav>
  <hr class="border-light"><div class="small text-white-50 text-break"><?= e($_SESSION['email']) ?></div>
  <a class="nav-link text-white-50 px-0" href="/" target="_blank">Ver sitio</a><a class="nav-link text-white px-0" href="/admin/logout.php">Cerrar sesión</a>
  </div>
</aside>
<main class="admin-main p-3 p-lg-4">
<header class="admin-topbar">
  <?php if (!empty($currentUser['profile_photo']) && is_file(__DIR__.'/../'.$currentUser['profile_photo'])): ?>
    <img class="admin-avatar" src="/<?= e($currentUser['profile_photo']) ?>" alt="">
  <?php else: ?><span class="admin-avatar admin-avatar-placeholder" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($currentUser['name'] ?: $currentUser['email'], 0, 1))) ?></span><?php endif; ?>
  <strong>Bienvenido <?= e($currentUser['name'] ?: $currentUser['email']) ?></strong>
</header>
<?php if ($fl): ?><div class="alert alert-<?= e($fl[1]) ?> alert-dismissible"><?= e($fl[0]) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<?php /* ================= INICIO ================= */ if ($s === 'inicio'):
  $slides = hero_slides(); ?>
  <h1 class="h3 mb-2">Carrusel de Inicio</h1>
  <p class="text-muted mb-4">Administra hasta 6 fotos de fondo. Cambian automáticamente con una transición de difuminado; la historia de la junta permanece fija en Inicio.</p>
  <form method="post" action="actions.php" enctype="multipart/form-data" class="card card-body">
    <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="hero_save">
    <?php if (count($slides) > 6): ?><p class="alert alert-warning">Hay más de 6 fotos guardadas. Quita las que sobren para poder guardar el carrusel con el nuevo límite.</p><?php endif; ?>
    <?php if ($slides): ?><div class="row g-3 mb-4">
      <?php foreach ($slides as $i=>$slide): ?>
        <div class="col-md-6"><div class="hero-slide-editor">
        <img src="/<?= e($slide['image']) ?>" alt="Foto <?= $i + 1 ?> del carrusel de Inicio">
          <label class="form-check-label mt-2"><input class="form-check-input" type="checkbox" name="remove_slide[]" value="<?= $i ?>"> Quitar esta foto</label>
        </div></div>
      <?php endforeach; ?>
    </div><?php else: ?><p class="text-muted">Todavía no hay fotos en el carrusel.</p><?php endif; ?>
    <div class="hero-slide-add">
      <h2 class="h5">Agregar foto</h2>
      <div class="row g-3 align-items-end">
        <div class="col-md-6"><label class="form-label" for="heroImage">Foto (JPG, PNG o WEBP, máximo 8 MB)</label><input id="heroImage" type="file" name="hero_image" class="form-control" accept="image/jpeg,image/png,image/webp" <?= count($slides) >= 6 ? 'disabled' : '' ?>></div>
      </div>
      <p class="form-text text-muted mb-0 mt-2">Recomendado: fotos horizontales 16:9. Máximo 6 imágenes; se guardan en <code>/uploads/hero/</code>.</p>
    </div>
    <div class="mt-4"><button class="btn btn-sea btn-lg">Guardar carrusel</button></div>
  </form>

<?php /* ================= DIRECTIVA ================= */ elseif ($s === 'directiva'):
  $members = directiva_members(); ?>
  <h1 class="h3 mb-2">Directiva</h1>
  <p class="text-muted mb-4">Actualiza la biografía y la foto de cada integrante. Si no seleccionas una foto, se conservará la actual.</p>
  <form method="post" action="actions.php" enctype="multipart/form-data" class="card card-body">
    <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="directiva_save">
    <div class="row g-3">
      <?php foreach ($members as $i=>$member): ?>
        <div class="col-12"><section class="directiva-member-editor">
          <div class="d-flex flex-column flex-md-row gap-3">
            <img class="directiva-photo-preview" src="/<?= e($member['foto']) ?>" alt="Foto actual de <?= e($member['nombre']) ?>">
            <div class="flex-grow-1">
              <h2 class="h5 mb-1"><?= e($member['cargo']) ?> · <?= e($member['nombre']) ?></h2>
              <label class="form-label mt-2" for="directivaBio<?= $i ?>">Descripción / biografía</label>
              <textarea class="form-control" id="directivaBio<?= $i ?>" name="bio[<?= $i ?>]" rows="4" maxlength="1200"><?= e($member['bio']) ?></textarea>
              <label class="form-label mt-3" for="directivaPhoto<?= $i ?>">Cambiar foto (JPG, PNG o WEBP, máximo 8 MB)</label>
              <input class="form-control" id="directivaPhoto<?= $i ?>" type="file" name="photo_<?= $i ?>" accept="image/jpeg,image/png,image/webp">
            </div>
          </div>
        </section></div>
      <?php endforeach; ?>
    </div>
    <div class="mt-4"><button class="btn btn-sea btn-lg">Guardar directiva</button></div>
  </form>

<?php /* ================= ADMINISTRADORES ================= */ elseif ($s === 'administradores'):
  $edit = $_GET['edit'] ?? ''; $editingAdmin = null;
  if ($edit !== '' && $edit !== 'new') {
    $q = $pdo->prepare("SELECT id,email,name,profile_photo FROM users WHERE id=?");
    $q->execute([(int)$edit]); $editingAdmin = $q->fetch();
  }
  if ($edit === 'new' || $editingAdmin): ?>
  <h1 class="h3 mb-3"><?= $editingAdmin ? 'Editar administrador' : 'Agregar administrador' ?></h1>
  <form method="post" action="actions.php" enctype="multipart/form-data" class="card card-body">
    <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="admin_save">
    <input type="hidden" name="id" value="<?= (int)($editingAdmin['id'] ?? 0) ?>">
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label" for="adminName">Nombre</label><input id="adminName" name="name" class="form-control" required maxlength="120" value="<?= e($editingAdmin['name'] ?? $adminForm['name'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label" for="adminEmail">Correo</label><input id="adminEmail" type="email" name="email" class="form-control" required maxlength="190" value="<?= e($editingAdmin['email'] ?? $adminForm['email'] ?? '') ?>" <?= is_superuser($editingAdmin['email'] ?? '') ? 'readonly' : '' ?>></div>
      <div class="col-md-6"><label class="form-label" for="adminPassword"><?= $editingAdmin ? 'Nueva clave (opcional)' : 'Clave inicial' ?></label>
        <div class="input-group">
          <input id="adminPassword" type="password" name="password" class="form-control" minlength="8" pattern="(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}" title="<?= e(PASS_MSG) ?>" <?= $editingAdmin ? '' : 'required' ?> autocomplete="new-password">
          <button class="btn btn-outline-secondary" id="adminPasswordToggle" type="button" aria-controls="adminPassword" aria-label="Mostrar clave" aria-pressed="false">Mostrar</button>
        </div>
        <small class="form-text text-muted"><?= $editingAdmin ? 'Si la cambias, deberá actualizarla al iniciar sesión.' : e(PASS_MSG).' Se solicitará cambiarla al primer ingreso.' ?></small></div>
      <div class="col-md-6"><label class="form-label" for="adminPhoto">Foto de perfil (máx. 5 MB; JPG, PNG o WEBP)</label>
        <input id="adminPhoto" type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
        <?php if (!empty($editingAdmin['profile_photo'])): ?><div class="mt-2 d-flex align-items-center gap-3"><img class="admin-avatar" src="/<?= e($editingAdmin['profile_photo']) ?>" alt=""><label class="small"><input type="checkbox" name="rm_photo" value="1"> Quitar foto</label></div><?php endif; ?></div>
    </div>
    <div class="mt-4 d-flex gap-2"><button class="btn btn-sea btn-lg">Guardar administrador</button><a class="btn btn-outline-secondary btn-lg" href="?s=administradores">Cancelar</a></div>
  </form>
  <?php else:
    $admins = $pdo->query("SELECT id,email,name,profile_photo,last_login FROM users ORDER BY name,email")->fetchAll(); ?>
  <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0">Administradores</h1><a class="btn btn-sea" href="?s=administradores&edit=new">+ Agregar administrador</a></div>
  <div class="card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Cuenta</th><th>Correo</th><th>Último acceso</th><th></th></tr></thead><tbody>
  <?php foreach ($admins as $admin): ?><tr>
    <td><div class="d-flex align-items-center gap-2"><?php if ($admin['profile_photo'] && is_file(__DIR__.'/../'.$admin['profile_photo'])): ?><img class="admin-avatar" src="/<?= e($admin['profile_photo']) ?>" alt=""><?php else: ?><span class="admin-avatar admin-avatar-placeholder" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($admin['name'] ?: $admin['email'], 0, 1))) ?></span><?php endif; ?>
      <span><?= e($admin['name'] ?: 'Sin nombre') ?><?= is_superuser($admin['email']) ? ' <span class="badge text-bg-warning">Superusuario</span>' : '' ?></span></div></td>
    <td><?= e($admin['email']) ?></td><td><?= $admin['last_login'] ? date('d/m/Y H:i', strtotime($admin['last_login'])) : '—' ?></td>
    <td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-secondary" href="?s=administradores&edit=<?= (int)$admin['id'] ?>">Editar</a>
      <?php if (!is_superuser($admin['email']) && (int)$admin['id'] !== (int)$_SESSION['uid']): ?>
      <form method="post" action="actions.php" class="d-inline" onsubmit="return confirm('¿Eliminar el acceso de <?= e($admin['email']) ?>?')"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="admin_delete"><input type="hidden" name="id" value="<?= (int)$admin['id'] ?>"><button class="btn btn-sm btn-outline-danger">Eliminar</button></form>
      <?php endif; ?>
    </td></tr>
  <?php endforeach; ?>
  </tbody></table></div></div>
  <?php endif; ?>

<?php /* ================= NOTICIAS ================= */ elseif ($s === 'noticias'):
  $edit = $_GET['edit'] ?? ''; $n = null;
  if ($edit && $edit !== 'new') { $q = $pdo->prepare("SELECT * FROM news WHERE id=?"); $q->execute([(int)$edit]); $n = $q->fetch(); }
  $editingType = ($n['type'] ?? '') === 'Asambleas' ? 'Asambleas y Reuniones' : ($n['type'] ?? '');
  if ($edit === 'new' || $n): ?>
  <h1 class="h3 mb-3"><?= $n ? 'Editar noticia' : 'Nueva noticia' ?></h1>
  <form method="post" action="actions.php" enctype="multipart/form-data" class="card card-body" id="newsForm">
    <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="news_save"><input type="hidden" name="id" value="<?= (int)($n['id'] ?? 0) ?>">
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label">Tipo de noticia</label><select name="type" class="form-select" required>
        <option value="">Selecciona…</option><?php foreach ($TIPOS_NOTICIA as $t): ?><option<?= $editingType === $t ? ' selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-8"><label class="form-label">Título de la noticia</label><input name="title" class="form-control" required maxlength="150" value="<?= e($n['title'] ?? '') ?>"></div>
      <div class="col-12"><label class="form-label">Resumen SEO <small class="text-muted">(se muestra en la tarjeta; <span id="seoCount">0</span>/160)</small></label>
        <input name="seo" id="seo" class="form-control" required minlength="10" maxlength="160" value="<?= e($n['seo'] ?? '') ?>"></div>
      <div class="col-12"><label class="form-label">Descripción</label><textarea name="body" class="form-control" rows="8" required minlength="10"><?= e($n['body'] ?? '') ?></textarea></div>
      <div class="col-12"><label class="form-label">Imagen o video <small class="text-muted">(JPG, PNG, WEBP, MP4 o WEBM · máx. 50 MB<?= $n ? ' · deja vacío para conservar el actual' : '' ?>)</small></label>
        <input type="file" name="media" id="media" class="form-control" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm" <?= $n ? '' : 'required' ?>>
        <?php if ($n): ?><div class="mt-2"><?= $n['media_type']==='video' ? '<video src="/'.e($n['media_path']).'" height="90" controls preload="none"></video>' : '<img class="thumb-sm" src="/'.e($n['media_path']).'" alt="">' ?></div><?php endif; ?>
        <div class="text-danger small mt-1 d-none" id="mediaErr">El archivo supera los 50 MB.</div></div>
    </div>
    <div class="mt-4 d-flex gap-2"><button class="btn btn-sea btn-lg" id="pubBtn"><?= $n ? 'Guardar cambios' : 'Publicar' ?></button><a class="btn btn-outline-secondary btn-lg" href="?s=noticias">Cancelar</a></div>
  </form>
  <?php else: $rows = $pdo->query("SELECT id,type,title,media_path,media_type,created_at FROM news ORDER BY created_at DESC")->fetchAll(); ?>
  <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0">Noticias</h1><a class="btn btn-sea" href="?s=noticias&edit=new">+ Nueva noticia</a></div>
  <div class="card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th></th><th>Título</th><th>Tipo</th><th>Fecha</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr>
    <td><?= $r['media_type']==='video' ? '<span class="badge text-bg-secondary">Video</span>' : '<img class="thumb-sm" src="/'.e($r['media_path']).'" alt="">' ?></td>
    <td><?= e($r['title']) ?></td><td><span class="badge text-bg-warning"><?= e($r['type'] === 'Asambleas' ? 'Asambleas y Reuniones' : $r['type']) ?></span></td><td><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
    <td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-secondary" href="?s=noticias&edit=<?= $r['id'] ?>">Editar</a>
      <form method="post" action="actions.php" class="d-inline" onsubmit="return confirm('¿Eliminar esta noticia?')"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="news_delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-outline-danger">Eliminar</button></form></td></tr>
  <?php endforeach; if (!$rows) echo '<tr><td colspan="5" class="text-center text-muted py-4">Aún no hay noticias. Crea la primera con “Nueva noticia”.</td></tr>'; ?>
  </tbody></table></div></div>
  <?php endif; ?>

<?php /* ================= SERVICIOS ================= */ elseif ($s === 'servicios'):
  $edit = $_GET['edit'] ?? ''; $v = null;
  if ($edit && $edit !== 'new') { $q = $pdo->prepare("SELECT * FROM services WHERE id=?"); $q->execute([(int)$edit]); $v = $q->fetch(); }
  if ($edit === 'new' || $v): $gal = json_decode($v['gallery'] ?? '[]', true) ?: []; ?>
  <h1 class="h3 mb-3"><?= $v ? 'Editar servicio' : 'Nuevo servicio' ?></h1>
  <form method="post" action="actions.php" enctype="multipart/form-data" class="card card-body">
    <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="svc_save"><input type="hidden" name="id" value="<?= (int)($v['id'] ?? 0) ?>">
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label">Título</label><input name="title" class="form-control" required maxlength="120" value="<?= e($v['title'] ?? '') ?>"></div>
      <div class="col-md-4"><label class="form-label">Valor (texto libre)</label><input name="price_text" class="form-control" maxlength="120" placeholder="$8.000 la hora" value="<?= e($v['price_text'] ?? '') ?>"></div>
      <div class="col-md-2"><label class="form-label">Orden</label><input type="number" name="sort_order" class="form-control" value="<?= (int)($v['sort_order'] ?? 0) ?>"></div>
      <div class="col-12"><label class="form-label">Descripción / condiciones</label><textarea name="description" class="form-control" rows="5"><?= e($v['description'] ?? '') ?></textarea></div>
      <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="whatsapp" id="wa" value="1" <?= !empty($v['whatsapp']) ? 'checked' : '' ?>><label class="form-check-label" for="wa">Mostrar botón “Agendar por WhatsApp”</label></div></div>
      <div class="col-md-6"><label class="form-label">Imagen de condiciones / reglas</label><input type="file" name="cond" class="form-control" accept="image/jpeg,image/png,image/webp">
        <?php if (!empty($v['cond_image'])): ?><div class="mt-2 d-flex align-items-center gap-3"><img class="thumb-sm" src="/<?= e($v['cond_image']) ?>" alt=""><label class="small"><input type="checkbox" name="rm_cond" value="1"> Quitar</label></div><?php endif; ?></div>
      <div class="col-md-6"><label class="form-label">Agregar imágenes a la galería <small class="text-muted">(recomendado 3 a 10 en total)</small></label><input type="file" name="gallery[]" class="form-control" multiple accept="image/jpeg,image/png,image/webp"></div>
      <div class="col-12"><p class="small text-muted mb-0">Las imágenes de galería se guardan en <code>/uploads/services/</code>. También puedes subir allí por FTP las fichas informativas con los nombres indicados en las instrucciones de instalación.</p></div>
      <?php if ($gal): ?><div class="col-12"><div class="form-label">Imágenes actuales (<?= count($gal) ?>) – marca las que quieras quitar</div><div class="d-flex flex-wrap gap-3">
        <?php foreach ($gal as $p): ?><label class="gimg"><img src="/<?= e($p) ?>" alt=""><br><input type="checkbox" name="rm_img[]" value="<?= e($p) ?>"> Quitar</label><?php endforeach; ?></div></div><?php endif; ?>
    </div>
    <div class="mt-4 d-flex gap-2"><button class="btn btn-sea btn-lg">Guardar servicio</button><a class="btn btn-outline-secondary btn-lg" href="?s=servicios">Cancelar</a></div>
  </form>
  <?php else: $rows = $pdo->query("SELECT * FROM services ORDER BY sort_order,id")->fetchAll(); ?>
  <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0">Servicios</h1><a class="btn btn-sea" href="?s=servicios&edit=new">+ Nuevo servicio</a></div>
  <div class="card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Servicio</th><th>Valor</th><th>Imágenes</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr><td><?= e($r['title']) ?></td><td><?= e($r['price_text']) ?></td><td><?= count(json_decode($r['gallery'] ?: '[]', true) ?: []) ?><?= $r['cond_image'] ? ' + condiciones' : '' ?></td>
    <td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-secondary" href="?s=servicios&edit=<?= $r['id'] ?>">Editar</a>
    <form method="post" action="actions.php" class="d-inline" onsubmit="return confirm('¿Eliminar este servicio y sus imágenes?')"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="svc_delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-outline-danger">Eliminar</button></form></td></tr>
  <?php endforeach; ?></tbody></table></div></div>
  <?php endif; ?>

<?php /* ================= OPTIMIZACIÓN DE IMÁGENES ================= */ elseif ($s === 'imagenes'):
  $imageCount = count(image_optimization_candidates());
  $imageOffset = min($imageCount, max(0, (int)($_GET['offset'] ?? 0)));
  $imageRemaining = $imageCount - $imageOffset; ?>
  <h1 class="h3 mb-2">Optimizar imágenes</h1>
  <p class="text-muted mb-4">Procesa las imágenes existentes en lotes de 10 para no sobrecargar el servidor. Solo se reemplazan cuando la versión optimizada pesa menos; antes se guarda una copia original en una carpeta privada.</p>
  <div class="card card-body">
    <p class="mb-2">Progreso: <?= $imageOffset ?> de <?= $imageCount ?> archivos revisados.</p>
    <div class="progress mb-4" role="progressbar" aria-label="Progreso de optimización" aria-valuenow="<?= $imageCount ? (int)round($imageOffset * 100 / $imageCount) : 100 ?>" aria-valuemin="0" aria-valuemax="100">
      <div class="progress-bar" style="width:<?= $imageCount ? (int)round($imageOffset * 100 / $imageCount) : 100 ?>%"></div>
    </div>
    <?php if ($imageRemaining > 0): ?>
      <p class="text-muted">Quedan <?= $imageRemaining ?> imágenes por revisar. Cada lote puede tardar según el tamaño de los archivos.</p>
      <form method="post" action="actions.php">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <input type="hidden" name="action" value="images_optimize">
        <input type="hidden" name="offset" value="<?= $imageOffset ?>">
        <button class="btn btn-sea">Procesar siguiente lote (10 imágenes)</button>
      </form>
    <?php else: ?>
      <p class="mb-0">Todas las imágenes existentes ya fueron revisadas.</p>
    <?php endif; ?>
    <p class="small text-muted mt-3 mb-0">Se revisan imágenes en <code>/assets/img/</code>, <code>/inicio/</code>, <code>/directiva/</code> y <code>/uploads/</code>. Las imágenes animadas se conservan sin cambios.</p>
  </div>

<?php /* ================= COMPROBANTES ================= */ else: if ($sub === 'historial'):
  $q = trim($_GET['q'] ?? ''); $pg = max(1, (int)($_GET['p'] ?? 1)); $per = 25; $rec = db(DB_REC);
  $w = ''; $par = [];
  if ($q !== '') { $w = "WHERE CONCAT(name,' ',lastname) LIKE ? OR id = ? OR concept LIKE ?"; $par = ["%$q%", (int)ltrim($q, '0'), "%$q%"]; }
  $c = $rec->prepare("SELECT COUNT(*) FROM receipts $w"); $c->execute($par); $tot = (int)$c->fetchColumn();
  $st = $rec->prepare("SELECT * FROM receipts $w ORDER BY id DESC LIMIT $per OFFSET ".(($pg-1)*$per)); $st->execute($par); $rows = $st->fetchAll(); $pages = max(1, (int)ceil($tot/$per)); ?>
  <div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><h1 class="h3 mb-0">Historial de comprobantes</h1>
    <form class="d-flex gap-2"><input type="hidden" name="s" value="comprobantes"><input type="hidden" name="sub" value="historial"><input name="q" class="form-control" placeholder="Buscar nombre, N° o concepto" value="<?= e($q) ?>"><button class="btn btn-sea">Buscar</button></form></div>
  <div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>N°</th><th>Emisión</th><th>Concepto</th><th>Nombre</th><th>Evento</th><th>Pago</th><th class="text-end">Valor</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr><td><strong><?= str_pad($r['id'], 6, '0', STR_PAD_LEFT) ?></strong></td><td><?= date('d/m/Y H:i', strtotime($r['issued_at'])) ?></td>
    <td><?= e($r['concept']) ?><?= $r['other_concept'] ? '<br><small class="text-muted">'.e($r['other_concept']).'</small>' : '' ?></td><td><?= e($r['name'].' '.$r['lastname']) ?></td>
    <td><?= $r['event_date'] ? date('d/m/Y', strtotime($r['event_date'])) : '—' ?></td><td><?= e($r['method']) ?></td><td class="text-end"><?= clp($r['amount']) ?></td>
    <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="pdf.php?id=<?= $r['id'] ?>" target="_blank">Ver / reimprimir</a></td></tr>
  <?php endforeach; if (!$rows) echo '<tr><td colspan="8" class="text-center text-muted py-4">No hay comprobantes para mostrar.</td></tr>'; ?></tbody></table></div></div>
  <?php if ($pages > 1): ?><nav class="mt-3"><ul class="pagination"><?php for ($i = 1; $i <= $pages; $i++): ?><li class="page-item<?= $i===$pg ? ' active' : '' ?>"><a class="page-link" href="?s=comprobantes&sub=historial&q=<?= urlencode($q) ?>&p=<?= $i ?>"><?= $i ?></a></li><?php endfor; ?></ul></nav><?php endif; ?>

<?php else: ?>
  <h1 class="h3 mb-3">Generar comprobante</h1>
  <form id="recForm" class="card card-body" autocomplete="off" novalidate data-cancha="<?= PRECIO_CANCHA_HORA ?>" data-sede="<?= PRECIO_SEDE ?>" data-hini="<?= CANCHA_HORA_INICIO ?>">
    <input type="hidden" name="csrf" value="<?= csrf() ?>">
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label">Concepto de pago</label><select name="concept" id="concept" class="form-select" required>
        <option value="">Selecciona…</option><option value="Arriendo de Cancha">Arriendo de Cancha</option><option value="Arriendo de Sede">Arriendo de Sede</option><option value="Otros Pagos">Otros Pagos</option></select></div>
      <div class="col-md-4"><label class="form-label">Nombre</label><input name="name" class="form-control" required maxlength="60"></div>
      <div class="col-md-4"><label class="form-label">Apellido</label><input name="lastname" class="form-control" required maxlength="60"></div>
      <div class="col-md-4" data-for="cancha sede"><label class="form-label">Teléfono</label><input name="phone" class="form-control" pattern="[0-9+ ]{8,15}" maxlength="15" inputmode="tel"></div>
      <div class="col-md-4" data-for="sede"><label class="form-label">RUT</label><input name="rut" id="rut" class="form-control" maxlength="12" placeholder="12.345.678-5"><div class="invalid-feedback d-block small text-danger d-none" id="rutErr">RUT no válido</div></div>
      <div class="col-md-4" data-for="cancha sede otros"><label class="form-label" id="dateLbl">Fecha del evento</label><input type="date" name="event_date" class="form-control"></div>
      <div class="col-md-4" data-for="cancha"><label class="form-label">Hora de inicio</label><select name="hour_start" id="hs" class="form-select"></select></div>
      <div class="col-md-4" data-for="cancha"><label class="form-label">Cantidad de horas</label><select name="hour_qty" id="hq" class="form-select"></select></div>
      <div class="col-md-8" data-for="otros"><label class="form-label">Concepto del pago</label><input name="other_concept" class="form-control" maxlength="150" placeholder="Ej: Cuota mensual, multa, certificado…"></div>
      <div class="col-md-4"><label class="form-label">Forma de pago</label><select name="method" class="form-select" required><option value="">Selecciona…</option><option>Efectivo</option><option>Transferencia</option></select></div>
      <div class="col-md-4" data-for="cancha sede"><label class="form-label d-block">Valor</label>
        <div class="form-check form-switch mt-1"><input class="form-check-input" type="checkbox" role="switch" name="special" id="special" value="1"><label class="form-check-label" for="special">Valor especial</label></div></div>
      <div class="col-md-4" id="amountBox" data-for="otros"><label class="form-label">Valor a cobrar ($)</label><input name="amount" id="amount" class="form-control" inputmode="numeric" maxlength="10"></div>
    </div>
    <div class="alert alert-info mt-4 mb-0 d-flex justify-content-between align-items-center"><span id="hint" class="small"></span><strong class="fs-4" id="total">$0</strong></div>
    <div class="mt-3"><button class="btn btn-sea btn-lg" id="genBtn" disabled>Generar comprobante</button> <span class="text-muted small ms-2" id="genMsg"></span></div>
  </form>
  <script src="/assets/js/receipt.js"></script>
<?php endif; endif; ?>
</main></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const themeToggle=document.getElementById('themeToggle');
function syncThemeControl(){const dark=document.documentElement.dataset.theme==='dark';themeToggle.setAttribute('aria-label',dark?'Cambiar a modo claro':'Cambiar a modo oscuro');themeToggle.setAttribute('title',dark?'Cambiar a modo claro':'Cambiar a modo oscuro');themeToggle.setAttribute('aria-pressed',String(dark));}
syncThemeControl();
themeToggle.addEventListener('click',()=>{const next=document.documentElement.dataset.theme==='dark'?'light':'dark';document.documentElement.dataset.theme=next;document.documentElement.dataset.bsTheme=next;syncThemeControl();try{localStorage.setItem('brisas-theme',next)}catch(error){console.warn('No se pudo guardar la preferencia de tema.',error)}});
const adminPassword=document.getElementById('adminPassword');
const adminPasswordToggle=document.getElementById('adminPasswordToggle');
if(adminPassword&&adminPasswordToggle)adminPasswordToggle.addEventListener('click',()=>{const visible=adminPassword.type==='password';adminPassword.type=visible?'text':'password';adminPasswordToggle.textContent=visible?'Ocultar':'Mostrar';adminPasswordToggle.setAttribute('aria-label',visible?'Ocultar clave':'Mostrar clave');adminPasswordToggle.setAttribute('aria-pressed',String(visible))});
</script>
<?php if ($s === 'noticias'): ?><script>
const seo=document.getElementById('seo'),sc=document.getElementById('seoCount'),m=document.getElementById('media');
if(seo){const u=()=>sc.textContent=seo.value.length;seo.addEventListener('input',u);u();
 m.addEventListener('change',()=>{const big=m.files[0]&&m.files[0].size>50*1048576;document.getElementById('mediaErr').classList.toggle('d-none',!big);document.getElementById('pubBtn').disabled=big;});
 document.getElementById('newsForm').addEventListener('submit',e=>{const b=document.getElementById('pubBtn');b.disabled=true;b.textContent='Subiendo…';});}
</script><?php endif; ?>
</body></html>
