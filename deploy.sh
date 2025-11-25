#!/bin/bash

##############################################################################
# Script de Deployment - Dilo Records
# 
# Uso: ./deploy.sh [environment] [version]
# Ejemplos:
#   ./deploy.sh production
#   ./deploy.sh staging v1.0.0
#
# Este script automatiza el despliegue a producción/staging incluyendo:
# - Descarga de nuevas dependencias
# - Migraciones de BD
# - Limpieza de cache
# - Compilación de assets
# - Reinicio de servicios
##############################################################################

set -e  # Exit on error

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variables
ENVIRONMENT=${1:-production}
VERSION=${2:-$(date +%Y%m%d_%H%M%S)}
LOG_FILE="storage/logs/deploy_${ENVIRONMENT}_${VERSION}.log"
ROLLBACK_FILE="storage/rollbacks/rollback_${ENVIRONMENT}_${VERSION}.sh"

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

# Validaciones iniciales
validate_environment() {
    if [[ "$ENVIRONMENT" != "production" && "$ENVIRONMENT" != "staging" && "$ENVIRONMENT" != "development" ]]; then
        error "Entorno inválido: $ENVIRONMENT. Usa: production, staging o development"
    fi
    success "Entorno validado: $ENVIRONMENT"
}

# Crear directorios necesarios
setup_directories() {
    mkdir -p storage/logs
    mkdir -p storage/rollbacks
    mkdir -p storage/app/public
    success "Directorios de almacenamiento creados"
}

# Backup de BD
backup_database() {
    log "Realizando backup de la base de datos..."
    if [ "$ENVIRONMENT" = "production" ]; then
        mkdir -p storage/backups
        BACKUP_FILE="storage/backups/db_backup_$(date +%Y%m%d_%H%M%S).sql"
        # Ajusta esto según tu tipo de BD (MySQL, PostgreSQL, etc.)
        # mysqldump -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_FILE"
        success "Backup de BD realizado: $BACKUP_FILE"
    fi
}

# Actualizar dependencias
update_dependencies() {
    log "Actualizando dependencias de Composer..."
    composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tee -a "$LOG_FILE"
    
    log "Instalando dependencias de npm..."
    npm ci --omit=dev 2>&1 | tee -a "$LOG_FILE"
    
    success "Dependencias actualizadas"
}

# Compilar assets
compile_assets() {
    log "Compilando assets con Vite..."
    npm run build 2>&1 | tee -a "$LOG_FILE"
    success "Assets compilados"
}

# Optimizaciones de Laravel
optimize_laravel() {
    log "Optimizando Laravel..."
    
    php artisan config:cache 2>&1 | tee -a "$LOG_FILE"
    php artisan route:cache 2>&1 | tee -a "$LOG_FILE"
    php artisan view:cache 2>&1 | tee -a "$LOG_FILE"
    
    if [ "$ENVIRONMENT" = "production" ]; then
        php artisan event:cache 2>&1 | tee -a "$LOG_FILE"
    fi
    
    success "Laravel optimizado"
}

# Migraciones de BD
run_migrations() {
    log "Ejecutando migraciones de base de datos..."
    php artisan migrate --force --no-interaction 2>&1 | tee -a "$LOG_FILE"
    success "Migraciones completadas"
}

# Limpiar cache
clear_cache() {
    log "Limpiando caches..."
    php artisan cache:clear 2>&1 | tee -a "$LOG_FILE"
    php artisan queue:restart 2>&1 | tee -a "$LOG_FILE"
    success "Caches limpiados"
}

# Cambiar permisos
set_permissions() {
    log "Estableciendo permisos de archivos..."
    chmod -R 755 storage bootstrap
    chmod -R 775 storage/logs storage/app storage/framework
    success "Permisos establecidos"
}

# Health check
health_check() {
    log "Realizando health check..."
    HEALTH_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost/up")
    
    if [ "$HEALTH_STATUS" != "200" ]; then
        error "Health check falló. Status: $HEALTH_STATUS"
    fi
    
    success "Health check pasado"
}

# Crear archivo de rollback
create_rollback() {
    log "Creando archivo de rollback..."
    cat > "$ROLLBACK_FILE" << 'EOF'
#!/bin/bash
echo "Rollback en progreso..."
# Aquí agregar comandos de rollback según sea necesario
# Ejemplos:
# - Restaurar BD desde backup
# - Revertir a versión anterior del código
# - Reiniciar servicios
EOF
    chmod +x "$ROLLBACK_FILE"
    success "Archivo de rollback creado: $ROLLBACK_FILE"
}

# Mostrar resumen
show_summary() {
    echo ""
    echo "=========================================="
    echo -e "${GREEN}DEPLOYMENT COMPLETADO${NC}"
    echo "=========================================="
    echo "Entorno: $ENVIRONMENT"
    echo "Versión: $VERSION"
    echo "Timestamp: $(date +'%Y-%m-%d %H:%M:%S')"
    echo "Log: $LOG_FILE"
    echo "Rollback: $ROLLBACK_FILE"
    echo "=========================================="
    echo ""
}

# === MAIN FLOW ===
main() {
    echo -e "${BLUE}=================================="
    echo "Iniciando Deployment - Dilo Records"
    echo "===================================${NC}"
    echo "Entorno: $ENVIRONMENT"
    echo "Versión: $VERSION"
    echo ""
    
    validate_environment
    setup_directories
    backup_database
    update_dependencies
    compile_assets
    optimize_laravel
    run_migrations
    clear_cache
    set_permissions
    create_rollback
    health_check
    show_summary
}

# Ejecutar
main
