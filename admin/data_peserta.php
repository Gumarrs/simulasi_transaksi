<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// 1. Proses Import CSV
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['import_csv'])) {
    $fileMimes = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain');
    if (!empty($_FILES['file_csv']['name']) && in_array($_FILES['file_csv']['type'], $fileMimes)) {
        if (is_uploaded_file($_FILES['file_csv']['tmp_name'])) {
            $csvFile = fopen($_FILES['file_csv']['tmp_name'], 'r');
            fgetcsv($csvFile); // Skip header
            $sukses = 0; $gagal = 0;
            while (($data = fgetcsv($csvFile, 10000, ",")) !== FALSE) {
                $username = mysqli_real_escape_string($conn, $data[0]);
                $password = mysqli_real_escape_string($conn, $data[1]);
                $nama_lengkap = mysqli_real_escape_string($conn, $data[2]);
                $cek = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
                if (mysqli_num_rows($cek) == 0) {
                    if(mysqli_query($conn, "INSERT INTO users (username, password, nama_lengkap, role) VALUES ('$username', '$password', '$nama_lengkap', 'peserta')")) { $sukses++; } else { $gagal++; }
                } else { $gagal++; }
            }
            fclose($csvFile);
            $msg = "Import selesai! Berhasil: $sukses akun. Gagal/Duplikat: $gagal akun.";
        }
    } else {
        $error_msg = "Format file tidak valid. Harap unggah file .CSV";
    }
}

// 2. Proses Tambah Peserta Manual
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tambah_manual'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);

    $cek = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($cek) == 0) {
        mysqli_query($conn, "INSERT INTO users (username, password, nama_lengkap, role) VALUES ('$username', '$password', '$nama_lengkap', 'peserta')");
        $msg = "Peserta '$nama_lengkap' berhasil ditambahkan secara manual.";
    } else {
        $error_msg = "Gagal! Username '$username' sudah digunakan.";
    }
}

// 3. Proses Edit Peserta (Nama & Password)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_peserta'])) {
    $id_user = (int)$_POST['id_user'];
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Jika password diisi, update password juga. Jika kosong, hanya update nama.
    if (!empty($password)) {
        mysqli_query($conn, "UPDATE users SET nama_lengkap = '$nama_lengkap', password = '$password' WHERE id = '$id_user'");
    } else {
        mysqli_query($conn, "UPDATE users SET nama_lengkap = '$nama_lengkap' WHERE id = '$id_user'");
    }
    $msg = "Data peserta berhasil diperbarui.";
}

// 4. Proses Hapus Peserta
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['hapus_peserta'])) {
    $id_user = (int)$_POST['id_user'];
    mysqli_query($conn, "DELETE FROM users WHERE id = '$id_user'");
    $msg = "Peserta berhasil dihapus dari sistem.";
}

// Ambil data seluruh peserta
$query_peserta = mysqli_query($conn, "SELECT * FROM users WHERE role = 'peserta' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Peserta - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fc; }
        .sidebar { min-height: 100vh; background-color: #212529; color: white; padding-top: 20px;}
        .sidebar a { color: #adb5bd; text-decoration: none; padding: 10px 20px; display: block; }
        .sidebar a:hover, .sidebar a.active { color: #fff; background-color: #343a40; border-left: 4px solid #0d6efd;}
    </style>
</head>
<body>
<div class="container-fluid">

    <!-- Navbar Mobile -->
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
          <div class="col-md-2 sidebar offcanvas-md offcanvas-start text-bg-dark"
             tabindex="-1"
             id="sidebarMenu">
            <h5 class="px-3 mb-4 fw-bold"><i class="fa-solid fa-chart-line text-primary"></i> Admin Panel</h5>
            <a href="dashboard.php"><i class="fa-solid fa-gauge me-2"></i> Dashboard</a>
            <a href="kelola_aset.php"><i class="fa-solid fa-boxes-stacked me-2"></i> Kelola Investasi</a>
            <a href="data_peserta.php" class="active"><i class="fa-solid fa-users me-2"></i> Data Peserta</a>
            <a href="leaderboard.php"><i class="fa-solid fa-trophy me-2"></i> Leaderboard</a>
            <div class="mt-5 px-3">
    <a href="#"
       onclick="confirmLogout()"
       class="btn btn-danger btn-sm w-100 fw-bold">

        <i class="fa-solid fa-right-from-bracket me-1"></i>
        Keluar

    </a>            </div>
        </div>

        <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold">Manajemen Akun Peserta</h3>
                <div>
                    <button class="btn btn-primary fw-bold me-2" data-bs-toggle="modal" data-bs-target="#modalTambahManual">
                        <i class="fa-solid fa-user-plus"></i> Tambah Manual
                    </button>
                    <button class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#modalImport">
                        <i class="fa-solid fa-file-csv"></i> Import CSV
                    </button>
                </div>
            </div>

            <?php if(isset($msg)): ?>
                <div class="alert alert-success alert-dismissible fade show"><i class="fa-solid fa-check"></i> <?php echo $msg; ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if(isset($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error_msg; ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>No</th>
                                    <th>ID Peserta</th>
                                    <th>Nama Lengkap</th>
                                    <th>Status Assessment</th>
                                    <th>Saldo Aktif</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; while($row = mysqli_fetch_assoc($query_peserta)): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td class="fw-bold text-primary"><?php echo $row['username']; ?></td>
                                    <td><?php echo $row['nama_lengkap']; ?></td>
                                    <td>
                                        <?php if($row['is_assessment_done'] == 1): ?>
                                            <span class="badge bg-success">Selesai</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Belum</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold">Rp <?php echo number_format($row['balance'],0,',','.'); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning text-dark me-1" title="Edit Peserta" 
                                                onclick="bukaModalEdit(<?php echo $row['id']; ?>, '<?php echo $row['username']; ?>', '<?php echo $row['nama_lengkap']; ?>')">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        
<button class="btn btn-sm btn-danger"
        title="Hapus Peserta"
        data-bs-toggle="modal"
        data-bs-target="#hapusModal<?php echo $row['id']; ?>">

    <i class="fa-solid fa-trash"></i>

</button>

<!-- Modal Hapus -->
<div class="modal fade"
     id="hapusModal<?php echo $row['id']; ?>"
     tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content border-0 shadow">

            <div class="modal-header bg-danger text-white">

                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>
                    Konfirmasi Hapus
                </h5>

                <button type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body">

                <p class="mb-2">
                    Yakin ingin menghapus peserta:
                </p>

                <h5 class="fw-bold text-danger">
                    <?php echo $row['nama_lengkap']; ?>
                </h5>

                <small class="text-muted">
                    Semua data transaksi peserta juga akan ikut terhapus.
                </small>

            </div>

            <div class="modal-footer">

                <button type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">

                    Batal

                </button>

                <form action="data_peserta.php"
                      method="POST">

                    <input type="hidden"
                           name="id_user"
                           value="<?php echo $row['id']; ?>">

                    <button type="submit"
                            name="hapus_peserta"
                            class="btn btn-danger fw-bold">

                        <i class="fa-solid fa-trash me-1"></i>
                        Ya, Hapus

                    </button>

                </form>

            </div>

        </div>

    </div>

</div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if(mysqli_num_rows($query_peserta) == 0): ?>
                                    <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada data peserta.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalImport" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Import Data Peserta (CSV)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="data_peserta.php" method="POST" enctype="multipart/form-data">
          <div class="modal-body">
              <div class="alert alert-info small border-0">
                  Format: <strong>Username | Password | Nama Lengkap</strong> (Tanpa Header)
              </div>
              <input type="file" name="file_csv" class="form-control mb-2" accept=".csv" required>
          </div>
          <div class="modal-footer border-0">
            <button type="submit" name="import_csv" class="btn btn-success w-100 fw-bold">Mulai Import</button>
          </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalTambahManual" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Tambah Peserta Manual</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="data_peserta.php" method="POST">
          <div class="modal-body">
              <div class="mb-3">
                  <label class="form-label fw-bold small">ID Peserta (Username)</label>
                  <input type="text" name="username" class="form-control" required>
              </div>
              <div class="mb-3">
                  <label class="form-label fw-bold small">Nama Lengkap</label>
                  <input type="text" name="nama_lengkap" class="form-control" required>
              </div>
              <div class="mb-3">
                  <label class="form-label fw-bold small">Password Default</label>
                  <input type="text" name="password" class="form-control" value="123456" required>
              </div>
          </div>
          <div class="modal-footer border-0">
            <button type="submit" name="tambah_manual" class="btn btn-primary w-100 fw-bold">Simpan Peserta</button>
          </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Edit Data Peserta</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="data_peserta.php" method="POST">
          <div class="modal-body">
              <input type="hidden" name="id_user" id="editIdUser">
              <div class="mb-3">
                  <label class="form-label fw-bold small">ID Peserta (Username)</label>
                  <input type="text" id="editUsername" class="form-control bg-light" readonly>
                  <small class="text-muted">Username tidak dapat diubah.</small>
              </div>
              <div class="mb-3">
                  <label class="form-label fw-bold small">Nama Lengkap</label>
                  <input type="text" name="nama_lengkap" id="editNama" class="form-control" required>
              </div>
              <div class="mb-3">
                  <label class="form-label fw-bold small">Reset Password (Opsional)</label>
                  <input type="text" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah password">
              </div>
          </div>
          <div class="modal-footer border-0">
            <button type="submit" name="edit_peserta" class="btn btn-warning w-100 fw-bold text-dark">Update Data</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Fungsi untuk memanggil modal edit dengan membawa data baris tabel
function bukaModalEdit(id, username, nama) {
    document.getElementById('editIdUser').value = id;
    document.getElementById('editUsername').value = username;
    document.getElementById('editNama').value = nama;
    
    var myModal = new bootstrap.Modal(document.getElementById('modalEdit'));
    myModal.show();
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
</body>
</html>