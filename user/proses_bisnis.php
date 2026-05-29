<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'peserta' || $_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: dashboard.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$asset_id = (int)$_POST['asset_id'];
$keputusan = $_POST['keputusan']; // 'hold' atau 'sell'

$q_set = mysqli_query($conn, "SELECT active_period FROM system_settings LIMIT 1");
$setting = mysqli_fetch_assoc($q_set);
$active_period = (int)$setting['active_period'];

if ($active_period <= 1) {
    header("Location: detail_aset.php?id=$asset_id");
    exit;
}

$target_p = $active_period - 1;
$kolom_val = "value_p" . $active_period;
$kolom_laba = "laba_p" . $target_p;

$q_asset = mysqli_query($conn, "SELECT *, $kolom_val AS val_now, $kolom_laba AS laba_now FROM market_assets WHERE id='$asset_id'");
$asset = mysqli_fetch_assoc($q_asset);
$nama_aset = $asset['nama_aset'];
function kurangiRemainingQtyPropertiFIFO($conn, $user_id, $asset_id, $qty_jual)
{
    $sisa_jual = floatval($qty_jual);

    $q_lots = mysqli_query($conn, "
        SELECT id, remaining_qty
        FROM transactions
        WHERE user_id = '$user_id'
        AND asset_id = '$asset_id'
        AND type = 'buy'
        AND remaining_qty > 0
        ORDER BY id ASC
    ");

    while ($lot = mysqli_fetch_assoc($q_lots)) {

        if ($sisa_jual <= 0) {
            break;
        }

        $lot_id = (int)$lot['id'];
        $remaining = floatval($lot['remaining_qty']);

        $dipakai = min($remaining, $sisa_jual);
        $remaining_baru = $remaining - $dipakai;

        mysqli_query($conn, "
            UPDATE transactions
            SET remaining_qty = '$remaining_baru'
            WHERE id = '$lot_id'
        ");

        $sisa_jual -= $dipakai;
    }
}

// Cek apakah user sudah pernah membuat keputusan Laba untuk periode ini
$q_cek = mysqli_query($conn, "SELECT id FROM transactions WHERE user_id='$user_id' AND asset_id='$asset_id' AND type='sell' AND qty=0 AND buy_period='$target_p'");

$pesan_sukses = "";
$html_rincian = "";

// Jika belum pernah diklaim di periode ini
if (mysqli_num_rows($q_cek) == 0) {
    
    // 1. HITUNG SISA UNIT AKTUAL REAL-TIME (Untuk mencegah Jual berkali-kali)
        if ($asset['tipe_simulasi'] == 'properti') {

    $q_aktual = mysqli_query($conn, "
        SELECT
            SUM(remaining_qty) AS sisa_aktual,

            SUM(
                CASE
                    WHEN with_insurance = 1 THEN remaining_qty
                    ELSE 0
                END
            ) AS sisa_asuransi,

            SUM(
                CASE
                    WHEN with_insurance = 0 THEN remaining_qty
                    ELSE 0
                END
            ) AS sisa_tanpa_asuransi
        FROM transactions
        WHERE user_id='$user_id'
        AND asset_id='$asset_id'
        AND type='buy'
        AND remaining_qty > 0
    ");

} else {

    $q_aktual = mysqli_query($conn, "
        SELECT
            SUM(
                CASE
                    WHEN type='buy' THEN qty
                    WHEN type='sell' AND qty > 0 THEN -qty
                    ELSE 0
                END
            ) AS sisa_aktual,

            0 AS sisa_asuransi,
            0 AS sisa_tanpa_asuransi
        FROM transactions
        WHERE user_id='$user_id'
        AND asset_id='$asset_id'
    ");

}

        $data_aktual = mysqli_fetch_assoc($q_aktual);

        $sisa_unit_aktual = floatval($data_aktual['sisa_aktual']);
        $sisa_asuransi = floatval($data_aktual['sisa_asuransi']);
        $sisa_tanpa_asuransi = floatval($data_aktual['sisa_tanpa_asuransi']);

    // PROTEKSI UTAMA: Jika aset fisik sudah 0, blokir paksa
    if ($sisa_unit_aktual <= 0) {
        echo '<script>alert("Anda sudah tidak memiliki aset ini!"); window.location.href="portfolio.php";</script>';
        exit;
    }

    // 2. HITUNG SISA UNIT LABA (Hanya menghitung unit yang mengendap dari periode sebelumnya)
    $q_laba = mysqli_query($conn, "SELECT SUM(CASE WHEN type='buy' THEN qty ELSE 0 END) - SUM(CASE WHEN type='sell' AND qty > 0 THEN qty ELSE 0 END) AS sisa_laba FROM transactions WHERE user_id='$user_id' AND asset_id='$asset_id' AND period <= '$target_p'");
    $sisa_unit_laba = floatval(mysqli_fetch_assoc($q_laba)['sisa_laba']);
    if ($sisa_unit_laba < 0) $sisa_unit_laba = 0; // Proteksi nilai minus

    $unclaimed_yield = $sisa_unit_laba * floatval($asset['laba_now']);
    $total_uang_masuk = 0;

    // --- A. EKSEKUSI PENCAIRAN LABA ---
    if ($unclaimed_yield > 0) { 
        mysqli_query($conn, "INSERT INTO transactions (user_id, asset_id, period, type, amount_money, qty, buy_price, realized_profit, buy_period) VALUES ('$user_id', '$asset_id', '$active_period', 'sell', '$unclaimed_yield', '0', '0', '$unclaimed_yield', '$target_p')");
        $total_uang_masuk += $unclaimed_yield;
        
        $html_rincian .= "
        <tr>
            <td>Laba Bisnis Masuk</td>
            <td align='right'><b style='color:green;'>+ Rp " . number_format($unclaimed_yield, 0, ',', '.') . "</b></td>
        </tr>";
    } else {
        // PENTING: Wajib pasang log kunci Rp 0 agar form tidak bisa dispam jika laba sedang Rp 0
        mysqli_query($conn, "INSERT INTO transactions (user_id, asset_id, period, type, amount_money, qty, buy_price, realized_profit, buy_period) VALUES ('$user_id', '$asset_id', '$active_period', 'sell', '0', '0', '0', '0', '$target_p')");
    }

    // --- B. EKSEKUSI PENJUALAN FISIK (Jika user klik Jual Semua) ---
    $hasil_penjualan = 0;
    $realized_profit = 0;
    
    if ($keputusan == 'sell') {
        if ($asset['tipe_simulasi'] == 'properti') {

    $harga_jual_asuransi = floatval($asset['val_now']);
    $harga_jual_tanpa_asuransi = floatval($asset['val_now']);

    if ($active_period == 3) {
        $harga_jual_tanpa_asuransi = 0;
    }

    $q_avg = mysqli_query($conn, "
        SELECT
            SUM(qty * buy_price) / NULLIF(SUM(qty), 0) AS avg_buy_price
        FROM transactions
        WHERE user_id='$user_id'
        AND asset_id='$asset_id'
        AND type='buy'
    ");

    $d_avg = mysqli_fetch_assoc($q_avg);
    $avg_buy_price = floatval($d_avg['avg_buy_price']);

    $hasil_penjualan_asuransi = $sisa_asuransi * $harga_jual_asuransi;
    $hasil_penjualan_tanpa_asuransi = $sisa_tanpa_asuransi * $harga_jual_tanpa_asuransi;

    $hasil_penjualan = $hasil_penjualan_asuransi + $hasil_penjualan_tanpa_asuransi;

    $modal_asli = $sisa_unit_aktual * $avg_buy_price;
    $realized_profit = $hasil_penjualan - $modal_asli;

    if ($sisa_asuransi > 0) {
        $profit_asuransi =
            $hasil_penjualan_asuransi
            -
            ($sisa_asuransi * $avg_buy_price);

        mysqli_query($conn, "
            INSERT INTO transactions
            (
                user_id,
                asset_id,
                period,
                type,
                amount_money,
                qty,
                buy_price,
                sell_price,
                realized_profit,
                buy_period,
                with_insurance
            )
            VALUES
            (
                '$user_id',
                '$asset_id',
                '$active_period',
                'sell',
                '$hasil_penjualan_asuransi',
                '$sisa_asuransi',
                '$avg_buy_price',
                '$harga_jual_asuransi',
                '$profit_asuransi',
                '$active_period',
                1
            )
        ");
    }

    if ($sisa_tanpa_asuransi > 0) {
        $profit_tanpa_asuransi =
            $hasil_penjualan_tanpa_asuransi
            -
            ($sisa_tanpa_asuransi * $avg_buy_price);

        mysqli_query($conn, "
            INSERT INTO transactions
            (
                user_id,
                asset_id,
                period,
                type,
                amount_money,
                qty,
                buy_price,
                sell_price,
                realized_profit,
                buy_period,
                with_insurance
            )
            VALUES
            (
                '$user_id',
                '$asset_id',
                '$active_period',
                'sell',
                '$hasil_penjualan_tanpa_asuransi',
                '$sisa_tanpa_asuransi',
                '$avg_buy_price',
                '$harga_jual_tanpa_asuransi',
                '$profit_tanpa_asuransi',
                '$active_period',
                0
            )
        "); 
    }
    kurangiRemainingQtyPropertiFIFO($conn, $user_id, $asset_id, $sisa_unit_aktual);
    } else {

        $harga_jual = floatval($asset['val_now']);
        $hasil_penjualan = $sisa_unit_aktual * $harga_jual;

        $q_avg = mysqli_query($conn, "
            SELECT
                SUM(amount_money) as total_uang,
                SUM(qty) as total_qty
            FROM transactions
            WHERE user_id='$user_id'
            AND asset_id='$asset_id'
            AND type='buy'
        ");

        $d_avg = mysqli_fetch_assoc($q_avg);
        $avg_buy_price =
            ($d_avg['total_qty'] > 0)
            ? (floatval($d_avg['total_uang']) / floatval($d_avg['total_qty']))
            : 0;

        $modal_asli = $sisa_unit_aktual * $avg_buy_price;
        $realized_profit = $hasil_penjualan - $modal_asli;

        mysqli_query($conn, "
            INSERT INTO transactions
            (
                user_id,
                asset_id,
                period,
                type,
                amount_money,
                qty,
                buy_price,
                sell_price,
                realized_profit,
                buy_period
            )
            VALUES
            (
                '$user_id',
                '$asset_id',
                '$active_period',
                'sell',
                '$hasil_penjualan',
                '$sisa_unit_aktual',
                '$avg_buy_price',
                '$harga_jual',
                '$realized_profit',
                '$active_period'
            )
        ");

    }
        $total_uang_masuk += $hasil_penjualan;
        
        $html_rincian .= "
        <tr>
            <td>Hasil Jual Aset</td>
            <td align='right'><b>+ Rp " . number_format($hasil_penjualan, 0, ',', '.') . "</b></td>
        </tr>
        <tr>
            <td>Capital Gain/Loss</td>
            <td align='right'><b style='color:" . ($realized_profit >= 0 ? 'green' : 'red') . ";'>" . ($realized_profit >= 0 ? '+' : '') . " Rp " . number_format($realized_profit, 0, ',', '.') . "</b></td>
        </tr>";
        
        $pesan_sukses = "Aset Dijual & Laba Diklaim";
    } else {
        $pesan_sukses = "Laba Bisnis Berhasil Diklaim";
    }

    // --- C. TAMBAHKAN UANG KE SALDO ---
    if ($total_uang_masuk > 0) {
        mysqli_query($conn, "UPDATE users SET balance = balance + '$total_uang_masuk' WHERE id='$user_id'");
    }
    
    // --- D. SWEETALERT ---
    echo '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
    <script>
    Swal.fire({
        icon: "success",
        title: "'.$pesan_sukses.'",
        html: `
            <div style="text-align:left">
                <p>Keputusan bisnis untuk aset <b>'.$nama_aset.'</b> berhasil diproses.</p>
                <hr>
                <table style="width:100%">
                    '.$html_rincian.'
                    <tr><td colspan="2"><hr></td></tr>
                    <tr>
                        <td><b>Total Saldo Bertambah</b></td>
                        <td align="right"><b style="color:#0d6efd;">Rp '.number_format($total_uang_masuk, 0, ',', '.').'</b></td>
                    </tr>
                </table>
            </div>
        `,
        confirmButtonText: "Ke Portofolio",
        allowOutsideClick: false
    }).then(() => {
        window.location.href = "portfolio.php";
    });
    </script>
    </body>
    </html>
    ';
    exit;

} // Penutup pengecekan q_cek

// Jika sudah diklaim / percobaan tembak URL
header("Location: dashboard.php");
exit;
?>