# Jalud Cumplimiento SBS

Aplicacion independiente del panel de Cumplimiento SBS de Jalud.

## Alcance

- Usa la misma base de datos de Jalud principal.
- Usa la misma tabla `users`, roles y permisos SBS.
- Usa las tablas `ros_*` existentes.
- Expone unicamente el panel Filament `/cumplimiento`.
- No contiene pantallas de creditos, pagos, tesoreria ni operaciones de sedes.

## Sesion compartida

En produccion, ambos proyectos deben tener el mismo `APP_KEY`, `SESSION_COOKIE`, `SESSION_DRIVER=database` y la misma tabla `sessions`. Si estan en subdominios, usar por ejemplo `SESSION_DOMAIN=.jalud.com.pe`.

Nunca cambiar `APP_KEY` en uno solo de los proyectos.

## Despliegue

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan permission:cache-reset
```

El proyecto tiene su propia tabla de control de migraciones (`migrations_cumplimiento`). Sus migraciones ROS son idempotentes y no recrean tablas existentes.

## URL local

```text
http://localhost:84/cumplimiento/login
```

## Declaraciones juradas

El panel incorpora dos recursos independientes:

- `/cumplimiento/declaraciones-trabajadores`: declaración del trabajador, antecedentes laborales e información patrimonial.
- `/cumplimiento/declaraciones-clientes`: conocimiento del cliente bajo el régimen general, incluyendo PEP y beneficiario de la operación.

Las declaraciones se guardan inicialmente como borrador. Al finalizarse quedan bloqueadas; cualquier corrección genera una nueva versión vinculada con la anterior. Los PDF se almacenan en el disco privado `local`, con hash y metadatos en `dj_documentos`, y solo pueden verse o descargarse mediante rutas autenticadas y autorizadas.

Para registrar los permisos del módulo:

```bash
php artisan db:seed --class=CumplimientoSbsPermisosSeeder --force
php artisan permission:cache-reset
```
