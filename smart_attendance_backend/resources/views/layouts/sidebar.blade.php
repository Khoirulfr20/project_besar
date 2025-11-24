{{-- ============================================ --}}
{{-- File: resources/views/layouts/sidebar.blade.php --}}
{{-- ============================================ --}}
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="sidebar-sticky pt-3">
        <ul class="nav flex-column">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" 
                   href="{{ route('admin.dashboard') }}">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            
            <!-- Kelola Pengguna -->
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" 
                   href="{{ route('admin.users.index') }}">
                    <i class="fas fa-users"></i> Kelola Pengguna
                </a>
            </li>
            
            <!-- Kelola Jadwal -->
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.schedules.*') ? 'active' : '' }}" 
                   href="{{ route('admin.schedules.index') }}">
                    <i class="fas fa-calendar-alt"></i> Kelola Jadwal
                </a>
            </li>
            
            <!-- Divider -->
            <hr class="my-2">
            <li class="nav-item">
                <small class="text-muted ps-3">KEHADIRAN</small>
            </li>

            {{-- Catat Kehadiran --}}
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.attendance.record') ? 'active' : '' }}" 
                   href="{{ route('admin.attendance.record') }}">
                    <i class="fas fa-user-check"></i> Record Attendance
                </a>
            </li>
            
            <!-- Kelola Kehadiran (Real-time) -->
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.attendances.index') ? 'active' : '' }}" 
                   href="{{ route('admin.attendances.index') }}">
                    <i class="fas fa-clipboard-check"></i> Kelola Kehadiran
                </a>
            </li>
            
            <!-- Laporan Kehadiran (Report & Export) -->
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" 
                   href="{{ route('admin.reports.attendance') }}">
                    <i class="fas fa-chart-bar"></i> Laporan Kehadiran
                </a>
            </li>
            
            <!-- Histori Kehadiran -->
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.history.*') ? 'active' : '' }}" 
                   href="{{ route('admin.history.index') }}">
                    <i class="fas fa-history"></i> Histori Kehadiran
                </a>
            </li>
            
            <!-- Divider -->
            <hr class="my-2">
            
            <!-- Pengaturan -->
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" 
                   href="{{ route('admin.settings.index') }}">
                    <i class="fas fa-cog"></i> Pengaturan
                </a>
            </li>
        </ul>
        
        <!-- Version Info -->
        <div class="position-absolute bottom-0 start-0 p-3 w-100">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> v1.0.0
            </small>
        </div>
    </div>
</nav>