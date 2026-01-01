@extends('layouts.app')
@section('title', 'Registrasi Wajah')

@section('content')

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
    <div>
        <h4 class="fw-semibold text-dark m-0">
            <i class="fas fa-user-plus me-2"></i> Registrasi Wajah Pengguna
        </h4>
        <p class="text-muted small mb-0">Daftarkan wajah pengguna untuk sistem pengenalan wajah (LBPH)</p>
    </div>
    <a href="{{ route('admin.attendance.face.record') }}" class="btn btn-outline-primary">
        <i class="fas fa-fingerprint me-1"></i> Ke Rekam Kehadiran
    </a>
</div>

<div class="row">

    {{-- LEFT: CAMERA --}}
    <div class="col-md-5">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-primary text-white rounded-top-4">
                <h6 class="m-0"><i class="fas fa-camera me-1"></i> Ambil Foto Wajah</h6>
            </div>

            <div class="card-body">

                {{-- Camera Live --}}
                <div id="cameraContainer">
                    <video id="video" autoplay class="w-100 rounded-3 border"></video>
                    <canvas id="canvas" style="display:none;"></canvas>
                </div>

                {{-- Preview --}}
                <div id="photoPreview" class="mt-2" style="display:none;">
                    <img id="previewImage" class="w-100 rounded-3 shadow-sm border">
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
                    <strong><i class="fas fa-info-circle me-1"></i> Tips Pengambilan Foto:</strong>
                    <ul class="mb-0 mt-2 ps-3 small">
                        <li>Pastikan pencahayaan <strong>cukup terang</strong></li>
                        <li>Wajah menghadap <strong>lurus ke kamera</strong></li>
                        <li>Jangan gunakan <strong>masker/kacamata hitam</strong></li>
                        <li>Posisi wajah di <strong>tengah frame</strong></li>
                        <li>Jarak ideal: <strong>30-50 cm</strong> dari kamera</li>
                    </ul>
                </div>

            </div>
        </div>
    </div>

    {{-- RIGHT: FORM --}}
    <div class="col-md-7">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-success text-white rounded-top-4">
                <h6 class="m-0"><i class="fas fa-user-check me-1"></i> Formulir Registrasi</h6>
            </div>

            <div class="card-body">

                {{-- Info LBPH --}}
                <div class="alert alert-primary border-0">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-robot fa-2x me-3 mt-1"></i>
                        <div>
                            <h6 class="mb-1">Sistem Pengenalan Wajah LBPH</h6>
                            <p class="mb-1 small">
                                Sistem menggunakan <strong>LBPH (Local Binary Patterns Histograms)</strong> 
                                untuk pengenalan wajah yang akurat dan cepat.
                            </p>
                            <ul class="small mb-0 ps-3">
                                <li>Setiap user bisa memiliki <strong>maksimal 5 sample wajah</strong></li>
                                <li>Foto pertama akan otomatis menjadi <strong>primary face</strong></li>
                                <li>Semakin banyak sample, semakin akurat pengenalan</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <form id="faceRegisterForm" onsubmit="submitRegister(event)">
                    
                    {{-- Pilih User --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-user me-1"></i> Pilih Pengguna
                        </label>
                        <select class="form-select form-select-lg rounded-3" id="registerUserId" required>
                            <option value="">-- Pilih Pengguna --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" 
                                        data-name="{{ $user->name }}" 
                                        data-employee="{{ $user->employee_id }}">
                                    {{ $user->name }} ({{ $user->employee_id }})
                                    @if($user->department) - {{ $user->department }}@endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Selected User Info --}}
                    <div id="selectedUserInfo" style="display:none;" class="alert alert-light border mb-3">
                        <h6 class="mb-2">Pengguna Dipilih:</h6>
                        <div class="row g-2 small">
                            <div class="col-6">
                                <strong>Nama:</strong><br>
                                <span id="infoName"></span>
                            </div>
                            <div class="col-6">
                                <strong>ID Pegawai:</strong><br>
                                <span id="infoEmployeeId"></span>
                            </div>
                        </div>
                        <div class="mt-2" id="sampleCountInfo"></div>
                    </div>

                    {{-- Instructions --}}
                    <div class="alert alert-warning border-0 mb-3">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <strong>Penting:</strong> Pastikan Anda sudah <strong>mengambil foto</strong> 
                        dan <strong>memilih pengguna</strong> sebelum menyimpan.
                    </div>

                    {{-- Submit Button --}}
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg rounded-3" id="submitBtn">
                            <i class="fas fa-save me-1"></i> Registrasikan Wajah (LBPH)
                        </button>
                    </div>

                </form>

            </div>
        </div>

        {{-- Registered Faces Table --}}
        <div class="card shadow-sm border-0 rounded-4 mt-3">
            <div class="card-header bg-secondary text-white rounded-top-4">
                <h6 class="m-0"><i class="fas fa-list me-1"></i> Wajah Terdaftar</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>User</th>
                                <th>Sample</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Ini bisa diisi dari controller jika diperlukan --}}
                            <tr>
                                <td colspan="4" class="text-center text-muted">
                                    Reload halaman untuk melihat data terbaru
                                </td>
                            </tr>
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
/* ===================== CAMERA ==================== */
let video = document.getElementById("video");
let canvas = document.getElementById("canvas");
let photoData = null;

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
        
        console.log('✅ Camera started successfully');
    } catch (e) {
        console.error('❌ Camera error:', e);
        Swal.fire({
            icon: "error",
            title: "Akses Kamera Ditolak",
            text: e.message,
            footer: "Pastikan Anda memberikan izin akses kamera"
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
    
    // Stop camera stream
    if (video.srcObject) {
        video.srcObject.getTracks().forEach(track => track.stop());
    }

    console.log('✅ Photo captured');
}

function retakePhoto() {
    photoData = null;
    
    // Update UI
    document.getElementById("cameraContainer").style.display = "block";
    document.getElementById("photoPreview").style.display = "none";
    document.getElementById("captureBtn").style.display = "block";
    document.getElementById("retakeBtn").style.display = "none";
    
    // Restart camera
    startCamera();
    
    console.log('🔄 Retaking photo');
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

/* ===================== USER SELECTION ==================== */
document.getElementById('registerUserId').addEventListener('change', async function() {
    const selectedOption = this.options[this.selectedIndex];
    const userId = this.value;
    
    if (!userId) {
        document.getElementById('selectedUserInfo').style.display = 'none';
        return;
    }
    
    // Show selected user info
    document.getElementById('infoName').textContent = selectedOption.dataset.name;
    document.getElementById('infoEmployeeId').textContent = selectedOption.dataset.employee;
    document.getElementById('selectedUserInfo').style.display = 'block';
    
    // ✅ TODO: Check existing samples count via AJAX (optional)
    // For now, just show placeholder
    document.getElementById('sampleCountInfo').innerHTML = 
        '<small class="text-muted"><i class="fas fa-info-circle"></i> Loading sample count...</small>';
    
    console.log('👤 User selected:', selectedOption.dataset.name);
});

/* ===================== SUBMIT REGISTER ==================== */
async function submitRegister(e) {
    e.preventDefault();

    // ✅ ENHANCED VALIDATION
    console.log('🚀 Submit register triggered');
    console.log('📸 Photo data exists:', !!photoData);
    console.log('📸 Photo data length:', photoData ? photoData.length : 0);

    // Validation: Photo
    if (!photoData) {
        console.error('❌ Photo data is null or empty');
        return Swal.fire({
            icon: "warning",
            title: "Foto Belum Diambil",
            text: "Silakan ambil foto wajah terlebih dahulu dengan klik tombol 'Ambil Foto'.",
            confirmButtonText: "OK"
        });
    }

    // Validation: User
    const userId = document.getElementById("registerUserId").value;
    console.log('👤 Selected user ID:', userId);
    
    if (!userId) {
        console.error('❌ User not selected');
        return Swal.fire({
            icon: "warning",
            title: "User Belum Dipilih",
            text: "Silakan pilih pengguna terlebih dahulu.",
            confirmButtonText: "OK"
        });
    }

    const userSelect = document.getElementById("registerUserId");
    const selectedOption = userSelect.options[userSelect.selectedIndex];
    const userName = selectedOption.dataset.name;
    const employeeId = selectedOption.dataset.employee;

    console.log('👤 User details:', { userId, userName, employeeId });

    // Confirmation
    const result = await Swal.fire({
        title: "Konfirmasi Registrasi",
        html: `
            <div class="text-start">
                <p>Anda akan mendaftarkan wajah untuk:</p>
                <ul>
                    <li><strong>Nama:</strong> ${userName}</li>
                    <li><strong>ID Pegawai:</strong> ${employeeId}</li>
                </ul>
                <div class="alert alert-info small mb-0">
                    <i class="fas fa-robot me-1"></i> 
                    Sistem akan menggunakan <strong>LBPH (Local Binary Patterns Histograms)</strong> 
                    untuk mengenkode wajah.
                </div>
            </div>
        `,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-check me-1"></i> Ya, Registrasikan!',
        cancelButtonText: '<i class="fas fa-times me-1"></i> Batal',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d'
    });

    if (!result.isConfirmed) {
        console.log('❌ User cancelled registration');
        return;
    }

    // Show loading
    Swal.fire({
        title: 'Memproses Registrasi...',
        html: `
            <div class="mb-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            <p class="mb-1">Sedang mengenkode wajah menggunakan LBPH...</p>
            <small class="text-muted">Proses ini membutuhkan waktu beberapa detik</small>
        `,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false
    });

    // Prepare form data
    const fd = new FormData();
    fd.append("user_id", userId);
    
    // ✅ CONVERT base64 to File
    const photoFile = dataURLtoFile(photoData, `face_${userId}_${Date.now()}.jpg`);
    fd.append("photo", photoFile);

    console.log('📦 FormData prepared:', {
        user_id: userId,
        photo_name: photoFile.name,
        photo_size: photoFile.size,
        photo_type: photoFile.type
    });

    try {
        console.log('📤 Sending registration request to:', "{{ route('admin.attendance.face.register') }}");
        
        const res = await fetch("{{ route('admin.attendance.face.register') }}", {
            method: "POST",
            headers: { 
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Accept": "application/json"
            },
            body: fd
        });

        console.log('📥 Response status:', res.status);
        console.log('📥 Response ok:', res.ok);

        // ✅ HANDLE NON-JSON RESPONSE
        const contentType = res.headers.get("content-type");
        console.log('📥 Content-Type:', contentType);

        let data;
        if (contentType && contentType.includes("application/json")) {
            data = await res.json();
        } else {
            const text = await res.text();
            console.error('❌ Non-JSON response:', text);
            throw new Error('Server returned non-JSON response. Check Laravel logs.');
        }
        
        console.log('📥 Response data:', data);

        if (data.success) {
            console.log('✅ Registration successful!');
            
            await Swal.fire({
                icon: "success",
                title: "Registrasi Berhasil!",
                html: `
                    <div class="text-start">
                        <p>${data.message || "Wajah berhasil didaftarkan menggunakan LBPH."}</p>
                        ${data.data ? `
                            <ul class="small mb-0">
                                <li>Sample Number: <strong>#${data.data.sample_number || 'N/A'}</strong></li>
                                <li>Method: <strong>${data.data.method || 'LBPH'}</strong></li>
                                <li>Quality Score: <strong>${data.data.quality_score || 'N/A'}</strong></li>
                                ${data.data.embedding_size ? `<li>Embedding Size: <strong>${data.data.embedding_size.toLocaleString()} values</strong></li>` : ''}
                            </ul>
                        ` : ''}
                    </div>
                `,
                confirmButtonText: "OK",
                timer: 5000
            });
            
            // Reload halaman
            console.log('🔄 Reloading page...');
            location.reload();
        } else {
            console.error('❌ Registration failed:', data.message);
            
            Swal.fire({
                icon: "error",
                title: "Registrasi Gagal",
                html: `
                    <p>${data.message || "Terjadi kesalahan saat mendaftarkan wajah."}</p>
                    ${data.error ? `<small class="text-muted">Error: ${data.error}</small>` : ''}
                `,
                confirmButtonText: "OK"
            });
        }
    } catch (error) {
        console.error('❌ Registration error:', error);
        console.error('❌ Error stack:', error.stack);
        
        Swal.fire({
            icon: "error",
            title: "Kesalahan Sistem",
            html: `
                <p>Terjadi kesalahan saat menghubungi server:</p>
                <div class="text-start bg-light p-2 rounded small">
                    <strong>Error:</strong> ${error.message}<br>
                    <strong>Kemungkinan penyebab:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Python API tidak berjalan (cek http://127.0.0.1:8001/health)</li>
                        <li>Koneksi database bermasalah</li>
                        <li>File size terlalu besar</li>
                    </ul>
                </div>
            `,
            confirmButtonText: "OK",
            footer: '<a href="http://127.0.0.1:8001/health" target="_blank">Test Python API</a>'
        });
    }
}

/* ===================== INIT ==================== */
window.onload = function() {
    console.log('🚀 Face Registration Page Loaded');
    startCamera();
};

// Stop camera on page unload
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
    max-height: 400px;
    object-fit: cover;
    background: #000;
}

#previewImage {
    max-height: 400px;
    object-fit: cover;
}

.form-select:focus,
.btn:focus {
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

#selectedUserInfo {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
@endpush