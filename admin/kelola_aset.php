<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$msg = "";
$error_msg = "";

// --- LOGIKA PEMPROSESAN DATA ---

// 1. Proses TAMBAH Aset
// 1. Proses TAMBAH Aset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_aset'])) {

    $nama = trim($_POST['nama_aset']);
    $group_name = trim($_POST['group_name']);
    $kategori = $_POST['kategori'];
    $tipe_simulasi = $_POST['tipe_simulasi'];
    $satuan = trim($_POST['satuan']);
    $multiplier = (int)$_POST['multiplier'];

    $p1 = floatval($_POST['value_p1']);
    $p2 = floatval($_POST['value_p2']);
    $p3 = floatval($_POST['value_p3']);

    $l1 = floatval($_POST['laba_p1']);
    $l2 = floatval($_POST['laba_p2']);
    $l3 = floatval($_POST['laba_p3']);

    $deskripsi_p1 = $_POST['deskripsi_p1'];
    $deskripsi_p2 = $_POST['deskripsi_p2'];
    $deskripsi_p3 = $_POST['deskripsi_p3'];

    $gambar = "placeholder.jpg";

    if(isset($_FILES['gambar']['name']) && $_FILES['gambar']['name'] != ""){

        $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));

        $gambar = time() . "_" . uniqid() . "." . $ext;

        move_uploaded_file(
            $_FILES['gambar']['tmp_name'],
            "../assets/img/investasi/" . $gambar
        );
    }

    $stmt = mysqli_prepare($conn, "
        INSERT INTO market_assets (
            nama_aset,
            group_name,
            kategori,
            tipe_simulasi,
            satuan,
            multiplier,
            value_p1,
            value_p2,
            value_p3,
            laba_p1,
            laba_p2,
            laba_p3,
            gambar,
            deskripsi_p1,
            deskripsi_p2,
            deskripsi_p3
        )
        VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?,?
        )
    ");

    mysqli_stmt_bind_param(
        $stmt,
        "sssssidddddsssss",

        $nama,
        $group_name,
        $kategori,
        $tipe_simulasi,
        $satuan,
        $multiplier,

        $p1,
        $p2,
        $p3,

        $l1,
        $l2,
        $l3,

        $gambar,

        $deskripsi_p1,
        $deskripsi_p2,
        $deskripsi_p3
    );

    if(mysqli_stmt_execute($stmt)) {

        $msg = "Aset baru berhasil ditambahkan!";

    } else {

        $error_msg = "Gagal Tambah: " . mysqli_error($conn);

    }
}

// 2. Proses EDIT Aset
// 2. Proses EDIT Aset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_aset'])) {

    $id = (int)$_POST['id_aset'];

    $nama = trim($_POST['nama_aset']);
    $group_name = trim($_POST['group_name']);
    $kategori = $_POST['kategori'];
    $tipe_simulasi = $_POST['tipe_simulasi'];
    $satuan = trim($_POST['satuan']);
    $multiplier = (int)$_POST['multiplier'];

    $p1 = floatval($_POST['value_p1']);
    $p2 = floatval($_POST['value_p2']);
    $p3 = floatval($_POST['value_p3']);

    $l1 = floatval($_POST['laba_p1']);
    $l2 = floatval($_POST['laba_p2']);
    $l3 = floatval($_POST['laba_p3']);

    $deskripsi_p1 = $_POST['deskripsi_p1'];
    $deskripsi_p2 = $_POST['deskripsi_p2'];
    $deskripsi_p3 = $_POST['deskripsi_p3'];

    $gambar_baru = null;

    if(isset($_FILES['gambar']['name']) && $_FILES['gambar']['name'] != ""){

        $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));

        $gambar_baru = time() . "_" . uniqid() . "." . $ext;

        move_uploaded_file(
            $_FILES['gambar']['tmp_name'],
            "../assets/img/investasi/" . $gambar_baru
        );
    }

    if($gambar_baru != null){

        $stmt = mysqli_prepare($conn, "
            UPDATE market_assets SET

                nama_aset = ?,
                group_name = ?,
                kategori = ?,
                tipe_simulasi = ?,
                satuan = ?,
                multiplier = ?,

                value_p1 = ?,
                value_p2 = ?,
                value_p3 = ?,

                laba_p1 = ?,
                laba_p2 = ?,
                laba_p3 = ?,

                gambar = ?,

                deskripsi_p1 = ?,
                deskripsi_p2 = ?,
                deskripsi_p3 = ?

            WHERE id = ?
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "sssssidddddsssssi",

            $nama,
            $group_name,
            $kategori,
            $tipe_simulasi,
            $satuan,
            $multiplier,

            $p1,
            $p2,
            $p3,

            $l1,
            $l2,
            $l3,

            $gambar_baru,

            $deskripsi_p1,
            $deskripsi_p2,
            $deskripsi_p3,

            $id
        );

    } else {

        $stmt = mysqli_prepare($conn, "
            UPDATE market_assets SET

                nama_aset = ?,
                group_name = ?,
                kategori = ?,
                tipe_simulasi = ?,
                satuan = ?,
                multiplier = ?,

                value_p1 = ?,
                value_p2 = ?,
                value_p3 = ?,

                laba_p1 = ?,
                laba_p2 = ?,
                laba_p3 = ?,

                deskripsi_p1 = ?,
                deskripsi_p2 = ?,
                deskripsi_p3 = ?

            WHERE id = ?
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "sssssidddddssssi",

            $nama,
            $group_name,
            $kategori,
            $tipe_simulasi,
            $satuan,
            $multiplier,

            $p1,
            $p2,
            $p3,

            $l1,
            $l2,
            $l3,

            $deskripsi_p1,
            $deskripsi_p2,
            $deskripsi_p3,

            $id
        );
    }

    if(mysqli_stmt_execute($stmt)) {

        $msg = "Data aset berhasil diperbarui!";

    } else {

        $error_msg = "Gagal Update: " . mysqli_error($conn);

    }
}

// 3. Proses HAPUS Aset
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if(mysqli_query($conn, "DELETE FROM market_assets WHERE id = '$id'")) {
        header("Location: kelola_aset.php?msg_del=1");
        exit;
    }
}

// Ambil semua data aset untuk tabel
$assets = mysqli_query($conn, "SELECT * FROM market_assets ORDER BY id DESC");
if (!$assets) {
    die("Error Database: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Investasi - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fc; font-family: 'Segoe UI', sans-serif; }
        .sidebar { min-height: 100vh; background-color: #212529; color: white; padding-top: 20px;}
        .sidebar a { color: #adb5bd; text-decoration: none; padding: 12px 20px; display: block; transition: 0.3s;}
        .sidebar a:hover, .sidebar a.active { color: #fff; background-color: #343a40; border-left: 4px solid #0d6efd;}
        .card { border-radius: 12px; }
        .modal-content { border-radius: 20px; border: none; }
        .modal-header { background-color: #f8f9fa; border-top-left-radius: 20px; border-top-right-radius: 20px; }
        .table img { border-radius: 8px; object-fit: cover; }
        .data-box { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 4px 8px; font-size: 0.85rem; }
    </style>
</head>
<body>
<div class="container-fluid">
<div class="d-md-none bg-dark text-white p-3 d-flex justify-content-between align-items-center">

    <h5 class="mb-0 fw-bold">
        <i class="fa-solid fa-chart-line text-primary"></i>
        Admin Panel
    </h5>

    <button class="btn btn-outline-light"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#sidebarMenu">

        <i class="fa-solid fa-bars"></i>

    </button>

</div>

    <div class="row">

        <!-- SIDEBAR -->
       <div class="col-md-2 sidebar offcanvas-md offcanvas-start text-bg-dark"
     tabindex="-1"
     id="sidebarMenu">

            <h5 class="px-3 mb-4 fw-bold">
                <i class="fa-solid fa-chart-line text-primary"></i>
                Admin Panel
            </h5>

            <a href="dashboard.php" >
                <i class="fa-solid fa-gauge me-2"></i>
                Dashboard
            </a>

            <a href="kelola_aset.php" class="active">
                <i class="fa-solid fa-boxes-stacked me-2"></i>
                Kelola Investasi
            </a>

            <a href="data_peserta.php">
                <i class="fa-solid fa-users me-2"></i>
                Data Peserta
            </a>

            <a href="leaderboard.php">
                <i class="fa-solid fa-trophy me-2"></i>
                Leaderboard
            </a>
<div class="mt-5 px-3">

    <a href="#"
       onclick="confirmLogout()"
       class="btn btn-danger btn-sm w-100 fw-bold">

        <i class="fa-solid fa-right-from-bracket me-1"></i>
        Keluar

    </a>

</div>

        </div>

        <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold text-dark">Manajemen Instrumen Investasi</h3>
                <button class="btn btn-primary px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="fa-solid fa-plus me-2"></i> Tambah Aset Baru
                </button>
            </div>

            <?php if($msg != "" || isset($_GET['msg_del'])): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4">
                    <i class="fa-solid fa-circle-check me-2"></i> <?php echo ($msg != "") ? $msg : "Aset berhasil dihapus!"; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if($error_msg != ""): ?>
                <div class="alert alert-danger border-0 shadow-sm mb-4">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Aset</th>
                                    <th>Detail</th>
                                    <th>Periode 1</th>
                                    <th>Periode 2</th>
                                    <th>Periode 3</th>
                                    <th class="text-center pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_assoc($assets)): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <img src="../assets/img/investasi/<?php echo $row['gambar']; ?>" alt="img" width="50" height="50" onerror="this.src='https://via.placeholder.com/50'" class="me-3">
                                            <div>
                                                <div class="fw-bold"><?php echo $row['nama_aset']; ?></div>
                                                <small class="text-muted"><?php echo $row['kategori']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary mb-1"><?php echo ucfirst($row['tipe_simulasi']); ?></span><br>
                                        <span class="badge bg-light text-dark border"><?php echo $row['satuan']; ?></span> (x<?php echo $row['multiplier']; ?>)
                                    </td>
                                    
                                    <?php 
                                    // Helper visual agar formatnya sesuai dengan jenis aset
                                    $is_persen = ($row['tipe_simulasi'] == 'persentase');
                                    
                                    $v1 = $is_persen ? "-" : "Rp " . number_format($row['value_p1'],0,',','.');
                                    $v2 = $is_persen ? "-" : "Rp " . number_format($row['value_p2'],0,',','.');
                                    $v3 = $is_persen ? "-" : "Rp " . number_format($row['value_p3'],0,',','.');
                                    
                                    $l1 = $is_persen ? $row['laba_p1'] . "%" : "Rp " . number_format($row['laba_p1'],0,',','.');
                                    $l2 = $is_persen ? $row['laba_p2'] . "%" : "Rp " . number_format($row['laba_p2'],0,',','.');
                                    $l3 = $is_persen ? $row['laba_p3'] . "%" : "Rp " . number_format($row['laba_p3'],0,',','.');
                                    ?>
                                    
                                    <td>
                                        <div class="data-box mb-1"><i class="fa-solid fa-tag text-secondary me-1"></i> <?php echo $v1; ?></div>
                                        <div class="data-box"><i class="fa-solid fa-coins text-success me-1"></i> <?php echo $l1; ?></div>
                                    </td>
                                    <td>
                                        <div class="data-box mb-1"><i class="fa-solid fa-tag text-secondary me-1"></i> <?php echo $v2; ?></div>
                                        <div class="data-box"><i class="fa-solid fa-coins text-success me-1"></i> <?php echo $l2; ?></div>
                                    </td>
                                    <td>
                                        <div class="data-box mb-1"><i class="fa-solid fa-tag text-secondary me-1"></i> <?php echo $v3; ?></div>
                                        <div class="data-box"><i class="fa-solid fa-coins text-success me-1"></i> <?php echo $l3; ?></div>
                                    </td>
                                    <td class="text-center pe-4">
                                        <button class="btn btn-sm btn-light text-warning shadow-sm mb-1" onclick='bukaEdit(<?php echo json_encode($row); ?>)'>
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <br>
                                        <a href="?hapus=<?php echo $row['id']; ?>" class="btn btn-sm btn-light text-danger shadow-sm" onclick="return confirm('Hapus aset ini?')">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content shadow-lg">
      <div class="modal-header px-4 py-3 border-0 bg-light">
        <h5 class="modal-title fw-bold">Tambah Aset Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="kelola_aset.php" method="POST" enctype="multipart/form-data">
          <div class="modal-body px-4">
              <div class="row g-3 mb-4">
                  <div class="col-md-5"><label class="small fw-bold">Nama Aset</label><input type="text" name="nama_aset" class="form-control" required></div>
                  <div class="col-md-3">
    <label class="small fw-bold">
        Kelompok Aset
    </label>

    <input
        type="text"
        name="group_name"
        class="form-control"
        placeholder="Contoh: Showroom"
    >
</div>
                  <div class="col-md-2"><label class="small fw-bold">Kategori</label><select name="kategori" class="form-select"><option value="Paper">Paper</option><option value="Commodity">Commodity</option><option value="Real">Real</option></select></div>
                  <div class="col-md-2"><label class="small fw-bold">Tipe Simulasi</label><select name="tipe_simulasi" class="form-select"><option value="market">Market</option><option value="persentase">Persentase</option><option value="bisnis">Bisnis</option><option value="properti">Properti</option><option value="edukasi">Edukasi</option><option value="proteksi">Proteksi</option></select></div>
                  <div class="col-md-2"><label class="small fw-bold">Satuan</label><input type="text" name="satuan" class="form-control" required></div>
                  <div class="col-md-1"><label class="small fw-bold">Multiplier</label><input type="number" name="multiplier" class="form-control" value="1" required></div>
                  <div class="col-md-12"><label class="small fw-bold">Gambar Thumbnail</label><input type="file" name="gambar" class="form-control"></div>
              </div>

              <div class="row g-3 mb-3 pb-3 border-bottom">
                  <h6 class="fw-bold text-primary mb-0">Periode 1</h6>
                  <div class="col-md-6"><label class="small">Nilai Modal / Harga Beli</label><input type="number" step="0.01" name="value_p1" class="form-control" value="0" required></div>
                  <div class="col-md-6"><label class="small">Laba / Imbal Hasil (Rp atau %)</label><input type="number" step="0.01" name="laba_p1" class="form-control" value="0" required></div>
              </div>

              <div class="row g-3 mb-3 pb-3 border-bottom">
                  <h6 class="fw-bold text-success mb-0">Periode 2</h6>
                  <div class="col-md-6"><label class="small">Nilai Harga Jual / Valuasi Baru</label><input type="number" step="0.01" name="value_p2" class="form-control" value="0" required></div>
                  <div class="col-md-6"><label class="small">Laba / Imbal Hasil (Rp atau %)</label><input type="number" step="0.01" name="laba_p2" class="form-control" value="0" required></div>
              </div>

              <div class="row g-3 mb-4">
                  <h6 class="fw-bold text-danger mb-0">Periode 3</h6>
                  <div class="col-md-6"><label class="small">Nilai Harga Jual / Valuasi Baru</label><input type="number" step="0.01" name="value_p3" class="form-control" value="0" required></div>
                  <div class="col-md-6"><label class="small">Laba / Imbal Hasil (Rp atau %)</label><input type="number" step="0.01" name="laba_p3" class="form-control" value="0" required></div>
              </div>
                  
              <div class="row g-3">
<div class="col-12 mb-4">
    <label class="small fw-bold text-primary">
        Deskripsi Periode 1
    </label>

    <textarea
        id="summernote_p1"
        name="deskripsi_p1"></textarea>
</div>

<div class="col-12 mb-4">
    <label class="small fw-bold text-success">
        Deskripsi Periode 2
    </label>

    <textarea
        id="summernote_p2"
        name="deskripsi_p2"></textarea>
</div>

<div class="col-12">
    <label class="small fw-bold text-danger">
        Deskripsi Periode 3
    </label>

    <textarea
        id="summernote_p3"
        name="deskripsi_p3"></textarea>
</div>              </div>
          </div>
          <div class="modal-footer px-4 border-0">
            <button type="submit" name="tambah_aset" class="btn btn-primary px-5 shadow">Simpan Aset</button>
          </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content shadow-lg">
      <div class="modal-header px-4 py-3 border-0 bg-light">
        <h5 class="modal-title fw-bold text-warning">Perbarui Data Aset</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="kelola_aset.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="id_aset" id="edit_id">
          <div class="modal-body px-4">
              <div class="row g-3 mb-4">
                  <div class="col-md-5"><label class="small fw-bold">Nama Aset</label><input type="text" name="nama_aset" id="edit_nama" class="form-control" required></div>
                  <div class="col-md-3">
    <label class="small fw-bold">
        Kelompok Aset
    </label>

    <input
        type="text"
        name="group_name"
        id="edit_group_name"
        class="form-control"
        placeholder="Contoh: Showroom"
    >
</div>
                  <div class="col-md-2"><label class="small fw-bold">Kategori</label><select name="kategori" id="edit_kategori" class="form-select"><option value="Paper">Paper</option><option value="Commodity">Commodity</option><option value="Real">Real</option></select></div>
                  <div class="col-md-2"><label class="small fw-bold">Tipe Simulasi</label><select name="tipe_simulasi" id="edit_tipe_simulasi" class="form-select"><option value="market">Market</option><option value="persentase">Persentase</option><option value="bisnis">Bisnis</option><option value="properti">Properti</option><option value="edukasi">Edukasi</option><option value="proteksi">Proteksi</option></select></div>
                  <div class="col-md-2"><label class="small fw-bold">Satuan</label><input type="text" name="satuan" id="edit_satuan" class="form-control" required></div>
                  <div class="col-md-1"><label class="small fw-bold">Multiplier</label><input type="number" name="multiplier" id="edit_multiplier" class="form-control" required></div>
                  <div class="col-md-12"><label class="small fw-bold">Ganti Gambar (Kosongkan jika tidak diubah)</label><input type="file" name="gambar" class="form-control"></div>
              </div>

              <div class="row g-3 mb-3 pb-3 border-bottom">
                  <h6 class="fw-bold text-primary mb-0">Periode 1</h6>
                  <div class="col-md-6"><label class="small">Nilai Modal / Harga Beli</label><input type="number" step="0.01" name="value_p1" id="edit_v1" class="form-control" required></div>
                  <div class="col-md-6"><label class="small">Laba / Imbal Hasil (Rp atau %)</label><input type="number" step="0.01" name="laba_p1" id="edit_l1" class="form-control" required></div>
              </div>

              <div class="row g-3 mb-3 pb-3 border-bottom">
                  <h6 class="fw-bold text-success mb-0">Periode 2</h6>
                  <div class="col-md-6"><label class="small">Nilai Harga Jual / Valuasi Baru</label><input type="number" step="0.01" name="value_p2" id="edit_v2" class="form-control" required></div>
                  <div class="col-md-6"><label class="small">Laba / Imbal Hasil (Rp atau %)</label><input type="number" step="0.01" name="laba_p2" id="edit_l2" class="form-control" required></div>
              </div>

              <div class="row g-3 mb-4">
                  <h6 class="fw-bold text-danger mb-0">Periode 3</h6>
                  <div class="col-md-6"><label class="small">Nilai Harga Jual / Valuasi Baru</label><input type="number" step="0.01" name="value_p3" id="edit_v3" class="form-control" required></div>
                  <div class="col-md-6"><label class="small">Laba / Imbal Hasil (Rp atau %)</label><input type="number" step="0.01" name="laba_p3" id="edit_l3" class="form-control" required></div>
              </div>
                  
              <div class="row g-3">
<div class="col-12 mb-4">
    <label class="small fw-bold text-primary">
        Deskripsi Periode 1
    </label>

    <textarea
        id="edit_summernote_p1"
        name="deskripsi_p1"></textarea>
</div>

<div class="col-12 mb-4">
    <label class="small fw-bold text-success">
        Deskripsi Periode 2
    </label>

    <textarea
        id="edit_summernote_p2"
        name="deskripsi_p2"></textarea>
</div>

<div class="col-12">
    <label class="small fw-bold text-danger">
        Deskripsi Periode 3
    </label>

    <textarea
        id="edit_summernote_p3"
        name="deskripsi_p3"></textarea>
</div>              </div>
          </div>
          <div class="modal-footer px-4 border-0">
            <button type="submit" name="edit_aset" class="btn btn-warning px-5 shadow">Update Data</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

<script>
$(document).ready(function() {

    $('#summernote_p1, #summernote_p2, #summernote_p3, #edit_summernote_p1, #edit_summernote_p2, #edit_summernote_p3').summernote({

        placeholder: 'Isi detail aset di sini...',
        height: 300,

        toolbar: [
          ['style', ['bold', 'underline', 'clear']],
          ['color', ['color']],
          ['para', ['ul', 'ol', 'paragraph']],
          ['table', ['table']],
          ['insert', ['link', 'picture']],
          ['view', ['fullscreen', 'codeview']]
        ],

        callbacks: {

            onImageUpload: function(files) {

                for(let i = 0; i < files.length; i++) {

                    uploadImage(files[i], this);

                }

            }

        }

    });

});

function uploadImage(file, editor) {

    let data = new FormData();

    data.append("file", file);

    $.ajax({

        url: 'upload_summernote.php',
        cache: false,
        contentType: false,
        processData: false,
        data: data,
        type: "POST",

        success: function(url) {

            $(editor).summernote('insertImage', url);

        },

        error: function() {

            alert('Upload gambar gagal');

        }

    });

}

function bukaEdit(data) {
    $('#edit_id').val(data.id);
    $('#edit_nama').val(data.nama_aset);
    $('#edit_group_name').val(data.group_name);
    $('#edit_kategori').val(data.kategori);
    $('#edit_tipe_simulasi').val(data.tipe_simulasi);
    $('#edit_satuan').val(data.satuan);
    $('#edit_multiplier').val(data.multiplier);
    
    // Assign nilai value
    $('#edit_v1').val(data.value_p1);
    $('#edit_v2').val(data.value_p2);
    $('#edit_v3').val(data.value_p3);
    
    // Assign nilai laba
    $('#edit_l1').val(data.laba_p1);
    $('#edit_l2').val(data.laba_p2);
    $('#edit_l3').val(data.laba_p3);
    
    // Sinkronisasi isi Summernote Edit
    $('#edit_summernote_p1').summernote('code', data.deskripsi_p1 ?? '');
    $('#edit_summernote_p2').summernote('code', data.deskripsi_p2 ?? '');
    $('#edit_summernote_p3').summernote('code', data.deskripsi_p3 ?? '');
    
    var editModal = new bootstrap.Modal(document.getElementById('modalEdit'));
    editModal.show();
}
</script>
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

        reverseButtons: true,

        background: '#ffffff',

        color: '#212529'

    }).then((result) => {

        if(result.isConfirmed) {

            Swal.fire({

                title: 'Sedang Logout...',

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
</script>
</body>
</html>