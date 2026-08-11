<?php
namespace App\Filament\Cumplimiento\Resources\DeclaracionTrabajadorResource\Pages;
use App\Filament\Cumplimiento\Resources\DeclaracionTrabajadorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListDeclaracionesTrabajador extends ListRecords { protected static string $resource = DeclaracionTrabajadorResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()->label('Nueva ficha de trabajador')]; } }
