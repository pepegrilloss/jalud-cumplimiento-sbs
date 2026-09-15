<?php

namespace App\Filament\Cumplimiento\Resources\RopCatalogoItemResource\Pages;

use App\Filament\Cumplimiento\Resources\RopCatalogoItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRopCatalogoItems extends ListRecords
{
    protected static string $resource = RopCatalogoItemResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
