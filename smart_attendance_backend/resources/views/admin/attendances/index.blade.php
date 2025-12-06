{{-- ========================================================= --}}
{{-- File: resources/views/admin/attendances/index.blade.php --}}
{{-- Kelola Kehadiran (Simple • Modern • Clean • With Photo Preview) --}}
{{-- ========================================================= --}}
@extends('layouts.app')
@section('title', 'Kelola Kehadiran')

@section('content')

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-semibold m-0">Kelola Kehadiran</h4>
    <a href="{{ route('admin.reports.attendance') }}" class="btn btn-primary rounded-3">
        <i class="fas fa-chart-bar me-1"></i> Lihat Laporan
    </a>
</div>

{{-- FILTER --}}
<div class="card border-0 shadow-sm rounded-3 mb-3">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label small fw-medium">Tanggal</label>
                <input type="date" id="filterDate" class="form-control form-control-modern"
                       value="{{ request('date', date('Y-m-d')) }}">
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-medium">Status</label>
                <select id="filterStatus" class="form-select form-select-modern">
                    <option value="">Semua Status</option>
                    <option value="present" {{ request('status')=='present'?'selected':'' }}>Hadir</option>
                    <option value="late"    {{ request('status')=='late'?'selected':'' }}>Terlambat</option>
                    <option value="absent"  {{ request('status')=='absent'?'selected':'' }}>Alfa</option>
                    <option value="excused" {{ request('status')=='excused'?'selected':'' }}>Izin</option>
                    <option value="leave"   {{ request('status')=='leave'?'selected':'' }}>Cuti</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-medium">User</label>
                <select id="filterUser" class="form-select form-select-modern">
                    <option value="">Semua User</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                            {{ $u->name }} ({{ $u->employee_id }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-3">
            <button class="btn btn-outline-secondary rounded-3 me-2" onclick="clearFilter()">Reset</button>
            <button class="btn btn-primary rounded-3" onclick="applyFilter()">
                <i class="fas fa-filter me-1"></i> Filter
            </button>
        </div>
    </div>
</div>

{{-- STATISTIK --}}
<div class="row g-3 mb-3">
    @php 
        $cards = [
            ['val'=>$todayStats['present'], 'label'=>'Hadir',       'bg'=>'#28a745'],
            ['val'=>$todayStats['late'],    'label'=>'Terlambat',   'bg'=>'#ffc107'],
            ['val'=>$todayStats['excused'], 'label'=>'Izin',        'bg'=>'#17a2b8'],
            ['val'=>$todayStats['absent'],  'label'=>'Tidak Hadir', 'bg'=>'#dc3545'],
        ];
    @endphp

    @foreach($cards as $c)
    <div class="col-6 col-md-3">
        <div class="card shadow-sm rounded-3">
            <div class="card-body text-center py-3">
                <h3 class="fw-bold" style="color:{{ $c['bg'] }}">{{ $c['val'] }}</h3>
                <small class="text-muted">{{ $c['label'] }}</small>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- TABLE --}}
<div class="card shadow-sm border-0 rounded-3">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Daftar Kehadiran</span>
        <span class="badge rounded-pill px-3" style="background:linear-gradient(90deg,#667eea,#764ba2);">
            {{ $attendances->total() }}
        </span>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>User</th>
                        <th>Check-In</th>
                        <th>Check-Out</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($attendances as $i => $att)
                    <tr>
                        <td>{{ $attendances->firstItem() + $i }}</td>
                        <td>{{ $att->date->format('d/m/Y') }}</td>

                        {{-- USER + AVATAR PREVIEW --}}
                        <td>
                            <div class="d-flex align-items-center">
                                @if($att->user->photo)
                                    <img src="{{ Storage::url($att->user->photo) }}"
                                         width="40" height="40"
                                         class="rounded-circle me-2 avatar-preview"
                                         data-photo="{{ Storage::url($att->user->photo) }}"
                                         style="object-fit:cover; cursor:pointer;">
                                @else
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-2"
                                         style="width:40px;height:40px;
                                         background:linear-gradient(90deg,#667eea,#764ba2);
                                         color:white;font-weight:bold;">
                                        {{ strtoupper(substr($att->user->name,0,1)) }}
                                    </div>
                                @endif
                                <div>
                                    <strong>{{ $att->user->name }}</strong><br>
                                    <small class="text-muted">{{ $att->user->employee_id }}</small>
                                </div>
                            </div>
                        </td>

                        {{-- CHECK-IN PHOTO --}}
                        <td>
                            @if($att->check_in_time)
                                <span class="badge checkin-photo"
                                      data-photo="{{ $att->check_in_photo ? Storage::url($att->check_in_photo) : '' }}"
                                      style="background:#e4f7ec;color:#176b2b;cursor:pointer;">
                                      {{ $att->check_in_time }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>

                        {{-- CHECK-OUT PHOTO --}}
                        <td>
                            @if($att->check_out_time)
                                <span class="badge checkout-photo"
                                      data-photo="{{ $att->check_out_photo ? Storage::url($att->check_out_photo) : '' }}"
                                      style="background:#eaf6ff;color:#0b4a60;cursor:pointer;">
                                      {{ $att->check_out_time }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>

                        {{-- STATUS --}}
                        <td>
                            <select class="form-select form-select-sm rounded-3 status-select"
                                    data-id="{{ $att->id }}"
                                    data-original="{{ $att->status }}"
                                    data-name="{{ $att->user->name }}"
                                    data-employee="{{ $att->user->employee_id }}"
                                    data-date="{{ $att->date->format('d/m/Y') }}">
                                <option value="present" {{ $att->status=='present'?'selected':'' }}>Hadir</option>
                                <option value="late"    {{ $att->status=='late'?'selected':'' }}>Terlambat</option>
                                <option value="absent"  {{ $att->status=='absent'?'selected':'' }}>Alfa</option>
                                <option value="excused" {{ $att->status=='excused'?'selected':'' }}>Izin</option>
                                <option value="leave"   {{ $att->status=='leave'?'selected':'' }}>Cuti</option>
                            </select>
                        </td>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="fas fa-inbox text-muted fa-2x mb-2"></i>
                            <p class="text-muted mb-0">Tidak ada data kehadiran</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3">
            {{ $attendances->links() }}
        </div>
    </div>
</div>

{{-- PHOTO PREVIEW MODAL --}}
<div class="modal fade" id="photoModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-body p-0">
        <img id="photoModalImg" class="img-fluid w-100 rounded">
      </div>
      <div class="p-2 text-end">
        <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

@endsection



@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {

    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    /* ------------------------------- */
    /* FILTER BUTTON                  */
    /* ------------------------------- */
    window.applyFilter = () => {
        const params = new URLSearchParams();

        const date = filterDate.value;
        const stat = filterStatus.value;
        const user = filterUser.value;

        if(date) params.append("date", date);
        if(stat) params.append("status", stat);
        if(user) params.append("user_id", user);

        window.location.href = "{{ route('admin.attendances.index') }}?" + params.toString();
    };

    window.clearFilter = () =>
        window.location.href = "{{ route('admin.attendances.index') }}";


    /* ------------------------------- */
    /* PREVIEW FOTO (Avatar + CI + CO) */
    /* ------------------------------- */
    document.addEventListener("click", (e) => {

        const target = e.target.closest(".avatar-preview, .checkin-photo, .checkout-photo");
        if (!target) return;

        const photo = target.dataset.photo;

        if (!photo) {
            Swal.fire({
                icon: "info",
                title: "Tidak ada foto",
                text: "Foto tidak tersedia untuk data ini.",
                confirmButtonColor: "#667eea"
            });
            return;
        }

        document.getElementById("photoModalImg").src = photo;
        new bootstrap.Modal(document.getElementById("photoModal")).show();
    });


    /* ------------------------------- */
    /* CATATAN */
    /* ------------------------------- */
    window.viewNotes = (notes) => {
        Swal.fire({
            title: "Catatan",
            html: `<div class="text-start">${notes}</div>`,
            confirmButtonText: "Tutup",
            confirmButtonColor: "#667eea"
        });
    };


    /* ------------------------------- */
    /* HISTORI */
    /* ------------------------------- */
    window.viewHistory = (id) => {

        Swal.fire({
            title: 'Memuat...',
            html: '<div class="spinner-border text-primary"></div>',
            showConfirmButton: false,
            allowOutsideClick: false
        });

        fetch(`/admin/attendances/${id}/history`)
            .then(res => res.json())
            .then(data => {

                let html = "<div class='list-group text-start'>";

                if (data.length) {
                    data.forEach(log => {
                        html += `
                            <div class="list-group-item">
                                <strong>${log.action ?? "-"}</strong><br>
                                <small class="text-muted">${log.created_at}</small>
                                <div>${log.description ?? ""}</div>
                            </div>
                        `;
                    });
                } else {
                    html = "<p class='text-muted text-center py-3'>Tidak ada histori</p>";
                }

                Swal.fire({
                    title: "Histori Kehadiran",
                    html,
                    width: "650px",
                    confirmButtonText: "Tutup",
                    confirmButtonColor: "#667eea"
                });
            });
    };


    /* ------------------------------- */
    /* UBAH STATUS */
    /* ------------------------------- */
    document.addEventListener("change", (e) => {
        const sel = e.target.closest(".status-select");
        if (!sel) return;

        const id = sel.dataset.id;
        const oldStatus = sel.dataset.original;
        const newStatus = sel.value;

        Swal.fire({
            title: "Ubah Status?",
            html: `
                <div class="text-start">
                    <strong>${sel.dataset.name}</strong> (${sel.dataset.employee})<br>
                    <small class="text-muted">${sel.dataset.date}</small>
                    <hr>
                    Status baru:
                    <span class="badge" style="background:#667eea">${newStatus}</span>
                </div>`,
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#667eea",
            cancelButtonText: "Batal"
        }).then(result => {
            if (!result.isConfirmed) {
                sel.value = oldStatus;
                return;
            }

            Swal.fire({ title:'Menyimpan...', showConfirmButton:false, didOpen:()=>Swal.showLoading() });

            fetch(`/admin/attendances/${id}/status`, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrf
                },
                body: JSON.stringify({ status: newStatus })
            })
            .then(res => res.json())
            .then(res => {
                if (!res.success) throw res;

                Swal.fire({
                    icon: "success",
                    title: "Status Berhasil Disimpan",
                    timer: 900,
                    showConfirmButton: false
                });

                sel.dataset.original = newStatus;

                setTimeout(() => location.reload(), 950);
            })
            .catch(() => {
                Swal.fire({
                    icon: "error",
                    title: "Gagal Mengubah Status",
                    text: "Terjadi kesalahan pada server."
                });
                sel.value = oldStatus;
            });
        });
    });

});
</script>

<style>
.form-control-modern,
.form-select-modern {
    border-radius: 10px;
    padding: 8px 12px;
    border: 1px solid rgba(102,126,234,0.25);
}

.avatar-preview:hover,
.checkin-photo:hover,
.checkout-photo:hover {
    transform: scale(1.03);
    transition: .12s ease;
    opacity: .9;
}

.table th, .table td { vertical-align: middle; }

@media(max-width: 720px) {
    .main-content { padding: 1rem; }
}
</style>
@endpush
