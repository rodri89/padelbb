<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Jugadore extends Model
{
    protected $fillable = ['nombre','apellido', 'posicion', 'telefono', 'foto','activo'];
    
    /**
     * Accessor para normalizar la ruta de la foto
     * Asegura que siempre sea una ruta relativa correcta
     */
    public function getFotoAttribute($value)
    {
        if (empty($value) || $value === null) {
            return 'images/jugador_img.png';
        }
        
        // Si ya es una URL completa, devolverla tal cual
        if (strpos($value, 'http://') === 0 || strpos($value, 'https://') === 0) {
            return $value;
        }
        
        // Normalizar la ruta: eliminar barras iniciales y asegurar formato correcto
        $ruta = ltrim($value, '/');
        
        // Si contiene rutas absolutas del sistema, extraer solo la parte relativa
        $publicPath = public_path();
        if (strpos($ruta, $publicPath) !== false) {
            $ruta = str_replace($publicPath . '/', '', $ruta);
            $ruta = ltrim($ruta, '/');
        }
        
        // Verificar que el archivo existe, si no, usar imagen por defecto
        $rutaCompleta = public_path($ruta);
        if (!file_exists($rutaCompleta) && $ruta !== 'images/jugador_img.png') {
            \Log::warning('Foto no encontrada para jugador ' . $this->id . ': ' . $ruta . ' (ruta completa: ' . $rutaCompleta . ')');
            return 'images/jugador_img.png';
        }
        
        return $ruta;
    }
}
