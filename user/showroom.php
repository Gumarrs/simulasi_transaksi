<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'peserta') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$q_set = mysqli_query($conn, "
    SELECT active_period
    FROM system_settings
    LIMIT 1
");

$settings = mysqli_fetch_assoc($q_set);
$active_period = $settings['active_period'] ?? 1;

$kolom_val = "value_p" . $active_period;
$kolom_laba = "laba_p" . $active_period;

$query_showroom = mysqli_query($conn, "
    SELECT
        id,
        nama_aset,
        kategori,
        tipe_simulasi,
        gambar,
        $kolom_val AS val_now,
        $kolom_laba AS laba_now
    FROM market_assets
    WHERE group_name = 'Showroom'
    ORDER BY nama_aset ASC
");

if(!$query_showroom){
    die("Error Query Showroom: " . mysqli_error($conn));
}

$total_mobil = mysqli_num_rows($query_showroom);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Showroom Mobil</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
body {
    background: #eef2f6;
    padding-bottom: 80px;
}

.mobile-container {
    max-width: 480px;
    margin: auto;
    background: #f8fafc;
    min-height: 100vh;
    box-shadow: 0 0 18px rgba(0,0,0,0.08);
}

.header-showroom {
    background: linear-gradient(135deg, #111827, #1d4ed8);
    color: white;
    border-bottom-left-radius: 28px;
    border-bottom-right-radius: 28px;
    padding: 18px 18px 26px;
}

.back-btn {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(255,255,255,0.15);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
}

.stat-pill {
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 999px;
    padding: 6px 12px;
    font-size: 0.75rem;
}

.car-card {
    border: none;
    border-radius: 20px;
    overflow: hidden;
    background: white;
    box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
    transition: 0.2s;
}

.car-card:active {
    transform: scale(0.98);
}

.car-img-wrap {
    position: relative;
    background: #f1f5f9;
}

.car-img {
    height: 135px;
    object-fit: cover;
}

.badge-category {
    position: absolute;
    top: 10px;
    left: 10px;
    background: rgba(255,255,255,0.92);
    color: #111827;
    border-radius: 999px;
    padding: 5px 10px;
    font-size: 0.7rem;
    font-weight: 700;
}

.price-text {
    color: #0d6efd;
    font-weight: 800;
    font-size: 0.95rem;
}

.small-muted {
    color: #64748b;
    font-size: 0.72rem;
}

.btn-detail-mini {
    background: #0d6efd;
    color: white;
    border-radius: 12px;
    font-size: 0.75rem;
    padding: 7px 10px;
    font-weight: 700;
    display: inline-block;
}

.bottom-nav {
    position: fixed;
    bottom: 0;
    width: 100%;
    max-width: 480px;
    background: white;
    border-top: 1px solid #ddd;
    display: flex;
    justify-content: space-around;
    padding: 10px 0;
    z-index: 999;
}

.nav-item {
    text-decoration: none;
    color: #6c757d;
    text-align: center;
    font-size: 0.8rem;
}

.nav-item.active {
    color: #0d6efd;
    font-weight: bold;
}

.nav-item i {
    display: block;
    font-size: 1.1rem;
    margin-bottom: 2px;
}
</style>
</head>

<body>

<div class="mobile-container">

    <div class="header-showroom">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <a href="dashboard.php" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i>
            </a>

            <span class="stat-pill">
                Periode <?php echo $active_period; ?>
            </span>
        </div>

        <h4 class="fw-bold mb-1">
            Showroom Mobil
        </h4>

        <p class="mb-3 opacity-75 small">
            Pilih mobil sesuai strategi investasi Anda.
        </p>

        <div class="d-flex gap-2">
            <span class="stat-pill">
                <i class="fa-solid fa-car me-1"></i>
                <?php echo $total_mobil; ?> Mobil
            </span>

        </div>
    </div>

    <div class="p-3">

        <?php if($total_mobil == 0): ?>

            <div class="text-center py-5">
                <i class="fa-solid fa-car-side fa-3x text-muted mb-3"></i>
                <h6 class="fw-bold text-muted">Belum ada mobil</h6>
                <p class="small text-muted">
                    Data mobil showroom belum ditambahkan oleh admin.
                </p>
            </div>

        <?php else: ?>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold mb-0">Katalog Mobil</h5>
                </div>
                <i class="fa-solid fa-store text-primary"></i>
            </div>

            <div class="row g-3">

                <?php while($row = mysqli_fetch_assoc($query_showroom)) : ?>

                    <?php $harga = floatval($row['val_now']); ?>

                    <div class="col-6">

                        <a
                            href="detail_aset.php?id=<?php echo $row['id']; ?>&from=showroom"
                            class="text-decoration-none text-dark"
                        >

                            <div class="card car-card h-100">

                                <div class="car-img-wrap">
                                    <img
                                        src="../assets/img/investasi/<?php echo !empty($row['gambar']) ? $row['gambar'] : 'placeholder.jpg'; ?>"
                                        class="car-img w-100"
                                        onerror="this.src='https://via.placeholder.com/300'"
                                    >

                                    <span class="badge-category">
                                        <?php echo $row['kategori']; ?>
                                    </span>
                                </div>

                                <div class="card-body p-3">

                                    <h6 class="fw-bold mb-1" style="font-size:0.88rem; min-height:38px;">
                                        <?php echo $row['nama_aset']; ?>
                                    </h6>

                                    <div class="small-muted mb-1">
                                        Harga periode <?php echo $active_period; ?>
                                    </div>

                                    <div class="price-text mb-3">
                                        Rp <?php echo number_format($harga,0,',','.'); ?>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="small-muted">
                                            Detail
                                        </span>

                                        <span class="btn-detail-mini">
                                            Pilih
                                        </span>
                                    </div>

                                </div>

                            </div>

                        </a>

                    </div>

                <?php endwhile; ?>

            </div>

        <?php endif; ?>

    </div>

    <div class="bottom-nav shadow-lg">
        <a href="dashboard.php" class="nav-item active">
            <i class="fa-solid fa-house"></i>
            Home
        </a>

        <a href="portfolio.php" class="nav-item">
            <i class="fa-solid fa-briefcase"></i>
            Portofolio
        </a>

        <a href="leaderboard.php" class="nav-item">
            <i class="fa-solid fa-trophy"></i>
            Peringkat
        </a>

        <a href="profile.php" class="nav-item">
            <i class="fa-solid fa-user"></i>
            Profil
        </a>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>