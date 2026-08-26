<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('MP_VST_CLIENTE', function (Blueprint $table) {
            $table->integer('ID_MP_VST_CLIENTE', true);
            $table->string('RAZON_SOCIAL', 200);
            $table->boolean('ACTIVO')->default(true);
            $table->integer('ID_MP_LGN_USUARIO_CREADOR')->nullable();
            $table->dateTime('CREATED_AT');
            $table->dateTime('UPDATED_AT');

            $table->index('RAZON_SOCIAL');
        });

        Schema::create('MP_VST_ENLACE_CLIENTE', function (Blueprint $table) {
            $table->integer('ID_MP_VST_ENLACE_CLIENTE', true);
            $table->integer('ID_MP_VST_CLIENTE');
            $table->string('NOMBRE', 100);
            $table->char('TOKEN_HASH', 64)->unique();
            $table->text('TOKEN_CIFRADO');
            $table->boolean('ACTIVO')->default(true);
            $table->integer('ID_MP_LGN_USUARIO_CREADOR')->nullable();
            $table->dateTime('ULTIMO_ACCESO_AT')->nullable();
            $table->dateTime('CREATED_AT');
            $table->dateTime('UPDATED_AT');

            $table->foreign('ID_MP_VST_CLIENTE')
                ->references('ID_MP_VST_CLIENTE')
                ->on('MP_VST_CLIENTE')
                ->cascadeOnDelete();
        });

        Schema::create('MP_VST_REGISTRO_CLIENTE', function (Blueprint $table) {
            $table->integer('ID_MP_VST_REGISTRO_CLIENTE', true);
            $table->integer('ID_MP_VST_ENLACE_CLIENTE');
            $table->char('SUBMISSION_KEY', 36)->unique();
            $table->string('DNI_PATH', 500);
            $table->string('IP', 45)->nullable();
            $table->string('USER_AGENT', 255)->nullable();
            $table->dateTime('CREATED_AT');
            $table->dateTime('UPDATED_AT');

            $table->foreign('ID_MP_VST_ENLACE_CLIENTE')
                ->references('ID_MP_VST_ENLACE_CLIENTE')
                ->on('MP_VST_ENLACE_CLIENTE')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('MP_VST_REGISTRO_CLIENTE');
        Schema::dropIfExists('MP_VST_ENLACE_CLIENTE');
        Schema::dropIfExists('MP_VST_CLIENTE');
    }
};
