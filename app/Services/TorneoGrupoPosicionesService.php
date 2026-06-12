<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Cálculo y persistencia de posiciones de fase de grupos en `grupos.posicion_grupo`.
 *
 * Nota: la lógica está alineada con `HomeController@calcularPosicionesZona` para evitar divergencias.
 */
class TorneoGrupoPosicionesService
{
    public static function parejaKey($j1, $j2): string
    {
        $a = (int) $j1;
        $b = (int) $j2;
        $min = min($a, $b);
        $max = max($a, $b);
        return $min . '_' . $max;
    }

    /**
     * Determina el ganador de un partido basándose en los sets.
     * Retorna 1 si ganó pareja_1, 2 si ganó pareja_2 (misma semántica que HomeController).
     */
    public static function determinarGanadorPartido($partido): int
    {
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

        if ($partido->pareja_1_set_3 > 0 || $partido->pareja_2_set_3 > 0) {
            if ($partido->pareja_1_set_3 > $partido->pareja_2_set_3) {
                $setsGanadosP1++;
            } else if ($partido->pareja_2_set_3 > $partido->pareja_1_set_3) {
                $setsGanadosP2++;
            }
        }

        if ($setsGanadosP1 == $setsGanadosP2) {
            if ($partido->pareja_1_set_super_tie_break > $partido->pareja_2_set_super_tie_break) {
                return 1;
            } else if ($partido->pareja_2_set_super_tie_break > $partido->pareja_1_set_super_tie_break) {
                return 2;
            }
        }

        return $setsGanadosP1 > $setsGanadosP2 ? 1 : 2;
    }

    private static function partidoTieneResultado($partido): bool
    {
        return ($partido->pareja_1_set_1 > 0 || $partido->pareja_2_set_1 > 0) ||
            ($partido->pareja_1_set_2 > 0 || $partido->pareja_2_set_2 > 0) ||
            ($partido->pareja_1_set_3 > 0 || $partido->pareja_2_set_3 > 0) ||
            ($partido->pareja_1_set_super_tie_break > 0 || $partido->pareja_2_set_super_tie_break > 0);
    }

    public static function zonaGruposTodosPartidosCompletos(int $torneoId, string $zona): bool
    {
        $todosPartidos = DB::table('grupos')
            ->join('partidos', 'grupos.partido_id', '=', 'partidos.id')
            ->where('grupos.torneo_id', $torneoId)
            ->where(function ($query) use ($zona) {
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
            if (self::partidoTieneResultado($partido)) {
                $partidosCompletos++;
            }
        }

        return $totalPartidos > 0 && $partidosCompletos == $totalPartidos;
    }

    /**
     * Persiste `posicion_grupo` en la zona base para las parejas del arreglo ordenado.
     *
     * @param array<int,array<string,mixed>> $posiciones
     */
    public static function persistirPosicionesEnZonaBase(int $torneoId, string $zona, array $posiciones): void
    {
        foreach (array_values($posiciones) as $i => $pareja) {
            $j1 = (int) ($pareja['jugador_1'] ?? 0);
            $j2 = (int) ($pareja['jugador_2'] ?? 0);
            if ($j1 <= 0 || $j2 <= 0) {
                continue;
            }

            DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', $zona)
                ->where(function ($q) use ($j1, $j2) {
                    $q->where(function ($q2) use ($j1, $j2) {
                        $q2->where('jugador_1', $j1)->where('jugador_2', $j2);
                    })->orWhere(function ($q2) use ($j1, $j2) {
                        $q2->where('jugador_1', $j2)->where('jugador_2', $j1);
                    });
                })
                ->update(['posicion_grupo' => $i + 1]);
        }
    }

    /**
     * Calcula posiciones (misma lógica que HomeController@calcularPosicionesZona) y persiste en `grupos.posicion_grupo`.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function calcularYPersistirPosicionesZona(int $torneoId, string $zona): array
    {
        if (!self::zonaGruposTodosPartidosCompletos($torneoId, $zona)) {
            return [];
        }

        $parejaKey = [self::class, 'parejaKey'];

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

        $grupos = DB::table('grupos')
            ->where('grupos.torneo_id', $torneoId)
            ->where('grupos.zona', $zona)
            ->select('grupos.jugador_1', 'grupos.jugador_2', 'grupos.partido_id')
            ->get();

        $parejas = [];
        foreach ($grupos as $grupo) {
            $key = $parejaKey($grupo->jugador_1, $grupo->jugador_2);
            if (!isset($parejas[$key])) {
                $parejas[$key] = [
                    'jugador_1' => (int) $grupo->jugador_1,
                    'jugador_2' => (int) $grupo->jugador_2,
                    'partidos_jugados' => 0,
                    'partidos_ganados' => 0,
                    'partidos_perdidos' => 0,
                    'puntos' => 0,
                    'sets_ganados' => 0,
                    'sets_perdidos' => 0,
                    'juegos_ganados' => 0,
                    'juegos_perdidos' => 0,
                    'partidos_directos' => []
                ];
            }
        }

        foreach ($partidos as $partido) {
            $pareja1 = null;
            $pareja2 = null;

            foreach ($grupos as $grupo) {
                if ($grupo->partido_id == $partido->partido_id) {
                    $key = $parejaKey($grupo->jugador_1, $grupo->jugador_2);
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

                $setsGanadosP1 = 0;
                $setsGanadosP2 = 0;

                if (($partido->pareja_1_set_1 ?? 0) > ($partido->pareja_2_set_1 ?? 0)) $setsGanadosP1++;
                else if (($partido->pareja_2_set_1 ?? 0) > ($partido->pareja_1_set_1 ?? 0)) $setsGanadosP2++;

                if (($partido->pareja_1_set_2 ?? 0) > ($partido->pareja_2_set_2 ?? 0)) $setsGanadosP1++;
                else if (($partido->pareja_2_set_2 ?? 0) > ($partido->pareja_1_set_2 ?? 0)) $setsGanadosP2++;

                if (($partido->pareja_1_set_super_tie_break ?? 0) > 0 || ($partido->pareja_2_set_super_tie_break ?? 0) > 0) {
                    if (($partido->pareja_1_set_super_tie_break ?? 0) > ($partido->pareja_2_set_super_tie_break ?? 0)) {
                        $setsGanadosP1 = 2;
                        $setsGanadosP2 = 1;
                    } else if (($partido->pareja_2_set_super_tie_break ?? 0) > ($partido->pareja_1_set_super_tie_break ?? 0)) {
                        $setsGanadosP1 = 1;
                        $setsGanadosP2 = 2;
                    }
                } else if (($partido->pareja_1_set_3 ?? 0) > 0 || ($partido->pareja_2_set_3 ?? 0) > 0) {
                    if (($partido->pareja_1_set_3 ?? 0) > ($partido->pareja_2_set_3 ?? 0)) $setsGanadosP1++;
                    else if (($partido->pareja_2_set_3 ?? 0) > ($partido->pareja_1_set_3 ?? 0)) $setsGanadosP2++;
                }

                $juegosGanadosP1 = $partido->pareja_1_set_1 + $partido->pareja_1_set_2;
                $juegosGanadosP2 = $partido->pareja_2_set_1 + $partido->pareja_2_set_2;

                if ($partido->pareja_1_set_3 > 0 || $partido->pareja_2_set_3 > 0) {
                    $juegosGanadosP1 += $partido->pareja_1_set_3;
                    $juegosGanadosP2 += $partido->pareja_2_set_3;
                }

                $parejas[$pareja1]['sets_ganados'] += $setsGanadosP1;
                $parejas[$pareja1]['sets_perdidos'] += $setsGanadosP2;
                $parejas[$pareja2]['sets_ganados'] += $setsGanadosP2;
                $parejas[$pareja2]['sets_perdidos'] += $setsGanadosP1;

                $parejas[$pareja1]['juegos_ganados'] += $juegosGanadosP1;
                $parejas[$pareja1]['juegos_perdidos'] += $juegosGanadosP2;
                $parejas[$pareja2]['juegos_ganados'] += $juegosGanadosP2;
                $parejas[$pareja2]['juegos_perdidos'] += $juegosGanadosP1;

                if ($setsGanadosP1 > $setsGanadosP2) {
                    $parejas[$pareja1]['partidos_ganados']++;
                    $parejas[$pareja2]['partidos_perdidos']++;
                    $parejas[$pareja1]['puntos'] += 2;
                    $parejas[$pareja2]['puntos'] += 1;

                    $parejas[$pareja1]['partidos_directos'][$pareja2] = [
                        'ganado' => true,
                        'sets' => $setsGanadosP1 . '-' . $setsGanadosP2,
                        'sets_ganados' => $setsGanadosP1,
                        'sets_perdidos' => $setsGanadosP2,
                        'juegos_ganados' => $juegosGanadosP1,
                        'juegos_perdidos' => $juegosGanadosP2
                    ];
                    $parejas[$pareja2]['partidos_directos'][$pareja1] = [
                        'ganado' => false,
                        'sets' => $setsGanadosP2 . '-' . $setsGanadosP1,
                        'sets_ganados' => $setsGanadosP2,
                        'sets_perdidos' => $setsGanadosP1,
                        'juegos_ganados' => $juegosGanadosP2,
                        'juegos_perdidos' => $juegosGanadosP1
                    ];
                } else if ($setsGanadosP2 > $setsGanadosP1) {
                    $parejas[$pareja2]['partidos_ganados']++;
                    $parejas[$pareja1]['partidos_perdidos']++;
                    $parejas[$pareja2]['puntos'] += 2;
                    $parejas[$pareja1]['puntos'] += 1;

                    $parejas[$pareja2]['partidos_directos'][$pareja1] = [
                        'ganado' => true,
                        'sets' => $setsGanadosP2 . '-' . $setsGanadosP1,
                        'sets_ganados' => $setsGanadosP2,
                        'sets_perdidos' => $setsGanadosP1,
                        'juegos_ganados' => $juegosGanadosP2,
                        'juegos_perdidos' => $juegosGanadosP1
                    ];
                    $parejas[$pareja1]['partidos_directos'][$pareja2] = [
                        'ganado' => false,
                        'sets' => $setsGanadosP1 . '-' . $setsGanadosP2,
                        'sets_ganados' => $setsGanadosP1,
                        'sets_perdidos' => $setsGanadosP2,
                        'juegos_ganados' => $juegosGanadosP1,
                        'juegos_perdidos' => $juegosGanadosP2
                    ];
                }
            }
        }

        foreach ($parejas as $key => $pareja) {
            $parejas[$key]['key'] = $key;
            $parejas[$key]['victorias_2_0'] = 0;
            $parejas[$key]['victorias_2_1'] = 0;
        }

        foreach ($partidos as $partido) {
            $pareja1 = null;
            $pareja2 = null;

            foreach ($grupos as $grupo) {
                if ($grupo->partido_id == $partido->partido_id) {
                    $key = $parejaKey($grupo->jugador_1, $grupo->jugador_2);
                    if (!$pareja1) {
                        $pareja1 = $key;
                    } else if ($key != $pareja1) {
                        $pareja2 = $key;
                        break;
                    }
                }
            }

            if ($pareja1 && $pareja2 && isset($parejas[$pareja1]) && isset($parejas[$pareja2])) {
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

                if ($setsGanadosP1 == 2 && $setsGanadosP2 == 0) {
                    $parejas[$pareja1]['victorias_2_0']++;
                } else if ($setsGanadosP1 == 2 && $setsGanadosP2 == 1) {
                    $parejas[$pareja1]['victorias_2_1']++;
                } else if ($setsGanadosP2 == 2 && $setsGanadosP1 == 0) {
                    $parejas[$pareja2]['victorias_2_0']++;
                } else if ($setsGanadosP2 == 2 && $setsGanadosP1 == 1) {
                    $parejas[$pareja2]['victorias_2_1']++;
                }
            }
        }

        foreach ($parejas as $key => $pareja) {
            $parejas[$key]['diferencia_sets'] = ($pareja['sets_ganados'] ?? 0) - ($pareja['sets_perdidos'] ?? 0);
            $parejas[$key]['diferencia_games'] = ($pareja['puntos_ganados'] ?? 0) - ($pareja['puntos_perdidos'] ?? 0);
            if (count($parejas) === 3 && !isset($parejas[$key]['puntos'])) {
                $parejas[$key]['puntos'] = 0;
            }
        }

        $numParejas = count($parejas);

        $partidoGanador = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'ganador ' . $zona)
            ->whereNotNull('partido_id')
            ->select('partido_id')
            ->distinct()
            ->first();

        $partidoPerdedor = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'perdedor ' . $zona)
            ->whereNotNull('partido_id')
            ->select('partido_id')
            ->distinct()
            ->first();

        $esZonaEliminatoria = ($partidoGanador && $partidoPerdedor);

        if ($esZonaEliminatoria) {
            $posiciones = [];

            $gruposElim = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where(function ($q) use ($zona) {
                    $q->where('zona', $zona)
                        ->orWhere('zona', 'ganador ' . $zona)
                        ->orWhere('zona', 'perdedor ' . $zona);
                })
                ->whereNotNull('partido_id')
                ->where('jugador_1', '!=', 0)
                ->where('jugador_2', '!=', 0)
                ->select('jugador_1', 'jugador_2', 'partido_id')
                ->get();

            $partidosElim = DB::table('grupos')
                ->join('partidos', 'grupos.partido_id', '=', 'partidos.id')
                ->where('grupos.torneo_id', $torneoId)
                ->where(function ($q) use ($zona) {
                    $q->where('grupos.zona', $zona)
                        ->orWhere('grupos.zona', 'ganador ' . $zona)
                        ->orWhere('grupos.zona', 'perdedor ' . $zona);
                })
                ->whereNotNull('grupos.partido_id')
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

            $parejasElim = [];
            foreach ($gruposElim as $g) {
                $k = $parejaKey($g->jugador_1, $g->jugador_2);
                if (!isset($parejasElim[$k])) {
                    $parejasElim[$k] = [
                        'jugador_1' => (int) $g->jugador_1,
                        'jugador_2' => (int) $g->jugador_2,
                        'partidos_jugados' => 0,
                        'partidos_ganados' => 0,
                        'partidos_perdidos' => 0,
                        'puntos' => 0,
                        'sets_ganados' => 0,
                        'sets_perdidos' => 0,
                        'juegos_ganados' => 0,
                        'juegos_perdidos' => 0,
                        'partidos_directos' => []
                    ];
                }
            }

            foreach ($partidosElim as $p) {
                $k1 = null;
                $k2 = null;
                foreach ($gruposElim as $g) {
                    if ($g->partido_id == $p->partido_id) {
                        $k = $parejaKey($g->jugador_1, $g->jugador_2);
                        if (!$k1) $k1 = $k;
                        else if ($k != $k1) {
                            $k2 = $k;
                            break;
                        }
                    }
                }
                if (!$k1 || !$k2 || !isset($parejasElim[$k1]) || !isset($parejasElim[$k2])) {
                    continue;
                }

                $parejasElim[$k1]['partidos_jugados']++;
                $parejasElim[$k2]['partidos_jugados']++;

                $setsGanadosP1 = 0;
                $setsGanadosP2 = 0;

                if (($p->pareja_1_set_1 ?? 0) > ($p->pareja_2_set_1 ?? 0)) $setsGanadosP1++;
                else if (($p->pareja_2_set_1 ?? 0) > ($p->pareja_1_set_1 ?? 0)) $setsGanadosP2++;

                if (($p->pareja_1_set_2 ?? 0) > ($p->pareja_2_set_2 ?? 0)) $setsGanadosP1++;
                else if (($p->pareja_2_set_2 ?? 0) > ($p->pareja_1_set_2 ?? 0)) $setsGanadosP2++;

                if (($p->pareja_1_set_super_tie_break ?? 0) > 0 || ($p->pareja_2_set_super_tie_break ?? 0) > 0) {
                    if (($p->pareja_1_set_super_tie_break ?? 0) > ($p->pareja_2_set_super_tie_break ?? 0)) {
                        $setsGanadosP1 = 2;
                        $setsGanadosP2 = 1;
                    } else if (($p->pareja_2_set_super_tie_break ?? 0) > ($p->pareja_1_set_super_tie_break ?? 0)) {
                        $setsGanadosP1 = 1;
                        $setsGanadosP2 = 2;
                    }
                } else if (($p->pareja_1_set_3 ?? 0) > 0 || ($p->pareja_2_set_3 ?? 0) > 0) {
                    if (($p->pareja_1_set_3 ?? 0) > ($p->pareja_2_set_3 ?? 0)) $setsGanadosP1++;
                    else if (($p->pareja_2_set_3 ?? 0) > ($p->pareja_1_set_3 ?? 0)) $setsGanadosP2++;
                }

                $juegosGanadosP1 = (int)($p->pareja_1_set_1 ?? 0) + (int)($p->pareja_1_set_2 ?? 0);
                $juegosGanadosP2 = (int)($p->pareja_2_set_1 ?? 0) + (int)($p->pareja_2_set_2 ?? 0);
                if (($p->pareja_1_set_3 ?? 0) > 0 || ($p->pareja_2_set_3 ?? 0) > 0) {
                    $juegosGanadosP1 += (int)($p->pareja_1_set_3 ?? 0);
                    $juegosGanadosP2 += (int)($p->pareja_2_set_3 ?? 0);
                }

                $parejasElim[$k1]['sets_ganados'] += $setsGanadosP1;
                $parejasElim[$k1]['sets_perdidos'] += $setsGanadosP2;
                $parejasElim[$k2]['sets_ganados'] += $setsGanadosP2;
                $parejasElim[$k2]['sets_perdidos'] += $setsGanadosP1;

                $parejasElim[$k1]['juegos_ganados'] += $juegosGanadosP1;
                $parejasElim[$k1]['juegos_perdidos'] += $juegosGanadosP2;
                $parejasElim[$k2]['juegos_ganados'] += $juegosGanadosP2;
                $parejasElim[$k2]['juegos_perdidos'] += $juegosGanadosP1;

                if ($setsGanadosP1 > $setsGanadosP2) {
                    $parejasElim[$k1]['partidos_ganados']++;
                    $parejasElim[$k2]['partidos_perdidos']++;
                    $parejasElim[$k1]['puntos'] += 2;
                    $parejasElim[$k2]['puntos'] += 1;
                } else if ($setsGanadosP2 > $setsGanadosP1) {
                    $parejasElim[$k2]['partidos_ganados']++;
                    $parejasElim[$k1]['partidos_perdidos']++;
                    $parejasElim[$k2]['puntos'] += 2;
                    $parejasElim[$k1]['puntos'] += 1;
                }
            }

            $partidoGanadorData = DB::table('partidos')
                ->where('id', $partidoGanador->partido_id)
                ->first();

            if ($partidoGanadorData) {
                $tieneResultadosGanador = ($partidoGanadorData->pareja_1_set_1 > 0 || $partidoGanadorData->pareja_2_set_1 > 0) ||
                    ($partidoGanadorData->pareja_1_set_2 > 0 || $partidoGanadorData->pareja_2_set_2 > 0) ||
                    ($partidoGanadorData->pareja_1_set_3 > 0 || $partidoGanadorData->pareja_2_set_3 > 0) ||
                    ($partidoGanadorData->pareja_1_set_super_tie_break > 0 || $partidoGanadorData->pareja_2_set_super_tie_break > 0);

                if ($tieneResultadosGanador) {
                    $gruposGanador = DB::table('grupos')
                        ->where('partido_id', $partidoGanador->partido_id)
                        ->where('torneo_id', $torneoId)
                        ->where(function ($q) use ($zona) {
                            $q->where('zona', $zona)
                                ->orWhere('zona', 'ganador ' . $zona);
                        })
                        ->where('jugador_1', '!=', 0)
                        ->where('jugador_2', '!=', 0)
                        ->orderBy('id')
                        ->get();

                    if ($gruposGanador->count() >= 2) {
                        $ganadorPartidoGanador = self::determinarGanadorPartido($partidoGanadorData);
                        $perdedorPartidoGanador = $ganadorPartidoGanador === 1 ? 2 : 1;

                        $grupoGanador = $gruposGanador[$ganadorPartidoGanador - 1];
                        $grupoPerdedorGanador = $gruposGanador[$perdedorPartidoGanador - 1];

                        $posiciones[0] = [
                            'jugador_1' => $grupoGanador->jugador_1,
                            'jugador_2' => $grupoGanador->jugador_2
                        ];

                        $posiciones[1] = [
                            'jugador_1' => $grupoPerdedorGanador->jugador_1,
                            'jugador_2' => $grupoPerdedorGanador->jugador_2
                        ];
                    }
                }
            }

            $partidoPerdedorData = DB::table('partidos')
                ->where('id', $partidoPerdedor->partido_id)
                ->first();

            if ($partidoPerdedorData) {
                $tieneResultadosPerdedor = ($partidoPerdedorData->pareja_1_set_1 > 0 || $partidoPerdedorData->pareja_2_set_1 > 0) ||
                    ($partidoPerdedorData->pareja_1_set_2 > 0 || $partidoPerdedorData->pareja_2_set_2 > 0) ||
                    ($partidoPerdedorData->pareja_1_set_3 > 0 || $partidoPerdedorData->pareja_2_set_3 > 0) ||
                    ($partidoPerdedorData->pareja_1_set_super_tie_break > 0 || $partidoPerdedorData->pareja_2_set_super_tie_break > 0);

                if ($tieneResultadosPerdedor) {
                    $gruposPerdedor = DB::table('grupos')
                        ->where('partido_id', $partidoPerdedor->partido_id)
                        ->where('torneo_id', $torneoId)
                        ->where(function ($q) use ($zona) {
                            $q->where('zona', $zona)
                                ->orWhere('zona', 'perdedor ' . $zona);
                        })
                        ->where('jugador_1', '!=', 0)
                        ->where('jugador_2', '!=', 0)
                        ->orderBy('id')
                        ->get();

                    if ($gruposPerdedor->count() >= 2) {
                        $ganadorPartidoPerdedor = self::determinarGanadorPartido($partidoPerdedorData);
                        $grupoGanadorPerdedor = $gruposPerdedor[$ganadorPartidoPerdedor - 1];

                        $posiciones[2] = [
                            'jugador_1' => $grupoGanadorPerdedor->jugador_1,
                            'jugador_2' => $grupoGanadorPerdedor->jugador_2
                        ];
                    }
                }
            }

            $posiciones = array_values($posiciones);

            $posicionesConStats = [];
            foreach ($posiciones as $idx => $pos) {
                $k = $parejaKey($pos['jugador_1'] ?? 0, $pos['jugador_2'] ?? 0);
                $stats = $parejasElim[$k] ?? null;
                if ($stats) {
                    $posicionesConStats[$idx] = $stats;
                } else {
                    $posicionesConStats[$idx] = [
                        'jugador_1' => (int)($pos['jugador_1'] ?? 0),
                        'jugador_2' => (int)($pos['jugador_2'] ?? 0),
                        'partidos_jugados' => 0,
                        'partidos_ganados' => 0,
                        'partidos_perdidos' => 0,
                        'puntos' => 0,
                        'sets_ganados' => 0,
                        'sets_perdidos' => 0,
                        'juegos_ganados' => 0,
                        'juegos_perdidos' => 0
                    ];
                }
            }

            $final = array_values($posicionesConStats);
            self::persistirPosicionesEnZonaBase($torneoId, $zona, $final);
            return $final;
        }

        $posiciones = array_values($parejas);

        if ($numParejas == 3) {
            usort($posiciones, function ($a, $b) {
                $puntosA = $a['puntos'] ?? 0;
                $puntosB = $b['puntos'] ?? 0;
                if ($puntosA != $puntosB) {
                    return $puntosB - $puntosA;
                }
                $diffSetsA = $a['diferencia_sets'] ?? 0;
                $diffSetsB = $b['diferencia_sets'] ?? 0;
                if ($diffSetsA != $diffSetsB) {
                    return $diffSetsB - $diffSetsA;
                }
                $diffGamesA = $a['diferencia_games'] ?? 0;
                $diffGamesB = $b['diferencia_games'] ?? 0;
                return $diffGamesB - $diffGamesA;
            });
        } else {
            usort($posiciones, function ($a, $b) {
                if ($a['puntos'] != $b['puntos']) {
                    return $b['puntos'] - $a['puntos'];
                }
                $keyA = $a['key'];
                $keyB = $b['key'];

                if (isset($a['partidos_directos'][$keyB])) {
                    if ($a['partidos_directos'][$keyB]['ganado']) {
                        return -1;
                    } else {
                        return 1;
                    }
                }

                $diffJuegosA = $a['juegos_ganados'] - $a['juegos_perdidos'];
                $diffJuegosB = $b['juegos_ganados'] - $b['juegos_perdidos'];
                if ($diffJuegosA != $diffJuegosB) {
                    return $diffJuegosB - $diffJuegosA;
                }

                $diffSetsA = $a['sets_ganados'] - $a['sets_perdidos'];
                $diffSetsB = $b['sets_ganados'] - $b['sets_perdidos'];
                if ($diffSetsA != $diffSetsB) {
                    return $diffSetsB - $diffSetsA;
                }

                if ($a['juegos_ganados'] != $b['juegos_ganados']) {
                    return $b['juegos_ganados'] - $a['juegos_ganados'];
                }

                return 0;
            });
        }

        self::persistirPosicionesEnZonaBase($torneoId, $zona, $posiciones);
        return $posiciones;
    }

    /**
     * Intenta persistir posiciones para todas las zonas base del torneo que estén completas
     * y tengan al menos un grupo sin `posicion_grupo`.
     */
    public static function syncPosicionesGruposFaltantes(int $torneoId): void
    {
        $zonas = DB::table('grupos')
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
            ->distinct()
            ->pluck('zona');

        foreach ($zonas as $zona) {
            $zona = (string) $zona;

            $faltaPosicion = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', $zona)
                ->where(function ($q) {
                    $q->whereNull('posicion_grupo')->orWhere('posicion_grupo', 0);
                })
                ->exists();

            if (!$faltaPosicion) {
                continue;
            }

            if (!self::zonaGruposTodosPartidosCompletos($torneoId, $zona)) {
                continue;
            }

            self::calcularYPersistirPosicionesZona($torneoId, $zona);
        }
    }
}
