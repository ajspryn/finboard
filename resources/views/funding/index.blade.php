@extends('layouts.app')

@section('title', 'Upload Data Funding')

@section('styles')
<style>
    .upload-area {
        border: 2px dashed #d9dee3;
        border-radius: 8px;
        padding: 3rem;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        background: #f8f9fa;
    }
    .upload-area:hover, .upload-area.dragover {
        border-color: #696cff;
        background: #f3f4ff;
    }
    .upload-icon {
        font-size: 3rem;
        color: #696cff;
        margin-bottom: 1rem;
    }
    .stats-card {
        border-left: 3px solid;
    }
</style>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <h4 class="fw-bold mb-4">
                <i class="ti ti-wallet me-2"></i>Upload Data Funding
            </h4>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ti ti-check me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ti ti-alert-triangle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ti ti-alert-triangle me-2"></i>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card stats-card" style="border-left-color: #696cff;">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-database ti-lg"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Total Data</small>
                            <div class="d-flex align-items-center">
                                <h3 class="mb-0 me-2">{{ number_format($totalData) }}</h3>
                                <small class="text-success fw-medium">Rekening</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stats-card" style="border-left-color: #71dd37;">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-clock ti-lg"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Upload Terakhir</small>
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0">
                                    @if($lastUpload)
                                        {{ \Carbon\Carbon::parse($lastUpload)->format('d M Y H:i') }}
                                    @else
                                        Belum ada upload
                                    @endif
                                </h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stats-card" style="border-left-color: #03c3ec;">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="ti ti-coin ti-lg"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Total Saldo</small>
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0">
                                    @php
                                        $totalSaldo = $stats->sum('total_saldo');
                                    @endphp
                                    Rp {{ number_format($totalSaldo / 1000000000, 2) }} M
                                </h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics per Jenis -->
    @if($stats->count() > 0)
    <div class="row mb-4">
        @foreach($stats as $stat)
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text mb-1">{{ $stat->jenis ?? 'Lainnya' }}</p>
                            <div class="d-flex align-items-end mb-2">
                                <h4 class="mb-0 me-2">{{ number_format($stat->jumlah) }}</h4>
                                <small class="text-muted">rekening</small>
                            </div>
                            <small class="text-success fw-medium">
                                Rp {{ number_format($stat->total_saldo / 1000000000, 2) }} Miliar
                            </small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-{{ $stat->jenis === 'TABUNGAN' ? 'info' : ($stat->jenis === 'DEPOSITO' ? 'success' : 'warning') }}">
                                <i class="ti ti-wallet ti-md"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Upload History Table -->
    @if($uploadHistory->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-history me-2"></i>Riwayat Upload Data Funding
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Periode</th>
                                    <th>Jenis</th>
                                    <th class="text-center">Jumlah Rekening</th>
                                    <th class="text-end">Total Saldo</th>
                                    <th class="text-center">Tanggal Upload</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($uploadHistory as $upload)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-2">
                                                <span class="avatar-initial rounded bg-label-primary">
                                                    <i class="ti ti-calendar ti-sm"></i>
                                                </span>
                                            </div>
                                            <strong>{{ str_pad($upload['month'], 2, '0', STR_PAD_LEFT) }}-{{ $upload['year'] }}</strong>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-2">
                                                <span class="avatar-initial rounded bg-label-{{ $upload['jenis'] === 'TABUNGAN' ? 'info' : 'success' }}">
                                                    <i class="ti ti-{{ $upload['jenis'] === 'TABUNGAN' ? 'piggy-bank' : 'clock-dollar' }} ti-sm"></i>
                                                </span>
                                            </div>
                                            <strong>{{ $upload['jenis'] }}</strong>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">{{ number_format($upload['count']) }}</span>
                                    </td>
                                    <td class="text-end">
                                        <strong>Rp {{ number_format($upload['total_saldo'] / 1000000000, 2) }} M</strong>
                                    </td>
                                    <td class="text-center">
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($upload['last_upload'])->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success">
                                            <i class="ti ti-check ti-xs me-1"></i>Berhasil
                                        </span>
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

    <!-- Upload Form -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ti ti-file-upload me-2"></i>Upload File CSV
                    </h5>
                </div>
                <div class="card-body">
                    <form id="uploadForm" action="{{ route('funding.upload') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <!-- Pilih Periode -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <label class="form-label">
                                    <i class="ti ti-calendar me-1"></i>Periode Data Funding
                                </label>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="month" class="form-label">Bulan</label>
                                        <select class="form-select" id="month" name="month" required>
                                            <option value="">Pilih Bulan</option>
                                            <option value="01" {{ old('month') == '01' ? 'selected' : '' }}>Januari</option>
                                            <option value="02" {{ old('month') == '02' ? 'selected' : '' }}>Februari</option>
                                            <option value="03" {{ old('month') == '03' ? 'selected' : '' }}>Maret</option>
                                            <option value="04" {{ old('month') == '04' ? 'selected' : '' }}>April</option>
                                            <option value="05" {{ old('month') == '05' ? 'selected' : '' }}>Mei</option>
                                            <option value="06" {{ old('month') == '06' ? 'selected' : '' }}>Juni</option>
                                            <option value="07" {{ old('month') == '07' ? 'selected' : '' }}>Juli</option>
                                            <option value="08" {{ old('month') == '08' ? 'selected' : '' }}>Agustus</option>
                                            <option value="09" {{ old('month') == '09' ? 'selected' : '' }}>September</option>
                                            <option value="10" {{ old('month') == '10' ? 'selected' : '' }}>Oktober</option>
                                            <option value="11" {{ old('month') == '11' ? 'selected' : '' }}>November</option>
                                            <option value="12" {{ old('month') == '12' ? 'selected' : '' }}>Desember</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="year" class="form-label">Tahun</label>
                                        <select class="form-select" id="year" name="year" required>
                                            <option value="">Pilih Tahun</option>
                                            @for($y = date('Y') - 2; $y <= date('Y') + 2; $y++)
                                            <option value="{{ $y }}" {{ old('year') == $y ? 'selected' : (date('Y') == $y ? 'selected' : '') }}>{{ $y }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>
                                <div class="alert alert-info">
                                    <i class="ti ti-info-circle me-2"></i>
                                    <strong>Pilih periode data yang akan diupload.</strong> Data yang sudah ada untuk periode yang sama akan diganti dengan data baru.
                                </div>
                            </div>
                        </div>

                        <!-- Pilih Jenis Upload -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <label class="form-label">
                                    <i class="ti ti-settings me-1"></i>Jenis Data Funding yang Akan Diupload
                                </label>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check form-check-lg">
                                            <input class="form-check-input" type="checkbox" id="uploadTabungan" name="upload_types[]" value="tabungan" checked>
                                            <label class="form-check-label" for="uploadTabungan">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-sm me-2">
                                                        <span class="avatar-initial rounded bg-label-info">
                                                            <i class="ti ti-piggy-bank"></i>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <strong>Tabungan</strong>
                                                        <br><small class="text-muted">Data rekening tabungan</small>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check form-check-lg">
                                            <input class="form-check-input" type="checkbox" id="uploadDeposito" name="upload_types[]" value="deposito" checked>
                                            <label class="form-check-label" for="uploadDeposito">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-sm me-2">
                                                        <span class="avatar-initial rounded bg-label-success">
                                                            <i class="ti ti-clock-dollar"></i>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <strong>Deposito</strong>
                                                        <br><small class="text-muted">Data deposito & ABP</small>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check form-check-lg">
                                            <input class="form-check-input" type="checkbox" id="uploadLinkage" name="upload_types[]" value="linkage" checked>
                                            <label class="form-check-label" for="uploadLinkage">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-sm me-2">
                                                        <span class="avatar-initial rounded bg-label-warning">
                                                            <i class="ti ti-link"></i>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <strong>Linkage</strong>
                                                        <br><small class="text-muted">Data linkage & dana pihak ketiga</small>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="alert alert-info mt-3">
                                    <i class="ti ti-info-circle me-2"></i>
                                    <strong>Pilih jenis data yang ingin Anda upload.</strong> Anda dapat memilih satu atau lebih jenis data funding sesuai kebutuhan.
                                </div>
                            </div>
                        </div>

                        <!-- Upload Tabungan -->
                        <div class="mb-4" id="tabunganSection" style="display: block;">
                            <label class="form-label">
                                <i class="ti ti-piggy-bank me-1"></i>File CSV Tabungan
                            </label>
                            <div class="upload-area" id="uploadAreaTabungan">
                                <div class="upload-icon">
                                    <i class="ti ti-cloud-upload"></i>
                                </div>
                                <h5>Upload CSV Tabungan</h5>
                                <p class="text-muted mb-3">Drag & drop atau klik untuk memilih file</p>
                                <input type="file" name="csv_tabungan" id="csvTabungan" accept=".csv" class="d-none">
                                <button type="button" class="btn btn-primary" onclick="document.getElementById('csvTabungan').click()">
                                    <i class="ti ti-folder-open me-1"></i>Pilih File Tabungan
                                </button>
                                <p class="text-muted small mt-3 mb-0">Format: CSV | Maksimal 10MB</p>
                            </div>

                            <div id="fileInfoTabungan" class="mt-3" style="display: none;">
                                <div class="alert alert-info d-flex align-items-center">
                                    <i class="ti ti-file-text ti-lg me-3"></i>
                                    <div>
                                        <strong>File Tabungan:</strong> <span id="fileNameTabungan"></span><br>
                                        <small>Ukuran: <span id="fileSizeTabungan"></span></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Upload Deposito -->
                        <div class="mb-4" id="depositoSection" style="display: block;">
                            <label class="form-label">
                                <i class="ti ti-clock-dollar me-1"></i>File CSV Deposito
                            </label>
                            <div class="upload-area" id="uploadAreaDeposito">
                                <div class="upload-icon">
                                    <i class="ti ti-cloud-upload"></i>
                                </div>
                                <h5>Upload CSV Deposito</h5>
                                <p class="text-muted mb-3">Drag & drop atau klik untuk memilih file</p>
                                <input type="file" name="csv_deposito" id="csvDeposito" accept=".csv" class="d-none">
                                <button type="button" class="btn btn-primary" onclick="document.getElementById('csvDeposito').click()">
                                    <i class="ti ti-folder-open me-1"></i>Pilih File Deposito
                                </button>
                                <p class="text-muted small mt-3 mb-0">Format: CSV | Maksimal 10MB</p>
                            </div>

                            <div id="fileInfoDeposito" class="mt-3" style="display: none;">
                                <div class="alert alert-info d-flex align-items-center">
                                    <i class="ti ti-file-text ti-lg me-3"></i>
                                    <div>
                                        <strong>File Deposito:</strong> <span id="fileNameDeposito"></span><br>
                                        <small>Ukuran: <span id="fileSizeDeposito"></span></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Upload Linkage -->
                        <div class="mb-4" id="linkageSection" style="display: block;">
                            <label class="form-label">
                                <i class="ti ti-link me-1"></i>File CSV Linkage
                            </label>
                            <div class="upload-area" id="uploadAreaLinkage">
                                <div class="upload-icon">
                                    <i class="ti ti-cloud-upload"></i>
                                </div>
                                <h5>Upload CSV Linkage</h5>
                                <p class="text-muted mb-3">Drag & drop atau klik untuk memilih file</p>
                                <input type="file" name="csv_linkage" id="csvLinkage" accept=".csv" class="d-none">
                                <button type="button" class="btn btn-primary" onclick="document.getElementById('csvLinkage').click()">
                                    <i class="ti ti-folder-open me-1"></i>Pilih File Linkage
                                </button>
                                <p class="text-muted small mt-3 mb-0">Format: CSV | Maksimal 10MB</p>
                            </div>

                            <div id="fileInfoLinkage" class="mt-3" style="display: none;">
                                <div class="alert alert-info d-flex align-items-center">
                                    <i class="ti ti-file-text ti-lg me-3"></i>
                                    <div>
                                        <strong>File Linkage:</strong> <span id="fileNameLinkage"></span><br>
                                        <small>Ukuran: <span id="fileSizeLinkage"></span></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="text-center" id="submitButton" style="display: none;">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="ti ti-upload me-1"></i><span id="submitButtonText">Upload Data Funding</span>
                            </button>
                        </div>
                    </form>

                    <div class="mt-4">
                        <h6 class="mb-3">
                            <i class="ti ti-info-circle me-2"></i>Informasi Format CSV
                        </h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="ti ti-check text-success me-2"></i>
                                <strong>Pilih jenis data yang ingin diupload</strong> - Anda dapat memilih satu atau lebih jenis data funding sesuai kebutuhan
                            </li>
                            <li class="mb-2">
                                <i class="ti ti-check text-success me-2"></i>
                                File harus berformat CSV dengan delimiter koma (,)
                            </li>
                            <li class="mb-2">
                                <i class="ti ti-check text-success me-2"></i>
                                Baris pertama harus berisi header kolom
                            </li>
                            <li class="mb-3">
                                <i class="ti ti-check text-success me-2"></i>
                                <strong>Header CSV Tabungan:</strong>
                                <br>
                                <code class="small">nocif,notab,kodeprd,sahirrp,fnama,namaqq,stsrec,saldoblok,stsrest,tax,tgltrnakh,avgeom,stspep,kdrisk,noid,hp,tgllhr,nmibu,ketsandi,namapt,kodeloc</code>
                            </li>
                            <li class="mb-3">
                                <i class="ti ti-check text-success me-2"></i>
                                <strong>Header CSV Deposito:</strong>
                                <br>
                                <code class="small">nodep,nocif,nobilyet,nama,nomrp,stsrec,kdprd,jkwaktu,jnsjkwaktu,tglbuka,tgleff,tgljtempo,aro,nisbah,spread,equivrate,komitrate,ststrn,kdwil,kodeaoh,kodeaop,noacbng,tambahnom,noid,alamat,kota,telprmh,hp,stskait,golcustbi,kelurahan,kecamatan,kdpos,kdrisk,tax,bnghtg,nisbahrp,stspep,tgllhr,nmibu,ketsandi,namapt</code>
                            </li>
                            <li class="mb-3">
                                <i class="ti ti-check text-success me-2"></i>
                                <strong>Header CSV Linkage:</strong>
                                <br>
                                <code class="small">nokontrak,nocif,nama,tgleff,tgljt,kelompok,jnsakad,prsnisbah,plafon,os</code>
                            </li>
                            <li class="mb-2">
                                <i class="ti ti-check text-success me-2"></i>
                                Kolom wajib: <code>notab</code> untuk Tabungan, <code>nodep</code> untuk Deposito
                            </li>
                            <li class="mb-2">
                                <i class="ti ti-check text-success me-2"></i>
                                Jenis akan diset otomatis sesuai file yang diupload
                            </li>
                            <li class="mb-2">
                                <i class="ti ti-check text-success me-2"></i>
                                Format tanggal: YYYYMMDD atau YYYY-MM-DD
                            </li>
                            <li class="mb-2">
                                <i class="ti ti-check text-success me-2"></i>
                                Data yang sudah ada akan di-update berdasarkan nomor rekening + period
                            </li>
                            <li class="mb-2">
                                <i class="ti ti-check text-success me-2"></i>
                                Maksimal ukuran file: 10MB per file
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Elements
        const uploadCheckboxes = document.querySelectorAll('input[name="upload_types[]"]');
        const tabunganSection = document.getElementById('tabunganSection');
        const depositoSection = document.getElementById('depositoSection');
        const linkageSection = document.getElementById('linkageSection');
        const submitButton = document.getElementById('submitButton');
        const submitButtonText = document.getElementById('submitButtonText');

        // File input elements
        const csvTabungan = document.getElementById('csvTabungan');
        const csvDeposito = document.getElementById('csvDeposito');
        const csvLinkage = document.getElementById('csvLinkage');

        // File info elements
        const fileInfoTabungan = document.getElementById('fileInfoTabungan');
        const fileInfoDeposito = document.getElementById('fileInfoDeposito');
        const fileInfoLinkage = document.getElementById('fileInfoLinkage');

        // Handle checkbox changes
        uploadCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const type = this.value;
                const isChecked = this.checked;

                if (type === 'tabungan') {
                    tabunganSection.style.display = isChecked ? 'block' : 'none';
                    if (!isChecked) {
                        csvTabungan.value = '';
                        fileInfoTabungan.style.display = 'none';
                    }
                } else if (type === 'deposito') {
                    depositoSection.style.display = isChecked ? 'block' : 'none';
                    if (!isChecked) {
                        csvDeposito.value = '';
                        fileInfoDeposito.style.display = 'none';
                    }
                } else if (type === 'linkage') {
                    linkageSection.style.display = isChecked ? 'block' : 'none';
                    if (!isChecked) {
                        csvLinkage.value = '';
                        fileInfoLinkage.style.display = 'none';
                    }
                }

                updateSubmitButton();
            });
        });

        // Check if required files are selected based on checked options
        function updateSubmitButton() {
            const checkedTypes = Array.from(uploadCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);

            let allRequiredFilesSelected = true;
            let selectedCount = 0;

            checkedTypes.forEach(type => {
                if (type === 'tabungan' && csvTabungan.files.length === 0) {
                    allRequiredFilesSelected = false;
                } else if (type === 'deposito' && csvDeposito.files.length === 0) {
                    allRequiredFilesSelected = false;
                } else if (type === 'linkage' && csvLinkage.files.length === 0) {
                    allRequiredFilesSelected = false;
                }
                selectedCount++;
            });

            if (selectedCount > 0 && allRequiredFilesSelected) {
                submitButton.style.display = 'block';
                const typeNames = checkedTypes.map(type => {
                    switch(type) {
                        case 'tabungan': return 'Tabungan';
                        case 'deposito': return 'Deposito';
                        case 'linkage': return 'Linkage';
                        default: return type;
                    }
                });
                submitButtonText.textContent = `Upload ${typeNames.join(', ')}`;
            } else {
                submitButton.style.display = 'none';
            }
        }

        // File change handlers
        csvTabungan.addEventListener('change', () => {
            handleFile(csvTabungan.files[0], 'Tabungan');
            updateSubmitButton();
        });

        csvDeposito.addEventListener('change', () => {
            handleFile(csvDeposito.files[0], 'Deposito');
            updateSubmitButton();
        });

        csvLinkage.addEventListener('change', () => {
            handleFile(csvLinkage.files[0], 'Linkage');
            updateSubmitButton();
        });

        // Setup drag & drop for all upload areas
        setupDragDrop('uploadAreaTabungan', 'csvTabungan', 'Tabungan');
        setupDragDrop('uploadAreaDeposito', 'csvDeposito', 'Deposito');
        setupDragDrop('uploadAreaLinkage', 'csvLinkage', 'Linkage');

        function setupDragDrop(areaId, inputId, type) {
            const area = document.getElementById(areaId);
            const input = document.getElementById(inputId);

            if (!area) return;

            area.addEventListener('click', (e) => {
                if (e.target !== input) {
                    input.click();
                }
            });

            area.addEventListener('dragover', (e) => {
                e.preventDefault();
                area.classList.add('dragover');
            });

            area.addEventListener('dragleave', () => {
                area.classList.remove('dragover');
            });

            area.addEventListener('drop', (e) => {
                e.preventDefault();
                area.classList.remove('dragover');

                const file = e.dataTransfer.files[0];
                if (file && file.name.endsWith('.csv')) {
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    input.files = dataTransfer.files;
                    handleFile(file, type);
                    updateSubmitButton();
                } else {
                    alert('Hanya file CSV yang diperbolehkan!');
                }
            });
        }

        function handleFile(file, type) {
            if (file) {
                if (type === 'Tabungan') {
                    document.getElementById('fileNameTabungan').textContent = file.name;
                    document.getElementById('fileSizeTabungan').textContent = formatFileSize(file.size);
                    fileInfoTabungan.style.display = 'block';
                } else if (type === 'Deposito') {
                    document.getElementById('fileNameDeposito').textContent = file.name;
                    document.getElementById('fileSizeDeposito').textContent = formatFileSize(file.size);
                    fileInfoDeposito.style.display = 'block';
                } else if (type === 'Linkage') {
                    document.getElementById('fileNameLinkage').textContent = file.name;
                    document.getElementById('fileSizeLinkage').textContent = formatFileSize(file.size);
                    fileInfoLinkage.style.display = 'block';
                }
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        // Initialize
        updateSubmitButton();
    });
</script>
@endsection
