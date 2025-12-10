@php
    function formatCurrencyPDF($amount) {
        if ($amount >= 1000000000) {
            return 'Rp ' . number_format($amount / 1000000000, 2, ',', '.') . ' M';
        } elseif ($amount >= 1000000) {
            return 'Rp ' . number_format($amount / 1000000, 2, ',', '.') . ' Jt';
        } elseif ($amount >= 1000) {
            return 'Rp ' . number_format($amount / 1000, 1, ',', '.') . ' Rb';
        } else {
            return 'Rp ' . number_format($amount, 0, ',', '.');
        }
    }
@endphp
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard FinBoard - {{ $filterMonth }}-{{ $filterYear }}</title>
    <style>
        @page {
            margin: 20mm;
            size: A4;
        }

        body {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #007bff;
            font-size: 24px;
            margin: 0 0 10px 0;
            font-weight: bold;
        }

        .header .subtitle {
            color: #666;
            font-size: 14px;
            margin: 5px 0;
        }

        .export-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 10px;
            border: 1px solid #dee2e6;
        }

        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #007bff;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }

        .card {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-bottom: 20px;
            background: white;
        }

        .card-header {
            background: #f8f9fa;
            padding: 10px 15px;
            border-bottom: 1px solid #dee2e6;
            font-weight: bold;
            font-size: 14px;
        }

        .card-body {
            padding: 15px;
        }

        .metric-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .metric-row {
            display: table-row;
        }

        .metric-cell {
            display: table-cell;
            padding: 8px;
            border: 1px solid #dee2e6;
            text-align: center;
            vertical-align: middle;
        }

        .metric-label {
            font-weight: bold;
            background: #f8f9fa;
        }

        .metric-value {
            font-size: 16px;
            font-weight: bold;
            color: #007bff;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }

        .table th,
        .table td {
            border: 1px solid #dee2e6;
            padding: 6px 8px;
            text-align: left;
        }

        .table th {
            background: #f8f9fa;
            font-weight: bold;
            text-align: center;
        }

        .table .text-right {
            text-align: right;
        }

        .table .text-center {
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 10px;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
        }

        .badge-success {
            background: #28a745;
            color: white;
        }

        .badge-danger {
            background: #dc3545;
            color: white;
        }

        .badge-warning {
            background: #ffc107;
            color: #212529;
        }

        .badge-info {
            background: #17a2b8;
            color: white;
        }

        .row {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .col-6 {
            display: table-cell;
            width: 50%;
            padding: 0 10px;
            vertical-align: top;
        }

        .col-12 {
            display: table-cell;
            width: 100%;
            padding: 0 10px;
            vertical-align: top;
        }

        .financial-highlights {
            margin-bottom: 20px;
        }

        .financial-highlights .highlight-grid {
            display: table;
            width: 100%;
        }

        .financial-highlights .highlight-row {
            display: table-row;
        }

        .financial-highlights .highlight-cell {
            display: table-cell;
            width: 33.33%;
            padding: 10px;
            border: 1px solid #dee2e6;
            vertical-align: top;
        }

        .highlight-item {
            text-align: center;
            padding: 10px;
        }

        .highlight-icon {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .highlight-value {
            font-size: 16px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }

        .highlight-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 3px;
        }

        .highlight-change {
            font-size: 10px;
            font-weight: bold;
        }

        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
        .text-warning { color: #ffc107; }
        .text-info { color: #17a2b8; }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 10px;
            color: #666;
        }

        @media print {
            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>📊 Dashboard FinBoard</h1>
        <div class="subtitle">Laporan Keuangan BPRS Bina Ummah Sejahtera</div>
        <div class="subtitle">Periode: {{ $filterMonth }}-{{ $filterYear }}</div>
        <div class="subtitle">Diekspor pada: {{ $exportDate }}</div>
    </div>

    <!-- Export Info -->
    <div class="export-info">
        <strong>Informasi Export:</strong><br>
        User: {{ $user->name ?? 'N/A' }} | Role: {{ $user->role ?? 'N/A' }}<br>
        Filter: Bulan {{ $filterMonth }} Tahun {{ $filterYear }}<br>
        Generated: {{ $exportDate }}
    </div>

    <!-- Financial Highlights -->
    @if(isset($financialHighlights) && $financialHighlights)
    <div class="section">
        <div class="section-title">📈 Financial Highlights</div>
        <div class="financial-highlights">
            <div class="row g-3">
                <div class="col-12 mb-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-muted" style="font-size: 12px;">Periode: {{ $filterMonth }}-{{ $filterYear }}</h6>
                        <small class="text-muted" style="font-size: 10px;">Data Statis</small>
                    </div>
                </div>

                <!-- Laba Rugi Section -->
                <div class="col-lg-6 col-md-12">
                    <div class="mb-3">
                        <small class="category-header" style="font-size: 11px; font-weight: bold; color: #007bff;">💰 Laba Rugi</small>
                    </div>
                    <div class="row g-3">
                        <!-- Pendapatan -->
                        @if(isset($financialHighlights->pendapatan))
                        <div class="col-12 mb-3">
                            <div class="card financial-highlight-card h-100 w-100" style="min-height: 80px; border: 1px solid #dee2e6; border-radius: 8px;">
                                <div class="card-body d-flex align-items-center p-2">
                                    <div class="avatar avatar-sm me-2 flex-shrink-0" style="background: linear-gradient(135deg, #28a745, #20c997); border-radius: 12px; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                        <i class="ti ti-currency-dollar" style="color: white; font-size: 1.2rem;"></i>
                                    </div>
                                    <div class="card-content flex-grow-1">
                                        <h6 class="card-title fw-bold mb-1" style="font-size: 10px; color: #28a745;">Pendapatan</h6>
                                        <h4 class="text-dark fw-bold mb-0" style="font-size: 14px;">{{ formatCurrencyPDF($financialHighlights->pendapatan) }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Biaya -->
                        @if(isset($financialHighlights->biaya))
                        <div class="col-12 mb-3">
                            <div class="card financial-highlight-card h-100 w-100" style="min-height: 80px; border: 1px solid #dee2e6; border-radius: 8px;">
                                <div class="card-body d-flex align-items-center p-2">
                                    <div class="avatar avatar-sm me-2 flex-shrink-0" style="background: linear-gradient(135deg, #6c757d, #5a6268); border-radius: 12px; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                        <i class="ti ti-receipt" style="color: white; font-size: 1.2rem;"></i>
                                    </div>
                                    <div class="card-content flex-grow-1">
                                        <h6 class="card-title fw-bold mb-1" style="font-size: 10px; color: #6c757d;">Biaya</h6>
                                        <h4 class="text-dark fw-bold mb-0" style="font-size: 14px;">{{ formatCurrencyPDF($financialHighlights->biaya) }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Laba Rugi -->
                        @if(isset($financialHighlights->laba_rugi))
                        <div class="col-12 mb-3">
                            <div class="card financial-highlight-card h-100 w-100" style="min-height: 80px; border: 1px solid #dee2e6; border-radius: 8px;">
                                <div class="card-body d-flex align-items-center p-2">
                                    <div class="avatar avatar-sm me-2 flex-shrink-0" style="background: linear-gradient(135deg, #28a745, #20c997); border-radius: 12px; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                        <i class="ti ti-coins" style="color: white; font-size: 1.2rem;"></i>
                                    </div>
                                    <div class="card-content flex-grow-1">
                                        <h6 class="card-title fw-bold mb-1" style="font-size: 10px; color: #28a745;">Laba/Rugi</h6>
                                        <h4 class="text-dark fw-bold mb-0" style="font-size: 14px;">{{ formatCurrencyPDF($financialHighlights->laba_rugi) }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Posisi Keuangan Section -->
                <div class="col-lg-6 col-md-12">
                    <div class="mb-3">
                        <small class="category-header" style="font-size: 11px; font-weight: bold; color: #007bff;">🏦 Posisi Keuangan</small>
                    </div>
                    <div class="row g-3">
                        <!-- Aset -->
                        @if(isset($financialHighlights->aset))
                        <div class="col-12 mb-3">
                            <div class="card financial-highlight-card h-100 w-100" style="min-height: 80px; border: 1px solid #dee2e6; border-radius: 8px;">
                                <div class="card-body d-flex align-items-center p-2">
                                    <div class="avatar avatar-sm me-2 flex-shrink-0" style="background: linear-gradient(135deg, #ffc107, #fd7e14); border-radius: 12px; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                        <i class="ti ti-building-bank" style="color: white; font-size: 1.2rem;"></i>
                                    </div>
                                    <div class="card-content flex-grow-1">
                                        <h6 class="card-title fw-bold mb-1" style="font-size: 10px; color: #ffc107;">Aset</h6>
                                        <h4 class="text-dark fw-bold mb-0" style="font-size: 14px;">{{ formatCurrencyPDF($financialHighlights->aset) }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- DPK -->
                        @if(isset($financialHighlights->dpk))
                        <div class="col-12 mb-3">
                            <div class="card financial-highlight-card h-100 w-100" style="min-height: 80px; border: 1px solid #dee2e6; border-radius: 8px;">
                                <div class="card-body d-flex align-items-center p-2">
                                    <div class="avatar avatar-sm me-2 flex-shrink-0" style="background: linear-gradient(135deg, #007bff, #6610f2); border-radius: 12px; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                        <i class="ti ti-wallet" style="color: white; font-size: 1.2rem;"></i>
                                    </div>
                                    <div class="card-content flex-grow-1">
                                        <h6 class="card-title fw-bold mb-1" style="font-size: 10px; color: #007bff;">DPK</h6>
                                        <h4 class="text-dark fw-bold mb-0" style="font-size: 14px;">{{ formatCurrencyPDF($financialHighlights->dpk) }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Pembiayaan -->
                        @if(isset($financialHighlights->pembiayaan))
                        <div class="col-12 mb-3">
                            <div class="card financial-highlight-card h-100 w-100" style="min-height: 80px; border: 1px solid #dee2e6; border-radius: 8px;">
                                <div class="card-body d-flex align-items-center p-2">
                                    <div class="avatar avatar-sm me-2 flex-shrink-0" style="background: linear-gradient(135deg, #dc3545, #fd7e14); border-radius: 12px; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                        <i class="ti ti-cash" style="color: white; font-size: 1.2rem;"></i>
                                    </div>
                                    <div class="card-content flex-grow-1">
                                        <h6 class="card-title fw-bold mb-1" style="font-size: 10px; color: #dc3545;">Pembiayaan</h6>
                                        <h4 class="text-dark fw-bold mb-0" style="font-size: 14px;">{{ formatCurrencyPDF($financialHighlights->pembiayaan) }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Rasio Section -->
            <div class="row mt-4">
                <!-- Rasio Modal & Profitabilitas -->
                <div class="col-lg-4 col-md-12">
                    <div class="mb-3">
                        <small class="category-header" style="font-size: 11px; font-weight: bold; color: #007bff;">📊 Rasio Modal & Profitabilitas</small>
                    </div>
                    <div class="row g-3">
                        <!-- CAR -->
                        @if(isset($financialHighlights->car))
                        <div class="col-12 mb-3">
                            <div class="card financial-highlight-card h-100 w-100" style="min-height: 80px; border: 1px solid #dee2e6; border-radius: 8px;">
                                <div class="card-body d-flex align-items-center p-2">
                                    <div class="avatar avatar-sm me-2 flex-shrink-0" style="background: linear-gradient(135deg, #007bff, #6610f2); border-radius: 12px; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                        <i class="ti ti-shield-check" style="color: white; font-size: 1.2rem;"></i>
                                    </div>
                                    <div class="card-content flex-grow-1">
                                        <h6 class="card-title fw-bold mb-1" style="font-size: 10px; color: #007bff;">CAR</h6>
                                        <h4 class="text-dark fw-bold mb-0" style="font-size: 14px;">{{ number_format($financialHighlights->car, 2, ',', '.') }}%</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- ROA -->
                        @if(isset($financialHighlights->roa))
                        <div class="col-12 mb-3">
                            <div class="card financial-highlight-card h-100 w-100" style="min-height: 80px; border: 1px solid #dee2e6; border-radius: 8px;">
                                <div class="card-body d-flex align-items-center p-2">
                                    <div class="avatar avatar-sm me-2 flex-shrink-0" style="background: linear-gradient(135deg, #28a745, #20c997); border-radius: 12px; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                        <i class="ti ti-trending-up" style="color: white; font-size: 1.2rem;"></i>
                                    </div>
                                    <div class="card-content flex-grow-1">
                                        <h6 class="card-title fw-bold mb-1" style="font-size: 10px; color: #28a745;">ROA</h6>
                                        <h4 class="text-dark fw-bold mb-0" style="font-size: 14px;">{{ number_format($financialHighlights->roa, 2, ',', '.') }}%</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- ROE -->
                        @if(isset($financialHighlights->roe))
                        <div class="col-12 mb-3">
                            <div class="card financial-highlight-card h-100 w-100" style="min-height: 80px; border: 1px solid #dee2e6; border-radius: 8px;">
                                <div class="card-body d-flex align-items-center p-2">
                                    <div class="avatar avatar-sm me-2 flex-shrink-0" style="background: linear-gradient(135deg, #17a2b8, #138496); border-radius: 12px; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                        <i class="ti ti-chart-bar" style="color: white; font-size: 1.2rem;"></i>
                                    </div>
                                    <div class="card-content flex-grow-1">
                                        <h6 class="card-title fw-bold mb-1" style="font-size: 10px; color: #17a2b8;">ROE</h6>
                                        <h4 class="text-dark fw-bold mb-0" style="font-size: 14px;">{{ number_format($financialHighlights->roe, 2, ',', '.') }}%</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Rasio Likuiditas & Risiko -->
                <div class="col-lg-4 col-md-12">
                    <div class="mb-3">
                        <small class="category-header" style="font-size: 11px; font-weight: bold; color: #007bff;">📊 Rasio Likuiditas & Risiko</small>
                    </div>
                    <div class="row g-3">
                        <!-- Cash Ratio -->
                        @if(isset($financialHighlights->cash_ratio))
                        <div class="col-12 mb-3">
                            <div class="card financial-highlight-card h-100 w-100" style="min-height: 80px; border: 1px solid #dee2e6; border-radius: 8px;">
                                <div class="card-body d-flex align-items-center p-2">
                                    <div class="avatar avatar-sm me-2 flex-shrink-0" style="background: linear-gradient(135deg, #28a745, #20c997); border-radius: 12px; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                        <i class="ti ti-cash-banknote" style="color: white; font-size: 1.2rem;"></i>
                                    </div>
                                    <div class="card-content flex-grow-1">
                                        <h6 class="card-title fw-bold mb-1" style="font-size: 10px; color: #28a745;">Cash Ratio</h6>
                                        <h4 class="text-dark fw-bold mb-0" style="font-size: 14px;">{{ number_format($financialHighlights->cash_ratio, 2, ',', '.') }}%</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- NPF -->
                        @if(isset($financialHighlights->npf))
                        <div class="col-12 mb-3">
                            <div class="card financial-highlight-card h-100 w-100" style="min-height: 80px; border: 1px solid #dee2e6; border-radius: 8px;">
                                <div class="card-body d-flex align-items-center p-2">
                                    <div class="avatar avatar-sm me-2 flex-shrink-0" style="background: linear-gradient(135deg, #dc3545, #c82333); border-radius: 12px; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                        <i class="ti ti-alert-triangle" style="color: white; font-size: 1.2rem;"></i>
                                    </div>
                                    <div class="card-content flex-grow-1">
                                        <h6 class="card-title fw-bold mb-1" style="font-size: 10px; color: #dc3545;">NPF</h6>
                                        <h4 class="text-dark fw-bold mb-0" style="font-size: 14px;">{{ number_format($financialHighlights->npf, 2, ',', '.') }}%</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- FDR -->
                        @if(isset($financialHighlights->fdr))
                        <div class="col-12 mb-3">
                            <div class="card financial-highlight-card h-100 w-100" style="min-height: 80px; border: 1px solid #dee2e6; border-radius: 8px;">
                                <div class="card-body d-flex align-items-center p-2">
                                    <div class="avatar avatar-sm me-2 flex-shrink-0" style="background: linear-gradient(135deg, #ffc107, #fd7e14); border-radius: 12px; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                        <i class="ti ti-percentage" style="color: white; font-size: 1.2rem;"></i>
                                    </div>
                                    <div class="card-content flex-grow-1">
                                        <h6 class="card-title fw-bold mb-1" style="font-size: 10px; color: #ffc107;">FDR</h6>
                                        <h4 class="text-dark fw-bold mb-0" style="font-size: 14px;">{{ number_format($financialHighlights->fdr, 2, ',', '.') }}%</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Rasio Efisiensi -->
                <div class="col-lg-4 col-md-12">
                    <div class="mb-3">
                        <small class="category-header" style="font-size: 11px; font-weight: bold; color: #007bff;">📊 Rasio Efisiensi</small>
                    </div>
                    <div class="row g-3">
                        <!-- BOPO -->
                        @if(isset($financialHighlights->bopo))
                        <div class="col-12 mb-3">
                            <div class="card financial-highlight-card h-100 w-100" style="min-height: 80px; border: 1px solid #dee2e6; border-radius: 8px;">
                                <div class="card-body d-flex align-items-center p-2">
                                    <div class="avatar avatar-sm me-2 flex-shrink-0" style="background: linear-gradient(135deg, #6c757d, #5a6268); border-radius: 12px; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                        <i class="ti ti-calculator" style="color: white; font-size: 1.2rem;"></i>
                                    </div>
                                    <div class="card-content flex-grow-1">
                                        <h6 class="card-title fw-bold mb-1" style="font-size: 10px; color: #6c757d;">BOPO</h6>
                                        <h4 class="text-dark fw-bold mb-0" style="font-size: 14px;">{{ number_format($financialHighlights->bopo, 2, ',', '.') }}%</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Charts Section -->
    <div class="section">
        <div class="section-title">💰 Funding (Dana Pihak Ketiga)</div>

        <div class="row">
            <div class="col-6">
                <div class="card">
                    <div class="card-header">Total Funding</div>
                    <div class="card-body">
                        <div style="text-align: center; font-size: 18px; font-weight: bold; color: #007bff;">
                            {{ formatCurrencyPDF($funding['total'] ?? 0) }}
                        </div>
                        <div style="text-align: center; margin-top: 10px;">
                            <span class="badge {{ ($funding['growth'] ?? 0) >= 0 ? 'badge-success' : 'badge-danger' }}">
                                {{ ($funding['growth'] ?? 0) >= 0 ? '+' : '' }}{{ $funding['growth'] ?? 0 }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6">
                <div class="card">
                    <div class="card-header">Pencairan Deposito Bulan Ini</div>
                    <div class="card-body">
                        <div style="text-align: center;">
                            <div style="font-size: 16px; font-weight: bold; color: #dc3545;">
                                {{ $funding['pencairan']['jumlah'] ?? 0 }} Bilyet
                            </div>
                            <div style="font-size: 14px; margin-top: 5px;">
                                Rp {{ number_format($funding['pencairan']['total'] ?? 0, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Tabungan Products -->
        @if(isset($topTabunganProducts) && $topTabunganProducts->count() > 0)
        <div class="card">
            <div class="card-header">🏆 Top 5 Produk Tabungan</div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Produk</th>
                            <th class="text-center">Jumlah Rekening</th>
                            <th class="text-right">Total Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topTabunganProducts as $index => $product)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $product->nama_produk }}</td>
                            <td class="text-center">{{ number_format($product->jumlah_rekening) }}</td>
                            <td class="text-right">Rp {{ number_format($product->total_nominal, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <!-- Lending Section -->
    <div class="section">
        <div class="section-title">💳 Lending (Pembiayaan & Kredit)</div>

        <div class="row">
            <div class="col-6">
                <div class="card">
                    <div class="card-header">Total Outstanding</div>
                    <div class="card-body">
                        <div style="text-align: center; font-size: 18px; font-weight: bold; color: #28a745;">
                            Rp {{ number_format($lending['total'] ?? 0, 0, ',', '.') }}
                        </div>
                        <div style="text-align: center; margin-top: 10px; font-size: 12px; color: #666;">
                            {{ number_format($lending['nasabah'] ?? 0) }} Nasabah Aktif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6">
                <div class="card">
                    <div class="card-header">Total Plafon (Disbursement)</div>
                    <div class="card-body">
                        <div style="text-align: center; font-size: 18px; font-weight: bold; color: #17a2b8;">
                            Rp {{ number_format($lending['plafon_awal'] ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rate Information -->
        <div class="card">
            <div class="card-header">Informasi Rate</div>
            <div class="card-body">
                <div class="metric-grid">
                    <div class="metric-row">
                        <div class="metric-cell metric-label">Rate Flat</div>
                        <div class="metric-cell metric-value">{{ $lending['rate_flat'] ?? 0 }}%</div>
                        <div class="metric-cell metric-label">Rate Efektif</div>
                        <div class="metric-cell metric-value">{{ $lending['rate_eff'] ?? 0 }}%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- NPF & Kolektibilitas Section -->
    <div class="section">
        <div class="section-title">📊 NPF & Kolektibilitas</div>

        <div class="row">
            <div class="col-6">
                <div class="card">
                    <div class="card-header">NPF Ratio</div>
                    <div class="card-body">
                        <div style="text-align: center; font-size: 24px; font-weight: bold; color: #dc3545;">
                            {{ $npf['ratio'] ?? 0 }}%
                        </div>
                        <div style="text-align: center; margin-top: 10px;">
                            <div style="font-size: 14px; color: #666;">Total NPF</div>
                            <div style="font-size: 16px; font-weight: bold;">
                                Rp {{ number_format($npf['total'] ?? 0, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6">
                <div class="card">
                    <div class="card-header">Tunggakan Pokok</div>
                    <div class="card-body">
                        <div style="text-align: center; font-size: 18px; font-weight: bold; color: #ffc107;">
                            Rp {{ number_format($npf['tunggakan_pokok'] ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolektibilitas Details -->
        @if(isset($kolektibilitasComparison) && $kolektibilitasComparison->count() > 0)
        <div class="card">
            <div class="card-header">Detail Kategori Kolektibilitas</div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th>Nama Kategori</th>
                            <th class="text-center">Jumlah Nasabah</th>
                            <th class="text-right">Total Outstanding</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($kolektibilitasComparison as $kolektibilitas)
                        <tr>
                            <td class="text-center">
                                <span class="badge badge-info">{{ $kolektibilitas['kategori'] }}</span>
                            </td>
                            <td>{{ $kolektibilitas['nama_kategori'] }}</td>
                            <td class="text-center">{{ number_format($kolektibilitas['jumlah_nasabah']) }}</td>
                            <td class="text-right">Rp {{ number_format($kolektibilitas['total_outstanding'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <!-- Charts Section -->
    <div class="section">
        <div class="section-title">📊 Analisis & Tren</div>

        <!-- Monthly Trends & Top Products -->
        <div class="row">
            <div class="col-6">
                <div class="card">
                    <div class="card-header">📈 Tren Bulanan</div>
                    <div class="card-body">
                        <div style="text-align: center; padding: 40px; color: #666;">
                            <i class="ti ti-chart-line" style="font-size: 48px;"></i>
                            <p style="margin-top: 10px;">Grafik Tren Bulanan</p>
                            <small>Data Plafon vs Outstanding per Bulan</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6">
                <div class="card">
                    <div class="card-header">🏆 Top 5 Produk</div>
                    <div class="card-body">
                        <div style="text-align: center; padding: 40px; color: #666;">
                            <i class="ti ti-bar-chart" style="font-size: 48px;"></i>
                            <p style="margin-top: 10px;">Grafik Top Produk</p>
                            <small>Outstanding Terbesar per Produk</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolektibilitas & NPF Distribution -->
        <div class="row" style="margin-top: 20px;">
            <div class="col-6">
                <div class="card">
                    <div class="card-header">📈 Kolektibilitas</div>
                    <div class="card-body">
                        <div style="text-align: center; padding: 40px; color: #666;">
                            <i class="ti ti-pie-chart" style="font-size: 48px;"></i>
                            <p style="margin-top: 10px;">Distribusi Kolektibilitas</p>
                            <small>Outstanding per Kualitas Kredit</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6">
                <div class="card">
                    <div class="card-header">📊 Distribusi NPF</div>
                    <div class="card-body">
                        <div style="text-align: center; padding: 40px; color: #666;">
                            <i class="ti ti-chart-donut" style="font-size: 48px;"></i>
                            <p style="margin-top: 10px;">Distribusi NPF</p>
                            <small>Non Performing Financing per Segmentasi</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top AO Performance -->
    @if(isset($topAOData) && $topAOData->count() > 0)
    <div class="section">
        <div class="section-title">🏆 Performa Account Officer (Top 5)</div>
        <div class="card">
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama AO</th>
                            <th class="text-center">Jumlah Nasabah</th>
                            <th class="text-right">Total Outstanding</th>
                            <th class="text-right">Total Plafon</th>
                            <th class="text-center">Jumlah NPF</th>
                            <th class="text-center">NPF Ratio</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topAOData as $index => $ao)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $ao['nmao'] }}</td>
                            <td class="text-center">{{ number_format($ao['total_nasabah']) }}</td>
                            <td class="text-right">Rp {{ number_format($ao['total_outstanding'], 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($ao['total_plafon'], 0, ',', '.') }}</td>
                            <td class="text-center">{{ $ao['jumlah_npf'] }}</td>
                            <td class="text-center">{{ $ao['npf_ratio'] }}%</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Sebaran Nasabah per Kecamatan -->
    @if(isset($kecamatanData) && $kecamatanData->count() > 0)
    <div class="section">
        <div class="section-title">🗺️ Sebaran Nasabah per Kecamatan (Top 10)</div>
        <div class="card">
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kecamatan</th>
                            <th class="text-center">Jumlah Nasabah</th>
                            <th class="text-right">Total Outstanding</th>
                            <th class="text-right">Rata-rata Tabungan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($kecamatanData as $kec)
                        <tr>
                            <td>{{ $kec->kecamatan }}</td>
                            <td class="text-center">{{ number_format($kec->jumlah_nasabah) }}</td>
                            <td class="text-right">Rp {{ number_format($kec->total_outstanding, 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($kec->avg_tabungan ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Segmentasi Data -->
    @if(isset($segmentasiData) && $segmentasiData->count() > 0)
    <div class="section">
        <div class="section-title">📊 Tabel Segmentasi Outstanding & Disburse</div>
        <div class="card">
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Segmentasi</th>
                            <th class="text-center">Jumlah Nasabah</th>
                            <th class="text-right">Outstanding</th>
                            <th class="text-right">Disbursement</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($segmentasiData as $segment)
                        <tr class="{{ $segment['is_total'] ? 'font-weight-bold' : '' }}">
                            <td>{{ $segment['segmentasi'] }}</td>
                            <td class="text-center">{{ number_format($segment['jumlah_nasabah']) }}</td>
                            <td class="text-right">Rp {{ number_format($segment['outstanding'], 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($segment['disbursement'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Top Nasabah Lending -->
    @if(isset($nasabahLending) && $nasabahLending->count() > 0)
    <div class="section">
        <div class="section-title">💰 Top 50 Nasabah dengan Total Pinjaman Terbesar</div>
        <div class="card">
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Nasabah</th>
                            <th>AO</th>
                            <th class="text-right">Outstanding</th>
                            <th class="text-right">Plafon</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($nasabahLending as $index => $nasabah)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $nasabah->nama }}</td>
                            <td>{{ $nasabah->nmao }}</td>
                            <td class="text-right">Rp {{ number_format($nasabah->osmdlc, 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($nasabah->plafon, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <div>Laporan ini dihasilkan oleh sistem FinBoard</div>
        <div>BPRS Bina Ummah Sejahtera - {{ date('Y') }}</div>
        <div style="margin-top: 10px; font-size: 9px; color: #999;">
            Dokumen ini bersifat rahasia dan hanya untuk internal BPRS BUS
        </div>
    </div>
</body>
</html>
