<?php

namespace App\Filament\Pages\Auth;

use App\Support\MlpVisitasHome;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    protected static string $layout = 'layouts.login';

    protected static string $view = 'filament.pages.auth.custom-login';

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->to(MlpVisitasHome::url());
        }

        if (request()->boolean('expired')) {
            Notification::make()
                ->title('Sesión expirada')
                ->body('Por seguridad, vuelve a iniciar sesión para continuar.')
                ->warning()
                ->send();
        }

        $this->form->fill();
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getLoginFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getRememberFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getLoginFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Usuario')
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1])
            ->rules(['required', 'string']);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'email' => $data['email'],
            'password' => $data['password'],
        ];
    }

    public function getHeading(): string | Htmlable
    {
        return 'Iniciar Sesión';
    }

    public function getSubHeading(): string | Htmlable | null
    {
        return 'Ingresa con tu usuario para acceder al portal';
    }

    protected function getAuthenticateFormAction(): \Filament\Actions\Action
    {
        return parent::getAuthenticateFormAction()
            ->label('Entrar');
    }
}
