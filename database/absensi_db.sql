-- ========================================================
-- DATABASE: absensi_db
-- UTB Tracker - Sistem Absensi & Evaluasi Magang (Testing Phase)
-- ========================================================

CREATE DATABASE IF NOT EXISTS `absensi_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `absensi_db`;

-- Mencegah bentrok Relasi Foreign Key saat Import Ulang
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `tugas_detail`;
DROP TABLE IF EXISTS `tugas`;
DROP TABLE IF EXISTS `bimbingan`;
DROP TABLE IF EXISTS `mentors`;
DROP TABLE IF EXISTS `milestones`;
DROP TABLE IF EXISTS `agenda`;
DROP TABLE IF EXISTS `kehadiran`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- 1. Struktur Tabel `users` (Mahasiswa Bimbingan)
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_user` varchar(100) NOT NULL,
  `nim` varchar(20) NOT NULL,
  `kelas` varchar(50) NOT NULL,
  `konsentrasi` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `pin` varchar(255) NOT NULL, 
  `failed_attempts` int(11) NOT NULL DEFAULT 0, 
  `lockout_time` datetime DEFAULT NULL, 
  PRIMARY KEY (`id`),
  UNIQUE KEY `nim_unik` (`nim`),
  UNIQUE KEY `email_unik` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data Awal Mahasiswa (Dipertahankan untuk Testing)
INSERT INTO `users` (`id`, `nama_user`, `nim`, `kelas`, `konsentrasi`, `email`, `pin`, `failed_attempts`, `lockout_time`) VALUES
(1, 'Alvin Nurfaiz', '232101111', 'TiF 23 CNS J', 'Computer and Network Security', 'alvin.nurfaiz@student.utb.ac.id', '$2y$10$771J.TR8EnLcoC2arj3gM.POiqGua6aT8J/c2naogZ8xB8ptOeQkG', 0, NULL),
(2, 'M. Yusman Bayuga', '232101145', 'TiF 23 CiD G', 'Creative Interactive Design', 'yusman.bayuga@student.utb.ac.id', '$2y$10$771J.TR8EnLcoC2arj3gM.POiqGua6aT8J/c2naogZ8xB8ptOeQkG', 0, NULL);


-- --------------------------------------------------------
-- 2. Struktur Tabel `kehadiran` (Log Presensi Harian)
-- --------------------------------------------------------
CREATE TABLE `kehadiran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu_masuk` time NOT NULL,
  `waktu_keluar` time DEFAULT NULL, 
  `status` enum('Hadir','Sakit','Izin','Lembur') NOT NULL, 
  `catatan` text DEFAULT NULL, 
  `status_approval` enum('Pending','Disetujui','Perlu Revisi') DEFAULT 'Pending',
  `catatan_mentor` text DEFAULT NULL, 
  `paraf_mentor` longtext DEFAULT NULL COMMENT 'Menyimpan format base64 gambar paraf', 
  `waktu_approval` datetime DEFAULT NULL, 
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_user_kehadiran` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Data dikosongkan untuk testing


-- --------------------------------------------------------
-- 3. Struktur Tabel `agenda` (Kalender Mandiri Mahasiswa)
-- --------------------------------------------------------
CREATE TABLE `agenda` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `kategori` enum('Industri','Kampus','Lembur') NOT NULL,
  `tanggal` date NOT NULL,
  `waktu` time NOT NULL, 
  `pengingat_offset` int(11) NOT NULL DEFAULT 12, 
  `deskripsi` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_user_agenda` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Data dikosongkan untuk testing


-- --------------------------------------------------------
-- 4. Struktur Tabel `milestones` (Target Bulanan Magang)
-- --------------------------------------------------------
CREATE TABLE `milestones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `bulan_key` varchar(2) NOT NULL, 
  `judul` varchar(50) NOT NULL,
  `status` enum('Pending','Berjalan','Selesai') NOT NULL DEFAULT 'Pending',
  `operasional` text DEFAULT NULL,
  `it` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_user_milestone` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Data dikosongkan untuk testing


-- --------------------------------------------------------
-- 5. Struktur Tabel `mentors` (Akun Pembimbing / Mentor)
-- --------------------------------------------------------
CREATE TABLE `mentors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `nama_mentor` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `pin` varchar(255) NOT NULL,
  `jabatan` varchar(100) DEFAULT 'Pembimbing Lapangan',
  `failed_attempts` int(11) NOT NULL DEFAULT 0,
  `lockout_time` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email_unik` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data Awal Mentor (Dipertahankan untuk Testing Approval/Tugas)
INSERT INTO `mentors` (`id`, `username`, `nama_mentor`, `email`, `pin`, `jabatan`, `failed_attempts`, `lockout_time`, `created_at`) VALUES
(1, 'mentor.alvin', 'Dr. Alvin Nurfaiz, M.T.', 'mentor.alvin@utb.ac.id', '1234', 'Pembimbing Lapangan BRI', 0, NULL, '2026-06-01 08:00:00'),
(2, 'mentor.bayuga', 'M. Yusman Bayuga, S.T., M.Kom.', 'mentor.bayuga@utb.ac.id', '1234', 'Pembimbing Akademik UTB', 0, NULL, '2026-06-01 08:00:00');


-- --------------------------------------------------------
-- 6. Struktur Tabel `bimbingan` (Catatan Revisi & Bimbingan)
-- --------------------------------------------------------
CREATE TABLE `bimbingan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mentor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tanggal_waktu` datetime NOT NULL,
  `topik` varchar(255) NOT NULL,
  `metode` enum('Tatap Muka','Online') NOT NULL DEFAULT 'Tatap Muka',
  `catatan_revisi` text DEFAULT NULL,
  `status` enum('Terjadwal','Selesai','Revisi') NOT NULL DEFAULT 'Terjadwal',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `mentor_id` (`mentor_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_bimbingan_mentor` FOREIGN KEY (`mentor_id`) REFERENCES `mentors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bimbingan_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Data dikosongkan untuk testing


-- --------------------------------------------------------
-- 7. Struktur Tabel `tugas` (Penugasan dari Mentor)
-- --------------------------------------------------------
CREATE TABLE `tugas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mentor_id` int(11) NOT NULL,
  `target_user_id` int(11) DEFAULT NULL,
  `judul_tugas` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `file_lampiran` varchar(255) DEFAULT NULL,
  `tenggat` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `mentor_id` (`mentor_id`),
  KEY `target_user_id` (`target_user_id`),
  CONSTRAINT `fk_tugas_mentor` FOREIGN KEY (`mentor_id`) REFERENCES `mentors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tugas_target_user` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Data dikosongkan untuk testing


-- --------------------------------------------------------
-- 8. Struktur Tabel `tugas_detail` (Pengumpulan & Approval)
-- --------------------------------------------------------
CREATE TABLE `tugas_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tugas_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `file_balasan` varchar(255) DEFAULT NULL,
  `waktu_kirim` datetime DEFAULT NULL,
  `status_approval` enum('Menunggu Review','Disetujui','Perlu Revisi','Belum Ada Berkas') NOT NULL DEFAULT 'Menunggu Review',
  `catatan_mentor` text DEFAULT NULL,
  `paraf_mentor` longtext DEFAULT NULL COMMENT 'Menyimpan format base64 gambar paraf', 
  `sesi_batch` enum('Pagi','Sore') DEFAULT 'Pagi',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tugas_id` (`tugas_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_tugas_detail_tugas` FOREIGN KEY (`tugas_id`) REFERENCES `tugas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tugas_detail_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Data dikosongkan untuk testing