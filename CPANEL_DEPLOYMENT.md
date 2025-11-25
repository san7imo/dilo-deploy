# 🎛️ Guía de Despliegue en cPanel - Dilo Records

**Este documento es específico para despliegues en cPanel/WHM**

---

## ⚠️ Cambios Necesarios vs Deployment Linux Estándar

### Lo que CAMBIA en cPanel

| Item | Linux Estándar | cPanel | Razón |
|------|---|---|---|
| **Ubicación** | `/var/www/` | `/home/username/public_html/` | cPanel usa estructura de usuario |
| **Usuario PHP** | `www-data` | `username` (tu usuario cPanel) | Permisos de cPanel |
| **Gestor de Procesos** | Systemd | cPanel AutoSSL + cPanel | Integración cPanel |
| **SSL Certificate** | Let's Encrypt manual | AutoSSL de cPanel | Renovación automática |
| **Queue Workers** | Supervisor | Cron + Background Process | Sin acceso root |
| **Nginx** | Manual | AutoInstaller de cPanel | Mejor integración |
| **Composer** | Sistema global | Local en el proyecto | Evita conflictos |
| **Node.js** | NVM/Sistema | NodeJS Selector de cPanel | Interfaz gráfica |

---

## 🚀 Guía Paso a Paso en cPanel

### PASO 1: Preparar el Entorno en cPanel

#### 1.1 Acceder a cPanel

```
URL: https://tu-servidor.com:2083
Usuario: tu_usuario_cpanel
Contraseña: contraseña_cpanel
```

#### 1.2 Instalar PHP 8.3 y Extensiones

**Via cPanel UI:**
1. Login en cPanel
2. **Home → Software → MultiPHP Manager**
3. Seleccionar dominio
4. Elegir PHP 8.3 (o versión superior)
5. Click "Apply"

**Via Terminal (cPanel Shell Access):**

```bash
# Acceder a Shell Access en cPanel primero
# Home → Advanced → Terminal

# Verificar versión PHP actual
php -v

# Si necesitas cambiar versión:
# Usar MultiPHP Manager desde interfaz gráfica
```

#### 1.3 Instalar Extensiones PHP Necesarias

**Via cPanel UI:**
1. **Home → Software → Select PHP Version**
2. Click en tu PHP 8.3
3. Marcar extensiones:
   - ✅ bcmath
   - ✅ ctype
   - ✅ curl
   - ✅ gd
   - ✅ mbstring
   - ✅ mysql (MySQLi o PDO MySQL)
   - ✅ openssl
   - ✅ xml
   - ✅ zip
   - ✅ redis (opcional pero recomendado)

4. Click "Save"

**Verificar en Terminal:**
```bash
php -m | grep -E "curl|gd|mbstring|mysql|xml|zip"
```

---

### PASO 2: Descargar el Código

#### 2.1 Descargar via Git (Recomendado)

```bash
# Acceder via SSH/Terminal de cPanel
cd ~/public_html

# Si ya existe un folder, borrarlo primero
rm -rf ./* ./.*

# Clonar el repositorio
git clone https://github.com/tu-usuario/dilo-records.git .

# Checkout a rama deseada (si no es main/master)
git checkout main
```

#### 2.2 O Descargar via FTP

1. **Home → Files → File Manager**
2. Ir a `public_html`
3. Subir archivo ZIP del proyecto
4. Click derecho → Extract
5. Mover archivos si es necesario

---

### PASO 3: Instalar Dependencias

#### 3.1 Instalar Composer Localmente

```bash
cd ~/public_html

# Descargar Composer (local)
curl -sS https://getcomposer.org/installer | php

# Instalar dependencias (sin dev)
php composer.phar install --no-dev --optimize-autoloader

# Limpiar
rm composer.phar
```

**Alternativa si Composer está disponible globalmente:**
```bash
cd ~/public_html
composer install --no-dev --optimize-autoloader
```

#### 3.2 Instalar Node.js y Assets

**Via cPanel UI (Recomendado):**
1. **Home → Software → NodeJS Selector**
2. Click en tu dominio
3. Seleccionar Node.js 18+ o 20+
4. Click "Create"
5. Copiar el comando `export` que aparece

**En Terminal:**
```bash
# Verificar Node disponible
node -v
npm -v

# Si no está disponible, usar nvm
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
source ~/.bashrc
nvm install 18
nvm use 18

# Instalar dependencias npm
cd ~/public_html
npm ci --omit=dev

# Compilar assets
npm run build
```

---

### PASO 4: Configurar Variables de Entorno

#### 4.1 Crear archivo .env.production

```bash
cd ~/public_html

# Copiar template
cp .env.production.example .env.production

# Editar con tus valores
nano .env.production
```

#### 4.2 Variables Específicas para cPanel

```bash
# .env.production

APP_NAME="Dilo Records"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

# ========================
# ⚠️ IMPORTANTE EN cPANEL
# ========================

# Base de datos (cPanel)
DB_CONNECTION=mysql
DB_HOST=localhost        # En cPanel siempre es localhost
DB_PORT=3306
DB_DATABASE=username_dbname  # cPanel usa formato: usuario_nombredb
DB_USERNAME=username_dbuser  # cPanel usa formato: usuario_dbuser
DB_PASSWORD=TU_CONTRASEÑA_FUERTE

# Session - Cambiar a archivo en cPanel (sin Redis)
SESSION_DRIVER=file
# SESSION_DRIVER=cookie  # Alternativa
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIES=true
SESSION_HTTP_ONLY=true

# Cache - Cambiar a archivo en cPanel (sin Redis)
CACHE_STORE=file
# CACHE_STORE=redis  # Solo si tienes Redis en cPanel

# Queue - Cambiar a base de datos en cPanel
QUEUE_CONNECTION=database
# QUEUE_CONNECTION=sync  # Para testing (NO para producción)

# Redis - COMENTAR si no disponible en cPanel
# REDIS_CLIENT=phpredis
# REDIS_HOST=redis-host
# REDIS_PASSWORD=null
# REDIS_PORT=6379

# ImageKit
IMAGEKIT_PUBLIC_KEY=tu_public_key
IMAGEKIT_PRIVATE_KEY=tu_private_key
IMAGEKIT_URL_ENDPOINT=tu_url_endpoint

# Mail
MAIL_MAILER=smtp
MAIL_HOST=tu_mail_host
MAIL_PORT=587
MAIL_USERNAME=tu_email@dominio.com
MAIL_PASSWORD=contraseña_email
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@tu-dominio.com"
MAIL_FROM_NAME="Dilo Records"

# CORS - Cambiar a tu dominio
CORS_ALLOWED_ORIGINS=https://tu-dominio.com,https://www.tu-dominio.com
SANCTUM_STATEFUL_DOMAINS=tu-dominio.com,www.tu-dominio.com

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=info
```

---

### PASO 5: Configurar Base de Datos

#### 5.1 Crear BD via cPanel UI

1. **Home → Databases → MySQL Databases**
2. Crear nueva BD:
   - Database name: `dilo_records_prod`
   - Click "Create Database"
3. Crear usuario:
   - Username: `dilo_prod_user`
   - Password: generada aleatoriamente
   - Click "Create User"
4. Añadir permisos:
   - Select usuarios creado
   - All Privileges ✅
   - Click "Make Changes"

#### 5.2 Ejecutar Migraciones

```bash
cd ~/public_html

# Generar APP_KEY
php artisan key:generate --env=production

# Ejecutar migraciones
php artisan migrate --env=production --force

# Crear usuario admin (opcional pero recomendado)
php artisan tinker
> User::create(['name' => 'Admin', 'email' => 'admin@dilorecords.com', 'password' => bcrypt('CONTRASEÑA_FUERTE')])
> exit
```

---

### PASO 6: Permisos de Carpetas

#### 6.1 Establecer Permisos Correctos

```bash
cd ~/public_html

# Crear directorios si no existen
mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views
mkdir -p bootstrap/cache

# Permisos para las carpetas storage y bootstrap
# En cPanel, el usuario es www-data pero ejecuta como el usuario cPanel
chmod -R 755 storage bootstrap

# Permisos más restrictivos (más seguro):
chmod -R 750 storage bootstrap

# Si tienes problemas, asegurar permisos en public_html:
chmod -R 755 ~/public_html
```

---

### PASO 7: SSL/HTTPS Certificate

#### 7.1 AutoSSL de cPanel (Recomendado)

1. **Home → Domains → AutoSSL**
2. Seleccionar dominio
3. Click "Reissue"
4. Esperar confirmación

**Verificar:**
```bash
https://tu-dominio.com  # Debe tener certificado válido
```

#### 7.2 Let's Encrypt Manual (si AutoSSL falla)

```bash
# Algunos cPanel soportan Let's Encrypt via interfaz
# Home → Security → Let's Encrypt for cPanel

# O vía certbot en terminal:
sudo certbot certonly --webroot -w ~/public_html -d tu-dominio.com -d www.tu-dominio.com
```

---

### PASO 8: Configurar Queue Workers

#### ⚠️ Problema: No hay Supervisor en cPanel

En cPanel **no puedes usar Supervisor** (requiere acceso root). Alternativas:

##### Opción A: Usar Queue DATABASE (Recomendado para cPanel)

**En `.env.production`:**
```bash
QUEUE_CONNECTION=database
# Queue se ejecutará al procesar requests
```

**Crear tabla queue jobs:**
```bash
cd ~/public_html
php artisan queue:table
php artisan migrate --env=production
```

**Limitación:** Los jobs se procesan síncronamente con requests. Para mejor performance:

##### Opción B: Usar Cron para Procesar Cola

```bash
# 1. Crear script en ~/public_html/artisan-queue-worker.php

#!/usr/bin/env php
<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
exit($kernel->handle(
    $input = new Symfony\Component\Console\Input\ArgvInput,
    new Symfony\Component\Console\Output\ConsoleOutput
));

# 2. En cPanel, Home → Advanced → Cron Jobs
# Añadir cron (ejecutar cada minuto):
* * * * * cd ~/public_html && php artisan queue:work --once --max-time=60 >> storage/logs/cron.log 2>&1
```

##### Opción C: Procesar Cola Síncronamente

**En `.env.production`:**
```bash
QUEUE_CONNECTION=sync  # NO RECOMENDADO para producción
```

**Problema:** Las tareas se ejecutan en el mismo request (más lento).

---

### PASO 9: Configurar Cron Jobs

#### 9.1 Cron para Tareas Programadas

```bash
# En cPanel: Home → Advanced → Cron Jobs
# Ejecutar comando cada minuto:

* * * * * cd ~/public_html && php artisan schedule:run >> storage/logs/cron.log 2>&1
```

#### 9.2 Cron para Backups (Recomendado)

```bash
# Cada día a las 2 AM:
0 2 * * * cd ~/public_html && php artisan backup:run >> storage/logs/backup.log 2>&1

# Cada semana:
0 3 * * 0 cd ~/public_html && php artisan backup:clean >> storage/logs/backup.log 2>&1
```

---

### PASO 10: Optimizaciones de Laravel

```bash
cd ~/public_html

# Limpiar caches existentes
php artisan cache:clear --env=production
php artisan config:clear --env=production
php artisan view:clear --env=production

# Optimizar para producción
php artisan config:cache --env=production
php artisan view:cache --env=production
php artisan route:cache --env=production

# Verificar que APP_DEBUG=false en .env.production
grep "APP_DEBUG" .env.production
# Debe mostrar: APP_DEBUG=false

# Health check
curl https://tu-dominio.com/up
# Debe retornar: OK (status 200)
```

---

### PASO 11: Verificar Instalación

```bash
# Test básico de PHP
curl https://tu-dominio.com/
# Debe cargar la página principal

# Check de logs
tail -f ~/public_html/storage/logs/laravel.log

# Verificar estructura de archivos
ls -la ~/public_html/
# Debe tener: app/ bootstrap/ config/ database/ resources/ routes/ ...
```

---

## 📋 Diferencias Principales: cPanel vs Linux Estándar

### Comandos Diferentes

```bash
# ============================================
# DEPLOYMENT SCRIPT ADAPTADO PARA cPANEL
# ============================================

#!/bin/bash

set -e

LOG_FILE="~/public_html/storage/logs/deploy.log"

log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

success() {
    echo "[✓] $1" | tee -a "$LOG_FILE"
}

error() {
    echo "[✗] $1" | tee -a "$LOG_FILE"
    exit 1
}

# Cambiar a directorio
cd ~/public_html || error "No se pudo acceder a ~/public_html"

log "=== DEPLOYMENT INICIADO ==="

# 1. Descargar último código
log "Descargando código..."
git fetch origin || error "Git fetch falló"
git pull origin main || error "Git pull falló"
success "Código actualizado"

# 2. Instalar dependencias
log "Instalando dependencias PHP..."
php composer.phar install --no-dev --optimize-autoloader || error "Composer install falló"
success "Dependencias PHP instaladas"

# 3. Instalar assets
log "Compilando assets..."
npm ci --omit=dev || error "npm ci falló"
npm run build || error "npm build falló"
success "Assets compilados"

# 4. Ejecutar migraciones
log "Ejecutando migraciones..."
php artisan migrate --env=production --force || error "Migraciones fallaron"
success "Migraciones completadas"

# 5. Limpiar caches
log "Limpiando caches..."
php artisan cache:clear --env=production
php artisan config:clear --env=production
php artisan view:clear --env=production
success "Caches limpiados"

# 6. Optimizar para producción
log "Optimizando para producción..."
php artisan config:cache --env=production
php artisan view:cache --env=production
php artisan route:cache --env=production
success "Optimizaciones aplicadas"

# 7. Verificar salud
log "Verificando salud de la aplicación..."
HEALTH=$(curl -s https://tu-dominio.com/up)
if [ "$HEALTH" == "OK" ]; then
    success "✓ Aplicación saludable"
else
    error "✗ Health check falló: $HEALTH"
fi

log "=== DEPLOYMENT COMPLETADO ==="
```

---

## 🔐 Consideraciones de Seguridad en cPanel

### ✅ Hacer

```bash
# 1. Proteger .env.production
chmod 600 ~/public_html/.env.production

# 2. Proteger storage
chmod 750 ~/public_html/storage
chmod 750 ~/public_html/bootstrap

# 3. Deshabilitar debug
APP_DEBUG=false

# 4. Usar HTTPS
# Todos los links deben ser https://

# 5. Proteger archivos sensibles
echo "deny from all" > ~/public_html/storage/.htaccess
echo "deny from all" > ~/public_html/bootstrap/.htaccess
```

### ❌ NO Hacer

```bash
# ✗ No ejecutar como root
# ✗ No usar 777 permisos (muy inseguro)
# ✗ No guardar passwords en código
# ✗ No dejar APP_DEBUG=true en producción
# ✗ No acceder a /admin sin contraseña fuerte
```

---

## 📊 Comparativa: Opciones en cPanel

| Característica | cPanel Standard | cPanel + Redis | cPanel + Dedicated |
|---|---|---|---|
| **Base de Datos** | MySQL Compartido | MySQL Compartido | MySQL Dedicado |
| **Cache** | File | Redis | Redis |
| **Queue** | Database/Sync | Database/Redis | Redis/Supervisor |
| **Costo** | $$ | $$$ | $$$$ |
| **Performance** | Bueno | Excelente | Excelente |
| **Recomendado** | Pequeño/Mediano | Mediano | Grande |

---

## ⚠️ Troubleshooting Común en cPanel

### Problema: "Permission denied" en storage/

```bash
# Solución:
chmod -R 755 ~/public_html/storage
chmod -R 755 ~/public_html/bootstrap
```

### Problema: "Class not found" después de deploy

```bash
# Solución:
cd ~/public_html
php artisan cache:clear --env=production
php artisan config:clear --env=production
php artisan autoload:dump
```

### Problema: CSS/JS no cargan en producción

```bash
# Solución:
cd ~/public_html
npm run build  # Recompilar
php artisan view:clear --env=production
```

### Problema: Base de datos no conecta

```bash
# Verificar credenciales en .env.production
# En cPanel, el host siempre es: localhost
# El nombre de BD es: usuario_nombredb
# El usuario es: usuario_dbuser

# Test de conexión:
mysql -u usuario_dbuser -p < /dev/null
```

### Problema: Emails no se envían

```bash
# Verificar credenciales en .env.production
MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD

# Test:
cd ~/public_html
php artisan tinker
> Mail::send(...)
```

---

## 🚀 Resumen: 11 Pasos para cPanel

1. ✅ Instalar PHP 8.3 + Extensiones via MultiPHP Manager
2. ✅ Descargar código (Git o FTP)
3. ✅ Instalar Composer localmente
4. ✅ Instalar dependencias PHP
5. ✅ Instalar Node.js + compilar assets
6. ✅ Configurar .env.production
7. ✅ Crear BD y ejecutar migraciones
8. ✅ Establecer permisos correctamente
9. ✅ Configurar SSL (AutoSSL o Let's Encrypt)
10. ✅ Configurar Cron jobs (queue + scheduled tasks)
11. ✅ Optimizar y verificar

---

## 📞 Recursos útiles

- [cPanel Documentation](https://docs.cpanel.net/)
- [Laravel Production Deployment](https://laravel.com/docs/11.x/deployment)
- [Let's Encrypt in cPanel](https://support.cpanel.net/hc/en-us/articles/1500001494561)
- [Managing PHP in cPanel](https://support.cpanel.net/hc/en-us/articles/360051992634)

---

**¿Necesitas ayuda con algún paso específico?** 🎛️

