<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ros_configuraciones', function (Blueprint $table): void {
            $table->decimal('UmbralRop', 14, 2)->default(2000)->after('SedeID');
        });

        Schema::create('rop_periodos', function (Blueprint $table): void {
            $table->bigIncrements('RopPeriodoID');
            $table->unsignedBigInteger('SedeID')->index();
            $table->date('Periodo');
            $table->string('Estado', 20)->default('ABIERTO')->index();
            $table->unsignedBigInteger('CerradoPorID')->nullable()->index();
            $table->timestamp('CerradoEn')->nullable();
            $table->timestamps();
            $table->unique(['SedeID', 'Periodo'], 'uq_rop_periodo_sede_mes');
        });

        Schema::create('rop_registros', function (Blueprint $table): void {
            $table->bigIncrements('RopRegistroID');
            $table->string('NumeroRop', 30)->nullable()->unique();
            $table->unsignedBigInteger('RopPeriodoID')->index();
            $table->unsignedBigInteger('SedeID')->index();
            $table->unsignedBigInteger('ZonaID')->nullable()->index();
            $table->unsignedBigInteger('ClienteID')->index();
            $table->unsignedBigInteger('ProposicionCreditoID')->index();
            $table->unsignedBigInteger('CreditoID')->unique();
            $table->string('CodigoCredito', 30)->index();
            $table->string('ClienteNombre', 300)->index();
            $table->string('NumeroDocumento', 30)->nullable()->index();
            $table->date('Periodo')->index();
            $table->dateTime('FechaOperacion')->index();
            $table->decimal('Monto', 14, 2);
            $table->decimal('UmbralAplicado', 14, 2);
            $table->string('Moneda', 10)->default('PEN');
            $table->string('Estado', 20)->default('PENDIENTE')->index();
            $table->string('Calificacion', 20)->nullable()->index();
            $table->text('MotivoCalificacion')->nullable();
            $table->text('ObservacionesCalificacion')->nullable();
            $table->unsignedBigInteger('CalificadoPorID')->nullable()->index();
            $table->timestamp('CalificadoEn')->nullable();
            $table->unsignedBigInteger('RosCasoID')->nullable()->unique();
            $table->json('SnapshotDatos');
            $table->unsignedBigInteger('CreadoPorID')->nullable()->index();
            $table->timestamps();
            $table->index(['SedeID', 'Periodo', 'Estado'], 'idx_rop_sede_periodo_estado');
        });

        Schema::create('rop_auditorias', function (Blueprint $table): void {
            $table->bigIncrements('RopAuditoriaID');
            $table->unsignedBigInteger('RopRegistroID')->nullable()->index();
            $table->unsignedBigInteger('RopPeriodoID')->nullable()->index();
            $table->unsignedBigInteger('SedeID')->index();
            $table->unsignedBigInteger('UserID')->nullable()->index();
            $table->string('Accion', 40)->index();
            $table->longText('ValoresAnteriores')->nullable();
            $table->longText('ValoresNuevos')->nullable();
            $table->text('Motivo')->nullable();
            $table->string('IpAddress', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::table('ros_casos', function (Blueprint $table): void {
            $table->unsignedBigInteger('RopRegistroID')->nullable()->unique()->after('PagoID');
        });
    }

    public function down(): void
    {
        Schema::table('ros_casos', fn (Blueprint $table) => $table->dropColumn('RopRegistroID'));
        Schema::dropIfExists('rop_auditorias');
        Schema::dropIfExists('rop_registros');
        Schema::dropIfExists('rop_periodos');
        Schema::table('ros_configuraciones', fn (Blueprint $table) => $table->dropColumn('UmbralRop'));
    }
};
