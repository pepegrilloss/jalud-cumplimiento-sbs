<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Njxqlus\FilamentProgressbar\FilamentProgressbarPlugin;

class MlpVisitasPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('mlp_visitas')
            ->path('')
            ->plugins([
                FilamentProgressbarPlugin::make()->color('#d97706'),
            ])
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->sidebarCollapsibleOnDesktop(true)
            ->maxContentWidth('full')
            ->darkMode(false)
            ->colors([
                'primary' => '#edd484',
            ])
            ->renderHook(
                'panels::user-menu.before',
                function (): string {
                    $current = session('locale', 'es');
                    $locales = [
                        'es' => ['label' => 'ES', 'name' => 'Español'],
                        'en' => ['label' => 'EN', 'name' => 'English'],
                    ];
                    $html = '<div style="display:flex;align-items:center;gap:6px;margin-right:4px;">';
                    foreach ($locales as $code => $info) {
                        $active = $code === $current ? 'background:#edd484;color:#111827;font-weight:600;' : 'color:#6b7280;';
                        $html .= '<a href="' . route('lang.switch', $code) . '" style="padding:4px 8px;border-radius:6px;text-decoration:none;font-size:12px;' . $active . 'transition:background .2s;" title="' . $info['name'] . '">' . $info['label'] . '</a>';
                    }
                    $html .= '</div>';
                    return $html;
                },
            )
            ->renderHook(
                'panels::scripts.after',
                function (): string {
                    $loginUrl = route('filament.mlp_visitas.auth.login', ['expired' => 1], false);

                    return <<<HTML
                <script>
                (() => {
                    const registerSessionExpiredHandler = () => {
                        if (! window.Livewire || window.__mlpSessionExpiredHandlerRegistered) {
                            return;
                        }

                        window.__mlpSessionExpiredHandlerRegistered = true;

                        const redirectToLogin = () => {
                            const loginUrl = new URL('{$loginUrl}', window.location.origin);
                            loginUrl.searchParams.set('next', window.location.pathname + window.location.search);
                            window.location.replace(loginUrl.toString());
                        };

                        window.Livewire.hook('request', ({ fail }) => {
                            fail(({ status, preventDefault }) => {
                                if (status === 419 || status === 401) {
                                    preventDefault();
                                    redirectToLogin();
                                }
                            });
                        });
                    };

                    document.addEventListener('livewire:init', registerSessionExpiredHandler);
                    registerSessionExpiredHandler();
                })();
                </script>
                HTML;
                },
            )
            ->renderHook(
                'panels::head.end',
                function (): string {
                    $theme = cache('theme') ?: config('themes.default.theme', 'sunset');
                    $themeCss = asset("vendor/themes/{$theme}.css");
                    return <<<HTML
                <link rel="stylesheet" href="{$themeCss}">
                <style>
                :root { --primary-50: 254,252,242 !important; --primary-100: 254,249,228 !important; --primary-200: 253,240,200 !important; --primary-300: 251,227,160 !important; --primary-400: 249,210,115 !important; --primary-500: 237,212,132 !important; --primary-600: 237,212,132 !important; --primary-700: 220,177,90 !important; --primary-800: 200,155,65 !important; --primary-900: 180,135,50 !important; --primary-950: 120,85,25 !important; }
                :root { --c-50: 254,252,242 !important; --c-100: 254,249,228 !important; --c-200: 253,240,200 !important; --c-300: 251,227,160 !important; --c-400: 249,210,115 !important; --c-500: 237,212,132 !important; --c-600: 237,212,132 !important; --c-700: 220,177,90 !important; --c-800: 200,155,65 !important; --c-900: 180,135,50 !important; --c-950: 120,85,25 !important; }
                .vs-card { border-radius: 16px; transition: transform .25s ease, box-shadow .25s ease; box-shadow: 0 4px 20px rgba(0,0,0,.06), 0 1px 3px rgba(0,0,0,.04); }
                .vs-card:hover { transform: translateY(-4px); box-shadow: 0 12px 35px rgba(237,212,132,.18), 0 4px 10px rgba(0,0,0,.06); }
                .dark .vs-card { box-shadow: 0 4px 20px rgba(0,0,0,.4), 0 1px 3px rgba(0,0,0,.2); }
                .dark .vs-card:hover { box-shadow: 0 12px 35px rgba(237,212,132,.12), 0 4px 10px rgba(0,0,0,.4); }
                .vs-card-warning { border-left: 5px solid #f59e0b; background: linear-gradient(135deg, rgba(245,158,11,.03) 0%, transparent 60%); }
                .vs-card-success { border-left: 5px solid #16a34a; background: linear-gradient(135deg, rgba(22,163,74,.03) 0%, transparent 60%); }
                .vs-card-danger { border-left: 5px solid #dc2626; background: linear-gradient(135deg, rgba(220,38,38,.03) 0%, transparent 60%); }
                .fi-sidebar-item-active .fi-sidebar-item-label,
                .fi-sidebar-item-active .fi-sidebar-item-label span,
                .fi-sidebar-item-active a,
                .fi-sidebar-item-active > a { color: #1a1a1a !important; font-weight: 700 !important; }
                .fi-sidebar-item-active svg { color: #1a1a1a !important; }
                .fi-sidebar-item-active * { font-weight: 700 !important; }
                .dark .fi-sidebar-item-active .fi-sidebar-item-label,
                .dark .fi-sidebar-item-active a { color: #fff !important; }
                .dark .fi-sidebar-item-active svg { color: #fff !important; }
                .fi-btn[type=submit], button.fi-btn.fi-color-primary { background-color: #edd484 !important; color: #111827 !important; border-color: #d4ba6a !important; font-weight: 700 !important; }
                .fi-btn[type=submit]:hover, button.fi-btn.fi-color-primary:hover { background-color: #dcb15a !important; }
                .dark .fi-btn[type=submit], .dark button.fi-btn.fi-color-primary { background-color: #edd484 !important; color: #111827 !important; }
                .fi-header-actions .fi-btn { font-weight: 700 !important; }
                a.fi-btn.fi-color-primary { background-color: #edd484 !important; color: #111827 !important; border-color: #d4ba6a !important; font-weight: 700 !important; }
                a.fi-btn.fi-color-primary:hover { background-color: #dcb15a !important; }
                .dark a.fi-btn.fi-color-primary { background-color: #edd484 !important; color: #111827 !important; }
                .fi-main-ctn a.fi-btn.fi-color-primary,
                .fi-main-ctn a.fi-btn[class*=fi-color-primary],
                .fi-header a.fi-btn.fi-color-primary,
                .fi-header a.fi-btn[class*=fi-color-primary] { background-color: #edd484 !important; color: #111827 !important; font-weight: 700 !important; }
                .fi-main-ctn a.fi-btn.fi-color-primary:hover,
                .fi-header a.fi-btn.fi-color-primary:hover { background-color: #dcb15a !important; }
                .fi-fo-toggle {
                    width: 46px !important;
                    height: 26px !important;
                    align-items: center !important;
                    background: #d9deda !important;
                    border: 1px solid rgba(23, 34, 31, .12) !important;
                    box-shadow: inset 0 1px 3px rgba(23, 34, 31, .18) !important;
                }
                .fi-fo-toggle[aria-checked="true"] {
                    background: linear-gradient(135deg, #d0a72d 0%, #b88a13 100%) !important;
                    border-color: rgba(184, 138, 19, .35) !important;
                    box-shadow: inset 0 1px 2px rgba(83, 61, 8, .22), 0 3px 8px rgba(184, 138, 19, .18) !important;
                }
                .fi-fo-toggle > span {
                    width: 20px !important;
                    height: 20px !important;
                    background: #fff !important;
                    box-shadow: 0 2px 5px rgba(23, 34, 31, .28) !important;
                }
                .fi-fo-toggle:focus-visible {
                    outline: 2px solid rgba(208, 167, 45, .65) !important;
                    outline-offset: 2px !important;
                }
                html.dark { color-scheme: light !important; }
                .fi-theme-switcher-btn,
                .fi-theme-switcher { display: none !important; }
                ::-webkit-scrollbar { width: 12px; height: 12px; }
                ::-webkit-scrollbar-track { background: transparent; }
                ::-webkit-scrollbar-thumb { background-color: #edd484 !important; border-radius: 20px !important; border: 3px solid transparent !important; background-clip: content-box !important; }
                ::-webkit-scrollbar-thumb:hover { background-color: #dcb15a !important; }
                * { scrollbar-width: thin; scrollbar-color: #edd484 transparent !important; }
                .fi-input-wrp .fi-input-wrp-prefix {
                    border-color: rgba(156, 163, 175, .28) !important;
                    box-shadow: none !important;
                }
                .fi-input-wrp:focus-within .fi-input-wrp-prefix {
                    border-color: rgba(212, 186, 106, .65) !important;
                    box-shadow: none !important;
                }
                .fi-input-wrp .fi-input,
                .fi-input-wrp .fi-input:focus {
                    --tw-ring-color: transparent !important;
                    --tw-ring-shadow: 0 0 #0000 !important;
                }

                /* ══════════════════════════════════════════════════════
                   CHECKLIST DE SEGURIDAD — Diseño Premium #EDD484
                   Selectores basados en la estructura real de Filament v3
                ══════════════════════════════════════════════════════ */

                /* ── Checklist: interruptores de cumplimiento ── */
                .fi-fo-checkbox-list {
                    background: #fff !important;
                    border: 1px solid #edf0ec !important;
                    border-radius: 14px !important;
                    overflow: hidden !important;
                    box-shadow: 0 6px 18px rgba(23, 34, 31, .045) !important;
                    padding: 0 !important;
                    margin-top: 8px !important;
                    gap: 0 !important;
                }

                .fi-fo-checkbox-list > div {
                    padding-top: 0 !important;
                    margin-top: 0 !important;
                }

                label.fi-fo-checkbox-list-option-label {
                    display: flex !important;
                    align-items: center !important;
                    min-height: 58px !important;
                    position: relative !important;
                    gap: 0 !important;
                    background: #fff !important;
                    border-bottom: 1px solid #edf0ec !important;
                    padding: 13px 20px 13px 82px !important;
                    margin: 0 !important;
                    cursor: pointer !important;
                    transition: background .18s ease, box-shadow .18s ease !important;
                }

                label.fi-fo-checkbox-list-option-label:hover {
                    background: #fafbf8 !important;
                }

                .fi-fo-checkbox-list > div:last-child label.fi-fo-checkbox-list-option-label {
                    border-bottom: none !important;
                }

                label.fi-fo-checkbox-list-option-label:has(input.fi-checkbox-input:checked) {
                    background: #fffaf0 !important;
                }

                /* Pista del interruptor */
                label.fi-fo-checkbox-list-option-label::before {
                    content: '' !important;
                    position: absolute !important;
                    left: 20px !important;
                    top: 50% !important;
                    width: 46px !important;
                    height: 26px !important;
                    border-radius: 999px !important;
                    background: #d9deda !important;
                    border: 1px solid rgba(23, 34, 31, .12) !important;
                    box-shadow: inset 0 1px 3px rgba(23, 34, 31, .18) !important;
                    transform: translateY(-50%) !important;
                    transition: background .2s ease, box-shadow .2s ease !important;
                }

                /* Perilla del interruptor */
                label.fi-fo-checkbox-list-option-label::after {
                    content: '' !important;
                    position: absolute !important;
                    left: 23px !important;
                    top: 50% !important;
                    width: 20px !important;
                    height: 20px !important;
                    border-radius: 999px !important;
                    background: #fff !important;
                    box-shadow: 0 2px 5px rgba(23, 34, 31, .28) !important;
                    transform: translateY(-50%) !important;
                    transition: left .2s cubic-bezier(.2, .8, .2, 1), background .2s ease, box-shadow .2s ease !important;
                }

                label.fi-fo-checkbox-list-option-label:has(input.fi-checkbox-input:checked)::before {
                    background: linear-gradient(135deg, #d0a72d 0%, #b88a13 100%) !important;
                    box-shadow: inset 0 1px 2px rgba(83, 61, 8, .22), 0 3px 8px rgba(184, 138, 19, .23) !important;
                }

                label.fi-fo-checkbox-list-option-label:has(input.fi-checkbox-input:checked)::after {
                    content: '✓' !important;
                    left: 47px !important;
                    color: #b88a13 !important;
                    font-size: 16px !important;
                    font-weight: 900 !important;
                    line-height: 22px !important;
                    text-align: center !important;
                }

                .fi-fo-checkbox-list label.fi-fo-checkbox-list-option-label:has(input.fi-checkbox-input:checked)::before {
                    border-color: rgba(184, 138, 19, .35) !important;
                    box-shadow: inset 0 1px 2px rgba(83, 61, 8, .22), 0 3px 8px rgba(184, 138, 19, .18) !important;
                }

                .fi-fo-checkbox-list label.fi-fo-checkbox-list-option-label:has(input.fi-checkbox-input:checked)::after {
                    content: '' !important;
                    left: 43px !important;
                    color: transparent !important;
                    font-size: 0 !important;
                    line-height: 20px !important;
                }

                span.fi-fo-checkbox-list-option-label {
                    font-size: 14px !important;
                    font-weight: 650 !important;
                    color: #26332f !important;
                    line-height: 1.45 !important;
                    overflow: hidden !important;
                    word-break: break-words !important;
                }

                /* El input sigue presente para teclado y Livewire; se oculta visualmente. */
                .fi-fo-checkbox-list input.fi-checkbox-input {
                    position: absolute !important;
                    width: 1px !important;
                    height: 1px !important;
                    opacity: 0 !important;
                    pointer-events: none !important;
                }

                label.fi-fo-checkbox-list-option-label:has(input.fi-checkbox-input:focus-visible) {
                    z-index: 1 !important;
                    box-shadow: inset 0 0 0 2px #d0a72d !important;
                }

                /* ── 6. TEXTO "Opcional" en items opcionales ── */
                span.fi-fo-checkbox-list-option-label:has(+ *) {
                    /* fallback: se aplica en todos por defecto */
                }

                /* ── 7. LABEL del campo (encima del checklist) — estilo mejorado ── */
                .fi-fo-field-wrp-label span:has(+ span.fi-fo-field-wrp-label-required-indicator),
                .fi-fo-field-wrp label {
                    /* no tocar el label global */
                }
                </style>
                HTML;
                },
            )
            ->brandLogo('https://cdn.cookielaw.org/logos/4d0f0420-9941-4d59-903c-3cb61fde5441/759044e8-70d7-44b7-9301-2404184257f1/fb1eeb1f-1479-4532-bf3b-30f5edc3bb17/logo.png')
            ->brandLogoHeight('3.5rem')
            ->favicon(asset('favicon.svg'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\CustomAccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \App\Http\Middleware\SetLocale::class,
            ])

            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
