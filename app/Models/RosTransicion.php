<?php

namespace App\Models;

use App\Traits\BelongsToSede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RosTransicion extends Model
{
    use BelongsToSede;

    protected $table = 'ros_transiciones';
    protected $primaryKey = 'RosTransicionID';
    protected $guarded = ['RosTransicionID'];
    protected $casts = ['FechaTransicion' => 'datetime'];

    public function caso(): BelongsTo
    {
        return $this->belongsTo(RosCaso::class, 'RosCasoID', 'RosCasoID');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'UserID');
    }
}
