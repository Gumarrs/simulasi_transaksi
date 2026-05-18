<?php
session_start();
require_once '../config/koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id = $_SESSION['user_id'];

    // PENGHASILAN
    $gaji     = floatval($_POST['gaji_bersih']);
    $bonus    = floatval($_POST['bonus']);
    $komisi   = floatval($_POST['komisi']);
    $hadiah   = floatval($_POST['hadiah']);
    $in_lain  = floatval($_POST['aktif_lain']);

    // TOTAL PENGHASILAN
    $total_income =
        $gaji +
        $bonus +
        $komisi +
        $hadiah +
        $in_lain;

    // TOTAL PENGELUARAN
    $monthly_expense = 0;

    foreach($_POST as $key => $value){

        if($key != 'gaji_bersih' &&
           $key != 'bonus' &&
           $key != 'komisi' &&
           $key != 'hadiah' &&
           $key != 'aktif_lain'){

            $monthly_expense += floatval($value);
        }
    }

    mysqli_query($conn, "

        UPDATE financial_assessment SET

        gaji='$gaji',
        bonus='$bonus',
        komisi='$komisi',
        hadiah='$hadiah',
        in_lain='$in_lain',

        monthly_expense='$monthly_expense',
        total_pengeluaran_bulanan='$monthly_expense'

        WHERE user_id='$user_id'

    ");

    mysqli_query($conn, "
        UPDATE users
        SET monthly_expense='$monthly_expense'
        WHERE id='$user_id'
    ");

    header("Location: assessment_step3.php");
    exit;
}
?>