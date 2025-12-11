@extends('layouts.app')

@section('title', 'Upload Data')

@section('content')
    <!-- Flash Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ti ti-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ti ti-alert-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
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
        <div class="col-md-3">
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
        <div class="col-md-3">
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
                                        $totalSaldo = $totalSaldoTabungan + $totalSaldoDeposito + $totalSaldoLinkage + $totalSaldoPembiayaan;
                                    @endphp
                                    Rp {{ number_format($totalSaldo / 1000000000, 2) }} M
                                </h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card" style="border-left-color: #ff5722;">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-building-bank ti-lg"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Total Pembiayaan</small>
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0">
                                    Rp {{ number_format($totalSaldoPembiayaan / 1000000000, 2) }} M
                                </h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload History Table -->
    @if($uploadHistory->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-history me-2"></i>Riwayat Upload Data
                    </h5>
                    <div class="d-flex align-items-center gap-3">
                        <div class="text-muted small">
                            Menampilkan {{ $uploadHistory->firstItem() }}-{{ $uploadHistory->lastItem() }} dari {{ $uploadHistory->total() }} data
                        </div>
                        <div class="d-flex align-items-center">
                            <label for="perPageSelect" class="form-label me-2 mb-0 small">Tampilkan:</label>
                            <select id="perPageSelect" class="form-select form-select-sm" style="width: 80px;" onchange="changePerPage(this.value)">
                                @foreach($perPageOptions as $option)
                                    <option value="{{ $option }}" {{ request('per_page', 10) == $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
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
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Progress</th>
                                    <th>Pesan</th>
                                    <th class="text-center">Tanggal Upload</th>
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
                                            <strong>{{ $upload['period'] }}</strong>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-2">
                                                <span class="avatar-initial rounded bg-label-{{ $upload['jenis'] === 'TABUNGAN' ? 'info' : ($upload['jenis'] === 'DEPOSITO' ? 'success' : ($upload['jenis'] === 'LINKAGE' ? 'warning' : 'primary')) }}">
                                                    <i class="ti ti-{{ $upload['jenis'] === 'TABUNGAN' ? 'piggy-bank' : ($upload['jenis'] === 'DEPOSITO' ? 'clock-dollar' : ($upload['jenis'] === 'LINKAGE' ? 'link' : 'building-bank')) }} ti-sm"></i>
                                                </span>
                                            </div>
                                            <strong>{{ $upload['jenis'] }}</strong>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($upload['type'] === 'completed')
                                            <span class="badge bg-primary">{{ number_format($upload['count']) }}</span>
                                        @else
                                            <span class="badge bg-secondary">{{ number_format($upload['processed_records']) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($upload['total_saldo'])
                                            <strong>Rp {{ number_format($upload['total_saldo'] / 1000000000, 2) }} M</strong>
                                        @else
                                            <small class="text-muted">-</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($upload['status'] === 'processing')
                                            <span class="badge bg-warning">
                                                <i class="ti ti-loader ti-xs me-1"></i>Memproses
                                            </span>
                                        @elseif($upload['status'] === 'completed')
                                            <span class="badge bg-success">
                                                <i class="ti ti-check ti-xs me-1"></i>Selesai
                                            </span>
                                        @elseif($upload['status'] === 'completed_with_errors')
                                            <span class="badge bg-warning">
                                                <i class="ti ti-alert-triangle ti-xs me-1"></i>Selesai dengan Error
                                            </span>
                                        @elseif($upload['status'] === 'failed')
                                            <span class="badge bg-danger">
                                                <i class="ti ti-x ti-xs me-1"></i>Gagal
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($upload['status']) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($upload['type'] === 'processing' && $upload['total_records'] > 0)
                                            <div class="d-flex align-items-center justify-content-center">
                                                <div class="progress flex-grow-1 me-2" style="height: 6px; width: 60px;">
                                                    <div class="progress-bar bg-primary" role="progressbar"
                                                         style="width: {{ $upload['progress'] }}%">
                                                    </div>
                                                </div>
                                                <small class="text-muted">
                                                    {{ $upload['processed_records'] }}/{{ $upload['total_records'] }}
                                                </small>
                                            </div>
                                        @elseif($upload['type'] === 'completed')
                                            <span class="badge bg-success">100%</span>
                                        @else
                                            <small class="text-muted">-</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="max-width: 250px;">
                                            @if($upload['status'] === 'processing')
                                                <small class="text-muted">{{ $upload['message'] }}</small>
                                            @elseif($upload['status'] === 'completed')
                                                <small class="text-success">{{ $upload['message'] }}</small>
                                            @elseif($upload['status'] === 'failed')
                                                <small class="text-danger">{{ $upload['message'] }}</small>
                                            @else
                                                <small class="text-muted">{{ $upload['message'] }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($upload['created_at'])->format('d/m/Y H:i') }}</small>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Enhanced Pagination -->
                    @if($uploadHistory->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted small">
                            Halaman {{ $uploadHistory->currentPage() }} dari {{ $uploadHistory->lastPage() }}
                        </div>
                        <nav aria-label="Upload history pagination">
                            <ul class="pagination pagination-sm mb-0">
                                {{-- Previous Page Link --}}
                                @if ($uploadHistory->onFirstPage())
                                    <li class="page-item disabled">
                                        <span class="page-link">
                                            <i class="ti ti-chevron-left"></i>
                                        </span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $uploadHistory->previousPageUrl() }}" aria-label="Previous">
                                            <i class="ti ti-chevron-left"></i>
                                        </a>
                                    </li>
                                @endif

                                {{-- Pagination Elements --}}
                                @foreach ($uploadHistory->getUrlRange(1, $uploadHistory->lastPage()) as $page => $url)
                                    @if ($page == $uploadHistory->currentPage())
                                        <li class="page-item active">
                                            <span class="page-link">{{ $page }}</span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                        </li>
                                    @endif
                                @endforeach

                                {{-- Next Page Link --}}
                                @if ($uploadHistory->hasMorePages())
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $uploadHistory->nextPageUrl() }}" aria-label="Next">
                                            <i class="ti ti-chevron-right"></i>
                                        </a>
                                    </li>
                                @else
                                    <li class="page-item disabled">
                                        <span class="page-link">
                                            <i class="ti ti-chevron-right"></i>
                                        </span>
                                    </li>
                                @endif
                            </ul>
                        </nav>
                        <div class="text-muted small">
                            Total: {{ $uploadHistory->total() }} data
                        </div>
                    </div>
                    @endif
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
                    @if($totalData > 0)
                    <div>
                        <form id="clearForm" action="{{ route('upload.clear') }}" method="POST" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmClear()">
                                <i class="ti ti-trash me-1"></i>Hapus Semua Data
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
                <div class="card-body">
                    <form id="uploadForm" action="{{ route('upload.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <!-- Pilih Periode -->
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

                        <!-- Pilih Jenis Data -->
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="ti ti-list-check me-1"></i>Jenis Data yang Akan Diupload
                            </label>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="pembiayaanCheck" name="upload_types[]" value="pembiayaan">
                                        <label class="form-check-label" for="pembiayaanCheck">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm me-2">
                                                    <span class="avatar-initial rounded bg-label-primary">
                                                        <i class="ti ti-building-bank ti-sm"></i>
                                                    </span>
                                                </div>
                                                <div>
                                                    <strong>Pembiayaan</strong>
                                                    <br><small class="text-muted">Data pembiayaan & kredit</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="tabunganCheck" name="upload_types[]" value="tabungan">
                                        <label class="form-check-label" for="tabunganCheck">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm me-2">
                                                    <span class="avatar-initial rounded bg-label-info">
                                                        <i class="ti ti-piggy-bank ti-sm"></i>
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
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="depositoCheck" name="upload_types[]" value="deposito">
                                        <label class="form-check-label" for="depositoCheck">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm me-2">
                                                    <span class="avatar-initial rounded bg-label-success">
                                                        <i class="ti ti-clock-dollar ti-sm"></i>
                                                    </span>
                                                </div>
                                                <div>
                                                    <strong>Deposito</strong>
                                                    <br><small class="text-muted">Data deposito & simpanan</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="linkageCheck" name="upload_types[]" value="linkage">
                                        <label class="form-check-label" for="linkageCheck">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm me-2">
                                                    <span class="avatar-initial rounded bg-label-warning">
                                                        <i class="ti ti-link ti-sm"></i>
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
                                <strong>Pilih jenis data yang ingin Anda upload.</strong> Anda dapat memilih satu atau lebih jenis data sesuai kebutuhan. Data pembiayaan akan diproses di background.
                            </div>
                        </div>

                        <!-- Upload Pembiayaan -->
                        <div class="mb-4" id="pembiayaanSection" style="display: none;">
                            <label class="form-label">
                                <i class="ti ti-building-bank me-1"></i>File CSV Pembiayaan
                            </label>
                            <div class="upload-area" id="uploadAreaPembiayaan">
                                <div class="upload-icon">
                                    <i class="ti ti-cloud-upload"></i>
                                </div>
                                <h5>Upload CSV Pembiayaan</h5>
                                <p class="text-muted mb-3">Drag & drop atau klik untuk memilih file</p>
                                <input type="file" name="csv_file" id="csvPembiayaan" accept=".csv" class="d-none">
                                <button type="button" class="btn btn-primary" onclick="document.getElementById('csvPembiayaan').click()">
                                    <i class="ti ti-folder-open me-1"></i>Pilih File Pembiayaan
                                </button>
                                <p class="text-muted small mt-3 mb-0">Format: CSV | Maksimal 10MB | Background Processing</p>
                            </div>

                            <div id="fileInfoPembiayaan" class="mt-3" style="display: none;">
                                <div class="alert alert-info d-flex align-items-center">
                                    <i class="ti ti-file-text ti-lg me-3"></i>
                                    <div>
                                        <strong>File Pembiayaan:</strong> <span id="fileNamePembiayaan"></span><br>
                                        <small>Ukuran: <span id="fileSizePembiayaan"></span></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Upload Tabungan -->
                        <div class="mb-4" id="tabunganSection" style="display: none;">
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
                        <div class="mb-4" id="depositoSection" style="display: none;">
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
                        <div class="mb-4" id="linkageSection" style="display: none;">
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
                                <i class="ti ti-upload me-1"></i><span id="submitButtonText">Upload Data</span>
                            </button>
                        </div>
                    </form>

                    <div class="mt-4">
                        <h6 class="mb-3">
                            <i class="ti ti-info-circle me-2"></i>Informasi Format CSV
                        </h6>
                        <div class="accordion" id="formatAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pembiayaanFormat">
                                        Format CSV Pembiayaan
                                    </button>
                                </h2>
                                <div id="pembiayaanFormat" class="accordion-collapse collapse" data-bs-parent="#formatAccordion">
                                    <div class="accordion-body">
                                        <p>File harus berformat CSV dengan delimiter koma (,). Baris pertama harus berisi header kolom.</p>
                                        <strong>Header CSV:</strong>
                                        <br>
                                        <code class="small">nocif,notab,kodeprd,sahirrp,fnama,namaqq,stsrec,saldoblok,stsrest,tax,tgltrnakh,avgeom,stspep,kdrisk,noid,hp,tgllhr,nmibu,ketsandi,namapt,kodeloc</code>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tabunganFormat">
                                        Format CSV Tabungan
                                    </button>
                                </h2>
                                <div id="tabunganFormat" class="accordion-collapse collapse" data-bs-parent="#formatAccordion">
                                    <div class="accordion-body">
                                        <p>File harus berformat CSV dengan delimiter koma (,). Baris pertama harus berisi header kolom.</p>
                                        <strong>Header CSV:</strong>
                                        <br>
                                        <code class="small">nocif,notab,kodeprd,sahirrp,fnama,namaqq,stsrec,saldoblok,stsrest,tax,tgltrnakh,avgeom,stspep,kdrisk,noid,hp,tgllhr,nmibu,ketsandi,namapt,kodeloc</code>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#depositoFormat">
                                        Format CSV Deposito
                                    </button>
                                </h2>
                                <div id="depositoFormat" class="accordion-collapse collapse" data-bs-parent="#formatAccordion">
                                    <div class="accordion-body">
                                        <p>File harus berformat CSV dengan delimiter koma (,). Baris pertama harus berisi header kolom.</p>
                                        <strong>Header CSV:</strong>
                                        <br>
                                        <code class="small">nodep,nocif,nobilyet,nama,nomrp,stsrec,kdprd,jkwaktu,jnsjkwaktu,tglbuka,tgleff,tgljtempo,aro,nisbah,spread,equivrate,komitrate,ststrn,kdwil,kodeaoh,kodeaop,noacbng,tambahnom,noid,alamat,kota,telprmh,hp,stskait,golcustbi,kelurahan,kecamatan,kdpos,kdrisk,tax,bnghtg,nisbahrp,stspep,tgllhr,nmibu,ketsandi,namapt</code>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#linkageFormat">
                                        Format CSV Linkage
                                    </button>
                                </h2>
                                <div id="linkageFormat" class="accordion-collapse collapse" data-bs-parent="#formatAccordion">
                                    <div class="accordion-body">
                                        <p>File harus berformat CSV dengan delimiter koma (,). Baris pertama harus berisi header kolom.</p>
                                        <strong>Header CSV:</strong>
                                        <br>
                                        <code class="small">nokontrak,nocif,nama,tgleff,tgljt,kelompok,jnsakad,prsnisbah,plafon,os</code>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <ul class="list-unstyled mt-3">
                            <li class="mb-2">
                                <i class="ti ti-check text-success me-2"></i>
                                Kolom wajib: <code>notab</code> untuk Tabungan, <code>nodep</code> untuk Deposito, <code>nokontrak</code> untuk Linkage
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
                            <li class="mb-2">
                                <i class="ti ti-check text-success me-2"></i>
                                <strong>Data pembiayaan diproses di background</strong> untuk performa optimal
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
<style>
.upload-area {
    border: 2px dashed #d9dee3;
    border-radius: 8px;
    padding: 40px 20px;
    text-align: center;
    transition: all 0.3s ease;
    background-color: #f8f9fa;
    cursor: pointer;
}

.upload-area:hover,
.upload-area.dragover {
    border-color: #696cff;
    background-color: #f0f0ff;
}

.upload-icon {
    font-size: 3rem;
    color: #696cff;
    margin-bottom: 15px;
}

.upload-area h5 {
    color: #566a7f;
    margin-bottom: 10px;
}

.stats-card {
    border-left: 4px solid;
    transition: transform 0.2s ease;
}

.stats-card:hover {
    transform: translateY(-2px);
}

.accordion-button:not(.collapsed) {
    background-color: #f8f9fa;
    color: #566a7f;
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const uploadCheckboxes = document.querySelectorAll('input[name="upload_types[]"]');
    const pembiayaanSection = document.getElementById('pembiayaanSection');
    const tabunganSection = document.getElementById('tabunganSection');
    const depositoSection = document.getElementById('depositoSection');
    const linkageSection = document.getElementById('linkageSection');
    const submitButton = document.getElementById('submitButton');
    const submitButtonText = document.getElementById('submitButtonText');

    // File input elements
    const csvPembiayaan = document.getElementById('csvPembiayaan');
    const csvTabungan = document.getElementById('csvTabungan');
    const csvDeposito = document.getElementById('csvDeposito');
    const csvLinkage = document.getElementById('csvLinkage');

    // File info elements
    const fileInfoPembiayaan = document.getElementById('fileInfoPembiayaan');
    const fileInfoTabungan = document.getElementById('fileInfoTabungan');
    const fileInfoDeposito = document.getElementById('fileInfoDeposito');
    const fileInfoLinkage = document.getElementById('fileInfoLinkage');

    // Handle checkbox changes
    uploadCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const type = this.value;
            const isChecked = this.checked;

            if (type === 'pembiayaan') {
                pembiayaanSection.style.display = isChecked ? 'block' : 'none';
                if (!isChecked) {
                    csvPembiayaan.value = '';
                    fileInfoPembiayaan.style.display = 'none';
                }
            } else if (type === 'tabungan') {
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
            if (type === 'pembiayaan' && csvPembiayaan.files.length === 0) {
                allRequiredFilesSelected = false;
            } else if (type === 'tabungan' && csvTabungan.files.length === 0) {
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
                    case 'pembiayaan': return 'Pembiayaan';
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
    csvPembiayaan.addEventListener('change', () => {
        handleFile(csvPembiayaan.files[0], 'Pembiayaan');
        updateSubmitButton();
    });

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
    setupDragDrop('uploadAreaPembiayaan', 'csvPembiayaan', 'Pembiayaan');
    setupDragDrop('uploadAreaTabungan', 'csvTabungan', 'Tabungan');
    setupDragDrop('uploadAreaDeposito', 'csvDeposito', 'Deposito');
    setupDragDrop('uploadAreaLinkage', 'csvLinkage', 'Linkage');

    function setupDragDrop(areaId, inputId, type) {
        const area = document.getElementById(areaId);
        const input = document.getElementById(inputId);

        if (!area) return;

        area.addEventListener('click', () => {
            input.click();
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
            if (type === 'Pembiayaan') {
                document.getElementById('fileNamePembiayaan').textContent = file.name;
                document.getElementById('fileSizePembiayaan').textContent = formatFileSize(file.size);
                fileInfoPembiayaan.style.display = 'block';
            } else if (type === 'Tabungan') {
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

function changePerPage(perPage) {
    const url = new URL(window.location);
    url.searchParams.set('per_page', perPage);
    url.searchParams.set('page', '1'); // Reset to first page when changing per_page
    window.location.href = url.toString();
}

function confirmClear() {
    if (confirm('Apakah Anda yakin ingin menghapus SEMUA data (Pembiayaan, Tabungan, Deposito, dan Linkage)? Tindakan ini tidak dapat dibatalkan!')) {
        document.getElementById('clearForm').submit();
    }
}
</script>
@endsection
