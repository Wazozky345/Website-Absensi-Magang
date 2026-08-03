<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/koneksi.php';

// Proteksi Hak Akses Role Mentor
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
            'message' => 'CSRF Token Invalid. Silakan muat ulang halaman.'
        ];
        header("Location: ../mentor/bimbingan.php");
        exit;
    }

    $mentor_id    = $_SESSION['user_id'];
    $action       = $_POST['action'] ?? 'save';
    $bimbingan_id = intval($_POST['bimbingan_id'] ?? 0);

    // ACTION 1: HAPUS BIMBINGAN (DELETE)
    if ($action === 'delete' && $bimbingan_id > 0) {
        $stmt_del = $conn->prepare("DELETE FROM bimbingan WHERE id = ? AND mentor_id = ?");
        if ($stmt_del) {
            $stmt_del->bind_param("ii", $bimbingan_id, $mentor_id);
            if ($stmt_del->execute()) {
                $_SESSION['alert'] = [
                    'type' => 'success',
                    'title' => 'Berhasil Dihapus',
                    'message' => 'Jadwal bimbingan telah dihapus dari kalender.'
                ];
            }
            $stmt_del->close();
        }
        header("Location: ../mentor/bimbingan.php");
        exit;
    }

    // ACTION 2 & 3: TAMBAH (CREATE) ATAU EDIT (UPDATE) BIMBINGAN
    $target_user_id = intval($_POST['user_id'] ?? 0);
    $nim_input      = trim($_POST['nim_mahasiswa'] ?? '');
    $tanggal_waktu  = trim($_POST['waktu_sesi'] ?? date('Y-m-d H:i:s'));
    $topik          = trim($_POST['topik'] ?? '');
    $metode         = trim($_POST['metode'] ?? 'Tatap Muka');
    $status_input   = trim($_POST['status_tipe'] ?? 'Terjadwal');
    $catatan_revisi = trim($_POST['catatan'] ?? '');

    // Normalisasi Nilai ENUM untuk Kolom `status`
    $status = 'Terjadwal';
    if ($status_input === 'Revisi') {
        $status = 'Revisi';
    } elseif ($status_input === 'Selesai') {
        $status = 'Selesai';
    }

    // Normalisasi Nilai ENUM untuk Kolom `metode`
    if (!in_array($metode, ['Tatap Muka', 'Online'])) {
        $metode = 'Tatap Muka';
    }

    // Cari User ID jika dikirim via NIM
    if ($target_user_id <= 0 && !empty($nim_input)) {
        $stmt_u = $conn->prepare("SELECT id FROM users WHERE nim = ? LIMIT 1");
        if ($stmt_u) {
            $stmt_u->bind_param("s", $nim_input);
            $stmt_u->execute();
            $res_u = $stmt_u->get_result();
            if ($res_u && $res_u->num_rows === 1) {
                $target_user_id = $res_u->fetch_assoc()['id'];
            }
            $stmt_u->close();
        }
    }

    if ($target_user_id <= 0) {
        $target_user_id = 1; 
    }

    if (empty($topik) || empty($catatan_revisi)) {
        $_SESSION['alert'] = [
            'type' => 'warning',
            'title' => 'Form Tidak Lengkap',
            'message' => 'Topik dan Catatan Revisi wajib diisi!'
        ];
        header("Location: ../mentor/bimbingan.php");
        exit;
    }

    if ($bimbingan_id > 0) {
        // UPDATE (EDIT) - Nama Kolom Disesuaikan dengan absensi_db.sql
        $stmt_upd = $conn->prepare("UPDATE bimbingan SET user_id = ?, tanggal_waktu = ?, topik = ?, metode = ?, catatan_revisi = ?, status = ? WHERE id = ? AND mentor_id = ?");
        if ($stmt_upd) {
            $stmt_upd->bind_param("isssssii", $target_user_id, $tanggal_waktu, $topik, $metode, $catatan_revisi, $status, $bimbingan_id, $mentor_id);
            if ($stmt_upd->execute()) {
                $_SESSION['alert'] = [
                    'type' => 'success',
                    'title' => 'Berhasil Diperbarui',
                    'message' => 'Data bimbingan & revisi berhasil diperbarui.'
                ];
            } else {
                $_SESSION['alert'] = [
                    'type' => 'error',
                    'title' => 'Gagal Memperbarui',
                    'message' => 'Terjadi kesalahan server saat memperbarui data.'
                ];
            }
            $stmt_upd->close();
        } else {
            $_SESSION['alert'] = [
                'type' => 'error',
                'title' => 'Gagal Query Database',
                'message' => 'Kesalahan SQL: ' . $conn->error
            ];
        }
    } else {
        // CREATE (TAMBAH BARU) - Nama Kolom Disesuaikan dengan absensi_db.sql
        $stmt_ins = $conn->prepare("INSERT INTO bimbingan (mentor_id, user_id, tanggal_waktu, topik, metode, catatan_revisi, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt_ins) {
            $stmt_ins->bind_param("iisssss", $mentor_id, $target_user_id, $tanggal_waktu, $topik, $metode, $catatan_revisi, $status);
            if ($stmt_ins->execute()) {
                $_SESSION['alert'] = [
                    'type' => 'success',
                    'title' => 'Bimbingan Disimpan!',
                    'message' => 'Jadwal bimbingan & catatan revisi berhasil didaftarkan.'
                ];
            } else {
                $_SESSION['alert'] = [
                    'type' => 'error',
                    'title' => 'Gagal Menyimpan',
                    'message' => 'Terjadi kesalahan saat menyimpan bimbingan.'
                ];
            }
            $stmt_ins->close();
        } else {
            $_SESSION['alert'] = [
                'type' => 'error',
                'title' => 'Gagal Query Database',
                'message' => 'Kesalahan SQL: ' . $conn->error
            ];
        }
    }

    header("Location: ../mentor/bimbingan.php");
    exit;
}