<?php
// install_admin.php
// Skrip ini untuk dijalankan sekali saja untuk membuat user admin pertama.

// Menggunakan path koneksi yang sudah kita buat
require 'config/database.php'; 

echo "<h1>Membuat Admin Awal untuk Sistem Pengaduan...</h1>";

// 1. Mencari ID untuk peran 'Admin' dari tabel 'roles'
$role_name_to_find = 'Admin';
$role_query = "SELECT id_role FROM roles WHERE nama_role = ? LIMIT 1";
$stmt_role = mysqli_prepare($conn, $role_query);
mysqli_stmt_bind_param($stmt_role, 's', $role_name_to_find);
mysqli_stmt_execute($stmt_role);
$result_role = mysqli_stmt_get_result($stmt_role);

if (mysqli_num_rows($result_role) == 0) {
    die("<p style='color:red;'>Error: Peran 'Admin' tidak ditemukan di tabel 'roles'. Pastikan Anda sudah mengisi tabel 'roles' terlebih dahulu.</p>");
}

$admin_role_id = mysqli_fetch_assoc($result_role)['id_role'];
mysqli_stmt_close($stmt_role);


// 2. Mendefinisikan data untuk admin sesuai struktur tabel 'users' kita
$nama_lengkap  = 'Administrator Sistem';
$nik           = '0000000000000001'; // NIK 16 digit untuk login admin. Bisa diganti.
$nomor_telepon = '080012345678';   // Kolom ini NOT NULL, jadi harus diisi.
$password      = 'admin123';         // Password yang mudah diingat untuk awal.

// 3. Mengubah password menjadi hash yang aman
$hashed_password = password_hash($password, PASSWORD_DEFAULT);


// 4. Kueri INSERT disesuaikan dengan tabel 'users'
// Kolom id_rt dan id_rw kita biarkan NULL untuk Admin
$query = "INSERT INTO users (nama_lengkap, nik, nomor_telepon, password, id_role) 
          VALUES (?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $query);

// 5. Bind parameter ke statement (sesuaikan tipe data: s = string, i = integer)
mysqli_stmt_bind_param($stmt, 'ssssi', $nama_lengkap, $nik, $nomor_telepon, $hashed_password, $admin_role_id);


// 6. Menjalankan kueri dan memberi pesan output yang sesuai
if(mysqli_stmt_execute($stmt)) {
    echo "<p style='color:green; font-size:18px;'>Pengguna 'Administrator Sistem' berhasil dibuat!</p>";
    echo "<p>Anda sekarang bisa login melalui halaman <a href='auth/login.php'>login.php</a> menggunakan:</p>";
    echo "<ul>";
    echo "<li><b>NIK:</b> $nik</li>";
    echo "<li><b>Password:</b> $password</li>";
    echo "</ul>";
    echo "<p style='color:red; font-weight:bold; border: 2px solid red; padding: 10px;'>";
    echo "PENTING: Segera hapus file 'install_admin.php' ini dari server Anda demi keamanan!";
    echo "</p>";
} else {
    // Pesan error jika gagal, kemungkinan karena NIK sudah ada
    if(mysqli_errno($conn) == 1062) { // 1062 adalah kode error untuk duplicate entry
        echo "<p style='color:red;'>Error: Pengguna dengan NIK <strong>'$nik'</strong> sudah ada di dalam database Anda.</p>";
    } else {
        echo "<p style='color:red;'>Error: " . mysqli_error($conn) . "</p>";
    }
}

// 7. Menutup koneksi
mysqli_stmt_close($stmt);
mysqli_close($conn);

?>