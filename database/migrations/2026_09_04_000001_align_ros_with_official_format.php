<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ros_casos', function (Blueprint $table): void {
            $table->longText('RelacionPersonas')->nullable()->after('DescripcionHechos');
            $table->longText('RelacionProductosServicios')->nullable()->after('RelacionPersonas');
            $table->longText('CaracteristicasSospechosas')->nullable()->after('RelacionProductosServicios');
            $table->longText('ExplicacionesJustificaciones')->nullable()->after('CaracteristicasSospechosas');
            $table->longText('RelacionOtrasOperaciones')->nullable()->after('ExplicacionesJustificaciones');
            $table->longText('InformacionExcluida')->nullable()->after('RelacionOtrasOperaciones');
            $table->longText('CambiosReporteAnterior')->nullable()->after('InformacionExcluida');
            $table->longText('FuentesConsultadas')->nullable()->after('CambiosReporteAnterior');
            $table->longText('DocumentacionComplementaria')->nullable()->after('FuentesConsultadas');
        });

        Schema::table('ros_personas', function (Blueprint $table): void {
            $table->string('PaisDomicilio', 120)->nullable()->after('Domicilio');
            $table->string('UrbanizacionSector', 180)->nullable()->after('DistritoDomicilio');

            $table->string('RepApellidoPaterno', 100)->nullable()->after('RosPersonaRepresentadaID');
            $table->string('RepApellidoMaterno', 100)->nullable()->after('RepApellidoPaterno');
            $table->string('RepNombres', 150)->nullable()->after('RepApellidoMaterno');
            $table->date('RepFechaNacimiento')->nullable()->after('RepNombres');
            $table->string('RepNacionalidad', 100)->nullable()->after('RepFechaNacimiento');
            $table->string('RepProfesionOcupacion', 180)->nullable()->after('RepNacionalidad');
            $table->string('RepTipoDocumento', 50)->nullable()->after('RepProfesionOcupacion');
            $table->string('RepNumeroDocumento', 80)->nullable()->after('RepTipoDocumento');
            $table->string('RepPaisEmisionDocumento', 120)->nullable()->after('RepNumeroDocumento');
            $table->string('RepPaisDomicilio', 120)->nullable()->after('RepPaisEmisionDocumento');
            $table->string('RepDepartamentoDomicilio', 120)->nullable()->after('RepPaisDomicilio');
            $table->string('RepProvinciaDomicilio', 120)->nullable()->after('RepDepartamentoDomicilio');
            $table->string('RepDistritoDomicilio', 120)->nullable()->after('RepProvinciaDomicilio');
            $table->string('RepUrbanizacionSector', 180)->nullable()->after('RepDistritoDomicilio');
            $table->string('RepTipoVia', 80)->nullable()->after('RepUrbanizacionSector');
            $table->string('RepNombreVia', 180)->nullable()->after('RepTipoVia');
            $table->string('RepNumeroDomicilio', 80)->nullable()->after('RepNombreVia');
            $table->string('RepTelefono', 80)->nullable()->after('RepNumeroDomicilio');
            $table->string('RepTelefono2', 80)->nullable()->after('RepTelefono');
            $table->string('RepTelefono3', 80)->nullable()->after('RepTelefono2');
            $table->string('RepCorreo', 180)->nullable()->after('RepTelefono3');
            $table->string('RepEstadoCivil', 50)->nullable()->after('RepCorreo');
            $table->string('RepConyugeApellidoPaterno', 100)->nullable()->after('RepEstadoCivil');
            $table->string('RepConyugeApellidoMaterno', 100)->nullable()->after('RepConyugeApellidoPaterno');
            $table->string('RepConyugeNombres', 150)->nullable()->after('RepConyugeApellidoMaterno');
        });
    }

    public function down(): void
    {
        Schema::table('ros_personas', function (Blueprint $table): void {
            $table->dropColumn([
                'PaisDomicilio', 'UrbanizacionSector',
                'RepApellidoPaterno', 'RepApellidoMaterno', 'RepNombres', 'RepFechaNacimiento',
                'RepNacionalidad', 'RepProfesionOcupacion', 'RepTipoDocumento', 'RepNumeroDocumento',
                'RepPaisEmisionDocumento', 'RepPaisDomicilio', 'RepDepartamentoDomicilio',
                'RepProvinciaDomicilio', 'RepDistritoDomicilio', 'RepUrbanizacionSector', 'RepTipoVia',
                'RepNombreVia', 'RepNumeroDomicilio', 'RepTelefono', 'RepTelefono2', 'RepTelefono3',
                'RepCorreo', 'RepEstadoCivil', 'RepConyugeApellidoPaterno', 'RepConyugeApellidoMaterno',
                'RepConyugeNombres',
            ]);
        });

        Schema::table('ros_casos', function (Blueprint $table): void {
            $table->dropColumn([
                'RelacionPersonas', 'RelacionProductosServicios', 'CaracteristicasSospechosas',
                'ExplicacionesJustificaciones', 'RelacionOtrasOperaciones', 'InformacionExcluida',
                'CambiosReporteAnterior', 'FuentesConsultadas', 'DocumentacionComplementaria',
            ]);
        });
    }
};
