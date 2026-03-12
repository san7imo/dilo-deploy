<?php

namespace App\Services\Compositions;

use App\Models\Composition;
use App\Models\CompositionRoyaltyLine;
use Illuminate\Support\Collection;

class CompositionRoyaltyLineMatcher
{
    /**
     * @var Collection<int, array<string, mixed>>|null
     */
    private ?Collection $compositionsIndex = null;

    /**
     * @var array<int, int>
     */
    private array $byId = [];

    /**
     * @var array<string, array<int>>
     */
    private array $byIswc = [];

    /**
     * @var array<string, array<int>>
     */
    private array $byTitle = [];

    /**
     * @param array<string, mixed> $canonical
     * @return array<string, mixed>
     */
    public function resolve(array $canonical): array
    {
        $this->ensureIndexLoaded();

        $compositionId = $canonical['composition_id'] ?? null;
        $iswc = $canonical['composition_iswc'] ?? null;
        $title = $canonical['composition_title'] ?? null;

        $normalizedIswc = $this->normalizeIswc($iswc);
        $normalizedTitle = $this->normalizeTitle($title);

        if ($compositionId !== null) {
            $id = (int) $compositionId;
            if (isset($this->byId[$id])) {
                return $this->buildResult(
                    CompositionRoyaltyLine::MATCH_STATUS_MATCHED,
                    $id,
                    'composition_id'
                );
            }
        }

        if ($normalizedIswc && isset($this->byIswc[$normalizedIswc])) {
            $candidates = array_values(array_unique($this->byIswc[$normalizedIswc]));
            if (count($candidates) === 1) {
                return $this->buildResult(
                    CompositionRoyaltyLine::MATCH_STATUS_MATCHED,
                    $candidates[0],
                    'iswc'
                );
            }

            return $this->buildAmbiguousResult('iswc', $candidates);
        }

        if ($normalizedTitle && isset($this->byTitle[$normalizedTitle])) {
            $candidates = array_values(array_unique($this->byTitle[$normalizedTitle]));
            if (count($candidates) === 1) {
                return $this->buildResult(
                    CompositionRoyaltyLine::MATCH_STATUS_MATCHED,
                    $candidates[0],
                    'title'
                );
            }

            return $this->buildAmbiguousResult('title', $candidates);
        }

        return [
            'composition_id' => null,
            'match_status' => CompositionRoyaltyLine::MATCH_STATUS_UNMATCHED,
            'match_meta' => [
                'strategy' => 'none',
                'input' => [
                    'composition_id' => $compositionId,
                    'iswc' => $normalizedIswc,
                    'title' => $normalizedTitle,
                ],
                'candidates' => [],
            ],
        ];
    }

    private function ensureIndexLoaded(): void
    {
        if ($this->compositionsIndex !== null) {
            return;
        }

        $index = Composition::query()
            ->select('id', 'title', 'iswc')
            ->get()
            ->map(function (Composition $composition): array {
                return [
                    'id' => (int) $composition->id,
                    'title' => (string) $composition->title,
                    'iswc' => $this->normalizeIswc($composition->iswc),
                    'title_normalized' => $this->normalizeTitle($composition->title),
                ];
            });

        $this->compositionsIndex = $index;

        foreach ($index as $item) {
            $id = (int) $item['id'];
            $this->byId[$id] = $id;

            if (!empty($item['iswc'])) {
                $this->byIswc[$item['iswc']] ??= [];
                $this->byIswc[$item['iswc']][] = $id;
            }

            if (!empty($item['title_normalized'])) {
                $this->byTitle[$item['title_normalized']] ??= [];
                $this->byTitle[$item['title_normalized']][] = $id;
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildResult(string $status, ?int $compositionId, string $strategy): array
    {
        return [
            'composition_id' => $compositionId,
            'match_status' => $status,
            'match_meta' => [
                'strategy' => $strategy,
                'candidates' => $compositionId ? [$compositionId] : [],
            ],
        ];
    }

    /**
     * @param array<int, int> $candidates
     * @return array<string, mixed>
     */
    private function buildAmbiguousResult(string $strategy, array $candidates): array
    {
        return [
            'composition_id' => null,
            'match_status' => CompositionRoyaltyLine::MATCH_STATUS_AMBIGUOUS,
            'match_meta' => [
                'strategy' => $strategy,
                'candidates' => array_values(array_unique($candidates)),
            ],
        ];
    }

    private function normalizeIswc(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $value) ?? '');

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeTitle(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = mb_strtolower(trim((string) $value), 'UTF-8');
        $normalized = preg_replace('/\s+/', ' ', $normalized ?? '');

        return $normalized !== '' ? $normalized : null;
    }
}

