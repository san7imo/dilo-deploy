<?php

namespace Tests\Unit\Royalties;

use App\Services\Royalties\MasterCurrencyNormalizer;
use App\Services\Royalties\MasterRoyaltyLineCanonicalNormalizer;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MasterRoyaltyLineCanonicalNormalizerTest extends TestCase
{
    public function test_it_normalizes_a_symphonic_row_to_canonical_structure(): void
    {
        $normalizer = $this->makeNormalizer();

        $rawRow = [
            'Reporting Period' => 'SEP-25',
            'Label' => 'Dilo Records',
            'Release Name' => 'Release Uno',
            'UPC Code' => '123-456-789-012',
            'Track Title' => 'Track Uno',
            'ISRC Code' => 'co-a1b-23-4567',
            'Digital Service Provider' => 'Spotify',
            'Activity Period' => 'August 2025',
            'Territory' => 'Colombia',
            'Delivery' => 'Streaming',
            'Content Type' => 'Single',
            'Sale or Void' => 'Sale',
            'Count' => '10',
            'Royalty ($US)' => '12.34',
        ];

        $normalizedRow = [
            'reporting period' => 'SEP-25',
            'label' => 'Dilo Records',
            'release name' => 'Release Uno',
            'upc code' => '123-456-789-012',
            'track title' => 'Track Uno',
            'isrc code' => 'co-a1b-23-4567',
            'digital service provider' => 'Spotify',
            'activity period' => 'August 2025',
            'territory' => 'Colombia',
            'delivery' => 'Streaming',
            'content type' => 'Single',
            'sale or void' => 'Sale',
            'count' => '10',
            'royalty ($us)' => '12.34',
        ];

        $canonical = $normalizer->normalizeSymphonicRow($rawRow, $normalizedRow, 5, 77);

        $expectedKeys = [
            'source_name',
            'source_statement_id',
            'source_line_id',
            'statement_period_raw',
            'statement_period_start',
            'statement_period_end',
            'activity_start_date',
            'activity_end_date',
            'activity_month',
            'label_name',
            'release_title',
            'upc',
            'track_title',
            'isrc',
            'dsp_name',
            'territory_code',
            'delivery_type',
            'content_type',
            'sale_or_void',
            'units',
            'amount_original',
            'currency_original',
            'fx_rate_to_usd',
            'amount_usd',
            'raw_payload_json',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $canonical);
        }

        $this->assertSame('symphonic', $canonical['source_name']);
        $this->assertSame('77', $canonical['source_statement_id']);
        $this->assertSame('5', $canonical['source_line_id']);
        $this->assertSame('SEP-25', $canonical['statement_period_raw']);
        $this->assertSame('2025-09-01', $canonical['statement_period_start']);
        $this->assertSame('2025-09-30', $canonical['statement_period_end']);
        $this->assertSame('2025-08-01', $canonical['activity_start_date']);
        $this->assertSame('2025-08-31', $canonical['activity_end_date']);
        $this->assertSame('2025-08-01', $canonical['activity_month']);
        $this->assertSame('Dilo Records', $canonical['label_name']);
        $this->assertSame('Release Uno', $canonical['release_title']);
        $this->assertSame('123456789012', $canonical['upc']);
        $this->assertSame('COA1B234567', $canonical['isrc']);
        $this->assertSame(10, $canonical['units']);
        $this->assertSame('12.340000', $canonical['amount_original']);
        $this->assertSame('USD', $canonical['currency_original']);
        $this->assertSame('1.000000', $canonical['fx_rate_to_usd']);
        $this->assertSame('12.340000', $canonical['amount_usd']);
        $this->assertSame('Track Uno', $canonical['raw_payload_json']['raw']['Track Title']);
        $this->assertSame('source_currency_usd', $canonical['raw_payload_json']['monetary']['conversion_strategy']);
    }

    public function test_it_parses_parenthesized_negative_amounts(): void
    {
        $normalizer = $this->makeNormalizer();

        $rawRow = [
            'Reporting Period' => 'SEP-25',
            'Royalty ($US)' => '(1.2345)',
        ];
        $normalizedRow = [
            'reporting period' => 'SEP-25',
            'royalty ($us)' => '(1.2345)',
        ];

        $canonical = $normalizer->normalizeSymphonicRow($rawRow, $normalizedRow, 1, 1);

        $this->assertSame('-1.234500', $canonical['amount_original']);
        $this->assertSame('-1.234500', $canonical['amount_usd']);
    }

    public function test_it_normalizes_a_sonosuite_row_to_canonical_structure(): void
    {
        $normalizer = $this->makeNormalizer();

        $rawRow = [
            'id' => '9988',
            'start_date' => '2025-08-01',
            'end_date' => '2025-08-31',
            'confirmation_report_date' => '2025-09-15',
            'country' => 'Colombia',
            'currency' => 'USD',
            'type' => 'streaming',
            'units' => '12',
            'gross_total' => '20.00',
            'other_costs' => '1.00',
            'channel_costs' => '1.00',
            'taxes' => '0.50',
            'net_total' => '17.50',
            'currency_rate' => '1',
            'channel' => 'Spotify',
            'label' => 'Dilo Records',
            'artist' => 'Artist Uno',
            'release' => 'Release Uno',
            'upc' => '123-123-123-123',
            'track_title' => 'Track Uno',
            'isrc' => 'co-a1b-23-4567',
        ];

        $normalizedRow = $rawRow;

        $canonical = $normalizer->normalizeSonosuiteRow($rawRow, $normalizedRow, 2, 88);

        $this->assertSame('sonosuite', $canonical['source_name']);
        $this->assertSame('88', $canonical['source_statement_id']);
        $this->assertSame('9988', $canonical['source_line_id']);
        $this->assertSame('2025-08-01', $canonical['statement_period_start']);
        $this->assertSame('2025-08-31', $canonical['statement_period_end']);
        $this->assertSame('2025-09-15', $canonical['confirmation_date']);
        $this->assertSame('2025-08-01', $canonical['activity_month']);
        $this->assertSame('123123123123', $canonical['upc']);
        $this->assertSame('COA1B234567', $canonical['isrc']);
        $this->assertSame(12, $canonical['units']);
        $this->assertSame('USD', $canonical['currency_original']);
        $this->assertSame('17.500000', $canonical['amount_original']);
        $this->assertSame('17.500000', $canonical['amount_usd']);
        $this->assertSame('source_currency_usd', $canonical['raw_payload_json']['sonosuite']['conversion_strategy']);
    }

    public function test_it_converts_sonosuite_non_usd_amounts_using_fx_rate(): void
    {
        $normalizer = $this->makeNormalizer();

        $rawRow = [
            'id' => '9989',
            'start_date' => '2025-08-01',
            'end_date' => '2025-08-31',
            'country' => 'Colombia',
            'currency' => 'COP',
            'type' => 'streaming',
            'units' => '1',
            'net_total' => '3500',
            'currency_rate' => '4000',
            'channel' => 'Spotify',
            'label' => 'Dilo Records',
            'artist' => 'Artist Uno',
            'release' => 'Release Uno',
            'track_title' => 'Track Uno',
            'isrc' => 'co-a1b-23-4567',
        ];

        $normalizedRow = $rawRow;

        $canonical = $normalizer->normalizeSonosuiteRow($rawRow, $normalizedRow, 3, 88);

        $this->assertSame('COP', $canonical['currency_original']);
        $this->assertSame('4000.000000', $canonical['fx_rate_to_usd']);
        $this->assertSame('3500.000000', $canonical['amount_original']);
        $this->assertSame('0.875000', $canonical['amount_usd']);
        $this->assertSame(
            'converted_using_currency_rate_division',
            $canonical['raw_payload_json']['sonosuite']['conversion_strategy']
        );
    }

    public function test_it_fails_for_non_usd_sonosuite_rows_without_valid_fx_rate(): void
    {
        $normalizer = $this->makeNormalizer();

        $this->expectException(InvalidArgumentException::class);

        $rawRow = [
            'id' => '9990',
            'currency' => 'EUR',
            'net_total' => '10',
            'currency_rate' => '0',
            'channel' => 'Spotify',
            'track_title' => 'Track Uno',
            'isrc' => 'COA1B234567',
        ];

        $normalizedRow = $rawRow;

        $normalizer->normalizeSonosuiteRow($rawRow, $normalizedRow, 4, 88);
    }

    private function makeNormalizer(): MasterRoyaltyLineCanonicalNormalizer
    {
        return new MasterRoyaltyLineCanonicalNormalizer(
            new MasterCurrencyNormalizer()
        );
    }
}
