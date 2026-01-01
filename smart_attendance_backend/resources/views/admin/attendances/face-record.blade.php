@extends('layouts.app')
@section('title', 'Rekam Kehadiran Face Recognition')

@section('content')

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
    <div>
        <h4 class="fw-semibold text-dark m-0">
            <i class="fas fa-fingerprint me-2"></i> Rekam Kehadiran (Face Recognition)
        </h4>
        <p class="text-muted small mb-0">Sistem pengenalan wajah menggunakan LBPH untuk check-in dan check-out</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="row">

    {{-- LEFT: CAMERA --}}
    <div class="col-md-4">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-primary text-white rounded-top-4">
                <h6 class="m-0"><i class="fas fa-camera me-1"></i> Kamera</h6>
            </div>

            <div class="card-body">

                {{-- Camera Live --}}
                <div id="cameraContainer">
                    <video id="video" autoplay playsinline class="w-100 rounded-3 border"></video>
                    <canvas id="canvas" style="display:none;"></canvas>
                </div>

                {{-- Preview --}}
                <div id="photoPreview" class="mt-2" style="display:none;">
                    <img id="previewImage" class="w-100 rounded-3 shadow-sm border" alt="Preview">
                </div>

                {{-- Buttons --}}
                <div class="d-grid gap-2 mt-3">
                    <button id="captureBtn" class="btn btn-lg btn-primary" onclick="capturePhoto()">
                        <i class="fas fa-camera me-1"></i> Ambil Foto
                    </button>

                    <button id="retakeBtn" class="btn btn-lg btn-warning" style="display:none;" onclick="retakePhoto()">
                        <i class="fas fa-redo me-1"></i> Ambil Ulang
                    </button>
                </div>

                {{-- Tips --}}
                <div class="alert alert-info mt-3 small mb-0">
                    <strong><i class="fas fa-lightbulb me-1"></i> Tips:</strong>
                    <ul class="mb-0 mt-2 ps-3">
                        <li>Pencahayaan <strong>cukup terang</strong></li>
                        <li>Wajah <strong>menghadap kamera</strong></li>
                        <li><strong>Tidak pakai masker</strong></li>
                        <li>Posisi wajah di <strong>tengah</strong></li>
                    </ul>
                </div>

            </div>
        </div>
    </div>

    {{-- RIGHT: RESULT & FORM --}}
    <div class="col-md-8">

        {{-- Recognition Result --}}
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-success text-white rounded-top-4">
                <h6 class="m-0">
                    <i class="fas fa-user-check me-1"></i> Hasil Pengenalan Wajah
                    <span class="badge bg-light text-success ms-2">LBPH</span>
                </h6>
            </div>

            <div class="card-body">

                {{-- Waiting State --}}
                <div id="waitingBox" class="text-center py-5">
                    <i class="fas fa-camera-retro fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Ambil Foto Untuk Memulai</h5>
                    <p class="text-muted small mb-0">
                        Sistem akan mendeteksi wajah secara otomatis
                    </p>
                </div>

                {{-- Result Box --}}
                <div id="resultBox" style="display:none;">
                    
                    {{-- User Info --}}
                    <div class="alert alert-success d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle fa-2x me-3"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-1" id="recognizedName"></h5>
                            <p class="mb-0 small">Wajah berhasil dikenali</p>
                        </div>
                    </div>

                    {{-- User Details --}}
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="card bg-light border-0">
                                <div class="card-body py-2">
                                    <small class="text-muted d-block">ID Pegawai</small>
                                    <strong id="recognizedEmployee"></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card bg-light border-0">
                                <div class="card-body py-2">
                                    <small class="text-muted d-block">Confidence</small>
                                    <strong id="recognizedConfidence"></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- LBPH Info --}}
                    <div class="alert alert-light border mb-3">
                        <div class="row g-2 small">
                            <div class="col-6">
                                <i class="fas fa-chart-line text-primary me-1"></i>
                                <strong>Distance:</strong> <span id="recognizedDistance"></span>
                            </div>
                            <div class="col-6">
                                <i class="fas fa-robot text-primary me-1"></i>
                                <strong>Method:</strong> <span id="recognizedMethod"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Select Schedule --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-calendar me-1"></i> Pilih Jadwal Hari Ini
                        </label>
                        <select class="form-select form-select-lg rounded-3" id="attendanceSchedule" required>
                            <option value="">-- Pilih Jadwal --</option>
                            @foreach($todaySchedules as $sc)
                            <option value="{{ $sc->id }}">
                                {{ $sc->title }} 
                                <small>({{ \Carbon\Carbon::parse($sc->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($sc->end_time)->format('H:i') }})</small>
                            </option>
                            @endforeach
                        </select>
                        @if($todaySchedules->isEmpty())
                        <small class="text-danger">
                            <i class="fas fa-exclamation-circle me-1"></i>
                            Tidak ada jadwal hari ini
                        </small>
                        @endif
                    </div>

                    {{-- Type Selection --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-clipboard-check me-1"></i> Tipe Absensi
                        </label>
                        <select class="form-select form-select-lg rounded-3" id="attendanceType" required>
                            <option value="check_in">Check-In (Masuk)</option>
                            <option value="check_out">Check-Out (Keluar)</option>
                        </select>
                        <small id="attendanceTypeHint" class="text-muted"></small>
                    </div>

                    {{-- Submit Button --}}
                    <div class="d-grid">
                        <button type="button" class="btn btn-success btn-lg rounded-3" onclick="saveAttendance()">
                            <i class="fas fa-save me-1"></i> Simpan Kehadiran
                        </button>
                    </div>

                </div>

            </div>
        </div>

        {{-- Today's Attendance --}}
        <div class="card shadow-sm border-0 rounded-4 mt-3">
            <div class="card-header bg-secondary text-white rounded-top-4">
                <h6 class="m-0"><i class="fas fa-list me-1"></i> Kehadiran Hari Ini</h6>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Nama</th>
                                <th>Jadwal</th>
                                <th class="text-center">Check-In</th>
                                <th class="text-center">Check-Out</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($todayAttendances as $att)
                            <tr>
                                <td class="ps-3">
                                    <strong>{{ $att->user->name }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $att->user->employee_id }}</small>
                                </td>
                                <td>
                                    @if($att->schedule)
                                        <small>{{ $att->schedule->title }}</small>
                                    @else
                                        <small class="text-muted">-</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($att->check_in_time)
                                        <span class="badge bg-success">
                                            {{ \Carbon\Carbon::parse($att->check_in_time)->format('H:i') }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($att->check_out_time)
                                        <span class="badge bg-danger">
                                            {{ \Carbon\Carbon::parse($att->check_out_time)->format('H:i') }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($att->status == 'present')
                                        <span class="badge bg-success">Hadir</span>
                                    @elseif($att->status == 'late')
                                        <span class="badge bg-warning text-dark">Terlambat</span>
                                    @elseif($att->status == 'absent')
                                        <span class="badge bg-danger">Tidak Hadir</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($att->status) }}</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-3 text-muted">
                                    <i class="fas fa-inbox me-1"></i> Belum ada kehadiran hari ini
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
/* ===================== VARIABLES ==================== */
let video = document.getElementById("video");
let canvas = document.getElementById("canvas");
let photoData = null;
let recognized = null;

/* ===================== CAMERA FUNCTIONS ==================== */
async function startCamera() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                width: { ideal: 640 },
                height: { ideal: 480 },
                facingMode: 'user'
            } 
        });
        video.srcObject = stream;
        console.log('✅ Camera started');
    } catch (e) {
        console.error('❌ Camera error:', e);
        Swal.fire({
            icon: "error",
            title: "Akses Kamera Ditolak",
            html: `<p>${e.message}</p><small class="text-muted">Pastikan Anda memberikan izin akses kamera</small>`,
        });
    }
}

function capturePhoto() {
    const ctx = canvas.getContext("2d");
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0);
    photoData = canvas.toDataURL("image/jpeg", 0.9);

    // Update UI
    document.getElementById("previewImage").src = photoData;
    document.getElementById("cameraContainer").style.display = "none";
    document.getElementById("photoPreview").style.display = "block";
    document.getElementById("captureBtn").style.display = "none";
    document.getElementById("retakeBtn").style.display = "block";
    
    // Stop camera
    if (video.srcObject) {
        video.srcObject.getTracks().forEach(track => track.stop());
    }

    console.log('✅ Photo captured');

    // Auto-recognize
    sendToRecognition();
}

function retakePhoto() {
    photoData = null;
    recognized = null;
    
    // Reset UI
    document.getElementById("cameraContainer").style.display = "block";
    document.getElementById("photoPreview").style.display = "none";
    document.getElementById("captureBtn").style.display = "block";
    document.getElementById("retakeBtn").style.display = "none";
    document.getElementById("resultBox").style.display = "none";
    document.getElementById("waitingBox").style.display = "block";
    
    // Reset form
    resetAttendanceForm();
    
    // Restart camera
    startCamera();
    console.log('🔄 Retaking photo');
}

function resetAttendanceForm() {
    const typeSelect = document.getElementById("attendanceType");
    const scheduleSelect = document.getElementById("attendanceSchedule");
    const hintElement = document.getElementById("attendanceTypeHint");
    
    typeSelect.disabled = false;
    typeSelect.value = "check_in";
    scheduleSelect.value = "";
    hintElement.textContent = "";
    hintElement.className = "text-muted";
    
    // Re-enable all options
    const checkInOption = typeSelect.querySelector('option[value="check_in"]');
    const checkOutOption = typeSelect.querySelector('option[value="check_out"]');
    if (checkInOption) checkInOption.disabled = false;
    if (checkOutOption) checkOutOption.disabled = false;
}

function dataURLtoFile(dataurl, filename = "photo.jpg") {
    let arr = dataurl.split(',');
    let mime = arr[0].match(/:(.*?);/)[1];
    let bstr = atob(arr[1]);
    let n = bstr.length;
    let u8arr = new Uint8Array(n);
    while (n--) u8arr[n] = bstr.charCodeAt(n);
    return new File([u8arr], filename, { type: mime });
}

/* ===================== FACE RECOGNITION ==================== */
async function sendToRecognition() {
    console.log('📤 Sending to face recognition API...');

    // Show loading
    Swal.fire({
        title: 'Mengenali Wajah...',
        html: 'Menggunakan LBPH untuk pengenalan wajah...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const fd = new FormData();
    fd.append("photo", dataURLtoFile(photoData));

    try {
        const res = await fetch("{{ route('admin.attendance.face.recognize') }}", {
            method: "POST",
            headers: { 
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Accept": "application/json"
            },
            body: fd
        });

        const data = await res.json();
        console.log('📥 Recognition response:', data);

        Swal.close();

        if (!data.success) {
            recognized = null;
            document.getElementById("resultBox").style.display = "none";
            document.getElementById("waitingBox").style.display = "block";
            
            return Swal.fire({
                icon: "error",
                title: "Wajah Tidak Dikenali",
                text: data.message || "Wajah tidak ditemukan dalam database.",
                footer: '<small>Pastikan Anda sudah registrasi wajah terlebih dahulu</small>'
            });
        }

        // Success - display result
        recognized = data.data;
        displayRecognitionResult();

    } catch (error) {
        console.error('❌ Recognition error:', error);
        Swal.fire({
            icon: "error",
            title: "Kesalahan Sistem",
            text: "Gagal menghubungi server: " + error.message
        });
    }
}

function displayRecognitionResult() {
    console.log('📊 Displaying recognition result:', recognized);
    
    // ✅ SAFE ELEMENT ACCESS
    const waitingBox = document.getElementById("waitingBox");
    const resultBox = document.getElementById("resultBox");
    
    if (!waitingBox || !resultBox) {
        console.error('❌ Required elements not found:', {
            waitingBox: !!waitingBox,
            resultBox: !!resultBox
        });
        
        Swal.fire({
            icon: 'error',
            title: 'Error UI',
            text: 'Element HTML tidak ditemukan. Refresh halaman dan coba lagi.'
        });
        return;
    }
    
    waitingBox.style.display = "none";
    resultBox.style.display = "block";
    
    // ✅ SAFE TEXT SETTER with null checks
    const setTextContent = (id, value, defaultValue = 'N/A') => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value || defaultValue;
            console.log(`✅ Set ${id}:`, value);
        } else {
            console.warn(`⚠️ Element #${id} not found in DOM`);
        }
    };
    
    // Fill user info with safe setter
    setTextContent("recognizedName", recognized.name, 'Unknown User');
    setTextContent("recognizedEmployee", recognized.employee_id, 'N/A');
    setTextContent("recognizedConfidence", (recognized.confidence || 0) + '%', '0%');
    setTextContent("recognizedDistance", recognized.distance, 'N/A');
    setTextContent("recognizedMethod", recognized.method, 'LBPH');
    
    console.log('✅ User recognized:', recognized.name);
}

/* ===================== ATTENDANCE STATUS CHECK ==================== */
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
        console.log('📊 Attendance status:', data);
        
        if (!data.success) {
            console.error("Error checking status:", data);
            return;
        }

        const typeSelect = document.getElementById("attendanceType");
        const hintElement = document.getElementById("attendanceTypeHint");
        const checkInOption = typeSelect.querySelector('option[value="check_in"]');
        const checkOutOption = typeSelect.querySelector('option[value="check_out"]');
        
        // Reset
        checkInOption.disabled = false;
        checkOutOption.disabled = false;
        typeSelect.disabled = false;
        hintElement.className = "text-muted";

        // ✅ SUDAH LENGKAP
        if (data.has_checked_out) {
            typeSelect.disabled = true;
            checkInOption.disabled = true;
            checkOutOption.disabled = true;
            hintElement.innerHTML = `<i class="fas fa-check-circle text-success me-1"></i> Sudah lengkap: Check-in <strong>${data.check_in_time}</strong> | Check-out <strong>${data.check_out_time}</strong>`;
            hintElement.className = "text-success fw-bold d-block mt-1";
            
            Swal.fire({
                icon: 'info',
                title: 'Absensi Lengkap',
                text: 'Anda sudah menyelesaikan check-in dan check-out untuk jadwal ini.',
            });
        }
        // ✅ SUDAH CHECK-IN
        else if (data.has_checked_in && !data.has_checked_out) {
            typeSelect.value = "check_out";
            typeSelect.disabled = false;
            checkInOption.disabled = true;
            checkOutOption.disabled = false;
            
            hintElement.innerHTML = `<i class="fas fa-info-circle text-warning me-1"></i> Sudah check-in pada <strong>${data.check_in_time}</strong>. Silakan check-out.`;
            hintElement.className = "text-warning fw-bold d-block mt-1";
        }
        // ✅ BELUM CHECK-IN
        else {
            typeSelect.value = "check_in";
            checkInOption.disabled = false;
            checkOutOption.disabled = true;
            
            hintElement.innerHTML = `<i class="fas fa-arrow-right text-primary me-1"></i> Silakan check-in terlebih dahulu`;
            hintElement.className = "text-muted d-block mt-1";
        }

    } catch (error) {
        console.error("❌ Error checking status:", error);
    }
}

/* ===================== SCHEDULE CHANGE EVENT ==================== */
document.getElementById("attendanceSchedule").addEventListener('change', async function() {
    if (recognized && this.value) {
        await updateAttendanceTypeOptions(recognized.id, this.value);
    } else {
        resetAttendanceForm();
    }
});

/* ===================== SAVE ATTENDANCE ==================== */
async function saveAttendance() {
    // Validation
    if (!recognized) {
        return Swal.fire("Belum Scan", "Silakan scan wajah terlebih dahulu.", "warning");
    }

    const scheduleId = document.getElementById("attendanceSchedule").value;
    if (!scheduleId) {
        return Swal.fire("Pilih Jadwal", "Silakan pilih jadwal terlebih dahulu.", "warning");
    }

    const typeSelect = document.getElementById("attendanceType");
    const type = typeSelect.value;
    
    if (!type) {
        return Swal.fire("Tipe Tidak Valid", "Silakan pilih tipe absensi.", "warning");
    }
    
    const typeText = type === 'check_in' ? 'Check-In' : 'Check-Out';
    const typeColor = type === 'check_in' ? 'success' : 'danger';

    // Confirmation
    const result = await Swal.fire({
        title: `Konfirmasi ${typeText}`,
        html: `
            <div class="text-start">
                <div class="mb-2">
                    <strong>Nama:</strong> ${recognized.name}<br>
                    <strong>ID Pegawai:</strong> ${recognized.employee_id}
                </div>
                <div class="alert alert-${typeColor} py-2 mb-2">
                    <strong>Tipe:</strong> <span class="badge bg-${typeColor}">${typeText}</span>
                </div>
                <div class="alert alert-light py-2 mb-0 small">
                    <i class="fas fa-robot me-1"></i> 
                    Method: <strong>${recognized.method || 'LBPH'}</strong> | 
                    Distance: <strong>${recognized.distance}</strong> | 
                    Confidence: <strong>${recognized.confidence}%</strong>
                </div>
            </div>
        `,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: `<i class="fas fa-check me-1"></i> Ya, Simpan!`,
        cancelButtonText: `<i class="fas fa-times me-1"></i> Batal`,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d'
    });

    if (!result.isConfirmed) return;

    // Show loading
    Swal.fire({
        title: 'Menyimpan...',
        html: `Sedang memproses ${typeText}...`,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Prepare data
    const fd = new FormData();
    fd.append("recognized_user_id", recognized.id);
    fd.append("schedule_id", scheduleId);
    fd.append("type", type);
    fd.append("photo", photoData);

    try {
        const r = await fetch("{{ route('admin.attendance.face.saveAttendance') }}", {
            method: "POST",
            headers: { 
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Accept": "application/json"
            },
            body: fd
        });

        const d = await r.json();
        console.log('💾 Save response:', d);
        
        if (d.success) {
            await Swal.fire({
                icon: "success",
                title: "Berhasil!",
                html: d.message,
                timer: 3000,
                showConfirmButton: true
            });
            
            location.reload();
        } else {
            Swal.fire({
                icon: "error",
                title: "Gagal",
                text: d.message
            });
        }
    } catch (error) {
        console.error('❌ Save error:', error);
        Swal.fire({
            icon: "error",
            title: "Kesalahan Sistem",
            text: "Terjadi kesalahan: " + error.message
        });
    }
}

/* ===================== INITIALIZATION ==================== */
window.onload = function() {
    console.log('🚀 Face Attendance Page Loaded');
    startCamera();
};

// Cleanup
window.addEventListener('beforeunload', () => {
    if (video.srcObject) {
        video.srcObject.getTracks().forEach(track => track.stop());
    }
});
</script>
@endpush

@push('styles')
<style>
video {
    max-height: 350px;
    object-fit: cover;
    background: #000;
}

#previewImage {
    max-height: 350px;
    object-fit: cover;
}

.form-select:focus,
.btn:focus {
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

#resultBox {
    animation: fadeIn 0.4s ease-in;
}

@keyframes fadeIn {
    from { 
        opacity: 0; 
        transform: translateY(-15px); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0); 
    }
}

.table tbody tr {
    transition: background-color 0.2s ease;
}

.table tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}
</style>
@endpush