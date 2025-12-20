@extends('layouts.app')
@section('title', 'Tambah Jadwal')

@section('content')

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
    <h4 class="fw-semibold m-0">Tambah Jadwal</h4>

    <a href="{{ route('admin.schedules.index') }}" 
       class="btn btn-light border rounded-3"
       onclick="return confirmBack(event)">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
</div>

{{-- CARD FORM --}}
<div class="card shadow-sm border-0 rounded-4">
    <div class="card-body p-4">

        <form id="scheduleForm" action="{{ route('admin.schedules.store') }}" method="POST">
            @csrf

            {{-- JUDUL --}}
            <div class="mb-3">
                <label class="form-label fw-medium">Judul Kegiatan *</label>
                <input type="text" 
                       name="title" 
                       class="form-control form-control-modern @error('title') is-invalid @enderror"
                       value="{{ old('title') }}" required>
                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- DESKRIPSI --}}
            <div class="mb-3">
                <label class="form-label fw-medium">Deskripsi</label>
                <textarea name="description" class="form-control form-control-modern" rows="3">{{ old('description') }}</textarea>
            </div>

            {{-- TANGGAL & WAKTU --}}
            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label fw-medium">Tanggal *</label>
                    <input type="date" name="date"
                           class="form-control form-control-modern @error('date') is-invalid @enderror"
                           value="{{ old('date') }}" required>
                    @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-medium">Jam Mulai *</label>
                    <input type="time" name="start_time"
                           class="form-control form-control-modern @error('start_time') is-invalid @enderror"
                           value="{{ old('start_time') }}" required>
                    @error('start_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-medium">Jam Selesai *</label>
                    <input type="time" name="end_time"
                           class="form-control form-control-modern @error('end_time') is-invalid @enderror"
                           value="{{ old('end_time') }}" required>
                    @error('end_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

            </div>

            {{-- LOKASI & TIPE --}}
            <div class="row g-3 mt-1">

                <div class="col-md-6">
                    <label class="form-label fw-medium">Lokasi</label>
                    <input type="text" name="location" 
                           class="form-control form-control-modern"
                           value="{{ old('location') }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-medium">Tipe Kegiatan *</label>
                    <select name="type" 
                            class="form-select form-select-modern @error('type') is-invalid @enderror"
                            required>
                        <option value="">Pilih Tipe</option>
                        <option value="meeting"  {{ old('type') == 'meeting'  ? 'selected' : '' }}>Meeting</option>
                        <option value="training" {{ old('type') == 'training' ? 'selected' : '' }}>Training</option>
                        <option value="event"    {{ old('type') == 'event'    ? 'selected' : '' }}>Event</option>
                        <option value="other"    {{ old('type') == 'other'    ? 'selected' : '' }}>Lainnya</option>
                    </select>
                    @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

            </div>

            {{-- PESERTA --}}
            <div class="mb-3 mt-3">
                <label class="form-label fw-medium">Peserta * <span class="badge bg-danger">Min 3 orang</span></label>
                <select name="participant_ids[]" 
                        id="participantsSelectCreate"
                        class="form-select form-select-modern @error('participant_ids') is-invalid @enderror"
                        multiple size="8" required>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ in_array($user->id, old('participant_ids', [])) ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->employee_id }})
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> Tahan Ctrl untuk memilih lebih dari satu. <strong>Minimal 3 peserta wajib dipilih.</strong>
                </small>
                @error('participant_ids')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            {{-- BUTTON GROUP --}}
            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="button" 
                        class="btn btn-light border rounded-3"
                        onclick="return confirmBack(event)">
                    <i class="fas fa-times me-1"></i> Batal
                </button>

                <button type="button" 
                        class="btn btn-primary rounded-3"
                        onclick="confirmSubmit()">
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
// =============================================
// CEK VALIDASI JAM
// =============================================
function validateTime() {
    let start = document.querySelector('input[name="start_time"]').value;
    let end   = document.querySelector('input[name="end_time"]').value;

    if (start && end && end <= start) {
        Swal.fire({
            icon: 'error',
            title: 'Jam tidak valid',
            text: 'Jam selesai tidak boleh lebih awal dari jam mulai.',
            confirmButtonColor: '#d33',
        });
        return false;
    }
    return true;
}

// =============================================
// KONFIRMASI SIMPAN
// =============================================
function confirmSubmit() {
    // Validasi jam terlebih dahulu
    if (!validateTime()) return;

    // 🔥 CEK JUMLAH PESERTA SAAT KLIK SIMPAN
    const selectedParticipants = document.querySelectorAll('#participantsSelectCreate option:checked').length;
    
    if (selectedParticipants < 3) {
        Swal.fire({
            icon: 'error',
            title: 'Peserta Kurang',
            text: 'Minimal 3 peserta harus dipilih untuk melanjutkan!',
            confirmButtonColor: '#d33',
        });
        return false;
    }

    // Jika validasi lolos, tampilkan konfirmasi
    Swal.fire({
        title: 'Simpan Jadwal?',
        html: `Pastikan semua data sudah benar.<br><small class="text-muted">Peserta terpilih: <strong>${selectedParticipants} orang</strong></small>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Simpan',
        cancelButtonText: 'Periksa Lagi',
        confirmButtonColor: '#667eea',
        cancelButtonColor: '#6c757d',
        buttonsStyling: false,
        customClass: {
            confirmButton: 'btn btn-primary mx-2',
            cancelButton:   'btn btn-secondary mx-2'
        }
    }).then(result => {
        if (result.isConfirmed) {
            loadingPopup();
            document.getElementById('scheduleForm').submit();
        }
    });
}

// =============================================
// POPUP LOADING
// =============================================
function loadingPopup() {
    Swal.fire({
        title: 'Menyimpan...',
        html: 'Harap tunggu sebentar.',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => Swal.showLoading(),
    });
}

// =============================================
// KONFIRMASI KEMBALI
// =============================================
function confirmBack(event) {
    event.preventDefault();

    Swal.fire({
        title: 'Batalkan perubahan?',
        text: 'Data yang belum disimpan akan hilang.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Kembali',
        cancelButtonText: 'Tetap di sini',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        buttonsStyling: false,
        customClass: {
            confirmButton: 'btn btn-danger mx-2',
            cancelButton:  'btn btn-secondary mx-2'
        }
    }).then(result => {
        if (result.isConfirmed) {
            window.location.href = "{{ route('admin.schedules.index') }}";
        }
    });
}
</script>

{{-- ============================= --}}
{{-- MODERN FORM STYLE --}}
{{-- ============================= --}}
<style>
.form-control-modern,
.form-select-modern {
    border-radius: 10px;
    border: 1px solid #d6d8e1;
    padding: 10px 12px;
    transition: .2s;
}

.form-control-modern:focus,
.form-select-modern:focus {
    border-color: #667eea !important;
    box-shadow: 0 0 0 0.15rem rgba(102,126,234,0.25);
}

.card {
    border-radius: 14px;
}

label.form-label {
    color: #444;
}
</style>

@endpush
