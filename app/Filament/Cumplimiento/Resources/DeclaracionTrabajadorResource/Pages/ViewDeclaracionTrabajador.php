<?php
namespace App\Filament\Cumplimiento\Resources\DeclaracionTrabajadorResource\Pages;
use App\Filament\Cumplimiento\Resources\DeclaracionTrabajadorResource;
use App\Services\Declaraciones\DeclaracionTrabajadorService;
use App\Services\Declaraciones\GeneradorPdfDeclaraciones;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
class ViewDeclaracionTrabajador extends ViewRecord
{
    protected static string $resource = DeclaracionTrabajadorResource::class;
    protected function getHeaderActions(): array
    {
        $tiene = fn (string $tipo) => $this->record->documentos()->where('TipoDocumento', $tipo)->exists();
        $tieneTodos = fn () => $tiene('ANEXO_1') && $tiene('ANEXO_2') && $tiene('ANEXO_3');

        return [
            Actions\EditAction::make()->visible(fn()=> $this->record->Estado==='BORRADOR'),
            Actions\Action::make('finalizar')->label('Finalizar declaración')->icon('heroicon-o-lock-closed')->color('success')->requiresConfirmation()->visible(fn()=> $this->record->Estado==='BORRADOR')->authorize('finalizar')->action(function(){ app(DeclaracionTrabajadorService::class)->finalizar($this->record); $this->record->refresh(); Notification::make()->success()->title('Declaración finalizada')->send(); }),
            Actions\Action::make('generar')->label('Generar declaraciones')->icon('heroicon-o-document-arrow-down')->color('success')->visible(fn()=> $this->record->Estado==='FINALIZADO' && ! $tieneTodos())->authorize('generarPdf')->action(function(){ app(GeneradorPdfDeclaraciones::class)->generarTrabajador($this->record); Notification::make()->success()->title('Anexos 1, 2 y 3 generados')->send(); }),
            Actions\ActionGroup::make([
                Actions\Action::make('ver1')->label('Ver Anexo 1')->url(fn()=>route('declaraciones.trabajadores.documentos.ver',[$this->record,'ANEXO_1']))->openUrlInNewTab()->visible(fn()=>$tiene('ANEXO_1')),
                Actions\Action::make('descargar1')->label('Descargar Anexo 1')->url(fn()=>route('declaraciones.trabajadores.documentos.descargar',[$this->record,'ANEXO_1']))->visible(fn()=>$tiene('ANEXO_1')),
                Actions\Action::make('ver2')->label('Ver Anexo 2')->url(fn()=>route('declaraciones.trabajadores.documentos.ver',[$this->record,'ANEXO_2']))->openUrlInNewTab()->visible(fn()=>$tiene('ANEXO_2')),
                Actions\Action::make('descargar2')->label('Descargar Anexo 2')->url(fn()=>route('declaraciones.trabajadores.documentos.descargar',[$this->record,'ANEXO_2']))->visible(fn()=>$tiene('ANEXO_2')),
                Actions\Action::make('ver3')->label('Ver Anexo 3')->url(fn()=>route('declaraciones.trabajadores.documentos.ver',[$this->record,'ANEXO_3']))->openUrlInNewTab()->visible(fn()=>$tiene('ANEXO_3')),
                Actions\Action::make('descargar3')->label('Descargar Anexo 3')->url(fn()=>route('declaraciones.trabajadores.documentos.descargar',[$this->record,'ANEXO_3']))->visible(fn()=>$tiene('ANEXO_3')),
                Actions\Action::make('todos')->label('Descargar todos (.zip)')->url(fn()=>route('declaraciones.trabajadores.documentos.todos',$this->record))->visible(fn()=>$tieneTodos()),
            ])->label('Ver / descargar anexos')->icon('heroicon-o-document-text')->color('primary')->button()->visible(fn()=>$tiene('ANEXO_1') || $tiene('ANEXO_2') || $tiene('ANEXO_3')),
            Actions\Action::make('nueva_version')->label('Crear nueva versión')->icon('heroicon-o-document-duplicate')->visible(fn()=> $this->record->Estado==='FINALIZADO' && auth()->user()?->can('nuevaVersion',$this->record))->authorize('nuevaVersion')->action(function(){ $n=app(DeclaracionTrabajadorService::class)->nuevaVersion($this->record); $this->redirect(static::$resource::getUrl('edit',['record'=>$n])); }),
        ];
    }
}
