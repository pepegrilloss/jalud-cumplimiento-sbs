<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('MP_VST_SCTR_VIGENCIA', function (Blueprint $table) {
            $table->integer('ID_MP_VST_SCTR_VIGENCIA', true);
            $table->integer('ID_MP_VST_VISITA');
            $table->string('SCTR_PATH', 500);
            $table->string('NOMBRE_ARCHIVO', 200);
            $table->dateTime('FECHA_DESDE')->nullable();
            $table->dateTime('FECHA_HASTA')->nullable();
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
        Schema::dropIfExists('MP_VST_SCTR_VIGENCIA');
    }
};
