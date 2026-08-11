<?php

namespace App\Observers;

use App\Models\LogAuditoria;
use Illuminate\Database\Eloquent\Model;

class DeclaracionAuditObserver
{
    public function created(Model $model): void { $this->registrar('CREAR', $model, array_keys($model->getAttributes())); }
    public function updated(Model $model): void { $this->registrar('ACTUALIZAR', $model, array_keys($model->getChanges())); }
    public function deleted(Model $model): void { $this->registrar('ELIMINAR', $model, []); }

    private function registrar(string $accion, Model $model, array $campos): void
    {
        if (! auth()->id()) { return; }
        $sedeId = $model->SedeID ?? $model->declaracion?->SedeID ?? null;
        LogAuditoria::withoutEvents(fn () => LogAuditoria::create([
            'user_id' => auth()->id(), 'accion' => $accion, 'modelo' => class_basename($model), 'modelo_id' => $model->getKey(),
            'old_values' => [], 'new_values' => ['campos_modificados' => array_values(array_diff($campos, ['updated_at', 'created_at']))],
            'ip_address' => request()?->ip(), 'user_agent' => mb_substr((string) request()?->userAgent(), 0, 255),
            'platform' => PHP_OS_FAMILY, 'created_at' => now(), 'SedeID' => $sedeId,
        ]));
    }

}
