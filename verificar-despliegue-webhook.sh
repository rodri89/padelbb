#!/bin/bash

echo "🔍 Verificando que el despliegue se ejecutó correctamente..."
echo ""

PROJECT_DIR="/home/u895805914/domains/padelbb.com/public_html/bahiapadel2"
cd "$PROJECT_DIR"

LOG_FILE="$PROJECT_DIR/storage/logs/webhook-deploy.log"

echo "📋 Últimas 30 líneas del log del webhook:"
echo "----------------------------------------"
if [ -f "$LOG_FILE" ]; then
    tail -30 "$LOG_FILE"
else
    echo "⚠️  El archivo de log no existe aún"
fi
echo "----------------------------------------"
echo ""

echo "🔍 Verificando que deploy.sh se ejecutó:"
if grep -q "Iniciando despliegue" "$LOG_FILE" 2>/dev/null; then
    echo "✅ El despliegue se inició"
else
    echo "⚠️  No se encontró evidencia de que el despliegue se ejecutó"
fi

echo ""
echo "📦 Verificando estado de Git:"
git log --oneline -3

echo ""
echo "💡 Si el despliegue no se ejecutó, verifica:"
echo "   1. Que deploy.sh tenga permisos de ejecución: chmod +x deploy.sh"
echo "   2. Que el archivo deploy.sh exista: ls -la deploy.sh"
echo "   3. Que PHP pueda ejecutar comandos: php -r 'exec(\"echo test\");'"

