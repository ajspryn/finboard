@extends('layouts.app')

@section('title', 'Financial Highlights')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Alerts -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ti ti-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ti ti-alert-circle me-2"></i>
                <strong>Terjadi kesalahan:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Header -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title mb-0">
                        <i class="ti ti-chart-line me-2"></i>
                        Financial Highlights
                    </h4>
                    <small class="text-muted">Kelola data indikator keuangan periode bulanan</small>
                </div>
                <a href="{{ route('financial-highlights.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i>
                    Tambah Data
                </a>
            </div>
        </div>

        <!-- Data Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Periode</th>
                                <th>CAR (%)</th>
                                <th>ROA (%)</th>
                                <th>ROE (%)</th>
                                <th>Aset (Rp) <small class="text-info">Otomatis</small></th>
                                <th>Pembiayaan (Rp) <small class="text-info">Otomatis</small></th>
                                <th>Laba/Rugi (Rp)</th>
                                <th>DPK (Rp) <small class="text-info">Otomatis</small></th>
                                <th>FDR (%)</th>
                                <th>NPF (%) <small class="text-info">Otomatis</small></th>
                                <th>BOPO (%)</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($highlights as $highlight)
                            <tr>
                                <td>
                                    <strong>{{ $highlight->period_year }}-{{ str_pad($highlight->period_month, 2, '0', STR_PAD_LEFT) }}</strong>
                                </td>
                                <td>{{ $highlight->car ? number_format($highlight->car, 2) : '-' }}</td>
                                <td>{{ $highlight->roa ? number_format($highlight->roa, 2) : '-' }}</td>
                                <td>{{ $highlight->roe ? number_format($highlight->roe, 2) : '-' }}</td>
                                <td>{{ $highlight->getCalculatedField('aset') ? 'Rp ' . number_format($highlight->getCalculatedField('aset'), 0, ',', '.') : '-' }}</td>
                                <td>{{ $highlight->getCalculatedField('pembiayaan') ? 'Rp ' . number_format($highlight->getCalculatedField('pembiayaan'), 0, ',', '.') : '-' }}</td>
                                <td>{{ $highlight->laba_rugi ? 'Rp ' . number_format($highlight->laba_rugi, 0, ',', '.') : '-' }}</td>
                                <td>{{ $highlight->getCalculatedField('dpk') ? 'Rp ' . number_format($highlight->getCalculatedField('dpk'), 0, ',', '.') : '-' }}</td>
                                <td>{{ $highlight->getCalculatedField('fdr') ? number_format($highlight->getCalculatedField('fdr'), 2) . ($highlight->fdr ? '' : ' <small class="text-muted">(Otomatis)</small>') : '-' }}</td>
                                <td>{{ $highlight->getCalculatedField('npf') ? number_format($highlight->getCalculatedField('npf'), 2) : '-' }}</td>
                                <td>{{ $highlight->bopo ? number_format($highlight->bopo, 2) : '-' }}</td>
                                <td>
                                    <a href="{{ route('financial-highlights.edit', $highlight) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="ti ti-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('financial-highlights.destroy', $highlight) }}" class="d-inline"
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="12" class="text-center text-muted">
                                    <i class="ti ti-database-off me-2"></i>
                                    Belum ada data financial highlights
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($highlights->hasPages())
                <div class="d-flex justify-content-center mt-3">
                    {{ $highlights->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
