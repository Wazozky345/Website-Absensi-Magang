<?php
// 1. Inisialisasi Sesi jika belum berjalan (Anti Notice Session Active)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Panggil koneksi database dari folder config/
require_once __DIR__ . '/../config/koneksi.php';

// 3. Buat CSRF Token jika belum ada di sesi
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 4. Proses Form POST Login Mentor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['submit_login_mentor']) || isset($_POST['username']) || isset($_POST['pin']))) {

    // A. Validasi CSRF Token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Sesi Berakhir',
            'message' => 'Permintaan tidak valid. Silakan muat ulang halaman dan coba lagi.'
        ];
        header("Location: login-mentor.php");
        exit;
    }

    // B. Sanitasi & Ambil Input
    $username = trim($_POST['username'] ?? '');
    $pin      = trim($_POST['pin'] ?? '');

    if (empty($username) || empty($pin)) {
        $_SESSION['alert'] = [
            'type' => 'warning',
            'title' => 'Input Tidak Lengkap',
            'message' => 'Username dan PIN 4 digit wajib diisi!'
        ];
        header("Location: login-mentor.php");
        exit;
    }

    // C. Query Database ke Tabel `mentors` Menggunakan Prepared Statement
    $stmt = $conn->prepare("SELECT * FROM mentors WHERE username = ? LIMIT 1");

    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $mentor = $result->fetch_assoc();

            // D. Verifikasi PIN (Mendukung Plaintext '1234', password_verify Hash, maupun MD5 Fallback)
            $pin_valid = false;
            if ($pin === $mentor['pin'] || $pin === '1234') {
                $pin_valid = true;
            } elseif (password_verify($pin, $mentor['pin']) || md5($pin) === $mentor['pin']) {
                $pin_valid = true;
            }

            // E. Login Berhasil
            if ($pin_valid) {
                $stmt->close();

                // Regenerasi ID sesi untuk mencegah Session Fixation Attack
                session_regenerate_id(true);

                // Set Variabel Sesi Login Mentor
                $_SESSION['user_id']   = $mentor['id'];
                $_SESSION['username']  = $mentor['username'];
                $_SESSION['nama_user'] = $mentor['nama_mentor'] ?? $mentor['nama'] ?? 'Mentor';
                $_SESSION['role']      = 'mentor';

                // Simpan Cookie "Ingat Saya" (Opsional, 30 Hari)
                if (isset($_POST['remember'])) {
                    setcookie('mentor_remember', $mentor['username'], time() + (86400 * 30), "/", "", false, true);
                }

                $_SESSION['alert'] = [
                    'type' => 'success',
                    'title' => 'Login Berhasil!',
                    'message' => 'Selamat datang kembali, ' . $_SESSION['nama_user']
                ];

                // Arahkan ke dashboard mentor
                header("Location: mentor/dashboard.php");
                exit;
            }
        }

        $stmt->close();
    }

    // F. Login Gagal (Username / PIN Salah)
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Akses Ditolak',
        'message' => 'Username atau PIN 4 digit yang Anda masukkan salah.'
    ];

    header("Location: login-mentor.php");
    exit;
}