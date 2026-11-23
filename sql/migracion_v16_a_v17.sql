-- =====================================================================
--  MIGRACIÓN v16 -> v17 · Personalización: personaje del mundo abierto y perfil
--  Correr:  mysql -uroot -p < sql/migracion_v16_a_v17.sql
-- =====================================================================
USE proyecto2026;
SET NAMES utf8mb4;

-- Personaje personalizado del mundo abierto: un código corto con formas, accesorios y 7 colores (ver js/apariencia.js)
-- Formato: p<pelo 0-4>s<sombrero 0-4>a<abrigo 0-2>x<accesorios hex>-<7 colores hex: piel pelo abrigo detalle pantalón sombrero bufanda>
SET @s = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'proyecto2026' AND TABLE_NAME = 'APARIENCIA_JUGADOR' AND COLUMN_NAME = 'Personal') = 0,
    'ALTER TABLE APARIENCIA_JUGADOR ADD COLUMN Personal VARCHAR(60) CHARACTER SET ascii NULL', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'proyecto2026' AND TABLE_NAME = 'POSICIONES_MUNDO' AND COLUMN_NAME = 'Personal') = 0,
    'ALTER TABLE POSICIONES_MUNDO ADD COLUMN Personal VARCHAR(60) CHARACTER SET ascii NULL', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- Perfil al estilo Discord: banner, color de acento, estado, pronombres, logros destacados y marco del avatar
CREATE TABLE IF NOT EXISTS PERFIL_PERSONAL (
    IDUsuario INT NOT NULL,
    Banner1 CHAR(7) CHARACTER SET ascii NOT NULL DEFAULT '#1b2440',
    Banner2 CHAR(7) CHARACTER SET ascii NOT NULL DEFAULT '#3a2a4a',
    Acento CHAR(7) CHARACTER SET ascii NOT NULL DEFAULT '#f4c430',
    EstadoEmoji VARCHAR(8) NULL,
    EstadoTexto VARCHAR(80) NULL,
    Pronombres VARCHAR(24) NULL,
    Insignias VARCHAR(200) CHARACTER SET ascii NULL,
    Marco VARCHAR(20) CHARACTER SET ascii NOT NULL DEFAULT 'ninguno',
    PRIMARY KEY (IDUsuario),
    FOREIGN KEY (IDUsuario) REFERENCES JUGADORES (IDUsuario)
);

GRANT SELECT, INSERT, UPDATE ON proyecto2026.PERFIL_PERSONAL TO 'reboo_app'@'localhost';
FLUSH PRIVILEGES;
