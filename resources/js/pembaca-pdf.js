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
	const kanvas = document.getElementById('kanvas-halaman')
	const konteks = kanvas.getContext('2d')
	const status = document.getElementById('status-pembaca')
	const isianHalaman = document.getElementById('isian-halaman')
	const totalHalaman = document.getElementById('total-halaman')
	const tombolUnduh = document.getElementById('tombol-unduh')

	let dokumen = null
	let halamanAktif = 1
	let skala = 1.3
	let sedangGambar = false
	let bytesAsli = null

	/** Menampilkan pesan di bilah status, dan menyembunyikannya bila tidak ada pesan. */
	function tampilkanStatus(teks) {
		if (!status) return

		status.textContent = teks
		status.classList.toggle('hidden', teks === '')
	}

	// Berkas diambil sekali, lalu dipakai ulang untuk membaca maupun mengunduh.
	tampilkanStatus('Memuat berkas…')

	try {
		const respons = await fetch(data.urlBerkas, { credentials: 'same-origin' })

		if (!respons.ok) {
			throw new Error(`Server menjawab ${respons.status}`)
		}

		bytesAsli = await respons.arrayBuffer()
	} catch (galat) {
		console.error('Gagal mengambil berkas PDF:', galat)
		tampilkanStatus(`Berkas tidak dapat dimuat (${galat.message}). Coba muat ulang halaman.`)
		return
	}

	// Dokumen dibuka dari salinan bytes karena pdf.js memindahkan kepemilikan buffer.
	try {
		const tugas = pdfjsLib.getDocument({
			data: bytesAsli.slice(0),
			...ASET_PDFJS,
		})

		dokumen = await tugas.promise
	} catch (galat) {
		console.error('Gagal membuka dokumen PDF:', galat)
		tampilkanStatus(`Berkas PDF tidak dapat dibuka (${galat.message}).`)
		return
	}

	if (import.meta.env.DEV) {
		console.info('pdf.js siap. Versi:', pdfjsLib.version, '· Jumlah halaman:', dokumen.numPages)
	}

	if (totalHalaman) totalHalaman.textContent = dokumen.numPages
	if (isianHalaman) isianHalaman.max = dokumen.numPages
	tampilkanStatus('')

	/** Menggambar satu halaman ke kanvas, lengkap dengan watermark layar. */
	async function gambarHalaman(nomor) {
		if (sedangGambar) return
		sedangGambar = true

		try {
			const halaman = await dokumen.getPage(nomor)
			const tampilan = halaman.getViewport({ scale: skala })

			// Ukuran kanvas disetel sebelum menggambar; ini otomatis mengosongkan isinya.
			kanvas.width = Math.floor(tampilan.width)
			kanvas.height = Math.floor(tampilan.height)
			kanvas.style.width = '100%'
			kanvas.style.height = 'auto'

			// Latar putih dipasang lebih dulu agar halaman transparan tetap terbaca.
			konteks.save()
			konteks.fillStyle = '#ffffff'
			konteks.fillRect(0, 0, kanvas.width, kanvas.height)
			konteks.restore()

			// Properti canvas disertakan agar tetap sesuai dengan pdf.js versi 5.
			const tugas = halaman.render({
				canvas: kanvas,
				canvasContext: konteks,
				viewport: tampilan,
			})

			await tugas.promise

			// Pemeriksaan bantu: memastikan halaman benar-benar berisi sesuatu.
			const operasi = await halaman.getOperatorList()
			if (import.meta.env.DEV) {
				console.info(`Halaman ${nomor} digambar. Jumlah operasi gambar:`, operasi.fnArray.length)
			}

			if (data.watermark) {
				gambarWatermarkLayar(kanvas.width, kanvas.height)
			}

			if (isianHalaman) isianHalaman.value = nomor
			tampilkanStatus('')
		} catch (galat) {
			console.error('Gagal menggambar halaman PDF:', galat)
			tampilkanStatus(`Halaman gagal ditampilkan (${galat.message}).`)
		} finally {
			// Wajib dikembalikan agar penampil tidak membeku setelah satu kegagalan.
			sedangGambar = false
		}
	}

	/** Cap miring semi transparan sebagai pengingat kepemilikan. */
	function gambarWatermarkLayar(lebar, tinggi) {
		konteks.save()
		konteks.translate(lebar / 2, tinggi / 2)
		konteks.rotate(-Math.PI / 7)
		konteks.font = `${Math.round(Math.min(lebar, tinggi) * 0.05)}px sans-serif`
		konteks.fillStyle = 'rgba(146, 56, 17, 0.16)'
		konteks.textAlign = 'center'
		konteks.fillText(data.watermark, 0, 0)
		konteks.restore()
	}

	/** Berpindah halaman dengan menjaga batas atas dan bawah. */
	function keHalaman(nomor) {
		halamanAktif = Math.min(Math.max(1, nomor), dokumen.numPages)
		gambarHalaman(halamanAktif)
		simpanProgres(halamanAktif, dokumen.numPages)
	}

	/** Memasang penangan klik hanya bila tombolnya benar-benar ada. */
	function pasangKlik(id, penangan) {
		const tombol = document.getElementById(id)
		if (tombol) tombol.onclick = penangan
	}

	pasangKlik('tombol-sebelum', () => keHalaman(halamanAktif - 1))
	pasangKlik('tombol-sesudah', () => keHalaman(halamanAktif + 1))

	pasangKlik('tombol-perbesar', () => {
		skala = Math.min(skala + 0.25, 3)
		gambarHalaman(halamanAktif)
	})

	pasangKlik('tombol-perkecil', () => {
		skala = Math.max(skala - 0.25, 0.6)
		gambarHalaman(halamanAktif)
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
		if (!data.urlProgres) return
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
			.then((res) => { if (res.status === 429 && import.meta.env.DEV) console.info('Penanda: terlalu banyak permintaan, dilewati.') })
			.catch((galat) => console.warn('Gagal mengubah penanda:', galat))
	})

	keHalaman(1)
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