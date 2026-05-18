<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'peserta') {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id = $_SESSION['user_id'];

    // =========================
    // ASET
    // =========================

    $dana_tunai        = floatval($_POST['dana_tunai']);
    $tabungan_giro     = floatval($_POST['tabungan_giro']);
    $piutang           = floatval($_POST['piutang']);
    $likuid_lain       = floatval($_POST['likuid_lain']);

    $nilai_rumah       = floatval($_POST['nilai_rumah']);
    $nilai_kendaraan   = floatval($_POST['nilai_kendaraan']);
    $perhiasan         = floatval($_POST['perhiasan']);
    $pribadi_lain      = floatval($_POST['pribadi_lain']);

    $deposito          = floatval($_POST['deposito']);
    $obligasi          = floatval($_POST['obligasi']);
    $reksadana_saham   = floatval($_POST['reksadana_saham']);
    $emas              = floatval($_POST['emas']);
    $tanah             = floatval($_POST['tanah']);
    $bisnis            = floatval($_POST['bisnis']);
    $rumah_investasi   = floatval($_POST['rumah_kedua_dst']);
    $investasi_lain    = floatval($_POST['investasi_lain']);

    // =========================
    // UTANG
    // =========================

    $utang_cc          = floatval($_POST['utang_kartu_kredit']);
    $tagihan_lunas     = floatval($_POST['tagihan_belum_lunas']);
    $pinjaman_lain     = floatval($_POST['pinjaman_lainnya']);

    $utang_rumah       = floatval($_POST['utang_rumah']);
    $utang_rumah_2     = floatval($_POST['utang_rumah_kedua']);
    $utang_kendaraan   = floatval($_POST['utang_kendaraan']);

    // =========================
    // HITUNG TOTAL
    // =========================

    $total_aset =
        $dana_tunai +
        $tabungan_giro +
        $piutang +
        $likuid_lain +
        $nilai_rumah +
        $nilai_kendaraan +
        $perhiasan +
        $pribadi_lain +
        $deposito +
        $obligasi +
        $reksadana_saham +
        $emas +
        $tanah +
        $bisnis +
        $rumah_investasi +
        $investasi_lain;

    $total_utang =
        $utang_cc +
        $tagihan_lunas +
        $pinjaman_lain +
        $utang_rumah +
        $utang_rumah_2 +
        $utang_kendaraan;

    $net_worth = $total_aset - $total_utang;

    // =========================
    // CEK DATA
    // =========================

    $cek = mysqli_query($conn,
        "SELECT id FROM financial_assessment WHERE user_id='$user_id'"
    );

    if (mysqli_num_rows($cek) > 0) {

        $query = "
        UPDATE financial_assessment SET

        dana_tunai='$dana_tunai',
        tabungan_giro='$tabungan_giro',
        piutang='$piutang',
        likuid_lain='$likuid_lain',

        nilai_rumah='$nilai_rumah',
        nilai_kendaraan='$nilai_kendaraan',
        perhiasan='$perhiasan',
        pribadi_lain='$pribadi_lain',

        deposito='$deposito',
        obligasi='$obligasi',
        reksadana_saham='$reksadana_saham',
        emas='$emas',
        tanah='$tanah',
        bisnis='$bisnis',
        rumah_investasi='$rumah_investasi',
        investasi_lain='$investasi_lain',

        utang_cc='$utang_cc',
        tagihan_lunas='$tagihan_lunas',
        pinjaman_lain='$pinjaman_lain',

        utang_rumah='$utang_rumah',
        utang_rumah_2='$utang_rumah_2',
        utang_kendaraan='$utang_kendaraan',

        total_aset='$total_aset',
        total_utang='$total_utang',
        net_worth='$net_worth'

        WHERE user_id='$user_id'
        ";

    } else {

        $query = "
        INSERT INTO financial_assessment (

        user_id,

        dana_tunai,
        tabungan_giro,
        piutang,
        likuid_lain,

        nilai_rumah,
        nilai_kendaraan,
        perhiasan,
        pribadi_lain,

        deposito,
        obligasi,
        reksadana_saham,
        emas,
        tanah,
        bisnis,
        rumah_investasi,
        investasi_lain,

        utang_cc,
        tagihan_lunas,
        pinjaman_lain,

        utang_rumah,
        utang_rumah_2,
        utang_kendaraan,

        total_aset,
        total_utang,
        net_worth

        ) VALUES (

        '$user_id',

        '$dana_tunai',
        '$tabungan_giro',
        '$piutang',
        '$likuid_lain',

        '$nilai_rumah',
        '$nilai_kendaraan',
        '$perhiasan',
        '$pribadi_lain',

        '$deposito',
        '$obligasi',
        '$reksadana_saham',
        '$emas',
        '$tanah',
        '$bisnis',
        '$rumah_investasi',
        '$investasi_lain',

        '$utang_cc',
        '$tagihan_lunas',
        '$pinjaman_lain',

        '$utang_rumah',
        '$utang_rumah_2',
        '$utang_kendaraan',

        '$total_aset',
        '$total_utang',
        '$net_worth'
        )
        ";
    }

    if(mysqli_query($conn, $query)){

        $_SESSION['networth'] = $net_worth;

        header("Location: assessment_step2.php");
        exit;

    } else {

        echo mysqli_error($conn);

    }

}
?>