<?php

namespace App\Services;

use App\Helpers\UsuarioHelper;
use App\Models\AuditLog;
use App\Models\Cliente;
use App\Models\EnlaceCliente;
use App\Models\RegistroCliente;
use App\Support\Pii;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuditLogger
{
    private const HIDDEN_FIELDS = [
        'password',
        'remember_token',
        'APP_KEY',
        'token',
        'api_token',
        'TOKEN_HASH',
        'TOKEN_CIFRADO',
        'SUBMISSION_KEY',
        'CONTRASEÑA',
        'CONTRASEÃ‘A',
    ];

    private const MASKED_FIELDS = [
        'DNI',
        'DOCUMENTO',
        'DOCUMENTO_IDENTIDAD',
    ];

    private const PATH_FIELDS = [
        'DNI_PATH',
        'SCTR_PATH',
        'DOCUMENTO_GENERAL',
    ];

    public static function modelCreated(Model $model): void
    {
        self::writeForModel($model, 'modelo_creado', 'CREAR', [], self::modelAttributes($model));
    }

    public static function modelUpdated(Model $model): void
    {
        $changes = Arr::except($model->getChanges(), ['UPDATED_AT']);

        if ($changes === []) {
            return;
        }

        $oldValues = [];
        foreach (array_keys($changes) as $field) {
            $oldValues[$field] = $model->getOriginal($field);
        }

        self::writeForModel($model, 'modelo_actualizado', 'ACTUALIZAR', $oldValues, $changes);
    }

    public static function modelDeleted(Model $model): void
    {
        self::writeForModel($model, 'modelo_eliminado', 'ELIMINAR', self::modelAttributes($model), []);
    }

    public static function event(string $event, string $action, ?string $summary = null, array $metadata = []): void
    {
        self::eventForUser(auth()->user(), $event, $action, $summary, $metadata);
    }

    public static function eventForUser(?Authenticatable $user, string $event, string $action, ?string $summary = null, array $metadata = []): void
    {
        self::write([
            'EVENTO' => $event,
            'ACCION' => $action,
            'RESUMEN' => $summary,
            'METADATA' => self::sanitize($metadata),
        ], $user);
    }

    private static function writeForModel(Model $model, string $event, string $action, array $oldValues, array $newValues): void
    {
        self::write([
            'EVENTO' => $event,
            'ACCION' => $action,
            'MODELO' => $model::class,
            'TABLA' => $model->getTable(),
            'ID_REGISTRO' => (string) $model->getKey(),
            'EMPRESA' => self::resolveEmpresa($model),
            'RESUMEN' => self::summaryFor($model, $action),
            'VALORES_ANTERIORES' => self::sanitize($oldValues),
            'VALORES_NUEVOS' => self::sanitize($newValues),
            'METADATA' => [
                'primary_key' => $model->getKeyName(),
            ],
        ]);
    }

    private static function write(array $data, ?Authenticatable $actor = null): void
    {
        try {
            AuditLog::withoutEvents(function () use ($data, $actor): void {
                AuditLog::create(array_merge(
                    self::requestContext(),
                    self::actorContext($actor),
                    ['CREATED_AT' => now()],
                    $data,
                ));
            });
        } catch (Throwable $exception) {
            Log::error('audit-log-write-failed', [
                'event' => $data['EVENTO'] ?? null,
                'action' => $data['ACCION'] ?? null,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private static function actorContext(?Authenticatable $actor = null): array
    {
        $user = $actor ?: auth()->user();

        return [
            'ID_MP_LGN_USUARIO' => $user?->ID_MP_LGN_USUARIO,
            'USUARIO' => $user?->USUARIO ?? $user?->name ?? $user?->email,
            'AREA' => self::areaName($user),
        ];
    }

    private static function requestContext(): array
    {
        if (!app()->bound('request')) {
            return [];
        }

        $request = request();
        $url = $request->routeIs('registro-publico.*')
            ? rtrim((string) config('app.url'), '/').'/registro/[token-oculto]'
            : $request->url();

        return [
            'IP' => $request->ip(),
            'METODO' => $request->method(),
            'URL' => $url,
            'USER_AGENT' => substr((string) $request->userAgent(), 0, 255),
        ];
    }

    private static function areaName(?Authenticatable $user = null): ?string
    {
        $areaId = $user
            ? DB::table('MP_LGN_USUARIOS_REGISTRADOS')
                ->where('ID_MP_LGN_USUARIO', $user->ID_MP_LGN_USUARIO)
                ->value('ID_MP_LGN_AREA')
            : UsuarioHelper::areaId();

        if (!$areaId) {
            return null;
        }

        return DB::table('MP_LGN_AREA')
            ->where('ID_MP_LGN_AREA', $areaId)
            ->value('AREA');
    }

    private static function modelAttributes(Model $model): array
    {
        return $model->getAttributes();
    }

    private static function sanitize(array $values): array
    {
        foreach ($values as $key => $value) {
            $normalizedKey = strtoupper((string) $key);

            if (is_array($value)) {
                $values[$key] = self::sanitize($value);
                continue;
            }

            if (in_array($normalizedKey, array_map('strtoupper', self::HIDDEN_FIELDS), true)) {
                $values[$key] = '[OCULTO]';
                continue;
            }

            if (in_array($normalizedKey, self::MASKED_FIELDS, true)) {
                $values[$key] = Pii::maskDni(is_scalar($value) ? (string) $value : null);
                continue;
            }

            if (in_array($normalizedKey, self::PATH_FIELDS, true)) {
                $values[$key] = self::sanitizePathValue($value);
            }
        }

        return $values;
    }

    private static function sanitizePathValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($item) => self::sanitizePathValue($item), $value);
        }

        if (!is_string($value) || $value === '') {
            return $value;
        }

        return dirname($value).'/[archivo]';
    }

    private static function summaryFor(Model $model, string $action): string
    {
        return match ($model->getTable()) {
            'MP_VST_CLIENTE' => "{$action} cliente",
            'MP_VST_ENLACE_CLIENTE' => "{$action} enlace de registro",
            'MP_VST_REGISTRO_CLIENTE' => "{$action} DNI recibido",
            'MP_VST_VISITA' => "{$action} solicitud de visita",
            'MP_VST_PERSONAL' => "{$action} personal ingresante",
            'MP_VST_APROBACION' => "{$action} aprobación de visita",
            'MP_VST_REGISTRO_INGRESO' => "{$action} control de ingreso/salida",
            'MP_VST_CHECKLIST' => "{$action} checklist de seguridad",
            'MP_VST_SCTR_VIGENCIA' => "{$action} vigencia SCTR",
            'MP_VST_HISTORIAL_APROBACION' => "{$action} historial de aprobación",
            default => "{$action} {$model->getTable()}",
        };
    }

    private static function resolveEmpresa(Model $model): ?string
    {
        if ($model instanceof Cliente) {
            return $model->RAZON_SOCIAL;
        }

        if ($model instanceof EnlaceCliente) {
            return $model->cliente?->RAZON_SOCIAL;
        }

        if ($model instanceof RegistroCliente) {
            return $model->enlace?->cliente?->RAZON_SOCIAL;
        }

        return null;
    }
}
