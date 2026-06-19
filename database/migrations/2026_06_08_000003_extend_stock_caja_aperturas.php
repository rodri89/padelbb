<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Crear la tabla base si no existe (p.ej. base limpia donde el compañero no corrió su SQL)
        if (!Schema::hasTable('stock_caja_aperturas')) {
            Schema::create('stock_caja_aperturas', function (Blueprint $table) {
                $table->id();
                $table->date('fecha')->unique();
                $table->decimal('monto_efectivo_inicial', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        // Extender la tabla con las columnas que faltan para cierre completo
        Schema::table('stock_caja_aperturas', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_caja_aperturas', 'fondo_final')) {
                $table->decimal('fondo_final', 10, 2)->nullable()->after('monto_efectivo_inicial');
            }
            if (!Schema::hasColumn('stock_caja_aperturas', 'efectivo_real')) {
                $table->decimal('efectivo_real', 10, 2)->nullable()->after('fondo_final');
            }
            if (!Schema::hasColumn('stock_caja_aperturas', 'diferencia')) {
                $table->decimal('diferencia', 10, 2)->nullable()->after('efectivo_real');
            }
            if (!Schema::hasColumn('stock_caja_aperturas', 'observaciones')) {
                $table->text('observaciones')->nullable()->after('diferencia');
            }
            if (!Schema::hasColumn('stock_caja_aperturas', 'usuario_apertura')) {
                $table->string('usuario_apertura', 100)->nullable()->after('observaciones');
            }
            if (!Schema::hasColumn('stock_caja_aperturas', 'usuario_cierre')) {
                $table->string('usuario_cierre', 100)->nullable()->after('usuario_apertura');
            }
            if (!Schema::hasColumn('stock_caja_aperturas', 'estado')) {
                $table->string('estado', 20)->default('abierta')->after('usuario_cierre');
            }
        });

        // Eliminar la tabla duplicada que cree yo y migrar sus datos
        if (Schema::hasTable('stock_caja_diaria')) {
            $datos = DB::table('stock_caja_diaria')->get();
            foreach ($datos as $row) {
                DB::table('stock_caja_aperturas')->updateOrInsert(
                    ['fecha' => $row->fecha],
                    [
                        'monto_efectivo_inicial' => $row->fondo_inicial,
                        'estado' => $row->estado,
                        'fondo_final' => $row->fondo_final,
                        'efectivo_real' => $row->efectivo_real,
                        'diferencia' => $row->diferencia,
                        'observaciones' => $row->observaciones,
                        'usuario_apertura' => $row->usuario_apertura,
                        'usuario_cierre' => $row->usuario_cierre,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ]
                );
            }
            Schema::dropIfExists('stock_caja_diaria');
        }
    }

    public function down(): void
    {
        Schema::table('stock_caja_aperturas', function (Blueprint $table) {
            $table->dropColumn(['fondo_final', 'efectivo_real', 'diferencia', 'observaciones', 'usuario_apertura', 'usuario_cierre', 'estado']);
        });
    }
};
