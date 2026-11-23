# Campaña: Nacht über Berlin (adaptación)

Fuente: `docs/nacht_uber_berlin_documento_original.pdf` (documento de diseño narrativo del dueño del proyecto).
Esta es la adaptación acordada al juego. **No está implementada todavía**: va después de la Fase 4 y de la etapa "Berlín vivo".

## Decisiones tomadas (no cambiarlas sin preguntar)

- **Público**: historiadores y jugadores de alrededor de 20 años. **No es un juego para menores.**
- **Tono**: drama histórico serio. La violencia del documento original se mantiene (tiroteos, interrogatorios, muertes, lenguaje fuerte).
- **Tiroteos jugables**: combate táctico **por turnos** (coberturas, línea de visión, munición, heridas), validado en el servidor como las partidas. Nada de acción en tiempo real.
- **Persecuciones** (también en auto): tramos cortos con decisiones de ruta y tiempo límite resueltos por el servidor, entre cinemáticas. A pie: persecución "nivel 1" (el perseguidor avanza en el servidor en cada pedido de movimiento).
- **Protagonista**: en los capítulos **jugás como Viktor Adler**; en el mundo libre sos tu personaje.
- **Marco**: el mundo compartido es **Berlín a mediados de los años 70**. Ingrid recibe una caja sin remitente con fotos de Viktor; cada objeto abre un capítulo (recuerdo jugable de 1961 a 1968). El final elegido cambia tu Berlín del presente.
- **Época**: los capítulos se juegan en mapas propios con la ciudad de su época (en agosto de 1961 el Muro era alambre de púas y bloques). Corregido: el Reichstag no tiene la cúpula de vidrio (es de 1999).
- **Exactitud**: los datos de época se verifican con fuentes antes de escribirlos. Aviso visible: "Los personajes y la trama son ficción; el contexto histórico es real". No se usan nombres de víctimas reales del Muro.

## Reparto

| Personaje | En la campaña (1961-1968) | En el mundo compartido (años 70) |
|---|---|---|
| Viktor Adler | Protagonista jugable | Dejó la caja; su paradero depende del final |
| Friedrich Keller, Anneliese Weber, Thomas Richter | El grupo, como en el documento | Thomas puede reaparecer con un taller si lo trataste bien |
| Erich Falk | Antagonista (Stasi) | Destino abierto |
| Lukas Brandt, Martin Adler, Konrad Weiss, Margarete | Como en el documento | — |
| Otto Weiss | Sobrino pobre de Konrad, usado como mensajero | — |
| Samuel Hartmann | Agente occidental, **hermano de Gertrud Hartmann** (familia partida por el Muro) | — |
| Ingrid Vogel | Periodista joven del Oeste; recibió la última nota del padre de Anneliese; es la periodista del Final A | Recibe la caja de Viktor |
| Lotte | Intermediaria de "Herr Schwarz" con Anneliese (la pista de que los dos bandos participaron) | Sigue cerca del Reichstag |
| Gertrud Hartmann (antes "Frau Hartmann") | Empleada del registro del Este donde se borraban nombres | Funcionaria en Mitte |
| Paul Lange (antes "Erich Lange", renombrado para no chocar con Erich Falk) | Obrero obligado a levantar las barreras el 13/8/1961; conoce los túneles | Obrero en Friedrichshain |

## Estructura (prólogo, 8 actos, 19 capítulos, 3 finales)

Se mantiene la del documento original. Adaptaciones jugables principales:
- Cap. 1: para que Otto hable hay que ganarle una partida de Draft Der Mauer en su mesa de apuestas.
- Cap. 2: cruce por la estación de Friedrichstraße con papeles falsos; interrogatorio como diálogo bajo presión; huida.
- Cap. 4: rescate con sigilo y uniformes; combate por turnos si te descubren.
- Cap. 5: decisión: perdonar a Anneliese (acceso a sus contactos, incluida Lotte) o no.
- Cap. 6: el tren de las tres (uniformes, agujas como acertijo, abordaje, combate por turnos).
- Acto III: el 13 de agosto se camina por la ciudad como testigo, sin objetivos.
- Cap. 7: decisión: ayudar a Friedrich con Margarete o frenarlo.
- Cap. 9: Paul Lange guía por los túneles y una estación fantasma; un tramo se juega como Thomas siguiendo a Lukas.
- Cap. 12: decisión: sigilo o asalto.
- Cap. 14: en la cena de Konrad Weiss, una partida en su mesa es la excusa para llegar a su oficina.
- Cap. 16: si trataste mal a Thomas, se va antes y se lleva información.
- Cap. 17: decisión: llevar a Falk con vos o entregárselo a Samuel.
- Final: entregar el archivo a Ingrid (A), quedártelo (B) o destruirlo (C). Cierra con la escena del auto bajo la lluvia.
- En el presente: "expedientes olvidados": buscar por la ciudad los nombres de la Lista de la Noche ("No dejes que desaparezcan otra vez").

## Lo que hay que construir para la campaña

Escenas propias por capítulo (interiores y ciudad de época; mientras jugás uno, salís del mundo compartido), motor de guiones y cinemáticas, retratos con emociones, combate táctico por turnos, sigilo, persecuciones, variantes de época del Muro. Estimado: 35 a 45 misiones, por actos (primero prólogo + Acto I).

## Estado de la implementación

- **Prólogo: implementado** (guion en `datos/campana/prologo.json`, escenas en `datos/campana/mapas/`). El tiroteo de la estación es un combate táctico por turnos (derribar a los tres matones o resistir 5 rondas; si cae Viktor, se reintenta). La decisión del taller (`trato_anneliese`: aceptar o regatear) queda guardada para los capítulos siguientes.
- **Combate táctico por turnos: implementado** (ver `Capa_Logica/CombateLogica.php`).
- **Capítulo 1 «El dinero no tiene patria»: implementado** (`datos/campana/capitulo_1.json`): para que Otto hable hay que ganarle una partida real en su mesa. Decisión: `vender_lista`.
- **Capítulo 2 «El precio de la frontera»: implementado** (`capitulo_2.json`): en julio de 1961 el control de Friedrichstraße lo hace la Transportpolizei en el andén (el edificio de controles es de 1962 y no aparece). Interrogatorio con sospecha, sigilo y persecución en auto. Usa las decisiones `trato_anneliese` y `vender_lista`.
- Nombres ficticios agregados: el club Silberfisch y el ferroviario Lindner.
- **Capítulo 3 «La casa de los muertos»: implementado** (`capitulo_3.json`), con lo que el **Acto I queda completo**. La pared de nombres son trazos ilegibles (no se usan nombres de víctimas reales). Decisión nueva: `esperar_a_friedrich` (ventana o esperar).
- **Capítulo 4 «Un hombre en una habitación»: implementado** (`capitulo_4.json`). **Excepción acordada al «jugás como Viktor»**: la primera parte se juega como Friedrich, porque Viktor no puede estar en esa habitación y la única manera de vivir la presión del interrogatorio de Falk es ponerse en su lugar. El cambio se anuncia con una tarjeta al entrar («Friedrich Keller») y al salir («La historia vuelve a Viktor»). Rescate: sigilo con uniformes robados; si te descubren, el tiroteo es adentro, más duro y sin Friedrich; si no, es a la salida, con Friedrich (en los dos casos hay que derribar a los guardias antes de que llegue el refuerzo). Decisión registrada: `descubierto_rescate`.
- **Capítulo 5 «La traición de Anneliese»: implementado** (`capitulo_5.json`). Decisión de la adaptación: `perdon_anneliese` (`perdonar`: entrega sus contactos, entre ellos Lotte; `no_perdonar`: se queda sin la confianza del grupo). También `tono_anneliese` (calma o furia).
- **Capítulo 6 «El tren de las tres»: implementado** (`capitulo_6.json`), con lo que el **Acto II queda completo**. Los siete pasos del documento: el camión, los uniformes de la Reichsbahn (sigilo en el depósito y `vestir`), la entrada (el guardia pregunta por el jefe de turno: «el viejo Lindner»), los cambios de vía como acertijo (`agujas`, con una aguja acoplada y tiempo límite), el abordaje y el vagón correcto, los prisioneros y Projekt Morgen, y el enfrentamiento (combate) con la muerte del anciano y la foto de los tres hombres. Época: la Reichsbahn todavía usaba muchas locomotoras a vapor en 1961; uniformes azul oscuro. El padre de Anneliese no se nombra (el documento no le da nombre).
- **Acto III «El Muro» y capítulo 7 «Los que quedaron detrás»: implementados** (`capitulo_7.json`). El 13 de agosto se camina como testigo, sin objetivos (paso `paseo`): la mujer que llama a su hijo, el que discute con las Kampfgruppen, el S-Bahn cortado, el alambre y el muchacho que salta; después, Thomas: «¿Qué está pasando?» / «Están construyendo una prisión». Datos verificados (Bundesarchiv, bpb): domingo; desde la una de la mañana; vías cortadas, calles levantadas, alambre de púas; policías de frontera, Volkspolizei y Kampfgruppen, con el ejército detrás; nada de hormigón (15 de agosto) ni Puerta de Brandeburgo cerrada (14). Los nombres son inventados (el «Jürgen» de la mujer incluido). Decisión de la adaptación: `friedrich_margarete` (ayudar o frenar); Friedrich se va igual, como en el documento, y muere en la celda. Margarete muere intentando cruzar.
- **Acto IV «El hombre del otro lado»: implementado** (capítulos 8, 9 y 10).
  - Cap. 8 «Lukas»: invierno de 1962, el departamento; Lukas cuenta lo de Martin tal como el documento. Decisión `trato_lukas` (escucharlo o echarlo; si lo echás, el nombre de Martin te frena igual).
  - Cap. 9 «La ciudad bajo la ciudad»: Paul Lange (el mismo contacto de 1975, joven, de la BVG) guía por un túnel de mantenimiento bajo una estación fantasma. Verificado: desde el 13 de agosto de 1961, dos líneas de U-Bahn y una de S-Bahn pasaban bajo Berlín Oriental; sus estaciones quedaron cerradas, en penumbra, custodiadas por guardias armados, y los trenes del Oeste pasaban sin parar. Aparece Konrad Weiss. Después, como Thomas (excepción acordada), se sigue a Lukas y se escucha «Adler todavía no sabe quién mató a su hermano»; Thomas no le cuenta nada a Viktor.
  - Cap. 10 «El hermano»: la espera en la estación abandonada es un `paseo`; el reencuentro con Martin, con la elección del primer recuerdo (`recuerdo_martin`); el golpe y «Me dejaste solo».
- **Acto V «Los muertos también hablan»: implementado** (capítulos 11, 12 y 13).
  - Cap. 11 «La última cena»: la cena como `paseo` (Thomas le dice a Viktor que tiene algo que contarle: lo que escuchó en el Cap. 9), el brindis (`brindis`), Der Architekt, el baño vacío y el auto con sangre.
  - Cap. 12 «El precio de la verdad»: decisión del documento, infiltrarse o entrar con armas (`rescate_thomas`), cada una con su combate; la gran secuencia de acción sigue con una persecución en auto; Martin muere después de darle la llave y contarle lo del padre (Konrad Weiss).
  - Cap. 13 «La caja de Friedrichstraße»: verificado (Bundesarchiv) que desde los andenes del Oeste de Friedrichstraße se cambiaba de tren sin pasar el control, bajo vigilancia. Sigilo hasta el armario, `primero_caja` y la grabación del padre con su frase final.
- **Acto VI «El Arquitecto»: implementado** (capítulos 14 y 15).
  - Cap. 14 «El hombre que vendía la paz»: la partida acordada en la mesa de Konrad Weiss (bot 110) es la distracción mientras Thomas y Anneliese suben a la oficina; la foto de Anneliese con su padre y la carta (la detención se negoció con funcionarios occidentales: una operación conjunta); Weiss los esperaba («Tardaron bastante»), no es el Arquitecto, y Lukas apunta a Viktor.
  - Cap. 15 «El verdadero traidor»: la confesión de Lukas tal como el documento, el tiroteo en la oficina (Weiss muere en el medio), la huida a pie y la decisión nueva `trato_thomas`: Viktor se entera de que Thomas sospechaba de Lukas desde el Cap. 9. La usa el Cap. 16 (regla de la adaptación: si lo trataste mal, se va).
- **Acto VII «La caída de los hombres»: implementado** (capítulos 16 y 17).
  - Cap. 16 «La ciudad arde»: la noche en el departamento (un `paseo` por la casa, con Thomas que ya no duerme), la discusión y «¿Y después qué?». Regla de la adaptación: si en el Cap. 15 Viktor culpó a Thomas, a la mañana se va (la carta y el estuche de ganzúas, como en el documento); si lo entendió, se queda hasta el final.
  - Cap. 17 «El último aliado»: Samuel Hartmann (el hermano de Gertrud, el contacto de 1975: un vínculo que el documento no contradice), el plan contra Falk (`plan_falk`: escuchar a Anneliese o hacerlo a la manera de Viktor), el interrogatorio con los lugares invertidos (la misma sala del Cap. 4) y la decisión `destino_falk`: si Viktor dispara, Anneliese lo frena. Falk sigue vivo para llevarlos al archivo.
- **Acto VIII «La última noche» y el final: implementados** (capítulos 18 y 19). **La campaña está completa.**
  - Cap. 18 «El archivo» (noviembre de 1968): el viaje con Anneliese («Intentaré recordar cómo era vivir sin mirar constantemente por encima del hombro»), la instalación militar, los trabajadores asesinados, la oferta de Lukas, la carpeta del padre de Anneliese (murió en 1957, envenenado, en un hospital privado de Berlín Occidental), «¿Lo sabías?», el tiroteo y la muerte de Anneliese («No dejes que desaparezcan otra vez»). Si Thomas se quedó, le cierra el paso a Falk; si no, Falk escapa con una parte del archivo, como en el documento.
  - Cap. 19 «El último hombre»: el seguimiento de Lukas por los túneles, «La gente no quiere conocer la verdad», y la decisión final.
  - Los tres finales, tal como el documento, con consecuencias de la campaña (Lotte en el A si perdonaste a Anneliese; Falk declara ante un juez si Thomas lo detuvo; las postales de Thomas si se quedó) y un cierre distinto del marco de 1975 en cada uno. Un logro por final; se pueden ganar los tres volviendo a jugar el capítulo.
