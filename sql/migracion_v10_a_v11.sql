-- =====================================================================
--  MIGRACIÓN v10 -> v11 · Berlín vivo, etapa A: confianza y memoria de los contactos
--  Correr:  mysql -uroot -p < sql/migracion_v10_a_v11.sql
-- =====================================================================
USE proyecto2026;
SET NAMES utf8mb4;

-- ===== Berlín vivo, etapa A: lo que cada contacto piensa y recuerda de cada jugador =====
-- Las rutinas, el ánimo y los diálogos están en datos/mundo (no se guardan: dependen de la hora y el clima).
CREATE TABLE IF NOT EXISTS RELACION_NPC (
    IDUsuario INT NOT NULL,
    IDContacto VARCHAR(30) NOT NULL,
    Confianza SMALLINT NOT NULL DEFAULT 0,
    PRIMARY KEY (IDUsuario, IDContacto),
    FOREIGN KEY (IDUsuario) REFERENCES JUGADORES (IDUsuario),
    CONSTRAINT chk_relacion_confianza CHECK (Confianza BETWEEN -100 AND 100)
);

CREATE TABLE IF NOT EXISTS RECUERDOS_NPC (
    IDUsuario INT NOT NULL,
    IDContacto VARCHAR(30) NOT NULL,
    Clave VARCHAR(80) NOT NULL,
    Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (IDUsuario, IDContacto, Clave),
    FOREIGN KEY (IDUsuario) REFERENCES JUGADORES (IDUsuario)
);

GRANT SELECT, INSERT, UPDATE ON proyecto2026.RELACION_NPC TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT ON proyecto2026.RECUERDOS_NPC TO 'reboo_app'@'localhost';
FLUSH PRIVILEGES;
