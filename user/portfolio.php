<?php
session_start();
require_once '../config/koneksi.php';
require_once 'auto_cair_deposito.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'peserta') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ======================================
// AMBIL SALDO USER
// ======================================

$q_user = mysqli_query($conn, "
    SELECT balance
    FROM users
    WHERE id = '$user_id'
");

$user_data = mysqli_fetch_assoc($q_user);

$cash_balance = floatval($user_data['balance']);


// ======================================
// AMBIL SETTING SISTEM
// ======================================

$q_set = mysqli_query($conn, "
    SELECT active_period, period_status
    FROM system_settings
    LIMIT 1
");

$settings = mysqli_fetch_assoc($q_set);

$active_period = $settings['active_period'] ?? 1;

$period_status = $settings['period_status'];


// ======================================
// KOLOM PERIODE AKTIF
// ======================================

$kolom_val = "value_p" . $active_period;

$kolom_laba = "laba_p" . $active_period;


// ======================================
// QUERY PORTFOLIO
// ======================================

$query_portfolio = mysqli_query($conn, "

    SELECT

        a.id AS asset_id,
        a.nama_aset,
        a.group_name,
        a.kategori,
        a.tipe_simulasi,
        a.multiplier,
        a.gambar,

        a.$kolom_val AS val_now,
        a.$kolom_laba AS laba_now,

        SUM(
            CASE
                WHEN t.type='buy' THEN t.qty
                WHEN t.type='sell' THEN -t.qty
                ELSE 0
            END
        ) AS total_unit,

        SUM(
            CASE
                WHEN t.type='buy' AND a.tipe_simulasi='persentase' THEN t.amount_money
                WHEN t.type='sell' AND a.tipe_simulasi='persentase' AND t.qty > 0 THEN -t.amount_money
                WHEN t.type='buy' THEN t.qty * t.buy_price
                WHEN t.type='sell' THEN -(t.qty * t.buy_price)
                ELSE 0
            END
        ) AS total_modal_aktif,

        MAX(
            CASE
                WHEN t.type='buy' THEN t.buy_period
                ELSE 0
            END
        ) AS last_buy_period

    FROM transactions t

    JOIN market_assets a
        ON t.asset_id = a.id

    WHERE t.user_id = '$user_id'

    GROUP BY a.id

    HAVING total_unit > 0.0001

");


// ======================================
// DETAIL TRANSAKSI PER PERIODE
// ======================================

$history_detail = [];

$q_detail = mysqli_query($conn, "

    SELECT

        t.period,
        t.type,
        t.qty,
        t.amount_money,
        t.realized_profit,
        t.buy_period,
        a.nama_aset,
        a.group_name,
        a.tipe_simulasi

    FROM transactions t

    JOIN market_assets a
        ON t.asset_id = a.id

    WHERE t.user_id = '$user_id'

    ORDER BY t.period ASC, t.id ASC

");

while($d = mysqli_fetch_assoc($q_detail)) {

    $period = $d['period'];

    if(!isset($history_detail[$period])) {

        $history_detail[$period] = [
            'buy_total' => 0,
            'sell_total' => 0,
            'profit_total' => 0,
            'buy_items' => [],
            'sell_items' => []
        ];
    }

    // BUY
    if($d['type'] == 'buy') {

        $history_detail[$period]['buy_total'] += $d['amount_money'];

        $label_aset =
        !empty($d['group_name'])
        ? $d['group_name'].' - '.$d['nama_aset']
        : $d['nama_aset'];

        $history_detail[$period]['buy_items'][] = [
            'aset' => $label_aset,
            'nominal' => $d['amount_money']
        ];
    }

// CAIR PROFIT DEPOSITO / LABA BISNIS (Tipe Sell tapi QTY = 0)
    elseif($d['type'] == 'sell' && floatval($d['qty']) == 0) {
        
        $history_detail[$period]['profit_total'] += $d['realized_profit'];
        $p_profit = $d['buy_period']; 
        
        // Pembedaan Nama Label berdasarkan Tipe Simulasi
    if ($d['tipe_simulasi'] == 'bisnis')
    {

        $label_aset=
        'Laba Bisnis '
        .$d['nama_aset']
        .' (Periode '
        .$p_profit.
        ')';

    }
    elseif(
    $d['tipe_simulasi']=='edukasi'
    )
    {

        $label_aset=
        'Penghasilan bertambah setelah investasi pendidikan anda di periode '
        .$p_profit;

    }
    else
    {
        $label_aset=
        'Pencairan Profit '
        .$d['nama_aset']
        .' (Periode '
        .$p_profit.
        ')';
    }

    $history_detail[$period]['sell_items'][] = [
            'aset' => $label_aset,
            'nominal' => $d['amount_money']
        ];
    }
    
    // SELL NORMAL (Jual Pokok Aset / Saham)
    elseif($d['type'] == 'sell' && floatval($d['qty']) > 0) {

        $history_detail[$period]['sell_total'] += $d['amount_money'];

        $history_detail[$period]['profit_total'] += $d['realized_profit'];

        $label_aset =
        !empty($d['group_name'])
        ? $d['group_name'].' - '.$d['nama_aset']
        : $d['nama_aset'];

        $history_detail[$period]['sell_items'][] = [
            'aset' => $label_aset,
            'nominal' => $d['amount_money']
        ];
    }
}


$total_portfolio_value = 0;
$punya_history = !empty($history_detail);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Portofolio Saya</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <style>
body {
    background:#eef2f7;
    padding-bottom:85px;
    font-family:'Segoe UI',sans-serif;
}

.mobile-container {
    max-width:480px;
    margin:auto;
    background:#f8fafc;
    min-height:100vh;
    box-shadow:0 0 18px rgba(15,23,42,.08);
    position:relative;
}

.page-header {
    background:linear-gradient(135deg,#111827,#0d6efd);
    color:white;
    padding:22px 18px 48px;
    border-bottom-left-radius:30px;
    border-bottom-right-radius:30px;
    position:sticky;
    top:0;
    z-index:99;
}

.header-pill {
    display:inline-block;
    background:rgba(255,255,255,.16);
    border:1px solid rgba(255,255,255,.22);
    color:white;
    border-radius:999px;
    padding:5px 12px;
    font-size:.72rem;
    font-weight:700;
}

.portfolio-card {
    border:none;
    border-radius:20px;
    box-shadow:0 8px 22px rgba(15,23,42,.08);
    overflow:hidden;
    background:white;
}

.asset-img {
    width:54px;
    height:54px;
    object-fit:contain;
    background:#f1f5f9;
    border-radius:16px;
    padding:5px;
}

.asset-name {
    font-size:.92rem;
    font-weight:800;
    color:#111827;
}

.asset-badge {
    background:#f1f5f9;
    color:#334155;
    border-radius:999px;
    padding:5px 10px;
    font-size:.68rem;
    font-weight:700;
}

.metric-box {
    background:#f8fafc;
    border:1px solid #e5e7eb;
    border-radius:16px;
    padding:11px;
}

.metric-label {
    font-size:.68rem;
    color:#64748b;
}

.metric-value {
    font-size:.9rem;
    font-weight:800;
    color:#111827;
}

.wealth-card {
    background:linear-gradient(135deg,#111827,#1e293b);
    color:white;
    border:none;
    border-radius:22px;
    box-shadow:0 10px 26px rgba(15,23,42,.18);
}

.history-card {
    border:none;
    border-radius:20px;
    box-shadow:0 8px 22px rgba(15,23,42,.08);
    overflow:hidden;
}

.history-button {
    background:white;
    border:none;
    border-bottom:1px solid #f1f5f9;
}

.history-period {
    font-weight:800;
    color:#111827;
}

.transaction-row {
    display:flex;
    justify-content:space-between;
    gap:12px;
    font-size:.78rem;
    padding:6px 0;
    border-bottom:1px dashed #e5e7eb;
}

.transaction-row:last-child {
    border-bottom:none;
}

.empty-state {
    background:white;
    border-radius:22px;
    box-shadow:0 8px 22px rgba(15,23,42,.08);
    padding:34px 18px;
}

.bottom-nav {
    position:fixed;
    bottom:0;
    width:100%;
    max-width:480px;
    background:white;
    border-top:1px solid #e5e7eb;
    z-index:1000;
    display:flex;
    justify-content:space-around;
    padding:10px 0;
}

.nav-item {
    text-align:center;
    color:#6c757d;
    text-decoration:none;
    font-size:.78rem;
}

.nav-item.active {
    color:#0d6efd;
    font-weight:bold;
}

.nav-item i {
    font-size:1.15rem;
    display:block;
    margin-bottom:2px;
}
    </style>

</head>

<body>

<div class="mobile-container">

    <div class="page-header text-center">

        <span class="header-pill mb-2">
            Periode <?php echo $active_period; ?>
        </span>

        <h5 class="fw-bold mb-0">
            Portofolio Investasi
        </h5>

        <small class="opacity-75">
            Ringkasan aset dan hasil transaksi Anda
        </small>

    </div>

    <div class="p-3">

        <?php if(mysqli_num_rows($query_portfolio) == 0): ?>

            <div class="text-center mt-5 pt-5">

                <i class="fa-solid fa-box-open fa-3x text-muted mb-3 opacity-50"></i>

                <h6 class="fw-bold text-secondary">
                    Portofolio Kosong
                </h6>

                <p class="text-muted small">
                    Anda belum memiliki instrumen investasi.
                    Silakan lakukan pembelian di halaman Home.
                </p>

                <a
                    href="dashboard.php"
                    class="btn btn-primary btn-sm mt-2 fw-bold"
                >
                    Mulai Investasi
                </a>

            </div>

        <?php else: ?>

            <div class="list-group mb-4">

                <?php while($row = mysqli_fetch_assoc($query_portfolio)): ?>
                    <?php
                    // [REVISI 1] Sembunyikan aset Edukasi jika modal aktifnya sudah 0 (sudah hangus/cair)
                    if ($row['tipe_simulasi'] == 'edukasi' && floatval($row['total_modal_aktif']) <= 0) {
                        continue;
                    }
                    ?>

                    <?php

                    

                    // =====================================
                    // DEPOSITO
                    // =====================================
                    if ($row['tipe_simulasi'] == 'edukasi') {
                        
                        $nilai_sekarang = 0; // Ditahan agar tidak terlihat rugi -100%
                        $label_unit = ""; // Kosong
                        $selisih = 0;
                        $persentase = 0;
                        $laba_potensial = 0;
                    }
                    elseif ($row['tipe_simulasi'] == 'persentase') {

                        $modal_awal =
                            floatval($row['total_modal_aktif']);

                        $label_unit =
                            "Rp " . number_format($modal_awal, 0, ',', '.');

                        $bunga =
                            floatval($row['laba_now']);

                        $selisih =
                            $modal_awal * ($bunga / 100);

                        $nilai_sekarang =
                            $modal_awal + $selisih;

                        $persentase =
                            $bunga;

                        $laba_potensial =
                            $selisih;

                    }

                    // =====================================
                    // MARKET / SAHAM / BISNIS
                    // =====================================

                // =====================================
                // MARKET / SAHAM / BISNIS / TOUR
                // =====================================

                else {

                    // KHUSUS TOUR
                    if(
                        !empty($row['group_name'])
                        &&
                        $row['group_name'] == 'Tour'
                    ){

                        $nilai_sekarang = 0;

                        $modal_asli =
                            floatval($row['total_modal_aktif']);

                        $selisih = 0;

                        $persentase = 0;

                        $laba_potensial = 0;

                    }
                    else{

                        $nilai_sekarang =
                            $row['total_unit'] * $row['val_now'];

                        $modal_asli =
                            floatval($row['total_modal_aktif']);

                        $selisih =
                            $nilai_sekarang - $modal_asli;

                        $persentase =
                            ($modal_asli > 0)
                            ? ($selisih / $modal_asli) * 100
                            : 0;

                        $laba_potensial =
                            $selisih;

                    }
                }

                    $total_portfolio_value += $nilai_sekarang;

                    $warna_teks =
                        ($selisih >= 0)
                        ? "text-success"
                        : "text-danger";

                    $ikon_panah =
                        ($selisih >= 0)
                        ? "<i class='fa-solid fa-arrow-trend-up'></i>"
                        : "<i class='fa-solid fa-arrow-trend-down'></i>";

                    ?>

                    <div class="portfolio-card p-3 mb-3">

                        <div class="d-flex justify-content-between align-items-center mb-2">

                            <div class="d-flex align-items-center">

                                <img
                                    src="../assets/img/investasi/<?php echo !empty($row['gambar']) ? $row['gambar'] : 'placeholder.jpg'; ?>"
                                    class="asset-img me-3"
                                    onerror="this.src='https://via.placeholder.com/50'"
                                >

                                <div>

                               <h6 class="asset-name mb-0">

                                <?php
                                echo !empty($row['group_name'])
                                ? $row['group_name'].' - '.$row['nama_aset']
                                : $row['nama_aset'];
                                ?>

                                </h6>

                                    <small class="text-muted">
                                        
                                    </small>

                                </div>

                            </div>

                            <div class="text-end">

                                <span class="asset-badge">
                                    <?php echo $row['kategori']; ?>
                                </span>

                            </div>

                        </div>

                        <div class="metric-box mt-3">

                            <div class="d-flex justify-content-between align-items-center mb-1">

                                <div>

                                    <small class="metric-label d-block">
                                        Valuasi Jual
                                    </small>

                                    <span class="metric-value">
                                        Rp <?php echo number_format($nilai_sekarang, 0, ',', '.'); ?>
                                    </span>

                                </div>

                                <div class="text-end">

                                    <small class="metric-label d-block">

                                        <?php if($row['tipe_simulasi'] == 'persentase'): ?>

                                            Imbal Hasil Berjalan

                                        <?php else: ?>

                                            Unrealized Return

                                        <?php endif; ?>

                                    </small>

                                    <span class="fw-bold <?php echo $warna_teks; ?>" style="font-size: 0.85rem;">

                                        <?php echo $ikon_panah; ?>

                                        Rp <?php echo number_format(abs($selisih), 0, ',', '.'); ?>

                                        (<?php echo number_format($persentase, 1, ',', '.'); ?>%)

                                    </span>

                                </div>

                            </div>

                            <div class="border-top pt-1 mt-1 d-flex justify-content-between align-items-center">

                                <small class="text-muted" style="font-size: 0.7rem;">
                                    Laba/Imbal Hasil Periode Ini
                                </small>

                                <span class="fw-bold text-success" style="font-size: 0.8rem;">

                                    + Rp <?php echo number_format($laba_potensial, 0, ',', '.'); ?>

                                </span>

                            </div>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>

            <!-- TOTAL WEALTH -->

            <div class="card wealth-card mt-4 mb-4">

                <div class="card-body p-4 text-center">

                    <p class="mb-1 text-light opacity-75 small">
                        Total Estimasi Kekayaan (Cash + Portofolio)
                    </p>

                    <h3 class="fw-bold mb-0 text-warning">

                        Rp <?php echo number_format($cash_balance + $total_portfolio_value, 0, ',', '.'); ?>

                    </h3>

                    <hr class="border-secondary my-3">

                    <div class="row text-center">

                        <div class="col-6 border-end border-secondary">

                            <small class="opacity-75 d-block">
                                Sisa Saldo (Cash)
                            </small>

                            <span class="fw-bold">

                                Rp <?php echo number_format($cash_balance, 0, ',', '.'); ?>

                            </span>

                        </div>

                        <div class="col-6">

                            <small class="opacity-75 d-block">
                                Nilai Portofolio
                            </small>

                            <span class="fw-bold">

                                Rp <?php echo number_format($total_portfolio_value, 0, ',', '.'); ?>

                            </span>

                        </div>

                    </div>

                </div>

            </div>


            <!-- HISTORY -->

<!-- HISTORY -->
  <?php endif; ?>
<div class="card history-card mb-5">

    <div class="card-body p-3">

        <h6 class="fw-bold mb-3 text-primary">
            Riwayat Hasil Transaksi
        </h6>

        <?php if(!empty($history_detail)): ?>

            <?php foreach($history_detail as $period => $detail): ?>

                <?php

                $profit =
                    floatval($detail['profit_total']);

                $warna_profit =
                    ($profit >= 0)
                    ? 'text-success'
                    : 'text-danger';

                ?>

                <div class="border rounded mb-3 overflow-hidden">

                    <!-- HEADER -->

                    <button
                        class="btn w-100 text-start p-3 history-button"
                        data-bs-toggle="collapse"
                        data-bs-target="#history_<?php echo $period; ?>"
                    >

                        <div class="d-flex justify-content-between align-items-center">

                            <div class="history-period">

                                <i class="fa-solid fa-chevron-down me-2"></i>

                                Periode <?php echo $period; ?>

                            </div>

                            <div class="<?php echo $warna_profit; ?> fw-bold">

                                <?php echo ($profit >= 0 ? '+' : '-'); ?>

                                Rp <?php echo number_format(abs($profit),0,',','.'); ?>

                            </div>

                        </div>

                    </button>

                    <!-- DETAIL -->

                    <div
                        class="collapse"
                        id="history_<?php echo $period; ?>"
                    >

                        <div class="p-3 bg-white border-top">

                            <!-- BUY -->

                            <div class="mb-3">

                                <div class="fw-bold text-primary small mb-2">

                                    Buy
                                    —
                                    Rp <?php echo number_format($detail['buy_total'],0,',','.'); ?>

                                </div>

                                <?php if(!empty($detail['buy_items'])): ?>

                                    <?php foreach($detail['buy_items'] as $item): ?>

                                        <div class="transaction-row">

                                            <span class="text-muted">

                                                • <?php echo $item['aset']; ?>

                                            </span>

                                            <span>

                                                Rp <?php echo number_format($item['nominal'],0,',','.'); ?>

                                            </span>

                                        </div>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </div>

                            <!-- SELL -->

                            <div class="mb-3">

                                <div class="fw-bold text-danger small mb-2">

                                    Sell
                                    —
                                    Rp <?php echo number_format($detail['sell_total'],0,',','.'); ?>

                                </div>

                                <?php if(!empty($detail['sell_items'])): ?>

                                    <?php foreach($detail['sell_items'] as $item): ?>

                                        <div class="transaction-row">

                                            <span class="text-muted">

                                                • <?php echo $item['aset']; ?>

                                            </span>

                                            <span>

                                                Rp <?php echo number_format($item['nominal'],0,',','.'); ?>

                                            </span>

                                        </div>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </div>

                            <!-- PROFIT -->

                            <div class="border-top pt-2 d-flex justify-content-between fw-bold">

                                <span>
                                    Profit Bersih
                                </span>

                                <span class="<?php echo $warna_profit; ?>">

                                    <?php echo ($profit >= 0 ? '+' : '-'); ?>

                                    Rp <?php echo number_format(abs($profit),0,',','.'); ?>

                                </span>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="text-muted small text-center py-3">
                Belum ada riwayat transaksi.
            </div>

        <?php endif; ?>

    </div>

</div>

      

    </div>

    <div class="bottom-nav shadow-lg">

        <a href="dashboard.php" class="nav-item">

            <i class="fa-solid fa-house"></i>

            Home

        </a>

        <a href="portfolio.php" class="nav-item active">

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