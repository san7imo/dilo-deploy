# 🎛️ DESPLIEGUE EN cPANEL - GUÍA RÁPIDA

## ✅ Cambios Realizados para cPanel

Se han creado **4 archivos nuevos** optimizados específicamente para desplegar en cPanel:

```
✅ CPANEL_DEPLOYMENT.md       (700+ líneas) ← LEE PRIMERO
✅ CPANEL_RESUMEN.md          (400+ líneas) ← RESUMEN DE CAMBIOS
✅ .env.cpanel.example        (Configuración optimizada para cPanel)
✅ deploy-cpanel.sh           (Script de deployment automatizado)
```

---

## 🚀 3 Diferencias Principales: cPanel vs Linux Estándar

### 1️⃣ Ubicación y Usuario
```bash
# Linux Estándar
/var/www/dilo-records/
Usuario: www-data

# cPanel
~/public_html/
Usuario: tu_usuario_cpanel
```

### 2️⃣ Cache, Session, Queue
```bash
# Linux Estándar (usa Redis)
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis + Supervisor

# cPanel (sin Redis)
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
# Queue se procesa via Cron job
```

### 3️⃣ SSL Certificate
```bash
# Linux Estándar
Let's Encrypt configurado manualmente

# cPanel
AutoSSL (automático y renovación cada 30 días)
```

---

## 📋 PASO A PASO - DEPLOYMENT EN cPANEL

### PASO 1: Leer la Guía Completa ⭐ IMPORTANTE
```bash
# Lee CPANEL_DEPLOYMENT.md para entender:
# - Cómo instalar PHP 8.3 en cPanel
# - Instalar extensiones necesarias
# - Configurar base de datos
# - Configurar SSL
# - Configurar Cron jobs
# - Troubleshooting
```

### PASO 2: Acceder a cPanel
```
URL: https://tu-servidor.com:2083
Usuario: tu_usuario_cpanel
Contraseña: tu_contraseña
```

### PASO 3: Instalar PHP 8.3 y Extensiones
**En cPanel UI:**
```
Home → Software → MultiPHP Manager
- Seleccionar tu dominio
- Elegir PHP 8.3
- Aplicar cambios

Home → Software → Select PHP Version
- Click en tu PHP 8.3
- Marcar: curl, gd, mbstring, mysql, xml, zip, bcmath
- Guardar
```

### PASO 4: Descargar Código
**Opción A: Via Git (Recomendado)**
```bash
cd ~/public_html
rm -rf ./* ./.*  # Limpiar si hay contenido
git clone https://github.com/tu-usuario/dilo-records.git .
git checkout main
```

**Opción B: Via FTP**
```
Subir ZIP del proyecto → Extract → Mover archivos
```

### PASO 5: Crear Archivo .env.production
```bash
cd ~/public_html

# Copiar template
cp .env.cpanel.example .env.production

# Editar con tus valores (nano o editor cPanel)
nano .env.production

# Cambiar estas líneas:
APP_URL=https://tu-dominio.com
DB_DATABASE=usuario_dilorecords    # Ver en cPanel Databases
DB_USERNAME=usuario_dilo           # Ver en cPanel Databases
DB_PASSWORD=tu_contraseña_fuerte
IMAGEKIT_PUBLIC_KEY=tu_clave_publica
IMAGEKIT_PRIVATE_KEY=tu_clave_privada
IMAGEKIT_URL_ENDPOINT=tu_url_endpoint
MAIL_HOST=tu_servidor_correo
MAIL_USERNAME=tu_email@tu-dominio.com
MAIL_PASSWORD=tu_contraseña_correo
```

### PASO 6: Crear Base de Datos
**En cPanel UI:**
```
Home → Databases → MySQL Databases

Crear BD:
- Database name: dilorecords_prod
- Click "Create Database"

Crear usuario:
- Username: dilo_prod_user
- Password: Generar (guardar contraseña)
- Click "Create User"

Dar permisos:
- Seleccionar usuario
- All Privileges ✅
- Click "Make Changes"
```

### PASO 7: Instalar Dependencias
```bash
cd ~/public_html

# Instalar Composer localmente
curl -sS https://getcomposer.org/installer | php

# Instalar PHP dependencias
php composer.phar install --no-dev --optimize-autoloader

# Generar APP_KEY
php artisan key:generate --env=production

# Ejecutar migraciones
php artisan migrate --env=production --force
```

### PASO 8: Instalar Node.js y Compilar Assets
**Opción A: Via cPanel UI (Recomendado)**
```
Home → Software → NodeJS Selector
- Click en tu dominio
- Seleccionar Node.js 18+ o 20+
- Click "Create"
- Copiar el comando export que aparece
```

**Opción B: Via Terminal**
```bash
cd ~/public_html

# Instalar npm dependencias
npm ci --omit=dev

# Compilar assets (Vite)
npm run build
```

### PASO 9: Configurar SSL (AutoSSL)
**En cPanel UI:**
```
Home → Domains → AutoSSL
- Seleccionar dominio
- Click "Reissue"
- Esperar confirmación

Verificar: https://tu-dominio.com (debe haber candado)
```

### PASO 10: Ejecutar Deployment Automático
```bash
cd ~/public_html

# Hacer script ejecutable
chmod +x deploy-cpanel.sh

# Ejecutar deployment
./deploy-cpanel.sh

# Esperar a que termine (10-15 minutos)
# Ver resultado final
```

### PASO 11: Configurar Cron Jobs
**En cPanel UI:**
```
Home → Advanced → Cron Jobs

Agregar dos cron jobs:

1. Queue Worker (procesar tareas en background):
   Minute: *
   Hour: *
   Day: *
   Month: *
   Weekday: *
   Command: cd ~/public_html && php artisan queue:work --once --max-time=60 >> storage/logs/cron.log 2>&1
   Click "Add Cron Job"

2. Scheduled Tasks (tareas programadas):
   Minute: *
   Hour: *
   Day: *
   Month: *
   Weekday: *
   Command: cd ~/public_html && php artisan schedule:run >> storage/logs/schedule.log 2>&1
   Click "Add Cron Job"
```

### PASO 12: Verificar Instalación
```bash
cd ~/public_html

# Ver logs
tail -f storage/logs/laravel.log

# Test en navegador
https://tu-dominio.com/                 # Debe cargar
https://tu-dominio.com/admin            # Panel de admin
curl https://tu-dominio.com/up          # Health check (debe ser OK)
```

---

## 🔐 Configuración de Seguridad Importante

```bash
cd ~/public_html

# 1. Proteger .env
chmod 600 .env.production

# 2. Permisos de carpetas
chmod -R 755 storage bootstrap public

# 3. Verificar APP_DEBUG
grep "APP_DEBUG" .env.production
# Debe mostrar: APP_DEBUG=false

# 4. Verificar SESSION y CACHE
grep "SESSION_DRIVER\|CACHE_STORE" .env.production
# Debe mostrar: 
#   SESSION_DRIVER=file
#   CACHE_STORE=file
```

---

## 📊 Archivos de Referencia Siempre Disponibles

```
📁 Documentación cPanel:
├── CPANEL_DEPLOYMENT.md        ← Guía COMPLETA (LEE PRIMERO)
├── CPANEL_RESUMEN.md           ← Resumen de cambios
└── .env.cpanel.example         ← Variables optimizadas

📁 Deployment Automático:
├── deploy-cpanel.sh            ← Script para cPanel
└── deploy.sh                   ← Script para Linux estándar

📁 Documentación General:
├── DEPLOYMENT.md               ← Guía Linux estándar
├── DEPLOYMENT_PLAN.md          ← Plan de despliegue
├── PRODUCTION_CHECKLIST.md     ← Checklist 100+ items
└── PROJECT_REVIEW.md           ← Revisión del proyecto
```

---

## 🚨 Problemas Comunes y Soluciones

### ❌ Error: "Class not found"
```bash
cd ~/public_html
php artisan cache:clear --env=production
php artisan config:clear --env=production
php artisan autoload:dump
```

### ❌ Error: "Permission denied" en storage/
```bash
chmod -R 755 ~/public_html/storage
chmod -R 755 ~/public_html/bootstrap
```

### ❌ Error: "Database connection refused"
```bash
# Verificar en .env.production
DB_HOST=localhost  # SIEMPRE localhost en cPanel
DB_DATABASE=usuario_nombredb  # Nombre exacto de cPanel
DB_USERNAME=usuario_usuario   # Usuario exacto de cPanel
DB_PASSWORD=tu_contraseña     # Contraseña correcta

# Test de conexión
mysql -u usuario_usuario -p < /dev/null
```

### ❌ Error: "CSS/JS no cargan"
```bash
cd ~/public_html
npm run build
php artisan view:clear --env=production
```

### ❌ Error: "Emails no se envían"
```bash
# Verificar credenciales en .env.production
grep "MAIL_" .env.production

# Test en terminal
php artisan tinker
> Mail::send(...)
```

---

## ✅ CHECKLIST FINAL

```
Antes de desplegar:
- [ ] Leer CPANEL_DEPLOYMENT.md
- [ ] Instalar PHP 8.3 en cPanel
- [ ] Crear base de datos en cPanel
- [ ] Descargar código
- [ ] Crear .env.production
- [ ] Instalar Composer
- [ ] Instalar Node.js
- [ ] Ejecutar deploy-cpanel.sh

Después de desplegar:
- [ ] Verificar https://tu-dominio.com
- [ ] Ver admin en https://tu-dominio.com/admin
- [ ] Verificar health check: https://tu-dominio.com/up
- [ ] Revisar logs: tail -f storage/logs/laravel.log
- [ ] Configurar Cron jobs
- [ ] Configurar SSL (AutoSSL)
- [ ] Hacer backup de BD
- [ ] Verificar emails funcionan
```

---

## 📞 Contacto y Soporte

**Para problemas o dudas:**
1. Revisar `CPANEL_DEPLOYMENT.md` sección "Troubleshooting"
2. Ver logs: `tail -f ~/public_html/storage/logs/laravel.log`
3. Revisar checklist en `PRODUCTION_CHECKLIST.md`
4. Contactar hosting provider

---

## 🎉 ¡Listo para Desplegar!

**Próximo paso:** Lee `CPANEL_DEPLOYMENT.md` y sigue los 11 pasos

¿Necesitas ayuda con algún paso específico? 🚀

