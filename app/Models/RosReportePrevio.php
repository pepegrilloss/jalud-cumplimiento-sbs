<?php

namespace App\Models;

use App\Traits\BelongsToSede;
use App\Traits\ResolvesRosCasoSede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RosReportePrevio extends Model
{
    use BelongsToSede, ResolvesRosCasoSede;

    protected $table = 'ros_reportes_previos';
    protected $primaryKey = 'RosReportePrevioID';
    protected $guarded = ['RosReportePrevioID'];
    protected $casts = ['FechaReporte' => 'date'];

    public function caso(): BelongsTo
    {
        return $this->belongsTo(RosCaso::class, 'RosCasoID', 'RosCasoID');
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(RosPersona::class, 'RosPersonaID', 'RosPersonaID');
    }
}
