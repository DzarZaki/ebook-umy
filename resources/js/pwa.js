// Berkas ini menangani dua hal: pendaftaran service worker dan tombol pasang aplikasi.

/**
 * Mendaftarkan service worker agar aplikasi bisa dibuka saat koneksi terputus.
 * Hanya dijalankan bila peramban mendukungnya.
 */
function daftarkanServiceWorker() {
	if (!('serviceWorker' in navigator)) {
		return
	}

	window.addEventListener('load', () => {
		navigator.serviceWorker
			.register('/sw.js')
			.catch((galat) => console.warn('Service worker gagal didaftarkan:', galat))
	})
}

/**
 * Mengumpulkan semua tombol pasang yang ada di halaman.
 * Ada dua: versi layar lebar dan versi menu tanggap.
 */
function ambilTombolPasang() {
	return Array.from(document.querySelectorAll('#tombol-pasang, #tombol-pasang-mobile'))
}

/**
 * Menampilkan atau menyembunyikan seluruh tombol pasang sekaligus.
 */
function aturTampilanTombol(tampilkan) {
	ambilTombolPasang().forEach((tombol) => {
		tombol.classList.toggle('hidden', !tampilkan)
	})
}

/**
 * Mengubah teks pada seluruh tombol pasang, dipakai saat proses berlangsung.
 */
function aturTeksTombol(teks) {
	ambilTombolPasang().forEach((tombol) => {
		tombol.textContent = teks
	})
}

/**
 * Mengatur seluruh alur pemasangan aplikasi ke perangkat pengguna.
 */
function siapkanTombolPasang() {
	// Peramban menyimpan tawaran pemasangan di sini sampai pengguna menekan tombol.
	let tawaranPasang = null

	// Peramban memancarkan peristiwa ini bila aplikasi memenuhi syarat untuk dipasang.
	window.addEventListener('beforeinstallprompt', (peristiwa) => {
		peristiwa.preventDefault()
		tawaranPasang = peristiwa
		aturTampilanTombol(true)
	})

	// Saat tombol ditekan, tawaran yang tersimpan ditampilkan ke pengguna.
	ambilTombolPasang().forEach((tombol) => {
		tombol.addEventListener('click', async () => {
			if (!tawaranPasang) {
				return
			}

			const teksAwal = tombol.textContent
			aturTeksTombol('Menyiapkan…')

			tawaranPasang.prompt()
			const hasil = await tawaranPasang.userChoice

			// Tawaran hanya boleh dipakai sekali, jadi dibuang setelah dipakai.
			tawaranPasang = null

			if (hasil.outcome === 'accepted') {
				aturTampilanTombol(false)
			} else {
				aturTeksTombol(teksAwal)
			}
		})
	})

	// Bila aplikasi sudah terpasang, tombolnya tidak perlu tampil lagi.
	window.addEventListener('appinstalled', () => {
		tawaranPasang = null
		aturTampilanTombol(false)
	})

	// Aplikasi yang dibuka dari ikon layar utama juga tidak perlu tombol pasang.
	if (window.matchMedia('(display-mode: standalone)').matches) {
		aturTampilanTombol(false)
	}
}

daftarkanServiceWorker()
document.addEventListener('DOMContentLoaded', siapkanTombolPasang)