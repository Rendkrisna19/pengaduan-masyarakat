<?php
require_once __DIR__ . '/../../config/database.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin' || !isset($_GET['id'])) {
    echo json_encode(['error' => 'Akses ditolak atau ID tidak valid.']);
    exit();
}

$id = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT * FROM rw WHERE id_rw = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($data = $result->fetch_assoc()) {
    echo json_encode($data);
} else {
    echo json_encode(['error' => 'Data tidak ditemukan.']);
}
$stmt->close();
$conn->close();
?>