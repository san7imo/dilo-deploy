# Hito 6 - Allocations master basados en split sets

## Objetivo del hito
Separar y robustecer el cálculo de allocations de master respecto al ingestion de statements, garantizando split vigente por fecha, suma exacta por línea y recálculo controlado.

## Cambios implementados

### 1) Motor de cálculo exacto por línea
Se agrega `App\Services\Royalties\SplitAllocationCalculator`:
- valida que el total de porcentajes sea 100 (con tolerancia),
- calcula allocations por participante a 6 decimales,
- fuerza cierre exacto en el último participante para evitar drift de redondeo.

Esto garantiza:
- suma de allocations por línea = monto de la línea,
- propagación correcta de líneas negativas.

### 2) Recalculo controlado y trazable
Se agrega tabla:
- `royalty_allocation_recalculations`

Guarda por cada recálculo:
- statement,
- trigger (`trigger_source`),
- motivo (`reason`),
- usuario que dispara (si aplica),
- conteos de líneas y allocations,
- warnings y contexto JSON.

### 3) Integración en `RoyaltyAllocationService`
`rebuildForStatement` ahora:
- acepta contexto opcional de recálculo,
- usa `SplitAllocationCalculator` para master (y composición cuando aplica),
- registra warnings de splits inválidos,
- persiste cada corrida en `royalty_allocation_recalculations`.

### 4) Integración de disparadores
Se pasa contexto explícito de recálculo desde:
- `ProcessRoyaltyStatementJob` (trigger de procesamiento),
- `RoyaltyStatementController@updateLineMatch` (trigger manual).

## Criterios de aceptación cubiertos
- Split vigente por fecha: se mantiene resolución por `activity_month_date` (con fallback) en `RoyaltyAllocationService`.
- Suma por línea: garantizada por cierre exacto del calculador.
- Negativos: soportados y validados en pruebas del calculador.

## Compatibilidad
- No se rompió el flujo Symphonic/Sonosuite.
- No se cambió el esquema principal de `royalty_allocations`.
- Se añadió solo tabla de trazabilidad de recálculos.

