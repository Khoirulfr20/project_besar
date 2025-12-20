<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Smart Attendance Admin')</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css">

    <!-- Theme Styling -->
    <style>
        body {
            font-size: 0.92rem;
            background: #f5f6ff;
            font-family: "Inter", sans-serif;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 240px;
            background: #ffffff;
            border-right: 1px solid #e3e6f0;
            padding-top: 70px;
            overflow-y: auto;
        }

        .sidebar .nav-link {
            color: #444;
            padding: 10px 20px;
            border-radius: 8px;
            margin: 4px 8px;
            font-weight: 500;
            transition: 0.25s;
        }

        .sidebar .nav-link:hover {
            background: rgba(102, 126, 234, 0.12);
            color: #667eea;
        }

        .sidebar .nav-link.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff !important;
            font-weight: 600;
        }

        /* Main Content */
        .main-content {
            margin-left: 240px;
            padding: 90px 25px 30px 25px;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                padding-top: 0;
            }
            .main-content {
                margin-left: 0;
                padding-top: 20px;
            }
        }

        /* Alerts with Progress Bar */
        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .alert-dismissible {
            padding-right: 3rem;
        }

        /* Progress Bar Animation */
        .alert-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 4px;
            background: rgba(255, 255, 255, 0.6);
            animation: countdown 4s linear forwards;
            border-radius: 0 0 10px 10px;
        }

        @keyframes countdown {
            from { width: 100%; }
            to { width: 0%; }
        }

        /* Alert Icons */
        .alert i {
            font-size: 1.1rem;
        }

        /* Success Alert */
        .alert-success {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
        }

        .alert-success .btn-close {
            filter: brightness(0) invert(1);
        }

        /* Danger Alert */
        .alert-danger {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: #fff;
        }

        .alert-danger .btn-close {
            filter: brightness(0) invert(1);
        }

        /* Warning Alert */
        .alert-warning {
            background: linear-gradient(135deg, #ffeaa7, #fdcb6e);
            color: #2d3436;
        }

        /* Info Alert */
        .alert-info {
            background: linear-gradient(135deg, #74b9ff, #0984e3);
            color: #fff;
        }

        .alert-info .btn-close {
            filter: brightness(0) invert(1);
        }
    </style>

    @stack('styles')
</head>

<body>

    @include('layouts.header')

    <div class="sidebar">
        @include('layouts.sidebar')
    </div>

    <main class="main-content">

        {{-- Success Alert with Progress Bar --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show position-relative overflow-hidden" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Berhasil!</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <div class="alert-progress"></div>
            </div>
        @endif

        {{-- Error Alert with Progress Bar --}}
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show position-relative overflow-hidden" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>Gagal!</strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <div class="alert-progress"></div>
            </div>
        @endif

        {{-- Warning Alert with Progress Bar --}}
        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show position-relative overflow-hidden" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Perhatian!</strong> {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <div class="alert-progress"></div>
            </div>
        @endif

        {{-- Info Alert with Progress Bar --}}
        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show position-relative overflow-hidden" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Info!</strong> {{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <div class="alert-progress"></div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- jQuery & Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>

    {{-- Auto Dismiss Alert Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Cari semua alert
            const alerts = document.querySelectorAll('.alert');
            
            alerts.forEach(function(alert) {
                // Hilangkan setelah 4 detik (sesuai animasi progress bar)
                setTimeout(function() {
                    // Tambahkan animasi fade out & slide up
                    alert.style.transition = 'all 0.5s ease';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    
                    // Hapus dari DOM setelah animasi selesai
                    setTimeout(function() {
                        alert.remove();
                    }, 500);
                }, 4000); // 4 detik (sesuai dengan durasi animasi countdown)
            });

            // Jika user klik tombol close manual
            const closeButtons = document.querySelectorAll('.alert .btn-close');
            closeButtons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const alert = this.closest('.alert');
                    alert.style.transition = 'all 0.3s ease';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    
                    setTimeout(function() {
                        alert.remove();
                    }, 300);
                });
            });
        });
    </script>

    @stack('scripts')

</body>
</html>