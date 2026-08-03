<?php
// 1. Inisialisasi Sesi jika belum berjalan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika diakses dengan parameter switch/reset (misal dari tombol Ganti Akun), bersihkan sesi lama
if (isset($_GET['switch']) || isset($_GET['reset'])) {
    unset($_SESSION['user_id'], $_SESSION['nama_user'], $_SESSION['nim'], $_SESSION['kelas'], $_SESSION['konsentrasi'], $_SESSION['role']);
}

// 2. SMART ROUTER: Jika pengguna sudah login dan BUKAN sedang memilih ulang peran, alihkan langsung
if (!isset($_GET['switch']) && isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'mahasiswa') {
        header("Location: mahasiswa/dashboard_mahasiswa.php");
        exit;
    } elseif ($_SESSION['role'] === 'mentor') {
        header("Location: mentor/dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTB Tracker - Pilih Peran Login</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-[#F4F7FE] text-gray-800 min-h-screen flex flex-col items-center justify-center p-4 select-none relative">
    <div class="w-full max-w-4xl mx-auto flex flex-col items-center">

        <!-- LOGO BRANDING UTAMA -->
        <div class="text-center mb-10">
            <div class="inline-flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white shadow-md shadow-blue-200">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
                    </svg>
                </div>
                <h1 class="text-3xl font-extrabold text-blue-600 tracking-tight">UTB Tracker</h1>
            </div>
            <p class="text-gray-400 text-sm font-medium">Pilih peran Anda untuk melanjutkan ke halaman login</p>
        </div>

        <!-- CONTAINER KARTU PILIHAN PERAN -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full max-w-3xl mb-10">

            <!-- KARTU 1: LOGIN MAHASISWA -->
            <a href="login.php?switch=1" class="bg-white rounded-3xl p-8 border border-gray-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 text-center flex flex-col items-center justify-between group">
                <div class="w-full flex flex-col items-center">
                    <!-- Icon Box Mahasiswa -->
                    <div class="w-28 h-28 bg-blue-100/70 rounded-3xl flex items-center justify-center mb-6 group-hover:bg-blue-100 transition">
                        <div class="w-14 h-14 bg-blue-600 text-white rounded-2xl flex flex-col items-center justify-center shadow-md">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <div class="w-5 h-2 bg-white rounded-sm mt-0.5 opacity-90"></div>
                        </div>
                    </div>

                    <h2 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-blue-600 transition">Login Mahasiswa</h2>
                    <p class="text-gray-400 text-xs font-medium leading-relaxed px-4">
                        Klik untuk masuk ke halaman login mahasiswa & mulai isi presensi / logbook magang.
                    </p>
                </div>
            </a>

            <!-- KARTU 2: LOGIN MENTOR -->
            <a href="login-mentor.php?switch=1" class="bg-white rounded-3xl p-8 border border-gray-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 text-center flex flex-col items-center justify-between group">
                <div class="w-full flex flex-col items-center">
                    <!-- Icon Box Mentor -->
                    <div class="w-28 h-28 bg-emerald-100/70 rounded-3xl flex items-center justify-center mb-6 group-hover:bg-emerald-100 transition">
                        <div class="w-14 h-14 bg-emerald-600 text-white rounded-2xl flex flex-col items-center justify-center shadow-md">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <div class="w-5 h-2 bg-white rounded-sm mt-0.5 opacity-90"></div>
                        </div>
                    </div>

                    <h2 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-emerald-600 transition">Login Mentor</h2>
                    <p class="text-gray-400 text-xs font-medium leading-relaxed px-4">
                        Klik untuk masuk ke halaman login mentor & pantau progres mahasiswa bimbingan.
                    </p>
                </div>
            </a>

        </div>
    </div>

</body>

</html>