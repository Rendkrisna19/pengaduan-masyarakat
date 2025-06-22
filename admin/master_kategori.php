<?php


session_start();
require_once __DIR__ . '/../config/database.php';

// Cek Keamanan Awal - Hanya Admin yang boleh akses
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Logika untuk memproses form (Create, Update, Delete)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'create') {
        $nama_kategori = $conn->real_escape_string($_POST['nama_kategori']);
        if (!empty($nama_kategori)) {
            $stmt = $conn->prepare("INSERT INTO kategori_pengaduan (nama_kategori) VALUES (?)");
            $stmt->bind_param("s", $nama_kategori);
            if ($stmt->execute()) $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Kategori baru berhasil ditambahkan.'];
            else $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal: ' . $stmt->error];
            $stmt->close();
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Nama kategori tidak boleh kosong.'];
        }
    }
    if (isset($_POST['action']) && $_POST['action'] == 'update') {
        $id_kategori = (int)$_POST['id_kategori'];
        $nama_kategori = $conn->real_escape_string($_POST['nama_kategori']);
        if (!empty($nama_kategori) && $id_kategori > 0) {
            $stmt = $conn->prepare("UPDATE kategori_pengaduan SET nama_kategori = ? WHERE id_kategori = ?");
            $stmt->bind_param("si", $nama_kategori, $id_kategori);
            if ($stmt->execute()) $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Kategori berhasil diperbarui.'];
            else $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal: ' . $stmt->error];
            $stmt->close();
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Data untuk update tidak valid.'];
        }
    }
    header("Location: master_kategori.php");
    exit();
}
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_kategori = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM kategori_pengaduan WHERE id_kategori = ?");
    $stmt->bind_param("i", $id_kategori);
    if ($stmt->execute()) $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Kategori berhasil dihapus.'];
    else $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal menghapus. Kategori mungkin sedang digunakan.'];
    $stmt->close();
    header("Location: master_kategori.php");
    exit();
}

// Ambil data untuk ditampilkan di tabel
$kategori_list = $conn->query("SELECT * FROM kategori_pengaduan ORDER BY nama_kategori ASC");

// Ambil flash message jika ada
$flash_message = $_SESSION['flash_message'] ?? null;
if ($flash_message) {
    unset($_SESSION['flash_message']);
}



?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kategori - Admin Panel</title>
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
        <nav id="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-bullhorn"></i> Panel Lapor</h3>
            </div>
            <ul class="list-unstyled components">
                <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
                <li class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>"><a href="index.php"><i
                            class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                <li class="<?php echo ($currentPage == 'kelola_pengaduan.php') ? 'active' : ''; ?>"><a
                        href="kelola_pengaduan.php"><i class="fas fa-file-alt me-2"></i>Kelola Pengaduan</a></li>
                <li class="<?php echo ($currentPage == 'laporan.php') ? 'active' : ''; ?>"><a href="laporan.php"><i
                            class="fas fa-chart-bar me-2"></i>Laporan & Statistik</a></li>
                <li>
                    <?php $isMasterPage = in_array($currentPage, ['master_kategori.php', 'master_rw.php', 'master_rt.php']); ?>
                    <a href="#masterSubmenu" data-bs-toggle="collapse"
                        aria-expanded="<?php echo $isMasterPage ? 'true' : 'false'; ?>" class="dropdown-toggle"><i
                            class="fas fa-database me-2"></i>Data Master</a>
                    <ul class="collapse list-unstyled <?php echo $isMasterPage ? 'show' : ''; ?>" id="masterSubmenu">
                        <li class="<?php echo ($currentPage == 'master_kategori.php') ? 'active' : ''; ?>"><a
                                href="master_kategori.php">Kategori</a></li>
                        <li class="<?php echo ($currentPage == 'master_rw.php') ? 'active' : ''; ?>"><a
                                href="master_rw.php">Data RW</a></li>
                        <li class="<?php echo ($currentPage == 'master_rt.php') ? 'active' : ''; ?>"><a
                                href="master_rt.php">Data RT</a></li>
                    </ul>
                </li>
                <li>
                    <?php $isUserManagementPage = in_array($currentPage, ['daftar_pengguna.php', 'create_pengguna.php']); ?>
                    <a href="#userSubmenu" data-bs-toggle="collapse"
                        aria-expanded="<?php echo $isUserManagementPage ? 'true' : 'false'; ?>"
                        class="dropdown-toggle"><i class="fas fa-users me-2"></i>Manajemen Pengguna</a>
                    <ul class="collapse list-unstyled <?php echo $isUserManagementPage ? 'show' : ''; ?>"
                        id="userSubmenu">
                        <li class="<?php echo ($currentPage == 'daftar_pengguna.php') ? 'active' : ''; ?>"><a
                                href="daftar_pengguna.php">Daftar Pengguna</a></li>
                        <li class="<?php echo ($currentPage == 'create_pengguna.php') ? 'active' : ''; ?>"><a
                                href="create_pengguna.php">Tambah Pengguna Baru</a></li>
                    </ul>
                </li>
            </ul>
        </nav>

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
                                <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Tambah Kategori
                                    Baru</h6>
                            </div>
                            <div class="card-body">
                                <form action="master_kategori.php" method="POST">
                                    <input type="hidden" name="action" value="create">
                                    <div class="mb-3">
                                        <label for="nama_kategori" class="form-label">Nama Kategori</label>
                                        <input type="text" class="form-control" id="nama_kategori" name="nama_kategori"
                                            required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Simpan</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Daftar Kategori
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nama Kategori</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $kategori_list->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $row['id_kategori']; ?></td>
                                                <td><?php echo htmlspecialchars($row['nama_kategori']); ?></td>
                                                <td>
                                                    <button class="btn btn-warning btn-sm edit-btn"
                                                        data-id="<?php echo $row['id_kategori']; ?>"
                                                        data-nama="<?php echo htmlspecialchars($row['nama_kategori']); ?>"
                                                        data-bs-toggle="modal" data-bs-target="#editModal"><i
                                                            class="fas fa-edit"></i></button>
                                                    <button class="btn btn-danger btn-sm delete-btn"
                                                        data-id="<?php echo $row['id_kategori']; ?>"
                                                        data-nama="<?php echo htmlspecialchars($row['nama_kategori']); ?>"><i
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

                <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Kategori</h5><button type="button" class="btn-close"
                                    data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="master_kategori.php" method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" id="edit_id_kategori" name="id_kategori">
                                    <div class="mb-3">
                                        <label for="edit_nama_kategori" class="form-label">Nama Kategori</label>
                                        <input type="text" class="form-control" id="edit_nama_kategori"
                                            name="nama_kategori" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                </div>
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
        // Script Umum: Sidebar Toggle (Hamburger)
        const sidebarCollapse = document.getElementById('sidebarCollapse');
        if (sidebarCollapse) {
            sidebarCollapse.addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('active');
            });
        }

        // Script Umum: Notifikasi Flash Message (SweetAlert)
        <?php if (isset($flash_message) && $flash_message): ?>
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

        // Script Spesifik Halaman: Tombol Edit
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                // TIDAK PERLU AJAX, CUKUP ISI MODAL DENGAN DATA DARI ATRIBUT DATA-*
                const id = this.getAttribute('data-id');
                const nama = this.getAttribute('data-nama');

                document.getElementById('edit_id_kategori').value = id;
                document.getElementById('edit_nama_kategori').value = nama;
            });
        });

        // Script Spesifik Halaman: Tombol Delete
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const nama = this.getAttribute('data-nama');

                Swal.fire({
                    title: 'Anda Yakin?',
                    text: `Kategori "${nama}" akan dihapus secara permanen!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href =
                            `master_kategori.php?action=delete&id=${id}`;
                    }
                })
            });
        });
    });
    </script>
</body>

</html>