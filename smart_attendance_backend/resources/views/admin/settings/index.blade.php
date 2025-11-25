@extends('layouts.app')

@section('title', 'Pengaturan Sistem')

@section('content')

{{-- Header --}}
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Pengaturan Sistem</h1>
</div>

{{-- Notifikasi --}}
@if(session('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: "{{ session('success') }}",
            confirmButtonColor: '#3085d6'
        });
    </script>
@endif

@if(session('error'))
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: "{{ session('error') }}",
            confirmButtonColor: '#d33'
        });
    </script>
@endif


<div class="row">

    {{-- Pengaturan Absensi --}}
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white fw-semibold">
                <i class="fas fa-clock me-2"></i> Pengaturan Absensi
            </div>

            <div class="card-body">
                <form class="settings-form" action="{{ route('admin.settings.update') }}" method="POST">
                    @csrf @method('PUT')
                    <input type="hidden" name="group" value="attendance">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jam Masuk Kerja</label>
                        <input type="time" class="form-control" name="work_start_time"
                               value="{{ \App\Models\Setting::getValue('work_start_time', '08:00') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jam Pulang Kerja</label>
                        <input type="time" class="form-control" name="work_end_time"
                               value="{{ \App\Models\Setting::getValue('work_end_time', '17:00') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Toleransi Keterlambatan (menit)</label>
                        <input type="number" class="form-control" name="late_tolerance" min="0"
                               value="{{ \App\Models\Setting::getValue('late_tolerance', '15') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jarak Maksimal Absensi (meter)</label>
                        <input type="number" class="form-control" name="max_distance" min="0"
                               value="{{ \App\Models\Setting::getValue('max_distance', '100') }}">
                    </div>

                    <button type="button" class="btn btn-primary w-100 btn-save-settings">
                        <i class="fas fa-save me-1"></i> Simpan Pengaturan
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Pengaturan Lokasi --}}
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white fw-semibold">
                <i class="fas fa-map-marker-alt me-2"></i> Lokasi Kantor
            </div>

            <div class="card-body">
                <form class="settings-form" action="{{ route('admin.settings.update') }}" method="POST">
                    @csrf @method('PUT')
                    <input type="hidden" name="group" value="location">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Latitude</label>
                        <input type="text" class="form-control" name="office_latitude"
                               value="{{ \App\Models\Setting::getValue('office_latitude', '-6.200000') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Longitude</label>
                        <input type="text" class="form-control" name="office_longitude"
                               value="{{ \App\Models\Setting::getValue('office_longitude', '106.816666') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alamat Kantor</label>
                        <textarea class="form-control" rows="3"
                                  name="office_address">{{ \App\Models\Setting::getValue('office_address', '') }}</textarea>
                    </div>

                    <button type="button" class="btn btn-primary w-100 btn-save-settings">
                        <i class="fas fa-save me-1"></i> Simpan Lokasi
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Pengaturan Aplikasi --}}
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white fw-semibold">
                <i class="fas fa-cogs me-2"></i> Pengaturan Aplikasi
            </div>

            <div class="card-body">
                <form class="settings-form" action="{{ route('admin.settings.update') }}" method="POST">
                    @csrf @method('PUT')
                    <input type="hidden" name="group" value="app">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Aplikasi</label>
                        <input type="text" class="form-control" name="app_name"
                               value="{{ \App\Models\Setting::getValue('app_name', 'Smart Attendance') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Perusahaan</label>
                        <input type="text" class="form-control" name="company_name"
                               value="{{ \App\Models\Setting::getValue('company_name', '') }}">
                    </div>

                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" name="require_photo" value="1"
                               {{ \App\Models\Setting::getValue('require_photo', '1') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold">Wajibkan Foto saat Absensi</label>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" name="enable_face_recognition" value="1"
                               {{ \App\Models\Setting::getValue('enable_face_recognition', '1') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold">Aktifkan Face Recognition</label>
                    </div>

                    <button type="button" class="btn btn-primary w-100 btn-save-settings">
                        <i class="fas fa-save me-1"></i> Simpan Pengaturan
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Informasi Sistem --}}
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white fw-semibold">
                <i class="fas fa-info-circle me-2"></i> Informasi Sistem
            </div>

            <div class="card-body">

                <table class="table table-sm">
                    <tr><td><strong>Versi Laravel</strong></td><td>{{ app()->version() }}</td></tr>
                    <tr><td><strong>Versi PHP</strong></td><td>{{ PHP_VERSION }}</td></tr>
                    <tr>
                        <td><strong>Environment</strong></td>
                        <td>
                            <span class="badge bg-{{ app()->environment() === 'production' ? 'success' : 'warning' }}">
                                {{ strtoupper(app()->environment()) }}
                            </span>
                        </td>
                    </tr>
                    <tr><td><strong>Storage Path</strong></td><td>{{ storage_path() }}</td></tr>
                </table>

                <button class="btn btn-warning w-100 mt-3" onclick="clearCache()">
                    <i class="fas fa-sync me-1"></i> Clear Cache
                </button>

            </div>
        </div>
    </div>
</div>

@endsection


@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
/* ===============================
   1. KONFIRMASI SIMPAN PENGATURAN
   =============================== */
document.querySelectorAll('.btn-save-settings').forEach(btn => {
    btn.addEventListener('click', function () {
        const form = this.closest('form');

        Swal.fire({
            title: 'Simpan Pengaturan?',
            text: 'Pastikan semua data sudah sesuai.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Simpan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Menyimpan...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => Swal.showLoading()
                });

                form.submit();
            }
        });
    });
});


/* ===============================
   2. CLEAR CACHE
   =============================== */
function clearCache() {
    Swal.fire({
        title: 'Bersihkan Cache?',
        text: 'Cache sistem akan dihapus.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Bersihkan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#d33'
    }).then((result) => {
        if (result.isConfirmed) {

            fetch('{{ route("admin.settings.clear-cache") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                }
            })
            .then(res => res.json())
            .then(data => {
                Swal.fire({
                    icon: data.success ? 'success' : 'error',
                    title: data.success ? 'Berhasil' : 'Gagal',
                    text: data.message ?? (data.success ? 'Cache dibersihkan!' : 'Cache gagal dibersihkan!')
                });
            })
            .catch(() => {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Terjadi kesalahan.'
                });
            });
        }
    });
}
</script>

@endpush
