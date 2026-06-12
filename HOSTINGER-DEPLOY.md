# 🚀 Guía de Despliegue en Hostinger

## ✅ Paso 1: Versión de PHP (YA CONFIGURADO)

El `composer.json` ya tiene definido:
```json
"require": {
    "php": "^8.2",
    ...
}
```

## 📋 Paso 2: Comandos de Despliegue en Hostinger

### Configuración en Panel de Hostinger:

1. Ve a **Git** → Tu repositorio → **"Configurar Despliegue"** o **"Comandos Post-Despliegue"**

2. **Elimina** cualquier comando que use `composer-mamp` (ese alias solo existe en tu máquina local)

3. **Agrega** estos comandos en orden:

```bash
# 1. Instalar dependencias (producción)
composer install --no-dev --optimize-autoloader

# 2. Limpiar cachés
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# 3. Regenerar cachés (producción)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Optimizar (opcional pero recomendado)
php artisan optimize
```

### Comandos Completos para Copiar y Pegar:

```bash
composer install --no-dev --optimize-autoloader && php artisan config:clear && php artisan cache:clear && php artisan view:clear && php artisan route:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan optimize
```

## ⚙️ Paso 3: Configurar Versión de PHP en Hostinger

1. En el panel de Hostinger, busca **"PHP"** o **"Selector de Versión de PHP"**
2. Selecciona **PHP 8.2** (o la versión más cercana disponible: 8.1, 8.3)
3. **Guarda** los cambios

## 🔄 Paso 4: Hacer el Despliegue

1. **Sube los cambios a GitHub:**
   ```bash
   git add .
   git commit -m "Actualizar composer.lock para producción"
   git push origin feature/user-registration-fix
   # O la rama que uses para producción
   ```

2. **En Hostinger:**
   - Ve a **Git** → Tu repositorio
   - Haz clic en **"Desplegar"** o **"Pull"**
   - O espera el despliegue automático si está configurado

## 🔍 Paso 5: Verificar Logs de Despliegue

Si hay errores:

1. En Hostinger, busca **"Logs"**, **"Historial de Despliegues"** o **"Detalles del Error"**
2. Revisa el error específico de Composer
3. Los errores comunes son:
   - **Versión de PHP incorrecta** → Cambia a PHP 8.2 en el selector
   - **Memoria insuficiente** → Aumenta `memory_limit` en `php.ini`
   - **Permisos** → Verifica permisos de carpetas `storage/` y `bootstrap/cache/`

## 📝 Notas Importantes

- **NO uses `composer-mamp`** en Hostinger (ese alias solo existe en tu máquina)
- **SÍ usa `composer install`** estándar en Hostinger (detectará PHP 8.2 automáticamente)
- El flag `--no-dev` es crucial para producción (no instala dependencias de desarrollo)
- `--optimize-autoloader` mejora el rendimiento en producción

## 🆘 Si Persisten los Errores

Comparte el error específico de los logs de Hostinger para diagnosticar el problema exacto.

