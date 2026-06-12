# 🔍 Diagnóstico del Webhook

## Estado Actual
Solo se ve la petición de prueba manual, pero NO hay peticiones del push reciente a GitHub.

## Posibles Causas

### 1. Webhook NO está configurado en GitHub
- El webhook no existe en GitHub
- El webhook está desactivado

### 2. Webhook configurado incorrectamente
- URL incorrecta
- Secret incorrecto
- Eventos incorrectos (no está escuchando "push")

### 3. Webhook configurado pero fallando
- GitHub no puede alcanzar la URL
- Error de SSL/certificado
- Timeout

## Cómo Verificar

### Paso 1: Verificar en GitHub
1. Ve a: `https://github.com/rodri89/bahiapadel/settings/hooks`
2. ¿Ves algún webhook configurado?
   - Si NO: Necesitas crear uno
   - Si SÍ: Verifica la configuración

### Paso 2: Verificar la Configuración del Webhook
Si el webhook existe, verifica:
- ✅ **Payload URL**: `https://bahiapadel2.padelbb.com/deploy-webhook`
- ✅ **Content type**: `application/json`
- ✅ **Secret**: `0e6ce09117155a7105a38e7355fe5356f03c19b03a4baa3d155fedd77d678296`
- ✅ **Which events**: "Just the push event" (o "Send me everything")
- ✅ **Active**: Debe estar marcado

### Paso 3: Ver "Recent Deliveries"
1. Haz clic en el webhook
2. Ve a "Recent Deliveries"
3. ¿Ves alguna petición del push reciente?
   - Si SÍ: Verifica el estado (200 = éxito, 403 = firma inválida, etc.)
   - Si NO: El webhook no se activó

### Paso 4: Probar el Webhook Manualmente desde GitHub
1. En la página del webhook, haz clic en "Recent Deliveries"
2. Haz clic en "Redeliver" en una petición anterior
3. O haz clic en "Test delivery" para enviar una petición de prueba

## Solución: Crear/Configurar el Webhook

Si el webhook NO existe o está mal configurado:

1. Ve a: `https://github.com/rodri89/bahiapadel/settings/hooks`
2. Haz clic en "Add webhook"
3. Configura:
   - **Payload URL**: `https://bahiapadel2.padelbb.com/deploy-webhook`
   - **Content type**: `application/json`
   - **Secret**: `0e6ce09117155a7105a38e7355fe5356f03c19b03a4baa3d155fedd77d678296`
   - **Which events**: "Just the push event"
   - **Active**: ✅ Marcado
4. Haz clic en "Add webhook"

## Después de Configurar

Una vez configurado, haz otro push de prueba:

```bash
echo "# Test webhook 2" >> README.md
git add README.md
git commit -m "Test: Verificar webhook después de configuración"
git push origin main
```

Luego verifica en el servidor:
```bash
tail -f storage/logs/webhook-deploy.log
```

Deberías ver una nueva entrada con la petición de GitHub.

