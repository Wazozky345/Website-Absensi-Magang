<?php
require_once __DIR__ . '/../config/sesi.php';
require_once __DIR__ . '/../config/koneksi.php';

// Validasi Hak Akses Mentor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'mentor') {
    header("Location: ../login-mentor.php");
    exit;
}

$mentor_id   = $_SESSION['user_id'];
$nama_mentor = $_SESSION['nama_user'] ?? 'Mentor Bimbingan';
$jabatan_mentor = $_SESSION['jabatan'] ?? 'Pembimbing Lapangan';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$upload_dir = __DIR__ . '/../uploads/tugas_mentor/';

// =========================================================================
// 1. LOGIKA PENERIMAAN AKSI (POST) - CRUD & TARIK FILE TUGAS
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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

    // A. AKSI TARIK FILE LAMPIRAN SAJA (HAPUS BERKAS FISIK)
    if (isset($_POST['tarik_file_id'])) {
        $tugas_id = intval($_POST['tarik_file_id']);

        $stmt = $conn->prepare("SELECT file_lampiran FROM tugas WHERE id = ? AND mentor_id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $tugas_id, $mentor_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $row = $res->fetch_assoc()) {
                if (!empty($row['file_lampiran'])) {
                    $file_path = $upload_dir . $row['file_lampiran'];
                    if (file_exists($file_path)) {
                        @unlink($file_path);
                    }
                }
            }
            $stmt->close();
        }

        $stmt_upd = $conn->prepare("UPDATE tugas SET file_lampiran = NULL WHERE id = ? AND mentor_id = ?");
        if ($stmt_upd) {
            $stmt_upd->bind_param("ii", $tugas_id, $mentor_id);
            if ($stmt_upd->execute()) {
                $_SESSION['alert'] = [
                    'type' => 'success',
                    'title' => 'Berkas Berhasil Ditarik!',
                    'message' => 'File lampiran telah dihapus dari server dan tidak dapat diakses lagi oleh mahasiswa.'
                ];
            }
            $stmt_upd->close();
        }

        header("Location: ../mentor/tugas.php");
        exit;
    }

    // B. AKSI UPLOAD / GANTI FILE LAMPIRAN UNTUK TUGAS YANG ADA
    if (isset($_POST['upload_lampiran_tugas']) || isset($_POST['update_lampiran_id'])) {
        $tugas_id = intval($_POST['update_lampiran_id'] ?? 0);

        if ($tugas_id > 0 && isset($_FILES['file_lampiran']) && $_FILES['file_lampiran']['error'] === UPLOAD_ERR_OK) {
            $file_tmp  = $_FILES['file_lampiran']['tmp_name'];
            $file_name = $_FILES['file_lampiran']['name'];
            $file_size = $_FILES['file_lampiran']['size'];
            $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_ext = ['pdf', 'docx', 'xlsx', 'pptx'];
            if (!in_array($file_ext, $allowed_ext) || $file_size > 10 * 1024 * 1024) {
                $_SESSION['alert'] = [
                    'type' => 'error',
                    'title' => 'Gagal Upload',
                    'message' => 'Format harus PDF/DOCX/XLSX/PPTX dan maksimal 10MB.'
                ];
                header("Location: ../mentor/tugas.php");
                exit;
            }

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Hapus file lama jika ada
            $stmt_old = $conn->prepare("SELECT file_lampiran FROM tugas WHERE id = ? AND mentor_id = ?");
            if ($stmt_old) {
                $stmt_old->bind_param("ii", $tugas_id, $mentor_id);
                $stmt_old->execute();
                $res_old = $stmt_old->get_result();
                if ($res_old && $row_old = $res_old->fetch_assoc()) {
                    if (!empty($row_old['file_lampiran']) && file_exists($upload_dir . $row_old['file_lampiran'])) {
                        @unlink($upload_dir . $row_old['file_lampiran']);
                    }
                }
                $stmt_old->close();
            }

            $new_filename = 'tugas_' . uniqid() . '.' . $file_ext;
            if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                $stmt_upd = $conn->prepare("UPDATE tugas SET file_lampiran = ? WHERE id = ? AND mentor_id = ?");
                if ($stmt_upd) {
                    $stmt_upd->bind_param("sii", $new_filename, $tugas_id, $mentor_id);
                    $stmt_upd->execute();
                    $stmt_upd->close();

                    $_SESSION['alert'] = [
                        'type' => 'success',
                        'title' => 'Berkas Diperbarui!',
                        'message' => 'File lampiran baru berhasil disimpan.'
                    ];
                }
            }
        }

        header("Location: ../mentor/tugas.php");
        exit;
    }

    // C. AKSI HAPUS SELURUH PENUGASAN TOTAL
    if (isset($_POST['hapus_tugas_id'])) {
        $tugas_id = intval($_POST['hapus_tugas_id']);

        if ($tugas_id > 0) {
            $stmt = $conn->prepare("SELECT file_lampiran FROM tugas WHERE id = ? AND mentor_id = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $tugas_id, $mentor_id);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $row = $res->fetch_assoc()) {
                    if (!empty($row['file_lampiran']) && file_exists($upload_dir . $row['file_lampiran'])) {
                        @unlink($upload_dir . $row['file_lampiran']);
                    }
                }
                $stmt->close();
            }

            $conn->query("DELETE FROM tugas_detail WHERE tugas_id = '$tugas_id'");
            $conn->query("DELETE FROM tugas WHERE id = '$tugas_id' AND mentor_id = '$mentor_id'");

            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => 'Penugasan Dihapus!',
                'message' => 'Tugas berhasil dihapus total dari sistem.'
            ];
        }

        header("Location: ../mentor/tugas.php");
        exit;
    }

    // D. AKSI KIRIM PENUGASAN BARU
    if (isset($_POST['kirim_tugas']) || isset($_POST['judul_tugas'])) {

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

        $nama_file_simpan = NULL;
        if (isset($_FILES['file_lampiran']) && $_FILES['file_lampiran']['error'] === UPLOAD_ERR_OK) {
            $file_tmp  = $_FILES['file_lampiran']['tmp_name'];
            $file_name = $_FILES['file_lampiran']['name'];
            $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $nama_file_simpan = 'tugas_' . uniqid() . '.' . $file_ext;
            move_uploaded_file($file_tmp, $upload_dir . $nama_file_simpan);
        }

        // Tentukan Target User ID Mahasiswa
        $target_user_id = NULL;
        if ($target_mahasiswa !== 'all') {
            $stmt_nim = $conn->prepare("SELECT id FROM users WHERE nim = ? OR id = ? LIMIT 1");
            if ($stmt_nim) {
                $stmt_nim->bind_param("ss", $target_mahasiswa, $target_mahasiswa);
                $stmt_nim->execute();
                $res_nim = $stmt_nim->get_result();
                if ($res_nim && $res_nim->num_rows === 1) {
                    $target_user_id = $res_nim->fetch_assoc()['id'];
                }
                $stmt_nim->close();
            }
        }

        // Insert Data Tugas
        $stmt_tugas = $conn->prepare("INSERT INTO tugas (mentor_id, target_user_id, judul_tugas, deskripsi, file_lampiran, tenggat) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt_tugas) {
            $stmt_tugas->bind_param("iissss", $mentor_id, $target_user_id, $judul_tugas, $deskripsi, $nama_file_simpan, $tenggat);

            if ($stmt_tugas->execute()) {
                $tugas_id = $stmt_tugas->insert_id;

                if ($target_user_id !== NULL) {
                    $conn->query("INSERT INTO tugas_detail (tugas_id, user_id, status_approval) VALUES ('$tugas_id', '$target_user_id', 'Belum Ada Berkas')");
                } else {
                    // FIX: Hapus query pengecekan kolom role yang menyebabkan error
                    $query_users = $conn->query("SELECT id FROM users");
                    if ($query_users && $query_users->num_rows > 0) {
                        while ($u = $query_users->fetch_assoc()) {
                            $uid = $u['id'];
                            $conn->query("INSERT INTO tugas_detail (tugas_id, user_id, status_approval) VALUES ('$tugas_id', '$uid', 'Belum Ada Berkas')");
                        }
                    }
                }

                $_SESSION['alert'] = [
                    'type' => 'success',
                    'title' => 'Tugas Berhasil Terkirim!',
                    'message' => 'Instruksi tugas baru telah didistribusikan.'
                ];
            }
            $stmt_tugas->close();
        }

        header("Location: ../mentor/tugas.php");
        exit;
    }
}

// =========================================================================
// 2. LOGIKA PENYEDIAAN DATA VIEW (GET) - DROPDOWN & TABEL
// =========================================================================

// A. Query Ambil Semua Data Mahasiswa untuk Option Dropdown
$mahasiswa_list = [];
// FIX: Hapus pengecekan kolom role yang menyebabkan error
$q_mhs = $conn->query("SELECT id, nama_user, nim FROM users ORDER BY nama_user ASC");
if ($q_mhs && $q_mhs->num_rows > 0) {
    while ($m = $q_mhs->fetch_assoc()) {
        $mahasiswa_list[] = $m;
    }
}

// B. Ambil Riwayat Penugasan Terdistribusi
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