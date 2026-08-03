<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/koneksi.php';

// 1. PROTEKSI HAK AKSES MAHASISWA
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 2. VALIDASI CSRF TOKEN
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Sesi Berakhir',
            'message' => 'CSRF Token Invalid. Silakan muat ulang halaman.'
        ];
        header("Location: ../mahasiswa/dashboard_mahasiswa.php");
        exit;
    }

    $tugas_id = intval($_POST['tugas_id'] ?? 0);

    if ($tugas_id <= 0) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Gagal Mengunggah',
            'message' => 'ID Tugas tidak valid atau tidak ditemukan.'
        ];
        header("Location: ../mahasiswa/dashboard_mahasiswa.php");
        exit;
    }

    // 3. VALIDASI PENGIRIMAN BERKAS
    if (!isset($_FILES['file_balasan']) || $_FILES['file_balasan']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['alert'] = [
            'type' => 'warning',
            'title' => 'Berkas Belum Dipilih',
            'message' => 'Silakan pilih berkas tugas terlebih dahulu sebelum mengirim.'
        ];
        header("Location: ../mahasiswa/dashboard_mahasiswa.php");
        exit;
    }

    $file_tmp   = $_FILES['file_balasan']['tmp_name'];
    $file_name  = $_FILES['file_balasan']['name'];
    $file_size  = $_FILES['file_balasan']['size'];
    $file_ext   = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Ekstensi yang diizinkan & Batas Maksimal 10MB
    $allowed_ext = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar', 'jpg', 'jpeg', 'png'];
    $max_size    = 10 * 1024 * 1024; // 10MB

    if (!in_array($file_ext, $allowed_ext)) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Format Tidak Didukung',
            'message' => 'Format berkas harus berupa PDF, DOCX, XLSX, ZIP, atau Gambar.'
        ];
        header("Location: ../mahasiswa/dashboard_mahasiswa.php");
        exit;
    }

    if ($file_size > $max_size) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Berkas Terlalu Besar',
            'message' => 'Ukuran berkas maksimal adalah 10MB.'
        ];
        header("Location: ../mahasiswa/dashboard_mahasiswa.php");
        exit;
    }

    // 4. DIREKTORI PENYIMPANAN AMAN
    $target_dir = __DIR__ . '/../uploads/tugas_mahasiswa/';
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Penamaan File Unik
    $new_filename = 'tugas_mhs_' . $user_id . '_' . $tugas_id . '_' . time() . '.' . $file_ext;
    $target_file  = $target_dir . $new_filename;

    if (move_uploaded_file($file_tmp, $target_file)) {

        $waktu_kirim = date('Y-m-d H:i:s');
        $jam_kirim   = date('H:i:s');
        $sesi_batch  = ($jam_kirim <= '12:00:00') ? 'Pagi' : 'Sore';

        // 5. CEK APAKAH SUDAH ADA RECORD SUBMISSION PREVIOUSLY
        $stmt_check = $conn->prepare("SELECT id FROM tugas_detail WHERE tugas_id = ? AND user_id = ?");
        $stmt_check->bind_param("ii", $tugas_id, $user_id);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();

        if ($res_check && $res_check->num_rows > 0) {
            // UPDATE SUBMISSION YANG SUDAH ADA
            $stmt_upd = $conn->prepare("
                UPDATE tugas_detail 
                SET file_balasan = ?, waktu_kirim = ?, status_approval = 'Menunggu Review', sesi_batch = ?
                WHERE tugas_id = ? AND user_id = ?
            ");
            $stmt_upd->bind_param("sssii", $new_filename, $waktu_kirim, $sesi_batch, $tugas_id, $user_id);
            $stmt_upd->execute();
            $stmt_upd->close();
        } else {
            // INSERT SUBMISSION BARU
            $stmt_ins = $conn->prepare("
                INSERT INTO tugas_detail (tugas_id, user_id, file_balasan, waktu_kirim, status_approval, sesi_batch)
                VALUES (?, ?, ?, ?, 'Menunggu Review', ?)
            ");
            $stmt_ins->bind_param("iisss", $tugas_id, $user_id, $new_filename, $waktu_kirim, $sesi_batch);
            $stmt_ins->execute();
            $stmt_ins->close();
        }
        $stmt_check->close();

        $_SESSION['alert'] = [
            'type' => 'success',
            'title' => 'Berhasil Terkirim!',
            'message' => 'Berkas tugas Anda telah berhasil diunggah dan menunggu review mentor.'
        ];

    } else {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Gagal Mengunggah',
            'message' => 'Terjadi kesalahan sistem saat memindahkan berkas.'
        ];
    }

    header("Location: ../mahasiswa/dashboard_mahasiswa.php");
    exit;
}