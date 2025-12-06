{{-- =============================================================== --}}
{{-- File: resources/views/admin/history/index.blade.php --}}
{{-- Versi Simple • Clean UI • Tema Biru-Ungu --}}
{{-- =============================================================== --}}

@extends('layouts.app')
@section('title', 'Histori Kehadiran')

@section('content')

{{-- Page Title --}}
<div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
    <h4 class="fw-semibold m-0">Histori Kehadiran</h4>
</div>

{{-- FILTER CARD --}}
<div class="card shadow-sm border-0 rounded-4 mb-4">
    <div class="card-body">

        <form id="filterForm" method="GET" class="row g-3">

            {{-- Tanggal Mulai --}}
            <div class="col-md-3">
                <label class="form-label fw-medium">Tanggal Mulai</label>
                <input type="date" name="start_date" id="start_date"
                       class="form-control rounded-3"
                       value="{{ request('start_date') }}">
            </div>

            {{-- Tanggal Akhir --}}
            <div class="col-md-3">
                <label class="form-label fw-medium">Tanggal Akhir</label>
                <input type="date" name="end_date" id="end_date"
                       class="form-control rounded-3"
                       value="{{ request('end_date') }}">
            </div>

            {{-- USER FILTER (Ganti Departemen) --}}
            <div class="col-md-3">
                <label class="form-label fw-medium">User</label>
                <select name="user_id" id="user_id" class="form-select rounded-3">
                    <option value="">Semua User</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                            {{ $u->name }} ({{ $u->employee_id }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Status --}}
            <div class="col-md-3">
                <label class="form-label fw-medium">Status</label>
                <select name="status" id="status" class="form-select rounded-3">
                    <option value="">Semua Status</option>
                    <option value="present"  {{ request('status')=='present'  ? 'selected':'' }}>Hadir</option>
                    <option value="late"     {{ request('status')=='late'     ? 'selected':'' }}>Terlambat</option>
                    <option value="absent"   {{ request('status')=='absent'   ? 'selected':'' }}>Tidak Hadir</option>
                    <option value="excused"  {{ request('status')=='excused'  ? 'selected':'' }}>Izin</option>
                    <option value="leave"    {{ request('status')=='leave'    ? 'selected':'' }}>Cuti</option>
                </select>
            </div>

            {{-- Buttons --}}
            <div class="col-md-12 d-flex gap-2 mt-2">
                <button class="btn btn-primary rounded-3 px-4">
                    <i class="fas fa-search me-1"></i> Cari
                </button>

                <button type="button" onclick="resetForm()"
                        class="btn btn-secondary rounded-3 px-4">
                    <i class="fas fa-redo me-1"></i> Reset
                </button>
            </div>
        </form>

    </div>
</div>

{{-- SUMMARY CARD --}}
@if(request()->hasAny(['start_date','end_date','user_id','status']))
<div class="row g-3 mb-4">

    @php
        $summaryBox = [
            ['Total', $attendances->total(), 'primary'],
            ['Hadir', $summary['present'] ?? 0, 'success'],
            ['Terlambat', $summary['late'] ?? 0, 'warning'],
            ['Tidak Hadir', $summary['absent'] ?? 0, 'danger'],
        ];
    @endphp

    @foreach($summaryBox as [$label,$value,$color])
    <div class="col-md-3">
        <div class="card text-white bg-{{ $color }} shadow-sm rounded-4">
            <div class="card-body text-center py-3">
                <h4 class="fw-bold m-0">{{ $value }}</h4>
                <small>{{ $label }}</small>
            </div>
        </div>
    </div>
    @endforeach

</div>
@endif

{{-- MAIN TABLE --}}
<div class="card shadow-sm border-0 rounded-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center rounded-top-4">
        <strong>Daftar Histori Kehadiran</strong>
        <span class="badge bg-primary">{{ $attendances->total() }} Data</span>
    </div>

    <div class="card-body">

        @if($attendances->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="historyTable">

                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>User</th>
                        <th>Check-In</th>
                        <th>Check-Out</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($attendances as $i => $att)
                    <tr>
                        <td>{{ $attendances->firstItem() + $i }}</td>

                        <td>{{ $att->date->format('d/m/Y') }}</td>

                        <td>
                            <strong>{{ $att->user->name }}</strong><br>
                            <small class="text-muted">{{ $att->user->employee_id }}</small>
                        </td>

                        <td>
                            @if($att->check_in_time)
                                <span class="badge bg-success">{{ $att->check_in_time }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>

                        <td>
                            @if($att->check_out_time)
                                <span class="badge bg-info">{{ $att->check_out_time }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>

                        <td>
                            @php
                                $statusColor = [
                                    'present' => 'success',
                                    'late'    => 'warning text-dark',
                                    'absent'  => 'danger',
                                    'excused' => 'info',
                                    'leave'   => 'secondary',
                                ];
                            @endphp
                            <span class="badge bg-{{ $statusColor[$att->status] ?? 'secondary' }}">
                                {{ ucfirst($att->status) }}
                            </span>
                        </td>

                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-primary"
                                    onclick="viewPhotos('{{ $att->id }}',
                                        '{{ $att->check_in_photo ? Storage::url($att->check_in_photo) : '' }}',
                                        '{{ $att->check_out_photo ? Storage::url($att->check_out_photo) : '' }}')">
                                    <i class="fas fa-image"></i>
                                </button>

                                @if($att->notes)
                                <button class="btn btn-secondary"
                                        onclick="viewNotes(`{{ addslashes($att->notes) }}`)">
                                    <i class="fas fa-sticky-note"></i>
                                </button>
                                @endif
                            </div>
                        </td>

                    </tr>
                    @endforeach
                </tbody>

            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-3">
            {{ $attendances->links() }}
        </div>

        @else
        <div class="text-center py-5">
            <i class="fas fa-history fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">Tidak Ada Data</h5>
        </div>
        @endif

    </div>
</div>

@endsection

{{-- =============================================================== --}}
{{-- SCRIPT --}}
{{-- =============================================================== --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
/* FILTER VALIDATION */
$('#filterForm').on('submit', function(e) {
    let s = $('#start_date').val();
    let eD = $('#end_date').val();

    if (s && eD && new Date(s) > new Date(eD)) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Tanggal Tidak Valid',
            text: 'Tanggal mulai tidak boleh melebihi tanggal akhir',
            confirmButtonColor: '#FFC107'
        });
    }
});

/* RESET FILTER */
function resetForm() {
    Swal.fire({
        title: 'Reset Filter?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Reset',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#6c63ff'
    }).then(r => {
        if (r.isConfirmed) {
            $('#start_date').val('');
            $('#end_date').val('');
            $('#user_id').val('');
            $('#status').val('');
        }
    });
}

/* VIEW PHOTOS */
function viewPhotos(id, checkIn, checkOut) {
    let html = '';

    if (checkIn) {
        html += `
            <h6 class="mb-2 text-center">Foto Check-In</h6>
            <img src="${checkIn}" class="img-fluid rounded mb-3">
            <hr>
        `;
    }

    if (checkOut) {
        html += `
            <h6 class="mb-2 text-center">Foto Check-Out</h6>
            <img src="${checkOut}" class="img-fluid rounded">
        `;
    }

    Swal.fire({
        title: 'Foto Kehadiran',
        html: html || '<p class="text-muted text-center">Tidak ada foto</p>',
        width: checkIn && checkOut ? 700 : 450,
        confirmButtonColor: '#4b7bec',
    });
}

/* VIEW NOTES */
function viewNotes(notes) {
    Swal.fire({
        title: 'Catatan',
        html: `<div class='p-3 bg-light rounded'>${notes}</div>`,
        confirmButtonColor: '#4b7bec',
    });
}
</script>
@endpush
