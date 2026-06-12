# 🔍 Diagnóstico: "failed to connect to host"

## Problema
GitHub no puede conectarse al servidor para entregar el webhook.

## Posibles Causas

### 1. Firewall bloqueando conexiones de GitHub
- Hostinger puede estar bloqueando IPs externas
- El servidor puede requerir whitelist de IPs de GitHub

### 2. URL no accesible públicamente
- El subdominio no está configurado correctamente
- Problemas de DNS

### 3. Restricciones de seguridad del servidor
- ModSecurity bloqueando peticiones
- Reglas de seguridad de Hostinger

## Soluciones

### Solución 1: Verificar que la URL es accesible

Prueba desde tu máquina local o desde otro servidor:

```bash
curl -I https://bahiapadel2.padelbb.com/deploy-webhook
```

Debería responder con un código HTTP (200, 403, 405, etc.), NO un error de conexión.

### Solución 2: Verificar IPs de GitHub

GitHub usa estas IPs para webhooks:
- `140.82.112.0/20`
- `143.55.64.0/20`
- `185.199.108.0/22`
- `192.30.252.0/22`
- `2a0a:a440::/29`
- `2606:50c0::/32`

Si Hostinger tiene un firewall, necesitas permitir estas IPs.

### Solución 3: Usar un servicio intermedio (alternativa)

Si no puedes configurar el firewall, puedes usar:
- **GitHub Actions** para hacer el despliegue
- **Un servicio de webhook proxy** (como webhook.site para pruebas)
- **Un script que se ejecute periódicamente** (cron job)

### Solución 4: Verificar configuración de Hostinger

1. Ve a hPanel de Hostinger
2. Busca configuración de firewall o seguridad
3. Verifica si hay reglas que bloqueen conexiones entrantes

### Solución 5: Usar un endpoint alternativo

Si el problema persiste, podemos crear un endpoint más simple que no requiera autenticación especial.

## Verificación Rápida

Ejecuta esto en el servidor para verificar que el endpoint responde:

```bash
# Desde el servidor mismo
curl -X POST http://localhost/deploy-webhook \
  -H "Content-Type: application/json" \
  -d '{"test": "data"}'
```

Si funciona desde localhost pero no desde GitHub, es un problema de firewall/red.

