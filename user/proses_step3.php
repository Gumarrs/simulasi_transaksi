<?php
session_start();
require_once '../config/koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id = $_SESSION['user_id'];

    $dplk    = floatval($_POST['dplk']);
    $bpjs    = floatval($_POST['bpjs']);
    $company = floatval($_POST['company']);

    $total_pension =
        $dplk +
        $bpjs +
        $company;

    // Ambil networth
    $q = mysqli_query($conn,
        "SELECT net_worth FROM financial_assessment WHERE user_id='$user_id'"
    );

    $data = mysqli_fetch_assoc($q);

    $net_worth = $data['net_worth'];

    // Modal Awal
    $modal_awal = $net_worth + $total_pension;

    // Update assessment
    mysqli_query($conn, "

        UPDATE financial_assessment SET

        up_dplk='$dplk',
        up_bpjs='$bpjs',
        up_company='$company',

        total_pension='$total_pension',
        modal_awal_simulasi='$modal_awal'

        WHERE user_id='$user_id'

    ");

    // Update user
    mysqli_query($conn, "

        UPDATE users SET

        balance='$modal_awal',
        is_assessment_done=1

        WHERE id='$user_id'

    ");

    header("Location: cover.php");
    exit;
}
?>