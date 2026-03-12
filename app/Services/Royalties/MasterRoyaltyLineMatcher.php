<?php

namespace App\Services\Royalties;

use App\Models\RoyaltyStatementLine;
use App\Models\Track;

class MasterRoyaltyLineMatcher
{
    /** @var array<string, array<int, array<string, mixed>>>|null */
    protected ?array $tracksByIsrc = null;

    /** @var array<string, array<int, array<string, mixed>>>|null */
    protected ?array $tracksByUpcAndTitle = null;

    public function resolve(array $canonical): array
    {
        $this->ensureIndexLoaded();

        $isrc = $this->normalizeIsrc($canonical['isrc'] ?? null);
        $upc = $this->normalizeUpc($canonical['upc'] ?? null);
        $title = $this->normalizeTitle($canonical['track_title'] ?? null);

        $input = [
            'isrc' => $isrc,
            'upc' => $upc,
            'track_title' => $title,
        ];

        if ($isrc !== null) {
            $candidates = $this->tracksByIsrc[strtolower($isrc)] ?? [];

            return $this->buildResult('isrc_exact', $input, $candidates);
        }

        if ($upc !== null && $title !== null) {
            $key = $this->buildUpcTitleKey($upc, $title);
            $candidates = $this->tracksByUpcAndTitle[$key] ?? [];

            return $this->buildResult('upc_title_exact', $input, $candidates);
        }

        return [
            'track_id' => null,
            'match_status' => RoyaltyStatementLine::MATCH_STATUS_UNMATCHED,
            'match_meta' => [
                'strategy' => 'no_reliable_identifier',
                'input' => $input,
                'note' => 'No se aplica auto-match sin ISRC o sin combinacion UPC+titulo.',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function suggestCandidates(?string $isrc, ?string $upc, ?string $trackTitle, int $limit = 10): array
    {
        $this->ensureIndexLoaded();

        $normalizedIsrc = $this->normalizeIsrc($isrc);
        $normalizedUpc = $this->normalizeUpc($upc);
        $normalizedTitle = $this->normalizeTitle($trackTitle);

        $candidates = [];

        if ($normalizedIsrc !== null) {
            $candidates = array_merge(
                $candidates,
                $this->tracksByIsrc[strtolower($normalizedIsrc)] ?? []
            );
        }

        if ($normalizedUpc !== null && $normalizedTitle !== null) {
            $key = $this->buildUpcTitleKey($normalizedUpc, $normalizedTitle);
            $candidates = array_merge(
                $candidates,
                $this->tracksByUpcAndTitle[$key] ?? []
            );
        }

        $scored = collect($candidates)
            ->unique('id')
            ->map(function (array $candidate) use ($normalizedIsrc, $normalizedUpc, $normalizedTitle) {
                $score = 0;
                $reasons = [];

                $candidateIsrc = $this->normalizeIsrc((string) ($candidate['isrc'] ?? ''));
                $candidateUpc = $this->normalizeUpc((string) ($candidate['release_upc'] ?? ''));
                $candidateTitle = $this->normalizeTitle((string) ($candidate['title'] ?? ''));

                if ($normalizedIsrc && $candidateIsrc === $normalizedIsrc) {
                    $score += 100;
                    $reasons[] = 'ISRC';
                }

                if ($normalizedUpc && $candidateUpc === $normalizedUpc) {
                    $score += 80;
                    $reasons[] = 'UPC';
                }

                if ($normalizedTitle && $candidateTitle === $normalizedTitle) {
                    $score += 40;
                    $reasons[] = 'Titulo';
                }

                return array_merge($candidate, [
                    'score' => $score,
                    'match_reason' => empty($reasons) ? 'Coincidencia parcial' : implode(' + ', $reasons),
                ]);
            })
            ->sortByDesc('score')
            ->take(max(1, $limit))
            ->values()
            ->map(fn(array $candidate) => [
                'id' => (int) $candidate['id'],
                'title' => (string) ($candidate['title'] ?? ''),
                'isrc' => $candidate['isrc'],
                'release_upc' => $candidate['release_upc'],
                'match_reason' => $candidate['match_reason'],
            ])
            ->all();

        return $scored;
    }

    private function buildResult(string $strategy, array $input, array $candidates): array
    {
        $count = count($candidates);

        if ($count === 1) {
            $candidate = $candidates[0];

            return [
                'track_id' => (int) $candidate['id'],
                'match_status' => RoyaltyStatementLine::MATCH_STATUS_MATCHED,
                'match_meta' => [
                    'strategy' => $strategy,
                    'input' => $input,
                    'candidates_count' => 1,
                    'matched_track_id' => (int) $candidate['id'],
                ],
            ];
        }

        if ($count > 1) {
            return [
                'track_id' => null,
                'match_status' => RoyaltyStatementLine::MATCH_STATUS_AMBIGUOUS,
                'match_meta' => [
                    'strategy' => $strategy,
                    'input' => $input,
                    'candidates_count' => $count,
                    'candidates' => array_slice($candidates, 0, 10),
                ],
            ];
        }

        return [
            'track_id' => null,
            'match_status' => RoyaltyStatementLine::MATCH_STATUS_UNMATCHED,
            'match_meta' => [
                'strategy' => $strategy,
                'input' => $input,
                'candidates_count' => 0,
            ],
        ];
    }

    protected function ensureIndexLoaded(): void
    {
        if ($this->tracksByIsrc !== null && $this->tracksByUpcAndTitle !== null) {
            return;
        }

        $rows = Track::query()
            ->leftJoin('releases', 'releases.id', '=', 'tracks.release_id')
            ->whereNull('tracks.deleted_at')
            ->where(function ($query) {
                $query->whereNull('releases.id')
                    ->orWhereNull('releases.deleted_at');
            })
            ->select([
                'tracks.id',
                'tracks.title',
                'tracks.isrc',
                'releases.upc as release_upc',
            ])
            ->orderBy('tracks.id')
            ->get();

        $byIsrc = [];
        $byUpcTitle = [];

        foreach ($rows as $row) {
            $entry = [
                'id' => (int) $row->id,
                'title' => $row->title,
                'isrc' => $row->isrc,
                'release_upc' => $row->release_upc,
            ];

            $isrc = $this->normalizeIsrc($row->isrc);
            if ($isrc !== null) {
                $byIsrc[strtolower($isrc)][] = $entry;
            }

            $upc = $this->normalizeUpc($row->release_upc);
            $title = $this->normalizeTitle($row->title);
            if ($upc !== null && $title !== null) {
                $byUpcTitle[$this->buildUpcTitleKey($upc, $title)][] = $entry;
            }
        }

        $this->tracksByIsrc = $byIsrc;
        $this->tracksByUpcAndTitle = $byUpcTitle;
    }

    private function normalizeIsrc(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $value));
        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeUpc(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\D/', '', (string) $value);
        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeTitle(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', (string) $value)));
        return $normalized !== '' ? $normalized : null;
    }

    private function buildUpcTitleKey(string $upc, string $title): string
    {
        return $upc . '|' . $title;
    }
}
