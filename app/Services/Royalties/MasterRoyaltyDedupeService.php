<?php

namespace App\Services\Royalties;

use App\Models\RoyaltyStatement;
use App\Models\RoyaltyStatementLine;
use Illuminate\Support\Facades\DB;

class MasterRoyaltyDedupeService
{
    public function buildFileHash(string $rawContents): string
    {
        return hash('sha256', $this->normalizeFileContentsForHash($rawContents));
    }

    public function normalizeFileContentsForHash(string $contents): string
    {
        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            $contents = substr($contents, 3);
        }

        return str_replace(["\r\n", "\r"], "\n", $contents);
    }

    public function buildLineHash(array $normalizedRow, array $canonical): string
    {
        $sourceName = strtolower(trim((string) ($canonical['source_name'] ?? 'unknown')));
        $sourceLineId = (string) ($canonical['source_line_id'] ?? '');

        $payload = [
            'source_name' => $sourceName,
            'isrc' => $canonical['isrc'] ?? null,
            'upc' => $canonical['upc'] ?? null,
            'track_title' => $canonical['track_title'] ?? null,
            'dsp_name' => $canonical['dsp_name'] ?? null,
            'territory_code' => $canonical['territory_code'] ?? null,
            'delivery_type' => $canonical['delivery_type'] ?? null,
            'content_type' => $canonical['content_type'] ?? null,
            'transaction_type' => $canonical['transaction_type'] ?? null,
            'sale_or_void' => $canonical['sale_or_void'] ?? null,
            'activity_period_text' => $canonical['activity_period_text'] ?? null,
            'activity_month' => $canonical['activity_month'] ?? null,
            'units' => (int) ($canonical['units'] ?? 0),
            'amount_usd' => $canonical['amount_usd'] ?? null,
        ];

        if ($this->hasReliableSourceLineId($sourceName, $sourceLineId)) {
            $payload['source_line_id'] = $sourceLineId;
        }

        $extraHeaders = ['delivery', 'sale/void', 'sale or void', 'sale/return', 'type', 'usage type'];
        foreach ($extraHeaders as $header) {
            $normalizedHeader = $this->normalizeHeader($header);
            if (array_key_exists($normalizedHeader, $normalizedRow) && trim((string) $normalizedRow[$normalizedHeader]) !== '') {
                $payload[$normalizedHeader] = trim((string) $normalizedRow[$normalizedHeader]);
            }
        }

        foreach ($payload as $key => $value) {
            if (is_string($value)) {
                $payload[$key] = trim($value);
            }
        }

        ksort($payload);

        return sha1(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    public function normalizeStatementKey(string $provider, string $label, string $reportingPeriod): string
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

    public function detectReferenceOnlyDuplicate(RoyaltyStatement $statement, string $statementKey): ?int
    {
        $lineCount = RoyaltyStatementLine::query()
            ->where('royalty_statement_id', $statement->id)
            ->count();

        if ($lineCount === 0) {
            return null;
        }

        $candidateIds = RoyaltyStatement::query()
            ->where('statement_key', $statementKey)
            ->where('status', 'processed')
            ->where('id', '!=', $statement->id)
            ->orderByDesc('is_current')
            ->orderByDesc('version')
            ->pluck('id');

        foreach ($candidateIds as $candidateId) {
            $matchedCount = DB::table('royalty_statement_lines as src')
                ->join('royalty_statement_lines as cmp', function ($join) use ($candidateId) {
                    $join->on('cmp.line_hash', '=', 'src.line_hash')
                        ->where('cmp.royalty_statement_id', '=', $candidateId);
                })
                ->where('src.royalty_statement_id', $statement->id)
                ->count();

            if ((int) $matchedCount === (int) $lineCount) {
                return (int) $candidateId;
            }
        }

        return null;
    }

    private function hasReliableSourceLineId(string $sourceName, string $sourceLineId): bool
    {
        if ($sourceLineId === '') {
            return false;
        }

        // Symphonic no expone line id estable; el row number no debe entrar al hash.
        if ($sourceName === 'symphonic') {
            return false;
        }

        return true;
    }

    private function normalizeHeader(string $value): string
    {
        $value = strtolower(trim($value));

        return preg_replace('/\s+/', ' ', $value);
    }
}
