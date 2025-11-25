# 📦 Guía de Despliegue - Dilo Records

Documento completo para desplegar Dilo Records en producción.

## 📋 Requisitos del Servidor

### Software Requerido
- **PHP**: 8.2+ (recomendado 8.3)
- **Composer**: ^2.0
- **Node.js**: 18+ (con npm)
- **Base de Datos**: MySQL 8.0+ o PostgreSQL 13+
- **Redis**: (Opcional pero recomendado para cache y queue)
- **Nginx/Apache**: Para servir la aplicación

### Requisitos Mínimos de Servidor
- **CPU**: 2 cores mínimo
- **RAM**: 2GB mínimo (4GB recomendado)
- **Almacenamiento**: 20GB mínimo
- **Ancho de banda**: Según tráfico esperado

### Certificado SSL
- Certificado SSL/TLS válido (ej: Let's Encrypt)
- HTTPS obligatorio en producción

## 🚀 Proceso de Despliegue

### 1. Preparación del Servidor

```bash
# Actualizar paquetes del sistema
sudo apt update && sudo apt upgrade -y

# Instalar dependencias
sudo apt install -y curl wget git unzip

# Instalar PHP 8.3 con extensiones necesarias
sudo apt install -y php8.3 php8.3-fpm php8.3-mysql php8.3-redis php8.3-gd php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip

# Instalar Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# Instalar Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Instalar Nginx
sudo apt install -y nginx

# Instalar MySQL (opcional, si no lo tienes en otro servidor)
sudo apt install -y mysql-server
```

### 2. Clonar y Configurar Repositorio

```bash
cd /var/www
sudo git clone <tu-repositorio> dilo-records
cd dilo-records

# Establecer permisos
sudo chown -R www-data:www-data /var/www/dilo-records
sudo chmod -R 775 storage bootstrap

# Copiar .env
sudo cp .env.production.example .env.production
sudo nano .env.production  # Editar con tus valores reales

# Generar APP_KEY si no lo tienes
php artisan key:generate --env=production
```

### 3. Instalar Dependencias

```bash
# Instalar dependencias de PHP
composer install --no-dev --optimize-autoloader

# Instalar dependencias de Node
npm ci --omit=dev

# Compilar assets
npm run build
```

### 4. Configurar Base de Datos

```bash
# Si usas archivo .env.production
export $(cat .env.production | grep -v '#' | xargs)

# Crear base de datos (si necesario)
mysql -u root -p -e "CREATE DATABASE dilo_records_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER 'dilo_prod_user'@'localhost' IDENTIFIED BY 'password_fuerte';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON dilo_records_prod.* TO 'dilo_prod_user'@'localhost';"

# Ejecutar migraciones
php artisan migrate --env=production --force
```

### 5. Optimizaciones de Laravel

```bash
php artisan config:cache --env=production
php artisan route:cache --env=production
php artisan view:cache --env=production
```

### 6. Configurar Nginx

Crea `/etc/nginx/sites-available/dilo-records`:

```nginx
server {
    listen 80;
    server_name example.com www.example.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name example.com www.example.com;

    # SSL
    ssl_certificate /etc/letsencrypt/live/example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/example.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss;

    # Root
    root /var/www/dilo-records/public;
    index index.php index.html;

    # Logs
    access_log /var/log/nginx/dilo-records.access.log;
    error_log /var/log/nginx/dilo-records.error.log;

    # PHP
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Laravel routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Cache estático
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # Denegar acceso a archivos sensibles
    location ~ /\. {
        deny all;
    }

    location ~ /storage/ {
        deny all;
    }
}
```

Habilita el sitio:

```bash
sudo ln -s /etc/nginx/sites-available/dilo-records /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 7. Configurar SSL (Let's Encrypt)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot certonly --nginx -d example.com -d www.example.com
```

### 8. Configurar Queue Worker (Supervisor)

Instala Supervisor:

```bash
sudo apt install -y supervisor
```

Crea `/etc/supervisor/conf.d/dilo-records.conf`:

```ini
[program:dilo-records-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/dilo-records/artisan queue:work redis --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/supervisor/dilo-records-worker.log
```

Reinicia Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart dilo-records-worker:*
```

### 9. Configurar Cron Job

Añade a crontab:

```bash
sudo crontab -e

# Agregar:
* * * * * cd /var/www/dilo-records && php artisan schedule:run >> /dev/null 2>&1
```

### 10. Ejecutar Script de Deployment (Alternativa)

```bash
cd /var/www/dilo-records
chmod +x deploy.sh
./deploy.sh production
```

## 📊 Monitoreo y Mantenimiento

### Logs

```bash
# Logs de Laravel
tail -f storage/logs/laravel.log

# Logs de Nginx
tail -f /var/log/nginx/dilo-records.error.log

# Logs del Queue Worker
tail -f /var/log/supervisor/dilo-records-worker.log
```

### Health Check

```bash
curl https://example.com/up
```

Debería retornar `200 OK`.

### Backups

```bash
# Backup de BD manual
mysqldump -u dilo_prod_user -p dilo_records_prod > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup de archivos
tar -czf dilo-records_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/dilo-records/storage
```

## 🔒 Checklist de Seguridad

- [ ] APP_DEBUG=false en .env
- [ ] APP_KEY generado correctamente
- [ ] HTTPS/SSL configurado
- [ ] Base de datos con usuario dedicado y contraseña fuerte
- [ ] Permisos de archivos correctamente establecidos
- [ ] Logs rotativos configurados
- [ ] Rate limiting activado
- [ ] CORS correctamente configurado
- [ ] Backups automáticos configurados
- [ ] Monitoreo de errores (Sentry/similar)
- [ ] Firewall configurado
- [ ] Puertos innecesarios cerrados

## 🚨 Troubleshooting

### Permisos de archivos
```bash
sudo chown -R www-data:www-data /var/www/dilo-records
sudo chmod -R 755 storage bootstrap
sudo chmod -R 775 storage/logs storage/app storage/framework
```

### Limpiar cache en emergencia
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Resetear BD (⚠️ Datos perdidos)
```bash
php artisan migrate:reset --force
php artisan migrate --force
php artisan db:seed --force
```

## 📞 Soporte

Para issues específicos, revisa:
- Laravel Documentation: https://laravel.com/docs
- Nginx Documentation: https://nginx.org/en/docs/
- PHP-FPM Documentation: https://www.php.net/manual/en/install.fpm.php

---

**Última actualización**: 19 de noviembre de 2025
