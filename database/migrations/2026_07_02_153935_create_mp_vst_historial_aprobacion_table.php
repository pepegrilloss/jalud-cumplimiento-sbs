<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('MP_VST_HISTORIAL_APROBACION', function (Blueprint $table) {
            $table->increments('ID_MP_VST_HISTORIAL');
            $table->unsignedInteger('ID_MP_VST_VISITA');
            $table->unsignedInteger('ID_MP_LGN_AREA');
            $table->string('ESTADO', 20);
            $table->string('COMENTARIO', 1000)->nullable();
            $table->unsignedInteger('ID_MP_LGN_USUARIO')->nullable();
            $table->dateTime('FECHA_APROBACION')->nullable();
            $table->unsignedInteger('REVISION')->default(1);
            $table->dateTime('CREATED_AT');
            $table->dateTime('UPDATED_AT');

            $table->foreign('ID_MP_VST_VISITA')
                ->references('ID_MP_VST_VISITA')
                ->on('MP_VST_VISITA')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('MP_VST_HISTORIAL_APROBACION');
    }
};
