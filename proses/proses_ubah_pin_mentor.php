<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $redirect_back = $_SERVER['HTTP_REFERER'] ?? '../mentor/dashboard.php';

    // 1. Validasi CSRF Token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Sesi Berakhir',
            'message' => 'Token keamanan tidak valid. Silakan muat ulang halaman.'
        ];
        header("Location: " . $redirect_back);
        exit;
    }

    // 2. Proteksi Harus Sesi Mentor Logged In
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Akses Ditolak',
            'message' => 'Sesi Anda telah habis. Silakan login kembali.'
        ];
        header("Location: ../login-mentor.php");
        exit;
    }

    $pin_lama       = trim($_POST['pin_lama'] ?? '');
    $pin_baru       = trim($_POST['pin_baru'] ?? '');
    $konfirmasi_pin = trim($_POST['konfirmasi_pin'] ?? '');

    // 3. Validasi Input Tidak Boleh Kosong
    if (empty($pin_lama) || empty($pin_baru) || empty($konfirmasi_pin)) {
        $_SESSION['alert'] = [
            'type' => 'warning',
            'title' => 'Data Belum Lengkap',
            'message' => 'Harap isi semua kolom form ubah PIN.'
        ];
        header("Location: " . $redirect_back);
        exit;
    }

    // 4. Validasi Format PIN Harus 4 Digit Angka
    if (!preg_match('/^[0-9]{4}$/', $pin_baru)) {
        $_SESSION['alert'] = [
            'type' => 'warning',
            'title' => 'Format PIN Salah',
            'message' => 'PIN Baru harus terdiri dari 4 digit angka.'
        ];
        header("Location: " . $redirect_back);
        exit;
    }

    // 5. Validasi Kesesuaian PIN Baru
    if ($pin_baru !== $konfirmasi_pin) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'PIN Tidak Cocok',
            'message' => 'Konfirmasi PIN Baru tidak cocok dengan PIN Baru.'
        ];
        header("Location: " . $redirect_back);
        exit;
    }

    // 6. Cek & Verifikasi PIN Lama di Database
    $stmt = $conn->prepare("SELECT pin, password FROM mentors WHERE id = ?");
    if (!$stmt) {
        $stmt = $conn->prepare("SELECT pin, password FROM users WHERE id = ? AND role = 'mentor'");
    }

    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $pin_db = $row['pin'] ?? $row['password'];

            $is_valid_old = (password_verify($pin_lama, $pin_db) || $pin_lama === $pin_db);

            if ($is_valid_old) {
                // Hash PIN Baru
                $pin_baru_hashed = password_hash($pin_baru, PASSWORD_DEFAULT);

                $stmt_upd = $conn->prepare("UPDATE mentors SET pin = ?, updated_at = NOW() WHERE id = ?");
                if (!$stmt_upd) {
                    $stmt_upd = $conn->prepare("UPDATE users SET pin = ?, updated_at = NOW() WHERE id = ? AND role = 'mentor'");
                }

                if ($stmt_upd) {
                    $stmt_upd->bind_param("si", $pin_baru_hashed, $user_id);
                    if ($stmt_upd->execute()) {
                        $_SESSION['alert'] = [
                            'type' => 'success',
                            'title' => 'PIN Berhasil Diperbarui!',
                            'message' => 'PIN 4-digit keamanan Anda telah berhasil diubah.'
                        ];
                    } else {
                        $_SESSION['alert'] = [
                            'type' => 'error',
                            'title' => 'Gagal Memperbarui',
                            'message' => 'Terjadi kesalahan sistem saat menyimpan PIN baru.'
                        ];
                    }
                    $stmt_upd->close();
                }
            } else {
                $_SESSION['alert'] = [
                    'type' => 'error',
                    'title' => 'PIN Lama Salah',
                    'message' => 'PIN Lama yang Anda masukkan tidak sesuai.'
                ];
            }
        }
        $stmt->close();
    }

    header("Location: " . $redirect_back);
    exit;
}