@extends('layouts.app')
@section('title', 'Record Attendance (Face Recognition)')

@section('content')

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
    <h4 class="fw-semibold text-dark m-0">
        <i class="fas fa-face-smile me-2"></i> Pengenalan Wajah (Face Recognition)
    </h4>
</div>

<div class="row">

    {{-- MODE SWITCH --}}
    <div class="col-12 mb-3">
        <div class="btn-group w-100">
            <button type="button" id="modeAttendance" class="btn btn-primary active" onclick="switchMode('attendance')">
                <i class="fas fa-fingerprint me-1"></i> Scan Wajah
            </button>

            <button type="button" id="modeRegister" class="btn btn-outline-primary" onclick="switchMode('register')">
                <i class="fas fa-user-plus me-1"></i> Registrasi Wajah
            </button>
        </div>
    </div>

    {{-- LEFT CAMERA --}}
    <div class="col-md-4">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-primary text-white rounded-top-4">
                <h6 class="m-0"><i class="fas fa-camera me-1"></i> Ambil Foto</h6>
            </div>

            <div class="card-body">

                {{-- Camera Live --}}
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
                        <li>Pencahayaan cukup dan wajah menghadap kamera</li>
                        <li>Jangan gunakan masker / kacamata gelap</li>
                        <li>Posisi wajah di tengah frame</li>
                    </ul>
                </div>

            </div>
        </div>
    </div>

    {{-- RIGHT --}}
    <div class="col-md-8">

        {{-- ===== MODE ABSENSI ===== --}}
        <div id="attendanceFormBox">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-success text-white rounded-top-4">
                    <h6 class="m-0"><i class="fas fa-user-check me-1"></i> Hasil Pengenalan Wajah</h6>
                </div>

                <div class="card-body">

                    {{-- Result Box --}}
                    <div id="resultBox" style="display:none;">
                        <div class="alert alert-success d-flex align-items-center">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong id="recognizedName"></strong>&nbsp; terdeteksi.
                        </div>

                        <ul class="list-group mb-3">
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>ID Pegawai</strong>
                                <span id="recognizedEmployee"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Tingkat Confidence</strong>
                                <span id="recognizedConfidence"></span>
                            </li>
                        </ul>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Tipe Absen *</label>
                            <select class="form-select rounded-3" id="attendanceType">
                                <option value="check_in">Check-In</option>
                                <option value="check_out">Check-Out</option>
                            </select>
                        </div>

                        {{-- Pilih Jadwal --}}
                        <div class="mb-3">
                            <label class="form-label fw-medium">Pilih Jadwal Hari Ini *</label>
                            <select class="form-select rounded-3" id="attendanceSchedule">
                                <option value="">-- Pilih Jadwal --</option>
                                @foreach($todaySchedules as $sc)
                                <option value="{{ $sc->id }}">
                                    {{ $sc->title }} - {{ $sc->start_time }} s/d {{ $sc->end_time }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <button type="button" class="btn btn-success w-100 rounded-3" onclick="saveAttendance()">
                            <i class="fas fa-save me-1"></i> Simpan Kehadiran
                        </button>
                    </div>

                    {{-- Waiting Default --}}
                    <div id="waitingBox" class="text-center text-muted py-4">
                        <i class="fas fa-search fa-3x mb-3"></i>
                        <p class="mb-1">Ambil foto untuk memulai deteksi wajah.</p>
                    </div>

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
                        <tbody>
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

        {{-- ===== MODE REGISTRASI ===== --}}
        <div id="registerFormBox" style="display:none;">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-primary text-white rounded-top-4">
                    <h6 class="m-0"><i class="fas fa-user-plus me-1"></i> Registrasi Wajah Pengguna</h6>
                </div>

                <div class="card-body">
                    <p class="text-muted small">
                        Ambil foto terlebih dahulu, lalu pilih user yang akan didaftarkan wajahnya.
                    </p>

                    <form id="faceRegisterForm" onsubmit="submitRegister(event)">
                        <div class="mb-3">
                            <label class="form-label fw-medium">Pilih User *</label>
                            <select class="form-select rounded-3" id="registerUserIdForm" required>
                                <option value="">Pilih User</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">
                                        {{ $user->name }} ({{ $user->employee_id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="d-grid mt-3">
                            <button type="submit" class="btn btn-success btn-lg rounded-3">
                                <i class="fas fa-save me-1"></i> Simpan & Registrasikan Wajah
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
/* ===================== CAMERA ==================== */
let video = document.getElementById("video");
let canvas = document.getElementById("canvas");
let photoData = null;
let recognized = null;
let currentMode = 'attendance';

async function startCamera() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        video.srcObject = stream;
    } catch (e) {
        Swal.fire("Akses Kamera Ditolak", e.message, "error");
    }
}

function retakePhoto() {
    photoData = null;
    recognized = null;
    cameraContainer.style.display = "block";
    photoPreview.style.display = "none";
    captureBtn.style.display = "block";
    retakeBtn.style.display = "none";
    document.getElementById("resultBox").style.display = "none";
    document.getElementById("waitingBox").style.display = "block";
    startCamera();
}

function dataURLtoFile(dataurl) {
    let arr = dataurl.split(',');
    let mime = arr[0].match(/:(.*?);/)[1];
    let bstr = atob(arr[1]);
    let n = bstr.length;
    let u8arr = new Uint8Array(n);
    while (n--) u8arr[n] = bstr.charCodeAt(n);
    return new File([u8arr], "photo.jpg", { type: mime });
}

/* ===================== CAPTURE ==================== */
async function capturePhoto() {
    const ctx = canvas.getContext("2d");
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0);
    photoData = canvas.toDataURL("image/jpeg");

    previewImage.src = photoData;
    cameraContainer.style.display = "none";
    photoPreview.style.display = "block";
    captureBtn.style.display = "none";
    retakeBtn.style.display = "block";
    if (video.srcObject) video.srcObject.getTracks().forEach(t => t.stop());

    if (currentMode === 'attendance') await sendToRecognition();
}

/* ===================== MODE SWITCH ==================== */
function switchMode(mode) {
    currentMode = mode;

    const modeAttendance = document.getElementById("modeAttendance");
    const modeRegister = document.getElementById("modeRegister");

    document.getElementById("attendanceFormBox").style.display = mode === 'attendance' ? "block" : "none";
    document.getElementById("registerFormBox").style.display = mode === 'register' ? "block" : "none";

    // Update class untuk efek fokus
    if (mode === 'attendance') {
        modeAttendance.classList.remove("btn-outline-primary");
        modeAttendance.classList.add("btn-primary", "active");
        
        modeRegister.classList.remove("btn-primary", "active");
        modeRegister.classList.add("btn-outline-primary");
    } else {
        modeRegister.classList.remove("btn-outline-primary");
        modeRegister.classList.add("btn-primary", "active");
        
        modeAttendance.classList.remove("btn-primary", "active");
        modeAttendance.classList.add("btn-outline-primary");
    }

    document.getElementById("resultBox").style.display = "none";
    document.getElementById("waitingBox").style.display = "block";
}

/* ===================== SEND RECOGNITION ==================== */
async function sendToRecognition() {
    let fd = new FormData();
    fd.append("photo", dataURLtoFile(photoData));

    let res = await fetch("{{ route('admin.attendance.face.recognize') }}", {
        method: "POST",
        headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}" },
        body: fd
    });

    let data = await res.json();

    if (!data.success) {
        recognized = null;
        document.getElementById("resultBox").style.display = "none";
        return Swal.fire("Gagal", data.message || "Wajah tidak dikenali.", "error");
    }

    recognized = data.data;
    document.getElementById("waitingBox").style.display = "none";
    document.getElementById("resultBox").style.display = "block";
    document.getElementById("recognizedName").innerHTML = recognized.name;
    document.getElementById("recognizedEmployee").innerHTML = recognized.employee_id;
    document.getElementById("recognizedConfidence").innerHTML = (recognized.confidence * 100).toFixed(2) + "%";
}

/* ===================== SAVE ATTENDANCE ==================== */
function saveAttendance() {
    if (!recognized) 
        return Swal.fire("Belum Ada Data", "Scan wajah terlebih dahulu.", "warning");

    const scheduleId = document.getElementById("attendanceSchedule").value;
    if (!scheduleId)
        return Swal.fire("Pilih Jadwal", "Silakan pilih jadwal terlebih dahulu.", "warning");

    Swal.fire({
        title: "Simpan Kehadiran?",
        text: recognized.name + " (" + recognized.employee_id + ")",
        icon: "question",
        showCancelButton: true
    }).then(async (res) => {
        if (!res.isConfirmed) return;

        const fd = new FormData();
        fd.append("recognized_user_id", recognized.id);
        fd.append("schedule_id", scheduleId);
        fd.append("type", document.getElementById("attendanceType").value);
        fd.append("photo", photoData);

        let r = await fetch("{{ route('admin.attendance.face.saveAttendance') }}", {
            method: "POST",
            headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}" },
            body: fd
        });

        let d = await r.json();
        d.success
            ? Swal.fire("Berhasil!", d.message, "success").then(() => location.reload())
            : Swal.fire("Gagal", d.message, "error");
    });
}

/* ===================== REGISTER FACE ==================== */
async function submitRegister(e) {
    e.preventDefault();

    if (!photoData) return Swal.fire("Foto Belum Ada", "Silakan ambil foto terlebih dahulu.", "warning");

    const userId = document.getElementById("registerUserIdForm").value;
    if (!userId) return Swal.fire("User Belum Dipilih", "Silakan pilih user.", "warning");

    Swal.fire({
        title: "Registrasikan wajah ini?",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Ya, Simpan!"
    }).then(async (result) => {
        if (!result.isConfirmed) return;

        const fd = new FormData();
        fd.append("user_id", userId);
        fd.append("photo", dataURLtoFile(photoData));

        let res = await fetch("{{ route('admin.attendance.face.register') }}", {
            method: "POST",
            headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}" },
            body: fd
        });

        const data = await res.json();
        data.success
            ? Swal.fire("Berhasil!", data.message || "Wajah berhasil diregistrasi.", "success").then(() => location.reload())
            : Swal.fire("Gagal!", data.message || "Registrasi wajah gagal.", "error");
    });
}

/* ===================== INIT ==================== */
window.onload = startCamera;
</script>
@endpush

@push('styles')
<style>
    /* Efek fokus untuk tombol mode */
    .btn-group .btn {
        transition: all 0.3s ease;
    }

    /* Tombol aktif */
    .btn-group .btn.active.btn-primary {
        background-color: #0d6efd !important;
        border-color: #0d6efd !important;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.5) !important;
        transform: scale(1.02);
    }

    /* Tombol tidak aktif */
    .btn-group .btn.btn-outline-primary {
        background-color: transparent;
        color: #0d6efd;
        border-color: #0d6efd;
    }

    .btn-group .btn.btn-outline-primary:hover {
        background-color: #0d6efd;
        color: white;
    }

    /* Hapus outline default browser */
    .btn-group .btn:focus {
        outline: none;
    }
</style>
@endpush