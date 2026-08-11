<?php
namespace App\Models;
class OtroBien extends DetalleDeclaracionTrabajador { protected $table = 'dj_otros_bienes'; protected $casts = ['Valor' => 'decimal:2', 'EsRegistrable' => 'boolean']; }
