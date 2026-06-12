-- =============================================================================
-- Ranking: tablas para entradas manuales (admin_ranking)
-- Ejecutar en producción si aparece: Table 'ranking_entradas' doesn't exist
-- Equivale a: database/migrations/2026_05_11_000001_create_ranking_entradas_tables.php
-- Motor: MySQL / MariaDB (InnoDB, utf8mb4)
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1) Cabecera de cada período manual (mes, categoría, temporada, tipo)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ranking_entradas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(128) NOT NULL COMMENT 'Nombre descriptivo, ej: Torneo Enero 2026',
  `tipo` varchar(16) NOT NULL DEFAULT 'masculino' COMMENT 'masculino, femenino, mixto',
  `categoria` tinyint unsigned NOT NULL COMMENT 'Categoría, ej: 6 = 6ta',
  `temporada` smallint unsigned NOT NULL COMMENT 'Año, ej: 2026',
  `mes` tinyint unsigned NOT NULL COMMENT 'Mes 1-12',
  `descripcion` text DEFAULT NULL COMMENT 'Descripción opcional',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ranking_entradas_tipo_categoria_temporada_index` (`tipo`,`categoria`,`temporada`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2) Detalle: puntos por jugador en cada entrada
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ranking_entradas_jugadores` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `entrada_id` int unsigned NOT NULL,
  `jugador_id` int unsigned NOT NULL,
  `puntos` int unsigned NOT NULL DEFAULT 0,
  `referencia_codigo` varchar(32) NOT NULL DEFAULT 'no_clasificados'
    COMMENT 'campeon, subcampeon, tercero_cuarto, cuartos, octavos, 16avos, no_clasificados',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rej_entrada_jugador_unique` (`entrada_id`,`jugador_id`),
  KEY `ranking_entradas_jugadores_jugador_id_index` (`jugador_id`),
  CONSTRAINT `ranking_entradas_jugadores_entrada_id_foreign`
    FOREIGN KEY (`entrada_id`) REFERENCES `ranking_entradas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ranking_entradas_jugadores_jugador_id_foreign`
    FOREIGN KEY (`jugador_id`) REFERENCES `jugadores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- Fin. Verificar:
--   SHOW TABLES LIKE 'ranking_entradas%';
-- =============================================================================
