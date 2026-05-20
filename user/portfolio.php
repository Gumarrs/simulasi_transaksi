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

        $history_detail[$period]['buy_items'][] = [
            'aset' => $d['nama_aset'],
            'nominal' => $d['amount_money']
        ];
    }

// CAIR PROFIT DEPOSITO / LABA BISNIS (Tipe Sell tapi QTY = 0)
    elseif($d['type'] == 'sell' && floatval($d['qty']) == 0) {
        
        $history_detail[$period]['profit_total'] += $d['realized_profit'];
        $p_profit = $d['buy_period']; 
        
        // Pembedaan Nama Label berdasarkan Tipe Simulasi
        if ($d['tipe_simulasi'] == 'bisnis') {
            $label_aset = 'Laba Bisnis ' . $d['nama_aset'] . ' (Periode ' . $p_profit . ')';
        } else {
            $label_aset = 'Pencairan Profit ' . $d['nama_aset'] . ' (Periode ' . $p_profit . ')';
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

        $history_detail[$period]['sell_items'][] = [
            'aset' => $d['nama_aset'],
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
            background-color: #f4f7f6;
            padding-bottom: 75px;
        }

        .mobile-container {
            max-width: 480px;
            margin: auto;
            background: #fff;
            min-height: 100vh;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            position: relative;
        }

        .bottom-nav {
            position: fixed;
            bottom: 0;
            width: 100%;
            max-width: 480px;
            background: #fff;
            border-top: 1px solid #ddd;
            z-index: 1000;
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
        }

        .nav-item {
            text-align: center;
            color: #6c757d;
            text-decoration: none;
            font-size: 0.8rem;
        }

        .nav-item.active {
            color: #0d6efd;
            font-weight: bold;
        }

        .nav-item i {
            font-size: 1.2rem;
            display: block;
            margin-bottom: 2px;
        }

        .asset-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }

    </style>

</head>

<body>

<div class="mobile-container">

    <div class="p-3 border-bottom bg-white sticky-top text-center">

        <h5 class="fw-bold text-primary mb-0">
            Portofolio Investasi
        </h5>

        <small class="text-muted">
            Periode <?php echo $active_period; ?>
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

                    // =====================================
                    // DEPOSITO
                    // =====================================

                    if ($row['tipe_simulasi'] == 'persentase') {

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

                    else {

                        $nilai_sekarang =
                            $row['total_unit'] * $row['val_now'];

                        $label_unit =
                            number_format($row['total_unit'], 4, ',', '.')
                            . " Unit";

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

                    <div
                        class="list-group-item list-group-item-action p-3 border-0 shadow-sm mb-2"
                        style="border-radius: 10px;"
                    >

                        <div class="d-flex justify-content-between align-items-center mb-2">

                            <div class="d-flex align-items-center">

                                <img
                                    src="../assets/img/investasi/<?php echo !empty($row['gambar']) ? $row['gambar'] : 'placeholder.jpg'; ?>"
                                    class="asset-img me-3"
                                    onerror="this.src='https://via.placeholder.com/50'"
                                >

                                <div>

                                    <h6 class="fw-bold mb-0">
                                        <?php echo $row['nama_aset']; ?>
                                    </h6>

                                    <small class="text-muted">
                                        <?php echo $label_unit; ?>
                                    </small>

                                </div>

                            </div>

                            <div class="text-end">

                                <span class="badge bg-light text-dark border mb-1">
                                    <?php echo $row['kategori']; ?>
                                </span>

                            </div>

                        </div>

                        <div class="bg-light p-2 rounded mt-2">

                            <div class="d-flex justify-content-between align-items-center mb-1">

                                <div>

                                    <small class="text-muted d-block" style="font-size: 0.7rem;">
                                        Valuasi Jual
                                    </small>

                                    <span class="fw-bold text-dark">
                                        Rp <?php echo number_format($nilai_sekarang, 0, ',', '.'); ?>
                                    </span>

                                </div>

                                <div class="text-end">

                                    <small class="text-muted d-block" style="font-size: 0.7rem;">

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

            <div class="card bg-dark text-white border-0 shadow mt-4 mb-4" style="border-radius: 12px;">

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
<div class="card border-0 shadow-sm mb-5" style="border-radius: 12px;">

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
                        class="btn w-100 text-start p-3 bg-light border-0"
                        data-bs-toggle="collapse"
                        data-bs-target="#history_<?php echo $period; ?>"
                    >

                        <div class="d-flex justify-content-between align-items-center">

                            <div class="fw-bold text-dark">

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

                                        <div class="d-flex justify-content-between small mb-1">

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

                                        <div class="d-flex justify-content-between small mb-1">

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