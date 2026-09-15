<?php

namespace App\Services\Rop;

readonly class RopDetectionResult
{
    public function __construct(
        public int $creados = 0,
        public int $duplicados = 0,
        public int $periodosCerrados = 0,
        public int $sedesSinConfiguracion = 0,
    ) {}
}
