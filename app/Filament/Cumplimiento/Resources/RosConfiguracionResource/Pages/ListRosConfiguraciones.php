<?php
namespace App\Filament\Cumplimiento\Resources\RosConfiguracionResource\Pages;
use App\Filament\Cumplimiento\Resources\RosConfiguracionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListRosConfiguraciones extends ListRecords { protected static string $resource = RosConfiguracionResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
