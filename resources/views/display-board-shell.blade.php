<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Display Board — FinBoard</title>
<link rel="stylesheet" href="/vendor/tabler-icons/tabler-icons.min.css"/>
<link rel="stylesheet" href="/template/assets/vendor/libs/apex-charts/apex-charts.css"/>
<script src="/template/assets/vendor/libs/apex-charts/apexcharts.js"></script>
<link rel="stylesheet" href="/vendor/leaflet/leaflet.css"/>
<script src="/vendor/leaflet/leaflet.js"></script>
<style>
:root{
  --bg:#f0f2f8;--s1:#ffffff;--s2:#f8f9fc;
  --border:#dde0ef;--border2:#c4c9df;
  --text:#2c3050;--muted:#6c7293;--dim:#9fa4bc;
  --rp:#e63946;--rs:#ff7b7b;
  --blue:#696cff;--cyan:#0095b6;--green:#3a9a0a;--red:#e63946;
  --yellow:#c98000;--orange:#d97b00;
  --gold:#c8a24b;--purple:#8592a3;--teal:#00b4d8;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{width:100vw;height:100vh;overflow:hidden;background:var(--bg);color:var(--text);font-family:'Segoe UI',system-ui,sans-serif;font-size:14px}
#topbar{position:fixed;top:0;left:0;right:0;z-index:200;height:52px;
  background:linear-gradient(90deg,#ffffffee,#f0f4ffee);
  border-bottom:1px solid var(--border);backdrop-filter:blur(12px);
  display:flex;align-items:center;padding:0 14px;gap:10px}
.logo{font-size:1.1rem;font-weight:800;letter-spacing:.03em;
  background:linear-gradient(135deg,var(--rp),var(--rs));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.pb{font-size:.73rem;font-weight:600;color:var(--rp);
  background:rgba(230,57,70,.1);border:1px solid rgba(230,57,70,.3);
  border-radius:20px;padding:3px 12px}
.tr{display:flex;align-items:center;gap:16px;margin-left:auto}
#clock{font-size:1.05rem;font-weight:700;font-variant-numeric:tabular-nums}
#dlbl{font-size:.73rem;color:var(--muted)}
#pw{position:fixed;top:52px;left:0;right:0;z-index:200;height:3px;background:var(--border)}
#pf{height:100%;width:0%;background:linear-gradient(90deg,var(--rp),var(--rs))}
#dots{display:flex;gap:6px;align-items:center;margin:0 8px}
.dot{width:8px;height:8px;border-radius:50%;background:var(--border2);cursor:pointer;transition:all .3s}
.dot.active{background:var(--rp);transform:scale(1.4);box-shadow:0 0 8px var(--rp)}
#btn-play{display:flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;border:1.5px solid var(--border2);background:rgba(255,255,255,.7);cursor:pointer;transition:all .2s;color:var(--rp);font-size:.95rem}
#btn-play:hover{background:rgba(230,57,70,.1);border-color:var(--rp)}
#sw{position:fixed;top:55px;left:0;right:0;bottom:0;overflow:hidden}
.slide{position:absolute;inset:0;opacity:0;pointer-events:none;transform:translateX(30px);transition:opacity .55s ease,transform .55s ease;padding:7px 12px;display:flex;flex-direction:column;gap:6px;overflow:hidden}
.slide.active{opacity:1;pointer-events:auto;transform:translateX(0)}
.slide.out{opacity:0;transform:translateX(-30px)}
.stitle{font-size:.75rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.1em;display:flex;align-items:center;gap:8px;flex-shrink:0}
.stitle i{font-size:1rem;opacity:.8}
.gc{background:linear-gradient(135deg,rgba(255,255,255,.98),rgba(248,249,252,.95));border:1px solid var(--border);border-radius:14px;backdrop-filter:blur(8px);overflow:hidden;position:relative}
.gc::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--ct,linear-gradient(90deg,var(--rp),var(--rs)));border-radius:14px 14px 0 0}
.kpi-card{padding:8px 12px;display:flex;flex-direction:column;gap:3px}
.kpi-grid{display:grid;gap:8px;flex:1;align-content:start}
.g3{grid-template-columns:repeat(3,1fr)}
.g2{grid-template-columns:repeat(2,1fr)}
.klbl{font-size:.67rem;color:var(--muted);text-transform:uppercase;letter-spacing:.07em;font-weight:600}
.kval{font-size:1.7rem;font-weight:800;line-height:1.1;color:var(--text)}
.kval.xl{font-size:2.1rem}
.kval.sm{font-size:1.1rem}
.ksub{font-size:.68rem;color:var(--muted)}
.kb{display:inline-flex;align-items:center;gap:3px;font-size:.68rem;font-weight:700;padding:2px 9px;border-radius:20px;width:fit-content}
.kb.up{background:rgba(58,154,10,.12);color:var(--green)}
.kb.dn{background:rgba(230,57,70,.12);color:var(--red)}
.kb.warn{background:rgba(201,128,0,.12);color:var(--yellow)}
.kb.info{background:rgba(230,57,70,.1);color:var(--rp)}
.kb.cy{background:rgba(0,149,182,.12);color:var(--cyan)}
.chart-row{display:grid;gap:6px;flex:1;min-height:0}
.cr21{grid-template-columns:2fr 1fr}
.cr12{grid-template-columns:1fr 2fr}
.cc{padding:6px 10px 12px;display:flex;flex-direction:column;min-height:0}
.cct{font-size:.68rem;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px;flex-shrink:0}
.ca{flex:1;min-height:0}
.nt{width:100%;border-collapse:collapse;font-size:.79rem}
.nt th{color:var(--dim);text-align:left;font-weight:700;padding:3px 8px;border-bottom:1px solid var(--border);text-transform:uppercase;font-size:.62rem;letter-spacing:.06em}
.nt td{padding:4px 8px;border-bottom:1px solid rgba(200,205,228,.6)}
.nt tr:last-child td{border-bottom:none}
.nb{display:inline-block;padding:1px 8px;border-radius:20px;font-size:.63rem;font-weight:800}
.nb1{background:rgba(58,154,10,.15);color:var(--green)}
.nb2{background:rgba(0,149,182,.15);color:var(--cyan)}
.nb3{background:rgba(201,128,0,.15);color:var(--yellow)}
.nb4{background:rgba(217,123,0,.15);color:var(--orange)}
.nb5{background:rgba(230,57,70,.15);color:var(--red)}
.top-dpk-wrap{overflow:hidden;flex:1;min-height:0;position:relative}.top-dpk-inner{display:flex;flex-direction:column;gap:0;animation:dpk-scroll 20s linear infinite}.top-dpk-inner:hover{animation-play-state:paused}@keyframes dpk-scroll{0%{transform:translateY(0)}100%{transform:translateY(-50%)}}.top-dpk-row{display:grid;grid-template-columns:1.2rem 1fr auto auto auto;gap:4px;align-items:center;padding:4px 6px;border-bottom:1px solid rgba(200,205,228,.5);font-size:.65rem}.top-dpk-row:last-child{border-bottom:none}
.fh-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:5px;flex:0 0 auto}
.fh-card{padding:6px 10px;display:flex;align-items:center;gap:8px}
.fh-icon{width:34px;height:34px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1rem}
.fh-body{flex:1;min-width:0}
.fh-lbl{font-size:.63rem;color:var(--muted);text-transform:uppercase;letter-spacing:.07em;font-weight:700;margin-bottom:1px}
.fh-val{font-size:.95rem;font-weight:800;color:var(--text);line-height:1}
.fh-chg{font-size:.63rem;margin-top:1px}
.bar-row{display:flex;align-items:center;gap:8px;padding:4px 0}
.bar-l{font-size:.7rem;color:var(--muted);min-width:62px}
.bar-o{flex:1;height:7px;background:var(--border);border-radius:4px;overflow:hidden}
.bar-i{height:100%;border-radius:4px;background:var(--rp)}
.bar-v{font-size:.7rem;color:var(--text);min-width:68px;text-align:right;font-weight:600}
.nscr{overflow-y:auto;flex:1;scrollbar-width:thin;scrollbar-color:var(--border) transparent}
/* ── Skeleton shimmer ────────────────────────────────────────────── */
@keyframes db-shimmer{0%{background-position:-200% 0}100%{background-position:200% 0}}
.db-shimmer{
  background:linear-gradient(90deg,#e4e7f2 25%,#f0f3fa 50%,#e4e7f2 75%);
  background-size:200% 100%;
  animation:db-shimmer 1.6s infinite linear;
  border-radius:8px;
}
/* Loading badge */
#db-badge{
  position:fixed;top:60px;left:50%;transform:translateX(-50%);
  z-index:9999;
  display:flex;align-items:center;gap:8px;
  background:rgba(255,255,255,.95);backdrop-filter:blur(12px);
  border:1px solid var(--border);border-radius:30px;
  padding:6px 18px;font-size:.78rem;font-weight:600;color:var(--text);
  box-shadow:0 2px 16px rgba(44,48,80,.12);
  transition:opacity .4s,transform .4s;
}
#db-badge.db-done{color:var(--green);border-color:rgba(58,154,10,.3);background:rgba(240,255,240,.97)}
#db-badge.db-error{color:var(--red);border-color:rgba(230,57,70,.3);background:rgba(255,240,240,.97)}
#db-badge.db-hide{opacity:0;transform:translateX(-50%) translateY(-8px);pointer-events:none}
@keyframes db-spin{to{transform:rotate(360deg)}}
#db-spin-icon{animation:db-spin .85s linear infinite;display:inline-block}
/* Real content wrapper */
#db-real-content{display:none}
</style>
</head>
<body>

<!-- ─── Loading badge ─────────────────────────────────────────────────── -->
<div id="db-badge">
  <i id="db-spin-icon" class="ti ti-loader-2"></i>
  <span id="db-badge-text">Menyiapkan display board…</span>
</div>

<!-- ─── Skeleton UI (topbar + progress + slide skeleton) ─────────────── -->
<div id="db-skeleton">
  <!-- Skeleton topbar (mirrors real topbar layout) -->
  <div id="topbar">
    <span class="logo"><i class="ti ti-chart-bar"></i> FinBoard</span>
    <span class="pb"><i class="ti ti-calendar-event"></i> Periode: {{ $periodeLabel }}</span>
    <div id="dots">
      <div class="dot active"></div>
      <div class="dot"></div>
      <div class="dot"></div>
      <div class="dot"></div>
      <div class="dot"></div>
      <div class="dot"></div>
      <div class="dot"></div>
    </div>
    <div class="tr">
      <span id="dlbl"></span>&nbsp;<span id="clock"></span>
    </div>
  </div>

  <!-- Progress bar -->
  <div id="pw"><div id="pf"></div></div>

  <!-- Skeleton slide area -->
  <div id="sw">
    <!-- Skeleton Slide 0 — Financial Highlights -->
    <div class="slide active">
      <div class="db-shimmer" style="height:14px;width:220px;margin-bottom:6px"></div>
      <!-- KPI grid skeleton (4 cards) -->
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:5px;flex-shrink:0">
        <div class="gc" style="height:64px"><div class="db-shimmer" style="margin:12px;height:40px"></div></div>
        <div class="gc" style="height:64px"><div class="db-shimmer" style="margin:12px;height:40px"></div></div>
        <div class="gc" style="height:64px"><div class="db-shimmer" style="margin:12px;height:40px"></div></div>
        <div class="gc" style="height:64px"><div class="db-shimmer" style="margin:12px;height:40px"></div></div>
      </div>
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:5px;flex-shrink:0">
        <div class="gc" style="height:64px"><div class="db-shimmer" style="margin:12px;height:40px"></div></div>
        <div class="gc" style="height:64px"><div class="db-shimmer" style="margin:12px;height:40px"></div></div>
        <div class="gc" style="height:64px"><div class="db-shimmer" style="margin:12px;height:40px"></div></div>
        <div class="gc" style="height:64px"><div class="db-shimmer" style="margin:12px;height:40px"></div></div>
      </div>
      <!-- Chart placeholders -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;flex:1;min-height:0">
        <div class="gc" style="padding:10px;display:flex;flex-direction:column;gap:8px">
          <div class="db-shimmer" style="height:10px;width:60%"></div>
          <div class="db-shimmer" style="flex:1;border-radius:6px"></div>
        </div>
        <div class="gc" style="padding:10px;display:flex;flex-direction:column;gap:8px">
          <div class="db-shimmer" style="height:10px;width:60%"></div>
          <div class="db-shimmer" style="flex:1;border-radius:6px"></div>
        </div>
      </div>
    </div>

    <!-- Skeleton Slide 1 — DPK -->
    <div class="slide">
      <div class="db-shimmer" style="height:14px;width:200px;margin-bottom:6px"></div>
      <div style="display:grid;grid-template-columns:1fr 2fr;gap:8px;flex:1;min-height:0">
        <div style="display:flex;flex-direction:column;gap:8px">
          <div class="gc" style="height:72px"><div class="db-shimmer" style="margin:12px;height:48px"></div></div>
          <div class="gc" style="height:72px"><div class="db-shimmer" style="margin:12px;height:48px"></div></div>
          <div class="gc" style="height:72px"><div class="db-shimmer" style="margin:12px;height:48px"></div></div>
          <div class="gc" style="flex:1"><div class="db-shimmer" style="margin:12px;height:80%"></div></div>
        </div>
        <div style="display:grid;grid-template-rows:1fr 1fr;gap:8px;min-height:0">
          <div style="display:grid;grid-template-columns:2fr 1fr;gap:8px">
            <div class="gc cc"><div class="db-shimmer" style="height:10px;width:55%;margin-bottom:6px"></div><div class="db-shimmer" style="flex:1"></div></div>
            <div class="gc cc"><div class="db-shimmer" style="height:10px;width:55%;margin-bottom:6px"></div><div class="db-shimmer" style="flex:1"></div></div>
          </div>
          <div style="display:grid;grid-template-columns:3fr 2fr;gap:8px">
            <div class="gc cc"><div class="db-shimmer" style="height:10px;width:55%;margin-bottom:6px"></div><div class="db-shimmer" style="flex:1"></div></div>
            <div class="gc cc"><div class="db-shimmer" style="height:10px;width:55%;margin-bottom:6px"></div><div class="db-shimmer" style="flex:1"></div></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Skeleton Slide 2 — Pembiayaan -->
    <div class="slide">
      <div class="db-shimmer" style="height:14px;width:220px;margin-bottom:6px"></div>
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;flex-shrink:0">
        <div class="gc" style="height:72px"><div class="db-shimmer" style="margin:12px;height:48px"></div></div>
        <div class="gc" style="height:72px"><div class="db-shimmer" style="margin:12px;height:48px"></div></div>
        <div class="gc" style="height:72px"><div class="db-shimmer" style="margin:12px;height:48px"></div></div>
        <div class="gc" style="height:72px"><div class="db-shimmer" style="margin:12px;height:48px"></div></div>
      </div>
      <div style="display:grid;grid-template-columns:3fr 2fr;gap:8px;flex:1;min-height:0">
        <div class="gc cc"><div class="db-shimmer" style="height:10px;width:55%;margin-bottom:6px"></div><div class="db-shimmer" style="flex:1"></div></div>
        <div class="gc cc"><div class="db-shimmer" style="height:10px;width:55%;margin-bottom:6px"></div><div class="db-shimmer" style="flex:1"></div></div>
      </div>
    </div>

    <!-- Skeleton Slide 3 — Segmentasi -->
    <div class="slide">
      <div class="db-shimmer" style="height:14px;width:240px;margin-bottom:6px"></div>
      <div class="gc" style="flex:1;padding:10px;display:flex;flex-direction:column;gap:8px">
        <div class="db-shimmer" style="height:10px;width:40%"></div>
        <div class="db-shimmer" style="height:32px"></div>
        <div class="db-shimmer" style="flex:1"></div>
      </div>
    </div>

    <!-- Skeleton Slide 4 — NPF -->
    <div class="slide">
      <div class="db-shimmer" style="height:14px;width:230px;margin-bottom:6px"></div>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;flex-shrink:0">
        <div class="gc" style="height:72px"><div class="db-shimmer" style="margin:12px;height:48px"></div></div>
        <div class="gc" style="height:72px"><div class="db-shimmer" style="margin:12px;height:48px"></div></div>
        <div class="gc" style="height:72px"><div class="db-shimmer" style="margin:12px;height:48px"></div></div>
      </div>
      <div style="display:grid;grid-template-columns:2fr 1fr;gap:8px;flex:1;min-height:0">
        <div style="display:grid;grid-template-rows:1fr 1fr;gap:8px">
          <div class="gc cc"><div class="db-shimmer" style="height:10px;width:55%;margin-bottom:6px"></div><div class="db-shimmer" style="flex:1"></div></div>
          <div class="gc cc"><div class="db-shimmer" style="height:10px;width:55%;margin-bottom:6px"></div><div class="db-shimmer" style="flex:1"></div></div>
        </div>
        <div class="gc" style="padding:10px;display:flex;flex-direction:column;gap:8px">
          <div class="db-shimmer" style="height:10px;width:55%"></div>
          <div class="db-shimmer" style="flex:1"></div>
        </div>
      </div>
    </div>

    <!-- Skeleton Slide 5 — Peta Sebaran -->
    <div class="slide">
      <div class="db-shimmer" style="height:14px;width:260px;margin-bottom:6px"></div>
      <div style="display:grid;grid-template-columns:3fr 1fr;gap:8px;flex:1;min-height:0">
        <div class="gc db-shimmer" style="border-radius:14px"></div>
        <div class="gc cc" style="display:flex;flex-direction:column;gap:8px;padding:10px">
          <div class="db-shimmer" style="height:10px;width:80%"></div>
          <div class="db-shimmer" style="flex:1"></div>
        </div>
      </div>
    </div>

    <!-- Skeleton Slide 6 — Performa AO -->
    <div class="slide">
      <div class="db-shimmer" style="height:14px;width:240px;margin-bottom:6px"></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;flex:1;min-height:0">
        <div style="display:flex;flex-direction:column;gap:8px">
          <div class="gc" style="height:140px;padding:10px"><div class="db-shimmer" style="height:100%"></div></div>
          <div class="gc cc" style="flex:1"><div class="db-shimmer" style="height:10px;width:55%;margin-bottom:6px"></div><div class="db-shimmer" style="flex:1"></div></div>
          <div class="gc cc" style="flex:1"><div class="db-shimmer" style="height:10px;width:55%;margin-bottom:6px"></div><div class="db-shimmer" style="flex:1"></div></div>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px">
          <div class="gc" style="height:140px;padding:10px"><div class="db-shimmer" style="height:100%"></div></div>
          <div class="gc cc" style="flex:1"><div class="db-shimmer" style="height:10px;width:55%;margin-bottom:6px"></div><div class="db-shimmer" style="flex:1"></div></div>
          <div class="gc cc" style="flex:1"><div class="db-shimmer" style="height:10px;width:55%;margin-bottom:6px"></div><div class="db-shimmer" style="flex:1"></div></div>
        </div>
      </div>
    </div>
  </div>{{-- end skeleton #sw --}}
</div>{{-- end #db-skeleton --}}

<!-- ─── Real content is injected here after fetch ────────────────────── -->
<div id="db-real-content"></div>

<script>
(function () {
  'use strict';

  // ── Clock (runs immediately on the skeleton topbar) ─────────────────
  function tick() {
    var n = new Date();
    var cl = document.getElementById('clock');
    var dl = document.getElementById('dlbl');
    if (cl) cl.textContent = n.toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
    if (dl) dl.textContent = n.toLocaleDateString('id-ID', {weekday:'long',day:'numeric',month:'long',year:'numeric'});
  }
  tick();
  setInterval(tick, 1000);

  // ── Progress (uses existing #pw/#pf on skeleton topbar) ─────────────
  var _pf = document.getElementById('pf');
  var _pv = 0;
  function setProgress(pct) {
    _pv = Math.max(_pv, pct);
    if (_pf) _pf.style.width = _pv + '%';
  }
  function finishProgress() {
    setProgress(100);
    setTimeout(function () {
      var pw = document.getElementById('pw');
      if (pw) { pw.style.opacity = '0'; pw.style.transition = 'opacity .4s'; }
    }, 400);
  }

  // ── Badge helper ────────────────────────────────────────────────────
  var badge     = document.getElementById('db-badge');
  var badgeText = document.getElementById('db-badge-text');
  var spinIcon  = document.getElementById('db-spin-icon');

  function setBadge(msg, state) {
    if (badgeText) badgeText.textContent = msg;
    if (badge) {
      badge.classList.remove('db-done', 'db-error');
      if (state) badge.classList.add(state);
    }
    if (spinIcon) spinIcon.style.display = (state === 'db-done' || state === 'db-error') ? 'none' : '';
  }

  function hideBadge() {
    if (badge) badge.classList.add('db-hide');
  }

  // ── Script injector ─────────────────────────────────────────────────
  function injectScripts(htmlString) {
    return new Promise(function (resolve) {
      var tmp = document.createElement('div');
      tmp.innerHTML = htmlString;
      var scripts = Array.from(tmp.querySelectorAll('script'));
      if (!scripts.length) { resolve(); return; }
      var queue = scripts.slice();
      function next() {
        if (!queue.length) { resolve(); return; }
        var old = queue.shift();
        var el  = document.createElement('script');
        if (old.src) {
          el.src     = old.src;
          el.onload  = next;
          el.onerror = next;
          document.body.appendChild(el);
        } else {
          try {
            el.textContent = old.textContent;
            document.body.appendChild(el);
          } catch (e) {
            console.warn('[DisplayBoard] Script error (lanjut):', e);
          }
          next();
        }
      }
      next();
    });
  }

  // ── Reveal: remove skeleton from DOM, show real content ─────────────
  //    Removing #db-skeleton ensures querySelectorAll('.slide') in the
  //    injected scripts only finds the REAL slides — not skeleton placeholders.
  function revealContent() {
    var skeleton = document.getElementById('db-skeleton');
    var content  = document.getElementById('db-real-content');
    if (skeleton) skeleton.remove();
    if (content) {
      content.style.display = 'block';
      content.style.opacity = '1';
    }
  }

  // ── Build render URL (provided by server — handles both token & auth routes) ──
  var _renderUrl = '{{ $renderUrl }}';

  // ── Main: fetch display board content ───────────────────────────────
  setProgress(5);
  setBadge('Menghubungkan ke server…');

  fetch(_renderUrl, {
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
    credentials: 'same-origin'
  })
  .then(function (resp) {
    if (!resp.ok) throw new Error('HTTP ' + resp.status);
    setProgress(35);
    setBadge('Mengunduh data display board…');
    return resp.json();
  })
  .then(function (data) {
    setProgress(60);
    setBadge('Membangun tampilan…');

    // 1. Inject the real slide HTML into #db-real-content
    var contentArea = document.getElementById('db-real-content');
    if (contentArea) contentArea.innerHTML = data.html || '';

    setProgress(80);

    // 2. Reveal real content (removes skeleton from DOM, shows real content)
    //    Must happen BEFORE script injection so the injected scripts can
    //    find the real slide elements (querySelectorAll('.slide') etc.)
    revealContent();

    setProgress(88);

    // 3. Inject & execute display-board scripts
    //    These scripts contain: data constants, clock, AP config, _initCharts(),
    //    slideshow engine, and the boot calls (_initCharts(0), _startBar(), timer).
    //    Since display-board uses lazy _initCharts() per slide (NOT DOMContentLoaded),
    //    no DOMContentLoaded shim is needed here.
    return injectScripts(data.scripts || '').then(function () {
      setProgress(100);
      setBadge('Selesai', 'db-done');
      finishProgress();
      setTimeout(hideBadge, 1800);
    });
  })
  .catch(function (err) {
    console.error('[DisplayBoard Shell] Gagal memuat konten:', err);
    setBadge('Gagal memuat. Klik untuk coba ulang.', 'db-error');
    if (badge) {
      badge.style.cursor = 'pointer';
      badge.onclick = function () { window.location.reload(); };
    }
    finishProgress();
  });

}());
</script>
</body>
</html>
