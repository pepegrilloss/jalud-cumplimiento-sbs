<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('MP_VST_VISITA', function (Blueprint $table) {
            $table->unsignedInteger('ID_MLP_PLANTA_EMPAQUE')->nullable()->after('HORA_INGRESO');
        });
    }

    public function down(): void
    {
        Schema::table('MP_VST_VISITA', function (Blueprint $table) {
            $table->dropColumn('ID_MLP_PLANTA_EMPAQUE');
        });
    }
};
