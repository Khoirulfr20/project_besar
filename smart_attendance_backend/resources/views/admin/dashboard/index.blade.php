@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')

{{-- ========================== --}}
{{-- TOP HEADER --}}
{{-- ========================== --}}
<div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
    <h4 class="fw-semibold text-dark">Dashboard</h4>

    <div class="d-flex">
        <button type="button" class="btn btn-sm btn-light border me-2">
            <i class="fas fa-calendar"></i> {{ now()->format('d F Y') }}
        </button>

        <button type="button" class="btn btn-sm btn-primary" id="refreshDashboard">
            <i class="fas fa-sync-alt"></i>
        </button>
    </div>
</div>


{{-- ========================== --}}
{{-- STATISTIC CARDS --}}
{{-- ========================== --}}
<div class="row g-3 mb-4">

    <div class="col-md-3">
        <div class="stat-box" data-stat="users">
            <div>
                <p class="label">Total Pengguna</p>
                <h3>{{ $totalUsers ?? 0 }}</h3>
            </div>
            <i class="fas fa-users icon"></i>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-box" data-stat="present">
            <div>
                <p class="label">Hadir Hari Ini</p>
                <h3>{{ $todayPresent ?? 0 }}</h3>
            </div>
            <i class="fas fa-user-check icon"></i>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-box" data-stat="late">
            <div>
                <p class="label">Terlambat Hari Ini</p>
                <h3>{{ $todayLate ?? 0 }}</h3>
            </div>
            <i class="fas fa-clock icon"></i>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-box" data-stat="absent">
            <div>
                <p class="label">Tidak Hadir</p>
                <h3>{{ $todayAbsent ?? 0 }}</h3>
            </div>
            <i class="fas fa-user-times icon"></i>
        </div>
    </div>

</div>


{{-- ========================== --}}
{{-- SCHEDULE & ATTENDANCE --}}
{{-- ========================== --}}
<div class="row g-3 mb-4">

    {{-- TODAY SCHEDULE --}}
    <div class="col-md-6">
        <div class="box-card">
            <div class="box-header">
                <span>Jadwal Hari Ini</span>

                @if(!empty($todaySchedules) && $todaySchedules->count())
                <button class="btn btn-sm btn-primary" onclick="viewAllSchedules()">
                    <i class="fas fa-folder-open"></i>
                </button>
                @endif
            </div>

            <div class="box-body">

                @if(!empty($todaySchedules) && $todaySchedules->count())

                <ul class="list-group simple-list">
                    @foreach($todaySchedules as $schedule)
                    <li class="simple-item schedule-item" data-schedule-id="{{ $schedule->id }}">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $schedule->title }}</strong>
                            <small>{{ $schedule->start_time }} - {{ $schedule->end_time }}</small>
                        </div>

                        <small class="text-muted">{{ $schedule->location }}</small>
                    </li>
                    @endforeach
                </ul>

                @else
                <div class="empty-box">
                    <i class="fas fa-calendar-times empty-icon"></i>
                    <p>Tidak ada jadwal hari ini</p>
                </div>
                @endif
            </div>
        </div>
    </div>


    {{-- TODAY ATTENDANCE --}}
    <div class="col-md-6">
        <div class="box-card">
            <div class="box-header">
                <span>Kehadiran Terbaru</span>

                @if(!empty($latestAttendances) && $latestAttendances->count())
                <button class="btn btn-sm btn-success" onclick="viewAllAttendances()">
                    <i class="fas fa-list"></i>
                </button>
                @endif
            </div>

            <div class="box-body">

                @if(!empty($latestAttendances) && $latestAttendances->count())

                <table class="table table-sm align-middle">
                    @foreach($latestAttendances as $att)
                    <tr class="simple-row attendance-row" data-attendance-id="{{ $att->id }}">

                        <td>
                            <strong>{{ $att->user->name }}</strong>
                        </td>

                        <td>{{ $att->check_in_time }}</td>

                        <td>
                            <span class="badge 
                                {{ $att->status === 'present' ? 'bg-success' : '' }}
                                {{ $att->status === 'late' ? 'bg-warning text-dark' : '' }}
                                {{ $att->status === 'absent' ? 'bg-danger' : '' }}">
                                {{ ucfirst($att->status) }}
                            </span>
                        </td>

                    </tr>
                    @endforeach
                </table>

                @else
                <div class="empty-box">
                    <i class="fas fa-user-clock empty-icon"></i>
                    <p>Belum ada data kehadiran</p>
                </div>
                @endif
            </div>
        </div>
    </div>

</div>


{{-- ========================== --}}
{{-- WEEKLY CHART --}}
{{-- ========================== --}}
<div class="box-card mb-4">
    <div class="box-header">
        <span>Statistik Kehadiran Minggu Ini</span>
    </div>

    <div class="box-body">
        <canvas id="weeklyChart" height="80"></canvas>
    </div>
</div>


@endsection

{{-- ========================== --}}
{{-- SCRIPTS & DESIGN --}}
{{-- ========================== --}}
@push('scripts')

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<script>
let weeklyChartInstance = null;

document.addEventListener("DOMContentLoaded", () => {

    /* -------------------------------
       CHART.JS
    --------------------------------*/
    const ctx = document.getElementById("weeklyChart");
    if (ctx) {
        weeklyChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($weeklyLabels ?? []) !!},
                datasets: [
                    {
                        label: 'Hadir',
                        data: {!! json_encode($weeklyPresent ?? []) !!},
                        backgroundColor: 'rgba(102, 126, 234, 0.85)',
                    },
                    {
                        label: 'Terlambat',
                        data: {!! json_encode($weeklyLate ?? []) !!},
                        backgroundColor: 'rgba(255, 193, 7, 0.85)',
                    },
                    {
                        label: 'Tidak Hadir',
                        data: {!! json_encode($weeklyAbsent ?? []) !!},
                        backgroundColor: 'rgba(220, 53, 69, 0.85)',
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


    /* -------------------------------
       EVENT HANDLERS
    --------------------------------*/
    $(".stat-box").click(function() {
        showStatDetail($(this).data("stat"));
    });

    $(".schedule-item").click(function() {
        showScheduleDetail($(this).data("schedule-id"));
    });

    $(".attendance-row").click(function() {
        showAttendanceDetail($(this).data("attendance-id"));
    });

    $("#refreshDashboard").click(refreshDashboard);

});


/* -------------------------------
   SweetAlert: STATISTIC DETAIL
--------------------------------*/
function showStatDetail(statType) {
    let title = '', icon = '', message = '', color = '';

    const info = {
        users: {
            title: 'Total Pengguna',
            icon: 'users',
            message: 'Jumlah total pengguna terdaftar.',
            color: '#667eea'
        },
        present: {
            title: 'Hadir Hari Ini',
            icon: 'user-check',
            message: 'Pengguna hadir tepat waktu.',
            color: '#28a745'
        },
        late: {
            title: 'Terlambat Hari Ini',
            icon: 'clock',
            message: 'Pengguna terlambat check-in.',
            color: '#ffc107'
        },
        absent: {
            title: 'Tidak Hadir',
            icon: 'user-times',
            message: 'Pengguna tidak hadir hari ini.',
            color: '#dc3545'
        }
    };

    let d = info[statType];

    Swal.fire({
        title: d.title,
        html: `
            <div class="text-center">
                <i class="fas fa-${d.icon} fa-4x mb-3" style="color:${d.color}"></i>
                <p>${d.message}</p>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: "Lihat Detail",
        confirmButtonColor: d.color,
        customClass: { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-secondary' },
        buttonsStyling: false
    }).then(res => {
        if (res.isConfirmed) {
            if (statType === "users") {
                window.location.href = "{{ route('admin.users.index') }}";
            } else {
                window.location.href = "{{ route('admin.attendances.index') }}?status=" + statType;
            }
            
        }
    });
}


/* -------------------------------
   SHOW SCHEDULE DETAIL
--------------------------------*/
function showScheduleDetail(id) {
    Swal.fire({
        title: "Detail Jadwal",
        html: `<div class='py-4'><div class="spinner-border"></div></div>`,
        showConfirmButton: false
    });
}


/* -------------------------------
   SHOW ATTENDANCE DETAIL
--------------------------------*/
function showAttendanceDetail(id) {
    Swal.fire({
        title: "Detail Kehadiran",
        html: `<div class='py-4'><div class="spinner-border"></div></div>`,
        showConfirmButton: false
    });
}


/* -------------------------------
   NAVIGATION SHORTCUTS
--------------------------------*/
function viewAllSchedules() {
    window.location.href = "{{ route('admin.schedules.index') }}";
}

function viewAllAttendances() {
    window.location.href = "{{ route('admin.attendances.index') }}";
}


/* -------------------------------
   REFRESH DASHBOARD
--------------------------------*/
function refreshDashboard() {

    Swal.fire({
        title: "Memuat ulang...",
        html: "Mengambil data terbaru...",
        allowEscapeKey: false,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => Swal.showLoading()
    });

    setTimeout(() => location.reload(), 1000);
}




</script>



{{-- ========================== --}}
{{-- CUSTOM UI DESIGN --}}
{{-- ========================== --}}
<style>

    /* ============================
       STAT CARDS
    ============================ */
    .stat-box {
        background: white;
        border: 1px solid #e4e6f5;
        padding: 18px;
        border-radius: 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        transition: 0.25s ease;
    }

    .stat-box:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    }

    .stat-box .label {
        font-size: 0.85rem;
        color: #666;
    }

    .stat-box .icon {
        font-size: 2.3rem;
        color: #667eea66;
    }

    /* ============================
       BOX CARD
    ============================ */
    .box-card {
        background: white;
        border: 1px solid #e4e6f5;
        border-radius: 14px;
        overflow: hidden;
    }

    .box-header {
        padding: 12px 16px;
        border-bottom: 1px solid #e4e6f5;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
        color: #444;
    }

    .box-body {
        padding: 16px;
    }

    /* ============================
       SIMPLE LIST
    ============================ */
    .simple-item {
        padding: 10px 12px;
        border-bottom: 1px solid #f2f4fb;
        cursor: pointer;
        transition: 0.25s;
        border-radius: 8px;
    }

    .simple-item:hover {
        background: #f5f7ff;
    }

    .simple-row:hover {
        background: #f5f7ff;
        cursor: pointer;
    }

    /* ============================
       EMPTY STATE
    ============================ */
    .empty-box {
        text-align: center;
        padding: 40px;
        color: #999;
    }

    .empty-icon {
        font-size: 45px;
        opacity: 0.35;
        margin-bottom: 12px;
    }

    .swal2-actions .btn {
        margin: 0 6px !important;
    }

</style>

@endpush
