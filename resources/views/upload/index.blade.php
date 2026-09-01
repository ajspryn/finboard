@extends('layouts.app')

@section('title', 'Upload Data')

@section('content')
    {{-- ============================================================
         UPLOAD PROGRESS OVERLAY (fixed position, shown via JS)
         ============================================================ --}}
    <div id="uploadProgressOverlay" class="upload-overlay" style="display:none;" aria-hidden="true">
        <div class="upload-overlay-box">
            <div class="upload-overlay-icon">
                <div class="upload-spinner">
                    <i class="ti ti-cloud-upload"></i>
                </div>
            </div>
            <h5 class="mt-3 mb-1" id="overlayTitle">Mengirim File ke Server...</h5>
            <p class="text-muted small mb-3" id="overlaySubtitle">Harap tunggu, jangan tutup halaman ini</p>

            {{-- Transfer Progress (fase 1) --}}
            <div id="transferPhase">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-muted">Transfer File</small>
                    <small class="fw-bold" id="transferPercent">0%</small>
                </div>
                <div class="progress mb-2" style="height: 10px; border-radius: 6px;">
                    <div id="transferBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                         role="progressbar" style="width:0%"></div>
                </div>
                <div class="d-flex justify-content-between">
                    <small class="text-muted" id="transferLoaded">0 KB</small>
                    <small class="text-muted" id="transferTotal">— KB</small>
                </div>
            </div>

            {{-- Processing Info (fase 2) --}}
            <div id="processingPhase" style="display:none;">
                <div class="alert alert-success py-2 px-3 mb-0">
                    <i class="ti ti-check-circle me-2"></i>
                    <strong>File berhasil diterima server!</strong><br>
                    <small>Data sedang diproses di background. Halaman akan muat ulang otomatis...</small>
                </div>
            </div>

            {{-- File list --}}
            <div id="overlayFileList" class="mt-3 text-start"></div>
        </div>
    </div>
    {{-- Flash Messages --}}
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

    {{-- Statistics --}}
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

    <div id="uploadHistoryContainer"
         data-refresh-url="{{ route('upload.history') }}"
         data-has-processing="{{ $hasProcessingUploads ? '1' : '0' }}">
        @include('upload.partials.history', [
            'uploadHistory' => $uploadHistory,
            'perPageOptions' => $perPageOptions,
            'hasProcessingUploads' => $hasProcessingUploads,
        ])
    </div>

    {{-- Upload Form --}}
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

                        {{-- Pilih Periode --}}
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

                        {{-- Pilih Jenis Data --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="ti ti-list-check me-1"></i>Jenis Data yang Akan Diupload
                            </label>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="upload-type-card" for="pembiayaanCheck">
                                        <input class="upload-type-input" type="checkbox" id="pembiayaanCheck" name="upload_types[]" value="pembiayaan">
                                        <div class="upload-type-inner">
                                            <span class="avatar-initial rounded bg-label-primary mb-2" style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                                                <i class="ti ti-building-bank" style="font-size:1.3rem;"></i>
                                            </span>
                                            <strong class="d-block mt-1">Pembiayaan</strong>
                                            <small class="text-muted">Data pembiayaan &amp; kredit</small>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-md-3">
                                    <label class="upload-type-card" for="tabunganCheck">
                                        <input class="upload-type-input" type="checkbox" id="tabunganCheck" name="upload_types[]" value="tabungan">
                                        <div class="upload-type-inner">
                                            <span class="avatar-initial rounded bg-label-info mb-2" style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                                                <i class="ti ti-piggy-bank" style="font-size:1.3rem;"></i>
                                            </span>
                                            <strong class="d-block mt-1">Tabungan</strong>
                                            <small class="text-muted">Data rekening tabungan</small>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-md-3">
                                    <label class="upload-type-card" for="depositoCheck">
                                        <input class="upload-type-input" type="checkbox" id="depositoCheck" name="upload_types[]" value="deposito">
                                        <div class="upload-type-inner">
                                            <span class="avatar-initial rounded bg-label-success mb-2" style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                                                <i class="ti ti-clock-dollar" style="font-size:1.3rem;"></i>
                                            </span>
                                            <strong class="d-block mt-1">Deposito</strong>
                                            <small class="text-muted">Data deposito &amp; simpanan</small>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-md-3">
                                    <label class="upload-type-card" for="linkageCheck">
                                        <input class="upload-type-input" type="checkbox" id="linkageCheck" name="upload_types[]" value="linkage">
                                        <div class="upload-type-inner">
                                            <span class="avatar-initial rounded bg-label-warning mb-2" style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                                                <i class="ti ti-link" style="font-size:1.3rem;"></i>
                                            </span>
                                            <strong class="d-block mt-1">Linkage</strong>
                                            <small class="text-muted">Data linkage &amp; DPK</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="alert alert-info mt-3 mb-0">
                                <i class="ti ti-info-circle me-2"></i>
                                <strong>Pilih jenis data yang ingin Anda upload.</strong> Anda dapat memilih satu atau lebih jenis data sesuai kebutuhan. Data akan diproses di background.
                            </div>
                        </div>

                        {{-- Upload Pembiayaan --}}
                        <div class="mb-4" id="pembiayaanSection" style="display: none;">
                            <label class="form-label fw-semibold">
                                <i class="ti ti-building-bank me-1"></i>File CSV Pembiayaan
                            </label>
                            <div class="upload-area" id="uploadAreaPembiayaan" data-type="Pembiayaan">
                                <div class="upload-area-content">
                                    <div class="upload-icon">
                                        <i class="ti ti-cloud-upload"></i>
                                    </div>
                                    <h5>Upload CSV Pembiayaan</h5>
                                    <p class="text-muted mb-3">Drag &amp; drop atau klik untuk memilih file</p>
                                    <input type="file" name="csv_file" id="csvPembiayaan" accept=".csv" class="d-none">
                                    <button type="button" class="btn btn-primary" onclick="document.getElementById('csvPembiayaan').click()">
                                        <i class="ti ti-folder-open me-1"></i>Pilih File
                                    </button>
                                    <p class="text-muted small mt-3 mb-0">Format: CSV &nbsp;|&nbsp; Maks. 10MB &nbsp;|&nbsp; Background Processing</p>
                                </div>
                                <div class="upload-area-selected" id="fileSelectedPembiayaan" style="display:none;">
                                    <div class="file-preview-card">
                                        <span class="file-icon"><i class="ti ti-file-text"></i></span>
                                        <div class="file-details">
                                            <span class="file-name" id="fileNamePembiayaan"></span>
                                            <span class="file-size" id="fileSizePembiayaan"></span>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile('Pembiayaan')">
                                            <i class="ti ti-x"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Upload Tabungan --}}
                        <div class="mb-4" id="tabunganSection" style="display: none;">
                            <label class="form-label fw-semibold">
                                <i class="ti ti-piggy-bank me-1"></i>File CSV Tabungan
                            </label>
                            <div class="upload-area" id="uploadAreaTabungan" data-type="Tabungan">
                                <div class="upload-area-content">
                                    <div class="upload-icon">
                                        <i class="ti ti-cloud-upload"></i>
                                    </div>
                                    <h5>Upload CSV Tabungan</h5>
                                    <p class="text-muted mb-3">Drag &amp; drop atau klik untuk memilih file</p>
                                    <input type="file" name="csv_tabungan" id="csvTabungan" accept=".csv" class="d-none">
                                    <button type="button" class="btn btn-info" onclick="document.getElementById('csvTabungan').click()">
                                        <i class="ti ti-folder-open me-1"></i>Pilih File
                                    </button>
                                    <p class="text-muted small mt-3 mb-0">Format: CSV &nbsp;|&nbsp; Maks. 10MB</p>
                                </div>
                                <div class="upload-area-selected" id="fileSelectedTabungan" style="display:none;">
                                    <div class="file-preview-card">
                                        <span class="file-icon text-info"><i class="ti ti-file-text"></i></span>
                                        <div class="file-details">
                                            <span class="file-name" id="fileNameTabungan"></span>
                                            <span class="file-size" id="fileSizeTabungan"></span>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile('Tabungan')">
                                            <i class="ti ti-x"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Upload Deposito --}}
                        <div class="mb-4" id="depositoSection" style="display: none;">
                            <label class="form-label fw-semibold">
                                <i class="ti ti-clock-dollar me-1"></i>File CSV Deposito
                            </label>
                            <div class="upload-area" id="uploadAreaDeposito" data-type="Deposito">
                                <div class="upload-area-content">
                                    <div class="upload-icon">
                                        <i class="ti ti-cloud-upload"></i>
                                    </div>
                                    <h5>Upload CSV Deposito</h5>
                                    <p class="text-muted mb-3">Drag &amp; drop atau klik untuk memilih file</p>
                                    <input type="file" name="csv_deposito" id="csvDeposito" accept=".csv" class="d-none">
                                    <button type="button" class="btn btn-success" onclick="document.getElementById('csvDeposito').click()">
                                        <i class="ti ti-folder-open me-1"></i>Pilih File
                                    </button>
                                    <p class="text-muted small mt-3 mb-0">Format: CSV &nbsp;|&nbsp; Maks. 10MB</p>
                                </div>
                                <div class="upload-area-selected" id="fileSelectedDeposito" style="display:none;">
                                    <div class="file-preview-card">
                                        <span class="file-icon text-success"><i class="ti ti-file-text"></i></span>
                                        <div class="file-details">
                                            <span class="file-name" id="fileNameDeposito"></span>
                                            <span class="file-size" id="fileSizeDeposito"></span>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile('Deposito')">
                                            <i class="ti ti-x"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Upload Linkage --}}
                        <div class="mb-4" id="linkageSection" style="display: none;">
                            <label class="form-label fw-semibold">
                                <i class="ti ti-link me-1"></i>File CSV Linkage
                            </label>
                            <div class="upload-area" id="uploadAreaLinkage" data-type="Linkage">
                                <div class="upload-area-content">
                                    <div class="upload-icon">
                                        <i class="ti ti-cloud-upload"></i>
                                    </div>
                                    <h5>Upload CSV Linkage</h5>
                                    <p class="text-muted mb-3">Drag &amp; drop atau klik untuk memilih file</p>
                                    <input type="file" name="csv_linkage" id="csvLinkage" accept=".csv" class="d-none">
                                    <button type="button" class="btn btn-warning" onclick="document.getElementById('csvLinkage').click()">
                                        <i class="ti ti-folder-open me-1"></i>Pilih File
                                    </button>
                                    <p class="text-muted small mt-3 mb-0">Format: CSV &nbsp;|&nbsp; Maks. 10MB</p>
                                </div>
                                <div class="upload-area-selected" id="fileSelectedLinkage" style="display:none;">
                                    <div class="file-preview-card">
                                        <span class="file-icon text-warning"><i class="ti ti-file-text"></i></span>
                                        <div class="file-details">
                                            <span class="file-name" id="fileNameLinkage"></span>
                                            <span class="file-size" id="fileSizeLinkage"></span>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile('Linkage')">
                                            <i class="ti ti-x"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Submit Button --}}
                        <div class="text-center" id="submitButton" style="display: none;">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="ti ti-upload me-2"></i><span id="submitButtonText">Upload Data</span>
                            </button>
                        </div>
                    </form>

                    <div class="mt-4">
                        <h6 class="mb-3">
                            <i class="ti ti-info-circle me-2"></i>Informasi Format CSV
                        </h6>

                        {{-- Download Template Buttons --}}
                        <div class="alert alert-light border mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="ti ti-download me-2 text-primary"></i>
                                <strong>Download Template CSV</strong>
                                <span class="text-muted ms-2 small">— unduh contoh format file sebelum upload</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('upload.template', 'pembiayaan') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ti ti-file-download me-1"></i>Template Pembiayaan
                                </a>
                                <a href="{{ route('upload.template', 'tabungan') }}" class="btn btn-sm btn-outline-info">
                                    <i class="ti ti-file-download me-1"></i>Template Tabungan
                                </a>
                                <a href="{{ route('upload.template', 'deposito') }}" class="btn btn-sm btn-outline-success">
                                    <i class="ti ti-file-download me-1"></i>Template Deposito
                                </a>
                                <a href="{{ route('upload.template', 'linkage') }}" class="btn btn-sm btn-outline-warning">
                                    <i class="ti ti-file-download me-1"></i>Template Linkage
                                </a>
                            </div>
                        </div>

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
                                        <code class="small">nokontrak,nocif,nama,tgleff,tglexp,jw,plafon,mdlawal,mgnawal,osmdlc,osmgnc,angsmdl,angsmgn,angs_ke,angske_x,sahirrp,tgkpok,tgkmgn,tgkdnd,haritgkmdl,haritgkmgn,tgkharilanjut,blntgkpok,blntgkmgn,blntgkdnd,colbaru,kdaoh,acpok,alamat,telprmh,hp,fnama,kdkolek,kdgroupdeb,kdgroupdana,kdprd,pokpby,kdloc,kelurahan,kecamatan,kota,nmao,colllanjut,kdmco,kdsektor,kdsub,tagmdl,tagmgn,inptgl</code>
                                        <div class="mt-2">
                                            <a href="{{ route('upload.template', 'pembiayaan') }}" class="btn btn-xs btn-outline-primary btn-sm">
                                                <i class="ti ti-download me-1"></i>Download Template
                                            </a>
                                        </div>
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
                                        <strong>Header CSV (21 kolom):</strong>
                                        <br>
                                        <code class="small">nocif,notab,kodeprd,sahirrp,fnama,namaqq,stsrec,saldoblok,stsrest,tax,tgltrnakh,avgeom,stspep,kdrisk,noid,hp,tgllhr,nmibu,ketsandi,namapt,kodeloc</code>
                                        <div class="mt-2">
                                            <a href="{{ route('upload.template', 'tabungan') }}" class="btn btn-sm btn-outline-info">
                                                <i class="ti ti-download me-1"></i>Download Template
                                            </a>
                                        </div>
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
                                        <strong>Header CSV (44 kolom):</strong>
                                        <br>
                                        <code class="small">nodep,nocif,nobilyet,nama,nomrp,stsrec,kdprd,jkwaktu,jnsjkwaktu,tglbuka,tgleff,tgljtempo,aro,nisbah,spread,equivrate,komitrate,ststrn,kdwil,kodeaoh,kodeaop,noacbng,tambahnom,noid,alamat,kota,telprmh,hp,stskait,golcustbi,kelurahan,kecamatan,kdpos,kdrisk,tax,bnghtg,nisbahrp,stspep,noid,hp,tgllhr,nmibu,ketsandi,namapt</code>
                                        <div class="mt-2">
                                            <a href="{{ route('upload.template', 'deposito') }}" class="btn btn-sm btn-outline-success">
                                                <i class="ti ti-download me-1"></i>Download Template
                                            </a>
                                        </div>
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
                                        <strong>Header CSV (9 kolom):</strong>
                                        <br>
                                        <code class="small">nocif,norek,fnama,namaqq,tgleff,tgljt,prsnisbah,plafon,os</code>
                                        <div class="mt-2">
                                            <a href="{{ route('upload.template', 'linkage') }}" class="btn btn-sm btn-outline-warning">
                                                <i class="ti ti-download me-1"></i>Download Template
                                            </a>
                                        </div>
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
                                <strong>Data diproses di background</strong> untuk performa optimal
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
/* ==============================
   Upload Type Card (checkbox)
   ============================== */
.upload-type-card {
    display: block;
    border: 2px solid #d9dee3;
    border-radius: 10px;
    padding: 18px 12px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    background: #fff;
    user-select: none;
    position: relative;
}
.upload-type-card:hover {
    border-color: #696cff;
    background: #f5f5ff;
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(105,108,255,0.12);
}
.upload-type-card.selected {
    border-color: #696cff;
    background: #f0f0ff;
    box-shadow: 0 0 0 3px rgba(105,108,255,0.18);
}
.upload-type-input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}
.upload-type-card .checkmark {
    display: none;
    position: absolute;
    top: 8px;
    right: 8px;
    background: #696cff;
    color: #fff;
    border-radius: 50%;
    width: 22px;
    height: 22px;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
}
.upload-type-card.selected .checkmark {
    display: flex;
}

/* ==============================
   Upload Area (dropzone)
   ============================== */
.upload-area {
    border: 2px dashed #d9dee3;
    border-radius: 10px;
    transition: all 0.3s ease;
    background-color: #f8f9fa;
    cursor: pointer;
    overflow: hidden;
}
.upload-area:hover,
.upload-area.dragover {
    border-color: #696cff;
    background-color: #f0f0ff;
}
.upload-area-content {
    padding: 40px 20px;
    text-align: center;
}
.upload-icon {
    font-size: 3rem;
    color: #696cff;
    margin-bottom: 12px;
    line-height: 1;
}
.upload-area h5 {
    color: #566a7f;
    margin-bottom: 8px;
}

/* File preview card inside dropzone */
.upload-area-selected {
    padding: 16px 20px;
}
.file-preview-card {
    display: flex;
    align-items: center;
    gap: 14px;
    background: #fff;
    border: 1.5px solid #696cff;
    border-radius: 8px;
    padding: 14px 18px;
}
.file-icon {
    font-size: 2rem;
    color: #696cff;
    flex-shrink: 0;
}
.file-details {
    flex: 1;
    min-width: 0;
    text-align: left;
}
.file-name {
    display: block;
    font-weight: 600;
    color: #566a7f;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 280px;
}
.file-size {
    display: block;
    font-size: 0.78rem;
    color: #a1acb8;
}

/* ==============================
   Stats Card
   ============================== */
.stats-card {
    border-left: 4px solid;
    transition: transform 0.2s ease;
}
.stats-card:hover {
    transform: translateY(-2px);
}

/* ==============================
   Accordion
   ============================== */
.accordion-button:not(.collapsed) {
    background-color: #f8f9fa;
    color: #566a7f;
}

/* ==============================
   Upload Progress Overlay
   ============================== */
.upload-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(30, 32, 48, 0.72);
    backdrop-filter: blur(3px);
    display: flex !important;
    align-items: center;
    justify-content: center;
}
.upload-overlay[style*="display:none"],
.upload-overlay[style*="display: none"] {
    display: none !important;
}
.upload-overlay-box {
    background: #fff;
    border-radius: 16px;
    padding: 36px 40px;
    max-width: 460px;
    width: 92%;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,0.22);
}
.upload-spinner {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 72px;
    height: 72px;
    background: linear-gradient(135deg, #696cff, #9b59b6);
    border-radius: 50%;
    animation: spinPulse 1.8s ease-in-out infinite;
    color: #fff;
    font-size: 2rem;
}
@keyframes spinPulse {
    0%   { transform: scale(1); box-shadow: 0 0 0 0 rgba(105,108,255,0.5); }
    50%  { transform: scale(1.08); box-shadow: 0 0 0 12px rgba(105,108,255,0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(105,108,255,0); }
}
.upload-overlay-box .progress {
    background: #eef0ff;
}
#overlayFileList .overlay-file-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 10px;
    border-radius: 6px;
    background: #f8f9fa;
    margin-bottom: 6px;
    font-size: 0.85rem;
    text-align: left;
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    /* -------------------------------------------------------
       Elements
    ------------------------------------------------------- */
    const uploadForm        = document.getElementById('uploadForm');
    const uploadCheckboxes  = document.querySelectorAll('input[name="upload_types[]"]');
    const pembiayaanSection = document.getElementById('pembiayaanSection');
    const tabunganSection   = document.getElementById('tabunganSection');
    const depositoSection   = document.getElementById('depositoSection');
    const linkageSection    = document.getElementById('linkageSection');
    const submitButton      = document.getElementById('submitButton');
    const submitButtonText  = document.getElementById('submitButtonText');
    const historyContainer  = document.getElementById('uploadHistoryContainer');
    let historyPollingTimer    = null;
    let historyRequestInFlight = false;

    /* file inputs */
    const csvPembiayaan = document.getElementById('csvPembiayaan');
    const csvTabungan   = document.getElementById('csvTabungan');
    const csvDeposito   = document.getElementById('csvDeposito');
    const csvLinkage    = document.getElementById('csvLinkage');

    /* -------------------------------------------------------
       Checkbox: toggle sections + card style
    ------------------------------------------------------- */
    uploadCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const type      = this.value;
            const isChecked = this.checked;
            const card      = this.closest('.upload-type-card');

            // Toggle card visual
            if (isChecked) {
                card.classList.add('selected');
                // add checkmark if not present
                if (!card.querySelector('.checkmark')) {
                    const mark = document.createElement('span');
                    mark.className = 'checkmark';
                    mark.innerHTML = '<i class="ti ti-check"></i>';
                    card.appendChild(mark);
                }
            } else {
                card.classList.remove('selected');
            }

            // Toggle file section
            if (type === 'pembiayaan') {
                pembiayaanSection.style.display = isChecked ? 'block' : 'none';
                if (!isChecked) resetFile('Pembiayaan');
            } else if (type === 'tabungan') {
                tabunganSection.style.display = isChecked ? 'block' : 'none';
                if (!isChecked) resetFile('Tabungan');
            } else if (type === 'deposito') {
                depositoSection.style.display = isChecked ? 'block' : 'none';
                if (!isChecked) resetFile('Deposito');
            } else if (type === 'linkage') {
                linkageSection.style.display = isChecked ? 'block' : 'none';
                if (!isChecked) resetFile('Linkage');
            }

            updateSubmitButton();
        });
    });

    /* -------------------------------------------------------
       Submit button visibility
    ------------------------------------------------------- */
    function updateSubmitButton() {
        const checkedTypes = Array.from(uploadCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);

        let allFilesSelected = true;

        checkedTypes.forEach(type => {
            if (type === 'pembiayaan' && csvPembiayaan.files.length === 0) allFilesSelected = false;
            else if (type === 'tabungan'   && csvTabungan.files.length === 0) allFilesSelected = false;
            else if (type === 'deposito'   && csvDeposito.files.length === 0) allFilesSelected = false;
            else if (type === 'linkage'    && csvLinkage.files.length === 0)  allFilesSelected = false;
        });

        if (checkedTypes.length > 0 && allFilesSelected) {
            submitButton.style.display = 'block';
            const names = checkedTypes.map(t =>
                t === 'pembiayaan' ? 'Pembiayaan' :
                t === 'tabungan'   ? 'Tabungan'   :
                t === 'deposito'   ? 'Deposito'   :
                t === 'linkage'    ? 'Linkage'    : t
            );
            submitButtonText.textContent = `Upload ${names.join(', ')}`;
        } else {
            submitButton.style.display = 'none';
        }
    }

    /* -------------------------------------------------------
       File change handlers
    ------------------------------------------------------- */
    csvPembiayaan.addEventListener('change', () => { handleFile(csvPembiayaan.files[0], 'Pembiayaan'); updateSubmitButton(); });
    csvTabungan.addEventListener('change',   () => { handleFile(csvTabungan.files[0],   'Tabungan');   updateSubmitButton(); });
    csvDeposito.addEventListener('change',   () => { handleFile(csvDeposito.files[0],   'Deposito');   updateSubmitButton(); });
    csvLinkage.addEventListener('change',    () => { handleFile(csvLinkage.files[0],    'Linkage');    updateSubmitButton(); });

    /* -------------------------------------------------------
       Drag & drop setup
    ------------------------------------------------------- */
    setupDragDrop('uploadAreaPembiayaan', 'csvPembiayaan', 'Pembiayaan');
    setupDragDrop('uploadAreaTabungan',   'csvTabungan',   'Tabungan');
    setupDragDrop('uploadAreaDeposito',   'csvDeposito',   'Deposito');
    setupDragDrop('uploadAreaLinkage',    'csvLinkage',    'Linkage');

    function setupDragDrop(areaId, inputId, type) {
        const area  = document.getElementById(areaId);
        const input = document.getElementById(inputId);
        if (!area) return;

        area.querySelector('.upload-area-content').addEventListener('click', () => input.click());

        area.addEventListener('dragover', e => {
            e.preventDefault();
            area.classList.add('dragover');
        });
        area.addEventListener('dragleave', () => area.classList.remove('dragover'));
        area.addEventListener('drop', e => {
            e.preventDefault();
            area.classList.remove('dragover');
            const file = e.dataTransfer.files[0];
            if (file && file.name.endsWith('.csv')) {
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                handleFile(file, type);
                updateSubmitButton();
            } else {
                alert('Hanya file CSV yang diperbolehkan!');
            }
        });
    }

    /* -------------------------------------------------------
       Show selected file info
    ------------------------------------------------------- */
    function handleFile(file, type) {
        if (!file) return;
        const nameEl = document.getElementById('fileName'   + type);
        const sizeEl = document.getElementById('fileSize'   + type);
        const selEl  = document.getElementById('fileSelected' + type);
        const areaContent = document.querySelector('#uploadArea' + type + ' .upload-area-content');
        if (nameEl) nameEl.textContent = file.name;
        if (sizeEl) sizeEl.textContent = formatFileSize(file.size);
        if (selEl)  selEl.style.display = 'block';
        if (areaContent) areaContent.style.display = 'none';
    }

    /* -------------------------------------------------------
       Remove file (exposed globally for inline onclick)
    ------------------------------------------------------- */
    window.removeFile = function(type) {
        const inputMap = { Pembiayaan: csvPembiayaan, Tabungan: csvTabungan, Deposito: csvDeposito, Linkage: csvLinkage };
        const inp = inputMap[type];
        if (inp) inp.value = '';
        const selEl = document.getElementById('fileSelected' + type);
        const areaContent = document.querySelector('#uploadArea' + type + ' .upload-area-content');
        if (selEl)  selEl.style.display = 'none';
        if (areaContent) areaContent.style.display = '';
        updateSubmitButton();
    };

    function resetFile(type) {
        const inputMap = { Pembiayaan: csvPembiayaan, Tabungan: csvTabungan, Deposito: csvDeposito, Linkage: csvLinkage };
        const inp = inputMap[type];
        if (inp) inp.value = '';
        const selEl = document.getElementById('fileSelected' + type);
        const areaContent = document.querySelector('#uploadArea' + type + ' .upload-area-content');
        if (selEl)  selEl.style.display = 'none';
        if (areaContent) areaContent.style.display = '';
    }

    /* -------------------------------------------------------
       Utility
    ------------------------------------------------------- */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024, sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
    }

    /* -------------------------------------------------------
       Upload Progress Overlay (XHR with progress)
    ------------------------------------------------------- */
    const overlay         = document.getElementById('uploadProgressOverlay');
    const transferBar     = document.getElementById('transferBar');
    const transferPercent = document.getElementById('transferPercent');
    const transferLoaded  = document.getElementById('transferLoaded');
    const transferTotal   = document.getElementById('transferTotal');
    const transferPhase   = document.getElementById('transferPhase');
    const processingPhase = document.getElementById('processingPhase');
    const overlayTitle    = document.getElementById('overlayTitle');
    const overlaySubtitle = document.getElementById('overlaySubtitle');
    const overlayFileList = document.getElementById('overlayFileList');

    if (uploadForm) {
        uploadForm.addEventListener('submit', function (e) {
            e.preventDefault(); // intercept normal submit

            const formData = new FormData(uploadForm);

            // Build file list for overlay display
            const checkedTypes = Array.from(uploadCheckboxes).filter(cb => cb.checked).map(cb => cb.value);
            let fileListHtml = '';
            checkedTypes.forEach(type => {
                const inputMap = { pembiayaan: csvPembiayaan, tabungan: csvTabungan, deposito: csvDeposito, linkage: csvLinkage };
                const inp = inputMap[type];
                const label = type.charAt(0).toUpperCase() + type.slice(1);
                const fname = inp && inp.files[0] ? inp.files[0].name : '-';
                const fsize = inp && inp.files[0] ? formatFileSize(inp.files[0].size) : '';
                const colors = { pembiayaan:'primary', tabungan:'info', deposito:'success', linkage:'warning' };
                fileListHtml += `
                    <div class="overlay-file-item">
                        <span class="text-${colors[type]}"><i class="ti ti-file-text fs-5"></i></span>
                        <div>
                            <strong>${label}</strong>
                            <div class="text-muted" style="font-size:0.78rem;">${fname} &nbsp; ${fsize}</div>
                        </div>
                    </div>`;
            });
            overlayFileList.innerHTML = fileListHtml;

            // Reset overlay state
            transferBar.style.width = '0%';
            transferPercent.textContent = '0%';
            transferLoaded.textContent  = '0 KB';
            transferTotal.textContent   = '— KB';
            transferPhase.style.display   = '';
            processingPhase.style.display = 'none';
            overlayTitle.textContent    = 'Mengirim File ke Server...';
            overlaySubtitle.textContent = 'Harap tunggu, jangan tutup halaman ini';

            // Show overlay
            overlay.style.display = '';

            // XHR upload
            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', function (e) {
                if (e.lengthComputable) {
                    const pct = Math.round((e.loaded / e.total) * 100);
                    transferBar.style.width      = pct + '%';
                    transferPercent.textContent  = pct + '%';
                    transferLoaded.textContent   = formatFileSize(e.loaded);
                    transferTotal.textContent    = formatFileSize(e.total);
                }
            });

            xhr.upload.addEventListener('load', function () {
                transferBar.style.width      = '100%';
                transferPercent.textContent  = '100%';
                overlayTitle.textContent     = 'File Diterima, Memproses...';
                overlaySubtitle.textContent  = 'Server sedang memvalidasi dan mengimport data';
            });

            xhr.addEventListener('load', function () {
                // Server responded — switch to processing phase
                transferPhase.style.display   = 'none';
                processingPhase.style.display = '';
                overlayTitle.textContent      = 'Upload Berhasil!';
                overlaySubtitle.textContent   = 'Data diproses di background. Halaman akan dimuat ulang...';

                // If server redirected (302→200) follow it
                if (xhr.responseURL && xhr.responseURL !== window.location.href) {
                    setTimeout(() => { window.location.href = xhr.responseURL; }, 1200);
                } else {
                    setTimeout(() => { window.location.reload(); }, 1200);
                }
            });

            xhr.addEventListener('error', function () {
                overlay.style.display = 'none';
                alert('Upload gagal. Silakan coba lagi.');
            });

            xhr.open('POST', uploadForm.action);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(formData);
        });
    }

    /* -------------------------------------------------------
       History polling (background processing indicator)
    ------------------------------------------------------- */
    function hasProcessingUploads() {
        return historyContainer && historyContainer.dataset.hasProcessing === '1';
    }

    async function refreshUploadHistory() {
        if (!historyContainer || historyRequestInFlight) return;
        historyRequestInFlight = true;
        try {
            const refreshUrl = new URL(historyContainer.dataset.refreshUrl, window.location.origin);
            const currentUrl = new URL(window.location.href);
            const perPage = currentUrl.searchParams.get('per_page');
            const page    = currentUrl.searchParams.get('page');
            if (perPage) refreshUrl.searchParams.set('per_page', perPage);
            if (page)    refreshUrl.searchParams.set('page', page);

            const response = await fetch(refreshUrl.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const payload = await response.json();
            historyContainer.innerHTML = payload.html;
            historyContainer.dataset.hasProcessing = payload.hasProcessingUploads ? '1' : '0';

            if (!payload.hasProcessingUploads && historyPollingTimer) {
                clearInterval(historyPollingTimer);
                historyPollingTimer = null;
            }
        } catch (error) {
            console.error('Failed to refresh upload history:', error);
        } finally {
            historyRequestInFlight = false;
        }
    }

    function ensureHistoryPolling() {
        if (!historyContainer) return;
        if (hasProcessingUploads() && !historyPollingTimer) {
            historyPollingTimer = setInterval(refreshUploadHistory, 5000);
        }
        if (!hasProcessingUploads() && historyPollingTimer) {
            clearInterval(historyPollingTimer);
            historyPollingTimer = null;
        }
    }

    // Initialize
    updateSubmitButton();
    ensureHistoryPolling();
});

/* -------------------------------------------------------
   Per-page selector (called from history partial)
------------------------------------------------------- */
function changePerPage(perPage) {
    const url = new URL(window.location);
    url.searchParams.set('per_page', perPage);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

/* -------------------------------------------------------
   Confirm clear all data
------------------------------------------------------- */
function confirmClear() {
    if (confirm('Apakah Anda yakin ingin menghapus SEMUA data (Pembiayaan, Tabungan, Deposito, dan Linkage)? Tindakan ini tidak dapat dibatalkan!')) {
        document.getElementById('clearForm').submit();
    }
}
</script>
@endsection
