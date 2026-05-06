@extends('layouts.bare')
@section('styles')
<style>
:root{
  --bg:#f0f2f8;--s1:#ffffff;--s2:#f8f9fc;
  --border:#dde0ef;--border2:#c4c9df;
  --text:#2c3050;--muted:#6c7293;--dim:#9fa4bc;
  --rp:#e63946;--rs:#ff7b7b;
  --blue:#696cff;--cyan:#0095b6;--green:#3a9a0a;--red:#e63946;
  --yellow:#c98000;--orange:#d97b00;
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
</style>
@endsection
@section('content')
<div id="topbar">
  <span class="logo"><i class="ti ti-chart-bar"></i> FinBoard</span>
  <span class="pb"><i class="ti ti-calendar-event"></i> Periode: {{ $periodeLabel }}</span>
  <div id="dots">
    <div class="dot active" onclick="goSlide(0)" title="Financial Highlights"></div>
    <div class="dot" onclick="goSlide(1)" title="Dana Pihak Ketiga"></div>
    <div class="dot" onclick="goSlide(2)" title="Pembiayaan"></div>
    <div class="dot" onclick="goSlide(3)" title="Segmentasi Pembiayaan"></div>
    <div class="dot" onclick="goSlide(4)" title="Kualitas NPF"></div>
    <div class="dot" onclick="goSlide(5)" title="Peta Sebaran Nasabah"></div>
    <div class="dot" onclick="goSlide(6)" title="Performa AO"></div>
  </div>
  <div class="tr"><span id="dlbl"></span>&nbsp;<span id="clock"></span>
    <div id="btn-play" onclick="togglePlay()" title="Play / Pause"><i id="ico-play" class="ti ti-pause"></i></div>
  </div>
</div>
<div id="pw"><div id="pf"></div></div>
<div id="sw">

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- SLIDE 0 : FINANCIAL HIGHLIGHTS                                     --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="slide active" id="s0">
  <div class="stitle"><i class="ti ti-chart-line"></i> Financial Highlights
    @if($financialHighlight)
      <span style="color:var(--dim);font-weight:400;font-size:.7rem;text-transform:none">&mdash; {{ $financialHighlight->period_month }}/{{ $financialHighlight->period_year }}</span>
    @endif
  </div>
  @if(!$financialHighlight)
    <div class="gc" style="flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px">
      <i class="ti ti-database-off" style="font-size:3rem;color:var(--dim)"></i>
      <span style="color:var(--muted)">Belum ada data Financial Highlights.</span>
    </div>
  @else
  @php
    $fhCards=[
      // Laba Rugi
      ['key'=>'pendapatan','label'=>'Pendapatan',      'icon'=>'ti-cash-banknote', 'color'=>'#71dd37', 'bg'=>'rgba(113,221,55,.15)',  'type'=>'nom', 'bad'=>false],
      ['key'=>'biaya',     'label'=>'Biaya',           'icon'=>'ti-receipt',       'color'=>'#ff9f43', 'bg'=>'rgba(255,159,67,.15)',  'type'=>'nom', 'bad'=>true],
      ['key'=>'laba_rugi', 'label'=>'Laba / Rugi',     'icon'=>'ti-coins',         'color'=>'#ffab00', 'bg'=>'rgba(255,171,0,.15)',   'type'=>'nom', 'bad'=>false],
      // Posisi Keuangan
      ['key'=>'aset',      'label'=>'Total Aset',      'icon'=>'ti-building-bank', 'color'=>'#e63946', 'bg'=>'rgba(230,57,70,.12)',  'type'=>'nom', 'bad'=>false],
      ['key'=>'dpk',       'label'=>'DPK (FH)',        'icon'=>'ti-database',      'color'=>'#03c3ec', 'bg'=>'rgba(3,195,236,.15)',   'type'=>'nom', 'bad'=>false],
      ['key'=>'pembiayaan','label'=>'Pembiayaan (FH)', 'icon'=>'ti-credit-card',   'color'=>'#71dd37', 'bg'=>'rgba(113,221,55,.15)',  'type'=>'nom', 'bad'=>false],
      // Rasio Modal & Profitabilitas
      ['key'=>'car',       'label'=>'CAR',             'icon'=>'ti-shield-check',  'color'=>'#e63946', 'bg'=>'rgba(230,57,70,.12)',  'type'=>'pct', 'bad'=>false],
      ['key'=>'roa',       'label'=>'ROA',             'icon'=>'ti-trending-up',   'color'=>'#71dd37', 'bg'=>'rgba(113,221,55,.15)',  'type'=>'pct', 'bad'=>false],
      ['key'=>'roe',       'label'=>'ROE',             'icon'=>'ti-chart-bar',     'color'=>'#03c3ec', 'bg'=>'rgba(3,195,236,.15)',  'type'=>'pct', 'bad'=>false],
      // Rasio Likuiditas & Risiko
      ['key'=>'cash_ratio','label'=>'Cash Ratio',      'icon'=>'ti-cash',          'color'=>'#8592a3', 'bg'=>'rgba(133,146,163,.15)','type'=>'pct', 'bad'=>false],
      ['key'=>'npf',       'label'=>'NPF (FH)',        'icon'=>'ti-alert-triangle','color'=>'#ff3e1d', 'bg'=>'rgba(255,62,29,.15)',   'type'=>'pct', 'bad'=>true],
      ['key'=>'fdr',       'label'=>'FDR',             'icon'=>'ti-scale',         'color'=>'#ffab00', 'bg'=>'rgba(255,171,0,.15)',   'type'=>'pct', 'bad'=>true],
      // Rasio Efisiensi
      ['key'=>'bopo',      'label'=>'BOPO',            'icon'=>'ti-calculator',    'color'=>'#ff9f43', 'bg'=>'rgba(255,159,67,.15)',  'type'=>'pct', 'bad'=>true],
    ];
  @endphp
  <div class="fh-grid">
    @foreach($fhCards as $c)
    @php
      $v=$financialHighlight->{$c['key']};
      $fmt=$c['type']==='pct'?number_format((float)$v,2).'%':'Rp '.number_format((float)$v/1e9,2).' M';
      $chg=$fhChanges[$c['key']]??null;
      $chgV=(float)($chg??0);
      $goodUp=!($c['bad']??false);
      $isUp=$chgV>=0;
      $chgCls=($isUp===$goodUp)?'up':'dn';
      $chgTxt=$chg!==null?($isUp?'&#9650;':'&#9660;').' '.number_format(abs($chgV),2).'%':'-';
    @endphp
    <div class="gc fh-card" style="--ct:linear-gradient(90deg,{{ $c['color'] }},{{ $c['color'] }}88)">
      <div class="fh-icon" style="background:{{ $c['bg'] }};color:{{ $c['color'] }}"><i class="ti {{ $c['icon'] }}"></i></div>
      <div class="fh-body">
        <div class="fh-lbl">{{ $c['label'] }}</div>
        <div class="fh-val" style="color:{{ $c['color'] }}">{{ $fmt }}</div>
        <div class="fh-chg">@if($chg!==null)<span class="kb {{ $chgCls }}" style="font-size:.6rem">{!! $chgTxt !!}</span>@endif</div>
      </div>
    </div>
    @endforeach
  </div>
  @if(count($fhTrends['labels'])>1)
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;flex:1;min-height:0">
    <div class="gc cc">
      <div class="cct">Tren Rasio Kinerja &mdash; CAR &middot; ROA &middot; ROE &middot; FDR &middot; NPF &middot; BOPO (%)</div>
      <div class="ca" id="fh-trend-chart"></div>
    </div>
    <div class="gc cc">
      <div class="cct">Tren Nominal &mdash; Aset &middot; DPK &middot; Pembiayaan &middot; Pendapatan &middot; Biaya &middot; Laba/Rugi (Rp M)</div>
      <div class="ca" id="fh-nom-chart"></div>
    </div>
  </div>
  @endif
  @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- SLIDE 1 : DANA PIHAK KETIGA (FUNDING)                             --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="slide" id="s1">
  <div class="stitle"><i class="ti ti-database"></i> Dana Pihak Ketiga &mdash; Tabungan &amp; Deposito</div>
  <div style="flex:1;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,2fr);gap:8px;min-height:0">
    {{-- Left: KPIs + Komposisi DP --}}
    <div style="display:flex;flex-direction:column;gap:8px;min-height:0;overflow:hidden">
      <div class="gc kpi-card" style="--ct:linear-gradient(90deg,#e63946,#ff7b7b);flex-shrink:0;padding:8px 12px">
        <div class="klbl">Total DPK</div>
        <div class="kval sm" style="color:var(--rp)">Rp {{ number_format($totalFunding/1e9,2) }} M</div>
        <span class="kb {{ $fundingGrowth>=0?'up':'dn' }}">{{ $fundingGrowth>=0?'&#9650;':'&#9660;' }} {{ abs($fundingGrowth) }}% MoM</span>
      </div>
      @php $tabPct=$totalFunding>0?round($totalTabungan/$totalFunding*100,1):0; @endphp
      <div class="gc kpi-card" style="--ct:linear-gradient(90deg,#03c3ec,#71dd37);flex-shrink:0;padding:8px 12px">
        <div class="klbl">Tabungan</div>
        <div class="kval sm" style="color:var(--cyan)">Rp {{ number_format($totalTabungan/1e9,2) }} M</div>
        <span class="kb cy">{{ $tabPct }}% dari DPK</span>
      </div>
      @php $depPct=$totalFunding>0?round($totalDeposito/$totalFunding*100,1):0; @endphp
      <div class="gc kpi-card" style="--ct:linear-gradient(90deg,#ff9f43,#ffab00);flex-shrink:0;padding:8px 12px">
        <div class="klbl">Deposito</div>
        <div class="kval sm" style="color:var(--orange)">Rp {{ number_format($totalDeposito/1e9,2) }} M</div>
        <span class="kb warn">{{ $depPct }}% dari DPK</span>
        @if($jumlahPencairan > 0)
        <span class="kb dn" style="margin-top:2px">&#9660; Cair: {{ number_format($jumlahPencairan) }} bilyet &middot; Rp {{ number_format($totalPencairan/1e9,2) }} M</span>
        @endif
      </div>
      <div class="gc cc" style="flex-shrink:0;padding:8px 12px">
        <div class="cct" style="margin-bottom:4px">Komposisi Sumber Dana (DP1/DP2/DP3)</div>
        @php
          $dp1Pct=$totalDanaReal>0?round($dp1Modal/$totalDanaReal*100,1):0;
          $dp2Pct=$totalDanaReal>0?round($dp2LinkAbp/$totalDanaReal*100,1):0;
          $dp3Pct=$totalDanaReal>0?round($dp3TabDep/$totalDanaReal*100,1):0;
        @endphp
        <div class="bar-row">
          <div class="bar-l" style="min-width:80px;font-size:.65rem">DP1 Modal</div>
          <div class="bar-o"><div class="bar-i" style="width:{{ $dp1Pct }}%;background:#e63946"></div></div>
          <div class="bar-v" style="color:#e63946;font-size:.67rem">{{ $dp1Pct }}%</div>
        </div>
        <div class="bar-row">
          <div class="bar-l" style="min-width:80px;font-size:.65rem">DP2 Linkage</div>
          <div class="bar-o"><div class="bar-i" style="width:{{ $dp2Pct }}%;background:#ffab00"></div></div>
          <div class="bar-v" style="color:#ffab00;font-size:.67rem">{{ $dp2Pct }}%</div>
        </div>
        <div class="bar-row">
          <div class="bar-l" style="min-width:80px;font-size:.65rem">DP3 Tab+Dep</div>
          <div class="bar-o"><div class="bar-i" style="width:{{ $dp3Pct }}%;background:#03c3ec"></div></div>
          <div class="bar-v" style="color:#03c3ec;font-size:.67rem">{{ $dp3Pct }}%</div>
        </div>
        <div style="margin-top:6px;padding-top:5px;border-top:1px solid var(--border)">
          <div style="font-size:.67rem;color:var(--dim)">Total Dana:
            <span style="color:var(--text);font-weight:700">Rp {{ number_format($totalDanaReal/1e9,2) }} M</span>
          </div>
        </div>
      </div>
      {{-- Top 10 Nasabah DPK --}}
      <div class="gc cc" style="flex:1;min-height:0;overflow:hidden">
        <div class="cct" style="flex-shrink:0">Top 10 Nasabah — Total DPK (Tab + Dep)</div>
        <div class="top-dpk-wrap">
          @php $dpkRows = $topDpkNasabah->values(); @endphp
          <div class="top-dpk-inner" style="animation-duration:{{ max(20, count($dpkRows)*3) }}s">
            {{-- duplicate rows for seamless loop --}}
            @foreach([$dpkRows, $dpkRows] as $set)
            @foreach($set as $i => $row)
            <div class="top-dpk-row">
              <span style="color:var(--muted);font-weight:700;font-size:.6rem">{{ $i+1 }}</span>
              <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--text)">{{ Str::limit($row->nama, 24) }}</span>
              <span style="color:var(--cyan);font-size:.6rem;white-space:nowrap">Tab: {{ number_format($row->tab_total/1e6,1) }} jt</span>
              <span style="color:var(--orange);font-size:.6rem;white-space:nowrap">Dep: {{ number_format($row->dep_total/1e6,1) }} jt</span>
              <span style="color:#71dd37;font-size:.6rem;font-weight:700;white-space:nowrap">Rp {{ number_format($row->grand_total/1e6,1) }} jt</span>
            </div>
            @endforeach
            @endforeach
          </div>
        </div>
      </div>
    </div>
    {{-- Right: Charts --}}
    <div style="display:grid;grid-template-rows:1fr 1fr;gap:8px;min-height:0">
      <div style="display:grid;grid-template-columns:2fr 1fr;gap:8px;min-height:0">
        <div class="gc cc">
          <div class="cct">Tren DPK &amp; Pencairan Deposito per Bulan (Rp Miliar)</div>
          <div class="ca" id="ch-dpk-trend"></div>
        </div>
        <div class="gc cc">
          <div class="cct">Proporsi DPK</div>
          <div class="ca" id="ch-dpk-pie"></div>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:3fr 2fr;gap:8px;min-height:0">
        <div class="gc cc">
          <div class="cct">Top 5 Produk Tabungan &mdash; Nominal (Rp M)</div>
          <div class="ca" id="ch-tab-bar"></div>
        </div>
        <div class="gc cc">
          <div class="cct">Komposisi Produk Deposito &mdash; Nominal (Rp M)</div>
          <div class="ca" id="ch-dep-bar"></div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- SLIDE 2 : PEMBIAYAAN (LENDING)                                    --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="slide" id="s2">
  <div class="stitle"><i class="ti ti-credit-card"></i> Pembiayaan &amp; Portofolio Lending</div>
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;flex-shrink:0">
    <div class="gc kpi-card" style="--ct:linear-gradient(90deg,#71dd37,#03c3ec)">
      <div class="klbl">Outstanding Pokok</div>
      <div class="kval sm" style="color:var(--green)">Rp {{ number_format($totalLending/1e9,2) }} M</div>
      <span class="kb {{ $lendingGrowth>=0?'up':'dn' }}">{{ $lendingGrowth>=0?'&#9650;':'&#9660;' }} {{ abs($lendingGrowth) }}% MoM</span>
    </div>
    <div class="gc kpi-card" style="--ct:linear-gradient(90deg,#e63946,#ff7b7b)">
      <div class="klbl">Total Plafon</div>
      <div class="kval sm" style="color:var(--rp)">Rp {{ number_format($totalPlafon/1e9,2) }} M</div>
      @php $util=$totalPlafon>0?round($totalLending/$totalPlafon*100,1):0; @endphp
      <span class="kb info">{{ $util }}% utilisasi</span>
    </div>
    <div class="gc kpi-card" style="--ct:linear-gradient(90deg,#696cff,#03c3ec)">
      <div class="klbl">Jumlah Kontrak</div>
      <div class="kval sm" style="color:#696cff">{{ number_format($totalNasabah) }}</div>
      <span class="kb cy"><i class="ti ti-users"></i> nasabah aktif</span>
    </div>
    <div class="gc kpi-card" style="--ct:linear-gradient(90deg,#ff3e1d,#ff9f43)">
      <div class="klbl">NPF Ratio</div>
      <div class="kval sm" style="color:var(--red)">{{ number_format($npfRatio,2) }}%</div>
      <span class="kb dn">Rp {{ number_format(($totalNPF??0)/1e9,2) }} M NPF</span>
    </div>
  </div>
  <div style="flex:1;display:grid;grid-template-columns:3fr 2fr;gap:8px;min-height:0">
    <div class="gc cc">
      <div class="cct">Tren Plafon vs Outstanding Pembiayaan (Rp Miliar)</div>
      <div class="ca" id="ch-lend-trend"></div>
    </div>
    <div class="gc cc">
      <div class="cct">Top Produk Pembiayaan &mdash; Outstanding (Rp M)</div>
      <div class="ca" id="ch-produk-bar"></div>
    </div>
  </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- SLIDE 3 : SEGMENTASI PEMBIAYAAN                                    --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="slide" id="s6">
  <div class="stitle"><i class="ti ti-chart-treemap"></i> Segmentasi Pembiayaan &mdash; Outstanding &amp; Disburse</div>
  <div style="flex:1;display:flex;flex-direction:column;min-height:0">
    <div class="gc cc" style="flex:1;min-height:0;display:flex;flex-direction:column">
      <div class="cct">Tabel Segmentasi Outstanding &amp; Disburse</div>
      <div class="nscr" style="flex:1">
        <table class="nt" style="font-size:.9rem">
          <thead>
            <tr>
              <th colspan="2" rowspan="2" style="text-align:center;vertical-align:middle;background:rgba(200,205,228,.15)">SEGMENTASI</th>
              <th colspan="2" style="text-align:center;background:rgba(40,167,69,.18);color:#28a745">DISBURSE</th>
              <th colspan="2" style="text-align:center;background:rgba(13,110,253,.18);color:#0d6efd">OUTSTANDING</th>
              <th colspan="5" style="text-align:center;background:rgba(255,193,7,.18);color:#b8860b">KOLEKTIBILITAS</th>
              <th rowspan="2" style="text-align:center;vertical-align:middle;background:rgba(200,205,228,.15)">CIF</th>
              <th rowspan="2" style="text-align:center;vertical-align:middle;background:rgba(200,205,228,.15)">NOA</th>
            </tr>
            <tr>
              <th style="text-align:right;background:rgba(40,167,69,.12);color:#28a745">DISBURSE</th>
              <th style="text-align:center;background:rgba(40,167,69,.12);color:#28a745">%</th>
              <th style="text-align:right;background:rgba(13,110,253,.12);color:#0d6efd">OUTSTANDING</th>
              <th style="text-align:center;background:rgba(13,110,253,.12);color:#0d6efd">%</th>
              <th style="text-align:center;background:rgba(255,193,7,.12);color:#b8860b">KOL 1</th>
              <th style="text-align:center;background:rgba(255,193,7,.12);color:#b8860b">KOL 2</th>
              <th style="text-align:center;background:rgba(255,193,7,.12);color:#b8860b">KOL 3</th>
              <th style="text-align:center;background:rgba(220,53,69,.2);color:#dc3545">KOL 4</th>
              <th style="text-align:center;background:rgba(220,53,69,.2);color:#dc3545">KOL 5</th>
            </tr>
          </thead>
          <tbody>
            @foreach($segmentasiData as $segment)
            <tr style="{{ $segment['is_total'] ? 'background:rgba(200,205,228,.15);font-weight:700' : '' }}">
              @if(isset($segment['rowspan']) && $segment['rowspan'] > 0)
                <td rowspan="{{ $segment['rowspan'] }}" style="vertical-align:middle;font-weight:800;font-size:.88rem;background:rgba(200,205,228,.08);border-right:2px solid var(--border)">{{ $segment['category'] }}</td>
              @endif
              @if(!$segment['is_total'])
                <td style="font-size:.85rem;color:var(--muted)">{{ $segment['type'] }}</td>
                <td style="text-align:right;font-variant-numeric:tabular-nums;color:var(--green)">
                  @if($segment['disburse'] >= 1000000000)
                    {{ number_format($segment['disburse']/1e9,2) }}M
                  @else
                    {{ number_format($segment['disburse']/1e6,0) }}jt
                  @endif
                </td>
                <td style="text-align:center;color:var(--dim)">{{ number_format($segment['pct_disburse'],1) }}%</td>
                <td style="text-align:right;font-variant-numeric:tabular-nums;color:var(--rp);font-weight:600">
                  @if($segment['outstanding'] >= 1000000000)
                    {{ number_format($segment['outstanding']/1e9,2) }}M
                  @else
                    {{ number_format($segment['outstanding']/1e6,0) }}jt
                  @endif
                </td>
                <td style="text-align:center;color:var(--dim)">{{ number_format($segment['pct_outstanding'],1) }}%</td>
                <td style="text-align:center;line-height:1.2">
                  <div style="font-size:.85rem">{{ ($segment['col1_sum']??0)>=1e9 ? number_format(($segment['col1_sum']??0)/1e9,1).'M' : number_format(($segment['col1_sum']??0)/1e6,0).'jt' }}</div>
                  <small style="font-size:.72rem;color:var(--muted)">{{ $segment['col1']??0 }} NOA</small>
                </td>
                <td style="text-align:center;line-height:1.2">
                  <div style="font-size:.85rem">{{ ($segment['col2_sum']??0)>=1e9 ? number_format(($segment['col2_sum']??0)/1e9,1).'M' : number_format(($segment['col2_sum']??0)/1e6,0).'jt' }}</div>
                  <small style="font-size:.72rem;color:var(--muted)">{{ $segment['col2']??0 }} NOA</small>
                </td>
                <td style="text-align:center;line-height:1.2">
                  <div style="font-size:.85rem;color:var(--yellow)">{{ ($segment['col3_sum']??0)>=1e9 ? number_format(($segment['col3_sum']??0)/1e9,1).'M' : number_format(($segment['col3_sum']??0)/1e6,0).'jt' }}</div>
                  <small style="font-size:.72rem;color:var(--muted)">{{ $segment['col3']??0 }} NOA</small>
                </td>
                <td style="text-align:center;line-height:1.2">
                  <div style="font-size:.85rem;color:var(--orange)">{{ ($segment['col4_sum']??0)>=1e9 ? number_format(($segment['col4_sum']??0)/1e9,1).'M' : number_format(($segment['col4_sum']??0)/1e6,0).'jt' }}</div>
                  <small style="font-size:.72rem;color:var(--muted)">{{ $segment['col4']??0 }} NOA</small>
                </td>
                <td style="text-align:center;line-height:1.2">
                  <div style="font-size:.85rem;color:var(--red)">{{ ($segment['col5_sum']??0)>=1e9 ? number_format(($segment['col5_sum']??0)/1e9,1).'M' : number_format(($segment['col5_sum']??0)/1e6,0).'jt' }}</div>
                  <small style="font-size:.72rem;color:var(--muted)">{{ $segment['col5']??0 }} NOA</small>
                </td>
                <td style="text-align:center">{{ number_format($segment['cif']??0) }}</td>
                <td style="text-align:center">{{ number_format($segment['noa']) }}</td>
              @else
                <td style="text-align:center;font-weight:800">{{ $segment['type'] }}</td>
                <td style="text-align:right;font-variant-numeric:tabular-nums;color:var(--green);font-weight:700">
                  @if($segment['disburse'] >= 1000000000)
                    {{ number_format($segment['disburse']/1e9,2) }}M
                  @else
                    {{ number_format($segment['disburse']/1e6,0) }}jt
                  @endif
                </td>
                <td style="text-align:center">{{ number_format($segment['pct_disburse'],1) }}%</td>
                <td style="text-align:right;font-variant-numeric:tabular-nums;color:var(--rp);font-weight:800">
                  @if($segment['outstanding'] >= 1000000000)
                    {{ number_format($segment['outstanding']/1e9,2) }}M
                  @else
                    {{ number_format($segment['outstanding']/1e6,0) }}jt
                  @endif
                </td>
                <td style="text-align:center">{{ number_format($segment['pct_outstanding'],1) }}%</td>
                <td style="text-align:center;line-height:1.2">
                  <div style="font-size:.85rem;font-weight:700">{{ ($segment['col1_sum']??0)>=1e9 ? number_format(($segment['col1_sum']??0)/1e9,1).'M' : number_format(($segment['col1_sum']??0)/1e6,0).'jt' }}</div>
                  <small style="font-size:.72rem;color:var(--muted)">{{ $segment['col1']??0 }}</small>
                </td>
                <td style="text-align:center;line-height:1.2">
                  <div style="font-size:.85rem;font-weight:700">{{ ($segment['col2_sum']??0)>=1e9 ? number_format(($segment['col2_sum']??0)/1e9,1).'M' : number_format(($segment['col2_sum']??0)/1e6,0).'jt' }}</div>
                  <small style="font-size:.72rem;color:var(--muted)">{{ $segment['col2']??0 }}</small>
                </td>
                <td style="text-align:center;line-height:1.2">
                  <div style="font-size:.85rem;font-weight:700;color:var(--yellow)">{{ ($segment['col3_sum']??0)>=1e9 ? number_format(($segment['col3_sum']??0)/1e9,1).'M' : number_format(($segment['col3_sum']??0)/1e6,0).'jt' }}</div>
                  <small style="font-size:.72rem;color:var(--muted)">{{ $segment['col3']??0 }}</small>
                </td>
                <td style="text-align:center;line-height:1.2">
                  <div style="font-size:.85rem;font-weight:700;color:var(--orange)">{{ ($segment['col4_sum']??0)>=1e9 ? number_format(($segment['col4_sum']??0)/1e9,1).'M' : number_format(($segment['col4_sum']??0)/1e6,0).'jt' }}</div>
                  <small style="font-size:.72rem;color:var(--muted)">{{ $segment['col4']??0 }}</small>
                </td>
                <td style="text-align:center;line-height:1.2">
                  <div style="font-size:.85rem;font-weight:700;color:var(--red)">{{ ($segment['col5_sum']??0)>=1e9 ? number_format(($segment['col5_sum']??0)/1e9,1).'M' : number_format(($segment['col5_sum']??0)/1e6,0).'jt' }}</div>
                  <small style="font-size:.72rem;color:var(--muted)">{{ $segment['col5']??0 }}</small>
                </td>
                <td style="text-align:center;font-weight:700">{{ number_format($segment['cif']??0) }}</td>
                <td style="text-align:center;font-weight:700">{{ number_format($segment['noa']) }}</td>
              @endif
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- SLIDE 4 : KUALITAS NPF                                            --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="slide" id="s3">
  <div class="stitle"><i class="ti ti-alert-circle"></i> Kualitas Pembiayaan &amp; Kolektibilitas NPF</div>
  @php $npfKontrak=($kolCount['3']??0)+($kolCount['4']??0)+($kolCount['5']??0); @endphp
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;flex-shrink:0">
    <div class="gc kpi-card" style="--ct:linear-gradient(90deg,#ff3e1d,#ff9f43);padding:8px 12px">
      <div class="klbl">Total OS NPF (Kol 3–5)</div>
      <div class="kval sm" style="color:var(--red)">Rp {{ number_format(($totalNPF??0)/1e9,2) }} M</div>
    </div>
    <div class="gc kpi-card" style="--ct:linear-gradient(90deg,#ff3e1d,#ff9f43);padding:8px 12px">
      <div class="klbl">Rasio NPF</div>
      <div class="kval sm" style="color:var(--red)">{{ number_format($npfRatio,2) }}%</div>
      <span class="kb dn">Threshold 5%</span>
    </div>
    <div class="gc kpi-card" style="--ct:linear-gradient(90deg,#ff9f43,#ffab00);padding:8px 12px">
      <div class="klbl">Kontrak NPF</div>
      <div class="kval sm" style="color:var(--orange)">{{ number_format($npfKontrak) }}</div>
      <span class="kb warn">Kol 3 + 4 + 5</span>
    </div>
  </div>
  <div class="chart-row cr21" style="flex:1;min-height:0">
    <div style="display:grid;grid-template-rows:1fr 1fr;gap:8px;min-height:0">
      <div class="gc cc">
        <div class="cct">Distribusi Kolektibilitas &mdash; Outstanding (Rp M)</div>
        <div class="ca" id="ch-kol"></div>
      </div>
      <div class="gc cc">
        <div class="cct">Tren Jumlah Kontrak per Kolektibilitas</div>
        <div class="ca" id="ch-kol-cnt"></div>
      </div>
    </div>
    <div class="gc cc" style="display:flex;flex-direction:column;min-height:0">
      <div class="cct">Top 10 Nasabah NPF Terbesar</div>
      <div class="nscr">
        <table class="nt">
          <thead><tr><th>#</th><th>Nama Nasabah</th><th>Produk</th><th>Kol</th><th style="text-align:right">OS Pokok</th></tr></thead>
          <tbody>
            @forelse($topNpf as $i=>$n)
            <tr>
              <td style="color:var(--dim);font-weight:700">{{ $i+1 }}</td>
              <td style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $n->nama??'-' }}</td>
              <td style="font-size:.65rem;color:var(--muted)">{{ $n->kdprd??'-' }}</td>
              <td><span class="nb nb{{ $n->colbaru }}">Kol {{ $n->colbaru }}</span></td>
              <td style="text-align:right;font-variant-numeric:tabular-nums">Rp {{ number_format($n->osmdlc/1e6,1) }} Jt</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;color:var(--dim);padding:20px">Tidak ada data NPF</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div style="margin-top:6px;border-top:1px solid var(--border);padding-top:6px">
        <div class="cct" style="margin-bottom:4px">Outstanding per Kolektibilitas</div>
        @php
          $kolLbl=['1'=>'Lancar','2'=>'Dlm Perhatian','3'=>'Kur. Lancar','4'=>'Diragukan','5'=>'Macet'];
          $kolCol=['1'=>'var(--green)','2'=>'var(--cyan)','3'=>'var(--yellow)','4'=>'var(--orange)','5'=>'var(--red)'];
          $maxK=max(array_values($kolDistrib)?:[1]);
        @endphp
        @foreach($kolLbl as $k=>$l)
        <div class="bar-row">
          <div class="bar-l">{{ $l }}</div>
          <div class="bar-o"><div class="bar-i" style="width:{{ $maxK>0?round($kolDistrib[$k]/$maxK*100).'%':'0%' }};background:{{ $kolCol[$k] }}"></div></div>
          <div class="bar-v" style="color:{{ $kolCol[$k] }}">Rp {{ number_format($kolDistrib[$k]/1e9,2) }} M</div>
        </div>
        @endforeach
      </div>
    </div>
  </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- SLIDE 5 : PETA SEBARAN NASABAH                                     --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="slide" id="s4">
  <div class="stitle"><i class="ti ti-map-pin"></i> Peta Sebaran Nasabah &mdash; Distribusi per Kecamatan (Bogor)</div>
  @php
    $sebaranBogor = $sebaranNasabah->filter(fn($sb) => stripos($sb->kota ?? '', 'BOGOR') !== false)->values();
    $sbTop    = $sebaranBogor->take(10);
    $sbBottom = $sebaranBogor->slice(max(0, $sebaranBogor->count() - 10))->sortBy('jumlah')->values();
  @endphp
  <div style="flex:1;display:grid;grid-template-columns:3fr 1fr;gap:8px;min-height:0">
    <div class="gc" style="overflow:hidden;position:relative">
      <div id="map-nasabah" style="width:100%;height:100%;border-radius:14px;z-index:1"></div>
    </div>
    <div class="gc cc" style="display:flex;flex-direction:column">
      <div class="cct">Top 10 &amp; Bottom 10 Kecamatan &mdash; Bogor</div>
      <div class="nscr" style="flex:1">
        <table class="nt">
          <thead><tr><th>#</th><th>Kecamatan</th><th>Kab/Kota</th><th style="text-align:right">Nasabah</th><th style="text-align:right">OS (M)</th></tr></thead>
          <tbody>
            <tr><td colspan="5" style="background:rgba(105,108,255,.08);color:var(--blue);font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;padding:4px 8px">&#9650; Top 10 Terbanyak</td></tr>
            @foreach($sbTop as $i => $sb)
            <tr>
              <td style="color:var(--dim);font-weight:700">{{ $i+1 }}</td>
              <td style="font-size:.72rem;max-width:80px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $sb->kecamatan }}</td>
              <td style="font-size:.65rem;color:var(--muted);max-width:70px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $sb->kota }}</td>
              <td style="text-align:right;color:var(--blue);font-weight:700">{{ number_format($sb->jumlah) }}</td>
              <td style="text-align:right;color:var(--rp);font-size:.7rem">{{ number_format($sb->outstanding/1e9,1) }}</td>
            </tr>
            @endforeach
            <tr><td colspan="5" style="background:rgba(230,57,70,.07);color:var(--red);font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;padding:4px 8px">&#9660; Bottom 10 Tersedikit</td></tr>
            @foreach($sbBottom as $i => $sb)
            <tr>
              <td style="color:var(--dim)">{{ $i+1 }}</td>
              <td style="font-size:.72rem;max-width:80px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--muted)">{{ $sb->kecamatan }}</td>
              <td style="font-size:.65rem;color:var(--muted);max-width:70px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $sb->kota }}</td>
              <td style="text-align:right;color:var(--muted)">{{ number_format($sb->jumlah) }}</td>
              <td style="text-align:right;color:var(--muted);font-size:.7rem">{{ number_format($sb->outstanding/1e9,1) }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- SLIDE 6 : PERFORMA ACCOUNT OFFICER                                --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="slide" id="s5">
  <div class="stitle"><i class="ti ti-users"></i> Performa Account Officer &mdash; Lending &amp; Funding</div>
  <div style="flex:1;display:grid;grid-template-columns:1fr 1fr;gap:8px;min-height:0">

    {{-- ═══════════ LENDING COLUMN (LEFT) ═══════════ --}}
    <div style="display:flex;flex-direction:column;gap:8px;min-height:0">

      {{-- Row 1: Top AO Lending --}}
      <div class="gc cc" style="flex:0 0 auto">
        <div class="cct"><i class="ti ti-trophy" style="color:var(--gold)"></i> Top AO Lending &mdash; Outstanding (Rp M)</div>
        <div class="nscr" style="max-height:130px">
          <table class="nt">
            <thead><tr><th>#</th><th>Nama AO</th><th style="text-align:right">Kontrak</th><th style="text-align:right">Outstanding</th><th style="text-align:right">NPF%</th></tr></thead>
            <tbody>
              @forelse($topAOLending as $i=>$ao)
              <tr>
                <td style="color:var(--dim);font-weight:700">{{ $i+1 }}</td>
                <td style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:110px">{{ $ao['nmao']??'-' }}</td>
                <td style="text-align:right;color:var(--cyan)">{{ number_format($ao['total_nasabah']) }}</td>
                <td style="text-align:right;font-variant-numeric:tabular-nums;color:var(--green)">Rp {{ number_format($ao['total_outstanding']/1e9,2) }}M</td>
                <td style="text-align:right;color:{{ $ao['npf_ratio']>5?'var(--red)':($ao['npf_ratio']>2?'var(--orange)':'var(--green)') }}">{{ $ao['npf_ratio'] }}%</td>
              </tr>
              @empty
              <tr><td colspan="5" style="text-align:center;color:var(--dim);padding:8px">Tidak ada data</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      {{-- Row 2: Trend AO Lending per bulan --}}
      <div class="gc cc" style="flex:1;min-height:0">
        <div class="cct"><i class="ti ti-chart-line" style="color:var(--cyan)"></i> Trend AO Lending &mdash; Outstanding per Bulan (Rp M)</div>
        <div class="ca" id="ch-ao-lend-trend"></div>
      </div>

      {{-- Row 3: Distribusi Segmentasi Pembiayaan Trend --}}
      <div class="gc cc" style="flex:1;min-height:0">
        <div class="cct"><i class="ti ti-chart-area" style="color:var(--purple)"></i> Trend Segmentasi Pembiayaan per Bulan (Rp M)</div>
        <div class="ca" id="ch-seg-trend"></div>
      </div>

    </div>

    {{-- ═══════════ FUNDING COLUMN (RIGHT) ═══════════ --}}
    <div style="display:flex;flex-direction:column;gap:8px;min-height:0">

      {{-- Row 1: Top AO Funding --}}
      <div class="gc cc" style="flex:0 0 auto">
        <div class="cct"><i class="ti ti-trophy" style="color:var(--gold)"></i> Top AO Funding &mdash; Total Deposito</div>
        <div class="nscr" style="max-height:130px">
          <table class="nt">
            <thead><tr><th>#</th><th>Nama AO</th><th style="text-align:right">Bilyet</th><th style="text-align:right">Nominal</th></tr></thead>
            <tbody>
              @forelse($topAOFunding as $i=>$ao)
              <tr>
                <td style="color:var(--dim);font-weight:700">{{ $i+1 }}</td>
                <td style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:110px">{{ $ao['nmao']??'-' }}</td>
                <td style="text-align:right;color:var(--cyan)">{{ number_format($ao['total_bilyet']) }}</td>
                <td style="text-align:right;font-variant-numeric:tabular-nums;color:var(--orange)">Rp {{ number_format($ao['total_funding']/1e9,2) }}M</td>
              </tr>
              @empty
              <tr><td colspan="4" style="text-align:center;color:var(--dim);padding:8px">Tidak ada data</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      {{-- Row 2: Trend AO Funding per bulan --}}
      <div class="gc cc" style="flex:1;min-height:0">
        <div class="cct"><i class="ti ti-chart-line" style="color:var(--orange)"></i> Trend AO Funding &mdash; Nominal per Bulan (Rp M)</div>
        <div class="ca" id="ch-ao-fund-trend"></div>
      </div>

      {{-- Row 3: Produk Funding Trend --}}
      <div class="gc cc" style="flex:1;min-height:0">
        <div class="cct"><i class="ti ti-chart-bar" style="color:var(--teal)"></i> Trend Produk Funding per Bulan (Rp M)</div>
        <div class="ca" id="ch-fund-produk-trend"></div>
      </div>

    </div>
  </div>
</div>


</div>{{-- end #sw --}}
@endsection

@section('scripts')
<script>
// ── Data ─────────────────────────────────────────────────────────────────
const TL  = @json($monthlyTrends['labels']);
const TP  = @json($monthlyTrends['plafon']);
const TOS = @json($monthlyTrends['lending']);
const TF  = @json($monthlyTrends['funding']);
const TN  = @json($monthlyTrends['npf_ratio']);
const TK  = @json($monthlyTrends['kontrak']);
const KV  = @json($kolDistrib);
const KC  = @json($kolCount);
const FHT = @json($fhTrends);
const TV  = {{ $totalTabungan }};
const DV  = {{ $totalDeposito }};
const TAB_PROD = @json($topTabunganProducts);
const DEP_PROD = @json($topDepositoProducts);
const LEN_PROD = @json($topProducts);
const AO_LEND  = @json($topAOLending);
const TPL  = @json($monthlyTrends['pelunasan_cepat']);       // Pelunasan cepat plafon Rp M
const TPLC = @json($monthlyTrends['pelunasan_cepat_count']); // Pelunasan cepat jumlah kontrak
const TDJ  = @json($monthlyTrends['deposito_jatuh_tempo']);  // Deposito jatuh tempo Rp M
const TDJC = @json($monthlyTrends['deposito_jatuh_tempo_count']); // Deposito jatuh tempo jumlah bilyet
const TDP  = @json($monthlyTrends['deposito_pencairan']);       // Pencairan deposito Rp M (nobilyet comparison)
const TDPC = @json($monthlyTrends['deposito_pencairan_count']); // Pencairan deposito jumlah bilyet
const TTAB = @json($monthlyTrends['tabungan']);  // Tabungan per bulan Rp M
const TDEP = @json($monthlyTrends['deposito']); // Deposito per bulan Rp M
const PENCAIRAN_TOTAL  = {{ $totalPencairan }};
const PENCAIRAN_JUMLAH = {{ $jumlahPencairan }};
const SEG    = @json($segmentasiDistrib);
const SEBARAN= @json($sebaranNasabah);
const AO_FUND = @json($topAOFunding);
const AO_LEND_TREND  = @json($aoLendingTrend);
const AO_FUND_TREND  = @json($aoFundingTrend);
const SEG_TREND      = @json($segmentasiTrend);
const FUND_PRODUK_TREND = @json($fundingProdukTrend);
const TKC1 = @json($monthlyTrends['kol_count_1']); // Tren kontrak KOL 1
const TKC2 = @json($monthlyTrends['kol_count_2']); // Tren kontrak KOL 2
const TKC3 = @json($monthlyTrends['kol_count_3']); // Tren kontrak KOL 3
const TKC4 = @json($monthlyTrends['kol_count_4']); // Tren kontrak KOL 4
const TKC5 = @json($monthlyTrends['kol_count_5']); // Tren kontrak KOL 5

// ── Clock ─────────────────────────────────────────────────────────────────
function tick(){
  const n=new Date();
  document.getElementById('clock').textContent=n.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
  document.getElementById('dlbl').textContent=n.toLocaleDateString('id-ID',{weekday:'long',day:'numeric',month:'long',year:'numeric'});
}
tick();setInterval(tick,1000);

// ── ApexCharts base config ────────────────────────────────────────────────
const AP={theme:{mode:'light'},chart:{background:'transparent',toolbar:{show:false},animations:{enabled:true,speed:600}},grid:{borderColor:'#dde0ef',strokeDashArray:4,padding:{top:0,right:10,bottom:20,left:10}},tooltip:{theme:'light',style:{fontSize:'12px'}},dataLabels:{enabled:false},legend:{labels:{colors:'#6c7293'},fontSize:'11px'},stroke:{curve:'smooth',width:2}};

// ── Lazy chart init ─────────────────────────────────────────────────────
let _leafletMap=null;
const _chartDone=[false,false,false,false,false,false,false];
function _initCharts(idx){
  if(_chartDone[idx])return;
  _chartDone[idx]=true;

  // ── Slide 0: Financial Highlights trend ──────────────────────────────
  if(idx===0){
    try{(function(){const el=document.getElementById('fh-trend-chart');if(!el)return;
    if(!FHT||!FHT.labels||FHT.labels.length<2)return;
    const nz=v=>v===0?null:v;
    new ApexCharts(el,{...AP,chart:{...AP.chart,type:'line',height:'100%'},
    series:[{name:'CAR (%)',data:(FHT.car||[]).map(nz)},{name:'ROA (%)',data:(FHT.roa||[]).map(nz)},{name:'ROE (%)',data:(FHT.roe||[]).map(nz)},{name:'FDR (%)',data:(FHT.fdr||[]).map(nz)},{name:'NPF (%)',data:(FHT.npf||[]).map(nz)},{name:'BOPO (%)',data:(FHT.bopo||[]).map(nz)}],
    xaxis:{categories:FHT.labels,labels:{style:{colors:'#6c7293',fontSize:'10px'},rotate:-30}},
    yaxis:{labels:{style:{colors:'#6c7293',fontSize:'11px'},formatter:v=>v!=null?v.toFixed(2)+'%':''}},
    colors:['#e63946','#71dd37','#696cff','#03c3ec','#ff3e1d','#ff9f43'],
    markers:{size:5,hover:{size:7}},stroke:{curve:'smooth',width:2},
    dataLabels:{enabled:true,formatter:v=>v!=null?v.toFixed(1)+'%':'',style:{fontSize:'9px',fontWeight:700,colors:['#e63946','#3daa28','#696cff','#03a0c5','#d42f10','#cc7800']},background:{enabled:true,foreColor:'#fff',padding:2,borderRadius:2,borderWidth:0,opacity:0.82},offsetY:-7},
    legend:{...AP.legend,position:'top',fontSize:'10px'},
    tooltip:{y:{formatter:v=>v!=null?v.toFixed(2)+'%':'N/A'}},
    }).render();})();}catch(e){console.warn('fh-trend',e);}

    try{(function(){const el=document.getElementById('fh-nom-chart');if(!el)return;
    if(!FHT||!FHT.labels||FHT.labels.length<2)return;
    const nz=v=>v===0?null:v;
    new ApexCharts(el,{...AP,chart:{...AP.chart,type:'line',height:'100%'},
    series:[
      {name:'Aset (Rp M)',       data:(FHT.aset||[]).map(nz)},
      {name:'DPK (Rp M)',        data:(FHT.dpk||[]).map(nz)},
      {name:'Pembiayaan (M)',    data:(FHT.pembiayaan||[]).map(nz)},
      {name:'Pendapatan (M)',    data:(FHT.pendapatan||[]).map(nz)},
      {name:'Biaya (M)',         data:(FHT.biaya||[]).map(nz)},
      {name:'Laba/Rugi (M)',     data:(FHT.laba_rugi||[]).map(nz)},
    ],
    xaxis:{categories:FHT.labels,labels:{style:{colors:'#6c7293',fontSize:'10px'},rotate:-30}},
    yaxis:{labels:{style:{colors:'#6c7293',fontSize:'11px'},formatter:v=>v!=null?(v>=1000?(v/1000).toFixed(2)+'T':v.toFixed(1)+'M'):''}},
    colors:['#e63946','#03c3ec','#71dd37','#8a2be2','#ff9f43','#e91e8c'],
    markers:{size:5,hover:{size:7}},stroke:{curve:'smooth',width:2},
    dataLabels:{enabled:true,formatter:v=>v!=null?(v>=1000?(v/1000).toFixed(2)+'T':v.toFixed(1)+'M'):'',style:{fontSize:'9px',fontWeight:700,colors:['#e63946','#03a0c5','#3daa28','#7020c0','#cc7800','#b0115a']},background:{enabled:true,foreColor:'#fff',padding:2,borderRadius:2,borderWidth:0,opacity:0.82},offsetY:-7},
    legend:{...AP.legend,position:'top',fontSize:'10px'},
    tooltip:{y:{formatter:v=>v!=null?(v>=1000?'Rp '+(v/1000).toFixed(3)+' T':'Rp '+v.toFixed(2)+' M'):'N/A'}},
    }).render();})();}catch(e){console.warn('fh-nom',e);}
  }

  // ── Slide 1: DPK trend + Pie + Top Tabungan bar ───────────────────────
  if(idx===1){
    try{(function(){const el=document.getElementById('ch-dpk-trend');if(!el)return;
    new ApexCharts(el,{...AP,chart:{...AP.chart,type:'line',height:'100%'},
    series:[
      {name:'Total DPK (Rp M)',   data:TF,   type:'area'},
      {name:'Tabungan (Rp M)',    data:TTAB, type:'line'},
      {name:'Deposito (Rp M)',    data:TDEP, type:'line'},
      {name:'Pencairan Dep (Rp M)',data:TDP, type:'bar'},
    ],
    xaxis:{categories:TL,labels:{style:{colors:'#6c7293',fontSize:'10px'},rotate:-30}},
    yaxis:{labels:{style:{colors:'#6c7293',fontSize:'11px'},formatter:v=>v>=1000?(v/1000).toFixed(1)+'T':v.toFixed(1)+'M'}},
    colors:['#03c3ec','#71dd37','#696cff','#ff9f43'],
    fill:{type:['gradient','solid','solid','solid'],gradient:{opacityFrom:.35,opacityTo:.02}},
    markers:{size:[4,4,4,0],hover:{size:6}},
    stroke:{curve:'smooth',width:[2,2,2,0]},
    plotOptions:{bar:{borderRadius:3,columnWidth:'40%',dataLabels:{position:'top'}}},
    dataLabels:{
      enabled:true,
      enabledOnSeries:[0,1,2,3],
      formatter:(v,o)=>v!=null&&v>0?(v>=1000?(v/1000).toFixed(2)+'T':v.toFixed(1)+'M'):'',
      style:{fontSize:'9px',fontWeight:700,colors:['#0095b6','#3daa28','#5558d4','#cc7800']},
      background:{enabled:true,foreColor:'#fff',padding:2,borderRadius:2,borderWidth:0,opacity:0.82},
      offsetY:-7,
    },
    legend:{...AP.legend,position:'top'},
    tooltip:{shared:true,intersect:false,y:{formatter:(v,{seriesIndex,dataPointIndex})=>seriesIndex===3?'Rp '+v.toFixed(2)+' M ('+TDPC[dataPointIndex]+' bilyet)':'Rp '+v.toFixed(2)+' M'}},
    }).render();})();}catch(e){console.warn('ch-dpk-trend',e);}

    try{(function(){const el=document.getElementById('ch-dpk-pie');if(!el)return;
    new ApexCharts(el,{...AP,chart:{...AP.chart,type:'donut',height:'100%'},
    series:[parseFloat((TV/1e9).toFixed(2)),parseFloat((DV/1e9).toFixed(2))],
    labels:['Tabungan','Deposito'],colors:['#03c3ec','#ff9f43'],
    plotOptions:{pie:{donut:{size:'68%',labels:{show:true,total:{show:true,label:'Total DPK',color:'#6c7293',formatter:w=>'Rp '+(w.globals.seriesTotals.reduce((a,b)=>a+b,0)).toFixed(1)+' M'}}}}},
    legend:{...AP.legend,position:'bottom',fontSize:'10px'},
    dataLabels:{enabled:true,formatter:v=>v.toFixed(0)+'%',style:{fontSize:'10px'}},
    tooltip:{y:{formatter:v=>'Rp '+v.toFixed(2)+' M'}},
    }).render();})();}catch(e){console.warn('ch-dpk-pie',e);}

    try{(function(){const el=document.getElementById('ch-tab-bar');if(!el)return;
    if(!TAB_PROD||!TAB_PROD.length)return;
    const cats=TAB_PROD.map(t=>t.nama_produk||'Produk');
    const vals=TAB_PROD.map(t=>parseFloat((t.total_nominal/1e9).toFixed(2)));
    const cnts=TAB_PROD.map(t=>t.jumlah_rekening||0);
    new ApexCharts(el,{...AP,chart:{...AP.chart,type:'bar',height:'100%'},
    stroke:{show:false},
    series:[{name:'Nominal (Rp M)',data:vals}],
    xaxis:{categories:cats,labels:{style:{colors:'#6c7293',fontSize:'8.5px'},rotate:-30,rotateAlways:false,trim:true,maxHeight:60}},
    yaxis:{labels:{style:{colors:'#6c7293',fontSize:'10px'},formatter:v=>v.toFixed(1)+'M'}},
    colors:['#03c3ec'],
    plotOptions:{bar:{borderRadius:5,columnWidth:'60%',dataLabels:{position:'top'}}},
    dataLabels:{enabled:true,offsetY:-16,style:{fontSize:'9px',fontWeight:700,colors:['#6c7293']},formatter:v=>'Rp '+v.toFixed(1)+'M'},
    tooltip:{y:{formatter:(v,{dataPointIndex})=>'Rp '+v.toFixed(2)+' M ('+cnts[dataPointIndex].toLocaleString()+' rek)'}},
    }).render();})();}catch(e){console.warn('ch-tab-bar',e);}

    try{(function(){const el=document.getElementById('ch-dep-bar');if(!el)return;
    if(!DEP_PROD||!DEP_PROD.length)return;
    const cats=DEP_PROD.map(t=>t.nama_produk||'Produk');
    const vals=DEP_PROD.map(t=>parseFloat((t.total_nominal/1e9).toFixed(2)));
    new ApexCharts(el,{...AP,chart:{...AP.chart,type:'bar',height:'100%'},
    stroke:{show:false},
    series:[{name:'Nominal (Rp M)',data:vals}],
    xaxis:{categories:cats,labels:{style:{colors:'#6c7293',fontSize:'11px'}}},
    yaxis:{labels:{style:{colors:'#6c7293',fontSize:'10px'},formatter:v=>v.toFixed(1)+'M'}},
    colors:['#ff9f43'],
    plotOptions:{bar:{horizontal:false,borderRadius:6,columnWidth:'50%',dataLabels:{position:'top'}}},
    dataLabels:{enabled:true,offsetY:-18,style:{fontSize:'10px',fontWeight:700,colors:['#6c7293']},formatter:v=>'Rp '+v.toFixed(1)+'M'},
    tooltip:{y:{formatter:v=>'Rp '+v.toFixed(2)+' M'}},
    }).render();})();}catch(e){console.warn('ch-dep-bar',e);}
  }

  // ── Slide 2: Lending trend + Top Produk bar ───────────────────────────
  if(idx===2){
    try{(function(){const el=document.getElementById('ch-lend-trend');if(!el)return;
    new ApexCharts(el,{...AP,chart:{...AP.chart,type:'line',height:'100%',foreColor:'#2c3050'},
    series:[{name:'Plafon (Rp M)',data:TP,type:'area'},{name:'Outstanding (Rp M)',data:TOS,type:'area'},{name:'Pelunasan Cepat (Rp M)',data:TPL,type:'bar'}],
    xaxis:{categories:TL,labels:{style:{colors:'#6c7293',fontSize:'10px'},rotate:-30}},
    yaxis:{labels:{style:{colors:'#6c7293',fontSize:'11px'},formatter:v=>v>=1000?(v/1000).toFixed(1)+'T':v.toFixed(1)+'M'}},
    colors:['#e63946','#696cff','#3a9a0a'],
    fill:{type:['gradient','gradient','solid'],gradient:{opacityFrom:.35,opacityTo:.03}},
    markers:{size:[4,4,0],hover:{size:6}},
    stroke:{curve:'smooth',width:[2.5,2.5,0]},
    plotOptions:{bar:{borderRadius:3,columnWidth:'35%',dataLabels:{position:'top'}}},
    dataLabels:{enabled:true,formatter:(v,o)=>o.seriesIndex===2?(v>0?v.toFixed(1)+'M':''):(v!=null?v.toFixed(1)+'M':''),style:{fontSize:'9px',fontWeight:700,colors:['#b01c24','#4b4ec7','#267a00']},background:{enabled:true,foreColor:'#f4f5fb',opacity:0.85,borderRadius:3,borderWidth:0,padding:2},offsetY:-6},
    legend:{...AP.legend,position:'top'},
    tooltip:{shared:true,intersect:false,y:{formatter:(v,{seriesIndex,dataPointIndex})=>seriesIndex===2?'Rp '+v.toFixed(2)+' M ('+TPLC[dataPointIndex]+' kontrak)':'Rp '+v.toFixed(2)+' M'}},
    }).render();})();}catch(e){console.warn('ch-lend-trend',e);}

    try{(function(){const el=document.getElementById('ch-produk-bar');if(!el)return;
    if(!LEN_PROD||!LEN_PROD.length)return;
    const cats=LEN_PROD.map(p=>p.nama_produk||'Produk');
    const vals=LEN_PROD.map(p=>parseFloat((p.total_outstanding/1e9).toFixed(1)));
    const kontrak=LEN_PROD.map(p=>p.total_kontrak||0);
    new ApexCharts(el,{...AP,chart:{...AP.chart,type:'bar',height:'100%'},
    stroke:{show:false},
    series:[{name:'Outstanding (Rp M)',data:vals},{name:'Jumlah Nasabah',data:kontrak}],
    xaxis:{categories:cats,labels:{style:{colors:'#6c7293',fontSize:'9px'},rotate:-30,rotateAlways:false}},
    yaxis:[
      {seriesName:'Outstanding (Rp M)',labels:{style:{colors:'#6c7293',fontSize:'10px'},formatter:v=>v.toFixed(0)+'M'}},
      {seriesName:'Jumlah Nasabah',opposite:true,labels:{style:{colors:'#6c7293',fontSize:'10px'},formatter:v=>Math.round(v).toLocaleString('id-ID')}}
    ],
    colors:['#71dd37','#696cff'],
    plotOptions:{bar:{borderRadius:4,columnWidth:'70%',dataLabels:{position:'top'},grouped:true}},
    dataLabels:{enabled:true,offsetY:-14,style:{fontSize:'8px',fontWeight:700,colors:['#5aaa2a','#4b4ec7']},
      formatter:(v,o)=>o.seriesIndex===0?v.toFixed(0)+'M':Math.round(v).toLocaleString('id-ID')},
    legend:{...AP.legend,show:true,position:'top'},
    tooltip:{shared:true,intersect:false,y:[
      {formatter:v=>'Rp '+v.toFixed(2)+' M'},
      {formatter:v=>Math.round(v).toLocaleString('id-ID')+' nasabah'}
    ]},
    }).render();})();}catch(e){console.warn('ch-produk-bar',e);}
  }

  // ── Slide 4: Kualitas NPF (Kolektibilitas donut + bar count) ───────────
  if(idx===4){
    try{(function(){const el=document.getElementById('ch-kol');if(!el)return;
    const vals=[KV['1'],KV['2'],KV['3'],KV['4'],KV['5']].map(v=>parseFloat(((v||0)/1e9).toFixed(2)));
    new ApexCharts(el,{...AP,chart:{...AP.chart,type:'donut',height:'100%'},
    series:vals,labels:['Lancar (1)','Dlm Perhatian (2)','Kur. Lancar (3)','Diragukan (4)','Macet (5)'],
    colors:['#71dd37','#03c3ec','#ffab00','#ff9f43','#ff3e1d'],
    plotOptions:{pie:{donut:{size:'65%',labels:{show:true,total:{show:true,label:'Total OS',color:'#6c7293',formatter:w=>{const s=w.globals.seriesTotals.reduce((a,b)=>a+b,0);return s.toFixed(2)+' M';}}}}}},
    legend:{...AP.legend,position:'bottom',fontSize:'10px'},
    dataLabels:{enabled:true,formatter:(v,o)=>o.w.globals.series[o.seriesIndex].toFixed(1)+'M',style:{fontSize:'10px'}},
    tooltip:{y:{formatter:v=>'Rp '+v.toFixed(2)+' M'}},
    }).render();})();}catch(e){console.warn('ch-kol',e);}

    try{(function(){const el=document.getElementById('ch-kol-cnt');if(!el)return;
    new ApexCharts(el,{...AP,chart:{...AP.chart,type:'line',height:'100%'},
    stroke:{curve:'smooth',width:[2.5,2.5,2.5,2.5,2.5]},
    series:[
      {name:'Lancar (1)',       data:TKC1},
      {name:'Dlm Perhatian (2)',data:TKC2},
      {name:'Kur. Lancar (3)', data:TKC3},
      {name:'Diragukan (4)',   data:TKC4},
      {name:'Macet (5)',       data:TKC5},
    ],
    xaxis:{categories:TL,labels:{style:{colors:'#6c7293',fontSize:'9px'},rotate:-30,rotateAlways:false}},
    yaxis:{labels:{style:{colors:'#6c7293',fontSize:'10px'}}},
    colors:['#00b894','#0984e3','#e17055','#6c5ce7','#d63031'],
    markers:{size:4,hover:{size:6}},
    legend:{...AP.legend,position:'top',fontSize:'10px'},
    dataLabels:{enabled:true,formatter:v=>v,style:{fontSize:'9px',fontWeight:700,colors:['#00b894','#0984e3','#e17055','#6c5ce7','#d63031']},background:{enabled:false},offsetY:-6},
    tooltip:{y:{formatter:v=>v+' kontrak'},shared:true,intersect:false},
    }).render();})();}catch(e){console.warn('ch-kol-cnt',e);}
  }

  // ── Slide 6: Performa AO ─────────────────────────────────────────────
  if(idx===6){
    // Row 2 Left: Trend AO Lending per bulan
    try{(function(){
      const el=document.getElementById('ch-ao-lend-trend');if(!el)return;
      if(!AO_LEND_TREND||!AO_LEND_TREND.datasets||!AO_LEND_TREND.datasets.length)return;
      const series=AO_LEND_TREND.datasets.map(d=>({name:d.label,data:d.data,color:d.color}));
      new ApexCharts(el,{...AP,chart:{...AP.chart,type:'line',height:'100%'},
      stroke:{curve:'smooth',width:2},
      series:series,
      xaxis:{categories:AO_LEND_TREND.labels,labels:{rotate:-35,style:{colors:'#6c7293',fontSize:'8px'}}},
      yaxis:{labels:{style:{colors:'#6c7293',fontSize:'10px'},formatter:v=>v.toFixed(1)+'M'}},
      legend:{show:true,fontSize:'9px',labels:{colors:'#c0c0c0'}},
      markers:{size:4,hover:{size:6}},
      dataLabels:{enabled:true,formatter:v=>v>0?v.toFixed(1)+'M':'',style:{fontSize:'8px',fontWeight:700},background:{enabled:true,foreColor:'#fff',padding:2,borderRadius:2,borderWidth:0,opacity:0.8},offsetY:-8},
      tooltip:{shared:true,y:{formatter:v=>'Rp '+v.toFixed(2)+' M'}},
      }).render();
    })();}catch(e){console.warn('ch-ao-lend-trend',e);}

    // Row 2 Right: Trend AO Funding per bulan
    try{(function(){
      const el=document.getElementById('ch-ao-fund-trend');if(!el)return;
      if(!AO_FUND_TREND||!AO_FUND_TREND.datasets||!AO_FUND_TREND.datasets.length)return;
      const series=AO_FUND_TREND.datasets.map(d=>({name:d.label,data:d.data,color:d.color}));
      new ApexCharts(el,{...AP,chart:{...AP.chart,type:'line',height:'100%'},
      stroke:{curve:'smooth',width:2},
      series:series,
      xaxis:{categories:AO_FUND_TREND.labels,labels:{rotate:-35,style:{colors:'#6c7293',fontSize:'8px'}}},
      yaxis:{labels:{style:{colors:'#6c7293',fontSize:'10px'},formatter:v=>v.toFixed(1)+'M'}},
      legend:{show:true,fontSize:'9px',labels:{colors:'#c0c0c0'}},
      markers:{size:4,hover:{size:6}},
      dataLabels:{enabled:true,formatter:v=>v>0?v.toFixed(1)+'M':'',style:{fontSize:'8px',fontWeight:700},background:{enabled:true,foreColor:'#fff',padding:2,borderRadius:2,borderWidth:0,opacity:0.8},offsetY:-8},
      tooltip:{shared:true,y:{formatter:v=>'Rp '+v.toFixed(2)+' M'}},
      }).render();
    })();}catch(e){console.warn('ch-ao-fund-trend',e);}

    // Row 3 Left: Trend Segmentasi Pembiayaan per bulan
    try{(function(){
      const el=document.getElementById('ch-seg-trend');if(!el)return;
      if(!SEG_TREND||!SEG_TREND.datasets||!SEG_TREND.datasets.length)return;
      const series=SEG_TREND.datasets.map(d=>({name:d.label,data:d.data,color:d.color}));
      new ApexCharts(el,{...AP,chart:{...AP.chart,type:'area',height:'100%'},
      stroke:{curve:'smooth',width:2},
      fill:{type:'gradient',gradient:{opacityFrom:.35,opacityTo:.05}},
      series:series,
      xaxis:{categories:SEG_TREND.labels,labels:{rotate:-35,style:{colors:'#6c7293',fontSize:'8px'}}},
      yaxis:{labels:{style:{colors:'#6c7293',fontSize:'10px'},formatter:v=>v.toFixed(1)+'M'}},
      legend:{show:true,fontSize:'9px',labels:{colors:'#c0c0c0'}},
      markers:{size:4,hover:{size:6}},
      dataLabels:{enabled:true,formatter:v=>v>0?v.toFixed(1)+'M':'',style:{fontSize:'8px',fontWeight:700},background:{enabled:true,foreColor:'#fff',padding:2,borderRadius:2,borderWidth:0,opacity:0.8},offsetY:-8},
      tooltip:{shared:true,y:{formatter:v=>'Rp '+v.toFixed(2)+' M'}},
      }).render();
    })();}catch(e){console.warn('ch-seg-trend',e);}

    // Row 3 Right: Trend Produk Funding per bulan (Tabungan vs Deposito)
    try{(function(){
      const el=document.getElementById('ch-fund-produk-trend');if(!el)return;
      if(!FUND_PRODUK_TREND||!FUND_PRODUK_TREND.labels||!FUND_PRODUK_TREND.labels.length)return;
      new ApexCharts(el,{...AP,chart:{...AP.chart,type:'bar',height:'100%'},
      stroke:{show:false},
      colors:['#4dd0e1','#ffb74d'],
      series:[
        {name:'Tabungan',data:FUND_PRODUK_TREND.tabungan},
        {name:'Deposito',data:FUND_PRODUK_TREND.deposito},
      ],
      xaxis:{categories:FUND_PRODUK_TREND.labels,labels:{rotate:-35,style:{colors:'#6c7293',fontSize:'8px'}}},
      yaxis:{labels:{style:{colors:'#6c7293',fontSize:'10px'},formatter:v=>v!=null?v.toFixed(1)+'M':'0M'}},
      plotOptions:{bar:{borderRadius:2,columnWidth:'65%',dataLabels:{position:'top'}}},
      dataLabels:{enabled:true,offsetY:-14,formatter:v=>v!=null&&v>0?v.toFixed(1)+'M':'',style:{fontSize:'8px',fontWeight:700,colors:['#222']}},
      legend:{show:true,fontSize:'9px',labels:{colors:'#c0c0c0'}},
      tooltip:{shared:true,intersect:false,y:{formatter:v=>v!=null?'Rp '+v.toFixed(2)+' M':''}},
      }).render();
    })();}catch(e){console.warn('ch-fund-produk-trend',e);}
  }

  // ── Slide 3: Segmentasi Pembiayaan ───────────────────────────────────────
  if(idx===3){
    try{(function(){const el=document.getElementById('ch-seg-bar');if(!el)return;
    if(!SEG||!SEG.length)return;
    const cats=SEG.map(s=>s.nama||s.kdprd||'?');
    const vals=SEG.map(s=>parseFloat(((s.outstanding||0)/1e9).toFixed(2)));
    const cnts=SEG.map(s=>parseInt(s.jumlah)||0);
    new ApexCharts(el,{...AP,chart:{...AP.chart,type:'bar',height:'100%'},
    stroke:{show:false},
    series:[{name:'Outstanding (Rp M)',data:vals}],
    xaxis:{categories:cats,labels:{style:{colors:'#6c7293',fontSize:'10px'}}},
    yaxis:{labels:{style:{colors:'#6c7293',fontSize:'10px'},formatter:v=>v.toFixed(1)+'M'}},
    colors:['#696cff'],
    plotOptions:{bar:{horizontal:true,borderRadius:4,barHeight:'55%',dataLabels:{position:'center'}}},
    dataLabels:{enabled:true,style:{fontSize:'9px',fontWeight:700,colors:['#fff']},formatter:v=>'Rp '+v.toFixed(1)+'M'},
    legend:{show:false},
    tooltip:{y:{formatter:(v,{dataPointIndex})=>'Rp '+v.toFixed(2)+' M ('+cnts[dataPointIndex].toLocaleString('id-ID')+' kontrak)'}},
    }).render();})();}catch(e){console.warn('ch-seg-bar',e);}
  }

  // ── Slide 5: Peta Sebaran Nasabah ───────────────────────────────────
  if(idx===5){
    try{(function(){
      const el=document.getElementById('map-nasabah');if(!el)return;
      if(!SEBARAN||!SEBARAN.length)return;
      if(typeof L==='undefined'){setTimeout(()=>_initCharts(5),300);_chartDone[5]=false;return;}
      if(_leafletMap){setTimeout(()=>_leafletMap.invalidateSize(),100);return;}
      // Kecamatan coordinates – Jawa Barat
      const COORDS={
        // BOGOR Kabupaten
        'CIBINONG':[-6.481,106.854],'CILEUNGSI':[-6.475,107.008],'GUNUNG PUTRI':[-6.455,106.970],
        'GUNUNGPUTRI':[-6.455,106.970],'JONGGOL':[-6.513,107.052],'LEUWILIANG':[-6.596,106.615],
        'LEUWI LIANG':[-6.596,106.615],'PAMIJAHAN':[-6.646,106.603],'JASINGA':[-6.500,106.438],
        'CIGUDEG':[-6.495,106.358],'CITEUREUP':[-6.450,106.935],'CITEREUP':[-6.450,106.935],
        'TAMANSARI':[-6.638,106.810],'CIOMAS':[-6.604,106.803],'CIAMPEA':[-6.578,106.717],
        'CIBUNGBULANG':[-6.600,106.680],'CIGOMBONG':[-6.731,106.797],'RUMPUN':[-6.380,106.520],
        'RUMPIN':[-6.388,106.521],'CIAWI':[-6.697,106.903],'BOJONG GEDE':[-6.467,106.810],
        'BOJONGGEDE':[-6.467,106.810],'BOJONG BESAR':[-6.467,106.810],'CARIU':[-6.509,107.167],
        'CISARUA':[-6.696,106.960],'SUKARAJA':[-6.590,106.843],'CISEENG':[-6.447,106.715],
        'DRAMAGA':[-6.558,106.722],'TENJO':[-6.404,106.436],'MEGAMENDUNG':[-6.715,106.937],
        'MEGAMEDUUNG':[-6.715,106.937],'PARUNG PANJANG':[-6.374,106.506],'PARUNGPANJANG':[-6.374,106.506],
        'PARAUNGPANJANG':[-6.374,106.506],'PARUNG PANJNG':[-6.374,106.506],
        'GUNUNG SINDUR':[-6.400,106.680],'GUNUNGSINDUR':[-6.400,106.680],
        'CARINGIN':[-6.720,106.822],'CARINGN':[-6.720,106.822],'SUKAMAKMUR':[-6.540,107.138],
        'KEMANG':[-6.505,106.762],'PARUNG':[-6.429,106.700],'LEUWISADENG':[-6.575,106.591],
        'TAJURHALANG':[-6.432,106.771],'TAJUR HALANG':[-6.432,106.771],
        'NANGGUNG':[-6.618,106.530],'KLAPANUNGGAL':[-6.500,107.052],'KELAPA NUNGGAL':[-6.500,107.052],
        'KELAPA NUNGGAL/KLAPA NUNGGAL':[-6.500,107.052],'KLPANUNGGAL':[-6.500,107.052],'KELAPANUNGGAL':[-6.500,107.052],
        'SUKAJAYA':[-6.541,106.418],'CIJERUK':[-6.683,106.817],'TANJUNGSARI':[-6.560,107.075],
        'BABAKAN MADANG':[-6.455,106.908],'BABAKANNMADANG':[-6.455,106.908],'TENJOLAYA':[-6.637,106.672],
        'RANCABUNGUR':[-6.448,106.730],'SETU':[-6.352,107.062],
        // BOGOR Kota
        'BOGOR BARAT':[-6.597,106.762],'BOGOR BARAT - KOTA':[-6.597,106.762],
        'BOGOR SELATAN':[-6.642,106.802],'BOGOR SELATAN - KOTA':[-6.642,106.802],
        'BOGOR TIMUR':[-6.591,106.836],'BOGOR TIMUR - KOTA':[-6.591,106.836],
        'BOGOR TENGAH':[-6.600,106.803],'BOGOR TENGAH - KOTA':[-6.600,106.803],
        'BOGOR UTARA':[-6.572,106.793],'BOGOR UTARA - KOTA':[-6.572,106.793],
        'KOTA BOGOR UTARA':[-6.572,106.793],'KOTA BOGOR BARAT':[-6.597,106.762],
        'TANAH SAREAL':[-6.578,106.783],'TANAH SEREAL':[-6.578,106.783],
        'BOGOR':[-6.597,106.806],'PANCORAN MAS':[-6.400,106.820],
        // DEPOK
        'CILODONG':[-6.400,106.848],'BOJONGSAR':[-6.436,106.764],'BOJONG SARI':[-6.436,106.764],
        'BOJONGSARI':[-6.436,106.764],'TAPOS':[-6.377,106.870],'CIPAYUNG':[-6.408,106.876],
        'SAWANGAN':[-6.428,106.744],'LIMO':[-6.396,106.772],'BEJI':[-6.383,106.822],
        'SUKMAJAYA':[-6.375,106.836],'SUKMA JAYA':[-6.375,106.836],'CIMANGGIS':[-6.377,106.873],
        // BEKASI Kabupaten
        'SETU':[-6.352,107.062],'CIBARUSAH':[-6.393,107.139],'JATI ASIH':[-6.300,107.013],
        'JATIASIH':[-6.300,107.013],'TAMBUN SELATAN':[-6.261,107.028],'BANTARGEBANG':[-6.333,107.020],
        'BANTAR GEBANG':[-6.333,107.020],'JATISAMPURNA':[-6.310,107.061],'JATI SAMPURNA':[-6.310,107.061],
        'MUSTIKA JAYA':[-6.293,107.044],'BOJONGMANGU':[-6.415,107.184],'CIKARCANG SELATAN':[-6.345,107.152],
        'CIKARANG SELATAN':[-6.345,107.152],'TARUMAJAYA':[-6.119,107.102],'CIBITUNG':[-6.275,107.100],
        'RAWALUMBU':[-6.293,107.009],'PONDOKGEDE':[-6.278,106.979],'PONDOK GEDE':[-6.278,106.979],
        'PONDOKMELATI':[-6.290,106.995],'PONDOK MELATI':[-6.290,106.995],
        'BEKASI TIMUR':[-6.266,107.010],'BEKASI UTARA':[-6.240,107.000],'BABELAN':[-6.173,107.047],
        'MEDAN SATRIA':[-6.220,106.993],'JATISARI':[-6.385,107.204],
        // BANDUNG Kota
        'ARCAMANIK':[-6.922,107.671],'UJUNG BERUNG':[-6.909,107.714],'CANGKUANG':[-6.973,107.649],
        'KIARA CONDONG':[-6.936,107.654],'KIARACONDONG':[-6.936,107.654],'CIBLENUK':[-6.905,107.582],
        'COBLONG':[-6.892,107.608],
        // BANDUNG BARAT
        'NGAMPRAH':[-6.841,107.512],'CIPATAN':[-6.840,107.380],'CIPATAT':[-6.840,107.380],
        // GARUT
        'KARANGPAWITAN':[-7.235,107.899],'TAROGONG KALER':[-7.206,107.888],'TAROGONG KIDUL':[-7.215,107.897],
        'BANYURESMI':[-7.152,107.974],'CILAWU':[-7.259,107.978],'GARUT KOTA':[-7.218,107.905],
        'CISOMPET':[-7.434,107.936],'MALANGBONG':[-7.390,108.027],
        // SUKABUMI
        'CICURUG':[-6.799,106.808],'CISAAT':[-6.918,106.901],'GUNUNG PUYUH':[-6.921,106.927],
        'JAMPANGKULON':[-7.286,106.629],'NYALINDUNG':[-7.001,106.892],'PARUNGKUDA':[-6.808,106.823],
        'CIRACAP':[-7.268,106.442],
        // CIANJUR
        'CIPANAS':[-6.742,107.033],'KARANG TENGAH':[-6.810,107.128],'CILAKU':[-6.856,107.102],
        'WARUNGKONDANG':[-6.842,107.044],'SUKALUYU':[-6.780,107.178],'MANDE':[-6.793,107.174],
        'HAURWANGI':[-6.813,107.234],'CUGEUANG':[-6.760,107.000],'CUGENAUG':[-6.760,107.000],
        'CIANJUR':[-6.820,107.140],
        // CIREBON
        'KAPETAKAN':[-6.618,108.556],'WERU':[-6.755,108.586],'DEPOK':[-6.684,108.547],
        // TASIKMALAYA
        'PAGERAGEUUNG':[-7.344,108.165],'CIIHIDEUUNG':[-7.355,108.191],'CIHIDEUUNG':[-7.355,108.191],
        // KARAWANG
        'KARAWANG BARAT':[-6.319,107.284],'PANGKALAN':[-6.277,107.477],'TEGALWARU':[-6.398,107.411],
        'CILAMAYA WETAN':[-6.148,107.614],
        // KUNINGAN
        'SINDANGAGUNG':[-6.938,108.493],
        // SUBANG
        'SUBANG':[-6.571,107.758],'CIJAMBE':[-6.580,107.665],'PABUAN':[-6.440,107.570],'PABUARAN':[-6.440,107.570],
        // SUMEDANG
        'SITUARAJA':[-6.867,107.989],'SITAURAJA':[-6.867,107.989],'SIRAUJA':[-6.867,107.989],
        'CIMALAKA':[-6.782,107.924],
        // CIAMIS
        'PANUMBANGAN':[-7.190,108.363],'SINDANGKASIH':[-7.360,108.459],
        // MAJALENGKA
        'JATITUJUH':[-6.617,108.271],
        // PURWAKARTA
        'JATILUHUR':[-6.540,107.426],
        // INDRAMAYU
        'KANDANGHAUR':[-6.365,108.106],
        // BANJAR
        'BANJAR':[-7.368,108.540],
        // CIMAHI
        'CIMAHI UTARA':[-6.868,107.544],
      };
      _leafletMap=L.map(el,{zoomControl:true,scrollWheelZoom:false,attributionControl:false}).setView([-6.54,106.82],10);
      L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',{
        maxZoom:18,attribution:'©OpenStreetMap ©CARTO'
      }).addTo(_leafletMap);
      L.control.attribution({position:'bottomright',prefix:false}).addAttribution('©<a href="https://carto.com">CARTO</a>').addTo(_leafletMap);
      const SEBARAN_BOGOR=SEBARAN.filter(s=>(s.kota||'').toUpperCase().includes('BOGOR'));
      const maxJ=Math.max(...SEBARAN_BOGOR.map(s=>parseInt(s.jumlah)||0),1);
      SEBARAN_BOGOR.forEach(function(s){
        const k=(s.kecamatan||'').trim().toUpperCase();
        const c=COORDS[k];if(!c)return;
        const j=parseInt(s.jumlah)||0;
        const r=Math.max(6,Math.min(40,6+Math.sqrt(j/maxJ)*34));
        L.circleMarker(c,{radius:r,fillColor:'#696cff',color:'#4040cc',weight:1.5,fillOpacity:0.65})
          .bindPopup('<b>'+s.kecamatan+'</b><br><small>'+s.kota+'</small><br>'+j.toLocaleString('id-ID')+' nasabah<br>OS: Rp '+(parseFloat(s.outstanding)/1e9).toFixed(1)+' M')
          .addTo(_leafletMap);
      });
      setTimeout(function(){if(_leafletMap)_leafletMap.invalidateSize();},200);
    })();}catch(e){console.warn('map-nasabah',e);}
  }
} // end _initCharts

// ── Slideshow engine ──────────────────────────────────────────────────────
const TOTAL=7, DUR=30000;
let _cur=0, _autoTimer=null;
const _slides=document.querySelectorAll('.slide');
const _dots=document.querySelectorAll('.dot');
const _pf=document.getElementById('pf');

function _startBar(){
  if(!_pf)return;
  _pf.style.transition='none';
  _pf.style.width='0%';
  void _pf.offsetWidth;
  _pf.style.transition='width '+DUR+'ms linear';
  _pf.style.width='100%';
}

function _showSlide(n){
  const next=((n%TOTAL)+TOTAL)%TOTAL;
  if(_slides[_cur]) _slides[_cur].classList.remove('active');
  if(_dots[_cur])   _dots[_cur].classList.remove('active');
  _cur=next;
  if(_slides[_cur]) _slides[_cur].classList.add('active');
  if(_dots[_cur])   _dots[_cur].classList.add('active');
  _initCharts(_cur);
  if(_cur===5&&_leafletMap)setTimeout(function(){_leafletMap.invalidateSize();},100);
  clearTimeout(_autoTimer);
  if(_playing){
    _startBar();
    _autoTimer=setTimeout(()=>_showSlide(_cur+1), DUR);
  }
}

window.goSlide=function(i){ clearTimeout(_autoTimer); _showSlide(i); };

let _playing=true;
window.togglePlay=function(){
  _playing=!_playing;
  const ico=document.getElementById('ico-play');
  if(_playing){
    ico.className='ti ti-pause';
    _showSlide(_cur);
  }else{
    ico.className='ti ti-player-play';
    clearTimeout(_autoTimer);
    if(_pf){_pf.style.transition='none';_pf.style.width=_pf.style.width;}
  }
};

document.addEventListener('keydown',function(e){
  if(e.key==='ArrowRight'||e.key===' '){ e.preventDefault(); clearTimeout(_autoTimer); _showSlide(_cur+1); }
  if(e.key==='ArrowLeft'){               e.preventDefault(); clearTimeout(_autoTimer); _showSlide(_cur-1); }
  if(e.key==='p'||e.key==='P'){          e.preventDefault(); togglePlay(); }
});

_initCharts(0);
_startBar();
_autoTimer=setTimeout(()=>_showSlide(1), DUR);
</script>
@endsection
