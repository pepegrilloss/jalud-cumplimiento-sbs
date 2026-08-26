<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ClientesStats;
use App\Filament\Widgets\CustomAccountWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getHeaderWidgets(): array
    {
        return [];
    }

    public function getWidgets(): array
    {
        return [
            CustomAccountWidget::class,
            ClientesStats::class,
        ];
    }
}
