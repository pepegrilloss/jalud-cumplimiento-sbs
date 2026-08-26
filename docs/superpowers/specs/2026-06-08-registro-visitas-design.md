# Diseño: Registro de Visitas

**Fecha:** 2026-06-08
**Proyecto:** MLP Visitas (Laravel 12 + Filament 3.3)
**DB:** SQL Server (sqlsrv)

---

## Requerimiento

Formulario interno (Filament admin panel) para registrar visitas con los siguientes datos:

- Empresa de origen (texto libre)
- Motivo de la visita (texto libre)
- Área que visita (selección desde catálogo `MP_LGN_AREA`)
- Personal ingresante (1 o más personas), cada una con:
  - Nombres, Apellidos, DNI
  - Adjunto SCTR (PDF/imagen)
  - Adjunto DNI escaneado (PDF/imagen)
- Hora de ingreso (datetime)
- Descripción (opcional)
- Documento general de la visita (PDF/imagen, opcional)

---

## Tablas

### MP_VST_VISITA

| Columna | Tipo | Nullable | Descripción |
|---|---|---|---|
| `ID_MP_VST_VISITA` | int PK autoincrement | No | ID único |
| `EMPRESA` | varchar(200) | No | Empresa que proviene |
| `MOTIVO` | varchar(200) | No | Motivo de la visita |
| `ID_MP_LGN_AREA` | int FK | No | Referencia a MP_LGN_AREA |
| `HORA_INGRESO` | datetime | No | Fecha y hora de ingreso |
| `DESCRIPCION` | text | Sí | Descripción adicional |
| `DOCUMENTO_GENERAL` | varchar(500) | Sí | Ruta del archivo general |
| `CREATED_AT` | datetime | No | Fecha de creación |
| `UPDATED_AT` | datetime | No | Fecha de actualización |

### MP_VST_PERSONAL

| Columna | Tipo | Nullable | Descripción |
|---|---|---|---|
| `ID_MP_VST_PERSONAL` | int PK autoincrement | No | ID único |
| `ID_MP_VST_VISITA` | int FK | No | Referencia a MP_VST_VISITA (CASCADE) |
| `NOMBRES` | varchar(200) | No | Nombres |
| `APELLIDOS` | varchar(200) | No | Apellidos |
| `DNI` | varchar(20) | No | Número de documento |
| `SCTR_PATH` | varchar(500) | Sí | Ruta del archivo SCTR |
| `DNI_PATH` | varchar(500) | Sí | Ruta del archivo DNI escaneado |
| `CREATED_AT` | datetime | No | Fecha de creación |
| `UPDATED_AT` | datetime | No | Fecha de actualización |

### Relaciones

- `MP_VST_VISITA.ID_MP_LGN_AREA` → `MP_LGN_AREA.ID_MP_LGN_AREA`
- `MP_VST_PERSONAL.ID_MP_VST_VISITA` → `MP_VST_VISITA.ID_MP_VST_VISITA` (ON DELETE CASCADE)

---

## Modelos

- `App\Models\Visita` → tabla `MP_VST_VISITA`
- `App\Models\Personal` → tabla `MP_VST_PERSONAL`

Siguiendo el patrón del modelo `User` existente:
- PK personalizada (ej: `ID_MP_VST_VISITA`)
- `$timestamps = false` con columnas explícitas `CREATED_AT`, `UPDATED_AT`
- Casting de IDs a integer
- Método `getAuthIdentifierName()` override si aplica

### Relaciones Eloquent

- `Visita::hasMany(Personal::class)`
- `Personal::belongsTo(Visita::class)`
- `Visita::belongsTo(Area::class)` (si existe modelo Area)

---

## Filament Resource

### VisitaResource

#### Formulario (Create/Edit)

**Sección "Datos de la Visita"**
- `TextInput::make('EMPRESA')` - requerido
- `TextInput::make('MOTIVO')` - requerido
- `Select::make('ID_MP_LGN_AREA')` - requerido, desde tabla `MP_LGN_AREA`
- `DateTimePicker::make('HORA_INGRESO')` - requerido
- `Textarea::make('DESCRIPCION')` - opcional, columnSpan full
- `FileUpload::make('DOCUMENTO_GENERAL')` - opcional, PDF/imagen

**Sección "Personal Ingresante" (Repeater)**
- `Repeater::make('personal')` con relación `hasMany`
  - `TextInput::make('NOMBRES')` - requerido
  - `TextInput::make('APELLIDOS')` - requerido
  - `TextInput::make('DNI')` - requerido
  - `FileUpload::make('SCTR_PATH')` - opcional, PDF/imagen
  - `FileUpload::make('DNI_PATH')` - opcional, PDF/imagen

#### Tabla (List)

Columnas: EMPRESA, MOTIVO, area.AREA, HORA_INGRESO, personal_count

---

## Almacenamiento de Archivos

Disco `local` en `storage/app/visitas/`. Filament maneja la subida y descarga interna sin exposición pública.

---

## Archivos a Crear

1. `database/migrations/XXXX_XX_XX_create_mp_vst_visita_table.php`
2. `database/migrations/XXXX_XX_XX_create_mp_vst_personal_table.php`
3. `app/Models/Visita.php`
4. `app/Models/Personal.php`
5. `app/Models/Area.php` (si no existe)
6. `app/Filament/Resources/VisitaResource.php`
7. `app/Filament/Resources/VisitaResource/Pages/ListVisitas.php`
8. `app/Filament/Resources/VisitaResource/Pages/CreateVisita.php`
9. `app/Filament/Resources/VisitaResource/Pages/EditVisita.php`
