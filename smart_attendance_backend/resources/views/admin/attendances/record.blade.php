@extends('layouts.app')
@section('title', 'Record Attendance - Admin')

@section('content')

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
    <h4 class="fw-semibold text-dark m-0"><i class="fas fa-user-check me-2"></i> Rekam Wajah Manual</h4>
</div>

<div class="row">

    {{-- LEFT: CAMERA --}}
    <div class="col-md-4">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-primary text-white rounded-top-4">
                <h6 class="m-0"><i class="fas fa-camera me-1"></i> Ambil Foto Manual</h6>
            </div>

            <div class="card-body">

                {{-- Live Camera --}}
                <div id="cameraContainer">
                    <video id="video" autoplay class="w-100 rounded-3"></video>
                    <canvas id="canvas" style="display:none;"></canvas>
                </div>

                {{-- Preview --}}
                <div id="photoPreview" class="mt-2" style="display:none;">
                    <img id="previewImage" class="w-100 rounded-3 shadow-sm">
                </div>

                {{-- Buttons --}}
                <div class="d-grid gap-2 mt-3">
                    <button id="captureBtn" class="btn btn-primary" onclick="capturePhoto()">
                        <i class="fas fa-camera me-1"></i> Ambil Foto
                    </button>

                    <button id="retakeBtn" class="btn btn-warning" style="display:none;" onclick="retakePhoto()">
                        <i class="fas fa-redo me-1"></i> Ambil Ulang
                    </button>
                </div>

                {{-- Info --}}
                <div class="alert alert-info mt-3 small">
                    <i class="fas fa-info-circle me-1"></i> Tips:
                    <ul class="mb-1 mt-1 ps-3 small">
                        <li>Pencahayaan cukup</li>
                        <li>Tatap kamera</li>
                        <li>Jangan gunakan masker</li>
                    </ul>
                </div>

            </div>
        </div>
    </div>

    {{-- RIGHT: FORM --}}
    <div class="col-md-8">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-primary text-white rounded-top-4">
                <h6 class="m-0"><i class="fas fa-file-signature me-1"></i> Isi Form Absensi Manual</h6>
            </div>

            <div class="card-body">

                <form id="attendanceForm" onsubmit="submitAttendance(event)">
                    <input type="hidden" id="photoData" name="photo">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label fw-medium">User *</label>
                            <select class="form-select rounded-3" id="userId" name="user_id" required>
                                <option value="">Pilih User</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}">
                                    {{ $user->name }} ({{ $user->employee_id }})
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Tanggal *</label>
                            <input type="date" class="form-control rounded-3" id="date" name="date"
                                   value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Tipe Absen *</label>
                            <select class="form-select rounded-3" id="attendanceType" name="type" required>
                                <option value="check_in">Check-In</option>
                                <option value="check_out">Check-Out</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Waktu *</label>
                            <input type="time" class="form-control rounded-3" id="time" name="time"
                                   value="{{ date('H:i') }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Status *</label>
                            <select class="form-select rounded-3" id="status" name="status" required>
                                <option value="present">Hadir</option>
                                <option value="late">Terlambat</option>
                                <option value="absent">Tidak Hadir</option>
                                <option value="excused">Izin</option>
                                <option value="leave">Cuti</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Jadwal (Opsional)</label>
                            <select class="form-select rounded-3" id="scheduleId" name="schedule_id">
                                <option value="">Tidak Ada Jadwal</option>
                                @foreach($schedules as $schedule)
                                    <option value="{{ $schedule->id }}">{{ $schedule->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- REASON MANUAL BACKUP --}}
                        <div class="col-md-12">
                            <label class="form-label fw-medium">Alasan Input Manual *</label>
                            <select class="form-select rounded-3" id="manual_reason" name="manual_reason" required>
                                <option value="">Pilih Alasan</option>
                                <option value="face_not_detected">Wajah tidak terdeteksi</option>
                                <option value="camera_problem">Kamera bermasalah</option>
                                <option value="guest_or_non_registered">Tamu / Non-Anggota</option>
                                <option value="network_issue">Jaringan/Server bermasalah</option>
                                <option value="urgent_manual_override">Override darurat oleh admin</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-medium">Catatan (Opsional)</label>
                            <textarea class="form-control rounded-3" id="notes" name="notes" rows="3"></textarea>
                        </div>

                    </div>

                    {{-- Warning --}}
                    <div class="alert alert-warning mt-3 small" id="photoWarning">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Silakan ambil foto terlebih dahulu!
                    </div>

                    {{-- Submit --}}
                    <div class="d-grid mt-3">
                        <button type="submit" class="btn btn-success btn-lg rounded-3" id="submitBtn" disabled>
                            <i class="fas fa-check-circle me-1"></i> Simpan Kehadiran Manual
                        </button>
                    </div>

                </form>

            </div>
        </div>

        {{-- TODAY ATTENDANCE --}}
        <div class="card shadow-sm border-0 rounded-4 mt-3">
            <div class="card-header bg-secondary text-white rounded-top-4">
                <h6 class="m-0"><i class="fas fa-list me-1"></i> Kehadiran Hari Ini</h6>
            </div>

            <div class="card-body small">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
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

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let video = document.getElementById("video");
let canvas = document.getElementById("canvas");
let photoData = null;

// START CAMERA
async function startCamera() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        video.srcObject = stream;
    } catch (e) {
        Swal.fire("Akses Kamera Ditolak", e.message, "error");
    }
}

// CAPTURE
function capturePhoto() {
    const ctx = canvas.getContext("2d");
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    ctx.drawImage(video, 0, 0);

    photoData = canvas.toDataURL("image/jpeg");
    document.getElementById("photoData").value = photoData;

    previewImage.src = photoData;
    cameraContainer.style.display = "none";
    photoPreview.style.display = "block";
    captureBtn.style.display = "none";
    retakeBtn.style.display = "block";
    submitBtn.disabled = false;
    photoWarning.style.display = "none";

    video.srcObject.getTracks().forEach(t => t.stop());
}

// RETAKE
function retakePhoto() {
    photoData = null;

    cameraContainer.style.display = "block";
    photoPreview.style.display = "none";
    captureBtn.style.display = "block";
    retakeBtn.style.display = "none";
    submitBtn.disabled = true;
    photoWarning.style.display = "block";

    startCamera();
}

// SUBMIT
async function submitAttendance(e) {
    e.preventDefault();
    if (!photoData) return Swal.fire("Foto Belum Ada", "Silakan ambil foto dulu!", "warning");

    const reason = document.getElementById("manual_reason").value;
    if (!reason) return Swal.fire("Alasan Dibutuhkan", "Silakan pilih alasan input manual!", "warning");

    const fd = new FormData(e.target);

    Swal.fire({
        title: "Simpan Kehadiran Manual?",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#28a745",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Ya, Simpan",
        cancelButtonText: "Batal"
    }).then((res) => {
        if (res.isConfirmed) {
            fetch("/admin/attendance/record", {
                method: "POST",
                headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                body: fd
            })
            .then(r => r.json())
            .then(r => {
                if (r.success) {
                    Swal.fire("Berhasil!", "Kehadiran manual tersimpan.", "success").then(() => location.reload());
                } else {
                    Swal.fire("Gagal", r.message, "error");
                }
            });
        }
    });
}

// INIT
window.onload = startCamera;
</script>
@endpush
