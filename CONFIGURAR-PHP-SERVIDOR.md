# 🔧 Configurar PHP 8.3 en el Servidor Hostinger

## Problema
El servidor está usando PHP 7.4.33 por defecto, pero el proyecto requiere PHP 8.2.0 o superior.

## Solución

### Opción 1: Usar Scripts Wrapper (Recomendado)

1. **Ejecutar el script de configuración:**

```bash
cd /home/u895805914/domains/padelbb.com/public_html/bahiapadel2
git pull origin main
chmod +x configurar-php83.sh
./configurar-php83.sh
```

Este script creará:
- `./php83` - Ejecuta PHP 8.3
- `./artisan83` - Ejecuta artisan con PHP 8.3
- `./composer83` - Ejecuta composer con PHP 8.3

2. **Usar los scripts wrapper:**

```bash
# En lugar de: php artisan config:cache
./artisan83 config:cache

# En lugar de: composer install
./composer83 install

# En lugar de: php artisan route:list
./artisan83 route:list
```

### Opción 2: Configurar PHP 8.3 en Hostinger hPanel

1. **Acceder a hPanel:**
   - Ve a: `https://hpanel.hostinger.com`
   - Inicia sesión

2. **Configurar PHP para el dominio/subdominio:**
   - Ve a: **Dominios** → **Administrar**
   - Busca `bahiapadel2.padelbb.com` o `padelbb.com`
   - Haz clic en **Configurar PHP**
   - Selecciona **PHP 8.3**
   - Guarda los cambios

3. **Verificar:**
   ```bash
   php -v
   ```
   Debería mostrar PHP 8.3.x

### Opción 3: Usar Selector de PHP (cPanel/CloudLinux)

Si tienes acceso a cPanel o CloudLinux:

```bash
# Ver versiones disponibles
ls -la /opt/alt/php*/usr/bin/php

# Configurar para el dominio (requiere permisos root o sudo)
# Esto normalmente se hace desde el panel de control
```

## Verificar la Configuración

```bash
# Verificar versión de PHP
/opt/alt/php83/usr/bin/php -v

# Debería mostrar: PHP 8.3.28 (cli)
```

## Actualizar deploy.sh

El `deploy.sh` ya está configurado para usar PHP 8.3 explícitamente:

```bash
PHP_BIN="/opt/alt/php83/usr/bin/php"
COMPOSER_CMD="$PHP_BIN /opt/alt/php83/usr/bin/composer"
ARTISAN_CMD="$PHP_BIN artisan"
```

## Comandos Importantes

### Limpiar caché (usando PHP 8.3):
```bash
./artisan83 config:clear
./artisan83 cache:clear
./artisan83 route:clear
./artisan83 view:clear
```

### Regenerar caché:
```bash
./artisan83 config:cache
./artisan83 route:cache
```

### Instalar dependencias:
```bash
./composer83 install --no-dev --optimize-autoloader --no-interaction
```

### Verificar rutas:
```bash
./artisan83 route:list | grep webhook
```

## Nota Importante

Si configuras PHP 8.3 desde hPanel (Opción 2), el comando `php` usará PHP 8.3 automáticamente y no necesitarás los scripts wrapper. Sin embargo, los scripts wrapper son útiles si no puedes cambiar la versión global de PHP o si quieres mantener compatibilidad con otros proyectos.

