<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rop_adjuntos', function (Blueprint $table): void {
            $table->bigIncrements('RopAdjuntoID');
            $table->unsignedBigInteger('RopRegistroID')->index();
            $table->unsignedBigInteger('SedeID')->index();
            $table->string('RutaArchivo', 500);
            $table->string('NombreOriginal', 255)->nullable();
            $table->string('TipoMime', 120)->nullable();
            $table->unsignedBigInteger('TamanioBytes')->nullable();
            $table->string('Descripcion', 255)->nullable();
            $table->string('Categoria', 80)->nullable();
            $table->string('HashSha256', 64)->nullable()->index();
            $table->unsignedBigInteger('SubidoPorID')->nullable()->index();
            $table->timestamps();
            $table->index(['RopRegistroID', 'created_at'], 'idx_rop_adjuntos_registro_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rop_adjuntos');
    }
};
