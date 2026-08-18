<?php
// Memanggil backend pengolah approval
require_once __DIR__ . '/../proses/proses_mentor_approval.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval & Paraf Logbook / Tugas - UTB Tracker</title>
    <link rel="stylesheet" href="../assets/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="flex h-screen overflow-hidden text-gray-800 bg-[#F4F7FE] relative">

    <!-- OVERLAY SIDEBAR UNTUK TAMPILAN HP -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden backdrop-blur-sm transition-opacity"></div>

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
                <!-- TOMBOL HAMBURGER MOBILE -->
                <button id="mobileMenuBtn" type="button" class="md:hidden text-gray-600 hover:text-blue-600 p-2 rounded-xl border border-gray-200 bg-white shadow-sm focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <div>
                    <h2 class="text-base font-bold text-gray-800">Approval & Paraf Logbook Mahasiswa</h2>
                    <p class="text-xs text-gray-400">Review, validasi, dan pembubuhan paraf digital tugas sesi pagi</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="text-right hidden sm:block">
                    <p class="text-xs font-bold text-gray-800"><?php echo htmlspecialchars($nama_mentor ?? 'Mentor'); ?></p>
                    <p class="text-[10px] text-gray-400">Pembimbing Lapangan</p>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($nama_mentor ?? 'Mentor'); ?>&background=2563eb&color=ffffff&size=128" class="w-9 h-9 rounded-full border-2 border-blue-100">
            </div>
        </header>

        <div class="p-4 md:p-8 space-y-6">

            <!-- BATCH 1: LOGBOOK / TUGAS MASUK - SESI PAGI -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-4 pb-3 border-b border-gray-100">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm">☀️</span>
                        <div>
                            <h3 class="text-base font-bold text-gray-800">Logbook / Tugas Masuk - Sesi Pagi</h3>
                            <p class="text-[10px] text-gray-400">Batch pengumpulan & review tugas sesi pagi (s.d. 12:00 WIB)</p>
                        </div>
                    </div>
                    <span class="bg-amber-50 text-amber-700 font-bold text-xs px-3 py-1 rounded-full">
                        <?php echo count($submission_pagi ?? []); ?> Berkas Masuk
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="text-gray-400 font-semibold border-b border-gray-100 pb-3">
                                <th class="py-2.5">Mahasiswa</th>
                                <th class="py-2.5">Judul Logbook/Tugas</th>
                                <th class="py-2.5">File Lampiran</th>
                                <th class="py-2.5">Waktu Kirim</th>
                                <th class="py-2.5 text-center">Status</th>
                                <th class="py-2.5 text-center">Aksi Mentor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-gray-700">
                            <?php if (!empty($submission_pagi)): ?>
                                <?php foreach ($submission_pagi as $sp): 
                                    $file_path = "../uploads/tugas_mahasiswa/" . htmlspecialchars($sp['file_balasan'] ?? '');
                                    $has_file  = !empty($sp['file_balasan']) && file_exists($file_path);

                                    $status_class = 'bg-amber-50 text-amber-600';
                                    if (($sp['status_approval'] ?? '') === 'Disetujui') $status_class = 'bg-emerald-50 text-emerald-600';
                                    elseif (($sp['status_approval'] ?? '') === 'Perlu Revisi') $status_class = 'bg-rose-50 text-rose-600';
                                ?>
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="py-3">
                                            <p class="font-bold text-gray-800"><?php echo htmlspecialchars($sp['nama_user']); ?></p>
                                            <p class="text-[10px] text-gray-400"><?php echo htmlspecialchars($sp['nim'] ?? '-'); ?></p>
                                        </td>
                                        <td class="py-3 font-semibold text-gray-700 max-w-xs truncate"><?php echo htmlspecialchars($sp['judul_tugas'] ?? 'Logbook Sesi Pagi'); ?></td>
                                        <td class="py-3">
                                            <?php if ($has_file): ?>
                                                <a href="<?php echo $file_path; ?>" download class="text-blue-600 font-semibold hover:underline flex items-center gap-1">
                                                    📄 <?php echo htmlspecialchars($sp['file_balasan']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-300 italic">Tanpa Berkas</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 text-gray-500"><?php echo date('H:i', strtotime($sp['waktu_kirim'] ?? 'now')); ?> WIB</td>
                                        <td class="py-3 text-center">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold <?php echo $status_class; ?>">
                                                <?php echo htmlspecialchars($sp['status_approval'] ?? 'Pending'); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 text-center">
                                            <button onclick="bukaModalApproval('<?php echo $sp['id']; ?>', '<?php echo addslashes($sp['nama_user']); ?>', '<?php echo addslashes($sp['judul_tugas'] ?? 'Logbook Sesi Pagi'); ?>')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-3 py-1.5 rounded-xl transition shadow-sm text-[11px]">
                                                Verifikasi & Paraf
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-gray-400 font-medium">Belum ada logbook/tugas masuk di sesi pagi.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- BATCH 2: LOGBOOK PARAF MENTOR DARI TUGAS SESI PAGI -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-4 pb-3 border-b border-gray-100">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm">✍️</span>
                        <div>
                            <h3 class="text-base font-bold text-gray-800">Logbook Paraf Mentor (Tugas Sesi Pagi)</h3>
                            <p class="text-[10px] text-gray-400">Rekapitulasi logbook & tugas sesi pagi mahasiswa yang sudah dikerjakan dan diparaf</p>
                        </div>
                    </div>
                    <span class="bg-emerald-50 text-emerald-700 font-bold text-xs px-3 py-1 rounded-full">
                        <?php echo count($submission_sore ?? []); ?> Logbook Diparaf
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="text-gray-400 font-semibold border-b border-gray-100 pb-3">
                                <th class="py-2.5">Mahasiswa</th>
                                <th class="py-2.5">Tugas Sesi Pagi</th>
                                <th class="py-2.5">Hasil Berkas Pagi</th>
                                <th class="py-2.5 text-center">Paraf Mentor</th>
                                <th class="py-2.5 text-center">Status Verification</th>
                                <th class="py-2.5 text-center">Aksi / Detail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-gray-700">
                            <?php if (!empty($submission_sore)): ?>
                                <?php foreach ($submission_sore as $ss): 
                                    $file_path = "../uploads/tugas_mahasiswa/" . htmlspecialchars($ss['file_balasan'] ?? '');
                                    $has_file  = !empty($ss['file_balasan']) && file_exists($file_path);
                                    $has_paraf = !empty($ss['paraf_mentor']);

                                    $status_class = 'bg-emerald-50 text-emerald-600';
                                    if (($ss['status_approval'] ?? '') === 'Perlu Revisi') $status_class = 'bg-rose-50 text-rose-600';
                                    elseif (($ss['status_approval'] ?? '') === 'Menunggu Review') $status_class = 'bg-amber-50 text-amber-600';
                                ?>
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="py-3">
                                            <p class="font-bold text-gray-800"><?php echo htmlspecialchars($ss['nama_user']); ?></p>
                                            <p class="text-[10px] text-gray-400"><?php echo htmlspecialchars($ss['nim'] ?? '-'); ?></p>
                                        </td>
                                        <td class="py-3 font-semibold text-gray-700 max-w-xs truncate"><?php echo htmlspecialchars($ss['judul_tugas'] ?? 'Logbook Sesi Pagi'); ?></td>
                                        <td class="py-3">
                                            <?php if ($has_file): ?>
                                                <a href="<?php echo $file_path; ?>" download class="text-blue-600 font-semibold hover:underline flex items-center gap-1">
                                                    📝 <?php echo htmlspecialchars($ss['file_balasan']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-300 italic">Tanpa Berkas</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 text-center">
                                            <?php if ($has_paraf): ?>
                                                <img src="<?php echo $ss['paraf_mentor']; ?>" alt="Paraf Mentor" class="h-8 mx-auto border border-gray-100 rounded bg-gray-50 p-1">
                                            <?php else: ?>
                                                <span class="text-[10px] text-gray-400 bg-gray-100 px-2 py-0.5 rounded">Belum Diparaf</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 text-center">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold <?php echo $status_class; ?>">
                                                <?php echo htmlspecialchars($ss['status_approval'] ?? 'Disetujui'); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 text-center">
                                            <button onclick="bukaModalApproval('<?php echo $ss['id']; ?>', '<?php echo addslashes($ss['nama_user']); ?>', '<?php echo addslashes($ss['judul_tugas'] ?? 'Logbook Sesi Pagi'); ?>')" class="bg-gray-800 hover:bg-black text-white font-bold px-3 py-1.5 rounded-xl transition shadow-sm text-[11px]">
                                                Update Paraf
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-gray-400 font-medium">Belum ada rekapitulasi paraf mentor untuk tugas sesi pagi.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <!-- MODAL APPROVAL & CANVAS PARAF -->
    <div id="modalApprovalDialog" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/50 p-4 backdrop-blur-sm">
        <div class="bg-white rounded-3xl w-full max-w-md p-6 shadow-2xl border border-gray-100 space-y-4">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-base font-bold text-gray-800">Verifikasi & Pembubuhan Paraf</h3>
                <button onclick="tutupModalApproval()" class="text-gray-400 hover:text-gray-600 font-bold">✕</button>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase block">Mahasiswa</span>
                    <p id="modalNamaMahasiswa" class="font-bold text-gray-800 text-sm">-</p>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase block">Judul Logbook / Tugas Sesi Pagi</span>
                    <p id="modalJudulTugas" class="font-medium text-gray-700 bg-gray-50 p-2.5 rounded-xl border border-gray-100">-</p>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Keputusan Mentor</label>
                    <select id="modalKeputusan" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 font-medium focus:outline-none focus:border-blue-500">
                        <option value="Disetujui">✅ Setujui & Paraf Logbook</option>
                        <option value="Perlu Revisi">⚠️ Perlu Revisi</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Catatan Evaluasi Mentor</label>
                    <textarea id="modalCatatan" rows="2" placeholder="Tuliskan catatan evaluasi atau instruksi..." class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 focus:outline-none focus:border-blue-500 resize-none"></textarea>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label class="block font-bold text-gray-700">Paraf Digital Mentor</label>
                        <button type="button" onclick="clearCanvas()" class="text-[10px] text-rose-500 font-bold hover:underline">Hapus Goresan</button>
                    </div>
                    <div class="border border-gray-200 rounded-2xl overflow-hidden bg-white">
                        <canvas id="canvasParaf" width="380" height="110" class="w-full touch-none cursor-crosshair"></canvas>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t">
                <button type="button" onclick="tutupModalApproval()" class="px-4 py-2 text-xs font-bold text-gray-500 rounded-xl border border-gray-200 hover:bg-gray-50">Batal</button>
                <button type="button" onclick="kirimApproval()" class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md">Simpan Paraf & Approval</button>
            </div>
        </div>
    </div>

    <!-- FORM POST APPROVAL HIDDEN -->
    <form id="formApprovalSubmit" method="POST" action="../proses/proses_mentor_approval.php" class="hidden">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
        <input type="hidden" name="id_tugas" id="postTugasId">
        <input type="hidden" name="status_approval" id="postKeputusan">
        <input type="hidden" name="catatan_mentor" id="postCatatanMentor">
        <input type="hidden" name="paraf_base64" id="postParafBase64">
    </form>

    <?php 
    if (file_exists(__DIR__ . '/../components/alert.php')) {
        include __DIR__ . '/../components/alert.php';
    } 
    ?>

    <script>
        // LOGIKA CANVAS PARAF DIGITAL
        const canvas = document.getElementById('canvasParaf');
        const ctx = canvas.getContext('2d');
        let isDrawing = false;

        ctx.strokeStyle = '#1e293b';
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';

        function getPos(e) {
            const rect = canvas.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            return { x: clientX - rect.left, y: clientY - rect.top };
        }

        function startDraw(e) { isDrawing = true; const pos = getPos(e); ctx.beginPath(); ctx.moveTo(pos.x, pos.y); }
        function draw(e) { if (!isDrawing) return; const pos = getPos(e); ctx.lineTo(pos.x, pos.y); ctx.stroke(); }
        function stopDraw() { isDrawing = false; }

        canvas.addEventListener('mousedown', startDraw);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDraw);
        canvas.addEventListener('touchstart', startDraw);
        canvas.addEventListener('touchmove', draw);
        canvas.addEventListener('touchend', stopDraw);

        function clearCanvas() { ctx.clearRect(0, 0, canvas.width, canvas.height); }

        function bukaModalApproval(id, nama, judul) {
            document.getElementById('postTugasId').value = id;
            document.getElementById('modalNamaMahasiswa').innerText = nama;
            document.getElementById('modalJudulTugas').innerText = judul;
            document.getElementById('modalCatatan').value = '';
            clearCanvas();
            document.getElementById('modalApprovalDialog').classList.remove('hidden');
        }

        function tutupModalApproval() {
            document.getElementById('modalApprovalDialog').classList.add('hidden');
        }

        function kirimApproval() {
            const status = document.getElementById('modalKeputusan').value;
            const catatan = document.getElementById('modalCatatan').value;
            const parafData = canvas.toDataURL('image/png');

            if (status === 'Perlu Revisi' && !catatan.trim()) {
                Swal.fire('Perhatian', 'Catatan evaluasi wajib diisi jika meminta revisi!', 'warning');
                return;
            }

            document.getElementById('postKeputusan').value = status;
            document.getElementById('postCatatanMentor').value = catatan;
            document.getElementById('postParafBase64').value = parafData;
            document.getElementById('formApprovalSubmit').submit();
        }

        // SCRIPT TOGGLE HAMBURGER MENU & OVERLAY MOBILE
        document.addEventListener('DOMContentLoaded', () => {
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            // Mengambil elemen sidebar (baik dari ID #sidebar maupun tag <aside>)
            const sidebar = document.getElementById('sidebar') || document.querySelector('aside');
            const closeSidebarBtn = document.getElementById('closeSidebarBtn');

            const toggleSidebar = () => {
                if (sidebar) {
                    sidebar.classList.toggle('-translate-x-full');
                    sidebar.classList.toggle('translate-x-0');
                }
                if (sidebarOverlay) {
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