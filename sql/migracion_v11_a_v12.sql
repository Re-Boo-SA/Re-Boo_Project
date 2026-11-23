-- =====================================================================
--  MIGRACIÓN v11 -> v12 · Berlín vivo, etapa B: sucesos
--  Correr:  mysql -uroot -p < sql/migracion_v11_a_v12.sql
-- =====================================================================
USE proyecto2026;
SET NAMES utf8mb4;

-- ===== Berlín vivo, etapa B: sucesos =====
-- Los sucesos no se guardan (se sortean con la hora del servidor). Acá solo se anota quién ya cobró
-- el premio de un suceso (por ejemplo, visitar el mercado), para que se cobre una sola vez.
CREATE TABLE IF NOT EXISTS SUCESOS_RECOMPENSAS (
    IDUsuario INT NOT NULL,
    Tramo INT NOT NULL,
    Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (IDUsuario, Tramo),
    FOREIGN KEY (IDUsuario) REFERENCES JUGADORES (IDUsuario)
);

GRANT SELECT, INSERT ON proyecto2026.SUCESOS_RECOMPENSAS TO 'reboo_app'@'localhost';
FLUSH PRIVILEGES;
