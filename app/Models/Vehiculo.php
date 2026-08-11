<?php
namespace App\Models;
class Vehiculo extends DetalleDeclaracionTrabajador { protected $table = 'dj_vehiculos'; protected $casts = ['Valor' => 'decimal:2']; }
