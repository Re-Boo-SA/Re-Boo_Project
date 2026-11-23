-- =====================================================================
--  MIGRACIÓN v4 -> v4.1 · Reportes de jugadores (RF27) y moderación (RF26)
--  Para bases v4 que ya tienen datos. Correr como root (desde cmd):
--      mysql -uroot -p < sql/migracion_v4_reportes.sql
--  Es lo mismo que la sección "v4.1" al final de "Proyecto 2026, BDDv4.sql".
-- =====================================================================
USE proyecto2026;
SET NAMES utf8mb4;

ALTER TABLE REPORTES
    ADD COLUMN Tipo VARCHAR(10) NOT NULL DEFAULT 'bug' AFTER IDReportes,
    ADD COLUMN Descripcion VARCHAR(1000) NULL AFTER Motivo,
    ADD COLUMN IDReportante INT NULL AFTER Descripcion,
    ADD COLUMN IDDenunciado INT NULL AFTER IDReportante,
    ADD COLUMN IDPartida INT NULL AFTER IDDenunciado,
    ADD COLUMN Estado VARCHAR(12) NOT NULL DEFAULT 'pendiente' AFTER IDPartida,
    ADD COLUMN Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER Estado,
    ADD COLUMN FechaResolucion DATETIME NULL AFTER Fecha,
    ADD CONSTRAINT fk_reportes_reportante FOREIGN KEY (IDReportante) REFERENCES USUARIOS (IDUsuario),
    ADD CONSTRAINT fk_reportes_denunciado FOREIGN KEY (IDDenunciado) REFERENCES USUARIOS (IDUsuario),
    ADD CONSTRAINT fk_reportes_partida FOREIGN KEY (IDPartida) REFERENCES PARTIDAS (IDPartida),
    ADD CONSTRAINT chk_reportes_tipo CHECK (Tipo IN ('bug', 'jugador', 'chat')),
    ADD CONSTRAINT chk_reportes_estado CHECK (Estado IN ('pendiente', 'resuelto', 'descartado')),
    ADD INDEX idx_reportes_estado (Estado, Fecha);

-- Los reportes que ya tenía un administrador asignado se dan por resueltos;
-- los del chat de Berlín (empiezan con "Chat de Berlín") pasan a tipo 'chat'.
UPDATE REPORTES SET Tipo = 'chat' WHERE Motivo LIKE 'Chat de Berl%';
UPDATE REPORTES r
    INNER JOIN administra_reportes a ON a.IDReportes = r.IDReportes
    SET r.Estado = 'resuelto', r.FechaResolucion = r.Fecha;

GRANT SELECT, INSERT, UPDATE ON proyecto2026.REPORTES TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT ON proyecto2026.administra_reportes TO 'reboo_app'@'localhost';
FLUSH PRIVILEGES;
