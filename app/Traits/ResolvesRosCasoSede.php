<?php

namespace App\Traits;

use App\Models\RosCaso;
use App\Models\RosPersona;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

trait ResolvesRosCasoSede
{
    public static function bootResolvesRosCasoSede(): void
    {
        static::saving(function (Model $model): void {
            if (empty($model->RosCasoID) && $model->getAttribute('RosPersonaID')) {
                $persona = RosPersona::withoutGlobalScope('sede')
                    ->whereKey($model->getAttribute('RosPersonaID'))
                    ->first(['RosCasoID']);

                if ($persona) {
                    $model->RosCasoID = $persona->RosCasoID;
                }
            }

            if (empty($model->RosCasoID)) {
                return;
            }

            $caso = RosCaso::withoutGlobalScope('sede')
                ->whereKey($model->RosCasoID)
                ->first(['SedeID', 'Estado', 'Bloqueado']);

            if (!$caso) {
                throw ValidationException::withMessages([
                    'RosCasoID' => 'El caso ROS relacionado no existe.',
                ]);
            }

            if ($caso->Estado === RosCaso::ESTADO_REPORTADO || $caso->Bloqueado) {
                throw ValidationException::withMessages([
                    'RosCasoID' => 'No se puede modificar información de un caso ROS reportado.',
                ]);
            }

            $model->SedeID = $caso->SedeID;
        });

        static::deleting(function (Model $model): void {
            $rosCasoId = $model->RosCasoID;

            if (!$rosCasoId && $model->getAttribute('RosPersonaID')) {
                $rosCasoId = RosPersona::withoutGlobalScope('sede')
                    ->whereKey($model->getAttribute('RosPersonaID'))
                    ->value('RosCasoID');
            }

            if (!$rosCasoId) {
                return;
            }

            $bloqueado = RosCaso::withoutGlobalScope('sede')
                ->whereKey($rosCasoId)
                ->where(fn ($query) => $query->where('Estado', RosCaso::ESTADO_REPORTADO)->orWhere('Bloqueado', true))
                ->exists();

            if ($bloqueado) {
                throw ValidationException::withMessages([
                    'RosCasoID' => 'No se puede eliminar información de un caso ROS reportado.',
                ]);
            }
        });
    }
}
