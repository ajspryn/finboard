@extends('layouts.app')

@section('title', 'Tambah Financial Highlight')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Header -->
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title mb-0">
                    <i class="ti ti-plus me-2"></i>
                    Tambah Financial Highlight
                </h4>
                <small class="text-muted">Input data indikator keuangan untuk periode tertentu</small>
            </div>
        </div>

        <!-- Form -->
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('financial-highlights.store') }}">
                    @csrf

                    <!-- Period Selection -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="period_year" class="form-label">Tahun</label>
                            <select class="form-control @error('period_year') is-invalid @enderror" id="period_year" name="period_year" required>
                                <option value="">Pilih Tahun</option>
                                @for($year = date('Y'); $year >= 2020; $year--)
                                    <option value="{{ $year }}" {{ old('period_year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endfor
                            </select>
                            @error('period_year')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="period_month" class="form-label">Bulan</label>
                            <select class="form-control @error('period_month') is-invalid @enderror" id="period_month" name="period_month" required>
                                <option value="">Pilih Bulan</option>
                                @for($month = 1; $month <= 12; $month++)
                                    <option value="{{ $month }}" {{ old('period_month') == $month ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $month, 1)) }}
                                    </option>
                                @endfor
                            </select>
                            @error('period_month')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Financial Indicators -->
                    <div class="row">
                        <!-- CAR -->
                        <div class="col-md-6 mb-3">
                            <label for="car" class="form-label">CAR (Capital Adequacy Ratio)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100"
                                       class="form-control @error('car') is-invalid @enderror"
                                       id="car" name="car" value="{{ old('car') }}"
                                       placeholder="Contoh: 15.50">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Rasio kecukupan modal</small>
                            @error('car')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- ROA -->
                        <div class="col-md-6 mb-3">
                            <label for="roa" class="form-label">ROA (Return on Assets)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="-100" max="100"
                                       class="form-control @error('roa') is-invalid @enderror"
                                       id="roa" name="roa" value="{{ old('roa') }}"
                                       placeholder="Contoh: 2.30">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Return on Assets</small>
                            @error('roa')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- ROE -->
                        <div class="col-md-6 mb-3">
                            <label for="roe" class="form-label">ROE (Return on Equity)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="-100" max="100"
                                       class="form-control @error('roe') is-invalid @enderror"
                                       id="roe" name="roe" value="{{ old('roe') }}"
                                       placeholder="Contoh: 18.75">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Return on Equity</small>
                            @error('roe')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Aset (Manual Input) -->
                        <div class="col-md-6 mb-3">
                            <label for="aset" class="form-label">Total Aset <span class="badge bg-secondary">Manual</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" min="0"
                                       class="form-control @error('aset') is-invalid @enderror"
                                       id="aset" name="aset" value="{{ old('aset') }}"
                                       placeholder="Masukkan total aset">
                            </div>
                            <small class="text-muted">Total aset perusahaan (input manual)</small>
                            @error('aset')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Pembiayaan (Calculated) -->
                        <div class="col-md-6 mb-3">
                            <label for="pembiayaan" class="form-label">Total Pembiayaan <span class="badge bg-info">Otomatis</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" min="0" readonly
                                       class="form-control bg-light"
                                       id="pembiayaan" name="pembiayaan" value="{{ old('pembiayaan') }}"
                                       placeholder="Akan dihitung otomatis">
                            </div>
                            <small class="text-muted">Total outstanding pembiayaan dari database</small>
                        </div>

                        <!-- Laba Rugi -->
                        <div class="col-md-6 mb-3">
                            <label for="laba_rugi" class="form-label">Laba/Rugi</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number"
                                       class="form-control @error('laba_rugi') is-invalid @enderror"
                                       id="laba_rugi" name="laba_rugi" value="{{ old('laba_rugi') }}"
                                       placeholder="Contoh: 25000000">
                            </div>
                            <small class="text-muted">Laba bersih (positif) atau rugi (negatif)</small>
                            @error('laba_rugi')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Biaya -->
                        <div class="col-md-6 mb-3">
                            <label for="biaya" class="form-label">Biaya</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number"
                                       class="form-control @error('biaya') is-invalid @enderror"
                                       id="biaya" name="biaya" value="{{ old('biaya') }}"
                                       placeholder="Masukkan total biaya">
                            </div>
                            <small class="text-muted">Total biaya operasional</small>
                            @error('biaya')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- DPK (Calculated) -->
                        <div class="col-md-6 mb-3">
                            <label for="dpk" class="form-label">DPK (Dana Pihak Ketiga) <span class="badge bg-info">Otomatis</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" min="0" readonly
                                       class="form-control bg-light"
                                       id="dpk" name="dpk" value="{{ old('dpk') }}"
                                       placeholder="Akan dihitung otomatis">
                            </div>
                            <small class="text-muted">Dana Pihak Ketiga (tabungan + deposito)</small>
                        </div>

                        <!-- FDR (Calculated) -->
                        <div class="col-md-6 mb-3">
                            <label for="fdr" class="form-label">FDR (Financing to Deposit Ratio) <span class="badge bg-info">Otomatis</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="500" readonly
                                       class="form-control bg-light @error('fdr') is-invalid @enderror"
                                       id="fdr" name="fdr" value="{{ old('fdr') }}"
                                       placeholder="Akan dihitung otomatis">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Rasio pembiayaan terhadap deposito (dihitung otomatis)</small>
                            @error('fdr')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- NPF (Calculated) -->
                        <div class="col-md-6 mb-3">
                            <label for="npf" class="form-label">NPF (Non-Performing Financing) <span class="badge bg-info">Otomatis</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" readonly
                                       class="form-control bg-light"
                                       id="npf" name="npf" value="{{ old('npf') }}"
                                       placeholder="Akan dihitung otomatis">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Non-Performing Financing ratio (dihitung dari database)</small>
                        </div>

                        <!-- BOPO -->
                        <div class="col-md-6 mb-3">
                            <label for="bopo" class="form-label">BOPO (Biaya Operasional vs Pendapatan Operasional)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="200"
                                       class="form-control @error('bopo') is-invalid @enderror"
                                       id="bopo" name="bopo" value="{{ old('bopo') }}"
                                       placeholder="Contoh: 78.90">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Efficiency ratio</small>
                            @error('bopo')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Cash Ratio -->
                        <div class="col-md-6 mb-3">
                            <label for="cash_ratio" class="form-label">Cash Ratio</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="200"
                                       class="form-control @error('cash_ratio') is-invalid @enderror"
                                       id="cash_ratio" name="cash_ratio" value="{{ old('cash_ratio') }}"
                                       placeholder="Contoh: 15.50">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Rasio kas terhadap kewajiban lancar</small>
                            @error('cash_ratio')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- KPMM -->
                        <div class="col-md-6 mb-3">
                            <label for="kpmm" class="form-label">KPMM</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" min="0"
                                       class="form-control @error('kpmm') is-invalid @enderror"
                                       id="kpmm" name="kpmm" value="{{ old('kpmm') }}"
                                       placeholder="Masukkan nominal KPMM">
                            </div>
                            <small class="text-muted">Kewajiban Penyediaan Modal Minimum (nominal)</small>
                            @error('kpmm')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('financial-highlights.index') }}" class="btn btn-secondary">
                            <i class="ti ti-arrow-left me-1"></i>
                            Kembali
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i>
                            Simpan Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const periodYear = document.getElementById('period_year');
    const periodMonth = document.getElementById('period_month');

    function calculateDerivedValues() {
        const year = periodYear.value;
        const month = periodMonth.value;

        if (year && month) {
            // Calculate derived values via AJAX
            fetch(`/api/financial-highlights/calculate?year=${year}&month=${month}`)
                .then(response => response.json())
                .then(data => {
                    if (data.dpk !== undefined) {
                        document.getElementById('dpk').value = data.dpk;
                    }
                    if (data.pembiayaan !== undefined) {
                        document.getElementById('pembiayaan').value = data.pembiayaan;
                    }
                    if (data.npf !== undefined) {
                        document.getElementById('npf').value = data.npf;
                    }
                    if (data.fdr !== undefined) {
                        document.getElementById('fdr').value = data.fdr;
                    }
                })
                .catch(error => {
                    console.error('Error calculating derived values:', error);
                });
        }
    }

    // Calculate when period changes
    periodYear.addEventListener('change', calculateDerivedValues);
    periodMonth.addEventListener('change', calculateDerivedValues);
});
</script>
@endsection
