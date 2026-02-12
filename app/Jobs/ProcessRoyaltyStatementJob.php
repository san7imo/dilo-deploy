<?php

namespace App\Jobs;

use App\Models\RoyaltyStatement;
use App\Models\RoyaltyStatementLine;
use App\Models\Track;
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

class ProcessRoyaltyStatementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $statementId;

    public function __construct(int $statementId)
    {
        $this->statementId = $statementId;
    }

    public function handle(): void
    {
        $statement = RoyaltyStatement::find($this->statementId);
        if (!$statement) {
            return;
        }

        if ($statement->status !== 'uploaded') {
            return;
        }

        $statement->update(['status' => 'processing']);

        $disk = Storage::disk('royalties_private');
        $stream = $disk->readStream($statement->stored_path);
        if (!$stream) {
            $statement->update(['status' => 'failed']);
            throw new \RuntimeException('No se pudo abrir el archivo para procesamiento.');
        }

        $totalUnits = 0;
        $totalNetUsd = 0.0;
        $label = null;
        $reportingPeriod = null;
        $reportingMonthDate = null;
        $activityPeriodFallback = null;
        $lineCount = 0;
        $batch = [];
        $batchSize = 1000;
        $trackCache = $this->buildTrackIsrcCache();
        $hasExistingLines = RoyaltyStatementLine::where('royalty_statement_id', $statement->id)->exists();
        $isrcKeys = ['isrc', 'isrc code', 'recording isrc', 'track isrc', 'song isrc', 'isrc#', 'isrc #'];
        $upcKeys = ['upc', 'upc code', 'barcode', 'release upc', 'album upc', 'upc/ean', 'upc/ean code'];

        try {
            [$headers, $normalizedHeaders] = $this->readHeaderRow($stream);

            if (!in_array('royalty ($us)', $normalizedHeaders, true)) {
                throw new \RuntimeException('El reporte debe estar en USD (Royalty ($US)).');
            }

            while (($row = fgetcsv($stream)) !== false) {
                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $row = $this->padRow($row, count($headers));
                $rawRow = array_combine($headers, $row);
                $normalizedRow = $this->normalizeRowKeys($rawRow, $normalizedHeaders);

                $label = $label ?: $this->firstValue($normalizedRow, ['label', 'label name']);
                $reportingPeriod = $reportingPeriod ?: $this->firstValue($normalizedRow, [
                    'reporting period',
                    'statement period',
                    'reporting period (month)',
                    'reporting month',
                    'statement month',
                ]);
                if (!$reportingMonthDate && $reportingPeriod) {
                    $reportingMonthDate = $this->parseMonthDate($reportingPeriod);
                }

                $isrcRaw = $this->firstValue($normalizedRow, $isrcKeys);
                $upcRaw = $this->firstValue($normalizedRow, $upcKeys);
                $isrc = $this->normalizeIsrc($isrcRaw);
                $upc = $this->normalizeUpc($upcRaw);
                $trackTitle = $this->firstValue($normalizedRow, ['track title', 'song title', 'track name', 'title', 'track']);
                $channel = $this->firstValue($normalizedRow, ['digital service provider', 'dsp', 'service provider', 'channel']);
                $country = $this->firstValue($normalizedRow, ['territory', 'country']);
                $activityPeriodText = $this->firstValue($normalizedRow, ['activity period', 'activity period (month)', 'period']);
                $activityMonthDate = $activityPeriodText ? $this->parseMonthDate($activityPeriodText) : null;
                if (!$activityPeriodFallback && $activityPeriodText) {
                    $activityPeriodFallback = $activityPeriodText;
                }
                $units = $this->parseInt($this->firstValue($normalizedRow, ['count', 'units', 'quantity']));
                $netTotalUsd = $this->parseMoney($this->firstValue($normalizedRow, ['royalty ($us)']));

                $trackId = null;
                if (!empty($isrc)) {
                    $cacheKey = strtolower($isrc);
                    $trackId = $trackCache[$cacheKey] ?? null;
                }

                $lineHashIsrc = $hasExistingLines
                    ? $this->firstValue($normalizedRow, ['isrc'])
                    : $isrcRaw;
                $lineHashUpc = $hasExistingLines
                    ? $this->firstValue($normalizedRow, ['upc'])
                    : $upcRaw;

                $lineHash = $this->buildLineHash($normalizedRow, [
                    'isrc' => $lineHashIsrc,
                    'upc' => $lineHashUpc,
                    'track_title' => $trackTitle,
                    'channel' => $channel,
                    'country' => $country,
                    'activity_period_text' => $activityPeriodText,
                    'units' => $units,
                    'net_total_usd' => $this->formatDecimal($netTotalUsd),
                ]);

                $rawPayload = [
                    'raw' => $rawRow,
                    'derived' => [
                        'label' => $label,
                        'reporting_period' => $reportingPeriod,
                        'isrc' => $isrc,
                        'upc' => $upc,
                        'track_title' => $trackTitle,
                        'channel' => $channel,
                        'country' => $country,
                        'activity_period_text' => $activityPeriodText,
                        'activity_month_date' => $activityMonthDate?->toDateString(),
                        'units' => $units,
                        'net_total_usd' => $this->formatDecimal($netTotalUsd),
                    ],
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
                    'raw' => json_encode($rawPayload, JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $totalUnits += $units;
                $totalNetUsd += $netTotalUsd;
                $lineCount++;

                if (count($batch) >= $batchSize) {
                    $this->upsertLines($batch);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                $this->upsertLines($batch);
            }

            $totals = $hasExistingLines
                ? $this->calculateTotalsFromDatabase($statement->id)
                : ['units' => $totalUnits, 'net' => $totalNetUsd];

            $this->finalizeStatement(
                $statement,
                $label,
                $reportingPeriod,
                $reportingMonthDate,
                $activityPeriodFallback,
                $totals['units'],
                $totals['net']
            );

            Log::info('✅ [Royalties] Statement procesado', [
                'statement_id' => $statement->id,
                'lines' => $lineCount,
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

    private function readHeaderRow($stream): array
    {
        $attempts = 0;
        while (($row = fgetcsv($stream)) !== false) {
            $attempts++;
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $headers = array_map([$this, 'normalizeHeaderOriginal'], $row);
            $normalized = array_map([$this, 'normalizeHeader'], $headers);

            if (in_array('royalty ($us)', $normalized, true) || in_array('digital service provider', $normalized, true)) {
                return [$headers, $normalized];
            }

            if ($attempts >= 20) {
                break;
            }
        }

        throw new \RuntimeException('No se pudo detectar el encabezado del CSV.');
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

    private function firstValue(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            $normalizedKey = $this->normalizeHeader($key);
            if (array_key_exists($normalizedKey, $row) && $row[$normalizedKey] !== '') {
                return $row[$normalizedKey];
            }
        }
        return null;
    }

    private function parseInt(?string $value): int
    {
        if ($value === null) {
            return 0;
        }
        $normalized = str_replace([',', ' '], '', $value);
        return (int) $normalized;
    }

    private function parseMoney(?string $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        $negative = str_contains($value, '(') && str_contains($value, ')');
        $normalized = str_replace(['$', ',', '(', ')', ' '], '', $value);
        $amount = (float) $normalized;
        return $negative ? -$amount : $amount;
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

    private function buildLineHash(array $normalizedRow, array $canonical): string
    {
        $extras = ['delivery', 'sale/void', 'sale or void', 'sale/return', 'type', 'usage type'];
        $payload = [];
        foreach ($canonical as $key => $value) {
            $payload[$key] = is_string($value) ? trim($value) : $value;
        }

        foreach ($extras as $extra) {
            $normalizedKey = $this->normalizeHeader($extra);
            if (array_key_exists($normalizedKey, $normalizedRow) && $normalizedRow[$normalizedKey] !== '') {
                $payload[$normalizedKey] = $normalizedRow[$normalizedKey];
            }
        }

        ksort($payload);
        return sha1(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    private function normalizeIsrc(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $value));
        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeUpc(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $normalized = preg_replace('/\D/', '', $value);
        return $normalized !== '' ? $normalized : null;
    }

    private function formatDecimal(float $value): string
    {
        return number_format($value, 6, '.', '');
    }

    private function buildTrackIsrcCache(): array
    {
        $cache = [];
        $tracks = Track::query()
            ->whereNotNull('isrc')
            ->pluck('id', 'isrc');

        foreach ($tracks as $isrc => $id) {
            $normalized = $this->normalizeIsrc($isrc);
            if ($normalized) {
                $cache[strtolower($normalized)] = $id;
            }
        }

        return $cache;
    }

    private function upsertLines(array $batch): void
    {
        DB::table('royalty_statement_lines')->upsert(
            $batch,
            ['royalty_statement_id', 'line_hash'],
            ['track_id', 'isrc', 'upc', 'updated_at']
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
        float $totalNetUsd
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
        ];

        if ($label && $finalReportingPeriod) {
            $statementKey = $this->normalizeStatementKey(
                $statement->provider,
                $label,
                $finalReportingPeriod
            );

            DB::transaction(function () use ($statement, $statementKey, $update) {
                $existing = RoyaltyStatement::where('statement_key', $statementKey)
                    ->lockForUpdate()
                    ->get(['id', 'version', 'is_current']);

                $maxVersion = $existing->max('version') ?? 0;
                $version = $maxVersion + 1;

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

    private function normalizeStatementKey(string $provider, string $label, string $reportingPeriod): string
    {
        $normalize = function (string $value): string {
            $value = strtolower(trim($value));
            return preg_replace('/\s+/', ' ', $value);
        };

        return implode('|', [
            $normalize($provider),
            $normalize($label),
            $normalize($reportingPeriod),
        ]);
    }
}
