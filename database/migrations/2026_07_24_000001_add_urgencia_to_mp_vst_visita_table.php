<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('MP_VST_VISITA', function (Blueprint $table) {
            $table->boolean('VISITA_URGENCIA')->default(false)->after('DIAS_ESTADIA');
            $table->text('JUSTIFICACION_URGENCIA')->nullable()->after('VISITA_URGENCIA');
        });
    }

    public function down(): void
    {
        Schema::table('MP_VST_VISITA', function (Blueprint $table) {
            $table->dropColumn(['VISITA_URGENCIA', 'JUSTIFICACION_URGENCIA']);
        });
    }
};
