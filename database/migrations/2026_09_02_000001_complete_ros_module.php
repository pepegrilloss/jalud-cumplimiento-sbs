<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ros_casos', function (Blueprint $table): void {
            $table->string('NumeroReporte', 60)->nullable()->after('ClaseReporte');
            $table->date('FechaReporte')->nullable()->after('NumeroReporte');
            $table->string('EnteSupervisor', 80)->default('SBS/UIF-PERU')->after('FechaReporteAnterior');
            $table->string('TipoSujetoObligado', 180)->nullable()->after('EnteSupervisor');
            $table->string('CodigoEmpresa', 80)->nullable()->after('TipoSujetoObligado');
            $table->string('CodigoOficialCumplimiento', 80)->nullable()->after('CodigoEmpresa');
            $table->string('OficinaInformante', 180)->nullable()->after('CodigoOficialCumplimiento');
            $table->string('OficinaOperacion', 180)->nullable()->after('OficinaInformante');
            $table->string('DepartamentoOperacion', 120)->nullable()->after('OficinaOperacion');
            $table->string('ProvinciaOperacion', 120)->nullable()->after('DepartamentoOperacion');
            $table->string('DistritoOperacion', 120)->nullable()->after('ProvinciaOperacion');
            $table->timestamp('FechaCalificadoRos')->nullable()->after('DescripcionHechos');
            $table->unsignedBigInteger('CalificadoPorID')->nullable()->index()->after('FechaCalificadoRos');
            $table->string('NumeroEnvioRosel', 100)->nullable()->after('FechaReportado');
            $table->date('FechaEnvioRosel')->nullable()->after('NumeroEnvioRosel');
            $table->string('ConstanciaRoselRuta', 500)->nullable()->after('FechaEnvioRosel');
            $table->text('MotivoDescarte')->nullable()->after('ConstanciaRoselRuta');
            $table->boolean('Bloqueado')->default(false)->index()->after('MotivoDescarte');
            $table->string('VersionNormativa', 40)->default('SBS-6561-2009')->after('Bloqueado');
        });

        Schema::table('ros_personas', function (Blueprint $table): void {
            $table->string('PaisEmisionDocumento', 100)->nullable()->after('NumeroDocumento');
            $table->string('DepartamentoDomicilio', 120)->nullable()->after('Domicilio');
            $table->string('ProvinciaDomicilio', 120)->nullable()->after('DepartamentoDomicilio');
            $table->string('DistritoDomicilio', 120)->nullable()->after('ProvinciaDomicilio');
            $table->string('TipoVia', 80)->nullable()->after('DistritoDomicilio');
            $table->string('NombreVia', 180)->nullable()->after('TipoVia');
            $table->string('NumeroDomicilio', 50)->nullable()->after('NombreVia');
            $table->string('Telefono2', 80)->nullable()->after('Telefono');
            $table->string('Telefono3', 80)->nullable()->after('Telefono2');
            $table->string('EstadoCivil', 50)->nullable()->after('Correo');
            $table->string('ConyugeApellidoPaterno', 100)->nullable()->after('EstadoCivil');
            $table->string('ConyugeApellidoMaterno', 100)->nullable()->after('ConyugeApellidoPaterno');
            $table->string('ConyugeNombres', 150)->nullable()->after('ConyugeApellidoMaterno');
            $table->string('CondicionActividad', 80)->nullable()->after('ConyugeNombres');
            $table->string('Empleador', 200)->nullable()->after('CondicionActividad');
            $table->string('TelefonoLaboral', 80)->nullable()->after('Empleador');
            $table->string('PartidaElectronica', 100)->nullable()->after('TelefonoLaboral');
            $table->string('SedeRegistral', 150)->nullable()->after('PartidaElectronica');
            $table->text('ObjetoSocial')->nullable()->after('SedeRegistral');
            $table->decimal('IngresoAnual', 16, 2)->nullable()->after('ObjetoSocial');
            $table->string('MonedaIngresoAnual', 10)->nullable()->after('IngresoAnual');
            $table->string('RelacionEmpresa', 100)->nullable()->after('MonedaIngresoAnual');
            $table->string('RelacionEmpresaOtro', 180)->nullable()->after('RelacionEmpresa');
            $table->boolean('VinculadoActualmente')->nullable()->after('RelacionEmpresaOtro');
            $table->string('MotivoDesvinculacion', 120)->nullable()->after('VinculadoActualmente');
            $table->date('FechaDesvinculacion')->nullable()->after('MotivoDesvinculacion');
            $table->unsignedBigInteger('RosPersonaRepresentadaID')->nullable()->index()->after('FechaDesvinculacion');
        });

        Schema::table('ros_operaciones', function (Blueprint $table): void {
            $table->string('PaisRelacionado', 100)->nullable()->after('FechaOperacion');
            $table->unsignedInteger('Orden')->default(0)->after('PaisRelacionado');
        });

        Schema::table('ros_adjuntos', function (Blueprint $table): void {
            $table->string('Categoria', 50)->default('SUSTENTO')->after('Descripcion');
            $table->string('HashSha256', 64)->nullable()->index()->after('Categoria');
        });

        Schema::table('ros_auditorias', function (Blueprint $table): void {
            $table->longText('ValoresAnteriores')->nullable()->after('ModeloID');
            $table->longText('ValoresNuevos')->nullable()->after('ValoresAnteriores');
            $table->text('Motivo')->nullable()->after('ValoresNuevos');
        });

        Schema::create('ros_reportes_previos', function (Blueprint $table): void {
            $table->bigIncrements('RosReportePrevioID');
            $table->unsignedBigInteger('RosCasoID')->index();
            $table->unsignedBigInteger('RosPersonaID')->nullable()->index();
            $table->unsignedBigInteger('SedeID')->index();
            $table->string('NumeroReporte', 60);
            $table->date('FechaReporte')->nullable();
            $table->string('EnviadoA', 100)->nullable();
            $table->text('InformacionRelevante')->nullable();
            $table->timestamps();
            $table->index(['RosCasoID', 'FechaReporte'], 'idx_ros_previo_caso_fecha');
        });

        Schema::create('ros_transiciones', function (Blueprint $table): void {
            $table->bigIncrements('RosTransicionID');
            $table->unsignedBigInteger('RosCasoID')->index();
            $table->unsignedBigInteger('SedeID')->index();
            $table->unsignedBigInteger('UserID')->nullable()->index();
            $table->string('EstadoAnterior', 30);
            $table->string('EstadoNuevo', 30);
            $table->text('Motivo')->nullable();
            $table->timestamp('FechaTransicion')->useCurrent();
            $table->timestamps();
        });

        Schema::create('ros_catalogo_items', function (Blueprint $table): void {
            $table->bigIncrements('RosCatalogoItemID');
            $table->string('Tipo', 50)->index();
            $table->string('Codigo', 30);
            $table->text('Nombre');
            $table->string('Grupo', 120)->nullable();
            $table->boolean('Activo')->default(true)->index();
            $table->unsignedInteger('Orden')->default(0);
            $table->string('VersionNormativa', 40)->default('SBS-6561-2009');
            $table->timestamps();
            $table->unique(['Tipo', 'Codigo'], 'uq_ros_catalogo_tipo_codigo');
        });

        Schema::create('ros_configuraciones', function (Blueprint $table): void {
            $table->bigIncrements('RosConfiguracionID');
            $table->unsignedBigInteger('SedeID')->unique();
            $table->string('EnteSupervisor', 80)->default('SBS/UIF-PERU');
            $table->string('TipoSujetoObligado', 180)->nullable();
            $table->string('CodigoEmpresa', 80)->nullable();
            $table->string('CodigoOficialCumplimiento', 80)->nullable();
            $table->string('OficinaInformante', 180)->nullable();
            $table->string('Departamento', 120)->nullable();
            $table->string('Provincia', 120)->nullable();
            $table->string('Distrito', 120)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ros_configuraciones');
        Schema::dropIfExists('ros_catalogo_items');
        Schema::dropIfExists('ros_transiciones');
        Schema::dropIfExists('ros_reportes_previos');

        Schema::table('ros_auditorias', function (Blueprint $table): void {
            $table->dropColumn(['ValoresAnteriores', 'ValoresNuevos', 'Motivo']);
        });

        Schema::table('ros_adjuntos', function (Blueprint $table): void {
            $table->dropColumn(['Categoria', 'HashSha256']);
        });

        Schema::table('ros_operaciones', function (Blueprint $table): void {
            $table->dropColumn(['PaisRelacionado', 'Orden']);
        });

        Schema::table('ros_personas', function (Blueprint $table): void {
            $table->dropColumn([
                'PaisEmisionDocumento', 'DepartamentoDomicilio', 'ProvinciaDomicilio',
                'DistritoDomicilio', 'TipoVia', 'NombreVia', 'NumeroDomicilio', 'Telefono2',
                'Telefono3', 'EstadoCivil', 'ConyugeApellidoPaterno', 'ConyugeApellidoMaterno',
                'ConyugeNombres', 'CondicionActividad', 'Empleador', 'TelefonoLaboral',
                'PartidaElectronica', 'SedeRegistral', 'ObjetoSocial', 'IngresoAnual',
                'MonedaIngresoAnual', 'RelacionEmpresa', 'RelacionEmpresaOtro',
                'VinculadoActualmente', 'MotivoDesvinculacion', 'FechaDesvinculacion',
                'RosPersonaRepresentadaID',
            ]);
        });

        Schema::table('ros_casos', function (Blueprint $table): void {
            $table->dropColumn([
                'NumeroReporte', 'FechaReporte', 'EnteSupervisor', 'TipoSujetoObligado',
                'CodigoEmpresa', 'CodigoOficialCumplimiento', 'OficinaInformante',
                'OficinaOperacion', 'DepartamentoOperacion', 'ProvinciaOperacion',
                'DistritoOperacion', 'FechaCalificadoRos', 'CalificadoPorID',
                'NumeroEnvioRosel', 'FechaEnvioRosel', 'ConstanciaRoselRuta',
                'MotivoDescarte', 'Bloqueado', 'VersionNormativa',
            ]);
        });
    }
};
