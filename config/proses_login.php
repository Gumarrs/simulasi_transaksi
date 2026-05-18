<?php
session_start();
require_once 'koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Ambil input
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // =========================
    // VALIDASI INPUT KOSONG
    // =========================

    if (empty($username)) {

        header("Location: ../login.php?error=username_kosong");
        exit;
    }

    if (empty($password)) {

        header("Location: ../login.php?error=password_kosong");
        exit;
    }

    // Bersihkan input
    $username = mysqli_real_escape_string($conn, $username);
    $password = mysqli_real_escape_string($conn, $password);

    // =========================
    // CEK USERNAME
    // =========================

    $check_user = mysqli_query($conn, "
        SELECT * FROM users
        WHERE username = '$username'
        LIMIT 1
    ");

    if (mysqli_num_rows($check_user) == 0) {

        header("Location: ../login.php?error=username_salah");
        exit;
    }

    $user = mysqli_fetch_assoc($check_user);

    // =========================
    // CEK PASSWORD
    // =========================

    if ($user['password'] != $password) {

        header("Location: ../login.php?error=password_salah");
        exit;
    }

    // =========================
    // LOGIN BERHASIL
    // =========================

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];

    // =========================
    // REDIRECT
    // =========================

    if ($user['role'] == 'admin') {

        header("Location: ../admin/dashboard.php");
        exit;

    } else {

        if ($user['is_assessment_done'] == 0) {

            header("Location: ../user/assessment_step1.php");
            exit;

        } else {

            header("Location: ../user/dashboard.php");
            exit;
        }
    }

} else {

    header("Location: ../login.php");
    exit;
}
?>