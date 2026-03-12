<?php

use App\Jobs\ProcessRoyaltyStatementJob;
use App\Models\Artist;
use App\Models\Release;
use App\Models\RoyaltyAllocation;
use App\Models\RoyaltyStatement;
use App\Models\RoyaltyStatementLine;
use App\Models\Track;
use App\Models\TrackSplitAgreement;
use App\Models\TrackSplitParticipant;
use App\Models\User;
use App\Jobs\PurgeSoftDeletedRecordsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('trash:purge-soft-deletes {--days=} {--batch=}', function () {
    $days = (int) ($this->option('days') ?: config('trash.purge_after_days', 60));
    $batch = (int) ($this->option('batch') ?: config('trash.batch_size', 200));

    $days = max(1, $days);
    $batch = max(1, $batch);

    PurgeSoftDeletedRecordsJob::dispatchSync($days, $batch);

    $this->info("Purge ejecutado correctamente para registros con más de {$days} días en papelera.");
})->purpose('Elimina permanentemente registros soft-deleted con retención vencida.');

Schedule::command('trash:purge-soft-deletes')
    ->dailyAt((string) config('trash.schedule_time', '03:00'))
    ->timezone((string) config('trash.schedule_timezone', config('app.timezone', 'UTC')))
    ->withoutOverlapping();

Artisan::command('royalties:verify-symphonic-flow {--provider=symphonic : Proveedor a verificar (symphonic|sonosuite)} {--keep : Conserva los datos generados para inspeccion manual}', function () {
    $requiredTables = [
        'users',
        'artists',
        'releases',
        'tracks',
        'royalty_statements',
        'royalty_statement_lines',
        'track_split_agreements',
        'track_split_participants',
        'royalty_allocations',
    ];

    foreach ($requiredTables as $tableName) {
        if (!Schema::hasTable($tableName)) {
            $this->error("Falta la tabla requerida: {$tableName}");
            return 1;
        }
    }

    $stamp = now()->format('YmdHis');
    $uuid = Str::lower((string) Str::uuid());
    $suffix = "{$stamp}_{$uuid}";

    $email = "h1.verify.{$suffix}@dilorecords.test";
    $artistName = "H1 Verify Artist {$suffix}";
    $releaseTitle = "H1 Verify Release {$suffix}";
    $trackTitle = "H1 Verify Track {$suffix}";
    $isrc = 'CO'.strtoupper(substr(md5($suffix), 0, 10));
    $upc = substr(preg_replace('/\D/', '', (string) (time() . random_int(100000, 999999))), 0, 12);

    $provider = strtolower(trim((string) $this->option('provider')));
    if (!in_array($provider, ['symphonic', 'sonosuite'], true)) {
        $this->error("Proveedor no soportado para verificación: {$provider}");
        return 1;
    }

    $reportingPeriod = 'SEP-25';
    $label = 'Dilo Records';

    if ($provider === 'sonosuite') {
        $csv = implode("\n", [
            'id,start_date,end_date,confirmation_report_date,country,currency,type,units,unit_price,gross_total,other_costs,channel_costs,taxes,net_total,currency_rate,gross_total_client_currency,other_costs_client_currency,channel_costs_client_currency,net_total_client_currency,user_email,channel,label,artist,release,upc,track_title,isrc',
            "1001,2025-08-01,2025-08-31,2025-09-15,Colombia,USD,streaming,10,0.00,15.00,1.00,1.00,0.66,12.34,1,15.00,1.00,1.00,12.34,ops@dilo.test,Spotify,\"{$label}\",\"{$artistName}\",\"{$releaseTitle}\",\"{$upc}\",\"{$trackTitle}\",\"{$isrc}\"",
            "1002,2025-08-01,2025-08-31,2025-09-15,Colombia,USD,streaming,5,0.00,0.00,0.00,0.00,0.00,-1.00,1,0.00,0.00,0.00,-1.00,ops@dilo.test,Spotify,\"{$label}\",\"{$artistName}\",\"{$releaseTitle}\",\"{$upc}\",\"Unmatched Track {$suffix}\",\"ZZUNMATCHED000\"",
        ]) . "\n";
    } else {
        $csv = implode("\n", [
            'Reporting Period,Label,Release Name,UPC Code,Track Title,ISRC Code,Digital Service Provider,Activity Period,Territory,Count,Royalty ($US)',
            "\"{$reportingPeriod}\",\"{$label}\",\"{$releaseTitle}\",\"{$upc}\",\"{$trackTitle}\",\"{$isrc}\",\"Spotify\",\"August 2025\",\"Colombia\",10,12.34",
            "\"{$reportingPeriod}\",\"{$label}\",\"{$releaseTitle}\",\"{$upc}\",\"Unmatched Track {$suffix}\",\"ZZUNMATCHED000\",\"Spotify\",\"August 2025\",\"Colombia\",5,-1.00",
        ]) . "\n";
    }

    $normalizedCsv = str_replace(["\r\n", "\r"], "\n", $csv);
    $fileHash = hash('sha256', $normalizedCsv);
    $storedPath = "royalties/h1-verification/statement_{$suffix}.csv";

    $user = null;
    $artist = null;
    $release = null;
    $track = null;
    $agreement = null;
    $statement = null;
    $createdEntityIds = [];
    $keepData = (bool) $this->option('keep');

    try {
        Storage::disk('royalties_private')->put($storedPath, $csv);

        $user = User::query()->create([
            'name' => 'H1 Verifier',
            'email' => $email,
            'password' => 'password',
        ]);
        $createdEntityIds['user_id'] = $user->id;

        $artist = Artist::query()->create([
            'name' => $artistName,
            'slug' => Str::slug($artistName) . '-' . substr($uuid, 0, 8),
            'user_id' => $user->id,
        ]);
        $createdEntityIds['artist_id'] = $artist->id;

        $release = Release::query()->create([
            'artist_id' => $artist->id,
            'title' => $releaseTitle,
            'slug' => Str::slug($releaseTitle) . '-' . substr($uuid, 0, 8),
            'upc' => $upc,
            'type' => 'single',
        ]);
        $createdEntityIds['release_id'] = $release->id;

        $track = Track::query()->create([
            'release_id' => $release->id,
            'title' => $trackTitle,
            'isrc' => $isrc,
        ]);
        $createdEntityIds['track_id'] = $track->id;

        $agreement = TrackSplitAgreement::query()->create([
            'track_id' => $track->id,
            'split_type' => 'master',
            'status' => 'active',
            'effective_from' => '2025-01-01',
            'effective_to' => null,
            'contract_path' => "contracts/h1_verify_{$suffix}.pdf",
            'contract_original_filename' => "h1_verify_{$suffix}.pdf",
            'contract_hash' => hash('sha256', "h1_verify_{$suffix}"),
            'created_by' => $user->id,
        ]);
        $createdEntityIds['track_split_agreement_id'] = $agreement->id;

        $participantArtist = TrackSplitParticipant::query()->create([
            'track_split_agreement_id' => $agreement->id,
            'artist_id' => $artist->id,
            'user_id' => $user->id,
            'payee_email' => $email,
            'name' => $artistName,
            'role' => 'artist',
            'percentage' => 70,
        ]);
        $participantLabel = TrackSplitParticipant::query()->create([
            'track_split_agreement_id' => $agreement->id,
            'artist_id' => null,
            'user_id' => null,
            'payee_email' => 'label@dilorecords.test',
            'name' => 'Dilo Records',
            'role' => 'label',
            'percentage' => 30,
        ]);
        $createdEntityIds['track_split_participant_ids'] = [$participantArtist->id, $participantLabel->id];

        $statement = RoyaltyStatement::query()->create([
            'provider' => $provider,
            'label' => null,
            'reporting_period' => null,
            'reporting_month_date' => null,
            'currency' => 'USD',
            'original_filename' => "statement_{$suffix}.csv",
            'stored_path' => $storedPath,
            'file_hash' => $fileHash,
            'statement_key' => null,
            'version' => 1,
            'is_current' => true,
            'status' => 'uploaded',
            'total_units' => 0,
            'total_net_usd' => 0,
            'created_by' => $user->id,
        ]);
        $createdEntityIds['statement_id'] = $statement->id;

        ProcessRoyaltyStatementJob::dispatchSync($statement->id);
        $statement->refresh();

        $lineCount = RoyaltyStatementLine::query()
            ->where('royalty_statement_id', $statement->id)
            ->count();
        $matchedCount = RoyaltyStatementLine::query()
            ->where('royalty_statement_id', $statement->id)
            ->whereNotNull('track_id')
            ->count();
        $unmatchedCount = RoyaltyStatementLine::query()
            ->where('royalty_statement_id', $statement->id)
            ->whereNull('track_id')
            ->count();
        $allocationCount = RoyaltyAllocation::query()
            ->where('royalty_statement_id', $statement->id)
            ->count();
        $allocationSum = (float) RoyaltyAllocation::query()
            ->where('royalty_statement_id', $statement->id)
            ->sum('allocated_amount_usd');
        $matchedGrossSum = (float) RoyaltyStatementLine::query()
            ->where('royalty_statement_id', $statement->id)
            ->whereNotNull('track_id')
            ->sum('net_total_usd');
        $matchedStatusCount = Schema::hasColumn('royalty_statement_lines', 'match_status')
            ? RoyaltyStatementLine::query()
                ->where('royalty_statement_id', $statement->id)
                ->where('match_status', 'matched')
                ->count()
            : 0;
        $unmatchedStatusCount = Schema::hasColumn('royalty_statement_lines', 'match_status')
            ? RoyaltyStatementLine::query()
                ->where('royalty_statement_id', $statement->id)
                ->where('match_status', 'unmatched')
                ->count()
            : 0;
        $firstMatchedLine = RoyaltyStatementLine::query()
            ->where('royalty_statement_id', $statement->id)
            ->whereNotNull('track_id')
            ->orderBy('id')
            ->first();

        $hasCanonicalPayload = false;
        $canonicalAmountMatches = false;
        $sourceProviderMatches = false;
        $hasMonetaryTrace = false;
        if ($firstMatchedLine) {
            $rawPayload = is_array($firstMatchedLine->raw) ? $firstMatchedLine->raw : [];
            $hasCanonicalPayload = isset($rawPayload['source'], $rawPayload['canonical'], $rawPayload['raw']);

            $canonicalAmount = (float) data_get($rawPayload, 'canonical.amount_usd', 0);
            $lineAmount = (float) $firstMatchedLine->net_total_usd;
            $canonicalAmountMatches = abs($canonicalAmount - $lineAmount) < 0.000001;
            $canonicalSourceMatches = data_get($rawPayload, 'canonical.source_name') === $provider;
            $sourceProviderMatches = data_get($rawPayload, 'source.provider') === $provider;
            $hasMonetaryTrace = data_get($rawPayload, 'canonical.amount_original') !== null
                && data_get($rawPayload, 'canonical.currency_original') !== null
                && data_get($rawPayload, 'canonical.fx_rate_to_usd') !== null
                && data_get($rawPayload, 'canonical.amount_usd') !== null;
        }

        $checks = [
            'statement_status_processed' => $statement->status === 'processed',
            'lines_total_2' => $lineCount === 2,
            'matched_1_unmatched_1' => $matchedCount === 1 && $unmatchedCount === 1,
            'statement_totals' => (int) $statement->total_units === 15
                && abs((float) $statement->total_net_usd - 11.34) < 0.000001,
            'allocations_created_2' => $allocationCount === 2,
            'allocation_sum_equals_matched_gross' => abs($allocationSum - $matchedGrossSum) < 0.000001,
            'statement_key_generated' => !empty($statement->statement_key),
            'statement_current_not_reference' => (bool) $statement->is_current === true
                && (bool) $statement->is_reference_only === false,
            'statement_provider_matches_expected' => $statement->provider === $provider,
            'raw_contains_source_and_canonical_payload' => $hasCanonicalPayload,
            'canonical_amount_matches_persisted_line_amount' => $canonicalAmountMatches,
            'canonical_source_matches_provider' => $canonicalSourceMatches ?? false,
            'source_provider_matches_provider' => $sourceProviderMatches,
            'canonical_has_monetary_trace' => $hasMonetaryTrace,
            'line_match_statuses_are_consistent' => !Schema::hasColumn('royalty_statement_lines', 'match_status')
                || ($matchedStatusCount === 1 && $unmatchedStatusCount === 1),
        ];

        $failed = collect($checks)->filter(fn($passed) => !$passed)->keys()->values();

        $this->newLine();
        $this->info('Verificacion de flujo master ejecutada.');
        $this->line("Statement ID: {$statement->id}");
        $this->line("Status: {$statement->status}");
        $this->line("Lineas: {$lineCount} | Matched: {$matchedCount} | Unmatched: {$unmatchedCount}");
        $this->line("Allocations: {$allocationCount} | Allocation sum USD: " . number_format($allocationSum, 6, '.', ''));
        $this->line("Statement total units: {$statement->total_units} | total net USD: " . number_format((float) $statement->total_net_usd, 6, '.', ''));
        $this->newLine();

        foreach ($checks as $name => $passed) {
            $this->line(($passed ? '[OK] ' : '[FAIL] ') . $name);
        }

        if ($failed->isNotEmpty()) {
            $this->newLine();
            $this->error('La verificacion fallo en: ' . $failed->implode(', '));
            return 1;
        }

        return 0;
    } finally {
        if (!$keepData) {
            if (isset($createdEntityIds['statement_id'])) {
                RoyaltyAllocation::query()
                    ->where('royalty_statement_id', $createdEntityIds['statement_id'])
                    ->delete();

                RoyaltyStatementLine::withTrashed()
                    ->where('royalty_statement_id', $createdEntityIds['statement_id'])
                    ->forceDelete();

                RoyaltyStatement::withTrashed()
                    ->whereKey($createdEntityIds['statement_id'])
                    ->forceDelete();
            }

            if (!empty($createdEntityIds['track_split_participant_ids'])) {
                TrackSplitParticipant::withTrashed()
                    ->whereIn('id', $createdEntityIds['track_split_participant_ids'])
                    ->forceDelete();
            }

            if (isset($createdEntityIds['track_split_agreement_id'])) {
                TrackSplitAgreement::withTrashed()
                    ->whereKey($createdEntityIds['track_split_agreement_id'])
                    ->forceDelete();
            }

            if (isset($createdEntityIds['track_id'])) {
                Track::withTrashed()
                    ->whereKey($createdEntityIds['track_id'])
                    ->forceDelete();
            }

            if (isset($createdEntityIds['release_id'])) {
                Release::withTrashed()
                    ->whereKey($createdEntityIds['release_id'])
                    ->forceDelete();
            }

            if (isset($createdEntityIds['artist_id'])) {
                Artist::withTrashed()
                    ->whereKey($createdEntityIds['artist_id'])
                    ->forceDelete();
            }

            if (isset($createdEntityIds['user_id'])) {
                User::withTrashed()
                    ->whereKey($createdEntityIds['user_id'])
                    ->forceDelete();
            }

            $disk = Storage::disk('royalties_private');
            if ($disk->exists($storedPath)) {
                $disk->delete($storedPath);
            }
        } else {
            $this->warn('Se conservaron datos de verificacion por --keep.');
        }
    }
})->purpose('Verifica de forma reproducible el flujo master actual (Symphonic/Sonosuite) (CSV -> lines -> allocations).');
