document.addEventListener("DOMContentLoaded", function() {
    
    // 1. Logika Hitung Mundur Sisa Magang
    function hitungSisaMagang() {
        const endDate = new Date('2026-10-08T00:00:00');
        const today = new Date(); 
        
        const diffTime = endDate - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
        
        const displayDays = diffDays > 0 ? diffDays : 0;
        const remainingEl = document.getElementById('remainingDays');
        if (remainingEl) {
            remainingEl.innerHTML = displayDays + ' <span class="text-lg font-medium text-rose-100">Days</span>';
        }
    }
    
    hitungSisaMagang();

    // [MAINTENANCE]: Inisialisasi Chart.js telah dipindahkan sepenuhnya ke file dashboard_mahasiswa.php 
    // agar terhubung langsung dengan data backend PHP dan mencegah error 'Canvas is already in use'.
});