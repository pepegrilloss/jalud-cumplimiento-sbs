<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('MP_VST_VISITA', function (Blueprint $table) {
            $table->integer('ID_MP_VST_VISITA', true);
            $table->string('EMPRESA', 200);
            $table->string('MOTIVO', 200);
            $table->integer('ID_MP_LGN_AREA');
            $table->dateTime('HORA_INGRESO');
            $table->text('DESCRIPCION')->nullable();
            $table->string('DOCUMENTO_GENERAL', 500)->nullable();
            $table->dateTime('CREATED_AT');
            $table->dateTime('UPDATED_AT');

            $table->foreign('ID_MP_LGN_AREA')
                ->references('ID_MP_LGN_AREA')
                ->on('MP_LGN_AREA');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('MP_VST_VISITA');
    }
};
