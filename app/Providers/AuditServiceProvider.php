<?php

namespace App\Providers;

use App\Models\RosAdjunto;
use App\Models\RosCaso;
use App\Models\RosOperacion;
use App\Models\RosPersona;
use App\Models\RosSenalAlerta;
use App\Models\RosTipologia;
use App\Observers\RosAuditObserver;
use App\Observers\DeclaracionAuditObserver;
use App\Models\DeclaracionTrabajador;
use App\Models\DeclaracionCliente;
use App\Models\DeclaracionDocumento;
use App\Models\Estudio;
use App\Models\EducacionEscolar;
use App\Models\OtroEstudio;
use App\Models\ExperienciaLaboral;
use App\Models\Capacitacion;
use App\Models\BienInmueble;
use App\Models\Vehiculo;
use App\Models\OtroBien;
use App\Models\Inversion;
use App\Models\Obligacion;
use App\Models\PepFamiliar;
use App\Models\PepRelacionado;
use Illuminate\Support\ServiceProvider;

class AuditServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach ([RosCaso::class, RosPersona::class, RosOperacion::class, RosSenalAlerta::class, RosTipologia::class, RosAdjunto::class] as $model) {
            $model::observe(RosAuditObserver::class);
        }

        foreach ([DeclaracionTrabajador::class, DeclaracionCliente::class, DeclaracionDocumento::class, Estudio::class,
            EducacionEscolar::class, OtroEstudio::class, ExperienciaLaboral::class, Capacitacion::class,
            BienInmueble::class, Vehiculo::class, OtroBien::class, Inversion::class, Obligacion::class,
            PepFamiliar::class, PepRelacionado::class] as $model) {
            $model::observe(DeclaracionAuditObserver::class);
        }
    }
}
