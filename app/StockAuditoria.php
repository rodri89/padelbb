<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StockAuditoria extends Model
{
    protected $table = 'stock_auditoria';

    public $timestamps = false;

    protected $fillable = [
        'tabla_afectada', 'id_registro', 'accion', 'usuario',
        'valores_anteriores', 'valores_nuevos', 'ip_address', 'created_at',
    ];

    protected $casts = [
        'valores_anteriores' => 'array',
        'valores_nuevos' => 'array',
        'id_registro' => 'integer',
        'created_at' => 'datetime',
    ];

    public static function registrar(
        string $tabla,
        int $idRegistro,
        string $accion,
        ?string $usuario,
        ?array $anterior,
        ?array $nuevo,
        ?string $ip
    ): void {
        static::query()->create([
            'tabla_afectada' => $tabla,
            'id_registro' => $idRegistro,
            'accion' => $accion,
            'usuario' => $usuario,
            'valores_anteriores' => $anterior,
            'valores_nuevos' => $nuevo,
            'ip_address' => $ip,
            'created_at' => now(),
        ]);
    }
}
