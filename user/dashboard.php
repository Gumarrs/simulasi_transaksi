<?php
session_start();
require_once '../config/koneksi.php';
require_once 'auto_cair_deposito.php';

// Proteksi akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'peserta') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$query_user = mysqli_query($conn, "
    SELECT balance, is_assessment_done 
    FROM users 
    WHERE id = '$user_id'
");

$user = mysqli_fetch_assoc($query_user);

// Jika belum assessment
if ($user['is_assessment_done'] == 0) {
    header("Location: assessment_step1.php");
    exit;
}

// Setting sistem
$query_settings = mysqli_query($conn, "
    SELECT active_period, period_status, end_time 
    FROM system_settings 
    LIMIT 1
");

$settings = mysqli_fetch_assoc($query_settings);

$active_period = $settings['active_period'] ?? 1;
$period_status = $settings['period_status']; // Pastikan variabel status ini ada

// HANYA CEK COVER PAGE JIKA STATUS PERIODE ADALAH 'OPEN'
if ($period_status == 'open') {
    $q_cover = mysqli_query($conn,"
        SELECT id
        FROM period_cover_views
        WHERE user_id='$user_id'
        AND period='$active_period'
    ");

    if(mysqli_num_rows($q_cover) == 0) {
        header("Location: cover.php");
        exit; // <--- WAJIB TAMBAHKAN INI
    }
}

if(mysqli_num_rows($q_cover) == 0){

    header("Location: cover.php");
    exit;
}
$period_status = $settings['period_status'] ?? 'closed';
$end_time = $settings['end_time'];

// Hitung sisa detik
date_default_timezone_set('Asia/Jakarta');
$sisa_waktu_detik = 0;
if (!empty($end_time) && $period_status == 'open') {
    $sisa_waktu_detik = strtotime($end_time) - time();
}

// ==========================================
// KUNCI PERBAIKAN: Panggil kolom harga_pX 
// agar sama persis dengan detail_aset.php
// ==========================================
// ==========================================
// KUNCI PERBAIKAN: Gunakan value_p dan 
// tambahkan Error Handling (mysqli_error)
// ==========================================
$kolom_val = "value_p" . $active_period; 
$kolom_laba = "laba_p" . $active_period;

// Ambil semua aset
$query_assets = mysqli_query($conn, "
    SELECT 
        id,
        nama_aset,
        kategori,
        tipe_simulasi,
        gambar,
        $kolom_val AS val_now,
        $kolom_laba AS laba_now
    FROM market_assets
");

// JIKA QUERY GAGAL, TAMPILKAN PESAN ERROR DATABASE-NYA
if (!$query_assets) {
    die("<div class='alert alert-danger m-4'><b>Error Database:</b> " . mysqli_error($conn) . "<br>Cek apakah kolom <b>$kolom_val</b> atau <b>$kolom_laba</b> benar-benar ada di tabel market_assets.</div>");
}

// Count total instrumen
$total_instrumen = mysqli_num_rows($query_assets);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Simulasi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body{ background:#f4f7f6; padding-bottom:80px; }
.mobile-container{ max-width:480px; margin:auto; background:white; min-height:100vh; box-shadow:0 0 15px rgba(0,0,0,0.05); }
.saldo-card{ background:linear-gradient(135deg,#0d6efd,#0b5ed7); border-radius:16px; color:white; }
.bottom-nav{ position:fixed; bottom:0; width:100%; max-width:480px; background:white; border-top:1px solid #ddd; display:flex; justify-content:space-around; padding:10px 0; z-index:999; }
.nav-item{ text-decoration:none; color:#6c757d; text-align:center; font-size:0.8rem; }
.nav-item.active{ color:#0d6efd; font-weight:bold; }
.nav-item i{ display:block; font-size:1.1rem; margin-bottom:2px; }
.asset-card{ border-radius:14px; overflow:hidden; border:none; box-shadow:0 2px 10px rgba(0,0,0,0.05); }
.asset-img{ height:120px; object-fit:cover; }
.asset-list{ border-radius:14px; padding:12px; background:white; border:1px solid #eee; }
.view-toggle .btn{ border-radius:10px; }
</style>
</head>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<body>

<div class="mobile-container">
    <div class="d-flex justify-content-between align-items-center p-3 border-bottom sticky-top bg-white">
        <div>
            <span class="fw-bold text-primary">Periode <?php echo $active_period; ?></span>
            <span class="badge bg-<?php echo ($period_status == 'open') ? 'success' : 'danger'; ?>">
                <?php echo strtoupper($period_status); ?>
            </span>
        </div>
        <div class="text-end">
            <small class="text-muted d-block" style="font-size:0.7rem;">Sisa Waktu</small>
            <span class="fw-bold" id="countdown">--:--</span>
        </div>
    </div>

    <div class="p-3">
        <div class="saldo-card p-4 mb-4 text-center shadow-sm">
            <p class="mb-1 opacity-75">Saldo Tunai Saat Ini</p>
            <h3 class="fw-bold mb-0">Rp <?php echo number_format($user['balance'],0,',','.'); ?></h3>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h6 class="fw-bold mb-0">Market Investasi</h6>
                <small class="text-muted"><?php echo $total_instrumen; ?> Instrumen</small>
            </div>
            <div class="view-toggle">
                <button class="btn btn-sm btn-primary" id="btnGrid"><i class="fa-solid fa-grip"></i></button>
                <button class="btn btn-sm btn-outline-primary" id="btnList"><i class="fa-solid fa-list"></i></button>
            </div>
        </div>

        <div class="row g-3" id="gridView">
            <?php mysqli_data_seek($query_assets, 0); ?>
            <?php while($row = mysqli_fetch_assoc($query_assets)) : 
                
                // PENGAMANAN DATA: Konversi langsung ke format float agar angka terbaca jelas
                $val_now = floatval($row['val_now']);
                $laba_now = floatval($row['laba_now']);
                
                if ($row['tipe_simulasi'] == 'persentase') {
                    $display_val =
    rtrim(
        rtrim(
            number_format(floatval($row['laba_now']) / 2, 3, '.', ''),
            '0'
        ),
        '.'
    ) . "% / Tahun";
                } else {
                    $display_val = "Rp " . number_format($val_now, 0, ',', '.');
                }
            ?>
            <div class="col-6">
                <a href="detail_aset.php?id=<?php echo $row['id']; ?>" class="text-decoration-none text-dark">
                    <div class="card asset-card h-100">
                        <img src="../assets/img/investasi/<?php echo !empty($row['gambar']) ? $row['gambar'] : 'placeholder.jpg'; ?>" class="asset-img w-100" onerror="this.src='https://via.placeholder.com/150'">
                        <div class="card-body p-2">
                            <span class="badge bg-light text-dark border mb-2"><?php echo $row['kategori']; ?></span>
                            <h6 class="fw-bold mb-1" style="font-size:0.85rem;"><?php echo $row['nama_aset']; ?></h6>
                            <div class="text-primary fw-bold small"><?php echo $display_val; ?></div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endwhile; ?>
        </div>

        <div id="listView" style="display:none;">
            <?php mysqli_data_seek($query_assets, 0); ?>
            <?php while($row = mysqli_fetch_assoc($query_assets)) : 
                
                $val_now = floatval($row['val_now']);
                $laba_now = floatval($row['laba_now']);
                
            if ($row['tipe_simulasi'] == 'persentase') {
                $display_val =
    rtrim(
        rtrim(
            number_format(floatval($row['laba_now']) / 2, 3, '.', ''),
            '0'
        ),
        '.'
    ) . "% / Tahun";
            } else {
                $display_val = "Rp " . number_format($val_now, 0, ',', '.');
            }
            ?>
            <a href="detail_aset.php?id=<?php echo $row['id']; ?>" class="text-decoration-none text-dark">
                <div class="asset-list mb-2 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <img src="../assets/img/investasi/<?php echo !empty($row['gambar']) ? $row['gambar'] : 'placeholder.jpg'; ?>" style="width: 45px; height: 45px; border-radius: 8px; object-fit: cover;" class="me-3" onerror="this.src='https://via.placeholder.com/45'">
                            <div>
                                <div class="fw-bold"><?php echo $row['nama_aset']; ?></div>
                                <small class="text-muted"><?php echo $row['kategori']; ?></small>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-primary"><?php echo $display_val; ?></div>
                            <small class="text-muted" style="font-size: 0.7rem;">Detail <i class="fa-solid fa-chevron-right"></i></small>
                        </div>
                    </div>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="bottom-nav shadow-lg">
        <a href="dashboard.php" class="nav-item active"><i class="fa-solid fa-house"></i>Home</a>
        <a href="portfolio.php" class="nav-item"><i class="fa-solid fa-briefcase"></i>Portofolio</a>
        <a href="leaderboard.php" class="nav-item"><i class="fa-solid fa-trophy"></i>Peringkat</a>
        <a href="profile.php" class="nav-item"><i class="fa-solid fa-user"></i>Profil</a>
    </div>
</div>

<script>
// TOGGLE VIEW
const btnGrid = document.getElementById('btnGrid');
const btnList = document.getElementById('btnList');
const gridView = document.getElementById('gridView');
const listView = document.getElementById('listView');

btnGrid.addEventListener('click', function(){
    gridView.style.display = 'flex';
    listView.style.display = 'none';
    btnGrid.classList.replace('btn-outline-primary', 'btn-primary');
    btnList.classList.replace('btn-primary', 'btn-outline-primary');
});

btnList.addEventListener('click', function(){
    gridView.style.display = 'none';
    listView.style.display = 'block';
    btnList.classList.replace('btn-outline-primary', 'btn-primary');
    btnGrid.classList.replace('btn-primary', 'btn-outline-primary');
});

// COUNTDOWN
let sisaDetik = <?php echo $sisa_waktu_detik; ?>;
const statusPeriode = "<?php echo $period_status; ?>";
const countdownEl = document.getElementById("countdown");

if (sisaDetik > 0 && statusPeriode === 'open') {
    const x = setInterval(function(){
        sisaDetik--;
        if(sisaDetik <= 0){
            clearInterval(x);
            countdownEl.innerHTML = "HABIS";
            countdownEl.className = "fw-bold text-danger";
            setTimeout(() => location.reload(), 2000);
        } else {
            const minutes = Math.floor(sisaDetik / 60);
            const seconds = Math.floor(sisaDetik % 60);
            const m = minutes < 10 ? "0" + minutes : minutes;
            const s = seconds < 10 ? "0" + seconds : seconds;
            countdownEl.innerHTML = m + ":" + s;
        }
    },1000);
} else {
    countdownEl.innerHTML = "DITUTUP";
    countdownEl.className = "fw-bold text-danger";
}
</script>
<?php if(!empty($_SESSION['deposito_notifications'])): ?>

<script>

const depositoNotif = <?php echo json_encode($_SESSION['deposito_notifications']); ?>;

async function showDepositoNotif() {

    for (const item of depositoNotif) {

        await Swal.fire({
            icon: 'success',
            title: 'Investasi Jatuh Tempo',
html: `
            <div style="text-align:left">
                <p>Profit Investasi <b>${item.aset}</b> telah cair. Pokok investasi tetap utuh di portofolio Anda.</p>
                <hr>
                <table style="width:100%">
                    <tr>
                        <td>Bunga/Profit Diterima</td>
                        <td align="right" style="color:green">
                            + Rp ${Number(item.nominal_bunga).toLocaleString('id-ID')}
                        </td>
                    </tr>
                </table>
            </div>
        `,
            confirmButtonText: 'OK'
        });

    }

}

showDepositoNotif();

</script>

<?php unset($_SESSION['deposito_notifications']); ?>
<?php endif; ?>

<?php if(!empty($_SESSION['force_sell_notifications'])): ?>
<script>
const forceSells = <?php echo json_encode($_SESSION['force_sell_notifications']); ?>;
let indexFS = 0;

function showForceSellNotif() {
    if (indexFS >= forceSells.length) return;
    const item = forceSells[indexFS];
    indexFS++;

    let profitText = item.profit >= 0 ? `+ Rp ${Number(item.profit).toLocaleString('id-ID')}` : `- Rp ${Number(Math.abs(item.profit)).toLocaleString('id-ID')}`;
    let profitColor = item.profit >= 0 ? 'green' : 'red';

    Swal.fire({
        title: 'Penjualan Otomatis Akhir Simulasi',
        icon: 'info',
        html: `
            <div style="text-align:left">
                <p>Simulasi (Periode Terakhir) telah berakhir. Sisa aset <b>${item.aset}</b> Anda telah dicairkan otomatis ke Saldo Tunai.</p>
                <hr>
                <table style="width:100%">
                    <tr>
                        <td>Total Nilai Jual</td>
                        <td align="right"><b>Rp ${Number(item.hasil).toLocaleString('id-ID')}</b></td>
                    </tr>
                    <tr>
                        <td>Profit/Loss</td>
                        <td align="right" style="color:${profitColor}"><b>${profitText}</b></td>
                    </tr>
                </table>
            </div>
        `,
        confirmButtonText: 'OK',
        allowOutsideClick: false
    }).then(() => {
        showForceSellNotif(); // Panggil notif selanjutnya jika ada beberapa aset
    });
}

// Jalankan notifikasi force sell
setTimeout(showForceSellNotif, 500); // delay dikit agar tidak bentrok dengan notif deposito
</script>
<?php unset($_SESSION['force_sell_notifications']); ?>
<?php endif; ?>
</body>
</html>