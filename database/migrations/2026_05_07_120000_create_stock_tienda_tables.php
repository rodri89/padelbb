<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stock_canchas')) {
            Schema::create('stock_canchas', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 50);
                $table->string('descripcion', 255)->nullable();
                $table->boolean('activa')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('stock_categorias_productos')) {
            Schema::create('stock_categorias_productos', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 100);
                $table->string('descripcion', 255)->nullable();
                $table->boolean('activa')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('stock_productos')) {
            Schema::create('stock_productos', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 100);
                $table->string('descripcion', 255)->nullable();
                $table->foreignId('stock_categoria_id')->constrained('stock_categorias_productos')->cascadeOnDelete();
                $table->decimal('precio_unitario', 10, 2);
                $table->unsignedInteger('stock_actual')->default(0);
                $table->unsignedInteger('stock_minimo')->default(0);
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('stock_ventas')) {
            Schema::create('stock_ventas', function (Blueprint $table) {
                $table->id();
                $table->string('nombre_cliente', 100);
                $table->string('nombre_turno', 50)->nullable();
                $table->foreignId('stock_cancha_id')->constrained('stock_canchas')->restrictOnDelete();
                $table->date('fecha_venta');
                $table->time('hora_venta');
                $table->decimal('precio_total', 10, 2)->default(0);
                $table->string('metodo_pago', 20); // efectivo | transferencia
                $table->string('estado_pago', 20); // pagado | pendiente
                $table->date('fecha_pago')->nullable();
                $table->string('referencia_pago', 100)->nullable();
                $table->string('notas', 255)->nullable();
                $table->timestamps();

                $table->index(['fecha_venta', 'estado_pago']);
            });
        }

        if (!Schema::hasTable('stock_detalles_venta')) {
            Schema::create('stock_detalles_venta', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stock_venta_id')->constrained('stock_ventas')->cascadeOnDelete();
                $table->foreignId('stock_producto_id')->constrained('stock_productos')->restrictOnDelete();
                $table->unsignedInteger('cantidad');
                $table->decimal('precio_unitario', 10, 2);
                $table->decimal('subtotal', 10, 2);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('stock_movimientos_stock')) {
            Schema::create('stock_movimientos_stock', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stock_producto_id')->constrained('stock_productos')->cascadeOnDelete();
                $table->string('tipo_movimiento', 20); // entrada | salida | ajuste
                $table->integer('cantidad');
                $table->unsignedInteger('cantidad_anterior');
                $table->unsignedInteger('cantidad_nueva');
                $table->string('motivo', 255)->nullable();
                $table->string('usuario_responsable', 100)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('stock_historial_pagos')) {
            Schema::create('stock_historial_pagos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stock_venta_id')->constrained('stock_ventas')->cascadeOnDelete();
                $table->decimal('monto_pagado', 10, 2);
                $table->string('metodo_pago', 20);
                $table->timestamp('fecha_pago');
                $table->string('referencia_pago', 100)->nullable();
                $table->string('usuario_responsable', 100)->nullable();
                $table->string('notas', 255)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('stock_auditoria')) {
            Schema::create('stock_auditoria', function (Blueprint $table) {
                $table->id();
                $table->string('tabla_afectada', 50);
                $table->unsignedBigInteger('id_registro');
                $table->string('accion', 50);
                $table->string('usuario', 100)->nullable();
                $table->json('valores_anteriores')->nullable();
                $table->json('valores_nuevos')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_auditoria');
        Schema::dropIfExists('stock_historial_pagos');
        Schema::dropIfExists('stock_movimientos_stock');
        Schema::dropIfExists('stock_detalles_venta');
        Schema::dropIfExists('stock_ventas');
        Schema::dropIfExists('stock_productos');
        Schema::dropIfExists('stock_categorias_productos');
        Schema::dropIfExists('stock_canchas');
    }
};
