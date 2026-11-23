-- =====================================================================
--  MIGRACIÓN v5 -> v6 · Mundo vivo (Fase 1)
--  Correr:  mysql -uroot -p < sql/migracion_v5_a_v6.sql
--  Cambios:
--    * APARIENCIA_JUGADOR: piel, pelo y gorro del personaje.
--    * POSICIONES_MUNDO (caché en memoria) guarda también el índice de apariencia.
--  La hora del día y el clima NO se guardan: se calculan con el reloj del servidor.
-- =====================================================================
USE proyecto2026;
SET NAMES utf8mb4;

-- Apariencia del personaje en el mundo abierto (Fase 1).
-- El índice del sprite es Piel * 12 + Pelo * 3 + Gorro (0 a 35); se calcula, no se guarda.
CREATE TABLE IF NOT EXISTS APARIENCIA_JUGADOR (
    IDUsuario INT NOT NULL,
    Piel TINYINT NOT NULL DEFAULT 0,
    Pelo TINYINT NOT NULL DEFAULT 0,
    Gorro TINYINT NOT NULL DEFAULT 0,
    PRIMARY KEY (IDUsuario),
    FOREIGN KEY (IDUsuario) REFERENCES JUGADORES (IDUsuario),
    CONSTRAINT chk_apariencia_rangos CHECK (Piel BETWEEN 0 AND 2 AND Pelo BETWEEN 0 AND 3 AND Gorro BETWEEN 0 AND 2)
);

ALTER TABLE POSICIONES_MUNDO ADD COLUMN Apariencia TINYINT NOT NULL DEFAULT 0 AFTER Visible;

GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.APARIENCIA_JUGADOR TO 'reboo_app'@'localhost';
FLUSH PRIVILEGES;
