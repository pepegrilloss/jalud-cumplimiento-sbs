<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('MP_VST_APROBACION', function (Blueprint $table) {
            $table->integer('ID_MP_VST_APROBACION', true);
            $table->integer('ID_MP_VST_VISITA');
            $table->integer('ID_MP_LGN_AREA');
            $table->integer('ID_MP_LGN_USUARIO')->nullable();
            $table->string('ESTADO', 20)->default('PENDIENTE');
            $table->text('COMENTARIO')->nullable();
            $table->dateTime('FECHA_APROBACION')->nullable();
            $table->dateTime('CREATED_AT');
            $table->dateTime('UPDATED_AT');

            $table->foreign('ID_MP_VST_VISITA')
                ->references('ID_MP_VST_VISITA')
                ->on('MP_VST_VISITA')
                ->onDelete('cascade');

            $table->foreign('ID_MP_LGN_AREA')
                ->references('ID_MP_LGN_AREA')
                ->on('MP_LGN_AREA');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('MP_VST_APROBACION');
    }
};
