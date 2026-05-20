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

if ($period_status == 'closed') {
    
    $q_all_assets = mysqli_query($conn, "SELECT * FROM market_assets");

    while ($a = mysqli_fetch_assoc($q_all_assets)) {
        $a_id = $a['id'];
        $harga_jual_now = floatval($a['value_p' . $active_period]);
        $tipe_sim = $a['tipe_simulasi'];

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
        if ($sisa_qty > 0 || $sisa_modal > 0) {
            
            if ($tipe_sim == 'persentase') {
                $hasil_penjualan = $sisa_modal;
                $realized_profit = 0; 
                $qty_jual = $sisa_modal; 
            } else {

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