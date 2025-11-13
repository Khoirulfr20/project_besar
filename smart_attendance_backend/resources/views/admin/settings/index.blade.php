@extends('layouts.app')

@section('title', 'Pengaturan Sistem')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-dark fw-semibold">Pengaturan Sistem</h1>

    {{-- Notifikasi Sukses --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Notifikasi Error --}}
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        {{-- Pengaturan Absensi --}}
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white fw-semibold">
                    <i class="fas fa-clock me-2"></i> Pengaturan Absensi
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="group" value="attendance">

                        <div class="mb-3">
                            <label for="work_start_time" class="form-label fw-semibold">Jam Masuk Kerja</label>
                            <input type="time" class="form-control" id="work_start_time"
                                   name="work_start_time"
                                   value="{{ \App\Models\Setting::getValue('work_start_time', '08:00') }}">
                            <small class="text-muted">Waktu mulai kerja standar</small>
                        </div>

                        <div class="mb-3">
                            <label for="work_end_time" class="form-label fw-semibold">Jam Pulang Kerja</label>
                            <input type="time" class="form-control" id="work_end_time"
                                   name="work_end_time"
                                   value="{{ \App\Models\Setting::getValue('work_end_time', '17:00') }}">
                            <small class="text-muted">Waktu selesai kerja standar</small>
                        </div>

                        <div class="mb-3">
                            <label for="late_tolerance" class="form-label fw-semibold">Toleransi Keterlambatan (menit)</label>
                            <input type="number" class="form-control" id="late_tolerance"
                                   name="late_tolerance"
                                   value="{{ \App\Models\Setting::getValue('late_tolerance', '15') }}"
                                   min="0">
                            <small class="text-muted">Batas waktu toleransi keterlambatan</small>
                        </div>

                        <div class="mb-3">
                            <label for="max_distance" class="form-label fw-semibold">Jarak Maksimal Absensi (meter)</label>
                            <input type="number" class="form-control" id="max_distance"
                                   name="max_distance"
                                   value="{{ \App\Models\Setting::getValue('max_distance', '100') }}"
                                   min="0">
                            <small class="text-muted">Jarak maksimal dari lokasi kantor</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
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
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="group" value="location">

                        <div class="mb-3">
                            <label for="office_latitude" class="form-label fw-semibold">Latitude</label>
                            <input type="text" class="form-control" id="office_latitude"
                                   name="office_latitude"
                                   value="{{ \App\Models\Setting::getValue('office_latitude', '-6.200000') }}"
                                   placeholder="-6.200000">
                            <small class="text-muted">Koordinat latitude kantor</small>
                        </div>

                        <div class="mb-3">
                            <label for="office_longitude" class="form-label fw-semibold">Longitude</label>
                            <input type="text" class="form-control" id="office_longitude"
                                   name="office_longitude"
                                   value="{{ \App\Models\Setting::getValue('office_longitude', '106.816666') }}"
                                   placeholder="106.816666">
                            <small class="text-muted">Koordinat longitude kantor</small>
                        </div>

                        <div class="mb-3">
                            <label for="office_address" class="form-label fw-semibold">Alamat Kantor</label>
                            <textarea class="form-control" id="office_address"
                                      name="office_address" rows="3">{{ \App\Models\Setting::getValue('office_address', '') }}</textarea>
                            <small class="text-muted">Alamat lengkap kantor</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
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
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="group" value="app">

                        <div class="mb-3">
                            <label for="app_name" class="form-label fw-semibold">Nama Aplikasi</label>
                            <input type="text" class="form-control" id="app_name"
                                   name="app_name"
                                   value="{{ \App\Models\Setting::getValue('app_name', 'Smart Attendance') }}">
                        </div>

                        <div class="mb-3">
                            <label for="company_name" class="form-label fw-semibold">Nama Perusahaan</label>
                            <input type="text" class="form-control" id="company_name"
                                   name="company_name"
                                   value="{{ \App\Models\Setting::getValue('company_name', '') }}">
                        </div>

                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input"
                                   id="require_photo" name="require_photo" value="1"
                                   {{ \App\Models\Setting::getValue('require_photo', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="require_photo">
                                Wajibkan Foto saat Absensi
                            </label>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input"
                                   id="enable_face_recognition" name="enable_face_recognition" value="1"
                                   {{ \App\Models\Setting::getValue('enable_face_recognition', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="enable_face_recognition">
                                Aktifkan Face Recognition
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
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
                        <tr>
                            <td><strong>Versi Laravel</strong></td>
                            <td>{{ app()->version() }}</td>
                        </tr>
                        <tr>
                            <td><strong>Versi PHP</strong></td>
                            <td>{{ PHP_VERSION }}</td>
                        </tr>
                        <tr>
                            <td><strong>Environment</strong></td>
                            <td>
                                <span class="badge bg-{{ app()->environment() === 'production' ? 'success' : 'warning' }}">
                                    {{ strtoupper(app()->environment()) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Storage Path</strong></td>
                            <td>{{ storage_path() }}</td>
                        </tr>
                    </table>

                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-warning w-100" onclick="clearCache()">
                            <i class="fas fa-sync me-1"></i> Clear Cache
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function clearCache() {
    if (confirm('Apakah Anda yakin ingin membersihkan cache?')) {
        fetch('{{ route("admin.settings.clear-cache") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            alert(data.success ? 'Cache berhasil dibersihkan!' : 'Gagal membersihkan cache!');
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan!');
        });
    }
}
</script>
@endpush
