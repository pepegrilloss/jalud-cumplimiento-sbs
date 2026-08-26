<?php

namespace App\Providers;

use App\Models\Cliente;
use App\Models\EnlaceCliente;
use App\Models\RegistroCliente;
use App\Observers\AuditModelObserver;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use App\Auth\CustomUserProvider;
use App\Http\Responses\Auth\LoginResponse;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Facades\Livewire\Features\SupportFileUploads\GenerateSignedUploadUrl as GenerateSignedUploadUrlFacade;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\GenerateSignedUploadUrl;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LoginResponseContract::class, LoginResponse::class);
    }

    public function boot(): void
    {
        $this->forceProductionHttpsUrls();
        $this->configureLivewireTemporaryUploads();

        $this->configureRateLimiting();

        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)
                ->middleware(['web', 'throttle:livewire'])
                ->name('livewire.update');
        });

        Auth::provider('custom', function ($app, array $config) {
            return new CustomUserProvider();
        });

        Cliente::observe(AuditModelObserver::class);
        EnlaceCliente::observe(AuditModelObserver::class);
        RegistroCliente::observe(AuditModelObserver::class);

        Event::listen(Login::class, function (Login $event): void {
            AuditLogger::eventForUser(
                $event->user,
                'sesion_iniciada',
                'LOGIN',
                'Usuario inició sesión',
            );
        });

        Event::listen(Logout::class, function (Logout $event): void {
            AuditLogger::eventForUser(
                $event->user,
                'sesion_cerrada',
                'LOGOUT',
                'Usuario cerró sesión',
            );
        });
    }

    private function forceProductionHttpsUrls(): void
    {
        $appUrl = (string) config('app.url');

        if (app()->isProduction() && str_starts_with($appUrl, 'https://')) {
            URL::forceRootUrl(rtrim($appUrl, '/'));
            URL::forceScheme('https');
        }
    }

    private function configureLivewireTemporaryUploads(): void
    {
        GenerateSignedUploadUrlFacade::swap(new class extends GenerateSignedUploadUrl {
            public function forLocal()
            {
                return URL::temporarySignedRoute(
                    'livewire.upload-file',
                    now()->addMinutes(FileUploadConfiguration::maxUploadTime()),
                    [],
                    false
                );
            }
        });
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('livewire', function (Request $request) {
            return Limit::perMinute(config('security.rate_limits.livewire_updates_per_minute', 120))
                ->by(($request->user()?->getAuthIdentifier() ?: $request->ip()) . '|livewire');
        });

        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(config('security.rate_limits.file_uploads_per_minute', 20))
                ->by(($request->user()?->getAuthIdentifier() ?: $request->ip()) . '|uploads');
        });

        RateLimiter::for('forms', function (Request $request) {
            return Limit::perMinute(config('security.rate_limits.forms_per_minute', 30))
                ->by(($request->user()?->getAuthIdentifier() ?: $request->ip()) . '|forms');
        });

        RateLimiter::for('documents', function (Request $request) {
            return Limit::perMinute(config('security.rate_limits.documents_per_minute', 60))
                ->by(($request->user()?->getAuthIdentifier() ?: $request->ip()) . '|documents');
        });

        RateLimiter::for('labels', function (Request $request) {
            return Limit::perMinute(config('security.rate_limits.labels_per_minute', 30))
                ->by(($request->user()?->getAuthIdentifier() ?: $request->ip()) . '|labels');
        });

        RateLimiter::for('public-registration-view', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->ip().'|registro-publico-vista');
        });

        RateLimiter::for('public-registration-submit', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->ip().'|registro-publico-envio');
        });

        RateLimiter::for('public-registration-ocr', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip().'|registro-publico-ocr');
        });
    }
}
