<?php
namespace App\Filament\Cumplimiento\Resources\DeclaracionClienteResource\Pages;
use App\Filament\Cumplimiento\Resources\DeclaracionClienteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListDeclaracionesCliente extends ListRecords { protected static string $resource=DeclaracionClienteResource::class; protected function getHeaderActions():array{return[Actions\CreateAction::make()->label('Nueva declaración de cliente')];} }
