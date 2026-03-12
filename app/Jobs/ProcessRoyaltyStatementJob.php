<?php

namespace App\Jobs;

use App\Models\RoyaltyStatement;
use App\Models\RoyaltyStatementLine;
use App\Services\RoyaltyAllocationService;
use App\Services\Royalties\MasterRoyaltyDedupeService;
use App\Services\Royalties\MasterRoyaltyLineCanonicalNormalizer;
use App\Services\Royalties\MasterRoyaltyLineMatcher;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessRoyaltyStatementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $statementId;

    public function __construct(int $statementId)
    {
        $this->statementId = $statementId;
    }

    public function handle(
        RoyaltyAllocationService $allocationService,
        MasterRoyaltyLineCanonicalNormalizer $canonicalNormalizer,
        MasterRoyaltyLineMatcher $lineMatcher,
        MasterRoyaltyDedupeService $dedupeService
    ): void
    {
        $statement = RoyaltyStatement::find($this->statementId);
        if (!$statement) {
            return;
        }

        if (!in_array($statement->status, ['uploaded', 'failed'], true)) {
            return;
        }

        $this->prepareStatementForProcessing($statement);

        $disk = Storage::disk('royalties_private');
        $stream = $disk->readStream($statement->stored_path);
        if (!$stream) {
            $statement->update(['status' => 'failed']);
            throw new \RuntimeException('No se pudo abrir el archivo para procesamiento.');
        }

        $label = null;
        $reportingPeriod = null;
        $reportingMonthDate = null;
        $activityPeriodFallback = null;
        $lineCount = 0;
        $duplicateLineCount = 0;
        $batch = [];
        $lineHashIndexByBatchPosition = [];
        $seenLineHashes = [];
        $batchSize = 1000;
        try {
            [$headers, $normalizedHeaders] = $this->readHeaderRow($stream, $statement->provider);
            $this->validateRequiredColumnsForProvider($statement->provider, $normalizedHeaders);

            while (($row = fgetcsv($stream)) !== false) {
                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $row = $this->padRow($row, count($headers));
                $rawRow = array_combine($headers, $row);
                $normalizedRow = $this->normalizeRowKeys($rawRow, $normalizedHeaders);

                $lineNumber = $lineCount + 1;
                $canonical = $this->normalizeCanonicalRowByProvider(
                    $statement->provider,
                    $canonicalNormalizer,
                    $rawRow,
                    $normalizedRow,
                    $lineNumber,
                    (int) $statement->id
                );

                $label = $label ?: ($canonical['label_name'] ?? null);
                $reportingPeriod = $reportingPeriod ?: ($canonical['statement_period_raw'] ?? null);
                if (!$reportingMonthDate && !empty($canonical['statement_period_start'])) {
                    $reportingMonthDate = Carbon::parse($canonical['statement_period_start'])->startOfMonth();
                }
                if (!$reportingMonthDate && !empty($canonical['confirmation_date'])) {
                    $reportingMonthDate = Carbon::parse($canonical['confirmation_date'])->startOfMonth();
                }

                $isrc = $canonical['isrc'] ?? null;
                $upc = $canonical['upc'] ?? null;
                $trackTitle = $canonical['track_title'] ?? null;
                $channel = $canonical['dsp_name'] ?? null;
                $country = $canonical['territory_code'] ?? null;
                $activityPeriodText = $canonical['activity_period_text'] ?? null;
                $activityMonthDate = !empty($canonical['activity_month'])
                    ? Carbon::parse($canonical['activity_month'])->startOfMonth()
                    : null;
                if (!$activityPeriodFallback && $activityPeriodText) {
                    $activityPeriodFallback = $activityPeriodText;
                }
                $units = (int) ($canonical['units'] ?? 0);
                $netTotalUsd = (float) ($canonical['amount_usd'] ?? 0);

                $match = $lineMatcher->resolve($canonical);
                $trackId = $match['track_id'] ?? null;
                $matchStatus = $match['match_status'] ?? RoyaltyStatementLine::MATCH_STATUS_UNMATCHED;
                $matchMeta = $match['match_meta'] ?? [];

                $lineHash = $dedupeService->buildLineHash($normalizedRow, $canonical);
                if (isset($seenLineHashes[$lineHash])) {
                    $sourceLineId = (string) ($canonical['source_line_id'] ?? $lineNumber);
                    if (isset($lineHashIndexByBatchPosition[$lineHash])) {
                        $existingIndex = $lineHashIndexByBatchPosition[$lineHash];
                        $batch[$existingIndex] = $this->markBatchRowAsDuplicate(
                            $batch[$existingIndex],
                            $sourceLineId
                        );
                    } else {
                        $this->markPersistedLineAsDuplicate(
                            (int) $statement->id,
                            $lineHash,
                            $sourceLineId
                        );
                    }

                    $duplicateLineCount++;
                    $lineCount++;
                    continue;
                }
                $seenLineHashes[$lineHash] = true;

                $rawPayload = [
                    'source' => [
                        'provider' => $statement->provider,
                        'statement_id' => (string) $statement->id,
                        'source_line_id' => $canonical['source_line_id'] ?? (string) $lineNumber,
                        'importer_version' => MasterRoyaltyLineCanonicalNormalizer::IMPORTER_VERSION,
                    ],
                    'canonical' => $canonical,
                    'raw' => $rawRow,
                ];

                $batch[] = [
                    'royalty_statement_id' => $statement->id,
                    'track_id' => $trackId,
                    'isrc' => $isrc,
                    'upc' => $upc,
                    'track_title' => $trackTitle,
                    'channel' => $channel,
                    'country' => $country,
                    'activity_period_text' => $activityPeriodText,
                    'activity_month_date' => $activityMonthDate?->toDateString(),
                    'units' => $units,
                    'net_total_usd' => $this->formatDecimal($netTotalUsd),
                    'line_hash' => $lineHash,
                    'match_status' => $matchStatus,
                    'match_meta' => json_encode($matchMeta, JSON_UNESCAPED_UNICODE),
                    'raw' => json_encode($rawPayload, JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $lineHashIndexByBatchPosition[$lineHash] = count($batch) - 1;

                $lineCount++;

                if (count($batch) >= $batchSize) {
                    $this->upsertLines($batch);
                    $batch = [];
                    $lineHashIndexByBatchPosition = [];
                }
            }

            if (!empty($batch)) {
                $this->upsertLines($batch);
            }

            $totals = $this->calculateTotalsFromDatabase($statement->id);

            $this->finalizeStatement(
                $statement,
                $label,
                $reportingPeriod,
                $reportingMonthDate,
                $activityPeriodFallback,
                $totals['units'],
                $totals['net'],
                $dedupeService
            );

            $statement->refresh();
            if ($statement->is_reference_only) {
                RoyaltyStatementLine::query()
                    ->where('royalty_statement_id', $statement->id)
                    ->update([
                        'match_status' => RoyaltyStatementLine::MATCH_STATUS_REFERENCE_ONLY,
                        'updated_at' => now(),
                    ]);
            }

            if (!$statement->is_reference_only) {
                $allocationService->rebuildForStatement($statement, [
                    'trigger_source' => 'statement_processing_job',
                    'reason' => 'statement_processed',
                    'context' => [
                        'provider' => $statement->provider,
                        'statement_id' => $statement->id,
                    ],
                ]);
            }

            Log::info('✅ [Royalties] Statement procesado', [
                'statement_id' => $statement->id,
                'lines_read' => $lineCount,
                'lines_deduped_in_file' => $duplicateLineCount,
            ]);
        } catch (Throwable $e) {
            $statement->update(['status' => 'failed']);
            Log::error('❌ [Royalties] Error procesando statement', [
                'statement_id' => $statement->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    private function prepareStatementForProcessing(RoyaltyStatement $statement): void
    {
        DB::transaction(function () use ($statement): void {
            if (Schema::hasTable('royalty_allocations')) {
                DB::table('royalty_allocations')
                    ->where('royalty_statement_id', $statement->id)
                    ->delete();
            }

            RoyaltyStatementLine::withTrashed()
                ->where('royalty_statement_id', $statement->id)
                ->forceDelete();

            $statement->update([
                'status' => 'processing',
                'total_units' => 0,
                'total_net_usd' => $this->formatDecimal(0),
                'is_reference_only' => false,
                'duplicate_of_statement_id' => null,
            ]);
        });

        $statement->refresh();
    }

    private function readHeaderRow($stream, string $provider): array
    {
        $attempts = 0;
        while (($row = fgetcsv($stream)) !== false) {
            $attempts++;
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $headers = array_map([$this, 'normalizeHeaderOriginal'], $row);
            $normalized = array_map([$this, 'normalizeHeader'], $headers);

            if ($this->matchesProviderHeader($provider, $normalized)) {
                return [$headers, $normalized];
            }

            if ($attempts >= 20) {
                break;
            }
        }

        throw new \RuntimeException('No se pudo detectar el encabezado del CSV.');
    }

    private function validateRequiredColumnsForProvider(string $provider, array $normalizedHeaders): void
    {
        $headers = array_values(array_unique($normalizedHeaders));

        if ($provider === 'symphonic') {
            if (!in_array('royalty ($us)', $headers, true)) {
                throw new \RuntimeException('El reporte Symphonic debe estar en USD (Royalty ($US)).');
            }

            return;
        }

        if ($provider === 'sonosuite') {
            $required = ['id', 'currency', 'net_total', 'channel', 'track_title', 'isrc'];
            $missing = [];

            foreach ($required as $column) {
                if (!in_array($column, $headers, true)) {
                    $missing[] = $column;
                }
            }

            if (!empty($missing)) {
                throw new \RuntimeException(
                    'El CSV Sonosuite no contiene columnas requeridas: ' . implode(', ', $missing)
                );
            }

            return;
        }

        throw new \RuntimeException("Proveedor no soportado para procesamiento: {$provider}");
    }

    private function matchesProviderHeader(string $provider, array $normalizedHeaders): bool
    {
        if ($provider === 'symphonic') {
            return in_array('royalty ($us)', $normalizedHeaders, true)
                || in_array('digital service provider', $normalizedHeaders, true);
        }

        if ($provider === 'sonosuite') {
            $required = ['id', 'currency', 'net_total', 'channel', 'track_title', 'isrc'];
            $hitCount = 0;

            foreach ($required as $column) {
                if (in_array($column, $normalizedHeaders, true)) {
                    $hitCount++;
                }
            }

            return $hitCount >= 5;
        }

        return false;
    }

    private function normalizeCanonicalRowByProvider(
        string $provider,
        MasterRoyaltyLineCanonicalNormalizer $canonicalNormalizer,
        array $rawRow,
        array $normalizedRow,
        int $lineNumber,
        int $statementId
    ): array {
        if ($provider === 'symphonic') {
            return $canonicalNormalizer->normalizeSymphonicRow(
                $rawRow,
                $normalizedRow,
                $lineNumber,
                $statementId
            );
        }

        if ($provider === 'sonosuite') {
            return $canonicalNormalizer->normalizeSonosuiteRow(
                $rawRow,
                $normalizedRow,
                $lineNumber,
                $statementId
            );
        }

        throw new \RuntimeException("Proveedor no soportado para normalizacion: {$provider}");
    }

    private function normalizeHeaderOriginal(string $value): string
    {
        $value = $this->stripBom((string) $value);
        return trim($value);
    }

    private function normalizeHeader(string $value): string
    {
        $value = $this->stripBom((string) $value);
        $value = strtolower(trim($value));
        return preg_replace('/\s+/', ' ', $value);
    }

    private function stripBom(string $value): string
    {
        if (str_starts_with($value, "\xEF\xBB\xBF")) {
            return substr($value, 3);
        }
        return $value;
    }

    private function normalizeRowKeys(array $rawRow, array $normalizedHeaders): array
    {
        $normalized = [];
        $keys = array_keys($rawRow);
        foreach ($keys as $idx => $key) {
            $normalized[$normalizedHeaders[$idx] ?? $this->normalizeHeader($key)] = trim((string) $rawRow[$key]);
        }
        return $normalized;
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }

    private function padRow(array $row, int $length): array
    {
        $count = count($row);
        if ($count < $length) {
            return array_pad($row, $length, null);
        }
        if ($count > $length) {
            return array_slice($row, 0, $length);
        }
        return $row;
    }

    private function parseMonthDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(?<mon>[A-Za-z]{3})[-\\s](?<year>\\d{2,4})$/', $value, $matches)) {
            $month = ucfirst(strtolower($matches['mon']));
            $year = $matches['year'];
            if (strlen($year) === 2) {
                $year = (int) ('20' . $year);
            }
            try {
                return Carbon::parse($month . ' ' . $year)->startOfMonth();
            } catch (Throwable $e) {
                return null;
            }
        }

        if (preg_match('/^(?<year>\\d{4})[-\\/](?<month>\\d{1,2})$/', $value, $matches)) {
            try {
                return Carbon::createFromDate((int) $matches['year'], (int) $matches['month'], 1)->startOfMonth();
            } catch (Throwable $e) {
                return null;
            }
        }

        try {
            return Carbon::parse($value)->startOfMonth();
        } catch (Throwable $e) {
            return null;
        }
    }

    private function formatDecimal(float $value): string
    {
        return number_format($value, 6, '.', '');
    }

    private function upsertLines(array $batch): void
    {
        DB::table('royalty_statement_lines')->upsert(
            $batch,
            ['royalty_statement_id', 'line_hash'],
            [
                'track_id',
                'match_status',
                'match_meta',
                'isrc',
                'upc',
                'track_title',
                'channel',
                'country',
                'activity_period_text',
                'activity_month_date',
                'units',
                'net_total_usd',
                'raw',
                'updated_at',
            ]
        );
    }

    private function calculateTotalsFromDatabase(int $statementId): array
    {
        $totals = RoyaltyStatementLine::where('royalty_statement_id', $statementId)
            ->selectRaw('COALESCE(SUM(units),0) as total_units, COALESCE(SUM(net_total_usd),0) as total_net')
            ->first();

        return [
            'units' => (int) ($totals->total_units ?? 0),
            'net' => (float) ($totals->total_net ?? 0),
        ];
    }

    private function finalizeStatement(
        RoyaltyStatement $statement,
        ?string $label,
        ?string $reportingPeriod,
        ?Carbon $reportingMonthDate,
        ?string $activityPeriodFallback,
        int $totalUnits,
        float $totalNetUsd,
        MasterRoyaltyDedupeService $dedupeService
    ): void {
        if (!$reportingMonthDate && $activityPeriodFallback) {
            $reportingMonthDate = $this->parseMonthDate($activityPeriodFallback);
        }

        $finalReportingPeriod = $reportingPeriod;
        if (!$finalReportingPeriod && $reportingMonthDate) {
            $finalReportingPeriod = strtoupper($reportingMonthDate->format('M-y'));
        }

        $update = [
            'label' => $label,
            'reporting_period' => $finalReportingPeriod,
            'reporting_month_date' => $reportingMonthDate?->toDateString(),
            'total_units' => $totalUnits,
            'total_net_usd' => $this->formatDecimal($totalNetUsd),
            'status' => 'processed',
            'is_reference_only' => false,
            'duplicate_of_statement_id' => null,
        ];

        if ($label && $finalReportingPeriod) {
            $statementKey = $dedupeService->normalizeStatementKey(
                $statement->provider,
                $label,
                $finalReportingPeriod
            );

            DB::transaction(function () use ($statement, $statementKey, $update, $dedupeService) {
                $existing = RoyaltyStatement::where('statement_key', $statementKey)
                    ->lockForUpdate()
                    ->get(['id', 'version', 'is_current']);

                $maxVersion = $existing->max('version') ?? 0;
                $version = $maxVersion + 1;

                $duplicateOfStatementId = $dedupeService->detectReferenceOnlyDuplicate($statement, $statementKey);
                if ($duplicateOfStatementId !== null) {
                    $statement->update(array_merge($update, [
                        'statement_key' => $statementKey,
                        'version' => $version,
                        'is_current' => false,
                        'is_reference_only' => true,
                        'duplicate_of_statement_id' => $duplicateOfStatementId,
                    ]));

                    return;
                }

                RoyaltyStatement::where('statement_key', $statementKey)
                    ->where('is_current', true)
                    ->where('id', '!=', $statement->id)
                    ->update(['is_current' => false]);

                $statement->update(array_merge($update, [
                    'statement_key' => $statementKey,
                    'version' => $version,
                    'is_current' => true,
                ]));
            });

            return;
        }

        $statement->update($update);
    }

    private function markBatchRowAsDuplicate(array $row, string $sourceLineId): array
    {
        $meta = [];
        if (!empty($row['match_meta'])) {
            $decoded = json_decode((string) $row['match_meta'], true);
            if (is_array($decoded)) {
                $meta = $decoded;
            }
        }

        $existingIds = data_get($meta, 'duplicate_source_line_ids', []);
        if (!is_array($existingIds)) {
            $existingIds = [];
        }
        $existingIds[] = $sourceLineId;

        $meta['duplicate_source_line_ids'] = array_values(array_unique($existingIds));
        $meta['duplicate_count'] = count($meta['duplicate_source_line_ids']);
        $meta['duplicate_note'] = 'Linea duplicada dentro del mismo archivo; se conserva una sola ocurrencia para calculo.';

        $row['match_status'] = RoyaltyStatementLine::MATCH_STATUS_DUPLICATE;
        $row['match_meta'] = json_encode($meta, JSON_UNESCAPED_UNICODE);

        return $row;
    }

    private function markPersistedLineAsDuplicate(int $statementId, string $lineHash, string $sourceLineId): void
    {
        $existing = DB::table('royalty_statement_lines')
            ->where('royalty_statement_id', $statementId)
            ->where('line_hash', $lineHash)
            ->select('id', 'match_meta')
            ->first();

        if (!$existing) {
            return;
        }

        $meta = [];
        if (!empty($existing->match_meta)) {
            $decoded = json_decode((string) $existing->match_meta, true);
            if (is_array($decoded)) {
                $meta = $decoded;
            }
        }

        $existingIds = data_get($meta, 'duplicate_source_line_ids', []);
        if (!is_array($existingIds)) {
            $existingIds = [];
        }
        $existingIds[] = $sourceLineId;

        $meta['duplicate_source_line_ids'] = array_values(array_unique($existingIds));
        $meta['duplicate_count'] = count($meta['duplicate_source_line_ids']);
        $meta['duplicate_note'] = 'Linea duplicada dentro del mismo archivo; se conserva una sola ocurrencia para calculo.';

        DB::table('royalty_statement_lines')
            ->where('id', $existing->id)
            ->update([
                'match_status' => RoyaltyStatementLine::MATCH_STATUS_DUPLICATE,
                'match_meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
    }
}
