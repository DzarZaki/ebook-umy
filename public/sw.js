// Service worker Pustaka Dosen.
// Prinsipnya sederhana: hanya aset statis bernama-hash yang disimpan di cache.
// Halaman, berkas PDF, dan semua permintaan lain selalu diambil dari jaringan
// agar pembaruan kode langsung terasa dan berkas privat tidak pernah tersimpan.

const NAMA_CACHE = 'pustaka-dosen-v3'
const HALAMAN_LURING = '/offline.html'

/**
 * Menyimpan halaman luring saat service worker dipasang.
 * skipWaiting membuat versi baru langsung menggantikan versi lama.
 */
self.addEventListener('install', (peristiwa) => {
	peristiwa.waitUntil(
		caches
			.open(NAMA_CACHE)
			.then((cache) => cache.addAll([HALAMAN_LURING]))
			.then(() => self.skipWaiting()),
	)
})

/**
 * Membuang seluruh cache versi lama, lalu langsung mengambil alih semua tab.
 */
self.addEventListener('activate', (peristiwa) => {
	peristiwa.waitUntil(
		caches
			.keys()
			.then((daftar) =>
				Promise.all(daftar.filter((nama) => nama !== NAMA_CACHE).map((nama) => caches.delete(nama))),
			)
			.then(() => self.clients.claim()),
	)
})

/**
 * Menentukan apakah sebuah alamat termasuk aset statis yang aman disimpan.
 * Berkas di /build/ memiliki nama ber-hash, jadi tidak mungkin basi.
 */
function asetStatis(pathname) {
	return (
		pathname.startsWith('/build/') ||
		pathname.startsWith('/images/') ||
		pathname === '/manifest.webmanifest' ||
		pathname === '/favicon.ico'
	)
}

self.addEventListener('fetch', (peristiwa) => {
	const permintaan = peristiwa.request
	const alamat = new URL(permintaan.url)

	// Hanya permintaan GET dari domain sendiri yang ditangani.
	if (permintaan.method !== 'GET' || alamat.origin !== self.location.origin) {
		return
	}

	// Berkas PDF privat tidak boleh pernah disimpan di cache peramban.
	if (alamat.pathname.includes('/berkas')) {
		return
	}

	// Perpindahan halaman selalu mengutamakan jaringan; cache hanya cadangan saat luring.
	if (permintaan.mode === 'navigate') {
		peristiwa.respondWith(
			fetch(permintaan).catch(() => caches.match(HALAMAN_LURING)),
		)
		return
	}

	// Aset statis diambil dari cache bila ada, dan disimpan saat pertama kali diunduh.
	if (asetStatis(alamat.pathname)) {
		peristiwa.respondWith(
			caches.match(permintaan).then((tersimpan) => {
				if (tersimpan) return tersimpan

				return fetch(permintaan).then((respons) => {
					if (respons.ok) {
						const salinan = respons.clone()
						caches.open(NAMA_CACHE).then((cache) => cache.put(permintaan, salinan))
					}

					return respons
				})
			}),
		)
		return
	}

	// Sisanya diteruskan apa adanya tanpa campur tangan service worker.
})