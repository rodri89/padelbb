<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Partido;
use App\Grupo;
use App\Services\TorneoGrupoPosicionesService;

class PuntuableController extends Controller
{
    /**
     * Lee las posiciones de cada zona desde la tabla grupos (zona + posicion_grupo).
     * Devuelve: [ 'A' => [ ['jugador_1'=>..,'jugador_2'=>..], ... ] , ... ]
     *
     * Importante:
     * - Solo usa la zona base (no 'ganador X' / 'perdedor X', ni rondas eliminatorias).
     * - Deduplica por pareja dentro de la zona.
     */
    private function obtenerPosicionesPorZonaDesdeGrupos($torneoId)
    {
        $rows = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->whereNotNull('posicion_grupo')
            ->where(function ($q) {
                $q->whereNotIn('zona', ['cuartos final', 'semifinal', 'final', 'octavos final', '16avos final'])
                  ->where('zona', 'not like', 'cuartos final|%')
                  ->where('zona', 'not like', 'ganador %')
                  ->where('zona', 'not like', 'perdedor %');
            })
            ->select([
                'zona',
                'posicion_grupo',
                DB::raw('LEAST(jugador_1, jugador_2) as jmin'),
                DB::raw('GREATEST(jugador_1, jugador_2) as jmax'),
            ])
            ->groupBy('zona', 'posicion_grupo', 'jmin', 'jmax')
            ->orderBy('zona')
            ->orderBy('posicion_grupo')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $z = (string) $r->zona;
            $p = (int) $r->posicion_grupo;
            $j1 = (int) $r->jmin;
            $j2 = (int) $r->jmax;
            if ($p <= 0 || $j1 <= 0 || $j2 <= 0) continue;

            if (!isset($out[$z])) $out[$z] = [];

            // Asegurar índices 0-based por posición (A1 => idx 0)
            $out[$z][$p - 1] = [
                'jugador_1' => $j1,
                'jugador_2' => $j2,
                'zona' => $z,
                'posicion' => $p,
            ];
        }

        // Reindexar para que no queden "huecos" si falta alguna posición
        foreach ($out as $z => $arr) {
            ksort($arr);
            $out[$z] = array_values($arr);
        }

        return $out;
    }

    /**
     * Mapa referencia de llave (A1, B2, …) → pareja clasificada, leyendo la fase de grupos en `grupos`.
     *
     * Prioridad: `referencia_config` cuando coincide con /^[A-P]\d+$/; si falta y `zona` es una sola letra A–P,
     * se usa zona + `posicion_grupo` (ej. zona "A" y posición 2 → "A2"). Así se alinea con la config de cruces
     * sin depender del orden alfabético de nombres de zona.
     *
     * @return array<string,array{jugador_1:int,jugador_2:int,zona:string,posicion:int}>
     */
    private function obtenerMapaReferenciaBracketDesdeGrupos(int $torneoId): array
    {
        $map = [];
        // Importante: en fase de grupos hay múltiples filas por pareja (una por partido),
        // y en algunos torneos se detectó desalineación de referencia_config/posicion_grupo en filas sueltas.
        // Para resolver refs A1/A2/... de forma estable, agrupamos por (zona, posicion_grupo, pareja)
        // y construimos la ref esperada desde zona+posicion (cuando la zona es A–P).
        $rows = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where(function ($q) {
                $q->whereNotIn('zona', ['dieciseisavos final', '16avos final', 'octavos final', 'cuartos final', 'semifinal', 'final'])
                    ->where('zona', 'not like', 'cuartos final|%')
                    ->where('zona', 'not like', 'ganador %')
                    ->where('zona', 'not like', 'perdedor %');
            })
            ->whereNotNull('posicion_grupo')
            ->where('posicion_grupo', '>', 0)
            ->whereNotNull('jugador_1')
            ->whereNotNull('jugador_2')
            ->where('jugador_1', '>', 0)
            ->where('jugador_2', '>', 0)
            ->select([
                'zona',
                'posicion_grupo',
                DB::raw('LEAST(jugador_1, jugador_2) as jmin'),
                DB::raw('GREATEST(jugador_1, jugador_2) as jmax'),
                DB::raw('MIN(referencia_config) as referencia_config'),
            ])
            ->groupBy('zona', 'posicion_grupo', 'jmin', 'jmax')
            ->orderBy('zona')
            ->orderBy('posicion_grupo')
            ->get();

        foreach ($rows as $g) {
            $zona = trim((string) $g->zona);
            $pos = (int) $g->posicion_grupo;
            $j1 = (int) $g->jmin;
            $j2 = (int) $g->jmax;
            if ($pos <= 0 || $j1 <= 0 || $j2 <= 0) {
                continue;
            }

            // Ref esperada desde zona+posicion cuando la zona es una letra A–P.
            $refEsperada = null;
            if ($zona !== '' && preg_match('/^[A-P]$/i', $zona)) {
                $refEsperada = strtoupper($zona) . $pos;
            }

            $refCfg = strtoupper(trim((string) ($g->referencia_config ?? '')));
            $refCfgValida = ($refCfg !== '' && preg_match('/^[A-P]\d+$/', $refCfg));

            // Si referencia_config existe y coincide con la esperada, la usamos; si no coincide, preferimos la esperada.
            // Esto evita que un A2 termine apuntando a la pareja de A1 por una fila suelta mal persistida.
            $refFinal = null;
            if ($refEsperada !== null) {
                $refFinal = $refEsperada;
            } else if ($refCfgValida) {
                $refFinal = $refCfg;
            } else {
                continue;
            }

            $map[$refFinal] = [
                'jugador_1' => $j1,
                'jugador_2' => $j2,
                'zona' => (string) $g->zona,
                'posicion' => $pos,
            ];
        }

        return $map;
    }

    /**
     * Resuelve la referencia de llave (A1, B2, …) para una pareja en una zona de grupos, alineada con `grupos.posicion_grupo`
     * y `referencia_config` persistidos — evita usar solo el índice del array ordenado (desfase en zonas de 4 con 3 clasificados).
     *
     * @param array<string,mixed> $pareja Debe incluir jugador_1, jugador_2
     */
    private function resolverReferenciaBracketParaParejaZona(int $torneoId, string $zona, array $pareja, array $zonaALetra): ?string
    {
        $j1 = (int) ($pareja['jugador_1'] ?? 0);
        $j2 = (int) ($pareja['jugador_2'] ?? 0);
        if ($j1 <= 0 || $j2 <= 0) {
            return null;
        }

        $gRow = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', $zona)
            ->whereNotNull('posicion_grupo')
            ->where('posicion_grupo', '>', 0)
            ->where(function ($q) use ($j1, $j2) {
                $q->where(function ($q2) use ($j1, $j2) {
                    $q2->where('jugador_1', $j1)->where('jugador_2', $j2);
                })->orWhere(function ($q2) use ($j1, $j2) {
                    $q2->where('jugador_1', $j2)->where('jugador_2', $j1);
                });
            })
            ->orderBy('id')
            ->first(['referencia_config', 'posicion_grupo']);

        if ($gRow) {
            $rc = strtoupper(trim((string) ($gRow->referencia_config ?? '')));
            if ($rc !== '' && preg_match('/^[A-P]\d+$/', $rc)) {
                return $rc;
            }
            $pg = (int) ($gRow->posicion_grupo ?? 0);
            if ($pg > 0) {
                $zTrim = trim((string) $zona);
                if (preg_match('/^[A-P]$/', $zTrim)) {
                    return strtoupper($zTrim) . $pg;
                }
                if (isset($zonaALetra[$zona])) {
                    return $zonaALetra[$zona] . $pg;
                }
            }
        }

        return null;
    }

    /**
     * Ganadores de cuartos numerados según la configuración (CU1..CU4),
     * NO por orden de partido_id. Esto es clave para rellenar semifinales de forma consistente
     * cuando el orden de creación/IDs en BD no coincide con la config.
     *
     * @return array<int,array{jugador_1:int,jugador_2:int,posicion:int}>
     */
    private function obtenerGanadoresCuartosPorOrdenTorneo(int $torneoId): array
    {
        $out = [];
        $pids = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'cuartos final')
            ->whereNotNull('partido_id')
            ->orderBy('partido_id')
            ->pluck('partido_id')
            ->unique()
            ->values();
        if ($pids->isEmpty()) {
            return $out;
        }
        $partidos = DB::table('partidos')->whereIn('id', $pids->all())->get()->keyBy('id');
        foreach ($pids as $idx => $pid) {
            $p = $partidos->get($pid);
            if (!$p || !$this->partidoTieneResultado($p)) {
                continue;
            }
            $gan = $this->determinarGanadorPartido($p);
            if (!$gan) {
                continue;
            }
            // Número de cuartos según config (preferido); fallback a orden por partido_id.
            $num = $this->obtenerNumeroCuartos($torneoId, $p);
            if ($num <= 0) {
                $num = $idx + 1;
            }
            $gr = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('partido_id', $pid)
                ->where('zona', 'cuartos final')
                ->orderBy('id')
                ->get();
            if ($gr->count() < 2) {
                continue;
            }
            $g1 = $gr[0];
            $g2 = $gr[1];
            $out[$num] = [
                'jugador_1' => $gan === 1 ? (int) $g1->jugador_1 : (int) $g2->jugador_1,
                'jugador_2' => $gan === 1 ? (int) $g1->jugador_2 : (int) $g2->jugador_2,
                'posicion' => $num,
            ];
        }

        return $out;
    }

    /**
     * Versión liviana de cruces para depuración en el navegador (console.log), sin objetos partido completos.
     *
     * @param array<int,array<string,mixed>> $cruces
     * @return array<int,array<string,mixed>>
     */
    private function serializarCrucesParaDebug(array $cruces)
    {
        $out = [];
        foreach ($cruces as $c) {
            $row = [
                'id' => $c['id'] ?? null,
                'ronda' => $c['ronda'] ?? null,
                'partido_id' => $c['partido_id'] ?? null,
                'dia' => $c['dia'] ?? null,
                'horario' => $c['horario'] ?? null,
                'referencia_1' => $c['referencia_1'] ?? null,
                'referencia_2' => $c['referencia_2'] ?? null,
                'pareja_1' => isset($c['pareja_1']) ? [
                    'jugador_1' => (int) ($c['pareja_1']['jugador_1'] ?? 0),
                    'jugador_2' => (int) ($c['pareja_1']['jugador_2'] ?? 0),
                ] : null,
                'pareja_2' => isset($c['pareja_2']) ? [
                    'jugador_1' => (int) ($c['pareja_2']['jugador_1'] ?? 0),
                    'jugador_2' => (int) ($c['pareja_2']['jugador_2'] ?? 0),
                ] : null,
            ];
            $p = $c['partido'] ?? null;
            if ($p) {
                $o = is_array($p) ? (object) $p : $p;
                $row['sets'] = [
                    'p1' => [(int) ($o->pareja_1_set_1 ?? 0), (int) ($o->pareja_1_set_2 ?? 0), (int) ($o->pareja_1_set_3 ?? 0)],
                    'p2' => [(int) ($o->pareja_2_set_1 ?? 0), (int) ($o->pareja_2_set_2 ?? 0), (int) ($o->pareja_2_set_3 ?? 0)],
                ];
            }
            $out[] = $row;
        }
        return $out;
    }

    /**
     * Asigna partido_id y parejas a un cruce buscando en BD el partido cuyos dos grupos tienen
     * referencia_config igual a (ref1, ref2) en cualquier orden.
     * Evita enlazar por orden de partido_id en BD (que no coincide con cuartos_1..4 / semis de la config).
     *
     * @param \Illuminate\Support\Collection|array $partidosPorPartidoId grupos agrupados por partido_id
     * @param array<int|string,bool>               $partidosUsados
     * @param \Illuminate\Support\Collection $partidosObj keyBy partido id
     */
    private function enlazarCrucePorReferenciasEnGrupos(
        array &$cruce,
        $partidosPorPartidoId,
        array &$partidosUsados,
        $partidosObj,
        $ref1,
        $ref2
    ) {
        $norm = function ($r) {
            $r = strtoupper(trim((string) $r));
            if ($r === '') return '';
            // Normalizar ganadores de cuartos: CU1, legacy C1, G1-4tos / G1-cuartos → CU1
            if (preg_match('/^CU(\d+)$/', $r, $m)) return 'CU' . (int) $m[1];
            if (preg_match('/^C(\d+)$/', $r, $m)) return 'CU' . (int) $m[1];
            if (preg_match('/^G(\d+)-(4TOS|CUARTOS)$/', $r, $m)) return 'CU' . (int) $m[1];
            // Normalizar ganadores de octavos: O1, G1-8vos / G1-octavos → O1
            if (preg_match('/^O(\d+)$/', $r, $m)) return 'O' . (int) $m[1];
            if (preg_match('/^G(\d+)-(8VOS|OCTAVOS)$/', $r, $m)) return 'O' . (int) $m[1];
            // Normalizar ganadores de semifinal: S1, G1-semifinal / G1-semis → S1
            if (preg_match('/^S(\d+)$/', $r, $m)) return 'S' . (int) $m[1];
            if (preg_match('/^G(\d+)-(2TOS|SEMIS|SEMIFINAL)$/', $r, $m)) return 'S' . (int) $m[1];
            return $r;
        };

        $ref1 = $norm($ref1);
        $ref2 = $norm($ref2);
        if ($ref1 === '' || $ref2 === '') {
            return false;
        }
        foreach ($partidosPorPartidoId as $partidoId => $grupos) {
            if (isset($partidosUsados[$partidoId]) || $grupos->count() < 2) {
                continue;
            }
            $g1 = $grupos[0];
            $g2 = $grupos[1];
            $r1 = $norm($g1->referencia_config ?? '');
            $r2 = $norm($g2->referencia_config ?? '');
            $match = ($r1 === $ref1 && $r2 === $ref2) || ($r1 === $ref2 && $r2 === $ref1);
            if (!$match) {
                continue;
            }
            $g1Vacio = (int) ($g1->jugador_1 ?? 0) === 0 && (int) ($g1->jugador_2 ?? 0) === 0;
            $g2Vacio = (int) ($g2->jugador_1 ?? 0) === 0 && (int) ($g2->jugador_2 ?? 0) === 0;
            $cruce['partido_id'] = $partidoId;
            $cruce['partido'] = $partidosObj[$partidoId] ?? null;
            $ordenIgualConfig = ($r1 === $ref1 && $r2 === $ref2);

            // No pisar parejas ya resueltas por Paso 1. Solo completar si faltan.
            $p1 = $cruce['pareja_1'] ?? null;
            $p2 = $cruce['pareja_2'] ?? null;
            $p1Ok = is_array($p1) && (int) ($p1['jugador_1'] ?? 0) > 0 && (int) ($p1['jugador_2'] ?? 0) > 0;
            $p2Ok = is_array($p2) && (int) ($p2['jugador_1'] ?? 0) > 0 && (int) ($p2['jugador_2'] ?? 0) > 0;
            if ($ordenIgualConfig) {
                if (!$p1Ok) $cruce['pareja_1'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                if (!$p2Ok) $cruce['pareja_2'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
            } else {
                if (!$p1Ok) $cruce['pareja_1'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                if (!$p2Ok) $cruce['pareja_2'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
            }
            $partidosUsados[$partidoId] = true;

            return true;
        }

        return false;
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Muestra la vista de cruces puntuables (versión antigua)
     */
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

    /**
     * Muestra la vista de cruces puntuables V2 (con soporte para octavos)
     */
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
        
        // Reparar: si hay 16avos y falta el partido octavos "ganador 16avos vs A1", crearlo
        $this->asegurarPartidoOctavosGanador16avosVsA1($torneoId);
        
        // Obtener todos los grupos eliminatorios con sus partidos
        // Usar DISTINCT para evitar duplicados
        $gruposEliminatorios = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->whereIn('zona', ['octavos final', 'cuartos final', 'semifinal', 'final', '16avos final'])
            ->whereNotNull('partido_id')
            ->orderBy('zona')
            ->orderBy('partido_id')
            ->orderBy('id')
            ->get();
        
        // Agrupar por partido_id, asegurándose de que cada partido solo aparezca una vez
        $partidosAgrupados = [];
        $partidosProcesados = []; // Para evitar procesar el mismo partido múltiples veces
        
        foreach ($gruposEliminatorios as $grupo) {
            $partidoId = $grupo->partido_id;
            
            // Solo procesar si este partido_id no ha sido procesado aún
            if (!in_array($partidoId, $partidosProcesados)) {
                // Obtener todos los grupos de este partido
                $gruposDelPartido = DB::table('grupos')
                    ->where('torneo_id', $torneoId)
                    ->where('partido_id', $partidoId)
                    ->orderBy('id')
                    ->get();
                
                if ($gruposDelPartido->count() >= 2) {
                    $partidosAgrupados[$partidoId] = [
                        'zona' => $grupo->zona,
                        'partido_id' => $partidoId,
                        'grupos' => $gruposDelPartido->toArray()
                    ];
                    $partidosProcesados[] = $partidoId;
                }
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
        $cruces = [];
        $crucesPorPartidoId = []; // Para evitar duplicados
        
        foreach ($partidosAgrupados as $partidoId => $datosPartido) {
            // Evitar procesar el mismo partido_id múltiples veces
            if (isset($crucesPorPartidoId[$partidoId])) {
                continue;
            }
            
            if (count($datosPartido['grupos']) >= 2) {
                // Convertir arrays a objetos si es necesario
                $g1 = is_array($datosPartido['grupos'][0]) ? (object)$datosPartido['grupos'][0] : $datosPartido['grupos'][0];
                $g2 = is_array($datosPartido['grupos'][1]) ? (object)$datosPartido['grupos'][1] : $datosPartido['grupos'][1];
                $partido = $partidos[$partidoId] ?? null;
                
                // Determinar la ronda según la zona
                $ronda = 'octavos';
                if ($datosPartido['zona'] === '16avos final') {
                    $ronda = '16avos';
                } else if ($datosPartido['zona'] === 'cuartos final') {
                    $ronda = 'cuartos';
                } else if ($datosPartido['zona'] === 'semifinal') {
                    $ronda = 'semifinales';
                } else if ($datosPartido['zona'] === 'final') {
                    $ronda = 'final';
                }
                
                // Crear el cruce (incluir día y horario si existen en el grupo)
                $dia = $g1->fecha ?? $g2->fecha ?? null;
                $horario = $g1->horario ?? $g2->horario ?? null;
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
                    'referencia_1' => $g1->referencia_config ?? null,
                    'referencia_2' => $g2->referencia_config ?? null,
                    'ronda' => $ronda,
                    'partido' => $partido, // Incluir el objeto partido completo
                    'dia' => $dia,
                    'horario' => $horario
                ];
                
                // Solo agregar si no existe ya un cruce con este partido_id
                if (!isset($crucesPorPartidoId[$partidoId])) {
                    $cruces[] = $cruce;
                    $crucesPorPartidoId[$partidoId] = true;
                }
            }
        }
        
        // SIEMPRE generar cruces desde la configuración para completar los que faltan
        // (especialmente los cruces de cuartos que esperan ganadores de octavos)
        // Esto asegura que se muestren todos los cruces configurados, incluso los que esperan ganadores
        \Log::info('Generando cruces desde configuración para torneo: ' . $torneoId . ' (cruces en BD: ' . count($cruces) . ')');
        
        // Asegurar posiciones persistidas en `grupos.posicion_grupo` para zonas completas
        // (no depende de pasar por la pantalla de resultados).
        TorneoGrupoPosicionesService::syncPosicionesGruposFaltantes($torneoId);

        // Construir posiciones por zona desde DB (grupos.zona + grupos.posicion_grupo).
        // Esto es la fuente de verdad para resolver referencias A1, A2, etc. según la configuración.
        $posicionesPorZona = $this->obtenerPosicionesPorZonaDesdeGrupos($torneoId);
        $zonas = collect(array_keys($posicionesPorZona))->sort()->values();
        
        // Obtener configuración de cruces (prioridad: torneo_id, luego global)
        $totalParejasClasificadas = 0;
        foreach ($posicionesPorZona as $zona => $posiciones) {
            $totalParejasClasificadas += count($posiciones);
        }

        $letrasZonasRefs = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P'];
        $zonaALetraParaRefs = [];
        foreach ($zonas as $idx => $zNombre) {
            if (isset($letrasZonasRefs[$idx])) {
                $zonaALetraParaRefs[$zNombre] = $letrasZonasRefs[$idx];
            }
        }
        $crucesV2Debug = [
            'torneo_id' => (int) $torneoId,
            'posicionesPorZona' => $posicionesPorZona,
            'zonasOrdenadas' => $zonas->values()->all(),
            'zonaALetra' => $zonaALetraParaRefs,
            'totalParejasClasificadas' => $totalParejasClasificadas,
            'configResumen' => null,
            'pasos' => [],
        ];
        
        $configuracionCruces = $this->getConfiguracionCruces($torneoId, $totalParejasClasificadas);
        $mapaReferenciaBracket = $this->obtenerMapaReferenciaBracketDesdeGrupos((int) $torneoId);
        $crucesV2Debug['mapaReferenciaBracket'] = $mapaReferenciaBracket;

        if ($configuracionCruces) {
            \Log::info('Configuración encontrada para ' . $totalParejasClasificadas . ' parejas (torneo ' . $torneoId . ')');
            $crucesV2Debug['configResumen'] = [
                'id' => (int) $configuracionCruces->id,
                'cantidad_parejas' => isset($configuracionCruces->cantidad_parejas) ? (int) $configuracionCruces->cantidad_parejas : null,
                'tiene_16avos_final' => !empty($configuracionCruces->tiene_16avos_final),
                'tiene_8vos_final' => !empty($configuracionCruces->tiene_8vos_final),
                'tiene_4tos_final' => !empty($configuracionCruces->tiene_4tos_final),
            ];
            $crucesDesdeConfig = $this->generarCrucesDesdeConfiguracion($configuracionCruces, $posicionesPorZona, $zonas, $mapaReferenciaBracket, (int) $torneoId);
            $crucesV2Debug['pasos']['desde_config_generador'] = $this->serializarCrucesParaDebug($crucesDesdeConfig);
            
            // Asegurar que cada cruce de primera ronda (octavos/16avos) tenga partido en BD
            foreach ($crucesDesdeConfig as $idx => $cruceConfig) {
                $ronda = $cruceConfig['ronda'] ?? null;
                if ($ronda !== 'octavos' && $ronda !== '16avos') {
                    continue;
                }
                $p1 = $cruceConfig['pareja_1'] ?? null;
                $p2 = $cruceConfig['pareja_2'] ?? null;
                if (!$p1 || !$p2 || !isset($p1['jugador_1'], $p1['jugador_2'], $p2['jugador_1'], $p2['jugador_2'])) {
                    continue;
                }
                $pareja1 = ['jugador_1' => $p1['jugador_1'], 'jugador_2' => $p1['jugador_2']];
                $pareja2 = ['jugador_1' => $p2['jugador_1'], 'jugador_2' => $p2['jugador_2']];
                $ref1 = $cruceConfig['referencia_1'] ?? null;
                $ref2 = $cruceConfig['referencia_2'] ?? null;
                
                $partidoExistente = DB::table('grupos as g1')
                    ->join('grupos as g2', function($join) {
                        $join->on('g1.partido_id', '=', 'g2.partido_id')->whereRaw('g1.id != g2.id')
                             ->whereNotNull('g1.partido_id')->whereNotNull('g2.partido_id');
                    })
                    ->where('g1.torneo_id', $torneoId)
                    ->where('g1.zona', $ronda === '16avos' ? '16avos final' : 'octavos final')
                    ->where('g2.torneo_id', $torneoId)
                    ->where(function($q) use ($pareja1, $pareja2) {
                        $q->where(function($q2) use ($pareja1, $pareja2) {
                            $q2->where('g1.jugador_1', $pareja1['jugador_1'])->where('g1.jugador_2', $pareja1['jugador_2'])
                               ->where('g2.jugador_1', $pareja2['jugador_1'])->where('g2.jugador_2', $pareja2['jugador_2']);
                        })->orWhere(function($q2) use ($pareja1, $pareja2) {
                            $q2->where('g1.jugador_1', $pareja2['jugador_1'])->where('g1.jugador_2', $pareja2['jugador_2'])
                               ->where('g2.jugador_1', $pareja1['jugador_1'])->where('g2.jugador_2', $pareja1['jugador_2']);
                        });
                    })
                    ->select('g1.partido_id')
                    ->first();
                
                if ($partidoExistente) {
                    $crucesDesdeConfig[$idx]['partido_id'] = $partidoExistente->partido_id;
                } elseif ($ref1 && $ref2) {
                    // No encontrado por jugadores: buscar por referencia_config (partidos creados al iniciar torneo)
                    $partidoPorRef = DB::table('grupos as g1')
                        ->join('grupos as g2', function($join) {
                            $join->on('g1.partido_id', '=', 'g2.partido_id')->whereRaw('g1.id != g2.id');
                        })
                        ->where('g1.torneo_id', $torneoId)
                        ->where('g1.zona', $ronda === '16avos' ? '16avos final' : 'octavos final')
                        ->where('g2.torneo_id', $torneoId)
                        ->where(function($q) use ($ref1, $ref2) {
                            $q->where(function($q2) use ($ref1, $ref2) {
                                $q2->where('g1.referencia_config', $ref1)->where('g2.referencia_config', $ref2);
                            })->orWhere(function($q2) use ($ref1, $ref2) {
                                $q2->where('g1.referencia_config', $ref2)->where('g2.referencia_config', $ref1);
                            });
                        })
                        ->select('g1.partido_id', 'g1.id as g1_id', 'g2.id as g2_id')
                        ->first();
                    if ($partidoPorRef) {
                        $crucesDesdeConfig[$idx]['partido_id'] = $partidoPorRef->partido_id;
                        // Actualizar jugadores en los grupos (rellenar placeholder)
                        $gruposP = DB::table('grupos')->where('partido_id', $partidoPorRef->partido_id)->where('torneo_id', $torneoId)->orderBy('id')->get();
                        if ($gruposP->count() >= 2) {
                            $g1 = $gruposP[0];
                            $g2 = $gruposP[1];
                            $esG1Pareja1 = (trim($g1->referencia_config ?? '') === $ref1 && trim($g2->referencia_config ?? '') === $ref2);
                            $esG1Pareja2 = (trim($g1->referencia_config ?? '') === $ref2 && trim($g2->referencia_config ?? '') === $ref1);
                            if ($esG1Pareja1) {
                                DB::table('grupos')->where('id', $g1->id)->update(['jugador_1' => $pareja1['jugador_1'], 'jugador_2' => $pareja1['jugador_2']]);
                                DB::table('grupos')->where('id', $g2->id)->update(['jugador_1' => $pareja2['jugador_1'], 'jugador_2' => $pareja2['jugador_2']]);
                            } else {
                                DB::table('grupos')->where('id', $g1->id)->update(['jugador_1' => $pareja2['jugador_1'], 'jugador_2' => $pareja2['jugador_2']]);
                                DB::table('grupos')->where('id', $g2->id)->update(['jugador_1' => $pareja1['jugador_1'], 'jugador_2' => $pareja1['jugador_2']]);
                            }
                        }
                    }
                    // Si no se encuentra: NO crear partido nuevo. Los partidos se crean solo al iniciar torneo.
                }
            }
            
            // Validar y eliminar grupos/partidos de octavos y 16avos que no estén en la configuración (evitar 10 partidos cuando debe haber 8)
            $this->limpiarGruposEliminatoriosExcedentes($torneoId, $crucesDesdeConfig);
            
            // Rellenar partido y resultados en cada cruce; para cuartos/semifinal/final rellenar parejas desde BD si existen
            $partidosCuartosBD = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'cuartos final')
                ->whereNotNull('partido_id')
                ->orderBy('partido_id')->orderBy('id')
                ->get()
                ->groupBy('partido_id');
            $partidosSemifinalBD = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'semifinal')
                ->whereNotNull('partido_id')
                ->orderBy('partido_id')->orderBy('id')
                ->get()
                ->groupBy('partido_id');
            $partidosFinalBD = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'final')
                ->whereNotNull('partido_id')
                ->orderBy('partido_id')->orderBy('id')
                ->get()
                ->groupBy('partido_id');
            
            // Cargar datos de TODOS los partidos (octavos + cuartos + semi + final) para que los resultados se muestren al recargar
            $partidosIds = [];
            foreach ($crucesDesdeConfig as $c) {
                if (!empty($c['partido_id'])) {
                    $partidosIds[] = $c['partido_id'];
                }
            }
            $partidosIds = array_merge(
                $partidosIds,
                $partidosCuartosBD->keys()->all(),
                $partidosSemifinalBD->keys()->all(),
                $partidosFinalBD->keys()->all()
            );
            $partidosIds = array_unique(array_filter($partidosIds));
            $partidosObj = [];
            if (count($partidosIds) > 0) {
                $partidosObj = DB::table('partidos')->whereIn('id', $partidosIds)->get()->keyBy('id');
            }
            
            $cuartosOrdenados = $partidosCuartosBD->keys()->sort()->values()->all();
            $semifinalOrdenados = $partidosSemifinalBD->keys()->sort()->values()->all();
            $finalOrdenados = $partidosFinalBD->keys()->sort()->values()->all();
            $partidosCuartosUsados = []; // Evitar asignar el mismo partido a varios cruces
            $partidosSemifinalUsados = [];
            $partidosFinalUsados = [];
            
            foreach ($crucesDesdeConfig as $idx => $c) {
                if (!empty($c['partido_id'])) {
                    $crucesDesdeConfig[$idx]['partido'] = $partidosObj[$c['partido_id']] ?? null;
                }
                $ronda = $c['ronda'] ?? null;
                // Si ya existe el partido en BD (creado al comenzar torneo) y se actualizaron los grupos (jugadores),
                // reflejarlo en la vista aunque el generador por refs (CU1/CU2/...) aún no pueda resolverlo.
                if (!empty($c['partido_id']) && in_array($ronda, ['semifinales', 'final'], true)) {
                    $pid = (int) $c['partido_id'];
                    $gruposBD = null;
                    if ($ronda === 'semifinales') {
                        $gruposBD = $partidosSemifinalBD[$pid] ?? null;
                    } elseif ($ronda === 'final') {
                        $gruposBD = $partidosFinalBD[$pid] ?? null;
                    }
                    if ($gruposBD && $gruposBD->count() >= 2) {
                        $g1 = $gruposBD[0];
                        $g2 = $gruposBD[1];
                        $ref1 = strtoupper(trim((string)($c['referencia_1'] ?? '')));
                        $ref2 = strtoupper(trim((string)($c['referencia_2'] ?? '')));
                        $g1Ref = strtoupper(trim((string)($g1->referencia_config ?? '')));
                        $g2Ref = strtoupper(trim((string)($g2->referencia_config ?? '')));

                        $p1 = $c['pareja_1'] ?? null;
                        $p2 = $c['pareja_2'] ?? null;
                        $p1Ok = is_array($p1) && (int)($p1['jugador_1'] ?? 0) > 0 && (int)($p1['jugador_2'] ?? 0) > 0;
                        $p2Ok = is_array($p2) && (int)($p2['jugador_1'] ?? 0) > 0 && (int)($p2['jugador_2'] ?? 0) > 0;

                        // Si podemos matchear por referencia_config, respetar el orden (ref1/ref2).
                        if ($ref1 !== '' && $ref2 !== '' && (($g1Ref === $ref1 && $g2Ref === $ref2) || ($g1Ref === $ref2 && $g2Ref === $ref1))) {
                            $slot1 = ($g1Ref === $ref1) ? $g1 : $g2;
                            $slot2 = ($g1Ref === $ref1) ? $g2 : $g1;
                            if (!$p1Ok && (int)($slot1->jugador_1 ?? 0) > 0 && (int)($slot1->jugador_2 ?? 0) > 0) {
                                $crucesDesdeConfig[$idx]['pareja_1'] = ['jugador_1' => (int)$slot1->jugador_1, 'jugador_2' => (int)$slot1->jugador_2];
                            }
                            if (!$p2Ok && (int)($slot2->jugador_1 ?? 0) > 0 && (int)($slot2->jugador_2 ?? 0) > 0) {
                                $crucesDesdeConfig[$idx]['pareja_2'] = ['jugador_1' => (int)$slot2->jugador_1, 'jugador_2' => (int)$slot2->jugador_2];
                            }
                        } else {
                            // Fallback: si falta en el cruce, tomar lo que haya en BD por orden de grupos.
                            $g1V = (int)($g1->jugador_1 ?? 0) <= 0 || (int)($g1->jugador_2 ?? 0) <= 0;
                            $g2V = (int)($g2->jugador_1 ?? 0) <= 0 || (int)($g2->jugador_2 ?? 0) <= 0;
                            if (!$p1Ok && !$g1V) $crucesDesdeConfig[$idx]['pareja_1'] = ['jugador_1' => (int)$g1->jugador_1, 'jugador_2' => (int)$g1->jugador_2];
                            if (!$p2Ok && !$g2V) $crucesDesdeConfig[$idx]['pareja_2'] = ['jugador_1' => (int)$g2->jugador_1, 'jugador_2' => (int)$g2->jugador_2];
                        }
                    }
                }
                if ($ronda === 'cuartos') {
                    // Buscar el partido en BD que corresponde a ESTE cruce (por parejas), no por índice
                    $expectedP1 = $c['pareja_1'] ?? null;
                    $expectedP2 = $c['pareja_2'] ?? null;
                    $expectedP1Ok = is_array($expectedP1) && (int)($expectedP1['jugador_1'] ?? 0) > 0 && (int)($expectedP1['jugador_2'] ?? 0) > 0;
                    $expectedP2Ok = is_array($expectedP2) && (int)($expectedP2['jugador_1'] ?? 0) > 0 && (int)($expectedP2['jugador_2'] ?? 0) > 0;
                    $partidoEncontrado = null;
                    foreach ($partidosCuartosBD as $partidoId => $gruposC) {
                        if (isset($partidosCuartosUsados[$partidoId]) || $gruposC->count() < 2) continue;
                        $g1 = $gruposC[0]; $g2 = $gruposC[1];
                        $g1Vacio = (int)($g1->jugador_1 ?? 0) === 0 && (int)($g1->jugador_2 ?? 0) === 0;
                        $g2Vacio = (int)($g2->jugador_1 ?? 0) === 0 && (int)($g2->jugador_2 ?? 0) === 0;
                        $g1MatchP1 = $expectedP1 && !$g1Vacio && (int)$g1->jugador_1 === (int)($expectedP1['jugador_1'] ?? 0) && (int)$g1->jugador_2 === (int)($expectedP1['jugador_2'] ?? 0);
                        $g1MatchP2 = $expectedP2 && !$g1Vacio && (int)$g1->jugador_1 === (int)($expectedP2['jugador_1'] ?? 0) && (int)$g1->jugador_2 === (int)($expectedP2['jugador_2'] ?? 0);
                        $g2MatchP1 = $expectedP1 && !$g2Vacio && (int)$g2->jugador_1 === (int)($expectedP1['jugador_1'] ?? 0) && (int)$g2->jugador_2 === (int)($expectedP1['jugador_2'] ?? 0);
                        $g2MatchP2 = $expectedP2 && !$g2Vacio && (int)$g2->jugador_1 === (int)($expectedP2['jugador_1'] ?? 0) && (int)$g2->jugador_2 === (int)($expectedP2['jugador_2'] ?? 0);
                        if ($g1MatchP1 || $g1MatchP2 || $g2MatchP1 || $g2MatchP2) {
                            $partidoEncontrado = ['partido_id' => $partidoId, 'g1' => $g1, 'g2' => $g2, 'g1Vacio' => $g1Vacio, 'g2Vacio' => $g2Vacio];
                            break;
                        }
                    }
                    if ($partidoEncontrado) {
                        $partidoId = $partidoEncontrado['partido_id'];
                        $g1 = $partidoEncontrado['g1']; $g2 = $partidoEncontrado['g2'];
                        $g1Vacio = $partidoEncontrado['g1Vacio']; $g2Vacio = $partidoEncontrado['g2Vacio'];
                        $crucesDesdeConfig[$idx]['partido_id'] = $partidoId;
                        $crucesDesdeConfig[$idx]['partido'] = $partidosObj[$partidoId] ?? null;
                        // CRÍTICO: si el generador (Paso 1) ya resolvió A1/A2/... a jugadores reales,
                        // no los pisamos con lo que venga en BD (en BD puede haber placeholders o refs desalineadas).
                        // Solo rellenar desde BD cuando falte alguna pareja en el cruce.
                        if (!$expectedP1Ok || !$expectedP2Ok) {
                            $matchG1P2 = $expectedP2 && !$g1Vacio && (int)$g1->jugador_1 === (int)($expectedP2['jugador_1'] ?? 0) && (int)$g1->jugador_2 === (int)($expectedP2['jugador_2'] ?? 0);
                            $matchG2P2 = $expectedP2 && !$g2Vacio && (int)$g2->jugador_1 === (int)($expectedP2['jugador_1'] ?? 0) && (int)$g2->jugador_2 === (int)($expectedP2['jugador_2'] ?? 0);
                            $matchG1P1 = $expectedP1 && !$g1Vacio && (int)$g1->jugador_1 === (int)($expectedP1['jugador_1'] ?? 0) && (int)$g1->jugador_2 === (int)($expectedP1['jugador_2'] ?? 0);
                            $matchG2P1 = $expectedP1 && !$g2Vacio && (int)$g2->jugador_1 === (int)($expectedP1['jugador_1'] ?? 0) && (int)$g2->jugador_2 === (int)($expectedP1['jugador_2'] ?? 0);
                            if ($matchG1P2) {
                                if (!$expectedP1Ok) $crucesDesdeConfig[$idx]['pareja_1'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                                if (!$expectedP2Ok) $crucesDesdeConfig[$idx]['pareja_2'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                            } elseif ($matchG2P2) {
                                if (!$expectedP1Ok) $crucesDesdeConfig[$idx]['pareja_1'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                                if (!$expectedP2Ok) $crucesDesdeConfig[$idx]['pareja_2'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                            } elseif ($matchG1P1) {
                                if (!$expectedP1Ok) $crucesDesdeConfig[$idx]['pareja_1'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                                if (!$expectedP2Ok) $crucesDesdeConfig[$idx]['pareja_2'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                            } elseif ($matchG2P1) {
                                if (!$expectedP1Ok) $crucesDesdeConfig[$idx]['pareja_1'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                                if (!$expectedP2Ok) $crucesDesdeConfig[$idx]['pareja_2'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                            } else {
                                if (!$expectedP1Ok) $crucesDesdeConfig[$idx]['pareja_1'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                                if (!$expectedP2Ok) $crucesDesdeConfig[$idx]['pareja_2'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                            }
                        }
                        $partidosCuartosUsados[$partidoId] = true;
                    }
                }
                // Semifinal / final: no enlazar aquí por índice de partido_id (orden BD ≠ orden de la config).
                // Se enlazan por referencia_config en la pasada dedicada más abajo.
            }
            
            // Segunda pasada cuartos: cruces que siguen sin partido_id — buscar partido no usado que coincida por parejas
            foreach ($crucesDesdeConfig as $idx => $c) {
                if (($c['ronda'] ?? '') !== 'cuartos') continue;
                if (!empty($c['partido_id'])) continue;
                $expectedP1 = $c['pareja_1'] ?? null;
                $expectedP2 = $c['pareja_2'] ?? null;
                $expectedP1Ok = is_array($expectedP1) && (int)($expectedP1['jugador_1'] ?? 0) > 0 && (int)($expectedP1['jugador_2'] ?? 0) > 0;
                $expectedP2Ok = is_array($expectedP2) && (int)($expectedP2['jugador_1'] ?? 0) > 0 && (int)($expectedP2['jugador_2'] ?? 0) > 0;
                foreach ($partidosCuartosBD as $partidoId => $gruposC) {
                    if (isset($partidosCuartosUsados[$partidoId]) || $gruposC->count() < 2) continue;
                    $g1 = $gruposC[0]; $g2 = $gruposC[1];
                    $g1Vacio = (int)($g1->jugador_1 ?? 0) === 0 && (int)($g1->jugador_2 ?? 0) === 0;
                    $g2Vacio = (int)($g2->jugador_1 ?? 0) === 0 && (int)($g2->jugador_2 ?? 0) === 0;
                    $g1MatchP1 = $expectedP1 && !$g1Vacio && (int)$g1->jugador_1 === (int)($expectedP1['jugador_1'] ?? 0) && (int)$g1->jugador_2 === (int)($expectedP1['jugador_2'] ?? 0);
                    $g1MatchP2 = $expectedP2 && !$g1Vacio && (int)$g1->jugador_1 === (int)($expectedP2['jugador_1'] ?? 0) && (int)$g1->jugador_2 === (int)($expectedP2['jugador_2'] ?? 0);
                    $g2MatchP1 = $expectedP1 && !$g2Vacio && (int)$g2->jugador_1 === (int)($expectedP1['jugador_1'] ?? 0) && (int)$g2->jugador_2 === (int)($expectedP1['jugador_2'] ?? 0);
                    $g2MatchP2 = $expectedP2 && !$g2Vacio && (int)$g2->jugador_1 === (int)($expectedP2['jugador_1'] ?? 0) && (int)$g2->jugador_2 === (int)($expectedP2['jugador_2'] ?? 0);
                    if (!$g1MatchP1 && !$g1MatchP2 && !$g2MatchP1 && !$g2MatchP2) continue;
                    $crucesDesdeConfig[$idx]['partido_id'] = $partidoId;
                    $crucesDesdeConfig[$idx]['partido'] = $partidosObj[$partidoId] ?? null;
                    if (!$expectedP1Ok || !$expectedP2Ok) {
                        $matchG1P2 = $expectedP2 && !$g1Vacio && (int)$g1->jugador_1 === (int)($expectedP2['jugador_1'] ?? 0) && (int)$g1->jugador_2 === (int)($expectedP2['jugador_2'] ?? 0);
                        $matchG2P2 = $expectedP2 && !$g2Vacio && (int)$g2->jugador_1 === (int)($expectedP2['jugador_1'] ?? 0) && (int)$g2->jugador_2 === (int)($expectedP2['jugador_2'] ?? 0);
                        $matchG1P1 = $expectedP1 && !$g1Vacio && (int)$g1->jugador_1 === (int)($expectedP1['jugador_1'] ?? 0) && (int)$g1->jugador_2 === (int)($expectedP1['jugador_2'] ?? 0);
                        $matchG2P1 = $expectedP1 && !$g2Vacio && (int)$g2->jugador_1 === (int)($expectedP1['jugador_1'] ?? 0) && (int)$g2->jugador_2 === (int)($expectedP1['jugador_2'] ?? 0);
                        if ($matchG1P2) {
                            if (!$expectedP1Ok) $crucesDesdeConfig[$idx]['pareja_1'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                            if (!$expectedP2Ok) $crucesDesdeConfig[$idx]['pareja_2'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                        } elseif ($matchG2P2) {
                            if (!$expectedP1Ok) $crucesDesdeConfig[$idx]['pareja_1'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                            if (!$expectedP2Ok) $crucesDesdeConfig[$idx]['pareja_2'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                        } elseif ($matchG1P1) {
                            if (!$expectedP1Ok) $crucesDesdeConfig[$idx]['pareja_1'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                            if (!$expectedP2Ok) $crucesDesdeConfig[$idx]['pareja_2'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                        } elseif ($matchG2P1) {
                            if (!$expectedP1Ok) $crucesDesdeConfig[$idx]['pareja_1'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                            if (!$expectedP2Ok) $crucesDesdeConfig[$idx]['pareja_2'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                        } else {
                            if (!$expectedP1Ok) $crucesDesdeConfig[$idx]['pareja_1'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                            if (!$expectedP2Ok) $crucesDesdeConfig[$idx]['pareja_2'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                        }
                    }
                    $partidosCuartosUsados[$partidoId] = true;
                    break;
                }
            }

            // Enlazar cuartos / semis / final por referencia_config de los grupos en BD (= pareja_1/pareja_2 de la config al crear el torneo).
            foreach ($crucesDesdeConfig as $idx => &$cruceRef) {
                if (!empty($cruceRef['partido_id'])) {
                    continue;
                }
                $r = $cruceRef['ronda'] ?? '';
                $r1 = $cruceRef['referencia_1'] ?? '';
                $r2 = $cruceRef['referencia_2'] ?? '';
                if ($r === 'cuartos') {
                    $this->enlazarCrucePorReferenciasEnGrupos($cruceRef, $partidosCuartosBD, $partidosCuartosUsados, $partidosObj, $r1, $r2);
                } elseif ($r === 'semifinales') {
                    $this->enlazarCrucePorReferenciasEnGrupos($cruceRef, $partidosSemifinalBD, $partidosSemifinalUsados, $partidosObj, $r1, $r2);
                } elseif ($r === 'final') {
                    $this->enlazarCrucePorReferenciasEnGrupos($cruceRef, $partidosFinalBD, $partidosFinalUsados, $partidosObj, $r1, $r2);
                }
            }
            unset($cruceRef);
            
            // Tercera pasada: solo si sigue sin partido_id, asignar por índice (último recurso; el orden de IDs en BD puede no coincidir con la config).
            foreach ($crucesDesdeConfig as $idx => $c) {
                if (!empty($c['partido_id'])) {
                    continue;
                }
                $ronda = $c['ronda'] ?? null;
                if ($ronda === 'cuartos') {
                    $i = null;
                    if (isset($c['id']) && preg_match('/cuartos_(\d+)/', $c['id'], $m)) $i = (int)$m[1] - 1;
                    if ($i === null || !isset($cuartosOrdenados[$i])) continue;
                    $partidoId = $cuartosOrdenados[$i];
                    if (isset($partidosCuartosUsados[$partidoId])) continue;
                    $gruposC = $partidosCuartosBD[$partidoId] ?? null;
                    if (!$gruposC || $gruposC->count() < 2) continue;
                    $g1 = $gruposC[0]; $g2 = $gruposC[1];
                    $g1Vacio = (int)($g1->jugador_1 ?? 0) === 0 && (int)($g1->jugador_2 ?? 0) === 0;
                    $g2Vacio = (int)($g2->jugador_1 ?? 0) === 0 && (int)($g2->jugador_2 ?? 0) === 0;
                    $crucesDesdeConfig[$idx]['partido_id'] = $partidoId;
                    $crucesDesdeConfig[$idx]['partido'] = $partidosObj[$partidoId] ?? null;
                    $expectedP1 = $c['pareja_1'] ?? null;
                    $expectedP2 = $c['pareja_2'] ?? null;
                    $expectedP1Ok = is_array($expectedP1) && (int)($expectedP1['jugador_1'] ?? 0) > 0 && (int)($expectedP1['jugador_2'] ?? 0) > 0;
                    $expectedP2Ok = is_array($expectedP2) && (int)($expectedP2['jugador_1'] ?? 0) > 0 && (int)($expectedP2['jugador_2'] ?? 0) > 0;
                    if (!$expectedP1Ok) $crucesDesdeConfig[$idx]['pareja_1'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                    if (!$expectedP2Ok) $crucesDesdeConfig[$idx]['pareja_2'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                    $partidosCuartosUsados[$partidoId] = true;
                }
                if ($ronda === 'semifinales') {
                    $i = null;
                    if (isset($c['id']) && preg_match('/semifinales_(\d+)/', $c['id'], $m)) $i = (int)$m[1] - 1;
                    if ($i === null || !isset($semifinalOrdenados[$i])) continue;
                    $partidoId = $semifinalOrdenados[$i];
                    if (isset($partidosSemifinalUsados[$partidoId])) {
                        continue;
                    }
                    $gruposS = $partidosSemifinalBD[$partidoId] ?? null;
                    if (!$gruposS || $gruposS->count() < 2) continue;
                    $g1 = $gruposS[0]; $g2 = $gruposS[1];
                    $g1Vacio = (int)($g1->jugador_1 ?? 0) === 0 && (int)($g1->jugador_2 ?? 0) === 0;
                    $g2Vacio = (int)($g2->jugador_1 ?? 0) === 0 && (int)($g2->jugador_2 ?? 0) === 0;
                    $crucesDesdeConfig[$idx]['partido_id'] = $partidoId;
                    $crucesDesdeConfig[$idx]['partido'] = $partidosObj[$partidoId] ?? null;
                    $expectedP1 = $c['pareja_1'] ?? null;
                    $expectedP2 = $c['pareja_2'] ?? null;
                    $expectedP1Ok = is_array($expectedP1) && (int)($expectedP1['jugador_1'] ?? 0) > 0 && (int)($expectedP1['jugador_2'] ?? 0) > 0;
                    $expectedP2Ok = is_array($expectedP2) && (int)($expectedP2['jugador_1'] ?? 0) > 0 && (int)($expectedP2['jugador_2'] ?? 0) > 0;
                    if (!$expectedP1Ok) $crucesDesdeConfig[$idx]['pareja_1'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                    if (!$expectedP2Ok) $crucesDesdeConfig[$idx]['pareja_2'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                    $partidosSemifinalUsados[$partidoId] = true;
                }
                if ($ronda === 'final') {
                    $i = null;
                    if (isset($c['id']) && preg_match('/final_(\d+)/', $c['id'], $m)) $i = (int)$m[1] - 1;
                    if ($i === null || !isset($finalOrdenados[$i])) continue;
                    $partidoId = $finalOrdenados[$i];
                    if (isset($partidosFinalUsados[$partidoId])) {
                        continue;
                    }
                    $gruposF = $partidosFinalBD[$partidoId] ?? null;
                    if (!$gruposF || $gruposF->count() < 2) continue;
                    $g1 = $gruposF[0]; $g2 = $gruposF[1];
                    $g1Vacio = (int)($g1->jugador_1 ?? 0) === 0 && (int)($g1->jugador_2 ?? 0) === 0;
                    $g2Vacio = (int)($g2->jugador_1 ?? 0) === 0 && (int)($g2->jugador_2 ?? 0) === 0;
                    $crucesDesdeConfig[$idx]['partido_id'] = $partidoId;
                    $crucesDesdeConfig[$idx]['partido'] = $partidosObj[$partidoId] ?? null;
                    $expectedP1 = $c['pareja_1'] ?? null;
                    $expectedP2 = $c['pareja_2'] ?? null;
                    $expectedP1Ok = is_array($expectedP1) && (int)($expectedP1['jugador_1'] ?? 0) > 0 && (int)($expectedP1['jugador_2'] ?? 0) > 0;
                    $expectedP2Ok = is_array($expectedP2) && (int)($expectedP2['jugador_1'] ?? 0) > 0 && (int)($expectedP2['jugador_2'] ?? 0) > 0;
                    if (!$expectedP1Ok) $crucesDesdeConfig[$idx]['pareja_1'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                    if (!$expectedP2Ok) $crucesDesdeConfig[$idx]['pareja_2'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                    $partidosFinalUsados[$partidoId] = true;
                }
            }
            
            // Si un partido de cuartos tiene resultados pero una pareja sigue null (grupo en BD con 0,0), rellenar con el ganador de la referencia (O1, O2, G1-8vos, etc.)
            $ganadoresOctavosPorNumero = [];
            $partidoIdToONumeroRelleno = [];
            if ($configuracionCruces->llave_8vos ?? null) {
                $llave8vos = json_decode($configuracionCruces->llave_8vos, true);
                if ($llave8vos && is_array($llave8vos)) {
                    $letrasZonas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P'];
                    $zonaALetra = [];
                    foreach ($zonas as $idx => $z) {
                        if (isset($letrasZonas[$idx])) $zonaALetra[$z] = $letrasZonas[$idx];
                    }
                    $gruposOctavos = DB::table('grupos')
                        ->where('torneo_id', $torneoId)
                        ->where('zona', 'octavos final')
                        ->whereNotNull('partido_id')
                        ->orderBy('partido_id')->orderBy('id')
                        ->get()->groupBy('partido_id');
                    foreach ($llave8vos as $idx => $partido8vos) {
                        $p1Ref = $partido8vos['pareja_1'] ?? null;
                        $p2Ref = $partido8vos['pareja_2'] ?? null;
                        if (!$p1Ref || !$p2Ref) continue;
                        $j1 = $j2 = null;
                        if (preg_match('/^([A-P])(\d+)$/i', (string) $p1Ref, $m)) {
                            $rk = strtoupper($m[0]);
                            if (!empty($mapaReferenciaBracket[$rk])) {
                                $pr = $mapaReferenciaBracket[$rk];
                                $j1 = TorneoGrupoPosicionesService::parejaKey($pr['jugador_1'], $pr['jugador_2']);
                            } else {
                                foreach ($zonaALetra as $zona => $letra) {
                                    if ($letra === $m[1] && isset($posicionesPorZona[$zona][(int) $m[2] - 1])) {
                                        $p = $posicionesPorZona[$zona][(int) $m[2] - 1];
                                        $j1 = TorneoGrupoPosicionesService::parejaKey(($p['jugador_1'] ?? 0), ($p['jugador_2'] ?? 0));
                                        break;
                                    }
                                }
                            }
                        }
                        if (preg_match('/^([A-P])(\d+)$/i', (string) $p2Ref, $m)) {
                            $rk = strtoupper($m[0]);
                            if (!empty($mapaReferenciaBracket[$rk])) {
                                $pr = $mapaReferenciaBracket[$rk];
                                $j2 = TorneoGrupoPosicionesService::parejaKey($pr['jugador_1'], $pr['jugador_2']);
                            } else {
                                foreach ($zonaALetra as $zona => $letra) {
                                    if ($letra === $m[1] && isset($posicionesPorZona[$zona][(int) $m[2] - 1])) {
                                        $p = $posicionesPorZona[$zona][(int) $m[2] - 1];
                                        $j2 = TorneoGrupoPosicionesService::parejaKey(($p['jugador_1'] ?? 0), ($p['jugador_2'] ?? 0));
                                        break;
                                    }
                                }
                            }
                        }
                        foreach ($gruposOctavos as $pid => $gruposP) {
                            if ($gruposP->count() < 2) continue;
                            $g1 = $gruposP[0]; $g2 = $gruposP[1];
                            $ref1 = strtoupper(trim($g1->referencia_config ?? ''));
                            $ref2 = strtoupper(trim($g2->referencia_config ?? ''));
                            $pr1 = strtoupper(trim((string) $p1Ref));
                            $pr2 = strtoupper(trim((string) $p2Ref));
                            $matchByRef = ($ref1 === $pr1 && $ref2 === $pr2) || ($ref1 === $pr2 && $ref2 === $pr1);
                            if ($matchByRef) {
                                $partidoIdToONumeroRelleno[$pid] = $idx + 1;
                                break;
                            }
                            if ($j1 && $j2) {
                                $k1 = TorneoGrupoPosicionesService::parejaKey($g1->jugador_1, $g1->jugador_2);
                                $k2 = TorneoGrupoPosicionesService::parejaKey($g2->jugador_1, $g2->jugador_2);
                                if (($k1 === $j1 && $k2 === $j2) || ($k1 === $j2 && $k2 === $j1)) {
                                    $partidoIdToONumeroRelleno[$pid] = $idx + 1;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            $partidoIdsOctavosOrden = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'octavos final')
                ->whereNotNull('partido_id')
                ->orderBy('partido_id')
                ->pluck('partido_id')->unique()->values();
            foreach ($partidoIdsOctavosOrden as $num => $pid) {
                $partidoO = $partidosObj[$pid] ?? null;
                if (!$partidoO) {
                    $partidoO = DB::table('partidos')->where('id', $pid)->first();
                }
                if ($partidoO) {
                    $gruposO = DB::table('grupos')->where('torneo_id', $torneoId)->where('partido_id', $pid)->orderBy('id')->get();
                    if ($gruposO->count() >= 2) {
                        $ganador = $this->determinarGanadorPartido($partidoO);
                        if ($ganador) {
                            $g1 = $gruposO[0]; $g2 = $gruposO[1];
                            $onum = $partidoIdToONumeroRelleno[$pid] ?? ($num + 1);
                            $ganadoresOctavosPorNumero[$onum] = [
                                'jugador_1' => $ganador == 1 ? $g1->jugador_1 : $g2->jugador_1,
                                'jugador_2' => $ganador == 1 ? $g1->jugador_2 : $g2->jugador_2
                            ];
                        }
                    }
                }
            }
            foreach ($crucesDesdeConfig as $idx => $c) {
                if (($c['ronda'] ?? '') !== 'cuartos') continue;
                $partido = $c['partido'] ?? null;
                if (!$partido || empty($c['partido_id'])) continue;
                $p = is_array($partido) ? (object)$partido : $partido;
                $tieneResultados = ((int)($p->pareja_1_set_1 ?? 0) > 0 || (int)($p->pareja_2_set_1 ?? 0) > 0 || (int)($p->pareja_1_set_2 ?? 0) > 0 || (int)($p->pareja_2_set_2 ?? 0) > 0);
                if (!$tieneResultados) continue;
                $ref1 = $c['referencia_1'] ?? '';
                $ref2 = $c['referencia_2'] ?? '';
                $pareja1Null = !isset($c['pareja_1']['jugador_1']) || (int)($c['pareja_1']['jugador_1'] ?? 0) === 0;
                $pareja2Null = !isset($c['pareja_2']['jugador_1']) || (int)($c['pareja_2']['jugador_1'] ?? 0) === 0;
                if ($pareja1Null && $ref1 && (preg_match('/^O(\d+)$/', $ref1, $m) || preg_match('/^G(\d+)-8vos$/', $ref1, $m) || preg_match('/^G(\d+)-octavos$/', $ref1, $m))) {
                    $n = (int)$m[1];
                    if (!empty($ganadoresOctavosPorNumero[$n])) {
                        $crucesDesdeConfig[$idx]['pareja_1'] = $ganadoresOctavosPorNumero[$n];
                    }
                }
                if ($pareja2Null && $ref2 && (preg_match('/^O(\d+)$/', $ref2, $m) || preg_match('/^G(\d+)-8vos$/', $ref2, $m) || preg_match('/^G(\d+)-octavos$/', $ref2, $m))) {
                    $n = (int)$m[1];
                    if (!empty($ganadoresOctavosPorNumero[$n])) {
                        $crucesDesdeConfig[$idx]['pareja_2'] = $ganadoresOctavosPorNumero[$n];
                    }
                }
            }
            
            // Combinar: 16avos y octavos desde BD (tienen todos los partidos incl. G1-16avos vs A1),
            // cuartos/semifinal/final desde configuración (tienen estructura y placeholders correctos)
            // Respetar tiene_16avos_final: si la config dice que no hay 16avos, no incluir cruces de 16avos
            $tiene16avosConfig = !empty($configuracionCruces->tiene_16avos_final);
            $cruces16avosOctavosBD = array_filter($cruces, function ($c) use ($tiene16avosConfig) {
                $r = $c['ronda'] ?? '';
                if ($r === '16avos' && !$tiene16avosConfig) {
                    return false;
                }
                return $r === '16avos' || $r === 'octavos';
            });
            $crucesCuartosSemiFinalConfig = array_filter($crucesDesdeConfig, function ($c) {
                $r = $c['ronda'] ?? '';
                return in_array($r, ['cuartos', 'semifinales', 'final']);
            });
            $crucesV2Debug['pasos']['solo_16avos_octavos_bd'] = $this->serializarCrucesParaDebug(array_values($cruces16avosOctavosBD));
            $crucesV2Debug['pasos']['cuartos_semifinal_final_desde_config'] = $this->serializarCrucesParaDebug(array_values($crucesCuartosSemiFinalConfig));
            $cruces = array_merge(array_values($cruces16avosOctavosBD), array_values($crucesCuartosSemiFinalConfig));
            $crucesV2Debug['pasos']['resumen_merge'] = [
                'n_16avos_octavos_bd' => count($cruces16avosOctavosBD),
                'n_cuartos_semifinal_final' => count($crucesCuartosSemiFinalConfig),
                'n_total' => count($cruces),
            ];
            
            \Log::info('Cruces armados desde configuración: ' . count($cruces));
        }

        $this->enriquecerCrucesSetsVisual($cruces, (int) $torneoId);

        // Resultados guardados para hidratar inputs (orden visual = columnas pareja_1 / pareja_2)
        $resultadosGuardados = [];
        foreach ($cruces as $cruce) {
            $partido = $cruce['partido'] ?? null;
            if (!$partido || !isset($cruce['pareja_1']['jugador_1'], $cruce['pareja_2']['jugador_1'])) {
                continue;
            }
            $p = is_array($partido) ? (object) $partido : $partido;
            $sv = $cruce['sets_visual'] ?? null;
            $s1 = $sv['pareja_1_set_1'] ?? ($p->pareja_1_set_1 ?? 0);
            $s2 = $sv['pareja_1_set_2'] ?? ($p->pareja_1_set_2 ?? 0);
            $s3 = $sv['pareja_1_set_3'] ?? ($p->pareja_1_set_3 ?? 0);
            $t1 = $sv['pareja_2_set_1'] ?? ($p->pareja_2_set_1 ?? 0);
            $t2 = $sv['pareja_2_set_2'] ?? ($p->pareja_2_set_2 ?? 0);
            $t3 = $sv['pareja_2_set_3'] ?? ($p->pareja_2_set_3 ?? 0);
            if (($p->pareja_1_set_1 ?? 0) > 0 || ($p->pareja_2_set_1 ?? 0) > 0 ||
                ($p->pareja_1_set_2 ?? 0) > 0 || ($p->pareja_2_set_2 ?? 0) > 0 ||
                ($p->pareja_1_set_3 ?? 0) > 0 || ($p->pareja_2_set_3 ?? 0) > 0) {
                $resultadosGuardados[] = [
                    'partido_id' => $cruce['partido_id'],
                    'cruce_id' => $cruce['id'],
                    'ronda' => $cruce['ronda'],
                    'pareja_1_jugador_1' => $cruce['pareja_1']['jugador_1'],
                    'pareja_1_jugador_2' => $cruce['pareja_1']['jugador_2'],
                    'pareja_2_jugador_1' => $cruce['pareja_2']['jugador_1'],
                    'pareja_2_jugador_2' => $cruce['pareja_2']['jugador_2'],
                    'pareja_1_set_1' => $s1,
                    'pareja_1_set_2' => $s2,
                    'pareja_1_set_3' => $s3,
                    'pareja_2_set_1' => $t1,
                    'pareja_2_set_2' => $t2,
                    'pareja_2_set_3' => $t3,
                ];
            }
        }

        // Enriquecer todos los cruces con dia/horario desde grupos (BD)
        $partidoIds = array_unique(array_filter(array_column($cruces, 'partido_id')));
        $horariosPorPartido = [];
        if (count($partidoIds) > 0) {
            $gruposHorario = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->whereIn('partido_id', $partidoIds)
                ->whereNotNull('partido_id')
                ->select('partido_id', 'fecha', 'horario')
                ->orderBy('partido_id')
                ->orderBy('id')
                ->get();
            foreach ($gruposHorario as $g) {
                if (!isset($horariosPorPartido[$g->partido_id])) {
                    $horariosPorPartido[$g->partido_id] = ['fecha' => $g->fecha, 'horario' => $g->horario];
                }
            }
        }
        foreach ($cruces as &$cruce) {
            $pid = $cruce['partido_id'] ?? null;
            if ($pid && isset($horariosPorPartido[$pid])) {
                $cruce['dia'] = $horariosPorPartido[$pid]['fecha'];
                $cruce['horario'] = $horariosPorPartido[$pid]['horario'];
            }
        }
        unset($cruce);

        // Respetar tiene_16avos_final: si la config dice que no hay 16avos, no mostrar esa columna
        $configPara16avos = $configuracionCruces ?? $this->getConfiguracionCruces($torneoId, $totalParejasClasificadas);
        $cruces16avos = $this->obtenerCrucesPorZona($cruces, '16avos final');
        if ($configPara16avos && empty($configPara16avos->tiene_16avos_final)) {
            $cruces16avos = [];
        }
        $crucesOctavos = $this->obtenerCrucesPorZona($cruces, 'octavos final');
        $crucesCuartos = $this->obtenerCrucesPorZona($cruces, 'cuartos final');
        $crucesSemifinales = $this->obtenerCrucesPorZona($cruces, 'semifinal');
        $crucesFinales = $this->obtenerCrucesPorZona($cruces, 'final');

        $crucesV2Debug['crucesFinales'] = $this->serializarCrucesParaDebug($cruces);
        if (!$configuracionCruces) {
            $crucesV2Debug['nota'] = 'Sin configuración de cruces para esta cantidad de parejas; cruces solo desde partidos en BD.';
        }
        
        \Log::info('Cruces de cuartos filtrados: ' . count($crucesCuartos));
        \Log::info('Detalle cruces de cuartos: ' . json_encode($crucesCuartos));

        //return $crucesOctavos;

        return View('bahia_padel.admin.torneo.cruces_puntuable_v2')
                    ->with('torneo', $torneo)
                    ->with('jugadores', $jugadores)
                    ->with('cruces', $cruces)
                    ->with('cruces16avos', $cruces16avos)
                    ->with('crucesOctavos', $crucesOctavos)
                    ->with('crucesCuartos', $crucesCuartos)
                    ->with('crucesSemifinales', $crucesSemifinales)
                    ->with('crucesFinales', $crucesFinales)
                    ->with('resultadosGuardados', $resultadosGuardados)
                    ->with('tiene16avos', !empty($configuracionCruces) && !empty($configuracionCruces->tiene_16avos_final))
                    ->with('crucesV2Debug', $crucesV2Debug);
    }

    /**
     * Marca el torneo puntuable como "en progreso" (estado = 2)
     * y crea todos los partidos de cruces (16avos/8vos/4tos/semis/final) en base a la configuración.
     *
     * Importante: los partidos se crean con jugadores = 0 y se guarda en cada fila de grupo
     * la referencia de configuración (A1, H2, G1-8vos, G1-4tos, etc.) en el campo referencia_config.
     * Más adelante, a medida que avancen los cruces, se irán reemplazando estas referencias por jugadores reales.
     */
    public function comenzarTorneoPuntuable(Request $request)
    {
        $torneoId = $request->get('torneo_id');

        if (!$torneoId) {
            return response()->json([
                'success' => false,
                'message' => 'torneo_id requerido'
            ], 400);
        }

        try {
            // Verificar torneo
            $torneo = DB::table('torneos')->where('id', $torneoId)->first();
            if (!$torneo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Torneo no encontrado'
                ], 404);
            }

            // Solo aplica a torneos puntuables
            $tipoTorneoFormato = $torneo->tipo_torneo_formato ?? 'puntuable';
            if ($tipoTorneoFormato !== 'puntuable') {
                return response()->json([
                    'success' => false,
                    'message' => 'El torneo no es de tipo puntuable'
                ], 400);
            }

            DB::beginTransaction();

            // Si se envía una configuración explícita desde el armado, persistirla en el torneo
            if ($request->exists('config_cruces_puntuable_id')) {
                DB::table('torneos')
                    ->where('id', $torneoId)
                    ->update([
                        'config_cruces_puntuable_id' => $request->input('config_cruces_puntuable_id') ?: null
                    ]);

                // Refrescar torneo para usar el valor recién persistido
                $torneo = DB::table('torneos')->where('id', $torneoId)->first();
            }

            // Actualizar estado del torneo a "en progreso"
            DB::table('torneos')
                ->where('id', $torneoId)
                ->update(['estado' => 2]);

            // Buscar configuración: primero del campo config_cruces_puntuable_id (selección explícita)
            $config = null;
            if (!empty($torneo->config_cruces_puntuable_id)) {
                $config = DB::table('configuracion_cruces_puntuables')
                    ->where('id', $torneo->config_cruces_puntuable_id)
                    ->first();
            }
            
            // Fallback: contar parejas desde grupos y buscar config con cantidad_parejas que coincida.
            // Así se respeta tiene_16avos_final de la config correcta (ej: 21 parejas sin 16avos).
            if (!$config) {
                $cantidadParejas = $this->contarParejasDesdeGrupos($torneoId);
                $config = $this->getConfiguracionCruces($torneoId, $cantidadParejas);
            }

            if (!$config) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Debes seleccionar una configuración de cruces para el torneo puntuable antes de comenzar.'
                ], 400);
            }

            // Intentar crear todos los partidos de 16avos/8vos/4tos/semis/final.
            // El método interno se encarga de no duplicar si ya existen para ese torneo/zona/referencias.
            $this->crearPartidosEliminatoriosDesdeConfiguracion($torneoId, $config);

            // Incrementar versión del torneo para notificar a vistas TV (si el método existe)
            if (class_exists(\App\Torneo::class) && method_exists(\App\Torneo::class, 'incrementarVersion')) {
                \App\Torneo::incrementarVersion($torneoId);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Torneo comenzado correctamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error en comenzarTorneoPuntuable: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            $response = [
                'success' => false,
                'message' => 'Error al comenzar el torneo',
                'error_detail' => $e->getMessage()
            ];
            return response()->json($response, 500);
        }
    }
    
    /**
     * GET: Participantes del torneo puntuable (jugadores que aparecen en grupos) y referencias de puntos.
     * Calcula automáticamente posición (campeón, sub, 3º/4º, cuartos, octavos, 16avos, no clasificados) según resultados del cuadro
     * y ordena la lista por esa posición.
     */
    public function getParticipantesTorneoPuntuable(Request $request) {
        $torneoId = $request->get('torneo_id');
        if (!$torneoId) {
            return response()->json(['success' => false, 'message' => 'torneo_id requerido'], 400);
        }
        // La tabla grupos tiene jugador_1 y jugador_2 por fila (una pareja por fila)
        $ids = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->selectRaw('jugador_1 as id')
            ->unionAll(DB::table('grupos')->where('torneo_id', $torneoId)->selectRaw('jugador_2 as id'))
            ->pluck('id')
            ->filter(function ($id) { return $id > 0; })
            ->unique()
            ->values();
        $posiciones = $this->calcularPosicionesDesdeCruces($torneoId);
        $referencias = DB::table('puntos_ranking_referencia')->orderBy('orden')->get(['codigo', 'nombre', 'puntos']);
        $refMap = $referencias->keyBy('codigo');
        $ordenPosicion = ['campeon' => 1, 'subcampeon' => 2, 'tercero_cuarto' => 3, 'cuartos' => 4, 'octavos' => 5, '16avos' => 6, 'no_clasificados' => 7];
        $jugadores = DB::table('jugadores')->whereIn('id', $ids)->get(['id', 'nombre', 'apellido']);
        foreach ($jugadores as $j) {
            $codigo = $posiciones[$j->id] ?? 'no_clasificados';
            $ref = $refMap->get($codigo);
            $j->referencia_codigo = $codigo;
            $j->puntos = $ref ? (int) $ref->puntos : 5;
            $j->orden_posicion = $ordenPosicion[$codigo] ?? 99;
        }
        $jugadores = $jugadores->sortBy(function ($j) {
            return sprintf('%02d_%s %s', $j->orden_posicion, $j->nombre ?? '', $j->apellido ?? '');
        })->values()->all();
        return response()->json(['success' => true, 'jugadores' => $jugadores, 'referencias' => $referencias]);
    }

    /**
     * Calcula la posición de cada jugador en el torneo según resultados del cuadro eliminatorio.
     * Retorna [ jugador_id => referencia_codigo ] (campeon, subcampeon, tercero_cuarto, cuartos, octavos, 16avos, no_clasificados).
     */
    private function calcularPosicionesDesdeCruces($torneoId) {
        $posiciones = [];
        $zonasOrden = [
            ['zona' => 'final',           'ganador_codigo' => 'campeon',    'perdedor_codigo' => 'subcampeon'],
            ['zona' => 'semifinal',       'ganador_codigo' => null,         'perdedor_codigo' => 'tercero_cuarto'],
            ['zona' => 'cuartos final',   'ganador_codigo' => null,         'perdedor_codigo' => 'cuartos'],
            ['zona' => 'octavos final',   'ganador_codigo' => null,         'perdedor_codigo' => 'octavos'],
            ['zona' => '16avos final',   'ganador_codigo' => null,         'perdedor_codigo' => '16avos'],
        ];
        foreach ($zonasOrden as $config) {
            $zona = $config['zona'];
            $gruposZona = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', $zona)
                ->whereNotNull('partido_id')
                ->where('partido_id', '>', 0)
                ->orderBy('partido_id')
                ->orderBy('id')
                ->get();
            $partidoIds = $gruposZona->pluck('partido_id')->unique()->filter()->values();
            $partidos = $partidoIds->isEmpty() ? collect() : DB::table('partidos')->whereIn('id', $partidoIds)->get()->keyBy('id');
            foreach ($partidoIds as $pid) {
                $partido = $partidos->get($pid);
                if (!$partido || !$this->partidoTieneResultado($partido)) continue;
                $ganador = $this->determinarGanadorPartido($partido);
                if ($ganador === null) continue;
                $perdedor = $ganador === 1 ? 2 : 1;
                $gruposPartido = $gruposZona->where('partido_id', $pid)->sortBy('id')->values();
                if ($gruposPartido->count() < 2) continue;
                $gGanador = $gruposPartido[$ganador - 1];
                $gPerdedor = $gruposPartido[$perdedor - 1];
                $idsGanador = [(int) $gGanador->jugador_1, (int) $gGanador->jugador_2];
                $idsPerdedor = [(int) $gPerdedor->jugador_1, (int) $gPerdedor->jugador_2];
                foreach ($idsGanador as $id) {
                    if ($id > 0 && !isset($posiciones[$id]) && $config['ganador_codigo']) $posiciones[$id] = $config['ganador_codigo'];
                }
                foreach ($idsPerdedor as $id) {
                    if ($id > 0 && !isset($posiciones[$id]) && $config['perdedor_codigo']) $posiciones[$id] = $config['perdedor_codigo'];
                }
            }
        }
        return $posiciones;
    }

    /** True si el partido tiene al menos un set con resultado cargado. */
    private function partidoTieneResultado($partido) {
        if (isset($partido->pareja_1_set_1) && ($partido->pareja_1_set_1 > 0 || (isset($partido->pareja_2_set_1) && $partido->pareja_2_set_1 > 0))) return true;
        if (isset($partido->pareja_1_set_2) && ($partido->pareja_1_set_2 > 0 || (isset($partido->pareja_2_set_2) && $partido->pareja_2_set_2 > 0))) return true;
        if (isset($partido->pareja_1_set_3) && ($partido->pareja_1_set_3 > 0 || (isset($partido->pareja_2_set_3) && $partido->pareja_2_set_3 > 0))) return true;
        return false;
    }

    /**
     * POST: Guardar puntos de ranking por torneo (ranking_puntos + actualizar ranking_totales).
     */
    public function guardarPuntosRankingTorneo(Request $request) {
        $torneoId = $request->input('torneo_id');
        $items = $request->input('items', []);
        if (!$torneoId || !is_array($items)) {
            return response()->json(['success' => false, 'message' => 'Datos inválidos'], 400);
        }
        $torneo = DB::table('torneos')->where('id', $torneoId)->first();
        if (!$torneo) {
            return response()->json(['success' => false, 'message' => 'Torneo no encontrado'], 404);
        }
        $categoria = isset($torneo->categoria) ? (int) $torneo->categoria : 6;
        $tipo = isset($torneo->tipo) && in_array($torneo->tipo, ['masculino', 'femenino', 'mixto'], true) ? $torneo->tipo : 'masculino';
        $fecha = isset($torneo->fecha_fin) && $torneo->fecha_fin
            ? $torneo->fecha_fin
            : (isset($torneo->fecha_inicio) ? $torneo->fecha_inicio : now()->format('Y-m-d'));
        $temporada = (int) date('Y', strtotime($fecha));
        $afectados = [];
        foreach ($items as $item) {
            $jugadorId = (int) ($item['jugador_id'] ?? 0);
            $puntos = (int) ($item['puntos'] ?? 0);
            $referenciaCodigo = (string) ($item['referencia_codigo'] ?? 'no_clasificados');
            if ($jugadorId <= 0) continue;
            $now = now();
            DB::table('ranking_puntos')->updateOrInsert(
                ['jugador_id' => $jugadorId, 'torneo_id' => $torneoId],
                [
                    'categoria' => $categoria,
                    'tipo' => $tipo,
                    'puntos' => $puntos,
                    'referencia_codigo' => $referenciaCodigo,
                    'temporada' => $temporada,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
            if (!isset($afectados[$jugadorId])) $afectados[$jugadorId] = ['categoria' => $categoria, 'temporada' => $temporada, 'tipo' => $tipo];
        }
        foreach ($afectados as $jugadorId => $par) {
            $total = DB::table('ranking_puntos')
                ->where('jugador_id', $jugadorId)
                ->where('categoria', $par['categoria'])
                ->where('temporada', $par['temporada'])
                ->where('tipo', $par['tipo'])
                ->sum('puntos');
            $now = now();
            DB::table('ranking_totales')->updateOrInsert(
                ['jugador_id' => $jugadorId, 'categoria' => $par['categoria'], 'temporada' => $par['temporada'], 'tipo' => $par['tipo']],
                ['puntos_totales' => $total, 'updated_at' => $now, 'created_at' => $now]
            );
        }

        // Asignar puntos al ranking = torneo finalizado a efectos operativos (estado 3)
        if (!empty($afectados)) {
            DB::table('torneos')->where('id', $torneoId)->update([
                'estado' => 3,
                'updated_at' => now(),
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Puntos guardados en el ranking correctamente.']);
    }

    /**
     * Obtiene los cruces filtrados por zona/ronda
     * 
     * @param array $cruces Array de cruces
     * @param string $zona Zona a filtrar ('octavos final', 'cuartos final', 'semifinal', 'final')
     * @return array Array de cruces filtrados por la zona especificada
     */
    private function obtenerCrucesPorZona($cruces, $zona) {
        // Mapear zona a ronda(s) - 16avos y octavos en columnas separadas
        $rondaMap = [
            '16avos final' => ['16avos'],
            'octavos final' => ['octavos'],
            'cuartos final' => ['cuartos'],
            'semifinal' => ['semifinales'],
            'final' => ['final']
        ];
        
        $rondas = $rondaMap[$zona] ?? null;
        
        if (!$rondas) {
            \Log::warning('Zona no reconocida en obtenerCrucesPorZona: ' . $zona);
            return [];
        }
        
        $rondas = (array) $rondas;
        // Filtrar cruces por ronda(s)
        $crucesFiltrados = array_filter($cruces, function($cruce) use ($rondas) {
            return isset($cruce['ronda']) && in_array($cruce['ronda'], $rondas);
        });
        // Ordenar: 16avos antes que octavos cuando hay ambos
        usort($crucesFiltrados, function($a, $b) use ($rondas) {
            $pa = array_search($a['ronda'], $rondas);
            $pb = array_search($b['ronda'], $rondas);
            if ($pa !== $pb) return $pa - $pb;
            return ($a['id'] ?? '') <=> ($b['id'] ?? '');
        });
        return array_values($crucesFiltrados);
    }

    /**
     * Elimina grupos y partidos de octavos final / 16avos final que no correspondan a la configuración.
     * La config define exactamente N partidos (ej. 8 en llave_8vos); si en BD hay más (ej. 10), se borran los excedentes.
     * IMPORTANTE: Preservar partidos de octavos con ref G1-16avos/GANADOR_DA* (ganador 16avos vs zona) que no están en crucesDesdeConfig
     * porque generarCrucesDesdeConfiguracion no los incluye (G1-16avos no se resuelve a jugadores aún).
     */
    private function limpiarGruposEliminatoriosExcedentes($torneoId, $crucesDesdeConfig) {
        $validOctavos = [];
        $valid16avos = [];
        foreach ($crucesDesdeConfig as $c) {
            $pid = $c['partido_id'] ?? null;
            if (!$pid) continue;
            $ronda = $c['ronda'] ?? null;
            if ($ronda === 'octavos') {
                $validOctavos[] = $pid;
            } elseif ($ronda === '16avos') {
                $valid16avos[] = $pid;
            }
        }
        // Preservar partidos octavos con ref ganador 16avos (G1-16avos, DA1, GANADOR_DA1, etc.) - no están en crucesDesdeConfig
        $gruposOctavosGanador16avos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'octavos final')
            ->whereNotNull('partido_id')
            ->where(function ($q) {
                $q->where('referencia_config', 'like', 'G%-16avos')
                  ->orWhere('referencia_config', 'like', 'GANADOR_DA%')
                  ->orWhere('referencia_config', 'like', 'DA%');
            })
            ->pluck('partido_id')
            ->unique()
            ->values()
            ->all();
        // Preservar TODOS los octavos que tengan referencia_config (O1, O2, A1, B1, etc.) - evitar borrar refs al arrancar cruces
        $gruposOctavosConRef = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'octavos final')
            ->whereNotNull('partido_id')
            ->whereNotNull('referencia_config')
            ->where('referencia_config', '!=', '')
            ->pluck('partido_id')
            ->unique()
            ->values()
            ->all();
        $validOctavos = array_unique(array_merge(array_filter($validOctavos), $gruposOctavosGanador16avos, $gruposOctavosConRef));
        $valid16avos = array_unique(array_filter($valid16avos));

        foreach (['octavos final' => $validOctavos, '16avos final' => $valid16avos] as $zona => $validIds) {
            if (count($validIds) === 0) {
                continue;
            }
            $gruposZona = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', $zona)
                ->whereNotNull('partido_id')
                ->get();
            $partidoIdsEnBD = $gruposZona->pluck('partido_id')->unique()->values()->all();
            $excedentes = array_diff($partidoIdsEnBD, $validIds);
            if (empty($excedentes)) {
                continue;
            }
            \Log::info('Limpiando ' . $zona . ': partidos válidos según config=' . count($validIds) . ', en BD=' . count($partidoIdsEnBD) . ', eliminando partido_ids=' . implode(',', $excedentes));
            DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', $zona)
                ->whereIn('partido_id', $excedentes)
                ->delete();
            // Borrar partidos que ya no tengan ningún grupo
            foreach ($excedentes as $pid) {
                $tieneGrupos = DB::table('grupos')->where('partido_id', $pid)->exists();
                if (!$tieneGrupos) {
                    DB::table('partidos')->where('id', $pid)->delete();
                    \Log::info('Partido eliminado (sin grupos): id=' . $pid);
                }
            }
        }
    }

    /**
     * Guarda el resultado de un partido para torneo puntuable
     */
    public function guardarResultadoPartidoPuntuable(Request $request) {
        try {
            \Log::info('=== INICIO guardarResultadoPartidoPuntuable ===');
            \Log::info('Request completo: ' . json_encode($request->all()));
            
            $partidoId = $request->partido_id;
            $torneoId = $request->torneo_id;
            $ronda = $request->ronda;
            
            \Log::info('Partido ID recibido: ' . $partidoId);
            \Log::info('Torneo ID recibido: ' . $torneoId);
            \Log::info('Ronda recibida: ' . $ronda);
            
            // Validar que partido_id existe
            if (!$partidoId) {
                \Log::error('Partido ID inválido o vacío');
                return response()->json([
                    'success' => false,
                    'message' => 'Partido ID inválido'
                ]);
            }
            
            // Buscar el partido
            $partido = Partido::find($partidoId);
            
            if (!$partido) {
                \Log::error('Partido no encontrado con ID: ' . $partidoId);
                return response()->json([
                    'success' => false,
                    'message' => 'Partido no encontrado'
                ]);
            }
            
            \Log::info('Partido encontrado: ID ' . $partido->id);
            
            // Obtener los valores de los sets
            $pareja1Set1 = $request->pareja_1_set_1 ?? 0;
            $pareja1Set2 = $request->pareja_1_set_2 ?? 0;
            $pareja1Set3 = $request->pareja_1_set_3 ?? 0;
            $pareja2Set1 = $request->pareja_2_set_1 ?? 0;
            $pareja2Set2 = $request->pareja_2_set_2 ?? 0;
            $pareja2Set3 = $request->pareja_2_set_3 ?? 0;
            
            \Log::info('Sets recibidos - Pareja 1: ' . $pareja1Set1 . '/' . $pareja1Set2 . '/' . $pareja1Set3);
            \Log::info('Sets recibidos - Pareja 2: ' . $pareja2Set1 . '/' . $pareja2Set2 . '/' . $pareja2Set3);
            
            // Obtener información de las parejas para identificar el orden
            $pareja1Jugador1 = $request->pareja_1_jugador_1 ?? null;
            $pareja1Jugador2 = $request->pareja_1_jugador_2 ?? null;
            $pareja2Jugador1 = $request->pareja_2_jugador_1 ?? null;
            $pareja2Jugador2 = $request->pareja_2_jugador_2 ?? null;
            
            \Log::info('Jugadores - Pareja 1: ' . $pareja1Jugador1 . '/' . $pareja1Jugador2);
            \Log::info('Jugadores - Pareja 2: ' . $pareja2Jugador1 . '/' . $pareja2Jugador2);
            
            // Guardar el resultado usando el método separado
            \Log::info('Llamando a guardarResultadoPartido...');
            $this->guardarResultadoPartido($partido, $torneoId, $pareja1Set1, $pareja1Set2, $pareja1Set3, 
                                          $pareja2Set1, $pareja2Set2, $pareja2Set3,
                                          $pareja1Jugador1, $pareja1Jugador2, $pareja2Jugador1, $pareja2Jugador2);
            
            // Refrescar el partido desde la base de datos para obtener los valores actualizados
            $partido->refresh();
            
            \Log::info('Resultado guardado - Partido ID: ' . $partidoId . ', Ronda: ' . $ronda . 
                      ', Sets P1: ' . $partido->pareja_1_set_1 . '/' . $partido->pareja_1_set_2 . '/' . $partido->pareja_1_set_3 . 
                      ', Sets P2: ' . $partido->pareja_2_set_1 . '/' . $partido->pareja_2_set_2 . '/' . $partido->pareja_2_set_3);
            
            // Propagar ganador a la ronda siguiente en `grupos` (refs CU1, O1, S1, etc. vía config).
            // Misma lógica que guardarResultadoCrucePuntuable: primero *DesdeConfiguracion*, legacy solo si hace falta.
            $partidoIdSiguiente = null;
            if ($ronda === '16avos') {
                $partidoIdSiguiente = $this->asignarGanador16avosAOctavos($torneoId, $partido);
            } elseif ($ronda === 'octavos') {
                $partidoIdSiguiente = $this->crearCuartosDesdeConfiguracionYOctavos($torneoId, $partido);
            } elseif ($ronda === 'cuartos') {
                \Log::info('[propagar cuartos->semis] ANTES: torneo_id=' . $torneoId . ' partido_cuartos_id=' . $partido->id);
                $partidoIdSiguiente = $this->crearSemifinalesDesdeConfiguracionYCuartos($torneoId, $partido);
                \Log::info('[propagar cuartos->semis] crearSemifinalesDesdeConfiguracionYCuartos retornó partido_id_siguiente=' . json_encode($partidoIdSiguiente));
                if ($partidoIdSiguiente) {
                    $verif = DB::table('grupos')
                        ->where('torneo_id', $torneoId)
                        ->where('partido_id', $partidoIdSiguiente)
                        ->where('zona', 'like', 'semifinal%')
                        ->orderBy('id')
                        ->get(['id', 'partido_id', 'referencia_config', 'jugador_1', 'jugador_2', 'zona']);
                    \Log::info('[propagar cuartos->semis] VERIF BD tras propagación (grupos semifinal partido_id=' . $partidoIdSiguiente . '): ' . json_encode($verif->all()));
                }
                if (!$partidoIdSiguiente) {
                    \Log::warning('[propagar cuartos->semis] Sin partido_id de config; intentando crearSemifinalesSiEsNecesario (legacy)');
                    $this->crearSemifinalesSiEsNecesario($torneoId);
                }
            } elseif ($ronda === 'semifinales') {
                $partidoIdSiguiente = $this->crearFinalDesdeConfiguracionYSemifinales($torneoId, $partido);
                if (!$partidoIdSiguiente) {
                    $this->crearFinalSiEsNecesario($torneoId);
                }
            }

            // Preparar datos del ganador para actualizar la llave siguiente en el frontend (sin recargar)
            $ganadorLlave = $this->obtenerGanadorLlaveParaFrontend($partido, $ronda, $request->cruce_id ?? '', $torneoId);
            if ($ganadorLlave && $partidoIdSiguiente) {
                $ganadorLlave['partido_id_siguiente'] = $partidoIdSiguiente;
            }
            if ($ganadorLlave) {
                \Log::info('ganador_llave para frontend: ronda=' . $ronda . ', ronda_siguiente=' . ($ganadorLlave['ronda_siguiente'] ?? '') . ', refs=' . json_encode($ganadorLlave['refs'] ?? []));
            }

            \Log::info('=== FIN guardarResultadoPartidoPuntuable (éxito) ===');
            
            // Incrementar versión del torneo para notificar a vistas TV
            \App\Torneo::incrementarVersion($torneoId);
            
            $respuesta = [
                'success' => true,
                'message' => 'Resultado guardado correctamente',
                'partido_id' => $partido->id
            ];
            if ($ganadorLlave) {
                $respuesta['ganador_llave'] = $ganadorLlave;
            }
            return response()->json($respuesta);
            
        } catch (\Exception $e) {
            \Log::error('=== ERROR en guardarResultadoPartidoPuntuable ===');
            \Log::error('Error al guardar resultado del partido: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            \Log::error('==================================================');
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el resultado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crea la siguiente ronda cuando se guarda un resultado de cruce desde cargar_resultados (HomeController).
     * Para 16avos -> asigna ganador a octavos; para octavos -> crea cuartos.
     */
    public function crearSiguienteRondaDesdeCruce($torneoId, $partido) {
        $grupo = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('partido_id', $partido->id)
            ->first();
        if (!$grupo) {
            return;
        }
        $zona = $grupo->zona;
        $zonaBase = (strpos($zona, '|') !== false) ? explode('|', $zona)[0] : $zona;
        if ($zonaBase === '16avos final' || strpos($zona, '16avos final') === 0) {
            $this->asignarGanador16avosAOctavos($torneoId, $partido);
        } else if ($zonaBase === 'octavos final' || strpos($zona, 'octavos final') === 0) {
            $this->crearCuartosDesdeConfiguracionYOctavos($torneoId, $partido);
        }
    }

    /**
     * Obtiene los datos del ganador del partido para que el frontend actualice la llave siguiente sin recargar.
     * Retorna refs (O1, G1-8vos, etc.), ronda_siguiente, jugadores y datos para mostrar (nombre, foto).
     */
    private function obtenerGanadorLlaveParaFrontend($partido, $ronda, $cruceId, $torneoId = null) {
        $ganador = $this->determinarGanadorPartido($partido);
        if (!$ganador) {
            return null;
        }
        $gruposPartido = DB::table('grupos')
            ->where('partido_id', $partido->id)
            ->orderBy('id')
            ->get();
        if ($gruposPartido->count() < 2) {
            return null;
        }
        $g1 = $gruposPartido[0];
        $g2 = $gruposPartido[1];
        $jugador1 = ($ganador == 1) ? $g1->jugador_1 : $g2->jugador_1;
        $jugador2 = ($ganador == 1) ? $g1->jugador_2 : $g2->jugador_2;
        if ($torneoId === null) {
            $torneoId = $g1->torneo_id ?? null;
        }

        // Referencias que este ganador llena en la llave siguiente (para que el frontend encuentre el slot)
        // Importante: aquí incluimos TODAS las variantes que se usan en la configuración
        // para que coincidan con los data-llave-ref1/data-llave-ref2 de la vista.
        $refs = [];
        if ($ronda === '16avos') {
            if ($torneoId) {
                $numeroDA = $this->obtenerNumeroDA16avos($torneoId, $partido);
                if ($numeroDA > 0) {
                    $refs = ['DA' . $numeroDA];
                }
            }
        } elseif ($ronda === 'octavos') {
            $torneoId = $gruposPartido[0]->torneo_id ?? null;
            if ($torneoId) {
                $n = $this->obtenerNumeroOctavos($torneoId, $partido);
                if ($n > 0) {
                    $refs = ['O' . $n, 'G' . $n . '-8vos', 'G' . $n . '-octavos'];
                }
            }
        } elseif ($ronda === 'cuartos') {
            $torneoId = $gruposPartido[0]->torneo_id ?? null;
            if ($torneoId) {
                $n = $this->obtenerNumeroCuartos($torneoId, $partido);
                if ($n > 0) {
                    $refs = ['CU' . $n];
                }
            }
        } elseif ($ronda === 'semifinales') {
            $torneoId = $gruposPartido[0]->torneo_id ?? null;
            if ($torneoId) {
                $n = $this->obtenerNumeroSemifinal($torneoId, $partido);
                if ($n > 0) {
                    $refs = ['G' . $n . '-2tos', 'G' . $n . '-semis', 'G' . $n . '-semifinal', 'S' . $n];
                }
            }
        } elseif (preg_match('/^final_(\d+)$/i', trim($cruceId), $m)) {
            // Ganador de la final: pueden existir varias etiquetas en futuras vistas,
            // pero para el cuadro actual no necesita llenar otra llave.
            $refs = ['Ganador Final', 'Final'];
        }
        if (empty($refs)) {
            return null;
        }

        $rondaSiguiente = null;
        if ($ronda === '16avos') {
            $rondaSiguiente = 'octavos';
        } elseif ($ronda === 'octavos') {
            $rondaSiguiente = 'cuartos';
        } elseif ($ronda === 'cuartos') {
            $rondaSiguiente = 'semifinales';
        } elseif ($ronda === 'semifinales') {
            $rondaSiguiente = 'final';
        } elseif ($ronda === 'final') {
            return null;
        }

        $jugadores = DB::table('jugadores')
            ->whereIn('id', [$jugador1, $jugador2])
            ->get()
            ->keyBy('id');
        $j1 = $jugadores->get($jugador1);
        $j2 = $jugadores->get($jugador2);
        $foto1Path = ($j1 && !empty($j1->foto)) ? $j1->foto : 'images/jugador_img.png';
        $foto2Path = ($j2 && !empty($j2->foto)) ? $j2->foto : 'images/jugador_img.png';

        return [
            'refs' => $refs,
            'ronda_siguiente' => $rondaSiguiente,
            'jugador_1' => (int)$jugador1,
            'jugador_2' => (int)$jugador2,
            'nombre1' => $j1 ? trim(($j1->nombre ?? '') . ' ' . ($j1->apellido ?? '')) : '',
            'nombre2' => $j2 ? trim(($j2->nombre ?? '') . ' ' . ($j2->apellido ?? '')) : '',
            'foto1' => asset($foto1Path),
            'foto2' => asset($foto2Path),
        ];
    }

    /**
     * Misma pareja (dobles): compara los dos jugadores como conjunto (el orden en grupos puede ser 188/503 y en el form 503/188).
     */
    private function mismaParejaQueGrupo($grupo, $j1, $j2): bool
    {
        $ga = [(int) ($grupo->jugador_1 ?? 0), (int) ($grupo->jugador_2 ?? 0)];
        $jb = [(int) ($j1 ?? 0), (int) ($j2 ?? 0)];
        sort($ga);
        sort($jb);
        return $ga[0] === $jb[0] && $ga[1] === $jb[1];
    }

    /**
     * En `partidos`, pareja_1_* corresponde al grupo físico índice 0 y pareja_2_* al índice 1 (orden `grupos.id`).
     * La tarjeta muestra pareja izquierda/derecha según la llave; puede no coincidir con ese orden.
     * No hay “inversión”: se eligen los triples de columnas DB del mismo índice de grupo que cada pareja del cruce (por IDs).
     *
     * @return array<string,int> claves pareja_1_set_1 … pareja_2_set_3 (orden visual del cruce)
     */
    private function obtenerSetsPartidoParaOrdenVisual($partido, array $pareja1Cruce, array $pareja2Cruce, int $torneoId, int $partidoId): array
    {
        $p = is_array($partido) ? (object) $partido : $partido;
        $tripleDesdeSlotDb = function (int $slot) use ($p): array {
            if ($slot === 0) {
                return [
                    (int) ($p->pareja_1_set_1 ?? 0),
                    (int) ($p->pareja_1_set_2 ?? 0),
                    (int) ($p->pareja_1_set_3 ?? 0),
                ];
            }
            return [
                (int) ($p->pareja_2_set_1 ?? 0),
                (int) ($p->pareja_2_set_2 ?? 0),
                (int) ($p->pareja_2_set_3 ?? 0),
            ];
        };
        $packVisual = function (array $t1, array $t2): array {
            return [
                'pareja_1_set_1' => $t1[0],
                'pareja_1_set_2' => $t1[1],
                'pareja_1_set_3' => $t1[2],
                'pareja_2_set_1' => $t2[0],
                'pareja_2_set_2' => $t2[1],
                'pareja_2_set_3' => $t2[2],
            ];
        };

        $pj1a = $pareja1Cruce['jugador_1'] ?? null;
        $pj1b = $pareja1Cruce['jugador_2'] ?? null;
        $pj2a = $pareja2Cruce['jugador_1'] ?? null;
        $pj2b = $pareja2Cruce['jugador_2'] ?? null;

        $grupos = DB::table('grupos')->where('partido_id', $partidoId)->where('torneo_id', $torneoId)->orderBy('id')->get();
        if ($grupos->count() < 2) {
            return $packVisual($tripleDesdeSlotDb(0), $tripleDesdeSlotDb(1));
        }

        $slotPorPareja = function ($ja, $jb) use ($grupos): ?int {
            foreach ([0, 1] as $idx) {
                $g = $grupos->get($idx);
                if ($g && $this->mismaParejaQueGrupo($g, $ja, $jb)) {
                    return $idx;
                }
            }
            return null;
        };

        $iv1 = $slotPorPareja($pj1a, $pj1b);
        $iv2 = $slotPorPareja($pj2a, $pj2b);

        if ($iv1 !== null && $iv2 !== null && $iv1 !== $iv2) {
            return $packVisual($tripleDesdeSlotDb($iv1), $tripleDesdeSlotDb($iv2));
        }

        if ($iv1 !== null && $iv2 === null) {
            $otro = 1 - $iv1;

            return $packVisual($tripleDesdeSlotDb($iv1), $tripleDesdeSlotDb($otro));
        }
        if ($iv2 !== null && $iv1 === null) {
            $otro = 1 - $iv2;

            return $packVisual($tripleDesdeSlotDb($otro), $tripleDesdeSlotDb($iv2));
        }

        return $packVisual($tripleDesdeSlotDb(0), $tripleDesdeSlotDb(1));
    }

    private function enriquecerCrucesSetsVisual(array &$cruces, int $torneoId): void
    {
        foreach ($cruces as &$cruce) {
            $pid = isset($cruce['partido_id']) ? (int) $cruce['partido_id'] : 0;
            $partido = $cruce['partido'] ?? null;
            $p1 = $cruce['pareja_1'] ?? null;
            $p2 = $cruce['pareja_2'] ?? null;
            if ($pid <= 0 || !$partido || !is_array($p1) || !is_array($p2)) {
                continue;
            }
            $cruce['sets_visual'] = $this->obtenerSetsPartidoParaOrdenVisual($partido, $p1, $p2, $torneoId, $pid);
        }
        unset($cruce);
    }

    /**
     * Guarda el resultado de un partido en la base de datos
     * 
     * @param Partido $partido El objeto Partido a actualizar
     * @param int $torneoId ID del torneo
     * @param int $pareja1Set1 Set 1 de la pareja 1
     * @param int $pareja1Set2 Set 2 de la pareja 1
     * @param int $pareja1Set3 Set 3 de la pareja 1
     * @param int $pareja2Set1 Set 1 de la pareja 2
     * @param int $pareja2Set2 Set 2 de la pareja 2
     * @param int $pareja2Set3 Set 3 de la pareja 2
     * @param int|null $pareja1Jugador1 ID del jugador 1 de la pareja 1
     * @param int|null $pareja1Jugador2 ID del jugador 2 de la pareja 1
     * @param int|null $pareja2Jugador1 ID del jugador 1 de la pareja 2
     * @param int|null $pareja2Jugador2 ID del jugador 2 de la pareja 2
     */
    private function guardarResultadoPartido($partido, $torneoId, $pareja1Set1, $pareja1Set2, $pareja1Set3,
                                            $pareja2Set1, $pareja2Set2, $pareja2Set3,
                                            $pareja1Jugador1, $pareja1Jugador2, $pareja2Jugador1, $pareja2Jugador2) {
        \Log::info('=== INICIO guardarResultadoPartido ===');
        \Log::info('Partido ID: ' . $partido->id . ', Torneo ID: ' . $torneoId);
        
        // Obtener los grupos asociados a este partido para identificar el orden
        $grupos = DB::table('grupos')
                    ->where('partido_id', $partido->id)
                    ->where('torneo_id', $torneoId)
                    ->orderBy('id')
                    ->get();
        
        \Log::info('Grupos encontrados: ' . $grupos->count());
        
        // `pareja_1_*` en partidos = grupo índice 0 por id; `pareja_2_*` = grupo índice 1.
        // El formulario envía pareja izquierda/derecha de la tarjeta; hay que ubicar qué grupo físico es cada una (por jugadores como pareja).
        if ($grupos->count() >= 2) {
            $g1 = $grupos->get(0);
            $g2 = $grupos->get(1);

            \Log::info('Grupo 1 - Jugadores: ' . $g1->jugador_1 . '/' . $g1->jugador_2);
            \Log::info('Grupo 2 - Jugadores: ' . $g2->jugador_1 . '/' . $g2->jugador_2);
            \Log::info('Pareja 1 request - Jugadores: ' . $pareja1Jugador1 . '/' . $pareja1Jugador2);
            \Log::info('Pareja 2 request - Jugadores: ' . $pareja2Jugador1 . '/' . $pareja2Jugador2);

            $slotParaForm = function ($j1, $j2) use ($g1, $g2): ?int {
                if ($this->mismaParejaQueGrupo($g1, $j1, $j2)) {
                    return 0;
                }
                if ($this->mismaParejaQueGrupo($g2, $j1, $j2)) {
                    return 1;
                }

                return null;
            };

            $sReq1 = $slotParaForm($pareja1Jugador1, $pareja1Jugador2);
            $sReq2 = $slotParaForm($pareja2Jugador1, $pareja2Jugador2);

            $triReq = [[$pareja1Set1, $pareja1Set2, $pareja1Set3], [$pareja2Set1, $pareja2Set2, $pareja2Set3]];
            $tripleParaSlot = function (int $slotIdx) use ($sReq1, $sReq2, $triReq): array {
                if ($sReq1 === $slotIdx) {
                    return $triReq[0];
                }
                if ($sReq2 === $slotIdx) {
                    return $triReq[1];
                }

                return [0, 0, 0];
            };

            /** Jugadores esperados por índice físico de grupo (0 = pareja_1_* en partidos, 1 = pareja_2_*). */
            $jugadoresParaSlot = function (int $slotIdx) use ($sReq1, $sReq2, $pareja1Jugador1, $pareja1Jugador2, $pareja2Jugador1, $pareja2Jugador2): ?array {
                if ($sReq1 !== null && $sReq2 !== null && $sReq1 !== $sReq2) {
                    if ($slotIdx === $sReq1) {
                        return [$pareja1Jugador1, $pareja1Jugador2];
                    }
                    if ($slotIdx === $sReq2) {
                        return [$pareja2Jugador1, $pareja2Jugador2];
                    }

                    return null;
                }
                // Solo una pareja coincide con BD (ej. clasificación directa / grupo sin actualizar): la otra va al otro slot.
                if ($sReq1 !== null && $sReq2 === null) {
                    return $slotIdx === $sReq1 ? [$pareja1Jugador1, $pareja1Jugador2] : [$pareja2Jugador1, $pareja2Jugador2];
                }
                if ($sReq2 !== null && $sReq1 === null) {
                    return $slotIdx === $sReq2 ? [$pareja2Jugador1, $pareja2Jugador2] : [$pareja1Jugador1, $pareja1Jugador2];
                }

                return null;
            };

            $t0 = null;
            $t1 = null;

            if ($sReq1 !== null && $sReq2 !== null && $sReq1 !== $sReq2) {
                \Log::info('guardarResultadoPartido: form pareja 1→slot ' . $sReq1 . ', pareja 2→slot ' . $sReq2);
                $t0 = $tripleParaSlot(0);
                $t1 = $tripleParaSlot(1);
            } elseif ($sReq1 !== null && $sReq2 === null) {
                \Log::info('guardarResultadoPartido: solo pareja 1 matchea grupo por IDs; pareja 2 va al otro slot (clasificación directa / jugadores desalineados en grupos).');
                $tripleInferido = function (int $slotIdx) use ($sReq1, $triReq): array {
                    return $slotIdx === $sReq1 ? $triReq[0] : $triReq[1];
                };
                $t0 = $tripleInferido(0);
                $t1 = $tripleInferido(1);
            } elseif ($sReq2 !== null && $sReq1 === null) {
                \Log::info('guardarResultadoPartido: solo pareja 2 matchea grupo por IDs; pareja 1 va al otro slot (clasificación directa / jugadores desalineados en grupos).');
                $tripleInferido = function (int $slotIdx) use ($sReq2, $triReq): array {
                    return $slotIdx === $sReq2 ? $triReq[1] : $triReq[0];
                };
                $t0 = $tripleInferido(0);
                $t1 = $tripleInferido(1);
            }

            if ($t0 !== null && $t1 !== null) {
                $partido->pareja_1_set_1 = $t0[0];
                $partido->pareja_1_set_2 = $t0[1];
                $partido->pareja_1_set_3 = $t0[2];
                $partido->pareja_2_set_1 = $t1[0];
                $partido->pareja_2_set_2 = $t1[1];
                $partido->pareja_2_set_3 = $t1[2];

                foreach ([[0, $g1], [1, $g2]] as $slotGrupo) {
                    $slotIdx = $slotGrupo[0];
                    $g = $slotGrupo[1];
                    $esp = $jugadoresParaSlot($slotIdx);
                    if ($esp === null) {
                        continue;
                    }
                    [$ej1, $ej2] = $esp;
                    if (!$this->mismaParejaQueGrupo($g, $ej1, $ej2)) {
                        DB::table('grupos')
                            ->where('id', $g->id)
                            ->where('torneo_id', $torneoId)
                            ->update([
                                'jugador_1' => (int) $ej1,
                                'jugador_2' => (int) $ej2,
                            ]);
                        \Log::info('guardarResultadoPartido: grupo ' . $g->id . ' alineado con slot ' . $slotIdx . ' (jugadores del request).');
                    }
                }
            } else {
                \Log::warning('guardarResultadoPartido: match incompleto (s1=' . json_encode($sReq1) . ' s2=' . json_encode($sReq2) . '); fallback posición jugadores en g1');
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
            }
        } else {
            // Si no hay grupos suficientes, guardar directamente
            \Log::info('No hay grupos suficientes (' . $grupos->count() . ') - Guardando directamente');
            $partido->pareja_1_set_1 = $pareja1Set1;
            $partido->pareja_1_set_2 = $pareja1Set2;
            $partido->pareja_1_set_3 = $pareja1Set3;
            $partido->pareja_2_set_1 = $pareja2Set1;
            $partido->pareja_2_set_2 = $pareja2Set2;
            $partido->pareja_2_set_3 = $pareja2Set3;
        }
        
        \Log::info('Valores antes de guardar - P1: ' . $partido->pareja_1_set_1 . '/' . $partido->pareja_1_set_2 . '/' . $partido->pareja_1_set_3);
        \Log::info('Valores antes de guardar - P2: ' . $partido->pareja_2_set_1 . '/' . $partido->pareja_2_set_2 . '/' . $partido->pareja_2_set_3);
        
        // Guardar el partido
        $resultadoSave = $partido->save();
        
        \Log::info('Resultado de save(): ' . ($resultadoSave ? 'true' : 'false'));
        \Log::info('=== FIN guardarResultadoPartido ===');
    }

    /**
     * Guarda el resultado de un cruce eliminatorio para torneo puntuable
     */
    public function guardarResultadoCrucePuntuable(Request $request) {
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
        
        // Mapear ronda a nombre de zona
        $zonaRonda = '';
        if ($ronda === '16avos') {
            $zonaRonda = '16avos final';
        } else if ($ronda === 'octavos') {
            $zonaRonda = 'octavos final';
        } else if ($ronda === 'cuartos') {
            $zonaRonda = 'cuartos final';
        } else if ($ronda === 'semifinales') {
            $zonaRonda = 'semifinal';
        } else if ($ronda === 'final') {
            $zonaRonda = 'final';
        }
        
        // Buscar si ya existe un partido eliminatorio con estas parejas
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
        
        // Para octavos, cuartos y 16avos, buscar por zona
        if ($ronda === '16avos') {
            $query->where('zona', '16avos final');
        } else if ($ronda === 'octavos') {
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
        
        $this->guardarResultadoPartido($partido, $torneoId,
            $pareja1Set1, $pareja1Set2, $pareja1Set3,
            $pareja2Set1, $pareja2Set2, $pareja2Set3,
            $pareja1Jugador1, $pareja1Jugador2, $pareja2Jugador1, $pareja2Jugador2);
        $partido->refresh();
        
        \Log::info('Resultado guardado para ronda: ' . $ronda . ', partido_id: ' . $partido->id . ', sets P1: ' . $partido->pareja_1_set_1 . '/' . $partido->pareja_1_set_2 . '/' . $partido->pareja_1_set_3 . ', sets P2: ' . $partido->pareja_2_set_1 . '/' . $partido->pareja_2_set_2 . '/' . $partido->pareja_2_set_3);
        
        // Si se guardó un resultado de 16avos, asignar ganador al partido de octavos correspondiente (DA1, DA2, etc.)
        if ($ronda === '16avos') {
            $this->asignarGanador16avosAOctavos($torneoId, $partido);
        }
        
        // Si se guardó un resultado de octavos, crear partidos de cuartos basándose en la configuración
        if ($ronda === 'octavos') {
            \Log::info('Resultado de octavos guardado, verificando si se pueden crear cuartos desde configuración');
            $this->crearCuartosDesdeConfiguracionYOctavos($torneoId, $partido);
        }
        
        // Si se guardó un resultado de cuartos, crear semifinales desde configuración (CU1..CU4); fallback a lógica legacy
        if ($ronda === 'cuartos') {
            $this->crearSemifinalesDesdeConfiguracionYCuartos($torneoId, $partido);
            $this->crearSemifinalesSiEsNecesario($torneoId);
        }
        
        // Si se guardó un resultado de semifinales, crear final desde configuración (G1-semifinal, G2-semifinal); fallback a lógica legacy
        if ($ronda === 'semifinales') {
            $this->crearFinalDesdeConfiguracionYSemifinales($torneoId, $partido);
            $this->crearFinalSiEsNecesario($torneoId);
        }
        
        // Incrementar versión del torneo para notificar a vistas TV
        \App\Torneo::incrementarVersion($torneoId);

        // Determinar si se actualizó/creó la siguiente instancia para poder setear partido_id en el card siguiente (frontend).
        $partidoIdSiguiente = null;
        if ($ronda === '16avos') {
            $partidoIdSiguiente = $this->asignarGanador16avosAOctavos($torneoId, $partido);
        } else if ($ronda === 'octavos') {
            $partidoIdSiguiente = $this->crearCuartosDesdeConfiguracionYOctavos($torneoId, $partido);
        } else if ($ronda === 'cuartos') {
            $partidoIdSiguiente = $this->crearSemifinalesDesdeConfiguracionYCuartos($torneoId, $partido);
            if (!$partidoIdSiguiente) $partidoIdSiguiente = $this->crearSemifinalesSiEsNecesario($torneoId);
        } else if ($ronda === 'semifinales') {
            $partidoIdSiguiente = $this->crearFinalDesdeConfiguracionYSemifinales($torneoId, $partido);
            if (!$partidoIdSiguiente) $partidoIdSiguiente = $this->crearFinalSiEsNecesario($torneoId);
        }

        $ganadorLlave = $this->obtenerGanadorLlaveParaFrontend($partido, $ronda, $request->cruce_id ?? '', $torneoId);
        if ($ganadorLlave && $partidoIdSiguiente) {
            $ganadorLlave['partido_id_siguiente'] = $partidoIdSiguiente;
        }

        $resp = [
            'success' => true,
            'partido' => $partido,
            'partido_id' => $partido->id
        ];
        if ($ganadorLlave) {
            $resp['ganador_llave'] = $ganadorLlave;
        }
        return response()->json($resp);
    }

    /**
     * Crea un nuevo partido vacío
     */
    private function crearPartido() {
        $partido = new Partido;
        $partido->pareja_1_set_1 = 0;
        $partido->pareja_1_set_1_tie_break = 0;
        $partido->pareja_2_set_1 = 0;
        $partido->pareja_2_set_1_tie_break = 0;
        $partido->pareja_1_set_2 = 0;
        $partido->pareja_1_set_2_tie_break = 0;
        $partido->pareja_2_set_2 = 0;
        $partido->pareja_2_set_2_tie_break = 0;
        $partido->pareja_1_set_3 = 0;
        $partido->pareja_1_set_3_tie_break = 0;    
        $partido->pareja_2_set_3 = 0;
        $partido->pareja_2_set_3_tie_break = 0;
        $partido->pareja_1_set_super_tie_break = 0;
        $partido->pareja_2_set_super_tie_break = 0;
        $partido->save();

        return $partido;
    }

    /**
     * Determina el ganador de un partido basándose en los sets
     * Retorna 1 si ganó pareja_1, 2 si ganó pareja_2
     */
    private function determinarGanadorPartido($partido) {
        $setsGanadosP1 = 0;
        $setsGanadosP2 = 0;
        
        // Set 1
        if (isset($partido->pareja_1_set_1) && isset($partido->pareja_2_set_1)) {
            if ($partido->pareja_1_set_1 > $partido->pareja_2_set_1) {
                $setsGanadosP1++;
            } else if ($partido->pareja_2_set_1 > $partido->pareja_1_set_1) {
                $setsGanadosP2++;
            }
        }
        
        // Set 2
        if (isset($partido->pareja_1_set_2) && isset($partido->pareja_2_set_2)) {
            if ($partido->pareja_1_set_2 > $partido->pareja_2_set_2) {
                $setsGanadosP1++;
            } else if ($partido->pareja_2_set_2 > $partido->pareja_1_set_2) {
                $setsGanadosP2++;
            }
        }
        
        // Set 3 (si existe)
        if (isset($partido->pareja_1_set_3) && isset($partido->pareja_2_set_3)) {
            if ($partido->pareja_1_set_3 > 0 || $partido->pareja_2_set_3 > 0) {
                if ($partido->pareja_1_set_3 > $partido->pareja_2_set_3) {
                    $setsGanadosP1++;
                } else if ($partido->pareja_2_set_3 > $partido->pareja_1_set_3) {
                    $setsGanadosP2++;
                }
            }
        }
        
        // Si hay empate en sets, usar super tie break (si existe)
        if ($setsGanadosP1 == $setsGanadosP2) {
            $superTieBreak1 = isset($partido->pareja_1_set_super_tie_break) ? $partido->pareja_1_set_super_tie_break : 0;
            $superTieBreak2 = isset($partido->pareja_2_set_super_tie_break) ? $partido->pareja_2_set_super_tie_break : 0;
            
            if ($superTieBreak1 > $superTieBreak2) {
                return 1;
            } else if ($superTieBreak2 > $superTieBreak1) {
                return 2;
            }
        }
        
        // Si no hay ganador claro (empate sin super tie break), retornar null
        if ($setsGanadosP1 == $setsGanadosP2) {
            return null;
        }
        
        return $setsGanadosP1 > $setsGanadosP2 ? 1 : 2;
    }

    private function normalizarReferenciaConfig($ref): string
    {
        $s = is_string($ref) ? $ref : '';
        $s = strtoupper(trim($s));
        $s = preg_replace('/\s+/', '', $s);
        return $s ?: '';
    }

    /**
     * Forma canónica del ganador de cuartos: CU1..CUn. Al leer config o legacy se acepta C1 / G1-4TOS y se mapea a CU1.
     */
    private function referenciaGanadorCuartosACU($ref): ?string
    {
        $r = $this->normalizarReferenciaConfig($ref);
        if ($r === '') {
            return null;
        }
        if (preg_match('/^CU(\d+)$/', $r, $m)) {
            return 'CU' . (int) $m[1];
        }
        if (preg_match('/^C(\d+)$/', $r, $m)) {
            return 'CU' . (int) $m[1];
        }
        if (preg_match('/^G(\d+)-(4TOS|CUARTOS)$/', $r, $m)) {
            return 'CU' . (int) $m[1];
        }
        return null;
    }

    /**
     * Devuelve una lista de referencias equivalentes para matchear `grupos.referencia_config`.
     * Normaliza a mayúsculas y sin espacios.
     *
     * @return array<int,string>
     */
    private function referenciasEquivalentes(string $ref): array
    {
        $ref = $this->normalizarReferenciaConfig($ref);
        if ($ref === '') return [];

        // DAx (ganador 16avos) -> puede estar guardado como DAx, GANADOR_DAx, Gx-16AVOS, etc.
        if (preg_match('/^DA(\d+)$/', $ref, $m)) {
            $n = (int) $m[1];
            return array_values(array_unique([
                'DA' . $n,
                'GANADOR_DA' . $n,
                'G' . $n . '-16AVOS',
                'G' . $n . '-16AVO',
            ]));
        }

        // Ox (ganador octavos) -> O1 / G1-8VOS / G1-OCTAVOS
        if (preg_match('/^O(\d+)$/', $ref, $m)) {
            $n = (int) $m[1];
            return array_values(array_unique([
                'O' . $n,
                'G' . $n . '-8VOS',
                'G' . $n . '-OCTAVOS',
            ]));
        }

        // CUx (ganador cuartos) — solo forma canónica CU{n} (C1 / G1-4TOS se normalizan a CU1)
        if (preg_match('/^CU(\d+)$/', $ref, $m) || preg_match('/^C(\d+)$/', $ref, $m)
            || preg_match('/^G(\d+)-(4TOS|CUARTOS)$/', $ref, $m)) {
            $n = (int) $m[1];
            return ['CU' . $n];
        }

        // Sx (ganador semifinal) -> S1 / G1-SEMIFINAL / G1-SEMIS
        if (preg_match('/^S(\d+)$/', $ref, $m)) {
            $n = (int) $m[1];
            return array_values(array_unique([
                'S' . $n,
                'G' . $n . '-SEMIFINAL',
                'G' . $n . '-SEMIS',
                'G' . $n . '-SEMIFINALES',
            ]));
        }

        return [$ref];
    }

    /**
     * Actualiza el primer slot (grupo) de una ronda cuyo `referencia_config` coincida con alguna referencia.
     * Retorna el partido_id encontrado (si existe).
     */
    private function actualizarSlotPorReferencias(int $torneoId, string $zonaLike, array $refs, array $winnerPair): ?int
    {
        $refs = array_values(array_filter(array_map([$this, 'normalizarReferenciaConfig'], $refs)));
        if (empty($refs)) return null;

        $slot = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'like', $zonaLike)
            ->where(function ($q) use ($refs) {
                foreach ($refs as $r) {
                    $q->orWhereRaw('UPPER(REPLACE(TRIM(COALESCE(referencia_config, \'\')), \' \', \'\')) = ?', [$r]);
                }
            })
            ->orderBy('id')
            ->first();

        if (!$slot) return null;

        DB::table('grupos')->where('id', $slot->id)->update([
            'jugador_1' => $winnerPair['jugador_1'] ?? 0,
            'jugador_2' => $winnerPair['jugador_2'] ?? 0,
        ]);

        return isset($slot->partido_id) ? (int) $slot->partido_id : null;
    }

    /**
     * Crea grupo de cuartos de final cuando se completa un partido de octavos
     * Respeta el orden: Partido 1 vs Partido 2, Partido 3 vs Partido 4, etc.
     */
    private function crearGrupoCuartosDesdeOctavos($torneoId, $partido, $grupos) {
        // PRIMERO: Verificar si ya existen todos los cuartos posibles (4 partidos de cuartos = 8 grupos)
        // Si ya hay 8 grupos de cuartos final, no intentar crear más
        $totalGruposCuartos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'cuartos final')
            ->whereNotNull('partido_id')
            ->count();
        
        // También verificar partidos únicos de cuartos
        $partidosCuartosUnicos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'cuartos final')
            ->whereNotNull('partido_id')
            ->select(DB::raw('COUNT(DISTINCT partido_id) as count'))
            ->value('count');
        
        if ($totalGruposCuartos >= 8 || $partidosCuartosUnicos >= 4) {
            \Log::info('Ya existen todos los cuartos de final (grupos: ' . $totalGruposCuartos . ', partidos únicos: ' . $partidosCuartosUnicos . '). No se crearán más.');
            return;
        }
        
        // Verificar ganador por partido completo (sets y super tie-break si aplica)
        $ganadorActual = $this->determinarGanadorPartido($partido);

        \Log::info('Verificando ganador en crearGrupoCuartosDesdeOctavos: partido_id=' . $partido->id . ', sets P1=' . $partido->pareja_1_set_1 . '/' . $partido->pareja_1_set_2 . '/' . $partido->pareja_1_set_3 . ', sets P2=' . $partido->pareja_2_set_1 . '/' . $partido->pareja_2_set_2 . '/' . $partido->pareja_2_set_3 . ', ganador=' . ($ganadorActual ?? 'null'));

        if ($ganadorActual === null) {
            \Log::info('Partido de octavos sin ganador claro aún (incluye super tie-break pendiente).');
            return;
        }
        
        if ($grupos->count() < 2) {
            \Log::error('No se encontraron los grupos del partido de octavos. Grupos encontrados: ' . $grupos->count() . ', partido_id=' . $partido->id . ', torneo_id=' . $torneoId);
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
        
        // Determinar cuál es el primer partido del par y cuál es el segundo
        $primerPartidoPar = ($numeroCuartos - 1) * 2 + 1;
        $segundoPartidoPar = $partidoParOctavos;
        
        // Si el partido actual es el segundo del par, necesitamos obtener el ganador del primer partido
        // Si el partido actual es el primero del par, necesitamos obtener el ganador del segundo partido
        $esPrimerPartido = ($numeroPartidoOctavos == $primerPartidoPar);
        $esSegundoPartido = ($numeroPartidoOctavos == $segundoPartidoPar);
        
        \Log::info('Partido actual es: ' . ($esPrimerPartido ? 'PRIMERO' : ($esSegundoPartido ? 'SEGUNDO' : 'DESCONOCIDO')) . ' del par');
        
        // Obtener el ganador del partido actual
        $ganador = $this->determinarGanadorPartido($partido);
        
        \Log::info('Ganador determinado para partido actual (partido_id=' . $partido->id . '): ' . $ganador . ' (1=pareja_1, 2=pareja_2)');
        \Log::info('Sets del partido actual: pareja_1=' . $partido->pareja_1_set_1 . '/' . $partido->pareja_1_set_2 . '/' . $partido->pareja_1_set_3 . ', pareja_2=' . $partido->pareja_2_set_1 . '/' . $partido->pareja_2_set_2 . '/' . $partido->pareja_2_set_3);
        
        // Acceder a los grupos de la colección
        $g1 = $grupos[0];
        $g2 = $grupos[1];
        
        \Log::info('Grupos del partido actual: g1 (primer grupo) jugadores=' . $g1->jugador_1 . '/' . $g1->jugador_2 . ', g2 (segundo grupo) jugadores=' . $g2->jugador_1 . '/' . $g2->jugador_2);
        
        // Si el ganador es 1, significa que pareja_1 ganó
        // Si el ganador es 2, significa que pareja_2 ganó
        // Asumimos que g1 es pareja_1 y g2 es pareja_2
        $ganadorActualJugador1 = ($ganador == 1) ? $g1->jugador_1 : $g2->jugador_1;
        $ganadorActualJugador2 = ($ganador == 1) ? $g1->jugador_2 : $g2->jugador_2;
        
        \Log::info('Ganador partido actual determinado: jugador_1=' . $ganadorActualJugador1 . ', jugador_2=' . $ganadorActualJugador2);
        
        // Verificar si el partido par también está completo
        $partidoParCompleto = false;
        $ganadorParJugador1 = null;
        $ganadorParJugador2 = null;
        
        // Determinar qué partido necesitamos verificar como "par"
        // Si el partido actual es el primero del par, necesitamos el segundo
        // Si el partido actual es el segundo del par, necesitamos el primero
        $partidoParANumero = $esPrimerPartido ? $segundoPartidoPar : $primerPartidoPar;
        
        \Log::info('Buscando partido par: número=' . $partidoParANumero . ' (actual es ' . ($esPrimerPartido ? 'PRIMERO' : 'SEGUNDO') . ' del par)');
        
        // Obtener el partido par (el otro partido del par)
        if ($partidoParANumero <= count($partidosOctavos) && $partidoParANumero != $numeroPartidoOctavos) {
            $partidoPar = $partidosOctavos[$partidoParANumero - 1];
            
            \Log::info('Verificando partido par: partido_id=' . $partidoPar->id . ' (número ' . $partidoParANumero . '), sets P1=' . $partidoPar->pareja_1_set_1 . '/' . $partidoPar->pareja_1_set_2 . '/' . $partidoPar->pareja_1_set_3 . ', sets P2=' . $partidoPar->pareja_2_set_1 . '/' . $partidoPar->pareja_2_set_2 . '/' . $partidoPar->pareja_2_set_3);
            
            // Verificar si el partido par tiene ganador claro por partido completo
            $ganadorPartidoPar = $this->determinarGanadorPartido($partidoPar);

            \Log::info('Ganador partido par en crearGrupoCuartosDesdeOctavos: partido_id=' . $partidoPar->id . ', ganador=' . ($ganadorPartidoPar ?? 'null'));

            if ($ganadorPartidoPar !== null) {
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
                    
                    // Obtener el objeto Partido completo para usar determinarGanadorPartido
                    $partidoParCompletoObj = Partido::find($partidoPar->id);
                    
                    if ($partidoParCompletoObj) {
                        // Usar el mismo método que para el partido actual
                        $ganadorPar = $this->determinarGanadorPartido($partidoParCompletoObj);
                        $ganadorParJugador1 = ($ganadorPar == 1) ? $g1Par->jugador_1 : $g2Par->jugador_1;
                        $ganadorParJugador2 = ($ganadorPar == 1) ? $g1Par->jugador_2 : $g2Par->jugador_2;
                        
                        \Log::info('Ganador partido par determinado usando determinarGanadorPartido: ganador=' . $ganadorPar . ', jugador_1=' . $ganadorParJugador1 . ', jugador_2=' . $ganadorParJugador2);
                        \Log::info('Grupos partido par: g1Par jugadores=' . $g1Par->jugador_1 . '/' . $g1Par->jugador_2 . ', g2Par jugadores=' . $g2Par->jugador_1 . '/' . $g2Par->jugador_2);
                    } else {
                        \Log::error('No se encontró el objeto Partido para partido_id=' . $partidoPar->id);
                    }
                } else {
                    \Log::error('No se encontraron suficientes grupos para el partido par. Grupos encontrados: ' . $gruposPar->count());
                }
            } else {
                \Log::info('Partido par no tiene ganador claro aún (incluye super tie-break pendiente).');
            }
        } else {
            if ($partidoParANumero == $numeroPartidoOctavos) {
                \Log::info('El partido par es el mismo que el partido actual (número ' . $numeroPartidoOctavos . '). Esto no debería pasar.');
            } else {
                \Log::info('Partido par no existe aún (número ' . $partidoParANumero . ' > ' . count($partidosOctavos) . ')');
            }
        }
        
        // Si ambos partidos del par están completos, verificar si ya existe el partido de cuartos antes de crear
        if ($partidoParCompleto && $ganadorParJugador1 !== null) {
            // PRIMERO: Verificar si ya existe un partido de cuartos con estos mismos ganadores
            $cuartosExistentesConMismosJugadores = DB::table('grupos as g1')
                ->join('grupos as g2', function($join) {
                    $join->on('g1.partido_id', '=', 'g2.partido_id')
                         ->whereRaw('g1.id != g2.id')
                         ->whereNotNull('g1.partido_id')
                         ->whereNotNull('g2.partido_id');
                })
                ->where('g1.torneo_id', $torneoId)
                ->where('g1.zona', 'cuartos final')
                ->where('g2.torneo_id', $torneoId)
                ->where('g2.zona', 'cuartos final')
                ->where(function($query) use ($ganadorActualJugador1, $ganadorActualJugador2, $ganadorParJugador1, $ganadorParJugador2) {
                    $query->where(function($q) use ($ganadorActualJugador1, $ganadorActualJugador2, $ganadorParJugador1, $ganadorParJugador2) {
                        $q->where('g1.jugador_1', $ganadorActualJugador1)
                          ->where('g1.jugador_2', $ganadorActualJugador2)
                          ->where('g2.jugador_1', $ganadorParJugador1)
                          ->where('g2.jugador_2', $ganadorParJugador2);
                    })
                    ->orWhere(function($q) use ($ganadorActualJugador1, $ganadorActualJugador2, $ganadorParJugador1, $ganadorParJugador2) {
                        $q->where('g1.jugador_1', $ganadorParJugador1)
                          ->where('g1.jugador_2', $ganadorParJugador2)
                          ->where('g2.jugador_1', $ganadorActualJugador1)
                          ->where('g2.jugador_2', $ganadorActualJugador2);
                    });
                })
                ->select('g1.partido_id')
                ->distinct()
                ->first();
            
            if ($cuartosExistentesConMismosJugadores) {
                \Log::info('Ya existe un partido de cuartos con estos mismos ganadores. partido_id=' . $cuartosExistentesConMismosJugadores->partido_id . '. No se creará duplicado.');
                return;
            }
            
            // SEGUNDO: Verificar si ya existe el partido de cuartos correspondiente por número
            $partidosCuartosExistentes = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'cuartos final')
                ->whereNotNull('partido_id')
                ->select('partido_id')
                ->distinct()
                ->orderBy('partido_id')
                ->get();
            
            $numeroCuartosExistentes = count($partidosCuartosExistentes);
            
            \Log::info('Cuartos existentes: ' . $numeroCuartosExistentes . ', cuartos necesarios para este par: ' . $numeroCuartos);
            
            // Si ya existen 4 o más partidos de cuartos, no crear más
            if ($numeroCuartosExistentes >= 4) {
                \Log::info('Ya existen todos los partidos de cuartos (4 partidos encontrados). No se creará duplicado.');
                return;
            }
            
            if ($numeroCuartosExistentes >= $numeroCuartos) {
                \Log::info('Ya existe el partido de cuartos número ' . $numeroCuartos . '. Total existentes: ' . $numeroCuartosExistentes . '. No se creará duplicado.');
                return;
            }
            
            \Log::info('Verificando condiciones para crear cuartos: partidoParCompleto=' . ($partidoParCompleto ? 'true' : 'false') . ', ganadorParJugador1=' . ($ganadorParJugador1 !== null ? $ganadorParJugador1 : 'null'));
            
            // Validar que los ganadores sean diferentes
            if ($ganadorActualJugador1 == $ganadorParJugador1 && $ganadorActualJugador2 == $ganadorParJugador2) {
                \Log::error('ERROR: Los ganadores son iguales! Ganador actual: ' . $ganadorActualJugador1 . '/' . $ganadorActualJugador2 . ', Ganador par: ' . $ganadorParJugador1 . '/' . $ganadorParJugador2);
                return;
            }
            
            // Validar que no haya jugadores repetidos entre las parejas
            if (($ganadorActualJugador1 == $ganadorParJugador1 || $ganadorActualJugador1 == $ganadorParJugador2) ||
                ($ganadorActualJugador2 == $ganadorParJugador1 || $ganadorActualJugador2 == $ganadorParJugador2)) {
                \Log::error('ERROR: Hay jugadores repetidos entre las parejas! Ganador actual: ' . $ganadorActualJugador1 . '/' . $ganadorActualJugador2 . ', Ganador par: ' . $ganadorParJugador1 . '/' . $ganadorParJugador2);
                return;
            }
            
            // ÚLTIMA VERIFICACIÓN antes de crear: asegurarse de que aún no existen todos los cuartos
            $verificacionFinalGrupos = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'cuartos final')
                ->whereNotNull('partido_id')
                ->count();
            
            $verificacionFinalPartidos = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'cuartos final')
                ->whereNotNull('partido_id')
                ->select('partido_id')
                ->distinct()
                ->count();
            
            if ($verificacionFinalGrupos >= 8 || $verificacionFinalPartidos >= 4) {
                \Log::info('VERIFICACIÓN FINAL: Ya existen todos los cuartos (grupos: ' . $verificacionFinalGrupos . ', partidos: ' . $verificacionFinalPartidos . '). No se creará el partido.');
                return;
            }
            
            \Log::info('Creando partido de cuartos número ' . $numeroCuartos);
            \Log::info('Ganador partido octavos ' . (($numeroCuartos - 1) * 2 + 1) . ': ' . $ganadorActualJugador1 . '/' . $ganadorActualJugador2);
            \Log::info('Ganador partido octavos ' . $partidoParOctavos . ': ' . $ganadorParJugador1 . '/' . $ganadorParJugador2);
            
            // Crear el partido de cuartos
            $partidoCuartos = $this->crearPartido();
            
            \Log::info('Partido de cuartos creado con ID: ' . $partidoCuartos->id);
            
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
            
            \Log::info('Grupo cuartos 1 guardado con ID: ' . $grupoCuartos1->id . ', jugadores: ' . $ganadorActualJugador1 . '/' . $ganadorActualJugador2);
            
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
            
            \Log::info('Grupo cuartos 2 guardado con ID: ' . $grupoCuartos2->id . ', jugadores: ' . $ganadorParJugador1 . '/' . $ganadorParJugador2);
            
            \Log::info('Creado partido de cuartos número ' . $numeroCuartos . ' desde octavos: partido_id=' . $partidoCuartos->id . 
                      ', pareja1 (octavos ' . (($numeroCuartos - 1) * 2 + 1) . ')=' . $ganadorActualJugador1 . '/' . $ganadorActualJugador2 . 
                      ', pareja2 (octavos ' . $partidoParOctavos . ')=' . $ganadorParJugador1 . '/' . $ganadorParJugador2);
        } else {
            \Log::info('Esperando que se complete el partido de octavos ' . $partidoParOctavos . ' para crear el partido de cuartos número ' . $numeroCuartos . 
                      '. partidoParCompleto=' . ($partidoParCompleto ? 'true' : 'false') . ', ganadorParJugador1=' . ($ganadorParJugador1 !== null ? $ganadorParJugador1 : 'null'));
        }
    }

    /**
     * Endpoint público para crear cuartos desde octavos (para debugging)
     */
    public function crearCuartosDesdeOctavosEndpoint(Request $request) {
        try {
            $torneoId = $request->torneo_id;
            
            if (!$torneoId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Torneo ID requerido'
                ]);
            }
            
            $this->crearCuartosDesdeOctavos($torneoId);
            
            return response()->json([
                'success' => true,
                'message' => 'Proceso de creación de cuartos ejecutado'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error en crearCuartosDesdeOctavosEndpoint: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crea los cuartos de final automáticamente cuando se completan los partidos de octavos necesarios
     * Respeta el orden: Partido 1 vs Partido 2, Partido 3 vs Partido 4, etc.
     */
    private function crearCuartosDesdeOctavos($torneoId) {
        // Verificar si ya existen todos los cuartos posibles (4 partidos de cuartos = 8 grupos)
        $totalGruposCuartos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'cuartos final')
            ->whereNotNull('partido_id')
            ->count();
        
        $partidosCuartosUnicos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'cuartos final')
            ->whereNotNull('partido_id')
            ->select(DB::raw('COUNT(DISTINCT partido_id) as count'))
            ->value('count');
        
        if ($totalGruposCuartos >= 8 || $partidosCuartosUnicos >= 4) {
            \Log::info('Ya existen todos los cuartos de final (grupos: ' . $totalGruposCuartos . ', partidos únicos: ' . $partidosCuartosUnicos . '). No se crearán más.');
            return;
        }
        
        // Obtener todos los partidos de octavos ordenados por el id del primer grupo (orden de creación)
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
        
        \Log::info('Revisando partidos de octavos para crear cuartos. Total encontrados: ' . $partidosOctavos->count());
        
        // Procesar los partidos de octavos en pares para crear cuartos
        // Partidos 1 y 2 → Cuartos 1, Partidos 3 y 4 → Cuartos 2, etc.
        for ($i = 0; $i < $partidosOctavos->count(); $i += 2) {
            if ($i + 1 >= $partidosOctavos->count()) {
                // No hay par completo, salir
                break;
            }
            
            $partido1 = $partidosOctavos[$i];
            $partido2 = $partidosOctavos[$i + 1];
            
            // Verificar que ambos partidos tengan ganador claro
            $ganador1 = $this->determinarGanadorPartido($partido1);
            $ganador2 = $this->determinarGanadorPartido($partido2);
            
            if ($ganador1 === null) {
                \Log::info('Partido de octavos ' . ($i + 1) . ' sin ganador claro aún. Continuando...');
                continue;
            }

            if ($ganador2 === null) {
                \Log::info('Partido de octavos ' . ($i + 2) . ' sin ganador claro aún. Continuando...');
                continue;
            }
            
            // Obtener los grupos de cada partido para identificar los ganadores
            $grupos1 = DB::table('grupos')
                ->where('partido_id', $partido1->id)
                ->where('torneo_id', $torneoId)
                ->where('zona', 'octavos final')
                ->orderBy('id')
                ->get();
            
            $grupos2 = DB::table('grupos')
                ->where('partido_id', $partido2->id)
                ->where('torneo_id', $torneoId)
                ->where('zona', 'octavos final')
                ->orderBy('id')
                ->get();
            
            if ($grupos1->count() < 2 || $grupos2->count() < 2) {
                \Log::error('No se encontraron los grupos completos para los partidos de octavos');
                continue;
            }
            
            $g1_1 = $grupos1->get(0);
            $g1_2 = $grupos1->get(1);
            $g2_1 = $grupos2->get(0);
            $g2_2 = $grupos2->get(1);
            
            // Determinar ganadores
            $ganador1Pareja = ($ganador1 == 1) ? 
                ['jugador_1' => $g1_1->jugador_1, 'jugador_2' => $g1_1->jugador_2] : 
                ['jugador_1' => $g1_2->jugador_1, 'jugador_2' => $g1_2->jugador_2];
            
            $ganador2Pareja = ($ganador2 == 1) ? 
                ['jugador_1' => $g2_1->jugador_1, 'jugador_2' => $g2_1->jugador_2] : 
                ['jugador_1' => $g2_2->jugador_1, 'jugador_2' => $g2_2->jugador_2];
            
            // Verificar si ya existe un partido de cuartos con estos ganadores
            $numeroCuartos = ($i / 2) + 1;
            $partidoCuartosExistente = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'cuartos final')
                ->where(function($q) use ($ganador1Pareja, $ganador2Pareja) {
                    $q->where(function($q2) use ($ganador1Pareja, $ganador2Pareja) {
                        $q2->where('jugador_1', $ganador1Pareja['jugador_1'])
                           ->where('jugador_2', $ganador1Pareja['jugador_2']);
                    })
                    ->orWhere(function($q2) use ($ganador1Pareja, $ganador2Pareja) {
                        $q2->where('jugador_1', $ganador2Pareja['jugador_1'])
                           ->where('jugador_2', $ganador2Pareja['jugador_2']);
                    });
                })
                ->whereNotNull('partido_id')
                ->first();
            
            if ($partidoCuartosExistente) {
                \Log::info('Ya existe un partido de cuartos con estos ganadores. No se creará duplicado.');
                continue;
            }
            
            // Verificación final antes de crear
            $verificacionFinalGrupos = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'cuartos final')
                ->whereNotNull('partido_id')
                ->count();
            
            $verificacionFinalPartidos = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'cuartos final')
                ->whereNotNull('partido_id')
                ->select(DB::raw('COUNT(DISTINCT partido_id) as count'))
                ->value('count');
            
            if ($verificacionFinalGrupos >= 8 || $verificacionFinalPartidos >= 4) {
                \Log::info('VERIFICACIÓN FINAL: Ya existen todos los cuartos (grupos: ' . $verificacionFinalGrupos . ', partidos: ' . $verificacionFinalPartidos . '). No se creará el partido.');
                continue;
            }
            
            // Crear el partido de cuartos
            $partidoCuartos = $this->crearPartido();
            
            \Log::info('Creando partido de cuartos número ' . $numeroCuartos);
            \Log::info('Ganador partido octavos ' . ($i + 1) . ': ' . $ganador1Pareja['jugador_1'] . '/' . $ganador1Pareja['jugador_2']);
            \Log::info('Ganador partido octavos ' . ($i + 2) . ': ' . $ganador2Pareja['jugador_1'] . '/' . $ganador2Pareja['jugador_2']);
            
            // Crear grupo para el ganador del primer partido
            $grupoCuartos1 = new Grupo;
            $grupoCuartos1->torneo_id = $torneoId;
            $grupoCuartos1->zona = 'cuartos final';
            $grupoCuartos1->fecha = '2000-01-01';
            $grupoCuartos1->horario = '00:00';
            $grupoCuartos1->jugador_1 = $ganador1Pareja['jugador_1'];
            $grupoCuartos1->jugador_2 = $ganador1Pareja['jugador_2'];
            $grupoCuartos1->partido_id = $partidoCuartos->id;
            $grupoCuartos1->save();
            
            // Crear grupo para el ganador del segundo partido
            $grupoCuartos2 = new Grupo;
            $grupoCuartos2->torneo_id = $torneoId;
            $grupoCuartos2->zona = 'cuartos final';
            $grupoCuartos2->fecha = '2000-01-01';
            $grupoCuartos2->horario = '00:00';
            $grupoCuartos2->jugador_1 = $ganador2Pareja['jugador_1'];
            $grupoCuartos2->jugador_2 = $ganador2Pareja['jugador_2'];
            $grupoCuartos2->partido_id = $partidoCuartos->id;
            $grupoCuartos2->save();
            
            \Log::info('Partido de cuartos creado con ID: ' . $partidoCuartos->id . ', grupos: ' . $grupoCuartos1->id . ' y ' . $grupoCuartos2->id);
        }
    }

    /**
     * Crea las semifinales automáticamente cuando se completan los cuartos necesarios
     */
    private function crearSemifinalesSiEsNecesario($torneoId) {
        // Verificar que existan al menos 4 partidos de cuartos completos antes de crear semifinales
        $totalPartidosCuartos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'cuartos final')
            ->whereNotNull('partido_id')
            ->select(DB::raw('COUNT(DISTINCT partido_id) as count'))
            ->value('count');
        
        if ($totalPartidosCuartos < 4) {
            \Log::info('Aún no hay suficientes partidos de cuartos completos (' . $totalPartidosCuartos . '/4). No se crearán semifinales.');
            return;
        }
        
        // Buscar todos los partidos de cuartos con resultados en la tabla grupos
        // Obtener todos los partidos de cuartos con resultados
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
        $ganadoresPorSemifinal = [
            'Semifinal 1' => [],
            'Semifinal 2' => []
        ];
        $partidosProcesados = [];
        
        foreach ($partidosCuartosOrdenados as $index => $partido) {
            if (in_array($partido->id, $partidosProcesados)) {
                continue;
            }
            
            $gruposCompletos = DB::table('grupos')
                ->where('partido_id', $partido->id)
                ->where('torneo_id', $torneoId)
                ->where('zona', 'like', 'cuartos final%')
                ->orderBy('id')
                ->get();
            
            if ($gruposCompletos->count() >= 2) {
                $g1 = $gruposCompletos[0];
                $g2 = $gruposCompletos[1];
                
                if ($partido->pareja_1_set_1 == 0 && $partido->pareja_2_set_1 == 0) {
                    continue;
                }
                
                // Determinar ganador usando el método determinarGanadorPartido
                $ganadorPartido = $this->determinarGanadorPartido($partido);
                
                if ($ganadorPartido === null) {
                    \Log::info('Partido de cuartos ' . $partido->id . ' no tiene ganador claro aún. Saltando.');
                    continue;
                }
                
                // Determinar ganador según el resultado del partido
                $ganador = ($ganadorPartido == 1) ? 
                    ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2] : 
                    ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                
                // Los primeros 2 partidos de cuartos van a Semifinal 1, los siguientes 2 a Semifinal 2
                $semifinalAsignada = ($index < 2) ? 'Semifinal 1' : 'Semifinal 2';
                
                // Verificar que no haya duplicados
                $yaExiste = false;
                foreach ($ganadoresPorSemifinal[$semifinalAsignada] as $ganadorExistente) {
                    if ($ganadorExistente['jugador_1'] == $ganador['jugador_1'] && 
                        $ganadorExistente['jugador_2'] == $ganador['jugador_2']) {
                        $yaExiste = true;
                        break;
                    }
                }
                
                if (!$yaExiste) {
                    $ganadoresPorSemifinal[$semifinalAsignada][] = $ganador;
                    $partidosProcesados[] = $partido->id;
                }
            }
        }
        
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
        if (count($ganadoresPorSemifinal['Semifinal 1']) == 2) {
            $partidosIds = array_keys($semifinalesPorPartido);
            sort($partidosIds);
            
            if (count($partidosIds) > 0) {
                $partidoIdSemifinal1 = $partidosIds[0];
                if (isset($semifinalesPorPartido[$partidoIdSemifinal1]) && count($semifinalesPorPartido[$partidoIdSemifinal1]) >= 2) {
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
                }
            }
        }
        
        // Actualizar Semifinal 2 SOLO con los ganadores de "Semifinal 2"
        if (count($ganadoresPorSemifinal['Semifinal 2']) == 2) {
            $partidosIds = array_keys($semifinalesPorPartido);
            sort($partidosIds);
            
            if (count($partidosIds) > 1) {
                $partidoIdSemifinal2 = $partidosIds[1];
                if (isset($semifinalesPorPartido[$partidoIdSemifinal2]) && count($semifinalesPorPartido[$partidoIdSemifinal2]) >= 2) {
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
                }
            }
        }
    }

    /**
     * Crea la final automáticamente cuando se completan las semifinales necesarias
     */
    private function crearFinalSiEsNecesario($torneoId) {
        // Obtener todos los partidos de semifinales con resultados
        $partidosSemifinales = DB::table('partidos')
            ->join('grupos', 'partidos.id', '=', 'grupos.partido_id')
            ->where('grupos.torneo_id', $torneoId)
            ->where('grupos.zona', 'semifinal')
            ->where(function($query) {
                $query->where('partidos.pareja_1_set_1', '>', 0)
                      ->orWhere('partidos.pareja_2_set_1', '>', 0)
                      ->orWhere('partidos.pareja_1_set_2', '>', 0)
                      ->orWhere('partidos.pareja_2_set_2', '>', 0)
                      ->orWhere('partidos.pareja_1_set_3', '>', 0)
                      ->orWhere('partidos.pareja_2_set_3', '>', 0)
                      ->orWhere('partidos.pareja_1_set_super_tie_break', '>', 0)
                      ->orWhere('partidos.pareja_2_set_super_tie_break', '>', 0);
            })
            ->select(
                'partidos.id',
                'partidos.pareja_1_set_1', 'partidos.pareja_2_set_1',
                'partidos.pareja_1_set_2', 'partidos.pareja_2_set_2',
                'partidos.pareja_1_set_3', 'partidos.pareja_2_set_3',
                'partidos.pareja_1_set_super_tie_break', 'partidos.pareja_2_set_super_tie_break'
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
                
                // Determinar ganador por partido completo (sets y, si aplica, super tie-break)
                $ganadorNum = $this->determinarGanadorPartido($partido);
                if ($ganadorNum === null) {
                    continue;
                }

                $ganador = ($ganadorNum === 1)
                    ? ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2]
                    : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                
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
            } else {
                // Si no existe, crearla
                $this->crearPartidoEliminatorio($torneoId, $ganadoresSemifinales[0], $ganadoresSemifinales[1], 'final');
            }
        }
    }

    /**
     * Cuenta las parejas reales del torneo desde la tabla grupos (excluyendo zonas eliminatorias).
     * Usar esto para seleccionar la config con cantidad_parejas que coincida.
     */
    private function contarParejasDesdeGrupos($torneoId) {
        $grupos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where(function ($q) {
                $q->whereNotIn('zona', ['cuartos final', 'semifinal', 'final', 'octavos final', '16avos final'])
                  ->where('zona', 'not like', 'cuartos final|%')
                  ->where('zona', 'not like', 'ganador %')
                  ->where('zona', 'not like', 'perdedor %');
            })
            ->whereNotNull('jugador_1')
            ->whereNotNull('jugador_2')
            ->where('jugador_1', '!=', 0)
            ->where('jugador_2', '!=', 0)
            ->select('jugador_1', 'jugador_2')
            ->get();
        $parejasUnicas = [];
        foreach ($grupos as $g) {
            $key = min($g->jugador_1, $g->jugador_2) . '_' . max($g->jugador_1, $g->jugador_2);
            $parejasUnicas[$key] = true;
        }
        return count($parejasUnicas);
    }

    /**
     * Obtiene la cantidad de parejas del torneo desde configuracion_cruces_puntuables o torneos.
     * La columna cantidad_parejas puede no existir en torneos, por eso se busca en la config.
     */
    private function obtenerCantidadParejasTorneo($torneoId) {
        $config = DB::table('configuracion_cruces_puntuables')
            ->where(function ($q) use ($torneoId) {
                $q->where('torneo_id', $torneoId)->orWhereNull('torneo_id');
            })
            ->orderByRaw('torneo_id IS NOT NULL DESC')
            ->orderBy('id', 'desc')
            ->first();
        return $config ? ($config->cantidad_parejas ?? 16) : 16;
    }

    /**
     * Obtiene la configuración de cruces según cantidad de parejas.
     * Primero busca config del torneo, luego global (torneo_id null).
     * Si no hay para la cantidad exacta, intenta con 16 (llave estándar 8 octavos / 4 cuartos).
     */
    private function getConfiguracionCruces($torneoId, $cantidadParejas) {
        // Primero: verificar si el torneo tiene un config_cruces_puntuable_id asignado
        $torneo = DB::table('torneos')->where('id', $torneoId)->first();
        if ($torneo && !empty($torneo->config_cruces_puntuable_id)) {
            $config = DB::table('configuracion_cruces_puntuables')
                ->where('id', $torneo->config_cruces_puntuable_id)
                ->first();
            if ($config) {
                return $config;
            }
        }
        
        // Fallback: buscar por torneo_id o global con cantidad_parejas
        foreach ([$torneoId, null] as $tid) {
            $q = DB::table('configuracion_cruces_puntuables')
                ->where('cantidad_parejas', $cantidadParejas)
                ->orderBy('id', 'desc');
            if ($tid === null) {
                $q->whereNull('torneo_id');
            } else {
                $q->where('torneo_id', $tid);
            }
            $config = $q->first();
            if ($config) {
                return $config;
            }
        }
        // Fallback: si hay 24 o más parejas y no hay config, usar config de 16 (misma estructura 8 octavos)
        if ($cantidadParejas >= 16) {
            $config = DB::table('configuracion_cruces_puntuables')
                ->whereNull('torneo_id')
                ->where('cantidad_parejas', 16)
                ->whereNotNull('llave_4tos')
                ->orderBy('id', 'desc')
                ->first();
            if ($config) {
                return $config;
            }
        }
        return null;
    }

    /**
     * Si hay 16avos en el torneo, asegura que exista el partido de octavos "ganador 16avos vs primera zona".
     * Usa las referencias de la config (DA1, GANADOR_DA1, G1-16avos, etc.) en lugar de hardcodear G1-16avos.
     */
    private function asegurarPartidoOctavosGanador16avosVsA1($torneoId)
    {
        $tiene16avos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', '16avos final')
            ->whereNotNull('partido_id')
            ->exists();
        if (!$tiene16avos) {
            return;
        }
        $esRefGanador16avos = function ($ref) {
            $r = trim($ref ?? '');
            return preg_match('/^G\d+-16avos$/i', $r) || preg_match('/^GANADOR_DA\d+$/i', $r) || preg_match('/^DA\d+$/i', $r);
        };
        $esRefPrimeraZona = function ($ref) {
            return preg_match('/^[A-Z]1$/i', trim($ref ?? ''));
        };
        $gruposOctavos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'octavos final')
            ->whereNotNull('partido_id')
            ->get();
        $porPartido = [];
        foreach ($gruposOctavos as $g) {
            $pid = $g->partido_id;
            if (!isset($porPartido[$pid])) {
                $porPartido[$pid] = [];
            }
            $porPartido[$pid][] = $g->referencia_config;
        }
        $tienePartidoGanadorVsZona = false;
        foreach ($porPartido as $refs) {
            if (count($refs) >= 2) {
                $r1 = trim($refs[0] ?? '');
                $r2 = trim($refs[1] ?? '');
                if (($esRefGanador16avos($r1) && $esRefPrimeraZona($r2)) || ($esRefGanador16avos($r2) && $esRefPrimeraZona($r1))) {
                    $tienePartidoGanadorVsZona = true;
                    break;
                }
            }
        }
        if ($tienePartidoGanadorVsZona) {
            return;
        }
        $refGanador = 'DA1';
        $refZona = 'A1';
        $cantidadParejas = $this->obtenerCantidadParejasTorneo($torneoId);
        $config = $this->getConfiguracionCruces($torneoId, $cantidadParejas);
        if ($config && !empty($config->llave_8vos)) {
            $llave = json_decode($config->llave_8vos, true);
            if (is_array($llave)) {
                foreach ($llave as $partidoCfg) {
                    $p1 = trim($partidoCfg['pareja_1'] ?? '');
                    $p2 = trim($partidoCfg['pareja_2'] ?? '');
                    if (($esRefGanador16avos($p1) && $esRefPrimeraZona($p2)) || ($esRefGanador16avos($p2) && $esRefPrimeraZona($p1))) {
                        $refGanador = $esRefGanador16avos($p1) ? $p1 : $p2;
                        $refZona = $esRefPrimeraZona($p1) ? $p1 : $p2;
                        break;
                    }
                }
            }
        }
        $this->crearPartidoEliminatorioDesdeReferencias($torneoId, 'octavos', [
            'pareja_1' => $refGanador,
            'pareja_2' => $refZona
        ]);
        \Log::info('asegurarPartidoOctavosGanador16avosVsA1: creado partido ' . $refGanador . ' vs ' . $refZona . ' para torneo ' . $torneoId);
    }

    /**
     * Crea todos los partidos eliminatorios (16avos, octavos, cuartos, semifinales, final)
     * usando directamente la configuración de cruces (sin resolver aún las letras a jugadores reales).
     *
     * Para cada partido se crea un registro en la tabla partidos y dos filas en grupos con:
     *  - jugador_1 = 0, jugador_2 = 0 (placeholder sin asignar)
     *  - zona = '16avos final' | 'octavos final' | 'cuartos final' | 'semifinal' | 'final'
     *  - referencia_config = texto de la configuración (A1, H2, G1-8vos, G1-4tos, etc.)
     */
    private function crearPartidosEliminatoriosDesdeConfiguracion($torneoId, $config)
    {
        // 16avos
        if (!empty($config->tiene_16avos_final) && !empty($config->llave_16avos)) {
            $llave = json_decode($config->llave_16avos, true);
            if (is_array($llave)) {
                foreach ($llave as $partidoCfg) {
                    $this->crearPartidoEliminatorioDesdeReferencias($torneoId, '16avos', $partidoCfg);
                }
            }
        }

        // Octavos
        if (!empty($config->tiene_8vos_final) && !empty($config->llave_8vos)) {
            $llave = json_decode($config->llave_8vos, true);
            if (is_array($llave)) {
                // Si hay 16avos y la config no tiene ref ganador 16avos (DA1, G1-16avos, etc.), agregar fallback
                $tieneRefGanador16avos = false;
                foreach ($llave as $partidoCfg) {
                    $ref1 = trim($partidoCfg['pareja_1'] ?? '');
                    $ref2 = trim($partidoCfg['pareja_2'] ?? '');
                    if (preg_match('/^G\d+-16avos$/i', $ref1) || preg_match('/^G\d+-16avos$/i', $ref2) ||
                        preg_match('/^GANADOR_DA\d+$/i', $ref1) || preg_match('/^GANADOR_DA\d+$/i', $ref2) ||
                        preg_match('/^DA\d+$/i', $ref1) || preg_match('/^DA\d+$/i', $ref2)) {
                        $tieneRefGanador16avos = true;
                        break;
                    }
                }
                if (!empty($config->tiene_16avos_final) && !$tieneRefGanador16avos) {
                    $partidoGanador16avos = ['pareja_1' => 'DA1', 'pareja_2' => 'A1'];
                    if (count($llave) >= 8) {
                        $llave[0] = $partidoGanador16avos;
                    } else {
                        array_unshift($llave, $partidoGanador16avos);
                    }
                }
                foreach ($llave as $partidoCfg) {
                    $this->crearPartidoEliminatorioDesdeReferencias($torneoId, 'octavos', $partidoCfg);
                }
            }
        }

        // Cuartos (crear si existe llave_4tos, sin depender del checkbox tiene_4tos_final)
        if (!empty($config->llave_4tos)) {
            $llave = json_decode($config->llave_4tos, true);
            if (is_array($llave)) {
                foreach ($llave as $partidoCfg) {
                    $this->crearPartidoEliminatorioDesdeReferencias($torneoId, 'cuartos', $partidoCfg);
                }
            }
        }

        // Semifinales
        if (!empty($config->llave_semifinal)) {
            $llave = json_decode($config->llave_semifinal, true);
            if (is_array($llave)) {
                foreach ($llave as $partidoCfg) {
                    $this->crearPartidoEliminatorioDesdeReferencias($torneoId, 'semifinales', $partidoCfg);
                }
            }
        }

        // Final
        if (!empty($config->llave_final)) {
            $llave = json_decode($config->llave_final, true);
            if (is_array($llave)) {
                foreach ($llave as $partidoCfg) {
                    $this->crearPartidoEliminatorioDesdeReferencias($torneoId, 'final', $partidoCfg);
                }
            }
        }

        // Una vez creados todos los placeholders, intentar rellenar con las parejas reales
        // para las referencias directas de zona (A1, B2, etc.) de las zonas que ya estén completas.
        $this->rellenarGruposEliminatoriosDesdeZonasCompletas($torneoId);
    }

    /**
     * Público: rellena los cruces eliminatorios (A1, B2, etc.) cuando alguna zona de grupos quedó completa.
     * Llamado desde HomeController al guardar un resultado de fase de grupos en torneo puntuable.
     */
    public function rellenarCrucesDesdeZonasCompletasPorTorneo($torneoId)
    {
        $this->rellenarGruposEliminatoriosDesdeZonasCompletas($torneoId);
    }

    /**
     * Para las zonas de grupos que ya estén completas (todos sus partidos con resultado),
     * calcula la tabla de posiciones y rellena los grupos eliminatorios que tengan
     * referencias directas del tipo A1, B2, etc. (campo referencia_config), en 16avos,
     * octavos, cuartos y semifinal (no final).
     *
     * Esto permite que, a medida que se completan las zonas, se vayan completando
     * automáticamente los partidos de 16avos / octavos / cuartos / semifinal que toman clasificados directos
     * (referencias tipo A1 en `referencia_config`). La final no se rellena aquí.
     *
     * Se llama desde comenzarTorneoPuntuable y desde HomeController al guardar un resultado de fase de grupos.
     */
    private function rellenarGruposEliminatoriosDesdeZonasCompletas($torneoId)
    {
        // Obtener grupos de fase de zonas (no eliminatorios)
        $grupos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->whereNotNull('partido_id')
            ->whereNotIn('zona', ['dieciseisavos final', '16avos final', 'octavos final', 'cuartos final', 'semifinal', 'final'])
            ->orderBy('zona')
            ->orderBy('id')
            ->get();

        if ($grupos->isEmpty()) {
            return;
        }

        $zonas = $grupos->pluck('zona')->unique()->sort()->values();

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

        $resolverResultadoPartido = function ($gruposPartido, $partido) {
            if (count($gruposPartido) < 2 || !$partido) {
                return [null, null, 0, 0, 0, 0];
            }

            $g1 = $gruposPartido[0];
            $g2 = $gruposPartido[1];
            $key1 = $g1->jugador_1 . '_' . $g1->jugador_2;
            $key2 = $g2->jugador_1 . '_' . $g2->jugador_2;

            $games1 = ($partido->pareja_1_set_1 ?? 0) + ($partido->pareja_1_set_2 ?? 0) + ($partido->pareja_1_set_3 ?? 0);
            $games2 = ($partido->pareja_2_set_1 ?? 0) + ($partido->pareja_2_set_2 ?? 0) + ($partido->pareja_2_set_3 ?? 0);

            $sets1 = 0;
            $sets2 = 0;
            if (($partido->pareja_1_set_1 ?? 0) > ($partido->pareja_2_set_1 ?? 0)) $sets1++;
            elseif (($partido->pareja_2_set_1 ?? 0) > ($partido->pareja_1_set_1 ?? 0)) $sets2++;

            if (($partido->pareja_1_set_2 ?? 0) > ($partido->pareja_2_set_2 ?? 0)) $sets1++;
            elseif (($partido->pareja_2_set_2 ?? 0) > ($partido->pareja_1_set_2 ?? 0)) $sets2++;

            if (isset($partido->pareja_1_set_super_tie_break) && (($partido->pareja_1_set_super_tie_break ?? 0) > 0 || ($partido->pareja_2_set_super_tie_break ?? 0) > 0)) {
                if (($partido->pareja_1_set_super_tie_break ?? 0) > ($partido->pareja_2_set_super_tie_break ?? 0)) { $sets1 = 2; $sets2 = 1; }
                elseif (($partido->pareja_2_set_super_tie_break ?? 0) > ($partido->pareja_1_set_super_tie_break ?? 0)) { $sets1 = 1; $sets2 = 2; }
            } else {
                if (($partido->pareja_1_set_3 ?? 0) > ($partido->pareja_2_set_3 ?? 0)) $sets1++;
                elseif (($partido->pareja_2_set_3 ?? 0) > ($partido->pareja_1_set_3 ?? 0)) $sets2++;
            }

            return [$key1, $key2, $games1, $games2, $sets1, $sets2];
        };

        // Calcular posiciones por zona SOLO para las zonas completas
        $posicionesPorZona = [];
        foreach ($zonas as $zona) {
            $gruposZonaBase = $grupos->where('zona', $zona)->filter(function ($grupo) {
                return $grupo->jugador_1 !== null && $grupo->jugador_2 !== null;
            });
            if ($gruposZonaBase->isEmpty()) {
                continue;
            }

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
                        'partidos_directos' => [],
                    ];
                }
            }

            $esZonaCuatro = count($parejas) >= 4;
            $gruposZona = $gruposZonaBase;
            if ($esZonaCuatro) {
                $gruposZona = $gruposZona
                    ->merge($gruposGanadorPerdedor->where('zona', 'ganador ' . $zona))
                    ->merge($gruposGanadorPerdedor->where('zona', 'perdedor ' . $zona));
            }

            $partidosIds = $gruposZona->pluck('partido_id')->unique()->filter();
            if ($partidosIds->isEmpty()) {
                continue;
            }

            $partidosZona = DB::table('partidos')
                ->whereIn('id', $partidosIds)
                ->get()
                ->keyBy('id');

            $zonaCompleta = true;
            foreach ($partidosIds as $pid) {
                $partido = $partidosZona->get($pid);
                if (!$partido || !$this->partidoTieneResultado($partido)) {
                    $zonaCompleta = false;
                    break;
                }
            }
            if (!$zonaCompleta) {
                continue;
            }

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
                if (count($grs) < 2 || !isset($partidosZona[$partidoId])) {
                    continue;
                }

                $gruposPartido = collect($grs)->sortBy('id')->values()->all();
                $partido = $partidosZona[$partidoId];
                list($key1, $key2, $games1, $games2, $sets1, $sets2) = $resolverResultadoPartido($gruposPartido, $partido);

                if (!$key1 || !$key2 || !isset($parejas[$key1]) || !isset($parejas[$key2])) {
                    continue;
                }

                $parejas[$key1]['sets_ganados'] += $sets1;
                $parejas[$key1]['sets_perdidos'] += $sets2;
                $parejas[$key2]['sets_ganados'] += $sets2;
                $parejas[$key2]['sets_perdidos'] += $sets1;

                $parejas[$key1]['puntos_ganados'] += $games1;
                $parejas[$key1]['puntos_perdidos'] += $games2;
                $parejas[$key2]['puntos_ganados'] += $games2;
                $parejas[$key2]['puntos_perdidos'] += $games1;

                if ($sets1 > $sets2) {
                    $parejas[$key1]['partidos_ganados']++;
                    $parejas[$key2]['partidos_perdidos']++;
                    $parejas[$key1]['partidos_directos'][$key2] = ['ganado' => true];
                    $parejas[$key2]['partidos_directos'][$key1] = ['ganado' => false];
                    if (count($parejas) === 3) {
                        $parejas[$key1]['puntos'] = ($parejas[$key1]['puntos'] ?? 0) + 2;
                        $parejas[$key2]['puntos'] = ($parejas[$key2]['puntos'] ?? 0) + 1;
                    }
                } elseif ($sets2 > $sets1) {
                    $parejas[$key2]['partidos_ganados']++;
                    $parejas[$key1]['partidos_perdidos']++;
                    $parejas[$key2]['partidos_directos'][$key1] = ['ganado' => true];
                    $parejas[$key1]['partidos_directos'][$key2] = ['ganado' => false];
                    if (count($parejas) === 3) {
                        $parejas[$key2]['puntos'] = ($parejas[$key2]['puntos'] ?? 0) + 2;
                        $parejas[$key1]['puntos'] = ($parejas[$key1]['puntos'] ?? 0) + 1;
                    }
                }
            }

            foreach ($parejas as $key => $pareja) {
                $parejas[$key]['key'] = $key;
                $parejas[$key]['diferencia_sets'] = ($pareja['sets_ganados'] ?? 0) - ($pareja['sets_perdidos'] ?? 0);
                $parejas[$key]['diferencia_games'] = ($pareja['puntos_ganados'] ?? 0) - ($pareja['puntos_perdidos'] ?? 0);
                if (count($parejas) === 3 && !isset($parejas[$key]['puntos'])) {
                    $parejas[$key]['puntos'] = 0;
                }
            }

            $posiciones = array_values($parejas);

            if ($esZonaCuatro) {
                $ordenZona4 = [];

                $matchGanador = $gruposGanadorPerdedor->where('zona', 'ganador ' . $zona)->groupBy('partido_id')->first();
                $matchPerdedor = $gruposGanadorPerdedor->where('zona', 'perdedor ' . $zona)->groupBy('partido_id')->first();

                if ($matchGanador && count($matchGanador) >= 2) {
                    $gp = collect($matchGanador)->sortBy('id')->values()->all();
                    $p = $partidosZona[$gp[0]->partido_id] ?? null;
                    list($k1, $k2, $g1, $g2, $s1, $s2) = $resolverResultadoPartido($gp, $p);
                    if (($g1 > 0 || $g2 > 0) && $k1 && $k2) {
                        $ordenZona4[] = $s1 > $s2 ? $k1 : $k2;
                        $ordenZona4[] = $s1 > $s2 ? $k2 : $k1;
                    }
                }

                if ($matchPerdedor && count($matchPerdedor) >= 2) {
                    $pp = collect($matchPerdedor)->sortBy('id')->values()->all();
                    $p = $partidosZona[$pp[0]->partido_id] ?? null;
                    list($k1, $k2, $g1, $g2, $s1, $s2) = $resolverResultadoPartido($pp, $p);
                    if (($g1 > 0 || $g2 > 0) && $k1 && $k2) {
                        $ordenZona4[] = $s1 > $s2 ? $k1 : $k2;
                        $ordenZona4[] = $s1 > $s2 ? $k2 : $k1;
                    }
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

            if (count($posiciones) === 3) {
                usort($posiciones, function($a, $b) {
                    $puntosA = $a['puntos'] ?? 0;
                    $puntosB = $b['puntos'] ?? 0;
                    if ($puntosA != $puntosB) return $puntosB - $puntosA;
                    $diffSetsA = $a['diferencia_sets'] ?? 0;
                    $diffSetsB = $b['diferencia_sets'] ?? 0;
                    if ($diffSetsA != $diffSetsB) return $diffSetsB - $diffSetsA;
                    $diffGamesA = $a['diferencia_games'] ?? 0;
                    $diffGamesB = $b['diferencia_games'] ?? 0;
                    return $diffGamesB - $diffGamesA;
                });
            } elseif (count($posiciones) !== 4 || ($posiciones[0]['partidos_ganados'] ?? null) !== 2 || ($posiciones[3]['partidos_ganados'] ?? null) !== 0) {
                $esTripleEmpate3 = count($posiciones) === 3
                    && count(array_unique(array_map(function($p) { return $p['partidos_ganados']; }, $posiciones))) === 1;

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

            $posicionesPorZona[$zona] = $posiciones;
        }

        if (empty($posicionesPorZona)) {
            return;
        }

        // Mapear zonas a letras (A, B, C, ...) igual que en la configuración
        $letrasZonas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P'];
        $zonaALetra = [];
        $idx = 0;
        foreach ($zonas as $zona) {
            if (isset($letrasZonas[$idx])) {
                $zonaALetra[$zona] = $letrasZonas[$idx];
            }
            $idx++;
        }

        // Para cada referencia directa tipo A1, B2, etc., rellenar jugador_1 / jugador_2
        foreach ($posicionesPorZona as $zona => $posiciones) {
            foreach ($posiciones as $i => $pareja) {
                $posicion = $i + 1;
                $ref = $this->resolverReferenciaBracketParaParejaZona($torneoId, $zona, $pareja, $zonaALetra);

                if ($ref === null) {
                    $gSlot = DB::table('grupos')
                        ->where('torneo_id', $torneoId)
                        ->where('zona', $zona)
                        ->where('posicion_grupo', $posicion)
                        ->orderBy('id')
                        ->first();
                    if ($gSlot) {
                        $rc = strtoupper(trim((string) ($gSlot->referencia_config ?? '')));
                        if ($rc !== '' && preg_match('/^[A-P]\d+$/', $rc)) {
                            $ref = $rc;
                        }
                    }
                    if ($ref === null) {
                        $zTrim = trim((string) $zona);
                        if (preg_match('/^[A-P]$/', $zTrim)) {
                            $ref = strtoupper($zTrim) . $posicion;
                        }
                    }
                    if ($ref === null) {
                        if (!isset($zonaALetra[$zona])) {
                            continue;
                        }
                        $ref = $zonaALetra[$zona] . $posicion;
                    }
                }

                // Buscar grupos eliminatorios con esta referencia_config y sin jugadores asignados aún
                $gruposElim = DB::table('grupos')
                    ->where('torneo_id', $torneoId)
                    ->whereIn('zona', ['16avos final', 'octavos final', 'cuartos final', 'semifinal'])
                    ->where('referencia_config', $ref)
                    ->where(function ($q) {
                        $q->whereNull('jugador_1')->orWhere('jugador_1', 0);
                    })
                    ->where(function ($q) {
                        $q->whereNull('jugador_2')->orWhere('jugador_2', 0);
                    })
                    ->get();

                foreach ($gruposElim as $g) {
                    DB::table('grupos')
                        ->where('id', $g->id)
                        ->update([
                            'jugador_1' => $pareja['jugador_1'] ?? 0,
                            'jugador_2' => $pareja['jugador_2'] ?? 0,
                        ]);
                }
            }
        }
    }

    /**
     * Crea un partido eliminatorio (octavos, 16avos, cuartos, semifinal o final).
     * @return int|null ID del partido creado o null si ya existía
     */
    private function crearPartidoEliminatorio($torneoId, $pareja1, $pareja2, $ronda) {
        // Mapear ronda a nombre de zona
        $zonaRonda = '';
        if ($ronda === '16avos') {
            $zonaRonda = '16avos final';
        } else if ($ronda === 'octavos') {
            $zonaRonda = 'octavos final';
        } else if ($ronda === 'cuartos') {
            $zonaRonda = 'cuartos final';
        } else if ($ronda === 'semifinales') {
            $zonaRonda = 'semifinal';
        } else if ($ronda === 'final') {
            $zonaRonda = 'final';
        }
        
        if ($zonaRonda === '') {
            \Log::warning('crearPartidoEliminatorio: ronda no reconocida: ' . $ronda);
            return null;
        }
        
        // Para partidos vacíos (jugadores 0), verificar cantidad de partidos existentes de esa ronda
        if ($pareja1['jugador_1'] == 0 && $pareja1['jugador_2'] == 0 && 
            $pareja2['jugador_1'] == 0 && $pareja2['jugador_2'] == 0) {
            // Contar cuántos partidos de esta ronda ya existen
            $partidosExistentes = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', $zonaRonda)
                ->whereNotNull('partido_id')
                ->select(DB::raw('COUNT(DISTINCT partido_id) as count'))
                ->value('count');
            
            // Si es semifinales, solo crear 2 máximo
            if ($ronda === 'semifinales' && $partidosExistentes >= 2) {
                return null;
            }
            // Si es final, solo crear 1 máximo
            if ($ronda === 'final' && $partidosExistentes >= 1) {
                return null;
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
            
            if ($partidoExistente) {
                return $partidoExistente->partido_id ?? null; // Ya existe este partido
            }
        }
        
        // Crear el partido
        $partido = $this->crearPartido();
        
        // Crear grupo para pareja 1 (zona = 'cuartos final', 'semifinal', 'final', etc.)
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
        
        \Log::info('crearPartidoEliminatorio: creados partido_id=' . $partido->id . ', zona=' . $zonaRonda . ', grupos id=' . $grupo1->id . ',' . $grupo2->id);
        return $partido->id;
    }

    /**
     * Crea un partido eliminatorio a partir de referencias de configuración (A1, H2, G1-8vos, etc.)
     * sin asignar todavía los jugadores reales. Guarda las referencias en grupos.referencia_config.
     *
     * @param int   $torneoId
     * @param string $ronda    '16avos' | 'octavos' | 'cuartos' | 'semifinales' | 'final'
     * @param array  $partidoCfg ['pareja_1' => 'A1', 'pareja_2' => 'H2', 'dia' => ..., 'horario' => ...]
     * @return int|null ID del partido creado, o null si faltan datos
     */
    private function crearPartidoEliminatorioDesdeReferencias($torneoId, $ronda, array $partidoCfg)
    {
        $ref1 = $partidoCfg['pareja_1'] ?? null;
        $ref2 = $partidoCfg['pareja_2'] ?? null;
        if (!$ref1 || !$ref2) {
            return null;
        }

        // Mapear ronda a nombre de zona
        $zonaRonda = '';
        if ($ronda === '16avos') {
            $zonaRonda = '16avos final';
        } elseif ($ronda === 'octavos') {
            $zonaRonda = 'octavos final';
        } elseif ($ronda === 'cuartos') {
            $zonaRonda = 'cuartos final';
        } elseif ($ronda === 'semifinales' || $ronda === 'semifinal') {
            $zonaRonda = 'semifinal';
        } elseif ($ronda === 'final') {
            $zonaRonda = 'final';
        }

        if ($zonaRonda === '') {
            \Log::warning('crearPartidoEliminatorioDesdeReferencias: ronda no reconocida: ' . $ronda);
            return null;
        }

        // Verificar si ya existe un partido para este torneo/zona con estas referencias (en cualquier orden)
        $partidoExistenteId = DB::table('grupos as g1')
            ->join('grupos as g2', function ($join) {
                $join->on('g1.partido_id', '=', 'g2.partido_id')
                    ->whereRaw('g1.id != g2.id');
            })
            ->where('g1.torneo_id', $torneoId)
            ->where('g2.torneo_id', $torneoId)
            ->where('g1.zona', $zonaRonda)
            ->where('g2.zona', $zonaRonda)
            ->where(function ($q) use ($ref1, $ref2) {
                $q->where(function ($q2) use ($ref1, $ref2) {
                    $q2->where('g1.referencia_config', $ref1)
                       ->where('g2.referencia_config', $ref2);
                })->orWhere(function ($q2) use ($ref1, $ref2) {
                    $q2->where('g1.referencia_config', $ref2)
                       ->where('g2.referencia_config', $ref1);
                });
            })
            ->value('g1.partido_id');

        if ($partidoExistenteId) {
            \Log::info('crearPartidoEliminatorioDesdeReferencias: partido ya existe (partido_id=' . $partidoExistenteId . ', zona=' . $zonaRonda . ' refs=' . $ref1 . ',' . $ref2 . ')');
            return $partidoExistenteId;
        }

        // Crear el partido vacío
        $partido = $this->crearPartido();

        // Por ahora no usamos el día/horario de config para fecha/horario reales
        $fechaDefault = '2000-01-01';
        $horarioDefault = '00:00';

        // Grupo 1 (pareja_1)
        $grupo1 = new Grupo;
        $grupo1->torneo_id = $torneoId;
        $grupo1->zona = $zonaRonda;
        $grupo1->fecha = $fechaDefault;
        $grupo1->horario = $horarioDefault;
        $grupo1->jugador_1 = 0;
        $grupo1->jugador_2 = 0;
        $grupo1->partido_id = $partido->id;
        $grupo1->referencia_config = $ref1;
        $grupo1->save();

        // Grupo 2 (pareja_2)
        $grupo2 = new Grupo;
        $grupo2->torneo_id = $torneoId;
        $grupo2->zona = $zonaRonda;
        $grupo2->fecha = $fechaDefault;
        $grupo2->horario = $horarioDefault;
        $grupo2->jugador_1 = 0;
        $grupo2->jugador_2 = 0;
        $grupo2->partido_id = $partido->id;
        $grupo2->referencia_config = $ref2;
        $grupo2->save();

        \Log::info('crearPartidoEliminatorioDesdeReferencias: creado partido_id=' . $partido->id . ', zona=' . $zonaRonda . ' refs=' . $ref1 . ',' . $ref2);
        return $partido->id;
    }
    
    /**
     * Genera cruces usando la configuración guardada
     */
    private function generarCrucesDesdeConfiguracion($configuracion, $posicionesPorZona, $zonas, array $mapaReferenciaBracket = [], ?int $torneoIdParaGanadoresCuartos = null) {
        $cruces = [];
        $zonasArray = $zonas->toArray();
        sort($zonasArray);
        
        // Mapear zonas a letras (A, B, C, D, etc.) — fallback si no hay mapa desde `grupos.referencia_config`
        $letrasZonas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P'];
        $zonaALetra = [];
        foreach ($zonasArray as $index => $zona) {
            if (isset($letrasZonas[$index])) {
                $zonaALetra[$zona] = $letrasZonas[$index];
            }
        }

        $ganadoresCuartosPorNumero = [];
        if ($torneoIdParaGanadoresCuartos !== null && $torneoIdParaGanadoresCuartos > 0) {
            $ganadoresCuartosPorNumero = $this->obtenerGanadoresCuartosPorOrdenTorneo($torneoIdParaGanadoresCuartos);
        }
        
        // Función para obtener pareja desde una referencia (ej: "A1", "B2", "CU1" = ganador cuartos 1, "G1-4tos")
        // CU1–CU4: ganador partido de cuartos 1–4 (evita confundir con zona C + posición 1). C1–C4 en llave_semifinal legacy = mismo sentido.
        $obtenerParejaDesdeReferencia = function($referencia, $torneoId = null, $contextoRonda = null) use ($posicionesPorZona, $zonaALetra, $mapaReferenciaBracket, $ganadoresCuartosPorNumero) {
            $refNorm = is_string($referencia) ? strtoupper(trim($referencia)) : '';

            $parejaDesdeGanadorCuartos = function (int $n) use ($ganadoresCuartosPorNumero) {
                if ($n < 1 || empty($ganadoresCuartosPorNumero[$n])) {
                    return null;
                }
                $w = $ganadoresCuartosPorNumero[$n];
                return [
                    'jugador_1' => $w['jugador_1'],
                    'jugador_2' => $w['jugador_2'],
                    'zona' => null,
                    'posicion' => $n,
                ];
            };

            if ($refNorm !== '' && preg_match('/^CU([1-4])$/', $refNorm, $m)) {
                return $parejaDesdeGanadorCuartos((int) $m[1]);
            }
            if ($refNorm !== '' && (preg_match('/^G(\d+)-4tos$/', $refNorm, $m) || preg_match('/^G(\d+)-cuartos$/', $refNorm, $m))) {
                return $parejaDesdeGanadorCuartos((int) $m[1]);
            }
            // Semifinal legacy: C1–C4 = ganadores de cuartos (no usar como zona C en esta ronda)
            if (in_array($contextoRonda, ['semifinales'], true) && $refNorm !== '' && preg_match('/^C([1-4])$/', $refNorm, $m)) {
                return $parejaDesdeGanadorCuartos((int) $m[1]);
            }
            
            if (preg_match('/^O(\d+)$/', $refNorm, $m) || preg_match('/^G(\d+)-8vos$/', $refNorm, $m) || preg_match('/^G(\d+)-octavos$/', $refNorm, $m)) {
                return null;
            }
            if (preg_match('/^G(\d+)-semifinal$/', $refNorm, $m)) {
                return null;
            }
            
            // Referencia directa a clasificado (A1, B2, …): primero `grupos` (referencia_config / zona+posicion_grupo)
            if ($refNorm !== '' && preg_match('/^([A-P])(\d+)$/', $refNorm, $matches)) {
                if (!empty($mapaReferenciaBracket[$refNorm])) {
                    $slot = $mapaReferenciaBracket[$refNorm];
                    return [
                        'jugador_1' => $slot['jugador_1'],
                        'jugador_2' => $slot['jugador_2'],
                        'zona' => $slot['zona'] ?? null,
                        'posicion' => $slot['posicion'] ?? (int) $matches[2],
                    ];
                }
                $letra = $matches[1];
                $posicion = (int) $matches[2];
                
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
                                'ronda' => '16avos',
                                'referencia_1' => $partido['pareja_1'] ?? null,
                                'referencia_2' => $partido['pareja_2'] ?? null,
                                'dia' => $partido['dia'] ?? null,
                                'horario' => $partido['horario'] ?? null
                            ];
                        }
                    }
                }
            }
        }
        
        // Luego octavos (si existe)
        if ($configuracion->tiene_8vos_final && $configuracion->llave_8vos) {
            $llave = json_decode($configuracion->llave_8vos, true);
            if ($llave && is_array($llave)) {
                foreach ($llave as $index => $partido) {
                    $pareja1Ref = $partido['pareja_1'] ?? null;
                    $pareja2Ref = $partido['pareja_2'] ?? null;
                    
                    if ($pareja1Ref && $pareja2Ref) {
                        $pareja1 = $obtenerParejaDesdeReferencia($pareja1Ref);
                        $pareja2 = $obtenerParejaDesdeReferencia($pareja2Ref);
                        
                        if ($pareja1 && $pareja2) {
                            $cruces[] = [
                                'id' => 'octavos_' . ($index + 1),
                                'partido_id' => null,
                                'pareja_1' => $pareja1,
                                'pareja_2' => $pareja2,
                                'ronda' => 'octavos',
                                'referencia_1' => $pareja1Ref,
                                'referencia_2' => $pareja2Ref,
                                'dia' => $partido['dia'] ?? null,
                                'horario' => $partido['horario'] ?? null
                            ];
                        }
                    }
                }
            }
        }
        
        // Luego cuartos (si existe) - Incluir cruces incluso si una pareja es null (referencia a ganador)
        if ($configuracion->tiene_4tos_final && $configuracion->llave_4tos) {
            $llave = json_decode($configuracion->llave_4tos, true);
            if ($llave && is_array($llave)) {
                foreach ($llave as $index => $partido) {
                    $pareja1Ref = $partido['pareja_1'] ?? null;
                    $pareja2Ref = $partido['pareja_2'] ?? null;
                    
                    if ($pareja1Ref && $pareja2Ref) {
                        $pareja1 = $obtenerParejaDesdeReferencia($pareja1Ref);
                        $pareja2 = $obtenerParejaDesdeReferencia($pareja2Ref);
                        
                        // Agregar el cruce incluso si una pareja es null (será "Esperando ganador")
                        $cruces[] = [
                            'id' => 'cuartos_' . ($index + 1),
                            'partido_id' => null,
                            'pareja_1' => $pareja1, // Puede ser null
                            'pareja_2' => $pareja2, // Puede ser null
                            'ronda' => 'cuartos',
                            'referencia_1' => $pareja1Ref, // Guardar la referencia original
                            'referencia_2' => $pareja2Ref, // Guardar la referencia original
                            'dia' => $partido['dia'] ?? null,
                            'horario' => $partido['horario'] ?? null
                        ];
                    }
                }
            }
        }
        
        // Semifinales (CU1–CU4 = ganadores cuartos 1–4; en JSON/BD solo CU{n})
        if ($configuracion->llave_semifinal) {
            $llave = json_decode($configuracion->llave_semifinal, true);
            if ($llave && is_array($llave)) {
                foreach ($llave as $index => $partido) {
                    $pareja1Ref = $partido['pareja_1'] ?? null;
                    $pareja2Ref = $partido['pareja_2'] ?? null;
                    if ($pareja1Ref && $pareja2Ref) {
                        $pareja1 = $obtenerParejaDesdeReferencia($pareja1Ref, null, 'semifinales');
                        $pareja2 = $obtenerParejaDesdeReferencia($pareja2Ref, null, 'semifinales');
                        $cruces[] = [
                            'id' => 'semifinales_' . ($index + 1),
                            'partido_id' => null,
                            'pareja_1' => $pareja1,
                            'pareja_2' => $pareja2,
                            'ronda' => 'semifinales',
                            'referencia_1' => $pareja1Ref,
                            'referencia_2' => $pareja2Ref,
                            'dia' => $partido['dia'] ?? null,
                            'horario' => $partido['horario'] ?? null
                        ];
                    }
                }
            }
        }
        
        // Final (referencias G1-semifinal, G2-semifinal)
        if ($configuracion->llave_final) {
            $llave = json_decode($configuracion->llave_final, true);
            if ($llave && is_array($llave)) {
                foreach ($llave as $index => $partido) {
                    $pareja1Ref = $partido['pareja_1'] ?? null;
                    $pareja2Ref = $partido['pareja_2'] ?? null;
                    if ($pareja1Ref && $pareja2Ref) {
                        $pareja1 = $obtenerParejaDesdeReferencia($pareja1Ref);
                        $pareja2 = $obtenerParejaDesdeReferencia($pareja2Ref);
                        $cruces[] = [
                            'id' => 'final_' . ($index + 1),
                            'partido_id' => null,
                            'pareja_1' => $pareja1,
                            'pareja_2' => $pareja2,
                            'ronda' => 'final',
                            'referencia_1' => $pareja1Ref,
                            'referencia_2' => $pareja2Ref,
                            'dia' => $partido['dia'] ?? null,
                            'horario' => $partido['horario'] ?? null
                        ];
                    }
                }
            }
        }
        
        return $cruces;
    }
    
    /**
     * Obtiene el número de octavos (1-8) para un partido según la config.
     */
    private function obtenerNumeroOctavos($torneoId, $partido) {
        $cantidadParejas = $this->obtenerCantidadParejasTorneo($torneoId);
        $config = $this->getConfiguracionCruces($torneoId, $cantidadParejas);
        if (!$config || empty($config->llave_8vos)) return 0;
        $llave = json_decode($config->llave_8vos, true);
        if (!is_array($llave)) return 0;
        $grupos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('partido_id', $partido->id)
            ->where('zona', 'like', 'octavos final%')
            ->orderBy('id')
            ->get();
        if ($grupos->count() < 2) return 0;
        $ref1 = strtoupper(trim($grupos[0]->referencia_config ?? ''));
        $ref2 = strtoupper(trim($grupos[1]->referencia_config ?? ''));
        foreach ($llave as $idx => $cfg) {
            $p1 = strtoupper(trim($cfg['pareja_1'] ?? $cfg['pareja1'] ?? ''));
            $p2 = strtoupper(trim($cfg['pareja_2'] ?? $cfg['pareja2'] ?? ''));
            if (($ref1 === $p1 && $ref2 === $p2) || ($ref1 === $p2 && $ref2 === $p1)) return $idx + 1;
        }
        $pids = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'like', 'octavos final%')
            ->whereNotNull('partido_id')
            ->orderBy('partido_id')
            ->pluck('partido_id')
            ->unique()
            ->values();
        $pos = $pids->search($partido->id);
        return ($pos !== false) ? ($pos + 1) : 0;
    }

    /**
     * Obtiene el número de cuartos (1-4) para un partido según la config.
     */
    private function obtenerNumeroCuartos($torneoId, $partido) {
        $cantidadParejas = $this->obtenerCantidadParejasTorneo($torneoId);
        $config = $this->getConfiguracionCruces($torneoId, $cantidadParejas);
        if (!$config || empty($config->llave_4tos)) return 0;
        $llave = json_decode($config->llave_4tos, true);
        if (!is_array($llave)) return 0;
        $grupos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('partido_id', $partido->id)
            ->where('zona', 'like', 'cuartos final%')
            ->orderBy('id')
            ->get();
        if ($grupos->count() < 2) return 0;
        $ref1 = strtoupper(trim($grupos[0]->referencia_config ?? ''));
        $ref2 = strtoupper(trim($grupos[1]->referencia_config ?? ''));
        foreach ($llave as $idx => $cfg) {
            $p1 = strtoupper(trim($cfg['pareja_1'] ?? $cfg['pareja1'] ?? ''));
            $p2 = strtoupper(trim($cfg['pareja_2'] ?? $cfg['pareja2'] ?? ''));
            if (($ref1 === $p1 && $ref2 === $p2) || ($ref1 === $p2 && $ref2 === $p1)) return $idx + 1;
        }
        $pids = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'like', 'cuartos final%')
            ->whereNotNull('partido_id')
            ->orderBy('partido_id')
            ->pluck('partido_id')
            ->unique()
            ->values();
        $pos = $pids->search($partido->id);
        return ($pos !== false) ? ($pos + 1) : 0;
    }

    /**
     * Obtiene el número de semifinal (1-2) para un partido según la config.
     */
    private function obtenerNumeroSemifinal($torneoId, $partido) {
        $cantidadParejas = $this->obtenerCantidadParejasTorneo($torneoId);
        $config = $this->getConfiguracionCruces($torneoId, $cantidadParejas);
        if (!$config || empty($config->llave_semifinal)) return 0;
        $llave = json_decode($config->llave_semifinal, true);
        if (!is_array($llave)) return 0;
        $grupos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('partido_id', $partido->id)
            ->where('zona', 'like', 'semifinal%')
            ->orderBy('id')
            ->get();
        if ($grupos->count() < 2) return 0;
        $ref1 = strtoupper(trim($grupos[0]->referencia_config ?? ''));
        $ref2 = strtoupper(trim($grupos[1]->referencia_config ?? ''));
        foreach ($llave as $idx => $cfg) {
            $p1 = strtoupper(trim($cfg['pareja_1'] ?? $cfg['pareja1'] ?? ''));
            $p2 = strtoupper(trim($cfg['pareja_2'] ?? $cfg['pareja2'] ?? ''));
            if (($ref1 === $p1 && $ref2 === $p2) || ($ref1 === $p2 && $ref2 === $p1)) return $idx + 1;
        }
        $pids = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'like', 'semifinal%')
            ->whereNotNull('partido_id')
            ->orderBy('partido_id')
            ->pluck('partido_id')
            ->unique()
            ->values();
        $pos = $pids->search($partido->id);
        return ($pos !== false) ? ($pos + 1) : 0;
    }

    /**
     * Obtiene el número DA (1-16) para un partido de 16avos según la config.
     */
    private function obtenerNumeroDA16avos($torneoId, $partido16avos) {
        $cantidadParejas = $this->obtenerCantidadParejasTorneo($torneoId);
        $config = $this->getConfiguracionCruces($torneoId, $cantidadParejas);
        if (!$config || empty($config->llave_16avos)) {
            return 0;
        }
        $llave16avos = json_decode($config->llave_16avos, true);
        if (!is_array($llave16avos)) {
            return 0;
        }
        $grupos16avos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('partido_id', $partido16avos->id)
            ->where('zona', '16avos final')
            ->orderBy('id')
            ->get();
        if ($grupos16avos->count() < 2) {
            return 0;
        }
        $ref1 = trim($grupos16avos[0]->referencia_config ?? '');
        $ref2 = trim($grupos16avos[1]->referencia_config ?? '');
        foreach ($llave16avos as $idx => $partidoCfg) {
            $p1 = trim($partidoCfg['pareja_1'] ?? '');
            $p2 = trim($partidoCfg['pareja_2'] ?? '');
            if (($ref1 === $p1 && $ref2 === $p2) || ($ref1 === $p2 && $ref2 === $p1)) {
                return $idx + 1;
            }
        }
        $partidoIds16avos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', '16avos final')
            ->whereNotNull('partido_id')
            ->orderBy('partido_id')
            ->pluck('partido_id')->unique()->values();
        $pos = $partidoIds16avos->search($partido16avos->id);
        return ($pos !== false) ? ($pos + 1) : 0;
    }

    /**
     * Cuando se guarda un partido de 16avos, asigna el ganador al partido de octavos correspondiente (DA1, DA2, etc.)
     * @return int|null partido_id del octavos actualizado, o null
     */
    private function asignarGanador16avosAOctavos($torneoId, $partido16avos) {
        \Log::info('=== INICIO asignarGanador16avosAOctavos ===');
        $cantidadParejas = $this->obtenerCantidadParejasTorneo($torneoId);
        $config = $this->getConfiguracionCruces($torneoId, $cantidadParejas);
        if (!$config || empty($config->llave_16avos) || empty($config->llave_8vos)) {
            \Log::info('No hay config con llave_16avos y llave_8vos');
            return null;
        }
        $llave16avos = json_decode($config->llave_16avos, true);
        $llave8vos = json_decode($config->llave_8vos, true);
        if (!is_array($llave16avos) || !is_array($llave8vos)) {
            return null;
        }
        $grupos16avos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('partido_id', $partido16avos->id)
            ->where('zona', '16avos final')
            ->orderBy('id')
            ->get();
        if ($grupos16avos->count() < 2) {
            \Log::warning('Partido 16avos sin 2 grupos');
            return null;
        }
        $numeroDA = $this->obtenerNumeroDA16avos($torneoId, $partido16avos);
        if ($numeroDA === 0) {
            \Log::info('No se encontró partido 16avos en llave');
            return null;
        }
        $refGanador = 'DA' . $numeroDA;
        $ganador = $this->determinarGanadorPartido($partido16avos);
        if (!$ganador) {
            \Log::info('Partido 16avos sin ganador claro');
            return null;
        }
        $g1 = $grupos16avos[0];
        $g2 = $grupos16avos[1];
        $ganadorJ1 = ($ganador == 1) ? $g1->jugador_1 : $g2->jugador_1;
        $ganadorJ2 = ($ganador == 1) ? $g1->jugador_2 : $g2->jugador_2;
        $winnerPair = ['jugador_1' => $ganadorJ1, 'jugador_2' => $ganadorJ2];

        $refs = $this->referenciasEquivalentes($refGanador);
        $partidoId = $this->actualizarSlotPorReferencias((int) $torneoId, 'octavos final%', $refs, $winnerPair);
        if ($partidoId) {
            \Log::info('Asignado ganador 16avos ' . $refGanador . ' a octavos (partido_id=' . $partidoId . ', refs=' . implode(',', $refs) . ')');
            return (int) $partidoId;
        }

        \Log::info('No se encontró slot de octavos para ganador 16avos (refs=' . implode(',', $refs) . ')');
        return null;
    }

    /**
     * Crea partidos de cuartos basándose en la configuración cuando se completa un partido de octavos
     * Resuelve las referencias (O1, O2, etc.) a los ganadores reales de octavos
     * @return int|null ID del partido de cuartos creado en esta llamada, o null
     */
    private function crearCuartosDesdeConfiguracionYOctavos($torneoId, $partidoOctavos) {
        \Log::info('=== INICIO crearCuartosDesdeConfiguracionYOctavos ===');
        $partidoIdCreado = null;
        \Log::info('Partido de octavos completado: partido_id=' . $partidoOctavos->id);
        
        // Configuración: en V2 la clasificación sale de `grupos.posicion_grupo` por zona (soporta zonas de 3 y 4 parejas).
        // Evitar recalcular standings acá (era una fuente de inconsistencias).
        $posicionesPorZona = $this->obtenerPosicionesPorZonaDesdeGrupos($torneoId);
        $totalParejasClasificadas = 0;
        foreach ($posicionesPorZona as $z => $pos) {
            $totalParejasClasificadas += is_array($pos) ? count($pos) : 0;
        }
        $configuracionCruces = $this->getConfiguracionCruces($torneoId, $totalParejasClasificadas);
        
        if (!$configuracionCruces || !$configuracionCruces->llave_4tos) {
            \Log::info('No hay configuración de cuartos disponible');
            return;
        }
        
        // Determinar el número de octavos (O1..O8) según la configuración y las referencias guardadas en grupos.referencia_config.
        // Esto es más confiable que intentar reconstruirlo desde posiciones calculadas ad-hoc.
        $numeroPartidoOctavos = $this->obtenerNumeroOctavos($torneoId, $partidoOctavos);
        if ($numeroPartidoOctavos == 0) {
            // Fallback final: usar orden por partido_id cuando no se puede determinar por refs.
            $partidoIds = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'octavos final')
                ->whereNotNull('partido_id')
                ->orderBy('partido_id')
                ->pluck('partido_id')->unique()->values();
            $pos = $partidoIds->search($partidoOctavos->id);
            if ($pos !== false) {
                $numeroPartidoOctavos = $pos + 1;
            }
        }
        
        if ($numeroPartidoOctavos == 0) {
            \Log::warning('No se pudo determinar el número del partido de octavos');
            return;
        }
        
        \Log::info('Partido de octavos número: ' . $numeroPartidoOctavos . ' (partido_id=' . $partidoOctavos->id . ', orden según llave_8vos)');
        
        // Obtener el ganador del partido de octavos
        $gruposPartido = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('partido_id', $partidoOctavos->id)
            ->orderBy('id')
            ->get();
        
        if ($gruposPartido->count() < 2) {
            \Log::warning('No se encontraron los grupos del partido de octavos');
            return;
        }
        
        $ganador = $this->determinarGanadorPartido($partidoOctavos);
        if (!$ganador) {
            \Log::info('Partido de octavos sin ganador claro aún');
            return;
        }
        
        $g1 = $gruposPartido[0];
        $g2 = $gruposPartido[1];
        $ganadorJugador1 = ($ganador == 1) ? $g1->jugador_1 : $g2->jugador_1;
        $ganadorJugador2 = ($ganador == 1) ? $g1->jugador_2 : $g2->jugador_2;
        
        \Log::info('Ganador del partido de octavos ' . $numeroPartidoOctavos . ': J1=' . $ganadorJugador1 . ', J2=' . $ganadorJugador2);
        
        // Decodificar la llave de cuartos
        $llaveCuartos = json_decode($configuracionCruces->llave_4tos, true);
        if (!$llaveCuartos || !is_array($llaveCuartos)) {
            \Log::warning('No se pudo decodificar la llave de cuartos');
            return;
        }
        
        // Buscar en los cruces de cuartos cuáles tienen referencia a este ganador (O1, O2, etc.)
        foreach ($llaveCuartos as $index => $partidoCuartos) {
            $pareja1Ref = $partidoCuartos['pareja_1'] ?? null;
            $pareja2Ref = $partidoCuartos['pareja_2'] ?? null;
            
            // Verificar si alguna de las referencias corresponde a este ganador (O1, G1-8vos, etc.)
            $referenciaCoincide = false;
            $esPareja1 = false;
            
            if (preg_match('/^O(\d+)$/', $pareja1Ref, $matches) || preg_match('/^G(\d+)-8vos$/', $pareja1Ref, $matches) || preg_match('/^G(\d+)-octavos$/', $pareja1Ref, $matches)) {
                $numeroReferencia = (int)$matches[1];
                if ($numeroReferencia == $numeroPartidoOctavos) {
                    $referenciaCoincide = true;
                    $esPareja1 = true;
                }
            }
            
            if (!$referenciaCoincide && (preg_match('/^O(\d+)$/', $pareja2Ref, $matches) || preg_match('/^G(\d+)-8vos$/', $pareja2Ref, $matches) || preg_match('/^G(\d+)-octavos$/', $pareja2Ref, $matches))) {
                $numeroReferencia = (int)$matches[1];
                if ($numeroReferencia == $numeroPartidoOctavos) {
                    $referenciaCoincide = true;
                    $esPareja1 = false;
                }
            }
            
            if (!$referenciaCoincide) {
                continue;
            }
            
            \Log::info('Encontrado cruce de cuartos ' . ($index + 1) . ' que espera ganador de octavos ' . $numeroPartidoOctavos);

            // Buscar el partido de cuartos existente (creado al iniciar torneo) por referencia_config
            $ref1 = trim($pareja1Ref ?? '');
            $ref2 = trim($pareja2Ref ?? '');
            $partidoCuartosPlaceholderId = DB::table('grupos as g1')
                ->join('grupos as g2', function ($join) {
                    $join->on('g1.partido_id', '=', 'g2.partido_id')
                        ->whereRaw('g1.id != g2.id');
                })
                ->where('g1.torneo_id', $torneoId)
                ->where('g2.torneo_id', $torneoId)
                // La zona puede venir como "cuartos final" o "cuartos final|N" (según configuración)
                ->where('g1.zona', 'like', 'cuartos final%')
                ->where('g2.zona', 'like', 'cuartos final%')
                ->where(function ($q) use ($ref1, $ref2) {
                    $q->where(function ($q2) use ($ref1, $ref2) {
                        $q2->whereRaw('TRIM(COALESCE(g1.referencia_config,\'\')) = ?', [$ref1])
                           ->whereRaw('TRIM(COALESCE(g2.referencia_config,\'\')) = ?', [$ref2]);
                    })->orWhere(function ($q2) use ($ref1, $ref2) {
                        $q2->whereRaw('TRIM(COALESCE(g1.referencia_config,\'\')) = ?', [$ref2])
                           ->whereRaw('TRIM(COALESCE(g2.referencia_config,\'\')) = ?', [$ref1]);
                    });
                })
                ->value('g1.partido_id');

            // Fallback: buscar por posición (cuartos 1 = index 0, cuartos 2 = index 1, etc.)
            if (!$partidoCuartosPlaceholderId) {
                $cuartosOrdenados = DB::table('grupos')
                    ->where('torneo_id', $torneoId)
                    ->where('zona', 'like', 'cuartos final%')
                    ->whereNotNull('partido_id')
                    ->select('partido_id', DB::raw('MIN(id) as min_id'))
                    ->groupBy('partido_id')
                    ->orderBy('min_id')
                    ->pluck('partido_id')
                    ->values()
                    ->all();
                if (isset($cuartosOrdenados[$index])) {
                    $partidoCuartosPlaceholderId = $cuartosOrdenados[$index];
                    \Log::info('Cuartos encontrado por posición: index=' . $index . ', partido_id=' . $partidoCuartosPlaceholderId);
                }
            }

            if ($partidoCuartosPlaceholderId) {
                \Log::info('Actualizando partido de cuartos placeholder (partido_id=' . $partidoCuartosPlaceholderId . ') con ganador de octavos.');

                $winnerPair = [
                    'jugador_1' => $ganadorJugador1,
                    'jugador_2' => $ganadorJugador2,
                ];

                // Actualizar solo el lado del ganador en el partido de cuartos; la otra pareja se completará cuando esté disponible
                $gruposCuartos = DB::table('grupos')
                    ->where('torneo_id', $torneoId)
                    ->where('zona', 'like', 'cuartos final%')
                    ->where('partido_id', $partidoCuartosPlaceholderId)
                    ->get();

                // Actualizar el grupo que corresponde a este ganador de octavos (por ref O1, G1-8vos, etc.)
                $refBuscada = $esPareja1 ? $ref1 : $ref2;
                $refsEquiv = $this->referenciasEquivalentes(preg_match('/^O\d+$/i', $refBuscada) ? strtoupper($refBuscada) : ('O' . $numeroPartidoOctavos));
                foreach ($gruposCuartos as $g) {
                    $gRef = $this->normalizarReferenciaConfig($g->referencia_config ?? '');
                    $buscadaNorm = $this->normalizarReferenciaConfig($refBuscada);
                    if ($gRef === $buscadaNorm || in_array($gRef, $refsEquiv, true)) {
                        DB::table('grupos')->where('id', $g->id)->update($winnerPair);
                        break;
                    }
                    // Fallback: ref con mismo número de octavos (O1=G1-8vos, etc.)
                    if (preg_match('/^[OG](\d+)([-]?(8vos|octavos))?$/i', $gRef, $m) && (int)$m[1] === $numeroPartidoOctavos) {
                        DB::table('grupos')->where('id', $g->id)->update($winnerPair);
                        break;
                    }
                }

                $partidoIdCreado = $partidoCuartosPlaceholderId;
                continue;
            }

            // No se encontró partido de cuartos existente. Los partidos se crean solo al iniciar torneo.
            \Log::warning('No se encontró partido de cuartos para actualizar (refs: ' . $ref1 . ', ' . $ref2 . '). Los cuartos se crean al iniciar el torneo.');
        }
        
        \Log::info('=== FIN crearCuartosDesdeConfiguracionYOctavos ===');
        return $partidoIdCreado;
    }

    /**
     * Crea partidos de semifinales desde la configuración cuando se completa un partido de cuartos.
     * Resuelve CU1–CU4 como ganador de cuartos 1..4 (en llave/BD solo forma canónica CU{n}; C1 / G-4TOS al leer se mapean a CU).
     */
    private function crearSemifinalesDesdeConfiguracionYCuartos($torneoId, $partidoCuartos) {
        \Log::info('=== INICIO crearSemifinalesDesdeConfiguracionYCuartos ===');
        $partidoIdCreado = null;

        $config = DB::table('configuracion_cruces_puntuables')
            ->where(function ($q) use ($torneoId) {
                $q->where('torneo_id', $torneoId)->orWhereNull('torneo_id');
            })
            ->whereNotNull('llave_semifinal')
            ->orderByRaw('torneo_id IS NOT NULL DESC')
            ->first();
        if (!$config || !$config->llave_semifinal) {
            \Log::warning('crearSemifinalesDesdeConfiguracionYCuartos: sin fila en configuracion_cruces_puntuables con llave_semifinal (torneo_id=' . $torneoId . '). Revisar config o cantidad_parejas.');
            return $partidoIdCreado;
        }
        \Log::info('crearSemifinalesDesdeConfiguracionYCuartos: config id=' . ($config->id ?? '?') . ' torneo_id config=' . ($config->torneo_id ?? 'null') . ' llave_semifinal(len)=' . strlen((string) $config->llave_semifinal));

        $partidosCuartosIds = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'like', 'cuartos final%')
            ->whereNotNull('partido_id')
            ->orderBy('partido_id')
            ->pluck('partido_id')
            ->unique()
            ->values();
        if ($partidosCuartosIds->isEmpty()) {
            \Log::warning('crearSemifinalesDesdeConfiguracionYCuartos: no hay partidos de cuartos (zona like cuartos final%) para torneo_id=' . $torneoId);
            return $partidoIdCreado;
        }
        $partidosCuartos = DB::table('partidos')->whereIn('id', $partidosCuartosIds->all())->get()->keyBy('id');
        $partidosCuartosOrdenados = $partidosCuartosIds->map(function ($pid) use ($partidosCuartos) {
            return $partidosCuartos->get($pid);
        })->filter()->values();

        // Determinar número de cuartos según la configuración (preferido).
        // Esto evita desalineaciones si el orden por partido_id no coincide con CU1..CU4.
        $numeroPartidoCuartos = $this->obtenerNumeroCuartos($torneoId, $partidoCuartos);
        if ($numeroPartidoCuartos <= 0) {
            // Fallback: posición por partido_id (último recurso).
            $pos = $partidosCuartosIds->search($partidoCuartos->id);
            if ($pos === false) {
                \Log::warning('crearSemifinalesDesdeConfiguracionYCuartos: partido ' . $partidoCuartos->id . ' no está en partidosCuartosIds: ' . json_encode($partidosCuartosIds->all()));
                return $partidoIdCreado;
            }
            $numeroPartidoCuartos = $pos + 1;
        }
        \Log::info('crearSemifinalesDesdeConfiguracionYCuartos: numeroPartidoCuartos (CU#)=' . $numeroPartidoCuartos . ' partido_id=' . $partidoCuartos->id);

        $ganador = $this->determinarGanadorPartido($partidoCuartos);
        if (!$ganador) {
            \Log::warning('crearSemifinalesDesdeConfiguracionYCuartos: determinarGanadorPartido devolvió null (partido_id=' . $partidoCuartos->id . ')');
            return $partidoIdCreado;
        }
        $gruposPartido = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('partido_id', $partidoCuartos->id)
            ->orderBy('id')
            ->get();
        if ($gruposPartido->count() < 2) {
            \Log::warning('crearSemifinalesDesdeConfiguracionYCuartos: partido de cuartos con menos de 2 grupos (count=' . $gruposPartido->count() . ' partido_id=' . $partidoCuartos->id . ')');
            return $partidoIdCreado;
        }
        $g1 = $gruposPartido[0];
        $g2 = $gruposPartido[1];
        $ganadorJugador1 = ($ganador == 1) ? $g1->jugador_1 : $g2->jugador_1;
        $ganadorJugador2 = ($ganador == 1) ? $g1->jugador_2 : $g2->jugador_2;
        $winnerPair = ['jugador_1' => $ganadorJugador1, 'jugador_2' => $ganadorJugador2];
        \Log::info('crearSemifinalesDesdeConfiguracionYCuartos: ganador par=' . $ganador . ' winnerPair=' . json_encode($winnerPair));

        $llaveSemi = json_decode($config->llave_semifinal, true);
        if (!$llaveSemi || !is_array($llaveSemi)) {
            \Log::warning('crearSemifinalesDesdeConfiguracionYCuartos: llave_semifinal JSON inválido o no es array. Raw prefix=' . substr((string) $config->llave_semifinal, 0, 200));
            return $partidoIdCreado;
        }
        \Log::info('crearSemifinalesDesdeConfiguracionYCuartos: llave_semifinal decodificada, cruces count=' . count($llaveSemi) . ' JSON=' . json_encode($llaveSemi));

        foreach ($llaveSemi as $index => $partidoSemi) {
            $pareja1Ref = $partidoSemi['pareja_1'] ?? null;
            $pareja2Ref = $partidoSemi['pareja_2'] ?? null;
            $referenciaCoincide = false;
            $esPareja1 = false;

            $cu1 = $this->referenciaGanadorCuartosACU($pareja1Ref);
            $cu2 = $this->referenciaGanadorCuartosACU($pareja2Ref);
            $nCu1 = null;
            $nCu2 = null;
            if ($cu1 && preg_match('/^CU(\d+)$/', $cu1, $m)) {
                $nCu1 = (int) $m[1];
            }
            if ($cu2 && preg_match('/^CU(\d+)$/', $cu2, $m)) {
                $nCu2 = (int) $m[1];
            }
            if ($nCu1 === $numeroPartidoCuartos) {
                $referenciaCoincide = true;
                $esPareja1 = true;
            } elseif ($nCu2 === $numeroPartidoCuartos) {
                $referenciaCoincide = true;
                $esPareja1 = false;
            }
            if (!$referenciaCoincide) {
                \Log::info('crearSemifinales: cruce index=' . $index . ' no aplica a CU' . $numeroPartidoCuartos . ' pareja1=' . json_encode($pareja1Ref) . ' pareja2=' . json_encode($pareja2Ref) . ' nCu1=' . json_encode($nCu1) . ' nCu2=' . json_encode($nCu2));
                continue;
            }

            // Actualizar semifinal placeholder existente (creado al comenzar el torneo) por referencias_config.
            // Igual criterio que octavos->cuartos: actualizar solo el lado del ganador, sin crear partidos nuevos.
            $ref1 = $this->referenciaGanadorCuartosACU($pareja1Ref) ?? strtoupper(trim((string) ($pareja1Ref ?? '')));
            $ref2 = $this->referenciaGanadorCuartosACU($pareja2Ref) ?? strtoupper(trim((string) ($pareja2Ref ?? '')));
            if ($ref1 === '' || $ref2 === '') {
                \Log::warning('crearSemifinales: cruce index=' . $index . ' ref1/ref2 vacíos tras normalizar. pareja1=' . json_encode($pareja1Ref) . ' pareja2=' . json_encode($pareja2Ref));
                continue;
            }
            \Log::info('crearSemifinales: MATCH cuarto CU' . $numeroPartidoCuartos . ' index_lla=' . $index . ' ref1=' . $ref1 . ' ref2=' . $ref2 . ' esPareja1=' . ($esPareja1 ? '1' : '0'));

            $semifinalPlaceholderId = DB::table('grupos as g1')
                ->join('grupos as g2', function ($join) {
                    $join->on('g1.partido_id', '=', 'g2.partido_id')
                        ->whereRaw('g1.id != g2.id');
                })
                ->where('g1.torneo_id', $torneoId)
                ->where('g2.torneo_id', $torneoId)
                // La zona puede venir como "semifinal" o "semifinal|N" (según configuración)
                ->where('g1.zona', 'like', 'semifinal%')
                ->where('g2.zona', 'like', 'semifinal%')
                ->where(function ($q) use ($ref1, $ref2) {
                    $q->where(function ($q2) use ($ref1, $ref2) {
                        $q2->whereRaw('TRIM(COALESCE(g1.referencia_config,\'\')) = ?', [$ref1])
                           ->whereRaw('TRIM(COALESCE(g2.referencia_config,\'\')) = ?', [$ref2]);
                    })->orWhere(function ($q2) use ($ref1, $ref2) {
                        $q2->whereRaw('TRIM(COALESCE(g1.referencia_config,\'\')) = ?', [$ref2])
                           ->whereRaw('TRIM(COALESCE(g2.referencia_config,\'\')) = ?', [$ref1]);
                    });
                })
                ->value('g1.partido_id');
            \Log::info('crearSemifinales: join ref1=' . $ref1 . ' ref2=' . $ref2 . ' => semifinalPlaceholderId=' . json_encode($semifinalPlaceholderId));

            // Fallback por posición (semi 1 = index 0, semi 2 = index 1)
            if (!$semifinalPlaceholderId) {
                $semisOrdenados = DB::table('grupos')
                    ->where('torneo_id', $torneoId)
                    ->where('zona', 'like', 'semifinal%')
                    ->whereNotNull('partido_id')
                    ->select('partido_id', DB::raw('MIN(id) as min_id'))
                    ->groupBy('partido_id')
                    ->orderBy('min_id')
                    ->pluck('partido_id')
                    ->values()
                    ->all();
                if (isset($semisOrdenados[$index])) {
                    $semifinalPlaceholderId = $semisOrdenados[$index];
                    \Log::info('crearSemifinales: fallback por posición index=' . $index . ' partido_id=' . $semifinalPlaceholderId);
                }
            }

            if (!$semifinalPlaceholderId) {
                \Log::warning('crearSemifinales: no se encontró partido semifinal (join+fallback) refs=' . $ref1 . ' / ' . $ref2);
                continue;
            }

            $gruposSemi = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'like', 'semifinal%')
                ->where('partido_id', $semifinalPlaceholderId)
                ->orderBy('id')
                ->get();

            $refBuscada = $esPareja1 ? $ref1 : $ref2;
            \Log::info('crearSemifinales: gruposSemi partido_id=' . $semifinalPlaceholderId . ' filas=' . $gruposSemi->count() . ' refBuscada=' . $refBuscada . ' snapshot=' . json_encode($gruposSemi->map(function ($g) {
                return ['id' => $g->id, 'referencia_config' => $g->referencia_config, 'j1' => $g->jugador_1, 'j2' => $g->jugador_2];
            })->all()));
            foreach ($gruposSemi as $g) {
                $gRef = strtoupper(trim((string) ($g->referencia_config ?? '')));
                if ($gRef === $refBuscada) {
                    $affected = DB::table('grupos')->where('id', $g->id)->update($winnerPair);
                    $partidoIdCreado = (int) $semifinalPlaceholderId;
                    \Log::info('crearSemifinales: UPDATE por ref exacta id=' . $g->id . ' affected=' . $affected);
                    break;
                }
                $gCu = $this->referenciaGanadorCuartosACU($gRef);
                if ($gCu && $gCu === ('CU' . $numeroPartidoCuartos)) {
                    $affected = DB::table('grupos')->where('id', $g->id)->update($winnerPair);
                    $partidoIdCreado = (int) $semifinalPlaceholderId;
                    \Log::info('crearSemifinales: UPDATE por ref CU# id=' . $g->id . ' affected=' . $affected);
                    break;
                }
            }
        }

        // Fallback: slot de semifinal con referencia CU{n} (ganador de cuartos n).
        if (!$partidoIdCreado) {
            $refsPosibles = [
                'CU' . $numeroPartidoCuartos,
            ];

            $slot = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'like', 'semifinal%')
                ->where(function ($q) use ($refsPosibles) {
                    foreach ($refsPosibles as $ref) {
                        $q->orWhereRaw('UPPER(TRIM(COALESCE(referencia_config, \'\'))) = ?', [$ref]);
                    }
                })
                ->orderBy('id')
                ->first();

            if ($slot) {
                $affected = DB::table('grupos')->where('id', $slot->id)->update($winnerPair);
                $partidoIdCreado = (int) ($slot->partido_id ?? 0) ?: null;
                \Log::info('crearSemifinales: Fallback UPDATE slot ref=' . ($slot->referencia_config ?? '') . ' grupo_id=' . $slot->id . ' partido_id=' . ($slot->partido_id ?? '') . ' affected=' . $affected);
            } else {
                \Log::warning('crearSemifinales: Fallback no encontró fila con referencia_config IN (' . implode(',', $refsPosibles) . ') en zona semifinal. Torneo=' . $torneoId);
            }
        }

        \Log::info('=== FIN crearSemifinalesDesdeConfiguracionYCuartos partidoIdCreado=' . json_encode($partidoIdCreado) . ' ===');
        return $partidoIdCreado;
    }

    /**
     * Crea la final desde la configuración cuando se completa un partido de semifinales.
     * Resuelve G1-semifinal, G2-semifinal ANTES que referencia directa.
     */
    private function crearFinalDesdeConfiguracionYSemifinales($torneoId, $partidoSemifinal) {
        \Log::info('=== INICIO crearFinalDesdeConfiguracionYSemifinales ===');
        $partidoIdCreado = null;

        $config = DB::table('configuracion_cruces_puntuables')
            ->where(function ($q) use ($torneoId) {
                $q->where('torneo_id', $torneoId)->orWhereNull('torneo_id');
            })
            ->whereNotNull('llave_final')
            ->orderByRaw('torneo_id IS NOT NULL DESC')
            ->first();
        if (!$config || !$config->llave_final) {
            \Log::info('No hay configuración llave_final; usando lógica legacy');
            return $partidoIdCreado;
        }

        $partidosSemiIds = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'like', 'semifinal%')
            ->whereNotNull('partido_id')
            ->orderBy('partido_id')
            ->pluck('partido_id')
            ->unique()
            ->values();
        if ($partidosSemiIds->count() < 2) {
            \Log::info('Aún no hay 2 partidos de semifinal');
            return $partidoIdCreado;
        }
        $partidosSemi = DB::table('partidos')->whereIn('id', $partidosSemiIds->all())->get()->keyBy('id');
        $partidosSemiOrdenados = $partidosSemiIds->map(function ($pid) use ($partidosSemi) {
            return $partidosSemi->get($pid);
        })->filter()->values();

        $numeroPartidoSemi = $partidosSemiIds->search($partidoSemifinal->id);
        if ($numeroPartidoSemi === false) return $partidoIdCreado;
        $numeroPartidoSemi += 1;

        $ganador = $this->determinarGanadorPartido($partidoSemifinal);
        if (!$ganador) return $partidoIdCreado;
        $gruposPartido = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('partido_id', $partidoSemifinal->id)
            ->orderBy('id')
            ->get();
        if ($gruposPartido->count() < 2) return $partidoIdCreado;
        $g1 = $gruposPartido[0];
        $g2 = $gruposPartido[1];
        $ganadorJugador1 = ($ganador == 1) ? $g1->jugador_1 : $g2->jugador_1;
        $ganadorJugador2 = ($ganador == 1) ? $g1->jugador_2 : $g2->jugador_2;

        // En vez de “rellenar la final completa” (que puede invertir parejas si el orden de grupos no coincide),
        // actualizar SOLAMENTE el slot de final que referencia al ganador de ESTA semifinal.
        $winnerPair = ['jugador_1' => $ganadorJugador1, 'jugador_2' => $ganadorJugador2];
        $refsWinner = $this->referenciasEquivalentes('S' . $numeroPartidoSemi);
        $partidoIdCreado = $this->actualizarSlotPorReferencias((int) $torneoId, 'final%', $refsWinner, $winnerPair);
        if ($partidoIdCreado) {
            \Log::info('Final slot actualizado para ganador de semifinal S' . $numeroPartidoSemi . ' (partido_id=' . $partidoIdCreado . ', refs=' . implode(',', $refsWinner) . ')');
        } else {
            \Log::warning('No se encontró slot de final para ganador de semifinal S' . $numeroPartidoSemi . ' (refs=' . implode(',', $refsWinner) . ')');
        }
        \Log::info('=== FIN crearFinalDesdeConfiguracionYSemifinales ===');
        return $partidoIdCreado;
    }

    /**
     * GET: Misma funcionalidad que getParticipantesTorneoPuntuable.
     * Devuelve jugadores que participan en el torneo (desde grupos) y referencias de puntuación.
     */
    public function obtenerParticipantesTorneoPuntuable(Request $request) {
        try {
            return $this->getParticipantesTorneoPuntuable($request);
        } catch (\Exception $e) {
            \Log::error('Error al obtener participantes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
