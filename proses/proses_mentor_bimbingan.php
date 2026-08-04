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

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mentor_id   = $_SESSION['user_id'] ?? 1;
$nama_mentor = $_SESSION['nama_user'] ?? 'Dr. Alvin Nurfaiz, M.T.';

// =========================================================================
// 1. LOGIKA PENERIMAAN AKSI (POST) - CRUD BIMBINGAN
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Sesi Berakhir',
            'message' => 'CSRF Token Invalid. Silakan muat ulang halaman.'
        ];
        header("Location: ../mentor/bimbingan.php");
        exit;
    }

    $action       = $_POST['action'] ?? 'save';
    $bimbingan_id = intval($_POST['bimbingan_id'] ?? 0);

    if ($action === 'delete' && $bimbingan_id > 0) {
        $stmt_del = $conn->prepare("DELETE FROM bimbingan WHERE id = ? AND mentor_id = ?");
        if ($stmt_del) {
            $stmt_del->bind_param("ii", $bimbingan_id, $mentor_id);
            if ($stmt_del->execute()) {
                $_SESSION['alert'] = [
                    'type' => 'success',
                    'title' => 'Berhasil Dihapus',
                    'message' => 'Jadwal bimbingan telah dihapus dari kalender.'
                ];
            }
            $stmt_del->close();
        }
        header("Location: ../mentor/bimbingan.php");
        exit;
    }

    $target_user_id = intval($_POST['user_id'] ?? 0);
    $nim_input      = trim($_POST['nim_mahasiswa'] ?? '');
    $tanggal_waktu  = trim($_POST['waktu_sesi'] ?? date('Y-m-d H:i:s'));
    $topik          = trim($_POST['topik'] ?? '');
    $metode         = trim($_POST['metode'] ?? 'Tatap Muka');
    $status_input   = trim($_POST['status_tipe'] ?? 'Terjadwal');
    $catatan_revisi = trim($_POST['catatan'] ?? '');

    $status = 'Terjadwal';
    if ($status_input === 'Revisi') $status = 'Revisi';
    elseif ($status_input === 'Selesai') $status = 'Selesai';

    if (!in_array($metode, ['Tatap Muka', 'Online'])) $metode = 'Tatap Muka';

    if ($target_user_id <= 0 && !empty($nim_input)) {
        $stmt_u = $conn->prepare("SELECT id FROM users WHERE nim = ? LIMIT 1");
        if ($stmt_u) {
            $stmt_u->bind_param("s", $nim_input);
            $stmt_u->execute();
            $res_u = $stmt_u->get_result();
            if ($res_u && $res_u->num_rows === 1) $target_user_id = $res_u->fetch_assoc()['id'];
            $stmt_u->close();
        }
    }

    if ($target_user_id <= 0) $target_user_id = 1; 

    if (empty($topik) || empty($catatan_revisi)) {
        $_SESSION['alert'] = [
            'type' => 'warning',
            'title' => 'Form Tidak Lengkap',
            'message' => 'Topik dan Catatan Revisi wajib diisi!'
        ];
        header("Location: ../mentor/bimbingan.php");
        exit;
    }

    if ($bimbingan_id > 0) {
        $stmt_upd = $conn->prepare("UPDATE bimbingan SET user_id = ?, tanggal_waktu = ?, topik = ?, metode = ?, catatan_revisi = ?, status = ? WHERE id = ? AND mentor_id = ?");
        if ($stmt_upd) {
            $stmt_upd->bind_param("isssssii", $target_user_id, $tanggal_waktu, $topik, $metode, $catatan_revisi, $status, $bimbingan_id, $mentor_id);
            if ($stmt_upd->execute()) {
                $_SESSION['alert'] = [
                    'type' => 'success',
                    'title' => 'Berhasil Diperbarui',
                    'message' => 'Data bimbingan & revisi berhasil diperbarui.'
                ];
            } else {
                $_SESSION['alert'] = [
                    'type' => 'error',
                    'title' => 'Gagal Memperbarui',
                    'message' => 'Terjadi kesalahan server saat memperbarui data.'
                ];
            }
            $stmt_upd->close();
        }
    } else {
        $stmt_ins = $conn->prepare("INSERT INTO bimbingan (mentor_id, user_id, tanggal_waktu, topik, metode, catatan_revisi, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt_ins) {
            $stmt_ins->bind_param("iisssss", $mentor_id, $target_user_id, $tanggal_waktu, $topik, $metode, $catatan_revisi, $status);
            if ($stmt_ins->execute()) {
                $_SESSION['alert'] = [
                    'type' => 'success',
                    'title' => 'Bimbingan Disimpan!',
                    'message' => 'Jadwal bimbingan & catatan revisi berhasil didaftarkan.'
                ];
            } else {
                $_SESSION['alert'] = [
                    'type' => 'error',
                    'title' => 'Gagal Menyimpan',
                    'message' => 'Terjadi kesalahan saat menyimpan bimbingan.'
                ];
            }
            $stmt_ins->close();
        }
    }

    header("Location: ../mentor/bimbingan.php");
    exit;
}

// =========================================================================
// 2. LOGIKA PENYEDIAAN DATA VIEW (GET)
// =========================================================================

// A. KALENDER
$bulan_aktif = isset($_GET['bulan']) ? sprintf('%02d', intval($_GET['bulan'])) : '08';
$tahun_aktif = '2026';
$nama_bulan_map = ['07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober'];
if (!isset($nama_bulan_map[$bulan_aktif])) $bulan_aktif = '08';

$first_day_timestamp = strtotime("$tahun_aktif-$bulan_aktif-01");
$total_hari          = date('t', $first_day_timestamp);
$day_of_week         = date('N', $first_day_timestamp);
$slot_kosong         = $day_of_week - 1;

// B. DAFTAR MAHASISWA
$mahasiswa_list = [];
$q_mhs = $conn->query("SELECT id, nama_user, nim FROM users ORDER BY nama_user ASC");
if ($q_mhs && $q_mhs->num_rows > 0) {
    while ($m = $q_mhs->fetch_assoc()) $mahasiswa_list[] = $m;
}

// C. DATA KALENDER BIMBINGAN
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

// D. DATA TIMELINE (DIKELOMPOKKAN PER MAHASISWA)
$timeline_data = [];
$q_timeline = $conn->query("
    SELECT user_id, tanggal_waktu, topik, catatan_revisi, status 
    FROM bimbingan 
    WHERE mentor_id = '$mentor_id' 
    ORDER BY tanggal_waktu DESC
");

if ($q_timeline && $q_timeline->num_rows > 0) {
    while ($row = $q_timeline->fetch_assoc()) {
        $uid = $row['user_id'];
        $timeline_data[$uid][] = [
            'tanggal' => date('d M Y', strtotime($row['tanggal_waktu'])),
            'topik'   => htmlspecialchars($row['topik']),
            'catatan' => htmlspecialchars($row['catatan_revisi'] ?? ''),
            'status'  => $row['status']
        ];
    }
}
$json_timeline = json_encode($timeline_data); // Lempar ke JS
?>