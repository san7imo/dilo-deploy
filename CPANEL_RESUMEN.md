# ✅ Cambios para Deployment en cPanel - Resumen

## 📋 Lo que cambió vs deployment Linux estándar

| Item | Linux Estándar | cPanel | Razón |
|------|---|---|---|
| **Ubicación código** | `/var/www/dilo-records/` | `~/public_html/` | Estructura de cPanel |
| **Usuario PHP** | `www-data` | Tu usuario cPanel | Permisos de cPanel |
| **Gestor Queue** | Supervisor + systemd | Cron + Database | Sin acceso root en cPanel |
| **Cache Driver** | Redis | File | cPanel no proporciona Redis |
| **Session Driver** | Redis | File | cPanel no proporciona Redis |
| **SSL Certificate** | Let's Encrypt manual | AutoSSL (automático) | Integración nativa cPanel |
| **Permisos** | `www-data:www-data` | Tu usuario cPanel | Estructura diferente |
| **Composer** | Global en sistema | Local en proyecto | Evita conflictos de versión |
| **Node.js** | NVM o global | NodeJS Selector cPanel | Interfaz gráfica cPanel |

---

## 🎯 3 Archivos Nuevos Creados

### 1. `CPANEL_DEPLOYMENT.md` 
**Guía completa (700+ líneas) específica para cPanel**

Incluye:
- ✅ Cómo instalar PHP 8.3 en cPanel
- ✅ Instalar extensiones requeridas
- ✅ Descargar código (Git o FTP)
- ✅ Instalar Composer localmente
- ✅ Instalar Node.js via NodeJS Selector
- ✅ Configurar .env para cPanel
- ✅ Crear BD en cPanel
- ✅ Ejecutar migraciones
- ✅ Configurar SSL/HTTPS
- ✅ Configurar Cron jobs (queue + scheduled tasks)
- ✅ Troubleshooting común en cPanel

### 2. `.env.cpanel.example`
**Variables de entorno optimizadas para cPanel**

Cambios principales vs `.env.production.example`:
```bash
# cPanel: Siempre localhost
DB_HOST=localhost

# cPanel: Formato usuario_nombredb
DB_DATABASE=usuario_dilorecords
DB_USERNAME=usuario_dilo

# cPanel: Usar FILE en lugar de REDIS
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=database  # No Supervisor en cPanel

# cPanel: Database para broadcast
BROADCAST_CONNECTION=database
```

### 3. `deploy-cpanel.sh`
**Script de deployment optimizado para cPanel**

Características:
- ✅ Actualiza código desde Git
- ✅ Backup automático de .env
- ✅ Instala Composer localmente
- ✅ Compila assets con npm
- ✅ Ejecuta migraciones
- ✅ Limpia y cachea configuración
- ✅ Configura permisos correctamente
- ✅ Verifica salud de la aplicación
- ✅ Sin requerimiento de acceso root
- ✅ Logging detallado

---

## 🚀 Cómo Usar en cPanel

### Opción A: Automatizada (Recomendado)

```bash
# 1. Desde SSH en cPanel:
cd ~/public_html

# 2. Hacer script ejecutable
chmod +x deploy-cpanel.sh

# 3. Ejecutar deployment
./deploy-cpanel.sh

# 4. Ver resultados
tail -f storage/logs/laravel.log
```

### Opción B: Manual paso a paso

Seguir la guía en `CPANEL_DEPLOYMENT.md`:
- Paso 1: Instalar PHP 8.3 via MultiPHP Manager
- Paso 2: Instalar extensiones necesarias
- Paso 3: Descargar código
- ... (11 pasos total)

---

## 📝 Cambios en Configuración Necesarios

### Base de Datos
```bash
# En .env.cpanel.example (o tu .env.production):

# ✅ En cPanel SIEMPRE es localhost
DB_HOST=localhost

# ✅ cPanel usa formato: usuario_nombredb
DB_DATABASE=usuario_dilorecords

# ✅ cPanel usa formato: usuario_dbuser
DB_USERNAME=usuario_dilo
```

### Cache y Sesiones
```bash
# ❌ NO usar Redis (no está en planes cPanel estándar)
# SESSION_DRIVER=redis       ← CAMBIAR A file
SESSION_DRIVER=file

# ❌ NO usar Redis para cache
# CACHE_STORE=redis          ← CAMBIAR A file
CACHE_STORE=file
```

### Queue Workers
```bash
# ❌ NO usar Supervisor (requiere acceso root)
# QUEUE_CONNECTION=redis + Supervisor  ← CAMBIAR

# ✅ Usar Database (procesamiento manual)
QUEUE_CONNECTION=database

# ✅ O procesar via Cron:
# En cPanel → Cron Jobs
# Agregar: * * * * * cd ~/public_html && php artisan queue:work --once --max-time=60
```

### SSL/HTTPS
```bash
# ❌ NO configurar Let's Encrypt manualmente
# ✅ USAR AutoSSL de cPanel (automático)
# Home → Domains → AutoSSL → Click "Reissue"
```

---

## ✅ Checklist Pre-Deployment en cPanel

```
Preparación
- [ ] Copiar .env.cpanel.example a .env.production
- [ ] Llenar variables reales (DB, mail, ImageKit, etc.)
- [ ] Generar APP_KEY: php artisan key:generate --env=production
- [ ] Verificar que APP_DEBUG=false

Instalación
- [ ] Descargar código via Git o FTP
- [ ] Instalar Composer localmente
- [ ] Ejecutar: php composer.phar install --no-dev
- [ ] Instalar Node.js via NodeJS Selector (o nvm)
- [ ] Ejecutar: npm ci && npm run build

Base de Datos
- [ ] Crear BD en cPanel (MySQL Databases)
- [ ] Crear usuario DB en cPanel
- [ ] Dar permisos All Privileges
- [ ] Ejecutar migraciones: php artisan migrate --env=production --force

Configuración
- [ ] Configurar SSL via AutoSSL
- [ ] Establecer permisos (755 storage/bootstrap)
- [ ] Crear directorios: mkdir -p storage/{logs,framework/cache}
- [ ] Proteger .env: chmod 600 .env.production

Cron Jobs (en cPanel → Cron Jobs)
- [ ] Queue worker: * * * * * cd ~/public_html && php artisan queue:work --once --max-time=60
- [ ] Scheduled tasks: * * * * * cd ~/public_html && php artisan schedule:run
- [ ] Backups (opcional): 0 2 * * * cd ~/public_html && php artisan backup:run

Verificación
- [ ] Prueba: https://tu-dominio.com
- [ ] Admin panel: https://tu-dominio.com/admin
- [ ] Ver logs: tail -f ~/public_html/storage/logs/laravel.log
- [ ] Health check: curl https://tu-dominio.com/up
```

---

## 📊 Comparación: Tipos de Hosting

### Opción 1: cPanel Estándar (Compartido)
```
Costo: $$
- PHP 8.3: ✅
- MySQL: ✅ (Compartido)
- Redis: ❌
- SSH Access: ✅
- Queue Workers: Cron + Database
- Performance: Bueno
- Escalabilidad: Limitada
```

### Opción 2: cPanel con Redis Add-on
```
Costo: $$$
- PHP 8.3: ✅
- MySQL: ✅ (Compartido)
- Redis: ✅ (Add-on)
- SSH Access: ✅
- Queue Workers: Cron o Redis
- Performance: Excelente
- Escalabilidad: Buena
```

### Opción 3: VPS/Servidor Dedicado (sin cPanel)
```
Costo: $$$ - $$$$
- PHP 8.3: ✅
- MySQL: ✅ (Dedicado)
- Redis: ✅
- SSH Access: ✅ (root)
- Queue Workers: Supervisor + systemd
- Performance: Excelente
- Escalabilidad: Excelente
- Complejidad: Mayor
```

---

## 🚨 Diferencias Críticas a Recordar

### ❌ Errores Comunes en cPanel

1. **Usar Redis sin tener el add-on**
   ```bash
   # ❌ MAL
   CACHE_STORE=redis
   SESSION_DRIVER=redis
   
   # ✅ BIEN
   CACHE_STORE=file
   SESSION_DRIVER=file
   ```

2. **Usar Supervisor (requiere root)**
   ```bash
   # ❌ MAL - No funciona en cPanel
   QUEUE_CONNECTION=redis  # + Supervisor
   
   # ✅ BIEN - Usar en cPanel
   QUEUE_CONNECTION=database  # + Cron
   ```

3. **Permisos incorrectos**
   ```bash
   # ❌ MAL - Muy inseguro
   chmod -R 777 storage
   
   # ✅ BIEN
   chmod -R 755 storage
   chmod 600 .env.production
   ```

4. **DB_HOST incorrecto**
   ```bash
   # ❌ MAL
   DB_HOST=mi.servidor.com
   
   # ✅ BIEN - En cPanel siempre localhost
   DB_HOST=localhost
   ```

5. **APP_DEBUG en producción**
   ```bash
   # ❌ MAL - NUNCA en producción
   APP_DEBUG=true
   
   # ✅ BIEN
   APP_DEBUG=false
   ```

---

## 🎯 Orden de Ejecución Recomendado

```
1. Leer CPANEL_DEPLOYMENT.md (guía completa)
2. Crear .env.production desde .env.cpanel.example
3. Descargar código en ~/public_html
4. Instalar Composer y Node.js
5. Ejecutar deploy-cpanel.sh automatizado
6. Verificar en https://tu-dominio.com
7. Configurar Cron jobs para queue y scheduled tasks
8. Monitorear logs en primeras 24 horas
```

---

## 📞 Recursos y Documentos

```
📁 Archivos Relacionados:
├── CPANEL_DEPLOYMENT.md          ← Guía detallada para cPanel
├── .env.cpanel.example           ← Variables de entorno optimizadas
├── deploy-cpanel.sh              ← Script automatizado
├── DEPLOYMENT.md                 ← Guía original (Linux estándar)
├── PRODUCTION_CHECKLIST.md       ← Checklist pre-deployment
└── PROJECT_REVIEW.md             ← Revisión del proyecto

💡 Próximos Pasos:
1. Leer: CPANEL_DEPLOYMENT.md
2. Copiar: .env.cpanel.example → .env.production
3. Ejecutar: ./deploy-cpanel.sh
4. Verificar: https://tu-dominio.com
```

---

## ✨ Resumen de Cambios

**Total de cambios para cPanel:**
- 📄 3 archivos nuevos
- 📝 ~2,000 líneas de documentación y código
- ⚙️ Configuración optimizada para cPanel
- 🔐 Seguridad adaptada a cPanel
- 🚀 Deploy script sin requerimiento root
- ✅ Guía completa paso a paso

**Tiempo estimado de deployment:**
- Automático: 10-15 minutos (con deploy-cpanel.sh)
- Manual: 30-45 minutos (siguiendo pasos en CPANEL_DEPLOYMENT.md)

---

**¡Listo para desplegar en cPanel!** 🎛️

¿Necesitas ayuda con algún paso específico del deployment?

