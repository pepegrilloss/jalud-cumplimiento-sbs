<?php

namespace App\Services\Declaraciones;

use App\Models\Cliente;
use App\Models\LogAuditoria;
use App\Models\Trabajador;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EstadoPersonaAuditService
{
    /**
     * Registra una transición de estado. No debe usarse para actualizaciones
     * ordinarias: cada llamada deja una evidencia inmutable en la bitácora.
     */
    public function cambiar(Model $persona, bool $activo, string $motivo): void
    {
        $this->autorizar($persona);

        if ((bool) $persona->Activo === $activo) {
            return;
        }

        DB::transaction(function () use ($persona, $activo, $motivo): void {
            $anterior = (bool) $persona->Activo;
            $persona->update(['Activo' => $activo]);

            LogAuditoria::withoutEvents(fn () => LogAuditoria::create([
                'user_id' => auth()->id(),
                'accion' => $activo ? 'REACTIVAR' : 'DAR_DE_BAJA',
                'modelo' => class_basename($persona),
                'modelo_id' => $persona->getKey(),
                'old_values' => [
                    'activo' => $anterior,
                    'situacion' => $anterior ? 'Activo' : 'De baja',
                ],
                'new_values' => [
                    'activo' => $activo,
                    'situacion' => $activo ? 'Activo' : 'De baja',
                    'motivo' => trim($motivo),
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => mb_substr((string) request()?->userAgent(), 0, 255),
                'platform' => PHP_OS_FAMILY,
                'created_at' => now(),
                'SedeID' => $persona->SedeID,
            ]));
        });
    }

    private function autorizar(Model $persona): void
    {
        $permiso = match (true) {
            $persona instanceof Trabajador => 'editar_declaraciones_trabajador',
            $persona instanceof Cliente => 'editar_declaraciones_cliente',
            default => throw new \InvalidArgumentException('Modelo no auditable para este cambio de estado.'),
        };

        if (! auth()->user()?->hasRole('super_admin') && ! auth()->user()?->can($permiso)) {
            throw new AuthorizationException('No tiene permiso para cambiar este estado.');
        }
    }
}
