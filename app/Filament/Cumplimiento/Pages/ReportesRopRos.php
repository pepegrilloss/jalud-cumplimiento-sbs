<?php

namespace App\Filament\Cumplimiento\Pages;

use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ReportesRopRos extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box-arrow-down';

    protected static ?string $navigationGroup = 'Cumplimiento';

    protected static ?string $navigationLabel = 'Reportes ROP y ROS';

    protected static ?string $title = 'Reportes ROP y ROS';

    protected static ?string $slug = 'reportes-rop-ros';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.cumplimiento.pages.reportes-rop-ros';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'Desde' => now()->startOfMonth()->toDateString(),
            'Hasta' => now()->toDateString(),
        ]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('descargar_reportes_rop_ros') ?? false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Rango de fechas')->schema([
                    Forms\Components\DatePicker::make('Desde')->label('Desde')->required()->native(false),
                    Forms\Components\DatePicker::make('Hasta')->label('Hasta')->required()->native(false),
                ])->columns(2),
            ])
            ->statePath('data');
    }

    public function descargar(): void
    {
        $datos = validator($this->form->getState(), [
            'Desde' => ['required', 'date'],
            'Hasta' => ['required', 'date', 'after_or_equal:Desde'],
        ])->validate();

        $this->redirect(route('cumplimiento.reportes.rop-ros', [
            'desde' => $datos['Desde'],
            'hasta' => $datos['Hasta'],
        ]), navigate: false);
    }
}
