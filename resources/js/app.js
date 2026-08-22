import './bootstrap';
import Alpine from 'alpinejs';
import './animasi';
import './pwa';
import './card-tilt';

// Inisialisasi Tema (Terang / Gelap / Sistem)
Alpine.store('theme', {
    mode: localStorage.getItem('theme') || 'system',
    resolvedDark: false,

    init() {
        this.apply();
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (this.mode === 'system') {
                this.apply();
            }
        });
    },

    setMode(mode) {
        this.mode = mode;
        if (mode === 'system') {
            localStorage.removeItem('theme');
        } else {
            localStorage.setItem('theme', mode);
        }
        this.apply();
    },

    apply() {
        const isDark = this.mode === 'dark' || (this.mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        this.resolvedDark = isDark;
        if (isDark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
});

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