/**
 * Umpan balik kemajuan untuk formulir unggah buku.
 *
 * Formulir bertanda data-progres-unggah dikirim lewat XMLHttpRequest agar
 * persentase unggahan sungguhan bisa ditampilkan — berkas 30 MB tanpa
 * indikasi apa pun terasa seperti macet. Bila JavaScript gagal berjalan,
 * pengiriman native tetap bekerja seperti biasa karena pengambilalihan
 * hanya terjadi pada event submit.
 *
 * Jalur ini bergantung pada tombol konfirmasi yang memicu requestSubmit():
 * pemanggilan .submit() langsung TIDAK menembakkan event submit.
 */

const PESAN_GALAT_JARINGAN =
	'Koneksi ke server terputus saat mengunggah. Periksa sambungan Anda lalu coba lagi.'

function pasangUnggah(form) {
	const progresWadah = document.getElementById('progres-unggah')
	const progresBar = document.getElementById('progres-bar')
	const progresLacak = document.getElementById('progres-bar-lacak')
	const progresLabel = document.getElementById('progres-label')
	const progresPersen = document.getElementById('progres-persen')
	const galatWadah = document.getElementById('galat-unggah')

	if (!progresWadah) return

	let sedangMengunggah = false
	let peringatanTutup = null

	function kunciFormulir(terkunci) {
		// Pemicu konfirmasi dan tautan Batal hidup di luar elemen <form>,
		// jadi keduanya ikut dikunci lewat wadah footernya.
		const wadahFooter = document.getElementById('footer-form-buku')
		const sasaran = wadahFooter ? [form, wadahFooter] : [form]

		sasaran.forEach((wadah) => {
			wadah.querySelectorAll('button, a[href]').forEach((elemen) => {
				if (elemen.disabled !== undefined) elemen.disabled = terkunci
				elemen.classList.toggle('pointer-events-none', terkunci)
				elemen.classList.toggle('opacity-50', terkunci)
			})
		})
	}

	function tampilkanGalat(daftarPesan) {
		if (!galatWadah) return

		galatWadah.replaceChildren()

		const judul = document.createElement('p')
		judul.className = 'font-semibold'
		judul.textContent = 'Unggahan belum dapat disimpan:'

		const daftar = document.createElement('ul')
		daftar.className = 'mt-2 list-disc space-y-1 pl-5'

		for (const pesan of daftarPesan.slice(0, 8)) {
			const butir = document.createElement('li')
			butir.textContent = pesan
			daftar.appendChild(butir)
		}

		galatWadah.append(judul, daftar)
		galatWadah.classList.remove('hidden')
		galatWadah.scrollIntoView({ behavior: 'smooth', block: 'center' })
	}

	function selesaikan() {
		sedangMengunggah = false
		kunciFormulir(false)
		window.removeEventListener('beforeunload', peringatanTutup)

		if (progresWadah) progresWadah.classList.add('hidden')
	}

	form.addEventListener('submit', (peristiwa) => {
		// Validasi HTML5 sudah lolos di requestSubmit(); dari sini kami yang
		// memegang kendali pengiriman.
		peristiwa.preventDefault()
		if (sedangMengunggah) return

		sedangMengunggah = true
		if (galatWadah) galatWadah.classList.add('hidden')
		kunciFormulir(true)
		if (progresWadah) progresWadah.classList.remove('hidden')
		aturProgres(0)
		if (progresLabel) progresLabel.textContent = 'Mengunggah…'

		peringatanTutup = (acaraKeluar) => {
			acaraKeluar.preventDefault()
			acaraKeluar.returnValue = ''
		}
		window.addEventListener('beforeunload', peringatanTutup)

		const xhr = new XMLHttpRequest()
		xhr.open(form.method.toUpperCase(), form.action, true)
		xhr.setRequestHeader('Accept', 'application/json')

		xhr.upload.onprogress = (kemajuan) => {
			if (!kemajuan.lengthComputable) return

			const persen = Math.round((kemajuan.loaded / kemajuan.total) * 100)
			aturProgres(persen)

			// Seratus persen berarti berkasnya sampai; server masih mengolah
			// (menghitung halaman, menyimpan). Beri tahu dengan jujur.
			if (persen >= 100 && progresLabel) {
				progresLabel.textContent = 'Memproses berkas di server…'
			}
		}

		xhr.onload = () => {
			selesaikan()

			if (xhr.status >= 200 && xhr.status < 400) {
				window.location.assign(xhr.responseURL || form.action)
				return
			}

			if (xhr.status === 422) {
				try {
					const isi = JSON.parse(xhr.responseText)
					tampilkanGalat(Object.values(isi.errors || { g: ['Periksa kembali isian formulir.'] }).flat())
					return
				} catch {
					tampilkanGalat(['Server menolak isian formulir. Muat ulang halaman lalu coba lagi.'])
					return
				}
			}

			tampilkanGalat(
				xhr.status === 0
					? [PESAN_GALAT_JARINGAN]
					: [`Terjadi kesalahan pada server (${xhr.status}). Silakan coba beberapa saat lagi.`],
			)
		}

		xhr.onerror = () => {
			selesaikan()
			tampilkanGalat([PESAN_GALAT_JARINGAN])
		}

		xhr.send(new FormData(form))
	})

	function aturProgres(persen) {
		if (progresBar) progresBar.style.width = `${persen}%`
		if (progresPersen) progresPersen.textContent = `${persen}%`
		if (progresLacak) progresLacak.setAttribute('aria-valuenow', String(persen))
	}
}

function nyalakan() {
	document.querySelectorAll('form[data-progres-unggah]').forEach(pasangUnggah)
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', nyalakan, { once: true })
} else {
	nyalakan()
}
