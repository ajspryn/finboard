@extends('layouts.app')

@section('title', 'Dashboard Bank')

@php
// Helper function untuk format nominal dengan satuan yang jelas
function formatNominal($amount) {
    if ($amount >= 1000000000) {
        return 'Rp ' . number_format($amount / 1000000000, 2) . ' M'; // Miliar
    } elseif ($amount >= 1000000) {
        return 'Rp ' . number_format($amount / 1000000, 2) . ' Jt'; // Juta
    } elseif ($amount >= 100000) {
        return 'Rp ' . number_format($amount / 1000, 0) . ' Rb'; // Ratusan Ribu
    } elseif ($amount >= 1000) {
        return 'Rp ' . number_format($amount / 1000, 1) . ' Rb'; // Ribuan
    } else {
        return 'Rp ' . number_format($amount, 0); // Di bawah ribu
    }
}
@endphp

@section('styles')
<link rel="stylesheet" href="/template/assets/vendor/libs/apex-charts/apex-charts.css" />
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
    }
    .segment-row {
        cursor: pointer;
    }
    .segment-row:hover {
        background-color: #f0f7ff !important;
        transition: background-color 0.2s ease;
    }
    .kol-cell:hover {
        background-color: #fff3cd !important;
        transition: background-color 0.2s ease;
        transform: scale(1.05);
    }
    .kol-cell {
        transition: all 0.2s ease;
    }
    .npf-badge:hover {
        transform: scale(1.1);
        box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        transition: all 0.2s ease;
    }
    .npf-badge {
        transition: all 0.2s ease;
    }
    .avatar-initial {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .nasabah-status-card {
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .nasabah-status-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2) !important;
    }
    .nasabah-status-card:active {
        transform: translateY(-2px);
    }
    /* Make ApexCharts markers more clickable */
    #nasabahTrendChart .apexcharts-marker {
        cursor: pointer !important;
        pointer-events: all !important;
    }
    #nasabahTrendChart .apexcharts-series path {
        cursor: pointer !important;
    }
    #nasabahTrendChart .apexcharts-data-labels {
        cursor: pointer !important;
    }
</style>
@endsection

@section('content')

    <!-- Row 1: KPI Cards Detail (Funding, Lending, NPF) -->
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
                                    {{ formatNominal($funding['total']) }}
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
                                    <span class="avatar-initial rounded bg-label-{{ $type == 'Deposito' ? 'success' : 'info' }}">
                                        <i class="ti ti-{{ $type == 'Deposito' ? 'clock-dollar' : 'piggy-bank' }}"></i>
                                    </span>
                                </div>
                                <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                    <div class="me-2">
                                        <small class="text-muted d-block mb-1">{{ $type }}</small>
                                        <small class="text-primary fw-medium">
                                            {{ formatNominal($funding['nominal'][$type]) }}
                                        </small>
                                    </div>
                                    <div class="user-progress d-flex align-items-center gap-1">
                                        <h6 class="mb-0">{{ $percentage }}%</h6>
                                    </div>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="mt-4">
                        <h6 class="mb-3">Top 5 Nasabah</h6>
                        <ul class="list-unstyled mb-0">
                            @forelse($funding['top_customers'] ?? [] as $customer)
                            <li class="d-flex mb-3">
                                <div class="avatar flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded-circle bg-label-{{ $customer['type'] == 'Deposito' ? 'success' : 'primary' }}">
                                        <i class="ti ti-{{ $customer['type'] == 'Deposito' ? 'clock-dollar' : 'piggy-bank' }}"></i>
                                    </span>
                                </div>
                                <div class="d-flex w-100 flex-column">
                                    <div class="d-flex justify-content-between mb-1">
                                        <h6 class="mb-0">{{ Str::limit($customer['name'], 25) }}</h6>
                                        <small class="text-muted">{{ $customer['type'] }}</small>
                                    </div>
                                    <small class="text-muted">{{ $customer['account'] }}</small>
                                    <small class="text-primary fw-medium">
                                        {{ formatNominal($customer['amount']) }}
                                    </small>
                                </div>
                            </li>
                            @empty
                            <li class="text-center text-muted">
                                <small>Belum ada data nasabah</small>
                            </li>
                            @endforelse
                        </ul>
                    </div>

                    <!-- Pencairan Deposito -->
                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Pencairan Deposito</h6>
                                <small class="text-muted">Bulan ini</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-label-warning mb-1">{{ number_format($funding['pencairan']['jumlah']) }} Bilyet</span>
                                <div>
                                    <small class="text-warning fw-medium">
                                        <i class="ti ti-arrow-down-circle"></i>
                                        {{ formatNominal($funding['pencairan']['total']) }}
                                    </small>
                                </div>
                            </div>
                        </div>
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
                                    {{ formatNominal($lending['total']) }}
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
                                        {{ formatNominal($lending['total']) }}
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
                                        {{ formatNominal($lending['plafon_awal']) }}
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
                            <small class="text-muted d-block mb-2">Outstanding per Segmentasi (Miliar Rupiah)</small>
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
                                    {{ formatNominal($npf['total']) }}
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

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <small class="text-muted">
                                <i class="ti ti-clock-exclamation me-1"></i>Tunggakan Pokok NPF
                            </small>
                            <strong class="text-danger">
                                {{ formatNominal($npf['tunggakan_pokok']) }}
                            </strong>
                        </div>                        <div class="alert alert-warning mb-3" role="alert">
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

                        <!-- Top 5 Nasabah Penyumbang NPF -->
                        <div class="border-top pt-3">
                            <h6 class="mb-3 text-danger">
                                <i class="ti ti-users-group me-1"></i>
                                Top 5 Nasabah NPF
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
                                            {{ formatNominal($contributor['osmdlc']) }}
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

    <!-- Row 5: Funding Charts & Tables -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">📈 Trend Funding</h5>
                        <small class="text-muted">Tabungan dan Deposito</small>
                    </div>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnFundingTrendNominal" onclick="toggleFundingTrendChart('nominal')">
                            <i class="ti ti-cash ti-xs me-1"></i> Nominal
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" id="btnFundingTrendJumlah" onclick="toggleFundingTrendChart('jumlah')">
                            <i class="ti ti-users ti-xs me-1"></i> Jumlah
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
                        <i class="ti ti-info-circle me-1"></i>
                        <small><strong>Tip:</strong> Klik pada titik data di chart untuk melihat detail funding</small>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <div id="fundingTrendChart" style="cursor: pointer;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 5.5: Product Trend Charts -->
    <div class="row mb-4">
        <div class="col-xl-6 col-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">📊 Trend Produk Tabungan</h5>
                        <small class="text-muted">Perkembangan Produk per Bulan</small>
                    </div>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnTabunganTrendNominal" onclick="toggleTabunganTrendChart('nominal')">
                            <i class="ti ti-cash ti-xs me-1"></i> Nominal
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" id="btnTabunganTrendJumlah" onclick="toggleTabunganTrendChart('jumlah')">
                            <i class="ti ti-users ti-xs me-1"></i> Jumlah
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="tabunganTrendChart"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-6 col-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">📊 Trend Produk Deposito</h5>
                        <small class="text-muted">Perkembangan Produk per Bulan</small>
                    </div>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnDepositoTrendNominal" onclick="toggleDepositoTrendChart('nominal')">
                            <i class="ti ti-cash ti-xs me-1"></i> Nominal
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" id="btnDepositoTrendJumlah" onclick="toggleDepositoTrendChart('jumlah')">
                            <i class="ti ti-users ti-xs me-1"></i> Jumlah
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="depositoTrendChart"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 6: Funding Detail Tables -->
    <div class="row mb-4">
        <div class="col-xl-6 col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">💰 Top 10 Tabungan</h5>
                    <small class="text-muted">Berdasarkan Saldo Tertinggi</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>No. Rekening</th>
                                    <th>Nama</th>
                                    <th class="text-end">Saldo</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($fundingDetails['tabungan'] as $index => $tab)
                                <tr>
                                    <td><strong>{{ $index + 1 }}</strong></td>
                                    <td><code>{{ $tab->notab }}</code></td>
                                    <td>{{ Str::limit($tab->fnama ?? 'N/A', 25) }}</td>
                                    <td class="text-end">
                                        <strong>
                                            {{ formatNominal($tab->sahirrp) }}
                                        </strong>
                                    </td>
                                    <td class="text-center">
                                        @if($tab->stsrec == 'A')
                                            <span class="badge bg-label-success">Aktif</span>
                                        @else
                                            <span class="badge bg-label-secondary">{{ $tab->stsrec }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Belum ada data</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">🏦 Top 10 Deposito</h5>
                    <small class="text-muted">Berdasarkan Nominal Tertinggi</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>No. Deposito</th>
                                    <th>Nama</th>
                                    <th class="text-end">Nominal</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($fundingDetails['deposito'] as $index => $dep)
                                <tr>
                                    <td><strong>{{ $index + 1 }}</strong></td>
                                    <td><code>{{ $dep->nodep }}</code></td>
                                    <td>{{ Str::limit($dep->nama ?? 'N/A', 25) }}</td>
                                    <td class="text-end">
                                        <strong>
                                            {{ formatNominal($dep->nomrp) }}
                                        </strong>
                                    </td>
                                    <td class="text-center">
                                        @if($dep->stsrec == 'A')
                                            <span class="badge bg-label-success">Aktif</span>
                                        @else
                                            <span class="badge bg-label-secondary">{{ $dep->stsrec }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Belum ada data</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 7: Nasabah dengan Tabungan DAN Deposito -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">👥 Top 50 Nasabah dengan Total Saldo Terbesar</h5>
                        <small class="text-muted">Gabungan Tabungan & Deposito</small>
                    </div>
                    <div>
                        <span class="badge bg-label-primary">{{ number_format($nasabahBothFunding->count()) }} Nasabah</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-hover">
                            <thead class="sticky-top bg-white">
                                <tr>
                                    <th>#</th>
                                    <th>No. CIF</th>
                                    <th>Nama Nasabah</th>
                                    <th class="text-center">Jml Tabungan</th>
                                    <th class="text-end">Total Tabungan</th>
                                    <th class="text-center">Jml Deposito</th>
                                    <th class="text-end">Total Deposito</th>
                                    <th class="text-end">Total Funding</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($nasabahBothFunding as $index => $nasabah)
                                <tr>
                                    <td><strong>{{ $index + 1 }}</strong></td>
                                    <td><code>{{ $nasabah->nocif }}</code></td>
                                    <td>{{ Str::limit($nasabah->nama ?? 'N/A', 30) }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-label-info">{{ $nasabah->jumlah_tabungan }}</span>
                                    </td>
                                    <td class="text-end">
                                        {{ formatNominal($nasabah->total_tabungan) }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-label-success">{{ $nasabah->jumlah_deposito }}</span>
                                    </td>
                                    <td class="text-end">
                                        {{ formatNominal($nasabah->total_deposito) }}
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-primary">
                                            {{ formatNominal($nasabah->total_funding) }}
                                        </strong>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Belum ada data</td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($nasabahBothFunding->count() > 0)
                            <tfoot class="table-light sticky-bottom bg-white" style="box-shadow: 0 -2px 4px rgba(0,0,0,0.1);">
                                <tr>
                                    <td colspan="3" class="text-end"><strong>TOTAL (Top 50)</strong></td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ number_format($nasabahBothFunding->sum('jumlah_tabungan')) }}</span>
                                    </td>
                                    <td class="text-end">
                                        <strong>
                                            @php $totalTab = $nasabahBothFunding->sum('total_tabungan'); @endphp
                                            {{ formatNominal($totalTab) }}
                                        </strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success">{{ number_format($nasabahBothFunding->sum('jumlah_deposito')) }}</span>
                                    </td>
                                    <td class="text-end">
                                        <strong>
                                            @php $totalDep = $nasabahBothFunding->sum('total_deposito'); @endphp
                                            {{ formatNominal($totalDep) }}
                                        </strong>
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-primary">
                                            @php $totalAll = $nasabahBothFunding->sum('total_funding'); @endphp
                                            {{ formatNominal($totalAll) }}
                                        </strong>
                                    </td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Charts (Monthly Trends & NPF Distribution) -->
    <div class="row">
        <!-- Monthly Trends Chart -->
        <div class="col-lg-8 mb-4">
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
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">📊 Distribusi NPF</h5>
                    <small class="text-muted">Per Segmentasi (Miliar Rupiah)</small>
                </div>
                <div class="card-body">
                    <div id="npfDistributionChart"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 3: Additional Charts -->
    <div class="row">
        <!-- Kolektibilitas Donut Chart -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">📈 Kolektibilitas</h5>
                    <small class="text-muted">Outstanding per Kualitas (Miliar)</small>
                </div>
                <div class="card-body">
                    <div id="kolektibilitasChart"></div>
                </div>
            </div>
        </div>

        <!-- Top Products Bar Chart -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">🏆 Top 5 Produk</h5>
                    <small class="text-muted">Outstanding Terbesar (Miliar)</small>
                </div>
                <div class="card-body">
                    <div id="topProductsBarChart"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row: Trend Kontrak Per Bulan (6 Bulan Terakhir) -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">📈 Trend Kontrak Per Bulan</h5>
                        <small class="text-muted">Perbandingan Kontrak Baru, Pelunasan Cepat, dan Kontrak Lunas (6 Bulan Terakhir)</small>
                    </div>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-primary" id="btnTrendJumlah" onclick="toggleTrendChart('jumlah')">
                            <i class="ti ti-users ti-xs me-1"></i> Jumlah
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnTrendNominal" onclick="toggleTrendChart('nominal')">
                            <i class="ti ti-cash ti-xs me-1"></i> Nominal
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
                        <i class="ti ti-info-circle me-1"></i>
                        <small><strong>Tip:</strong> Klik pada titik data di chart untuk melihat detail kontrak</small>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <div id="nasabahTrendChart" style="cursor: pointer;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 4: Top 5 AO Performance -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">🏆 Top 5 Performa Account Officer</h5>
                        <small class="text-muted">Berdasarkan Total Outstanding</small>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama AO</th>
                                    <th class="text-center">Jumlah Nasabah</th>
                                    <th class="text-end">Total Outstanding</th>
                                    <th class="text-end">Total Plafon</th>
                                    <th class="text-center">Jumlah NPF</th>
                                    <th class="text-center">NPF Ratio</th>
                                    <th style="width: 200px;">Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topAOData as $index => $ao)
                                <tr class="ao-row" data-ao="{{ $ao['nmao'] }}" style="cursor: pointer;">
                                    <td><strong>{{ $index + 1 }}</strong></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-2">
                                                <span class="avatar-initial rounded-circle bg-label-primary">
                                                    {{ strtoupper(substr($ao['nmao'], 0, 2)) }}
                                                </span>
                                            </div>
                                            <strong>{{ $ao['nmao'] }}</strong>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-label-info">{{ number_format($ao['total_nasabah']) }} Nasabah</span>
                                    </td>
                                    <td class="text-end">
                                        <strong>
                                            {{ formatNominal($ao['total_outstanding']) }}
                                        </strong>
                                    </td>
                                    <td class="text-end">
                                        <strong>
                                            {{ formatNominal($ao['total_plafon']) }}
                                        </strong>
                                    </td>
                                    <td class="text-center">
                                        @if($ao['jumlah_npf'] > 0)
                                            <span class="badge bg-label-danger npf-badge"
                                                style="cursor: pointer;"
                                                onclick="showAONpfDetail(event, '{{ $ao['nmao'] }}')"
                                                title="Klik untuk melihat detail NPF">
                                                {{ $ao['jumlah_npf'] }} NPF
                                            </span>
                                        @else
                                            <span class="badge bg-label-success">0 NPF</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $npfClass = $ao['npf_ratio'] >= 5 ? 'danger' : ($ao['npf_ratio'] >= 2 ? 'warning' : 'success');
                                        @endphp
                                        <span class="badge bg-label-{{ $npfClass }}">{{ number_format($ao['npf_ratio'], 2) }}%</span>
                                    </td>
                                    <td>
                                        @php
                                            $performanceScore = 100 - $ao['npf_ratio'];
                                            $performanceClass = $performanceScore >= 95 ? 'success' : ($performanceScore >= 90 ? 'primary' : ($performanceScore >= 85 ? 'warning' : 'danger'));
                                        @endphp
                                        <div class="progress" style="height: 25px;">
                                            <div class="progress-bar bg-{{ $performanceClass }}" role="progressbar"
                                                style="width: {{ $performanceScore }}%;"
                                                aria-valuenow="{{ $performanceScore }}"
                                                aria-valuemin="0"
                                                aria-valuemax="100">
                                                <small><strong>{{ number_format($performanceScore, 1) }}%</strong></small>
                                            </div>
                                        </div>
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

    <!-- Row 5: Peta Sebaran Nasabah per Kecamatan -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">🗺️ Sebaran Nasabah per Kecamatan</h5>
                        <small class="text-muted">Jumlah Nasabah & Outstanding</small>
                    </div>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-primary" id="btnShowMap" onclick="toggleView('map')">
                            <i class="ti ti-map"></i> Peta
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnShowTable" onclick="toggleView('table')">
                            <i class="ti ti-table"></i> Tabel
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Map View -->
                    <div id="mapView" style="display: block;">
                        <div id="map" style="height: 500px; width: 100%; border-radius: 8px;"></div>
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="ti ti-info-circle"></i> Klik marker untuk melihat detail nasabah di kecamatan tersebut
                            </small>
                        </div>
                    </div>

                    <!-- Table View -->
                    <div id="tableView" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-hover" id="kecamatanTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th class="sortable" data-sort="kecamatan" style="cursor: pointer;">
                                        Kecamatan <i class="ti ti-selector"></i>
                                    </th>
                                    <th class="sortable text-center" data-sort="nasabah" style="cursor: pointer;">
                                        Jumlah Nasabah <i class="ti ti-selector"></i>
                                    </th>
                                    <th class="sortable text-end" data-sort="outstanding" style="cursor: pointer;">
                                        Total Outstanding <i class="ti ti-selector"></i>
                                    </th>
                                    <th class="sortable text-center" data-sort="persentase" style="cursor: pointer;">
                                        Persentase <i class="ti ti-selector"></i>
                                    </th>
                                    <th style="width: 200px;">Distribusi</th>
                                </tr>
                            </thead>
                            <tbody id="kecamatanTableBody">
                                @php
                                    $totalNasabahKec = $kecamatanData->sum('total_nasabah');
                                    $totalOutstandingKec = $kecamatanData->sum('total_outstanding');
                                @endphp
                                @foreach($kecamatanData as $index => $kec)
                                <tr class="kecamatan-row"
                                    data-kecamatan="{{ $kec['kecamatan'] }}"
                                    data-nasabah="{{ $kec['total_nasabah'] }}"
                                    data-outstanding="{{ $kec['total_outstanding'] }}"
                                    data-persentase="{{ $totalNasabahKec > 0 ? ($kec['total_nasabah'] / $totalNasabahKec) * 100 : 0 }}"
                                    style="cursor: pointer;">
                                    <td><strong>{{ $index + 1 }}</strong></td>
                                    <td>
                                        <strong>{{ $kec['kecamatan'] }}</strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-label-primary">{{ number_format($kec['total_nasabah']) }} Nasabah</span>
                                    </td>
                                    <td class="text-end">
                                        <strong>
                                            {{ formatNominal($kec['total_outstanding']) }}
                                        </strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-label-success">{{ $totalNasabahKec > 0 ? number_format(($kec['total_nasabah'] / $totalNasabahKec) * 100, 1) : 0 }}%</span>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 25px;">
                                            <div class="progress-bar bg-primary" role="progressbar"
                                                style="width: {{ $totalNasabahKec > 0 ? ($kec['total_nasabah'] / $totalNasabahKec) * 100 : 0 }}%;"
                                                aria-valuenow="{{ $kec['total_nasabah'] }}"
                                                aria-valuemin="0"
                                                aria-valuemax="{{ $totalNasabahKec }}">
                                                <small>{{ $kec['total_nasabah'] }}</small>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                                <tr class="table-active fw-bold">
                                    <td colspan="2" class="text-end">TOTAL</td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">{{ number_format($totalNasabahKec) }} Nasabah</span>
                                    </td>
                                    <td class="text-end">
                                        {{ formatNominal($totalOutstandingKec) }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success">100%</span>
                                    </td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    </div>
                    <!-- End Table View -->
                </div>
            </div>
        </div>
    </div>

    <!-- Row 8: Segmentasi Table -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">📊 Tabel Segmentasi Outstanding & Disburse</h5>
                </div>
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
                                        <td class="text-center kol-cell" style="line-height: 1.2; cursor: pointer;"
                                            onclick="showSegmentKolDetail(event, '{{ $segment['category'] }}', '{{ $segment['type'] }}', '1')"
                                            title="Klik untuk melihat detail nasabah KOL 1">
                                            <div style="font-size: 14px;">
                                                @if(($segment['col1_sum'] ?? 0) >= 1000000000)
                                                    {{ number_format(($segment['col1_sum'] ?? 0) / 1000000000, 1) }}M
                                                @else
                                                    {{ number_format(($segment['col1_sum'] ?? 0) / 1000000, 0) }}jt
                                                @endif
                                            </div>
                                            <small class="text-muted" style="font-size: 9px;">{{ $segment['col1'] ?? 0 }} nasabah</small>
                                        </td>
                                        <td class="text-center kol-cell" style="line-height: 1.2; cursor: pointer;"
                                            onclick="showSegmentKolDetail(event, '{{ $segment['category'] }}', '{{ $segment['type'] }}', '2')"
                                            title="Klik untuk melihat detail nasabah KOL 2">
                                            <div style="font-size: 14px;">
                                                @if(($segment['col2_sum'] ?? 0) >= 1000000000)
                                                    {{ number_format(($segment['col2_sum'] ?? 0) / 1000000000, 1) }}M
                                                @else
                                                    {{ number_format(($segment['col2_sum'] ?? 0) / 1000000, 0) }}jt
                                                @endif
                                            </div>
                                            <small class="text-muted" style="font-size: 9px;">{{ $segment['col2'] ?? 0 }} nasabah</small>
                                        </td>
                                        <td class="text-center kol-cell" style="line-height: 1.2; cursor: pointer;"
                                            onclick="showSegmentKolDetail(event, '{{ $segment['category'] }}', '{{ $segment['type'] }}', '3')"
                                            title="Klik untuk melihat detail nasabah KOL 3">
                                            <div style="font-size: 14px;">
                                                @if(($segment['col3_sum'] ?? 0) >= 1000000000)
                                                    {{ number_format(($segment['col3_sum'] ?? 0) / 1000000000, 1) }}M
                                                @else
                                                    {{ number_format(($segment['col3_sum'] ?? 0) / 1000000, 0) }}jt
                                                @endif
                                            </div>
                                            <small class="text-muted" style="font-size: 9px;">{{ $segment['col3'] ?? 0 }} nasabah</small>
                                        </td>
                                        <td class="text-center kol-cell" style="line-height: 1.2; cursor: pointer;"
                                            onclick="showSegmentKolDetail(event, '{{ $segment['category'] }}', '{{ $segment['type'] }}', '4')"
                                            title="Klik untuk melihat detail nasabah KOL 4">
                                            <div style="font-size: 14px;">
                                                @if(($segment['col4_sum'] ?? 0) >= 1000000000)
                                                    {{ number_format(($segment['col4_sum'] ?? 0) / 1000000000, 1) }}M
                                                @else
                                                    {{ number_format(($segment['col4_sum'] ?? 0) / 1000000, 0) }}jt
                                                @endif
                                            </div>
                                            <small class="text-muted" style="font-size: 9px;">{{ $segment['col4'] ?? 0 }} nasabah</small>
                                        </td>
                                        <td class="text-center kol-cell" style="line-height: 1.2; cursor: pointer;"
                                            onclick="showSegmentKolDetail(event, '{{ $segment['category'] }}', '{{ $segment['type'] }}', '5')"
                                            title="Klik untuk melihat detail nasabah KOL 5">
                                            <div style="font-size: 14px;">
                                                @if(($segment['col5_sum'] ?? 0) >= 1000000000)
                                                    {{ number_format(($segment['col5_sum'] ?? 0) / 1000000000, 1) }}M
                                                @else
                                                    {{ number_format(($segment['col5_sum'] ?? 0) / 1000000, 0) }}jt
                                                @endif
                                            </div>
                                            <small class="text-muted" style="font-size: 9px;">{{ $segment['col5'] ?? 0 }} nasabah</small>
                                        </td>
                                        <td class="text-center">{{ number_format($segment['noa']) }}</td>
                                    @else
                                        <td class="text-center"><strong>{{ $segment['type'] }}</strong></td>
                                        <td class="text-end"><strong>{{ number_format($segment['outstanding'], 0, ',', '.') }}</strong></td>
                                        <td class="text-center"><strong>{{ number_format($segment['pct_outstanding'], 2) }}%</strong></td>
                                        <td class="text-end"><strong>{{ number_format($segment['disburse'], 0, ',', '.') }}</strong></td>
                                        <td class="text-center"><strong>{{ number_format($segment['pct_disburse'], 2) }}%</strong></td>
                                        <td class="text-center" style="line-height: 1.2;">
                                            <div style="font-size: 14px;"><strong>
                                                @if(($segment['col1_sum'] ?? 0) >= 1000000000)
                                                    {{ number_format(($segment['col1_sum'] ?? 0) / 1000000000, 1) }}M
                                                @else
                                                    {{ number_format(($segment['col1_sum'] ?? 0) / 1000000, 0) }}jt
                                                @endif
                                            </strong></div>
                                            <small class="text-muted" style="font-size: 9px;">{{ $segment['col1'] ?? 0 }} </small>
                                        </td>
                                        <td class="text-center" style="line-height: 1.2;">
                                            <div style="font-size: 14px;"><strong>
                                                @if(($segment['col2_sum'] ?? 0) >= 1000000000)
                                                    {{ number_format(($segment['col2_sum'] ?? 0) / 1000000000, 1) }}M
                                                @else
                                                    {{ number_format(($segment['col2_sum'] ?? 0) / 1000000, 0) }}jt
                                                @endif
                                            </strong></div>
                                            <small class="text-muted" style="font-size: 9px;">{{ $segment['col2'] ?? 0 }} </small>
                                        </td>
                                        <td class="text-center" style="line-height: 1.2;">
                                            <div style="font-size: 14px;"><strong>
                                                @if(($segment['col3_sum'] ?? 0) >= 1000000000)
                                                    {{ number_format(($segment['col3_sum'] ?? 0) / 1000000000, 1) }}M
                                                @else
                                                    {{ number_format(($segment['col3_sum'] ?? 0) / 1000000, 0) }}jt
                                                @endif
                                            </strong></div>
                                            <small class="text-muted" style="font-size: 9px;">{{ $segment['col3'] ?? 0 }} </small>
                                        </td>
                                        <td class="text-center" style="line-height: 1.2;">
                                            <div style="font-size: 14px;"><strong>
                                                @if(($segment['col4_sum'] ?? 0) >= 1000000000)
                                                    {{ number_format(($segment['col4_sum'] ?? 0) / 1000000000, 1) }}M
                                                @else
                                                    {{ number_format(($segment['col4_sum'] ?? 0) / 1000000, 0) }}jt
                                                @endif
                                            </strong></div>
                                            <small class="text-muted" style="font-size: 9px;">{{ $segment['col4'] ?? 0 }} </small>
                                        </td>
                                        <td class="text-center" style="line-height: 1.2;">
                                            <div style="font-size: 14px;"><strong>
                                                @if(($segment['col5_sum'] ?? 0) >= 1000000000)
                                                    {{ number_format(($segment['col5_sum'] ?? 0) / 1000000000, 1) }}M
                                                @else
                                                    {{ number_format(($segment['col5_sum'] ?? 0) / 1000000, 0) }}jt
                                                @endif
                                            </strong></div>
                                            <small class="text-muted" style="font-size: 9px;">{{ $segment['col5'] ?? 0 }} </small>
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


<!-- Modal Detail Segmentasi -->
<div class="modal fade" id="segmentDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalSegmentTitle">Detail Segmentasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalSegmentBody">
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Kolektibilitas per Segmentasi -->
<div class="modal fade" id="segmentKolDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalKolTitle">Detail Nasabah</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalKolBody">
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail NPF Account Officer -->
<div class="modal fade" id="aoNpfDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-label-danger">
                <h5 class="modal-title" id="modalAONpfTitle">
                    <i class="ti ti-alert-triangle"></i> Detail NPF Account Officer
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalAONpfBody">
                <div class="text-center p-4">
                    <div class="spinner-border text-danger" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Status Nasabah -->
<div class="modal fade" id="nasabahStatusDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNasabahStatusTitle">
                    <i class="ti ti-users"></i> Detail Nasabah
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalNasabahStatusBody">
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Trend Kontrak -->
<div class="modal fade" id="trendFundingDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTrendFundingTitle">
                    <i class="ti ti-wallet"></i> Detail Funding
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalTrendFundingBody">
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Trend Kontrak Detail -->
<div class="modal fade" id="trendKontrakDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTrendKontrakTitle">
                    <i class="ti ti-file-invoice"></i> Detail Kontrak
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalTrendKontrakBody">
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard loaded');
    console.log('ApexCharts available:', typeof ApexCharts !== 'undefined');

// Function to format nominal values in JavaScript
function formatNominal(amount) {
    if (amount >= 1000000000) {
        return 'Rp ' + (amount / 1000000000).toFixed(2) + ' M'; // Miliar
    } else if (amount >= 1000000) {
        return 'Rp ' + (amount / 1000000).toFixed(2) + ' Jt'; // Juta
    } else if (amount >= 100000) {
        return 'Rp ' + (amount / 1000).toFixed(0) + ' Rb'; // Ratusan Ribu
    } else if (amount >= 1000) {
        return 'Rp ' + (amount / 1000).toFixed(1) + ' Rb'; // Ribuan
    } else {
        return 'Rp ' + amount.toFixed(0); // Di bawah ribu
    }
}

    // Format nominal function for JavaScript
    window.formatNominal = function(amount) {
        if (amount >= 1000000000) {
            return 'Rp ' + (amount / 1000000000).toFixed(2) + ' M'; // Miliar
        } else if (amount >= 1000000) {
            return 'Rp ' + (amount / 1000000).toFixed(2) + ' Jt'; // Juta
        } else if (amount >= 100000) {
            return 'Rp ' + (amount / 1000).toFixed(0) + ' Rb'; // Ratusan Ribu
        } else if (amount >= 1000) {
            return 'Rp ' + (amount / 1000).toFixed(1) + ' Rb'; // Ribuan
        } else {
            return 'Rp ' + amount.toFixed(0); // Di bawah ribu
        }
    };

    // Format product code to description
    window.formatProductCode = function(kode, type) {
        // Product mappings for tabungan
        const tabunganMappings = {
            '02': 'TABUNGAN BERIMAN',
            '04': 'TABUNGAN BERIMAN GAYATRI',
            '05': 'TABUNGAN BERIMAN PEGAWAI',
            '21': 'TABUNGAN TEGAR',
            '22': 'TABUNGAN SIMPANAN PELAJAR',
            '25': 'TABUNGAN PASAR',
            '50': 'TAB BANSOS BUPATI BOGOR'
        };

        // Product mappings for deposito
        const depositoMappings = {
            '31': 'DEPOSITO TOHAGA',
            '41': 'DEPOSITO MUDHARABAH ABP'
        };

        if (type === 'tabungan') {
            return tabunganMappings[kode] || kode + ' (Kode Produk)';
        } else if (type === 'deposito') {
            return depositoMappings[kode] || kode + ' (Kode Produk)';
        }

        return kode || '-';
    };    if (typeof ApexCharts === 'undefined') {
        console.error('ApexCharts not loaded!');
        return;
    }

    // 1. Monthly Trend Chart
    const monthlyTrendEl = document.querySelector("#monthlyTrendChart");
    if (monthlyTrendEl) {
        const monthlyTrendChart = new ApexCharts(monthlyTrendEl, {
            series: [{
                name: 'Plafon',
                data: @json($monthlyTrends['funding'])
            }, {
                name: 'Outstanding',
                data: @json($monthlyTrends['lending'])
            }],
            chart: {
                height: 350,
                type: 'line',
                toolbar: { show: true }
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            colors: ['#696cff', '#71dd37'],
            xaxis: {
                categories: @json($monthlyTrends['labels'])
            },
            yaxis: {
                title: { text: 'Miliar Rupiah' },
                labels: {
                    formatter: function(val) {
                        return 'Rp ' + val.toFixed(1) + 'M';
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
        });
        monthlyTrendChart.render();
        console.log('Monthly trend chart rendered');
    }

    // 2. NPF Distribution Chart (Per Segmentasi)
    const npfDistributionEl = document.querySelector("#npfDistributionChart");
    if (npfDistributionEl) {
        const npfDistributionChart = new ApexCharts(npfDistributionEl, {
            series: @json($npfDistribution['values']),
            chart: {
                height: 280,
                type: 'donut'
            },
            labels: @json($npfDistribution['labels']),
            colors: ['#ff3e1d', '#EA5455', '#FF9F43', '#ffab00', '#8592a3', '#343A40', '#696cff', '#71dd37', '#00cfe8', '#826bf8'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            name: {
                                fontSize: '14px',
                                offsetY: -10
                            },
                            value: {
                                fontSize: '20px',
                                offsetY: 5,
                                formatter: function(val) {
                                    return parseFloat(val).toFixed(2) + 'M';
                                }
                            },
                            total: {
                                show: true,
                                label: 'Total NPF',
                                fontSize: '14px',
                                formatter: function(w) {
                                    const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    return total.toFixed(2) + 'M';
                                }
                            }
                        }
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val, opts) {
                    return opts.w.config.series[opts.seriesIndex].toFixed(1) + 'M';
                }
            },
            legend: {
                show: true,
                position: 'bottom',
                fontSize: '11px'
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return 'Rp ' + val.toFixed(2) + ' Miliar';
                    }
                }
            }
        });
        npfDistributionChart.render();
        console.log('NPF distribution chart rendered');
    }

    // 3. Segmentasi Pie Chart (Outstanding per Segmentasi)
    const segmentasiEl = document.querySelector('#segmentasiPieChart');
    if (segmentasiEl) {
        const segmentasiData = @json($segmentasiDistribution);
        if (segmentasiData && segmentasiData.values && segmentasiData.values.length > 0) {
            const segmentasiChart = new ApexCharts(segmentasiEl, {
                series: segmentasiData.values,
                chart: {
                    height: 280,
                    type: 'pie'
                },
                labels: segmentasiData.labels,
                colors: ['#696cff', '#71dd37', '#ff3e1d', '#ffab00', '#8592a3', '#00cfe8', '#ea5455', '#28c76f', '#03c3ec', '#826bf8', '#2b9bf4'],
                dataLabels: {
                    enabled: true,
                    formatter: function(val, opts) {
                        return opts.w.config.series[opts.seriesIndex].toFixed(1) + 'M';
                    },
                    style: {
                        fontSize: '11px',
                        fontWeight: 600
                    }
                },
                legend: {
                    position: 'bottom',
                    fontSize: '11px',
                    markers: {
                        width: 10,
                        height: 10
                    }
                },
                plotOptions: {
                    pie: {
                        dataLabels: {
                            offset: -10
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
            });
            segmentasiChart.render();
            console.log('Segmentasi chart rendered');
        }
    }

    // 4. Kolektibilitas Donut Chart
    const kolektibilitasEl = document.querySelector('#kolektibilitasChart');
    if (kolektibilitasEl) {
        const kolektibilitasChart = new ApexCharts(kolektibilitasEl, {
            series: @json($kolektibilitasDistribution['series']),
            chart: {
                height: 280,
                type: 'donut'
            },
            labels: @json($kolektibilitasDistribution['labels']),
            colors: ['#28c76f', '#00cfe8', '#ffab00', '#ff6b6b', '#ea5455'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            value: {
                                fontSize: '18px',
                                formatter: function(val) {
                                    return parseFloat(val).toFixed(2) + 'M';
                                }
                            },
                            total: {
                                show: true,
                                label: 'Total',
                                formatter: function(w) {
                                    const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    return total.toFixed(2) + 'M';
                                }
                            }
                        }
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return val.toFixed(1) + '%';
                }
            },
            legend: {
                position: 'bottom',
                fontSize: '12px'
            }
        });
        kolektibilitasChart.render();
        console.log('Kolektibilitas chart rendered');
    }

    // 5. Top Products Bar Chart
    const topProductsEl = document.querySelector('#topProductsBarChart');
    if (topProductsEl) {
        const topProductsChart = new ApexCharts(topProductsEl, {
            series: [{
                name: 'Outstanding',
                data: @json($topProductsChart['data'])
            }],
            chart: {
                type: 'bar',
                height: 280,
                toolbar: { show: false }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    barHeight: '70%',
                    borderRadius: 4
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return val.toFixed(2) + ' M';
                }
            },
            colors: ['#696cff'],
            xaxis: {
                categories: @json($topProductsChart['categories']),
                labels: {
                    formatter: function(val) {
                        return val.toFixed(1) + 'M';
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
        });
        topProductsChart.render();
        console.log('Top products chart rendered');
    }

    // Nasabah Trend Chart (Line Chart - 6 Bulan Terakhir)
    let nasabahTrendChart;
    const nasabahTrendEl = document.querySelector('#nasabahTrendChart');

    // Data untuk jumlah dan nominal
    const trendData = {
        jumlah: {
            nasabah_baru: @json($nasabahTrendData['nasabah_baru']),
            pelunasan_cepat: @json($nasabahTrendData['pelunasan_cepat']),
            nasabah_lunas: @json($nasabahTrendData['nasabah_lunas'])
        },
        nominal: {
            nasabah_baru: @json($nasabahTrendData['nasabah_baru_nominal']),
            pelunasan_cepat: @json($nasabahTrendData['pelunasan_cepat_nominal']),
            nasabah_lunas: @json($nasabahTrendData['nasabah_lunas_nominal'])
        }
    };

    function createTrendChart(type = 'jumlah') {
        if (nasabahTrendChart) {
            nasabahTrendChart.destroy();
        }

        const isNominal = type === 'nominal';
        const data = trendData[type];

        if (nasabahTrendEl) {
            nasabahTrendChart = new ApexCharts(nasabahTrendEl, {
                series: [{
                    name: 'Kontrak Baru',
                    data: data.nasabah_baru
                }, {
                    name: 'Pelunasan Cepat',
                    data: data.pelunasan_cepat
                }, {
                    name: 'Kontrak Lunas',
                    data: data.nasabah_lunas
                }],
                chart: {
                    type: 'line',
                    height: 350,
                    toolbar: { show: true },
                    zoom: { enabled: true },
                    events: {
                        markerClick: function(event, chartContext, { seriesIndex, dataPointIndex, config }) {
                            console.log('Marker clicked!', seriesIndex, dataPointIndex);
                            const monthLabel = @json($nasabahTrendData['labels'])[dataPointIndex];

                            // Tentukan kategori berdasarkan series
                            let kategori = '';
                            if (seriesIndex === 0) kategori = 'kontrak_baru';
                            else if (seriesIndex === 1) kategori = 'pelunasan_cepat';
                            else if (seriesIndex === 2) kategori = 'kontrak_lunas';

                            console.log('Opening modal:', monthLabel, kategori);
                            // Buka modal detail
                            window.showTrendKontrakDetail(monthLabel, kategori);
                        },
                        dataPointSelection: function(event, chartContext, config) {
                            console.log('Data point selected!', config);
                            const monthIndex = config.dataPointIndex;
                            const seriesIndex = config.seriesIndex;
                            const monthLabel = @json($nasabahTrendData['labels'])[monthIndex];

                            // Tentukan kategori berdasarkan series
                            let kategori = '';
                            if (seriesIndex === 0) kategori = 'kontrak_baru';
                            else if (seriesIndex === 1) kategori = 'pelunasan_cepat';
                            else if (seriesIndex === 2) kategori = 'kontrak_lunas';

                            console.log('Opening modal from selection:', monthLabel, kategori);
                            // Buka modal detail
                            window.showTrendKontrakDetail(monthLabel, kategori);
                        }
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                colors: ['#696cff', '#ffab00', '#28c76f'],
                markers: {
                    size: 6,
                    strokeWidth: 2,
                    strokeColors: '#fff',
                    hover: {
                        size: 9
                    }
                },
                states: {
                    active: {
                        allowMultipleDataPointsSelection: false,
                        filter: {
                            type: 'none'
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    style: {
                        fontSize: '11px',
                        fontWeight: 'bold'
                    },
                    background: {
                        enabled: true,
                        borderRadius: 2,
                        padding: 4,
                        opacity: 0.9
                    },
                    formatter: function(val) {
                        if (isNominal) {
                            return 'Rp ' + val.toFixed(2) + 'M';
                        }
                        return val;
                    }
                },
                xaxis: {
                    categories: @json($nasabahTrendData['labels']),
                    labels: {
                        style: {
                            fontSize: '12px'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: isNominal ? 'Nominal (Miliar Rupiah)' : 'Jumlah Kontrak'
                    },
                    labels: {
                        formatter: function(val) {
                            if (isNominal) {
                                return 'Rp ' + val.toFixed(1) + 'M';
                            }
                            return Math.round(val);
                        }
                    }
                },
                grid: {
                    borderColor: '#f1f1f1',
                    strokeDashArray: 4
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'left',
                    fontSize: '13px',
                    markers: {
                        width: 12,
                        height: 12,
                        radius: 2
                    }
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function(val) {
                            if (isNominal) {
                                return 'Rp ' + val.toFixed(2) + ' Miliar';
                            }
                            return val + ' kontrak';
                        }
                    }
                }
            });
            nasabahTrendChart.render();
            console.log('Nasabah trend chart rendered (type: ' + type + ')');
        }
    }

    // Function untuk toggle chart (pindah ke window scope agar bisa dipanggil dari HTML)
    window.toggleTrendChart = function(type) {
        // Update button state
        const btnJumlah = document.getElementById('btnTrendJumlah');
        const btnNominal = document.getElementById('btnTrendNominal');

        if (type === 'jumlah') {
            btnJumlah.classList.remove('btn-outline-primary');
            btnJumlah.classList.add('btn-primary');
            btnNominal.classList.remove('btn-primary');
            btnNominal.classList.add('btn-outline-primary');
        } else {
            btnNominal.classList.remove('btn-outline-primary');
            btnNominal.classList.add('btn-primary');
            btnJumlah.classList.remove('btn-primary');
            btnJumlah.classList.add('btn-outline-primary');
        }

        // Recreate chart with new data
        createTrendChart(type);
    }

    // Function untuk show detail trend kontrak
    window.showTrendKontrakDetail = function(monthLabel, kategori) {
        const modal = new bootstrap.Modal(document.getElementById('trendKontrakDetailModal'));
        const modalTitle = document.getElementById('modalTrendKontrakTitle');
        const modalBody = document.getElementById('modalTrendKontrakBody');

        // Update title
        let kategoriLabel = '';
        if (kategori === 'kontrak_baru') kategoriLabel = 'Kontrak Baru';
        else if (kategori === 'pelunasan_cepat') kategoriLabel = 'Pelunasan Cepat';
        else if (kategori === 'kontrak_lunas') kategoriLabel = 'Kontrak Lunas';

        modalTitle.innerHTML = '<i class="ti ti-file-invoice"></i> Detail ' + kategoriLabel + ' - ' + monthLabel;

        // Show loading
        modalBody.innerHTML = `
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;

        modal.show();

        // Parse month and year from label (format: "Nov 2025")
        const parts = monthLabel.split(' ');
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
        const month = monthNames.indexOf(parts[0]) + 1;
        const year = parseInt(parts[1]);

        // Fetch detail data
        fetch(`/dashboard/trend-kontrak-detail?month=${month}&year=${year}&kategori=${kategori}`)
            .then(response => response.json())
            .then(data => {
                let html = '<div class="container-fluid">';

                // Summary
                html += '<div class="row mb-3">';
                html += '<div class="col-12">';
                html += '<div class="alert alert-primary d-flex align-items-center" role="alert">';
                html += '<i class="ti ti-info-circle me-2"></i>';
                html += '<div>';
                html += '<strong>' + kategoriLabel + ' - ' + monthLabel + '</strong><br>';
                html += '<small>Total: ' + data.summary.total_kontrak.toLocaleString('id-ID') + ' kontrak | ';
                html += 'Nominal: Rp ' + (data.summary.total_nominal / 1000000000).toFixed(2) + ' Miliar</small>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
                html += '</div>';

                // Table
                html += '<div class="table-responsive">';
                html += '<table class="table table-sm table-hover">';
                html += '<thead class="table-light">';
                html += '<tr>';
                html += '<th>No</th><th>No. Kontrak</th><th>Nama</th><th>CIF</th><th>Tgl Efektif</th>';
                html += '<th class="text-end">Plafon</th><th class="text-end">Outstanding</th>';
                html += '<th class="text-center">Tenor</th><th>AO</th>';
                html += '</tr>';
                html += '</thead>';
                html += '<tbody>';

                if (data.kontrak && data.kontrak.length > 0) {
                    data.kontrak.forEach((item, index) => {
                        html += '<tr>';
                        html += '<td>' + (index + 1) + '</td>';
                        html += '<td><small>' + item.nokontrak + '</small></td>';
                        html += '<td><small>' + item.nama + '</small></td>';
                        html += '<td><small>' + item.nocif + '</small></td>';
                        html += '<td><small>' + item.tgleff + '</small></td>';
                        html += '<td class="text-end"><small>Rp ' + (item.mdlawal / 1000000).toFixed(1) + ' Jt</small></td>';
                        html += '<td class="text-end"><small>Rp ' + (item.osmdlc / 1000000).toFixed(1) + ' Jt</small></td>';
                        html += '<td class="text-center"><small>' + item.angs_ke + '/' + item.jw + '</small></td>';
                        html += '<td><small>' + (item.nmao || '-') + '</small></td>';
                        html += '</tr>';
                    });
                } else {
                    html += '<tr><td colspan="9" class="text-center">Tidak ada data</td></tr>';
                }

                html += '</tbody>';
                html += '</table>';
                html += '</div>';
                html += '</div>';

                modalBody.innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="ti ti-alert-circle"></i> Gagal memuat data. Silakan coba lagi.
                    </div>
                `;
            });
    }

    // Initial render
    createTrendChart('jumlah');

    // Event listener untuk klik pada baris segmentasi
    const segmentRows = document.querySelectorAll('.segment-row');
    segmentRows.forEach(row => {
        row.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            const type = this.getAttribute('data-type');
            if (category && type) {
                showSegmentDetail(category, type);
            }
        });
    });

    // Event listener untuk klik pada card status nasabah
    const statusCards = document.querySelectorAll('.nasabah-status-card');
    statusCards.forEach(card => {
        // Hover effect
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.2)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '';
        });

        // Click event
        card.addEventListener('click', function() {
            const status = this.getAttribute('data-status');
            if (status) {
                showNasabahStatusDetail(status);
            }
        });
    });

    console.log('All charts initialized successfully!');
});

// Function untuk menampilkan detail segmentasi
function showSegmentDetail(category, type) {
    const modalElement = document.getElementById('segmentDetailModal');
    if (!modalElement) return;

    const modal = new bootstrap.Modal(modalElement);
    modal.show();

    document.getElementById('modalSegmentTitle').textContent = type + ' - ' + category;
    document.getElementById('modalSegmentBody').innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

    // Get current filter parameters from URL
    const urlParams = new URLSearchParams(window.location.search);
    const startDay = urlParams.get('start_day');
    const endDay = urlParams.get('end_day');
    const month = urlParams.get('month');
    const year = urlParams.get('year');

    // Build URL with all filter parameters
    let url = `/dashboard/segmentasi-detail/${encodeURIComponent(category)}/${encodeURIComponent(type)}`;
    const params = [];
    if (startDay) params.push('start_day=' + startDay);
    if (endDay) params.push('end_day=' + endDay);
    if (month) params.push('month=' + month);
    if (year) params.push('year=' + year);
    if (params.length > 0) {
        url += '?' + params.join('&');
    }

    fetch(url)
        .then(response => response.json())
        .then(data => {
            let html = '<div class="alert alert-info mb-3">';
            html += '<div class="row text-center">';
            html += '<div class="col-4"><strong>Total Kontrak</strong><br>' + data.summary.total_kontrak.toLocaleString() + '</div>';
            html += '<div class="col-4"><strong>Outstanding</strong><br>Rp ' + Math.round(data.summary.total_outstanding).toLocaleString() + '</div>';
            html += '<div class="col-4"><strong>Disburse</strong><br>Rp ' + Math.round(data.summary.total_disburse).toLocaleString() + '</div>';
            html += '</div></div>';

            html += '<div class="table-responsive">';
            html += '<table class="table table-sm table-striped">';
            html += '<thead><tr>';
            html += '<th>No. Kontrak</th><th>Nama</th><th>Nama AO</th><th class="text-end">Outstanding</th><th class="text-end">Disburse</th><th class="text-center">Kol</th>';
            html += '</tr></thead><tbody>';

            data.details.forEach((item) => {
                html += '<tr>';
                html += '<td><small>' + item.nokontrak + '</small></td>';
                html += '<td><small>' + item.nama + '</small></td>';
                html += '<td><small>' + (item.nmao || '-') + '</small></td>';
                html += '<td class="text-end"><small>Rp ' + Math.round(item.osmdlc).toLocaleString() + '</small></td>';
                html += '<td class="text-end"><small>Rp ' + Math.round(item.mdlawal).toLocaleString() + '</small></td>';
                html += '<td class="text-center"><span class="badge bg-label-' + (item.colbaru >= 3 ? 'danger' : 'success') + '">' + item.colbaru_label + '</span></td>';
                html += '</tr>';
            });

            html += '</tbody></table></div>';

            if (data.details.length >= 100) {
                html += '<div class="alert alert-warning mt-2"><small>Menampilkan 100 data teratas</small></div>';
            }

            document.getElementById('modalSegmentBody').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('modalSegmentBody').innerHTML = '<div class="alert alert-danger">Gagal memuat data</div>';
            console.error('Error:', error);
        });
}

// Function untuk menampilkan detail nasabah per kolektibilitas dan segmentasi
function showSegmentKolDetail(event, category, type, kolValue) {
    // Stop event propagation to prevent row click event
    if (event) {
        event.stopPropagation();
    }

    const modalElement = document.getElementById('segmentKolDetailModal');
    if (!modalElement) return;

    const modal = new bootstrap.Modal(modalElement);
    modal.show();

    const kolLabels = {
        '1': 'Lancar',
        '2': 'Kurang Lancar',
        '3': 'Diragukan',
        '4': 'Macet',
        '5': 'Loss'
    };

    const kolColors = {
        '1': 'success',
        '2': 'info',
        '3': 'warning',
        '4': 'danger',
        '5': 'dark'
    };

    document.getElementById('modalKolTitle').innerHTML =
        `<i class="ti ti-users"></i> Detail Nasabah KOL ${kolValue} (${kolLabels[kolValue]}) - ${type} (${category})`;
    document.getElementById('modalKolBody').innerHTML =
        '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

    // Get current filter parameters from URL
    const urlParams = new URLSearchParams(window.location.search);
    const startDay = urlParams.get('start_day');
    const endDay = urlParams.get('end_day');
    const month = urlParams.get('month');
    const year = urlParams.get('year');

    // Build URL with all filter parameters
    let url = `/dashboard/segmentasi-kol-detail/${encodeURIComponent(category)}/${encodeURIComponent(type)}/${kolValue}`;
    const params = [];
    if (startDay) params.push('start_day=' + startDay);
    if (endDay) params.push('end_day=' + endDay);
    if (month) params.push('month=' + month);
    if (year) params.push('year=' + year);
    if (params.length > 0) {
        url += '?' + params.join('&');
    }

    fetch(url)
        .then(response => response.json())
        .then(data => {
            // Helper function untuk format rupiah
            const formatRupiah = (amount) => {
                if (amount >= 1000000000) {
                    return 'Rp ' + (amount / 1000000000).toFixed(2).replace('.', ',') + ' M';
                } else if (amount >= 1000000) {
                    return 'Rp ' + (amount / 1000000).toFixed(2).replace('.', ',') + ' Jt';
                } else {
                    return 'Rp ' + Math.round(amount).toLocaleString('id-ID');
                }
            };

            let html = '<div class="alert alert-' + kolColors[kolValue] + ' mb-3">';
            html += '<div class="row text-center">';
            html += '<div class="col-3"><strong>Total Nasabah</strong><br>' + data.summary.total_nasabah.toLocaleString('id-ID') + '</div>';
            html += '<div class="col-3"><strong>Total Kontrak</strong><br>' + data.summary.total_kontrak.toLocaleString('id-ID') + '</div>';
            html += '<div class="col-3"><strong>Total Outstanding</strong><br>' + formatRupiah(data.summary.total_outstanding) + '</div>';
            html += '<div class="col-3"><strong>Rata-rata</strong><br>' + formatRupiah(data.summary.avg_outstanding) + '</div>';
            html += '</div></div>';

            if (data.details.length > 0) {
                html += '<div class="table-responsive">';
                html += '<table class="table table-sm table-striped table-hover">';
                html += '<thead class="table-light"><tr>';
                html += '<th>No</th><th>No. Kontrak</th><th>Nama</th><th>Nama AO</th>';
                html += '<th class="text-end">Outstanding</th><th class="text-end">Disburse</th>';
                html += '<th class="text-center">Kol</th><th class="text-center">DPD</th>';
                html += '</tr></thead><tbody>';

                data.details.forEach((item, index) => {
                    html += '<tr>';
                    html += '<td><small>' + (index + 1) + '</small></td>';
                    html += '<td><small>' + item.nokontrak + '</small></td>';
                    html += '<td><small>' + item.nama + '</small></td>';
                    html += '<td><small>' + (item.nmao || '-') + '</small></td>';
                    html += '<td class="text-end"><small>' + formatRupiah(item.osmdlc) + '</small></td>';
                    html += '<td class="text-end"><small>' + formatRupiah(item.mdlawal) + '</small></td>';
                    html += '<td class="text-center"><span class="badge bg-' + kolColors[item.colbaru] + '">' + item.colbaru_label + '</span></td>';
                    html += '<td class="text-center"><small>' + (item.dpd || 0) + ' hari</small></td>';
                    html += '</tr>';
                });

                html += '</tbody></table></div>';

                if (data.details.length >= 100) {
                    html += '<div class="alert alert-warning mt-2"><small><i class="ti ti-info-circle"></i> Menampilkan 100 data teratas</small></div>';
                }
            } else {
                html += '<div class="alert alert-info"><i class="ti ti-info-circle"></i> Tidak ada data nasabah untuk KOL ' + kolValue + ' pada segmentasi ini</div>';
            }

            document.getElementById('modalKolBody').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('modalKolBody').innerHTML =
                '<div class="alert alert-danger"><i class="ti ti-alert-circle"></i> Gagal memuat data: ' + error.message + '</div>';
            console.error('Error:', error);
        });
}

// Function untuk menampilkan detail kecamatan (sama seperti segmentasi)
function showKecamatanDetail(kecamatan) {
    const modalElement = document.getElementById('segmentDetailModal');
    if (!modalElement) return;

    const modal = new bootstrap.Modal(modalElement);
    modal.show();

    document.getElementById('modalSegmentTitle').textContent = 'Detail Nasabah - Kecamatan ' + kecamatan;
    document.getElementById('modalSegmentBody').innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

    // Get current filter parameters from URL
    const urlParams = new URLSearchParams(window.location.search);
    const startDay = urlParams.get('start_day');
    const endDay = urlParams.get('end_day');
    const month = urlParams.get('month');
    const year = urlParams.get('year');

    // Build URL with all filter parameters
    let url = `/dashboard/kecamatan-detail/${encodeURIComponent(kecamatan)}`;
    const params = [];
    if (startDay) params.push('start_day=' + startDay);
    if (endDay) params.push('end_day=' + endDay);
    if (month) params.push('month=' + month);
    if (year) params.push('year=' + year);
    if (params.length > 0) {
        url += '?' + params.join('&');
    }

    fetch(url)
        .then(response => response.json())
        .then(data => {
            let html = '<div class="alert alert-info mb-3">';
            html += '<div class="row text-center">';
            html += '<div class="col-4"><strong>Total Kontrak</strong><br>' + data.summary.total_kontrak.toLocaleString() + '</div>';
            html += '<div class="col-4"><strong>Outstanding</strong><br>Rp ' + Math.round(data.summary.total_outstanding).toLocaleString() + '</div>';
            html += '<div class="col-4"><strong>Disburse</strong><br>Rp ' + Math.round(data.summary.total_disburse).toLocaleString() + '</div>';
            html += '</div></div>';

            html += '<div class="table-responsive">';
            html += '<table class="table table-sm table-striped">';
            html += '<thead><tr>';
            html += '<th>No. Kontrak</th><th>Nama</th><th>Nama AO</th><th class="text-end">Outstanding</th><th class="text-end">Disburse</th><th class="text-center">Kol</th>';
            html += '</tr></thead><tbody>';

            data.details.forEach((item) => {
                html += '<tr>';
                html += '<td><small>' + item.nokontrak + '</small></td>';
                html += '<td><small>' + item.nama + '</small></td>';
                html += '<td><small>' + (item.nmao || '-') + '</small></td>';
                html += '<td class="text-end"><small>Rp ' + Math.round(item.osmdlc).toLocaleString() + '</small></td>';
                html += '<td class="text-end"><small>Rp ' + Math.round(item.mdlawal).toLocaleString() + '</small></td>';
                html += '<td class="text-center"><span class="badge bg-label-' + (item.colbaru >= 3 ? 'danger' : 'success') + '">' + item.colbaru_label + '</span></td>';
                html += '</tr>';
            });

            html += '</tbody></table></div>';

            if (data.details.length >= 100) {
                html += '<div class="alert alert-warning mt-2"><small>Menampilkan 100 data teratas</small></div>';
            }

            document.getElementById('modalSegmentBody').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('modalSegmentBody').innerHTML = '<div class="alert alert-danger">Gagal memuat data</div>';
            console.error('Error:', error);
        });
}

// Function untuk menampilkan detail AO
function showAODetail(nmao) {
    const modalElement = document.getElementById('segmentDetailModal');
    if (!modalElement) return;

    const modal = new bootstrap.Modal(modalElement);
    modal.show();

    document.getElementById('modalSegmentTitle').textContent = 'Detail Nasabah - AO: ' + nmao;
    document.getElementById('modalSegmentBody').innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

    // Get current filter parameters from URL
    const urlParams = new URLSearchParams(window.location.search);
    const startDay = urlParams.get('start_day');
    const endDay = urlParams.get('end_day');
    const month = urlParams.get('month');
    const year = urlParams.get('year');

    // Build URL with all filter parameters
    let url = `/dashboard/ao-detail/${encodeURIComponent(nmao)}`;
    const params = [];
    if (startDay) params.push('start_day=' + startDay);
    if (endDay) params.push('end_day=' + endDay);
    if (month) params.push('month=' + month);
    if (year) params.push('year=' + year);
    if (params.length > 0) {
        url += '?' + params.join('&');
    }

    fetch(url)
        .then(response => response.json())
        .then(data => {
            let html = '<div class="alert alert-info mb-3">';
            html += '<div class="row text-center">';
            html += '<div class="col-3"><strong>Total Kontrak</strong><br>' + data.summary.total_kontrak.toLocaleString() + '</div>';
            html += '<div class="col-3"><strong>Outstanding</strong><br>Rp ' + Math.round(data.summary.total_outstanding).toLocaleString() + '</div>';
            html += '<div class="col-3"><strong>Disburse</strong><br>Rp ' + Math.round(data.summary.total_disburse).toLocaleString() + '</div>';
            html += '<div class="col-3"><strong>Total NPF</strong><br>' + data.summary.jumlah_npf.toLocaleString() + ' (' + (data.summary.total_outstanding > 0 ? ((data.summary.total_npf / data.summary.total_outstanding) * 100).toFixed(2) : 0) + '%)</div>';
            html += '</div></div>';

            html += '<div class="table-responsive">';
            html += '<table class="table table-sm table-striped">';
            html += '<thead><tr>';
            html += '<th>No. Kontrak</th><th>Nama</th><th>Kecamatan</th><th class="text-end">Outstanding</th><th class="text-end">Disburse</th><th class="text-center">Kol</th>';
            html += '</tr></thead><tbody>';

            data.details.forEach((item) => {
                html += '<tr>';
                html += '<td><small>' + item.nokontrak + '</small></td>';
                html += '<td><small>' + item.nama + '</small></td>';
                html += '<td><small>' + (item.kecamatan || '-') + '</small></td>';
                html += '<td class="text-end"><small>Rp ' + Math.round(item.osmdlc).toLocaleString() + '</small></td>';
                html += '<td class="text-end"><small>Rp ' + Math.round(item.mdlawal).toLocaleString() + '</small></td>';
                html += '<td class="text-center"><span class="badge bg-label-' + (item.colbaru >= 3 ? 'danger' : 'success') + '">' + item.colbaru_label + '</span></td>';
                html += '</tr>';
            });

            html += '</tbody></table></div>';

            if (data.details.length >= 100) {
                html += '<div class="alert alert-warning mt-2"><small>Menampilkan 100 data teratas</small></div>';
            }

            document.getElementById('modalSegmentBody').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('modalSegmentBody').innerHTML = '<div class="alert alert-danger">Gagal memuat data</div>';
            console.error('Error:', error);
        });
}

// Function untuk menampilkan detail NPF dari Account Officer
function showAONpfDetail(event, nmao) {
    // Stop event propagation to prevent row click event
    if (event) {
        event.stopPropagation();
    }

    const modalElement = document.getElementById('aoNpfDetailModal');
    if (!modalElement) return;

    const modal = new bootstrap.Modal(modalElement);
    modal.show();

    document.getElementById('modalAONpfTitle').innerHTML =
        '<i class="ti ti-alert-triangle"></i> Detail NPF - AO: ' + nmao;
    document.getElementById('modalAONpfBody').innerHTML =
        '<div class="text-center p-4"><div class="spinner-border text-danger" role="status"><span class="visually-hidden">Loading...</span></div></div>';

    // Get current filter parameters from URL
    const urlParams = new URLSearchParams(window.location.search);
    const startDay = urlParams.get('start_day');
    const endDay = urlParams.get('end_day');
    const month = urlParams.get('month');
    const year = urlParams.get('year');

    // Build URL with all filter parameters
    let url = `/dashboard/ao-npf-detail/${encodeURIComponent(nmao)}`;
    const params = [];
    if (startDay) params.push('start_day=' + startDay);
    if (endDay) params.push('end_day=' + endDay);
    if (month) params.push('month=' + month);
    if (year) params.push('year=' + year);
    if (params.length > 0) {
        url += '?' + params.join('&');
    }

    fetch(url)
        .then(response => response.json())
        .then(data => {
            // Helper function untuk format rupiah
            const formatRupiah = (amount) => {
                if (amount >= 1000000000) {
                    return 'Rp ' + (amount / 1000000000).toFixed(2).replace('.', ',') + ' M';
                } else if (amount >= 1000000) {
                    return 'Rp ' + (amount / 1000000).toFixed(2).replace('.', ',') + ' Jt';
                } else {
                    return 'Rp ' + Math.round(amount).toLocaleString('id-ID');
                }
            };

            let html = '<div class="alert alert-danger mb-3">';
            html += '<div class="row text-center">';
            html += '<div class="col-3"><strong>Total Nasabah NPF</strong><br>' + data.summary.total_nasabah.toLocaleString('id-ID') + '</div>';
            html += '<div class="col-3"><strong>Total Outstanding NPF</strong><br>' + formatRupiah(data.summary.total_outstanding) + '</div>';
            html += '<div class="col-3"><strong>Rata-rata Outstanding</strong><br>' + formatRupiah(data.summary.avg_outstanding) + '</div>';
            html += '<div class="col-3"><strong>NPF Ratio</strong><br><span class="badge bg-danger">' + data.summary.npf_ratio.toFixed(2) + '%</span></div>';
            html += '</div></div>';

            if (data.details.length > 0) {
                html += '<div class="table-responsive">';
                html += '<table class="table table-sm table-striped table-hover">';
                html += '<thead class="table-light"><tr>';
                html += '<th>No</th><th>No. Kontrak</th><th>Nama</th><th>Kecamatan</th>';
                html += '<th class="text-end">Outstanding</th><th class="text-end">Disburse</th>';
                html += '<th class="text-center">Kol</th><th class="text-center">DPD</th>';
                html += '</tr></thead><tbody>';

                data.details.forEach((item, index) => {
                    const kolColors = {
                        '1': 'success',
                        '2': 'info',
                        '3': 'warning',
                        '4': 'danger',
                        '5': 'dark'
                    };

                    html += '<tr>';
                    html += '<td><small>' + (index + 1) + '</small></td>';
                    html += '<td><small>' + item.nokontrak + '</small></td>';
                    html += '<td><small>' + item.nama + '</small></td>';
                    html += '<td><small>' + (item.kecamatan || '-') + '</small></td>';
                    html += '<td class="text-end"><small>' + formatRupiah(item.osmdlc) + '</small></td>';
                    html += '<td class="text-end"><small>' + formatRupiah(item.mdlawal) + '</small></td>';
                    html += '<td class="text-center"><span class="badge bg-' + kolColors[item.colbaru] + '">' + item.colbaru_label + '</span></td>';
                    html += '<td class="text-center"><small>' + (item.dpd || 0) + ' hari</small></td>';
                    html += '</tr>';
                });

                html += '</tbody></table></div>';

                if (data.details.length >= 100) {
                    html += '<div class="alert alert-warning mt-2"><small><i class="ti ti-info-circle"></i> Menampilkan 100 data teratas</small></div>';
                }
            } else {
                html += '<div class="alert alert-success"><i class="ti ti-check-circle"></i> Tidak ada NPF untuk AO ini</div>';
            }

            document.getElementById('modalAONpfBody').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('modalAONpfBody').innerHTML =
                '<div class="alert alert-danger"><i class="ti ti-alert-circle"></i> Gagal memuat data: ' + error.message + '</div>';
            console.error('Error:', error);
        });
}

// Event listener untuk klik pada baris kecamatan
document.addEventListener('DOMContentLoaded', function() {
    // Click event untuk detail nasabah kecamatan
    document.querySelectorAll('.kecamatan-row').forEach(row => {
        row.addEventListener('click', function(e) {
            // Jangan trigger jika yang diklik adalah header (untuk sorting)
            if (!e.target.closest('th')) {
                const kecamatan = this.dataset.kecamatan;
                showKecamatanDetail(kecamatan);
            }
        });
    });

    // Click event untuk detail nasabah AO
    document.querySelectorAll('.ao-row').forEach(row => {
        row.addEventListener('click', function(e) {
            const aoName = this.dataset.ao;
            showAODetail(aoName);
        });
    });

    // Sorting functionality untuk tabel kecamatan
    let currentSort = { column: null, direction: 'asc' };

    document.querySelectorAll('#kecamatanTable .sortable').forEach(header => {
        header.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent row click

            const sortBy = this.dataset.sort;
            const tbody = document.getElementById('kecamatanTableBody');
            const rows = Array.from(tbody.querySelectorAll('.kecamatan-row'));

            // Toggle direction if same column, otherwise default to ascending
            if (currentSort.column === sortBy) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.column = sortBy;
                currentSort.direction = 'asc';
            }

            // Sort rows
            rows.sort((a, b) => {
                let aVal, bVal;

                switch(sortBy) {
                    case 'kecamatan':
                        aVal = a.dataset.kecamatan.toLowerCase();
                        bVal = b.dataset.kecamatan.toLowerCase();
                        return currentSort.direction === 'asc'
                            ? aVal.localeCompare(bVal)
                            : bVal.localeCompare(aVal);

                    case 'nasabah':
                        aVal = parseFloat(a.dataset.nasabah);
                        bVal = parseFloat(b.dataset.nasabah);
                        break;

                    case 'outstanding':
                        aVal = parseFloat(a.dataset.outstanding);
                        bVal = parseFloat(b.dataset.outstanding);
                        break;

                    case 'persentase':
                        aVal = parseFloat(a.dataset.persentase);
                        bVal = parseFloat(b.dataset.persentase);
                        break;
                }

                if (sortBy !== 'kecamatan') {
                    return currentSort.direction === 'asc' ? aVal - bVal : bVal - aVal;
                }
            });

            // Update row numbers and reappend
            rows.forEach((row, index) => {
                row.querySelector('td:first-child strong').textContent = index + 1;
                tbody.appendChild(row);
            });

            // Update sort indicators
            document.querySelectorAll('#kecamatanTable .sortable i').forEach(icon => {
                icon.className = 'ti ti-selector';
            });

            const icon = this.querySelector('i');
            icon.className = currentSort.direction === 'asc' ? 'ti ti-sort-ascending' : 'ti ti-sort-descending';
        });
    });

    // Initialize Map
    initializeMap();
});

// Toggle between map and table view
function toggleView(view) {
    const mapView = document.getElementById('mapView');
    const tableView = document.getElementById('tableView');
    const btnShowMap = document.getElementById('btnShowMap');
    const btnShowTable = document.getElementById('btnShowTable');

    if (view === 'map') {
        mapView.style.display = 'block';
        tableView.style.display = 'none';
        btnShowMap.classList.remove('btn-outline-primary');
        btnShowMap.classList.add('btn-primary');
        btnShowTable.classList.remove('btn-primary');
        btnShowTable.classList.add('btn-outline-primary');

        // Refresh map size
        if (window.kecamatanMap) {
            setTimeout(() => window.kecamatanMap.invalidateSize(), 100);
        }
    } else {
        mapView.style.display = 'none';
        tableView.style.display = 'block';
        btnShowMap.classList.remove('btn-primary');
        btnShowMap.classList.add('btn-outline-primary');
        btnShowTable.classList.remove('btn-outline-primary');
        btnShowTable.classList.add('btn-primary');
    }
}

// Initialize Leaflet Map
function initializeMap() {
    // Create map dengan peta flat Indonesia
    const map = L.map('map', {
        center: [-2.5, 118.0], // Pusat Indonesia
        zoom: 5,
        zoomControl: true,
        attributionControl: false,
        minZoom: 4,
        maxZoom: 15
    });
    window.kecamatanMap = map;

    // Tambahkan tiles peta FLAT (CartoDB Positron - style flat tanpa 3D)
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '© OpenStreetMap contributors © CARTO',
        subdomains: 'abcd',
        maxZoom: 19
    }).addTo(map);

    // Data kecamatan dari blade
    const kecamatanData = @json($kecamatanData);

    console.log('Total kecamatan yang akan dimuat:', kecamatanData.length);

    // Function untuk mendapatkan warna berdasarkan jumlah nasabah
    function getColor(nasabah) {
        if (!nasabah || nasabah === 0) return '#cccccc';
        return nasabah > 100 ? '#c72e1d' :   // Merah tua
               nasabah > 50  ? '#e84b3c' :   // Merah
               nasabah > 20  ? '#f39c12' :   // Orange
               nasabah > 10  ? '#f9d423' :   // Kuning
                              '#27ae60';     // Hijau
    }

    // Function untuk mendapatkan ukuran marker
    function getMarkerSize(nasabah) {
        if (nasabah > 100) return 14;
        if (nasabah > 50) return 11;
        if (nasabah > 20) return 9;
        if (nasabah > 10) return 7;
        return 6;
    }

    // Counter untuk tracking progress
    let markersAdded = 0;
    let markersFailed = 0;
    const totalMarkers = kecamatanData.length;

    // Gunakan geocoding murni untuk semua kecamatan (tanpa hardcoded coordinates)
    kecamatanData.forEach((kec, index) => {
        const kecamatanName = kec.kecamatan;
        const kotaName = kec.kota || '';

        // Geocode menggunakan Nominatim API dengan delay untuk rate limiting
        setTimeout(() => {
            // Buat query yang lebih spesifik dengan menambahkan kota/kabupaten
            let searchQuery = '';
            if (kotaName && kotaName.trim() !== '') {
                // Coba dengan format: "Kecamatan [nama], [kota], Indonesia"
                searchQuery = encodeURIComponent(`Kecamatan ${kecamatanName}, ${kotaName}, Indonesia`);
            } else {
                // Fallback jika tidak ada kota
                searchQuery = encodeURIComponent(`Kecamatan ${kecamatanName}, Indonesia`);
            }

            fetch(`https://nominatim.openstreetmap.org/search?q=${searchQuery}&format=json&limit=3&countrycodes=id`, {
                headers: {
                    'User-Agent': 'FinBoard-Dashboard/1.0 (Contact: admin@finboard.app)'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        // Cari hasil yang paling sesuai (yang mengandung nama kota jika ada)
                        let bestMatch = data[0];

                        if (kotaName && kotaName.trim() !== '') {
                            // Cari hasil yang display_name nya mengandung nama kota
                            const kotaLower = kotaName.toLowerCase();
                            for (let i = 0; i < data.length; i++) {
                                if (data[i].display_name.toLowerCase().includes(kotaLower)) {
                                    bestMatch = data[i];
                                    break;
                                }
                            }
                        }

                        const lat = parseFloat(bestMatch.lat);
                        const lon = parseFloat(bestMatch.lon);

                        console.log(`✓ Geocoded: ${kecamatanName}${kotaName ? ' (' + kotaName + ')' : ''} → [${lat.toFixed(4)}, ${lon.toFixed(4)}]`);
                        console.log(`  Location: ${bestMatch.display_name}`);

                        const nasabah = kec.total_nasabah;
                        const color = getColor(nasabah);
                        const size = getMarkerSize(nasabah);

                        // Create circle marker
                        const marker = L.circleMarker([lat, lon], {
                            radius: size,
                            fillColor: color,
                            color: '#fff',
                            weight: 2,
                            opacity: 1,
                            fillOpacity: 0.85
                        }).addTo(map);

                        // Popup content dengan informasi kota
                        const outstandingFormatted = kec.total_outstanding >= 1000000000
                            ? 'Rp ' + (kec.total_outstanding / 1000000000).toFixed(2) + ' M'
                            : 'Rp ' + (kec.total_outstanding / 1000000).toFixed(2) + ' Jt';

                        const popupContent = `
                            <div style="min-width: 200px;">
                                <h6 class="mb-2"><strong>${kec.kecamatan}</strong></h6>
                                ${kotaName ? '<small class="text-muted d-block mb-2"><i class="ti ti-map-pin"></i> ' + kotaName + '</small>' : ''}
                                <div class="mb-1">
                                    <i class="ti ti-users text-primary"></i>
                                    <strong>${kec.total_nasabah.toLocaleString()}</strong> Nasabah
                                </div>
                                <div class="mb-2">
                                    <i class="ti ti-currency-dollar text-success"></i>
                                    ${outstandingFormatted}
                                </div>
                                <button class="btn btn-xs btn-primary w-100" onclick="showKecamatanDetail('${kec.kecamatan}')">
                                    <i class="ti ti-eye"></i> Lihat Detail
                                </button>
                            </div>
                        `;

                        marker.bindPopup(popupContent);

                        // Click event
                        marker.on('click', function() {
                            this.openPopup();
                        });

                        markersAdded++;

                        // Log progress setiap 10 marker
                        if (markersAdded % 10 === 0 || markersAdded === totalMarkers) {
                            console.log(`Progress: ${markersAdded}/${totalMarkers} markers loaded (${markersFailed} failed)`);
                        }
                    } else {
                        console.warn(`✗ Tidak ditemukan koordinat untuk: ${kecamatanName}${kotaName ? ' (' + kotaName + ')' : ''}`);
                        markersFailed++;
                    }
                })
                .catch(error => {
                    console.error(`✗ Error geocoding ${kecamatanName}:`, error);
                    markersFailed++;
                });
        }, index * 1200); // Delay 1.2 detik per request untuk menghormati rate limit Nominatim
    });

    // Add legend
    const legend = L.control({ position: 'bottomright' });
    legend.onAdd = function(map) {
        const div = L.DomUtil.create('div', 'info legend');
        div.style.background = 'white';
        div.style.padding = '12px';
        div.style.borderRadius = '8px';
        div.style.boxShadow = '0 0 15px rgba(0,0,0,0.2)';
        div.style.fontSize = '12px';
        div.style.lineHeight = '20px';

        const grades = [0, 10, 20, 50, 100];
        const labels = ['<strong>Jumlah Nasabah:</strong>'];

        for (let i = 0; i < grades.length; i++) {
            const from = grades[i];
            const to = grades[i + 1];
            const color = getColor(from + 1);

            labels.push(
                '<i style="background:' + color + '; width: 16px; height: 16px; display: inline-block; margin-right: 5px; border: 1px solid #fff; border-radius: 50%;"></i> ' +
                from + (to ? '&ndash;' + to : '+')
            );
        }

        div.innerHTML = labels.join('<br>');
        return div;
    };
    legend.addTo(map);

    // Info box untuk title
    const info = L.control({ position: 'topleft' });
    info.onAdd = function(map) {
        const div = L.DomUtil.create('div', 'info');
        div.style.background = 'white';
        div.style.padding = '10px 15px';
        div.style.borderRadius = '8px';
        div.style.boxShadow = '0 0 15px rgba(0,0,0,0.2)';
        div.innerHTML = '<h6 style="margin: 0;"><strong>PETA SEBARAN NASABAH</strong></h6><small>Seluruh Indonesia</small>';
        return div;
    };
    info.addTo(map);

    // Loading indicator
    const loadingInfo = L.control({ position: 'bottomleft' });
    loadingInfo.onAdd = function(map) {
        const div = L.DomUtil.create('div', 'info');
        div.id = 'loadingInfo';
        div.style.background = 'white';
        div.style.padding = '8px 12px';
        div.style.borderRadius = '8px';
        div.style.boxShadow = '0 0 15px rgba(0,0,0,0.2)';
        div.innerHTML = '<small><i class="ti ti-loader"></i> Memuat lokasi kecamatan...</small>';
        return div;
    };
    loadingInfo.addTo(map);

    // Remove loading after some time
    setTimeout(() => {
        const loadingEl = document.getElementById('loadingInfo');
        if (loadingEl) {
            loadingEl.innerHTML = '<small><i class="ti ti-check"></i> ' + markersAdded + ' lokasi dimuat</small>';
            setTimeout(() => {
                if (loadingEl.parentNode) {
                    loadingEl.parentNode.removeChild(loadingEl);
                }
            }, 3000);
        }
    }, (maxMarkers * 100) + 2000);
}

// Function untuk menampilkan detail status nasabah
function showNasabahStatusDetail(status) {
    const modalElement = document.getElementById('nasabahStatusDetailModal');
    if (!modalElement) return;

    const modal = new bootstrap.Modal(modalElement);
    modal.show();

    // Set loading state
    document.getElementById('modalNasabahStatusTitle').innerHTML = '<i class="ti ti-users"></i> Detail Nasabah';
    document.getElementById('modalNasabahStatusBody').innerHTML =
        '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

    // Get current filter parameters from URL
    const urlParams = new URLSearchParams(window.location.search);
    const startDay = urlParams.get('start_day');
    const endDay = urlParams.get('end_day');
    const month = urlParams.get('month');
    const year = urlParams.get('year');

    // Build URL with all filter parameters
    let url = `/dashboard/nasabah-status-detail/${encodeURIComponent(status)}`;
    const params = [];
    if (startDay) params.push('start_day=' + startDay);
    if (endDay) params.push('end_day=' + endDay);
    if (month) params.push('month=' + month);
    if (year) params.push('year=' + year);
    if (params.length > 0) {
        url += '?' + params.join('&');
    }

    fetch(url)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalNasabahStatusTitle').innerHTML =
                '<i class="ti ti-users"></i> ' + data.title;

            // Helper function untuk format rupiah
            const formatRupiah = (amount) => {
                if (!amount || amount == 0) return 'Rp 0';

                // Remove dots and parse as number
                const numAmount = typeof amount === 'string' ?
                    parseInt(amount.replace(/\./g, '')) : amount;

                if (numAmount >= 1000000000) {
                    return 'Rp ' + (numAmount / 1000000000).toFixed(2).replace('.', ',') + ' M';
                } else if (numAmount >= 1000000) {
                    return 'Rp ' + (numAmount / 1000000).toFixed(2).replace('.', ',') + ' Jt';
                } else {
                    return 'Rp ' + numAmount.toLocaleString('id-ID');
                }
            };

            let html = '<div class="alert alert-info mb-3 text-center">';
            html += '<strong>Total Data: ' + data.total.toLocaleString('id-ID') + ' kontrak</strong>';
            if (data.total >= 100) {
                html += '<br><small>Menampilkan 100 data teratas</small>';
            }
            html += '</div>';

            if (data.data.length > 0) {
                html += '<div class="table-responsive">';
                html += '<table class="table table-sm table-striped table-hover">';
                html += '<thead class="table-light"><tr>';
                html += '<th>No</th><th>No. Kontrak</th><th>Nama</th>';
                html += '<th>Tgl Efektif</th><th class="text-center">Tenor</th>';
                html += '<th class="text-center">Angsuran Ke</th><th class="text-center">Progress</th>';
                html += '<th class="text-end">Plafon</th><th class="text-end">Outstanding</th>';
                html += '<th class="text-center">Kol</th><th>AO</th><th>Produk</th><th>Kecamatan</th>';
                html += '</tr></thead><tbody>';

                data.data.forEach((item, index) => {
                    const kolColors = {
                        '1': 'success',
                        '2': 'info',
                        '3': 'warning',
                        '4': 'danger',
                        '5': 'dark'
                    };

                    html += '<tr>';
                    html += '<td><small>' + (index + 1) + '</small></td>';
                    html += '<td><small>' + item.nokontrak + '</small></td>';
                    html += '<td><small>' + item.nama + '</small></td>';
                    html += '<td><small>' + item.tgleff + '</small></td>';
                    html += '<td class="text-center"><small>' + item.jw + '</small></td>';
                    html += '<td class="text-center"><small>' + item.angs_ke + '</small></td>';
                    html += '<td class="text-center">';
                    html += '<div class="progress" style="height: 15px; min-width: 60px;">';
                    html += '<div class="progress-bar" role="progressbar" style="width: ' + item.progress + '%">';
                    html += '<small>' + item.progress + '%</small>';
                    html += '</div></div>';
                    html += '</td>';
                    html += '<td class="text-end"><small>' + formatRupiah(item.mdlawal) + '</small></td>';
                    html += '<td class="text-end"><small>' + formatRupiah(item.osmdlc) + '</small></td>';
                    html += '<td class="text-center">';
                    if (item.colbaru && item.colbaru !== '-') {
                        html += '<span class="badge bg-' + (kolColors[item.colbaru] || 'secondary') + '">' + item.colbaru + '</span>';
                    } else {
                        html += '<small>-</small>';
                    }
                    html += '</td>';
                    html += '<td><small>' + item.nmao + '</small></td>';
                    html += '<td><small>' + item.nmjenis + '</small></td>';
                    html += '<td><small>' + item.kecamatan + '</small></td>';
                    html += '</tr>';
                });

                html += '</tbody></table></div>';
            } else {
                html += '<div class="alert alert-warning"><i class="ti ti-info-circle"></i> Tidak ada data untuk kategori ini</div>';
            }

            document.getElementById('modalNasabahStatusBody').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('modalNasabahStatusBody').innerHTML =
                '<div class="alert alert-danger"><i class="ti ti-alert-circle"></i> Gagal memuat data: ' + error.message + '</div>';
            console.error('Error:', error);
        });
}

// Funding Trend Chart
let fundingTrendChart;
const fundingTrendEl = document.querySelector("#fundingTrendChart");
if (fundingTrendEl) {
    @php
        $fundingTrendLabels = $fundingTrends->pluck('period')->map(function($period) {
            $parts = explode('-', $period);
            $year = $parts[0];
            $month = $parts[1];
            $monthNames = ['01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'Mei', '06' => 'Jun',
                          '07' => 'Jul', '08' => 'Agt', '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des'];
            return ($monthNames[$month] ?? $month) . ' ' . $year;
        })->toArray();

        // Data nominal (dalam miliar)
        $fundingTabunganData = $fundingTrends->pluck('tabungan')->map(fn($v) => round($v / 1000000000, 2))->toArray();
        $fundingDepositoData = $fundingTrends->pluck('deposito')->map(fn($v) => round($v / 1000000000, 2))->toArray();
        $fundingTotalData = $fundingTrends->pluck('total')->map(fn($v) => round($v / 1000000000, 2))->toArray();
        $fundingPencairanData = $fundingTrends->pluck('pencairan')->map(fn($v) => round($v / 1000000000, 2))->toArray();

        // Data jumlah (banyaknya)
        $fundingTabunganJumlah = $fundingTrends->pluck('jumlah_tabungan')->toArray();
        $fundingDepositoJumlah = $fundingTrends->pluck('jumlah_deposito')->toArray();
        $fundingTotalJumlah = $fundingTrends->map(fn($item) => $item['jumlah_tabungan'] + $item['jumlah_deposito'])->toArray();
        $fundingPencairanJumlah = $fundingTrends->pluck('jumlah_pencairan')->toArray();

        // Data untuk toggle
        $fundingTrendData = [
            'nominal' => [
                'tabungan' => $fundingTabunganData,
                'deposito' => $fundingDepositoData,
                'total' => $fundingTotalData,
                'pencairan' => $fundingPencairanData
            ],
            'jumlah' => [
                'tabungan' => $fundingTabunganJumlah,
                'deposito' => $fundingDepositoJumlah,
                'total' => $fundingTotalJumlah,
                'pencairan' => $fundingPencairanJumlah
            ]
        ];
    @endphp

    function createFundingTrendChart(type = 'nominal') {
        if (fundingTrendChart) {
            fundingTrendChart.destroy();
        }

        const isNominal = type === 'nominal';
        const data = @json($fundingTrendData)[type];

        if (fundingTrendEl) {
            fundingTrendChart = new ApexCharts(fundingTrendEl, {
                series: [{
                    name: 'Tabungan',
                    data: data.tabungan
                }, {
                    name: 'Deposito',
                    data: data.deposito
                }, {
                    name: 'Total Funding',
                    data: data.total
                }, {
                    name: 'Pencairan Deposito',
                    data: data.pencairan
                }],
                chart: {
                    height: 350,
                    type: 'line',
                    toolbar: { show: true },
                    zoom: { enabled: true },
                    events: {
                        markerClick: function(event, chartContext, { seriesIndex, dataPointIndex, config }) {
                            console.log('Funding marker clicked!', seriesIndex, dataPointIndex);
                            const monthLabel = @json($fundingTrendLabels)[dataPointIndex];

                            // Tentukan kategori berdasarkan series
                            let kategori = '';
                            if (seriesIndex === 0) kategori = 'tabungan';
                            else if (seriesIndex === 1) kategori = 'deposito';
                            else if (seriesIndex === 2) kategori = 'total_funding';
                            else if (seriesIndex === 3) kategori = 'pencairan_deposito';

                            console.log('Opening funding modal:', monthLabel, kategori);
                            // Buka modal detail
                            window.showTrendFundingDetail(monthLabel, kategori, type);
                        },
                        dataPointSelection: function(event, chartContext, config) {
                            console.log('Funding data point selected!', config);
                            const monthIndex = config.dataPointIndex;
                            const seriesIndex = config.seriesIndex;
                            const monthLabel = @json($fundingTrendLabels)[monthIndex];

                            // Tentukan kategori berdasarkan series
                            let kategori = '';
                            if (seriesIndex === 0) kategori = 'tabungan';
                            else if (seriesIndex === 1) kategori = 'deposito';
                            else if (seriesIndex === 2) kategori = 'total_funding';
                            else if (seriesIndex === 3) kategori = 'pencairan_deposito';

                            console.log('Opening funding modal from selection:', monthLabel, kategori);
                            // Buka modal detail
                            window.showTrendFundingDetail(monthLabel, kategori, type);
                        }
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: [3, 3, 4, 2],
                    dashArray: [0, 0, 0, 5]
                },
                colors: ['#03c3ec', '#71dd37', '#696cff', '#ff9f43'],
                markers: {
                    size: 6,
                    strokeWidth: 2,
                    strokeColors: '#fff',
                    hover: {
                        size: 9
                    }
                },
                states: {
                    active: {
                        allowMultipleDataPointsSelection: false,
                        filter: {
                            type: 'none'
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    style: {
                        fontSize: '11px',
                        fontWeight: 'bold'
                    },
                    background: {
                        enabled: true,
                        borderRadius: 2,
                        padding: 4,
                        opacity: 0.9
                    },
                    formatter: function(val) {
                        if (isNominal) {
                            return 'Rp ' + val.toFixed(2) + 'M';
                        }
                        return val;
                    }
                },
                xaxis: {
                    categories: @json($fundingTrendLabels),
                    labels: {
                        style: {
                            fontSize: '12px'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: isNominal ? 'Nominal (Miliar Rupiah)' : 'Jumlah Rekening'
                    },
                    labels: {
                        formatter: function(val) {
                            if (isNominal) {
                                return 'Rp ' + val.toFixed(1) + 'M';
                            }
                            return Math.round(val);
                        }
                    }
                },
                grid: {
                    borderColor: '#f1f1f1',
                    strokeDashArray: 4
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'left',
                    fontSize: '13px',
                    markers: {
                        width: 12,
                        height: 12,
                        radius: 2
                    }
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function(val) {
                            if (isNominal) {
                                return 'Rp ' + val.toFixed(2) + ' M';
                            }
                            return val + ' rekening';
                        }
                    }
                }
            });
            fundingTrendChart.render();
            console.log('Funding trend chart rendered (type: ' + type + ')');
        }
    }

    // Initialize with jumlah data
    createFundingTrendChart('jumlah');
}

// Function untuk toggle funding trend chart (pindah ke window scope agar bisa dipanggil dari HTML)
window.toggleFundingTrendChart = function(type) {
    // Update button state
    const btnJumlah = document.getElementById('btnFundingTrendJumlah');
    const btnNominal = document.getElementById('btnFundingTrendNominal');

    if (type === 'jumlah') {
        btnJumlah.classList.remove('btn-outline-primary');
        btnJumlah.classList.add('btn-primary');
        btnNominal.classList.remove('btn-primary');
        btnNominal.classList.add('btn-outline-primary');
    } else {
        btnNominal.classList.remove('btn-outline-primary');
        btnNominal.classList.add('btn-primary');
        btnJumlah.classList.remove('btn-primary');
        btnJumlah.classList.add('btn-outline-primary');
    }

    // Recreate chart with new data
    createFundingTrendChart(type);
}

// Product Trend Charts
let tabunganTrendChart;
let depositoTrendChart;
const tabunganTrendEl = document.querySelector("#tabunganTrendChart");
const depositoTrendEl = document.querySelector("#depositoTrendChart");

function createTabunganTrendChart(type = 'nominal') {
    if (tabunganTrendChart) {
        tabunganTrendChart.destroy();
    }

    fetch(`/dashboard/trend-product-detail?jenis=tabungan&type=${type}`)
        .then(response => response.json())
        .then(data => {
            const series = [];
            const categories = [];

            // Collect all unique months that have data across all products
            const allMonths = new Set();
            data.data.forEach(product => {
                Object.keys(product.data).forEach(monthKey => {
                    allMonths.add(monthKey);
                });
            });

            // Sort months chronologically
            const sortedMonths = Array.from(allMonths).sort();

            // Create month labels from available data
            sortedMonths.forEach(monthKey => {
                const [year, month] = monthKey.split('-');
                const date = new Date(parseInt(year), parseInt(month) - 1, 1);
                const monthLabel = date.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' });
                categories.push(monthLabel);
            });

            // Process data for each product
            data.data.forEach(product => {
                const productData = [];
                const productName = formatProductCode(product.kodeprd, 'tabungan');

                sortedMonths.forEach(monthKey => {
                    const monthData = product.data[monthKey];
                    productData.push(monthData ? (type === 'nominal' ? monthData.nominal : monthData.jumlah) : 0);
                });

                series.push({
                    name: productName,
                    data: productData
                });
            });

            const options = {
                series: series,
                chart: {
                    type: 'line',
                    height: 400,
                    toolbar: {
                        show: true
                    }
                },
                colors: ['#696cff', '#03c3ec', '#fdb528', '#ff5722', '#8592a3', '#71dd37', '#e91e63'],
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                markers: {
                    size: 4,
                    hover: {
                        size: 6
                    }
                },
                xaxis: {
                    categories: categories,
                    title: {
                        text: 'Bulan'
                    }
                },
                yaxis: {
                    title: {
                        text: type === 'nominal' ? 'Nominal (Rp)' : 'Jumlah Rekening'
                    },
                    labels: {
                        formatter: function(value) {
                            if (type === 'nominal') {
                                return formatNominal(value);
                            }
                            return value;
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(value) {
                            if (type === 'nominal') {
                                return formatNominal(value);
                            }
                            return value + ' rekening';
                        }
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'left'
                }
            };

            tabunganTrendChart = new ApexCharts(tabunganTrendEl, options);
            tabunganTrendChart.render();
        })
        .catch(error => {
            console.error('Error loading tabungan trend data:', error);
            tabunganTrendEl.innerHTML = '<div class="text-center text-muted py-4"><i class="ti ti-alert-circle ti-lg mb-2"></i><br>Gagal memuat data</div>';
        });
}

function createDepositoTrendChart(type = 'nominal') {
    if (depositoTrendChart) {
        depositoTrendChart.destroy();
    }

    fetch(`/dashboard/trend-product-detail?jenis=deposito&type=${type}`)
        .then(response => response.json())
        .then(data => {
            const series = [];
            const categories = [];

            // Collect all unique months that have data across all products
            const allMonths = new Set();
            data.data.forEach(product => {
                Object.keys(product.data).forEach(monthKey => {
                    allMonths.add(monthKey);
                });
            });

            // Sort months chronologically
            const sortedMonths = Array.from(allMonths).sort();

            // Create month labels from available data
            sortedMonths.forEach(monthKey => {
                const [year, month] = monthKey.split('-');
                const date = new Date(parseInt(year), parseInt(month) - 1, 1);
                const monthLabel = date.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' });
                categories.push(monthLabel);
            });

            // Process data for each product
            data.data.forEach(product => {
                const productData = [];
                const productName = formatProductCode(product.kdprd, 'deposito');

                sortedMonths.forEach(monthKey => {
                    const monthData = product.data[monthKey];
                    productData.push(monthData ? (type === 'nominal' ? monthData.nominal : monthData.jumlah) : 0);
                });

                series.push({
                    name: productName,
                    data: productData
                });
            });

            const options = {
                series: series,
                chart: {
                    type: 'line',
                    height: 400,
                    toolbar: {
                        show: true
                    }
                },
                colors: ['#696cff', '#03c3ec', '#fdb528', '#ff5722', '#8592a3', '#71dd37', '#e91e63'],
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                markers: {
                    size: 4,
                    hover: {
                        size: 6
                    }
                },
                xaxis: {
                    categories: categories,
                    title: {
                        text: 'Bulan'
                    }
                },
                yaxis: {
                    title: {
                        text: type === 'nominal' ? 'Nominal (Rp)' : 'Jumlah Rekening'
                    },
                    labels: {
                        formatter: function(value) {
                            if (type === 'nominal') {
                                return formatNominal(value);
                            }
                            return value;
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(value) {
                            if (type === 'nominal') {
                                return formatNominal(value);
                            }
                            return value + ' rekening';
                        }
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'left'
                }
            };

            depositoTrendChart = new ApexCharts(depositoTrendEl, options);
            depositoTrendChart.render();
        })
        .catch(error => {
            console.error('Error loading deposito trend data:', error);
            depositoTrendEl.innerHTML = '<div class="text-center text-muted py-4"><i class="ti ti-alert-circle ti-lg mb-2"></i><br>Gagal memuat data</div>';
        });
}

// Toggle functions for product trend charts
window.toggleTabunganTrendChart = function(type) {
    // Update button states
    document.getElementById('btnTabunganTrendNominal').className = type === 'nominal' ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-outline-primary';
    document.getElementById('btnTabunganTrendJumlah').className = type === 'jumlah' ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-outline-primary';

    createTabunganTrendChart(type);
}

window.toggleDepositoTrendChart = function(type) {
    // Update button states
    document.getElementById('btnDepositoTrendNominal').className = type === 'nominal' ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-outline-primary';
    document.getElementById('btnDepositoTrendJumlah').className = type === 'jumlah' ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-outline-primary';

    createDepositoTrendChart(type);
}

// Initialize product trend charts on page load
document.addEventListener('DOMContentLoaded', function() {
    createTabunganTrendChart('jumlah');
    createDepositoTrendChart('jumlah');
});

// Function untuk show detail trend funding
window.showTrendFundingDetail = function(monthLabel, kategori, type) {
    console.log('showTrendFundingDetail called with:', {monthLabel, kategori, type});
    const modal = new bootstrap.Modal(document.getElementById('trendFundingDetailModal'));
    const modalTitle = document.getElementById('modalTrendFundingTitle');
    const modalBody = document.getElementById('modalTrendFundingBody');

    // Parse month and year from label
    const parts = monthLabel.split(' ');
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
    const monthIndex = monthNames.indexOf(parts[0]);
    if (monthIndex === -1) {
        console.error('Invalid month name:', parts[0]);
        return;
    }
    const year = parseInt(parts[1]);
    const month = (monthIndex + 1).toString().padStart(2, '0');

    // Update title
    let kategoriLabel = '';
    if (kategori === 'tabungan') kategoriLabel = 'Tabungan';
    else if (kategori === 'deposito') kategoriLabel = 'Deposito';
    else if (kategori === 'total_funding') kategoriLabel = 'Total Funding';
    else if (kategori === 'pencairan_deposito') kategoriLabel = 'Pencairan Deposito';

    modalTitle.textContent = `Detail ${kategoriLabel} - ${monthLabel}`;

    // Show loading
    modalBody.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div><br>Loading...</div>';
    modal.show();

    // Fetch data
    fetch(`/dashboard/trend-funding-detail?month=${month}&year=${year}&kategori=${kategori}&type=${type}`)
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Received data:', data);
            console.log('Data.data exists:', !!data.data);
            console.log('Data.data length:', data.data ? data.data.length : 'N/A');
            console.log('Data.total:', data.total);

            let html = '';

            if (data.data && data.data.length > 0) {
                const isNominal = type === 'nominal';
                const totalText = isNominal ? formatNominal(data.total) : data.total + ' rekening';
                html += `<div class="mb-3">
                    <h6>Total: ${totalText}</h6>
                    <small class="text-muted">Klik pada rekening untuk detail lebih lanjut</small>
                </div>`;

                html += '<div class="table-responsive"><table class="table table-sm table-hover">';
                html += '<thead><tr>';
                if (kategori === 'tabungan') {
                    html += '<th>No Rekening</th><th>Nama</th><th>Kode Produk</th><th>Saldo</th><th>Tanggal Buka</th>';
                } else if (kategori === 'deposito') {
                    html += '<th>No Bilyet</th><th>Nama</th><th>Kode Produk</th><th>Nominal</th><th>Jangka Waktu</th><th>Tanggal Buka</th>';
                } else if (kategori === 'pencairan_deposito') {
                    html += '<th>No Bilyet</th><th>Nama</th><th>Nominal</th><th>Tanggal Cair</th>';
                } else if (kategori === 'total_funding') {
                    html += '<th>Jenis</th><th>No Rekening/Bilyet</th><th>Nama</th><th>Nominal</th><th>Tanggal</th>';
                }
                html += '</tr></thead><tbody>';

                data.data.forEach(item => {
                    html += '<tr>';
                    if (kategori === 'tabungan') {
                        html += `<td>${item.norek}</td>`;
                        html += `<td>${item.nama}</td>`;
                        html += `<td>${formatProductCode(item.kodeprd, 'tabungan')}</td>`;
                        html += `<td>${formatNominal(item.sahirrp)}</td>`;
                        html += `<td>${item.tgleff}</td>`;
                    } else if (kategori === 'deposito') {
                        html += `<td>${item.nobilyet}</td>`;
                        html += `<td>${item.nama}</td>`;
                        html += `<td>${formatProductCode(item.kdprd, 'deposito')}</td>`;
                        html += `<td>${formatNominal(item.nomrp)}</td>`;
                        html += `<td>${item.jw} bulan</td>`;
                        html += `<td>${item.tglbuka}</td>`;
                    } else if (kategori === 'pencairan_deposito') {
                        html += `<td>${item.nobilyet}</td>`;
                        html += `<td>${item.nama}</td>`;
                        html += `<td>${formatNominal(item.nomrp)}</td>`;
                        html += `<td>${item.tglcair || 'N/A'}</td>`;
                    } else if (kategori === 'total_funding') {
                        html += `<td>${item.jenis}</td>`;
                        html += `<td>${item.no_rek}</td>`;
                        html += `<td>${item.nama}</td>`;
                        html += `<td>${formatNominal(item.nominal)}</td>`;
                        html += `<td>${item.tanggal}</td>`;
                    }
                    html += '</tr>';
                });

                html += '</tbody></table></div>';
            } else {
                html += '<div class="alert alert-warning"><i class="ti ti-info-circle"></i> Tidak ada data untuk kategori ini</div>';
            }

            modalBody.innerHTML = html;
        })
        .catch(error => {
            modalBody.innerHTML = '<div class="alert alert-danger"><i class="ti ti-alert-circle"></i> Gagal memuat data: ' + error.message + '</div>';
            console.error('Error:', error);
        });
}
</script>
@endsection

