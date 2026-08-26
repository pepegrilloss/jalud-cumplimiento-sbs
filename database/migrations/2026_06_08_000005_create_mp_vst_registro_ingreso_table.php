<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('MP_VST_REGISTRO_INGRESO', function (Blueprint $table) {
            $table->integer('ID_MP_VST_REGISTRO_INGRESO', true);
            $table->integer('ID_MP_VST_PERSONAL');
            $table->integer('ID_MP_VST_VISITA');
            $table->string('TIPO', 10);
            $table->dateTime('FECHA_HORA');
            $table->integer('ID_MP_LGN_USUARIO');
            $table->dateTime('CREATED_AT');
            $table->dateTime('UPDATED_AT');

            $table->foreign('ID_MP_VST_PERSONAL')
                ->references('ID_MP_VST_PERSONAL')
                ->on('MP_VST_PERSONAL')
                ->onDelete('cascade');

            $table->foreign('ID_MP_VST_VISITA')
                ->references('ID_MP_VST_VISITA')
                ->on('MP_VST_VISITA');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('MP_VST_REGISTRO_INGRESO');
    }
};
