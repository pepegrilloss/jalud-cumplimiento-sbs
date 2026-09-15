<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ros_casos', function (Blueprint $table): void {
            $table->string('TipoPersonaOrigen', 20)->default('CLIENTE')->index()->after('OrigenRos');
            $table->unsignedBigInteger('TrabajadorID')->nullable()->index()->after('ClienteID');
        });

        DB::table('ros_casos')
            ->whereNull('TipoPersonaOrigen')
            ->update(['TipoPersonaOrigen' => 'CLIENTE']);
    }

    public function down(): void
    {
        Schema::table('ros_casos', function (Blueprint $table): void {
            $table->dropIndex(['TipoPersonaOrigen']);
            $table->dropIndex(['TrabajadorID']);
            $table->dropColumn(['TipoPersonaOrigen', 'TrabajadorID']);
        });
    }
};
