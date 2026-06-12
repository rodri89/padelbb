# 🔧 Solución: "failed to connect to host"

## Problema
GitHub no puede conectarse al servidor para entregar el webhook.

## Solución 1: Usar el archivo PHP directo (Recomendado)

En lugar de usar la ruta de Laravel (`/deploy-webhook`), usa el archivo PHP directo que ya existe:

### Configurar en GitHub:

1. Ve a: `https://github.com/rodri89/bahiapadel/settings/hooks`
2. Edita el webhook o crea uno nuevo
3. Cambia la **Payload URL** a:
   ```
   https://bahiapadel2.padelbb.com/deploy-webhook.php
   ```
   (Nota: termina en `.php`, no solo `/deploy-webhook`)
4. Mantén el mismo **Secret**: `0e6ce09117155a7105a38e7355fe5356f03c19b03a4baa3d155fedd77d678296`
5. Guarda

### Ventajas:
- ✅ No requiere pasar por el router de Laravel
- ✅ Más directo y rápido
- ✅ Menos dependencias (no necesita CSRF, sesiones, etc.)
- ✅ Ya está configurado y funcionando

## Solución 2: Verificar acceso desde fuera

Prueba si la URL es accesible desde fuera del servidor:

```bash
# Desde tu máquina local o cualquier servidor externo
curl -I https://bahiapadel2.padelbb.com/deploy-webhook.php
```

Si esto funciona pero GitHub no puede conectarse, puede ser:
- Firewall bloqueando IPs de GitHub
- ModSecurity bloqueando peticiones
- Restricciones de Hostinger

## Solución 3: Verificar firewall de Hostinger

1. Ve a hPanel de Hostinger
2. Busca "Firewall" o "Seguridad"
3. Verifica si hay reglas que bloqueen conexiones
4. Si es necesario, contacta al soporte de Hostinger para permitir IPs de GitHub

## Solución 4: Usar GitHub Actions (Alternativa)

Si el webhook directo no funciona, puedes usar GitHub Actions para hacer el despliegue:

1. Crea `.github/workflows/deploy.yml`
2. Configura un workflow que se ejecute en push
3. El workflow puede hacer SSH al servidor y ejecutar comandos

## Verificación

Después de cambiar la URL a `deploy-webhook.php`:

1. Haz clic en "Test delivery" en GitHub
2. Verifica el estado (debería ser 200)
3. En el servidor, verifica el log:
   ```bash
   tail -f storage/logs/webhook-deploy.log
   ```

