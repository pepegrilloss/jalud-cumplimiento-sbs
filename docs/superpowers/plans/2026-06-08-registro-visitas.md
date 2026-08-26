# Registro de Visitas - Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Crear un formulario Filament Resource para registrar visitas con personal ingresante y adjuntos de documentos (SCTR, DNI).

**Architecture:** Dos nuevas tablas (`MP_VST_VISITA` y `MP_VST_PERSONAL`) con relación 1:N. Un Filament Resource `VisitaResource` con Repeater para el personal y FileUpload para documentos. Archivos en disco local `storage/app/visitas/`.

**Tech Stack:** Laravel 12, Filament 3.3, SQL Server (sqlsrv), Tailwind CSS 4

**Convenciones existentes:**
- Tablas: `MP_<MODULO>_<ENTIDAD>` (ej: `MP_LGN_AREA`)
- PK: `ID_MP_<TABLA>` (ej: `ID_MP_LGN_AREA`)
- Modelos: `$timestamps = false`, columnas `CREATED_AT`, `UPDATED_AT`
- Resources auto-descubiertos desde `app/Filament/Resources/`

---

### Task 1: Crear modelo Area

**Files:**
- Create: `app/Models/Area.php`

- [ ] **Step 1: Escribir el modelo Area**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $table = 'MP_LGN_AREA';
    protected $primaryKey = 'ID_MP_LGN_AREA';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'ID_MP_LGN_AREA',
        'AREA',
    ];

    protected function casts(): array
    {
        return [
            'ID_MP_LGN_AREA' => 'integer',
        ];
    }
}
```

Save to `app/Models/Area.php`.

---

### Task 2: Crear migración MP_VST_VISITA

**Files:**
- Create: `database/migrations/2026_06_08_000001_create_mp_vst_visita_table.php`

- [ ] **Step 1: Escribir la migración**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('MP_VST_VISITA', function (Blueprint $table) {
            $table->integer('ID_MP_VST_VISITA', true);
            $table->string('EMPRESA', 200);
            $table->string('MOTIVO', 200);
            $table->integer('ID_MP_LGN_AREA');
            $table->dateTime('HORA_INGRESO');
            $table->text('DESCRIPCION')->nullable();
            $table->string('DOCUMENTO_GENERAL', 500)->nullable();
            $table->dateTime('CREATED_AT');
            $table->dateTime('UPDATED_AT');

            $table->foreign('ID_MP_LGN_AREA')
                ->references('ID_MP_LGN_AREA')
                ->on('MP_LGN_AREA');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('MP_VST_VISITA');
    }
};
```

Save to `database/migrations/2026_06_08_000001_create_mp_vst_visita_table.php`.

---

### Task 3: Crear migración MP_VST_PERSONAL

**Files:**
- Create: `database/migrations/2026_06_08_000002_create_mp_vst_personal_table.php`

- [ ] **Step 1: Escribir la migración**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('MP_VST_PERSONAL', function (Blueprint $table) {
            $table->integer('ID_MP_VST_PERSONAL', true);
            $table->integer('ID_MP_VST_VISITA');
            $table->string('NOMBRES', 200);
            $table->string('APELLIDOS', 200);
            $table->string('DNI', 20);
            $table->string('SCTR_PATH', 500)->nullable();
            $table->string('DNI_PATH', 500)->nullable();
            $table->dateTime('CREATED_AT');
            $table->dateTime('UPDATED_AT');

            $table->foreign('ID_MP_VST_VISITA')
                ->references('ID_MP_VST_VISITA')
                ->on('MP_VST_VISITA')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('MP_VST_PERSONAL');
    }
};
```

Save to `database/migrations/2026_06_08_000002_create_mp_vst_personal_table.php`.

- [ ] **Step 2: Ejecutar migraciones**

Run: `php artisan migrate --path=database/migrations/2026_06_08_000001_create_mp_vst_visita_table.php`
Expected: Migration runs successfully.

Run: `php artisan migrate --path=database/migrations/2026_06_08_000002_create_mp_vst_personal_table.php`
Expected: Migration runs successfully.

---

### Task 4: Crear modelo Visita

**Files:**
- Create: `app/Models/Visita.php`

- [ ] **Step 1: Escribir el modelo Visita**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visita extends Model
{
    protected $table = 'MP_VST_VISITA';
    protected $primaryKey = 'ID_MP_VST_VISITA';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    const CREATED_AT = 'CREATED_AT';
    const UPDATED_AT = 'UPDATED_AT';

    protected $fillable = [
        'EMPRESA',
        'MOTIVO',
        'ID_MP_LGN_AREA',
        'HORA_INGRESO',
        'DESCRIPCION',
        'DOCUMENTO_GENERAL',
    ];

    protected function casts(): array
    {
        return [
            'ID_MP_VST_VISITA' => 'integer',
            'ID_MP_LGN_AREA' => 'integer',
            'HORA_INGRESO' => 'datetime',
            'CREATED_AT' => 'datetime',
            'UPDATED_AT' => 'datetime',
        ];
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'ID_MP_LGN_AREA', 'ID_MP_LGN_AREA');
    }

    public function personal()
    {
        return $this->hasMany(Personal::class, 'ID_MP_VST_VISITA', 'ID_MP_VST_VISITA');
    }
}
```

Save to `app/Models/Visita.php`.

---

### Task 5: Crear modelo Personal

**Files:**
- Create: `app/Models/Personal.php`

- [ ] **Step 1: Escribir el modelo Personal**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Personal extends Model
{
    protected $table = 'MP_VST_PERSONAL';
    protected $primaryKey = 'ID_MP_VST_PERSONAL';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    const CREATED_AT = 'CREATED_AT';
    const UPDATED_AT = 'UPDATED_AT';

    protected $fillable = [
        'ID_MP_VST_VISITA',
        'NOMBRES',
        'APELLIDOS',
        'DNI',
        'SCTR_PATH',
        'DNI_PATH',
    ];

    protected function casts(): array
    {
        return [
            'ID_MP_VST_PERSONAL' => 'integer',
            'ID_MP_VST_VISITA' => 'integer',
            'CREATED_AT' => 'datetime',
            'UPDATED_AT' => 'datetime',
        ];
    }

    public function visita()
    {
        return $this->belongsTo(Visita::class, 'ID_MP_VST_VISITA', 'ID_MP_VST_VISITA');
    }
}
```

Save to `app/Models/Personal.php`.

---

### Task 6: Crear VisitaResource (Filament)

**Files:**
- Create: `app/Filament/Resources/VisitaResource.php`
- Create: `app/Filament/Resources/VisitaResource/Pages/ListVisitas.php`
- Create: `app/Filament/Resources/VisitaResource/Pages/CreateVisita.php`
- Create: `app/Filament/Resources/VisitaResource/Pages/EditVisita.php`

- [ ] **Step 1: Crear directorio para Pages**

Run: `New-Item -ItemType Directory -Force -Path "app\Filament\Resources\VisitaResource\Pages"`

- [ ] **Step 2: Escribir VisitaResource.php**

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitaResource\Pages;
use App\Models\Area;
use App\Models\Visita;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VisitaResource extends Resource
{
    protected static ?string $model = Visita::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Registro de Visitas';

    protected static ?string $modelLabel = 'Visita';

    protected static ?string $pluralModelLabel = 'Visitas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos de la Visita')
                    ->schema([
                        Forms\Components\TextInput::make('EMPRESA')
                            ->label('Empresa')
                            ->required()
                            ->maxLength(200),
                        Forms\Components\TextInput::make('MOTIVO')
                            ->label('Motivo de la Visita')
                            ->required()
                            ->maxLength(200),
                        Forms\Components\Select::make('ID_MP_LGN_AREA')
                            ->label('Área que Visita')
                            ->options(Area::pluck('AREA', 'ID_MP_LGN_AREA'))
                            ->searchable()
                            ->required(),
                        Forms\Components\DateTimePicker::make('HORA_INGRESO')
                            ->label('Hora de Ingreso')
                            ->required()
                            ->default(now()),
                        Forms\Components\Textarea::make('DESCRIPCION')
                            ->label('Descripción')
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('DOCUMENTO_GENERAL')
                            ->label('Documento General de la Visita')
                            ->directory('visitas/documentos_generales')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(10240)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Personal Ingresante')
                    ->schema([
                        Forms\Components\Repeater::make('personal')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('NOMBRES')
                                    ->label('Nombres')
                                    ->required()
                                    ->maxLength(200),
                                Forms\Components\TextInput::make('APELLIDOS')
                                    ->label('Apellidos')
                                    ->required()
                                    ->maxLength(200),
                                Forms\Components\TextInput::make('DNI')
                                    ->label('DNI')
                                    ->required()
                                    ->maxLength(20),
                                Forms\Components\FileUpload::make('SCTR_PATH')
                                    ->label('SCTR (PDF/Imagen)')
                                    ->directory('visitas/sctr')
                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                    ->maxSize(10240),
                                Forms\Components\FileUpload::make('DNI_PATH')
                                    ->label('DNI Escaneado (PDF/Imagen)')
                                    ->directory('visitas/dni')
                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                    ->maxSize(10240),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('Agregar Persona'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('EMPRESA')
                    ->label('Empresa')
                    ->searchable(),
                Tables\Columns\TextColumn::make('MOTIVO')
                    ->label('Motivo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('area.AREA')
                    ->label('Área'),
                Tables\Columns\TextColumn::make('HORA_INGRESO')
                    ->label('Hora de Ingreso')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('personal_count')
                    ->label('Personal')
                    ->counts('personal'),
                Tables\Columns\TextColumn::make('CREATED_AT')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('HORA_INGRESO', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVisitas::route('/'),
            'create' => Pages\CreateVisita::route('/create'),
            'edit' => Pages\EditVisita::route('/{record}/edit'),
        ];
    }
}
```

Save to `app/Filament/Resources/VisitaResource.php`.

- [ ] **Step 3: Escribir ListVisitas.php**

```php
<?php

namespace App\Filament\Resources\VisitaResource\Pages;

use App\Filament\Resources\VisitaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVisitas extends ListRecords
{
    protected static string $resource = VisitaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
```

Save to `app/Filament/Resources/VisitaResource/Pages/ListVisitas.php`.

- [ ] **Step 4: Escribir CreateVisita.php**

```php
<?php

namespace App\Filament\Resources\VisitaResource\Pages;

use App\Filament\Resources\VisitaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVisita extends CreateRecord
{
    protected static string $resource = VisitaResource::class;
}
```

Save to `app/Filament/Resources/VisitaResource/Pages/CreateVisita.php`.

- [ ] **Step 5: Escribir EditVisita.php**

```php
<?php

namespace App\Filament\Resources\VisitaResource\Pages;

use App\Filament\Resources\VisitaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVisita extends EditRecord
{
    protected static string $resource = VisitaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
```

Save to `app/Filament/Resources/VisitaResource/Pages/EditVisita.php`.

---

### Task 7: Verificación

- [ ] **Step 1: Limpiar caché de rutas**

Run: `php artisan optimize:clear`
Expected: All caches cleared.

- [ ] **Step 2: Verificar que el Resource aparece en el panel**

Run: `php artisan route:list --name=filament`
Expected: Routes for `visitas` resource appear (index, create, edit).

- [ ] **Step 3: Probar navegación**

Acceder a `http://10.0.101.240/mlp_visitas` > iniciar sesión > verificar que "Registro de Visitas" aparece en la barra lateral. Hacer clic y verificar que carga el listado vacío. Crear una visita de prueba con personal y documentos.
