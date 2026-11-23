-- =====================================================================
--  MIGRACIÓN v3 -> v4 · Skins reales + MUNDO ABIERTO (Berlín)
--  Correr como root:  mysql -uroot -p < sql/migracion_v3_a_v4.sql
-- =====================================================================
USE proyecto2026;
SET NAMES utf8mb4;

-- ---------- Skins: 3 por ficha (Azul Oeste, Rojo Este, Bronce) con imagen real ----------
-- IDSkin se numera dentro de cada ficha (la clave es IDSkin + IDFicha).
INSERT INTO SKINS (IDSkin, IDFicha, NombreSkin, Precio, URL) VALUES
    (1, 1, 'Azul Oeste', 120, 'img/skins/general_oeste.png'),
    (2, 1, 'Militar rojo', 120, 'img/skins/general_este.png'),
    (3, 1, 'Bronce', 300, 'img/skins/general_bronce.png'),
    (1, 2, 'Azul Oeste', 120, 'img/skins/oficial_militar_oeste.png'),
    (2, 2, 'Rojo Este', 120, 'img/skins/oficial_militar_este.png'),
    (3, 2, 'Bronce', 300, 'img/skins/oficial_militar_bronce.png'),
    (1, 3, 'Azul Oeste', 120, 'img/skins/tropa_oeste.png'),
    (2, 3, 'Rojo Este', 120, 'img/skins/tropa_este.png'),
    (3, 3, 'Bronce', 300, 'img/skins/tropa_bronce.png'),
    (1, 4, 'Azul Oeste', 120, 'img/skins/cabo_oeste.png'),
    (2, 4, 'Rojo Este', 120, 'img/skins/cabo_este.png'),
    (3, 4, 'Bronce', 300, 'img/skins/cabo_bronce.png'),
    (1, 5, 'Azul Oeste', 120, 'img/skins/oficial_obrera_oeste.png'),
    (2, 5, 'Rojo Este', 120, 'img/skins/oficial_obrera_este.png'),
    (3, 5, 'Bronce', 300, 'img/skins/oficial_obrera_bronce.png'),
    (1, 6, 'Azul Oeste', 120, 'img/skins/peon_oeste.png'),
    (2, 6, 'Rojo Este', 120, 'img/skins/peon_este.png'),
    (3, 6, 'Bronce', 300, 'img/skins/peon_bronce.png'),
    (1, 7, 'Azul Oeste', 120, 'img/skins/politico_oeste.png'),
    (2, 7, 'Rojo Este', 120, 'img/skins/politico_este.png'),
    (3, 7, 'Bronce', 300, 'img/skins/politico_bronce.png'),
    (1, 8, 'Espía azul', 150, 'img/skins/markus_oeste.png'),
    (2, 8, 'Rojo Este', 120, 'img/skins/markus_este.png'),
    (3, 8, 'Bronce', 300, 'img/skins/markus_bronce.png'),
    (1, 9, 'Azul Oeste', 120, 'img/skins/ursula_oeste.png'),
    (2, 9, 'Rojo Este', 120, 'img/skins/ursula_este.png'),
    (3, 9, 'Bronce', 300, 'img/skins/ursula_bronce.png')
ON DUPLICATE KEY UPDATE NombreSkin = VALUES(NombreSkin), Precio = VALUES(Precio), URL = VALUES(URL);
INSERT IGNORE INTO tienda_skins (IDTienda, IDSkin, IDFicha)
    SELECT 1, IDSkin, IDFicha FROM SKINS;

-- ---------- Temporadas de 30 días ----------
CREATE TABLE IF NOT EXISTS TEMPORADAS (
    IDTemporada INT AUTO_INCREMENT,
    Numero INT NOT NULL UNIQUE,
    FechaInicio DATETIME NOT NULL,
    FechaFin DATETIME NOT NULL,
    Cerrada BOOLEAN NOT NULL DEFAULT 0,
    BandoGanador VARCHAR(5) NULL,
    DistritosOeste TINYINT NULL,
    DistritosEste TINYINT NULL,
    PRIMARY KEY (IDTemporada),
    CONSTRAINT chk_temporadas_bando CHECK (BandoGanador IS NULL OR BandoGanador IN ('oeste', 'este', 'empate'))
);
INSERT INTO TEMPORADAS (Numero, FechaInicio, FechaFin) VALUES (1, NOW(), NOW() + INTERVAL 30 DAY);

-- ---------- Bando del jugador: 'oeste' (EE. UU.) o 'este' (URSS) ----------
ALTER TABLE JUGADORES
    ADD COLUMN Bando VARCHAR(5) NULL AFTER Estado,
    ADD COLUMN IDTemporadaBando INT NULL AFTER Bando,
    ADD CONSTRAINT chk_jugadores_bando CHECK (Bando IS NULL OR Bando IN ('oeste', 'este')),
    ADD CONSTRAINT fk_jugadores_temporada_bando FOREIGN KEY (IDTemporadaBando) REFERENCES TEMPORADAS (IDTemporada);

-- ---------- Distritos del mapa (coordenadas en unidades del mapa de 2400 × 1400) ----------
CREATE TABLE IF NOT EXISTS DISTRITOS (
    IDDistrito INT NOT NULL,
    Codigo VARCHAR(30) NOT NULL UNIQUE,
    Nombre VARCHAR(40) NOT NULL,
    Lado VARCHAR(5) NOT NULL,
    X SMALLINT NOT NULL,
    Y SMALLINT NOT NULL,
    Ancho SMALLINT NOT NULL,
    Alto SMALLINT NOT NULL,
    CafeX SMALLINT NOT NULL,
    CafeY SMALLINT NOT NULL,
    PRIMARY KEY (IDDistrito),
    CONSTRAINT chk_distritos_lado CHECK (Lado IN ('oeste', 'este'))
);
INSERT INTO DISTRITOS (IDDistrito, Codigo, Nombre, Lado, X, Y, Ancho, Alto, CafeX, CafeY) VALUES
    (1,  'spandau',         'Spandau',         'oeste', 0,    0,   600, 466, 300,  240),
    (2,  'wedding',         'Wedding',         'oeste', 600,  0,   600, 466, 860,  200),
    (3,  'charlottenburg',  'Charlottenburg',  'oeste', 0,    466, 600, 467, 280,  700),
    (4,  'tiergarten',      'Tiergarten',      'oeste', 600,  466, 600, 467, 760,  800),
    (5,  'schoeneberg',     'Schöneberg',      'oeste', 0,    933, 600, 467, 330,  1180),
    (6,  'kreuzberg',       'Kreuzberg',       'oeste', 600,  933, 600, 467, 820,  1240),
    (7,  'prenzlauer_berg', 'Prenzlauer Berg', 'este',  1200, 0,   600, 466, 1540, 220),
    (8,  'pankow',          'Pankow',          'este',  1800, 0,   600, 466, 2100, 240),
    (9,  'mitte',           'Mitte',           'este',  1200, 466, 600, 467, 1560, 640),
    (10, 'lichtenberg',     'Lichtenberg',     'este',  1800, 466, 600, 467, 2120, 640),
    (11, 'friedrichshain',  'Friedrichshain',  'este',  1200, 933, 600, 467, 1520, 1220),
    (12, 'treptow',         'Treptow',         'este',  1800, 933, 600, 467, 2100, 1180);

-- Influencia de cada bando en cada distrito, por temporada.
CREATE TABLE IF NOT EXISTS INFLUENCIA (
    IDTemporada INT NOT NULL,
    IDDistrito INT NOT NULL,
    Bando VARCHAR(5) NOT NULL,
    Puntos INT NOT NULL DEFAULT 0,
    PRIMARY KEY (IDTemporada, IDDistrito, Bando),
    FOREIGN KEY (IDTemporada) REFERENCES TEMPORADAS (IDTemporada),
    FOREIGN KEY (IDDistrito) REFERENCES DISTRITOS (IDDistrito),
    CONSTRAINT chk_influencia_bando CHECK (Bando IN ('oeste', 'este')),
    CONSTRAINT chk_influencia_puntos CHECK (Puntos >= 0)
);

-- Distrito del café donde se creó cada partida (las de la cola competitiva no tienen).
CREATE TABLE IF NOT EXISTS PARTIDA_DISTRITO (
    IDPartida INT NOT NULL,
    IDDistrito INT NOT NULL,
    PRIMARY KEY (IDPartida),
    FOREIGN KEY (IDPartida) REFERENCES PARTIDAS (IDPartida) ON DELETE CASCADE,
    FOREIGN KEY (IDDistrito) REFERENCES DISTRITOS (IDDistrito)
);

-- Dónde está parado cada jugador en el mapa (el distrito se calcula con X e Y).
CREATE TABLE IF NOT EXISTS POSICIONES_MUNDO (
    IDUsuario INT NOT NULL,
    X SMALLINT NOT NULL,
    Y SMALLINT NOT NULL,
    Direccion TINYINT NOT NULL DEFAULT 0,
    UltimaActividad DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (IDUsuario),
    FOREIGN KEY (IDUsuario) REFERENCES JUGADORES (IDUsuario),
    INDEX idx_posiciones_actividad (UltimaActividad)
);

-- Burbujas de chat: se muestran unos segundos y se borran a los 10 minutos.
CREATE TABLE IF NOT EXISTS MENSAJES_MUNDO (
    IDMensaje BIGINT AUTO_INCREMENT,
    IDUsuario INT NOT NULL,
    Texto VARCHAR(80) NOT NULL,
    Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (IDMensaje),
    FOREIGN KEY (IDUsuario) REFERENCES JUGADORES (IDUsuario),
    INDEX idx_mensajes_fecha (Fecha)
);

-- ---------- Permisos del usuario de la app ----------
GRANT SELECT ON proyecto2026.DISTRITOS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE ON proyecto2026.TEMPORADAS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.INFLUENCIA TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.PARTIDA_DISTRITO TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.POSICIONES_MUNDO TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, DELETE ON proyecto2026.MENSAJES_MUNDO TO 'reboo_app'@'localhost';
GRANT INSERT ON proyecto2026.REPORTES TO 'reboo_app'@'localhost';
FLUSH PRIVILEGES;
