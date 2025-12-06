<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login • Smart Attendance</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #667eea, #764ba2);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: "Inter", Arial, sans-serif;
        }

        .login-card {
            width: 100%;
            max-width: 380px;
            background: #fff;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.18);
            animation: fadeIn .6s ease-out;
        }

        .login-title {
            text-align: center;
            font-weight: 700;
            font-size: 1.4rem;
            color: #444;
        }

        .icon-wrap {
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            margin: 0 auto 20px;
            color: white;
            font-size: 2rem;
        }

        /* Inputs */
        .input-group-text {
            border-color: #667eea;
            background: #f7f8ff;
        }

        .form-control {
            border-color: #667eea;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.17rem rgba(102, 126, 234, .25);
        }

        /* Toggle Password Button */
        #togglePassword {
            border-left: 0;
            border-color: #667eea;
            color: #667eea;
        }

        #togglePassword:hover {
            background: rgba(102, 126, 234, 0.12);
            color: #667eea;
        }

        /* Login Button */
        .btn-login {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            padding: 12px;
            font-weight: 600;
        }

        .btn-login:hover {
            opacity: .9;
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body>
    <div class="login-card">
        

        <div class="icon-wrap">
            <i class="fas fa-user-shield"></i>
        </div>

        <h3 class="login-title mb-4">Login Admin</h3>

        <form id="loginForm" method="POST" action="{{ route('admin.login.post') }}">
            @csrf

            <!-- Email -->
            <div class="mb-3">
                <label class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
            </div>

            <!-- Password -->
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <button type="button" class="btn" id="togglePassword">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
            </div>

            <button class="btn btn-primary btn-login w-100 mt-2" id="loginBtn">
                <i class="fa fa-sign-in-alt me-2"></i> Login
            </button>
        </form>
    </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Toggle Password Visibility
    $("#togglePassword").click(function() {
        let pass = $("#password");
        let icon = $(this).find("i");

        pass.attr("type", pass.attr("type") === "password" ? "text" : "password");
        icon.toggleClass("fa-eye fa-eye-slash");
    });

    // Form Submit Validation
    $("#loginForm").submit(function(e) {
        e.preventDefault();

        const email = $("#email").val().trim();
        const pass = $("#password").val().trim();

        if (!email) {
            return Swal.fire("Email Kosong", "Masukkan email Anda", "warning");
        }
        if (!pass) {
            return Swal.fire("Password Kosong", "Masukkan password Anda", "warning");
        }

        // Loading
        Swal.fire({
            title: "Memverifikasi...",
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading()
        });

        $("#loginBtn").prop("disabled", true).html(`
            <span class="spinner-border spinner-border-sm"></span> Memverifikasi...
        `);

        this.submit();
    });

    // Server-side Error
    @if ($errors->any())
        Swal.fire({
            icon: "error",
            title: "Login Gagal",
            html: `{!! implode('<br>', $errors->all()) !!}`
        });
    @endif

    // Success
    @if (session("success"))
        Swal.fire({
            icon: "success",
            title: "Berhasil!",
            text: "{{ session('success') }}"
        });
    @endif
</script>

</body>
</html>
