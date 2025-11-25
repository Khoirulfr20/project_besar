{{-- ========================================================= --}}
{{-- File: resources/views/admin/reports/attendance.blade.php --}}
{{-- Laporan Kehadiran — versi simpel, modern, sesuai tema --}}
{{-- ========================================================= --}}
@extends('layouts.app')
@section('title', 'Laporan Kehadiran')

@section('content')

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
    <h4 class="fw-semibold m-0">Laporan Kehadiran</h4>
</div>

{{-- FILTER CARD --}}
<div class="card shadow-sm border-0 rounded-4 mb-4">
    <div class="card-body">

        <form method="GET" id="filterForm" class="row g-3">

            {{-- Start Date --}}
            <div class="col-md-3">
                <label class="form-label fw-medium">Tanggal Mulai</label>
                <input type="date" name="start_date" class="form-control rounded-3"
                       value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}">
            </div>

            {{-- End Date --}}
            <div class="col-md-3">
                <label class="form-label fw-medium">Tanggal Akhir</label>
                <input type="date" name="end_date" class="form-control rounded-3"
                       value="{{ request('end_date', now()->format('Y-m-d')) }}">
            </div>

            {{-- USER FILTER (Pengganti Departemen) --}}
            <div class="col-md-3">
                <label class="form-label fw-medium">User</label>
                <select name="user" class="form-select rounded-3">
                    <option value="">Semua User</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user') == $u->id ? 'selected' : '' }}>
                            {{ $u->name }} ({{ $u->employee_id }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Status --}}
            <div class="col-md-3">
                <label class="form-label fw-medium">Status</label>
                <select name="status" class="form-select rounded-3">
                    <option value="">Semua Status</option>
                    <option value="present"  {{ request('status') == 'present' ? 'selected' : '' }}>Hadir</option>
                    <option value="late"     {{ request('status') == 'late' ? 'selected' : '' }}>Terlambat</option>
                    <option value="absent"   {{ request('status') == 'absent' ? 'selected' : '' }}>Alfa</option>
                    <option value="excused"  {{ request('status') == 'excused' ? 'selected' : '' }}>Izin</option>
                    <option value="leave"    {{ request('status') == 'leave' ? 'selected' : '' }}>Cuti</option>
                </select>
            </div>

            {{-- Filter Button --}}
            <div class="col-md-12 text-end mt-2">
                <button class="btn btn-primary rounded-3 px-4">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
            </div>

        </form>

        {{-- EXPORT --}}
        <hr class="my-3">
        <h6 class="fw-semibold mb-2">Export Laporan</h6>

        <button class="btn btn-success rounded-3 me-2" onclick="exportData('excel')">
            <i class="fas fa-file-excel me-1"></i> Excel
        </button>

        <button class="btn btn-danger rounded-3 me-2" onclick="exportData('pdf')">
            <i class="fas fa-file-pdf me-1"></i> PDF
        </button>

        <button class="btn btn-info rounded-3 me-2" onclick="exportData('csv')">
            <i class="fas fa-file-csv me-1"></i> CSV
        </button>

        <button class="btn btn-secondary rounded-3" onclick="window.print()">
            <i class="fas fa-print me-1"></i> Print
        </button>

    </div>
</div>

{{-- STATISTICS --}}
<div class="row g-3 mb-4">

    @php 
        $statCards = [
            ['Total',         $statistics['total'],        'primary'],
            ['Hadir',         $statistics['present'],      'success'],
            ['Terlambat',     $statistics['late'],         'warning'],
            ['Izin',          $statistics['excused'],      'info'],
            ['Alfa',          $statistics['absent'],       'danger'],
            ['Cuti',          $statistics['leave'],        'secondary'],
        ];
    @endphp

    @foreach($statCards as [$label, $value, $color])
    <div class="col-md-2">
        <div class="card text-white bg-{{ $color }} rounded-4 shadow-sm">
            <div class="card-body py-3 text-center">
                <h4 class="fw-bold mb-0">{{ $value }}</h4>
                <small>{{ $label }}</small>
            </div>
        </div>
    </div>
    @endforeach

</div>

{{-- ATTENDANCE RATE --}}
<div class="card shadow-sm border-0 rounded-4 mb-4">
    <div class="card-body">
        <h6 class="fw-semibold">Tingkat Kehadiran</h6>

        <div class="progress mt-2" style="height: 18px;">
            <div class="progress-bar 
                {{ $statistics['attendance_rate'] >= 80 ? 'bg-success' : 
                   ($statistics['attendance_rate'] >= 60 ? 'bg-warning' : 'bg-danger') }} 
            rounded-3"
            style="width: {{ $statistics['attendance_rate'] }}%">
                {{ number_format($statistics['attendance_rate'],1) }}%
            </div>
        </div>

        <small class="text-muted d-block mt-2">
            Periode: {{ request('start_date') }} s/d {{ request('end_date') }}
        </small>
    </div>
</div>

{{-- CHART --}}
<div class="card shadow-sm border-0 rounded-4 mb-4">
    <div class="card-header bg-light">
        <strong>Grafik Kehadiran (7 Hari Terakhir)</strong>
    </div>
    <div class="card-body">
        <canvas id="attendanceChart" height="80"></canvas>
    </div>
</div>

{{-- TABLE --}}
<div class="card shadow-sm border-0 rounded-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <strong>Detail Kehadiran</strong>
        <span class="badge bg-primary">{{ $attendances->total() }} Data</span>
    </div>

    <div class="card-body">

        <div class="table-responsive">
            <table id="reportTable" class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>User</th>
                        <th>ID Karyawan</th>
                        <th>Check-In</th>
                        <th>Check-Out</th>
                        <th>Durasi</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($attendances as $i => $att)
                    <tr>
                        <td>{{ $attendances->firstItem() + $i }}</td>
                        <td>{{ $att->date->format('d/m/Y') }}</td>
                        <td>{{ $att->user->name }}</td>
                        <td>{{ $att->user->employee_id }}</td>

                        <td>{{ $att->check_in_time ?? '-' }}</td>
                        <td>{{ $att->check_out_time ?? '-' }}</td>

                        <td>
                            @if($att->work_duration)
                                {{ floor($att->work_duration / 60) }}j 
                                {{ $att->work_duration % 60 }}m
                            @else
                                -
                            @endif
                        </td>

                        <td>
                            <span class="badge 
                                @if($att->status=='present') bg-success
                                @elseif($att->status=='late') bg-warning text-dark
                                @elseif($att->status=='absent') bg-danger
                                @elseif($att->status=='excused') bg-info
                                @else bg-secondary @endif
                            ">
                                {{ ucfirst($att->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            Tidak ada data
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-3">
            {{ $attendances->appends(request()->query())->links() }}
        </div>

    </div>
</div>

@endsection

{{-- ========================================================= --}}
{{-- JAVASCRIPT --}}
{{-- ========================================================= --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// EXPORT
function exportData(type) {
    Swal.fire({
        title: 'Export Data?',
        text: 'Format: ' + type.toUpperCase(),
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4B7BEC',
        cancelButtonColor: '#6c757d'
    }).then(res => {
        if (res.isConfirmed) {
            const params = new URLSearchParams(new FormData(document.getElementById('filterForm')));
            params.set('export', type);
            window.location.href = "{{ route('admin.reports.export') }}?" + params.toString();
        }
    });
}

// CHART
new Chart(document.getElementById('attendanceChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($chartLabels) !!},
        datasets: [
            { label: "Hadir",      data: {!! json_encode($chartPresent) !!}, backgroundColor: "#4CAF50" },
            { label: "Terlambat",  data: {!! json_encode($chartLate) !!},    backgroundColor: "#FFC107" },
            { label: "Alfa",       data: {!! json_encode($chartAbsent) !!},  backgroundColor: "#F44336" }
        ]
    }
});
</script>
@endpush
