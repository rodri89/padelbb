#!/bin/bash

echo "📥 Obteniendo deploy-webhook.php en el servidor..."
echo ""

PROJECT_DIR="/home/u895805914/domains/padelbb.com/public_html/bahiapadel2"
cd "$PROJECT_DIR"

# Verificar si el archivo existe
if [ -f "public/deploy-webhook.php" ]; then
    echo "✅ El archivo ya existe: public/deploy-webhook.php"
    echo ""
    echo "📋 Verificando contenido..."
    head -5 public/deploy-webhook.php
else
    echo "⚠️  El archivo NO existe. Obteniendo desde Git..."
    echo ""
    
    # Hacer pull para obtener el archivo
    echo "🔄 Ejecutando git pull..."
    git pull origin main
    
    # Verificar nuevamente
    if [ -f "public/deploy-webhook.php" ]; then
        echo "✅ Archivo obtenido exitosamente!"
    else
        echo "❌ Error: El archivo aún no existe después de git pull"
        echo ""
        echo "💡 Verifica que el archivo esté en el repositorio:"
        echo "   git ls-files public/deploy-webhook.php"
        exit 1
    fi
fi

echo ""
echo "🔍 Verificando permisos..."
ls -la public/deploy-webhook.php

echo ""
echo "🧪 Probando acceso al archivo..."
curl -X POST https://bahiapadel2.padelbb.com/deploy-webhook.php \
  -H "Content-Type: application/json" \
  -d '{"test": "data"}' \
  -s | head -5

echo ""
echo ""
echo "✅ Verificación completada!"
echo ""
echo "💡 Si el archivo no existe, ejecuta:"
echo "   git pull origin main"
echo ""
echo "💡 Si el archivo existe pero no responde, verifica:"
echo "   - Que el archivo tenga permisos de lectura (644)"
echo "   - Que la URL sea correcta: https://bahiapadel2.padelbb.com/deploy-webhook.php"

