DROP SCHEMA IF EXISTS proyecto2026;
CREATE SCHEMA IF NOT EXISTS proyecto2026;
USE proyecto2026;

CREATE TABLE IF NOT EXISTS USUARIOS (
    IDUsuario INT AUTO_INCREMENT,
    Correo VARCHAR(100) NOT NULL UNIQUE,
    Contra VARCHAR(255) NOT NULL,
    NombreUsuario VARCHAR(50) NOT NULL,
    Rol VARCHAR(15) NOT NULL,
    BajaLogica BOOLEAN NOT NULL DEFAULT 0,
    PRIMARY KEY (IDUsuario)
);

CREATE TABLE IF NOT EXISTS ADMINISTRADORES (
    IDUsuario INT NOT NULL,
    BajaLogica BOOLEAN NOT NULL DEFAULT 0,
    PRIMARY KEY (IDUsuario),
    FOREIGN KEY (IDUsuario) REFERENCES USUARIOS (IDUsuario)
);

CREATE TABLE IF NOT EXISTS JUGADORES (
    IDUsuario INT NOT NULL,
    FichasActuales INT NOT NULL DEFAULT 0,
    CantidadFichas INT NOT NULL DEFAULT 0,
	PntsPartida INT NOT NULL DEFAULT 0,
    PartidasJugadas INT NOT NULL DEFAULT 0,
	PartidasGanadas INT NOT NULL DEFAULT 0,
    BajaLogica BOOLEAN NOT NULL DEFAULT 0,
    PRIMARY KEY (IDUsuario),
    FOREIGN KEY (IDUsuario) REFERENCES USUARIOS (IDUsuario)
);

CREATE TABLE IF NOT EXISTS REPORTES (
    IDReportes INT AUTO_INCREMENT,
    Motivo VARCHAR(255) NOT NULL,
    BajaLogica BOOLEAN NOT NULL DEFAULT 0,
    PRIMARY KEY (IDReportes)
);

CREATE TABLE IF NOT EXISTS FICHAS (
    IDFicha INT AUTO_INCREMENT,
    FichaEspecie VARCHAR(50) NOT NULL,
    BajaLogica BOOLEAN NOT NULL DEFAULT 0,
    PRIMARY KEY (IDFicha)
);

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

CREATE TABLE IF NOT EXISTS TIENDA (
    IDTienda INT AUTO_INCREMENT,
    BajaLogica BOOLEAN NOT NULL DEFAULT 0,
    PRIMARY KEY (IDTienda)
);

CREATE TABLE IF NOT EXISTS PARTIDAS (
    IDPartida INT AUTO_INCREMENT,
    Puntajes VARCHAR(255),
    Posiciones VARCHAR(255),
    Fecha DATETIME NOT NULL,
    BajaLogica BOOLEAN NOT NULL DEFAULT 0,
    PRIMARY KEY (IDPartida)
);

CREATE TABLE IF NOT EXISTS RECINTOS (
    IDRecinto INT AUTO_INCREMENT,
    NombreRecinto VARCHAR(50) NOT NULL,
    BajaLogica BOOLEAN NOT NULL DEFAULT 0,
    PRIMARY KEY (IDRecinto)
);

CREATE TABLE IF NOT EXISTS JUGADAS (
    IDJugada INT NOT NULL,
    IDPartida INT NOT NULL,
    IDFicha INT NOT NULL,
    IDUsuario INT NOT NULL,
    IDRecinto INT NOT NULL,
    PRIMARY KEY (IDJugada, IDPartida),
    FOREIGN KEY (IDPartida) REFERENCES PARTIDAS(IDPartida),
    FOREIGN KEY (IDFicha) REFERENCES FICHAS(IDFicha),
    FOREIGN KEY (IDUsuario) REFERENCES JUGADORES(IDUsuario),
    FOREIGN KEY (IDRecinto) REFERENCES RECINTOS(IDRecinto)
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
    FOREIGN KEY (IDTienda) REFERENCES TIENDA(IDTienda)
);

CREATE TABLE IF NOT EXISTS acceso_jugador_tienda (
    IDUsuario INT NOT NULL,
    IDTienda INT NOT NULL,
    PRIMARY KEY (IDUsuario),
    FOREIGN KEY (IDUsuario) REFERENCES JUGADORES(IDUsuario),
    FOREIGN KEY (IDTienda) REFERENCES TIENDA(IDTienda)
);

CREATE TABLE IF NOT EXISTS tienda_skins (
    IDTienda INT NOT NULL,
    IDSkin INT NOT NULL,
    IDFicha INT NOT NULL,
    PRIMARY KEY (IDSkin, IDFicha),
    FOREIGN KEY (IDTienda) REFERENCES TIENDA(IDTienda),
    FOREIGN KEY (IDSkin, IDFicha) REFERENCES SKINS(IDSkin, IDFicha)
);

INSERT INTO USUARIOS (IDUsuario, Correo, Contra, NombreUsuario, Rol, BajaLogica) VALUES (1, "hola@gmail.com", 123, "AURA", "jugador", 0);
INSERT INTO USUARIOS (IDUsuario, Correo, Contra, NombreUsuario, Rol, BajaLogica) VALUES (2, "chau@gmail.com", 1234, "pepe", "administrador", 0);
INSERT INTO USUARIOS (IDUsuario, Correo, Contra, NombreUsuario, Rol, BajaLogica) VALUES (3, "TESTEO@gmail.com", 12345, "pepe", "TESTEO", 0);

INSERT INTO ADMINISTRADORES (IDUsuario, BajaLogica) VALUES (2, 0);
INSERT INTO JUGADORES (IDUsuario, FichasActuales, CantidadFichas, PntsPartida, PartidasJugadas, PartidasGanadas, BajaLogica) VALUES (1, 6, 12, 120, 20, 15, 0);
INSERT INTO JUGADORES (IDUsuario, FichasActuales, CantidadFichas, PntsPartida, PartidasJugadas, PartidasGanadas, BajaLogica) VALUES (3, 64, 142, 1320, 220, 135, 0);



INSERT INTO REPORTES (IDReportes, Motivo, BajaLogica) VALUES (1, "mala palabra", 0);
INSERT INTO REPORTES (IDReportes, Motivo, BajaLogica) VALUES (2, "bugs", 0);

INSERT INTO FICHAS (IDFicha, FichaEspecie, BajaLogica) VALUES (1, "obrero", 0);
INSERT INTO FICHAS (IDFicha, FichaEspecie, BajaLogica) VALUES (2, "politico", 0);

INSERT INTO SKINS (IDSkin, IDFicha, NombreSkin, Precio, URL, BajaLogica) VALUES (1, 1, "Espia azul", 150, "skin.png", 0);
INSERT INTO SKINS (IDSkin, IDFicha, NombreSkin, Precio, URL, BajaLogica) VALUES (2, 2, "Militar rojo", 120, "skin.png", 0);

INSERT INTO TIENDA (IDTienda, BajaLogica) VALUES (1, 0);
INSERT INTO TIENDA (IDTienda, BajaLogica) VALUES (2, 0);

INSERT INTO PARTIDAS (IDPartida, Puntajes, Posiciones, Fecha, BajaLogica) VALUES (1, "AURA:45", "1", "2026-06-26 14:30:00", 0);
INSERT INTO PARTIDAS (IDPartida, Puntajes, Posiciones, Fecha, BajaLogica) VALUES (2, "Pepe:55", "2", "2026-06-26 15:15:00", 0);


INSERT INTO RECINTOS (IDRecinto, NombreRecinto, BajaLogica) VALUES (1, "Sector Oeste", 0);
INSERT INTO RECINTOS (IDRecinto, NombreRecinto, BajaLogica) VALUES (2, "Sector Este", 0);

INSERT INTO JUGADAS (IDJugada, IDPartida, IDFicha, IDUsuario, IDRecinto) VALUES (1, 1, 1, 1, 1);
INSERT INTO JUGADAS (IDJugada, IDPartida, IDFicha, IDUsuario, IDRecinto) VALUES (2, 1, 2, 1, 2);

INSERT INTO administra_reportes (IDUsuario, IDReportes) VALUES (2, 1);
INSERT INTO administra_reportes (IDUsuario, IDReportes) VALUES (2, 2);

INSERT INTO acceso_admin_tienda (IDUsuario, IDTienda, Ofertas) VALUES (2, 1, 15);
INSERT INTO acceso_jugador_tienda (IDUsuario, IDTienda) VALUES (1, 1);


INSERT INTO tienda_skins (IDTienda, IDSkin, IDFicha) VALUES (1, 1, 1);
INSERT INTO tienda_skins (IDTienda, IDSkin, IDFicha) VALUES (1, 2, 2);

DELIMITER $$

CREATE PROCEDURE bajaAdmin()
		BEGIN
			DECLARE EXIT HANDLER FOR sqlexception
			BEGIN 
				rollback;
				SELECT "error" AS mensaje;
			END;
		SELECT * FROM ADMINISTRADORES;
		UPDATE ADMINISTRADORES SET BajaLogica = 1 WHERE IDUsuario = 1;
		END $$

DELIMITER ;
CALL bajaAdmin();


DELIMITER $$

CREATE PROCEDURE bajaJugador()
		BEGIN
			DECLARE EXIT HANDLER FOR sqlexception
			BEGIN 
				rollback;
				SELECT "error" AS mensaje;
			END;
		SELECT * FROM JUGADORES;
		UPDATE JUGADORES SET BajaLogica = 1 WHERE IDUsuario = 1;
		END $$

DELIMITER ;
CALL bajaJugador();

DELIMITER $$

	CREATE PROCEDURE eliminarJugador()
		BEGIN
			DECLARE EXIT HANDLER FOR sqlexception
			BEGIN 
				rollback;
				-- SELECT "error" AS mensaje
			END;
            DELETE FROM USUARIOS WHERE IDUsuario = 3;
			DELETE FROM JUGADORES WHERE IDUsuario = 3;
        
        
		END $$

DELIMITER ;
CALL eliminarJugador();

SELECT * FROM JUGADORES;

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
CALL modificarJugador();

DELIMITER //
CREATE PROCEDURE listarJugadores()
BEGIN
    SELECT 
        u.NombreUsuario,
        u.Correo,
        j.IDUsuario,
        j.FichasActuales,
        j.CantidadFichas,
        j.PntsPartida,
        j.PartidasJugadas,
        j.PartidasGanadas,
        j.BajaLogica
    FROM JUGADORES j
    INNER JOIN USUARIOS u ON j.IDUsuario = u.IDUsuario
    WHERE j.BajaLogica = 0 AND u.BajaLogica = 0;
END //
DELIMITER ;
CALL listarJugadores();


DELIMITER //
CREATE PROCEDURE listarAdmin()
BEGIN
    SELECT u.IDUsuario, u.Correo, u.Contra, u.NombreUsuario, a.BajaLogica
                FROM ADMINISTRADORES a
                INNER JOIN USUARIOS u ON a.IDUsuario = u.IDUsuario
                WHERE a.BajaLogica = 0 AND u.BajaLogica = 0;
END //
DELIMITER ;
CALL listarAdmin;

DELIMITER //
CREATE PROCEDURE buscarAdmin()
BEGIN
    SELECT u.IDUsuario, u.Correo, u.Contra, u.NombreUsuario, u.Rol, a.BajaLogica
                FROM ADMINISTRADORES a
                INNER JOIN USUARIOS u ON a.IDUsuario = u.IDUsuario
                WHERE a.IDUsuario = ? AND a.BajaLogica = 0 AND u.BajaLogica = 0
                LIMIT 1;
END //
DELIMITER ;
CALL buscarAdmin();

DELIMITER //
CREATE PROCEDURE buscarJugador()
BEGIN
    SELECT u.IDUsuario, u.Correo, u.Contra, u.NombreUsuario, u.Rol,
                       j.FichasActuales, j.CantidadFichas, j.PntsPartida, j.PartidasJugadas, j.PartidasGanadas, j.BajaLogica
                FROM JUGADORES j
                INNER JOIN USUARIOS u ON j.IDUsuario = u.IDUsuario
                WHERE j.IDUsuario = ? AND j.BajaLogica = 0 AND u.BajaLogica = 0
                LIMIT 1
END //
DELIMITER ;
CALL buscarJugador()