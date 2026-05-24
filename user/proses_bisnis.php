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

// Cek apakah user sudah pernah membuat keputusan Laba untuk periode ini
$q_cek = mysqli_query($conn, "SELECT id FROM transactions WHERE user_id='$user_id' AND asset_id='$asset_id' AND type='sell' AND qty=0 AND buy_period='$target_p'");

$pesan_sukses = "";
$html_rincian = "";

// Jika belum pernah diklaim di periode ini
if (mysqli_num_rows($q_cek) == 0) {
    
    // 1. HITUNG SISA UNIT AKTUAL REAL-TIME (Untuk mencegah Jual berkali-kali)
    $q_aktual = mysqli_query($conn, "SELECT SUM(CASE WHEN type='buy' THEN qty ELSE 0 END) - SUM(CASE WHEN type='sell' AND qty > 0 THEN qty ELSE 0 END) AS sisa_aktual FROM transactions WHERE user_id='$user_id' AND asset_id='$asset_id'");
    $sisa_unit_aktual = floatval(mysqli_fetch_assoc($q_aktual)['sisa_aktual']);

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
        $harga_jual = floatval($asset['val_now']);
        // Menggunakan unit AKTUAL, bukan unit laba
        $hasil_penjualan = $sisa_unit_aktual * $harga_jual;
        
        // Perhitungan Modal Rata-rata 
        $q_avg = mysqli_query($conn, "SELECT SUM(amount_money) as total_uang, SUM(qty) as total_qty FROM transactions WHERE user_id='$user_id' AND asset_id='$asset_id' AND type='buy'");
        $d_avg = mysqli_fetch_assoc($q_avg);
        $avg_buy_price = ($d_avg['total_qty'] > 0) ? (floatval($d_avg['total_uang']) / floatval($d_avg['total_qty'])) : 0;
        
        $modal_asli = $sisa_unit_aktual * $avg_buy_price;
        $realized_profit = $hasil_penjualan - $modal_asli;

        // Eksekusi pelepasan aset (jual)
        mysqli_query($conn, "INSERT INTO transactions (user_id, asset_id, period, type, amount_money, qty, buy_price, realized_profit, buy_period) VALUES ('$user_id', '$asset_id', '$active_period', 'sell', '$hasil_penjualan', '$sisa_unit_aktual', '$harga_jual', '$realized_profit', '$active_period')");
        
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