<?php
// Deteksi nama file halaman aktif untuk penandaan menu
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- OVERLAY BACKDROP MOBILE MENU -->
<div id="sidebarOverlay" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40 hidden md:hidden transition-opacity"></div>

<!-- CONTAINER SIDEBAR MENTOR -->
<aside id="sidebar" class="fixed md:static inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-100 flex flex-col justify-between transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out select-none flex-shrink-0">

    <div>
        <!-- LOGO BRANDING & MOBILE CLOSE BUTTON -->
        <div class="px-6 py-5 flex items-center justify-between border-b border-gray-50">
            <a href="dashboard.php" class="flex items-center gap-3">
                <div class="w-9 h-9 bg-emerald-600 rounded-xl flex items-center justify-center text-white shadow-md shadow-emerald-200">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-base font-extrabold text-gray-800 tracking-tight leading-none">UTB Tracker</h1>
                    <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full inline-block mt-1">Portal Mentor</span>
                </div>
            </a>
            <button id="closeSidebarBtn" class="md:hidden text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- NAVIGASI UTAMA MENTOR (SESUAI WIREFRAME LAYAR 08-11) -->
        <div class="px-4 py-6">
            <p class="px-3 text-[10px] font-extrabold text-gray-400 uppercase tracking-wider mb-3">MENTOR MENU</p>
            <nav class="space-y-1.5">

                <!-- MENU 1: DASHBOARD / MONITORING MAHASISWA -->
                <a href="dashboard.php" class="flex items-center gap-3 px-3.5 py-3 rounded-2xl text-xs font-bold transition-all duration-200 <?php echo ($current_page === 'dashboard.php') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-200' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800'; ?>">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                    </svg>
                    Dashboard
                </a>

                <!-- MENU 2: JADWAL BIMBINGAN -->
                <a href="bimbingan.php" class="flex items-center gap-3 px-3.5 py-3 rounded-2xl text-xs font-bold transition-all duration-200 <?php echo ($current_page === 'bimbingan.php') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-200' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800'; ?>">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Jadwal Bimbingan
                </a>

                <!-- MENU 3: TUGAS & FILE -->
                <a href="tugas.php" class="flex items-center gap-3 px-3.5 py-3 rounded-2xl text-xs font-bold transition-all duration-200 <?php echo ($current_page === 'tugas.php') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-200' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800'; ?>">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Tugas & File
                </a>

                <!-- MENU 4: APPROVAL TUGAS -->
                <a href="approval.php" class="flex items-center gap-3 px-3.5 py-3 rounded-2xl text-xs font-bold transition-all duration-200 <?php echo ($current_page === 'approval.php') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-200' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800'; ?>">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Approval Tugas
                </a>

            </nav>
        </div>
    </div>

    <!-- FOOTER SIDEBAR (TOMBOL LOGOUT / GANTI PERAN) -->
    <div class="p-4 border-t border-gray-100">
        <a href="../login-mentor.php?switch=1" class="flex items-center gap-3 px-3.5 py-3 rounded-2xl text-xs font-bold text-rose-600 hover:bg-rose-50 transition-all duration-200">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
            Keluar (Ganti Peran)
        </a>
    </div>

</aside>

<!-- SCRIPT PENGENDALI SIDEBAR DRAWER MOBILE -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('sidebar');
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const closeSidebarBtn = document.getElementById('closeSidebarBtn');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        const toggleSidebar = () => {
            if (sidebar && sidebarOverlay) {
                sidebar.classList.toggle('-translate-x-full');
                sidebarOverlay.classList.toggle('hidden');
            }
        };

        if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', toggleSidebar);
        if (closeSidebarBtn) closeSidebarBtn.addEventListener('click', toggleSidebar);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', toggleSidebar);
    });
</script>