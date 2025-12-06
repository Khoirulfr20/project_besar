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
        <div class="dropdown ms-auto">
            <button class="btn btn-sm text-white d-flex align-items-center dropdown-toggle"
                    type="button" id="userMenu"
                    data-bs-toggle="dropdown" aria-expanded="false"
                    style="font-size: 0.9rem; font-weight: 600;">
                <i class="fas fa-user-circle me-1"></i> {{ auth()->user()->name }}
            </button>

            <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userMenu">
                {{-- Logout --}}
                <li>
                    <form action="{{ route('admin.logout') }}" method="POST" class="m-0 p-0">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger fw-semibold">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </button>
                    </form>
                </li>

            </ul>
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

    /* Dropdown */
    .dropdown-menu {
        border-radius: 10px;
        font-size: 0.9rem;
        padding: 6px 0;
    }

    .dropdown-item {
        padding: 9px 16px;
        border-radius: 6px;
        font-weight: 500;
    }

    .dropdown-item:hover {
        background-color: #f2f2f2;
    }

    /* Mobile */
    .navbar-toggler {
        border: none;
    }

    .navbar-toggler:focus {
        box-shadow: none !important;
    }
</style>
