<?php

namespace App\Services\Royalties;

use Illuminate\Support\Collection;

class SplitAllocationCalculator
{
    /**
     * @param Collection<int, mixed> $participants
     * @return array{
     *   valid: bool,
     *   total_percentage: float,
     *   sum_allocated: float,
     *   difference: float,
     *   amounts: array<int, array{participant:mixed, percentage:float, allocated:float}>
     * }
     */
    public function allocate(
        float $grossAmountUsd,
        Collection $participants,
        string $percentageField = 'percentage',
        float $percentageTolerance = 0.01
    ): array {
        $participants = $participants->values();
        if ($participants->isEmpty()) {
            return [
                'valid' => false,
                'total_percentage' => 0.0,
                'sum_allocated' => 0.0,
                'difference' => round($grossAmountUsd, 6),
                'amounts' => [],
            ];
        }

        $totalPercentage = round(
            (float) $participants->sum(fn($participant) => (float) data_get($participant, $percentageField, 0)),
            6
        );

        if (abs($totalPercentage - 100.0) > $percentageTolerance) {
            return [
                'valid' => false,
                'total_percentage' => $totalPercentage,
                'sum_allocated' => 0.0,
                'difference' => round($grossAmountUsd, 6),
                'amounts' => [],
            ];
        }

        $amounts = [];
        $running = 0.0;
        $lastIndex = $participants->count() - 1;

        foreach ($participants as $index => $participant) {
            $percentage = (float) data_get($participant, $percentageField, 0);

            if ($index < $lastIndex) {
                $allocated = round($grossAmountUsd * ($percentage / 100), 6);
                $running = round($running + $allocated, 6);
            } else {
                // Force exact closure to line gross at 6 decimals.
                $allocated = round($grossAmountUsd - $running, 6);
                $running = round($running + $allocated, 6);
            }

            $amounts[] = [
                'participant' => $participant,
                'percentage' => $percentage,
                'allocated' => $allocated,
            ];
        }

        $difference = round($grossAmountUsd - $running, 6);

        return [
            'valid' => abs($difference) < 0.000001,
            'total_percentage' => $totalPercentage,
            'sum_allocated' => $running,
            'difference' => $difference,
            'amounts' => $amounts,
        ];
    }
}

