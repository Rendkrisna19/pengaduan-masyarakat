<?php

session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Logika Proses Create, Update, Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'create') {
        $nomor_rw = $conn->real_escape_string($_POST['nomor_rw']);
        $nama_ketua_rw = $conn->real_escape_string($_POST['nama_ketua_rw']);
        if(!empty($nomor_rw)) {
            $stmt = $conn->prepare("INSERT INTO rw (nomor_rw, nama_ketua_rw) VALUES (?, ?)");
            $stmt->bind_param("ss", $nomor_rw, $nama_ketua_rw);
            if ($stmt->execute()) $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Data RW berhasil ditambahkan.'];
            else $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal: ' . $stmt->error];
            $stmt->close();
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Nomor RW tidak boleh kosong.'];
        }
    }
    if (isset($_POST['action']) && $_POST['action'] == 'update') {
        $id_rw = (int)$_POST['id_rw'];
        $nomor_rw = $conn->real_escape_string($_POST['nomor_rw']);
        $nama_ketua_rw = $conn->real_escape_string($_POST['nama_ketua_rw']);
        if (!empty($nomor_rw) && $id_rw > 0) {
            $stmt = $conn->prepare("UPDATE rw SET nomor_rw = ?, nama_ketua_rw = ? WHERE id_rw = ?");
            $stmt->bind_param("ssi", $nomor_rw, $nama_ketua_rw, $id_rw);
            if ($stmt->execute()) $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Data RW berhasil diperbarui.'];
            else $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal: ' . $stmt->error];
            $stmt->close();
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Data untuk update tidak valid.'];
        }
    }
    header("Location: master_rw.php");
    exit();
}
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_rw = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM rw WHERE id_rw = ?");
    $stmt->bind_param("i", $id_rw);
    if ($stmt->execute()) $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Data RW berhasil dihapus.'];
    else $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal menghapus RW. Pastikan tidak ada RT yang terhubung dengan RW ini.'];
    $stmt->close();
    header("Location: master_rw.php");
    exit();
}

$rw_list = $conn->query("SELECT * FROM rw ORDER BY nomor_rw ASC");
$flash_message = $_SESSION['flash_message'] ?? null;
if ($flash_message) unset($_SESSION['flash_message']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen RW - Admin Panel</title>
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
            <nav class="topbar">
                <button type="button" id="sidebarCollapse" class="btn"><i class="fas fa-bars"></i></button>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
            <div class="main-content">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Tambah Data RW
                                </h6>
                            </div>
                            <div class="card-body">
                                <form action="master_rw.php" method="POST">
                                    <input type="hidden" name="action" value="create">
                                    <div class="mb-3"><label for="nomor_rw" class="form-label">Nomor RW</label><input
                                            type="text" class="form-control" name="nomor_rw" required></div>
                                    <div class="mb-3"><label for="nama_ketua_rw" class="form-label">Nama Ketua
                                            RW</label><input type="text" class="form-control" name="nama_ketua_rw">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Simpan</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Daftar RW</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nomor RW</th>
                                                <th>Ketua RW</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $rw_list->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $row['id_rw']; ?></td>
                                                <td><?php echo htmlspecialchars($row['nomor_rw']); ?></td>
                                                <td><?php echo htmlspecialchars($row['nama_ketua_rw']); ?></td>
                                                <td>
                                                    <button class="btn btn-warning btn-sm edit-btn"
                                                        data-id="<?php echo $row['id_rw']; ?>"
                                                        data-nomor_rw="<?php echo htmlspecialchars($row['nomor_rw']); ?>"
                                                        data-nama_ketua_rw="<?php echo htmlspecialchars($row['nama_ketua_rw']); ?>"
                                                        data-bs-toggle="modal" data-bs-target="#editModal"><i
                                                            class="fas fa-edit"></i></button>
                                                    <button class="btn btn-danger btn-sm delete-btn"
                                                        data-id="<?php echo $row['id_rw']; ?>"
                                                        data-nama="RW <?php echo htmlspecialchars($row['nomor_rw']); ?>"><i
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
                </div>
                <div class="modal fade" id="editModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Data RW</h5><button type="button" class="btn-close"
                                    data-bs-dismiss="modal"></button>
                            </div>
                            <form action="master_rw.php" method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" id="edit_id_rw" name="id_rw">
                                    <div class="mb-3"><label for="edit_nomor_rw" class="form-label">Nomor
                                            RW</label><input type="text" class="form-control" id="edit_nomor_rw"
                                            name="nomor_rw" required></div>
                                    <div class="mb-3"><label for="edit_nama_ketua_rw" class="form-label">Nama Ketua
                                            RW</label><input type="text" class="form-control" id="edit_nama_ketua_rw"
                                            name="nama_ketua_rw"></div>
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
        const sidebarCollapse = document.getElementById('sidebarCollapse');
        if (sidebarCollapse) sidebarCollapse.addEventListener('click', () => document.getElementById('sidebar')
            .classList.toggle('active'));
        <?php if ($flash_message): ?>
        Swal.fire({
            icon: '<?php echo $flash_message["type"]; ?>',
            title: '<?php echo ($flash_message["type"] == "success" ? "Berhasil!" : "Gagal!"); ?>',
            text: '<?php echo addslashes($flash_message["message"]); ?>',
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
        <?php endif; ?>
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_id_rw').value = this.getAttribute('data-id');
                document.getElementById('edit_nomor_rw').value = this.getAttribute(
                    'data-nomor_rw');
                document.getElementById('edit_nama_ketua_rw').value = this.getAttribute(
                    'data-nama_ketua_rw');
            });
        });
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
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then(result => {
                    if (result.isConfirmed) window.location.href =
                        `master_rw.php?action=delete&id=${id}`;
                })
            });
        });
    });
    </script>
</body>

</html>