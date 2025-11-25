#!/bin/bash

##############################################################################
# Script de Deployment Específico para cPanel - Dilo Records
# 
# Uso: ./deploy-cpanel.sh
#
# Este script está optimizado para deployments en cPanel y NO requiere acceso root
# Automatiza:
# - Descarga de nuevas dependencias
# - Compilación de assets
# - Migraciones de BD
# - Limpieza de cache
# - Configuración de permisos
##############################################################################

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Variables
PROJECT_ROOT="$HOME/public_html"
LOG_FILE="$PROJECT_ROOT/storage/logs/deploy_cpanel_$(date +%Y%m%d_%H%M%S).log"
BACKUP_DIR="$PROJECT_ROOT/storage/backups"

# Crear directorio de logs si no existe
mkdir -p "$PROJECT_ROOT/storage/logs"
mkdir -p "$BACKUP_DIR"

# Funciones
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

success() {
    echo -e "${GREEN}[✓]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[✗ ERROR]${NC} $1" | tee -a "$LOG_FILE"
    exit 1
}

warning() {
    echo -e "${YELLOW}[!]${NC} $1" | tee -a "$LOG_FILE"
}

# Iniciar deployment
log "================================"
log "🚀 DEPLOYMENT INICIADO EN cPANEL"
log "================================"
log "Fecha: $(date)"
log "Ruta: $PROJECT_ROOT"
log ""

# 1. Verificar que estamos en el directorio correcto
cd "$PROJECT_ROOT" || error "No se pudo acceder a $PROJECT_ROOT"
success "Directorio correcto: $PROJECT_ROOT"

# 2. Descargar código desde Git
log ""
log "📥 Descargando código..."
git fetch origin 2>&1 | tee -a "$LOG_FILE" || error "git fetch falló"
git pull origin main 2>&1 | tee -a "$LOG_FILE" || error "git pull falló"
success "Código descargado correctamente"

# 3. Hacer backup de .env si existe
if [ -f ".env.production" ]; then
    log ""
    log "💾 Haciendo backup de configuración..."
    cp .env.production "$BACKUP_DIR/.env.production.backup_$(date +%Y%m%d_%H%M%S)" || warning "Backup de .env falló"
    success "Backup de configuración realizado"
else
    warning "No existe .env.production. Asegúrate de crearlo manualmente"
fi

# 4. Instalar dependencias PHP con Composer
log ""
log "📦 Instalando dependencias PHP..."

# Verificar si composer.phar existe localmente
if [ -f "composer.phar" ]; then
    php composer.phar install --no-dev --optimize-autoloader 2>&1 | tee -a "$LOG_FILE" || error "Composer install falló"
    success "Dependencias PHP instaladas (composer.phar local)"
elif command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader 2>&1 | tee -a "$LOG_FILE" || error "Composer install falló"
    success "Dependencias PHP instaladas (composer global)"
else
    error "Composer no encontrado. Instala Composer localmente: curl -sS https://getcomposer.org/installer | php"
fi

# 5. Instalar dependencias Node.js
log ""
log "🎨 Compilando assets..."

if ! command -v npm &> /dev/null; then
    warning "⚠️ npm no encontrado. Salta compilación de assets"
    warning "Usa NodeJS Selector en cPanel o instala nvm"
else
    npm ci --omit=dev 2>&1 | tee -a "$LOG_FILE" || error "npm ci falló"
    npm run build 2>&1 | tee -a "$LOG_FILE" || error "npm run build falló"
    success "Assets compilados correctamente"
fi

# 6. Ejecutar migraciones
log ""
log "🗄️ Ejecutando migraciones de BD..."
php artisan migrate --env=production --force 2>&1 | tee -a "$LOG_FILE" || error "Migraciones fallaron"
success "Migraciones completadas"

# 7. Limpiar caches
log ""
log "🧹 Limpiando caches..."
php artisan cache:clear --env=production 2>&1 | tee -a "$LOG_FILE"
php artisan config:clear --env=production 2>&1 | tee -a "$LOG_FILE"
php artisan view:clear --env=production 2>&1 | tee -a "$LOG_FILE"
success "Caches limpiados"

# 8. Optimizar para producción
log ""
log "⚡ Optimizando para producción..."
php artisan config:cache --env=production 2>&1 | tee -a "$LOG_FILE" || warning "Config cache falló"
php artisan view:cache --env=production 2>&1 | tee -a "$LOG_FILE" || warning "View cache falló"
php artisan route:cache --env=production 2>&1 | tee -a "$LOG_FILE" || warning "Route cache falló"
success "Optimizaciones aplicadas"

# 9. Establecer permisos correctos
log ""
log "🔐 Configurando permisos..."
chmod -R 755 storage bootstrap public 2>&1 | tee -a "$LOG_FILE"
chmod -R 755 storage/logs storage/framework storage/app 2>&1 | tee -a "$LOG_FILE"
# Proteger .env
chmod 600 .env.production 2>&1 | tee -a "$LOG_FILE"
success "Permisos configurados"

# 10. Ejecutar seeders si existen (opcional)
log ""
log "🌱 Ejecutando seeders..."
read -p "¿Ejecutar seeders? (s/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Ss]$ ]]; then
    php artisan db:seed --env=production 2>&1 | tee -a "$LOG_FILE" || warning "Seeders fallaron (no es crítico)"
    success "Seeders ejecutados"
else
    log "Seeders saltados"
fi

# 11. Verificar salud de la aplicación
log ""
log "🏥 Verificando salud de la aplicación..."

# Obtener el dominio desde .env.production
APP_URL=$(grep "^APP_URL=" .env.production | cut -d'=' -f2)

if [ -z "$APP_URL" ]; then
    warning "⚠️ APP_URL no definida en .env.production"
else
    HEALTH_ENDPOINT="${APP_URL}/up"
    RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "$HEALTH_ENDPOINT" 2>/dev/null || echo "000")
    
    if [ "$RESPONSE" = "200" ]; then
        success "✓ Aplicación saludable (HTTP $RESPONSE)"
    else
        warning "⚠️ Health check retornó HTTP $RESPONSE (esperado 200)"
        warning "Verifica logs en: $PROJECT_ROOT/storage/logs/laravel.log"
    fi
fi

# 12. Mostrar información útil
log ""
log "================================"
log "✅ DEPLOYMENT COMPLETADO"
log "================================"
log ""
log "📊 Información útil:"
log "  - Logs de la aplicación: $PROJECT_ROOT/storage/logs/laravel.log"
log "  - Logs del deployment: $LOG_FILE"
log "  - Backups: $BACKUP_DIR"
log ""
log "🔍 Próximos pasos:"
log "  1. Verificar: https://tu-dominio.com/"
log "  2. Ver logs: tail -f $PROJECT_ROOT/storage/logs/laravel.log"
log "  3. Admin panel: https://tu-dominio.com/admin"
log ""
log "📞 Si hay errores:"
log "  - Revisar archivo de log: $LOG_FILE"
log "  - Verificar permisos de carpetas"
log "  - Verificar .env.production"
log ""
log "⏰ Deployment iniciado: $(date)" >> "$LOG_FILE"
log "⏰ Deployment completado: $(date)" >> "$LOG_FILE"

success "¡Deployment exitoso!"

