-- =====================================================================
--  MIGRACIÓN v4 -> v5 · Fundaciones del mundo abierto (Fase 0)
--  Correr:  mysql -uroot -p < sql/migracion_v4_a_v5.sql
--  Cambios:
--    * POSICIONES_MUNDO pasa a ENGINE=MEMORY (sin escrituras a disco ni joins).
-- =====================================================================
USE proyecto2026;
SET NAMES utf8mb4;

DROP TABLE IF EXISTS POSICIONES_MUNDO;
-- Dónde está parado cada jugador. Es un CACHÉ EN MEMORIA (ENGINE=MEMORY):
--   * se mueve varias veces por segundo y no hace falta que sobreviva a un reinicio de MySQL
--     (al volver a entrar al mapa se recrea sola);
--   * guarda una copia de nombre, bando y MMR para listar a los jugadores sin hacer joins
--     (la fuente de verdad sigue siendo USUARIOS y JUGADORES; se refresca cada pocos segundos).
-- Las tablas MEMORY no admiten claves foráneas.
CREATE TABLE IF NOT EXISTS POSICIONES_MUNDO (
    IDUsuario INT NOT NULL,
    Nombre VARCHAR(30) NOT NULL,
    Bando VARCHAR(5) NULL,
    MMR INT NOT NULL DEFAULT 1000,
    Visible TINYINT NOT NULL DEFAULT 1,
    X SMALLINT NOT NULL,
    Y SMALLINT NOT NULL,
    Direccion TINYINT NOT NULL DEFAULT 0,
    UltimaActividad DATETIME(3) NOT NULL,
    PRIMARY KEY (IDUsuario),
    INDEX idx_posiciones_actividad (UltimaActividad) USING BTREE
) ENGINE=MEMORY;

GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.POSICIONES_MUNDO TO 'reboo_app'@'localhost';
FLUSH PRIVILEGES;
