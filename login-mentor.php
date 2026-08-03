<?php
// 1. Inisialisasi Sesi jika belum berjalan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Bersihkan sesi lama jika datang dari tombol ganti peran / index
if (isset($_GET['switch']) || isset($_GET['reset'])) {
    unset($_SESSION['user_id'], $_SESSION['nama_user'], $_SESSION['username'], $_SESSION['nim'], $_SESSION['kelas'], $_SESSION['konsentrasi'], $_SESSION['role']);
}

// 3. Panggil pemrosesan login mentor dari folder proses/
require_once __DIR__ . '/proses/proses_login_mentor.php';

// 4. Jika mentor sudah login dan tidak dalam mode switch, alihkan ke dashboard mentor
if (!isset($_GET['switch']) && isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'mentor') {
    header("Location: mentor/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Mentor - UTB Tracker</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="flex min-h-screen items-center justify-center bg-[#F4F7FE] text-gray-800 select-none p-4">

    <div class="w-full max-w-md relative">

        <!-- NAVIGASI KEMBALI KE LANDING PAGE (ROLE PICKER) -->
        <div class="absolute -top-12 left-0">
            <a href="index.php?switch=1" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-emerald-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Ganti Peran (Halaman Utama)
            </a>
        </div>

        <!-- HEADER UTAMA BRANDING -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-3 text-emerald-600 mb-2">
                <div class="w-10 h-10 bg-emerald-600 text-white rounded-xl flex items-center justify-center shadow-md shadow-emerald-200">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
                    </svg>
                </div>
                <h1 class="text-3xl font-extrabold text-emerald-600 tracking-tight">UTB Tracker</h1>
            </div>
            <p class="text-gray-500 font-medium text-sm">Portal Pembimbing & Evaluasi Magang</p>
        </div>

        <!-- CARD FORM LOGIN MENTOR -->
        <div class="bg-white/95 backdrop-blur-md rounded-3xl p-8 shadow-xl border border-white">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-emerald-100/80 text-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-gray-800">Login Mentor / Pembimbing</h2>
                <p class="text-xs text-gray-400 mt-1">Masukkan username dan PIN 4-digit keamanan Anda</p>
            </div>

            <form method="POST" action="" class="space-y-5">
                <!-- CSRF TOKEN -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

                <!-- INPUT USERNAME -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-2">Username Mentor <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input type="text" name="username" required placeholder="Contoh: mentor.alvin" autocomplete="username" class="w-full bg-gray-50 border border-gray-200 rounded-2xl pl-11 pr-4 py-3.5 text-sm focus:outline-none focus:border-emerald-500 transition font-medium text-gray-800 placeholder-gray-400">
                        <svg class="w-5 h-5 text-gray-400 absolute left-4 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                </div>

                <!-- INPUT PIN 4 DIGIT -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-2">PIN Keamanan (4 Digit) <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input type="password" id="mentor-pin" name="pin" maxlength="4" inputmode="numeric" required placeholder="••••" autocomplete="current-password" class="w-full bg-gray-50 border border-gray-200 rounded-2xl pl-11 pr-12 py-3.5 text-sm focus:outline-none focus:border-emerald-500 transition font-bold text-gray-800 tracking-widest placeholder-gray-400">
                        <svg class="w-5 h-5 text-gray-400 absolute left-4 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <!-- EYE BUTTON TOGGLE -->
                        <button type="button" onclick="togglePinVisibility()" class="absolute right-4 top-3.5 text-gray-400 hover:text-emerald-600 transition" title="Lihat PIN">
                            <svg id="eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- REMEMBER ME CHECKBOX -->
                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center gap-2 cursor-pointer text-gray-600 font-medium">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-gray-300">
                        Ingat akun saya
                    </label>
                </div>

                <!-- TOMBOL SUBMIT -->
                <button type="submit" name="submit_login_mentor" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3.5 rounded-2xl shadow-lg shadow-emerald-200 transition text-sm flex items-center justify-center gap-2">
                    Masuk Ke Portal Mentor
                </button>
            </form>
        </div>

    </div>

    <?php include 'components/alert.php'; ?>

    <script>
        function togglePinVisibility() {
            const pinInput = document.getElementById('mentor-pin');
            if (pinInput.type === 'password') {
                pinInput.type = 'text';
            } else {
                pinInput.type = 'password';
            }
        }
    </script>
</body>

</html>