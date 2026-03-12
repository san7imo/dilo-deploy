<?php

namespace App\Services\Royalties;

use Carbon\Carbon;
use Throwable;

class MasterRoyaltyLineCanonicalNormalizer
{
    public const IMPORTER_VERSION = 'master-canonical-v1';

    public function __construct(
        private readonly MasterCurrencyNormalizer $currencyNormalizer
    ) {
    }

    public function normalizeSymphonicRow(
        array $rawRow,
        array $normalizedRow,
        int $rowNumber,
        int $statementId
    ): array {
        $statementPeriodRaw = $this->firstValue($normalizedRow, [
            'reporting period',
            'statement period',
            'reporting period (month)',
            'reporting month',
            'statement month',
        ]);
        $statementMonth = $this->parseMonthDate($statementPeriodRaw);

        $activityPeriodText = $this->firstValue($normalizedRow, [
            'activity period',
            'activity period (month)',
            'period',
        ]);
        $activityMonth = $this->parseMonthDate($activityPeriodText);

        $amountOriginal = $this->parseMoney(
            $this->firstValue($normalizedRow, ['royalty ($us)'])
        );
        $money = $this->currencyNormalizer->normalizeFromOriginal(
            $amountOriginal,
            'USD',
            1.0,
            'symphonic'
        );

        $transactionType = $this->firstValue($normalizedRow, [
            'transaction type',
            'sale or void',
            'sale/void',
        ]);

        return [
            'source_name' => 'symphonic',
            'source_statement_id' => (string) $statementId,
            'source_line_id' => (string) $rowNumber,
            'statement_period_raw' => $statementPeriodRaw,
            'statement_period_start' => $statementMonth?->copy()->startOfMonth()->toDateString(),
            'statement_period_end' => $statementMonth?->copy()->endOfMonth()->toDateString(),
            'activity_start_date' => $activityMonth?->copy()->startOfMonth()->toDateString(),
            'activity_end_date' => $activityMonth?->copy()->endOfMonth()->toDateString(),
            'activity_month' => $activityMonth?->copy()->startOfMonth()->toDateString(),
            'activity_period_text' => $activityPeriodText,
            'confirmation_date' => null,
            'label_name' => $this->firstValue($normalizedRow, ['label', 'label name']),
            'release_title' => $this->firstValue($normalizedRow, ['release name', 'release title', 'album', 'release']),
            'release_version' => $this->firstValue($normalizedRow, ['release version']),
            'release_artists' => $this->firstValue($normalizedRow, ['release artists', 'album artists']),
            'upc' => $this->normalizeUpc(
                $this->firstValue($normalizedRow, ['upc', 'upc code', 'barcode', 'release upc', 'album upc', 'upc/ean', 'upc/ean code'])
            ),
            'catalogue' => $this->firstValue($normalizedRow, ['catalogue', 'catalog', 'catalog no']),
            'track_title' => $this->firstValue($normalizedRow, ['track title', 'song title', 'track name', 'title', 'track']),
            'mix_version' => $this->firstValue($normalizedRow, ['mix version', 'version']),
            'track_artists' => $this->firstValue($normalizedRow, ['track artists', 'artists']),
            'isrc' => $this->normalizeIsrc(
                $this->firstValue($normalizedRow, ['isrc', 'isrc code', 'recording isrc', 'track isrc', 'song isrc', 'isrc#', 'isrc #'])
            ),
            'dsp_name' => $this->firstValue($normalizedRow, ['digital service provider', 'dsp', 'service provider', 'channel']),
            'territory_code' => $this->firstValue($normalizedRow, ['territory', 'country']),
            'delivery_type' => $this->firstValue($normalizedRow, ['delivery']),
            'content_type' => $this->firstValue($normalizedRow, ['content type']),
            'transaction_type' => $transactionType,
            'sale_or_void' => $this->firstValue($normalizedRow, ['sale or void', 'sale/void']),
            'units' => $this->parseInt($this->firstValue($normalizedRow, ['count', 'units', 'quantity'])),
            'amount_original' => $money['amount_original'],
            'currency_original' => $money['currency_original'],
            'gross_amount_original' => null,
            'other_costs_original' => null,
            'channel_costs_original' => null,
            'taxes_original' => null,
            'fx_rate_to_usd' => $money['fx_rate_to_usd'],
            'amount_usd' => $money['amount_usd'],
            'raw_payload_json' => [
                'raw' => $rawRow,
                'normalized' => $normalizedRow,
                'monetary' => [
                    'conversion_strategy' => $money['conversion_strategy'],
                ],
            ],
        ];
    }

    public function normalizeSonosuiteRow(
        array $rawRow,
        array $normalizedRow,
        int $rowNumber,
        int $statementId
    ): array {
        $startDate = $this->parseDate($this->firstValue($normalizedRow, ['start_date', 'start date']));
        $endDate = $this->parseDate($this->firstValue($normalizedRow, ['end_date', 'end date']));
        $confirmationDate = $this->parseDate(
            $this->firstValue($normalizedRow, ['confirmation_report_date', 'confirmation date', 'report date'])
        );

        $currencyOriginal = strtoupper((string) ($this->firstValue($normalizedRow, ['currency']) ?? 'USD'));
        if ($currencyOriginal === '') {
            $currencyOriginal = 'USD';
        }

        $amountOriginal = $this->parseMoney($this->firstValue($normalizedRow, ['net_total', 'net total']));
        $fxRateRaw = $this->parseFloat(
            $this->firstValue($normalizedRow, ['currency_rate', 'currency rate', 'fx_rate'])
        );
        $money = $this->currencyNormalizer->normalizeFromOriginal(
            $amountOriginal,
            $currencyOriginal,
            $fxRateRaw > 0 ? $fxRateRaw : null,
            'sonosuite'
        );

        $sourceLineId = $this->firstValue($normalizedRow, ['id', 'line_id', 'line id']);
        if ($sourceLineId === null || $sourceLineId === '') {
            $sourceLineId = (string) $rowNumber;
        }

        return [
            'source_name' => 'sonosuite',
            'source_statement_id' => (string) $statementId,
            'source_line_id' => (string) $sourceLineId,
            'statement_period_raw' => $this->buildPeriodRawFromDates($startDate, $endDate),
            'statement_period_start' => $startDate?->toDateString(),
            'statement_period_end' => $endDate?->toDateString(),
            'activity_start_date' => $startDate?->toDateString(),
            'activity_end_date' => $endDate?->toDateString(),
            'activity_month' => ($startDate ?? $endDate)?->copy()->startOfMonth()->toDateString(),
            'activity_period_text' => $this->buildPeriodRawFromDates($startDate, $endDate),
            'confirmation_date' => $confirmationDate?->toDateString(),
            'label_name' => $this->firstValue($normalizedRow, ['label']),
            'release_title' => $this->firstValue($normalizedRow, ['release', 'release_title', 'release title']),
            'release_version' => null,
            'release_artists' => $this->firstValue($normalizedRow, ['artist', 'release_artists']),
            'upc' => $this->normalizeUpc(
                $this->firstValue($normalizedRow, ['upc', 'upc code'])
            ),
            'catalogue' => null,
            'track_title' => $this->firstValue($normalizedRow, ['track_title', 'track title']),
            'mix_version' => null,
            'track_artists' => $this->firstValue($normalizedRow, ['artist', 'track_artists']),
            'isrc' => $this->normalizeIsrc(
                $this->firstValue($normalizedRow, ['isrc'])
            ),
            'dsp_name' => $this->firstValue($normalizedRow, ['channel']),
            'territory_code' => $this->firstValue($normalizedRow, ['country']),
            'delivery_type' => $this->firstValue($normalizedRow, ['type']),
            'content_type' => $this->firstValue($normalizedRow, ['type']),
            'transaction_type' => $this->firstValue($normalizedRow, ['type']),
            'sale_or_void' => null,
            'units' => $this->parseInt($this->firstValue($normalizedRow, ['units'])),
            'amount_original' => $money['amount_original'],
            'currency_original' => $money['currency_original'],
            'gross_amount_original' => $this->formatDecimal(
                $this->parseMoney($this->firstValue($normalizedRow, ['gross_total', 'gross total']))
            ),
            'other_costs_original' => $this->formatDecimal(
                $this->parseMoney($this->firstValue($normalizedRow, ['other_costs', 'other costs']))
            ),
            'channel_costs_original' => $this->formatDecimal(
                $this->parseMoney($this->firstValue($normalizedRow, ['channel_costs', 'channel costs']))
            ),
            'taxes_original' => $this->formatDecimal(
                $this->parseMoney($this->firstValue($normalizedRow, ['taxes']))
            ),
            'fx_rate_to_usd' => $money['fx_rate_to_usd'],
            'amount_usd' => $money['amount_usd'],
            'raw_payload_json' => [
                'raw' => $rawRow,
                'normalized' => $normalizedRow,
                'sonosuite' => [
                    'user_email' => $this->firstValue($normalizedRow, ['user_email', 'user email']),
                    'unit_price' => $this->firstValue($normalizedRow, ['unit_price', 'unit price']),
                    'gross_total_client_currency' => $this->firstValue($normalizedRow, ['gross_total_client_currency']),
                    'other_costs_client_currency' => $this->firstValue($normalizedRow, ['other_costs_client_currency']),
                    'channel_costs_client_currency' => $this->firstValue($normalizedRow, ['channel_costs_client_currency']),
                    'net_total_client_currency' => $this->firstValue($normalizedRow, ['net_total_client_currency']),
                    'conversion_strategy' => $money['conversion_strategy'],
                ],
            ],
        ];
    }

    private function firstValue(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            $normalizedKey = $this->normalizeHeader($key);
            if (array_key_exists($normalizedKey, $row) && trim((string) $row[$normalizedKey]) !== '') {
                return trim((string) $row[$normalizedKey]);
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

    private function parseFloat(?string $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $normalized = str_replace([',', ' '], ['', ''], $value);
        return (float) $normalized;
    }

    private function parseMonthDate(?string $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

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
            } catch (Throwable) {
                return null;
            }
        }

        if (preg_match('/^(?<year>\\d{4})[-\\/](?<month>\\d{1,2})$/', $value, $matches)) {
            try {
                return Carbon::createFromDate((int) $matches['year'], (int) $matches['month'], 1)->startOfMonth();
            } catch (Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse($value)->startOfMonth();
        } catch (Throwable) {
            return null;
        }
    }

    private function parseDate(?string $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
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

    private function formatDecimal(float|int $value): string
    {
        return number_format((float) $value, 6, '.', '');
    }

    private function buildPeriodRawFromDates(?Carbon $startDate, ?Carbon $endDate): ?string
    {
        if (!$startDate && !$endDate) {
            return null;
        }

        if ($startDate && $endDate) {
            return $startDate->toDateString() . ' -> ' . $endDate->toDateString();
        }

        return ($startDate ?? $endDate)?->toDateString();
    }
}
