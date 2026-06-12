# Fotos de jugadores en producción

Las fotos de jugadores se guardan en **storage** (no en `public`) para que **no se pierdan en cada deploy**.

## En producción (obligatorio)

Después de cada deploy, ejecutá **una sola vez** (o si cambió el servidor):

**Opción 1 – por navegador:** entrá a:

```
https://tu-dominio.com/storage-link
```

**Opción 2 – por consola:**

```bash
php artisan storage:link
```

En ambos casos se crea el enlace `public/storage` → `storage/app/public`. Así las fotos en `storage/app/public/images/jugadores/` se sirven en la URL `/storage/images/jugadores/...` y no se borran al actualizar el código.

*(Si querés que solo un admin pueda ejecutar `/storage-link`, mové esa ruta dentro del grupo con middleware `auth` y `usuarioAdminPadel` en `routes/web.php`.)*

## Rutas

- **Nuevas subidas:** se guardan en `storage/app/public/images/jugadores/` y en la BD la ruta es `storage/images/jugadores/nombre_archivo.jpg`.
- **Fotos antiguas** (si tenían `images/jugadores/...`) siguen funcionando desde `public/images/jugadores/` si esos archivos siguen ahí.

## Si ya subiste fotos en prod y se perdieron

Las que estaban en `public/images/jugadores/` se pierden si el deploy reemplaza esa carpeta. A partir de ahora, con `storage` y `storage:link`, las nuevas fotos **persisten** entre deploys.
