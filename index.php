<?php
// File: index.php (di folder utama proyek)
//
// Tugas file ini adalah sebagai "gerbang utama".
// Siapapun yang mengakses root website akan langsung diarahkan (redirect) ke halaman login.

// Mengarahkan browser ke halaman login yang ada di dalam folder 'auth'
header("Location: auth/login.php");

// Penting: Hentikan eksekusi skrip setelah redirect untuk memastikan
// tidak ada kode atau konten lain yang tidak sengaja berjalan atau ditampilkan.
exit();

?>