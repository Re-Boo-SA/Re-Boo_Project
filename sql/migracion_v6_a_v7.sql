-- =====================================================================
--  MIGRACIÓN v6 -> v7 · Reportes de jugadores (RF27) y moderación (RF26)
--  Correr:  mysql -uroot -p < sql/migracion_v6_a_v7.sql
--  Se puede correr aunque ya hayas aplicado sql/anteriores/migracion_v4_reportes.sql:
--  si REPORTES ya tiene las columnas nuevas, no hace nada.
-- =====================================================================
USE proyecto2026;
SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS reboo_migrar_reportes;
DELIMITER //
CREATE PROCEDURE reboo_migrar_reportes()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = 'proyecto2026' AND TABLE_NAME = 'REPORTES' AND COLUMN_NAME = 'Tipo') THEN
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
        UPDATE REPORTES SET Tipo = 'chat' WHERE Motivo LIKE 'Chat de Berl%';
        UPDATE REPORTES r INNER JOIN administra_reportes a ON a.IDReportes = r.IDReportes
            SET r.Estado = 'resuelto', r.FechaResolucion = r.Fecha;
    END IF;
END //
DELIMITER ;
CALL reboo_migrar_reportes();
DROP PROCEDURE reboo_migrar_reportes;

GRANT SELECT, INSERT, UPDATE ON proyecto2026.REPORTES TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT ON proyecto2026.administra_reportes TO 'reboo_app'@'localhost';
FLUSH PRIVILEGES;
