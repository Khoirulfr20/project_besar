@extends('layouts.app')
@section('title', 'Laporan Kehadiran')

@section('content')
<div class="d-flex justify-content-between pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Laporan Kehadiran</h1>
</div>

<!-- Filter & Export Card -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.attendance') }}" class="row g-3" id="filterForm">
            <div class="col-md-3">
                <label class="form-label">Tanggal Mulai</label>
                <input type="date" name="start_date" class="form-control" 
                       value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal Akhir</label>
                <input type="date" name="end_date" class="form-control" 
                       value="{{ request('end_date', now()->format('Y-m-d')) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Departemen</label>
                <select name="department" class="form-select">
                    <option value="">Semua</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}" {{ request('department') == $dept ? 'selected' : '' }}>
                            {{ $dept }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Hadir</option>
                    <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>Terlambat</option>
                    <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Alfa</option>
                    <option value="excused" {{ request('status') == 'excused' ? 'selected' : '' }}>Izin</option>
                    <option value="leave" {{ request('status') == 'leave' ? 'selected' : '' }}>Cuti</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary d-block w-100">
                    <i class="fas fa-search"></i> Filter
                </button>
            </div>
        </form>
        
        <!-- Export Buttons -->
        <div class="row mt-3">
            <div class="col-12">
                <hr>
                <h6>Export Laporan:</h6>
                <button type="button" class="btn btn-success" onclick="exportData('excel')">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
                <button type="button" class="btn btn-danger" onclick="exportData('pdf')">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </button>
                <button type="button" class="btn btn-info" onclick="exportData('csv')">
                    <i class="fas fa-file-csv"></i> Export CSV
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Summary -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card text-white bg-primary">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $statistics['total'] }}</h3>
                <small>Total</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-white bg-success">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $statistics['present'] }}</h3>
                <small>Hadir</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-white bg-warning">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $statistics['late'] }}</h3>
                <small>Terlambat</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-white bg-info">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $statistics['excused'] }}</h3>
                <small>Izin</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-white bg-danger">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $statistics['absent'] }}</h3>
                <small>Alfa</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-white bg-secondary">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $statistics['leave'] }}</h3>
                <small>Cuti</small>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Rate -->
<div class="card mb-4">
    <div class="card-body">
        <h5>Tingkat Kehadiran</h5>
        <div class="progress" style="height: 25px;">
            <div class="progress-bar {{ $statistics['attendance_rate'] >= 80 ? 'bg-success' : ($statistics['attendance_rate'] >= 60 ? 'bg-warning' : 'bg-danger') }}" 
                 role="progressbar" style="width: {{ $statistics['attendance_rate'] }}%">
                {{ number_format($statistics['attendance_rate'], 1) }}%
            </div>
        </div>
        <small class="text-muted mt-2 d-block">
            <i class="fas fa-info-circle"></i>
            Periode: {{ request('start_date') }} s/d {{ request('end_date') }} | 
            Total Kehadiran: {{ $statistics['total'] }}
        </small>
    </div>
</div>

<!-- Chart -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Grafik Kehadiran (7 Hari Terakhir)</h5>
    </div>
    <div class="card-body">
        <canvas id="attendanceChart" height="80"></canvas>
    </div>
</div>

<!-- Data Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Detail Kehadiran</h5>
        <span class="badge bg-primary">{{ $attendances->total() }} Data</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="reportTable" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Nama</th>
                        <th>ID Karyawan</th>
                        <th>Departemen</th>
                        <th>Check-In</th>
                        <th>Check-Out</th>
                        <th>Durasi</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $index => $att)
                    <tr>
                        <td>{{ $attendances->firstItem() + $index }}</td>
                        <td>{{ $att->date->format('d/m/Y') }}</td>
                        <td>{{ $att->user->name }}</td>
                        <td>{{ $att->user->employee_id }}</td>
                        <td>{{ $att->user->department ?? '-' }}</td>
                        <td>
                            {{ $att->check_in_time ?? '-' }}
                            @if($att->check_in_photo)
                                <a href="{{ Storage::url($att->check_in_photo) }}" target="_blank" class="text-primary">
                                    <i class="fas fa-image"></i>
                                </a>
                            @endif
                        </td>
                        <td>
                            {{ $att->check_out_time ?? '-' }}
                            @if($att->check_out_photo)
                                <a href="{{ Storage::url($att->check_out_photo) }}" target="_blank" class="text-primary">
                                    <i class="fas fa-image"></i>
                                </a>
                            @endif
                        </td>
                        <td>
                            @if($att->work_duration)
                                {{ floor($att->work_duration / 60) }}j {{ $att->work_duration % 60 }}m
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($att->status == 'present')
                                <span class="badge bg-success">Hadir</span>
                            @elseif($att->status == 'late')
                                <span class="badge bg-warning text-dark">Terlambat</span>
                            @elseif($att->status == 'absent')
                                <span class="badge bg-danger">Alfa</span>
                            @elseif($att->status == 'excused')
                                <span class="badge bg-info">Izin</span>
                            @elseif($att->status == 'leave')
                                <span class="badge bg-secondary">Cuti</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center">Tidak ada data kehadiran</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-3">
            {{ $attendances->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    @media print {
        .btn, .pagination, .card-header button, form, hr { display: none !important; }
        .card { border: 1px solid #ddd !important; }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>
<script>
// DataTable - Only if data exists
$(document).ready(function() {
    const $table = $('#reportTable');
    const hasData = $table.find('tbody tr').length > 0 && 
                    !$table.find('tbody tr td').first().hasClass('text-center');
    
    if (hasData) {
        $table.DataTable({
            paging: false,
            searching: false,
            ordering: true,
            info: false,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.5/i18n/id.json' }
        });
    }
});

// Chart
const ctx = document.getElementById('attendanceChart');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: {!! json_encode($chartLabels) !!},
        datasets: [{
            label: 'Hadir',
            data: {!! json_encode($chartPresent) !!},
            backgroundColor: 'rgba(40, 167, 69, 0.8)',
        }, {
            label: 'Terlambat',
            data: {!! json_encode($chartLate) !!},
            backgroundColor: 'rgba(255, 193, 7, 0.8)',
        }, {
            label: 'Alfa',
            data: {!! json_encode($chartAbsent) !!},
            backgroundColor: 'rgba(220, 53, 69, 0.8)',
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            title: { display: false }
        },
        scales: { y: { beginAtZero: true } }
    }
});

// Export Function
function exportData(format) {
    const form = document.getElementById('filterForm');
    const url = new URL(form.action);
    const params = new URLSearchParams(new FormData(form));
    params.set('export', format);
    
    window.location.href = '{{ route("admin.reports.export") }}?' + params.toString();
}
</script>
@endpush