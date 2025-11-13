{{-- ============================================ --}}
{{-- File: resources/views/layouts/header.blade.php --}}
{{-- ============================================ --}}
<nav class="navbar navbar-dark bg-dark fixed-top shadow-sm">
    <div class="container-fluid">
        {{-- Sidebar Toggle (muncul hanya di mobile) --}}
        <button class="navbar-toggler d-md-none me-2" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        {{-- Brand --}}
        <a class="navbar-brand d-flex align-items-center fw-semibold" href="{{ route('admin.dashboard') }}">
            <i class="fas fa-user-check me-2"></i> Smart Attendance Admin
        </a>

        {{-- Right section --}}
        <div class="d-flex align-items-center ms-auto">
            <span class="text-white me-3 d-flex align-items-center">
                <i class="fas fa-user-circle me-1"></i> {{ auth()->user()->name }}
            </span>

            <form action="{{ route('admin.logout') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-light d-flex align-items-center">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </button>
            </form>
        </div>
    </div>
</nav>
{{-- End of Header --}}
