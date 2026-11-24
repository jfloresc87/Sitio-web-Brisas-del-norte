<?php
/* Plantilla mínima para páginas de acceso (login, recuperar, cambiar clave) */
function auth_head(string $title): void { global $JUNTA; ?><!doctype html><html lang="es"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title><?= e($title) ?></title>
<script>(()=>{let t='light';try{const s=localStorage.getItem('brisas-theme');if(s==='light'||s==='dark')t=s}catch(e){console.warn('No se pudo leer la preferencia de tema guardada.',e)}document.documentElement.dataset.theme=t})();</script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="/assets/css/style.css?v=<?= filemtime(__DIR__.'/../assets/css/style.css') ?>" rel="stylesheet">
<style>body{background:var(--surface-alt);min-height:100vh;display:grid;place-items:center;padding:1rem}.box{max-width:420px;width:100%;background:var(--surface);border:1px solid var(--border);border-radius:1.25rem;padding:2rem;box-shadow:var(--shadow-lg)}</style>
</head><body><div class="box"><div class="text-center mb-3"><img src="/<?= e($JUNTA['logo']) ?>" width="80" height="80" alt="">
<h1 class="h5 mt-2 text-sea" style="color:var(--sea)"><?= e($title) ?></h1></div><?php }
function auth_foot(): void { echo '</div><script src="/assets/js/passcheck.js"></script></body></html>'; }
function alert_box(string $m, string $t = 'danger'): void { if ($m) echo '<div class="alert alert-'.$t.'" role="alert">'.e($m).'</div>'; }
function pass_fields(): void { ?>
<div class="mb-3"><label class="form-label" for="np">Nueva clave</label><input type="password" class="form-control" id="np" name="np" required minlength="8" autocomplete="new-password">
<ul class="small text-muted mt-2 mb-0 ps-3" id="rules"><li data-r="len">Mínimo 8 caracteres</li><li data-r="up">1 mayúscula</li><li data-r="lo">1 minúscula</li><li data-r="nu">1 número</li><li data-r="sp">1 carácter especial</li></ul></div>
<div class="mb-3"><label class="form-label" for="np2">Repite la nueva clave</label><input type="password" class="form-control" id="np2" name="np2" required autocomplete="new-password"></div>
<?php }
