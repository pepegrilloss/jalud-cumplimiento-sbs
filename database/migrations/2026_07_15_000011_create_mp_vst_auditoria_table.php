<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('MP_VST_AUDITORIA', function (Blueprint $table) {
            $table->integer('ID_MP_VST_AUDITORIA', true);
            $table->integer('ID_MP_LGN_USUARIO')->nullable();
            $table->string('USUARIO', 150)->nullable();
            $table->string('AREA', 150)->nullable();
            $table->string('EVENTO', 80);
            $table->string('ACCION', 80);
            $table->string('MODELO', 160)->nullable();
            $table->string('TABLA', 120)->nullable();
            $table->string('ID_REGISTRO', 80)->nullable();
            $table->integer('ID_MP_VST_VISITA')->nullable();
            $table->string('EMPRESA', 200)->nullable();
            $table->text('RESUMEN')->nullable();
            $table->json('VALORES_ANTERIORES')->nullable();
            $table->json('VALORES_NUEVOS')->nullable();
            $table->json('METADATA')->nullable();
            $table->string('IP', 64)->nullable();
            $table->string('METODO', 12)->nullable();
            $table->text('URL')->nullable();
            $table->text('USER_AGENT')->nullable();
            $table->dateTime('CREATED_AT');

            $table->index('ID_MP_LGN_USUARIO');
            $table->index('EVENTO');
            $table->index('ACCION');
            $table->index('ID_MP_VST_VISITA');
            $table->index('CREATED_AT');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('MP_VST_AUDITORIA');
    }
};
