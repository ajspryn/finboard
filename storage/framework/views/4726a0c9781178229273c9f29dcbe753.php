<?php $__env->startSection('title', 'Daily Activity Karyawan'); ?>

<?php $__env->startSection('content'); ?>
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4 class="card-title mb-1">
                                <i class="ti ti-calendar-event ti-sm me-2"></i>
                                Daily Activity Karyawan
                            </h4>
                            <p class="text-muted mb-0">Pantau aktivitas harian karyawan secara real-time</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="button" class="btn btn-primary" onclick="refreshData()">
                                <i class="ti ti-refresh ti-sm me-1"></i>
                                Refresh Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <div class="avatar-initial bg-label-success rounded">
                                <i class="ti ti-users ti-sm"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <span class="fw-semibold d-block mb-1">Total Aktivitas</span>
                            <h3 class="card-title mb-1"><?php echo e(count($activities)); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <div class="avatar-initial bg-label-info rounded">
                                <i class="ti ti-calendar ti-sm"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <span class="fw-semibold d-block mb-1">Tanggal Update</span>
                            <h6 class="card-title mb-1"><?php echo e(now()->format('d M Y H:i')); ?></h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <div class="avatar-initial bg-label-primary rounded">
                                <i class="ti ti-server ti-sm"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <span class="fw-semibold d-block mb-1">Status API</span>
                            <h6 class="card-title mb-1 text-success">Terhubung</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-3">
                        <i class="ti ti-list-details ti-sm me-2"></i>
                        Daftar Aktivitas Harian
                    </h5>

                    <!-- Filter Form -->
                    <form method="GET" action="<?php echo e(route('daily.activity.index')); ?>" class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="date" class="form-label">Pilih Tanggal</label>
                            <input type="date" class="form-control form-control-sm" id="date" name="date"
                                   value="<?php echo e($filterDate); ?>" onchange="this.form.submit()">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-1">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="ti ti-search ti-xs me-1"></i>
                                    Filter
                                </button>
                                <a href="<?php echo e(route('daily.activity.index')); ?>" class="btn btn-outline-secondary btn-sm">
                                    <i class="ti ti-refresh ti-xs me-1"></i>
                                    Hari Ini
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Current Filter Info -->
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <?php if($filterDate === now()->format('Y-m-d')): ?>
                                Menampilkan aktivitas hari ini (<?php echo e(\Carbon\Carbon::parse($filterDate)->format('d M Y')); ?>)
                            <?php else: ?>
                                Menampilkan aktivitas tanggal <?php echo e(\Carbon\Carbon::parse($filterDate)->format('d M Y')); ?>

                            <?php endif; ?>
                            - <?php echo e(count($activities)); ?> aktivitas ditemukan
                        </small>
                        <div class="d-flex gap-2">
                            <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Cari aktivitas..." onkeyup="filterTable()">
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="activityTable">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Karyawan</th>
                                    <th>Tanggal</th>
                                    <th>Judul Aktivitas</th>
                                    <th>Deskripsi</th>
                                    <th>Tugas</th>
                                    <th>Lampiran</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="activityTableBody">
                                <?php if(count($activities) > 0): ?>
                                    <?php $__currentLoopData = $activities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><?php echo e($index + 1); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm me-2">
                                                    <?php
                                                        $photoUrl = null;
                                                        if (isset($activity['employee']['photo_url']) && $activity['employee']['photo_url']) {
                                                            $photoUrl = $activity['employee']['photo_url'];
                                                        } elseif (isset($activity['employee']['photo']) && $activity['employee']['photo']) {
                                                            $photoUrl = 'https://absensi.bprsbtb.co.id/storage/' . $activity['employee']['photo'];
                                                        }
                                                    ?>
                                                    <?php if($photoUrl): ?>
                                                        <img src="<?php echo e($photoUrl); ?>" alt="Avatar" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                        <div class="avatar-initial bg-label-primary rounded-circle" style="width: 32px; height: 32px; display: none; align-items: center; justify-content: center;">
                                                            <?php echo e(substr($activity['employee']['full_name'] ?? 'N', 0, 1)); ?>

                                                        </div>
                                                    <?php else: ?>
                                                        <div class="avatar-initial bg-label-primary rounded-circle">
                                                            <?php echo e(substr($activity['employee']['full_name'] ?? 'N', 0, 1)); ?>

                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <span class="fw-semibold"><?php echo e($activity['employee']['full_name'] ?? '-'); ?></span>
                                                    <br>
                                                    <small class="text-muted"><?php echo e($activity['employee']['employee_id'] ?? '-'); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo e($activity['date'] ? \Carbon\Carbon::parse($activity['date'])->format('d M Y') : '-'); ?></td>
                                        <td><?php echo e($activity['title'] ?? '-'); ?></td>
                                        <td>
                                            <span class="text-truncate d-inline-block" style="max-width: 200px;" title="<?php echo e($activity['description'] ?? '-'); ?>">
                                                <?php echo e($activity['description'] ?? '-'); ?>

                                            </span>
                                        </td>
                                        <td>
                                            <?php if(isset($activity['tasks']) && is_array($activity['tasks'])): ?>
                                                <span class="badge bg-info"><?php echo e(count($activity['tasks'])); ?> tugas</span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if(isset($activity['attachments']) && is_array($activity['attachments'])): ?>
                                                <span class="badge bg-warning"><?php echo e(count($activity['attachments'])); ?> file</span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="showActivityDetail(<?php echo e($activity['id'] ?? $index); ?>)">
                                                <i class="ti ti-eye ti-xs"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="ti ti-calendar-x ti-lg text-muted mb-2"></i>
                                            <h6 class="text-muted">Tidak ada aktivitas</h6>
                                            <p class="text-muted small">
                                                <?php if($filterDate === now()->format('Y-m-d')): ?>
                                                    Belum ada aktivitas yang tercatat hari ini
                                                <?php else: ?>
                                                    Tidak ada aktivitas pada tanggal <?php echo e(\Carbon\Carbon::parse($filterDate)->format('d M Y')); ?>

                                                <?php endif; ?>
                                            </p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Modal Detail Aktivitas -->
<div class="modal fade" id="activityDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="activityDetailTitle">
                    <i class="ti ti-user ti-sm me-2"></i>
                    Detail Aktivitas Karyawan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="activityDetailBody">
                <!-- Detail akan diisi oleh JavaScript -->
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
let allActivities = <?php echo json_encode($allActivities, 15, 512) ?>;

document.addEventListener('DOMContentLoaded', function() {
    console.log('Daily Activity page loaded');
    console.log('Activities data:', allActivities);
});

function filterTable() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const tableBody = document.getElementById('activityTableBody');
    const rows = tableBody.getElementsByTagName('tr');

    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName('td');

        if (cells.length > 1) {
            const employeeName = cells[1].textContent.toLowerCase();
            const title = cells[3].textContent.toLowerCase();
            const description = cells[4].textContent.toLowerCase();

            let showRow = true;

            // Filter by search term (employee name, title, or description)
            if (searchTerm && !employeeName.includes(searchTerm) && !title.includes(searchTerm) && !description.includes(searchTerm)) {
                showRow = false;
            }

            row.style.display = showRow ? '' : 'none';
        }
    }
}

function showActivityDetail(activityId) {
    // Convert to number if it's a string
    const id = typeof activityId === 'string' ? parseInt(activityId) : activityId;

    let activity = allActivities.find(a => a.id === id);
    if (!activity) {
        // Fallback: find by index if id not found
        activity = allActivities[id];
    }

    if (!activity) {
        console.error('Activity not found for id:', activityId);
        return;
    }

    const modal = new bootstrap.Modal(document.getElementById('activityDetailModal'));
    const modalTitle = document.getElementById('activityDetailTitle');
    const modalBody = document.getElementById('activityDetailBody');

    modalTitle.innerHTML = `<i class="ti ti-user ti-sm me-2"></i>Detail Aktivitas - ${activity.employee?.full_name || 'N/A'}`;

    modalBody.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted mb-3">Informasi Karyawan</h6>
                <div class="mb-2">
                    <strong>Nama:</strong> ${activity.employee?.full_name || '-'}
                </div>
                <div class="mb-2">
                    <strong>ID Karyawan:</strong> ${activity.employee?.employee_id || '-'}
                </div>
                <div class="mb-2">
                    <strong>Email:</strong> ${activity.employee?.email || '-'}
                </div>
                <div class="mb-2">
                    <strong>Telepon:</strong> ${activity.employee?.phone || '-'}
                </div>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted mb-3">Informasi Aktivitas</h6>
                <div class="mb-2">
                    <strong>Tanggal:</strong> ${formatDate(activity.date)}
                </div>
                <div class="mb-2">
                    <strong>Jam Mulai:</strong> ${activity.start_time || '-'}
                </div>
                <div class="mb-2">
                    <strong>Jam Selesai:</strong> ${activity.end_time || '-'}
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <h6 class="text-muted mb-2">Judul Aktivitas</h6>
                <p class="mb-2 fw-semibold">${activity.title || '-'}</p>

                <h6 class="text-muted mb-2">Deskripsi</h6>
                <p class="mb-3">${activity.description || 'Tidak ada deskripsi'}</p>

                ${activity.tasks && activity.tasks.length > 0 ? `
                <h6 class="text-muted mb-2">Daftar Tugas</h6>
                <ul class="list-group mb-3">
                    ${activity.tasks.map(task => `
                        <li class="list-group-item">
                            <strong>${task.title}</strong>
                            ${task.notes ? `<br><small class="text-muted">${task.notes}</small>` : ''}
                        </li>
                    `).join('')}
                </ul>
                ` : ''}

                ${activity.attachments && activity.attachments.length > 0 ? `
                <h6 class="text-muted mb-2">Lampiran</h6>
                <div class="d-flex flex-wrap gap-2">
                    ${activity.attachments.map((attachment, index) => {
                        // Construct full URL from attachment path
                        const fileUrl = 'https://absensi.bprsbtb.co.id/storage/' + attachment;
                        return `
                            <a href="${fileUrl}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-file ti-xs me-1"></i>
                                File ${index + 1}
                            </a>
                        `;
                    }).join('')}
                </div>
                ` : ''}
            </div>
        </div>
    `;

    modal.show();
}

function formatDate(dateString) {
    if (!dateString) return '-';
    // Parse as UTC and keep the date as is (don't convert timezone)
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        timeZone: 'UTC' // Keep in UTC to prevent timezone conversion
    });
}

function refreshData() {
    // Reload the page to get fresh data from API
    window.location.reload();
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ajspryn/Project/finboard/resources/views/daily-activity/index.blade.php ENDPATH**/ ?>