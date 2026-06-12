-- =============================================================================
-- Alineación producción — abril 2026
-- Equivalente a las migraciones Laravel:
--   - database/migrations/2026_04_22_000001_add_posicion_grupo_to_grupos_table.php
--   - database/migrations/2026_04_22_120000_semifinal_refs_c_to_cu.php
--
-- Motor asumido: MySQL / MariaDB (como Laravel en prod habitual).
-- ANTES: backup completo de la base. Ejecutar en la BD correcta:
--   mysql -u ... -p nombre_bd < script/prod_align_grupos_posicion_and_cu_refs.sql
--
-- Si la columna grupos.posicion_grupo ya existe, COMENTAR o omitir la sección 1.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1) Tabla grupos: columna posicion_grupo (persistir orden por zona para cruces)
-- -----------------------------------------------------------------------------
ALTER TABLE `grupos`
    ADD COLUMN `posicion_grupo` TINYINT UNSIGNED NULL AFTER `referencia_config`;

-- -----------------------------------------------------------------------------
-- 2) configuracion_cruces_puntuables: en llave_semifinal (JSON texto),
--    referencias de ganadores de cuartos "C1".."C4" → "CU1".."CU4"
--    (solo strings JSON entre comillas, igual que la migración PHP).
-- Orden de REPLACE: de C4 a C1 para no pisar subcadenas improbables pero seguras.
-- -----------------------------------------------------------------------------
UPDATE `configuracion_cruces_puntuables`
SET `llave_semifinal` = REPLACE(
        REPLACE(
            REPLACE(
                REPLACE(`llave_semifinal`, '"C4"', '"CU4"'),
                '"C3"', '"CU3"'
            ),
            '"C2"', '"CU2"'
        ),
        '"C1"', '"CU1"'
    )
WHERE `llave_semifinal` IS NOT NULL
  AND (
      `llave_semifinal` LIKE '%"C1"%'
      OR `llave_semifinal` LIKE '%"C2"%'
      OR `llave_semifinal` LIKE '%"C3"%'
      OR `llave_semifinal` LIKE '%"C4"%'
  );

-- -----------------------------------------------------------------------------
-- 3) grupos (zona semifinal): referencia_config C1–C4 → CU1–CU4
-- -----------------------------------------------------------------------------
UPDATE `grupos`
SET `referencia_config` = 'CU1'
WHERE `zona` = 'semifinal' AND `referencia_config` = 'C1';

UPDATE `grupos`
SET `referencia_config` = 'CU2'
WHERE `zona` = 'semifinal' AND `referencia_config` = 'C2';

UPDATE `grupos`
SET `referencia_config` = 'CU3'
WHERE `zona` = 'semifinal' AND `referencia_config` = 'C3';

UPDATE `grupos`
SET `referencia_config` = 'CU4'
WHERE `zona` = 'semifinal' AND `referencia_config` = 'C4';

-- -----------------------------------------------------------------------------
-- Verificación opcional (descomentar)
-- -----------------------------------------------------------------------------
-- SHOW COLUMNS FROM `grupos` LIKE 'posicion_grupo';
-- SELECT id, LEFT(llave_semifinal, 200) FROM configuracion_cruces_puntuables WHERE llave_semifinal LIKE '%CU%' LIMIT 10;
-- SELECT id, torneo_id, zona, referencia_config FROM grupos WHERE zona = 'semifinal' AND referencia_config LIKE 'CU%' LIMIT 20;
