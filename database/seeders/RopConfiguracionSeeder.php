<?php

namespace Database\Seeders;

use App\Models\RosConfiguracion;
use App\Models\Sede;
use Illuminate\Database\Seeder;

class RopConfiguracionSeeder extends Seeder
{
    public function run(): void
    {
        Sede::withoutGlobalScopes()->operativas()->pluck('SedeID')->each(function ($sedeId): void {
            RosConfiguracion::withoutGlobalScopes()->firstOrCreate(
                ['SedeID' => $sedeId],
                ['UmbralRop' => 2000, 'EnteSupervisor' => 'SBS/UIF-PERU'],
            );
        });
    }
}
