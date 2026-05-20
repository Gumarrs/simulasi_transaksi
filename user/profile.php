<?php
session_start();
require_once '../config/koneksi.php';

// Proteksi akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'peserta') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data user lengkap dengan hasil assessment-nya
$query = mysqli_query($conn, "
    SELECT u.id as uid, u.username, u.nama_lengkap, u.balance as current_balance, f.* FROM users u 
    LEFT JOIN financial_assessment f ON u.id = f.user_id 
    WHERE u.id = '$user_id'
");
$user = mysqli_fetch_assoc($query);

// Fungsi pembantu untuk format Rupiah agar rapi
function rp($angka) {
    return number_format((float)$angka, 0, ',', '.');
}

// ======================================
// AMBIL HISTORY INVESTASI
// ======================================

$q_laporan = mysqli_query($conn, "

    SELECT
        t.period,
        t.type,
        t.amount_money,
        t.realized_profit,
        a.nama_aset

    FROM transactions t

    JOIN market_assets a
        ON t.asset_id = a.id

    WHERE t.user_id = '$user_id'

    ORDER BY t.period ASC

");

// ======================================
// AMBIL PERIODE AKTIF
// ======================================

$q_setting = mysqli_query($conn, "
    SELECT active_period
    FROM system_settings
    LIMIT 1
");

$setting = mysqli_fetch_assoc($q_setting);

$active_period =
    intval($setting['active_period'] ?? 1);


// ======================================
// AMBIL HISTORY INVESTASI
// ======================================

$q_laporan = mysqli_query($conn, "

    SELECT
        t.period,
        t.type,
        t.amount_money,
        t.realized_profit,
        a.nama_aset

    FROM transactions t

    JOIN market_assets a
        ON t.asset_id = a.id

    WHERE t.user_id = '$user_id'

    ORDER BY t.period ASC

");

$laporan = [];

$total_profit_all = 0;

while($r = mysqli_fetch_assoc($q_laporan)) {

    $p = $r['period'];

    if(!isset($laporan[$p])) {

        $laporan[$p] = [];
    }

    $laporan[$p][] = $r;

    // TOTAL PROFIT REALIZED
    $total_profit_all += floatval($r['realized_profit']);
}


// ======================================
// PERHITUNGAN HARTA AWAL
// ======================================

// TOTAL ASET
$total_aset =
    floatval($user['total_aset']);

// TOTAL UTANG
$total_utang =
    floatval($user['total_utang']);

// NET WORTH AWAL
$net_worth_awal =
    $total_aset - $total_utang;


// ======================================
// TOTAL DANA PENSIUN
// ======================================

$total_dana_pensiun =
    floatval($user['up_dplk']) +
    floatval($user['up_bpjs']) +
    floatval($user['up_company']);


// ======================================
// TOTAL HARTA AWAL FINAL
// ======================================

$total_harta_awal =
    $net_worth_awal +
    $total_dana_pensiun;


// ======================================
// PENGELUARAN HIDUP
// 1 PERIODE = 2 TAHUN
// ======================================

$total_pengeluaran_hidup =
    floatval($user['monthly_expense'])
    * 24
    * ($active_period - 1);


// ======================================
// SISA HARTA SAAT INI
// ======================================

$sisa_harta = floatval($user['current_balance']);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; padding-bottom: 75px; }
        .mobile-container { max-width: 480px; margin: auto; background: #fff; min-height: 100vh; box-shadow: 0 0 15px rgba(0,0,0,0.05); position: relative;}
        .bottom-nav { position: fixed; bottom: 0; width: 100%; max-width: 480px; background: #fff; border-top: 1px solid #ddd; z-index: 1000; display: flex; justify-content: space-around; padding: 10px 0; }
        .nav-item { text-align: center; color: #6c757d; text-decoration: none; font-size: 0.8rem; }
        .nav-item.active { color: #0d6efd; font-weight: bold; }
        .nav-item i { font-size: 1.2rem; display: block; margin-bottom: 2px; }
        
        .profile-header { background: linear-gradient(135deg, #0d6efd, #0b5ed7); color: white; padding: 40px 20px; border-bottom-left-radius: 30px; border-bottom-right-radius: 30px; }
        .info-card { margin-top: -30px; border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        
        /* Styling untuk Modal Laporan */
        .report-section-title { background-color: #e9ecef; padding: 8px 12px; border-radius: 8px; font-weight: bold; font-size: 0.85rem; color: #495057; margin-bottom: 10px; margin-top: 15px;}
        .report-row { display: flex; justify-content: space-between; font-size: 0.8rem; padding: 4px 0; border-bottom: 1px dashed #eee; }
        .report-row:last-child { border-bottom: none; }
        .report-total { display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: bold; padding-top: 8px; margin-top: 8px; border-top: 2px solid #eee;}
    </style>
</head>
<body>

<div class="mobile-container">
    <div class="profile-header text-center">
        <div class="mb-3">
            <div class="bg-white d-inline-block rounded-circle p-1 shadow-sm">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['nama_lengkap']); ?>&background=random&color=0d6efd&size=80" class="rounded-circle" alt="Avatar">
            </div>
        </div>
        <h5 class="fw-bold mb-0"><?php echo $user['nama_lengkap']; ?></h5>
        <small class="opacity-75">ID: <?php echo $user['username']; ?></small>
    </div>

    <div class="p-3">
        <div class="card info-card mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i> Data Finansial Awal</h6>
                
                <div class="d-flex justify-content-between mb-2">
                    <small class="text-muted">Kekayaan Bersih (Net Worth)</small>
                    <span class="fw-bold text-dark">Rp <?php echo rp($user['net_worth']); ?></span>
                </div>
                
                <div class="d-flex justify-content-between mb-2">
                    <small class="text-muted">Total Dana Pensiun & Pesangon</small>
                    <span class="fw-bold text-info">Rp <?php echo rp($user['total_pension']); ?></span>
                </div>

                <hr class="my-2 opacity-50">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <small class="text-muted d-block">Pengeluaran Bulanan</small>
                        <span class="fw-bold text-danger">Rp <?php echo rp($user['monthly_expense']); ?></span>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block">Burn Rate (2 Thn)</small>
                        <span class="badge bg-danger">Rp <?php echo rp($user['monthly_expense'] * 24); ?></span>
                    </div>
                </div>
                
                <button class="btn btn-outline-primary btn-sm w-100 fw-bold" data-bs-toggle="modal" data-bs-target="#modalLaporan">
                    <i class="fa-solid fa-list-check me-1"></i> Lihat Detail Finansial
                </button>
                <button
    class="btn btn-dark btn-sm w-100 fw-bold mt-2"
    data-bs-toggle="modal"
    data-bs-target="#modalLaporanKeuangan"
>
    <i class="fa-solid fa-chart-line me-1"></i>
    Lihat Laporan Keuangan
</button>
            </div>
        </div>

        <h6 class="fw-bold px-1 mb-3">Pengaturan Akun</h6>
        <div class="list-group list-group-flush border rounded-3 overflow-hidden mb-4">
            <div class="list-group-item p-3 d-flex justify-content-between align-items-center">
                <div>
                    <i class="fa-solid fa-shield-halved text-muted me-2"></i>
                    <small class="text-muted">Status Akun</small>
                </div>
                <span class="badge bg-success">Aktif</span>
            </div>
<a 
    href="#"
    onclick="confirmLogout()"
    class="list-group-item list-group-item-action p-3 text-danger fw-bold"
>
    <i class="fa-solid fa-right-from-bracket me-2"></i>
    Keluar / Logout
</a>
        </div>

        <div class="mt-4 text-center">
            <p class="text-muted" style="font-size: 0.7rem;">
                Aplikasi Simulasi Pensiun v1.0 <br>
                &copy; 2026 Retirement Platform
            </p>
        </div>
    </div>

    <div class="bottom-nav shadow-lg">
        <a href="dashboard.php" class="nav-item"><i class="fa-solid fa-house"></i>Home</a>
        <a href="portfolio.php" class="nav-item"><i class="fa-solid fa-briefcase"></i>Portofolio</a>
        <a href="leaderboard.php" class="nav-item"><i class="fa-solid fa-trophy"></i>Peringkat</a>
        <a href="profile.php" class="nav-item active"><i class="fa-solid fa-user"></i>Profil</a>
    </div>
</div>

<div class="modal fade" id="modalLaporan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 15px;">
            <div class="modal-header bg-primary text-white border-0" style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
                <h6 class="modal-title fw-bold"><i class="fa-solid fa-file-lines me-2"></i>Detail Finansial Awal</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 bg-light">
                
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-3">
                        <h6 class="fw-bold text-success border-bottom pb-2 mb-2">Total Kekayaan (Aset)</h6>
                        
                        <div class="report-section-title mt-0">Aset Likuid (Tunai)</div>
                        <div class="report-row"><span>Dana Tunai</span><span>Rp <?php echo rp($user['dana_tunai']); ?></span></div>
                        <div class="report-row"><span>Tabungan/Giro</span><span>Rp <?php echo rp($user['tabungan_giro']); ?></span></div>
                        <div class="report-row"><span>Piutang</span><span>Rp <?php echo rp($user['piutang']); ?></span></div>
                        <div class="report-row"><span>Lain-lain</span><span>Rp <?php echo rp($user['likuid_lain']); ?></span></div>
                        
                        <div class="report-section-title">Kekayaan Pribadi</div>
                        <div class="report-row"><span>Rumah Tinggal</span><span>Rp <?php echo rp($user['nilai_rumah']); ?></span></div>
                        <div class="report-row"><span>Kendaraan</span><span>Rp <?php echo rp($user['nilai_kendaraan']); ?></span></div>
                        <div class="report-row"><span>Perhiasan</span><span>Rp <?php echo rp($user['perhiasan']); ?></span></div>
                        
                        <div class="report-section-title">Aset Investasi</div>
                        <div class="report-row"><span>Deposito</span><span>Rp <?php echo rp($user['deposito']); ?></span></div>
                        <div class="report-row"><span>Obligasi</span><span>Rp <?php echo rp($user['obligasi']); ?></span></div>
                        <div class="report-row"><span>Reksadana/Saham</span><span>Rp <?php echo rp($user['reksadana_saham']); ?></span></div>
                        <div class="report-row"><span>Emas</span><span>Rp <?php echo rp($user['emas']); ?></span></div>
                        <div class="report-row"><span>Bisnis</span><span>Rp <?php echo rp($user['bisnis']); ?></span></div>
                        <div class="report-row"><span>Properti Investasi</span><span>Rp <?php echo rp($user['rumah_investasi']); ?></span></div>
                        <div class="report-row"><span>Lainnya</span><span>Rp <?php echo rp($user['investasi_lain']); ?></span></div>

                        <div class="report-total text-success">
                            <span>TOTAL ASET</span>
                            <span>Rp <?php echo rp($user['total_aset']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-3">
                        <h6 class="fw-bold text-danger border-bottom pb-2 mb-2">Total Kewajiban (Utang)</h6>
                        <div class="report-row"><span>Kartu Kredit</span><span>Rp <?php echo rp($user['utang_cc']); ?></span></div>
                        <div class="report-row"><span>Tagihan Belum Lunas</span><span>Rp <?php echo rp($user['tagihan_lunas']); ?></span></div>
                        <div class="report-row"><span>KPR (Rumah)</span><span>Rp <?php echo rp($user['utang_rumah'] + $user['utang_rumah_2']); ?></span></div>
                        <div class="report-row"><span>Kredit Kendaraan</span><span>Rp <?php echo rp($user['utang_kendaraan']); ?></span></div>
                        <div class="report-row"><span>Pinjaman Lainnya</span><span>Rp <?php echo rp($user['pinjaman_lain']); ?></span></div>
                        
                        <div class="report-total text-danger">
                            <span>TOTAL UTANG</span>
                            <span>Rp <?php echo rp($user['total_utang']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-3">
                        <h6 class="fw-bold text-info border-bottom pb-2 mb-2">Dana Pensiun & Pesangon</h6>
                        <div class="report-row"><span>DPLK</span><span>Rp <?php echo rp($user['up_dplk']); ?></span></div>
                        <div class="report-row"><span>BPJS Ketenagakerjaan (JHT)</span><span>Rp <?php echo rp($user['up_bpjs']); ?></span></div>
                        <div class="report-row"><span>Pesangon Perusahaan</span><span>Rp <?php echo rp($user['up_company']); ?></span></div>
                        
                        <div class="report-total text-info">
                            <span>TOTAL DANA PENSIUN</span>
                            <span>Rp <?php echo rp($user['total_pension']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-2">
                    <div class="card-body p-3">
                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-2">Arus Kas Bulanan</h6>
                        
                        <div class="report-section-title mt-0 text-success">Pemasukan</div>
                        <div class="report-row"><span>Gaji Rutin</span><span>Rp <?php echo rp($user['gaji']); ?></span></div>
                        <div class="report-row"><span>Bonus/Komisi</span><span>Rp <?php echo rp($user['bonus'] + $user['komisi']); ?></span></div>
                        <div class="report-row"><span>Pemasukan Lain</span><span>Rp <?php echo rp($user['in_lain']); ?></span></div>

                        <div class="report-section-title text-danger">Pengeluaran Utama</div>
                        <div class="report-row"><span>Sewa / Cicilan Utama</span><span>Rp <?php echo rp($user['out_sewa_cicilan']); ?></span></div>
                        <div class="report-row"><span>Listrik, Air, Internet</span><span>Rp <?php echo rp($user['out_listrik_air_internet']); ?></span></div>
                        <div class="report-row"><span>Belanja Dapur / Pasar</span><span>Rp <?php echo rp($user['out_belanja_pasar']); ?></span></div>
                        <div class="report-row"><span>Transportasi / BBM</span><span>Rp <?php echo rp($user['out_bbm_tol_parkir']); ?></span></div>
                        
                        <div class="report-total text-dark">
                            <span>TOTAL PENGELUARAN</span>
                            <span>Rp <?php echo rp($user['total_pengeluaran_bulanan']); ?></span>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer border-0 p-3 bg-white" style="border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;">
                <button type="button" class="btn btn-secondary w-100 fw-bold" data-bs-dismiss="modal">Tutup Laporan</button>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

function confirmLogout() {

    Swal.fire({

        icon: 'question',

        title: 'Keluar dari akun?',

        text: 'Anda akan keluar dari sesi simulasi.',

        showCancelButton: true,

        confirmButtonText: 'Ya, Keluar',

        cancelButtonText: 'Batal',

        confirmButtonColor: '#dc3545',

        reverseButtons: true

    }).then((result) => {

        if(result.isConfirmed) {

            Swal.fire({

                title: 'Mengeluarkan akun...',
                html: 'Mohon tunggu sebentar',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,

                didOpen: () => {

                    Swal.showLoading();

                }

            });

            setTimeout(() => {

                window.location.href = '../config/logout.php';

            }, 800);

        }

    });

}

</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<div
    class="modal fade"
    id="modalLaporanKeuangan"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-scrollable">

        <div
            class="modal-content border-0"
            style="border-radius: 18px;"
        >

            <div class="modal-header bg-dark text-white">

                <h6 class="fw-bold mb-0">
                    Laporan Keuangan Simulasi
                </h6>

                <button
                    type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="modal"
                ></button>

            </div>

            <div class="modal-body bg-light">

                <!-- HARTA AWAL -->

                <div class="card border-0 shadow-sm mb-3">

                    <div class="card-body">

                        <small class="text-muted d-block mb-1">
                            Total Harta Awal
                        </small>

                        <h5 class="fw-bold text-dark mb-0">

                            Rp <?php echo rp($total_harta_awal); ?>

                        </h5>

                    </div>

                </div>

                <!-- PERIODE -->

                <?php foreach($laporan as $periode => $items): ?>

                    <div class="card border-0 shadow-sm mb-3">

                        <div class="card-body">

                            <h6 class="fw-bold text-primary mb-3">
                                Periode <?php echo $periode; ?>
                            </h6>

                            <?php foreach($items as $item): ?>

                                <div class="d-flex justify-content-between small mb-2">

                                    <div>

                                        <span class="<?php echo $item['type'] == 'buy' ? 'text-primary' : 'text-danger'; ?> fw-bold">

                                            <?php echo strtoupper($item['type']); ?>

                                        </span>

                                        —
                                        <?php echo $item['nama_aset']; ?>

                                    </div>

                                    <div>

                                        Rp <?php echo rp($item['amount_money']); ?>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                            <hr>

                            <div class="d-flex justify-content-between">

                                <span class="small text-muted">
                                    Pengeluaran Hidup
                                </span>

                                <span class="fw-bold text-danger">

                                    Rp <?php echo rp($user['monthly_expense'] * 24); ?>

                                </span>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

                <!-- HASIL AKHIR -->

                <div class="card border-0 bg-dark text-white">

                    <div class="card-body text-center">

                        <small class="opacity-75">
                            Estimasi Sisa Kekayaan
                        </small>

                        <h4 class="fw-bold text-warning mt-2 mb-0">

                            Rp <?php echo rp($sisa_harta); ?>

                        </h4>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>
</body>
</html>