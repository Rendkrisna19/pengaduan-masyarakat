<?php


$host = 'localhost';    
$db_name = 'db_pengaduan';
$username = 'root';       
$password = '';            


$conn = new mysqli($host, $username, $password, $db_name);


if ($conn->connect_error) {
    die("Koneksi ke database gagal: " . $conn->connect_error);
}


$conn->set_charset("utf8mb4");

?>