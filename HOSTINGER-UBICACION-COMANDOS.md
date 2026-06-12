# 📍 Dónde Encontrar la Configuración de Comandos en Hostinger

## 🔍 Ubicaciones Posibles:

### Opción 1: Panel de Control Principal
1. Entra a tu **Panel de Hostinger** (hPanel)
2. Busca la sección **"Git"** o **"Repositorios Git"**
3. Selecciona tu repositorio (`bahiapadel`)
4. Busca opciones como:
   - **"Configurar Despliegue"**
   - **"Deploy Settings"**
   - **"Auto Deploy"**
   - **"Post-Deploy Commands"**
   - **"Comandos Post-Despliegue"**

### Opción 2: Desde el Gestor de Archivos
1. Ve a **"Administrador de Archivos"** o **"File Manager"**
2. Navega a la carpeta de tu sitio (donde está el proyecto)
3. Busca un archivo `.htaccess` o configuración de despliegue
4. O busca opciones de **"Configuración"** o **"Settings"**

### Opción 3: Desde el Selector de Git
1. En el panel principal, busca **"Git"**
2. Haz clic en tu repositorio
3. Busca pestañas como:
   - **"Deploy"**
   - **"Settings"**
   - **"Configuración"**
   - **"Advanced"** (Avanzado)

### Opción 4: Si No Existe la Opción de Comandos

Si Hostinger **no tiene opción para comandos personalizados**, el despliegue automático solo hace `git pull`. En ese caso:

**Solución: Usar SSH o Terminal**

1. **Accede por SSH a Hostinger** (si está disponible)
2. **Navega a la carpeta del proyecto**
3. **Ejecuta los comandos manualmente:**

```bash
cd /home/usuario/dominio.com/public_html
# O la ruta que Hostinger te indique

# Instalar dependencias
composer install --no-dev --optimize-autoloader --no-interaction

# Limpiar cachés
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Regenerar cachés
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 🎯 Alternativa: Script de Despliegue Manual

Si no puedes configurar comandos automáticos, crea un script que ejecutes después de cada `git pull`:

### Crear archivo `deploy.sh` en la raíz del proyecto:

```bash
#!/bin/bash
composer install --no-dev --optimize-autoloader --no-interaction
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

Luego en Hostinger (por SSH):
```bash
chmod +x deploy.sh
./deploy.sh
```

## 📋 Pasos para Encontrar la Configuración:

1. **Busca en el menú principal:**
   - Git
   - Repositorios
   - Deploy
   - Auto Deploy

2. **Revisa las pestañas/opciones:**
   - Settings
   - Configuración
   - Advanced
   - Deploy Settings

3. **Si no encuentras nada:**
   - Contacta al soporte de Hostinger
   - O pregunta: "¿Dónde configuro comandos post-despliegue para Git?"

## 🔧 Si Hostinger Solo Hace Git Pull:

En ese caso, necesitarás:

1. **Acceder por SSH** después de cada despliegue
2. **Ejecutar los comandos manualmente**
3. **O configurar un cron job** que ejecute los comandos automáticamente

## 💡 Pregunta para Hostinger:

Si contactas al soporte, pregunta:
- "¿Dónde puedo configurar comandos que se ejecuten automáticamente después de un despliegue Git?"
- "¿Tiene Hostinger soporte para comandos post-deploy?"

