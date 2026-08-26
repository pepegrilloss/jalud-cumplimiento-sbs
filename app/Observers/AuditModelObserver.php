<?php

namespace App\Observers;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

class AuditModelObserver
{
    public function created(Model $model): void
    {
        AuditLogger::modelCreated($model);
    }

    public function updated(Model $model): void
    {
        AuditLogger::modelUpdated($model);
    }

    public function deleted(Model $model): void
    {
        AuditLogger::modelDeleted($model);
    }
}
