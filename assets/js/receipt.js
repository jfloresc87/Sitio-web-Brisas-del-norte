/* Formulario de comprobantes: campos según concepto, total en vivo y botón activo solo si todo es válido */
(() => {
const f = document.getElementById('recForm'), $ = id => document.getElementById(id);
const P = { cancha: +f.dataset.cancha, sede: +f.dataset.sede, hini: +f.dataset.hini };
const clp = n => '$' + Math.round(n).toLocaleString('es-CL');
const key = { 'Arriendo de Cancha': 'cancha', 'Arriendo de Sede': 'sede', 'Otros Pagos': 'otros' };
const hs = $('hs'), hq = $('hq');
for (let h = P.hini; h <= 23; h++) hs.add(new Option(String(h).padStart(2, '0') + ':00', h));
const fillQty = () => { const max = 24 - +hs.value; hq.innerHTML = ''; for (let i = 1; i <= max; i++) hq.add(new Option(i + (i === 1 ? ' hora' : ' horas'), i)); };
fillQty(); hs.addEventListener('change', () => { fillQty(); update(); });

function rutOk(v) { v = v.toUpperCase().replace(/[^0-9K]/g, ''); if (v.length < 8) return false;
  const d = v.slice(-1), n = v.slice(0, -1); let s = 0, m = 2; for (let i = n.length - 1; i >= 0; i--) { s += +n[i] * m; m = m === 7 ? 2 : m + 1; }
  const x = 11 - s % 11; return (x === 11 ? '0' : x === 10 ? 'K' : String(x)) === d; }

function update() {
  const k = key[f.concept.value] || '', special = f.special.checked;
  f.querySelectorAll('[data-for]').forEach(box => {
    let on = box.dataset.for.split(' ').includes(k);
    if (box.id === 'amountBox') on = k === 'otros' || (k !== '' && special);        // valor manual
    box.classList.toggle('d-none', !on);
    box.querySelectorAll('input,select').forEach(el => { el.disabled = !on; });
  });
  f.querySelectorAll('[data-for]').forEach(box => box.querySelectorAll('input:not([type=checkbox]),select').forEach(el => {
    if (el.disabled) { el.required = false; return; }
    el.required = !(k === 'otros' && el.name === 'event_date');                    // fecha opcional en "Otros pagos"
  }));
  $('dateLbl').textContent = k === 'otros' ? 'Fecha (opcional)' : 'Fecha del evento';
  let total = 0, hint = 'Selecciona un concepto.';
  if (k === 'cancha') { total = special ? +f.amount.value.replace(/\D/g, '') : P.cancha * +hq.value; hint = special ? 'Valor especial ingresado manualmente.' : `${hq.value} hora(s) × ${clp(P.cancha)} · solo horas completas desde las ${P.hini}:00`; }
  if (k === 'sede') { total = special ? +f.amount.value.replace(/\D/g, '') : P.sede; hint = special ? 'Valor especial ingresado manualmente.' : 'El cliente deposita $120.000; el valor final del arriendo es ' + clp(P.sede) + '.'; }
  if (k === 'otros') { total = +f.amount.value.replace(/\D/g, ''); hint = 'Ingresa concepto y valor manualmente.'; }
  $('total').textContent = clp(total); $('hint').textContent = hint;
  const rutBad = k === 'sede' && f.rut.value && !rutOk(f.rut.value); $('rutErr').classList.toggle('d-none', !rutBad);
  const amountOk = !(special || k === 'otros') || total > 0;
  $('genBtn').disabled = !(k && f.checkValidity() && !rutBad && amountOk && total > 0);
}
f.addEventListener('input', update); f.addEventListener('change', update); update();

f.addEventListener('submit', async e => {
  e.preventDefault(); update(); if ($('genBtn').disabled) return;
  const w = window.open('', '_blank');                                               // se abre antes para evitar bloqueo de popups
  $('genBtn').disabled = true; $('genMsg').textContent = 'Generando…';
  try {
    const r = await fetch('comprobante_crear.php', { method: 'POST', body: new FormData(f) }), j = await r.json();
    if (j.ok) { if (w) w.location = 'pdf.php?id=' + j.id; $('genMsg').textContent = 'Comprobante N° ' + String(j.id).padStart(6, '0') + ' generado.'; f.reset(); fillQty(); }
    else { if (w) w.close(); $('genMsg').textContent = j.msg || 'No se pudo generar.'; }
  } catch (_) { if (w) w.close(); $('genMsg').textContent = 'Error de conexión.'; }
  update();
});
})();
