<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dj_clientes', function (Blueprint $table): void {
            $table->unsignedBigInteger('ClienteID')->primary(); // ID source in Jalud
            $table->unsignedBigInteger('SedeID')->index();
            $table->string('DNI', 30)->nullable()->index();
            $table->string('NombresApellidos', 300)->nullable();
            $table->string('ApellidoPaterno', 100)->nullable();
            $table->string('ApellidoMaterno', 100)->nullable();
            $table->string('Nombres', 150)->nullable();
            $table->string('Domicilio', 300)->nullable();
            $table->string('ConyugeNombresApellidos', 300)->nullable();
            $table->boolean('Activo')->default(true)->index();
            $table->timestamp('SincronizadoEn')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dj_clientes');
    }
};
