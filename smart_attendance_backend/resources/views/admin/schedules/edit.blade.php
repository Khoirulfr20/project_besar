@extends('layouts.app')
@section('title', 'Edit Jadwal')

@section('content')

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
    <h4 class="fw-semibold m-0">Edit Jadwal</h4>

    <a href="{{ route('admin.schedules.index') }}" 
       class="btn btn-light border rounded-3"
       onclick="return confirmBack(event)">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
</div>

{{-- CARD FORM --}}
<div class="card shadow-sm border-0 rounded-4">
    <div class="card-body p-4">

        {{-- ERROR HANDLER --}}
        @if ($errors->any())
            <div class="alert alert-danger border-0 rounded-3 shadow-sm">
                <strong>Periksa kembali input Anda:</strong>
                <ul class="mt-2 mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- FORM --}}
        <form id="editForm" action="{{ route('admin.schedules.update', $schedule->id) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- JUDUL --}}
            <div class="mb-3">
                <label class="form-label fw-medium">Judul Kegiatan</label>
                <input type="text" 
                       name="title" 
                       class="form-control form-control-modern @error('title') is-invalid @enderror" 
                       value="{{ old('title', $schedule->title) }}" required>
                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- DESKRIPSI --}}
            <div class="mb-3">
                <label class="form-label fw-medium">Deskripsi</label>
                <textarea class="form-control form-control-modern" name="description" rows="3">{{ old('description', $schedule->description) }}</textarea>
            </div>

            {{-- TANGGAL & WAKTU --}}
            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label fw-medium">Tanggal</label>
                    <input type="date" 
                           name="date" 
                           class="form-control form-control-modern @error('date') is-invalid @enderror"
                           value="{{ old('date', $schedule->date->format('Y-m-d')) }}" required>
                    @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-medium">Jam Mulai</label>
                    <input type="time" 
                           name="start_time"
                           id="start_time"
                           class="form-control form-control-modern @error('start_time') is-invalid @enderror"
                           value="{{ old('start_time', $schedule->start_time) }}" required>
                    @error('start_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-medium">Jam Selesai</label>
                    <input type="time" 
                           name="end_time"
                           id="end_time"
                           class="form-control form-control-modern @error('end_time') is-invalid @enderror"
                           value="{{ old('end_time', $schedule->end_time) }}" required>
                    @error('end_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

            </div>

            {{-- LOKASI & TIPE --}}
            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label class="form-label fw-medium">Lokasi</label>
                    <select name="location" 
                            id="locationSelect"
                            class="form-select form-select-modern @error('location') is-invalid @enderror">
                        <option value="">Pilih Lokasi</option>
                        <option value="Gedung Serba Guna" {{ old('location', $schedule->location) == 'Gedung Serba Guna' ? 'selected' : '' }}>Gedung Serba Guna</option>
                        <option value="Masjid" {{ old('location', $schedule->location) == 'Masjid' ? 'selected' : '' }}>Masjid</option>
                        <option value="Kantor PC" {{ old('location', $schedule->location) == 'Kantor PC' ? 'selected' : '' }}>Kantor PC</option>
                    </select>
                    @error('location') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-medium">Tipe Kegiatan</label>
                    <select name="type" 
                            class="form-select form-select-modern @error('type') is-invalid @enderror"
                            required>
                        <option value="">Pilih Tipe</option>
                        <option value="meeting"  {{ old('type', $schedule->type) == 'meeting' ? 'selected' : '' }}>Meeting</option>
                        <option value="training" {{ old('type', $schedule->type) == 'training' ? 'selected' : '' }}>Training</option>
                        <option value="event"    {{ old('type', $schedule->type) == 'event'    ? 'selected' : '' }}>Event</option>
                        <option value="other"    {{ old('type', $schedule->type) == 'other'    ? 'selected' : '' }}>Lainnya</option>
                    </select>
                    @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            {{-- PESERTA --}}
            <div class="mb-3 mt-3">
                <label class="form-label fw-medium">Peserta<span class="badge bg-danger">Min 3 orang</span></label>
                <select name="participant_ids[]" 
                        id="participantsSelectEdit"
                        class="form-select form-select-modern @error('participant_ids') is-invalid @enderror"
                        multiple required>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}"
                            {{ in_array($user->id, old('participant_ids', $schedule->participants->pluck('id')->toArray())) ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->employee_id }})
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> Gunakan kolom pencarian untuk menemukan peserta dengan cepat. <strong>Minimal 3 peserta wajib dipilih.</strong>
                </small>
                @error('participant_ids')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            {{-- STATUS --}}
            <div class="mt-3 mb-2">
                <label class="form-label fw-medium">Status</label>
                <select name="status" class="form-select form-select-modern" required>
                    <option value="scheduled" {{ old('status', $schedule->status) == 'scheduled' ? 'selected' : '' }}>Terjadwal</option>
                    <option value="ongoing"   {{ old('status', $schedule->status) == 'ongoing'   ? 'selected' : '' }}>Berlangsung</option>
                    <option value="completed" {{ old('status', $schedule->status) == 'completed' ? 'selected' : '' }}>Selesai</option>
                    <option value="cancelled" {{ old('status', $schedule->status) == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>

            {{-- BUTTONS --}}
            <div class="d-flex justify-content-end gap-2 mt-4">

                <button type="button" 
                        class="btn btn-light border rounded-3"
                        onclick="confirmBack(event)">
                    <i class="fas fa-times me-1"></i> Batal
                </button>

                <button type="button" 
                        class="btn btn-outline-secondary rounded-3"
                        onclick="confirmReset()">
                    <i class="fas fa-undo me-1"></i> Reset
                </button>

                <button type="button" 
                        class="btn btn-primary rounded-3"
                        onclick="confirmSubmit()">
                    <i class="fas fa-save me-1"></i> Update
                </button>

            </div>

        </form>

    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
{{-- Select2 CDN --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
// =============================================
// INISIALISASI SELECT2
// =============================================
$(document).ready(function() {
    // Lokasi Select dengan fitur pencarian
    $('#locationSelect').select2({
        placeholder: 'Ketik untuk mencari lokasi...',
        allowClear: true,
        width: '100%',
        language: {
            noResults: function() {
                return "Lokasi tidak ditemukan";
            }
        }
    });

    // Peserta Select (Multiple) dengan fitur pencarian
    $('#participantsSelectEdit').select2({
        placeholder: 'Cari dan pilih peserta (min. 3 orang)...',
        allowClear: true,
        width: '100%',
        closeOnSelect: false,
        language: {
            noResults: function() {
                return "Peserta tidak ditemukan";
            }
        }
    });

    // Custom styling untuk Select2 agar sesuai dengan form-control-modern
    $('.select2-container--default .select2-selection--single').css({
        'border-radius': '10px',
        'border': '1px solid #d6d8e1',
        'padding': '6px 12px',
        'height': 'auto',
        'min-height': '43px'
    });

    $('.select2-container--default .select2-selection--multiple').css({
        'border-radius': '10px',
        'border': '1px solid #d6d8e1',
        'padding': '4px 8px',
        'min-height': '43px'
    });
});

// =============================================
// VALIDASI JAM
// =============================================
function validateTime() {
    let start = document.getElementById("start_time").value;
    let end   = document.getElementById("end_time").value;

    if (start && end && end <= start) {
        Swal.fire({
            icon: "error",
            title: "Jam Tidak Valid",
            text: "Waktu selesai harus lebih besar dari waktu mulai.",
            confirmButtonColor: "#d33",
        });
        return false;
    }
    return true;
}

// =============================================
// KONFIRMASI UPDATE
// =============================================
function confirmSubmit() {
    // Validasi jam terlebih dahulu
    if (!validateTime()) return;

    // 🔥 CEK JUMLAH PESERTA SAAT KLIK UPDATE
    const selectedParticipants = $('#participantsSelectEdit').val() ? $('#participantsSelectEdit').val().length : 0;
    
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
        title: "Update Jadwal?",
        html: `Pastikan semua data sudah benar.<br><small class="text-muted">Peserta terpilih: <strong>${selectedParticipants} orang</strong></small>`,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Ya, Update",
        cancelButtonText: "Periksa Lagi",
        confirmButtonColor: "#667eea",
        cancelButtonColor: "#6c757d",
        buttonsStyling: false,
        customClass: {
            confirmButton: "btn btn-primary mx-2",
            cancelButton: "btn btn-secondary mx-2"
        }
    }).then(res => {
        if (res.isConfirmed) {
            loadingPopup();
            document.getElementById("editForm").submit();
        }
    });
}

// =============================================
// POPUP LOADING
// =============================================
function loadingPopup() {
    Swal.fire({
        title: "Menyimpan...",
        html: "Harap tunggu sebentar.",
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => Swal.showLoading(),
    });
}

// =============================================
// KONFIRMASI RESET
// =============================================
function confirmReset() {
    Swal.fire({
        title: "Reset Form?",
        text: "Semua input akan dikembalikan ke nilai awal.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya, Reset",
        cancelButtonText: "Batal",
        confirmButtonColor: "#d33",
        buttonsStyling: false,
        customClass: {
            confirmButton: "btn btn-danger mx-2",
            cancelButton:  "btn btn-secondary mx-2"
        }
    }).then(res => {
        if (res.isConfirmed) {
            // Reset form dan reinitialize Select2
            document.getElementById("editForm").reset();
            
            // Refresh Select2 setelah reset
            $('#locationSelect').val('{{ old('location', $schedule->location) }}').trigger('change');
            
            // Reset participants ke nilai awal
            let initialParticipants = @json(old('participant_ids', $schedule->participants->pluck('id')->toArray()));
            $('#participantsSelectEdit').val(initialParticipants).trigger('change');
        }
    });
}

// =============================================
// KONFIRMASI KEMBALI
// =============================================
function confirmBack(event) {
    event.preventDefault();

    Swal.fire({
        title: "Batalkan perubahan?",
        text: "Data yang belum disimpan akan hilang.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya, Kembali",
        cancelButtonText: "Tetap di sini",
        confirmButtonColor: "#d33",
        buttonsStyling: false,
        customClass: {
            confirmButton: "btn btn-danger mx-2",
            cancelButton:  "btn btn-secondary mx-2"
        }
    }).then(res => {
        if (res.isConfirmed) {
            window.location.href = "{{ route('admin.schedules.index') }}";
        }
    });
}
</script>

{{-- MODERN FORM STYLE --}}
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
    box-shadow: 0 0 0 .15rem rgba(102,126,234,0.25);
}

.card {
    border-radius: 14px;
}

label.form-label {
    color: #444;
}

/* Custom Select2 Styling agar sesuai dengan form-control-modern */
.select2-container--default .select2-selection--single:focus,
.select2-container--default .select2-selection--multiple:focus {
    border-color: #667eea !important;
    box-shadow: 0 0 0 0.15rem rgba(102,126,234,0.25);
}

.select2-container--default.select2-container--focus .select2-selection--single,
.select2-container--default.select2-container--focus .select2-selection--multiple {
    border-color: #667eea !important;
    box-shadow: 0 0 0 0.15rem rgba(102,126,234,0.25) !important;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #667eea !important;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #667eea !important;
    border: none !important;
    border-radius: 6px !important;
    color: white !important;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: white !important;
}

.select2-dropdown {
    border: 1px solid #d6d8e1 !important;
    border-radius: 10px !important;
}
</style>

@endpush