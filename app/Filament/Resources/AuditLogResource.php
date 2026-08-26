<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Helpers\UsuarioHelper;
use App\Models\AuditLog;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Auditoría';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?string $modelLabel = 'Registro de auditoría';

    protected static ?string $pluralModelLabel = 'Auditoría';

    protected static ?int $navigationSort = 90;

    public static function canAccess(): bool
    {
        return UsuarioHelper::esTI();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('CREATED_AT')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ACCION')
                    ->label('Acción')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'CREAR' => 'success',
                        'ACTUALIZAR' => 'warning',
                        'ELIMINAR' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('RESUMEN')
                    ->label('Resumen')
                    ->searchable()
                    ->wrap()
                    ->limit(55),

                Tables\Columns\TextColumn::make('USUARIO')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('AREA')
                    ->label('Área')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('EMPRESA')
                    ->label('Razón Social (Empresa)')
                    ->searchable()
                    ->limit(35)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ID_MP_VST_VISITA')
                    ->label('Visita')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('TABLA')
                    ->label('Tabla')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('IP')
                    ->label('IP')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('ACCION')
                    ->label('Acción')
                    ->options([
                        'CREAR' => 'Crear',
                        'ACTUALIZAR' => 'Actualizar',
                        'ELIMINAR' => 'Eliminar',
                    ]),

                Tables\Filters\SelectFilter::make('TABLA')
                    ->label('Tabla')
                    ->options([
                        'MP_VST_CLIENTE' => 'Clientes',
                        'MP_VST_ENLACE_CLIENTE' => 'Enlaces de registro',
                        'MP_VST_REGISTRO_CLIENTE' => 'DNI recibidos',
                        'MP_VST_VISITA' => 'Solicitudes',
                        'MP_VST_PERSONAL' => 'Personal',
                        'MP_VST_APROBACION' => 'Aprobaciones',
                        'MP_VST_REGISTRO_INGRESO' => 'Ingresos / salidas',
                        'MP_VST_CHECKLIST' => 'Checklist',
                        'MP_VST_SCTR_VIGENCIA' => 'SCTR',
                        'MP_VST_HISTORIAL_APROBACION' => 'Historial aprobación',
                    ]),

                Tables\Filters\Filter::make('fecha')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('desde')
                            ->label('Desde'),
                        \Filament\Forms\Components\DatePicker::make('hasta')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['desde'], fn (Builder $q, string $date) => $q->whereDate('CREATED_AT', '>=', $date))
                            ->when($data['hasta'], fn (Builder $q, string $date) => $q->whereDate('CREATED_AT', '<=', $date));
                    })
                    ->label('Fecha'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver detalle'),
            ])
            ->defaultSort('CREATED_AT', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Evento')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('CREATED_AT')
                            ->label('Fecha')
                            ->dateTime('d/m/Y H:i:s'),
                        Infolists\Components\TextEntry::make('ACCION')
                            ->label('Acción')
                            ->badge(),
                        Infolists\Components\TextEntry::make('EVENTO')
                            ->label('Evento'),
                        Infolists\Components\TextEntry::make('USUARIO')
                            ->label('Usuario'),
                        Infolists\Components\TextEntry::make('AREA')
                            ->label('Área'),
                        Infolists\Components\TextEntry::make('IP')
                            ->label('IP'),
                        Infolists\Components\TextEntry::make('RESUMEN')
                            ->label('Resumen')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Registro afectado')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('TABLA')
                            ->label('Tabla'),
                        Infolists\Components\TextEntry::make('ID_REGISTRO')
                            ->label('ID registro'),
                        Infolists\Components\TextEntry::make('ID_MP_VST_VISITA')
                            ->label('ID visita'),
                        Infolists\Components\TextEntry::make('EMPRESA')
                            ->label('Razón Social (Empresa)')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Cambios')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('VALORES_ANTERIORES')
                            ->label('Antes')
                            ->formatStateUsing(fn ($state): string => self::formatJson($state))
                            ->prose(),
                        Infolists\Components\TextEntry::make('VALORES_NUEVOS')
                            ->label('Después')
                            ->formatStateUsing(fn ($state): string => self::formatJson($state))
                            ->prose(),
                    ]),

                Infolists\Components\Section::make('Contexto técnico')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('METODO')
                            ->label('Método'),
                        Infolists\Components\TextEntry::make('URL')
                            ->label('URL')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('USER_AGENT')
                            ->label('Navegador')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('METADATA')
                            ->label('Metadata')
                            ->formatStateUsing(fn ($state): string => self::formatJson($state))
                            ->columnSpanFull()
                            ->prose(),
                    ]),
            ]);
    }

    public static function formatJson($state): string
    {
        if (!$state) {
            return '-';
        }

        return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '-';
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }
}
