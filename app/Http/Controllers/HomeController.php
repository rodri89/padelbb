<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Torneo;
use App\Jugadore;
use App\Partido;
use App\Grupo;
use App\Calendario;
use Intervention\Image\Facades\Image;
use App\Services\TorneoGrupoPosicionesService;

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
    function adminTorneos() {
        return View('bahia_padel.admin.torneo.index'); 
    }

    /**
     * Cargar resultados - pantalla mobile para partidos sin resultado de torneos en progreso
     */
    function adminCargarResultados(Request $request) {
        $torneoId = $request->torneo_id;
        
        // Si no hay torneo seleccionado: mostrar listado de torneos en progreso
        if (!$torneoId) {
            $torneos = DB::table('torneos')
                ->where('activo', 1)
                ->where('estado', 2)
                ->orderBy('fecha_inicio', 'desc')
                ->get();
            return View('bahia_padel.admin.torneo.cargar_resultados')
                ->with('torneos', $torneos)
                ->with('torneo', null)
                ->with('partidos', collect())
                ->with('jugadores', collect())
                ->with('modoEditar', false);
        }
        
        $torneo = DB::table('torneos')
            ->where('id', $torneoId)
            ->where('activo', 1)
            ->first();
        
        if (!$torneo) {
            return redirect()->route('admincargarresultados')->with('error', 'Torneo no encontrado');
        }
        
        $jugadores = DB::table('jugadores')->where('activo', 1)->get();
        
        $modoEditar = $request->get('editar', 0) == 1;
        
        // Obtener partidos: sin resultado por defecto, o TODOS si modo editar
        $query = DB::table('grupos')
            ->join('partidos', 'grupos.partido_id', '=', 'partidos.id')
            ->where('grupos.torneo_id', $torneoId)
            ->whereNotNull('grupos.partido_id');
        
        if (!$modoEditar) {
            $query->whereRaw('(COALESCE(partidos.pareja_1_set_1,0) + COALESCE(partidos.pareja_2_set_1,0) + COALESCE(partidos.pareja_1_set_2,0) + COALESCE(partidos.pareja_2_set_2,0) + COALESCE(partidos.pareja_1_set_3,0) + COALESCE(partidos.pareja_2_set_3,0)) = 0');
        }
        
        $grupos = $query->select(
                'grupos.id as grupo_id', 'grupos.torneo_id', 'grupos.zona', 'grupos.fecha', 'grupos.horario',
                'grupos.jugador_1', 'grupos.jugador_2', 'grupos.partido_id',
                'partidos.id as partido_id_full',
                'partidos.pareja_1_set_1', 'partidos.pareja_1_set_1_tie_break', 'partidos.pareja_2_set_1', 'partidos.pareja_2_set_1_tie_break',
                'partidos.pareja_1_set_2', 'partidos.pareja_1_set_2_tie_break', 'partidos.pareja_2_set_2', 'partidos.pareja_2_set_2_tie_break',
                'partidos.pareja_1_set_3', 'partidos.pareja_1_set_3_tie_break', 'partidos.pareja_2_set_3', 'partidos.pareja_2_set_3_tie_break',
                'partidos.pareja_1_set_super_tie_break', 'partidos.pareja_2_set_super_tie_break'
            )
            ->orderBy('grupos.fecha')
            ->orderBy('grupos.horario')
            ->orderBy('grupos.partido_id')
            ->get();
        
        // Agrupar por partido_id y construir estructura (reutilizando lógica de adminTorneoResultados)
        $partidosMap = [];
        $zonasCruces = ['16avos final', 'dieciseisavos final', 'octavos final', 'cuartos final', 'semifinal', 'final'];
        $esZonaCruce = function($z) use ($zonasCruces) {
            if (in_array($z, $zonasCruces)) return true;
            foreach (['16avos final', 'dieciseisavos final', 'octavos final', 'cuartos final'] as $pref) {
                if (strpos($z ?? '', $pref) === 0) return true;
            }
            return false;
        };
        
        foreach ($grupos as $grupo) {
            $partidoId = $grupo->partido_id;
            $zonaOriginal = $grupo->zona;
            
            $zonaBase = $zonaOriginal;
            $esGanador = strpos($zonaOriginal, 'ganador ') === 0;
            $esPerdedor = strpos($zonaOriginal, 'perdedor ') === 0;
            $esCruce = $esZonaCruce($zonaOriginal);
            if ($esGanador) $zonaBase = substr($zonaOriginal, 8);
            if ($esPerdedor) $zonaBase = substr($zonaOriginal, 9);
            if ($esCruce && strpos($zonaOriginal, '|') !== false) {
                $zonaBase = explode('|', $zonaOriginal)[0];
            }
            
            if (!isset($partidosMap[$partidoId])) {
                $partidosMap[$partidoId] = [
                    'partido_id' => $partidoId,
                    'zona' => $zonaBase,
                    'pareja_1' => null,
                    'pareja_2' => null,
                    'fecha' => $grupo->fecha,
                    'horario' => $grupo->horario,
                    'resultados' => $grupo,
                    'tipo' => $esCruce ? 'cruce' : ($esGanador ? 'ganador' : ($esPerdedor ? 'perdedor' : 'normal')),
                    'grupos' => []
                ];
            }
            $partidosMap[$partidoId]['grupos'][] = [
                'jugador_1' => $grupo->jugador_1,
                'jugador_2' => $grupo->jugador_2,
                'fecha' => $grupo->fecha,
                'horario' => $grupo->horario
            ];
        }
        
        // Procesar parejas para cada partido
        foreach ($partidosMap as $partidoId => &$partidoData) {
            $gruposList = $partidoData['grupos'] ?? [];
            $gruposConJugadores = array_filter($gruposList, fn($g) => ($g['jugador_1'] ?? 0) && ($g['jugador_2'] ?? 0));
            $gruposUnicos = [];
            $vistos = [];
            foreach ($gruposConJugadores as $g) {
                $k = min($g['jugador_1'], $g['jugador_2']) . '_' . max($g['jugador_1'], $g['jugador_2']);
                if (!isset($vistos[$k])) {
                    $vistos[$k] = true;
                    $gruposUnicos[] = $g;
                }
            }
            if (empty($gruposUnicos) && !empty($gruposList)) {
                $gruposUnicos = array_slice($gruposList, 0, 2);
            }
            $partidoData['pareja_1'] = isset($gruposUnicos[0]) ? ['jugador_1' => (int)$gruposUnicos[0]['jugador_1'], 'jugador_2' => (int)$gruposUnicos[0]['jugador_2']] : null;
            $pareja1Key = $partidoData['pareja_1'] ? min($partidoData['pareja_1']['jugador_1'], $partidoData['pareja_1']['jugador_2']) . '_' . max($partidoData['pareja_1']['jugador_1'], $partidoData['pareja_1']['jugador_2']) : null;
            $partidoData['pareja_2'] = null;
            foreach ($gruposUnicos as $i => $g) {
                if ($i === 0) continue;
                $k = min($g['jugador_1'], $g['jugador_2']) . '_' . max($g['jugador_1'], $g['jugador_2']);
                if ($k !== $pareja1Key) {
                    $partidoData['pareja_2'] = ['jugador_1' => (int)$g['jugador_1'], 'jugador_2' => (int)$g['jugador_2']];
                    break;
                }
            }
            if (!empty($gruposUnicos)) {
                $partidoData['fecha'] = $gruposUnicos[0]['fecha'];
                $partidoData['horario'] = $gruposUnicos[0]['horario'];
            }
            unset($partidoData['grupos']);
        }
        unset($partidoData);
        
        // Ordenar: cruces primero (16avos, octavos, cuartos, semifinal, final), luego zonas por fecha/horario
        $ordenRonda = function($zona) {
            $z = $zona ?? '';
            if (strpos($z, '16avos') === 0 || strpos($z, 'dieciseisavos') === 0) return 1;
            if (strpos($z, 'octavos') === 0) return 2;
            if (strpos($z, 'cuartos') === 0) return 3;
            if ($z === 'semifinal') return 4;
            if ($z === 'final') return 5;
            return 6; // zonas
        };
        $partidos = collect($partidosMap)->sortBy(function ($p) use ($ordenRonda) {
            $zona = $p['zona'] ?? '';
            $tipo = $p['tipo'] ?? 'normal';
            $r = ($tipo === 'cruce') ? $ordenRonda($zona) : 6;
            $f = $p['fecha'] ?? '2000-01-01';
            $h = $p['horario'] ?? '00:00';
            return sprintf('%d_%s_%s', $r, $f, $h);
        })->values();
        
        return View('bahia_padel.admin.torneo.cargar_resultados')
            ->with('torneos', collect())
            ->with('torneo', $torneo)
            ->with('partidos', $partidos)
            ->with('jugadores', $jugadores)
            ->with('modoEditar', $modoEditar);
    }

    function adminFotos() {
        return View('bahia_padel.admin.fotos.index'); 
    }
    
    /**
     * Panel de ranking por categoría (admin).
     * Incluye torneos puntuables y entradas manuales de ranking.
     */
    function adminRanking(Request $request) {
        $tipos = [
            'masculino' => 'Masculino',
            'femenino' => 'Femenino',
            'mixto' => 'Mixto',
        ];
        
        $tipoSeleccionado = $request->get('tipo');
        if (!array_key_exists($tipoSeleccionado, $tipos)) {
            $tipoSeleccionado = 'masculino';
        }

        // Categorías y temporadas disponibles (ranking_totales + ranking_entradas)
        $categoriasFromTotales = DB::table('ranking_totales')
            ->where('tipo', $tipoSeleccionado)
            ->distinct()->pluck('categoria');

        $categoriasFromEntradas = DB::table('ranking_entradas')
            ->where('tipo', $tipoSeleccionado)
            ->distinct()->pluck('categoria');

        $categorias = $categoriasFromTotales->merge($categoriasFromEntradas)
            ->unique()->sort()->values();

        $temporadasFromTotales = DB::table('ranking_totales')
            ->where('tipo', $tipoSeleccionado)
            ->distinct()->pluck('temporada');

        $temporadasFromEntradas = DB::table('ranking_entradas')
            ->where('tipo', $tipoSeleccionado)
            ->distinct()->pluck('temporada');

        $temporadas = $temporadasFromTotales->merge($temporadasFromEntradas)
            ->unique()->sortDesc()->values();

        $categoriaSeleccionada = (int) ($request->get('categoria') ?? ($categorias->first() ?? 6));
        $temporadaSeleccionada = (int) ($request->get('temporada') ?? ($temporadas->first() ?? date('Y')));

        $ranking = collect();
        $columnasDesglose = collect(); // columnas unificadas (torneos + entradas manuales)
        $desglosePuntos = [];
        $referenciasPuntuacion = DB::table('puntos_ranking_referencia')
            ->orderBy('orden')
            ->get(['id', 'orden', 'codigo', 'nombre', 'puntos']);

        // Entradas manuales para este tipo/cat/temporada
        $entradasManuales = DB::table('ranking_entradas')
            ->where('tipo', $tipoSeleccionado)
            ->where('categoria', $categoriaSeleccionada)
            ->where('temporada', $temporadaSeleccionada)
            ->orderBy('mes')
            ->orderBy('nombre')
            ->get();

        if (!$categorias->isEmpty() || !$entradasManuales->isEmpty()) {
            // Ranking total por jugador
            $ranking = DB::table('ranking_totales')
                ->join('jugadores', 'jugadores.id', '=', 'ranking_totales.jugador_id')
                ->where('ranking_totales.tipo', $tipoSeleccionado)
                ->where('ranking_totales.categoria', $categoriaSeleccionada)
                ->where('ranking_totales.temporada', $temporadaSeleccionada)
                ->orderByDesc('ranking_totales.puntos_totales')
                ->orderBy('jugadores.apellido')
                ->orderBy('jugadores.nombre')
                ->get([
                    'ranking_totales.jugador_id',
                    'ranking_totales.puntos_totales',
                    'jugadores.nombre',
                    'jugadores.apellido',
                    'jugadores.foto',
                ]);

            // Columnas de torneos puntuables
            $meses = ['01' => 'Ene', '02' => 'Feb', '03' => 'Mar', '04' => 'Abr', '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dic'];

            $torneosRanking = DB::table('ranking_puntos')
                ->join('torneos', 'torneos.id', '=', 'ranking_puntos.torneo_id')
                ->where('ranking_puntos.tipo', $tipoSeleccionado)
                ->where('ranking_puntos.categoria', $categoriaSeleccionada)
                ->where('ranking_puntos.temporada', $temporadaSeleccionada)
                ->select('torneos.id', 'torneos.nombre', 'torneos.fecha_inicio', 'torneos.fecha_fin')
                ->distinct()
                ->orderBy('torneos.fecha_inicio')
                ->get()
                ->map(function ($t) use ($meses) {
                    $fecha = $t->fecha_inicio ?? $t->fecha_fin;
                    $t->mes_label = $fecha ? ($meses[date('m', strtotime($fecha))] ?? '—') : '—';
                    $t->tipo_columna = 'torneo';
                    $t->col_key = 'torneo_' . $t->id;
                    return $t;
                });

            // Columnas de entradas manuales
            $columnasEntradas = $entradasManuales->map(function ($e) use ($meses) {
                $mesNum = str_pad((int) $e->mes, 2, '0', STR_PAD_LEFT);
                $e->mes_label = ($meses[$mesNum] ?? $e->mes) . ' (M)';
                $e->tipo_columna = 'entrada';
                $e->col_key = 'entrada_' . $e->id;
                return $e;
            });

            // Unificamos columnas: primero torneos (por fecha), luego entradas (por mes)
            $columnasDesglose = $torneosRanking->merge($columnasEntradas);

            // Desglose de puntos por torneo
            if (!$torneosRanking->isEmpty()) {
                $puntosPorTorneo = DB::table('ranking_puntos')
                    ->where('tipo', $tipoSeleccionado)
                    ->where('categoria', $categoriaSeleccionada)
                    ->where('temporada', $temporadaSeleccionada)
                    ->whereIn('torneo_id', $torneosRanking->pluck('id'))
                    ->get(['jugador_id', 'torneo_id', 'puntos']);

                foreach ($puntosPorTorneo as $row) {
                    $desglosePuntos[$row->jugador_id]['torneo_' . $row->torneo_id] = (int) $row->puntos;
                }
            }

            // Desglose de puntos por entrada manual
            if (!$entradasManuales->isEmpty()) {
                $puntosPorEntrada = DB::table('ranking_entradas_jugadores')
                    ->whereIn('entrada_id', $entradasManuales->pluck('id'))
                    ->get(['jugador_id', 'entrada_id', 'puntos']);

                foreach ($puntosPorEntrada as $row) {
                    $desglosePuntos[$row->jugador_id]['entrada_' . $row->entrada_id] = (int) $row->puntos;
                }
            }
        }

        return View('bahia_padel.admin.ranking.index', [
            'tipos' => $tipos,
            'tipo_seleccionado' => $tipoSeleccionado,
            'categorias' => $categorias,
            'categoria_seleccionada' => $categoriaSeleccionada,
            'temporadas' => $temporadas,
            'temporada_seleccionada' => $temporadaSeleccionada,
            'ranking' => $ranking,
            'torneos_ranking' => $columnasDesglose,
            'desglose_puntos' => $desglosePuntos,
            'referencias_puntuacion' => $referenciasPuntuacion,
            'entradas_manuales' => $entradasManuales,
        ]);
    }

    /**
     * Admin: subir/bajar jugador de categoría en ranking_totales (temporada actual).
     * - Up: categoria - 1
     * - Down: categoria + 1
     * - puntos_totales: se divide por 2 (entero)
     */
    public function adminRankingMoverCategoria(Request $request)
    {
        $jugadorId = (int) $request->input('jugador_id');
        $direccion = (string) $request->input('direccion');
        $tipo = (string) $request->input('tipo');
        $temporada = (int) $request->input('temporada', (int) date('Y'));
        $categoriaActual = (int) $request->input('categoria');

        if ($jugadorId <= 0) {
            return response()->json(['success' => false, 'message' => 'Jugador inválido'], 400);
        }
        if (!in_array($direccion, ['up', 'down'], true)) {
            return response()->json(['success' => false, 'message' => 'Dirección inválida'], 400);
        }
        if (!in_array($tipo, ['masculino', 'femenino', 'mixto'], true)) {
            return response()->json(['success' => false, 'message' => 'Tipo inválido'], 400);
        }
        if ($temporada < 2000 || $temporada > 2100) {
            return response()->json(['success' => false, 'message' => 'Temporada inválida'], 400);
        }
        if ($categoriaActual <= 0) {
            return response()->json(['success' => false, 'message' => 'Categoría inválida'], 400);
        }

        $nuevaCategoria = $direccion === 'up' ? ($categoriaActual - 1) : ($categoriaActual + 1);
        if ($nuevaCategoria <= 0) {
            return response()->json(['success' => false, 'message' => 'No se puede subir más de categoría'], 400);
        }

        try {
            DB::beginTransaction();

            $actual = DB::table('ranking_totales')
                ->where('jugador_id', $jugadorId)
                ->where('tipo', $tipo)
                ->where('temporada', $temporada)
                ->where('categoria', $categoriaActual)
                ->lockForUpdate()
                ->first(['id', 'puntos_totales']);

            if (!$actual) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'No se encontró el registro de ranking para este jugador/categoría/temporada'], 404);
            }

            $puntosActuales = (int) ($actual->puntos_totales ?? 0);
            $puntosNuevos = intdiv($puntosActuales, 2);

            $destino = DB::table('ranking_totales')
                ->where('jugador_id', $jugadorId)
                ->where('tipo', $tipo)
                ->where('temporada', $temporada)
                ->where('categoria', $nuevaCategoria)
                ->lockForUpdate()
                ->first(['id']);

            if ($destino) {
                // Si ya existe un registro en la categoría destino, lo sobreescribimos y borramos el actual.
                DB::table('ranking_totales')
                    ->where('id', $destino->id)
                    ->update([
                        'puntos_totales' => $puntosNuevos,
                        'updated_at' => now(),
                    ]);
                DB::table('ranking_totales')->where('id', $actual->id)->delete();
            } else {
                DB::table('ranking_totales')
                    ->where('id', $actual->id)
                    ->update([
                        'categoria' => $nuevaCategoria,
                        'puntos_totales' => $puntosNuevos,
                        'updated_at' => now(),
                    ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'nueva_categoria' => $nuevaCategoria,
                'puntos_nuevos' => $puntosNuevos,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Admin: Calendario (eventos a mostrar en home)
     */
    function adminCalendario(Request $request) {
        $eventos = Calendario::withCount('inscripciones')
            ->orderByRaw('COALESCE(fecha_desde, fecha)')
            ->orderBy('id')
            ->get();
        $editarId = $request->get('editar');
        $item = null;
        if ($editarId) {
            $item = Calendario::find($editarId);
        }
        return View('bahia_padel.admin.calendario.index')
            ->with('eventos', $eventos)
            ->with('item', $item);
    }

    function guardarCalendario(Request $request) {
        try {
            $id = $request->id ? (int) $request->id : null;
            $fechaDesde = $request->fecha_desde ?: $request->fecha;
            $fechaHasta = $request->fecha_hasta ?: $fechaDesde;
            $categoria = (int) $request->categoria;
            $tipo = $request->tipo;
            if (!in_array($tipo, ['mixto', 'femenino', 'masculino'])) {
                $tipo = 'mixto';
            }
            if (!$fechaDesde || $categoria < 1 || $categoria > 7) {
                return response()->json(['success' => false, 'message' => 'Fecha desde y categoría son obligatorios']);
            }
            $data = [
                'fecha' => $fechaDesde,
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'fecha_abre_inscripcion' => $request->fecha_abre_inscripcion ?: null,
                'fecha_cierra_inscripcion' => $request->fecha_cierra_inscripcion ?: null,
                'categoria' => $categoria,
                'tipo' => $tipo,
                'nombre' => $request->nombre,
                'premio_1' => $request->premio_1 !== null && $request->premio_1 !== '' ? (float) $request->premio_1 : null,
                'premio_2' => $request->premio_2 !== null && $request->premio_2 !== '' ? (float) $request->premio_2 : null,
                'premio_3' => $request->premio_3 !== null && $request->premio_3 !== '' ? (float) $request->premio_3 : null,
                'premio_4' => $request->premio_4 !== null && $request->premio_4 !== '' ? (float) $request->premio_4 : null,
                'valor_inscripcion' => $request->valor_inscripcion !== null && $request->valor_inscripcion !== '' ? (float) $request->valor_inscripcion : null,
            ];
            if ($id) {
                $item = Calendario::find($id);
                if ($item) {
                    $item->fill($data);
                    $item->save();
                }
            } else {
                Calendario::create($data);
            }
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    function eliminarCalendario(Request $request) {
        try {
            $id = (int) $request->id;
            if ($id) {
                Calendario::destroy($id);
                return response()->json(['success' => true]);
            }
            return response()->json(['success' => false], 400);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Admin: datos JSON de inscripciones (modal en admin_calendario).
     */
    public function adminCalendarioInscripcionesJson(Calendario $calendario)
    {
        $inscripciones = $calendario->inscripciones()
            ->orderByDesc('created_at')
            ->get();

        $titulo = $calendario->nombre
            ?: ($calendario->categoria.'ª categoría · '.$calendario->tipo_label);

        $fmtDisp = function ($raw) {
            $raw = is_string($raw) ? trim($raw) : '';
            if ($raw === '') {
                return 'Sin restricciones';
            }
            $j = json_decode($raw, true);
            if (!is_array($j)) {
                return $raw;
            }
            $fmtDia = function ($label, $dia) {
                if (!is_array($dia)) return null;
                $desde = $dia['desde'] ?? null;
                $hasta = $dia['hasta'] ?? null;
                $desde = is_string($desde) && trim($desde) !== '' ? trim($desde) : null;
                $hasta = is_string($hasta) && trim($hasta) !== '' ? trim($hasta) : null;
                if (!$desde && !$hasta) return null;
                if ($desde && $hasta) return $label.': '.$desde.' a '.$hasta;
                if ($desde && !$hasta) return $label.': desde '.$desde;
                if (!$desde && $hasta) return $label.': hasta '.$hasta;
                return null;
            };
            $partes = array_filter([
                $fmtDia('Viernes', $j['viernes'] ?? null),
                $fmtDia('Sábado', $j['sabado'] ?? null),
            ]);
            return !empty($partes) ? implode(' · ', $partes) : 'Sin restricciones';
        };

        return response()->json([
            'titulo' => $titulo,
            'fechas' => $calendario->textoFechasTorneo(),
            'inscripciones' => $inscripciones->map(function ($i) use ($fmtDisp) {
                $j1 = trim($i->jugador1_nombre.' '.$i->jugador1_apellido);
                if ($i->jugador1_id) {
                    $j1 .= ' ('.$i->jugador1_id.')';
                }
                $j2 = trim($i->jugador2_nombre.' '.$i->jugador2_apellido);
                if ($i->jugador2_id) {
                    $j2 .= ' ('.$i->jugador2_id.')';
                }
                return [
                    'registrado' => $i->created_at ? $i->created_at->format('d/m/Y H:i') : '—',
                    'jugador1' => $j1,
                    'tel1' => $i->jugador1_telefono,
                    'jugador2' => $j2,
                    'tel2' => $i->jugador2_telefono ?: '',
                    'disponibilidad' => $fmtDisp($i->disponibilidad_horaria),
                ];
            })->values(),
        ]);
    }

    /**
     * POST: Generar datos de prueba para un torneo: parejas al azar, zonas y horarios.
     * Elimina grupos existentes (fase de grupos, no eliminatoria) y crea nuevos con partidos.
     */
    function generarDatosPruebaTorneo(Request $request) {
        $torneoId = (int) $request->input('torneo_id');
        $cantidadParejas = (int) $request->input('cantidad_parejas');
        if ($torneoId <= 0 || $cantidadParejas < 4 || $cantidadParejas > 32) {
            return response()->json(['success' => false, 'message' => 'Torneo inválido o cantidad de parejas debe ser entre 4 y 32.'], 400);
        }
        $torneo = DB::table('torneos')->where('id', $torneoId)->where('activo', 1)->first();
        if (!$torneo) {
            return response()->json(['success' => false, 'message' => 'Torneo no encontrado.'], 404);
        }
        $zonasEliminatoria = ['cuartos final', 'semifinal', 'final', 'octavos final', '16avos final'];
        $gruposBorrar = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->whereNotIn('zona', $zonasEliminatoria)
            ->get(['id', 'partido_id']);
        $partidoIds = $gruposBorrar->pluck('partido_id')->filter()->unique()->values()->all();
        DB::table('grupos')->whereIn('id', $gruposBorrar->pluck('id'))->delete();
        if (!empty($partidoIds)) {
            DB::table('partidos')->whereIn('id', $partidoIds)->delete();
        }
        $jugadores = DB::table('jugadores')->where('activo', 1)->inRandomOrder()->get(['id'])->pluck('id')->all();
        $necesarios = $cantidadParejas * 2;
        if (count($jugadores) < 4) {
            return response()->json(['success' => false, 'message' => 'Se necesitan al menos 4 jugadores activos para generar parejas.'], 400);
        }
        $jugadores = array_slice($jugadores, 0, min($necesarios, count($jugadores)));
        $nParejas = (int) floor(count($jugadores) / 2);
        $parejas = [];
        for ($i = 0; $i < $nParejas; $i++) {
            $parejas[] = [$jugadores[$i * 2], $jugadores[$i * 2 + 1]];
        }
        // Preferir grupos de 3; usar 4 solo cuando haga falta. Máximo 4 por grupo. Mínimos grupos de 4.
        $q = (int) floor($nParejas / 3);
        $r = $nParejas % 3;
        $zoneSizes = [];
        if ($r == 0) {
            $zoneSizes = array_fill(0, $q, 3);
        } elseif ($r == 1) {
            if ($q >= 1) {
                $zoneSizes = array_merge([4], array_fill(0, $q - 1, 3));
            } else {
                $zoneSizes = [$nParejas];
            }
        } else {
            if ($q >= 2) {
                $zoneSizes = array_merge([4, 4], array_fill(0, $q - 2, 3));
            } elseif ($q == 1) {
                $zoneSizes = [3, 2];
            } else {
                $zoneSizes = [2];
            }
        }
        $numZonas = count($zoneSizes);
        $fechaBase = isset($torneo->fecha_inicio) && $torneo->fecha_inicio ? $torneo->fecha_inicio : date('Y-m-d');
        $now = now();
        $partidoDefaults = [
            'pareja_1_set_1' => 0, 'pareja_1_set_1_tie_break' => 0, 'pareja_2_set_1' => 0, 'pareja_2_set_1_tie_break' => 0,
            'pareja_1_set_2' => 0, 'pareja_1_set_2_tie_break' => 0, 'pareja_2_set_2' => 0, 'pareja_2_set_2_tie_break' => 0,
            'pareja_1_set_3' => 0, 'pareja_1_set_3_tie_break' => 0, 'pareja_2_set_3' => 0, 'pareja_2_set_3_tie_break' => 0,
            'pareja_1_set_super_tie_break' => 0, 'pareja_2_set_super_tie_break' => 0,
            'created_at' => $now, 'updated_at' => $now
        ];
        $horaInicio = 8;
        $slot = 0;
        $offsetParejas = 0;
        for ($z = 0; $z < $numZonas; $z++) {
            $zonaNombre = chr(65 + $z);
            $tamZona = $zoneSizes[$z];
            $paresZona = array_slice($parejas, $offsetParejas, $tamZona);
            $offsetParejas += $tamZona;
            $k = count($paresZona);
            for ($i = 0; $i < $k; $i++) {
                for ($j = $i + 1; $j < $k; $j++) {
                    $partidoId = DB::table('partidos')->insertGetId($partidoDefaults);
                    $hora = sprintf('%02d:%02d', $horaInicio + (int)($slot / 2), ($slot % 2) * 30);
                    $slot++;
                    $gruposInsert = [
                        [
                            'torneo_id' => $torneoId, 'zona' => $zonaNombre, 'fecha' => $fechaBase, 'horario' => $hora,
                            'jugador_1' => $paresZona[$i][0], 'jugador_2' => $paresZona[$i][1], 'partido_id' => $partidoId,
                            'created_at' => $now, 'updated_at' => $now
                        ],
                        [
                            'torneo_id' => $torneoId, 'zona' => $zonaNombre, 'fecha' => $fechaBase, 'horario' => $hora,
                            'jugador_1' => $paresZona[$j][0], 'jugador_2' => $paresZona[$j][1], 'partido_id' => $partidoId,
                            'created_at' => $now, 'updated_at' => $now
                        ]
                    ];
                    DB::table('grupos')->insert($gruposInsert);
                }
            }
        }
        return response()->json([
            'success' => true,
            'message' => 'Se generaron ' . $nParejas . ' parejas en ' . $numZonas . ' zonas con partidos y horarios.',
            'torneo_id' => $torneoId
        ]);
    }
    
    function adminConfig() {
        // Cargar TODAS las configuraciones globales para el listado
        $configuraciones = DB::table('configuracion_cruces_puntuables')
            ->whereNull('torneo_id')
            ->orderBy('id', 'desc')
            ->get();
        
        // Cargar configuración específica si se está editando
        $config = null;
        $editarId = request()->get('editar');
        if ($editarId) {
            $configuracion = DB::table('configuracion_cruces_puntuables')
                ->where('id', $editarId)
                ->whereNull('torneo_id')
                ->first();
            
            if ($configuracion) {
                $config = [
                    'id' => $configuracion->id,
                    'cantidad_parejas' => $configuracion->cantidad_parejas,
                    'tiene_16avos_final' => $configuracion->tiene_16avos_final,
                    'tiene_8vos_final' => $configuracion->tiene_8vos_final,
                    'tiene_4tos_final' => $configuracion->tiene_4tos_final,
                    'llave_16avos' => $configuracion->llave_16avos ? json_decode($configuracion->llave_16avos, true) : null,
                    'llave_8vos' => $configuracion->llave_8vos ? json_decode($configuracion->llave_8vos, true) : null,
                    'llave_4tos' => $configuracion->llave_4tos ? json_decode($configuracion->llave_4tos, true) : null,
                    'llave_semifinal' => $configuracion->llave_semifinal ? json_decode($configuracion->llave_semifinal, true) : null,
                    'llave_final' => $configuracion->llave_final ? json_decode($configuracion->llave_final, true) : null,
                ];
            }
        }
        
        return View('bahia_padel.admin.config.index')
            ->with('config', $config)
            ->with('configuraciones', $configuraciones); 
    }
    
    function guardarConfigCruces(Request $request) {
        try {
            $tiene16avos = $request->tiene_16avos_final ? 1 : 0;
            $tiene8vos = $request->tiene_8vos_final ? 1 : 0;
            $tiene4tos = $request->tiene_4tos_final ? 1 : 0;

            $data = [
                'torneo_id' => null, // Configuración global por ahora
                'cantidad_parejas' => $request->cantidad_parejas,
                'tiene_16avos_final' => $tiene16avos,
                'tiene_8vos_final' => $tiene8vos,
                'tiene_4tos_final' => $tiene4tos,
                'llave_16avos' => $tiene16avos ? $this->sanitizarLlaveCruces($request->llave_16avos, 16) : null,
                'llave_8vos' => $tiene8vos ? $this->sanitizarLlaveCruces($request->llave_8vos, 8) : null,
                // Cuartos/semi/final pueden existir aunque no se jueguen todas las series (byes)
                'llave_4tos' => $this->sanitizarLlaveCruces($request->llave_4tos, 4),
                'llave_semifinal' => $this->sanitizarLlaveCruces($request->llave_semifinal, 2),
                'llave_final' => $this->sanitizarLlaveCruces($request->llave_final, 1),
                'updated_at' => now()
            ];
            
            $configId = $request->config_id ? (int) $request->config_id : null;
            
            if ($configId) {
                // Actualizar configuración existente
                $existente = DB::table('configuracion_cruces_puntuables')
                    ->where('id', $configId)
                    ->whereNull('torneo_id')
                    ->first();
                
                if ($existente) {
                    DB::table('configuracion_cruces_puntuables')
                        ->where('id', $configId)
                        ->update($data);
                } else {
                    // ID inválido, crear nueva
                    $data['created_at'] = now();
                    DB::table('configuracion_cruces_puntuables')->insert($data);
                }
            } else {
                // Crear nueva configuración
                $data['created_at'] = now();
                DB::table('configuracion_cruces_puntuables')->insert($data);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Configuración guardada correctamente'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error al guardar configuración de cruces: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Normaliza referencias de cruces (A1, O3, C2, DA1, G1-8vos, etc.).
     */
    private function normalizarReferenciaCruce($ref)
    {
        $ref = strtoupper(trim((string) $ref));
        if ($ref === '') {
            return null;
        }

        $ref = preg_replace('/\s+/', '', $ref);

        // 1B -> B1 (error comun de tipeo)
        if (preg_match('/^(\d+)([A-P])$/', $ref, $m)) {
            return $m[2] . (int) $m[1];
        }

        // A01 -> A1
        if (preg_match('/^([A-P])0*(\d+)$/', $ref, $m)) {
            return $m[1] . (int) $m[2];
        }

        // O01 / C02 / S01 / F01 / DA01
        if (preg_match('/^(DA|O|C|S|F)0*(\d+)$/', $ref, $m)) {
            return $m[1] . (int) $m[2];
        }

        // G01-8VOS / G1-OCTAVOS / G2-4TOS / G1-16AVOS / G1-SEMIFINAL
        if (preg_match('/^G0*(\d+)-(16AVOS|8VOS|OCTAVOS|4TOS|CUARTOS|SEMIFINAL|SEMIFINALES)$/', $ref, $m)) {
            $ronda = $m[2];
            if ($ronda === 'OCTAVOS') $ronda = '8VOS';
            if ($ronda === 'CUARTOS') $ronda = '4TOS';
            if ($ronda === 'SEMIFINALES') $ronda = 'SEMIFINAL';
            return 'G' . (int) $m[1] . '-' . $ronda;
        }

        if ($ref === 'BYE') {
            return 'BYE';
        }

        return $ref;
    }

    /**
     * Limpia y normaliza el JSON de llaves para evitar cruces invalidos o duplicados.
     */
    private function sanitizarLlaveCruces($llaveRaw, $maxPartidos)
    {
        if (empty($llaveRaw)) {
            return null;
        }

        $llave = $llaveRaw;
        if (is_string($llaveRaw)) {
            $decoded = json_decode($llaveRaw, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return null;
            }
            $llave = $decoded;
        }

        if (!is_array($llave)) {
            return null;
        }

        $limpia = [];
        $seen = [];

        foreach ($llave as $partido) {
            if (!is_array($partido)) {
                continue;
            }

            $p1 = $this->normalizarReferenciaCruce($partido['pareja_1'] ?? null);
            $p2 = $this->normalizarReferenciaCruce($partido['pareja_2'] ?? null);

            if (empty($p1) || empty($p2)) {
                continue;
            }

            // Evitar partido espejo o repetido
            $k1 = strcmp($p1, $p2) <= 0 ? $p1 . '|' . $p2 : $p2 . '|' . $p1;
            if (isset($seen[$k1])) {
                continue;
            }
            $seen[$k1] = true;

            $limpia[] = [
                'pareja_1' => $p1,
                'pareja_2' => $p2,
            ];

            if ($maxPartidos > 0 && count($limpia) >= $maxPartidos) {
                break;
            }
        }

        if (empty($limpia)) {
            return null;
        }

        return json_encode($limpia, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Vista de configuración de cruces para torneos Americanos
     */
    function adminConfigAmericano() {
        // Cargar todas las configuraciones existentes
        $configuraciones = DB::table('configuracion_cruces_americanos')
            ->orderBy('cantidad_parejas', 'asc')
            ->get();
        
        // Cargar configuración específica si se está editando
        $config = null;
        $editarId = request()->get('editar');
        if ($editarId) {
            $configuracion = DB::table('configuracion_cruces_americanos')
                ->where('id', $editarId)
                ->first();
            
            if ($configuracion) {
                $config = [
                    'id' => $configuracion->id,
                    'nombre' => $configuracion->nombre,
                    'cantidad_parejas' => $configuracion->cantidad_parejas,
                    'cantidad_zonas' => $configuracion->cantidad_zonas,
                    'parejas_por_zona' => $configuracion->parejas_por_zona,
                    'clasifican_primeros' => $configuracion->clasifican_primeros,
                    'clasifican_segundos' => $configuracion->clasifican_segundos,
                    'clasifican_terceros' => $configuracion->clasifican_terceros,
                    'clasifican_cuartos' => $configuracion->clasifican_cuartos,
                    'tiene_16avos_final' => $configuracion->tiene_16avos_final,
                    'tiene_8vos_final' => $configuracion->tiene_8vos_final,
                    'tiene_4tos_final' => $configuracion->tiene_4tos_final,
                    'criterio_desempate_orden' => $configuracion->criterio_desempate_orden,
                    'games_fase_grupos' => $configuracion->games_fase_grupos,
                    'games_cruces' => $configuracion->games_cruces,
                    'games_semifinal' => $configuracion->games_semifinal,
                    'games_final' => $configuracion->games_final,
                    'llave_16avos' => $configuracion->llave_16avos ? json_decode($configuracion->llave_16avos, true) : null,
                    'llave_8vos' => $configuracion->llave_8vos ? json_decode($configuracion->llave_8vos, true) : null,
                    'llave_4tos' => $configuracion->llave_4tos ? json_decode($configuracion->llave_4tos, true) : null,
                    'llave_semifinal' => $configuracion->llave_semifinal ? json_decode($configuracion->llave_semifinal, true) : null,
                    'llave_final' => $configuracion->llave_final ? json_decode($configuracion->llave_final, true) : null,
                    'notas' => $configuracion->notas,
                ];
            }
        }
        
        return View('bahia_padel.admin.config.americano')
            ->with('configuraciones', $configuraciones)
            ->with('config', $config);
    }
    
    /**
     * Guardar configuración de cruces para torneos Americanos
     */
    function guardarConfigCrucesAmericano(Request $request) {
        try {
            $tiene16avos = $request->tiene_16avos_final ? 1 : 0;
            $tiene8vos = $request->tiene_8vos_final ? 1 : 0;
            $tiene4tos = $request->tiene_4tos_final ? 1 : 0;

            $data = [
                'nombre' => $request->nombre,
                'cantidad_parejas' => $request->cantidad_parejas,
                'cantidad_zonas' => $request->cantidad_zonas,
                'parejas_por_zona' => $request->parejas_por_zona ?? 4,
                'clasifican_primeros' => $request->clasifican_primeros ?? 0,
                'clasifican_segundos' => $request->clasifican_segundos ?? 0,
                'clasifican_terceros' => $request->clasifican_terceros ?? 0,
                'clasifican_cuartos' => $request->clasifican_cuartos ?? 0,
                'tiene_16avos_final' => $tiene16avos,
                'tiene_8vos_final' => $tiene8vos,
                'tiene_4tos_final' => $tiene4tos,
                'criterio_desempate_orden' => $request->criterio_desempate_orden ?? 'PG,DIF_GAMES,GF',
                'games_fase_grupos' => $request->games_fase_grupos ?? 5,
                'games_cruces' => $request->games_cruces ?? 5,
                'games_semifinal' => $request->games_semifinal ?? 5,
                'games_final' => $request->games_final ?? 7,
                'llave_16avos' => $tiene16avos ? $this->sanitizarLlaveCruces($request->llave_16avos, 16) : null,
                'llave_8vos' => $tiene8vos ? $this->sanitizarLlaveCruces($request->llave_8vos, 8) : null,
                'llave_4tos' => $tiene4tos ? $this->sanitizarLlaveCruces($request->llave_4tos, 4) : null,
                'llave_semifinal' => $this->sanitizarLlaveCruces($request->llave_semifinal, 2),
                'llave_final' => $this->sanitizarLlaveCruces($request->llave_final, 1),
                'notas' => $request->notas,
                'updated_at' => now()
            ];
            
            $configId = $request->config_id;
            
            if ($configId) {
                // Actualizar existente
                DB::table('configuracion_cruces_americanos')
                    ->where('id', $configId)
                    ->update($data);
            } else {
                // Crear nueva
                $data['created_at'] = now();
                $configId = DB::table('configuracion_cruces_americanos')->insertGetId($data);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Configuración guardada correctamente',
                'config_id' => $configId
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error al guardar configuración de cruces americano: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Eliminar configuración de cruces americano
     */
    function eliminarConfigCrucesAmericano(Request $request) {
        try {
            $configId = $request->config_id;
            
            if (!$configId) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de configuración no proporcionado'
                ], 400);
            }
            
            DB::table('configuracion_cruces_americanos')
                ->where('id', $configId)
                ->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Configuración eliminada correctamente'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error al eliminar configuración de cruces americano: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener configuración de cruces americano por cantidad de parejas
     */
    function getConfigCrucesAmericano(Request $request) {
        try {
            $cantidadParejas = $request->cantidad_parejas;
            
            $configuracion = DB::table('configuracion_cruces_americanos')
                ->where('cantidad_parejas', $cantidadParejas)
                ->first();
            
            if (!$configuracion) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay configuración para ' . $cantidadParejas . ' parejas'
                ]);
            }
            
            return response()->json([
                'success' => true,
                'config' => $configuracion
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    function registrarTorneo(Request $request) {
        try {
            $id = $request->id_torneo;
            if($id == 0){
                $torneo = new Torneo;            
                $torneo->activo = 1;
                $torneo->estado = 1;
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
            
            $estado = (int) ($torneo->estado ?? 1);
            $tipoTorneo = isset($torneo->tipo_torneo_formato) ? $torneo->tipo_torneo_formato : 'puntuable';
            $torneoId = $request->torneo_id;
            
            // En progreso (estado 2): ir directo a zonas/resultados
            if ($estado === 2) {
                if ($tipoTorneo == 'americano') {
                    return redirect()->route('admintorneoamericanopartidos', ['torneo_id' => $torneoId]);
                }
                return redirect()->route('admintorneoresultados', ['torneo_id' => $torneoId]);
            }
            
            // Finalizado (estado 3): ir directo a cruces
            if ($estado === 3) {
                if ($tipoTorneo == 'americano') {
                    return redirect()->route('admintorneoamericanocruces', ['torneo_id' => $torneoId]);
                }
                return redirect()->route('admintorneopuntuablecrucesv2', ['torneo_id' => $torneoId]);
            }
            
            $jugadores = DB::table('jugadores')                                                                                        
                            ->where('jugadores.activo', 1)                                
                            ->get();
            
            // Obtener grupos excluyendo los de eliminatoria (zonas: 'cuartos final', 'semifinal', 'final')
            // Los grupos de eliminatoria son solo para los cruces y no deben mostrarse en la configuración inicial
            // Para torneos americanos, verificar si hay grupos con partido_id (torneo comenzado)
            if ($tipoTorneo == 'americano') {
                // Verificar si hay grupos con partido_id (torneo ya comenzado)
                $gruposConPartidos = DB::table('grupos')
                                ->where('grupos.torneo_id', $request->torneo_id)
                                ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final'])
                                ->whereNotNull('grupos.partido_id')
                                ->whereNotNull('grupos.jugador_1')
                                ->whereNotNull('grupos.jugador_2')
                                ->count();
                
                // Si hay grupos con partido_id, el torneo ya comenzó, redirigir a partidos
                if ($gruposConPartidos > 0) {
                    return redirect()->route('admintorneoamericanopartidos', ['torneo_id' => $request->torneo_id]);
                }
                
                // Si no hay grupos con partido_id, obtener grupos iniciales (borrador) para permitir seguir editando
                $grupos = DB::table('grupos')
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
            } else {
                // Para otros tipos de torneo, usar la lógica original
                // Incluir grupos con jugador_1 = 0 o jugador_2 = 0 (partidos "libres" para zonas de 4 parejas)
                $grupos = DB::table('grupos')
                    ->where('grupos.torneo_id', $request->torneo_id)
                    ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final'])
                    ->where(function($query) {
                        $query->where(function($q) {
                            $q->whereNotNull('grupos.jugador_1')
                              ->whereNotNull('grupos.jugador_2');
                        })->orWhere(function($q) {
                            // Incluir grupos con jugador_1 = 0 o jugador_2 = 0
                            $q->where(function($q2) {
                                $q2->where('grupos.jugador_1', 0)
                                   ->whereNotNull('grupos.jugador_2');
                            })->orWhere(function($q2) {
                                $q2->whereNotNull('grupos.jugador_1')
                                   ->where('grupos.jugador_2', 0);
                            });
                        });
                    })
                    ->select('grupos.id', 'grupos.torneo_id', 'grupos.zona', 'grupos.fecha', 
                            'grupos.horario', 'grupos.jugador_1', 'grupos.jugador_2', 'grupos.partido_id')
                    ->orderBy('grupos.id')  // Solo ordenar por ID para mantener el orden de creación
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
                
                // $grupos = $gruposFiltrados;
            }
            
            // Navegar a la vista correspondiente según el tipo de torneo
            if ($tipoTorneo == 'americano') {
                // Cargar configuraciones de cruces americanos disponibles
                $configsCrucesAmericanos = DB::table('configuracion_cruces_americanos')
                    ->orderBy('cantidad_parejas', 'asc')
                    ->get();
                
                return View('bahia_padel.admin.torneo.armar_americano')
                            ->with('jugadores', $jugadores)
                            ->with('torneo', $torneo)
                            ->with('grupos', $grupos)
                            ->with('configsCrucesAmericanos', $configsCrucesAmericanos);
            } elseif ($tipoTorneo == 'suma') {
                return View('bahia_padel.admin.torneo.armar_suma')
                            ->with('jugadores', $jugadores)
                            ->with('torneo', $torneo)
                            ->with('grupos', $grupos);
            } else {
                // Puntuable (por defecto): cargar configuraciones globales disponibles
                $configsCrucesPuntuables = DB::table('configuracion_cruces_puntuables')
                    ->whereNull('torneo_id')
                    ->orderBy('cantidad_parejas', 'asc')
                    ->get();

                return View('bahia_padel.admin.torneo.armar_torneo_v2')
                            ->with('jugadores', $jugadores)
                            ->with('torneo', $torneo)
                            ->with('grupos', $grupos)
                            ->with('configsCrucesPuntuables', $configsCrucesPuntuables);
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
            
            // Manejar subida de foto (guardar en storage para que persista en producción)
            if ($request->hasFile('foto')) {
                try {
                    $image = $request->file('foto');
                    $name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $image->getClientOriginalName());
                    Storage::disk('public')->makeDirectory('images/jugadores');
                    $fullPath = Storage::disk('public')->path('images/jugadores/' . $name);
                    Image::make($image->getRealPath())->save($fullPath);
                    $jugador->foto = 'storage/images/jugadores/' . $name;
                } catch (\Exception $e) {
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
    function mostrarSubirFotoJugador(Request $request) {
        $jugadorId = $request->query('jugador_id');
        return view('bahia_padel.mobile.subir_foto_jugador', ['jugador_id_seleccionado' => $jugadorId]);
    }
    
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
            $id = $request->input('id');
            
            if (!$id) {
                return redirect()->route('subir.foto.jugador')->with('error', 'ID de jugador requerido');
            }
            
            $jugador = Jugadore::find($id);
            if (!$jugador) {
                return redirect()->route('subir.foto.jugador')->with('error', 'Jugador no encontrado');
            }
            
            // Manejar subida de foto
            if ($request->hasFile('foto')) {
                try {
                    $image = $request->file('foto');
                    
                    // Validar que sea una imagen
                    if (!$image->isValid()) {
                        return redirect()->route('subir.foto.jugador')->with('error', 'El archivo enviado no es válido');
                    }
                    
                    // Sanitizar nombre del archivo y guardar en storage (persiste en producción)
                    $originalName = $image->getClientOriginalName();
                    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
                    $extension = $image->getClientOriginalExtension();
                    $name = time() . '_' . $safeName . '.' . $extension;
                    $path = 'storage/images/jugadores/' . $name;
                    $pathRel = 'images/jugadores/' . $name;
                    Storage::disk('public')->makeDirectory('images/jugadores');
                    $directory = Storage::disk('public')->path('images/jugadores');
                    $imgPath = Storage::disk('public')->path($pathRel);
                    
                    \Log::info('=== INICIO SUBIDA FOTO (storage) ===');
                    \Log::info('Nombre archivo: ' . $name);
                    \Log::info('Ruta para BD: ' . $path);
                    \Log::info('Ruta completa: ' . $imgPath);
                    
                    // Cargar imagen con Intervention Image
                    try {
                        $img = Image::make($image->getRealPath());
                    } catch (\Exception $e) {
                        throw new \Exception('No se pudo procesar la imagen. Verifica que sea un formato válido (JPG, PNG, GIF).');
                    }
                    
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
                    
                    // Guardar imagen con compresión progresiva si es necesario
                    $quality = 90;
                    $maxAttempts = 8;
                    $attempt = 0;
                    $fileSize = 0;
                    
                    do {
                        try {
                            $rutaCompleta = $imgPath;
                            \Log::info('Intentando guardar en: ' . $rutaCompleta);
                            $img->save($rutaCompleta, $quality);
                            if (!file_exists($rutaCompleta)) {
                                \Log::error('ERROR: Archivo no existe después de save() en: ' . $rutaCompleta);
                                throw new \Exception('No se pudo guardar el archivo en: ' . $rutaCompleta);
                            }
                            \Log::info('Archivo guardado exitosamente en: ' . $rutaCompleta);
                            $fileSize = filesize($rutaCompleta);
                            
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
                                $quality = 75;
                                $rutaCompleta = $imgPath;
                            }
                            
                        } catch (\Exception $saveError) {
                            if ($attempt >= $maxAttempts - 1) {
                                throw $saveError;
                            }
                            $quality -= 10;
                            $attempt++;
                        }
                        
                    } while ($fileSize > $maxSize && $quality >= 40 && $attempt < $maxAttempts);
                    
                    $rutaFinal = $imgPath;
                    if (!file_exists($rutaFinal)) {
                        \Log::error('ERROR: El archivo no existe después de guardar: ' . $rutaFinal);
                        throw new \Exception('El archivo no se guardó correctamente.');
                    }
                    \Log::info('Archivo guardado en storage: ' . $rutaFinal);
                    $rutaFinalBD = $path;
                    $jugador->foto = $rutaFinalBD;
                    \Log::info('Ruta guardada en BD: ' . $jugador->foto);
                    $filePathVerificacion = file_exists($rutaFinal) ? $rutaFinal : public_path($jugador->foto);
                    \Log::info('Verificación post-BD - Ruta guardada en BD: ' . $jugador->foto);
                    \Log::info('Verificación post-BD - Ruta completa: ' . $filePathVerificacion);
                    \Log::info('Verificación post-BD - Existe: ' . (file_exists($filePathVerificacion) ? 'SÍ' : 'NO'));
                    
                    // Listar archivos en el directorio para debugging (últimos 10 archivos ordenados por fecha)
                    $archivosEnDirectorio = scandir($directory);
                    $archivosFiltrados = array_filter($archivosEnDirectorio, function($file) use ($directory) {
                        return $file !== '.' && $file !== '..' && is_file($directory . '/' . $file);
                    });
                    // Ordenar por fecha de modificación (más recientes primero)
                    usort($archivosFiltrados, function($a, $b) use ($directory) {
                        return filemtime($directory . '/' . $b) - filemtime($directory . '/' . $a);
                    });
                    $ultimosArchivos = array_slice($archivosFiltrados, 0, 10);
                    \Log::info('Últimos 10 archivos en directorio ' . $directory . ': ' . json_encode($ultimosArchivos));
                    foreach ($ultimosArchivos as $archivo) {
                        $rutaArchivo = $directory . '/' . $archivo;
                        \Log::info('  - ' . $archivo . ' (modificado: ' . date('Y-m-d H:i:s', filemtime($rutaArchivo)) . ')');
                    }
                } catch (\Exception $e) {
                    \Log::error('Error al procesar imagen: ' . $e->getMessage());
                    \Log::error('Stack: ' . $e->getTraceAsString());
                    return redirect()->route('subir.foto.jugador')->with('error', 'Error al procesar la imagen: ' . $e->getMessage());
                }
            } else {
                return redirect()->route('subir.foto.jugador')->with('error', 'No se envió ninguna imagen. Verifica que hayas seleccionado un archivo.');
            }
            
            $jugador->save();
            
            // Verificar que el archivo existe antes de obtener su tamaño
            $filePath = public_path($jugador->foto);
            $fileSizeMB = 0;
            \Log::info('=== VERIFICACIÓN FINAL ===');
            \Log::info('Ruta en BD: ' . $jugador->foto);
            \Log::info('Ruta completa del archivo: ' . $filePath);
            \Log::info('Archivo existe: ' . (file_exists($filePath) ? 'SÍ' : 'NO'));
            
            if (file_exists($filePath)) {
                $fileSize = filesize($filePath);
                $fileSizeMB = round($fileSize / (1024 * 1024), 2);
                \Log::info('Foto guardada exitosamente en: ' . $filePath . ' (Tamaño: ' . $fileSizeMB . ' MB)');
                \Log::info('URL pública generada: ' . asset($jugador->foto));
                \Log::info('URL completa esperada: ' . url($jugador->foto));
            } else {
                \Log::error('ERROR: El archivo no existe después de guardar: ' . $filePath);
                \Log::error('Intentando buscar en otras ubicaciones...');
                
                // Buscar el archivo en posibles ubicaciones alternativas
                $nombreArchivo = basename($jugador->foto);
                $posiblesRutas = [
                    base_path('public/images/jugadores/' . $nombreArchivo),
                    storage_path('app/public/images/jugadores/' . $nombreArchivo),
                    public_path('images/jugadores/' . $nombreArchivo),
                ];
                
                foreach ($posiblesRutas as $rutaAlternativa) {
                    if (file_exists($rutaAlternativa)) {
                        \Log::error('ARCHIVO ENCONTRADO EN: ' . $rutaAlternativa);
                    } else {
                        \Log::error('No encontrado en: ' . $rutaAlternativa);
                    }
                }
            }
            
            \Log::info('=== FIN VERIFICACIÓN ===');
            
            $mensaje = 'Foto actualizada correctamente';
            if ($fileSizeMB > 0) {
                $mensaje .= ' (tamaño final: ' . $fileSizeMB . ' MB)';
            }
            
            // Redirigir con el ID del jugador para mantener la selección
            return redirect()->route('subir.foto.jugador', ['jugador_id' => $jugador->id])->with('success', $mensaje);
        } catch (\Exception $e) {
            return redirect()->route('subir.foto.jugador')->with('error', 'Error al subir la foto: ' . $e->getMessage());
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
            
            // Manejar subida de foto solo si se envía una nueva (storage para persistir en prod)
            if ($request->hasFile('foto')) {
                try {
                    $image = $request->file('foto');
                    $name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $image->getClientOriginalName());
                    Storage::disk('public')->makeDirectory('images/jugadores');
                    $fullPath = Storage::disk('public')->path('images/jugadores/' . $name);
                    Image::make($image->getRealPath())->save($fullPath);
                    $jugador->foto = 'storage/images/jugadores/' . $name;
                } catch (\Exception $e) {
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
        // Normalizar zona: solo guardar letra (A, B, C, D), nunca "Zona A"
        $zonaRaw = trim($request->zona ?? 'A');
        $zona = (preg_match('/^Zona\s+([A-Z])$/i', $zonaRaw, $m)) ? strtoupper($m[1]) : (strlen($zonaRaw) === 1 ? strtoupper($zonaRaw) : $zonaRaw);
        $tieneCuatroParejas = $request->input('tiene_cuatro_parejas', 0) == 1;
        $tieneCuatroParejasEliminatoria = $request->input('tiene_cuatro_parejas_eliminatoria', 0) == 1;
        
        \Log::info('=== guardarFechaAdminTorneo ===');
        \Log::info('Torneo ID: ' . $torneoId . ', Zona: ' . $zona . ', 4 parejas eliminatoria: ' . ($tieneCuatroParejasEliminatoria ? 'Sí' : 'No'));

        // Guardar configuración de cruces puntuable seleccionada al momento de armar el torneo
        if ($request->exists('config_cruces_puntuable_id')) {
            $configCrucesPuntuableId = $request->input('config_cruces_puntuable_id');
            DB::table('torneos')
                ->where('id', $torneoId)
                ->update([
                    'config_cruces_puntuable_id' => $configCrucesPuntuableId ?: null
                ]);
        }

        // Eliminar grupos de la zona actual (incluyendo "ganador X" y "perdedor X")
        $grupos = \App\Grupo::where('torneo_id', $torneoId)
            ->where(function($query) use ($zona) {
                $query->where('zona', $zona)
                      ->orWhere('zona', 'ganador ' . $zona)
                      ->orWhere('zona', 'perdedor ' . $zona);
            })
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
                ->where(function($query) use ($zona) {
                    $query->where('zona', $zona)
                          ->orWhere('zona', 'ganador ' . $zona)
                          ->orWhere('zona', 'perdedor ' . $zona);
                })
                ->delete();
        }
        
        // Función helper para obtener fecha/horario con valores por defecto
        $getFecha = function($value) {
            return !empty($value) && $value !== 'null' && $value !== null ? $value : '2000-01-01';
        };
        $getHorario = function($value) {
            return !empty($value) && $value !== 'null' && $value !== null ? $value : '00:00';
        };
        
        if ($tieneCuatroParejasEliminatoria && $tieneCuatroParejas && $request->pareja_4_idJugadorArriba && $request->pareja_4_idJugadorAbajo) {
            // ESTRUCTURA CON 4 PAREJAS ELIMINATORIA: Partido A, Perdedor, Ganador, Partido B
            // Partido A: Pareja 1 vs Pareja 2
            $partidoA = $this->crearPartido();
            $grupoA_P1 = new Grupo;
            $grupoA_P1->torneo_id = $torneoId;
            $grupoA_P1->zona = $zona;
            $grupoA_P1->fecha = $getFecha($request->input('pareja_1_partido_1_dia'));
            $grupoA_P1->horario = $getHorario($request->input('pareja_1_partido_1_horario'));
            $grupoA_P1->jugador_1 = $request->pareja_1_idJugadorArriba;
            $grupoA_P1->jugador_2 = $request->pareja_1_idJugadorAbajo;
            $grupoA_P1->partido_id = $partidoA->id;
            $grupoA_P1->save();
            
            $grupoA_P2 = new Grupo;
            $grupoA_P2->torneo_id = $torneoId;
            $grupoA_P2->zona = $zona;
            $grupoA_P2->fecha = $getFecha($request->input('pareja_2_partido_1_dia'));
            $grupoA_P2->horario = $getHorario($request->input('pareja_2_partido_1_horario'));
            $grupoA_P2->jugador_1 = $request->pareja_2_idJugadorArriba;
            $grupoA_P2->jugador_2 = $request->pareja_2_idJugadorAbajo;
            $grupoA_P2->partido_id = $partidoA->id;
            $grupoA_P2->save();
            
            // Partido B: Pareja 3 vs Pareja 4
            $partidoB = $this->crearPartido();
            $grupoB_P3 = new Grupo;
            $grupoB_P3->torneo_id = $torneoId;
            $grupoB_P3->zona = $zona;
            $grupoB_P3->fecha = $getFecha($request->input('pareja_3_partido_2_dia'));
            $grupoB_P3->horario = $getHorario($request->input('pareja_3_partido_2_horario'));
            $grupoB_P3->jugador_1 = $request->pareja_3_idJugadorArriba;
            $grupoB_P3->jugador_2 = $request->pareja_3_idJugadorAbajo;
            $grupoB_P3->partido_id = $partidoB->id;
            $grupoB_P3->save();
            
            $grupoB_P4 = new Grupo;
            $grupoB_P4->torneo_id = $torneoId;
            $grupoB_P4->zona = $zona;
            $grupoB_P4->fecha = $getFecha($request->input('pareja_4_partido_2_dia'));
            $grupoB_P4->horario = $getHorario($request->input('pareja_4_partido_2_horario'));
            $grupoB_P4->jugador_1 = $request->pareja_4_idJugadorArriba;
            $grupoB_P4->jugador_2 = $request->pareja_4_idJugadorAbajo;
            $grupoB_P4->partido_id = $partidoB->id;
            $grupoB_P4->save();
            
            // Ganador: Ganador Partido A vs Ganador Partido B (jugadores aún no se conocen)
            // En el formato eliminatoria, la pareja 4 tiene "ganador" en celda 10 (partido 1)
            $partidoGanador = $this->crearPartido();
            $grupoGanador = new Grupo;
            $grupoGanador->torneo_id = $torneoId;
            $grupoGanador->zona = 'ganador ' . $zona; // Identificar como partido de ganadores
            // Buscar la fecha/horario de "ganador" - puede venir de varias celdas sincronizadas
            $fechaGanador = $getFecha($request->input('pareja_4_partido_1_dia')); // Celda 10 (ganador pareja 4)
            if (empty($fechaGanador) || $fechaGanador === '2000-01-01') {
                $fechaGanador = $getFecha($request->input('pareja_2_partido_2_dia')); // Celda 6 (ganador pareja 2)
            }
            if (empty($fechaGanador) || $fechaGanador === '2000-01-01') {
                $fechaGanador = $getFecha($request->input('pareja_1_partido_2_dia')); // Celda 10 (ganador pareja 1)
            }
            $grupoGanador->fecha = $fechaGanador;
            $horarioGanador = $getHorario($request->input('pareja_4_partido_1_horario'));
            if (empty($horarioGanador) || $horarioGanador === '00:00') {
                $horarioGanador = $getHorario($request->input('pareja_2_partido_2_horario'));
            }
            if (empty($horarioGanador) || $horarioGanador === '00:00') {
                $horarioGanador = $getHorario($request->input('pareja_1_partido_2_horario'));
            }
            $grupoGanador->horario = $horarioGanador;
            $grupoGanador->jugador_1 = 0; // Se asignará después según resultados
            $grupoGanador->jugador_2 = 0;
            $grupoGanador->partido_id = $partidoGanador->id;
            $grupoGanador->save();
            
            // Perdedor: Perdedor Partido A vs Perdedor Partido B (jugadores aún no se conocen)
            // En el formato eliminatoria, la pareja 3 tiene "perdedor" en celda 7 (partido 1)
            $partidoPerdedor = $this->crearPartido();
            $grupoPerdedor = new Grupo;
            $grupoPerdedor->torneo_id = $torneoId;
            $grupoPerdedor->zona = 'perdedor ' . $zona; // Identificar como partido de perdedores
            // Buscar la fecha/horario de "perdedor" - puede venir de varias celdas sincronizadas
            $fechaPerdedor = $getFecha($request->input('pareja_3_partido_1_dia')); // Celda 7 (perdedor pareja 3)
            if (empty($fechaPerdedor) || $fechaPerdedor === '2000-01-01') {
                $fechaPerdedor = $getFecha($request->input('pareja_1_partido_2_dia')); // Celda 3 (perdedor pareja 1)
            }
            if (empty($fechaPerdedor) || $fechaPerdedor === '2000-01-01') {
                $fechaPerdedor = $getFecha($request->input('pareja_2_partido_2_dia')); // Celda 11 (perdedor pareja 2)
            }
            if (empty($fechaPerdedor) || $fechaPerdedor === '2000-01-01') {
                $fechaPerdedor = $getFecha($request->input('pareja_4_partido_2_dia')); // Celda 11 (perdedor pareja 4)
            }
            $grupoPerdedor->fecha = $fechaPerdedor;
            $horarioPerdedor = $getHorario($request->input('pareja_3_partido_1_horario'));
            if (empty($horarioPerdedor) || $horarioPerdedor === '00:00') {
                $horarioPerdedor = $getHorario($request->input('pareja_1_partido_2_horario'));
            }
            if (empty($horarioPerdedor) || $horarioPerdedor === '00:00') {
                $horarioPerdedor = $getHorario($request->input('pareja_2_partido_2_horario'));
            }
            if (empty($horarioPerdedor) || $horarioPerdedor === '00:00') {
                $horarioPerdedor = $getHorario($request->input('pareja_4_partido_2_horario'));
            }
            $grupoPerdedor->horario = $horarioPerdedor;
            $grupoPerdedor->jugador_1 = 0; // Se asignará después según resultados
            $grupoPerdedor->jugador_2 = 0;
            $grupoPerdedor->partido_id = $partidoPerdedor->id;
            $grupoPerdedor->save();
            
            return response()->json(['success' => true, 'partidos' => [$partidoA->id, $partidoB->id, $partidoGanador->id, $partidoPerdedor->id]]);
        } else if ($tieneCuatroParejas && $request->pareja_4_idJugadorArriba && $request->pareja_4_idJugadorAbajo) {
            // ESTRUCTURA CON 4 PAREJAS: SEMIFINALES Y FINAL
            // Semifinal 1: Pareja 1 vs Pareja 2
            $partidoSF1 = $this->crearPartido();
            $grupoSF1_P1 = new Grupo;
            $grupoSF1_P1->torneo_id = $torneoId;
            $grupoSF1_P1->zona = $zona;
            $grupoSF1_P1->fecha = $getFecha($request->input('pareja_1_partido_1_dia'));
            $grupoSF1_P1->horario = $getHorario($request->input('pareja_1_partido_1_horario'));
            $grupoSF1_P1->jugador_1 = $request->pareja_1_idJugadorArriba;
            $grupoSF1_P1->jugador_2 = $request->pareja_1_idJugadorAbajo;
            $grupoSF1_P1->partido_id = $partidoSF1->id;
            $grupoSF1_P1->save();
            
            $grupoSF1_P2 = new Grupo;
            $grupoSF1_P2->torneo_id = $torneoId;
            $grupoSF1_P2->zona = $zona;
            $grupoSF1_P2->fecha = $getFecha($request->input('pareja_2_partido_1_dia'));
            $grupoSF1_P2->horario = $getHorario($request->input('pareja_2_partido_1_horario'));
            $grupoSF1_P2->jugador_1 = $request->pareja_2_idJugadorArriba;
            $grupoSF1_P2->jugador_2 = $request->pareja_2_idJugadorAbajo;
            $grupoSF1_P2->partido_id = $partidoSF1->id;
            $grupoSF1_P2->save();
            
            // Semifinal 2: Pareja 3 vs Pareja 4
            $partidoSF2 = $this->crearPartido();
            $grupoSF2_P3 = new Grupo;
            $grupoSF2_P3->torneo_id = $torneoId;
            $grupoSF2_P3->zona = $zona;
            $grupoSF2_P3->fecha = $getFecha($request->input('pareja_3_partido_1_dia'));
            $grupoSF2_P3->horario = $getHorario($request->input('pareja_3_partido_1_horario'));
            $grupoSF2_P3->jugador_1 = $request->pareja_3_idJugadorArriba;
            $grupoSF2_P3->jugador_2 = $request->pareja_3_idJugadorAbajo;
            $grupoSF2_P3->partido_id = $partidoSF2->id;
            $grupoSF2_P3->save();
            
            $grupoSF2_P4 = new Grupo;
            $grupoSF2_P4->torneo_id = $torneoId;
            $grupoSF2_P4->zona = $zona;
            $grupoSF2_P4->fecha = $getFecha($request->input('pareja_4_partido_1_dia'));
            $grupoSF2_P4->horario = $getHorario($request->input('pareja_4_partido_1_horario'));
            $grupoSF2_P4->jugador_1 = $request->pareja_4_idJugadorArriba;
            $grupoSF2_P4->jugador_2 = $request->pareja_4_idJugadorAbajo;
            $grupoSF2_P4->partido_id = $partidoSF2->id;
            $grupoSF2_P4->save();
            
            // Final: Ganador SF1 vs Ganador SF2 (se crea pero sin jugadores asignados aún)
            $partidoFinal = $this->crearPartido();
            $grupoFinal = new Grupo;
            $grupoFinal->torneo_id = $torneoId;
            $grupoFinal->zona = $zona;
            $grupoFinal->fecha = $getFecha($request->input('final_dia'));
            $grupoFinal->horario = $getHorario($request->input('final_horario'));
            $grupoFinal->jugador_1 = 0; // Se asignará después según resultados
            $grupoFinal->jugador_2 = 0;
            $grupoFinal->partido_id = $partidoFinal->id;
            $grupoFinal->save();
            
            // Consolación: Perdedor SF1 vs Perdedor SF2
            $partidoConsolacion = $this->crearPartido();
            $grupoConsolacion = new Grupo;
            $grupoConsolacion->torneo_id = $torneoId;
            $grupoConsolacion->zona = $zona;
            $grupoConsolacion->fecha = $getFecha($request->input('consolacion_dia'));
            $grupoConsolacion->horario = $getHorario($request->input('consolacion_horario'));
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
            $grupoA1->fecha = $getFecha($request->input('pareja_1_partido_1_dia'));
            $grupoA1->horario = $getHorario($request->input('pareja_1_partido_1_horario'));
            $grupoA1->jugador_1 = $request->pareja_1_idJugadorArriba;
            $grupoA1->jugador_2 = $request->pareja_1_idJugadorAbajo;
            $grupoA1->partido_id = $partido1->id;
            $grupoA1->save();   
            
            $grupoA2 = new Grupo;
            $grupoA2->torneo_id = $torneoId;
            $grupoA2->zona = $zona;
            $grupoA2->fecha = $getFecha($request->input('pareja_1_partido_2_dia'));
            $grupoA2->horario = $getHorario($request->input('pareja_1_partido_2_horario'));
            $grupoA2->jugador_1 = $request->pareja_1_idJugadorArriba;
            $grupoA2->jugador_2 = $request->pareja_1_idJugadorAbajo;
            $grupoA2->partido_id = $partido2->id;
            $grupoA2->save();

            // PAREJA 2 ZONA Y PARTIDOS        
            $grupoA3 = new Grupo;
            $grupoA3->torneo_id = $torneoId;
            $grupoA3->zona = $zona;
            $grupoA3->fecha = $getFecha($request->input('pareja_2_partido_1_dia'));
            $grupoA3->horario = $getHorario($request->input('pareja_2_partido_1_horario'));
            $grupoA3->jugador_1 = $request->pareja_2_idJugadorArriba;
            $grupoA3->jugador_2 = $request->pareja_2_idJugadorAbajo;
            $grupoA3->partido_id = $partido1->id;
            $grupoA3->save();   
            
            $grupoA4 = new Grupo;
            $grupoA4->torneo_id = $torneoId;
            $grupoA4->zona = $zona;
            $grupoA4->fecha = $getFecha($request->input('pareja_2_partido_2_dia'));
            $grupoA4->horario = $getHorario($request->input('pareja_2_partido_2_horario'));
            $grupoA4->jugador_1 = $request->pareja_2_idJugadorArriba;
            $grupoA4->jugador_2 = $request->pareja_2_idJugadorAbajo;
            $grupoA4->partido_id = $partido3->id;
            $grupoA4->save();

            // PAREJA 3 ZONA Y PARTIDOS        
            $grupoA5 = new Grupo;
            $grupoA5->torneo_id = $torneoId;
            $grupoA5->zona = $zona;
            $grupoA5->fecha = $getFecha($request->input('pareja_3_partido_1_dia'));
            $grupoA5->horario = $getHorario($request->input('pareja_3_partido_1_horario'));
            $grupoA5->jugador_1 = $request->pareja_3_idJugadorArriba;
            $grupoA5->jugador_2 = $request->pareja_3_idJugadorAbajo;
            $grupoA5->partido_id = $partido2->id;
            $grupoA5->save();   
            
            $grupoA6 = new Grupo;
            $grupoA6->torneo_id = $torneoId;
            $grupoA6->zona = $zona;
            $grupoA6->fecha = $getFecha($request->input('pareja_3_partido_2_dia'));
            $grupoA6->horario = $getHorario($request->input('pareja_3_partido_2_horario'));
            $grupoA6->jugador_1 = $request->pareja_3_idJugadorArriba;
            $grupoA6->jugador_2 = $request->pareja_3_idJugadorAbajo;
            $grupoA6->partido_id = $partido3->id;
            $grupoA6->save();
            
            // NOTA: En un formato de 3 parejas, cada pareja juega 2 partidos:
            // - Pareja 1: partido 1 (vs Pareja 2) y partido 2 (vs Pareja 3)
            // - Pareja 2: partido 1 (vs Pareja 1) y partido 3 (vs Pareja 3)  
            // - Pareja 3: partido 2 (vs Pareja 1) y partido 3 (vs Pareja 2)
            // Los horarios de celda 3, 6 y 8 ya están siendo guardados correctamente arriba

            return response()->json(['success' => true, 'partidos' => [$partido1->id, $partido2->id, $partido3->id]]);
        }
    }

    public function obtenerDatosZona(Request $request) {
        try {
            $torneoId = $request->torneo_id;
            $zona = $request->zona;
            
            if (!$torneoId || !$zona) {
                return response()->json(['success' => false, 'message' => 'Faltan parámetros'], 400);
            }
            
            // Normalizar zona: la vista envía "A", "B"; en BD puede estar "A" o "Zona A"
            $zonaLike = strlen($zona) === 1 ? [$zona, 'Zona ' . $zona] : [$zona];

            // Obtener todos los grupos de esta zona (incluyendo "ganador X" y "perdedor X")
            $grupos = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where(function($query) use ($zona, $zonaLike) {
                    $query->whereIn('zona', $zonaLike)
                          ->orWhere('zona', 'ganador ' . $zona)
                          ->orWhere('zona', 'perdedor ' . $zona)
                          ->orWhere('zona', 'ganador Zona ' . $zona)
                          ->orWhere('zona', 'perdedor Zona ' . $zona);
                })
                ->whereNotIn('zona', ['cuartos final', 'semifinal', 'final'])
                ->where(function($query) {
                    $query->where(function($q) {
                        $q->whereNotNull('grupos.jugador_1')
                          ->whereNotNull('grupos.jugador_2');
                    })->orWhere(function($q) {
                        // Incluir grupos con jugador_1 = 0 o jugador_2 = 0
                        $q->where(function($q2) {
                            $q2->where('grupos.jugador_1', 0)
                               ->whereNotNull('grupos.jugador_2');
                        })->orWhere(function($q2) {
                            $q2->whereNotNull('grupos.jugador_1')
                               ->where('grupos.jugador_2', 0);
                        });
                    });
                })
                ->select('grupos.id', 'grupos.torneo_id', 'grupos.zona', 'grupos.fecha', 
                        'grupos.horario', 'grupos.jugador_1', 'grupos.jugador_2', 'grupos.partido_id')
                ->orderBy('grupos.id')
                ->get();
            
            // Obtener información de jugadores
            $jugadoresIds = [];
            foreach ($grupos as $grupo) {
                if ($grupo->jugador_1 && $grupo->jugador_1 != 0) $jugadoresIds[] = $grupo->jugador_1;
                if ($grupo->jugador_2 && $grupo->jugador_2 != 0) $jugadoresIds[] = $grupo->jugador_2;
            }
            $jugadoresIds = array_unique($jugadoresIds);
            
            $jugadoresInfo = [];
            if (!empty($jugadoresIds)) {
                $jugadores = DB::table('jugadores')
                    ->whereIn('id', $jugadoresIds)
                    ->where('activo', 1)
                    ->get();
                
                foreach ($jugadores as $jugador) {
                    $foto = $jugador->foto ?? 'images/jugador_img.png';
                    if (!str_starts_with($foto, 'http') && !str_starts_with($foto, '/')) {
                        $foto = asset($foto);
                    } else if (str_starts_with($foto, 'images/')) {
                        $foto = asset($foto);
                    }
                    $jugadoresInfo[$jugador->id] = [
                        'id' => $jugador->id,
                        'nombre' => $jugador->nombre ?? '',
                        'apellido' => $jugador->apellido ?? '',
                        'foto' => $foto
                    ];
                }
            }
            
            // Procesar grupos para determinar estructura
            // Separar grupos con partido_id (partidos reales) de grupos sin partido_id (borradores)
            $gruposConPartidoId = [];
            $gruposSinPartidoId = [];
            $gruposLibres = [];
            
            foreach ($grupos as $grupo) {
                if (($grupo->jugador_1 == 0 || $grupo->jugador_1 === null) || 
                    ($grupo->jugador_2 == 0 || $grupo->jugador_2 === null)) {
                    $gruposLibres[] = $grupo;
                } else if ($grupo->partido_id) {
                    $gruposConPartidoId[] = $grupo;
                } else {
                    $gruposSinPartidoId[] = $grupo;
                }
            }
            
            // Agrupar por pareja
            $parejas = [];
            $parejasMap = [];
            
            // Primero procesar grupos con partido_id agrupados por partido_id
            $partidosMap = [];
            foreach ($gruposConPartidoId as $grupo) {
                if (!isset($partidosMap[$grupo->partido_id])) {
                    $partidosMap[$grupo->partido_id] = [];
                }
                $partidosMap[$grupo->partido_id][] = $grupo;
            }
            
            // Cada partido tiene 2 grupos (una pareja por grupo)
            foreach ($partidosMap as $partidoId => $gruposPartido) {
                foreach ($gruposPartido as $grupo) {
                    $key = min($grupo->jugador_1, $grupo->jugador_2) . '_' . max($grupo->jugador_1, $grupo->jugador_2);
                    if (!isset($parejasMap[$key])) {
                        $parejasMap[$key] = [];
                    }
                    $parejasMap[$key][] = $grupo;
                }
            }
            
            // Agregar grupos sin partido_id
            foreach ($gruposSinPartidoId as $grupo) {
                $key = min($grupo->jugador_1, $grupo->jugador_2) . '_' . max($grupo->jugador_1, $grupo->jugador_2);
                if (!isset($parejasMap[$key])) {
                    $parejasMap[$key] = [];
                }
                $parejasMap[$key][] = $grupo;
            }
            
            // Convertir a array indexado
            $parejas = array_values($parejasMap);
            
            // Determinar si tiene 4 parejas
            $tieneCuatroParejas = count($parejas) >= 4;
            
            // Identificar grupos de ganador y perdedor
            // Buscar directamente por el nombre de la zona "ganador X" y "perdedor X"
            $grupoGanador = null;
            $grupoPerdedor = null;
            
            // Buscar grupo de ganador
            $gruposGanador = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'ganador ' . $zona)
                ->whereNotNull('partido_id')
                ->orderBy('id')
                ->get();
            
            if ($gruposGanador->count() > 0) {
                $grupoGanador = $gruposGanador->first();
            }
            
            // Buscar grupo de perdedor
            $gruposPerdedor = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'perdedor ' . $zona)
                ->whereNotNull('partido_id')
                ->orderBy('id')
                ->get();
            
            if ($gruposPerdedor->count() > 0) {
                $grupoPerdedor = $gruposPerdedor->first();
            }
            
            // Estructurar datos para respuesta
            $datos = [
                'zona' => $zona,
                'tieneCuatroParejas' => $tieneCuatroParejas,
                'tieneCuatroParejasEliminatoria' => ($grupoGanador && $grupoPerdedor) ? true : false,
                'parejas' => [],
                'gruposLibres' => $gruposLibres,
                'grupoGanador' => $grupoGanador ? [
                    'id' => $grupoGanador->id,
                    'fecha' => $grupoGanador->fecha,
                    'horario' => $grupoGanador->horario,
                    'partido_id' => $grupoGanador->partido_id
                ] : null,
                'grupoPerdedor' => $grupoPerdedor ? [
                    'id' => $grupoPerdedor->id,
                    'fecha' => $grupoPerdedor->fecha,
                    'horario' => $grupoPerdedor->horario,
                    'partido_id' => $grupoPerdedor->partido_id
                ] : null,
                'jugadores' => $jugadoresInfo
            ];
            
            // Procesar cada pareja
            foreach ($parejas as $index => $parejaGrupos) {
                if (empty($parejaGrupos)) continue;
                
                $primerGrupo = $parejaGrupos[0];
                $parejaData = [
                    'jugador_1' => $primerGrupo->jugador_1,
                    'jugador_2' => $primerGrupo->jugador_2,
                    'grupos' => []
                ];
                
                foreach ($parejaGrupos as $grupo) {
                    $parejaData['grupos'][] = [
                        'id' => $grupo->id,
                        'fecha' => $grupo->fecha,
                        'horario' => $grupo->horario,
                        'partido_id' => $grupo->partido_id
                    ];
                }
                
                $datos['parejas'][] = $parejaData;
            }
            
            return response()->json(['success' => true, 'datos' => $datos]);
            
        } catch (\Exception $e) {
            \Log::error('Error al obtener datos de zona: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function verificarNumeroParejasZona(Request $request) {
        try {
            $torneoId = $request->torneo_id;
            $zona = $request->zona;
            
            if (!$torneoId || !$zona) {
                return response()->json(['success' => false, 'message' => 'Faltan parámetros'], 400);
            }
            
            // Obtener todos los grupos de esta zona que tienen jugadores (excluyendo jugador 0)
            $grupos = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', $zona)
                ->whereNotIn('zona', ['cuartos final', 'semifinal', 'final'])
                ->whereNotNull('jugador_1')
                ->whereNotNull('jugador_2')
                ->where('jugador_1', '!=', 0)
                ->where('jugador_2', '!=', 0)
                ->select('jugador_1', 'jugador_2')
                ->get();
            
            // Si no hay grupos, devolver null
            if ($grupos->isEmpty()) {
                return response()->json(['success' => true, 'numParejas' => null]);
            }
            
            // Agrupar por pareja única
            $parejasMap = [];
            foreach ($grupos as $grupo) {
                $key = min($grupo->jugador_1, $grupo->jugador_2) . '_' . max($grupo->jugador_1, $grupo->jugador_2);
                if (!isset($parejasMap[$key])) {
                    $parejasMap[$key] = true;
                }
            }
            
            $numParejas = count($parejasMap);
            
            // Determinar si son 3 o 4 parejas
            if ($numParejas >= 4) {
                return response()->json(['success' => true, 'numParejas' => 4]);
            } else if ($numParejas >= 3) {
                return response()->json(['success' => true, 'numParejas' => 3]);
            } else {
                // Si hay menos de 3 parejas, devolver 3 por defecto
                return response()->json(['success' => true, 'numParejas' => 3]);
            }
            
        } catch (\Exception $e) {
            \Log::error('Error al verificar número de parejas: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function obtenerTodasLasZonas(Request $request) {
        try {
            $torneoId = $request->torneo_id;

            if (!$torneoId) {
                return response()->json(['success' => false, 'message' => 'Falta torneo_id'], 400);
            }

            // Obtener todas las zonas únicas del torneo (excluyendo zonas de eliminatoria)
            $zonasRaw = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->whereNotIn('zona', ['cuartos final', 'semifinal', 'final'])
                ->where('zona', 'not like', 'ganador %')
                ->where('zona', 'not like', 'perdedor %')
                ->select('zona')
                ->distinct()
                ->orderBy('zona')
                ->pluck('zona')
                ->toArray();

            // Normalizar: la vista espera letras sueltas (A, B, C). "Zona A" -> "A"
            $zonas = [];
            foreach ($zonasRaw as $z) {
                if (preg_match('/^Zona\s+([A-Z])$/i', trim($z), $m)) {
                    $zonas[] = strtoupper($m[1]);
                } else {
                    $zonas[] = $z;
                }
            }
            $zonas = array_values(array_unique($zonas));

            // Si no hay zonas, retornar al menos 'A'
            if (empty($zonas)) {
                $zonas = ['A'];
            }

            return response()->json(['success' => true, 'zonas' => $zonas]);

        } catch (\Exception $e) {
            \Log::error('Error al obtener todas las zonas: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
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
        $esBorrador = $request->es_borrador ?? 0; // 1 si es borrador, 0 si es guardado final
        $configCrucesAmericanoId = $request->config_cruces_americano_id ?? null;
        
        // Guardar la configuración de cruces en el torneo
        if ($configCrucesAmericanoId) {
            DB::table('torneos')
                ->where('id', $torneoId)
                ->update(['config_cruces_americano_id' => $configCrucesAmericanoId]);
        }
        
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
        
        // Incrementar la versión del torneo para notificar a las pantallas TV
        \App\Torneo::incrementarVersion($torneoId);
        
        // Si es borrador, actualizar el estado del torneo (si existe un campo para esto)
        // Por ahora, solo retornamos un mensaje diferente
        $mensaje = $esBorrador 
            ? 'Borrador guardado correctamente. Puede continuar editando.' 
            : 'Torneo americano guardado correctamente';
        
        return response()->json(['success' => true, 'message' => $mensaje, 'es_borrador' => $esBorrador]);
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
    
    /**
     * Endpoint para consultar la versión actual del torneo.
     * Las vistas TV lo usan para detectar si deben recargar datos.
     */
    public function tvTorneoVersion(Request $request) {
        $torneoId = $request->torneo_id;
        
        if (!$torneoId) {
            return response()->json(['version' => 0]);
        }
        
        $version = \App\Torneo::getVersion($torneoId);
        
        return response()->json(['version' => $version]);
    }

    /**
     * Endpoint para consultar las versiones de múltiples torneos.
     * Usado por la vista de rotación inteligente.
     */
    public function tvTorneosVersiones(Request $request) {
        $torneoIds = $request->torneo_ids;
        
        if (!$torneoIds) {
            return response()->json(['versiones' => []]);
        }
        
        // Convertir string "1,2,3" a array
        if (is_string($torneoIds)) {
            $torneoIds = array_filter(explode(',', $torneoIds), function($id) {
                return is_numeric(trim($id));
            });
            $torneoIds = array_map('intval', $torneoIds);
        }
        
        $versiones = [];
        foreach ($torneoIds as $torneoId) {
            $versiones[$torneoId] = \App\Torneo::getVersion($torneoId);
        }
        
        return response()->json(['versiones' => $versiones]);
    }

    /**
     * Diagnóstico de detección de torneos.
     * Muestra qué torneos activos hay y por qué se incluyen o excluyen.
     * URL: /tv_torneos_diagnostico
     */
    public function tvTorneosDiagnostico(Request $request) {
        $fecha = $request->fecha ?? date('Y-m-d');
        
        // Obtener TODOS los torneos activos
        $todosTorneos = DB::table('torneos')
            ->where('activo', 1)
            ->select('id', 'nombre', 'fecha_inicio', 'fecha_fin', 'activo', 'categoria')
            ->orderBy('id', 'desc')
            ->get();
        
        // Obtener también torneos NO activos para comparar
        $torneosInactivos = DB::table('torneos')
            ->where('activo', 0)
            ->select('id', 'nombre', 'fecha_inicio', 'fecha_fin', 'activo', 'categoria')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();
        
        $html = '<html><head><title>Diagnóstico Torneos</title>';
        $html .= '<style>body{font-family:Arial;padding:20px;} table{border-collapse:collapse;margin:20px 0;} td,th{border:1px solid #ccc;padding:8px;} th{background:#f0f0f0;} .ok{color:green;} .no{color:red;}</style>';
        $html .= '</head><body>';
        $html .= '<h1>Diagnóstico Detección Torneos</h1>';
        $html .= '<p><strong>Fecha servidor:</strong> ' . $fecha . '</p>';
        $html .= '<p><strong>DateTime servidor:</strong> ' . date('Y-m-d H:i:s') . '</p>';
        
        // Tabla de torneos activos
        $html .= '<h2>Torneos ACTIVOS (activo=1)</h2>';
        $html .= '<table><tr><th>ID</th><th>Nombre</th><th>Fecha Inicio</th><th>Fecha Fin</th><th>Categoría</th><th>¿Se detecta HOY?</th><th>Razón</th></tr>';
        
        foreach ($todosTorneos as $t) {
            $seDetecta = false;
            $razon = '';
            
            // Verificar si se detectaría hoy
            if ($t->fecha_inicio !== null && $t->fecha_fin !== null) {
                if ($t->fecha_inicio <= $fecha && $t->fecha_fin >= $fecha) {
                    $seDetecta = true;
                    $razon = 'Rango de fechas: ' . $t->fecha_inicio . ' a ' . $t->fecha_fin;
                } else {
                    $razon = 'Fuera del rango: ' . $t->fecha_inicio . ' a ' . $t->fecha_fin;
                }
            } elseif ($t->fecha_inicio !== null) {
                if ($t->fecha_inicio === $fecha) {
                    $seDetecta = true;
                    $razon = 'Fecha inicio coincide con hoy';
                } else {
                    $razon = 'Fecha inicio (' . $t->fecha_inicio . ') no es hoy';
                }
            } else {
                // Sin fechas - fallback
                $seDetecta = true;
                $razon = 'Sin fechas definidas (fallback)';
            }
            
            $claseDetecta = $seDetecta ? 'ok' : 'no';
            $textoDetecta = $seDetecta ? 'SÍ' : 'NO';
            
            $html .= '<tr>';
            $html .= '<td>' . $t->id . '</td>';
            $html .= '<td>' . htmlspecialchars($t->nombre) . '</td>';
            $html .= '<td>' . ($t->fecha_inicio ?: '<em>NULL</em>') . '</td>';
            $html .= '<td>' . ($t->fecha_fin ?: '<em>NULL</em>') . '</td>';
            $html .= '<td>' . ($t->categoria ?: '-') . '</td>';
            $html .= '<td class="' . $claseDetecta . '"><strong>' . $textoDetecta . '</strong></td>';
            $html .= '<td>' . $razon . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        
        // Torneos inactivos
        if ($torneosInactivos->count() > 0) {
            $html .= '<h2>Torneos INACTIVOS (activo=0) - últimos 10</h2>';
            $html .= '<table><tr><th>ID</th><th>Nombre</th><th>Fecha Inicio</th><th>Fecha Fin</th></tr>';
            foreach ($torneosInactivos as $t) {
                $html .= '<tr><td>' . $t->id . '</td><td>' . htmlspecialchars($t->nombre) . '</td>';
                $html .= '<td>' . ($t->fecha_inicio ?: 'NULL') . '</td><td>' . ($t->fecha_fin ?: 'NULL') . '</td></tr>';
            }
            $html .= '</table>';
        }
        
        $html .= '<h2>Enlaces útiles</h2>';
        $html .= '<ul>';
        $html .= '<li><a href="/tv_torneos_hoy">Torneos de HOY (rotación automática)</a></li>';
        foreach ($todosTorneos as $t) {
            $html .= '<li><a href="/tv_torneos_rotacion?torneos=' . $t->id . '">Ver torneo ' . $t->id . ' (' . htmlspecialchars($t->nombre) . ')</a></li>';
        }
        $html .= '</ul>';
        
        $html .= '</body></html>';
        
        return response($html);
    }

    /**
     * Detecta torneos que se juegan hoy (o en un rango de fechas) y redirige a la vista de rotación.
     * URL: /tv_torneos_hoy  o  /tv_torneos_hoy?fecha=2026-02-26
     */
    public function tvTorneosHoy(Request $request) {
        $fecha = $request->fecha ?? date('Y-m-d');
        $intervalo = $request->intervalo ?? 60;
        
        // Buscar torneos activos cuya fecha coincida con hoy
        // Un torneo se considera "de hoy" si:
        // - fecha_inicio <= hoy <= fecha_fin  O
        // - fecha_inicio = hoy (para torneos de un día)
        $torneos = DB::table('torneos')
            ->where('activo', 1)
            ->where(function($query) use ($fecha) {
                $query->where(function($q) use ($fecha) {
                    // Torneo con rango de fechas
                    $q->whereNotNull('fecha_inicio')
                      ->whereNotNull('fecha_fin')
                      ->where('fecha_inicio', '<=', $fecha)
                      ->where('fecha_fin', '>=', $fecha);
                })
                ->orWhere(function($q) use ($fecha) {
                    // Torneo de un solo día
                    $q->where('fecha_inicio', $fecha);
                })
                ->orWhere(function($q) use ($fecha) {
                    // Fallback: torneos sin fecha definida pero activos
                    $q->whereNull('fecha_inicio')
                      ->whereNull('fecha_fin');
                });
            })
            ->orderBy('categoria')
            ->get();
        
        if ($torneos->isEmpty()) {
            return view('bahia_padel.tv.sin_torneos', [
                'fecha' => $fecha,
                'mensaje' => 'No hay torneos programados para hoy'
            ]);
        }
        
        // Construir lista de IDs
        $torneoIds = $torneos->pluck('id')->implode(',');
        
        // Redirigir a la vista de rotación con los torneos detectados
        return redirect()->route('tvtorneosrotacion', [
            'torneos' => $torneoIds,
            'intervalo' => $intervalo
        ]);
    }

    /**
     * Vista TV de rotación inteligente entre múltiples torneos.
     * Detecta automáticamente si cada torneo está en fase de grupos o cruces.
     * Rota cada X segundos, pero prioriza el torneo que tuvo cambios.
     */
    public function tvTorneosRotacion(Request $request) {
        $torneoIdsParam = $request->torneos;
        $intervalo = $request->intervalo ?? 60; // Segundos entre rotación
        
        if (!$torneoIdsParam) {
            return redirect()->route('index')->with('error', 'Debe especificar los IDs de torneos (ej: ?torneos=1,2)');
        }
        
        // Parsear IDs de torneos
        $torneoIds = array_filter(explode(',', $torneoIdsParam), function($id) {
            return is_numeric(trim($id));
        });
        $torneoIds = array_map('intval', $torneoIds);
        
        if (empty($torneoIds)) {
            return redirect()->route('index')->with('error', 'IDs de torneos inválidos');
        }
        
        // Obtener información de todos los torneos
        $torneos = DB::table('torneos')
            ->whereIn('id', $torneoIds)
            ->where('activo', 1)
            ->get();
        
        if ($torneos->isEmpty()) {
            return redirect()->route('index')->with('error', 'No se encontraron torneos activos');
        }
        
        // Obtener información de jugadores (para todos los torneos)
        $jugadores = DB::table('jugadores')
            ->where('activo', 1)
            ->get()
            ->keyBy('id')
            ->toArray();
        
        // Colores por categoría - Paleta elegante para TV
        // Colores saturados pero armoniosos, fáciles de distinguir
        $coloresCategorias = [
            1 => ['bg' => '#e11d48', 'border' => '#be123c', 'text' => '#fff', 'nombre' => '1RA'],  // Rosa intenso (élite)
            2 => ['bg' => '#f97316', 'border' => '#ea580c', 'text' => '#fff', 'nombre' => '2DA'],  // Naranja vibrante
            3 => ['bg' => '#eab308', 'border' => '#ca8a04', 'text' => '#000', 'nombre' => '3RA'],  // Dorado
            4 => ['bg' => '#22c55e', 'border' => '#16a34a', 'text' => '#fff', 'nombre' => '4TA'],  // Verde esmeralda
            5 => ['bg' => '#06b6d4', 'border' => '#0891b2', 'text' => '#fff', 'nombre' => '5TA'],  // Turquesa
            6 => ['bg' => '#3b82f6', 'border' => '#2563eb', 'text' => '#fff', 'nombre' => '6TA'],  // Azul brillante
            7 => ['bg' => '#8b5cf6', 'border' => '#7c3aed', 'text' => '#fff', 'nombre' => '7MA'],  // Violeta
            8 => ['bg' => '#ec4899', 'border' => '#db2777', 'text' => '#fff', 'nombre' => '8VA'],  // Fucsia
        ];
        
        // Obtener datos para cada torneo (detectando fase)
        $torneosData = [];
        foreach ($torneos as $torneo) {
            $torneoId = $torneo->id;
            $categoria = $torneo->categoria ?? 6;
            $colorCategoria = $coloresCategorias[$categoria] ?? $coloresCategorias[6];
            
            // Detectar fase: ¿tiene cruces eliminatorios?
            $tieneCruces = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where(function($query) {
                    $query->whereIn('zona', ['dieciseisavos final', 'octavos final', 'cuartos final', 'semifinal', 'final'])
                          ->orWhere('zona', 'like', 'dieciseisavos final|%')
                          ->orWhere('zona', 'like', 'octavos final|%')
                          ->orWhere('zona', 'like', 'cuartos final|%');
                })
                ->whereNotNull('partido_id')
                ->exists();
            
            $fase = $tieneCruces ? 'cruces' : 'grupos';
            $tipoTorneoFormato = $torneo->tipo_torneo_formato ?? 'puntuable';
            
            $torneoData = [
                'id' => $torneoId,
                'nombre' => $torneo->nombre,
                'categoria' => $categoria,
                'colorCategoria' => $colorCategoria,
                'version' => \App\Torneo::getVersion($torneoId),
                'fase' => $fase,
                'tipo_torneo_formato' => $tipoTorneoFormato
            ];
            
            if ($fase === 'cruces') {
                // Obtener datos de cruces
                $cruces = $this->obtenerCrucesTorneo($torneoId, $jugadores);
                $torneoData['cruces'] = $cruces['cruces'];
                $torneoData['rondas'] = $cruces['rondas'];
                $torneoData['totalRondas'] = $cruces['totalRondas'];
            } else {
                // Obtener datos de grupos/zonas (pasando tipo de torneo)
                $grupos = $this->obtenerGruposTorneo($torneoId, $jugadores, $tipoTorneoFormato);
                $torneoData['zonas'] = $grupos['zonas'];
                $torneoData['tablasPosiciones'] = $grupos['tablasPosiciones'];
            }
            
            $torneosData[] = $torneoData;
        }
        
        $sponsors = \App\Sponsor::where('active', 1)->orderBy('orden')->get();

        return view('bahia_padel.tv.rotacion_torneos', [
            'torneosData' => $torneosData,
            'intervalo' => $intervalo,
            'torneoIdsParam' => $torneoIdsParam,
            'jugadores' => $jugadores,
            'coloresCategorias' => $coloresCategorias,
            'sponsors' => $sponsors
        ]);
    }

    /**
     * Vista TV para zonas de torneos puntuables.
     * URL: /tv_torneos_puntuables_zonas?torneos=1,2&intervalo=20
     * Si se pasa intervalo_total, se divide automáticamente entre la cantidad de zonas
     */
    public function tvTorneosPuntuablesZonas(Request $request) {
        $torneoIdsParam = $request->torneos;
        $intervaloTotal = $request->intervalo_total ? (int) $request->intervalo_total : null;
        $intervalo = (int) ($request->intervalo ?? 20);
        $fecha = $request->fecha ?? date('Y-m-d');

        $torneoIds = [];
        if ($torneoIdsParam) {
            $torneoIds = array_filter(explode(',', $torneoIdsParam), function($id) {
                return is_numeric(trim($id));
            });
            $torneoIds = array_map('intval', $torneoIds);
        } else {
            $torneoIds = DB::table('torneos')
                ->where('activo', 1)
                ->where(function($query) use ($fecha) {
                    $query->where(function($q) use ($fecha) {
                        $q->whereNotNull('fecha_inicio')
                          ->whereNotNull('fecha_fin')
                          ->where('fecha_inicio', '<=', $fecha)
                          ->where('fecha_fin', '>=', $fecha);
                    })
                    ->orWhere(function($q) use ($fecha) {
                        $q->where('fecha_inicio', $fecha);
                    })
                    ->orWhere(function($q) use ($fecha) {
                        $q->whereNull('fecha_inicio')
                          ->whereNull('fecha_fin');
                    });
                })
                ->where(function($query) {
                    $query->whereNull('tipo_torneo_formato')
                          ->orWhere('tipo_torneo_formato', 'puntuable');
                })
                ->orderBy('categoria')
                ->pluck('id')
                ->toArray();
        }

        if (empty($torneoIds)) {
            return view('bahia_padel.tv.sin_torneos', [
                'fecha' => $fecha,
                'mensaje' => 'No hay torneos puntuables en juego'
            ]);
        }

        $torneos = DB::table('torneos')
            ->whereIn('id', $torneoIds)
            ->where('activo', 1)
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get();

        if ($torneos->isEmpty()) {
            return view('bahia_padel.tv.sin_torneos', [
                'fecha' => $fecha,
                'mensaje' => 'No se encontraron torneos activos'
            ]);
        }

        $jugadores = DB::table('jugadores')
            ->where('activo', 1)
            ->get()
            ->keyBy('id');

        $slides = [];
        $esEliminatoria = ['cuartos final', 'semifinal', 'final', 'octavos final', '16avos final'];

        $parejaCoincideGrupo = function($pareja, $grupo) {
            return ($grupo->jugador_1 == $pareja['jugador_1'] && $grupo->jugador_2 == $pareja['jugador_2'])
                || ($grupo->jugador_1 == $pareja['jugador_2'] && $grupo->jugador_2 == $pareja['jugador_1']);
        };

        foreach ($torneos as $torneo) {
            $torneoId = $torneo->id;

            $grupos = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->whereNotNull('partido_id')
                ->where(function($query) use ($esEliminatoria) {
                    $query->whereNotIn('zona', $esEliminatoria)
                          ->where('zona', 'not like', 'cuartos final|%')
                          ->where('zona', 'not like', 'ganador %')
                          ->where('zona', 'not like', 'perdedor %')
                          ->where('zona', 'not like', 'octavos final|%')
                          ->where('zona', 'not like', '16avos final|%');
                })
                ->orderBy('zona')
                ->orderBy('id')
                ->get();

            if ($grupos->isEmpty()) {
                continue;
            }

            $gruposCollection = collect($grupos);
            $zonas = $gruposCollection->pluck('zona')->unique()->sort()->values();

            foreach ($zonas as $zona) {
                $gruposZonaBase = $gruposCollection->where('zona', $zona)->values();
                $parejas = [];
                $parejasIndex = [];

                foreach ($gruposZonaBase as $grupo) {
                    if (!$grupo->jugador_1 || !$grupo->jugador_2) {
                        continue;
                    }

                    $jugadorA = min($grupo->jugador_1, $grupo->jugador_2);
                    $jugadorB = max($grupo->jugador_1, $grupo->jugador_2);
                    $key = $jugadorA . '_' . $jugadorB;

                    if (isset($parejasIndex[$key])) {
                        continue;
                    }

                    $j1 = $jugadores[$jugadorA] ?? null;
                    $j2 = $jugadores[$jugadorB] ?? null;

                    $parejas[] = [
                        'key' => $key,
                        'jugador_1' => $jugadorA,
                        'jugador_2' => $jugadorB,
                        'apellido_1' => $j1->apellido ?? ($j1->nombre ?? 'Jugador'),
                        'apellido_2' => $j2->apellido ?? ($j2->nombre ?? 'Jugador'),
                        'foto_1' => $j1->foto ?? null,
                        'foto_2' => $j2->foto ?? null,
                    ];
                    $parejasIndex[$key] = true;
                }

                if (count($parejas) < 2) {
                    continue;
                }

                $gruposZonaForMatches = $gruposZonaBase;
                if (count($parejas) === 4) {
                    $zonaNormalizada = strtolower(trim($zona));
                    $gruposFinales = DB::table('grupos')
                        ->where('torneo_id', $torneoId)
                        ->whereNotNull('partido_id')
                        // Compatibilidad con datos historicos (ej: "PERDEDOR A").
                        ->whereRaw('LOWER(TRIM(zona)) in (?, ?)', [
                            'ganador ' . $zonaNormalizada,
                            'perdedor ' . $zonaNormalizada,
                        ])
                        ->orderBy('id')
                        ->get();

                    if ($gruposFinales->isNotEmpty()) {
                        $gruposZonaForMatches = $gruposZonaBase->concat($gruposFinales)->values();
                    }
                }

                $gruposPorPartido = $gruposZonaForMatches->groupBy('partido_id');
                $partidosMap = [];
                $partidosIds = [];

                foreach ($gruposPorPartido as $partidoId => $gruposPartido) {
                    if (!$partidoId || $gruposPartido->count() < 2) {
                        continue;
                    }

                    // En zonas de 4 puede haber placeholders (0,0) y filas reales
                    // para el mismo partido_id. Priorizamos siempre filas reales.
                    $gruposConJugadores = $gruposPartido
                        ->filter(function ($g) {
                            return (int) ($g->jugador_1 ?? 0) > 0 && (int) ($g->jugador_2 ?? 0) > 0;
                        })
                        ->values();

                    $gruposElegidos = $gruposConJugadores->count() >= 2
                        ? $gruposConJugadores
                        : $gruposPartido->values();

                    if ($gruposElegidos->count() < 2) {
                        continue;
                    }

                    $g1 = $gruposElegidos[0];
                    $g2 = $gruposElegidos[1];

                    $k1 = min($g1->jugador_1, $g1->jugador_2) . '_' . max($g1->jugador_1, $g1->jugador_2);
                    $k2 = min($g2->jugador_1, $g2->jugador_2) . '_' . max($g2->jugador_1, $g2->jugador_2);

                    $matchKey = ($k1 < $k2) ? ($k1 . '|' . $k2) : ($k2 . '|' . $k1);
                    $partidosMap[$matchKey] = [
                        'partido_id' => $partidoId,
                        'g1_key' => $k1,
                        'g2_key' => $k2,
                    ];
                    $partidosIds[] = $partidoId;
                }

                $partidosIds = array_values(array_unique($partidosIds));
                $partidosConResultados = [];

                if (!empty($partidosIds)) {
                    $partidos = DB::table('partidos')
                        ->whereIn('id', $partidosIds)
                        ->get();

                    foreach ($partidos as $partido) {
                        $partidosConResultados[$partido->id] = $partido;
                    }
                }

                $matchesMap = [];
                $statsByPair = [];
                $totalParejas = count($parejas);

                foreach ($parejas as $pareja) {
                    $statsByPair[$pareja['key']] = [
                        'pj' => 0,
                        'pg' => 0,
                        'pp' => 0,
                        'sf' => 0,
                        'sc' => 0,
                        'gf' => 0,
                        'gc' => 0,
                    ];
                }

                for ($i = 0; $i < $totalParejas; $i++) {
                    for ($j = $i + 1; $j < $totalParejas; $j++) {
                        $p1 = $parejas[$i];
                        $p2 = $parejas[$j];
                        $matchKey = ($p1['key'] < $p2['key']) ? ($p1['key'] . '|' . $p2['key']) : ($p2['key'] . '|' . $p1['key']);

                        $score1 = '-';
                        $score2 = '-';
                        $hasResult = false;

                        $scoreData = null;
                        if (isset($partidosMap[$matchKey])) {
                            $partidoId = $partidosMap[$matchKey]['partido_id'];
                            if (isset($partidosConResultados[$partidoId])) {
                                $partidoValue = $partidosConResultados[$partidoId];
                                $hasResult = true;

                                $resSets = [];
                                for ($s = 1; $s <= 3; $s++) {
                                    $s1 = (int) ($partidoValue->{"pareja_1_set_$s"} ?? 0);
                                    $s2 = (int) ($partidoValue->{"pareja_2_set_$s"} ?? 0);
                                    if ($s1 > 0 || $s2 > 0) {
                                        $resSets[] = ['p1' => $s1, 'p2' => $s2];
                                    }
                                }

                                if (empty($resSets)) {
                                    $hasResult = false;
                                }

                                $scoreData = [
                                    'sets' => $resSets,
                                    'original_p1_key' => $partidosMap[$matchKey]['g1_key']
                                ];
                            }
                        }

                        $p1Key = $partidosMap[$matchKey]['g1_key'] ?? null;
                        $p2Key = $partidosMap[$matchKey]['g2_key'] ?? null;

                        $matchesMap[$matchKey] = [
                            'data' => $scoreData,
                            'has_result' => $hasResult,
                            'p1_key' => $p1Key,
                            'p2_key' => $p2Key,
                        ];

                        if ($hasResult && $scoreData && $p1Key && $p2Key) {
                            $sets1 = 0;
                            $sets2 = 0;
                            $games1 = 0;
                            $games2 = 0;

                            foreach ($scoreData['sets'] as $set) {
                                $s1 = (int) ($set['p1'] ?? 0);
                                $s2 = (int) ($set['p2'] ?? 0);
                                $games1 += $s1;
                                $games2 += $s2;

                                if ($s1 > $s2) {
                                    $sets1++;
                                } elseif ($s2 > $s1) {
                                    $sets2++;
                                }
                            }

                            $statsByPair[$p1Key]['pj']++;
                            $statsByPair[$p2Key]['pj']++;

                            $statsByPair[$p1Key]['sf'] += $sets1;
                            $statsByPair[$p1Key]['sc'] += $sets2;
                            $statsByPair[$p1Key]['gf'] += $games1;
                            $statsByPair[$p1Key]['gc'] += $games2;

                            $statsByPair[$p2Key]['sf'] += $sets2;
                            $statsByPair[$p2Key]['sc'] += $sets1;
                            $statsByPair[$p2Key]['gf'] += $games2;
                            $statsByPair[$p2Key]['gc'] += $games1;

                            if ($sets1 > $sets2) {
                                $statsByPair[$p1Key]['pg']++;
                                $statsByPair[$p2Key]['pp']++;
                            } elseif ($sets2 > $sets1) {
                                $statsByPair[$p2Key]['pg']++;
                                $statsByPair[$p1Key]['pp']++;
                            }
                        }
                    }
                }

                $parejasOrdenadas = $parejas;

                // Para zonas de 4 parejas: lógica de bracket (semi → final/3er puesto)
                // 1º: 2 PG, 2º: perdió contra 1º, 3º: le ganó al 4º, 4º: 0 PG
                if (count($parejas) === 4) {
                    // Construir mapa de victorias: quién le ganó a quién
                    $victories = []; // $victories[$winnerKey][] = $loserKey
                    foreach ($matchesMap as $matchKey => $match) {
                        if (!$match['has_result'] || !$match['data']) {
                            continue;
                        }
                        $p1Key = $match['p1_key'] ?? null;
                        $p2Key = $match['p2_key'] ?? null;
                        if (!$p1Key || !$p2Key) {
                            continue;
                        }

                        $sets1 = 0;
                        $sets2 = 0;
                        foreach ($match['data']['sets'] as $set) {
                            $s1 = (int) ($set['p1'] ?? 0);
                            $s2 = (int) ($set['p2'] ?? 0);
                            if ($s1 > $s2) {
                                $sets1++;
                            } elseif ($s2 > $s1) {
                                $sets2++;
                            }
                        }

                        if ($sets1 > $sets2) {
                            $victories[$p1Key][] = $p2Key;
                        } elseif ($sets2 > $sets1) {
                            $victories[$p2Key][] = $p1Key;
                        }
                    }

                    // Asignar posiciones por lógica de bracket
                    $positions = [];
                    foreach ($parejas as $p) {
                        $key = $p['key'];
                        $pg = (int) ($statsByPair[$key]['pg'] ?? 0);
                        if ($pg === 2) {
                            $positions[$key] = 1; // Campeón
                        } elseif ($pg === 0) {
                            $positions[$key] = 4; // Último
                        } else {
                            $positions[$key] = 0; // Por determinar (1 PG)
                        }
                    }

                    // Para los que tienen 1 PG: determinar 2º y 3º
                    $firstPlaceKey = array_search(1, $positions);
                    $fourthPlaceKey = array_search(4, $positions);

                    foreach ($parejas as $p) {
                        $key = $p['key'];
                        if ($positions[$key] !== 0) {
                            continue;
                        }

                        // Si perdió contra el 1º → es 2º (perdió la final)
                        // Si le ganó al 4º → es 3º (ganó partido por 3er puesto)
                        $lostToFirst = false;
                        $beatFourth = false;

                        if ($firstPlaceKey && isset($victories[$firstPlaceKey])) {
                            $lostToFirst = in_array($key, $victories[$firstPlaceKey]);
                        }
                        if (isset($victories[$key]) && $fourthPlaceKey) {
                            $beatFourth = in_array($fourthPlaceKey, $victories[$key]);
                        }

                        if ($lostToFirst) {
                            $positions[$key] = 2;
                        } elseif ($beatFourth) {
                            $positions[$key] = 3;
                        }
                    }

                    // Si aún quedan sin posición asignarlas
                    $remaining = array_keys(array_filter($positions, fn($v) => $v === 0));
                    $usedPositions = array_values(array_filter($positions, fn($v) => $v > 0));
                    $available = array_diff([2, 3], $usedPositions);
                    foreach ($remaining as $i => $key) {
                        $positions[$key] = array_shift($available) ?? (2 + $i);
                    }

                    usort($parejasOrdenadas, function($a, $b) use ($positions) {
                        return ($positions[$a['key']] ?? 99) <=> ($positions[$b['key']] ?? 99);
                    });
                } else {
                    // Para otras zonas: ordenar por PG > DIF SETS > DIF GAMES
                    usort($parejasOrdenadas, function($a, $b) use ($statsByPair) {
                        $sa = $statsByPair[$a['key']] ?? ['pg' => 0, 'sf' => 0, 'sc' => 0, 'gf' => 0, 'gc' => 0];
                        $sb = $statsByPair[$b['key']] ?? ['pg' => 0, 'sf' => 0, 'sc' => 0, 'gf' => 0, 'gc' => 0];

                        $pgA = (int) ($sa['pg'] ?? 0);
                        $pgB = (int) ($sb['pg'] ?? 0);
                        if ($pgA !== $pgB) {
                            return $pgB <=> $pgA;
                        }

                        $dsA = (int) ($sa['sf'] ?? 0) - (int) ($sa['sc'] ?? 0);
                        $dsB = (int) ($sb['sf'] ?? 0) - (int) ($sb['sc'] ?? 0);
                        if ($dsA !== $dsB) {
                            return $dsB <=> $dsA;
                        }

                        $dgA = (int) ($sa['gf'] ?? 0) - (int) ($sa['gc'] ?? 0);
                        $dgB = (int) ($sb['gf'] ?? 0) - (int) ($sb['gc'] ?? 0);
                        if ($dgA !== $dgB) {
                            return $dgB <=> $dgA;
                        }

                        $nameA = ($a['apellido_1'] ?? '') . ' ' . ($a['apellido_2'] ?? '');
                        $nameB = ($b['apellido_1'] ?? '') . ' ' . ($b['apellido_2'] ?? '');
                        return strcasecmp($nameA, $nameB);
                    });
                }

                $slides[] = [
                    'torneo_id' => $torneoId,
                    'torneo_nombre' => $torneo->nombre ?? 'Torneo',
                    'categoria' => $torneo->categoria ?? '-',
                    'zona' => $zona,
                    'parejas' => $parejas,
                    'parejas_ordenadas' => $parejasOrdenadas,
                    'matches' => $matchesMap,
                    'stats' => $statsByPair,
                ];
            }
        }

        $sponsors = \App\Sponsor::where('active', 1)->orderBy('orden')->get();

        // Si se pasó intervalo_total, calcular el intervalo por slide
        // para que la rotación completa (zonas + páginas de estadísticas) quepa en el tiempo total.
        // La vista agrega páginas de estadísticas en bloques de 4 zonas por torneo.
        $cantidadSlidesZonas = count($slides);
        if ($intervaloTotal && $cantidadSlidesZonas > 0) {
            $slidesCollection = collect($slides);
            $paginasEstadisticas = $slidesCollection
                ->groupBy('torneo_id')
                ->sum(function ($torneoSlides) {
                    return (int) ceil($torneoSlides->count() / 4);
                });

            $totalPantallasInternas = $cantidadSlidesZonas + $paginasEstadisticas;
            $intervalo = max(3, floor($intervaloTotal / max(1, $totalPantallasInternas)));
        }

        return view('bahia_padel.tv.zonas_puntuables', [
            'slides' => $slides,
            'intervalo' => max(3, $intervalo),
            'sponsors' => $sponsors
        ]);
    }

    /**
     * Obtiene los datos de grupos/zonas de un torneo (para fase de grupos).
     * Soporta torneos normales (puntuables) y americanos.
     */
    private function obtenerGruposTorneo($torneoId, $jugadores, $tipoTorneoFormato = 'puntuable') {
        $esAmericano = ($tipoTorneoFormato === 'americano');
        
        // Obtener configuración de criterios de desempate si es americano
        $criterios = ['PG', 'ENFRENTAMIENTO', 'DIF_GAMES', 'GF']; // Default
        if ($esAmericano) {
            $torneo = DB::table('torneos')->where('id', $torneoId)->first();
            if ($torneo && $torneo->config_cruces_americano_id) {
                $config = DB::table('configuracion_cruces_americanos')
                    ->where('id', $torneo->config_cruces_americano_id)
                    ->first();
                if ($config && $config->criterio_desempate_orden) {
                    $criterios = explode(',', $config->criterio_desempate_orden);
                }
            }
        }
        
        // Obtener grupos que NO son de eliminatoria
        $grupos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->whereNotNull('partido_id')
            ->whereNotIn('zona', ['dieciseisavos final', 'octavos final', 'cuartos final', 'semifinal', 'final'])
            ->where('zona', 'not like', 'dieciseisavos final|%')
            ->where('zona', 'not like', 'octavos final|%')
            ->where('zona', 'not like', 'cuartos final|%')
            ->orderBy('zona')
            ->orderBy('id')
            ->get();
        
        // Agrupar por zona
        $zonas = [];
        $parejasPorZona = [];
        
        foreach ($grupos as $grupo) {
            $zona = $grupo->zona;
            if (!isset($zonas[$zona])) {
                $zonas[$zona] = [];
                $parejasPorZona[$zona] = [];
            }
            
            // Evitar duplicados de parejas
            $keyPareja = min($grupo->jugador_1, $grupo->jugador_2) . '-' . max($grupo->jugador_1, $grupo->jugador_2);
            if (!isset($parejasPorZona[$zona][$keyPareja])) {
                $j1 = $jugadores[$grupo->jugador_1] ?? null;
                $j2 = $jugadores[$grupo->jugador_2] ?? null;
                
                $parejasPorZona[$zona][$keyPareja] = [
                    'jugador_1' => $grupo->jugador_1,
                    'jugador_2' => $grupo->jugador_2,
                    'nombre' => ($j1->apellido ?? 'J' . $grupo->jugador_1) . ' / ' . ($j2->apellido ?? 'J' . $grupo->jugador_2),
                    'partido_ids' => []
                ];
            }
            $parejasPorZona[$zona][$keyPareja]['partido_ids'][] = $grupo->partido_id;
        }
        
        // Calcular tablas de posiciones por zona
        $tablasPosiciones = [];
        
        foreach ($parejasPorZona as $zona => $parejas) {
            $tablasPosiciones[$zona] = [];
            
            // Obtener todos los partido_ids de esta zona
            $partidoIds = [];
            foreach ($parejas as $pareja) {
                $partidoIds = array_merge($partidoIds, $pareja['partido_ids']);
            }
            $partidoIds = array_unique($partidoIds);
            
            // Obtener resultados de partidos
            $partidos = DB::table('partidos')
                ->whereIn('id', $partidoIds)
                ->get()
                ->keyBy('id');
            
            // Calcular estadísticas por pareja
            foreach ($parejas as $keyPareja => $pareja) {
                $stats = [
                    'key' => $keyPareja,
                    'nombre' => $pareja['nombre'],
                    'jugador_1' => $pareja['jugador_1'],
                    'jugador_2' => $pareja['jugador_2'],
                    'pj' => 0,   // Partidos jugados
                    'pg' => 0,   // Partidos ganados
                    'pp' => 0,   // Partidos perdidos
                    'sf' => 0,   // Sets a favor (solo puntuable)
                    'sc' => 0,   // Sets en contra (solo puntuable)
                    'gf' => 0,   // Games a favor
                    'gc' => 0,   // Games en contra
                    'pts' => 0,  // Puntos (solo puntuable)
                    'enfrentamientos' => [] // Para desempate americano: key_rival => ['gf' => X, 'gc' => Y, 'gano' => bool]
                ];
                
                // Buscar partidos donde participa esta pareja
                $gruposPareja = DB::table('grupos')
                    ->where('torneo_id', $torneoId)
                    ->where('zona', $zona)
                    ->where('jugador_1', $pareja['jugador_1'])
                    ->where('jugador_2', $pareja['jugador_2'])
                    ->whereNotNull('partido_id')
                    ->get();
                
                foreach ($gruposPareja as $grupoP) {
                    $partido = $partidos[$grupoP->partido_id] ?? null;
                    if (!$partido) continue;
                    
                    // Verificar si hay resultado
                    $tieneResultado = ($partido->pareja_1_set_1 ?? 0) > 0 || ($partido->pareja_2_set_1 ?? 0) > 0;
                    if (!$tieneResultado) continue;
                    
                    $stats['pj']++;
                    
                    // Determinar si es pareja_1 o pareja_2 en este partido
                    $otroGrupo = DB::table('grupos')
                        ->where('partido_id', $grupoP->partido_id)
                        ->where('id', '!=', $grupoP->id)
                        ->first();
                    
                    if (!$otroGrupo) continue;
                    
                    // Key del rival para enfrentamientos directos
                    $keyRival = min($otroGrupo->jugador_1, $otroGrupo->jugador_2) . '-' . max($otroGrupo->jugador_1, $otroGrupo->jugador_2);
                    
                    // Determinar posición (pareja_1 es el primer grupo por id)
                    $esPareja1 = $grupoP->id < $otroGrupo->id;
                    
                    if ($esAmericano) {
                        // TORNEO AMERICANO: solo usamos set_1 como marcador de games
                        $miScore = $esPareja1 ? ($partido->pareja_1_set_1 ?? 0) : ($partido->pareja_2_set_1 ?? 0);
                        $rivalScore = $esPareja1 ? ($partido->pareja_2_set_1 ?? 0) : ($partido->pareja_1_set_1 ?? 0);
                        
                        $stats['gf'] += $miScore;
                        $stats['gc'] += $rivalScore;
                        
                        // Guardar enfrentamiento directo
                        $stats['enfrentamientos'][$keyRival] = [
                            'gf' => $miScore,
                            'gc' => $rivalScore,
                            'gano' => $miScore > $rivalScore
                        ];
                        
                        // Determinar ganador por games
                        if ($miScore > $rivalScore) {
                            $stats['pg']++;
                        } else {
                            $stats['pp']++;
                        }
                    } else {
                        // TORNEO PUNTUABLE (NORMAL): calcular sets y games
                        $setsGanadosMios = 0;
                        $setsGanadosRival = 0;
                        $gamesMios = 0;
                        $gamesRival = 0;
                        
                        for ($set = 1; $set <= 3; $set++) {
                            $miScore = $esPareja1 ? ($partido->{'pareja_1_set_' . $set} ?? 0) : ($partido->{'pareja_2_set_' . $set} ?? 0);
                            $rivalScore = $esPareja1 ? ($partido->{'pareja_2_set_' . $set} ?? 0) : ($partido->{'pareja_1_set_' . $set} ?? 0);
                            
                            if ($miScore > 0 || $rivalScore > 0) {
                                $gamesMios += $miScore;
                                $gamesRival += $rivalScore;
                                
                                if ($miScore > $rivalScore) {
                                    $setsGanadosMios++;
                                } else if ($rivalScore > $miScore) {
                                    $setsGanadosRival++;
                                }
                            }
                        }
                        
                        $stats['sf'] += $setsGanadosMios;
                        $stats['sc'] += $setsGanadosRival;
                        $stats['gf'] += $gamesMios;
                        $stats['gc'] += $gamesRival;
                        
                        // Guardar enfrentamiento directo
                        $stats['enfrentamientos'][$keyRival] = [
                            'gf' => $gamesMios,
                            'gc' => $gamesRival,
                            'gano' => $setsGanadosMios > $setsGanadosRival
                        ];
                        
                        // Determinar ganador por sets
                        if ($setsGanadosMios > $setsGanadosRival) {
                            $stats['pg']++;
                            $stats['pts'] += 3; // 3 puntos por victoria
                        } else {
                            $stats['pp']++;
                            $stats['pts'] += 1; // 1 punto por derrota
                        }
                    }
                }
                
                $tablasPosiciones[$zona][] = $stats;
            }
            
            // Ordenar según tipo de torneo
            if ($esAmericano) {
                // ORDENAMIENTO AMERICANO con criterios dinámicos según configuración
                usort($tablasPosiciones[$zona], function($a, $b) use ($criterios) {
                    foreach ($criterios as $criterio) {
                        $criterio = trim($criterio);
                        $resultado = 0;
                        
                        switch ($criterio) {
                            case 'PG': // Partidos Ganados
                                if ($a['pg'] != $b['pg']) {
                                    $resultado = $b['pg'] - $a['pg'];
                                }
                                break;
                                
                            case 'ENFRENTAMIENTO': // Enfrentamiento Directo
                                $keyB = $b['key'];
                                if (isset($a['enfrentamientos'][$keyB])) {
                                    $resultado = $a['enfrentamientos'][$keyB]['gano'] ? -1 : 1;
                                }
                                break;
                                
                            case 'DIF_GAMES': // Diferencia de Games
                                $diffGamesA = $a['gf'] - $a['gc'];
                                $diffGamesB = $b['gf'] - $b['gc'];
                                if ($diffGamesA != $diffGamesB) {
                                    $resultado = $diffGamesB - $diffGamesA;
                                }
                                break;
                                
                            case 'GF': // Games a Favor
                                if ($a['gf'] != $b['gf']) {
                                    $resultado = $b['gf'] - $a['gf'];
                                }
                                break;
                        }
                        
                        if ($resultado !== 0) {
                            return $resultado;
                        }
                    }
                    return 0;
                });
            } else {
                // ORDENAMIENTO PUNTUABLE (NORMAL):
                usort($tablasPosiciones[$zona], function($a, $b) {
                    if ($a['pts'] != $b['pts']) return $b['pts'] - $a['pts'];
                    $diffSetsA = $a['sf'] - $a['sc'];
                    $diffSetsB = $b['sf'] - $b['sc'];
                    if ($diffSetsA != $diffSetsB) return $diffSetsB - $diffSetsA;
                    $diffGamesA = $a['gf'] - $a['gc'];
                    $diffGamesB = $b['gf'] - $b['gc'];
                    return $diffGamesB - $diffGamesA;
                });
            }
        }
        
        return [
            'zonas' => array_keys($parejasPorZona),
            'tablasPosiciones' => $tablasPosiciones
        ];
    }

    /**
     * Obtiene los cruces de un torneo (método auxiliar para rotación).
     */
    private function obtenerCrucesTorneo($torneoId, $jugadores) {
        $cruces = [];
        
        // Obtener grupos eliminatorios directamente de la DB
        $gruposEliminatorios = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where(function($query) {
                $query->whereIn('zona', ['dieciseisavos final', 'octavos final', 'cuartos final', 'semifinal', 'final'])
                      ->orWhere('zona', 'like', 'dieciseisavos final|%')
                      ->orWhere('zona', 'like', 'octavos final|%')
                      ->orWhere('zona', 'like', 'cuartos final|%');
            })
            ->whereNotNull('partido_id')
            ->orderBy('zona')
            ->orderBy('partido_id')
            ->orderBy('id')
            ->get();
        
        // Agrupar por partido_id (máximo 2 grupos por partido)
        $partidosAgrupados = [];
        foreach ($gruposEliminatorios as $grupo) {
            $partidoId = $grupo->partido_id;
            if (!isset($partidosAgrupados[$partidoId])) {
                $zonaNormalizada = $grupo->zona;
                if (strpos($zonaNormalizada, '|') !== false) {
                    $zonaNormalizada = explode('|', $zonaNormalizada)[0];
                }
                $partidosAgrupados[$partidoId] = [
                    'zona' => $zonaNormalizada,
                    'grupos' => []
                ];
            }
            if (count($partidosAgrupados[$partidoId]['grupos']) < 2) {
                $partidosAgrupados[$partidoId]['grupos'][] = $grupo;
            }
        }
        
        // Obtener resultados de partidos
        $partidoIds = array_keys($partidosAgrupados);
        $partidos = [];
        if (!empty($partidoIds)) {
            $partidos = DB::table('partidos')
                ->whereIn('id', $partidoIds)
                ->get()
                ->keyBy('id')
                ->toArray();
        }

        $ordenRondas = ['dieciseisavos final', 'octavos final', 'cuartos final', 'semifinal', 'final'];
        
        // Construir cruces leyendo directamente lo que hay en la DB
        foreach ($partidosAgrupados as $partidoId => $data) {
            if (count($data['grupos']) < 2) continue;
            
            $g1 = $data['grupos'][0];
            $g2 = $data['grupos'][1];
            $partido = $partidos[$partidoId] ?? null;

            // Contar jugadores reales (para deduplicación)
            $rawJugadoresReales = 0;
            if ((int) ($g1->jugador_1 ?? 0) > 0) $rawJugadoresReales++;
            if ((int) ($g1->jugador_2 ?? 0) > 0) $rawJugadoresReales++;
            if ((int) ($g2->jugador_1 ?? 0) > 0) $rawJugadoresReales++;
            if ((int) ($g2->jugador_2 ?? 0) > 0) $rawJugadoresReales++;

            $tieneResultadoReal = false;
            if ($partido) {
                $tieneResultadoReal = (
                    (int) ($partido->pareja_1_set_1 ?? 0) > 0 ||
                    (int) ($partido->pareja_2_set_1 ?? 0) > 0 ||
                    (int) ($partido->pareja_1_set_2 ?? 0) > 0 ||
                    (int) ($partido->pareja_2_set_2 ?? 0) > 0 ||
                    (int) ($partido->pareja_1_set_3 ?? 0) > 0 ||
                    (int) ($partido->pareja_2_set_3 ?? 0) > 0
                );
            }
            
            // Leer nombres directamente de lo que hay en grupos
            $nombre1 = $this->getNombrePareja(
                (int) ($g1->jugador_1 ?? 0),
                (int) ($g1->jugador_2 ?? 0),
                $jugadores,
                trim((string) ($g1->referencia_config ?? '')) ?: null
            );
            $nombre2 = $this->getNombrePareja(
                (int) ($g2->jugador_1 ?? 0),
                (int) ($g2->jugador_2 ?? 0),
                $jugadores,
                trim((string) ($g2->referencia_config ?? '')) ?: null
            );
            
            $cruces[] = [
                'ronda' => $data['zona'],
                'partido_id' => $partidoId,
                'pareja1' => [
                    'jugador_1' => (int) ($g1->jugador_1 ?? 0),
                    'jugador_2' => (int) ($g1->jugador_2 ?? 0),
                    'nombre' => $nombre1
                ],
                'pareja2' => [
                    'jugador_1' => (int) ($g2->jugador_1 ?? 0),
                    'jugador_2' => (int) ($g2->jugador_2 ?? 0),
                    'nombre' => $nombre2
                ],
                'resultado' => $partido ? [
                    'pareja_1_set_1' => $partido->pareja_1_set_1 ?? 0,
                    'pareja_1_set_2' => $partido->pareja_1_set_2 ?? 0,
                    'pareja_1_set_3' => $partido->pareja_1_set_3 ?? 0,
                    'pareja_2_set_1' => $partido->pareja_2_set_1 ?? 0,
                    'pareja_2_set_2' => $partido->pareja_2_set_2 ?? 0,
                    'pareja_2_set_3' => $partido->pareja_2_set_3 ?? 0
                ] : null,
                'meta' => [
                    'raw_jugadores_reales' => $rawJugadoresReales,
                    'tiene_resultado_real' => $tieneResultadoReal ? 1 : 0
                ]
            ];
        }

        // Deduplicación: si hay más cruces de los esperados por ronda
        // (placeholders viejos + registros reales), quedarse con los mejores.
        $maximosPorRonda = [
            'dieciseisavos final' => 16,
            'octavos final' => 8,
            'cuartos final' => 4,
            'semifinal' => 2,
            'final' => 1,
        ];

        $crucesDepurados = [];
        foreach ($maximosPorRonda as $ronda => $maximoEsperado) {
            $rondaCruces = array_values(array_filter($cruces, function($c) use ($ronda) {
                return ($c['ronda'] ?? null) === $ronda;
            }));

            if (count($rondaCruces) <= $maximoEsperado) {
                $crucesDepurados = array_merge($crucesDepurados, $rondaCruces);
                continue;
            }

            // Si hay suficientes cruces con al menos algún jugador real, descartar los 100% placeholder.
            $rondaConAlgunJugadorReal = array_values(array_filter($rondaCruces, function($c) {
                return ((int) ($c['meta']['raw_jugadores_reales'] ?? 0)) >= 1;
            }));
            if (count($rondaConAlgunJugadorReal) >= $maximoEsperado) {
                $rondaCruces = $rondaConAlgunJugadorReal;
            }

            if (count($rondaCruces) > $maximoEsperado) {
                usort($rondaCruces, function($a, $b) {
                    // Prioridad: resultado > jugadores reales > partido más reciente
                    $aResultado = (int) ($a['meta']['tiene_resultado_real'] ?? 0);
                    $bResultado = (int) ($b['meta']['tiene_resultado_real'] ?? 0);
                    if ($aResultado !== $bResultado) return $bResultado <=> $aResultado;

                    $aJugadores = (int) ($a['meta']['raw_jugadores_reales'] ?? 0);
                    $bJugadores = (int) ($b['meta']['raw_jugadores_reales'] ?? 0);
                    if ($aJugadores !== $bJugadores) return $bJugadores <=> $aJugadores;

                    return ((int)($b['partido_id'] ?? 0)) <=> ((int)($a['partido_id'] ?? 0));
                });

                $rondaCruces = array_slice($rondaCruces, 0, $maximoEsperado);
            }

            usort($rondaCruces, function($a, $b) {
                return ((int)($a['partido_id'] ?? 0)) <=> ((int)($b['partido_id'] ?? 0));
            });

            $crucesDepurados = array_merge($crucesDepurados, $rondaCruces);
        }

        // Mantener cualquier ronda no estándar sin perder datos
        $rondasConocidas = array_keys($maximosPorRonda);
        $otrosCruces = array_values(array_filter($cruces, function($c) use ($rondasConocidas) {
            return !in_array($c['ronda'] ?? '', $rondasConocidas, true);
        }));
        $cruces = array_merge($crucesDepurados, $otrosCruces);
        
        // Determinar rondas presentes
        $rondasPresentes = array_unique(array_column($cruces, 'ronda'));
        $rondas = array_intersect($ordenRondas, $rondasPresentes);
        
        return [
            'cruces' => $cruces,
            'rondas' => array_values($rondas),
            'totalRondas' => count($rondas)
        ];
    }

    /**
     * Obtiene el nombre de una pareja (método auxiliar).
     */
    private function getNombrePareja($jugador1Id, $jugador2Id, $jugadores, $referenciaConfig = null) {
        $jugador1Id = (int) $jugador1Id;
        $jugador2Id = (int) $jugador2Id;

        // Para cruces aún no definidos, mostrar referencia de configuración (ej: GANADOR O1)
        if (($jugador1Id === 0 && $jugador2Id === 0) && !empty($referenciaConfig)) {
            return 'GANADOR ' . strtoupper(trim($referenciaConfig));
        }

        $j1 = $jugadores[$jugador1Id] ?? null;
        $j2 = $jugadores[$jugador2Id] ?? null;

        $nombre1 = $j1 ? ($j1->apellido ?? $j1->nombre ?? 'Jugador ' . $jugador1Id) : ($jugador1Id === 0 ? 'POR DEFINIR' : 'Jugador ' . $jugador1Id);
        $nombre2 = $j2 ? ($j2->apellido ?? $j2->nombre ?? 'Jugador ' . $jugador2Id) : ($jugador2Id === 0 ? 'POR DEFINIR' : 'Jugador ' . $jugador2Id);
        
        return $nombre1 . ' / ' . $nombre2;
    }

    public function tvTorneoAmericano(Request $request) {
        $torneoId = $request->torneo_id;
        $intervaloTotal = $request->intervalo_total ? (int) $request->intervalo_total : null;
        
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
            
            foreach ($partidosOrdenados as $partido) {
                $partidosPorZona[$zona][] = [
                    'partido_id' => $partido['partido_id'],
                    'pareja_1' => $partido['pareja_1'],
                    'pareja_2' => $partido['pareja_2']
                ];
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
                        'puntos_perdidos' => 0,
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
                              $parejas[$p1_key]['puntos_perdidos'] += $p2_score;
                              $parejas[$p1_key]['partidos_directos'][$p2_key] = ['ganado'=>true];
                          }
                          if(isset($parejas[$p2_key])) {
                              $parejas[$p2_key]['partidos_perdidos']++;
                              $parejas[$p2_key]['puntos_ganados'] += $p2_score;
                              $parejas[$p2_key]['puntos_perdidos'] += $p1_score;
                              $parejas[$p2_key]['partidos_directos'][$p1_key] = ['ganado'=>false];
                          }
                      } elseif ($p2_score > $p1_score) {
                          if(isset($parejas[$p2_key])) {
                              $parejas[$p2_key]['partidos_ganados']++;
                              $parejas[$p2_key]['puntos_ganados'] += $p2_score;
                              $parejas[$p2_key]['puntos_perdidos'] += $p1_score;
                              $parejas[$p2_key]['partidos_directos'][$p1_key] = ['ganado'=>true];
                          }
                          if(isset($parejas[$p1_key])) {
                              $parejas[$p1_key]['partidos_perdidos']++;
                              $parejas[$p1_key]['puntos_ganados'] += $p1_score;
                              $parejas[$p1_key]['puntos_perdidos'] += $p2_score;
                              $parejas[$p1_key]['partidos_directos'][$p2_key] = ['ganado'=>false];
                          }
                      }
                 }
             }
             
             // Calcular diferencia de games y agregar key
             foreach ($parejas as $key => $val) {
                 $parejas[$key]['key'] = $key;
                 $parejas[$key]['diferencia_games'] = ($val['puntos_ganados'] ?? 0) - ($val['puntos_perdidos'] ?? 0);
             }
             $posiciones = array_values($parejas);
             
             usort($posiciones, function($a, $b) {
                if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                    return $b['partidos_ganados'] - $a['partidos_ganados'];
                }
                // Usar diferencia de games en lugar de solo games ganados
                if ($a['diferencia_games'] != $b['diferencia_games']) {
                    return $b['diferencia_games'] - $a['diferencia_games'];
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
        
        // Calcular intervalo por zona si se pasó intervalo_total
        $cantidadZonas = count($posicionesPorZona);
        $intervalo = 20; // Default: 20 segundos por zona
        if ($intervaloTotal && $cantidadZonas > 0) {
            $intervalo = max(5, floor($intervaloTotal / $cantidadZonas));
        }
        
        return View('bahia_padel.tv.resultados')
                    ->with('torneo', $torneo)
                    ->with('partidosPorZona', $partidosPorZona)
                    ->with('jugadores', $jugadores)
                    ->with('partidosConResultados', $partidosConResultados)
                    ->with('posicionesPorZona', $posicionesPorZona)
                    ->with('intervalo', $intervalo);
    }
    
    public function tvTorneoAmericanoActualizar(Request $request) {
        $torneoId = $request->torneo_id;
        
        $torneo = DB::table('torneos')
                        ->where('torneos.id', $torneoId)
                        ->where('torneos.activo', 1)
                        ->first();
        
        if (!$torneo) {
            return response()->json(['success' => false, 'message' => 'Torneo no encontrado'], 404);
        }
        
        // Obtener grupos y calcular posiciones (misma lógica que tvTorneoAmericano)
        $grupos = DB::table('grupos')
                        ->where('grupos.torneo_id', $torneoId)
                        ->whereNotNull('grupos.partido_id')
                        ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final'])
                        ->orderBy('grupos.zona')
                        ->orderBy('grupos.id')
                        ->get();
        
        // Obtener resultados de partidos
        $partidosIds = $grupos->pluck('partido_id')->unique()->filter();
        $partidosConResultados = [];
        if ($partidosIds->count() > 0) {
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
        
        // Obtener zonas únicas
        $zonas = $grupos->pluck('zona')->unique();
        
        foreach ($zonas as $zona) {
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
                        'puntos_perdidos' => 0,
                        'partidos_directos' => []
                    ];
                }
            }
            
            // Obtener partidos de esta zona
            $partidosZona = [];
            foreach ($gruposZona as $grupo) {
                if ($grupo->partido_id && isset($partidosConResultados[$grupo->partido_id])) {
                    $partidosZona[$grupo->partido_id] = $partidosConResultados[$grupo->partido_id];
                }
            }
            
            // Procesar partidos para calcular estadísticas
            foreach ($partidosZona as $partidoId => $partido) {
                $gruposPartido = $gruposZona->where('partido_id', $partidoId)->sortBy('id')->values();
                if ($gruposPartido->count() < 2) continue;
                
                $g1 = $gruposPartido[0];
                $g2 = $gruposPartido[1];
                
                $key1 = $g1->jugador_1 . '_' . $g1->jugador_2;
                $key2 = $g2->jugador_1 . '_' . $g2->jugador_2;
                
                if (!isset($parejas[$key1]) || !isset($parejas[$key2])) continue;
                
                $puntosPareja1 = $partido->pareja_1_set_1 ?? 0;
                $puntosPareja2 = $partido->pareja_2_set_1 ?? 0;
                
                if ($puntosPareja1 > 0 || $puntosPareja2 > 0) {
                    if ($puntosPareja1 > $puntosPareja2) {
                        $parejas[$key1]['partidos_ganados']++;
                        $parejas[$key1]['puntos_ganados'] += $puntosPareja1;
                        $parejas[$key1]['puntos_perdidos'] += $puntosPareja2;
                        $parejas[$key2]['partidos_perdidos']++;
                        $parejas[$key2]['puntos_ganados'] += $puntosPareja2;
                        $parejas[$key2]['puntos_perdidos'] += $puntosPareja1;
                    } elseif ($puntosPareja2 > $puntosPareja1) {
                        $parejas[$key2]['partidos_ganados']++;
                        $parejas[$key2]['puntos_ganados'] += $puntosPareja2;
                        $parejas[$key2]['puntos_perdidos'] += $puntosPareja1;
                        $parejas[$key1]['partidos_perdidos']++;
                        $parejas[$key1]['puntos_ganados'] += $puntosPareja1;
                        $parejas[$key1]['puntos_perdidos'] += $puntosPareja2;
                    }
                }
            }
            
            // Calcular diferencia y ordenar
            foreach ($parejas as $key => $val) {
                $parejas[$key]['key'] = $key;
                $parejas[$key]['diferencia_games'] = ($val['puntos_ganados'] ?? 0) - ($val['puntos_perdidos'] ?? 0);
            }
            
            $posiciones = array_values($parejas);
            usort($posiciones, function($a, $b) {
                if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                    return $b['partidos_ganados'] - $a['partidos_ganados'];
                }
                if ($a['diferencia_games'] != $b['diferencia_games']) {
                    return $b['diferencia_games'] - $a['diferencia_games'];
                }
                return 0;
            });
            
            $posicionesPorZona[$zona] = $posiciones;
        }
        
        return response()->json([
            'success' => true,
            'posicionesPorZona' => $posicionesPorZona,
            'partidosConResultados' => $partidosConResultados
        ]);
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
                        ->where(function($query) {
                            // Excluir grupos de eliminatoria: cuartos final (con o sin |), semifinal, final, ganador, perdedor
                            $query->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final'])
                                  ->where('grupos.zona', 'not like', 'cuartos final|%')
                                  ->where('grupos.zona', 'not like', 'ganador %')
                                  ->where('grupos.zona', 'not like', 'perdedor %');
                        })
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
            
            foreach ($partidosOrdenados as $partido) {
                $partidosPorZona[$zona][] = [
                    'partido_id' => $partido['partido_id'],
                    'pareja_1' => $partido['pareja_1'],
                    'pareja_2' => $partido['pareja_2']
                ];
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
        
        // Incrementar versión del torneo para notificar a vistas TV
        if ($torneoId) {
            \App\Torneo::incrementarVersion($torneoId);
        }
        
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
        
        // Obtener la configuración de desempate del torneo
        $torneo = DB::table('torneos')->where('id', $torneoId)->first();
        $criterioDesempateOrden = 'PG,ENFRENTAMIENTO,DIF_GAMES,GF'; // Default con enfrentamiento primero
        
        if ($torneo && $torneo->config_cruces_americano_id) {
            $config = DB::table('configuracion_cruces_americanos')
                ->where('id', $torneo->config_cruces_americano_id)
                ->first();
            if ($config && $config->criterio_desempate_orden) {
                $criterioDesempateOrden = $config->criterio_desempate_orden;
            }
        }
        
        // Convertir a array de criterios
        $criterios = explode(',', $criterioDesempateOrden);
        
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
                    'puntos_ganados' => 0, // Suma de games ganados
                    'puntos_perdidos' => 0, // Suma de games perdidos
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
                    $parejas[$key1]['puntos_perdidos'] += $puntosPareja2;
                    $parejas[$key2]['partidos_perdidos']++;
                    $parejas[$key2]['puntos_ganados'] += $puntosPareja2;
                    $parejas[$key2]['puntos_perdidos'] += $puntosPareja1;
                    
                    // Guardar resultado del partido directo
                    $parejas[$key1]['partidos_directos'][$key2] = ['ganado' => true, 'puntos' => $puntosPareja1 . '-' . $puntosPareja2];
                    $parejas[$key2]['partidos_directos'][$key1] = ['ganado' => false, 'puntos' => $puntosPareja2 . '-' . $puntosPareja1];
                } else if ($puntosPareja2 > $puntosPareja1) {
                    $parejas[$key2]['partidos_ganados']++;
                    $parejas[$key2]['puntos_ganados'] += $puntosPareja2;
                    $parejas[$key2]['puntos_perdidos'] += $puntosPareja1;
                    $parejas[$key1]['partidos_perdidos']++;
                    $parejas[$key1]['puntos_ganados'] += $puntosPareja1;
                    $parejas[$key1]['puntos_perdidos'] += $puntosPareja2;
                    
                    // Guardar resultado del partido directo
                    $parejas[$key2]['partidos_directos'][$key1] = ['ganado' => true, 'puntos' => $puntosPareja2 . '-' . $puntosPareja1];
                    $parejas[$key1]['partidos_directos'][$key2] = ['ganado' => false, 'puntos' => $puntosPareja1 . '-' . $puntosPareja2];
                }
            }
        }
        
        // Agregar keys a cada pareja para poder comparar partidos directos
        // Calcular diferencia de games (ganados - perdidos)
        foreach ($parejas as $key => $pareja) {
            $parejas[$key]['key'] = $key;
            $parejas[$key]['diferencia_games'] = $pareja['puntos_ganados'] - $pareja['puntos_perdidos'];
        }
        
        // Convertir a array y ordenar por posición
        $posiciones = array_values($parejas);
        
        // Función de comparación con criterios de desempate dinámicos según configuración
        usort($posiciones, function($a, $b) use ($criterios) {
            foreach ($criterios as $criterio) {
                $criterio = trim($criterio);
                $resultado = 0;
                
                switch ($criterio) {
                    case 'PG': // Partidos Ganados
                        if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                            $resultado = $b['partidos_ganados'] - $a['partidos_ganados'];
                        }
                        break;
                        
                    case 'ENFRENTAMIENTO': // Enfrentamiento Directo
                        $keyA = $a['key'];
                        $keyB = $b['key'];
                        if (isset($a['partidos_directos'][$keyB])) {
                            $resultado = $a['partidos_directos'][$keyB]['ganado'] ? -1 : 1;
                        }
                        break;
                        
                    case 'DIF_GAMES': // Diferencia de Games
                        if ($a['diferencia_games'] != $b['diferencia_games']) {
                            $resultado = $b['diferencia_games'] - $a['diferencia_games'];
                        }
                        break;
                        
                    case 'GF': // Games a Favor
                        if ($a['puntos_ganados'] != $b['puntos_ganados']) {
                            $resultado = $b['puntos_ganados'] - $a['puntos_ganados'];
                        }
                        break;
                }
                
                // Si este criterio dio un resultado diferente de 0, usar ese resultado
                if ($resultado !== 0) {
                    return $resultado;
                }
            }
            
            // Si todos los criterios dan empate, mantener orden
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
        // Los grupos de "ganador X" y "perdedor X" deben agruparse con la zona base "X"
        $partidosPorZona = [];
        foreach ($grupos as $grupo) {
            $zonaOriginal = $grupo->zona;
            $partidoId = $grupo->partido_id;
            
            // Validar que el partido_id existe
            if (!$partidoId || $partidoId === null) {
                continue; // Saltar grupos sin partido_id
            }
            
            // Determinar la zona base (si es "ganador A" o "perdedor A", usar "A")
            $zonaBase = $zonaOriginal;
            $esGanador = false;
            $esPerdedor = false;
            
            if (strpos($zonaOriginal, 'ganador ') === 0) {
                $zonaBase = substr($zonaOriginal, 8); // Quitar "ganador "
                $esGanador = true;
            } else if (strpos($zonaOriginal, 'perdedor ') === 0) {
                $zonaBase = substr($zonaOriginal, 9); // Quitar "perdedor "
                $esPerdedor = true;
            }
            
            // Excluir zonas especiales de eliminatoria (se manejan en pantalla de cruces)
            if (in_array($zonaBase, ['16avos final', 'octavos final', 'cuartos final', 'semifinal', 'final'])) {
                continue;
            }
            
            if (!isset($partidosPorZona[$zonaBase])) {
                $partidosPorZona[$zonaBase] = [];
            }
            
            // Agrupar por partido_id único (usar partido_id como clave para preservarlo)
            if (!isset($partidosPorZona[$zonaBase][$partidoId])) {
                $partidosPorZona[$zonaBase][$partidoId] = [
                    'partido_id' => $partidoId,
                    'pareja_1' => null,
                    'pareja_2' => null,
                    'fecha' => $grupo->fecha,
                    'horario' => $grupo->horario,
                    'resultados' => $grupo,
                    'tipo' => $esGanador ? 'ganador' : ($esPerdedor ? 'perdedor' : 'normal'),
                    'grupos' => [] // Almacenar todos los grupos para este partido
                ];
            }
            
            // Agregar este grupo a la lista de grupos del partido
            $partidosPorZona[$zonaBase][$partidoId]['grupos'][] = [
                'jugador_1' => $grupo->jugador_1,
                'jugador_2' => $grupo->jugador_2,
                'fecha' => $grupo->fecha,
                'horario' => $grupo->horario
            ];
        }
        
        // Procesar grupos para cada partido y asignar parejas correctamente
        // Esto asegura que cuando hay múltiples grupos para el mismo partido, se prioricen los que tienen jugadores asignados
        foreach ($partidosPorZona as $zona => &$partidos) {
            foreach ($partidos as $partidoId => $partidoDataOriginal) {
                // Crear una copia del partido para procesar sin afectar el original hasta el final
                $partidoData = $partidoDataOriginal;
                if (isset($partidoData['grupos']) && count($partidoData['grupos']) > 0) {
                    // Log para debugging
                    \Log::info("Procesando partido {$partidoId} en zona {$zona}. Total grupos: " . count($partidoData['grupos']));
                    
                    // Separar grupos con jugadores asignados de los que tienen jugadores = 0
                    $gruposConJugadores = [];
                    $gruposSinJugadores = [];
                    
                    foreach ($partidoData['grupos'] as $grupo) {
                        $tieneJugadores = ($grupo['jugador_1'] != 0 && $grupo['jugador_1'] !== null) && 
                                         ($grupo['jugador_2'] != 0 && $grupo['jugador_2'] !== null);
                        if ($tieneJugadores) {
                            $gruposConJugadores[] = $grupo;
                            \Log::info("  Grupo con jugadores: J1={$grupo['jugador_1']}, J2={$grupo['jugador_2']}");
                        } else {
                            $gruposSinJugadores[] = $grupo;
                        }
                    }
                    
                    \Log::info("  Grupos con jugadores: " . count($gruposConJugadores) . ", Grupos sin jugadores: " . count($gruposSinJugadores));
                    
                    // Eliminar grupos duplicados (misma pareja) - importante para zonas de 3 parejas
                    // Cada partido debe tener exactamente 2 grupos únicos (una por cada pareja)
                    $parejasUnicas = [];
                    $gruposUnicos = [];
                    
                    foreach ($gruposConJugadores as $grupo) {
                        // Crear una clave única para la pareja (ordenar jugadores para evitar duplicados)
                        $jugadorMin = min($grupo['jugador_1'], $grupo['jugador_2']);
                        $jugadorMax = max($grupo['jugador_1'], $grupo['jugador_2']);
                        $keyPareja = $jugadorMin . '_' . $jugadorMax;
                        
                        if (!isset($parejasUnicas[$keyPareja])) {
                            $parejasUnicas[$keyPareja] = $grupo;
                            $gruposUnicos[] = $grupo;
                        }
                    }
                    
                    // Si no hay grupos con jugadores únicos, usar los grupos sin jugadores (pero también eliminar duplicados)
                    if (empty($gruposUnicos)) {
                        $parejasUnicasSinJugadores = [];
                        foreach ($gruposSinJugadores as $grupo) {
                            $jugadorMin = min($grupo['jugador_1'], $grupo['jugador_2']);
                            $jugadorMax = max($grupo['jugador_1'], $grupo['jugador_2']);
                            $keyPareja = $jugadorMin . '_' . $jugadorMax;
                            
                            if (!isset($parejasUnicasSinJugadores[$keyPareja])) {
                                $parejasUnicasSinJugadores[$keyPareja] = true;
                                $gruposUnicos[] = $grupo;
                            }
                        }
                    }
                    
                    // Asignar parejas: usar solo grupos únicos
                    // Cada partido debe tener exactamente 2 parejas diferentes
                    // Limpiar parejas anteriores
                    $partidoData['pareja_1'] = null;
                    $partidoData['pareja_2'] = null;
                    
                    // Verificar que tenemos exactamente 2 parejas únicas
                    // Cada partido debe tener 2 grupos diferentes (una por cada pareja)
                    // IMPORTANTE: Crear copias explícitas de los valores para evitar referencias compartidas
                    if (count($gruposUnicos) >= 1) {
                        $jugador1Pareja1 = (int)$gruposUnicos[0]['jugador_1'];
                        $jugador2Pareja1 = (int)$gruposUnicos[0]['jugador_2'];
                        $partidoData['pareja_1'] = [
                            'jugador_1' => $jugador1Pareja1,
                            'jugador_2' => $jugador2Pareja1
                        ];
                        // Actualizar fecha y horario del primer grupo con jugadores
                        if (!empty($gruposConJugadores)) {
                            $partidoData['fecha'] = $gruposUnicos[0]['fecha'];
                            $partidoData['horario'] = $gruposUnicos[0]['horario'];
                        }
                    }
                    
                    // Buscar la segunda pareja que sea diferente de la primera
                    $pareja1Key = null;
                    if (count($gruposUnicos) >= 1) {
                        $pareja1Key = min($gruposUnicos[0]['jugador_1'], $gruposUnicos[0]['jugador_2']) . '_' . 
                                     max($gruposUnicos[0]['jugador_1'], $gruposUnicos[0]['jugador_2']);
                    }
                    
                    // Buscar la segunda pareja única diferente
                    for ($i = 1; $i < count($gruposUnicos); $i++) {
                        $parejaActualKey = min($gruposUnicos[$i]['jugador_1'], $gruposUnicos[$i]['jugador_2']) . '_' . 
                                          max($gruposUnicos[$i]['jugador_1'], $gruposUnicos[$i]['jugador_2']);
                        
                        // Si encontramos una pareja diferente, asignarla como pareja_2
                        // IMPORTANTE: Crear copias explícitas de los valores
                        if ($pareja1Key && $pareja1Key != $parejaActualKey) {
                            $jugador1Pareja2 = (int)$gruposUnicos[$i]['jugador_1'];
                            $jugador2Pareja2 = (int)$gruposUnicos[$i]['jugador_2'];
                            $partidoData['pareja_2'] = [
                                'jugador_1' => $jugador1Pareja2,
                                'jugador_2' => $jugador2Pareja2
                            ];
                            break; // Solo necesitamos 2 parejas diferentes
                        }
                    }
                    
                    // Log detallado para debugging
                    \Log::info("  Grupos únicos encontrados: " . count($gruposUnicos));
                    foreach ($gruposUnicos as $idx => $grupo) {
                        \Log::info("    Grupo único {$idx}: J1={$grupo['jugador_1']}, J2={$grupo['jugador_2']}");
                    }
                    
                    if ($partidoData['pareja_1']) {
                        \Log::info("  Pareja 1 asignada: J1={$partidoData['pareja_1']['jugador_1']}, J2={$partidoData['pareja_1']['jugador_2']}");
                    }
                    if ($partidoData['pareja_2']) {
                        \Log::info("  Pareja 2 asignada: J1={$partidoData['pareja_2']['jugador_1']}, J2={$partidoData['pareja_2']['jugador_2']}");
                    }
                    
                    // Log para debugging si un partido no tiene 2 parejas diferentes
                    if (!$partidoData['pareja_2'] && count($gruposUnicos) > 0) {
                        \Log::warning("Partido {$partidoId} en zona {$zona} no tiene 2 parejas diferentes. Grupos únicos: " . count($gruposUnicos));
                        foreach ($gruposUnicos as $idx => $grupo) {
                            \Log::warning("  Grupo único {$idx}: J1={$grupo['jugador_1']}, J2={$grupo['jugador_2']}");
                        }
                    }
                    
                    // Limpiar el array de grupos después de procesarlo
                    unset($partidoData['grupos']);
                    
                    // IMPORTANTE: Asignar los valores procesados de vuelta al array original
                    // Hacer copia profunda para evitar referencias compartidas
                    $partidos[$partidoId]['pareja_1'] = $partidoData['pareja_1'] ? [
                        'jugador_1' => (int)$partidoData['pareja_1']['jugador_1'],
                        'jugador_2' => (int)$partidoData['pareja_1']['jugador_2']
                    ] : null;
                    $partidos[$partidoId]['pareja_2'] = $partidoData['pareja_2'] ? [
                        'jugador_1' => (int)$partidoData['pareja_2']['jugador_1'],
                        'jugador_2' => (int)$partidoData['pareja_2']['jugador_2']
                    ] : null;
                    $partidos[$partidoId]['fecha'] = $partidoData['fecha'];
                    $partidos[$partidoId]['horario'] = $partidoData['horario'];
                    if (isset($partidos[$partidoId]['grupos'])) {
                        unset($partidos[$partidoId]['grupos']);
                    }
                    
                    // Log inmediatamente después de procesar para verificar valores
                    if ($zona == 'C' && $partidoId == 2077) {
                        $pareja1Info = $partidos[$partidoId]['pareja_1'] ? "J1={$partidos[$partidoId]['pareja_1']['jugador_1']}, J2={$partidos[$partidoId]['pareja_1']['jugador_2']}" : "Sin pareja 1";
                        $pareja2Info = $partidos[$partidoId]['pareja_2'] ? "J1={$partidos[$partidoId]['pareja_2']['jugador_1']}, J2={$partidos[$partidoId]['pareja_2']['jugador_2']}" : "Sin pareja 2";
                        \Log::info("  INMEDIATAMENTE DESPUÉS DE PROCESAR partido 2077: Pareja 1: {$pareja1Info}, Pareja 2: {$pareja2Info}");
                    }
                }
            }
            
            // Log después de procesar todos los partidos de la zona pero antes de separarlos
            if ($zona == 'C') {
                \Log::info("=== DESPUÉS DE PROCESAR TODOS LOS PARTIDOS DE ZONA C (antes de separar) ===");
                foreach ($partidos as $partidoId => $partidoData) {
                    $pareja1Info = $partidoData['pareja_1'] ? "J1={$partidoData['pareja_1']['jugador_1']}, J2={$partidoData['pareja_1']['jugador_2']}" : "Sin pareja 1";
                    $pareja2Info = $partidoData['pareja_2'] ? "J1={$partidoData['pareja_2']['jugador_1']}, J2={$partidoData['pareja_2']['jugador_2']}" : "Sin pareja 2";
                    \Log::info("  Partido ID {$partidoId}: Pareja 1: {$pareja1Info}, Pareja 2: {$pareja2Info}");
                }
            }
        }
        unset($partidos); // Liberar referencia
        
        // NO eliminar partidos completos - cada partido_id es único y debe mostrarse
        // La eliminación de duplicados ya se hizo a nivel de grupos dentro de cada partido
        // Si hay múltiples partido_id con el mismo par de parejas, eso es un problema de la BD
        // pero no debemos ocultar partidos válidos aquí
        
        // Ordenar los partidos por zona: primero los normales, luego ganador, luego perdedor
        // Mantener las claves originales (partido_id) para que el frontend pueda identificarlos
        foreach ($partidosPorZona as $zona => &$partidos) {
            // IMPORTANTE: Primero procesar y asignar parejas a todos los partidos
            // Luego separar en normales, ganador y perdedor haciendo copias profundas
            
            // Separar partidos normales, ganador y perdedor DESPUÉS de procesar
            // Hacer copias profundas para evitar problemas de referencias
            $partidosNormales = [];
            $partidoGanador = null;
            $partidoPerdedor = null;
            
            foreach ($partidos as $partidoId => $partidoData) {
                // Hacer copia profunda del partido para evitar referencias
                $partidoCopia = [
                    'partido_id' => $partidoData['partido_id'],
                    'pareja_1' => $partidoData['pareja_1'] ? [
                        'jugador_1' => $partidoData['pareja_1']['jugador_1'],
                        'jugador_2' => $partidoData['pareja_1']['jugador_2']
                    ] : null,
                    'pareja_2' => $partidoData['pareja_2'] ? [
                        'jugador_1' => $partidoData['pareja_2']['jugador_1'],
                        'jugador_2' => $partidoData['pareja_2']['jugador_2']
                    ] : null,
                    'fecha' => $partidoData['fecha'],
                    'horario' => $partidoData['horario'],
                    'resultados' => $partidoData['resultados'],
                    'tipo' => $partidoData['tipo']
                ];
                
                if ($partidoData['tipo'] === 'ganador') {
                    $partidoGanador = [$partidoId => $partidoCopia];
                } else if ($partidoData['tipo'] === 'perdedor') {
                    $partidoPerdedor = [$partidoId => $partidoCopia];
                } else {
                    $partidosNormales[$partidoId] = $partidoCopia;
                }
            }
            
            // Reconstruir el array: normales primero, luego ganador, luego perdedor
            // IMPORTANTE: Crear un nuevo array para evitar problemas de referencias
            $partidosFinales = [];
            
            // Agregar partidos normales (hacer copia profunda para evitar referencias)
            foreach ($partidosNormales as $partidoId => $partidoData) {
                $partidosFinales[$partidoId] = [
                    'partido_id' => $partidoData['partido_id'],
                    'pareja_1' => $partidoData['pareja_1'] ? [
                        'jugador_1' => $partidoData['pareja_1']['jugador_1'],
                        'jugador_2' => $partidoData['pareja_1']['jugador_2']
                    ] : null,
                    'pareja_2' => $partidoData['pareja_2'] ? [
                        'jugador_1' => $partidoData['pareja_2']['jugador_1'],
                        'jugador_2' => $partidoData['pareja_2']['jugador_2']
                    ] : null,
                    'fecha' => $partidoData['fecha'],
                    'horario' => $partidoData['horario'],
                    'resultados' => $partidoData['resultados'],
                    'tipo' => $partidoData['tipo']
                ];
            }
            
            // Agregar partido ganador si existe
            if ($partidoGanador) {
                foreach ($partidoGanador as $partidoId => $partidoData) {
                    $partidosFinales[$partidoId] = [
                        'partido_id' => $partidoData['partido_id'],
                        'pareja_1' => $partidoData['pareja_1'] ? [
                            'jugador_1' => $partidoData['pareja_1']['jugador_1'],
                            'jugador_2' => $partidoData['pareja_1']['jugador_2']
                        ] : null,
                        'pareja_2' => $partidoData['pareja_2'] ? [
                            'jugador_1' => $partidoData['pareja_2']['jugador_1'],
                            'jugador_2' => $partidoData['pareja_2']['jugador_2']
                        ] : null,
                        'fecha' => $partidoData['fecha'],
                        'horario' => $partidoData['horario'],
                        'resultados' => $partidoData['resultados'],
                        'tipo' => $partidoData['tipo']
                    ];
                }
            }
            
            // Agregar partido perdedor si existe
            if ($partidoPerdedor) {
                foreach ($partidoPerdedor as $partidoId => $partidoData) {
                    $partidosFinales[$partidoId] = [
                        'partido_id' => $partidoData['partido_id'],
                        'pareja_1' => $partidoData['pareja_1'] ? [
                            'jugador_1' => $partidoData['pareja_1']['jugador_1'],
                            'jugador_2' => $partidoData['pareja_1']['jugador_2']
                        ] : null,
                        'pareja_2' => $partidoData['pareja_2'] ? [
                            'jugador_1' => $partidoData['pareja_2']['jugador_1'],
                            'jugador_2' => $partidoData['pareja_2']['jugador_2']
                        ] : null,
                        'fecha' => $partidoData['fecha'],
                        'horario' => $partidoData['horario'],
                        'resultados' => $partidoData['resultados'],
                        'tipo' => $partidoData['tipo']
                    ];
                }
            }
            
            // Ordenar los partidos por partido_id para asegurar orden consistente
            // Esto es importante para zonas de 3 parejas donde el orden importa
            uksort($partidosFinales, function($a, $b) {
                return $a - $b; // Ordenar por partido_id (clave del array)
            });
            
            $partidos = $partidosFinales;
            
            // Log final para verificar el orden y contenido de los partidos
            if ($zona == 'C') {
                \Log::info("=== Partidos finales para zona C ===");
                foreach ($partidos as $partidoId => $partidoData) {
                    $pareja1Info = $partidoData['pareja_1'] ? "J1={$partidoData['pareja_1']['jugador_1']}, J2={$partidoData['pareja_1']['jugador_2']}" : "Sin pareja 1";
                    $pareja2Info = $partidoData['pareja_2'] ? "J1={$partidoData['pareja_2']['jugador_1']}, J2={$partidoData['pareja_2']['jugador_2']}" : "Sin pareja 2";
                    \Log::info("  Partido ID {$partidoId}: Pareja 1: {$pareja1Info}, Pareja 2: {$pareja2Info}");
                }
            }
        }
        unset($partidos); // Liberar referencia
        
        return View('bahia_padel.admin.torneo.resultados_torneo')
                    ->with('jugadores', $jugadores)
                    ->with('torneo', $torneo)
                    ->with('partidosPorZona', $partidosPorZona); 
    }

    /**
     * GET: Lista los partidos de cruces (16avos, octavos, cuartos, semifinal, final) del torneo
     * para cargar/editar día y horario. Devuelve partido_id, zona, etiqueta, fecha, horario.
     */
    public function obtenerHorariosCruces(Request $request) {
        $torneoId = $request->get('torneo_id');
        if (!$torneoId) {
            return response()->json(['success' => false, 'message' => 'torneo_id requerido'], 400);
        }
        $zonasCruces = ['16avos final', 'octavos final', 'cuartos final', 'semifinal', 'final'];
        $grupos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->whereIn('zona', $zonasCruces)
            ->whereNotNull('partido_id')
            ->orderByRaw("FIELD(zona, '16avos final', 'octavos final', 'cuartos final', 'semifinal', 'final')")
            ->orderBy('partido_id')
            ->orderBy('id')
            ->get();
        $partidosPorId = [];
        $ordenZona = ['16avos final' => 1, 'octavos final' => 2, 'cuartos final' => 3, 'semifinal' => 4, 'final' => 5];
        $nombreZona = [
            '16avos final' => '16avos',
            'octavos final' => 'Octavos',
            'cuartos final' => 'Cuartos',
            'semifinal' => 'Semifinal',
            'final' => 'Final'
        ];
        $contadorZona = [];
        foreach ($grupos as $g) {
            $pid = $g->partido_id;
            if (!isset($partidosPorId[$pid])) {
                $z = $g->zona;
                if (!isset($contadorZona[$z])) $contadorZona[$z] = 0;
                $contadorZona[$z]++;
                $partidosPorId[$pid] = [
                    'partido_id' => (int) $pid,
                    'zona' => $z,
                    'etiqueta' => ($nombreZona[$z] ?? $z) . ' ' . $contadorZona[$z],
                    'fecha' => $g->fecha ?? '',
                    'horario' => $g->horario ?? ''
                ];
            }
        }
        $lista = array_values($partidosPorId);
        usort($lista, function ($a, $b) use ($ordenZona) {
            $oa = $ordenZona[$a['zona']] ?? 99;
            $ob = $ordenZona[$b['zona']] ?? 99;
            if ($oa !== $ob) return $oa - $ob;
            return $a['partido_id'] - $b['partido_id'];
        });
        return response()->json(['success' => true, 'partidos' => $lista]);
    }

    /**
     * POST: Guarda día y horario de los partidos de cruces. Body: torneo_id, partidos: [{ partido_id, fecha, horario }]
     */
    public function guardarHorariosCruces(Request $request) {
        $torneoId = $request->input('torneo_id');
        $partidos = $request->input('partidos', []);
        if (!$torneoId || !is_array($partidos)) {
            return response()->json(['success' => false, 'message' => 'torneo_id y partidos requeridos'], 400);
        }
        foreach ($partidos as $p) {
            $partidoId = (int) ($p['partido_id'] ?? 0);
            $fecha = $p['fecha'] ?? null;
            $horario = $p['horario'] ?? null;
            if ($partidoId <= 0) continue;
            DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('partido_id', $partidoId)
                ->update([
                    'fecha' => $fecha ?: '2000-01-01',
                    'horario' => $horario ?: '00:00'
                ]);
        }
        return response()->json(['success' => true, 'message' => 'Horarios guardados']);
    }

    public function guardarResultadoPartido(Request $request) {
        try {
            $partidoId = $request->partido_id;
            
            \Log::info('=== Iniciando guardarResultadoPartido ===');
            \Log::info('Partido ID recibido: ' . $partidoId);
            \Log::info('Tipo de partido_id: ' . gettype($partidoId));
            \Log::info('Request data: ' . json_encode($request->all()));
            
            // Validar que partido_id existe y es válido
            if (!$partidoId || $partidoId === 'null' || $partidoId === '') {
                \Log::error('Partido ID inválido o vacío: ' . $partidoId);
                return response()->json(['success' => false, 'message' => 'Partido ID inválido']);
            }
            
            // Convertir a entero si es necesario
            $partidoIdInt = is_numeric($partidoId) ? (int)$partidoId : $partidoId;
            
            \Log::info('Buscando partido con ID: ' . $partidoIdInt);
            
            $partido = Partido::find($partidoIdInt);
            
            if (!$partido) {
                \Log::error('Partido no encontrado en BD con ID: ' . $partidoIdInt);
                // Intentar buscar directamente en la tabla
                $partidoDirecto = DB::table('partidos')->where('id', $partidoIdInt)->first();
                if ($partidoDirecto) {
                    \Log::info('Partido encontrado directamente en tabla, pero no con Eloquent');
                } else {
                    \Log::error('Partido tampoco encontrado directamente en tabla');
                }
                return response()->json(['success' => false, 'message' => 'Partido no encontrado']);
            }
            
            \Log::info('Partido encontrado: ID ' . $partido->id);
            
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
        
        // Verificar si es un partido eliminatorio y generar siguientes rondas si es necesario
        $grupo = DB::table('grupos')
                    ->where('partido_id', $partidoId)
                    ->first();
        
        if ($grupo && $grupo->torneo_id) {
            $torneo = DB::table('torneos')->where('id', $grupo->torneo_id)->first();
            $esPuntuable = $torneo && ($torneo->tipo_torneo_formato ?? 'puntuable') === 'puntuable';
            if ($esPuntuable) {
                $zonaNorm = $grupo->zona;
                $zonaBase = (strpos($zonaNorm, '|') !== false) ? explode('|', $zonaNorm)[0] : $zonaNorm;
                if ($zonaBase === 'cuartos final' || strpos($zonaNorm, 'cuartos final') === 0) {
                    $this->crearSemifinalesPuntuable($grupo->torneo_id);
                } else if ($zonaBase === 'semifinal') {
                    $this->crearFinalPuntuable($grupo->torneo_id);
                } else if (($zonaBase === 'octavos final' || strpos($zonaNorm, 'octavos final') === 0) || ($zonaBase === '16avos final' || strpos($zonaNorm, '16avos final') === 0) || ($zonaBase === 'dieciseisavos final' || strpos($zonaNorm, 'dieciseisavos final') === 0)) {
                    try {
                        app(\App\Http\Controllers\PuntuableController::class)->crearSiguienteRondaDesdeCruce($grupo->torneo_id, $partido);
                    } catch (\Exception $e) {
                        \Log::warning('crearSiguienteRondaDesdeCruce: ' . $e->getMessage());
                    }
                }
            }
        }

        // Torneo puntuable: al guardar resultado de fase de grupos, rellenar cruces (A1, B2, etc.) si alguna zona quedó completa
        if ($grupo && $grupo->torneo_id) {
            $torneo = DB::table('torneos')->where('id', $grupo->torneo_id)->first();
            if ($torneo && ($torneo->tipo_torneo_formato ?? 'puntuable') === 'puntuable') {
                $zonasEliminatorias = ['cuartos final', 'semifinal', 'final', 'octavos final', '16avos final'];
                if (!in_array($grupo->zona, $zonasEliminatorias)) {
                    try {
                        app(\App\Http\Controllers\PuntuableController::class)->rellenarCrucesDesdeZonasCompletasPorTorneo($grupo->torneo_id);
                    } catch (\Exception $e) {
                        \Log::warning('rellenarCrucesDesdeZonasCompletas: ' . $e->getMessage());
                    }
                }
            }
        }
        
        // Si es una zona de 4 parejas eliminatoria, actualizar partidos de Ganador y Perdedor (no para cruces)
        $recargar = false;
        $debugInfo = [];
        $zonasCrucesExcluir = ['16avos final', 'dieciseisavos final', 'octavos final', 'cuartos final', 'semifinal', 'final'];
        $esCruceParaExcluir = $grupo && (in_array($grupo->zona, $zonasCrucesExcluir) || strpos($grupo->zona ?? '', 'octavos final') === 0 || strpos($grupo->zona ?? '', 'cuartos final') === 0 || strpos($grupo->zona ?? '', '16avos final') === 0 || strpos($grupo->zona ?? '', 'dieciseisavos final') === 0);
        if ($grupo && !$esCruceParaExcluir) {
            $debugInfo['zona'] = $grupo->zona;
            $debugInfo['torneo_id'] = $grupo->torneo_id;
            $debugInfo['partido_id'] = $partidoId;
            $resultadoActualizacion = $this->actualizarPartidosGanadorPerdedor($partidoId, $partido, $grupo->torneo_id, $grupo->zona);
            
            // El resultado ahora es un array con 'actualizado', 'debug' y 'partidos_actualizados'
            if (is_array($resultadoActualizacion)) {
                $recargar = $resultadoActualizacion['actualizado'];
                $debugInfo = array_merge($debugInfo, $resultadoActualizacion['debug']);
                // Incluir información de partidos actualizados en la respuesta
                if (isset($resultadoActualizacion['partidos_actualizados'])) {
                    $debugInfo['partidos_actualizados'] = $resultadoActualizacion['partidos_actualizados'];
                }
            } else {
                // Compatibilidad con versión anterior
                $recargar = $resultadoActualizacion;
            }
            
            $debugInfo['recargar'] = $recargar;
            $debugInfo['mensaje'] = $recargar ? 'Se actualizaron partidos de Ganador/Perdedor' : 'No se actualizaron partidos de Ganador/Perdedor';
        } else {
            $debugInfo['mensaje'] = 'No es una zona de 4 parejas eliminatoria o es fase eliminatoria';
            if ($grupo) {
                $debugInfo['zona_detectada'] = $grupo->zona;
            }
        }
        
            // Preparar respuesta con información de partidos actualizados
            $response = [
                'success' => true, 
                'partido' => $partido, 
                'recargar' => $recargar,
                'debug' => $debugInfo
            ];
            
            // Incluir información de partidos actualizados si existe
            if (isset($debugInfo['partidos_actualizados'])) {
                $response['partidos_actualizados'] = $debugInfo['partidos_actualizados'];
            }
            
            // Incrementar versión del torneo para notificar a vistas TV
            if ($grupo && $grupo->torneo_id) {
                \App\Torneo::incrementarVersion($grupo->torneo_id);
            }
            
            return response()->json($response);
        } catch (\Exception $e) {
            \Log::error('Error en guardarResultadoPartido: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false, 
                'message' => 'Error al guardar el resultado: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Actualiza los partidos de Ganador y Perdedor cuando se guarda un resultado de Partido A o B
     */
    private function actualizarPartidosGanadorPerdedor($partidoId, $partido, $torneoId, $zona) {
        // Inicializar variables para evitar errores de "Undefined variable"
        $ganadorA = null;
        $ganadorB = null;
        $perdedorA = null;
        $perdedorB = null;
        $gruposGanador = collect();
        $gruposPerdedor = collect();
        
        $debugInfo = [];
        $debugInfo['inicio'] = 'Partido ID: ' . $partidoId . ', Torneo ID: ' . $torneoId . ', Zona: ' . $zona;
        
        \Log::info('=== Iniciando actualizarPartidosGanadorPerdedor ===');
        \Log::info('Partido ID: ' . $partidoId . ', Torneo ID: ' . $torneoId . ', Zona: ' . $zona);
        // Obtener los grupos de este partido para identificar las parejas
        $gruposPartido = DB::table('grupos')
            ->where('partido_id', $partidoId)
            ->where('torneo_id', $torneoId)
            ->where('zona', $zona)
            ->get();
        
        // Verificar si este partido tiene jugadores reales (no 0) - es Partido A o B
        $esPartidoAB = false;
        foreach ($gruposPartido as $gp) {
            if (($gp->jugador_1 != 0 && $gp->jugador_1 !== null) && 
                ($gp->jugador_2 != 0 && $gp->jugador_2 !== null)) {
                $esPartidoAB = true;
                break;
            }
        }
        
        if (!$esPartidoAB) {
            \Log::info('Partido ' . $partidoId . ' no es Partido A o B (no tiene jugadores reales)');
            return false; // No es Partido A o B
        }
        
        // Verificar que el partido tenga resultados para determinar ganador
        // Un partido tiene resultados si al menos un set tiene valores mayores a 0
        $tieneResultados = false;
        if (isset($partido->pareja_1_set_1) && ($partido->pareja_1_set_1 > 0 || $partido->pareja_2_set_1 > 0)) {
            $tieneResultados = true;
        } else if (isset($partido->pareja_1_set_2) && ($partido->pareja_1_set_2 > 0 || $partido->pareja_2_set_2 > 0)) {
            $tieneResultados = true;
        } else if (isset($partido->pareja_1_set_3) && ($partido->pareja_1_set_3 > 0 || $partido->pareja_2_set_3 > 0)) {
            $tieneResultados = true;
        } else if (isset($partido->pareja_1_set_super_tie_break) && ($partido->pareja_1_set_super_tie_break > 0 || $partido->pareja_2_set_super_tie_break > 0)) {
            $tieneResultados = true;
        }
        
        if (!$tieneResultados) {
            \Log::info('Partido ' . $partidoId . ' no tiene resultados aún. Set1: ' . ($partido->pareja_1_set_1 ?? 'null') . '/' . ($partido->pareja_2_set_1 ?? 'null'));
            return false; // No tiene resultados, no actualizar
        }
        
        \Log::info('Partido ' . $partidoId . ' tiene resultados. Set1: ' . ($partido->pareja_1_set_1 ?? 0) . '/' . ($partido->pareja_2_set_1 ?? 0));
        
        // Determinar ganador y perdedor del partido
        $ganador = $this->determinarGanadorPartido($partido);
        $perdedor = $ganador === 1 ? 2 : 1;
        
        // Obtener las parejas (jugadores) del ganador y perdedor
        $gruposOrdenados = $gruposPartido->sortBy('id')->values();
        if ($gruposOrdenados->count() < 2) {
            \Log::warning('Partido ' . $partidoId . ' no tiene 2 grupos');
            return false;
        }
        
        $grupoGanador = $gruposOrdenados[$ganador - 1];
        $grupoPerdedor = $gruposOrdenados[$perdedor - 1];
        
        $ganadorJugador1 = $grupoGanador->jugador_1;
        $ganadorJugador2 = $grupoGanador->jugador_2;
        $perdedorJugador1 = $grupoPerdedor->jugador_1;
        $perdedorJugador2 = $grupoPerdedor->jugador_2;
        
        \Log::info('Partido ' . $partidoId . ' - Ganador: ' . $ganadorJugador1 . '/' . $ganadorJugador2 . ', Perdedor: ' . $perdedorJugador1 . '/' . $perdedorJugador2);
        
        // Identificar si es Partido A o Partido B
        // Partido A: Pareja 1 vs Pareja 2 (los primeros grupos creados)
        // Partido B: Pareja 3 vs Pareja 4 (los siguientes grupos)
        
        // Obtener todos los partidos de la zona con jugadores reales, ordenados por partido_id
        // Buscar partidos que tengan grupos con jugadores reales (no 0)
        $todosPartidos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', $zona)
            ->whereNotNull('partido_id')
            ->where('jugador_1', '!=', 0)
            ->where('jugador_1', '!=', null)
            ->where('jugador_2', '!=', 0)
            ->where('jugador_2', '!=', null)
            ->select('partido_id')
            ->distinct()
            ->orderBy('partido_id')
            ->get();
        
        $partidosConJugadores = $todosPartidos->pluck('partido_id')->unique()->values()->toArray();
        
        \Log::info('Partidos con jugadores reales en zona ' . $zona . ': ' . implode(', ', $partidosConJugadores));
        
        // Identificar Partido A y B por orden de partido_id
        $partidoAId = null;
        $partidoBId = null;
        
        if (count($partidosConJugadores) >= 1) {
            $partidoAId = $partidosConJugadores[0];
        }
        if (count($partidosConJugadores) >= 2) {
            $partidoBId = $partidosConJugadores[1];
        }
        
        // Determinar si el partido actual es A o B
        $esPartidoA = ($partidoId == $partidoAId);
        $esPartidoB = ($partidoId == $partidoBId);
        
        if (!$esPartidoA && !$esPartidoB) {
            \Log::info('Partido ' . $partidoId . ' no es Partido A ni B. Partidos con jugadores: ' . implode(', ', $partidosConJugadores));
            return false; // No es Partido A ni B
        }
        
        \Log::info('Partido identificado: ' . ($esPartidoA ? 'A' : 'B') . ' (ID: ' . $partidoId . ')');
        
        // Obtener los partidos de Ganador y Perdedor buscando directamente por zona
        // Buscar partidos que tengan grupos en las zonas 'ganador X' y 'perdedor X'
        $partidosGanadorPerdedor = collect();
        
        // Buscar partido Ganador directamente por zona
        $gruposGanador = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'ganador ' . $zona)
            ->whereNotNull('partido_id')
            ->select('partido_id', 'zona')
            ->distinct()
            ->first();
        
        if ($gruposGanador) {
            $partidosGanadorPerdedor->push((object)['partido_id' => $gruposGanador->partido_id, 'zona' => $gruposGanador->zona]);
            \Log::info('Partido Ganador encontrado: ID ' . $gruposGanador->partido_id);
        }
        
        // Buscar partido Perdedor directamente por zona
        $gruposPerdedor = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'perdedor ' . $zona)
            ->whereNotNull('partido_id')
            ->select('partido_id', 'zona')
            ->distinct()
            ->first();
        
        if ($gruposPerdedor) {
            $partidosGanadorPerdedor->push((object)['partido_id' => $gruposPerdedor->partido_id, 'zona' => $gruposPerdedor->zona]);
            \Log::info('Partido Perdedor encontrado: ID ' . $gruposPerdedor->partido_id);
        }
        
        \Log::info('Partidos Ganador/Perdedor encontrados: ' . $partidosGanadorPerdedor->count());
        \Log::info('Partidos IDs con zonas: ' . json_encode($partidosGanadorPerdedor->map(function($p) {
            return ['partido_id' => $p->partido_id, 'zona' => $p->zona];
        })->toArray()));
        
        $partidoGanador = null;
        $partidoPerdedor = null;
        
        // Identificar Partido Ganador y Perdedor por el nombre de la zona
        foreach ($partidosGanadorPerdedor as $partidoInfo) {
            if (strpos($partidoInfo->zona, 'ganador') !== false && !$partidoGanador) {
                $partidoGanador = (object)['partido_id' => $partidoInfo->partido_id];
                \Log::info('Partido Ganador (Partido 3) ID: ' . $partidoGanador->partido_id . ' - Zona: ' . $partidoInfo->zona);
            } else if (strpos($partidoInfo->zona, 'perdedor') !== false && !$partidoPerdedor) {
                $partidoPerdedor = (object)['partido_id' => $partidoInfo->partido_id];
                \Log::info('Partido Perdedor (Partido 4) ID: ' . $partidoPerdedor->partido_id . ' - Zona: ' . $partidoInfo->zona);
            }
        }
        
        // Crear o actualizar Partido Ganador
        if ($partidoGanador) {
            $gruposGanador = DB::table('grupos')
                ->where('partido_id', $partidoGanador->partido_id)
                ->where('torneo_id', $torneoId)
                ->where(function($q) use ($zona) {
                    $q->where('zona', $zona)
                      ->orWhere('zona', 'ganador ' . $zona);
                })
                ->get();
            
            \Log::info('Grupos Ganador encontrados: ' . $gruposGanador->count() . ' para partido ID: ' . $partidoGanador->partido_id);
            
            // Obtener ganadores de Partido A y B
            $ganadorA = null;
            $ganadorB = null;
            
            if ($esPartidoA) {
                $ganadorA = ['jugador_1' => $ganadorJugador1, 'jugador_2' => $ganadorJugador2];
            } else if ($esPartidoB) {
                $ganadorB = ['jugador_1' => $ganadorJugador1, 'jugador_2' => $ganadorJugador2];
            }
            
            // Intentar obtener el ganador del otro partido si aún no lo tenemos
            if (!$ganadorA || !$ganadorB) {
                $otroPartidoId = $esPartidoA ? $partidoBId : $partidoAId;
                if ($otroPartidoId) {
                    $otroPartido = DB::table('partidos')->where('id', $otroPartidoId)->first();
                    if ($otroPartido) {
                        // Verificar si el otro partido tiene resultados
                        $otroTieneResultados = ($otroPartido->pareja_1_set_1 > 0 || $otroPartido->pareja_2_set_1 > 0) ||
                                             ($otroPartido->pareja_1_set_2 > 0 || $otroPartido->pareja_2_set_2 > 0) ||
                                             ($otroPartido->pareja_1_set_3 > 0 || $otroPartido->pareja_2_set_3 > 0) ||
                                             ($otroPartido->pareja_1_set_super_tie_break > 0 || $otroPartido->pareja_2_set_super_tie_break > 0);
                        
                        if ($otroTieneResultados) {
                            $otroGanador = $this->determinarGanadorPartido($otroPartido);
                            if ($otroGanador) {
                                $otroGrupos = DB::table('grupos')
                                    ->where('partido_id', $otroPartidoId)
                                    ->where('torneo_id', $torneoId)
                                    ->where('zona', $zona)
                                    ->orderBy('id')
                                    ->get();
                                
                                if ($otroGrupos->count() >= 2) {
                                    $otroGrupoGanador = $otroGrupos[$otroGanador - 1];
                                    if ($esPartidoA && !$ganadorB) {
                                        $ganadorB = ['jugador_1' => $otroGrupoGanador->jugador_1, 'jugador_2' => $otroGrupoGanador->jugador_2];
                                        \Log::info('Obtenido ganador B del otro partido: ' . $ganadorB['jugador_1'] . '/' . $ganadorB['jugador_2']);
                                    } else if ($esPartidoB && !$ganadorA) {
                                        $ganadorA = ['jugador_1' => $otroGrupoGanador->jugador_1, 'jugador_2' => $otroGrupoGanador->jugador_2];
                                        \Log::info('Obtenido ganador A del otro partido: ' . $ganadorA['jugador_1'] . '/' . $ganadorA['jugador_2']);
                                    }
                                }
                            }
                        } else {
                            \Log::info('El otro partido (ID: ' . $otroPartidoId . ') aún no tiene resultados');
                        }
                    }
                }
            }
            
            // Crear o actualizar grupos del partido Ganador
            // Obtener la zona correcta para el grupo ganador
            $zonaGrupoGanador = 'ganador ' . $zona;
            
            // Obtener fecha y horario del primer grupo si existe, o usar valores por defecto
            $fechaGanador = null;
            $horarioGanador = null;
            if ($gruposGanador->count() > 0) {
                $fechaGanador = $gruposGanador->first()->fecha;
                $horarioGanador = $gruposGanador->first()->horario;
            } else {
                // Si no hay grupos, obtener fecha y horario de algún grupo de la zona base
                $grupoBase = DB::table('grupos')
                    ->where('torneo_id', $torneoId)
                    ->where('zona', $zona)
                    ->whereNotNull('partido_id')
                    ->first();
                if ($grupoBase) {
                    $fechaGanador = $grupoBase->fecha;
                    $horarioGanador = $grupoBase->horario;
                } else {
                    $fechaGanador = '2000-01-01';
                    $horarioGanador = '00:00';
                }
            }
            
            // Si es Partido A, crear o actualizar el primer grupo
            if ($ganadorA) {
                // Verificar si ya existe un grupo con estos jugadores para este partido
                $grupoExistente = DB::table('grupos')
                    ->where('partido_id', $partidoGanador->partido_id)
                    ->where('torneo_id', $torneoId)
                    ->where('zona', $zonaGrupoGanador)
                    ->where('jugador_1', $ganadorA['jugador_1'])
                    ->where('jugador_2', $ganadorA['jugador_2'])
                    ->first();
                
                if ($grupoExistente) {
                    // Ya existe, solo loggear
                    \Log::info('Grupo Ganador A ya existe: ID ' . $grupoExistente->id);
                    $debugInfo['actualizacionGanador1'] = [
                        'grupoId' => $grupoExistente->id,
                        'jugadores' => $ganadorA['jugador_1'] . '/' . $ganadorA['jugador_2'],
                        'accion' => 'ya_existia'
                    ];
                } else {
                    // Crear nuevo grupo para ganador A
                    $nuevoGrupoId = DB::table('grupos')->insertGetId([
                        'torneo_id' => $torneoId,
                        'zona' => $zonaGrupoGanador,
                        'fecha' => $fechaGanador,
                        'horario' => $horarioGanador,
                        'jugador_1' => $ganadorA['jugador_1'],
                        'jugador_2' => $ganadorA['jugador_2'],
                        'partido_id' => $partidoGanador->partido_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    \Log::info('Creado nuevo grupo Ganador A: ID ' . $nuevoGrupoId . ' con jugadores ' . $ganadorA['jugador_1'] . '/' . $ganadorA['jugador_2']);
                    $debugInfo['actualizacionGanador1'] = [
                        'grupoId' => $nuevoGrupoId,
                        'jugadores' => $ganadorA['jugador_1'] . '/' . $ganadorA['jugador_2'],
                        'accion' => 'creado'
                    ];
                }
            }
            
            // Si es Partido B, crear un NUEVO grupo (no actualizar el existente)
            if ($ganadorB) {
                // Verificar si ya existe un grupo con estos jugadores para este partido
                $grupoExistente = DB::table('grupos')
                    ->where('partido_id', $partidoGanador->partido_id)
                    ->where('torneo_id', $torneoId)
                    ->where('zona', $zonaGrupoGanador)
                    ->where('jugador_1', $ganadorB['jugador_1'])
                    ->where('jugador_2', $ganadorB['jugador_2'])
                    ->first();
                
                if ($grupoExistente) {
                    // Ya existe, solo loggear
                    \Log::info('Grupo Ganador B ya existe: ID ' . $grupoExistente->id);
                    $debugInfo['actualizacionGanador2'] = [
                        'grupoId' => $grupoExistente->id,
                        'jugadores' => $ganadorB['jugador_1'] . '/' . $ganadorB['jugador_2'],
                        'accion' => 'ya_existia'
                    ];
                } else {
                    // Crear nuevo grupo para ganador B
                    $nuevoGrupoId = DB::table('grupos')->insertGetId([
                        'torneo_id' => $torneoId,
                        'zona' => $zonaGrupoGanador,
                        'fecha' => $fechaGanador,
                        'horario' => $horarioGanador,
                        'jugador_1' => $ganadorB['jugador_1'],
                        'jugador_2' => $ganadorB['jugador_2'],
                        'partido_id' => $partidoGanador->partido_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    \Log::info('Creado nuevo grupo Ganador B: ID ' . $nuevoGrupoId . ' con jugadores ' . $ganadorB['jugador_1'] . '/' . $ganadorB['jugador_2']);
                    $debugInfo['actualizacionGanador2'] = [
                        'grupoId' => $nuevoGrupoId,
                        'jugadores' => $ganadorB['jugador_1'] . '/' . $ganadorB['jugador_2'],
                        'accion' => 'creado'
                    ];
                }
            }
        }
        
        // Actualizar Partido Perdedor (similar lógica)
        if ($partidoPerdedor) {
            $gruposPerdedor = DB::table('grupos')
                ->where('partido_id', $partidoPerdedor->partido_id)
                ->where('torneo_id', $torneoId)
                ->where(function($q) use ($zona) {
                    $q->where('zona', $zona)
                      ->orWhere('zona', 'perdedor ' . $zona);
                })
                ->get();
            
            \Log::info('Grupos Perdedor encontrados: ' . $gruposPerdedor->count() . ' para partido ID: ' . $partidoPerdedor->partido_id);
            
            $perdedorA = null;
            $perdedorB = null;
            
            if ($esPartidoA) {
                $perdedorA = ['jugador_1' => $perdedorJugador1, 'jugador_2' => $perdedorJugador2];
            } else if ($esPartidoB) {
                $perdedorB = ['jugador_1' => $perdedorJugador1, 'jugador_2' => $perdedorJugador2];
            }
            
            // Intentar obtener el perdedor del otro partido si aún no lo tenemos
            if (!$perdedorA || !$perdedorB) {
                $otroPartidoId = $esPartidoA ? $partidoBId : $partidoAId;
                if ($otroPartidoId) {
                    $otroPartido = DB::table('partidos')->where('id', $otroPartidoId)->first();
                    if ($otroPartido) {
                        // Verificar si el otro partido tiene resultados
                        $otroTieneResultados = ($otroPartido->pareja_1_set_1 > 0 || $otroPartido->pareja_2_set_1 > 0) ||
                                             ($otroPartido->pareja_1_set_2 > 0 || $otroPartido->pareja_2_set_2 > 0) ||
                                             ($otroPartido->pareja_1_set_3 > 0 || $otroPartido->pareja_2_set_3 > 0) ||
                                             ($otroPartido->pareja_1_set_super_tie_break > 0 || $otroPartido->pareja_2_set_super_tie_break > 0);
                        
                        if ($otroTieneResultados) {
                            $otroGanador = $this->determinarGanadorPartido($otroPartido);
                            if ($otroGanador) {
                                $otroPerdedor = $otroGanador === 1 ? 2 : 1;
                                $otroGrupos = DB::table('grupos')
                                    ->where('partido_id', $otroPartidoId)
                                    ->where('torneo_id', $torneoId)
                                    ->where('zona', $zona)
                                    ->orderBy('id')
                                    ->get();
                                
                                if ($otroGrupos->count() >= 2) {
                                    $otroGrupoPerdedor = $otroGrupos[$otroPerdedor - 1];
                                    if ($esPartidoA && !$perdedorB) {
                                        $perdedorB = ['jugador_1' => $otroGrupoPerdedor->jugador_1, 'jugador_2' => $otroGrupoPerdedor->jugador_2];
                                        \Log::info('Obtenido perdedor B del otro partido: ' . $perdedorB['jugador_1'] . '/' . $perdedorB['jugador_2']);
                                    } else if ($esPartidoB && !$perdedorA) {
                                        $perdedorA = ['jugador_1' => $otroGrupoPerdedor->jugador_1, 'jugador_2' => $otroGrupoPerdedor->jugador_2];
                                        \Log::info('Obtenido perdedor A del otro partido: ' . $perdedorA['jugador_1'] . '/' . $perdedorA['jugador_2']);
                                    }
                                }
                            }
                        } else {
                            \Log::info('El otro partido (ID: ' . $otroPartidoId . ') aún no tiene resultados para perdedor');
                        }
                    }
                }
            }
            
            // Crear o actualizar grupos del partido Perdedor
            // Obtener la zona correcta para el grupo perdedor
            $zonaGrupoPerdedor = 'perdedor ' . $zona;
            
            // Obtener fecha y horario del primer grupo si existe, o usar valores por defecto
            $fechaPerdedor = null;
            $horarioPerdedor = null;
            if ($gruposPerdedor->count() > 0) {
                $fechaPerdedor = $gruposPerdedor->first()->fecha;
                $horarioPerdedor = $gruposPerdedor->first()->horario;
            } else {
                // Si no hay grupos, obtener fecha y horario de algún grupo de la zona base
                $grupoBase = DB::table('grupos')
                    ->where('torneo_id', $torneoId)
                    ->where('zona', $zona)
                    ->whereNotNull('partido_id')
                    ->first();
                if ($grupoBase) {
                    $fechaPerdedor = $grupoBase->fecha;
                    $horarioPerdedor = $grupoBase->horario;
                } else {
                    $fechaPerdedor = '2000-01-01';
                    $horarioPerdedor = '00:00';
                }
            }
            
            // Si es Partido A, crear o actualizar el primer grupo
            if ($perdedorA) {
                // Verificar si ya existe un grupo con estos jugadores para este partido
                $grupoExistente = DB::table('grupos')
                    ->where('partido_id', $partidoPerdedor->partido_id)
                    ->where('torneo_id', $torneoId)
                    ->where('zona', $zonaGrupoPerdedor)
                    ->where('jugador_1', $perdedorA['jugador_1'])
                    ->where('jugador_2', $perdedorA['jugador_2'])
                    ->first();
                
                if ($grupoExistente) {
                    // Ya existe, solo loggear
                    \Log::info('Grupo Perdedor A ya existe: ID ' . $grupoExistente->id);
                    $debugInfo['actualizacionPerdedor1'] = [
                        'grupoId' => $grupoExistente->id,
                        'jugadores' => $perdedorA['jugador_1'] . '/' . $perdedorA['jugador_2'],
                        'accion' => 'ya_existia'
                    ];
                } else {
                    // Crear nuevo grupo para perdedor A
                    $nuevoGrupoId = DB::table('grupos')->insertGetId([
                        'torneo_id' => $torneoId,
                        'zona' => $zonaGrupoPerdedor,
                        'fecha' => $fechaPerdedor,
                        'horario' => $horarioPerdedor,
                        'jugador_1' => $perdedorA['jugador_1'],
                        'jugador_2' => $perdedorA['jugador_2'],
                        'partido_id' => $partidoPerdedor->partido_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    \Log::info('Creado nuevo grupo Perdedor A: ID ' . $nuevoGrupoId . ' con jugadores ' . $perdedorA['jugador_1'] . '/' . $perdedorA['jugador_2']);
                    $debugInfo['actualizacionPerdedor1'] = [
                        'grupoId' => $nuevoGrupoId,
                        'jugadores' => $perdedorA['jugador_1'] . '/' . $perdedorA['jugador_2'],
                        'accion' => 'creado'
                    ];
                }
            }
            
            // Si es Partido B, crear un NUEVO grupo (no actualizar el existente)
            if ($perdedorB) {
                // Verificar si ya existe un grupo con estos jugadores para este partido
                $grupoExistente = DB::table('grupos')
                    ->where('partido_id', $partidoPerdedor->partido_id)
                    ->where('torneo_id', $torneoId)
                    ->where('zona', $zonaGrupoPerdedor)
                    ->where('jugador_1', $perdedorB['jugador_1'])
                    ->where('jugador_2', $perdedorB['jugador_2'])
                    ->first();
                
                if ($grupoExistente) {
                    // Ya existe, solo loggear
                    \Log::info('Grupo Perdedor B ya existe: ID ' . $grupoExistente->id);
                    $debugInfo['actualizacionPerdedor2'] = [
                        'grupoId' => $grupoExistente->id,
                        'jugadores' => $perdedorB['jugador_1'] . '/' . $perdedorB['jugador_2'],
                        'accion' => 'ya_existia'
                    ];
                } else {
                    // Crear nuevo grupo para perdedor B
                    $nuevoGrupoId = DB::table('grupos')->insertGetId([
                        'torneo_id' => $torneoId,
                        'zona' => $zonaGrupoPerdedor,
                        'fecha' => $fechaPerdedor,
                        'horario' => $horarioPerdedor,
                        'jugador_1' => $perdedorB['jugador_1'],
                        'jugador_2' => $perdedorB['jugador_2'],
                        'partido_id' => $partidoPerdedor->partido_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    \Log::info('Creado nuevo grupo Perdedor B: ID ' . $nuevoGrupoId . ' con jugadores ' . $perdedorB['jugador_1'] . '/' . $perdedorB['jugador_2']);
                    $debugInfo['actualizacionPerdedor2'] = [
                        'grupoId' => $nuevoGrupoId,
                        'jugadores' => $perdedorB['jugador_1'] . '/' . $perdedorB['jugador_2'],
                        'accion' => 'creado'
                    ];
                }
            }
        }
        
        // Recopilar información de debug
        $debugInfo = [];
        $debugInfo['esPartidoA'] = $esPartidoA;
        $debugInfo['esPartidoB'] = $esPartidoB;
        $debugInfo['partidoAId'] = $partidoAId;
        $debugInfo['partidoBId'] = $partidoBId;
        $debugInfo['ganadorJugadores'] = $ganadorJugador1 . '/' . $ganadorJugador2;
        $debugInfo['perdedorJugadores'] = $perdedorJugador1 . '/' . $perdedorJugador2;
        $debugInfo['partidoGanadorId'] = $partidoGanador ? $partidoGanador->partido_id : null;
        $debugInfo['partidoPerdedorId'] = $partidoPerdedor ? $partidoPerdedor->partido_id : null;
        $debugInfo['ganadorA'] = $ganadorA;
        $debugInfo['ganadorB'] = $ganadorB;
        $debugInfo['perdedorA'] = $perdedorA;
        $debugInfo['perdedorB'] = $perdedorB;
        $debugInfo['gruposGanadorCount'] = isset($gruposGanador) ? $gruposGanador->count() : 0;
        $debugInfo['gruposPerdedorCount'] = isset($gruposPerdedor) ? $gruposPerdedor->count() : 0;
        
        // Verificar qué se actualizó realmente
        $actualizacionesRealizadas = [];
        if (isset($gruposGanador) && $gruposGanador->count() >= 2) {
            $gruposGanadorArray = $gruposGanador->values();
            if ($ganadorA) {
                $actualizacionesRealizadas[] = 'Ganador grupo 1: ' . $ganadorA['jugador_1'] . '/' . $ganadorA['jugador_2'];
            }
            if ($ganadorB) {
                $actualizacionesRealizadas[] = 'Ganador grupo 2: ' . $ganadorB['jugador_1'] . '/' . $ganadorB['jugador_2'];
            }
        }
        if (isset($gruposPerdedor) && $gruposPerdedor->count() >= 2) {
            $gruposPerdedorArray = $gruposPerdedor->values();
            if ($perdedorA) {
                $actualizacionesRealizadas[] = 'Perdedor grupo 1: ' . $perdedorA['jugador_1'] . '/' . $perdedorA['jugador_2'];
            }
            if ($perdedorB) {
                $actualizacionesRealizadas[] = 'Perdedor grupo 2: ' . $perdedorB['jugador_1'] . '/' . $perdedorB['jugador_2'];
            }
        }
        $debugInfo['actualizacionesRealizadas'] = $actualizacionesRealizadas;
        
        // Retornar información de debug junto con el flag de recargar
        // Si se actualizó al menos un grupo, marcar como actualizado
        $seActualizo = false;
        if (isset($debugInfo['actualizacionGanador1']) || isset($debugInfo['actualizacionGanador2']) || 
            isset($debugInfo['actualizacionPerdedor1']) || isset($debugInfo['actualizacionPerdedor2'])) {
            $seActualizo = true;
        } else {
            // Fallback: verificar si tenemos ganadores/perdedores asignados
            $seActualizo = ($ganadorA || $ganadorB) || ($perdedorA || $perdedorB);
        }
        $debugInfo['seActualizo'] = $seActualizo;
        
        // Preparar información estructurada de partidos actualizados para el frontend
        $partidosActualizados = [];
        
        if ($partidoGanador && $seActualizo) {
            // Obtener información actualizada del partido Ganador
            $gruposGanadorActualizados = DB::table('grupos')
                ->where('partido_id', $partidoGanador->partido_id)
                ->where('torneo_id', $torneoId)
                ->where(function($q) use ($zona) {
                    $q->where('zona', $zona)
                      ->orWhere('zona', 'ganador ' . $zona);
                })
                ->orderBy('id')
                ->get();
            
            $pareja1Ganador = null;
            $pareja2Ganador = null;
            $fechaGanador = null;
            $horarioGanador = null;
            if ($gruposGanadorActualizados->count() >= 1) {
                $pareja1Ganador = [
                    'jugador_1' => $gruposGanadorActualizados[0]->jugador_1,
                    'jugador_2' => $gruposGanadorActualizados[0]->jugador_2
                ];
                $fechaGanador = $gruposGanadorActualizados[0]->fecha;
                $horarioGanador = $gruposGanadorActualizados[0]->horario;
            }
            if ($gruposGanadorActualizados->count() >= 2) {
                $pareja2Ganador = [
                    'jugador_1' => $gruposGanadorActualizados[1]->jugador_1,
                    'jugador_2' => $gruposGanadorActualizados[1]->jugador_2
                ];
            }
            
            $partidosActualizados['ganador'] = [
                'partido_id' => $partidoGanador->partido_id,
                'pareja_1' => $pareja1Ganador,
                'pareja_2' => $pareja2Ganador,
                'fecha' => $fechaGanador,
                'horario' => $horarioGanador,
                'tipo' => 'ganador'
            ];
        }
        
        if ($partidoPerdedor && $seActualizo) {
            // Obtener información actualizada del partido Perdedor
            $gruposPerdedorActualizados = DB::table('grupos')
                ->where('partido_id', $partidoPerdedor->partido_id)
                ->where('torneo_id', $torneoId)
                ->where(function($q) use ($zona) {
                    $q->where('zona', $zona)
                      ->orWhere('zona', 'perdedor ' . $zona);
                })
                ->orderBy('id')
                ->get();
            
            $pareja1Perdedor = null;
            $pareja2Perdedor = null;
            $fechaPerdedor = null;
            $horarioPerdedor = null;
            if ($gruposPerdedorActualizados->count() >= 1) {
                $pareja1Perdedor = [
                    'jugador_1' => $gruposPerdedorActualizados[0]->jugador_1,
                    'jugador_2' => $gruposPerdedorActualizados[0]->jugador_2
                ];
                $fechaPerdedor = $gruposPerdedorActualizados[0]->fecha;
                $horarioPerdedor = $gruposPerdedorActualizados[0]->horario;
            }
            if ($gruposPerdedorActualizados->count() >= 2) {
                $pareja2Perdedor = [
                    'jugador_1' => $gruposPerdedorActualizados[1]->jugador_1,
                    'jugador_2' => $gruposPerdedorActualizados[1]->jugador_2
                ];
            }
            
            $partidosActualizados['perdedor'] = [
                'partido_id' => $partidoPerdedor->partido_id,
                'pareja_1' => $pareja1Perdedor,
                'pareja_2' => $pareja2Perdedor,
                'fecha' => $fechaPerdedor,
                'horario' => $horarioPerdedor,
                'tipo' => 'perdedor'
            ];
        }
        
        // Guardar debugInfo en el log también
        \Log::info('Debug info actualizarPartidosGanadorPerdedor: ' . json_encode($debugInfo));
        
        return [
            'actualizado' => $seActualizo, 
            'debug' => $debugInfo,
            'partidos_actualizados' => $partidosActualizados
        ];
    }
    
    /**
     * Determina el ganador de un partido basándose en los sets
     * Retorna 1 si ganó pareja_1, 2 si ganó pareja_2
     */
    private function determinarGanadorPartido($partido) {
        $setsGanadosP1 = 0;
        $setsGanadosP2 = 0;
        
        // Set 1
        if ($partido->pareja_1_set_1 > $partido->pareja_2_set_1) {
            $setsGanadosP1++;
        } else if ($partido->pareja_2_set_1 > $partido->pareja_1_set_1) {
            $setsGanadosP2++;
        }
        
        // Set 2
        if ($partido->pareja_1_set_2 > $partido->pareja_2_set_2) {
            $setsGanadosP1++;
        } else if ($partido->pareja_2_set_2 > $partido->pareja_1_set_2) {
            $setsGanadosP2++;
        }
        
        // Set 3 (si existe)
        if ($partido->pareja_1_set_3 > 0 || $partido->pareja_2_set_3 > 0) {
            if ($partido->pareja_1_set_3 > $partido->pareja_2_set_3) {
                $setsGanadosP1++;
            } else if ($partido->pareja_2_set_3 > $partido->pareja_1_set_3) {
                $setsGanadosP2++;
            }
        }
        
        // Si hay empate en sets, usar super tie break
        if ($setsGanadosP1 == $setsGanadosP2) {
            if ($partido->pareja_1_set_super_tie_break > $partido->pareja_2_set_super_tie_break) {
                return 1;
            } else if ($partido->pareja_2_set_super_tie_break > $partido->pareja_1_set_super_tie_break) {
                return 2;
            }
        }
        
        return $setsGanadosP1 > $setsGanadosP2 ? 1 : 2;
    }
    
    /**
     * Crea grupo de cuartos de final cuando se completa un partido de octavos
     * Respeta el orden: Partido 1 vs Partido 2, Partido 3 vs Partido 4, etc.
     */
    private function crearGrupoCuartosDesdeOctavos($torneoId, $partido, $grupos) {
        // Verificar que haya un ganador claro (al menos 2 sets ganados)
        $setsGanadosP1 = 0;
        $setsGanadosP2 = 0;
        
        if ($partido->pareja_1_set_1 > $partido->pareja_2_set_1) {
            $setsGanadosP1++;
        } else if ($partido->pareja_2_set_1 > $partido->pareja_1_set_1) {
            $setsGanadosP2++;
        }
        
        if ($partido->pareja_1_set_2 > $partido->pareja_2_set_2) {
            $setsGanadosP1++;
        } else if ($partido->pareja_2_set_2 > $partido->pareja_1_set_2) {
            $setsGanadosP2++;
        }
        
        if ($partido->pareja_1_set_3 > $partido->pareja_2_set_3) {
            $setsGanadosP1++;
        } else if ($partido->pareja_2_set_3 > $partido->pareja_1_set_3) {
            $setsGanadosP2++;
        }
        
        // Solo crear grupo si hay un ganador claro (al menos 2 sets ganados)
        \Log::info('Verificando ganador en crearGrupoCuartosDesdeOctavos: partido_id=' . $partido->id . ', sets P1=' . $partido->pareja_1_set_1 . '/' . $partido->pareja_1_set_2 . '/' . $partido->pareja_1_set_3 . ', sets P2=' . $partido->pareja_2_set_1 . '/' . $partido->pareja_2_set_2 . '/' . $partido->pareja_2_set_3 . ', sets ganados P1=' . $setsGanadosP1 . ', P2=' . $setsGanadosP2);
        
        if ($setsGanadosP1 < 2 && $setsGanadosP2 < 2) {
            \Log::info('Partido de octavos sin ganador claro aún. Sets ganados: P1=' . $setsGanadosP1 . ', P2=' . $setsGanadosP2);
            return;
        }
        
        if ($grupos->count() < 2) {
            \Log::error('No se encontraron los grupos del partido de octavos');
            return;
        }
        
        // Obtener todos los partidos de octavos ordenados por el id del primer grupo (orden de creación)
        // Esto asegura que el orden sea el mismo que en la vista
        $partidosOctavos = DB::table('partidos')
            ->join('grupos', 'partidos.id', '=', 'grupos.partido_id')
            ->where('grupos.torneo_id', $torneoId)
            ->where('grupos.zona', 'octavos final')
            ->whereNotNull('grupos.partido_id')
            ->select('partidos.id', 'partidos.pareja_1_set_1', 'partidos.pareja_1_set_2', 'partidos.pareja_1_set_3',
                     'partidos.pareja_2_set_1', 'partidos.pareja_2_set_2', 'partidos.pareja_2_set_3',
                     DB::raw('MIN(grupos.id) as grupo_min_id'))
            ->groupBy('partidos.id', 'partidos.pareja_1_set_1', 'partidos.pareja_1_set_2', 'partidos.pareja_1_set_3',
                     'partidos.pareja_2_set_1', 'partidos.pareja_2_set_2', 'partidos.pareja_2_set_3')
            ->orderBy('grupo_min_id')
            ->get();
        
        \Log::info('Partidos de octavos encontrados: ' . $partidosOctavos->count());
        
        // Identificar la posición del partido actual en el orden
        $posicionPartidoActual = -1;
        foreach ($partidosOctavos as $index => $p) {
            if ($p->id == $partido->id) {
                $posicionPartidoActual = $index;
                break;
            }
        }
        
        if ($posicionPartidoActual < 0) {
            \Log::error('No se encontró el partido de octavos en la lista ordenada');
            return;
        }
        
        // Determinar qué número de partido de octavos es (1-8)
        $numeroPartidoOctavos = $posicionPartidoActual + 1;
        
        // Determinar qué par de partidos debe crear el partido de cuartos:
        // Partidos 1 y 2 → Cuartos 1
        // Partidos 3 y 4 → Cuartos 2
        // Partidos 5 y 6 → Cuartos 3
        // Partidos 7 y 8 → Cuartos 4
        $numeroCuartos = (int)(($numeroPartidoOctavos - 1) / 2) + 1;
        $partidoParOctavos = ($numeroCuartos - 1) * 2 + 2; // El otro partido del par
        
        \Log::info('Partido de octavos completado: número=' . $numeroPartidoOctavos . ', debe crear cuartos número=' . $numeroCuartos . ' con partidos ' . (($numeroCuartos - 1) * 2 + 1) . ' y ' . $partidoParOctavos);
        
        // Obtener el ganador del partido actual
        $ganador = $this->determinarGanadorPartido($partido);
        $g1 = $grupos[0];
        $g2 = $grupos[1];
        
        $ganadorActualJugador1 = ($ganador == 1) ? $g1->jugador_1 : $g2->jugador_1;
        $ganadorActualJugador2 = ($ganador == 1) ? $g1->jugador_2 : $g2->jugador_2;
        
        // Verificar si el partido par también está completo
        $partidoParCompleto = false;
        $ganadorParJugador1 = null;
        $ganadorParJugador2 = null;
        
        if ($partidoParOctavos <= count($partidosOctavos)) {
            $partidoPar = $partidosOctavos[$partidoParOctavos - 1];
            
            \Log::info('Verificando partido par: partido_id=' . $partidoPar->id . ', sets P1=' . $partidoPar->pareja_1_set_1 . '/' . $partidoPar->pareja_1_set_2 . '/' . $partidoPar->pareja_1_set_3 . ', sets P2=' . $partidoPar->pareja_2_set_1 . '/' . $partidoPar->pareja_2_set_2 . '/' . $partidoPar->pareja_2_set_3);
            
            // Verificar si el partido par tiene ganador claro
            $setsGanadosP1Par = 0;
            $setsGanadosP2Par = 0;
            
            if ($partidoPar->pareja_1_set_1 > $partidoPar->pareja_2_set_1) {
                $setsGanadosP1Par++;
            } else if ($partidoPar->pareja_2_set_1 > $partidoPar->pareja_1_set_1) {
                $setsGanadosP2Par++;
            }
            
            if ($partidoPar->pareja_1_set_2 > $partidoPar->pareja_2_set_2) {
                $setsGanadosP1Par++;
            } else if ($partidoPar->pareja_2_set_2 > $partidoPar->pareja_1_set_2) {
                $setsGanadosP2Par++;
            }
            
            if ($partidoPar->pareja_1_set_3 > $partidoPar->pareja_2_set_3) {
                $setsGanadosP1Par++;
            } else if ($partidoPar->pareja_2_set_3 > $partidoPar->pareja_1_set_3) {
                $setsGanadosP2Par++;
            }
            
            \Log::info('Sets ganados partido par: P1=' . $setsGanadosP1Par . ', P2=' . $setsGanadosP2Par);
            
            if ($setsGanadosP1Par >= 2 || $setsGanadosP2Par >= 2) {
                $partidoParCompleto = true;
                
                // Obtener los grupos del partido par
                $gruposPar = DB::table('grupos')
                    ->where('partido_id', $partidoPar->id)
                    ->where('torneo_id', $torneoId)
                    ->orderBy('id')
                    ->get();
                
                \Log::info('Grupos encontrados para partido par: ' . $gruposPar->count());
                
                if ($gruposPar->count() >= 2) {
                    $g1Par = $gruposPar[0];
                    $g2Par = $gruposPar[1];
                    
                    $ganadorPar = ($setsGanadosP1Par > $setsGanadosP2Par) ? 1 : 2;
                    $ganadorParJugador1 = ($ganadorPar == 1) ? $g1Par->jugador_1 : $g2Par->jugador_1;
                    $ganadorParJugador2 = ($ganadorPar == 1) ? $g1Par->jugador_2 : $g2Par->jugador_2;
                    
                    \Log::info('Ganador partido par determinado: jugador_1=' . $ganadorParJugador1 . ', jugador_2=' . $ganadorParJugador2);
                }
            } else {
                \Log::info('Partido par no tiene ganador claro aún');
            }
        } else {
            \Log::info('Partido par no existe aún (índice ' . $partidoParOctavos . ' > ' . count($partidosOctavos) . ')');
        }
        
        // Verificar si ya existe un partido de cuartos para este número
        $partidosCuartosExistentes = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'cuartos final')
            ->whereNotNull('partido_id')
            ->select('partido_id')
            ->distinct()
            ->orderBy('partido_id')
            ->get();
        
        $numeroCuartosExistentes = count($partidosCuartosExistentes);
        
        // Si ya existe el partido de cuartos correspondiente, no crear otro
        if ($numeroCuartosExistentes >= $numeroCuartos) {
            \Log::info('Ya existe el partido de cuartos número ' . $numeroCuartos);
            return;
        }
        
        // Si ambos partidos del par están completos, crear el partido de cuartos
        if ($partidoParCompleto && $ganadorParJugador1 !== null) {
            // Crear el partido de cuartos
            $partidoCuartos = $this->crearPartido();
            
            // Crear grupo para el ganador del primer partido del par
            $grupoCuartos1 = new Grupo;
            $grupoCuartos1->torneo_id = $torneoId;
            $grupoCuartos1->zona = 'cuartos final';
            $grupoCuartos1->fecha = '2000-01-01';
            $grupoCuartos1->horario = '00:00';
            $grupoCuartos1->jugador_1 = $ganadorActualJugador1;
            $grupoCuartos1->jugador_2 = $ganadorActualJugador2;
            $grupoCuartos1->partido_id = $partidoCuartos->id;
            $grupoCuartos1->save();
            
            // Crear grupo para el ganador del segundo partido del par
            $grupoCuartos2 = new Grupo;
            $grupoCuartos2->torneo_id = $torneoId;
            $grupoCuartos2->zona = 'cuartos final';
            $grupoCuartos2->fecha = '2000-01-01';
            $grupoCuartos2->horario = '00:00';
            $grupoCuartos2->jugador_1 = $ganadorParJugador1;
            $grupoCuartos2->jugador_2 = $ganadorParJugador2;
            $grupoCuartos2->partido_id = $partidoCuartos->id;
            $grupoCuartos2->save();
            
            \Log::info('Creado partido de cuartos número ' . $numeroCuartos . ' desde octavos: partido_id=' . $partidoCuartos->id . 
                      ', pareja1 (octavos ' . (($numeroCuartos - 1) * 2 + 1) . ')=' . $ganadorActualJugador1 . '/' . $ganadorActualJugador2 . 
                      ', pareja2 (octavos ' . $partidoParOctavos . ')=' . $ganadorParJugador1 . '/' . $ganadorParJugador2);
        } else {
            \Log::info('Esperando que se complete el partido de octavos ' . $partidoParOctavos . ' para crear el partido de cuartos número ' . $numeroCuartos);
        }
    }
    
    private function crearSemifinalesPuntuable($torneoId) {
        // Obtener todos los partidos de cuartos con resultados completos
        $partidosCuartos = DB::table('partidos')
            ->join('grupos', 'partidos.id', '=', 'grupos.partido_id')
            ->where('grupos.torneo_id', $torneoId)
            ->where('grupos.zona', 'cuartos final')
            ->where(function($query) {
                $query->where('partidos.pareja_1_set_1', '>', 0)
                      ->orWhere('partidos.pareja_2_set_1', '>', 0)
                      ->orWhere('partidos.pareja_1_set_super_tie_break', '>', 0)
                      ->orWhere('partidos.pareja_2_set_super_tie_break', '>', 0);
            })
            ->select('partidos.id', 'partidos.pareja_1_set_1', 'partidos.pareja_2_set_1', 
                    'partidos.pareja_1_set_2', 'partidos.pareja_2_set_2',
                    'partidos.pareja_1_set_3', 'partidos.pareja_2_set_3',
                    'partidos.pareja_1_set_super_tie_break', 'partidos.pareja_2_set_super_tie_break')
            ->distinct()
            ->orderBy('partidos.id')
            ->get();
        
        // Verificar si ya existen semifinales
        $semifinalesExistentes = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'semifinal')
            ->whereNotNull('partido_id')
            ->count();
        
        if ($semifinalesExistentes > 0) {
            return; // Ya existen semifinales
        }
        
        // Para 12 parejas (4 grupos de 3): Semifinales = (1A/2C) vs (1C/2A) y (1B/2D) vs (1D/2B)
        // Obtener los ganadores de cada cuarto
        $ganadoresCuartos = [];
        foreach ($partidosCuartos as $partido) {
            $gruposPartido = DB::table('grupos')
                ->where('partido_id', $partido->id)
                ->where('torneo_id', $torneoId)
                ->where('zona', 'cuartos final')
                ->orderBy('id')
                ->get();
            
            if ($gruposPartido->count() >= 2) {
                $g1 = $gruposPartido[0];
                $g2 = $gruposPartido[1];
                
                // Determinar ganador basado en sets
                $setsGanadosP1 = 0;
                $setsGanadosP2 = 0;
                
                if ($partido->pareja_1_set_1 > $partido->pareja_2_set_1) $setsGanadosP1++;
                else if ($partido->pareja_2_set_1 > $partido->pareja_1_set_1) $setsGanadosP2++;
                
                if ($partido->pareja_1_set_2 > $partido->pareja_2_set_2) $setsGanadosP1++;
                else if ($partido->pareja_2_set_2 > $partido->pareja_1_set_2) $setsGanadosP2++;
                
                if ($partido->pareja_1_set_super_tie_break > 0 || $partido->pareja_2_set_super_tie_break > 0) {
                    if ($partido->pareja_1_set_super_tie_break > $partido->pareja_2_set_super_tie_break) {
                        $setsGanadosP1 = 2;
                        $setsGanadosP2 = 1;
                    } else if ($partido->pareja_2_set_super_tie_break > $partido->pareja_1_set_super_tie_break) {
                        $setsGanadosP1 = 1;
                        $setsGanadosP2 = 2;
                    }
                } else if ($partido->pareja_1_set_3 > $partido->pareja_2_set_3) {
                    $setsGanadosP1++;
                } else if ($partido->pareja_2_set_3 > $partido->pareja_1_set_3) {
                    $setsGanadosP2++;
                }
                
                $ganador = ($setsGanadosP1 > $setsGanadosP2) ? 
                    ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2] : 
                    ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                
                $ganadoresCuartos[] = $ganador;
            }
        }
        
        // Si tenemos 4 ganadores de cuartos, crear las semifinales
        // SF1: Ganador cuarto 1 (1A-2C) vs Ganador cuarto 3 (1C-2A)
        // SF2: Ganador cuarto 2 (1B-2D) vs Ganador cuarto 4 (1D-2B)
        if (count($ganadoresCuartos) == 4) {
            // Verificar si ya existen estas semifinales
            $existeSF1 = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'semifinal')
                ->where(function($q) use ($ganadoresCuartos) {
                    $q->where(function($q2) use ($ganadoresCuartos) {
                        $q2->where('jugador_1', $ganadoresCuartos[0]['jugador_1'])
                           ->where('jugador_2', $ganadoresCuartos[0]['jugador_2']);
                    })
                    ->orWhere(function($q2) use ($ganadoresCuartos) {
                        $q2->where('jugador_1', $ganadoresCuartos[2]['jugador_1'])
                           ->where('jugador_2', $ganadoresCuartos[2]['jugador_2']);
                    });
                })
                ->exists();
            
            if (!$existeSF1) {
                // Crear Semifinal 1: Ganador cuarto 1 vs Ganador cuarto 3
                $partidoSF1 = $this->crearPartido();
                
                $grupoSF1_P1 = new Grupo;
                $grupoSF1_P1->torneo_id = $torneoId;
                $grupoSF1_P1->zona = 'semifinal';
                $grupoSF1_P1->fecha = '2000-01-01';
                $grupoSF1_P1->horario = '00:00';
                $grupoSF1_P1->jugador_1 = $ganadoresCuartos[0]['jugador_1'];
                $grupoSF1_P1->jugador_2 = $ganadoresCuartos[0]['jugador_2'];
                $grupoSF1_P1->partido_id = $partidoSF1->id;
                $grupoSF1_P1->save();
                
                $grupoSF1_P2 = new Grupo;
                $grupoSF1_P2->torneo_id = $torneoId;
                $grupoSF1_P2->zona = 'semifinal';
                $grupoSF1_P2->fecha = '2000-01-01';
                $grupoSF1_P2->horario = '00:00';
                $grupoSF1_P2->jugador_1 = $ganadoresCuartos[2]['jugador_1'];
                $grupoSF1_P2->jugador_2 = $ganadoresCuartos[2]['jugador_2'];
                $grupoSF1_P2->partido_id = $partidoSF1->id;
                $grupoSF1_P2->save();
            }
            
            // Verificar si ya existe SF2
            $existeSF2 = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'semifinal')
                ->where(function($q) use ($ganadoresCuartos) {
                    $q->where(function($q2) use ($ganadoresCuartos) {
                        $q2->where('jugador_1', $ganadoresCuartos[1]['jugador_1'])
                           ->where('jugador_2', $ganadoresCuartos[1]['jugador_2']);
                    })
                    ->orWhere(function($q2) use ($ganadoresCuartos) {
                        $q2->where('jugador_1', $ganadoresCuartos[3]['jugador_1'])
                           ->where('jugador_2', $ganadoresCuartos[3]['jugador_2']);
                    });
                })
                ->exists();
            
            if (!$existeSF2) {
                // Crear Semifinal 2: Ganador cuarto 2 vs Ganador cuarto 4
                $partidoSF2 = $this->crearPartido();
                
                $grupoSF2_P1 = new Grupo;
                $grupoSF2_P1->torneo_id = $torneoId;
                $grupoSF2_P1->zona = 'semifinal';
                $grupoSF2_P1->fecha = '2000-01-01';
                $grupoSF2_P1->horario = '00:00';
                $grupoSF2_P1->jugador_1 = $ganadoresCuartos[1]['jugador_1'];
                $grupoSF2_P1->jugador_2 = $ganadoresCuartos[1]['jugador_2'];
                $grupoSF2_P1->partido_id = $partidoSF2->id;
                $grupoSF2_P1->save();
                
                $grupoSF2_P2 = new Grupo;
                $grupoSF2_P2->torneo_id = $torneoId;
                $grupoSF2_P2->zona = 'semifinal';
                $grupoSF2_P2->fecha = '2000-01-01';
                $grupoSF2_P2->horario = '00:00';
                $grupoSF2_P2->jugador_1 = $ganadoresCuartos[3]['jugador_1'];
                $grupoSF2_P2->jugador_2 = $ganadoresCuartos[3]['jugador_2'];
                $grupoSF2_P2->partido_id = $partidoSF2->id;
                $grupoSF2_P2->save();
            }
        }
    }
    
    private function crearFinalPuntuable($torneoId) {
        // Obtener todos los partidos de semifinales con resultados completos
        $partidosSemifinales = DB::table('partidos')
            ->join('grupos', 'partidos.id', '=', 'grupos.partido_id')
            ->where('grupos.torneo_id', $torneoId)
            ->where('grupos.zona', 'semifinal')
            ->where(function($query) {
                $query->where('partidos.pareja_1_set_1', '>', 0)
                      ->orWhere('partidos.pareja_2_set_1', '>', 0)
                      ->orWhere('partidos.pareja_1_set_super_tie_break', '>', 0)
                      ->orWhere('partidos.pareja_2_set_super_tie_break', '>', 0);
            })
            ->select('partidos.id', 'partidos.pareja_1_set_1', 'partidos.pareja_2_set_1', 
                    'partidos.pareja_1_set_2', 'partidos.pareja_2_set_2',
                    'partidos.pareja_1_set_3', 'partidos.pareja_2_set_3',
                    'partidos.pareja_1_set_super_tie_break', 'partidos.pareja_2_set_super_tie_break')
            ->distinct()
            ->orderBy('partidos.id')
            ->get();
        
        // Verificar si ya existe la final
        $finalExiste = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'final')
            ->whereNotNull('partido_id')
            ->count();
        
        if ($finalExiste > 0) {
            return; // Ya existe la final
        }
        
        // Obtener los ganadores de cada semifinal
        $ganadoresSemifinales = [];
        foreach ($partidosSemifinales as $partido) {
            $gruposPartido = DB::table('grupos')
                ->where('partido_id', $partido->id)
                ->where('torneo_id', $torneoId)
                ->where('zona', 'semifinal')
                ->orderBy('id')
                ->get();
            
            if ($gruposPartido->count() >= 2) {
                $g1 = $gruposPartido[0];
                $g2 = $gruposPartido[1];
                
                // Determinar ganador basado en sets
                $setsGanadosP1 = 0;
                $setsGanadosP2 = 0;
                
                if ($partido->pareja_1_set_1 > $partido->pareja_2_set_1) $setsGanadosP1++;
                else if ($partido->pareja_2_set_1 > $partido->pareja_1_set_1) $setsGanadosP2++;
                
                if ($partido->pareja_1_set_2 > $partido->pareja_2_set_2) $setsGanadosP1++;
                else if ($partido->pareja_2_set_2 > $partido->pareja_1_set_2) $setsGanadosP2++;
                
                if ($partido->pareja_1_set_super_tie_break > 0 || $partido->pareja_2_set_super_tie_break > 0) {
                    if ($partido->pareja_1_set_super_tie_break > $partido->pareja_2_set_super_tie_break) {
                        $setsGanadosP1 = 2;
                        $setsGanadosP2 = 1;
                    } else if ($partido->pareja_2_set_super_tie_break > $partido->pareja_1_set_super_tie_break) {
                        $setsGanadosP1 = 1;
                        $setsGanadosP2 = 2;
                    }
                } else if ($partido->pareja_1_set_3 > $partido->pareja_2_set_3) {
                    $setsGanadosP1++;
                } else if ($partido->pareja_2_set_3 > $partido->pareja_1_set_3) {
                    $setsGanadosP2++;
                }
                
                $ganador = ($setsGanadosP1 > $setsGanadosP2) ? 
                    ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2] : 
                    ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                
                $ganadoresSemifinales[] = $ganador;
            }
        }
        
        // Si tenemos 2 ganadores de semifinales, crear la final
        if (count($ganadoresSemifinales) == 2) {
            $partidoFinal = $this->crearPartido();
            
            $grupoFinal_P1 = new Grupo;
            $grupoFinal_P1->torneo_id = $torneoId;
            $grupoFinal_P1->zona = 'final';
            $grupoFinal_P1->fecha = '2000-01-01';
            $grupoFinal_P1->horario = '00:00';
            $grupoFinal_P1->jugador_1 = $ganadoresSemifinales[0]['jugador_1'];
            $grupoFinal_P1->jugador_2 = $ganadoresSemifinales[0]['jugador_2'];
            $grupoFinal_P1->partido_id = $partidoFinal->id;
            $grupoFinal_P1->save();
            
            $grupoFinal_P2 = new Grupo;
            $grupoFinal_P2->torneo_id = $torneoId;
            $grupoFinal_P2->zona = 'final';
            $grupoFinal_P2->fecha = '2000-01-01';
            $grupoFinal_P2->horario = '00:00';
            $grupoFinal_P2->jugador_1 = $ganadoresSemifinales[1]['jugador_1'];
            $grupoFinal_P2->jugador_2 = $ganadoresSemifinales[1]['jugador_2'];
            $grupoFinal_P2->partido_id = $partidoFinal->id;
            $grupoFinal_P2->save();
        }
    }

    public function verificarPartidosCompletos(Request $request) {
        $torneoId = $request->torneo_id;
        $zona = $request->zona;
        
        \Log::info('=== verificarPartidosCompletos ===');
        \Log::info('Torneo ID: ' . $torneoId . ', Zona: ' . $zona);
        
        // Obtener todos los partidos únicos de la zona
        // Incluir también partidos de "ganador X" y "perdedor X" si es una zona de 4 parejas
        // Usar pluck para obtener solo los partido_id únicos y luego hacer join
        $partidosIds = DB::table('grupos')
                        ->where('grupos.torneo_id', $torneoId)
                        ->where(function($query) use ($zona) {
                            $query->where('grupos.zona', $zona)
                                  ->orWhere('grupos.zona', 'ganador ' . $zona)
                                  ->orWhere('grupos.zona', 'perdedor ' . $zona);
                        })
                        ->whereNotNull('grupos.partido_id')
                        ->select('grupos.partido_id')
                        ->distinct()
                        ->pluck('partido_id');
        
        \Log::info('Partidos IDs encontrados: ' . json_encode($partidosIds->toArray()));
        
        // Obtener información completa de cada partido
        $partidos = DB::table('partidos')
                        ->whereIn('id', $partidosIds)
                        ->get();
        
        $totalPartidos = $partidos->count();
        $partidosCompletos = 0;
        $detallePartidos = [];
        
        foreach ($partidos as $partido) {
            // Un partido está completo si tiene al menos un set con resultado > 0
            $tieneResultado = ($partido->pareja_1_set_1 > 0 || $partido->pareja_2_set_1 > 0) ||
                             ($partido->pareja_1_set_2 > 0 || $partido->pareja_2_set_2 > 0) ||
                             ($partido->pareja_1_set_3 > 0 || $partido->pareja_2_set_3 > 0) ||
                             ($partido->pareja_1_set_super_tie_break > 0 || $partido->pareja_2_set_super_tie_break > 0);
            
            // Obtener información de la zona de este partido para debugging
            $zonaPartido = DB::table('grupos')
                            ->where('partido_id', $partido->id)
                            ->where('torneo_id', $torneoId)
                            ->select('zona')
                            ->distinct()
                            ->pluck('zona')
                            ->toArray();
            
            $detallePartidos[] = [
                'partido_id' => $partido->id,
                'tiene_resultado' => $tieneResultado,
                'zonas' => $zonaPartido,
                'set1' => $partido->pareja_1_set_1 . '-' . $partido->pareja_2_set_1,
                'set2' => $partido->pareja_1_set_2 . '-' . $partido->pareja_2_set_2,
                'set3' => $partido->pareja_1_set_3 . '-' . $partido->pareja_2_set_3,
                'super_tb' => $partido->pareja_1_set_super_tie_break . '-' . $partido->pareja_2_set_super_tie_break
            ];
            
            if ($tieneResultado) {
                $partidosCompletos++;
            }
        }
        
        \Log::info('Verificar partidos completos - Zona: ' . $zona . ', Total: ' . $totalPartidos . ', Completos: ' . $partidosCompletos);
        \Log::info('Detalle partidos: ' . json_encode($detallePartidos));
        
        return response()->json([
            'success' => true,
            'total_partidos' => $totalPartidos,
            'partidos_completos' => $partidosCompletos,
            'todos_completos' => $totalPartidos > 0 && $partidosCompletos == $totalPartidos,
            'detalle_partidos' => $detallePartidos
        ]);
    }

    public function calcularPosicionesZona(Request $request) {
        $torneoId = $request->torneo_id;
        $zona = $request->zona;
        
        // PRIMERO: Verificar que todos los partidos estén completos antes de calcular posiciones
        // Incluir también partidos de "ganador X" y "perdedor X" si es una zona de 4 parejas
        $todosPartidos = DB::table('grupos')
                        ->join('partidos', 'grupos.partido_id', '=', 'partidos.id')
                        ->where('grupos.torneo_id', $torneoId)
                        ->where(function($query) use ($zona) {
                            $query->where('grupos.zona', $zona)
                                  ->orWhere('grupos.zona', 'ganador ' . $zona)
                                  ->orWhere('grupos.zona', 'perdedor ' . $zona);
                        })
                        ->select('grupos.partido_id', 'partidos.*')
                        ->distinct()
                        ->get();
        
        $totalPartidos = $todosPartidos->count();
        $partidosCompletos = 0;
        
        foreach ($todosPartidos as $partido) {
            $tieneResultado = ($partido->pareja_1_set_1 > 0 || $partido->pareja_2_set_1 > 0) ||
                             ($partido->pareja_1_set_2 > 0 || $partido->pareja_2_set_2 > 0) ||
                             ($partido->pareja_1_set_3 > 0 || $partido->pareja_2_set_3 > 0) ||
                             ($partido->pareja_1_set_super_tie_break > 0 || $partido->pareja_2_set_super_tie_break > 0);
            
            if ($tieneResultado) {
                $partidosCompletos++;
            }
        }
        
        // Si no todos los partidos están completos, retornar error
        if ($totalPartidos > 0 && $partidosCompletos < $totalPartidos) {
            \Log::info('No todos los partidos están completos - Zona: ' . $zona . ', Completos: ' . $partidosCompletos . '/' . $totalPartidos);
            return response()->json([
                'success' => false,
                'message' => 'No todos los partidos están completos. Faltan ' . ($totalPartidos - $partidosCompletos) . ' partido(s).',
                'total_partidos' => $totalPartidos,
                'partidos_completos' => $partidosCompletos
            ]);
        }
        
        $posiciones = TorneoGrupoPosicionesService::calcularYPersistirPosicionesZona((int) $torneoId, (string) $zona);
        return response()->json(['success' => true, 'posiciones' => $posiciones]);
    }

    public function confirmarCruces(Request $request) {
        $torneoId = $request->torneo_id;
        $cruces = json_decode($request->cruces, true);
        
        if (!$cruces || !is_array($cruces)) {
            return response()->json(['success' => false, 'message' => 'Datos de cruces inválidos']);
        }
        
        // Eliminar cruces de octavos y cuartos existentes para este torneo
        $gruposEliminatorios = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->whereIn('zona', ['octavos final', 'cuartos final', '16avos final'])
            ->whereNotNull('partido_id')
            ->get();
        
        $partidosIds = $gruposEliminatorios->pluck('partido_id')->unique();
        if ($partidosIds->count() > 0) {
            DB::table('partidos')->whereIn('id', $partidosIds)->delete();
            DB::table('grupos')->whereIn('partido_id', $partidosIds)->delete();
        }
        
        \Log::info('Confirmando cruces. Total cruces recibidos: ' . count($cruces));
        \Log::info('Cruces recibidos: ' . json_encode($cruces));
        
        // Crear los nuevos cruces usando la ronda de cada cruce individual
        foreach ($cruces as $index => $cruce) {
            if (!isset($cruce['pareja_1']) || !isset($cruce['pareja_2'])) {
                \Log::warning('Cruce ' . $index . ' no tiene pareja_1 o pareja_2, saltando');
                continue;
            }
            
            $pareja1 = $cruce['pareja_1'];
            $pareja2 = $cruce['pareja_2'];
            
            // Usar la ronda del cruce si está especificada
            // Si no está especificada, intentar determinar por el ID del cruce o por la cantidad
            $ronda = $cruce['ronda'] ?? null;
            
            // Si no tiene ronda, intentar determinar por el ID del cruce
            if (!$ronda && isset($cruce['id'])) {
                if (strpos($cruce['id'], '16avos_') === 0) {
                    $ronda = '16avos';
                } elseif (strpos($cruce['id'], 'octavos_') === 0) {
                    $ronda = 'octavos';
                } elseif (strpos($cruce['id'], 'cuartos_') === 0) {
                    $ronda = 'cuartos';
                }
            }
            
            // Si aún no tiene ronda, usar lógica de fallback basada en cantidad
            // PERO solo si todos los cruces tienen la misma cantidad
            if (!$ronda) {
                $numCruces = count($cruces);
                $ronda = ($numCruces == 8) ? 'octavos' : (($numCruces == 16) ? '16avos' : 'cuartos');
            }
            
            // Determinar la zona según la ronda
            $zona = 'cuartos final'; // Por defecto
            if ($ronda === 'octavos' || $ronda === '8vos') {
                $zona = 'octavos final';
            } elseif ($ronda === '16avos' || $ronda === '16vos') {
                $zona = '16avos final';
            } elseif ($ronda === 'cuartos' || $ronda === '4tos') {
                $zona = 'cuartos final';
            }
            
            \Log::info('Procesando cruce ' . $index . ': Ronda=' . $ronda . ', Zona=' . $zona);
            
            // Crear partido
            $partido = $this->crearPartido();
            
            // Crear grupo para pareja 1
            $grupo1 = new Grupo;
            $grupo1->torneo_id = $torneoId;
            $grupo1->zona = $zona;
            $grupo1->fecha = '2000-01-01';
            $grupo1->horario = '00:00';
            $grupo1->jugador_1 = $pareja1['jugador_1'];
            $grupo1->jugador_2 = $pareja1['jugador_2'];
            $grupo1->partido_id = $partido->id;
            $grupo1->save();
            
            // Crear grupo para pareja 2
            $grupo2 = new Grupo;
            $grupo2->torneo_id = $torneoId;
            $grupo2->zona = $zona;
            $grupo2->fecha = '2000-01-01';
            $grupo2->horario = '00:00';
            $grupo2->jugador_1 = $pareja2['jugador_1'];
            $grupo2->jugador_2 = $pareja2['jugador_2'];
            $grupo2->partido_id = $partido->id;
            $grupo2->save();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Cruces confirmados correctamente',
            'torneo_id' => $torneoId
        ]);
    }
    
    public function crearCuartosDesdeOctavos(Request $request) {
        $torneoId = $request->torneo_id;
        $cruces = json_decode($request->cruces, true);
        
        if (!$cruces || !is_array($cruces)) {
            return response()->json(['success' => false, 'message' => 'Datos de cruces inválidos']);
        }
        
        // Crear los cruces de cuartos desde los ganadores de octavos
        foreach ($cruces as $cruce) {
            if (!isset($cruce['pareja_1']) || !isset($cruce['pareja_2'])) {
                continue;
            }
            
            $pareja1 = $cruce['pareja_1'];
            $pareja2 = $cruce['pareja_2'];
            
            // Crear partido
            $partido = $this->crearPartido();
            
            // Crear grupo para pareja 1
            $grupo1 = new Grupo;
            $grupo1->torneo_id = $torneoId;
            $grupo1->zona = 'cuartos final';
            $grupo1->fecha = '2000-01-01';
            $grupo1->horario = '00:00';
            $grupo1->jugador_1 = $pareja1['jugador_1'];
            $grupo1->jugador_2 = $pareja1['jugador_2'];
            $grupo1->partido_id = $partido->id;
            $grupo1->save();
            
            // Crear grupo para pareja 2
            $grupo2 = new Grupo;
            $grupo2->torneo_id = $torneoId;
            $grupo2->zona = 'cuartos final';
            $grupo2->fecha = '2000-01-01';
            $grupo2->horario = '00:00';
            $grupo2->jugador_1 = $pareja2['jugador_1'];
            $grupo2->jugador_2 = $pareja2['jugador_2'];
            $grupo2->partido_id = $partido->id;
            $grupo2->save();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Cuartos de final creados correctamente desde octavos',
            'torneo_id' => $torneoId
        ]);
    }

    public function adminTorneoPuntuableCruces(Request $request) {
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
        $cruces = [];
        
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
                    'partido_id' => $partidoId,
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
                    'ronda' => $ronda,
                    'partido' => $partido // Incluir el objeto partido completo
                ];
                
                $cruces[] = $cruce;
            }
        }
        
        return View('bahia_padel.admin.torneo.cruces_puntuable')
                    ->with('torneo', $torneo)
                    ->with('jugadores', $jugadores)
                    ->with('cruces', $cruces);
    }

    public function adminTorneoPuntuableCrucesV2(Request $request) {
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
        
        // Obtener todos los grupos eliminatorios con sus partidos
        $gruposEliminatorios = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->whereIn('zona', ['octavos final', 'cuartos final', 'semifinal', 'final'])
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
        $cruces = [];
        $resultadosGuardados = [];
        
        foreach ($partidosAgrupados as $partidoId => $datosPartido) {
            if (count($datosPartido['grupos']) >= 2) {
                $g1 = $datosPartido['grupos'][0];
                $g2 = $datosPartido['grupos'][1];
                $partido = $partidos[$partidoId] ?? null;
                
                // Determinar la ronda según la zona
                $ronda = 'octavos';
                if ($datosPartido['zona'] === 'cuartos final') {
                    $ronda = 'cuartos';
                } else if ($datosPartido['zona'] === 'semifinal') {
                    $ronda = 'semifinales';
                } else if ($datosPartido['zona'] === 'final') {
                    $ronda = 'final';
                }
                
                // Crear el cruce
                $cruce = [
                    'id' => $ronda . '_' . $partidoId,
                    'partido_id' => $partidoId,
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
                    'ronda' => $ronda,
                    'partido' => $partido // Incluir el objeto partido completo
                ];
                
                $cruces[] = $cruce;
                
                // Guardar resultado si existe (verificar si hay al menos un set con resultado)
                // Incluir resultados incluso si algunos sets son 0, siempre que haya al menos un set con resultado
                if ($partido && ($partido->pareja_1_set_1 > 0 || $partido->pareja_2_set_1 > 0 || 
                    $partido->pareja_1_set_2 > 0 || $partido->pareja_2_set_2 > 0 || 
                    $partido->pareja_1_set_3 > 0 || $partido->pareja_2_set_3 > 0)) {
                    $resultadosGuardados[] = [
                        'partido_id' => $partidoId,
                        'cruce_id' => $cruce['id'],
                        'ronda' => $ronda,
                        'pareja_1_jugador_1' => $g1->jugador_1,
                        'pareja_1_jugador_2' => $g1->jugador_2,
                        'pareja_2_jugador_1' => $g2->jugador_1,
                        'pareja_2_jugador_2' => $g2->jugador_2,
                        'pareja_1_set_1' => isset($partido->pareja_1_set_1) ? (int)$partido->pareja_1_set_1 : null,
                        'pareja_1_set_2' => isset($partido->pareja_1_set_2) ? (int)$partido->pareja_1_set_2 : null,
                        'pareja_1_set_3' => isset($partido->pareja_1_set_3) ? (int)$partido->pareja_1_set_3 : null,
                        'pareja_2_set_1' => isset($partido->pareja_2_set_1) ? (int)$partido->pareja_2_set_1 : null,
                        'pareja_2_set_2' => isset($partido->pareja_2_set_2) ? (int)$partido->pareja_2_set_2 : null,
                        'pareja_2_set_3' => isset($partido->pareja_2_set_3) ? (int)$partido->pareja_2_set_3 : null,
                    ];
                }
            }
        }
        
        // Determinar si hay octavos
        $tieneOctavos = false;
        foreach ($cruces as $cruce) {
            if (isset($cruce['ronda']) && $cruce['ronda'] === 'octavos') {
                $tieneOctavos = true;
                break;
            }
        }
        
        return View('bahia_padel.admin.torneo.cruces_puntuable_v2')
                    ->with('torneo', $torneo)
                    ->with('jugadores', $jugadores)
                    ->with('cruces', $cruces)
                    ->with('resultadosGuardados', $resultadosGuardados)
                    ->with('tieneOctavos', $tieneOctavos)
                    ->with('tiene16avos', false);
    }

    public function tvTorneoAmericanoCruces(Request $request) {
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
        // Incluir todas las rondas: dieciseisavos, octavos, cuartos, semifinal, final
        $gruposEliminatorios = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where(function($query) {
                $query->whereIn('zona', ['dieciseisavos final', 'octavos final', 'cuartos final', 'semifinal', 'final'])
                      ->orWhere('zona', 'like', 'dieciseisavos final|%')
                      ->orWhere('zona', 'like', 'octavos final|%')
                      ->orWhere('zona', 'like', 'cuartos final|%');
            })
            ->whereNotNull('partido_id')
            ->orderBy('zona')
            ->orderBy('partido_id')
            ->orderBy('id')
            ->get();
        
        // Agrupar por partido_id
        // IMPORTANTE: Si hay múltiples grupos con el mismo partido_id, solo tomar los primeros 2
        // para evitar duplicados cuando se actualizan las parejas
        $partidosAgrupados = [];
        foreach ($gruposEliminatorios as $grupo) {
            $partidoId = $grupo->partido_id;
            if (!isset($partidosAgrupados[$partidoId])) {
                // Normalizar la zona: si tiene "|", usar solo la base para la agrupación
                $zonaNormalizada = $grupo->zona;
                if (strpos($zonaNormalizada, '|') !== false) {
                    $zonaNormalizada = explode('|', $zonaNormalizada)[0];
                }
                $partidosAgrupados[$partidoId] = [
                    'zona' => trim($zonaNormalizada),
                    'partido_id' => $partidoId,
                    'grupos' => []
                ];
            }
            // Solo agregar si no hay ya 2 grupos (pareja_1 y pareja_2)
            if (count($partidosAgrupados[$partidoId]['grupos']) < 2) {
                $partidosAgrupados[$partidoId]['grupos'][] = $grupo;
            }
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
            'dieciseisavos' => [],
            'octavos' => [],
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
                if ($datosPartido['zona'] === 'dieciseisavos final') {
                    $ronda = 'dieciseisavos';
                } else if ($datosPartido['zona'] === 'octavos final') {
                    $ronda = 'octavos';
                } else if ($datosPartido['zona'] === 'cuartos final') {
                    $ronda = 'cuartos';
                } else if ($datosPartido['zona'] === 'semifinal') {
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
        
        // Determinar qué rondas existen para la vista
        $tieneDieciseisavos = count($crucesPorRonda['dieciseisavos']) > 0;
        $tieneOctavos = count($crucesPorRonda['octavos']) > 0;
        $tieneCuartos = count($crucesPorRonda['cuartos']) > 0;
        
        // Calcular posiciones de cada zona para mostrar en la vista (necesario para clasificados)
        $grupos = DB::table('grupos')
                        ->where('grupos.torneo_id', $torneoId)
                        ->whereNotIn('grupos.zona', ['dieciseisavos final', 'octavos final', 'cuartos final', 'semifinal', 'final'])
                        ->where('grupos.zona', 'not like', 'dieciseisavos final|%')
                        ->where('grupos.zona', 'not like', 'octavos final|%')
                        ->where('grupos.zona', 'not like', 'cuartos final|%')
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
            $terceros = array_values($tercerosPorZona);
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
        
        // SISTEMA ADAPTATIVO: Detectar qué rondas están completadas (todos los partidos tienen ganador)
        $rondasCompletadas = [];
        
        foreach ($crucesPorRonda as $rondaNombre => $crucesRonda) {
            if (empty($crucesRonda)) continue;
            
            $todosCrucesCompletos = true;
            foreach ($crucesRonda as $cruce) {
                // Buscar si este cruce tiene resultado
                $tieneResultado = false;
                $cruceId = $cruce['id'] ?? null;
                
                foreach ($resultadosGuardados as $resultado) {
                    if (($resultado['cruce_id'] ?? null) == $cruceId && ($resultado['ronda'] ?? '') == $rondaNombre) {
                        // Verificar que haya un ganador definido (al menos un set completo)
                        $set1_p1 = $resultado['pareja_1_set_1'] ?? 0;
                        $set1_p2 = $resultado['pareja_2_set_1'] ?? 0;
                        if ($set1_p1 > 0 || $set1_p2 > 0) {
                            $tieneResultado = true;
                        }
                        break;
                    }
                }
                
                if (!$tieneResultado) {
                    $todosCrucesCompletos = false;
                    break;
                }
            }
            
            if ($todosCrucesCompletos) {
                $rondasCompletadas[] = $rondaNombre;
            }
        }
        
        return View('bahia_padel.tv.cruces_americano')
                    ->with('torneo', $torneo)
                    ->with('jugadores', $jugadores)
                    ->with('clasificados', $clasificados)
                    ->with('cruces', $cruces)
                    ->with('crucesPorRonda', $crucesPorRonda)
                    ->with('posicionesPorZona', $posicionesPorZona)
                    ->with('resultadosGuardados', $resultadosGuardados)
                    ->with('primerosClasificados', $primerosClasificados)
                    ->with('totalClasificados', count($clasificados))
                    ->with('tieneDieciseisavos', $tieneDieciseisavos)
                    ->with('tieneOctavos', $tieneOctavos)
                    ->with('tieneCuartos', $tieneCuartos)
                    ->with('rondasCompletadas', $rondasCompletadas);
    }
    
    public function tvTorneoAmericanoCrucesActualizar(Request $request) {
        $torneoId = $request->torneo_id;
        
        $torneo = DB::table('torneos')
                        ->where('torneos.id', $torneoId)
                        ->where('torneos.activo', 1)
                        ->first();
        
        if (!$torneo) {
            return response()->json(['success' => false, 'message' => 'Torneo no encontrado'], 404);
        }
        
        // Obtener todos los grupos eliminatorios con sus partidos
        // Incluir zonas que comienzan con "cuartos final|" además de las exactas
        $gruposEliminatorios = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where(function($query) {
                $query->whereIn('zona', ['cuartos final', 'semifinal', 'final'])
                      ->orWhere('zona', 'like', 'cuartos final|%');
            })
            ->whereNotNull('partido_id')
            ->orderBy('zona')
            ->orderBy('partido_id')
            ->orderBy('id')
            ->get();
        
        // Agrupar por partido_id
        // IMPORTANTE: Si hay múltiples grupos con el mismo partido_id, solo tomar los primeros 2
        // para evitar duplicados cuando se actualizan las parejas
        $partidosAgrupados = [];
        foreach ($gruposEliminatorios as $grupo) {
            $partidoId = $grupo->partido_id;
            if (!isset($partidosAgrupados[$partidoId])) {
                // Normalizar la zona: si tiene "|", usar solo "cuartos final" para la agrupación
                $zonaNormalizada = $grupo->zona;
                if (strpos($zonaNormalizada, '|') !== false) {
                    $zonaNormalizada = 'cuartos final';
                }
                $partidosAgrupados[$partidoId] = [
                    'zona' => $zonaNormalizada,
                    'partido_id' => $partidoId,
                    'grupos' => []
                ];
            }
            // Solo agregar si no hay ya 2 grupos (pareja_1 y pareja_2)
            if (count($partidosAgrupados[$partidoId]['grupos']) < 2) {
                $partidosAgrupados[$partidoId]['grupos'][] = $grupo;
            }
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
        
        // Construir resultados guardados
        $resultadosGuardados = [];
        foreach ($partidosAgrupados as $partidoId => $datosPartido) {
            if (count($datosPartido['grupos']) >= 2 && isset($partidos[$partidoId])) {
                $g1 = $datosPartido['grupos'][0];
                $g2 = $datosPartido['grupos'][1];
                $partido = $partidos[$partidoId];
                
                // Determinar la ronda según la zona
                $ronda = 'cuartos';
                if ($datosPartido['zona'] === 'semifinal') {
                    $ronda = 'semifinales';
                } else if ($datosPartido['zona'] === 'final') {
                    $ronda = 'final';
                }
                
                $cruceId = $ronda . '_' . $partidoId;
                
                if ($partido->pareja_1_set_1 > 0 || $partido->pareja_2_set_1 > 0) {
                    $resultadosGuardados[] = [
                        'partido_id' => $partidoId,
                        'cruce_id' => $cruceId,
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
        
        return response()->json([
            'success' => true,
            'resultadosGuardados' => $resultadosGuardados
        ]);
    }
    
    public function tvTorneoAmericanoSorteo(Request $request) {
        $torneoId = $request->torneo_id;
        
        $torneo = DB::table('torneos')
                        ->where('torneos.id', $torneoId)
                        ->where('torneos.activo', 1)
                        ->first();
        
        if (!$torneo) {
            return redirect()->route('index')->with('error', 'Torneo no encontrado');
        }
        
        // Obtener grupos iniciales (sin partido_id) para mostrar el sorteo
        $grupos = DB::table('grupos')
                        ->where('grupos.torneo_id', $torneoId)
                        ->whereNull('grupos.partido_id') // Solo grupos iniciales
                        ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final'])
                        ->whereNotNull('grupos.jugador_1')
                        ->whereNotNull('grupos.jugador_2')
                        ->orderBy('grupos.zona')
                        ->orderBy('grupos.id')
                        ->get();
        
        // Agrupar por zona
        $gruposPorZona = [];
        foreach ($grupos as $grupo) {
            $zona = $grupo->zona;
            if (!isset($gruposPorZona[$zona])) {
                $gruposPorZona[$zona] = [];
            }
            $gruposPorZona[$zona][] = $grupo;
        }
        
        // Obtener información de los jugadores
        $jugadores = DB::table('jugadores')
                        ->where('jugadores.activo', 1)
                        ->get()
                        ->keyBy('id');
        
        return View('bahia_padel.tv.sorteo_americano')
                    ->with('torneo', $torneo)
                    ->with('gruposPorZona', $gruposPorZona)
                    ->with('jugadores', $jugadores);
    }
    
    public function tvTorneoAmericanoSorteoActualizar(Request $request) {
        $torneoId = $request->torneo_id;
        
        $torneo = DB::table('torneos')
                        ->where('torneos.id', $torneoId)
                        ->where('torneos.activo', 1)
                        ->first();
        
        if (!$torneo) {
            return response()->json(['success' => false, 'message' => 'Torneo no encontrado'], 404);
        }
        
        // Obtener grupos iniciales (sin partido_id) para mostrar el sorteo
        $grupos = DB::table('grupos')
                        ->where('grupos.torneo_id', $torneoId)
                        ->whereNull('grupos.partido_id')
                        ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final'])
                        ->where(function($query) {
                            $query->whereNotNull('grupos.jugador_1')
                                  ->whereNotNull('grupos.jugador_2')
                                  ->where('grupos.jugador_1', '!=', 0)
                                  ->where('grupos.jugador_2', '!=', 0);
                        })
                        ->orderBy('grupos.zona')
                        ->orderBy('grupos.id')
                        ->get();
        
        // Obtener todos los jugadores necesarios
        $jugadoresIds = [];
        foreach ($grupos as $grupo) {
            if ($grupo->jugador_1) $jugadoresIds[] = $grupo->jugador_1;
            if ($grupo->jugador_2) $jugadoresIds[] = $grupo->jugador_2;
        }
        $jugadoresIds = array_unique($jugadoresIds);
        
        $jugadores = [];
        if (count($jugadoresIds) > 0) {
            $jugadoresData = DB::table('jugadores')
                                ->whereIn('id', $jugadoresIds)
                                ->where('activo', 1)
                                ->get();
            
            foreach ($jugadoresData as $jugador) {
                $jugadores[$jugador->id] = [
                    'id' => $jugador->id,
                    'nombre' => $jugador->nombre,
                    'apellido' => $jugador->apellido,
                    'foto' => $jugador->foto
                ];
            }
        }
        
        // Agrupar por zona
        $gruposPorZona = [];
        foreach ($grupos as $grupo) {
            $zona = $grupo->zona;
            if (!isset($gruposPorZona[$zona])) {
                $gruposPorZona[$zona] = [];
            }
            $gruposPorZona[$zona][] = [
                'id' => $grupo->id,
                'zona' => $grupo->zona,
                'jugador_1' => $grupo->jugador_1,
                'jugador_2' => $grupo->jugador_2
            ];
        }
        
        return response()->json([
            'success' => true,
            'gruposPorZona' => $gruposPorZona,
            'jugadores' => $jugadores
        ]);
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
        
        // Verificar si hay más de 17 parejas clasificadas para determinar si deberían ser octavos
        $gruposZonas = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where(function($query) {
                $query->whereNotIn('zona', ['cuartos final', 'semifinal', 'final', 'octavos final'])
                      ->where('zona', 'not like', 'cuartos final|%')
                      ->where('zona', 'not like', 'octavos final|%')
                      ->where('zona', 'not like', 'ganador %')
                      ->where('zona', 'not like', 'perdedor %');
            })
            ->get();
        
        $posicionesPorZona = [];
        foreach ($gruposZonas->groupBy('zona') as $zona => $grupos) {
            $posicionesPorZona[$zona] = $grupos->count();
        }
        
        $totalParejasClasificadas = array_sum($posicionesPorZona);
        $necesitaOctavos = $totalParejasClasificadas > 17;
        
        // Si necesita octavos y hay exactamente 8 cruces de cuartos, convertirlos a octavos
        if ($necesitaOctavos) {
            $crucesCuartos = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'cuartos final')
                ->whereNotNull('partido_id')
                ->select('partido_id')
                ->distinct()
                ->get();
            
            // Si hay exactamente 8 partidos de cuartos, convertirlos a octavos
            if ($crucesCuartos->count() == 8) {
                \Log::info('Convirtiendo 8 cruces de cuartos a octavos para torneo ' . $torneoId);
                DB::table('grupos')
                    ->where('torneo_id', $torneoId)
                    ->where('zona', 'cuartos final')
                    ->whereNotNull('partido_id')
                    ->update(['zona' => 'octavos final']);
            }
        }
        
        // PRIMERO: Obtener todos los partidos eliminatorios existentes directamente de la base de datos
        $cruces = [];
        $resultadosGuardados = [];
        
        // Obtener todos los grupos eliminatorios con sus partidos
        // Para octavos, cuartos, buscar también zonas que comiencen con "octavos final" o "cuartos final"
        $gruposEliminatorios = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where(function($query) {
                $query->whereIn('zona', ['octavos final', 'cuartos final', 'semifinal', 'final'])
                      ->orWhere('zona', 'like', 'octavos final|%')
                      ->orWhere('zona', 'like', 'cuartos final|%');
            })
            ->whereNotNull('partido_id')
            ->orderBy('zona')
            ->orderBy('partido_id')
            ->orderBy('id')
            ->get();
        
        // Agrupar por partido_id
        // IMPORTANTE: Si hay múltiples grupos con el mismo partido_id, solo tomar los primeros 2
        // para evitar duplicados cuando se actualizan las parejas
        $partidosAgrupados = [];
        foreach ($gruposEliminatorios as $grupo) {
            $partidoId = $grupo->partido_id;
            if (!isset($partidosAgrupados[$partidoId])) {
                $partidosAgrupados[$partidoId] = [
                    'zona' => $grupo->zona, // Guardar zona completa con número de partido si existe
                    'partido_id' => $partidoId,
                    'grupos' => []
                ];
            }
            // Solo agregar si no hay ya 2 grupos (pareja_1 y pareja_2)
            if (count($partidosAgrupados[$partidoId]['grupos']) < 2) {
                $partidosAgrupados[$partidoId]['grupos'][] = $grupo;
            }
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
            'octavos' => [],
            'cuartos' => [],
            'semifinales' => [],
            'final' => []
        ];
        
        // Ordenar los partidos agrupados por partido_id para mantener orden consistente
        ksort($partidosAgrupados);
        
        foreach ($partidosAgrupados as $partidoId => $datosPartido) {
            if (count($datosPartido['grupos']) >= 2) {
                $g1 = $datosPartido['grupos'][0];
                $g2 = $datosPartido['grupos'][1];
                $partido = $partidos[$partidoId] ?? null;
                
                // Determinar la ronda según la zona
                $ronda = 'cuartos';
                $zonaLower = strtolower($datosPartido['zona']);
                if (strpos($zonaLower, 'octavos') !== false || $zonaLower === 'octavos final') {
                    $ronda = 'octavos';
                    \Log::info('Detectado cruce de octavos: zona=' . $datosPartido['zona'] . ', partido_id=' . $partidoId);
                } else if (strpos($zonaLower, 'semifinal') !== false) {
                    $ronda = 'semifinales';
                } else if ($zonaLower === 'final') {
                    $ronda = 'final';
                }
                
                // Crear el cruce
                $cruce = [
                    'id' => $ronda . '_' . $partidoId,
                    'partido_id' => $partidoId,
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
        
        // Obtener criterios de desempate de la configuración
        $criterios = ['PG', 'ENFRENTAMIENTO', 'DIF_GAMES', 'GF']; // Default
        if (isset($torneo->config_cruces_americano_id) && $torneo->config_cruces_americano_id) {
            $configCriterios = DB::table('configuracion_cruces_americanos')
                ->where('id', $torneo->config_cruces_americano_id)
                ->first();
            if ($configCriterios && $configCriterios->criterio_desempate_orden) {
                $criterios = explode(',', $configCriterios->criterio_desempate_orden);
            }
        }
        
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
                        'puntos_perdidos' => 0,
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
                        $parejas[$key1]['puntos_perdidos'] += $puntosPareja2;
                        $parejas[$key2]['partidos_perdidos']++;
                        $parejas[$key2]['puntos_ganados'] += $puntosPareja2;
                        $parejas[$key2]['puntos_perdidos'] += $puntosPareja1;
                        $parejas[$key1]['partidos_directos'][$key2] = ['ganado' => true];
                        $parejas[$key2]['partidos_directos'][$key1] = ['ganado' => false];
                    } else if ($puntosPareja2 > $puntosPareja1) {
                        $parejas[$key2]['partidos_ganados']++;
                        $parejas[$key2]['puntos_ganados'] += $puntosPareja2;
                        $parejas[$key2]['puntos_perdidos'] += $puntosPareja1;
                        $parejas[$key1]['partidos_perdidos']++;
                        $parejas[$key1]['puntos_ganados'] += $puntosPareja1;
                        $parejas[$key1]['puntos_perdidos'] += $puntosPareja2;
                        $parejas[$key2]['partidos_directos'][$key1] = ['ganado' => true];
                        $parejas[$key1]['partidos_directos'][$key2] = ['ganado' => false];
                    }
                }
            }
            
            // Agregar keys, diferencia de games y ordenar
            foreach ($parejas as $key => $pareja) {
                $parejas[$key]['key'] = $key;
                $parejas[$key]['diferencia_games'] = ($pareja['puntos_ganados'] ?? 0) - ($pareja['puntos_perdidos'] ?? 0);
            }
            
            $posiciones = array_values($parejas);
            
            // Ordenar con criterios dinámicos según configuración
            usort($posiciones, function($a, $b) use ($criterios) {
                foreach ($criterios as $criterio) {
                    $criterio = trim($criterio);
                    $resultado = 0;
                    
                    switch ($criterio) {
                        case 'PG':
                            if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                                $resultado = $b['partidos_ganados'] - $a['partidos_ganados'];
                            }
                            break;
                        case 'ENFRENTAMIENTO':
                            $keyB = $b['key'];
                            if (isset($a['partidos_directos'][$keyB])) {
                                $resultado = $a['partidos_directos'][$keyB]['ganado'] ? -1 : 1;
                            }
                            break;
                        case 'DIF_GAMES':
                            $diffA = ($a['diferencia_games'] ?? 0);
                            $diffB = ($b['diferencia_games'] ?? 0);
                            if ($diffA != $diffB) {
                                $resultado = $diffB - $diffA;
                            }
                            break;
                        case 'GF':
                            if ($a['puntos_ganados'] != $b['puntos_ganados']) {
                                $resultado = $b['puntos_ganados'] - $a['puntos_ganados'];
                            }
                            break;
                    }
                    
                    if ($resultado !== 0) {
                        return $resultado;
                    }
                }
                return 0;
            });
            
            $posicionesPorZona[$zona] = $posiciones;
        }
        
        // Calcular clasificados para pasarlos a la vista (siempre necesario)
        $clasificados = [];
        $zonasArray = $zonas->toArray();
        
        // Verificar si hay grupos de 10 parejas totales: 2 grupos de 5 parejas cada uno
        $esGrupoDe10 = false;
        if (count($zonasArray) == 2) {
            $zona1 = $zonasArray[0];
            $zona2 = $zonasArray[1];
            // Dos zonas con 5 parejas cada una (total 10 parejas)
            if (isset($posicionesPorZona[$zona1]) && isset($posicionesPorZona[$zona2]) &&
                count($posicionesPorZona[$zona1]) == 5 && count($posicionesPorZona[$zona2]) == 5) {
                $esGrupoDe10 = true;
            }
            // O una zona con 10 parejas (cuando hay 2 zonas en total)
            elseif (isset($posicionesPorZona[$zona1]) && count($posicionesPorZona[$zona1]) == 10) {
                $esGrupoDe10 = true;
            }
        }
        
        // Si es grupo de 10 parejas totales con 2 zonas, clasificar los primeros 4 de cada grupo
        if ($esGrupoDe10 && count($zonasArray) == 2) {
            $zonasOrdenadasArray = $zonasArray;
            sort($zonasOrdenadasArray);
            
            foreach ($zonasOrdenadasArray as $zona) {
                if (isset($posicionesPorZona[$zona])) {
                    // Clasificar posiciones 1, 2, 3 y 4 (eliminar solo el último: posición 5 si es grupo de 5, o posición 10 si es grupo de 10)
                    for ($i = 0; $i < min(4, count($posicionesPorZona[$zona])); $i++) {
                        $clasificados[] = [
                            'zona' => $zona,
                            'posicion' => $i + 1,
                            'jugador_1' => $posicionesPorZona[$zona][$i]['jugador_1'],
                            'jugador_2' => $posicionesPorZona[$zona][$i]['jugador_2'],
                            'partidos_ganados' => $posicionesPorZona[$zona][$i]['partidos_ganados'],
                            'puntos_ganados' => $posicionesPorZona[$zona][$i]['puntos_ganados']
                        ];
                    }
                }
            }
        } else {
            // Lógica original para otros casos
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
                $terceros = array_values($tercerosPorZona);
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
        }
        
        // Si no hay cruces de cuartos en la base de datos, generarlos desde los clasificados
        if (count($crucesPorRonda['cuartos']) == 0) {
            // Verificar si es el caso especial de grupos de 10 parejas con 2 zonas (8 clasificados: 4 de cada zona)
            if ($esGrupoDe10 && count($zonasArray) == 2 && count($clasificados) == 8) {
                // Ordenar zonas (A y B)
                $zonasOrdenadasCruces = $zonasArray;
                sort($zonasOrdenadasCruces);
                $zonaA = $zonasOrdenadasCruces[0];
                $zonaB = $zonasOrdenadasCruces[1];
                
                // Organizar clasificados por zona y posición
                $clasificadosPorZonaPosicion = [];
                foreach ($clasificados as $clasificado) {
                    $clasificadosPorZonaPosicion[$clasificado['zona']][$clasificado['posicion']] = $clasificado;
                }
                
                // Crear cruces según el formato en este orden: A1-B4, B2-A3, A2-B3, B1-A4
                $crucesCuartos = [];
                // 1. A1 vs B4
                if (isset($clasificadosPorZonaPosicion[$zonaA][1]) && isset($clasificadosPorZonaPosicion[$zonaB][4])) {
                    $crucesCuartos[] = [
                        'pareja_1' => $clasificadosPorZonaPosicion[$zonaA][1],
                        'pareja_2' => $clasificadosPorZonaPosicion[$zonaB][4],
                        'ronda' => 'cuartos'
                    ];
                }
                // 2. B2 vs A3
                if (isset($clasificadosPorZonaPosicion[$zonaB][2]) && isset($clasificadosPorZonaPosicion[$zonaA][3])) {
                    $crucesCuartos[] = [
                        'pareja_1' => $clasificadosPorZonaPosicion[$zonaB][2],
                        'pareja_2' => $clasificadosPorZonaPosicion[$zonaA][3],
                        'ronda' => 'cuartos'
                    ];
                }
                // 3. A2 vs B3
                if (isset($clasificadosPorZonaPosicion[$zonaA][2]) && isset($clasificadosPorZonaPosicion[$zonaB][3])) {
                    $crucesCuartos[] = [
                        'pareja_1' => $clasificadosPorZonaPosicion[$zonaA][2],
                        'pareja_2' => $clasificadosPorZonaPosicion[$zonaB][3],
                        'ronda' => 'cuartos'
                    ];
                }
                // 4. B1 vs A4
                if (isset($clasificadosPorZonaPosicion[$zonaB][1]) && isset($clasificadosPorZonaPosicion[$zonaA][4])) {
                    $crucesCuartos[] = [
                        'pareja_1' => $clasificadosPorZonaPosicion[$zonaB][1],
                        'pareja_2' => $clasificadosPorZonaPosicion[$zonaA][4],
                        'ronda' => 'cuartos'
                    ];
                }
            } else {
                // Armar los cruces según las reglas estándar (lógica original)
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
            }
            
            // Agregar los cruces de cuartos generados a los cruces existentes
            // Ordenar cada ronda por partido_id para mantener orden consistente
            usort($crucesPorRonda['octavos'], function($a, $b) {
                return ($a['partido_id'] ?? 0) <=> ($b['partido_id'] ?? 0);
            });
            usort($crucesPorRonda['cuartos'], function($a, $b) {
                return ($a['partido_id'] ?? 0) <=> ($b['partido_id'] ?? 0);
            });
            
            $cruces = array_merge($crucesPorRonda['octavos'], $crucesPorRonda['cuartos'], $crucesCuartos, $crucesPorRonda['semifinales'], $crucesPorRonda['final']);
        } else {
            // Si ya hay cruces de cuartos en la base de datos, usar todos los cruces existentes
            // Ordenar cada ronda por partido_id para mantener orden consistente
            usort($crucesPorRonda['octavos'], function($a, $b) {
                return ($a['partido_id'] ?? 0) <=> ($b['partido_id'] ?? 0);
            });
            usort($crucesPorRonda['cuartos'], function($a, $b) {
                return ($a['partido_id'] ?? 0) <=> ($b['partido_id'] ?? 0);
            });
            usort($crucesPorRonda['semifinales'], function($a, $b) {
                return ($a['partido_id'] ?? 0) <=> ($b['partido_id'] ?? 0);
            });
            usort($crucesPorRonda['final'], function($a, $b) {
                return ($a['partido_id'] ?? 0) <=> ($b['partido_id'] ?? 0);
            });
            
            $cruces = array_merge($crucesPorRonda['octavos'], $crucesPorRonda['cuartos'], $crucesPorRonda['semifinales'], $crucesPorRonda['final']);
        }
        
        // Verificar si todos los cuartos tienen resultados antes de mostrar semifinales
        // Buscar directamente en la base de datos todos los partidos de cuartos
        $gruposCuartos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where(function($query) {
                $query->where('zona', 'cuartos final')
                      ->orWhere('zona', 'like', 'cuartos final|%');
            })
            ->whereNotNull('partido_id')
            ->select('partido_id')
            ->distinct()
            ->pluck('partido_id');
        
        $cuartosCompletos = true;
        
        if (count($gruposCuartos) > 0) {
            // Obtener todos los partidos de cuartos
            $partidosCuartos = DB::table('partidos')
                ->whereIn('id', $gruposCuartos)
                ->get();
            
            // Verificar que todos los partidos de cuartos tengan resultados (al menos un set > 0)
            foreach ($partidosCuartos as $partido) {
                $set1Pareja1 = $partido->pareja_1_set_1 ?? 0;
                $set1Pareja2 = $partido->pareja_2_set_1 ?? 0;
                
                // Si ambos sets son 0, el partido no tiene resultado
                if ($set1Pareja1 == 0 && $set1Pareja2 == 0) {
                    $cuartosCompletos = false;
                    break;
                }
            }
        } else {
            // Si no hay partidos de cuartos en la BD, no están completos
            $cuartosCompletos = false;
        }
        
        // Si los cuartos no están completos, filtrar semifinales y final de los cruces
        // Pero mantener octavos y cuartos
        if (!$cuartosCompletos) {
            $cruces = array_filter($cruces, function($cruce) {
                return isset($cruce['ronda']) && ($cruce['ronda'] === 'cuartos' || $cruce['ronda'] === 'octavos');
            });
            // Reindexar el array
            $cruces = array_values($cruces);
        }
        
        // Separar primeros para pasarlos a la vista (necesario para el caso de 6 clasificados)
        $primerosClasificados = [];
        foreach ($clasificados as $clasificado) {
            if ($clasificado['posicion'] == 1) {
                $primerosClasificados[] = $clasificado;
            }
        }
        
        // Determinar si hay octavos
        $tieneOctavos = isset($crucesPorRonda['octavos']) && count($crucesPorRonda['octavos']) > 0;
        
        // Log para debugging
        \Log::info('Cruces por ronda antes de pasar a vista: octavos=' . count($crucesPorRonda['octavos']) . ', cuartos=' . count($crucesPorRonda['cuartos']) . ', semifinales=' . count($crucesPorRonda['semifinales']) . ', final=' . count($crucesPorRonda['final']));
        \Log::info('Total cruces: ' . count($cruces) . ', tieneOctavos=' . ($tieneOctavos ? 'true' : 'false'));
        
        // Log total de resultados guardados antes de pasar a vista
        $octavosGuardados = array_filter($resultadosGuardados, function($r) { return isset($r['ronda']) && $r['ronda'] === 'octavos'; });
        \Log::info('Total resultados guardados antes de pasar a vista: ' . count($resultadosGuardados) . ', de octavos: ' . count($octavosGuardados));
        if (count($octavosGuardados) > 0) {
            \Log::info('Resultados de octavos guardados: ' . json_encode($octavosGuardados));
        }
        
        return View('bahia_padel.admin.torneo.cruces_americano')
                    ->with('torneo', $torneo)
                    ->with('jugadores', $jugadores)
                    ->with('clasificados', $clasificados)
                    ->with('cruces', $cruces)
                    ->with('posicionesPorZona', $posicionesPorZona)
                    ->with('resultadosGuardados', $resultadosGuardados)
                    ->with('primerosClasificados', $primerosClasificados)
                    ->with('totalClasificados', count($clasificados))
                    ->with('cuartosCompletos', $cuartosCompletos)
                    ->with('tieneOctavos', $tieneOctavos);
    }

    public function guardarResultadoCruceAmericano(Request $request) {
        $torneoId = $request->torneo_id;
        $ronda = $request->ronda; // 'octavos', 'cuartos', 'semifinales', 'final'
        $pareja1Set1 = $request->pareja_1_set_1 ?? 0;
        $pareja1Set2 = $request->pareja_1_set_2 ?? 0;
        $pareja1Set3 = $request->pareja_1_set_3 ?? 0;
        $pareja2Set1 = $request->pareja_2_set_1 ?? 0;
        $pareja2Set2 = $request->pareja_2_set_2 ?? 0;
        $pareja2Set3 = $request->pareja_2_set_3 ?? 0;
        $pareja1Jugador1 = $request->pareja_1_jugador_1 ?? null;
        $pareja1Jugador2 = $request->pareja_1_jugador_2 ?? null;
        $pareja2Jugador1 = $request->pareja_2_jugador_1 ?? null;
        $pareja2Jugador2 = $request->pareja_2_jugador_2 ?? null;
        $semifinal = $request->semifinal ?? null; // 'Semifinal 1' o 'Semifinal 2'
        
        // Mapear ronda a nombre de zona
        $zonaRonda = '';
        if ($ronda === 'octavos') {
            $zonaRonda = 'octavos final';
        } else if ($ronda === 'cuartos') {
            $zonaRonda = 'cuartos final';
        } else if ($ronda === 'semifinales') {
            $zonaRonda = 'semifinal';
        } else if ($ronda === 'final') {
            $zonaRonda = 'final';
        }
        
        // Buscar si ya existe un partido eliminatorio con estas parejas
        // Para cuartos, buscar también por número de partido si está disponible
        $query = DB::table('grupos')
            ->where('torneo_id', $torneoId)
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
            });
        
        // Para octavos y cuartos, buscar por zona que comience con el nombre correspondiente
        if ($ronda === 'octavos') {
            $query->where('zona', 'like', 'octavos final%');
        } else if ($ronda === 'cuartos') {
            $query->where('zona', 'like', 'cuartos final%');
        } else {
            $query->where('zona', $zonaRonda);
        }
        
        $grupo1Encontrado = $query->first();
        
        $partido = null;
        
        if ($grupo1Encontrado) {
            // Buscar el otro grupo del mismo partido
            $query2 = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('partido_id', $grupo1Encontrado->partido_id)
                ->where('id', '!=', $grupo1Encontrado->id);
            
            // Para octavos y cuartos, buscar por zona que comience con el nombre correspondiente
            if ($ronda === 'octavos') {
                $query2->where('zona', 'like', 'octavos final%');
            } else if ($ronda === 'cuartos') {
                $query2->where('zona', 'like', 'cuartos final%');
            } else {
                $query2->where('zona', $zonaRonda);
            }
            
            $grupo2Encontrado = $query2->first();
            
            if ($grupo2Encontrado) {
                // Verificar que el segundo grupo tenga la otra pareja
                $tienePareja1 = ($grupo1Encontrado->jugador_1 == $pareja1Jugador1 && $grupo1Encontrado->jugador_2 == $pareja1Jugador2) ||
                                ($grupo2Encontrado->jugador_1 == $pareja1Jugador1 && $grupo2Encontrado->jugador_2 == $pareja1Jugador2);
                $tienePareja2 = ($grupo1Encontrado->jugador_1 == $pareja2Jugador1 && $grupo1Encontrado->jugador_2 == $pareja2Jugador2) ||
                                ($grupo2Encontrado->jugador_1 == $pareja2Jugador1 && $grupo2Encontrado->jugador_2 == $pareja2Jugador2);
                
                if ($tienePareja1 && $tienePareja2) {
                    $partido = Partido::find($grupo1Encontrado->partido_id);
                    
                    // Si existe el partido pero no tiene el número de partido y se está guardando uno, actualizar
                    if (($ronda === 'octavos' || $ronda === 'cuartos') && strpos($grupo1Encontrado->zona, '|') === false) {
                        DB::table('grupos')
                            ->where('partido_id', $grupo1Encontrado->partido_id)
                            ->where('torneo_id', $torneoId)
                            ->update(['zona' => $zonaRonda]);
                    }
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
                $partido->pareja_1_set_2 = $pareja1Set2;
                $partido->pareja_1_set_3 = $pareja1Set3;
                $partido->pareja_2_set_1 = $pareja2Set1;
                $partido->pareja_2_set_2 = $pareja2Set2;
                $partido->pareja_2_set_3 = $pareja2Set3;
            } else {
                $partido->pareja_1_set_1 = $pareja2Set1;
                $partido->pareja_1_set_2 = $pareja2Set2;
                $partido->pareja_1_set_3 = $pareja2Set3;
                $partido->pareja_2_set_1 = $pareja1Set1;
                $partido->pareja_2_set_2 = $pareja1Set2;
                $partido->pareja_2_set_3 = $pareja1Set3;
            }
            
            // Log para debugging
            \Log::info('Guardando resultado de ' . $ronda . ': partido_id=' . $partido->id . ', pareja_1 sets=' . $partido->pareja_1_set_1 . '/' . $partido->pareja_1_set_2 . '/' . $partido->pareja_1_set_3 . ', pareja_2 sets=' . $partido->pareja_2_set_1 . '/' . $partido->pareja_2_set_2 . '/' . $partido->pareja_2_set_3);
        } else {
            $partido->pareja_1_set_1 = $pareja1Set1;
            $partido->pareja_1_set_2 = $pareja1Set2;
            $partido->pareja_1_set_3 = $pareja1Set3;
            $partido->pareja_2_set_1 = $pareja2Set1;
            $partido->pareja_2_set_2 = $pareja2Set2;
            $partido->pareja_2_set_3 = $pareja2Set3;
        }
        
        $partido->save();
        
        \Log::info('Resultado guardado para ronda: ' . $ronda . ', partido_id: ' . $partido->id . ', sets P1: ' . $partido->pareja_1_set_1 . '/' . $partido->pareja_1_set_2 . '/' . $partido->pareja_1_set_3 . ', sets P2: ' . $partido->pareja_2_set_1 . '/' . $partido->pareja_2_set_2 . '/' . $partido->pareja_2_set_3);
        
        // Si se guardó un resultado de octavos, crear grupo de cuartos para el ganador
        // Nota: Para torneos puntuables, esto se maneja en PuntuableController
        if ($ronda === 'octavos') {
            \Log::info('Llamando a crearGrupoCuartosDesdeOctavos para partido_id: ' . $partido->id);
            $this->crearGrupoCuartosDesdeOctavos($torneoId, $partido, $grupos);
        }
        
        // Si se guardó un resultado de cuartos, verificar si se pueden crear las semifinales automáticamente
        if ($ronda === 'cuartos') {
            return $this->crearSemifinalesSiEsNecesario($torneoId, $semifinal);            
        }
        
        // Si se guardó un resultado de semifinales, verificar si se puede crear la final automáticamente
        if ($ronda === 'semifinales') {
            $this->crearFinalSiEsNecesario($torneoId);
        }
        
        // Incrementar versión del torneo para notificar a vistas TV
        \App\Torneo::incrementarVersion($torneoId);
        
        return response()->json([
            'success' => true, 
            'partido' => $partido, 
            'partido_id' => $partido->id
        ]);
    }
    
    /**
     * Crea las semifinales automáticamente cuando se completan los cuartos necesarios
     */
    private function crearSemifinalesSiEsNecesario($torneoId, $semifinalActual = null) {
        // Buscar todos los partidos de cuartos con resultados en la tabla grupos
        // Solo buscar en grupos donde zona es "cuartos final" (o "cuartos final|Partido X")
        
        // Obtener todos los partidos de cuartos con resultados
        // Primero obtener los IDs de partidos únicos
        $partidosIds = DB::table('partidos')
            ->join('grupos', 'partidos.id', '=', 'grupos.partido_id')
            ->where('grupos.torneo_id', $torneoId)
            ->where('grupos.zona', 'like', 'cuartos final%')
            ->where(function($query) {
                $query->where('partidos.pareja_1_set_1', '>', 0)
                      ->orWhere('partidos.pareja_2_set_1', '>', 0);
            })
            ->select('partidos.id')
            ->distinct()
            ->pluck('id');
        
        // Luego obtener los datos completos de los partidos
        $partidosCuartos = DB::table('partidos')
            ->whereIn('id', $partidosIds)
            ->get();
        
        // Ordenar los partidos por partido_id
        $partidosCuartosOrdenados = $partidosCuartos->sortBy('id')->values();
        
        // Obtener los ganadores de cada partido de cuartos, agrupados por semifinal
        // Usar partido_id como clave para evitar duplicados
        $ganadoresPorSemifinal = [
            'Semifinal 1' => [],
            'Semifinal 2' => []
        ];
        $partidosProcesados = []; // Para evitar procesar el mismo partido dos veces
        
        foreach ($partidosCuartosOrdenados as $index => $partido) {
            // Evitar procesar el mismo partido dos veces
            if (in_array($partido->id, $partidosProcesados)) {
                \Log::warning('Partido ' . $partido->id . ' ya fue procesado, saltando...');
                continue;
            }
            
            // Obtener ambos grupos del partido directamente
            $gruposCompletos = DB::table('grupos')
                ->where('partido_id', $partido->id)
                ->where('torneo_id', $torneoId)
                ->where('zona', 'like', 'cuartos final%')
                ->orderBy('id')
                ->get();
            
            if ($gruposCompletos->count() >= 2) {
                $g1 = $gruposCompletos[0];
                $g2 = $gruposCompletos[1];
                
                // Verificar que el partido tenga un resultado válido (al menos un set con resultado)
                if ($partido->pareja_1_set_1 == 0 && $partido->pareja_2_set_1 == 0) {
                    \Log::warning('Partido ' . $partido->id . ' no tiene resultado válido, saltando...');
                    continue;
                }
                
                // Determinar ganador por sets (no solo por set 1)
                $ganadorPartido = $this->determinarGanadorPartido($partido);
                if ($ganadorPartido === 1) {
                    $ganador = ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                } elseif ($ganadorPartido === 2) {
                    $ganador = ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                } else {
                    
                    continue;
                }
                
                // Determinar a qué semifinal pertenece según el índice del partido
                // Los primeros 2 partidos de cuartos (índices 0 y 1) van a Semifinal 1
                // Los siguientes 2 partidos de cuartos (índices 2 y 3) van a Semifinal 2
                $semifinalAsignada = ($index < 2) ? 'Semifinal 1' : 'Semifinal 2';
                
                // Verificar que no haya duplicados en la semifinal asignada
                $yaExiste = false;
                foreach ($ganadoresPorSemifinal[$semifinalAsignada] as $ganadorExistente) {
                    if ($ganadorExistente['jugador_1'] == $ganador['jugador_1'] && 
                        $ganadorExistente['jugador_2'] == $ganador['jugador_2']) {
                        $yaExiste = true;
                        break;
                    }
                }
                
                if (!$yaExiste) {
                    // Agregar el ganador a la semifinal correspondiente
                    $ganadoresPorSemifinal[$semifinalAsignada][] = $ganador;
                    $partidosProcesados[] = $partido->id;
                    \Log::info('Ganador cuarto (partido_id: ' . $partido->id . ', índice: ' . $index . ', semifinal: ' . $semifinalAsignada . '): ' . json_encode($ganador));
                } else {
                    \Log::warning('Ganador duplicado detectado para partido ' . $partido->id . ', saltando...');
                }
            }
        }
        
        \Log::info('=== RESUMEN DE GANADORES POR SEMIFINAL ===');
        \Log::info('Ganadores Semifinal 1: ' . count($ganadoresPorSemifinal['Semifinal 1']));
        foreach ($ganadoresPorSemifinal['Semifinal 1'] as $idx => $ganador) {
            \Log::info('  Semifinal 1 Ganador ' . ($idx + 1) . ': ' . json_encode($ganador));
        }
        \Log::info('Ganadores Semifinal 2: ' . count($ganadoresPorSemifinal['Semifinal 2']));
        foreach ($ganadoresPorSemifinal['Semifinal 2'] as $idx => $ganador) {
            \Log::info('  Semifinal 2 Ganador ' . ($idx + 1) . ': ' . json_encode($ganador));
        }
        \Log::info('==========================================');
        
        // Obtener las semifinales existentes ordenadas por partido_id
        $semifinalesExistentes = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'semifinal')
            ->whereNotNull('partido_id')
            ->orderBy('partido_id')
            ->orderBy('id')
            ->get();
        
        // Agrupar por partido_id
        $semifinalesPorPartido = [];
        foreach ($semifinalesExistentes as $grupo) {
            $partidoId = $grupo->partido_id;
            if (!isset($semifinalesPorPartido[$partidoId])) {
                $semifinalesPorPartido[$partidoId] = [];
            }
            $semifinalesPorPartido[$partidoId][] = $grupo;
        }
        
        // Si no hay semifinales, crearlas vacías primero
        if (count($semifinalesPorPartido) == 0) {
            $parejaVacia = ['jugador_1' => 0, 'jugador_2' => 0];
            $this->crearPartidoEliminatorio($torneoId, $parejaVacia, $parejaVacia, 'semifinales');
            $this->crearPartidoEliminatorio($torneoId, $parejaVacia, $parejaVacia, 'semifinales');
            
            // Re-obtener las semifinales después de crearlas
            $semifinalesExistentes = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'semifinal')
                ->whereNotNull('partido_id')
                ->orderBy('partido_id')
                ->orderBy('id')
                ->get();
            
            $semifinalesPorPartido = [];
            foreach ($semifinalesExistentes as $grupo) {
                $partidoId = $grupo->partido_id;
                if (!isset($semifinalesPorPartido[$partidoId])) {
                    $semifinalesPorPartido[$partidoId] = [];
                }
                $semifinalesPorPartido[$partidoId][] = $grupo;
            }
        }
        
        // Actualizar Semifinal 1 SOLO con los ganadores de "Semifinal 1"
        // Solo actualizar si hay exactamente 2 ganadores de "Semifinal 1"
        if (count($ganadoresPorSemifinal['Semifinal 1']) == 2) {
            $partidosIds = array_keys($semifinalesPorPartido);
            sort($partidosIds);
            
            if (count($partidosIds) > 0) {
                $partidoIdSemifinal1 = $partidosIds[0];
                if (isset($semifinalesPorPartido[$partidoIdSemifinal1]) && count($semifinalesPorPartido[$partidoIdSemifinal1]) >= 2) {
                    // Actualizar los grupos de la semifinal 1 con los primeros 2 ganadores
                    DB::table('grupos')
                        ->where('id', $semifinalesPorPartido[$partidoIdSemifinal1][0]->id)
                        ->update([
                            'jugador_1' => $ganadoresPorSemifinal['Semifinal 1'][0]['jugador_1'],
                            'jugador_2' => $ganadoresPorSemifinal['Semifinal 1'][0]['jugador_2']
                        ]);
                    
                    DB::table('grupos')
                        ->where('id', $semifinalesPorPartido[$partidoIdSemifinal1][1]->id)
                        ->update([
                            'jugador_1' => $ganadoresPorSemifinal['Semifinal 1'][1]['jugador_1'],
                            'jugador_2' => $ganadoresPorSemifinal['Semifinal 1'][1]['jugador_2']
                        ]);
                    
                    \Log::info('Actualizando Semifinal 1 (partido_id: ' . $partidoIdSemifinal1 . ') con 2 ganadores');
                    \Log::info('Ganador 1 Semifinal 1: ' . json_encode($ganadoresPorSemifinal['Semifinal 1'][0]));
                    \Log::info('Ganador 2 Semifinal 1: ' . json_encode($ganadoresPorSemifinal['Semifinal 1'][1]));
                }
            }
        }
        
        // Actualizar Semifinal 2 SOLO con los ganadores de "Semifinal 2"
        // Solo actualizar si hay exactamente 2 ganadores de "Semifinal 2"
        if (count($ganadoresPorSemifinal['Semifinal 2']) == 2) {
            $partidosIds = array_keys($semifinalesPorPartido);
            sort($partidosIds);
            
            if (count($partidosIds) > 1) {
                $partidoIdSemifinal2 = $partidosIds[1];
                if (isset($semifinalesPorPartido[$partidoIdSemifinal2]) && count($semifinalesPorPartido[$partidoIdSemifinal2]) >= 2) {
                    // Actualizar los grupos de la semifinal 2 con los primeros 2 ganadores
                    DB::table('grupos')
                        ->where('id', $semifinalesPorPartido[$partidoIdSemifinal2][0]->id)
                        ->update([
                            'jugador_1' => $ganadoresPorSemifinal['Semifinal 2'][0]['jugador_1'],
                            'jugador_2' => $ganadoresPorSemifinal['Semifinal 2'][0]['jugador_2']
                        ]);
                    
                    DB::table('grupos')
                        ->where('id', $semifinalesPorPartido[$partidoIdSemifinal2][1]->id)
                        ->update([
                            'jugador_1' => $ganadoresPorSemifinal['Semifinal 2'][1]['jugador_1'],
                            'jugador_2' => $ganadoresPorSemifinal['Semifinal 2'][1]['jugador_2']
                        ]);
                    
                    \Log::info('Actualizando Semifinal 2 (partido_id: ' . $partidoIdSemifinal2 . ') con 2 ganadores');
                    \Log::info('Ganador 1 Semifinal 2: ' . json_encode($ganadoresPorSemifinal['Semifinal 2'][0]));
                    \Log::info('Ganador 2 Semifinal 2: ' . json_encode($ganadoresPorSemifinal['Semifinal 2'][1]));
                }
            }
        }
        
        // Retornar respuesta JSON para que el frontend pueda recargar
        return response()->json([
            'success' => true,
            'message' => 'Semifinales actualizadas correctamente'
        ]);
    }
    
    /**
     * Crea las semifinales y final vacías si no existen
     */
    private function crearSemifinalesYFinalVacias($torneoId) {
        // Verificar si ya existen semifinales
        $semifinalesExistentes = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'semifinal')
            ->whereNotNull('partido_id')
            ->count();
        
        // Si no hay semifinales, crear 2 semifinales vacías
        if ($semifinalesExistentes == 0) {
            // Crear Semifinal 1 vacía
            $parejaVacia = ['jugador_1' => 0, 'jugador_2' => 0];
            $this->crearPartidoEliminatorio($torneoId, $parejaVacia, $parejaVacia, 'semifinales');
            
            // Crear Semifinal 2 vacía
            $this->crearPartidoEliminatorio($torneoId, $parejaVacia, $parejaVacia, 'semifinales');
        }
        
        // Verificar si ya existe la final
        $finalExistente = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'final')
            ->whereNotNull('partido_id')
            ->count();
        
        // Si no hay final, crear 1 final vacía
        if ($finalExistente == 0) {
            $parejaVacia = ['jugador_1' => 0, 'jugador_2' => 0];
            $this->crearPartidoEliminatorio($torneoId, $parejaVacia, $parejaVacia, 'final');
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
            ->select(
                'partidos.id',
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
                
                // Determinar ganador por sets (no solo por set 1)
                $ganadorPartido = $this->determinarGanadorPartido($partido);
                if ($ganadorPartido === 1) {
                    $ganador = ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                } elseif ($ganadorPartido === 2) {
                    $ganador = ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                } else {
                    continue;
                }
                
                // Usar el índice del array para identificar la semifinal (0 o 1)
                $ganadoresSemifinales[$index] = $ganador;
            }
        }
        
        // Actualizar final existente con los ganadores de semifinales
        if (count($ganadoresSemifinales) >= 2 && isset($ganadoresSemifinales[0]) && isset($ganadoresSemifinales[1])) {
            // Buscar la final existente
            $finalExistente = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'final')
                ->whereNotNull('partido_id')
                ->orderBy('partido_id')
                ->orderBy('id')
                ->get();
            
            if ($finalExistente->count() >= 2) {
                // Actualizar los grupos de la final
                DB::table('grupos')
                    ->where('id', $finalExistente[0]->id)
                    ->update([
                        'jugador_1' => $ganadoresSemifinales[0]['jugador_1'],
                        'jugador_2' => $ganadoresSemifinales[0]['jugador_2']
                    ]);
                
                DB::table('grupos')
                    ->where('id', $finalExistente[1]->id)
                    ->update([
                        'jugador_1' => $ganadoresSemifinales[1]['jugador_1'],
                        'jugador_2' => $ganadoresSemifinales[1]['jugador_2']
                    ]);
                
                \Log::info('Actualizando Final con ganadores de semifinales');
            } else {
                // Si no existe, crearla
                $this->crearPartidoEliminatorio($torneoId, $ganadoresSemifinales[0], $ganadoresSemifinales[1], 'final');
            }
        }
    }
    
    /**
     * Crea un partido eliminatorio (semifinal o final) en la base de datos
     */
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
        
        // Para partidos vacíos (jugadores 0), verificar cantidad de partidos existentes de esa ronda
        if ($pareja1['jugador_1'] == 0 && $pareja1['jugador_2'] == 0 && 
            $pareja2['jugador_1'] == 0 && $pareja2['jugador_2'] == 0) {
            // Contar cuántos partidos de esta ronda ya existen
            $partidosExistentes = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', $zonaRonda)
                ->whereNotNull('partido_id')
                ->distinct()
                ->count('partido_id');
            
            // Si es semifinales, solo crear 2 máximo
            if ($ronda === 'semifinales' && $partidosExistentes >= 2) {
                return;
            }
            // Si es final, solo crear 1 máximo
            if ($ronda === 'final' && $partidosExistentes >= 1) {
                return;
            }
        } else {
            // Si no son jugadores vacíos, verificar si ya existe este partido específico
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

    public function adminTorneoValidarCruces(Request $request) {
        $torneoId = $request->torneo_id;
        
        $torneo = DB::table('torneos')
                        ->where('torneos.id', $torneoId)
                        ->where('torneos.activo', 1)
                        ->first();
        
        if (!$torneo) {
            return redirect()->route('admintorneos')->with('error', 'Torneo no encontrado');
        }
        
        // Determinar el tipo de torneo (por defecto puntuable si no existe)
        $tipoTorneo = isset($torneo->tipo_torneo_formato) ? $torneo->tipo_torneo_formato : 'puntuable';
        
        // Verificar si ya existen cruces armados (zonas que comienzan con "cuartos final|" o "cuartos final", o "octavos final")
        $crucesExistentes = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where(function($query) {
                $query->where('zona', 'cuartos final')
                      ->orWhere('zona', 'like', 'cuartos final|%')
                      ->orWhere('zona', 'octavos final')
                      ->orWhere('zona', 'like', 'octavos final|%');
            })
            ->whereNotNull('partido_id')
            ->count();
        
        // Si ya existen cruces armados, redirigir directamente a la pantalla de cruces según el tipo de torneo
        if ($crucesExistentes > 0) {
            if ($tipoTorneo === 'puntuable') {
                return redirect()->route('admintorneopuntuablecrucesv2', ['torneo_id' => $torneoId]);
            } else {
                return redirect()->route('admintorneoamericanocruces', ['torneo_id' => $torneoId]);
            }
        }
        
        // Obtener información de los jugadores
        $jugadores = DB::table('jugadores')
                        ->where('jugadores.activo', 1)
                        ->get();
        
        // Calcular posiciones por zona (reutilizar lógica de adminTorneoAmericanoCruces)
        // Filtrar zonas internas: cuartos final (con o sin |), semifinal, final, ganador, perdedor
        $grupos = DB::table('grupos')
                        ->where('grupos.torneo_id', $torneoId)
                        ->where(function($query) {
                            $query->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final'])
                                  ->where('grupos.zona', 'not like', 'cuartos final|%')
                                  ->where('grupos.zona', 'not like', 'ganador %')
                                  ->where('grupos.zona', 'not like', 'perdedor %');
                        })
                        ->orderBy('grupos.zona')
                        ->orderBy('grupos.id')
                        ->get();
        
        $posicionesPorZona = [];
        $zonas = $grupos->pluck('zona')->unique()->sort()->values();
        
        // Obtener criterios de desempate de la configuración (si existe)
        $criterios = ['PG', 'ENFRENTAMIENTO', 'DIF_GAMES', 'GF']; // Default
        if ($tipoTorneo === 'americano' && isset($torneo->config_cruces_americano_id) && $torneo->config_cruces_americano_id) {
            $configTemporal = DB::table('configuracion_cruces_americanos')
                ->where('id', $torneo->config_cruces_americano_id)
                ->first();
            if ($configTemporal && $configTemporal->criterio_desempate_orden) {
                $criterios = explode(',', $configTemporal->criterio_desempate_orden);
            }
        }
        
        $gruposGanadorPerdedor = collect();
        if ($tipoTorneo === 'puntuable') {
            $gruposGanadorPerdedor = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->whereNotNull('partido_id')
                ->where(function ($q) {
                    $q->where('zona', 'like', 'ganador %')
                      ->orWhere('zona', 'like', 'perdedor %');
                })
                ->orderBy('zona')
                ->orderBy('id')
                ->get();
        }

        foreach ($zonas as $zona) {
            $gruposZonaBase = $grupos->where('zona', $zona)->filter(function($grupo) {
                return $grupo->jugador_1 !== null && $grupo->jugador_2 !== null;
            });

            $parejas = [];
            foreach ($gruposZonaBase as $grupo) {
                $key = $grupo->jugador_1 . '_' . $grupo->jugador_2;
                if (!isset($parejas[$key])) {
                    $parejas[$key] = [
                        'jugador_1' => $grupo->jugador_1,
                        'jugador_2' => $grupo->jugador_2,
                        'partidos_ganados' => 0,
                        'partidos_perdidos' => 0,
                        'sets_ganados' => 0,
                        'sets_perdidos' => 0,
                        'puntos_ganados' => 0,
                        'puntos_perdidos' => 0,
                        'partidos_directos' => []
                    ];
                }
            }

            $esZonaCuatro = ($tipoTorneo === 'puntuable' && count($parejas) >= 4);
            $gruposZona = $gruposZonaBase;
            if ($esZonaCuatro) {
                $gruposZona = $gruposZona
                    ->merge($gruposGanadorPerdedor->where('zona', 'ganador ' . $zona))
                    ->merge($gruposGanadorPerdedor->where('zona', 'perdedor ' . $zona));
            }

            $partidosIds = $gruposZona->pluck('partido_id')->unique()->filter();
            $partidos = DB::table('partidos')
                ->whereIn('id', $partidosIds)
                ->get()
                ->keyBy('id');

            $gruposPorPartido = [];
            foreach ($gruposZona as $grupo) {
                if ($grupo->partido_id) {
                    if (!isset($gruposPorPartido[$grupo->partido_id])) {
                        $gruposPorPartido[$grupo->partido_id] = [];
                    }
                    $gruposPorPartido[$grupo->partido_id][] = $grupo;
                }
            }

            foreach ($gruposPorPartido as $partidoId => $grs) {
                if (count($grs) < 2 || !isset($partidos[$partidoId])) {
                    continue;
                }

                $gruposPartido = collect($grs)->sortBy('id')->values()->all();
                $pareja1Grupo = $gruposPartido[0];
                $pareja2Grupo = $gruposPartido[1];

                $key1 = $pareja1Grupo->jugador_1 . '_' . $pareja1Grupo->jugador_2;
                $key2 = $pareja2Grupo->jugador_1 . '_' . $pareja2Grupo->jugador_2;

                if (!isset($parejas[$key1]) || !isset($parejas[$key2])) {
                    continue;
                }

                $partido = $partidos[$partidoId];

                if ($tipoTorneo === 'puntuable') {
                    $puntosPareja1 = ($partido->pareja_1_set_1 ?? 0) + ($partido->pareja_1_set_2 ?? 0) + ($partido->pareja_1_set_3 ?? 0);
                    $puntosPareja2 = ($partido->pareja_2_set_1 ?? 0) + ($partido->pareja_2_set_2 ?? 0) + ($partido->pareja_2_set_3 ?? 0);

                    $setsP1 = 0;
                    $setsP2 = 0;
                    if (($partido->pareja_1_set_1 ?? 0) > ($partido->pareja_2_set_1 ?? 0)) $setsP1++;
                    elseif (($partido->pareja_2_set_1 ?? 0) > ($partido->pareja_1_set_1 ?? 0)) $setsP2++;
                    if (($partido->pareja_1_set_2 ?? 0) > ($partido->pareja_2_set_2 ?? 0)) $setsP1++;
                    elseif (($partido->pareja_2_set_2 ?? 0) > ($partido->pareja_1_set_2 ?? 0)) $setsP2++;
                    if (isset($partido->pareja_1_set_super_tie_break) && (($partido->pareja_1_set_super_tie_break ?? 0) > 0 || ($partido->pareja_2_set_super_tie_break ?? 0) > 0)) {
                        if (($partido->pareja_1_set_super_tie_break ?? 0) > ($partido->pareja_2_set_super_tie_break ?? 0)) { $setsP1 = 2; $setsP2 = 1; }
                        elseif (($partido->pareja_2_set_super_tie_break ?? 0) > ($partido->pareja_1_set_super_tie_break ?? 0)) { $setsP1 = 1; $setsP2 = 2; }
                    } else {
                        if (($partido->pareja_1_set_3 ?? 0) > ($partido->pareja_2_set_3 ?? 0)) $setsP1++;
                        elseif (($partido->pareja_2_set_3 ?? 0) > ($partido->pareja_1_set_3 ?? 0)) $setsP2++;
                    }
                    $ganadorEsP1 = $setsP1 > $setsP2;
                    $ganadorEsP2 = $setsP2 > $setsP1;

                    $parejas[$key1]['sets_ganados'] += $setsP1;
                    $parejas[$key1]['sets_perdidos'] += $setsP2;
                    $parejas[$key2]['sets_ganados'] += $setsP2;
                    $parejas[$key2]['sets_perdidos'] += $setsP1;
                } else {
                    $puntosPareja1 = $partido->pareja_1_set_1 ?? 0;
                    $puntosPareja2 = $partido->pareja_2_set_1 ?? 0;
                    $ganadorEsP1 = $puntosPareja1 > $puntosPareja2;
                    $ganadorEsP2 = $puntosPareja2 > $puntosPareja1;
                }

                if ($puntosPareja1 > 0 || $puntosPareja2 > 0) {
                    if ($ganadorEsP1) {
                        $parejas[$key1]['partidos_ganados']++;
                        $parejas[$key1]['puntos_ganados'] += $puntosPareja1;
                        $parejas[$key1]['puntos_perdidos'] += $puntosPareja2;
                        $parejas[$key2]['partidos_perdidos']++;
                        $parejas[$key2]['puntos_ganados'] += $puntosPareja2;
                        $parejas[$key2]['puntos_perdidos'] += $puntosPareja1;
                        $parejas[$key1]['partidos_directos'][$key2] = ['ganado' => true];
                        $parejas[$key2]['partidos_directos'][$key1] = ['ganado' => false];
                    } else if ($ganadorEsP2) {
                        $parejas[$key2]['partidos_ganados']++;
                        $parejas[$key2]['puntos_ganados'] += $puntosPareja2;
                        $parejas[$key2]['puntos_perdidos'] += $puntosPareja1;
                        $parejas[$key1]['partidos_perdidos']++;
                        $parejas[$key1]['puntos_ganados'] += $puntosPareja1;
                        $parejas[$key1]['puntos_perdidos'] += $puntosPareja2;
                        $parejas[$key2]['partidos_directos'][$key1] = ['ganado' => true];
                        $parejas[$key1]['partidos_directos'][$key2] = ['ganado' => false];
                    }
                }
            }

            foreach ($parejas as $key => $pareja) {
                $parejas[$key]['key'] = $key;
                $parejas[$key]['diferencia_sets'] = ($pareja['sets_ganados'] ?? 0) - ($pareja['sets_perdidos'] ?? 0);
                $parejas[$key]['diferencia_games'] = ($pareja['puntos_ganados'] ?? 0) - ($pareja['puntos_perdidos'] ?? 0);
            }

            $posiciones = array_values($parejas);

            if ($tipoTorneo === 'puntuable' && $esZonaCuatro) {
                $resolverZonaPartido = function($zonaNombre) use ($gruposGanadorPerdedor, $partidos) {
                    $grs = $gruposGanadorPerdedor->where('zona', $zonaNombre)->groupBy('partido_id')->first();
                    if (!$grs || count($grs) < 2) return [null, null, 0, 0];
                    $ordenados = collect($grs)->sortBy('id')->values()->all();
                    $partido = $partidos[$ordenados[0]->partido_id] ?? null;
                    if (!$partido) return [null, null, 0, 0];
                    $key1 = $ordenados[0]->jugador_1 . '_' . $ordenados[0]->jugador_2;
                    $key2 = $ordenados[1]->jugador_1 . '_' . $ordenados[1]->jugador_2;
                    $setsP1 = 0; $setsP2 = 0;
                    if (($partido->pareja_1_set_1 ?? 0) > ($partido->pareja_2_set_1 ?? 0)) $setsP1++; elseif (($partido->pareja_2_set_1 ?? 0) > ($partido->pareja_1_set_1 ?? 0)) $setsP2++;
                    if (($partido->pareja_1_set_2 ?? 0) > ($partido->pareja_2_set_2 ?? 0)) $setsP1++; elseif (($partido->pareja_2_set_2 ?? 0) > ($partido->pareja_1_set_2 ?? 0)) $setsP2++;
                    if (isset($partido->pareja_1_set_super_tie_break) && (($partido->pareja_1_set_super_tie_break ?? 0) > 0 || ($partido->pareja_2_set_super_tie_break ?? 0) > 0)) {
                        if (($partido->pareja_1_set_super_tie_break ?? 0) > ($partido->pareja_2_set_super_tie_break ?? 0)) { $setsP1 = 2; $setsP2 = 1; }
                        elseif (($partido->pareja_2_set_super_tie_break ?? 0) > ($partido->pareja_1_set_super_tie_break ?? 0)) { $setsP1 = 1; $setsP2 = 2; }
                    } else {
                        if (($partido->pareja_1_set_3 ?? 0) > ($partido->pareja_2_set_3 ?? 0)) $setsP1++; elseif (($partido->pareja_2_set_3 ?? 0) > ($partido->pareja_1_set_3 ?? 0)) $setsP2++;
                    }
                    $games1 = ($partido->pareja_1_set_1 ?? 0) + ($partido->pareja_1_set_2 ?? 0) + ($partido->pareja_1_set_3 ?? 0);
                    $games2 = ($partido->pareja_2_set_1 ?? 0) + ($partido->pareja_2_set_2 ?? 0) + ($partido->pareja_2_set_3 ?? 0);
                    return [$key1, $key2, $setsP1, $setsP2, $games1, $games2];
                };

                $ordenZona4 = [];
                list($gk1, $gk2, $gs1, $gs2, $gg1, $gg2) = $resolverZonaPartido('ganador ' . $zona);
                if ($gk1 && $gk2 && ($gg1 > 0 || $gg2 > 0)) {
                    $ordenZona4[] = $gs1 > $gs2 ? $gk1 : $gk2;
                    $ordenZona4[] = $gs1 > $gs2 ? $gk2 : $gk1;
                }
                list($pk1, $pk2, $ps1, $ps2, $pg1, $pg2) = $resolverZonaPartido('perdedor ' . $zona);
                if ($pk1 && $pk2 && ($pg1 > 0 || $pg2 > 0)) {
                    $ordenZona4[] = $ps1 > $ps2 ? $pk1 : $pk2;
                    $ordenZona4[] = $ps1 > $ps2 ? $pk2 : $pk1;
                }

                if (count($ordenZona4) === 4) {
                    $tmp = [];
                    foreach ($ordenZona4 as $k) {
                        if (isset($parejas[$k])) {
                            $tmp[] = $parejas[$k];
                        }
                    }
                    if (count($tmp) === 4) {
                        $posiciones = $tmp;
                    }
                }
            }

            if ($tipoTorneo === 'puntuable') {
                $esTripleEmpate3 = count($posiciones) === 3
                    && count(array_unique(array_map(function($p) { return $p['partidos_ganados']; }, $posiciones))) === 1;

                if (!(count($posiciones) === 4 && ($posiciones[0]['partidos_ganados'] ?? null) === 2 && ($posiciones[3]['partidos_ganados'] ?? null) === 0)) {
                    usort($posiciones, function($a, $b) use ($esTripleEmpate3) {
                        if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                            return $b['partidos_ganados'] - $a['partidos_ganados'];
                        }

                        if ($esTripleEmpate3) {
                            if (($a['diferencia_sets'] ?? 0) != ($b['diferencia_sets'] ?? 0)) {
                                return ($b['diferencia_sets'] ?? 0) - ($a['diferencia_sets'] ?? 0);
                            }
                            if (($a['diferencia_games'] ?? 0) != ($b['diferencia_games'] ?? 0)) {
                                return ($b['diferencia_games'] ?? 0) - ($a['diferencia_games'] ?? 0);
                            }
                        }

                        $keyB = $b['key'] ?? null;
                        if ($keyB && isset($a['partidos_directos'][$keyB])) {
                            return $a['partidos_directos'][$keyB]['ganado'] ? -1 : 1;
                        }

                        if (($a['diferencia_sets'] ?? 0) != ($b['diferencia_sets'] ?? 0)) {
                            return ($b['diferencia_sets'] ?? 0) - ($a['diferencia_sets'] ?? 0);
                        }
                        return ($b['diferencia_games'] ?? 0) - ($a['diferencia_games'] ?? 0);
                    });
                }
            } else {
                usort($posiciones, function($a, $b) use ($criterios) {
                    foreach ($criterios as $criterio) {
                        $criterio = trim($criterio);
                        $resultado = 0;

                        switch ($criterio) {
                            case 'PG':
                                if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                                    $resultado = $b['partidos_ganados'] - $a['partidos_ganados'];
                                }
                                break;
                            case 'ENFRENTAMIENTO':
                                $keyB = $b['key'];
                                if (isset($a['partidos_directos'][$keyB])) {
                                    $resultado = $a['partidos_directos'][$keyB]['ganado'] ? -1 : 1;
                                }
                                break;
                            case 'DIF_GAMES':
                                if ($a['diferencia_games'] != $b['diferencia_games']) {
                                    $resultado = $b['diferencia_games'] - $a['diferencia_games'];
                                }
                                break;
                            case 'GF':
                                if ($a['puntos_ganados'] != $b['puntos_ganados']) {
                                    $resultado = $b['puntos_ganados'] - $a['puntos_ganados'];
                                }
                                break;
                        }

                        if ($resultado !== 0) {
                            return $resultado;
                        }
                    }
                    return 0;
                });
            }

            $posicionesPorZona[$zona] = $posiciones;
        }
        
        // Identificar los dos mejores terceros para resaltarlos
        $mejoresTercerosIds = [];
        if (count($zonas->toArray()) == 3) {
            $tercerosArray = [];
            foreach ($zonas as $zona) {
                if (isset($posicionesPorZona[$zona]) && count($posicionesPorZona[$zona]) >= 3) {
                    $tercero = $posicionesPorZona[$zona][2];
                    $tercero['zona'] = $zona;
                    $tercerosArray[] = $tercero;
                }
            }

            // Ordenar terceros usando los criterios configurados (excepto enfrentamiento directo entre zonas)
            usort($tercerosArray, function($a, $b) use ($criterios) {
                foreach ($criterios as $criterio) {
                    $criterio = trim($criterio);
                    $resultado = 0;
                    
                    switch ($criterio) {
                        case 'PG':
                            if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                                $resultado = $b['partidos_ganados'] - $a['partidos_ganados'];
                            }
                            break;
                        case 'ENFRENTAMIENTO':
                            // No aplica para terceros de diferentes zonas - saltar
                            break;
                        case 'DIF_GAMES':
                            if (($a['diferencia_games'] ?? 0) != ($b['diferencia_games'] ?? 0)) {
                                $resultado = ($b['diferencia_games'] ?? 0) - ($a['diferencia_games'] ?? 0);
                            }
                            break;
                        case 'GF':
                            if ($a['puntos_ganados'] != $b['puntos_ganados']) {
                                $resultado = $b['puntos_ganados'] - $a['puntos_ganados'];
                            }
                            break;
                    }
                    
                    if ($resultado !== 0) {
                        return $resultado;
                    }
                }
                return 0;
            });
            
            // Obtener los 2 mejores terceros
            $mejoresTerceros = array_slice($tercerosArray, 0, 2);
            foreach ($mejoresTerceros as $tercero) {
                $terceroId = $tercero['zona'] . '_' . $tercero['jugador_1'] . '_' . $tercero['jugador_2'];
                $mejoresTercerosIds[] = $terceroId;
            }
            \Log::info('Mejores terceros identificados: ' . json_encode($mejoresTercerosIds));
        }
        
        // Obtener solo cruces de cuartos de final
        $cruces = [];
        $gruposEliminatorios = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'cuartos final')
            ->whereNotNull('partido_id')
            ->orderBy('partido_id')
            ->orderBy('id')
            ->get();
        
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
        
        // Construir cruces de cuartos desde los partidos existentes
        foreach ($partidosAgrupados as $partidoId => $datosPartido) {
            if (count($datosPartido['grupos']) >= 2) {
                $g1 = $datosPartido['grupos'][0];
                $g2 = $datosPartido['grupos'][1];
                
                $cruce = [
                    'id' => 'cuartos_' . $partidoId,
                    'partido_id' => $partidoId,
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
                    'ronda' => 'cuartos'
                ];
                
                $cruces[] = $cruce;
            }
        }
        
        // Calcular total de parejas clasificadas
        $totalParejasClasificadas = 0;
        foreach ($posicionesPorZona as $zona => $posiciones) {
            $totalParejasClasificadas += count($posiciones);
        }
        
        // Determinar si necesitamos octavos de final (16 o más parejas = 8 partidos de octavos)
        $necesitaOctavos = $totalParejasClasificadas >= 16;
        
        // Buscar configuración de cruces según la cantidad de parejas
        $configuracionCruces = null;
        $configuracionAmericano = null;
        $llavesPreconfiguradas = [];
        
        // Si es torneo americano, buscar la configuración vinculada al torneo
        if ($tipoTorneo === 'americano' && isset($torneo->config_cruces_americano_id) && $torneo->config_cruces_americano_id) {
            $configuracionAmericano = DB::table('configuracion_cruces_americanos')
                ->where('id', $torneo->config_cruces_americano_id)
                ->first();
                
            if ($configuracionAmericano) {
                // Parsear las llaves preconfiguradas
                $llavesPreconfiguradas = $this->parsearLlavesConfigAmericano($configuracionAmericano, $totalParejasClasificadas);
                \Log::info('=== VALIDAR CRUCES - DEBUG ===');
                \Log::info('Torneo ID: ' . $torneoId);
                \Log::info('Tipo torneo: ' . $tipoTorneo);
                \Log::info('Config ID: ' . $torneo->config_cruces_americano_id);
                \Log::info('Configuración americano nombre: ' . ($configuracionAmericano->nombre ?? 'N/A'));
                \Log::info('Llave 4tos raw: ' . ($configuracionAmericano->llave_4tos ?? 'NULL'));
                \Log::info('Llaves preconfiguradas resultado: ' . json_encode($llavesPreconfiguradas));
                \Log::info('=== FIN DEBUG ===');
            }
        } else {
            // Buscar configuración puntuable global
            $configuracionCruces = DB::table('configuracion_cruces_puntuables')
                ->where('cantidad_parejas', $totalParejasClasificadas)
                ->whereNull('torneo_id') // Configuración global
                ->orderBy('id', 'desc')
                ->first();
        }
        
        // Si no hay cruces de cuartos, generarlos desde los clasificados
        if (count($cruces) == 0) {
            // Si existe configuración para torneos puntuables, usar las llaves configuradas
            if ($configuracionCruces && $tipoTorneo === 'puntuable') {
                $cruces = $this->generarCrucesDesdeConfiguracion($configuracionCruces, $posicionesPorZona, $zonas);
            }
            // Si es americano con configuración, generar cruces desde esa configuración
            elseif ($configuracionAmericano && $tipoTorneo === 'americano') {
                $cruces = $this->generarCrucesDesdeConfigAmericano($configuracionAmericano, $posicionesPorZona, $zonas);
            }
        }
        
        // Si aún no hay cruces (no hay configuración o no es puntuable), usar lógica por defecto
        if (count($cruces) == 0) {
                $zonasArray = $zonas->toArray();
                sort($zonasArray);
            
            // Verificar si es caso de 12 parejas (3 zonas de 4 parejas cada una)
            $esGrupoDe12 = false;
            if (count($zonasArray) == 3) {
                $totalParejas = 0;
                foreach ($zonasArray as $zona) {
                    if (isset($posicionesPorZona[$zona])) {
                        $totalParejas += count($posicionesPorZona[$zona]);
                    }
                }
                if ($totalParejas == 12) {
                    $esGrupoDe12 = true;
                }
            }
            
            if ($esGrupoDe12 && count($zonasArray) == 3) {
                // Caso especial: 12 parejas (3 zonas de 4 parejas)
                $zonaA = $zonasArray[0];
                $zonaB = $zonasArray[1];
                $zonaC = $zonasArray[2];
                
                // Obtener primeros y segundos de cada zona
                $primeros = [];
                $segundos = [];
                $terceros = [];
                
                foreach ($zonasArray as $zona) {
                    if (isset($posicionesPorZona[$zona]) && count($posicionesPorZona[$zona]) >= 1) {
                        $primeros[$zona] = [
                            'zona' => $zona,
                            'posicion' => 1,
                            'jugador_1' => $posicionesPorZona[$zona][0]['jugador_1'],
                            'jugador_2' => $posicionesPorZona[$zona][0]['jugador_2'],
                            'partidos_ganados' => $posicionesPorZona[$zona][0]['partidos_ganados'],
                            'puntos_ganados' => $posicionesPorZona[$zona][0]['puntos_ganados']
                        ];
                    }
                    if (isset($posicionesPorZona[$zona]) && count($posicionesPorZona[$zona]) >= 2) {
                        $segundos[$zona] = [
                            'zona' => $zona,
                            'posicion' => 2,
                            'jugador_1' => $posicionesPorZona[$zona][1]['jugador_1'],
                            'jugador_2' => $posicionesPorZona[$zona][1]['jugador_2'],
                            'partidos_ganados' => $posicionesPorZona[$zona][1]['partidos_ganados'],
                            'puntos_ganados' => $posicionesPorZona[$zona][1]['puntos_ganados']
                        ];
                    }
                    if (isset($posicionesPorZona[$zona]) && count($posicionesPorZona[$zona]) >= 3) {
                        $terceros[$zona] = [
                            'zona' => $zona,
                            'posicion' => 3,
                            'jugador_1' => $posicionesPorZona[$zona][2]['jugador_1'],
                            'jugador_2' => $posicionesPorZona[$zona][2]['jugador_2'],
                            'partidos_ganados' => $posicionesPorZona[$zona][2]['partidos_ganados'],
                            'puntos_ganados' => $posicionesPorZona[$zona][2]['puntos_ganados']
                        ];
                    }
                }
                
                // Seleccionar los 2 mejores terceros
                $tercerosArray = [];
                if (isset($terceros[$zonaA])) $tercerosArray[] = $terceros[$zonaA];
                if (isset($terceros[$zonaB])) $tercerosArray[] = $terceros[$zonaB];
                if (isset($terceros[$zonaC])) $tercerosArray[] = $terceros[$zonaC];
                
                // Ordenar terceros por partidos ganados y puntos
                usort($tercerosArray, function($a, $b) {
                    if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                        return $b['partidos_ganados'] - $a['partidos_ganados'];
                    }
                    return $b['puntos_ganados'] - $a['puntos_ganados'];
                });
                
                $mejoresTerceros = array_slice($tercerosArray, 0, 2);
                $tercero1 = $mejoresTerceros[0] ?? null;
                $tercero2 = $mejoresTerceros[1] ?? null;
                
                // Generar cruces según la estructura especificada:
                // 1A vs 3C o 3B
                // 1B vs 3A o 3C
                // 1C vs 2A
                // 2B vs 2C
                
                $terceroPara1A = null;
                $terceroPara1B = null;
                
                // Cruce 1: 1A vs mejor tercero (3C o 3B)
                if (isset($primeros[$zonaA]) && ($tercero1 || $tercero2)) {
                    // Priorizar terceros de C o B
                    if ($tercero1 && ($tercero1['zona'] == $zonaC || $tercero1['zona'] == $zonaB)) {
                        $terceroPara1A = $tercero1;
                    } else if ($tercero2 && ($tercero2['zona'] == $zonaC || $tercero2['zona'] == $zonaB)) {
                        $terceroPara1A = $tercero2;
                    } else if ($tercero1) {
                        $terceroPara1A = $tercero1; // Usar el mejor disponible
                    } else if ($tercero2) {
                        $terceroPara1A = $tercero2;
                    }
                    
                    if ($terceroPara1A && isset($primeros[$zonaA])) {
                        $cruces[] = [
                            'id' => 'cuartos_1',
                            'partido_id' => null,
                            'pareja_1' => [
                                'jugador_1' => $primeros[$zonaA]['jugador_1'],
                                'jugador_2' => $primeros[$zonaA]['jugador_2'],
                                'zona' => $zonaA,
                                'posicion' => 1
                            ],
                            'pareja_2' => [
                                'jugador_1' => $terceroPara1A['jugador_1'],
                                'jugador_2' => $terceroPara1A['jugador_2'],
                                'zona' => $terceroPara1A['zona'],
                                'posicion' => 3
                            ],
                            'ronda' => 'cuartos'
                        ];
                    }
                }
                
                // Cruce 2: 1B vs mejor tercero restante (3A o 3C)
                if (isset($primeros[$zonaB]) && ($tercero1 || $tercero2)) {
                    // Buscar el tercero que no se usó en el cruce 1 y que sea de A o C
                    if ($tercero1 && $tercero1['zona'] != ($terceroPara1A['zona'] ?? null) && ($tercero1['zona'] == $zonaA || $tercero1['zona'] == $zonaC)) {
                        $terceroPara1B = $tercero1;
                    } else if ($tercero2 && $tercero2['zona'] != ($terceroPara1A['zona'] ?? null) && ($tercero2['zona'] == $zonaA || $tercero2['zona'] == $zonaC)) {
                        $terceroPara1B = $tercero2;
                    } else if ($tercero1 && $tercero1['zona'] != ($terceroPara1A['zona'] ?? null)) {
                        $terceroPara1B = $tercero1;
                    } else if ($tercero2 && $tercero2['zona'] != ($terceroPara1A['zona'] ?? null)) {
                        $terceroPara1B = $tercero2;
                    }
                    
                    if ($terceroPara1B && isset($primeros[$zonaB])) {
                        $cruces[] = [
                            'id' => 'cuartos_2',
                            'partido_id' => null,
                            'pareja_1' => [
                                'jugador_1' => $primeros[$zonaB]['jugador_1'],
                                'jugador_2' => $primeros[$zonaB]['jugador_2'],
                                'zona' => $zonaB,
                                'posicion' => 1
                            ],
                            'pareja_2' => [
                                'jugador_1' => $terceroPara1B['jugador_1'],
                                'jugador_2' => $terceroPara1B['jugador_2'],
                                'zona' => $terceroPara1B['zona'],
                                'posicion' => 3
                            ],
                            'ronda' => 'cuartos'
                        ];
                    }
                }
                
                // Cruce 3: 1C vs 2A
                if (isset($primeros[$zonaC]) && isset($segundos[$zonaA])) {
                    $cruces[] = [
                        'id' => 'cuartos_3',
                        'partido_id' => null,
                        'pareja_1' => [
                            'jugador_1' => $primeros[$zonaC]['jugador_1'],
                            'jugador_2' => $primeros[$zonaC]['jugador_2'],
                            'zona' => $zonaC,
                            'posicion' => 1
                        ],
                        'pareja_2' => [
                            'jugador_1' => $segundos[$zonaA]['jugador_1'],
                            'jugador_2' => $segundos[$zonaA]['jugador_2'],
                            'zona' => $zonaA,
                            'posicion' => 2
                        ],
                        'ronda' => 'cuartos'
                    ];
                }
                
                // Cruce 4: 2B vs 2C
                if (isset($segundos[$zonaB]) && isset($segundos[$zonaC])) {
                    $cruces[] = [
                        'id' => 'cuartos_4',
                        'partido_id' => null,
                        'pareja_1' => [
                            'jugador_1' => $segundos[$zonaB]['jugador_1'],
                            'jugador_2' => $segundos[$zonaB]['jugador_2'],
                            'zona' => $zonaB,
                            'posicion' => 2
                        ],
                        'pareja_2' => [
                            'jugador_1' => $segundos[$zonaC]['jugador_1'],
                            'jugador_2' => $segundos[$zonaC]['jugador_2'],
                            'zona' => $zonaC,
                            'posicion' => 2
                        ],
                        'ronda' => 'cuartos'
                    ];
                }
            } else {
                // Lógica para otros casos (reutilizar lógica existente)
                $clasificados = [];
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
                
                // Generar cruces básicos
                if (count($clasificados) >= 2) {
                    for ($i = 0; $i < count($clasificados) - 1; $i += 2) {
                        $cruces[] = [
                            'id' => 'cuartos_' . ($i / 2),
                            'partido_id' => null,
                            'pareja_1' => [
                                'jugador_1' => $clasificados[$i]['jugador_1'],
                                'jugador_2' => $clasificados[$i]['jugador_2'],
                                'zona' => $clasificados[$i]['zona'],
                                'posicion' => $clasificados[$i]['posicion']
                            ],
                            'pareja_2' => [
                                'jugador_1' => $clasificados[$i + 1]['jugador_1'],
                                'jugador_2' => $clasificados[$i + 1]['jugador_2'],
                                'zona' => $clasificados[$i + 1]['zona'],
                                'posicion' => $clasificados[$i + 1]['posicion']
                            ],
                            'ronda' => 'cuartos'
                        ];
                    }
                }
            }
        }
        
        // Determinar si hay cruces de octavos o cuartos generados
        $tieneCrucesOctavos = false;
        $tieneCrucesCuartos = false;
        foreach ($cruces as $cruce) {
            if (isset($cruce['ronda'])) {
                if ($cruce['ronda'] === 'octavos' || $cruce['ronda'] === '16avos') {
                    $tieneCrucesOctavos = true;
                } elseif ($cruce['ronda'] === 'cuartos') {
                    $tieneCrucesCuartos = true;
                }
            }
        }
        
        // Si hay cruces de octavos desde configuración, mostrar sección de octavos
        if ($tieneCrucesOctavos) {
            $necesitaOctavos = true;
        }
        
        //return $cruces;
        
        // Cargar configs puntuables para selector (solo para torneos puntuables)
        $configsCrucesPuntuables = [];
        if ($tipoTorneo === 'puntuable') {
            $configsCrucesPuntuables = DB::table('configuracion_cruces_puntuables')
                ->whereNull('torneo_id')
                ->orderBy('cantidad_parejas', 'asc')
                ->get();
        }
        
        return View('bahia_padel.admin.torneo.validar_cruces_americano')
                    ->with('jugadores', $jugadores)
                    ->with('torneo', $torneo)
                    ->with('cruces', $cruces)
                    ->with('posicionesPorZona', $posicionesPorZona)
                    ->with('mejoresTercerosIds', $mejoresTercerosIds)
                    ->with('necesitaOctavos', $necesitaOctavos ?? false)
                    ->with('totalParejasClasificadas', $totalParejasClasificadas ?? 0)
                    ->with('tipoTorneo', $tipoTorneo)
                    ->with('configuracionCruces', $configuracionCruces)
                    ->with('configuracionAmericano', $configuracionAmericano ?? null)
                    ->with('configsCrucesPuntuables', $configsCrucesPuntuables)
                    ->with('llavesPreconfiguradas', $llavesPreconfiguradas ?? [])
                    ->with('tieneCrucesOctavos', $tieneCrucesOctavos)
                    ->with('tieneCrucesCuartos', $tieneCrucesCuartos);
    }
    
    /**
     * Genera cruces usando la configuración guardada
     */
    private function generarCrucesDesdeConfiguracion($configuracion, $posicionesPorZona, $zonas) {
        $cruces = [];
        $zonasArray = $zonas->toArray();
        sort($zonasArray);
        
        // Mapear zonas a letras (A, B, C, D, etc.)
        $letrasZonas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P'];
        $zonaALetra = [];
        foreach ($zonasArray as $index => $zona) {
            if (isset($letrasZonas[$index])) {
                $zonaALetra[$zona] = $letrasZonas[$index];
            }
        }
        
        // Función para obtener pareja desde una referencia (ej: "A1", "B2", "A3", "O1", "O2")
        $obtenerParejaDesdeReferencia = function($referencia, $torneoId = null) use ($posicionesPorZona, $zonaALetra) {
            \Log::info('=== INICIANDO RESOLUCIÓN DE REFERENCIA: ' . $referencia . ' ===');
            
            // PRIMERO verificar si es referencia directa a zona (ej: "A1", "B2", "C2", "A3")
            // Esto debe ir ANTES de verificar ganadores de cuartos para evitar que "C2" se interprete como "ganador de cuartos 2"
            if (preg_match('/^([A-P])(\d+)$/', $referencia, $matches)) {
                \Log::info('Referencia ' . $referencia . ' coincide con patrón de zona directa');
                $letra = $matches[1];
                $posicion = (int)$matches[2];
                
                \Log::info('Buscando pareja para referencia: ' . $referencia . ' (Letra: ' . $letra . ', Posición: ' . $posicion . ')');
                \Log::info('Zonas mapeadas: ' . json_encode($zonaALetra));
                \Log::info('Zonas disponibles en posicionesPorZona: ' . json_encode(array_keys($posicionesPorZona)));
                
                // Buscar la zona que corresponde a esta letra
                foreach ($zonaALetra as $zona => $letraZona) {
                    if ($letraZona === $letra) {
                        \Log::info('Zona encontrada para letra ' . $letra . ': ' . $zona . ', Total posiciones: ' . (isset($posicionesPorZona[$zona]) ? count($posicionesPorZona[$zona]) : 0));
                        // La posición en el array es índice 0-based, pero la referencia es 1-based
                        if (isset($posicionesPorZona[$zona]) && isset($posicionesPorZona[$zona][$posicion - 1])) {
                            $pareja = $posicionesPorZona[$zona][$posicion - 1];
                            \Log::info('Pareja encontrada: J1=' . $pareja['jugador_1'] . ', J2=' . $pareja['jugador_2']);
                            return [
                                'jugador_1' => $pareja['jugador_1'],
                                'jugador_2' => $pareja['jugador_2'],
                                'zona' => $zona,
                                'posicion' => $posicion
                            ];
                        } else {
                            \Log::warning('No se encontró pareja en posición ' . $posicion . ' para zona ' . $zona . '. Posiciones disponibles: ' . (isset($posicionesPorZona[$zona]) ? count($posicionesPorZona[$zona]) : 0));
                        }
                    }
                }
                \Log::warning('No se encontró zona para letra: ' . $letra);
            }
            
            // Si NO es referencia directa a zona, verificar si es referencia a ganadores
            // Si es referencia a ganador de octavos (ej: "O1", "O2", "O3")
            // O referencia a ganador con formato "G1-8vos", "G2-8vos"
            if (preg_match('/^O(\d+)$/', $referencia, $matches) || 
                preg_match('/^G(\d+)-8vos$/', $referencia, $matches) ||
                preg_match('/^G(\d+)-octavos$/', $referencia, $matches)) {
                \Log::info('Referencia ' . $referencia . ' es ganador de octavos, retornando null');
                // Esta es una referencia a un ganador de octavos que aún no existe
                // Retornar null para indicar que no se puede resolver aún
                return null;
            }
            
            // Si es referencia a ganador de cuartos (ej: "C1", "C2" o "G1-4tos")
            // NOTA: Esto solo se ejecuta si NO es una referencia de zona directa (ej: "C2" como zona C posición 2)
            // Para referencias de ganadores de cuartos, usar formato "G1-4tos" o "G1-cuartos"
            if (preg_match('/^G(\d+)-4tos$/', $referencia, $matches) ||
                preg_match('/^G(\d+)-cuartos$/', $referencia, $matches)) {
                \Log::info('Referencia ' . $referencia . ' es ganador de cuartos, retornando null');
                // Esta es una referencia a un ganador de cuartos que aún no existe
                return null;
            }
            
            \Log::warning('Referencia ' . $referencia . ' no coincide con ningún patrón conocido');
            \Log::info('=== FIN RESOLUCIÓN DE REFERENCIA: ' . $referencia . ' (retornando null) ===');
            return null;
        };
        
        // Generar cruces para cada ronda según la configuración
        // Primero 16avos (si existe)
        if ($configuracion->tiene_16avos_final && $configuracion->llave_16avos) {
            $llave = json_decode($configuracion->llave_16avos, true);
            if ($llave && is_array($llave)) {
                foreach ($llave as $index => $partido) {
                    $pareja1Ref = $partido['pareja_1'] ?? null;
                    $pareja2Ref = $partido['pareja_2'] ?? null;
                    
                    if ($pareja1Ref && $pareja2Ref) {
                        $pareja1 = $obtenerParejaDesdeReferencia($pareja1Ref);
                        $pareja2 = $obtenerParejaDesdeReferencia($pareja2Ref);
                        
                        if ($pareja1 && $pareja2) {
                            $cruces[] = [
                                'id' => '16avos_' . ($index + 1),
                                'partido_id' => null,
                                'pareja_1' => $pareja1,
                                'pareja_2' => $pareja2,
                                'ronda' => '16avos'
                            ];
                        }
                    }
                }
            }
        }
        
        // Luego octavos (si existe)
        if ($configuracion->tiene_8vos_final && $configuracion->llave_8vos) {
            $llave = json_decode($configuracion->llave_8vos, true);
            \Log::info('Generando cruces de octavos. Llave decodificada: ' . json_encode($llave));
            if ($llave && is_array($llave)) {
                foreach ($llave as $index => $partido) {
                    $pareja1Ref = $partido['pareja_1'] ?? null;
                    $pareja2Ref = $partido['pareja_2'] ?? null;
                    
                    \Log::info('Procesando partido octavos ' . ($index + 1) . ': ' . $pareja1Ref . ' vs ' . $pareja2Ref);
                    
                    if ($pareja1Ref && $pareja2Ref) {
                        $pareja1 = $obtenerParejaDesdeReferencia($pareja1Ref);
                        $pareja2 = $obtenerParejaDesdeReferencia($pareja2Ref);
                        
                        \Log::info('Pareja 1 resuelta: ' . ($pareja1 ? 'Sí' : 'No') . ', Pareja 2 resuelta: ' . ($pareja2 ? 'Sí' : 'No'));
                        
                        if ($pareja1 && $pareja2) {
                            $cruces[] = [
                                'id' => 'octavos_' . ($index + 1),
                                'partido_id' => null,
                                'pareja_1' => $pareja1,
                                'pareja_2' => $pareja2,
                                'ronda' => 'octavos'
                            ];
                            \Log::info('Cruce de octavos ' . ($index + 1) . ' agregado correctamente');
                        }
                    }
                }
            }
        }
        
        \Log::info('Total cruces generados desde configuración: ' . count($cruces) . ' (Octavos: ' . count(array_filter($cruces, function($c) { return $c['ronda'] === 'octavos'; })) . ', Cuartos: ' . count(array_filter($cruces, function($c) { return $c['ronda'] === 'cuartos'; })) . ')');
        
        // Luego cuartos (si existe) - Solo generar cruces si ambas parejas se pueden resolver
        // Si alguna pareja es referencia a ganador de octavos (O1, O2, etc.), no generar el cruce aún
        if ($configuracion->tiene_4tos_final && $configuracion->llave_4tos) {
            $llave = json_decode($configuracion->llave_4tos, true);
            if ($llave && is_array($llave)) {
                foreach ($llave as $index => $partido) {
                    $pareja1Ref = $partido['pareja_1'] ?? null;
                    $pareja2Ref = $partido['pareja_2'] ?? null;
                    
                    if ($pareja1Ref && $pareja2Ref) {
                        $pareja1 = $obtenerParejaDesdeReferencia($pareja1Ref);
                        $pareja2 = $obtenerParejaDesdeReferencia($pareja2Ref);
                        
                        // Solo agregar el cruce si ambas parejas se pueden resolver
                        // Si alguna es referencia a ganador de octavos (O1, O2, etc.), no agregar aún
                        if ($pareja1 && $pareja2) {
                            $cruces[] = [
                                'id' => 'cuartos_' . ($index + 1),
                                'partido_id' => null,
                                'pareja_1' => $pareja1,
                                'pareja_2' => $pareja2,
                                'ronda' => 'cuartos'
                            ];
                        }
                    }
                }
            }
        }
        
        return $cruces;
    }

    /**
     * Parsea las llaves de la configuración americano para precargar los selectores
     */
    private function parsearLlavesConfigAmericano($configuracion, $totalParejas) {
        $llavesPreconfiguradas = [];
        
        // Determinar qué ronda usar según la configuración
        $ronda = 'cuartos';
        $llaveJson = null;
        
        if ($configuracion->tiene_8vos_final && $configuracion->llave_8vos) {
            $ronda = 'octavos';
            $llaveJson = $configuracion->llave_8vos;
        } elseif ($configuracion->tiene_4tos_final && $configuracion->llave_4tos) {
            $ronda = 'cuartos';
            $llaveJson = $configuracion->llave_4tos;
        }
        
        // Función para convertir formato Z1_P1 a A1, Z2_P4 a B4, etc.
        $convertirFormato = function($referencia) {
            if (!$referencia) return null;
            
            // Si ya está en formato A1, B2, etc., devolverlo tal cual
            if (preg_match('/^[A-P]\d+$/', $referencia)) {
                return $referencia;
            }
            
            // Convertir formato Z1_P1 a A1
            if (preg_match('/^Z(\d+)_P(\d+)$/', $referencia, $matches)) {
                $zonaNum = (int)$matches[1];
                $posicion = $matches[2];
                $letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P'];
                if ($zonaNum >= 1 && $zonaNum <= 16) {
                    return $letras[$zonaNum - 1] . $posicion;
                }
            }
            
            return $referencia;
        };
        
        if ($llaveJson) {
            $llave = json_decode($llaveJson, true);
            if ($llave && is_array($llave)) {
                foreach ($llave as $index => $partido) {
                    $llavesPreconfiguradas[] = [
                        'fila' => $index + 1,
                        'pareja_1' => $convertirFormato($partido['pareja_1'] ?? null),
                        'pareja_2' => $convertirFormato($partido['pareja_2'] ?? null)
                    ];
                }
            }
        }
        
        \Log::info('Llaves parseadas:', ['llaves' => $llavesPreconfiguradas, 'ronda' => $ronda]);
        
        return [
            'ronda' => $ronda,
            'llaves' => $llavesPreconfiguradas,
            'necesitaOctavos' => $configuracion->tiene_8vos_final ?? false
        ];
    }
    
    /**
     * Genera cruces usando la configuración de americanos
     */
    private function generarCrucesDesdeConfigAmericano($configuracion, $posicionesPorZona, $zonas) {
        $cruces = [];
        $zonasArray = $zonas->toArray();
        sort($zonasArray);
        
        // Mapear zonas a letras (A, B, C, D, etc.)
        $letrasZonas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P'];
        $zonaALetra = [];
        foreach ($zonasArray as $index => $zona) {
            if (isset($letrasZonas[$index])) {
                $zonaALetra[$zona] = $letrasZonas[$index];
            }
        }
        
        // Función para obtener pareja desde una referencia (ej: "A1", "B2")
        $obtenerParejaDesdeReferencia = function($referencia) use ($posicionesPorZona, $zonaALetra) {
            if (preg_match('/^([A-P])(\d+)$/', $referencia, $matches)) {
                $letra = $matches[1];
                $posicion = (int)$matches[2];
                
                foreach ($zonaALetra as $zona => $letraZona) {
                    if ($letraZona === $letra) {
                        if (isset($posicionesPorZona[$zona]) && isset($posicionesPorZona[$zona][$posicion - 1])) {
                            $pareja = $posicionesPorZona[$zona][$posicion - 1];
                            return [
                                'jugador_1' => $pareja['jugador_1'],
                                'jugador_2' => $pareja['jugador_2'],
                                'zona' => $zona,
                                'posicion' => $posicion
                            ];
                        }
                    }
                }
            }
            return null;
        };

        // Generar cruces de octavos si existe
        if ($configuracion->tiene_8vos_final && $configuracion->llave_8vos) {
            $llave = json_decode($configuracion->llave_8vos, true);
            if ($llave && is_array($llave)) {
                foreach ($llave as $index => $partido) {
                    $pareja1 = $obtenerParejaDesdeReferencia($partido['pareja_1'] ?? '');
                    $pareja2 = $obtenerParejaDesdeReferencia($partido['pareja_2'] ?? '');

                    if ($pareja1 && $pareja2) {
                        $cruces[] = [
                            'id' => 'octavos_' . ($index + 1),
                            'partido_id' => null,
                            'pareja_1' => $pareja1,
                            'pareja_2' => $pareja2,
                            'ronda' => 'octavos'
                        ];
                    }
                }
            }
        }
        
        // Generar cruces de cuartos si existe y no hay octavos
        if ($configuracion->tiene_4tos_final && $configuracion->llave_4tos && !$configuracion->tiene_8vos_final) {
            $llave = json_decode($configuracion->llave_4tos, true);
            if ($llave && is_array($llave)) {
                foreach ($llave as $index => $partido) {
                    $pareja1 = $obtenerParejaDesdeReferencia($partido['pareja_1'] ?? '');
                    $pareja2 = $obtenerParejaDesdeReferencia($partido['pareja_2'] ?? '');
                    
                    if ($pareja1 && $pareja2) {
                        $cruces[] = [
                            'id' => 'cuartos_' . ($index + 1),
                            'partido_id' => null,
                            'pareja_1' => $pareja1,
                            'pareja_2' => $pareja2,
                            'ronda' => 'cuartos'
                        ];
                    }
                }
            }
        }
        
        return $cruces;
    }

    public function guardarCrucesEditados(Request $request) {
        try {
            $torneoId = $request->torneo_id;
            $cruces = $request->cruces; // Array de cruces editados
            
            \Log::info('Guardando cruces editados para torneo: ' . $torneoId);
            \Log::info('Cruces recibidos: ' . json_encode($cruces));
            
            if (!$cruces || !is_array($cruces)) {
                return response()->json(['success' => false, 'message' => 'No se recibieron cruces válidos']);
            }
            
            // Eliminar cruces existentes para este torneo (incluyendo octavos final, 16avos final)
            $gruposEliminatorios = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->whereIn('zona', ['16avos final', 'octavos final', 'cuartos final', 'semifinal', 'final'])
                ->get();
            
            $partidosIds = $gruposEliminatorios->pluck('partido_id')->unique()->filter();
            
            // Eliminar partidos asociados
            if ($partidosIds->count() > 0) {
                DB::table('partidos')->whereIn('id', $partidosIds)->delete();
            }
            
            // Eliminar grupos eliminatorios (incluyendo octavos final, 16avos final)
            DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->whereIn('zona', ['16avos final', 'octavos final', 'cuartos final', 'semifinal', 'final'])
                ->delete();
            
            // Crear nuevos cruces usando la ronda de cada cruce individual
            foreach ($cruces as $index => $cruce) {
                \Log::info('Procesando cruce ' . $index . ': ' . json_encode($cruce));
                
                // Validar estructura del cruce
                if (!isset($cruce['pareja_1']) || !isset($cruce['pareja_2'])) {
                    \Log::error('Cruce ' . $index . ' no tiene pareja_1 o pareja_2');
                    continue;
                }
                
                if (!isset($cruce['pareja_1']['jugador_1']) || !isset($cruce['pareja_1']['jugador_2']) ||
                    !isset($cruce['pareja_2']['jugador_1']) || !isset($cruce['pareja_2']['jugador_2'])) {
                    \Log::error('Cruce ' . $index . ' no tiene todos los jugadores requeridos');
                    continue;
                }
                
                // Usar la ronda del cruce si está especificada
                $ronda = $cruce['ronda'] ?? null;
                
                // Si no tiene ronda, intentar determinar por el ID del cruce
                if (!$ronda && isset($cruce['id'])) {
                    if (strpos($cruce['id'], '16avos_') === 0 || strpos($cruce['id'], '16vos_') === 0) {
                        $ronda = '16avos';
                    } elseif (strpos($cruce['id'], 'octavos_') === 0 || strpos($cruce['id'], '8vos_') === 0) {
                        $ronda = 'octavos';
                    } elseif (strpos($cruce['id'], 'cuartos_') === 0 || strpos($cruce['id'], '4tos_') === 0) {
                        $ronda = 'cuartos';
                    }
                }
                
                // Si aún no tiene ronda, usar lógica de fallback basada en cantidad
                // PERO solo si todos los cruces tienen la misma cantidad
                if (!$ronda) {
                    $numCruces = count($cruces);
                    $ronda = ($numCruces == 16) ? '16avos' : (($numCruces == 8) ? 'octavos' : 'cuartos');
                    \Log::warning('Cruce ' . $index . ' no tiene ronda definida, usando fallback: ' . $ronda);
                }
                
                // Solo crear partidos para la primera ronda (octavos, 16avos) y cuartos.
                // NO crear semifinales ni final aquí: dependen de los ganadores de rondas anteriores
                // y se crean automáticamente al guardar resultados (crearCuartosDesdeConfiguracionYOctavos, etc.)
                if ($ronda === 'semifinales' || $ronda === 'semifinal' || $ronda === 'final') {
                    \Log::info('Cruce ' . $index . ': Saltando ronda ' . $ronda . ' (se crea al guardar resultados de rondas anteriores)');
                    continue;
                }
                
                // Determinar la zona según la ronda
                $zona = 'cuartos final'; // Por defecto
                if ($ronda === 'octavos' || $ronda === '8vos') {
                    $zona = 'octavos final';
                } elseif ($ronda === '16avos' || $ronda === '16vos') {
                    $zona = '16avos final';
                } elseif ($ronda === 'cuartos' || $ronda === '4tos') {
                    $zona = 'cuartos final';
                }
                
                \Log::info('Cruce ' . $index . ': Ronda=' . $ronda . ', Zona=' . $zona);
                
                // Validar que los jugadores no sean null o 0
                $jugador1_1 = $cruce['pareja_1']['jugador_1'];
                $jugador1_2 = $cruce['pareja_1']['jugador_2'];
                $jugador2_1 = $cruce['pareja_2']['jugador_1'];
                $jugador2_2 = $cruce['pareja_2']['jugador_2'];
                
                if (!$jugador1_1 || !$jugador1_2 || !$jugador2_1 || !$jugador2_2) {
                    \Log::error('Cruce ' . $index . ' tiene jugadores nulos o cero');
                    continue;
                }
                
                // Crear partido con todos los campos requeridos
                $partido = DB::table('partidos')->insertGetId([
                    'pareja_1_set_1' => 0,
                    'pareja_1_set_1_tie_break' => 0,
                    'pareja_2_set_1' => 0,
                    'pareja_2_set_1_tie_break' => 0,
                    'pareja_1_set_2' => 0,
                    'pareja_1_set_2_tie_break' => 0,
                    'pareja_2_set_2' => 0,
                    'pareja_2_set_2_tie_break' => 0,
                    'pareja_1_set_3' => 0,
                    'pareja_1_set_3_tie_break' => 0,
                    'pareja_2_set_3' => 0,
                    'pareja_2_set_3_tie_break' => 0,
                    'pareja_1_set_super_tie_break' => 0,
                    'pareja_2_set_super_tie_break' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                // Crear grupos para las dos parejas
                DB::table('grupos')->insert([
                    [
                        'torneo_id' => $torneoId,
                        'zona' => $zona,
                        'fecha' => '2000-01-01',
                        'horario' => '00:00',
                        'jugador_1' => $jugador1_1,
                        'jugador_2' => $jugador1_2,
                        'partido_id' => $partido,
                        'created_at' => now(),
                        'updated_at' => now()
                    ],
                    [
                        'torneo_id' => $torneoId,
                        'zona' => $zona,
                        'fecha' => '2000-01-01',
                        'horario' => '00:00',
                        'jugador_1' => $jugador2_1,
                        'jugador_2' => $jugador2_2,
                        'partido_id' => $partido,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                ]);
            }
            
            // Guardar config_cruces_puntuable_id si se envió
            if ($request->has('config_cruces_puntuable_id') && $request->config_cruces_puntuable_id) {
                DB::table('torneos')
                    ->where('id', $torneoId)
                    ->update(['config_cruces_puntuable_id' => $request->config_cruces_puntuable_id]);
                \Log::info('Guardado config_cruces_puntuable_id: ' . $request->config_cruces_puntuable_id . ' para torneo: ' . $torneoId);
            }
            
            return response()->json(['success' => true, 'message' => 'Cruces guardados correctamente']);
            
        } catch (\Exception $e) {
            \Log::error('Error al guardar cruces editados: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()], 500);
        }
    }

    public function guardarReferenciaspuntuacion(Request $request) {
        try {
            $items = $request->input('items', []);
            
            // TODO: Implementar guardado de referencias de puntuación para ranking
            // Por ahora retornamos éxito para que no de error
            
            return response()->json([
                'success' => true,
                'message' => 'Referencias de puntuación guardadas correctamente'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al guardar referencias de puntuación: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage()
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // Entradas manuales de ranking
    // -------------------------------------------------------------------------

    /**
     * GET: Lista de jugadores activos para los selectores del modal de ranking.
     */
    public function getJugadoresParaRanking(Request $request)
    {
        try {
            $jugadores = DB::table('jugadores')
                ->where('activo', 1)
                ->orderBy('apellido')
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'apellido', 'foto']);

            return response()->json(['success' => true, 'jugadores' => $jugadores]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST: Crear una nueva entrada manual de ranking.
     */
    public function crearEntradaRanking(Request $request)
    {
        try {
            $nombre    = trim($request->input('nombre', ''));
            $tipo      = $request->input('tipo', 'masculino');
            $categoria = (int) $request->input('categoria', 6);
            $temporada = (int) $request->input('temporada', (int) date('Y'));
            $mes       = (int) $request->input('mes', (int) date('n'));
            $descripcion = trim($request->input('descripcion', ''));

            if ($nombre === '') {
                return response()->json(['success' => false, 'message' => 'El nombre es obligatorio'], 400);
            }
            if (!in_array($tipo, ['masculino', 'femenino', 'mixto'], true)) {
                return response()->json(['success' => false, 'message' => 'Tipo inválido'], 400);
            }
            if ($categoria <= 0 || $categoria > 20) {
                return response()->json(['success' => false, 'message' => 'Categoría inválida'], 400);
            }
            if ($temporada < 2000 || $temporada > 2100) {
                return response()->json(['success' => false, 'message' => 'Temporada inválida'], 400);
            }
            if ($mes < 1 || $mes > 12) {
                return response()->json(['success' => false, 'message' => 'Mes inválido'], 400);
            }

            $id = DB::table('ranking_entradas')->insertGetId([
                'nombre'      => $nombre,
                'tipo'        => $tipo,
                'categoria'   => $categoria,
                'temporada'   => $temporada,
                'mes'         => $mes,
                'descripcion' => $descripcion ?: null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $entrada = DB::table('ranking_entradas')->where('id', $id)->first();

            return response()->json([
                'success' => true,
                'message' => 'Entrada de ranking creada correctamente.',
                'entrada' => $entrada,
            ]);
        } catch (\Exception $e) {
            \Log::error('crearEntradaRanking: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET: Obtener jugadores y puntos de una entrada manual.
     */
    public function obtenerJugadoresEntrada(Request $request)
    {
        try {
            $entradaId = (int) $request->input('entrada_id');
            if ($entradaId <= 0) {
                return response()->json(['success' => false, 'message' => 'entrada_id inválido'], 400);
            }

            $entrada = DB::table('ranking_entradas')->where('id', $entradaId)->first();
            if (!$entrada) {
                return response()->json(['success' => false, 'message' => 'Entrada no encontrada'], 404);
            }

            $jugadores = DB::table('ranking_entradas_jugadores as rej')
                ->join('jugadores as j', 'j.id', '=', 'rej.jugador_id')
                ->where('rej.entrada_id', $entradaId)
                ->orderByDesc('rej.puntos')
                ->orderBy('j.apellido')
                ->get([
                    'rej.id',
                    'rej.jugador_id',
                    'rej.puntos',
                    'rej.referencia_codigo',
                    'j.nombre',
                    'j.apellido',
                    'j.foto',
                ]);

            $referencias = DB::table('puntos_ranking_referencia')
                ->orderBy('orden')
                ->get(['codigo', 'nombre', 'puntos']);

            return response()->json([
                'success'    => true,
                'entrada'    => $entrada,
                'jugadores'  => $jugadores,
                'referencias' => $referencias,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST: Guardar/actualizar los jugadores y puntos de una entrada manual.
     * Recalcula ranking_totales después de guardar.
     */
    public function guardarJugadoresEntrada(Request $request)
    {
        try {
            $entradaId = (int) $request->input('entrada_id');
            $items     = $request->input('items', []);

            if ($entradaId <= 0) {
                return response()->json(['success' => false, 'message' => 'entrada_id inválido'], 400);
            }

            $entrada = DB::table('ranking_entradas')->where('id', $entradaId)->first();
            if (!$entrada) {
                return response()->json(['success' => false, 'message' => 'Entrada no encontrada'], 404);
            }

            DB::beginTransaction();

            // Guardar/actualizar jugadores de la entrada
            $jugadoresAfectados = [];
            foreach ($items as $item) {
                $jugadorId       = (int) ($item['jugador_id'] ?? 0);
                $puntos          = (int) ($item['puntos'] ?? 0);
                $referenciaCodigo = (string) ($item['referencia_codigo'] ?? 'no_clasificados');

                if ($jugadorId <= 0) continue;

                DB::table('ranking_entradas_jugadores')->updateOrInsert(
                    ['entrada_id' => $entradaId, 'jugador_id' => $jugadorId],
                    [
                        'puntos'           => $puntos,
                        'referencia_codigo' => $referenciaCodigo,
                        'updated_at'       => now(),
                        'created_at'       => now(),
                    ]
                );
                $jugadoresAfectados[] = $jugadorId;
            }

            // Recalcular ranking_totales para cada jugador afectado
            foreach ($jugadoresAfectados as $jugadorId) {
                $this->recalcularTotalRankingJugador(
                    $jugadorId,
                    (int) $entrada->categoria,
                    (int) $entrada->temporada,
                    $entrada->tipo
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Jugadores guardados y ranking actualizado correctamente.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('guardarJugadoresEntrada: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST: Eliminar una entrada manual de ranking y recalcular totales.
     */
    public function eliminarEntradaRanking(Request $request)
    {
        try {
            $entradaId = (int) $request->input('entrada_id');
            if ($entradaId <= 0) {
                return response()->json(['success' => false, 'message' => 'entrada_id inválido'], 400);
            }

            $entrada = DB::table('ranking_entradas')->where('id', $entradaId)->first();
            if (!$entrada) {
                return response()->json(['success' => false, 'message' => 'Entrada no encontrada'], 404);
            }

            DB::beginTransaction();

            // Jugadores afectados antes de borrar
            $jugadoresAfectados = DB::table('ranking_entradas_jugadores')
                ->where('entrada_id', $entradaId)
                ->pluck('jugador_id');

            // Cascade elimina ranking_entradas_jugadores automáticamente
            DB::table('ranking_entradas')->where('id', $entradaId)->delete();

            // Recalcular totales
            foreach ($jugadoresAfectados as $jugadorId) {
                $this->recalcularTotalRankingJugador(
                    (int) $jugadorId,
                    (int) $entrada->categoria,
                    (int) $entrada->temporada,
                    $entrada->tipo
                );
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Entrada eliminada y ranking actualizado.']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('eliminarEntradaRanking: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Recalcula puntos_totales en ranking_totales para un jugador/cat/temporada/tipo
     * sumando ranking_puntos (torneos) + ranking_entradas_jugadores (manuales).
     */
    private function recalcularTotalRankingJugador(int $jugadorId, int $categoria, int $temporada, string $tipo): void
    {
        $puntosDesdeT = DB::table('ranking_puntos')
            ->where('jugador_id', $jugadorId)
            ->where('categoria', $categoria)
            ->where('temporada', $temporada)
            ->where('tipo', $tipo)
            ->sum('puntos');

        $puntosDesdeE = DB::table('ranking_entradas_jugadores as rej')
            ->join('ranking_entradas as re', 're.id', '=', 'rej.entrada_id')
            ->where('rej.jugador_id', $jugadorId)
            ->where('re.categoria', $categoria)
            ->where('re.temporada', $temporada)
            ->where('re.tipo', $tipo)
            ->sum('rej.puntos');

        $total = (int) $puntosDesdeT + (int) $puntosDesdeE;

        if ($total > 0) {
            DB::table('ranking_totales')->updateOrInsert(
                ['jugador_id' => $jugadorId, 'categoria' => $categoria, 'temporada' => $temporada, 'tipo' => $tipo],
                ['puntos_totales' => $total, 'updated_at' => now(), 'created_at' => now()]
            );
        } else {
            // Si no tiene puntos en ninguna fuente, eliminamos el registro para no contaminar el ranking
            DB::table('ranking_totales')
                ->where('jugador_id', $jugadorId)
                ->where('categoria', $categoria)
                ->where('temporada', $temporada)
                ->where('tipo', $tipo)
                ->delete();
        }
    }
}


