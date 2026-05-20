<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| AUTO CAIR INVESTASI PERSENTASE
|--------------------------------------------------------------------------
| Logic:
| - Deposito/Reksadana/Bunga tetap
| - Otomatis cair saat masuk periode berikutnya
| - Pokok + bunga masuk ke saldo user
| - Simpan notifikasi ke SESSION
| - Tidak bisa diproses 2x
|--------------------------------------------------------------------------
*/


// ==========================================
// AMBIL PERIODE AKTIF
// ==========================================

$q_setting = mysqli_query($conn, "
    SELECT active_period
    FROM system_settings
    LIMIT 1
");

$setting = mysqli_fetch_assoc($q_setting);

$active_period = (int)$setting['active_period'];


// ==========================================
// CEK TRANSAKSI INVESTASI PERSENTASE
// YANG SUDAH JATUH TEMPO
// ==========================================

$q_deposito = mysqli_query($conn, "
    SELECT
        t.id,
        t.user_id,
        t.asset_id,
        t.amount_money,
        t.qty,
        t.buy_period,
        t.maturity_period,
        t.locked_return_rate,

        a.nama_aset,
        a.tipe_simulasi

    FROM transactions t

    JOIN market_assets a
        ON t.asset_id = a.id

    WHERE
        a.tipe_simulasi = 'persentase'
        AND t.type = 'buy'
        AND t.is_processed = 0
        AND t.buy_period < '$active_period'
");


// ==========================================
// SESSION NOTIFICATION
// ==========================================

if (!isset($_SESSION['deposito_notifications'])) {
    $_SESSION['deposito_notifications'] = [];
}


// ==========================================
// LOOP PENCAIRAN
// ==========================================

while ($d = mysqli_fetch_assoc($q_deposito)) {

    $transaksi_id = (int)$d['id'];

    $user_id = (int)$d['user_id'];

    $asset_id = (int)$d['asset_id'];

    $modal_awal = floatval($d['amount_money']);

    $buy_period = (int)$d['buy_period'];

    $maturity_period = (int)$d['maturity_period'];

    $qty = floatval($d['qty']);

    // ==========================================
    // BUNGA TERKUNCI SAAT PEMBELIAN
    // ==========================================

    $persentase_bunga =
        isset($d['locked_return_rate'])
        ? floatval($d['locked_return_rate'])
        : 0;


    // ==========================================
    // HITUNG BUNGA
    // ==========================================

    $nominal_bunga =
        $modal_awal * ($persentase_bunga / 100);

    $total_pencairan = $nominal_bunga;


    // ==========================================
    // UPDATE SALDO USER
    // ==========================================

    mysqli_query($conn, "
        UPDATE users
        SET balance = balance + '$total_pencairan'
        WHERE id = '$user_id'
    ");


    // ==========================================
    // TANDAI SUDAH DIPROSES
    // ==========================================

    mysqli_query($conn, "
        UPDATE transactions
        SET is_processed = 1
        WHERE id = '$transaksi_id'
    ");


    // ==========================================
    // INSERT HISTORY SELL OTOMATIS
    // ==========================================

    mysqli_query($conn, "
INSERT INTO transactions
        (
            user_id, asset_id, period, type, amount_money, qty, buy_price, buy_period, realized_profit
        )
        VALUES
        (
            '$user_id', '$asset_id', '$active_period', 'sell', '$total_pencairan', '0', '0', '$buy_period', '$nominal_bunga'
        )");


    // ==========================================
    // SIMPAN NOTIF
    // ==========================================

    $_SESSION['deposito_notifications'][] = [

        'aset' => $d['nama_aset'],

        'modal' => $modal_awal,

        'persentase_bunga' => $persentase_bunga,

        'nominal_bunga' => $nominal_bunga,

        'total' => $total_pencairan

    ];

}

?>