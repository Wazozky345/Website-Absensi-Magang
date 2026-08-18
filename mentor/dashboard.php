<?php
// Murni memanggil otak dashboard mentor dari folder proses
require_once __DIR__ . '/../proses/proses_dashboard_mentor.php';
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

    <?php 
    if (file_exists(__DIR__ . '/../components/sidebar_mentor.php')) {
        include __DIR__ . '/../components/sidebar_mentor.php';
    } elseif (file_exists(__DIR__ . '/../components/sidebar.php')) {
        include __DIR__ . '/../components/sidebar.php';
    }
    ?>

    <main class="flex-1 flex flex-col overflow-y-auto w-full relative">

        <!-- TOPBAR MENTOR WITH PROFILE DROPDOWN -->
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

            <!-- WIDGET PROFIL POJOK KANAN (DROPDOWN TRIGGER) -->
            <div class="relative">
                <button id="profileDropdownBtn" onclick="toggleProfileDropdown()" class="flex items-center gap-3 p-1.5 rounded-2xl hover:bg-gray-100 transition focus:outline-none">
                    <div class="text-right hidden sm:block">
                        <p class="text-xs font-bold text-gray-800"><?php echo htmlspecialchars($nama_mentor); ?></p>
                        <p class="text-[10px] text-gray-400">Pembimbing Associate</p>
                    </div>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($nama_mentor); ?>&background=2563eb&color=ffffff&size=128" class="w-9 h-9 rounded-full border-2 border-blue-100 shadow-sm">
                </button>

                <!-- MENU DROPDOWN POJOK KANAN -->
                <div id="profileDropdownMenu" class="hidden absolute right-0 mt-2 w-52 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 z-50 transition-all">
                    <div class="px-4 py-2 border-b border-gray-100">
                        <p class="text-xs font-bold text-gray-800"><?php echo htmlspecialchars($nama_mentor); ?></p>
                        <p class="text-[10px] text-emerald-600 font-semibold">● Sesi Mentor Aktif</p>
                    </div>

                    <!-- TOMBOL BUKA MODAL UBAH PIN -->
                    <button type="button" onclick="openModalUbahPinMentor()" class="w-full text-left flex items-center gap-2.5 px-4 py-2.5 text-xs text-gray-700 hover:bg-blue-50 hover:text-blue-600 font-medium transition">
                        🔑 Ubah PIN Keamanan
                    </button>

                    <div class="border-t border-gray-100 my-1"></div>

                    <a href="../logout.php" class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-rose-600 hover:bg-rose-50 font-bold transition">
                        🚪 Keluar / Logout
                    </a>
                </div>
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
                                <!-- INJEKSI DATA DINAMIS DARI DATABASE -->
                                <?php if (!empty($antrean_tugas)): ?>
                                    <?php foreach ($antrean_tugas as $at): 
                                        $waktu_ts = strtotime($at['waktu_kirim']);
                                        $is_today = (date('Y-m-d', $waktu_ts) === date('Y-m-d'));
                                        $tampil_waktu = $is_today ? 'Hari ini, ' . date('H:i', $waktu_ts) . ' WIB' : date('d M Y, H:i', $waktu_ts) . ' WIB';
                                    ?>
                                        <tr class="hover:bg-gray-50/50 transition">
                                            <td class="py-3 font-bold text-gray-800"><?php echo htmlspecialchars($at['nama_user']); ?></td>
                                            <td class="py-3 truncate max-w-[150px]"><?php echo htmlspecialchars($at['judul_tugas']); ?></td>
                                            <td class="py-3 text-gray-400"><?php echo $tampil_waktu; ?></td>
                                            <td class="py-3 text-center">
                                                <a href="approval.php" class="bg-blue-50 text-blue-600 hover:bg-blue-100 font-bold px-3 py-1.5 rounded-xl transition inline-block">Review</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="py-6 text-center text-gray-400 font-medium">Belum ada antrean tugas yang perlu direview.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- JADWAL BIMBINGAN TERDEKAT -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-center mb-5 pb-3 border-b border-gray-100">
                            <h3 class="text-base font-bold text-gray-800">Agenda Hari Ini</h3>
                            <a href="bimbingan.php" class="text-xs font-bold text-blue-600 hover:underline">Kelola</a>
                        </div>

                        <div class="space-y-3">
                            <!-- INJEKSI DATA DINAMIS DARI DATABASE -->
                            <?php if (!empty($agenda_bimbingan)): ?>
                                <?php foreach ($agenda_bimbingan as $ab): 
                                    $jam = date('H:i', strtotime($ab['tanggal_waktu']));
                                    $metode = htmlspecialchars($ab['metode']);
                                    $warna_bg = ($metode === 'Online') ? 'emerald' : 'blue';
                                ?>
                                    <div class="p-3.5 bg-<?php echo $warna_bg; ?>-50/60 border border-<?php echo $warna_bg; ?>-100 rounded-2xl flex items-center gap-3 transition hover:shadow-sm">
                                        <div class="w-10 h-10 rounded-xl bg-<?php echo $warna_bg; ?>-600 text-white font-bold flex items-center justify-center text-xs"><?php echo $jam; ?></div>
                                        <div>
                                            <p class="text-xs font-bold text-gray-800"><?php echo htmlspecialchars($ab['nama_user']); ?></p>
                                            <p class="text-[10px] text-gray-500"><?php echo $metode; ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-6">
                                    <p class="text-xs font-medium text-gray-400">Tidak ada jadwal bimbingan hari ini.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <a href="tugas.php" class="mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white text-center font-bold py-3 rounded-2xl text-xs shadow-md shadow-blue-200 transition flex justify-center items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Distribusi Tugas Baru
                    </a>
                </div>

            </div>

        </div>
    </main>

    <!-- MODAL POPUP UBAH PIN DI DASHBOARD MENTOR -->
    <div id="modalUbahPinMentor" class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 transition-all">
        <div class="bg-white rounded-3xl shadow-2xl border border-gray-100 w-full max-w-md overflow-hidden transform transition-all scale-95 opacity-0 duration-300" id="modalUbahPinContent">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <div>
                    <h3 class="text-base font-bold text-gray-800">Ubah PIN Keamanan</h3>
                    <p class="text-xs text-gray-400">Ganti PIN 4-digit untuk autentikasi login Anda</p>
                </div>
                <button type="button" onclick="closeModalUbahPinMentor()" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form method="POST" action="../proses/proses_ubah_pin_mentor.php" class="p-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">PIN Saat Ini (Lama) <span class="text-rose-500">*</span></label>
                    <input type="password" name="pin_lama" maxlength="4" inputmode="numeric" required placeholder="••••" class="w-full bg-gray-50 border border-gray-200 rounded-2xl p-3 text-xs text-gray-800 focus:outline-none focus:border-blue-500 font-bold tracking-widest">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">PIN Baru (4 Digit) <span class="text-rose-500">*</span></label>
                    <input type="password" name="pin_baru" maxlength="4" inputmode="numeric" required placeholder="••••" class="w-full bg-gray-50 border border-gray-200 rounded-2xl p-3 text-xs text-gray-800 focus:outline-none focus:border-blue-500 font-bold tracking-widest">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Konfirmasi PIN Baru <span class="text-rose-500">*</span></label>
                    <input type="password" name="konfirmasi_pin" maxlength="4" inputmode="numeric" required placeholder="••••" class="w-full bg-gray-50 border border-gray-200 rounded-2xl p-3 text-xs text-gray-800 focus:outline-none focus:border-blue-500 font-bold tracking-widest">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                    <button type="button" onclick="closeModalUbahPinMentor()" class="px-4 py-2.5 rounded-2xl text-xs font-bold text-gray-500 hover:bg-gray-100 transition">
                        Batal
                    </button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-5 py-2.5 rounded-2xl shadow-lg shadow-blue-200 transition text-xs">
                        Simpan PIN Baru
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPT DROPDOWN & MODAL POPUP -->
    <script>
        function toggleProfileDropdown() {
            const dropdown = document.getElementById('profileDropdownMenu');
            if (dropdown) dropdown.classList.toggle('hidden');
        }

        function openModalUbahPinMentor() {
            const dropdown = document.getElementById('profileDropdownMenu');
            if (dropdown) dropdown.classList.add('hidden'); // Tutup dropdown

            const modal = document.getElementById('modalUbahPinMentor');
            const content = document.getElementById('modalUbahPinContent');
            if (!modal || !content) return;

            modal.classList.remove('hidden');
            setTimeout(() => {
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeModalUbahPinMentor() {
            const modal = document.getElementById('modalUbahPinMentor');
            const content = document.getElementById('modalUbahPinContent');
            if (!modal || !content) return;

            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');

            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        // TUTUP DROPDOWN SAAT KLIK DI LUAR AREA PROFIL
        document.addEventListener('click', function(e) {
            const btn = document.getElementById('profileDropdownBtn');
            const dropdown = document.getElementById('profileDropdownMenu');
            if (btn && dropdown && !btn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    </script>

    <?php 
    if (file_exists(__DIR__ . '/../components/alert.php')) {
        include __DIR__ . '/../components/alert.php';
    } 
    ?>

</body>

</html>