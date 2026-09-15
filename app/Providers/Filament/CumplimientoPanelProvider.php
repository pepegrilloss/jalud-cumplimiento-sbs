<?php

namespace App\Providers\Filament;

use App\Filament\Cumplimiento\Pages\ReportesRopRos;
use App\Filament\Cumplimiento\Pages\SelectSede;
use App\Filament\Cumplimiento\Resources\DeclaracionClienteResource;
use App\Filament\Cumplimiento\Resources\DeclaracionTrabajadorResource;
use App\Filament\Cumplimiento\Resources\RopCatalogoItemResource;
use App\Filament\Cumplimiento\Resources\RopRegistroResource;
use App\Filament\Cumplimiento\Resources\RosCasoResource;
use App\Filament\Cumplimiento\Resources\RosCatalogoItemResource;
use App\Filament\Cumplimiento\Resources\RosConfiguracionResource;
use App\Filament\Cumplimiento\Resources\UserResource;
use App\Filament\Pages\Auth\Login;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class CumplimientoPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('cumplimiento')
            ->path('cumplimiento')
            ->login(Login::class)
            ->brandName('JALUD - Cumplimiento SBS')
            ->brandLogo(asset('logo.png'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('favicon-j.svg'))
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->colors(['primary' => '#9fcb36'])
            ->assets([Css::make('custom-login-css', asset('css/login-custom.css'))])
            ->renderHook(
                PanelsRenderHook::HEAD_START,
                fn (): string => '<link rel="stylesheet" href="'.asset('css/login-custom.css').'?v='.filemtime(public_path('css/login-custom.css')).'">'
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn (): string => view('filament.cumplimiento.components.sede-activa')->render(),
            )
            ->navigationGroups(['Declaraciones juradas', 'Cumplimiento', 'Catálogos y Mantenimiento', 'Reportes', 'Administración'])
            ->pages([SelectSede::class, ReportesRopRos::class])
            ->resources([DeclaracionTrabajadorResource::class, DeclaracionClienteResource::class, RopRegistroResource::class, RopCatalogoItemResource::class, RosCasoResource::class, RosCatalogoItemResource::class, RosConfiguracionResource::class, UserResource::class])
            ->databaseTransactions()
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                \Hasnayeen\Themes\Http\Middleware\SetTheme::class,
                DispatchServingFilamentEvent::class,
                \App\Http\Middleware\EnsureSedeIsSelected::class,
            ])
            ->plugins([
                \Hasnayeen\Themes\ThemesPlugin::make(),
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([Authenticate::class]);
    }
}
