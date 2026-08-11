<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dj_trabajadores')) {
            Schema::create('dj_trabajadores', function (Blueprint $table): void {
                $table->bigIncrements('TrabajadorID');
                $table->integer('SedeID')->index();
                $table->bigInteger('UserID')->nullable()->index();
                $table->string('TipoDocumento', 20);
                $table->string('NumeroDocumento', 30);
                $table->string('Nombres', 150);
                $table->string('ApellidoPaterno', 100);
                $table->string('ApellidoMaterno', 100)->nullable();
                $table->boolean('Activo')->default(true)->index();
                $table->timestamps();
                $table->unique(['SedeID', 'TipoDocumento', 'NumeroDocumento'], 'uq_dj_trabajador_sede_documento');
            });
        }

        if (! Schema::hasTable('dj_declaraciones_trabajador')) {
            Schema::create('dj_declaraciones_trabajador', function (Blueprint $table): void {
                $table->bigIncrements('DeclaracionTrabajadorID');
                $table->unsignedBigInteger('TrabajadorID')->index();
                $table->integer('SedeID')->index();
                $table->unsignedInteger('NumeroVersion');
                $table->string('Estado', 20)->default('BORRADOR')->index();
                $table->date('FechaContratacion');
                $table->string('Nombres', 150);
                $table->string('ApellidoPaterno', 100);
                $table->string('ApellidoMaterno', 100)->nullable();
                $table->string('TipoDocumento', 20);
                $table->string('NumeroDocumento', 30);
                $table->string('Nacionalidad', 100)->nullable();
                $table->string('Pais', 100)->nullable();
                $table->string('LugarNacimiento', 180)->nullable();
                $table->date('FechaNacimiento')->nullable();
                $table->string('EstadoCivil', 20);
                $table->string('ConyugeNombres', 150)->nullable();
                $table->string('ConyugeApellidos', 200)->nullable();
                $table->string('ProfesionOficio', 180)->nullable();
                $table->char('CodigoOcupacion', 3);
                $table->string('Cargo', 180);
                $table->string('Telefono', 50)->nullable();
                $table->string('DomicilioVia', 300);
                $table->string('DomicilioNumero', 30)->nullable();
                $table->string('DomicilioInterior', 30)->nullable();
                $table->string('DomicilioZona', 180)->nullable();
                $table->string('DomicilioDistrito', 120);
                $table->string('DomicilioProvincia', 120);
                $table->string('DomicilioDepartamento', 120);
                $table->boolean('TieneAntecedentesPoliciales')->default(false);
                $table->text('DetalleAntecedentesPoliciales')->nullable();
                $table->boolean('TieneAntecedentesPenales')->default(false);
                $table->text('DetalleAntecedentesPenales')->nullable();
                $table->unsignedSmallInteger('NumeroDependientes')->default(0);
                $table->string('CondicionVivienda', 30)->nullable();
                $table->string('CondicionViviendaOtra', 180)->nullable();
                $table->decimal('RemuneracionBrutaMensual', 14, 2)->default(0);
                $table->decimal('IngresoEjercicioIndividual', 14, 2)->default(0);
                $table->decimal('OtrosIngresosMensuales', 14, 2)->default(0);
                $table->text('DetalleOtrosIngresos')->nullable();
                $table->string('CiudadSuscripcion', 120);
                $table->date('FechaDeclaracion');
                $table->bigInteger('CreadoPorID')->index();
                $table->bigInteger('ActualizadoPorID')->nullable()->index();
                $table->bigInteger('FinalizadoPorID')->nullable()->index();
                $table->timestamp('FinalizadoEn')->nullable();
                $table->timestamps();
                $table->unique(['TrabajadorID', 'NumeroVersion'], 'uq_dj_trabajador_version');
                $table->foreign('TrabajadorID')->references('TrabajadorID')->on('dj_trabajadores');
            });
        }

        $this->crearDetalleTrabajador('dj_estudios', function (Blueprint $table): void {
            $table->string('Nivel', 40);
            $table->string('TituloGrado', 200)->nullable();
            $table->string('Especialidad', 200)->nullable();
            $table->string('CentroEstudios', 250)->nullable();
            $table->string('Ciudad', 120)->nullable();
            $table->string('Pais', 120)->nullable();
            $table->date('FechaExpedicion')->nullable();
            $table->string('EstadoEstudio', 30)->nullable();
            $table->unsignedSmallInteger('CicloActual')->nullable();
        });

        $this->crearDetalleTrabajador('dj_educacion_escolar', function (Blueprint $table): void {
            $table->string('Nivel', 20);
            $table->boolean('Concluida')->default(false);
            $table->string('UltimoGrado', 50)->nullable();
            $table->string('Colegio', 200)->nullable();
            $table->string('Ciudad', 120)->nullable();
            $table->string('Pais', 120)->nullable();
            $table->unsignedSmallInteger('AnioInicio')->nullable();
            $table->unsignedSmallInteger('AnioTermino')->nullable();
        });

        $this->crearDetalleTrabajador('dj_otros_estudios', function (Blueprint $table): void {
            $table->string('Tipo', 50);
            $table->string('Descripcion', 250);
            $table->string('Institucion', 250)->nullable();
        });

        $this->crearDetalleTrabajador('dj_experiencias_laborales', function (Blueprint $table): void {
            $table->string('EmpresaEntidad', 250);
            $table->string('CargoDesempenado', 200);
            $table->date('FechaInicio');
            $table->date('FechaConclusion')->nullable();
        });

        $this->crearDetalleTrabajador('dj_capacitaciones', function (Blueprint $table): void {
            $table->string('NombreCursoEvento', 250);
            $table->string('InstitucionOrganizadora', 250);
            $table->date('FechaCapacitacion');
        });

        $this->crearDetalleTrabajador('dj_bienes_inmuebles', function (Blueprint $table): void {
            $table->string('ClasificacionPropiedad', 5);
            $table->string('Direccion', 500);
            $table->string('FichaPartidaRegistral', 120)->nullable();
            $table->decimal('Valor', 14, 2)->default(0);
        });

        $this->crearDetalleTrabajador('dj_vehiculos', function (Blueprint $table): void {
            $table->string('TipoVehiculo', 80);
            $table->string('Marca', 100)->nullable();
            $table->string('Modelo', 100)->nullable();
            $table->unsignedSmallInteger('Anio')->nullable();
            $table->string('Placa', 20)->nullable();
            $table->decimal('Valor', 14, 2)->default(0);
        });

        $this->crearDetalleTrabajador('dj_otros_bienes', function (Blueprint $table): void {
            $table->string('Descripcion', 250);
            $table->string('Caracteristicas', 500)->nullable();
            $table->decimal('Valor', 14, 2)->default(0);
            $table->boolean('EsRegistrable')->default(false);
        });

        $this->crearDetalleTrabajador('dj_inversiones', function (Blueprint $table): void {
            $table->string('EntidadFinanciera', 250);
            $table->string('TipoInstrumento', 250);
            $table->decimal('Valor', 14, 2)->default(0);
        });

        $this->crearDetalleTrabajador('dj_obligaciones', function (Blueprint $table): void {
            $table->string('Detalle', 500);
            $table->string('TipoAcreedor', 30);
            $table->string('AcreedorOtro', 180)->nullable();
            $table->decimal('Monto', 14, 2)->default(0);
        });

        if (! Schema::hasTable('dj_declaraciones_cliente')) {
            Schema::create('dj_declaraciones_cliente', function (Blueprint $table): void {
                $table->bigIncrements('DeclaracionClienteID');
                $table->integer('ClienteID')->index();
                $table->integer('SedeID')->index();
                $table->unsignedInteger('NumeroVersion');
                $table->string('Estado', 20)->default('BORRADOR')->index();
                $table->string('Nombres', 150);
                $table->string('Apellidos', 200);
                $table->string('TipoDocumento', 20);
                $table->string('NumeroDocumento', 30);
                $table->string('Nacionalidad', 100)->nullable();
                $table->string('EstadoCivil', 20);
                $table->string('ConyugeConviviente', 300)->nullable();
                $table->string('DomicilioVia', 300);
                $table->string('DomicilioNumero', 30)->nullable();
                $table->string('DomicilioInterior', 30)->nullable();
                $table->string('DomicilioZona', 180)->nullable();
                $table->string('DomicilioDistrito', 120);
                $table->string('DomicilioProvincia', 120);
                $table->string('DomicilioDepartamento', 120);
                $table->string('Ocupacion', 180);
                $table->string('TelefonoFijo', 50)->nullable();
                $table->string('Celular', 50)->nullable();
                $table->string('Correo', 180)->nullable();
                $table->text('PropositoRelacion');
                $table->string('EstadoPep', 20);
                $table->string('EstadoColaboradorAutoridad', 20);
                $table->string('PepCargo', 180)->nullable();
                $table->string('PepInstitucion', 250)->nullable();
                $table->boolean('EsParientePep')->default(false);
                $table->string('TipoBeneficiario', 30);
                $table->text('OrigenFondos')->nullable();
                $table->string('TerceroNombresApellidos', 300)->nullable();
                $table->string('TerceroTipoDocumento', 20)->nullable();
                $table->string('TerceroNumeroDocumento', 30)->nullable();
                $table->string('TerceroRepresentacion', 40)->nullable();
                $table->string('TerceroEstadoPep', 20)->nullable();
                $table->string('TerceroPepCargo', 180)->nullable();
                $table->string('TerceroPepInstitucion', 250)->nullable();
                $table->string('EntidadRazonSocial', 300)->nullable();
                $table->string('EntidadRucRegistro', 30)->nullable();
                $table->string('EntidadRepresentacion', 40)->nullable();
                $table->string('BeneficiarioFinalNombres', 300)->nullable();
                $table->string('NumeroCuentaFinanciera', 100)->nullable();
                $table->date('FechaDeclaracion');
                $table->bigInteger('CreadoPorID')->index();
                $table->bigInteger('ActualizadoPorID')->nullable()->index();
                $table->bigInteger('FinalizadoPorID')->nullable()->index();
                $table->timestamp('FinalizadoEn')->nullable();
                $table->timestamps();
                $table->unique(['ClienteID', 'NumeroVersion'], 'uq_dj_cliente_version');
            });
        }

        $this->crearDetalleCliente('dj_pep_familiares', function (Blueprint $table): void {
            $table->string('Nombres', 150);
            $table->string('Apellidos', 200);
            $table->string('Parentesco', 100);
        });

        $this->crearDetalleCliente('dj_pep_relacionados', function (Blueprint $table): void {
            $table->string('Nombres', 150);
            $table->string('Apellidos', 200);
            $table->string('Parentesco', 100);
        });

        if (! Schema::hasTable('dj_documentos')) {
            Schema::create('dj_documentos', function (Blueprint $table): void {
                $table->bigIncrements('DeclaracionDocumentoID');
                $table->unsignedBigInteger('DeclaracionTrabajadorID')->nullable()->index();
                $table->unsignedBigInteger('DeclaracionClienteID')->nullable()->index();
                $table->integer('SedeID')->index();
                $table->string('TipoDocumento', 30);
                $table->string('RutaArchivo', 500);
                $table->string('NombreArchivo', 255);
                $table->char('HashSha256', 64);
                $table->unsignedBigInteger('TamanioBytes');
                $table->bigInteger('GeneradoPorID')->index();
                $table->timestamp('GeneradoEn')->useCurrent();
                $table->timestamps();
                $table->foreign('DeclaracionTrabajadorID')->references('DeclaracionTrabajadorID')->on('dj_declaraciones_trabajador');
                $table->foreign('DeclaracionClienteID')->references('DeclaracionClienteID')->on('dj_declaraciones_cliente');
            });
        }
    }

    private function crearDetalleTrabajador(string $nombre, callable $columnas): void
    {
        if (Schema::hasTable($nombre)) {
            return;
        }

        Schema::create($nombre, function (Blueprint $table) use ($columnas): void {
            $table->bigIncrements('ID');
            $table->unsignedBigInteger('DeclaracionTrabajadorID')->index();
            $columnas($table);
            $table->unsignedInteger('Orden')->default(0);
            $table->timestamps();
            $table->foreign('DeclaracionTrabajadorID')->references('DeclaracionTrabajadorID')->on('dj_declaraciones_trabajador')->cascadeOnDelete();
        });
    }

    private function crearDetalleCliente(string $nombre, callable $columnas): void
    {
        if (Schema::hasTable($nombre)) {
            return;
        }

        Schema::create($nombre, function (Blueprint $table) use ($columnas): void {
            $table->bigIncrements('ID');
            $table->unsignedBigInteger('DeclaracionClienteID')->index();
            $columnas($table);
            $table->unsignedInteger('Orden')->default(0);
            $table->timestamps();
            $table->foreign('DeclaracionClienteID')->references('DeclaracionClienteID')->on('dj_declaraciones_cliente')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        foreach ([
            'dj_documentos', 'dj_pep_relacionados', 'dj_pep_familiares', 'dj_declaraciones_cliente',
            'dj_obligaciones', 'dj_inversiones', 'dj_otros_bienes', 'dj_vehiculos', 'dj_bienes_inmuebles',
            'dj_capacitaciones', 'dj_experiencias_laborales', 'dj_otros_estudios', 'dj_educacion_escolar',
            'dj_estudios', 'dj_declaraciones_trabajador', 'dj_trabajadores',
        ] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
