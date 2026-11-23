-- =====================================================================
--  MIGRACIÓN v15 -> v16 · Campaña, Acto I: el bot de Otto Weiss
--  Correr:  mysql -uroot -p < sql/migracion_v15_a_v16.sql
-- =====================================================================
USE proyecto2026;
SET NAMES utf8mb4;

-- Bot de Otto Weiss (Capítulo 1: la partida en su mesa de apuestas). Como los de los contactos, nunca aparece al azar.
INSERT IGNORE INTO USUARIOS (IDUsuario, Correo, Contra, NombreUsuario, Rol) VALUES (109, 'otto@bots.reboo.local', '!', 'Otto Weiss', 'jugador');
INSERT IGNORE INTO JUGADORES (IDUsuario, EsBot, Estado, Biografia) VALUES (109, 1, 'en_linea', 'Juega nervioso y apuesta lo que no tiene.');
