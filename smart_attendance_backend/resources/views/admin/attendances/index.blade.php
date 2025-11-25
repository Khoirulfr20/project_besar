{{-- ========================================================= --}}
{{-- File: resources/views/admin/attendances/index.blade.php --}}
{{-- Kelola Kehadiran (Modern, Simple, Clean UI) --}}
{{-- ========================================================= --}}
@extends('layouts.app')
@section('title', 'Kelola Kehadiran')

@section('content')

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
    <h4 class="fw-semibold m-0">Kelola Kehadiran</h4>
    <a href="{{ route('admin.reports.attendance') }}" class="btn btn-primary rounded-3">
        <i class="fas fa-chart-bar me-1"></i> Lihat Laporan
    </a>
</div>

{{-- FILTER (Sederhana & To the point) --}}
<div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body">

        <div class="row g-3">

            {{-- Tanggal --}}
            <div class="col-md-4">
                <label class="form-label fw-medium">Tanggal</label>
                <input type="date" id="filterDate" 
                       class="form-control form-control-modern" 
                       value="{{ request('date', date('Y-m-d')) }}">
            </div>

            {{-- Status --}}
            <div class="col-md-4">
                <label class="form-label fw-medium">Status</label>
                <select id="filterStatus" class="form-select form-select-modern">
                    <option value="">Semua Status</option>
                    <option value="present">Hadir</option>
                    <option value="late">Terlambat</option>
                    <option value="absent">Alfa</option>
                    <option value="excused">Izin</option>
                    <option value="leave">Cuti</option>
                </select>
            </div>

            {{-- USER (Pengganti Departemen) --}}
            <div class="col-md-4">
                <label class="form-label fw-medium">User</label>
                <select id="filterUser" class="form-select form-select-modern">
                    <option value="">Semua User</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->employee_id }})</option>
                    @endforeach
                </select>
            </div>

        </div>

        <div class="text-end mt-3">
            <button class="btn btn-primary rounded-3 px-4" onclick="applyFilter()">
                <i class="fas fa-filter me-1"></i> Filter
            </button>
        </div>

    </div>
</div>

{{-- STATISTIK HARI INI --}}
<div class="row g-3 mb-3">

    @php 
        $cards = [
            ['val'=>$todayStats['present'], 'label'=>'Hadir',       'bg'=>'success'],
            ['val'=>$todayStats['late'],    'label'=>'Terlambat',   'bg'=>'warning'],
            ['val'=>$todayStats['excused'], 'label'=>'Izin',        'bg'=>'info'],
            ['val'=>$todayStats['absent'],  'label'=>'Tidak Hadir', 'bg'=>'danger'],
        ];
    @endphp

    @foreach($cards as $c)
    <div class="col-md-3">
        <div class="card text-white bg-{{ $c['bg'] }} rounded-4 shadow-sm">
            <div class="card-body text-center py-3">
                <h3 class="mb-0 fw-bold">{{ $c['val'] }}</h3>
                <small>{{ $c['label'] }}</small>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- TABLE --}}
<div class="card shadow-sm border-0 rounded-4">
    <div class="card-header bg-light rounded-top-4 py-3 d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Daftar Kehadiran</span>
        <span class="badge bg-primary rounded-pill">{{ $attendances->total() }}</span>
    </div>

    <div class="card-body">

        <div class="table-responsive">
            <table id="attendanceTable" class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>User</th>
                        <th>Check-In</th>
                        <th>Check-Out</th>
                        <th>Durasi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($attendances as $i => $att)
                    <tr>
                        {{-- No --}}
                        <td>{{ $attendances->firstItem() + $i }}</td>

                        {{-- Tanggal --}}
                        <td>{{ $att->date->format('d/m/Y') }}</td>

                        {{-- USER --}}
                        <td>
                            <div class="d-flex align-items-center">

                                {{-- Avatar --}}
                                @if($att->user->photo)
                                    <img src="{{ Storage::url($att->user->photo) }}" 
                                         class="rounded-circle me-2" width="32" height="32">
                                @else
                                    <div class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center me-2" 
                                         style="width:32px; height:32px;">
                                         {{ substr($att->user->name,0,1) }}
                                    </div>
                                @endif

                                <div class="lh-sm">
                                    <strong>{{ $att->user->name }}</strong><br>
                                    <small class="text-muted">{{ $att->user->employee_id }}</small>
                                </div>

                            </div>
                        </td>

                        {{-- Check-In --}}
                        <td>
                            @if($att->check_in_time)
                                <span class="badge bg-success">{{ $att->check_in_time }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>

                        {{-- Check-Out --}}
                        <td>
                            @if($att->check_out_time)
                                <span class="badge bg-info">{{ $att->check_out_time }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>

                        {{-- Durasi --}}
                        <td>
                            @if($att->work_duration)
                                {{ floor($att->work_duration / 60) }}j {{ $att->work_duration % 60 }}m
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>

                        {{-- STATUS (Editable) --}}
                        <td>
                            <select class="form-select form-select-sm rounded-3 status-select"
                                    data-id="{{ $att->id }}"
                                    data-original="{{ $att->status }}"
                                    data-name="{{ $att->user->name }}"
                                    data-employee-id="{{ $att->user->employee_id }}"
                                    data-date="{{ $att->date->format('d/m/Y') }}">
                                <option value="present" {{ $att->status=='present'?'selected':'' }}>Hadir</option>
                                <option value="late"    {{ $att->status=='late'?'selected':'' }}>Terlambat</option>
                                <option value="absent"  {{ $att->status=='absent'?'selected':'' }}>Alfa</option>
                                <option value="excused" {{ $att->status=='excused'?'selected':'' }}>Izin</option>
                                <option value="leave"   {{ $att->status=='leave'?'selected':'' }}>Cuti</option>
                            </select>
                        </td>

                        {{-- AKSI --}}
                        <td>
                            <button class="btn btn-sm btn-outline-primary rounded-3" onclick="viewHistory({{ $att->id }})">
                                <i class="fas fa-history"></i>
                            </button>
                            @if($att->notes)
                            <button class="btn btn-sm btn-outline-secondary rounded-3" onclick="viewNotes(`{{ addslashes($att->notes) }}`)">
                                <i class="fas fa-sticky-note"></i>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted d-block mb-2"></i>
                            <h6 class="text-muted">Tidak ada data kehadiran</h6>
                        </td>
                    </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        <div class="mt-3">
            {{ $attendances->links() }}
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// ====================================================
// FILTER
// ====================================================
function applyFilter() {
    const date  = $('#filterDate').val();
    const stat  = $('#filterStatus').val();
    const user  = $('#filterUser').val();

    let url = '{{ route("admin.attendances.index") }}?';

    if (date) url += `date=${date}&`;
    if (stat) url += `status=${stat}&`;
    if (user) url += `user=${user}`;

    window.location.href = url;
}

// ====================================================
// POPUP NOTES
// ====================================================
function viewNotes(notes) {
    Swal.fire({
        title: 'Catatan',
        html: `<div class='p-3 bg-light rounded text-start'>${notes}</div>`,
        confirmButtonText: 'Tutup',
        confirmButtonColor: '#667eea'
    });
}

// ====================================================
// HISTORY
// ====================================================
function viewHistory(id) {
    Swal.fire({
        title: 'Memuat...',
        html: '<div class="spinner-border text-primary"></div>',
        showConfirmButton: false
    });

    $.get(`/admin/attendances/${id}/history`, function(res) {

        let html = "<div class='list-group text-start'>";

        if (res.length > 0) {
            res.forEach(log => {
                html += `
                    <div class="list-group-item">
                        <strong>${log.action}</strong>
                        <p class="mb-1">${log.description ?? ''}</p>
                        <small class="text-muted">${log.created_at}</small>
                    </div>
                `;
            });
        } else {
            html = "<p class='text-muted text-center py-3'>Tidak ada histori</p>";
        }

        Swal.fire({
            title: 'Histori Kehadiran',
            html,
            width: '600px',
            confirmButtonText: 'Tutup',
            confirmButtonColor: '#667eea'
        });

    });
}

// ====================================================
// PERUBAHAN STATUS
// ====================================================
$('.status-select').change(function () {

    const el = $(this);
    const id = el.data('id');
    const newStatus = el.val();
    const oldStatus = el.data('original');

    const name = el.data('name');
    const emp  = el.data('employee-id');
    const date = el.data('date');

    Swal.fire({
        title: 'Ubah Status?',
        html: `
            <div class='text-start'>
                <strong>${name}</strong> (${emp}) <br>
                <small class='text-muted'>${date}</small>

                <hr>
                <p>Status baru: <span class='badge bg-primary'>${newStatus}</span></p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, ubah',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#667eea',
        cancelButtonColor: '#6c757d'
    })
    .then(res => {

        if (!res.isConfirmed) {
            el.val(oldStatus);
            return;
        }

        Swal.fire({
            title: 'Menyimpan...',
            showConfirmButton: false,
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        $.ajax({
            url: `/admin/attendances/${id}/status`,
            method: 'PUT',
            data: {
                status: newStatus,
                _token: '{{ csrf_token() }}'
            },
            success: function () {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    timer: 1200,
                    showConfirmButton: false
                });
                el.data('original', newStatus);
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Tidak dapat mengubah status',
                    confirmButtonColor: '#d33'
                });
                el.val(oldStatus);
            }
        });

    });

});
</script>

<style>
.form-control-modern,
.form-select-modern {
    border-radius: 12px;
    padding: 10px;
}
.status-select:hover {
    box-shadow: 0 0 0 .15rem rgba(102,126,234,.25);
}
</style>
@endpush
