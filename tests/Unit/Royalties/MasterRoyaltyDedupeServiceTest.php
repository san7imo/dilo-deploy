<?php

namespace Tests\Unit\Royalties;

use App\Services\Royalties\MasterRoyaltyDedupeService;
use PHPUnit\Framework\TestCase;

class MasterRoyaltyDedupeServiceTest extends TestCase
{
    public function test_symphonic_line_hash_does_not_depend_on_row_number(): void
    {
        $service = new MasterRoyaltyDedupeService();

        $canonicalA = [
            'source_name' => 'symphonic',
            'source_line_id' => '1',
            'isrc' => 'COA1B234567',
            'upc' => '123456789012',
            'track_title' => 'Track Uno',
            'dsp_name' => 'Spotify',
            'territory_code' => 'CO',
            'activity_period_text' => 'August 2025',
            'activity_month' => '2025-08-01',
            'units' => 10,
            'amount_usd' => '12.340000',
        ];
        $canonicalB = array_merge($canonicalA, ['source_line_id' => '999']);

        $hashA = $service->buildLineHash([], $canonicalA);
        $hashB = $service->buildLineHash([], $canonicalB);

        $this->assertSame($hashA, $hashB);
    }

    public function test_sonosuite_line_hash_uses_external_line_id(): void
    {
        $service = new MasterRoyaltyDedupeService();

        $canonicalA = [
            'source_name' => 'sonosuite',
            'source_line_id' => '1001',
            'isrc' => 'COA1B234567',
            'upc' => '123456789012',
            'track_title' => 'Track Uno',
            'dsp_name' => 'Spotify',
            'territory_code' => 'CO',
            'activity_period_text' => '2025-08-01 -> 2025-08-31',
            'activity_month' => '2025-08-01',
            'units' => 10,
            'amount_usd' => '12.340000',
        ];
        $canonicalB = array_merge($canonicalA, ['source_line_id' => '1002']);

        $hashA = $service->buildLineHash([], $canonicalA);
        $hashB = $service->buildLineHash([], $canonicalB);

        $this->assertNotSame($hashA, $hashB);
    }

    public function test_it_normalizes_statement_key_consistently(): void
    {
        $service = new MasterRoyaltyDedupeService();

        $key = $service->normalizeStatementKey('  SONOSUITE ', ' Dilo   Records ', ' sep-25 ');

        $this->assertSame('sonosuite|dilo records|sep-25', $key);
    }
}

