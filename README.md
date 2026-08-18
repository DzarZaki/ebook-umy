# Pustaka Dosen (E-Book UMY)

Perpustakaan PDF internal: dosen (admin prodi) mengunggah buku, mahasiswa membacanya di penampil ber-watermark. Setiap prodi terpisah.

## Kebutuhan

- PHP 8.2+, Composer
- Node 20+
- **qpdf** (wajib) — untuk potong halaman & tempel stempel
- SQLite (bawaan)

## Pemasangan

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate