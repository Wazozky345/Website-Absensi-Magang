<?php
// =========================================================================
// CARA PAKAI (WAJIB DIBACA SEKALI):
// 1. Extract fpdf-library.zip yang saya berikan, taruh folder "fpdf" (isinya
//    fpdf.php + folder font/) SEJAJAR dengan file export_excel.php ini,
//    di dalam folder yang sama (bukan di folder config, bukan di root project).
//    Contoh struktur:
//      /export/export_excel.php   <- file ini
//      /export/fpdf/fpdf.php
//      /export/fpdf/font/...
// 2. TIDAK PERLU Composer / vendor/autoload.php sama sekali dengan versi ini.
// =========================================================================

require_once __DIR__ . '/../config/sesi.php';
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/fpdf/fpdf.php'; // <-- sesuaikan path ini kalau folder fpdf Anda taruh di lokasi lain

// Helper: konversi teks UTF-8 (dari DB) ke encoding yang dipahami FPDF standar
function txt($str) {
    $str = (string) $str;
    return @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $str) ?: $str;
}

// Pastikan user_id terambil dari sesi
$user_id = $_SESSION['user_id'] ?? 0;

// 1. Ambil data informasi mahasiswa
$query_user = $conn->query("SELECT * FROM users WHERE id = '$user_id'");
$user_data = $query_user->fetch_assoc();

// =========================================================================
// LOGIKA DETEKSI TANGGAL MERAH OTOMATIS (API + LOCAL CACHE + FOLDER KHUSUS)
// (Tidak diubah dari versi asli)
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

// 3. Statistik (tetap dihitung seperti versi asli, belum ditampilkan di lembar resmi ini)
$stat_hadir = $conn->query("SELECT COUNT(id) as total FROM kehadiran WHERE user_id = '$user_id' AND status IN ('Hadir', 'Lembur')")->fetch_assoc()['total'];
$stat_izin  = $conn->query("SELECT COUNT(id) as total FROM kehadiran WHERE user_id = '$user_id' AND status = 'Izin'")->fetch_assoc()['total'];
$stat_sakit = $conn->query("SELECT COUNT(id) as total FROM kehadiran WHERE user_id = '$user_id' AND status = 'Sakit'")->fetch_assoc()['total'];
$stat_alpha = 0;
$stat_cuti  = 0;
$stat_libur = $total_hari_kerja - ($stat_hadir + $stat_izin + $stat_sakit);

// 4. Ambil riwayat absen lengkap
$query_absen = $conn->query("SELECT * FROM kehadiran WHERE user_id = '$user_id' ORDER BY tanggal ASC");

function getHariIndo(string $tanggal) {
    $hari_inggris = date('l', strtotime($tanggal));
    $daftar_hari = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
    ];
    return $daftar_hari[$hari_inggris];
}

function formatTanggalIndo(string $tanggal) {
    $bulan_array = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $ts = strtotime($tanggal);
    return date('d', $ts) . ' ' . $bulan_array[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

// TODO: sesuaikan nama kolom berikut dengan skema database Anda yang sebenarnya
$nim        = $user_data['nim'] ?? '';
$nama       = $user_data['nama_user'] ?? '';
$perusahaan = $user_data['perusahaan'] ?? '';
$unit       = $user_data['unit'] ?? '';

$tahun_awal   = (int) date('Y', strtotime($periode_mulai));
$tahun_ajaran = $tahun_awal . '/' . ($tahun_awal + 1);

// =========================================================================
// KELAS PDF: menggambar kop surat resmi UTB (kotak logo + info + No Dokumen dst)
// =========================================================================
class AbsensiPDF extends FPDF
{
    public $noDokumen = '';
    public $noRevisi = '';
    public $tglBerlaku = '';

    function HeaderBox()
    {
        $leftX = $this->lMargin;
        $topY = $this->tMargin;
        $fullW = $this->w - $this->lMargin - $this->rMargin;

        $logoW = 28;
        $metaW = 45;
        $midW = $fullW - $logoW - $metaW;
        $boxH = 28;
        $rowH = $boxH / 3;

        $this->Rect($leftX, $topY, $fullW, $boxH);
        $this->Line($leftX + $logoW, $topY, $leftX + $logoW, $topY + $boxH);
        $this->Line($leftX + $logoW + $midW, $topY, $leftX + $logoW + $midW, $topY + $boxH);
        $this->Line($leftX + $logoW + $midW, $topY + $rowH, $leftX + $fullW, $topY + $rowH);
        $this->Line($leftX + $logoW + $midW, $topY + 2 * $rowH, $leftX + $fullW, $topY + 2 * $rowH);
        $this->Line($leftX + $logoW, $topY + 2 * $rowH, $leftX + $logoW + $midW, $topY + 2 * $rowH);

        $this->SetXY($leftX, $topY);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell($logoW, $boxH, txt('UTB'), 0, 0, 'C');
        // Kalau Anda punya file logo (png/jpg), ganti baris di atas dengan:
        // $this->Image(__DIR__.'/logo-utb.png', $leftX+7, $topY+9, 14);

        $this->SetXY($leftX + $logoW, $topY + 2);
        $this->SetFont('Arial', 'B', 10.5);
        $this->Cell($midW, 5, txt('UNIVERSITAS TEKNOLOGI BANDUNG'), 0, 2, 'C');
        $this->SetFont('Arial', '', 8);
        $this->SetX($leftX + $logoW);
        $this->Cell($midW, 4, txt('Jl Soekarno Hatta No.378 Telp. (022) 522-4000'), 0, 2, 'C');
        $this->SetX($leftX + $logoW);
        $this->Cell($midW, 4, txt('Bandung-40235 Jawa Barat'), 0, 2, 'C');

        $this->SetXY($leftX + $logoW, $topY + 2 * $rowH);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell($midW, $rowH, txt('ABSENSI KERJA PRAKTIK'), 0, 0, 'C');

        $this->SetFont('Arial', '', 8);
        $metaX = $leftX + $logoW + $midW + 2;
        $this->SetXY($metaX, $topY + 2);
        $this->Cell($metaW - 4, $rowH - 4, txt('No. Dokumen : ' . $this->noDokumen), 0, 0, 'L');
        $this->SetXY($metaX, $topY + $rowH + 2);
        $this->Cell($metaW - 4, $rowH - 4, txt('No. Revisi   : ' . $this->noRevisi), 0, 0, 'L');
        $this->SetXY($metaX, $topY + 2 * $rowH + 2);
        $this->Cell($metaW - 4, $rowH - 4, txt('Tgl. Berlaku : ' . $this->tglBerlaku), 0, 0, 'L');

        $this->SetY($topY + $boxH + 4);
    }
}

// =========================================================================
// BUAT PDF
// =========================================================================
$pdf = new AbsensiPDF('P', 'mm', 'A4');
$bottomMargin = 20; // harus sama dengan angka kedua di SetAutoPageBreak() di bawah
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, $bottomMargin);
$pdf->AddPage();

$pdf->HeaderBox(); // isi noDokumen/noRevisi/tglBerlaku di atas kalau perlu diisi

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 6, txt('LEMBAR ABSENSI KEHADIRAN KERJA PRAKTIK (KP)'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, txt('SEMESTER GANJIL/GENAP *'), 0, 1, 'C');
$pdf->Cell(0, 5, txt('TAHUN AJARAN ' . $tahun_ajaran), 0, 1, 'C');
$pdf->Ln(3);

$pdf->SetFont('Arial', '', 10);
$fullW = $pdf->GetPageWidth() - 30;
$halfW = $fullW / 2;
$pdf->Cell($halfW, 6, txt('NIM        : ' . $nim), 0, 0);
$pdf->Cell($halfW, 6, txt('Perusahaan : ' . $perusahaan), 0, 1);
$pdf->Cell($halfW, 6, txt('Nama       : ' . $nama), 0, 0);
$pdf->Cell($halfW, 6, txt('Unit/Bagian : ' . $unit), 0, 1);
$pdf->Ln(2);

$colNo = 12; $colTgl = 38; $colParaf = 32;
$colUraian = $fullW - $colNo - $colTgl - $colParaf;

$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(219, 233, 245);
$pdf->Cell($colNo, 8, 'No', 1, 0, 'C', true);
$pdf->Cell($colTgl, 8, txt('Hari/Tanggal'), 1, 0, 'C', true);
$pdf->Cell($colUraian, 8, txt('Uraian Kegiatan pada Perusahaan'), 1, 0, 'C', true);
$pdf->Cell($colParaf, 8, txt('Paraf Pembina'), 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 8);
$no = 1;
if ($query_absen->num_rows > 0) {
    while ($row = $query_absen->fetch_assoc()) {
        $tgl = $row['tanggal'];
        $hari_indo = getHariIndo($tgl);
        $tgl_indo  = formatTanggalIndo($tgl);

        $catatan = !empty($row['catatan']) ? $row['catatan'] : '';
        $catatan = str_replace(["\r\n", "\r", "\n"], ' ', $catatan);

        $uraian = $catatan;
        if (!in_array($row['status'], ['Hadir', 'Lembur'])) {
            $uraian = strtoupper($row['status']) . ($catatan !== '' ? ' - ' . $catatan : '');
        }
        if ($uraian === '') {
            $uraian = '-';
        }
        $uraian = txt($uraian);

        // Hitung tinggi baris otomatis berdasar panjang teks uraian
        $lineH = 4;
        $nb = ceil($pdf->GetStringWidth($uraian) / ($colUraian - 2));
        $rowH = max(8, $nb * $lineH + 2);

        // Page-break manual: kalau baris ini bakal kepotong, pindah halaman dulu
        if ($pdf->GetY() + $rowH > $pdf->GetPageHeight() - $bottomMargin) {
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell($colNo, 8, 'No', 1, 0, 'C', true);
            $pdf->Cell($colTgl, 8, txt('Hari/Tanggal'), 1, 0, 'C', true);
            $pdf->Cell($colUraian, 8, txt('Uraian Kegiatan pada Perusahaan'), 1, 0, 'C', true);
            $pdf->Cell($colParaf, 8, txt('Paraf Pembina'), 1, 1, 'C', true);
            $pdf->SetFont('Arial', '', 8);
        }

        $x = $pdf->GetX(); $y = $pdf->GetY();
        $pdf->Cell($colNo, $rowH, $no, 1, 0, 'C');
        $pdf->Cell($colTgl, $rowH, txt($hari_indo . ', ' . $tgl_indo), 1, 0, 'C');

        $xUraian = $pdf->GetX(); $yUraian = $pdf->GetY();
        $pdf->MultiCell($colUraian, $lineH, $uraian, 0);
        $pdf->Rect($xUraian, $yUraian, $colUraian, $rowH);

        $pdf->SetXY($xUraian + $colUraian, $y);
        $pdf->Cell($colParaf, $rowH, '', 1, 1, 'C');
        $pdf->SetXY($x, $y + $rowH);

        $no++;
    }
} else {
    $pdf->Cell($fullW, 8, txt('Belum ada data absensi yang tercatat.'), 1, 1, 'C');
}

$pdf->Ln(4);
$pdf->SetFont('Arial', '', 8);
$pdf->MultiCell(0, 4, txt('Ket : *Coret salah satu, dilengkapi dengan tanda tangan Pembina kerja praktik dan stempel perusahaan.'));

$pdf->Ln(15);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, txt('Pembina Kerja Praktik'), 0, 1);
$pdf->Ln(15);
$pdf->Cell(0, 5, '(..................................................)', 0, 1);

$filename = "Absensi_KP_" . str_replace(' ', '_', ($user_data['nama_user'] ?? 'Mahasiswa')) . ".pdf";
$pdf->Output('D', $filename); // 'D' = auto-download. Ganti ke 'I' kalau mau tampil di tab browser dulu.
exit;