<?php
require __DIR__.'/includes/bootstrap.php';
$_SESSION['form_t'] = time();
$news = $services = []; $dbError = false;
try {
  $news = db()->query("SELECT * FROM news ORDER BY created_at DESC LIMIT 60")->fetchAll();
  $services = db()->query("SELECT * FROM services ORDER BY sort_order, id")->fetchAll();
} catch (Throwable $x) { $dbError = true; }
$wa = preg_replace('/\D/', '', $JUNTA['whatsapp']);
?><!doctype html>
<html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($JUNTA['nombre']) ?></title>
<meta name="description" content="<?= e($JUNTA['nombre'].' – '.$JUNTA['lema']) ?>">
<link rel="icon" href="/<?= e($JUNTA['logo']) ?>">
<script>
  (() => {
    let theme = 'light';
    try {
      const saved = localStorage.getItem('brisas-theme');
      if (saved === 'light' || saved === 'dark') theme = saved;
    } catch (error) {
      console.warn('No se pudo leer la preferencia de tema guardada.', error);
    }
    document.documentElement.dataset.theme = theme;
  })();
</script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="/assets/css/style.css?v=<?= filemtime(__DIR__.'/assets/css/style.css') ?>" rel="stylesheet">
</head><body>

<nav class="navbar navbar-expand-lg mainnav">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="#inicio"><img src="/<?= e($JUNTA['logo']) ?>" alt="" width="36" height="36"><span><?= e($JUNTA['nombre']) ?></span></a>
    <div class="nav-tools">
      <button class="theme-toggle" id="themeToggle" type="button" aria-label="Cambiar a modo oscuro" title="Cambiar tema">
        <span class="theme-icon theme-icon-sun" aria-hidden="true">☀</span>
        <span class="theme-icon theme-icon-moon" aria-hidden="true">☾</span>
      </button>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nv" aria-controls="nv" aria-expanded="false" aria-label="Abrir menú">
        <span class="navbar-toggler-icon"></span>
      </button>
    </div>
    <div id="nv" class="collapse navbar-collapse justify-content-end">
      <ul class="navbar-nav">
        <?php foreach (['inicio'=>'Inicio','directiva'=>'Directiva','noticias'=>'Noticias','servicios'=>'Servicios','contacto'=>'Contacto'] as $k=>$v): ?>
        <li class="nav-item"><a class="nav-link" href="#<?= $k ?>"><?= $v ?></a></li>
        <?php endforeach; ?>
        <li class="nav-item nav-admin"><a class="nav-link" href="/admin/login.php">Administración <span aria-hidden="true">↗</span></a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- ===== INICIO ===== -->
<header id="inicio" class="hero">
  <div class="slides" aria-hidden="true">
    <?php foreach ($CARRUSEL as $i => $img): if (!is_file(__DIR__.'/inicio/'.$img)) continue; ?>
      <div class="slide<?= $i===0?' active':'' ?>" style="background-image:url('/inicio/<?= e(rawurlencode($img)) ?>')"></div>
    <?php endforeach; ?>
  </div>
  <div class="hero-over text-center">
    <span class="hero-eyebrow">Conchalí · Santiago</span>
    <img src="/<?= e($JUNTA['logo']) ?>" alt="Logo" class="hero-logo" width="150" height="150">
    <h1><?= e($JUNTA['nombre']) ?></h1>
    <p><?= e($JUNTA['lema']) ?></p>
    <a href="#noticias" class="btn btn-sun btn-lg">Ver últimas noticias</a>
  </div>
  <a class="hero-scroll" href="#directiva" aria-label="Ir a la sección Directiva"><span></span></a>
</header>

<!-- ===== DIRECTIVA ===== -->
<section id="directiva" class="py-5"><div class="container">
  <div class="section-heading reveal"><span>Quiénes somos</span><h2 class="sec-title">Nuestra directiva</h2><p>Vecinos comprometidos con una comunidad más segura, activa y unida.</p></div>
  <div class="row g-4 justify-content-center">
    <?php foreach ($DIRECTIVA as $d): $src = '/directiva/'.rawurlencode($d['foto']); ?>
    <div class="col-6 col-lg-3 reveal"><div class="person">
      <img src="<?= e($src) ?>" alt="<?= e($d['nombre']) ?>" loading="lazy" data-zoom="<?= e($src) ?>" role="button" tabindex="0">
      <div class="p-3"><small class="text-sun fw-semibold"><?= e($d['cargo']) ?></small>
        <h3 class="h5 mb-2"><?= e($d['nombre']) ?></h3><p class="small mb-0"><?= e($d['bio']) ?></p></div>
    </div></div>
    <?php endforeach; ?>
  </div>
</div></section>

<!-- ===== NOTICIAS ===== -->
<section id="noticias" class="py-5 band"><div class="container">
  <div class="section-heading reveal"><span>Desde el barrio</span><h2 class="sec-title">Noticias y avisos</h2><p>Novedades, actividades y comunicaciones importantes para toda la comunidad.</p></div>
  <?php if ($dbError): ?><p class="text-center text-muted">Las noticias no están disponibles por ahora.</p><?php else: ?>
  <div class="d-flex flex-wrap gap-2 justify-content-center mb-4" id="newsFilters">
    <button class="btn btn-chip active" data-filter="all">Todas</button>
    <?php foreach ($TIPOS_NOTICIA as $t): ?><button class="btn btn-chip" data-filter="<?= e($t) ?>"><?= e($t) ?></button><?php endforeach; ?>
  </div>
  <div class="row g-4" id="newsGrid">
    <?php foreach ($news as $n): $mp = '/'.$n['media_path']; ?>
    <div class="col-md-6 col-lg-4 news-item" data-type="<?= e($n['type']) ?>">
      <article class="card h-100 news-card" role="button" tabindex="0" data-news="<?= (int)$n['id'] ?>">
        <div class="thumb">
          <?php if ($n['media_type']==='video'): ?><video src="<?= e($mp) ?>#t=0.5" preload="metadata" muted playsinline></video><span class="play">▶</span>
          <?php else: ?><img src="<?= e($mp) ?>" alt="" loading="lazy"><?php endif; ?>
          <span class="badge-type"><?= e($n['type']) ?></span>
        </div>
        <div class="card-body"><small class="text-muted"><?= date('d/m/Y', strtotime($n['created_at'])) ?></small>
          <h3 class="h5 mt-1"><?= e($n['title']) ?></h3><p class="small mb-0"><?= e($n['seo']) ?></p></div>
      </article>
      <div class="d-none" id="nb-<?= (int)$n['id'] ?>"
           data-title="<?= e($n['title']) ?>" data-type="<?= e($n['type']) ?>" data-date="<?= date('d/m/Y', strtotime($n['created_at'])) ?>"
           data-media="<?= e($mp) ?>" data-mtype="<?= e($n['media_type']) ?>"><?= nl2br(e($n['body'])) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <p class="text-center text-muted d-none" id="newsEmpty">No hay noticias en esta categoría todavía.</p>
  <?php if (!$news): ?><p class="text-center text-muted">Aún no hay noticias publicadas.</p><?php endif; ?>
  <div class="text-center mt-4"><button class="btn btn-outline-sea d-none" id="newsMore">Ver más noticias</button></div>
  <?php endif; ?>
</div></section>

<!-- ===== SERVICIOS ===== -->
<section id="servicios" class="py-5"><div class="container">
  <div class="section-heading reveal"><span>Espacios y reservas</span><h2 class="sec-title">Servicios comunitarios</h2><p>Conoce los espacios disponibles y solicita tu reserva directamente.</p></div>
  <div class="row g-4">
    <?php foreach ($services as $s): $g = json_decode($s['gallery'] ?: '[]', true) ?: []; ?>
    <div class="col-lg-4"><div class="card h-100 svc">
      <?php if ($g): ?><div class="gallery"><?php foreach ($g as $p): ?>
        <img src="/<?= e($p) ?>" alt="<?= e($s['title']) ?>" loading="lazy" data-zoom="/<?= e($p) ?>" role="button"><?php endforeach; ?></div><?php endif; ?>
      <div class="card-body d-flex flex-column">
        <h3 class="h4"><?= e($s['title']) ?></h3>
        <?php if ($s['price_text']): ?><div class="price"><?= e($s['price_text']) ?></div><?php endif; ?>
        <p class="mt-2"><?= nl2br(e($s['description'])) ?></p>
        <div class="mt-auto d-grid gap-2">
          <?php if ($s['cond_image']): ?><button class="btn btn-outline-sea" data-zoom="/<?= e($s['cond_image']) ?>">Ver condiciones</button><?php endif; ?>
          <?php if ($s['whatsapp'] && $wa): ?><a class="btn btn-wa" target="_blank" rel="noopener"
            href="https://wa.me/<?= e($wa) ?>?text=<?= rawurlencode('Hola, quisiera agendar: '.$s['title']) ?>">Agendar por WhatsApp</a><?php endif; ?>
        </div>
      </div></div></div>
    <?php endforeach; ?>
  </div>
</div></section>

<!-- ===== CONTACTO ===== -->
<section id="contacto" class="py-5 band"><div class="container">
  <div class="section-heading reveal"><span>Escríbenos</span><h2 class="sec-title">Contacto</h2><p>Estamos disponibles para escuchar tus consultas y propuestas.</p></div>
  <div class="row g-4">
    <div class="col-lg-4">
      <p><strong>Dirección</strong><br><?= e($JUNTA['direccion']) ?></p>
      <p><strong>Teléfono</strong><br><?= e($JUNTA['telefono']) ?></p>
      <p><strong>Correo</strong><br><?= e($JUNTA['email']) ?></p>
    </div>
    <div class="col-lg-8">
      <form id="contactForm" class="row g-3" novalidate>
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
        <div class="col-md-6"><label class="form-label" for="c_n">Nombre</label><input class="form-control" id="c_n" name="nombre" required maxlength="60" autocomplete="given-name"><div class="invalid-feedback">Ingresa tu nombre.</div></div>
        <div class="col-md-6"><label class="form-label" for="c_a">Apellido</label><input class="form-control" id="c_a" name="apellido" required maxlength="60" autocomplete="family-name"><div class="invalid-feedback">Ingresa tu apellido.</div></div>
        <div class="col-md-6"><label class="form-label" for="c_e">Correo</label><input type="email" class="form-control" id="c_e" name="correo" required maxlength="120" autocomplete="email"><div class="invalid-feedback">Ingresa un correo válido.</div></div>
        <div class="col-md-6"><label class="form-label" for="c_t">Teléfono de contacto</label><input type="tel" class="form-control" id="c_t" name="telefono" required pattern="[0-9+ ]{8,15}" maxlength="15" autocomplete="tel"><div class="invalid-feedback">Ingresa un teléfono válido (solo números).</div></div>
        <div class="col-12"><label class="form-label" for="c_m">Mensaje</label><textarea class="form-control" id="c_m" name="mensaje" rows="5" required minlength="10" maxlength="2000"></textarea><div class="invalid-feedback">Escribe tu mensaje (mínimo 10 caracteres).</div></div>
        <div class="col-md-6"><label class="form-label" for="c_c" id="capQ">Verificación…</label>
          <div class="input-group"><input class="form-control" id="c_c" name="captcha" required inputmode="numeric" autocomplete="off"><button type="button" class="btn btn-outline-secondary" id="capRefresh" aria-label="Nueva pregunta">↻</button></div>
          <div class="invalid-feedback">Responde la verificación.</div></div>
        <div class="col-12"><button class="btn btn-sea btn-lg" id="sendBtn">Enviar mensaje</button></div>
      </form>
    </div>
  </div>
</div></section>

<footer class="site-footer foot">
  <div class="container py-5">
    <div class="row g-4">
      <div class="col-lg-4">
        <a class="footer-brand" href="#inicio">
          <img src="/<?= e($JUNTA['logo']) ?>" alt="" width="56" height="56" loading="lazy">
          <span><?= e($JUNTA['nombre']) ?></span>
        </a>
        <p class="footer-tagline mt-3 mb-0"><?= e($JUNTA['lema']) ?></p>
      </div>
      <div class="col-sm-6 col-lg-4">
        <h2 class="footer-heading">Datos de la junta</h2>
        <address class="mb-0"><?= e($JUNTA['direccion']) ?></address>
        <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $JUNTA['telefono'])) ?>"><?= e($JUNTA['telefono']) ?></a>
        <a href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener">WhatsApp: <?= e($JUNTA['telefono']) ?></a>
        <a href="mailto:<?= e($JUNTA['email']) ?>"><?= e($JUNTA['email']) ?></a>
      </div>
      <div class="col-sm-6 col-lg-4">
        <h2 class="footer-heading">Síguenos</h2>
        <div class="footer-socials">
          <?php foreach (['Facebook'=>'facebook','Instagram'=>'instagram'] as $label=>$key): ?>
            <?php if (!empty($JUNTA['redes'][$key])): ?>
              <a href="<?= e($JUNTA['redes'][$key]) ?>" target="_blank" rel="noopener noreferrer"><?= e($label) ?> <span aria-hidden="true">↗</span></a>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
        <nav class="footer-links" aria-label="Enlaces del sitio">
          <a href="#directiva">Directiva</a>
          <a href="#noticias">Noticias</a>
          <a href="#servicios">Servicios</a>
          <a href="#contacto">Contacto</a>
        </nav>
      </div>
    </div>
    <div class="footer-bottom mt-4 pt-3">
      <span>&copy; <?= date('Y') ?> <?= e($JUNTA['nombre']) ?></span>
      <span><?= e($JUNTA['lema']) ?></span>
    </div>
  </div>
</footer>

<!-- Modales -->
<div class="modal fade" id="zoomModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content bg-transparent border-0">
  <button class="btn-close btn-close-white ms-auto mb-2" data-bs-dismiss="modal" aria-label="Cerrar"></button><img id="zoomImg" class="img-fluid rounded" alt=""></div></div></div>
<div class="modal fade" id="newsModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg"><div class="modal-content">
  <div class="modal-header"><div><span class="badge text-bg-warning" id="nmType"></span> <small class="text-muted ms-1" id="nmDate"></small><h3 class="h4 mt-1 mb-0" id="nmTitle"></h3></div><button class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
  <div class="modal-body"><div id="nmMedia" class="mb-3"></div><div id="nmBody"></div></div></div></div></div>
<div class="modal fade" id="msgModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h3 class="h5 mb-0" id="msgTitle"></h3><button class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
  <div class="modal-body" id="msgBody"></div><div class="modal-footer"><button class="btn btn-sea" data-bs-dismiss="modal">Entendido</button></div></div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/main.js?v=<?= filemtime(__DIR__.'/assets/js/main.js') ?>"></script>
</body></html>
