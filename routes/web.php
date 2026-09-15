<?php

use App\Http\Controllers\DeclaracionClienteDocumentoController;
use App\Http\Controllers\DeclaracionTrabajadorDocumentoController;
use App\Http\Controllers\ReporteCumplimientoController;
use App\Http\Controllers\RopReporteController;
use App\Http\Controllers\RosAdjuntoController;
use App\Http\Controllers\RosReporteController;
use App\Http\Controllers\TrabajadorArchivoController;
use App\Http\Middleware\EnsureSedeIsSelected;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/cumplimiento'));

Route::middleware(['auth', EnsureSedeIsSelected::class])->prefix('cumplimiento/declaraciones')->name('declaraciones.')->group(function (): void {
    Route::get('trabajadores/{declaracion}/documentos/todos/descargar', [DeclaracionTrabajadorDocumentoController::class, 'todos'])->name('trabajadores.documentos.todos');
    Route::get('trabajadores/{declaracion}/documentos/{tipo}/ver', [DeclaracionTrabajadorDocumentoController::class, 'ver'])->name('trabajadores.documentos.ver');
    Route::get('trabajadores/{declaracion}/documentos/{tipo}/descargar', [DeclaracionTrabajadorDocumentoController::class, 'descargar'])->name('trabajadores.documentos.descargar');
    Route::get('trabajadores/archivos/{archivo}/ver', [TrabajadorArchivoController::class, 'ver'])->name('trabajadores.archivos.ver');
    Route::get('trabajadores/archivos/{archivo}/descargar', [TrabajadorArchivoController::class, 'descargar'])->name('trabajadores.archivos.descargar');
    Route::get('clientes/{declaracion}/documento/ver', [DeclaracionClienteDocumentoController::class, 'ver'])->name('clientes.documento.ver');
    Route::get('clientes/{declaracion}/documento/descargar', [DeclaracionClienteDocumentoController::class, 'descargar'])->name('clientes.documento.descargar');
});

Route::middleware(['auth', EnsureSedeIsSelected::class])->prefix('cumplimiento/ros')->name('ros.')->group(function (): void {
    Route::get('{caso}/reporte.pdf', [RosReporteController::class, 'pdf'])->name('reporte.pdf');
    Route::get('{caso}/reporte.xls', [RosReporteController::class, 'excel'])->name('reporte.excel');
    Route::get('adjuntos/{adjunto}/ver', [RosAdjuntoController::class, 'ver'])->name('adjuntos.ver');
    Route::get('adjuntos/{adjunto}/descargar', [RosAdjuntoController::class, 'descargar'])->name('adjuntos.descargar');
});

Route::middleware(['auth', EnsureSedeIsSelected::class])->prefix('cumplimiento/rop')->name('rop.')->group(function (): void {
    Route::get('{registro}/reporte.xlsx', [RopReporteController::class, 'excel'])->name('reporte.excel');
});

Route::middleware(['auth', EnsureSedeIsSelected::class])->prefix('cumplimiento/reportes')->name('cumplimiento.reportes.')->group(function (): void {
    Route::get('rop-ros.zip', [ReporteCumplimientoController::class, 'ropRos'])->name('rop-ros');
});
