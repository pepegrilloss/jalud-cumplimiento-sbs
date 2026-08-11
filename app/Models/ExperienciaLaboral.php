<?php
namespace App\Models;
class ExperienciaLaboral extends DetalleDeclaracionTrabajador { protected $table = 'dj_experiencias_laborales'; protected $casts = ['FechaInicio' => 'date', 'FechaConclusion' => 'date']; }
