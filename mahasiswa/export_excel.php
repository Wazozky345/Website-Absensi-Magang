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
$user_data = $query_user->fetch_assoc();

// =========================================================================
// LOGIKA DETEKSI TANGGAL MERAH
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
    $url_api = "https://dayoffapi.vercel.app/api?year={$tahun_sekarang}";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
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

// Ambil riwayat absen lengkap
$query_absen = $conn->query("SELECT * FROM kehadiran WHERE user_id = '$user_id' ORDER BY tanggal ASC");

// Fungsi penerjemah hari
function getHariIndo(string $tanggal) {
    $hari_inggris = date('l', strtotime($tanggal));
    $daftar_hari = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
    ];
    return $daftar_hari[$hari_inggris];
}

// Looping isi tabel berdasarkan history absen database 
$html_tabel_absen = '';
$no = 1;
if($query_absen->num_rows > 0) {
    while($row = $query_absen->fetch_assoc()) {
        $tgl_format = date('d-m-Y', strtotime($row['tanggal']));
        $hari_indo = getHariIndo($row['tanggal']);
        $catatan = !empty($row['catatan']) ? $row['catatan'] : '-';
        
        $html_tabel_absen .= '
        <tr>
            <td style="text-align:center;">' . $no++ . '</td>
            <td style="text-align:center;">' . $hari_indo . ',<br>' . $tgl_format . '</td>
            <td style="text-align:left; padding-left:5px;">' . htmlspecialchars($catatan) . '</td>
            <td></td>
        </tr>';
    }
} else {
    for ($i = 0; $i < 8; $i++) {
        $html_tabel_absen .= '
        <tr>
            <td style="height: 25px;"></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>';
    }
}

// =========================================================================
// LOGIKA GAMBAR BASE 64 (MENGGUNAKAN __DIR__ DINAMIS)
// =========================================================================
// Mengambil path absolut otomatis dari root sistem
$path_logo = realpath(__DIR__ . '/../assets/picture/logo_utb.png');

// Fallback jika file sistem tidak mengenali pembacaan realpath (biasanya terjadi di beberapa OS)
if (!$path_logo) {
    $path_logo = __DIR__ . '/../assets/picture/logo_utb.png';
}

if (file_exists($path_logo)) {
    // Jika gambar ketemu, convert ke Base64
    $type = pathinfo($path_logo, PATHINFO_EXTENSION);
    $data_img = file_get_contents($path_logo);
    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data_img);
    $logo_img = '<img src="' . $base64 . '" style="width: 75px; height: auto;">';
} else {
    // JIKA GAMBAR GAGAL DITEMUKAN, MUNCULKAN PESAN ERROR MERAH DI PDF
    $logo_img = '<div style="color:red; font-weight:bold; font-size:10px;">
                    GAMBAR GAGAL DIMUAT!<br>
                    Path yang dicari:<br>' . htmlspecialchars($path_logo) . '
                 </div>';
}

// =========================================================================
// STRUKTUR HTML
// =========================================================================
$html = '
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
    @page { 
        size: A4; 
        margin: 15mm 20mm; 
    }
    
    body {
        font-family: "Times New Roman", Times, serif;
        font-size: 11pt;
        color: #000;
        line-height: 1.2;
    }
    table { width: 100%; border-collapse: collapse; }
    
    /* Layout Header Kop Surat */
    .header-table td { border: 1px solid black; vertical-align: middle; }
    .logo-cell { width: 22%; text-align: center; padding: 10px; }
    .title-cell { width: 55%; text-align: center; }
    .title-top { padding: 10px 5px; }
    .title-bottom { border-top: 1px solid black; padding: 8px; }
    .info-cell { width: 23%; font-size: 8.5pt; vertical-align: top; padding: 0; }
    
    .info-inner-table td { border: none; border-bottom: 1px solid black; padding: 4px; }
    .info-inner-table tr:last-child td { border-bottom: none; }
    
    /* Layout Judul Dokumen */
    .doc-title { text-align: center; font-weight: bold; font-size: 11pt; margin: 25px 0 35px 0; line-height: 1.5; }
    
    /* Layout Info Mahasiswa */
    .student-info { margin-bottom: 10px; font-size: 11pt; border: none; }
    .student-info td { border: none; padding: 2px 0; vertical-align: bottom; }
    .col-label { width: 10%; }
    .col-colon { width: 2%; }
    .col-value { width: 38%; }
    .col-label-right { width: 15%; }
    
    /* Layout Tabel Utama */
    .absen-table th, .absen-table td { border: 1px solid black; padding: 8px 5px; font-size: 11pt; }
    .absen-table th { background-color: #b4c6e7; font-weight: bold; text-align: center; }
    
    /* Layout Footer & TTD */
    .footer-note { font-size: 9.5pt; margin-top: 8px; margin-bottom: 40px; }
    .signature-box { float: right; width: 40%; text-align: center; font-size: 11pt; }
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
                <div style="font-size: 13pt; font-weight: bold; margin-bottom: 3px;">UNIVERSITAS TEKNOLOGI BANDUNG</div>
                <div style="font-size: 10pt;">Jl Soekarno Hatta No.378 Telp. (022) 522-4000</div>
                <div style="font-size: 10pt;">Bandung-40235 Jawa Barat</div>
            </div>
            <div class="title-bottom">
                <div style="font-size: 12pt;">ABSENSI KERJA PRAKTIK</div>
            </div>
        </td>
        <td class="info-cell">
            <table class="info-inner-table">
                <tr><td style="width: 45%;">No. Dokumen</td><td style="width: 5%;">:</td><td style="width: 50%;"></td></tr>
                <tr><td>No. Revisi</td><td>:</td><td></td></tr>
                <tr><td>Tgl. Berlaku</td><td>:</td><td></td></tr>
            </table>
        </td>
    </tr>
</table>

<!-- Judul Dokumen -->
<div class="doc-title">
    LEMBAR ABSENSI KEHADIRAN KERJA PRAKTIK (KP)<br>
    SEMESTER GANJIL/GENAP *<br>
    TAHUN AJARAN ............../..............
</div>

<!-- Informasi Mahasiswa -->
<table class="student-info">
    <tr>
        <td class="col-label">NIM</td>
        <td class="col-colon">:</td>
        <td class="col-value">' . (isset($user_data['nim']) ? htmlspecialchars($user_data['nim']) : '.......................') . '</td>
        <td class="col-label-right">Perusahaan</td>
        <td class="col-colon">:</td>
        <td class="col-value">' . (isset($user_data['perusahaan']) ? htmlspecialchars($user_data['perusahaan']) : '.......................') . '</td>
    </tr>
    <tr>
        <td class="col-label">Nama</td>
        <td class="col-colon">:</td>
        <td class="col-value">' . htmlspecialchars($user_data['nama_user']) . '</td>
        <td class="col-label-right">Unit/Bagian</td>
        <td class="col-colon">:</td>
        <td class="col-value">' . (isset($user_data['unit_bagian']) ? htmlspecialchars($user_data['unit_bagian']) : '.......................') . '</td>
    </tr>
</table>

<!-- Tabel Absensi -->
<table class="absen-table">
    <thead>
        <tr>
            <th style="width: 5%;">No</th>
            <th style="width: 20%;">Hari/Tanggal</th>
            <th style="width: 60%;">Uraian Kegiatan pada Perusahaan</th>
            <th style="width: 15%;">Paraf<br>Pembina</th>
        </tr>
    </thead>
    <tbody>
        ' . $html_tabel_absen . '
    </tbody>
</table>

<!-- Keterangan & TTD -->
<div class="footer-note">
    Ket : *Coret salah satu, dilengkapi dengan tanda tangan Pembina kerja praktik dan stempel perusahaan.
</div>

<div class="signature-box">
    Pembina Kerja Praktik
    <br><br><br><br><br>
    (.......................................................)
</div>

</body>
</html>
';

// Render & Output ke PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$nama_file = "Absensi_Magang_" . str_replace(' ', '_', $user_data['nama_user']) . ".pdf";
$dompdf->stream($nama_file, ["Attachment" => 1]);
exit;
?>