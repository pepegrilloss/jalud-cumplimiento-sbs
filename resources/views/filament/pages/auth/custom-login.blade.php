<div class="mlp-login-form-inner">
    {{-- Logo --}}
    <div class="mlp-login-logo">
        <img src="{{ asset('images/mlp2.png') }}" alt="Medlog">
    </div>

    {{-- Heading --}}
    <h2 class="mlp-login-heading">{{ $this->getHeading() }}</h2>
    <p class="mlp-login-subheading">{{ $this->getSubHeading() }}</p>

    {{-- Separator --}}
    <div class="mlp-login-separator">
        <div class="mlp-login-separator-line"></div>
        <div class="mlp-login-separator-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
            </svg>
        </div>
        <div class="mlp-login-separator-line"></div>
    </div>

    {{-- Form --}}
    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

    <x-filament-panels::form id="form" wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()" />
    </x-filament-panels::form>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}

    {{-- Footer --}}
    <div class="mlp-login-footer">
        <p>&copy; {{ date('Y') }} MEDLOG - Medlog Piura. Todos los derechos reservados.</p>
    </div>
</div>
