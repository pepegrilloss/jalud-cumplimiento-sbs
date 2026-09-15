<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CumplimientoAccessSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (config('filament-shield.custom_permissions', []) as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdmin = Role::findOrCreate('super_admin', 'web');
        $superAdmin->syncPermissions(Permission::all());

        $oficial = Role::findOrCreate('oficial_cumplimiento_sbs', 'web');
        $oficial->syncPermissions(Permission::query()->whereIn('name', [
            'acceder_cumplimiento_sbs', 'ver_todos_los_casos_sbs', 'gestionar_catalogos_sbs',
            'ver_declaraciones_trabajador', 'crear_declaraciones_trabajador', 'editar_declaraciones_trabajador',
            'finalizar_declaraciones_trabajador', 'versionar_declaraciones_trabajador',
            'ver_declaraciones_cliente', 'crear_declaraciones_cliente', 'editar_declaraciones_cliente',
            'finalizar_declaraciones_cliente', 'versionar_declaraciones_cliente',
            'generar_pdf_declaraciones', 'descargar_declaraciones',
            'view_declaracion::cliente', 'view_any_declaracion::cliente', 'create_declaracion::cliente', 'update_declaracion::cliente',
            'view_declaracion::trabajador', 'view_any_declaracion::trabajador', 'create_declaracion::trabajador', 'update_declaracion::trabajador',
            'view_ros::caso', 'view_any_ros::caso', 'create_ros::caso', 'update_ros::caso',
            'view_rop::registro', 'view_any_rop::registro', 'sincronizar_rop', 'completar_rop', 'calificar_rop',
            'cerrar_periodo_rop', 'generar_ros_desde_rop',
        ])->get());

        $consulta = Role::findOrCreate('consulta_cumplimiento_sbs', 'web');
        $consulta->syncPermissions(Permission::query()->whereIn('name', [
            'acceder_cumplimiento_sbs', 'ver_declaraciones_trabajador', 'ver_declaraciones_cliente',
            'descargar_declaraciones', 'view_declaracion::cliente', 'view_any_declaracion::cliente',
            'view_declaracion::trabajador', 'view_any_declaracion::trabajador',
            'view_ros::caso', 'view_any_ros::caso',
            'view_rop::registro', 'view_any_rop::registro',
        ])->get());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
