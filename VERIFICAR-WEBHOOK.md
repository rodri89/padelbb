# 🔍 Cómo Verificar el Webhook

## 1. Ejecutar el Script de Verificación

En el servidor (SSH), ejecuta:

```bash
cd /home/u895805914/domains/padelbb.com/public_html/bahiapadel2
chmod +x verificar-webhook.sh
./verificar-webhook.sh
```

Este script verificará:
- ✅ Si existe el directorio de logs
- ✅ Si existe el archivo de log del webhook
- ✅ Si `deploy.sh` existe y tiene permisos
- ✅ Si PHP 8.3 está disponible
- 📋 Mostrará las últimas 20 líneas del log si existe

## 2. Verificar en GitHub

1. Ve a: `https://github.com/rodri89/bahiapadel/settings/hooks`
2. Busca el webhook configurado
3. Haz clic en "Recent Deliveries"
4. Deberías ver las peticiones recientes con:
   - ✅ Estado 200 (éxito)
   - ❌ Estado 403 (firma inválida)
   - ❌ Estado 405 (método incorrecto)

## 3. Ver los Logs en Tiempo Real

```bash
# Ver el log del webhook
tail -f storage/logs/webhook-deploy.log

# O ver el log general de Laravel
tail -f storage/logs/laravel.log | grep -i webhook
```

## 4. Probar el Webhook Manualmente

Si quieres probar que el webhook responde (sin ejecutar el despliegue):

```bash
curl -X POST https://bahiapadel2.padelbb.com/deploy-webhook
```

Esto debería devolver un error de "Firma inválida" (porque no enviamos el secret), pero confirma que la ruta funciona.

## 5. Verificar que el Webhook está Configurado en GitHub

El webhook debe estar configurado con:
- **Payload URL**: `https://bahiapadel2.padelbb.com/deploy-webhook`
- **Content type**: `application/json`
- **Secret**: `0e6ce09117155a7105a38e7355fe5356f03c19b03a4baa3d155fedd77d678296`
- **Events**: "Just the push event"
- **Active**: ✅ Marcado

## 6. Si el Webhook No Funciona

### Verificar que el código está actualizado en el servidor:

```bash
cd /home/u895805914/domains/padelbb.com/public_html/bahiapadel2
git pull origin main
php artisan config:clear
php artisan route:clear
php artisan config:cache
```

### Verificar permisos:

```bash
chmod 755 storage/logs
chmod 644 storage/logs/*.log
chmod +x deploy.sh
```

### Verificar que la ruta está registrada:

```bash
php artisan route:list | grep webhook
```

Deberías ver: `POST deploy-webhook`

## 7. Logs que Deberías Ver

Cuando el webhook funciona correctamente, deberías ver en `webhook-deploy.log`:

```
2025-01-XX XX:XX:XX - Webhook llamado - Método: POST - IP: XXX.XXX.XXX.XXX
2025-01-XX XX:XX:XX - Verificando firma... Signature header: presente
2025-01-XX XX:XX:XX - Ref recibido: refs/heads/main
2025-01-XX XX:XX:XX - ✅ Iniciando despliegue - Commit: abc123... - Mensaje: Test: ...
```

Si ves errores, el log mostrará qué falló específicamente.

