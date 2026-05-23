<?php
session_start();
require_once '../config/koneksi.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id = $_SESSION['user_id'];

    // ==========================================
    // 1. TANGKAP SEMUA DATA PENGHASILAN
    // ==========================================
    $gaji           = floatval($_POST['gaji_bersih'] ?? 0);
    $bonus          = floatval($_POST['bonus'] ?? 0);
    $komisi         = floatval($_POST['komisi'] ?? 0);
    $hadiah         = floatval($_POST['hadiah'] ?? 0);
    $untung_saham   = floatval($_POST['untung_saham'] ?? 0);
    $aktif_lain     = floatval($_POST['aktif_lain'] ?? 0);

    $bunga          = floatval($_POST['bunga'] ?? 0);
    $sewa_properti  = floatval($_POST['sewa_properti'] ?? 0);
    $laba_bisnis    = floatval($_POST['laba_bisnis'] ?? 0);
    $dividen        = floatval($_POST['dividen'] ?? 0);
    $royalti        = floatval($_POST['royalti'] ?? 0);
    $pasif_lain     = floatval($_POST['pasif_lain'] ?? 0);

    // ==========================================
    // 2. TANGKAP SEMUA DATA PENGELUARAN
    // ==========================================
    
    // Rumah Tangga
    $sewa_rumah         = floatval($_POST['sewa_rumah'] ?? 0);
    $cicilan_rumah      = floatval($_POST['cicilan_rumah'] ?? 0);
    $perawatan_rumah    = floatval($_POST['perawatan_rumah'] ?? 0);
    $asuransi_alat_rt   = floatval($_POST['asuransi_alat_rt'] ?? 0);
    $belanja_rt         = floatval($_POST['belanja_rt'] ?? 0);
    $pbb                = floatval($_POST['pbb'] ?? 0);
    $keamanan_rt        = floatval($_POST['keamanan_rt'] ?? 0);
    $servis_alat        = floatval($_POST['servis_alat'] ?? 0);
    $rt_lain            = floatval($_POST['rt_lain'] ?? 0);

    // Kesehatan
    $dokter             = floatval($_POST['dokter'] ?? 0);
    $obat_obatan        = floatval($_POST['obat_obatan'] ?? 0);
    $checkup            = floatval($_POST['checkup'] ?? 0);
    $asuransi_kes       = floatval($_POST['asuransi_kes'] ?? 0);
    $fitness            = floatval($_POST['fitness'] ?? 0);
    $kesehatan_lain     = floatval($_POST['kesehatan_lain'] ?? 0);

    // Transportasi
    $asuransi_kendaraan = floatval($_POST['asuransi_kendaraan'] ?? 0);
    $bbm                = floatval($_POST['bbm'] ?? 0);
    $cicilan_kendaraan  = floatval($_POST['cicilan_kendaraan'] ?? 0);
    $servis_kendaraan   = floatval($_POST['servis_kendaraan'] ?? 0);
    $pajak_stnk         = floatval($_POST['pajak_stnk'] ?? 0);
    $transport_umum     = floatval($_POST['transport_umum'] ?? 0);
    $tol                = floatval($_POST['tol'] ?? 0);
    $parkir             = floatval($_POST['parkir'] ?? 0);

    // Makanan
    $makan_pagi         = floatval($_POST['makan_pagi'] ?? 0);
    $makan_siang        = floatval($_POST['makan_siang'] ?? 0);
    $makan_malam        = floatval($_POST['makan_malam'] ?? 0);
    $jajanan            = floatval($_POST['jajanan'] ?? 0);
    $makan_luar         = floatval($_POST['makan_luar'] ?? 0);
    $makanan_lain       = floatval($_POST['makanan_lain'] ?? 0);

    // Telepon, Listrik & Utilitas
    $telepon_rumah      = floatval($_POST['telepon_rumah'] ?? 0);
    $hp                 = floatval($_POST['hp'] ?? 0);
    $tv_kabel           = floatval($_POST['tv_kabel'] ?? 0);
    $gas                = floatval($_POST['gas'] ?? 0);
    $air_minum          = floatval($_POST['air_minum'] ?? 0);
    $air                = floatval($_POST['air'] ?? 0);
    $listrik            = floatval($_POST['listrik'] ?? 0);
    $internet           = floatval($_POST['internet'] ?? 0);
    $utilitas_lain      = floatval($_POST['utilitas_lain'] ?? 0);

    // Rekreasi
    $rek_keanggotaan    = floatval($_POST['rek_keanggotaan'] ?? 0);
    $rek_surat_kabar    = floatval($_POST['rek_surat_kabar'] ?? 0);
    $rek_acara          = floatval($_POST['rek_acara'] ?? 0);
    $rek_film           = floatval($_POST['rek_film'] ?? 0);
    $rek_musik          = floatval($_POST['rek_musik'] ?? 0);
    $rek_hobi           = floatval($_POST['rek_hobi'] ?? 0);
    $rek_liburan        = floatval($_POST['rek_liburan'] ?? 0);
    $rek_lain           = floatval($_POST['rek_lain'] ?? 0);

    // Kebutuhan Lain
    $pajak_penghasilan   = floatval($_POST['pajak_penghasilan'] ?? 0);
    $pengembangan_diri   = floatval($_POST['pengembangan_diri'] ?? 0);
    $kartu_kredit        = floatval($_POST['kartu_kredit'] ?? 0);
    $pendidikan_anak     = floatval($_POST['pendidikan_anak'] ?? 0);
    $asuransi_pendidikan = floatval($_POST['asuransi_pendidikan'] ?? 0);
    $mainan_anak         = floatval($_POST['mainan_anak'] ?? 0);
    $uang_saku           = floatval($_POST['uang_saku'] ?? 0);
    $pakaian_sepatu      = floatval($_POST['pakaian_sepatu'] ?? 0);
    $laundry             = floatval($_POST['laundry'] ?? 0);
    $donasi              = floatval($_POST['donasi'] ?? 0);
    $hadiah_out = floatval($_POST['hadiah_out'] ?? 0);
    $pembantu_supir      = floatval($_POST['pembantu_supir'] ?? 0);
    $kebutuhan_lain      = floatval($_POST['kebutuhan_lain'] ?? 0);

    // ==========================================
    // 3. KALKULASI TOTAL PENGELUARAN BULANAN
    // ==========================================
    $monthly_expense = 
        $sewa_rumah + $cicilan_rumah + $perawatan_rumah + $asuransi_alat_rt + $belanja_rt + $pbb + $keamanan_rt + $servis_alat + $rt_lain +
        $dokter + $obat_obatan + $checkup + $asuransi_kes + $fitness + $kesehatan_lain +
        $asuransi_kendaraan + $bbm + $cicilan_kendaraan + $servis_kendaraan + $pajak_stnk + $transport_umum + $tol + $parkir +
        $makan_pagi + $makan_siang + $makan_malam + $jajanan + $makan_luar + $makanan_lain +
        $telepon_rumah + $hp + $tv_kabel + $gas + $air_minum + $air + $listrik + $internet + $utilitas_lain +
        $rek_keanggotaan + $rek_surat_kabar + $rek_acara + $rek_film + $rek_musik + $rek_hobi + $rek_liburan + $rek_lain +
        $pajak_penghasilan + $pengembangan_diri + $kartu_kredit + $pendidikan_anak + $asuransi_pendidikan + $mainan_anak + $uang_saku + $pakaian_sepatu + $laundry + $donasi + $hadiah_out + $pembantu_supir + $kebutuhan_lain;

    // ==========================================
    // 4. SIMPAN KE DATABASE (UPDATE QUERY)
    // ==========================================
    $query = "UPDATE financial_assessment SET 
        gaji='$gaji', bonus='$bonus', komisi='$komisi', hadiah='$hadiah', untung_saham='$untung_saham', aktif_lain='$aktif_lain',
        bunga='$bunga', sewa_properti='$sewa_properti', laba_bisnis='$laba_bisnis', dividen='$dividen', royalti='$royalti', pasif_lain='$pasif_lain',
        sewa_rumah='$sewa_rumah', cicilan_rumah='$cicilan_rumah', perawatan_rumah='$perawatan_rumah', asuransi_alat_rt='$asuransi_alat_rt', belanja_rt='$belanja_rt', pbb='$pbb', keamanan_rt='$keamanan_rt', servis_alat='$servis_alat', rt_lain='$rt_lain',
        dokter='$dokter', obat_obatan='$obat_obatan', checkup='$checkup', asuransi_kes='$asuransi_kes', fitness='$fitness', kesehatan_lain='$kesehatan_lain',
        asuransi_kendaraan='$asuransi_kendaraan', bbm='$bbm', cicilan_kendaraan='$cicilan_kendaraan', servis_kendaraan='$servis_kendaraan', pajak_stnk='$pajak_stnk', transport_umum='$transport_umum', tol='$tol', parkir='$parkir',
        makan_pagi='$makan_pagi', makan_siang='$makan_siang', makan_malam='$makan_malam', jajanan='$jajanan', makan_luar='$makan_luar', makanan_lain='$makanan_lain',
        telepon_rumah='$telepon_rumah', hp='$hp', tv_kabel='$tv_kabel', gas='$gas', air_minum='$air_minum', air='$air', listrik='$listrik', internet='$internet', utilitas_lain='$utilitas_lain',
        rek_keanggotaan='$rek_keanggotaan', rek_surat_kabar='$rek_surat_kabar', rek_acara='$rek_acara', rek_film='$rek_film', rek_musik='$rek_musik', rek_hobi='$rek_hobi', rek_liburan='$rek_liburan', rek_lain='$rek_lain',
        pajak_penghasilan='$pajak_penghasilan', pengembangan_diri='$pengembangan_diri', kartu_kredit='$kartu_kredit', pendidikan_anak='$pendidikan_anak', asuransi_pendidikan='$asuransi_pendidikan', mainan_anak='$mainan_anak', uang_saku='$uang_saku', pakaian_sepatu='$pakaian_sepatu', laundry='$laundry', donasi='$donasi',hadiah_pengeluaran='$hadiah_out', pembantu_supir='$pembantu_supir', kebutuhan_lain='$kebutuhan_lain',
        monthly_expense='$monthly_expense',
        total_pengeluaran_bulanan='$monthly_expense'
        WHERE user_id='$user_id'";

    $result = mysqli_query($conn, $query);

    // Pengecekan Error Query (Opsional, sangat bagus untuk debugging)
    if (!$result) {
        die("Terjadi Kesalahan SQL: " . mysqli_error($conn));
    }

    // UPDATE DATA PENGELUARAN DI TABEL UTAMA USER
    mysqli_query($conn, "UPDATE users SET monthly_expense='$monthly_expense' WHERE id='$user_id'");

    // ARAHKAN KE STEP 3
    header("Location: assessment_step3.php");
    exit;
}
?>