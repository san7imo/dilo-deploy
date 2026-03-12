<?php

namespace App\Services\Royalties;

use InvalidArgumentException;

class MasterCurrencyNormalizer
{
    public const STRATEGY_SOURCE_USD = 'source_currency_usd';
    public const STRATEGY_CONVERTED_BY_RATE_DIVISION = 'converted_using_currency_rate_division';

    public function normalizeFromOriginal(
        float $amountOriginal,
        ?string $currencyOriginal,
        ?float $fxRateToUsd,
        string $source
    ): array {
        $currency = strtoupper(trim((string) $currencyOriginal));
        if ($currency === '') {
            $currency = 'USD';
        }

        if ($currency === 'USD') {
            return [
                'amount_original' => $this->formatDecimal($amountOriginal),
                'currency_original' => 'USD',
                'fx_rate_to_usd' => $this->formatDecimal(1),
                'amount_usd' => $this->formatDecimal($amountOriginal),
                'conversion_strategy' => self::STRATEGY_SOURCE_USD,
            ];
        }

        $rate = (float) ($fxRateToUsd ?? 0);
        if ($rate <= 0) {
            throw new InvalidArgumentException(
                "No se puede normalizar {$source} a USD: currency_rate invalido para moneda {$currency}."
            );
        }

        return [
            'amount_original' => $this->formatDecimal($amountOriginal),
            'currency_original' => $currency,
            'fx_rate_to_usd' => $this->formatDecimal($rate),
            // Inference: currency_rate representa "moneda original por 1 USD".
            'amount_usd' => $this->formatDecimal($amountOriginal / $rate),
            'conversion_strategy' => self::STRATEGY_CONVERTED_BY_RATE_DIVISION,
        ];
    }

    private function formatDecimal(float|int $value): string
    {
        return number_format((float) $value, 6, '.', '');
    }
}

