# Diseño: Sistema de Aprobaciones de Visitas

**Fecha:** 2026-06-08
**Proyecto:** MLP Visitas (Laravel 12 + Filament 3.3)

---

## Requerimiento

Cada visita registrada debe ser aprobada por dos áreas antes de permitir el ingreso:

- **SEGURIDAD PATRIMONIAL** (ID_MP_LGN_AREA = 3)
- **SYSO** (ID_MP_LGN_AREA = 10)

**Reglas:**
- Ambas áreas deben aprobar (AND)
- Orden de aprobación: cualquiera
- Rechazo de cualquier área: definitivo
- Cada usuario solo puede aprobar/rechazar su propia área
- TI (área 1) puede aprobar/rechazar todo (admin)
- La aprobación guarda: quién, cuándo y comentario opcional

---

## Tablas

### Nueva: MP_VST_APROBACION

| Columna | Tipo | Descripción |
|---|---|---|
| ID_MP_VST_APROBACION | int PK autoincrement | ID único |
| ID_MP_VST_VISITA | int FK | Referencia a la visita |
| ID_MP_LGN_AREA | int FK | Área aprobadora (3 o 10) |
| ID_MP_LGN_USUARIO | int FK nullable | Usuario que aprobó/rechazó |
| ESTADO | varchar(20) | PENDIENTE / APROBADO / RECHAZADO |
| COMENTARIO | text nullable | Comentario del aprobador |
| FECHA_APROBACION | datetime nullable | Fecha/hora de la acción |
| CREATED_AT | datetime | Fecha de creación |
| UPDATED_AT | datetime | Fecha de actualización |

### Modificación: MP_VST_VISITA

Nueva columna:
- `ESTADO` varchar(20) default 'PENDIENTE' — PENDIENTE / APROBADO / RECHAZADO

---

## Modelos

- `App\Models\Aprobacion` → tabla `MP_VST_APROBACION`
- Se agrega `aprobaciones()` hasMany en `Visita`
- Método `estadoGeneral()` en Visita para calcular estado consolidado

---

## Lógica de negocio

1. Al crear visita → estado PENDIENTE + crear 2 registros en MP_VST_APROBACION (PENDIENTE c/u)
2. Al aprobar/rechazar → actualizar registro correspondiente, recalcular estado de la visita
3. Regla AND: si ambas APROBADO → visita APROBADO
4. Regla RECHAZO: si alguna RECHAZADO → visita RECHAZADO (definitivo, no reversible)
5. Permisos: usuario ve botones solo si pertenece al área (o es TI área 1)

---

## UI

### Panel de aprobaciones en la vista de visita

- Sección con 2 tarjetas lado a lado (Seguridad + SYSO)
- Cada tarjeta: ícono de área, estado (badge color), aprobador, fecha, comentario
- Botones Aprobar/Rechazar visibles solo si tiene permiso
- Modal para confirmar acción + comentario opcional

### Listado de visitas

- Nueva columna ESTADO con badge de color (ámbar/verde/rojo)

---

## Archivos a crear/modificar

1. Migración: `MP_VST_APROBACION`
2. Migración: agregar ESTADO a `MP_VST_VISITA`
3. Modelo: `Aprobacion.php`
4. Modelo: modificar `Visita.php` (relaciones, estado)
5. Acción Filament: `AprobarVisita` (Action personalizada)
6. Acción Filament: `RechazarVisita` (Action personalizada)
7. Modificar `ViewVisita` Blade: panel de aprobaciones
8. Modificar `VisitaResource`: columna ESTADO, lógica de creación
9. Modificar `ListVisitas`: columna ESTADO
