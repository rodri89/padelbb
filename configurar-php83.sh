#!/bin/bash

echo "🔧 Configurando PHP 8.3 en Hostinger..."
echo ""

# Ruta al proyecto
PROJECT_DIR="/home/u895805914/domains/padelbb.com/public_html/bahiapadel2"

cd "$PROJECT_DIR"

# Verificar que PHP 8.3 existe
PHP83="/opt/alt/php83/usr/bin/php"
if [ ! -f "$PHP83" ]; then
    echo "❌ PHP 8.3 no encontrado en $PHP83"
    echo "   Buscando versiones disponibles..."
    find /opt/alt -name "php" -type f 2>/dev/null | grep -E "php8[0-4]" | head -5
    exit 1
fi

echo "✅ PHP 8.3 encontrado: $PHP83"
$PHP83 -v | head -1
echo ""

# Crear script wrapper para usar PHP 8.3
echo "📝 Creando scripts wrapper para PHP 8.3..."

# Wrapper para php
cat > php83 << 'EOF'
#!/bin/bash
/opt/alt/php83/usr/bin/php "$@"
EOF

# Wrapper para artisan
cat > artisan83 << 'EOF'
#!/bin/bash
/opt/alt/php83/usr/bin/php artisan "$@"
EOF

# Wrapper para composer
cat > composer83 << 'EOF'
#!/bin/bash
/opt/alt/php83/usr/bin/php /opt/alt/php83/usr/bin/composer "$@"
EOF

chmod +x php83 artisan83 composer83

echo "✅ Scripts creados:"
echo "   - ./php83      (ejecuta PHP 8.3)"
echo "   - ./artisan83  (ejecuta artisan con PHP 8.3)"
echo "   - ./composer83 (ejecuta composer con PHP 8.3)"
echo ""

# Probar que funciona
echo "🧪 Probando PHP 8.3:"
./php83 -v | head -1
echo ""

# Limpiar caché con PHP 8.3
echo "🧹 Limpiando caché con PHP 8.3..."
./artisan83 config:clear
./artisan83 cache:clear
./artisan83 route:clear
./artisan83 view:clear

echo ""
echo "⚡ Regenerando caché..."
./artisan83 config:cache

echo ""
echo "✅ Configuración completada!"
echo ""
echo "💡 Ahora puedes usar:"
echo "   ./php83 artisan route:list"
echo "   ./artisan83 config:cache"
echo "   ./composer83 install"
echo ""
echo "💡 O agregar al PATH:"
echo "   export PATH=\$PWD:\$PATH"
echo "   php83 artisan route:list"

