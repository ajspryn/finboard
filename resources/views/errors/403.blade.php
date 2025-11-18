@extends('layouts.app')

@section('title', 'Akses Ditolak')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="ti ti-lock display-1 text-danger"></i>
                    </div>
                    <h1 class="display-4 text-danger mb-3">403</h1>
                    <h4 class="mb-3">Akses Ditolak</h4>
                    <p class="text-muted mb-4">
                        Anda tidak memiliki izin untuk mengakses halaman ini.
                        <br>
                        Silakan hubungi administrator jika Anda memerlukan akses.
                    </p>
                    <div class="mb-4">
                        <span class="badge bg-label-info fs-6">
                            <i class="ti ti-user me-1"></i>
                            Role Anda: {{ ucfirst(auth()->user()->role ?? 'Unknown') }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="{{ route('dashboard') }}" class="btn btn-primary">
                            <i class="ti ti-home me-2"></i>
                            Kembali ke Dashboard
                        </a>
                        <button onclick="history.back()" class="btn btn-secondary">
                            <i class="ti ti-arrow-left me-2"></i>
                            Kembali
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
