<?php


session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kirim_pengaduan'])) {
    $id_user_pelapor = $_SESSION['user_id'];
    $id_kategori = (int)$_POST['id_kategori'];
    $lokasi_lengkap = $conn->real_escape_string($_POST['lokasi_lengkap']);
    $deskripsi = $conn->real_escape_string($_POST['deskripsi']);
    $tanggal_lapor = date('Y-m-d');
    $id_rt_lokasi = (int)$_POST['id_rt_lokasi'];
    $tujuan = $_POST['tujuan'];

    $tujuan_id_rt = null;
    $tujuan_id_rw = null;

    if ($tujuan == 'rt') {
        $tujuan_id_rt = $id_rt_lokasi;
    } else {
        $rw_query = $conn->query("SELECT id_rw FROM rt WHERE id_rt = $id_rt_lokasi");
        if($rw_query && $rw_query->num_rows > 0) {
            $tujuan_id_rw = $rw_query->fetch_assoc()['id_rw'];
        }
    }
    
    // Validasi input dasar
    if (empty($id_kategori) || empty($lokasi_lengkap) || empty($deskripsi) || empty($id_rt_lokasi)) {
        $error_message = "Semua field wajib diisi.";
    } else {
        
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO pengaduan (deskripsi, lokasi_lengkap, tanggal_lapor, id_user_pelapor, id_kategori, id_rt_lokasi, tujuan_id_rt, tujuan_id_rw) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssiisii", $deskripsi, $lokasi_lengkap, $tanggal_lapor, $id_user_pelapor, $id_kategori, $id_rt_lokasi, $tujuan_id_rt, $tujuan_id_rw);
            $stmt->execute();
            $id_pengaduan_baru = $stmt->insert_id;
            $stmt->close();

         
            $upload_ok = true;
            if (isset($_FILES['bukti_pendukung']) && !empty($_FILES['bukti_pendukung']['name'][0])) {
                $target_dir = __DIR__ . "/../uploads/bukti/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                $max_file_size = 5 * 1024 * 1024; // 5 MB

                foreach ($_FILES['bukti_pendukung']['name'] as $key => $name) {
                    if ($_FILES['bukti_pendukung']['error'][$key] == 0) {
                        $file_tmp_path = $_FILES['bukti_pendukung']['tmp_name'][$key];
                        $file_name = time() . '_' . basename($name);
                        $file_size = $_FILES['bukti_pendukung']['size'][$key];
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                        // Validasi tipe file
                        if (!in_array($file_ext, $allowed_types)) {
                            $error_message .= "Error: File '$name' memiliki tipe yang tidak diizinkan. Hanya JPG, PNG, GIF yang boleh. ";
                            $upload_ok = false;
                            continue; // Lanjut ke file berikutnya
                        }

                        // Validasi ukuran file
                        if ($file_size > $max_file_size) {
                            $error_message .= "Error: Ukuran file '$name' terlalu besar (Maks 5MB). ";
                            $upload_ok = false;
                            continue; // Lanjut ke file berikutnya
                        }

                        // Pindahkan file jika valid
                        $target_file = $target_dir . $file_name;
                        if (move_uploaded_file($file_tmp_path, $target_file)) {
                            $file_path_db = "uploads/bukti/" . $file_name;
                            $stmt_bukti = $conn->prepare("INSERT INTO bukti_pendukung (id_pengaduan, file_path) VALUES (?, ?)");
                            $stmt_bukti->bind_param("is", $id_pengaduan_baru, $file_path_db);
                            $stmt_bukti->execute();
                            $stmt_bukti->close();
                        } else {
                            $error_message .= "Error saat mengupload file '$name'. ";
                            $upload_ok = false;
                        }
                    }
                }
            }

            if ($upload_ok) {
                // Jika semua proses berhasil, commit transaksi
                $conn->commit();
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Laporan Anda berhasil dikirim!'];
                header("Location: index.php");
                exit();
            } else {
                // Jika ada error saat upload, batalkan semua insert ke database
                $conn->rollback();
                // Pesan error sudah diset di dalam loop
            }

        } catch (Exception $e) {
            $conn->rollback();
            $error_message = 'Terjadi kesalahan pada database: ' . $e->getMessage();
        }
    }
}


// Ambil data untuk dropdown
$kategori_list = $conn->query("SELECT * FROM kategori_pengaduan");
$rt_list = $conn->query("SELECT rt.id_rt, rt.nomor_rt, rw.nomor_rw FROM rt JOIN rw ON rt.id_rw = rw.id_rw ORDER BY rw.nomor_rw, rt.nomor_rt");

require_once __DIR__ . '/components/header.php';
?>

<h1 class="h3 mb-4">Buat Pengaduan Baru</h1>
<div class="card shadow">
    <div class="card-body">
        <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <form action="buat_pengaduan.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="id_kategori" class="form-label">Kategori Pengaduan</label>
                <select name="id_kategori" id="id_kategori" class="form-select" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php while($k = $kategori_list->fetch_assoc()): ?>
                    <option value="<?php echo $k['id_kategori']; ?>">
                        <?php echo htmlspecialchars($k['nama_kategori']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="id_rt_lokasi" class="form-label">Lokasi RT Kejadian</label>
                <select name="id_rt_lokasi" id="id_rt_lokasi" class="form-select" required>
                    <option value="">-- Pilih RT Lokasi Kejadian --</option>
                    <?php while($rt = $rt_list->fetch_assoc()): ?>
                    <option value="<?php echo $rt['id_rt']; ?>">RT <?php echo $rt['nomor_rt']; ?> / RW
                        <?php echo $rt['nomor_rw']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="lokasi_lengkap" class="form-label">Detail Lokasi (Contoh: Depan rumah No. 15)</label>
                <input type="text" name="lokasi_lengkap" id="lokasi_lengkap" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="deskripsi" class="form-label">Deskripsi Lengkap Masalah</label>
                <textarea name="deskripsi" id="deskripsi" class="form-control" rows="5" required></textarea>
            </div>
            <div class="mb-3">
                <label for="bukti_pendukung" class="form-label">Foto Bukti (Maks 5MB per file, tipe: JPG, PNG)</label>
                <input type="file" name="bukti_pendukung[]" id="bukti_pendukung" class="form-control" multiple>
            </div>
            <div class="mb-3">
                <label class="form-label">Tujuan Laporan</label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="tujuan" id="tujuan_rt" value="rt" checked>
                    <label class="form-check-label" for="tujuan_rt">Kirim ke Pengurus RT</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="tujuan" id="tujuan_rw" value="rw">
                    <label class="form-check-label" for="tujuan_rw">Kirim ke Pengurus RW (untuk masalah lebih
                        besar)</label>
                </div>
            </div>
            <button type="submit" name="kirim_pengaduan" class="btn btn-primary">Kirim Laporan</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../components/scripts.php'; ?>
</body>

</html>