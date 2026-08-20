<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/koneksi.php';

// 1. Generasi CSRF Token jika belum ada
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 2. Bersihkan variabel sesi jika mengakses dari tombol switch/reset
if (isset($_GET['switch']) || isset($_GET['reset'])) {
    unset($_SESSION['user_id'], $_SESSION['nama_user'], $_SESSION['nim'], $_SESSION['kelas'], $_SESSION['konsentrasi'], $_SESSION['role']);
}

// 3. Jika mahasiswa sudah login dan tidak dalam mode switch, alihkan langsung ke dashboard
if (!isset($_GET['switch']) && isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'mahasiswa') {
    header("Location: mahasiswa/dashboard_mahasiswa.php");
    exit;
}

// 4. Mengambil daftar seluruh mahasiswa bimbingan dari database
$query_users = "SELECT id, nama_user, nim, kelas, konsentrasi, email FROM users ORDER BY id ASC";
$result_users = $conn->query($query_users);
$daftar_mahasiswa = [];
if ($result_users && $result_users->num_rows > 0) {
    while ($row = $result_users->fetch_assoc()) {
        $daftar_mahasiswa[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Mahasiswa - UTB Tracker</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>

<body class="flex min-h-screen items-center justify-center bg-[#F4F7FE] text-gray-800 select-none p-4 relative">

    <!-- TOP NAVIGATION BAR -->
    <header class="fixed top-0 left-0 right-0 p-4 sm:p-6 flex justify-between items-center z-30">
        <a href="landing_page_utama.php" class="inline-flex items-center gap-2 px-4 py-2 bg-white/80 backdrop-blur-md border border-gray-200 text-gray-600 hover:text-blue-600 hover:border-blue-200 font-bold text-xs rounded-full shadow-sm hover:shadow-md transition-all duration-200 hover:scale-105">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Ganti Peran (Halaman Utama)
        </a>

        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-blue-500 animate-pulse"></span>
            <span class="text-xs font-bold text-gray-500 tracking-wider uppercase">Portal Mahasiswa</span>
        </div>
    </header>

    <div class="w-full max-w-4xl p-4 md:p-8 relative pt-16">

        <!-- HEADER BRANDING UTAMA -->
        <div class="text-center mb-10">
            <div class="inline-flex items-center gap-3 text-blue-600 mb-2">
                <div class="w-10 h-10 bg-blue-600 text-white rounded-xl flex items-center justify-center shadow-md shadow-blue-200">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
                    </svg>
                </div>
                <h1 class="text-3xl font-extrabold tracking-tight">UTB Tracker</h1>
            </div>
            <p class="text-gray-500 font-medium text-sm">Sistem Absensi & Logbook Magang Mahasiswa</p>
        </div>

        <!-- TAHAP 1: PILIH AKUN MAHASISWA -->
        <div id="step-select-account" class="transition-all duration-500 ease-in-out">
            <h2 class="text-lg font-bold text-center mb-8 text-gray-700">Siapa yang sedang presensi?</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5 md:gap-6">
                
                <?php if (!empty($daftar_mahasiswa)): ?>
                    <?php foreach ($daftar_mahasiswa as $mhs): 
                        $bg_avatar = (stristr($mhs['nama_user'], 'Bayuga') !== false) ? 'ecfdf5' : 'ebf4ff';
                        $color_avatar = (stristr($mhs['nama_user'], 'Bayuga') !== false) ? '059669' : '2563eb';
                        $nama_escaped = htmlspecialchars(addslashes($mhs['nama_user']), ENT_QUOTES, 'UTF-8');
                        $nim_escaped  = htmlspecialchars($mhs['nim'], ENT_QUOTES, 'UTF-8');
                    ?>
                        <!-- KARTU MAHASISWA -->
                        <div onclick="showPinForm('<?php echo $nama_escaped; ?>', '<?php echo $nim_escaped; ?>')" class="bg-white/95 backdrop-blur-md rounded-3xl p-6 shadow-sm border border-white cursor-pointer hover:shadow-xl hover:-translate-y-1 transition text-center group">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($mhs['nama_user']); ?>&background=<?php echo $bg_avatar; ?>&color=<?php echo $color_avatar; ?>&size=128" alt="Avatar" class="w-20 h-20 rounded-full mx-auto mb-3 border-4 border-white shadow-sm group-hover:border-blue-100 transition">
                            <h3 class="text-base font-bold text-gray-800 truncate"><?php echo htmlspecialchars($mhs['nama_user']); ?></h3>
                            <p class="text-blue-600 font-semibold text-xs mt-1">NIM: <?php echo htmlspecialchars($mhs['nim']); ?></p>
                            <p class="text-gray-400 text-[11px] mt-1 font-medium truncate"><?php echo htmlspecialchars($mhs['kelas']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- KARTU TAMBAH / DAFTAR AKUN BARU -->
                <a href="register_mahasiswa.php" class="bg-white/50 border-2 border-dashed border-gray-300 hover:border-blue-500 rounded-3xl p-6 flex flex-col items-center justify-center text-gray-400 hover:text-blue-600 transition group cursor-pointer hover:-translate-y-1 min-h-[190px]">
                    <div class="w-12 h-12 rounded-2xl bg-gray-100 group-hover:bg-blue-50 flex items-center justify-center mb-2 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                    <span class="font-bold text-xs">Daftar Akun Baru</span>
                </a>

            </div>
        </div>

        <!-- TAHAP 2: INPUT PIN 4 DIGIT -->
        <div id="step-pin-entry" class="hidden transition-all duration-500 ease-in-out absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md mt-10 p-4 z-20">
            <div class="bg-white/95 backdrop-blur-md rounded-3xl p-8 shadow-xl border border-white text-center relative">

                <button type="button" onclick="backToSelect()" class="absolute top-6 left-6 text-gray-400 hover:text-gray-700 transition" title="Kembali pilih akun">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </button>

                <h2 class="text-lg font-bold text-gray-800 mb-1">Masukkan PIN Keamanan</h2>
                <p id="selected-name" class="text-blue-600 font-bold mb-1 text-sm">Nama User</p>
                <p id="selected-nim" class="text-xs text-gray-400 font-semibold mb-8">NIM: -</p>

                <form id="form-login" method="POST" action="proses/proses_login_mahasiswa.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    <input type="hidden" name="submit_login_mahasiswa" value="1">
                    <input type="hidden" name="nim" id="input-nim">

                    <div class="relative flex justify-center items-center gap-6 mb-10 w-max mx-auto">
                        <input type="tel" name="pin" id="real-pin-input" maxlength="4" autocomplete="off" autofocus class="absolute inset-0 w-full h-full opacity-0 cursor-text z-10">

                        <div class="w-6 h-8 flex items-center justify-center">
                            <div class="pin-dot w-4 h-4 rounded-full bg-gray-300"></div>
                            <span class="pin-digit hidden text-3xl font-bold text-gray-800"></span>
                        </div>
                        <div class="w-6 h-8 flex items-center justify-center">
                            <div class="pin-dot w-4 h-4 rounded-full bg-gray-300"></div>
                            <span class="pin-digit hidden text-3xl font-bold text-gray-800"></span>
                        </div>
                        <div class="w-6 h-8 flex items-center justify-center">
                            <div class="pin-dot w-4 h-4 rounded-full bg-gray-300"></div>
                            <span class="pin-digit hidden text-3xl font-bold text-gray-800"></span>
                        </div>
                        <div class="w-6 h-8 flex items-center justify-center">
                            <div class="pin-dot w-4 h-4 rounded-full bg-gray-300"></div>
                            <span class="pin-digit hidden text-3xl font-bold text-gray-800"></span>
                        </div>

                        <button type="button" id="eye-btn" class="absolute -right-16 z-20 p-2 text-gray-400 hover:text-blue-600 transition touch-none cursor-pointer">
                            <svg id="eye-icon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>

                    <button type="button" onclick="loginSimulasi()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-2xl shadow-lg shadow-blue-200 transition text-sm">
                        Buka Dashboard Mahasiswa
                    </button>
                </form>
            </div>
        </div>

    </div>

    <?php 
    if (file_exists('components/alert.php')) {
        include 'components/alert.php'; 
    }
    ?>

    <script>
        const stepSelect = document.getElementById('step-select-account');
        const stepPin = document.getElementById('step-pin-entry');
        const selectedNameLabel = document.getElementById('selected-name');
        const selectedNimLabel = document.getElementById('selected-nim');
        const inputNim = document.getElementById('input-nim');

        const pinInput = document.getElementById('real-pin-input');
        const dots = document.querySelectorAll('.pin-dot');
        const digits = document.querySelectorAll('.pin-digit');
        const eyeBtn = document.getElementById('eye-btn');
        let isRevealed = false;

        function showPinForm(name, nim) {
            stepSelect.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
            setTimeout(() => {
                stepSelect.classList.add('hidden');

                selectedNameLabel.innerText = name;
                selectedNimLabel.innerText = "NIM: " + nim;
                inputNim.value = nim;

                stepPin.classList.remove('hidden');
                setTimeout(() => {
                    stepPin.classList.remove('opacity-0', 'scale-95');
                    stepPin.classList.add('opacity-100', 'scale-100');
                    pinInput.value = '';
                    renderPin();
                    pinInput.focus();
                }, 50);
            }, 300);
        }

        function backToSelect() {
            stepPin.classList.remove('opacity-100', 'scale-100');
            stepPin.classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                stepPin.classList.add('hidden');
                stepSelect.classList.remove('hidden');
                setTimeout(() => {
                    stepSelect.classList.remove('opacity-0', 'scale-95', 'pointer-events-none');
                }, 50);
            }, 300);
        }

        function renderPin() {
            const val = pinInput.value;
            for (let i = 0; i < 4; i++) {
                if (i < val.length) {
                    if (isRevealed) {
                        dots[i].classList.add('hidden');
                        digits[i].classList.remove('hidden');
                        digits[i].innerText = val[i];
                    } else {
                        dots[i].classList.remove('hidden', 'bg-gray-300');
                        dots[i].classList.add('bg-gray-800');
                        digits[i].classList.add('hidden');
                    }
                } else {
                    dots[i].classList.remove('hidden', 'bg-gray-800');
                    dots[i].classList.add('bg-gray-300');
                    digits[i].classList.add('hidden');
                }
            }

            if (val.length === 4) {
                setTimeout(() => {
                    document.getElementById('form-login').submit();
                }, 150);
            }
        }

        pinInput.addEventListener('input', () => {
            pinInput.value = pinInput.value.replace(/[^0-9]/g, '').slice(0, 4);
            renderPin();
        });

        const showPin = (e) => {
            e.preventDefault();
            isRevealed = true;
            renderPin();
            eyeBtn.classList.add('text-blue-600');
        }
        const hidePin = (e) => {
            e.preventDefault();
            isRevealed = false;
            renderPin();
            eyeBtn.classList.remove('text-blue-600');
            pinInput.focus();
        }

        eyeBtn.addEventListener('mousedown', showPin);
        eyeBtn.addEventListener('mouseup', hidePin);
        eyeBtn.addEventListener('mouseleave', hidePin);
        eyeBtn.addEventListener('touchstart', showPin);
        eyeBtn.addEventListener('touchend', hidePin);
        eyeBtn.addEventListener('touchcancel', hidePin);

        function loginSimulasi() {
            if (pinInput.value.length === 4) {
                document.getElementById('form-login').submit();
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'PIN Belum Lengkap',
                        text: 'Silakan masukkan 4 digit PIN Anda!',
                        confirmButtonColor: '#2563eb'
                    });
                } else {
                    alert("Silakan masukkan 4 digit PIN Anda!");
                }
                pinInput.focus();
            }
        }
    </script>
</body>

</html>