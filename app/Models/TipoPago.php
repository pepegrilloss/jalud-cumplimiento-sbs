<?php

namespace App\Models;

class TipoPago extends JaludReadModel
{
    protected $table = 'TipoPago';

    protected $primaryKey = 'TipoPagoID';

    public $timestamps = false;

    protected $guarded = [];
}
