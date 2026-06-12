<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Calendario extends Model
{
    protected $table = 'calendario';

    protected $fillable = [
        'fecha',
        'fecha_desde',
        'fecha_hasta',
        'fecha_abre_inscripcion',
        'fecha_cierra_inscripcion',
        'categoria',
        'tipo',
        'nombre',
        'premio_1',
        'premio_2',
        'premio_3',
        'premio_4',
        'valor_inscripcion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_desde' => 'date',
        'fecha_hasta' => 'date',
        'fecha_abre_inscripcion' => 'date',
        'fecha_cierra_inscripcion' => 'date',
        'premio_1' => 'decimal:2',
        'premio_2' => 'decimal:2',
        'premio_3' => 'decimal:2',
        'premio_4' => 'decimal:2',
        'valor_inscripcion' => 'decimal:2',
    ];

    /**
     * Devuelve el label del tipo para mostrar (mixto, damas, libre)
     */
    public function getTipoLabelAttribute()
    {
        $map = [
            'mixto' => 'Mixto',
            'femenino' => 'Damas',
            'masculino' => 'Libre',
        ];
        return $map[strtolower($this->tipo ?? '')] ?? ucfirst($this->tipo ?? '');
    }

    /**
     * Ej.: "10, 11, 12, 13 Abril" o varios meses: "28, 29 Marzo, 1, 2 Abril".
     */
    public function textoFechasTorneo(): string
    {
        $desde = $this->fecha_desde ?? $this->fecha;
        $hasta = $this->fecha_hasta ?? $this->fecha_desde ?? $this->fecha;
        if (!$desde || !$hasta) {
            return '';
        }
        $d0 = $desde instanceof Carbon ? $desde->copy()->startOfDay() : Carbon::parse($desde)->startOfDay();
        $d1 = $hasta instanceof Carbon ? $hasta->copy()->startOfDay() : Carbon::parse($hasta)->startOfDay();
        if ($d1->lt($d0)) {
            $d1 = $d0->copy();
        }
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
        $porYm = [];
        for ($c = $d0->copy(); $c->lte($d1); $c->addDay()) {
            $ym = $c->format('Y-m');
            if (!isset($porYm[$ym])) {
                $porYm[$ym] = ['dias' => [], 'n' => (int) $c->format('n')];
            }
            $porYm[$ym]['dias'][] = (int) $c->format('j');
        }
        ksort($porYm);
        $partes = [];
        foreach ($porYm as $bloque) {
            $partes[] = implode(', ', $bloque['dias']).' '.$meses[$bloque['n']];
        }

        return implode(', ', $partes);
    }

    /**
     * Hoy está entre abre y cierra inscripción (inclusive), ambas fechas deben existir.
     */
    public function inscripcionAbiertaHoy(): bool
    {
        if (!$this->fecha_abre_inscripcion || !$this->fecha_cierra_inscripcion) {
            return false;
        }
        $hoy = Carbon::today()->format('Y-m-d');
        $abre = ($this->fecha_abre_inscripcion instanceof Carbon
            ? $this->fecha_abre_inscripcion
            : Carbon::parse($this->fecha_abre_inscripcion))->format('Y-m-d');
        $cierra = ($this->fecha_cierra_inscripcion instanceof Carbon
            ? $this->fecha_cierra_inscripcion
            : Carbon::parse($this->fecha_cierra_inscripcion))->format('Y-m-d');

        return $hoy >= $abre && $hoy <= $cierra;
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(CalendarioInscripcion::class);
    }
}
