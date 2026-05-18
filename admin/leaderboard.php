<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$q_set = mysqli_query($conn, "SELECT active_period FROM system_settings LIMIT 1");
$settings = mysqli_fetch_assoc($q_set);

$active_period = $settings['active_period'] ?? 1;

$kolom_harga = "value_p" . $active_period;

$leaderboard = [];
$q_users = mysqli_query($conn, "SELECT id, username, nama_lengkap, balance FROM users WHERE role='peserta' AND is_assessment_done=1");

while ($u = mysqli_fetch_assoc($q_users)) {
    $uid = $u['id'];
    $portfolio_value = 0;

$q_port = mysqli_query($conn, "
    SELECT 
        a.$kolom_harga AS harga_sekarang,
        SUM(
            CASE 
                WHEN t.type = 'buy' THEN t.qty 
                ELSE 0 
            END
        ) 
        -
        SUM(
            CASE 
                WHEN t.type = 'sell' THEN t.qty 
                ELSE 0 
            END
        ) AS total_unit

    FROM transactions t

    JOIN market_assets a 
    ON t.asset_id = a.id

    WHERE t.user_id = '$uid'

    GROUP BY a.id

    HAVING total_unit > 0.0001
");

if (!$q_port) {

    die(mysqli_error($conn));

}

    while ($pt = mysqli_fetch_assoc($q_port)) {
        $portfolio_value += ($pt['total_unit'] * $pt['harga_sekarang']);
    }

    $total_wealth = $u['balance'] + $portfolio_value;
    
    $leaderboard[] = [
        'nama' => $u['nama_lengkap'],
        'cash' => $u['balance'],
        'portfolio' => $portfolio_value,
        'wealth' => $total_wealth
    ];
}

usort($leaderboard, function($a, $b) {
    return $b['wealth'] <=> $a['wealth'];
});
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Leaderboard - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fc; }
        .sidebar { min-height: 100vh; background-color: #212529; color: white; padding-top: 20px;}
        .sidebar a { color: #adb5bd; text-decoration: none; padding: 10px 20px; display: block; }
        .sidebar a:hover, .sidebar a.active { color: #fff; background-color: #343a40; border-left: 4px solid #0d6efd;}
    </style>
    <meta http-equiv="refresh" content="10">
</head>
<body>
<div class="container-fluid">
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

        <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold"><i class="fa-solid fa-trophy text-warning"></i> Live Leaderboard</h3>
                <span class="badge bg-primary fs-6">Periode <?php echo $active_period; ?></span>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th class="text-center">Peringkat</th>
                                    <th>Nama Peserta</th>
                                    <th>Sisa Uang (Cash)</th>
                                    <th>Nilai Aset (Portofolio)</th>
                                    <th class="text-success fw-bold">Total Kekayaan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rank = 1; 
                                foreach ($leaderboard as $peserta): 
                                    $medal = "";
                                    if($rank == 1) $medal = "<i class='fa-solid fa-medal text-warning fa-2x'></i>";
                                    if($rank == 2) $medal = "<i class='fa-solid fa-medal text-secondary fa-2x'></i>";
                                    if($rank == 3) $medal = "<i class='fa-solid fa-medal text-danger fa-2x' style='color:#cd7f32 !important;'></i>";
                                ?>
                                <tr class="<?php echo ($rank <= 3) ? 'bg-light fw-bold' : ''; ?>">
                                    <td class="text-center align-middle">
                                        <?php echo ($rank <= 3) ? $medal : "<span class='text-muted'>$rank</span>"; ?>
                                    </td>
                                    <td class="fs-5"><?php echo $peserta['nama']; ?></td>
                                    <td class="text-muted">Rp <?php echo number_format($peserta['cash'],0,',','.'); ?></td>
                                    <td class="text-primary">Rp <?php echo number_format($peserta['portfolio'],0,',','.'); ?></td>
                                    <td class="fs-5 text-success">Rp <?php echo number_format($peserta['wealth'],0,',','.'); ?></td>
                                </tr>
                                <?php $rank++; endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <p class="text-muted mt-3 small"><i class="fa-solid fa-rotate text-primary"></i> Halaman ini akan di-refresh otomatis setiap 10 detik.</p>
        </div>
    </div>
</div>
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