<?php
// Jalur absolut langsung masuk ke folder config
require_once __DIR__ . '/../config/sesi.php';
require_once __DIR__ . '/../config/koneksi.php';

// Panggil autoload composer Dompdf
require_once __DIR__ . '/../Lib/dompdf/autoload.inc.php'; 

use Dompdf\Dompdf;
use Dompdf\Options;

// Pastikan user_id terambil dari sesi
$user_id = $_SESSION['user_id'] ?? 0;

// 1. Ambil data informasi mahasiswa
$query_user = $conn->query("SELECT * FROM users WHERE id = '$user_id'");
$user_data = ($query_user && $query_user->num_rows > 0) ? $query_user->fetch_assoc() : [];

// =========================================================================
// LOGIKA DETEKSI TANGGAL MERAH (CACHE LOKAL + FALLBACK AUTO FETCH API)
// =========================================================================
$tahun_sekarang = date('Y');
$dir_api_logs = __DIR__ . "/../config/api_logs";
$file_cache_libur = $dir_api_logs . "/libur_nasional_{$tahun_sekarang}.json";
$libur_nasional = [];

// Pastikan folder config/api_logs ada
if (!is_dir($dir_api_logs)) {
    mkdir($dir_api_logs, 0755, true);
}

if (file_exists($file_cache_libur)) {
    // 1. Baca dari file JSON lokal yang sudah tersedia
    $libur_nasional = json_decode(file_get_contents($file_cache_libur), true) ?? [];
} else {
    // 2. Jika file JSON belum ada / baru saja dihapus, coba tarik dari API
    $url_api = "https://dayoffapi.vercel.app/api?year={$tahun_sekarang}";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Timeout 3 detik agar tidak menghambat PDF jika API mati
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response && $http_code === 200) {
        $data_api = json_decode($response, true);
        if (is_array($data_api) && !empty($data_api)) {
            foreach ($data_api as $libur) {
                if (isset($libur['is_cuti']) && $libur['is_cuti'] == false) {
                    $libur_nasional[] = $libur['tanggal'];
                }
            }
        }
    }

    // 3. Simpan hasilnya ke file JSON (jika API gagal/kosong, buat JSON kosongan [])
    file_put_contents($file_cache_libur, json_encode($libur_nasional));
}

// Fungsi penerjemah hari
function getHariIndo(string $tanggal) {
    $hari_inggris = date('l', strtotime($tanggal));
    $daftar_hari = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
    ];
    return $daftar_hari[$hari_inggris] ?? $hari_inggris;
}

// =========================================================================
// MENGAMBIL DATA KEHADIRAN DARI DATABASE (FULL DINAMIS)
// =========================================================================
$query_absen = $conn->query("SELECT * FROM kehadiran WHERE user_id = '$user_id' ORDER BY tanggal ASC");

$html_tabel_absen = '';
$no = 1;
$total_data = 0;

if ($query_absen && $query_absen->num_rows > 0) {
    while ($row = $query_absen->fetch_assoc()) {
        $total_data++;
        $tgl_key    = date('Y-m-d', strtotime($row['tanggal']));
        $tgl_format = date('d-m-Y', strtotime($row['tanggal']));
        $hari_inggris = date('l', strtotime($row['tanggal']));
        $hari_indo  = getHariIndo($tgl_key);
        
        $catatan = !empty($row['catatan']) ? $row['catatan'] : ($row['status'] == 'Lembur' ? 'Lembur Operasional' : '-');
        
        // Pembacaan Paraf Mentor (Base64 atau File Path)
        $paraf_data = $row['paraf_mentor'] ?? $row['paraf'] ?? $row['ttd_mentor'] ?? '';
        $paraf_img  = '';

        if (!empty($paraf_data)) {
            if (strpos($paraf_data, 'data:image') === 0) {
                $paraf_img = '<img src="' . $paraf_data . '" style="max-height: 30px; max-width: 65px; display: block; margin: 0 auto;">';
            } else {
                $path_paraf_abs = realpath(__DIR__ . '/../' . ltrim($paraf_data, '/'));
                if ($path_paraf_abs && file_exists($path_paraf_abs)) {
                    $type_p = pathinfo($path_paraf_abs, PATHINFO_EXTENSION);
                    $data_p = file_get_contents($path_paraf_abs);
                    $base64_p = 'data:image/' . $type_p . ';base64,' . base64_encode($data_p);
                    $paraf_img = '<img src="' . $base64_p . '" style="max-height: 30px; max-width: 65px; display: block; margin: 0 auto;">';
                }
            }
        }

        // Highlight merah untuk hari Minggu dan tanggal merah (Libur Nasional)
        $style_hari = (in_array($tgl_key, $libur_nasional) || $hari_inggris == 'Sunday') ? 'color: red;' : '';

        $html_tabel_absen .= '
        <tr>
            <td style="text-align:center; height: 26px;">' . $no++ . '</td>
            <td style="text-align:center;"><span style="' . $style_hari . '">' . $hari_indo . '</span>,<br>' . $tgl_format . '</td>
            <td style="text-align:left; padding-left:6px;">' . htmlspecialchars($catatan) . '</td>
            <td style="text-align:center; vertical-align:middle;">' . $paraf_img . '</td>
        </tr>';
    }
}

// =========================================================================
// PADDING BARIS KOSONG (MINIMUM ROWS UNTUK TEMPLATE RESMI)
// =========================================================================
$min_rows = 13; 
if ($total_data < $min_rows) {
    $sisa_baris = $min_rows - $total_data;
    for ($i = 0; $i < $sisa_baris; $i++) {
        $html_tabel_absen .= '
        <tr>
            <td style="text-align:center; height: 26px;">' . $no++ . '</td>
            <td></td>
            <td></td>
            <td></td>
        </tr>';
    }
}

// =========================================================================
// LOGIKA GAMBAR BASE 64 LOGO UTB
// =========================================================================
$path_logo = realpath(__DIR__ . '/../assets/picture/logo_utb.png');
if (!$path_logo) {
    $path_logo = __DIR__ . '/../assets/picture/logo_utb.png';
}

if (file_exists($path_logo)) {
    $type = pathinfo($path_logo, PATHINFO_EXTENSION);
    $data_img = file_get_contents($path_logo);
    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data_img);
    $logo_img = '<img src="' . $base64 . '" style="width: 70px; height: auto;">';
} else {
    $logo_img = '<div style="font-weight:bold; font-size:11pt;">UTB</div>';
}

// =========================================================================
// SETUP VARIABEL DISPLAY INFO MAHASISWA
// =========================================================================
$nama_user_display   = $user_data['nama_user'] ?? '.......................';
$nim_user_display    = $user_data['nim'] ?? '.......................';
$perusahaan_display  = $user_data['tempat_magang'] ?? '.......................';
$unit_bagian_display = '.......................';

// =========================================================================
// STRUKTUR HTML UNTUK RENDER PDF
// =========================================================================
$html = '
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
    @page { 
        size: A4 portrait; 
        margin: 12mm 18mm; 
    }
    
    body {
        font-family: "Times New Roman", Times, serif;
        font-size: 10.5pt;
        color: #000;
        line-height: 1.2;
    }
    table { width: 100%; border-collapse: collapse; }
    
    /* Layout Header Kop Surat */
    .header-table td { border: 1px solid black; vertical-align: middle; }
    .logo-cell { width: 20%; text-align: center; padding: 6px; }
    .title-cell { width: 55%; text-align: center; }
    .title-top { padding: 6px 4px; }
    .title-bottom { border-top: 1px solid black; padding: 5px; font-weight: bold; font-size: 11pt; }
    .info-cell { width: 25%; font-size: 8.5pt; vertical-align: top; padding: 0; }
    
    .info-inner-table td { border: none; border-bottom: 1px solid black; padding: 3px 4px; }
    .info-inner-table tr:last-child td { border-bottom: none; }
    
    /* Layout Judul Dokumen */
    .doc-title { text-align: center; font-weight: bold; font-size: 11pt; margin: 16px 0 20px 0; line-height: 1.4; }
    
    /* Layout Info Mahasiswa */
    .student-info { margin-bottom: 8px; font-size: 10.5pt; border: none; }
    .student-info td { border: none; padding: 2px 0; vertical-align: bottom; }
    .col-label { width: 10%; }
    .col-colon { width: 2%; }
    .col-value { width: 38%; }
    .col-label-right { width: 15%; }
    
    /* Layout Tabel Utama */
    .absen-table th, .absen-table td { border: 1px solid black; padding: 5px 4px; font-size: 10pt; }
    .absen-table th { background-color: #b4c6e7; font-weight: bold; text-align: center; }
    
    /* Layout Footer & TTD */
    .footer-container { margin-top: 10px; width: 100%; }
    .footer-note { font-size: 8.5pt; width: 55%; float: left; }
    .signature-box { float: right; width: 40%; text-align: center; font-size: 10.5pt; }
</style>
</head>
<body>

<!-- Kop Surat -->
<table class="header-table">
    <tr>
        <td class="logo-cell">
            ' . $logo_img . '
        </td>
        <td class="title-cell">
            <div class="title-top">
                <div style="font-size: 12pt; font-weight: bold; margin-bottom: 2px;">UNIVERSITAS TEKNOLOGI BANDUNG</div>
                <div style="font-size: 8.5pt;">Jl Soekarno Hatta No.378 Telp. (022) 522-4000</div>
                <div style="font-size: 8.5pt;">Bandung-40235 Jawa Barat</div>
            </div>
            <div class="title-bottom">
                ABSENSI KERJA PRAKTIK
            </div>
        </td>
        <td class="info-cell">
            <table class="info-inner-table">
                <tr><td style="width: 48%;">No. Dokumen</td><td style="width: 4%;">:</td><td style="width: 48%;"></td></tr>
                <tr><td>No. Revisi</td><td>:</td><td></td></tr>
                <tr><td>Tgl. Berlaku</td><td>:</td><td></td></tr>
                <tr><td>Halaman</td><td>:</td><td></td></tr>
            </table>
        </td>
    </tr>
</table>

<!-- Judul Dokumen -->
<div class="doc-title">
    LEMBAR ABSENSI KEHADIRAN KERJA PRAKTIK (KP)<br>
    SEMESTER GANJIL/GENAP *<br>
    TAHUN AJARAN ' . date('Y') . '/' . (date('Y') + 1) . '
</div>

<!-- Informasi Mahasiswa -->
<table class="student-info">
    <tr>
        <td class="col-label">NIM</td>
        <td class="col-colon">:</td>
        <td class="col-value">' . htmlspecialchars($nim_user_display) . '</td>
        <td class="col-label-right">Perusahaan</td>
        <td class="col-colon">:</td>
        <td class="col-value">' . htmlspecialchars($perusahaan_display) . '</td>
    </tr>
    <tr>
        <td class="col-label">Nama</td>
        <td class="col-colon">:</td>
        <td class="col-value">' . htmlspecialchars($nama_user_display) . '</td>
        <td class="col-label-right">Unit/Bagian</td>
        <td class="col-colon">:</td>
        <td class="col-value">' . htmlspecialchars($unit_bagian_display) . '</td>
    </tr>
</table>

<!-- Tabel Absensi -->
<table class="absen-table">
    <thead>
        <tr>
            <th style="width: 6%;">No</th>
            <th style="width: 22%;">Hari/Tanggal</th>
            <th style="width: 57%;">Uraian Kegiatan pada Perusahaan</th>
            <th style="width: 15%;">Paraf<br>Pembina</th>
        </tr>
    </thead>
    <tbody>
        ' . $html_tabel_absen . '
    </tbody>
</table>

<!-- Keterangan & TTD -->
<div class="footer-container">
    <div class="footer-note">
        Ket : *Coret salah satu, dilengkapi dengan tanda tangan Pembina kerja praktik dan stempel perusahaan.
    </div>

    <div class="signature-box">
        Pembina Kerja Praktik
        <br><br><br><br>
        (.......................................................)
    </div>
</div>

</body>
</html>
';

// Render ke Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$nama_file = "Absensi_Magang_" . str_replace(' ', '_', $nama_user_display) . ".pdf";

// Attachment => 0 untuk Preview di tab browser
$dompdf->stream($nama_file, ["Attachment" => 0]); 
exit;
?>