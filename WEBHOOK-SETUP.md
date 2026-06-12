# 🔗 Configuración de Webhook para Despliegue Automático

## 🎯 Dos Opciones Disponibles:

### Opción A: Ruta de Laravel (RECOMENDADO) ✅
- URL: `https://bahiapadel2.padelbb.com/deploy-webhook`
- Usa el controlador `DeployWebhookController`
- Mejor integración con Laravel
- Logging automático con Laravel

### Opción B: Archivo PHP Directo
- URL: `https://bahiapadel2.padelbb.com/deploy-webhook.php`
- Archivo en `public/deploy-webhook.php`
- Funciona sin pasar por Laravel

## 📋 Paso 1: Generar Clave Secreta

La clave secreta ya está generada:
```
0e6ce09117155a7105a38e7355fe5356f03c19b03a4baa3d155fedd77d678296
```

Esta clave está configurada en:
- `app/Http/Controllers/DeployWebhookController.php` (línea 18)
- `public/deploy-webhook.php` (línea 15)

## 📝 Paso 2: Configurar el Webhook en GitHub

1. Ve a tu repositorio en GitHub: `https://github.com/rodri89/bahiapadel`
2. Ve a **Settings** → **Webhooks** → **Add webhook**
3. Configura:
   - **Payload URL**: `https://bahiapadel2.padelbb.com/deploy-webhook` (Opción A - Recomendado)
     - O: `https://bahiapadel2.padelbb.com/deploy-webhook.php` (Opción B)
   - **Content type**: `application/json`
   - **Secret**: `0e6ce09117155a7105a38e7355fe5356f03c19b03a4baa3d155fedd77d678296`
   - **Which events**: Selecciona **"Just the push event"**
   - **Active**: ✅ Marcado
4. Haz clic en **"Add webhook"**

## ⚙️ Paso 3: Verificar Configuración en el Servidor

### Para Opción A (Ruta Laravel):
- La ruta ya está configurada en `routes/web.php`
- El controlador está en `app/Http/Controllers/DeployWebhookController.php`
- Verifica que la ruta del proyecto sea correcta (línea 25 del controlador)

### Para Opción B (Archivo PHP):
- Edita `public/deploy-webhook.php` si es necesario
- Verifica que la ruta del proyecto sea correcta

## 🧪 Paso 4: Probar el Webhook

### Opción A: Desde GitHub (Automático)

1. Haz un cambio pequeño en tu código
2. Haz commit y push:
   ```bash
   git add .
   git commit -m "Test webhook"
   git push origin main
   ```
3. En GitHub, ve a **Settings** → **Webhooks** → Haz clic en tu webhook
4. Revisa los **"Recent Deliveries"** para ver si se ejecutó correctamente

### Opción B: Prueba Manual (cURL)

```bash
# Desde tu máquina local o servidor
curl -X POST https://bahiapadel2.padelbb.com/deploy-webhook.php \
  -H "Content-Type: application/json" \
  -H "X-Hub-Signature: sha1=TU_FIRMA" \
  -d '{"ref":"refs/heads/main"}'
```

### Opción C: Prueba Simple (Sin Seguridad)

Si quieres probar primero sin seguridad, puedes comentar temporalmente la verificación:

```php
// Comentar temporalmente para pruebas
// if ($secret && ...) { ... }
```

**⚠️ IMPORTANTE:** Vuelve a activar la seguridad después de probar.

## 📊 Paso 5: Verificar Logs

Después de que se ejecute el webhook, verifica los logs:

```bash
# En el servidor (SSH)
cd /home/u895805914/domains/padelbb.com/public_html/bahiapadel2
tail -50 storage/logs/webhook-deploy.log
```

## 🔍 Solución de Problemas

### Error 403 (Forbidden)
- Verifica que la clave secreta coincida en GitHub y en el archivo PHP
- Verifica que la firma se esté enviando correctamente

### Error 405 (Method Not Allowed)
- Asegúrate de que GitHub esté enviando POST
- Verifica que el archivo esté en `public/deploy-webhook.php`

### El webhook se ejecuta pero no despliega
- Verifica permisos: `chmod +x deploy.sh`
- Verifica la ruta del proyecto en `deploy-webhook.php`
- Revisa los logs: `tail -f storage/logs/webhook-deploy.log`

### El webhook no se ejecuta
- Verifica que GitHub pueda acceder a la URL
- Revisa los "Recent Deliveries" en GitHub para ver el error
- Verifica que el archivo tenga permisos de lectura

## 📝 Notas de Seguridad

1. **Nunca subas la clave secreta a Git** - Usa variables de entorno o `.env`
2. **Limita el acceso** - Considera agregar IP whitelist si es posible
3. **Monitorea los logs** - Revisa regularmente para detectar intentos de acceso no autorizados
4. **Usa HTTPS** - Asegúrate de que el webhook use HTTPS, no HTTP

## 🎯 Flujo Completo

1. **Haces cambios** en tu código local
2. **Haces commit y push** a GitHub
3. **GitHub detecta el push** y envía POST al webhook
4. **El webhook verifica la firma** (seguridad)
5. **Ejecuta `deploy.sh`** en segundo plano
6. **El sitio se actualiza** automáticamente

¡Listo! 🚀

