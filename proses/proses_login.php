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

     //KITA MATIKAN SEMENTARA CEK CSRF
    //(Penyebab utama sering tertendang jika form HTML tidak memiliki input csrf_token)
    
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Sesi Berakhir',
            'message' => 'Permintaan tidak valid (CSRF). Silakan muat ulang.'
        ];
        session_write_close();
        header("Location: login.php?switch=1");
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
        session_write_close();
        header("Location: login.php?switch=1"); // Tambah ?switch=1 agar tidak memantul ke Index
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
                'message' => "Terlalu banyak percobaan salah. Coba lagi dalam {$sisa_menit} menit."
            ];
            session_write_close();
            header("Location: login.php?switch=1");
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
                // DIMATIKAN SEMENTARA: Sering membuat XAMPP/Laragon lokal kehilangan data Sesi
                // session_regenerate_id(true); 

                $reset_stmt = $conn->prepare("UPDATE users SET failed_attempts = 0, lockout_time = NULL WHERE id = ?");
                $reset_stmt->bind_param("i", $user_id_db);
                $reset_stmt->execute();
                $reset_stmt->close();

                $_SESSION['user_id']       = $row['id'];
                $_SESSION['nama_user']     = $row['nama_user'];
                $_SESSION['nim']           = $row['nim'];
                $_SESSION['kelas']         = $row['kelas'];
                $_SESSION['konsentrasi']   = $row['konsentrasi'];
                $_SESSION['role']          = 'mahasiswa';
                $_SESSION['last_activity'] = time();

                session_write_close(); 
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
                        'message' => "PIN yang dimasukkan salah. Sisa kesempatan: {$sisa_kesempatan}x."
                    ];
                }
                
                session_write_close(); 
                header("Location: login.php?switch=1"); // Tambah ?switch=1
                exit;
            }
        }
    } else {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Akses Ditolak',
            'message' => 'Gagal Login! User tidak ditemukan dalam database.'
        ];
        session_write_close(); 
        header("Location: login.php?switch=1"); // Tambah ?switch=1
        exit;
    }

    $stmt->close();
}
?>