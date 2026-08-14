import './bootstrap';
import Alpine from 'alpinejs';
import './animasi';
import './pwa';

// PEMANGKAS BERKAS UTAMA
// pdf.js dan pdf-lib adalah ±80% dari 583 kB berkas JS utama, padahal hanya
// berguna di halaman baca. Dengan impor dinamis, Vite memecahnya menjadi
// bongkahan tersendiri yang baru diambil dari server bila #pembaca-pdf ada
// di halaman. Pengunjung beranda, katalog, dan koleksi tidak lagi membayar
// ongkos unduhnya.
//
// Kegagalan pemuatan ditangani terang-terangan: tanpa .catch(), bongkahan
// yang gagal diambil hanya meninggalkan pembaca membeku di "Memuat berkas…"
// tanpa penjelasan apa pun.
if (document.getElementById('pembaca-pdf')) {
    import('./pembaca-pdf').catch((galat) => {
        console.error('Gagal memuat penampil PDF:', galat);

        const status = document.getElementById('status-pembaca');
        if (status) {
            status.textContent = 'Penampil PDF gagal dimuat. Periksa sambungan lalu muat ulang halaman.';
        }
    });
}

window.Alpine = Alpine;
Alpine.start();