<?php

namespace App\Models;

class TipoCredito extends JaludReadModel
{
    protected $table = 'TipoCredito';

    protected $primaryKey = 'TipoCreditoID';

    public $timestamps = false;

    protected $guarded = [];
}
