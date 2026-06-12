-- =============================================================================
-- Bahía Pádel — calendario: tabla de inscripciones web + columna valor_inscripcion
-- MySQL / MariaDB. Ejecutar una vez en producción si no usás `php artisan migrate`.
-- Revisá que exista la tabla `calendario` con columna `premio_4` (orden del AFTER).
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- 1) Tabla calendario_inscripciones
-- ---------------------------------------------------------------------------
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
-- 2) Columna valor_inscripcion en calendario (solo si no existe)
-- ---------------------------------------------------------------------------
SET @db := DATABASE();
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'calendario' AND COLUMN_NAME = 'valor_inscripcion'
);
SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE `calendario` ADD COLUMN `valor_inscripcion` DECIMAL(12,2) NULL AFTER `premio_4`',
  'SELECT ''Column calendario.valor_inscripcion already exists'' AS notice'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- 3) (Opcional) Registrar migraciones en Laravel para que `migrate` no las repita
-- Ajustá el número de batch al siguiente libre: SELECT MAX(batch) FROM migrations;
-- ---------------------------------------------------------------------------
-- INSERT INTO `migrations` (`migration`, `batch`) VALUES
--   ('2026_04_08_120000_create_calendario_inscripciones_table', (SELECT IFNULL(MAX(batch),0)+1 FROM migrations m)),
--   ('2026_04_08_130000_add_valor_inscripcion_to_calendario_table', (SELECT IFNULL(MAX(batch),0) FROM migrations m));
-- MySQL no permite subquery correlacionada así en un INSERT simple; ejecutá:
-- SET @b = (SELECT IFNULL(MAX(batch),0)+1 FROM migrations);
-- INSERT INTO migrations (migration, batch) VALUES
--   ('2026_04_08_120000_create_calendario_inscripciones_table', @b);
-- INSERT INTO migrations (migration, batch) VALUES
--   ('2026_04_08_130000_add_valor_inscripcion_to_calendario_table', @b);
