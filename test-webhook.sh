#!/bin/bash

echo "🧪 Probando el webhook manualmente..."
echo ""

# Probar con POST (debería fallar por falta de firma, pero confirma que la ruta funciona)
echo "1️⃣ Probando POST sin firma (debería fallar con 'Firma inválida'):"
curl -X POST https://bahiapadel2.padelbb.com/deploy-webhook \
  -H "Content-Type: application/json" \
  -d '{"test": "data"}' \
  -v

echo ""
echo ""
echo "2️⃣ Verificando que se creó el log:"
sleep 2

LOG_FILE="/home/u895805914/domains/padelbb.com/public_html/bahiapadel2/storage/logs/webhook-deploy.log"
if [ -f "$LOG_FILE" ]; then
    echo "✅ Log creado! Contenido:"
    echo "----------------------------------------"
    cat "$LOG_FILE"
    echo "----------------------------------------"
else
    echo "⚠️  Log aún no existe. Esto puede significar:"
    echo "   - La ruta no está funcionando"
    echo "   - Hay un error en el controlador"
    echo "   - Necesitas limpiar caché de rutas"
fi

echo ""
echo "💡 Si ves 'Firma inválida', significa que la ruta funciona correctamente!"
echo "💡 Si ves '404 Not Found', necesitas limpiar el caché de rutas:"
echo "   php artisan route:clear && php artisan config:clear && php artisan config:cache"

