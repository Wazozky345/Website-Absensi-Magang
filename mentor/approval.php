<?php
// Memanggil otak proses_mentor_approval.php
require_once __DIR__ . '/../proses/proses_mentor_approval.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Tugas Mahasiswa - UTB Tracker</title>
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

        <!-- TOPBAR MENTOR -->
        <header class="bg-white/80 backdrop-blur-md border-b border-gray-100 px-6 py-4 flex justify-between items-center sticky top-0 z-30">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <div>
                    <h2 class="text-base font-bold text-gray-800">Approval Tugas Mahasiswa</h2>
                    <p class="text-xs text-gray-400">Review & validasi tugas harian (Batch Sesi Pagi & Sore)</p>
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

            <!-- BATCH 1: TUGAS MASUK SESI PAGI (S.D 12.00 WIB) -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-4 pb-3 border-b border-gray-100">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm">☀️</span>
                        <div>
                            <h3 class="text-base font-bold text-gray-800">Tugas Masuk - Sesi Pagi</h3>
                            <p class="text-[10px] text-gray-400">Batch review tugas s.d. 12:00 WIB</p>
                        </div>
                    </div>
                    <span class="bg-amber-50 text-amber-700 font-bold text-xs px-3 py-1 rounded-full">
                        <?php echo count($submission_pagi); ?> Berkas Masuk
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="text-gray-400 font-semibold border-b border-gray-100 pb-3">
                                <th class="py-2.5">Mahasiswa</th>
                                <th class="py-2.5">Judul Tugas</th>
                                <th class="py-2.5">File Dikirim</th>
                                <th class="py-2.5">Waktu Kirim</th>
                                <th class="py-2.5 text-center">Status</th>
                                <th class="py-2.5 text-center">Aksi Mentor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-gray-700">
                            <?php if (!empty($submission_pagi)): ?>
                                <?php foreach ($submission_pagi as $sp): 
                                    $file_path = "../uploads/tugas_mahasiswa/" . htmlspecialchars($sp['file_balasan']);
                                    $has_file  = !empty($sp['file_balasan']) && file_exists($file_path);

                                    $status_class = 'bg-amber-50 text-amber-600';
                                    if ($sp['status_approval'] === 'Disetujui') $status_class = 'bg-emerald-50 text-emerald-600';
                                    elseif ($sp['status_approval'] === 'Perlu Revisi') $status_class = 'bg-rose-50 text-rose-600';
                                ?>
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="py-3">
                                            <p class="font-bold text-gray-800"><?php echo htmlspecialchars($sp['nama_user']); ?></p>
                                            <p class="text-[10px] text-gray-400"><?php echo htmlspecialchars($sp['nim']); ?></p>
                                        </td>
                                        <td class="py-3 font-semibold text-gray-700 max-w-xs truncate"><?php echo htmlspecialchars($sp['judul_tugas']); ?></td>
                                        <td class="py-3">
                                            <?php if ($has_file): ?>
                                                <a href="<?php echo $file_path; ?>" download class="text-blue-600 font-semibold hover:underline flex items-center gap-1">
                                                    📄 <?php echo htmlspecialchars($sp['file_balasan']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-300 italic">Berkas tidak ditemukan</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 text-gray-500"><?php echo date('H:i', strtotime($sp['waktu_kirim'])); ?> WIB</td>
                                        <td class="py-3 text-center">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold <?php echo $status_class; ?>">
                                                <?php echo htmlspecialchars($sp['status_approval']); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 text-center space-x-1.5">
                                            <button onclick="prosesApproval('<?php echo $sp['id']; ?>', '<?php echo addslashes($sp['nama_user']); ?>', 'Setujui')" class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-3 py-1.5 rounded-xl transition">Setujui</button>
                                            <button onclick="prosesApproval('<?php echo $sp['id']; ?>', '<?php echo addslashes($sp['nama_user']); ?>', 'Revisi')" class="bg-rose-50 text-rose-600 hover:bg-rose-100 font-bold px-3 py-1.5 rounded-xl transition">Minta Revisi</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-gray-400 font-medium">Belum ada tugas masuk di sesi pagi.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- BATCH 2: TUGAS MASUK SESI SORE (S.D 17.00 WIB) -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-4 pb-3 border-b border-gray-100">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold text-sm">🌙</span>
                        <div>
                            <h3 class="text-base font-bold text-gray-800">Tugas Masuk - Sesi Sore</h3>
                            <p class="text-[10px] text-gray-400">Batch review tugas s.d. 17:00 WIB</p>
                        </div>
                    </div>
                    <span class="bg-purple-50 text-purple-700 font-bold text-xs px-3 py-1 rounded-full">
                        <?php echo count($submission_sore); ?> Berkas Masuk
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="text-gray-400 font-semibold border-b border-gray-100 pb-3">
                                <th class="py-2.5">Mahasiswa</th>
                                <th class="py-2.5">Judul Tugas</th>
                                <th class="py-2.5">File Dikirim</th>
                                <th class="py-2.5">Waktu Kirim</th>
                                <th class="py-2.5 text-center">Status</th>
                                <th class="py-2.5 text-center">Aksi Mentor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-gray-700">
                            <?php if (!empty($submission_sore)): ?>
                                <?php foreach ($submission_sore as $ss): 
                                    $file_path = "../uploads/tugas_mahasiswa/" . htmlspecialchars($ss['file_balasan']);
                                    $has_file  = !empty($ss['file_balasan']) && file_exists($file_path);

                                    $status_class = 'bg-amber-50 text-amber-600';
                                    if ($ss['status_approval'] === 'Disetujui') $status_class = 'bg-emerald-50 text-emerald-600';
                                    elseif ($ss['status_approval'] === 'Perlu Revisi') $status_class = 'bg-rose-50 text-rose-600';
                                ?>
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="py-3">
                                            <p class="font-bold text-gray-800"><?php echo htmlspecialchars($ss['nama_user']); ?></p>
                                            <p class="text-[10px] text-gray-400"><?php echo htmlspecialchars($ss['nim']); ?></p>
                                        </td>
                                        <td class="py-3 font-semibold text-gray-700 max-w-xs truncate"><?php echo htmlspecialchars($ss['judul_tugas']); ?></td>
                                        <td class="py-3">
                                            <?php if ($has_file): ?>
                                                <a href="<?php echo $file_path; ?>" download class="text-blue-600 font-semibold hover:underline flex items-center gap-1">
                                                    📝 <?php echo htmlspecialchars($ss['file_balasan']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-300 italic">Berkas tidak ditemukan</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 text-gray-500"><?php echo date('H:i', strtotime($ss['waktu_kirim'])); ?> WIB</td>
                                        <td class="py-3 text-center">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold <?php echo $status_class; ?>">
                                                <?php echo htmlspecialchars($ss['status_approval']); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 text-center space-x-1.5">
                                            <button onclick="prosesApproval('<?php echo $ss['id']; ?>', '<?php echo addslashes($ss['nama_user']); ?>', 'Setujui')" class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-3 py-1.5 rounded-xl transition">Setujui</button>
                                            <button onclick="prosesApproval('<?php echo $ss['id']; ?>', '<?php echo addslashes($ss['nama_user']); ?>', 'Revisi')" class="bg-rose-50 text-rose-600 hover:bg-rose-100 font-bold px-3 py-1.5 rounded-xl transition">Minta Revisi</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-gray-400 font-medium">Belum ada tugas masuk di sesi sore.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <!-- FORM POST APPROVAL HIDDEN -->
    <form id="formApprovalSubmit" method="POST" action="../proses/proses_mentor_approval.php" class="hidden">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
        <input type="hidden" name="id_tugas" id="postTugasId">
        <input type="hidden" name="keputusan" id="postKeputusan">
        <input type="hidden" name="catatan_mentor" id="postCatatanMentor">
    </form>

    <?php 
    if (file_exists(__DIR__ . '/../components/alert.php')) {
        include __DIR__ . '/../components/alert.php';
    } 
    ?>

    <script>
        function prosesApproval(id, nama, jenis) {
            if (jenis === 'Setujui') {
                Swal.fire({
                    title: 'Setujui Tugas?',
                    text: 'Tugas milik ' + nama + ' akan ditandai Disetujui.',
                    icon: 'question',
                    input: 'text',
                    inputPlaceholder: 'Catatan apresiasi/petunjuk (opsional)',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Setujui',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#10b981'
                }).then((res) => {
                    if (res.isConfirmed) {
                        document.getElementById('postTugasId').value = id;
                        document.getElementById('postKeputusan').value = 'Disetujui';
                        document.getElementById('postCatatanMentor').value = res.value || 'Sudah sesuai, lanjut ke tahap berikutnya.';
                        document.getElementById('formApprovalSubmit').submit();
                    }
                });
            } else {
                Swal.fire({
                    title: 'Minta Revisi Tugas?',
                    text: 'Berikan alasan perbaikan untuk ' + nama,
                    icon: 'warning',
                    input: 'textarea',
                    inputPlaceholder: 'Tuliskan bagian yang perlu diperbaiki...',
                    inputValidator: (value) => {
                        if (!value) {
                            return 'Catatan revisi wajib diisi!';
                        }
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Kirim Instruksi Revisi',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#e11d48'
                }).then((res) => {
                    if (res.isConfirmed) {
                        document.getElementById('postTugasId').value = id;
                        document.getElementById('postKeputusan').value = 'Perlu Revisi';
                        document.getElementById('postCatatanMentor').value = res.value;
                        document.getElementById('formApprovalSubmit').submit();
                    }
                });
            }
        }

        // SCRIPT DRAWER MOBILE
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
</body>
</html>