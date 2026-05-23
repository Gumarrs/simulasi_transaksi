<?php
session_start();
require_once '../config/koneksi.php'; // Sesuaikan path ini dengan letak file koneksi Anda

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// =========================================================================
// 1. PROSES UPDATE KETIKA FORM DISUBMIT
// =========================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Fungsi helper untuk memastikan data berupa angka
    function getNum($post_val) {
        return floatval($post_val ?? 0);
    }

    // --- STEP 1: ASET ---
    $dana_tunai       = getNum($_POST['dana_tunai']);
    $tabungan_giro    = getNum($_POST['tabungan_giro']);
    $piutang          = getNum($_POST['piutang']);
    $likuid_lain      = getNum($_POST['likuid_lain']);
    $nilai_rumah      = getNum($_POST['nilai_rumah']);
    $nilai_kendaraan  = getNum($_POST['nilai_kendaraan']);
    $perhiasan        = getNum($_POST['perhiasan']);
    $pribadi_lain     = getNum($_POST['pribadi_lain']);
    $deposito         = getNum($_POST['deposito']);
    $obligasi         = getNum($_POST['obligasi']);
    $reksadana_saham  = getNum($_POST['reksadana_saham']);
    $emas             = getNum($_POST['emas']);
    $tanah            = getNum($_POST['tanah']);
    $bisnis           = getNum($_POST['bisnis']);
    $rumah_investasi  = getNum($_POST['rumah_investasi']);
    $investasi_lain   = getNum($_POST['investasi_lain']);

    $total_aset = $dana_tunai + $tabungan_giro + $piutang + $likuid_lain + $nilai_rumah + $nilai_kendaraan + $perhiasan + $pribadi_lain + $deposito + $obligasi + $reksadana_saham + $emas + $tanah + $bisnis + $rumah_investasi + $investasi_lain;

    // --- STEP 1: UTANG ---
    $utang_cc         = getNum($_POST['utang_cc']);
    $tagihan_lunas    = getNum($_POST['tagihan_lunas']);
    $pinjaman_lain    = getNum($_POST['pinjaman_lain']);
    $utang_rumah      = getNum($_POST['utang_rumah']);
    $utang_rumah_2    = getNum($_POST['utang_rumah_2']);
    $utang_kendaraan  = getNum($_POST['utang_kendaraan']);

    $total_utang = $utang_cc + $tagihan_lunas + $pinjaman_lain + $utang_rumah + $utang_rumah_2 + $utang_kendaraan;
    $net_worth   = $total_aset - $total_utang;

    // --- STEP 2: PENGHASILAN ---
    $gaji           = getNum($_POST['gaji']);
    $bonus          = getNum($_POST['bonus']);
    $komisi         = getNum($_POST['komisi']);
    $hadiah         = getNum($_POST['hadiah']); // Hadiah Pemasukan
    $untung_saham   = getNum($_POST['untung_saham']);
    $aktif_lain     = getNum($_POST['aktif_lain']);
    $bunga          = getNum($_POST['bunga']);
    $sewa_properti  = getNum($_POST['sewa_properti']);
    $laba_bisnis    = getNum($_POST['laba_bisnis']);
    $dividen        = getNum($_POST['dividen']);
    $royalti        = getNum($_POST['royalti']);
    $pasif_lain     = getNum($_POST['pasif_lain']);

    // --- STEP 2: PENGELUARAN ---
    $sewa_rumah         = getNum($_POST['sewa_rumah']);
    $cicilan_rumah      = getNum($_POST['cicilan_rumah']);
    $perawatan_rumah    = getNum($_POST['perawatan_rumah']);
    $asuransi_alat_rt   = getNum($_POST['asuransi_alat_rt']);
    $belanja_rt         = getNum($_POST['belanja_rt']);
    $pbb                = getNum($_POST['pbb']);
    $keamanan_rt        = getNum($_POST['keamanan_rt']);
    $servis_alat        = getNum($_POST['servis_alat']);
    $rt_lain            = getNum($_POST['rt_lain']);
    $dokter             = getNum($_POST['dokter']);
    $obat_obatan        = getNum($_POST['obat_obatan']);
    $checkup            = getNum($_POST['checkup']);
    $asuransi_kes       = getNum($_POST['asuransi_kes']);
    $fitness            = getNum($_POST['fitness']);
    $kesehatan_lain     = getNum($_POST['kesehatan_lain']);
    $asuransi_kendaraan = getNum($_POST['asuransi_kendaraan']);
    $bbm                = getNum($_POST['bbm']);
    $cicilan_kendaraan  = getNum($_POST['cicilan_kendaraan']);
    $servis_kendaraan   = getNum($_POST['servis_kendaraan']);
    $pajak_stnk         = getNum($_POST['pajak_stnk']);
    $transport_umum     = getNum($_POST['transport_umum']);
    $tol                = getNum($_POST['tol']);
    $parkir             = getNum($_POST['parkir']);
    $makan_pagi         = getNum($_POST['makan_pagi']);
    $makan_siang        = getNum($_POST['makan_siang']);
    $makan_malam        = getNum($_POST['makan_malam']);
    $jajanan            = getNum($_POST['jajanan']);
    $makan_luar         = getNum($_POST['makan_luar']);
    $makanan_lain       = getNum($_POST['makanan_lain']);
    $telepon_rumah      = getNum($_POST['telepon_rumah']);
    $hp                 = getNum($_POST['hp']);
    $tv_kabel           = getNum($_POST['tv_kabel']);
    $gas                = getNum($_POST['gas']);
    $air_minum          = getNum($_POST['air_minum']);
    $air                = getNum($_POST['air']);
    $listrik            = getNum($_POST['listrik']);
    $internet           = getNum($_POST['internet']);
    $utilitas_lain      = getNum($_POST['utilitas_lain']);
    $rek_keanggotaan    = getNum($_POST['rek_keanggotaan']);
    $rek_surat_kabar    = getNum($_POST['rek_surat_kabar']);
    $rek_acara          = getNum($_POST['rek_acara']);
    $rek_film           = getNum($_POST['rek_film']);
    $rek_musik          = getNum($_POST['rek_musik']);
    $rek_hobi           = getNum($_POST['rek_hobi']);
    $rek_liburan        = getNum($_POST['rek_liburan']);
    $rek_lain           = getNum($_POST['rek_lain']);
    $pajak_penghasilan  = getNum($_POST['pajak_penghasilan']);
    $pengembangan_diri  = getNum($_POST['pengembangan_diri']);
    $kartu_kredit       = getNum($_POST['kartu_kredit']);
    $pendidikan_anak    = getNum($_POST['pendidikan_anak']);
    $asuransi_pendidikan= getNum($_POST['asuransi_pendidikan']);
    $mainan_anak        = getNum($_POST['mainan_anak']);
    $uang_saku          = getNum($_POST['uang_saku']);
    $pakaian_sepatu     = getNum($_POST['pakaian_sepatu']);
    $laundry            = getNum($_POST['laundry']);
    $donasi             = getNum($_POST['donasi']);
    $hadiah_pengeluaran = getNum($_POST['hadiah_pengeluaran']); // Hadiah Pengeluaran
    $pembantu_supir     = getNum($_POST['pembantu_supir']);
    $kebutuhan_lain     = getNum($_POST['kebutuhan_lain']);

    $monthly_expense = 
        $sewa_rumah + $cicilan_rumah + $perawatan_rumah + $asuransi_alat_rt + $belanja_rt + $pbb + $keamanan_rt + $servis_alat + $rt_lain +
        $dokter + $obat_obatan + $checkup + $asuransi_kes + $fitness + $kesehatan_lain +
        $asuransi_kendaraan + $bbm + $cicilan_kendaraan + $servis_kendaraan + $pajak_stnk + $transport_umum + $tol + $parkir +
        $makan_pagi + $makan_siang + $makan_malam + $jajanan + $makan_luar + $makanan_lain +
        $telepon_rumah + $hp + $tv_kabel + $gas + $air_minum + $air + $listrik + $internet + $utilitas_lain +
        $rek_keanggotaan + $rek_surat_kabar + $rek_acara + $rek_film + $rek_musik + $rek_hobi + $rek_liburan + $rek_lain +
        $pajak_penghasilan + $pengembangan_diri + $kartu_kredit + $pendidikan_anak + $asuransi_pendidikan + $mainan_anak + $uang_saku + $pakaian_sepatu + $laundry + $donasi + $hadiah_pengeluaran + $pembantu_supir + $kebutuhan_lain;

    // --- STEP 3: DANA PENSIUN ---
    $up_dplk    = getNum($_POST['up_dplk']);
    $up_bpjs    = getNum($_POST['up_bpjs']);
    $up_company = getNum($_POST['up_company']);

    $total_pension = $up_dplk + $up_bpjs + $up_company;
    
    // --- KALKULASI MODAL AWAL ---
    $modal_awal_simulasi = $net_worth + $total_pension;

    // QUERY UPDATE TABEL financial_assessment
    $query = "UPDATE financial_assessment SET 
        dana_tunai='$dana_tunai', tabungan_giro='$tabungan_giro', piutang='$piutang', likuid_lain='$likuid_lain',
        nilai_rumah='$nilai_rumah', nilai_kendaraan='$nilai_kendaraan', perhiasan='$perhiasan', pribadi_lain='$pribadi_lain',
        deposito='$deposito', obligasi='$obligasi', reksadana_saham='$reksadana_saham', emas='$emas', tanah='$tanah', bisnis='$bisnis', rumah_investasi='$rumah_investasi', investasi_lain='$investasi_lain',
        utang_cc='$utang_cc', tagihan_lunas='$tagihan_lunas', pinjaman_lain='$pinjaman_lain', utang_rumah='$utang_rumah', utang_rumah_2='$utang_rumah_2', utang_kendaraan='$utang_kendaraan',
        total_aset='$total_aset', total_utang='$total_utang', net_worth='$net_worth',
        gaji='$gaji', bonus='$bonus', komisi='$komisi', hadiah='$hadiah', untung_saham='$untung_saham', aktif_lain='$aktif_lain',
        bunga='$bunga', sewa_properti='$sewa_properti', laba_bisnis='$laba_bisnis', dividen='$dividen', royalti='$royalti', pasif_lain='$pasif_lain',
        sewa_rumah='$sewa_rumah', cicilan_rumah='$cicilan_rumah', perawatan_rumah='$perawatan_rumah', asuransi_alat_rt='$asuransi_alat_rt', belanja_rt='$belanja_rt', pbb='$pbb', keamanan_rt='$keamanan_rt', servis_alat='$servis_alat', rt_lain='$rt_lain',
        dokter='$dokter', obat_obatan='$obat_obatan', checkup='$checkup', asuransi_kes='$asuransi_kes', fitness='$fitness', kesehatan_lain='$kesehatan_lain',
        asuransi_kendaraan='$asuransi_kendaraan', bbm='$bbm', cicilan_kendaraan='$cicilan_kendaraan', servis_kendaraan='$servis_kendaraan', pajak_stnk='$pajak_stnk', transport_umum='$transport_umum', tol='$tol', parkir='$parkir',
        makan_pagi='$makan_pagi', makan_siang='$makan_siang', makan_malam='$makan_malam', jajanan='$jajanan', makan_luar='$makan_luar', makanan_lain='$makanan_lain',
        telepon_rumah='$telepon_rumah', hp='$hp', tv_kabel='$tv_kabel', gas='$gas', air_minum='$air_minum', air='$air', listrik='$listrik', internet='$internet', utilitas_lain='$utilitas_lain',
        rek_keanggotaan='$rek_keanggotaan', rek_surat_kabar='$rek_surat_kabar', rek_acara='$rek_acara', rek_film='$rek_film', rek_musik='$rek_musik', rek_hobi='$rek_hobi', rek_liburan='$rek_liburan', rek_lain='$rek_lain',
        pajak_penghasilan='$pajak_penghasilan', pengembangan_diri='$pengembangan_diri', kartu_kredit='$kartu_kredit', pendidikan_anak='$pendidikan_anak', asuransi_pendidikan='$asuransi_pendidikan', mainan_anak='$mainan_anak', uang_saku='$uang_saku', pakaian_sepatu='$pakaian_sepatu', laundry='$laundry', donasi='$donasi', hadiah_pengeluaran='$hadiah_pengeluaran', pembantu_supir='$pembantu_supir', kebutuhan_lain='$kebutuhan_lain',
        monthly_expense='$monthly_expense', total_pengeluaran_bulanan='$monthly_expense',
        up_dplk='$up_dplk', up_bpjs='$up_bpjs', up_company='$up_company',
        total_pension='$total_pension', modal_awal_simulasi='$modal_awal_simulasi'
        WHERE user_id='$user_id'";

    $result = mysqli_query($conn, $query);

    if (!$result) {
        die("Terjadi Kesalahan SQL: " . mysqli_error($conn));
    }

    // UPDATE TABEL users (Update modal/balance simulasi ke profil utama)
    mysqli_query($conn, "UPDATE users SET balance='$modal_awal_simulasi', monthly_expense='$monthly_expense' WHERE id='$user_id'");

    // Kembali ke profil dengan alert sukses
echo '
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<script>

Swal.fire({

    icon: "success",

    title: "Berhasil",

    html: `
        <div style="font-size:14px;">
            Data Finansial berhasil diupdate!<br>
            Modal Awal Simulasi telah dihitung ulang.
        </div>
    `,

    confirmButtonText: "Kembali",

    confirmButtonColor: "#0d6efd",

    allowOutsideClick: false

}).then(() => {

    window.location.href = "profile.php";

});

</script>

</body>
</html>
';

exit;
}

// =========================================================================
// 2. AMBIL DATA SAAT INI UNTUK PRE-FILL FORM
// =========================================================================
$q_data = mysqli_query($conn, "SELECT * FROM financial_assessment WHERE user_id='$user_id'");
$data = mysqli_fetch_assoc($q_data);

// Fungsi bantu untuk menampilkan data agar tidak error jika null
function val($key, $data) {
    return isset($data[$key]) ? floatval($data[$key]) : 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Finansial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; padding-bottom: 80px; }
        .mobile-container { max-width: 480px; margin: auto; background: #fff; min-height: 100vh; padding: 20px; box-shadow: 0 0 15px rgba(0,0,0,0.05); }
        .section-title { padding: 10px; font-weight: bold; font-size: 0.9rem; border-radius: 5px; margin-top: 25px; margin-bottom: 15px; text-transform: uppercase; }
        .st-asset { background: #e0f3ff; color: #0d6efd; }
        .st-income { background: #e6f8f0; color: #198754; }
        .st-expense { background: #f8d7da; color: #dc3545; }
        .st-pension { background: #fff3cd; color: #ffc107; }
        .form-label { font-size: 0.85rem; font-weight: 600; color: #555; margin-bottom: 4px; }
        .header-back { text-decoration: none; color: #555; font-size: 0.9rem; font-weight: 600; display: inline-block; margin-bottom: 15px;}
        .step-badge { font-size: 0.75rem; background: #333; color: #fff; padding: 3px 8px; border-radius: 4px; margin-bottom: 10px; display: inline-block;}
    </style>
</head>
<body>

<div class="mobile-container">
    
    <a href="profile.php" class="header-back"><i class="fa-solid fa-arrow-left me-2"></i>Kembali ke Profil</a>
    
    <h3 class="fw-bold mb-1">Edit Finansial</h3>
    <p class="text-muted small">Perbaiki data pada lembar finansial check-up Anda.</p>
    <hr class="mb-4">

    <form action="" method="POST" id="editForm">

        <span class="step-badge">Tahap 1</span>
        <h5 class="fw-bold text-primary mb-0"><i class="fa-solid fa-building-columns me-2"></i>ASET & KEKAYAAN</h5>

        <div class="section-title st-asset">Aset Likuid (Tunai)</div>
        <div class="mb-2"><label class="form-label">Dana Tunai</label><input type="text" name="dana_tunai" class="form-control rp-input" value="<?= val('dana_tunai', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Tabungan & Giro</label><input type="text" name="tabungan_giro" class="form-control rp-input" value="<?= val('tabungan_giro', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Piutang</label><input type="text" name="piutang" class="form-control rp-input" value="<?= val('piutang', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Likuid Lainnya</label><input type="text" name="likuid_lain" class="form-control rp-input" value="<?= val('likuid_lain', $data) ?>"></div>

        <div class="section-title st-asset">Aset Pribadi</div>
        <div class="mb-2"><label class="form-label">Nilai Rumah Tinggal</label><input type="text" name="nilai_rumah" class="form-control rp-input" value="<?= val('nilai_rumah', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Nilai Kendaraan Pribadi</label><input type="text" name="nilai_kendaraan" class="form-control rp-input" value="<?= val('nilai_kendaraan', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Perhiasan / Koleksi</label><input type="text" name="perhiasan" class="form-control rp-input" value="<?= val('perhiasan', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Aset Pribadi Lainnya</label><input type="text" name="pribadi_lain" class="form-control rp-input" value="<?= val('pribadi_lain', $data) ?>"></div>

        <div class="section-title st-asset">Aset Investasi</div>
        <div class="mb-2"><label class="form-label">Deposito</label><input type="text" name="deposito" class="form-control rp-input" value="<?= val('deposito', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Obligasi</label><input type="text" name="obligasi" class="form-control rp-input" value="<?= val('obligasi', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Reksadana / Saham</label><input type="text" name="reksadana_saham" class="form-control rp-input" value="<?= val('reksadana_saham', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Emas Logam Mulia</label><input type="text" name="emas" class="form-control rp-input" value="<?= val('emas', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Tanah (Kosong)</label><input type="text" name="tanah" class="form-control rp-input" value="<?= val('tanah', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Nilai Bisnis Anda</label><input type="text" name="bisnis" class="form-control rp-input" value="<?= val('bisnis', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Rumah Investasi (Disewakan)</label><input type="text" name="rumah_investasi" class="form-control rp-input" value="<?= val('rumah_investasi', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Investasi Lainnya</label><input type="text" name="investasi_lain" class="form-control rp-input" value="<?= val('investasi_lain', $data) ?>"></div>

        <div class="section-title st-expense"><i class="fa-solid fa-hand-holding-dollar"></i> KEWAJIBAN / UTANG</div>
        <div class="mb-2"><label class="form-label">Utang Kartu Kredit</label><input type="text" name="utang_cc" class="form-control rp-input" value="<?= val('utang_cc', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Tagihan Belum Lunas</label><input type="text" name="tagihan_lunas" class="form-control rp-input" value="<?= val('tagihan_lunas', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Pinjaman Lainnya (KTA dll)</label><input type="text" name="pinjaman_lain" class="form-control rp-input" value="<?= val('pinjaman_lain', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Utang Rumah (KPR 1)</label><input type="text" name="utang_rumah" class="form-control rp-input" value="<?= val('utang_rumah', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Utang Rumah (KPR 2 dst)</label><input type="text" name="utang_rumah_2" class="form-control rp-input" value="<?= val('utang_rumah_2', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Utang Mobil/Motor</label><input type="text" name="utang_kendaraan" class="form-control rp-input" value="<?= val('utang_kendaraan', $data) ?>"></div>


        <hr class="mt-5 mb-4">
        <span class="step-badge">Tahap 2</span>
        <h5 class="fw-bold text-success mb-0"><i class="fa-solid fa-wallet me-2"></i>ARUS KAS BULANAN</h5>

        <div class="section-title st-income">PENGHASILAN (AKTIF)</div>
        <div class="mb-2"><label class="form-label">Gaji Bersih</label><input type="text" name="gaji" class="form-control rp-input" value="<?= val('gaji', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Bonus</label><input type="text" name="bonus" class="form-control rp-input" value="<?= val('bonus', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Komisi</label><input type="text" name="komisi" class="form-control rp-input" value="<?= val('komisi', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Hadiah</label><input type="text" name="hadiah" class="form-control rp-input" value="<?= val('hadiah', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Keuntungan Saham dll</label><input type="text" name="untung_saham" class="form-control rp-input" value="<?= val('untung_saham', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Lain-lain (Aktif)</label><input type="text" name="aktif_lain" class="form-control rp-input" value="<?= val('aktif_lain', $data) ?>"></div>

        <div class="section-title st-income">PENGHASILAN (PASIF)</div>
        <div class="mb-2"><label class="form-label">Bunga</label><input type="text" name="bunga" class="form-control rp-input" value="<?= val('bunga', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Sewa (Rumah, Ruko)</label><input type="text" name="sewa_properti" class="form-control rp-input" value="<?= val('sewa_properti', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Laba Bisnis Pribadi</label><input type="text" name="laba_bisnis" class="form-control rp-input" value="<?= val('laba_bisnis', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Dividen</label><input type="text" name="dividen" class="form-control rp-input" value="<?= val('dividen', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Royalti</label><input type="text" name="royalti" class="form-control rp-input" value="<?= val('royalti', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Lain-lain (Pasif)</label><input type="text" name="pasif_lain" class="form-control rp-input" value="<?= val('pasif_lain', $data) ?>"></div>

        <div class="section-title st-expense"><i class="fa-solid fa-house-user"></i> PENGELUARAN RUMAH TANGGA</div>
        <div class="mb-2"><label class="form-label">Sewa Rumah</label><input type="text" name="sewa_rumah" class="form-control rp-input" value="<?= val('sewa_rumah', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Cicilan Rumah</label><input type="text" name="cicilan_rumah" class="form-control rp-input" value="<?= val('cicilan_rumah', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Perawatan Rumah</label><input type="text" name="perawatan_rumah" class="form-control rp-input" value="<?= val('perawatan_rumah', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Asuransi Alat Rumah Tangga</label><input type="text" name="asuransi_alat_rt" class="form-control rp-input" value="<?= val('asuransi_alat_rt', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Belanja Bulanan</label><input type="text" name="belanja_rt" class="form-control rp-input" value="<?= val('belanja_rt', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Pajak Bangunan (PBB)</label><input type="text" name="pbb" class="form-control rp-input" value="<?= val('pbb', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Keamanan & Kebersihan</label><input type="text" name="keamanan_rt" class="form-control rp-input" value="<?= val('keamanan_rt', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Servis Alat-alat</label><input type="text" name="servis_alat" class="form-control rp-input" value="<?= val('servis_alat', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Lain-lain (Rumah Tangga)</label><input type="text" name="rt_lain" class="form-control rp-input" value="<?= val('rt_lain', $data) ?>"></div>

        <div class="section-title st-expense">KESEHATAN</div>
        <div class="mb-2"><label class="form-label">Dokter</label><input type="text" name="dokter" class="form-control rp-input" value="<?= val('dokter', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Obat-obatan</label><input type="text" name="obat_obatan" class="form-control rp-input" value="<?= val('obat_obatan', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Medical Check Up</label><input type="text" name="checkup" class="form-control rp-input" value="<?= val('checkup', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Asuransi Kesehatan</label><input type="text" name="asuransi_kes" class="form-control rp-input" value="<?= val('asuransi_kes', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Fitness / Gym</label><input type="text" name="fitness" class="form-control rp-input" value="<?= val('fitness', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Lain-lain</label><input type="text" name="kesehatan_lain" class="form-control rp-input" value="<?= val('kesehatan_lain', $data) ?>"></div>

        <div class="section-title st-expense">TRANSPORTASI</div>
        <div class="mb-2"><label class="form-label">Asuransi Kendaraan</label><input type="text" name="asuransi_kendaraan" class="form-control rp-input" value="<?= val('asuransi_kendaraan', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">BBM</label><input type="text" name="bbm" class="form-control rp-input" value="<?= val('bbm', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Cicilan Mobil/Motor</label><input type="text" name="cicilan_kendaraan" class="form-control rp-input" value="<?= val('cicilan_kendaraan', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Servis Kendaraan</label><input type="text" name="servis_kendaraan" class="form-control rp-input" value="<?= val('servis_kendaraan', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Pajak Kendaraan, STNK</label><input type="text" name="pajak_stnk" class="form-control rp-input" value="<?= val('pajak_stnk', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Taksi / Transportasi Umum</label><input type="text" name="transport_umum" class="form-control rp-input" value="<?= val('transport_umum', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Biaya Tol</label><input type="text" name="tol" class="form-control rp-input" value="<?= val('tol', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Biaya Parkir</label><input type="text" name="parkir" class="form-control rp-input" value="<?= val('parkir', $data) ?>"></div>

        <div class="section-title st-expense">MAKANAN</div>
        <div class="mb-2"><label class="form-label">Makan Pagi</label><input type="text" name="makan_pagi" class="form-control rp-input" value="<?= val('makan_pagi', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Makan Siang</label><input type="text" name="makan_siang" class="form-control rp-input" value="<?= val('makan_siang', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Makan Malam</label><input type="text" name="makan_malam" class="form-control rp-input" value="<?= val('makan_malam', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Jajanan</label><input type="text" name="jajanan" class="form-control rp-input" value="<?= val('jajanan', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Makan/Minum di Luar</label><input type="text" name="makan_luar" class="form-control rp-input" value="<?= val('makan_luar', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Lain-lain (Makanan)</label><input type="text" name="makanan_lain" class="form-control rp-input" value="<?= val('makanan_lain', $data) ?>"></div>

        <div class="section-title st-expense">TELEPON, LISTRIK & UTILITAS</div>
        <div class="mb-2"><label class="form-label">Telepon Rumah</label><input type="text" name="telepon_rumah" class="form-control rp-input" value="<?= val('telepon_rumah', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">HP</label><input type="text" name="hp" class="form-control rp-input" value="<?= val('hp', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">TV Kabel</label><input type="text" name="tv_kabel" class="form-control rp-input" value="<?= val('tv_kabel', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Gas Elpiji</label><input type="text" name="gas" class="form-control rp-input" value="<?= val('gas', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Air Minum</label><input type="text" name="air_minum" class="form-control rp-input" value="<?= val('air_minum', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Air</label><input type="text" name="air" class="form-control rp-input" value="<?= val('air', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Listrik</label><input type="text" name="listrik" class="form-control rp-input" value="<?= val('listrik', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Internet</label><input type="text" name="internet" class="form-control rp-input" value="<?= val('internet', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Lain-lain</label><input type="text" name="utilitas_lain" class="form-control rp-input" value="<?= val('utilitas_lain', $data) ?>"></div>

        <div class="section-title st-expense">REKREASI</div>
        <div class="mb-2"><label class="form-label">Keanggotaan</label><input type="text" name="rek_keanggotaan" class="form-control rp-input" value="<?= val('rek_keanggotaan', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Surat Kabar, Majalah</label><input type="text" name="rek_surat_kabar" class="form-control rp-input" value="<?= val('rek_surat_kabar', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Acara/Pesta</label><input type="text" name="rek_acara" class="form-control rp-input" value="<?= val('rek_acara', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Film, Pertunjukan</label><input type="text" name="rek_film" class="form-control rp-input" value="<?= val('rek_film', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Musik</label><input type="text" name="rek_musik" class="form-control rp-input" value="<?= val('rek_musik', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Hobi</label><input type="text" name="rek_hobi" class="form-control rp-input" value="<?= val('rek_hobi', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Liburan</label><input type="text" name="rek_liburan" class="form-control rp-input" value="<?= val('rek_liburan', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Lain-lain</label><input type="text" name="rek_lain" class="form-control rp-input" value="<?= val('rek_lain', $data) ?>"></div>

        <div class="section-title st-expense">KEBUTUHAN LAIN</div>
        <div class="mb-2"><label class="form-label">Pajak Penghasilan</label><input type="text" name="pajak_penghasilan" class="form-control rp-input" value="<?= val('pajak_penghasilan', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Pengembangan Diri</label><input type="text" name="pengembangan_diri" class="form-control rp-input" value="<?= val('pengembangan_diri', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Kartu Kredit</label><input type="text" name="kartu_kredit" class="form-control rp-input" value="<?= val('kartu_kredit', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Pendidikan Anak</label><input type="text" name="pendidikan_anak" class="form-control rp-input" value="<?= val('pendidikan_anak', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Asuransi Pendidikan</label><input type="text" name="asuransi_pendidikan" class="form-control rp-input" value="<?= val('asuransi_pendidikan', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Mainan Anak</label><input type="text" name="mainan_anak" class="form-control rp-input" value="<?= val('mainan_anak', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Uang Saku</label><input type="text" name="uang_saku" class="form-control rp-input" value="<?= val('uang_saku', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Pakaian, Sepatu dll</label><input type="text" name="pakaian_sepatu" class="form-control rp-input" value="<?= val('pakaian_sepatu', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Laundry, Dry Clean</label><input type="text" name="laundry" class="form-control rp-input" value="<?= val('laundry', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Donasi, Sosial/Amal</label><input type="text" name="donasi" class="form-control rp-input" value="<?= val('donasi', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Hadiah (Pengeluaran)</label><input type="text" name="hadiah_pengeluaran" class="form-control rp-input" value="<?= val('hadiah_pengeluaran', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Pembantu, Supir</label><input type="text" name="pembantu_supir" class="form-control rp-input" value="<?= val('pembantu_supir', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Lain-lain</label><input type="text" name="kebutuhan_lain" class="form-control rp-input" value="<?= val('kebutuhan_lain', $data) ?>"></div>


        <hr class="mt-5 mb-4">
        <span class="step-badge" style="background:#ffc107; color:#000;">Tahap 3</span>
        <h5 class="fw-bold mb-0 text-warning" style="color: #d39e00 !important;"><i class="fa-solid fa-piggy-bank me-2"></i>DANA PENSIUN SAAT INI</h5>

        <div class="section-title st-pension">Program Pensiun yang Dimiliki</div>
        <div class="mb-2"><label class="form-label">Saldo DPLK / Reksa Dana Pensiun</label><input type="text" name="up_dplk" class="form-control rp-input" value="<?= val('up_dplk', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Saldo JHT/JP (BPJS Ketenagakerjaan)</label><input type="text" name="up_bpjs" class="form-control rp-input" value="<?= val('up_bpjs', $data) ?>"></div>
        <div class="mb-2"><label class="form-label">Pesangon / Pensiun dari Perusahaan</label><input type="text" name="up_company" class="form-control rp-input" value="<?= val('up_company', $data) ?>"></div>


        <button type="button" id="openConfirmModal" class="btn btn-primary w-100 fw-bold mt-5 py-3 shadow">
            SIMPAN PERUBAHAN & HITUNG ULANG <i class="fa-solid fa-arrows-rotate ms-1"></i>
        </button>

    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {

    const inputs = document.querySelectorAll('.rp-input');

    // 1. Fungsi Format ke Rupiah
    function formatRupiah(angka) {
        let number_string = angka.toString().replace(/[^,\d]/g, '');
        return number_string.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    // 2. Terapan format pada saat halaman pertama kali load (dari database)
    inputs.forEach(input => {
        // Hapus pecahan decimal (.00) dari DB agar tidak mengganggu regex format titik
        let val_awal = input.value.split('.')[0]; 
        input.value = formatRupiah(val_awal);

        // 3. Terapan format secara live saat user mengetik
        input.addEventListener('input', function(e) {
            let val = this.value.replace(/\D/g, ''); // Hapus text selain angka
            
            // Hapus angka nol di awal, kecuali jika nilainya cuma "0"
            val = val.replace(/^0+/, '');
            if(val === '') val = '0';

            this.value = formatRupiah(val);
        });

        // 4. Kosongkan 0 saat diklik agar mempermudah pengetikan
        input.addEventListener('focus', function(){
            if(this.value === '0') this.value = '';
        });

        // Kembalikan 0 jika blur/ditinggal kosong
        input.addEventListener('blur', function(){
            if(this.value === '') this.value = '0';
        });
    });

    // 5. Sebelum form di-submit, hapus titik-titik pada input agar terbaca di DB (MariaDB)
    document.getElementById('editForm').addEventListener('submit', function() {
        inputs.forEach(input => {
            input.value = input.value.replace(/\./g, '');
        });
    });

});
</script>
<!-- MODAL KONFIRMASI -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 18px;">

            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary">
                    <i class="fa-solid fa-circle-question me-2"></i>
                    Konfirmasi Perubahan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body pt-2">
                <p class="mb-2 fw-semibold">
                    Apakah Anda yakin ingin menyimpan perubahan data finansial?
                </p>

                <small class="text-muted">
                    Sistem akan menghitung ulang:
                </small>

                <ul class="small text-muted mt-2 mb-0">
                    <li>Total aset & kewajiban</li>
                    <li>Cashflow bulanan</li>
                    <li>Total dana pensiun</li>
                    <li>Modal awal simulasi</li>
                </ul>
            </div>

            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">
                    Batal
                </button>

                <button type="button" id="confirmSubmitBtn" class="btn btn-primary px-4 fw-bold">
                    Ya, Simpan
                </button>
            </div>

        </div>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {

    const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));

    // Buka modal
    document.getElementById('openConfirmModal').addEventListener('click', function () {
        confirmModal.show();
    });

    // Submit form setelah konfirmasi
    document.getElementById('confirmSubmitBtn').addEventListener('click', function () {

        document.querySelectorAll('.rp-input').forEach(input => {
            input.value = input.value.replace(/\./g, '');
        });

        document.getElementById('editForm').submit();

    });

});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>