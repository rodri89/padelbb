<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tv_configuracion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->default('TV Principal');
            $table->boolean('activo')->default(true);
            $table->json('slides')->nullable(); // Array de slides con torneo_id, tipo, duracion
            $table->integer('intervalo_default')->default(15); // Segundos entre slides
            $table->timestamps();
        });
        
        // Insertar configuración por defecto
        DB::table('tv_configuracion')->insert([
            'nombre' => 'TV Principal',
            'activo' => true,
            'slides' => json_encode([]),
            'intervalo_default' => 15,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tv_configuracion');
    }
};
