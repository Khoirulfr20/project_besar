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
                    style="font-size: 0.9rem; font-weight: 600; white-space: nowrap;">
                <i class="fas fa-user-circle me-2" style="font-size: 1.1rem;"></i> 
                <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
                <span class="d-inline d-sm-none">Menu</span>
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

    /* Right Section Button */
    .dropdown > button {
        padding: 6px 12px;
        border-radius: 8px;
        transition: all 0.3s ease;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .dropdown > button:hover {
        background: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.3);
    }

    /* Dropdown Menu */
    .dropdown-menu {
        border-radius: 10px;
        font-size: 0.9rem;
        padding: 8px;
        min-width: 160px;
        margin-top: 8px;
        border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .dropdown-item {
        padding: 10px 14px;
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .dropdown-item:hover {
        background-color: #f8f9fa;
        transform: translateX(2px);
    }

    /* Mobile */
    .navbar-toggler {
        border: none;
        padding: 6px 10px;
    }

    .navbar-toggler:focus {
        box-shadow: none !important;
    }

    /* Responsive adjustments */
    @media (max-width: 576px) {
        .navbar-brand {
            font-size: 0.9rem;
        }
        
        .dropdown > button {
            padding: 5px 10px;
            font-size: 0.85rem;
        }
    }
</style>