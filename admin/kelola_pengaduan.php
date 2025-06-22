<?php

session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}




// PROSES UPDATE STATUS YANG SUDAH DIMODIFIKASI
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $id_pengaduan = (int)$_POST['id_pengaduan'];
    // ... (kode validasi kepemilikan untuk RT/RW tetap ada) ...

    $status_baru = $conn->real_escape_string($_POST['status']);
    $stmt = $conn->prepare("UPDATE pengaduan SET status = ? WHERE id_pengaduan = ?");
    $stmt->bind_param("si", $status_baru, $id_pengaduan);

    if ($stmt->execute()) {
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Status pengaduan berhasil diubah.'];

        // ==========================================================
        // == KODE BARU UNTUK MEMBUAT NOTIFIKASI ==
        // ==========================================================
        
        // 1. Ambil ID user yang membuat laporan
        $result_pelapor = $conn->query("SELECT id_user_pelapor FROM pengaduan WHERE id_pengaduan = $id_pengaduan");
        $id_pelapor = $result_pelapor->fetch_assoc()['id_user_pelapor'];

        // 2. Buat pesan notifikasi
        $pesan_notif = "Status laporan Anda #" . $id_pengaduan . " telah diubah menjadi '" . $status_baru . "'.";

        // 3. Masukkan notifikasi ke database
        $stmt_notif = $conn->prepare("INSERT INTO notifikasi (id_user_penerima, id_pengaduan, pesan) VALUES (?, ?, ?)");
        $stmt_notif->bind_param("iis", $id_pelapor, $id_pengaduan, $pesan_notif);
        $stmt_notif->execute();
        $stmt_notif->close();
        
        // ==========================================================
        
    } else {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal mengubah status.'];
    }
    $stmt->close();
    header("Location: kelola_pengaduan.php?action=detail&id=" . $id_pengaduan);
    exit();
}

// PROSES TAMBAH TINDAK LANJUT (DENGAN UPLOAD FOTO)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'tambah_tindak_lanjut') {
    $id_pengaduan = (int)$_POST['id_pengaduan'];
    $id_user_petugas = (int)$_SESSION['user_id'];
    $keterangan = $conn->real_escape_string($_POST['keterangan']);
    $foto_hasil_path = null;

    if (isset($_FILES['foto_hasil']) && $_FILES['foto_hasil']['error'] == 0) {
        $target_dir = __DIR__ . "/../uploads/hasil/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $file_name = time() . '_' . basename($_FILES["foto_hasil"]["name"]);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["foto_hasil"]["tmp_name"], $target_file)) {
            $foto_hasil_path = "uploads/hasil/" . $file_name;
        }
    }

    $stmt = $conn->prepare("INSERT INTO tindak_lanjut (id_pengaduan, id_user_petugas, keterangan, foto_hasil) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $id_pengaduan, $id_user_petugas, $keterangan, $foto_hasil_path);
    if ($stmt->execute()) $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Tindak lanjut berhasil ditambahkan.'];
    else $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal menambahkan tindak lanjut.'];
    $stmt->close();
    header("Location: kelola_pengaduan.php?action=detail&id=" . $id_pengaduan);
    exit();
}

// PROSES TAMBAH KOMENTAR
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'tambah_komentar') {
    $id_pengaduan = (int)$_POST['id_pengaduan'];
    $id_user_pengirim = (int)$_SESSION['user_id'];
    $isi_komentar = $conn->real_escape_string($_POST['isi_komentar']);
    $stmt = $conn->prepare("INSERT INTO komentar_pengaduan (id_pengaduan, id_user_pengirim, isi_komentar) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $id_pengaduan, $id_user_pengirim, $isi_komentar);
    $stmt->execute();
    $stmt->close();
    // Tidak perlu flash message untuk komentar, langsung refresh halaman detail
    header("Location: kelola_pengaduan.php?action=detail&id=" . $id_pengaduan);
    exit();
}

// PROSES HAPUS PENGADUAN
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM pengaduan WHERE id_pengaduan = ?");
    $stmt->bind_param("i", $id_to_delete);
    if ($stmt->execute()) $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Pengaduan berhasil dihapus.'];
    else $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal menghapus pengaduan.'];
    $stmt->close();
    header("Location: kelola_pengaduan.php");
    exit();
}

// Ambil flash message jika ada
$flash_message = $_SESSION['flash_message'] ?? null;
if ($flash_message) unset($_SESSION['flash_message']);

// Panggil header SETELAH semua logika proses selesai
require_once __DIR__ . '/../components/header.php';

// =================================================================
// BAGIAN 2: KONTROL TAMPILAN (Menampilkan Daftar atau Detail)
// =================================================================
$action = $_GET['action'] ?? 'list';

if ($action == 'detail' && isset($_GET['id'])) {
    // ---- TAMPILKAN HALAMAN DETAIL PENGADUAN ----
    $id_pengaduan = (int)$_GET['id'];

    // Ambil semua data terkait pengaduan ini
    $detail_pengaduan = $conn->query("SELECT p.*, u.nama_lengkap as nama_pelapor, u.nomor_telepon, k.nama_kategori, rt.nomor_rt, rw.nomor_rw FROM pengaduan p JOIN users u ON p.id_user_pelapor = u.id_user JOIN kategori_pengaduan k ON p.id_kategori = k.id_kategori JOIN rt ON p.id_rt_lokasi = rt.id_rt JOIN rw ON rt.id_rw = rw.id_rw WHERE p.id_pengaduan = $id_pengaduan")->fetch_assoc();
    $bukti_list = $conn->query("SELECT * FROM bukti_pendukung WHERE id_pengaduan = $id_pengaduan");
    $tindak_lanjut_list = $conn->query("SELECT tl.*, u.nama_lengkap as nama_petugas FROM tindak_lanjut tl JOIN users u ON tl.id_user_petugas = u.id_user WHERE tl.id_pengaduan = $id_pengaduan ORDER BY tl.tanggal_aksi DESC");
    $komentar_list = $conn->query("SELECT k.*, u.nama_lengkap as nama_pengirim, r.nama_role FROM komentar_pengaduan k JOIN users u ON k.id_user_pengirim = u.id_user JOIN roles r ON u.id_role = r.id_role WHERE k.id_pengaduan = $id_pengaduan ORDER BY k.tanggal_kirim ASC");

    if (!$detail_pengaduan) {
        echo "<div class='alert alert-danger'>Pengaduan tidak ditemukan.</div>";
    } else {
?>
<a href="kelola_pengaduan.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left me-2"></i>Kembali ke
    Daftar</a>
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Detail Laporan
                    #<?php echo $detail_pengaduan['id_pengaduan']; ?></h6>
            </div>
            <div class="card-body">
                <p><strong>Pelapor:</strong> <?php echo htmlspecialchars($detail_pengaduan['nama_pelapor']); ?> (Telp:
                    <?php echo htmlspecialchars($detail_pengaduan['nomor_telepon']); ?>)</p>
                <p><strong>Tanggal Lapor:</strong>
                    <?php echo date('d F Y', strtotime($detail_pengaduan['tanggal_lapor'])); ?></p>
                <p><strong>Kategori:</strong> <?php echo htmlspecialchars($detail_pengaduan['nama_kategori']); ?></p>
                <p><strong>Lokasi:</strong> <?php echo htmlspecialchars($detail_pengaduan['lokasi_lengkap']); ?> (RT
                    <?php echo $detail_pengaduan['nomor_rt']; ?>/RW <?php echo $detail_pengaduan['nomor_rw']; ?>)</p>
                <hr>
                <h6><strong>Deskripsi Masalah:</strong></h6>
                <p><?php echo nl2br(htmlspecialchars($detail_pengaduan['deskripsi'])); ?></p>
                <hr>
                <h6><strong>Bukti Awal dari Pelapor:</strong></h6>
                <div class="row">
                    <?php while($b = $bukti_list->fetch_assoc()): ?>
                    <div class="col-md-4 mb-2"><a href="../<?php echo $b['file_path']; ?>" target="_blank"><img
                                src="../<?php echo $b['file_path']; ?>" class="img-fluid rounded shadow-sm"></a></div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        <div class="card shadow mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Diskusi / Komentar</h6>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php while($k = $komentar_list->fetch_assoc()): ?>
                <div
                    class="mb-2 p-2 rounded <?php echo ($k['nama_role'] == 'Masyarakat' ? 'bg-light' : 'bg-info bg-opacity-25'); ?>">
                    <strong><?php echo htmlspecialchars($k['nama_pengirim']); ?></strong> <span
                        class="badge bg-secondary"><?php echo $k['nama_role']; ?></span>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($k['isi_komentar'])); ?></p>
                    <small class="text-muted"><?php echo date('d M Y, H:i', strtotime($k['tanggal_kirim'])); ?></small>
                </div>
                <?php endwhile; ?>
            </div>
            <div class="card-footer">
                <form action="kelola_pengaduan.php" method="POST">
                    <input type="hidden" name="action" value="tambah_komentar">
                    <input type="hidden" name="id_pengaduan" value="<?php echo $id_pengaduan; ?>">
                    <div class="input-group">
                        <textarea name="isi_komentar" class="form-control" placeholder="Tulis komentar..." rows="1"
                            required></textarea>
                        <button class="btn btn-primary" type="submit">Kirim</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Update Status & Progres</h6>
            </div>
            <div class="card-body">
                <form action="kelola_pengaduan.php" method="POST" class="mb-4">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id_pengaduan" value="<?php echo $id_pengaduan; ?>">
                    <label class="form-label">Ubah Status</label>
                    <div class="input-group">
                        <select name="status" class="form-select">
                            <option value="Diterima"
                                <?php if($detail_pengaduan['status'] == 'Diterima') echo 'selected'; ?>>Diterima
                            </option>
                            <option value="Diproses"
                                <?php if($detail_pengaduan['status'] == 'Diproses') echo 'selected'; ?>>Diproses
                            </option>
                            <option value="Selesai"
                                <?php if($detail_pengaduan['status'] == 'Selesai') echo 'selected'; ?>>Selesai</option>
                            <option value="Ditolak"
                                <?php if($detail_pengaduan['status'] == 'Ditolak') echo 'selected'; ?>>Ditolak</option>
                        </select>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
                <hr>
                <h6>Tambah Tindak Lanjut</h6>
                <form action="kelola_pengaduan.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="tambah_tindak_lanjut">
                    <input type="hidden" name="id_pengaduan" value="<?php echo $id_pengaduan; ?>">
                    <div class="mb-3">
                        <label class="form-label">Keterangan Progres</label>
                        <textarea name="keterangan" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Foto Hasil (Opsional)</label>
                        <input type="file" name="foto_hasil" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-success w-100">Simpan Progres</button>
                </form>
            </div>
        </div>
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Riwayat Tindak Lanjut</h6>
            </div>
            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                <?php while($tl = $tindak_lanjut_list->fetch_assoc()): ?>
                <div class="mb-2 p-2 rounded bg-light">
                    <p class="mb-1"><?php echo nl2br(htmlspecialchars($tl['keterangan'])); ?></p>
                    <?php if($tl['foto_hasil']): ?>
                    <a href="../<?php echo $tl['foto_hasil']; ?>" target="_blank">Lihat Foto Hasil</a>
                    <?php endif; ?>
                    <small class="text-muted d-block">Oleh: <?php echo htmlspecialchars($tl['nama_petugas']); ?> -
                        <?php echo date('d M Y, H:i', strtotime($tl['tanggal_aksi'])); ?></small>
                </div>
                <?php endwhile; ?>
                <?php if($tindak_lanjut_list->num_rows == 0) echo '<p class="text-muted">Belum ada tindak lanjut.</p>'; ?>
            </div>
        </div>
    </div>
</div>
<?php
    }
} else {
    // ---- TAMPILKAN DAFTAR SEMUA PENGADUAN ----
    $query_pengaduan = "SELECT p.*, u.nama_lengkap as nama_pelapor, k.nama_kategori, rt.nomor_rt FROM pengaduan p JOIN users u ON p.id_user_pelapor = u.id_user JOIN kategori_pengaduan k ON p.id_kategori = k.id_kategori JOIN rt ON p.id_rt_lokasi = rt.id_rt ORDER BY p.id_pengaduan DESC";
    $pengaduan_list = $conn->query($query_pengaduan);
?>
<div class="card shadow">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Kelola Semua Pengaduan</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tgl Lapor</th>
                        <th>Pelapor</th>
                        <th>Kategori</th>
                        <th>Lokasi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($pengaduan = $pengaduan_list->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $pengaduan['id_pengaduan']; ?></td>
                        <td><?php echo date('d-m-Y', strtotime($pengaduan['tanggal_lapor'])); ?></td>
                        <td><?php echo htmlspecialchars($pengaduan['nama_pelapor']); ?></td>
                        <td><?php echo htmlspecialchars($pengaduan['nama_kategori']); ?></td>
                        <td>RT <?php echo $pengaduan['nomor_rt']; ?></td>
                        <td>
                            <?php $status = $pengaduan['status']; $badge_class = 'bg-secondary';
                                if ($status == 'Diterima') $badge_class = 'bg-primary'; if ($status == 'Diproses') $badge_class = 'bg-warning text-dark';
                                if ($status == 'Selesai') $badge_class = 'bg-success'; if ($status == 'Ditolak') $badge_class = 'bg-danger'; ?>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo $status; ?></span>
                        </td>
                        <td>
                            <a href="kelola_pengaduan.php?action=detail&id=<?php echo $pengaduan['id_pengaduan']; ?>"
                                class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Detail</a>
                            <button class="btn btn-danger btn-sm delete-btn"
                                data-id="<?php echo $pengaduan['id_pengaduan']; ?>"
                                data-nama="#<?php echo $pengaduan['id_pengaduan']; ?>"><i
                                    class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
} // Menutup blok else dari kontrol tampilan

require_once __DIR__ . '/../components/scripts.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Hanya perlu script untuk tombol delete
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nama = this.getAttribute('data-nama');
            Swal.fire({
                title: 'Anda Yakin?',
                text: `Pengaduan "${nama}" akan dihapus secara permanen!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href =
                        `kelola_pengaduan.php?action=delete&id=${id}`;
                }
            })
        });
    });
});
</script>
</body>

</html>