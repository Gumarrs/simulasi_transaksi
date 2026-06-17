<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

$filter_period = $_GET['period'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_kategori = $_GET['kategori'] ?? '';
$filter_tipe = $_GET['tipe_simulasi'] ?? '';
$filter_asset = $_GET['asset_id'] ?? '';
$filter_user = $_GET['user_id'] ?? '';
$export = $_GET['export'] ?? '';

$where = "WHERE u.role='peserta'";
$params = [];
$types = "";

if ($filter_period !== '') {
    $where .= " AND t.period = ?";
    $params[] = $filter_period;
    $types .= "i";
}

if ($filter_type !== '') {
    $where .= " AND t.type = ?";
    $params[] = $filter_type;
    $types .= "s";
}

if ($filter_kategori !== '') {
    $where .= " AND a.kategori = ?";
    $params[] = $filter_kategori;
    $types .= "s";
}

if ($filter_tipe !== '') {
    $where .= " AND a.tipe_simulasi = ?";
    $params[] = $filter_tipe;
    $types .= "s";
}

if ($filter_asset !== '') {
    $where .= " AND a.id = ?";
    $params[] = $filter_asset;
    $types .= "i";
}

if ($filter_user !== '') {
    $where .= " AND u.id = ?";
    $params[] = $filter_user;
    $types .= "i";
}

$sql = "
    SELECT 
        t.id AS transaksi_id,
        t.created_at,
        t.period,
        t.type,
        t.amount_money,
        t.qty,
        t.buy_price,
        t.sell_price,
        t.realized_profit,
        t.with_insurance,
        t.is_active,
        t.remaining_qty,

        u.id AS user_id,
        u.username,
        u.nama_lengkap,

        a.id AS asset_id,
        a.nama_aset,
        a.group_name,
        a.kategori,
        a.tipe_simulasi,
        a.satuan
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    JOIN market_assets a ON t.asset_id = a.id
    $where
    ORDER BY t.created_at DESC, t.id DESC
";

$stmt = mysqli_prepare($conn, $sql);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$rows = [];
$total_beli = 0;
$total_jual = 0;
$total_profit = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;

    if ($row['type'] === 'buy') {
        $total_beli += (float)$row['amount_money'];
    } else {
        $total_jual += (float)$row['amount_money'];
    }

    $total_profit += (float)$row['realized_profit'];
}

$q_users = mysqli_query($conn, "SELECT id, username, nama_lengkap FROM users WHERE role='peserta' ORDER BY username ASC");
$q_assets = mysqli_query($conn, "SELECT id, nama_aset, group_name FROM market_assets ORDER BY nama_aset ASC");

if ($export === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=monitoring_transaksi.xls");
    header("Pragma: no-cache");
    header("Expires: 0");
}

if ($export === 'pdf') {
    echo "<script>window.onload = function(){ window.print(); }</script>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Monitoring Transaksi - Admin</title>

    <?php if ($export === ''): ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php endif; ?>

    <style>
        body { background-color: #f8f9fc; }
        .sidebar { min-height: 100vh; background-color: #212529; color: white; padding-top: 20px; }
        .sidebar a { color: #adb5bd; text-decoration: none; padding: 10px 20px; display: block; }
        .sidebar a:hover, .sidebar a.active { color: #fff; background-color: #343a40; border-left: 4px solid #0d6efd; }
        .stat-card { border: 0; border-radius: 16px; }
        .table th { white-space: nowrap; }
        .table td { white-space: nowrap; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">

        <?php if ($export === ''): ?>
        <div class="col-md-2 sidebar no-print">
            <h5 class="px-3 mb-4 fw-bold">
                <i class="fa-solid fa-chart-line text-primary"></i>
                Admin Panel
            </h5>

            <a href="dashboard.php">
                <i class="fa-solid fa-gauge me-2"></i> Dashboard
            </a>

            <a href="kelola_aset.php">
                <i class="fa-solid fa-boxes-stacked me-2"></i> Kelola Investasi
            </a>

            <a href="data_peserta.php">
                <i class="fa-solid fa-users me-2"></i> Data Peserta
            </a>

            <a href="leaderboard.php" class="active">
                <i class="fa-solid fa-chart-column me-2"></i> Monitoring Transaksi
            </a>

            <div class="mt-5 px-3">
                <a href="#" onclick="confirmLogout()" class="btn btn-danger btn-sm w-100 fw-bold">
                    <i class="fa-solid fa-right-from-bracket me-1"></i> Keluar
                </a>
            </div>
        </div>
        <?php endif; ?>

        <div class="<?php echo ($export === '') ? 'col-md-10' : 'col-md-12'; ?> p-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0">
                    <?php if ($export === ''): ?>
                    <i class="fa-solid fa-chart-column text-primary"></i>
                    <?php endif; ?>
                    Monitoring Transaksi Peserta
                </h3>

                <?php if ($export === ''): ?>
                <span class="badge bg-primary fs-6">Admin</span>
                <?php endif; ?>
            </div>

            <?php if ($export === ''): ?>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card stat-card shadow-sm">
                        <div class="card-body">
                            <div class="text-muted small">Total Pembelian</div>
                            <h4 class="fw-bold text-danger mb-0"><?php echo rupiah($total_beli); ?></h4>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card stat-card shadow-sm">
                        <div class="card-body">
                            <div class="text-muted small">Total Penjualan / Pencairan</div>
                            <h4 class="fw-bold text-success mb-0"><?php echo rupiah($total_jual); ?></h4>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card stat-card shadow-sm">
                        <div class="card-body">
                            <div class="text-muted small">Total Realized Profit</div>
                            <h4 class="fw-bold text-primary mb-0"><?php echo rupiah($total_profit); ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4 no-print">
                <div class="card-body">
                    <form method="GET" class="row g-3">

                        <div class="col-md-2">
                            <label class="form-label">Periode</label>
                            <select name="period" class="form-select">
                                <option value="">Semua</option>
                                <option value="1" <?php if($filter_period==='1') echo 'selected'; ?>>Periode 1</option>
                                <option value="2" <?php if($filter_period==='2') echo 'selected'; ?>>Periode 2</option>
                                <option value="3" <?php if($filter_period==='3') echo 'selected'; ?>>Periode 3</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Jenis Transaksi</label>
                            <select name="type" class="form-select">
                                <option value="">Semua</option>
                                <option value="buy" <?php if($filter_type==='buy') echo 'selected'; ?>>Beli</option>
                                <option value="sell" <?php if($filter_type==='sell') echo 'selected'; ?>>Jual / Cair</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Kategori</label>
                            <select name="kategori" class="form-select">
                                <option value="">Semua</option>
                                <option value="Paper" <?php if($filter_kategori==='Paper') echo 'selected'; ?>>Paper</option>
                                <option value="Commodity" <?php if($filter_kategori==='Commodity') echo 'selected'; ?>>Commodity</option>
                                <option value="Real" <?php if($filter_kategori==='Real') echo 'selected'; ?>>Real</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Tipe Investasi</label>
                            <select name="tipe_simulasi" class="form-select">
                                <option value="">Semua</option>
                                <option value="market" <?php if($filter_tipe==='market') echo 'selected'; ?>>Market</option>
                                <option value="persentase" <?php if($filter_tipe==='persentase') echo 'selected'; ?>>Persentase</option>
                                <option value="bisnis" <?php if($filter_tipe==='bisnis') echo 'selected'; ?>>Bisnis</option>
                                <option value="edukasi" <?php if($filter_tipe==='edukasi') echo 'selected'; ?>>Edukasi</option>
                                <option value="properti" <?php if($filter_tipe==='properti') echo 'selected'; ?>>Properti</option>
                                <option value="proteksi" <?php if($filter_tipe==='proteksi') echo 'selected'; ?>>Proteksi</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Peserta</label>
                            <select name="user_id" class="form-select">
                                <option value="">Semua</option>
                                <?php while($user = mysqli_fetch_assoc($q_users)): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php if($filter_user == $user['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($user['username'] . ' - ' . $user['nama_lengkap']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Aset</label>
                            <select name="asset_id" class="form-select">
                                <option value="">Semua</option>
                                <?php while($asset = mysqli_fetch_assoc($q_assets)): ?>
                                    <option value="<?php echo $asset['id']; ?>" <?php if($filter_asset == $asset['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($asset['nama_aset']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary fw-bold">
                                <i class="fa-solid fa-filter me-1"></i> Terapkan Filter
                            </button>

                            <a href="leaderboard.php" class="btn btn-secondary">
                                Reset
                            </a>

                            <button type="submit" name="export" value="excel" class="btn btn-success ms-auto">
                                <i class="fa-solid fa-file-excel me-1"></i> Export Excel
                            </button>

                            <button type="submit" name="export" value="pdf" class="btn btn-danger">
                                <i class="fa-solid fa-file-pdf me-1"></i> Export PDF
                            </button>
                        </div>

                    </form>
                </div>
            </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0 align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>No</th>
                                    <th>Waktu</th>
                                    <th>ID User</th>
                                    <th>Username</th>
                                    <th>Nama Peserta</th>
                                    <th>Periode</th>
                                    <th>Transaksi</th>
                                    <th>Nama Aset</th>
                                    <th>Group</th>
                                    <th>Kategori</th>
                                    <th>Tipe Investasi</th>
                                    <th>Nominal</th>
                                    <th>Qty</th>
                                    <th>Harga Beli</th>
                                    <th>Harga Jual</th>
                                    <th>Profit</th>
                                    <th>Asuransi</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($rows) > 0): ?>
                                    <?php $no = 1; foreach ($rows as $r): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($r['created_at'])); ?></td>
                                        <td><?php echo $r['user_id']; ?></td>
                                        <td><?php echo htmlspecialchars($r['username']); ?></td>
                                        <td><?php echo htmlspecialchars($r['nama_lengkap']); ?></td>
                                        <td>Periode <?php echo $r['period']; ?></td>
                                        <td>
                                            <?php if ($r['type'] === 'buy'): ?>
                                                <span class="badge bg-danger">BELI</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">JUAL / CAIR</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($r['nama_aset']); ?></td>
                                        <td><?php echo htmlspecialchars($r['group_name']); ?></td>
                                        <td><?php echo htmlspecialchars($r['kategori']); ?></td>
                                        <td><?php echo htmlspecialchars($r['tipe_simulasi']); ?></td>
                                        <td><?php echo rupiah($r['amount_money']); ?></td>
                                        <td><?php echo number_format((float)$r['qty'], 4, ',', '.'); ?> <?php echo htmlspecialchars($r['satuan']); ?></td>
                                        <td><?php echo rupiah($r['buy_price']); ?></td>
                                        <td><?php echo rupiah($r['sell_price']); ?></td>
                                        <td><?php echo rupiah($r['realized_profit']); ?></td>
                                        <td><?php echo $r['with_insurance'] ? 'Ya' : 'Tidak'; ?></td>
                                        <td><?php echo $r['is_active'] ? 'Aktif' : 'Selesai'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="18" class="text-center text-muted py-4">
                                            Belum ada data transaksi sesuai filter.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>

                            <tfoot>
                                <tr class="fw-bold table-light">
                                    <td colspan="11" class="text-end">TOTAL</td>
                                    <td><?php echo rupiah($total_beli + $total_jual); ?></td>
                                    <td colspan="3"></td>
                                    <td><?php echo rupiah($total_profit); ?></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <?php if ($export === ''): ?>
            <p class="text-muted mt-3 small">
                Data ini mengikuti filter yang dipilih. Export Excel/PDF juga hanya mengambil data sesuai filter.
            </p>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php if ($export === ''): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmLogout() {
    Swal.fire({
        icon: 'question',
        title: 'Konfirmasi Logout',
        text: 'Apakah Anda yakin ingin keluar dari panel admin?',
        showCancelButton: true,
        confirmButtonText: 'Ya, Keluar',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        reverseButtons: true
    }).then((result) => {
        if(result.isConfirmed) {
            window.location.href = '../config/logout.php';
        }
    });
}
</script>
<?php endif; ?>

</body>
</html>