-- =====================================================================
--  DRAFT DER MAUER · Base de datos v10 (instalación completa)
--  v10 = v9 + torneos relámpago. v9 = v8 + amigos, bloqueos, grupos e invitaciones. v8 = v7 + misiones, XP y logros. v7 = v6 + reportes completos de jugadores (RF27/RF26, al final). v6 = v5 + apariencia del personaje. v5 = v4 + posiciones del mundo en memoria. v4 = v3 + skins reales + mundo abierto.
--  Cambios de v3 respecto a v2:
--    * Catálogos completos: 4 categorías, 9 fichas, 7 recintos, 6 caras.
--    * Partidas reales: salas, configuración, participantes, bolsa de
--      fichas por partida, historial de jugadas.
--    * Ranking y competitivo: MMR, puntos históricos, cola de búsqueda.
--    * Bots y skins equipadas.
--  Todo en 3FN: los datos repetidos se movieron a su propia tabla
--  (por ejemplo, los puntajes por jugador ya no van como texto en PARTIDAS).
-- =====================================================================
DROP SCHEMA IF EXISTS proyecto2026;
CREATE SCHEMA IF NOT EXISTS proyecto2026 DEFAULT CHARACTER SET utf8mb4;
USE proyecto2026;
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
--  USUARIOS Y ROLES
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS USUARIOS (
    IDUsuario INT AUTO_INCREMENT,
    Correo VARCHAR(100) NOT NULL UNIQUE,
    Contra VARCHAR(255) NOT NULL,
    NombreUsuario VARCHAR(30) NOT NULL UNIQUE,
    Rol VARCHAR(15) NOT NULL,
    BajaLogica BOOLEAN NOT NULL DEFAULT 0,
    PRIMARY KEY (IDUsuario),
    CONSTRAINT chk_usuarios_rol CHECK (Rol IN ('jugador', 'administrador')),
    CONSTRAINT chk_usuarios_nombre CHECK (CHAR_LENGTH(NombreUsuario) >= 3)
);

CREATE TABLE IF NOT EXISTS ADMINISTRADORES (
    IDUsuario INT NOT NULL,
    BajaLogica BOOLEAN NOT NULL DEFAULT 0,
    PRIMARY KEY (IDUsuario),
    FOREIGN KEY (IDUsuario) REFERENCES USUARIOS (IDUsuario)
);

-- PntsPartida       = saldo de la tienda (sube al jugar, baja al comprar).
-- PuntosHistoricos  = todo lo ganado en la vida; nunca baja (para el ranking).
-- MMR               = nivel del modo competitivo (Elo multijugador).
CREATE TABLE IF NOT EXISTS JUGADORES (
    IDUsuario INT NOT NULL,
    FichasActuales INT NOT NULL DEFAULT 0,
    CantidadFichas INT NOT NULL DEFAULT 0,
    PntsPartida INT NOT NULL DEFAULT 0,
    PuntosHistoricos INT NOT NULL DEFAULT 0,
    XP INT NOT NULL DEFAULT 0,
    PartidasJugadas INT NOT NULL DEFAULT 0,
    PartidasGanadas INT NOT NULL DEFAULT 0,
    MMR INT NOT NULL DEFAULT 1000,
    PartidasCompetitivas INT NOT NULL DEFAULT 0,
    EsBot BOOLEAN NOT NULL DEFAULT 0,
    TutorialVisto BOOLEAN NOT NULL DEFAULT 0,
    BajaLogica BOOLEAN NOT NULL DEFAULT 0,
    Biografia VARCHAR(300) NULL,
    Estado VARCHAR(20) NOT NULL DEFAULT 'ocupado',
    PRIMARY KEY (IDUsuario),
    FOREIGN KEY (IDUsuario) REFERENCES USUARIOS (IDUsuario),
    INDEX idx_jugadores_mmr (MMR),
    INDEX idx_jugadores_historicos (PuntosHistoricos),
    CONSTRAINT chk_jugadores_estado CHECK (Estado IN ('en_linea', 'ocupado', 'no_molestar', 'invisible')),
    CONSTRAINT chk_jugadores_positivos CHECK (FichasActuales >= 0 AND CantidadFichas >= 0 AND PntsPartida >= 0
        AND PuntosHistoricos >= 0 AND PartidasJugadas >= 0 AND PartidasGanadas >= 0
        AND MMR >= 0 AND PartidasCompetitivas >= 0 AND XP >= 0)
);

CREATE TABLE IF NOT EXISTS REPORTES (
    IDReportes INT AUTO_INCREMENT,
    Motivo VARCHAR(255) NOT NULL,
    BajaLogica BOOLEAN NOT NULL DEFAULT 0,
    PRIMARY KEY (IDReportes)
);

-- ---------------------------------------------------------------------
--  CATÁLOGOS DEL JUEGO (los códigos son los que usa el motor en PHP)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS CATEGORIAS (
    IDCategoria INT NOT NULL,
    Codigo VARCHAR(20) NOT NULL UNIQUE,
    Nombre VARCHAR(30) NOT NULL,
    PRIMARY KEY (IDCategoria)
);

CREATE TABLE IF NOT EXISTS FICHAS (
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

CREATE TABLE IF NOT EXISTS RECINTOS (
    IDRecinto INT AUTO_INCREMENT,
    Codigo VARCHAR(20) NOT NULL UNIQUE,
    NombreRecinto VARCHAR(50) NOT NULL,
    Capacidad INT NOT NULL,
    Lado VARCHAR(10) NOT NULL,
    IDCategoriaExclusiva INT NULL, -- NULL = acepta cualquier categoría
    BajaLogica BOOLEAN NOT NULL DEFAULT 0,
    PRIMARY KEY (IDRecinto),
    FOREIGN KEY (IDCategoriaExclusiva) REFERENCES CATEGORIAS (IDCategoria),
    CONSTRAINT chk_recintos_lado CHECK (Lado IN ('izquierdo', 'derecho', 'centro')),
    CONSTRAINT chk_recintos_capacidad CHECK (Capacidad > 0)
);

CREATE TABLE IF NOT EXISTS CARAS_DADO (
    IDCara INT NOT NULL,
    Codigo VARCHAR(20) NOT NULL UNIQUE,
    Nombre VARCHAR(50) NOT NULL,
    Imagen VARCHAR(100) NOT NULL,
    PRIMARY KEY (IDCara)
);

-- ---------------------------------------------------------------------
--  TIENDA Y SKINS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS SKINS (
    IDSkin INT NOT NULL,
    IDFicha INT NOT NULL,
    NombreSkin VARCHAR(50) NOT NULL,
    Precio INT NOT NULL DEFAULT 0,
    URL VARCHAR(255),
    BajaLogica BOOLEAN NOT NULL DEFAULT 0,
    PRIMARY KEY (IDSkin, IDFicha),
    FOREIGN KEY (IDFicha) REFERENCES FICHAS(IDFicha)
);

CREATE TABLE IF NOT EXISTS tiendas (
    IDTienda INT AUTO_INCREMENT,
    BajaLogica BOOLEAN NOT NULL DEFAULT 0,
    PRIMARY KEY (IDTienda)
);

CREATE TABLE IF NOT EXISTS administra_reportes (
    IDUsuario INT NOT NULL,
    IDReportes INT NOT NULL,
    PRIMARY KEY (IDUsuario, IDReportes),
    FOREIGN KEY (IDUsuario) REFERENCES ADMINISTRADORES(IDUsuario),
    FOREIGN KEY (IDReportes) REFERENCES REPORTES(IDReportes)
);

CREATE TABLE IF NOT EXISTS acceso_admin_tienda (
    IDUsuario INT NOT NULL,
    IDTienda INT NOT NULL,
    Ofertas INT NOT NULL,
    PRIMARY KEY (IDUsuario),
    FOREIGN KEY (IDUsuario) REFERENCES ADMINISTRADORES(IDUsuario),
    FOREIGN KEY (IDTienda) REFERENCES tiendas(IDTienda)
);

CREATE TABLE IF NOT EXISTS acceso_jugador_tienda (
    IDUsuario INT NOT NULL,
    IDTienda INT NOT NULL,
    PRIMARY KEY (IDUsuario),
    FOREIGN KEY (IDUsuario) REFERENCES JUGADORES(IDUsuario),
    FOREIGN KEY (IDTienda) REFERENCES tiendas(IDTienda)
);

CREATE TABLE IF NOT EXISTS tienda_skins (
    IDTienda INT NOT NULL,
    IDSkin INT NOT NULL,
    IDFicha INT NOT NULL,
    PRIMARY KEY (IDSkin, IDFicha),
    FOREIGN KEY (IDTienda) REFERENCES tiendas(IDTienda),
    FOREIGN KEY (IDSkin, IDFicha) REFERENCES SKINS(IDSkin, IDFicha)
);

CREATE TABLE IF NOT EXISTS jugador_skins (
    IDUsuario INT NOT NULL,
    IDSkin INT NOT NULL,
    IDFicha INT NOT NULL,
    PRIMARY KEY (IDUsuario, IDSkin, IDFicha),
    FOREIGN KEY (IDUsuario) REFERENCES JUGADORES(IDUsuario),
    FOREIGN KEY (IDSkin, IDFicha) REFERENCES SKINS(IDSkin, IDFicha)
);

-- Una skin equipada por tipo de ficha. Solo se puede equipar una que se posee.
CREATE TABLE IF NOT EXISTS skins_equipadas (
    IDUsuario INT NOT NULL,
    IDFicha INT NOT NULL,
    IDSkin INT NOT NULL,
    PRIMARY KEY (IDUsuario, IDFicha),
    FOREIGN KEY (IDUsuario, IDSkin, IDFicha) REFERENCES jugador_skins(IDUsuario, IDSkin, IDFicha) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
--  PARTIDAS
-- ---------------------------------------------------------------------
-- Estado:   esperando -> en_curso -> finalizada   (o cancelada)
-- Fase:     dado (falta tirar) -> colocar (todos colocan y confirman)
-- Version:  sube con cada cambio; el navegador la usa para saber si
--           tiene que redibujar (polling liviano).
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

-- ---------------------------------------------------------------------
--  AUDITORÍA
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS AUDITORIA (
    IDEvento BIGINT AUTO_INCREMENT,
    Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Tipo VARCHAR(40) NOT NULL,
    Nivel VARCHAR(10) NOT NULL DEFAULT 'info',
    IDUsuario INT NULL,
    Identificador VARCHAR(100) NULL,
    IP VARCHAR(45) NOT NULL,
    Detalle VARCHAR(255) NULL,
    PRIMARY KEY (IDEvento),
    INDEX idx_auditoria_tipo_fecha (Tipo, Fecha),
    INDEX idx_auditoria_ip_fecha (IP, Fecha),
    INDEX idx_auditoria_identificador (Identificador, Fecha),
    CONSTRAINT chk_auditoria_nivel CHECK (Nivel IN ('info', 'aviso', 'critico'))
);

-- =====================================================================
--  DATOS DE CATÁLOGO
-- =====================================================================
INSERT INTO CATEGORIAS (IDCategoria, Codigo, Nombre) VALUES
    (1, 'militares', 'Militares'),
    (2, 'obreros', 'Obreros'),
    (3, 'politicos', 'Políticos'),
    (4, 'espias', 'Espías');

-- Composición total: 60 fichas (manual, sección 2.4).
INSERT INTO FICHAS (IDFicha, Codigo, FichaEspecie, IDCategoria, CantidadTotal, Imagen) VALUES
    (1, 'general',        'General',         1, 6,  'GeneralDraftDerMauer.png'),
    (2, 'oficial_militar','Oficial Militar', 1, 6,  'OficialDraftDerMauer.png'),
    (3, 'tropa',          'Tropa',           1, 6,  'TropaDraftDerMauer.png'),
    (4, 'cabo',           'Cabo',            2, 6,  'ACaboDraftDerMauer.png'),
    (5, 'oficial_obrera', 'Oficial Obrera',  2, 6,  'OficialMujerDraftDerMauer.png'),
    (6, 'peon',           'Peón',            2, 6,  'PeonDraftDerMauer.png'),
    (7, 'politico',       'Político',        3, 14, 'PoliticoDraftDerMauer.png'),
    (8, 'markus',         'Markus',          4, 5,  'MarkusEspiaDraftDerMauer.png'),
    (9, 'ursula',         'Ursula',          4, 5,  'UrsulaEspiaDraftDerMauer.png');

INSERT INTO RECINTOS (IDRecinto, Codigo, NombreRecinto, Capacidad, Lado, IDCategoriaExclusiva) VALUES
    (1, 'oeste',     'Oeste',        6,  'izquierdo', NULL),
    (2, 'fabrica',   'Fábrica',      3,  'izquierdo', 2),
    (3, 'espias',    'Espías',       2,  'izquierdo', 4),
    (4, 'politicos', 'Políticos',    1,  'derecho',   3),
    (5, 'este',      'Este',         6,  'derecho',   NULL),
    (6, 'militares', 'Militares',    3,  'derecho',   1),
    (7, 'muro',      'Muro Central', 12, 'centro',    NULL);

INSERT INTO CARAS_DADO (IDCara, Codigo, Nombre, Imagen) VALUES
    (1, 'lado_izquierdo',  'Bloqueo del lado izquierdo', 'NoIzquierdoDadoDraftDerMauer.png'),
    (2, 'lado_derecho',    'Bloqueo del lado derecho',   'NoDerechoDadoDraftDerMauer.png'),
    (3, 'solo_militares',  'Solo Militares',             'SoloRecintoMilitaresDadoDraftDerMauer.png'),
    (4, 'bloquea_fabrica', 'Bloqueo de Fábrica',         'NoFabricaDadoDraftDerMauer.png'),
    (5, 'cambiar_ficha',   'Cambiar ficha',              'ReacomodarFichaDadoDraftDerMauer.png'),
    (6, 'solo_espias',     'Solo Espías',                'SoloEspiasDadoDraftDerMauer.png');

-- =====================================================================
--  DATOS DE PRUEBA
-- =====================================================================
-- Contraseña de prueba de todos: "Prueba2026!". Los hashes dependen de la
-- pimienta de config.php: después de importar corré
--     php herramientas/instalar.php
-- que crea config.php (si falta) y regenera estos hashes.
INSERT INTO USUARIOS (IDUsuario, Correo, Contra, NombreUsuario, Rol) VALUES
    (1, 'hola@gmail.com',        'pendiente', 'AURA',         'jugador'),
    (2, 'chau@gmail.com',        'pendiente', 'pepe',         'administrador'),
    (3, 'TESTEO@gmail.com',      'pendiente', 'testeo',       'jugador'),
    (4, 'juan_admin@re-boo.com', 'pendiente', 'juan_admin',   'administrador'),
    (5, 'admin@reboo.com',       'pendiente', 'admin_prueba', 'administrador');

INSERT INTO ADMINISTRADORES (IDUsuario) VALUES (2), (4), (5);

INSERT INTO JUGADORES (IDUsuario, FichasActuales, CantidadFichas, PntsPartida, PuntosHistoricos, PartidasJugadas, PartidasGanadas, MMR) VALUES
    (1, 6, 12, 120, 120, 20, 15, 1040),
    (3, 64, 142, 1320, 1320, 220, 135, 1180);

-- Bots: cuentas sin contraseña válida (el hash '!' nunca verifica).
INSERT INTO USUARIOS (IDUsuario, Correo, Contra, NombreUsuario, Rol) VALUES
    (101, 'klaus@bots.reboo.local', '!', 'Bot Klaus', 'jugador'),
    (102, 'greta@bots.reboo.local', '!', 'Bot Greta', 'jugador'),
    (103, 'dieter@bots.reboo.local', '!', 'Bot Dieter', 'jugador'),
    (104, 'ilse@bots.reboo.local', '!', 'Bot Ilse', 'jugador');
INSERT INTO JUGADORES (IDUsuario, EsBot, Estado, Biografia) VALUES
    (101, 1, 'en_linea', 'Juega rápido y le encanta el Muro.'),
    (102, 1, 'en_linea', 'Coleccionista de espías.'),
    (103, 1, 'en_linea', 'Siempre busca el 7 de Militares.'),
    (104, 1, 'en_linea', 'Prudente: primero el político.');
ALTER TABLE USUARIOS AUTO_INCREMENT = 200;

INSERT INTO REPORTES (IDReportes, Motivo) VALUES (1, 'mala palabra'), (2, 'bugs');
INSERT INTO administra_reportes (IDUsuario, IDReportes) VALUES (2, 1), (2, 2);

INSERT INTO tiendas (IDTienda) VALUES (1), (2);
INSERT INTO acceso_admin_tienda (IDUsuario, IDTienda, Ofertas) VALUES (2, 1, 15);
INSERT INTO acceso_jugador_tienda (IDUsuario, IDTienda) VALUES (1, 1);

INSERT INTO SKINS (IDSkin, IDFicha, NombreSkin, Precio, URL) VALUES
    (1, 8, 'Espía azul', 150, 'skin.png'),
    (2, 1, 'Militar rojo', 120, 'skin.png');
INSERT INTO tienda_skins (IDTienda, IDSkin, IDFicha) VALUES (1, 1, 8), (1, 2, 1);

-- Dos partidas viejas ya terminadas, para que el historial no esté vacío.
INSERT INTO PARTIDAS (IDPartida, Nombre, Tipo, Modo, Estado, MaxJugadores, IDCreador, RondaActual, TurnoActual, Fecha, FechaInicio, FechaFin) VALUES
    (1, 'Partida de prueba 1', 'publica', 'normal', 'finalizada', 2, 1, 2, 6, '2026-06-26 14:30:00', '2026-06-26 14:30:00', '2026-06-26 14:45:00'),
    (2, 'Partida de prueba 2', 'publica', 'normal', 'finalizada', 2, 3, 2, 6, '2026-06-26 15:15:00', '2026-06-26 15:15:00', '2026-06-26 15:31:00');
INSERT INTO CONFIG_PARTIDA (IDPartida) VALUES (1), (2);
INSERT INTO PARTICIPACIONES (IDPartida, IDUsuario, Asiento, PuntajeFinal, Posicion, PuntosOtorgados) VALUES
    (1, 1, 0, 45, 1, 45), (1, 3, 1, 38, 2, 38),
    (2, 3, 0, 55, 1, 55), (2, 1, 1, 41, 2, 41);

DELIMITER $$

CREATE PROCEDURE bajaAdmin(IN p_IDUsuario INT)
		BEGIN
			DECLARE EXIT HANDLER FOR sqlexception
			BEGIN 
				rollback;
				SELECT "error" AS mensaje;
			END;
		SELECT * FROM ADMINISTRADORES;
		UPDATE ADMINISTRADORES SET BajaLogica = 1 WHERE IDUsuario = p_IDUsuario;
		END $$

DELIMITER ;


DELIMITER $$

CREATE PROCEDURE bajaJugador(IN p_IDUsuario INT)
		BEGIN
			DECLARE EXIT HANDLER FOR sqlexception
			BEGIN 
				rollback;
				SELECT "error" AS mensaje;
			END;
		SELECT * FROM JUGADORES;
		UPDATE JUGADORES SET BajaLogica = 1 WHERE IDUsuario = p_IDUsuario;
		END $$

DELIMITER ;

DELIMITER $$

	CREATE PROCEDURE eliminarJugador(IN p_IDUsuario INT)
		BEGIN
			DECLARE EXIT HANDLER FOR sqlexception
			BEGIN 
				rollback;
				-- SELECT "error" AS mensaje
			END;
			DELETE FROM JUGADORES WHERE IDUsuario = p_IDUsuario;
            DELETE FROM USUARIOS WHERE IDUsuario = p_IDUsuario AND Rol = 'jugador';
        
		END $$

DELIMITER ;


DELIMITER //
CREATE PROCEDURE modificarJugador(
    IN p_IDUsuario INT,
    IN p_FichasActuales INT,
    IN p_CantidadFichas INT,
    IN p_PntsPartida INT,
    IN p_PartidasJugadas INT,
    IN p_PartidasGanadas INT,
    IN p_BajaLogica BOOLEAN
)
BEGIN
    UPDATE JUGADORES 
    SET FichasActuales = p_FichasActuales,
        CantidadFichas = p_CantidadFichas,
        PntsPartida = p_PntsPartida,
        PartidasJugadas = p_PartidasJugadas,
        PartidasGanadas = p_PartidasGanadas,
        BajaLogica = p_BajaLogica
    WHERE IDUsuario = p_IDUsuario;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE listarJugadores()
BEGIN
    SELECT 
		u.IDUsuario,
        u.NombreUsuario,
        u.Correo,
		u.Rol,
        j.IDUsuario,
        j.FichasActuales,
        j.CantidadFichas,
        j.PntsPartida,
        j.PartidasJugadas,
        j.PartidasGanadas,
        j.BajaLogica
    FROM JUGADORES j
    INNER JOIN USUARIOS u ON j.IDUsuario = u.IDUsuario
    WHERE j.BajaLogica = 0 AND u.BajaLogica = 0 AND j.EsBot = 0;
END //
DELIMITER ;


DELIMITER //
CREATE PROCEDURE listarAdmin()
BEGIN
    SELECT 
		u.IDUsuario, 
		u.Correo, 
		u.Contra, 
		u.NombreUsuario, 
		a.BajaLogica
      FROM ADMINISTRADORES a
      INNER JOIN USUARIOS u ON a.IDUsuario = u.IDUsuario
      WHERE a.BajaLogica = 0 AND u.BajaLogica = 0;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE buscarAdmin(IN p_IDUsuario INT)
BEGIN
    SELECT 
		u.IDUsuario, 
		u.Correo, 
		u.Contra, 
		u.NombreUsuario, 
		u.Rol, 
		a.BajaLogica
    FROM ADMINISTRADORES a
    INNER JOIN USUARIOS u ON a.IDUsuario = u.IDUsuario
    WHERE a.IDUsuario = p_IDUsuario AND a.BajaLogica = 0 AND u.BajaLogica = 0
    LIMIT 1;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE buscarJugador(IN p_IDUsuario INT)
BEGIN
    SELECT 
		u.IDUsuario, 
		u.Correo, 
		u.Contra, 
		u.NombreUsuario, 
		u.Rol,
    	j.FichasActuales, 
		j.CantidadFichas, 
		j.PntsPartida, 
		j.PartidasJugadas, 
		j.PartidasGanadas, 
		j.BajaLogica
	FROM JUGADORES j
    INNER JOIN USUARIOS u ON j.IDUsuario = u.IDUsuario
    WHERE j.IDUsuario = p_IDUsuario AND j.BajaLogica = 0 AND u.BajaLogica = 0
    LIMIT 1;
END //
DELIMITER ;


-- =====================================================================
--  RETENCIÓN DE LOGS: el scheduler borra los eventos viejos.
--   info/aviso: 180 días. critico: 5 años.
--   En MariaDB hay que activarlo: SET GLOBAL event_scheduler = ON;
-- =====================================================================
DROP EVENT IF EXISTS purgarAuditoria;
CREATE EVENT purgarAuditoria
    ON SCHEDULE EVERY 1 DAY
    DO
        DELETE FROM AUDITORIA
        WHERE (Nivel <> 'critico' AND Fecha < NOW() - INTERVAL 180 DAY)
           OR (Nivel = 'critico' AND Fecha < NOW() - INTERVAL 5 YEAR);

-- =====================================================================
--  USUARIO DE LA APLICACIÓN (principio del mínimo privilegio)
--   No puede crear, alterar ni borrar tablas. AUDITORIA es de solo agregar.
--   Los catálogos (fichas, recintos, caras, categorías) son de solo lectura.
--   Cambiar la contraseña acá y en config.php (BD_CONTRA).
-- =====================================================================
DROP USER IF EXISTS 'reboo_app'@'localhost';
CREATE USER 'reboo_app'@'localhost' IDENTIFIED BY 'CambiarEstaClave_2026!';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.USUARIOS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.ADMINISTRADORES TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.JUGADORES TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.REPORTES TO 'reboo_app'@'localhost';
GRANT SELECT ON proyecto2026.CATEGORIAS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.FICHAS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.RECINTOS TO 'reboo_app'@'localhost';
GRANT SELECT ON proyecto2026.CARAS_DADO TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.SKINS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.tiendas TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.administra_reportes TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.acceso_admin_tienda TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.acceso_jugador_tienda TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.tienda_skins TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT ON proyecto2026.jugador_skins TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.skins_equipadas TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.PARTIDAS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.CONFIG_PARTIDA TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.PARTIDA_CARAS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.PARTICIPACIONES TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.FICHAS_PARTIDA TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.JUGADAS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.COLA_COMPETITIVA TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT ON proyecto2026.AUDITORIA TO 'reboo_app'@'localhost';
GRANT EXECUTE ON proyecto2026.* TO 'reboo_app'@'localhost';
FLUSH PRIVILEGES;

-- =====================================================================
--  v4: SKINS Y MUNDO ABIERTO (idéntico a sql/migracion_v3_a_v4.sql)
-- =====================================================================


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

-- Dónde está parado cada jugador. Es un CACHÉ EN MEMORIA (ENGINE=MEMORY):
--   * se mueve varias veces por segundo y no hace falta que sobreviva a un reinicio de MySQL
--     (al volver a entrar al mapa se recrea sola);
--   * guarda una copia de nombre, bando y MMR para listar a los jugadores sin hacer joins
--     (la fuente de verdad sigue siendo USUARIOS y JUGADORES; se refresca cada pocos segundos).
-- Las tablas MEMORY no admiten claves foráneas.
CREATE TABLE IF NOT EXISTS POSICIONES_MUNDO (
    IDUsuario INT NOT NULL,
    Nombre VARCHAR(30) NOT NULL,
    Bando VARCHAR(5) NULL,
    MMR INT NOT NULL DEFAULT 1000,
    Visible TINYINT NOT NULL DEFAULT 1,
    Apariencia TINYINT NOT NULL DEFAULT 0,
    XP INT NOT NULL DEFAULT 0,
    Gesto TINYINT NOT NULL DEFAULT 0,
    GestoHasta DATETIME(3) NULL,
    X SMALLINT NOT NULL,
    Y SMALLINT NOT NULL,
    Direccion TINYINT NOT NULL DEFAULT 0,
    UltimaActividad DATETIME(3) NOT NULL,
    PRIMARY KEY (IDUsuario),
    INDEX idx_posiciones_actividad (UltimaActividad) USING BTREE
) ENGINE=MEMORY;

-- Apariencia del personaje en el mundo abierto (Fase 1).
-- El índice del sprite es Piel * 12 + Pelo * 3 + Gorro (0 a 35); se calcula, no se guarda.
CREATE TABLE IF NOT EXISTS APARIENCIA_JUGADOR (
    IDUsuario INT NOT NULL,
    Piel TINYINT NOT NULL DEFAULT 0,
    Pelo TINYINT NOT NULL DEFAULT 0,
    Gorro TINYINT NOT NULL DEFAULT 0,
    PRIMARY KEY (IDUsuario),
    FOREIGN KEY (IDUsuario) REFERENCES JUGADORES (IDUsuario),
    CONSTRAINT chk_apariencia_rangos CHECK (Piel BETWEEN 0 AND 2 AND Pelo BETWEEN 0 AND 3 AND Gorro BETWEEN 0 AND 2)
);

-- Misiones y logros del mundo abierto (Fase 2).
-- Las misiones y logros se DEFINEN en datos/mundo/misiones.json; acá solo se guarda el progreso.
-- El nivel no se guarda: sale de JUGADORES.XP (se llega al nivel n+1 con 50·n·(n+1) XP).
CREATE TABLE IF NOT EXISTS MISIONES_JUGADOR (
    IDUsuario INT NOT NULL,
    IDMision VARCHAR(40) NOT NULL,
    Estado VARCHAR(12) NOT NULL DEFAULT 'activa',
    Paso TINYINT NOT NULL DEFAULT 0,
    FechaInicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaPaso DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaFin DATETIME NULL,
    PRIMARY KEY (IDUsuario, IDMision),
    FOREIGN KEY (IDUsuario) REFERENCES JUGADORES (IDUsuario),
    CONSTRAINT chk_misiones_estado CHECK (Estado IN ('activa', 'completada')),
    CONSTRAINT chk_misiones_paso CHECK (Paso >= 0)
);

CREATE TABLE IF NOT EXISTS LOGROS_JUGADOR (
    IDUsuario INT NOT NULL,
    IDLogro VARCHAR(40) NOT NULL,
    Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (IDUsuario, IDLogro),
    FOREIGN KEY (IDUsuario) REFERENCES JUGADORES (IDUsuario)
);

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
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.APARIENCIA_JUGADOR TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE ON proyecto2026.MISIONES_JUGADOR TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT ON proyecto2026.LOGROS_JUGADOR TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.AMISTADES TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, DELETE ON proyecto2026.BLOQUEOS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.GRUPOS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, DELETE ON proyecto2026.GRUPO_MIEMBROS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT ON proyecto2026.MENSAJES_GRUPO TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, DELETE ON proyecto2026.INVITACIONES TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE ON proyecto2026.TORNEOS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.TORNEO_JUGADORES TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE ON proyecto2026.TORNEO_LLAVES TO 'reboo_app'@'localhost';
GRANT INSERT ON proyecto2026.REPORTES TO 'reboo_app'@'localhost';
FLUSH PRIVILEGES;

-- =====================================================================
--  v4.1 · Reportes de jugadores (RF27) y su moderación (RF26)
--  REPORTES pasa de ser solo un texto a un reporte completo: quién lo
--  manda, de qué tipo es (bug, jugador o mensaje del chat), a quién
--  denuncia, en qué partida pasó y en qué estado está. Quién lo resolvió
--  se guarda en administra_reportes.
--  (Para una base con datos: sql/migracion_v4_reportes.sql hace lo mismo.)
-- =====================================================================
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

-- Los dos reportes de ejemplo ya estaban atendidos por pepe (administra_reportes).
UPDATE REPORTES SET Tipo = 'jugador', Estado = 'resuelto', FechaResolucion = Fecha WHERE IDReportes = 1;
UPDATE REPORTES SET Tipo = 'bug', Estado = 'resuelto', FechaResolucion = Fecha WHERE IDReportes = 2;

GRANT SELECT, INSERT, UPDATE ON proyecto2026.REPORTES TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT ON proyecto2026.administra_reportes TO 'reboo_app'@'localhost';
FLUSH PRIVILEGES;
