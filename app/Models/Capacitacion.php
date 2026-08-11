<?php
namespace App\Models;
class Capacitacion extends DetalleDeclaracionTrabajador { protected $table = 'dj_capacitaciones'; protected $casts = ['FechaCapacitacion' => 'date']; }
