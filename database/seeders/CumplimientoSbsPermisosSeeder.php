<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CumplimientoSbsPermisosSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'acceder_cumplimiento_sbs',
            'ver_todos_los_casos_sbs',
            'gestionar_catalogos_sbs',
            'ver_declaraciones_trabajador',
            'crear_declaraciones_trabajador',
            'editar_declaraciones_trabajador',
            'finalizar_declaraciones_trabajador',
            'versionar_declaraciones_trabajador',
            'ver_declaraciones_cliente',
            'crear_declaraciones_cliente',
            'editar_declaraciones_cliente',
            'finalizar_declaraciones_cliente',
            'versionar_declaraciones_cliente',
            'generar_pdf_declaraciones',
            'descargar_declaraciones',
        ];

        foreach ($permisos as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }

        $rol = Role::findOrCreate('oficial_cumplimiento_sbs', 'web');
        $rol->syncPermissions($permisos);
    }
}
