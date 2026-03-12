# Hito 7 - Dominio base de composición

## Objetivo del hito
Completar el dominio base de composición con catálogo, vínculo auditable grabación↔composición y registros editoriales, manteniendo compatibilidad con lo ya existente.

## Cambios implementados

### 1) Nueva relación recording ↔ composition
Se agrega tabla `recording_compositions` como pivote canónico:
- `track_id`
- `composition_id`
- `relationship_type`
- `source`
- `notes`

Incluye:
- índices y `UNIQUE(track_id, composition_id)`,
- backfill automático desde `composition_track` para no perder datos históricos.

### 2) Registros editoriales de composición
Se agrega tabla `composition_registrations`:
- `composition_id`
- `registration_type`
- `society_name`
- `registration_number`
- `territory_code`
- `status`
- `metadata`
- `created_by`

Con esto, la composición puede existir sin ISWC y también mantener registros editoriales adicionales.

### 3) Modelos de dominio
Se agregan:
- `RecordingComposition`
- `CompositionRegistration`

Se actualiza:
- `Composition`:
  - `tracks()` ahora usa `recording_compositions`
  - `recordingCompositions()`
  - `registrations()`
- `Track`:
  - `compositions()` ahora usa `recording_compositions`

### 4) Servicio de catálogo de composición
Se agrega `App\Services\Compositions\CompositionCatalogService` para:
- crear composición,
- editar composición,
- vincular/desvincular tracks (recordings),
- sincronizar registro ISWC en `composition_registrations` de forma automática.

### 5) Controlador admin de composición
`CompositionController` ahora usa el servicio de catálogo para creación/edición y carga `registrations` en edición.
También incluye `registrations_count` en el listado.

### 6) Compatibilidad en allocations
`RoyaltyAllocationService` ahora soporta ambos pivotes:
- prioriza `recording_compositions`,
- hace fallback a `composition_track` si el nuevo pivote no existe.

## Criterios de aceptación cubiertos
- Una grabación puede vincularse a una o varias composiciones.
- Una composición puede existir sin ISWC.
- La relación recording ↔ composition queda separada en una entidad propia (`recording_compositions`) y trazable.

