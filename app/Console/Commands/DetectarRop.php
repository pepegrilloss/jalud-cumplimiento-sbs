<?php

namespace App\Console\Commands;

use App\Services\Rop\RopDetectionService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class DetectarRop extends Command
{
    protected $signature = 'rop:detectar {--sede=} {--desde=} {--hasta=}';

    protected $description = 'Incorpora al ROP los créditos de JALUD que superan el umbral configurado';

    public function handle(RopDetectionService $detector): int
    {
        $desde = $this->option('desde') ? CarbonImmutable::parse($this->option('desde'))->startOfDay() : null;
        $hasta = $this->option('hasta') ? CarbonImmutable::parse($this->option('hasta'))->endOfDay() : null;
        $sede = $this->option('sede') ? (int) $this->option('sede') : null;
        $resultado = $detector->detectar($desde, $hasta, $sede);

        $this->info("ROP creados: {$resultado->creados}");
        $this->line("Ya existentes: {$resultado->duplicados}");
        $this->line("Omitidos por período cerrado: {$resultado->periodosCerrados}");
        $this->line("Sedes sin configuración: {$resultado->sedesSinConfiguracion}");

        return self::SUCCESS;
    }
}
