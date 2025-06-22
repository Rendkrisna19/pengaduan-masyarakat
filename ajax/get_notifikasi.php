<?php
// ajax/get_notifikasi.php
session_start();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { die(json_encode([])); }

$id_user = $_SESSION['user_id'];
$query = "SELECT * FROM notifikasi WHERE id_user_penerima = ? ORDER BY tanggal_dibuat DESC LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
$notifikasi = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($notifikasi);
?>