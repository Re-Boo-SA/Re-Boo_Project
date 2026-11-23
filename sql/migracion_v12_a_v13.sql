-- =====================================================================
--  MIGRACIÓN v12 -> v13 · Berlín vivo, etapa C: misiones de tablero
--  Correr:  mysql -uroot -p < sql/migracion_v12_a_v13.sql
--  Agrega los bots de los contactos (no aparecen como rivales al azar) y los intentos de desafío.
-- =====================================================================
USE proyecto2026;
SET NAMES utf8mb4;

-- Bots con el nombre y el estilo de cada contacto (solo juegan desafíos de misiones)
INSERT IGNORE INTO USUARIOS (IDUsuario, Correo, Contra, NombreUsuario, Rol) VALUES
    (105, 'ingrid@bots.reboo.local', '!', 'Ingrid Vogel', 'jugador'),
    (106, 'paul@bots.reboo.local', '!', 'Paul Lange', 'jugador'),
    (107, 'gertrud@bots.reboo.local', '!', 'Gertrud Hartmann', 'jugador'),
    (108, 'lotte@bots.reboo.local', '!', 'Lotte', 'jugador');
INSERT IGNORE INTO JUGADORES (IDUsuario, EsBot, Estado, Biografia) VALUES
    (105, 1, 'en_linea', 'Juega por los costados y no le teme al Muro.'),
    (106, 1, 'en_linea', 'Militares y Fábrica: lo sólido primero.'),
    (107, 1, 'en_linea', 'Prolija: cada ficha en su lugar, el político primero.'),
    (108, 1, 'en_linea', 'Siempre sabe dónde están los espías.');

-- ===== Berlín vivo, etapa C: misiones de tablero =====
-- Intentos de un desafío (partida contra el bot de un contacto). Resultado: ganada | perdida | sin_secundario | abandonada.
CREATE TABLE IF NOT EXISTS MISION_PARTIDAS (
    IDPartida INT NOT NULL,
    IDUsuario INT NOT NULL,
    IDMision VARCHAR(40) NOT NULL,
    Paso TINYINT NOT NULL,
    Resultado VARCHAR(16) NULL,
    Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (IDPartida),
    INDEX idx_mision_partidas_usuario (IDUsuario, IDMision, Paso),
    FOREIGN KEY (IDPartida) REFERENCES PARTIDAS (IDPartida),
    FOREIGN KEY (IDUsuario) REFERENCES JUGADORES (IDUsuario),
    CONSTRAINT chk_mision_partidas_resultado CHECK (Resultado IS NULL OR Resultado IN ('ganada', 'perdida', 'sin_secundario', 'abandonada'))
);

GRANT SELECT, INSERT, UPDATE ON proyecto2026.MISION_PARTIDAS TO 'reboo_app'@'localhost';
FLUSH PRIVILEGES;
