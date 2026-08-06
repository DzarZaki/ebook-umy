# TUGAS: Analisis Menyeluruh Seluruh Codebase

Kamu adalah senior software architect. Tugasmu: **mempelajari dan memahami SELURUH kode dalam workspace ini secara menyeluruh, tanpa ada satu file pun yang terlewat.**

## ATURAN WAJIB
1. **Jangan menebak.** Setiap kesimpulan harus berdasarkan isi file yang benar-benar kamu baca.
2. **Jangan berhenti di tengah jalan.** Jika konteks hampir penuh, ringkas temuanmu sejauh ini, lalu lanjutkan dari file berikutnya.
3. **Jangan mengubah kode apa pun** pada tahap ini. Ini murni tugas membaca & analisis (read-only).
4. Selalu **tampilkan progres**: `[sudah dibaca X dari Y file]`.
5. Jika ada file yang tidak bisa dibaca (binary, terlalu besar, terenkripsi), catat di daftar "File Tidak Terbaca" beserta alasannya — jangan diam-diam dilewati.

## LANGKAH KERJA (ikuti berurutan)

### Tahap 1 — Inventarisasi
- Petakan struktur direktori lengkap (kedalaman penuh).
- Buat **daftar lengkap semua file** beserta path, ekstensi, dan perkiraan jumlah baris.
- Kecualikan hanya: `node_modules/`, `.git/`, `dist/`, `build/`, `.venv/`, `vendor/`, file lock, dan aset biner besar — tapi **sebutkan** bahwa folder itu dikecualikan dan mengapa.
- Tampilkan total: jumlah file, total baris kode, dan sebaran per bahasa.

### Tahap 2 — Konteks Proyek
Baca lebih dulu: `README*`, `package.json` / `requirements.txt` / `pom.xml` / `go.mod` / `composer.json`, file konfigurasi (`.env.example`, `tsconfig.json`, `Dockerfile`, `docker-compose*`, CI/CD), dan dokumentasi apa pun.
Simpulkan: tujuan proyek, bahasa & framework, versi runtime, dependensi utama, cara build/run/test.

### Tahap 3 — Pembacaan File Demi File (INI BAGIAN TERPENTING)
Baca **setiap file** dari daftar Tahap 1, satu per satu, secara sistematis (urut folder). Untuk tiap file berikan:
- **Path & peran** file dalam sistem
- **Isi utama**: class, fungsi, method, konstanta, tipe/interface — beserta signature dan tujuannya
- **Dependensi**: apa yang di-import file ini, dan siapa yang meng-import file ini
- **Alur logika** penting di dalamnya
- **Catatan**: bug potensial, TODO/FIXME, kode mati, duplikasi, hardcoded value, risiko keamanan

Setelah setiap 10 file, tulis checkpoint singkat lalu lanjutkan otomatis tanpa menunggu perintah saya.

### Tahap 4 — Sintesis Arsitektur
Setelah semua file terbaca, sajikan:
1. **Arsitektur sistem** — lapisan, modul, pola desain yang dipakai
2. **Diagram alur** (format Mermaid) untuk: struktur modul, alur data utama, dan alur request/eksekusi end-to-end
3. **Peta ketergantungan** antar modul
4. **Model data** — skema database/entity/state management
5. **Entry point** dan urutan eksekusi dari awal aplikasi berjalan
6. **API / antarmuka eksternal** yang tersedia atau dikonsumsi
7. **Penanganan error, logging, autentikasi, dan konfigurasi**
8. **Coverage testing** — apa yang sudah dites dan apa yang belum

### Tahap 5 — Temuan & Rekomendasi
- Daftar **masalah** diurutkan: Kritis → Tinggi → Sedang → Rendah (sertakan path + nomor baris)
- **Utang teknis** dan area yang perlu refactor
- **Risiko keamanan** dan **potensi masalah performa**
- **Rekomendasi perbaikan** yang konkret dan bisa langsung dikerjakan

### Tahap 6 — Verifikasi Kelengkapan
Di akhir, buktikan tidak ada yang terlewat:
- Tabel: `Total file ditemukan` vs `Total file dibaca` vs `File dikecualikan (+alasan)`
- Jika angkanya tidak sama, **kembali dan baca file yang tersisa** sebelum menyatakan selesai.
- Tutup dengan pernyataan: "Verifikasi selesai — X/X file telah dianalisis."

## FORMAT OUTPUT
Gunakan heading Markdown, tabel untuk ringkasan, blok kode untuk contoh, dan Mermaid untuk diagram. Bahasa: Indonesia, tapi istilah teknis tetap dalam bahasa Inggris.

Mulai sekarang dari Tahap 1.