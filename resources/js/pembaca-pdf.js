import * as pdfjsLib from 'pdfjs-dist'
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url'

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

/**
 * Helper terpusat untuk semua DELETE JSON ke server.
 */
async function deleteJson(url, csrf, body = {}) {
	return fetch(url, {
		method: 'DELETE',
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
	const labelZoom = document.getElementById('label-zoom')

	// Elemen Penanda
	const tombolPenanda = document.getElementById('tombol-penanda')
	const ikonPenandaOutline = document.getElementById('ikon-penanda-outline')
	const ikonPenandaIsi = document.getElementById('ikon-penanda-isi')

	// Elemen Catatan
	const tombolCatatan = document.getElementById('tombol-catatan')
	const dotCatatanAktif = document.getElementById('dot-catatan-aktif')
	const modalCatatan = document.getElementById('modal-catatan')
	const judulHalamanCatatan = document.getElementById('judul-halaman-catatan')
	const isianTeksCatatan = document.getElementById('isian-teks-catatan')
	const infoWaktuCatatan = document.getElementById('info-waktu-catatan')
	const tombolSimpanCatatanModal = document.getElementById('tombol-simpan-catatan-modal')
	const tombolHapusCatatanModal = document.getElementById('tombol-hapus-catatan-modal')
	const tombolTutupModalCatatan = document.getElementById('tombol-tutup-modal-catatan')
	const tombolBatalCatatan = document.getElementById('tombol-batal-catatan')

	// Elemen Drawer
	const drawerPenanda = document.getElementById('drawer-penanda')
	const drawerBackdrop = document.getElementById('drawer-backdrop')
	const drawerPanel = document.getElementById('drawer-panel')
	const tombolBukaDrawer = document.getElementById('tombol-buka-drawer')
	const tombolTutupDrawer = document.getElementById('tombol-tutup-drawer')
	const tabDrawerPenanda = document.getElementById('tab-drawer-penanda')
	const tabDrawerCatatan = document.getElementById('tab-drawer-catatan')
	const kontenTabPenanda = document.getElementById('konten-tab-penanda')
	const kontenTabCatatan = document.getElementById('konten-tab-catatan')
	const jumlahPenandaTab = document.getElementById('jumlah-penanda-tab')
	const jumlahCatatanTab = document.getElementById('jumlah-catatan-tab')
	const daftarPenandaEl = document.getElementById('daftar-penanda')
	const pesanPenandaKosong = document.getElementById('pesan-penanda-kosong')
	const daftarCatatanEl = document.getElementById('daftar-catatan')
	const pesanCatatanKosong = document.getElementById('pesan-catatan-kosong')
	const tombolTambahCatatanDrawer = document.getElementById('tombol-tambah-catatan-drawer')

	// Elemen Mode Fokus & Lebar Penuh
	const tombolFokus = document.getElementById('tombol-fokus')
	const ikonFokusMasuk = document.getElementById('ikon-fokus-masuk')
	const ikonFokusKeluar = document.getElementById('ikon-fokus-keluar')
	const tombolLebarPenuh = document.getElementById('tombol-lebar-penuh')

	let dokumen = null
	let halamanAktif = 1
	let skala = 1.3
	let bytesAsli = null
	let observerRender = null
	let observerHalamanAktif = null
	let sedangUbahSkala = false
	let sinkronisasiTerjadwal = false
	let modeFokusAktif = false
	let ukuranPlaceholder = { lebar: 0, tinggi: 0, rasio: 1 }
	let pemicuOverlay = null

	// Pengelolaan fokus overlay: elemen yang bisa menerima fokus di dalam
	// sebuah wadah, untuk memindahkan dan mengunci siklus Tab.
	const fokusableDalam = (akar) =>
		[...akar.querySelectorAll('button, a[href], input, textarea')]
			.filter((el) => ! el.disabled && el.offsetParent !== null)

	function fokusPertama(wadah) {
		fokusableDalam(wadah)[0]?.focus()
	}

	const daftarPenanda = new Set()
	const daftarCatatan = new Map() // page -> { id, halaman, isi, waktu }
	const halamanTerlihat = new Set()
	const halamanDenganKanvas = new Set()
	const rasioTerlihat = new Map()
	const keadaanHalaman = new Map()

	function perbaruiLabelZoom() {
		if (labelZoom) {
			labelZoom.textContent = `${Math.round(skala * 100)}%`
		}
	}

	/** Menampilkan pesan di bilah status, dan menyembunyikannya bila tidak ada pesan. */
	function tampilkanStatus(teks) {
		if (!status) return
		status.textContent = teks
		status.classList.toggle('hidden', teks === '')
	}

	tampilkanStatus('Memuat berkas…')

	async function muatDokumen() {
		try {
			const respons = await fetch(data.urlBerkas, { credentials: 'same-origin' })
			if (!respons.ok) throw new Error(`Server menjawab ${respons.status}`)
			bytesAsli = await respons.arrayBuffer()
		} catch (galat) {
			console.error('Gagal mengambil berkas PDF:', galat)
			tampilkanStatus(`Berkas tidak dapat dimuat (${galat.message}). Coba muat ulang halaman.`)
			throw galat
		}

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
	perbaruiLabelZoom()

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
			elemen.className = 'mx-auto mb-6 overflow-hidden bg-white rounded shadow-md transition-shadow'
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
			teks.className = 'text-sm text-netral-500'
			teks.textContent = `Halaman ${nomor} gagal dimuat`
			const tombol = document.createElement('button')
			tombol.type = 'button'
			tombol.className = 'rounded-sm border border-netral-300 px-3 py-1.5 text-sm font-medium text-netral-700 hover:bg-netral-100'
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
		perbaruiStatusCatatan()
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

	// =========================================================================
	// PENGELOLAAN PENANDA HALAMAN (BOOKMARK)
	// =========================================================================
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
		tombolPenanda.setAttribute('title', aktif ? 'Hapus penanda halaman ini (B)' : 'Tandai halaman ini (B)')
		tombolPenanda.setAttribute('aria-pressed', aktif ? 'true' : 'false')
		if (ikonPenandaOutline) ikonPenandaOutline.classList.toggle('hidden', aktif)
		if (ikonPenandaIsi) ikonPenandaIsi.classList.toggle('hidden', !aktif)
		renderDaftarPenanda()
	}

	function renderDaftarPenanda() {
		if (!daftarPenandaEl || !pesanPenandaKosong) return

		const urut = Array.from(daftarPenanda).sort((a, b) => a - b)
		daftarPenandaEl.innerHTML = ''
		if (jumlahPenandaTab) jumlahPenandaTab.textContent = urut.length
		pesanPenandaKosong.classList.toggle('hidden', urut.length > 0)

		urut.forEach((nomor) => {
			const item = document.createElement('li')
			const tombol = document.createElement('button')
			tombol.type = 'button'
			tombol.className = 'rounded-md border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700 px-3.5 py-2 text-xs font-semibold text-netral-800 dark:text-netral-200 hover:border-jingga-500 hover:text-jingga-600 transition shadow-sm'
			tombol.innerHTML = `Halaman <span class="font-display text-sm font-bold text-jingga-600 dark:text-jingga-400">${nomor}</span>`
			if (nomor === halamanAktif) {
				tombol.classList.add('ring-2', 'ring-jingga-500', 'bg-jingga-50/50')
			}
			tombol.onclick = () => {
				keHalaman(nomor)
				tutupDrawer()
			}
			item.appendChild(tombol)
			daftarPenandaEl.appendChild(item)
		})
	}

	// =========================================================================
	// PENGELOLAAN CATATAN BELAJAR (PAGE NOTES)
	// =========================================================================
	function pulihkanCatatan(daftar) {
		daftarCatatan.clear()
		if (Array.isArray(daftar)) {
			daftar.forEach((c) => {
				const hal = Number.parseInt(c.halaman, 10)
				if (Number.isInteger(hal)) {
					daftarCatatan.set(hal, c)
				}
			})
		}
		perbaruiStatusCatatan()
	}

	function perbaruiStatusCatatan() {
		const ada = daftarCatatan.has(halamanAktif)
		if (dotCatatanAktif) {
			dotCatatanAktif.classList.toggle('hidden', !ada)
		}
		renderDaftarCatatan()
	}

	function bukaModalCatatan(halaman = halamanAktif) {
		if (!modalCatatan) return

		// Simpan pemicunya supaya fokus bisa pulih saat modal ditutup.
		pemicuOverlay = document.activeElement

		if (judulHalamanCatatan) judulHalamanCatatan.textContent = halaman

		const catatanAda = daftarCatatan.get(halaman)
		if (isianTeksCatatan) {
			isianTeksCatatan.value = catatanAda ? catatanAda.isi : ''
		}
		if (infoWaktuCatatan) {
			infoWaktuCatatan.textContent = catatanAda && catatanAda.waktu ? `Terakhir disimpan: ${catatanAda.waktu}` : ''
		}
		if (tombolHapusCatatanModal) {
			tombolHapusCatatanModal.classList.toggle('hidden', !catatanAda)
		}

		modalCatatan.classList.remove('hidden')
		modalCatatan.classList.add('flex')
		if (isianTeksCatatan) isianTeksCatatan.focus()
	}

	function tutupModalCatatan() {
		if (!modalCatatan) return
		modalCatatan.classList.add('hidden')
		modalCatatan.classList.remove('flex')

		pemicuOverlay?.focus?.()
		pemicuOverlay = null
	}

	async function simpanCatatanAktif() {
		if (!data.urlCatatanSimpan || !isianTeksCatatan) return
		const isi = isianTeksCatatan.value.trim()
		const hal = halamanAktif

		if (!isi) {
			// Jika kosong dan sebelumnya ada catatan, hapus catatan
			if (daftarCatatan.has(hal)) {
				await hapusCatatanAktif()
			}
			tutupModalCatatan()
			return
		}

		try {
			if (tombolSimpanCatatanModal) tombolSimpanCatatanModal.disabled = true
			const res = await postJson(data.urlCatatanSimpan, data.csrf, { halaman: hal, isi })
			if (!res.ok) throw new Error(`Server menjawab ${res.status}`)
			const hasil = await res.json()
			pulihkanCatatan(hasil.catatan || [])
			tutupModalCatatan()
		} catch (galat) {
			console.warn('Gagal menyimpan catatan:', galat)
			alert('Catatan gagal disimpan. Silakan coba lagi.')
		} finally {
			if (tombolSimpanCatatanModal) tombolSimpanCatatanModal.disabled = false
		}
	}

	async function hapusCatatanAktif() {
		if (!data.urlCatatanHapus) return
		const hal = halamanAktif

		try {
			const res = await deleteJson(data.urlCatatanHapus, data.csrf, { halaman: hal })
			if (!res.ok) throw new Error(`Server menjawab ${res.status}`)
			const hasil = await res.json()
			pulihkanCatatan(hasil.catatan || [])
			tutupModalCatatan()
		} catch (galat) {
			console.warn('Gagal menghapus catatan:', galat)
		}
	}

	function renderDaftarCatatan() {
		if (!daftarCatatanEl || !pesanCatatanKosong) return

		const list = Array.from(daftarCatatan.values()).sort((a, b) => a.halaman - b.halaman)
		daftarCatatanEl.innerHTML = ''
		if (jumlahCatatanTab) jumlahCatatanTab.textContent = list.length
		pesanCatatanKosong.classList.toggle('hidden', list.length > 0)

		list.forEach((item) => {
			const card = document.createElement('div')
			card.className = 'rounded-xl border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700/70 p-4 shadow-sm hover:border-jingga-500/50 transition cursor-pointer'
			card.onclick = () => {
				keHalaman(item.halaman)
				tutupDrawer()
			}

			const head = document.createElement('div')
			head.className = 'flex items-center justify-between mb-2'

			const badge = document.createElement('span')
			badge.className = 'rounded bg-jingga-50 dark:bg-jingga-900/40 border border-jingga-200 dark:border-jingga-700/50 px-2.5 py-0.5 text-xs font-semibold text-jingga-700 dark:text-jingga-300'
			badge.textContent = `Halaman ${item.halaman}`

			const waktu = document.createElement('span')
			waktu.className = 'text-[11px] text-netral-400'
			waktu.textContent = item.waktu || ''

			head.appendChild(badge)
			head.appendChild(waktu)

			const teks = document.createElement('p')
			teks.className = 'text-xs leading-relaxed text-netral-700 dark:text-netral-200 line-clamp-3 whitespace-pre-line'
			teks.textContent = item.isi

			card.appendChild(head)
			card.appendChild(teks)
			daftarCatatanEl.appendChild(card)
		})
	}

	// =========================================================================
	// SMART DRAWER (SLIDE-OVER PANEL)
	// =========================================================================
	// Penahan penutupan disimpan: bila drawer dibuka lagi sebelum transisi
	// 300 ms selesai, penundaan lama wajib dibatalkan — kalau tidak, jadwal
	// lama menyembunyikan drawer yang baru saja dibuka.
	let timerTutupDrawer = null

	function bukaDrawer(tab = 'penanda') {
		if (!drawerPenanda || !drawerBackdrop || !drawerPanel) return

		clearTimeout(timerTutupDrawer)

		pemicuOverlay = document.activeElement

		drawerPenanda.classList.remove('hidden')
		requestAnimationFrame(() => {
			drawerBackdrop.classList.remove('opacity-0')
			drawerBackdrop.classList.add('opacity-100')
			drawerPanel.classList.remove('translate-x-full')
			drawerPanel.classList.add('translate-x-0')
		})
		setTabDrawer(tab)
		fokusPertama(drawerPanel)
	}

	function tutupDrawer() {
		if (!drawerPenanda || !drawerBackdrop || !drawerPanel) return
		drawerBackdrop.classList.remove('opacity-100')
		drawerBackdrop.classList.add('opacity-0')
		drawerPanel.classList.remove('translate-x-0')
		drawerPanel.classList.add('translate-x-full')
		clearTimeout(timerTutupDrawer)
		timerTutupDrawer = setTimeout(() => {
			drawerPenanda.classList.add('hidden')
		}, 300)

		if (modalCatatan && modalCatatan.classList.contains('hidden')) {
			// Modal catatan yang dibuka dari drawer menimpa pemicunya;
			// pemulihan hanya dilakukan bila tidak ada overlay penerus.
			pemicuOverlay?.focus?.()
			pemicuOverlay = null
		}
	}

	function setTabDrawer(tab) {
		if (!tabDrawerPenanda || !tabDrawerCatatan || !kontenTabPenanda || !kontenTabCatatan) return
		if (tab === 'penanda') {
			tabDrawerPenanda.classList.add('border-jingga-600', 'text-jingga-600', 'dark:text-jingga-400', 'bg-white', 'dark:bg-arang-800')
			tabDrawerPenanda.classList.remove('border-transparent', 'text-netral-500')
			tabDrawerCatatan.classList.remove('border-jingga-600', 'text-jingga-600', 'dark:text-jingga-400', 'bg-white', 'dark:bg-arang-800')
			tabDrawerCatatan.classList.add('border-transparent', 'text-netral-500')
			kontenTabPenanda.classList.remove('hidden')
			kontenTabCatatan.classList.add('hidden')
		} else {
			tabDrawerCatatan.classList.add('border-jingga-600', 'text-jingga-600', 'dark:text-jingga-400', 'bg-white', 'dark:bg-arang-800')
			tabDrawerCatatan.classList.remove('border-transparent', 'text-netral-500')
			tabDrawerPenanda.classList.remove('border-jingga-600', 'text-jingga-600', 'dark:text-jingga-400', 'bg-white', 'dark:bg-arang-800')
			tabDrawerPenanda.classList.add('border-transparent', 'text-netral-500')
			kontenTabCatatan.classList.remove('hidden')
			kontenTabPenanda.classList.add('hidden')
		}
	}

	// =========================================================================
	// MODE FOKUS (ZEN / FULLSCREEN MODE)
	// =========================================================================
	function toggleModeFokus() {
		modeFokusAktif = !modeFokusAktif
		const headerBaca = document.getElementById('header-baca')
		const infoAturan = document.getElementById('info-aturan-baca')
		const navUtama = document.querySelector('nav')

		if (ikonFokusMasuk) ikonFokusMasuk.classList.toggle('hidden', modeFokusAktif)
		if (ikonFokusKeluar) ikonFokusKeluar.classList.toggle('hidden', !modeFokusAktif)

		if (modeFokusAktif) {
			if (navUtama) navUtama.classList.add('hidden')
			if (headerBaca) headerBaca.classList.add('hidden')
			if (infoAturan) infoAturan.classList.add('hidden')
			if (tombolFokus) tombolFokus.classList.add('bg-jingga-50', 'text-jingga-600', 'border-jingga-500')
		} else {
			if (navUtama) navUtama.classList.remove('hidden')
			if (headerBaca) headerBaca.classList.remove('hidden')
			if (infoAturan) infoAturan.classList.remove('hidden')
			if (tombolFokus) tombolFokus.classList.remove('bg-jingga-50', 'text-jingga-600', 'border-jingga-500')
		}

		// Sesuaikan ulang render setelah layout berubah
		jadwalkanSinkronisasiRender()
	}

	// =========================================================================
	// SESUAIKAN LEBAR (FIT TO WIDTH)
	// =========================================================================
	async function sesuaikanLebar() {
		if (sedangUbahSkala || !wadah) return
		const halamanPertama = await dokumen.getPage(1)
		const viewportAsli = halamanPertama.getViewport({ scale: 1.0 })
		const lebarWadah = wadah.clientWidth - 48 // margin dalam
		if (lebarWadah <= 0) return

		const rasioSkala = Math.min(Math.max(lebarWadah / viewportAsli.width, 0.6), 2.8)
		skala = Math.round(rasioSkala * 100) / 100
		perbaruiLabelZoom()

		sedangUbahSkala = true
		const target = halamanAktif
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

	// =========================================================================
	// SKALA ZOOM
	// =========================================================================
	async function ubahSkala(delta) {
		if (sedangUbahSkala) return

		const skalaBaru = Math.min(Math.max(Math.round((skala + delta) * 100) / 100, 0.6), 3)
		if (skalaBaru === skala) return

		sedangUbahSkala = true
		const target = halamanAktif
		skala = skalaBaru
		perbaruiLabelZoom()

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

	/** Memasang penangan klik hanya bila tombolnya benar-benar ada. */
	function pasangKlik(id, penangan) {
		const tombol = document.getElementById(id)
		if (tombol) tombol.onclick = penangan
	}

	// Navigasi Halaman
	pasangKlik('tombol-sebelum', () => keHalaman(halamanAktif - 1))
	pasangKlik('tombol-sesudah', () => keHalaman(halamanAktif + 1))
	if (isianHalaman) {
		isianHalaman.onchange = () => keHalaman(parseInt(isianHalaman.value) || 1)
	}

	// Zoom & Fit
	pasangKlik('tombol-perbesar', () => { void ubahSkala(0.2) })
	pasangKlik('tombol-perkecil', () => { void ubahSkala(-0.2) })
	pasangKlik('tombol-lebar-penuh', () => { void sesuaikanLebar() })

	// Mode Fokus
	pasangKlik('tombol-fokus', toggleModeFokus)

	// Bookmark Toggle
	pasangKlik('tombol-penanda', () => {
		if (!data.urlPenanda) return
		postJson(data.urlPenanda, data.csrf, { halaman: halamanAktif })
			.then(async (res) => {
				if (res.status === 429) return
				if (!res.ok) throw new Error(`Server menjawab ${res.status}`)
				const hasil = await res.json()
				pulihkanPenanda(Array.isArray(hasil.penanda) ? hasil.penanda : [])
			})
			.catch((galat) => console.warn('Gagal mengubah penanda:', galat))
	})

	// Catatan Modal Actions
	pasangKlik('tombol-catatan', () => bukaModalCatatan(halamanAktif))
	pasangKlik('tombol-tutup-modal-catatan', tutupModalCatatan)
	pasangKlik('tombol-batal-catatan', tutupModalCatatan)
	pasangKlik('tombol-simpan-catatan-modal', simpanCatatanAktif)
	pasangKlik('tombol-hapus-catatan-modal', hapusCatatanAktif)

	// Drawer Actions
	pasangKlik('tombol-buka-drawer', () => bukaDrawer('penanda'))
	pasangKlik('tombol-tutup-drawer', tutupDrawer)
	pasangKlik('drawer-backdrop', tutupDrawer)
	pasangKlik('tab-drawer-penanda', () => setTabDrawer('penanda'))
	pasangKlik('tab-drawer-catatan', () => setTabDrawer('catatan'))
	pasangKlik('tombol-tambah-catatan-drawer', () => {
		tutupDrawer()
		bukaModalCatatan(halamanAktif)
	})

	// Keyboard Shortcuts
	document.addEventListener('keydown', (peristiwa) => {
		// Jebakan Tab: selama drawer atau modal catatan terbuka, siklus
		// fokus dikunci di dalam wadahnya — keyboard tidak boleh lolos
		// ke halaman di belakang overlay.
		if (peristiwa.key === 'Tab') {
			const overlay =
				modalCatatan && ! modalCatatan.classList.contains('hidden')
					? modalCatatan
					: drawerPenanda && ! drawerPenanda.classList.contains('hidden')
						? drawerPanel
						: null

			if (overlay) {
				const milik = fokusableDalam(overlay)
				if (milik.length) {
					const pertama = milik[0]
					const terakhir = milik[milik.length - 1]

					if (peristiwa.shiftKey && document.activeElement === pertama) {
						peristiwa.preventDefault()
						terakhir.focus()
					} else if (! peristiwa.shiftKey && document.activeElement === terakhir) {
						peristiwa.preventDefault()
						pertama.focus()
					}
				}
			}

			return
		}

		if (peristiwa.target.tagName === 'INPUT' || peristiwa.target.tagName === 'TEXTAREA') {
			if (peristiwa.key === 'Escape') {
				tutupModalCatatan()
				tutupDrawer()
			}
			return
		}

		/*
		 * Selama overlay (modal catatan atau drawer) terbuka, pintasan
		 * pembaca diam: memindah halaman, zoom, penanda, dan mode fokus
		 * tidak boleh menyentuh buku di baliknya. Escape tetap lolos agar
		 * overlay dapat ditutup dari keyboard.
		 */
		const overlayTerbuka =
			(modalCatatan && ! modalCatatan.classList.contains('hidden')) ||
			(drawerPenanda && ! drawerPenanda.classList.contains('hidden'))

		if (overlayTerbuka && peristiwa.key !== 'Escape') {
			return
		}

		if (peristiwa.key === 'ArrowRight') keHalaman(halamanAktif + 1)
		if (peristiwa.key === 'ArrowLeft') keHalaman(halamanAktif - 1)

		// Pintasan zoom yang dijanjikan tooltip toolbar. Hanya untuk tombol
		// polos — Ctrl/Alt +/− tetap milik zoom bawaan browser.
		if (!peristiwa.ctrlKey && !peristiwa.metaKey && !peristiwa.altKey) {
			if (peristiwa.key === '+' || peristiwa.key === '=') {
				void ubahSkala(0.2)
				return
			}
			if (peristiwa.key === '-' || peristiwa.key === '_') {
				void ubahSkala(-0.2)
				return
			}
		}

		if (peristiwa.key === 'f' || peristiwa.key === 'F') toggleModeFokus()
		if (peristiwa.key === 'b' || peristiwa.key === 'B') {
			const btn = document.getElementById('tombol-penanda')
			if (btn) btn.click()
		}
		if (peristiwa.key === 'n' || peristiwa.key === 'N') bukaModalCatatan(halamanAktif)
		if (peristiwa.key === 'w' || peristiwa.key === 'W') { void sesuaikanLebar() }
		if (peristiwa.key === 'Escape') {
			if (modalCatatan && !modalCatatan.classList.contains('hidden')) {
				tutupModalCatatan()
			} else if (drawerPenanda && !drawerPenanda.classList.contains('hidden')) {
				tutupDrawer()
			} else if (modeFokusAktif) {
				toggleModeFokus()
			}
		}
	})

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

	document.addEventListener('visibilitychange', () => {
		if (document.visibilityState === 'hidden' && progresTertunda) {
			clearTimeout(timerProgres)
			kirimProgres(progresTertunda)
			progresTertunda = null
		}
	})

	// Prioritas halaman awal: parameter ?halaman= dari tautan penanda
	// ("lanjut ke halaman X") menyatakan niat yang lebih spesifik daripada
	// progres tersimpan, jadi ia menang bila keduanya ada.
	const paramHalaman = Number.parseInt(
		new URLSearchParams(window.location.search).get('halaman'),
		10,
	)
	const halamanAwal = Math.min(
		Math.max(
			1,
			Number.isNaN(paramHalaman)
				? Number.parseInt(dataAwal?.halamanTerakhir, 10) || 1
				: paramHalaman,
		),
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
	pulihkanCatatan(Array.isArray(dataAwal?.catatan) ? dataAwal.catatan : [])
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

const nyalakanPembaca = () => {
	const wadah = document.getElementById('pembaca-pdf')
	if (wadah) siapkanPembaca(wadah)
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', nyalakanPembaca, { once: true })
} else {
	nyalakanPembaca()
}