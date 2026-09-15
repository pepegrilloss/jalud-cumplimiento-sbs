<?php

namespace App\Filament\Cumplimiento\Pages;

use App\Filament\Cumplimiento\Resources\RopRegistroResource;
use App\Services\SedeContext;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class SelectSede extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $title = 'Seleccionar sede';

    protected static ?string $slug = 'seleccionar-sede';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.cumplimiento.pages.select-sede';

    public function getLayout(): string
    {
        return 'filament-panels::components.layout.base';
    }

    public function getSedes(): Collection
    {
        return app(SedeContext::class)->sedesPermitidas(auth()->user());
    }

    public function seleccionarSede(int $sedeId): void
    {
        app(SedeContext::class)->seleccionar(auth()->user(), $sedeId);
        Notification::make()->success()->title('Sede de trabajo seleccionada')->send();
        $this->redirect(RopRegistroResource::getUrl('index'));
    }
}
