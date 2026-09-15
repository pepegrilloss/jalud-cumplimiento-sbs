<?php

namespace App\Services\Rop;

class RopDetectionRule
{
    public function qualifies(float $amount, float $threshold): bool
    {
        return $amount >= $threshold;
    }
}
