@extends('layouts.app')

@section('title', 'Dashboard Bank')

@section('styles')
<link rel="stylesheet" href="/template/assets/vendor/libs/apex-charts/apex-charts.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
/* =====================================================
   SKELETON SCREEN — dipakai saat data sedang dimuat
   ===================================================== */
@keyframes skel-shimmer {
    0%   { background-position: -700px 0; }
    100% { background-position: 700px 0; }
}

.skel {
    background: linear-gradient(90deg, #eef0f2 25%, #e2e5e9 50%, #eef0f2 75%);
    background-size: 1400px 100%;
    animation: skel-shimmer 1.6s ease-in-out infinite;
    border-radius: 5px;
    display: block;
}

.skel-circle { border-radius: 50% !important; }

.skel-card {
    background: #fff;
    border-radius: 0.5rem;
    border: 1px solid rgba(0,0,0,0.07);
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    overflow: hidden;
    margin-bottom: 0;
}

.skel-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid rgba(0,0,0,0.06);
    background: #fafbfc;
}

.skel-body { padding: 1.25rem; }

/* Loading progress bar di atas halaman */
#sk-progress-bar {
    position: fixed;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: #e9ecef;
    z-index: 99999;
}

#sk-progress-fill {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #696cff, #7c3aed);
    transition: width 0.4s ease;
    border-radius: 0 3px 3px 0;
}

/* Badge status loading */
#sk-badge {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    z-index: 9999;
    background: rgba(105, 108, 255, 0.95);
    color: #fff;
    padding: 0.45rem 1rem;
    border-radius: 50px;
    font-size: 0.78rem;
    font-weight: 600;
    box-shadow: 0 4px 18px rgba(105,108,255,0.4);
    display: flex;
    align-items: center;
    gap: 0.45rem;
    transition: opacity 0.4s ease, transform 0.4s ease;
}

#sk-badge.sk-done {
    background: rgba(40, 167, 69, 0.95);
    box-shadow: 0 4px 18px rgba(40,167,69,0.3);
}

#sk-badge.sk-error {
    background: rgba(220, 53, 69, 0.95);
    box-shadow: 0 4px 18px rgba(220,53,69,0.3);
}

#sk-badge.sk-hide {
    opacity: 0;
    transform: translateY(8px);
    pointer-events: none;
}

.sk-spin {
    width: 13px; height: 13px;
    border: 2px solid rgba(255,255,255,0.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: sk-spin 0.7s linear infinite;
    flex-shrink: 0;
}

@keyframes sk-spin { to { transform: rotate(360deg); } }

/* Wrapper real content — mulai tersembunyi */
#sk-content-area {
    display: none;
    opacity: 0;
    transition: opacity 0.4s ease;
}

#sk-content-area.sk-visible {
    display: block;
    opacity: 1;
}

@media (prefers-reduced-motion: reduce) {
    .skel { animation: none; background: #e9ecef; }
    .sk-spin { animation: none; }
    #sk-content-area { transition: none; }
}
</style>
@endsection

@section('content')

{{-- Progress bar loading --}}
<div id="sk-progress-bar"><div id="sk-progress-fill"></div></div>

{{-- Badge status --}}
<div id="sk-badge"><div class="sk-spin" id="sk-spin-icon"></div><span id="sk-badge-text">Memuat Dashboard…</span></div>

{{-- ===================================================
     SKELETON PLACEHOLDER
     =================================================== --}}
<div id="sk-skeleton-area">

    {{-- Search bar --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="skel-card">
                <div class="skel-body py-4">
                    <div class="row align-items-center g-3">
                        <div class="col-md-8 col-12">
                            <div class="skel" style="height:48px;border-radius:8px;"></div>
                        </div>
                        <div class="col-md-4 col-12 d-flex justify-content-md-end">
                            <div class="skel" style="height:32px;width:130px;border-radius:20px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Time-range filter --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="skel-card">
                <div class="skel-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <div class="skel mb-2" style="height:20px;width:210px;"></div>
                        <div class="skel" style="height:13px;width:140px;"></div>
                    </div>
                    <div class="d-flex gap-2">
                        @foreach(['1D','1W','1M','3M','1Y','YTD','ALL'] as $btn)
                        <div class="skel" style="height:32px;width:44px;border-radius:4px;"></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Financial Highlights --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="skel-card">
                <div class="skel-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <div class="skel mb-2" style="height:24px;width:235px;"></div>
                        <div class="skel mb-1" style="height:13px;width:280px;"></div>
                        <div class="skel" style="height:13px;width:220px;"></div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <div class="skel" style="height:32px;width:76px;border-radius:20px;"></div>
                        <div class="skel" style="height:32px;width:76px;border-radius:20px;"></div>
                        <div class="skel" style="height:32px;width:90px;border-radius:6px;"></div>
                        <div class="skel" style="height:32px;width:110px;border-radius:6px;"></div>
                    </div>
                </div>
                <div class="skel-body">
                    <div class="row g-3">
                        @for($i = 0; $i < 8; $i++)
                        <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                            <div class="skel-card" style="min-height:110px;">
                                <div class="skel-body d-flex align-items-center gap-3">
                                    <div class="skel skel-circle flex-shrink-0" style="width:56px;height:56px;"></div>
                                    <div class="flex-grow-1">
                                        <div class="skel mb-2" style="height:11px;width:80%;"></div>
                                        <div class="skel mb-2" style="height:22px;width:65%;"></div>
                                        <div class="skel" style="height:11px;width:50%;"></div>
                                    </div>
                                    <div class="d-flex flex-column align-items-center gap-1 flex-shrink-0" style="min-width:56px;">
                                        <div class="skel skel-circle" style="width:26px;height:26px;"></div>
                                        <div class="skel" style="height:20px;width:54px;border-radius:20px;margin-top:4px;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI Cards: Funding / Lending / NPF --}}
    <div class="row">
        @for($i = 0; $i < 3; $i++)
        <div class="col-lg-4 col-md-6 col-12 mb-4">
            <div class="skel-card h-100" style="min-height:440px;">
                <div class="skel-header d-flex justify-content-between align-items-start gap-2">
                    <div style="flex:1;">
                        <div class="skel mb-2" style="height:20px;width:115px;"></div>
                        <div class="skel mb-1" style="height:13px;width:160px;"></div>
                        <div class="skel" style="height:13px;width:135px;"></div>
                    </div>
                    <div class="skel" style="height:22px;width:56px;border-radius:20px;flex-shrink:0;"></div>
                </div>
                <div class="skel-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <div class="skel mb-2" style="height:34px;width:155px;"></div>
                            <div class="skel" style="height:13px;width:120px;"></div>
                        </div>
                        <div class="skel skel-circle" style="width:52px;height:52px;flex-shrink:0;"></div>
                    </div>
                    @for($j = 0; $j < 3; $j++)
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="skel skel-circle flex-shrink-0" style="width:36px;height:36px;"></div>
                        <div class="flex-grow-1">
                            <div class="skel mb-1" style="height:11px;width:75%;"></div>
                            <div class="skel" style="height:13px;width:45%;"></div>
                        </div>
                        <div class="skel flex-shrink-0" style="height:14px;width:38px;"></div>
                    </div>
                    @endfor
                    <div class="mt-3 pt-3" style="border-top:1px solid #f0f0f0;">
                        <div class="skel mb-3" style="height:15px;width:170px;"></div>
                        @for($k = 0; $k < 3; $k++)
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="skel skel-circle flex-shrink-0" style="width:30px;height:30px;"></div>
                            <div class="flex-grow-1">
                                <div class="skel mb-1" style="height:11px;width:70%;"></div>
                                <div class="skel" style="height:13px;width:48%;"></div>
                            </div>
                        </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
        @endfor
    </div>

    {{-- Charts Row --}}
    <div class="row mb-4">
        <div class="col-lg-8 col-12 mb-4">
            <div class="skel-card h-100">
                <div class="skel-header">
                    <div class="skel mb-2" style="height:20px;width:230px;"></div>
                    <div class="skel" style="height:13px;width:160px;"></div>
                </div>
                <div class="skel-body">
                    <div class="skel" style="height:290px;border-radius:8px;"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-12 mb-4">
            <div class="skel-card h-100">
                <div class="skel-header">
                    <div class="skel mb-2" style="height:20px;width:180px;"></div>
                    <div class="skel" style="height:13px;width:130px;"></div>
                </div>
                <div class="skel-body">
                    <div class="skel" style="height:290px;border-radius:8px;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- AO Performance table --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="skel-card">
                <div class="skel-header d-flex justify-content-between align-items-center gap-2">
                    <div class="skel" style="height:20px;width:200px;"></div>
                    <div class="skel" style="height:32px;width:130px;border-radius:6px;"></div>
                </div>
                <div class="skel-body">
                    <div class="d-flex gap-3 mb-3 pb-2" style="border-bottom:2px solid #f0f0f0;">
                        @for($i = 0; $i < 5; $i++)
                        <div class="skel flex-grow-1" style="height:13px;"></div>
                        @endfor
                    </div>
                    @for($i = 0; $i < 5; $i++)
                    <div class="d-flex gap-3 mb-3 align-items-center">
                        <div class="skel skel-circle flex-shrink-0" style="width:32px;height:32px;"></div>
                        <div class="skel flex-grow-1" style="height:13px;"></div>
                        <div class="skel flex-grow-1" style="height:13px;"></div>
                        <div class="skel flex-grow-1" style="height:13px;"></div>
                        <div class="skel flex-grow-1" style="height:13px;"></div>
                    </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>

    {{-- Map + distribution --}}
    <div class="row mb-4">
        <div class="col-lg-6 col-12 mb-4">
            <div class="skel-card h-100">
                <div class="skel-header">
                    <div class="skel" style="height:20px;width:225px;"></div>
                </div>
                <div class="skel-body">
                    <div class="skel" style="height:360px;border-radius:8px;"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-12 mb-4">
            <div class="skel-card h-100">
                <div class="skel-header">
                    <div class="skel" style="height:20px;width:200px;"></div>
                </div>
                <div class="skel-body">
                    @for($i = 0; $i < 8; $i++)
                    <div class="d-flex gap-3 mb-3 align-items-center">
                        <div class="skel flex-shrink-0" style="height:13px;width:24px;"></div>
                        <div class="skel flex-grow-1" style="height:13px;"></div>
                        <div class="skel flex-shrink-0" style="height:13px;width:80px;"></div>
                        <div class="skel flex-shrink-0" style="height:13px;width:55px;"></div>
                    </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>

    {{-- Funding detail tables --}}
    <div class="row mb-4">
        @for($i = 0; $i < 2; $i++)
        <div class="col-lg-6 col-12 mb-4">
            <div class="skel-card">
                <div class="skel-header">
                    <div class="skel" style="height:20px;width:200px;"></div>
                </div>
                <div class="skel-body">
                    @for($j = 0; $j < 6; $j++)
                    <div class="d-flex gap-3 mb-3 align-items-center">
                        <div class="skel skel-circle flex-shrink-0" style="width:28px;height:28px;"></div>
                        <div class="skel flex-grow-1" style="height:13px;"></div>
                        <div class="skel flex-shrink-0" style="height:13px;width:90px;"></div>
                        <div class="skel flex-shrink-0" style="height:20px;width:50px;border-radius:20px;"></div>
                    </div>
                    @endfor
                </div>
            </div>
        </div>
        @endfor
    </div>

</div>{{-- /sk-skeleton-area --}}

{{-- ===================================================
     REAL CONTENT — di-inject via AJAX
     =================================================== --}}
<div id="sk-content-area"></div>

@endsection

@section('scripts')
<script>
(function () {
    'use strict';

    // ── Utilitas progress bar ────────────────────────────────────────
    var progressFill = document.getElementById('sk-progress-fill');
    var progressVal  = 0;

    function setProgress(pct) {
        progressVal = Math.max(progressVal, pct);
        if (progressFill) progressFill.style.width = progressVal + '%';
    }

    function finishProgress() {
        setProgress(100);
        setTimeout(function () {
            var bar = document.getElementById('sk-progress-bar');
            if (bar) { bar.style.opacity = '0'; bar.style.transition = 'opacity 0.4s'; }
        }, 400);
    }

    // ── Badge helper ─────────────────────────────────────────────────
    var badge     = document.getElementById('sk-badge');
    var badgeText = document.getElementById('sk-badge-text');
    var spinIcon  = document.getElementById('sk-spin-icon');

    function setBadge(msg, state) {
        if (badgeText) badgeText.textContent = msg;
        if (badge) {
            badge.classList.remove('sk-done', 'sk-error');
            if (state) badge.classList.add(state);
        }
        if (spinIcon) spinIcon.style.display = (state === 'sk-done' || state === 'sk-error') ? 'none' : '';
    }

    function hideBadge() {
        if (badge) badge.classList.add('sk-hide');
    }

    // ── Script injector (handles <script src> dan inline <script>) ────
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
                var el = document.createElement('script');
                if (old.src) {
                    el.src = old.src;
                    el.onload  = next;
                    el.onerror = next;
                    document.body.appendChild(el);
                } else {
                    try {
                        el.textContent = old.textContent;
                        document.body.appendChild(el);
                    } catch (e) {
                        console.warn('[Dashboard] Script error (lanjut):', e);
                    }
                    next();
                }
            }

            next();
        });
    }

    // ── Reveal real content (immediate — no fade delay) ──────────────
    function revealContent() {
        var skeleton = document.getElementById('sk-skeleton-area');
        var content  = document.getElementById('sk-content-area');

        if (skeleton) {
            skeleton.style.display = 'none';
        }
        if (content) {
            content.style.display = 'block';
            content.style.opacity = '1';
        }
    }

    // ── Server-resolved defaults (fallback jika URL tidak punya params) ─
    var _bladeDefaults = {
        month: '{{ $filterMonth }}',
        year:  '{{ $filterYear }}',
        range: '{{ $range }}',
        start_day: '{{ $startDay }}',
        end_day:   '{{ $endDay }}',
        group_by: '{{ request('group_by', 'segmentasi') }}'
    };

    // ── Bangun query params dari URL saat ini ────────────────────────
    function buildRenderUrl() {
        var src    = new URLSearchParams(window.location.search);
        var params = new URLSearchParams();
        ['month','year','range','start_day','end_day','group_by'].forEach(function (k) {
            var v = src.get(k) || _bladeDefaults[k] || '';
            if (v) params.set(k, v);
        });
        params.set('_render', '1');
        return '/dashboard/render?' + params.toString();
    }

    // ── Main: fetch & inject dashboard content ───────────────────────
    setProgress(5);
    setBadge('Menghubungkan ke server…');

    var renderUrl = buildRenderUrl();

    fetch(renderUrl, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        credentials: 'same-origin'
    })
    .then(function (resp) {
        if (!resp.ok) throw new Error('HTTP ' + resp.status);
        setProgress(35);
        setBadge('Mengunduh data…');
        return resp.json();
    })
    .then(function (data) {
        setProgress(60);

        // Jika server me-redirect (normalisasi periode), ikuti URL baru
        if (data.redirect) {
            window.location.href = data.redirect;
            return;
        }

        setBadge('Membangun tampilan…');

        // 1. Inject styles
        if (data.styles && data.styles.trim()) {
            var styleEl = document.createElement('style');
            styleEl.textContent = data.styles;
            document.head.appendChild(styleEl);
        }

        setProgress(75);

        // 2. Inject content HTML
        var contentArea = document.getElementById('sk-content-area');
        if (contentArea) contentArea.innerHTML = data.html || '';

        setProgress(85);

        // 3. Reveal content FIRST so charts can measure container dimensions
        revealContent();
        setProgress(90);

        // 4. Shim document.addEventListener so DOMContentLoaded callbacks in
        //    injected scripts fire immediately (DCL already fired on the shell page)
        var _origDocAEL = document.addEventListener.bind(document);
        document.addEventListener = function (type, listener, opts) {
            if (type === 'DOMContentLoaded') {
                setTimeout(listener, 0); // fire right after current call stack
            } else {
                _origDocAEL(type, listener, opts);
            }
        };

        // 5. Inject & execute scripts (Leaflet, ApexCharts init, etc.)
        //    Content is already visible so charts render at correct size
        return injectScripts(data.scripts || '').then(function () {
            // Restore original addEventListener
            document.addEventListener = _origDocAEL;
            setProgress(100);
            setBadge('Selesai', 'sk-done');
            finishProgress();
            setTimeout(hideBadge, 1800);
        });
    })
    .catch(function (err) {
        console.error('[Dashboard Skeleton] Gagal memuat konten:', err);
        setBadge('Gagal memuat. Klik untuk coba ulang.', 'sk-error');
        if (badge) {
            badge.style.cursor = 'pointer';
            badge.onclick = function () { window.location.reload(); };
        }
        finishProgress();
    });

}());
</script>
@endsection
