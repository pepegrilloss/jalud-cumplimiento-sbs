<?php
namespace App\Models;
class EducacionEscolar extends DetalleDeclaracionTrabajador { protected $table = 'dj_educacion_escolar'; protected $casts = ['Concluida' => 'boolean']; }
