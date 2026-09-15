<?php

namespace Tests\Unit;

use App\Services\Rop\RopDetectionRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RopDetectionRuleTest extends TestCase
{
    #[DataProvider('amounts')]
    public function test_amounts_equal_to_or_above_the_configured_threshold_qualify(
        float $amount,
        float $threshold,
        bool $expected,
    ): void {
        self::assertSame($expected, (new RopDetectionRule)->qualifies($amount, $threshold));
    }

    public static function amounts(): array
    {
        return [
            'above' => [2000.01, 2000, true],
            'equal' => [2000, 2000, true],
            'below' => [1999.99, 2000, false],
        ];
    }
}
