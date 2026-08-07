import * as pdfjsLib from 'pdfjs-dist'
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url'
import { PDFDocument, StandardFonts, degrees, rgb } from 'pdf-lib'

pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl

// Alamat harus lengkap, bukan relatif. Sumber daya ini dimuat dari dalam worker,
// dan worker tidak mengenali alamat relatif seperti /pdfjs/ sehingga akan gagal.
const ASAL = window.location.origin

const ASET_PDFJS = {
	standardFontDataUrl: `${ASAL}/pdfjs/standard_fonts/`,
	cMapUrl: `${ASAL}/pdfjs/cmaps/`,
	cMapPacked: true,
	wasmUrl: `${ASAL}/pdfjs/wasm/`,
	iccUrl: `${ASAL}/pdfjs/iccs/`,
}

/**
 * Helper terpusat untuk semua POST JSON ke server.
 * Selalu menyisipkan header X-CSRF-TOKEN dan Accept: application/json.
 */
async function postJson(url, csrf, body = {}) {
	return fetch(url, {
		method: 'POST',
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/json',
			'X-CSRF-TOKEN': csrf,
			Accept: 'application/json',
		},
		body: JSON.stringify(body),
	})
}

/** Menyiapkan penampil PDF pada halaman baca. */
async function siapkanPembaca(wadah) {
	const data = wadah.dataset
	const daftarHalaman = document.getElementById('daftar-halaman')
	const status = document.getElementById('status-pembaca')
	const isianHalaman = document.getElementById('isian-halaman')
	const totalHalaman = document.getElementById('total-halaman')
	const tombolUnduh = document.getElementById('tombol-unduh')
	const tombolPenanda = document.getElementById('tombol-penanda')
	const ikonPenandaOutline = document.getElementById('ikon-penanda-outline')
	const ikonPenandaIsi = document.getElementById('ikon-penanda-isi')
	const tombolPanelPenanda = document.getElementById('tombol-panel-penanda')
	const panelPenanda = document.getElementById('panel-penanda')
	const daftarPenandaEl = document.getElementById('daftar-penanda')
	const pesanPenandaKosong = document.getElementById('pesan-penanda-kosong')
	const jumlahPenanda = document.getElementById('jumlah-penanda')

	let dokumen = null
	let halamanAktif = 1
	let skala = 1.3
	let bytesAsli = null
	let observerRender = null
	let observerHalamanAktif = null
	let sedangUbahSkala = false
	let sinkronisasiTerjadwal = false
	let ukuranPlaceholder = { lebar: 0, tinggi: 0, rasio: 1 }
	const daftarPenanda = new Set()
	const halamanTerlihat = new Set()
	const halamanDenganKanvas = new Set()
	const rasioTerlihat = new Map()
	const keadaanHalaman = new Map()

	/** Menampilkan pesan di bilah status, dan menyembunyikannya bila tidak ada pesan. */
	function tampilkanStatus(teks) {
		if (!status) return

		status.textContent = teks
		status.classList.toggle('hidden', teks === '')
	}

	// Berkas diambil sekali, lalu dipakai ulang untuk membaca maupun mengunduh.
	tampilkanStatus('Memuat berkas…')

	async function muatDokumen() {
		try {
			const respons = await fetch(data.urlBerkas, { credentials: 'same-origin' })

			if (!respons.ok) {
				throw new Error(`Server menjawab ${respons.status}`)
			}

			bytesAsli = await respons.arrayBuffer()
		} catch (galat) {
			console.error('Gagal mengambil berkas PDF:', galat)
			tampilkanStatus(`Berkas tidak dapat dimuat (${galat.message}). Coba muat ulang halaman.`)
			throw galat
		}

		// Dokumen dibuka dari salinan bytes karena pdf.js memindahkan kepemilikan buffer.
		try {
			const tugas = pdfjsLib.getDocument({
				data: bytesAsli.slice(0),
				...ASET_PDFJS,
			})

			return await tugas.promise
		} catch (galat) {
			console.error('Gagal membuka dokumen PDF:', galat)
			tampilkanStatus(`Berkas PDF tidak dapat dibuka (${galat.message}).`)
			throw galat
		}
	}

	async function muatDataBacaAwal() {
		if (!data.urlDataBaca) return null

		try {
			const respons = await fetch(data.urlDataBaca, { credentials: 'same-origin' })
			if (!respons.ok) return null
			return await respons.json()
		} catch {
			return null
		}
	}

	let dataAwal = null
	try {
		const [dokumenSiap, dataBacaAwal] = await Promise.all([
			muatDokumen(),
			muatDataBacaAwal(),
		])
		dokumen = dokumenSiap
		dataAwal = dataBacaAwal
	} catch {
		return
	}

	if (import.meta.env.DEV) {
		console.info('pdf.js siap. Versi:', pdfjsLib.version, '· Jumlah halaman:', dokumen.numPages)
	}

	if (totalHalaman) totalHalaman.textContent = dokumen.numPages
	if (isianHalaman) isianHalaman.max = dokumen.numPages

	function frameBerikutnya() {
		return new Promise((lanjut) => requestAnimationFrame(lanjut))
	}

	async function tungguGulirSelesai(nomor) {
		const elemen = keadaanHalaman.get(nomor)?.elemen
		if (!elemen) return

		for (let i = 0; i < 24; i++) {
			await frameBerikutnya()
			const kotak = elemen.getBoundingClientRect()
			if (kotak.top >= 0 && kotak.top < window.innerHeight * 0.7) return
		}
	}

	function terapkanUkuranPlaceholder(keadaan) {
		const rasio = keadaan.rasio || ukuranPlaceholder.rasio
		keadaan.elemen.style.width = '100%'
		keadaan.elemen.style.maxWidth = '100%'
		keadaan.elemen.style.aspectRatio = `${rasio}`
	}

	async function hitungUkuranPlaceholder() {
		const halamanPertama = await dokumen.getPage(1)
		const viewport = halamanPertama.getViewport({ scale: skala })
		ukuranPlaceholder = {
			lebar: viewport.width,
			tinggi: viewport.height,
			rasio: viewport.width / viewport.height,
		}
	}

	async function siapkanKerangkaHalaman() {
		if (!daftarHalaman) return false

		await hitungUkuranPlaceholder()
		daftarHalaman.innerHTML = ''
		halamanTerlihat.clear()
		halamanDenganKanvas.clear()
		rasioTerlihat.clear()
		keadaanHalaman.clear()

		for (let nomor = 1; nomor <= dokumen.numPages; nomor++) {
			const elemen = document.createElement('div')
			elemen.id = `halaman-${nomor}`
			elemen.dataset.halaman = `${nomor}`
			elemen.className = 'mx-auto mb-4 overflow-hidden bg-white shadow-sm'
			daftarHalaman.appendChild(elemen)
			const keadaan = { elemen, sedangDirender: false, gagalRender: false, rasio: ukuranPlaceholder.rasio }
			terapkanUkuranPlaceholder(keadaan)
			keadaanHalaman.set(nomor, keadaan)
		}

		return true
	}

	function halamanTargetRender() {
		const target = new Set()
		const sumber = halamanTerlihat.size > 0 ? Array.from(halamanTerlihat) : [halamanAktif]

		sumber.forEach((nomor) => {
			for (let i = nomor - 2; i <= nomor + 2; i++) {
				if (i >= 1 && i <= dokumen.numPages) {
					target.add(i)
				}
			}
		})

		return target
	}

	function jarakTerdekatDariViewport(nomor) {
		const sumber = halamanTerlihat.size > 0 ? Array.from(halamanTerlihat) : [halamanAktif]
		let jarak = Number.POSITIVE_INFINITY

		sumber.forEach((nomorSumber) => {
			jarak = Math.min(jarak, Math.abs(nomor - nomorSumber))
		})

		return jarak
	}

	function bongkarKanvas(nomor) {
		const keadaan = keadaanHalaman.get(nomor)
		if (!keadaan) return
		const kanvas = keadaan.elemen.querySelector('canvas')
		if (kanvas) {
			kanvas.width = 0
			kanvas.height = 0
			kanvas.remove()
		}
		halamanDenganKanvas.delete(nomor)
	}

	function bongkarSemuaKanvas() {
		for (let nomor = 1; nomor <= dokumen.numPages; nomor++) {
			bongkarKanvas(nomor)
		}
	}

	/** Cap miring semi transparan sebagai pengingat kepemilikan. */
	function gambarWatermarkLayar(konteks, lebar, tinggi) {
		konteks.save()
		konteks.translate(lebar / 2, tinggi / 2)
		konteks.rotate(-Math.PI / 7)
		konteks.font = `${Math.round(Math.min(lebar, tinggi) * 0.05)}px sans-serif`
		konteks.fillStyle = 'rgba(146, 56, 17, 0.16)'
		konteks.textAlign = 'center'
		konteks.fillText(data.watermark, 0, 0)
		konteks.restore()
	}

	async function renderHalamanJikaPerlu(nomor, paksa = false) {
		const keadaan = keadaanHalaman.get(nomor)
		if (!keadaan || keadaan.sedangDirender) return
		if (keadaan.gagalRender && !paksa) return
		if (keadaan.elemen.querySelector('canvas')) return

		keadaan.sedangDirender = true
		try {
			const halaman = await dokumen.getPage(nomor)
			const viewport = halaman.getViewport({ scale: skala })
			const rasioBaru = viewport.width / viewport.height
			if (Math.abs((keadaan.rasio || 0) - rasioBaru) > 0.0001) {
				keadaan.rasio = rasioBaru
				terapkanUkuranPlaceholder(keadaan)
			}
			const kanvas = document.createElement('canvas')
			const konteks = kanvas.getContext('2d')
			if (!konteks) return

			kanvas.width = Math.floor(viewport.width)
			kanvas.height = Math.floor(viewport.height)
			kanvas.style.width = '100%'
			kanvas.style.height = 'auto'
			kanvas.className = 'block'

			konteks.save()
			konteks.fillStyle = '#ffffff'
			konteks.fillRect(0, 0, kanvas.width, kanvas.height)
			konteks.restore()

			const tugas = halaman.render({
				canvas: kanvas,
				canvasContext: konteks,
				viewport,
			})
			await tugas.promise

			if (data.watermark) {
				gambarWatermarkLayar(konteks, kanvas.width, kanvas.height)
			}

			keadaan.elemen.replaceChildren(kanvas)
			keadaan.gagalRender = false
			halamanDenganKanvas.add(nomor)
		} catch (galat) {
			console.error(`Gagal merender halaman ${nomor}:`, galat)
			const pesan = document.createElement('div')
			pesan.className = 'flex h-full flex-col items-center justify-center gap-2 px-4 py-6 text-center'
			const teks = document.createElement('p')
			teks.className = 'text-sm text-kabut-600'
			teks.textContent = `Halaman ${nomor} gagal dimuat`
			const tombol = document.createElement('button')
			tombol.type = 'button'
			tombol.className = 'rounded-sm border border-kabut-300 px-3 py-1.5 text-sm font-medium text-kabut-700 hover:bg-kabut-100'
			tombol.textContent = 'Coba lagi'
			tombol.onclick = () => { void renderHalamanJikaPerlu(nomor, true) }
			pesan.appendChild(teks)
			pesan.appendChild(tombol)
			keadaan.elemen.replaceChildren(pesan)
			keadaan.gagalRender = true
			halamanDenganKanvas.delete(nomor)
		} finally {
			keadaan.sedangDirender = false
		}
	}

	function daftarHalamanSinkron() {
		const sumber = halamanTerlihat.size > 0 ? Array.from(halamanTerlihat) : [halamanAktif]
		const nomorMinimum = Math.max(1, Math.min(...sumber) - 6)
		const nomorMaksimum = Math.min(dokumen.numPages, Math.max(...sumber) + 6)
		const kandidat = new Set()

		for (let nomor = nomorMinimum; nomor <= nomorMaksimum; nomor++) {
			kandidat.add(nomor)
		}

		halamanDenganKanvas.forEach((nomor) => kandidat.add(nomor))
		return kandidat
	}

	function sinkronkanRenderHalaman() {
		const target = halamanTargetRender()
		const kandidat = daftarHalamanSinkron()

		kandidat.forEach((nomor) => {
			if (target.has(nomor)) {
				void renderHalamanJikaPerlu(nomor)
				return
			}

			if (halamanDenganKanvas.has(nomor) && jarakTerdekatDariViewport(nomor) > 5) {
				bongkarKanvas(nomor)
			}
		})
	}

	function jadwalkanSinkronisasiRender() {
		if (sinkronisasiTerjadwal) return
		sinkronisasiTerjadwal = true
		requestAnimationFrame(() => {
			sinkronisasiTerjadwal = false
			sinkronkanRenderHalaman()
		})
	}

	function perbaruiHalamanAktif(nomor, simpan = true) {
		const nomorValid = Math.min(Math.max(1, nomor), dokumen.numPages)
		if (nomorValid === halamanAktif && !simpan) return
		const berubah = nomorValid !== halamanAktif
		halamanAktif = nomorValid
		if (isianHalaman) isianHalaman.value = nomorValid
		perbaruiStatusPenanda()
		if (berubah && simpan) {
			simpanProgres(halamanAktif, dokumen.numPages)
		}
	}

	function tentukanHalamanAktifDariViewport() {
		let kandidat = halamanAktif
		let rasioTerbesar = 0

		rasioTerlihat.forEach((rasio, nomor) => {
			if (rasio > rasioTerbesar) {
				rasioTerbesar = rasio
				kandidat = nomor
			}
		})

		if (rasioTerbesar === 0 && halamanTerlihat.size > 0) {
			kandidat = Math.min(...Array.from(halamanTerlihat))
		}

		perbaruiHalamanAktif(kandidat, true)
	}

	function daftarThreshold() {
		return [0, 0.1, 0.25, 0.4, 0.6, 0.75, 0.9, 1]
	}

	function siapkanObserverRender() {
		if (observerRender) observerRender.disconnect()

		observerRender = new IntersectionObserver((entries) => {
			entries.forEach((entry) => {
				const nomor = Number.parseInt(entry.target.dataset.halaman, 10)
				if (!Number.isInteger(nomor)) return
				if (entry.isIntersecting) {
					halamanTerlihat.add(nomor)
				} else {
					halamanTerlihat.delete(nomor)
				}
			})
			jadwalkanSinkronisasiRender()
		}, { threshold: 0.01 })

		keadaanHalaman.forEach((keadaan) => observerRender.observe(keadaan.elemen))
	}

	function siapkanObserverHalamanAktif() {
		if (observerHalamanAktif) observerHalamanAktif.disconnect()

		observerHalamanAktif = new IntersectionObserver((entries) => {
			entries.forEach((entry) => {
				const nomor = Number.parseInt(entry.target.dataset.halaman, 10)
				if (!Number.isInteger(nomor)) return
				rasioTerlihat.set(nomor, entry.isIntersecting ? entry.intersectionRatio : 0)
			})
			tentukanHalamanAktifDariViewport()
		}, { threshold: daftarThreshold() })

		keadaanHalaman.forEach((keadaan) => observerHalamanAktif.observe(keadaan.elemen))
	}

	function gulirKeHalaman(nomor, perilaku = 'smooth') {
		const tujuan = Math.min(Math.max(1, nomor), dokumen.numPages)
		const elemen = keadaanHalaman.get(tujuan)?.elemen
		if (!elemen) return null
		elemen.scrollIntoView({ behavior: perilaku, block: 'start' })
		return tujuan
	}

	/** Berpindah halaman dengan menggulir ke pembungkus halaman target. */
	function keHalaman(nomor) {
		gulirKeHalaman(nomor, 'smooth')
	}

	function pulihkanPenanda(daftar) {
		daftarPenanda.clear()
		daftar.forEach((nomor) => {
			const halaman = Number.parseInt(nomor, 10)
			if (Number.isInteger(halaman) && halaman >= 1) {
				daftarPenanda.add(halaman)
			}
		})
		perbaruiStatusPenanda()
	}

	function perbaruiStatusPenanda() {
		if (!tombolPenanda) return
		const aktif = daftarPenanda.has(halamanAktif)
		tombolPenanda.setAttribute('aria-label', aktif ? 'Hapus penanda halaman ini' : 'Tandai halaman ini')
		tombolPenanda.setAttribute('title', aktif ? 'Hapus penanda halaman ini' : 'Tandai halaman ini')
		tombolPenanda.setAttribute('aria-pressed', aktif ? 'true' : 'false')
		if (ikonPenandaOutline) ikonPenandaOutline.classList.toggle('hidden', aktif)
		if (ikonPenandaIsi) ikonPenandaIsi.classList.toggle('hidden', !aktif)
		renderDaftarPenanda()
	}

	function renderDaftarPenanda() {
		if (!daftarPenandaEl || !pesanPenandaKosong || !jumlahPenanda) return

		const urut = Array.from(daftarPenanda).sort((a, b) => a - b)
		daftarPenandaEl.innerHTML = ''
		jumlahPenanda.textContent = urut.length
		pesanPenandaKosong.classList.toggle('hidden', urut.length > 0)

		urut.forEach((nomor) => {
			const item = document.createElement('li')
			const tombol = document.createElement('button')
			tombol.type = 'button'
			tombol.className = 'rounded-sm border border-kabut-300 px-3 py-1.5 text-sm font-medium text-kabut-700 hover:bg-kabut-100'
			tombol.textContent = `Hal. ${nomor}`
			if (nomor === halamanAktif) {
				tombol.classList.add('bg-kabut-100')
			}
			tombol.onclick = () => keHalaman(nomor)
			item.appendChild(tombol)
			daftarPenandaEl.appendChild(item)
		})
	}

	function aturPanelPenanda(terbuka) {
		if (!panelPenanda || !tombolPanelPenanda) return
		panelPenanda.classList.toggle('hidden', !terbuka)
		tombolPanelPenanda.setAttribute('aria-expanded', terbuka ? 'true' : 'false')
		tombolPanelPenanda.setAttribute('title', terbuka ? 'Tutup daftar penanda' : 'Buka daftar penanda')
		tombolPanelPenanda.setAttribute('aria-label', terbuka ? 'Tutup daftar penanda' : 'Buka daftar penanda')
	}

	/** Memasang penangan klik hanya bila tombolnya benar-benar ada. */
	function pasangKlik(id, penangan) {
		const tombol = document.getElementById(id)
		if (tombol) tombol.onclick = penangan
	}

	pasangKlik('tombol-sebelum', () => keHalaman(halamanAktif - 1))
	pasangKlik('tombol-sesudah', () => keHalaman(halamanAktif + 1))

	async function ubahSkala(delta) {
		if (sedangUbahSkala) return

		const skalaBaru = Math.min(Math.max(skala + delta, 0.6), 3)
		if (skalaBaru === skala) return

		sedangUbahSkala = true
		const target = halamanAktif
		skala = skalaBaru

		try {
			await hitungUkuranPlaceholder()
			keadaanHalaman.forEach((keadaan) => terapkanUkuranPlaceholder(keadaan))
			bongkarSemuaKanvas()
			jadwalkanSinkronisasiRender()
			const tujuan = gulirKeHalaman(target, 'auto')
			if (tujuan) {
				await tungguGulirSelesai(tujuan)
				perbaruiHalamanAktif(tujuan, false)
			}
		} finally {
			sedangUbahSkala = false
		}
	}

	pasangKlik('tombol-perbesar', () => { void ubahSkala(0.25) })
	pasangKlik('tombol-perkecil', () => { void ubahSkala(-0.25) })
	pasangKlik('tombol-panel-penanda', () => {
		if (!panelPenanda) return
		const terbuka = panelPenanda.classList.contains('hidden')
		aturPanelPenanda(terbuka)
	})

	if (isianHalaman) {
		isianHalaman.onchange = () => keHalaman(parseInt(isianHalaman.value) || 1)
	}

	// Panah kiri/kanan untuk berpindah halaman.
	document.addEventListener('keydown', (peristiwa) => {
		if (peristiwa.target.tagName === 'INPUT') return
		if (peristiwa.key === 'ArrowRight') keHalaman(halamanAktif + 1)
		if (peristiwa.key === 'ArrowLeft') keHalaman(halamanAktif - 1)
	})

	if (tombolUnduh) {
		tombolUnduh.onclick = () => susunUnduhan(tombolUnduh, data, bytesAsli)
	}

	// Simpan kemajuan membaca setiap kali halaman berpindah (debounce 2 detik).
	let timerProgres = null
	let progresTertunda = null
	let siapSimpan = false

	function kirimProgres(payload) {
		if (!data.urlProgres) return
		fetch(data.urlProgres, {
			method: 'POST',
			credentials: 'same-origin',
			keepalive: true,
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': data.csrf,
				Accept: 'application/json',
			},
			body: JSON.stringify(payload),
		}).catch((galat) => { if (import.meta.env.DEV) console.warn('Gagal simpan progres:', galat) })
	}

	function simpanProgres(nomor, total) {
		if (!siapSimpan || !data.urlProgres) return
		progresTertunda = { halaman: nomor, total }
		clearTimeout(timerProgres)
		timerProgres = setTimeout(() => {
			kirimProgres(progresTertunda)
			progresTertunda = null
		}, 2000)
	}

	// Flush saat tab disembunyikan atau ditutup agar progres terakhir tidak hilang.
	// sendBeacon tidak mendukung header CSRF, jadi dipakai fetch dengan keepalive: true.
	document.addEventListener('visibilitychange', () => {
		if (document.visibilityState === 'hidden' && progresTertunda) {
			clearTimeout(timerProgres)
			kirimProgres(progresTertunda)
			progresTertunda = null
		}
	})

	// Pasang tombol penanda bila ada di halaman.
	pasangKlik('tombol-penanda', () => {
		if (!data.urlPenanda) return
		postJson(data.urlPenanda, data.csrf, { halaman: halamanAktif })
			.then(async (res) => {
				if (res.status === 429) {
					if (import.meta.env.DEV) console.info('Penanda: terlalu banyak permintaan, dilewati.')
					return
				}
				if (!res.ok) throw new Error(`Server menjawab ${res.status}`)
				const hasil = await res.json()
				pulihkanPenanda(Array.isArray(hasil.penanda) ? hasil.penanda : [])
			})
			.catch((galat) => console.warn('Gagal mengubah penanda:', galat))
	})

	const halamanAwal = Math.min(
		Math.max(1, Number.parseInt(dataAwal?.halamanTerakhir, 10) || 1),
		dokumen.numPages,
	)
	const kerangkaSiap = await siapkanKerangkaHalaman()
	if (!kerangkaSiap) {
		tampilkanStatus('Wadah halaman tidak ditemukan.')
		return
	}
	siapkanObserverRender()
	tampilkanStatus('')
	pulihkanPenanda(Array.isArray(dataAwal?.penanda) ? dataAwal.penanda : [])
	aturPanelPenanda(false)
	jadwalkanSinkronisasiRender()
	const tujuanAwal = gulirKeHalaman(halamanAwal, 'auto')
	if (tujuanAwal) {
		await tungguGulirSelesai(tujuanAwal)
		perbaruiHalamanAktif(tujuanAwal, false)
		jadwalkanSinkronisasiRender()
	}
	siapkanObserverHalamanAktif()
	siapSimpan = true
}

/** Menyusun berkas unduhan sesuai rentang halaman dan membubuhkan watermark. */
async function susunUnduhan(tombol, data, bytesAsli) {
	const labelAwal = tombol.textContent
	tombol.disabled = true
	tombol.textContent = 'Menyiapkan berkas…'

	try {
		const sumber = await PDFDocument.load(bytesAsli.slice(0))
		const hasil = await PDFDocument.create()
		const total = sumber.getPageCount()

		const awal = data.halAwal ? Math.max(1, parseInt(data.halAwal)) : 1
		const akhir = data.halAkhir ? Math.min(total, parseInt(data.halAkhir)) : total

		const indeks = []
		for (let i = awal; i <= akhir; i++) indeks.push(i - 1)

		const halamanTersalin = await hasil.copyPages(sumber, indeks)
		const font = await hasil.embedFont(StandardFonts.Helvetica)

		halamanTersalin.forEach((halaman) => {
			hasil.addPage(halaman)

			if (!data.watermark) return

			const { width, height } = halaman.getSize()

			// Cap miring di tengah halaman.
			halaman.drawText(data.watermark, {
				x: width * 0.12,
				y: height * 0.4,
				size: Math.min(width, height) * 0.042,
				font,
				color: rgb(0.57, 0.22, 0.07),
				opacity: 0.2,
				rotate: degrees(32),
			})

			// Keterangan kecil di kaki halaman.
			if (data.watermarkKaki) {
				halaman.drawText(data.watermarkKaki, {
					x: 24,
					y: 18,
					size: 8,
					font,
					color: rgb(0.45, 0.42, 0.4),
					opacity: 0.75,
				})
			}
		})

		const bytes = await hasil.save()
		const tautan = document.createElement('a')
		tautan.href = URL.createObjectURL(new Blob([bytes], { type: 'application/pdf' }))
		tautan.download = data.namaBerkas
		tautan.click()
		URL.revokeObjectURL(tautan.href)

		// Catat ke server untuk keperluan statistik dosen.
		await postJson(data.urlCatat, data.csrf)

		tombol.textContent = 'Berkas tersimpan'
		setTimeout(() => { tombol.textContent = labelAwal }, 2500)
	} catch (galat) {
		console.error('Gagal menyiapkan berkas unduhan:', galat)
		tombol.textContent = 'Gagal menyiapkan berkas'
		setTimeout(() => { tombol.textContent = labelAwal }, 2500)
	} finally {
		tombol.disabled = false
	}
}

document.addEventListener('DOMContentLoaded', () => {
	const wadah = document.getElementById('pembaca-pdf')
	if (wadah) siapkanPembaca(wadah)
})