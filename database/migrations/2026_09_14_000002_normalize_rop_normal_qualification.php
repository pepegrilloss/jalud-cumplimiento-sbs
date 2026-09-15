<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rop_registros') || ! Schema::hasColumn('rop_registros', 'Calificacion')) {
            return;
        }

        DB::table('rop_registros')
            ->where('Calificacion', 'NORMAL')
            ->update(['Calificacion' => null]);
    }

    public function down(): void
    {
        // NULL también representa revisiones legítimas sin clasificación especial.
    }
};
