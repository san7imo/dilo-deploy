# Hito 5 - Matching y dedupe robustos de master

## Objetivo del hito
Centralizar el matching y la deduplicacion del dominio master, con estados explicitos por linea y reporte de revision manual.

## Cambios implementados

### 1) Estado explicito de match por linea
Se agrega migracion para `royalty_statement_lines`:
- `match_status` (`matched|unmatched|ambiguous|duplicate|reference_only`)
- `match_meta` (JSON de trazabilidad)

Backfill inicial:
- `reference_only` si el statement ya estaba marcado como referencia
- `matched` si tenia `track_id`
- `unmatched` en el resto

### 2) Servicio central de matching
Se agrega `App\Services\Royalties\MasterRoyaltyLineMatcher`:
- Prioridad de match:
  1. `ISRC` exacto
  2. `UPC + titulo` exacto (solo si ambos existen)
- Si hay multiples candidatos: `ambiguous`
- Si no hay identificador confiable: `unmatched`
- Regla explicita: no auto-match solo por titulo.

### 3) Servicio central de dedupe
Se agrega `App\Services\Royalties\MasterRoyaltyDedupeService`:
- hash tecnico de archivo (`buildFileHash`)
- `statement_key` normalizado
- `line_hash` canonico por linea
- deteccion financiera de subset/reference-only

Regla importante:
- En Symphonic, el `source_line_id` (row number) no entra al `line_hash`.
- En fuentes con line id estable (ej. Sonosuite), si entra.

### 4) Job de procesamiento integrado a servicios
`ProcessRoyaltyStatementJob` ahora:
- usa matcher central por linea,
- usa dedupe service para `line_hash` y `statement_key`,
- persiste `match_status` + `match_meta`,
- detecta lineas duplicadas dentro del mismo archivo y conserva una sola ocurrencia para calculo (estado `duplicate`),
- marca lineas como `reference_only` cuando el statement queda en ese modo.

### 5) Reporte manual en UI de statements
`RoyaltyStatementController@show` y `Show.vue` ahora soportan filtros:
- `all`
- `review` (unmatched + ambiguous + duplicate)
- `matched`
- `unmatched`
- `ambiguous`
- `duplicate`
- `reference_only`

Tambien se muestra badge de estado por linea y conteos por estado.

### 6) Ajustes de rematch manual
Al guardar match manual:
- actualiza `track_id`
- actualiza `match_status` (`matched` o `unmatched`)
- persiste trazabilidad en `match_meta` (`manual_override`, usuario, fecha)
- bloquea rematch si el statement es `reference_only`

### 7) Pruebas agregadas
- `MasterRoyaltyDedupeServiceTest`
- `MasterRoyaltyLineMatcherTest`

Cobertura incluida:
- hash Symphonic sin depender de row number
- hash Sonosuite con line id externo
- normalize de statement key
- match por ISRC
- ambiguous por multiples candidatos
- fallback seguro UPC+titulo
- bloqueo de auto-match por titulo solo

