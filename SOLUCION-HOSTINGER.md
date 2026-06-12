# 🚨 Solución Inmediata para Error de Despliegue en Hostinger

## ❌ Error:
```
Your requirements could not be resolved to an installable set of packages.
Deployment failed
```

## 🔍 Diagnóstico:

Hostinger probablemente está usando **PHP 8.1 o inferior**, pero el `composer.lock` fue generado con **PHP 8.2**.

## ✅ Solución Rápida (2 Opciones):

### **Opción 1: Configurar PHP 8.2 en Hostinger (RECOMENDADO)**

1. En Hostinger, ve a **"PHP"** o **"Selector de Versión de PHP"**
2. Selecciona **PHP 8.2** (o la más cercana disponible)
3. **Guarda** los cambios
4. **Vuelve a desplegar**

### **Opción 2: Hacer el Proyecto Compatible con PHP 8.1**

Si Hostinger no tiene PHP 8.2, sigue estos pasos:

#### Paso 1: En tu máquina local, crear un composer.lock compatible con PHP 8.1

```bash
# Cambiar temporalmente composer.json a PHP 8.1
# (Ya creé composer.hostinger.json como referencia)

# Opción A: Modificar composer.json temporalmente
# Cambia "php": "^8.2" a "php": "^8.1"

# Opción B: Usar el archivo alternativo
cp composer.hostinger.json composer.json

# Regenerar composer.lock con PHP 8.1
composer-mamp update --lock

# Restaurar composer.json original
git checkout composer.json

# Subir solo el nuevo composer.lock
git add composer.lock
git commit -m "Ajustar composer.lock para PHP 8.1 (Hostinger)"
git push
```

#### Paso 2: En Hostinger

1. Asegúrate de que **PHP 8.1** esté seleccionado
2. Vuelve a desplegar

## 🔧 Comandos de Despliegue para Hostinger:

Configura estos comandos en el panel de Hostinger:

```bash
# Limpiar instalación previa
rm -rf vendor/

# Instalar dependencias (sin dev, con optimización)
composer install --no-dev --optimize-autoloader --no-interaction

# Si falla, intentar sin optimización
composer install --no-dev --no-interaction
```

## 📋 Checklist de Verificación:

- [ ] **Versión de PHP en Hostinger**: ¿8.1, 8.2 u otra?
- [ ] **Comando de despliegue**: Usa `composer install` (no `composer-mamp`)
- [ ] **Flag --no-dev**: Incluido para producción
- [ ] **Logs de error**: Revisa el error específico en Hostinger

## 🆘 Si Nada Funciona:

1. **Accede por SSH a Hostinger** (si está disponible)
2. **Ejecuta manualmente:**
   ```bash
   php -v  # Ver versión de PHP
   composer --version  # Ver versión de Composer
   composer install --no-dev --dry-run  # Ver qué falla
   ```

3. **Comparte el error específico** de los logs para diagnosticar mejor

## 💡 Recomendación:

**Primero intenta la Opción 1** (configurar PHP 8.2 en Hostinger). Es la solución más limpia y mantiene la compatibilidad con tu entorno local.

Si Hostinger no tiene PHP 8.2 disponible, entonces usa la **Opción 2** para hacer el proyecto compatible con PHP 8.1.

