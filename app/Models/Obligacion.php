<?php
namespace App\Models;
class Obligacion extends DetalleDeclaracionTrabajador { protected $table = 'dj_obligaciones'; protected $casts = ['Monto' => 'decimal:2']; }
