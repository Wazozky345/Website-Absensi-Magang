<?php require_once __DIR__ . '/config/ddos_layer.php'; 
// 🚧⚠️ File Register ini masih dalam tahap pengembangan, jadi beberapa fitur mungkin belum sepenuhnya berfungsi.
// jangan gunakan register.php dulu sampai comment ini terhapus 🚧🗼
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - UTB Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .bg-pattern {
            background-color: #F4F7FE;
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 20px 20px;
        }
        /* Sembunyikan panah pada input number */
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { 
            -webkit-appearance: none; margin: 0; 
        }
    </style>
</head>
<body class="bg-pattern flex flex-col min-h-screen text-gray-800 relative items-center justify-center py-10 px-4">

    <!-- Tombol Kembali -->
    <a href="landing_page_utama.php" class="absolute top-6 left-6 md:top-10 md:left-10 flex items-center gap-2 text-gray-500 hover:text-blue-600 transition font-medium bg-white px-4 py-2 rounded-full shadow-sm z-50">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Kembali
    </a>

    <div class="w-full max-w-xl bg-white/95 backdrop-blur-md rounded-3xl shadow-xl border border-white overflow-hidden relative">
        
        <!-- HEADER PROGRESS BAR -->
        <div class="bg-gray-50 px-8 py-6 border-b border-gray-100 relative z-10">
            <h2 class="text-2xl font-bold text-gray-800 text-center mb-6">Pendaftaran Akun Magang</h2>
            
            <div class="flex items-center justify-between relative">
                <div class="absolute left-0 top-1/2 transform -translate-y-1/2 w-full h-1 bg-gray-200 rounded-full z-0"></div>
                <div id="progress-line" class="absolute left-0 top-1/2 transform -translate-y-1/2 w-0 h-1 bg-blue-500 rounded-full z-0 transition-all duration-500"></div>
                
                <div class="relative z-10 flex flex-col items-center gap-2">
                    <div id="step1-indicator" class="w-10 h-10 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center border-4 border-white shadow-sm transition-colors">1</div>
                    <span class="text-xs font-bold text-gray-500">Data Diri</span>
                </div>
                <div class="relative z-10 flex flex-col items-center gap-2">
                    <div id="step2-indicator" class="w-10 h-10 rounded-full bg-gray-200 text-gray-500 font-bold flex items-center justify-center border-4 border-white shadow-sm transition-colors">2</div>
                    <span class="text-xs font-bold text-gray-400">Verifikasi</span>
                </div>
                <div class="relative z-10 flex flex-col items-center gap-2">
                    <div id="step3-indicator" class="w-10 h-10 rounded-full bg-gray-200 text-gray-500 font-bold flex items-center justify-center border-4 border-white shadow-sm transition-colors">3</div>
                    <span class="text-xs font-bold text-gray-400">Keamanan</span>
                </div>
            </div>
        </div>

        <div class="p-8 relative min-h-[440px]">
            
            <!-- LOADING OVERLAY -->
            <div id="loading-overlay" class="absolute inset-0 bg-white/80 backdrop-blur-sm z-20 flex flex-col items-center justify-center hidden rounded-b-3xl">
                <svg class="animate-spin h-10 w-10 text-blue-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <p id="loading-text" class="text-gray-600 font-semibold animate-pulse">Memproses permintaan...</p>
            </div>

            <!-- STEP 1: FORM DATA DIRI -->
            <form id="form-step-1" class="space-y-5 transition-all duration-500 transform translate-x-0 opacity-100">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-1.5">NIM</label>
                        <input type="number" id="reg-nim" required placeholder="Contoh: 232101111" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition font-medium">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-1.5">Nama Lengkap</label>
                        <input type="text" id="reg-nama" required placeholder="Sesuai kartu mahasiswa" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition font-medium">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-1.5">Kelas</label>
                        <input type="text" id="reg-kelas" required placeholder="Contoh: TiF RP 23 H" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition font-medium">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-1.5">Konsentrasi</label>
                        <input type="text" id="reg-konsentrasi" required placeholder="Contoh: Computer and Network Security" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition font-medium">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1.5">Email Aktif (Untuk Kode OTP)</label>
                    <input type="email" id="reg-email" required placeholder="nama@email.com" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition font-medium">
                </div>

                <button type="button" onclick="prosesStep1()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-200 transition mt-4">
                    Kirim Kode OTP
                </button>
            </form>

            <!-- STEP 2: FORM OTP EMAIL -->
            <form id="form-step-2" class="space-y-6 hidden absolute inset-0 p-8 pt-10">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center mx-auto mb-4 border border-blue-100 shadow-sm">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800">Cek Email Anda</h3>
                    <p class="text-sm text-gray-500 mt-2">Kami telah mengirimkan 6 digit kode OTP ke email <br><span id="display-email" class="font-bold text-gray-700"></span></p>
                </div>

                <div class="flex justify-center gap-2 md:gap-3">
                    <input type="text" maxlength="1" class="otp-input w-10 h-12 md:w-12 md:h-14 text-center text-xl font-bold bg-gray-50 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition">
                    <input type="text" maxlength="1" class="otp-input w-10 h-12 md:w-12 md:h-14 text-center text-xl font-bold bg-gray-50 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition">
                    <input type="text" maxlength="1" class="otp-input w-10 h-12 md:w-12 md:h-14 text-center text-xl font-bold bg-gray-50 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition">
                    <span class="text-gray-300 font-bold self-center">-</span>
                    <input type="text" maxlength="1" class="otp-input w-10 h-12 md:w-12 md:h-14 text-center text-xl font-bold bg-gray-50 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition">
                    <input type="text" maxlength="1" class="otp-input w-10 h-12 md:w-12 md:h-14 text-center text-xl font-bold bg-gray-50 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition">
                    <input type="text" maxlength="1" class="otp-input w-10 h-12 md:w-12 md:h-14 text-center text-xl font-bold bg-gray-50 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition">
                </div>

                <div class="flex flex-col gap-3 mt-8">
                    <button type="button" onclick="prosesStep2()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-200 transition">
                        Verifikasi Kode
                    </button>
                    <button type="button" onclick="kembaliKeStep1()" class="w-full text-gray-500 hover:text-gray-700 font-semibold py-3 text-sm transition">
                        Salah ketik email? Kembali
                    </button>
                </div>
            </form>

            <!-- STEP 3: BUAT PIN (SLIDER & CUSTOM PIN DOTS) -->
            <div id="form-step-3" class="hidden absolute inset-0 overflow-hidden rounded-b-3xl">
                <!-- Wadah Slider Utama -->
                <form id="pin-slider-container" class="flex w-full h-full transition-transform duration-500 ease-in-out transform translate-x-0 pt-8">
                    
                    <!-- BAGIAN 3A: Input PIN Pertama -->
                    <div class="w-full flex-shrink-0 flex flex-col items-center px-8 relative">
                        <div class="text-center mb-8 w-full">
                            <div class="w-16 h-16 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center mx-auto mb-4 border border-blue-100 shadow-sm">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-800">Buat PIN Keamanan</h3>
                            <p class="text-sm text-gray-500 mt-2">Buat 4 digit angka kunci akses masuk.</p>
                        </div>

                        <!-- DESAIN CUSTOM PIN DOTS (KOTAK PERTAMA) -->
                        <div class="w-full max-w-[280px]">
                            <div class="relative w-full bg-gray-50 border-2 border-gray-200 rounded-2xl h-20 flex items-center justify-center cursor-pointer shadow-inner focus-within:border-blue-500 focus-within:ring-4 focus-within:ring-blue-50 transition-all">
                                <!-- Input asli disembunyikan total -->
                                <input type="tel" id="reg-pin" maxlength="4" inputmode="numeric" class="absolute inset-0 w-full h-full opacity-0 z-10 cursor-pointer" oninput="updatePinUI(this, 'dot-pin-1')">
                                <!-- Wadah Titik -->
                                <div class="flex gap-5 pointer-events-none">
                                    <div id="dot-pin-1-0" class="w-4 h-4 rounded-full bg-gray-300 transition-colors duration-200"></div>
                                    <div id="dot-pin-1-1" class="w-4 h-4 rounded-full bg-gray-300 transition-colors duration-200"></div>
                                    <div id="dot-pin-1-2" class="w-4 h-4 rounded-full bg-gray-300 transition-colors duration-200"></div>
                                    <div id="dot-pin-1-3" class="w-4 h-4 rounded-full bg-gray-300 transition-colors duration-200"></div>
                                </div>
                            </div>
                        </div>

                        <button type="button" onclick="lanjutKonfirmasiPin()" class="w-full max-w-[280px] bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-blue-200 transition mt-8 text-lg">
                            Konfirmasi PIN
                        </button>
                    </div>

                    <!-- BAGIAN 3B: Konfirmasi PIN (Bergeser dari kanan) -->
                    <div class="w-full flex-shrink-0 flex flex-col items-center px-8 relative">
                        
                        <!-- Tombol Back Kecil untuk merevisi PIN Pertama -->
                        <button type="button" onclick="kembaliInputPin()" class="absolute top-0 left-6 text-gray-400 hover:text-gray-700 transition p-2 bg-gray-50 rounded-full">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </button>

                        <div class="text-center mb-8 w-full">
                            <div class="w-16 h-16 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-4 border border-emerald-100 shadow-sm">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-800">Ketik Ulang PIN</h3>
                            <p class="text-sm text-gray-500 mt-2">Pastikan 4 digit angka yang dimasukkan sama.</p>
                        </div>

                        <!-- DESAIN CUSTOM PIN DOTS (KOTAK KEDUA) -->
                        <div class="w-full max-w-[280px]">
                            <div class="relative w-full bg-gray-50 border-2 border-gray-200 rounded-2xl h-20 flex items-center justify-center cursor-pointer shadow-inner focus-within:border-emerald-500 focus-within:ring-4 focus-within:ring-emerald-50 transition-all">
                                <input type="tel" id="reg-pin-confirm" maxlength="4" inputmode="numeric" class="absolute inset-0 w-full h-full opacity-0 z-10 cursor-pointer" oninput="updatePinUI(this, 'dot-pin-2')">
                                <div class="flex gap-5 pointer-events-none">
                                    <div id="dot-pin-2-0" class="w-4 h-4 rounded-full bg-gray-300 transition-colors duration-200"></div>
                                    <div id="dot-pin-2-1" class="w-4 h-4 rounded-full bg-gray-300 transition-colors duration-200"></div>
                                    <div id="dot-pin-2-2" class="w-4 h-4 rounded-full bg-gray-300 transition-colors duration-200"></div>
                                    <div id="dot-pin-2-3" class="w-4 h-4 rounded-full bg-gray-300 transition-colors duration-200"></div>
                                </div>
                            </div>
                        </div>

                        <button type="button" onclick="prosesStep3()" class="w-full max-w-[280px] bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-4 rounded-xl shadow-lg shadow-emerald-200 transition mt-8 text-lg">
                            Selesaikan Pendaftaran
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Variabel UI Utama
        const step1 = document.getElementById('form-step-1');
        const step2 = document.getElementById('form-step-2');
        const step3 = document.getElementById('form-step-3');
        const overlay = document.getElementById('loading-overlay');
        const loadingText = document.getElementById('loading-text');
        const progLine = document.getElementById('progress-line');
        const pinSlider = document.getElementById('pin-slider-container');

        // Logika Input OTP (Auto Next)
        const otpInputs = document.querySelectorAll('.otp-input');
        otpInputs.forEach((input, index) => {
            input.addEventListener('keyup', (e) => {
                if (e.key >= 0 && e.key <= 9) {
                    if (index < otpInputs.length - 1) otpInputs[index + 1].focus();
                } else if (e.key === 'Backspace') {
                    if (index > 0) otpInputs[index - 1].focus();
                }
            });
        });

        function showLoading(text) {
            loadingText.innerText = text;
            overlay.classList.remove('hidden');
        }

        function hideLoading() {
            overlay.classList.add('hidden');
        }

        // --- STEP 1 KE STEP 2 ---
        function prosesStep1() {
            const nim = document.getElementById('reg-nim').value;
            const nama = document.getElementById('reg-nama').value;
            const email = document.getElementById('reg-email').value;

            if(!nim || !nama || !email) {
                Swal.fire({ icon: 'warning', title: 'Oops', text: 'Semua data diri wajib diisi!' });
                return;
            }

            showLoading("Mengirim OTP ke email...");

            setTimeout(() => {
                hideLoading();
                
                document.getElementById('display-email').innerText = email;
                step1.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
                
                setTimeout(() => {
                    step1.classList.add('hidden');
                    step2.classList.remove('hidden');
                    progLine.style.width = '50%';
                    document.getElementById('step2-indicator').classList.replace('bg-gray-200', 'bg-blue-600');
                    document.getElementById('step2-indicator').classList.replace('text-gray-500', 'text-white');
                    
                    otpInputs[0].focus();
                }, 300);
            }, 1500);
        }

        function kembaliKeStep1() {
            step2.classList.add('hidden');
            step1.classList.remove('hidden', 'opacity-0', 'scale-95', 'pointer-events-none');
            
            progLine.style.width = '0%';
            document.getElementById('step2-indicator').classList.replace('bg-blue-600', 'bg-gray-200');
            document.getElementById('step2-indicator').classList.replace('text-white', 'text-gray-500');
        }

        // --- STEP 2 KE STEP 3 ---
        function prosesStep2() {
            let otpValue = '';
            otpInputs.forEach(input => otpValue += input.value);

            if(otpValue.length < 6) {
                Swal.fire({ icon: 'error', title: 'Kode Tidak Lengkap', text: 'Masukkan 6 digit kode OTP!' });
                return;
            }

            showLoading("Memverifikasi kode OTP...");

            setTimeout(() => {
                hideLoading();
                
                step2.classList.add('hidden');
                step3.classList.remove('hidden');
                
                progLine.style.width = '100%';
                document.getElementById('step3-indicator').classList.replace('bg-gray-200', 'bg-blue-600');
                document.getElementById('step3-indicator').classList.replace('text-gray-500', 'text-white');
                
                document.getElementById('reg-pin').focus();
            }, 1500);
        }

        // --- LOGIKA WARNA TITIK PIN (CUSTOM PIN DOTS) ---
        function updatePinUI(inputElement, dotPrefix) {
            // Blokir input selain angka
            inputElement.value = inputElement.value.replace(/[^0-9]/g, '');
            let val = inputElement.value;
            
            // Loop 4 titik
            for (let i = 0; i < 4; i++) {
                let dot = document.getElementById(dotPrefix + '-' + i);
                if (i < val.length) {
                    // Berubah hitam (terisi)
                    dot.classList.remove('bg-gray-300');
                    dot.classList.add('bg-gray-800');
                } else {
                    // Kembali abu-abu (kosong)
                    dot.classList.remove('bg-gray-800');
                    dot.classList.add('bg-gray-300');
                }
            }
        }

        // --- STEP 3: LOGIKA ANIMASI GESER (SLIDER) ---
        function lanjutKonfirmasiPin() {
            const pin1 = document.getElementById('reg-pin').value;
            if (pin1.length < 4) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'PIN harus berisi 4 angka!' });
                return;
            }

            pinSlider.classList.remove('translate-x-0');
            pinSlider.classList.add('-translate-x-full');
            
            setTimeout(() => {
                document.getElementById('reg-pin-confirm').focus();
            }, 500);
        }

        function kembaliInputPin() {
            // Bersihkan input konfirmasi secara data dan visual
            const confirmInput = document.getElementById('reg-pin-confirm');
            confirmInput.value = '';
            updatePinUI(confirmInput, 'dot-pin-2'); // Reset titik ke abu-abu

            pinSlider.classList.remove('-translate-x-full');
            pinSlider.classList.add('translate-x-0');
            
            setTimeout(() => {
                document.getElementById('reg-pin').focus();
            }, 500);
        }

        // --- STEP 3 (FINALISASI SIMPAN DB) ---
        function prosesStep3() {
            const pin1 = document.getElementById('reg-pin').value;
            const pin2 = document.getElementById('reg-pin-confirm').value;

            if (pin2.length < 4) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Konfirmasi PIN harus terisi penuh 4 angka!' });
                return;
            }

            if (pin1 !== pin2) {
                Swal.fire({ icon: 'error', title: 'PIN Tidak Cocok', text: 'Konfirmasi PIN yang Anda masukkan berbeda. Silakan ulangi.' });
                kembaliInputPin();
                return;
            }

            showLoading("Menyimpan data akun...");

            // Simulasi Sukses Register
            setTimeout(() => {
                hideLoading();
                Swal.fire({
                    icon: 'success',
                    title: 'Pendaftaran Berhasil!',
                    text: 'Akun magang Anda telah aktif. Silakan masuk melalui Dashboard.',
                    confirmButtonText: 'Menuju Halaman Login',
                    confirmButtonColor: '#10b981',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'login.php';
                    }
                });
            }, 1500);
        }
    </script>
</body>
</html>