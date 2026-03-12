<?php

namespace Tests\Unit\Royalties;

use App\Models\RoyaltyStatementLine;
use App\Services\Royalties\MasterRoyaltyLineMatcher;
use PHPUnit\Framework\TestCase;

class MasterRoyaltyLineMatcherTest extends TestCase
{
    public function test_it_matches_by_isrc_when_single_candidate_exists(): void
    {
        $matcher = $this->makeMatcher(
            [
                'coa1b234567' => [
                    ['id' => 10, 'title' => 'Track Uno', 'isrc' => 'COA1B234567', 'release_upc' => '123456789012'],
                ],
            ],
            []
        );

        $result = $matcher->resolve([
            'isrc' => 'co-a1b-23-4567',
            'upc' => null,
            'track_title' => 'Track Uno',
        ]);

        $this->assertSame(10, $result['track_id']);
        $this->assertSame(RoyaltyStatementLine::MATCH_STATUS_MATCHED, $result['match_status']);
        $this->assertSame('isrc_exact', $result['match_meta']['strategy']);
    }

    public function test_it_marks_ambiguous_when_multiple_isrc_candidates_exist(): void
    {
        $matcher = $this->makeMatcher(
            [
                'coa1b234567' => [
                    ['id' => 10, 'title' => 'Track Uno', 'isrc' => 'COA1B234567', 'release_upc' => '123456789012'],
                    ['id' => 11, 'title' => 'Track Uno Alt', 'isrc' => 'COA1B234567', 'release_upc' => '123456789013'],
                ],
            ],
            []
        );

        $result = $matcher->resolve([
            'isrc' => 'COA1B234567',
            'upc' => null,
            'track_title' => 'Track Uno',
        ]);

        $this->assertNull($result['track_id']);
        $this->assertSame(RoyaltyStatementLine::MATCH_STATUS_AMBIGUOUS, $result['match_status']);
        $this->assertSame(2, $result['match_meta']['candidates_count']);
    }

    public function test_it_matches_by_upc_and_title_when_isrc_missing(): void
    {
        $matcher = $this->makeMatcher(
            [],
            [
                '123456789012|track uno' => [
                    ['id' => 12, 'title' => 'Track Uno', 'isrc' => null, 'release_upc' => '123456789012'],
                ],
            ]
        );

        $result = $matcher->resolve([
            'isrc' => null,
            'upc' => '123-456-789-012',
            'track_title' => 'Track Uno',
        ]);

        $this->assertSame(12, $result['track_id']);
        $this->assertSame(RoyaltyStatementLine::MATCH_STATUS_MATCHED, $result['match_status']);
        $this->assertSame('upc_title_exact', $result['match_meta']['strategy']);
    }

    public function test_it_never_auto_matches_with_title_only(): void
    {
        $matcher = $this->makeMatcher([], []);

        $result = $matcher->resolve([
            'isrc' => null,
            'upc' => null,
            'track_title' => 'Track Uno',
        ]);

        $this->assertNull($result['track_id']);
        $this->assertSame(RoyaltyStatementLine::MATCH_STATUS_UNMATCHED, $result['match_status']);
        $this->assertSame('no_reliable_identifier', $result['match_meta']['strategy']);
    }

    private function makeMatcher(array $tracksByIsrc, array $tracksByUpcAndTitle): MasterRoyaltyLineMatcher
    {
        return new class($tracksByIsrc, $tracksByUpcAndTitle) extends MasterRoyaltyLineMatcher {
            public function __construct(
                private readonly array $seedByIsrc,
                private readonly array $seedByUpcTitle
            ) {
            }

            protected function ensureIndexLoaded(): void
            {
                $this->tracksByIsrc = $this->seedByIsrc;
                $this->tracksByUpcAndTitle = $this->seedByUpcTitle;
            }
        };
    }
}

