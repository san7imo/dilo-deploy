<?php

namespace App\Services\Compositions;

use App\Models\CompositionRoyaltyLine;
use App\Models\CompositionRoyaltyStatement;
use App\Models\CompositionSplitSet;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CompositionRoyaltyAllocationService
{
    public function __construct(
        private readonly CompositionRoyaltyAllocationCalculator $calculator
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function rebuildForStatement(CompositionRoyaltyStatement $statement): array
    {
        $stats = [
            'lines_total' => 0,
            'lines_matched' => 0,
            'allocations_count' => 0,
            'allocations_total_usd' => 0.0,
            'warnings' => [],
        ];

        if (!$this->canAllocate()) {
            $stats['warnings'][] = 'Tablas de composición no disponibles para allocations.';
            return $stats;
        }

        DB::table('composition_allocations')
            ->where('composition_royalty_statement_id', $statement->id)
            ->delete();

        $lineQuery = CompositionRoyaltyLine::query()
            ->where('composition_royalty_statement_id', $statement->id)
            ->whereNotNull('composition_id')
            ->where('match_status', CompositionRoyaltyLine::MATCH_STATUS_MATCHED);

        $stats['lines_total'] = (clone $lineQuery)->count();
        $stats['lines_matched'] = $stats['lines_total'];

        if ($stats['lines_total'] === 0) {
            return $stats;
        }

        $compositionIds = (clone $lineQuery)
            ->distinct()
            ->pluck('composition_id')
            ->map(fn($id) => (int) $id)
            ->values();

        $splitSetsByComposition = CompositionSplitSet::query()
            ->whereIn('composition_id', $compositionIds)
            ->with(['participants:id,composition_split_set_id,artist_id,user_id,payee_email,role,share_pool,percentage'])
            ->get(['id', 'composition_id', 'status', 'effective_from', 'effective_to', 'created_at'])
            ->groupBy('composition_id')
            ->map(fn(Collection $sets) => $this->sortSplitSetsByPriority($sets)->values());

        $lineQuery
            ->orderBy('id')
            ->chunkById(1000, function ($lines) use (&$stats, $statement, $splitSetsByComposition): void {
                $rows = [];
                $now = now();

                foreach ($lines as $line) {
                    $sets = $splitSetsByComposition->get((int) $line->composition_id, collect());
                    if ($sets->isEmpty()) {
                        $stats['warnings'][] = "No hay split set para composición {$line->composition_id} (line {$line->id}).";
                        continue;
                    }

                    $referenceDate = $this->resolveReferenceDate(
                        $line->activity_month_date?->toDateString(),
                        $statement->reporting_month_date?->toDateString(),
                        $statement->reporting_period
                    );

                    $splitSet = $this->resolveSplitSetForDate($sets, $referenceDate);
                    if (!$splitSet) {
                        $stats['warnings'][] = "No se pudo resolver split set por vigencia para line {$line->id}.";
                        continue;
                    }

                    $participants = $splitSet->participants
                        ->filter(fn($participant) => (float) $participant->percentage > 0)
                        ->values();

                    $writers = $participants->where('share_pool', 'writer')->values();
                    $publishers = $participants->where('share_pool', 'publisher')->values();
                    $mechanicals = $participants->where('share_pool', 'mechanical_payee')->values();

                    $gross = (float) $line->amount_usd;
                    $lineType = strtolower((string) $line->line_type);

                    if ($lineType === CompositionRoyaltyLine::LINE_TYPE_PERFORMANCE) {
                        $result = $this->calculator->allocatePerformance($gross, $writers, $publishers);
                        if (!$result['valid']) {
                            $stats['warnings'][] = "Split inválido de performance para line {$line->id}.";
                            continue;
                        }

                        foreach (['writer', 'publisher'] as $pool) {
                            $poolGross = $pool === 'writer'
                                ? round($gross * 0.5, 6)
                                : round($gross - round($gross * 0.5, 6), 6);

                            foreach ($result['allocations'][$pool] as $item) {
                                $participant = $item['participant'];
                                $rows[] = $this->buildAllocationRow(
                                    statementId: (int) $statement->id,
                                    lineId: (int) $line->id,
                                    compositionId: (int) $line->composition_id,
                                    splitSetId: (int) $splitSet->id,
                                    participant: $participant,
                                    sharePool: $pool,
                                    lineType: CompositionRoyaltyLine::LINE_TYPE_PERFORMANCE,
                                    activityMonthDate: $line->activity_month_date?->toDateString(),
                                    splitPercentage: (float) $item['percentage'],
                                    grossAmountUsd: $poolGross,
                                    allocatedAmountUsd: (float) $item['allocated'],
                                    now: $now,
                                );
                            }
                        }

                        continue;
                    }

                    if ($lineType === CompositionRoyaltyLine::LINE_TYPE_MECHANICAL) {
                        $result = $this->calculator->allocateMechanical($gross, $mechanicals);
                        if (!$result['valid']) {
                            $stats['warnings'][] = "Split inválido de mechanical para line {$line->id}.";
                            continue;
                        }

                        foreach ($result['allocations']['mechanical_payee'] as $item) {
                            $participant = $item['participant'];
                            $rows[] = $this->buildAllocationRow(
                                statementId: (int) $statement->id,
                                lineId: (int) $line->id,
                                compositionId: (int) $line->composition_id,
                                splitSetId: (int) $splitSet->id,
                                participant: $participant,
                                sharePool: 'mechanical_payee',
                                lineType: CompositionRoyaltyLine::LINE_TYPE_MECHANICAL,
                                activityMonthDate: $line->activity_month_date?->toDateString(),
                                splitPercentage: (float) $item['percentage'],
                                grossAmountUsd: $gross,
                                allocatedAmountUsd: (float) $item['allocated'],
                                now: $now,
                            );
                        }

                        continue;
                    }

                    $stats['warnings'][] = "line_type no soportado en line {$line->id}: {$line->line_type}";
                }

                if (empty($rows)) {
                    return;
                }

                $stats['allocations_count'] += count($rows);
                $stats['allocations_total_usd'] += array_sum(array_map(
                    fn(array $row) => (float) $row['allocated_amount_usd'],
                    $rows
                ));

                DB::table('composition_allocations')->upsert(
                    $rows,
                    ['composition_royalty_line_id', 'composition_split_participant_id', 'share_pool'],
                    [
                        'composition_royalty_statement_id',
                        'composition_id',
                        'composition_split_set_id',
                        'party_user_id',
                        'party_artist_id',
                        'party_email',
                        'role',
                        'line_type',
                        'activity_month_date',
                        'split_percentage',
                        'gross_amount_usd',
                        'allocated_amount_usd',
                        'currency',
                        'status',
                        'updated_at',
                    ]
                );
            });

        if (!empty($stats['warnings'])) {
            Log::warning('[CompositionRoyalties] warnings en rebuildForStatement', [
                'statement_id' => (int) $statement->id,
                'warnings' => $stats['warnings'],
            ]);
        }

        return $stats;
    }

    private function canAllocate(): bool
    {
        return Schema::hasTable('composition_allocations')
            && Schema::hasTable('composition_royalty_lines')
            && Schema::hasTable('composition_split_sets')
            && Schema::hasTable('composition_split_participants');
    }

    /**
     * @param Collection<int, CompositionSplitSet> $sets
     */
    private function resolveSplitSetForDate(Collection $sets, ?Carbon $referenceDate): ?CompositionSplitSet
    {
        if ($sets->isEmpty()) {
            return null;
        }

        if ($referenceDate) {
            $matching = $sets->filter(function (CompositionSplitSet $set) use ($referenceDate) {
                $from = $set->effective_from ? Carbon::parse($set->effective_from)->startOfDay() : null;
                $to = $set->effective_to ? Carbon::parse($set->effective_to)->startOfDay() : null;

                $startsOk = !$from || $from->lte($referenceDate);
                $endsOk = !$to || $to->gte($referenceDate);

                return $startsOk && $endsOk;
            });

            if ($matching->isNotEmpty()) {
                return $matching->first();
            }
        }

        $active = $sets->first(fn(CompositionSplitSet $set) => $set->status === 'active');
        if ($active) {
            return $active;
        }

        return $sets->first();
    }

    /**
     * @param Collection<int, CompositionSplitSet> $sets
     * @return Collection<int, CompositionSplitSet>
     */
    private function sortSplitSetsByPriority(Collection $sets): Collection
    {
        return $sets->sort(function (CompositionSplitSet $a, CompositionSplitSet $b) {
            $fromA = $a->effective_from ? Carbon::parse($a->effective_from)->startOfDay()->timestamp : PHP_INT_MIN;
            $fromB = $b->effective_from ? Carbon::parse($b->effective_from)->startOfDay()->timestamp : PHP_INT_MIN;
            if ($fromA !== $fromB) {
                return $fromB <=> $fromA;
            }

            $createdA = $a->created_at?->timestamp ?? 0;
            $createdB = $b->created_at?->timestamp ?? 0;

            return $createdB <=> $createdA;
        });
    }

    private function resolveReferenceDate(
        ?string $activityMonthDate,
        ?string $reportingMonthDate,
        ?string $reportingPeriod
    ): ?Carbon {
        if (!empty($activityMonthDate)) {
            try {
                return Carbon::parse($activityMonthDate)->startOfDay();
            } catch (\Throwable) {
                // ignore
            }
        }

        if (!empty($reportingMonthDate)) {
            try {
                return Carbon::parse($reportingMonthDate)->startOfDay();
            } catch (\Throwable) {
                // ignore
            }
        }

        if (!empty($reportingPeriod)) {
            try {
                return Carbon::parse($reportingPeriod)->startOfMonth();
            } catch (\Throwable) {
                // ignore
            }
        }

        return null;
    }

    /**
     * @param object $participant
     * @param string|\DateTimeInterface|null $now
     * @return array<string, mixed>
     */
    private function buildAllocationRow(
        int $statementId,
        int $lineId,
        int $compositionId,
        int $splitSetId,
        object $participant,
        string $sharePool,
        string $lineType,
        ?string $activityMonthDate,
        float $splitPercentage,
        float $grossAmountUsd,
        float $allocatedAmountUsd,
        $now
    ): array {
        return [
            'composition_royalty_statement_id' => $statementId,
            'composition_royalty_line_id' => $lineId,
            'composition_id' => $compositionId,
            'composition_split_set_id' => $splitSetId,
            'composition_split_participant_id' => $participant->id,
            'share_pool' => $sharePool,
            'line_type' => $lineType,
            'party_user_id' => $participant->user_id,
            'party_artist_id' => $participant->artist_id,
            'party_email' => $participant->payee_email,
            'role' => $participant->role,
            'activity_month_date' => $activityMonthDate,
            'split_percentage' => number_format($splitPercentage, 4, '.', ''),
            'gross_amount_usd' => number_format($grossAmountUsd, 6, '.', ''),
            'allocated_amount_usd' => number_format($allocatedAmountUsd, 6, '.', ''),
            'currency' => 'USD',
            'status' => 'accrued',
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}

