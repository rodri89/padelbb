-- Alineado a database/migrations/2026_05_12_000001_stock_venta_participantes.php
-- Ejecutar en MySQL/MariaDB si no usás artisan migrate (ej. Hostinger).
-- Si la tabla o las columnas ya existen, omití la parte correspondiente (MySQL no tiene IF NOT EXISTS en ADD COLUMN).

CREATE TABLE IF NOT EXISTS `stock_venta_participantes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stock_venta_id` BIGINT UNSIGNED NOT NULL,
  `slot` TINYINT UNSIGNED NOT NULL COMMENT '1-4',
  `nombre` VARCHAR(100) NOT NULL,
  `jugador_id` INT UNSIGNED DEFAULT NULL,
  `estado_pago` VARCHAR(20) NOT NULL DEFAULT 'pendiente',
  `metodo_pago` VARCHAR(20) DEFAULT NULL,
  `fecha_pago` DATE DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_venta_participantes_stock_venta_id_slot_unique` (`stock_venta_id`, `slot`),
  CONSTRAINT `stock_venta_participantes_stock_venta_id_foreign`
    FOREIGN KEY (`stock_venta_id`) REFERENCES `stock_ventas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_venta_participantes_jugador_id_foreign`
    FOREIGN KEY (`jugador_id`) REFERENCES `jugadores` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `stock_detalles_venta`
  ADD COLUMN `stock_venta_participante_id` BIGINT UNSIGNED DEFAULT NULL AFTER `stock_venta_id`,
  ADD CONSTRAINT `stock_detalles_venta_stock_venta_participante_id_foreign`
    FOREIGN KEY (`stock_venta_participante_id`) REFERENCES `stock_venta_participantes` (`id`) ON DELETE SET NULL;

ALTER TABLE `stock_historial_pagos`
  ADD COLUMN `stock_venta_participante_id` BIGINT UNSIGNED DEFAULT NULL AFTER `stock_venta_id`,
  ADD CONSTRAINT `stock_historial_pagos_stock_venta_participante_id_foreign`
    FOREIGN KEY (`stock_venta_participante_id`) REFERENCES `stock_venta_participantes` (`id`) ON DELETE SET NULL;
