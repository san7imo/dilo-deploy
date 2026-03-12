<?php

namespace Tests\Unit\Royalties;

use App\Services\Compositions\CompositionSplitPoolValidator;
use PHPUnit\Framework\TestCase;

class CompositionSplitPoolValidatorTest extends TestCase
{
    public function test_it_accepts_valid_three_pool_payload(): void
    {
        $validator = new CompositionSplitPoolValidator();

        $errors = $validator->validate([
            ['share_pool' => 'writer', 'percentage' => 60],
            ['share_pool' => 'writer', 'percentage' => 40],
            ['share_pool' => 'publisher', 'percentage' => 100],
            ['share_pool' => 'mechanical_payee', 'percentage' => 100],
        ]);

        $this->assertSame([], $errors);
    }

    public function test_it_returns_errors_when_any_pool_is_missing_or_not_100(): void
    {
        $validator = new CompositionSplitPoolValidator();

        $errors = $validator->validate([
            ['share_pool' => 'writer', 'percentage' => 70],
            ['share_pool' => 'publisher', 'percentage' => 100],
        ]);

        $this->assertArrayHasKey('writer', $errors);
        $this->assertArrayHasKey('mechanical_payee', $errors);
        $this->assertStringContainsString('100.00%', $errors['writer']);
    }
}

