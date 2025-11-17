@extends('layouts.app')

@section('title', 'Dashboard Bank')

@section('styles')
<link rel="stylesheet" href="/template/assets/vendor/libs/apex-charts/apex-charts.css" />
<style>
    .card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .segment-row:hover {
        background-color: #f0f7ff !important;
        transition: background-color 0.2s ease;
    }

    .segment-row {
        cursor: pointer;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
    }
    .avatar-initial {
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>
@endsection

@section('content')
<!-- Kartu Statistik Utama -->
<div class="row">
    <!-- Funding Card -->
    <div class="col-lg-4 col-md-6 col-12 mb-4">
        <div class="card h-100 border-primary border-2">
            <div class="card-header d-flex justify-content-between bg-label-primary">
                <div class="card-title mb-0">
                    <h5 class="mb-0 text-primary">💰 Funding</h5>
                    <small class="text-muted">Dana Pihak Ketiga</small>
                </div>
                <div class="dropdown">
                    <span class="badge bg-success">+{{ $funding['growth'] }}%</span>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex flex-column">
                        <div class="d-flex align-items-center mb-1">
                            <h2 class="mb-0 me-2 text-primary fw-bold">
                                @if($funding['total'] >= 1000000000)
                                    Rp {{ number_format($funding['total'] / 1000000000, 2) }} M
                                @elseif($funding['total'] >= 1000000)
                                    Rp {{ number_format($funding['total'] / 1000000, 2) }} Juta
                                @else
                                    Rp {{ number_format($funding['total'], 0, ',', '.') }}
                                @endif
                            </h2>
                        </div>
                        <small class="text-success fw-medium">
                            <i class="ti ti-trending-up ti-sm"></i>
                            <span>Pertumbuhan {{ $funding['growth'] }}%</span>
                        </small>
                    </div>
                    <div class="avatar avatar-lg">
                        <span class="avatar-initial rounded-3 bg-primary">
                            <i class="ti ti-coin ti-lg text-white"></i>
                        </span>
                    </div>
                </div>

                <div class="mt-3">
                    <h6 class="mb-2">Komposisi Dana</h6>
                    <ul class="list-unstyled mb-0">
                        @foreach($funding['composition'] as $type => $percentage)
                        <li class="d-flex mb-2 pb-1">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="ti ti-wallet"></i>
                                </span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <small class="text-muted d-block mb-1">{{ $type }}</small>
                                </div>
                                <div class="user-progress d-flex align-items-center gap-1">
                                    <h6 class="mb-0">{{ $percentage }}%</h6>
                                </div>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Lending Card -->
    <div class="col-lg-4 col-md-6 col-12 mb-4">
        <div class="card h-100 border-success border-2">
            <div class="card-header d-flex justify-content-between bg-label-success">
                <div class="card-title mb-0">
                    <h5 class="mb-0 text-success">💳 Lending</h5>
                    <small class="text-muted">Pembiayaan & Kredit</small>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex flex-column">
                        <div class="d-flex align-items-center mb-1">
                            <h2 class="mb-0 me-2 text-success fw-bold">
                                @if($lending['total'] >= 1000000000)
                                    Rp {{ number_format($lending['total'] / 1000000000, 2) }} M
                                @elseif($lending['total'] >= 1000000)
                                    Rp {{ number_format($lending['total'] / 1000000, 2) }} Juta
                                @else
                                    Rp {{ number_format($lending['total'], 0, ',', '.') }}
                                @endif
                            </h2>
                        </div>
                        <small class="text-muted">Total Pembiayaan</small>
                    </div>
                    <div class="avatar avatar-lg">
                        <span class="avatar-initial rounded-3 bg-success">
                            <i class="ti ti-credit-card ti-lg text-white"></i>
                        </span>
                    </div>
                </div>

                <div class="mt-3">
                    <h6 class="mb-3">Detail Pembiayaan</h6>

                    <!-- Outstanding & Disbursement -->
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar avatar-sm me-2">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="ti ti-wallet"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <small class="text-muted d-block">Outstanding</small>
                                <h6 class="mb-0">
                                    @if($lending['total'] >= 1000000000)
                                        Rp {{ number_format($lending['total'] / 1000000000, 2) }} M
                                    @elseif($lending['total'] >= 1000000)
                                        Rp {{ number_format($lending['total'] / 1000000, 2) }} Jt
                                    @else
                                        Rp {{ number_format($lending['total'], 0, ',', '.') }}
                                    @endif
                                </h6>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-sm me-2">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="ti ti-coin"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <small class="text-muted d-block">Disbursement (Plafon)</small>
                                <h6 class="mb-0">
                                    @if($lending['plafon_awal'] >= 1000000000)
                                        Rp {{ number_format($lending['plafon_awal'] / 1000000000, 2) }} M
                                    @elseif($lending['plafon_awal'] >= 1000000)
                                        Rp {{ number_format($lending['plafon_awal'] / 1000000, 2) }} Jt
                                    @else
                                        Rp {{ number_format($lending['plafon_awal'], 0, ',', '.') }}
                                    @endif
                                </h6>
                            </div>
                        </div>
                    </div>

                    <div class="border-top pt-3 mb-3"></div>

                    <div class="d-flex justify-content-between mb-3">
                        <div class="d-flex align-items-center">
                            <div class="avatar me-2">
                                <span class="avatar-initial rounded bg-label-warning">
                                    <i class="ti ti-percentage"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Rate Flat</small>
                                <h6 class="mb-0">{{ $lending['rate_flat'] }}%</h6>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="avatar me-2">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="ti ti-chart-line"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Rate Efektif</small>
                                <h6 class="mb-0">{{ $lending['rate_eff'] }}%</h6>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center mt-3 pt-3 border-top">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-users"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Nasabah Aktif</small>
                            <h6 class="mb-0">{{ number_format($lending['nasabah']) }} Nasabah</h6>
                        </div>
                    </div>

                    <!-- Segmentasi Chart -->
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="mb-3">Sebaran Segmentasi</h6>
                        <div id="segmentasiPieChart" style="min-height: 200px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- NPF Card -->
    <div class="col-lg-4 col-md-12 col-12 mb-4">
        <div class="card h-100 border-danger border-2">
            <div class="card-header d-flex justify-content-between bg-label-danger">
                <div class="card-title mb-0">
                    <h5 class="mb-0 text-danger">⚠️ NPF</h5>
                    <small class="text-muted">Non-Performing Financing</small>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex flex-column">
                        <div class="d-flex align-items-center mb-1">
                            <h2 class="mb-0 me-2 text-danger fw-bold">
                                @if($npf['total'] >= 1000000000)
                                    Rp {{ number_format($npf['total'] / 1000000000, 2) }} M
                                @elseif($npf['total'] >= 1000000)
                                    Rp {{ number_format($npf['total'] / 1000000, 2) }} Juta
                                @else
                                    Rp {{ number_format($npf['total'], 0, ',', '.') }}
                                @endif
                            </h2>
                        </div>
                        <small class="text-muted">Total NPF</small>
                    </div>
                    <div class="avatar avatar-lg">
                        <span class="avatar-initial rounded-3 bg-danger">
                            <i class="ti ti-alert-triangle ti-lg text-white"></i>
                        </span>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="mb-0">Rasio NPF</h6>
                        <h4 class="mb-0 text-danger">{{ $npf['ratio'] }}%</h4>
                    </div>

                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $npf['ratio'] }}%;" aria-valuenow="{{ $npf['ratio'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>

                    <!-- Outstanding Info -->

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <small class="text-muted">
                            <i class="ti ti-clock-exclamation me-1"></i>Tunggakan Pokok NPF
                        </small>
                        <strong class="text-danger">
                            @if($npf['tunggakan_pokok'] >= 1000000000)
                                Rp {{ number_format($npf['tunggakan_pokok'] / 1000000000, 2) }} M
                            @elseif($npf['tunggakan_pokok'] >= 1000000)
                                Rp {{ number_format($npf['tunggakan_pokok'] / 1000000, 2) }} Jt
                            @else
                                Rp {{ number_format($npf['tunggakan_pokok'], 0, ',', '.') }}
                            @endif
                        </strong>
                    </div>

                    <div class="alert alert-warning mb-3" role="alert">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <i class="ti ti-info-circle"></i>
                            </div>
                            <div class="flex-grow-1 ms-2">
                                <small>
                                    @if($npf['ratio'] < 5)
                                        NPF dalam batas aman (< 5%)
                                    @else
                                        NPF memerlukan perhatian khusus
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Top 3 Nasabah Penyumbang NPF -->
                    <div class="border-top pt-3">
                        <h6 class="mb-3 text-danger">
                            <i class="ti ti-users-group me-1"></i>
                            Top 3 Nasabah NPF
                        </h6>
                        @if($topNpfContributors->count() > 0)
                            @foreach($topNpfContributors as $index => $contributor)
                            <div class="d-flex align-items-start mb-3 pb-3 {{ $loop->last ? '' : 'border-bottom' }}">
                                <div class="avatar avatar-sm me-3 flex-shrink-0">
                                    <span class="avatar-initial rounded-circle bg-label-{{ $contributor['colbaru'] == '3' ? 'warning' : ($contributor['colbaru'] == '4' ? 'danger' : 'dark') }}">
                                        {{ $index + 1 }}
                                    </span>
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <h6 class="mb-0 text-truncate" style="max-width: 180px;" title="{{ $contributor['nama'] }}">
                                            {{ $contributor['nama'] }}
                                        </h6>
                                        <span class="badge bg-{{ $contributor['colbaru'] == '3' ? 'warning' : ($contributor['colbaru'] == '4' ? 'danger' : 'dark') }} badge-sm">
                                            {{ $contributor['colbaru_label'] }}
                                        </span>
                                    </div>
                                    <small class="text-muted d-block mb-1">{{ $contributor['nokontrak'] }}</small>
                                    <strong class="text-danger">
                                        @if($contributor['osmdlc'] >= 1000000000)
                                            Rp {{ number_format($contributor['osmdlc'] / 1000000000, 2) }} M
                                        @elseif($contributor['osmdlc'] >= 1000000)
                                            Rp {{ number_format($contributor['osmdlc'] / 1000000, 2) }} Jt
                                        @else
                                            Rp {{ number_format($contributor['osmdlc'], 0, ',', '.') }}
                                        @endif
                                    </strong>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center text-muted py-3">
                                <i class="ti ti-check-circle ti-lg mb-2"></i>
                                <p class="mb-0 small">Tidak ada NPF</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row Charts: Monthly Trends & NPF Distribution -->
<div class="row">
    <!-- Monthly Trends Chart -->
    <div class="col-lg-8 col-md-12 col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">📈 Tren Bulanan</h5>
                <small class="text-muted">Plafon vs Outstanding (Miliar Rupiah)</small>
            </div>
            <div class="card-body">
                <div id="monthlyTrendChart"></div>
            </div>
        </div>
    </div>

    <!-- NPF Distribution Chart -->
    <div class="col-lg-4 col-md-12 col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">📊 Distribusi NPF</h5>
                <small class="text-muted">Berdasarkan Kolektibilitas</small>
            </div>
            <div class="card-body">
                <div id="npfDistributionChart"></div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Segmentasi Outstanding & Disburse -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr class="table-light">
                                <th colspan="2" rowspan="2" class="text-center align-middle">SEGMENTASI</th>
                                <th colspan="2" class="text-center bg-primary text-white">OUTSTANDING</th>
                                <th colspan="2" class="text-center bg-success text-white">DISBURSE</th>
                                <th colspan="5" class="text-center bg-warning text-dark">KOLEKTIBILITAS</th>
                                <th rowspan="2" class="text-center align-middle">NOA</th>
                            </tr>
                            <tr class="table-light">
                                <th class="text-end bg-primary text-white">OUTSTANDING</th>
                                <th class="text-center bg-primary text-white">%</th>
                                <th class="text-end bg-success text-white">DISBURSE</th>
                                <th class="text-center bg-success text-white">%</th>
                                <th class="text-center bg-warning text-dark">KOL 1</th>
                                <th class="text-center bg-warning text-dark">KOL 2</th>
                                <th class="text-center bg-warning text-dark">KOL 3</th>
                                <th class="text-center bg-warning text-dark">KOL 4</th>
                                <th class="text-center bg-warning text-dark">KOL 5</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($segmentasiData as $segment)
                            <tr class="{{ $segment['is_total'] ? 'table-active fw-bold' : 'segment-row' }}"
                                @if(!$segment['is_total'])
                                    data-category="{{ $segment['category'] }}"
                                    data-type="{{ $segment['type'] }}"
                                    style="cursor: pointer;"
                                @endif>
                                @if($segment['rowspan'] > 0)
                                    <td rowspan="{{ $segment['rowspan'] }}" class="align-middle fw-bold">{{ $segment['category'] }}</td>
                                @endif
                                @if(!$segment['is_total'])
                                    <td>{{ $segment['type'] }}</td>
                                    <td class="text-end">{{ number_format($segment['outstanding'], 0, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($segment['pct_outstanding'], 2) }}%</td>
                                    <td class="text-end">{{ number_format($segment['disburse'], 0, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($segment['pct_disburse'], 2) }}%</td>
                                    <td class="text-center" style="line-height: 1.2;">
                                        <div style="font-size: 14px;">{{ $segment['col1'] ?? 0 }}</div>
                                        <small class="text-muted" style="font-size: 9px;">
                                            @if(($segment['col1_sum'] ?? 0) >= 1000000000)
                                                {{ number_format(($segment['col1_sum'] ?? 0) / 1000000000, 1) }}M
                                            @else
                                                {{ number_format(($segment['col1_sum'] ?? 0) / 1000000, 0) }}jt
                                            @endif
                                        </small>
                                    </td>
                                    <td class="text-center" style="line-height: 1.2;">
                                        <div style="font-size: 14px;">{{ $segment['col2'] ?? 0 }}</div>
                                        <small class="text-muted" style="font-size: 9px;">
                                            @if(($segment['col2_sum'] ?? 0) >= 1000000000)
                                                {{ number_format(($segment['col2_sum'] ?? 0) / 1000000000, 1) }}M
                                            @else
                                                {{ number_format(($segment['col2_sum'] ?? 0) / 1000000, 0) }}jt
                                            @endif
                                        </small>
                                    </td>
                                    <td class="text-center" style="line-height: 1.2;">
                                        <div style="font-size: 14px;">{{ $segment['col3'] ?? 0 }}</div>
                                        <small class="text-muted" style="font-size: 9px;">
                                            @if(($segment['col3_sum'] ?? 0) >= 1000000000)
                                                {{ number_format(($segment['col3_sum'] ?? 0) / 1000000000, 1) }}M
                                            @else
                                                {{ number_format(($segment['col3_sum'] ?? 0) / 1000000, 0) }}jt
                                            @endif
                                        </small>
                                    </td>
                                    <td class="text-center" style="line-height: 1.2;">
                                        <div style="font-size: 14px;">{{ $segment['col4'] ?? 0 }}</div>
                                        <small class="text-muted" style="font-size: 9px;">
                                            @if(($segment['col4_sum'] ?? 0) >= 1000000000)
                                                {{ number_format(($segment['col4_sum'] ?? 0) / 1000000000, 1) }}M
                                            @else
                                                {{ number_format(($segment['col4_sum'] ?? 0) / 1000000, 0) }}jt
                                            @endif
                                        </small>
                                    </td>
                                    <td class="text-center" style="line-height: 1.2;">
                                        <div style="font-size: 14px;">{{ $segment['col5'] ?? 0 }}</div>
                                        <small class="text-muted" style="font-size: 9px;">
                                            @if(($segment['col5_sum'] ?? 0) >= 1000000000)
                                                {{ number_format(($segment['col5_sum'] ?? 0) / 1000000000, 1) }}M
                                            @else
                                                {{ number_format(($segment['col5_sum'] ?? 0) / 1000000, 0) }}jt
                                            @endif
                                        </small>
                                    </td>
                                    <td class="text-center">{{ number_format($segment['noa']) }}</td>
                                @else
                                    <td class="text-center"><strong>{{ $segment['type'] }}</strong></td>
                                    <td class="text-end"><strong>{{ number_format($segment['outstanding'], 0, ',', '.') }}</strong></td>
                                    <td class="text-center"><strong>{{ number_format($segment['pct_outstanding'], 2) }}%</strong></td>
                                    <td class="text-end"><strong>{{ number_format($segment['disburse'], 0, ',', '.') }}</strong></td>
                                    <td class="text-center"><strong>{{ number_format($segment['pct_disburse'], 2) }}%</strong></td>
                                    <td class="text-center" style="line-height: 1.2;">
                                        <div style="font-size: 14px;"><strong>{{ $segment['col1'] ?? 0 }}</strong></div>
                                        <small class="text-muted" style="font-size: 9px;">
                                            @if(($segment['col1_sum'] ?? 0) >= 1000000000)
                                                {{ number_format(($segment['col1_sum'] ?? 0) / 1000000000, 1) }}M
                                            @else
                                                {{ number_format(($segment['col1_sum'] ?? 0) / 1000000, 0) }}jt
                                            @endif
                                        </small>
                                    </td>
                                    <td class="text-center" style="line-height: 1.2;">
                                        <div style="font-size: 14px;"><strong>{{ $segment['col2'] ?? 0 }}</strong></div>
                                        <small class="text-muted" style="font-size: 9px;">
                                            @if(($segment['col2_sum'] ?? 0) >= 1000000000)
                                                {{ number_format(($segment['col2_sum'] ?? 0) / 1000000000, 1) }}M
                                            @else
                                                {{ number_format(($segment['col2_sum'] ?? 0) / 1000000, 0) }}jt
                                            @endif
                                        </small>
                                    </td>
                                    <td class="text-center" style="line-height: 1.2;">
                                        <div style="font-size: 14px;"><strong>{{ $segment['col3'] ?? 0 }}</strong></div>
                                        <small class="text-muted" style="font-size: 9px;">
                                            @if(($segment['col3_sum'] ?? 0) >= 1000000000)
                                                {{ number_format(($segment['col3_sum'] ?? 0) / 1000000000, 1) }}M
                                            @else
                                                {{ number_format(($segment['col3_sum'] ?? 0) / 1000000, 0) }}jt
                                            @endif
                                        </small>
                                    </td>
                                    <td class="text-center" style="line-height: 1.2;">
                                        <div style="font-size: 14px;"><strong>{{ $segment['col4'] ?? 0 }}</strong></div>
                                        <small class="text-muted" style="font-size: 9px;">
                                            @if(($segment['col4_sum'] ?? 0) >= 1000000000)
                                                {{ number_format(($segment['col4_sum'] ?? 0) / 1000000000, 1) }}M
                                            @else
                                                {{ number_format(($segment['col4_sum'] ?? 0) / 1000000, 0) }}jt
                                            @endif
                                        </small>
                                    </td>
                                    <td class="text-center" style="line-height: 1.2;">
                                        <div style="font-size: 14px;"><strong>{{ $segment['col5'] ?? 0 }}</strong></div>
                                        <small class="text-muted" style="font-size: 9px;">
                                            @if(($segment['col5_sum'] ?? 0) >= 1000000000)
                                                {{ number_format(($segment['col5_sum'] ?? 0) / 1000000000, 1) }}M
                                            @else
                                                {{ number_format(($segment['col5_sum'] ?? 0) / 1000000, 0) }}jt
                                            @endif
                                        </small>
                                    </td>
                                    <td class="text-center"><strong>{{ number_format($segment['noa']) }}</strong></td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Kolektibilitas, Produk & Area -->
<div class="row">
    <!-- Kolektibilitas Card -->
    <div class="col-lg-4 col-md-12 col-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-1 text-primary">📊 Kolektibilitas</h5>
                <small class="text-muted">Distribusi Kualitas Pembiayaan</small>
            </div>
            <div class="card-body">
                @foreach($collectibilityStats as $stat)
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-xs me-2">
                                <span class="avatar-initial rounded bg-label-{{ $stat['color'] }}">
                                    <i class="ti ti-circle-check"></i>
                                </span>
                            </div>
                            <span class="fw-medium">{{ $stat['label'] }}</span>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block">{{ number_format($stat['count']) }} kontrak</small>
                            <strong class="text-{{ $stat['color'] }}">{{ $stat['percentage'] }}%</strong>
                        </div>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-{{ $stat['color'] }}" role="progressbar" style="width: {{ $stat['percentage'] }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Top Produk Card -->
    <div class="col-lg-4 col-md-6 col-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-1 text-success">🏆 Top 5 Produk</h5>
                <small class="text-muted">Berdasarkan Outstanding</small>
            </div>
            <div class="card-body">
                <div class="table-responsive text-nowrap">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="text-end">Kontrak</th>
                                <th class="text-end">Outstanding</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topProducts as $index => $product)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-label-success me-2">{{ $index + 1 }}</span>
                                        <div>
                                            <strong>{{ $product->nama_produk }}</strong>
                                            <br>
                                            <small class="text-muted">Kode: {{ $product->kdprd }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end">{{ number_format($product->total_kontrak) }}</td>
                                <td class="text-end">
                                    <strong class="text-success">
                                        @if($product->total_outstanding >= 1000000000)
                                            {{ number_format($product->total_outstanding / 1000000000, 2) }} M
                                        @elseif($product->total_outstanding >= 1000000)
                                            {{ number_format($product->total_outstanding / 1000000, 2) }} Jt
                                        @else
                                            {{ number_format($product->total_outstanding / 1000, 0) }} rb
                                        @endif
                                    </strong>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Area Card -->
    <div class="col-lg-4 col-md-6 col-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-1 text-info">📍 Top 5 Account Officer</h5>
                <small class="text-muted">Berdasarkan Outstanding</small>
            </div>
            <div class="card-body">
                <div class="table-responsive text-nowrap">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Account Officer</th>
                                <th class="text-end">Kontrak</th>
                                <th class="text-end">Outstanding</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topAreas as $index => $area)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-label-info me-2">{{ $index + 1 }}</span>
                                        <div>
                                            <strong>{{ $area->nama_ao }}</strong>
                                            <br>
                                            <small class="text-muted">Kode: {{ $area->kdaoh }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end">{{ number_format($area->total_kontrak) }}</td>
                                <td class="text-end">
                                    <strong class="text-info">
                                        @if($area->total_outstanding >= 1000000000)
                                            {{ number_format($area->total_outstanding / 1000000000, 2) }} M
                                        @elseif($area->total_outstanding >= 1000000)
                                            {{ number_format($area->total_outstanding / 1000000, 2) }} Jt
                                        @else
                                            {{ number_format($area->total_outstanding / 1000, 0) }} rb
                                        @endif
                                    </strong>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 4: Additional Charts -->
<div class="row">
    <!-- Portfolio Summary Card -->
    <div class="col-lg-3 col-md-6 col-12 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">📊 Portfolio Summary</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted">Total Kontrak</span>
                    <span class="fw-bold">{{ number_format($portfolioSummary['total_kontrak']) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted">Total Outstanding</span>
                    <span class="fw-bold text-primary">{{ number_format($portfolioSummary['total_outstanding'] / 1000000, 0) }} Jt</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted">Total Plafon</span>
                    <span class="fw-bold">{{ number_format($portfolioSummary['total_plafon'] / 1000000, 0) }} Jt</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted">Utilisasi</span>
                    <span class="badge bg-success">{{ $portfolioSummary['utilisasi'] }}%</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">Avg per Kontrak</span>
                    <span class="fw-bold">{{ number_format($portfolioSummary['avg_outstanding'] / 1000, 0) }} rb</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolektibilitas Distribution Chart -->
    <div class="col-lg-5 col-md-6 col-12 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">📈 Distribusi Kolektibilitas</h5>
                <small class="text-muted">Outstanding berdasarkan kualitas</small>
            </div>
            <div class="card-body">
                <div id="kolektibilitasChart"></div>
            </div>
        </div>
    </div>

    <!-- Top Products Bar Chart -->
    <div class="col-lg-4 col-md-12 col-12 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">🏆 Top 5 Produk</h5>
                <small class="text-muted">Outstanding terbesar (Miliar)</small>
            </div>
            <div class="card-body">
                <div id="topProductsBarChart"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Segmentasi -->
<div class="modal fade" id="segmentDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalSegmentTitle">Detail Segmentasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalSegmentBody">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function() {
    'use strict';

    console.log('=== Dashboard Chart Initialization ===');
    console.log('ApexCharts available:', typeof ApexCharts !== 'undefined');

    if (typeof ApexCharts === 'undefined') {
        console.error('ERROR: ApexCharts library not loaded!');
        return;
    }

    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM Content Loaded - Starting chart initialization');

        // Monthly Trend Chart (Line Chart)
        const monthlyTrendData = {
            labels: @json($monthlyTrends['labels']),
            funding: @json($monthlyTrends['funding']),
            lending: @json($monthlyTrends['lending'])
        };

        console.log('Monthly Trends Data:', monthlyTrendData);

    const monthlyTrendOptions = {
        series: [{
            name: 'Plafon',
            data: monthlyTrendData.funding
        }, {
            name: 'Outstanding',
            data: monthlyTrendData.lending
        }],
        chart: {
            height: 320,
            type: 'line',
            toolbar: {
                show: true,
                tools: {
                    download: true,
                    zoom: true,
                    zoomin: true,
                    zoomout: true,
                    pan: true,
                    reset: true
                }
            },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800
            }
        },
        stroke: {
                curve: 'smooth',
                width: 4
            },
            colors: ['#7367F0', '#28C76F'],
            dataLabels: {
                enabled: false
            },
            markers: {
                size: 6,
                colors: ['#7367F0', '#28C76F'],
                strokeColors: '#fff',
                strokeWidth: 2,
                hover: {
                    size: 8,
                    sizeOffset: 3
                }
            },
            grid: {
                borderColor: '#f1f1f1',
                strokeDashArray: 4,
                xaxis: {
                    lines: {
                        show: true
                    }
                }
            },
            xaxis: {
                categories: @json($monthlyTrends['labels']),
                labels: {
                    style: {
                        colors: '#6c757d',
                        fontSize: '13px'
                    }
                }
            },
            yaxis: {
                title: {
                    text: 'Miliar Rupiah',
                    style: {
                        color: '#6c757d',
                        fontSize: '13px',
                        fontWeight: 500
                    }
                },
                labels: {
                    formatter: function(val) {
                        return 'Rp ' + val.toFixed(1) + 'M';
                    },
                    style: {
                        colors: '#6c757d',
                        fontSize: '13px'
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                fontSize: '14px',
                markers: {
                    width: 12,
                    height: 12,
                    radius: 12
                },
                itemMargin: {
                    horizontal: 10,
                    vertical: 5
                }
            },
            tooltip: {
                shared: true,
                intersect: false,
                theme: 'light',
                y: {
                    formatter: function(val) {
                        return 'Rp ' + val.toFixed(2) + ' Miliar';
                    }
                }
            }
        }
    };

    const monthlyTrendChartElement = document.querySelector("#monthlyTrendChart");
    if (monthlyTrendChartElement) {
        console.log('Rendering monthly trend chart...');
        const monthlyTrendChart = new ApexCharts(
            monthlyTrendChartElement,
            monthlyTrendOptions
        );
        monthlyTrendChart.render().then(() => {
            console.log('Monthly trend chart rendered successfully');
        }).catch((error) => {
            console.error('Error rendering monthly trend chart:', error);
        });
    } else {
        console.error('Monthly trend chart element not found!');
    }

    // NPF Distribution Chart (Donut Chart)
    const npfDistributionOptions = {
        series: @json($npfDistribution['values']),
        chart: {
            height: 280,
            type: 'donut',
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800,
                animateGradually: {
                    enabled: true,
                    delay: 150
                },
                dynamicAnimation: {
                    enabled: true,
                    speed: 350
                }
            }
        },
        labels: @json($npfDistribution['labels']),
        colors: ['#FF9F43', '#EA5455', '#343A40'],
        plotOptions: {
            pie: {
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        name: {
                            fontSize: '16px',
                            fontFamily: 'Public Sans, sans-serif',
                            fontWeight: 500,
                            color: '#6c757d',
                            offsetY: -10
                        },
                        value: {
                            fontSize: '24px',
                            fontFamily: 'Public Sans, sans-serif',
                            fontWeight: 700,
                            color: '#343a40',
                            offsetY: 10,
                            formatter: function(val) {
                                return val + '%';
                            }
                        },
                        total: {
                            show: true,
                            label: 'Total NPF',
                            fontSize: '16px',
                            fontWeight: 500,
                            color: '#6c757d',
                            formatter: function() {
                                return '100%';
                            }
                        }
                    }
                },
                expandOnClick: true
            }
        },
        states: {
            hover: {
                filter: {
                    type: 'lighten',
                    value: 0.15
                }
            },
            active: {
                filter: {
                    type: 'darken',
                    value: 0.15
                }
            }
        },
        legend: {
            show: false
        },
        dataLabels: {
            enabled: true,
            style: {
                fontSize: '14px',
                fontFamily: 'Public Sans, sans-serif',
                fontWeight: 600,
                colors: ['#fff']
            },
            formatter: function(val) {
                return val.toFixed(0) + '%';
            },
            dropShadow: {
                enabled: true,
                top: 1,
                left: 1,
                blur: 1,
                color: '#000',
                opacity: 0.45
            }
        },
        tooltip: {
            theme: 'light',
            y: {
                formatter: function(val) {
                    return val + '% dari total NPF';
                }
                }
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        height: 250
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
        }]
    };

    const npfDistributionElement = document.querySelector("#npfDistributionChart");
    if (npfDistributionElement) {
        console.log('Rendering NPF distribution chart...');
        const npfDistributionChart = new ApexCharts(
            npfDistributionElement,
            npfDistributionOptions
        );
        npfDistributionChart.render();
        console.log('NPF distribution chart rendered');
    } else {
        console.error('NPF distribution chart element not found!');
    }

    // Segmentasi Pie Chart
    const segmentasiChartEl = document.querySelector('#segmentasiPieChart');
    if (typeof segmentasiChartEl !== 'undefined' && segmentasiChartEl !== null) {
        const segmentasiData = @json($segmentasiDistribution);
        console.log('Segmentasi Distribution:', segmentasiData);

        if (segmentasiData && segmentasiData.values && segmentasiData.values.length > 0) {
            const segmentasiPieConfig = {
                chart: {
                    height: 250,
                    type: 'pie',
                    parentHeightOffset: 0
                },
                labels: segmentasiData.labels,
                series: segmentasiData.values,
                colors: ['#696cff', '#71dd37', '#ff3e1d', '#ffab00', '#8592a3', '#00cfe8', '#ea5455', '#28c76f', '#03c3ec', '#826bf8', '#2b9bf4'],
                stroke: {
                    width: 0
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        return val.toFixed(1) + '%';
                    }
                },
                legend: {
                    show: true,
                    position: 'bottom',
                    fontSize: '13px',
                    fontFamily: 'Public Sans',
                    labels: {
                        colors: '#6c757d'
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return 'Rp ' + val.toFixed(2) + ' Miliar';
                        }
                    }
                },
                plotOptions: {
                    pie: {
                        expandOnClick: true
                    }
                }
            };

            const segmentasiPieChart = new ApexCharts(segmentasiChartEl, segmentasiPieConfig);
            segmentasiPieChart.render();
            console.log('Segmentasi pie chart rendered');
        } else {
            console.warn('No segmentasi data available');
        }
    }

    // Kolektibilitas Donut Chart
    const kolektibilitasChartEl = document.querySelector('#kolektibilitasChart');
    if (typeof kolektibilitasChartEl !== 'undefined' && kolektibilitasChartEl !== null) {
        const kolektibilitasConfig = {
            chart: {
                height: 280,
                type: 'donut',
                parentHeightOffset: 0
            },
            labels: @json($kolektibilitasDistribution['labels']),
            series: @json($kolektibilitasDistribution['series']),
            colors: ['#28c76f', '#00cfe8', '#ffab00', '#ff6b6b', '#ea5455'],
            stroke: {
                width: 0
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return val.toFixed(1) + '%';
                }
            },
            legend: {
                show: true,
                position: 'bottom',
                fontSize: '13px',
                fontFamily: 'Public Sans'
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return 'Rp ' + val.toFixed(2) + ' Miliar';
                    }
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            value: {
                                fontSize: '1.5rem',
                                fontFamily: 'Public Sans',
                                fontWeight: 500,
                                formatter: function(val) {
                                    return parseFloat(val).toFixed(2) + 'M';
                                }
                            },
                            name: {
                                fontSize: '0.875rem',
                                fontFamily: 'Public Sans'
                            },
                            total: {
                                show: true,
                                fontSize: '0.875rem',
                                label: 'Total',
                                formatter: function(w) {
                                    const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    return total.toFixed(2) + 'M';
                                }
                            }
                        }
                    }
                }
            }
        };
        const kolektibilitasChart = new ApexCharts(kolektibilitasChartEl, kolektibilitasConfig);
        kolektibilitasChart.render();
    }

    // Top Products Bar Chart
    const topProductsBarChartEl = document.querySelector('#topProductsBarChart');
    if (typeof topProductsBarChartEl !== 'undefined' && topProductsBarChartEl !== null) {
        const topProductsConfig = {
            chart: {
                type: 'bar',
                height: 280,
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    barHeight: '70%',
                    borderRadius: 4,
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            dataLabels: {
                enabled: true,
                offsetX: 30,
                style: {
                    fontSize: '12px',
                    colors: ['#304758']
                },
                formatter: function(val) {
                    return val.toFixed(2) + ' M';
                }
            },
            series: [{
                name: 'Outstanding',
                data: @json($topProductsChart['data'])
            }],
            colors: ['#696cff'],
            xaxis: {
                categories: @json($topProductsChart['categories']),
                labels: {
                    formatter: function(val) {
                        return val.toFixed(1) + 'M';
                    }
                }
            },
            grid: {
                borderColor: '#f1f1f1',
                xaxis: {
                    lines: {
                        show: true
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return 'Rp ' + val.toFixed(2) + ' Miliar';
                    }
                }
            }
        };
        const topProductsBarChart = new ApexCharts(topProductsBarChartEl, topProductsConfig);
        topProductsBarChart.render();
        console.log('Top products bar chart rendered');
    }

    // Event listener untuk klik pada baris segmentasi
    const segmentRows = document.querySelectorAll('.segment-row');
    console.log('Found segment rows:', segmentRows.length);

    segmentRows.forEach(row => {
        row.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            const type = this.getAttribute('data-type');
            console.log('Row clicked:', category, type);
            if (category && type) {
                showSegmentDetail(category, type);
            }
        });
    });

    console.log('=== All charts initialized successfully ===');
}); // End of DOMContentLoaded

// Fungsi untuk menampilkan detail segmentasi - expose to window
window.showSegmentDetail = function(category, type) {
    console.log('showSegmentDetail called:', category, type);

    // Show modal
    const modalElement = document.getElementById('segmentDetailModal');
    if (!modalElement) {
        console.error('Modal element not found');
        return;
    }

    const modal = new bootstrap.Modal(modalElement);
    modal.show();

    // Set loading state
    document.getElementById('modalSegmentTitle').textContent = type + ' - ' + category;
    document.getElementById('modalSegmentBody').innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

    // Fetch detail data
    const url = `/dashboard/segmentasi-detail/${encodeURIComponent(category)}/${encodeURIComponent(type)}`;
    console.log('Fetching URL:', url);

    fetch(url)
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Data received:', data);
            let html = '';

            // Summary
        html += '<div class="alert alert-info mb-3">';
        html += '<div class="row text-center">';
        html += '<div class="col-4"><strong>Total Kontrak</strong><br>' + data.summary.total_kontrak.toLocaleString() + '</div>';
        html += '<div class="col-4"><strong>Outstanding</strong><br>Rp ' + Math.round(data.summary.total_outstanding).toLocaleString() + '</div>';
        html += '<div class="col-4"><strong>Disburse</strong><br>Rp ' + Math.round(data.summary.total_disburse).toLocaleString() + '</div>';
        html += '</div>';
        html += '</div>';

        // Table
        html += '<div class="table-responsive">';
        html += '<table class="table table-sm table-striped">';
        html += '<thead><tr>';
        html += '<th>No. Kontrak</th>';
        html += '<th>Nama</th>';
        html += '<th class="text-end">Outstanding</th>';
        html += '<th class="text-end">Disburse</th>';
        html += '<th class="text-center">Kol</th>';
        html += '</tr></thead>';
        html += '<tbody>';

        data.details.forEach((item, index) => {
            const colClass = item.colbaru >= 3 ? 'text-danger' : 'text-success';
            html += '<tr>';
            html += '<td><small>' + item.nokontrak + '</small></td>';
            html += '<td><small>' + item.nama + '</small></td>';
            html += '<td class="text-end"><small>Rp ' + Math.round(item.osmdlc).toLocaleString() + '</small></td>';
            html += '<td class="text-end"><small>Rp ' + Math.round(item.mdlawal).toLocaleString() + '</small></td>';
            html += '<td class="text-center"><span class="badge bg-label-' + (item.colbaru >= 3 ? 'danger' : 'success') + '">' + item.colbaru_label + '</span></td>';
            html += '</tr>';
        });

        html += '</tbody></table>';
        html += '</div>';

        if (data.details.length >= 100) {
            html += '<div class="alert alert-warning mt-2"><small>Menampilkan 100 data teratas</small></div>';
        }

    document.getElementById('modalSegmentBody').innerHTML = html;
    })
    .catch(error => {
        document.getElementById('modalSegmentBody').innerHTML = '<div class="alert alert-danger">Gagal memuat data</div>';
        console.error('Error:', error);
    });
}; // End of showSegmentDetail

</script>

@endsection
