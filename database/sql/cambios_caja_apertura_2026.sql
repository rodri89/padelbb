-- Apertura diaria de caja (efectivo inicial por fecha)
-- Ejecutar directamente en MySQL / MariaDB (compatible con MySQL 5.7 / phpMyAdmin)
-- Es idempotente: si la tabla ya existe no falla.

CREATE TABLE IF NOT EXISTS stock_caja_aperturas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    fecha DATE NOT NULL,
    monto_efectivo_inicial DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY stock_caja_aperturas_fecha_unique (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
