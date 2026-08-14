// Service worker Pustaka Dosen.
// Prinsipnya sederhana: hanya aset statis bernama-hash yang disimpan di cache.
// Halaman, berkas PDF, dan semua permintaan lain selalu diambil dari jaringan
// agar pembaruan kode langsung terasa dan berkas privat tidak pernah tersimpan.
//
// WAJIB DIBACA SEBELUM MENERBITKAN VERSI BARU:
// Naikkan angka pada NAMA_CACHE setiap kali aplikasi di-deploy. Angka itulah
// satu-satunya cara cache lama — beserta seluruh sisa berkas build sebelumnya
// — dibuang dari perangkat pengguna. Bila tidak dinaikkan, sampahnya menumpuk
// tanpa batas di ponsel mahasiswa.

const NAMA_CACHE = 'pustaka-dosen-v4'
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

/**
 * Memutuskan apakah sebuah jawaban layak diawetkan.
 *
 * Pemeriksaan `respons.ok` saja TIDAK CUKUP, dan ini bukan kehati-hatian
 * berlebihan — kami sudah pernah tertimpa akibatnya. Apache sempat menyajikan
 * berkas .mjs dengan status 200 tetapi tanpa Content-Type sama sekali. Status
 * 200 membuat `ok` bernilai benar, jawaban cacat itu tersimpan, dan halaman
 * baca rusak berhari-hari — bahkan setelah servernya sendiri diperbaiki,
 * karena yang disajikan bukan lagi jawaban server melainkan salinan busuk
 * dari cache. Pengguna tidak punya cara memperbaikinya sendiri.
 *
 * Karena itu tipe isinya ikut diperiksa, dan khusus untuk skrip dan gaya
 * tipenya harus benar-benar cocok.
 */
function layakDisimpan(respons, permintaan) {
	if (!respons || respons.status !== 200 || respons.type !== 'basic') {
		return false
	}

	const tipe = (respons.headers.get('content-type') || '').toLowerCase()
	if (!tipe) {
		return false
	}

	const tujuan = permintaan.destination

	if (tujuan === 'script' || tujuan === 'worker' || tujuan === 'sharedworker') {
		return tipe.includes('javascript') || tipe.includes('ecmascript')
	}

	if (tujuan === 'style') {
		return tipe.includes('text/css')
	}

	return true
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
				// Penyembuhan diri: entri lama yang cacat dibuang, bukan disajikan.
				// Tanpa ini, satu jawaban busuk yang terlanjur tersimpan oleh versi
				// service worker sebelumnya akan hidup selamanya di perangkat itu.
				if (tersimpan && layakDisimpan(tersimpan, permintaan)) {
					return tersimpan
				}

				if (tersimpan) {
					caches.open(NAMA_CACHE).then((cache) => cache.delete(permintaan))
				}

				return fetch(permintaan)
					.then((respons) => {
						if (layakDisimpan(respons, permintaan)) {
							const salinan = respons.clone()
							caches.open(NAMA_CACHE).then((cache) => cache.put(permintaan, salinan))
						}

						return respons
					})
					.catch((galat) => {
						// Jaringan mati dan salinannya cacat: salinan cacat masih lebih
						// baik daripada tidak ada jawaban sama sekali.
						if (tersimpan) {
							return tersimpan
						}

						throw galat
					})
			}),
		)
		return
	}

	// Sisanya diteruskan apa adanya tanpa campur tangan service worker.
})