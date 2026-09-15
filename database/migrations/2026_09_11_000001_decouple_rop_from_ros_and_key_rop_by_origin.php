<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rop_registros', function (Blueprint $table): void {
            $table->string('OrigenTipo', 40)->nullable()->after('CreditoID');
            $table->unsignedBigInteger('OrigenID')->nullable()->after('OrigenTipo');
        });

        DB::table('rop_registros')->update([
            'OrigenTipo' => 'CREDITO_DESEMBOLSO',
            'OrigenID' => DB::raw('CreditoID'),
        ]);

        Schema::table('rop_registros', function (Blueprint $table): void {
            $table->dropUnique(['CreditoID']);
            $table->index('CreditoID', 'idx_rop_credito');
            $table->unique(['OrigenTipo', 'OrigenID'], 'uq_rop_origen_tipo_id');
        });

        Schema::table('ros_casos', function (Blueprint $table): void {
            $table->string('OrigenRos', 20)->default('DIRECTO')->index()->after('RopRegistroID');
        });

        DB::table('ros_casos')
            ->whereNotNull('RopRegistroID')
            ->update(['OrigenRos' => 'ROP']);
    }

    public function down(): void
    {
        Schema::table('ros_casos', function (Blueprint $table): void {
            $table->dropIndex(['OrigenRos']);
            $table->dropColumn('OrigenRos');
        });

        Schema::table('rop_registros', function (Blueprint $table): void {
            $table->dropUnique('uq_rop_origen_tipo_id');
            $table->dropIndex('idx_rop_credito');
            $table->dropColumn(['OrigenTipo', 'OrigenID']);
            $table->unique('CreditoID');
        });
    }
};
