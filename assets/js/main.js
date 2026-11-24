(() => {
const $ = (s, r = document) => r.querySelector(s), $$ = (s, r = document) => [...r.querySelectorAll(s)];

/* ---- Tema claro/oscuro persistente ---- */
const themeToggle = $('#themeToggle');
function syncThemeControl() {
  const dark = document.documentElement.dataset.theme === 'dark';
  themeToggle?.setAttribute('aria-label', dark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
  themeToggle?.setAttribute('title', dark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
  themeToggle?.setAttribute('aria-pressed', String(dark));
}
syncThemeControl();
themeToggle?.addEventListener('click', () => {
  const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
  document.documentElement.dataset.theme = next;
  syncThemeControl();
  try {
    localStorage.setItem('brisas-theme', next);
  } catch (error) {
    console.warn('No se pudo guardar la preferencia de tema.', error);
  }
});

/* ---- Navegación y revelado al hacer scroll ---- */
const navbar = $('.mainnav');
const updateNav = () => navbar?.classList.toggle('scrolled', window.scrollY > 24);
updateNav(); window.addEventListener('scroll', updateNav, { passive:true });
const revealObserver = 'IntersectionObserver' in window ? new IntersectionObserver(entries => {
  entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.add('visible'); revealObserver.unobserve(entry.target); } });
}, { threshold:.12 }) : null;
$$('.reveal').forEach(el => revealObserver ? revealObserver.observe(el) : el.classList.add('visible'));

const sections = $$('section[id], header[id]');
const navLinks = $$('.mainnav .nav-link');
const navCollapse = $('#nv');
navLinks.forEach(link => {
  if (!link.getAttribute('href')?.startsWith('#')) return;
  link.addEventListener('click', () => {
    if (navCollapse?.classList.contains('show')) bootstrap.Collapse.getOrCreateInstance(navCollapse).hide();
  });
});
if ('IntersectionObserver' in window) {
  const navObserver = new IntersectionObserver(entries => entries.forEach(entry => {
    if (!entry.isIntersecting) return;
    navLinks.forEach(link => link.classList.toggle('active', link.getAttribute('href') === '#' + entry.target.id));
  }), { rootMargin:'-35% 0px -55%', threshold:0 });
  sections.forEach(section => navObserver.observe(section));
}

/* ---- Carrusel: cambia cada 6 s con fundido lento (CSS) ---- */
const sl = $$('.slide');
if (sl.length > 1) { let i = 0; setInterval(() => { sl[i].classList.remove('active'); i = (i + 1) % sl.length; sl[i].classList.add('active'); }, 6000); }

/* ---- Zoom de imágenes (directiva, galerías, condiciones) ---- */
const zoom = new bootstrap.Modal('#zoomModal');
function openZoom(el) { $('#zoomImg').src = el.dataset.zoom; zoom.show(); }
document.addEventListener('click', e => { const z = e.target.closest('[data-zoom]'); if (z) openZoom(z); });
document.addEventListener('keydown', e => { if (e.key === 'Enter') { const z = e.target.closest?.('[data-zoom]'); if (z) openZoom(z); } });
$('#zoomModal').addEventListener('hidden.bs.modal', () => $('#zoomImg').removeAttribute('src'));

/* ---- Noticias: filtro, "ver más" y modal ---- */
const items = $$('.news-item'), PAGE = 6; let filter = 'all', shown = PAGE;
function renderNews() {
  const vis = items.filter(it => filter === 'all' || it.dataset.type === filter);
  items.forEach(it => it.classList.add('d-none'));
  vis.slice(0, shown).forEach(it => it.classList.remove('d-none'));
  $('#newsMore')?.classList.toggle('d-none', vis.length <= shown);
  $('#newsEmpty')?.classList.toggle('d-none', vis.length > 0 || !items.length);
}
$$('#newsFilters [data-filter]').forEach(b => b.addEventListener('click', () => {
  $$('#newsFilters .btn').forEach(x => x.classList.remove('active')); b.classList.add('active');
  filter = b.dataset.filter; shown = PAGE; renderNews();
}));
$('#newsMore')?.addEventListener('click', () => { shown += PAGE; renderNews(); });
renderNews();
const nm = new bootstrap.Modal('#newsModal');
function openNews(id) {
  const d = $('#nb-' + id); if (!d) return;
  $('#nmTitle').textContent = d.dataset.title; $('#nmType').textContent = d.dataset.type; $('#nmDate').textContent = d.dataset.date;
  $('#nmBody').innerHTML = d.innerHTML;
  $('#nmMedia').innerHTML = d.dataset.mtype === 'video'
    ? `<video src="${d.dataset.media}" controls playsinline preload="metadata"></video>` : `<img src="${d.dataset.media}" alt="">`;
  nm.show();
}
$$('[data-news]').forEach(c => { c.addEventListener('click', () => openNews(c.dataset.news));
  c.addEventListener('keydown', e => { if (e.key === 'Enter') openNews(c.dataset.news); }); });
$('#newsModal').addEventListener('hidden.bs.modal', () => $('#nmMedia').innerHTML = '');

/* ---- Contacto: validación, captcha y envío ---- */
const f = $('#contactForm'), mm = new bootstrap.Modal('#msgModal');
const msg = (t, b) => { $('#msgTitle').textContent = t; $('#msgBody').textContent = b; mm.show(); };
async function loadCaptcha() {
  try { const r = await fetch('/captcha.php', { cache: 'no-store' }); $('#capQ').textContent = (await r.json()).q; $('#c_c').value = ''; } catch (_) { $('#capQ').textContent = 'No se pudo cargar la verificación'; }
}
loadCaptcha(); $('#capRefresh').addEventListener('click', loadCaptcha);
f.addEventListener('submit', async e => {
  e.preventDefault(); f.classList.add('was-validated');
  if (!f.checkValidity()) return;
  const btn = $('#sendBtn'); btn.disabled = true; btn.textContent = 'Enviando…';
  try {
    const r = await fetch('/contact.php', { method: 'POST', body: new FormData(f) }), j = await r.json();
    if (j.ok) { msg('Mensaje enviado', j.msg); f.reset(); f.classList.remove('was-validated'); }
    else msg('No se pudo enviar', j.msg || 'Inténtalo nuevamente.');
  } catch (_) { msg('Sin conexión', 'No se pudo enviar el mensaje. Revisa tu conexión.'); }
  btn.disabled = false; btn.textContent = 'Enviar mensaje'; loadCaptcha();
});
})();
