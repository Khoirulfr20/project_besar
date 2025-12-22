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
                        </ul>

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

                        <div class="mb-3">
                            <label class="form-label fw-medium">Tipe Absen *</label>
                            <select class="form-select rounded-3" id="attendanceType">
                                <option value="check_in">Check-In</option>
                                <option value="check_out">Check-Out</option>
                            </select>
                            <small class="text-muted" id="attendanceTypeHint"></small>
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
    
    // Reset form - PERBAIKAN: Pastikan semua elemen di-reset dengan benar
    const typeSelect = document.getElementById("attendanceType");
    const scheduleSelect = document.getElementById("attendanceSchedule");
    const hintElement = document.getElementById("attendanceTypeHint");
    
    typeSelect.disabled = false;
    typeSelect.value = "check_in";
    scheduleSelect.value = "";
    hintElement.textContent = "";
    hintElement.className = "text-muted";
    
    // Re-enable semua options
    typeSelect.querySelector('option[value="check_in"]').disabled = false;
    typeSelect.querySelector('option[value="check_out"]').disabled = false;
    
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
    
    // Auto-detect status jika jadwal sudah dipilih
    const scheduleId = document.getElementById("attendanceSchedule").value;
    if (scheduleId) {
        await updateAttendanceTypeOptions(recognized.id, scheduleId);
    }
}

/* ===================== ✅ AUTO-DETECT CHECK-IN/OUT - FIXED ==================== */
async function updateAttendanceTypeOptions(userId, scheduleId) {
    const fd = new FormData();
    fd.append("user_id", userId);
    fd.append("schedule_id", scheduleId);

    try {
        const res = await fetch("{{ route('admin.attendance.face.checkStatus') }}", {
            method: "POST",
            headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}" },
            body: fd
        });

        const data = await res.json();
        const typeSelect = document.getElementById("attendanceType");
        const hintElement = document.getElementById("attendanceTypeHint");
        
        if (!data.success) {
            console.error("Error checking status:", data);
            return;
        }

        // Reset options dan state
        const checkInOption = typeSelect.querySelector('option[value="check_in"]');
        const checkOutOption = typeSelect.querySelector('option[value="check_out"]');
        checkInOption.disabled = false;
        checkOutOption.disabled = false;
        typeSelect.disabled = false;
        hintElement.className = "text-muted";

        // ✅ SUDAH CHECK-OUT (LENGKAP) - Cek dulu kondisi ini
        if (data.has_checked_out) {
            typeSelect.disabled = true;
            checkInOption.disabled = true;
            checkOutOption.disabled = true;
            hintElement.textContent = `✓ Sudah lengkap: Check-in ${data.check_in_time || ''} | Check-out ${data.check_out_time}`;
            hintElement.className = "text-success fw-bold";
            
            Swal.fire({
                icon: 'info',
                title: 'Absensi Lengkap',
                text: 'Anda sudah menyelesaikan check-in dan check-out untuk jadwal ini.',
                confirmButtonText: 'OK'
            });
        }
        // ✅ SUDAH CHECK-IN, BELUM CHECK-OUT - Wajib check-out
        else if (data.has_checked_in && !data.has_checked_out) {
            // PERBAIKAN UTAMA: Jangan disable select, hanya set value dan disable option check-in
            typeSelect.value = "check_out";
            typeSelect.disabled = false; // ✅ PENTING: Jangan disable select!
            checkInOption.disabled = true;
            checkOutOption.disabled = false;
            checkOutOption.selected = true;
            
            hintElement.textContent = `✓ Sudah check-in pada ${data.check_in_time}. Silakan check-out.`;
            hintElement.className = "text-warning fw-bold";
            
            Swal.fire({
                icon: 'info',
                title: 'Sudah Check-In',
                html: `Anda sudah check-in pada <strong>${data.check_in_time}</strong>.<br>Silakan lakukan <strong>Check-Out</strong>.`,
                timer: 3000,
                showConfirmButton: true
            });
        }
        // ✅ BELUM CHECK-IN - Wajib check-in dulu
        else if (!data.has_checked_in) {
            typeSelect.value = "check_in";
            typeSelect.disabled = false;
            checkInOption.disabled = false;
            checkOutOption.disabled = true;
            
            hintElement.textContent = "Silakan check-in terlebih dahulu";
            hintElement.className = "text-muted";
        }

    } catch (error) {
        console.error("Error updating attendance type:", error);
        Swal.fire("Error", "Gagal memeriksa status absensi: " + error.message, "error");
    }
}

/* ===================== ✅ EVENT: SCHEDULE CHANGE ==================== */
document.getElementById("attendanceSchedule").addEventListener('change', async function() {
    const typeSelect = document.getElementById("attendanceType");
    const hintElement = document.getElementById("attendanceTypeHint");
    
    if (recognized && this.value) {
        // Reset state sebelum check
        typeSelect.disabled = false;
        typeSelect.querySelector('option[value="check_in"]').disabled = false;
        typeSelect.querySelector('option[value="check_out"]').disabled = false;
        
        await updateAttendanceTypeOptions(recognized.id, this.value);
    } else {
        // Reset jika tidak ada jadwal
        typeSelect.querySelector('option[value="check_in"]').disabled = false;
        typeSelect.querySelector('option[value="check_out"]').disabled = false;
        typeSelect.disabled = false;
        typeSelect.value = "check_in";
        hintElement.textContent = "";
        hintElement.className = "text-muted";
    }
});

/* ===================== SAVE ATTENDANCE - FIXED ==================== */
function saveAttendance() {
    if (!recognized) 
        return Swal.fire("Belum Ada Data", "Scan wajah terlebih dahulu.", "warning");

    const scheduleId = document.getElementById("attendanceSchedule").value;
    if (!scheduleId)
        return Swal.fire("Pilih Jadwal", "Silakan pilih jadwal terlebih dahulu.", "warning");

    const typeSelect = document.getElementById("attendanceType");
    const type = typeSelect.value;
    
    // ✅ PERBAIKAN: Pastikan type value terambil dengan benar
    if (!type) {
        return Swal.fire("Tipe Tidak Valid", "Silakan pilih tipe absensi (Check-In atau Check-Out).", "warning");
    }
    
    const typeText = type === 'check_in' ? 'Check-In' : 'Check-Out';

    Swal.fire({
        title: `Konfirmasi ${typeText}`,
        html: `
            <div class="text-start">
                <p><strong>Nama:</strong> ${recognized.name}</p>
                <p><strong>ID Pegawai:</strong> ${recognized.employee_id}</p>
                <p><strong>Tipe:</strong> <span class="badge bg-${type === 'check_in' ? 'success' : 'danger'}">${typeText}</span></p>
            </div>
        `,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: 'Ya, Simpan!',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d'
    }).then(async (res) => {
        if (!res.isConfirmed) return;

        // Show loading
        Swal.fire({
            title: 'Menyimpan...',
            html: `Sedang memproses ${typeText}...`,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const fd = new FormData();
        fd.append("recognized_user_id", recognized.id);
        fd.append("schedule_id", scheduleId);
        fd.append("type", type);
        fd.append("photo", photoData);

        try {
            let r = await fetch("{{ route('admin.attendance.face.saveAttendance') }}", {
                method: "POST",
                headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                body: fd
            });

            let d = await r.json();
            
            if (d.success) {
                Swal.fire({
                    icon: "success",
                    title: "Berhasil!",
                    html: d.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => location.reload());
            } else {
                Swal.fire("Gagal", d.message, "error");
            }
        } catch (error) {
            Swal.fire("Error", "Terjadi kesalahan: " + error.message, "error");
        }
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

        Swal.fire({
            title: 'Memproses...',
            text: 'Sedang melakukan encoding wajah...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const fd = new FormData();
        fd.append("user_id", userId);
        fd.append("photo", dataURLtoFile(photoData));

        let res = await fetch("{{ route('admin.attendance.face.register') }}", {
            method: "POST",
            headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}" },
            body: fd
        });

        const data = await res.json();
        
        if (data.success) {
            Swal.fire({
                icon: "success",
                title: "Berhasil!",
                text: data.message || "Wajah berhasil diregistrasi.",
                timer: 2000,
                showConfirmButton: false
            }).then(() => location.reload());
        } else {
            Swal.fire("Gagal!", data.message || "Registrasi wajah gagal.", "error");
        }
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

    /* Hint text styling */
    #attendanceTypeHint {
        display: block;
        margin-top: 0.25rem;
        font-weight: 500;
    }
</style>
@endpush