DROP SCHEMA IF EXISTS proyecto2026;
CREATE SCHEMA IF NOT EXISTS proyecto2026;
USE proyecto2026;

CREATE TABLE IF NOT EXISTS USUARIOS (
    IDUsuario INT AUTO_INCREMENT,
    Correo VARCHAR(100) NOT NULL UNIQUE,
    Contra VARCHAR(255) NOT NULL,
    NombreUsuario VARCHAR(30) NOT NULL UNIQUE,
    Rol VARCHAR(15) NOT NULL,
    BajaLogica BOOLEAN NOT NULL DEFAULT 0,
    -- 2FA: secreto TOTP cifrado con AES-256-GCM y códigos de recuperación hasheados
    SecretoTOTP VARCHAR(255) NULL,
    CodigosRecuperacion TEXT NULL,
    PRIMARY KEY (IDUsuario),
    -- Límites también en la base, por si fallan las validaciones de PHP
    CONSTRAINT chk_usuarios_rol CHECK (Rol IN ('jugador', 'administrador')),
    CONSTRAINT chk_usuarios_nombre CHECK (CHAR_LENGTH(NombreUsuario) >= 3)
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
    Biografia VARCHAR(300) NULL,
    Estado VARCHAR(20) NOT NULL DEFAULT 'ocupado',
    PRIMARY KEY (IDUsuario),
    FOREIGN KEY (IDUsuario) REFERENCES USUARIOS (IDUsuario),
    CONSTRAINT chk_jugadores_estado CHECK (Estado IN ('en_linea', 'ocupado', 'no_molestar', 'invisible')),
    CONSTRAINT chk_jugadores_positivos CHECK (FichasActuales >= 0 AND CantidadFichas >= 0 AND PntsPartida >= 0
        AND PartidasJugadas >= 0 AND PartidasGanadas >= 0)
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

-- Registro de eventos de seguridad (logins, bloqueos, acciones de admin, etc.).
-- No tiene FK a USUARIOS a propósito: el registro tiene que quedar aunque se borre la cuenta.
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

-- Usuarios de prueba: la contraseña es "Prueba2026!" hasheada con Argon2id y la pimienta de config.example.php.
-- Si su config.php usa otra pimienta, correr: php tools/regenerar_hashes_prueba.php
INSERT INTO USUARIOS (IDUsuario, Correo, Contra, NombreUsuario, Rol, BajaLogica) VALUES (1, "hola@gmail.com", '$argon2id$v=19$m=65536,t=4,p=1$cExTazQyTThDRWtybXYyUg$5ClKfPf4sUL24dKq5dCLHACn/8mzz6MubCLdEu1Ts7w', "AURA", "jugador", 0);
INSERT INTO USUARIOS (IDUsuario, Correo, Contra, NombreUsuario, Rol, BajaLogica) VALUES (2, "chau@gmail.com", '$argon2id$v=19$m=65536,t=4,p=1$cExTazQyTThDRWtybXYyUg$5ClKfPf4sUL24dKq5dCLHACn/8mzz6MubCLdEu1Ts7w', "pepe", "administrador", 0);
INSERT INTO USUARIOS (IDUsuario, Correo, Contra, NombreUsuario, Rol, BajaLogica) VALUES (3, "TESTEO@gmail.com", '$argon2id$v=19$m=65536,t=4,p=1$cExTazQyTThDRWtybXYyUg$5ClKfPf4sUL24dKq5dCLHACn/8mzz6MubCLdEu1Ts7w', "testeo", "jugador", 0);

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


INSERT INTO USUARIOS (Correo, Contra, NombreUsuario, Rol, BajaLogica)
SELECT 'juan_admin@re-boo.com', '$2y$12$frxrag24/a0.xaAqc9i4A.n6ftrIH6yj3PxpsLk7pHWISKScoyui2', 'juan_admin', 'administrador', 0
WHERE NOT EXISTS (SELECT 1 FROM USUARIOS WHERE NombreUsuario = 'juan_admin' OR Correo = 'juan_admin@re-boo.com');

INSERT INTO ADMINISTRADORES (IDUsuario, BajaLogica)
SELECT IDUsuario, 0 FROM USUARIOS
WHERE NombreUsuario = 'juan_admin' AND Rol = 'administrador'
    AND NOT EXISTS (SELECT 1 FROM ADMINISTRADORES a WHERE a.IDUsuario = USUARIOS.IDUsuario);


INSERT INTO tienda_skins (IDTienda, IDSkin, IDFicha) VALUES (1, 1, 1);
INSERT INTO tienda_skins (IDTienda, IDSkin, IDFicha) VALUES (1, 2, 2);

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
    WHERE j.BajaLogica = 0 AND u.BajaLogica = 0;
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

START TRANSACTION;

INSERT INTO USUARIOS
    (Correo, Contra, NombreUsuario, Rol, BajaLogica)
VALUES
    (
        'admin@reboo.com',
        '$argon2id$v=19$m=65536,t=4,p=1$ZHJtVWttYlkyZHRvTnRmeg$i6TxXSgG+3r08A3V55ApxJ2/gF/AOmGpJnTe3LFITEQ',
        'admin_prueba',
        'administrador',
        0
    );

SET @id_admin = LAST_INSERT_ID();

INSERT INTO ADMINISTRADORES
    (IDUsuario, BajaLogica)
VALUES
    (@id_admin, 0);

COMMIT;

-- =====================================================================
-- RETENCIÓN DE LOGS: el scheduler de MySQL borra los eventos viejos.
--   info/aviso: 180 días. critico (acciones de admin, bajas): 5 años.
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
-- USUARIO DE LA APLICACIÓN (principio del mínimo privilegio)
--   La app no puede crear, alterar ni borrar tablas.
--   AUDITORIA queda de solo agregar: la app inserta y lee, pero no puede
--   modificar ni borrar registros (nadie "limpia" los logs desde la app).
--   Cambiar la contraseña acá y en config.php (BD_CONTRA).
-- =====================================================================
DROP USER IF EXISTS 'reboo_app'@'localhost';
CREATE USER 'reboo_app'@'localhost' IDENTIFIED BY 'CambiarEstaClave_2026!';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.USUARIOS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.ADMINISTRADORES TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.JUGADORES TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.REPORTES TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.FICHAS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.SKINS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.TIENDA TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.PARTIDAS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.RECINTOS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.JUGADAS TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.administra_reportes TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.acceso_admin_tienda TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.acceso_jugador_tienda TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON proyecto2026.tienda_skins TO 'reboo_app'@'localhost';
GRANT SELECT, INSERT ON proyecto2026.AUDITORIA TO 'reboo_app'@'localhost';
GRANT EXECUTE ON proyecto2026.* TO 'reboo_app'@'localhost';
FLUSH PRIVILEGES;
