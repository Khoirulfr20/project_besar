@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-calendar"></i> {{ now()->format('d F Y') }}
            </button>
        </div>
    </div>
</div>

<!-- Statistik Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="card-title">Total Pengguna</h6>
                    <h2 class="mb-0">{{ $totalUsers ?? 0 }}</h2>
                </div>
                <i class="fas fa-users fa-3x opacity-50"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white bg-success mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="card-title">Hadir Hari Ini</h6>
                    <h2 class="mb-0">{{ $todayPresent ?? 0 }}</h2>
                </div>
                <i class="fas fa-user-check fa-3x opacity-50"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white bg-warning mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="card-title">Terlambat Hari Ini</h6>
                    <h2 class="mb-0">{{ $todayLate ?? 0 }}</h2>
                </div>
                <i class="fas fa-clock fa-3x opacity-50"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white bg-danger mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="card-title">Tidak Hadir</h6>
                    <h2 class="mb-0">{{ $todayAbsent ?? 0 }}</h2>
                </div>
                <i class="fas fa-user-times fa-3x opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<!-- Jadwal Hari Ini -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Jadwal Hari Ini</h5>
            </div>
            <div class="card-body">
                @if(isset($todaySchedules) && $todaySchedules->count() > 0)
                    <div class="list-group">
                        @foreach($todaySchedules as $schedule)
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">{{ $schedule->title ?? '-' }}</h6>
                                <small>{{ $schedule->start_time ?? '-' }} - {{ $schedule->end_time ?? '-' }}</small>
                            </div>
                            <p class="mb-1">{{ $schedule->description ?? '-' }}</p>
                            <small>
                                <i class="fas fa-map-marker-alt"></i> {{ $schedule->location ?? '-' }}
                                | <i class="fas fa-users"></i> {{ $schedule->participants->count() ?? 0 }} peserta
                            </small>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-center py-3 mb-0">Tidak ada jadwal hari ini</p>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Kehadiran Terbaru</h5>
            </div>
            <div class="card-body">
                @if(isset($latestAttendances) && $latestAttendances->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Waktu</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($latestAttendances as $attendance)
                                <tr>
                                    <td>{{ $attendance->user->name ?? '-' }}</td>
                                    <td>{{ $attendance->check_in_time ?? '-' }}</td>
                                    <td>
                                        @switch($attendance->status)
                                            @case('present')
                                                <span class="badge bg-success">Hadir</span>
                                                @break
                                            @case('late')
                                                <span class="badge bg-warning text-dark">Terlambat</span>
                                                @break
                                            @case('absent')
                                                <span class="badge bg-danger">Tidak Hadir</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ ucfirst($attendance->status ?? '-') }}</span>
                                        @endswitch
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center py-3 mb-0">Belum ada data kehadiran hari ini</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Chart Statistik Mingguan -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Statistik Kehadiran Minggu Ini</h5>
            </div>
            <div class="card-body">
                <canvas id="weeklyChart" height="80"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('weeklyChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($weeklyLabels ?? []) !!},
                datasets: [
                    {
                        label: 'Hadir',
                        data: {!! json_encode($weeklyPresent ?? []) !!},
                        backgroundColor: 'rgba(40, 167, 69, 0.8)',
                    },
                    {
                        label: 'Terlambat',
                        data: {!! json_encode($weeklyLate ?? []) !!},
                        backgroundColor: 'rgba(255, 193, 7, 0.8)',
                    },
                    {
                        label: 'Tidak Hadir',
                        data: {!! json_encode($weeklyAbsent ?? []) !!},
                        backgroundColor: 'rgba(220, 53, 69, 0.8)',
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
});
</script>
@endpush
