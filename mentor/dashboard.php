<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/koneksi.php';

// Proteksi Hak Akses Role Mentor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'mentor') {
    header("Location: ../login-mentor.php");
    exit;
}

$mentor_id   = $_SESSION['user_id'];
$nama_mentor = $_SESSION['nama_user'] ?? 'Mentor Bimbingan';

// Query Ringkasan Statistik Mentor
$q_mhs = $conn->query("SELECT COUNT(id) as total FROM users");
$total_mhs = ($q_mhs) ? $q_mhs->fetch_assoc()['total'] : 0;

$q_pending = $conn->query("SELECT COUNT(id) as total FROM tugas_detail WHERE status_approval = 'Menunggu Review'");
$total_pending = ($q_pending) ? $q_pending->fetch_assoc()['total'] : 0;

$q_bimbingan = $conn->query("SELECT COUNT(id) as total FROM bimbingan WHERE mentor_id = '$mentor_id' AND DATE(tanggal_waktu) = CURDATE()");
$total_bimbingan_hari_ini = ($q_bimbingan) ? $q_bimbingan->fetch_assoc()['total'] : 0;

$q_approved = $conn->query("SELECT COUNT(id) as total FROM tugas_detail WHERE status_approval = 'Disetujui'");
$total_approved = ($q_approved) ? $q_approved->fetch_assoc()['total'] : 0;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mentor - UTB Tracker</title>
    <link rel="stylesheet" href="../assets/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="flex h-screen overflow-hidden text-gray-800 bg-[#F4F7FE]">

    <?php include '../components/sidebar_mentor.php'; ?>

    <main class="flex-1 flex flex-col overflow-y-auto w-full relative">

        <!-- TOPBAR MENTOR -->
        <header class="bg-white/80 backdrop-blur-md border-b border-gray-100 px-6 py-4 flex justify-between items-center sticky top-0 z-30">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <div>
                    <h2 class="text-base font-bold text-gray-800">Dashboard Portal Pembimbing</h2>
                    <p class="text-xs text-gray-400">Ringkasan aktivitas bimbingan dan evaluasi tugas mahasiswa</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="text-right hidden sm:block">
                    <p class="text-xs font-bold text-gray-800"><?php echo htmlspecialchars($nama_mentor); ?></p>
                    <p class="text-[10px] text-gray-400">Pembimbing Lapangan</p>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($nama_mentor); ?>&background=2563eb&color=ffffff&size=128" class="w-9 h-9 rounded-full border-2 border-blue-100">
            </div>
        </header>

        <div class="p-4 md:p-8 space-y-6">

            <!-- METRIK KARTU RINGKASAN -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                <div class="bg-blue-600 text-white rounded-3xl p-6 shadow-lg shadow-blue-200">
                    <p class="text-blue-100 font-medium text-xs mb-1">Mahasiswa Bimbingan</p>
                    <h3 class="text-3xl font-extrabold"><?php echo $total_mhs; ?> <span class="text-sm font-normal text-blue-200">Orang</span></h3>
                </div>
                <div class="bg-amber-500 text-white rounded-3xl p-6 shadow-lg shadow-amber-200">
                    <p class="text-amber-100 font-medium text-xs mb-1">Tugas Perlu Review</p>
                    <h3 class="text-3xl font-extrabold"><?php echo $total_pending; ?> <span class="text-sm font-normal text-amber-200">Berkas</span></h3>
                </div>
                <div class="bg-emerald-500 text-white rounded-3xl p-6 shadow-lg shadow-emerald-200">
                    <p class="text-emerald-100 font-medium text-xs mb-1">Tugas Disetujui</p>
                    <h3 class="text-3xl font-extrabold"><?php echo $total_approved; ?> <span class="text-sm font-normal text-emerald-200">Selesai</span></h3>
                </div>
                <div class="bg-indigo-600 text-white rounded-3xl p-6 shadow-lg shadow-indigo-200">
                    <p class="text-indigo-100 font-medium text-xs mb-1">Bimbingan Hari Ini</p>
                    <h3 class="text-3xl font-extrabold"><?php echo $total_bimbingan_hari_ini; ?> <span class="text-sm font-normal text-indigo-200">Sesi</span></h3>
                </div>
            </div>

            <!-- DUA KOLOM: PENUGASAN CEPAT & JADWAL BAMBINGAN -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- TABEL TUGAS PERLU REVIEW SEGERA -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 lg:col-span-2">
                    <div class="flex justify-between items-center mb-5 pb-3 border-b border-gray-100">
                        <div>
                            <h3 class="text-base font-bold text-gray-800">Antrean Review Tugas</h3>
                            <p class="text-xs text-gray-400">Berkas masuk yang membutuhkan persetujuan mentor</p>
                        </div>
                        <a href="approval.php" class="text-xs font-bold text-blue-600 hover:underline">Lihat Semua</a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="text-gray-400 font-semibold border-b border-gray-100 pb-2">
                                    <th class="py-2.5">Mahasiswa</th>
                                    <th class="py-2.5">Judul Tugas</th>
                                    <th class="py-2.5">Waktu Pengumpulan</th>
                                    <th class="py-2.5 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 text-gray-700">
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="py-3 font-bold text-gray-800">Alvin Nurfaiz</td>
                                    <td class="py-3">Analisis Kebutuhan Sistem W7</td>
                                    <td class="py-3 text-gray-400">Hari ini, 08:14 WIB</td>
                                    <td class="py-3 text-center">
                                        <a href="approval.php" class="bg-blue-50 text-blue-600 hover:bg-blue-100 font-bold px-3 py-1.5 rounded-xl transition inline-block">Review</a>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="py-3 font-bold text-gray-800">M. Yusman Bayuga</td>
                                    <td class="py-3">Revisi Laporan BAB II</td>
                                    <td class="py-3 text-gray-400">Hari ini, 15:22 WIB</td>
                                    <td class="py-3 text-center">
                                        <a href="approval.php" class="bg-blue-50 text-blue-600 hover:bg-blue-100 font-bold px-3 py-1.5 rounded-xl transition inline-block">Review</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- JADWAL BAMBINGAN TERDEKAT -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-center mb-5 pb-3 border-b border-gray-100">
                            <h3 class="text-base font-bold text-gray-800">Agenda Bimbingan</h3>
                            <a href="bimbingan.php" class="text-xs font-bold text-blue-600 hover:underline">Kelola</a>
                        </div>

                        <div class="space-y-3">
                            <div class="p-3.5 bg-blue-50/60 border border-blue-100 rounded-2xl flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-600 text-white font-bold flex items-center justify-center text-xs">13:00</div>
                                <div>
                                    <p class="text-xs font-bold text-gray-800">Alvin Nurfaiz</p>
                                    <p class="text-[10px] text-gray-500">Tatap Muka - Ruang Lapangan BRI</p>
                                </div>
                            </div>
                            <div class="p-3.5 bg-emerald-50/60 border border-emerald-100 rounded-2xl flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white font-bold flex items-center justify-center text-xs">15:30</div>
                                <div>
                                    <p class="text-xs font-bold text-gray-800">M. Yusman Bayuga</p>
                                    <p class="text-[10px] text-gray-500">Online - Google Meet</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <a href="tugas.php" class="mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white text-center font-bold py-3 rounded-2xl text-xs shadow-md shadow-blue-200 transition">
                        + Distribusi Tugas Baru
                    </a>
                </div>

            </div>

        </div>
    </main>

    <?php include '../components/alert.php'; ?>

</body>

</html>