<?php
// 1. Inisialisasi Sesi & Koneksi
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/koneksi.php';

// 2. Proteksi Hak Akses Role Mentor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'mentor') {
    header("Location: ../login-mentor.php");
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mentor_id   = $_SESSION['user_id'];
$nama_mentor = $_SESSION['nama_user'] ?? 'Mentor Bimbingan';

// =========================================================================
// AMBIL DATA PROFIL & JABATAN MENTOR DARI DATABASE / SESI
// =========================================================================
$jabatan_mentor = $_SESSION['jabatan'] ?? '';

// Jika di sesi belum ada, lakukan query ke database
if (empty($jabatan_mentor)) {
    $stmt_m = $conn->prepare("SELECT nama_mentor, jabatan FROM mentors WHERE id = ?");
    if ($stmt_m) {
        $stmt_m->bind_param("i", $mentor_id);
        $stmt_m->execute();
        $res_m = $stmt_m->get_result();
        if ($row_m = $res_m->fetch_assoc()) {
            $nama_mentor    = !empty($row_m['nama_mentor']) ? $row_m['nama_mentor'] : $nama_mentor;
            $jabatan_mentor = !empty($row_m['jabatan']) ? $row_m['jabatan'] : 'Pembimbing Lapangan';
            $_SESSION['jabatan']   = $jabatan_mentor;
            $_SESSION['nama_user'] = $nama_mentor;
        }
        $stmt_m->close();
    }
}

// Fallback default jika data masih kosong
if (empty($jabatan_mentor)) {
    $jabatan_mentor = 'Pembimbing Lapangan';
}

// =========================================================================
// 3. QUERY STATISTIK KPI (DIPERBAIKI LOGIKANYA)
// =========================================================================

// Total Mahasiswa
$q_mhs = $conn->query("SELECT COUNT(id) as total FROM users");
$total_mhs = ($q_mhs) ? $q_mhs->fetch_assoc()['total'] : 0;

// Tugas Pending (Difilter sesuai mentor yang login)
$q_pending = $conn->query("
    SELECT COUNT(td.id) as total 
    FROM tugas_detail td 
    JOIN tugas t ON td.tugas_id = t.id 
    WHERE td.status_approval = 'Menunggu Review' AND t.mentor_id = '$mentor_id'
");
$total_pending = ($q_pending) ? $q_pending->fetch_assoc()['total'] : 0;

// Total Bimbingan Hari Ini
$q_bimbingan = $conn->query("
    SELECT COUNT(id) as total 
    FROM bimbingan 
    WHERE mentor_id = '$mentor_id' AND DATE(tanggal_waktu) = CURDATE()
");
$total_bimbingan_hari_ini = ($q_bimbingan) ? $q_bimbingan->fetch_assoc()['total'] : 0;

// Tugas Disetujui (Difilter sesuai mentor yang login)
$q_approved = $conn->query("
    SELECT COUNT(td.id) as total 
    FROM tugas_detail td 
    JOIN tugas t ON td.tugas_id = t.id 
    WHERE td.status_approval = 'Disetujui' AND t.mentor_id = '$mentor_id'
");
$total_approved = ($q_approved) ? $q_approved->fetch_assoc()['total'] : 0;

// =========================================================================
// 4. AMBIL DATA DINAMIS UNTUK TABEL & LIST DASHBOARD
// =========================================================================

// A. Ambil Antrean Review Tugas (Maksimal 5 Terbaru)
$antrean_tugas = [];
$q_antrean = $conn->query("
    SELECT td.id as detail_id, u.nama_user, t.judul_tugas, td.waktu_kirim 
    FROM tugas_detail td 
    JOIN tugas t ON td.tugas_id = t.id 
    JOIN users u ON td.user_id = u.id 
    WHERE td.status_approval = 'Menunggu Review' AND t.mentor_id = '$mentor_id'
    ORDER BY td.waktu_kirim ASC LIMIT 5
");
if ($q_antrean && $q_antrean->num_rows > 0) {
    while ($row = $q_antrean->fetch_assoc()) {
        $antrean_tugas[] = $row;
    }
}

// B. Ambil Agenda Bimbingan Hari Ini
$agenda_bimbingan = [];
$q_agenda = $conn->query("
    SELECT b.*, u.nama_user 
    FROM bimbingan b 
    JOIN users u ON b.user_id = u.id 
    WHERE b.mentor_id = '$mentor_id' AND DATE(b.tanggal_waktu) = CURDATE()
    ORDER BY b.tanggal_waktu ASC
");
if ($q_agenda && $q_agenda->num_rows > 0) {
    while ($row = $q_agenda->fetch_assoc()) {
        $agenda_bimbingan[] = $row;
    }
}
?>