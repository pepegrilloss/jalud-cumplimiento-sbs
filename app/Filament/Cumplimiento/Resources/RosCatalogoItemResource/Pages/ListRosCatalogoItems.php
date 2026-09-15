<?php
namespace App\Filament\Cumplimiento\Resources\RosCatalogoItemResource\Pages;
use App\Filament\Cumplimiento\Resources\RosCatalogoItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListRosCatalogoItems extends ListRecords { protected static string $resource = RosCatalogoItemResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
