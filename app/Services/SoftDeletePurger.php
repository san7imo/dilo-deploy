<?php

namespace App\Services;

use App\Models\Artist;
use App\Models\Collaborator;
use App\Models\Composition;
use App\Models\CompositionRoyaltyLine;
use App\Models\CompositionRoyaltyStatement;
use App\Models\CompositionSplitAgreement;
use App\Models\CompositionSplitParticipant;
use App\Models\CompositionSplitSet;
use App\Models\Event;
use App\Models\EventExpense;
use App\Models\EventPayment;
use App\Models\EventPersonalExpense;
use App\Models\Genre;
use App\Models\Release;
use App\Models\RoyaltyStatement;
use App\Models\RoyaltyStatementLine;
use App\Models\Track;
use App\Models\TrackSplitAgreement;
use App\Models\TrackSplitParticipant;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SoftDeletePurger
{
    /**
     * Orden crítico: hijos primero para minimizar conflictos de FK.
     *
     * @var array<int, class-string<Model>>
     */
    private array $purgeOrder = [
        CompositionRoyaltyLine::class,
        CompositionRoyaltyStatement::class,
        CompositionSplitParticipant::class,
        CompositionSplitSet::class,
        CompositionSplitAgreement::class,
        Composition::class,
        TrackSplitParticipant::class,
        TrackSplitAgreement::class,
        RoyaltyStatementLine::class,
        EventPayment::class,
        EventExpense::class,
        EventPersonalExpense::class,
        Track::class,
        Release::class,
        Event::class,
        RoyaltyStatement::class,
        Collaborator::class,
        Artist::class,
        Genre::class,
        User::class,
    ];

    /**
     * @return array<string, mixed>
     */
    public function purgeOlderThan(CarbonInterface $threshold, int $batchSize = 200): array
    {
        $batchSize = max(1, $batchSize);

        $summary = [
            'threshold' => $threshold->toDateTimeString(),
            'deleted' => 0,
            'failed' => 0,
            'models' => [],
        ];

        foreach ($this->purgeOrder as $modelClass) {
            $modelSummary = ['deleted' => 0, 'failed' => 0];
            $table = (new $modelClass())->getTable();

            $modelClass::query()
                ->onlyTrashed()
                ->where('deleted_at', '<=', $threshold)
                ->orderBy('id')
                ->chunkById($batchSize, function ($records) use (&$summary, &$modelSummary, $modelClass, $table): void {
                    foreach ($records as $record) {
                        try {
                            DB::transaction(function () use ($record): void {
                                $this->cleanupStoredFiles($record);
                                $record->forceDelete();
                            });

                            $summary['deleted']++;
                            $modelSummary['deleted']++;
                        } catch (Throwable $exception) {
                            $summary['failed']++;
                            $modelSummary['failed']++;

                            Log::warning('Soft-delete purge skipped record due to constraint/error.', [
                                'model' => $modelClass,
                                'table' => $table,
                                'record_id' => $record->getKey(),
                                'error' => $exception->getMessage(),
                            ]);
                        }
                    }
                });

            $summary['models'][$modelClass] = $modelSummary;
        }

        return $summary;
    }

    private function cleanupStoredFiles(Model $record): void
    {
        if ($record instanceof RoyaltyStatement && !empty($record->stored_path)) {
            $disk = Storage::disk('royalties_private');

            if ($disk->exists($record->stored_path)) {
                $disk->delete($record->stored_path);
            }
        }

        if ($record instanceof CompositionRoyaltyStatement && !empty($record->stored_path)) {
            $disk = Storage::disk('royalties_private');

            if ($disk->exists($record->stored_path)) {
                $disk->delete($record->stored_path);
            }
        }

        if ($record instanceof TrackSplitAgreement && !empty($record->contract_path)) {
            $disk = Storage::disk('contracts_private');

            if ($disk->exists($record->contract_path)) {
                $disk->delete($record->contract_path);
            }
        }

        if ($record instanceof CompositionSplitAgreement && !empty($record->contract_path)) {
            $disk = Storage::disk('contracts_private');

            if ($disk->exists($record->contract_path)) {
                $disk->delete($record->contract_path);
            }
        }

        if ($record instanceof CompositionSplitSet && !empty($record->contract_path)) {
            $disk = Storage::disk('contracts_private');

            if ($disk->exists($record->contract_path)) {
                $disk->delete($record->contract_path);
            }
        }
    }
}
