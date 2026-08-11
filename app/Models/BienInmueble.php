<?php
namespace App\Models;
class BienInmueble extends DetalleDeclaracionTrabajador { protected $table = 'dj_bienes_inmuebles'; protected $casts = ['Valor' => 'decimal:2']; }
