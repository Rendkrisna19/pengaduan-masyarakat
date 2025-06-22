<?php
// admin/proses/proses_update_user.php
session_start();
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_user = (int)$_POST['id_user'];
    $nama_lengkap = $conn->real_escape_string($_POST['nama_lengkap']);
    $nik = $conn->real_escape_string($_POST['nik']);
    $nomor_telepon = $conn->real_escape_string($_POST['nomor_telepon']);
    $password = $_POST['password'];

    // Bangun query update
    if (!empty($password)) {
        // Jika password diisi, update dengan password baru yang di-hash
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET nama_lengkap = ?, nik = ?, nomor_telepon = ?, password = ? WHERE id_user = ?");
        $stmt->bind_param("ssssi", $nama_lengkap, $nik, $nomor_telepon, $hashed_password, $id_user);
    } else {
        // Jika password kosong, update tanpa mengubah password
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
$conn->close();
header("Location: ../daftar_pengguna.php");
exit();
?>