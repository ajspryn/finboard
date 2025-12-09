@extends('layouts.app')

@section('title', 'Edit Financial Highlight')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Header -->
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title mb-0">
                    <i class="ti ti-edit me-2"></i>
                    Edit Financial Highlight
                </h4>
                <small class="text-muted">Periode: {{ $financialHighlight->period_year }}-{{ str_pad($financialHighlight->period_month, 2, '0', STR_PAD_LEFT) }}</small>
            </div>
        </div>

        <!-- Form -->
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('financial-highlights.update', $financialHighlight) }}">
                    @csrf
                    @method('PUT')

                    <!-- Financial Indicators -->
                    <div class="row">
                        <!-- CAR -->
                        <div class="col-md-6 mb-3">
                            <label for="car" class="form-label">CAR (Capital Adequacy Ratio)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100"
                                       class="form-control @error('car') is-invalid @enderror"
                                       id="car" name="car" value="{{ old('car', $financialHighlight->car) }}"
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
                                       id="roa" name="roa" value="{{ old('roa', $financialHighlight->roa) }}"
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
                                       id="roe" name="roe" value="{{ old('roe', $financialHighlight->roe) }}"
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
                                <input type="number" min="0" step="0.01"
                                       class="form-control @error('aset') is-invalid @enderror"
                                       id="aset" name="aset" value="{{ old('aset', $financialHighlight->aset) }}"
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
                                <input type="number" min="0" step="0.01" readonly
                                       class="form-control bg-light"
                                       id="pembiayaan" name="pembiayaan" value="{{ old('pembiayaan', $financialHighlight->getCalculatedField('pembiayaan')) }}"
                                       placeholder="Dihitung otomatis">
                            </div>
                            <small class="text-muted">Total outstanding pembiayaan dari database</small>
                        </div>

                        <!-- Laba Rugi -->
                        <div class="col-md-6 mb-3">
                            <label for="laba_rugi" class="form-label">Laba/Rugi</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" step="0.01"
                                       class="form-control @error('laba_rugi') is-invalid @enderror"
                                       id="laba_rugi" name="laba_rugi" value="{{ old('laba_rugi', $financialHighlight->laba_rugi) }}"
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
                                <input type="number" step="0.01"
                                       class="form-control @error('biaya') is-invalid @enderror"
                                       id="biaya" name="biaya" value="{{ old('biaya', $financialHighlight->biaya) }}"
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
                                <input type="number" min="0" step="0.01" readonly
                                       class="form-control bg-light"
                                       id="dpk" name="dpk" value="{{ old('dpk', $financialHighlight->getCalculatedField('dpk')) }}"
                                       placeholder="Dihitung otomatis">
                            </div>
                            <small class="text-muted">Dana Pihak Ketiga (tabungan + deposito)</small>
                        </div>

                        <!-- FDR (Calculated) -->
                        <div class="col-md-6 mb-3">
                            <label for="fdr" class="form-label">FDR (Financing to Deposit Ratio) <span class="badge bg-info">Otomatis</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" readonly
                                       class="form-control bg-light @error('fdr') is-invalid @enderror"
                                       id="fdr" name="fdr" value="{{ old('fdr', $financialHighlight->fdr ?: $financialHighlight->getCalculatedField('fdr')) }}"
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
                                       id="npf" name="npf" value="{{ old('npf', $financialHighlight->getCalculatedField('npf')) }}"
                                       placeholder="Dihitung otomatis">
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
                                       id="bopo" name="bopo" value="{{ old('bopo', $financialHighlight->bopo) }}"
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
                                       id="cash_ratio" name="cash_ratio" value="{{ old('cash_ratio', $financialHighlight->cash_ratio) }}"
                                       placeholder="Contoh: 15.50">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Rasio kas terhadap kewajiban lancar</small>
                            @error('cash_ratio')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Pendapatan -->
                        <div class="col-md-6 mb-3">
                            <label for="pendapatan" class="form-label">Pendapatan</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" min="0" step="0.01"
                                       class="form-control @error('pendapatan') is-invalid @enderror"
                                       id="pendapatan" name="pendapatan" value="{{ old('pendapatan', $financialHighlight->pendapatan) }}"
                                       placeholder="Masukkan nominal pendapatan">
                            </div>
                            <small class="text-muted">Total pendapatan operasional</small>
                            @error('pendapatan')
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
                            Update Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
