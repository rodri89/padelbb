#!/bin/bash
# Ejecutar UNA VEZ por SSH en Hostinger, en public_html de padelbb.com
# Requiere: Git, PHP 8.2+, MySQL con BD creada e importada desde database/sql/padelbb_local_dump.sql

set -e

PROJECT_DIR="/home/u895805914/domains/padelbb.com/public_html"
PHP_BIN="/opt/alt/php83/usr/bin/php"
REPO="https://github.com/rodri89/padelbb.git"

echo "=== PadelBB: setup inicial en padelbb.com ==="

if [ ! -f "$PROJECT_DIR/artisan" ]; then
  echo "Clonando repositorio..."
  cd "$(dirname "$PROJECT_DIR")"
  if [ -d "public_html" ] && [ "$(ls -A public_html 2>/dev/null | wc -l)" -gt 0 ]; then
    echo "Backup de public_html existente..."
    mv public_html "public_html_backup_$(date +%Y%m%d_%H%M%S)"
  fi
  git clone "$REPO" public_html
  cd "$PROJECT_DIR"
else
  cd "$PROJECT_DIR"
fi

if [ ! -f .env ]; then
  cp .env.production.example .env
  echo "Editá .env con DB, mail y MercadoPago antes de continuar."
  exit 1
fi

chmod +x deploy.sh
chmod -R 775 storage bootstrap/cache

echo "Instalando dependencias..."
$PHP_BIN /opt/alt/php83/usr/bin/composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-sodium --no-interaction

if grep -q '^APP_KEY=$' .env || grep -q '^APP_KEY=$' .env 2>/dev/null; then
  $PHP_BIN artisan key:generate --force
fi

$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
$PHP_BIN artisan optimize

echo ""
echo "=== Setup completado ==="
echo "1. hPanel: Document Root de padelbb.com -> public_html/public"
echo "2. Visitar una vez: https://padelbb.com/storage-link"
echo "3. Webhook GitHub ya debe apuntar a https://padelbb.com/deploy-webhook"
echo "4. Probar: git push origin main y revisar storage/logs/webhook-deploy.log"
