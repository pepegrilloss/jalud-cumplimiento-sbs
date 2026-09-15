<?php

namespace App\Filament\Cumplimiento\Resources;

use App\Filament\Cumplimiento\Resources\UserResource\Pages;
use App\Models\Sede;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $modelLabel = 'usuario';

    protected static ?string $pluralModelLabel = 'Usuarios de Cumplimiento';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $sedeId = auth()->user()?->getEffectiveSedeId();

        return parent::getEloquentQuery()->where(function (Builder $query) use ($sedeId): void {
            $query->where('SedeID', $sedeId)->orWhere('id', auth()->id());
        });
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Acceso a Cumplimiento')->schema([
                Forms\Components\TextInput::make('name')->label('Nombre completo')->required()->maxLength(255),
                Forms\Components\TextInput::make('username')->label('Usuario')->required()->alphaDash()->unique(ignoreRecord: true)->maxLength(100),
                Forms\Components\TextInput::make('email')->label('Correo')->email()->unique(ignoreRecord: true)->maxLength(255),
                Forms\Components\Select::make('SedeID')->label('Sede asignada')
                    ->options(fn (): array => Sede::query()->operativas()->whereKey(auth()->user()?->getEffectiveSedeId())->pluck('Nombre', 'SedeID')->all())
                    ->default(fn () => auth()->user()?->getEffectiveSedeId())
                    ->required()->disabled()->dehydrated(),
                Forms\Components\TextInput::make('password')->label('Contraseña')->password()->revealable()->required(fn (string $operation): bool => $operation === 'create')->minLength(10)->dehydrateStateUsing(fn (string $state): string => Hash::make($state))->dehydrated(fn (?string $state): bool => filled($state)),
                Forms\Components\Select::make('roles')->label('Roles')->relationship('roles', 'name')->multiple()->preload()->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('username')->label('Usuario')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('sede.Nombre')->label('Sede')->placeholder('Sin sede'),
            Tables\Columns\TextColumn::make('roles.name')->label('Roles')->badge(),
            Tables\Columns\TextColumn::make('created_at')->label('Creado')->dateTime('d/m/Y H:i')->sortable(),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
