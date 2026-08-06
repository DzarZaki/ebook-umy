// Menyalin sumber daya pendukung pdf.js ke folder public agar bisa diakses peramban.
// Tanpa berkas-berkas ini, pdf.js menggambar halaman kosong tanpa memberi galat.
// Jalankan dengan: npm run aset:pdfjs
import { cp, mkdir, access } from 'node:fs/promises'
import { constants } from 'node:fs'

const asal = 'node_modules/pdfjs-dist'
const tujuan = 'public/pdfjs'

// Nama folder yang perlu disalin. Sebagian hanya ada di versi pdf.js tertentu.
const folder = ['standard_fonts', 'cmaps', 'wasm']

await mkdir(tujuan, { recursive: true })

for (const nama of folder) {
	const sumber = `${asal}/${nama}`

	try {
		await access(sumber, constants.R_OK)
	} catch {
		console.log(`Dilewati: ${nama} tidak ada di versi pdf.js ini.`)
		continue
	}

	await cp(sumber, `${tujuan}/${nama}`, { recursive: true })
	console.log(`Tersalin: ${nama}`)
}