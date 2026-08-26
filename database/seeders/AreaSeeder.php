<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = ['RECEPCION', 'PRODUCCION', 'EXPEDICIONES', 'ALMACEN', 'MANTENIMIENTO', 'ADMINISTRATIVOS'];

        foreach ($areas as $nombre) {
            Area::firstOrCreate(['AREA' => $nombre]);
        }
    }
}
