# Hito 2 - Modelo canonico de master royalty lines

## Objetivo del hito
Introducir una capa de normalizacion reusable para master royalties y adaptar Symphonic para producir ese modelo canonico, sin romper el flujo actual.

## Cambios implementados

### 1) Servicio canonico reusable
Se agrega:
- `App\Services\Royalties\MasterRoyaltyLineCanonicalNormalizer`

Responsabilidades:
- Normalizar una fila Symphonic a un esquema canonico interno.
- Estandarizar fechas de statement/activity.
- Estandarizar ISRC/UPC.
- Estandarizar montos (`amount_original`, `amount_usd`) en formato decimal estable.
- Exponer `source_*` para trazabilidad de origen.

### 2) Job Symphonic adaptado al modelo canonico
`ProcessRoyaltyStatementJob` ahora:
- usa el servicio canonico por cada fila;
- toma desde el canonico los campos que persiste en `royalty_statement_lines`;
- conserva en `raw`:
  - metadata de fuente,
  - `canonical` completo,
  - `raw` original de la fila.

No se altero el comportamiento funcional de negocio:
- sigue validando USD por `Royalty ($US)`;
- sigue haciendo matching principal por ISRC;
- sigue calculando `line_hash` y upsert idempotente;
- sigue generando allocations al finalizar.

### 3) Script de verificacion actualizado
El comando:
- `php artisan royalties:verify-symphonic-flow`

Ahora tambien valida:
- presencia de `source + canonical + raw` dentro de `royalty_statement_lines.raw`;
- coherencia de monto canonico (`canonical.amount_usd`) vs monto persistido en la linea.

## Definicion del modelo canonico usado en este hito

Campos implementados por fila master:
- `source_name`
- `source_statement_id`
- `source_line_id`
- `statement_period_raw`
- `statement_period_start`
- `statement_period_end`
- `activity_start_date`
- `activity_end_date`
- `activity_month`
- `activity_period_text`
- `confirmation_date`
- `label_name`
- `release_title`
- `release_version`
- `release_artists`
- `upc`
- `catalogue`
- `track_title`
- `mix_version`
- `track_artists`
- `isrc`
- `dsp_name`
- `territory_code`
- `delivery_type`
- `content_type`
- `transaction_type`
- `sale_or_void`
- `units`
- `amount_original`
- `currency_original`
- `gross_amount_original`
- `other_costs_original`
- `channel_costs_original`
- `taxes_original`
- `fx_rate_to_usd`
- `amount_usd`
- `raw_payload_json`

## Compatibilidad
- Symphonic sigue operativo con el mismo endpoint/proceso.
- No se agregaron migraciones nuevas en este hito.
- No se habilito Sonosuite todavia (queda para Hito 3).

