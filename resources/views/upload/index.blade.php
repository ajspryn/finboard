@extends('layouts.app')

@section('title', 'Upload Data Pembiayaan')

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
                <i class="ti ti-upload me-2"></i>Upload Data Pembiayaan
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
        <div class="col-md-6">
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
                                <small class="text-success fw-medium">Kontrak</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
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
    </div>

    <!-- Upload Form -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ti ti-file-upload me-2"></i>Upload File CSV
                    </h5>
                    @if($totalData > 0)
                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmClear()">
                        <i class="ti ti-trash me-1"></i>Hapus Semua Data
                    </button>
                    @endif
                </div>
                <div class="card-body">
                    <form id="uploadForm" action="/upload" method="POST" enctype="multipart/form-data">
                        @csrf

                        <!-- Periode Data -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="month" class="form-label">
                                    <i class="ti ti-calendar me-1"></i>Bulan
                                </label>
                                <select name="month" id="month" class="form-select" required>
                                    <option value="">Pilih Bulan</option>
                                    <option value="01" {{ date('m') == '01' ? 'selected' : '' }}>Januari</option>
                                    <option value="02" {{ date('m') == '02' ? 'selected' : '' }}>Februari</option>
                                    <option value="03" {{ date('m') == '03' ? 'selected' : '' }}>Maret</option>
                                    <option value="04" {{ date('m') == '04' ? 'selected' : '' }}>April</option>
                                    <option value="05" {{ date('m') == '05' ? 'selected' : '' }}>Mei</option>
                                    <option value="06" {{ date('m') == '06' ? 'selected' : '' }}>Juni</option>
                                    <option value="07" {{ date('m') == '07' ? 'selected' : '' }}>Juli</option>
                                    <option value="08" {{ date('m') == '08' ? 'selected' : '' }}>Agustus</option>
                                    <option value="09" {{ date('m') == '09' ? 'selected' : '' }}>September</option>
                                    <option value="10" {{ date('m') == '10' ? 'selected' : '' }}>Oktober</option>
                                    <option value="11" {{ date('m') == '11' ? 'selected' : '' }}>November</option>
                                    <option value="12" {{ date('m') == '12' ? 'selected' : '' }}>Desember</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="year" class="form-label">
                                    <i class="ti ti-calendar-event me-1"></i>Tahun
                                </label>
                                <select name="year" id="year" class="form-select" required>
                                    <option value="">Pilih Tahun</option>
                                    @php
                                        $currentYear = date('Y');
                                        for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                                            $selected = ($y == $currentYear) ? 'selected' : '';
                                            echo "<option value=\"{$y}\" {$selected}>{$y}</option>";
                                        }
                                    @endphp
                                </select>
                            </div>
                        </div>

                        <div class="upload-area" id="uploadArea">
                            <div class="upload-icon">
                                <i class="ti ti-cloud-upload"></i>
                            </div>
                            <h5>Drag & Drop file CSV di sini</h5>
                            <p class="text-muted mb-3">atau klik untuk memilih file</p>
                            <input type="file" name="csv_file" id="csvFile" accept=".csv" class="d-none" required>
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('csvFile').click()">
                                <i class="ti ti-folder-open me-1"></i>Pilih File
                            </button>
                            <p class="text-muted small mt-3 mb-0">Format: CSV | Maksimal 10MB</p>
                        </div>

                        <div id="fileInfo" class="mt-3" style="display: none;">
                            <div class="alert alert-info d-flex align-items-center">
                                <i class="ti ti-file-text ti-lg me-3"></i>
                                <div>
                                    <strong>File terpilih:</strong> <span id="fileName"></span><br>
                                    <small>Ukuran: <span id="fileSize"></span></small>
                                </div>
                                <button type="submit" class="btn btn-primary ms-auto">
                                    <i class="ti ti-upload me-1"></i>Upload Sekarang
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="mt-4">
                        <h6 class="mb-3">
                            <i class="ti ti-info-circle me-2"></i>Informasi Format CSV
                        </h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="ti ti-check text-success me-2"></i>
                                File harus berformat CSV dengan delimiter koma (,)
                            </li>
                            <li class="mb-2">
                                <i class="ti ti-check text-success me-2"></i>
                                Baris pertama harus berisi header kolom
                            </li>
                            <li class="mb-2">
                                <i class="ti ti-check text-success me-2"></i>
                                Kolom wajib: nokontrak, nama
                            </li>
                            <li class="mb-2">
                                <i class="ti ti-check text-success me-2"></i>
                                Data yang sudah ada akan di-update berdasarkan nokontrak
                            </li>
                            <li class="mb-2">
                                <i class="ti ti-check text-success me-2"></i>
                                Maksimal ukuran file: 10MB
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Form untuk Clear Data -->
<form id="clearForm" action="/upload/clear" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@section('scripts')
<script>
    // File input handling
    const uploadArea = document.getElementById('uploadArea');
    const csvFile = document.getElementById('csvFile');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');

    // Click to upload
    uploadArea.addEventListener('click', (e) => {
        if (e.target !== csvFile) {
            csvFile.click();
        }
    });

    // File selected
    csvFile.addEventListener('change', (e) => {
        handleFile(e.target.files[0]);
    });

    // Drag & Drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');

        const file = e.dataTransfer.files[0];
        if (file && file.name.endsWith('.csv')) {
            csvFile.files = e.dataTransfer.files;
            handleFile(file);
        } else {
            alert('Hanya file CSV yang diperbolehkan!');
        }
    });

    function handleFile(file) {
        if (file) {
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            fileInfo.style.display = 'block';
        }
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    function confirmClear() {
        if (confirm('Apakah Anda yakin ingin menghapus SEMUA data pembiayaan? Tindakan ini tidak dapat dibatalkan!')) {
            document.getElementById('clearForm').submit();
        }
    }
</script>
@endsection
