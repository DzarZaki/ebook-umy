# Checklist Rilis Produksi

Daftar periksa untuk membawa aplikasi dari Laragon ke server produksi.
Disusun dari keputusan-keputusan spesifik di kode ini — bukan templat umum.
Centang berurutan; setiap butir punya sebabnya.

---

## 1. Server & dependensi

- [ ] PHP **8.2+** dengan ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd` (sampul), `curl`
- [ ] Composer 2 dan Node 20+
- [ ] **qpdf terpasang** (`dnf install qpdf` / `apt install qpdf`) — tanpa ini unduhan berstempel ditolak 503 dan buku "unduh sebagian" mati
- [ ] MySQL berjalan; database + pengguna dibuat
- [ ] Dokumen root web server menunjuk ke folder **`public/`**, bukan root proyek

## 2. Berkas `.env`

Salin dari `.env.example`, lalu pastikan nilai-nilai kritis ini:

| Kunci | Nilai produksi | Sebab |
|---|---|---|
| `APP_ENV` | `production` | Menonaktifkan jalur dev |
| `APP_DEBUG` | **`false`** | Debug yang bocor = peta celah |
| `APP_URL` | `https://…` | Dipakai tautan surel verifikasi & notifikasi |
| `APP_KEY` | hasil `php artisan key:generate` | Enkripsi sesi |
| `SESSION_SECURE_COOKIE` | **`true`** | Cookie sesi hanya lewat HTTPS |
| `DB_CONNECTION` / `DB_*` | MySQL produksi | — |
| `MAIL_MAILER` | `smtp` (+ host, port 587, akun, **app password**) | Lihat butir kritis di bawah |
| `QPDF_BINARY` | `qpdf` (atau jalur lengkap) | Sesuai komentar `.env.example` |
| `EBOOK_QPDF_WAJIB` | biarkan `true` | Buku tak boleh tersalur tanpa stempel diam-diam |
| `SEED_PASSWORD` | **kosongkan** | Hanya untuk development |

> ⚠️ **SMTP adalah prasyarat keras.** Sejak verifikasi email ditegakkan,
> mahasiswa baru terkunci di halaman "verifikasi email Anda" sampai surel
> benar-benar sampai. Gmail memerlukan *App Password* (bukan sandi biasa)
> dengan 2FA menyala. Uji dengan pendaftaran akun sungguhan sebelum umum.

## 3. Build & migrasi

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build          # menghasilkan public/build
php artisan key:generate         # bila APP_KEY masih kosong
php artisan migrate --force
php artisan storage:link         # sampul & foto profil di disk public
php artisan ebook:periksa-qpdf   # kesehatan qpdf + folder sementara
```

Bila memindahkan data lama (buku yang diunggah sebelum fitur pencarian isi):

```bash
php artisan ebook:indeks-teks    # mengisi kolom search_text sekali jalan
```

Optimasi muat:

```bash
php artisan config:cache route:cache view:cache event:cache
# atau ringkasnya: php artisan optimize
```

Pastikan web server boleh menulis: `storage/` dan `bootstrap/cache/`.

## 4. Dua proses latar yang wajib hidup

### Antrean (surel notifikasi buku baru)

Tanpa worker, notifikasi hanya menumpuk di tabel `jobs` tanpa pernah terkirim.

```bash
php artisan queue:work --tries=3 --max-time=3600   # via supervisor/systemd
```

Setiap kali rilis baru: `php artisan queue:restart`.

### Penjadwal

Sweeper berkas sementara & tempat sampah buku hidup dari cron:

```cron
* * * * * cd /jalur/proyek && php artisan schedule:run >> /dev/null 2>&1
```

## 5. HTTPS

Header `Strict-Transport-Security` dikirim otomatis oleh middleware
(`AddSecurityHeaders`) begitu koneksi aman — tidak ada sakelar tambahan.
Pastikan sertifikat valid dan seluruh trafik diarahkan ke HTTPS.

## 6. Akun Super Admin pertama

Seeder sengaja menolak berjalan di production. Buat akun pertama lewat tinker:

```bash
php artisan tinker
```
```php
App\Models\User::create([
    'name' => 'Pengelola Utama',
    'email' => '…@gmail.com',
    'password' => 'sandi-kuat',      // cast 'hashed' mengenkripsi otomatis
    'role' => App\Models\User::ROLE_SUPERADMIN,
    'is_active' => true,
    'email_verified_at' => now(),
]);
```

Lalu buat prodi & dosen dari panel Super Admin seperti alur biasa.

## 7. Uji terima (lakukan semua sebelum umum)

- [ ] Login Super Admin berhasil
- [ ] **Pendaftaran uji**: daftar dengan kode akses → surel verifikasi datang → klik tautan → bisa buka katalog
- [ ] Kirim ulang tautan verifikasi bekerja
- [ ] Unggah satu buku uji (mode unduh penuh) → halaman terindeks pencarian isinya
- [ ] Unduh buku itu → stempel identitas tertanam di kepala & kaki tiap halaman
- [ ] Mode "unduh sebagian" dipotong sesuai rentang
- [ ] Notifikasi: aktifkan langganan pada akun mahasiswa uji → terbitkan satu buku → surel masuk (cek tabel `jobs` kosong setelah worker jalan)
- [ ] Header keamanan hadir: `curl -I https://domain` memuat `X-Frame-Options`, `Content-Security-Policy`, `Referrer-Policy`
- [ ] Rate limit: 6× percobaan register berturut dari satu IP mendapat 429
- [ ] Halaman galat (mis. URL ngawur) tidak menampilkan jejak stack/debug

## 8. Operasional berjalan

- [ ] **Cadangan harian**: database **dan** `storage/app/private/` (berkas PDF buku — aset paling tak tergantikan di sistem ini)
- [ ] Pantau `storage/logs/laravel.log` beberapa hari pertama (peringatan stempel & qpdf muncul di sana)
- [ ] Saat rilis ulang: `git pull`, `composer install --no-dev`, `npm run build`, `php artisan migrate --force`, `php artisan optimize`, `php artisan queue:restart`
- [ ] Gangguan massal? `php artisan down` — penampil baca ikut aman karena berkas privat tak pernah publik

---

*Rilis pertama disusun Agustus 2026. Perbarui daftar ini setiap kali ada
keputusan arsitektur baru yang menyentuh deployment.*
