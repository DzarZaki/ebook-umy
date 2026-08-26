#!/usr/bin/env bash
# ===========================================================================
# 1-setup-vps.sh â€” pemasangan sekali jalan di VPS Ubuntu 24.04 yang baru.
#
# Pemakaian:
#   1. Sunting blok KONFIGURASI di bawah (minimal MAIL di env-produksi.contoh).
#   2. Unggah skrip ini + deploy/env-produksi.contoh ke server, lalu:
#        sudo bash 1-setup-vps.sh
#   3. Ikuti "LANGKAH LANJUTAN" yang dicetak di akhir.
#
# Target: Ubuntu 24.04 (PHP 8.3 bawaan). Untuk 22.04, tambahkan dulu:
#   add-apt-repository ppa:ondrej/php && apt update
# ===========================================================================
set -euo pipefail

# ======================= KONFIGURASI â€” SUNTING INI =========================
DOMAIN=""                 # contoh: ebook.umy.ac.id â€” kosongkan bila masih pakai IP
GIT_REPO="https://github.com/DzarZaki/ebook-umy.git"
BRANCH="master"
APP_DIR="/var/www/ebook-umy"
DB_NAME="ebook_umy"
DB_USER="ebook_umy"
KREDENSIAL="/root/kredensial-db.txt"   # berkas kredensial database (chmod 600)
# ===========================================================================

if [[ $EUID -ne 0 ]]; then
    echo "Jalankan sebagai root: sudo bash $0" >&2
    exit 1
fi

echo "== [1/12] Paket sistem =="
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq \
    nginx mariadb-server qpdf supervisor cron unzip curl git certbot \
    python3-certbot-nginx \
    php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-curl \
    php8.3-gd php8.3-xml php8.3-zip php8.3-intl php8.3-bcmath

echo "== [2/12] Composer & Node 22 =="
if ! command -v composer >/dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi
if ! command -v node >/dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
    apt-get install -y -qq nodejs
fi

echo "== [3/12] Database: basis data + pengguna baru =="
DB_PASS="$(openssl rand -base64 24 | tr -dc 'A-Za-z0-9')"
mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'127.0.0.1'; FLUSH PRIVILEGES;"
umask 077
cat > "$KREDENSIAL" <<EOF
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}
EOF

echo "== [4/12] Kode aplikasi: clone ${BRANCH} =="
mkdir -p "$(dirname "$APP_DIR")"
if [[ -d "${APP_DIR}/.git" ]]; then
    git -C "$APP_DIR" fetch --all && git -C "$APP_DIR" reset --hard "origin/${BRANCH}"
else
    git clone --branch "$BRANCH" "$GIT_REPO" "$APP_DIR"
fi
cd "$APP_DIR"

echo "== [5/12] Berkas .env dari template =="
if [[ -f .env ]]; then
    echo "   .env sudah ada â€” dilewati (tidak ditimpa)."
else
    cp deploy/env-produksi.contoh .env
    if [[ -n "$DOMAIN" ]]; then
        sed -i "s|__APP_URL__|https://${DOMAIN}|; s|__SECURE_COOKIE__|true|" .env
    else
        IP=$(curl -4 -s https://ifconfig.me || echo "127.0.0.1")
        sed -i "s|__APP_URL__|http://${IP}|; s|__SECURE_COOKIE__|false|" .env
        echo "   PERHATIAN: tanpa DOMAIN situs berjalan HTTP â€” cookie tidak aman dan HTTPS wajib menyusul."
    fi
    sed -i "s|__DB_DATABASE__|${DB_NAME}|; s|__DB_USERNAME__|${DB_USER}|; s|__DB_PASSWORD__|${DB_PASS}|" .env
    if grep -q "WAJIB_ISI" .env; then
        echo >&2 "==================================================================="
        echo >&2 "BERHENTI: isi MAIL_USERNAME dan MAIL_PASSWORD (App Password Gmail)"
        echo >&2 "di berkas ${APP_DIR}/.env terlebih dahulu, lalu jalankan ulang"
        echo >&2 "skrip ini. Tanpa SMTP benar, mahasiswa baru tak bisa verifikasi."
        echo >&2 "==================================================================="
        exit 2
    fi
fi

echo "== [6/12] Dependensi PHP & build aset =="
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --silent || npm ci
npm run build
[ -f public/build/manifest.json ] || { echo "Build aset gagal: manifest.json tidak ada." >&2; exit 1; }

echo "== [7/12] APP_KEY, migrasi, optimasi =="
grep -q "^APP_KEY=base64" .env || php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan ebook:periksa-qpdf
php artisan optimize

echo "== [8/12] Hak akses berkas =="
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
find "$APP_DIR/storage" -type d -exec chmod 775 {} +
find "$APP_DIR/bootstrap/cache" -type d -exec chmod 775 {} +

echo "== [9/12] PHP-FPM: naikkan batas unggah PDF =="
INI=$(php8.3 --ini | grep -m1 'Scan for additional' | awk '{print $NF}')/90-upload.ini
printf "upload_max_filesize=32M\npost_max_size=40M\nmemory_limit=512M\nmax_execution_time=180\n" > "$INI"
systemctl restart php8.3-fpm

echo "== [10/12] Nginx =="
SERVER_NAME="${DOMAIN:-_}"
cat > /etc/nginx/sites-available/ebook-umy <<EOF
server {
    listen 80;
    server_name ${SERVER_NAME};
    root ${APP_DIR}/public;
    index index.php;

    client_max_body_size 40M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.ht { deny all; }
}
EOF
ln -sf /etc/nginx/sites-available/ebook-umy /etc/nginx/sites-enabled/ebook-umy
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

echo "== [11/12] Supervisor: worker antrean surel =="
cat > /etc/supervisor/conf.d/ebook-queue.conf <<EOF
[program:ebook-queue]
command=php ${APP_DIR}/artisan queue:work --tries=3 --max-time=3600
directory=${APP_DIR}
user=www-data
autostart=true
autorestart=true
stopwaitsecs=30
stdout_logfile=${APP_DIR}/storage/logs/worker.log
redirect_stderr=true
EOF
supervisorctl reread && supervisorctl update && supervisorctl start ebook-queue || true

echo "== [12/12] Cron penjadwal =="
CRON_LINE="* * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1"
crontab -l 2>/dev/null | grep -F "$CRON_LINE" || (crontab -l 2>/dev/null; echo "$CRON_LINE") | crontab -

if [[ -n "$DOMAIN" ]]; then
    echo "== HTTPS via Let's Encrypt =="
    certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos --redirect || \
        echo "certbot gagal â€” pastikan DNS domain sudah menunjuk ke server ini, jalankan ulang manual."
fi

cat <<PESAN

=====================================================================
 PEMASANGAN SELESAI
=====================================================================
 Kredensial DB : ${KREDENSIAL}

 LANGKAH LANJUTAN (wajib, urut):
   1) cd ${APP_DIR}
   2) php artisan tinker   -> buat Super Admin pertama:
      App\\Models\\User::create([
          'name' => 'Pengelola Utama',
          'email' => 'email-anda@gmail.com',
          'password' => 'sandi-kuat',       // otomatis ter-hash
          'role' => App\\Models\\User::ROLE_SUPERADMIN,
          'is_active' => true,
          'email_verified_at' => now(),
      ]);
   3) Buat prodi & akun dosen lewat panel Super Admin.
   4) Uji terima: ikuti bagian 7 CHECKLIST-PRODUKSI.md
      (pendaftaran uji sampai surel verifikasi benar-benar masuk kotak).
=====================================================================
PESAN
