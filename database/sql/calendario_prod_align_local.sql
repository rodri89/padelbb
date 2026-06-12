-- =============================================================================
-- Bahía Pádel — Alinear tabla `calendario` en PROD con LOCAL (Laravel migrations)
--
-- Asume PROD con al menos: id, fecha, categoria, tipo, nombre, created_at, updated_at
-- Añade (si no existen): rango de fechas, inscripción, premios, valor_inscripcion
-- Luego copia fecha -> fecha_desde / fecha_hasta donde falten.
-- Al final: tabla calendario_inscripciones + FK (como en local).
--
-- MySQL / MariaDB. Ejecutar en la base del proyecto. Hacé backup antes.
-- =============================================================================

SET NAMES utf8mb4;
SET @db := DATABASE();

-- ---------------------------------------------------------------------------
-- Helpers: añadir columna solo si no existe (nombre de columna en @col)
-- ---------------------------------------------------------------------------
-- fecha_desde
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'calendario' AND COLUMN_NAME = 'fecha_desde');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE `calendario` ADD COLUMN `fecha_desde` DATE NULL AFTER `fecha`', 'SELECT ''skip fecha_desde'' AS notice');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- fecha_hasta
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'calendario' AND COLUMN_NAME = 'fecha_hasta');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE `calendario` ADD COLUMN `fecha_hasta` DATE NULL AFTER `fecha_desde`', 'SELECT ''skip fecha_hasta'' AS notice');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- fecha_abre_inscripcion
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'calendario' AND COLUMN_NAME = 'fecha_abre_inscripcion');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE `calendario` ADD COLUMN `fecha_abre_inscripcion` DATE NULL AFTER `fecha_hasta`', 'SELECT ''skip fecha_abre_inscripcion'' AS notice');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- fecha_cierra_inscripcion
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'calendario' AND COLUMN_NAME = 'fecha_cierra_inscripcion');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE `calendario` ADD COLUMN `fecha_cierra_inscripcion` DATE NULL AFTER `fecha_abre_inscripcion`', 'SELECT ''skip fecha_cierra_inscripcion'' AS notice');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- premio_1 … premio_4 (después de nombre, como en migration)
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'calendario' AND COLUMN_NAME = 'premio_1');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE `calendario` ADD COLUMN `premio_1` DECIMAL(12,2) NULL AFTER `nombre`', 'SELECT ''skip premio_1'' AS notice');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'calendario' AND COLUMN_NAME = 'premio_2');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE `calendario` ADD COLUMN `premio_2` DECIMAL(12,2) NULL AFTER `premio_1`', 'SELECT ''skip premio_2'' AS notice');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'calendario' AND COLUMN_NAME = 'premio_3');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE `calendario` ADD COLUMN `premio_3` DECIMAL(12,2) NULL AFTER `premio_2`', 'SELECT ''skip premio_3'' AS notice');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'calendario' AND COLUMN_NAME = 'premio_4');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE `calendario` ADD COLUMN `premio_4` DECIMAL(12,2) NULL AFTER `premio_3`', 'SELECT ''skip premio_4'' AS notice');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- valor_inscripcion
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'calendario' AND COLUMN_NAME = 'valor_inscripcion');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE `calendario` ADD COLUMN `valor_inscripcion` DECIMAL(12,2) NULL AFTER `premio_4`', 'SELECT ''skip valor_inscripcion'' AS notice');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- Datos: igual que migration AddCamposToCalendarioTable (copiar fecha al rango)
-- ---------------------------------------------------------------------------
UPDATE `calendario`
SET `fecha_desde` = `fecha`, `fecha_hasta` = `fecha`
WHERE `fecha_desde` IS NULL AND `fecha` IS NOT NULL;

-- ---------------------------------------------------------------------------
-- Tabla calendario_inscripciones (inscripciones web)
-- ---------------------------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `calendario_inscripciones` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `calendario_id` int unsigned NOT NULL,
  `jugador1_nombre` varchar(120) NOT NULL,
  `jugador1_apellido` varchar(120) NOT NULL,
  `jugador1_telefono` varchar(40) NOT NULL,
  `jugador2_nombre` varchar(120) NOT NULL,
  `jugador2_apellido` varchar(120) NOT NULL,
  `jugador2_telefono` varchar(40) DEFAULT NULL,
  `disponibilidad_horaria` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `calendario_inscripciones_calendario_id_foreign` (`calendario_id`),
  CONSTRAINT `calendario_inscripciones_calendario_id_foreign`
    FOREIGN KEY (`calendario_id`) REFERENCES `calendario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- (Opcional) Registrar migraciones Laravel — descomentá y ajustá @b si usás migrate después
-- ---------------------------------------------------------------------------
-- SET @b = (SELECT IFNULL(MAX(batch),0)+1 FROM migrations);
-- INSERT IGNORE INTO migrations (migration, batch) VALUES
--   ('2026_04_08_000001_add_campos_to_calendario_table', @b),
--   ('2026_04_08_120000_create_calendario_inscripciones_table', @b),
--   ('2026_04_08_130000_add_valor_inscripcion_to_calendario_table', @b);
