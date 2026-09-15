<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/** Data from Jalud operational database. It is never writable here. */
abstract class JaludReadModel extends Model
{
    protected $connection = 'jalud';

    public function save(array $options = []): bool
    {
        throw new LogicException('Los datos de Jalud son de solo lectura desde Cumplimiento.');
    }

    public function delete(): ?bool
    {
        throw new LogicException('Los datos de Jalud son de solo lectura desde Cumplimiento.');
    }
}
