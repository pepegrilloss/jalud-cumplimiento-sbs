<?php

return [
    'shield_resource' => [
        'should_register_navigation' => true,
        'slug' => 'shield/roles',
        'navigation_sort' => -1,
        'navigation_badge' => true,
        'navigation_group' => true,
        'sub_navigation_position' => null,
        'is_globally_searchable' => false,
        'show_model_path' => true,
        'is_scoped_to_tenant' => false,
        'cluster' => null,
    ],

    'tenant_model' => null,

    'auth_provider_model' => [
        'fqcn' => 'App\\Models\\User',
    ],

    'super_admin' => [
        'enabled' => true,
        'name' => 'super_admin',
        'define_via_gate' => false,
        'intercept_gate' => 'after',
    ],

    'panel_user' => [
        'enabled' => true,
        'name' => 'panel_user',
    ],

    'permission_prefixes' => [
        'resource' => [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
        ],

        'page' => 'page',
        'widget' => 'widget',
    ],

    'entities' => [
        'pages' => true,
        'widgets' => true,
        'resources' => true,
        'custom_permissions' => true,
    ],

    'custom_permissions' => [
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
        'view_rop::registro',
        'view_any_rop::registro',
        'sincronizar_rop',
        'completar_rop',
        'calificar_rop',
        'cerrar_periodo_rop',
        'generar_ros_desde_rop',
        'descargar_reportes_rop_ros',
    ],

    'generator' => [
        'option' => 'policies_and_permissions',
        'policy_directory' => 'Policies',
        'policy_namespace' => 'Policies',
    ],

    'exclude' => [
        'enabled' => true,

        'pages' => [
            'Dashboard',
            'SelectSede',
            'EvaluacionDeCredito',
            'Mantenimiento',
        ],

        'widgets' => [
            'AccountWidget',
            'FilamentInfoWidget',
        ],

        'resources' => [
            'CrearProposicionCreditoResource',
        ],
    ],

    'discovery' => [
        'discover_all_resources' => false,
        'discover_all_widgets' => false,
        'discover_all_pages' => false,
    ],

    'register_role_policy' => [
        'enabled' => true,
    ],

];
