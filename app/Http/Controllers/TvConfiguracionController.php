<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\TvConfiguracion;

class TvConfiguracionController extends Controller
{
    /**
     * Panel de control para configurar qué se muestra en TV
     */
    public function panelControl()
    {
        // Obtener o crear configuración principal
        $config = TvConfiguracion::first();
        if (!$config) {
            $config = TvConfiguracion::create([
                'nombre' => 'TV Principal',
                'activo' => true,
                'slides' => [],
                'intervalo_default' => 15
            ]);
        }
        
        // Obtener torneos activos
        $torneos = DB::table('torneos')
            ->where('activo', 1)
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get();
        
        // Definir tipos de vista disponibles
        $tiposVista = [
            ['id' => 'zonas_americano', 'nombre' => 'Zonas (Americano)', 'icono' => 'fa-th', 'descripcion' => 'Muestra partidos por zona, rota entre zonas'],
            ['id' => 'cruces_americano', 'nombre' => 'Cruces (Americano)', 'icono' => 'fa-sitemap', 'descripcion' => 'Bracket de eliminatorias'],
            ['id' => 'zonas_puntuable', 'nombre' => 'Zonas (Puntuable)', 'icono' => 'fa-table', 'descripcion' => 'Tablas de posiciones por zona, rota entre zonas'],
            ['id' => 'cruces_puntuable', 'nombre' => 'Cruces (Puntuable)', 'icono' => 'fa-project-diagram', 'descripcion' => 'Bracket con todas las categorías'],
        ];
        
        return view('bahia_padel.admin.tv.panel_control', [
            'config' => $config,
            'torneos' => $torneos,
            'tiposVista' => $tiposVista
        ]);
    }
    
    /**
     * Guardar la configuración de slides
     */
    public function guardarConfiguracion(Request $request)
    {
        $config = TvConfiguracion::first();
        if (!$config) {
            $config = new TvConfiguracion();
            $config->nombre = 'TV Principal';
            $config->activo = true;
        }
        
        $slides = $request->input('slides', []);
        $config->slides = $slides;
        $config->intervalo_default = $request->input('intervalo_default', 15);
        $config->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Configuración guardada correctamente',
            'config' => $config
        ]);
    }
    
    /**
     * Obtener la configuración actual (para polling desde TV)
     */
    public function obtenerConfiguracion()
    {
        $config = TvConfiguracion::first();
        
        if (!$config) {
            return response()->json([
                'slides' => [],
                'intervalo_default' => 15
            ]);
        }
        
        // Enriquecer slides con información del torneo
        $slides = $config->slides ?? [];
        $slidesEnriquecidos = [];
        
        foreach ($slides as $slide) {
            $torneo = DB::table('torneos')->where('id', $slide['torneo_id'])->first();
            if ($torneo) {
                $slide['torneo_nombre'] = $torneo->nombre;
                $slide['torneo_categoria'] = $torneo->categoria;
                $slidesEnriquecidos[] = $slide;
            }
        }
        
        return response()->json([
            'slides' => $slidesEnriquecidos,
            'intervalo_default' => $config->intervalo_default,
            'updated_at' => $config->updated_at->timestamp
        ]);
    }
    
    /**
     * Vista TV que muestra los slides configurados
     */
    public function display()
    {
        $config = TvConfiguracion::first();
        
        if (!$config || empty($config->slides)) {
            return view('bahia_padel.tv.sin_torneos', [
                'fecha' => date('Y-m-d'),
                'mensaje' => 'No hay vistas configuradas para mostrar'
            ]);
        }
        
        // Enriquecer slides con info de torneos
        $slides = $config->slides ?? [];
        $slidesEnriquecidos = [];
        
        foreach ($slides as $index => $slide) {
            $torneo = DB::table('torneos')->where('id', $slide['torneo_id'])->first();
            if ($torneo && $torneo->activo) {
                $slide['torneo'] = $torneo;
                $slide['index'] = $index;
                
                // Construir URL según tipo - pasar intervalo para sincronizar rotación interna
                $intervalo = $slide['duracion'] ?? $config->intervalo_default;
                switch ($slide['tipo']) {
                    case 'zonas_americano':
                        $slide['url'] = route('tvtorneoamericano') . '?torneo_id=' . $slide['torneo_id'] . '&intervalo_total=' . $intervalo;
                        break;
                    case 'cruces_americano':
                        $slide['url'] = route('tvtorneoamericanocruces') . '?torneo_id=' . $slide['torneo_id'] . '&intervalo_total=' . $intervalo;
                        break;
                    case 'zonas_puntuable':
                        $slide['url'] = route('tvtorneospuntuableszonas') . '?torneos=' . $slide['torneo_id'] . '&intervalo_total=' . $intervalo;
                        break;
                    case 'cruces_puntuable':
                        $slide['url'] = route('tvtorneosrotacion') . '?torneos=' . $slide['torneo_id'] . '&intervalo=' . $intervalo;
                        break;
                    default:
                        $slide['url'] = route('tvtorneoamericano') . '?torneo_id=' . $slide['torneo_id'] . '&intervalo_total=' . $intervalo;
                }
                
                $slidesEnriquecidos[] = $slide;
            }
        }
        
        if (empty($slidesEnriquecidos)) {
            return view('bahia_padel.tv.sin_torneos', [
                'fecha' => date('Y-m-d'),
                'mensaje' => 'No hay torneos activos en la configuración'
            ]);
        }
        
        return view('bahia_padel.tv.display_controlado', [
            'slides' => $slidesEnriquecidos,
            'intervalo' => $config->intervalo_default,
            'configId' => $config->id,
            'configTimestamp' => $config->updated_at->timestamp
        ]);
    }
}
