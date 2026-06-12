<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Torneo;
use App\Jugadore;
use App\Partido;
use App\Grupo;
use Intervention\Image\Facades\Image;

use Session;

class HomeController extends Controller
{

    use AuthenticatesUsers;

    protected $redirectTo = '/homes';
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['buscarJugadoresPublico', 'subirFotoJugadorPublico', 'tvTorneoAmericano']]);
        //$this->middleware('guest', ['except' => 'logout']);
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('index');
    }

    function adminHome2(){
        return View('padel.admin.home'); 
    }

    function adminHome(){
        return View('bahia_padel.admin.index'); 
    }

    function adminHomeBp() {
        return View('bahia_padel.admin.index'); 
    }
    function adminJugadores() {
        return View('bahia_padel.admin.jugadores.index'); 
    }
    function adminVivo() {
        return View('bahia_padel.admin.vivo.index'); 
    }
    function adminTorneos() {
        return View('bahia_padel.admin.torneo.index'); 
    }
    function adminFotos() {
        return View('bahia_padel.admin.fotos.index'); 
    }

    function registrarTorneo(Request $request) {
        try {
            $id = $request->id_torneo;
            if($id == 0){
                $torneo = new Torneo;            
                $torneo->activo = 1;
            } else {
                $torneo = Torneo::find($id);
                if (!$torneo) {
                    return response()->json([
                        'torneo' => null,
                        'error' => 'Torneo no encontrado'
                    ], 404);
                }
            }
            
            if($request->nombre != null)        
                $torneo->nombre = $request->nombre;
            else
                $torneo->nombre = '';                
            
            if($request->tipo != null)        
                $torneo->tipo = $request->tipo;
            else
                $torneo->tipo = '';                
            
            if($request->fechaInicio != null)
                $torneo->fecha_inicio = $request->fechaInicio;
            else
                $torneo->fecha_inicio = '2000-01-01';
            
            if($request->fechaFin != null)
                $torneo->fecha_fin = $request->fechaFin;
            else
                $torneo->fecha_fin = '2000-01-01';
                    
            if($request->premio1 != null)
                $torneo->premio_1 = $request->premio1;
            else
                $torneo->premio_1 = '';
            
            if($request->premio2 != null)
                $torneo->premio_2 = $request->premio2;
            else
                $torneo->premio_2 = '';
            
            if($request->descripcion != null)
                $torneo->descripcion = $request->descripcion;
            else
                $torneo->descripcion = '';
            
            $torneo->es_torneo_individual = $request->tipo_torneo ?? 2;        
            $torneo->categoria = $request->categoria ?? 1;
            $torneo->imagen = '';
            
            // Guardar tipo de torneo (americano, puntuable, suma)
            if($request->tipo_torneo_formato != null) {
                $torneo->tipo_torneo_formato = $request->tipo_torneo_formato;
            } else {
                $torneo->tipo_torneo_formato = 'puntuable'; // Por defecto
            }

            $torneo->save();

            return response()->json(array('torneo'=>$torneo));
        } catch (\Exception $e) {
            \Log::error('Error al registrar torneo: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'torneo' => null,
                'error' => 'Error al guardar el torneo: ' . $e->getMessage()
            ], 500);
        }
    }

    function getTorneos(Request $request) {
        $torneos = DB::table('torneos')                                                                
                        ->where('torneos.activo', 1)        
                        ->orderby('torneos.fecha_inicio')
                        ->get(); 
        
        return response()->json(array('torneos'=>$torneos));
    }

    public function adminTorneoSelected(Request $request) {
            $torneo = DB::table('torneos')                                                                
                            ->where('torneos.id', $request->torneo_id)                                
                            ->where('torneos.activo', 1)                                
                            ->first(); 
            
            if (!$torneo) {
                return redirect()->route('admintorneos')->with('error', 'Torneo no encontrado');
            }
            
            $jugadores = DB::table('jugadores')                                                                                        
                            ->where('jugadores.activo', 1)                                
                            ->get();
            // Determinar el tipo de torneo (por defecto puntuable si no existe)
            $tipoTorneo = isset($torneo->tipo_torneo_formato) ? $torneo->tipo_torneo_formato : 'puntuable';
            
            // Obtener grupos excluyendo los de eliminatoria (zonas: 'cuartos final', 'semifinal', 'final')
            // Los grupos de eliminatoria son solo para los cruces y no deben mostrarse en la configuración inicial
            // Para torneos americanos, priorizar grupos iniciales (sin partido_id) si existen
            if ($tipoTorneo == 'americano') {
                // Primero intentar obtener grupos iniciales (sin partido_id)
                $gruposIniciales = DB::table('grupos')
                                ->where('grupos.torneo_id', $request->torneo_id)
                                ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final'])
                                ->whereNull('grupos.partido_id')
                                ->whereNotNull('grupos.jugador_1')
                                ->whereNotNull('grupos.jugador_2')
                                ->select('grupos.id', 'grupos.torneo_id', 'grupos.zona', 'grupos.fecha', 'grupos.horario', 'grupos.jugador_1', 'grupos.jugador_2', 'grupos.partido_id')
                                ->orderBy('grupos.zona')
                                ->orderBy('grupos.jugador_1')
                                ->orderBy('grupos.jugador_2')
                                ->orderBy('grupos.id')
                                ->get();
                
                // Si hay grupos iniciales, usarlos; si no, usar todos los grupos
                if ($gruposIniciales->count() > 0) {
                    $grupos = $gruposIniciales;
                } else {
                    // Si no hay grupos iniciales, obtener todos los grupos (con partido_id)
                    $grupos = DB::table('grupos')
                                ->where('grupos.torneo_id', $request->torneo_id)
                                ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final'])
                                ->whereNotNull('grupos.jugador_1')
                                ->whereNotNull('grupos.jugador_2')
                                ->select('grupos.id', 'grupos.torneo_id', 'grupos.zona', 'grupos.fecha', 'grupos.horario', 'grupos.jugador_1', 'grupos.jugador_2', 'grupos.partido_id')
                                ->orderBy('grupos.zona')
                                ->orderBy('grupos.jugador_1')
                                ->orderBy('grupos.jugador_2')
                                ->orderBy('grupos.id')
                                ->get();
                    
                    // Filtrar para obtener solo parejas únicas por zona
                    $parejasUnicas = [];
                    $grupos = collect($grupos)->filter(function($grupo) use (&$parejasUnicas) {
                        $key = $grupo->zona . '_' . min($grupo->jugador_1, $grupo->jugador_2) . '_' . max($grupo->jugador_1, $grupo->jugador_2);
                        if (!isset($parejasUnicas[$key])) {
                            $parejasUnicas[$key] = true;
                            return true;
                        }
                        return false;
                    })->values();
                }
            } else {
                // Para otros tipos de torneo, usar la lógica original
                $grupos = DB::table('grupos')
                            ->where('grupos.torneo_id', $request->torneo_id)
                            ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final'])
                            ->whereNotNull('grupos.jugador_1')
                            ->whereNotNull('grupos.jugador_2')
                            ->select('grupos.id', 'grupos.torneo_id', 'grupos.zona', 'grupos.fecha', 'grupos.horario', 'grupos.jugador_1', 'grupos.jugador_2', 'grupos.partido_id')
                            ->orderBy('grupos.zona')
                            ->orderBy('grupos.jugador_1')
                            ->orderBy('grupos.jugador_2')
                            ->orderBy('grupos.id')
                            ->get();
                
                // Filtrar para obtener solo parejas únicas por zona
                $parejasUnicas = [];
                $gruposFiltrados = collect($grupos)->filter(function($grupo) use (&$parejasUnicas) {
                    $key = $grupo->zona . '_' . min($grupo->jugador_1, $grupo->jugador_2) . '_' . max($grupo->jugador_1, $grupo->jugador_2);
                    if (!isset($parejasUnicas[$key])) {
                        $parejasUnicas[$key] = true;
                        return true;
                    }
                    return false;
                })->values();
                
                $grupos = $gruposFiltrados;
            }
            
            // Navegar a la vista correspondiente según el tipo de torneo
            if ($tipoTorneo == 'americano') {
                return View('bahia_padel.admin.torneo.armar_americano')
                            ->with('jugadores', $jugadores)
                            ->with('torneo', $torneo)
                            ->with('grupos', $grupos);
            } elseif ($tipoTorneo == 'suma') {
                return View('bahia_padel.admin.torneo.armar_suma')
                            ->with('jugadores', $jugadores)
                            ->with('torneo', $torneo)
                            ->with('grupos', $grupos);
            } else {
                // Puntuable (por defecto)
                return View('bahia_padel.admin.torneo.armar_torneo')
                            ->with('jugadores', $jugadores)
                            ->with('torneo', $torneo)
                            ->with('grupos', $grupos);
            }
    }

    function adminCrearJugador(Request $request) {
        try {
            $jugador = new Jugadore;
            $jugador->activo = 1;                
            $jugador->nombre = $request->nombre;
            $jugador->apellido = $request->apellido;
            $jugador->telefono = $request->telefono ?? 0;
            $jugador->posicion = 0;
            $jugador->foto = 'images/jugador_img.png';
            
            // Manejar subida de foto
            if ($request->hasFile('foto')) {
                try {
                    $image = $request->file('foto');
                    $name = time() . '_' . $image->getClientOriginalName();
                    $path = 'images/jugadores/' . $name;
                    
                    // Crear directorio si no existe
                    $directory = public_path('images/jugadores');
                    if (!file_exists($directory)) {
                        mkdir($directory, 0755, true);
                    }
                    
                    // Usar Image para procesar y guardar la imagen
                    Image::make($image->getRealPath())->save(public_path($path));
                    $jugador->foto = $path;
                } catch (\Exception $e) {
                    // Si falla la imagen, usar la imagen por defecto
                    \Log::error('Error al procesar imagen: ' . $e->getMessage());
                    $jugador->foto = 'images/jugador_img.png';
                }
            }
            
            $jugador->save();

            return response()->json([
                'success' => true,
                'jugador' => $jugador
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al crear jugador: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el jugador: ' . $e->getMessage()
            ], 500);
        }
    }

    function getJugadores(Request $request) {
        $jugadores = DB::table('jugadores')
                        ->where('jugadores.activo', 1)
                        ->orderBy('jugadores.nombre')
                        ->orderBy('jugadores.apellido')
                        ->get();
        
        return response()->json(['jugadores' => $jugadores]);
    }

    // Métodos públicos para subir fotos (sin autenticación)
    function buscarJugadoresPublico(Request $request) {
        $busqueda = $request->input('busqueda', '');
        
        $query = DB::table('jugadores')
                   ->where('jugadores.activo', 1);
        
        // Si hay búsqueda con al menos 2 caracteres, filtrar
        if (!empty($busqueda) && strlen(trim($busqueda)) >= 2) {
            $busqueda = trim($busqueda);
            $query->where(function($q) use ($busqueda) {
                $q->where('jugadores.nombre', 'LIKE', '%' . $busqueda . '%')
                  ->orWhere('jugadores.apellido', 'LIKE', '%' . $busqueda . '%')
                  ->orWhere(DB::raw("CONCAT(jugadores.nombre, ' ', jugadores.apellido)"), 'LIKE', '%' . $busqueda . '%');
            });
        }
        
        $jugadores = $query->orderBy('jugadores.nombre')
                          ->orderBy('jugadores.apellido')
                          ->limit(100) // Limitar resultados para mejor rendimiento
                          ->get();
        
        return response()->json(['jugadores' => $jugadores]);
    }

    function subirFotoJugadorPublico(Request $request) {
        try {
            $id = $request->id;
            
            if (!$id) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de jugador requerido'
                ], 400);
            }
            
            $jugador = Jugadore::find($id);
            if (!$jugador) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jugador no encontrado'
                ], 404);
            }
            
            // Manejar subida de foto
            if ($request->hasFile('foto')) {
                try {
                    $image = $request->file('foto');
                    
                    // Validar que sea una imagen
                    if (!$image->isValid()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'El archivo enviado no es válido'
                        ], 400);
                    }
                    
                    // Sanitizar nombre del archivo
                    $originalName = $image->getClientOriginalName();
                    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                    $name = time() . '_' . $safeName;
                    $path = 'images/jugadores/' . $name;
                    $imgPath = public_path($path);
                    
                    // Crear directorio si no existe
                    $directory = public_path('images/jugadores');
                    if (!file_exists($directory)) {
                        if (!mkdir($directory, 0755, true)) {
                            throw new \Exception('No se pudo crear el directorio de imágenes');
                        }
                    }
                    
                    // Verificar permisos de escritura
                    if (!is_writable($directory)) {
                        throw new \Exception('El directorio no tiene permisos de escritura');
                    }
                    
                    // Cargar imagen con Intervention Image
                    $img = Image::make($image->getRealPath());
                    
                    // Tamaño máximo en bytes (5MB)
                    $maxSize = 5 * 1024 * 1024; // 5MB
                    
                    // Redimensionar si es muy grande (mantener aspecto, máximo 1920px)
                    $maxDimension = 1920;
                    if ($img->width() > $maxDimension || $img->height() > $maxDimension) {
                        $img->resize($maxDimension, $maxDimension, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                    }
                    
                    // Comprimir y guardar con calidad ajustable
                    $quality = 85;
                    $maxAttempts = 10;
                    $attempt = 0;
                    
                    do {
                        $img->save($imgPath, $quality);
                        
                        if (!file_exists($imgPath)) {
                            throw new \Exception('No se pudo guardar el archivo');
                        }
                        
                        $fileSize = filesize($imgPath);
                        
                        // Si el archivo es menor a 5MB, salir del bucle
                        if ($fileSize <= $maxSize) {
                            break;
                        }
                        
                        // Reducir calidad
                        $quality -= 10;
                        $attempt++;
                        
                        // Si la calidad es muy baja y aún es grande, reducir tamaño
                        if ($quality < 60 && $fileSize > $maxSize && $attempt < $maxAttempts) {
                            $currentWidth = $img->width();
                            $currentHeight = $img->height();
                            $newWidth = intval($currentWidth * 0.85);
                            $newHeight = intval($currentHeight * 0.85);
                            $img->resize($newWidth, $newHeight, function ($constraint) {
                                $constraint->aspectRatio();
                            });
                            $quality = 70; // Resetear calidad después de redimensionar
                        }
                        
                    } while ($fileSize > $maxSize && $quality >= 40 && $attempt < $maxAttempts);
                    
                    // Verificar que el archivo se guardó correctamente
                    if (!file_exists($imgPath)) {
                        throw new \Exception('El archivo no se guardó correctamente');
                    }
                    
                    $jugador->foto = $path;
                } catch (\Exception $e) {
                    \Log::error('Error al procesar imagen: ' . $e->getMessage());
                    \Log::error('Stack trace: ' . $e->getTraceAsString());
                    return response()->json([
                        'success' => false,
                        'message' => 'Error al procesar la imagen: ' . $e->getMessage(),
                        'trace' => config('app.debug') ? $e->getTraceAsString() : null
                    ], 500);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No se envió ninguna imagen'
                ], 400);
            }
            
            $jugador->save();
            
            // Verificar que el archivo existe antes de obtener su tamaño
            $filePath = public_path($jugador->foto);
            $fileSizeMB = 0;
            if (file_exists($filePath)) {
                $fileSize = filesize($filePath);
                $fileSizeMB = round($fileSize / (1024 * 1024), 2);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Foto actualizada correctamente' . ($fileSizeMB > 0 ? ' (tamaño final: ' . $fileSizeMB . ' MB)' : ''),
                'jugador' => $jugador,
                'foto_url' => asset($jugador->foto),
                'file_size_mb' => $fileSizeMB
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al subir foto: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error al subir la foto: ' . $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    function adminEliminarJugador(Request $request) {
        $id = $request->id;
        
        $jugador = Jugadore::find($id);
        if (!$jugador) {
            return response()->json([
                'success' => false,
                'message' => 'Jugador no encontrado'
            ]);
        }
        
        // Marcar como inactivo en lugar de eliminar
        $jugador->activo = 0;
        $jugador->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Jugador eliminado correctamente'
        ]);
    }

    function adminEditarJugador(Request $request) {
        try {
            $id = $request->id;
            
            $jugador = Jugadore::find($id);
            if (!$jugador) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jugador no encontrado'
                ], 404);
            }
            
            $jugador->nombre = $request->nombre;
            $jugador->apellido = $request->apellido;
            $jugador->telefono = $request->telefono ?? 0;
            
            // Manejar subida de foto solo si se envía una nueva
            if ($request->hasFile('foto')) {
                try {
                    $image = $request->file('foto');
                    $name = time() . '_' . $image->getClientOriginalName();
                    $path = 'images/jugadores/' . $name;
                    
                    // Crear directorio si no existe
                    $directory = public_path('images/jugadores');
                    if (!file_exists($directory)) {
                        mkdir($directory, 0755, true);
                    }
                    
                    // Usar Image para procesar y guardar la imagen
                    Image::make($image->getRealPath())->save(public_path($path));
                    $jugador->foto = $path;
                } catch (\Exception $e) {
                    // Si falla la imagen, mantener la actual
                    \Log::error('Error al procesar imagen: ' . $e->getMessage());
                }
            }
            
            $jugador->save();
            
            return response()->json([
                'success' => true,
                'jugador' => $jugador
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al editar jugador: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al editar el jugador: ' . $e->getMessage()
            ], 500);
        }
    }

    public function guardarFechaAdminTorneo(Request $request) {
        $torneoId = $request->torneo_id;
        $zona = $request->zona;
        $tieneCuatroParejas = $request->input('tiene_cuatro_parejas', 0) == 1;

        $grupos = \App\Grupo::where('torneo_id', $torneoId)
            ->where('zona', $zona)
            ->get();
        if($grupos->count() > 0 ) {
            foreach ($grupos as $grupo) {
                if ($grupo->partido_id) {
                    \App\Partido::where('id', $grupo->partido_id)->delete();
                }
                $grupo->delete();
            }                
            DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', $zona)
                ->delete();
        }
        
        if ($tieneCuatroParejas && $request->pareja_4_idJugadorArriba && $request->pareja_4_idJugadorAbajo) {
            // ESTRUCTURA CON 4 PAREJAS: SEMIFINALES Y FINAL
            // Semifinal 1: Pareja 1 vs Pareja 2
            $partidoSF1 = $this->crearPartido();
            $grupoSF1_P1 = new Grupo;
            $grupoSF1_P1->torneo_id = $torneoId;
            $grupoSF1_P1->zona = $zona;
            $grupoSF1_P1->fecha = $request->input('pareja_1_partido_1_dia', '2000-01-01');
            $grupoSF1_P1->horario = $request->input('pareja_1_partido_1_horario', '00:00');
            $grupoSF1_P1->jugador_1 = $request->pareja_1_idJugadorArriba;
            $grupoSF1_P1->jugador_2 = $request->pareja_1_idJugadorAbajo;
            $grupoSF1_P1->partido_id = $partidoSF1->id;
            $grupoSF1_P1->save();
            
            $grupoSF1_P2 = new Grupo;
            $grupoSF1_P2->torneo_id = $torneoId;
            $grupoSF1_P2->zona = $zona;
            $grupoSF1_P2->fecha = $request->input('pareja_2_partido_1_dia', '2000-01-01');
            $grupoSF1_P2->horario = $request->input('pareja_2_partido_1_horario', '00:00');
            $grupoSF1_P2->jugador_1 = $request->pareja_2_idJugadorArriba;
            $grupoSF1_P2->jugador_2 = $request->pareja_2_idJugadorAbajo;
            $grupoSF1_P2->partido_id = $partidoSF1->id;
            $grupoSF1_P2->save();
            
            // Semifinal 2: Pareja 3 vs Pareja 4
            $partidoSF2 = $this->crearPartido();
            $grupoSF2_P3 = new Grupo;
            $grupoSF2_P3->torneo_id = $torneoId;
            $grupoSF2_P3->zona = $zona;
            $grupoSF2_P3->fecha = $request->input('pareja_3_partido_1_dia', '2000-01-01');
            $grupoSF2_P3->horario = $request->input('pareja_3_partido_1_horario', '00:00');
            $grupoSF2_P3->jugador_1 = $request->pareja_3_idJugadorArriba;
            $grupoSF2_P3->jugador_2 = $request->pareja_3_idJugadorAbajo;
            $grupoSF2_P3->partido_id = $partidoSF2->id;
            $grupoSF2_P3->save();
            
            $grupoSF2_P4 = new Grupo;
            $grupoSF2_P4->torneo_id = $torneoId;
            $grupoSF2_P4->zona = $zona;
            $grupoSF2_P4->fecha = $request->input('pareja_4_partido_1_dia', '2000-01-01');
            $grupoSF2_P4->horario = $request->input('pareja_4_partido_1_horario', '00:00');
            $grupoSF2_P4->jugador_1 = $request->pareja_4_idJugadorArriba;
            $grupoSF2_P4->jugador_2 = $request->pareja_4_idJugadorAbajo;
            $grupoSF2_P4->partido_id = $partidoSF2->id;
            $grupoSF2_P4->save();
            
            // Final: Ganador SF1 vs Ganador SF2 (se crea pero sin jugadores asignados aún)
            $partidoFinal = $this->crearPartido();
            $grupoFinal = new Grupo;
            $grupoFinal->torneo_id = $torneoId;
            $grupoFinal->zona = $zona;
            $grupoFinal->fecha = $request->input('final_dia', '2000-01-01');
            $grupoFinal->horario = $request->input('final_horario', '00:00');
            $grupoFinal->jugador_1 = 0; // Se asignará después según resultados
            $grupoFinal->jugador_2 = 0;
            $grupoFinal->partido_id = $partidoFinal->id;
            $grupoFinal->save();
            
            // Consolación: Perdedor SF1 vs Perdedor SF2
            $partidoConsolacion = $this->crearPartido();
            $grupoConsolacion = new Grupo;
            $grupoConsolacion->torneo_id = $torneoId;
            $grupoConsolacion->zona = $zona;
            $grupoConsolacion->fecha = $request->input('consolacion_dia', '2000-01-01');
            $grupoConsolacion->horario = $request->input('consolacion_horario', '00:00');
            $grupoConsolacion->jugador_1 = 0; // Se asignará después según resultados
            $grupoConsolacion->jugador_2 = 0;
            $grupoConsolacion->partido_id = $partidoConsolacion->id;
            $grupoConsolacion->save();
            
            return response()->json(['success' => true, 'partidos' => [$partidoSF1->id, $partidoSF2->id, $partidoFinal->id, $partidoConsolacion->id]]);
        } else {
            // ESTRUCTURA CON 3 PAREJAS: TODOS CONTRA TODOS
            $partido1 = $this->crearPartido();
            $partido2 = $this->crearPartido();
            $partido3 = $this->crearPartido();        

            $grupoA1 = new Grupo;
            $grupoA1->torneo_id = $torneoId;
            $grupoA1->zona = $zona;
            $grupoA1->fecha = $request->input('pareja_1_partido_1_dia', '2000-01-01');
            $grupoA1->horario = $request->input('pareja_1_partido_1_horario', '00:00');
            $grupoA1->jugador_1 = $request->pareja_1_idJugadorArriba;
            $grupoA1->jugador_2 = $request->pareja_1_idJugadorAbajo;
            $grupoA1->partido_id = $partido1->id;
            $grupoA1->save();   
            
            $grupoA2 = new Grupo;
            $grupoA2->torneo_id = $torneoId;
            $grupoA2->zona = $zona;
            $grupoA2->fecha = $request->input('pareja_1_partido_2_dia', '2000-01-01');
            $grupoA2->horario = $request->input('pareja_1_partido_2_horario', '00:00');
            $grupoA2->jugador_1 = $request->pareja_1_idJugadorArriba;
            $grupoA2->jugador_2 = $request->pareja_1_idJugadorAbajo;
            $grupoA2->partido_id = $partido2->id;
            $grupoA2->save();

            // PAREJA 2 ZONA Y PARTIDOS        
            $grupoA3 = new Grupo;
            $grupoA3->torneo_id = $torneoId;
            $grupoA3->zona = $zona;
            $grupoA3->fecha = $request->input('pareja_2_partido_1_dia', '2000-01-01');
            $grupoA3->horario = $request->input('pareja_2_partido_1_horario', '00:00');
            $grupoA3->jugador_1 = $request->pareja_2_idJugadorArriba;
            $grupoA3->jugador_2 = $request->pareja_2_idJugadorAbajo;
            $grupoA3->partido_id = $partido1->id;
            $grupoA3->save();   
            
            $grupoA4 = new Grupo;
            $grupoA4->torneo_id = $torneoId;
            $grupoA4->zona = $zona;
            $grupoA4->fecha = $request->input('pareja_2_partido_2_dia', '2000-01-01');
            $grupoA4->horario = $request->input('pareja_2_partido_2_horario', '00:00');
            $grupoA4->jugador_1 = $request->pareja_2_idJugadorArriba;
            $grupoA4->jugador_2 = $request->pareja_2_idJugadorAbajo;
            $grupoA4->partido_id = $partido3->id;
            $grupoA4->save();

            // PAREJA 3 ZONA Y PARTIDOS        
            $grupoA5 = new Grupo;
            $grupoA5->torneo_id = $torneoId;
            $grupoA5->zona = $zona;
            $grupoA5->fecha = $request->input('pareja_3_partido_1_dia', '2000-01-01');
            $grupoA5->horario = $request->input('pareja_3_partido_1_horario', '00:00');
            $grupoA5->jugador_1 = $request->pareja_3_idJugadorArriba;
            $grupoA5->jugador_2 = $request->pareja_3_idJugadorAbajo;
            $grupoA5->partido_id = $partido2->id;
            $grupoA5->save();   
            
            $grupoA6 = new Grupo;
            $grupoA6->torneo_id = $torneoId;
            $grupoA6->zona = $zona;
            $grupoA6->fecha = $request->input('pareja_3_partido_2_dia', '2000-01-01');
            $grupoA6->horario = $request->input('pareja_3_partido_2_horario', '00:00');
            $grupoA6->jugador_1 = $request->pareja_3_idJugadorArriba;
            $grupoA6->jugador_2 = $request->pareja_3_idJugadorAbajo;
            $grupoA6->partido_id = $partido3->id;
            $grupoA6->save();

            return response()->json(['success' => true, 'partidos' => [$partido1->id, $partido2->id, $partido3->id]]);
        }
    }

    function crearPartido() {
        $partido1 = new Partido;
        $partido1->pareja_1_set_1 = 0;
        $partido1->pareja_1_set_1_tie_break = 0;
        $partido1->pareja_2_set_1 = 0;
        $partido1->pareja_2_set_1_tie_break = 0;
        $partido1->pareja_1_set_2 = 0;
        $partido1->pareja_1_set_2_tie_break = 0;
        $partido1->pareja_2_set_2 = 0;
        $partido1->pareja_2_set_2_tie_break = 0;
        $partido1->pareja_1_set_3 = 0;
        $partido1->pareja_1_set_3_tie_break = 0;    
        $partido1->pareja_2_set_3 = 0;
        $partido1->pareja_2_set_3_tie_break = 0;
        $partido1->pareja_1_set_super_tie_break = 0;
        $partido1->pareja_2_set_super_tie_break = 0;
        $partido1->save();

        return $partido1;
    }

    /**
     * Ordena los partidos de manera que se intercalen las parejas
     * para evitar que la misma pareja aparezca en partidos consecutivos
     */
    private function ordenarPartidosIntercalados($partidos) {
        if (count($partidos) <= 1) {
            return $partidos;
        }
        
        $ordenados = [];
        $usados = [];
        $ultimaPareja1 = null;
        $ultimaPareja2 = null;
        
        // Función para obtener la key de una pareja
        $getParejaKey = function($pareja) {
            return $pareja['jugador_1'] . '_' . $pareja['jugador_2'];
        };
        
        // Función para verificar si un partido tiene alguna pareja en común con el último
        $tieneParejaComun = function($partido, $ultP1, $ultP2) use ($getParejaKey) {
            if (!$ultP1 || !$ultP2) return false;
            $key1 = $getParejaKey($partido['pareja_1']);
            $key2 = $getParejaKey($partido['pareja_2']);
            $ultKey1 = $getParejaKey($ultP1);
            $ultKey2 = $getParejaKey($ultP2);
            return ($key1 == $ultKey1 || $key1 == $ultKey2 || $key2 == $ultKey1 || $key2 == $ultKey2);
        };
        
        // Algoritmo: intentar siempre elegir un partido que no tenga parejas en común con el anterior
        while (count($ordenados) < count($partidos)) {
            $encontrado = false;
            
            // Primera pasada: buscar partidos sin parejas comunes
            foreach ($partidos as $index => $partido) {
                if (isset($usados[$index])) continue;
                
                if (!$tieneParejaComun($partido, $ultimaPareja1, $ultimaPareja2)) {
                    $ordenados[] = $partido;
                    $usados[$index] = true;
                    $ultimaPareja1 = $partido['pareja_1'];
                    $ultimaPareja2 = $partido['pareja_2'];
                    $encontrado = true;
                    break;
                }
            }
            
            // Si no se encontró uno sin parejas comunes, tomar el primero disponible
            if (!$encontrado) {
                foreach ($partidos as $index => $partido) {
                    if (isset($usados[$index])) continue;
                    
                    $ordenados[] = $partido;
                    $usados[$index] = true;
                    $ultimaPareja1 = $partido['pareja_1'];
                    $ultimaPareja2 = $partido['pareja_2'];
                    break;
                }
            }
        }
        
        return $ordenados;
    }

    public function guardarTorneoAmericano(Request $request) {
        $torneoId = $request->torneo_id;
        $grupos = $request->grupos; // Array de grupos con zona y parejas
        
        // Eliminar solo los grupos iniciales del torneo (sin partido_id)
        // NO eliminar los grupos que ya tienen partido_id, porque esos son los partidos ya creados
        $gruposExistentes = \App\Grupo::where('torneo_id', $torneoId)
                                        ->whereNull('partido_id')
                                        ->get();
        foreach ($gruposExistentes as $grupo) {
            $grupo->delete();
        }
        
        // Crear nuevos grupos
        foreach ($grupos as $grupoData) {
            $zona = $grupoData['zona'];
            $parejas = $grupoData['parejas'] ?? []; // Array de parejas [{jugador1: id, jugador2: id}, ...]
            
            // Para el torneo americano, guardamos cada pareja
            // NO crear partidos aquí, se crearán cuando se presione "Comenzar Torneo"
            if (count($parejas) > 0) {
                foreach ($parejas as $pareja) {
                    $grupo = new Grupo;
                    $grupo->torneo_id = $torneoId;
                    $grupo->zona = $zona;
                    $grupo->fecha = '2000-01-01'; // Fecha por defecto
                    $grupo->horario = '00:00'; // Horario por defecto
                    $grupo->jugador_1 = $pareja['jugador1'] ?? null;
                    $grupo->jugador_2 = $pareja['jugador2'] ?? null;
                    $grupo->partido_id = null; // Se asignará cuando se creen los partidos
                    $grupo->save();
                }
            }
        }
        
        return response()->json(['success' => true, 'message' => 'Torneo americano guardado correctamente']);
    }

    public function crearPartidosAmericano(Request $request) {
        try {
            $torneoId = $request->torneo_id;
            
            $torneo = DB::table('torneos')
                            ->where('torneos.id', $torneoId)
                            ->where('torneos.activo', 1)
                            ->first();
            
            if (!$torneo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Torneo no encontrado'
                ], 404);
            }
            
            // Verificar si ya hay partidos creados y jugados
            $partidosExistentes = DB::table('grupos')
                                    ->where('grupos.torneo_id', $torneoId)
                                    ->whereNotNull('grupos.partido_id')
                                    ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final'])
                                    ->count();
            
            // Si ya hay partidos creados, solo verificar que no falten partidos
            // No eliminar grupos iniciales si ya hay partidos jugados
            if ($partidosExistentes > 0) {
                // Verificar si hay grupos iniciales sin partido_id
                $gruposIniciales = DB::table('grupos')
                                    ->where('grupos.torneo_id', $torneoId)
                                    ->whereNull('grupos.partido_id')
                                    ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final'])
                                    ->count();
                
                // Si hay grupos iniciales, crear los partidos faltantes
                if ($gruposIniciales > 0) {
                    // Continuar con la lógica de creación de partidos
                } else {
                    // Ya están todos los partidos creados, solo retornar éxito
                    return response()->json([
                        'success' => true,
                        'message' => 'Los partidos ya están creados',
                        'partidos_existentes' => true
                    ]);
                }
            }
            
            // Obtener solo los grupos iniciales del torneo (sin partido_id)
            // Estos son los grupos que se crearon al guardar el torneo, antes de crear los partidos
            $grupos = DB::table('grupos')
                            ->where('grupos.torneo_id', $torneoId)
                            ->whereNull('grupos.partido_id') // Solo grupos iniciales, sin partido_id
                            ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final']) // Excluir grupos de eliminatoria
                            ->orderBy('grupos.zona')
                            ->orderBy('grupos.id')
                            ->get();
        
        // Agrupar por zona y extraer parejas únicas
        $parejasPorZona = [];
        $parejasUnicas = [];
        foreach ($grupos as $grupo) {
            $zona = $grupo->zona;
            if (!isset($parejasPorZona[$zona])) {
                $parejasPorZona[$zona] = [];
            }
            if ($grupo->jugador_1 && $grupo->jugador_2) {
                $keyPareja = $zona . '-' . min($grupo->jugador_1, $grupo->jugador_2) . '-' . max($grupo->jugador_1, $grupo->jugador_2);
                if (!isset($parejasUnicas[$keyPareja])) {
                    $parejasPorZona[$zona][] = [
                        'grupo_id' => $grupo->id,
                        'jugador_1' => $grupo->jugador_1,
                        'jugador_2' => $grupo->jugador_2,
                        'partido_id' => $grupo->partido_id
                    ];
                    $parejasUnicas[$keyPareja] = true;
                }
            }
        }
        
        // Crear todos los partidos posibles (todos contra todos) para cada zona
        foreach ($parejasPorZona as $zona => $parejas) {
            // Generar todas las combinaciones "todos contra todos"
            $combinaciones = [];
            for ($i = 0; $i < count($parejas); $i++) {
                for ($j = $i + 1; $j < count($parejas); $j++) {
                    $combinaciones[] = [
                        'pareja_1' => $parejas[$i],
                        'pareja_2' => $parejas[$j],
                    ];
                }
            }
            
            // Para cada combinación, verificar si existe partido, si no existe crearlo
            foreach ($combinaciones as $combo) {
                $pareja1 = $combo['pareja_1'];
                $pareja2 = $combo['pareja_2'];
                
                // Buscar si ya existe un partido con estas parejas
                $partidoExistente = DB::table('grupos as g1')
                    ->join('grupos as g2', function($join) {
                        $join->on('g1.partido_id', '=', 'g2.partido_id')
                             ->whereRaw('g1.id != g2.id')
                             ->whereNotNull('g1.partido_id')
                             ->whereNotNull('g2.partido_id');
                    })
                    ->where('g1.torneo_id', $torneoId)
                    ->where('g1.zona', $zona)
                    ->where('g2.torneo_id', $torneoId)
                    ->where('g2.zona', $zona)
                    ->where(function($query) use ($pareja1, $pareja2) {
                        $query->where(function($q) use ($pareja1, $pareja2) {
                            $q->where('g1.jugador_1', $pareja1['jugador_1'])
                              ->where('g1.jugador_2', $pareja1['jugador_2'])
                              ->where('g2.jugador_1', $pareja2['jugador_1'])
                              ->where('g2.jugador_2', $pareja2['jugador_2']);
                        })
                        ->orWhere(function($q) use ($pareja1, $pareja2) {
                            $q->where('g1.jugador_1', $pareja2['jugador_1'])
                              ->where('g1.jugador_2', $pareja2['jugador_2'])
                              ->where('g2.jugador_1', $pareja1['jugador_1'])
                              ->where('g2.jugador_2', $pareja1['jugador_2']);
                        });
                    })
                    ->select('g1.partido_id')
                    ->first();
                
                // Solo crear si no existe
                if (!$partidoExistente) {
                    // Verificar una vez más que no exista antes de crear (doble verificación)
                    $verificacionFinal = DB::table('grupos as g1')
                        ->join('grupos as g2', function($join) {
                            $join->on('g1.partido_id', '=', 'g2.partido_id')
                                 ->whereRaw('g1.id != g2.id')
                                 ->whereNotNull('g1.partido_id')
                                 ->whereNotNull('g2.partido_id');
                        })
                        ->where('g1.torneo_id', $torneoId)
                        ->where('g1.zona', $zona)
                        ->where('g2.torneo_id', $torneoId)
                        ->where('g2.zona', $zona)
                        ->where(function($query) use ($pareja1, $pareja2) {
                            $query->where(function($q) use ($pareja1, $pareja2) {
                                $q->where('g1.jugador_1', $pareja1['jugador_1'])
                                  ->where('g1.jugador_2', $pareja1['jugador_2'])
                                  ->where('g2.jugador_1', $pareja2['jugador_1'])
                                  ->where('g2.jugador_2', $pareja2['jugador_2']);
                            })
                            ->orWhere(function($q) use ($pareja1, $pareja2) {
                                $q->where('g1.jugador_1', $pareja2['jugador_1'])
                                  ->where('g1.jugador_2', $pareja2['jugador_2'])
                                  ->where('g2.jugador_1', $pareja1['jugador_1'])
                                  ->where('g2.jugador_2', $pareja1['jugador_2']);
                            });
                        })
                        ->select('g1.partido_id')
                        ->first();
                    
                    if (!$verificacionFinal) {
                        $nuevoPartido = $this->crearPartido();
                        
                        // Crear nuevos registros de grupo para este partido específico
                        // (cada pareja puede tener múltiples partidos, así que creamos un registro por partido)
                        $grupo1 = new Grupo;
                        $grupo1->torneo_id = $torneoId;
                        $grupo1->zona = $zona;
                        $grupo1->fecha = '2000-01-01';
                        $grupo1->horario = '00:00';
                        $grupo1->jugador_1 = $pareja1['jugador_1'];
                        $grupo1->jugador_2 = $pareja1['jugador_2'];
                        $grupo1->partido_id = $nuevoPartido->id;
                        $grupo1->save();
                        
                        $grupo2 = new Grupo;
                        $grupo2->torneo_id = $torneoId;
                        $grupo2->zona = $zona;
                        $grupo2->fecha = '2000-01-01';
                        $grupo2->horario = '00:00';
                        $grupo2->jugador_1 = $pareja2['jugador_1'];
                        $grupo2->jugador_2 = $pareja2['jugador_2'];
                        $grupo2->partido_id = $nuevoPartido->id;
                        $grupo2->save();
                    }
                }
            }
        }
        
            // Solo eliminar los grupos iniciales si no hay partidos jugados
            // Si ya hay partidos jugados, mantener los grupos iniciales por seguridad
            $partidosConResultados = DB::table('partidos')
                                        ->join('grupos', 'partidos.id', '=', 'grupos.partido_id')
                                        ->where('grupos.torneo_id', $torneoId)
                                        ->where(function($query) {
                                            $query->where('partidos.pareja_1_set_1', '>', 0)
                                                  ->orWhere('partidos.pareja_2_set_1', '>', 0);
                                        })
                                        ->count();
            
            // Solo eliminar grupos iniciales si no hay partidos con resultados
            if ($partidosConResultados == 0) {
                DB::table('grupos')
                    ->where('torneo_id', $torneoId)
                    ->whereNull('partido_id')
                    ->whereNotIn('zona', ['cuartos final', 'semifinal', 'final']) // No eliminar grupos de eliminatoria
                    ->delete();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Partidos creados correctamente'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al crear partidos americano: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear los partidos: ' . $e->getMessage()
            ], 500);
        }
    }

    public function tvTorneoAmericano(Request $request) {
        $torneoId = $request->torneo_id;
        
        $torneo = DB::table('torneos')
                        ->where('torneos.id', $torneoId)
                        ->where('torneos.activo', 1)
                        ->first();
        
        if (!$torneo) {
            return redirect()->route('index')->with('error', 'Torneo no encontrado');
        }
        
        // Obtener todos los grupos del torneo (con partido_id) para identificar parejas y zonas
        // Después de crear los partidos, los grupos iniciales se eliminan, así que usamos los grupos con partido_id
        $grupos = DB::table('grupos')
                        ->where('grupos.torneo_id', $torneoId)
                        ->whereNotNull('grupos.partido_id') // Solo grupos con partido_id (partidos creados)
                        ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final']) // Excluir grupos de eliminatoria
                        ->orderBy('grupos.zona')
                        ->orderBy('grupos.id')
                        ->get();
        
        // Agrupar por zona y extraer parejas únicas (sin duplicados)
        $parejasPorZona = [];
        $parejasUnicas = []; // Para evitar duplicados: "zona-jugador1-jugador2"
        foreach ($grupos as $grupo) {
            $zona = $grupo->zona;
            if (!isset($parejasPorZona[$zona])) {
                $parejasPorZona[$zona] = [];
            }
            // Solo agregar si tiene ambos jugadores (es una pareja válida)
            if ($grupo->jugador_1 && $grupo->jugador_2) {
                $keyPareja = $zona . '-' . min($grupo->jugador_1, $grupo->jugador_2) . '-' . max($grupo->jugador_1, $grupo->jugador_2);
                if (!isset($parejasUnicas[$keyPareja])) {
                    $parejasPorZona[$zona][] = [
                        'grupo_id' => $grupo->id,
                        'jugador_1' => $grupo->jugador_1,
                        'jugador_2' => $grupo->jugador_2,
                        'partido_id' => $grupo->partido_id
                    ];
                    $parejasUnicas[$keyPareja] = true;
                }
            }
        }
        
        // Construir TODOS los partidos posibles (todos contra todos) para cada zona
        // Crear los partidos si no existen, o usar los existentes
        $partidosPorZona = [];
        
        foreach ($parejasPorZona as $zona => $parejas) {
            $partidosPorZona[$zona] = [];
            
            // Generar todas las combinaciones "todos contra todos"
            $combinaciones = [];
            for ($i = 0; $i < count($parejas); $i++) {
                for ($j = $i + 1; $j < count($parejas); $j++) {
                    $combinaciones[] = [
                        'pareja_1' => $parejas[$i],
                        'pareja_2' => $parejas[$j],
                    ];
                }
            }
            
            // Para cada combinación, SOLO buscar partidos existentes (NO crear nuevos)
            $partidosTemporales = [];
            foreach ($combinaciones as $combo) {
                $pareja1 = $combo['pareja_1'];
                $pareja2 = $combo['pareja_2'];
                
                // SOLO buscar si existe un partido con estas parejas en la BD
                $partidoExistente = DB::table('grupos as g1')
                    ->join('grupos as g2', function($join) {
                        $join->on('g1.partido_id', '=', 'g2.partido_id')
                             ->whereRaw('g1.id != g2.id');
                    })
                    ->where('g1.torneo_id', $torneoId)
                    ->where('g1.zona', $zona)
                    ->where('g2.torneo_id', $torneoId)
                    ->where('g2.zona', $zona)
                    ->whereNotNull('g1.partido_id')
                    ->whereNotNull('g2.partido_id')
                    ->where(function($query) use ($pareja1, $pareja2) {
                        // Caso 1: g1 tiene pareja1 y g2 tiene pareja2
                        $query->where(function($q) use ($pareja1, $pareja2) {
                            $q->where('g1.jugador_1', $pareja1['jugador_1'])
                              ->where('g1.jugador_2', $pareja1['jugador_2'])
                              ->where('g2.jugador_1', $pareja2['jugador_1'])
                              ->where('g2.jugador_2', $pareja2['jugador_2']);
                        })
                        // Caso 2: g1 tiene pareja2 y g2 tiene pareja1
                        ->orWhere(function($q) use ($pareja1, $pareja2) {
                            $q->where('g1.jugador_1', $pareja2['jugador_1'])
                              ->where('g1.jugador_2', $pareja2['jugador_2'])
                              ->where('g2.jugador_1', $pareja1['jugador_1'])
                              ->where('g2.jugador_2', $pareja1['jugador_2']);
                        });
                    })
                    ->select('g1.partido_id')
                    ->first();
                
                // Si existe, usar su partido_id, si no existe, usar null
                $partidoIdEncontrado = $partidoExistente ? $partidoExistente->partido_id : null;
                
                // Agregar el partido temporalmente
                $partidosTemporales[] = [
                    'partido_id' => $partidoIdEncontrado,
                    'pareja_1' => $pareja1,
                    'pareja_2' => $pareja2
                ];
            }
            
            // Ordenar los partidos para intercalar las parejas
            $partidosOrdenados = $this->ordenarPartidosIntercalados($partidosTemporales);
            
            // Asignar números de partido en el orden final
            $numeroPartido = 1;
            foreach ($partidosOrdenados as $partido) {
                $partidosPorZona[$zona][] = [
                    'partido_id' => $partido['partido_id'],
                    'pareja_1' => $partido['pareja_1'],
                    'pareja_2' => $partido['pareja_2'],
                    'numero_partido' => $numeroPartido
                ];
                $numeroPartido++;
            }
        }
        
        // Obtener información de los jugadores (como array, no keyBy para que funcione en JavaScript)
        $jugadores = DB::table('jugadores')
                        ->where('jugadores.activo', 1)
                        ->get()
                        ->toArray();
        
        // Obtener resultados de partidos existentes
        $partidosIds = [];
        foreach ($partidosPorZona as $zona => $partidos) {
            foreach ($partidos as $partido) {
                if ($partido['partido_id']) {
                    $partidosIds[] = $partido['partido_id'];
                }
            }
        }
        $partidosIds = array_unique($partidosIds);
        
        $partidosConResultados = [];
        if (!empty($partidosIds)) {
            $partidos = DB::table('partidos')
                            ->whereIn('id', $partidosIds)
                            ->get();
            
            foreach ($partidos as $partido) {
                $partidosConResultados[$partido->id] = $partido;
            }
        }
        
        // Calcular posiciones por zona
        $posicionesPorZona = [];
        $gruposCollection = collect($grupos);

        foreach (array_keys($partidosPorZona) as $zona) {
             $gruposZona = $gruposCollection->where('zona', $zona);
             
             $parejas = [];
             foreach ($gruposZona as $grupo) {
                if (!$grupo->jugador_1 || !$grupo->jugador_2) continue;
                
                $key = $grupo->jugador_1 . '_' . $grupo->jugador_2;
                if (!isset($parejas[$key])) {
                    $parejas[$key] = [
                        'jugador_1' => $grupo->jugador_1,
                        'jugador_2' => $grupo->jugador_2,
                        'partidos_ganados' => 0,
                        'partidos_perdidos' => 0,
                        'puntos_ganados' => 0, 
                        'partidos_directos' => []
                    ];
                }
             }

             // Iterar sobre partidos
             foreach ($partidosPorZona[$zona] as $p) {
                 if (!$p['partido_id']) continue;
                 $pid = $p['partido_id'];
                 if (!isset($partidosConResultados[$pid])) continue;
                 
                 $partido = $partidosConResultados[$pid];
                 
                 $gruposPartido = $gruposCollection->where('partido_id', $pid)->sortBy('id')->values();
                 if ($gruposPartido->count() < 2) continue;
                 
                 $g1 = $gruposPartido[0];
                 $paramPareja1 = $p['pareja_1'];
                 
                 $p1_key = $paramPareja1['jugador_1'] . '_' . $paramPareja1['jugador_2'];
                 $p2_key = $p['pareja_2']['jugador_1'] . '_' . $p['pareja_2']['jugador_2'];
                 
                 $set1 = $partido->pareja_1_set_1;
                 $set2 = $partido->pareja_2_set_1;
                 
                 $p1_score = 0; 
                 $p2_score = 0;
                 
                 // Verificar orden
                 if ($g1->jugador_1 == $paramPareja1['jugador_1'] && $g1->jugador_2 == $paramPareja1['jugador_2']) {
                     $p1_score = $set1;
                     $p2_score = $set2;
                 } else {
                     $p1_score = $set2;
                     $p2_score = $set1;
                 }
                 
                 if ($p1_score > 0 || $p2_score > 0) {
                      if ($p1_score > $p2_score) {
                          if(isset($parejas[$p1_key])) {
                              $parejas[$p1_key]['partidos_ganados']++;
                              $parejas[$p1_key]['puntos_ganados'] += $p1_score;
                              $parejas[$p1_key]['partidos_directos'][$p2_key] = ['ganado'=>true];
                          }
                          if(isset($parejas[$p2_key])) {
                              $parejas[$p2_key]['partidos_perdidos']++;
                              $parejas[$p2_key]['puntos_ganados'] += $p2_score;
                              $parejas[$p2_key]['partidos_directos'][$p1_key] = ['ganado'=>false];
                          }
                      } elseif ($p2_score > $p1_score) {
                          if(isset($parejas[$p2_key])) {
                              $parejas[$p2_key]['partidos_ganados']++;
                              $parejas[$p2_key]['puntos_ganados'] += $p2_score;
                              $parejas[$p2_key]['partidos_directos'][$p1_key] = ['ganado'=>true];
                          }
                          if(isset($parejas[$p1_key])) {
                              $parejas[$p1_key]['partidos_perdidos']++;
                              $parejas[$p1_key]['puntos_ganados'] += $p1_score;
                              $parejas[$p1_key]['partidos_directos'][$p2_key] = ['ganado'=>false];
                          }
                      }
                 }
             }
             
             // Sort
             foreach ($parejas as $key => $val) { $parejas[$key]['key'] = $key; }
             $posiciones = array_values($parejas);
             
             usort($posiciones, function($a, $b) {
                if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                    return $b['partidos_ganados'] - $a['partidos_ganados'];
                }
                if ($a['puntos_ganados'] != $b['puntos_ganados']) {
                    return $b['puntos_ganados'] - $a['puntos_ganados'];
                }
                $keyA = $a['key'];
                $keyB = $b['key'];
                if (isset($a['partidos_directos'][$keyB])) {
                    return $a['partidos_directos'][$keyB]['ganado'] ? -1 : 1;
                }
                return 0;
             });
             
             $posicionesPorZona[$zona] = $posiciones;
        }
        
        return View('bahia_padel.tv.resultados')
                    ->with('torneo', $torneo)
                    ->with('partidosPorZona', $partidosPorZona)
                    ->with('jugadores', $jugadores)
                    ->with('partidosConResultados', $partidosConResultados)
                    ->with('posicionesPorZona', $posicionesPorZona);
    }

    public function adminTorneoAmericanoPartidos(Request $request) {
        $torneoId = $request->torneo_id;
        
        $torneo = DB::table('torneos')
                        ->where('torneos.id', $torneoId)
                        ->where('torneos.activo', 1)
                        ->first();
        
        if (!$torneo) {
            return redirect()->route('admintorneos')->with('error', 'Torneo no encontrado');
        }
        
        // Obtener todos los grupos del torneo (con partido_id) para identificar parejas y zonas
        // Después de crear los partidos, los grupos iniciales se eliminan, así que usamos los grupos con partido_id
        $grupos = DB::table('grupos')
                        ->where('grupos.torneo_id', $torneoId)
                        ->whereNotNull('grupos.partido_id') // Solo grupos con partido_id (partidos creados)
                        ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final']) // Excluir grupos de eliminatoria
                        ->orderBy('grupos.zona')
                        ->orderBy('grupos.id')
                        ->get();
        
        // Agrupar por zona y extraer parejas únicas (sin duplicados)
        $parejasPorZona = [];
        $parejasUnicas = []; // Para evitar duplicados: "zona-jugador1-jugador2"
        foreach ($grupos as $grupo) {
            $zona = $grupo->zona;
            if (!isset($parejasPorZona[$zona])) {
                $parejasPorZona[$zona] = [];
            }
            // Solo agregar si tiene ambos jugadores (es una pareja válida)
            if ($grupo->jugador_1 && $grupo->jugador_2) {
                $keyPareja = $zona . '-' . min($grupo->jugador_1, $grupo->jugador_2) . '-' . max($grupo->jugador_1, $grupo->jugador_2);
                if (!isset($parejasUnicas[$keyPareja])) {
                    $parejasPorZona[$zona][] = [
                        'grupo_id' => $grupo->id,
                        'jugador_1' => $grupo->jugador_1,
                        'jugador_2' => $grupo->jugador_2,
                        'partido_id' => $grupo->partido_id
                    ];
                    $parejasUnicas[$keyPareja] = true;
                }
            }
        }
        
        // Construir TODOS los partidos posibles (todos contra todos) para cada zona
        // Crear los partidos si no existen, o usar los existentes
        $partidosPorZona = [];
        
        foreach ($parejasPorZona as $zona => $parejas) {
            $partidosPorZona[$zona] = [];
            
            // Generar todas las combinaciones "todos contra todos"
            $combinaciones = [];
            for ($i = 0; $i < count($parejas); $i++) {
                for ($j = $i + 1; $j < count($parejas); $j++) {
                    $combinaciones[] = [
                        'pareja_1' => $parejas[$i],
                        'pareja_2' => $parejas[$j],
                    ];
                }
            }
            
            // Para cada combinación, SOLO buscar partidos existentes (NO crear nuevos)
            $partidosTemporales = [];
            foreach ($combinaciones as $combo) {
                $pareja1 = $combo['pareja_1'];
                $pareja2 = $combo['pareja_2'];
                
                // SOLO buscar si existe un partido con estas parejas en la BD
                $partidoExistente = DB::table('grupos as g1')
                    ->join('grupos as g2', function($join) {
                        $join->on('g1.partido_id', '=', 'g2.partido_id')
                             ->whereRaw('g1.id != g2.id');
                    })
                    ->where('g1.torneo_id', $torneoId)
                    ->where('g1.zona', $zona)
                    ->where('g2.torneo_id', $torneoId)
                    ->where('g2.zona', $zona)
                    ->whereNotNull('g1.partido_id')
                    ->whereNotNull('g2.partido_id')
                    ->where(function($query) use ($pareja1, $pareja2) {
                        // Caso 1: g1 tiene pareja1 y g2 tiene pareja2
                        $query->where(function($q) use ($pareja1, $pareja2) {
                            $q->where('g1.jugador_1', $pareja1['jugador_1'])
                              ->where('g1.jugador_2', $pareja1['jugador_2'])
                              ->where('g2.jugador_1', $pareja2['jugador_1'])
                              ->where('g2.jugador_2', $pareja2['jugador_2']);
                        })
                        // Caso 2: g1 tiene pareja2 y g2 tiene pareja1
                        ->orWhere(function($q) use ($pareja1, $pareja2) {
                            $q->where('g1.jugador_1', $pareja2['jugador_1'])
                              ->where('g1.jugador_2', $pareja2['jugador_2'])
                              ->where('g2.jugador_1', $pareja1['jugador_1'])
                              ->where('g2.jugador_2', $pareja1['jugador_2']);
                        });
                    })
                    ->select('g1.partido_id')
                    ->first();
                
                // Si existe, usar su partido_id, si no existe, usar null
                $partidoIdEncontrado = $partidoExistente ? $partidoExistente->partido_id : null;
                
                // Agregar el partido temporalmente
                $partidosTemporales[] = [
                    'partido_id' => $partidoIdEncontrado,
                    'pareja_1' => $pareja1,
                    'pareja_2' => $pareja2
                ];
            }
            
            // Ordenar los partidos para intercalar las parejas
            $partidosOrdenados = $this->ordenarPartidosIntercalados($partidosTemporales);
            
            // Asignar números de partido en el orden final
            $numeroPartido = 1;
            foreach ($partidosOrdenados as $partido) {
                $partidosPorZona[$zona][] = [
                    'partido_id' => $partido['partido_id'],
                    'pareja_1' => $partido['pareja_1'],
                    'pareja_2' => $partido['pareja_2'],
                    'numero_partido' => $numeroPartido
                ];
                $numeroPartido++;
            }
        }
        
        // Obtener información de los jugadores (como array, no keyBy para que funcione en JavaScript)
        $jugadores = DB::table('jugadores')
                        ->where('jugadores.activo', 1)
                        ->get()
                        ->toArray();
        
        // Obtener resultados de partidos existentes
        $partidosIds = [];
        foreach ($partidosPorZona as $zona => $partidos) {
            foreach ($partidos as $partido) {
                if ($partido['partido_id']) {
                    $partidosIds[] = $partido['partido_id'];
                }
            }
        }
        $partidosIds = array_unique($partidosIds);
        
        $partidosConResultados = [];
        if (!empty($partidosIds)) {
            $partidos = DB::table('partidos')
                            ->whereIn('id', $partidosIds)
                            ->get();
            
            foreach ($partidos as $partido) {
                $partidosConResultados[$partido->id] = $partido;
            }
        }
        
        return View('bahia_padel.admin.torneo.partidos_americano')
                    ->with('torneo', $torneo)
                    ->with('partidosPorZona', $partidosPorZona)
                    ->with('jugadores', $jugadores)
                    ->with('partidosConResultados', $partidosConResultados);
    }

    public function guardarResultadoAmericano(Request $request) {
        $partidoId = $request->partido_id;
        $pareja1Set1 = $request->pareja_1_set_1 ?? 0;
        $pareja2Set1 = $request->pareja_2_set_1 ?? 0;
        $torneoId = $request->torneo_id ?? null;
        $zona = $request->zona ?? null;
        $pareja1Jugador1 = $request->pareja_1_jugador_1 ?? null;
        $pareja1Jugador2 = $request->pareja_1_jugador_2 ?? null;
        $pareja2Jugador1 = $request->pareja_2_jugador_1 ?? null;
        $pareja2Jugador2 = $request->pareja_2_jugador_2 ?? null;
        
        $partido = null;
        
        // Si tenemos un partido_id válido (número mayor a 0), usar ese partido directamente
        $partidoIdInt = is_numeric($partidoId) ? (int)$partidoId : 0;
        if ($partidoIdInt > 0) {
            $partido = Partido::find($partidoIdInt);
        }
        
        // Si no encontramos el partido por ID, buscar por las parejas (pero NUNCA crear uno nuevo)
        // Los partidos ya deberían existir porque se crean al cargar la página
        if (!$partido) {
            // Buscar si ya existe un partido con estas parejas
            if ($torneoId && $zona && $pareja1Jugador1 && $pareja1Jugador2 && $pareja2Jugador1 && $pareja2Jugador2) {
                $partidoExistente = DB::table('grupos as g1')
                    ->join('grupos as g2', function($join) {
                        $join->on('g1.partido_id', '=', 'g2.partido_id')
                             ->whereRaw('g1.id != g2.id')
                             ->whereNotNull('g1.partido_id')
                             ->whereNotNull('g2.partido_id');
                    })
                    ->where('g1.torneo_id', $torneoId)
                    ->where('g1.zona', $zona)
                    ->where('g2.torneo_id', $torneoId)
                    ->where('g2.zona', $zona)
                    ->where(function($query) use ($pareja1Jugador1, $pareja1Jugador2, $pareja2Jugador1, $pareja2Jugador2) {
                        $query->where(function($q) use ($pareja1Jugador1, $pareja1Jugador2) {
                            $q->where('g1.jugador_1', $pareja1Jugador1)
                              ->where('g1.jugador_2', $pareja1Jugador2);
                        })
                        ->where(function($q) use ($pareja2Jugador1, $pareja2Jugador2) {
                            $q->where('g2.jugador_1', $pareja2Jugador1)
                              ->where('g2.jugador_2', $pareja2Jugador2);
                        });
                    })
                    ->orWhere(function($query) use ($pareja1Jugador1, $pareja1Jugador2, $pareja2Jugador1, $pareja2Jugador2) {
                        $query->where(function($q) use ($pareja2Jugador1, $pareja2Jugador2) {
                            $q->where('g1.jugador_1', $pareja2Jugador1)
                              ->where('g1.jugador_2', $pareja2Jugador2);
                        })
                        ->where(function($q) use ($pareja1Jugador1, $pareja1Jugador2) {
                            $q->where('g2.jugador_1', $pareja1Jugador1)
                              ->where('g2.jugador_2', $pareja1Jugador2);
                        });
                    })
                    ->select('g1.partido_id')
                    ->first();
                
                if ($partidoExistente && $partidoExistente->partido_id) {
                    // Usar el partido existente
                    $partido = Partido::find($partidoExistente->partido_id);
                }
            }
        }
        
        // Si no tenemos partido, devolver error
        // NO crear nuevos partidos aquí, deberían existir ya
        if (!$partido) {
            return response()->json([
                'success' => false, 
                'message' => 'Partido no encontrado. Por favor recarga la página para crear los partidos.'
            ]);
        }
        
        // Obtener los grupos asociados a este partido para identificar el orden
        $grupos = DB::table('grupos')
                    ->where('partido_id', $partido->id)
                    ->orderBy('id')
                    ->get();
        
        // En americano solo se guarda el set 1
        // Los valores que vienen del request ya están en el orden correcto según la vista
        // (pareja_1_set_1 corresponde a la primera pareja mostrada, pareja_2_set_1 a la segunda)
        // Pero necesitamos guardarlos según el orden de los grupos en la BD
        if ($grupos->count() >= 2) {
            $g1 = $grupos[0];
            $g2 = $grupos[1];
            
            // Verificar qué pareja corresponde a cada grupo
            if ($g1->jugador_1 == $pareja1Jugador1 && $g1->jugador_2 == $pareja1Jugador2) {
                // El primer grupo es pareja 1
                $partido->pareja_1_set_1 = $pareja1Set1;
                $partido->pareja_2_set_1 = $pareja2Set1;
            } else {
                // El primer grupo es pareja 2 (invertido)
                $partido->pareja_1_set_1 = $pareja2Set1;
                $partido->pareja_2_set_1 = $pareja1Set1;
            }
        } else {
            // Si no hay grupos, guardar en el orden recibido
            $partido->pareja_1_set_1 = $pareja1Set1;
            $partido->pareja_2_set_1 = $pareja2Set1;
        }
        
        $partido->save();
        
        // Siempre devolver el partido_id para que el frontend lo actualice
        return response()->json([
            'success' => true, 
            'partido' => $partido, 
            'partido_id' => $partido->id
        ]);
    }

    public function calcularPosicionesAmericano(Request $request) {
        $torneoId = $request->torneo_id;
        $zona = $request->zona;
        
        // Obtener todas las parejas de la zona
        $grupos = DB::table('grupos')
                        ->where('torneo_id', $torneoId)
                        ->where('zona', $zona)
                        ->whereNotNull('jugador_1')
                        ->whereNotNull('jugador_2')
                        ->get();
        
        // Agrupar por pareja (jugador_1 y jugador_2)
        $parejas = [];
        foreach ($grupos as $grupo) {
            $key = $grupo->jugador_1 . '_' . $grupo->jugador_2;
            if (!isset($parejas[$key])) {
                $parejas[$key] = [
                    'jugador_1' => $grupo->jugador_1,
                    'jugador_2' => $grupo->jugador_2,
                    'partidos_ganados' => 0,
                    'partidos_perdidos' => 0,
                    'puntos_ganados' => 0, // Suma de games/sets ganados
                    'partidos_directos' => [] // Para almacenar resultados de partidos directos
                ];
            }
        }
        
        // Obtener todos los partidos de la zona
        $partidosIds = $grupos->pluck('partido_id')->unique();
        $partidos = DB::table('partidos')
                        ->whereIn('id', $partidosIds)
                        ->get();
        
        // Obtener grupos asociados a cada partido para identificar las parejas
        $gruposPorPartido = [];
        foreach ($grupos as $grupo) {
            if (!isset($gruposPorPartido[$grupo->partido_id])) {
                $gruposPorPartido[$grupo->partido_id] = [];
            }
            $gruposPorPartido[$grupo->partido_id][] = $grupo;
        }
        
        // Procesar cada partido identificando las parejas
        foreach ($partidos as $partido) {
            if (!isset($gruposPorPartido[$partido->id]) || count($gruposPorPartido[$partido->id]) < 2) {
                continue; // Necesitamos al menos 2 grupos (2 parejas) para un partido
            }
            
            $gruposPartido = $gruposPorPartido[$partido->id];
            // Ordenar por ID para tener consistencia
            $gruposPartido = collect($gruposPartido)->sortBy('id')->values()->all();
            
            $pareja1Grupo = $gruposPartido[0];
            $pareja2Grupo = $gruposPartido[1];
            
            $key1 = $pareja1Grupo->jugador_1 . '_' . $pareja1Grupo->jugador_2;
            $key2 = $pareja2Grupo->jugador_1 . '_' . $pareja2Grupo->jugador_2;
            
            // Verificar que ambas parejas existan
            if (!isset($parejas[$key1]) || !isset($parejas[$key2])) {
                continue;
            }
            
            // En el torneo americano, pareja_1_set_1 corresponde al primer grupo (menor ID)
            // y pareja_2_set_1 corresponde al segundo grupo (mayor ID)
            $puntosPareja1 = $partido->pareja_1_set_1 ?? 0;
            $puntosPareja2 = $partido->pareja_2_set_1 ?? 0;
            
            // Solo procesar si hay resultado (al menos un punto)
            if ($puntosPareja1 > 0 || $puntosPareja2 > 0) {
                // Determinar ganador
                if ($puntosPareja1 > $puntosPareja2) {
                    $parejas[$key1]['partidos_ganados']++;
                    $parejas[$key1]['puntos_ganados'] += $puntosPareja1;
                    $parejas[$key2]['partidos_perdidos']++;
                    $parejas[$key2]['puntos_ganados'] += $puntosPareja2;
                    
                    // Guardar resultado del partido directo
                    $parejas[$key1]['partidos_directos'][$key2] = ['ganado' => true, 'puntos' => $puntosPareja1 . '-' . $puntosPareja2];
                    $parejas[$key2]['partidos_directos'][$key1] = ['ganado' => false, 'puntos' => $puntosPareja2 . '-' . $puntosPareja1];
                } else if ($puntosPareja2 > $puntosPareja1) {
                    $parejas[$key2]['partidos_ganados']++;
                    $parejas[$key2]['puntos_ganados'] += $puntosPareja2;
                    $parejas[$key1]['partidos_perdidos']++;
                    $parejas[$key1]['puntos_ganados'] += $puntosPareja1;
                    
                    // Guardar resultado del partido directo
                    $parejas[$key2]['partidos_directos'][$key1] = ['ganado' => true, 'puntos' => $puntosPareja2 . '-' . $puntosPareja1];
                    $parejas[$key1]['partidos_directos'][$key2] = ['ganado' => false, 'puntos' => $puntosPareja1 . '-' . $puntosPareja2];
                }
            }
        }
        
        // Agregar keys a cada pareja para poder comparar partidos directos
        foreach ($parejas as $key => $pareja) {
            $parejas[$key]['key'] = $key;
        }
        
        // Convertir a array y ordenar por posición
        $posiciones = array_values($parejas);
        
        // Función de comparación con todos los criterios de desempate
        usort($posiciones, function($a, $b) {
            // 1. Primero por PARTIDOS GANADOS
            if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                return $b['partidos_ganados'] - $a['partidos_ganados'];
            }
            
            // 2. Si tienen los mismos partidos ganados, por PUNTOS GANADOS (games)
            if ($a['puntos_ganados'] != $b['puntos_ganados']) {
                return $b['puntos_ganados'] - $a['puntos_ganados'];
            }
            
            // 3. Si siguen empatando, por PARTIDO DIRECTO
            $keyA = $a['key'];
            $keyB = $b['key'];
            
            if (isset($a['partidos_directos'][$keyB])) {
                if ($a['partidos_directos'][$keyB]['ganado']) {
                    return -1; // A gana el partido directo
                } else {
                    return 1; // B gana el partido directo
                }
            }
            
            // 4. Si no hay partido directo o está empatado, mantener orden
            return 0;
        });
        
        return response()->json(['success' => true, 'posiciones' => $posiciones]);
    }

    public function adminTorneoResultados(Request $request) {
        $torneoId = $request->torneo_id;
        
        $torneo = DB::table('torneos')                                                                
                        ->where('torneos.id', $torneoId)                                
                        ->where('torneos.activo', 1)                                
                        ->first(); 
        
        if (!$torneo) {
            return redirect()->route('admintorneos')->with('error', 'Torneo no encontrado');
        }
        
        $jugadores = DB::table('jugadores')                                                                                        
                        ->where('jugadores.activo', 1)                                
                        ->get();
        
        // Obtener todos los grupos con sus partidos
        $grupos = DB::table('grupos')
                        ->join('partidos', 'grupos.partido_id', '=', 'partidos.id')
                        ->where('grupos.torneo_id', $torneoId)
                        ->select(
                            'grupos.id as grupo_id',
                            'grupos.torneo_id',
                            'grupos.zona',
                            'grupos.fecha',
                            'grupos.horario',
                            'grupos.jugador_1',
                            'grupos.jugador_2',
                            'grupos.partido_id',
                            'partidos.id as partido_id_full',
                            'partidos.pareja_1_set_1',
                            'partidos.pareja_1_set_1_tie_break',
                            'partidos.pareja_2_set_1',
                            'partidos.pareja_2_set_1_tie_break',
                            'partidos.pareja_1_set_2',
                            'partidos.pareja_1_set_2_tie_break',
                            'partidos.pareja_2_set_2',
                            'partidos.pareja_2_set_2_tie_break',
                            'partidos.pareja_1_set_3',
                            'partidos.pareja_1_set_3_tie_break',
                            'partidos.pareja_2_set_3',
                            'partidos.pareja_2_set_3_tie_break',
                            'partidos.pareja_1_set_super_tie_break',
                            'partidos.pareja_2_set_super_tie_break'
                        )
                        ->orderBy('grupos.zona')
                        ->orderBy('grupos.partido_id')
                        ->orderBy('grupos.id')
                        ->get();
        
        // Agrupar por zona y luego por partido único
        $partidosPorZona = [];
        foreach ($grupos as $grupo) {
            $zona = $grupo->zona;
            $partidoId = $grupo->partido_id;
            
            if (!isset($partidosPorZona[$zona])) {
                $partidosPorZona[$zona] = [];
            }
            
            // Agrupar por partido_id único
            if (!isset($partidosPorZona[$zona][$partidoId])) {
                $partidosPorZona[$zona][$partidoId] = [
                    'partido_id' => $partidoId,
                    'pareja_1' => null,
                    'pareja_2' => null,
                    'fecha' => $grupo->fecha,
                    'horario' => $grupo->horario,
                    'resultados' => $grupo
                ];
            }
            
            // Asignar parejas (cada partido tiene 2 grupos con las dos parejas)
            if (!$partidosPorZona[$zona][$partidoId]['pareja_1']) {
                $partidosPorZona[$zona][$partidoId]['pareja_1'] = [
                    'jugador_1' => $grupo->jugador_1,
                    'jugador_2' => $grupo->jugador_2
                ];
            } else {
                $partidosPorZona[$zona][$partidoId]['pareja_2'] = [
                    'jugador_1' => $grupo->jugador_1,
                    'jugador_2' => $grupo->jugador_2
                ];
            }
        }
        
        return View('bahia_padel.admin.torneo.resultados_torneo')
                    ->with('jugadores', $jugadores)
                    ->with('torneo', $torneo)
                    ->with('partidosPorZona', $partidosPorZona); 
    }

    public function guardarResultadoPartido(Request $request) {
        $partidoId = $request->partido_id;
        
        $partido = Partido::find($partidoId);
        
        if (!$partido) {
            return response()->json(['success' => false, 'message' => 'Partido no encontrado']);
        }
        
        // Actualizar resultados del partido
        if ($request->has('pareja_1_set_1')) {
            $partido->pareja_1_set_1 = $request->pareja_1_set_1 ?? 0;
        }
        if ($request->has('pareja_1_set_1_tie_break')) {
            $partido->pareja_1_set_1_tie_break = $request->pareja_1_set_1_tie_break ?? 0;
        }
        if ($request->has('pareja_2_set_1')) {
            $partido->pareja_2_set_1 = $request->pareja_2_set_1 ?? 0;
        }
        if ($request->has('pareja_2_set_1_tie_break')) {
            $partido->pareja_2_set_1_tie_break = $request->pareja_2_set_1_tie_break ?? 0;
        }
        
        if ($request->has('pareja_1_set_2')) {
            $partido->pareja_1_set_2 = $request->pareja_1_set_2 ?? 0;
        }
        if ($request->has('pareja_1_set_2_tie_break')) {
            $partido->pareja_1_set_2_tie_break = $request->pareja_1_set_2_tie_break ?? 0;
        }
        if ($request->has('pareja_2_set_2')) {
            $partido->pareja_2_set_2 = $request->pareja_2_set_2 ?? 0;
        }
        if ($request->has('pareja_2_set_2_tie_break')) {
            $partido->pareja_2_set_2_tie_break = $request->pareja_2_set_2_tie_break ?? 0;
        }
        
        if ($request->has('pareja_1_set_3')) {
            $partido->pareja_1_set_3 = $request->pareja_1_set_3 ?? 0;
        }
        if ($request->has('pareja_1_set_3_tie_break')) {
            $partido->pareja_1_set_3_tie_break = $request->pareja_1_set_3_tie_break ?? 0;
        }
        if ($request->has('pareja_2_set_3')) {
            $partido->pareja_2_set_3 = $request->pareja_2_set_3 ?? 0;
        }
        if ($request->has('pareja_2_set_3_tie_break')) {
            $partido->pareja_2_set_3_tie_break = $request->pareja_2_set_3_tie_break ?? 0;
        }
        
        if ($request->has('pareja_1_set_super_tie_break')) {
            $partido->pareja_1_set_super_tie_break = $request->pareja_1_set_super_tie_break ?? 0;
        }
        if ($request->has('pareja_2_set_super_tie_break')) {
            $partido->pareja_2_set_super_tie_break = $request->pareja_2_set_super_tie_break ?? 0;
        }
        
        $partido->save();
        
        return response()->json(['success' => true, 'partido' => $partido]);
    }

    public function verificarPartidosCompletos(Request $request) {
        $torneoId = $request->torneo_id;
        $zona = $request->zona;
        
        // Obtener todos los partidos únicos de la zona
        $partidos = DB::table('grupos')
                        ->join('partidos', 'grupos.partido_id', '=', 'partidos.id')
                        ->where('grupos.torneo_id', $torneoId)
                        ->where('grupos.zona', $zona)
                        ->select('grupos.partido_id', 'partidos.*')
                        ->distinct()
                        ->get();
        
        $totalPartidos = $partidos->count();
        $partidosCompletos = 0;
        
        foreach ($partidos as $partido) {
            // Un partido está completo si tiene al menos un set con resultado > 0
            $tieneResultado = ($partido->pareja_1_set_1 > 0 || $partido->pareja_2_set_1 > 0) ||
                             ($partido->pareja_1_set_2 > 0 || $partido->pareja_2_set_2 > 0) ||
                             ($partido->pareja_1_set_super_tie_break > 0 || $partido->pareja_2_set_super_tie_break > 0);
            
            if ($tieneResultado) {
                $partidosCompletos++;
            }
        }
        
        return response()->json([
            'success' => true,
            'total_partidos' => $totalPartidos,
            'partidos_completos' => $partidosCompletos,
            'todos_completos' => $totalPartidos > 0 && $partidosCompletos == $totalPartidos
        ]);
    }

    public function calcularPosicionesZona(Request $request) {
        $torneoId = $request->torneo_id;
        $zona = $request->zona;
        
        // Obtener todos los partidos únicos de la zona
        $partidos = DB::table('grupos')
                        ->join('partidos', 'grupos.partido_id', '=', 'partidos.id')
                        ->where('grupos.torneo_id', $torneoId)
                        ->where('grupos.zona', $zona)
                        ->select(
                            'grupos.partido_id',
                            'partidos.pareja_1_set_1',
                            'partidos.pareja_2_set_1',
                            'partidos.pareja_1_set_2',
                            'partidos.pareja_2_set_2',
                            'partidos.pareja_1_set_3',
                            'partidos.pareja_2_set_3',
                            'partidos.pareja_1_set_super_tie_break',
                            'partidos.pareja_2_set_super_tie_break'
                        )
                        ->distinct()
                        ->get();
        
        // Obtener las parejas de la zona
        $grupos = DB::table('grupos')
                        ->where('grupos.torneo_id', $torneoId)
                        ->where('grupos.zona', $zona)
                        ->select('grupos.jugador_1', 'grupos.jugador_2', 'grupos.partido_id')
                        ->get();
        
        // Agrupar por pareja
        $parejas = [];
        foreach ($grupos as $grupo) {
            $key = $grupo->jugador_1 . '_' . $grupo->jugador_2;
            if (!isset($parejas[$key])) {
                $parejas[$key] = [
                    'jugador_1' => $grupo->jugador_1,
                    'jugador_2' => $grupo->jugador_2,
                    'partidos_jugados' => 0,
                    'partidos_ganados' => 0,
                    'partidos_perdidos' => 0,
                    'puntos' => 0,
                    'sets_ganados' => 0,
                    'sets_perdidos' => 0,
                    'juegos_ganados' => 0,
                    'juegos_perdidos' => 0,
                    'partidos_directos' => [] // Para almacenar resultados de partidos directos
                ];
            }
        }
        
        // Procesar cada partido
        foreach ($partidos as $partido) {
            // Encontrar las dos parejas que juegan este partido
            $pareja1 = null;
            $pareja2 = null;
            
            foreach ($grupos as $grupo) {
                if ($grupo->partido_id == $partido->partido_id) {
                    $key = $grupo->jugador_1 . '_' . $grupo->jugador_2;
                    if (!$pareja1) {
                        $pareja1 = $key;
                    } else if ($key != $pareja1) {
                        $pareja2 = $key;
                        break;
                    }
                }
            }
            
            if ($pareja1 && $pareja2) {
                $parejas[$pareja1]['partidos_jugados']++;
                $parejas[$pareja2]['partidos_jugados']++;
                
                // Calcular sets ganados
                $setsGanadosP1 = 0;
                $setsGanadosP2 = 0;
                $ganoPorSuperTB = false;
                
                // Contar sets ganados
                if ($partido->pareja_1_set_1 > $partido->pareja_2_set_1) $setsGanadosP1++;
                else if ($partido->pareja_2_set_1 > $partido->pareja_1_set_1) $setsGanadosP2++;
                
                if ($partido->pareja_1_set_2 > $partido->pareja_2_set_2) $setsGanadosP1++;
                else if ($partido->pareja_2_set_2 > $partido->pareja_1_set_2) $setsGanadosP2++;
                
                // Si hay super tie break, ese determina el tercer set
                if ($partido->pareja_1_set_super_tie_break > 0 || $partido->pareja_2_set_super_tie_break > 0) {
                    $ganoPorSuperTB = true;
                    if ($partido->pareja_1_set_super_tie_break > $partido->pareja_2_set_super_tie_break) {
                        $setsGanadosP1 = 2; // Gana por super TB (2-1)
                        $setsGanadosP2 = 1;
                    } else if ($partido->pareja_2_set_super_tie_break > $partido->pareja_1_set_super_tie_break) {
                        $setsGanadosP1 = 1;
                        $setsGanadosP2 = 2; // Gana por super TB (2-1)
                    }
                }
                
                // Calcular juegos (games) ganados y perdidos
                $juegosGanadosP1 = $partido->pareja_1_set_1 + $partido->pareja_1_set_2;
                $juegosGanadosP2 = $partido->pareja_2_set_1 + $partido->pareja_2_set_2;
                
                // Si hay super tie break, no se cuentan juegos del super TB (solo sets)
                
                // Actualizar estadísticas de sets
                $parejas[$pareja1]['sets_ganados'] += $setsGanadosP1;
                $parejas[$pareja1]['sets_perdidos'] += $setsGanadosP2;
                $parejas[$pareja2]['sets_ganados'] += $setsGanadosP2;
                $parejas[$pareja2]['sets_perdidos'] += $setsGanadosP1;
                
                // Actualizar estadísticas de juegos
                $parejas[$pareja1]['juegos_ganados'] += $juegosGanadosP1;
                $parejas[$pareja1]['juegos_perdidos'] += $juegosGanadosP2;
                $parejas[$pareja2]['juegos_ganados'] += $juegosGanadosP2;
                $parejas[$pareja2]['juegos_perdidos'] += $juegosGanadosP1;
                
                // Determinar ganador y asignar puntos
                if ($setsGanadosP1 > $setsGanadosP2) {
                    // Pareja 1 gana
                    $parejas[$pareja1]['partidos_ganados']++;
                    $parejas[$pareja2]['partidos_perdidos']++;
                    
                    // Asignar puntos: 2-0 = 2 puntos ganador, 0 perdedor | 2-1 = 2 puntos ganador, 1 perdedor
                    if ($setsGanadosP1 == 2 && $setsGanadosP2 == 0) {
                        $parejas[$pareja1]['puntos'] += 2;
                        $parejas[$pareja2]['puntos'] += 0;
                    } else if ($setsGanadosP1 == 2 && $setsGanadosP2 == 1) {
                        $parejas[$pareja1]['puntos'] += 2;
                        $parejas[$pareja2]['puntos'] += 1;
                    }
                    
                    // Guardar resultado del partido directo
                    $parejas[$pareja1]['partidos_directos'][$pareja2] = ['ganado' => true, 'sets' => $setsGanadosP1 . '-' . $setsGanadosP2];
                    $parejas[$pareja2]['partidos_directos'][$pareja1] = ['ganado' => false, 'sets' => $setsGanadosP2 . '-' . $setsGanadosP1];
                    
                } else if ($setsGanadosP2 > $setsGanadosP1) {
                    // Pareja 2 gana
                    $parejas[$pareja2]['partidos_ganados']++;
                    $parejas[$pareja1]['partidos_perdidos']++;
                    
                    // Asignar puntos
                    if ($setsGanadosP2 == 2 && $setsGanadosP1 == 0) {
                        $parejas[$pareja2]['puntos'] += 2;
                        $parejas[$pareja1]['puntos'] += 0;
                    } else if ($setsGanadosP2 == 2 && $setsGanadosP1 == 1) {
                        $parejas[$pareja2]['puntos'] += 2;
                        $parejas[$pareja1]['puntos'] += 1;
                    }
                    
                    // Guardar resultado del partido directo
                    $parejas[$pareja2]['partidos_directos'][$pareja1] = ['ganado' => true, 'sets' => $setsGanadosP2 . '-' . $setsGanadosP1];
                    $parejas[$pareja1]['partidos_directos'][$pareja2] = ['ganado' => false, 'sets' => $setsGanadosP1 . '-' . $setsGanadosP2];
                }
            }
        }
        
        // Agregar keys a cada pareja para poder comparar partidos directos
        foreach ($parejas as $key => $pareja) {
            $parejas[$key]['key'] = $key;
        }
        
        // Convertir a array y ordenar por posición
        $posiciones = array_values($parejas);
        
        // Función de comparación con todos los criterios de desempate
        usort($posiciones, function($a, $b) {
            // 1. Primero por PUNTOS (no partidos ganados)
            if ($a['puntos'] != $b['puntos']) {
                return $b['puntos'] - $a['puntos'];
            }
            
            // 2. Si tienen los mismos puntos, aplicar desempates
            $keyA = $a['key'];
            $keyB = $b['key'];
            
            // 2.1. Partido Directo
            if (isset($a['partidos_directos'][$keyB])) {
                if ($a['partidos_directos'][$keyB]['ganado']) {
                    return -1; // A gana el partido directo
                } else {
                    return 1; // B gana el partido directo
                }
            }
            
            // 2.2. Diferencia de Juegos
            $diffJuegosA = $a['juegos_ganados'] - $a['juegos_perdidos'];
            $diffJuegosB = $b['juegos_ganados'] - $b['juegos_perdidos'];
            if ($diffJuegosA != $diffJuegosB) {
                return $diffJuegosB - $diffJuegosA;
            }
            
            // 2.3. Diferencia de Sets
            $diffSetsA = $a['sets_ganados'] - $a['sets_perdidos'];
            $diffSetsB = $b['sets_ganados'] - $b['sets_perdidos'];
            if ($diffSetsA != $diffSetsB) {
                return $diffSetsB - $diffSetsA;
            }
            
            // 2.4. Mayor Número de Juegos Ganados
            if ($a['juegos_ganados'] != $b['juegos_ganados']) {
                return $b['juegos_ganados'] - $a['juegos_ganados'];
            }
            
            // 2.5. Si todo está igual, mantener orden (equivalente a sorteo)
            return 0;
        });
        
        return response()->json(['success' => true, 'posiciones' => $posiciones]);
    }

    public function adminTorneoAmericanoCruces(Request $request) {
        $torneoId = $request->torneo_id;
        
        $torneo = DB::table('torneos')
                        ->where('torneos.id', $torneoId)
                        ->where('torneos.activo', 1)
                        ->first();
        
        if (!$torneo) {
            return redirect()->route('admintorneos')->with('error', 'Torneo no encontrado');
        }
        
        // Obtener información de los jugadores
        $jugadores = DB::table('jugadores')
                        ->where('jugadores.activo', 1)
                        ->get()
                        ->toArray();
        
        // PRIMERO: Obtener todos los partidos eliminatorios existentes directamente de la base de datos
        $cruces = [];
        $resultadosGuardados = [];
        
        // Obtener todos los grupos eliminatorios con sus partidos
        $gruposEliminatorios = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->whereIn('zona', ['cuartos final', 'semifinal', 'final'])
            ->whereNotNull('partido_id')
            ->orderBy('zona')
            ->orderBy('partido_id')
            ->orderBy('id')
            ->get();
        
        // Agrupar por partido_id
        $partidosAgrupados = [];
        foreach ($gruposEliminatorios as $grupo) {
            $partidoId = $grupo->partido_id;
            if (!isset($partidosAgrupados[$partidoId])) {
                $partidosAgrupados[$partidoId] = [
                    'zona' => $grupo->zona,
                    'partido_id' => $partidoId,
                    'grupos' => []
                ];
            }
            $partidosAgrupados[$partidoId]['grupos'][] = $grupo;
        }
        
        // Obtener los datos de los partidos
        $partidosIds = array_keys($partidosAgrupados);
        $partidos = [];
        if (count($partidosIds) > 0) {
            $partidos = DB::table('partidos')
                ->whereIn('id', $partidosIds)
                ->get()
                ->keyBy('id');
        }
        
        // Construir cruces desde los partidos existentes
        $crucesPorRonda = [
            'cuartos' => [],
            'semifinales' => [],
            'final' => []
        ];
        
        foreach ($partidosAgrupados as $partidoId => $datosPartido) {
            if (count($datosPartido['grupos']) >= 2) {
                $g1 = $datosPartido['grupos'][0];
                $g2 = $datosPartido['grupos'][1];
                $partido = $partidos[$partidoId] ?? null;
                
                // Determinar la ronda según la zona
                $ronda = 'cuartos';
                if ($datosPartido['zona'] === 'semifinal') {
                    $ronda = 'semifinales';
                } else if ($datosPartido['zona'] === 'final') {
                    $ronda = 'final';
                }
                
                // Crear el cruce
                $cruce = [
                    'id' => $ronda . '_' . $partidoId,
                    'pareja_1' => [
                        'jugador_1' => $g1->jugador_1,
                        'jugador_2' => $g1->jugador_2,
                        'zona' => null,
                        'posicion' => null
                    ],
                    'pareja_2' => [
                        'jugador_1' => $g2->jugador_1,
                        'jugador_2' => $g2->jugador_2,
                        'zona' => null,
                        'posicion' => null
                    ],
                    'ronda' => $ronda
                ];
                
                $crucesPorRonda[$ronda][] = $cruce;
                $cruces[] = $cruce;
                
                // Guardar resultado si existe
                if ($partido && ($partido->pareja_1_set_1 > 0 || $partido->pareja_2_set_1 > 0)) {
                    $resultadosGuardados[] = [
                        'partido_id' => $partidoId,
                        'cruce_id' => $cruce['id'], // Agregar el ID del cruce para facilitar la búsqueda en la vista
                        'ronda' => $ronda,
                        'pareja_1_jugador_1' => $g1->jugador_1,
                        'pareja_1_jugador_2' => $g1->jugador_2,
                        'pareja_2_jugador_1' => $g2->jugador_1,
                        'pareja_2_jugador_2' => $g2->jugador_2,
                        'pareja_1_set_1' => $partido->pareja_1_set_1 ?? 0,
                        'pareja_2_set_1' => $partido->pareja_2_set_1 ?? 0,
                    ];
                }
            }
        }
        
        // Calcular posiciones de cada zona para mostrar en la vista (necesario para clasificados)
        $grupos = DB::table('grupos')
                        ->where('grupos.torneo_id', $torneoId)
                        ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final'])
                        ->orderBy('grupos.zona')
                        ->orderBy('grupos.id')
                        ->get();
        
        // Calcular posiciones de cada zona y clasificados (siempre necesario para la vista)
        $posicionesPorZona = [];
        $zonas = $grupos->pluck('zona')->unique()->sort()->values();
        
        foreach ($zonas as $zona) {
            // Obtener todas las parejas de la zona
            $gruposZona = $grupos->where('zona', $zona)->filter(function($grupo) {
                return $grupo->jugador_1 !== null && $grupo->jugador_2 !== null;
            });
            
            // Agrupar por pareja (jugador_1 y jugador_2)
            $parejas = [];
            foreach ($gruposZona as $grupo) {
                $key = $grupo->jugador_1 . '_' . $grupo->jugador_2;
                if (!isset($parejas[$key])) {
                    $parejas[$key] = [
                        'jugador_1' => $grupo->jugador_1,
                        'jugador_2' => $grupo->jugador_2,
                        'partidos_ganados' => 0,
                        'partidos_perdidos' => 0,
                        'puntos_ganados' => 0,
                        'partidos_directos' => []
                    ];
                }
            }
            
            // Obtener todos los partidos de la zona
            $partidosIds = $gruposZona->pluck('partido_id')->unique()->filter();
            $partidos = DB::table('partidos')
                            ->whereIn('id', $partidosIds)
                            ->get();
            
            // Obtener grupos asociados a cada partido
            $gruposPorPartido = [];
            foreach ($gruposZona as $grupo) {
                if ($grupo->partido_id) {
                    if (!isset($gruposPorPartido[$grupo->partido_id])) {
                        $gruposPorPartido[$grupo->partido_id] = [];
                    }
                    $gruposPorPartido[$grupo->partido_id][] = $grupo;
                }
            }
            
            // Procesar cada partido
            foreach ($partidos as $partido) {
                if (!isset($gruposPorPartido[$partido->id]) || count($gruposPorPartido[$partido->id]) < 2) {
                    continue;
                }
                
                $gruposPartido = collect($gruposPorPartido[$partido->id])->sortBy('id')->values()->all();
                $pareja1Grupo = $gruposPartido[0];
                $pareja2Grupo = $gruposPartido[1];
                
                $key1 = $pareja1Grupo->jugador_1 . '_' . $pareja1Grupo->jugador_2;
                $key2 = $pareja2Grupo->jugador_1 . '_' . $pareja2Grupo->jugador_2;
                
                if (!isset($parejas[$key1]) || !isset($parejas[$key2])) {
                    continue;
                }
                
                $puntosPareja1 = $partido->pareja_1_set_1 ?? 0;
                $puntosPareja2 = $partido->pareja_2_set_1 ?? 0;
                
                if ($puntosPareja1 > 0 || $puntosPareja2 > 0) {
                    if ($puntosPareja1 > $puntosPareja2) {
                        $parejas[$key1]['partidos_ganados']++;
                        $parejas[$key1]['puntos_ganados'] += $puntosPareja1;
                        $parejas[$key2]['partidos_perdidos']++;
                        $parejas[$key2]['puntos_ganados'] += $puntosPareja2;
                        $parejas[$key1]['partidos_directos'][$key2] = ['ganado' => true];
                        $parejas[$key2]['partidos_directos'][$key1] = ['ganado' => false];
                    } else if ($puntosPareja2 > $puntosPareja1) {
                        $parejas[$key2]['partidos_ganados']++;
                        $parejas[$key2]['puntos_ganados'] += $puntosPareja2;
                        $parejas[$key1]['partidos_perdidos']++;
                        $parejas[$key1]['puntos_ganados'] += $puntosPareja1;
                        $parejas[$key2]['partidos_directos'][$key1] = ['ganado' => true];
                        $parejas[$key1]['partidos_directos'][$key2] = ['ganado' => false];
                    }
                }
            }
            
            // Agregar keys y ordenar
            foreach ($parejas as $key => $pareja) {
                $parejas[$key]['key'] = $key;
            }
            
            $posiciones = array_values($parejas);
            usort($posiciones, function($a, $b) {
                if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                    return $b['partidos_ganados'] - $a['partidos_ganados'];
                }
                if ($a['puntos_ganados'] != $b['puntos_ganados']) {
                    return $b['puntos_ganados'] - $a['puntos_ganados'];
                }
                $keyA = $a['key'];
                $keyB = $b['key'];
                if (isset($a['partidos_directos'][$keyB])) {
                    return $a['partidos_directos'][$keyB]['ganado'] ? -1 : 1;
                }
                return 0;
            });
            
            $posicionesPorZona[$zona] = $posiciones;
        }
        
        // Calcular clasificados para pasarlos a la vista (siempre necesario)
        $clasificados = [];
        $zonasArray = $zonas->toArray();
        
        // Clasificar los primeros de cada grupo
        foreach ($zonasArray as $zona) {
            if (isset($posicionesPorZona[$zona]) && count($posicionesPorZona[$zona]) > 0) {
                $clasificados[] = [
                    'zona' => $zona,
                    'posicion' => 1,
                    'jugador_1' => $posicionesPorZona[$zona][0]['jugador_1'],
                    'jugador_2' => $posicionesPorZona[$zona][0]['jugador_2'],
                    'partidos_ganados' => $posicionesPorZona[$zona][0]['partidos_ganados'],
                    'puntos_ganados' => $posicionesPorZona[$zona][0]['puntos_ganados']
                ];
            }
        }
        
        // Obtener segundos y terceros por zona (necesario para completar clasificados)
        $segundosPorZona = [];
        $tercerosPorZona = [];
        foreach ($zonasArray as $zona) {
            if (isset($posicionesPorZona[$zona]) && count($posicionesPorZona[$zona]) > 1) {
                $segundosPorZona[$zona] = [
                    'zona' => $zona,
                    'posicion' => 2,
                    'jugador_1' => $posicionesPorZona[$zona][1]['jugador_1'],
                    'jugador_2' => $posicionesPorZona[$zona][1]['jugador_2'],
                    'partidos_ganados' => $posicionesPorZona[$zona][1]['partidos_ganados'],
                    'puntos_ganados' => $posicionesPorZona[$zona][1]['puntos_ganados']
                ];
            }
            if (isset($posicionesPorZona[$zona]) && count($posicionesPorZona[$zona]) > 2) {
                $tercerosPorZona[$zona] = [
                    'zona' => $zona,
                    'posicion' => 3,
                    'jugador_1' => $posicionesPorZona[$zona][2]['jugador_1'],
                    'jugador_2' => $posicionesPorZona[$zona][2]['jugador_2'],
                    'partidos_ganados' => $posicionesPorZona[$zona][2]['partidos_ganados'],
                    'puntos_ganados' => $posicionesPorZona[$zona][2]['puntos_ganados']
                ];
            }
        }
        
        // Completar clasificados según el formato del torneo
        $zonasOrdenadasArray = $zonasArray;
        sort($zonasOrdenadasArray);
        if (count($zonasOrdenadasArray) == 3) {
            // 3 zonas: agregar A2, B2, C2 y 2 mejores terceros
            foreach ($zonasOrdenadasArray as $zona) {
                if (isset($segundosPorZona[$zona])) {
                    $clasificados[] = $segundosPorZona[$zona];
                }
            }
            $terceros = [];
            foreach ($tercerosPorZona as $tercero) {
                $terceros[] = $tercero;
            }
            usort($terceros, function($a, $b) {
                if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                    return $b['partidos_ganados'] - $a['partidos_ganados'];
                }
                return $b['puntos_ganados'] - $a['puntos_ganados'];
            });
            for ($i = 0; $i < min(2, count($terceros)); $i++) {
                $clasificados[] = $terceros[$i];
            }
        } else {
            // Lógica estándar para otros casos
            $segundos = array_values($segundosPorZona);
            usort($segundos, function($a, $b) {
                if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                    return $b['partidos_ganados'] - $a['partidos_ganados'];
                }
                return $b['puntos_ganados'] - $a['puntos_ganados'];
            });
            $necesarios = 8 - count($clasificados);
            for ($i = 0; $i < min($necesarios, count($segundos)); $i++) {
                $clasificados[] = $segundos[$i];
            }
            if (count($clasificados) < 8) {
                $terceros = array_values($tercerosPorZona);
                usort($terceros, function($a, $b) {
                    if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                        return $b['partidos_ganados'] - $a['partidos_ganados'];
                    }
                    return $b['puntos_ganados'] - $a['puntos_ganados'];
                });
                $necesarios = 8 - count($clasificados);
                for ($i = 0; $i < min($necesarios, count($terceros)); $i++) {
                    $clasificados[] = $terceros[$i];
                }
            }
        }
        
        // Si no hay cruces de cuartos en la base de datos, generarlos desde los clasificados
        if (count($crucesPorRonda['cuartos']) == 0) {
            // Armar los cruces según las reglas estándar
            $primerosPorZonaFinal = [];
            $segundosPorZonaFinal = [];
            $tercerosFinal = [];
            
            foreach ($clasificados as $clasificado) {
                if ($clasificado['posicion'] == 1) {
                    $primerosPorZonaFinal[$clasificado['zona']] = $clasificado;
                } else if ($clasificado['posicion'] == 2) {
                    $segundosPorZonaFinal[$clasificado['zona']] = $clasificado;
                } else if ($clasificado['posicion'] == 3) {
                    $tercerosFinal[] = $clasificado;
                }
            }
            
            usort($tercerosFinal, function($a, $b) {
                if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                    return $b['partidos_ganados'] - $a['partidos_ganados'];
                }
                return $b['puntos_ganados'] - $a['puntos_ganados'];
            });
            
            $crucesCuartos = [];
            $totalClasificados = count($clasificados);
            $zonasOrdenadasFinal = array_keys($primerosPorZonaFinal);
            sort($zonasOrdenadasFinal);
            
            // Caso especial: 6 clasificados
            if ($totalClasificados == 6) {
                $primeros = [];
                $resto = [];
                
                foreach ($clasificados as $clasificado) {
                    if ($clasificado['posicion'] == 1) {
                        $primeros[] = $clasificado;
                    } else {
                        $resto[] = $clasificado;
                    }
                }
                
                $segundosPorZona = [];
                $tercerosPorZona = [];
                
                foreach ($resto as $pareja) {
                    if ($pareja['posicion'] == 2) {
                        $segundosPorZona[$pareja['zona']] = $pareja;
                    } else if ($pareja['posicion'] == 3) {
                        $tercerosPorZona[$pareja['zona']] = $pareja;
                    }
                }
                
                $zonasArray = array_keys($segundosPorZona + $tercerosPorZona);
                sort($zonasArray);
                
                if (count($zonasArray) >= 2) {
                    $zona1 = $zonasArray[0];
                    $zona2 = $zonasArray[1];
                    
                    if (isset($segundosPorZona[$zona1]) && isset($tercerosPorZona[$zona2])) {
                        $crucesCuartos[] = [
                            'pareja_1' => $segundosPorZona[$zona1],
                            'pareja_2' => $tercerosPorZona[$zona2],
                            'ronda' => 'cuartos'
                        ];
                    }
                    
                    if (isset($segundosPorZona[$zona2]) && isset($tercerosPorZona[$zona1])) {
                        $crucesCuartos[] = [
                            'pareja_1' => $segundosPorZona[$zona2],
                            'pareja_2' => $tercerosPorZona[$zona1],
                            'ronda' => 'cuartos'
                        ];
                    }
                } else {
                    if (count($resto) >= 2) {
                        for ($i = 0; $i < count($resto) - 1; $i += 2) {
                            if (isset($resto[$i + 1])) {
                                $crucesCuartos[] = [
                                    'pareja_1' => $resto[$i],
                                    'pareja_2' => $resto[$i + 1],
                                    'ronda' => 'cuartos'
                                ];
                            }
                        }
                    }
                }
            } else if ($totalClasificados == 8 && count($zonasOrdenadasFinal) == 3) {
                $zonaA = $zonasOrdenadasFinal[0];
                $zonaB = $zonasOrdenadasFinal[1];
                $zonaC = $zonasOrdenadasFinal[2];
                
                if (isset($primerosPorZonaFinal[$zonaA]) && count($tercerosFinal) > 0) {
                    $crucesCuartos[] = [
                        'pareja_1' => $primerosPorZonaFinal[$zonaA],
                        'pareja_2' => $tercerosFinal[0],
                        'ronda' => 'cuartos'
                    ];
                }
                
                if (isset($primerosPorZonaFinal[$zonaB]) && count($tercerosFinal) > 1) {
                    $crucesCuartos[] = [
                        'pareja_1' => $primerosPorZonaFinal[$zonaB],
                        'pareja_2' => $tercerosFinal[1],
                        'ronda' => 'cuartos'
                    ];
                }
                
                if (isset($primerosPorZonaFinal[$zonaC]) && isset($segundosPorZonaFinal[$zonaA])) {
                    $crucesCuartos[] = [
                        'pareja_1' => $primerosPorZonaFinal[$zonaC],
                        'pareja_2' => $segundosPorZonaFinal[$zonaA],
                        'ronda' => 'cuartos'
                    ];
                }
                
                if (isset($segundosPorZonaFinal[$zonaB]) && isset($segundosPorZonaFinal[$zonaC])) {
                    $crucesCuartos[] = [
                        'pareja_1' => $segundosPorZonaFinal[$zonaB],
                        'pareja_2' => $segundosPorZonaFinal[$zonaC],
                        'ronda' => 'cuartos'
                    ];
                }
            } else {
                $primeros = [];
                $resto = [];
                
                foreach ($clasificados as $clasificado) {
                    if ($clasificado['posicion'] == 1) {
                        $primeros[] = $clasificado;
                    } else {
                        $resto[] = $clasificado;
                    }
                }
                
                $primerosUsados = [];
                $restoUsados = [];
                
                $mitad = ceil(count($primeros) / 2);
                $primerosSuperior = array_slice($primeros, 0, $mitad);
                $primerosInferior = array_slice($primeros, $mitad);
                
                foreach ($primerosSuperior as $primero) {
                    $encontrado = false;
                    foreach ($resto as $index => $r) {
                        if (!in_array($index, $restoUsados) && $r['zona'] != $primero['zona']) {
                            $crucesCuartos[] = [
                                'pareja_1' => $primero,
                                'pareja_2' => $r,
                                'ronda' => 'cuartos'
                            ];
                            $restoUsados[] = $index;
                            $encontrado = true;
                            break;
                        }
                    }
                    if (!$encontrado && count($resto) > 0) {
                        $index = 0;
                        while (in_array($index, $restoUsados) && $index < count($resto)) {
                            $index++;
                        }
                        if ($index < count($resto)) {
                            $crucesCuartos[] = [
                                'pareja_1' => $primero,
                                'pareja_2' => $resto[$index],
                                'ronda' => 'cuartos'
                            ];
                            $restoUsados[] = $index;
                        }
                    }
                }
                
                foreach ($primerosInferior as $primero) {
                    $encontrado = false;
                    foreach ($resto as $index => $r) {
                        if (!in_array($index, $restoUsados) && $r['zona'] != $primero['zona']) {
                            $crucesCuartos[] = [
                                'pareja_1' => $primero,
                                'pareja_2' => $r,
                                'ronda' => 'cuartos'
                            ];
                            $restoUsados[] = $index;
                            $encontrado = true;
                            break;
                        }
                    }
                    if (!$encontrado && count($resto) > 0) {
                        $index = 0;
                        while (in_array($index, $restoUsados) && $index < count($resto)) {
                            $index++;
                        }
                        if ($index < count($resto)) {
                            $crucesCuartos[] = [
                                'pareja_1' => $primero,
                                'pareja_2' => $resto[$index],
                                'ronda' => 'cuartos'
                            ];
                            $restoUsados[] = $index;
                        }
                    }
                }
                
                $restantes = [];
                foreach ($resto as $index => $r) {
                    if (!in_array($index, $restoUsados)) {
                        $restantes[] = $r;
                    }
                }
                if (count($restantes) >= 2) {
                    for ($i = 0; $i < count($restantes) - 1; $i += 2) {
                        $crucesCuartos[] = [
                            'pareja_1' => $restantes[$i],
                            'pareja_2' => $restantes[$i + 1],
                            'ronda' => 'cuartos'
                        ];
                    }
                }
            }
            
            // Agregar los cruces de cuartos generados a los cruces existentes
            $cruces = array_merge($crucesPorRonda['cuartos'], $crucesCuartos, $crucesPorRonda['semifinales'], $crucesPorRonda['final']);
        } else {
            // Si ya hay cruces de cuartos en la base de datos, usar todos los cruces existentes
            $cruces = array_merge($crucesPorRonda['cuartos'], $crucesPorRonda['semifinales'], $crucesPorRonda['final']);
        }
        
        // Separar primeros para pasarlos a la vista (necesario para el caso de 6 clasificados)
        $primerosClasificados = [];
        foreach ($clasificados as $clasificado) {
            if ($clasificado['posicion'] == 1) {
                $primerosClasificados[] = $clasificado;
            }
        }
        
        return View('bahia_padel.admin.torneo.cruces_americano')
                    ->with('torneo', $torneo)
                    ->with('jugadores', $jugadores)
                    ->with('clasificados', $clasificados)
                    ->with('cruces', $cruces)
                    ->with('posicionesPorZona', $posicionesPorZona)
                    ->with('resultadosGuardados', $resultadosGuardados)
                    ->with('primerosClasificados', $primerosClasificados)
                    ->with('totalClasificados', count($clasificados));
    }

    public function guardarResultadoCruceAmericano(Request $request) {
        $torneoId = $request->torneo_id;
        $ronda = $request->ronda; // 'cuartos', 'semifinales', 'final'
        $pareja1Set1 = $request->pareja_1_set_1 ?? 0;
        $pareja2Set1 = $request->pareja_2_set_1 ?? 0;
        $pareja1Jugador1 = $request->pareja_1_jugador_1 ?? null;
        $pareja1Jugador2 = $request->pareja_1_jugador_2 ?? null;
        $pareja2Jugador1 = $request->pareja_2_jugador_1 ?? null;
        $pareja2Jugador2 = $request->pareja_2_jugador_2 ?? null;
        
        // Mapear ronda a nombre de zona
        $zonaRonda = '';
        if ($ronda === 'cuartos') {
            $zonaRonda = 'cuartos final';
        } else if ($ronda === 'semifinales') {
            $zonaRonda = 'semifinal';
        } else if ($ronda === 'final') {
            $zonaRonda = 'final';
        }
        
        // Buscar si ya existe un partido eliminatorio con estas parejas
        // Buscar grupos que tengan una de las parejas y verificar si el otro grupo del mismo partido tiene la otra pareja
        $grupo1Encontrado = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', $zonaRonda)
            ->whereNotNull('partido_id')
            ->where(function($q) use ($pareja1Jugador1, $pareja1Jugador2, $pareja2Jugador1, $pareja2Jugador2) {
                $q->where(function($q2) use ($pareja1Jugador1, $pareja1Jugador2) {
                    $q2->where('jugador_1', $pareja1Jugador1)
                       ->where('jugador_2', $pareja1Jugador2);
                })
                ->orWhere(function($q2) use ($pareja2Jugador1, $pareja2Jugador2) {
                    $q2->where('jugador_1', $pareja2Jugador1)
                       ->where('jugador_2', $pareja2Jugador2);
                });
            })
            ->first();
        
        $partido = null;
        
        if ($grupo1Encontrado) {
            // Buscar el otro grupo del mismo partido
            $grupo2Encontrado = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', $zonaRonda)
                ->where('partido_id', $grupo1Encontrado->partido_id)
                ->where('id', '!=', $grupo1Encontrado->id)
                ->first();
            
            if ($grupo2Encontrado) {
                // Verificar que el segundo grupo tenga la otra pareja
                $tienePareja1 = ($grupo1Encontrado->jugador_1 == $pareja1Jugador1 && $grupo1Encontrado->jugador_2 == $pareja1Jugador2) ||
                                ($grupo2Encontrado->jugador_1 == $pareja1Jugador1 && $grupo2Encontrado->jugador_2 == $pareja1Jugador2);
                $tienePareja2 = ($grupo1Encontrado->jugador_1 == $pareja2Jugador1 && $grupo1Encontrado->jugador_2 == $pareja2Jugador2) ||
                                ($grupo2Encontrado->jugador_1 == $pareja2Jugador1 && $grupo2Encontrado->jugador_2 == $pareja2Jugador2);
                
                if ($tienePareja1 && $tienePareja2) {
                    $partido = Partido::find($grupo1Encontrado->partido_id);
                }
            }
        }
        
        // Si no existe, crear nuevo partido y grupos
        if (!$partido) {
            $partido = $this->crearPartido();
            
            // Crear grupo para pareja 1
            $grupo1 = new Grupo;
            $grupo1->torneo_id = $torneoId;
            $grupo1->zona = $zonaRonda;
            $grupo1->fecha = '2000-01-01';
            $grupo1->horario = '00:00';
            $grupo1->jugador_1 = $pareja1Jugador1;
            $grupo1->jugador_2 = $pareja1Jugador2;
            $grupo1->partido_id = $partido->id;
            $grupo1->save();
            
            // Crear grupo para pareja 2
            $grupo2 = new Grupo;
            $grupo2->torneo_id = $torneoId;
            $grupo2->zona = $zonaRonda;
            $grupo2->fecha = '2000-01-01';
            $grupo2->horario = '00:00';
            $grupo2->jugador_1 = $pareja2Jugador1;
            $grupo2->jugador_2 = $pareja2Jugador2;
            $grupo2->partido_id = $partido->id;
            $grupo2->save();
        }
        
        // Obtener los grupos asociados a este partido para identificar el orden
        $grupos = DB::table('grupos')
                    ->where('partido_id', $partido->id)
                    ->orderBy('id')
                    ->get();
        
        // Guardar resultado según el orden de los grupos
        if ($grupos->count() >= 2) {
            $g1 = $grupos[0];
            $g2 = $grupos[1];
            
            // Verificar qué pareja corresponde a cada grupo
            if ($g1->jugador_1 == $pareja1Jugador1 && $g1->jugador_2 == $pareja1Jugador2) {
                $partido->pareja_1_set_1 = $pareja1Set1;
                $partido->pareja_2_set_1 = $pareja2Set1;
            } else {
                $partido->pareja_1_set_1 = $pareja2Set1;
                $partido->pareja_2_set_1 = $pareja1Set1;
            }
        } else {
            $partido->pareja_1_set_1 = $pareja1Set1;
            $partido->pareja_2_set_1 = $pareja2Set1;
        }
        
        $partido->save();
        
        // Si se guardó un resultado de cuartos, verificar si se pueden crear las semifinales automáticamente
        if ($ronda === 'cuartos') {
            $this->crearSemifinalesSiEsNecesario($torneoId);
        }
        
        // Si se guardó un resultado de semifinales, verificar si se puede crear la final automáticamente
        if ($ronda === 'semifinales') {
            $this->crearFinalSiEsNecesario($torneoId);
        }
        
        return response()->json([
            'success' => true, 
            'partido' => $partido, 
            'partido_id' => $partido->id
        ]);
    }
    
    /**
     * Crea las semifinales automáticamente cuando se completan los cuartos necesarios
     */
    private function crearSemifinalesSiEsNecesario($torneoId) {
        // Obtener información de clasificados para determinar el formato
        // Usar la misma lógica que en adminTorneoAmericanoCruces
        $grupos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->whereNotIn('zona', ['cuartos final', 'semifinal', 'final'])
            ->whereNotNull('partido_id')
            ->get();
        
        // Calcular posiciones de cada zona (simplificado)
        $posicionesPorZona = [];
        $zonas = $grupos->pluck('zona')->unique()->sort()->values();
        
        foreach ($zonas as $zona) {
            $gruposZona = $grupos->where('zona', $zona)->filter(function($g) {
                return $g->jugador_1 !== null && $g->jugador_2 !== null;
            });
            
            $parejas = [];
            foreach ($gruposZona as $g) {
                $key = $g->jugador_1 . '_' . $g->jugador_2;
                if (!isset($parejas[$key])) {
                    $parejas[$key] = [
                        'jugador_1' => $g->jugador_1,
                        'jugador_2' => $g->jugador_2,
                        'partidos_ganados' => 0,
                        'puntos_ganados' => 0
                    ];
                }
            }
            
            // Obtener partidos de la zona para calcular estadísticas
            $partidosIds = $gruposZona->pluck('partido_id')->unique()->filter();
            $partidos = DB::table('partidos')->whereIn('id', $partidosIds)->get();
            
            foreach ($partidos as $partido) {
                $gruposPartido = $gruposZona->where('partido_id', $partido->id)->sortBy('id')->values();
                if ($gruposPartido->count() >= 2) {
                    $g1 = $gruposPartido[0];
                    $g2 = $gruposPartido[1];
                    $key1 = $g1->jugador_1 . '_' . $g1->jugador_2;
                    $key2 = $g2->jugador_1 . '_' . $g2->jugador_2;
                    
                    if (isset($parejas[$key1]) && isset($parejas[$key2])) {
                        $p1 = $partido->pareja_1_set_1 ?? 0;
                        $p2 = $partido->pareja_2_set_1 ?? 0;
                        if ($p1 > 0 || $p2 > 0) {
                            if ($p1 > $p2) {
                                $parejas[$key1]['partidos_ganados']++;
                                $parejas[$key1]['puntos_ganados'] += $p1;
                            } else if ($p2 > $p1) {
                                $parejas[$key2]['partidos_ganados']++;
                                $parejas[$key2]['puntos_ganados'] += $p2;
                            }
                        }
                    }
                }
            }
            
            // Ordenar por partidos ganados y puntos
            $posiciones = array_values($parejas);
            usort($posiciones, function($a, $b) {
                if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                    return $b['partidos_ganados'] - $a['partidos_ganados'];
                }
                return $b['puntos_ganados'] - $a['puntos_ganados'];
            });
            
            $posicionesPorZona[$zona] = $posiciones;
        }
        
        // Obtener clasificados (misma lógica que adminTorneoAmericanoCruces)
        $clasificados = [];
        $zonasArray = $zonas->toArray();
        
        foreach ($zonasArray as $zona) {
            if (isset($posicionesPorZona[$zona]) && count($posicionesPorZona[$zona]) > 0) {
                $clasificados[] = [
                    'zona' => $zona,
                    'posicion' => 1,
                    'jugador_1' => $posicionesPorZona[$zona][0]['jugador_1'],
                    'jugador_2' => $posicionesPorZona[$zona][0]['jugador_2']
                ];
            }
        }
        
        // Obtener segundos y terceros según el formato
        $segundosPorZona = [];
        $tercerosPorZona = [];
        foreach ($zonasArray as $zona) {
            if (isset($posicionesPorZona[$zona]) && count($posicionesPorZona[$zona]) > 1) {
                $segundosPorZona[$zona] = [
                    'zona' => $zona,
                    'posicion' => 2,
                    'jugador_1' => $posicionesPorZona[$zona][1]['jugador_1'],
                    'jugador_2' => $posicionesPorZona[$zona][1]['jugador_2']
                ];
            }
            if (isset($posicionesPorZona[$zona]) && count($posicionesPorZona[$zona]) > 2) {
                $tercerosPorZona[$zona] = [
                    'zona' => $zona,
                    'posicion' => 3,
                    'jugador_1' => $posicionesPorZona[$zona][2]['jugador_1'],
                    'jugador_2' => $posicionesPorZona[$zona][2]['jugador_2']
                ];
            }
        }
        
        // Determinar formato (6 o 8 clasificados)
        $zonasOrdenadasArray = $zonasArray;
        sort($zonasOrdenadasArray);
        $totalClasificados = count($clasificados);
        
        if (count($zonasOrdenadasArray) == 3) {
            // 3 zonas: agregar A2, B2, C2 y 2 mejores terceros
            foreach ($zonasOrdenadasArray as $zona) {
                if (isset($segundosPorZona[$zona])) {
                    $clasificados[] = $segundosPorZona[$zona];
                }
            }
            $terceros = array_values($tercerosPorZona);
            usort($terceros, function($a, $b) {
                return 0; // Simplificado
            });
            for ($i = 0; $i < min(2, count($terceros)); $i++) {
                $clasificados[] = $terceros[$i];
            }
            $totalClasificados = count($clasificados);
        }
        
        // Obtener todos los partidos de cuartos con resultados
        $partidosCuartos = DB::table('partidos')
            ->join('grupos', 'partidos.id', '=', 'grupos.partido_id')
            ->where('grupos.torneo_id', $torneoId)
            ->where('grupos.zona', 'cuartos final')
            ->where(function($query) {
                $query->where('partidos.pareja_1_set_1', '>', 0)
                      ->orWhere('partidos.pareja_2_set_1', '>', 0);
            })
            ->select('partidos.id', 'partidos.pareja_1_set_1', 'partidos.pareja_2_set_1')
            ->distinct()
            ->orderBy('partidos.id')
            ->get();
        
        // Obtener los grupos de cada partido para identificar las parejas y determinar ganadores
        $ganadoresCuartos = [];
        $clasificadosKeys = [];
        foreach ($clasificados as $c) {
            $clasificadosKeys[] = $c['jugador_1'] . '_' . $c['jugador_2'];
        }
        
        foreach ($partidosCuartos as $index => $partido) {
            $gruposPartido = DB::table('grupos')
                ->where('partido_id', $partido->id)
                ->where('torneo_id', $torneoId)
                ->where('zona', 'cuartos final')
                ->orderBy('id')
                ->get();
            
            if ($gruposPartido->count() >= 2) {
                $g1 = $gruposPartido[0];
                $g2 = $gruposPartido[1];
                $key1 = $g1->jugador_1 . '_' . $g1->jugador_2;
                $key2 = $g2->jugador_1 . '_' . $g2->jugador_2;
                
                // Verificar si ambas parejas están en clasificados (es un cuarto de final)
                $pareja1EnClasificados = in_array($key1, $clasificadosKeys);
                $pareja2EnClasificados = in_array($key2, $clasificadosKeys);
                
                if ($pareja1EnClasificados && $pareja2EnClasificados) {
                    // Determinar ganador
                    $ganador = ($partido->pareja_1_set_1 > $partido->pareja_2_set_1) ? 
                        ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2] : 
                        ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                    
                    // Identificar índice del cuarto (basado en el orden de creación)
                    $ganadoresCuartos[$index] = $ganador;
                }
            }
        }
        
        // Para 6 clasificados: SF1 = Primero 1 vs Ganador QF1, SF2 = Primero 2 vs Ganador QF2
        if ($totalClasificados == 6) {
            $primeros = [];
            foreach ($clasificados as $c) {
                if ($c['posicion'] == 1) {
                    $primeros[] = [
                        'jugador_1' => $c['jugador_1'],
                        'jugador_2' => $c['jugador_2']
                    ];
                }
            }
            
            // Crear SF1: Primero 1 vs Ganador QF1
            if (isset($ganadoresCuartos[0]) && count($primeros) > 0) {
                $this->crearPartidoEliminatorio($torneoId, $primeros[0], $ganadoresCuartos[0], 'semifinales');
            }
            
            // Crear SF2: Primero 2 vs Ganador QF2
            if (isset($ganadoresCuartos[1]) && count($primeros) > 1) {
                $this->crearPartidoEliminatorio($torneoId, $primeros[1], $ganadoresCuartos[1], 'semifinales');
            }
        } 
        // Para 8 clasificados: SF1 = Ganador QF1 vs Ganador QF3, SF2 = Ganador QF2 vs Ganador QF4
        else if ($totalClasificados == 8) {
            // Crear SF1: Ganador QF1 vs Ganador QF3
            if (isset($ganadoresCuartos[0]) && isset($ganadoresCuartos[2])) {
                $this->crearPartidoEliminatorio($torneoId, $ganadoresCuartos[0], $ganadoresCuartos[2], 'semifinales');
            }
            
            // Crear SF2: Ganador QF2 vs Ganador QF4
            if (isset($ganadoresCuartos[1]) && isset($ganadoresCuartos[3])) {
                $this->crearPartidoEliminatorio($torneoId, $ganadoresCuartos[1], $ganadoresCuartos[3], 'semifinales');
            }
        }
    }
    
    /**
     * Crea la final automáticamente cuando se completan las semifinales
     */
    private function crearFinalSiEsNecesario($torneoId) {
        // Obtener todos los partidos de semifinales con resultados
        $partidosSemifinales = DB::table('partidos')
            ->join('grupos', 'partidos.id', '=', 'grupos.partido_id')
            ->where('grupos.torneo_id', $torneoId)
            ->where('grupos.zona', 'semifinal')
            ->where(function($query) {
                $query->where('partidos.pareja_1_set_1', '>', 0)
                      ->orWhere('partidos.pareja_2_set_1', '>', 0);
            })
            ->select('partidos.id', 'partidos.pareja_1_set_1', 'partidos.pareja_2_set_1')
            ->distinct()
            ->orderBy('partidos.id')
            ->get();
        
        // Verificar si hay al menos 2 semifinales completas
        $ganadoresSemifinales = [];
        foreach ($partidosSemifinales as $index => $partido) {
            $gruposPartido = DB::table('grupos')
                ->where('partido_id', $partido->id)
                ->where('torneo_id', $torneoId)
                ->where('zona', 'semifinal')
                ->orderBy('id')
                ->get();
            
            if ($gruposPartido->count() >= 2) {
                $g1 = $gruposPartido[0];
                $g2 = $gruposPartido[1];
                
                // Determinar ganador
                $ganador = ($partido->pareja_1_set_1 > $partido->pareja_2_set_1) ? 
                    ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2] : 
                    ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                
                // Usar el índice del array para identificar la semifinal (0 o 1)
                $ganadoresSemifinales[$index] = $ganador;
            }
        }
        
        // Crear final si hay 2 ganadores de semifinales
        if (count($ganadoresSemifinales) >= 2 && isset($ganadoresSemifinales[0]) && isset($ganadoresSemifinales[1])) {
            $this->crearPartidoEliminatorio($torneoId, $ganadoresSemifinales[0], $ganadoresSemifinales[1], 'final');
        }
    }
    
    /**
     * Crea un partido eliminatorio (semifinal o final) en la base de datos
     */
    private function crearPartidoEliminatorio($torneoId, $pareja1, $pareja2, $ronda) {
        // Mapear ronda a nombre de zona
        $zonaRonda = '';
        if ($ronda === 'semifinales') {
            $zonaRonda = 'semifinal';
        } else if ($ronda === 'final') {
            $zonaRonda = 'final';
        }
        
        // Verificar si ya existe este partido
        $partidoExistente = DB::table('grupos as g1')
            ->join('grupos as g2', function($join) {
                $join->on('g1.partido_id', '=', 'g2.partido_id')
                     ->whereRaw('g1.id != g2.id')
                     ->whereNotNull('g1.partido_id')
                     ->whereNotNull('g2.partido_id');
            })
            ->where('g1.torneo_id', $torneoId)
            ->where('g1.zona', $zonaRonda)
            ->where('g2.torneo_id', $torneoId)
            ->where('g2.zona', $zonaRonda)
            ->where(function($query) use ($pareja1, $pareja2) {
                $query->where(function($q) use ($pareja1, $pareja2) {
                    $q->where('g1.jugador_1', $pareja1['jugador_1'])
                      ->where('g1.jugador_2', $pareja1['jugador_2'])
                      ->where('g2.jugador_1', $pareja2['jugador_1'])
                      ->where('g2.jugador_2', $pareja2['jugador_2']);
                })
                ->orWhere(function($q) use ($pareja1, $pareja2) {
                    $q->where('g1.jugador_1', $pareja2['jugador_1'])
                      ->where('g1.jugador_2', $pareja2['jugador_2'])
                      ->where('g2.jugador_1', $pareja1['jugador_1'])
                      ->where('g2.jugador_2', $pareja1['jugador_2']);
                });
            })
            ->select('g1.partido_id')
            ->first();
        
        // Si ya existe, no crear otro
        if ($partidoExistente) {
            return;
        }
        
        // Crear nuevo partido
        $partido = $this->crearPartido();
        
        // Crear grupo para pareja 1
        $grupo1 = new Grupo;
        $grupo1->torneo_id = $torneoId;
        $grupo1->zona = $zonaRonda;
        $grupo1->fecha = '2000-01-01';
        $grupo1->horario = '00:00';
        $grupo1->jugador_1 = $pareja1['jugador_1'];
        $grupo1->jugador_2 = $pareja1['jugador_2'];
        $grupo1->partido_id = $partido->id;
        $grupo1->save();
        
        // Crear grupo para pareja 2
        $grupo2 = new Grupo;
        $grupo2->torneo_id = $torneoId;
        $grupo2->zona = $zonaRonda;
        $grupo2->fecha = '2000-01-01';
        $grupo2->horario = '00:00';
        $grupo2->jugador_1 = $pareja2['jugador_1'];
        $grupo2->jugador_2 = $pareja2['jugador_2'];
        $grupo2->partido_id = $partido->id;
        $grupo2->save();
    }

}


