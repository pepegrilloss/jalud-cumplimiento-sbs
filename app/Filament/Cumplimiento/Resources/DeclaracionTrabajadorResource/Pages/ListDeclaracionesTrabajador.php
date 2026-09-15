<?php

namespace App\Filament\Cumplimiento\Resources\DeclaracionTrabajadorResource\Pages;

use App\Filament\Cumplimiento\Resources\DeclaracionTrabajadorResource;
use App\Models\DeclaracionTrabajador;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListDeclaracionesTrabajador extends ListRecords
{
    protected static string $resource = DeclaracionTrabajadorResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Nueva ficha de trabajador')];
    }

    public function getTabs(): array
    {
        return [
            'activos' => Tab::make('Trabajadores activos')
                ->icon('heroicon-o-user-group')
                ->badge($this->contarPorEstado(true))
                ->modifyQueryUsing(fn (Builder $query): Builder => $this->filtrarPorEstado($query, true)),
            'baja' => Tab::make('Trabajadores de baja')
                ->icon('heroicon-o-user-minus')
                ->badge($this->contarPorEstado(false))
                ->modifyQueryUsing(fn (Builder $query): Builder => $this->filtrarPorEstado($query, false)),
            'todos' => Tab::make('Todos')
                ->badge(DeclaracionTrabajador::query()->distinct()->count('TrabajadorID')),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'activos';
    }

    private function filtrarPorEstado(Builder $query, bool $activo): Builder
    {
        return $query->whereHas('trabajador', fn (Builder $trabajadores): Builder => $trabajadores
            ->where('Activo', $activo));
    }

    private function contarPorEstado(bool $activo): int
    {
        return DeclaracionTrabajador::query()
            ->whereHas('trabajador', fn (Builder $trabajadores): Builder => $trabajadores
                ->where('Activo', $activo))
            ->distinct()
            ->count('TrabajadorID');
    }
}
