<?php

namespace Tests\Unit\Royalties;

use App\Services\Royalties\MasterCurrencyNormalizer;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MasterCurrencyNormalizerTest extends TestCase
{
    public function test_it_keeps_usd_amounts_without_conversion(): void
    {
        $service = new MasterCurrencyNormalizer();

        $result = $service->normalizeFromOriginal(12.34, 'usd', null, 'symphonic');

        $this->assertSame('12.340000', $result['amount_original']);
        $this->assertSame('USD', $result['currency_original']);
        $this->assertSame('1.000000', $result['fx_rate_to_usd']);
        $this->assertSame('12.340000', $result['amount_usd']);
        $this->assertSame(MasterCurrencyNormalizer::STRATEGY_SOURCE_USD, $result['conversion_strategy']);
    }

    public function test_it_converts_non_usd_amounts_using_fx_rate_division(): void
    {
        $service = new MasterCurrencyNormalizer();

        $result = $service->normalizeFromOriginal(3500, 'COP', 4000, 'sonosuite');

        $this->assertSame('3500.000000', $result['amount_original']);
        $this->assertSame('COP', $result['currency_original']);
        $this->assertSame('4000.000000', $result['fx_rate_to_usd']);
        $this->assertSame('0.875000', $result['amount_usd']);
        $this->assertSame(
            MasterCurrencyNormalizer::STRATEGY_CONVERTED_BY_RATE_DIVISION,
            $result['conversion_strategy']
        );
    }

    public function test_it_throws_for_non_usd_without_valid_fx_rate(): void
    {
        $service = new MasterCurrencyNormalizer();

        $this->expectException(InvalidArgumentException::class);

        $service->normalizeFromOriginal(10, 'EUR', 0, 'sonosuite');
    }
}

