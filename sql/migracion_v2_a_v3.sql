-- =====================================================================
--  MIGRACIÓN v2 -> v3 (para una base proyecto2026 que ya tiene datos)
--  Conserva usuarios, jugadores, skins compradas, reportes y auditoría.
--  Hacé un respaldo antes:  mysqldump -uroot -p proyecto2026 > respaldo.sql
--  Correr como root:       mysql -uroot -p < sql/migracion_v2_a_v3.sql
-- =====================================================================
USE proyecto2026;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------- Jugadores: columnas nuevas ----------
ALTER TABLE JUGADORES
    ADD COLUMN PuntosHistoricos INT NOT NULL DEFAULT 0 AFTER PntsPartida,
    ADD COLUMN MMR INT NOT NULL DEFAULT 1000 AFTER PartidasGanadas,
    ADD COLUMN PartidasCompetitivas INT NOT NULL DEFAULT 0 AFTER MMR,
    ADD COLUMN EsBot BOOLEAN NOT NULL DEFAULT 0 AFTER PartidasCompetitivas,
    ADD COLUMN TutorialVisto BOOLEAN NOT NULL DEFAULT 0 AFTER EsBot,
    ADD INDEX idx_jugadores_mmr (MMR),
    ADD INDEX idx_jugadores_historicos (PuntosHistoricos);
-- Lo mejor que se puede estimar: lo que tienen hoy de saldo.
UPDATE JUGADORES SET PuntosHistoricos = PntsPartida;

-- ---------- Catálogos ----------
CREATE TABLE CATEGORIAS (
    IDCategoria INT NOT NULL,
    Codigo VARCHAR(20) NOT NULL UNIQUE,
    Nombre VARCHAR(30) NOT NULL,
    PRIMARY KEY (IDCategoria)
);
INSERT INTO CATEGORIAS VALUES (1,'militares','Militares'),(2,'obreros','Obreros'),(3,'politicos','Políticos'),(4,'espias','Espías');

-- Las fichas v2 eran 1 'obrero' y 2 'politico'. Se reemplazan por las 9 reales
-- y se reubican las skins existentes (1 Espía azul -> Markus, 2 Militar rojo -> General).
CREATE TABLE FICHAS_NUEVAS (
    IDFicha INT AUTO_INCREMENT,
    Codigo VARCHAR(20) NOT NULL UNIQUE,
    FichaEspecie VARCHAR(50) NOT NULL,
    IDCategoria INT NOT NULL,
    CantidadTotal INT NOT NULL,
    Imagen VARCHAR(100) NOT NULL,
    BajaLogica BOOLEAN NOT NULL DEFAULT 0,
    PRIMARY KEY (IDFicha),
    FOREIGN KEY (IDCategoria) REFERENCES CATEGORIAS (IDCategoria),
    CONSTRAINT chk_fichas_cantidad CHECK (CantidadTotal >= 0)
);
INSERT INTO FICHAS_NUEVAS (IDFicha, Codigo, FichaEspecie, IDCategoria, CantidadTotal, Imagen) VALUES
    (1,'general','General',1,6,'GeneralDraftDerMauer.png'),
    (2,'oficial_militar','Oficial Militar',1,6,'OficialDraftDerMauer.png'),
    (3,'tropa','Tropa',1,6,'TropaDraftDerMauer.png'),
    (4,'cabo','Cabo',2,6,'ACaboDraftDerMauer.png'),
    (5,'oficial_obrera','Oficial Obrera',2,6,'OficialMujerDraftDerMauer.png'),
    (6,'peon','Peón',2,6,'PeonDraftDerMauer.png'),
    (7,'politico','Político',3,14,'PoliticoDraftDerMauer.png'),
    (8,'markus','Markus',4,5,'MarkusEspiaDraftDerMauer.png'),
    (9,'ursula','Ursula',4,5,'UrsulaEspiaDraftDerMauer.png');

-- Reubicar skins: primero a ids temporales para no chocar las claves.
UPDATE SKINS          SET IDFicha = IDFicha + 100;
UPDATE tienda_skins   SET IDFicha = IDFicha + 100;
UPDATE jugador_skins  SET IDFicha = IDFicha + 100;
UPDATE SKINS          SET IDFicha = CASE IDFicha WHEN 101 THEN 8 WHEN 102 THEN 1 ELSE 7 END;
UPDATE tienda_skins   SET IDFicha = CASE IDFicha WHEN 101 THEN 8 WHEN 102 THEN 1 ELSE 7 END;
UPDATE jugador_skins  SET IDFicha = CASE IDFicha WHEN 101 THEN 8 WHEN 102 THEN 1 ELSE 7 END;

-- JUGADAS v2 tenía datos de ejemplo con otra estructura: se descarta.
DROP TABLE JUGADAS;
DROP TABLE FICHAS;
RENAME TABLE FICHAS_NUEVAS TO FICHAS;
ALTER TABLE SKINS DROP FOREIGN KEY skins_ibfk_1;
ALTER TABLE SKINS ADD CONSTRAINT skins_ibfk_1 FOREIGN KEY (IDFicha) REFERENCES FICHAS(IDFicha);

DROP TABLE RECINTOS;
CREATE TABLE RECINTOS (
    IDRecinto INT AUTO_INCREMENT,
    Codigo VARCHAR(20) NOT NULL UNIQUE,
    NombreRecinto VARCHAR(50) NOT NULL,
    Capacidad INT NOT NULL,
    Lado VARCHAR(10) NOT NULL,
    IDCategoriaExclusiva INT NULL,
    BajaLogica BOOLEAN NOT NULL DEFAULT 0,
    PRIMARY KEY (IDRecinto),
    FOREIGN KEY (IDCategoriaExclusiva) REFERENCES CATEGORIAS (IDCategoria),
    CONSTRAINT chk_recintos_lado CHECK (Lado IN ('izquierdo', 'derecho', 'centro')),
    CONSTRAINT chk_recintos_capacidad CHECK (Capacidad > 0)
);
INSERT INTO RECINTOS (IDRecinto, Codigo, NombreRecinto, Capacidad, Lado, IDCategoriaExclusiva) VALUES
    (1,'oeste','Oeste',6,'izquierdo',NULL),(2,'fabrica','Fábrica',3,'izquierdo',2),
    (3,'espias','Espías',2,'izquierdo',4),(4,'politicos','Políticos',1,'derecho',3),
    (5,'este','Este',6,'derecho',NULL),(6,'militares','Militares',3,'derecho',1),
    (7,'muro','Muro Central',12,'centro',NULL);

CREATE TABLE CARAS_DADO (
    IDCara INT NOT NULL,
    Codigo VARCHAR(20) NOT NULL UNIQUE,
    Nombre VARCHAR(50) NOT NULL,
    Imagen VARCHAR(100) NOT NULL,
    PRIMARY KEY (IDCara)
);
INSERT INTO CARAS_DADO VALUES
    (1,'lado_izquierdo','Bloqueo del lado izquierdo','NoIzquierdoDadoDraftDerMauer.png'),
    (2,'lado_derecho','Bloqueo del lado derecho','NoDerechoDadoDraftDerMauer.png'),
    (3,'solo_militares','Solo Militares','SoloRecintoMilitaresDadoDraftDerMauer.png'),
    (4,'bloquea_fabrica','Bloqueo de Fábrica','NoFabricaDadoDraftDerMauer.png'),
    (5,'cambiar_ficha','Cambiar ficha','ReacomodarFichaDadoDraftDerMauer.png'),
    (6,'solo_espias','Solo Espías','SoloEspiasDadoDraftDerMauer.png');

CREATE TABLE skins_equipadas (
    IDUsuario INT NOT NULL,
    IDFicha INT NOT NULL,
    IDSkin INT NOT NULL,
    PRIMARY KEY (IDUsuario, IDFicha),
    FOREIGN KEY (IDUsuario, IDSkin, IDFicha) REFERENCES jugador_skins(IDUsuario, IDSkin, IDFicha) ON DELETE CASCADE
);

-- ---------- Partidas: PARTIDAS v2 guardaba puntajes como texto ("AURA:45").
-- Se recrea la tabla y lo que se pueda se pasa a PARTICIPACIONES.
CREATE TABLE PARTIDAS_V2 AS SELECT * FROM PARTIDAS;
DROP TABLE PARTIDAS;
CREATE TABLE IF NOT EXISTS PARTIDAS (
    IDPartida INT AUTO_INCREMENT,
    Nombre VARCHAR(40) NOT NULL,
    Tipo VARCHAR(10) NOT NULL DEFAULT 'publica',
    HashContra VARCHAR(255) NULL,
    Modo VARCHAR(15) NOT NULL DEFAULT 'normal',
    Estado VARCHAR(12) NOT NULL DEFAULT 'esperando',
    MaxJugadores TINYINT NOT NULL,
    IDCreador INT NULL,
    RondaActual TINYINT NOT NULL DEFAULT 0,
    TurnoActual TINYINT NOT NULL DEFAULT 0,
    Fase VARCHAR(10) NOT NULL DEFAULT 'dado',
    AsientoDado TINYINT NOT NULL DEFAULT 0,
    IDCara INT NULL,
    InicioFase DATETIME NULL,
    Version INT NOT NULL DEFAULT 0,
    Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaInicio DATETIME NULL,
    FechaFin DATETIME NULL,
    BajaLogica BOOLEAN NOT NULL DEFAULT 0,
    PRIMARY KEY (IDPartida),
    FOREIGN KEY (IDCreador) REFERENCES JUGADORES(IDUsuario),
    FOREIGN KEY (IDCara) REFERENCES CARAS_DADO(IDCara),
    INDEX idx_partidas_estado (Estado, Modo),
    CONSTRAINT chk_partidas_tipo CHECK (Tipo IN ('publica', 'privada')),
    CONSTRAINT chk_partidas_modo CHECK (Modo IN ('normal', 'personalizada', 'competitiva')),
    CONSTRAINT chk_partidas_estado CHECK (Estado IN ('esperando', 'en_curso', 'finalizada', 'cancelada')),
    CONSTRAINT chk_partidas_fase CHECK (Fase IN ('dado', 'colocar')),
    CONSTRAINT chk_partidas_jugadores CHECK (MaxJugadores BETWEEN 2 AND 5)
);

-- Reglas de cada partida (1 a 1 con PARTIDAS). Una partida normal o
-- competitiva usa los valores por defecto = reglas oficiales.
CREATE TABLE IF NOT EXISTS CONFIG_PARTIDA (
    IDPartida INT NOT NULL,
    Rondas TINYINT NOT NULL DEFAULT 2,
    FichasPorRonda TINYINT NOT NULL DEFAULT 6,
    CapacidadMuro TINYINT NOT NULL DEFAULT 12,
    SegundosTurno SMALLINT NOT NULL DEFAULT 60,
    ValorTipo1 TINYINT NOT NULL DEFAULT 1,
    ValorTipo2 TINYINT NOT NULL DEFAULT 3,
    ValorTipo3 TINYINT NOT NULL DEFAULT 7,
    PuntosEspias TINYINT NOT NULL DEFAULT 4,
    ReglaPolitico BOOLEAN NOT NULL DEFAULT 1,
    TiradorExento BOOLEAN NOT NULL DEFAULT 1,
    PermiteBots BOOLEAN NOT NULL DEFAULT 0,
    PRIMARY KEY (IDPartida),
    FOREIGN KEY (IDPartida) REFERENCES PARTIDAS(IDPartida) ON DELETE CASCADE,
    CONSTRAINT chk_config_rangos CHECK (Rondas BETWEEN 1 AND 3 AND FichasPorRonda BETWEEN 4 AND 8
        AND CapacidadMuro BETWEEN 1 AND 24 AND SegundosTurno BETWEEN 15 AND 300
        AND ValorTipo1 BETWEEN 0 AND 20 AND ValorTipo2 BETWEEN 0 AND 20 AND ValorTipo3 BETWEEN 0 AND 20
        AND PuntosEspias BETWEEN 0 AND 20)
);

-- Caras del dado habilitadas en la partida (personalizadas pueden quitar caras).
CREATE TABLE IF NOT EXISTS PARTIDA_CARAS (
    IDPartida INT NOT NULL,
    IDCara INT NOT NULL,
    PRIMARY KEY (IDPartida, IDCara),
    FOREIGN KEY (IDPartida) REFERENCES PARTIDAS(IDPartida) ON DELETE CASCADE,
    FOREIGN KEY (IDCara) REFERENCES CARAS_DADO(IDCara)
);

-- Un registro por jugador sentado en la partida.
-- Asiento: 0..n-1, las fichas y el dado pasan al asiento siguiente ("izquierda").
CREATE TABLE IF NOT EXISTS PARTICIPACIONES (
    IDPartida INT NOT NULL,
    IDUsuario INT NOT NULL,
    Asiento TINYINT NOT NULL,
    Estado VARCHAR(10) NOT NULL DEFAULT 'activo',
    Confirmado BOOLEAN NOT NULL DEFAULT 0,
    UltimoLatido DATETIME NULL,
    PuntajeFinal INT NULL,
    Posicion TINYINT NULL,
    PuntosOtorgados INT NOT NULL DEFAULT 0,
    MMRAntes INT NULL,
    MMRDespues INT NULL,
    FechaUnion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (IDPartida, IDUsuario),
    UNIQUE KEY uq_participaciones_asiento (IDPartida, Asiento),
    FOREIGN KEY (IDPartida) REFERENCES PARTIDAS(IDPartida) ON DELETE CASCADE,
    FOREIGN KEY (IDUsuario) REFERENCES JUGADORES(IDUsuario),
    INDEX idx_participaciones_usuario (IDUsuario, Estado),
    CONSTRAINT chk_participaciones_estado CHECK (Estado IN ('activo', 'abandono'))
);

-- Cada ficha física de la partida (la bolsa).
-- Ubicacion: bolsa -> mano (de IDUsuario) -> tablero (de IDUsuario, en IDRecinto/Posicion)
--            o retirada (salió de la partida, p. ej. la mano de quien abandona).
CREATE TABLE IF NOT EXISTS FICHAS_PARTIDA (
    IDPartida INT NOT NULL,
    NroFicha TINYINT NOT NULL,
    IDFicha INT NOT NULL,
    Ubicacion VARCHAR(10) NOT NULL DEFAULT 'bolsa',
    IDUsuario INT NULL,
    IDRecinto INT NULL,
    Posicion TINYINT NULL,
    RondaColocada TINYINT NULL,
    TurnoColocada TINYINT NULL,
    PRIMARY KEY (IDPartida, NroFicha),
    FOREIGN KEY (IDPartida) REFERENCES PARTIDAS(IDPartida) ON DELETE CASCADE,
    FOREIGN KEY (IDFicha) REFERENCES FICHAS(IDFicha),
    FOREIGN KEY (IDUsuario) REFERENCES JUGADORES(IDUsuario),
    FOREIGN KEY (IDRecinto) REFERENCES RECINTOS(IDRecinto),
    INDEX idx_fichas_partida_dueno (IDPartida, IDUsuario, Ubicacion),
    CONSTRAINT chk_fichas_partida_ubicacion CHECK (Ubicacion IN ('bolsa', 'mano', 'tablero', 'retirada'))
);

-- Historial de movimientos (qué jugador puso qué ficha y dónde).
-- Solo se muestra a los demás cuando el turno ya terminó.
CREATE TABLE IF NOT EXISTS JUGADAS (
    IDJugada INT AUTO_INCREMENT,
    IDPartida INT NOT NULL,
    NroFicha TINYINT NOT NULL,
    IDUsuario INT NOT NULL,
    IDRecinto INT NOT NULL,
    IDRecintoOrigen INT NULL,
    Tipo VARCHAR(10) NOT NULL DEFAULT 'colocar',
    Ronda TINYINT NOT NULL,
    Turno TINYINT NOT NULL,
    Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (IDJugada),
    FOREIGN KEY (IDPartida, NroFicha) REFERENCES FICHAS_PARTIDA(IDPartida, NroFicha) ON DELETE CASCADE,
    FOREIGN KEY (IDUsuario) REFERENCES JUGADORES(IDUsuario),
    FOREIGN KEY (IDRecinto) REFERENCES RECINTOS(IDRecinto),
    FOREIGN KEY (IDRecintoOrigen) REFERENCES RECINTOS(IDRecinto),
    INDEX idx_jugadas_partida (IDPartida, Ronda, Turno),
    CONSTRAINT chk_jugadas_tipo CHECK (Tipo IN ('colocar', 'mover', 'auto'))
);

-- Cola del modo competitivo (el MMR se lee de JUGADORES, no se duplica).
CREATE TABLE IF NOT EXISTS COLA_COMPETITIVA (
    IDUsuario INT NOT NULL,
    FechaIngreso DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UltimoLatido DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    IDPartidaAsignada INT NULL,
    PRIMARY KEY (IDUsuario),
    FOREIGN KEY (IDUsuario) REFERENCES JUGADORES(IDUsuario),
    FOREIGN KEY (IDPartidaAsignada) REFERENCES PARTIDAS(IDPartida) ON DELETE SET NULL
);

-- Partidas viejas: se conservan como finalizadas.
INSERT INTO PARTIDAS (IDPartida, Nombre, Modo, Estado, MaxJugadores, RondaActual, TurnoActual, Fecha, FechaInicio, FechaFin, BajaLogica)
    SELECT IDPartida, CONCAT('Partida ', IDPartida), 'normal', 'finalizada', 2, 2, 6, Fecha, Fecha, Fecha, BajaLogica FROM PARTIDAS_V2;
INSERT INTO CONFIG_PARTIDA (IDPartida) SELECT IDPartida FROM PARTIDAS_V2;
-- "Nombre:puntos" -> participación, solo si el nombre es de un jugador existente.
INSERT IGNORE INTO PARTICIPACIONES (IDPartida, IDUsuario, Asiento, PuntajeFinal, Posicion, PuntosOtorgados)
    SELECT p.IDPartida, j.IDUsuario, 0,
           CAST(SUBSTRING_INDEX(p.Puntajes, ':', -1) AS UNSIGNED),
           CAST(p.Posiciones AS UNSIGNED),
           CAST(SUBSTRING_INDEX(p.Puntajes, ':', -1) AS UNSIGNED)
    FROM PARTIDAS_V2 p
    INNER JOIN USUARIOS u ON u.NombreUsuario = SUBSTRING_INDEX(p.Puntajes, ':', 1)
    INNER JOIN JUGADORES j ON j.IDUsuario = u.IDUsuario
    WHERE p.Puntajes LIKE '%:%';
DROP TABLE PARTIDAS_V2;

-- ---------- Bots ----------
INSERT INTO USUARIOS (IDUsuario, Correo, Contra, NombreUsuario, Rol) VALUES
    (101,'klaus@bots.reboo.local','!','Bot Klaus','jugador'),
    (102,'greta@bots.reboo.local','!','Bot Greta','jugador'),
    (103,'dieter@bots.reboo.local','!','Bot Dieter','jugador'),
    (104,'ilse@bots.reboo.local','!','Bot Ilse','jugador');
INSERT INTO JUGADORES (IDUsuario, EsBot, Estado, Biografia) VALUES
    (101,1,'en_linea','Juega rápido y le encanta el Muro.'),
    (102,1,'en_linea','Coleccionista de espías.'),
    (103,1,'en_linea','Siempre busca el 7 de Militares.'),
    (104,1,'en_linea','Prudente: primero el político.');

SET FOREIGN_KEY_CHECKS = 1;

-- ---------- Procedimiento: no listar bots en el panel ----------
DROP PROCEDURE IF EXISTS listarJugadores;
DELIMITER //
CREATE PROCEDURE listarJugadores()
BEGIN
    SELECT u.IDUsuario, u.NombreUsuario, u.Correo, u.Rol, j.IDUsuario,
           j.FichasActuales, j.CantidadFichas, j.PntsPartida, j.PartidasJugadas, j.PartidasGanadas, j.BajaLogica
    FROM JUGADORES j
    INNER JOIN USUARIOS u ON j.IDUsuario = u.IDUsuario
    WHERE j.BajaLogica = 0 AND u.BajaLogica = 0 AND j.EsBot = 0;
END //
DELIMITER ;

-- ---------- Permisos del usuario de la app ----------
GRANT SELECT ON proyecto2026.CATEGORIAS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.FICHAS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.RECINTOS TO 'reboo_app'@'localhost';
GRANT SELECT ON proyecto2026.CARAS_DADO TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.skins_equipadas TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.PARTIDAS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.CONFIG_PARTIDA TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.PARTIDA_CARAS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.PARTICIPACIONES TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.FICHAS_PARTIDA TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.JUGADAS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.COLA_COMPETITIVA TO 'reboo_app'@'localhost';
FLUSH PRIVILEGES;
