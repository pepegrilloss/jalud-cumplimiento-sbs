<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\JefeArea;
use Illuminate\Database\Seeder;

class JefeAreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            'RECEPCION',
            'PRODUCCION',
            'EXPEDICIONES',
            'ALMACEN',
            'MANTENIMIENTO',
            'ADMINISTRATIVOS',
            'ASEGURAMIENTO DE CALIDAD',
            'GERENCIA',
            'COMERCIAL',
        ];

        foreach ($areas as $nombre) {
            $area = Area::where('AREA', $nombre)->first();
            if (!$area) {
                continue;
            }

            JefeArea::firstOrCreate(
                ['ID_MP_LGN_AREA' => $area->ID_MP_LGN_AREA],
                [
                    'NOMBRE' => 'Julio Vilcherrez',
                    'EMAIL' => 'julio.vilcherrez@medlogpiura.pe',
                ],
            );
        }
    }
}
