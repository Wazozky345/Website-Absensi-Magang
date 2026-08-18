<?php
// Jalur absolut langsung masuk ke folder config
require_once __DIR__ . '/../config/sesi.php';
require_once __DIR__ . '/../config/koneksi.php'; // <--- INI OBATNYA! Memanggil database

// Pastikan user_id terambil dari sesi
$user_id = $_SESSION['user_id'] ?? 0;

// 1. Ambil data informasi mahasiswa
$query_user = $conn->query("SELECT * FROM users WHERE id = '$user_id'");
$user_data = $query_user->fetch_assoc();

// =========================================================================
// LOGIKA DETEKSI TANGGAL MERAH OTOMATIS (API + LOCAL CACHE + FOLDER KHUSUS)
// =========================================================================
$tahun_sekarang = date('Y');

$dir_api_logs = __DIR__ . "/../config/api_logs";
$file_cache_libur = $dir_api_logs . "/libur_nasional_{$tahun_sekarang}.json";
$libur_nasional = [];

if (!is_dir($dir_api_logs)) {
    mkdir($dir_api_logs, 0755, true);
}

if (file_exists($file_cache_libur)) {
    $libur_nasional = json_decode(file_get_contents($file_cache_libur), true) ?? [];
} else {
    $file_lama = glob($dir_api_logs . "/libur_nasional_*.json");
    if ($file_lama) {
        foreach ($file_lama as $file) {
            if ($file !== $file_cache_libur && is_file($file)) {
                @unlink($file); 
            }
        }
    }

    $url_api = "https://dayoffapi.vercel.app/api?year={$tahun_sekarang}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); 

    $is_localhost = in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1']);
    if ($is_localhost) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
    } else {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); 
    }

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $data_api = json_decode($response, true);
        if (is_array($data_api) && !empty($data_api)) {
            foreach ($data_api as $libur) {
                if (isset($libur['is_cuti']) && $libur['is_cuti'] == false) {
                    $libur_nasional[] = $libur['tanggal'];
                }
            }
        }
    }

    file_put_contents($file_cache_libur, json_encode($libur_nasional));
}
// =========================================================================

// 2. Pengaturan Periode 
$periode_mulai = '2026-07-08';
$periode_selesai = '2026-10-08';
$hari_ini = date('Y-m-d');
$jam_kerja = '07:30 - 16:30';

// Fungsi di-update untuk menerima parameter array $libur_nasional
function getHariKerja($start, $end, $libur_arr) {
    $mulai = new DateTime($start);
    $selesai = new DateTime($end);
    $selesai->modify('+1 day'); 
    
    $interval = DateInterval::createFromDateString('1 day');
    $period = new DatePeriod($mulai, $interval, $selesai);

    $hari_kerja = 0;
    foreach ($period as $dt) {
        $tgl_loop = $dt->format('Y-m-d');
        if ($dt->format('N') <= 5 && !in_array($tgl_loop, $libur_arr)) { 
            $hari_kerja++;
        }
    }
    return $hari_kerja;
}

$total_hari_kerja = getHariKerja($periode_mulai, $periode_selesai, $libur_nasional);
$tgl_terlewati = ($hari_ini > $periode_selesai) ? $periode_selesai : $hari_ini;
$hari_kerja_terlewati = getHariKerja($periode_mulai, $tgl_terlewati, $libur_nasional);

$sisa_hari_kerja = $total_hari_kerja - $hari_kerja_terlewati;
if ($sisa_hari_kerja < 0) $sisa_hari_kerja = 0;

$progres = ($total_hari_kerja > 0) ? ($hari_kerja_terlewati / $total_hari_kerja) : 0;
$progres_persen = round($progres * 100, 2) . '%';

// 3. Ambil data statistik dari Database
$stat_hadir = $conn->query("SELECT COUNT(id) as total FROM kehadiran WHERE user_id = '$user_id' AND status IN ('Hadir', 'Lembur')")->fetch_assoc()['total'];
$stat_izin  = $conn->query("SELECT COUNT(id) as total FROM kehadiran WHERE user_id = '$user_id' AND status = 'Izin'")->fetch_assoc()['total'];
$stat_sakit = $conn->query("SELECT COUNT(id) as total FROM kehadiran WHERE user_id = '$user_id' AND status = 'Sakit'")->fetch_assoc()['total'];
$stat_alpha = 0; 
$stat_cuti  = 0;
$stat_libur = $total_hari_kerja - ($stat_hadir + $stat_izin + $stat_sakit); 

// 4. Ambil riwayat absen lengkap
$query_absen = $conn->query("SELECT * FROM kehadiran WHERE user_id = '$user_id' ORDER BY tanggal ASC");

// =========================================================================
// HEADER UNTUK EXPORT FILE CSV
// =========================================================================
$filename = "Absensi_Magang_" . str_replace(' ', '_', $user_data['nama_user']) . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, ['DASHBOARD ABSENSI MAGANG - ' . strtoupper($user_data['nama_user'])]);
fputcsv($output, []); 
fputcsv($output, ['Periode Mulai', ': ' . $periode_mulai, '', 'Status', 'Jumlah Hari']);
fputcsv($output, ['Periode Selesai', ': ' . $periode_selesai, '', 'Hadir & Lembur', $stat_hadir]);
fputcsv($output, ['Jam Kerja', ': ' . $jam_kerja, '', 'Izin', $stat_izin]);
fputcsv($output, ['Hari Ini', ': ' . $hari_ini, '', 'Sakit', $stat_sakit]);
fputcsv($output, ['Total Hari Kerja (Non-Libur)', ': ' . $total_hari_kerja, '', 'Alpha', $stat_alpha]);
fputcsv($output, ['Hari Kerja Terlewati', ': ' . $hari_kerja_terlewati, '', 'Cuti', $stat_cuti]);
fputcsv($output, ['Sisa Hari Kerja', ': ' . $sisa_hari_kerja, '', 'Libur / Belum Absen', $stat_libur]);
fputcsv($output, ['Progres (%)', ': ' . $progres_persen, '', '', '']);
fputcsv($output, []); 
fputcsv($output, []); 

fputcsv($output, ['No', 'Tanggal', 'Hari', 'Jam Masuk', 'Jam Keluar', 'Total Jam', 'Status', 'Catatan / Logbook']);

function getHariIndo(string $tanggal) {
    $hari_inggris = date('l', strtotime($tanggal));
    $daftar_hari = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
    ];
    return $daftar_hari[$hari_inggris];
}

$no = 1;
if($query_absen->num_rows > 0) {
    while($row = $query_absen->fetch_assoc()) {
        $tgl = $row['tanggal'];
        $hari_indo = getHariIndo($tgl);
        
        if (in_array($row['status'], ['Hadir', 'Lembur'])) {
            $jam_masuk = substr($row['waktu_masuk'], 0, 5);
            
            if (!empty($row['waktu_keluar'])) {
                $jam_keluar = substr($row['waktu_keluar'], 0, 5);
                $selisih = strtotime($row['waktu_keluar']) - strtotime($row['waktu_masuk']);
                $total_jam = round($selisih / 3600, 1) . ' Jam';
            } else {
                $jam_keluar = 'Belum Checkout';
                $total_jam = '-';
            }
        } else {
            $jam_masuk = substr($row['waktu_masuk'], 0, 5);
            $jam_keluar = '-';
            $total_jam = '-';
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
    fputcsv($output, ['Belum ada data absensi yang tercatat.']);
}

fclose($output);
exit;
?>