<?php
namespace App\Models;
class Estudio extends DetalleDeclaracionTrabajador { protected $table = 'dj_estudios'; protected $casts = ['FechaExpedicion' => 'date']; }
