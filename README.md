# Dilo Records – Plataforma de Gestion Musical

Plataforma web para la gestion integral de artistas, lanzamientos, eventos y finanzas internas de Dilo Records. Incluye un sitio publico para discovery, un panel administrativo y un portal para artistas y road managers.

## Stack Tecnologico

- Backend: Laravel 12, PHP 8.2
- Frontend: Inertia.js + Vue 3
- UI: Tailwind CSS
- Auth: Jetstream + Sanctum
- Roles y permisos: Spatie Laravel Permission
- Archivos/imagenes: ImageKit
- Build: Vite
- DB: MySQL

## Modulos Principales

### Sitio Publico
- Home con banners, lanzamientos, artistas y eventos.
- Artistas: listado y detalle publico.
- Eventos: listado y detalle publico.
- Lanzamientos y tracks: listado y detalle publico.
- Generos publicos.
- Formulario de contacto (envio a diana@dilorecords.com).
- Sitemaps publicos (artistas, releases, eventos).

### Panel Administrativo (Admin)
- Dashboard general.
- Artistas: CRUD, imagenes (ImageKit) y perfiles publicos.
- Lanzamientos: CRUD y plataformas.
- Tracks: CRUD y previsualizaciones.
- Generos: CRUD.
- Eventos: CRUD, poster, ubicacion, estado.
- Finanzas de eventos: pagos, gastos, gastos personales, estados y resumen.
- Equipo de trabajo: road managers y gestores de contenido.

### Portal de Artista
- Dashboard de artista.
- Perfil publico (bio, redes, imagenes).
- Mis eventos.
- Finanzas del artista.
- Mis lanzamientos y tracks.

## Roles y Permisos

- admin
  - Acceso total a todos los modulos y finanzas.
- contentmanager (gestor de contenido)
  - Gestiona artistas, lanzamientos, tracks, generos y eventos.
  - Acceso al modulo de equipo de trabajo.
  - Sin acceso a finanzas.
- roadmanager
  - Gestiona eventos asignados y puede crear eventos.
  - Acceso limitado a finanzas relacionadas a sus pagos.
- artist
  - Accede a su portal, eventos y finanzas personales.

## Modelo de Datos (resumen)

- artists: perfiles publicos, bio, redes y recursos de imagen.
- releases / tracks: discografia y reproducciones.
- events: detalles, posters, estado y finanzas.
- event_payments / event_expenses / event_personal_expenses: finanzas por evento.
- genres: catalogo musical.
- users + roles: administracion de accesos.

Campos publicos recientes en eventos:
- whatsapp_event: numero o link de WhatsApp del evento.
- page_tickets: URL de boleteria.

## Contacto

- Ruta publica: /contacto
- Envio de correos: SMTP configurado en .env
- Destino: diana@dilorecords.com

## Estructura (alto nivel)

- app/Http/Controllers/Web/Admin: panel admin.
- app/Http/Controllers/Web/Public: sitio publico.
- app/Http/Controllers/Web/Artist: portal del artista.
- resources/js/Pages: vistas Inertia.
- resources/js/Components: componentes reutilizables.
- app/Services: ImageKit y servicios de dominio.

## Configuracion Local

1) Instalar dependencias:
```
composer install
npm install
```

2) Copiar .env y configurar:
```
cp .env.example .env
php artisan key:generate
```

3) Migrar y ejecutar:
```
php artisan migrate
php artisan serve
npm run dev
```

## Configuracion Produccion (recomendado)

Ajustes clave en .env:
- APP_ENV=production
- APP_DEBUG=false
- APP_URL=https://app.dilorecords.com
- SESSION_DOMAIN=app.dilorecords.com
- SESSION_SECURE_COOKIE=true
- SESSION_COOKIE=dilorecords_app_session
- SANCTUM_STATEFUL_DOMAINS=app.dilorecords.com

Luego limpiar cache:
```
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

## Scripts utiles

- Build assets:
```
npm run build
```

- Dev completo (backend, queue, logs, vite):
```
composer dev
```

- Tests:
```
composer test
```

## Roadmap sugerido

- Consolidar permisos por rol con politicas y/o permisos granulares.
- Mejorar logs de auditoria (quien creo/edito).
- Panel de analytics para artistas y releases.
- Integracion de pagos externos para eventos.

---

Si necesitas agregar nuevos modulos o documentar flujos especificos, indica el alcance y lo incorporo en el README.
