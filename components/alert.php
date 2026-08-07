<?php
// 1. SISTEM MODERN: Menangkap pesan pop-up dari sesi Backend
if (isset($_SESSION['alert'])): 
    $alert_type    = $_SESSION['alert']['type'] ?? 'info';
    $alert_title   = $_SESSION['alert']['title'] ?? 'Informasi';
    $alert_message = $_SESSION['alert']['message'] ?? '';
?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '<?php echo $alert_type; ?>',
                title: '<?php echo $alert_title; ?>',
                text: '<?php echo addslashes($alert_message); ?>',
                confirmButtonColor: '#2563eb',
                confirmButtonText: 'Oke, Mengerti',
                backdrop: `rgba(0,0,0,0.4)`
            });
        });
    </script>
<?php 
    unset($_SESSION['alert']); // Hapus supaya tidak muncul terus saat refresh
endif; 

// 2. SISTEM LAMA (LEGACY): Menjaga pop-up mahasiswa milikmu agar tidak rusak
if (!empty($pesan_alert) && !isset($_SESSION['alert'])): 
?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let pesan = "<?php echo addslashes($pesan_alert); ?>";
            let tipeIcon = "success"; 
            let judul = "Berhasil!";

            if (pesan.toLowerCase().includes("gagal") || pesan.toLowerCase().includes("salah")) {
                tipeIcon = "error";
                judul = "Oops, Terjadi Kesalahan!";
            } else if (pesan.toLowerCase().includes("lembur") || pesan.toLowerCase().includes("pulang") || pesan.toLowerCase().includes("izin") || pesan.toLowerCase().includes("sakit")) {
                tipeIcon = "info";
                judul = "Informasi";
            }

            Swal.fire({
                icon: tipeIcon,
                title: judul,
                text: pesan,
                confirmButtonColor: '#2563eb',
                confirmButtonText: 'Oke, Mengerti',
                backdrop: `rgba(0,0,0,0.4)`
            });
        });
    </script>
<?php endif; ?>