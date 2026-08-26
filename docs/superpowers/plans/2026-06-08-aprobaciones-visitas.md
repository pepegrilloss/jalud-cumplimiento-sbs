# Sistema de Aprobaciones - Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Agregar flujo de aprobación dual (Seguridad Patrimonial + SYSO) a las visitas con control por área del usuario.

**Architecture:** Nueva tabla `MP_VST_APROBACION` con lógica AND. Columna `ESTADO` en visita. Acciones Filament personalizadas + panel UI en vista. Control de permisos basado en área del usuario logueado via `MP_LGN_USUARIOS_REGISTRADOS`.

**Tech Stack:** Laravel 12, Filament 3.3, SQL Server

**Convenciones:** MP_ prefix, PK = ID_MP_<TABLA>, $timestamps = true con CREATED_AT/UPDATED_AT

---

### Task 1: Migraciones

**Files:**
- Create: `database/migrations/2026_06_08_000003_add_estado_to_mp_vst_visita.php`
- Create: `database/migrations/2026_06_08_000004_create_mp_vst_aprobacion_table.php`

- [ ] **Step 1: Agregar columna ESTADO a MP_VST_VISITA**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('MP_VST_VISITA', function (Blueprint $table) {
            $table->string('ESTADO', 20)->default('PENDIENTE');
        });
    }

    public function down(): void
    {
        Schema::table('MP_VST_VISITA', function (Blueprint $table) {
            $table->dropColumn('ESTADO');
        });
    }
};
```

- [ ] **Step 2: Crear tabla MP_VST_APROBACION**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('MP_VST_APROBACION', function (Blueprint $table) {
            $table->integer('ID_MP_VST_APROBACION', true);
            $table->integer('ID_MP_VST_VISITA');
            $table->integer('ID_MP_LGN_AREA');
            $table->integer('ID_MP_LGN_USUARIO')->nullable();
            $table->string('ESTADO', 20)->default('PENDIENTE');
            $table->text('COMENTARIO')->nullable();
            $table->dateTime('FECHA_APROBACION')->nullable();
            $table->dateTime('CREATED_AT');
            $table->dateTime('UPDATED_AT');

            $table->foreign('ID_MP_VST_VISITA')
                ->references('ID_MP_VST_VISITA')
                ->on('MP_VST_VISITA')
                ->onDelete('cascade');

            $table->foreign('ID_MP_LGN_AREA')
                ->references('ID_MP_LGN_AREA')
                ->on('MP_LGN_AREA');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('MP_VST_APROBACION');
    }
};
```

- [ ] **Step 3: Ejecutar migraciones**

Run: `php artisan migrate --force`
Expected: Both migrations run successfully.

---

### Task 2: Modelo Aprobacion

**Files:**
- Create: `app/Models/Aprobacion.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aprobacion extends Model
{
    protected $table = 'MP_VST_APROBACION';
    protected $primaryKey = 'ID_MP_VST_APROBACION';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    const CREATED_AT = 'CREATED_AT';
    const UPDATED_AT = 'UPDATED_AT';

    protected $fillable = [
        'ID_MP_VST_VISITA',
        'ID_MP_LGN_AREA',
        'ID_MP_LGN_USUARIO',
        'ESTADO',
        'COMENTARIO',
        'FECHA_APROBACION',
    ];

    protected function casts(): array
    {
        return [
            'ID_MP_VST_APROBACION' => 'integer',
            'ID_MP_VST_VISITA' => 'integer',
            'ID_MP_LGN_AREA' => 'integer',
            'ID_MP_LGN_USUARIO' => 'integer',
            'FECHA_APROBACION' => 'datetime',
            'CREATED_AT' => 'datetime',
            'UPDATED_AT' => 'datetime',
        ];
    }

    public function visita()
    {
        return $this->belongsTo(Visita::class, 'ID_MP_VST_VISITA', 'ID_MP_VST_VISITA');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'ID_MP_LGN_AREA', 'ID_MP_LGN_AREA');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'ID_MP_LGN_USUARIO', 'ID_MP_LGN_USUARIO');
    }

    // Constantes de areas aprobadoras
    const AREA_SEGURIDAD = 3;
    const AREA_SYSO = 10;
    const AREA_TI = 1;
}
```

---

### Task 3: Actualizar modelo Visita

**Files:**
- Modify: `app/Models/Visita.php`

Agregar `ESTADO` a fillable y casts, relación `aprobaciones()`, y helper `estadoGeneral()`.

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
    public $timestamps = true;

    const CREATED_AT = 'CREATED_AT';
    const UPDATED_AT = 'UPDATED_AT';

    protected $fillable = [
        'EMPRESA',
        'MOTIVO',
        'ID_MP_LGN_AREA',
        'HORA_INGRESO',
        'DESCRIPCION',
        'DOCUMENTO_GENERAL',
        'ESTADO',
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

    public function aprobaciones()
    {
        return $this->hasMany(Aprobacion::class, 'ID_MP_VST_VISITA', 'ID_MP_VST_VISITA');
    }

    public function aprobacionSeguridad()
    {
        return $this->hasOne(Aprobacion::class, 'ID_MP_VST_VISITA', 'ID_MP_VST_VISITA')
            ->where('ID_MP_LGN_AREA', Aprobacion::AREA_SEGURIDAD);
    }

    public function aprobacionSyso()
    {
        return $this->hasOne(Aprobacion::class, 'ID_MP_VST_VISITA', 'ID_MP_VST_VISITA')
            ->where('ID_MP_LGN_AREA', Aprobacion::AREA_SYSO);
    }

    public function calcularEstado(): void
    {
        $aprobaciones = $this->aprobaciones()->get();
        if ($aprobaciones->contains('ESTADO', 'RECHAZADO')) {
            $this->ESTADO = 'RECHAZADO';
        } elseif ($aprobaciones->every(fn($a) => $a->ESTADO === 'APROBADO')) {
            $this->ESTADO = 'APROBADO';
        } else {
            $this->ESTADO = 'PENDIENTE';
        }
        $this->save();
    }
}
```

Save the updated file to `app/Models/Visita.php`.

---

### Task 4: Helper para obtener área del usuario logueado

**Files:**
- Create: `app/Helpers/UsuarioHelper.php`

```php
<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class UsuarioHelper
{
    public static function areaId(): ?int
    {
        $userId = auth()->id();
        if (!$userId) return null;

        $registro = DB::table('MP_LGN_USUARIOS_REGISTRADOS')
            ->where('ID_MP_LGN_USUARIO', $userId)
            ->first();

        return $registro?->ID_MP_LGN_AREA;
    }

    public static function esTI(): bool
    {
        return self::areaId() === 1; // TECNOLOGIA DE INFORMACION
    }

    public static function puedeAprobarArea(int $areaId): bool
    {
        if (self::esTI()) return true;
        return self::areaId() === $areaId;
    }
}
```

---

### Task 5: Auto-crear aprobaciones al registrar visita

**Files:**
- Modify: `app/Filament/Resources/VisitaResource/Pages/CreateVisita.php`

Modificar `CreateVisita` para crear las aprobaciones pendientes después de guardar:

```php
<?php

namespace App\Filament\Resources\VisitaResource\Pages;

use App\Filament\Resources\VisitaResource;
use App\Models\Aprobacion;
use Filament\Resources\Pages\CreateRecord;

class CreateVisita extends CreateRecord
{
    protected static string $resource = VisitaResource::class;

    protected function afterCreate(): void
    {
        $visita = $this->getRecord();

        Aprobacion::create([
            'ID_MP_VST_VISITA' => $visita->ID_MP_VST_VISITA,
            'ID_MP_LGN_AREA' => Aprobacion::AREA_SEGURIDAD,
            'ESTADO' => 'PENDIENTE',
        ]);

        Aprobacion::create([
            'ID_MP_VST_VISITA' => $visita->ID_MP_VST_VISITA,
            'ID_MP_LGN_AREA' => Aprobacion::AREA_SYSO,
            'ESTADO' => 'PENDIENTE',
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
```

---

### Task 6: Agregar columna ESTADO al listado

**Files:**
- Modify: `app/Filament/Resources/VisitaResource.php`

Agregar columna ESTADO con badge de color al `table()`:

```php
Tables\Columns\TextColumn::make('ESTADO')
    ->label('Estado')
    ->badge()
    ->color(fn (string $state): string => match ($state) {
        'APROBADO' => 'success',
        'RECHAZADO' => 'danger',
        default => 'warning',
    })
    ->sortable(),
```

Insertarla después de `personal_count` en el método `table()`.

---

### Task 7: Panel de aprobaciones en la vista

**Files:**
- Modify: `resources/views/filament/resources/visita/view.blade.php`

Agregar sección del panel de aprobaciones antes del cierre de `</x-filament-panels::page>`, usando CSS puro con soporte dark mode y lógica de permisos.

El código completo del panel de aprobaciones se agrega en el template. Ver archivo completo en implementación.

---

### Task 8: Acciones Filament Aprobar/Rechazar

**Files:**
- Modify: `app/Filament/Resources/VisitaResource/Pages/ViewVisita.php`

Agregar acciones `AprobarAction` y `RechazarAction` en el header del ViewRecord. Cada acción recibe el ID del área como parámetro y solo se muestra si el usuario tiene permiso.

```php
<?php

namespace App\Filament\Resources\VisitaResource\Pages;

use App\Filament\Resources\VisitaResource;
use App\Helpers\UsuarioHelper;
use App\Models\Aprobacion;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewVisita extends ViewRecord
{
    protected static string $resource = VisitaResource::class;
    protected static string $view = 'filament.resources.visita.view';

    protected function getHeaderActions(): array
    {
        $acciones = [];
        $visita = $this->getRecord();

        // Cargar aprobaciones
        $aprobSeg = $visita->aprobaciones()->where('ID_MP_LGN_AREA', Aprobacion::AREA_SEGURIDAD)->first();
        $aprobSyso = $visita->aprobaciones()->where('ID_MP_LGN_AREA', Aprobacion::AREA_SYSO)->first();

        // Boton Aprobar Seguridad
        if ($aprobSeg && $aprobSeg->ESTADO === 'PENDIENTE' && UsuarioHelper::puedeAprobarArea(Aprobacion::AREA_SEGURIDAD)) {
            $acciones[] = Actions\Action::make('aprobarSeguridad')
                ->label('Aprobar Seguridad')
                ->icon('heroicon-o-shield-check')
                ->color('success')
                ->form([Textarea::make('COMENTARIO')->label('Comentario')])
                ->action(function (array $data) use ($aprobSeg, $visita) {
                    $aprobSeg->update([
                        'ESTADO' => 'APROBADO',
                        'ID_MP_LGN_USUARIO' => auth()->id(),
                        'COMENTARIO' => $data['COMENTARIO'] ?? null,
                        'FECHA_APROBACION' => now(),
                    ]);
                    $visita->calcularEstado();
                    Notification::make()->title('Seguridad Patrimonial aprobada')->success()->send();
                });
        }

        // Boton Rechazar Seguridad
        if ($aprobSeg && $aprobSeg->ESTADO === 'PENDIENTE' && UsuarioHelper::puedeAprobarArea(Aprobacion::AREA_SEGURIDAD)) {
            $acciones[] = Actions\Action::make('rechazarSeguridad')
                ->label('Rechazar Seguridad')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->form([Textarea::make('COMENTARIO')->label('Motivo del rechazo')->required()])
                ->action(function (array $data) use ($aprobSeg, $visita) {
                    $aprobSeg->update([
                        'ESTADO' => 'RECHAZADO',
                        'ID_MP_LGN_USUARIO' => auth()->id(),
                        'COMENTARIO' => $data['COMENTARIO'],
                        'FECHA_APROBACION' => now(),
                    ]);
                    $visita->calcularEstado();
                    Notification::make()->title('Visita rechazada por Seguridad Patrimonial')->danger()->send();
                });
        }

        // Boton Aprobar SYSO
        if ($aprobSyso && $aprobSyso->ESTADO === 'PENDIENTE' && UsuarioHelper::puedeAprobarArea(Aprobacion::AREA_SYSO)) {
            $acciones[] = Actions\Action::make('aprobarSyso')
                ->label('Aprobar SYSO')
                ->icon('heroicon-o-shield-check')
                ->color('success')
                ->form([Textarea::make('COMENTARIO')->label('Comentario')])
                ->action(function (array $data) use ($aprobSyso, $visita) {
                    $aprobSyso->update([
                        'ESTADO' => 'APROBADO',
                        'ID_MP_LGN_USUARIO' => auth()->id(),
                        'COMENTARIO' => $data['COMENTARIO'] ?? null,
                        'FECHA_APROBACION' => now(),
                    ]);
                    $visita->calcularEstado();
                    Notification::make()->title('SYSO aprobada')->success()->send();
                });
        }

        // Boton Rechazar SYSO
        if ($aprobSyso && $aprobSyso->ESTADO === 'PENDIENTE' && UsuarioHelper::puedeAprobarArea(Aprobacion::AREA_SYSO)) {
            $acciones[] = Actions\Action::make('rechazarSyso')
                ->label('Rechazar SYSO')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->form([Textarea::make('COMENTARIO')->label('Motivo del rechazo')->required()])
                ->action(function (array $data) use ($aprobSyso, $visita) {
                    $aprobSyso->update([
                        'ESTADO' => 'RECHAZADO',
                        'ID_MP_LGN_USUARIO' => auth()->id(),
                        'COMENTARIO' => $data['COMENTARIO'],
                        'FECHA_APROBACION' => now(),
                    ]);
                    $visita->calcularEstado();
                    Notification::make()->title('Visita rechazada por SYSO')->danger()->send();
                });
        }

        return $acciones;
    }
}
```

---

### Task 9: Agregar panel de aprobaciones al Blade

**Files:**
- Modify: `resources/views/filament/resources/visita/view.blade.php`

Agregar al final del archivo, antes de `</x-filament-panels::page>`:

```blade
@php
    use App\Helpers\UsuarioHelper;
    use App\Models\Aprobacion;
    $aprobSeg = $record->aprobaciones()->where('ID_MP_LGN_AREA', Aprobacion::AREA_SEGURIDAD)->first();
    $aprobSyso = $record->aprobaciones()->where('ID_MP_LGN_AREA', Aprobacion::AREA_SYSO)->first();
@endphp

<div class="vp-card" style="margin-top:24px;">
    <div class="vp-section-header">
        <div class="vp-section-icon">
            <x-heroicon-o-shield-check />
        </div>
        <div>
            <h3 class="vp-section-label">Panel de Aprobaciones</h3>
            <p class="vp-section-count">Seguridad Patrimonial &amp; SYSO</p>
        </div>
    </div>
    <div class="vp-pad">
        <div style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));">
            {{-- SEGURIDAD PATRIMONIAL --}}
            <div style="border-radius:10px;border:1px solid #e5e7eb;overflow:hidden;">
                <div style="padding:12px 16px;background:#f9fafb;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;gap:10px;">
                    <span style="font-weight:600;font-size:14px;color:#111827;">&#x1f6e1;&#xfe0f; Seguridad Patrimonial</span>
                </div>
                <div style="padding:16px;">
                    @if ($aprobSeg && $aprobSeg->ESTADO === 'APROBADO')
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                            <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:500;background:#dcfce7;color:#16a34a;">&#x2705; Aprobado</span>
                        </div>
                        @if ($aprobSeg->usuario)
                            <p style="font-size:13px;color:#6b7280;">Por: <strong>{{ $aprobSeg->usuario->name }}</strong></p>
                        @endif
                        @if ($aprobSeg->FECHA_APROBACION)
                            <p style="font-size:12px;color:#9ca3af;">{{ \Carbon\Carbon::parse($aprobSeg->FECHA_APROBACION)->format('d/m/Y H:i') }}</p>
                        @endif
                        @if ($aprobSeg->COMENTARIO)
                            <p style="margin-top:8px;font-size:13px;color:#374151;font-style:italic;">"{{ $aprobSeg->COMENTARIO }}"</p>
                        @endif
                    @elseif ($aprobSeg && $aprobSeg->ESTADO === 'RECHAZADO')
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                            <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:500;background:#fee2e2;color:#dc2626;">&#x274c; Rechazado</span>
                        </div>
                        @if ($aprobSeg->usuario)
                            <p style="font-size:13px;color:#6b7280;">Por: <strong>{{ $aprobSeg->usuario->name }}</strong></p>
                        @endif
                        @if ($aprobSeg->COMENTARIO)
                            <p style="margin-top:8px;font-size:13px;color:#dc2626;font-style:italic;">"{{ $aprobSeg->COMENTARIO }}"</p>
                        @endif
                    @else
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:500;background:#fef3c7;color:#b45309;">&#x23f3; Pendiente</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- SYSO --}}
            <div style="border-radius:10px;border:1px solid #e5e7eb;overflow:hidden;">
                <div style="padding:12px 16px;background:#f9fafb;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;gap:10px;">
                    <span style="font-weight:600;font-size:14px;color:#111827;">&#x1f3d7;&#xfe0f; SYSO</span>
                </div>
                <div style="padding:16px;">
                    @if ($aprobSyso && $aprobSyso->ESTADO === 'APROBADO')
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                            <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:500;background:#dcfce7;color:#16a34a;">&#x2705; Aprobado</span>
                        </div>
                        @if ($aprobSyso->usuario)
                            <p style="font-size:13px;color:#6b7280;">Por: <strong>{{ $aprobSyso->usuario->name }}</strong></p>
                        @endif
                        @if ($aprobSyso->FECHA_APROBACION)
                            <p style="font-size:12px;color:#9ca3af;">{{ \Carbon\Carbon::parse($aprobSyso->FECHA_APROBACION)->format('d/m/Y H:i') }}</p>
                        @endif
                        @if ($aprobSyso->COMENTARIO)
                            <p style="margin-top:8px;font-size:13px;color:#374151;font-style:italic;">"{{ $aprobSyso->COMENTARIO }}"</p>
                        @endif
                    @elseif ($aprobSyso && $aprobSyso->ESTADO === 'RECHAZADO')
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                            <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:500;background:#fee2e2;color:#dc2626;">&#x274c; Rechazado</span>
                        </div>
                        @if ($aprobSyso->usuario)
                            <p style="font-size:13px;color:#6b7280;">Por: <strong>{{ $aprobSyso->usuario->name }}</strong></p>
                        @endif
                        @if ($aprobSyso->COMENTARIO)
                            <p style="margin-top:8px;font-size:13px;color:#dc2626;font-style:italic;">"{{ $aprobSyso->COMENTARIO }}"</p>
                        @endif
                    @else
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:500;background:#fef3c7;color:#b45309;">&#x23f3; Pendiente</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
```

---

### Task 10: Verificación

- [ ] Run `php artisan migrate --force` — ambas migraciones OK
- [ ] Run `php artisan optimize:clear` — limpiar caché
- [ ] Login como usuario de Seguridad → ver botones Aprobar/Rechazar en header de vista
- [ ] Login como SYSO → ver solo sus botones
- [ ] Login como TI → ver todos los botones
- [ ] Aprobar ambas → ESTADO cambia a APROBADO
- [ ] Rechazar una → ESTADO cambia a RECHAZADO
- [ ] Verificar columna ESTADO en listado con badge de color
