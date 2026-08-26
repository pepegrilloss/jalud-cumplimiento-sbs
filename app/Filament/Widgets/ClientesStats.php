<?php

namespace App\Filament\Widgets;

use App\Models\Cliente;
use App\Models\EnlaceCliente;
use App\Models\RegistroCliente;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ClientesStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Clientes activos', Cliente::query()->where('ACTIVO', true)->count())
                ->description('Empresas habilitadas')
                ->descriptionIcon('heroicon-m-building-office-2'),
            Stat::make('Enlaces activos', EnlaceCliente::query()->where('ACTIVO', true)->count())
                ->description('Links disponibles')
                ->descriptionIcon('heroicon-m-link'),
            Stat::make('DNI recibidos', RegistroCliente::query()->count())
                ->description('Registros totales')
                ->descriptionIcon('heroicon-m-identification'),
        ];
    }
}
