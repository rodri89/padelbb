# 🔧 Solución al Error de Despliegue en Hostinger

## ❌ Error Actual:
```
update: Your requirements could not be resolved to an installable set of packages.
Deployment failed
```

## 🔍 Causa del Problema:

Hostinger está intentando instalar las dependencias pero no puede resolverlas. Esto generalmente ocurre porque:

1. **Versión de PHP incorrecta** - Hostinger está usando una versión diferente a PHP 8.2
2. **composer.lock incompatible** - El lock fue generado con PHP 8.2 pero Hostinger usa otra versión

## ✅ Solución Paso a Paso:

### Paso 1: Verificar Versión de PHP en Hostinger

1. En el panel de Hostinger, ve a **"PHP"** o **"Selector de Versión de PHP"**
2. **Verifica qué versión está seleccionada**
3. **Cámbiala a PHP 8.2** (o la más cercana: 8.1, 8.3)
4. **Guarda los cambios**

### Paso 2: Limpiar y Regenerar composer.lock (Si es necesario)

Si Hostinger no tiene PHP 8.2 disponible, puedes hacer el proyecto compatible con PHP 8.1:

**Opción A: Si Hostinger tiene PHP 8.2:**
- Solo asegúrate de que esté seleccionado en el panel
- El composer.lock actual debería funcionar

**Opción B: Si Hostinger solo tiene PHP 8.1:**
Necesitarás ajustar el `composer.json` temporalmente:

```json
"require": {
    "php": "^8.1",
    ...
}
```

Luego regenerar:
```bash
composer-mamp update --lock
```

### Paso 3: Configurar Comandos de Despliegue Correctamente

En Hostinger, configura estos comandos en orden:

```bash
# 1. Limpiar cualquier instalación previa
rm -rf vendor/

# 2. Instalar dependencias
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Si falla, intentar sin optimización
composer install --no-dev --no-interaction
```

### Paso 4: Verificar Logs de Error Específicos

En Hostinger, busca:
- **"Logs de Despliegue"**
- **"Detalles del Error"**
- **"Ver Log Completo"**

El error específico te dirá qué paquete está fallando.

## 🚨 Soluciones Alternativas:

### Si el error persiste, prueba esto:

**1. Forzar reinstalación:**
```bash
rm -rf vendor/ composer.lock
composer install --no-dev --optimize-autoloader
```

**2. Instalar sin optimización:**
```bash
composer install --no-dev --no-scripts
```

**3. Verificar memoria:**
Agrega al inicio del comando:
```bash
php -d memory_limit=512M /usr/local/bin/composer install --no-dev --optimize-autoloader
```

## 📋 Checklist de Verificación:

- [ ] PHP 8.2 (o 8.1/8.3) está seleccionado en Hostinger
- [ ] El comando de despliegue usa `composer install` (no `composer-mamp`)
- [ ] El flag `--no-dev` está incluido
- [ ] Los logs muestran el error específico
- [ ] El directorio `vendor/` tiene permisos correctos

## 🔍 Comandos de Diagnóstico:

Si puedes acceder por SSH a Hostinger, ejecuta:

```bash
# Ver versión de PHP
php -v

# Ver versión de Composer
composer --version

# Verificar permisos
ls -la vendor/ storage/ bootstrap/cache/

# Probar instalación manual
composer install --no-dev --dry-run
```

## 💡 Próximos Pasos:

1. **Comparte el error específico** de los logs de Hostinger
2. **Confirma la versión de PHP** que está usando Hostinger
3. Con esa información podremos ajustar la configuración exacta

