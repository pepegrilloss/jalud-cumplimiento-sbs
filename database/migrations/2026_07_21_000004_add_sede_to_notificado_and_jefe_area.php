<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('MP_VST_NOTIFICADO', function (Blueprint $table) {
            $table->integer('ID_MLP_PLANTA_EMPAQUE')->nullable()->after('ID_MP_LGN_AREA');
            $table->foreign('ID_MLP_PLANTA_EMPAQUE')
                ->references('ID_MLP_PLANTA_EMPAQUE')
                ->on('MLP_PLANTA_EMPAQUE')
                ->onDelete('set null');
        });

        Schema::table('MP_VST_JEFE_AREA', function (Blueprint $table) {
            $table->integer('ID_MLP_PLANTA_EMPAQUE')->nullable()->after('ID_MP_LGN_AREA');
            $table->foreign('ID_MLP_PLANTA_EMPAQUE')
                ->references('ID_MLP_PLANTA_EMPAQUE')
                ->on('MLP_PLANTA_EMPAQUE')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('MP_VST_NOTIFICADO', function (Blueprint $table) {
            $table->dropForeign(['ID_MLP_PLANTA_EMPAQUE']);
            $table->dropColumn('ID_MLP_PLANTA_EMPAQUE');
        });

        Schema::table('MP_VST_JEFE_AREA', function (Blueprint $table) {
            $table->dropForeign(['ID_MLP_PLANTA_EMPAQUE']);
            $table->dropColumn('ID_MLP_PLANTA_EMPAQUE');
        });
    }
};
