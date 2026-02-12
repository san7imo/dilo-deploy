# AGENTS.md — Dilo Records (Royalties Module)

## Contexto del proyecto
Plataforma Dilo Records (Laravel 12 + Inertia + Vue 3 + Tailwind + MySQL).
Se debe implementar un módulo de regalías (royalties) con importación de CSV (tipo Symphonic mensual),
visualización por canción para artistas, y cálculo de “mi parte” según split contractual.

Moneda: USD (SIEMPRE).

## Objetivo del módulo (MVP)
1) Admin puede subir archivos CSV de regalías.
2) El sistema procesa el CSV y guarda:
   - el statement (archivo/periodo) y
   - las líneas normalizadas (line-level).
3) El artista puede ver, por canción:
   - cards por período (ej: SEP-25, OCT-25),
   - Total Track (USD), Units, y “Mi parte (USD)” según split,
   - un “View details” con breakdown por DSP/territorio/activity period.
4) Mantener módulo coherente:
   - NO duplicar reportes ni líneas,
   - soportar correcciones/versiones del mismo periodo.

## Convenciones del repo
- Backend: Laravel 12, PHP 8.2
- Frontend: Inertia.js + Vue 3
- DB: MySQL
- Roles/Permisos: Spatie (admin, artist, etc.)
- UI en español, nombres de tablas/código en inglés (`royalty_*`).

## Principios obligatorios (no negociables)

### A) Trabajo por hitos y validación
- Implementar en pasos pequeños y verificables.
- NO mezclar múltiples hitos en un solo cambio grande.
- Al final de cada hito:
  - listar archivos modificados/creados,
  - explicar decisiones clave,
  - dar comandos para validar (migraciones/tests),
  - detenerse y esperar OK del usuario.

### B) Auditoría y trazabilidad
- Guardar el archivo subido (path en storage privado) y su metadata.
- Guardar `raw` JSON por línea para auditoría (campos originales + derivados).

### C) Deduplicación e idempotencia (OBLIGATORIO)
Se deben cumplir 3 niveles:

1) **Archivo duplicado exacto**
   - Calcular `file_hash` (SHA-256) normalizando saltos de línea y BOM si aplica.
   - Constraint: `UNIQUE(provider, file_hash)`.

2) **Statement lógico por periodo (evitar múltiples “SEP-25” vigentes)**
   - Calcular `statement_key = provider + label + reporting_period` (o equivalente).
   - Permitir versionado para correcciones:
     - `version` incremental (1..n)
     - `is_current` boolean para el vigente.
   - Regla: solo 1 `is_current = true` por `statement_key`.

3) **Líneas duplicadas por reintento**
   - Si NO hay `external_id` confiable en el CSV, generar `line_hash` a partir de campos canónicos.
   - Constraint: `UNIQUE(royalty_statement_id, line_hash)`.

### D) Normalización de “periodos”
- Diferenciar:
  - `reporting_period` (mes del statement: SEP-25)
  - `activity_period` (periodo real de consumo: “September 2025”, etc.)
- En UI por canción:
  - cards por `reporting_period`,
  - details deben mostrar también `activity_period`.

### E) Moneda (USD obligatorio)
- El sistema mostrará y almacenará montos en USD.
- Para CSV Symphonic mensual, usar `Royalty ($US)` como `net_total_usd`.
- Si un archivo no está en USD o no permite normalizar a USD con certeza:
  - fallar el import con un error claro (“El reporte debe estar en USD”).

## Modelo de datos (mínimo esperado)

### Identidad para matching
- `tracks.isrc` (string nullable, index)
- `releases.upc` (string nullable, index)

### Regalías
- `royalty_statements`
  - provider, label, reporting_period, reporting_month_date
  - currency (USD)
  - original_filename, stored_path, file_hash
  - statement_key, version, is_current
  - status (uploaded|processing|processed|failed)
  - totals (total_units, total_net_usd)
  - created_by

- `royalty_statement_lines`
  - royalty_statement_id (FK)
  - track_id nullable (FK)
  - isrc, upc, track_title (raw fallback)
  - channel, country
  - activity_period_text, activity_month_date
  - units, net_total_usd
  - line_hash
  - raw JSON

### Splits (contrato por canción)
- `track_split_agreements` (con vigencia y contrato)
- `track_split_participants`
  - artist_id nullable
  - payee_email nullable
  - role, percentage
- Validación server-side: suma porcentajes = 100%

## Permisos / Seguridad
- Solo admin puede subir/gestionar statements y splits.
- Artista solo ve:
  - tracks donde participa (track_artist) y/o donde figura en split,
  - regalías asociadas a esos tracks.
- Nunca exponer información de otros artistas por error de matching.

## Reglas del importador (Symphonic mensual)
- Detectar formato por headers esperados (p.ej: Reporting Period, Digital Service Provider, ISRC, Royalty ($US), etc.)
- Procesar en Job con lectura por chunks (evitar memory blowups).
- Matching por ISRC:
  - si existe `tracks.isrc == ISRC`, asignar track_id
  - si no, guardar línea como unmatched (track_id null)

## Performance
- Preferir agregados para cards por período por track/artist si las consultas se vuelven pesadas.
- Si se implementan summaries:
  - recalcular al final del import,
  - por defecto la UI muestra solo statements con `is_current = true`.

## Estilo de implementación (Laravel)
- Usar FormRequests para validaciones.
- Usar Policies/Gates para permisos.
- Nombres: migraciones y tablas en snake_case; modelos en StudlyCase.
- Controladores del módulo bajo `App\Http\Controllers\Admin/...` y `App\Http\Controllers/Artist/...` (o convención existente).
- Mantener consistencia con el patrón actual del repo.

## Entrega por hito (formato de salida requerido)
Al terminar un hito, responder con:
1) Resumen de cambios
2) Lista de archivos tocados
3) Comandos de verificación
4) Notas/decisiones importantes
5) “Listo para revisión — no continúo hasta tu OK”