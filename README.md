# Draft Der Mauer · Re-Boo

Juego web de drafting (adaptación de Draftosaurus) ambientado en la Alemania dividida.
PHP 8 + MySQL/MariaDB + HTML/CSS/JS, en capas (Presentación, Lógica, Persistencia) con Facade, Singleton y DTO.

## Instalación en XAMPP (Windows)

1. **Base de datos.** Abrí phpMyAdmin o MySQL Workbench como `root` e importá
   `sql/Proyecto 2026, BDDv18.sql`. Crea el esquema `proyecto2026`, los datos de prueba
   y el usuario de la aplicación `reboo_app`.
   - ¿Ya tenías una base con datos? Hacé un respaldo y corré las migraciones que te falten, en orden:
     `sql/migracion_v2_a_v3.sql`, `sql/migracion_v3_a_v4.sql`, `sql/migracion_v4_a_v5.sql`, `sql/migracion_v5_a_v6.sql`, `sql/migracion_v6_a_v7.sql`, `sql/migracion_v7_a_v8.sql`, `sql/migracion_v8_a_v9.sql`, `sql/migracion_v9_a_v10.sql`, `sql/migracion_v10_a_v11.sql`, `sql/migracion_v11_a_v12.sql`, `sql/migracion_v12_a_v13.sql`, `sql/migracion_v13_a_v14.sql`, `sql/migracion_v14_a_v15.sql`, `sql/migracion_v15_a_v16.sql`, `sql/migracion_v16_a_v17.sql` y `sql/migracion_v17_a_v18.sql`, en ese orden (guía: `docs/actualizar_version.md`).
   - Las versiones viejas quedan en `sql/anteriores/`.
2. **Configuración y contraseñas de prueba.** Desde la carpeta del proyecto:
   ```
   C:\xampp\php\php.exe herramientas\instalar.php
   ```
   Crea `config.php` con una pimienta aleatoria y deja todas las cuentas de prueba con la contraseña `Prueba2026!`.
   Si tu MySQL usa otro usuario o contraseña, editá `BD_USUARIO` / `BD_CONTRA` en `config.php`.
3. **Levantar el sitio.** En VS Code: *Terminal → Run Task → Re-Boo: servidor web local*, o a mano:
   ```
   C:\xampp\php\php.exe -S 127.0.0.1:8000 index.php
   ```
   y entrá a http://127.0.0.1:8000/DraftDerMauer/
   Para el mundo abierto con varias personas conviene **Apache** en lugar de `php -S`: seguí los pasos de `herramientas/apache/reboo-vhost.conf`.

## Cuentas de prueba (contraseña `Prueba2026!`)

| Usuario | Rol |
|---|---|
| AURA, testeo | jugador |
| pepe, juan_admin, admin_prueba | administrador |

Los bots (`Bot Klaus`, `Bot Greta`, `Bot Dieter`, `Bot Ilse`) no pueden iniciar sesión.

## Qué se puede hacer

| Dónde | Qué |
|---|---|
| Inicio → Crear partida | Sala pública o privada de 2 a 5, con reglas oficiales o personalizadas (rondas, fichas, valores 1/3/7, caras del dado...) |
| Sala de espera | Esperar gente o completar con bots (con bots no se ganan puntos de tienda) |
| Partida | Dado, colocación, la X para corregir, "cambiar ficha", tiempo por turno, abandono, puntaje final |
| Competitivo | Cola por MMR, mesas de 4 (o de 2-3 si esperás más de un minuto), rangos de Recluta a General |
| Ranking | Top 100 por MMR, puntos históricos, victorias y % de victorias |
| Galería | Las 9 fichas con su regla y un dato histórico; equipar skins compradas |
| Berlín (mundo abierto) | Elegir bando, caminar por 12 distritos, cruzar por Checkpoint Charlie, armar mesas en los cafés, chat, conquista por temporadas, día y noche, clima, peatones y tranvías, música, apariencia del personaje, misiones con contactos, niveles y logros, contactos con rutina diaria, ánimo, confianza, memoria y diálogos con opciones, sucesos (controles, mercados, manifestaciones, apagones…), radio y diario del día, desafíos de tablero contra los contactos (reglas especiales, escenarios y objetivos extra) |
| Tutorial | Las reglas paso a paso. Aparece solo la primera vez; se puede saltear y volver a ver desde el inicio |
| Reportes | Avisar un problema o denunciar a un jugador (también desde la partida con ⚑ y desde el chat de Berlín). Los administradores los resuelven desde su panel |
| Celular | Todo el sitio se adapta a pantallas chicas; en Berlín se camina tocando el mapa |
| Amigos y grupos | Perfil público, solicitudes de amistad, ver dónde están tus amigos, bloquear, grupos de hasta 4 con chat propio, invitar a tu mesa y mirar partidas sin ver las manos |
| Torneos | Torneos relámpago de 4 u 8 armados por los jugadores, cuadro por MMR, partidas que se pueden mirar, premio para el campeón. Sede: la Torre de TV |
| Idiomas | Español, inglés y alemán (selector ESP/ENG/DEU en el login, el inicio, la tienda, el registro y la calculadora): toda la interfaz y toda la campaña |
| Personalizar | Tu personaje de Berlín (pelo, sombrero, abrigo, accesorios y todos los colores) y tu perfil al estilo Discord (banner, acento, estado, pronombres, logros destacados y marco) |
| Primera persona | En Berlín, el botón 👁 (o la tecla V) cambia a una vista en primera persona y vuelve a la normal |
| Campaña | «Nacht über Berlin»: el prólogo jugable con cinemáticas, sigilo, persecución, decisiones y combate táctico por turnos, la campaña completa: prólogo, ocho actos, diecinueve capítulos y tres finales que dependen de tus decisiones. Se entra desde Ingrid, en Berlín |
| Calculadora | Modo seguimiento del juego físico, calculado con el mismo motor del servidor |

## Tests

```
C:\xampp\php\php.exe tests\motor_test.php     (reglas, sin base de datos)
C:\xampp\php\php.exe tests\partida_test.php   (partidas, bots, personalizadas, competitivo)
C:\xampp\php\php.exe tests\mundo_test.php     (mapa, bandos, cafés, influencia, temporadas)
C:\xampp\php\php.exe tests\misiones_test.php  (misiones, XP y logros; con la base recién importada)
C:\xampp\php\php.exe tests\social_test.php    (amigos, grupos, espectador y gestos; con la base recién importada)
C:\xampp\php\php.exe tests\torneos_test.php   (torneos; con la base recién importada)
C:\xampp\php\php.exe tests\npc_test.php       (contactos: rutinas, ánimo, confianza y diálogos; con la base recién importada)
C:\xampp\php\php.exe tests\sucesos_test.php   (sucesos, radio y diario; con la base recién importada)
C:\xampp\php\php.exe tests\desafios_test.php  (misiones de tablero; con la base recién importada)
C:\xampp\php\php.exe tests\campana_test.php   (campaña; con la base recién importada)
C:\xampp\php\php.exe tests\combate_test.php   (combate táctico; con la base recién importada)
C:\xampp\php\php.exe tests\capitulos_test.php (capítulos; con la base recién importada)
C:\xampp\php\php.exe tests\personalizacion_test.php (personaje y perfil; con la base recién importada)
```
Los dos últimos usan la base de datos y crean partidas de prueba con AURA y testeo: correlos en una base de desarrollo.

## Estructura

| Carpeta | Qué hay |
|---|---|
| `Capa_Presentacion/` | Páginas PHP, CSS, JS, imágenes (`img/juego/` = fichas y dado) |
| `Capa_Logica/` | Reglas de negocio. `MotorJuego.php` aplica el manual del juego |
| `Capa_Persistencia/` | Acceso a la base (Singleton por tabla) |
| `DTO/` | Objetos de transferencia (`ConfigPartidaDTO.php` = reglas de una partida) |
| `sql/` | Base v18, migraciones, `ver_version.sql` y versiones anteriores |
| `datos/` | Mapa del mundo abierto (`mundo/berlin.json`, formato Tiled) |
| `herramientas/` | Instalador, generador de arte del mundo (`arte/`) y configuración de Apache (`apache/`) |
| `tests/` | Tests por consola |

## Decisiones de reglas que el manual no cerraba

- Quien tira el dado no sufre su restricción (como en Draftosaurus).
- El Muro tiene capacidad 12.
- Con "cambiar ficha" y sin fichas previas en el tablero, solo se coloca.
- Si ninguna ficha de la mano entra en un recinto permitido, va al Muro (aunque sea un político sin Políticos abierto).
- No se puede sacar el único político de Políticos si hay otros políticos afuera.

## Puntos, ranking y mundo

- Quien abandona pierde los puntos de la partida. Si queda uno solo, la partida termina y recibe lo que llevaba.
- Partidas con bots: no dan puntos de tienda ni de ranking (sí cuentan como jugadas).
- Personalizadas: dan puntos de tienda pero no suman al ranking.
- `PntsPartida` es el saldo de la tienda; `PuntosHistoricos` nunca baja y es el que usa el ranking.
- Influencia en Berlín: 1.º = 3, 2.º = 2, resto = 1 (el competitivo vale doble), en el distrito del café donde se jugó.
  Si tu bando ya dominaba ese distrito, ganás un 10 % más de puntos de tienda.
- Las temporadas duran 30 días; al cerrar gana el bando con más distritos. Se puede cambiar de bando una vez por temporada.

## Abrir el juego a internet
Para jugar con gente de otras casas y ciudades (túnel de Cloudflare, Apache, y lo que hay que cambiar antes por seguridad) mirá `docs/publicar_en_internet.md`. Para cambiar o desactivar la clave de una cuenta: `php herramientas/cambiar_clave.php NOMBRE "NuevaClave"` (o `NOMBRE --desactivar`).
