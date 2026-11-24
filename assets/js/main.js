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
if (sl.length > 1) {
  let i = 0;
  setInterval(() => {
    sl[i].classList.remove('active');
    i = (i + 1) % sl.length;
    sl[i].classList.add('active');
  }, 6000);
}

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

/* ---- Servicios: galería y detalles en modal ---- */
const serviceModalElement = $('#serviceModal');
if (serviceModalElement) {
  const serviceModal = new bootstrap.Modal(serviceModalElement);
  const serviceGallery = $('#serviceModalGallery');
  const conditionButton = $('#serviceModalConditions');
  const conditionPanel = $('#serviceConditionPanel');
  const conditionImage = $('#serviceConditionImage');
  const conditionUnavailable = $('#serviceConditionUnavailable');
  const certificateImage = $('#serviceCertificateImage');
  const certificateUnavailable = $('#serviceCertificateUnavailable');
  const whatsappButton = $('#serviceModalWhatsapp');
  let activeServiceTrigger = null;
  $$('[data-service-trigger]').forEach(trigger => trigger.addEventListener('click', () => {
    activeServiceTrigger = trigger;
    $('#serviceModalTitle').textContent = trigger.dataset.title;
    $('#serviceModalDescription').textContent = trigger.dataset.description;
    const price = $('#serviceModalPrice');
    price.textContent = trigger.dataset.price;
    price.classList.toggle('d-none', !trigger.dataset.price);

    serviceGallery.replaceChildren();
    const conditionOnly = trigger.dataset.conditionOnly === 'true';
    let photos;
    try {
      photos = JSON.parse(trigger.dataset.gallery || '[]');
      if (!Array.isArray(photos)) throw new Error('La galería del servicio no es válida.');
    } catch (error) {
      console.error('No se pudo leer la galería del servicio.', error);
      photos = [];
    }
    photos.filter(path => typeof path === 'string' && path !== '').forEach((path, index) => {
      const image = document.createElement('img');
      image.src = path.startsWith('/') ? path : `/${path}`;
      image.alt = `${trigger.dataset.title}, foto ${index + 1}`;
      image.loading = 'lazy';
      serviceGallery.append(image);
    });
    if (!conditionOnly && !serviceGallery.childElementCount) {
      const empty = document.createElement('p');
      empty.className = 'service-gallery-empty';
      empty.textContent = 'Pronto podrás ver aquí las fotos de este espacio.';
      serviceGallery.append(empty);
    }

    const conditionPath = trigger.dataset.condition || '';
    conditionButton.textContent = trigger.dataset.conditionLabel || 'Condiciones de uso';
    conditionButton.classList.toggle('d-none', !conditionPath || conditionOnly);
    conditionPanel.classList.add('d-none');
    conditionImage.classList.add('d-none');
    conditionImage.removeAttribute('src');
    conditionUnavailable.classList.add('d-none');
    certificateImage.classList.add('d-none');
    certificateImage.removeAttribute('src');
    certificateUnavailable.classList.add('d-none');
    if (conditionOnly) {
      if (trigger.dataset.conditionReady === 'true' && conditionPath) {
        certificateImage.src = conditionPath.startsWith('/') ? conditionPath : `/${conditionPath}`;
        certificateImage.alt = trigger.dataset.conditionLabel || trigger.dataset.title;
        certificateImage.classList.remove('d-none');
      } else {
        certificateUnavailable.textContent = `La ficha informativa aún no está disponible. Súbela en: /${conditionPath}`;
        certificateUnavailable.classList.remove('d-none');
      }
    }

    const whatsappUrl = trigger.dataset.whatsapp || '';
    whatsappButton.href = whatsappUrl || '#';
    whatsappButton.textContent = trigger.dataset.whatsappLabel || 'Consultar por WhatsApp';
    whatsappButton.classList.toggle('d-none', !whatsappUrl);
    serviceModal.show();
  }));
  conditionButton.addEventListener('click', () => {
    conditionPanel.classList.remove('d-none');
    if (activeServiceTrigger?.dataset.conditionReady === 'true') {
      const path = activeServiceTrigger.dataset.condition || '';
      conditionImage.src = path.startsWith('/') ? path : `/${path}`;
      conditionImage.alt = conditionButton.textContent;
      conditionImage.classList.remove('d-none');
    } else {
      const path = activeServiceTrigger?.dataset.condition || '';
      conditionUnavailable.textContent = `La imagen aún no está disponible. Súbela en: /${path}`;
      conditionUnavailable.classList.remove('d-none');
    }
  });
  $('#serviceConditionClose').addEventListener('click', () => conditionPanel.classList.add('d-none'));
  serviceModalElement.addEventListener('hidden.bs.modal', () => {
    serviceGallery.replaceChildren();
    conditionImage.removeAttribute('src');
    certificateImage.removeAttribute('src');
    conditionPanel.classList.add('d-none');
  });
}

/* ---- Contacto: validación, captcha y envío ---- */
const f = $('#contactForm'), mm = new bootstrap.Modal('#msgModal');
const contactModalEl = $('#contactModal'), contactModal = bootstrap.Modal.getOrCreateInstance(contactModalEl);
const recaptchaWidget = $('#recaptchaWidget'), captchaError = $('#captchaError');
let recaptchaWidgetId = null;
const msg = (t, b) => {
  const showMessage = () => { $('#msgTitle').textContent = t; $('#msgBody').textContent = b; mm.show(); };
  if (contactModalEl.classList.contains('show')) {
    contactModalEl.addEventListener('hidden.bs.modal', showMessage, { once:true });
    contactModal.hide();
  } else showMessage();
};
window.brisasRecaptchaVerified = () => captchaError?.classList.add('d-none');
function renderRecaptcha() {
  if (!recaptchaWidget || !contactModalEl.classList.contains('show') || recaptchaWidgetId !== null || !window.grecaptcha?.render) return;
  recaptchaWidgetId = window.grecaptcha.render(recaptchaWidget, {
    sitekey: recaptchaWidget.dataset.sitekey,
    callback: window.brisasRecaptchaVerified,
    'expired-callback': () => captchaError?.classList.remove('d-none')
  });
}
window.addEventListener('brisas-recaptcha-ready', renderRecaptcha);
if (window.brisasRecaptchaReady) renderRecaptcha();
contactModalEl.addEventListener('shown.bs.modal', renderRecaptcha);
function resetCaptcha() {
  if (recaptchaWidget && recaptchaWidgetId !== null) window.grecaptcha?.reset?.(recaptchaWidgetId);
  captchaError?.classList.add('d-none');
}
f.addEventListener('submit', async e => {
  e.preventDefault(); f.classList.add('was-validated');
  if (!recaptchaWidget || recaptchaWidgetId === null || !window.grecaptcha?.getResponse?.(recaptchaWidgetId)) {
    captchaError?.classList.remove('d-none');
    return;
  }
  if (!f.checkValidity()) return;
  const btn = $('#sendBtn'); btn.disabled = true; btn.textContent = 'Enviando…';
  try {
    const r = await fetch('/contact.php', { method: 'POST', body: new FormData(f) }), j = await r.json();
    if (j.ok) { msg('Mensaje enviado', j.msg); f.reset(); f.classList.remove('was-validated'); }
    else msg('No se pudo enviar', j.msg || 'Inténtalo nuevamente.');
  } catch (_) { msg('Sin conexión', 'No se pudo enviar el mensaje. Revisa tu conexión.'); }
  btn.disabled = false; btn.textContent = 'Enviar mensaje'; resetCaptcha();
});
})();
