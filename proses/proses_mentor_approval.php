<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set zona waktu Indonesia & sembunyikan peringatan Deprecated PHP 8+
date_default_timezone_set('Asia/Jakarta');
error_reporting(E_ALL & ~E_DEPRECATED);

require_once __DIR__ . '/../config/koneksi.php';

// Validasi Hak Akses Mentor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'mentor') {
    header("Location: ../login-mentor.php");
    exit;
}

$mentor_id   = $_SESSION['user_id'];
$nama_mentor = $_SESSION['nama_user'] ?? 'Mentor Bimbingan';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// =========================================================================
// 1. LOGIKA PENERIMAAN AKSI (POST) - SETUJUI / REVISI TUGAS & SIMPAN PARAF
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validasi CSRF Token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Sesi Berakhir',
            'message' => 'Permintaan tidak valid. Silakan muat ulang halaman.'
        ];
        header("Location: ../mentor/approval.php");
        exit;
    }

    // Ambil data dari Form (Fleksibel mendukung 'status_approval' maupun 'keputusan')
    $id_tugas_detail = intval($_POST['id_tugas'] ?? $_POST['id_tugas_detail'] ?? 0);
    $keputusan       = trim($_POST['status_approval'] ?? $_POST['keputusan'] ?? '');
    $catatan_mentor  = trim($_POST['catatan_mentor'] ?? '');
    $paraf_base64    = trim($_POST['paraf_base64'] ?? '');

    if ($id_tugas_detail <= 0 || !in_array($keputusan, ['Disetujui', 'Perlu Revisi'])) {
        $_SESSION['alert'] = [
            'type' => 'warning',
            'title' => 'Data Tidak Valid',
            'message' => 'Pilihan keputusan approval tidak dikenali.'
        ];
        header("Location: ../mentor/approval.php");
        exit;
    }

    // Update Status Approval, Catatan Penilaian, dan Paraf Digital Mentor
    $stmt = $conn->prepare("UPDATE tugas_detail SET status_approval = ?, catatan_mentor = ?, paraf_mentor = ?, updated_at = NOW() WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("sssi", $keputusan, $catatan_mentor, $paraf_base64, $id_tugas_detail);

        if ($stmt->execute()) {
            $pesan_status = ($keputusan === 'Disetujui') ? 'Logbook/tugas berhasil disetujui & diparaf.' : 'Instruksi revisi telah dikirim ke mahasiswa.';
            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => 'Keputusan Disimpan!',
                'message' => $pesan_status
            ];
        } else {
            $_SESSION['alert'] = [
                'type' => 'error',
                'title' => 'Gagal Memproses',
                'message' => 'Terjadi kesalahan sistem: ' . $stmt->error
            ];
        }

        $stmt->close();
    } else {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Gagal Query Database',
            'message' => 'Kesalahan SQL: ' . $conn->error
        ];
    }

    header("Location: ../mentor/approval.php");
    exit;
}

// =========================================================================
// 2. LOGIKA PENYEDIAAN DATA VIEW (GET) - TABEL APPROVAL
// =========================================================================
$submission_pagi = [];
$submission_sore = [];

$query_submission = $conn->query("
    SELECT td.*, t.judul_tugas, u.nama_user, u.nim, u.kelas
    FROM tugas_detail td
    JOIN tugas t ON td.tugas_id = t.id
    JOIN users u ON td.user_id = u.id
    WHERE t.mentor_id = '$mentor_id'
    ORDER BY td.waktu_kirim DESC
");

if ($query_submission && $query_submission->num_rows > 0) {
    while ($row = $query_submission->fetch_assoc()) {
        
        // Pengecekan NULL-safe pada fungsi strtotime() untuk PHP 8.1+
        $jam_kirim = !empty($row['waktu_kirim']) ? date('H:i:s', strtotime($row['waktu_kirim'])) : '00:00:00';
        $sesi_batch = $row['sesi_batch'] ?? '';

        // Pengelompokan Batch berdasarkan Sesi atau Jam Kirim
        if ($sesi_batch === 'Pagi' || ($jam_kirim !== '00:00:00' && $jam_kirim <= '12:00:00')) {
            $submission_pagi[] = $row;
        } else {
            $submission_sore[] = $row;
        }
    }
}
?>