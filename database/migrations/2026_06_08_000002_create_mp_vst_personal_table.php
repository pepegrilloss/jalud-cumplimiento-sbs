<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('MP_VST_PERSONAL', function (Blueprint $table) {
            $table->integer('ID_MP_VST_PERSONAL', true);
            $table->integer('ID_MP_VST_VISITA');
            $table->string('NOMBRES', 200);
            $table->string('APELLIDOS', 200);
            $table->string('DNI', 20);
            $table->string('SCTR_PATH', 500)->nullable();
            $table->string('DNI_PATH', 500)->nullable();
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
        Schema::dropIfExists('MP_VST_PERSONAL');
    }
};
