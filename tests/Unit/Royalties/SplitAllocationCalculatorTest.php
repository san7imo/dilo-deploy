<?php

namespace Tests\Unit\Royalties;

use App\Services\Royalties\SplitAllocationCalculator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class SplitAllocationCalculatorTest extends TestCase
{
    public function test_it_allocates_positive_amount_and_closes_exactly(): void
    {
        $calculator = new SplitAllocationCalculator();
        $participants = new Collection([
            (object) ['id' => 1, 'percentage' => 33.33],
            (object) ['id' => 2, 'percentage' => 33.33],
            (object) ['id' => 3, 'percentage' => 33.34],
        ]);

        $result = $calculator->allocate(12.34, $participants);

        $this->assertTrue($result['valid']);
        $this->assertSame(100.0, round($result['total_percentage'], 2));
        $this->assertSame(12.34, round($result['sum_allocated'], 2));
        $this->assertSame(0.0, $result['difference']);
    }

    public function test_it_allocates_negative_amount_and_closes_exactly(): void
    {
        $calculator = new SplitAllocationCalculator();
        $participants = new Collection([
            (object) ['id' => 1, 'percentage' => 70],
            (object) ['id' => 2, 'percentage' => 30],
        ]);

        $result = $calculator->allocate(-1.00, $participants);

        $this->assertTrue($result['valid']);
        $this->assertSame(-1.0, $result['sum_allocated']);
        $this->assertSame(0.0, $result['difference']);
        $this->assertSame(-0.7, $result['amounts'][0]['allocated']);
        $this->assertSame(-0.3, $result['amounts'][1]['allocated']);
    }

    public function test_it_fails_when_percentage_sum_is_not_100(): void
    {
        $calculator = new SplitAllocationCalculator();
        $participants = new Collection([
            (object) ['id' => 1, 'percentage' => 60],
            (object) ['id' => 2, 'percentage' => 30],
        ]);

        $result = $calculator->allocate(10.00, $participants);

        $this->assertFalse($result['valid']);
        $this->assertSame(90.0, $result['total_percentage']);
        $this->assertSame([], $result['amounts']);
    }
}

