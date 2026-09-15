<?php

namespace App\Models;

use App\Traits\BelongsToSede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RopRegistro extends Model
{
    use BelongsToSede;

    public const ORIGEN_CREDITO_DESEMBOLSO = 'CREDITO_DESEMBOLSO';

    public const ESTADO_PENDIENTE = 'PENDIENTE';

    public const ESTADO_CALIFICADO = 'CALIFICADO';

    public const CALIFICACION_INUSUAL = 'INUSUAL';

    public const CALIFICACION_SOSPECHOSA = 'SOSPECHOSA';

    protected $table = 'rop_registros';

    protected $primaryKey = 'RopRegistroID';

    protected $guarded = ['RopRegistroID'];

    protected $casts = [
        'Periodo' => 'date',
        'FechaOperacion' => 'datetime',
        'Monto' => 'decimal:2',
        'UmbralAplicado' => 'decimal:2',
        'CalificadoEn' => 'datetime',
        'SnapshotDatos' => 'array',
        'DatosRegulatorios' => 'array',
    ];

    public static function estados(): array
    {
        return [self::ESTADO_PENDIENTE => 'Pendiente', self::ESTADO_CALIFICADO => 'Calificado'];
    }

    public static function calificaciones(): array
    {
        return [
            self::CALIFICACION_INUSUAL => 'Inusual',
            self::CALIFICACION_SOSPECHOSA => 'Sospechosa',
        ];
    }

    public function puedeGenerarRos(): bool
    {
        return $this->Estado === self::ESTADO_CALIFICADO
            && $this->Calificacion === self::CALIFICACION_SOSPECHOSA
            && blank($this->RosCasoID);
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(RopPeriodo::class, 'RopPeriodoID', 'RopPeriodoID');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'ClienteID', 'ClienteID');
    }

    public function proposicion(): BelongsTo
    {
        return $this->belongsTo(ProposicionCredito::class, 'ProposicionCreditoID', 'ProposicionCreditoID');
    }

    public function credito(): BelongsTo
    {
        return $this->belongsTo(Credito::class, 'CreditoID', 'CreditoID');
    }

    public function ros(): BelongsTo
    {
        return $this->belongsTo(RosCaso::class, 'RosCasoID', 'RosCasoID');
    }

    public function calificadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'CalificadoPorID');
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(RopAuditoria::class, 'RopRegistroID', 'RopRegistroID')->latest('created_at');
    }
}
