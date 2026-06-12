# Setup padelbb.com (raíz) — Hostinger

Sitio independiente de `bahiapadel2.padelbb.com`. Repo: https://github.com/rodri89/padelbb

## 1. hPanel — antes de desplegar

1. **Websites → padelbb.com → Manage**
2. **Backup** del contenido actual de `public_html` (hoy hay placeholder)
3. **PHP**: versión 8.2 o 8.3
4. **MySQL**: crear BD `u895805914_padelbb` + usuario con permisos
5. Importar `database/sql/padelbb_local_dump.sql` vía phpMyAdmin (subir desde local o copiar por SSH)

## 2. Document root

Tras clonar Laravel, en hPanel:

**Websites → padelbb.com → Advanced → Document Root** → `public_html/public`

## 3. Despliegue inicial (SSH)

```bash
# Subir dump (desde tu Mac):
scp database/sql/padelbb_local_dump.sql u895805914@<host-ssh-hostinger>:~/

# En el servidor:
bash scripts/hostinger-initial-setup.sh
# Editar .env con credenciales reales si el script lo pide
```

O usar **hPanel → Git → Create** conectado a `rodri89/padelbb`, rama `main`, directorio `public_html`.

## 4. `.env` producción

Copiar `.env.production.example` → `.env` y completar:

- `APP_URL=https://padelbb.com`
- `APP_KEY` (generar con `php artisan key:generate`)
- Credenciales MySQL de hPanel
- Mail / MercadoPago si aplica

## 5. Post-setup (una vez)

```bash
chmod +x deploy.sh
chmod -R 775 storage bootstrap/cache
```

Visitar: `https://padelbb.com/storage-link`

## 6. Webhook (deploy automático)

Ya configurado en GitHub para el repo `padelbb`:

- URL: `https://padelbb.com/deploy-webhook`
- Secret: ver `CLAVE-SECRETA-WEBHOOK.txt` (local, no en Git)

Cada `git push origin main` ejecuta `deploy.sh` en el servidor.

## 7. Verificación

```bash
curl -I https://padelbb.com/deploy-webhook   # debe responder 405 (GET no permitido)
tail -f storage/logs/webhook-deploy.log
```

Confirmar que `https://bahiapadel2.padelbb.com` sigue operativo (repo y webhook separados).
