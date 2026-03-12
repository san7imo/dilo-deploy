# AGENTS.md

## Proyecto

Implementación del módulo de regalías y splits para **Dilo Records**, cubriendo dos dominios separados pero relacionados:

1. **Master Royalties**
2. **Composition Royalties**

Este archivo define reglas obligatorias, límites de alcance, criterios de calidad, hitos verificables y forma de trabajo para que Codex implemente el sistema de forma consistente, auditable y segura.

---

## Objetivo general

Construir un sistema de regalías robusto, auditable y extensible que:

- procese statements de **master royalties** provenientes de distribuidores como **Symphonic** y **Sonosuite**;
- normalice esos datos a un modelo canónico interno;
- convierta montos a **USD** cuando corresponda;
- haga match confiable con el catálogo interno usando **ISRC** para grabaciones;
- calcule allocations según splits internos de máster;
- modele por separado las **composiciones**, sus splits y sus regalías;
- procese en una fase posterior statements de composición desde PROs, MLC o administradores editoriales;
- muestre a cada participante cuánto le corresponde, cuánto está acumulado, cuánto está pendiente y cuánto fue pagado.

---

## Principios no negociables

### 1. Separación estricta de derechos

**Nunca mezclar master royalties con composition royalties.**

Ambos dominios deben vivir separados en:

- modelo de datos,
- lógica de negocio,
- importadores,
- allocations,
- validaciones,
- reportes.

### 2. Identificadores correctos

- **ISRC** identifica la **grabación** y debe ser la llave primaria funcional para master royalties.
- **UPC** identifica el release y sirve como soporte adicional.
- **ISWC** identifica la **composición**, pero puede no existir al inicio.
- Si no hay ISWC, usar un `composition_id` interno como fuente de verdad para composición.

### 3. El statement no define ownership

Los archivos importados **no son la fuente de verdad contractual**.

Los statements solo indican:

- uso,
- fuente,
- período,
- monto,
- metadatos de explotación.

Los porcentajes que le corresponden a cada participante deben salir de:

- splits internos de master, o
- splits internos de composición.

### 4. Toda línea importada debe ser auditable

Cada fila importada debe conservar:

- fuente,
- identificador externo,
- archivo origen,
- payload crudo,
- versión del importador,
- timestamps,
- estado de match,
- estado de allocation.

### 5. No perder precisión monetaria

Trabajar con decimal de alta precisión.

- No redondear en pasos intermedios.
- Redondear solo al presentar resultados o consolidar payouts según reglas contables definidas.
- Conservar monto original, moneda original, tasa de cambio y monto normalizado en USD.

### 6. Soporte de recalculo

El sistema debe permitir recalcular allocations si:

- cambia un split,
- se corrige un match,
- cambia una tasa de conversión,
- se reimporta un archivo con errores corregidos,
- se ajusta una composición o grabación.

---

## Alcance funcional

---

## Dominio A: Master Royalties

### Objetivo

Procesar statements de distribuidores y asignar regalías de la grabación a los participantes definidos en splits de máster.

### Fuentes iniciales

- Symphonic
- Sonosuite

### Reglas

- Todo master royalty entra por el dominio de grabaciones.
- El match principal debe hacerse por **ISRC**.
- El release puede apoyarse con **UPC**.
- Nunca usar solo el título para match definitivo.
- Toda línea negativa, void o ajuste debe conservarse y asignarse también.

### Entradas soportadas

#### Symphonic
Formato ya existente en el sistema.

Campos conocidos de referencia:

- Reporting Period
- Label
- Release Name
- Release Version
- Release Artists
- UPC Code
- Catalogue
- Track Title
- Mix Version
- ISRC Code
- Track Artists
- Digital Service Provider
- Activity Period
- Territory
- Delivery
- Content Type
- Sale or Void
- Count
- Royalty ($US)

#### Sonosuite
Nuevo formato a soportar.

Campos observados:

- id
- start_date
- end_date
- confirmation_report_date
- country
- currency
- type
- units
- unit_price
- gross_total
- other_costs
- channel_costs
- taxes
- net_total
- currency_rate
- gross_total_client_currency
- other_costs_client_currency
- channel_costs_client_currency
- net_total_client_currency
- user_email
- channel
- label
- artist
- release
- upc
- track_title
- isrc

### Requisito obligatorio de normalización

Todos los importadores de máster deben terminar produciendo un mismo modelo canónico interno.

#### Modelo canónico sugerido para líneas master

Cada línea normalizada debe exponer como mínimo:

- `source_name`
- `source_statement_id`
- `source_line_id`
- `statement_period_raw`
- `statement_period_start`
- `statement_period_end`
- `activity_start_date`
- `activity_end_date`
- `activity_month`
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

### Regla de conversión a USD

El sistema entrega reportes en USD. Por tanto:

- toda línea master debe almacenar `amount_usd`;
- si la fuente ya viene en USD, conservar ese valor como verdad de origen;
- si la fuente viene en otra moneda, convertir a USD usando una estrategia explícita y auditable.

#### Regla preferida para Sonosuite

Dado que Sonosuite trae múltiples campos monetarios, la implementación debe:

1. conservar los importes originales;
2. conservar los importes en moneda cliente si existen;
3. definir con claridad cuál campo será la base de cálculo para reportes;
4. convertir siempre a una columna final `amount_usd`.

#### Estrategia recomendada

- Si la línea ya puede mapearse de forma confiable a USD desde la fuente, usar ese valor.
- Si no, convertir desde `amount_original` usando una tasa explícita y almacenada.
- La lógica exacta debe ser centralizada en un servicio de normalización monetaria, no dispersa en importadores.

### Dedupe y control de duplicados

Implementar dos niveles:

#### A. Dedupe técnico
Evitar importar el mismo archivo dos veces si:

- mismo hash,
- misma fuente,
- mismo statement,
- mismo tamaño y firma esperada.

#### B. Dedupe financiero
Detectar si un archivo contiene líneas que ya existen en statements previos.

- No eliminar automáticamente sin trazabilidad.
- Permitir marcar statements como `reference_only` si son subconjuntos de otros.
- Nunca descartar filas silenciosamente.

### Match de catálogo master

#### Orden obligatorio de matching

1. Match exacto por `isrc`
2. Si falla, intentar `upc + normalized track title`
3. Si falla, intentar `catalogue + normalized track title`
4. Si falla, marcar como `unmatched` y enviar a revisión manual

#### Prohibiciones

- No hacer match definitivo solo por título.
- No crear tracks nuevos automáticamente sin una bandera explícita de ingestión aprobada.

### Splits de master

Los splits de master se configuran dentro del sistema y no en los CSV.

#### Reglas

- Debe existir un `split_set` versionado por grabación.
- La suma de porcentajes activos debe ser 100.
- Los splits deben poder tener vigencia temporal.
- La selección del split debe basarse en la fecha de actividad de la línea, no solo en el statement period.

#### Ejemplo conceptual

Para cada `recording_id`:

- `master_split_sets`
- `master_split_participants`

Campos mínimos sugeridos:

`master_split_sets`
- `id`
- `recording_id`
- `basis`
- `effective_from`
- `effective_to`
- `status`
- `notes`

`master_split_participants`
- `id`
- `split_set_id`
- `party_id`
- `role`
- `percentage`
- `is_recoupable`
- `recoup_priority`
- `notes`

### Allocations de master

Por cada línea importada:

1. resolver recording por match;
2. resolver split activo por fecha de actividad;
3. calcular allocations por participante;
4. persistir allocations como filas auditablemente trazables.

#### Regla de cálculo

`allocated_amount_usd = line.amount_usd * (percentage / 100)`

#### Reglas adicionales

- Si la línea es negativa, allocation negativo.
- No consolidar demasiado pronto.
- Cada allocation debe apuntar a la línea origen.

---

## Dominio B: Composition Royalties

### Objetivo

Modelar propiedad y splits de composición para luego procesar regalías provenientes de PROs, MLC, publishers o administradores.

### Reglas fundamentales

- Composition no usa ISRC como llave primaria de ownership.
- Una composición puede estar vinculada a múltiples grabaciones.
- El sistema debe permitir composiciones sin ISWC al inicio.
- Los splits de composición se configuran manualmente y luego se aplican sobre statements externos.

### Entidades mínimas

#### `compositions`
Representa la obra musical.

Campos sugeridos:
- `id`
- `title`
- `normalized_title`
- `iswc`
- `status`
- `metadata_json`

#### `recording_compositions`
Relación entre grabación y composición.

Campos sugeridos:
- `id`
- `recording_id`
- `composition_id`
- `relationship_type`

#### `composition_registrations`
Registro externo de la obra.

Campos sugeridos:
- `id`
- `composition_id`
- `source_name`
- `external_work_id`
- `member_account_id`
- `registered_title`
- `registration_status`
- `registered_at`
- `last_verified_at`

### Splits de composición

No usar una sola tabla plana de porcentaje sin distinguir contexto.

Separar por lado de derecho.

#### Pools mínimos a soportar

1. `writer`
2. `publisher`
3. `mechanical_payee`

### Regla de validación

#### Performance splits

- suma `writer` = 100
- suma `publisher` = 100

#### Mechanical splits

- suma `mechanical_payee` = 100

### Modelo sugerido

`composition_split_sets`
- `id`
- `composition_id`
- `effective_from`
- `effective_to`
- `status`
- `notes`

`composition_split_participants`
- `id`
- `split_set_id`
- `party_id`
- `role`
- `share_side`
- `share_percent`
- `notes`

### Reglas de cálculo de composición

#### Performance royalty

Cuando una línea de performance entra:

- 50% del monto total va al pool `writer`
- 50% del monto total va al pool `publisher`

Luego cada pool se reparte según sus shares internos.

#### Mechanical royalty

Cuando una línea de mechanical entra:

- 100% del monto se reparte según `mechanical_payee`

### Importación de composition royalties

Primero construir el dominio y el motor de allocations. Los importadores específicos son fase posterior.

#### Formatos a soportar después

- ASCAP CSV / eStatement
- The MLC TSV Work Summary
- The MLC TSV Royalty Detail
- plantilla manual Dilo para carga estándar

### Matching de composition statements

Orden sugerido:

1. `external_work_id`
2. `iswc`
3. `normalized title + writers`
4. `recording_compositions` mediante ISRC solo como apoyo, nunca como ownership principal

### Allocations de composición

Toda línea importada debe producir allocations contra el split set correcto según:

- composition_id
- source_type
- usage period
- split vigente

---

## Forma de trabajo para Codex

### Regla principal

**No implementar todo de una sola vez.**

Trabajar por hitos pequeños, verificables, testeables y con entregables claros.

### En cada hito, Codex debe:

1. leer este archivo completo antes de modificar código;
2. identificar qué parte del dominio toca;
3. no mezclar tareas de master y composition si el hito no lo pide;
4. producir cambios mínimos pero completos;
5. incluir tests o validaciones del alcance trabajado;
6. dejar migraciones, modelos y servicios coherentes con nombres consistentes;
7. evitar decisiones implícitas sobre dinero o currency sin documentarlas.

### Convenciones obligatorias

- Nombres explícitos y semánticos.
- Evitar abreviaturas ambiguas.
- No usar enums mágicos sin documentación.
- Centralizar reglas monetarias.
- Centralizar reglas de matching.
- Centralizar reglas de allocation.
- No duplicar lógica entre importadores.

---

## Plan de implementación por hitos

# Hito 1 — Blindaje del dominio actual de master royalties

## Objetivo

Entender y estabilizar la implementación actual de Symphonic antes de agregar Sonosuite.

## Entregables

- Inventario de tablas, jobs, servicios y comandos actuales involucrados en master royalties.
- Identificación del flujo actual desde CSV hasta allocations.
- Documentación breve del estado actual.
- Tests o scripts de verificación del comportamiento actual.

## Criterios de aceptación

- Se puede describir exactamente cómo el sistema actual procesa Symphonic.
- Existen puntos claros donde extender sin romper lo existente.
- Hay una prueba reproducible que confirma el flujo actual.

---

# Hito 2 — Modelo canónico de master royalty lines

## Objetivo

Crear una capa de normalización común para múltiples distribuidores.

## Entregables

- Definición del modelo canónico interno.
- Servicio de normalización reusable.
- Adaptación del importador de Symphonic para producir ese modelo canónico.
- Persistencia de payload crudo y metadatos de fuente.

## Criterios de aceptación

- Symphonic sigue funcionando.
- El flujo ya no depende de columnas específicas fuera del adaptador.
- El sistema puede recibir líneas normalizadas desde cualquier fuente.

---

# Hito 3 — Importador de Sonosuite

## Objetivo

Agregar soporte a Sonosuite usando el mismo dominio de master royalties.

## Entregables

- Parser de Sonosuite.
- Adaptador Sonosuite → modelo canónico.
- Persistencia de columnas críticas de Sonosuite.
- Manejo de fechas de actividad y confirmación.

## Criterios de aceptación

- Un CSV de Sonosuite puede importarse sin romper Symphonic.
- Las líneas quedan normalizadas en el mismo modelo interno.
- El sistema distingue fuente, statement y line ids externos.

---

# Hito 4 — Conversión monetaria a USD en master

## Objetivo

Garantizar que todos los reportes master terminen en USD.

## Entregables

- Servicio central de normalización monetaria.
- Reglas explícitas de derivación de `amount_usd`.
- Persistencia de monto original, moneda original, tasa y monto USD.
- Pruebas de precisión y trazabilidad.

## Criterios de aceptación

- Tanto Symphonic como Sonosuite dejan `amount_usd` consistente.
- Es posible auditar cómo se obtuvo el valor final en USD.
- No se pierde el monto original.

---

# Hito 5 — Matching y dedupe robustos de master

## Objetivo

Hacer confiable el match con catálogo y evitar duplicados silenciosos.

## Entregables

- Servicio central de matching.
- Servicio central de dedupe técnico y financiero.
- Estados de match: `matched`, `unmatched`, `ambiguous`, `duplicate`, `reference_only`.
- Cola o reporte de revisión manual.

## Criterios de aceptación

- El match sigue prioridad ISRC.
- No hay match automático solo por título.
- Los duplicados quedan trazables.

---

# Hito 6 — Allocations de master basados en split sets

## Objetivo

Separar definitivamente statement ingestion de split calculation.

## Entregables

- `master_split_sets`
- `master_split_participants`
- `master_allocations`
- servicio de resolución de split por fecha
- recálculo controlado

## Criterios de aceptación

- El allocation usa el split vigente por fecha de actividad.
- La suma de allocations por línea coincide con el monto de la línea.
- Los negativos se propagan correctamente.

---

# Hito 7 — Dominio base de composición

## Objetivo

Construir el catálogo de composiciones y su relación con grabaciones.

## Entregables

- `compositions`
- `recording_compositions`
- `composition_registrations`
- servicios básicos de creación, edición y vinculación

## Criterios de aceptación

- Una grabación puede vincularse a una o varias composiciones.
- Una composición puede existir sin ISWC.
- La relación recording ↔ composition es auditable.

---

# Hito 8 — Splits de composición

## Objetivo

Modelar ownership y shares de composición correctamente.

## Entregables

- `composition_split_sets`
- `composition_split_participants`
- validaciones por pool de shares
- servicios para crear y versionar splits

## Criterios de aceptación

- writer suma 100
- publisher suma 100
- mechanical_payee suma 100
- los splits pueden cambiar por vigencia

---

# Hito 9 — Composition royalty statements y allocations

## Objetivo

Preparar el dominio para procesar statements de composición.

## Entregables

- `composition_royalty_statements`
- `composition_royalty_lines`
- `composition_allocations`
- motor de allocations para performance y mechanical
- plantilla manual estándar Dilo para importación inicial

## Criterios de aceptación

- Una línea performance reparte 50/50 writer/publisher.
- Una línea mechanical reparte sobre mechanical_payee.
- Todo queda listo para agregar importadores ASCAP / MLC después.

---

# Hito 10 — Dashboards y reportes

## Objetivo

Mostrar a cada participante cuánto le corresponde en ambos dominios sin mezclar derechos.

## Entregables

- vistas o endpoints de resumen por participante
- total accrued
- total payable
- total paid
- desglose por track, composición, fuente, período y territorio

## Criterios de aceptación

- master y composition se muestran separados.
- se puede entender claramente de dónde sale cada monto.
- los montos coinciden con allocations persistidos.

---

## Requisitos de testing

### Reglas mínimas

Cada hito debe incluir pruebas suficientes para proteger su alcance.

### Tipos de pruebas esperadas

#### Unit tests

Para:
- normalización,
- currency conversion,
- matching,
- split validation,
- allocation math.

#### Integration tests

Para:
- importar archivo completo,
- persistir statement,
- generar líneas,
- hacer match,
- calcular allocations.

#### Regression tests

Especialmente para Symphonic, ya que no se debe romper el flujo existente.

### Casos obligatorios

- línea positiva
- línea negativa
- línea unmatched
- split inválido que no suma 100
- múltiple vigencia de splits
- archivo duplicado
- archivo subset marcado como reference_only
- Sonosuite con moneda distinta de USD
- reallocación tras corregir split

---

## Requisitos de observabilidad y auditoría

Cada proceso de importación debe registrar:

- archivo procesado
- fuente
- timestamp
- cantidad de filas leídas
- cantidad de filas válidas
- cantidad de filas inválidas
- cantidad de filas matched
- cantidad de filas unmatched
- cantidad de allocations creadas
- errores y warnings

Cada allocation debe poder rastrearse hacia:

- statement
- line
- split set aplicado
- participante
- monto calculado

---

## Requisitos de seguridad y consistencia

- No borrar statements importados; usar estados lógicos.
- No sobrescribir allocations históricos sin versionado o trazabilidad.
- Cualquier recálculo debe registrar motivo y usuario/proceso disparador.
- No alterar montos originales del statement.
- No mezclar payout status con accrued status.

---

## Decisiones explícitas para evitar errores

### A. Sobre Sonosuite

- No asumir semántica de `type` si no está documentada.
- Guardar valor raw y mapearlo después si se valida.
- No deduplicar agresivamente por campos parciales.
- Usar `source_line_id` externo como referencia principal de línea.

### B. Sobre composición

- No esperar a tener ASCAP o MLC para modelar composición.
- Primero construir catálogo, splits y allocations.
- Luego agregar importadores específicos.

### C. Sobre ownership

- No inferir splits desde statements externos.
- Los splits son configurados internamente y luego aplicados.

---

## Definition of Done global

Una implementación se considera aceptable solo si:

1. respeta la separación master vs composition;
2. preserva auditabilidad total por línea y allocation;
3. soporta recalculo controlado;
4. permite normalizar Symphonic y Sonosuite a un modelo común;
5. deja montos master reportables en USD;
6. modela composición con splits por pool adecuados;
7. incluye pruebas suficientes;
8. no rompe comportamiento existente de Symphonic.

---

## Instrucción final para Codex

Implementar **solo el hito solicitado** en cada iteración.

No adelantar hitos sin necesidad.

Si una decisión de negocio es ambigua:

- no inventarla silenciosamente;
- dejarla explícita en comentarios o notas técnicas;
- elegir la opción más auditable y reversible.

La prioridad de implementación es:

1. estabilizar master actual;
2. normalizar master;
3. agregar Sonosuite;
4. asegurar USD y allocations;
5. construir composición correctamente;
6. luego importar regalías de composición.