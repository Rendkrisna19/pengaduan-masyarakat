<?php

session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Logika Proses
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'create') {
        $nomor_rt = $conn->real_escape_string($_POST['nomor_rt']);
        $nama_ketua_rt = $conn->real_escape_string($_POST['nama_ketua_rt']);
        $id_rw = (int)$_POST['id_rw'];
        if (!empty($nomor_rt) && $id_rw > 0) {
            $stmt = $conn->prepare("INSERT INTO rt (nomor_rt, nama_ketua_rt, id_rw) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $nomor_rt, $nama_ketua_rt, $id_rw);
            if($stmt->execute()) $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Data RT berhasil ditambahkan.'];
            else $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal: '.$stmt->error];
            $stmt->close();
        } else {
             $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Nomor RT dan Wilayah RW wajib diisi.'];
        }
    }
    if (isset($_POST['action']) && $_POST['action'] == 'update') {
        $id_rt = (int)$_POST['id_rt'];
        $nomor_rt = $conn->real_escape_string($_POST['nomor_rt']);
        $nama_ketua_rt = $conn->real_escape_string($_POST['nama_ketua_rt']);
        $id_rw = (int)$_POST['id_rw'];
         if (!empty($nomor_rt) && $id_rw > 0 && $id_rt > 0) {
            $stmt = $conn->prepare("UPDATE rt SET nomor_rt = ?, nama_ketua_rt = ?, id_rw = ? WHERE id_rt = ?");
            $stmt->bind_param("ssii", $nomor_rt, $nama_ketua_rt, $id_rw, $id_rt);
            if($stmt->execute()) $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Data RT berhasil diperbarui.'];
            else $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal: '.$stmt->error];
            $stmt->close();
        } else {
             $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Data untuk update tidak valid.'];
        }
    }
    header("Location: master_rt.php");
    exit();
}
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_rt = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM rt WHERE id_rt = ?");
    $stmt->bind_param("i", $id_rt);
    if ($stmt->execute()) $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Data RT berhasil dihapus.'];
    else $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal menghapus RT. Pastikan tidak ada pengguna atau pengaduan yang terhubung.'];
    $stmt->close();
    header("Location: master_rt.php");
    exit();
}

$rt_list = $conn->query("SELECT rt.*, rw.nomor_rw FROM rt JOIN rw ON rt.id_rw = rw.id_rw ORDER BY rw.nomor_rw, rt.nomor_rt ASC");
$rw_options = $conn->query("SELECT * FROM rw ORDER BY nomor_rw ASC");
$flash_message = $_SESSION['flash_message'] ?? null;
if ($flash_message) unset($_SESSION['flash_message']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen RT - Admin Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
    :root {
        --primary-color: #3dc7f5;
        --sidebar-bg: #fff;
        --content-bg: #f4f7fc;
        --font-family: 'Poppins', sans-serif;
    }

    body {
        font-family: var(--font-family);
        background-color: var(--content-bg);
    }

    .wrapper {
        display: flex;
        width: 100%;
        align-items: stretch;
        min-height: 100vh;
        /* FIX: Ini membuat sidebar selalu tinggi */
    }

    #sidebar {
        min-width: 250px;
        max-width: 250px;
        background: var(--sidebar-bg);
        transition: all 0.3s ease-in-out;
        box-shadow: 2px 0 15px rgba(0, 0, 0, 0.05);
    }

    #sidebar.active {
        margin-left: -250px;
    }

    #content {
        width: 100%;
        transition: all 0.3s ease-in-out;
    }

    #sidebar .sidebar-header {
        padding: 20px;
        border-bottom: 1px solid #eee;
        color: var(--primary-color);
        font-weight: 600;
    }

    #sidebar ul li a {
        padding: 10px 20px;
        font-size: 1.1em;
        display: block;
        color: #5a5a5a;
        border-radius: 5px;
        margin: 2px 10px;
        transition: all 0.2s;
    }

    #sidebar ul li a:hover {
        color: var(--primary-color);
        background: #f0f0f0;
        text-decoration: none;
    }

    #sidebar ul li.active>a {
        color: #fff;
        background: var(--primary-color);
    }

    .topbar {
        padding: 15px 30px;
        background: #fff;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    #sidebarCollapse {
        border: none;
        background: transparent;
        font-size: 1.5rem;
        color: #333;
    }

    .main-content {
        padding: 30px;
    }

    @media (max-width: 768px) {
        #sidebar {
            margin-left: -250px;
        }

        #sidebar.active {
            margin-left: 0;
        }
    }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        <div id="content">
            <nav class="topbar"></nav>
            <div class="main-content">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Tambah Data RT
                                </h6>
                            </div>
                            <div class="card-body">
                                <form action="master_rt.php" method="POST">
                                    <input type="hidden" name="action" value="create">
                                    <div class="mb-3"><label for="nomor_rt" class="form-label">Nomor RT</label><input
                                            type="text" class="form-control" name="nomor_rt" required></div>
                                    <div class="mb-3"><label for="nama_ketua_rt" class="form-label">Nama Ketua
                                            RT</label><input type="text" class="form-control" name="nama_ketua_rt">
                                    </div>
                                    <div class="mb-3">
                                        <label for="id_rw" class="form-label">Masuk ke Wilayah RW</label>
                                        <select class="form-select" name="id_rw" required>
                                            <option value="">-- Pilih RW --</option>
                                            <?php mysqli_data_seek($rw_options, 0); while($rw = $rw_options->fetch_assoc()): ?>
                                            <option value="<?php echo $rw['id_rw']; ?>">RW
                                                <?php echo htmlspecialchars($rw['nomor_rw']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Simpan</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Daftar RT</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nomor RT</th>
                                            <th>Ketua RT</th>
                                            <th>Wilayah RW</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $rt_list->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['id_rt']; ?></td>
                                            <td><?php echo htmlspecialchars($row['nomor_rt']); ?></td>
                                            <td><?php echo htmlspecialchars($row['nama_ketua_rt']); ?></td>
                                            <td><?php echo htmlspecialchars($row['nomor_rw']); ?></td>
                                            <td>
                                                <button class="btn btn-warning btn-sm edit-btn"
                                                    data-id="<?php echo $row['id_rt']; ?>"
                                                    data-nomor_rt="<?php echo htmlspecialchars($row['nomor_rt']); ?>"
                                                    data-nama_ketua_rt="<?php echo htmlspecialchars($row['nama_ketua_rt']); ?>"
                                                    data-id_rw="<?php echo $row['id_rw']; ?>" data-bs-toggle="modal"
                                                    data-bs-target="#editModal"><i class="fas fa-edit"></i></button>
                                                <button class="btn btn-danger btn-sm delete-btn"
                                                    data-id="<?php echo $row['id_rt']; ?>"
                                                    data-nama="RT <?php echo htmlspecialchars($row['nomor_rt']); ?>/RW <?php echo htmlspecialchars($row['nomor_rw']); ?>"><i
                                                        class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="editModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Data RT</h5><button type="button" class="btn-close"
                                    data-bs-dismiss="modal"></button>
                            </div>
                            <form action="master_rt.php" method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" id="edit_id_rt" name="id_rt">
                                    <div class="mb-3"><label for="edit_nomor_rt" class="form-label">Nomor
                                            RT</label><input type="text" class="form-control" id="edit_nomor_rt"
                                            name="nomor_rt" required></div>
                                    <div class="mb-3"><label for="edit_nama_ketua_rt" class="form-label">Nama Ketua
                                            RT</label><input type="text" class="form-control" id="edit_nama_ketua_rt"
                                            name="nama_ketua_rt"></div>
                                    <div class="mb-3">
                                        <label for="edit_id_rw" class="form-label">Masuk ke Wilayah RW</label>
                                        <select class="form-select" id="edit_id_rw" name="id_rw" required>
                                            <option value="">-- Pilih RW --</option>
                                            <?php mysqli_data_seek($rw_options, 0); while($rw = $rw_options->fetch_assoc()): ?>
                                            <option value="<?php echo $rw['id_rw']; ?>">RW
                                                <?php echo htmlspecialchars($rw['nomor_rw']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer"><button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Batal</button><button type="submit"
                                        class="btn btn-primary">Simpan Perubahan</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Script Umum: Sidebar Toggle, Notifikasi Flash Message

        // Script Spesifik Halaman: Edit RT
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_id_rt').value = this.getAttribute('data-id');
                document.getElementById('edit_nomor_rt').value = this.getAttribute(
                    'data-nomor_rt');
                document.getElementById('edit_nama_ketua_rt').value = this.getAttribute(
                    'data-nama_ketua_rt');
                document.getElementById('edit_id_rw').value = this.getAttribute('data-id_rw');
            });
        });
        // Script Spesifik Halaman: Delete RT
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const nama = this.getAttribute('data-nama');
                Swal.fire({
                    title: 'Anda Yakin?',
                    text: `Data "${nama}" akan dihapus!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonText: 'Batal',
                    confirmButtonText: 'Ya, Hapus!'
                }).then(result => {
                    if (result.isConfirmed) window.location.href =
                        `master_rt.php?action=delete&id=${id}`;
                })
            });
        });
    });
    </script>
</body>

</html>