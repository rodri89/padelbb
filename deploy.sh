#!/bin/bash
set -e

echo "🚀 Iniciando despliegue..."

# Cambiar al directorio del proyecto
cd /home/u895805914/domains/padelbb.com/public_html

echo "📥 Actualizando código desde GitHub..."
echo "   Directorio actual: $(pwd)"
echo "   Estado de Git antes del pull:"
git status --short || echo "   ⚠️  Error al verificar estado de Git"

# Descartar cambios locales para evitar conflictos
echo "   Descartando cambios locales (si existen)..."
git reset --hard HEAD || true
git clean -fd || true

echo "   Ejecutando git pull..."
if git pull origin main; then
    echo "   ✅ Git pull exitoso"
    echo "   Estado de Git después del pull:"
    git status --short || true
else
    echo "   ❌ Error en git pull"
    echo "   Intentando reset hard a origin/main..."
    git fetch origin main || true
    git reset --hard origin/main || echo "   ❌ Error persistente en git pull"
fi

# CONFIGURACIÓN EXPLÍCITA PARA HOSTINGER PHP 8.3
PHP_BIN="/opt/alt/php83/usr/bin/php"
COMPOSER_CMD="/opt/alt/php83/usr/bin/composer"
ARTISAN_CMD="$PHP_BIN artisan"

echo "📦 Instalando dependencias..."
$COMPOSER_CMD install --no-dev --optimize-autoloader --ignore-platform-req=ext-sodium --no-interaction

echo "🎨 Construyendo assets frontend (jQuery/Bootstrap)..."
if command -v npm >/dev/null 2>&1; then
    cd public
    npm install
    npx gulp build
    cd ..
    echo "   ✅ Assets frontend generados"
else
    echo "   ⚠️  npm no disponible; se usan los assets versionados en public/vendor"
fi

echo "🧹 Limpiando cachés..."
$ARTISAN_CMD config:clear
$ARTISAN_CMD cache:clear
$ARTISAN_CMD view:clear
$ARTISAN_CMD route:clear

echo "⚡ Regenerando cachés..."
$ARTISAN_CMD config:cache
$ARTISAN_CMD view:cache

# Intentar cachear rutas (opcional)
echo "🛣️  Cacheando rutas..."
if $ARTISAN_CMD route:cache 2>/dev/null; then
    echo "   ✅ Rutas cacheadas"
else
    echo "   ⚠️  Saltando cache de rutas"
fi

$ARTISAN_CMD optimize

echo "✅ Despliegue completado!"
