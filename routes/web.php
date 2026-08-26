<?php

use App\Http\Controllers\DniClienteController;
use App\Http\Controllers\LivewireTemporaryUploadController;
use App\Http\Controllers\OcrDniPublicoController;
use App\Http\Controllers\QrClienteController;
use App\Http\Controllers\RegistroPublicoController;
use Illuminate\Support\Facades\Route;

Route::post('livewire/upload-file', [LivewireTemporaryUploadController::class, 'handle'])
    ->middleware('throttle:uploads')
    ->name('livewire.upload-file');

Route::middleware('throttle:public-registration-view')->group(function () {
    Route::get('registro/{token}', [RegistroPublicoController::class, 'show'])
        ->where('token', '[A-Za-z0-9]{64}')
        ->name('registro-publico.show');

    Route::get('registro/{token}/gracias', [RegistroPublicoController::class, 'gracias'])
        ->where('token', '[A-Za-z0-9]{64}')
        ->name('registro-publico.gracias');

    Route::get('registro/{token}/dni/{registro}', [RegistroPublicoController::class, 'dni'])
        ->whereNumber('registro')
        ->where('token', '[A-Za-z0-9]{64}')
        ->name('registro-publico.dni');
});

Route::post('registro/{token}', [RegistroPublicoController::class, 'store'])
    ->where('token', '[A-Za-z0-9]{64}')
    ->middleware('throttle:public-registration-submit')
    ->name('registro-publico.store');

Route::post('registro/{token}/ocr-dni', OcrDniPublicoController::class)
    ->where('token', '[A-Za-z0-9]{64}')
    ->middleware('throttle:public-registration-ocr')
    ->name('registro-publico.ocr-dni');

Route::post('registro/{token}/cerrar', [RegistroPublicoController::class, 'cerrar'])
    ->where('token', '[A-Za-z0-9]{64}')
    ->middleware('throttle:public-registration-submit')
    ->name('registro-publico.cerrar');

Route::middleware('auth')->group(function () {
    Route::get('registros-clientes/{registro}/dni', DniClienteController::class)
        ->middleware('throttle:documents')
        ->name('registros-clientes.dni');

    Route::get('enlaces-clientes/{enlace}/qr.svg', QrClienteController::class)
        ->middleware('throttle:documents')
        ->name('enlaces-clientes.qr');
});

Route::get('lang/{locale}', function ($locale) {
    abort_unless(in_array($locale, ['es', 'en'], true), 404);

    session(['locale' => $locale]);

    return redirect()->back();
})->name('lang.switch');
