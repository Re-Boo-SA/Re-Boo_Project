# Hoja de ruta del mundo abierto (Berlín)

Decidido con el dueño del proyecto. El orden de prioridad de los pilares es: mundo vivo, misiones y NPCs, facciones, eventos de temporada, espionaje, progresión y social. Las fases respetan las dependencias entre ellos.

## Decisiones tomadas

- El **arte lo genera Claude por código** (`herramientas/arte/generar_mundo.py`, Pillow): pixel-art procedural. Sin dependencias de arte externo.
- **Público**: historiadores y jugadores de alrededor de 20 años (no es un juego para menores).
- El **chat se mantiene abierto** (texto libre, 80 caracteres, límite de frecuencia, botón de reportar).
- Se usa **Apache** en lugar de `php -S` y **Phaser 3** como motor 2D.
- Regla de oro: **lo que se gana en el mundo nunca cambia el resultado de una partida.** El mundo alimenta al juego de cartas, no lo reemplaza.
- Sesiones cortas: ninguna mecánica obliga a estar conectado horas.

## Fase 0 — Fundaciones: HECHA

Apache, conexión única, posiciones en memoria, mapa Tiled como fuente única, colisiones en servidor, sprites animados con Phaser, contenido como datos (el mapa; las misiones vendrán en la Fase 2).

## Fase 1 — Mundo vivo: HECHA

Día de 30 min reales con atardecer y amanecer; de noche se prenden faroles y ventanas y los reflectores de las torres barren la franja. Clima cada 10 min (despejado, nublado, lluvia, niebla, nieve). Peatones por las veredas, dos tranvías en la avenida del Este (en el Oeste se fueron quitando), patrullas en la franja y un guardia a cada lado de Checkpoint Charlie. Música sintetizada distinta a cada lado del Muro, más ambiente de ciudad, lluvia y viento. Apariencia: piel, pelo y gorro (36 combinaciones). Todo determinista: no agrega carga al servidor.

## Fase 2 — Misiones y NPCs: HECHA

Cuatro contactos ficticios (Ingrid, periodista del Oeste; Erich, obrero en Friedrichshain; Frau Hartmann, funcionaria en Mitte; Lotte, cerca del Reichstag) con 8 misiones encadenadas y 5 logros, todo en `datos/mundo/misiones.json`. Tipos de paso: ir a un lugar, hablar con alguien, jugar o ganar partidas (opcionalmente en cafés de un lado del Muro). XP y niveles (100, 300, 600, 1000…), marcas `!` y `?` sobre los contactos, objetivo marcado en el mapa y en el minimapa, panel de misiones y logros. Todo validado en el servidor.

## Fase 3 — Espionaje y el Muro

Documentos falsos (se consiguen con misiones o en un mercado negro), cruce al otro lado con patrullas. **El sigilo se resuelve en el servidor** (detección con tiempos y azar que decide el servidor): un minijuego solo en el navegador se puede hackear. Depende de la Fase 2.

## Fase 4 — Progresión y social: HECHA

Hecho y probado: perfil público, amigos (solicitudes, presencia, invitar a la mesa, bloquear), grupos de hasta 4 con chat propio y miembros en el minimapa, mirar partidas sin ver las manos, gestos animados y torneos relámpago (eliminación de 4 u 8, sede en la Torre de TV). El departamento decorable se pasa a "Berlín vivo" porque necesita escenas interiores, igual que la campaña.

## Etapa "Berlín vivo" (en curso)

**A. Cerebro de los NPCs: HECHA.** Rutinas diarias con rutas reales, ánimo por clima y hora, confianza por jugador según bando y respuestas, memoria, diálogos con opciones (45 nodos), retratos con emoción, peatones que esquivan y menos gente de noche y con lluvia. 

**B. Sucesos, radio y diario: HECHA.** Siete sucesos por tramos de 10 minutos (control en Checkpoint Charlie, noche de torneo con doble influencia, mercado con XP, apagón, manifestación, concierto, visita oficial), con escena en el mapa y reacciones de los contactos. Radio en el HUD y diario del día con dos diarios ficticios, uno por lado. Todo el contenido, también en inglés.

**C. Misiones de tablero: HECHA.** Desafíos contra el bot de cada contacto (con su nombre y estilo): «Día de controles» de Paul (sin espías en el dado, con soldados en Militares), «Todo en regla» de Gertrud (sin usar el Muro), «Juego de espías» de Lotte (dado de espías, dos espías en el tablero) y «Dar vuelta la nota» de Ingrid (escenario: una ronda arrancando 2 a 7). Reintentos con comentarios del contacto.

**D. Campaña, primera entrega: HECHA.** Motor de escenas y cinemáticas (diálogos con retrato, decisiones, franjas, fundidos, cámara, sigilo con conos de visión, persecución a pie, luces, lluvia, sonido sintetizado) y el prólogo completo: la caja de Ingrid en 1975, la calle de 1961, el taller, la decisión con Anneliese, la estación (sigilo, tiroteo en cinemática, huida), el escondite con la libreta y Erich Falk. **D. Combate táctico por turnos: HECHO.** Validado y resuelto en el servidor (movimiento, línea de vista, cobertura, cubrirse, IA de los rivales, semilla oculta); el tiroteo de la estación del prólogo es el primero. **D. Acto I, capítulos 1 y 2: HECHOS.** «El dinero no tiene patria» (la Nachtliste, el club Silberfisch y la partida en la mesa de Otto) y «El precio de la frontera» (interrogatorio en Friedrichstraße, sigilo en la vía de carga y persecución en auto). **Capítulo 3 «La casa de los muertos»: HECHO**, con lo que el Acto I queda completo (la cerradura de Thomas, la pared de nombres, la decisión de esperar o no a Friedrich y su detención). **Acto II, capítulos 4 y 5: HECHOS** («Un hombre en una habitación», jugado en parte como Friedrich, con el rescate que cambia según te descubran o no; y «La traición de Anneliese», con la decisión de perdonarla o no). **Capítulo 6 «El tren de las tres»: HECHO** (uniformes, el acertijo de las agujas, el abordaje, Projekt Morgen y la foto de los tres hombres); el Acto II queda completo. **Personalización: HECHA (v17).** Personaje del mundo abierto con formas, accesorios y colores libres, y perfil al estilo Discord.

**Acto III «El Muro»: HECHO** (el 13 de agosto como testigo y el capítulo 7, con la muerte de Friedrich). **Acto IV «El hombre del otro lado»: HECHO** (capítulos 8 a 10: Lukas, el túnel bajo la estación fantasma y el seguimiento como Thomas, y el reencuentro con Martin). **Acto V «Los muertos también hablan»: HECHO** (la última cena, el rescate de Thomas con la muerte de Martin y la caja de Friedrichstraße). **Alemán: HECHO.** Toda la interfaz y la campaña (1930 textos), con el mismo mecanismo que el inglés.

**Acto VI «El Arquitecto»: HECHO** (la cena y la partida en la mesa de Konrad Weiss, la carta del padre de Anneliese y la traición de Lukas). **Acto VII «La caída de los hombres»: HECHO** (Thomas se va o se queda según cómo lo trataste, Samuel Hartmann y la captura de Falk). **Acto VIII y el final: HECHOS.** La etapa D (campaña «Nacht über Berlin») está completa: prólogo, ocho actos, diecinueve capítulos y tres finales, en español, inglés y alemán.

Queda del plan general: la etapa E de Berlín vivo (actividades) y las fases 3 (espionaje y el Muro), 5 (facciones) y 6 (eventos de temporada). Datos ya verificados para escribirlo: era domingo; la operación empezó una hora después de medianoche; se cortaron las vías del S-Bahn y el U-Bahn hacia el Oeste, se levantaron calles y se tendió alambre de púas; lo hicieron policías de frontera, Volkspolizei y Kampfgruppen, con el ejército (NVA) detrás; la Puerta de Brandeburgo se cerró recién el 14 y los primeros bloques de hormigón son del 15 (fuentes: Bundesarchiv, bpb).

**Primera persona en Berlín: HECHA.** Botón en el HUD (el mismo activa y vuelve); la vista normal sigue siendo la predeterminada.

Después: **E** actividades.


NPCs con rutinas, ánimo, confianza, memoria y diálogos con opciones; sucesos aleatorios con radio y diario; misiones que se juegan en el tablero; escenas interiores; motor de guiones y cinemáticas; campaña (ver `docs/campana.md`); actividades (tranvía, bicicleta, fotografía, coleccionables, estaciones fantasma, oficios). Persecuciones "nivel 1" (sin servidor en tiempo real). Conviene antes que la Fase 3, que reutiliza ese cerebro de NPCs.

## Fase 5 — Facciones con profundidad

Rangos de bando, chat de bando, aportes de recursos, mejoras de distrito (ventajas pequeñas, que no desequilibren las partidas) y objetivos semanales. Depende de la economía de las Fases 2 y 3.

## Fase 6 — Eventos de temporada

Cada temporada de 30 días narra un episodio histórico con clímax (puente aéreo 1948-49, levantamiento del 17 de junio de 1953, Túnel 29 en 1962, caída del Muro en 1989). Incluye un evento cooperativo por bando. Hasta entonces las temporadas siguen como hoy.

## Cuidados transversales

- Cada fase termina jugable y con tests (hay tests de carga en `tests/carga/`).
- Balancear la economía de puntos antes de abrir una mecánica nueva.
- No dar plazos en calendario: dependen del tiempo disponible.
