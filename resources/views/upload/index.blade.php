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

    <div id="uploadHistoryContainer"
         data-refresh-url="{{ route('upload.history') }}"
         data-has-processing="{{ $hasProcessingUploads ? '1' : '0' }}">
        @include('upload.partials.history', [
            'uploadHistory' => $uploadHistory,
            'perPageOptions' => $perPageOptions,
            'hasProcessingUploads' => $hasProcessingUploads,
        ])
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

                        <!-- Download Template Buttons -->
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
    const uploadForm = document.getElementById('uploadForm');
    const uploadCheckboxes = document.querySelectorAll('input[name="upload_types[]"]');
    const pembiayaanSection = document.getElementById('pembiayaanSection');
    const tabunganSection = document.getElementById('tabunganSection');
    const depositoSection = document.getElementById('depositoSection');
    const linkageSection = document.getElementById('linkageSection');
    const submitButton = document.getElementById('submitButton');
    const submitButtonText = document.getElementById('submitButtonText');
    const historyContainer = document.getElementById('uploadHistoryContainer');
    let historyPollingTimer = null;
    let historyRequestInFlight = false;

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

    function hasProcessingUploads() {
        return historyContainer && historyContainer.dataset.hasProcessing === '1';
    }

    async function refreshUploadHistory() {
        if (!historyContainer || historyRequestInFlight) {
            return;
        }

        historyRequestInFlight = true;

        try {
            const refreshUrl = new URL(historyContainer.dataset.refreshUrl, window.location.origin);
            const currentUrl = new URL(window.location.href);
            const perPage = currentUrl.searchParams.get('per_page');
            const page = currentUrl.searchParams.get('page');

            if (perPage) {
                refreshUrl.searchParams.set('per_page', perPage);
            }

            if (page) {
                refreshUrl.searchParams.set('page', page);
            }

            const response = await fetch(refreshUrl.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

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
        if (!historyContainer) {
            return;
        }

        if (hasProcessingUploads() && !historyPollingTimer) {
            historyPollingTimer = setInterval(refreshUploadHistory, 5000);
        }

        if (!hasProcessingUploads() && historyPollingTimer) {
            clearInterval(historyPollingTimer);
            historyPollingTimer = null;
        }
    }

    if (uploadForm) {
        uploadForm.addEventListener('submit', function() {
            const submitActionButton = uploadForm.querySelector('button[type="submit"]');
            if (submitActionButton) {
                submitActionButton.disabled = true;
            }
            submitButtonText.textContent = 'Mengirim file ke server...';
        });
    }

    // Initialize
    updateSubmitButton();
    ensureHistoryPolling();
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
