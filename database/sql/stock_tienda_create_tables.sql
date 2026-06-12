-- =============================================================================
-- Bahía Pádel — Tablas módulo Stock / Tienda / Caja
--
-- Equivale a la migración Laravel: 2026_05_07_120000_create_stock_tienda_tables
-- MySQL 5.7+ / MariaDB 10.2+ (usa tipo JSON en stock_auditoria).
--
-- Antes de ejecutar: backup de la base. En PROD con datos, no correr los DROP
-- salvo que sepas que no hay tablas stock_* que quieras conservar.
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- Desinstalar tablas existentes (orden inverso a las FK). Opcional.
-- Descomentá solo si recreás desde cero en un entorno de prueba.
-- -----------------------------------------------------------------------------
-- DROP TABLE IF EXISTS `stock_auditoria`;
-- DROP TABLE IF EXISTS `stock_historial_pagos`;
-- DROP TABLE IF EXISTS `stock_movimientos_stock`;
-- DROP TABLE IF EXISTS `stock_detalles_venta`;
-- DROP TABLE IF EXISTS `stock_ventas`;
-- DROP TABLE IF EXISTS `stock_productos`;
-- DROP TABLE IF EXISTS `stock_categorias_productos`;
-- DROP TABLE IF EXISTS `stock_canchas`;

CREATE TABLE IF NOT EXISTS `stock_canchas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stock_categorias_productos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stock_productos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `stock_categoria_id` bigint unsigned NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `stock_actual` int unsigned NOT NULL DEFAULT '0',
  `stock_minimo` int unsigned NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_productos_stock_categoria_id_foreign` (`stock_categoria_id`),
  CONSTRAINT `stock_productos_stock_categoria_id_foreign` FOREIGN KEY (`stock_categoria_id`) REFERENCES `stock_categorias_productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stock_ventas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_cliente` varchar(100) NOT NULL,
  `nombre_turno` varchar(50) DEFAULT NULL,
  `stock_cancha_id` bigint unsigned NOT NULL,
  `fecha_venta` date NOT NULL,
  `hora_venta` time NOT NULL,
  `precio_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `metodo_pago` varchar(20) NOT NULL COMMENT 'efectivo | transferencia',
  `estado_pago` varchar(20) NOT NULL COMMENT 'pagado | pendiente',
  `fecha_pago` date DEFAULT NULL,
  `referencia_pago` varchar(100) DEFAULT NULL,
  `notas` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_ventas_stock_cancha_id_foreign` (`stock_cancha_id`),
  KEY `stock_ventas_fecha_venta_estado_pago_index` (`fecha_venta`,`estado_pago`),
  CONSTRAINT `stock_ventas_stock_cancha_id_foreign` FOREIGN KEY (`stock_cancha_id`) REFERENCES `stock_canchas` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stock_detalles_venta` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stock_venta_id` bigint unsigned NOT NULL,
  `stock_producto_id` bigint unsigned NOT NULL,
  `cantidad` int unsigned NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `stock_detalles_venta_stock_venta_id_foreign` (`stock_venta_id`),
  KEY `stock_detalles_venta_stock_producto_id_foreign` (`stock_producto_id`),
  CONSTRAINT `stock_detalles_venta_stock_venta_id_foreign` FOREIGN KEY (`stock_venta_id`) REFERENCES `stock_ventas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_detalles_venta_stock_producto_id_foreign` FOREIGN KEY (`stock_producto_id`) REFERENCES `stock_productos` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stock_movimientos_stock` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stock_producto_id` bigint unsigned NOT NULL,
  `tipo_movimiento` varchar(20) NOT NULL COMMENT 'entrada | salida | ajuste',
  `cantidad` int NOT NULL,
  `cantidad_anterior` int unsigned NOT NULL,
  `cantidad_nueva` int unsigned NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `usuario_responsable` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `stock_movimientos_stock_stock_producto_id_foreign` (`stock_producto_id`),
  CONSTRAINT `stock_movimientos_stock_stock_producto_id_foreign` FOREIGN KEY (`stock_producto_id`) REFERENCES `stock_productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stock_historial_pagos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stock_venta_id` bigint unsigned NOT NULL,
  `monto_pagado` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(20) NOT NULL,
  `fecha_pago` timestamp NOT NULL,
  `referencia_pago` varchar(100) DEFAULT NULL,
  `usuario_responsable` varchar(100) DEFAULT NULL,
  `notas` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `stock_historial_pagos_stock_venta_id_foreign` (`stock_venta_id`),
  CONSTRAINT `stock_historial_pagos_stock_venta_id_foreign` FOREIGN KEY (`stock_venta_id`) REFERENCES `stock_ventas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stock_auditoria` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tabla_afectada` varchar(50) NOT NULL,
  `id_registro` bigint unsigned NOT NULL,
  `accion` varchar(50) NOT NULL,
  `usuario` varchar(100) DEFAULT NULL,
  `valores_anteriores` json DEFAULT NULL,
  `valores_nuevos` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
