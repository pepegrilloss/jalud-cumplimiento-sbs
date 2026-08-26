<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('MP_VST_NOTIFICADO', function (Blueprint $table) {
            $table->integer('ID_MP_VST_NOTIFICADO', true);
            $table->integer('ID_MP_LGN_AREA')->nullable();
            $table->string('NOMBRE', 200);
            $table->string('EMAIL', 200);
            $table->dateTime('CREATED_AT');
            $table->dateTime('UPDATED_AT');

            $table->foreign('ID_MP_LGN_AREA')
                ->references('ID_MP_LGN_AREA')
                ->on('MP_LGN_AREA')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('MP_VST_NOTIFICADO');
    }
};
