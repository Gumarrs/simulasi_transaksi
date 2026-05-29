<?php
session_start();
require_once '../config/koneksi.php';
// HAPUS REQUIRE AUTO CAIR DARI SINI

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
$period_status = $settings['period_status']; 

// ==============================================================
// 1. LOGIKA COVER PAGE (WAJIB UNTUK SEMUA PERIODE/STATUS)
// ==============================================================
$q_cover = mysqli_query($conn,"
    SELECT id
    FROM period_cover_views
    WHERE user_id='$user_id'
    AND period='$active_period'
");

// Jika belum baca cover page, stop semua dan lempar ke cover.php
if(mysqli_num_rows($q_cover) == 0) {
    header("Location: cover.php");
    exit; 
}
// ==============================================================


// ==============================================================
// 2. LOGIKA PROFIT BERJALAN DI SINI
// (Hanya tereksekusi jika user sudah lolos pengecekan Cover Page)
// ==============================================================
require_once 'auto_cair_deposito.php';
// ==============================================================


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
    WHERE
    nama_aset NOT IN ('Showroom', 'Travel Agent')
    AND
    (group_name IS NULL OR group_name = '' OR group_name = 'bodong' )
");

$q_tour_parent = mysqli_query($conn, "
    SELECT id, nama_aset, kategori, gambar
    FROM market_assets
    WHERE nama_aset = 'Travel Agent'
    LIMIT 1
");

$tour_parent = mysqli_fetch_assoc($q_tour_parent);

// JIKA QUERY GAGAL, TAMPILKAN PESAN ERROR DATABASE-NYA
if (!$query_assets) {
    die("<div class='alert alert-danger m-4'><b>Error Database:</b> " . mysqli_error($conn) . "<br>Cek apakah kolom <b>$kolom_val</b> atau <b>$kolom_laba</b> benar-benar ada di tabel market_assets.</div>");
}

$q_showroom_parent = mysqli_query($conn, "
    SELECT id, nama_aset, kategori, gambar
    FROM market_assets
    WHERE nama_aset = 'Showroom'
    LIMIT 1
");

$showroom_parent = mysqli_fetch_assoc($q_showroom_parent);

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
body{
    background:#eef2f7;
    padding-bottom:85px;
    font-family:'Segoe UI',sans-serif;
}

.mobile-container{
    max-width:480px;
    margin:auto;
    background:#f8fafc;
    min-height:100vh;
    box-shadow:0 0 18px rgba(15,23,42,.08);
}

.topbar{
    background:white;
    border-bottom:1px solid #e5e7eb;
    position:sticky;
    top:0;
    z-index:99;
}

.period-pill{
    background:#eef6ff;
    color:#0d6efd;
    border-radius:999px;
    padding:6px 12px;
    font-size:.78rem;
    font-weight:700;
}

.saldo-card{
    background:linear-gradient(135deg,#111827,#0d6efd);
    border-radius:24px;
    color:white;
    position:relative;
    overflow:hidden;
}

.saldo-card:after{
    content:"";
    position:absolute;
    width:150px;
    height:150px;
    right:-45px;
    top:-55px;
    background:rgba(255,255,255,.12);
    border-radius:50%;
}

.market-title{
    font-weight:800;
    color:#111827;
}

.view-toggle .btn{
    border-radius:12px;
    width:36px;
    height:36px;
}

.asset-card{
    border-radius:20px;
    overflow:hidden;
    border:none;
    background:white;
    box-shadow:0 8px 22px rgba(15,23,42,.08);
    transition:.2s;
}

.asset-card:active{
    transform:scale(.98);
}

.asset-img{
    height:125px;
    object-fit:cover;
    background:#f1f5f9;
}

.asset-body{
    padding:12px;
}

.asset-badge{
    display:inline-block;
    background:#f1f5f9;
    color:#334155;
    border-radius:999px;
    padding:4px 9px;
    font-size:.68rem;
    font-weight:700;
    margin-bottom:7px;
}

.asset-name{
    font-size:.86rem;
    font-weight:800;
    color:#111827;
    min-height:35px;
    margin-bottom:6px;
}

.asset-price{
    color:#0d6efd;
    font-weight:800;
    font-size:.82rem;
}

.asset-sub{
    color:#64748b;
    font-size:.68rem;
}

.showroom-card{
    background:linear-gradient(135deg,#1e293b,#2563eb);
    color:white;
}

.showroom-card .asset-badge{
    background:rgba(255,255,255,.16);
    color:white;
    border:1px solid rgba(255,255,255,.25);
}

.showroom-card .asset-name,
.showroom-card .asset-price,
.showroom-card .asset-sub{
    color:white;
}

.asset-list{
    border-radius:18px;
    padding:12px;
    background:white;
    border:1px solid #e5e7eb;
    box-shadow:0 6px 18px rgba(15,23,42,.06);
    margin-bottom:10px;
}

.asset-list-img{
    width:52px;
    height:52px;
    border-radius:14px;
    object-fit:cover;
    background:#f1f5f9;
}

.list-name{
    font-weight:800;
    color:#111827;
    font-size:.9rem;
}

.list-category{
    color:#64748b;
    font-size:.72rem;
}

.list-price{
    color:#0d6efd;
    font-weight:800;
    font-size:.82rem;
}

.bottom-nav{
    position:fixed;
    bottom:0;
    width:100%;
    max-width:480px;
    background:white;
    border-top:1px solid #e5e7eb;
    display:flex;
    justify-content:space-around;
    padding:10px 0;
    z-index:999;
}

.nav-item{
    text-decoration:none;
    color:#6c757d;
    text-align:center;
    font-size:.78rem;
}

.nav-item.active{
    color:#0d6efd;
    font-weight:bold;
}

.nav-item i{
    display:block;
    font-size:1.1rem;
    margin-bottom:2px;
}
</style>
</head>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<body>

<div class="mobile-container">
    <div class="topbar d-flex justify-content-between align-items-center p-3">
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
 <?php if($showroom_parent): ?>
<div class="col-6">
    <a href="showroom.php" class="text-decoration-none text-dark">
        <div class="card asset-card h-100">
            <img
                src="../assets/img/investasi/<?php echo !empty($showroom_parent['gambar']) ? $showroom_parent['gambar'] : 'placeholder.jpg'; ?>"
                class="asset-img w-100"
                onerror="this.src='https://via.placeholder.com/150'"
            >

            <div class="card-body p-2">
                <span class="badge bg-light text-dark border mb-2">
                    <?php echo $showroom_parent['kategori']; ?>
                </span>

                <h6 class="fw-bold mb-1" style="font-size:0.85rem;">
                    <?php echo $showroom_parent['nama_aset']; ?>
                </h6>

                <div class="text-primary fw-bold small">
                    Pilih Jenis Mobil
                </div>
            </div>
        </div>
    </a>
</div>
<?php endif; ?>

<?php if($tour_parent): ?>
<div class="col-6">
    <a href="tour.php" class="text-decoration-none text-dark">
        <div class="card asset-card h-100">
            <img
                src="../assets/img/investasi/<?php echo !empty($tour_parent['gambar']) ? $tour_parent['gambar'] : 'placeholder.jpg'; ?>"
                class="asset-img w-100"
                onerror="this.src='https://via.placeholder.com/150'"
            >

            <div class="card-body p-2">
                <span class="badge bg-light text-dark border mb-2">
                    <?php echo $tour_parent['kategori']; ?>
                </span>

                <h6 class="fw-bold mb-1" style="font-size:0.85rem;">
                    <?php echo $tour_parent['nama_aset']; ?>
                </h6>

                <div class="text-primary fw-bold small">
                    Pilih Paket Tour
                </div>
            </div>
        </div>
    </a>
</div>
<?php endif; ?>
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
            <?php if($showroom_parent): ?>

    <a href="showroom.php" class="text-decoration-none text-dark">

        <div class="asset-list mb-2 shadow-sm">

            <div class="d-flex justify-content-between align-items-center">

                <div class="d-flex align-items-center">

                    <img
                        src="../assets/img/investasi/<?php echo !empty($showroom_parent['gambar']) ? $showroom_parent['gambar'] : 'placeholder.jpg'; ?>"
                        style="width:45px;height:45px;border-radius:8px;object-fit:cover;"
                        class="me-3"
                        onerror="this.src='https://via.placeholder.com/45'"
                    >

                    <div>

                        <div class="fw-bold">
                            <?php echo $showroom_parent['nama_aset']; ?>
                        </div>

                        <small class="text-muted">
                            <?php echo $showroom_parent['kategori']; ?>
                        </small>

                    </div>

                </div>

                <div class="text-end">

                    <div class="fw-bold text-primary">
                        Pilih Jenis Mobil
                    </div>

                    <small class="text-muted" style="font-size:0.7rem;">
                        Detail
                        <i class="fa-solid fa-chevron-right"></i>
                    </small>

                </div>

            </div>

        </div>

    </a>

    <?php endif; ?>

    <?php if($tour_parent): ?>

<a href="tour.php" class="text-decoration-none text-dark">

    <div class="asset-list mb-2 shadow-sm">

        <div class="d-flex justify-content-between align-items-center">

            <div class="d-flex align-items-center">

                <img
                    src="../assets/img/investasi/<?php echo !empty($tour_parent['gambar']) ? $tour_parent['gambar'] : 'placeholder.jpg'; ?>"
                    style="width:45px;height:45px;border-radius:8px;object-fit:cover;"
                    class="me-3"
                    onerror="this.src='https://via.placeholder.com/45'"
                >

                <div>
                    <div class="fw-bold">
                        <?php echo $tour_parent['nama_aset']; ?>
                    </div>

                    <small class="text-muted">
                        <?php echo $tour_parent['kategori']; ?>
                    </small>
                </div>

            </div>

            <div class="text-end">

                <div class="fw-bold text-primary">
                    Pilih Paket Tour
                </div>

                <small class="text-muted" style="font-size:0.7rem;">
                    Detail
                    <i class="fa-solid fa-chevron-right"></i>
                </small>

            </div>

        </div>

    </div>

</a>

<?php endif; ?>

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
const endTime = "<?php echo $end_time; ?>"; // Tambahan variabel untuk mengecek settingan admin
const countdownEl = document.getElementById("countdown");

if (statusPeriode === 'closed') {
    // 1. Jika admin menutup periode secara manual
    countdownEl.innerHTML = "DITUTUP";
    countdownEl.className = "fw-bold text-danger";
} else {
    // JIKA PERIODE SEDANG OPEN:
    if (endTime && endTime.trim() !== "") {
        // 2. Jika admin MENYETING WAKTU
        if (sisaDetik > 0) {
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
            // Waktu sudah lewat
            countdownEl.innerHTML = "HABIS";
            countdownEl.className = "fw-bold text-danger";
        }
    } else {
        // 3. Jika admin TIDAK MENYETING WAKTU (Unlimited)
        countdownEl.innerHTML = "--:--";
        countdownEl.className = "fw-bold text-primary"; // Tetap warna biru agar tidak terkesan tutup
    }
}
</script>

<script>
const popupQueue = [];

// DEPOSITO
<?php if(!empty($_SESSION['deposito_notifications'])): ?>
const depositoNotif = <?php echo json_encode($_SESSION['deposito_notifications']); ?>;

depositoNotif.forEach(item => {
    popupQueue.push({
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
        `
    });
});
<?php unset($_SESSION['deposito_notifications']); ?>
<?php endif; ?>

// FORCE SELL UMUM
<?php if(!empty($_SESSION['force_sell_notifications'])): ?>
const forceSells = <?php echo json_encode($_SESSION['force_sell_notifications']); ?>;

forceSells.forEach(item => {
    let profitText = item.profit >= 0
        ? `+ Rp ${Number(item.profit).toLocaleString('id-ID')}`
        : `- Rp ${Number(Math.abs(item.profit)).toLocaleString('id-ID')}`;

    let profitColor = item.profit >= 0 ? 'green' : 'red';

    popupQueue.push({
        icon: 'info',
        title: 'Penjualan Otomatis Akhir Simulasi',
        html: `
            <div style="text-align:left">
                <p>Simulasi periode terakhir telah berakhir. Sisa aset <b>${item.aset}</b> Anda telah dicairkan otomatis ke Saldo Tunai.</p>
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
        `
    });
});
<?php unset($_SESSION['force_sell_notifications']); ?>
<?php endif; ?>

// FORCE SELL PROPERTI
<?php if(!empty($_SESSION['properti_force_sell_notifications'])): ?>
const propertiForceSells = <?php echo json_encode($_SESSION['properti_force_sell_notifications']); ?>;

propertiForceSells.forEach(item => {
    popupQueue.push({
        icon: 'info',
        title: 'Penjualan Otomatis Properti',
        html: `
            <div style="text-align:left">
                <p>Periode terakhir telah ditutup. Sisa aset <b>${item.aset}</b> Anda dicairkan otomatis sesuai status asuransi.</p>
                <hr>
                <table style="width:100%">
                    <tr>
                        <td>Unit Dengan Asuransi</td>
                        <td align="right"><b>${Number(item.unit_asuransi).toLocaleString('id-ID')}</b></td>
                    </tr>
                    <tr>
                        <td>Unit Tanpa Asuransi</td>
                        <td align="right"><b>${Number(item.unit_tanpa_asuransi).toLocaleString('id-ID')}</b></td>
                    </tr>
                    <tr>
                        <td>Total Nilai Jual</td>
                        <td align="right"><b>Rp ${Number(item.hasil_jual).toLocaleString('id-ID')}</b></td>
                    </tr>
                </table>
                <p class="mt-3 mb-0 text-danger" style="font-size:0.8rem;">
                    <i>* Properti tanpa asuransi bernilai Rp 0 pada periode 3.</i>
                </p>
            </div>
        `
    });
});
<?php unset($_SESSION['properti_force_sell_notifications']); ?>
<?php endif; ?>

// EDUKASI
<?php if(!empty($_SESSION['edukasi_notifications'])): ?>
const edukasiNotif = <?php echo json_encode($_SESSION['edukasi_notifications']); ?>;

edukasiNotif.forEach(item => {
    popupQueue.push({
        icon: 'success',
        title: 'Manfaat Pendidikan',
        confirmButtonText: 'Luar Biasa!',
        html: `
            <div style="text-align:left">
                <p>Pendidikan <b>${item.aset}</b> yang Anda ambil telah meningkatkan penghasilan Anda!</p>
                <hr>
                <table style="width:100%">
                    <tr>
                        <td>Peningkatan Penghasilan</td>
                        <td align="right" style="color:green; font-weight:bold;">
                            + Rp ${Number(item.benefit).toLocaleString('id-ID')}
                        </td>
                    </tr>
                </table>
                <p class="mt-3 mb-0 text-danger" style="font-size:0.8rem;">
                    <i>* Dana awal pendidikan hangus dan status pendidikan telah diselesaikan.</i>
                </p>
            </div>
        `
    });
});
<?php unset($_SESSION['edukasi_notifications']); ?>
<?php endif; ?>

async function runPopupQueue() {
    await new Promise(resolve => setTimeout(resolve, 700));

    for (const popup of popupQueue) {
        await Swal.fire({
            icon: popup.icon || 'info',
            title: popup.title,
            html: popup.html,
            confirmButtonText: popup.confirmButtonText || 'OK',
            allowOutsideClick: false,
            allowEscapeKey: false
        });

        await new Promise(resolve => setTimeout(resolve, 350));
    }
}

if (popupQueue.length > 0) {
    runPopupQueue();
}
</script>


</body>
</html>
