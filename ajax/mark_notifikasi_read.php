<?php
// ajax/mark_notifikasi_read.php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) { exit(); }

$id_user = $_SESSION['user_id'];
$conn->query("UPDATE notifikasi SET sudah_dibaca = 1 WHERE id_user_penerima = $id_user AND sudah_dibaca = 0");

echo json_encode(['status' => 'success']);
?>