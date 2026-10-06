(function () {
  var root = document.documentElement;
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  try { if (localStorage.getItem('theme') === 'dark') root.classList.add('dark'); } catch (e) {}

  // Bouton clair / sombre
  var b = document.createElement('button');
  b.className = 'theme-fab';
  b.setAttribute('aria-label', 'Changer de thème');
  function icon() { b.textContent = root.classList.contains('dark') ? '☀️' : '🌙'; }
  b.onclick = function () {
    root.classList.toggle('dark');
    try { localStorage.setItem('theme', root.classList.contains('dark') ? 'dark' : 'light'); } catch (e) {}
    icon();
  };
  icon();
  document.body.appendChild(b);

  // Chiffres qui comptent jusqu'à leur valeur
  function count(el) {
    if (el.dataset.cu) return;
    var n = el.firstChild;
    if (!n || n.nodeType !== 3) return;
    var m = /^(\d+(?:[.,]\d+)?)(.*)$/.exec(n.nodeValue.trim());
    if (!m) return;
    el.dataset.cu = 1;
    if (reduce) return;
    var end = parseFloat(m[1].replace(',', '.')), dec = /[.,]/.test(m[1]), suf = m[2], t0 = performance.now();
    (function step(t) {
      var p = Math.min(1, (t - t0) / 900), v = end * (1 - Math.pow(1 - p, 3));
      n.nodeValue = (dec ? v.toFixed(1) : Math.round(v)) + suf;
      if (p < 1) requestAnimationFrame(step); else n.nodeValue = m[1] + suf;
    })(t0);
  }

  // Squelette de chargement + compteurs, à chaque changement de page
  var queued = false;
  function scan() {
    queued = false;
    document.querySelectorAll('.text-3xl.font-bold').forEach(count);
    document.querySelectorAll('p').forEach(function (p) {
      var loading = p.textContent.trim() === 'Chargement...';
      if (loading) p.classList.add('skel'); else if (p.classList.contains('skel')) p.classList.remove('skel');
    });
  }
  new MutationObserver(function () {
    if (!queued) { queued = true; requestAnimationFrame(scan); }
  }).observe(document.body, { childList: true, subtree: true, characterData: true });
  scan();
})();
