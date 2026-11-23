-- =====================================================================
--  ¿EN QUE VERSION ESTA MI BASE DE DATOS?
--  Correr desde cmd (no PowerShell):
--      mysql.exe -u root -p -N < sql\ver_version.sql
--  Solo lee, no cambia nada. Dice que migraciones faltan (ver docs\actualizar_version.md).
-- =====================================================================
SELECT CASE
  WHEN EXISTS (SELECT 1 FROM proyecto2026.USUARIOS WHERE IDUsuario = 110) THEN 'v18 (ya esta al dia)'
  WHEN EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA='proyecto2026' AND TABLE_NAME='PERFIL_PERSONAL') THEN 'v17 (falta migracion_v17_a_v18)'
  WHEN EXISTS (SELECT 1 FROM proyecto2026.USUARIOS WHERE IDUsuario = 109) THEN 'v16 (falta v16_a_v17 y v17_a_v18)'
  WHEN EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA='proyecto2026' AND TABLE_NAME='CAMPANA_COMBATES') THEN 'v15 (falta v15_a_v16 hasta v17_a_v18)'
  WHEN EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA='proyecto2026' AND TABLE_NAME='CAMPANA_PROGRESO') THEN 'v14 (falta v14_a_v15 hasta v17_a_v18)'
  WHEN EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA='proyecto2026' AND TABLE_NAME='MISION_PARTIDAS') THEN 'v13 (falta v13_a_v14 hasta v17_a_v18)'
  WHEN EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA='proyecto2026' AND TABLE_NAME='SUCESOS_RECOMPENSAS') THEN 'v12 (falta v12_a_v13 hasta v17_a_v18)'
  WHEN EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA='proyecto2026' AND TABLE_NAME='RELACION_NPC') THEN 'v11 (falta v11_a_v12 hasta v17_a_v18)'
  WHEN EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA='proyecto2026' AND TABLE_NAME='TORNEOS') THEN 'v10 (falta v10_a_v11 hasta v17_a_v18)'
  WHEN EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA='proyecto2026' AND TABLE_NAME='AMISTADES') THEN 'v9 (falta v9_a_v10 hasta v17_a_v18)'
  WHEN EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='proyecto2026' AND TABLE_NAME='JUGADORES' AND COLUMN_NAME='XP') THEN 'v8 (falta v8_a_v9 hasta v17_a_v18)'
  WHEN EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='proyecto2026' AND TABLE_NAME='REPORTES' AND COLUMN_NAME='Tipo')
       AND EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA='proyecto2026' AND TABLE_NAME='APARIENCIA_JUGADOR') THEN 'v7 (falta v7_a_v8 hasta v17_a_v18)'
  WHEN EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA='proyecto2026' AND TABLE_NAME='APARIENCIA_JUGADOR') THEN 'v6 (falta v6_a_v7 hasta v17_a_v18)'
  WHEN EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA='proyecto2026' AND TABLE_NAME='POSICIONES_MUNDO' AND ENGINE='MEMORY') THEN 'v5 (falta v5_a_v6 hasta v17_a_v18)'
  WHEN EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA='proyecto2026' AND TABLE_NAME='POSICIONES_MUNDO') THEN 'v4 (falta v4_a_v5 hasta v17_a_v18)'
  ELSE 'anterior a v4 (hace falta migracion_v2_a_v3 y v3_a_v4 antes)'
END AS tu_version;
