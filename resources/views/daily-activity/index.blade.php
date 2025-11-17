@extends('layouts.app')

@section('title', 'Daily Activity Karyawan')

@section('content')
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
                            <h3 class="card-title mb-1">{{ count($activities) }}</h3>
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
                            <h6 class="card-title mb-1">{{ now()->format('d M Y H:i') }}</h6>
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
                    <form method="GET" action="{{ route('daily.activity.index') }}" class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="date" class="form-label">Pilih Tanggal</label>
                            <input type="date" class="form-control form-control-sm" id="date" name="date"
                                   value="{{ $filterDate }}" onchange="this.form.submit()">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-1">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="ti ti-search ti-xs me-1"></i>
                                    Filter
                                </button>
                                <a href="{{ route('daily.activity.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="ti ti-refresh ti-xs me-1"></i>
                                    Hari Ini
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Current Filter Info -->
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            @if($filterDate === now()->format('Y-m-d'))
                                Menampilkan aktivitas hari ini ({{ \Carbon\Carbon::parse($filterDate)->format('d M Y') }})
                            @else
                                Menampilkan aktivitas tanggal {{ \Carbon\Carbon::parse($filterDate)->format('d M Y') }}
                            @endif
                            - {{ count($activities) }} aktivitas ditemukan
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
                                @if(count($activities) > 0)
                                    @foreach($activities as $index => $activity)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm me-2">
                                                    @if(isset($activity['employee']['photo_url']) && $activity['employee']['photo_url'])
                                                        <img src="{{ $activity['employee']['photo_url'] }}" alt="Avatar" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                        <div class="avatar-initial bg-label-primary rounded-circle" style="width: 32px; height: 32px; display: none; align-items: center; justify-content: center;">
                                                            {{ substr($activity['employee']['full_name'] ?? 'N', 0, 1) }}
                                                        </div>
                                                    @else
                                                        <div class="avatar-initial bg-label-primary rounded-circle">
                                                            {{ substr($activity['employee']['full_name'] ?? 'N', 0, 1) }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div>
                                                    <span class="fw-semibold">{{ $activity['employee']['full_name'] ?? '-' }}</span>
                                                    <br>
                                                    <small class="text-muted">{{ $activity['employee']['employee_id'] ?? '-' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $activity['date'] ? \Carbon\Carbon::parse($activity['date'])->format('d M Y') : '-' }}</td>
                                        <td>{{ $activity['title'] ?? '-' }}</td>
                                        <td>
                                            <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $activity['description'] ?? '-' }}">
                                                {{ $activity['description'] ?? '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if(isset($activity['tasks']) && is_array($activity['tasks']))
                                                <span class="badge bg-info">{{ count($activity['tasks']) }} tugas</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(isset($activity['attachments']) && is_array($activity['attachments']))
                                                <span class="badge bg-warning">{{ count($activity['attachments']) }} file</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="showActivityDetail({{ $activity['id'] ?? $index }})">
                                                <i class="ti ti-eye ti-xs"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="ti ti-calendar-x ti-lg text-muted mb-2"></i>
                                            <h6 class="text-muted">Tidak ada aktivitas</h6>
                                            <p class="text-muted small">
                                                @if($filterDate === now()->format('Y-m-d'))
                                                    Belum ada aktivitas yang tercatat hari ini
                                                @else
                                                    Tidak ada aktivitas pada tanggal {{ \Carbon\Carbon::parse($filterDate)->format('d M Y') }}
                                                @endif
                                            </p>
                                        </td>
                                    </tr>
                                @endif
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
@endsection

@section('scripts')
<script>
let allActivities = @json($allActivities);

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
                    ${activity.attachment_urls && activity.attachment_urls.length > 0 ?
                        activity.attachment_urls.map((url, index) => `
                            <a href="${url}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-file ti-xs me-1"></i>
                                File ${index + 1}
                            </a>
                        `).join('') :
                        activity.attachments.map((attachment, index) => `
                            <span class="btn btn-sm btn-outline-secondary" title="File tidak dapat diakses">
                                <i class="ti ti-file ti-xs me-1"></i>
                                File ${index + 1}
                            </span>
                        `).join('')
                    }
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
@endsection
