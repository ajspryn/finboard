<?php $__env->startSection('title', 'Upload Data Funding'); ?>

<?php $__env->startSection('styles'); ?>
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
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="row">
        <div class="col-lg-12">
            <h4 class="fw-bold mb-4">
                <i class="ti ti-wallet me-2"></i>Upload Data Funding
            </h4>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if(session('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ti ti-check me-2"></i><?php echo e(session('success')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ti ti-alert-triangle me-2"></i><?php echo e(session('error')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ti ti-alert-triangle me-2"></i>
        <ul class="mb-0">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

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
                                <h3 class="mb-0 me-2"><?php echo e(number_format($totalData)); ?></h3>
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
                                    <?php if($lastUpload): ?>
                                        <?php echo e(\Carbon\Carbon::parse($lastUpload)->format('d M Y H:i')); ?>

                                    <?php else: ?>
                                        Belum ada upload
                                    <?php endif; ?>
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
                                    <?php
                                        $totalSaldo = $stats->sum('total_saldo');
                                    ?>
                                    Rp <?php echo e(number_format($totalSaldo / 1000000000, 2)); ?> M
                                </h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics per Jenis -->
    <?php if($stats->count() > 0): ?>
    <div class="row mb-4">
        <?php $__currentLoopData = $stats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text mb-1"><?php echo e($stat->jenis ?? 'Lainnya'); ?></p>
                            <div class="d-flex align-items-end mb-2">
                                <h4 class="mb-0 me-2"><?php echo e(number_format($stat->jumlah)); ?></h4>
                                <small class="text-muted">rekening</small>
                            </div>
                            <small class="text-success fw-medium">
                                Rp <?php echo e(number_format($stat->total_saldo / 1000000000, 2)); ?> Miliar
                            </small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-<?php echo e($stat->jenis === 'TABUNGAN' ? 'info' : ($stat->jenis === 'DEPOSITO' ? 'success' : 'warning')); ?>">
                                <i class="ti ti-wallet ti-md"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php endif; ?>

    <!-- Upload History Table -->
    <?php if($uploadHistory->count() > 0): ?>
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
                                <?php $__currentLoopData = $uploadHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $upload): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-2">
                                                <span class="avatar-initial rounded bg-label-primary">
                                                    <i class="ti ti-calendar ti-sm"></i>
                                                </span>
                                            </div>
                                            <strong><?php echo e(str_pad($upload->month, 2, '0', STR_PAD_LEFT)); ?>-<?php echo e($upload->year); ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-2">
                                                <span class="avatar-initial rounded bg-label-<?php echo e($upload->jenis === 'TABUNGAN' ? 'info' : 'success'); ?>">
                                                    <i class="ti ti-<?php echo e($upload->jenis === 'TABUNGAN' ? 'piggy-bank' : 'clock-dollar'); ?> ti-sm"></i>
                                                </span>
                                            </div>
                                            <strong><?php echo e($upload->jenis); ?></strong>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?php echo e(number_format($upload->count)); ?></span>
                                    </td>
                                    <td class="text-end">
                                        <strong>Rp <?php echo e(number_format($upload->total_saldo / 1000000000, 2)); ?> M</strong>
                                    </td>
                                    <td class="text-center">
                                        <small class="text-muted"><?php echo e(\Carbon\Carbon::parse($upload->last_upload)->format('d/m/Y H:i')); ?></small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success">
                                            <i class="ti ti-check ti-xs me-1"></i>Berhasil
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
                    <form id="uploadForm" action="<?php echo e(route('funding.upload')); ?>" method="POST" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>

                        <!-- Periode Data -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="month" class="form-label">
                                    <i class="ti ti-calendar me-1"></i>Bulan
                                </label>
                                <select name="month" id="month" class="form-select" required>
                                    <option value="">Pilih Bulan</option>
                                    <option value="01" <?php echo e(date('m') == '01' ? 'selected' : ''); ?>>Januari</option>
                                    <option value="02" <?php echo e(date('m') == '02' ? 'selected' : ''); ?>>Februari</option>
                                    <option value="03" <?php echo e(date('m') == '03' ? 'selected' : ''); ?>>Maret</option>
                                    <option value="04" <?php echo e(date('m') == '04' ? 'selected' : ''); ?>>April</option>
                                    <option value="05" <?php echo e(date('m') == '05' ? 'selected' : ''); ?>>Mei</option>
                                    <option value="06" <?php echo e(date('m') == '06' ? 'selected' : ''); ?>>Juni</option>
                                    <option value="07" <?php echo e(date('m') == '07' ? 'selected' : ''); ?>>Juli</option>
                                    <option value="08" <?php echo e(date('m') == '08' ? 'selected' : ''); ?>>Agustus</option>
                                    <option value="09" <?php echo e(date('m') == '09' ? 'selected' : ''); ?>>September</option>
                                    <option value="10" <?php echo e(date('m') == '10' ? 'selected' : ''); ?>>Oktober</option>
                                    <option value="11" <?php echo e(date('m') == '11' ? 'selected' : ''); ?>>November</option>
                                    <option value="12" <?php echo e(date('m') == '12' ? 'selected' : ''); ?>>Desember</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="year" class="form-label">
                                    <i class="ti ti-calendar-event me-1"></i>Tahun
                                </label>
                                <select name="year" id="year" class="form-select" required>
                                    <option value="">Pilih Tahun</option>
                                    <?php
                                        $currentYear = date('Y');
                                        for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                                            $selected = ($y == $currentYear) ? 'selected' : '';
                                            echo "<option value=\"{$y}\" {$selected}>{$y}</option>";
                                        }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- Upload Tabungan -->
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="ti ti-piggy-bank me-1"></i>File CSV Tabungan
                            </label>
                            <div class="upload-area" id="uploadAreaTabungan">
                                <div class="upload-icon">
                                    <i class="ti ti-cloud-upload"></i>
                                </div>
                                <h5>Upload CSV Tabungan</h5>
                                <p class="text-muted mb-3">Drag & drop atau klik untuk memilih file</p>
                                <input type="file" name="csv_tabungan" id="csvTabungan" accept=".csv" class="d-none" required>
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
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="ti ti-clock-dollar me-1"></i>File CSV Deposito
                            </label>
                            <div class="upload-area" id="uploadAreaDeposito">
                                <div class="upload-icon">
                                    <i class="ti ti-cloud-upload"></i>
                                </div>
                                <h5>Upload CSV Deposito</h5>
                                <p class="text-muted mb-3">Drag & drop atau klik untuk memilih file</p>
                                <input type="file" name="csv_deposito" id="csvDeposito" accept=".csv" class="d-none" required>
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
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="ti ti-link me-1"></i>File CSV Linkage
                            </label>
                            <div class="upload-area" id="uploadAreaLinkage">
                                <div class="upload-icon">
                                    <i class="ti ti-cloud-upload"></i>
                                </div>
                                <h5>Upload CSV Linkage</h5>
                                <p class="text-muted mb-3">Drag & drop atau klik untuk memilih file</p>
                                <input type="file" name="csv_linkage" id="csvLinkage" accept=".csv" class="d-none" required>
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
                                <i class="ti ti-upload me-1"></i>Upload Tiga File Funding
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
                                Upload 3 file CSV: <strong>Tabungan</strong>, <strong>Deposito</strong>, dan <strong>Linkage</strong>
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
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
    // Tabungan file handling
    const uploadAreaTabungan = document.getElementById('uploadAreaTabungan');
    const csvTabungan = document.getElementById('csvTabungan');
    const fileInfoTabungan = document.getElementById('fileInfoTabungan');
    const fileNameTabungan = document.getElementById('fileNameTabungan');
    const fileSizeTabungan = document.getElementById('fileSizeTabungan');

    // Deposito file handling
    const uploadAreaDeposito = document.getElementById('uploadAreaDeposito');
    const csvDeposito = document.getElementById('csvDeposito');
    const fileInfoDeposito = document.getElementById('fileInfoDeposito');
    const fileNameDeposito = document.getElementById('fileNameDeposito');
    const fileSizeDeposito = document.getElementById('fileSizeDeposito');

    // Linkage file handling
    const uploadAreaLinkage = document.getElementById('uploadAreaLinkage');
    const csvLinkage = document.getElementById('csvLinkage');
    const fileInfoLinkage = document.getElementById('fileInfoLinkage');
    const fileNameLinkage = document.getElementById('fileNameLinkage');
    const fileSizeLinkage = document.getElementById('fileSizeLinkage');

    const submitButton = document.getElementById('submitButton');

    // Check if all three files are selected
    function checkAllFiles() {
        if (csvTabungan.files.length > 0 && csvDeposito.files.length > 0 && csvLinkage.files.length > 0) {
            submitButton.style.display = 'block';
        } else {
            submitButton.style.display = 'none';
        }
    }

    // Tabungan: Click to upload
    uploadAreaTabungan.addEventListener('click', (e) => {
        if (e.target !== csvTabungan) {
            csvTabungan.click();
        }
    });

    // Tabungan: File selected
    csvTabungan.addEventListener('change', (e) => {
        handleFile(e.target.files[0], 'Tabungan');
        checkAllFiles();
    });

    // Tabungan: Drag & Drop
    uploadAreaTabungan.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadAreaTabungan.classList.add('dragover');
    });

    uploadAreaTabungan.addEventListener('dragleave', () => {
        uploadAreaTabungan.classList.remove('dragover');
    });

    uploadAreaTabungan.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadAreaTabungan.classList.remove('dragover');

        const file = e.dataTransfer.files[0];
        if (file && file.name.endsWith('.csv')) {
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            csvTabungan.files = dataTransfer.files;
            handleFile(file, 'Tabungan');
            checkAllFiles();
        } else {
            alert('Hanya file CSV yang diperbolehkan!');
        }
    });

    // Deposito: Click to upload
    uploadAreaDeposito.addEventListener('click', (e) => {
        if (e.target !== csvDeposito) {
            csvDeposito.click();
        }
    });

    // Deposito: File selected
    csvDeposito.addEventListener('change', (e) => {
        handleFile(e.target.files[0], 'Deposito');
        checkAllFiles();
    });

    // Deposito: Drag & Drop
    uploadAreaDeposito.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadAreaDeposito.classList.add('dragover');
    });

    uploadAreaDeposito.addEventListener('dragleave', () => {
        uploadAreaDeposito.classList.remove('dragover');
    });

    uploadAreaDeposito.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadAreaDeposito.classList.remove('dragover');

        const file = e.dataTransfer.files[0];
        if (file && file.name.endsWith('.csv')) {
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            csvDeposito.files = dataTransfer.files;
            handleFile(file, 'Deposito');
            checkAllFiles();
        } else {
            alert('Hanya file CSV yang diperbolehkan!');
        }
    });

    // Linkage: Click to upload
    uploadAreaLinkage.addEventListener('click', (e) => {
        if (e.target !== csvLinkage) {
            csvLinkage.click();
        }
    });

    // Linkage: File selected
    csvLinkage.addEventListener('change', (e) => {
        handleFile(e.target.files[0], 'Linkage');
        checkAllFiles();
    });

    // Linkage: Drag & Drop
    uploadAreaLinkage.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadAreaLinkage.classList.add('dragover');
    });

    uploadAreaLinkage.addEventListener('dragleave', () => {
        uploadAreaLinkage.classList.remove('dragover');
    });

    uploadAreaLinkage.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadAreaLinkage.classList.remove('dragover');

        const file = e.dataTransfer.files[0];
        if (file && file.name.endsWith('.csv')) {
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            csvLinkage.files = dataTransfer.files;
            handleFile(file, 'Linkage');
            checkAllFiles();
        } else {
            alert('Hanya file CSV yang diperbolehkan!');
        }
    });

    function handleFile(file, type) {
        if (file) {
            if (type === 'Tabungan') {
                fileNameTabungan.textContent = file.name;
                fileSizeTabungan.textContent = formatFileSize(file.size);
                fileInfoTabungan.style.display = 'block';
            } else if (type === 'Deposito') {
                fileNameDeposito.textContent = file.name;
                fileSizeDeposito.textContent = formatFileSize(file.size);
                fileInfoDeposito.style.display = 'block';
            } else if (type === 'Pembiayaan') {
                fileNamePembiayaan.textContent = file.name;
                fileSizePembiayaan.textContent = formatFileSize(file.size);
                fileInfoPembiayaan.style.display = 'block';
            } else if (type === 'Linkage') {
                fileNameLinkage.textContent = file.name;
                fileSizeLinkage.textContent = formatFileSize(file.size);
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
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ajspryn/Project/finboard/resources/views/funding/index.blade.php ENDPATH**/ ?>