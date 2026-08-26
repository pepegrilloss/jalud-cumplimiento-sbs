<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('MP_VST_VISITA', function (Blueprint $table) {
            $table->text('SCTR_PATH')->nullable()->after('DOCUMENTO_GENERAL');
        });

        Schema::table('MP_VST_PERSONAL', function (Blueprint $table) {
            $table->dropColumn('SCTR_PATH');
        });
    }

    public function down(): void
    {
        Schema::table('MP_VST_VISITA', function (Blueprint $table) {
            $table->dropColumn('SCTR_PATH');
        });

        Schema::table('MP_VST_PERSONAL', function (Blueprint $table) {
            $table->string('SCTR_PATH', 500)->nullable()->after('DNI');
        });
    }
};
