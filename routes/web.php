<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeclaracionTrabajadorDocumentoController;
use App\Http\Controllers\DeclaracionClienteDocumentoController;

Route::get('/', fn () => redirect('/cumplimiento'));

Route::middleware('auth')->prefix('cumplimiento/declaraciones')->name('declaraciones.')->group(function (): void {
    Route::get('trabajadores/{declaracion}/documentos/todos/descargar', [DeclaracionTrabajadorDocumentoController::class, 'todos'])->name('trabajadores.documentos.todos');
    Route::get('trabajadores/{declaracion}/documentos/{tipo}/ver', [DeclaracionTrabajadorDocumentoController::class, 'ver'])->name('trabajadores.documentos.ver');
    Route::get('trabajadores/{declaracion}/documentos/{tipo}/descargar', [DeclaracionTrabajadorDocumentoController::class, 'descargar'])->name('trabajadores.documentos.descargar');
    Route::get('clientes/{declaracion}/documento/ver', [DeclaracionClienteDocumentoController::class, 'ver'])->name('clientes.documento.ver');
    Route::get('clientes/{declaracion}/documento/descargar', [DeclaracionClienteDocumentoController::class, 'descargar'])->name('clientes.documento.descargar');
});
