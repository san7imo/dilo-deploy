# Hito 8 - Splits de composicion por pools y versionado

## Objetivo del hito
Modelar ownership de composicion con pools separados (`writer`, `publisher`, `mechanical_payee`) y versionado por vigencia.

## Cambios implementados

### 1) Nueva entidad de versionado: `composition_split_sets`
- Se agrega tabla `composition_split_sets` con:
  - `composition_id`
  - `version`
  - `status`
  - `effective_from`, `effective_to`
  - metadata de contrato (`contract_path`, `contract_hash`, etc.)
- Regla: version incremental por composicion.

### 2) Extension de participantes de composicion
En `composition_split_participants` se agregan:
- `composition_split_set_id`
- `share_pool` (`writer`, `publisher`, `mechanical_payee`)

Backfill:
- Se crea un `split_set` por cada `composition_split_agreement` historico.
- Se vinculan participantes existentes al set.
- Se asigna pool inicial por rol legacy.

### 3) Servicio de negocio para versionar
Se agrega `CompositionSplitSetService` para:
- archivar set activo anterior,
- crear nuevo set versionado,
- crear acuerdo legacy en paralelo para compatibilidad temporal,
- resolver participantes (internos/externos/manuales),
- persistir pool por participante.

### 4) Validacion por pools
Se agrega `CompositionSplitPoolValidator` y se integra en request:
- `writer` suma 100
- `publisher` suma 100
- `mechanical_payee` suma 100

Si no se cumple, el request rechaza el split.

### 5) UI admin de splits de composicion
Formulario de creacion:
- selector de `share_pool` por participante,
- resumen visual de totales por pool,
- payload inicial con los tres pools al 100%.

Listado de splits:
- muestra `version`,
- conteo por pool (`W`, `P`, `M`).

### 6) Compatibilidad de calculo existente
- `RoyaltyAllocationService` preserva flujo actual.
- Si existe `share_pool`, el calculo temporal toma pool `writer` para evitar sobreasignacion antes del Hito 9.

## Criterios de aceptacion cubiertos
- Existe estructura de `composition_split_sets`.
- Validaciones por pool aplicadas (100% en cada pool).
- Versionado de splits por composicion con vigencias.

