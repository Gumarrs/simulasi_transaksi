<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Ambil Networth dari STEP 1
|--------------------------------------------------------------------------
*/
$networth = $_SESSION['networth'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 3 - Dana Pensiun</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>

        body{
            background-color:#f4f7f6;
        }

        .mobile-container{
            max-width:480px;
            margin:auto;
            background:#fff;
            min-height:100vh;
            box-shadow:0 0 15px rgba(0,0,0,0.05);
        }

        .section-title{
            font-weight:700;
            font-size:15px;
            margin-top:24px;
            margin-bottom:15px;
            text-transform:uppercase;
            letter-spacing:.5px;
        }

        .summary-box{
            background:#f8f9fa;
            border:1px solid #ddd;
            padding:20px;
            border-radius:14px;
        }

        .summary-item{
            margin-bottom:18px;
        }

        .summary-item:last-child{
            margin-bottom:0;
        }

        .summary-label{
            font-size:13px;
            opacity:.75;
            margin-bottom:4px;
        }

        .summary-value{
            font-size:24px;
            font-weight:800;
        }

    </style>
</head>
<script>

document.addEventListener("DOMContentLoaded", function(){

    // SEMUA INPUT NUMBER UANG
    const currencyInputs = document.querySelectorAll('input[type="number"]');

    currencyInputs.forEach(input => {

        // UBAH TYPE MENJADI TEXT
        input.setAttribute('type', 'text');

        // FORMAT SAAT KETIK
        input.addEventListener('input', function(e){

            // HAPUS SELAIN ANGKA
            let value = this.value.replace(/\D/g, '');

            // FORMAT TITIK
            this.value = formatRupiah(value);

        });

    });

    // SAAT FORM SUBMIT
    document.querySelectorAll('form').forEach(form => {

        form.addEventListener('submit', function(){

            currencyInputs.forEach(input => {

                // HAPUS TITIK AGAR MASUK DB JADI ANGKA
                input.value = input.value.replace(/\./g, '');

            });

        });

    });

    // FORMAT RUPIAH
    function formatRupiah(angka){

        return angka.replace(/\B(?=(\d{3})+(?!\d))/g, ".");

    }

});
</script>
<body>

<div class="mobile-container p-4">

    <!-- HEADER -->
    <div class="mb-4">
        <span class="text-muted small fw-bold text-uppercase">
            Step 3 dari 3
        </span>

        <h4 class="fw-bold text-primary mt-1">
            Estimasi Dana Pensiun
        </h4>

        <p class="text-muted small">
            Input dana yang akan Anda terima saat pensiun nanti.
        </p>
    </div>

    <form action="proses_step3.php" method="POST">

        <!-- INPUT -->
        <div class="section-title text-primary">
            <i class="fa-solid fa-wallet"></i>
            UANG PESANGON
        </div>

        <div class="mb-3">
            <label class="form-label">DPLK</label>

            <input
                type="number"
                class="form-control pension-input"
                name="dplk"
                value="0"
            >
        </div>

        <div class="mb-3">
            <label class="form-label">
                BPJS Ketenagakerjaan
            </label>

            <input
                type="number"
                class="form-control pension-input"
                name="bpjs"
                value="0"
            >
        </div>

        <div class="mb-4">
            <label class="form-label">
                Company Pension Scheme
            </label>

            <input
                type="number"
                class="form-control pension-input"
                name="company"
                value="0"
            >
        </div>


        <!-- SUMMARY -->
        <div class="summary-box">

            <!-- TOTAL PESANGON -->
            <div class="summary-item">

                <div class="summary-label">
                    Total Uang Pesangon
                </div>

                <div
                    class="summary-value text-success"
                    id="displayPesangon"
                >
                    Rp 0
                </div>

            </div>

            <hr>

            <!-- NETWORTH -->
            <div class="summary-item">

                <div class="summary-label">
                    Kekayaan Bersih (Net Worth)
                </div>

                <div
                    class="summary-value text-primary"
                    id="displayNetworth"
                >
                    Rp <?= number_format($networth,0,',','.') ?>
                </div>

            </div>

            <hr>

            <!-- TOTAL HARTA -->
            <div class="summary-item">

                <div class="summary-label">
                    Total Harta
                </div>

                <div
                    class="summary-value text-dark"
                    id="displayTotalHarta"
                >
                    Rp <?= number_format($networth,0,',','.') ?>
                </div>

            </div>

        </div>

        <!-- ALERT -->
        <div class="alert alert-warning border-0 small mt-4">

            Setelah ini,
            <strong>Total Harta</strong>
            akan menjadi modal awal Anda untuk berinvestasi.

        </div>

        <!-- BUTTON -->
        <button
            type="submit"
            class="btn btn-success w-100 fw-bold py-3 mt-2"
        >
            Mulai Simulasi Sekarang!
        </button>

    </form>

</div>


<!-- SCRIPT -->
<script>

    const pensionInputs = document.querySelectorAll('.pension-input');

    const displayPesangon = document.getElementById('displayPesangon');
    const displayTotalHarta = document.getElementById('displayTotalHarta');

    const networth = <?= $networth ?>;

    // FORMAT RUPIAH
    function rupiah(angka){

        return 'Rp ' + angka.toLocaleString('id-ID');

    }

    // AMBIL ANGKA ASLI
    function ambilAngka(value){

        // HAPUS TITIK
        value = value.replace(/\./g, '');

        return parseInt(value) || 0;

    }

    function hitungDana(){

        let totalPesangon = 0;

        pensionInputs.forEach(input => {

            totalPesangon += ambilAngka(input.value);

        });

        let totalHarta = totalPesangon + networth;

        displayPesangon.innerHTML = rupiah(totalPesangon);

        displayTotalHarta.innerHTML = rupiah(totalHarta);

    }

    pensionInputs.forEach(input => {

        input.addEventListener('input', hitungDana);

    });

    hitungDana();

</script>

</body>
</html>