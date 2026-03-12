<?php

namespace App\Services\Compositions;

class CompositionSplitPoolValidator
{
    public const REQUIRED_POOLS = [
        'writer',
        'publisher',
        'mechanical_payee',
    ];

    /**
     * @param array<int, array<string, mixed>> $participants
     * @return array<string, string>
     */
    public function validate(array $participants, float $tolerance = 0.01): array
    {
        $poolTotals = $this->poolTotals($participants);
        $poolCounts = $this->poolCounts($participants);
        $errors = [];

        foreach (self::REQUIRED_POOLS as $pool) {
            if (($poolCounts[$pool] ?? 0) === 0) {
                $errors[$pool] = "Debes agregar al menos un participante en el pool {$pool}.";
                continue;
            }

            $total = (float) ($poolTotals[$pool] ?? 0.0);
            if (abs($total - 100.0) > $tolerance) {
                $errors[$pool] = "La suma del pool {$pool} debe ser 100.00%. Total actual: {$this->formatTotal($total)}%.";
            }
        }

        return $errors;
    }

    /**
     * @param array<int, array<string, mixed>> $participants
     * @return array<string, float>
     */
    public function poolTotals(array $participants): array
    {
        $totals = array_fill_keys(self::REQUIRED_POOLS, 0.0);

        foreach ($participants as $participant) {
            $pool = $this->normalizePool($participant['share_pool'] ?? null);
            if (!array_key_exists($pool, $totals)) {
                continue;
            }

            $totals[$pool] += (float) ($participant['percentage'] ?? 0);
        }

        return $totals;
    }

    /**
     * @param array<int, array<string, mixed>> $participants
     * @return array<string, int>
     */
    private function poolCounts(array $participants): array
    {
        $counts = array_fill_keys(self::REQUIRED_POOLS, 0);

        foreach ($participants as $participant) {
            $pool = $this->normalizePool($participant['share_pool'] ?? null);
            if (!array_key_exists($pool, $counts)) {
                continue;
            }

            $counts[$pool]++;
        }

        return $counts;
    }

    private function normalizePool(mixed $value): string
    {
        $pool = strtolower(trim((string) $value));

        return in_array($pool, self::REQUIRED_POOLS, true)
            ? $pool
            : 'writer';
    }

    private function formatTotal(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}

