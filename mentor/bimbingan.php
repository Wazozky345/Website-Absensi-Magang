<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/koneksi.php';

// Proteksi Hak Akses Role Mentor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'mentor') {
    header("Location: ../login-mentor.php");
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mentor_id   = $_SESSION['user_id'] ?? 1;
$nama_mentor = $_SESSION['nama_user'] ?? 'Dr. Alvin Nurfaiz, M.T.';

// 1. LOGIKA BULAN AKTIF & KALENDER (JULI - OKTOBER 2026)
$bulan_aktif = isset($_GET['bulan']) ? sprintf('%02d', intval($_GET['bulan'])) : '08'; // Default Agustus 2026
$tahun_aktif = '2026';

$nama_bulan_map = [
    '07' => 'Juli',
    '08' => 'Agustus',
    '09' => 'September',
    '10' => 'Oktober'
];

if (!isset($nama_bulan_map[$bulan_aktif])) {
    $bulan_aktif = '08';
}

// Menhitung total hari & slot kosong kalender (Senin - Minggu)
$first_day_timestamp = strtotime("$tahun_aktif-$bulan_aktif-01");
$total_hari          = date('t', $first_day_timestamp);
$day_of_week         = date('N', $first_day_timestamp); // 1 (Senin) - 7 (Minggu)
$slot_kosong         = $day_of_week - 1;

// 2. AMBIL DAFTAR MAHASISWA BIMBINGAN
$mahasiswa_list = [];
$q_mhs = $conn->query("SELECT id, nama_user, nim FROM users ORDER BY nama_user ASC");
if ($q_mhs && $q_mhs->num_rows > 0) {
    while ($m = $q_mhs->fetch_assoc()) {
        $mahasiswa_list[] = $m;
    }
} else {
    $mahasiswa_list = [
        ['id' => 1, 'nama_user' => 'Alvin Nurfaiz', 'nim' => '232101111'],
        ['id' => 2, 'nama_user' => 'M. Yusman Bayuga', 'nim' => '232101145']
    ];
}

// 3. AMBIL DATA BIMBINGAN DARI DATABASE UNTUK BULAN AKTIF
$bimbingan_db = [];
$q_bimb = $conn->query("
    SELECT b.*, u.nama_user, u.nim 
    FROM bimbingan b 
    LEFT JOIN users u ON b.user_id = u.id 
    WHERE b.mentor_id = '$mentor_id' 
      AND MONTH(b.tanggal_waktu) = '$bulan_aktif' 
      AND YEAR(b.tanggal_waktu) = '$tahun_aktif'
    ORDER BY b.tanggal_waktu ASC
");

if ($q_bimb && $q_bimb->num_rows > 0) {
    while ($b = $q_bimb->fetch_assoc()) {
        $tgl_d = intval(date('j', strtotime($b['tanggal_waktu'])));
        $bimbingan_db[$tgl_d][] = $b;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Bimbingan & Catatan Revisi - UTB Tracker</title>
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
                    <h2 class="text-base font-bold text-gray-800">Kalender Bimbingan + Catatan Revisi</h2>
                    <p class="text-xs text-gray-400">Atur jadwal bimbingan dan catat perbaikan proyek mahasiswa</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="text-right hidden sm:block">
                    <p class="text-xs font-bold text-gray-800"><?php echo htmlspecialchars($nama_mentor); ?></p>
                    <p class="text-[10px] text-gray-400">Pembimbing Lapangan</p>
                </div>
                <div class="w-9 h-9 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shadow-sm">
                    DM
                </div>
            </div>
        </header>

        <div class="p-4 md:p-8 space-y-6">

            <!-- TATA LETAK 2 KOLOM WIREFRAME LAYAR 09 -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

                <!-- KOLOM UTAMA (KIRI - 2 GRID SPAN) -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- CARD 1: KALENDER BIMBINGAN -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-4">
                            <div class="flex items-center gap-3">
                                <h3 class="text-base font-bold text-gray-800">
                                    Kalender Bimbingan <?php echo $nama_bulan_map[$bulan_aktif] . ' ' . $tahun_aktif; ?>
                                </h3>
                                <!-- DROPDOWN FILTER BULAN (JULI - OKTOBER 2026) -->
                                <select onchange="location = this.value;" class="bg-gray-50 border border-gray-200 text-xs font-bold rounded-xl px-3 py-1.5 text-gray-700 focus:outline-none cursor-pointer">
                                    <option value="bimbingan.php?bulan=07" <?php echo $bulan_aktif === '07' ? 'selected' : ''; ?>>Juli 2026</option>
                                    <option value="bimbingan.php?bulan=08" <?php echo $bulan_aktif === '08' ? 'selected' : ''; ?>>Agustus 2026</option>
                                    <option value="bimbingan.php?bulan=09" <?php echo $bulan_aktif === '09' ? 'selected' : ''; ?>>September 2026</option>
                                    <option value="bimbingan.php?bulan=10" <?php echo $bulan_aktif === '10' ? 'selected' : ''; ?>>Oktober 2026</option>
                                </select>
                            </div>

                            <p class="text-[11px] text-gray-400 bg-gray-50 px-3 py-1 rounded-xl">
                                💡 <span class="text-blue-600 font-semibold">Sel biru</span>: Bimbingan | <span class="text-rose-500 font-semibold">Sel merah muda</span>: Revisi
                            </p>
                        </div>

                        <!-- GRID 7 HARI -->
                        <div class="grid grid-cols-7 gap-1.5 text-center text-xs font-bold text-gray-400 mb-2">
                            <div class="py-1">Sen</div>
                            <div class="py-1">Sel</div>
                            <div class="py-1">Rab</div>
                            <div class="py-1">Kam</div>
                            <div class="py-1">Jum</div>
                            <div class="py-1 text-rose-400">Sab</div>
                            <div class="py-1 text-rose-400">Min</div>
                        </div>

                        <!-- GRID KALENDER INTERAKTIF -->
                        <div class="grid grid-cols-7 gap-1.5">
                            <?php for ($i = 0; $i < $slot_kosong; $i++): ?>
                                <div class="min-h-[64px] bg-gray-50/40 rounded-xl border border-dashed border-gray-100"></div>
                            <?php endfor; ?>

                            <?php for ($d = 1; $d <= $total_hari; $d++): 
                                $tgl_full = sprintf('%s-%s-%02dT10:00', $tahun_aktif, $bulan_aktif, $d);
                                
                                if (isset($bimbingan_db[$d]) && !empty($bimbingan_db[$d])):
                                    foreach ($bimbingan_db[$d] as $bm):
                                        $is_revisi   = ($bm['status'] === 'Revisi');
                                        $bg_card     = $is_revisi ? 'bg-rose-50/80 border-rose-400 text-rose-600' : 'bg-blue-50/80 border-blue-400 text-blue-600';
                                        $badge_color = $is_revisi ? 'bg-rose-500' : 'bg-blue-600';

                                        // Inisial Nama Mahasiswa
                                        $words = explode(' ', $bm['nama_user']);
                                        $inisial = count($words) >= 2 ? strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1)) : strtoupper(substr($bm['nama_user'], 0, 2));

                                        $js_id      = $bm['id'];
                                        $js_user_id = $bm['user_id'];
                                        $js_nama    = addslashes($bm['nama_user']);
                                        $js_nim     = addslashes($bm['nim']);
                                        $js_waktu   = date('Y-m-d\TH:i', strtotime($bm['tanggal_waktu']));
                                        $js_topik   = addslashes($bm['topik']);
                                        $js_metode  = addslashes($bm['metode']);
                                        $js_status  = addslashes($bm['status']);
                                        $js_catatan = str_replace(["\r", "\n"], ["", " "], addslashes($bm['catatan_revisi'] ?? ''));
                            ?>
                                        <div class="min-h-[64px] border-2 rounded-xl p-1 text-[10px] cursor-pointer shadow-sm hover:scale-105 transition <?php echo $bg_card; ?>"
                                             onclick="bukaDetailForm('edit', '<?php echo $js_id; ?>', '<?php echo $js_user_id; ?>', '<?php echo $js_nama; ?>', '<?php echo $js_nim; ?>', '<?php echo $js_waktu; ?>', '<?php echo $js_topik; ?>', '<?php echo $js_metode; ?>', '<?php echo $js_status; ?>', '<?php echo $js_catatan; ?>')">
                                            <span class="font-bold block mb-0.5"><?php echo $d; ?></span>
                                            <span class="<?php echo $badge_color; ?> text-white font-bold px-1.5 py-0.5 rounded block truncate">
                                                <?php echo ($is_revisi ? 'Revisi ' : 'Bimbingan ') . $inisial; ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <!-- SEL KOSONG: KLIK UNTUK TAMBAH BIMBINGAN BARU -->
                                    <div class="min-h-[64px] bg-white border border-gray-100 rounded-xl p-1 text-[11px] hover:border-blue-300 transition cursor-pointer flex flex-col justify-between"
                                         onclick="bukaDetailForm('create', 0, 1, '', '', '<?php echo $tgl_full; ?>', '', 'Tatap Muka', 'Terjadwal', '')">
                                        <span class="font-bold text-gray-400"><?php echo $d; ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- CARD 2: DETAIL BIMBINGAN TERPILIH (FORM CRUD) -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100" id="cardFormDetail">
                        <div class="mb-5 pb-3 border-b border-gray-100 flex justify-between items-center">
                            <div>
                                <h3 class="text-base font-bold text-gray-800" id="formHeaderTitle">Tambah Bimbingan Baru</h3>
                                <p class="text-[11px] text-gray-400" id="formHeaderSubtitle">Kelola instruksi revisi dan jadwal temu mahasiswa bimbingan</p>
                            </div>
                            <span class="text-xs bg-blue-50 text-blue-600 font-bold px-3 py-1 rounded-full" id="labelMahasiswaNIM">NIM: -</span>
                        </div>

                        <!-- SUBMIT FORM KE PROSES MENTOR BIMBINGAN -->
                        <form method="POST" action="../proses/proses_mentor_bimbingan.php" id="formBimbingan" class="space-y-4">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="action" id="inputAction" value="save">
                            <input type="hidden" name="bimbingan_id" id="inputBimbinganId" value="0">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- PILIH MAHASISWA -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Mahasiswa Bimbingan <span class="text-rose-500">*</span></label>
                                    <select name="user_id" id="selectUserId" onchange="updateSelectedStudentInfo()" required class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-3.5 py-2.5 text-xs focus:outline-none focus:border-blue-500 font-medium text-gray-800">
                                        <?php foreach ($mahasiswa_list as $m): ?>
                                            <option value="<?php echo $m['id']; ?>" data-nim="<?php echo htmlspecialchars($m['nim']); ?>" data-nama="<?php echo htmlspecialchars($m['nama_user']); ?>">
                                                <?php echo htmlspecialchars($m['nama_user']); ?> (<?php echo htmlspecialchars($m['nim']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- TANGGAL & WAKTU -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Tanggal & Waktu Sesi <span class="text-rose-500">*</span></label>
                                    <input type="datetime-local" name="waktu_sesi" id="inputWaktuSesi" value="<?php echo date('Y-m-d\TH:i'); ?>" required class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-3.5 py-2.5 text-xs focus:outline-none focus:border-blue-500 font-medium text-gray-800">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <!-- TOPIK / AGENDA -->
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Topik / Agenda Bimbingan <span class="text-rose-500">*</span></label>
                                    <input type="text" name="topik" id="inputTopik" required placeholder="Contoh: Review BAB II - Kajian Pustaka" class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-3.5 py-2.5 text-xs focus:outline-none focus:border-blue-500 font-medium text-gray-800">
                                </div>

                                <!-- STATUS SESI -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Status Bimbingan</label>
                                    <select name="status_tipe" id="selectStatusTipe" class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-3.5 py-2.5 text-xs focus:outline-none focus:border-blue-500 font-medium text-gray-800">
                                        <option value="Terjadwal">Terjadwal</option>
                                        <option value="Revisi">Revisi</option>
                                        <option value="Selesai">Selesai</option>
                                    </select>
                                </div>
                            </div>

                            <!-- METODE -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Metode Pelaksanaan</label>
                                <div class="flex items-center gap-3">
                                    <label class="flex-1 text-center bg-blue-50 text-blue-600 border border-blue-200 py-2.5 rounded-2xl text-xs font-bold cursor-pointer transition" id="lblMetodeTatapMuka">
                                        <input type="radio" name="metode" id="radTatapMuka" value="Tatap Muka" checked onclick="updateMetodeStyle('Tatap Muka')" class="hidden"> Tatap Muka (Luring)
                                    </label>
                                    <label class="flex-1 text-center bg-gray-50 text-gray-600 border border-gray-200 py-2.5 rounded-2xl text-xs font-medium cursor-pointer hover:bg-gray-100 transition" id="lblMetodeOnline">
                                        <input type="radio" name="metode" id="radOnline" value="Online" onclick="updateMetodeStyle('Online')" class="hidden"> Online (Zoom/Meet)
                                    </label>
                                </div>
                            </div>

                            <!-- CATATAN REVISI -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Catatan Revisi & Instruksi <span class="text-rose-500">*</span></label>
                                <textarea name="catatan" id="inputCatatan" rows="4" required class="w-full bg-gray-50 border border-gray-200 rounded-2xl p-4 text-xs focus:outline-none focus:border-blue-500 transition text-gray-800 resize-none placeholder-gray-400 leading-relaxed" placeholder="Tuliskan rincian perbaikan bab, poin evaluasi, atau batas waktu revisi..."></textarea>
                            </div>

                            <!-- TOMBOL AKSI CRUD -->
                            <div class="flex items-center justify-between pt-2">
                                <button type="button" id="btnHapusBimbingan" onclick="konfirmasiHapus()" class="hidden bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold px-5 py-2.5 rounded-2xl text-xs transition">
                                    Hapus Jadwal
                                </button>
                                
                                <div class="flex items-center gap-2 ml-auto">
                                    <button type="button" onclick="resetFormToCreate()" class="px-5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold text-gray-500 hover:bg-gray-50 transition">
                                        Batal / Tambah Baru
                                    </button>
                                    <button type="submit" id="btnSubmitForm" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2.5 rounded-2xl text-xs shadow-lg shadow-blue-200 transition">
                                        Simpan Catatan
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>

                <!-- KOLOM KANAN (RIWAYAT REVISI MAHASISWA TERPILIH) -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 space-y-5">
                    <div class="pb-3 border-b border-gray-100 flex justify-between items-center">
                        <div>
                            <h3 class="text-base font-bold text-gray-800">Riwayat Revisi</h3>
                            <p class="text-[11px] text-gray-400" id="labelTimelineNama">Alvin Nurfaiz</p>
                        </div>
                        <span class="text-[10px] bg-blue-50 text-blue-600 px-2 py-1 rounded-full font-bold">Timeline</span>
                    </div>

                    <div class="space-y-4" id="containerTimeline">
                        <div class="relative pl-4 border-l-2 border-blue-500 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-gray-800">28 Jun 2026</span>
                                <span class="text-[10px] bg-blue-50 text-blue-600 px-2 py-0.5 rounded-md font-bold">BAB I</span>
                            </div>
                            <p class="text-xs text-gray-600 leading-relaxed">
                                Perbaiki rumusan masalah, tambahkan batasan penelitian sesuai template UAS.
                            </p>
                        </div>

                        <div class="relative pl-4 border-l-2 border-gray-200 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-gray-800">15 Jun 2026</span>
                                <span class="text-[10px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded-md font-medium">Proposal Awal</span>
                            </div>
                            <p class="text-xs text-gray-500 leading-relaxed">
                                Lengkapi identitas dosen pembimbing (NIP/NIDN) pada halaman sampul.
                            </p>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </main>

    <!-- SCRIPT INTERAKTIF CONTROLLER -->
    <script>
        function updateMetodeStyle(metode) {
            const lblTatapMuka = document.getElementById('lblMetodeTatapMuka');
            const lblOnline    = document.getElementById('lblMetodeOnline');

            if (metode === 'Tatap Muka') {
                lblTatapMuka.className = "flex-1 text-center bg-blue-50 text-blue-600 border border-blue-200 py-2.5 rounded-2xl text-xs font-bold cursor-pointer transition";
                lblOnline.className    = "flex-1 text-center bg-gray-50 text-gray-600 border border-gray-200 py-2.5 rounded-2xl text-xs font-medium cursor-pointer hover:bg-gray-100 transition";
                document.getElementById('radTatapMuka').checked = true;
            } else {
                lblOnline.className    = "flex-1 text-center bg-blue-50 text-blue-600 border border-blue-200 py-2.5 rounded-2xl text-xs font-bold cursor-pointer transition";
                lblTatapMuka.className = "flex-1 text-center bg-gray-50 text-gray-600 border border-gray-200 py-2.5 rounded-2xl text-xs font-medium cursor-pointer hover:bg-gray-100 transition";
                document.getElementById('radOnline').checked = true;
            }
        }

        function updateSelectedStudentInfo() {
            const select = document.getElementById('selectUserId');
            if (!select || select.options.length === 0) return;

            const selectedOption = select.options[select.selectedIndex];
            const nama = selectedOption.getAttribute('data-nama') || selectedOption.text.split(' (')[0];
            const nim  = selectedOption.getAttribute('data-nim')  || '-';

            document.getElementById('labelMahasiswaNama').innerText = nama;
            document.getElementById('labelMahasiswaNIM').innerText  = "NIM: " + nim;
            document.getElementById('labelTimelineNama').innerText  = nama;
        }

        function bukaDetailForm(mode, id, userId, nama, nim, waktu, topik, metode, status, catatan) {
            document.getElementById('inputBimbinganId').value = id;
            document.getElementById('inputAction').value      = 'save';

            const btnHapus   = document.getElementById('btnHapusBimbingan');
            const btnSubmit  = document.getElementById('btnSubmitForm');
            const formHeader = document.getElementById('formHeaderTitle');

            if (mode === 'edit' && id > 0) {
                formHeader.innerHTML = "Edit Bimbingan - <span class='text-blue-600'>" + nama + "</span>";
                btnHapus.classList.remove('hidden');
                btnSubmit.innerText = "Simpan Perubahan";
            } else {
                formHeader.innerHTML = "Tambah Bimbingan Baru";
                btnHapus.classList.add('hidden');
                btnSubmit.innerText = "Simpan Catatan";
            }

            if (userId) {
                document.getElementById('selectUserId').value = userId;
                updateSelectedStudentInfo();
            }

            if (waktu) document.getElementById('inputWaktuSesi').value = waktu;
            document.getElementById('inputTopik').value              = topik || '';
            document.getElementById('selectStatusTipe').value        = status || 'Terjadwal';
            document.getElementById('inputCatatan').value            = catatan || '';

            updateMetodeStyle(metode || 'Tatap Muka');

            document.getElementById('cardFormDetail').scrollIntoView({ behavior: 'smooth' });
        }

        function resetFormToCreate() {
            const nowIso = new Date().toISOString().slice(0, 16);
            bukaDetailForm('create', 0, 1, '', '', nowIso, '', 'Tatap Muka', 'Terjadwal', '');
        }

        function konfirmasiHapus() {
            const bimbinganId = document.getElementById('inputBimbinganId').value;
            if (!bimbinganId || bimbinganId == 0) return;

            Swal.fire({
                title: 'Hapus Jadwal ini?',
                text: 'Data bimbingan yang dihapus tidak dapat dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('inputAction').value = 'delete';
                    document.getElementById('formBimbingan').submit();
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateSelectedStudentInfo();
        });
    </script>

    <?php 
    if (file_exists(__DIR__ . '/../components/alert.php')) {
        include __DIR__ . '/../components/alert.php';
    } 
    ?>

</body>

</html>