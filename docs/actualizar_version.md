# Actualizar un programa viejo a esta versión (sin perder datos)

Todo se hace en **cmd** (no PowerShell: no entiende `<` y `>` rompe los respaldos).
Cambiá `C:\Re-Boo` por tu carpeta y agregá `-p` a los comandos de MySQL si `root` tiene contraseña.
Atajo para no escribir la ruta larga, en cada ventana de cmd:

    set MYSQL="C:\Program Files\MySQL\MySQL Server 8.0\bin"

## 1. Antes de empezar
- Que nadie esté jugando: salgan de las partidas y cerrá el servidor (Ctrl+C en su ventana).

## 2. Respaldo (no te lo saltees)
    cd C:\Re-Boo
    %MYSQL%\mysqldump.exe -u root -p --routines --events --add-drop-database --databases proyecto2026 > respaldo_antes_de_actualizar.sql
    dir respaldo_antes_de_actualizar.sql
El archivo tiene que pesar decenas de KB. Si pesa 0, no sigas.

## 3. Cambiar el programa (la carpeta vieja queda de respaldo)
    cd C:\Re-Boo
    ren Re-Boo_Project-main Re-Boo_Project-viejo
Descomprimí el zip de la versión nueva (por ejemplo `Re-Boo_Completo_v21.zip`) dentro de `C:\Re-Boo` (tiene que quedar `C:\Re-Boo\Re-Boo_Project-main`). Después:
    copy Re-Boo_Project-viejo\config.php Re-Boo_Project-main\config.php
    xcopy Re-Boo_Project-viejo\Capa_Presentacion\img\perfiles Re-Boo_Project-main\Capa_Presentacion\img\perfiles\ /E /I /Y
El primero conserva tu clave de la base y las contraseñas de los usuarios. El segundo, las fotos de perfil.

## 4. Actualizar la base
    cd C:\Re-Boo\Re-Boo_Project-main
    %MYSQL%\mysql.exe -u root -p -N < sql\ver_version.sql
Dice en qué versión está. Corré SOLO las migraciones que faltan, en este orden, y una por una:
    %MYSQL%\mysql.exe -u root -p < sql\migracion_v4_a_v5.sql
    %MYSQL%\mysql.exe -u root -p < sql\migracion_v5_a_v6.sql
    %MYSQL%\mysql.exe -u root -p < sql\migracion_v6_a_v7.sql
    %MYSQL%\mysql.exe -u root -p < sql\migracion_v7_a_v8.sql
    %MYSQL%\mysql.exe -u root -p < sql\migracion_v8_a_v9.sql
    %MYSQL%\mysql.exe -u root -p < sql\migracion_v9_a_v10.sql
    %MYSQL%\mysql.exe -u root -p < sql\migracion_v10_a_v11.sql
    %MYSQL%\mysql.exe -u root -p < sql\migracion_v11_a_v12.sql
    %MYSQL%\mysql.exe -u root -p < sql\migracion_v12_a_v13.sql
    %MYSQL%\mysql.exe -u root -p < sql\migracion_v13_a_v14.sql
    %MYSQL%\mysql.exe -u root -p < sql\migracion_v14_a_v15.sql
    %MYSQL%\mysql.exe -u root -p < sql\migracion_v15_a_v16.sql
    %MYSQL%\mysql.exe -u root -p < sql\migracion_v16_a_v17.sql
    %MYSQL%\mysql.exe -u root -p < sql\migracion_v17_a_v18.sql
Cada una no muestra nada si salió bien. **Si alguna muestra un error, parás ahí** y vas al paso 7.
Al terminar, `ver_version.sql` tiene que decir `v18 (ya esta al dia)`.

## 5. Levantar
    C:\xampp\php\php.exe -S 127.0.0.1:8000 index.php

## 6. Comprobar
Entrá con tu usuario: tienen que estar el ranking, el saldo de la tienda, las skins y el historial de siempre. En Berlín vas a ver el mapa nuevo y a los contactos con un `!`.
**No corras los tests (`tests\...`) sobre tu base con datos reales**: crean partidas y reinician los bandos. Solo `motor_test.php` es seguro.

## 7. Si algo salió mal: volver atrás
    (cerrá el servidor)
    %MYSQL%\mysql.exe -u root -p < C:\Re-Boo\respaldo_antes_de_actualizar.sql
    cd C:\Re-Boo
    ren Re-Boo_Project-main Re-Boo_Project-fallido
    ren Re-Boo_Project-viejo Re-Boo_Project-main
Con eso queda todo como antes de empezar.

## Qué cambia con la actualización
- Se conserva: usuarios y contraseñas, saldos, puntos, MMR, skins, partidas, reportes, bandos, temporadas e influencia.
- Se pierde: nada importante. Lo único que se borra y se vuelve a crear es la tabla de posiciones del mapa (dónde está parado cada jugador en este instante): cada uno reaparece en su lado del Muro.
- Se agrega: apariencia del personaje, experiencia, misiones, logros, amigos, grupos, torneos y la confianza con cada contacto (todos empiezan de cero).
