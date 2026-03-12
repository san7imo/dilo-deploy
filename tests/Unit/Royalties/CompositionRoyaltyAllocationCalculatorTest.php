<?php

namespace Tests\Unit\Royalties;

use App\Services\Compositions\CompositionRoyaltyAllocationCalculator;
use App\Services\Royalties\SplitAllocationCalculator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class CompositionRoyaltyAllocationCalculatorTest extends TestCase
{
    public function test_performance_is_split_50_50_between_writer_and_publisher_pools(): void
    {
        $calculator = new CompositionRoyaltyAllocationCalculator(new SplitAllocationCalculator());

        $writers = new Collection([
            (object) ['id' => 1, 'percentage' => 100],
        ]);
        $publishers = new Collection([
            (object) ['id' => 2, 'percentage' => 100],
        ]);

        $result = $calculator->allocatePerformance(100.00, $writers, $publishers);

        $this->assertTrue($result['valid']);
        $this->assertSame(50.0, $result['allocations']['writer'][0]['allocated']);
        $this->assertSame(50.0, $result['allocations']['publisher'][0]['allocated']);
    }

    public function test_mechanical_is_allocated_from_mechanical_pool_only(): void
    {
        $calculator = new CompositionRoyaltyAllocationCalculator(new SplitAllocationCalculator());

        $mechanicals = new Collection([
            (object) ['id' => 1, 'percentage' => 60],
            (object) ['id' => 2, 'percentage' => 40],
        ]);

        $result = $calculator->allocateMechanical(10.00, $mechanicals);

        $this->assertTrue($result['valid']);
        $this->assertSame(6.0, $result['allocations']['mechanical_payee'][0]['allocated']);
        $this->assertSame(4.0, $result['allocations']['mechanical_payee'][1]['allocated']);
    }
}

