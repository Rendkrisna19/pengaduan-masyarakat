<?php
// rt/kelola_pengaduan.php (Versi All-in-One FINAL & LENGKAP)

// BAGIAN 1: LOGIKA PHP
session_start();
require_once __DIR__ . '/../config/database.php';

// Keamanan: Cek jika sudah login dan rolenya adalah RT
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'RT') {
    header("Location: ../auth/login.php");
    exit();
}
// Keamanan: Pastikan ID RT tersimpan di sesi
if (empty($_SESSION['user_rt_id'])) {
    die("Error: Akun RT Anda tidak terhubung dengan data wilayah. Silakan hubungi Admin.");
}
$id_rt_session = $_SESSION['user_rt_id'];
$id_user_session = $_SESSION['user_id'];

// Fungsi bantuan untuk cek apakah pengaduan milik RT ini
function cekKepemilikan($conn, $id_pengaduan, $id_rt) {
    $stmt = $conn->prepare("SELECT id_pengaduan FROM pengaduan WHERE id_pengaduan = ? AND tujuan_id_rt = ?");
    $stmt->bind_param("ii", $id_pengaduan, $id_rt);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result->num_rows > 0;
}

// === BLOK PEMROSESAN SEMUA AKSI DENGAN VALIDASI KEPEMILIKAN ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $id_pengaduan = (int)$_POST['id_pengaduan'];
    if (cekKepemilikan($conn, $id_pengaduan, $id_rt_session)) {
        
        $result_pelapor = $conn->query("SELECT id_user_pelapor FROM pengaduan WHERE id_pengaduan = $id_pengaduan");
        $id_pelapor = $result_pelapor->fetch_assoc()['id_user_pelapor'];

        switch ($_POST['action']) {
            case 'update_status':
                $status_baru = $conn->real_escape_string($_POST['status']);
                $stmt = $conn->prepare("UPDATE pengaduan SET status = ? WHERE id_pengaduan = ?");
                $stmt->bind_param("si", $status_baru, $id_pengaduan);
                if ($stmt->execute()) {
                    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Status berhasil diubah.'];
                    $pesan_notif = "Status laporan Anda #" . $id_pengaduan . " telah diubah oleh Petugas RT menjadi '" . $status_baru . "'.";
                    $stmt_notif = $conn->prepare("INSERT INTO notifikasi (id_user_penerima, id_pengaduan, pesan) VALUES (?, ?, ?)");
                    $stmt_notif->bind_param("iis", $id_pelapor, $id_pengaduan, $pesan_notif);
                    $stmt_notif->execute(); $stmt_notif->close();
                } else { $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal mengubah status.']; }
                $stmt->close();
                break;
            case 'tambah_tindak_lanjut':
                $keterangan = $conn->real_escape_string($_POST['keterangan']);
                $foto_hasil_path = null;
                if (isset($_FILES['foto_hasil']) && $_FILES['foto_hasil']['error'] == 0) {
                    $target_dir = __DIR__ . "/../uploads/hasil/";
                    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                    if (is_writable($target_dir)) {
                        $file_name = time() . '_' . basename($_FILES["foto_hasil"]["name"]);
                        $target_file = $target_dir . $file_name;
                        if (move_uploaded_file($_FILES["foto_hasil"]["tmp_name"], $target_file)) {
                            $foto_hasil_path = "uploads/hasil/" . $file_name;
                        }
                    }
                }
                $stmt = $conn->prepare("INSERT INTO tindak_lanjut (id_pengaduan, id_user_petugas, keterangan, foto_hasil) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiss", $id_pengaduan, $id_user_session, $keterangan, $foto_hasil_path);
                if($stmt->execute()) {
                    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Tindak lanjut berhasil ditambahkan.'];
                    $pesan_notif = "Petugas RT telah menambahkan progres baru pada laporan Anda #" . $id_pengaduan . ".";
                    $stmt_notif = $conn->prepare("INSERT INTO notifikasi (id_user_penerima, id_pengaduan, pesan) VALUES (?, ?, ?)");
                    $stmt_notif->bind_param("iis", $id_pelapor, $id_pengaduan, $pesan_notif);
                    $stmt_notif->execute(); $stmt_notif->close();
                } else { $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal menambahkan tindak lanjut.']; }
                $stmt->close();
                break;
            case 'tambah_komentar':
                $isi_komentar = $conn->real_escape_string($_POST['isi_komentar']);
                $stmt = $conn->prepare("INSERT INTO komentar_pengaduan (id_pengaduan, id_user_pengirim, isi_komentar) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $id_pengaduan, $id_user_session, $isi_komentar);
                if ($stmt->execute()) {
                    $pesan_notif = "Petugas RT memberikan komentar pada laporan Anda #" . $id_pengaduan . ".";
                    $stmt_notif = $conn->prepare("INSERT INTO notifikasi (id_user_penerima, id_pengaduan, pesan) VALUES (?, ?, ?)");
                    $stmt_notif->bind_param("iis", $id_pelapor, $id_pengaduan, $pesan_notif);
                    $stmt_notif->execute(); $stmt_notif->close();
                }
                $stmt->close();
                break;
        }
    } else {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Akses ditolak.'];
    }
    header("Location: kelola_pengaduan.php?action=detail&id=" . $id_pengaduan);
    exit();
}

$flash_message = $_SESSION['flash_message'] ?? null;
if ($flash_message) unset($_SESSION['flash_message']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengaduan - Panel RT</title>
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
                <h3><i class="fas fa-home"></i> Panel Ketua RT</h3>
            </div>
            <ul class="list-unstyled components">
                <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
                <li class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>"><a href="index.php"><i
                            class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                <li class="<?php echo ($currentPage == 'kelola_pengaduan.php') ? 'active' : ''; ?>"><a
                        href="kelola_pengaduan.php"><i class="fas fa-file-alt me-2"></i>Kelola Pengaduan</a></li>
                <li class="<?php echo ($currentPage == 'laporan.php') ? 'active' : ''; ?>"><a href="laporan.php"><i
                            class="fas fa-chart-bar me-2"></i>Laporan Wilayah</a></li>
            </ul>
        </nav>
        <div id="content">
            <nav class="topbar">
                <button type="button" id="sidebarCollapse" class="btn"><i class="fas fa-bars"></i></button>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" id="navbarDropdown"
                            role="button" data-bs-toggle="dropdown" aria-expanded="false"><i
                                class="fas fa-user me-2"></i><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
            <div class="main-content">
                <?php
            $action = $_GET['action'] ?? 'list';
            if ($action == 'detail' && isset($_GET['id'])) {
                // ---- TAMPILKAN HALAMAN DETAIL PENGADUAN ----
                $id_pengaduan = (int)$_GET['id'];
                if (cekKepemilikan($conn, $id_pengaduan, $id_rt_session)) {
                    $stmt_detail = $conn->prepare("SELECT p.*, u.nama_lengkap as nama_pelapor, u.nomor_telepon, k.nama_kategori, rt.nomor_rt, rw.nomor_rw FROM pengaduan p LEFT JOIN users u ON p.id_user_pelapor = u.id_user LEFT JOIN kategori_pengaduan k ON p.id_kategori = k.id_kategori JOIN rt ON p.id_rt_lokasi = rt.id_rt JOIN rw ON rt.id_rw = rw.id_rw WHERE p.id_pengaduan = ?");
                    $stmt_detail->bind_param("i", $id_pengaduan);
                    $stmt_detail->execute();
                    $detail_pengaduan = $stmt_detail->get_result()->fetch_assoc();
                    $stmt_detail->close();

                    if ($detail_pengaduan) {
                        $bukti_list = $conn->query("SELECT * FROM bukti_pendukung WHERE id_pengaduan = $id_pengaduan");
                        $tindak_lanjut_list = $conn->query("SELECT tl.*, u.nama_lengkap as nama_petugas FROM tindak_lanjut tl JOIN users u ON tl.id_user_petugas = u.id_user WHERE tl.id_pengaduan = $id_pengaduan ORDER BY tl.tanggal_aksi DESC");
                        $komentar_list = $conn->query("SELECT k.*, u.nama_lengkap as nama_pengirim, r.nama_role FROM komentar_pengaduan k JOIN users u ON k.id_user_pengirim = u.id_user JOIN roles r ON u.id_role = r.id_role WHERE k.id_pengaduan = $id_pengaduan ORDER BY k.tanggal_kirim ASC");
            ?>
                <a href="kelola_pengaduan.php" class="btn btn-secondary mb-3"><i
                        class="fas fa-arrow-left me-2"></i>Kembali ke Daftar</a>
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Detail Laporan
                                    #<?php echo $detail_pengaduan['id_pengaduan']; ?></h6>
                                <?php $status = $detail_pengaduan['status']; $badge_class = 'bg-secondary'; if ($status == 'Diterima') $badge_class = 'bg-primary'; if ($status == 'Diproses') $badge_class = 'bg-warning text-dark'; if ($status == 'Selesai') $badge_class = 'bg-success'; if ($status == 'Ditolak') $badge_class = 'bg-danger'; ?>
                                <span class="badge <?php echo $badge_class; ?> fs-6"><?php echo $status; ?></span>
                            </div>
                            <div class="card-body">
                                <p><strong>Pelapor:</strong>
                                    <?php echo htmlspecialchars($detail_pengaduan['nama_pelapor'] ?? 'N/A'); ?> (Telp:
                                    <?php echo htmlspecialchars($detail_pengaduan['nomor_telepon'] ?? 'N/A'); ?>)</p>
                                <p><strong>Tanggal Lapor:</strong>
                                    <?php echo date('d F Y', strtotime($detail_pengaduan['tanggal_lapor'])); ?></p>
                                <p><strong>Kategori:</strong>
                                    <?php echo htmlspecialchars($detail_pengaduan['nama_kategori'] ?? 'N/A'); ?></p>
                                <p><strong>Lokasi:</strong>
                                    <?php echo htmlspecialchars($detail_pengaduan['lokasi_lengkap']); ?> (RT
                                    <?php echo $detail_pengaduan['nomor_rt']; ?>/RW
                                    <?php echo $detail_pengaduan['nomor_rw']; ?>)</p>
                                <hr>
                                <h6><strong>Deskripsi Masalah:</strong></h6>
                                <p><?php echo nl2br(htmlspecialchars($detail_pengaduan['deskripsi'])); ?></p>
                                <hr>
                                <h6><strong>Bukti Awal dari Pelapor:</strong></h6>
                                <div class="row g-2">
                                    <?php if($bukti_list->num_rows > 0): while($b = $bukti_list->fetch_assoc()): ?>
                                    <div class="col-md-4 mb-2"><a href="../<?php echo $b['file_path']; ?>"
                                            target="_blank"><img src="../<?php echo $b['file_path']; ?>"
                                                class="img-fluid rounded shadow-sm"></a></div>
                                    <?php endwhile; else: echo '<p class="text-muted">Tidak ada bukti.</p>'; endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="card shadow mb-4">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);"><i
                                        class="fas fa-comments me-2"></i>Diskusi Laporan</h6>
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                <?php if($komentar_list->num_rows > 0): while($k = $komentar_list->fetch_assoc()): ?>
                                <div
                                    class="mb-2 p-2 rounded <?php echo ($k['id_user_pengirim'] == $detail_pengaduan['id_user_pelapor'] ? 'bg-light' : 'bg-info bg-opacity-10'); ?>">
                                    <strong><?php echo htmlspecialchars($k['nama_pengirim']); ?></strong> <span
                                        class="badge bg-secondary"><?php echo $k['nama_role']; ?></span>
                                    <p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars($k['isi_komentar'])); ?></p>
                                    <small
                                        class="text-muted d-block text-end"><?php echo date('d M Y, H:i', strtotime($k['tanggal_kirim'])); ?></small>
                                </div>
                                <?php endwhile; else: echo '<p class="text-muted">Belum ada komentar.</p>'; endif; ?>
                            </div>
                            <div class="card-footer">
                                <form action="kelola_pengaduan.php" method="POST">
                                    <input type="hidden" name="action" value="tambah_komentar"><input type="hidden"
                                        name="id_pengaduan" value="<?php echo $id_pengaduan; ?>">
                                    <div class="input-group"><textarea name="isi_komentar" class="form-control"
                                            placeholder="Tulis komentar..." rows="2" required></textarea><button
                                            class="btn btn-primary" type="submit">Kirim</button></div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card shadow mb-4">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Update Status &
                                    Progres</h6>
                            </div>
                            <div class="card-body">
                                <form action="kelola_pengaduan.php" method="POST" class="mb-4">
                                    <input type="hidden" name="action" value="update_status"><input type="hidden"
                                        name="id_pengaduan" value="<?php echo $id_pengaduan; ?>">
                                    <label class="form-label">Ubah Status</label>
                                    <div class="input-group">
                                        <select name="status" class="form-select">
                                            <option value="Diterima"
                                                <?php if($detail_pengaduan['status'] == 'Diterima') echo 'selected'; ?>>
                                                Diterima</option>
                                            <option value="Diproses"
                                                <?php if($detail_pengaduan['status'] == 'Diproses') echo 'selected'; ?>>
                                                Diproses</option>
                                            <option value="Selesai"
                                                <?php if($detail_pengaduan['status'] == 'Selesai') echo 'selected'; ?>>
                                                Selesai</option>
                                            <option value="Ditolak"
                                                <?php if($detail_pengaduan['status'] == 'Ditolak') echo 'selected'; ?>>
                                                Ditolak</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary">Update</button>
                                    </div>
                                </form>
                                <hr>
                                <h6>Tambah Tindak Lanjut</h6>
                                <form action="kelola_pengaduan.php" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="tambah_tindak_lanjut"><input type="hidden"
                                        name="id_pengaduan" value="<?php echo $id_pengaduan; ?>">
                                    <div class="mb-3"><label class="form-label">Keterangan Progres</label><textarea
                                            name="keterangan" class="form-control" rows="3" required></textarea></div>
                                    <div class="mb-3"><label class="form-label">Foto Hasil (Opsional)</label><input
                                            type="file" name="foto_hasil" class="form-control"></div>
                                    <button type="submit" class="btn btn-success w-100">Simpan Progres</button>
                                </form>
                            </div>
                        </div>
                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Riwayat Tindak
                                    Lanjut</h6>
                            </div>
                            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                <?php if($tindak_lanjut_list->num_rows > 0): while($tl = $tindak_lanjut_list->fetch_assoc()): ?>
                                <div class="mb-2 p-2 rounded bg-light border">
                                    <p class="mb-1"><?php echo nl2br(htmlspecialchars($tl['keterangan'])); ?></p>
                                    <?php if($tl['foto_hasil']): ?><a href="../<?php echo $tl['foto_hasil']; ?>"
                                        target="_blank" class="btn btn-sm btn-outline-info mt-1"><i
                                            class="fas fa-camera"></i> Lihat Foto Hasil</a><?php endif; ?>
                                    <small class="text-muted d-block mt-2">Oleh:
                                        <?php echo htmlspecialchars($tl['nama_petugas']); ?> -
                                        <?php echo date('d M Y, H:i', strtotime($tl['tanggal_aksi'])); ?></small>
                                </div>
                                <?php endwhile; else: echo '<p class="text-muted">Belum ada tindak lanjut.</p>'; endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                    }
                } else {
                    echo "<div class='alert alert-danger'>Pengaduan tidak ditemukan atau bukan untuk wilayah RT Anda.</div>";
                }
            } else {
                // TAMPILAN DAFTAR PENGADUAN UNTUK RT INI
                $stmt_list = $conn->prepare("SELECT p.id_pengaduan, p.tanggal_lapor, p.status, u.nama_lengkap as nama_pelapor, k.nama_kategori FROM pengaduan p LEFT JOIN users u ON p.id_user_pelapor = u.id_user LEFT JOIN kategori_pengaduan k ON p.id_kategori = k.id_kategori WHERE p.tujuan_id_rt = ? ORDER BY p.id_pengaduan DESC");
                $stmt_list->bind_param("i", $id_rt_session);
                $stmt_list->execute();
                $pengaduan_list = $stmt_list->get_result();
            ?>
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Daftar Pengaduan untuk
                            Wilayah Anda</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tgl Lapor</th>
                                        <th>Pelapor</th>
                                        <th>Kategori</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($pengaduan_list->num_rows > 0): while ($pengaduan = $pengaduan_list->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $pengaduan['id_pengaduan']; ?></td>
                                        <td><?php echo date('d-m-Y', strtotime($pengaduan['tanggal_lapor'])); ?></td>
                                        <td><?php echo htmlspecialchars($pengaduan['nama_pelapor'] ?? 'Data Pelapor Dihapus'); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($pengaduan['nama_kategori'] ?? 'Tanpa Kategori'); ?>
                                        </td>
                                        <td>
                                            <?php $status = $pengaduan['status']; $badge_class = 'bg-secondary';
                                            if ($status == 'Diterima') $badge_class = 'bg-primary'; if ($status == 'Diproses') $badge_class = 'bg-warning text-dark';
                                            if ($status == 'Selesai') $badge_class = 'bg-success'; if ($status == 'Ditolak') $badge_class = 'bg-danger'; ?>
                                            <span
                                                class="badge <?php echo $badge_class; ?>"><?php echo $status; ?></span>
                                        </td>
                                        <td><a href="kelola_pengaduan.php?action=detail&id=<?php echo $pengaduan['id_pengaduan']; ?>"
                                                class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Detail</a></td>
                                    </tr>
                                    <?php endwhile; else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Belum ada pengaduan untuk wilayah Anda.</td>
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
    });
    </script>
</body>

</html>