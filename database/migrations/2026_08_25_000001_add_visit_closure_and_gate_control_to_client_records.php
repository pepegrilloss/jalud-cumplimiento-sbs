<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('MP_VST_ENLACE_CLIENTE', function (Blueprint $table) {
            $table->dateTime('CERRADO_AT')->nullable()->after('ULTIMO_ACCESO_AT');
        });

        Schema::table('MP_VST_REGISTRO_CLIENTE', function (Blueprint $table) {
            // Nullable: conserva sin riesgo los registros creados por la versión anterior.
            $table->string('NOMBRES', 150)->nullable()->after('SUBMISSION_KEY');
            $table->string('DNI', 20)->nullable()->after('NOMBRES');
            $table->dateTime('INGRESO_AT')->nullable()->after('USER_AGENT');
            $table->dateTime('SALIDA_AT')->nullable()->after('INGRESO_AT');
            $table->index(['ID_MP_VST_ENLACE_CLIENTE', 'INGRESO_AT'], 'registro_cliente_enlace_ingreso_index');
        });
    }

    public function down(): void
    {
        Schema::table('MP_VST_REGISTRO_CLIENTE', function (Blueprint $table) {
            $table->dropIndex('registro_cliente_enlace_ingreso_index');
            $table->dropColumn(['NOMBRES', 'DNI', 'INGRESO_AT', 'SALIDA_AT']);
        });

        Schema::table('MP_VST_ENLACE_CLIENTE', function (Blueprint $table) {
            $table->dropColumn('CERRADO_AT');
        });
    }
};
