<?php

session_start();


require_once __DIR__ . '/../config/database.php';



// LOGIKA UNTUK PROSES UPDATE PENGGUNA (DARI MODAL)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_user') {
    // ... (Logika update yang sudah ada, tidak perlu diubah) ...
    $id_user = (int)$_POST['id_user'];
    $nama_lengkap = $conn->real_escape_string($_POST['nama_lengkap']);
    $nik = $conn->real_escape_string($_POST['nik']);
    $nomor_telepon = $conn->real_escape_string($_POST['nomor_telepon']);
    $password = $_POST['password'];

    if (empty($nama_lengkap) || empty($nik) || empty($nomor_telepon) || strlen($nik) != 16) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Data tidak valid. Pastikan semua field terisi dan NIK 16 digit.'];
    } else {
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET nama_lengkap = ?, nik = ?, nomor_telepon = ?, password = ? WHERE id_user = ?");
            $stmt->bind_param("ssssi", $nama_lengkap, $nik, $nomor_telepon, $hashed_password, $id_user);
        } else {
            $stmt = $conn->prepare("UPDATE users SET nama_lengkap = ?, nik = ?, nomor_telepon = ? WHERE id_user = ?");
            $stmt->bind_param("sssi", $nama_lengkap, $nik, $nomor_telepon, $id_user);
        }

        if ($stmt->execute()) {
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Data pengguna berhasil diperbarui.'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal memperbarui data: ' . $stmt->error];
        }
        $stmt->close();
    }
    // Setelah proses selesai, kirim header redirect SEBELUM ada output HTML
    header("Location: daftar_pengguna.php");
    exit();
}

// LOGIKA UNTUK MENGHAPUS PENGGUNA
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    // ... (Logika delete yang sudah ada, tidak perlu diubah) ...
    $id_to_delete = (int)$_GET['id'];
    if ($id_to_delete == $_SESSION['user_id']) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Anda tidak dapat menghapus akun Anda sendiri.'];
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id_user = ?");
        $stmt->bind_param("i", $id_to_delete);
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Pengguna berhasil dihapus.'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal menghapus pengguna. Kemungkinan terhubung dengan data pengaduan.'];
        }
        $stmt->close();
    }
    // Setelah proses selesai, kirim header redirect SEBELUM ada output HTML
    header("Location: daftar_pengguna.php");
    exit();
}


// 3. BARU SETELAH SEMUA LOGIKA PROSES SELESAI, KITA PANGGIL HEADER UNTUK MULAI MENGGAMBAR HALAMAN
require_once __DIR__ . '/../components/header.php';

// =================================================================
// BAGIAN KONTROL TAMPILAN (Daftar Pengguna atau Form Edit)
// Kode ini sama seperti sebelumnya, tidak ada yang diubah.
// =================================================================
$action = $_GET['action'] ?? 'list';

if ($action == 'edit' && isset($_GET['id'])) {
    // Tampilkan Form Edit
    $id_to_edit = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
    $stmt->bind_param("i", $id_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_to_edit = $result->fetch_assoc();
    $stmt->close();

    if (!$user_to_edit) {
        echo "<div class='alert alert-danger'>Pengguna tidak ditemukan.</div>";
    } else {
?>
<h3 class="mb-4">Edit Pengguna: <?php echo htmlspecialchars($user_to_edit['nama_lengkap']); ?></h3>
<div class="card shadow">
    <div class="card-body">
        <form action="daftar_pengguna.php" method="POST">
            <input type="hidden" name="id_user" value="<?php echo $user_to_edit['id_user']; ?>">
            <input type="hidden" name="action" value="update_user">
            <div class="mb-3">
                <label for="edit_nama_lengkap" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" id="edit_nama_lengkap" name="nama_lengkap"
                    value="<?php echo htmlspecialchars($user_to_edit['nama_lengkap']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="edit_nik" class="form-label">NIK</label>
                <input type="text" class="form-control" id="edit_nik" name="nik"
                    value="<?php echo htmlspecialchars($user_to_edit['nik']); ?>" required maxlength="16"
                    minlength="16">
            </div>
            <div class="mb-3">
                <label for="edit_nomor_telepon" class="form-label">Nomor Telepon</label>
                <input type="text" class="form-control" id="edit_nomor_telepon" name="nomor_telepon"
                    value="<?php echo htmlspecialchars($user_to_edit['nomor_telepon']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="edit_password" class="form-label">Password Baru (Opsional)</label>
                <input type="password" class="form-control" id="edit_password" name="password">
                <small class="form-text text-muted">Kosongkan jika tidak ingin mengubah password.</small>
            </div>

            <a href="daftar_pengguna.php" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </form>
    </div>
</div>
<?php
    }
} else {
    // Tampilkan Daftar Pengguna
    $query = "SELECT u.id_user, u.nama_lengkap, u.nik, u.nomor_telepon, r.nama_role, rt.nomor_rt, rw.nomor_rw FROM users u JOIN roles r ON u.id_role = r.id_role LEFT JOIN rt ON u.id_rt = rt.id_rt LEFT JOIN rw ON u.id_rw = rw.id_rw ORDER BY u.id_user DESC";
    $users = $conn->query($query);
?>
<div class="card shadow">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Daftar Pengguna Sistem</h6>
        <a href="create_pengguna.php" class="btn btn-primary btn-sm"><i class="fas fa-plus me-2"></i>Tambah Pengguna</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>NIK</th>
                        <th>Telepon</th>
                        <th>Role</th>
                        <th>Wilayah</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $user['id_user']; ?></td>
                        <td><?php echo htmlspecialchars($user['nama_lengkap']); ?></td>
                        <td><?php echo htmlspecialchars($user['nik']); ?></td>
                        <td><?php echo htmlspecialchars($user['nomor_telepon']); ?></td>
                        <td><span
                                class="badge bg-info text-dark"><?php echo htmlspecialchars($user['nama_role']); ?></span>
                        </td>
                        <td>
                            <?php
                                    if ($user['nomor_rt']) echo "RT " . $user['nomor_rt'] . " / RW " . $user['nomor_rw'];
                                    elseif ($user['nomor_rw']) echo "RW " . $user['nomor_rw'];
                                    else echo "-";
                                ?>
                        </td>
                        <td>
                            <a href="daftar_pengguna.php?action=edit&id=<?php echo $user['id_user']; ?>"
                                class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <button class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $user['id_user']; ?>"
                                data-nama="<?php echo htmlspecialchars($user['nama_lengkap']); ?>"><i
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
}
require_once __DIR__ . '/../components/footer.php';
?>