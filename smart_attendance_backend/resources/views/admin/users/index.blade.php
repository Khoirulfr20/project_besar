@extends('layouts.app')
@section('title', 'Kelola Pengguna')

@section('content')

{{-- ===== Page Header ===== --}}
<div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
    <h4 class="fw-semibold text-dark m-0">Kelola Pengguna</h4>

    <a href="{{ route('admin.users.create') }}" class="btn btn-primary shadow-sm">
        <i class="fas fa-plus me-1"></i> Tambah Pengguna
    </a>
</div>

{{-- ===== Table Container ===== --}}
<div class="box-card">
    <div class="box-body">
        <table id="usersTable" class="table table-hover align-middle mb-0 modern-table">
            <thead>
                <tr>
                    <th>ID Karyawan</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Departemen</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>

            <tbody>
                @foreach($users as $user)
                <tr>
                    <td>{{ $user->employee_id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>

                    <td>
                        @if($user->role == 'admin')
                            <span class="badge bg-danger">Admin</span>
                        @elseif($user->role == 'pimpinan')
                            <span class="badge bg-warning text-dark">Pimpinan</span>
                        @else
                            <span class="badge bg-info">Anggota</span>
                        @endif
                    </td>

                    <td>{{ $user->department ?? '-' }}</td>

                    <td>
                        @if($user->is_active)
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-secondary">Nonaktif</span>
                        @endif
                    </td>

                    <td class="text-center">
                        <a href="{{ route('admin.users.edit', $user->id) }}" 
                           class="btn btn-sm btn-warning me-1">
                            <i class="fas fa-edit"></i>
                        </a>

                        <form action="{{ route('admin.users.destroy', $user->id) }}" 
                              method="POST" class="d-inline delete-form">
                            @csrf
                            @method('DELETE')

                            <button type="button" 
                                class="btn btn-sm btn-danger btn-delete"
                                data-name="{{ $user->name }}"
                                data-employee-id="{{ $user->employee_id }}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>

                </tr>
                @endforeach
            </tbody>

        </table>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {

    // ===== DataTable =====
    $('#usersTable').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.5/i18n/id.json' },
        pageLength: 10,
        order: [[1, "asc"]]
    });

    // ===== Delete Confirmation =====
    $(document).on('click', '.btn-delete', function (e) {
        e.preventDefault();

        const button = $(this);
        const form = button.closest('form');
        const userName = button.data('name');
        const employeeId = button.data('employee-id');

        Swal.fire({
            title: 'Hapus Pengguna?',
            html: `
                <strong>${userName}</strong><br>
                <small class="text-muted">ID: ${employeeId}</small><br><br>
                <span class="text-danger">Tindakan ini tidak dapat dibatalkan.</span>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            buttonsStyling: false,
            customClass: {
                confirmButton: 'btn btn-danger mx-2',
                cancelButton: 'btn btn-secondary mx-2'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Menghapus...',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => Swal.showLoading()
                });
                form.submit();
            }
        });
    });

});
</script>

{{-- ===== Modern CSS ===== --}}
<style>

    /* ===== CARD STYLE ===== */
    .box-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e2e6f3;
        padding: 0;
    }

    .box-body {
        padding: 18px;
    }

    /* ===== TABLE STYLE ===== */
    table.modern-table thead tr {
        background: #f5f7ff;
        border-bottom: 2px solid #e3e6f5;
    }

    table.modern-table thead th {
        font-weight: 600;
        color: #444;
        padding: 12px;
    }

    table.modern-table tbody tr {
        transition: 0.2s;
    }

    table.modern-table tbody tr:hover {
        background: #f5f7ff !important;
    }

    table.modern-table td {
        padding: 10px 12px;
    }

    /* ===== BUTTONS ===== */
    .btn-warning, .btn-danger {
        padding: 5px 8px;
        border-radius: 6px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        border: none;
    }

    .btn-primary:hover {
        opacity: .9;
    }

    /* ===== SWEETALERT FONT ===== */
    .swal2-popup {
        font-family: 'Inter', sans-serif;
    }
</style>
@endpush
