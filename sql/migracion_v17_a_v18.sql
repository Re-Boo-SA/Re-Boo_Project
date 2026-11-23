-- =====================================================================
--  MIGRACIÓN v17 -> v18 · Campaña, Acto VI: Konrad Weiss (bot 110) para la partida del Capítulo 14
--  Correr:  mysql -uroot -p < sql/migracion_v17_a_v18.sql
-- =====================================================================
USE proyecto2026;
SET NAMES utf8mb4;

INSERT IGNORE INTO USUARIOS (IDUsuario, Correo, Contra, NombreUsuario, Rol) VALUES (110, 'konrad@bots.reboo.local', '!', 'Konrad Weiss', 'jugador');
INSERT IGNORE INTO JUGADORES (IDUsuario, EsBot, Estado, Biografia) VALUES (110, 1, 'en_linea', 'Juega sin apuro. Siempre parece saber cómo termina.');
