<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dj_trabajador_archivos')) {
            return;
        }

        Schema::create('dj_trabajador_archivos', function (Blueprint $table): void {
            $table->bigIncrements('TrabajadorArchivoID');
            $table->unsignedBigInteger('TrabajadorID')->index();
            $table->integer('SedeID')->index();
            $table->string('TipoDocumento', 40)->index();
            $table->string('Titulo', 200);
            $table->text('Descripcion')->nullable();
            $table->string('RutaArchivo', 500);
            $table->string('NombreOriginal', 255)->nullable();
            $table->bigInteger('SubidoPorID')->nullable()->index();
            $table->timestamps();

            $table->foreign('TrabajadorID')
                ->references('TrabajadorID')
                ->on('dj_trabajadores')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dj_trabajador_archivos');
    }
};
