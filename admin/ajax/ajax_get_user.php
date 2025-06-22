<?php
// admin/ajax/ajax_get_user.php
require_once __DIR__ . '/../../config/database.php';
session_start();

// Hanya admin yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    echo json_encode(['error' => 'Akses ditolak']);
    exit();
}

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT id_user, nama_lengkap, nik, nomor_telepon, id_role, id_rt, id_rw FROM users WHERE id_user = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
        echo json_encode($user);
    } else {
        echo json_encode(['error' => 'User tidak ditemukan']);
    }
    $stmt->close();
} else {
    echo json_encode(['error' => 'ID User tidak valid']);
}
$conn->close();
?>