<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
abstract class DetalleDeclaracionCliente extends Model {
 protected $primaryKey = 'ID'; protected $guarded = ['ID'];
 protected static function booted():void{$verificar=function(self $detalle):void{$id=$detalle->DeclaracionClienteID?:$detalle->getOriginal('DeclaracionClienteID');$estado=DeclaracionCliente::withoutGlobalScope('sede')->whereKey($id)->value('Estado');if($estado==='FINALIZADO'){throw ValidationException::withMessages(['declaracion'=>'No se pueden modificar detalles de una declaración finalizada.']);}};static::creating($verificar);static::updating($verificar);static::deleting($verificar);}
 public function declaracion(): BelongsTo { return $this->belongsTo(DeclaracionCliente::class, 'DeclaracionClienteID', 'DeclaracionClienteID'); }
}
