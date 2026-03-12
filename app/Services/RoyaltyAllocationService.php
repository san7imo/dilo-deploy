<?php

namespace App\Services;

use App\Models\CompositionSplitAgreement;
use App\Models\CompositionSplitSet;
use App\Models\RoyaltyAllocationRecalculation;
use App\Models\RoyaltyStatement;
use App\Models\RoyaltyStatementLine;
use App\Models\TrackSplitAgreement;
use App\Services\Royalties\SplitAllocationCalculator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RoyaltyAllocationService
{
    public function __construct(
        private readonly SplitAllocationCalculator $splitCalculator
    ) {
    }

    public function rebuildForStatement(RoyaltyStatement $statement, array $context = []): void
    {
        $stats = $this->buildInitialStats($statement);
        $warnings = [];

        if (!Schema::hasTable('royalty_allocations')) {
            $warnings[] = 'Tabla royalty_allocations no disponible; recálculo omitido.';
            $this->recordRecalculation($statement, $context, $stats, $warnings);
            return;
        }

        if ($statement->is_reference_only) {
            DB::table('royalty_allocations')
                ->where('royalty_statement_id', $statement->id)
                ->delete();

            $warnings[] = 'Statement en modo reference_only; allocations eliminadas.';
            $this->recordRecalculation($statement, $context, $stats, $warnings);
            return;
        }

        $trackIds = RoyaltyStatementLine::query()
            ->where('royalty_statement_id', $statement->id)
            ->whereNotNull('track_id')
            ->distinct()
            ->pluck('track_id');

        DB::table('royalty_allocations')
            ->where('royalty_statement_id', $statement->id)
            ->delete();

        if ($trackIds->isEmpty()) {
            $warnings[] = 'No hay líneas matched para recalcular.';
            $this->recordRecalculation($statement, $context, $stats, $warnings);
            return;
        }

        $canAllocateComposition = $this->canAllocateComposition();
        $hasCompositionSplitSetColumn = Schema::hasColumn('royalty_allocations', 'composition_split_set_id');

        $agreementsByTrack = TrackSplitAgreement::query()
            ->whereIn('track_id', $trackIds)
            ->where('split_type', 'master')
            ->with(['participants:id,track_split_agreement_id,artist_id,user_id,payee_email,role,percentage'])
            ->get(['id', 'track_id', 'status', 'effective_from', 'effective_to', 'created_at'])
            ->groupBy('track_id')
            ->map(fn(Collection $agreements) => $this->sortAgreementsByPriority($agreements)->values());

        $compositionContextsByTrack = $canAllocateComposition
            ? $this->buildCompositionContextsByTrack($trackIds)
            : collect();

        RoyaltyStatementLine::query()
            ->where('royalty_statement_id', $statement->id)
            ->whereNotNull('track_id')
            ->orderBy('id')
            ->chunkById(1000, function ($lines) use ($statement, $agreementsByTrack, $compositionContextsByTrack, $canAllocateComposition, $hasCompositionSplitSetColumn, &$stats, &$warnings): void {
                $masterRows = [];
                $compositionRows = [];
                $now = now();

                foreach ($lines as $line) {
                    /** @var Collection<int, TrackSplitAgreement> $agreements */
                    $agreements = $agreementsByTrack->get($line->track_id, collect());
                    if ($agreements->isEmpty()) {
                        continue;
                    }

                    $referenceDate = $this->resolveReferenceDate(
                        $line->activity_month_date?->toDateString(),
                        $statement->reporting_month_date?->toDateString(),
                        $statement->reporting_period
                    );

                    $agreement = $this->resolveAgreementForDate($agreements, $referenceDate);
                    if (!$agreement) {
                        continue;
                    }

                    $participants = $agreement->participants
                        ->filter(fn($participant) => (float) $participant->percentage > 0)
                        ->values();

                    if ($participants->isEmpty()) {
                        continue;
                    }

                    $gross = (float) $line->net_total_usd;
                    $masterCalculation = $this->splitCalculator->allocate($gross, $participants, 'percentage');
                    if (!$masterCalculation['valid']) {
                        $warnings = $this->appendWarning($warnings, 'master_split_invalid_for_line', [
                            'line_id' => (int) $line->id,
                            'track_id' => (int) $line->track_id,
                            'gross_amount_usd' => $gross,
                            'total_percentage' => $masterCalculation['total_percentage'],
                            'difference' => $masterCalculation['difference'],
                        ]);
                        continue;
                    }

                    foreach ($masterCalculation['amounts'] as $item) {
                        $participant = $item['participant'];
                        $percentage = (float) $item['percentage'];
                        $allocated = (float) $item['allocated'];

                        $masterRow = [
                            'royalty_statement_id' => $statement->id,
                            'royalty_statement_line_id' => $line->id,
                            'track_id' => $line->track_id,
                            'right_type' => 'master',
                            'composition_id' => null,
                            'track_split_agreement_id' => $agreement->id,
                            'composition_split_agreement_id' => null,
                            'track_split_participant_id' => $participant->id,
                            'composition_split_participant_id' => null,
                            'party_user_id' => $participant->user_id,
                            'party_artist_id' => $participant->artist_id,
                            'party_email' => $participant->payee_email,
                            'role' => $participant->role,
                            'activity_month_date' => $line->activity_month_date?->toDateString(),
                            'split_percentage' => $this->formatDecimal($percentage, 4),
                            'gross_amount_usd' => $this->formatDecimal($gross, 6),
                            'allocated_amount_usd' => $this->formatDecimal($allocated, 6),
                            'currency' => 'USD',
                            'status' => 'accrued',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];

                        if ($hasCompositionSplitSetColumn) {
                            $masterRow['composition_split_set_id'] = null;
                        }

                        $masterRows[] = $masterRow;
                    }

                    if (!$canAllocateComposition) {
                        continue;
                    }

                    $compositionContexts = $compositionContextsByTrack->get($line->track_id, collect());
                    if ($compositionContexts->isEmpty()) {
                        continue;
                    }

                    $compositionGross = round($gross / $compositionContexts->count(), 6);

                    foreach ($compositionContexts as $context) {
                        $compositionAgreements = $context['agreements'] ?? collect();
                        if ($compositionAgreements->isEmpty()) {
                            continue;
                        }

                        $compositionAgreement = $this->resolveCompositionAgreementForDate($compositionAgreements, $referenceDate);
                        if (!$compositionAgreement) {
                            continue;
                        }

                        $compositionParticipants = $compositionAgreement->participants
                            ->filter(fn($participant) => (float) $participant->percentage > 0)
                            ->values();

                        if (Schema::hasColumn('composition_split_participants', 'share_pool')) {
                            $pooledParticipants = $compositionParticipants
                                ->filter(fn($participant) => ($participant->share_pool ?? 'writer') === 'writer')
                                ->values();

                            if ($pooledParticipants->isNotEmpty()) {
                                $compositionParticipants = $pooledParticipants;
                            }
                        }

                        if ($compositionParticipants->isEmpty()) {
                            continue;
                        }

                        $compositionCalculation = $this->splitCalculator->allocate(
                            $compositionGross,
                            $compositionParticipants,
                            'percentage'
                        );
                        if (!$compositionCalculation['valid']) {
                            $warnings = $this->appendWarning($warnings, 'composition_split_invalid_for_line', [
                                'line_id' => (int) $line->id,
                                'track_id' => (int) $line->track_id,
                                'composition_id' => (int) ($context['composition_id'] ?? 0),
                                'gross_amount_usd' => $compositionGross,
                                'total_percentage' => $compositionCalculation['total_percentage'],
                                'difference' => $compositionCalculation['difference'],
                            ]);
                            continue;
                        }

                        foreach ($compositionCalculation['amounts'] as $item) {
                            $compositionParticipant = $item['participant'];
                            $percentage = (float) $item['percentage'];
                            $allocated = (float) $item['allocated'];

                            $compositionRow = [
                                'royalty_statement_id' => $statement->id,
                                'royalty_statement_line_id' => $line->id,
                                'track_id' => $line->track_id,
                                'right_type' => 'composition',
                                'composition_id' => $context['composition_id'] ?? null,
                                'track_split_agreement_id' => null,
                                'composition_split_agreement_id' => $compositionAgreement->id,
                                'track_split_participant_id' => null,
                                'composition_split_participant_id' => $compositionParticipant->id,
                                'party_user_id' => $compositionParticipant->user_id,
                                'party_artist_id' => $compositionParticipant->artist_id,
                                'party_email' => $compositionParticipant->payee_email,
                                'role' => $compositionParticipant->role,
                                'activity_month_date' => $line->activity_month_date?->toDateString(),
                                'split_percentage' => $this->formatDecimal($percentage, 4),
                                'gross_amount_usd' => $this->formatDecimal($compositionGross, 6),
                                'allocated_amount_usd' => $this->formatDecimal($allocated, 6),
                                'currency' => 'USD',
                                'status' => 'accrued',
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];

                            if ($hasCompositionSplitSetColumn) {
                                $compositionRow['composition_split_set_id'] = $compositionParticipant->composition_split_set_id ?? null;
                            }

                            $compositionRows[] = $compositionRow;
                        }
                    }
                }

                if (!empty($masterRows)) {
                    $stats['master_allocations_count'] += count($masterRows);
                    $masterUpdateColumns = [
                        'royalty_statement_id',
                        'track_id',
                        'right_type',
                        'composition_id',
                        'track_split_agreement_id',
                        'composition_split_agreement_id',
                        'party_user_id',
                        'party_artist_id',
                        'party_email',
                        'role',
                        'activity_month_date',
                        'split_percentage',
                        'gross_amount_usd',
                        'allocated_amount_usd',
                        'currency',
                        'status',
                        'updated_at',
                    ];

                    if ($hasCompositionSplitSetColumn) {
                        $masterUpdateColumns[] = 'composition_split_set_id';
                    }

                    DB::table('royalty_allocations')->upsert(
                        $masterRows,
                        ['royalty_statement_line_id', 'track_split_participant_id'],
                        $masterUpdateColumns
                    );
                }

                if (!empty($compositionRows)) {
                    $stats['composition_allocations_count'] += count($compositionRows);
                    $compositionUpdateColumns = [
                        'royalty_statement_id',
                        'track_id',
                        'right_type',
                        'composition_id',
                        'composition_split_agreement_id',
                        'party_user_id',
                        'party_artist_id',
                        'party_email',
                        'role',
                        'activity_month_date',
                        'split_percentage',
                        'gross_amount_usd',
                        'allocated_amount_usd',
                        'currency',
                        'status',
                        'updated_at',
                    ];

                    if ($hasCompositionSplitSetColumn) {
                        $compositionUpdateColumns[] = 'composition_split_set_id';
                    }

                    DB::table('royalty_allocations')->upsert(
                        $compositionRows,
                        ['royalty_statement_line_id', 'composition_split_participant_id'],
                        $compositionUpdateColumns
                    );
                }
            });

        $this->recordRecalculation($statement, $context, $stats, $warnings);
    }

    private function canAllocateComposition(): bool
    {
        $recordingCompositionTableAvailable = Schema::hasTable('recording_compositions')
            || Schema::hasTable('composition_track');

        $splitSetsAvailable = Schema::hasTable('composition_split_sets')
            && Schema::hasColumn('composition_split_participants', 'composition_split_set_id');

        $legacyAgreementsAvailable = Schema::hasTable('composition_split_agreements');

        return Schema::hasTable('compositions')
            && $recordingCompositionTableAvailable
            && ($splitSetsAvailable || $legacyAgreementsAvailable)
            && Schema::hasTable('composition_split_participants')
            && Schema::hasColumn('royalty_allocations', 'composition_id')
            && Schema::hasColumn('royalty_allocations', 'right_type')
            && Schema::hasColumn('royalty_allocations', 'composition_split_agreement_id')
            && Schema::hasColumn('royalty_allocations', 'composition_split_participant_id');
    }

    private function buildCompositionContextsByTrack(Collection $trackIds): Collection
    {
        $pivotTable = Schema::hasTable('recording_compositions')
            ? 'recording_compositions'
            : 'composition_track';

        $links = DB::table($pivotTable)
            ->select('track_id', 'composition_id')
            ->whereIn('track_id', $trackIds)
            ->get();

        if ($links->isEmpty()) {
            return collect();
        }

        $compositionIds = $links->pluck('composition_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($compositionIds->isEmpty()) {
            return collect();
        }

        if (Schema::hasTable('composition_split_sets')) {
            $agreementsByComposition = CompositionSplitSet::query()
                ->whereIn('composition_id', $compositionIds)
                ->with(['participants:id,composition_split_set_id,composition_split_agreement_id,artist_id,user_id,payee_email,role,share_pool,percentage'])
                ->get(['id', 'composition_id', 'status', 'effective_from', 'effective_to', 'created_at'])
                ->groupBy('composition_id')
                ->map(fn(Collection $agreements) => $this->sortCompositionAgreementsByPriority($agreements)->values());
        } else {
            $agreementsByComposition = CompositionSplitAgreement::query()
                ->whereIn('composition_id', $compositionIds)
                ->with(['participants:id,composition_split_agreement_id,artist_id,user_id,payee_email,role,percentage'])
                ->get(['id', 'composition_id', 'status', 'effective_from', 'effective_to', 'created_at'])
                ->groupBy('composition_id')
                ->map(fn(Collection $agreements) => $this->sortCompositionAgreementsByPriority($agreements)->values());
        }

        return $links
            ->groupBy('track_id')
            ->map(function (Collection $items) use ($agreementsByComposition) {
                return $items->map(function ($item) use ($agreementsByComposition) {
                    $compositionId = (int) $item->composition_id;

                    return [
                        'composition_id' => $compositionId,
                        'agreements' => $agreementsByComposition->get($compositionId, collect()),
                    ];
                })->values();
            });
    }

    private function resolveAgreementForDate(Collection $agreements, ?Carbon $referenceDate): ?TrackSplitAgreement
    {
        if ($agreements->isEmpty()) {
            return null;
        }

        if ($referenceDate) {
            $matching = $agreements->filter(function (TrackSplitAgreement $agreement) use ($referenceDate) {
                $from = $agreement->effective_from ? Carbon::parse($agreement->effective_from)->startOfDay() : null;
                $to = $agreement->effective_to ? Carbon::parse($agreement->effective_to)->startOfDay() : null;

                $startsOk = !$from || $from->lte($referenceDate);
                $endsOk = !$to || $to->gte($referenceDate);

                return $startsOk && $endsOk;
            });

            if ($matching->isNotEmpty()) {
                return $matching->first();
            }
        }

        $active = $agreements->first(fn(TrackSplitAgreement $agreement) => $agreement->status === 'active');
        if ($active) {
            return $active;
        }

        return $agreements->first();
    }

    private function resolveCompositionAgreementForDate(Collection $agreements, ?Carbon $referenceDate): ?object
    {
        if ($agreements->isEmpty()) {
            return null;
        }

        if ($referenceDate) {
            $matching = $agreements->filter(function ($agreement) use ($referenceDate) {
                $from = $agreement->effective_from ? Carbon::parse($agreement->effective_from)->startOfDay() : null;
                $to = $agreement->effective_to ? Carbon::parse($agreement->effective_to)->startOfDay() : null;

                $startsOk = !$from || $from->lte($referenceDate);
                $endsOk = !$to || $to->gte($referenceDate);

                return $startsOk && $endsOk;
            });

            if ($matching->isNotEmpty()) {
                return $matching->first();
            }
        }

        $active = $agreements->first(fn($agreement) => $agreement->status === 'active');
        if ($active) {
            return $active;
        }

        return $agreements->first();
    }

    private function sortAgreementsByPriority(Collection $agreements): Collection
    {
        return $agreements->sort(function (TrackSplitAgreement $a, TrackSplitAgreement $b) {
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

    private function sortCompositionAgreementsByPriority(Collection $agreements): Collection
    {
        return $agreements->sort(function ($a, $b) {
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
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if (!empty($reportingMonthDate)) {
            try {
                return Carbon::parse($reportingMonthDate)->startOfDay();
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if (!empty($reportingPeriod)) {
            return $this->parseReportingPeriod($reportingPeriod);
        }

        return null;
    }

    private function parseReportingPeriod(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(?<mon>[A-Za-z]{3})[-\\s](?<year>\\d{2,4})$/', $value, $matches)) {
            $month = ucfirst(strtolower($matches['mon']));
            $year = $matches['year'];
            if (strlen($year) === 2) {
                $year = '20' . $year;
            }

            try {
                return Carbon::parse($month . ' ' . $year)->startOfMonth();
            } catch (\Throwable $e) {
                return null;
            }
        }

        if (preg_match('/^(?<year>\\d{4})[-\\/](?<month>\\d{1,2})$/', $value, $matches)) {
            try {
                return Carbon::createFromDate((int) $matches['year'], (int) $matches['month'], 1)->startOfMonth();
            } catch (\Throwable $e) {
                return null;
            }
        }

        try {
            return Carbon::parse($value)->startOfMonth();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function formatDecimal(float $value, int $decimals): string
    {
        return number_format($value, $decimals, '.', '');
    }

    private function buildInitialStats(RoyaltyStatement $statement): array
    {
        return [
            'lines_total' => (int) RoyaltyStatementLine::query()
                ->where('royalty_statement_id', $statement->id)
                ->count(),
            'lines_matched' => (int) RoyaltyStatementLine::query()
                ->where('royalty_statement_id', $statement->id)
                ->whereNotNull('track_id')
                ->count(),
            'master_allocations_count' => 0,
            'composition_allocations_count' => 0,
        ];
    }

    private function recordRecalculation(
        RoyaltyStatement $statement,
        array $context,
        array $stats,
        array $warnings
    ): void {
        if (!Schema::hasTable('royalty_allocation_recalculations')) {
            return;
        }

        RoyaltyAllocationRecalculation::query()->create([
            'royalty_statement_id' => $statement->id,
            'triggered_by_user_id' => $context['triggered_by_user_id'] ?? null,
            'trigger_source' => (string) ($context['trigger_source'] ?? 'system'),
            'reason' => $context['reason'] ?? null,
            'lines_total' => (int) ($stats['lines_total'] ?? 0),
            'lines_matched' => (int) ($stats['lines_matched'] ?? 0),
            'master_allocations_count' => (int) ($stats['master_allocations_count'] ?? 0),
            'composition_allocations_count' => (int) ($stats['composition_allocations_count'] ?? 0),
            'warnings' => empty($warnings) ? null : $warnings,
            'context' => $context['context'] ?? null,
        ]);

        Log::info('Royalty allocations recalculated', [
            'statement_id' => $statement->id,
            'trigger_source' => (string) ($context['trigger_source'] ?? 'system'),
            'reason' => $context['reason'] ?? null,
            'triggered_by_user_id' => $context['triggered_by_user_id'] ?? null,
            'lines_total' => (int) ($stats['lines_total'] ?? 0),
            'lines_matched' => (int) ($stats['lines_matched'] ?? 0),
            'master_allocations_count' => (int) ($stats['master_allocations_count'] ?? 0),
            'composition_allocations_count' => (int) ($stats['composition_allocations_count'] ?? 0),
            'warnings_count' => count($warnings),
        ]);
    }

    private function appendWarning(array $warnings, string $code, array $payload): array
    {
        if (count($warnings) >= 100) {
            return $warnings;
        }

        $warnings[] = [
            'code' => $code,
            'payload' => $payload,
        ];

        return $warnings;
    }
}
