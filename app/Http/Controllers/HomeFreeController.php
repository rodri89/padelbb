<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\User;
use App\Torneo;
use App\Jugadore;
use Image;
use Storage;
use DateTime;
use Carbon\Carbon;
use App\Calendario;
use App\CalendarioInscripcion;


class HomeFreeController extends Controller
{
    function bahiaPadelHome(){
       return View('bahia_padel.home.index');
    }

    public function torneos(){
        $anioActual = (int) date('Y');
        $anios = range($anioActual - 1, $anioActual + 2);
        $tipos = [
            'todos' => 'Todos',
            'mixto' => 'Mixto',
            'femenino' => 'Femenino',
            'masculino' => 'Masculino',
        ];
        $meses = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        ];
        return view('bahia_padel.home.torneos', [
            'anios' => $anios,
            'anioDefault' => 2026,
            'tipos' => $tipos,
            'meses' => $meses,
        ]);
    }

    /**
     * API: lista de torneos públicos filtrados por tipo, año y meses (para la vista Torneos).
     */
    public function getTorneosPublic(Request $request){
        $anio = (int) ($request->get('anio') ?? date('Y'));
        $tipo = $request->get('tipo', 'todos');
        $mesesParam = $request->get('meses'); // "1,2,3" o array

        $query = Torneo::where('activo', 1)
            ->where('tipo_torneo_formato', 'puntuable')
            ->where(function ($q) use ($anio) {
                $q->whereYear('fecha_inicio', $anio)
                  ->orWhereYear('fecha_fin', $anio);
            });

        if ($tipo !== 'todos') {
            $query->whereRaw('LOWER(tipo) = ?', [strtolower($tipo)]);
        }

        $mesesIds = [];
        if (!empty($mesesParam)) {
            $mesesIds = is_array($mesesParam) ? $mesesParam : array_map('intval', explode(',', $mesesParam));
            $mesesIds = array_filter($mesesIds, function ($m) { return $m >= 1 && $m <= 12; });
        }

        if (!empty($mesesIds)) {
            $placeholders = implode(',', array_map('intval', $mesesIds));
            $query->whereRaw("(MONTH(fecha_inicio) IN ({$placeholders}) OR MONTH(fecha_fin) IN ({$placeholders}))");
        }

        $torneos = $query->orderBy('fecha_inicio', 'desc')
            ->get(['id', 'nombre', 'tipo', 'tipo_torneo_formato', 'fecha_inicio', 'fecha_fin', 'categoria']);

        $torneosConGanador = $torneos->map(function ($t) {
            $item = $t->toArray();
            $item['ganador'] = $this->obtenerGanadorFinalPuntuable($t->id);
            $item['tiene_cruces'] = $this->torneoTieneCruces($t->id);
            return $item;
        });

        return response()->json([
            'success' => true,
            'torneos' => $torneosConGanador,
        ]);
    }

    /**
     * Indica si el torneo tiene cruces (eliminatoria) creados: al menos un grupo en zona de cruces con partido_id.
     */
    private function torneoTieneCruces($torneoId)
    {
        $count = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->whereNotNull('partido_id')
            ->where(function ($q) {
                $q->whereIn('zona', ['16avos final', 'octavos final', 'cuartos final', 'semifinal', 'final'])
                  ->orWhere('zona', 'like', 'octavos final%')
                  ->orWhere('zona', 'like', 'cuartos final%');
            })
            ->limit(1)
            ->count();

        return $count > 0;
    }

    /**
     * Si el torneo puntuable tiene final jugada con resultado, devuelve datos de la pareja ganadora (nombres y fotos).
     * @return array|null ['nombre1', 'nombre2', 'foto1', 'foto2'] o null si no hay ganador
     */
    private function obtenerGanadorFinalPuntuable($torneoId)
    {
        $gruposFinal = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'final')
            ->whereNotNull('partido_id')
            ->orderBy('partido_id')
            ->orderBy('id')
            ->get();

        if ($gruposFinal->count() < 2) {
            return null;
        }

        $partidoId = $gruposFinal->first()->partido_id;
        $partido = DB::table('partidos')->where('id', $partidoId)->first();
        if (!$partido) {
            return null;
        }

        $ganadorPareja = $this->determinarGanadorPartidoDesdeResultados($partido);
        if ($ganadorPareja === null) {
            return null;
        }

        $grupoGanador = $gruposFinal->get($ganadorPareja - 1);
        $id1 = (int) $grupoGanador->jugador_1;
        $id2 = (int) $grupoGanador->jugador_2;
        if ($id1 <= 0 && $id2 <= 0) {
            return null;
        }

        $jugadores = Jugadore::whereIn('id', [$id1, $id2])->get()->keyBy('id');
        $j1 = $jugadores->get($id1);
        $j2 = $jugadores->get($id2);
        $nombre1 = $j1 ? trim($j1->nombre . ' ' . $j1->apellido) : '';
        $nombre2 = $j2 ? trim($j2->nombre . ' ' . $j2->apellido) : '';
        $foto1 = $j1 ? asset($j1->foto) : asset('images/jugador_img.png');
        $foto2 = $j2 ? asset($j2->foto) : asset('images/jugador_img.png');

        return [
            'nombre1' => $nombre1,
            'nombre2' => $nombre2,
            'foto1' => $foto1,
            'foto2' => $foto2,
        ];
    }

    /**
     * Determina qué pareja ganó el partido (1 o 2) según sets. Retorna null si no hay resultado claro.
     */
    private function determinarGanadorPartidoDesdeResultados($partido)
    {
        $p1s1 = (int) ($partido->pareja_1_set_1 ?? 0);
        $p2s1 = (int) ($partido->pareja_2_set_1 ?? 0);
        $p1s2 = (int) ($partido->pareja_1_set_2 ?? 0);
        $p2s2 = (int) ($partido->pareja_2_set_2 ?? 0);
        $p1s3 = (int) ($partido->pareja_1_set_3 ?? 0);
        $p2s3 = (int) ($partido->pareja_2_set_3 ?? 0);
        $p1st = (int) ($partido->pareja_1_set_super_tie_break ?? 0);
        $p2st = (int) ($partido->pareja_2_set_super_tie_break ?? 0);

        $sets1 = 0;
        $sets2 = 0;
        if ($p1s1 > $p2s1) $sets1++; elseif ($p2s1 > $p1s1) $sets2++;
        if ($p1s2 > $p2s2) $sets1++; elseif ($p2s2 > $p1s2) $sets2++;
        if ($p1s3 > 0 || $p2s3 > 0) {
            if ($p1s3 > $p2s3) $sets1++; elseif ($p2s3 > $p1s3) $sets2++;
        }
        if ($sets1 === $sets2 && ($p1st > 0 || $p2st > 0)) {
            return $p1st > $p2st ? 1 : 2;
        }
        if ($sets1 >= 2) return 1;
        if ($sets2 >= 2) return 2;
        return null;
    }

    /**
     * Página de detalle de un torneo: botones Zonas y Cruces (si hay cruces).
     */
    public function torneoDetalle($id)
    {
        $torneo = Torneo::where('id', (int) $id)->where('activo', 1)->first();
        if (!$torneo) {
            return redirect()->route('home.torneos')->with('error', 'Torneo no encontrado');
        }
        $tieneCruces = $this->torneoTieneCruces($torneo->id);
        return view('bahia_padel.home.torneo_detalle', [
            'torneo' => $torneo,
            'tiene_cruces' => $tieneCruces,
        ]);
    }

    /**
     * API pública: zonas de grupos y clasificación para un torneo.
     * Devuelve partidos de cada zona y el orden (1º, 2º, 3º) según partidos ganados y diferencia de games.
     */
    public function torneoZonasPublic(Request $request, $id)
    {
        $torneoId = (int) $id;
        $torneo = DB::table('torneos')->where('id', $torneoId)->where('activo', 1)->first();
        if (!$torneo) {
            return response()->json(['success' => false, 'message' => 'Torneo no encontrado'], 404);
        }

        // Grupos de fase de zonas (excluir eliminatoria y grupos especiales)
        $grupos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where(function ($query) {
                $query->whereNotIn('zona', ['cuartos final', 'semifinal', 'final', 'octavos final', '16avos final'])
                      ->where('zona', 'not like', 'cuartos final|%')
                      ->where('zona', 'not like', 'ganador %')
                      ->where('zona', 'not like', 'perdedor %');
            })
            ->orderBy('zona')
            ->orderBy('id')
            ->get();

        if ($grupos->isEmpty()) {
            return response()->json(['success' => true, 'zonas' => []]);
        }

        // Info de jugadores para armar nombres
        $jugadoresIds = [];
        foreach ($grupos as $g) {
            if (!empty($g->jugador_1) && $g->jugador_1 > 0) {
                $jugadoresIds[] = (int) $g->jugador_1;
            }
            if (!empty($g->jugador_2) && $g->jugador_2 > 0) {
                $jugadoresIds[] = (int) $g->jugador_2;
            }
        }
        $jugadoresIds = array_values(array_unique($jugadoresIds));
        $jugadoresInfo = [];
        if (!empty($jugadoresIds)) {
            $jugadores = DB::table('jugadores')
                ->whereIn('id', $jugadoresIds)
                ->get(['id', 'nombre', 'apellido', 'foto']);
            foreach ($jugadores as $j) {
                $ruta = $j->foto ?? 'images/jugador_img.png';
                if (!str_starts_with($ruta, 'http')) {
                    $ruta = ltrim($ruta, '/');
                    $ruta = asset($ruta);
                }
                $jugadoresInfo[(int) $j->id] = [
                    'id' => (int) $j->id,
                    'nombre' => $j->nombre ?? '',
                    'apellido' => $j->apellido ?? '',
                    'foto' => $ruta,
                ];
            }
        }

        $zonas = $grupos->pluck('zona')->unique()->sort()->values();
        $resultadoZonas = [];

        foreach ($zonas as $zona) {
            $gruposZona = $grupos->where('zona', $zona)->filter(function ($grupo) {
                return $grupo->jugador_1 !== null && $grupo->jugador_2 !== null;
            });

            // Partidos por zona
            $partidosIds = $gruposZona->pluck('partido_id')->unique()->filter()->values();
            $partidos = $partidosIds->isEmpty()
                ? collect()
                : DB::table('partidos')->whereIn('id', $partidosIds)->get()->keyBy('id');

            $gruposPorPartido = [];
            foreach ($gruposZona as $g) {
                if ($g->partido_id) {
                    $pid = (int) $g->partido_id;
                    if (!isset($gruposPorPartido[$pid])) {
                        $gruposPorPartido[$pid] = [];
                    }
                    $gruposPorPartido[$pid][] = $g;
                }
            }

            $partidosOut = [];
            $partidoNro = 1;
            foreach ($partidosIds as $pid) {
                $pid = (int) $pid;
                if (!isset($gruposPorPartido[$pid]) || count($gruposPorPartido[$pid]) < 2) {
                    continue;
                }
                $gList = collect($gruposPorPartido[$pid])->sortBy('id')->values()->all();
                $g1 = $gList[0];
                $g2 = $gList[1];
                $p = $partidos->get($pid);
                if (!$p) continue;

                $j1a = $jugadoresInfo[(int) $g1->jugador_1] ?? null;
                $j1b = $jugadoresInfo[(int) $g1->jugador_2] ?? null;
                $j2a = $jugadoresInfo[(int) $g2->jugador_1] ?? null;
                $j2b = $jugadoresInfo[(int) $g2->jugador_2] ?? null;

                $diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                $fecha = $g1->fecha ?? $g2->fecha ?? null;
                $horario = $g1->horario ?? $g2->horario ?? null;
                $diaDisplay = null;
                $horarioDisplay = null;
                $fechaStr = $fecha ? (is_object($fecha) ? $fecha->format('Y-m-d') : trim((string) $fecha)) : '';
                $horarioStr = $horario !== null && $horario !== '' ? (is_object($horario) ? $horario->format('H:i') : preg_replace('/^(\d{2}:\d{2})(:\d{2})?$/', '$1', trim((string) $horario))) : '';
                $fechaEsDefault = $fechaStr !== '' && strpos($fechaStr, '2000-01-01') !== false;
                $horarioEsDefault = $horarioStr === '' || $horarioStr === '00:00';
                if ($fechaStr !== '' && !$fechaEsDefault) {
                    $diaDisplay = in_array(strtolower($fechaStr), ['viernes', 'sabado', 'domingo']) ? ucfirst($fechaStr) : (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaStr) ? $diasSemana[(int) date('w', strtotime($fechaStr))] : $fechaStr);
                }
                if ($horarioStr !== '' && !$horarioEsDefault) {
                    $horarioDisplay = $horarioStr;
                }

                $partidosOut[] = [
                    'partido_numero' => $partidoNro++,
                    'pareja_1' => [
                        'label' => trim(($j1a['nombre'] ?? '') . ' ' . ($j1a['apellido'] ?? '') . ' / ' . ($j1b['nombre'] ?? '') . ' ' . ($j1b['apellido'] ?? '')),
                        'jugador_1' => $j1a,
                        'jugador_2' => $j1b,
                    ],
                    'pareja_2' => [
                        'label' => trim(($j2a['nombre'] ?? '') . ' ' . ($j2a['apellido'] ?? '') . ' / ' . ($j2b['nombre'] ?? '') . ' ' . ($j2b['apellido'] ?? '')),
                        'jugador_1' => $j2a,
                        'jugador_2' => $j2b,
                    ],
                    'dia' => $diaDisplay,
                    'horario' => $horarioDisplay,
                    'resultado' => [
                        'p1_set1' => (int) ($p->pareja_1_set_1 ?? 0),
                        'p2_set1' => (int) ($p->pareja_2_set_1 ?? 0),
                        'p1_set2' => (int) ($p->pareja_1_set_2 ?? 0),
                        'p2_set2' => (int) ($p->pareja_2_set_2 ?? 0),
                        'p1_set3' => (int) ($p->pareja_1_set_3 ?? 0),
                        'p2_set3' => (int) ($p->pareja_2_set_3 ?? 0),
                        'p1_super_tb' => (int) ($p->pareja_1_set_super_tie_break ?? 0),
                        'p2_super_tb' => (int) ($p->pareja_2_set_super_tie_break ?? 0),
                    ],
                ];
            }

            // Clasificación por zona: mismo criterio que admin (set 3, super TB, zona 3 con puntos 2/1/0)
            $keyNorm = function ($j1, $j2) {
                return min((int) $j1, (int) $j2) . '_' . max((int) $j1, (int) $j2);
            };
            $parejas = [];
            foreach ($gruposZona as $g) {
                $key = $keyNorm($g->jugador_1, $g->jugador_2);
                if (!isset($parejas[$key])) {
                    $parejas[$key] = [
                        'jugador_1' => (int) $g->jugador_1,
                        'jugador_2' => (int) $g->jugador_2,
                        'partidos_ganados' => 0,
                        'partidos_perdidos' => 0,
                        'puntos' => 0,
                        'sets_ganados' => 0,
                        'sets_perdidos' => 0,
                        'puntos_ganados' => 0,
                        'puntos_perdidos' => 0,
                    ];
                }
            }

            foreach ($partidos as $pid => $p) {
                if (!isset($gruposPorPartido[$pid]) || count($gruposPorPartido[$pid]) < 2) {
                    continue;
                }
                $gList = collect($gruposPorPartido[$pid])->sortBy('id')->values()->all();
                $g1 = $gList[0];
                $g2 = $gList[1];

                $key1 = $keyNorm($g1->jugador_1, $g1->jugador_2);
                $key2 = $keyNorm($g2->jugador_1, $g2->jugador_2);
                if (!isset($parejas[$key1]) || !isset($parejas[$key2])) {
                    continue;
                }

                // Contar sets ganados (incluyendo set 3 y super tie-break)
                $sets1 = 0;
                $sets2 = 0;
                if (($p->pareja_1_set_1 ?? 0) > ($p->pareja_2_set_1 ?? 0)) $sets1++;
                elseif (($p->pareja_2_set_1 ?? 0) > ($p->pareja_1_set_1 ?? 0)) $sets2++;
                if (($p->pareja_1_set_2 ?? 0) > ($p->pareja_2_set_2 ?? 0)) $sets1++;
                elseif (($p->pareja_2_set_2 ?? 0) > ($p->pareja_1_set_2 ?? 0)) $sets2++;
                if (($p->pareja_1_set_super_tie_break ?? 0) > 0 || ($p->pareja_2_set_super_tie_break ?? 0) > 0) {
                    if (($p->pareja_1_set_super_tie_break ?? 0) > ($p->pareja_2_set_super_tie_break ?? 0)) {
                        $sets1 = 2; $sets2 = 1;
                    } elseif (($p->pareja_2_set_super_tie_break ?? 0) > ($p->pareja_1_set_super_tie_break ?? 0)) {
                        $sets1 = 1; $sets2 = 2;
                    }
                } elseif (($p->pareja_1_set_3 ?? 0) > 0 || ($p->pareja_2_set_3 ?? 0) > 0) {
                    if (($p->pareja_1_set_3 ?? 0) > ($p->pareja_2_set_3 ?? 0)) $sets1++;
                    elseif (($p->pareja_2_set_3 ?? 0) > ($p->pareja_1_set_3 ?? 0)) $sets2++;
                }

                // Juegos (games): set1 + set2 + set3 (super TB no suma juegos)
                $juegos1 = ($p->pareja_1_set_1 ?? 0) + ($p->pareja_1_set_2 ?? 0) + ($p->pareja_1_set_3 ?? 0);
                $juegos2 = ($p->pareja_2_set_1 ?? 0) + ($p->pareja_2_set_2 ?? 0) + ($p->pareja_2_set_3 ?? 0);

                if ($juegos1 > 0 || $juegos2 > 0) {
                    $parejas[$key1]['sets_ganados'] += $sets1;
                    $parejas[$key1]['sets_perdidos'] += $sets2;
                    $parejas[$key2]['sets_ganados'] += $sets2;
                    $parejas[$key2]['sets_perdidos'] += $sets1;
                    $parejas[$key1]['puntos_ganados'] += $juegos1;
                    $parejas[$key1]['puntos_perdidos'] += $juegos2;
                    $parejas[$key2]['puntos_ganados'] += $juegos2;
                    $parejas[$key2]['puntos_perdidos'] += $juegos1;

                    if ($sets1 > $sets2) {
                        $parejas[$key1]['partidos_ganados']++;
                        $parejas[$key2]['partidos_perdidos']++;
                        if (count($parejas) === 3) {
                            $parejas[$key1]['puntos'] += 2;
                            $parejas[$key2]['puntos'] += 1;
                        }
                    } elseif ($sets2 > $sets1) {
                        $parejas[$key2]['partidos_ganados']++;
                        $parejas[$key1]['partidos_perdidos']++;
                        if (count($parejas) === 3) {
                            $parejas[$key2]['puntos'] += 2;
                            $parejas[$key1]['puntos'] += 1;
                        }
                    }
                }
            }

            foreach ($parejas as $key => $val) {
                $parejas[$key]['diferencia_games'] = ($val['puntos_ganados'] ?? 0) - ($val['puntos_perdidos'] ?? 0);
                $parejas[$key]['diferencia_sets'] = ($val['sets_ganados'] ?? 0) - ($val['sets_perdidos'] ?? 0);
                if (count($parejas) === 3 && !isset($parejas[$key]['puntos'])) {
                    $parejas[$key]['puntos'] = 0;
                }
            }

            $posiciones = array_values($parejas);
            $numParejas = count($posiciones);

            if ($numParejas === 3) {
                usort($posiciones, function ($a, $b) {
                    if (($a['puntos'] ?? 0) !== ($b['puntos'] ?? 0)) {
                        return ($b['puntos'] ?? 0) <=> ($a['puntos'] ?? 0);
                    }
                    if (($a['diferencia_sets'] ?? 0) !== ($b['diferencia_sets'] ?? 0)) {
                        return ($b['diferencia_sets'] ?? 0) <=> ($a['diferencia_sets'] ?? 0);
                    }
                    return ($b['diferencia_games'] ?? 0) <=> ($a['diferencia_games'] ?? 0);
                });
            } else {
                usort($posiciones, function ($a, $b) {
                    if ($a['partidos_ganados'] !== $b['partidos_ganados']) {
                        return $b['partidos_ganados'] <=> $a['partidos_ganados'];
                    }
                    if (($a['diferencia_sets'] ?? 0) !== ($b['diferencia_sets'] ?? 0)) {
                        return ($b['diferencia_sets'] ?? 0) <=> ($a['diferencia_sets'] ?? 0);
                    }
                    return ($b['diferencia_games'] ?? 0) <=> ($a['diferencia_games'] ?? 0);
                });
            }

            $clasificacionOut = [];
            $posNum = 1;
            foreach ($posiciones as $fila) {
                if ($posNum > 3) break; // Solo mostrar Top 3
                $j1 = $jugadoresInfo[$fila['jugador_1']] ?? null;
                $j2 = $jugadoresInfo[$fila['jugador_2']] ?? null;
                $clasificacionOut[] = [
                    'posicion' => $posNum,
                    'label' => trim(($j1['nombre'] ?? '') . ' ' . ($j1['apellido'] ?? '') . ' / ' . ($j2['nombre'] ?? '') . ' ' . ($j2['apellido'] ?? '')),
                    'jugador_1' => $j1,
                    'jugador_2' => $j2,
                    'partidos_ganados' => $fila['partidos_ganados'],
                    'partidos_perdidos' => $fila['partidos_perdidos'] ?? 0,
                    'puntos' => $fila['puntos'] ?? null,
                    'sets_ganados' => $fila['sets_ganados'] ?? 0,
                    'sets_perdidos' => $fila['sets_perdidos'] ?? 0,
                    'puntos_ganados' => $fila['puntos_ganados'],
                    'puntos_perdidos' => $fila['puntos_perdidos'],
                ];
                $posNum++;
            }

            $resultadoZonas[] = [
                'zona' => (string) $zona,
                'partidos' => $partidosOut,
                'clasificacion' => $clasificacionOut,
            ];
        }

        return response()->json([
            'success' => true,
            'zonas' => $resultadoZonas,
        ]);
    }

    /**
     * API pública: cruces eliminatorios (octavos, cuartos, semifinal, final) para un torneo.
     */
    public function torneoCrucesPublic(Request $request, $id)
    {
        $torneoId = (int) $id;
        $torneo = DB::table('torneos')->where('id', $torneoId)->where('activo', 1)->first();
        if (!$torneo) {
            return response()->json(['success' => false, 'message' => 'Torneo no encontrado'], 404);
        }

        $grupos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->whereNotNull('partido_id')
            ->where(function ($query) {
                $query->whereIn('zona', ['16avos final', 'octavos final', 'cuartos final', 'semifinal', 'final'])
                      ->orWhere('zona', 'like', 'octavos final%')
                      ->orWhere('zona', 'like', 'cuartos final%');
            })
            ->orderBy('zona')
            ->orderBy('partido_id')
            ->orderBy('id')
            ->get();

        if ($grupos->isEmpty()) {
            return response()->json(['success' => true, 'rondas' => []]);
        }

        $jugadoresIds = [];
        foreach ($grupos as $g) {
            if (!empty($g->jugador_1) && $g->jugador_1 > 0) $jugadoresIds[] = (int) $g->jugador_1;
            if (!empty($g->jugador_2) && $g->jugador_2 > 0) $jugadoresIds[] = (int) $g->jugador_2;
        }
        $jugadoresIds = array_values(array_unique($jugadoresIds));
        $jugadoresInfo = [];
        if (!empty($jugadoresIds)) {
            $jugadores = DB::table('jugadores')
                ->whereIn('id', $jugadoresIds)
                ->get(['id', 'nombre', 'apellido', 'foto']);
            foreach ($jugadores as $j) {
                $ruta = $j->foto ?? 'images/jugador_img.png';
                if (!str_starts_with($ruta, 'http')) {
                    $ruta = ltrim($ruta, '/');
                    $ruta = asset($ruta);
                }
                $jugadoresInfo[(int) $j->id] = [
                    'id' => (int) $j->id,
                    'nombre' => $j->nombre ?? '',
                    'apellido' => $j->apellido ?? '',
                    'foto' => $ruta,
                ];
            }
        }

        $partidosIds = $grupos->pluck('partido_id')->unique()->filter()->values();
        $partidos = $partidosIds->isEmpty()
            ? collect()
            : DB::table('partidos')->whereIn('id', $partidosIds)->get()->keyBy('id');

        $gruposPorPartido = [];
        $zonaPorPartido = [];
        foreach ($grupos as $g) {
            $pid = (int) $g->partido_id;
            if (!isset($gruposPorPartido[$pid])) {
                $gruposPorPartido[$pid] = [];
                $zonaPorPartido[$pid] = $g->zona;
            }
            $gruposPorPartido[$pid][] = $g;
        }

        $rondasMap = [
            '16avos' => ['label' => '16AVOS', 'partidos' => []],
            'octavos' => ['label' => 'OCTAVOS', 'partidos' => []],
            'cuartos' => ['label' => 'CUARTOS', 'partidos' => []],
            'semifinal' => ['label' => 'SEMIFINAL', 'partidos' => []],
            'final' => ['label' => 'FINAL', 'partidos' => []],
        ];
        $contadorPorRonda = ['16avos' => 1, 'octavos' => 1, 'cuartos' => 1, 'semifinal' => 1, 'final' => 1];

        $diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

        foreach ($partidosIds as $pid) {
            $pid = (int) $pid;
            if (!isset($gruposPorPartido[$pid]) || count($gruposPorPartido[$pid]) < 2) continue;
            $p = $partidos->get($pid);
            if (!$p) continue;
            $zona = strtolower($zonaPorPartido[$pid] ?? '');
            $ronda = null;
            if (strpos($zona, '16avos') !== false) $ronda = '16avos';
            elseif (strpos($zona, 'octavos') !== false) $ronda = 'octavos';
            elseif (strpos($zona, 'cuartos') !== false) $ronda = 'cuartos';
            elseif (strpos($zona, 'semifinal') !== false) $ronda = 'semifinal';
            elseif (trim($zona) === 'final') $ronda = 'final';
            if (!$ronda || !isset($rondasMap[$ronda])) continue;

            $gList = collect($gruposPorPartido[$pid])->sortBy('id')->values()->all();
            $g1 = $gList[0];
            $g2 = $gList[1];
            $j1a = $jugadoresInfo[(int) $g1->jugador_1] ?? null;
            $j1b = $jugadoresInfo[(int) $g1->jugador_2] ?? null;
            $j2a = $jugadoresInfo[(int) $g2->jugador_1] ?? null;
            $j2b = $jugadoresInfo[(int) $g2->jugador_2] ?? null;

            $label1 = trim(($j1a['nombre'] ?? '') . ' ' . ($j1a['apellido'] ?? '') . ' / ' . ($j1b['nombre'] ?? '') . ' ' . ($j1b['apellido'] ?? ''));
            $label2 = trim(($j2a['nombre'] ?? '') . ' ' . ($j2a['apellido'] ?? '') . ' / ' . ($j2b['nombre'] ?? '') . ' ' . ($j2b['apellido'] ?? ''));
            $ref1 = trim($g1->referencia_config ?? '');
            $ref2 = trim($g2->referencia_config ?? '');
            $labelVacio = function ($s) { return $s === '' || preg_match('/^[\s\/]+$/', $s) || strlen(trim(str_replace('/', '', $s))) === 0; };
            if ($labelVacio($label1) && $ref1 !== '') $label1 = $ref1;
            if ($labelVacio($label2) && $ref2 !== '') $label2 = $ref2;

            $fecha = $g1->fecha ?? $g2->fecha ?? null;
            $horario = $g1->horario ?? $g2->horario ?? null;
            $diaDisplay = null;
            $horarioDisplay = null;
            $fechaStr = $fecha ? (is_object($fecha) ? $fecha->format('Y-m-d') : trim((string) $fecha)) : '';
            $horarioStr = $horario !== null && $horario !== '' ? (is_object($horario) ? $horario->format('H:i') : preg_replace('/^(\d{2}:\d{2})(:\d{2})?$/', '$1', trim((string) $horario))) : '';
            $fechaEsDefault = $fechaStr !== '' && strpos($fechaStr, '2000-01-01') !== false;
            $horarioEsDefault = $horarioStr === '' || $horarioStr === '00:00';
            if ($fechaStr !== '' && !$fechaEsDefault) {
                $diaDisplay = in_array(strtolower($fechaStr), ['viernes', 'sabado', 'domingo']) ? ucfirst($fechaStr) : (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaStr) ? $diasSemana[(int) date('w', strtotime($fechaStr))] : $fechaStr);
            }
            if ($horarioStr !== '' && !$horarioEsDefault) {
                $horarioDisplay = $horarioStr;
            }

            $rondasMap[$ronda]['partidos'][] = [
                'partido_numero' => $contadorPorRonda[$ronda]++,
                'pareja_1' => [
                    'label' => $label1 ?: 'Pareja 1',
                    'referencia' => $ref1,
                    'jugador_1' => $j1a,
                    'jugador_2' => $j1b,
                ],
                'pareja_2' => [
                    'label' => $label2 ?: 'Pareja 2',
                    'referencia' => $ref2,
                    'jugador_1' => $j2a,
                    'jugador_2' => $j2b,
                ],
                'dia' => $diaDisplay,
                'horario' => $horarioDisplay,
                'resultado' => [
                    'p1_set1' => (int) ($p->pareja_1_set_1 ?? 0),
                    'p2_set1' => (int) ($p->pareja_2_set_1 ?? 0),
                    'p1_set2' => (int) ($p->pareja_1_set_2 ?? 0),
                    'p2_set2' => (int) ($p->pareja_2_set_2 ?? 0),
                    'p1_set3' => (int) ($p->pareja_1_set_3 ?? 0),
                    'p2_set3' => (int) ($p->pareja_2_set_3 ?? 0),
                ],
            ];
        }

        $rondasOut = [];
        foreach (['16avos', 'octavos', 'cuartos', 'semifinal', 'final'] as $key) {
            if (!empty($rondasMap[$key]['partidos'])) {
                $rondasOut[] = [
                    'ronda' => $key,
                    'label' => $rondasMap[$key]['label'],
                    'partidos' => $rondasMap[$key]['partidos'],
                ];
            }
        }

        return response()->json([
            'success' => true,
            'rondas' => $rondasOut,
        ]);
    }

    /**
     * Ranking público: filtros por tipo (masculino/femenino/mixto), categoría y temporada/año.
     */
    public function ranking(Request $request)
    {
        $tipos = [
            'masculino' => 'Masculino',
            'femenino' => 'Femenino',
            'mixto' => 'Mixto',
        ];
        $tipoSeleccionado = $request->get('tipo');
        if (!array_key_exists($tipoSeleccionado ?? '', $tipos)) {
            $tipoSeleccionado = 'masculino';
        }
        $categorias = DB::table('ranking_totales')
            ->where('tipo', $tipoSeleccionado)
            ->select('categoria')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria');
        $temporadas = DB::table('ranking_totales')
            ->where('tipo', $tipoSeleccionado)
            ->select('temporada')
            ->distinct()
            ->orderByDesc('temporada')
            ->pluck('temporada');
        $categoriaSeleccionada = (int) ($request->get('categoria') ?? $categorias->first() ?? 6);
        $temporadaSeleccionada = (int) ($request->get('temporada') ?? $temporadas->first() ?? date('Y'));
        $ranking = collect();
        if (!$categorias->isEmpty() && !$temporadas->isEmpty()) {
            $ranking = DB::table('ranking_totales')
                ->join('jugadores', 'jugadores.id', '=', 'ranking_totales.jugador_id')
                ->where('ranking_totales.tipo', $tipoSeleccionado)
                ->where('ranking_totales.categoria', $categoriaSeleccionada)
                ->where('ranking_totales.temporada', $temporadaSeleccionada)
                ->where('jugadores.activo', 1)
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
        }
        return view('bahia_padel.home.ranking', [
            'tipos' => $tipos,
            'tipo_seleccionado' => $tipoSeleccionado,
            'categorias' => $categorias,
            'categoria_seleccionada' => $categoriaSeleccionada,
            'temporadas' => $temporadas,
            'temporada_seleccionada' => $temporadaSeleccionada,
            'ranking' => $ranking,
        ]);
    }

    public function calendario(Request $request)
    {
        $anio = (int) $request->query('anio', now()->year);
        if ($anio < 2000 || $anio > 2100) {
            $anio = (int) now()->year;
        }

        $mesActual = (int) now()->format('n');
        $mesDefault = $mesActual >= 3 && $mesActual <= 12 ? $mesActual : 3;

        $mes = (int) $request->query('mes', $mesDefault);
        if ($mes < 3 || $mes > 12) {
            $mes = $mesDefault;
        }

        $inicioMes = Carbon::create($anio, $mes, 1)->startOfMonth();
        $finMes = Carbon::create($anio, $mes, 1)->endOfMonth();

        $todos = Calendario::orderByRaw('COALESCE(fecha_desde, fecha)')
            ->orderBy('id')
            ->get();

        $eventos = $todos->filter(function (Calendario $e) use ($inicioMes, $finMes) {
            $desde = $e->fecha_desde ?? $e->fecha;
            $hasta = $e->fecha_hasta ?? $e->fecha_desde ?? $e->fecha;
            if (!$desde || !$hasta) {
                return false;
            }
            $d0 = $desde instanceof Carbon ? $desde->copy()->startOfDay() : Carbon::parse($desde)->startOfDay();
            $d1 = $hasta instanceof Carbon ? $hasta->copy()->startOfDay() : Carbon::parse($hasta)->startOfDay();
            if ($d1->lt($d0)) {
                $d1 = $d0->copy();
            }

            return $d1->gte($inicioMes) && $d0->lte($finMes);
        })->values();

        $nombresMes = [
            3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return view('bahia_padel.home.calendario', [
            'eventos' => $eventos,
            'mesSeleccionado' => $mes,
            'anioCalendario' => $anio,
            'nombresMes' => $nombresMes,
            'tieneEventosTotales' => $todos->isNotEmpty(),
        ]);
    }

    public function calendarioInscribir(Calendario $calendario)
    {
        if (!$calendario->inscripcionAbiertaHoy()) {
            return redirect()
                ->route('home.calendario')
                ->with('error', 'La inscripción no está abierta para este evento.');
        }

        return view('bahia_padel.home.calendario_inscribir', [
            'evento' => $calendario,
        ]);
    }

    public function calendarioInscribirGuardar(Request $request, Calendario $calendario)
    {
        if (!$calendario->inscripcionAbiertaHoy()) {
            return redirect()
                ->route('home.calendario')
                ->with('error', 'La inscripción no está abierta para este evento.');
        }

        $validated = $request->validate([
            'jugador1_id' => 'nullable|integer|min:1',
            'jugador2_id' => 'nullable|integer|min:1',
            'jugador1_nombre' => 'required|string|max:120',
            'jugador1_apellido' => 'required|string|max:120',
            'jugador1_telefono' => 'required|string|max:40',
            'jugador2_nombre' => 'required|string|max:120',
            'jugador2_apellido' => 'required|string|max:120',
            'jugador2_telefono' => 'nullable|string|max:40',
            // Puede venir vacío = sin restricciones horarias
            'disponibilidad_horaria' => 'nullable|string|max:5000',
        ], [
            'jugador1_nombre.required' => 'Completá el nombre del jugador 1.',
            'jugador1_apellido.required' => 'Completá el apellido del jugador 1.',
            'jugador1_telefono.required' => 'Completá el teléfono del jugador 1.',
            'jugador2_nombre.required' => 'Completá el nombre del jugador 2.',
            'jugador2_apellido.required' => 'Completá el apellido del jugador 2.',
        ]);

        $j2tel = $validated['jugador2_telefono'] ?? null;
        if (is_string($j2tel) && trim($j2tel) === '') {
            $j2tel = null;
        }

        CalendarioInscripcion::create([
            'calendario_id' => $calendario->id,
            'jugador1_id' => $validated['jugador1_id'] ?? null,
            'jugador2_id' => $validated['jugador2_id'] ?? null,
            'jugador1_nombre' => $validated['jugador1_nombre'],
            'jugador1_apellido' => $validated['jugador1_apellido'],
            'jugador1_telefono' => $validated['jugador1_telefono'],
            'jugador2_nombre' => $validated['jugador2_nombre'],
            'jugador2_apellido' => $validated['jugador2_apellido'],
            'jugador2_telefono' => $j2tel,
            'disponibilidad_horaria' => $validated['disponibilidad_horaria'] ?? '',
        ]);

        return redirect()
            ->route('home.calendario')
            ->with('success', 'Tu inscripción fue registrada. Nos pondremos en contacto.');
    }

    public function calendarioBuscarJugadores(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $query = \DB::table('jugadores')->where('activo', 1);
        if ($q !== '' && mb_strlen($q) >= 1) {
            $query->where(function ($w) use ($q) {
                $w->where('nombre', 'LIKE', "%{$q}%")
                    ->orWhere('apellido', 'LIKE', "%{$q}%")
                    ->orWhere(\DB::raw("CONCAT(nombre, ' ', apellido)"), 'LIKE', "%{$q}%");
            });
        }

        $jugadores = $query
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->limit(15)
            ->get(['id', 'nombre', 'apellido', 'telefono']);

        return response()->json(['jugadores' => $jugadores]);
    }

    public function calendarioCrearJugador(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:120',
            'apellido' => 'required|string|max:120',
            'telefono' => 'nullable|string|max:40',
        ], [
            'nombre.required' => 'Completá el nombre.',
            'apellido.required' => 'Completá el apellido.',
        ]);

        $tel = $validated['telefono'] ?? '';
        if (is_string($tel)) {
            $tel = trim($tel);
        }

        $jugador = new Jugadore();
        $jugador->activo = 1;
        $jugador->nombre = $validated['nombre'];
        $jugador->apellido = $validated['apellido'];
        $jugador->telefono = $tel === '' ? '0' : $tel;
        $jugador->posicion = 0;
        $jugador->foto = 'images/jugador_img.png';
        $jugador->save();

        return response()->json([
            'success' => true,
            'jugador' => [
                'id' => $jugador->id,
                'nombre' => $jugador->nombre,
                'apellido' => $jugador->apellido,
                'telefono' => $jugador->telefono,
            ],
        ]);
    }

    public function reglamento(){
        return view('bahia_padel.home.reglamento');
    }
    
    function bahiaPadelAdmin() {
        return View('bahia_padel.admin.index'); 
    }
    
}
