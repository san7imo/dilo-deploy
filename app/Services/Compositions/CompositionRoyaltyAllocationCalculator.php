<?php

namespace App\Services\Compositions;

use App\Services\Royalties\SplitAllocationCalculator;
use Illuminate\Support\Collection;

class CompositionRoyaltyAllocationCalculator
{
    public function __construct(
        private readonly SplitAllocationCalculator $splitCalculator
    ) {
    }

    /**
     * @param Collection<int, object> $writerParticipants
     * @param Collection<int, object> $publisherParticipants
     * @return array<string, mixed>
     */
    public function allocatePerformance(
        float $grossAmountUsd,
        Collection $writerParticipants,
        Collection $publisherParticipants
    ): array {
        $writerGross = round($grossAmountUsd * 0.5, 6);
        $publisherGross = round($grossAmountUsd - $writerGross, 6);

        $writer = $this->splitCalculator->allocate($writerGross, $writerParticipants, 'percentage');
        $publisher = $this->splitCalculator->allocate($publisherGross, $publisherParticipants, 'percentage');

        if (!$writer['valid'] || !$publisher['valid']) {
            return [
                'valid' => false,
                'writer' => $writer,
                'publisher' => $publisher,
                'allocations' => [],
            ];
        }

        return [
            'valid' => true,
            'writer' => $writer,
            'publisher' => $publisher,
            'allocations' => [
                'writer' => $writer['amounts'],
                'publisher' => $publisher['amounts'],
            ],
        ];
    }

    /**
     * @param Collection<int, object> $mechanicalParticipants
     * @return array<string, mixed>
     */
    public function allocateMechanical(
        float $grossAmountUsd,
        Collection $mechanicalParticipants
    ): array {
        $mechanical = $this->splitCalculator->allocate($grossAmountUsd, $mechanicalParticipants, 'percentage');

        if (!$mechanical['valid']) {
            return [
                'valid' => false,
                'mechanical' => $mechanical,
                'allocations' => [],
            ];
        }

        return [
            'valid' => true,
            'mechanical' => $mechanical,
            'allocations' => [
                'mechanical_payee' => $mechanical['amounts'],
            ],
        ];
    }
}

