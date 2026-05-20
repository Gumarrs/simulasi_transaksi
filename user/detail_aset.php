<?php
session_start();
require_once '../config/koneksi.php';

// 1. Proteksi Akses
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$asset_id = (int)$_GET['id'];

// 2. Ambil Status Sistem (Periode & Market)
$q_set = mysqli_query($conn, "SELECT active_period, period_status FROM system_settings LIMIT 1");
$settings = mysqli_fetch_assoc($q_set);
$active_period = $settings['active_period'] ?? 1;
$period_status = $settings['period_status'] ?? 'closed';

// UPDATE LOGIKA BARU: Mengambil kolom value_pX dan laba_pX
$kolom_val = "value_p" . $active_period;
$kolom_laba = "laba_p" . $active_period;

// 3. Ambil Detail Aset & Saldo User
$q_asset = mysqli_query($conn, "SELECT *, $kolom_val AS val_now, $kolom_laba AS laba_now FROM market_assets WHERE id = '$asset_id'");
$asset = mysqli_fetch_assoc($q_asset);

if (!$asset) {
    header("Location: dashboard.php");
    exit;
}

$q_user = mysqli_query($conn, "SELECT balance FROM users WHERE id = '$user_id'");
$user_data = mysqli_fetch_assoc($q_user);
$saldo_sekarang = floatval($user_data['balance']);

// 4. Hitung Unit yang Dimiliki (Untuk Validasi Jual)
// Hitung apakah punya modal lama dari periode sebelumnya (khusus persentase)
$sisa_pokok_lama = 0;
if ($asset['tipe_simulasi'] == 'persentase') {
    $q_old_buy = mysqli_query($conn, "SELECT SUM(qty) as sum_buy FROM transactions WHERE user_id='$user_id' AND asset_id='$asset_id' AND type='buy' AND buy_period < '$active_period'");
    $old_buy = mysqli_fetch_assoc($q_old_buy)['sum_buy'] ?? 0;
    
    $q_all_sell = mysqli_query($conn, "SELECT SUM(qty) as sum_sell FROM transactions WHERE user_id='$user_id' AND asset_id='$asset_id' AND type='sell'");
    $all_sell = mysqli_fetch_assoc($q_all_sell)['sum_sell'] ?? 0;
    
    $sisa_pokok_lama = $old_buy - $all_sell;
}

$q_own = mysqli_query($conn, "SELECT SUM(CASE WHEN type='buy' THEN qty ELSE 0 END) - SUM(CASE WHEN type='sell' THEN qty ELSE 0 END) AS sisa FROM transactions WHERE user_id='$user_id' AND asset_id='$asset_id'");
$own = mysqli_fetch_assoc($q_own);
$multiplier = (int)$asset['multiplier'];

// Jika persentase, 'sisa' adalah nominal uangnya langsung. Jika bukan, 'sisa' dibagi multiplier untuk dapat Unit.
if($asset['tipe_simulasi'] == 'persentase') {
    $total_unit_dimiliki = floatval($own['sisa']); // Berupa Rupiah
} else {
    $total_unit_dimiliki = floatval($own['sisa'] / $multiplier); // Konversi ke satuan Lot/Unit
}

// 5. Siapkan Data Grafik (Dinamis sesuai Periode Aktif)
$chart_labels = [];
$chart_data = [];
for ($i = 1; $i <= $active_period; $i++) {
    $chart_labels[] = "'P-$i'";
    $chart_data[] = floatval($asset['value_p' . $i]); // Update pakai value_p
}
$js_labels = implode(", ", $chart_labels);
$js_data = implode(", ", $chart_data);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail <?php echo $asset['nama_aset']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f4f7f6; padding-bottom: 90px; }
        .mobile-container { max-width: 480px; margin: auto; background: #fff; min-height: 100vh; position: relative;}
        .header-img { width: 100%; height: 250px; object-fit: cover; border-bottom-left-radius: 25px; border-bottom-right-radius: 25px;}
        .back-btn { position: absolute; top: 15px; left: 15px; background: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #333; z-index: 10; box-shadow: 0 2px 5px rgba(0,0,0,0.2); text-decoration: none;}
        .sticky-bottom { position: fixed; bottom: 0; width: 100%; max-width: 480px; background: white; padding: 15px; border-top: 1px solid #eee; z-index: 1000; }
        #chipContainer::-webkit-scrollbar { height: 4px; }
        #chipContainer::-webkit-scrollbar-thumb { background: #0d6efd; border-radius: 10px; }
        .chip-btn { flex: 0 0 auto; border: 1px solid #0d6efd; background: #fff; color: #0d6efd; border-radius: 20px; padding: 6px 15px; font-size: 0.8rem; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .chip-btn.active { background: #0d6efd; color: #fff; }
        
    </style>
</head>
<body>

<div class="mobile-container">
    <a href="dashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i></a>
    <img src="../assets/img/investasi/<?php echo !empty($asset['gambar']) ? $asset['gambar'] : 'placeholder.jpg'; ?>" class="header-img" onerror="this.src='https://via.placeholder.com/400x250'">

    <div class="p-4">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <span class="badge bg-primary mb-1"><?php echo $asset['kategori']; ?></span>
                <h3 class="fw-bold mb-0"><?php echo $asset['nama_aset']; ?></h3>
            </div>
            <div class="text-end">
                <small class="text-muted d-block">
                    <?php echo $asset['tipe_simulasi'] == 'persentase' ? 'Imbal Hasil' : 'Harga / '.$asset['satuan']; ?>
                </small>
                
            <?php if($asset['tipe_simulasi'] == 'persentase'): ?>
                <h4 class="fw-bold text-success mb-0">
                    <?php 
echo rtrim(
        rtrim(
            number_format(floatval($asset['laba_now']) / 2, 3, '.', ''),
            '0'
        ),
        '.'
    ); 
?>% / Tahun
                </h4>
            <?php else: ?>
                <h4 class="fw-bold text-primary mb-0">
                    Rp <?php echo number_format($asset['val_now'],0,',','.'); ?>
                </h4>
            <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 bg-light mb-4" style="border-radius: 15px;">
            <div class="card-body p-2">
                <canvas id="priceChart" height="180"></canvas>
            </div>
        </div>

     <h6 class="fw-bold">
            <i class="fa-solid fa-align-left text-primary me-2"></i>
            Deskripsi & Prospek
        </h6>

<?php
$field = 'deskripsi_p' . $active_period;

$deskripsi =
    !empty($asset[$field])
    ? $asset[$field]
    : 'Belum ada deskripsi untuk aset ini.';
?>

<div
    class="wysiwyg-content text-muted small lh-lg"
    role="button"
    data-bs-toggle="modal"
    data-bs-target="#modalDeskripsi"
    style="cursor:pointer;"
>
    <?php echo $deskripsi; ?>
</div>
<?php 
$is_disabled = ($period_status == 'closed') ? 'disabled' : '';

// ==========================================
// KHUSUS MARKET YANG HARGANYA 0
// ==========================================

if ($asset['tipe_simulasi'] != 'persentase' && $asset['val_now'] <= 0) {

    $is_disabled = 'disabled';
}

// ==========================================
// KHUSUS DEPOSITO
// TIDAK BISA DICAIRKAN DI PERIODE SAMA
// ==========================================

$q_last_buy = mysqli_query($conn, "
    SELECT MAX(buy_period) AS last_buy_period
    FROM transactions
    WHERE user_id = '$user_id'
    AND asset_id = '$asset_id'
    AND type = 'buy'
");

$last_buy = mysqli_fetch_assoc($q_last_buy);

$last_buy_period = intval($last_buy['last_buy_period']);

$disable_jual_deposito = false;

if (
    $asset['tipe_simulasi'] == 'persentase'
    &&
    $last_buy_period >= $active_period
) {
    $disable_jual_deposito = true;
}
?>
    <div class="sticky-bottom d-flex gap-2">
<?php if($asset['tipe_simulasi'] != 'persentase'): ?>

<button 
    class="btn btn-outline-danger w-50 fw-bold py-2"
    onclick="openModal('sell')"
    <?php echo $is_disabled; ?>
>
    JUAL
</button>

<?php endif; ?>
<button 
    class="btn btn-success <?php echo ($asset['tipe_simulasi'] != 'persentase') ? 'w-50' : 'w-100'; ?> fw-bold py-2" 
    onclick="openModal('buy')" 
    <?php echo $is_disabled; ?>
>
    BELI
</button>
</div>
</div>

<div class="modal fade" id="tradeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius: 20px;">
            <div class="modal-header border-0">
                <h5 class="fw-bold mb-0" id="modalTitle">Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses_transaksi.php" method="POST" id="tradeForm">
                <div class="modal-body pt-0">
                    <input type="hidden" name="asset_id" value="<?php echo $asset_id; ?>">
                    <input type="hidden" name="tipe" id="tipeInput">
                    <input type="hidden" name="nominal" id="finalNominal">

                    <div class="d-flex justify-content-between mb-3 p-2 bg-light rounded">
                        <small class="text-muted">Aset Anda:</small>
                        <small class="fw-bold">
                            <?php if($asset['tipe_simulasi'] == 'persentase'): ?>
                                Rp <?php echo number_format($total_unit_dimiliki, 0, ',', '.'); ?>
                            <?php else: ?>
                                <?php echo number_format($total_unit_dimiliki, 0); ?> <?php echo $asset['satuan']; ?>
                            <?php endif; ?>
                        </small>
                    </div>

                    <label class="small fw-bold mb-2">
                        <?php echo $asset['tipe_simulasi'] == 'persentase' ? 'Pilih / Input Nominal (Rp)' : 'Pilih Jumlah ('.$asset['satuan'].')'; ?>
                    </label>
                   <div class="d-flex gap-2 overflow-auto pb-2 mb-3" id="chipContainer">

                    <?php if($asset['tipe_simulasi'] == 'persentase'): ?>

                        <?php for($i = 100000000; $i <= 1000000000; $i += 100000000): ?>
                            <button 
                                type="button" 
                                class="chip-btn" 
                                onclick="selectQty(<?php echo $i; ?>, this)"
                            >
                                <?php echo ($i >= 1000000000) 
                                    ? ($i / 1000000000) . ' M' 
                                    : ($i / 1000000) . ' Jt'; ?>
                            </button>
                        <?php endfor; ?>

 <?php else: ?>

    <?php if($asset['tipe_simulasi'] == 'bisnis'): ?>

        <?php for($i=1; $i<=10; $i++): ?>
            <button 
                type="button" 
                class="chip-btn" 
                onclick="selectQty(<?php echo $i; ?>, this)"
            >
                <?php echo $i; ?>
            </button>
        <?php endfor; ?>

    <?php else: ?>

        <?php for($i=100; $i<=1000; $i+=100): ?>
            <button 
                type="button" 
                class="chip-btn" 
                onclick="selectQty(<?php echo $i; ?>, this)"
            >
                <?php echo $i; ?>
            </button>
        <?php endfor; ?>

    <?php endif; ?>

<?php endif; ?>

                </div>

                    <div class="mb-3">
                        <input type="number" id="manualQty" class="form-control form-control-lg text-center fw-bold" placeholder="Input Manual" onkeyup="calc()">
                    </div>

                    <div class="text-center py-2">
                        <small class="text-muted">Total Bayar/Terima:</small>
                        <h3 class="fw-bold text-primary" id="viewNominal">Rp 0</h3>
                    </div>
                </div>
                <div class="modal-footer border-0">
<button 
    type="button"
    class="btn btn-primary w-100 fw-bold"
    id="confirmBtn"
    disabled
    onclick="confirmTrade()"
>
    Konfirmasi
</button>                </div>
            </form>
        </div>
    </div>
</div>

<script>
// 1. Chart Logic
const ctx = document.getElementById('priceChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: [<?php echo $js_labels; ?>],
        datasets: [{
            data: [<?php echo $js_data; ?>],
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13, 110, 253, 0.1)',
            fill: true, tension: 0.3, borderWidth: 3, pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: false, ticks: { callback: v => 'Rp ' + v.toLocaleString('id-ID') } } }
    }
});

// 2. Trade Logic UPDATE
const tipeSim = "<?php echo $asset['tipe_simulasi']; ?>";
const price = <?php echo floatval($asset['val_now']); ?>;
const mult = <?php echo $multiplier; ?>;
const cash = <?php echo $saldo_sekarang; ?>;
const stock = <?php echo $total_unit_dimiliki; ?>; // stock is Nominal (Rp) if persentase, Unit if others

function openModal(tipe) {
    document.getElementById('tipeInput').value = tipe;
    document.getElementById('modalTitle').innerText = (tipe == 'buy' ? 'Beli ' : 'Jual ') + '<?php echo $asset['nama_aset']; ?>';
    const btn = document.getElementById('confirmBtn');
    btn.className = (tipe == 'buy') ? 'btn btn-success w-100 fw-bold' : 'btn btn-danger w-100 fw-bold';
    btn.innerText = (tipe == 'buy') ? 'KONFIRMASI BELI' : 'KONFIRMASI JUAL';
    new bootstrap.Modal(document.getElementById('tradeModal')).show();
}

function selectQty(q, el) {
    document.querySelectorAll('.chip-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('manualQty').value = q;
    calc();
}

function calc() {
    const q = parseFloat(document.getElementById('manualQty').value) || 0;
    let total = 0;
    
    if(tipeSim === 'persentase') {
        total = q; // Untuk deposito, q yang diinput langsung menjadi total Rupiah
    } else {
        total = q * mult * price; // Logika orisinal untuk saham/emas/properti
    }
    
    const tipe = document.getElementById('tipeInput').value;
    
    document.getElementById('finalNominal').value = total;
    document.getElementById('viewNominal').innerText = 'Rp ' + total.toLocaleString('id-ID');
    
    const btn = document.getElementById('confirmBtn');
    if (q <= 0) {
        btn.disabled = true;
    } else if (tipe == 'buy' && total > cash) {
        btn.disabled = true;
        document.getElementById('viewNominal').innerText = "Saldo Tunai Kurang!";
    } else if (tipe == 'sell' && q > stock && tipeSim !== 'persentase') {
        btn.disabled = true;
        document.getElementById('viewNominal').innerText = "Unit Tidak Cukup!";
    } else if (tipe == 'sell' && total > stock && tipeSim === 'persentase') {
        btn.disabled = true;
        document.getElementById('viewNominal').innerText = "Saldo Deposito/Aset Kurang!";
    } else {
        btn.disabled = false;
    }
}
</script>
<script>

function confirmTrade() {

    const tipe =
        document.getElementById('tipeInput').value;

    const nominal =
        parseFloat(document.getElementById('finalNominal').value) || 0;

    const qty =
        parseFloat(document.getElementById('manualQty').value) || 0;

    let judul =
        tipe === 'buy'
        ? 'Konfirmasi Pembelian'
        : 'Konfirmasi Penjualan';

    let tombol =
        tipe === 'buy'
        ? 'Ya, Beli Sekarang'
        : 'Ya, Jual Sekarang';

    let warna =
        tipe === 'buy'
        ? '#198754'
        : '#dc3545';

    let htmlText = `
        <div style="text-align:left">

            <p>
                Apakah anda yakin ingin
                <b>${tipe === 'buy' ? 'membeli' : 'menjual'}</b>
                aset ini?
            </p>

            <hr>

            <table style="width:100%">

                <tr>
                    <td>Jumlah</td>
                    <td align="right">
                        ${qty.toLocaleString('id-ID')}
                    </td>
                </tr>

                <tr>
                    <td>Total</td>
                    <td align="right">
                        <b>
                            Rp ${nominal.toLocaleString('id-ID')}
                        </b>
                    </td>
                </tr>

            </table>

        </div>
    `;

    Swal.fire({

        icon: 'question',

        title: judul,

        html: htmlText,

        showCancelButton: true,

        confirmButtonText: tombol,

        cancelButtonText: 'Batal',

        confirmButtonColor: warna,

        reverseButtons: true

    }).then((result) => {

if(result.isConfirmed) {

    Swal.fire({

        title: 'Memproses Transaksi...',
        html: 'Mohon tunggu sebentar',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,

        didOpen: () => {
            Swal.showLoading();
        }

    });

    setTimeout(() => {

        document.getElementById('tradeForm').submit();

    }, 300);

}

    });

}

</script>

<!-- MODAL DESKRIPSI MOBILE -->

<div
    class="modal fade"
    id="modalDeskripsi"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered custom-mobile-modal">

        <div class="modal-content border-0">

            <!-- HEADER -->

            <div class="modal-header">

                <h6 class="fw-bold mb-0">
                    <?php echo $asset['nama_aset']; ?>
                </h6>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>

            <!-- BODY -->

            <div class="modal-body">

                <div class="wysiwyg-content lh-lg">

                    <?php echo $deskripsi; ?>

                </div>

            </div>

        </div>

    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($sisa_pokok_lama > 0): ?>

<form id="formAutoJual" action="proses_transaksi.php" method="POST" style="display: none;">
    <input type="hidden" name="asset_id" value="<?php echo $asset_id; ?>">
    <input type="hidden" name="tipe" value="sell">
    <input type="hidden" name="nominal" value="<?php echo $sisa_pokok_lama; ?>">
    <input type="hidden" name="qty" value="<?php echo $sisa_pokok_lama; ?>"> 
</form>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // POPUP PERTAMA (Pemberitahuan Aset Hold/Jual)
        Swal.fire({
            title: 'Pemberitahuan Aset',
            html: 'Anda masih mempunyai modal <b><?php echo $asset["nama_aset"]; ?></b> dari periode sebelumnya senilai <b>Rp <?php echo number_format($sisa_pokok_lama,0,",","."); ?></b>.<br><br>Profitnya sudah otomatis cair. Apakah Anda ingin tetap memegang (Hold) pokok ini atau menjualnya sekarang?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Jual Pokok',
            cancelButtonText: 'Tetap Hold',
            reverseButtons: true,
            allowOutsideClick: false // User tidak bisa klik sembarang di luar box
        }).then((result) => {
            if (result.isConfirmed) {
                
                // POPUP KEDUA (Konfirmasi Kepastian Jual)
                Swal.fire({
                    title: 'Konfirmasi Jual Pokok',
                    html: 'Anda akan menjual aset <b><?php echo $asset["nama_aset"]; ?></b> dengan nilai <b>Rp <?php echo number_format($sisa_pokok_lama,0,",","."); ?></b>.<br><br>Apakah Anda yakin?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Jual Sekarang',
                    cancelButtonText: 'Batal (Tetap Hold)',
                    confirmButtonColor: '#dc3545', // Warna merah untuk tombol jual
                    reverseButtons: true,
                    allowOutsideClick: false
                }).then((resultConfirm) => {
                    if (resultConfirm.isConfirmed) {
                        
                        // POPUP KETIGA (Loading Screen)
                        Swal.fire({
                            title: 'Memproses Penjualan...',
                            html: 'Mohon tunggu sebentar',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Eksekusi submit form tersembunyi
                        document.getElementById('formAutoJual').submit();
                    }
                });
                
            }
        });
    });
</script>
<?php endif; ?>
</body>
</html>