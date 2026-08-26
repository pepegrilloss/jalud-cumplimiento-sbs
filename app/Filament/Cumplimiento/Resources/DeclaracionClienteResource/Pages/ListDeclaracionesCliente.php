<?php

namespace App\Filament\Cumplimiento\Resources\DeclaracionClienteResource\Pages;

use App\Filament\Cumplimiento\Resources\DeclaracionClienteResource;
use App\Models\DeclaracionCliente;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListDeclaracionesCliente extends ListRecords
{
    protected static string $resource = DeclaracionClienteResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Nueva declaración de cliente')];
    }

    public function getTabs(): array
    {
        return [
            'activos' => Tab::make('Clientes activos')
                ->badge($this->contarPorEstado(true))
                ->modifyQueryUsing(fn (Builder $query): Builder => $this->filtrarPorEstado($query, true)),
            'baja' => Tab::make('Clientes de baja')
                ->badge($this->contarPorEstado(false))
                ->modifyQueryUsing(fn (Builder $query): Builder => $this->filtrarPorEstado($query, false)),
            'todos' => Tab::make('Todos')
                ->badge(DeclaracionCliente::withoutGlobalScope('sede')->distinct()->count('ClienteID')),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'activos';
    }

    private function filtrarPorEstado(Builder $query, bool $activo): Builder
    {
        return $query->whereHas('cliente', fn (Builder $clientes): Builder => $clientes
            ->withoutGlobalScopes()
            ->where('Activo', $activo));
    }

    private function contarPorEstado(bool $activo): int
    {
        return DeclaracionCliente::withoutGlobalScope('sede')
            ->whereHas('cliente', fn (Builder $clientes): Builder => $clientes
                ->withoutGlobalScopes()
                ->where('Activo', $activo))
            ->distinct()
            ->count('ClienteID');
    }
}
