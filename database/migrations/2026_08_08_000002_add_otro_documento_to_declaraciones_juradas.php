<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dj_declaraciones_trabajador', function (Blueprint $table): void {
            $table->string('TipoDocumentoOtro', 100)->nullable()->after('TipoDocumento');
        });
        Schema::table('dj_declaraciones_cliente', function (Blueprint $table): void {
            $table->string('TipoDocumentoOtro', 100)->nullable()->after('TipoDocumento');
            $table->string('TerceroTipoDocumentoOtro', 100)->nullable()->after('TerceroTipoDocumento');
        });
    }

    public function down(): void
    {
        Schema::table('dj_declaraciones_trabajador', fn (Blueprint $table) => $table->dropColumn('TipoDocumentoOtro'));
        Schema::table('dj_declaraciones_cliente', fn (Blueprint $table) => $table->dropColumn(['TipoDocumentoOtro', 'TerceroTipoDocumentoOtro']));
    }
};
