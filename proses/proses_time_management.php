<?php
// 1. Inisialisasi Sesi, Proteksi Hak Akses & Jalur Berkas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (file_exists(__DIR__ . '/../config/sesi.php')) {
    require_once __DIR__ . '/../config/sesi.php';
} elseif (file_exists(__DIR__ . '/../../config/sesi.php')) {
    require_once __DIR__ . '/../../config/sesi.php';
} else {
    require_once __DIR__ . '/../config/koneksi.php';
}

date_default_timezone_set('Asia/Jakarta');

// === FIX BUG: PEMBUAT TOKEN CSRF ===
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Deklarasikan variabel $user_id dari sesi secara eksplisit
$user_id = $_SESSION['user_id'] ?? 0;

if ($user_id <= 0) {
    header("Location: ../login.php");
    exit;
}

// === OTOMATISASI SCHEMA TABLE SAFEGUARD ===
$conn->query("CREATE TABLE IF NOT EXISTS `agenda` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `judul` VARCHAR(255) NOT NULL,
    `kategori` VARCHAR(100) DEFAULT 'Umum',
    `tanggal` DATE NOT NULL,
    `waktu` TIME NOT NULL,
    `pengingat_offset` INT DEFAULT 1,
    `deskripsi` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$conn->query("CREATE TABLE IF NOT EXISTS `milestones` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `bulan_key` VARCHAR(10) NOT NULL,
    `judul` VARCHAR(255) NOT NULL,
    `status` VARCHAR(50) DEFAULT 'Pending',
    `operasional` TEXT NULL,
    `it` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");


// 2. PROSES CRUD AGENDA MANDIRI KALENDER & TARGET MILESTONE (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // SATPAM CSRF: Periksa apakah token dikirim dan cocok dengan yang ada di server
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Akses Ditolak',
            'message' => 'CSRF Token Invalid! Terdeteksi aktivitas mencurigakan.'
        ];
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    $action    = $_POST['action'] ?? '';
    $id_agenda = intval($_POST['id_agenda'] ?? 0);

    // A. PROSES HAPUS (DELETE) AGENDA
    if (isset($_POST['hapus_agenda']) && $id_agenda > 0) {
        $stmt_del = $conn->prepare("DELETE FROM agenda WHERE id = ? AND user_id = ?");
        $stmt_del->bind_param("ii", $id_agenda, $user_id);
        
        if ($stmt_del->execute()) {
            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => 'Berhasil Dihapus',
                'message' => 'Agenda berhasil dihapus dari kalender.'
            ];
        } else {
            $_SESSION['alert'] = [
                'type' => 'error',
                'title' => 'Gagal Dihapus',
                'message' => 'Terjadi kesalahan saat menghapus agenda.'
            ];
        }
        $stmt_del->close();

        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    } 
    
    // B. PROSES SIMPAN / UPDATE AGENDA (CREATE & EDIT)
    elseif (isset($_POST['simpan_agenda'])) {
        
        $judul     = htmlspecialchars(trim($_POST['judul_agenda'] ?? ''), ENT_QUOTES, 'UTF-8');
        $kategori  = htmlspecialchars(trim($_POST['kategori'] ?? 'Umum'), ENT_QUOTES, 'UTF-8');
        $tanggal   = htmlspecialchars(trim($_POST['tanggal_agenda'] ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8');
        $deskripsi = htmlspecialchars(trim($_POST['deskripsi_agenda'] ?? ''), ENT_QUOTES, 'UTF-8');
        $waktu     = htmlspecialchars(trim($_POST['waktu_agenda'] ?? '08:00:00'), ENT_QUOTES, 'UTF-8');
        $offset    = intval($_POST['pengingat_offset'] ?? 1);

        if ($action === 'create') {
            $stmt_ins = $conn->prepare("INSERT INTO agenda (user_id, judul, kategori, tanggal, waktu, pengingat_offset, deskripsi) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_ins->bind_param("issssis", $user_id, $judul, $kategori, $tanggal, $waktu, $offset, $deskripsi);
            
            if ($stmt_ins->execute()) {
                $_SESSION['alert'] = [
                    'type' => 'success',
                    'title' => 'Agenda Ditambahkan!',
                    'message' => 'Agenda baru berhasil dicatat di kalender.'
                ];
            } else {
                $_SESSION['alert'] = [
                    'type' => 'error',
                    'title' => 'Gagal Menyimpan',
                    'message' => 'Gagal menambahkan agenda baru.'
                ];
            }
            $stmt_ins->close();

        } elseif ($action === 'edit' && $id_agenda > 0) {
            $stmt_upd = $conn->prepare("UPDATE agenda SET judul = ?, kategori = ?, tanggal = ?, waktu = ?, pengingat_offset = ?, deskripsi = ? WHERE id = ? AND user_id = ?");
            $stmt_upd->bind_param("ssssisii", $judul, $kategori, $tanggal, $waktu, $offset, $deskripsi, $id_agenda, $user_id);
            
            if ($stmt_upd->execute()) {
                $_SESSION['alert'] = [
                    'type' => 'success',
                    'title' => 'Agenda Diperbarui!',
                    'message' => 'Data agenda berhasil diperbarui.'
                ];
            } else {
                $_SESSION['alert'] = [
                    'type' => 'error',
                    'title' => 'Gagal Memperbarui',
                    'message' => 'Gagal memperbarui data agenda.'
                ];
            }
            $stmt_upd->close();
        }

        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    // C. PROSES UBAH MILESTONE BULANAN (UPDATE)
    elseif (isset($_POST['ubah_milestone'])) {
        $bulan_key   = trim($_POST['bulan_key'] ?? '07');
        $status      = trim($_POST['status_milestone'] ?? 'Pending');
        $operasional = htmlspecialchars(trim($_POST['operasional_milestone'] ?? ''), ENT_QUOTES, 'UTF-8');
        $it          = htmlspecialchars(trim($_POST['it_milestone'] ?? ''), ENT_QUOTES, 'UTF-8');

        $stmt_ms_upd = $conn->prepare("UPDATE milestones SET status = ?, operasional = ?, it = ? WHERE user_id = ? AND bulan_key = ?");
        $stmt_ms_upd->bind_param("sssis", $status, $operasional, $it, $user_id, $bulan_key);

        if ($stmt_ms_upd->execute()) {
            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => 'Milestone Diperbarui!',
                'message' => 'Target milestone bulanan berhasil disimpan.'
            ];
        } else {
            $_SESSION['alert'] = [
                'type' => 'error',
                'title' => 'Gagal Memperbarui',
                'message' => 'Gagal memperbarui target milestone.'
            ];
        }
        $stmt_ms_upd->close();

        header("Location: time-management.php?bulan=" . urlencode($bulan_key));
        exit;
    }
}


// 3. LOGIKA MATEMATIKA RENDER KALENDER
// Membaca bulan saat ini secara otomatis jika tidak ada request '?bulan=' dari URL
$bulan_aktif = isset($_GET['bulan']) ? sprintf('%02d', intval($_GET['bulan'])) : date('m');
$tahun_aktif = date('Y');

// Konversi angka ke nama bulan
$nama_bulan_indo = [
    '07' => 'Juli ' . $tahun_aktif,
    '08' => 'Agustus ' . $tahun_aktif,
    '09' => 'September ' . $tahun_aktif,
    '10' => 'Oktober ' . $tahun_aktif
];

// Menghitung slot kosong dan total hari untuk tampilan grid kalender
$total_hari   = date('t', strtotime("$tahun_aktif-$bulan_aktif-01"));
$hari_pertama = date('N', strtotime("$tahun_aktif-$bulan_aktif-01"));
$slot_kosong  = $hari_pertama - 1; 


// 4. AMBIL DATA AGENDA DARI DATABASE UNTUK BULAN AKTIF
$agenda_list = [];
$stmt_get_ag = $conn->prepare("SELECT * FROM agenda WHERE user_id = ? AND MONTH(tanggal) = ? AND YEAR(tanggal) = ? ORDER BY waktu ASC");
$stmt_get_ag->bind_param("iss", $user_id, $bulan_aktif, $tahun_aktif);
$stmt_get_ag->execute();
$res_get_ag = $stmt_get_ag->get_result();

if ($res_get_ag) {
    while ($row = $res_get_ag->fetch_assoc()) {
        $tgl_hari = date('j', strtotime($row['tanggal'])); 
        // Kelompokkan dalam array bertingkat jika ada lebih dari 1 agenda per tanggal
        $agenda_list[$tgl_hari][] = $row; 
    }
}
$stmt_get_ag->close();


// 5. AMBIL DATA MILESTONE DINAMIS DARI DATABASE (DENGAN AUTOMATIC SEEDER)
$milestone_list = [];
$stmt_ms = $conn->prepare("SELECT * FROM milestones WHERE user_id = ? ORDER BY bulan_key ASC");
$stmt_ms->bind_param("i", $user_id);
$stmt_ms->execute();
$res_ms = $stmt_ms->get_result();

while ($row = $res_ms->fetch_assoc()) {
    $milestone_list[$row['bulan_key']] = [
        'id'          => $row['id'],
        'judul'       => $row['judul'],
        'status'      => $row['status'],
        'operasional' => $row['operasional'],
        'it'          => $row['it']
    ];
}

// OTOMASI SEEDER: Jika data milestone di database user ini masih kosong, isi otomatis
if (empty($milestone_list)) {
    $defaults = [
        ['07', 'Milestone 1 (Juli)', 'Selesai', 'Penginputan & rekapitulasi data harian finansial (Tabungan, Giro, Depo) Uker Sumedang ke Excel.', 'Analisis kelemahan sistem absen fisik pemagang & perancangan basis data Tracker.'],
        ['08', 'Milestone 2 (Agustus)', 'Berjalan', 'Monitoring akuisisi produk digital (Brimo, Qlola, QRIS) dan validasi leads Brispot.', 'Desain UI/UX dashboard desktop serta sinkronisasi penataan kolom logbook agar sesuai output Excel.'],
        ['09', 'Milestone 3 (September)', 'Pending', 'Evaluasi berkala alokasi Dana Talangan Brilink dan volume transaksi Uker.', 'Implementasi koding CRUD agenda mandiri kalender dan pengujian fungsi unduh file rekapitulasi.'],
        ['10', 'Milestone 4 (Oktober)', 'Pending', 'Penyusunan laporan akhir magang, dokumentasi kode program, serta serah terima sistem.', 'Final deployment sistem absensi magang ke server produksi Laragon.']
    ];

    foreach ($defaults as $d) {
        $ins = $conn->prepare("INSERT INTO milestones (user_id, bulan_key, judul, status, operasional, it) VALUES (?, ?, ?, ?, ?, ?)");
        $ins->bind_param("isssss", $user_id, $d[0], $d[1], $d[2], $d[3], $d[4]);
        $ins->execute();
        $ins->close();
    }

    // Ambil ulang setelah di-isi data default
    $stmt_ms->execute();
    $res_ms = $stmt_ms->get_result();
    while ($row = $res_ms->fetch_assoc()) {
        $milestone_list[$row['bulan_key']] = [
            'id'          => $row['id'],
            'judul'       => $row['judul'],
            'status'      => $row['status'],
            'operasional' => $row['operasional'],
            'it'          => $row['it']
        ];
    }
}
$stmt_ms->close();


// 6. FUNGSI TRANSLATE HARI INDONESIA
function hariIndo(string $tanggal): string
{
    $hari_inggris = date('l', strtotime($tanggal));
    $daftar_hari = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
    ];
    return $daftar_hari[$hari_inggris] ?? $hari_inggris;
}


// 7. FITUR PENGINGAT DINAMIS (ALGORITMA PENCARIAN DATABASE)
$agenda_besok = null;

$query_reminder = $conn->prepare("
    SELECT judul, tanggal, waktu, pengingat_offset, deskripsi 
    FROM agenda 
    WHERE user_id = ? 
    AND CONCAT(tanggal, ' ', waktu) > NOW() 
    AND NOW() >= DATE_SUB(CONCAT(tanggal, ' ', waktu), INTERVAL pengingat_offset HOUR)
    ORDER BY tanggal ASC, waktu ASC 
    LIMIT 1
");

if ($query_reminder) {
    $query_reminder->bind_param("i", $user_id);
    $query_reminder->execute();
    $result_reminder = $query_reminder->get_result();

    if ($row_reminder = $result_reminder->fetch_assoc()) {
        $agenda_besok = [
            'judul'     => $row_reminder['judul'],
            'tanggal'   => $row_reminder['tanggal'],
            'waktu'     => $row_reminder['waktu'],
            'offset'    => $row_reminder['pengingat_offset'],
            'deskripsi' => $row_reminder['deskripsi']
        ];
    }
    $query_reminder->close();
}
?>