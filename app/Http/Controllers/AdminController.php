<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Jugadore;
use App\Torneo;
use App\Fecha;
use Image;
use App\TablaFechaPunto;

use Session;

class AdminController extends Controller
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
        $this->middleware('auth');
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

    function adminHome(){
        return View('padel.admin.home'); 
    }

    function adminJugador(){
        return View('padel.admin.jugador'); 
    }

    function adminTorneo(){
        return View('padel.admin.torneo'); 
    }

    function adminTablaGeneral() {
        $torneos = DB::table('torneos')->where('torneos.activo', 1)->get();

        return View('padel.admin.tabla_general')->with('torneos',$torneos);  
    }

    function adminFecha(){
        $torneos = DB::table('torneos')->where('torneos.activo', 1)->get();
        
        $nuevaFecha = DB::table('fechas')
                        ->where('fechas.torneo_id', $torneos[0]->id)                        
                        ->max('fechas.numero');

        $nuevaFecha++;

        $jugadores = DB::table('jugadores')                                                                
                        ->where('jugadores.activo', 1)
                        ->where('jugadores.nombre', '!=', 'Libre')                                 
                        ->orderby('jugadores.apellido')
                        ->get();        

        return View('padel.admin.fecha')
                ->with('jugadores',$jugadores)
                ->with('nuevaFecha',$nuevaFecha)
                ->with('torneos',$torneos); 
    }

    function onChangeTorneo(Request $request) {
        $torneoId = $request->torneo;

        $nuevaFecha = DB::table('fechas')
                        ->where('fechas.torneo_id', $torneoId)                        
                        ->max('fechas.numero');

        $nuevaFecha++;
        return response()->json(array('nuevaFecha'=>$nuevaFecha));
    } 

    function registrarTorneo(Request $request) {
        $id = $request->id_torneo;
        if($id == 0){
            $torneo = new Torneo;
            $torneo->categoria = 6;
            $torneo->fecha_inicio = '2025-01-01';
            $torneo->fecha_fin = '2025-01-03';
            $torneo->premio_1 = '1000';
            $torneo->premio_2 = '1000';
            $torneo->descripcion = '';
            $torneo->imagen = '';
            $torneo->tipo = '';
            $torneo->activo = 1;
            $torneo->estado = 1;
        } else {
            $torneo = Torneo::find($id);
        }
        
        $torneo->nombre = $request->nombre;
        $torneo->es_torneo_individual    = $request->tipo_torneo;        

        $torneo->save();

        return response()->json(array('torneo'=>$torneo));
    }
    
    function modalBuscarJugadorList(){            
        $jugadores = DB::table('jugadores')                                                                
                                ->where('jugadores.activo', 1)
                                ->where('jugadores.nombre', '!=', 'Libre')                                 
                                ->orderby('jugadores.apellido');        

        return datatables()->of($jugadores)
                           ->addIndexColumn()
                           ->addColumn('foto', function($row){                                   
                               $val = $row->id;                               
                               //$btn = "<p>'{{$val}}'</p>";                               
                               $btn = "<img src='" . htmlspecialchars($row->foto) . "' alt='Jugador' style='width: 60px; height: 60px;' />";
                               return $btn;
                            })
                           ->addColumn('posicion', function($row){                                   
                                $val = $row->posicion;                               
                                if($val == 0)
                                    $posicion = 'Drive';
                                if($val == 1)
                                    $posicion = 'Reves';
                                if($val == 2)
                                    $posicion = 'Drive|Reves';
                               $btn = "<p>{$posicion}</p>";   
                               return $btn;
                            })
                           ->addColumn('action', function($row){                                   
                               $val = $row->id;                               
                               $btn = "<button onclick=seleccionarJugador($val) class='rodri_button_aceptar_si'>></button>";                               
                               return $btn;
                            })
                            ->rawColumns(['foto','posicion','action'])
                            ->make(true);
    }

    function getJugador(Request $request) {        
        $jugador = Jugadore::find($request->id);
        return response()->json(array('jugador'=>$jugador));
    }

    function getJugadores(Request $request) {

        //$jugadoresAll = Jugadore::all();
        //return response()->json(array('jugadores'=>$jugadoresAll));


        // Obtener los IDs del request
        $jugadoresArray = $request->jugadoresIds;

        // Validar que sea un array
        if (!is_array($jugadoresArray) || empty($jugadoresArray)) {
            return response()->json(['error' => 'No se enviaron jugadores válidos'], 400);
        }
        foreach ($jugadoresArray as $jugadorId) {
            $jugador = DB::table('jugadores')->where('id', $jugadorId)->first();
            if ($jugador) {
                $jugadoresAll[] = $jugador; // Agregar el jugador al array
            }
        }

        // Retornar la lista de jugadores en formato JSON
        return response()->json(array('jugadores'=>$jugadoresAll));
    }

    function registrarJugador(Request $request) {
        $id = $request->id_jugador;
        if($id == 0){
            $jugador = new Jugadore;
            $jugador->activo = 1;
        } else {
            $jugador = Jugadore::find($id);
        }
        
        $jugador->nombre = $request->nombre;
        $jugador->apellido = $request->apellido;
        $jugador->telefono = $request->telefono;
        $jugador->posicion = $request->posicion;
        $jugador->foto = 'images/jugador_img.png';
        
        $jugador->save();

        return response()->json(array('jugador'=>$jugador));

    }

    function cargarImagenJugador(Request $request) {
        $jugadorId = $request->id_jugador;
        if($jugadorId != null){
            if($request->hasfile('image')) {
                try {
                    $image = $request->file('image');
                    $originalName = $image->getClientOriginalName();
                    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
                    $extension = $image->getClientOriginalExtension();
                    $name = time() . '_' . $safeName . '.' . $extension;
                    Storage::disk('public')->makeDirectory('images/jugadores');
                    $fullPath = Storage::disk('public')->path('images/jugadores/' . $name);
                    Image::make($image->getRealPath())->save($fullPath);
                    $path = 'storage/images/jugadores/' . $name;
                    \Log::info('Foto guardada en storage: ' . $path);
                } catch (\Exception $e) {
                    \Log::error('Error al procesar imagen en cargarImagenJugador: ' . $e->getMessage());
                    $path = 'images/jugador_img.png';
                }
            } else {
                $path = 'images/jugador_img.png';
            }
            
            $jugador = Jugadore::find($jugadorId);
            if ($jugador) {
                $jugador->foto = $path;
                $jugador->save();
                \Log::info('Foto actualizada en BD para jugador ' . $jugadorId . ': ' . $path);
            }
        }
    }

    function generarFecha(Request $request) {
        $calendario = $request->calendario; // Recibimos el calendario de partidos
        $jugadores = $request->jugadoresIds; // Array de jugadores
        $numeroFecha = $request->numFecha; // Número de fecha a generar
        $torneoId = $request->torneoId; // ID del torneo (opcional)

        // Generar array de parejas a partir de los jugadores
        $parejas = $this->generarArrayParejas($jugadores);

        // Reemplazar las letras (A, B, C, ...) por los jugadores correspondientes
        $partidos = $this->reemplazarLetrasPorIds($calendario, $parejas);

        $fechaInterna = 0;
        // Recorriendo las fechas y partidos
        foreach ($partidos as $index => $fecha) {
            //echo "Fecha " . ($index + 1) . ":\n"; // Mostrar el número de la fecha (índice + 1)
            $fechaInterna = $fechaInterna + 1;
            // Recorriendo los partidos dentro de cada fecha
            foreach ($fecha as $partido) {
                // Accediendo a los jugadores de cada partido                
                //echo "Jugador 11: " . $partido[0][0] . " - Jugador 12: " . $partido[0][1] . "\n";
                //echo "Jugador 21: " . $partido[1][0] . " - Jugador 22: " . $partido[1][1] . "\n";
                $fecha = new Fecha;
                $fecha->torneo_id = $torneoId;
                $fecha->numero = $numeroFecha;
                $fecha->partido_numero = $fechaInterna;
                $fecha->jugador_id_1 = $partido[0][0];
                $fecha->jugador_id_2 = $partido[0][1];
                $fecha->jugador_id_3 = $partido[1][0];
                $fecha->jugador_id_4 = $partido[1][1];
                $fecha->es_torneo_individual = 0;
                $fecha->resultado_set_1 = 0;
                $fecha->resultado_set_2 = 0;
                $fecha->resultado_set_3 = 0;
                $fecha->resultado_games_jugador_1 = 0;
                $fecha->resultado_games_jugador_2 = 0;
                $fecha->resultado_games_jugador_3 = 0;
                $fecha->resultado_games_jugador_4 = 0;
                $fecha->save();            
            }
        }

//        return redirect()->route('comenzar_fecha', ['torneo' => $torneoId]);
        return redirect()->route('comenzarfecha', ['torneo' => $torneoId]);        

        /*return response()->json([
            "torneo_id" => $torneoId,
            "fecha" => $numeroFecha,
            "partidos" => $partidos,
            "parejas" => $parejas
        ]); */
    }


    function generarArrayParejas($jugadoresIds) {
        $parejas = [];
        $letras = range('A', 'Z'); // Genera las letras de la A a la Z

        for ($i = 0, $index = 0; $i < count($jugadoresIds); $i += 2, $index++) {
            if (isset($jugadoresIds[$i + 1])) {
                $parejas[] = [
                    $letras[$index] => [$jugadoresIds[$i], $jugadoresIds[$i + 1]]
                ];
            }
        }
        
        return $parejas;
    }

    function reemplazarLetrasPorIds($calendario, $parejas) {
        // Convertir el array de parejas en un mapa de letras a IDs
        $letrasMap = [];
        foreach ($parejas as $pareja) {
            foreach ($pareja as $letra => $ids) {
                $letrasMap[$letra] = $ids;
            }
        }

        // Recorrer el calendario y reemplazar las letras por los IDs correspondientes
        foreach ($calendario as &$fecha) {
            foreach ($fecha as &$partido) {
                // Reemplazar cada letra con los jugadores correspondientes
                foreach ($partido as $key => $letra) {
                    if (isset($letrasMap[$letra])) {
                        // Reemplazar la letra por los IDs
                        $partido[$key] = $letrasMap[$letra];
                    }
                }
            }
        }

        return $calendario;
    }

    function comenzarFecha(Request $request) {
        $torneo_id = $request->torneo;
        $fechaSeleccionada = $request->fecha_id;

        $fechasAux = DB::table('fechas')
            ->where('fechas.torneo_id', $torneo_id)
            ->orderBy('fechas.id', 'desc') // Ordenar por ID de forma descendente (últimos primero)
            ->get();        

        if($fechaSeleccionada != null) {
            $numeroFecha = $fechaSeleccionada;
        } else {
            $numeroFecha = $fechasAux[0]->numero;
        }

        $fechas = DB::table('fechas')
            ->where('fechas.torneo_id', $torneo_id)
            ->where('fechas.numero', $numeroFecha)
            ->where('fechas.partido_numero', 1)
            ->orderBy('id') // Ordenar por ID de forma descendente (últimos primero)
            ->get();
            //return $fechas;

        return View('padel.admin.fecha_actual')
                ->with('fecha_numero',$numeroFecha)
                ->with('torneo_id',$torneo_id)
                ->with('fechas',$fechas); 

    }

    function getPartidoFecha(Request $request) {
        $torneoId = $request->torneoId;
        $fechaNumero = $request->fechaNumero;
        $partidoNumero = $request->partidoNumero;
                
        $partidos = DB::table('fechas')            
            ->where('fechas.torneo_id', $torneoId)
            ->where('fechas.numero', $fechaNumero)            
            ->where('fechas.partido_numero', $partidoNumero)                    
            ->get();

        $maxPartidoNumero = DB::table('fechas')
                        ->where('fechas.torneo_id', $torneoId)
                        ->where('fechas.numero', $fechaNumero)            
                        ->max('partido_numero');

        $jugadores = DB::table('jugadores')->get(); 

        return response()->json(array('partidos'=>$partidos, 'jugadores'=>$jugadores, 'maxPartidoNumero'=>$maxPartidoNumero));
    }

    function guardarPuntos(Request $request){
        $torneoId = $request->torneoId;
        $fechaNumero = $request->fechaNumero;
        $partidoNumero = $request->partidoNumero;
        $jugador_id = $request->jugador_id;
        $puntos = $request->puntos;
                
        $partidos = DB::table('fechas')            
            ->where('fechas.torneo_id', $torneoId)
            ->where('fechas.numero', $fechaNumero)            
            ->where('fechas.partido_numero', $partidoNumero)                    
            ->where('fechas.jugador_id_1', $jugador_id)                    
            ->get();
        if($partidos->count() > 0) {
            $partido = Fecha::find($partidos[0]->id);
            $partido->resultado_games_jugador_1 = $puntos;
            $partido->resultado_games_jugador_2 = $puntos;
            $partido->save();
        }

        $partidos = DB::table('fechas')            
            ->where('fechas.torneo_id', $torneoId)
            ->where('fechas.numero', $fechaNumero)            
            ->where('fechas.partido_numero', $partidoNumero)                    
            ->where('fechas.jugador_id_2', $jugador_id)                    
            ->get();
        if($partidos->count() > 0) {
            $partido = Fecha::find($partidos[0]->id);
            $partido->resultado_games_jugador_1 = $puntos;
            $partido->resultado_games_jugador_2 = $puntos;
            $partido->save();
        }

        $partidos = DB::table('fechas')            
            ->where('fechas.torneo_id', $torneoId)
            ->where('fechas.numero', $fechaNumero)            
            ->where('fechas.partido_numero', $partidoNumero)                    
            ->where('fechas.jugador_id_3', $jugador_id)                    
            ->get();
        if($partidos->count() > 0) {
            $partido = Fecha::find($partidos[0]->id);
            $partido->resultado_games_jugador_3 = $puntos;
            $partido->resultado_games_jugador_4 = $puntos;
            $partido->save();
        }

        $partidos = DB::table('fechas')            
            ->where('fechas.torneo_id', $torneoId)
            ->where('fechas.numero', $fechaNumero)            
            ->where('fechas.partido_numero', $partidoNumero)                    
            ->where('fechas.jugador_id_4', $jugador_id)                    
            ->get();
        if($partidos->count() > 0) {
            $partido = Fecha::find($partidos[0]->id);
            $partido->resultado_games_jugador_3 = $puntos;
            $partido->resultado_games_jugador_4 = $puntos;
            $partido->save();
        }

        return response()->json(array('response'=>1));
    }

    function calcularPosiciones(Request $request) {
        $torneoId = $request->torneoId;
        $fechaNumero = $request->fechaNumero;

        $jugadores = collect()
            ->merge(DB::table('fechas')
                ->where('fechas.torneo_id', $torneoId)
                ->where('fechas.numero', $fechaNumero)
                ->pluck('jugador_id_1'))
            ->merge(DB::table('fechas')
                ->where('fechas.torneo_id', $torneoId)
                ->where('fechas.numero', $fechaNumero)
                ->pluck('jugador_id_2'))
            ->merge(DB::table('fechas')
                ->where('fechas.torneo_id', $torneoId)
                ->where('fechas.numero', $fechaNumero)
                ->pluck('jugador_id_3'))
            ->merge(DB::table('fechas')
                ->where('fechas.torneo_id', $torneoId)
                ->where('fechas.numero', $fechaNumero)
                ->pluck('jugador_id_4'))
            ->unique()
            ->values(); // Reindexa el array
        $posicionesAux = array();
        foreach($jugadores as $jugador) {
            $jugadorInfo = Jugadore::find($jugador);
            $puntosJugador = DB::table('fechas')
                ->where('fechas.torneo_id', $torneoId)
                ->where('fechas.numero', $fechaNumero)
                ->where('fechas.jugador_id_1', $jugador)
                ->get();

            $totalPuntos1 = $puntosJugador->sum('resultado_games_jugador_1');

            $puntosJugador = DB::table('fechas')
                ->where('fechas.torneo_id', $torneoId)
                ->where('fechas.numero', $fechaNumero)
                ->where('fechas.jugador_id_2', $jugador)
                ->get();

            $totalPuntos2 = $puntosJugador->sum('resultado_games_jugador_1');

            $puntosJugador = DB::table('fechas')
                ->where('fechas.torneo_id', $torneoId)
                ->where('fechas.numero', $fechaNumero)
                ->where('fechas.jugador_id_3', $jugador)
                ->get();

            $totalPuntos3 = $puntosJugador->sum('resultado_games_jugador_3');

            $puntosJugador = DB::table('fechas')
                ->where('fechas.torneo_id', $torneoId)
                ->where('fechas.numero', $fechaNumero)
                ->where('fechas.jugador_id_4', $jugador)
                ->get();

            $totalPuntos4 = $puntosJugador->sum('resultado_games_jugador_3');
            $totalPuntos = $totalPuntos1 + $totalPuntos2 +$totalPuntos3 + $totalPuntos4;
            $nuevoJson = [
                'jugador_id' => $jugador,
                'nombre' => $jugadorInfo->nombre,
                'apellido' => $jugadorInfo->apellido,
                'imagen' => $jugadorInfo->foto,
                'puntos' => $totalPuntos,
            ];
            array_push($posicionesAux, $nuevoJson);
        }

        usort($posicionesAux, function($a, $b) {
            return $b['puntos'] - $a['puntos']; // Cambiar el orden de la resta para ordenar de mayor a menor
        });

        return response()->json(array('jugadores'=>$jugadores, 'posicionesAux'=>$posicionesAux));
    }

    function getLibres(Request $request) {
        $jugadoresLibres = DB::table('jugadores')                
                ->where('jugadores.nombre', '=', 'Libre') 
                ->get();

        return response()->json(array('jugadoresLibres'=>$jugadoresLibres));    
    }

function getTablaGeneral(Request $request) {
    $torneoId = $request->torneo_id;        

    // Obtenemos jugadores con sus datos completos
    $jugadores = DB::table('tabla_fecha_puntos')
            ->join('jugadores', 'jugadores.id', '=', 'tabla_fecha_puntos.jugador_id')
            ->select('tabla_fecha_puntos.jugador_id', 'jugadores.nombre', 'jugadores.apellido', 'jugadores.foto')
            ->where('tabla_fecha_puntos.torneo_id', $torneoId)
            ->distinct()
            ->get();

    $cantidadFechas = DB::table('fechas')
                        ->where('torneo_id', $torneoId)
                        ->max('numero');        
    
    $data = array();

    foreach ($jugadores as $jugador) {
        // Array con foto y nombre primero
        $jugadorPoints = array(
            'foto' => $jugador->foto,
            'nombre' => $jugador->nombre.', '.$jugador->apellido
        );
        
        $totalPuntos = 0;

        for ($fechaNumero = 1; $fechaNumero <= $cantidadFechas; $fechaNumero++) {
            $puntos = DB::table('tabla_fecha_puntos')
                        ->where('torneo_id', $torneoId)
                        ->where('jugador_id', $jugador->jugador_id)
                        ->where('fecha_numero', $fechaNumero)
                        ->value('puntos');
            
            $puntos = $puntos ?? 0;
            $jugadorPoints['fechas'][$fechaNumero] = $puntos;
            $totalPuntos += $puntos;
        }
        
        $jugadorPoints['total'] = $totalPuntos;
        $data[] = $jugadorPoints;
    }

    // Ordenar por total de puntos (descendente)
    usort($data, function($a, $b) {
        return $b['total'] - $a['total'];
    });

    return response()->json([
        'success' => true,
        'data' => $data,
        'total_fechas' => $cantidadFechas
    ]);    
}

    function getListadoFechasPrevias(Request $request) {
        $torneoId = $request->torneo_id;        
        $cantidadFechas = DB::table('fechas')
                        ->where('fechas.torneo_id', $torneoId) 
                        ->max('fechas.numero');

        $rutas = array_map(function ($i) use ($torneoId) {
            return route('ruta.fecha', [
                'torneo_id' => $torneoId,
                'fecha_id' => $i,
            ]);
        }, range(1, $cantidadFechas));


        return response()->json([
                'cantidadFechas' => $cantidadFechas,
                'rutas' => $rutas,
            ]); 
    }

    function getFecha($torneo_id, $fecha_id) {
        //return $torneo_id.' '.$fecha_id;        
        
        $fechasAux = DB::table('fechas')
            ->where('fechas.torneo_id', $torneo_id)
            ->orderBy('fechas.id', 'desc') // Ordenar por ID de forma descendente (últimos primero)
            ->get();        

        $numeroFecha = $fecha_id;

        $fechas = DB::table('fechas')
            ->where('fechas.torneo_id', $torneo_id)
            ->where('fechas.numero', $numeroFecha)
            ->where('fechas.partido_numero', 1)
            ->orderBy('id') // Ordenar por ID de forma descendente (últimos primero)
            ->get();
            //return $fechas;
        
        return View('padel.admin.fecha_actual')
                ->with('fecha_numero',$numeroFecha)
                ->with('torneo_id',$torneo_id)
                ->with('fechas',$fechas); 

    }

    function guardarPuntosFecha(Request $request) {
        $torneoId = $request->torneoId;        
        $fechaNumero = $request->fechaNumero;    
        $resultados = $request->resultados;        

        foreach ($resultados as $jugador) {
            $jugador_id = $jugador["jugador_id"];
            $puntos = $jugador["puntos"];
            $fechaPuntosAux = DB::table('tabla_fecha_puntos')
                                ->where('tabla_fecha_puntos.torneo_id', $torneoId)
                                ->where('tabla_fecha_puntos.fecha_numero', $fechaNumero)
                                ->where('tabla_fecha_puntos.jugador_id', $jugador_id)
                                ->get();
            if($fechaPuntosAux->count() > 0) {
                $fechaPuntos = TablaFechaPunto::find($fechaPuntosAux[0]->id);                
            } else {
                $fechaPuntos = new TablaFechaPunto;                
                $fechaPuntos->torneo_id = $torneoId;
                $fechaPuntos->fecha_numero = $fechaNumero;
                $fechaPuntos->jugador_id = $jugador_id;
            }
            $fechaPuntos->puntos = $puntos;
            $fechaPuntos->save();
        }

        return response()->json([
                'resultados' => $resultados,                
            ]); 
    }

    function getFechasPreviasJugadores(Request $request) {
        $torneoId = $request->torneo_id;

        $fechas = DB::table('fechas')
            ->where('fechas.torneo_id', $torneoId)
            ->where('fechas.partido_numero', 1)
            ->orderBy('fechas.numero')
            ->orderBy('fechas.partido_numero')
            ->get();

        $resultado = [];

        foreach ($fechas as $f) {
            $resultado[] = [
                'fecha_numero' => $f->numero,
                'partido_numero' => $f->partido_numero,
                'pareja_1' => [$f->jugador_id_1, $f->jugador_id_2],
                'pareja_2' => [$f->jugador_id_3, $f->jugador_id_4],
            ];
        }

        return response()->json(['data' => $resultado]);


        /*$torneoId = $request->torneo_id;
        $fechaPuntosAux = DB::table('fechas')
                                ->select('fechas.numero', 'fechas.jugador_id_1', 'fechas.jugador_id_2', 'fechas.jugador_id_3', 'fechas.jugador_id_4'),
                                ->where('fechas.torneo_id', $torneoId)                                                                
                                ->where('fechas.partido_numero', 1)
                                ->get();

        return response()->json([
                'fechas' => $fechas,                
            ]); */
    }
}

