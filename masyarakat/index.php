<?php
// masyarakat/index.php (Versi FINAL Lengkap dengan Notifikasi & Konten)

session_start();
require_once __DIR__ . '/../config/database.php';

// Keamanan: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
$id_user_session = $_SESSION['user_id'];

// LOGIKA UNTUK MENAMBAHKAN KOMENTAR BARU
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'tambah_komentar') {
    $id_pengaduan = (int)$_POST['id_pengaduan'];
    $isi_komentar = $conn->real_escape_string($_POST['isi_komentar']);

    // Cek kepemilikan sebelum post komentar
    $stmt_cek = $conn->prepare("SELECT id_pengaduan FROM pengaduan WHERE id_pengaduan = ? AND id_user_pelapor = ?");
    $stmt_cek->bind_param("ii", $id_pengaduan, $id_user_session);
    $stmt_cek->execute();
    $result_cek = $stmt_cek->get_result();
    
    if ($result_cek->num_rows > 0 && !empty($isi_komentar)) {
        $stmt_insert = $conn->prepare("INSERT INTO komentar_pengaduan (id_pengaduan, id_user_pengirim, isi_komentar) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("iis", $id_pengaduan, $id_user_session, $isi_komentar);
        $stmt_insert->execute();
        $stmt_insert->close();
    }
    $stmt_cek->close();
    header("Location: index.php?action=detail&id=" . $id_pengaduan);
    exit();
}

// BARU: Ambil jumlah notifikasi yang belum dibaca untuk ditampilkan di badge
$stmt_notif_count = $conn->prepare("SELECT COUNT(id_notifikasi) as total FROM notifikasi WHERE id_user_penerima = ? AND sudah_dibaca = 0");
$stmt_notif_count->bind_param("i", $id_user_session);
$stmt_notif_count->execute();
$notif_count = $stmt_notif_count->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_notif_count->close();

$flash_message = $_SESSION['flash_message'] ?? null;
if ($flash_message) unset($_SESSION['flash_message']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Masyarakat - Panel Lapor</title>
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

    #notifDropdown .badge {
        font-size: 0.6em;
        top: 10px;
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
                            class="fas fa-history me-2"></i>Riwayat Pengaduan</a></li>
                <li class="<?php echo ($currentPage == 'buat_pengaduan.php') ? 'active' : ''; ?>"><a
                        href="buat_pengaduan.php"><i class="fas fa-plus-circle me-2"></i>Buat Laporan Baru</a></li>
            </ul>
        </nav>
        <div id="content">
            <nav class="topbar">
                <button type="button" id="sidebarCollapse" class="btn"><i class="fas fa-bars"></i></button>
                <ul class="navbar-nav ms-auto d-flex flex-row align-items-center">
                    <li class="nav-item dropdown me-3">
                        <a class="nav-link position-relative" href="#" id="notifDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell fa-lg"></i>
                            <?php if($notif_count > 0): ?>
                            <span
                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                id="notif-badge"><?php echo $notif_count; ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notifDropdown" id="notif-list"
                            style="width: 350px; max-height: 400px; overflow-y: auto;">
                            <li><a class="dropdown-item text-center text-muted small" href="#">Memuat...</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false"><i
                                class="fas fa-user me-2"></i><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
            <div class="main-content">
                <?php
            // KONTROL TAMPILAN (Daftar atau Detail)
            $action = $_GET['action'] ?? 'list';
            if ($action == 'detail' && isset($_GET['id'])) {
                $id_pengaduan = (int)$_GET['id'];
                $stmt_detail = $conn->prepare("SELECT p.*, k.nama_kategori, rt.nomor_rt, rw.nomor_rw FROM pengaduan p JOIN kategori_pengaduan k ON p.id_kategori = k.id_kategori JOIN rt ON p.id_rt_lokasi = rt.id_rt JOIN rw ON rt.id_rw = rw.id_rw WHERE p.id_pengaduan = ? AND p.id_user_pelapor = ?");
                $stmt_detail->bind_param("ii", $id_pengaduan, $id_user_session);
                $stmt_detail->execute();
                $detail_pengaduan = $stmt_detail->get_result()->fetch_assoc();
                $stmt_detail->close();

                if (!$detail_pengaduan) {
                    echo "<div class='alert alert-danger'>Pengaduan tidak ditemukan atau Anda tidak memiliki akses.</div>";
                } else {
                    $bukti_list = $conn->query("SELECT * FROM bukti_pendukung WHERE id_pengaduan = $id_pengaduan");
                    $tindak_lanjut_list = $conn->query("SELECT tl.*, u.nama_lengkap as nama_petugas FROM tindak_lanjut tl JOIN users u ON tl.id_user_petugas = u.id_user WHERE tl.id_pengaduan = $id_pengaduan ORDER BY tl.tanggal_aksi DESC");
                    $komentar_list = $conn->query("SELECT k.*, u.nama_lengkap as nama_pengirim, r.nama_role FROM komentar_pengaduan k JOIN users u ON k.id_user_pengirim = u.id_user JOIN roles r ON u.id_role = r.id_role WHERE k.id_pengaduan = $id_pengaduan ORDER BY k.tanggal_kirim ASC");
            ?>
                <a href="index.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left me-2"></i>Kembali ke
                    Riwayat</a>
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Detail Pengaduan
                            #<?php echo $detail_pengaduan['id_pengaduan']; ?></h6>
                        <?php $status = $detail_pengaduan['status']; $badge_class = 'bg-secondary'; if ($status == 'Diterima') $badge_class = 'bg-primary'; if ($status == 'Diproses') $badge_class = 'bg-warning text-dark'; if ($status == 'Selesai') $badge_class = 'bg-success'; if ($status == 'Ditolak') $badge_class = 'bg-danger'; ?>
                        <span class="badge <?php echo $badge_class; ?> fs-6"><?php echo $status; ?></span>
                    </div>
                    <div class="card-body">
                        <p><strong>Kategori:</strong>
                            <?php echo htmlspecialchars($detail_pengaduan['nama_kategori']); ?></p>
                        <p><strong>Lokasi:</strong> <?php echo htmlspecialchars($detail_pengaduan['lokasi_lengkap']); ?>
                            (RT <?php echo $detail_pengaduan['nomor_rt']; ?>/RW
                            <?php echo $detail_pengaduan['nomor_rw']; ?>)</p>
                        <hr>
                        <h6><strong>Deskripsi Laporan Anda:</strong></h6>
                        <p><?php echo nl2br(htmlspecialchars($detail_pengaduan['deskripsi'])); ?></p>
                        <hr>
                        <h6><strong>Bukti yang Anda Lampirkan:</strong></h6>
                        <div class="row g-2">
                            <?php if($bukti_list->num_rows > 0): while($b = $bukti_list->fetch_assoc()): ?>
                            <div class="col-md-3 mb-2"><a href="../<?php echo $b['file_path']; ?>" target="_blank"><img
                                        src="../<?php echo $b['file_path']; ?>" class="img-fluid rounded shadow-sm"></a>
                            </div>
                            <?php endwhile; else: echo '<p class="text-muted">Tidak ada bukti yang dilampirkan.</p>'; endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);"><i
                                class="fas fa-tasks me-2"></i>Progres dari Petugas</h6>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        <?php if($tindak_lanjut_list->num_rows > 0): while($tl = $tindak_lanjut_list->fetch_assoc()): ?>
                        <div class="mb-3 p-3 rounded bg-light border">
                            <p class="mb-1"><strong><?php echo nl2br(htmlspecialchars($tl['keterangan'])); ?></strong>
                            </p>
                            <?php if($tl['foto_hasil']): ?><a href="../<?php echo $tl['foto_hasil']; ?>"
                                target="_blank">Lihat Foto Hasil Perbaikan</a><?php endif; ?>
                            <small class="text-muted d-block mt-2">Oleh:
                                <?php echo htmlspecialchars($tl['nama_petugas']); ?> pada
                                <?php echo date('d M Y, H:i', strtotime($tl['tanggal_aksi'])); ?></small>
                        </div>
                        <?php endwhile; else: ?><p class="text-muted">Belum ada progres dari petugas.</p><?php endif; ?>
                    </div>
                </div>
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);"><i
                                class="fas fa-comments me-2"></i>Diskusi Laporan</h6>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        <?php if($komentar_list->num_rows > 0): while($k = $komentar_list->fetch_assoc()): ?>
                        <div
                            class="mb-2 p-2 rounded <?php echo ($k['id_user_pengirim'] == $id_user_session ? 'bg-primary bg-opacity-10' : 'bg-light'); ?>">
                            <strong><?php echo htmlspecialchars($k['nama_pengirim']); ?></strong> <span
                                class="badge bg-secondary"><?php echo $k['nama_role']; ?></span>
                            <p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars($k['isi_komentar'])); ?></p>
                            <small
                                class="text-muted d-block text-end"><?php echo date('d M Y, H:i', strtotime($k['tanggal_kirim'])); ?></small>
                        </div>
                        <?php endwhile; else: echo '<p class="text-muted">Belum ada komentar.</p>'; endif; ?>
                    </div>
                    <div class="card-footer">
                        <form action="index.php?action=detail&id=<?php echo $id_pengaduan; ?>" method="POST">
                            <input type="hidden" name="action" value="tambah_komentar"><input type="hidden"
                                name="id_pengaduan" value="<?php echo $id_pengaduan; ?>">
                            <div class="input-group"><textarea name="isi_komentar" class="form-control"
                                    placeholder="Tulis komentar atau pertanyaan..." rows="2" required></textarea><button
                                    class="btn btn-primary" type="submit">Kirim</button></div>
                        </form>
                    </div>
                </div>
                <?php
                }
            } else {
                // TAMPILAN DAFTAR RIWAYAT PENGADUAN (DEFAULT)
                $query = "SELECT p.*, k.nama_kategori FROM pengaduan p JOIN kategori_pengaduan k ON p.id_kategori = k.id_kategori WHERE p.id_user_pelapor = ? ORDER BY p.id_pengaduan DESC";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id_user_session);
                $stmt->execute();
                $riwayat_pengaduan = $stmt->get_result();
            ?>
                <h1 class="h3 mb-4">Riwayat Pengaduan Saya</h1>
                <div class="card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tgl Lapor</th>
                                        <th>Kategori</th>
                                        <th>Deskripsi</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($riwayat_pengaduan->num_rows > 0): while ($row = $riwayat_pengaduan->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $row['id_pengaduan']; ?></td>
                                        <td><?php echo date('d M Y', strtotime($row['tanggal_lapor'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_kategori']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($row['deskripsi'], 0, 50)) . '...'; ?>
                                        </td>
                                        <td>
                                            <?php $status = $row['status']; $badge_class = 'bg-secondary'; if ($status == 'Diterima') $badge_class = 'bg-primary'; if ($status == 'Diproses') $badge_class = 'bg-warning text-dark'; if ($status == 'Selesai') $badge_class = 'bg-success'; if ($status == 'Ditolak') $badge_class = 'bg-danger';?>
                                            <span
                                                class="badge <?php echo $badge_class; ?>"><?php echo $status; ?></span>
                                        </td>
                                        <td><a href="index.php?action=detail&id=<?php echo $row['id_pengaduan']; ?>"
                                                class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Detail</a></td>
                                    </tr>
                                    <?php endwhile; else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Anda belum pernah membuat pengaduan. <a
                                                href="buat_pengaduan.php">Buat Laporan Sekarang</a></td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Script Umum: Sidebar Toggle & Flash Message
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

        // SCRIPT BARU UNTUK MENGHIDUPKAN NOTIFIKASI
        const notifDropdown = document.getElementById('notifDropdown');
        const notifList = document.getElementById('notif-list');
        const notifBadge = document.getElementById('notif-badge');
        if (notifDropdown) {
            notifDropdown.addEventListener('show.bs.dropdown', function() {
                notifList.innerHTML =
                    '<li><a class="dropdown-item text-center text-muted small" href="#">Memuat...</a></li>';
                fetch('../ajax/get_notifikasi.php')
                    .then(response => response.json())
                    .then(data => {
                        notifList.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(notif => {
                                const listItem = document.createElement('li');
                                const linkClass = notif.sudah_dibaca == 0 ?
                                    'bg-light fw-bold' : '';
                                listItem.innerHTML = `<a class="dropdown-item ${linkClass}" href="index.php?action=detail&id=${notif.id_pengaduan}">
                                    <div class="small">${notif.pesan}</div>
                                    <div class="fw-light text-muted" style="font-size: 0.8em;">${new Date(notif.tanggal_dibuat).toLocaleString('id-ID')}</div>
                                </a>`;
                                notifList.appendChild(listItem);
                            });
                        } else {
                            notifList.innerHTML =
                                '<li><a class="dropdown-item text-center text-muted small" href="#">Tidak ada notifikasi.</a></li>';
                        }
                    });

                if (notifBadge) {
                    setTimeout(() => {
                        fetch('../ajax/mark_notifikasi_read.php');
                        notifBadge.style.display = 'none';
                    }, 1500);
                }
            });
        }
    });
    </script>
</body>

</html>