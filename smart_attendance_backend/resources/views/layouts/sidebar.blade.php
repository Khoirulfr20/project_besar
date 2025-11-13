{{-- ============================================ --}}
{{-- File: resources/views/layouts/sidebar.blade.php --}}
{{-- ============================================ --}}
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse border-end min-vh-100">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            {{-- Dashboard --}}
            <li class="nav-item mb-1">
                <a class="nav-link d-flex align-items-center px-3 py-2 {{ request()->routeIs('admin.dashboard') ? 'active bg-primary text-white rounded' : 'text-dark' }}" 
                   href="{{ route('admin.dashboard') }}">
                    <i class="fas fa-tachometer-alt me-2"></i> 
                    <span>Dashboard</span>
                </a>
            </li>

            {{-- Kelola Pengguna --}}
            <li class="nav-item mb-1">
                <a class="nav-link d-flex align-items-center px-3 py-2 {{ request()->routeIs('admin.users.*') ? 'active bg-primary text-white rounded' : 'text-dark' }}" 
                   href="{{ route('admin.users.index') }}">
                    <i class="fas fa-users me-2"></i>
                    <span>Kelola Pengguna</span>
                </a>
            </li>

            {{-- Kelola Jadwal --}}
            <li class="nav-item mb-1">
                <a class="nav-link d-flex align-items-center px-3 py-2 {{ request()->routeIs('admin.schedules.*') ? 'active bg-primary text-white rounded' : 'text-dark' }}" 
                   href="{{ route('admin.schedules.index') }}">
                    <i class="fas fa-calendar-alt me-2"></i>
                    <span>Kelola Jadwal</span>
                </a>
            </li>
            
            {{-- Catat Kehadiran --}}
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.attendance.record') ? 'active' : '' }}" 
                   href="{{ route('admin.attendance.record') }}">
                    <i class="fas fa-user-check"></i> Record Attendance
                </a>
            </li>

            {{-- Kelola Kehadiran --}}
            <li class="nav-item mb-1">
                <a class="nav-link d-flex align-items-center px-3 py-2 {{ request()->routeIs('admin.attendances.*') ? 'active bg-primary text-white rounded' : 'text-dark' }}" 
                   href="{{ route('admin.attendances.index') }}">
                    <i class="fas fa-clipboard-check me-2"></i>
                    <span>Kelola Kehadiran</span>
                </a>
            </li>

            {{-- Histori Kehadiran --}}
            <li class="nav-item mb-1">
                <a class="nav-link d-flex align-items-center px-3 py-2 {{ request()->routeIs('admin.history.*') ? 'active bg-primary text-white rounded' : 'text-dark' }}" 
                   href="{{ route('admin.history.index') }}">
                    <i class="fas fa-history me-2"></i>
                    <span>Histori Kehadiran</span>
                </a>
            </li>

            {{-- Pengaturan --}}
            <li class="nav-item mt-2 border-top pt-2">
                <a class="nav-link d-flex align-items-center px-3 py-2 {{ request()->routeIs('admin.settings.*') ? 'active bg-primary text-white rounded' : 'text-dark' }}" 
                   href="{{ route('admin.settings.index') }}">
                    <i class="fas fa-cog me-2"></i>
                    <span>Pengaturan</span>
                </a>
            </li>
        </ul>
    </div>
</nav>
