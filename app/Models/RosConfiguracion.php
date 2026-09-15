<?php

namespace App\Models;

use App\Traits\BelongsToSede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RosConfiguracion extends Model
{
    use BelongsToSede;

    protected $table = 'ros_configuraciones';

    protected $primaryKey = 'RosConfiguracionID';

    protected $guarded = ['RosConfiguracionID'];

    protected $casts = ['UmbralRop' => 'decimal:2'];

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'SedeID', 'SedeID');
    }
}
