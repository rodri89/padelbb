<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TvConfiguracion extends Model
{
    protected $table = 'tv_configuracion';
    
    protected $fillable = ['nombre', 'activo', 'slides', 'intervalo_default'];
    
    protected $casts = [
        'slides' => 'array',
        'activo' => 'boolean'
    ];
    
    /**
     * Obtiene la configuración principal (o la primera activa)
     */
    public static function getConfiguracionActiva()
    {
        return self::where('activo', true)->first();
    }
    
    /**
     * Actualiza los slides de la configuración
     */
    public function actualizarSlides(array $slides)
    {
        $this->slides = $slides;
        $this->save();
        return $this;
    }
}
