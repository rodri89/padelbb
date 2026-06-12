# 🔧 Comandos de Despliegue para Hostinger (PHP 8.2 Configurado)

## ✅ PHP 8.2 ya está seleccionado - El problema es otro

## 🔍 Posibles Causas del Error:

1. **Falta memoria durante la instalación**
2. **Extensiones PHP faltantes** (pdo_mysql, mbstring, etc.)
3. **Comando de despliegue incorrecto**
4. **Permisos de archivos/carpetas**

## 📋 Comandos de Despliegue para Configurar en Hostinger:

### Opción 1: Comando Completo (Recomendado)

En Hostinger → Git → Configurar Despliegue, usa:

```bash
php -d memory_limit=512M /usr/local/bin/composer install --no-dev --optimize-autoloader --no-interaction && php artisan config:clear && php artisan cache:clear && php artisan view:clear && php artisan route:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### Opción 2: Comando Simplificado (Si la Opción 1 falla)

```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

### Opción 3: Sin Optimización (Si hay problemas de memoria)

```bash
composer install --no-dev --no-interaction
```

### Opción 4: Paso a Paso (Para debugging)

```bash
# Paso 1: Limpiar
rm -rf vendor/

# Paso 2: Instalar
composer install --no-dev --no-interaction

# Paso 3: Si funciona, optimizar
composer dump-autoload --optimize --no-dev
```

## 🔧 Verificar Extensiones PHP en Hostinger:

En Hostinger → Configuración de PHP → **"Extensiones PHP"**, asegúrate de tener habilitadas:

- ✅ **pdo_mysql** (o pdo)
- ✅ **mbstring**
- ✅ **openssl**
- ✅ **tokenizer**
- ✅ **xml**
- ✅ **ctype**
- ✅ **json**
- ✅ **fileinfo**
- ✅ **curl**
- ✅ **zip**

## 🚨 Si el Error Persiste:

### 1. Revisar Logs Específicos

En Hostinger, busca:
- **"Logs de Despliegue"**
- **"Detalles del Error"**
- **"Ver Log Completo"**

El error específico te dirá:
- ¿Qué paquete está fallando?
- ¿Es un problema de memoria?
- ¿Falta alguna extensión?

### 2. Probar Instalación Manual (Si tienes SSH)

```bash
# Ver versión de PHP
php -v

# Ver versión de Composer
composer --version

# Verificar extensiones
php -m | grep -E "(pdo|mbstring|openssl)"

# Probar instalación en modo verbose
composer install --no-dev --no-interaction -vvv
```

### 3. Comandos de Diagnóstico

```bash
# Verificar memoria disponible
php -r "echo ini_get('memory_limit');"

# Verificar permisos
ls -la vendor/ storage/ bootstrap/cache/

# Probar sin scripts
composer install --no-dev --no-scripts --no-interaction
```

## 💡 Soluciones Comunes:

### Si el error es "memory_limit":
```bash
php -d memory_limit=512M composer install --no-dev --optimize-autoloader
```

### Si el error es "extension missing":
Habilita la extensión faltante en Hostinger → Configuración de PHP → Extensiones PHP

### Si el error es "permission denied":
```bash
chmod -R 755 storage bootstrap/cache
chmod -R 755 vendor
```

## 📝 Checklist Final:

- [ ] PHP 8.2 seleccionado ✅ (Ya lo tienes)
- [ ] Extensiones PHP habilitadas (pdo_mysql, mbstring, etc.)
- [ ] Comando de despliegue configurado correctamente
- [ ] Logs de error revisados para ver el error específico
- [ ] Memoria suficiente (512M o más)

## 🆘 Próximo Paso:

**Comparte el error específico** que aparece en los logs de Hostinger para diagnosticar el problema exacto.

