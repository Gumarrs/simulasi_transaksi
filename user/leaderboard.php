<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'peserta') {
    header("Location: ../login.php");
    exit;
}

$user_id_sekarang = $_SESSION['user_id'];

// Ambil Periode Aktif
$q_set = mysqli_query($conn, "SELECT active_period FROM system_settings LIMIT 1");
$settings = mysqli_fetch_assoc($q_set);
$active_period = $settings['active_period'] ?? 1;

// UPDATE LOGIKA: Kolom valuasi menyesuaikan struktur baru
$kolom_val = "value_p" . $active_period;

// ARRAY KOSONG UNTUK MENAMPUNG PERINGKAT
$leaderboard = [];

// 1. Ambil semua user yang sudah assessment
$q_users = mysqli_query($conn, "SELECT id, username, nama_lengkap, balance FROM users WHERE role='peserta' AND is_assessment_done=1");

while ($u = mysqli_fetch_assoc($q_users)) {
    $uid = $u['id'];
    $portfolio_value = 0;

    // 2. Hitung nilai aset user ini berdasarkan valuasi periode aktif
    $q_port = mysqli_query($conn, "
        SELECT 
            a.tipe_simulasi,
            a.$kolom_val AS val_now,
            SUM(CASE WHEN t.type = 'buy' THEN t.qty ELSE 0 END) - SUM(CASE WHEN t.type = 'sell' THEN t.qty ELSE 0 END) AS total_unit
        FROM transactions t
        JOIN market_assets a ON t.asset_id = a.id
        WHERE t.user_id = '$uid'
        GROUP BY a.id
        HAVING total_unit > 0.0001
    ");

    while ($pt = mysqli_fetch_assoc($q_port)) {
        if ($pt['tipe_simulasi'] == 'persentase') {
            // Jika deposito, obligasi, dll (persentase) -> unit = nominal uang
            $portfolio_value += $pt['total_unit'];
        } else {
            // Jika saham, emas, bisnis -> unit x harga_sekarang
            $portfolio_value += ($pt['total_unit'] * $pt['val_now']);
        }
    }

    // 3. Total Kekayaan/Profit = Saldo Uang + Nilai Aset Portofolio
    $total_wealth = $u['balance'] + $portfolio_value;
    
    // Masukkan ke array
    $leaderboard[] = [
        'id' => $u['id'],
        'nama' => $u['nama_lengkap'],
        'wealth' => $total_wealth
    ];
}

// 4. Sorting Array berdasarkan Total Kekayaan (Descending/Terbesar ke Terkecil)
usort($leaderboard, function($a, $b) {
    return $b['wealth'] <=> $a['wealth'];
});
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peringkat Profit - Simulasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; padding-bottom: 75px; }
        .mobile-container { max-width: 480px; margin: auto; background: #fff; min-height: 100vh; box-shadow: 0 0 15px rgba(0,0,0,0.05); position: relative;}
        .bottom-nav { position: fixed; bottom: 0; width: 100%; max-width: 480px; background: #fff; border-top: 1px solid #ddd; z-index: 1000; display: flex; justify-content: space-around; padding: 10px 0; }
        .nav-item { text-align: center; color: #6c757d; text-decoration: none; font-size: 0.8rem; }
        .nav-item.active { color: #0d6efd; font-weight: bold; }
        .nav-item i { font-size: 1.2rem; display: block; margin-bottom: 2px; }
        
        .rank-1 { background: linear-gradient(135deg, #ffd700, #daa520); color: #000; border: none; }
        .rank-2 { background: linear-gradient(135deg, #e0e0e0, #a9a9a9); color: #000; border: none; }
        .rank-3 { background: linear-gradient(135deg, #cd7f32, #8b4513); color: #fff; border: none; }
        .rank-highlight { border: 2px solid #0d6efd; background-color: #e9f2ff; }
    </style>
</head>
<body>

<div class="mobile-container">
    <div class="p-3 border-bottom bg-white sticky-top text-center">
        <h5 class="fw-bold text-primary mb-0"><i class="fa-solid fa-trophy text-warning"></i> Peringkat Profit Tertinggi</h5>
        <small class="text-muted">Periode <?php echo $active_period; ?></small>
    </div>

    <div class="p-3">
        <?php 
        $rank = 1;
        foreach ($leaderboard as $peserta): 
            $card_class = "bg-white border shadow-sm";
            $badge = "<span class='fw-bold text-muted'>#$rank</span>";
            
            if ($rank == 1) { 
                $card_class = "rank-1 shadow"; 
                $badge = "<i class='fa-solid fa-crown fa-lg'></i>"; 
            } elseif ($rank == 2) { 
                $card_class = "rank-2 shadow-sm"; 
            } elseif ($rank == 3) { 
                $card_class = "rank-3 shadow-sm"; 
            }

            if ($peserta['id'] == $user_id_sekarang && $rank > 3) {
                $card_class = "rank-highlight";
            }
        ?>
        <div class="card mb-2 <?php echo $card_class; ?>" style="border-radius: 12px;">
            <div class="card-body p-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="text-center me-3" style="width: 30px;">
                        <?php echo $badge; ?>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">
                            <?php echo $peserta['nama']; ?>
                            <?php if($peserta['id'] == $user_id_sekarang) echo "<span class='badge bg-primary ms-1' style='font-size:0.6rem;'>SAYA</span>"; ?>
                        </h6>
                    </div>
                </div>
                <div class="text-end">
                    <small class="d-block" style="font-size: 0.7rem; opacity: 0.8;">Total Aset (Profit)</small>
                    <span class="fw-bold <?php echo ($rank <= 2) ? 'text-dark' : 'text-success'; ?>">
                        Rp <?php echo number_format($peserta['wealth'], 0, ',', '.'); ?>
                    </span>
                </div>
            </div>
        </div>
        <?php $rank++; endforeach; ?>
        
        <?php if(empty($leaderboard)): ?>
            <div class="text-center text-muted mt-5 pt-5">
                <i class="fa-solid fa-ranking-star fa-3x mb-3 opacity-25"></i>
                <p>Belum ada data peringkat.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="bottom-nav shadow-lg">
        <a href="dashboard.php" class="nav-item"><i class="fa-solid fa-house"></i>Home</a>
        <a href="portfolio.php" class="nav-item"><i class="fa-solid fa-briefcase"></i>Portofolio</a>
        <a href="leaderboard.php" class="nav-item active"><i class="fa-solid fa-trophy"></i>Peringkat</a>
        <a href="profile.php" class="nav-item"><i class="fa-solid fa-user"></i>Profil</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>