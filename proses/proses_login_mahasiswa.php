<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/koneksi.php';

// Memproses Form Login saat metode POST dikirimkan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['submit_login_mahasiswa']) || isset($_POST['nim']))) {
    
    // 1. Validasi CSRF Token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
        $_SESSION['alert'] = [
            'type'  => 'error',
            'title' => 'Akses Ditolak',
            'text'  => 'Sesi formulir tidak valid. Silakan coba lagi.'
        ];
        header("Location: ../login.php");
        exit;
    }

    $nim = trim($_POST['nim'] ?? '');
    $pin = trim($_POST['pin'] ?? '');

    // 2. Validasi Input Dasar
    if (empty($nim) || empty($pin)) {
        $_SESSION['alert'] = [
            'type'  => 'warning',
            'title' => 'Input Tidak Lengkap',
            'text'  => 'NIM dan PIN 4-digit keamanan wajib diisi!'
        ];
        header("Location: ../login.php");
        exit;
    }

    if (strlen($pin) !== 4 || !ctype_digit($pin)) {
        $_SESSION['alert'] = [
            'type'  => 'warning',
            'title' => 'Format PIN Salah',
            'text'  => 'PIN Keamanan harus berupa tepat 4 digit angka!'
        ];
        header("Location: ../login.php");
        exit;
    }

    // 3. Pencarian Mahasiswa Berdasarkan NIM pada Tabel `users`
    $stmt = $conn->prepare("SELECT id, nama_user, nim, kelas, konsentrasi, email, pin, failed_attempts, lockout_time FROM users WHERE nim = ?");
    $stmt->bind_param("s", $nim);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // 4. Pemeriksaan Status Lockout (Akun Terkunci akibat 5x Salah PIN)
        if (!empty($user['lockout_time'])) {
            $lockout_until = strtotime($user['lockout_time']);
            $current_time  = time();

            if ($current_time < $lockout_until) {
                $sisa_menit = ceil(($lockout_until - $current_time) / 60);
                $_SESSION['alert'] = [
                    'type'  => 'error',
                    'title' => 'Akun Terkunci Sementara',
                    'text'  => "Akun Anda terkunci karena salah PIN 5 kali. Coba lagi dalam {$sisa_menit} menit."
                ];
                $stmt->close();
                header("Location: ../login.php");
                exit;
            } else {
                // Reset kuncian jika waktu penalti 15 menit telah habis
                $reset_lock = $conn->prepare("UPDATE users SET failed_attempts = 0, lockout_time = NULL WHERE id = ?");
                $reset_lock->bind_param("i", $user['id']);
                $reset_lock->execute();
                $reset_lock->close();
                $user['failed_attempts'] = 0;
            }
        }

        // 5. Verifikasi Keamanan PIN (Bcrypt Hash & Fallback Otomatis)
        $pin_valid = false;
        if (password_verify($pin, $user['pin'])) {
            $pin_valid = true;
        } elseif ($user['pin'] === $pin) {
            // Migrasi otomatis jika data PIN lama masih berformat plaintext
            $new_hash = password_hash($pin, PASSWORD_BCRYPT);
            $update_hash = $conn->prepare("UPDATE users SET pin = ? WHERE id = ?");
            $update_hash->bind_param("si", $new_hash, $user['id']);
            $update_hash->execute();
            $update_hash->close();
            $pin_valid = true;
        }

        if ($pin_valid) {
            // Reset failed_attempts saat login berhasil
            $reset_attempts = $conn->prepare("UPDATE users SET failed_attempts = 0, lockout_time = NULL WHERE id = ?");
            $reset_attempts->bind_param("i", $user['id']);
            $reset_attempts->execute();
            $reset_attempts->close();

            // 6. Inisialisasi Sesi Login Mahasiswa
            $_SESSION['user_id']     = $user['id'];
            $_SESSION['role']        = 'mahasiswa';
            $_SESSION['nama_user']   = $user['nama_user'];
            $_SESSION['nim']         = $user['nim'];
            $_SESSION['kelas']       = $user['kelas'];
            $_SESSION['konsentrasi'] = $user['konsentrasi'];
            $_SESSION['email']       = $user['email'];

            // Alihkan Pengguna ke Dashboard Utama Mahasiswa
            header("Location: ../mahasiswa/dashboard_mahasiswa.php");
            exit;
        } else {
            // PIN Salah: Tambah Penghitung Kegagalan
            $new_attempts = $user['failed_attempts'] + 1;
            
            if ($new_attempts >= 5) {
                // Kunci akun selama 15 menit jika mencapai 5 kali kegagalan
                $lock_stmt = $conn->prepare("UPDATE users SET failed_attempts = ?, lockout_time = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?");
                $lock_stmt->bind_param("ii", $new_attempts, $user['id']);
                $lock_stmt->execute();
                $lock_stmt->close();

                $_SESSION['alert'] = [
                    'type'  => 'error',
                    'title' => 'Akun Terkunci',
                    'text'  => 'Anda salah memasukkan PIN 5 kali. Akun terkunci selama 15 menit.'
                ];
            } else {
                $update_attempts = $conn->prepare("UPDATE users SET failed_attempts = ? WHERE id = ?");
                $update_attempts->bind_param("ii", $new_attempts, $user['id']);
                $update_attempts->execute();
                $update_attempts->close();

                $sisa_kesempatan = 5 - $new_attempts;
                $_SESSION['alert'] = [
                    'type'  => 'error',
                    'title' => 'PIN Salah',
                    'text'  => "PIN yang Anda masukkan salah. Sisa percobaan: {$sisa_kesempatan} kali."
                ];
            }
        }
    } else {
        // NIM Mahasiswa Tidak Terdaftar
        $_SESSION['alert'] = [
            'type'  => 'error',
            'title' => 'Akun Tidak Ditemukan',
            'text'  => 'NIM tidak terdaftar. Silakan mendaftar terlebih dahulu.'
        ];
    }

    $stmt->close();
    header("Location: ../login.php");
    exit;
}
?>