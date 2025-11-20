@extends('layouts.app')

@section('title', 'Manajemen User')

@section('content')
    <div class="row">
        <div class="col-md-12">
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

            <!-- Add New User Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="ti ti-user-plus me-2"></i>
                        Tambah User Baru
                    </h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.settings.store') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nama Lengkap</label>
                                    <input type="text"
                                           class="form-control @error('name') is-invalid @enderror"
                                           id="name"
                                           name="name"
                                           value="{{ old('name') }}"
                                           placeholder="Masukkan nama lengkap"
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email"
                                           class="form-control @error('email') is-invalid @enderror"
                                           id="email"
                                           name="email"
                                           value="{{ old('email') }}"
                                           placeholder="Masukkan email"
                                           required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-select @error('role') is-invalid @enderror"
                                            id="role"
                                            name="role"
                                            required>
                                        <option value="">Pilih Role</option>
                                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                                        <option value="pengurus" {{ old('role') == 'pengurus' ? 'selected' : '' }}>Pengurus</option>
                                        <option value="lending" {{ old('role') == 'lending' ? 'selected' : '' }}>Lending</option>
                                        <option value="funding" {{ old('role') == 'funding' ? 'selected' : '' }}>Funding</option>
                                    </select>
                                    @error('role')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-user-plus me-2"></i>
                                    Tambah User
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users List Card -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">
                        <i class="ti ti-users me-2"></i>
                        Daftar User ({{ $users->count() }})
                    </h4>
                </div>
                <div class="card-body">
                    @if($users->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Terakhir Login</th>
                                        <th>Dibuat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-sm me-3">
                                                        <span class="avatar-initial rounded-circle bg-label-primary">
                                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <span class="fw-medium">{{ $user->name }}</span>
                                                        @if($user->id === auth()->id())
                                                            <small class="text-muted">(Anda)</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $user->email }}</td>
                                            <td>
                                                <span class="badge bg-label-{{ $user->role === 'admin' ? 'primary' : ($user->role === 'pengurus' ? 'info' : ($user->role === 'lending' ? 'warning' : 'success')) }}">
                                                    {{ ucfirst($user->role) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($user->last_login)
                                                    <span class="text-muted">
                                                        <i class="ti ti-clock me-1"></i>
                                                        {{ $user->last_login->format('d M Y H:i') }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">
                                                        <i class="ti ti-minus me-1"></i>
                                                        Belum pernah login
                                                    </span>
                                                @endif
                                            </td>
                                            <td>{{ $user->created_at->format('d M Y') }}</td>
                                            <td>
                                                <!-- Edit Role Button -->
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-primary me-1"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editRoleModal"
                                                        onclick="editUserRole({{ $user->id }}, '{{ $user->name }}', '{{ $user->role }}')">
                                                    <i class="ti ti-edit"></i>
                                                </button>

                                                <!-- Delete Button (disabled for current user) -->
                                                @if($user->id !== auth()->id())
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-danger"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#deleteUserModal"
                                                            onclick="deleteUser({{ $user->id }}, '{{ $user->name }}')">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="ti ti-users display-1 text-muted mb-3"></i>
                            <h5 class="text-muted">Belum ada user</h5>
                            <p class="text-muted">Tambah user pertama menggunakan form di atas.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

<!-- Edit Role Modal -->
<div class="modal fade" id="editRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Role User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editRoleForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama User</label>
                        <input type="text" class="form-control" id="editUserName" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="editRole" class="form-label">Role</label>
                        <select class="form-select" id="editRole" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="pengurus">Pengurus</option>
                            <option value="lending">Lending</option>
                            <option value="funding">Funding</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">Hapus User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="deleteUserForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="ti ti-alert-triangle display-1 text-warning"></i>
                    </div>
                    <p class="text-center mb-1">Apakah Anda yakin ingin menghapus user:</p>
                    <p class="text-center fw-bold mb-0" id="deleteUserName"></p>
                    <p class="text-center text-muted mt-2">Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus User</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function editUserRole(userId, userName, currentRole) {
    document.getElementById('editUserName').value = userName;
    document.getElementById('editRole').value = currentRole;
    document.getElementById('editRoleForm').action = `/user-settings/${userId}`;
}

function deleteUser(userId, userName) {
    document.getElementById('deleteUserName').textContent = userName;
    document.getElementById('deleteUserForm').action = `/user-settings/${userId}`;
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>
@endsection
