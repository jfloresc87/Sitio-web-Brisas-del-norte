/* Muestra en vivo qué requisitos cumple la clave y habilita el botón */
(() => { const p = document.getElementById('np'); if (!p) return;
  const q = document.getElementById('np2'), b = document.querySelector('button[type=submit]');
  const T = { len: v => v.length >= 8, up: v => /[A-Z]/.test(v), lo: v => /[a-z]/.test(v), nu: v => /\d/.test(v), sp: v => /[^A-Za-z0-9]/.test(v) };
  const run = () => { let all = true;
    document.querySelectorAll('#rules li').forEach(li => { const ok = T[li.dataset.r](p.value); li.style.color = ok ? '#198754' : ''; li.style.fontWeight = ok ? '600' : ''; all = all && ok; });
    const same = p.value === q.value && q.value !== ''; q.classList.toggle('is-invalid', q.value !== '' && !same);
    if (b) b.disabled = !(all && same); };
  p.addEventListener('input', run); q.addEventListener('input', run); run(); })();
