@php
use Filament\Support\Enums\MaxWidth;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ __('filament-panels::layout.direction') ?? 'ltr' }}" @class([
    'fi min-h-screen',
])>

<head>
    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::HEAD_START, scopes: $livewire->getRenderHookScopes()) }}

    <meta charset="utf-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    @if ($favicon = filament()->getFavicon())
        <link rel="icon" href="{{ $favicon }}" />
    @endif

    @php
        $title = trim(strip_tags(($livewire ?? null)?->getTitle() ?? ''));
        $brandName = trim(strip_tags(filament()->getBrandName()));
    @endphp

    <title>
        {{ filled($title) ? "{$title} - " : null }} {{ $brandName }}
    </title>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::STYLES_BEFORE, scopes: $livewire->getRenderHookScopes()) }}

    <style>
        [x-cloak=''],
        [x-cloak='x-cloak'],
        [x-cloak='1'] {
            display: none !important;
        }

        @media (max-width: 1023px) {
            [x-cloak='-lg'] {
                display: none !important;
            }
        }

        @media (min-width: 1024px) {
            [x-cloak='lg'] {
                display: none !important;
            }
        }
    </style>

    @filamentStyles

    {{ filament()->getTheme()->getHtml() }}
    {{ filament()->getFontHtml() }}

    <style>
        :root {
            --font-family: '{!! filament()->getFontFamily() !!}';
            --sidebar-width: {{ filament()->getSidebarWidth() }};
            --collapsed-sidebar-width: {{ filament()->getCollapsedSidebarWidth() }};
            --default-theme-mode: {{ filament()->getDefaultThemeMode()->value }};
        }
    </style>

    {{-- ===== MEDLOG LOGIN STYLES ===== --}}
    <style>
        body.fi-body {
            background: #ffffff !important;
            overflow: hidden;
            margin: 0;
            padding: 0;
        }

        .mlp-login-container {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        .mlp-login-video-panel {
            position: relative;
            width: 50%;
            min-height: 100vh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .mlp-login-video-panel video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: 20% center;
        }

        .mlp-video-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(34, 34, 33, 0.45);
            z-index: 1;
        }

        .mlp-video-content {
            position: relative;
            z-index: 2;
            text-align: center;
            padding: 2.5rem;
            color: #fff;
        }

        .mlp-video-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            padding: 0.5rem 1.25rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #ffffff;
            margin-bottom: 1.75rem;
        }

        .mlp-video-badge svg {
            width: 0px;
            height: 0px;
            display: none;
        }

        .mlp-video-title {
            font-size: 4rem;
            font-weight: 800;
            line-height: 1.05;
            margin: 0 0 1rem 0;
            letter-spacing: -0.03em;
        }

        .mlp-video-title span {
            color: #ffffff;
        }

        .mlp-video-subtitle {
            font-size: 1.15rem;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.90);
            max-width: 400px;
            margin: 0 auto;
            font-weight: 400;
        }

        .mlp-login-form-panel {
            width: 50%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2.5rem;
            background: #ffffff;
            position: relative;
            overflow-y: auto;
        }

        .mlp-login-form-wrapper {
            width: 100%;
            max-width: 400px;
        }

        .mlp-login-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 1.75rem;
        }

        .mlp-login-logo img {
            height: 52px;
            width: auto;
            object-fit: contain;
        }

        .mlp-login-separator {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.75rem;
        }

        .mlp-login-separator-line {
            flex: 1;
            height: 1px;
            background: #e0e0e0;
        }

        .mlp-login-separator-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: transparent;
        }

        .mlp-login-separator-icon svg {
            width: 24px;
            height: 24px;
            color: #EED484;
        }

        .mlp-login-heading {
            text-align: center;
            margin: 0 0 0.4rem 0;
            font-size: 1.85rem;
            font-weight: 700;
            color: #222221;
            letter-spacing: -0.02em;
        }

        .mlp-login-subheading {
            text-align: center;
            margin: 0 0 2rem 0;
            font-size: 0.95rem;
            color: #888;
            font-weight: 400;
        }

        .mlp-login-form-wrapper .fi-fo-field-wrp {
            margin-bottom: 0.35rem;
        }

        .mlp-login-form-wrapper .fi-input-wrp {
            border-radius: 6px !important;
            border: 1px solid #e1e8ed !important;
            background: #eff3f9 !important;
            transition: all 0.2s ease;
            box-shadow: none !important;
        }

        .mlp-login-form-wrapper .fi-input-wrp:focus-within {
            border-color: #EED484 !important;
            box-shadow: 0 0 0 1px #EED484 !important;
            background: #fff !important;
        }

        .mlp-login-form-wrapper .fi-input {
            font-size: 0.95rem !important;
            padding: 0.6rem 0.75rem !important;
        }

        .mlp-login-form-wrapper .fi-fo-field-wrp-label {
            font-weight: 600 !important;
            color: #222221 !important;
        }

        .mlp-login-form-wrapper sup.text-danger-600 {
            color: #dc3545 !important;
            font-size: 1rem !important;
        }

        .mlp-login-form-wrapper .fi-form-actions .fi-btn {
            width: 100% !important;
            border-radius: 6px !important;
            padding: 0.75rem 1.5rem !important;
            font-size: 0.95rem !important;
            font-weight: 600 !important;
            background: #EED484 !important;
            border: none !important;
            color: #222221 !important;
            box-shadow: none !important;
            transition: all 0.2s ease !important;
        }

        .mlp-login-form-wrapper .fi-form-actions .fi-btn:hover {
            background: #e2c572 !important;
            transform: translateY(-1px);
        }

        .mlp-login-form-wrapper .fi-form-actions .fi-btn:active {
            transform: translateY(0);
        }

        .mlp-login-form-wrapper .fi-checkbox-input:checked {
            background-color: #EED484 !important;
            border-color: #d4a94e !important;
        }

        .mlp-login-form-wrapper .fi-checkbox-input {
            width: 1rem !important;
            height: 1rem !important;
            opacity: 1 !important;
            pointer-events: auto !important;
            border-radius: 4px !important;
            border: 1px solid #d8d8d8 !important;
            color: #222221 !important;
            box-shadow: none !important;
        }

        .mlp-login-form-wrapper .fi-checkbox-input:focus {
            border-color: #d4a94e !important;
            box-shadow: 0 0 0 3px rgba(238, 212, 132, .35) !important;
        }

        .mlp-login-footer {
            text-align: center;
            margin-top: 2.25rem;
            padding-top: 1.25rem;
            border-top: 1px solid #f0f0f0;
        }

        .mlp-login-footer p {
            font-size: 0.72rem;
            color: #aaa;
            margin: 0;
        }

        @media (max-width: 1024px) {
            .mlp-login-container {
                flex-direction: column;
            }

            body.fi-body {
                overflow: auto !important;
            }

            .mlp-login-video-panel {
                width: 100%;
                min-height: 260px;
                max-height: 32vh;
            }

            .mlp-video-title {
                font-size: 2rem;
            }

            .mlp-video-subtitle {
                font-size: 0.9rem;
            }

            .mlp-login-form-panel {
                width: 100%;
                min-height: auto;
                flex: 1;
                padding: 2rem 1.5rem;
                justify-content: flex-start;
                padding-top: 2.5rem;
            }
        }

        @media (max-width: 640px) {
            .mlp-login-video-panel {
                min-height: 200px;
                max-height: 26vh;
            }

            .mlp-video-content {
                padding: 1.5rem;
            }

            .mlp-video-title {
                font-size: 1.5rem;
            }

            .mlp-video-badge {
                font-size: 0.6rem;
                padding: 0.35rem 0.9rem;
            }

            .mlp-login-form-panel {
                padding: 1.5rem 1rem;
            }

            .mlp-login-heading {
                font-size: 1.35rem;
            }
        }

        @keyframes mlpFadeInUp {
            from {
                opacity: 0;
                transform: translateY(18px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes mlpFadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .mlp-login-form-wrapper {
            animation: mlpFadeInUp 0.55s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .mlp-video-content {
            animation: mlpFadeIn 0.9s ease 0.25s forwards;
            opacity: 0;
        }

    </style>

    @stack('styles')

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::STYLES_AFTER, scopes: $livewire->getRenderHookScopes()) }}

    <script>
        document.documentElement.classList.remove('dark')
        localStorage.setItem('theme', 'light')
    </script>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::HEAD_END, scopes: $livewire->getRenderHookScopes()) }}
</head>

<body class="fi-body fi-panel-{{ filament()->getId() }} min-h-screen font-normal antialiased">
    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::BODY_START, scopes: $livewire->getRenderHookScopes()) }}

    <div class="mlp-login-container">
        {{-- LEFT: VIDEO --}}
        <div class="mlp-login-video-panel">
            <video autoplay muted loop playsinline>
                <source src="{{ asset('videos/medlog_banner_desktop.mp4') }}" type="video/mp4">
            </video>

            <div class="mlp-video-overlay"></div>

            <div class="mlp-video-content">
                <div class="mlp-video-badge">
                    SISTEMA DE VISITAS
                </div>
                <h1 class="mlp-video-title">
                    BIENVENIDO A<br>MEDLOG
                </h1>
                <p class="mlp-video-subtitle">
                    Medlog Piura.<br>Plataforma para la gestión y control de visitas.
                </p>
            </div>

        </div>

        {{-- RIGHT: FORM --}}
        <div class="mlp-login-form-panel">
            <div class="mlp-login-form-wrapper">
                {{ $slot }}
            </div>
        </div>
    </div>

    @livewire(Filament\Livewire\Notifications::class)

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SCRIPTS_BEFORE, scopes: $livewire->getRenderHookScopes()) }}

    @filamentScripts(withCore: true)

    @stack('scripts')

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SCRIPTS_AFTER, scopes: $livewire->getRenderHookScopes()) }}
    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::BODY_END, scopes: $livewire->getRenderHookScopes()) }}
</body>

</html>
