-- =====================================================================
--  MIGRACIÓN v8 -> v9 · Social (Fase 4): amigos, bloqueos, grupos, invitaciones y gestos
--  Correr:  mysql -uroot -p < sql/migracion_v8_a_v9.sql
-- =====================================================================
USE proyecto2026;
SET NAMES utf8mb4;

-- Gesto que está haciendo cada jugador en Berlín (caché en memoria)
ALTER TABLE POSICIONES_MUNDO ADD COLUMN Gesto TINYINT NOT NULL DEFAULT 0 AFTER XP,
    ADD COLUMN GestoHasta DATETIME(3) NULL AFTER Gesto;

-- ===== Fase 4: social =====
-- Amistades: una fila por par. ParMenor/ParMayor (columnas calculadas) impiden que existan
-- a la vez "A pidió a B" y "B pidió a A".
CREATE TABLE IF NOT EXISTS AMISTADES (
    IDSolicitante INT NOT NULL,
    IDDestino INT NOT NULL,
    Estado VARCHAR(10) NOT NULL DEFAULT 'pendiente',
    Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaRespuesta DATETIME NULL,
    ParMenor INT AS (LEAST(IDSolicitante, IDDestino)) STORED,
    ParMayor INT AS (GREATEST(IDSolicitante, IDDestino)) STORED,
    PRIMARY KEY (IDSolicitante, IDDestino),
    UNIQUE KEY uq_amistades_par (ParMenor, ParMayor),
    INDEX idx_amistades_destino (IDDestino, Estado),
    FOREIGN KEY (IDSolicitante) REFERENCES USUARIOS (IDUsuario),
    FOREIGN KEY (IDDestino) REFERENCES USUARIOS (IDUsuario),
    CONSTRAINT chk_amistades_distintos CHECK (IDSolicitante <> IDDestino),
    CONSTRAINT chk_amistades_estado CHECK (Estado IN ('pendiente', 'aceptada'))
);

-- Bloqueos: el bloqueado no te ve en Berlín, no te escribe y no te puede invitar.
CREATE TABLE IF NOT EXISTS BLOQUEOS (
    IDUsuario INT NOT NULL,
    IDBloqueado INT NOT NULL,
    Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (IDUsuario, IDBloqueado),
    INDEX idx_bloqueos_bloqueado (IDBloqueado),
    FOREIGN KEY (IDUsuario) REFERENCES USUARIOS (IDUsuario),
    FOREIGN KEY (IDBloqueado) REFERENCES USUARIOS (IDUsuario),
    CONSTRAINT chk_bloqueos_distintos CHECK (IDUsuario <> IDBloqueado)
);

-- Grupos de hasta 4 (el límite lo controla la lógica). Un jugador está en un solo grupo.
CREATE TABLE IF NOT EXISTS GRUPOS (
    IDGrupo INT NOT NULL AUTO_INCREMENT,
    IDLider INT NOT NULL,
    Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (IDGrupo),
    FOREIGN KEY (IDLider) REFERENCES USUARIOS (IDUsuario)
);

CREATE TABLE IF NOT EXISTS GRUPO_MIEMBROS (
    IDUsuario INT NOT NULL,
    IDGrupo INT NOT NULL,
    Fecha DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (IDUsuario),
    INDEX idx_grupo_miembros_grupo (IDGrupo),
    FOREIGN KEY (IDUsuario) REFERENCES USUARIOS (IDUsuario),
    FOREIGN KEY (IDGrupo) REFERENCES GRUPOS (IDGrupo) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS MENSAJES_GRUPO (
    IDMensaje BIGINT NOT NULL AUTO_INCREMENT,
    IDGrupo INT NOT NULL,
    IDUsuario INT NOT NULL,
    Texto VARCHAR(200) NOT NULL,
    Fecha DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (IDMensaje),
    INDEX idx_mensajes_grupo (IDGrupo, IDMensaje),
    FOREIGN KEY (IDGrupo) REFERENCES GRUPOS (IDGrupo) ON DELETE CASCADE,
    FOREIGN KEY (IDUsuario) REFERENCES USUARIOS (IDUsuario)
);

-- Invitaciones a un grupo o a una mesa (se borran al responderlas).
CREATE TABLE IF NOT EXISTS INVITACIONES (
    IDInvitacion INT NOT NULL AUTO_INCREMENT,
    Tipo VARCHAR(5) NOT NULL,
    IDInvitador INT NOT NULL,
    IDInvitado INT NOT NULL,
    IDGrupo INT NULL,
    IDPartida INT NULL,
    Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (IDInvitacion),
    UNIQUE KEY uq_invitaciones (Tipo, IDInvitado, IDGrupo, IDPartida),
    INDEX idx_invitaciones_invitado (IDInvitado),
    FOREIGN KEY (IDInvitador) REFERENCES USUARIOS (IDUsuario),
    FOREIGN KEY (IDInvitado) REFERENCES USUARIOS (IDUsuario),
    FOREIGN KEY (IDGrupo) REFERENCES GRUPOS (IDGrupo) ON DELETE CASCADE,
    FOREIGN KEY (IDPartida) REFERENCES PARTIDAS (IDPartida) ON DELETE CASCADE,
    CONSTRAINT chk_invitaciones_tipo CHECK ((Tipo = 'grupo' AND IDGrupo IS NOT NULL) OR (Tipo = 'mesa' AND IDPartida IS NOT NULL))
);

GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.AMISTADES TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, DELETE ON proyecto2026.BLOQUEOS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.GRUPOS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, DELETE ON proyecto2026.GRUPO_MIEMBROS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT ON proyecto2026.MENSAJES_GRUPO TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, DELETE ON proyecto2026.INVITACIONES TO 'reboo_app'@'localhost';
FLUSH PRIVILEGES;
