<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE MP_VST_VISITA ADD FECHA_SALIDA_ESTIMADA_TMP time(0) NULL');
        DB::statement('ALTER TABLE MP_VST_VISITA DROP COLUMN FECHA_SALIDA_ESTIMADA');
        DB::statement("EXEC sp_rename 'MP_VST_VISITA.FECHA_SALIDA_ESTIMADA_TMP', 'FECHA_SALIDA_ESTIMADA', 'COLUMN'");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE MP_VST_VISITA ADD FECHA_SALIDA_ESTIMADA_TMP date NULL');
        DB::statement('ALTER TABLE MP_VST_VISITA DROP COLUMN FECHA_SALIDA_ESTIMADA');
        DB::statement("EXEC sp_rename 'MP_VST_VISITA.FECHA_SALIDA_ESTIMADA_TMP', 'FECHA_SALIDA_ESTIMADA', 'COLUMN'");
    }
};
