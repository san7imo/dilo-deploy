# Hito 10 - Dashboards y reportes separados (Master vs Composition)

## Objetivo

Mostrar en el dashboard de regalías los dos dominios sin mezclar derechos:

- Master Royalties
- Composition Royalties

y exponer reportes por participante y desgloses operativos por fuente, período y territorio.

## Cambios implementados

### 1) Dashboard principal de regalías separado por dominio

Se mantiene la vista con dos paneles principales y accesos directos a statements:

- `admin.royalties.statements.index` (master)
- `admin.royalties.composition-statements.index` (composition)

Archivo:

- `resources/js/Pages/Admin/Royalties/Dashboard.vue`

### 2) Reporte por participante (accrued/payable/paid)

Se agregaron tablas para cada dominio:

- Participantes Master
- Participantes Composition

Cada tabla muestra:

- accrued
- payable
- paid
- total

Datos desde:

- `master_participants`
- `composition_participants`

### 3) Desgloses por fuente/período/territorio

Se agregaron bloques para cada dominio:

- por fuente
- por período
- por territorio

Datos desde:

- `master_breakdown.source|period|territory`
- `composition_breakdown.source|period|territory`

### 4) Backend del dashboard

El backend ya entrega el payload separado para ambos dominios y el frontend ahora lo consume completo.

Archivo:

- `app/Http/Controllers/Web/Admin/RoyaltyDashboardController.php`

## Validaciones ejecutadas

1. `npm run build` OK
2. `php -l app/Http/Controllers/Web/Admin/RoyaltyDashboardController.php` OK
3. `php artisan route:list --name=admin.royalties.dashboard` OK

## Resultado

Queda cerrado el objetivo del hito para visualización/reportes:

- separación explícita de derechos en dashboard,
- reportes por participante en ambos dominios,
- desgloses operativos separados y auditables.
