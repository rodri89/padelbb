# Webhook deploy — padelbb.com

Repo: https://github.com/rodri89/padelbb

## Endpoint (recomendado)

- URL: `https://padelbb.com/deploy-webhook`
- Controlador: `DeployWebhookController`
- Fallback: `https://padelbb.com/deploy-webhook.php`

## Secret

Generado para este proyecto (no compartir con bahiapadel2). Ver `CLAVE-SECRETA-WEBHOOK.txt` en local.

## GitHub

Settings → Webhooks → Add webhook:

- **Payload URL**: `https://padelbb.com/deploy-webhook`
- **Content type**: `application/json`
- **Secret**: (desde CLAVE-SECRETA-WEBHOOK.txt)
- **Events**: Just the push event

## Servidor

- Path: `/home/u895805914/domains/padelbb.com/public_html`
- Script: `./deploy.sh`
- Log: `storage/logs/webhook-deploy.log`

## Probar

```bash
git commit --allow-empty -m "test deploy"
git push origin main
```

En el servidor:

```bash
tail -20 storage/logs/webhook-deploy.log
```
