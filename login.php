<?php
session_start();

// =====================================
// JIKA SUDAH LOGIN
// =====================================

if(isset($_SESSION['user_id'])){

    if($_SESSION['role'] === 'admin'){

        header("Location: admin/dashboard.php");

    } else {

        header("Location: user/dashboard.php");
    }

    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login Simulasi Pensiun</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <style>

        body{
            background-color:#e9ecef;
        }

        .mobile-container{

            max-width:480px;

            margin:0 auto;

            background:#fff;

            min-height:100vh;

            box-shadow:0 0 25px rgba(0,0,0,0.1);

            display:flex;

            flex-direction:column;

            position:relative;
        }

        .login-wrapper{

            flex-grow:1;

            display:flex;

            flex-direction:column;

            justify-content:center;

            padding:40px 30px;
        }

        .back-btn{

            position:absolute;

            top:20px;

            left:20px;

            color:#6c757d;

            text-decoration:none;

            font-size:0.9rem;

            font-weight:500;
        }

        .back-btn:hover{

            color:#0d6efd;
        }

        .form-control{

            border-radius:10px;

            padding:12px 15px;
        }

        .btn-login{

            border-radius:10px;

            padding:12px;

            font-size:1.05rem;
        }

        .input-group-text{

            border-radius:10px 0 0 10px !important;
        }

        .password-toggle{

            border-radius:0 10px 10px 0 !important;
        }

    </style>

</head>

<body>

<div class="mobile-container">

    <a href="index.php" class="back-btn">

        <i class="fa-solid fa-arrow-left me-1"></i>

        Beranda

    </a>

    <div class="login-wrapper">

        <!-- HEADER -->
        <div class="text-center mb-5">

            <div
                class="bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3 shadow-sm"
                style="width:70px;height:70px;border-radius:20px;"
            >
                <i class="fa-solid fa-wallet fa-2x"></i>
            </div>

            <h3 class="text-primary fw-bold mb-1">
                Simulasi Transaksi
            </h3>

            <p class="text-muted small">
                Silakan masuk menggunakan ID Peserta
            </p>

        </div>

        <!-- ALERT ERROR -->
        <?php if(isset($_GET['error'])): ?>

            <div class="alert alert-danger border-0 shadow-sm rounded-3 small py-2">

                <i class="fa-solid fa-circle-exclamation me-1"></i>

                <?php

                    if($_GET['error'] == 'username_kosong'){

                        echo "ID peserta wajib diisi.";
                    }

                    elseif($_GET['error'] == 'password_kosong'){

                        echo "Kata sandi wajib diisi.";
                    }

                    elseif($_GET['error'] == 'username_salah'){

                        echo "ID peserta tidak ditemukan.";
                    }

                    elseif($_GET['error'] == 'password_salah'){

                        echo "Kata sandi yang anda masukkan salah.";
                    }

                    else{

                        echo "Login gagal. Silakan coba kembali.";
                    }

                ?>

            </div>

        <?php endif; ?>

        <!-- FORM LOGIN -->
        <form
            action="config/proses_login.php"
            method="POST"
            id="loginForm"
            autocomplete="off"
        >

            <!-- USERNAME -->
            <div class="mb-3">

                <label
                    for="username"
                    class="form-label fw-bold small text-secondary"
                >
                    ID Peserta
                </label>

                <div class="input-group">

                    <span class="input-group-text bg-light border-0">

                        <i class="fa-solid fa-user text-secondary"></i>

                    </span>

                    <input
                        type="text"
                        class="form-control bg-light border-0"
                        id="username"
                        name="username"
                        placeholder="Misal: USER001"
                        required
                    >

                </div>

            </div>

            <!-- PASSWORD -->
            <div class="mb-4">

                <label
                    for="password"
                    class="form-label fw-bold small text-secondary"
                >
                    Kata Sandi
                </label>

                <div class="input-group">

                    <span class="input-group-text bg-light border-0">

                        <i class="fa-solid fa-lock text-secondary"></i>

                    </span>

                    <input
                        type="password"
                        class="form-control bg-light border-0"
                        id="password"
                        name="password"
                        placeholder="••••••••"
                        required
                    >

                    <button
                        type="button"
                        class="btn bg-light border-0 password-toggle"
                        onclick="togglePassword()"
                    >
                        <i class="fa-solid fa-eye" id="eyeIcon"></i>
                    </button>

                </div>

            </div>

            <!-- BUTTON -->
            <button
                type="submit"
                class="btn btn-primary w-100 fw-bold btn-login shadow-sm"
                id="loginBtn"
            >
                Masuk Simulasi
            </button>

        </form>

    </div>

</div>

<!-- SWEETALERT -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- BOOTSTRAP -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>

// =====================================
// TOGGLE PASSWORD
// =====================================

function togglePassword(){

    const pass =
        document.getElementById('password');

    const eye =
        document.getElementById('eyeIcon');

    if(pass.type === 'password'){

        pass.type = 'text';

        eye.classList.remove('fa-eye');

        eye.classList.add('fa-eye-slash');

    } else {

        pass.type = 'password';

        eye.classList.remove('fa-eye-slash');

        eye.classList.add('fa-eye');
    }
}


// =====================================
// RESET SAAT BACK BROWSER
// =====================================

window.addEventListener('pageshow', function(event){

    // Tutup swal jika masih ada
    Swal.close();

    // Reset tombol login
    const btn =
        document.getElementById('loginBtn');

    if(btn){

        btn.disabled = false;

        btn.innerHTML = 'Masuk Simulasi';
    }

});


// =====================================
// VALIDASI + LOADING LOGIN
// =====================================

document
.getElementById('loginForm')
.addEventListener('submit', function(e){

    const username =
        document.getElementById('username')
        .value
        .trim();

    const password =
        document.getElementById('password')
        .value
        .trim();

    // VALIDASI USERNAME
    if(username === ''){

        e.preventDefault();

        Swal.fire({

            icon:'warning',

            title:'ID Peserta Kosong',

            text:'Silakan masukkan ID peserta terlebih dahulu.'

        });

        return;
    }

    // VALIDASI PASSWORD
    if(password === ''){

        e.preventDefault();

        Swal.fire({

            icon:'warning',

            title:'Password Kosong',

            text:'Silakan masukkan kata sandi.'

        });

        return;
    }

    // Disable tombol
    const btn =
        document.getElementById('loginBtn');

    btn.disabled = true;

    btn.innerHTML = `
        <span class="spinner-border spinner-border-sm me-2"></span>
        Memproses...
    `;

    // Loading popup
    Swal.fire({

        title:'Sedang Masuk...',

        html:'Memverifikasi akun peserta',

        allowOutsideClick:false,

        allowEscapeKey:false,

        showConfirmButton:false,

        didOpen: () => {

            Swal.showLoading();

        }

    });

});

</script>

</body>
</html>