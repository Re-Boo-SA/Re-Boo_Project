-- =====================================================================
--  MIGRACIÓN v14 -> v15 · Campaña: combate táctico por turnos
--  Correr:  mysql -uroot -p < sql/migracion_v14_a_v15.sql
-- =====================================================================
USE proyecto2026;
SET NAMES utf8mb4;

-- ===== Campaña: combate táctico por turnos (etapa D) =====
-- Estado del combate en curso de cada jugador (lo valida y lo resuelve el servidor; la semilla de las tiradas nunca sale de acá).
CREATE TABLE IF NOT EXISTS CAMPANA_COMBATES (
    IDUsuario INT NOT NULL,
    IDCapitulo VARCHAR(40) NOT NULL,
    IDCombate VARCHAR(40) NOT NULL,
    Estado MEDIUMTEXT NOT NULL,
    Resultado VARCHAR(12) NULL,
    Intentos INT NOT NULL DEFAULT 1,
    Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (IDUsuario, IDCapitulo, IDCombate),
    FOREIGN KEY (IDUsuario) REFERENCES JUGADORES (IDUsuario),
    CONSTRAINT chk_campana_combate_resultado CHECK (Resultado IS NULL OR Resultado IN ('victoria', 'derrota'))
);

GRANT SELECT, INSERT, UPDATE ON proyecto2026.CAMPANA_COMBATES TO 'reboo_app'@'localhost';
FLUSH PRIVILEGES;
