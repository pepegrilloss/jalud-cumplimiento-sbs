<?php
namespace App\Models;
class Inversion extends DetalleDeclaracionTrabajador { protected $table = 'dj_inversiones'; protected $casts = ['Valor' => 'decimal:2']; }
