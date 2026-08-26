<?php
// ==========================================================
// IP-BASED RATE LIMITER & PENALTY BOX (ANTI-DDOS LAYER 7)
// ==========================================================
$batas_request = 20;        // Maksimal request (Bisa diganti ke 3 untuk testing)
$jeda_waktu = 10;           // Dalam rentang 10 detik
$waktu_hukuman = 86400;     // HUKUMAN: Diblokir 86400 detik (24 jam)

// 1. TANGKAP IP ADDRESS PENGUNJUNG (MENEMBUS PROXY/CLOUDFLARE HOSTING)
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip_address = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    // Terkadang X_FORWARDED_FOR mengembalikan banyak IP, kita ambil yang pertama (IP asli)
    $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ip_address = trim($ip_list[0]);
} else {
    $ip_address = $_SERVER['REMOTE_ADDR'];
}

// Ubah IP menjadi hash MD5 agar nama file aman dan rapi
$nama_file = md5($ip_address) . '.json';

// Tentukan lokasi folder untuk menyimpan catatan IP
$dir_log = __DIR__ . '/ddos_logs/';
$file_path = $dir_log . $nama_file;

// 2. BUAT FOLDER OTOMATIS JIKA BELUM ADA
if (!is_dir($dir_log)) {
    mkdir($dir_log, 0755, true);
}

// --- FITUR BARU: SMART GARBAGE COLLECTOR ---
// Berjalan 100% pada setiap request untuk memastikan log tidak nyampah.
$files = glob($dir_log . '*.json');
$waktu_sekarang = time();

if ($files) {
    foreach ($files as $file) {
        if (is_file($file)) {
            // Lewati pengecekan untuk file IP milik user yang sedang mengakses detik ini
            // (karena akan ditulis ulang di akhir script)
            if ($file === $file_path) continue;

            $json_data = file_get_contents($file);
            $content = json_decode($json_data, true);
            
            if (is_array($content)) {
                // 1. User Normal: Hapus jika file sudah tidak diupdate lebih dari 5 menit (300 detik)
                if (isset($content['banned_until']) && $content['banned_until'] == 0) {
                    if (($waktu_sekarang - filemtime($file)) > 300) {
                        @unlink($file); // Tambahkan '@' agar error permission senyap (tidak merusak UI)
                    }
                }
                // 2. User Diblokir: Hapus hanya jika masa hukuman 24 jam sudah selesai
                elseif (isset($content['banned_until']) && $content['banned_until'] > 0 && $waktu_sekarang > $content['banned_until']) {
                    @unlink($file);
                }
            } else {
                // 3. Jika file korup/kosong tapi tersisa di server, langsung bersihkan
                @unlink($file);
            }
        }
    }
}
// ---------------------------------------------------

// 3. BACA CATATAN IP DARI FILE JSON
if (file_exists($file_path)) {
    // Ambil log yang sudah ada
    $log_data = json_decode(file_get_contents($file_path), true);
} else {
    // Jika IP baru pertama kali datang, buat data awal
    $log_data = [
        'request_count' => 0,
        'waktu_awal' => time(),
        'banned_until' => 0
    ];
}

$waktu_sekarang = time();

// 4. EKSEKUSI HUKUMAN (JIKA MASIH DALAM MASA BLOKIR)
if ($log_data['banned_until'] > $waktu_sekarang) {
    $sisa_waktu = $log_data['banned_until'] - $waktu_sekarang;
    
    // Format sisa waktu ke Jam:Menit:Detik
    $sisa_jam = floor($sisa_waktu / 3600);
    $sisa_menit = floor(($sisa_waktu % 3600) / 60);
    $sisa_detik = $sisa_waktu % 60;
    $format_waktu = "{$sisa_jam} jam {$sisa_menit} menit {$sisa_detik} detik";

    header('HTTP/1.1 429 Too Many Requests');
    die("<h1 style='font-family: sans-serif; text-align: center; margin-top: 20%; color: #ef4444;'>
            ⚠️ Akses Diblokir! (Error 429) <br>
            <span style='font-size: 16px; color: #6b7280;'>
                IP Anda (<b>{$ip_address}</b>) terdeteksi melakukan spam. <br>
                Silakan tunggu <b>{$format_waktu}</b> lagi untuk mengakses web ini.
            </span>
         </h1>");
}

// 5. HITUNG JEDA WAKTU UNTUK PENGUNJUNG NORMAL
$waktu_berlalu = $waktu_sekarang - $log_data['waktu_awal'];

if ($waktu_berlalu < $jeda_waktu) {
    $log_data['request_count']++;
    
    // JIKA NGE-SPAM MELEBIHI BATAS -> PENJARA 24 JAM!
    if ($log_data['request_count'] > $batas_request) {
        $log_data['banned_until'] = $waktu_sekarang + $waktu_hukuman;
        
        // Simpan palu hakim ke file sebelum memutus koneksi
        file_put_contents($file_path, json_encode($log_data));
        
        header('HTTP/1.1 429 Too Many Requests');
        die("<h1 style='font-family: sans-serif; text-align: center; margin-top: 20%; color: #ef4444;'>
                ⚠️ Terdeteksi Serangan! (Error 429) <br>
                <span style='font-size: 16px; color: #6b7280;'>IP Anda diblokir selama 24 jam tanpa ampun.</span>
             </h1>");
    }
} else {
    // Jika jeda waktu aman (lebih dari 10 detik tanpa melanggar), reset ulang hitungan
    $log_data['request_count'] = 1;
    $log_data['waktu_awal'] = $waktu_sekarang;
}

// 6. SIMPAN LOG AKTIVITAS TERBARU KE FILE
file_put_contents($file_path, json_encode($log_data));
?>