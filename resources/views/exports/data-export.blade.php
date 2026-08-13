@extends('layouts.app')

@section('title', 'Export Data')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">Export Data Dinamis</h5>
                    <p class="text-muted mb-0">Pilih jenis data, periode, dan parameter filter lalu unduh CSV.</p>
                </div>
                <span class="badge bg-label-info">CSV</span>
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('export.data.download') }}" class="row g-3">
                    @csrf

                    <div class="col-md-4">
                        <label for="data_type" class="form-label">Jenis Data</label>
                        <select id="data_type" name="data_type" class="form-select" required>
                            <option value="tabungan" {{ old('data_type') === 'tabungan' ? 'selected' : '' }}>Tabungan</option>
                            <option value="funding" {{ old('data_type') === 'funding' ? 'selected' : '' }}>Funding (Deposito + Linkage)</option>
                            <option value="lending" {{ old('data_type') === 'lending' ? 'selected' : '' }}>Lending (Pembiayaan)</option>
                            <option value="all" {{ old('data_type') === 'all' ? 'selected' : '' }}>Gabungan Semua Data</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="period_type" class="form-label">Periode</label>
                        <select id="period_type" name="period_type" class="form-select" required>
                            <option value="this_month" {{ old('period_type') === 'this_month' ? 'selected' : '' }}>Bulan Ini ({{ $currentPeriod }})</option>
                            <option value="last_month" {{ old('period_type') === 'last_month' ? 'selected' : '' }}>Bulan Lalu ({{ $lastPeriod }})</option>
                            <option value="custom_range" {{ old('period_type') === 'custom_range' ? 'selected' : '' }}>Rentang Bulan</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="start_period" class="form-label">Dari Bulan</label>
                        <input type="month" id="start_period" name="start_period" class="form-control" value="{{ old('start_period', $defaultStartPeriod) }}">
                    </div>

                    <div class="col-md-2">
                        <label for="end_period" class="form-label">Sampai Bulan</label>
                        <input type="month" id="end_period" name="end_period" class="form-control" value="{{ old('end_period', $defaultEndPeriod) }}">
                    </div>

                    <div class="col-md-4">
                        <label for="filter_field" class="form-label">Filter Berdasarkan</label>
                        <select id="filter_field" name="filter_field" class="form-select">
                            <option value="">Tanpa Filter Tambahan</option>
                            <option value="cif" {{ old('filter_field') === 'cif' ? 'selected' : '' }}>CIF</option>
                            <option value="nik" {{ old('filter_field') === 'nik' ? 'selected' : '' }}>NIK / No ID</option>
                            <option value="nama" {{ old('filter_field') === 'nama' ? 'selected' : '' }}>Nama Nasabah</option>
                            <option value="rekening" {{ old('filter_field') === 'rekening' ? 'selected' : '' }}>Nomor Rekening / Kontrak</option>
                            <option value="hp" {{ old('filter_field') === 'hp' ? 'selected' : '' }}>No HP</option>
                            <option value="produk" {{ old('filter_field') === 'produk' ? 'selected' : '' }}>Produk</option>
                            <option value="ao" {{ old('filter_field') === 'ao' ? 'selected' : '' }}>AO</option>
                            <option value="kecamatan" {{ old('filter_field') === 'kecamatan' ? 'selected' : '' }}>Kecamatan</option>
                            <option value="keyword" {{ old('filter_field') === 'keyword' ? 'selected' : '' }}>Keyword Umum</option>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label for="filter_value" class="form-label">Nilai Filter</label>
                        <input type="text" id="filter_value" name="filter_value" class="form-control" maxlength="100" value="{{ old('filter_value') }}" placeholder="Contoh: 123456 (CIF), 3276... (NIK), Nama, Produk, AO, dll">
                    </div>

                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-download me-1"></i>
                            Export CSV
                        </button>
                    </div>
                </form>

                <hr class="my-4">

                <div class="alert alert-info mb-0">
                    <strong>Catatan:</strong>
                    Data export akan otomatis unik berdasarkan CIF (1 CIF = 1 baris).<br>
                    Baris tanpa CIF tidak akan ikut diexport agar tidak terjadi duplikasi.
                    <br>
                    Filter NIK hanya berlaku untuk data yang memiliki kolom no identitas (Tabungan dan Deposito).
                    Bila filter tidak relevan untuk jenis data tertentu, data pada jenis tersebut tidak akan ikut diekspor.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    (function () {
        const periodType = document.getElementById('period_type');
        const startPeriod = document.getElementById('start_period');
        const endPeriod = document.getElementById('end_period');

        function toggleRangeInputs() {
            const isCustom = periodType.value === 'custom_range';
            startPeriod.disabled = !isCustom;
            endPeriod.disabled = !isCustom;

            if (!isCustom) {
                startPeriod.classList.add('bg-light');
                endPeriod.classList.add('bg-light');
            } else {
                startPeriod.classList.remove('bg-light');
                endPeriod.classList.remove('bg-light');
            }
        }

        periodType.addEventListener('change', toggleRangeInputs);
        toggleRangeInputs();
    })();
</script>
@endsection
