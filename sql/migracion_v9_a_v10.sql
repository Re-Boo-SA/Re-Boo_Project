-- =====================================================================
--  MIGRACIÓN v9 -> v10 · Torneos relámpago (cierre de la Fase 4)
--  Correr:  mysql -uroot -p < sql/migracion_v9_a_v10.sql
-- =====================================================================
USE proyecto2026;
SET NAMES utf8mb4;

-- ===== Fase 4: torneos relámpago (eliminación directa de 4 u 8) =====
CREATE TABLE IF NOT EXISTS TORNEOS (
    IDTorneo INT NOT NULL AUTO_INCREMENT,
    Nombre VARCHAR(40) NOT NULL,
    Tamano TINYINT NOT NULL,
    Estado VARCHAR(12) NOT NULL DEFAULT 'inscripcion',
    IDCreador INT NOT NULL,
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaInicio DATETIME NOT NULL,
    Ronda TINYINT NOT NULL DEFAULT 0,
    IDGanador INT NULL,
    FechaFin DATETIME NULL,
    PRIMARY KEY (IDTorneo),
    INDEX idx_torneos_estado (Estado, FechaInicio),
    FOREIGN KEY (IDCreador) REFERENCES USUARIOS (IDUsuario),
    FOREIGN KEY (IDGanador) REFERENCES USUARIOS (IDUsuario),
    CONSTRAINT chk_torneos_tamano CHECK (Tamano IN (4, 8)),
    CONSTRAINT chk_torneos_estado CHECK (Estado IN ('inscripcion', 'en_curso', 'finalizado', 'cancelado'))
);

CREATE TABLE IF NOT EXISTS TORNEO_JUGADORES (
    IDTorneo INT NOT NULL,
    IDUsuario INT NOT NULL,
    Semilla TINYINT NULL,
    Eliminado BOOLEAN NOT NULL DEFAULT 0,
    Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (IDTorneo, IDUsuario),
    INDEX idx_torneo_jugadores_usuario (IDUsuario),
    FOREIGN KEY (IDTorneo) REFERENCES TORNEOS (IDTorneo),
    FOREIGN KEY (IDUsuario) REFERENCES USUARIOS (IDUsuario)
);

-- Una llave = un cruce del cuadro. Sin rival (B nulo) = pasa directo.
CREATE TABLE IF NOT EXISTS TORNEO_LLAVES (
    IDTorneo INT NOT NULL,
    Ronda TINYINT NOT NULL,
    Posicion TINYINT NOT NULL,
    IDJugadorA INT NOT NULL,
    IDJugadorB INT NULL,
    IDPartida INT NULL,
    IDGanador INT NULL,
    Resultado VARCHAR(16) NULL,
    PRIMARY KEY (IDTorneo, Ronda, Posicion),
    UNIQUE KEY uq_torneo_llaves_partida (IDPartida),
    FOREIGN KEY (IDTorneo) REFERENCES TORNEOS (IDTorneo),
    FOREIGN KEY (IDJugadorA) REFERENCES USUARIOS (IDUsuario),
    FOREIGN KEY (IDJugadorB) REFERENCES USUARIOS (IDUsuario),
    FOREIGN KEY (IDPartida) REFERENCES PARTIDAS (IDPartida),
    FOREIGN KEY (IDGanador) REFERENCES USUARIOS (IDUsuario),
    CONSTRAINT chk_torneo_llaves_resultado CHECK (Resultado IS NULL OR Resultado IN ('partida', 'sin_rival', 'no_se_presento', 'desempate'))
);

GRANT SELECT, INSERT, UPDATE ON proyecto2026.TORNEOS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.TORNEO_JUGADORES TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE ON proyecto2026.TORNEO_LLAVES TO 'reboo_app'@'localhost';
FLUSH PRIVILEGES;
