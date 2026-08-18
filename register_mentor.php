<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/koneksi.php';

$error_msg   = '';
$success_redirect = false;

// PROSES PENDAFTARAN MENTOR
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_mentor = trim($_POST['nama_mentor'] ?? '');
    $username    = trim($_POST['username'] ?? '');
    $pin         = trim($_POST['pin'] ?? '');

    // Validasi Input Sederhana
    if (empty($nama_mentor) || empty($username) || empty($pin)) {
        $error_msg = 'Semua kolom wajib diisi!';
    } elseif (strlen($pin) < 4) {
        $error_msg = 'PIN Keamanan minimal 4 angka!';
    } else {
        // 1. Cek apakah Username Mentor Sudah Ada
        $stmt_check = $conn->prepare("SELECT id FROM mentors WHERE username = ?");
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();

        if ($res_check->num_rows > 0) {
            $error_msg = 'Username sudah terdaftar! Gunakan username lain.';
        } else {
            // 2. Simpan Data Mentor Baru ke Database `mentors`
            // Catatan: PIN disimpan langsung/hash sesuai standar database
            $stmt_insert = $conn->prepare("INSERT INTO mentors (username, nama_mentor, pin, jabatan) VALUES (?, ?, ?, 'Pembimbing Lapangan')");
            $stmt_insert->bind_param("sss", $username, $nama_mentor, $pin);

            if ($stmt_insert->execute()) {
                $new_mentor_id = $stmt_insert->insert_id;

                // 3. AUTO LOGIN (Buat Sesi Login Mentor Seketika)
                $_SESSION['user_id']   = $new_mentor_id;
                $_SESSION['role']      = 'mentor';
                $_SESSION['nama_user'] = $nama_mentor;
                $_SESSION['username']  = $username;

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
    <title>Pendaftaran Mentor - UTB Tracker</title>
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
<body class="bg-pattern flex flex-col min-h-screen text-gray-800 items-center justify-center p-4">

    <a href="landing_page_utama.php" class="absolute top-6 left-6 flex items-center gap-2 text-xs font-bold text-gray-500 hover:text-blue-600 transition bg-white px-4 py-2.5 rounded-full shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Kembali Utama
    </a>

    <div class="w-full max-w-md bg-white rounded-3xl shadow-xl border border-gray-100 p-8 space-y-6">
        
        <div class="text-center space-y-2">
            <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-3 border border-blue-100 shadow-sm">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Registrasi Mentor</h2>
            <p class="text-xs text-gray-400">Buat akun Pembimbing Lapangan / Akademik</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-600 px-4 py-3 rounded-2xl text-xs font-semibold flex items-center gap-2">
                <span>⚠️</span>
                <span><?php echo htmlspecialchars($error_msg); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-4">
            
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                <input type="text" name="nama_mentor" required placeholder="Contoh: Budi Nugroho" 
                       value="<?php echo htmlspecialchars($_POST['nama_mentor'] ?? ''); ?>"
                       class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:bg-white transition font-semibold">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Username Akses/PN</label>
                <input type="text" name="username" required placeholder="Contoh: mentor.Budi 001122" 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                       class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:bg-white transition font-semibold">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">PIN Keamanan (4 Angka)</label>
                <input type="password" name="pin" maxlength="6" inputmode="numeric" required placeholder="••••" 
                       class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:bg-white transition font-semibold tracking-widest">
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-200 transition text-sm mt-2">
                Daftar & Masuk Dashboard
            </button>
        </form>

        <div class="text-center pt-2 border-t border-gray-100">
            <p class="text-xs text-gray-500">Sudah punya akun mentor? 
                <a href="login-mentor.php" class="text-blue-600 font-bold hover:underline">Masuk disini</a>
            </p>
        </div>

    </div>

    <?php if ($success_redirect): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Pendaftaran Berhasil!',
            text: 'Akun Mentor telah aktif. Mengarahkan ke Dashboard...',
            timer: 1500,
            showConfirmButton: false
        }).then(() => {
            // Arahkan langsung ke halaman Approval / Dashboard Mentor
            window.location.href = 'mentor/approval.php';
        });
    </script>
    <?php endif; ?>

</body>
</html>