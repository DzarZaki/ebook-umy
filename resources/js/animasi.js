/**
 * Gerak dan kedalaman untuk halaman mahasiswa.
 *
 * Tanpa paket luar sama sekali. Semuanya IntersectionObserver,
 * pointer event, dan variabel CSS.
 *
 * Aturan yang dipegang seluruh berkas ini:
 * 1. Isi halaman tidak boleh bergantung pada JavaScript agar terlihat.
 * 2. prefers-reduced-motion dipatuhi tanpa tawar-menawar.
 * 3. Tidak ada gerak yang dipasang pada perangkat sentuh bila gerak itu
 *    hanya bermakna dengan kursor.
 */

const KURANGI_GERAK = window.matchMedia('(prefers-reduced-motion: reduce)')
const KURSOR_HALUS = window.matchMedia('(pointer: fine)')

/* ---------------------------------------------------------------------
 * 0. Tanda bahwa JavaScript hidup
 *
 * CSS menyembunyikan [data-muncul] HANYA di dalam .js-gerak. Jadi bila
 * berkas ini gagal dimuat atau melempar galat sebelum baris ini,
 * halaman tetap terbaca seluruhnya — hanya tanpa gerak.
 * ------------------------------------------------------------------- */
document.documentElement.classList.add('js-gerak')

/* ---------------------------------------------------------------------
 * 1. Muncul saat bergulir
 * ------------------------------------------------------------------- */
function siapkanMunculan() {
	const sasaran = document.querySelectorAll('[data-muncul]')
	if (!sasaran.length) return

	// Peramban tanpa IntersectionObserver: tampilkan semuanya sekarang.
	if (!('IntersectionObserver' in window)) {
		sasaran.forEach((el) => el.classList.add('tampil'))
		return
	}

	const pengamat = new IntersectionObserver(
		(entri, pengamatIni) => {
			entri.forEach((satu) => {
				if (!satu.isIntersecting) return
				satu.target.classList.add('tampil')
				// Sekali muncul, selamanya muncul. Elemen yang berkedip
				// setiap kali digulir naik-turun sangat melelahkan.
				pengamatIni.unobserve(satu.target)
			})
		},
		{
			// Dimulai sedikit sebelum benda benar-benar masuk layar,
			// supaya gerakannya sudah selesai ketika mata sampai.
			rootMargin: '0px 0px -12% 0px',
			threshold: 0.08,
		},
	)

	sasaran.forEach((el) => {
		// Jeda bertahap: data-tunda dalam milidetik, atau dihitung dari
		// urutan anak bila induknya memakai data-tahap.
		const tundaSendiri = el.dataset.tunda
		const tahapInduk = el.parentElement?.dataset.tahap

		if (tundaSendiri) {
			el.style.setProperty('--tunda', `${parseInt(tundaSendiri, 10)}ms`)
		} else if (tahapInduk) {
			const urutan = Array.from(el.parentElement.children).indexOf(el)
			const jeda = Math.min(urutan * parseInt(tahapInduk, 10), 600)
			el.style.setProperty('--tunda', `${jeda}ms`)
		}

		pengamat.observe(el)
	})
}

/* ---------------------------------------------------------------------
 * 2. Buku mengikuti kursor
 *
 * Transform ditulis sebagai gaya sebaris karena gaya sebaris menang
 * atas aturan :hover di CSS. Saat kursor keluar, gaya itu dihapus
 * seluruhnya sehingga buku kembali diatur CSS — termasuk kembali ke
 * kurva perlambatan yang lebih panjang.
 * ------------------------------------------------------------------- */
function siapkanKemiringan() {
	if (KURANGI_GERAK.matches || !KURSOR_HALUS.matches) return

	document.querySelectorAll('[data-miring]').forEach((wadah) => {
		const buku = wadah.querySelector('.buku3d')
		if (!buku) return

		wadah.addEventListener('pointerenter', () => {
			// Selama diikuti kursor, gerak harus nyaris seketika.
			buku.style.transition = 'transform 150ms linear'
		})

		wadah.addEventListener('pointermove', (peristiwa) => {
			const kotak = wadah.getBoundingClientRect()
			// -1 di tepi kiri, +1 di tepi kanan.
			const x = ((peristiwa.clientX - kotak.left) / kotak.width) * 2 - 1
			const y = ((peristiwa.clientY - kotak.top) / kotak.height) * 2 - 1

			// Sapuan kursor menyeberangi buku dari punggung ke lembaran:
			// di tepi kiri punggung membuka lebar (+20°), di tepi kanan
			// buku terbalik hingga tumpukan halaman menghadap penonton
			// (−36°). Dari sinilah tipis-tebalnya sebuah buku terbaca.
			const putar = -8 - x * 28
			const angkat = -6 - y * 2

			buku.style.transform =
				`rotateY(${putar.toFixed(2)}deg) rotateX(${(-y * 3).toFixed(2)}deg) ` +
				`translateY(${angkat.toFixed(2)}px) scale(1.02)`
		})

		wadah.addEventListener('pointerleave', () => {
			buku.style.transition = ''
			buku.style.transform = ''
		})
	})
}

/* ---------------------------------------------------------------------
 * 3. Rak bergulir mendatar
 * ------------------------------------------------------------------- */
function siapkanRak() {
	document.querySelectorAll('[data-rak]').forEach((rak) => {
		const isi = rak.querySelector('[data-rak-isi]')
		if (!isi) return

		const mundur = rak.querySelector('[data-rak-mundur]')
		const maju = rak.querySelector('[data-rak-maju]')

		const langkah = () => Math.max(240, isi.clientWidth * 0.8)

		mundur?.addEventListener('click', () =>
			isi.scrollBy({ left: -langkah(), behavior: 'smooth' }),
		)
		maju?.addEventListener('click', () =>
			isi.scrollBy({ left: langkah(), behavior: 'smooth' }),
		)

		// Tombol dinonaktifkan di kedua ujung. Tanpa ini orang menekan
		// tombol yang tidak melakukan apa pun, dan itu terasa rusak.
		const perbaruiTombol = () => {
			const sisaKiri = isi.scrollLeft > 4
			const sisaKanan =
				isi.scrollLeft + isi.clientWidth < isi.scrollWidth - 4

			if (mundur) mundur.disabled = !sisaKiri
			if (maju) maju.disabled = !sisaKanan

			// Bila seluruh isi sudah terlihat, sembunyikan kedua tombol.
			const perluTombol = isi.scrollWidth > isi.clientWidth + 8
			rak.querySelectorAll('[data-rak-kendali]').forEach((el) => {
				el.hidden = !perluTombol
			})
		}

		let menunggu = false
		isi.addEventListener('scroll', () => {
			if (menunggu) return
			menunggu = true
			requestAnimationFrame(() => {
				perbaruiTombol()
				menunggu = false
			})
		})

		window.addEventListener('resize', perbaruiTombol)
		perbaruiTombol()
	})
}

/* ---------------------------------------------------------------------
 * 4. Marquee
 *
 * Animasi CSS bergeser sampai -50%, jadi isinya harus tepat dua kali.
 * Penggandaan dikerjakan di sini supaya Blade tidak perlu menulis
 * daftar yang sama dua kali — dan supaya salinannya bisa disembunyikan
 * dari pembaca layar.
 * ------------------------------------------------------------------- */
function siapkanMarquee() {
	document.querySelectorAll('.marquee__isi[data-gandakan]').forEach((isi) => {
		if (isi.dataset.sudahGanda === '1') return

		const salinan = document.createElement('span')
		salinan.className = 'flex'
		salinan.setAttribute('aria-hidden', 'true')
		salinan.innerHTML = isi.innerHTML

		isi.appendChild(salinan)
		isi.dataset.sudahGanda = '1'
	})
}

/* ---------------------------------------------------------------------
 * 5. Mulai
 *
 * Pola readyState inilah yang nanti akan dipasang di pembaca-pdf.js
 * agar pdf.js aman dimuat secara dinamis.
 * ------------------------------------------------------------------- */
function mulai() {
	siapkanMarquee()
	siapkanMunculan()
	siapkanKemiringan()
	siapkanRak()
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', mulai)
} else {
	mulai()
}

// Bila orang mengubah pengaturan "kurangi gerak" saat halaman terbuka,
// tampilkan semua yang tersembunyi agar tidak ada isi yang hilang.
KURANGI_GERAK.addEventListener?.('change', (peristiwa) => {
	if (!peristiwa.matches) return
	document
		.querySelectorAll('[data-muncul]')
		.forEach((el) => el.classList.add('tampil'))
})