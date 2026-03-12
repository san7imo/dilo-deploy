# Hito 1 - Blindaje del dominio actual de master royalties (baseline)

## Objetivo del hito
Documentar y verificar el comportamiento actual del flujo Symphonic antes de introducir cambios estructurales (modelo canonico, Sonosuite, conversion monetaria centralizada, etc.).

Este documento describe el estado real actual del repositorio en el dominio de master royalties.

## Inventario tecnico actual (master royalties)

### Tablas principales
- `royalty_statements`
- `royalty_statement_lines`
- `track_split_agreements`
- `track_split_participants`
- `royalty_allocations`

### Tablas de soporte de dedupe/versionado
- `royalty_statements`:
  - `UNIQUE(provider, file_hash)` (dedupe tecnico de archivo)
  - `statement_key + version` (versionado logico)
  - `current_statement_key` unico para forzar un solo current por statement key
  - flags: `is_reference_only`, `duplicate_of_statement_id`
- `royalty_statement_lines`:
  - `UNIQUE(royalty_statement_id, line_hash)` (idempotencia por linea en reproceso)

### Modelos
- `App\Models\RoyaltyStatement`
- `App\Models\RoyaltyStatementLine`
- `App\Models\TrackSplitAgreement`
- `App\Models\TrackSplitParticipant`
- `App\Models\RoyaltyAllocation`

### Requests / Controladores involucrados
- Upload y gestion de statements:
  - `App\Http\Controllers\Web\Admin\RoyaltyStatementController`
  - `App\Http\Requests\StoreRoyaltyStatementRequest`
  - `App\Http\Requests\UpdateRoyaltyStatementLineMatchRequest`
- Dashboard admin royalties:
  - `App\Http\Controllers\Web\Admin\RoyaltyDashboardController`
- Dashboard artista royalties:
  - `App\Http\Controllers\Web\Artist\TrackController`

### Job y servicios de negocio
- Parsing/proceso de archivo:
  - `App\Jobs\ProcessRoyaltyStatementJob`
- Calculo de allocations:
  - `App\Services\RoyaltyAllocationService`

### Vistas relevantes
- Admin:
  - `resources/js/Pages/Admin/Royalties/Statements/Create.vue`
  - `resources/js/Pages/Admin/Royalties/Statements/Index.vue`
  - `resources/js/Pages/Admin/Royalties/Statements/Show.vue`
  - `resources/js/Pages/Admin/Royalties/Dashboard.vue`
- Artista:
  - `resources/js/Pages/Artist/Tracks/Index.vue`
  - `resources/js/Pages/Artist/Tracks/Royalties/Show.vue`
  - `resources/js/Pages/Artist/Tracks/Royalties/Detail.vue`

## Flujo actual exacto (Symphonic)

1. Admin sube CSV desde `admin/royalties/statements/create`.
2. `StoreRoyaltyStatementRequest` valida:
   - `provider` solo admite `symphonic`.
   - `file` CSV/TXT.
3. `RoyaltyStatementController@store`:
   - normaliza contenido (BOM + saltos de linea),
   - calcula `file_hash`,
   - evita duplicado tecnico por `provider + file_hash`,
   - guarda archivo en disco `royalties_private`,
   - crea `royalty_statement` en estado `uploaded`.
4. Admin dispara proceso (`POST /admin/royalties/statements/{id}/process`).
5. `ProcessRoyaltyStatementJob`:
   - valida header con `Royalty ($US)` (si no existe, falla),
   - parsea CSV por filas,
   - extrae campos actuales: ISRC, UPC, titulo, DSP, territorio, activity period, units, net USD,
   - hace matching de track por ISRC normalizado,
   - genera `line_hash`,
   - hace `upsert` en `royalty_statement_lines`,
   - recalcula totales de statement,
   - define `statement_key`, `version`, `is_current`,
   - detecta subset y marca `is_reference_only` si aplica.
6. Al finalizar, si statement no es `reference_only`, ejecuta `RoyaltyAllocationService::rebuildForStatement`.
7. `RoyaltyAllocationService`:
   - borra allocations previos del statement,
   - toma lineas matched (`track_id != null`),
   - resuelve split master vigente por fecha (`activity_month_date` fallback reporting),
   - crea allocations por participante:
     - `allocated_amount_usd = gross * (percentage / 100)`,
     - conserva negativos,
     - estado inicial `accrued`.
8. Dashboard admin y vistas artista consultan principalmente `royalty_statement_lines` y `royalty_allocations`.

## Comportamiento validado en este hito

Se agrega un script reproducible para verificar el flujo actual de forma automatizada en entorno real:

- Comando: `php artisan royalties:verify-symphonic-flow`
- Opcion: `--keep` para conservar datos de verificacion.

### Que valida el comando
- Estado final del statement en `processed`.
- Creacion de lineas (`matched` y `unmatched`).
- Totales de statement (`units`, `total_net_usd`).
- Generacion de allocations para linea matched.
- Coherencia: suma de allocations == suma gross de lineas matched.
- Generacion de `statement_key` y estado `is_current`.

El comando crea datos temporales y, por defecto, limpia todo al terminar.

## Limites actuales detectados (sin resolver en Hito 1)

- Importador acoplado a Symphonic (no modelo canonico multi-fuente aun).
- `provider` soportado solo `symphonic`.
- No existe servicio central de normalizacion monetaria multi-moneda.
- El modelo de linea master actual no incluye aun todas las columnas canonicas esperadas en AGENTS para multi-distribuidor.

## Salida esperada del hito

- Inventario tecnico documentado.
- Flujo actual Symphonic descrito y trazable.
- Verificacion reproducible disponible por comando Artisan.

