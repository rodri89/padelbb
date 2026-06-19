-- Cambios para el módulo de Caja (dividir productos + ticket de continuación)
-- Ejecutar directamente en MySQL / MariaDB (compatible con MySQL 5.7 / phpMyAdmin)
-- Es idempotente: si la columna ya existe no falla.
--
-- Nota: CREATE PROCEDURE IF NOT EXISTS solo existe desde MySQL 8.0.29.
-- En MAMP (MySQL 5.7) usamos DROP + CREATE sin IF NOT EXISTS.

-- 1) Permite marcar líneas de venta que son resultado de una división entre jugadores
DROP PROCEDURE IF EXISTS add_es_division;
DELIMITER //
CREATE PROCEDURE add_es_division()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'stock_detalles_venta'
          AND COLUMN_NAME = 'es_division'
          AND TABLE_SCHEMA = DATABASE()
    ) THEN
        ALTER TABLE stock_detalles_venta
            ADD COLUMN es_division TINYINT(1) NOT NULL DEFAULT 0
            AFTER stock_venta_participante_id;
    END IF;
END //
DELIMITER ;
CALL add_es_division();
DROP PROCEDURE IF EXISTS add_es_division;

-- 2) Permite vincular un ticket nuevo (hijo) a un ticket ya cerrado (padre)
DROP PROCEDURE IF EXISTS add_stock_venta_id_padre;
DELIMITER //
CREATE PROCEDURE add_stock_venta_id_padre()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'stock_ventas'
          AND COLUMN_NAME = 'stock_venta_id_padre'
          AND TABLE_SCHEMA = DATABASE()
    ) THEN
        ALTER TABLE stock_ventas
            ADD COLUMN stock_venta_id_padre BIGINT UNSIGNED NULL
            AFTER id;
    END IF;
END //
DELIMITER ;
CALL add_stock_venta_id_padre();
DROP PROCEDURE IF EXISTS add_stock_venta_id_padre;

-- Opcional: Foreign Key (si tu BD lo soporta y querés mantener integridad referencial)
-- Ejecutar solo si no existe ya la FK:
-- ALTER TABLE stock_ventas
--     ADD CONSTRAINT fk_stock_ventas_padre
--     FOREIGN KEY (stock_venta_id_padre) REFERENCES stock_ventas(id)
--     ON DELETE SET NULL;

-- 3) Estado de pago por línea de venta (cobro producto a producto)
DROP PROCEDURE IF EXISTS add_estado_pago_detalle;
DELIMITER //
CREATE PROCEDURE add_estado_pago_detalle()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'stock_detalles_venta'
          AND COLUMN_NAME = 'estado_pago'
          AND TABLE_SCHEMA = DATABASE()
    ) THEN
        ALTER TABLE stock_detalles_venta
            ADD COLUMN estado_pago VARCHAR(20) NOT NULL DEFAULT 'pendiente'
            AFTER es_division;
    END IF;
END //
DELIMITER ;
CALL add_estado_pago_detalle();
DROP PROCEDURE IF EXISTS add_estado_pago_detalle;

UPDATE stock_detalles_venta d
INNER JOIN stock_ventas v ON d.stock_venta_id = v.id
SET d.estado_pago = 'pagado'
WHERE v.estado_pago = 'pagado';

-- 4) Vincular historial de pagos a la línea cobrada
DROP PROCEDURE IF EXISTS add_stock_detalle_venta_id_historial;
DELIMITER //
CREATE PROCEDURE add_stock_detalle_venta_id_historial()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'stock_historial_pagos'
          AND COLUMN_NAME = 'stock_detalle_venta_id'
          AND TABLE_SCHEMA = DATABASE()
    ) THEN
        ALTER TABLE stock_historial_pagos
            ADD COLUMN stock_detalle_venta_id BIGINT UNSIGNED NULL
            AFTER stock_venta_participante_id;
    END IF;
END //
DELIMITER ;
CALL add_stock_detalle_venta_id_historial();
DROP PROCEDURE IF EXISTS add_stock_detalle_venta_id_historial;
