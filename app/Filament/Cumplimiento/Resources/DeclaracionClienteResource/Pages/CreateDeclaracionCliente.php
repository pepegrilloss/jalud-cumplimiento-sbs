<?php
namespace App\Filament\Cumplimiento\Resources\DeclaracionClienteResource\Pages;
use App\Filament\Cumplimiento\Resources\DeclaracionClienteResource;
use App\Models\Cliente;
use App\Models\DeclaracionCliente;
use Filament\Resources\Pages\CreateRecord;
class CreateDeclaracionCliente extends CreateRecord { protected static string $resource=DeclaracionClienteResource::class; protected function mutateFormDataBeforeCreate(array$data):array{$c=DeclaracionClienteResource::clientesDisponiblesQuery()->findOrFail($data['ClienteID']);$data['SedeID']=$c->SedeID;$data['NumeroVersion']=(int)DeclaracionCliente::withoutGlobalScope('sede')->where('ClienteID',$c->getKey())->max('NumeroVersion')+1;$data['Estado']='BORRADOR';return$data;} protected function afterCreate():void{app(\App\Services\Declaraciones\DeclaracionClienteService::class)->sanitizarRelaciones($this->record);} protected function getFormActions():array{return[];} }
