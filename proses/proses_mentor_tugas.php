<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/koneksi.php';

// Validasi Hak Akses Mentor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'mentor') {
    header("Location: ../login-mentor.php");
    exit;
}

$mentor_id   = $_SESSION['user_id'];
$nama_mentor = $_SESSION['nama_user'] ?? 'Mentor Bimbingan';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// =========================================================================
// 1. LOGIKA PENERIMAAN AKSI (POST) - KIRIM TUGAS BARU
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['kirim_tugas']) || isset($_POST['judul_tugas']))) {

    // Validasi CSRF Token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Sesi Berakhir',
            'message' => 'Permintaan tidak valid. Silakan muat ulang halaman.'
        ];
        header("Location: ../mentor/tugas.php");
        exit;
    }

    $judul_tugas      = trim($_POST['judul_tugas'] ?? '');
    $target_mahasiswa = trim($_POST['target_mahasiswa'] ?? 'all');
    $deskripsi        = trim($_POST['deskripsi'] ?? '');
    $tenggat          = trim($_POST['tenggat'] ?? '');

    if (empty($judul_tugas) || empty($tenggat)) {
        $_SESSION['alert'] = [
            'type' => 'warning',
            'title' => 'Input Wajib Kosong',
            'message' => 'Judul tugas dan tenggat pengumpulan wajib diisi!'
        ];
        header("Location: ../mentor/tugas.php");
        exit;
    }

    // Penanganan Unggah File Lampiran
    $nama_file_simpan = NULL;
    if (isset($_FILES['file_lampiran']) && $_FILES['file_lampiran']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['file_lampiran']['tmp_name'];
        $file_name = $_FILES['file_lampiran']['name'];
        $file_size = $_FILES['file_lampiran']['size'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_ext = ['pdf', 'docx', 'xlsx', 'pptx'];
        $max_size    = 10 * 1024 * 1024; // 10 MB

        if (!in_array($file_ext, $allowed_ext)) {
            $_SESSION['alert'] = [
                'type' => 'error',
                'title' => 'Format File Ditolak',
                'message' => 'Hanya format PDF, DOCX, XLSX, dan PPTX yang diperbolehkan.'
            ];
            header("Location: ../mentor/tugas.php");
            exit;
        }

        if ($file_size > $max_size) {
            $_SESSION['alert'] = [
                'type' => 'error',
                'title' => 'File Terlalu Besar',
                'message' => 'Ukuran file lampiran tidak boleh melebihi 10MB.'
            ];
            header("Location: ../mentor/tugas.php");
            exit;
        }

        $upload_dir = __DIR__ . '/../uploads/tugas_mentor/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $nama_file_simpan = 'tugas_' . uniqid() . '.' . $file_ext;
        if (!move_uploaded_file($file_tmp, $upload_dir . $nama_file_simpan)) {
            $_SESSION['alert'] = [
                'type' => 'error',
                'title' => 'Gagal Upload File',
                'message' => 'Terjadi kesalahan server saat menyimpan file lampiran.'
            ];
            header("Location: ../mentor/tugas.php");
            exit;
        }
    }

    // Pastikan Struktur Tabel Siap
    $conn->query("CREATE TABLE IF NOT EXISTS `tugas` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `mentor_id` INT NOT NULL,
        `target_user_id` INT NULL,
        `judul_tugas` VARCHAR(255) NOT NULL,
        `deskripsi` TEXT NULL,
        `file_lampiran` VARCHAR(255) NULL,
        `tenggat` DATETIME NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $conn->query("CREATE TABLE IF NOT EXISTS `tugas_detail` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `tugas_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `status_approval` VARCHAR(50) DEFAULT 'Belum Ada Berkas',
        `file_jawaban` VARCHAR(255) NULL,
        `catatan_mentor` TEXT NULL,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Tentukan Target Mahasiswa
    $target_user_id = NULL;
    if ($target_mahasiswa !== 'all') {
        $stmt_nim = $conn->prepare("SELECT id FROM users WHERE nim = ? LIMIT 1");
        if ($stmt_nim) {
            $stmt_nim->bind_param("s", $target_mahasiswa);
            $stmt_nim->execute();
            $res_nim = $stmt_nim->get_result();
            if ($res_nim && $res_nim->num_rows === 1) {
                $target_user_id = $res_nim->fetch_assoc()['id'];
            }
            $stmt_nim->close();
        }
    }

    // Insert Data Tugas Utama
    $stmt_tugas = $conn->prepare("INSERT INTO tugas (mentor_id, target_user_id, judul_tugas, deskripsi, file_lampiran, tenggat) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt_tugas) {
        $stmt_tugas->bind_param("iissss", $mentor_id, $target_user_id, $judul_tugas, $deskripsi, $nama_file_simpan, $tenggat);

        if ($stmt_tugas->execute()) {
            $tugas_id = $stmt_tugas->insert_id;

            // Inisialisasi Record Detail Tugas
            if ($target_user_id !== NULL) {
                $stmt_detail = $conn->prepare("INSERT INTO tugas_detail (tugas_id, user_id, status_approval) VALUES (?, ?, 'Belum Ada Berkas')");
                if ($stmt_detail) {
                    $stmt_detail->bind_param("ii", $tugas_id, $target_user_id);
                    $stmt_detail->execute();
                    $stmt_detail->close();
                }
            } else {
                $query_users = $conn->query("SELECT id FROM users");
                if ($query_users && $query_users->num_rows > 0) {
                    $stmt_detail = $conn->prepare("INSERT INTO tugas_detail (tugas_id, user_id, status_approval) VALUES (?, ?, 'Belum Ada Berkas')");
                    if ($stmt_detail) {
                        while ($u = $query_users->fetch_assoc()) {
                            $stmt_detail->bind_param("ii", $tugas_id, $u['id']);
                            $stmt_detail->execute();
                        }
                        $stmt_detail->close();
                    }
                }
            }

            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => 'Tugas Berhasil Terkirim!',
                'message' => 'Instruksi tugas baru telah didistribusikan ke portal mahasiswa.'
            ];
        } else {
            $_SESSION['alert'] = [
                'type' => 'error',
                'title' => 'Gagal Mengirim Tugas',
                'message' => 'Terjadi kesalahan saat menyimpan tugas ke basis data.'
            ];
        }
        $stmt_tugas->close();
    }

    header("Location: ../mentor/tugas.php");
    exit;
}

// =========================================================================
// 2. LOGIKA PENYEDIAAN DATA VIEW (GET) - DROPDOWN & TABEL
// =========================================================================

// A. Ambil Daftar Mahasiswa untuk Dropdown Pilihan
$mahasiswa_list = [];
$q_mhs = $conn->query("SELECT id, nama_user, nim FROM users ORDER BY nama_user ASC");
if ($q_mhs && $q_mhs->num_rows > 0) {
    while ($m = $q_mhs->fetch_assoc()) {
        $mahasiswa_list[] = $m;
    }
}

// B. Ambil Riwayat Penugasan yang Pernah Dibuat Mentor Ini
$tugas_terdistribusi = [];
$q_tugas = $conn->query("
    SELECT t.*, u.nama_user, u.nim 
    FROM tugas t 
    LEFT JOIN users u ON t.target_user_id = u.id 
    WHERE t.mentor_id = '$mentor_id' 
    ORDER BY t.created_at DESC
");
if ($q_tugas && $q_tugas->num_rows > 0) {
    while ($row = $q_tugas->fetch_assoc()) {
        $tugas_terdistribusi[] = $row;
    }
}
?>