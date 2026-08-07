<?php
// Murni memanggil otak proses_mentor_tugas.php
require_once __DIR__ . '/../proses/proses_mentor_tugas.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kirim Tugas & File - UTB Tracker</title>
    <link rel="stylesheet" href="../assets/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
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
                    <h2 class="text-base font-bold text-gray-800">Distribusi Tugas & File Lampiran</h2>
                    <p class="text-xs text-gray-400">Kirim instruksi penugasan dan kelola berkas mahasiswa bimbingan</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="text-right hidden sm:block">
                    <p class="text-xs font-bold text-gray-800"><?php echo htmlspecialchars($nama_mentor); ?></p>
                    <p class="text-[10px] text-gray-400">Pembimbing Lapangan</p>
                </div>
                
                <div class="w-9 h-9 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-xs ring-2 ring-blue-100">
                    <?php 
                    $words = explode(' ', $nama_mentor);
                    $initials = '';
                    foreach (array_slice($words, 0, 2) as $w) {
                        $initials .= strtoupper(substr($w, 0, 1));
                    }
                    echo htmlspecialchars($initials ?: 'M');
                    ?>
                </div>
            </div>
        </header>

        <div class="p-4 md:p-8 space-y-6">

            <!-- FORM PENUGASAN BARU -->
            <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-gray-100">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
                    <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center font-bold">
                        ➕
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-800">Form Penugasan Baru</h3>
                        <p class="text-xs text-gray-400">Isi detail instruksi dan unggah lampiran file pendukung</p>
                    </div>
                </div>

                <form method="POST" action="../proses/proses_mentor_tugas.php" enctype="multipart/form-data" id="formKirimTugas" class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- JUDUL TUGAS -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-2">Judul Tugas <span class="text-rose-500">*</span></label>
                            <input type="text" name="judul_tugas" required placeholder="Contoh: Analisis kebutuhan sistem minggu 7" class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-4 py-3 text-xs focus:outline-none focus:border-blue-500 transition text-gray-800 placeholder-gray-400">
                        </div>

                        <!-- DITUJUKAN UNTUK (OPSIONAL: SEMUA / SPESIFIK MAHASISWA) -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-2">Ditujukan Untuk <span class="text-rose-500">*</span></label>
                            <select name="target_mahasiswa" required class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-4 py-3 text-xs focus:outline-none focus:border-blue-500 font-semibold text-gray-800">
                                <option value="all">👥 Semua Mahasiswa Bimbingan</option>
                                <?php if (!empty($mahasiswa_list)): ?>
                                    <optgroup label="Pilih Spesifik Mahasiswa">
                                        <?php foreach ($mahasiswa_list as $mhs): ?>
                                            <option value="<?php echo htmlspecialchars($mhs['nim'] ?: $mhs['id']); ?>">
                                                👤 <?php echo htmlspecialchars($mhs['nama_user']); ?> <?php echo !empty($mhs['nim']) ? '(' . htmlspecialchars($mhs['nim']) . ')' : ''; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <!-- DESKRIPSI INSTRUKSI -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-2">Deskripsi / Instruksi Tugas</label>
                        <textarea name="deskripsi" rows="3" placeholder="Tuliskan rincian instruksi pengerjaan tugas di sini..." class="w-full bg-gray-50 border border-gray-200 rounded-2xl p-4 text-xs focus:outline-none focus:border-blue-500 transition text-gray-800 resize-none placeholder-gray-400 leading-relaxed"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-end">
                        <!-- UPLOAD FILE LAMPIRAN -->
                        <div class="md:col-span-8">
                            <label class="block text-xs font-bold text-gray-700 mb-2">Lampiran File Pendukung <span class="text-gray-400 font-normal">(PDF/DOCX/XLSX, maks 10MB)</span></label>
                            
                            <div id="dropzone" class="border-2 border-dashed border-gray-200 hover:border-blue-400 bg-gray-50/60 hover:bg-blue-50/30 rounded-2xl p-4 text-center cursor-pointer transition relative flex flex-col items-center justify-center min-h-[96px]">
                                <input type="file" name="file_lampiran" id="fileInput" accept=".pdf,.docx,.xlsx,.pptx" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                                
                                <div id="dropzoneContent" class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0 font-bold">
                                        📁
                                    </div>
                                    <div class="text-left">
                                        <p class="text-xs font-bold text-gray-700" id="fileNameText">Tarik file ke sini atau <span class="text-blue-600 underline">klik untuk unggah</span></p>
                                        <p class="text-[10px] text-gray-400">Format yang didukung: .pdf, .docx, .xlsx, .pptx</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TENGGAT PENGUMPULAN -->
                        <div class="md:col-span-4">
                            <label class="block text-xs font-bold text-gray-700 mb-2">Tenggat Pengumpulan <span class="text-rose-500">*</span></label>
                            <input type="datetime-local" name="tenggat" required value="<?php echo date('Y-m-d\T23:59', strtotime('+3 days')); ?>" class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-4 py-3 text-xs focus:outline-none focus:border-blue-500 font-medium text-gray-800">
                        </div>
                    </div>

                    <!-- TOMBOL SUBMIT -->
                    <div class="pt-2 flex justify-end">
                        <button type="submit" name="kirim_tugas" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-2xl text-xs shadow-lg shadow-blue-200 transition flex items-center gap-2">
                            Kirim Tugas ke Mahasiswa
                        </button>
                    </div>
                </form>
            </div>

            <!-- DAFTAR TUGAS TERKIRIM DENGAN FITUR TARIK FILE & DELETE -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-base font-bold text-gray-800">Daftar Penugasan Terdistribusi</h3>
                    <span class="bg-blue-50 text-blue-600 font-bold text-xs px-3 py-1 rounded-full">Total: <?php echo count($tugas_terdistribusi); ?></span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="text-gray-400 font-semibold border-b border-gray-100 pb-3">
                                <th class="py-3">Judul Tugas</th>
                                <th class="py-3">Target Mahasiswa</th>
                                <th class="py-3">Tenggat Waktu</th>
                                <th class="py-3">Lampiran Soal</th>
                                <th class="py-3 text-center">Aksi Kelola</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-gray-700">
                            <?php if (!empty($tugas_terdistribusi)): ?>
                                <?php foreach ($tugas_terdistribusi as $tg): 
                                    $target_nama = $tg['nama_user'] ? htmlspecialchars($tg['nama_user']) : 'Semua Mahasiswa';
                                    $tenggat_tampil = date('d M Y, H:i', strtotime($tg['tenggat'])) . ' WIB';
                                    
                                    $has_file = !empty($tg['file_lampiran']);
                                    $file_link = $has_file ? '../uploads/tugas_mentor/' . htmlspecialchars($tg['file_lampiran']) : '#';
                                    $file_tampil = $has_file ? htmlspecialchars($tg['file_lampiran']) : '';
                                ?>
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="py-3.5 font-bold text-gray-800"><?php echo htmlspecialchars($tg['judul_tugas']); ?></td>
                                        <td class="py-3.5 text-gray-600">
                                            <span class="bg-gray-100 text-gray-600 px-2.5 py-1 rounded-md font-medium text-[10px]">
                                                <?php echo $target_nama; ?>
                                            </span>
                                        </td>
                                        <td class="py-3.5 text-rose-500 font-semibold"><?php echo $tenggat_tampil; ?></td>
                                        
                                        <!-- KOLOM LAMPIRAN + KELOLA TARIK BERKAS -->
                                        <td class="py-3.5">
                                            <?php if ($has_file): ?>
                                                <div class="flex flex-col gap-1">
                                                    <span class="text-gray-700 font-medium truncate max-w-[150px] block" title="<?php echo $file_tampil; ?>">
                                                        📄 <?php echo $file_tampil; ?>
                                                    </span>
                                                    <div class="flex items-center gap-1.5">
                                                        <a href="<?php echo $file_link; ?>" target="_blank" class="text-[10px] bg-emerald-50 text-emerald-600 hover:bg-emerald-100 px-2 py-0.5 rounded-md font-bold transition">
                                                            Lihat
                                                        </a>
                                                        <button type="button" onclick="openModalUploadLampiran(<?php echo $tg['id']; ?>, '<?php echo htmlspecialchars(addslashes($tg['judul_tugas'])); ?>')" class="text-[10px] bg-blue-50 text-blue-600 hover:bg-blue-100 px-2 py-0.5 rounded-md font-bold transition">
                                                            Ganti
                                                        </button>

                                                        <form method="POST" action="../proses/proses_mentor_tugas.php" id="formTarikFile_<?php echo $tg['id']; ?>" class="inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                            <input type="hidden" name="tarik_file_id" value="<?php echo $tg['id']; ?>">
                                                            <button type="button" onclick="konfirmasiTarikFile(<?php echo $tg['id']; ?>, '<?php echo htmlspecialchars(addslashes($tg['file_lampiran'])); ?>')" class="text-[10px] bg-amber-50 text-amber-600 hover:bg-amber-100 px-2 py-0.5 rounded-md font-bold transition cursor-pointer" title="Tarik berkas agar tidak dapat diunduh mahasiswa">
                                                                🚫 Tarik File
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-gray-300 text-[11px] font-medium">Tanpa Lampiran</span>
                                                    <button type="button" onclick="openModalUploadLampiran(<?php echo $tg['id']; ?>, '<?php echo htmlspecialchars(addslashes($tg['judul_tugas'])); ?>')" class="text-[10px] bg-emerald-50 text-emerald-600 hover:bg-emerald-100 px-2 py-0.5 rounded-lg font-bold transition">
                                                        + Upload
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- AKSI PANTAU & DELETE SELURUH TUGAS -->
                                        <td class="py-3.5 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="approval.php" class="bg-blue-50 text-blue-600 hover:bg-blue-100 font-bold px-3 py-1.5 rounded-xl transition inline-block text-xs">
                                                    Review Status
                                                </a>

                                                <form method="POST" action="../proses/proses_mentor_tugas.php" id="formHapusTugas_<?php echo $tg['id']; ?>" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <input type="hidden" name="hapus_tugas_id" value="<?php echo $tg['id']; ?>">
                                                    <button type="button" onclick="konfirmasiHapus(<?php echo $tg['id']; ?>, '<?php echo htmlspecialchars(addslashes($tg['judul_tugas'])); ?>')" class="bg-rose-50 text-rose-600 hover:bg-rose-100 font-bold px-3 py-1.5 rounded-xl transition inline-block text-xs cursor-pointer">
                                                        Hapus Tugas
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-gray-400 font-medium">Belum ada tugas yang didistribusikan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <!-- MODAL POPUP UPLOAD / GANTI FILE LAMPIRAN -->
    <div id="modalUploadContainer" class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 transition-all">
        <div class="bg-white rounded-3xl shadow-2xl border border-gray-100 w-full max-w-md overflow-hidden transform transition-all scale-95 opacity-0 duration-300" id="modalUploadContent">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <div>
                    <h3 class="text-base font-bold text-gray-800">Unggah File Lampiran</h3>
                    <p id="targetJudulTugas" class="text-xs text-blue-600 font-semibold truncate max-w-[280px]">Judul Tugas</p>
                </div>
                <button type="button" onclick="closeModalUploadLampiran()" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form method="POST" action="../proses/proses_mentor_tugas.php" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="update_lampiran_id" id="modalTugasId">

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Pilih Berkas Pendukung <span class="text-rose-500">*</span></label>
                    <input type="file" name="file_lampiran" required accept=".pdf,.docx,.xlsx,.pptx" class="w-full bg-gray-50 border border-gray-200 rounded-2xl p-3 text-xs text-gray-700 focus:outline-none focus:border-blue-500">
                    <p class="text-[10px] text-gray-400 mt-1">Maksimal ukuran berkas 10MB (.pdf, .docx, .xlsx, .pptx)</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                    <button type="button" onclick="closeModalUploadLampiran()" class="px-4 py-2.5 rounded-2xl text-xs font-bold text-gray-500 hover:bg-gray-100 transition">
                        Batal
                    </button>
                    <button type="submit" name="upload_lampiran_tugas" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-5 py-2.5 rounded-2xl shadow-lg shadow-blue-200 transition text-xs">
                        Simpan Berkas
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php 
    if (file_exists(__DIR__ . '/../components/alert.php')) {
        include __DIR__ . '/../components/alert.php';
    } 
    ?>

    <script>
        const fileInput = document.getElementById('fileInput');
        const fileNameText = document.getElementById('fileNameText');

        if (fileInput && fileNameText) {
            fileInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    const file = e.target.files[0];
                    const fileSizeMB = file.size / (1024 * 1024);

                    if (fileSizeMB > 10) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Ukuran Berkas Terlalu Besar',
                            text: 'Maksimal batas ukuran berkas lampiran adalah 10MB.',
                            confirmButtonColor: '#2563eb'
                        });
                        fileInput.value = '';
                        fileNameText.innerHTML = 'Tarik file ke sini atau <span class="text-blue-600 underline">klik untuk unggah</span>';
                        return;
                    }

                    fileNameText.innerHTML = 'File terpilih: <span class="text-blue-600 font-bold">' + file.name + '</span> (' + fileSizeMB.toFixed(2) + ' MB)';
                }
            });
        }

        function konfirmasiTarikFile(id, namaFile) {
            Swal.fire({
                title: 'Tarik Berkas Lampiran?',
                text: 'Berkas "' + namaFile + '" akan ditarik dari server. Mahasiswa tidak akan dapat mengunduh berkas ini lagi.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d97706',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Tarik Berkas',
                cancelButtonText: 'Batal',
                customClass: { popup: 'rounded-3xl' }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('formTarikFile_' + id).submit();
                }
            });
        }

        function konfirmasiHapus(id, judul) {
            Swal.fire({
                title: 'Hapus Penugasan Total?',
                text: 'Tugas "' + judul + '" beserta seluruh instruksi akan dibatalkan & dihapus dari antrean mahasiswa.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus Total',
                cancelButtonText: 'Batal',
                customClass: { popup: 'rounded-3xl' }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('formHapusTugas_' + id).submit();
                }
            });
        }

        function openModalUploadLampiran(id, judul) {
            document.getElementById('modalTugasId').value = id;
            document.getElementById('targetJudulTugas').innerText = judul;

            const modal = document.getElementById('modalUploadContainer');
            const content = document.getElementById('modalUploadContent');
            if (!modal || !content) return;

            modal.classList.remove('hidden');
            setTimeout(() => {
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeModalUploadLampiran() {
            const modal = document.getElementById('modalUploadContainer');
            const content = document.getElementById('modalUploadContent');
            if (!modal || !content) return;

            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');

            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

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

        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const closeSidebarBtn = document.getElementById('closeSidebarBtn');
            const sidebarOverlay = document.getElementById('sidebarOverlay');

            // Logika Toggle yang lebih fleksibel (Tahan banting meskipun tidak ada overlay)
            const toggleSidebar = () => {
                if (sidebar) sidebar.classList.toggle('-translate-x-full');
                if (sidebarOverlay) sidebarOverlay.classList.toggle('hidden');
            };

            if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', toggleSidebar);
            if (closeSidebarBtn) closeSidebarBtn.addEventListener('click', toggleSidebar);
            if (sidebarOverlay) sidebarOverlay.addEventListener('click', toggleSidebar);
        });
    </script>
</body>
</html>