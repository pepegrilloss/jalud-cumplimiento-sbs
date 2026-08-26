<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('MP_VST_VISITA', function (Blueprint $table): void {
            $table->unsignedInteger('DIAS_ESTADIA')->default(1)->after('HORA_INGRESO');
        });
    }

    public function down(): void
    {
        Schema::table('MP_VST_VISITA', function (Blueprint $table): void {
            $table->dropColumn('DIAS_ESTADIA');
        });
    }
};
