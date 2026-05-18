<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 2: Arus Kas Bulanan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; }
        .mobile-container { max-width: 480px; margin: auto; background: #fff; min-height: 100vh; box-shadow: 0 0 15px rgba(0,0,0,0.05); }
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
<script>

document.addEventListener("DOMContentLoaded", function () {

    const incomeInputs = document.querySelectorAll(".income");
    const expenseInputs = document.querySelectorAll(".expense");

    const totalIncomeEl = document.getElementById("totalIncome");
    const totalExpenseEl = document.getElementById("totalExpense");
    const displayCashflowEl = document.getElementById("displayCashflow");

    // FORMAT RUPIAH
    function formatRupiah(angka) {

        return "Rp " + angka.toLocaleString("id-ID");

    }

    // AMBIL ANGKA ASLI
    function ambilAngka(value){

        // HAPUS TITIK
        value = value.replace(/\./g, '');

        return parseInt(value) || 0;
    }

    function hitungCashflow() {

        let totalIncome = 0;
        let totalExpense = 0;

        // TOTAL PENGHASILAN
        incomeInputs.forEach(input => {

            totalIncome += ambilAngka(input.value);

        });

        // TOTAL PENGELUARAN
        expenseInputs.forEach(input => {

            totalExpense += ambilAngka(input.value);

        });

        // CASHFLOW
        let cashflow = totalIncome - totalExpense;

        // TAMPILKAN
        totalIncomeEl.innerText = formatRupiah(totalIncome);

        totalExpenseEl.innerText = formatRupiah(totalExpense);

        displayCashflowEl.innerText = formatRupiah(cashflow);

        // WARNA DINAMIS
        if (cashflow < 0) {

            displayCashflowEl.classList.remove("text-primary");
            displayCashflowEl.classList.add("text-danger");

        } else {

            displayCashflowEl.classList.remove("text-danger");
            displayCashflowEl.classList.add("text-primary");

        }

    }

    // EVENT INPUT
    [...incomeInputs, ...expenseInputs].forEach(input => {

        input.addEventListener("input", hitungCashflow);

    });

    // LOAD AWAL
    hitungCashflow();

});

</script>
<body>
<div class="mobile-container p-4">
    <div class="mb-4">
        <span class="text-muted small fw-bold uppercase">Step 2 dari 3</span>
<h4 class="fw-bold text-success mb-1">Arus Kas Bulanan</h4>
<p class="text-muted small mb-4">Rincian pemasukan dan pengeluaran rutin Anda setiap bulan.</p>
    </div>

<form action="proses_step2.php" method="POST">
    
    <div class="section-title text-primary">
        <i class="fa-solid fa-wallet"></i> PENGHASILAN (AKTIF)
    </div>
    <div class="mb-2">
        <label class="form-label">Gaji Bersih</label>
        <input type="number" name="gaji_bersih" class="form-control income" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Bonus</label>
        <input type="number" name="bonus" class="form-control income" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Komisi</label>
        <input type="number" name="komisi" class="form-control income" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Hadiah</label>
        <input type="number" name="hadiah" class="form-control income" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Keuntungan Saham dll</label>
        <input type="number" name="untung_saham" class="form-control income" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Lain-lain (Aktif)</label>
        <input type="number" name="aktif_lain" class="form-control income" value="0">
    </div>

    <div class="section-title text-primary">
        PENGHASILAN (PASIF)
    </div>
    <div class="mb-2">
        <label class="form-label">Bunga</label>
        <input type="number" name="bunga" class="form-control income" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Sewa (Rumah, Ruko)</label>
        <input type="number" name="sewa_properti" class="form-control income" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Laba Bisnis Pribadi</label>
        <input type="number" name="laba_bisnis" class="form-control income" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Dividen</label>
        <input type="number" name="dividen" class="form-control income" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Royalti</label>
        <input type="number" name="royalti" class="form-control income" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Lain-lain (Pasif)</label>
        <input type="number" name="pasif_lain" class="form-control income" value="0">
    </div>

    <div class="section-title text-danger mt-4">
        <i class="fa-solid fa-house-user"></i> RUMAH TANGGA
    </div>
    <div class="mb-2">
        <label class="form-label">Sewa Rumah</label>
        <input type="number" name="sewa_rumah" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Cicilan Rumah</label>
        <input type="number" name="cicilan_rumah" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Perawatan Rumah</label>
        <input type="number" name="perawatan_rumah" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Asuransi Alat Rumah Tangga</label>
        <input type="number" name="asuransi_alat_rt" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Belanja Bulanan</label>
        <input type="number" name="belanja_rt" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Pajak Bangunan (PBB)</label>
        <input type="number" name="pbb" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Keamanan & Kebersihan</label>
        <input type="number" name="keamanan_rt" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Servis Alat-alat</label>
        <input type="number" name="servis_alat" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Lain-lain (Rumah Tangga)</label>
        <input type="number" name="rt_lain" class="form-control expense" value="0">
    </div>

    <div class="section-title text-danger">
        KESEHATAN
    </div>
    <div class="mb-2">
        <label class="form-label">Dokter</label>
        <input type="number" name="dokter" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Obat-obatan</label>
        <input type="number" name="obat_obatan" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Medical Check Up</label>
        <input type="number" name="checkup" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Asuransi Kesehatan</label>
        <input type="number" name="asuransi_kes" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Fitness / Gym</label>
        <input type="number" name="fitness" class="form-control expense" value="0">
    </div>

    <div class="section-title text-danger">
        TRANSPORTASI
    </div>
    <div class="mb-2">
        <label class="form-label">Asuransi Kendaraan</label>
        <input type="number" name="asuransi_kendaraan" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">BBM</label>
        <input type="number" name="bbm" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Cicilan Mobil/Motor</label>
        <input type="number" name="cicilan_kendaraan" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Servis Kendaraan</label>
        <input type="number" name="servis_kendaraan" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Pajak Kendaraan, STNK</label>
        <input type="number" name="pajak_stnk" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Taksi / Transportasi Umum</label>
        <input type="number" name="transport_umum" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Biaya Tol & Parkir</label>
        <input type="number" name="tol_parkir" class="form-control expense" value="0">
    </div>

    <div class="section-title text-danger">
        MAKANAN
    </div>
    <div class="mb-2">
        <label class="form-label">Makan Pagi, Siang, Malam</label>
        <input type="number" name="makan_pokok" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Jajanan & Kopi</label>
        <input type="number" name="jajanan" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Makan/Minum di Luar</label>
        <input type="number" name="makan_luar" class="form-control expense" value="0">
    </div>

    <div class="section-title text-danger">
        TELEPON, LISTRIK & UTILITAS
    </div>
    <div class="mb-2">
        <label class="form-label">HP & Telepon Rumah</label>
        <input type="number" name="telepon_hp" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Listrik, Air & Gas</label>
        <input type="number" name="listrik_air_gas" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">TV Kabel & Internet</label>
        <input type="number" name="internet_tv" class="form-control expense" value="0">
    </div>

    <div class="section-title text-danger">
        REKREASI & HOBI
    </div>
    <div class="mb-2">
        <label class="form-label">Hobi & Keanggotaan</label>
        <input type="number" name="hobi" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Film, Musik & Hiburan</label>
        <input type="number" name="hiburan" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Liburan</label>
        <input type="number" name="liburan" class="form-control expense" value="0">
    </div>

    <div class="section-title text-danger">
        KEBUTUHAN LAIN & SOSIAL
    </div>
    <div class="mb-2">
        <label class="form-label">Tagihan Kartu Kredit</label>
        <input type="number" name="kartu_kredit_bayar" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Pendidikan Anak & Mainan</label>
        <input type="number" name="pendidikan_anak" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Laundry & Pakaian</label>
        <input type="number" name="laundry_pakaian" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Donasi / Zakat / Amal</label>
        <input type="number" name="donasi" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Gaji Pembantu / Supir</label>
        <input type="number" name="gaji_staf_rt" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Pajak Penghasilan</label>
        <input type="number" name="pajak_pendapatan" class="form-control expense" value="0">
    </div>
    <div class="mb-2">
        <label class="form-label">Pengembangan Diri (Kursus dll)</label>
        <input type="number" name="pengembangan_diri" class="form-control expense" value="0">
    </div>

    <div class="total-box mt-4" style="background: #f8f9fa; border: 1px solid #ddd; padding: 20px; border-radius: 10px;">
        <div class="row">
            <div class="col-6">
                <small class="d-block opacity-75">Total Penghasilan (A)</small>
                <h5 class="fw-bold text-success" id="totalIncome">Rp 0</h5>
            </div>
            <div class="col-6">
                <small class="d-block opacity-75">Total Pengeluaran (B)</small>
                <h5 class="fw-bold text-danger" id="totalExpense">Rp 0</h5>
            </div>
        </div>
        <hr>
        <small class="d-block opacity-75">Penghasilan Bersih (C = A - B)</small>
        <h4 class="fw-bold mb-0 text-primary" id="displayCashflow">Rp 0</h4>
    </div>

    <button type="submit" class="btn btn-primary w-100 fw-bold mt-4 py-3">
        LANJUT KE UANG PESANGON
        <i class="fa-solid fa-check-double"></i>
    </button>

</form>

    
</div>
</body>
</html>