<?php

use App\Http\Controllers\DeclaracionClienteDocumentoController;
use App\Http\Controllers\DeclaracionTrabajadorDocumentoController;
use App\Http\Controllers\TrabajadorArchivoController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/cumplimiento'));

Route::middleware('auth')->prefix('cumplimiento/declaraciones')->name('declaraciones.')->group(function (): void {
    Route::get('trabajadores/{declaracion}/documentos/todos/descargar', [DeclaracionTrabajadorDocumentoController::class, 'todos'])->name('trabajadores.documentos.todos');
    Route::get('trabajadores/{declaracion}/documentos/{tipo}/ver', [DeclaracionTrabajadorDocumentoController::class, 'ver'])->name('trabajadores.documentos.ver');
    Route::get('trabajadores/{declaracion}/documentos/{tipo}/descargar', [DeclaracionTrabajadorDocumentoController::class, 'descargar'])->name('trabajadores.documentos.descargar');
    Route::get('trabajadores/archivos/{archivo}/ver', [TrabajadorArchivoController::class, 'ver'])->name('trabajadores.archivos.ver');
    Route::get('trabajadores/archivos/{archivo}/descargar', [TrabajadorArchivoController::class, 'descargar'])->name('trabajadores.archivos.descargar');
    Route::get('clientes/{declaracion}/documento/ver', [DeclaracionClienteDocumentoController::class, 'ver'])->name('clientes.documento.ver');
    Route::get('clientes/{declaracion}/documento/descargar', [DeclaracionClienteDocumentoController::class, 'descargar'])->name('clientes.documento.descargar');
});
