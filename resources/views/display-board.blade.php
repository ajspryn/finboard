<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Display Board — FinBoard</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css"/>
<link rel="stylesheet" href="/template/assets/vendor/libs/apex-charts/apex-charts.css"/>
<script src="/template/assets/vendor/libs/apex-charts/apexcharts.js"></script>
<style>
:root{
  --bg:#0f1117;--s1:#161b27;--s2:#1e2435;
  --border:#2d3551;--border2:#3a4468;
  --text:#e8edf5;--muted:#8892b0;--dim:#5a6480;
  --blue:#696cff;--cyan:#03c3ec;--green:#71dd37;--red:#ff3e1d;
  --yellow:#ffab00;--orange:#ff9f43;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{width:100vw;height:100vh;overflow:hidden;background:var(--bg);color:var(--text);font-family:'Segoe UI',system-ui,sans-serif;font-size:14px}
#topbar{position:fixed;top:0;left:0;right:0;z-index:200;height:52px;
  background:linear-gradient(90deg,#13172280,#1e2435cc);
  border-bottom:1px solid var(--border);backdrop-filter:blur(12px);
  display:flex;align-items:center;padding:0 24px;gap:16px}
.logo{font-size:1.1rem;font-weight:800;letter-spacing:.03em;
  background:linear-gradient(135deg,var(--blue),var(--cyan));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.pb{font-size:.73rem;font-weight:600;color:var(--cyan);
  background:rgba(3,195,236,.1);border:1px solid rgba(3,195,236,.25);
  border-radius:20px;padding:3px 12px}
.tr{display:flex;align-items:center;gap:16px;margin-left:auto}
#clock{font-size:1.05rem;font-weight:700;font-variant-numeric:tabular-nums}
#dlbl{font-size:.73rem;color:var(--muted)}
#pw{position:fixed;top:52px;left:0;right:0;z-index:200;height:3px;background:var(--border)}
#pf{height:100%;width:0%;background:linear-gradient(90deg,var(--blue),var(--cyan))}
#dots{position:fixed;bottom:12px;left:50%;transform:translateX(-50%);z-index:200;display:flex;gap:8px;align-items:center}
.dot{width:8px;height:8px;border-radius:50%;background:var(--border2);cursor:pointer;transition:all .3s}
.dot.active{background:var(--blue);transform:scale(1.4);box-shadow:0 0 8px var(--blue)}
#sw{position:fixed;top:55px;left:0;right:0;bottom:32px;overflow:hidden}
.slide{position:absolute;inset:0;opacity:0;pointer-events:none;transition:opacity .5s ease;padding:14px 18px;display:flex;flex-direction:column;gap:12px}
.slide.active{opacity:1;pointer-events:auto}
.stitle{font-size:.75rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.1em;display:flex;align-items:center;gap:8px;flex-shrink:0}
.stitle i{font-size:1rem;opacity:.8}
.gc{background:linear-gradient(135deg,rgba(30,36,53,.95),rgba(22,27,39,.9));border:1px solid var(--border);border-radius:14px;backdrop-filter:blur(8px);overflow:hidden;position:relative}
.gc::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--ct,linear-gradient(90deg,var(--blue),var(--cyan)));border-radius:14px 14px 0 0}
.kpi-card{padding:16px 18px;display:flex;flex-direction:column;gap:5px}
.kpi-grid{display:grid;gap:10px;flex:1;align-content:start}
.g3{grid-template-columns:repeat(3,1fr)}
.g2{grid-template-columns:repeat(2,1fr)}
.klbl{font-size:.67rem;color:var(--muted);text-transform:uppercase;letter-spacing:.07em;font-weight:600}
.kval{font-size:1.7rem;font-weight:800;line-height:1.1;color:var(--text)}
.kval.xl{font-size:2.1rem}
.kval.sm{font-size:1.3rem}
.ksub{font-size:.68rem;color:var(--muted)}
.kb{display:inline-flex;align-items:center;gap:3px;font-size:.68rem;font-weight:700;padding:2px 9px;border-radius:20px;width:fit-content}
.kb.up{background:rgba(113,221,55,.15);color:var(--green)}
.kb.dn{background:rgba(255,62,29,.15);color:var(--red)}
.kb.warn{background:rgba(255,171,0,.15);color:var(--yellow)}
.kb.info{background:rgba(105,108,255,.15);color:var(--blue)}
.kb.cy{background:rgba(3,195,236,.15);color:var(--cyan)}
.chart-row{display:grid;gap:12px;flex:1;min-height:0}
.cr21{grid-template-columns:2fr 1fr}
.cr12{grid-template-columns:1fr 2fr}
.cc{padding:12px 14px;display:flex;flex-direction:column;min-height:0}
.cct{font-size:.68rem;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px;flex-shrink:0}
.ca{flex:1;min-height:0}
.nt{width:100%;border-collapse:collapse;font-size:.79rem}
.nt th{color:var(--dim);text-align:left;font-weight:700;padding:4px 8px;border-bottom:1px solid var(--border);text-transform:uppercase;font-size:.62rem;letter-spacing:.06em}
.nt td{padding:7px 8px;border-bottom:1px solid rgba(45,53,81,.4)}
.nt tr:last-child td{border-bottom:none}
.nb{display:inline-block;padding:1px 8px;border-radius:20px;font-size:.63rem;font-weight:800}
.nb1{background:rgba(113,221,55,.2);color:var(--green)}
.nb2{background:rgba(3,195,236,.2);color:var(--cyan)}
.nb3{background:rgba(255,171,0,.2);color:var(--yellow)}
.nb4{background:rgba(255,159,67,.2);color:var(--orange)}
.nb5{background:rgba(255,62,29,.2);color:var(--red)}
.fh-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;flex:1;align-content:start}
.fh-card{padding:14px 16px;display:flex;align-items:center;gap:14px}
.fh-icon{width:48px;height:48px;border-radius:13px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.3rem}
.fh-body{flex:1;min-width:0}
.fh-lbl{font-size:.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:.07em;font-weight:700;margin-bottom:2px}
.fh-val{font-size:1.25rem;font-weight:800;color:var(--text);line-height:1}
.fh-chg{font-size:.65rem;margin-top:3px}
.bar-row{display:flex;align-items:center;gap:8px;padding:4px 0}
.bar-l{font-size:.7rem;color:var(--muted);min-width:62px}
.bar-o{flex:1;height:7px;background:var(--border);border-radius:4px;overflow:hidden}
.bar-i{height:100%;border-radius:4px;background:var(--blue)}
.bar-v{font-size:.7rem;color:var(--text);min-width:68px;text-align:right;font-weight:600}
.nscr{overflow-y:auto;flex:1;scrollbar-width:thin;scrollbar-color:var(--border) transparent}
</style>
</head>
<body>
<div id="topbar">
  <span class="logo"><i class="ti ti-chart-bar"></i> FinBoard</span>
  <span class="pb"><i class="ti ti-calendar-event"></i> Periode: {{ $periodeLabel }}</span>
  <div class="tr"><span id="dlbl"></span>&nbsp;<span id="clock"></span></div>
</div>
<div id="pw"><div id="pf"></div></div>
<div id="sw">

{{-- SLIDE 1: KPI OVERVIEW --}}
<div class="slide active" id="s0">
  <div class="stitle"><i class="ti ti-dashboard"></i> Ringkasan Kinerja &mdash; {{ $periodeLabel }}</div>
  <div class="kpi-grid g3" style="flex:1">

    <div class="gc kpi-card" style="--ct:linear-gradient(90deg,#03c3ec,#696cff)">
      <div class="klbl">Dana Pihak Ketiga (DPK)</div>
      <div class="kval xl" style="color:var(--cyan)">Rp {{ number_format($totalFunding/1e9,2) }} <small style="font-size:.85rem;color:var(--muted)">M</small></div>
      <div class="ksub">Tabungan Rp {{ number_format($totalTabungan/1e9,2) }} M &nbsp;&middot;&nbsp; Deposito Rp {{ number_format($totalDeposito/1e9,2) }} M</div>
      @php $gc=$fundingGrowth>=0?'up':'dn' @endphp
      <span class="kb {{ $gc }}">{{ $fundingGrowth>=0?'&#9650;':'&#9660;' }} {{ abs($fundingGrowth) }}% vs bln lalu</span>
    </div>

    <div class="gc kpi-card" style="--ct:linear-gradient(90deg,#71dd37,#03c3ec)">
      <div class="klbl">Outstanding Pembiayaan (Pokok)</div>
      <div class="kval xl" style="color:var(--green)">Rp {{ number_format($totalLending/1e9,2) }} <small style="font-size:.85rem;color:var(--muted)">M</small></div>
      <div class="ksub">Plafon: Rp {{ number_format($totalPlafon/1e9,2) }} M</div>
      @php $lc=$lendingGrowth>=0?'up':'dn' @endphp
      <span class="kb {{ $lc }}">{{ $lendingGrowth>=0?'&#9650;':'&#9660;' }} {{ abs($lendingGrowth) }}% vs bln lalu</span>
    </div>

    @php
      $nc=$npfRatio>5?'dn':($npfRatio>3?'warn':'up');
      $ncol=$npfRatio>5?'var(--red)':($npfRatio>3?'var(--yellow)':'var(--green)');
      $ngt=$npfRatio>5?'#ff3e1d,#ff9f43':($npfRatio>3?'#ffab00,#ff9f43':'#71dd37,#03c3ec');
    @endphp
    <div class="gc kpi-card" style="--ct:linear-gradient(90deg,{{ $ngt }})">
      <div class="klbl">NPF Ratio</div>
      <div class="kval xl" style="color:{{ $ncol }}">{{ $npfRatio }}%</div>
      <div class="ksub">Nominal NPF: Rp {{ number_format($totalNPF/1e9,2) }} M</div>
      <span class="kb {{ $nc }}">
        @if($npfRatio>5) &#9888; Di atas batas aman (5%)
        @elseif($npfRatio>3) &#9888; Perlu perhatian
        @else &#10003; Terkendali @endif
      </span>
    </div>

    <div class="gc kpi-card" style="--ct:linear-gradient(90deg,#8592a3,#696cff)">
      <div class="klbl">Total Tabungan</div>
      <div class="kval" style="color:#a8b2ff">Rp {{ number_format($totalTabungan/1e9,2) }} <small style="font-size:.75rem;color:var(--muted)">M</small></div>
      @php $tp=$totalFunding>0?round($totalTabungan/$totalFunding*100,1):0 @endphp
      <div class="ksub">{{ $tp }}% dari DPK</div>
      <span class="kb info"><i class="ti ti-piggy-bank"></i> Simpanan harian</span>
    </div>

    <div class="gc kpi-card" style="--ct:linear-gradient(90deg,#ff9f43,#ff3e1d)">
      <div class="klbl">Total Deposito</div>
      <div class="kval" style="color:var(--orange)">Rp {{ number_format($totalDeposito/1e9,2) }} <small style="font-size:.75rem;color:var(--muted)">M</small></div>
      @php $dp2=$totalFunding>0?round($totalDeposito/$totalFunding*100,1):0 @endphp
      <div class="ksub">{{ $dp2 }}% dari DPK</div>
      <span class="kb warn"><i class="ti ti-building-bank"></i> Simpanan berjangka</span>
    </div>

    <div class="gc kpi-card" style="--ct:linear-gradient(90deg,#03c3ec,#71dd37)">
      <div class="klbl">Total Kontrak Pembiayaan</div>
      <div class="kval xl" style="color:var(--cyan)">{{ number_format($totalNasabah) }}</div>
      <div class="ksub">Lancar: {{ number_format($kolCount['1']??0) }} &middot; Perhatian: {{ number_format($kolCount['2']??0) }}</div>
      <span class="kb cy"><i class="ti ti-users"></i> Nasabah aktif</span>
    </div>

  </div>
</div>

{{-- SLIDE 2: FINANCIAL HIGHLIGHTS --}}
<div class="slide" id="s1">
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
      ['key'=>'car',       'label'=>'CAR',             'icon'=>'ti-shield-check',  'color'=>'#696cff', 'bg'=>'rgba(105,108,255,.15)', 'type'=>'pct'],
      ['key'=>'roa',       'label'=>'ROA',             'icon'=>'ti-trending-up',   'color'=>'#71dd37', 'bg'=>'rgba(113,221,55,.15)',  'type'=>'pct'],
      ['key'=>'roe',       'label'=>'ROE',             'icon'=>'ti-coin',          'color'=>'#03c3ec', 'bg'=>'rgba(3,195,236,.15)',   'type'=>'pct'],
      ['key'=>'fdr',       'label'=>'FDR',             'icon'=>'ti-scale',         'color'=>'#ffab00', 'bg'=>'rgba(255,171,0,.15)',   'type'=>'pct'],
      ['key'=>'npf',       'label'=>'NPF (FH)',        'icon'=>'ti-alert-triangle','color'=>'#ff3e1d', 'bg'=>'rgba(255,62,29,.15)',   'type'=>'pct'],
      ['key'=>'bopo',      'label'=>'BOPO',            'icon'=>'ti-receipt',       'color'=>'#ff9f43', 'bg'=>'rgba(255,159,67,.15)',  'type'=>'pct'],
      ['key'=>'cash_ratio','label'=>'Cash Ratio',      'icon'=>'ti-cash',          'color'=>'#8592a3', 'bg'=>'rgba(133,146,163,.15)','type'=>'pct'],
      ['key'=>'aset',      'label'=>'Total Aset',      'icon'=>'ti-building',      'color'=>'#696cff', 'bg'=>'rgba(105,108,255,.15)', 'type'=>'nom'],
      ['key'=>'dpk',       'label'=>'DPK (FH)',        'icon'=>'ti-database',      'color'=>'#03c3ec', 'bg'=>'rgba(3,195,236,.15)',   'type'=>'nom'],
      ['key'=>'pembiayaan','label'=>'Pembiayaan (FH)', 'icon'=>'ti-credit-card',   'color'=>'#71dd37', 'bg'=>'rgba(113,221,55,.15)',  'type'=>'nom'],
      ['key'=>'laba_rugi', 'label'=>'Laba / Rugi',     'icon'=>'ti-chart-bar',     'color'=>'#ffab00', 'bg'=>'rgba(255,171,0,.15)',   'type'=>'nom'],
      ['key'=>'pendapatan','label'=>'Pendapatan',      'icon'=>'ti-cash-banknote', 'color'=>'#71dd37', 'bg'=>'rgba(113,221,55,.15)',  'type'=>'nom'],
    ];
  @endphp
  <div class="fh-grid">
    @foreach($fhCards as $c)
    @php
      $v=$financialHighlight->{$c['key']};
      $fmt=$c['type']==='pct'?number_format((float)$v,2).'%':'Rp '.number_format((float)$v/1e9,2).' M';
      $chg=$fhChanges[$c['key']]??null;
      $chgV=(float)($chg??0);
      $chgCls=$chgV>=0?'up':'dn';
      $chgTxt=$chg!==null?($chgV>=0?'&#9650;':'&#9660;').' '.number_format(abs($chgV),2).'%':'-';
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
  <div style="flex:0 0 140px;display:grid">
    <div class="gc cc">
      <div class="cct">Tren Rasio Kinerja &mdash; CAR &middot; ROA &middot; FDR &middot; NPF</div>
      <div class="ca" id="fh-trend-chart"></div>
    </div>
  </div>
  @endif
  @endif
</div>

{{-- SLIDE 3: TREN BULANAN --}}
<div class="slide" id="s2">
  <div class="stitle"><i class="ti ti-chart-area"></i> Tren Bulanan Pembiayaan &amp; DPK</div>
  <div style="flex:1;display:grid;grid-template-rows:1fr 1fr;gap:12px;min-height:0">
    <div class="gc cc">
      <div class="cct">Plafon vs Outstanding Pembiayaan (Rp Miliar)</div>
      <div class="ca" id="ch-t-lending"></div>
    </div>
    <div class="gc cc">
      <div class="cct">Total DPK &amp; NPF Ratio Bulanan</div>
      <div class="ca" id="ch-t-dpk"></div>
    </div>
  </div>
</div>

{{-- SLIDE 4: KUALITAS NPF --}}
<div class="slide" id="s3">
  <div class="stitle"><i class="ti ti-alert-circle"></i> Kualitas Pembiayaan &amp; NPF</div>
  <div class="chart-row cr21" style="flex:1;min-height:0">
    <div style="display:grid;grid-template-rows:1fr 1fr;gap:12px;min-height:0">
      <div class="gc cc">
        <div class="cct">Distribusi Kolektibilitas &mdash; Outstanding (Rp M)</div>
        <div class="ca" id="ch-kol"></div>
      </div>
      <div class="gc cc">
        <div class="cct">Jumlah Kontrak per Kolektibilitas</div>
        <div class="ca" id="ch-kol-cnt"></div>
      </div>
    </div>
    <div class="gc cc" style="display:flex;flex-direction:column;min-height:0">
      <div class="cct">Top 5 Nasabah NPF Terbesar</div>
      <div class="nscr">
        <table class="nt">
          <thead><tr><th>#</th><th>Nama Nasabah</th><th>Segmen</th><th>Kol</th><th style="text-align:right">OS Pokok</th></tr></thead>
          <tbody>
            @forelse($topNpf as $i=>$n)
            <tr>
              <td style="color:var(--dim);font-weight:700">{{ $i+1 }}</td>
              <td style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $n->nama??'-' }}</td>
              <td style="font-size:.65rem;color:var(--muted)">{{ $n->segmen??'-' }}</td>
              <td><span class="nb nb{{ $n->colbaru }}">Kol {{ $n->colbaru }}</span></td>
              <td style="text-align:right;font-variant-numeric:tabular-nums">Rp {{ number_format($n->osmdlc/1e6,1) }} Jt</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;color:var(--dim);padding:20px">Tidak ada data NPF</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div style="margin-top:10px;border-top:1px solid var(--border);padding-top:10px">
        <div class="cct" style="margin-bottom:8px">Outstanding per Kolektibilitas</div>
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

{{-- SLIDE 5: KOMPOSISI DPK --}}
<div class="slide" id="s4">
  <div class="stitle"><i class="ti ti-chart-donut-3"></i> Komposisi Dana Pihak Ketiga</div>
  <div class="chart-row cr12" style="flex:1;min-height:0">
    <div style="display:grid;grid-template-rows:auto 1fr;gap:12px;min-height:0">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div class="gc kpi-card">
          <div class="klbl">Total DPK</div>
          <div class="kval sm" style="color:var(--cyan)">Rp {{ number_format($totalFunding/1e9,2) }} M</div>
          <span class="kb {{ $fundingGrowth>=0?'up':'dn' }}" style="margin-top:2px">{{ $fundingGrowth>=0?'&#9650;':'&#9660;' }} {{ abs($fundingGrowth) }}%</span>
        </div>
        <div class="gc kpi-card">
          @php $tp2=$totalFunding>0?round($totalTabungan/$totalFunding*100,1):0 @endphp
          <div class="klbl">Tabungan</div>
          <div class="kval sm" style="color:#a8b2ff">Rp {{ number_format($totalTabungan/1e9,2) }} M</div>
          <span class="kb info">{{ $tp2 }}% dari DPK</span>
        </div>
        <div class="gc kpi-card">
          @php $dp3=$totalFunding>0?round($totalDeposito/$totalFunding*100,1):0 @endphp
          <div class="klbl">Deposito</div>
          <div class="kval sm" style="color:var(--orange)">Rp {{ number_format($totalDeposito/1e9,2) }} M</div>
          <span class="kb warn">{{ $dp3 }}% dari DPK</span>
        </div>
        <div class="gc kpi-card">
          <div class="klbl">Kontrak Aktif</div>
          <div class="kval sm" style="color:var(--green)">{{ number_format($totalNasabah) }}</div>
          <span class="kb up"><i class="ti ti-users"></i> nasabah</span>
        </div>
      </div>
      <div class="gc cc">
        <div class="cct">Tren DPK &mdash; Total per Periode (Rp M)</div>
        <div class="ca" id="ch-dpk-line"></div>
      </div>
    </div>
    <div style="display:grid;grid-template-rows:1fr 1fr;gap:12px;min-height:0">
      <div class="gc cc">
        <div class="cct">Proporsi DPK saat ini</div>
        <div class="ca" id="ch-dpk-pie"></div>
      </div>
      <div class="gc cc">
        <div class="cct">Tren NPF Ratio (%)</div>
        <div class="ca" id="ch-npf-line"></div>
      </div>
    </div>
  </div>
</div>

</div>{{-- end #sw --}}

<div id="dots">
  <div class="dot active" onclick="goSlide(0)" title="KPI Overview"></div>
  <div class="dot" onclick="goSlide(1)" title="Financial Highlights"></div>
  <div class="dot" onclick="goSlide(2)" title="Tren Bulanan"></div>
  <div class="dot" onclick="goSlide(3)" title="Kualitas NPF"></div>
  <div class="dot" onclick="goSlide(4)" title="Komposisi DPK"></div>
</div>

<script>
const TL=@json($monthlyTrends['labels']);
const TP=@json($monthlyTrends['plafon']);
const TOS=@json($monthlyTrends['lending']);
const TF=@json($monthlyTrends['funding']);
const TN=@json($monthlyTrends['npf_ratio']);
const KV=@json($kolDistrib);
const KC=@json($kolCount);
const FHT=@json($fhTrends);
const TV={{ $totalTabungan }};
const DV={{ $totalDeposito }};

function tick(){
  const n=new Date();
  document.getElementById('clock').textContent=n.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
  document.getElementById('dlbl').textContent=n.toLocaleDateString('id-ID',{weekday:'long',day:'numeric',month:'long',year:'numeric'});
}
tick();setInterval(tick,1000);

const TOTAL=5,DUR=12000;
let cur=0,pt=null;
const slides=document.querySelectorAll('.slide'),dots=document.querySelectorAll('.dot'),pf=document.getElementById('pf');
function goSlide(i){slides[cur].classList.remove('active');dots[cur].classList.remove('active');cur=i;slides[cur].classList.add('active');dots[cur].classList.add('active');startP();}
function nextSlide(){goSlide((cur+1)%TOTAL);}
function startP(){clearTimeout(pt);pf.style.transition='none';pf.style.width='0%';void pf.offsetWidth;pf.style.transition='width '+DUR+'ms linear';pf.style.width='100%';pt=setTimeout(nextSlide,DUR);}
startP();
document.addEventListener('keydown',e=>{if(e.key==='ArrowRight'||e.key===' ')nextSlide();if(e.key==='ArrowLeft')goSlide((cur-1+TOTAL)%TOTAL);});

const AP={theme:{mode:'dark'},chart:{background:'transparent',toolbar:{show:false},animations:{enabled:true,speed:500}},grid:{borderColor:'#2d3551',strokeDashArray:4,padding:{top:0,right:10,bottom:0,left:10}},tooltip:{theme:'dark',style:{fontSize:'12px'}},dataLabels:{enabled:false},legend:{labels:{colors:'#8892b0'},fontSize:'11px'},stroke:{curve:'smooth',width:2}};

(function(){const el=document.getElementById('ch-t-lending');if(!el)return;
new ApexCharts(el,{...AP,chart:{...AP.chart,type:'area',height:'100%'},
series:[{name:'Plafon (Rp M)',data:TP},{name:'Outstanding (Rp M)',data:TOS}],
xaxis:{categories:TL,labels:{style:{colors:'#8892b0',fontSize:'10px'},rotate:-30}},
yaxis:{labels:{style:{colors:'#8892b0',fontSize:'11px'},formatter:v=>v>=1000?(v/1000).toFixed(1)+'T':v.toFixed(1)+' M'}},
colors:['#696cff','#71dd37'],fill:{type:'gradient',gradient:{opacityFrom:.3,opacityTo:.03}},
markers:{size:3,hover:{size:5}},stroke:{curve:'smooth',width:2.5},
tooltip:{y:{formatter:v=>'Rp '+v.toFixed(2)+' M'}},legend:{...AP.legend,position:'top'},}).render();})();

(function(){const el=document.getElementById('ch-t-dpk');if(!el)return;
new ApexCharts(el,{...AP,chart:{...AP.chart,type:'line',height:'100%'},
series:[{name:'DPK (Rp M)',data:TF,type:'area'},{name:'NPF Ratio (%)',data:TN,type:'line'}],
xaxis:{categories:TL,labels:{style:{colors:'#8892b0',fontSize:'10px'},rotate:-30}},
yaxis:[{labels:{style:{colors:'#8892b0',fontSize:'11px'},formatter:v=>v.toFixed(1)+' M'},title:{text:'DPK (M)',style:{color:'#8892b0'}}},{opposite:true,labels:{style:{colors:'#ff3e1d',fontSize:'11px'},formatter:v=>v.toFixed(2)+'%'},title:{text:'NPF%',style:{color:'#ff3e1d'}}}],
colors:['#03c3ec','#ff3e1d'],fill:{type:'gradient',gradient:{opacityFrom:.3,opacityTo:.02}},
markers:{size:3,hover:{size:5}},stroke:{width:[2.5,2.5],curve:'smooth'},
tooltip:{shared:true,intersect:false},legend:{...AP.legend,position:'top'},}).render();})();

(function(){const el=document.getElementById('ch-kol');if(!el)return;
const vals=[KV['1'],KV['2'],KV['3'],KV['4'],KV['5']].map(v=>parseFloat((v/1e9).toFixed(2)));
new ApexCharts(el,{...AP,chart:{...AP.chart,type:'donut',height:'100%'},
series:vals,labels:['Lancar (1)','Dlm Perhatian (2)','Kur. Lancar (3)','Diragukan (4)','Macet (5)'],
colors:['#71dd37','#03c3ec','#ffab00','#ff9f43','#ff3e1d'],
plotOptions:{pie:{donut:{size:'65%',labels:{show:true,total:{show:true,label:'Total OS',color:'#8892b0',formatter:w=>{const s=w.globals.seriesTotals.reduce((a,b)=>a+b,0);return s.toFixed(2)+' M';}}}}}}},
legend:{...AP.legend,position:'bottom',fontSize:'10px'},
dataLabels:{enabled:true,formatter:(v,o)=>o.w.globals.series[o.seriesIndex].toFixed(1)+'M',style:{fontSize:'10px'}},
tooltip:{y:{formatter:v=>'Rp '+v.toFixed(2)+' M'}},}).render();})();

(function(){const el=document.getElementById('ch-kol-cnt');if(!el)return;
new ApexCharts(el,{...AP,chart:{...AP.chart,type:'bar',height:'100%'},
series:[{name:'Jumlah Kontrak',data:[KC['1']||0,KC['2']||0,KC['3']||0,KC['4']||0,KC['5']||0]}],
xaxis:{categories:['Lancar','Dlm Perhatian','Kur. Lancar','Diragukan','Macet'],labels:{style:{colors:'#8892b0',fontSize:'10px'}}},
yaxis:{labels:{style:{colors:'#8892b0',fontSize:'11px'}}},
colors:['#71dd37','#03c3ec','#ffab00','#ff9f43','#ff3e1d'],
plotOptions:{bar:{borderRadius:6,distributed:true,columnWidth:'55%',dataLabels:{position:'top'}}},
dataLabels:{enabled:true,offsetY:-20,style:{fontSize:'10px',fontWeight:700,colors:['#8892b0']}},
tooltip:{y:{formatter:v=>v+' kontrak'}},}).render();})();

(function(){const el=document.getElementById('fh-trend-chart');if(!el)return;
if(!FHT||!FHT.labels||FHT.labels.length<2)return;
new ApexCharts(el,{...AP,chart:{...AP.chart,type:'line',height:'100%'},
series:[{name:'CAR (%)',data:FHT.car||[]},{name:'ROA (%)',data:FHT.roa||[]},{name:'FDR (%)',data:FHT.fdr||[]},{name:'NPF (%)',data:FHT.npf||[]}],
xaxis:{categories:FHT.labels,labels:{style:{colors:'#8892b0',fontSize:'10px'},rotate:-30}},
yaxis:{labels:{style:{colors:'#8892b0',fontSize:'11px'},formatter:v=>v.toFixed(2)+'%'}},
colors:['#696cff','#71dd37','#ffab00','#ff3e1d'],markers:{size:3,hover:{size:5}},stroke:{curve:'smooth',width:2},
legend:{...AP.legend,position:'top',fontSize:'10px'},tooltip:{y:{formatter:v=>v.toFixed(2)+'%'}},}).render();})();

(function(){const el=document.getElementById('ch-dpk-line');if(!el)return;
new ApexCharts(el,{...AP,chart:{...AP.chart,type:'area',height:'100%'},
series:[{name:'Total DPK (Rp M)',data:TF}],
xaxis:{categories:TL,labels:{style:{colors:'#8892b0',fontSize:'10px'},rotate:-30}},
yaxis:{labels:{style:{colors:'#8892b0',fontSize:'11px'},formatter:v=>v.toFixed(1)+' M'}},
colors:['#03c3ec'],fill:{type:'gradient',gradient:{opacityFrom:.4,opacityTo:.02}},
markers:{size:3,hover:{size:5}},stroke:{curve:'smooth',width:2.5},
tooltip:{y:{formatter:v=>'Rp '+v.toFixed(2)+' M'}},}).render();})();

(function(){const el=document.getElementById('ch-dpk-pie');if(!el)return;
new ApexCharts(el,{...AP,chart:{...AP.chart,type:'donut',height:'100%'},
series:[parseFloat((TV/1e9).toFixed(2)),parseFloat((DV/1e9).toFixed(2))],
labels:['Tabungan','Deposito'],colors:['#696cff','#03c3ec'],
plotOptions:{pie:{donut:{size:'65%',labels:{show:true,total:{show:true,label:'Total DPK',color:'#8892b0',formatter:w=>'Rp '+(w.globals.seriesTotals.reduce((a,b)=>a+b,0)).toFixed(2)+' M'}}}}},
legend:{...AP.legend,position:'bottom',fontSize:'11px'},
dataLabels:{enabled:true,formatter:v=>v.toFixed(1)+'%',style:{fontSize:'11px'}},
tooltip:{y:{formatter:v=>'Rp '+v.toFixed(2)+' M'}},}).render();})();

(function(){const el=document.getElementById('ch-npf-line');if(!el)return;
new ApexCharts(el,{...AP,chart:{...AP.chart,type:'area',height:'100%'},
series:[{name:'NPF Ratio (%)',data:TN}],
xaxis:{categories:TL,labels:{style:{colors:'#8892b0',fontSize:'10px'},rotate:-30}},
yaxis:{labels:{style:{colors:'#ff3e1d',fontSize:'11px'},formatter:v=>v.toFixed(2)+'%'}},
colors:['#ff3e1d'],fill:{type:'gradient',gradient:{opacityFrom:.35,opacityTo:.02}},
markers:{size:3,hover:{size:5}},stroke:{curve:'smooth',width:2.5},
annotations:{yaxis:[{y:5,borderColor:'#ff3e1d',strokeDashArray:4,label:{text:'Batas 5%',style:{color:'#ff3e1d',background:'transparent',fontSize:'10px'}}}]},
tooltip:{y:{formatter:v=>v.toFixed(2)+'%'}},}).render();})();
</script>
</body>
</html>
