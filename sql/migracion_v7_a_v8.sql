-- =====================================================================
--  MIGRACIÓN v7 -> v8 · Misiones, experiencia y logros (Fase 2)
--  Correr:  mysql -uroot -p < sql/migracion_v7_a_v8.sql
-- =====================================================================
USE proyecto2026;
SET NAMES utf8mb4;

ALTER TABLE JUGADORES ADD COLUMN XP INT NOT NULL DEFAULT 0 AFTER PuntosHistoricos,
    ADD CONSTRAINT chk_jugadores_xp CHECK (XP >= 0);
-- Copia del XP en la caché del mapa (para mostrar el nivel sobre cada personaje)
ALTER TABLE POSICIONES_MUNDO ADD COLUMN XP INT NOT NULL DEFAULT 0 AFTER Apariencia;

-- Misiones y logros del mundo abierto (Fase 2).
-- Las misiones y logros se DEFINEN en datos/mundo/misiones.json; acá solo se guarda el progreso.
-- El nivel no se guarda: sale de JUGADORES.XP (se llega al nivel n+1 con 50·n·(n+1) XP).
CREATE TABLE IF NOT EXISTS MISIONES_JUGADOR (
    IDUsuario INT NOT NULL,
    IDMision VARCHAR(40) NOT NULL,
    Estado VARCHAR(12) NOT NULL DEFAULT 'activa',
    Paso TINYINT NOT NULL DEFAULT 0,
    FechaInicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaPaso DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaFin DATETIME NULL,
    PRIMARY KEY (IDUsuario, IDMision),
    FOREIGN KEY (IDUsuario) REFERENCES JUGADORES (IDUsuario),
    CONSTRAINT chk_misiones_estado CHECK (Estado IN ('activa', 'completada')),
    CONSTRAINT chk_misiones_paso CHECK (Paso >= 0)
);

CREATE TABLE IF NOT EXISTS LOGROS_JUGADOR (
    IDUsuario INT NOT NULL,
    IDLogro VARCHAR(40) NOT NULL,
    Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (IDUsuario, IDLogro),
    FOREIGN KEY (IDUsuario) REFERENCES JUGADORES (IDUsuario)
);

GRANT SELECT, INSERT, UPDATE ON proyecto2026.MISIONES_JUGADOR TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT ON proyecto2026.LOGROS_JUGADOR TO 'reboo_app'@'localhost';
FLUSH PRIVILEGES;
