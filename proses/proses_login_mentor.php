<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/koneksi.php';
date_default_timezone_set('Asia/Jakarta');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validasi CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $_SESSION['alert'] = ['type' => 'error', 'title' => 'Sesi Berakhir', 'message' => 'Permintaan tidak valid.'];
        header("Location: login-mentor.php"); // FIX: Jalur sudah diluruskan
        exit;
    }

    $username = trim($_POST['username'] ?? '');
    $pin      = trim($_POST['pin'] ?? '');

    if (empty($username) || empty($pin)) {
        $_SESSION['alert'] = ['type' => 'warning', 'title' => 'Input Kosong', 'message' => 'Username dan PIN wajib diisi!'];
        header("Location: login-mentor.php"); // FIX: Jalur sudah diluruskan
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM mentors WHERE username = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $mentor = $result->fetch_assoc();
            $mentor_id = $mentor['id'];
            $waktu_sekarang = date('Y-m-d H:i:s');
            $lockout_time = $mentor['lockout_time'];
            $failed_attempts = $mentor['failed_attempts'];

            // Cek Masa Hukuman
            if ($lockout_time != NULL && $lockout_time > $waktu_sekarang) {
                $sisa_menit = ceil((strtotime($lockout_time) - strtotime($waktu_sekarang)) / 60);
                $_SESSION['alert'] = ['type' => 'error', 'title' => 'Akun Terkunci!', 'message' => "Coba lagi dalam {$sisa_menit} menit."];
                header("Location: login-mentor.php"); // FIX: Jalur sudah diluruskan
                exit;
            } else {
                // Reset hukuman kalau waktu sudah lewat
                if ($lockout_time != NULL && $lockout_time <= $waktu_sekarang) {
                    $conn->query("UPDATE mentors SET failed_attempts = 0, lockout_time = NULL WHERE id = $mentor_id");
                    $failed_attempts = 0;
                }

                // Cek PIN (Plaintext atau Bcrypt)
                $pin_valid = false;
                if ($pin === $mentor['pin'] || password_verify($pin, $mentor['pin'])) {
                    $pin_valid = true;
                }

                if ($pin_valid) {
                    session_regenerate_id(true);
                    $conn->query("UPDATE mentors SET failed_attempts = 0, lockout_time = NULL WHERE id = $mentor_id");

                    $_SESSION['user_id'] = $mentor['id'];
                    $_SESSION['username'] = $mentor['username'];
                    $_SESSION['nama_user'] = $mentor['nama_mentor'];
                    $_SESSION['jabatan']   = !empty($mentor['jabatan']) ? $mentor['jabatan'] : 'Pembimbing Lapangan';
                    $_SESSION['role'] = 'mentor';

                    // [PERBAIKAN]: Reset timer timeout & paksa simpan sesi ke server
                    $_SESSION['last_activity'] = time();
                    session_write_close();

                    // $_SESSION['alert'] = ['type' => 'success', 'title' => 'Login Berhasil!', 'message' => 'Selamat datang!'];
                    header("Location: mentor/dashboard.php"); // FIX: Masuk ke folder mentor
                    exit;
                } else {
                    $attempts = $failed_attempts + 1;
                    if ($attempts >= 3) {
                        $lockout_until = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                        $stmt_lock = $conn->prepare("UPDATE mentors SET failed_attempts = ?, lockout_time = ? WHERE id = ?");
                        $stmt_lock->bind_param("isi", $attempts, $lockout_until, $mentor_id);
                        $stmt_lock->execute();

                        $_SESSION['alert'] = ['type' => 'error', 'title' => 'Akun Terkunci!', 'message' => 'PIN salah 3x. Akun dikunci 5 menit.'];
                    } else {
                        $stmt_fail = $conn->prepare("UPDATE mentors SET failed_attempts = ? WHERE id = ?");
                        $stmt_fail->bind_param("ii", $attempts, $mentor_id);
                        $stmt_fail->execute();

                        $sisa = 3 - $attempts;
                        $_SESSION['alert'] = ['type' => 'error', 'title' => 'PIN Salah!', 'message' => "Sisa kesempatan: {$sisa}x lagi."];
                    }
                    header("Location: login-mentor.php"); // FIX: Jalur sudah diluruskan
                    exit;
                }
            }
        } else {
            // Username Tidak Ditemukan
            $_SESSION['alert'] = ['type' => 'error', 'title' => 'Gagal!', 'message' => 'Username tidak terdaftar!'];
            header("Location: login-mentor.php"); // FIX: Jalur sudah diluruskan
            exit;
        }
    }
}
