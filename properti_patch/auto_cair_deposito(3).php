<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. AMBIL PERIODE AKTIF & STATUS SISTEM
$q_setting = mysqli_query($conn, "SELECT active_period, period_status FROM system_settings LIMIT 1");
$setting = mysqli_fetch_assoc($q_setting);
$active_period = (int)$setting['active_period'];
$period_status = $setting['period_status'];
$user_id = $_SESSION['user_id'];


// ====================================================================
// A. AUTO CAIR PROFIT PERSENTASE (Berulang Tiap Periode)
// ====================================================================

$q_assets = mysqli_query($conn, "SELECT * FROM market_assets WHERE tipe_simulasi = 'persentase'");

while ($asset = mysqli_fetch_assoc($q_assets)) {
    $asset_id = $asset['id'];

    // --- KONDISI 1: Cairkan profit dari periode SEBELUMNYA (Rutin dijalankan saat OPEN) ---
    if ($active_period > 1) {
        $target_p = $active_period - 1;

        // Cek apakah profit untuk target periode ini sudah pernah dicairkan
        $q_cek = mysqli_query($conn, "SELECT id FROM transactions WHERE user_id='$user_id' AND asset_id='$asset_id' AND type='sell' AND qty=0 AND buy_period='$target_p'");

        if (mysqli_num_rows($q_cek) == 0) {
            // Hitung modal yang mengendap sampai dengan periode target
            $q_modal = mysqli_query($conn, "
                SELECT 
                    SUM(CASE WHEN type='buy' THEN amount_money ELSE 0 END) as total_beli,
                    SUM(CASE WHEN type='sell' AND qty > 0 THEN amount_money ELSE 0 END) as total_jual
                FROM transactions 
                WHERE user_id = '$user_id' AND asset_id = '$asset_id' AND period <= '$target_p'
            ");
            $modal_data = mysqli_fetch_assoc($q_modal);
            $sisa_modal = floatval($modal_data['total_beli']) - floatval($modal_data['total_jual']);

            if ($sisa_modal > 0) {
                $bunga_rate = floatval($asset['laba_p' . $target_p]);
                $nominal_bunga = $sisa_modal * ($bunga_rate / 100);

                if ($nominal_bunga > 0) {
                    mysqli_query($conn, "UPDATE users SET balance = balance + '$nominal_bunga' WHERE id = '$user_id'");
                    // buy_period diisi dengan $target_p sebagai penanda profit milik periode tersebut
                    mysqli_query($conn, "
                        INSERT INTO transactions (user_id, asset_id, period, type, amount_money, qty, buy_price, realized_profit, buy_period)
                        VALUES ('$user_id', '$asset_id', '$active_period', 'sell', '$nominal_bunga', '0', '0', '$nominal_bunga', '$target_p')
                    ");

                    $_SESSION['deposito_notifications'][] = [
                        'aset' => $asset['nama_aset'],
                        'modal' => $sisa_modal,
                        'nominal_bunga' => $nominal_bunga,
                        'total' => $nominal_bunga
                    ];
                }
            }
        }
    }

    // --- KONDISI 2: Cairkan profit dari periode SEKARANG (Khusus dijalankan saat CLOSED / Akhir Game) ---

    if ($period_status == 'closed') {
        $target_p = $active_period;

        // Cek apakah profit untuk periode aktif saat ini sudah dicairkan
        $q_cek = mysqli_query($conn, "SELECT id FROM transactions WHERE user_id='$user_id' AND asset_id='$asset_id' AND type='sell' AND qty=0 AND buy_period='$target_p'");

        if (mysqli_num_rows($q_cek) == 0) {
            // Hitung seluruh modal yang ada di periode ini
            $q_modal = mysqli_query($conn, "
                SELECT 
                    SUM(CASE WHEN type='buy' THEN amount_money ELSE 0 END) as total_beli,
                    SUM(CASE WHEN type='sell' AND qty > 0 THEN amount_money ELSE 0 END) as total_jual
                FROM transactions 
                WHERE user_id = '$user_id' AND asset_id = '$asset_id' AND period <= '$target_p'
            ");
            $modal_data = mysqli_fetch_assoc($q_modal);
            $sisa_modal = floatval($modal_data['total_beli']) - floatval($modal_data['total_jual']);

            if ($sisa_modal > 0) {
                $bunga_rate = floatval($asset['laba_p' . $target_p]);
                $nominal_bunga = $sisa_modal * ($bunga_rate / 100);

                if ($nominal_bunga > 0) {
                    mysqli_query($conn, "UPDATE users SET balance = balance + '$nominal_bunga' WHERE id = '$user_id'");
                    mysqli_query($conn, "
                        INSERT INTO transactions (user_id, asset_id, period, type, amount_money, qty, buy_price, realized_profit, buy_period)
                        VALUES ('$user_id', '$asset_id', '$active_period', 'sell', '$nominal_bunga', '0', '0', '$nominal_bunga', '$target_p')
                    ");

                    $_SESSION['deposito_notifications'][] = [
                        'aset' => $asset['nama_aset'],
                        'modal' => $sisa_modal,
                        'nominal_bunga' => $nominal_bunga,
                        'total' => $nominal_bunga
                    ];
                }
            }
        }
    }
}


        // ====================================================================
        // B. AUTO JUAL SEMUA ASET SAAT SIMULASI SELESAI (STATUS = CLOSED)
        // ====================================================================

        // ====================================================================
        // TOUR (PENGELUARAN HANGUS OTOMATIS PERIODE BERIKUTNYA)
        // ====================================================================

        $q_tour = mysqli_query($conn, "
            SELECT *
            FROM market_assets
            WHERE group_name = 'Tour'
        ");

        while ($tour_asset = mysqli_fetch_assoc($q_tour)) {

            $tour_asset_id = $tour_asset['id'];

            if ($active_period <= 1) {
                continue;
            }

            $target_p = $active_period - 1;

            $q_cek = mysqli_query($conn, "
                SELECT id
                FROM transactions
                WHERE
                    user_id = '$user_id'
                    AND asset_id = '$tour_asset_id'
                    AND type = 'sell'
                    AND qty > 0
                    AND buy_period = '$target_p'
                LIMIT 1
            ");

            if (mysqli_num_rows($q_cek) > 0) {
                continue;
            }

            $q_buy = mysqli_query($conn, "
                SELECT id, amount_money, qty, buy_price
                FROM transactions
                WHERE
                    user_id = '$user_id'
                    AND asset_id = '$tour_asset_id'
                    AND type = 'buy'
                    AND buy_period = '$target_p'
                    AND is_active = 1
                LIMIT 1
            ");

            if (mysqli_num_rows($q_buy) == 0) {
                continue;
            }

            $buy = mysqli_fetch_assoc($q_buy);

            $qty_beli = floatval($buy['qty']);
            $harga_beli = floatval($buy['buy_price']);
            $modal_asli = floatval($buy['amount_money']);

            mysqli_query($conn, "
                INSERT INTO transactions
                (
                    user_id,
                    asset_id,
                    period,
                    type,
                    amount_money,
                    realized_profit,
                    qty,
                    buy_price,
                    sell_price,
                    buy_period
                )
                VALUES
                (
                    '$user_id',
                    '$tour_asset_id',
                    '$active_period',
                    'sell',
                    0,
                    '-$modal_asli',
                    '$qty_beli',
                    '$harga_beli',
                    0,
                    '$target_p'
                )
            ");

            mysqli_query($conn, "
                UPDATE transactions
                SET is_active = 0
                WHERE id = '".$buy['id']."'
            ");
        }

        // ====================================================================
        // TOUR SAAT SIMULASI CLOSED
        // Hanguskan Tour yang dibeli di periode aktif
        // ====================================================================

        if ($period_status == 'closed') {

            $q_tour_closed = mysqli_query($conn, "
                SELECT *
                FROM market_assets
                WHERE group_name = 'Tour'
            ");

            while ($tour_asset = mysqli_fetch_assoc($q_tour_closed)) {

                $tour_asset_id = $tour_asset['id'];
                $target_p = $active_period;

                $q_cek = mysqli_query($conn, "
                    SELECT id
                    FROM transactions
                    WHERE
                        user_id = '$user_id'
                        AND asset_id = '$tour_asset_id'
                        AND type = 'sell'
                        AND qty > 0
                        AND buy_period = '$target_p'
                    LIMIT 1
                ");

                if (mysqli_num_rows($q_cek) > 0) {
                    continue;
                }

                $q_buy = mysqli_query($conn, "
                    SELECT id, amount_money, qty, buy_price
                    FROM transactions
                    WHERE
                        user_id = '$user_id'
                        AND asset_id = '$tour_asset_id'
                        AND type = 'buy'
                        AND buy_period = '$target_p'
                        AND is_active = 1
                    LIMIT 1
                ");

                if (mysqli_num_rows($q_buy) == 0) {
                    continue;
                }

                $buy = mysqli_fetch_assoc($q_buy);

                $qty_beli = floatval($buy['qty']);
                $harga_beli = floatval($buy['buy_price']);
                $modal_asli = floatval($buy['amount_money']);

                mysqli_query($conn, "
                    INSERT INTO transactions
                    (
                        user_id,
                        asset_id,
                        period,
                        type,
                        amount_money,
                        realized_profit,
                        qty,
                        buy_price,
                        sell_price,
                        buy_period
                    )
                    VALUES
                    (
                        '$user_id',
                        '$tour_asset_id',
                        '$active_period',
                        'sell',
                        0,
                        '-$modal_asli',
                        '$qty_beli',
                        '$harga_beli',
                        0,
                        '$target_p'
                    )
                ");

                mysqli_query($conn, "
                    UPDATE transactions
                    SET is_active = 0
                    WHERE id = '".$buy['id']."'
                ");
            }
        }

// ====================================================================
// EDUKASI (1X BENEFIT PER PERIODE BERIKUTNYA)
// ====================================================================

$q_edukasi =
mysqli_query(
$conn,
"
SELECT *
FROM market_assets
WHERE tipe_simulasi='edukasi'
"
);

while(
$asset=
mysqli_fetch_assoc(
$q_edukasi
)
){

$asset_id=
$asset['id'];

if(
$active_period<=1
){

continue;

}

// target periode sebelumnya

$target_p=
$active_period-1;


// CEK APAKAH BENEFIT SUDAH PERNAH MASUK

$q_cek=
mysqli_query(
$conn,
"
SELECT id
FROM transactions
WHERE
user_id='$user_id'
AND asset_id='$asset_id'
AND type='sell'
AND qty=0
AND buy_period='$target_p'
LIMIT 1
"
);

if(
mysqli_num_rows(
$q_cek
)>0
){

continue;

}


// CEK ADA PEMBELIAN EDUKASI PERIODE TARGET
// CEK ADA PEMBELIAN EDUKASI PERIODE TARGET
$q_buy=
mysqli_query(
$conn,
"
SELECT id, amount_money, qty, buy_price
FROM transactions
WHERE
user_id='$user_id' AND asset_id='$asset_id' AND type='buy' AND buy_period='$target_p' AND is_active=1
LIMIT 1
"
);

if(mysqli_num_rows($q_buy)==0){
    continue;
}

$buy=mysqli_fetch_assoc($q_buy);
$qty_beli = floatval($buy['qty']);
$harga_beli = floatval($buy['buy_price']);

// ambil benefit
$benefit=floatval($asset['laba_p'.$target_p]);

if($benefit<=0){
    continue;
}

// tambah saldo (profit cair)
mysqli_query($conn, "UPDATE users SET balance=balance+$benefit WHERE id='$user_id'");

// catat histori profit masuk
mysqli_query($conn, "
    INSERT INTO transactions (user_id, asset_id, period, type, amount_money, qty, buy_price, realized_profit, buy_period)
    VALUES ('$user_id', '$asset_id', '$active_period', 'sell', '$benefit', 0, 0, '$benefit', '$target_p')
");

// [LOGIKA BARU EDUKASI] catat histori HANGUS POKOK agar unit hilang dari portofolio
mysqli_query($conn, "
    INSERT INTO transactions (user_id, asset_id, period, type, amount_money, qty, buy_price, realized_profit, buy_period)
    VALUES ('$user_id', '$asset_id', '$active_period', 'sell', 0, '$qty_beli', '$harga_beli', 0, '$target_p')
");

// hapus aset pendidikan
mysqli_query($conn, "UPDATE transactions SET is_active=0 WHERE id='".$buy['id']."'");

// notifikasi
$_SESSION['edukasi_notifications'][]=[
    'aset'=> $asset['nama_aset'],
    'benefit'=> $benefit,
    'periode'=> $target_p
];

}   

if ($period_status == 'closed') {
    
    $q_all_assets = mysqli_query($conn, "SELECT * FROM market_assets");

    while ($a = mysqli_fetch_assoc($q_all_assets)) {
    $a_id = $a['id'];
    $harga_jual_now = floatval($a['value_p' . $active_period]);
    $tipe_sim = $a['tipe_simulasi'];

    // EDUKASI DAN TOUR BUKAN ASET FORCE SELL AKHIR SIMULASI
    if ($tipe_sim == 'edukasi' || $a['group_name'] == 'Tour') {
        continue;
    }

    // Cegah force sell berulang saat reload
    $q_force = mysqli_query($conn,"
        SELECT id
        FROM transactions
        WHERE
            user_id='$user_id'
            AND asset_id='$a_id'
            AND type='sell'
            AND buy_period='$active_period'
            AND qty > 0
        LIMIT 1
    ");

if(mysqli_num_rows($q_force) > 0){
    continue;
}

        // Hitung sisa QTY dan Modal saat ini
        $q_qty = mysqli_query($conn, "
            SELECT 
                SUM(CASE WHEN type='buy' THEN qty ELSE 0 END) - 
                SUM(CASE WHEN type='sell' AND qty > 0 THEN qty ELSE 0 END) AS sisa_qty,
                
                SUM(CASE WHEN type='buy' THEN amount_money ELSE 0 END) - 
                SUM(CASE WHEN type='sell' AND qty > 0 THEN amount_money ELSE 0 END) AS sisa_modal
            FROM transactions 
            WHERE user_id = '$user_id' AND asset_id = '$a_id'
        ");
        
        $data_qty = mysqli_fetch_assoc($q_qty);
        $sisa_qty = floatval($data_qty['sisa_qty']);
        $sisa_modal = floatval($data_qty['sisa_modal']);

        // Eksekusi Jual Paksa jika masih ada aset tersisa
        if (($tipe_sim != 'persentase' && $sisa_qty > 0) || ($tipe_sim == 'persentase' && $sisa_modal > 0)) {
            
            if ($tipe_sim == 'persentase') {
                $hasil_penjualan = $sisa_modal;
                $realized_profit = 0; 
                $qty_jual = $sisa_modal; 
            } else {

    // KHUSUS PROPERTI:
    // Jika periode 3 dan pembelian tidak memakai asuransi, harga jualnya menjadi 0.
    if ($tipe_sim == 'properti' && $active_period == 3) {
        $q_asuransi = mysqli_query($conn, "
            SELECT MAX(with_insurance) AS ada_asuransi
            FROM transactions
            WHERE user_id='$user_id'
            AND asset_id='$a_id'
            AND type='buy'
            AND is_active=1
        ");

        $d_asuransi = mysqli_fetch_assoc($q_asuransi);
        $ada_asuransi = intval($d_asuransi['ada_asuransi'] ?? 0);

        if ($ada_asuransi <= 0) {
            $harga_jual_now = 0;
        }
    }

    $hasil_penjualan =
    $sisa_qty * $harga_jual_now;

    // KHUSUS BISNIS
    if($tipe_sim == 'bisnis'){

        $realized_profit = 0;

    } else {

        $q_avg = mysqli_query($conn,"
            SELECT
            SUM(amount_money) total_uang,
            SUM(qty) total_qty
            FROM transactions
            WHERE
            user_id='$user_id'
            AND asset_id='$a_id'
            AND type='buy'
        ");

        $d_avg =
        mysqli_fetch_assoc($q_avg);

        $avg_buy_price =
        ($d_avg['total_qty']>0)
        ? ($d_avg['total_uang']/$d_avg['total_qty'])
        : 0;

        $modal_asli =
        $sisa_qty*$avg_buy_price;

        $realized_profit =
        $hasil_penjualan-$modal_asli;
    }

    $qty_jual = $sisa_qty;
}

            // 1. Tambah Hasil Penjualan ke Saldo User
            mysqli_query($conn, "UPDATE users SET balance = balance + '$hasil_penjualan' WHERE id = '$user_id'");

            // 2. Insert Transaksi Sell (Force Sell)
            mysqli_query($conn, "
                INSERT INTO transactions 
                (user_id, asset_id, period, type, amount_money, qty, buy_price, realized_profit, buy_period)
                VALUES 
                ('$user_id', '$a_id', '$active_period', 'sell', '$hasil_penjualan', '$qty_jual', '$harga_jual_now', '$realized_profit', '$active_period')
            ");

            // 3. Notifikasi Jual Paksa Akhir Game
            $_SESSION['force_sell_notifications'][] = [
                'aset' => $a['nama_aset'],
                'hasil' => $hasil_penjualan,
                'profit' => $realized_profit
            ];
        }
    }
}
?>