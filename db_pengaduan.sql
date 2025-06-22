-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 21, 2025 at 12:45 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_pengaduan`
--

-- --------------------------------------------------------

--
-- Table structure for table `bukti_pendukung`
--

CREATE TABLE `bukti_pendukung` (
  `id_bukti` int NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `id_pengaduan` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bukti_pendukung`
--

INSERT INTO `bukti_pendukung` (`id_bukti`, `file_path`, `id_pengaduan`) VALUES
(3, 'uploads/bukti/1750477315_th (2).jpeg', 3),
(4, 'uploads/bukti/1750477431_th (2).jpeg', 4),
(5, 'uploads/bukti/1750483600_clas diagram.png', 5),
(6, 'uploads/bukti/1750485070_th (2).jpeg', 6);

-- --------------------------------------------------------

--
-- Table structure for table `kategori_pengaduan`
--

CREATE TABLE `kategori_pengaduan` (
  `id_kategori` int NOT NULL,
  `nama_kategori` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kategori_pengaduan`
--

INSERT INTO `kategori_pengaduan` (`id_kategori`, `nama_kategori`) VALUES
(1, 'Jalan'),
(3, 'Rumput');

-- --------------------------------------------------------

--
-- Table structure for table `komentar_pengaduan`
--

CREATE TABLE `komentar_pengaduan` (
  `id_komentar` int NOT NULL,
  `id_pengaduan` int NOT NULL,
  `id_user_pengirim` int NOT NULL,
  `isi_komentar` text NOT NULL,
  `tanggal_kirim` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `komentar_pengaduan`
--

INSERT INTO `komentar_pengaduan` (`id_komentar`, `id_pengaduan`, `id_user_pengirim`, `isi_komentar`, `tanggal_kirim`) VALUES
(2, 4, 1, 'siap sedang di proses', '2025-06-21 03:44:24'),
(3, 4, 3, 'baik pak siap', '2025-06-21 03:45:57'),
(4, 5, 4, 'proses', '2025-06-21 05:46:57'),
(5, 4, 5, 'otw', '2025-06-21 06:30:40'),
(6, 6, 4, 'siap saya kerjakan\\r\\n', '2025-06-21 06:31:44'),
(7, 6, 4, 'sudah siap ya ', '2025-06-21 11:44:03'),
(8, 6, 3, 'siap pak', '2025-06-21 12:27:13');

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id_notifikasi` int NOT NULL,
  `id_user_penerima` int NOT NULL,
  `id_pengaduan` int NOT NULL,
  `pesan` varchar(255) NOT NULL,
  `sudah_dibaca` tinyint(1) NOT NULL DEFAULT '0',
  `tanggal_dibuat` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifikasi`
--

INSERT INTO `notifikasi` (`id_notifikasi`, `id_user_penerima`, `id_pengaduan`, `pesan`, `sudah_dibaca`, `tanggal_dibuat`) VALUES
(1, 3, 6, 'Status laporan Anda #6 telah diubah menjadi \'Diproses\'.', 1, '2025-06-21 12:23:34'),
(2, 3, 6, 'Status laporan Anda #6 telah diubah menjadi \'Ditolak\'.', 1, '2025-06-21 12:30:59'),
(3, 3, 4, 'Status laporan Anda #4 telah diubah oleh Petugas RW menjadi \'Diterima\'.', 1, '2025-06-21 12:34:00');

-- --------------------------------------------------------

--
-- Table structure for table `pengaduan`
--

CREATE TABLE `pengaduan` (
  `id_pengaduan` int NOT NULL,
  `deskripsi` text NOT NULL,
  `lokasi_lengkap` varchar(255) NOT NULL,
  `tanggal_lapor` date NOT NULL,
  `status` enum('Diterima','Diproses','Selesai','Ditolak') NOT NULL DEFAULT 'Diterima',
  `id_user_pelapor` int NOT NULL,
  `id_kategori` int NOT NULL,
  `id_rt_lokasi` int NOT NULL,
  `tujuan_id_rt` int DEFAULT NULL,
  `tujuan_id_rw` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pengaduan`
--

INSERT INTO `pengaduan` (`id_pengaduan`, `deskripsi`, `lokasi_lengkap`, `tanggal_lapor`, `status`, `id_user_pelapor`, `id_kategori`, `id_rt_lokasi`, `tujuan_id_rt`, `tujuan_id_rw`, `updated_at`) VALUES
(3, 'jalan rusak', 'depan rumah no 15', '2025-06-21', 'Diterima', 3, 1, 1, 1, NULL, '2025-06-21 03:41:55'),
(4, 'dwdwd', 'depan rumah no 15', '2025-06-21', 'Diterima', 3, 1, 1, 1, NULL, '2025-06-21 12:34:00'),
(5, 'ddwdd', 'depan rumah', '2025-06-21', 'Diproses', 3, 1, 2, 2, NULL, '2025-06-21 05:47:58'),
(6, 'dwdwdd', 'depan rumah', '2025-06-21', 'Ditolak', 3, 3, 2, 2, NULL, '2025-06-21 12:30:59');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id_role` int NOT NULL,
  `nama_role` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id_role`, `nama_role`) VALUES
(4, 'Admin'),
(1, 'Masyarakat'),
(2, 'RT'),
(3, 'RW');

-- --------------------------------------------------------

--
-- Table structure for table `rt`
--

CREATE TABLE `rt` (
  `id_rt` int NOT NULL,
  `nomor_rt` varchar(5) NOT NULL,
  `nama_ketua_rt` varchar(100) DEFAULT NULL,
  `id_rw` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `rt`
--

INSERT INTO `rt` (`id_rt`, `nomor_rt`, `nama_ketua_rt`, `id_rw`) VALUES
(1, '001', 'Bintang', 1),
(2, '002', 'hahh', 2);

-- --------------------------------------------------------

--
-- Table structure for table `rw`
--

CREATE TABLE `rw` (
  `id_rw` int NOT NULL,
  `nomor_rw` varchar(5) NOT NULL,
  `nama_ketua_rw` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `rw`
--

INSERT INTO `rw` (`id_rw`, `nomor_rw`, `nama_ketua_rw`) VALUES
(1, '001', 'Pak rahmat'),
(2, '002', 'Bintangpro');

-- --------------------------------------------------------

--
-- Table structure for table `tindak_lanjut`
--

CREATE TABLE `tindak_lanjut` (
  `id_tindak_lanjut` int NOT NULL,
  `id_pengaduan` int NOT NULL,
  `id_user_petugas` int NOT NULL,
  `keterangan` text NOT NULL,
  `foto_hasil` varchar(255) DEFAULT NULL,
  `tanggal_aksi` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tindak_lanjut`
--

INSERT INTO `tindak_lanjut` (`id_tindak_lanjut`, `id_pengaduan`, `id_user_petugas`, `keterangan`, `foto_hasil`, `tanggal_aksi`) VALUES
(1, 4, 1, 'sedang di kerjakan', 'uploads/hasil/1750477478_dfd.drawio.png', '2025-06-21 03:44:38'),
(2, 4, 1, 'sudah ya sudah di perbaiki', 'uploads/hasil/1750480134_th (2).jpeg', '2025-06-21 04:28:54'),
(3, 5, 4, 'proses yak', 'uploads/hasil/1750484833_clas diagram.png', '2025-06-21 05:47:13');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `nomor_telepon` varchar(20) NOT NULL,
  `nik` varchar(16) NOT NULL,
  `password` varchar(255) NOT NULL,
  `id_role` int NOT NULL,
  `id_rt` int DEFAULT NULL,
  `id_rw` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `nama_lengkap`, `nomor_telepon`, `nik`, `password`, `id_role`, `id_rt`, `id_rw`, `created_at`) VALUES
(1, 'Administrator Sistem', '080012345678', '0000000000000004', '$2y$10$qTV0Lb2CJGIYijlrNqRD8OiYyBgczVmlCEPmnPrRZJ8bD82I7l9XC', 4, NULL, NULL, '2025-06-20 16:29:59'),
(3, 'bintang', '08227362362', '0000000000000001', '$2y$10$EGD3IPVcpZVkZU/NMVcLM.l53i5.frsQQMQ7Qwp2ssD5VnPxwawK2', 1, 1, NULL, '2025-06-21 03:25:51'),
(4, 'HENDRA RT', '372743', '0000000000000002', '$2y$10$aVCVqtTGqEoD7Vcl2Z1NSOnGkSZeJ704I8Widpgq2xYDX6zGrT.s2', 2, 2, NULL, '2025-06-21 04:31:11'),
(5, 'HENDRA RW', '353234', '0000000000000003', '$2y$10$A4Esm.9zAgb4uitNyD8L8.S.e5X1k/fZF4Yt1GuslKh3ZiP4ST7oe', 3, NULL, 1, '2025-06-21 04:32:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bukti_pendukung`
--
ALTER TABLE `bukti_pendukung`
  ADD PRIMARY KEY (`id_bukti`),
  ADD KEY `fk_bukti_pengaduan2` (`id_pengaduan`);

--
-- Indexes for table `kategori_pengaduan`
--
ALTER TABLE `kategori_pengaduan`
  ADD PRIMARY KEY (`id_kategori`),
  ADD UNIQUE KEY `nama_kategori` (`nama_kategori`);

--
-- Indexes for table `komentar_pengaduan`
--
ALTER TABLE `komentar_pengaduan`
  ADD PRIMARY KEY (`id_komentar`),
  ADD KEY `fk_komentar_pengaduan` (`id_pengaduan`),
  ADD KEY `fk_komentar_user` (`id_user_pengirim`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id_notifikasi`),
  ADD KEY `id_user_penerima` (`id_user_penerima`),
  ADD KEY `id_pengaduan` (`id_pengaduan`);

--
-- Indexes for table `pengaduan`
--
ALTER TABLE `pengaduan`
  ADD PRIMARY KEY (`id_pengaduan`),
  ADD KEY `fk_pengaduan_user` (`id_user_pelapor`),
  ADD KEY `fk_pengaduan_kategori2` (`id_kategori`),
  ADD KEY `fk_pengaduan_rt_lokasi2` (`id_rt_lokasi`),
  ADD KEY `fk_pengaduan_tujuan_rt` (`tujuan_id_rt`),
  ADD KEY `fk_pengaduan_tujuan_rw` (`tujuan_id_rw`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_role`),
  ADD UNIQUE KEY `nama_role` (`nama_role`);

--
-- Indexes for table `rt`
--
ALTER TABLE `rt`
  ADD PRIMARY KEY (`id_rt`),
  ADD KEY `fk_rt_rw` (`id_rw`);

--
-- Indexes for table `rw`
--
ALTER TABLE `rw`
  ADD PRIMARY KEY (`id_rw`);

--
-- Indexes for table `tindak_lanjut`
--
ALTER TABLE `tindak_lanjut`
  ADD PRIMARY KEY (`id_tindak_lanjut`),
  ADD KEY `fk_tindaklanjut_pengaduan` (`id_pengaduan`),
  ADD KEY `fk_tindaklanjut_user` (`id_user_petugas`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `nik_unique` (`nik`),
  ADD KEY `fk_users_role` (`id_role`),
  ADD KEY `fk_users_rt` (`id_rt`),
  ADD KEY `fk_users_rw` (`id_rw`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bukti_pendukung`
--
ALTER TABLE `bukti_pendukung`
  MODIFY `id_bukti` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `kategori_pengaduan`
--
ALTER TABLE `kategori_pengaduan`
  MODIFY `id_kategori` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `komentar_pengaduan`
--
ALTER TABLE `komentar_pengaduan`
  MODIFY `id_komentar` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notifikasi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pengaduan`
--
ALTER TABLE `pengaduan`
  MODIFY `id_pengaduan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id_role` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `rt`
--
ALTER TABLE `rt`
  MODIFY `id_rt` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `rw`
--
ALTER TABLE `rw`
  MODIFY `id_rw` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tindak_lanjut`
--
ALTER TABLE `tindak_lanjut`
  MODIFY `id_tindak_lanjut` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bukti_pendukung`
--
ALTER TABLE `bukti_pendukung`
  ADD CONSTRAINT `fk_bukti_pengaduan2` FOREIGN KEY (`id_pengaduan`) REFERENCES `pengaduan` (`id_pengaduan`) ON DELETE CASCADE;

--
-- Constraints for table `komentar_pengaduan`
--
ALTER TABLE `komentar_pengaduan`
  ADD CONSTRAINT `fk_komentar_pengaduan` FOREIGN KEY (`id_pengaduan`) REFERENCES `pengaduan` (`id_pengaduan`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_komentar_user` FOREIGN KEY (`id_user_pengirim`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `notifikasi_ibfk_1` FOREIGN KEY (`id_user_penerima`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifikasi_ibfk_2` FOREIGN KEY (`id_pengaduan`) REFERENCES `pengaduan` (`id_pengaduan`) ON DELETE CASCADE;

--
-- Constraints for table `pengaduan`
--
ALTER TABLE `pengaduan`
  ADD CONSTRAINT `fk_pengaduan_kategori2` FOREIGN KEY (`id_kategori`) REFERENCES `kategori_pengaduan` (`id_kategori`),
  ADD CONSTRAINT `fk_pengaduan_rt_lokasi2` FOREIGN KEY (`id_rt_lokasi`) REFERENCES `rt` (`id_rt`),
  ADD CONSTRAINT `fk_pengaduan_tujuan_rt` FOREIGN KEY (`tujuan_id_rt`) REFERENCES `rt` (`id_rt`),
  ADD CONSTRAINT `fk_pengaduan_tujuan_rw` FOREIGN KEY (`tujuan_id_rw`) REFERENCES `rw` (`id_rw`),
  ADD CONSTRAINT `fk_pengaduan_user` FOREIGN KEY (`id_user_pelapor`) REFERENCES `users` (`id_user`);

--
-- Constraints for table `rt`
--
ALTER TABLE `rt`
  ADD CONSTRAINT `fk_rt_rw` FOREIGN KEY (`id_rw`) REFERENCES `rw` (`id_rw`);

--
-- Constraints for table `tindak_lanjut`
--
ALTER TABLE `tindak_lanjut`
  ADD CONSTRAINT `fk_tindaklanjut_pengaduan` FOREIGN KEY (`id_pengaduan`) REFERENCES `pengaduan` (`id_pengaduan`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tindaklanjut_user` FOREIGN KEY (`id_user_petugas`) REFERENCES `users` (`id_user`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`id_role`) REFERENCES `roles` (`id_role`),
  ADD CONSTRAINT `fk_users_rt` FOREIGN KEY (`id_rt`) REFERENCES `rt` (`id_rt`),
  ADD CONSTRAINT `fk_users_rw` FOREIGN KEY (`id_rw`) REFERENCES `rw` (`id_rw`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
