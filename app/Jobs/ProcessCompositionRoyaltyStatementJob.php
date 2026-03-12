<?php

namespace App\Jobs;

use App\Models\CompositionRoyaltyLine;
use App\Models\CompositionRoyaltyStatement;
use App\Services\Compositions\CompositionRoyaltyAllocationService;
use App\Services\Compositions\CompositionRoyaltyLineMatcher;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessCompositionRoyaltyStatementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $statementId
    ) {
    }

    public function handle(
        CompositionRoyaltyLineMatcher $lineMatcher,
        CompositionRoyaltyAllocationService $allocationService
    ): void {
        $statement = CompositionRoyaltyStatement::query()->find($this->statementId);
        if (!$statement) {
            return;
        }

        if (!in_array($statement->status, [
            CompositionRoyaltyStatement::STATUS_UPLOADED,
            CompositionRoyaltyStatement::STATUS_FAILED,
        ], true)) {
            return;
        }

        $this->prepareStatementForProcessing($statement);

        $disk = Storage::disk('royalties_private');
        $stream = $disk->readStream($statement->stored_path);
        if (!$stream) {
            $statement->update([
                'status' => CompositionRoyaltyStatement::STATUS_FAILED,
                'error_message' => 'No se pudo abrir el archivo para procesamiento.',
            ]);
            throw new \RuntimeException('No se pudo abrir el archivo de composition royalties.');
        }

        $lineCount = 0;
        $duplicateCount = 0;
        $batch = [];
        $batchSize = 1000;
        $seenLineHashes = [];
        $reportingPeriodDetected = $statement->reporting_period;
        $sourceNameDetected = $statement->source_name;

        try {
            [$headers, $normalizedHeaders] = $this->readHeaderRow($stream);
            $this->validateRequiredColumns($normalizedHeaders);

            while (($row = fgetcsv($stream)) !== false) {
                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $row = $this->padRow($row, count($headers));
                $rawRow = array_combine($headers, $row);
                $normalizedRow = $this->normalizeRowKeys($rawRow, $normalizedHeaders);
                $lineNumber = $lineCount + 1;

                $canonical = $this->normalizeCanonicalRow(
                    normalizedRow: $normalizedRow,
                    lineNumber: $lineNumber,
                    statementId: (int) $statement->id
                );

                if (($canonical['currency'] ?? 'USD') !== 'USD') {
                    throw new \RuntimeException("La línea {$lineNumber} no está en USD.");
                }

                $reportingPeriodDetected = $reportingPeriodDetected ?: ($canonical['reporting_period'] ?? null);
                $sourceNameDetected = $sourceNameDetected ?: ($canonical['source_name'] ?? null);

                $lineHash = $this->buildLineHash($canonical);
                if (isset($seenLineHashes[$lineHash])) {
                    $duplicateCount++;
                    $lineCount++;
                    continue;
                }

                $seenLineHashes[$lineHash] = true;
                $match = $lineMatcher->resolve($canonical);

                $rawPayload = [
                    'source' => [
                        'provider' => $statement->provider,
                        'statement_id' => (string) $statement->id,
                        'source_line_id' => $canonical['source_line_id'] ?? (string) $lineNumber,
                    ],
                    'canonical' => $canonical,
                    'raw' => $rawRow,
                ];

                $batch[] = [
                    'composition_royalty_statement_id' => $statement->id,
                    'composition_id' => $match['composition_id'],
                    'line_type' => $canonical['line_type'],
                    'source_line_id' => $canonical['source_line_id'],
                    'external_reference' => $canonical['external_reference'],
                    'composition_iswc' => $canonical['composition_iswc'],
                    'composition_title' => $canonical['composition_title'],
                    'source_name' => $canonical['source_name'],
                    'territory_code' => $canonical['territory_code'],
                    'activity_period_text' => $canonical['activity_period_text'],
                    'activity_month_date' => $canonical['activity_month_date'],
                    'units' => $canonical['units'],
                    'amount_usd' => number_format((float) $canonical['amount_usd'], 6, '.', ''),
                    'currency' => 'USD',
                    'line_hash' => $lineHash,
                    'match_status' => $match['match_status'],
                    'match_meta' => json_encode($match['match_meta'] ?? [], JSON_UNESCAPED_UNICODE),
                    'raw' => json_encode($rawPayload, JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $lineCount++;

                if (count($batch) >= $batchSize) {
                    $this->upsertLines($batch);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                $this->upsertLines($batch);
            }

            $totals = $this->calculateTotals((int) $statement->id);

            $reportingMonthDate = $this->parseReportingMonthDate($reportingPeriodDetected);

            $statement->update([
                'source_name' => $sourceNameDetected,
                'reporting_period' => $reportingPeriodDetected,
                'reporting_month_date' => $reportingMonthDate?->toDateString(),
                'status' => CompositionRoyaltyStatement::STATUS_PROCESSED,
                'total_lines' => $totals['line_count'],
                'total_units' => $totals['units'],
                'total_amount_usd' => number_format($totals['amount_usd'], 6, '.', ''),
                'processed_at' => now(),
                'error_message' => null,
            ]);

            $allocationStats = $allocationService->rebuildForStatement($statement->fresh());

            Log::info('[CompositionRoyalties] Statement procesado', [
                'statement_id' => (int) $statement->id,
                'lines_read' => $lineCount,
                'lines_deduped_in_file' => $duplicateCount,
                'allocations_count' => $allocationStats['allocations_count'] ?? 0,
            ]);
        } catch (Throwable $e) {
            $statement->update([
                'status' => CompositionRoyaltyStatement::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

            Log::error('[CompositionRoyalties] Error procesando statement', [
                'statement_id' => (int) $statement->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    private function prepareStatementForProcessing(CompositionRoyaltyStatement $statement): void
    {
        DB::transaction(function () use ($statement): void {
            DB::table('composition_allocations')
                ->where('composition_royalty_statement_id', $statement->id)
                ->delete();

            CompositionRoyaltyLine::withTrashed()
                ->where('composition_royalty_statement_id', $statement->id)
                ->forceDelete();

            $statement->update([
                'status' => CompositionRoyaltyStatement::STATUS_PROCESSING,
                'total_lines' => 0,
                'total_units' => 0,
                'total_amount_usd' => number_format(0, 6, '.', ''),
                'processed_at' => null,
                'error_message' => null,
            ]);
        });
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private function readHeaderRow($stream): array
    {
        while (($row = fgetcsv($stream)) !== false) {
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $headers = array_map([$this, 'normalizeHeaderOriginal'], $row);
            $normalized = array_map([$this, 'normalizeHeader'], $headers);

            return [$headers, $normalized];
        }

        throw new \RuntimeException('No se pudo detectar encabezado del CSV de composición.');
    }

    /**
     * @param array<int, string> $normalizedHeaders
     */
    private function validateRequiredColumns(array $normalizedHeaders): void
    {
        $required = ['line_type', 'amount_usd', 'currency'];
        $missing = [];

        foreach ($required as $column) {
            if (!in_array($column, $normalizedHeaders, true)) {
                $missing[] = $column;
            }
        }

        if (!empty($missing)) {
            throw new \RuntimeException(
                'El CSV de composición no contiene columnas requeridas: ' . implode(', ', $missing)
            );
        }
    }

    /**
     * @param array<string, mixed> $normalizedRow
     * @return array<string, mixed>
     */
    private function normalizeCanonicalRow(array $normalizedRow, int $lineNumber, int $statementId): array
    {
        $lineType = strtolower(trim((string) ($normalizedRow['line_type'] ?? '')));
        if (!in_array($lineType, [
            CompositionRoyaltyLine::LINE_TYPE_PERFORMANCE,
            CompositionRoyaltyLine::LINE_TYPE_MECHANICAL,
        ], true)) {
            throw new \RuntimeException("line_type inválido en línea {$lineNumber}: {$lineType}");
        }

        $amountUsd = $this->parseMoney($normalizedRow['amount_usd'] ?? null);
        $units = $this->parseInt($normalizedRow['units'] ?? null);

        $currency = strtoupper(trim((string) ($normalizedRow['currency'] ?? 'USD')));
        if ($currency === '') {
            $currency = 'USD';
        }

        $activityMonth = $this->parseMonthDate(
            $normalizedRow['activity_month'] ?? ($normalizedRow['activity_period'] ?? null)
        );

        return [
            'source_statement_id' => (string) $statementId,
            'source_line_id' => trim((string) ($normalizedRow['source_line_id'] ?? $lineNumber)),
            'external_reference' => $this->nullableTrimmed($normalizedRow['external_reference'] ?? null),
            'reporting_period' => $this->nullableTrimmed($normalizedRow['reporting_period'] ?? null),
            'line_type' => $lineType,
            'composition_id' => $this->parseNullableInt($normalizedRow['composition_id'] ?? null),
            'composition_iswc' => $this->normalizeIswc($normalizedRow['composition_iswc'] ?? null),
            'composition_title' => $this->nullableTrimmed($normalizedRow['composition_title'] ?? null),
            'source_name' => $this->nullableTrimmed($normalizedRow['source_name'] ?? null),
            'territory_code' => $this->nullableTrimmed($normalizedRow['territory_code'] ?? null),
            'activity_period_text' => $this->nullableTrimmed($normalizedRow['activity_period'] ?? null),
            'activity_month_date' => $activityMonth?->startOfMonth()->toDateString(),
            'units' => $units,
            'amount_usd' => $amountUsd,
            'currency' => $currency,
        ];
    }

    /**
     * @param array<string, mixed> $canonical
     */
    private function buildLineHash(array $canonical): string
    {
        $parts = [
            $canonical['source_statement_id'] ?? '',
            $canonical['source_line_id'] ?? '',
            $canonical['line_type'] ?? '',
            $canonical['composition_id'] ?? '',
            $canonical['composition_iswc'] ?? '',
            $canonical['composition_title'] ?? '',
            $canonical['source_name'] ?? '',
            $canonical['territory_code'] ?? '',
            $canonical['activity_period_text'] ?? '',
            $canonical['activity_month_date'] ?? '',
            $canonical['units'] ?? 0,
            number_format((float) ($canonical['amount_usd'] ?? 0), 6, '.', ''),
            $canonical['currency'] ?? 'USD',
            $canonical['external_reference'] ?? '',
        ];

        return hash('sha256', implode('|', array_map(fn($value) => (string) $value, $parts)));
    }

    /**
     * @param array<int, array<string, mixed>> $batch
     */
    private function upsertLines(array $batch): void
    {
        DB::table('composition_royalty_lines')->upsert(
            $batch,
            ['composition_royalty_statement_id', 'line_hash'],
            [
                'composition_id',
                'line_type',
                'source_line_id',
                'external_reference',
                'composition_iswc',
                'composition_title',
                'source_name',
                'territory_code',
                'activity_period_text',
                'activity_month_date',
                'units',
                'amount_usd',
                'currency',
                'match_status',
                'match_meta',
                'raw',
                'updated_at',
            ]
        );
    }

    /**
     * @return array{line_count: int, units: int, amount_usd: float}
     */
    private function calculateTotals(int $statementId): array
    {
        $totals = DB::table('composition_royalty_lines')
            ->where('composition_royalty_statement_id', $statementId)
            ->selectRaw('COUNT(*) as line_count, COALESCE(SUM(units),0) as units, COALESCE(SUM(amount_usd),0) as amount_usd')
            ->first();

        return [
            'line_count' => (int) ($totals->line_count ?? 0),
            'units' => (int) ($totals->units ?? 0),
            'amount_usd' => (float) ($totals->amount_usd ?? 0),
        ];
    }

    private function parseReportingMonthDate(?string $reportingPeriod): ?Carbon
    {
        if (!$reportingPeriod) {
            return null;
        }

        $value = trim($reportingPeriod);
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
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse($value)->startOfMonth();
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseInt(mixed $value): int
    {
        if ($value === null) {
            return 0;
        }

        $normalized = preg_replace('/[^0-9\-]/', '', (string) $value) ?? '';
        if ($normalized === '' || $normalized === '-') {
            return 0;
        }

        return (int) $normalized;
    }

    private function parseNullableInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        if (!preg_match('/^\d+$/', $normalized)) {
            return null;
        }

        return (int) $normalized;
    }

    private function parseMoney(mixed $value): float
    {
        if ($value === null) {
            return 0.0;
        }

        $string = trim((string) $value);
        if ($string === '') {
            return 0.0;
        }

        $negative = false;
        if (str_starts_with($string, '(') && str_ends_with($string, ')')) {
            $negative = true;
            $string = substr($string, 1, -1);
        }

        $string = str_replace([',', '$', ' '], '', $string);
        if ($string === '' || $string === '-') {
            return 0.0;
        }

        $number = (float) $string;
        return $negative ? -1 * $number : $number;
    }

    private function parseMonthDate(mixed $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);
        if ($string === '') {
            return null;
        }

        try {
            return Carbon::parse($string)->startOfMonth();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeIswc(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $value) ?? '');
        return $normalized !== '' ? $normalized : null;
    }

    private function nullableTrimmed(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        return $normalized !== '' ? $normalized : null;
    }

    /**
     * @param array<int, string> $row
     * @return array<int, string>
     */
    private function padRow(array $row, int $targetCount): array
    {
        $current = count($row);
        if ($current >= $targetCount) {
            return array_slice($row, 0, $targetCount);
        }

        return array_merge($row, array_fill(0, $targetCount - $current, ''));
    }

    /**
     * @param array<string, string> $row
     * @param array<int, string> $normalizedHeaders
     * @return array<string, string>
     */
    private function normalizeRowKeys(array $row, array $normalizedHeaders): array
    {
        $values = array_values($row);
        $normalized = [];

        foreach ($normalizedHeaders as $index => $header) {
            $normalized[$header] = $values[$index] ?? '';
        }

        return $normalized;
    }

    /**
     * @param array<int, string> $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeHeaderOriginal(string $value): string
    {
        $value = $this->stripBom($value);
        return trim($value);
    }

    private function normalizeHeader(string $value): string
    {
        $normalized = mb_strtolower(trim($this->stripBom($value)), 'UTF-8');
        $normalized = preg_replace('/[^a-z0-9]+/u', '_', $normalized) ?? '';
        $normalized = trim($normalized, '_');

        return $normalized;
    }

    private function stripBom(string $value): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
    }
}
