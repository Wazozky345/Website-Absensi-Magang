<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/koneksi.php';

// Pastikan yang mengakses adalah Mentor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'mentor') {
    header("Location: ../login-mentor.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Validasi CSRF Token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $_SESSION['alert'] = [
            'type' => 'error', 
            'title' => 'Sesi Berakhir', 
            'message' => 'Permintaan tidak valid. Silakan coba lagi.'
        ];
        header("Location: ../mentor/dashboard.php");
        exit;
    }

    $mentor_id      = $_SESSION['user_id'];
    $pin_lama       = $_POST['pin_lama'] ?? '';
    $pin_baru       = $_POST['pin_baru'] ?? '';
    $konfirmasi_pin = $_POST['konfirmasi_pin'] ?? '';

    // 2. Validasi Input Kosong
    if (empty($pin_lama) || empty($pin_baru) || empty($konfirmasi_pin)) {
        $_SESSION['alert'] = [
            'type' => 'warning', 
            'title' => 'Input Tidak Lengkap', 
            'message' => 'Semua kolom PIN wajib diisi!'
        ];
        header("Location: ../mentor/dashboard.php");
        exit;
    }

    // 3. Validasi Kesamaan PIN Baru
    if ($pin_baru !== $konfirmasi_pin) {
        $_SESSION['alert'] = [
            'type' => 'error', 
            'title' => 'PIN Tidak Cocok', 
            'message' => 'Konfirmasi PIN baru tidak sesuai dengan PIN baru yang dimasukkan!'
        ];
        header("Location: ../mentor/dashboard.php");
        exit;
    }

    // 4. Validasi Format PIN (Wajib 4 Digit Angka)
    if (!preg_match('/^[0-9]{4}$/', $pin_baru)) {
        $_SESSION['alert'] = [
            'type' => 'error', 
            'title' => 'Format Salah', 
            'message' => 'PIN baru wajib terdiri dari tepat 4 digit angka (0-9)!'
        ];
        header("Location: ../mentor/dashboard.php");
        exit;
    }

    // 5. Cek PIN Lama di Database 
    $stmt_cek = $conn->prepare("SELECT pin FROM mentors WHERE id = ? LIMIT 1");
    if ($stmt_cek) {
        $stmt_cek->bind_param("i", $mentor_id);
        $stmt_cek->execute();
        $result = $stmt_cek->get_result();

        if ($result && $result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $db_pin = $row['pin'];

            // VERIFIKASI STRICT BCRYPT & PLAINTEXT FALLBACK
            $pin_valid = false;
            // Cek apakah PIN di database masih plaintext (telanjang) ATAU sudah di-hash pakai Bcrypt
            if ($pin_lama === $db_pin || password_verify($pin_lama, $db_pin)) {
                $pin_valid = true;
            }

            if (!$pin_valid) {
                $_SESSION['alert'] = [
                    'type' => 'error', 
                    'title' => 'Akses Ditolak', 
                    'message' => 'PIN Lama yang Anda masukkan salah!'
                ];
                header("Location: ../mentor/dashboard.php");
                exit;
            }

            // 6. Update dengan PIN Baru (MURNI BCRYPT PASSWORD_HASH)
            $hash_pin_baru = password_hash($pin_baru, PASSWORD_DEFAULT);
            $stmt_upd = $conn->prepare("UPDATE mentors SET pin = ? WHERE id = ?");
            if ($stmt_upd) {
                $stmt_upd->bind_param("si", $hash_pin_baru, $mentor_id);
                
                if ($stmt_upd->execute()) {
                    $_SESSION['alert'] = [
                        'type' => 'success', 
                        'title' => 'Berhasil!', 
                        'message' => 'PIN Keamanan Anda berhasil diubah. Gunakan PIN baru ini untuk login selanjutnya.'
                    ];
                } else {
                    $_SESSION['alert'] = [
                        'type' => 'error', 
                        'title' => 'Gagal Menyimpan', 
                        'message' => 'Terjadi kesalahan sistem saat menyimpan PIN baru.'
                    ];
                }
                $stmt_upd->close();
            }
        } else {
            $_SESSION['alert'] = [
                'type' => 'error', 
                'title' => 'Gagal', 
                'message' => 'Data mentor tidak ditemukan di sistem.'
            ];
        }
        $stmt_cek->close();
    }

    header("Location: ../mentor/dashboard.php");
    exit;
}
?>