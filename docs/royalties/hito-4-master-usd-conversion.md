# Hito 4 - Conversion monetaria a USD en master

## Objetivo del hito
Centralizar la conversion monetaria a USD para lineas master (Symphonic y Sonosuite), con trazabilidad explicita y sin perder monto original.

## Cambios implementados

### 1) Servicio central de normalizacion monetaria
Se usa `App\Services\Royalties\MasterCurrencyNormalizer` como unica capa para derivar:
- `amount_original`
- `currency_original`
- `fx_rate_to_usd`
- `amount_usd`
- `conversion_strategy`

Reglas aplicadas:
- Si `currency_original = USD`, no convierte y usa estrategia `source_currency_usd`.
- Si la moneda no es USD, convierte con `amount_original / fx_rate_to_usd` y estrategia `converted_using_currency_rate_division`.
- Si la moneda no es USD y no hay tasa valida, lanza error y el import falla (auditable en logs/estado failed).

### 2) Refactor del normalizador canonico
`MasterRoyaltyLineCanonicalNormalizer` ahora delega conversiones al servicio central:
- Symphonic: monetario en USD con trazabilidad (`raw_payload_json.monetary.conversion_strategy`).
- Sonosuite: conversion segun `currency` + `currency_rate`, sin fallback silencioso.

### 3) Trazabilidad de proveedor en payload raw
`ProcessRoyaltyStatementJob` corrige `raw.source.provider` para persistir el proveedor real del statement (`symphonic` o `sonosuite`), en vez de valor fijo.

### 4) Pruebas unitarias
Se agregan pruebas para:
- Servicio monetario aislado (`MasterCurrencyNormalizerTest`).
- Normalizador canonico con casos Sonosuite USD y no-USD.
- Error esperado cuando Sonosuite no-USD no trae tasa valida.

## Compatibilidad
- Symphonic se mantiene estable (sigue en USD nativo).
- Sonosuite mantiene flujo actual y ahora falla explicitamente en conversiones no auditables.
- No se agregaron migraciones.

