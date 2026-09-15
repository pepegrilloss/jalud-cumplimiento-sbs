# Registro de Operaciones Sospechosas

## Puesta en marcha

Ejecutar en el entorno de la aplicación:

```bash
php artisan migrate
php artisan db:seed --class=RosCatalogosSeeder
php artisan optimize:clear
```

La migración es aditiva y conserva los casos ROS existentes. El seeder obtiene los catálogos directamente de `resources/templates/ros/modelo-ros-sbs.xls`.

## Flujo operativo

1. Crear el caso en borrador y vincular cliente, crédito o pago cuando corresponda.
2. Completar empresa informante, personas, operaciones, análisis, señales y sustentos.
3. Enviar el caso a evaluación.
4. Aprobarlo para ROS o descartarlo con fundamento.
5. Descargar el PDF o el Excel de preparación.
6. Realizar el envío en ROSEL.
7. Registrar número, fecha y constancia del envío. El caso queda bloqueado.

## Alcance de seguridad

La implementación no agrega, elimina ni modifica permisos, roles, políticas o seeders de autorización. Reutiliza los controles ya existentes en el sistema.

Los adjuntos, constancias y reportes temporales se guardan en el disco `local`. Las descargas pasan por rutas autenticadas y la política existente del caso ROS.

## Verificación

```bash
php artisan test --filter=Ros
```

Validar también en un ambiente de pruebas la creación, las cinco transiciones, el aislamiento por sede, la generación PDF/Excel y la descarga de evidencias antes de promover a producción.
