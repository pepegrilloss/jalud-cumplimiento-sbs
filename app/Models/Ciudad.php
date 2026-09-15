<?php

namespace App\Models;

class Ciudad extends JaludReadModel
{
    protected $table = 'Ciudad';

    protected $primaryKey = 'CiudadID';

    public $timestamps = false;

    protected $guarded = [];
}
