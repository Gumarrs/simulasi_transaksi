<?php
session_start();
require_once '../config/koneksi.php';

// WIB / Jakarta
date_default_timezone_set('Asia/Jakarta');

// Proteksi akses: Hanya admin yang boleh masuk
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Proses Update Pengaturan Periode & Timer
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    $new_period = (int)$_POST['active_period'];
    $status = $_POST['period_status'];
    $duration = (int)$_POST['duration_minutes']; // Durasi dalam menit

    // Ambil periode sistem saat ini sebelum diubah
    $q_curr = mysqli_query($conn, "SELECT active_period FROM system_settings LIMIT 1");
    $curr_set = mysqli_fetch_assoc($q_curr);
    $old_period = (int)$curr_set['active_period'];

    // LOGIKA BURN RATE: Jika periode NAIK
    if ($new_period > $old_period) {

        // Ambil semua peserta yang sudah selesai assessment
        $peserta_aktif = mysqli_query($conn, "
            SELECT id, balance, monthly_expense
            FROM users
            WHERE role = 'peserta'
            AND is_assessment_done = 1
        ");

        while ($p = mysqli_fetch_assoc($peserta_aktif)) {

            // Hitung biaya hidup 2 tahun (24 bulan)
            $burn_amount = $p['monthly_expense'] * 24;

            $saldo_baru = $p['balance'] - $burn_amount;

            // Update saldo dan status periode tiap peserta
            mysqli_query($conn, "
                UPDATE users
                SET balance = '$saldo_baru',
                    current_period = '$new_period'
                WHERE id = '{$p['id']}'
            ");
        }

        $pesan_sukses = "Periode dinaikkan ke $new_period! Biaya hidup (Burn Rate) 24 bulan telah dipotong otomatis dari saldo semua peserta.";

    } else {

        $pesan_sukses = "Pengaturan Status Market dan Timer berhasil diperbarui!";
    }

// ===============================================
    // Eksekusi update tabel settings (PERBAIKAN TIMER)
    // ===============================================
    if ($status == 'open') {
        
        if ($duration > 0) {
            // Jika admin mengisi waktu (> 0), atur waktu selesainya
            $end_time = date('Y-m-d H:i:s', strtotime("+$duration minutes"));
            
            $query_update = "
                UPDATE system_settings
                SET active_period = '$new_period',
                    period_status = '$status',
                    end_time = '$end_time'
            ";
        } else {
            // Jika admin mengisi 0 / kosong, jadikan timer UNLIMITED (NULL)
            $query_update = "
                UPDATE system_settings
                SET active_period = '$new_period',
                    period_status = '$status',
                    end_time = NULL
            ";
        }

    } else {
        // Jika status CLOSED, otomatis matikan timer (NULL)
        $query_update = "
            UPDATE system_settings
            SET active_period = '$new_period',
                period_status = '$status',
                end_time = NULL
        ";
    }

    mysqli_query($conn, $query_update);
}

// Ambil data pengaturan saat ini
$q_set = mysqli_query($conn, "SELECT * FROM system_settings LIMIT 1");
$settings = mysqli_fetch_assoc($q_set);

// Statistik Singkat Peserta
$q_stats = mysqli_query($conn, "
    SELECT 
        COUNT(id) AS total_peserta,
        SUM(CASE WHEN is_assessment_done = 1 THEN 1 ELSE 0 END) AS selesai_assessment,
        SUM(balance) AS total_uang_beredar
    FROM users
    WHERE role = 'peserta'
");

$stats = mysqli_fetch_assoc($q_stats);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Simulasi Pensiun</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background-color: #f8f9fc;
        }

        .sidebar {
            min-height: 100vh;
            background-color: #212529;
            color: white;
            padding-top: 20px;
        }

        .sidebar a {
            color: #adb5bd;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
        }

        .sidebar a:hover,
        .sidebar a.active {
            color: #fff;
            background-color: #343a40;
            border-left: 4px solid #0d6efd;
        }
    </style>
</head>

<body>

<div class="container-fluid">
    <!-- Navbar Mobile -->
<div class="d-md-none bg-dark text-white p-3 d-flex justify-content-between align-items-center">

    <h5 class="mb-0 fw-bold">
        <i class="fa-solid fa-chart-line text-primary"></i>
        Admin Panel
    </h5>

    <button class="btn btn-outline-light"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#sidebarMenu">

        <i class="fa-solid fa-bars"></i>

    </button>

</div>

    <div class="row">

        <!-- SIDEBAR -->
       <div class="col-md-2 sidebar offcanvas-md offcanvas-start text-bg-dark"
     tabindex="-1"
     id="sidebarMenu">

            <h5 class="px-3 mb-4 fw-bold">
                <i class="fa-solid fa-chart-line text-primary"></i>
                Admin Panel
            </h5>

            <a href="dashboard.php" class="active">
                <i class="fa-solid fa-gauge me-2"></i>
                Dashboard
            </a>

            <a href="kelola_aset.php">
                <i class="fa-solid fa-boxes-stacked me-2"></i>
                Kelola Investasi
            </a>

            <a href="data_peserta.php">
                <i class="fa-solid fa-users me-2"></i>
                Data Peserta
            </a>

            <a href="leaderboard.php">
                <i class="fa-solid fa-trophy me-2"></i>
                Leaderboard
            </a>

            <div class="mt-5 px-3">

        <a href="#"
       onclick="confirmLogout()"
       class="btn btn-danger btn-sm w-100 fw-bold">

        <i class="fa-solid fa-right-from-bracket me-1"></i>
        Keluar

    </a>

            </div>

        </div>

        <!-- CONTENT -->
        <div class="col-md-10 p-4">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <h3 class="fw-bold">
                    Control Center
                </h3>

                <span class="text-muted">
                    Tanggal:
                    <?php echo date('d M Y H:i:s'); ?> WIB
                </span>

            </div>

            <!-- ALERT -->
            <?php if(isset($pesan_sukses)): ?>

                <div class="alert alert-success alert-dismissible fade show" role="alert">

                    <i class="fa-solid fa-circle-check"></i>

                    <?php echo $pesan_sukses; ?>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="alert"
                            aria-label="Close">
                    </button>

                </div>

            <?php endif; ?>

            <div class="row">

                <!-- PENGATURAN -->
                <div class="col-lg-6 mb-4">

                    <div class="card shadow-sm border-0 h-100">

                        <div class="card-header bg-white fw-bold">

                            <i class="fa-solid fa-stopwatch text-danger"></i>

                            Pengaturan Market & Periode

                        </div>

                        <div class="card-body">

                            <form action="dashboard.php"
                                  method="POST"
                                  id="settingsForm">

                                <div class="row mb-3">

                                    <!-- PERIODE -->
                                    <div class="col-md-6">

                                        <label class="form-label small fw-bold">
                                            Periode Aktif
                                        </label>

                                        <select class="form-select"
                                                name="active_period">

                                            <option value="1"
                                                <?php if($settings['active_period'] == 1) echo 'selected'; ?>>

                                                Periode 1 (Awal)

                                            </option>

                                            <option value="2"
                                                <?php if($settings['active_period'] == 2) echo 'selected'; ?>>

                                                Periode 2 (Tengah)

                                            </option>

                                            <option value="3"
                                                <?php if($settings['active_period'] == 3) echo 'selected'; ?>>

                                                Periode 3 (Akhir)

                                            </option>

                                        </select>

                                    </div>

                                    <!-- STATUS -->
                                    <div class="col-md-6">

                                        <label class="form-label small fw-bold">
                                            Status Market
                                        </label>

                                        <select class="form-select <?php echo ($settings['period_status']=='open') ? 'border-success' : 'border-danger'; ?>"
                                                name="period_status">

                                            <option value="open"
                                                <?php if($settings['period_status'] == 'open') echo 'selected'; ?>>

                                                OPEN (Bisa Transaksi)

                                            </option>

                                            <option value="closed"
                                                <?php if($settings['period_status'] == 'closed') echo 'selected'; ?>>

                                                CLOSED (Terkunci)

                                            </option>

                                        </select>

                                    </div>

                                </div>

                                <!-- TIMER -->
                                <div class="mb-3">

                                    <label class="form-label small fw-bold">
                                        Set Timer (Menit)
                                    </label>

                                    <input type="number"
                                           class="form-control"
                                           name="duration_minutes"
                                           placeholder="Contoh: 15"
                                           min="0">

                                    <small class="text-muted">
                                        Kosongkan/isi 0 jika hanya ingin mengubah status tanpa mereset timer.
                                    </small>

                                </div>

                                <!-- BUTTON -->
                                <button type="button"
                                        class="btn btn-primary w-100 fw-bold"
                                        data-bs-toggle="modal"
                                        data-bs-target="#confirmModal">

                                    Update Pengaturan

                                </button>

                            </form>

                        </div>

                        <!-- FOOTER TIMER -->
                        <?php if($settings['end_time'] != null && $settings['period_status'] == 'open'): ?>

                        <div class="card-footer bg-light text-center">

                            <small class="text-muted">

                                Market akan ditutup otomatis pada:

                                <br>

                                <strong>
                                    <?php echo date('H:i:s', strtotime($settings['end_time'])); ?> WIB
                                </strong>

                            </small>

                        </div>

                        <?php endif; ?>

                    </div>

                </div>

                <!-- STATISTIK -->
                <div class="col-lg-6 mb-4">

                    <div class="row">

                        <!-- TOTAL PESERTA -->
                        <div class="col-md-6 mb-3">

                            <div class="card shadow-sm border-0 bg-primary text-white h-100">

                                <div class="card-body">

                                    <h6 class="card-title opacity-75">
                                        Total Peserta
                                    </h6>

                                    <h2 class="fw-bold mb-0">

                                        <?php echo $stats['total_peserta']; ?>

                                        <i class="fa-solid fa-users float-end opacity-50"></i>

                                    </h2>

                                </div>

                            </div>

                        </div>

                        <!-- ASSESSMENT -->
                        <div class="col-md-6 mb-3">

                            <div class="card shadow-sm border-0 bg-success text-white h-100">

                                <div class="card-body">

                                    <h6 class="card-title opacity-75">
                                        Selesai Assessment
                                    </h6>

                                    <h2 class="fw-bold mb-0">

                                        <?php echo $stats['selesai_assessment']; ?>

                                        <i class="fa-solid fa-check-double float-end opacity-50"></i>

                                    </h2>

                                </div>

                            </div>

                        </div>

                        <!-- UANG BEREDAR -->
                        <div class="col-12">

                            <div class="card shadow-sm border-0 h-100">

                                <div class="card-body">

                                    <h6 class="card-title text-muted fw-bold">
                                        Total Uang Beredar (Saldo User)
                                    </h6>

                                    <h3 class="fw-bold text-dark">

                                        Rp
                                        <?php echo number_format($stats['total_uang_beredar'], 0, ',', '.'); ?>

                                    </h3>

                                    <small class="text-muted">
                                        Gabungan seluruh sisa saldo peserta saat ini.
                                    </small>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<!-- MODAL KONFIRMASI -->
<div class="modal fade" id="confirmModal" tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content border-0 shadow">

            <div class="modal-header">

                <h5 class="modal-title">

                    <i class="fa-solid fa-triangle-exclamation text-warning"></i>

                    Konfirmasi Perubahan

                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body">

                Apakah Anda yakin ingin mengubah pengaturan market dan periode?

                <div class="alert alert-warning mt-3 mb-0">

                    <small>

                        Jika periode dinaikkan,
                        sistem akan otomatis menjalankan
                        <strong>Burn Rate 24 bulan</strong>
                        kepada seluruh peserta.

                    </small>

                </div>

            </div>

            <div class="modal-footer">

                <button type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">

                    Batal

                </button>

                <button type="submit"
                        form="settingsForm"
                        name="update_settings"
                        class="btn btn-primary">

                    Ya, Update

                </button>

            </div>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>

function confirmLogout() {

    Swal.fire({

        icon: 'question',

        title: 'Konfirmasi Logout',

        text: 'Apakah Anda yakin ingin keluar dari panel admin?',

        showCancelButton: true,

        confirmButtonText: 'Ya, Keluar',

        cancelButtonText: 'Batal',

        confirmButtonColor: '#dc3545',

        cancelButtonColor: '#6c757d',

        reverseButtons: true,

        background: '#ffffff',

        color: '#212529'

    }).then((result) => {

        if(result.isConfirmed) {

            Swal.fire({

                title: 'Sedang Logout...',

                html: 'Mohon tunggu sebentar',

                allowOutsideClick: false,

                allowEscapeKey: false,

                showConfirmButton: false,

                didOpen: () => {

                    Swal.showLoading();

                }

            });

            setTimeout(() => {

                window.location.href = '../config/logout.php';

            }, 800);

        }

    });

}

</script>
</body>
</html>