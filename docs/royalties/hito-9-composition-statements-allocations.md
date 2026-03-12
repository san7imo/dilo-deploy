# Hito 9 - Composition royalty statements y allocations

## Objetivo del hito
Preparar el dominio de composición para importar statements y calcular allocations sin mezclarlo con master royalties.

## Cambios implementados

### 1) Nuevas tablas del dominio de composición
- `composition_royalty_statements`
- `composition_royalty_lines`
- `composition_allocations`

Con campos de auditoría (`raw`, `match_meta`, hashes, archivo original, estado de procesamiento, versión y `is_current` por statement key).

### 2) Flujo de importación manual estándar Dilo
Se agrega módulo admin con:
- listado de statements de composición,
- formulario de upload CSV,
- detalle de líneas con estado de match,
- descarga de CSV original,
- reproceso manual de statement,
- endpoint para descargar plantilla CSV estándar.

Rutas base:
- `admin/royalties/composition-statements/*`

### 3) Job de procesamiento de statements
`ProcessCompositionRoyaltyStatementJob` ahora:
- valida encabezados mínimos del CSV manual,
- exige moneda USD por línea,
- normaliza cada fila a modelo canónico de composición,
- hace dedupe in-file por `line_hash`,
- resuelve match de composición por:
  1. `composition_id`,
  2. `composition_iswc`,
  3. `composition_title`,
- persiste líneas y totales del statement,
- dispara recálculo de allocations de composición.

### 4) Motor de allocations de composición
Se agrega `CompositionRoyaltyAllocationService` con reglas:
- `performance`: 50% pool `writer` + 50% pool `publisher`.
- `mechanical`: 100% pool `mechanical_payee`.

El cálculo usa `SplitAllocationCalculator` para asegurar cierre por porcentaje y soporte de montos negativos.

### 5) Soporte de plantilla manual
La plantilla CSV descargable incluye columnas:
- `reporting_period`
- `activity_period`
- `activity_month`
- `line_type`
- `composition_id`
- `composition_iswc`
- `composition_title`
- `source_name`
- `territory_code`
- `units`
- `amount_usd`
- `currency`
- `source_line_id`
- `external_reference`

## Criterios de aceptación cubiertos
- Existe dominio separado para statements/lines/allocations de composición.
- Una línea `performance` reparte 50/50 writer-publisher.
- Una línea `mechanical` reparte sobre `mechanical_payee`.
- Queda lista la base para conectores futuros ASCAP/MLC sin tocar el dominio master.

