@if($uploadHistory->count() > 0)
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0">
                        <i class="ti ti-history me-2"></i>Riwayat Upload Data
                    </h5>
                    @if($hasProcessingUploads)
                        <small class="text-warning d-block mt-1">
                            <i class="ti ti-loader me-1"></i>Status diperbarui otomatis setiap 5 detik selama proses import berjalan.
                        </small>
                    @endif
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="text-muted small">
                        Menampilkan {{ $uploadHistory->firstItem() }}-{{ $uploadHistory->lastItem() }} dari {{ $uploadHistory->total() }} data
                    </div>
                    <div class="d-flex align-items-center">
                        <label for="perPageSelect" class="form-label me-2 mb-0 small">Tampilkan:</label>
                        <select id="perPageSelect" class="form-select form-select-sm" style="width: 80px;" onchange="changePerPage(this.value)">
                            @foreach($perPageOptions as $option)
                                <option value="{{ $option }}" {{ request('per_page', 5) == $option ? 'selected' : '' }}>{{ $option }}</option>
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
                                <th>User</th>
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
                                    <div style="max-width: 150px;">
                                        @if($upload['user_name'])
                                            <small class="text-primary">{{ $upload['user_name'] }}</small>
                                        @else
                                            <small class="text-muted">-</small>
                                        @endif
                                    </div>
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

                @if($uploadHistory->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="text-muted small">
                        Halaman {{ $uploadHistory->currentPage() }} dari {{ $uploadHistory->lastPage() }}
                    </div>
                    <nav aria-label="Upload history pagination">
                        <ul class="pagination pagination-sm mb-0">
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
