<?php
// auth/logout.php

// 1. Mulai sesi untuk bisa mengakses dan menghancurkannya.
// Ini adalah langkah pertama yang wajib ada.
session_start();

// 2. Kosongkan semua variabel sesi.
// Ini untuk memastikan semua data seperti user_id, nama_lengkap, user_role benar-benar hilang dari array $_SESSION.
$_SESSION = array();

// 3. Hancurkan sesi.
// Perintah ini akan menghapus file sesi dari server. Ini adalah langkah logout yang sesungguhnya.
session_destroy();

// 4. Arahkan (redirect) pengguna kembali ke halaman login.
// Setelah sesi dihancurkan, pengguna tidak boleh berada di halaman admin lagi.
header("location: login.php");

// 5. Hentikan eksekusi skrip.
// Ini penting untuk memastikan tidak ada kode lain yang berjalan setelah redirect.
exit;

?>