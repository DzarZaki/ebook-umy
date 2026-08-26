import './bootstrap';
import Alpine from 'alpinejs';
import './animasi';
import './pwa';
import './unggah-buku';

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

// Data Alpine bersama untuk seluruh modal konfirmasi rumah:
// fokus dipindahkan ke dalam panel saat terbuka, dikelilingi Tab
// (tidak bocor ke halaman di belakang overlay), dan dikembalikan
// ke tombol pemicunya saat ditutup.
Alpine.data('modalFokus', () => ({
    terbuka: false,
    pemicu: null,

    buka() {
        this.pemicu = document.activeElement;
        this.terbuka = true;
        this.$nextTick(() => this.$root.querySelector('[data-panel-fokus]')?.focus());
    },

    tutup() {
        if (!this.terbuka) return;
        this.terbuka = false;
        this.pemicu?.focus?.();
        this.pemicu = null;
    },

    jagaTab(peristiwa) {
        if (peristiwa.key !== 'Tab') return;

        const milik = [...this.$root.querySelectorAll(
            'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])',
        )];
        if (!milik.length) return;

        const pertama = milik[0];
        const terakhir = milik[milik.length - 1];

        if (peristiwa.shiftKey && document.activeElement === pertama) {
            peristiwa.preventDefault();
            terakhir.focus();
        } else if (!peristiwa.shiftKey && document.activeElement === terakhir) {
            peristiwa.preventDefault();
            pertama.focus();
        }
    },
}));

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