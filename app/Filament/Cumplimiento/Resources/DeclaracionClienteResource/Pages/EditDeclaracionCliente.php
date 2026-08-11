<?php
namespace App\Filament\Cumplimiento\Resources\DeclaracionClienteResource\Pages;
use App\Filament\Cumplimiento\Resources\DeclaracionClienteResource;
use App\Services\Declaraciones\DeclaracionClienteService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditDeclaracionCliente extends EditRecord { protected static string $resource=DeclaracionClienteResource::class; protected function getHeaderActions():array{return[Actions\ViewAction::make()];} protected function afterSave():void{app(DeclaracionClienteService::class)->sanitizarRelaciones($this->record);} protected function getFormActions():array{return[];} }
