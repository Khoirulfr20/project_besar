{{-- ============================================ --}}
{{-- File: resources/views/layouts/sidebar.blade.php --}}
{{-- ============================================ --}}
<nav class="sidebar bg-white">
    <ul class="nav flex-column px-3 pt-4">

        <!-- Dashboard -->
        <li class="nav-item mb-1">
            <a class="nav-link d-flex align-items-center 
                {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                href="{{ route('admin.dashboard') }}">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
        </li>

        <!-- Pengguna -->
        <li class="nav-item mb-1">
            <a class="nav-link d-flex align-items-center
                {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                href="{{ route('admin.users.index') }}">
                <i class="fas fa-users me-2"></i> Kelola Pengguna
            </a>
        </li>

        <!-- Jadwal -->
        <li class="nav-item mb-1">
            <a class="nav-link d-flex align-items-center
                {{ request()->routeIs('admin.schedules.*') ? 'active' : '' }}"
                href="{{ route('admin.schedules.index') }}">
                <i class="fas fa-calendar-alt me-2"></i> Kelola Jadwal
            </a>
        </li>

        <!-- Divider -->
        <div class="text-muted small mt-3 mb-1 px-2">KEHADIRAN</div>

        <!-- Record Attendance -->
        <li class="nav-item mb-1">
            <a class="nav-link d-flex align-items-center
                {{ request()->routeIs('admin.attendance.record') ? 'active' : '' }}"
                href="{{ route('admin.attendance.record') }}">
                <i class="fas fa-user-check me-2"></i> Record Attendance
            </a>
        </li>

        <!-- Kelola Kehadiran -->
        <li class="nav-item mb-1">
            <a class="nav-link d-flex align-items-center
                {{ request()->routeIs('admin.attendances.index') ? 'active' : '' }}"
                href="{{ route('admin.attendances.index') }}">
                <i class="fas fa-clipboard-check me-2"></i> Kelola Kehadiran
            </a>
        </li>

        <!-- Laporan -->
        <li class="nav-item mb-1">
            <a class="nav-link d-flex align-items-center
                {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}"
                href="{{ route('admin.reports.attendance') }}">
                <i class="fas fa-chart-bar me-2"></i> Laporan Kehadiran
            </a>
        </li>

        <!-- Histori -->
        <li class="nav-item mb-1">
            <a class="nav-link d-flex align-items-center
                {{ request()->routeIs('admin.history.*') ? 'active' : '' }}"
                href="{{ route('admin.history.index') }}">
                <i class="fas fa-history me-2"></i> Histori Kehadiran
            </a>
        </li>

        <!-- Divider -->
        <div class="my-3"></div>

        
    </ul>

    <!-- Version -->
    <div class="sidebar-footer px-3 py-3">
        <small class="text-muted">
            <i class="fas fa-info-circle me-1"></i> v1.0.0
        </small>
    </div>
</nav>

<style>
    /* Sidebar Base */
    .sidebar {
        width: 240px;
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        border-right: 1px solid #e5e7f1;
        padding-top: 70px;
        overflow-y: auto;
    }

    /* Nav Link */
    .sidebar .nav-link {
        color: #444;
        padding: 9px 14px;
        border-radius: 8px;
        transition: 0.25s;
        font-weight: 500;
    }

    /* Hover */
    .sidebar .nav-link:hover {
        background: rgba(102, 126, 234, 0.12);
        color: #667eea;
    }

    /* Active */
    .sidebar .nav-link.active {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: #fff !important;
        font-weight: 600;
        box-shadow: 0 3px 8px rgba(102, 126, 234, 0.25);
    }

    /* Footer */
    .sidebar-footer {
        position: absolute;
        bottom: 0;
        width: 100%;
        border-top: 1px solid #e5e7f1;
        background: #fff;
    }

    /* Mobile */
    @media (max-width: 767px) {
        .sidebar {
            width: 100%;
            height: auto;
            position: relative;
            padding-top: 20px;
        }
        .sidebar-footer {
            position: relative;
        }
    }
</style>
