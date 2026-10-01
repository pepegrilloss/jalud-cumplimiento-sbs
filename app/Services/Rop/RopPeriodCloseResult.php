<?php

namespace App\Services\Rop;

use App\Models\RopPeriodo;

readonly class RopPeriodCloseResult
{
    public function __construct(
        public RopPeriodo $periodo,
        public int $pendientes,
        public int $ropCreadosDuranteCierre,
        public bool $yaEstabaCerrado = false,
    ) {}

    public function cerrado(): bool
    {
        return $this->periodo->Estado === RopPeriodo::ESTADO_CERRADO;
    }
}
