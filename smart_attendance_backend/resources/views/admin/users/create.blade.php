@extends('layouts.app')
@section('title', 'Tambah Pengguna')

@section('content')

{{-- ===== PAGE HEADER ===== --}}
<div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
    <h4 class="fw-semibold text-dark m-0">Tambah Pengguna</h4>

    <a href="{{ route('admin.users.index') }}" class="btn btn-light border">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
</div>

{{-- ===== FORM CARD ===== --}}
<div class="box-card">
    <div class="box-body">

        <form id="createUserForm" action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- === Row 1 === --}}
            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label fw-medium">ID Karyawan</label>
                    <input type="text" name="employee_id" 
                           class="form-control @error('employee_id') is-invalid @enderror"
                           value="{{ old('employee_id') }}" required>
                    @error('employee_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-medium">Nama Lengkap</label>
                    <input type="text" name="name" 
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

            </div>

            {{-- === Row 2 === --}}
            <div class="row g-3 mt-1">

                <div class="col-md-6">
                    <label class="form-label fw-medium">Email</label>
                    <input type="email" name="email" 
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}" required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-medium">Password</label>
                    <input type="password" name="password" 
                           class="form-control @error('password') is-invalid @enderror"
                           required>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

            </div>

            {{-- === Row 3 === --}}
            <div class="row g-3 mt-1">

                <div class="col-md-4">
                    <label class="form-label fw-medium">Role</label>
                    <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                        <option value="">Pilih Role</option>
                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="pimpinan" {{ old('role') == 'pimpinan' ? 'selected' : '' }}>Pimpinan</option>
                        <option value="anggota" {{ old('role') == 'anggota' ? 'selected' : '' }}>Anggota</option>
                    </select>
                    @error('role')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-medium">Jabatan</label>
                    <input type="text" name="position" class="form-control" value="{{ old('position') }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-medium">Departemen</label>
                    <input type="text" name="department" class="form-control" value="{{ old('department') }}">
                </div>

            </div>

            {{-- === Row 4 === --}}
            <div class="row g-3 mt-1">

                <div class="col-md-6">
                    <label class="form-label fw-medium">Telepon</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-medium">Foto Profil</label>
                    <input type="file" name="photo" 
                           class="form-control @error('photo') is-invalid @enderror"
                           accept="image/*">
                    @error('photo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

            </div>

            {{-- === ACTION BUTTONS === --}}
            <div class="d-flex justify-content-end mt-4 gap-2">
                <button type="button" id="btnCancel" class="btn btn-light border">
                    <i class="fas fa-times me-1"></i> Batal
                </button>

                <button type="button" id="btnSave" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Simpan
                </button>
            </div>

        </form>

    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(function () {

    let formChanged = false;

    // Detect form changes
    $('#createUserForm :input').on('input change', function() {
        formChanged = true;
    });

    // Save button
    $('#btnSave').click(function () {
        const form = document.getElementById('createUserForm');

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        Swal.fire({
            title: "Konfirmasi Simpan",
            icon: "question",
            html: `
                Simpan pengguna baru?<br><br>
                <div class="confirm-box">
                    <strong>ID:</strong> ${$('input[name="employee_id"]').val()}<br>
                    <strong>Nama:</strong> ${$('input[name="name"]').val()}<br>
                    <strong>Email:</strong> ${$('input[name="email"]').val()}
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: "Simpan",
            cancelButtonText: "Batal",
            confirmButtonColor: "#667eea",
            cancelButtonColor: "#6c757d",
            buttonsStyling: false,
            customClass: {
                confirmButton: "btn btn-primary mx-2",
                cancelButton: "btn btn-secondary mx-2"
            }
        }).then((res) => {
            if (res.isConfirmed) {
                Swal.fire({
                    title: "Menyimpan...",
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                formChanged = false;
                form.submit();
            }
        });

    });

    // Cancel button
    $('#btnCancel').click(function () {
        if (!formChanged) {
            window.location.href = "{{ route('admin.users.index') }}";
            return;
        }

        Swal.fire({
            title: "Batalkan?",
            icon: "warning",
            text: "Data yang sudah diisi tidak akan disimpan.",
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Ya, Batalkan",
            cancelButtonText: "Lanjutkan",
            buttonsStyling: false,
            customClass: {
                confirmButton: "btn btn-danger mx-2",
                cancelButton: "btn btn-secondary mx-2"
            }
        }).then((res) => {
            if (res.isConfirmed) window.location.href = "{{ route('admin.users.index') }}";
        });
    });

    // Warn before leaving
    window.addEventListener("beforeunload", function (e) {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = "";
        }
    });

});
</script>

{{-- ===== Modern CSS ===== --}}
<style>

    .box-card {
        background: #fff;
        border: 1px solid #e1e4f0;
        border-radius: 14px;
    }

    .box-body {
        padding: 20px;
    }

    label.form-label {
        font-weight: 600;
        color: #444;
    }

    input.form-control, select.form-select {
        border-radius: 10px;
        border: 1px solid #d5d8e6;
        transition: 0.2s;
    }

    input.form-control:focus, select.form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.15rem rgba(102, 126, 234, 0.25);
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        border: none;
    }

    .btn-primary:hover {
        opacity: .9;
    }

    .confirm-box {
        background: #f5f7ff;
        padding: 12px;
        border-radius: 10px;
        border-left: 4px solid #667eea;
        font-size: 0.9rem;
        color: #444;
    }
</style>
@endpush
