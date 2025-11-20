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
    /* Customer Details Modal Styles */
    .customer-detail-row:hover {
        background-color: #f8f9fa !important;
        cursor: pointer;
    }
    .customer-modal .modal-dialog {
        max-width: 900px;
    }
    .clickable-metric {
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .clickable-metric:hover {
        background-color: rgba(0,123,255,0.1) !important;
        transform: scale(1.02);
    }
</style>
@endsection

@section('content')

    <!-- Row 1: KPI Cards Detail (Funding, Lending, NPF) -->
    <div class="row">
        <!-- Funding Card -->
        @if(auth()->user()->role === 'admin' || auth()->user()->role === 'pengurus' || auth()->user()->role === 'funding')
        <div class="col-lg-4 col-md-6 col-12 mb-4">
            <div class="card h-100 border-primary border-2">
                <div class="card-header d-flex justify-content-between bg-label-primary">
                    <div class="card-title mb-0">
                        <h5 class="mb-0 text-primary">💰 Funding</h5>
                        <small class="text-muted">Dana Pihak Ketiga</small>
                    </div>
                    <div class="dropdown">
                        <span class="badge {{ $funding['growth'] >= 0 ? 'bg-success' : 'bg-danger' }}">
                            {{ $funding['growth'] >= 0 ? '+' : '' }}{{ $funding['growth'] }}%
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex flex-column">
                            <div class="d-flex align-items-center mb-1">
                                <h2 class="mb-0 me-2 text-primary fw-bold clickable-metric" onclick="showCustomerDetails('current_total_funding', 'nominal')" title="Klik untuk lihat detail nasabah">
                                    {{ formatNominal($funding['total']) }}
                                </h2>
                            </div>
                            <small class="{{ $funding['growth'] >= 0 ? 'text-success' : 'text-danger' }} fw-medium">
                                <i class="ti ti-trending-{{ $funding['growth'] >= 0 ? 'up' : 'down' }} ti-sm"></i>
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
                            @php
                                // Hitung data real dari database untuk komposisi dana berdasarkan filter
                                $linkageTotal = \DB::table('linkages')->where('period_month', $filterMonth)->where('period_year', $filterYear)->sum('plafon');
                                $abpTotal = \DB::table('depositos')->where('period_month', $filterMonth)->where('period_year', $filterYear)->where('kdprd', '41')->sum('nomrp');
                                $tabunganTotal = \DB::table('tabungans')->where('period_month', $filterMonth)->where('period_year', $filterYear)->sum('sahirrp');
                                $depositoTotal = \DB::table('depositos')->where('period_month', $filterMonth)->where('period_year', $filterYear)->sum('nomrp');

                                // Hitung komposisi yang lebih akurat
                                $dp1_modal = 75000000000; // Modal Utama
                                $dp2_linkage_abp = $linkageTotal + $abpTotal; // Linkage + ABP
                                $dp3_tabungan_deposito = $tabunganTotal + ($depositoTotal - $abpTotal); // Tabungan + Deposito (kecuali ABP)
                                $totalDanaReal = $dp1_modal + $dp2_linkage_abp + $dp3_tabungan_deposito;

                                // Hitung persentase
                                $dp1_pct = $totalDanaReal > 0 ? round(($dp1_modal / $totalDanaReal) * 100, 1) : 0;
                                $dp2_pct = $totalDanaReal > 0 ? round(($dp2_linkage_abp / $totalDanaReal) * 100, 1) : 0;
                                $dp3_pct = $totalDanaReal > 0 ? round(($dp3_tabungan_deposito / $totalDanaReal) * 100, 1) : 0;
                            @endphp
                            <li class="d-flex mb-2 pb-1">
                                <div class="avatar flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded bg-label-primary">
                                        <i class="ti ti-building-bank"></i>
                                    </span>
                                </div>
                                <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                    <div class="me-2">
                                        <small class="text-muted d-block mb-1">Modal Utama</small>
                                        <small class="text-primary fw-medium">
                                            {{ formatNominal($dp1_modal) }}
                                        </small>
                                    </div>
                                    <div class="user-progress d-flex align-items-center gap-1">
                                        <h6 class="mb-0">{{ $dp1_pct }}%</h6>
                                    </div>
                                </div>
                            </li>
                            <li class="d-flex mb-2 pb-1">
                                <div class="avatar flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded bg-label-success">
                                        <i class="ti ti-link"></i>
                                    </span>
                                </div>
                                <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                    <div class="me-2">
                                        <small class="text-muted d-block mb-1">Linkage + ABP</small>
                                        <small class="text-success fw-medium">
                                            {{ formatNominal($dp2_linkage_abp) }}
                                        </small>
                                    </div>
                                    <div class="user-progress d-flex align-items-center gap-1">
                                        <h6 class="mb-0">{{ $dp2_pct }}%</h6>
                                    </div>
                                </div>
                            </li>
                            <li class="d-flex mb-2 pb-1">
                                <div class="avatar flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded bg-label-info">
                                        <i class="ti ti-wallet"></i>
                                    </span>
                                </div>
                                <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                    <div class="me-2">
                                        <small class="text-muted d-block mb-1">Tabungan + Deposito</small>
                                        <small class="text-info fw-medium">
                                            {{ formatNominal($dp3_tabungan_deposito) }}
                                        </small>
                                    </div>
                                    <div class="user-progress d-flex align-items-center gap-1">
                                        <h6 class="mb-0">{{ $dp3_pct }}%</h6>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div class="mt-4">
                        <h6 class="mb-3">🏆 Top 5 Produk Tabungan</h6>
                        <small class="text-muted d-block mb-3">Berdasarkan Nominal Terbanyak</small>
                        <ul class="list-unstyled mb-0">
                            @forelse($topTabunganProducts as $index => $product)
                            <li class="d-flex mb-3">
                                <div class="avatar flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded-circle bg-label-{{ ['primary', 'success', 'info', 'warning', 'danger'][$index] }}">
                                        <i class="ti ti-piggy-bank"></i>
                                    </span>
                                </div>
                                <div class="d-flex w-100 flex-column">
                                    <div class="d-flex justify-content-between mb-1">
                                        <h6 class="mb-0">{{ $product->nama_produk }}</h6>
                                        <small class="text-muted">{{ number_format($product->jumlah_rekening) }} Rekening</small>
                                    </div>
                                    <h6 class="text-{{ ['primary', 'success', 'info', 'warning', 'danger'][$index] }} fw-medium">
                                        {{ formatNominal($product->total_nominal) }}
                                    </h6>
                                </div>
                            </li>
                            @empty
                            <li class="d-flex mb-3">
                                <div class="avatar flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded-circle bg-label-secondary">
                                        <i class="ti ti-info-circle"></i>
                                    </span>
                                </div>
                                <div class="d-flex w-100 flex-column">
                                    <small class="text-muted">Belum ada data produk tabungan</small>
                                </div>
                            </li>
                            @endforelse
                        </ul>
                    </div>

                    <!-- Pencairan Deposito -->
                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-center clickable-metric" onclick="showCustomerDetails('current_pencairan_deposito', 'nominal')" title="Klik untuk lihat detail nasabah">
                            <div>
                                <h6 class="mb-1">Pencairan Deposito</h6>
                                <small class="text-muted">Bulan ini</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-label-warning mb-1">{{ number_format($funding['pencairan']['jumlah']) }} Bilyet</span>
                                <div>
                                    <small class="{{ $funding['pencairan']['growth'] < 0 ? 'text-success' : 'text-danger' }} fw-medium">
                                        <i class="ti ti-trending-{{ $funding['pencairan']['growth'] < 0 ? 'up' : 'down' }}"></i>
                                        {{ formatNominal($funding['pencairan']['total']) }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Lending Card -->
        @if(auth()->user()->role === 'admin' || auth()->user()->role === 'pengurus' || auth()->user()->role === 'lending')
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
        @endif

        <!-- NPF Card -->
        @if(auth()->user()->role === 'admin' || auth()->user()->role === 'pengurus')
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
        @endif
    </div>

    <!-- Row 5.5: Combined Product Trend Chart -->
    @if(auth()->user()->role === 'admin' || auth()->user()->role === 'pengurus' || auth()->user()->role === 'funding')
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">📊 Trend Produk Funding</h5>
                        <small class="text-muted">Perkembangan Tabungan & Deposito per Bulan</small>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <!-- Filter Produk Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-outline-success btn-sm dropdown-toggle" type="button" id="productFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ti ti-package ti-xs me-1"></i>
                                Filter Produk
                            </button>
                            <div class="dropdown-menu" aria-labelledby="productFilterDropdown" style="min-width: 300px; max-height: 400px; overflow-y: auto;">
                                <!-- Total Options -->
                                <div class="px-3 py-2">
                                    <h6 class="mb-2 fw-bold">Pilih Data</h6>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" value="total_tabungan" id="filterTotalTabungan" checked>
                                        <label class="form-check-label" for="filterTotalTabungan">
                                            Total Tabungan
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" value="total_deposito" id="filterTotalDeposito" checked>
                                        <label class="form-check-label" for="filterTotalDeposito">
                                            Total Deposito
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" value="total_linkage" id="filterTotalLinkage" checked>
                                        <label class="form-check-label" for="filterTotalLinkage">
                                            Total Linkage
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" value="total_pencairan_deposito" id="filterTotalPencairanDeposito">
                                        <label class="form-check-label" for="filterTotalPencairanDeposito">
                                            Total Pencairan Deposito
                                        </label>
                                    </div>
                                </div>

                                <div class="dropdown-divider"></div>

                                <!-- Produk Tabungan -->
                                <div class="px-3 py-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0 fw-bold">Produk Tabungan</h6>
                                        <div>
                                            <button type="button" class="btn btn-xs btn-outline-primary me-1" onclick="toggleAllProducts('tabungan', true)">
                                                <i class="ti ti-check ti-xs"></i>
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-secondary" onclick="toggleAllProducts('tabungan', false)">
                                                <i class="ti ti-x ti-xs"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div id="tabunganProductsList" class="row">
                                        <div class="col-12 text-center text-muted py-2">
                                            <small>Loading produk tabungan...</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="dropdown-divider"></div>

                                <!-- Produk Deposito -->
                                <div class="px-3 py-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0 fw-bold">Produk Deposito</h6>
                                        <div>
                                            <button type="button" class="btn btn-xs btn-outline-primary me-1" onclick="toggleAllProducts('deposito', true)">
                                                <i class="ti ti-check ti-xs"></i>
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-secondary" onclick="toggleAllProducts('deposito', false)">
                                                <i class="ti ti-x ti-xs"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div id="depositoProductsList" class="row">
                                        <div class="col-12 text-center text-muted py-2">
                                            <small>Loading produk deposito...</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- View Toggle Buttons -->
                        <div class="btn-group me-2" role="group">
                            <button type="button" class="btn btn-sm btn-primary" id="btnCombinedTrendChart" onclick="toggleCombinedTrendView('chart')">
                                <i class="ti ti-chart-line ti-xs me-1"></i> Chart
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnCombinedTrendTable" onclick="toggleCombinedTrendView('table')">
                                <i class="ti ti-table ti-xs me-1"></i> Table
                            </button>
                        </div>

                        <!-- Type Toggle Buttons -->
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-primary" id="btnCombinedTrendNominal" onclick="toggleCombinedTrendChart('nominal')">
                                <i class="ti ti-cash ti-xs me-1"></i> Nominal
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnCombinedTrendJumlah" onclick="toggleCombinedTrendChart('jumlah')">
                                <i class="ti ti-users ti-xs me-1"></i> Jumlah
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
                        <i class="ti ti-info-circle me-1"></i>
                        <small><strong>Tip:</strong> Gunakan filter untuk menampilkan data yang diinginkan. Secara default menampilkan Total Tabungan dan Total Deposito.</small>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <div id="combinedTrendChart" style="min-height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Row 4.5: AO Funding Performance -->
    @if(auth()->user()->role === 'admin' || auth()->user()->role === 'pengurus' || auth()->user()->role === 'funding')
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">💰 Performa Funding Account Officer</h5>
                        <small class="text-muted">Deposito & ABP per AO (klik untuk detail nasabah)</small>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; position: relative;">
                        <table class="table table-hover mb-0" style="table-layout: fixed; margin-bottom: 0;">
                            <thead style="position: sticky; top: 0; background-color: #f8f9fa; z-index: 10; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                <tr>
                                    <th style="width: 50px; background-color: #f8f9fa;">#</th>
                                    <th style="width: 200px; background-color: #f8f9fa;">Nama AO</th>
                                    <th class="text-center" style="width: 80px; background-color: #f8f9fa;">Deposito</th>
                                    <th class="text-center" style="width: 80px; background-color: #f8f9fa;">ABP</th>
                                    <th class="text-center" style="width: 80px; background-color: #f8f9fa;">Cairkan</th>
                                    <th class="text-end" style="width: 120px; background-color: #f8f9fa;">Nominal Deposito</th>
                                    <th class="text-end" style="width: 120px; background-color: #f8f9fa;">Nominal ABP</th>
                                    <th class="text-end" style="width: 120px; background-color: #f8f9fa;">Nominal Cairkan</th>
                                    <th class="text-end" style="width: 120px; background-color: #f8f9fa;">Total Funding</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($aoFundingData as $index => $ao)
                                <tr class="ao-funding-row" data-ao="{{ $ao['kodeaoh'] }}" style="cursor: pointer;">
                                    <td><strong>{{ $index + 1 }}</strong></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-2">
                                                <span class="avatar-initial rounded-circle bg-label-success">
                                                    {{ strtoupper(substr($ao['nmao'], 0, 2)) }}
                                                </span>
                                            </div>
                                            <strong>{{ $ao['nmao'] }}</strong>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-label-info">{{ number_format($ao['total_deposito']) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-label-warning">{{ number_format($ao['total_abp']) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-label-danger">{{ number_format($ao['total_cairkan']) }}</span>
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-info">
                                            {{ formatNominal($ao['nominal_deposito']) }}
                                        </strong>
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-warning">
                                            {{ formatNominal($ao['nominal_abp']) }}
                                        </strong>
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-danger">
                                            {{ formatNominal($ao['nominal_cairkan']) }}
                                        </strong>
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-primary">
                                            {{ formatNominal($ao['total_funding']) }}
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
    @endif

    <!-- Row 6: Funding Detail Tables -->
    @if(auth()->user()->role === 'admin' || auth()->user()->role === 'pengurus' || auth()->user()->role === 'funding')
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
    @endif

    <!-- Row 7: Nasabah dengan Tabungan DAN Deposito -->
    @if(auth()->user()->role === 'admin' || auth()->user()->role === 'pengurus' || auth()->user()->role === 'funding')
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
    @endif

    <!-- Row 8: Lending Tables -->
    @if(auth()->user()->role === 'admin' || auth()->user()->role === 'pengurus' || auth()->user()->role === 'lending')
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">💰 Top 50 Nasabah dengan Total Pinjaman Terbesar</h5>
                        <small class="text-muted">Data Pinjaman Aktif</small>
                    </div>
                    <div>
                        <span class="badge bg-label-warning">{{ number_format($nasabahLending->count()) }} Nasabah</span>
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
                                    <th class="text-center">Jml Pinjaman</th>
                                    <th class="text-end">Total Pinjaman</th>
                                    <th class="text-end">Total Bunga</th>
                                    <th class="text-end">Total Angsuran</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($nasabahLending as $index => $nasabah)
                                <tr>
                                    <td><strong>{{ $index + 1 }}</strong></td>
                                    <td><code>{{ $nasabah->nocif }}</code></td>
                                    <td>{{ Str::limit($nasabah->nama ?? 'N/A', 30) }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-label-warning">{{ $nasabah->jumlah_pinjaman }}</span>
                                    </td>
                                    <td class="text-end">
                                        {{ formatNominal($nasabah->total_pinjaman) }}
                                    </td>
                                    <td class="text-end">
                                        {{ formatNominal($nasabah->total_bunga) }}
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-warning">
                                            {{ formatNominal($nasabah->total_angsuran) }}
                                        </strong>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Belum ada data</td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($nasabahLending->count() > 0)
                            <tfoot class="table-light sticky-bottom bg-white" style="box-shadow: 0 -2px 4px rgba(0,0,0,0.1);">
                                <tr>
                                    <td colspan="3" class="text-end"><strong>TOTAL (Top 50)</strong></td>
                                    <td class="text-center">
                                        <span class="badge bg-warning">{{ number_format($nasabahLending->sum('jumlah_pinjaman')) }}</span>
                                    </td>
                                    <td class="text-end">
                                        <strong>
                                            @php $totalPinj = $nasabahLending->sum('total_pinjaman'); @endphp
                                            {{ formatNominal($totalPinj) }}
                                        </strong>
                                    </td>
                                    <td class="text-end">
                                        <strong>
                                            @php $totalBunga = $nasabahLending->sum('total_bunga'); @endphp
                                            {{ formatNominal($totalBunga) }}
                                        </strong>
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-warning">
                                            @php $totalAngsuran = $nasabahLending->sum('total_angsuran'); @endphp
                                            {{ formatNominal($totalAngsuran) }}
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
    @endif

    <!-- Row 2: Charts (Monthly Trends & NPF Distribution) -->
    <div class="row">
        <!-- Monthly Trends Chart (hanya untuk admin dan pengurus) -->
        @if(auth()->user()->role === 'admin' || auth()->user()->role === 'pengurus')
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
        @endif

        <!-- NPF Distribution Chart -->
        @if(auth()->user()->role === 'admin' || auth()->user()->role === 'pengurus')
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
        @endif
    </div>

    @if(auth()->user()->role === 'admin' || auth()->user()->role === 'pengurus' || auth()->user()->role === 'lending')
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
                        <h5 class="card-title mb-0">🏆 Performa Account Officer</h5>
                        <small class="text-muted">Berdasarkan Total Outstanding</small>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; position: relative;">
                        <table class="table table-hover mb-0" style="table-layout: fixed; margin-bottom: 0;">
                            <thead style="position: sticky; top: 0; background-color: #f8f9fa; z-index: 10; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                <tr>
                                    <th style="width: 50px; background-color: #f8f9fa;">#</th>
                                    <th style="width: 200px; background-color: #f8f9fa;">Nama AO</th>
                                    <th class="text-center" style="width: 120px; background-color: #f8f9fa;">Jumlah Nasabah</th>
                                    <th class="text-end" style="width: 150px; background-color: #f8f9fa;">Total Outstanding</th>
                                    <th class="text-end" style="width: 150px; background-color: #f8f9fa;">Total Plafon</th>
                                    <th class="text-center" style="width: 100px; background-color: #f8f9fa;">Jumlah NPF</th>
                                    <th class="text-center" style="width: 100px; background-color: #f8f9fa;">NPF Ratio</th>
                                    <th style="width: 200px; background-color: #f8f9fa;">Performance</th>
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
                                    <th colspan="2" class="text-center bg-success text-white">DISBURSE</th>
                                    <th colspan="2" class="text-center bg-primary text-white">OUTSTANDING</th>
                                    <th colspan="5" class="text-center bg-warning text-dark">KOLEKTIBILITAS</th>
                                    <th rowspan="2" class="text-center align-middle">CIF</th>
                                    <th rowspan="2" class="text-center align-middle">NOA</th>
                                </tr>
                                <tr class="table-light">
                                    <th class="text-end bg-success text-white">DISBURSE</th>
                                    <th class="text-center bg-success text-white">%</th>
                                    <th class="text-end bg-primary text-white">OUTSTANDING</th>
                                    <th class="text-center bg-primary text-white">%</th>
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
                                        <td class="text-end">{{ number_format($segment['disburse'], 0, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($segment['pct_disburse'], 2) }}%</td>
                                        <td class="text-end">{{ number_format($segment['outstanding'], 0, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($segment['pct_outstanding'], 2) }}%</td>
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
                                            <small class="text-muted" style="font-size: 9px;">{{ $segment['col1'] ?? 0 }} NOA</small>
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
                                            <small class="text-muted" style="font-size: 9px;">{{ $segment['col2'] ?? 0 }} NOA</small>
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
                                            <small class="text-muted" style="font-size: 9px;">{{ $segment['col3'] ?? 0 }} NOA</small>
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
                                            <small class="text-muted" style="font-size: 9px;">{{ $segment['col4'] ?? 0 }} NOA</small>
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
                                            <small class="text-muted" style="font-size: 9px;">{{ $segment['col5'] ?? 0 }} NOA</small>
                                        </td>
                                        <td class="text-center">{{ number_format($segment['cif'] ?? 0) }}</td>
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
                                        <td class="text-center"><strong>{{ number_format($segment['cif'] ?? 0) }}</strong></td>
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
    @endif


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
            markers: {
                size: 4,
                hover: {
                    size: 6
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return 'Rp ' + val.toFixed(1) + 'M';
                },
                style: {
                    fontSize: '10px',
                    fontWeight: 'bold'
                },
                background: {
                    enabled: true,
                    foreColor: '#fff',
                    padding: 4,
                    borderRadius: 2,
                    borderWidth: 1,
                    borderColor: '#fff',
                    opacity: 0.9
                },
                offsetY: -10
            },
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

    // 3. Segmentasi Bar Chart (Outstanding per Segmentasi)
    const segmentasiEl = document.querySelector('#segmentasiPieChart');
    if (segmentasiEl) {
        const segmentasiData = @json($segmentasiDistribution);
        if (segmentasiData && segmentasiData.values && segmentasiData.values.length > 0) {
            const segmentasiChart = new ApexCharts(segmentasiEl, {
                series: [{
                    data: segmentasiData.values
                }],
                chart: {
                    height: 350,
                    type: 'bar'
                },
                plotOptions: {
                    bar: {
                        dataLabels: {
                            position: 'top'
                        },
                        columnWidth: '45%',
                        distributed: true
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        return val.toFixed(1) + 'M';
                    },
                    offsetY: -20,
                    style: {
                        fontSize: '11px',
                        fontWeight: 600,
                        colors: ['#696cff', '#03c3ec', '#fdb528', '#ff5722', '#8592a3']
                    }
                },
                xaxis: {
                    categories: segmentasiData.labels
                },
                yaxis: {
                    min: 0,
                    labels: {
                        formatter: function(val) {
                            return val + 'M';
                        }
                    }
                },
                colors: ['#696cff', '#03c3ec', '#fdb528', '#ff5722', '#8592a3'],
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
                    height: 400,
                    toolbar: { show: true },
                    zoom: { enabled: true },
                    events: {
                        markerClick: function(event, chartContext, config) {
                            console.log('Marker clicked!', config);
                            const seriesIndex = config.seriesIndex;
                            const dataPointIndex = config.dataPointIndex;
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
                        },
                        click: function(event, chartContext, config) {
                            console.log('Chart clicked!', config);
                            if (config && config.dataPointIndex !== undefined) {
                                const seriesIndex = config.seriesIndex;
                                const dataPointIndex = config.dataPointIndex;
                                const monthLabel = @json($nasabahTrendData['labels'])[dataPointIndex];

                                // Tentukan kategori berdasarkan series
                                let kategori = '';
                                if (seriesIndex === 0) kategori = 'kontrak_baru';
                                else if (seriesIndex === 1) kategori = 'pelunasan_cepat';
                                else if (seriesIndex === 2) kategori = 'kontrak_lunas';

                                console.log('Opening modal from click:', monthLabel, kategori);
                                // Buka modal detail
                                window.showTrendKontrakDetail(monthLabel, kategori);
                            }
                        },
                        dataPointMouseEnter: function(event, chartContext, config) {
                            // Make data labels clickable on hover
                            event.target.style.cursor = 'pointer';
                        },
                        dataPointMouseLeave: function(event, chartContext, config) {
                            // Reset cursor
                            event.target.style.cursor = 'default';
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
                    },
                    offsetY: -10
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

            // Make data labels clickable after chart is rendered
            setTimeout(() => {
                const chartElement = document.querySelector('#nasabahTrendChart');
                if (chartElement) {
                    const dataLabels = chartElement.querySelectorAll('.apexcharts-data-labels text');
                    dataLabels.forEach((label, index) => {
                        label.style.cursor = 'pointer';
                        label.addEventListener('click', function() {
                            // Calculate which data point this label belongs to
                            const seriesIndex = Math.floor(index / @json($nasabahTrendData['labels']).length);
                            const dataPointIndex = index % @json($nasabahTrendData['labels']).length;
                            const monthLabel = @json($nasabahTrendData['labels'])[dataPointIndex];

                            // Tentukan kategori berdasarkan series
                            let kategori = '';
                            if (seriesIndex === 0) kategori = 'kontrak_baru';
                            else if (seriesIndex === 1) kategori = 'pelunasan_cepat';
                            else if (seriesIndex === 2) kategori = 'kontrak_lunas';

                            console.log('Data label clicked:', monthLabel, kategori);
                            // Buka modal detail
                            window.showTrendKontrakDetail(monthLabel, kategori);
                        });
                    });
                }
            }, 500);
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
    // Create map dengan fokus pada Jawa Barat
    const map = L.map('map', {
        center: [-6.6, 106.8], // Pusat Jawa Barat (Bandung)
        zoom: 9,
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
                dataLabels: {
                    enabled: true,
                    formatter: function(value) {
                        if (type === 'nominal') {
                            return formatNominal(value);
                        }
                        return value;
                    },
                    style: {
                        fontSize: '10px',
                        fontWeight: 'bold'
                    },
                    background: {
                        enabled: true,
                        foreColor: '#fff',
                        padding: 4,
                        borderRadius: 2,
                        borderWidth: 1,
                        borderColor: '#fff',
                        opacity: 0.9
                    },
                    offsetY: -10
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
                dataLabels: {
                    enabled: true,
                    formatter: function(value) {
                        if (type === 'nominal') {
                            return formatNominal(value);
                        }
                        return value.toString();
                    },
                    style: {
                        fontSize: '10px',
                        fontWeight: 'bold'
                    },
                    background: {
                        enabled: true,
                        foreColor: '#fff',
                        padding: 4,
                        borderRadius: 2,
                        borderWidth: 1,
                        borderColor: '#fff',
                        opacity: 0.9
                    },
                    offsetY: -10
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

// Combined trend chart variable
let combinedTrendChart = null;
const combinedTrendEl = document.querySelector("#combinedTrendChart");
let currentCombinedTrendType = 'nominal';
let currentCombinedTrendView = 'chart';

// Function to create combined trend view (chart or table)
function createCombinedTrendView(type = 'nominal', view = 'chart') {
    if (combinedTrendChart) {
        combinedTrendChart.destroy();
    }

    // Get selected filters
    const showTotalTabungan = document.getElementById('filterTotalTabungan').checked;
    const showTotalDeposito = document.getElementById('filterTotalDeposito').checked;
    const showTotalLinkage = document.getElementById('filterTotalLinkage').checked;
    const showTotalPencairanDeposito = document.getElementById('filterTotalPencairanDeposito').checked;

    // Get selected products
    const selectedTabunganProducts = Array.from(document.querySelectorAll('#tabunganProductsList input[type="checkbox"]:checked')).map(cb => cb.value);
    const selectedDepositoProducts = Array.from(document.querySelectorAll('#depositoProductsList input[type="checkbox"]:checked')).map(cb => cb.value);

    // Show loading
    combinedTrendEl.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"></div><br>Loading...</div>';

    // Fetch both tabungan and deposito data
    Promise.all([
        (showTotalTabungan || selectedTabunganProducts.length > 0) ? fetch(`/dashboard/trend-product-detail?jenis=tabungan&type=${type}`).then(r => r.json()) : Promise.resolve({data: []}),
        (showTotalDeposito || selectedDepositoProducts.length > 0) ? fetch(`/dashboard/trend-product-detail?jenis=deposito&type=${type}`).then(r => r.json()) : Promise.resolve({data: []}),
        showTotalLinkage ? fetch(`/dashboard/trend-product-detail?jenis=linkage&type=${type}`).then(r => r.json()) : Promise.resolve({data: []}),
        showTotalPencairanDeposito ? fetch(`/dashboard/trend-product-detail?jenis=pencairan_deposito&type=${type}`).then(r => r.json()) : Promise.resolve({data: []})
    ])
    .then(([tabunganData, depositoData, linkageData, pencairanData]) => {
        const series = [];
        const categories = [];
        const tableRows = {};

        // Collect all unique months from all datasets
        const allMonths = new Set();

        if (tabunganData.data) {
            tabunganData.data.forEach(product => {
                Object.keys(product.data).forEach(monthKey => {
                    allMonths.add(monthKey);
                });
            });
        }

        if (depositoData.data) {
            depositoData.data.forEach(product => {
                Object.keys(product.data).forEach(monthKey => {
                    allMonths.add(monthKey);
                });
            });
        }

        if (linkageData.data) {
            linkageData.data.forEach(product => {
                Object.keys(product.data).forEach(monthKey => {
                    allMonths.add(monthKey);
                });
            });
        }

        if (pencairanData.data) {
            pencairanData.data.forEach(product => {
                Object.keys(product.data).forEach(monthKey => {
                    allMonths.add(monthKey);
                });
            });
        }

        // Sort months chronologically
        const sortedMonths = Array.from(allMonths).sort();

        // Create month labels and initialize table rows
        sortedMonths.forEach(monthKey => {
            const [year, month] = monthKey.split('-');
            const date = new Date(parseInt(year), parseInt(month) - 1, 1);
            const monthLabel = date.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' });
            categories.push(monthLabel);
            tableRows[monthKey] = { month: monthLabel };
        });

        // Process tabungan data
        if (tabunganData.data && (showTotalTabungan || selectedTabunganProducts.length > 0)) {
            // Calculate totals for tabungan
            if (showTotalTabungan) {
                const totalTabunganData = [];
                sortedMonths.forEach(monthKey => {
                    let total = 0;
                    tabunganData.data.forEach(product => {
                        const monthData = product.data[monthKey];
                        if (monthData) {
                            total += type === 'nominal' ? monthData.nominal : monthData.jumlah;
                        }
                    });
                    totalTabunganData.push(total);
                    tableRows[monthKey]['Total Tabungan'] = total;
                });

                series.push({
                    name: type === 'nominal' ? 'Total Tabungan' : 'Jumlah Rekening Tabungan',
                    data: totalTabunganData,
                    type: 'line'
                });
            }

            // Add individual selected products
            if (selectedTabunganProducts.length > 0) {
                selectedTabunganProducts.forEach(productCode => {
                    const product = tabunganData.data.find(p => p.kodeprd === productCode);
                    if (product) {
                        const productData = [];
                        const productName = formatProductCode(product.kodeprd, 'tabungan');

                        sortedMonths.forEach(monthKey => {
                            const monthData = product.data[monthKey];
                            const value = monthData ? (type === 'nominal' ? monthData.nominal : monthData.jumlah) : 0;
                            productData.push(value);
                            tableRows[monthKey][productName] = value;
                        });

                        series.push({
                            name: productName,
                            data: productData,
                            type: 'line'
                        });
                    }
                });
            }
        }

        // Process deposito data
        if (depositoData.data && (showTotalDeposito || selectedDepositoProducts.length > 0)) {
            // Calculate totals for deposito
            if (showTotalDeposito) {
                const totalDepositoData = [];
                sortedMonths.forEach(monthKey => {
                    let total = 0;
                    depositoData.data.forEach(product => {
                        const monthData = product.data[monthKey];
                        if (monthData) {
                            total += type === 'nominal' ? monthData.nominal : monthData.jumlah;
                        }
                    });
                    totalDepositoData.push(total);
                    tableRows[monthKey]['Total Deposito'] = total;
                });

                series.push({
                    name: type === 'nominal' ? 'Total Deposito' : 'Jumlah Rekening Deposito',
                    data: totalDepositoData,
                    type: 'line'
                });
            }

            // Add individual selected products
            if (selectedDepositoProducts.length > 0) {
                selectedDepositoProducts.forEach(productCode => {
                    const product = depositoData.data.find(p => p.kdprd === productCode);
                    if (product) {
                        const productData = [];
                        const productName = formatProductCode(product.kdprd, 'deposito');

                        sortedMonths.forEach(monthKey => {
                            const monthData = product.data[monthKey];
                            const value = monthData ? (type === 'nominal' ? monthData.nominal : monthData.jumlah) : 0;
                            productData.push(value);
                            tableRows[monthKey][productName] = value;
                        });

                        series.push({
                            name: productName,
                            data: productData,
                            type: 'line'
                        });
                    }
                });
            }
        }

        // Process linkage data
        if (linkageData.data && showTotalLinkage) {
            // Calculate totals for linkage
            const totalLinkageData = [];
            sortedMonths.forEach(monthKey => {
                let total = 0;
                linkageData.data.forEach(product => {
                    const monthData = product.data[monthKey];
                    if (monthData) {
                        total += type === 'nominal' ? monthData.nominal : monthData.jumlah;
                    }
                });
                totalLinkageData.push(total);
                tableRows[monthKey]['Total Linkage'] = total;
            });

            series.push({
                name: type === 'nominal' ? 'Total Linkage' : 'Jumlah Rekening Linkage',
                data: totalLinkageData,
                type: 'line'
            });
        }

        // Process pencairan deposito data
        if (pencairanData.data && showTotalPencairanDeposito) {
            const totalPencairanData = [];
            sortedMonths.forEach(monthKey => {
                let total = 0;
                pencairanData.data.forEach(product => {
                    const monthData = product.data[monthKey];
                    if (monthData) {
                        total += type === 'nominal' ? monthData.nominal : monthData.jumlah;
                    }
                });
                totalPencairanData.push(total);
                tableRows[monthKey]['Total Pencairan Deposito'] = total;
            });

            series.push({
                name: type === 'nominal' ? 'Total Pencairan Deposito' : 'Jumlah Pencairan Deposito',
                data: totalPencairanData,
                type: 'line'
            });
        }

        // If no data to show
        if (series.length === 0) {
            combinedTrendEl.innerHTML = '<div class="text-center text-muted py-4"><i class="ti ti-info-circle ti-lg mb-2"></i><br>Pilih minimal satu filter data atau produk</div>';
            return;
        }

        // Validate series data before rendering
        const validSeries = series.filter(s => s.data && s.data.length > 0);
        if (validSeries.length === 0) {
            combinedTrendEl.innerHTML = '<div class="text-center text-muted py-4"><i class="ti ti-info-circle ti-lg mb-2"></i><br>Tidak ada data yang valid untuk ditampilkan</div>';
            return;
        }

        // Render based on view type
        if (view === 'table') {
            try {
                renderCombinedTrendTable(tableRows, sortedMonths, type);
            } catch (error) {
                console.error('Error rendering table:', error);
                combinedTrendEl.innerHTML = '<div class="text-center text-muted py-4"><i class="ti ti-alert-circle ti-lg mb-2"></i><br>Gagal merender tabel</div>';
            }
        } else {
            try {
                renderCombinedTrendChart(validSeries, categories, type);
            } catch (error) {
                console.error('Error rendering chart:', error);
                combinedTrendEl.innerHTML = '<div class="text-center text-muted py-4"><i class="ti ti-alert-circle ti-lg mb-2"></i><br>Gagal merender chart</div>';
            }
        }
    })
    .catch(error => {
        console.error('Error loading combined trend data:', error);
        combinedTrendEl.innerHTML = '<div class="text-center text-muted py-4"><i class="ti ti-alert-circle ti-lg mb-2"></i><br>Gagal memuat data</div>';
    });
}

// Function to render combined trend chart
function renderCombinedTrendChart(series, categories, type) {
    try {
        const options = {
            series: series,
            chart: {
                type: 'line',
                height: 400,
                toolbar: {
                    show: true
                },
                events: {
                    markerClick: function(event, chartContext, config) {
                        console.log('Combined trend marker clicked!', config);
                        const seriesIndex = config.seriesIndex;
                        const dataPointIndex = config.dataPointIndex;
                        const seriesName = series[seriesIndex].name;
                        const monthLabel = categories[dataPointIndex];

                        // Determine category based on series name
                        let kategori = '';
                        if (seriesName.includes('Tabungan')) {
                            kategori = 'tabungan';
                        } else if (seriesName.includes('Deposito')) {
                            kategori = 'deposito';
                        } else if (seriesName.includes('Pencairan')) {
                            kategori = 'pencairan_deposito';
                        }

                        if (kategori) {
                            console.log('Opening combined trend modal:', monthLabel, kategori);
                            // Parse month and year from label
                            const parts = monthLabel.split(' ');
                            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
                            const month = monthNames.indexOf(parts[0]) + 1;
                            const year = parseInt(parts[1]);

                            window.showTrendFundingDetail(month, year, kategori);
                        }
                    },
                    dataPointSelection: function(event, chartContext, config) {
                        console.log('Combined trend data point selected!', config);
                        const seriesIndex = config.seriesIndex;
                        const dataPointIndex = config.dataPointIndex;
                        const seriesName = series[seriesIndex].name;
                        const monthLabel = categories[dataPointIndex];

                        // Determine category based on series name
                        let kategori = '';
                        if (seriesName.includes('Tabungan')) {
                            kategori = 'tabungan';
                        } else if (seriesName.includes('Deposito')) {
                            kategori = 'deposito';
                        } else if (seriesName.includes('Pencairan')) {
                            kategori = 'pencairan_deposito';
                        }

                        if (kategori) {
                            console.log('Opening combined trend modal from selection:', monthLabel, kategori);
                            // Parse month and year from label
                            const parts = monthLabel.split(' ');
                            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
                            const month = monthNames.indexOf(parts[0]) + 1;
                            const year = parseInt(parts[1]);

                            window.showTrendFundingDetail(month, year, kategori);
                        }
                    },
                    click: function(event, chartContext, config) {
                        console.log('Combined trend chart clicked!', config);
                        if (config && config.dataPointIndex !== undefined) {
                            const seriesIndex = config.seriesIndex;
                            const dataPointIndex = config.dataPointIndex;
                            const seriesName = series[seriesIndex].name;
                            const monthLabel = categories[dataPointIndex];

                            // Determine category based on series name
                            let kategori = '';
                            if (seriesName.includes('Tabungan')) {
                                kategori = 'tabungan';
                            } else if (seriesName.includes('Deposito')) {
                                kategori = 'deposito';
                            } else if (seriesName.includes('Pencairan')) {
                                kategori = 'pencairan_deposito';
                            }

                            if (kategori) {
                                console.log('Opening combined trend modal from click:', monthLabel, kategori);
                                // Parse month and year from label
                                const parts = monthLabel.split(' ');
                                const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
                                const month = monthNames.indexOf(parts[0]) + 1;
                                const year = parseInt(parts[1]);

                                window.showTrendFundingDetail(month, year, kategori);
                            }
                        }
                    },
                    dataPointMouseEnter: function(event, chartContext, config) {
                        // Make data labels clickable on hover
                        event.target.style.cursor = 'pointer';
                    },
                    dataPointMouseLeave: function(event, chartContext, config) {
                        // Reset cursor
                        event.target.style.cursor = 'default';
                    },
                    mounted: function() {
                        // Chart is fully rendered, clear any loading state
                        console.log('Chart mounted successfully');
                    },
                    updated: function() {
                        // Chart updated successfully
                        console.log('Chart updated successfully');
                    },
                    animationEnd: function() {
                        console.log('Chart animation ended');
                    }
                }
            },
            colors: ['#696cff', '#03c3ec', '#fdb528', '#ff5722', '#8592a3', '#71dd37', '#e91e63', '#9c27b0', '#607d8b', '#795548'],
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
            dataLabels: {
                enabled: true,
                formatter: function(value) {
                    if (type === 'nominal') {
                        if (value >= 1000000000) {
                            return 'Rp ' + (value / 1000000000).toFixed(2) + ' M';
                        } else if (value >= 1000000) {
                            return 'Rp ' + (value / 1000000).toFixed(2) + ' Jt';
                        } else if (value >= 100000) {
                            return 'Rp ' + (value / 1000).toFixed(0) + ' Rb';
                        } else if (value >= 1000) {
                            return 'Rp ' + (value / 1000).toFixed(1) + ' Rb';
                        } else {
                            return 'Rp ' + value.toFixed(0);
                        }
                    }
                    return value + ' rekening';
                },
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
                offsetY: -10
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

        // Destroy existing chart if it exists
        if (combinedTrendChart) {
            combinedTrendChart.destroy();
        }

        console.log('Creating new ApexCharts instance with series:', series.length, 'categories:', categories.length);
        combinedTrendChart = new ApexCharts(combinedTrendEl, options);

        // Clear the loading state before rendering
        combinedTrendEl.innerHTML = '';

        combinedTrendChart.render();

        // Make data labels clickable after chart is rendered
        setTimeout(() => {
            const chartElement = document.querySelector('#combinedTrendChart');
            if (chartElement) {
                const dataLabels = chartElement.querySelectorAll('.apexcharts-data-labels text');
                dataLabels.forEach((label, index) => {
                    label.style.cursor = 'pointer';
                    label.addEventListener('click', function() {
                        // Calculate which data point this label belongs to
                        const totalSeries = series.length;
                        const totalCategories = categories.length;
                        const seriesIndex = Math.floor(index / totalCategories);
                        const dataPointIndex = index % totalCategories;
                        const seriesName = series[seriesIndex].name;
                        const monthLabel = categories[dataPointIndex];

                        // Determine category based on series name
                        let kategori = '';
                        if (seriesName.includes('Tabungan')) {
                            kategori = 'tabungan';
                        } else if (seriesName.includes('Deposito')) {
                            kategori = 'deposito';
                        } else if (seriesName.includes('Pencairan')) {
                            kategori = 'pencairan_deposito';
                        }

                        if (kategori) {
                            console.log('Data label clicked:', monthLabel, kategori);
                            // Parse month and year from label
                            const parts = monthLabel.split(' ');
                            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
                            const month = monthNames.indexOf(parts[0]) + 1;
                            const year = parseInt(parts[1]);

                            window.showTrendFundingDetail(month, year, kategori);
                        }
                    });
                });
            }
        }, 500);

        // Clear loading state after a short delay to ensure chart is rendered
        setTimeout(() => {
            // Check if the element still has loading content
            if (combinedTrendEl.innerHTML.includes('spinner-border')) {
                console.log('Clearing loading state after chart render');
            }
        }, 100);
    } catch (error) {
        console.error('Error in renderCombinedTrendChart:', error);
        throw error; // Re-throw to be caught by the calling function
    }
}

// Function to render combined trend table
function renderCombinedTrendTable(tableRows, sortedMonths, type) {
    try {
        let html = '<div class="table-responsive"><table class="table table-striped table-hover">';
        html += '<thead><tr><th>Bulan</th>';

        // Get all column names from first row
        const firstRow = tableRows[sortedMonths[0]];
        Object.keys(firstRow).forEach(key => {
            if (key !== 'month') {
                html += '<th class="text-end">' + key + '</th>';
            }
        });
        html += '</tr></thead><tbody>';

        // Table body
        sortedMonths.forEach(monthKey => {
            const row = tableRows[monthKey];
            html += '<tr>';
            html += '<td><strong>' + row.month + '</strong></td>';

            Object.keys(row).forEach(key => {
                if (key !== 'month') {
                    const value = row[key];
                    let displayValue = '';
                    if (type === 'nominal') {
                        displayValue = formatNominal(value);
                    } else {
                        displayValue = value.toLocaleString('id-ID');
                    }
                    html += '<td class="text-end">' + displayValue + '</td>';
                }
            });
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        combinedTrendEl.innerHTML = html;
    } catch (error) {
        console.error('Error in renderCombinedTrendTable:', error);
        throw error; // Re-throw to be caught by the calling function
    }
}

// Toggle function for combined trend view (chart/table)
window.toggleCombinedTrendView = function(view) {
    currentCombinedTrendView = view;
    document.getElementById('btnCombinedTrendChart').className = view === 'chart' ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-outline-primary';
    document.getElementById('btnCombinedTrendTable').className = view === 'table' ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-outline-primary';
    createCombinedTrendView(currentCombinedTrendType, view);
};

// Toggle function for combined trend chart type
window.toggleCombinedTrendChart = function(type) {
    currentCombinedTrendType = type;
    document.getElementById('btnCombinedTrendNominal').className = type === 'nominal' ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-outline-primary';
    document.getElementById('btnCombinedTrendJumlah').className = type === 'jumlah' ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-outline-primary';
    createCombinedTrendView(type, currentCombinedTrendView);
};

// Function to load available products for filtering
function loadProductFilters() {
    const tabunganPromise = fetch('/dashboard/trend-product-detail?jenis=tabungan&type=nominal')
        .then(response => response.json())
        .then(data => {
            const tabunganList = document.getElementById('tabunganProductsList');
            tabunganList.innerHTML = '';

            if (data.data && data.data.length > 0) {
                data.data.forEach(product => {
                    const productName = formatProductCode(product.kodeprd, 'tabungan');
                    const col = document.createElement('div');
                    col.className = 'col-6 mb-1';
                    col.innerHTML = `
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="${product.kodeprd}" id="tabungan_${product.kodeprd}">
                            <label class="form-check-label small" for="tabungan_${product.kodeprd}">
                                ${productName}
                            </label>
                        </div>
                    `;
                    tabunganList.appendChild(col);
                });
            } else {
                tabunganList.innerHTML = '<div class="col-12 text-center text-muted py-2"><small>Tidak ada produk tabungan</small></div>';
            }
        })
        .catch(error => {
            console.error('Error loading tabungan products:', error);
            document.getElementById('tabunganProductsList').innerHTML = '<div class="col-12 text-center text-muted py-2"><small>Gagal memuat produk tabungan</small></div>';
        });

    const depositoPromise = fetch('/dashboard/trend-product-detail?jenis=deposito&type=nominal')
        .then(response => response.json())
        .then(data => {
            const depositoList = document.getElementById('depositoProductsList');
            depositoList.innerHTML = '';

            if (data.data && data.data.length > 0) {
                data.data.forEach(product => {
                    const productName = formatProductCode(product.kdprd, 'deposito');
                    const col = document.createElement('div');
                    col.className = 'col-6 mb-1';
                    col.innerHTML = `
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="${product.kdprd}" id="deposito_${product.kdprd}">
                            <label class="form-check-label small" for="deposito_${product.kdprd}">
                                ${productName}
                            </label>
                        </div>
                    `;
                    depositoList.appendChild(col);
                });
            } else {
                depositoList.innerHTML = '<div class="col-12 text-center text-muted py-2"><small>Tidak ada produk deposito</small></div>';
            }
        })
        .catch(error => {
            console.error('Error loading deposito products:', error);
            document.getElementById('depositoProductsList').innerHTML = '<div class="col-12 text-center text-muted py-2"><small>Gagal memuat produk deposito</small></div>';
        });

    return Promise.all([tabunganPromise, depositoPromise]);
}

// Function to handle product filter changes
function handleProductFilterChange() {
    const currentType = document.getElementById('btnCombinedTrendNominal').classList.contains('btn-primary') ? 'nominal' : 'jumlah';
    createCombinedTrendView(currentType, currentCombinedTrendView);
}

// Function to select/deselect all products in a category
function toggleAllProducts(category, selectAll) {
    const listId = category === 'tabungan' ? 'tabunganProductsList' :
                   category === 'deposito' ? 'depositoProductsList' : 'pembiayaanProductsList';
    const checkboxes = document.querySelectorAll(`#${listId} input[type="checkbox"]`);

    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll;
    });

    handleProductFilterChange();
}

// Function to handle filter changes
function handleFilterChange() {
    const currentType = document.getElementById('btnCombinedTrendNominal').classList.contains('btn-primary') ? 'nominal' : 'jumlah';
    createCombinedTrendView(currentType, currentCombinedTrendView);
}

// Add event listeners for filter checkboxes
document.addEventListener('DOMContentLoaded', function() {
    // Load product filters first
    loadProductFilters().catch(error => {
        console.error('Error loading product filters:', error);
    });

    // Add event listeners for data filter checkboxes
    const filterCheckboxes = ['filterTotalTabungan', 'filterTotalDeposito', 'filterTotalLinkage', 'filterTotalPencairanDeposito'];
    filterCheckboxes.forEach(id => {
        const checkbox = document.getElementById(id);
        if (checkbox) {
            checkbox.addEventListener('change', handleFilterChange);
        }
    });

    // Add event listeners for product filter checkboxes (delegated)
    document.addEventListener('change', function(e) {
        if (e.target.matches('#tabunganProductsList input[type="checkbox"], #depositoProductsList input[type="checkbox"]')) {
            handleProductFilterChange();
        }
    });

    // Initialize combined trend chart
    createCombinedTrendView('nominal', 'chart');
});

// Helper function for formatting nominal in JavaScript
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

// Helper function for formatting product codes
function formatProductCode(code, type) {
    if (!code) return 'N/A';

    // For tabungan products
    if (type === 'tabungan') {
        const tabunganProducts = {
            '01': 'Tabungan Simpel',
            '02': 'Tabungan Berjangka',
            '03': 'Tabungan Pendidikan',
            '04': 'Tabungan Haji',
            '05': 'Tabungan Emas',
            '06': 'Tabungan Valas',
            '07': 'Tabungan Payroll',
            '08': 'Tabungan Bisnis',
            '09': 'Tabungan Premium',
            '10': 'Tabungan Digital'
        };
        return tabunganProducts[code] || `Tabungan ${code}`;
    }

    // For deposito products
    if (type === 'deposito') {
        const depositoProducts = {
            '01': 'Deposito 1 Bulan',
            '02': 'Deposito 3 Bulan',
            '03': 'Deposito 6 Bulan',
            '04': 'Deposito 12 Bulan',
            '05': 'Deposito 24 Bulan',
            '06': 'Deposito Valas',
            '07': 'Deposito Premium',
            '08': 'Deposito Bisnis',
            '09': 'Deposito Online',
            '10': 'Deposito Khusus'
        };
        return depositoProducts[code] || `Deposito ${code}`;
    }

    // For pembiayaan products
    if (type === 'pembiayaan') {
        const pembiayaanProducts = {
            'NON SINDIKASI': 'Non Sindikasi',
            'SINDIKASI-01': 'Sindikasi 1',
            'SINDIKASI-02': 'Sindikasi 2',
            'SINDIKASI-03': 'Sindikasi 3',
            'SINDIKASI-04': 'Sindikasi 4'
        };
        return pembiayaanProducts[code] || `Pembiayaan ${code}`;
    }

    return code;
}

// Customer Details Modal Functions
function showCustomerDetails(jenis, type) {
    // Jika jenis dimulai dengan 'current_', gunakan logika trend seperti chart kontrak
    if (jenis.startsWith('current_')) {
        const currentDate = new Date();
        const currentMonth = currentDate.getMonth() + 1;
        const currentYear = currentDate.getFullYear();

        let kategori = '';
        if (jenis === 'current_tabungan') kategori = 'tabungan';
        else if (jenis === 'current_deposito') kategori = 'deposito';
        else if (jenis === 'current_pencairan_deposito') kategori = 'pencairan_deposito';
        else if (jenis === 'current_total_funding') kategori = 'total_funding';

        showTrendFundingDetail(currentMonth, currentYear, kategori);
        return;
    }

    // Get modal elements
    const modal = document.getElementById('customerDetailsModal');
    const modalTitle = document.getElementById('customerDetailsModalTitle');
    const modalBody = document.getElementById('customerDetailsModalBody');

    // Dispose of any existing modal instance to prevent conflicts
    const existingModal = bootstrap.Modal.getInstance(modal);
    if (existingModal) {
        existingModal.dispose();
    }

    // Clear any existing backdrop
    const existingBackdrop = document.querySelector('.modal-backdrop');
    if (existingBackdrop) {
        existingBackdrop.remove();
    }

    // Remove modal-open class from body if it exists
    document.body.classList.remove('modal-open');

    const modalInstance = new bootstrap.Modal(modal, {
        backdrop: true,
        keyboard: true
    });

    // Set modal title
    let title = '';
    switch(jenis) {
        case 'tabungan':
            title = 'Detail Nasabah Tabungan';
            break;
        case 'deposito':
            title = 'Detail Nasabah Deposito';
            break;
        case 'pencairan_deposito':
            title = 'Detail Pencairan Deposito';
            break;
        case 'total_funding':
            title = 'Detail Total Funding';
            break;
        default:
            title = 'Detail Nasabah';
    }
    modalTitle.textContent = title;

    // Show loading
    modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"></div><br>Loading...</div>';
    modalInstance.show();

    // Add event listener for when modal is hidden to ensure proper cleanup
    modal.addEventListener('hidden.bs.modal', function() {
        console.log('Customer details modal hidden, cleaning up...');
        // Dispose of the modal instance
        modalInstance.dispose();
        // Ensure backdrop is removed
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
        // Remove modal-open class from body
        document.body.classList.remove('modal-open');
        // Restore body scroll
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }, { once: true });

    // Fetch customer data
    fetch(`/dashboard/customer-details?jenis=${jenis}&type=${type}&limit=100`)
        .then(response => response.json())
        .then(data => {
            if (data.customers && data.customers.length > 0) {
                let html = '<div class="table-responsive">';
                html += '<table class="table table-striped table-hover">';
                html += '<thead><tr>';
                html += '<th>No</th>';
                html += '<th>Nama Nasabah</th>';
                html += '<th>No Rekening</th>';
                html += '<th>Nominal</th>';
                html += '<th>Periode</th>';
                html += '</tr></thead><tbody>';

                data.customers.forEach((customer, index) => {
                    html += '<tr class="customer-detail-row">';
                    html += `<td>${index + 1}</td>`;
                    html += `<td>${customer.nama}</td>`;
                    html += `<td>${customer.account}</td>`;
                    html += `<td>${formatNominal(customer.amount)}</td>`;
                    html += `<td>${customer.period}</td>`;
                    html += '</tr>';
                });

                html += '</tbody></table>';
                html += `<div class="mt-3 text-muted">Menampilkan ${data.total} nasabah teratas</div>`;
                html += '</div>';
                modalBody.innerHTML = html;
            } else {
                modalBody.innerHTML = '<div class="text-center text-muted py-4"><i class="ti ti-info-circle ti-lg mb-2"></i><br>Tidak ada data nasabah</div>';
            }
        })
        .catch(error => {
            console.error('Error loading customer details:', error);
            modalBody.innerHTML = `
                <div class="alert alert-danger d-flex justify-content-between align-items-center">
                    <div><i class="ti ti-alert-circle me-2"></i>Gagal memuat data nasabah: ${error.message}</div>
                    <button type="button" class="btn-close" onclick="bootstrap.Modal.getInstance(document.getElementById('customerDetailsModal')).hide()" aria-label="Close"></button>
                </div>
            `;
        });
}
</script>

<!-- Customer Details Modal -->
<div class="modal fade customer-modal" id="customerDetailsModal" tabindex="-1" aria-labelledby="customerDetailsModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="customerDetailsModalTitle">Detail Nasabah</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="customerDetailsModalBody">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

    <script>
    // Global modal cleanup function
    function cleanupModal() {
        console.log('Running global modal cleanup...');
        // Dispose of any existing modal instances
        const modal = document.getElementById('customerDetailsModal');
        if (modal) {
            const existingModal = bootstrap.Modal.getInstance(modal);
            if (existingModal) {
                existingModal.dispose();
            }
        }

        // Clear any existing backdrop
        const existingBackdrop = document.querySelector('.modal-backdrop');
        if (existingBackdrop) {
            existingBackdrop.remove();
        }

        // Remove modal-open class from body
        document.body.classList.remove('modal-open');

        // Restore body scroll
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';

        console.log('Modal cleanup completed');
    }

    // Add global modal cleanup on page load/unload
    window.addEventListener('beforeunload', cleanupModal);
    window.addEventListener('unload', cleanupModal);
    // Function untuk show detail trend funding (seperti showTrendKontrakDetail)
    window.showTrendFundingDetail = function(month, year, kategori) {
        // Get modal elements
        const modal = document.getElementById('customerDetailsModal');
        const modalTitle = document.getElementById('customerDetailsModalTitle');
        const modalBody = document.getElementById('customerDetailsModalBody');

        // Dispose of any existing modal instance to prevent conflicts
        const existingModal = bootstrap.Modal.getInstance(modal);
        if (existingModal) {
            existingModal.dispose();
        }

        // Clear any existing backdrop
        const existingBackdrop = document.querySelector('.modal-backdrop');
        if (existingBackdrop) {
            existingBackdrop.remove();
        }

        // Remove modal-open class from body if it exists
        document.body.classList.remove('modal-open');

        const modalInstance = new bootstrap.Modal(modal, {
            backdrop: true,
            keyboard: true
        });

        // Update title
        let kategoriLabel = '';
        if (kategori === 'tabungan') kategoriLabel = 'Tabungan';
        else if (kategori === 'deposito') kategoriLabel = 'Deposito';
        else if (kategori === 'pencairan_deposito') kategoriLabel = 'Pencairan Deposito';

        const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                           'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const monthLabel = monthNames[month - 1] + ' ' + year;

        modalTitle.innerHTML = '<i class="ti ti-wallet"></i> Detail ' + kategoriLabel + ' - ' + monthLabel;

        // Show loading
        modalBody.innerHTML = `
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;

        modalInstance.show();

        // Add event listener for when modal is hidden to ensure proper cleanup
        modal.addEventListener('hidden.bs.modal', function() {
            console.log('Trend funding detail modal hidden, cleaning up...');
            // Dispose of the modal instance
            modalInstance.dispose();
            // Ensure backdrop is removed
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            // Remove modal-open class from body
            document.body.classList.remove('modal-open');
            // Restore body scroll
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }, { once: true });

        // Fetch detail data
        fetch(`/dashboard/trend-funding-detail?month=${month}&year=${year}&kategori=${kategori}`)
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
                html += '<small>Total: ' + data.summary.total_nasabah.toLocaleString('id-ID') + ' nasabah | ';
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
                html += '<th>No</th><th>No. Rekening</th><th>Nama Nasabah</th><th>CIF</th><th>Nominal</th>';
                html += '</tr>';
                html += '</thead>';
                html += '<tbody>';

                if (data.nasabah && data.nasabah.length > 0) {
                    data.nasabah.forEach((item, index) => {
                        html += '<tr>';
                        html += '<td>' + (index + 1) + '</td>';
                        html += '<td><small>' + item.account + '</small></td>';
                        html += '<td><small>' + item.nama + '</small></td>';
                        html += '<td><small>' + (item.nocif || '-') + '</small></td>';
                        html += '<td class="text-end"><small>Rp ' + (item.nominal / 1000000).toFixed(1) + ' Jt</small></td>';
                        html += '</tr>';
                    });
                } else {
                    html += '<tr><td colspan="5" class="text-center">Tidak ada data</td></tr>';
                }

                html += '</tbody></table>';
                html += '</div>';

                modalBody.innerHTML = html;
            })
            .catch(error => {
                console.error('Error fetching funding detail:', error);
                modalBody.innerHTML = `
                    <div class="alert alert-danger d-flex justify-content-between align-items-center">
                        <div>Terjadi kesalahan saat memuat data detail funding: ${error.message}</div>
                        <button type="button" class="btn-close" onclick="bootstrap.Modal.getInstance(document.getElementById('customerDetailsModal')).hide()" aria-label="Close"></button>
                    </div>
                `;
            });
    }

    // Function to format nominal values consistently with PHP formatNominal function
    function formatNominalJS(amount) {
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

    // Function to show AO Customer Details for specific month and category
    window.showAOCustomerDetails = function(ao, month, category) {
        console.log('Opening AO customer details for ao:', ao, 'month:', month, 'category:', category);

        // Get modal elements
        const modal = document.getElementById('customerDetailsModal');
        const modalTitle = document.getElementById('customerDetailsModalTitle');
        const modalBody = document.getElementById('customerDetailsModalBody');

        if (!modal) {
            console.error('Modal element not found');
            return;
        }

        // Dispose of any existing modal instance to prevent conflicts
        const existingModal = bootstrap.Modal.getInstance(modal);
        if (existingModal) {
            existingModal.dispose();
        }

        // Clear any existing backdrop
        const existingBackdrop = document.querySelector('.modal-backdrop');
        if (existingBackdrop) {
            existingBackdrop.remove();
        }

        // Remove modal-open class from body if it exists
        document.body.classList.remove('modal-open');

        // Determine category label
        let categoryLabel = '';
        if (category === 'deposito') categoryLabel = 'Deposito';
        else if (category === 'abp') categoryLabel = 'ABP';
        else if (category === 'pencairan') categoryLabel = 'Pencairan';
        else if (category === 'total') categoryLabel = 'Total Funding';

        // Determine month label
        let monthLabel = '';
        if (month === 'all') {
            monthLabel = 'Seluruh Tahun ' + new Date().getFullYear();
        } else {
            const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                               'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            monthLabel = monthNames[parseInt(month) - 1] + ' ' + new Date().getFullYear();
        }

        // Update title
        modalTitle.innerHTML = '<i class="ti ti-users"></i> Detail Nasabah Funding - ' + monthLabel + ' (AO: ' + ao + ')';

        // Show loading with category buttons
        modalBody.innerHTML = `
            <div class="text-center p-4">
                <div class="mb-3">
                    <div class="btn-group" role="group" aria-label="Kategori Funding">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="loadAOCustomerData('${ao}', '${month}', 'deposito')">
                            <i class="ti ti-wallet me-1"></i>Deposito
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="loadAOCustomerData('${ao}', '${month}', 'abp')">
                            <i class="ti ti-building-bank me-1"></i>ABP
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="loadAOCustomerData('${ao}', '${month}', 'pencairan')">
                            <i class="ti ti-cash-off me-1"></i>Pencairan
                        </button>
                    </div>
                </div>
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Pilih kategori funding untuk melihat data nasabah...</p>
            </div>
        `;

        // Initialize and show modal
        const bsModal = new bootstrap.Modal(modal, {
            backdrop: true, // Allow closing by clicking outside
            keyboard: true  // Allow closing with escape key
        });
        bsModal.show();

        // Add event listener for when modal is hidden to ensure proper cleanup
        modal.addEventListener('hidden.bs.modal', function() {
            console.log('Modal hidden, cleaning up...');
            // Dispose of the modal instance
            bsModal.dispose();
            // Ensure backdrop is removed
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            // Remove modal-open class from body
            document.body.classList.remove('modal-open');
            // Restore body scroll
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }, { once: true }); // Use once: true to avoid multiple listeners

        // Fetch customer details
        fetch(`/dashboard/ao-customer-details/${encodeURIComponent(ao)}/${month}/${category}`)
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Received customer data:', data);
                loadAOCustomerData(ao, month, category, data);
            })
            .catch(error => {
                console.error('Error fetching AO customer details:', error);
                modalBody.innerHTML = `
                    <div class="alert alert-danger d-flex justify-content-between align-items-center">
                        <div>Terjadi kesalahan saat memuat data nasabah: ${error.message}</div>
                        <button type="button" class="btn-close" onclick="bootstrap.Modal.getInstance(document.getElementById('customerDetailsModal')).hide()" aria-label="Close"></button>
                    </div>
                `;
            });
    }

    // Function to load AO customer data with category buttons
    window.loadAOCustomerData = function(ao, month, category, data = null) {
        console.log('Loading AO customer data for ao:', ao, 'month:', month, 'category:', category);

        const modalBody = document.getElementById('customerDetailsModalBody');

        // Determine month label
        let monthLabel = '';
        if (month === 'all') {
            monthLabel = 'Seluruh Tahun ' + new Date().getFullYear();
        } else {
            const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                               'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            monthLabel = monthNames[parseInt(month) - 1] + ' ' + new Date().getFullYear();
        }

        // Determine category label and button classes
        let categoryLabel = '';
        let depositoBtnClass = 'btn-outline-primary';
        let abpBtnClass = 'btn-outline-success';
        let pencairanBtnClass = 'btn-outline-danger';

        if (category === 'deposito') {
            categoryLabel = 'Deposito';
            depositoBtnClass = 'btn-primary';
        } else if (category === 'abp') {
            categoryLabel = 'ABP';
            abpBtnClass = 'btn-success';
        } else if (category === 'pencairan') {
            categoryLabel = 'Pencairan';
            pencairanBtnClass = 'btn-danger';
        }

        if (!data) {
            // Show loading for the selected category
            modalBody.innerHTML = `
                <div class="text-center p-4">
                    <div class="mb-3">
                        <div class="btn-group" role="group" aria-label="Kategori Funding">
                            <button type="button" class="btn ${depositoBtnClass} btn-sm" onclick="loadAOCustomerData('${ao}', '${month}', 'deposito')">
                                <i class="ti ti-wallet me-1"></i>Deposito
                            </button>
                            <button type="button" class="btn ${abpBtnClass} btn-sm" onclick="loadAOCustomerData('${ao}', '${month}', 'abp')">
                                <i class="ti ti-building-bank me-1"></i>ABP
                            </button>
                            <button type="button" class="btn ${pencairanBtnClass} btn-sm" onclick="loadAOCustomerData('${ao}', '${month}', 'pencairan')">
                                <i class="ti ti-cash-off me-1"></i>Pencairan
                            </button>
                        </div>
                    </div>
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat data ${categoryLabel.toLowerCase()}...</p>
                </div>
            `;

            // Fetch data
            fetch(`/dashboard/ao-customer-details/${encodeURIComponent(ao)}/${month}/${category}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(fetchedData => {
                    loadAOCustomerData(ao, month, category, fetchedData);
                })
                .catch(error => {
                    console.error('Error fetching AO customer data:', error);
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="ti ti-alert-circle"></i> Gagal memuat data. Silakan coba lagi.
                        </div>
                    `;
                });
            return;
        }

        // Display data with category buttons
        let html = '<div class="container-fluid">';

        // Category buttons
        html += '<div class="row mb-3">';
        html += '<div class="col-12 text-center">';
        html += '<div class="btn-group" role="group" aria-label="Kategori Funding">';
        html += '<button type="button" class="btn ' + depositoBtnClass + ' btn-sm" onclick="loadAOCustomerData(\'' + ao + '\', \'' + month + '\', \'deposito\')">';
        html += '<i class="ti ti-wallet me-1"></i>Deposito';
        html += '</button>';
        html += '<button type="button" class="btn ' + abpBtnClass + ' btn-sm" onclick="loadAOCustomerData(\'' + ao + '\', \'' + month + '\', \'abp\')">';
        html += '<i class="ti ti-building-bank me-1"></i>ABP';
        html += '</button>';
        html += '<button type="button" class="btn ' + pencairanBtnClass + ' btn-sm" onclick="loadAOCustomerData(\'' + ao + '\', \'' + month + '\', \'pencairan\')">';
        html += '<i class="ti ti-cash-off me-1"></i>Pencairan';
        html += '</button>';
        html += '</div>';
        html += '</div>';
        html += '</div>';

        // Summary
        html += '<div class="row mb-3">';
        html += '<div class="col-12">';
        html += '<div class="alert alert-info d-flex align-items-center" role="alert">';
        html += '<i class="ti ti-info-circle me-2"></i>';
        html += '<div>';
        html += '<strong>' + categoryLabel + ' - ' + monthLabel + '</strong><br>';
        html += '<small>AO: ' + (data.ao_name || ao) + ' | Total: ' + data.customers.length + ' rekening | ';
        html += 'Nominal: ' + formatNominalJS(data.total_nominal) + '</small>';
        html += '</div>';
        html += '</div>';
        html += '</div>';

        // Customer table
        html += '<div class="row">';
        html += '<div class="col-12">';
        html += '<div class="table-responsive">';
        html += '<table class="table table-sm table-striped table-hover">';
        html += '<thead class="table-dark">';
        html += '<tr>';
        html += '<th style="width: 50px;">No</th>';
        html += '<th style="width: 120px;">No. Bilyet</th>';
        html += '<th>Nama Nasabah</th>';
        html += '<th style="width: 120px;">Nominal</th>';
        html += '<th style="width: 100px;">Tgl Buka</th>';
        html += '<th style="width: 100px;">Jatuh Tempo</th>';
        html += '<th style="width: 80px;">Status</th>';
        html += '</tr>';
        html += '</thead>';
        html += '<tbody>';

        if (data.customers && data.customers.length > 0) {
            data.customers.forEach((customer, index) => {
                const statusClass = customer.is_cairkan ? 'text-danger' : 'text-success';
                const statusText = customer.status;
                html += '<tr>';
                html += '<td>' + (index + 1) + '</td>';
                html += '<td><small>' + (customer.nobilyet || '-') + '</small></td>';
                html += '<td><small>' + (customer.nama || '-') + '</small></td>';
                html += '<td class="text-end"><small>' + (customer.nomrp_formatted || 'Rp 0') + '</small></td>';
                html += '<td><small>' + (customer.tglbuka || '-') + '</small></td>';
                html += '<td><small>' + (customer.tgljtempo || '-') + '</small></td>';
                html += '<td><small class="' + statusClass + '">' + statusText + '</small></td>';
                html += '</tr>';
            });
        } else {
            html += '<tr><td colspan="7" class="text-center">Tidak ada data nasabah untuk kategori ini</td></tr>';
        }

        html += '</tbody></table>';
        html += '</div>';
        html += '</div>';
        html += '</div>';

        modalBody.innerHTML = html;
    }
    window.showAOFundingDetail = function(kodeaoh) {
        console.log('showAOFundingDetail called with kodeaoh:', kodeaoh);

        // Get modal elements
        const modal = document.getElementById('customerDetailsModal');
        const modalTitle = document.getElementById('customerDetailsModalTitle');
        const modalBody = document.getElementById('customerDetailsModalBody');

        if (!modal) {
            console.error('Modal element not found');
            return;
        }

        // Dispose of any existing modal instance to prevent conflicts
        const existingModal = bootstrap.Modal.getInstance(modal);
        if (existingModal) {
            existingModal.dispose();
        }

        // Clear any existing backdrop
        const existingBackdrop = document.querySelector('.modal-backdrop');
        if (existingBackdrop) {
            existingBackdrop.remove();
        }

        // Remove modal-open class from body if it exists
        document.body.classList.remove('modal-open');

        // Update title
        modalTitle.innerHTML = '<i class="ti ti-calendar"></i> Detail Funding AO per Bulan: ' + kodeaoh;

        // Show loading
        modalBody.innerHTML = `
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Memuat data...</p>
            </div>
        `;

        // Initialize and show modal
        const bsModal = new bootstrap.Modal(modal, {
            backdrop: true, // Allow closing by clicking outside
            keyboard: true  // Allow closing with escape key
        });
        bsModal.show();

        // Add event listener for when modal is hidden to ensure proper cleanup
        modal.addEventListener('hidden.bs.modal', function() {
            console.log('Funding detail modal hidden, cleaning up...');
            // Dispose of the modal instance
            bsModal.dispose();
            // Ensure backdrop is removed
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            // Remove modal-open class from body
            document.body.classList.remove('modal-open');
            // Restore body scroll
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }, { once: true }); // Use once: true to avoid multiple listeners

        // Fetch AO funding detail data
        fetch(`/dashboard/ao-funding-detail/${encodeURIComponent(kodeaoh)}`)
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Received data:', data);

                let html = '<div class="container-fluid">';

                // Summary
                html += '<div class="row mb-3">';
                html += '<div class="col-12">';
                html += '<div class="alert alert-primary d-flex align-items-center" role="alert">';
                html += '<i class="ti ti-info-circle me-2"></i>';
                html += '<div>';
                html += '<strong>' + (data.ao_name || data.ao_code) + ' (' + data.ao_code + ')</strong><br>';
                html += '<small>Tahun: ' + data.year + ' | Total Deposito: ' + (data.totals.deposito_count || 0).toLocaleString('id-ID') + ' | ';
                html += 'Total ABP: ' + (data.totals.abp_count || 0).toLocaleString('id-ID') + ' | ';
                html += 'Total Pencairan: ' + (data.totals.pencairan_count || 0).toLocaleString('id-ID') + '<br>';
                html += 'Total Nominal: Rp ' + ((data.totals.total_nominal || 0) / 1000000000).toFixed(2) + ' Miliar</small>';
                html += '</div>';
                html += '</div>';
                html += '</div>';

                // Monthly Table
                html += '<div class="row">';
                html += '<div class="col-12">';
                html += '<div class="table-responsive">';
                html += '<table class="table table-sm table-striped table-hover">';
                html += '<thead class="table-dark">';
                html += '<tr>';
                html += '<th class="text-center">Bulan</th>';
                html += '<th class="text-center">Deposito</th>';
                html += '<th class="text-center">ABP</th>';
                html += '<th class="text-center">Pencairan</th>';
                html += '<th class="text-center">Total</th>';
                html += '</tr>';
                html += '<tr>';
                html += '<th class="text-center">Nominal / Jumlah</th>';
                html += '<th class="text-center">Rp / rekening</th>';
                html += '<th class="text-center">Rp / rekening</th>';
                html += '<th class="text-center">Rp / rekening</th>';
                html += '<th class="text-center">Rp / rekening</th>';
                html += '</tr>';
                html += '</thead>';
                html += '<tbody>';

                if (data.monthly_data && data.monthly_data.length > 0) {
                    data.monthly_data.forEach((monthData, index) => {
                        html += '<tr>';
                        html += '<td class="text-center fw-bold">' + monthData.month_name + '</td>';

                        // Deposito
                        html += '<td class="text-center ao-detail-cell" data-month="' + monthData.month + '" data-category="deposito" data-ao="' + data.ao_code + '" style="cursor: pointer;">';
                        html += '<div>' + formatNominalJS(monthData.deposito.nominal) + '</div>';
                        html += '<small class="text-muted">' + monthData.deposito.count + ' rekening</small>';
                        html += '</td>';

                        // ABP
                        html += '<td class="text-center ao-detail-cell" data-month="' + monthData.month + '" data-category="abp" data-ao="' + data.ao_code + '" style="cursor: pointer;">';
                        html += '<div>' + formatNominalJS(monthData.abp.nominal) + '</div>';
                        html += '<small class="text-muted">' + monthData.abp.count + ' rekening</small>';
                        html += '</td>';

                        // Pencairan
                        html += '<td class="text-center ao-detail-cell" data-month="' + monthData.month + '" data-category="pencairan" data-ao="' + data.ao_code + '" style="cursor: pointer;">';
                        html += '<div class="text-danger">' + formatNominalJS(monthData.pencairan.nominal) + '</div>';
                        html += '<small class="text-muted text-danger">' + monthData.pencairan.count + ' rekening</small>';
                        html += '</td>';

                        // Total
                        html += '<td class="text-center ao-detail-cell" data-month="' + monthData.month + '" data-category="total" data-ao="' + data.ao_code + '" style="cursor: pointer;">';
                        html += '<div>' + formatNominalJS(monthData.total.nominal) + '</div>';
                        html += '<small class="text-muted">' + monthData.total.count + ' rekening</small>';
                        html += '</td>';                        html += '</tr>';
                    });

                } else {
                    html += '<tr><td colspan="5" class="text-center">Tidak ada data funding</td></tr>';
                }

                html += '</tbody></table>';
                html += '</div>';
                html += '</div>';
                html += '</div>';

                modalBody.innerHTML = html;
            })
            .catch(error => {
                console.error('Error fetching AO funding detail:', error);
                modalBody.innerHTML = `
                    <div class="alert alert-danger d-flex justify-content-between align-items-center">
                        <div>Terjadi kesalahan saat memuat data detail funding AO: ${error.message}</div>
                        <button type="button" class="btn-close" onclick="bootstrap.Modal.getInstance(document.getElementById('customerDetailsModal')).hide()" aria-label="Close"></button>
                    </div>
                `;
                // Allow modal to be closed on error
                bsModal._config.backdrop = true;
                bsModal._config.keyboard = true;
            });
    }

    // Add click handler for AO funding rows
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, attaching AO funding row click handlers');
        document.addEventListener('click', function(e) {
            console.log('Click event detected, target:', e.target);

            // Handle AO funding table rows
            if (e.target.closest('.ao-funding-row')) {
                console.log('AO funding row clicked');
                e.preventDefault();
                const row = e.target.closest('.ao-funding-row');
                const kodeaoh = row.getAttribute('data-ao');
                console.log('AO funding row clicked, kodeaoh:', kodeaoh);

                if (kodeaoh) {
                    showAOFundingDetail(kodeaoh);
                } else {
                    console.error('No data-ao attribute found on clicked row');
                }
            }

            // Handle AO detail cells
            if (e.target.closest('.ao-detail-cell')) {
                console.log('AO detail cell clicked');
                e.preventDefault();
                const cell = e.target.closest('.ao-detail-cell');
                const month = cell.getAttribute('data-month');
                const category = cell.getAttribute('data-category');
                const ao = cell.getAttribute('data-ao');
                console.log('AO detail cell clicked, month:', month, 'category:', category, 'ao:', ao);

                if (month && category && ao) {
                    showAOCustomerDetails(ao, month, category);
                } else {
                    console.error('Missing data attributes on clicked cell');
                }
            }
        });
    });
    </script>

@endsection

