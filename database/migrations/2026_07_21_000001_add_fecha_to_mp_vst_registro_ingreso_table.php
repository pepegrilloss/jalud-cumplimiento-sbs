<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('MP_VST_REGISTRO_INGRESO', function (Blueprint $table) {
            $table->date('FECHA')->nullable()->after('FECHA_HORA');
        });
    }

    public function down(): void
    {
        Schema::table('MP_VST_REGISTRO_INGRESO', function (Blueprint $table) {
            $table->dropColumn('FECHA');
        });
    }
};
