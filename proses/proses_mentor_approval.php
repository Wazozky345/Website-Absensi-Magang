<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/koneksi.php';

// Validasi Hak Akses Mentor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'mentor') {
    header("Location: ../login-mentor.php");
    exit;
}

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

    $id_tugas_detail = intval($_POST['id_tugas'] ?? 0);
    $keputusan       = trim($_POST['keputusan'] ?? '');
    $catatan_mentor  = trim($_POST['catatan_mentor'] ?? '');

    if ($id_tugas_detail <= 0 || !in_array($keputusan, ['Disetujui', 'Perlu Revisi'])) {
        $_SESSION['alert'] = [
            'type' => 'warning',
            'title' => 'Data Tidak Valid',
            'message' => 'Pilihan keputusan approval tidak dikenali.'
        ];
        header("Location: ../mentor/approval.php");
        exit;
    }

    // Update Status Approval dan Catatan Penilaian
    $stmt = $conn->prepare("UPDATE tugas_detail SET status_approval = ?, catatan_mentor = ?, updated_at = NOW() WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ssi", $keputusan, $catatan_mentor, $id_tugas_detail);

        if ($stmt->execute()) {
            $pesan_status = ($keputusan === 'Disetujui') ? 'Tugas mahasiswa berhasil disetujui.' : 'Instruksi revisi telah dikirim ke mahasiswa.';
            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => 'Keputusan Disimpan!',
                'message' => $pesan_status
            ];
        } else {
            $_SESSION['alert'] = [
                'type' => 'error',
                'title' => 'Gagal Memproses',
                'message' => 'Terjadi kesalahan sistem saat memperbarui keputusan.'
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