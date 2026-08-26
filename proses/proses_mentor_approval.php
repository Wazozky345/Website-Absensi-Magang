<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
$jabatan_mentor = $_SESSION['jabatan'] ?? 'Pembimbing Lapangan';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// =========================================================================
// 1. LOGIKA PENERIMAAN AKSI (POST) - SETUJUI / REVISI
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Sesi Berakhir',
            'message' => 'Permintaan tidak valid. Silakan muat ulang halaman.'
        ];
        header("Location: ../mentor/approval.php");
        exit;
    }

    $id_sumber       = intval($_POST['id_sumber'] ?? 0);
    $tipe_sumber     = trim($_POST['tipe_sumber'] ?? 'tugas');
    $keputusan       = trim($_POST['status_approval'] ?? '');
    $catatan_mentor  = trim($_POST['catatan_mentor'] ?? '');
    $paraf_base64    = trim($_POST['paraf_base64'] ?? '');

    if ($id_sumber <= 0 || !in_array($keputusan, ['Disetujui', 'Perlu Revisi'])) {
        $_SESSION['alert'] = [
            'type' => 'warning',
            'title' => 'Data Tidak Valid',
            'message' => 'Pilihan keputusan approval tidak dikenali.'
        ];
        header("Location: ../mentor/approval.php");
        exit;
    }

    if ($tipe_sumber === 'logbook') {
        $stmt = $conn->prepare("UPDATE kehadiran SET status_approval = ?, catatan_mentor = ?, paraf_mentor = ?, waktu_approval = NOW() WHERE id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE tugas_detail SET status_approval = ?, catatan_mentor = ?, paraf_mentor = ?, updated_at = NOW() WHERE id = ?");
    }

    if ($stmt) {
        $stmt->bind_param("sssi", $keputusan, $catatan_mentor, $paraf_base64, $id_sumber);

        if ($stmt->execute()) {
            $label_obj = ($tipe_sumber === 'logbook') ? 'Logbook harian' : 'Tugas';
            $pesan_status = ($keputusan === 'Disetujui') ? "$label_obj berhasil disetujui & diparaf." : "Instruksi revisi $label_obj telah dikirim ke mahasiswa.";
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
    }

    header("Location: ../mentor/approval.php");
    exit;
}

// =========================================================================
// 2. DATA SECTION 1: TUGAS MASUK (DARI TABEL TUGAS_DETAIL)
// =========================================================================
$list_tugas = [];
$query_tugas = $conn->query("
    SELECT td.*, t.judul_tugas, u.nama_user, u.nim
    FROM tugas_detail td
    JOIN tugas t ON td.tugas_id = t.id
    JOIN users u ON td.user_id = u.id
    WHERE t.mentor_id = '$mentor_id'
    ORDER BY td.waktu_kirim DESC
");

if ($query_tugas && $query_tugas->num_rows > 0) {
    while ($row = $query_tugas->fetch_assoc()) {
        $list_tugas[] = $row;
    }
}

// =========================================================================
// 3. DATA SECTION 2: LOGBOOK HARIAN (DARI TABEL KEHADIRAN)
// =========================================================================
$list_logbook = [];
$query_logbook = $conn->query("
    SELECT k.*, u.nama_user, u.nim
    FROM kehadiran k
    JOIN users u ON k.user_id = u.id
    WHERE k.catatan IS NOT NULL AND k.catatan != ''
    ORDER BY k.tanggal DESC, k.waktu_masuk DESC
");

if ($query_logbook && $query_logbook->num_rows > 0) {
    while ($row = $query_logbook->fetch_assoc()) {
        $list_logbook[] = $row;
    }
}
?>