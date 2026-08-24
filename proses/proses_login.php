<?php
// Proteksi agar session_start() tidak dipanggil dua kali
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Jika diakses biasa tanpa switch dan sesi mahasiswa aktif, arahkan ke dashboard
if (!isset($_GET['switch']) && isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'mahasiswa') {
    header("Location: mahasiswa/dashboard_mahasiswa.php");
    exit;
}

// Inisialisasi CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Tangkap request POST login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['submit_login']) || isset($_POST['pin']))) {

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Sesi Berakhir',
            'message' => 'Permintaan tidak valid. Silakan muat ulang halaman.'
        ];
        header("Location: login.php");
        exit;
    }

    $user = trim($_POST['nama_user'] ?? '');
    $pin  = trim($_POST['pin'] ?? '');

    if (empty($user) || empty($pin)) {
        $_SESSION['alert'] = [
            'type' => 'warning',
            'title' => 'Input Kosong',
            'message' => 'Silakan pilih akun dan masukkan PIN 4 digit!'
        ];
        header("Location: login.php");
        exit;
    }

    $stmt = $conn->prepare("SELECT id, nama_user, nim, kelas, konsentrasi, pin, failed_attempts, lockout_time FROM users WHERE nama_user = ? OR nim = ? LIMIT 1");
    $stmt->bind_param("ss", $user, $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_id_db = $row['id'];
        $waktu_sekarang = date('Y-m-d H:i:s');

        // Cek Lockout
        if ($row['lockout_time'] != NULL && $row['lockout_time'] > $waktu_sekarang) {
            $sisa_waktu = strtotime($row['lockout_time']) - strtotime($waktu_sekarang);
            $sisa_menit = ceil($sisa_waktu / 60);

            $_SESSION['alert'] = [
                'type' => 'error',
                'title' => 'Akun Terkunci!',
                'message' => "Terlalu banyak percobaan PIN salah. Silakan coba lagi dalam {$sisa_menit} menit."
            ];
            header("Location: login.php");
            exit;
        } else {
            if ($row['lockout_time'] != NULL && $row['lockout_time'] <= $waktu_sekarang) {
                $reset_lock = $conn->prepare("UPDATE users SET failed_attempts = 0, lockout_time = NULL WHERE id = ?");
                $reset_lock->bind_param("i", $user_id_db);
                $reset_lock->execute();
                $reset_lock->close();
                $row['failed_attempts'] = 0;
            }

            // Verifikasi PIN
            $pin_valid = false;
            if (password_verify($pin, $row['pin'])) {
                $pin_valid = true;
            } elseif ($pin === $row['pin'] || md5($pin) === $row['pin']) {
                $pin_valid = true;
            }

            if ($pin_valid) {
                session_regenerate_id(true);

                $reset_stmt = $conn->prepare("UPDATE users SET failed_attempts = 0, lockout_time = NULL WHERE id = ?");
                $reset_stmt->bind_param("i", $user_id_db);
                $reset_stmt->execute();
                $reset_stmt->close();

                // 1. SET SEMUA DATA SESI
                $_SESSION['user_id']       = $row['id'];
                $_SESSION['nama_user']     = $row['nama_user'];
                $_SESSION['nim']           = $row['nim'];
                $_SESSION['kelas']         = $row['kelas'];
                $_SESSION['konsentrasi']   = $row['konsentrasi'];
                $_SESSION['role']          = 'mahasiswa';
                $_SESSION['last_activity'] = time();

                /*$_SESSION['alert'] = [
                    'type' => 'success',
                    'title' => 'Login Berhasil!',
                    'message' => 'Selamat datang kembali, ' . $_SESSION['nama_user']
                ];*/

                // 2. KUNCI DAN SIMPAN SESI SEKARANG JUGA (Solusi Anti-Loop)
                session_write_close(); 

                // 3. BARU REDIRECT KE DASHBOARD
                header("Location: mahasiswa/dashboard_mahasiswa.php");
                exit;
            } else {
                $attempts = $row['failed_attempts'] + 1;

                if ($attempts >= 3) {
                    $lockout_until = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                    $lock_stmt = $conn->prepare("UPDATE users SET failed_attempts = ?, lockout_time = ? WHERE id = ?");
                    $lock_stmt->bind_param("isi", $attempts, $lockout_until, $user_id_db);
                    $lock_stmt->execute();
                    $lock_stmt->close();

                    $_SESSION['alert'] = [
                        'type' => 'error',
                        'title' => 'Akun Terkunci!',
                        'message' => 'PIN salah 3 kali berturut-turut. Akun dikunci selama 5 menit.'
                    ];
                } else {
                    $update_stmt = $conn->prepare("UPDATE users SET failed_attempts = ? WHERE id = ?");
                    $update_stmt->bind_param("ii", $attempts, $user_id_db);
                    $update_stmt->execute();
                    $update_stmt->close();

                    $sisa_kesempatan = 3 - $attempts;
                    $_SESSION['alert'] = [
                        'type' => 'error',
                        'title' => 'PIN Salah!',
                        'message' => "PIN 4 digit yang dimasukkan salah. Sisa kesempatan: {$sisa_kesempatan}x."
                    ];
                }
                
                session_write_close(); // Simpan juga alert sesi sebelum redirect gagal
                header("Location: login.php");
                exit;
            }
        }
    } else {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Akses Ditolak',
            'message' => 'Gagal Login! User tidak ditemukan dalam database.'
        ];
        session_write_close(); // Simpan juga alert sesi sebelum redirect gagal
        header("Location: login.php");
        exit;
    }

    $stmt->close();
}
?>