<nav class="navbar fixed-top shadow-sm header-bar">
    <div class="container-fluid">

        {{-- Sidebar Toggle Mobile --}}
        <button class="navbar-toggler d-md-none me-2" type="button" 
                data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
            <i class="fas fa-bars text-white"></i>
        </button>

        {{-- Brand --}}
        <a class="navbar-brand d-flex align-items-center fw-semibold text-white" 
           href="{{ route('admin.dashboard') }}">
            <i class="fas fa-user-check me-2"></i> Smart Attendance
        </a>

        {{-- Right Section --}}
        <div class="d-flex align-items-center ms-auto">
            <span class="text-white me-3 d-flex align-items-center small fw-semibold">
                <i class="fas fa-user-circle me-1"></i> {{ auth()->user()->name }}
            </span>

            <form action="{{ route('admin.logout') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-light logout-btn">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </button>
            </form>
        </div>
    </div>
</nav>

<style>
    /* Header bar theme */
    .header-bar {
        background: linear-gradient(135deg, #667eea, #764ba2);
        padding: 10px 15px;
    }

    .navbar-brand {
        font-size: 1rem;
        letter-spacing: 0.3px;
    }

    /* Logout button */
    .logout-btn {
        font-size: 0.8rem;
        padding: 6px 10px;
        border-radius: 6px;
        font-weight: 600;
        color: #444 !important;
    }

    .logout-btn:hover {
        background: #ffffffd9;
    }

    /* Mobile */
    .navbar-toggler {
        border: none;
    }

    .navbar-toggler:focus {
        box-shadow: none;
    }
</style>
