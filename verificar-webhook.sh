#!/bin/bash

echo "🔍 Verificando configuración del webhook..."
echo ""

# Verificar directorio de logs
LOG_DIR="/home/u895805914/domains/padelbb.com/public_html/bahiapadel2/storage/logs"
if [ -d "$LOG_DIR" ]; then
    echo "✅ Directorio de logs existe: $LOG_DIR"
else
    echo "❌ Directorio de logs NO existe. Creando..."
    mkdir -p "$LOG_DIR"
    chmod 755 "$LOG_DIR"
fi

# Verificar archivo de log
LOG_FILE="$LOG_DIR/webhook-deploy.log"
if [ -f "$LOG_FILE" ]; then
    echo "✅ Archivo de log existe: $LOG_FILE"
    echo ""
    echo "📋 Últimas 20 líneas del log:"
    echo "----------------------------------------"
    tail -20 "$LOG_FILE"
    echo "----------------------------------------"
else
    echo "⚠️  Archivo de log NO existe aún: $LOG_FILE"
    echo "   (Se creará automáticamente cuando el webhook sea llamado)"
fi

echo ""
echo "🔍 Verificando deploy.sh..."
DEPLOY_SCRIPT="/home/u895805914/domains/padelbb.com/public_html/bahiapadel2/deploy.sh"
if [ -f "$DEPLOY_SCRIPT" ]; then
    echo "✅ deploy.sh existe"
    if [ -x "$DEPLOY_SCRIPT" ]; then
        echo "✅ deploy.sh tiene permisos de ejecución"
    else
        echo "⚠️  deploy.sh NO tiene permisos de ejecución. Ejecutando chmod +x..."
        chmod +x "$DEPLOY_SCRIPT"
    fi
else
    echo "❌ deploy.sh NO existe"
fi

echo ""
echo "🔍 Verificando PHP..."
PHP_BIN="/opt/alt/php83/usr/bin/php"
if [ -f "$PHP_BIN" ]; then
    echo "✅ PHP 8.3 encontrado: $PHP_BIN"
    $PHP_BIN -v | head -1
else
    echo "⚠️  PHP 8.3 no encontrado en $PHP_BIN"
    echo "   Buscando otras versiones..."
    find /opt/alt -name "php" -type f 2>/dev/null | grep -E "php8[0-4]" | head -5
fi

echo ""
echo "✅ Verificación completada"
echo ""
echo "💡 Para probar el webhook manualmente, ejecuta:"
echo "   curl -X POST https://bahiapadel2.padelbb.com/deploy-webhook"
echo ""
echo "💡 Para ver los logs en tiempo real:"
echo "   tail -f $LOG_FILE"

