<?php
session_start();
require_once '../config/koneksi.php';
?>



<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment - Tahap 1</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; }
        .mobile-container { max-width: 500px; margin: auto; background: #fff; min-height: 100vh; padding: 20px; }
        .section-title { background: #e9ecef; padding: 10px; font-weight: bold; font-size: 0.85rem; border-radius: 5px; margin-top: 15px; }
        .form-label { font-size: 0.8rem; font-weight: 600; color: #555; margin-bottom: 2px; }
        .total-box { background: #0d6efd; color: white; padding: 15px; border-radius: 10px; margin-top: 20px; }
    </style>
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
</head>

<body>

<div class="mobile-container shadow-sm">
    <h4 class="fw-bold text-primary mb-1">Catatan Kekayaan</h4>
    <p class="text-muted small mb-4">Lengkapi rincian harta dan utang Anda sesuai kondisi saat ini.</p>

<form action="proses_step1.php" method="POST">

    <!-- ASET LIKUID -->
    <div class="section-title text-primary">
        <i class="fa-solid fa-box"></i> ASET LIKUID
    </div>

    <div class="mb-2">
        <label class="form-label">Dana Tunai</label>
        <input type="number" name="dana_tunai" class="form-control harta" value="0">
    </div>

    <div class="mb-2">
        <label class="form-label">Tabungan dan Giro</label>
        <input type="number" name="tabungan_giro" class="form-control harta" value="0">
    </div>

    <div class="mb-2">
        <label class="form-label">Piutang</label>
        <input type="number" name="piutang" class="form-control harta" value="0">
    </div>

    <div class="mb-2">
        <label class="form-label">Lain-lain</label>
        <input type="number" name="likuid_lain" class="form-control harta" value="0">
    </div>


    <!-- ASET PRIBADI -->
    <div class="section-title text-primary">
        ASET PRIBADI
    </div>

    <div class="mb-2">
        <label class="form-label">Nilai Rumah</label>
        <input type="number" name="nilai_rumah" class="form-control harta" value="0">
    </div>

    <div class="mb-2">
        <label class="form-label">Nilai Mobil/Motor</label>
        <input type="number" name="nilai_kendaraan" class="form-control harta" value="0">
    </div>

    <div class="mb-2">
        <label class="form-label">Perhiasan</label>
        <input type="number" name="perhiasan" class="form-control harta" value="0">
    </div>

    <div class="mb-2">
        <label class="form-label">Lain-lain</label>
        <input type="number" name="pribadi_lain" class="form-control harta" value="0">
    </div>


    <!-- ASET INVESTASI -->
    <div class="section-title text-primary">
        ASET INVESTASI
    </div>

    <div class="mb-2">
        <label class="form-label">Deposito</label>
        <input type="number" name="deposito" class="form-control harta" value="0">
    </div>

    <div class="mb-2">
        <label class="form-label">Obligasi</label>
        <input type="number" name="obligasi" class="form-control harta" value="0">
    </div>

    <div class="mb-2">
        <label class="form-label">Reksadana / Saham</label>
        <input type="number" name="reksadana_saham" class="form-control harta" value="0">
    </div>

    <div class="mb-2">
        <label class="form-label">Emas</label>
        <input type="number" name="emas" class="form-control harta" value="0">
    </div>

    <div class="mb-2">
        <label class="form-label">Tanah</label>
        <input type="number" name="tanah" class="form-control harta" value="0">
    </div>

    <div class="mb-2">
        <label class="form-label">Bisnis</label>
        <input type="number" name="bisnis" class="form-control harta" value="0">
    </div>

    <div class="mb-2">
        <label class="form-label">Rumah Kedua, Ketiga, dst</label>
        <input type="number" name="rumah_kedua_dst" class="form-control harta" value="0">
    </div>

    <div class="mb-2">
        <label class="form-label">Lain-lain</label>
        <input type="number" name="investasi_lain" class="form-control harta" value="0">
    </div>


    <!-- UTANG JANGKA PENDEK -->
    <div class="section-title text-danger">
        UTANG JANGKA PENDEK
    </div>

    <div class="mb-2">
        <label class="form-label">Utang Kartu Kredit</label>
        <input type="number" name="utang_kartu_kredit" class="form-control utang" value="0">
    </div>

    <div class="mb-2">
        <label class="form-label">Tagihan Belum Lunas</label>
        <input type="number" name="tagihan_belum_lunas" class="form-control utang" value="0">
    </div>

    <div class="mb-2">
        <label class="form-label">Pinjaman Lainnya</label>
        <input type="number" name="pinjaman_lainnya" class="form-control utang" value="0">
    </div>


    <!-- UTANG JANGKA PANJANG -->
    <div class="section-title text-danger">
        UTANG JANGKA PANJANG
    </div>

    <div class="mb-2">
        <label class="form-label">Utang Rumah</label>
        <input type="number" name="utang_rumah" class="form-control utang" value="0">
    </div>

    <div class="mb-2">
        <label class="form-label">Utang Rumah Kedua</label>
        <input type="number" name="utang_rumah_kedua" class="form-control utang" value="0">
    </div>

    <div class="mb-2">
        <label class="form-label">Utang Mobil/Motor</label>
        <input type="number" name="utang_kendaraan" class="form-control utang" value="0">
    </div>


    <!-- NETWORTH -->
    <div class="total-box">
        <small class="d-block opacity-75">
            Estimasi Kekayaan Bersih (Net Worth)
        </small>

        <h4 class="fw-bold mb-0" id="displayNetworth">
            Rp 0
        </h4>
    </div>

    <button type="submit" class="btn btn-primary w-100 fw-bold mt-4 py-3">
        LANJUT KE ARUS KAS
        <i class="fa-solid fa-arrow-right"></i>
    </button>

</form>
</div>

<script>

    function ambilAngka(value){

        // HAPUS TITIK
        value = value.replace(/\./g, '');

        return parseInt(value) || 0;
    }

    function calc() {

        let harta = 0;

        document.querySelectorAll('.harta').forEach(i => {

            harta += ambilAngka(i.value);

        });

        let utang = 0;

        document.querySelectorAll('.utang').forEach(i => {

            utang += ambilAngka(i.value);

        });

        let net = harta - utang;

        document.getElementById('displayNetworth').innerText =
            "Rp " + net.toLocaleString('id-ID');
    }

    document.querySelectorAll('input').forEach(i => {

        i.addEventListener('input', calc);

    });

    calc();

</script>
</body>
</html>