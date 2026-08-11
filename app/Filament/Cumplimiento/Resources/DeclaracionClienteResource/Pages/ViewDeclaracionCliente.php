<?php
namespace App\Filament\Cumplimiento\Resources\DeclaracionClienteResource\Pages;
use App\Filament\Cumplimiento\Resources\DeclaracionClienteResource;
use App\Services\Declaraciones\DeclaracionClienteService;
use App\Services\Declaraciones\GeneradorPdfDeclaraciones;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
class ViewDeclaracionCliente extends ViewRecord
{
    protected static string $resource=DeclaracionClienteResource::class;
    protected function getHeaderActions():array
    {
        $tiene=fn()=>$this->record->documentos()->where('TipoDocumento','CLIENTE_REGIMEN_GENERAL')->exists();
        return [
            Actions\EditAction::make()->visible(fn()=>$this->record->Estado==='BORRADOR'),
            Actions\Action::make('finalizar')->label('Finalizar declaración')->icon('heroicon-o-lock-closed')->color('success')->requiresConfirmation()->visible(fn()=>$this->record->Estado==='BORRADOR')->authorize('finalizar')->action(function(){app(DeclaracionClienteService::class)->finalizar($this->record);$this->record->refresh();Notification::make()->success()->title('Declaración finalizada')->send();}),
            Actions\Action::make('generar')->label('Generar declaración jurada')->icon('heroicon-o-document-arrow-down')->color('success')->visible(fn()=>$this->record->Estado==='FINALIZADO' && ! $tiene())->authorize('generarPdf')->action(function(){app(GeneradorPdfDeclaraciones::class)->generarCliente($this->record);Notification::make()->success()->title('Declaración PDF generada')->send();}),
            Actions\Action::make('ver')->label('Ver declaración')->url(fn()=>route('declaraciones.clientes.documento.ver',$this->record))->openUrlInNewTab()->visible($tiene),
            Actions\Action::make('descargar')->label('Descargar declaración')->url(fn()=>route('declaraciones.clientes.documento.descargar',$this->record))->visible($tiene),
            Actions\Action::make('nueva_version')->label('Crear nueva versión')->icon('heroicon-o-document-duplicate')->visible(fn()=>$this->record->Estado==='FINALIZADO' && auth()->user()?->can('nuevaVersion',$this->record))->authorize('nuevaVersion')->action(function(){$n=app(DeclaracionClienteService::class)->nuevaVersion($this->record);$this->redirect(static::$resource::getUrl('edit',['record'=>$n]));}),
        ];
    }
}
