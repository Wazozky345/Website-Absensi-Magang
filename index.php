<?php
// 1. Inisialisasi Sesi jika belum berjalan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generasi CSRF Token jika belum ada
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
    <title>UTB Tracker - Pilih Peran Portal</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>

<body class="bg-[#F4F7FE] text-gray-800 min-h-screen flex flex-col items-center justify-center p-4 select-none relative">

    <!-- TOP NAVIGATION BAR (TOMBOL DI SEBELAH KIRI) -->
    <header class="fixed top-0 left-0 right-0 p-4 sm:p-6 flex justify-between items-center z-30">
        <!-- TOMBOL KEMBALI KE LANDING PAGE UTAMA (POJOK KIRI) -->
        <a href="landing_page_utama.php" class="inline-flex items-center gap-2 px-4 py-2 bg-white/80 backdrop-blur-md border border-gray-200 text-gray-600 hover:text-blue-600 hover:border-blue-200 font-bold text-xs rounded-full shadow-sm hover:shadow-md transition-all duration-200 hover:scale-105">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Halaman Utama
        </a>

        <!-- INDIKATOR STATUS (POJOK KANAN) -->
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="text-xs font-bold text-gray-500 tracking-wider uppercase">Pilih Peran Login</span>
        </div>
    </header>

    <div class="w-full max-w-4xl mx-auto flex flex-col items-center pt-12">

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
            <p class="text-gray-400 text-sm font-medium">Pilih peran Anda untuk melanjutkan ke portal sistem</p>
        </div>

        <!-- CONTAINER KARTU PILIHAN PERAN -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full max-w-3xl mb-10">

            <!-- KARTU 1: LOGIN MAHASISWA -->
            <a href="login.php?switch=1" class="bg-white rounded-3xl p-8 border border-gray-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 text-center flex flex-col items-center justify-between group">
                <div class="w-full flex flex-col items-center">
                    <div class="w-28 h-28 bg-blue-50 rounded-3xl flex items-center justify-center mb-6 group-hover:bg-blue-100/80 transition">
                        <div class="w-14 h-14 bg-blue-600 text-white rounded-2xl flex flex-col items-center justify-center shadow-md shadow-blue-200 group-hover:scale-105 transition-transform">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                    </div>

                    <h2 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-blue-600 transition">Portal Mahasiswa</h2>
                    <p class="text-gray-400 text-xs font-medium leading-relaxed px-4">
                        Masuk untuk melakukan presensi harian, mengisi logbook, dan mengunggah berkas laporan magang.
                    </p>
                </div>
            </a>

            <!-- KARTU 2: LOGIN MENTOR -->
            <a href="login-mentor.php?switch=1" class="bg-white rounded-3xl p-8 border border-gray-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 text-center flex flex-col items-center justify-between group">
                <div class="w-full flex flex-col items-center">
                    <div class="w-28 h-28 bg-emerald-50 rounded-3xl flex items-center justify-center mb-6 group-hover:bg-emerald-100/80 transition">
                        <div class="w-14 h-14 bg-emerald-600 text-white rounded-2xl flex flex-col items-center justify-center shadow-md shadow-emerald-200 group-hover:scale-105 transition-transform">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>

                    <h2 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-emerald-600 transition">Portal Mentor / Pembimbing</h2>
                    <p class="text-gray-400 text-xs font-medium leading-relaxed px-4">
                        Masuk untuk mereview berkas tugas, menyetujui laporan, dan mengelola jadwal bimbingan mahasiswa.
                    </p>
                </div>
            </a>

        </div>
    </div>

</body>

</html>