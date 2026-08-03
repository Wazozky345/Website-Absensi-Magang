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
                    <h2 class="text-base font-bold text-gray-800">Distribusi Tugas & File Lampiran</h2>
                    <p class="text-xs text-gray-400">Kirim instruksi penugasan dan kelola berkas mahasiswa bimbingan</p>
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

                        <!-- DITUJUKAN UNTUK -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-2">Ditujukan Untuk <span class="text-rose-500">*</span></label>
                            <select name="target_mahasiswa" required class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-4 py-3 text-xs focus:outline-none focus:border-blue-500 font-medium text-gray-800">
                                <option value="all">Semua Mahasiswa Bimbingan</option>
                                <option value="232101111">Alvin Nurfaiz (232101111)</option>
                                <option value="232101145">M. Yusman Bayuga (232101145)</option>
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
                            <input type="datetime-local" name="tenggat" required value="2026-08-05T23:59" class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-4 py-3 text-xs focus:outline-none focus:border-blue-500 font-medium text-gray-800">
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

            <!-- DAFTAR TUGAS TERKIRIM -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-base font-bold text-gray-800">Daftar Penugasan Terdistribusi</h3>
                    <span class="bg-blue-50 text-blue-600 font-bold text-xs px-3 py-1 rounded-full">Aktif</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="text-gray-400 font-semibold border-b border-gray-100 pb-3">
                                <th class="py-3">Judul Tugas</th>
                                <th class="py-3">Target</th>
                                <th class="py-3">Tenggat</th>
                                <th class="py-3">File Balasan</th>
                                <th class="py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-gray-700">
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="py-3 font-bold text-gray-800">Analisis Kebutuhan Sistem W7</td>
                                <td class="py-3">Semua Mahasiswa</td>
                                <td class="py-3 text-gray-500">25 Jul 23:59 WIB</td>
                                <td class="py-3 text-blue-600 font-semibold">📄 tugas_rangga_w7.pdf</td>
                                <td class="py-3 text-center">
                                    <a href="approval.php" class="bg-blue-50 text-blue-600 hover:bg-blue-100 font-bold px-3 py-1.5 rounded-xl transition inline-block">Review</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <?php include '../components/alert.php'; ?>

    <script>
        const fileInput = document.getElementById('fileInput');
        const fileNameText = document.getElementById('fileNameText');

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
    </script>
</body>

</html>