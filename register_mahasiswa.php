<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/koneksi.php';

$error_msg        = '';
$success_redirect = false;

// PROSES PENDAFTARAN MAHASISWA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim         = trim($_POST['nim'] ?? '');
    $nama_user   = trim($_POST['nama_user'] ?? '');
    $kelas       = trim($_POST['kelas'] ?? '');
    $konsentrasi = trim($_POST['konsentrasi'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $pin         = trim($_POST['pin'] ?? '');

    // 1. Validasi Input Dasar
    if (empty($nim) || empty($nama_user) || empty($kelas) || empty($konsentrasi) || empty($email) || empty($pin)) {
        $error_msg = 'Semua kolom wajib diisi!';
    } elseif (strlen($pin) !== 4 || !ctype_digit($pin)) {
        // Validasi ketat PIN harus 4 digit angka
        $error_msg = 'PIN Keamanan harus terdiri dari tepat 4 digit angka!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Format email tidak valid!';
    } else {
        // 2. Cek Duplikasi NIM atau Email di Tabel `users`
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE nim = ? OR email = ?");
        $stmt_check->bind_param("ss", $nim, $email);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();

        if ($res_check->num_rows > 0) {
            $error_msg = 'NIM atau Email sudah terdaftar dalam sistem!';
        } else {
            // 3. Hash PIN menggunakan Bcrypt
            $hashed_pin = password_hash($pin, PASSWORD_BCRYPT);

            // 4. Simpan Data Mahasiswa Baru ke Database `users`
            $stmt_insert = $conn->prepare("INSERT INTO users (nama_user, nim, kelas, konsentrasi, email, pin) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("ssssss", $nama_user, $nim, $kelas, $konsentrasi, $email, $hashed_pin);

            if ($stmt_insert->execute()) {
                $new_user_id = $stmt_insert->insert_id;

                // 5. AUTO LOGIN (Buat Sesi Login Mahasiswa Seketika)
                $_SESSION['user_id']     = $new_user_id;
                $_SESSION['role']        = 'mahasiswa';
                $_SESSION['nama_user']   = $nama_user;
                $_SESSION['nim']         = $nim;
                $_SESSION['kelas']       = $kelas;
                $_SESSION['konsentrasi'] = $konsentrasi;

                $success_redirect = true;
            } else {
                $error_msg = 'Gagal mendaftarkan akun: ' . $conn->error;
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Mahasiswa - UTB Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .bg-pattern {
            background-color: #F4F7FE;
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 20px 20px;
        }
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { 
            -webkit-appearance: none; margin: 0; 
        }
    </style>
</head>
<body class="bg-pattern flex flex-col min-h-screen text-gray-800 items-center justify-center p-4 py-8">

    <!-- Tombol Kembali -->
    <a href="login.php?switch=1" class="absolute top-6 left-6 flex items-center gap-2 text-xs font-bold text-gray-500 hover:text-blue-600 transition bg-white px-4 py-2.5 rounded-full shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Kembali ke Login
    </a>

    <div class="w-full max-w-lg bg-white rounded-3xl shadow-xl border border-gray-100 p-8 space-y-6">
        
        <!-- Header Branding -->
        <div class="text-center space-y-1">
            <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-3 border border-blue-100 shadow-sm">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path></svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Registrasi Mahasiswa</h2>
            <p class="text-xs text-gray-400">Buat akun presensi & evaluasi magang mandiri</p>
        </div>

        <!-- Alert Error -->
        <?php if (!empty($error_msg)): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-600 px-4 py-3 rounded-2xl text-xs font-semibold flex items-center gap-2">
                <span>⚠️</span>
                <span><?php echo htmlspecialchars($error_msg); ?></span>
            </div>
        <?php endif; ?>

        <!-- Form Registrasi Mahasiswa -->
        <form method="POST" action="" class="space-y-4">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">NIM Mahasiswa</label>
                    <input type="text" name="nim" required placeholder="Contoh: 232101145" 
                           value="<?php echo htmlspecialchars($_POST['nim'] ?? ''); ?>"
                           class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:bg-white transition font-semibold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                    <input type="text" name="nama_user" required placeholder="Sesuai KTM" 
                           value="<?php echo htmlspecialchars($_POST['nama_user'] ?? ''); ?>"
                           class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:bg-white transition font-semibold">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Kelas</label>
                    <input type="text" name="kelas" required placeholder="Contoh: TiF 23 CiD G" 
                           value="<?php echo htmlspecialchars($_POST['kelas'] ?? ''); ?>"
                           class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:bg-white transition font-semibold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Konsentrasi</label>
                    <input type="text" name="konsentrasi" required placeholder="Contoh: Creative Interactive Design" 
                           value="<?php echo htmlspecialchars($_POST['konsentrasi'] ?? ''); ?>"
                           class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:bg-white transition font-semibold">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Email Aktif</label>
                <input type="email" name="email" required placeholder="nama@student.utb.ac.id" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                       class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:bg-white transition font-semibold">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">PIN Keamanan (Tepat 4 Angka)</label>
                <input type="password" name="pin" maxlength="4" pattern="[0-9]{4}" inputmode="numeric" required placeholder="••••" 
                       class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:bg-white transition font-semibold tracking-widest">
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-200 transition text-sm mt-2">
                Daftar & Masuk Dashboard
            </button>
        </form>

        <div class="text-center pt-2 border-t border-gray-100">
            <p class="text-xs text-gray-500">Sudah punya akun? 
                <a href="login.php?switch=1" class="text-blue-600 font-bold hover:underline">Masuk disini</a>
            </p>
        </div>

    </div>

    <!-- Handling Redirect Auto-Login -->
    <?php if ($success_redirect): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Pendaftaran Berhasil!',
            text: 'Akun Mahasiswa aktif. Mengarahkan ke Dashboard...',
            timer: 1500,
            showConfirmButton: false
        }).then(() => {
            window.location.href = 'mahasiswa/dashboard_mahasiswa.php';
        });
    </script>
    <?php endif; ?>

</body>
</html>