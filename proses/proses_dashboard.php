<?php
// 1. Inisialisasi Sesi & Proteksi Jalur Berkas
if (file_exists(__DIR__ . '/../config/sesi.php')) {
    require_once __DIR__ . '/../config/sesi.php';
} elseif (file_exists(__DIR__ . '/../../config/sesi.php')) {
    require_once __DIR__ . '/../../config/sesi.php';
} else {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once __DIR__ . '/../config/koneksi.php';
}

date_default_timezone_set('Asia/Jakarta');

// === FIX BUG: PEMBUAT TOKEN CSRF ===
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// AMAN DARI ERROR: Deklarasikan variabel $user_id dari sesi secara eksplisit
$user_id = $_SESSION['user_id'] ?? 0;

if ($user_id <= 0) {
    header("Location: ../login.php");
    exit;
}

// 2. Persiapan Data Dasar
$pesan_alert      = "";
$tanggal_hari_ini = date('Y-m-d');
$waktu_sekarang   = date('H:i:s');
$hari_ini_angka   = date('N');
$is_weekend       = ($hari_ini_angka >= 6);

// 3. AMBIL DATA USER (Untuk Profile Card)
$stmt_user = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$query_user = $stmt_user->get_result();
$user_data  = $query_user->fetch_assoc();
$stmt_user->close();

// 4. PROSES SUBMIT POST (ABSEN MASUK, PULANG, & LOGBOOK)
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

    // A. LOGIKA ABSEN MASUK / LEMBUR
    if (isset($_POST['submit_masuk']) || isset($_POST['submit_lembur'])) {
        $status = isset($_POST['submit_lembur']) ? 'Lembur' : trim($_POST['status'] ?? 'Hadir');

        $stmt_cek = $conn->prepare("SELECT id FROM kehadiran WHERE user_id = ? AND tanggal = ?");
        $stmt_cek->bind_param("is", $user_id, $tanggal_hari_ini);
        $stmt_cek->execute();
        $res_cek = $stmt_cek->get_result();

        if ($res_cek->num_rows === 0) {
            $stmt_ins = $conn->prepare("INSERT INTO kehadiran (user_id, tanggal, waktu_masuk, status) VALUES (?, ?, ?, ?)");
            $stmt_ins->bind_param("isss", $user_id, $tanggal_hari_ini, $waktu_sekarang, $status);
            
            if ($stmt_ins->execute()) {
                if ($status === 'Lembur') {
                    $pesan_alert = "Semangat lembur! Waktu ekstra kamu sudah tercatat.";
                } elseif ($status === 'Sakit') {
                    $pesan_alert = "Laporan sakit diterima. Jangan lupa istirahat dan semoga lekas sembuh!";
                } elseif ($status === 'Izin') {
                    $pesan_alert = "Laporan izin berhasil dicatat. Semoga urusan hari ini dilancarkan!";
                } else {
                    $pesan_alert = "Mantap! Absen masuk berhasil dicatat. Selamat magang!";
                }

                $_SESSION['alert'] = [
                    'type' => 'success',
                    'title' => 'Laporan Terkirim!',
                    'message' => $pesan_alert
                ];
            } else {
                $_SESSION['alert'] = [
                    'type' => 'error',
                    'title' => 'Gagal Menyimpan',
                    'message' => 'Gagal menyimpan laporan hari ini!'
                ];
            }
            $stmt_ins->close();
        } else {
            $_SESSION['alert'] = [
                'type' => 'info',
                'title' => 'Sudah Absen',
                'message' => 'Anda sudah melakukan presensi untuk hari ini.'
            ];
        }
        $stmt_cek->close();

        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    } 
    
    // B. LOGIKA ABSEN PULANG
    elseif (isset($_POST['submit_pulang'])) {
        $stmt_pulang = $conn->prepare("UPDATE kehadiran SET waktu_keluar = ? WHERE user_id = ? AND tanggal = ?");
        $stmt_pulang->bind_param("sis", $waktu_sekarang, $user_id, $tanggal_hari_ini);
        
        if ($stmt_pulang->execute()) {
            $pesan_alert = "Absen PULANG berhasil dicatat! Selamat beristirahat.";
            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => 'Absen Pulang Berhasil!',
                'message' => $pesan_alert
            ];
        } else {
            $_SESSION['alert'] = [
                'type' => 'error',
                'title' => 'Gagal Absen',
                'message' => 'Gagal mencatat absen pulang.'
            ];
        }
        $stmt_pulang->close();

        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
    
    // C. LOGIKA SIMPAN LOGBOOK
    elseif (isset($_POST['simpan_catatan'])) {
        $id_kehadiran = intval($_POST['id_kehadiran'] ?? 0);
        $catatan      = htmlspecialchars(trim($_POST['catatan'] ?? ''), ENT_QUOTES, 'UTF-8');

        $stmt_log = $conn->prepare("UPDATE kehadiran SET catatan = ? WHERE id = ? AND user_id = ?");
        $stmt_log->bind_param("sii", $catatan, $id_kehadiran, $user_id);

        if ($stmt_log->execute()) {
            $pesan_alert = "Catatan Logbook berhasil diperbarui!";
            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => 'Logbook Diperbarui!',
                'message' => $pesan_alert
            ];
        } else {
            $_SESSION['alert'] = [
                'type' => 'error',
                'title' => 'Gagal Memperbarui',
                'message' => 'Gagal memperbarui logbook.'
            ];
        }
        $stmt_log->close();

        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// 5. CEK STATUS ABSENSI HARI INI (Untuk Smart Action Box)
$stmt_today = $conn->prepare("SELECT * FROM kehadiran WHERE user_id = ? AND tanggal = ?");
$stmt_today->bind_param("is", $user_id, $tanggal_hari_ini);
$stmt_today->execute();
$query_absen_hari_ini = $stmt_today->get_result();
$data_absen_hari_ini  = $query_absen_hari_ini->fetch_assoc();
$stmt_today->close();

// 6. HITUNG STATISTIK KARTU (KPI)
// A. Hadir & Lembur
$stmt_hadir = $conn->prepare("SELECT COUNT(id) as total FROM kehadiran WHERE user_id = ? AND status IN ('Hadir', 'Lembur')");
$stmt_hadir->bind_param("i", $user_id);
$stmt_hadir->execute();
$stat_hadir = $stmt_hadir->get_result()->fetch_assoc()['total'];
$stmt_hadir->close();

// B. Izin & Sakit
$stmt_izin = $conn->prepare("SELECT COUNT(id) as total FROM kehadiran WHERE user_id = ? AND status IN ('Sakit', 'Izin')");
$stmt_izin->bind_param("i", $user_id);
$stmt_izin->execute();
$stat_izin = $stmt_izin->get_result()->fetch_assoc()['total'];
$stmt_izin->close();

// C. Deteksi Terlambat (Status Hadir & waktu_masuk > 08:00:00)
$batas_jam_masuk = '08:00:00'; 
$stmt_telat = $conn->prepare("SELECT COUNT(id) as total FROM kehadiran WHERE user_id = ? AND status = 'Hadir' AND TIME(waktu_masuk) > ?");
$stmt_telat->bind_param("is", $user_id, $batas_jam_masuk);
$stmt_telat->execute();
$stat_terlambat = $stmt_telat->get_result()->fetch_assoc()['total'];
$stmt_telat->close();

// D. Kalkulasi Sisa Hari Kerja Magang (Hingga 8 Oktober 2026)
$tgl_selesai_magang = new DateTime('2026-10-08');
$tgl_curr            = new DateTime($tanggal_hari_ini);
$sisa_hari_kerja    = 0;

if ($tgl_curr <= $tgl_selesai_magang) {
    $interval_1d = new DateInterval('P1D');
    $periode_magang = new DatePeriod($tgl_curr, $interval_1d, $tgl_selesai_magang->modify('+1 day'));
    foreach ($periode_magang as $dt) {
        // Hanya menghitung hari kerja (Senin - Jumat / N < 6)
        if ($dt->format('N') < 6) {
            $sisa_hari_kerja++;
        }
    }
}
$stat_sisa = $sisa_hari_kerja;

// 7. AMBIL DATA UNTUK GRAFIK (CHART.JS) BULANAN
$tahun_ini = date('Y');
$data_grafik_hadir = array_fill(1, 12, 0);

$stmt_grafik = $conn->prepare("
    SELECT MONTH(tanggal) as bulan, COUNT(id) as total 
    FROM kehadiran 
    WHERE user_id = ? AND status IN ('Hadir', 'Lembur') AND YEAR(tanggal) = ? 
    GROUP BY MONTH(tanggal)
");
$stmt_grafik->bind_param("is", $user_id, $tahun_ini);
$stmt_grafik->execute();
$query_grafik = $stmt_grafik->get_result();

if ($query_grafik) {
    while ($row = $query_grafik->fetch_assoc()) {
        $data_grafik_hadir[$row['bulan']] = (int)$row['total'];
    }
}
$stmt_grafik->close();

$json_grafik_hadir = json_encode(array_values($data_grafik_hadir));

// =========================================================================
// 8. DETEKSI NOTIFIKASI TUGAS AKTIF / REVISI DARI MENTOR (DIPINDAH KE SINI)
// =========================================================================
$tugas_baru_masuk = null;

$stmt_tugas = $conn->prepare("
    SELECT t.*, m.nama_mentor, td.status_approval, td.catatan_mentor, td.file_balasan
    FROM tugas t
    JOIN mentors m ON t.mentor_id = m.id
    LEFT JOIN tugas_detail td ON t.id = td.tugas_id AND td.user_id = ?
    WHERE (t.target_user_id IS NULL OR t.target_user_id = ?)
      AND (td.status_approval IS NULL OR td.status_approval = 'Belum Ada Berkas' OR td.status_approval = 'Perlu Revisi')
    ORDER BY t.created_at DESC LIMIT 1
");

if ($stmt_tugas) {
    $stmt_tugas->bind_param("ii", $user_id, $user_id);
    $stmt_tugas->execute();
    $res_tugas = $stmt_tugas->get_result();
    
    if ($res_tugas && $res_tugas->num_rows > 0) {
        $tugas_baru_masuk = $res_tugas->fetch_assoc();
    }
    $stmt_tugas->close();
}
?>