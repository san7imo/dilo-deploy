# Hito 3 - Importador Sonosuite

## Objetivo del hito
Agregar soporte Sonosuite al dominio de master royalties reutilizando el modelo canonico interno y sin romper el flujo Symphonic existente.

## Cambios implementados

### 1) Soporte de proveedor Sonosuite en upload
- `StoreRoyaltyStatementRequest` ahora permite:
  - `provider: symphonic | sonosuite`
- `RoyaltyStatementController@create` expone ambos proveedores en el formulario.

### 2) Parsing por proveedor en el Job
`ProcessRoyaltyStatementJob` ahora:
- detecta encabezado segun proveedor;
- valida columnas requeridas por proveedor:
  - Symphonic: `royalty ($us)`;
  - Sonosuite: `id, currency, net_total, channel, track_title, isrc`.
- enruta la normalizacion por proveedor.

### 3) Adaptador Sonosuite -> modelo canonico
Se agrega en `MasterRoyaltyLineCanonicalNormalizer`:
- `normalizeSonosuiteRow(...)`

Mapeo principal:
- `source_line_id` <- `id`
- `statement_period_start/end` <- `start_date/end_date`
- `confirmation_date` <- `confirmation_report_date`
- `territory_code` <- `country`
- `dsp_name` <- `channel`
- `release_title` <- `release`
- `track_title` <- `track_title`
- `isrc` <- `isrc` (normalizado)
- `upc` <- `upc` (normalizado)
- `amount_original` <- `net_total`
- `currency_original` <- `currency`
- `gross/other/channel/taxes` <- campos Sonosuite equivalentes
- `fx_rate_to_usd` <- `currency_rate`
- `amount_usd`:
  - si `currency = USD`, usa `amount_original`;
  - si no, aplica conversion inferida con `amount_original / currency_rate`.

La estrategia aplicada queda auditada en:
- `raw_payload_json.sonosuite.conversion_strategy`

### 4) Persistencia de campos criticos Sonosuite
En `royalty_statement_lines.raw` se persiste:
- `source` (provider, source_line_id, importer_version),
- `canonical` completo,
- `raw` original,
- subobjeto `sonosuite` con columnas relevantes no mapeadas directo
  (ej: `user_email`, `unit_price`, *_client_currency).

### 5) Verificacion reproducible ampliada
Comando existente actualizado:
- `php artisan royalties:verify-symphonic-flow --provider=symphonic`
- `php artisan royalties:verify-symphonic-flow --provider=sonosuite`

Valida:
- proceso correcto de statement,
- matched/unmatched,
- allocations,
- presencia de payload canonico,
- `canonical.source_name` consistente con el proveedor.

## Compatibilidad
- Symphonic se mantiene operativo.
- Sonosuite se importa en el mismo modelo interno (`royalty_statement_lines` + `raw` canonico).
- No se introdujeron migraciones nuevas en este hito.

## Notas de alcance
- Este hito agrega parser + adaptador Sonosuite.
- La estandarizacion monetaria completa y estrategia global de conversion USD queda para Hito 4.

