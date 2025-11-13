@extends('layouts.app')
@section('title', 'Record Attendance - Admin')

@section('content')
<div class="d-flex justify-content-between pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Record Attendance</h1>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Ambil Foto</h5>
            </div>
            <div class="card-body">
                <!-- Camera Preview -->
                <div id="cameraContainer" class="mb-3">
                    <video id="video" width="100%" autoplay style="border-radius: 8px;"></video>
                    <canvas id="canvas" style="display: none;"></canvas>
                </div>

                <!-- Preview Photo -->
                <div id="photoPreview" style="display: none;">
                    <img id="previewImage" width="100%" style="border-radius: 8px;">
                </div>

                <!-- Camera Controls -->
                <div class="d-grid gap-2">
                    <button id="captureBtn" class="btn btn-primary" onclick="capturePhoto()">
                        <i class="fas fa-camera"></i> Ambil Foto
                    </button>
                    <button id="retakeBtn" class="btn btn-warning" style="display: none;" onclick="retakePhoto()">
                        <i class="fas fa-redo"></i> Ambil Ulang
                    </button>
                </div>

                <div class="alert alert-info mt-3" role="alert">
                    <small>
                        <i class="fas fa-info-circle"></i> Tips:
                        <ul class="mb-0 mt-1">
                            <li>Pastikan pencahayaan cukup</li>
                            <li>Lihat langsung ke kamera</li>
                            <li>Jangan gunakan masker</li>
                        </ul>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Data Kehadiran</h5>
            </div>
            <div class="card-body">
                <form id="attendanceForm" onsubmit="submitAttendance(event)">
                    <input type="hidden" id="photoData" name="photo">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pilih User *</label>
                            <select class="form-select" id="userId" name="user_id" required>
                                <option value="">-- Pilih User --</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->employee_id }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal *</label>
                            <input type="date" class="form-control" id="date" name="date" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipe Absen *</label>
                            <select class="form-select" id="attendanceType" name="type" required>
                                <option value="check_in">Check-In</option>
                                <option value="check_out">Check-Out</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Waktu *</label>
                            <input type="time" class="form-control" id="time" name="time" value="{{ date('H:i') }}" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status *</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="present">Hadir</option>
                                <option value="late">Terlambat</option>
                                <option value="absent">Tidak Hadir</option>
                                <option value="excused">Izin</option>
                                <option value="leave">Cuti</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jadwal (Opsional)</label>
                            <select class="form-select" id="scheduleId" name="schedule_id">
                                <option value="">-- Tidak Ada Jadwal --</option>
                                @foreach($schedules as $schedule)
                                    <option value="{{ $schedule->id }}">{{ $schedule->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Lokasi</label>
                        <input type="text" class="form-control" id="location" name="location" placeholder="Contoh: Kantor Pusat">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>

                    <div class="alert alert-warning" id="photoWarning">
                        <i class="fas fa-exclamation-triangle"></i> Silakan ambil foto terlebih dahulu!
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg" id="submitBtn" disabled>
                            <i class="fas fa-check"></i> Simpan Kehadiran
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recent Attendances -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">Kehadiran Hari Ini</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Check-In</th>
                                <th>Check-Out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="todayAttendances">
                            @foreach($todayAttendances as $att)
                            <tr>
                                <td>{{ $att->user->name }}</td>
                                <td>{{ $att->check_in_time ?? '-' }}</td>
                                <td>{{ $att->check_out_time ?? '-' }}</td>
                                <td>
                                    @if($att->status == 'present')
                                        <span class="badge bg-success">Hadir</span>
                                    @elseif($att->status == 'late')
                                        <span class="badge bg-warning">Terlambat</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($att->status) }}</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let video = document.getElementById('video');
let canvas = document.getElementById('canvas');
let photoData = null;

// Start camera
async function startCamera() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ 
            video: { facingMode: 'user' } 
        });
        video.srcObject = stream;
    } catch (err) {
        alert('Tidak dapat mengakses kamera: ' + err.message);
    }
}

// Capture photo
function capturePhoto() {
    const context = canvas.getContext('2d');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    context.drawImage(video, 0, 0);
    
    photoData = canvas.toDataURL('image/jpeg');
    document.getElementById('photoData').value = photoData;
    document.getElementById('previewImage').src = photoData;
    
    document.getElementById('cameraContainer').style.display = 'none';
    document.getElementById('photoPreview').style.display = 'block';
    document.getElementById('captureBtn').style.display = 'none';
    document.getElementById('retakeBtn').style.display = 'block';
    document.getElementById('submitBtn').disabled = false;
    document.getElementById('photoWarning').style.display = 'none';
    
    // Stop camera
    video.srcObject.getTracks().forEach(track => track.stop());
}

// Retake photo
function retakePhoto() {
    document.getElementById('cameraContainer').style.display = 'block';
    document.getElementById('photoPreview').style.display = 'none';
    document.getElementById('captureBtn').style.display = 'block';
    document.getElementById('retakeBtn').style.display = 'none';
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('photoWarning').style.display = 'block';
    
    photoData = null;
    startCamera();
}

// Submit attendance
async function submitAttendance(event) {
    event.preventDefault();
    
    if (!photoData) {
        alert('Silakan ambil foto terlebih dahulu!');
        return;
    }
    
    const formData = new FormData(event.target);
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
    
    try {
        const response = await fetch('/admin/attendance/record', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Kehadiran berhasil disimpan!');
            location.reload();
        } else {
            alert('Gagal: ' + result.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check"></i> Simpan Kehadiran';
        }
    } catch (error) {
        alert('Terjadi kesalahan: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-check"></i> Simpan Kehadiran';
    }
}

// Initialize
window.onload = function() {
    startCamera();
};
</script>
@endpush