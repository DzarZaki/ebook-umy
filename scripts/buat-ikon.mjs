// Mengubah logo.svg menjadi ikon PNG untuk keperluan PWA.
// Jalankan dengan: npm run ikon
import sharp from 'sharp'
import { mkdir } from 'node:fs/promises'

const sumber = 'public/images/logo.svg'
const tujuan = 'public/images'

// Latar krem agar ikon tetap terbaca di layar gelap maupun terang.
const latar = { r: 250, g: 249, b: 247, alpha: 1 }

const ukuran = [192, 512]

await mkdir(tujuan, { recursive: true })

for (const sisi of ukuran) {
	// Logo diberi ruang kosong sekitar 12% agar aman saat dipotong bulat oleh Android.
	const isi = Math.round(sisi * 0.76)
	const tepi = Math.round((sisi - isi) / 2)

	const logo = await sharp(sumber, { density: 512 })
		.resize(isi, isi, { fit: 'contain', background: { r: 0, g: 0, b: 0, alpha: 0 } })
		.png()
		.toBuffer()

	await sharp({
		create: { width: sisi, height: sisi, channels: 4, background: latar },
	})
		.composite([{ input: logo, top: tepi, left: tepi }])
		.png()
		.toFile(`${tujuan}/icon-${sisi}.png`)

	console.log(`Ikon ${sisi}x${sisi} selesai dibuat.`)
}