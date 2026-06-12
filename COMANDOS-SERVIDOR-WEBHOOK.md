# 🔧 Comandos para Ejecutar en el Servidor (SSH)

Después de hacer `git pull` en el servidor, ejecuta estos comandos para que el webhook funcione:

## 📋 Comandos a Ejecutar:

```bash
# 1. Ir al directorio del proyecto
cd /home/u895805914/domains/padelbb.com/public_html/bahiapadel2

# 2. Limpiar todas las cachés de Laravel
/opt/alt/php82/usr/bin/php artisan config:clear
/opt/alt/php82/usr/bin/php artisan route:clear
/opt/alt/php82/usr/bin/php artisan cache:clear
/opt/alt/php82/usr/bin/php artisan view:clear

# 3. Regenerar cachés
/opt/alt/php82/usr/bin/php artisan config:cache
/opt/alt/php82/usr/bin/php artisan route:cache

# 4. Verificar que la ruta existe
/opt/alt/php82/usr/bin/php artisan route:list | grep deploy-webhook
```

## ✅ Verificación:

Después de ejecutar los comandos, deberías ver:
- La ruta `deploy-webhook` en el listado de rutas
- La URL `https://bahiapadel2.padelbb.com/deploy-webhook` debería responder (aunque sea con error 405 si accedes con GET)

## 🧪 Prueba Rápida:

```bash
# Probar que la ruta responde (debería dar 405 Method Not Allowed si usas GET)
curl -X GET https://bahiapadel2.padelbb.com/deploy-webhook

# Probar con POST (debería dar 403 si no tienes la firma correcta, o 200 si todo está bien)
curl -X POST https://bahiapadel2.padelbb.com/deploy-webhook \
  -H "Content-Type: application/json" \
  -d '{"ref":"refs/heads/main"}'
```

