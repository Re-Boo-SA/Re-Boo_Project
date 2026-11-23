-- =====================================================================
--  MIGRACIÓN v13 -> v14 · Campaña "Nacht über Berlin": progreso y decisiones
--  Correr:  mysql -uroot -p < sql/migracion_v13_a_v14.sql
-- =====================================================================
USE proyecto2026;
SET NAMES utf8mb4;

-- Corrección: una temporada empatada ('empate', 6 letras) no entraba en la columna (era de 5).
ALTER TABLE TEMPORADAS MODIFY BandoGanador VARCHAR(6) NULL;

-- ===== Campaña "Nacht über Berlin" (etapa D) =====
-- El guion está en datos/campana/*.json. Acá solo va hasta dónde llegó cada jugador y qué decidió.
CREATE TABLE IF NOT EXISTS CAMPANA_PROGRESO (
    IDUsuario INT NOT NULL,
    IDCapitulo VARCHAR(40) NOT NULL,
    Estado VARCHAR(12) NOT NULL DEFAULT 'en_curso',
    PuntoGuardado VARCHAR(40) NULL,
    FechaInicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaFin DATETIME NULL,
    PRIMARY KEY (IDUsuario, IDCapitulo),
    FOREIGN KEY (IDUsuario) REFERENCES JUGADORES (IDUsuario),
    CONSTRAINT chk_campana_estado CHECK (Estado IN ('en_curso', 'completado'))
);

CREATE TABLE IF NOT EXISTS CAMPANA_DECISIONES (
    IDUsuario INT NOT NULL,
    Clave VARCHAR(60) NOT NULL,
    Valor VARCHAR(60) NOT NULL,
    Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (IDUsuario, Clave),
    FOREIGN KEY (IDUsuario) REFERENCES JUGADORES (IDUsuario)
);

GRANT SELECT, INSERT, UPDATE ON proyecto2026.CAMPANA_PROGRESO TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE ON proyecto2026.CAMPANA_DECISIONES TO 'reboo_app'@'localhost';
FLUSH PRIVILEGES;
