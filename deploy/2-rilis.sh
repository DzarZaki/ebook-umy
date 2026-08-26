#!/usr/bin/env bash
# ===========================================================================
# 2-rilis.sh â€” pembaruan aplikasi untuk rilis berikutnya (jalankan di VPS).
#
# Pemakaian:
#   cd /var/www/ebook-umy && sudo bash deploy/2-rilis.sh
#
# Urutan mengikuti bagian 8 CHECKLIST-PRODUKSI.md.
# ===========================================================================
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_DIR"

echo "== Tarik kode terbaru =="
sudo -u www-data git pull --ff-only 2>/dev/null || git pull --ff-only

echo "== Dependensi PHP =="
composer install --no-dev --optimize-autoloader --no-interaction

echo "== Build aset =="
npm ci --silent || npm ci
npm run build

echo "== Migrasi & cache =="
php artisan migrate --force
php artisan optimize

echo "== Restart worker antrean =="
supervisorctl restart ebook-queue

echo "Rilis selesai: $(date '+%d/%m/%Y %H:%M')"
