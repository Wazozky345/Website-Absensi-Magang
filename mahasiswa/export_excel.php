<?php
// Jalur naik 1 tingkat ke folder config
require_once __DIR__ . '/../config/sesi.php';

// 1. Ambil data informasi mahasiswa
$user_id_clean = intval($user_id);
$stmt_user = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id_clean);
$stmt_user->execute();
$res_user = $stmt_user->get_result();

if (!$res_user || $res_user->num_rows === 0) {
    die("Data pengguna tidak ditemukan.");
}

$user_data = $res_user->fetch_assoc();
$stmt_user->close();

// 2. Pengaturan Periode Magang
$periode_mulai   = '2026-07-08';
$periode_selesai = '2026-10-08';
$hari_ini        = date('Y-m-d');
$jam_kerja       = '07:30 - 16:30';

function getHariKerja($start, $end) {
    $mulai   = new DateTime($start);
    $selesai = new DateTime($end);
    $selesai->modify('+1 day'); 
    
    $interval = DateInterval::createFromDateString('1 day');
    $period   = new DatePeriod($mulai, $interval, $selesai);
    
    $hari_kerja = 0;
    foreach ($period as $dt) {
        if ($dt->format('N') <= 5) { 
            $hari_kerja++;
        }
    }
    return $hari_kerja;
}

$total_hari_kerja     = getHariKerja($periode_mulai, $periode_selesai);
$tgl_terlewati        = ($hari_ini > $periode_selesai) ? $periode_selesai : $hari_ini;
$hari_kerja_terlewati = getHariKerja($periode_mulai, $tgl_terlewati);

$sisa_hari_kerja = $total_hari_kerja - $hari_kerja_terlewati;
if ($sisa_hari_kerja < 0) $sisa_hari_kerja = 0;

$progres        = ($total_hari_kerja > 0) ? ($hari_kerja_terlewati / $total_hari_kerja) : 0;
$progres_persen = round($progres * 100, 2) . '%';

// 3. Ambil data statistik dari Database
$stmt_hadir = $conn->prepare("SELECT COUNT(id) as total FROM kehadiran WHERE user_id = ? AND status IN ('Hadir', 'Lembur')");
$stmt_hadir->bind_param("i", $user_id_clean);
$stmt_hadir->execute();
$stat_hadir = $stmt_hadir->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_hadir->close();

$stmt_izin = $conn->prepare("SELECT COUNT(id) as total FROM kehadiran WHERE user_id = ? AND status = 'Izin'");
$stmt_izin->bind_param("i", $user_id_clean);
$stmt_izin->execute();
$stat_izin = $stmt_izin->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_izin->close();

$stmt_sakit = $conn->prepare("SELECT COUNT(id) as total FROM kehadiran WHERE user_id = ? AND status = 'Sakit'");
$stmt_sakit->bind_param("i", $user_id_clean);
$stmt_sakit->execute();
$stat_sakit = $stmt_sakit->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_sakit->close();

$stat_alpha = 0; 
$stat_cuti  = 0;
$stat_libur = $total_hari_kerja - ($stat_hadir + $stat_izin + $stat_sakit); 
if ($stat_libur < 0) $stat_libur = 0;

// 4. Ambil riwayat absen lengkap
$stmt_absen = $conn->prepare("SELECT * FROM kehadiran WHERE user_id = ? ORDER BY tanggal ASC");
$stmt_absen->bind_param("i", $user_id_clean);
$stmt_absen->execute();
$query_absen = $stmt_absen->get_result();

// HEADER EXPORT CSV
$nama_clean = preg_replace('/[^a-zA-Z0-9_]/', '_', $user_data['nama_user'] ?? 'Mahasiswa');
$filename   = "Absensi_Magang_" . $nama_clean . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// MENULIS DATA DASHBOARD KE CSV
fputcsv($output, ['DASHBOARD ABSENSI MAGANG - ' . strtoupper($user_data['nama_user'] ?? '')]);
fputcsv($output, []);
fputcsv($output, ['Periode Mulai', ': ' . $periode_mulai, '', 'Status', 'Jumlah Hari']);
fputcsv($output, ['Periode Selesai', ': ' . $periode_selesai, '', 'Hadir & Lembur', $stat_hadir]);
fputcsv($output, ['Jam Kerja', ': ' . $jam_kerja, '', 'Izin', $stat_izin]);
fputcsv($output, ['Hari Ini', ': ' . $hari_ini, '', 'Sakit', $stat_sakit]);
fputcsv($output, ['Total Hari Kerja (Senin-Jumat)', ': ' . $total_hari_kerja, '', 'Alpha', $stat_alpha]);
fputcsv($output, ['Hari Kerja Terlewati', ': ' . $hari_kerja_terlewati, '', 'Cuti', $stat_cuti]);
fputcsv($output, ['Sisa Hari Kerja', ': ' . $sisa_hari_kerja, '', 'Libur / Belum Absen', $stat_libur]);
fputcsv($output, ['Progres (%)', ': ' . $progres_persen, '', '', '']);
fputcsv($output, []);
fputcsv($output, []);

// HEADER TABEL UTAMA
fputcsv($output, ['No', 'Tanggal', 'Hari', 'Jam Masuk', 'Jam Keluar', 'Total Jam', 'Status', 'Catatan / Logbook']);

function getHariIndo(string $tanggal) {
    $hari_inggris = date('l', strtotime($tanggal));
    $daftar_hari = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
    ];
    return $daftar_hari[$hari_inggris] ?? $hari_inggris;
}

// ISI TABEL
$no = 1;
if ($query_absen && $query_absen->num_rows > 0) {
    while ($row = $query_absen->fetch_assoc()) {
        $tgl       = $row['tanggal'];
        $hari_indo = getHariIndo($tgl);
        
        $waktu_masuk  = $row['waktu_masuk'] ?? '';
        $waktu_keluar = $row['waktu_keluar'] ?? '';

        if (in_array($row['status'], ['Hadir', 'Lembur'])) {
            $jam_masuk = !empty($waktu_masuk) ? substr($waktu_masuk, 0, 5) : '-';
            
            if (!empty($waktu_keluar)) {
                $jam_keluar = substr($waktu_keluar, 0, 5);
                $selisih    = strtotime($waktu_keluar) - strtotime($waktu_masuk);
                $total_jam  = round($selisih / 3600, 1) . ' Jam';
            } else {
                $jam_keluar = 'Belum Checkout';
                $total_jam  = '-';
            }
        } else {
            $jam_masuk  = !empty($waktu_masuk) ? substr($waktu_masuk, 0, 5) : '-';
            $jam_keluar = '-';
            $total_jam  = '-';
        }

        $catatan = !empty($row['catatan']) ? $row['catatan'] : '-';
        $catatan = str_replace(array("\r\n", "\r", "\n"), ' ', $catatan);

        fputcsv($output, [
            $no++, 
            $tgl, 
            $hari_indo, 
            $jam_masuk, 
            $jam_keluar, 
            $total_jam, 
            $row['status'], 
            $catatan
        ]);
    }
} else {
    fputcsv($output, ['-', '-', '-', '-', '-', '-', '-', 'Belum ada data absensi yang tercatat.']);
}

$stmt_absen->close();
fclose($output);
exit;
?>