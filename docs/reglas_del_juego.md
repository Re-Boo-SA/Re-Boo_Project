# Draftosaurus por DRAFT DER MAUER

## Manual de reglas, mecánicas y funcionamiento del juego

**Versión:** Documento de diseño del juego físico y digital  
**Objetivo:** Describir las piezas, el tablero, los dados, las reglas de colocación, la puntuación, los turnos y las funcionalidades de la versión digital.

\---

# 1\. Introducción

Draft Der Mauer es un juego de estrategia de tablero en el que los jugadores colocan fichas en distintos recintos, siguiendo las restricciones de las casillas y los resultados de un dado.

Cada recinto tiene una función particular, una capacidad determinada y unas reglas de puntuación propias. La elección de dónde colocar una ficha es importante porque cada espacio puede contribuir a la puntuación final de una forma diferente.

El juego utiliza seis recintos principales y un espacio adicional central denominado **Muro Central**. Los jugadores disponen de fichas de distintas categorías: militares, obreros, políticos y espías.

El dado introduce restricciones que pueden limitar los recintos en los que se permite colocar una ficha o exigir una acción especial, como mover una ficha que ya estaba en el tablero.

La versión digital debe reproducir estas mecánicas del juego físico. La diferencia principal es que las acciones se realizan mediante una interfaz en la computadora en lugar de colocar físicamente las fichas sobre el tablero.

La versión digital es un juego en línea para entre dos y cinco jugadores, con partidas en tiempo real, un historial de partidas y una tienda de apariencias (skins) para las fichas.

## 1.1. Principios fundamentales

El funcionamiento del juego se basa en los siguientes principios:

* Cada recinto tiene una función y una capacidad específicas.
* Las fichas se colocan siguiendo las reglas del recinto y las restricciones del dado.
* Las fichas se colocan en los espacios disponibles del recinto, de izquierda a derecha.
* La puntuación se calcula de acuerdo con las reglas de cada recinto.
* Los políticos pueden colocarse en otros recintos, pero existe una condición previa para habilitar esa posibilidad.
* El Muro Central permite colocar fichas cuando no resulta conveniente o posible hacerlo en los demás recintos.
* El dado no proporciona multiplicadores ni bonificaciones generales: sus caras establecen restricciones o acciones concretas.
* En el juego físico, el dado se tira físicamente. En la versión digital, el jugador que tiene el dado lo lanza desde la interfaz y el sistema genera y aplica el resultado.
* Las fichas se reparten desde una bolsa con una cantidad limitada que depende del número de jugadores.
* La partida consta de dos rondas de seis turnos y gana el jugador con más puntos al finalizar.

# 2\. Componentes del juego

Los componentes principales son el tablero, las fichas, el dado y los espacios destinados a registrar las jugadas y la puntuación.

## 2.1. El tablero

El tablero está organizado en dos columnas y tres filas, con seis recintos principales:

1. Oeste.
2. Políticos.
3. Fábrica u Obreros.
4. Este.
5. Espías.
6. Militares.

Entre las dos columnas se encuentra el Muro Central, que funciona como un espacio adicional.

### Distribución del tablero

|Fila|Columna izquierda|Columna derecha|
|-|-|-|
|Superior|Oeste|Políticos|
|Central|Fábrica u Obreros|Este|
|Inferior|Espías|Militares|

El Muro Central se encuentra entre ambas columnas.

La distribución tiene importancia porque dos caras del dado restringen la colocación según el lado del tablero.

* **Lado izquierdo:** Oeste, Fábrica y Espías.
* **Lado derecho:** Políticos, Este y Militares.

El Muro Central no pertenece a ninguna de esas dos columnas y tiene una regla propia.

## 2.2. Capacidad de los recintos

|Recinto|Capacidad|
|-|-:|
|Oeste|6 fichas|
|Políticos|1 ficha|
|Fábrica u Obreros|3 fichas|
|Este|6 fichas|
|Espías|2 fichas|
|Militares|3 fichas|
|Muro Central|Capacidad máxima no especificada|

La cantidad de fichas de cada tipo es limitada y depende de las fichas de la partida (ver sección 2.4). Además, cada recinto mantiene su capacidad física.

Por ejemplo, aunque en la bolsa queden varias fichas de tipo Cabo, Fábrica sigue teniendo tres espacios.

## 2.3. Las fichas

Las fichas representan los distintos tipos de unidades que pueden utilizarse en el tablero.

### A. Fichas militares

La categoría militar incluye tres tipos:

* **General**
* **Oficial Militar**
* **Tropa**

Estas fichas están asociadas principalmente al recinto Militares.

### B. Fichas de obreros

La categoría de obreros incluye tres tipos:

* **Cabo**
* **Oficial Obrera**
* **Peón**

Estas fichas están asociadas principalmente al recinto Fábrica.

### C. Ficha política

Existe un único tipo de ficha política:

* **Político**

Los políticos pueden colocarse en el recinto Políticos y, una vez cumplida la condición correspondiente, también en otros recintos.

Cada político colocado suma un punto, independientemente del recinto en el que se encuentre.

### D. Fichas de espías

La categoría de espías incluye dos tipos:

* **Markus**
* **Ursula**

El recinto Espías utiliza estas dos fichas para obtener su puntuación especial.

## 2.4. Disponibilidad de fichas

Las fichas se obtienen de una bolsa, igual que en Draftosaurus. La cantidad de fichas de cada tipo es limitada y varía según el número de jugadores.

### Composición total de las fichas

El juego completo cuenta con 60 fichas:

|Categoría|Tipo de ficha|Cantidad|
|-|-|-:|
|Militares|General|6|
|Militares|Oficial Militar|6|
|Militares|Tropa|6|
|Obreros|Cabo|6|
|Obreros|Oficial Obrera|6|
|Obreros|Peón|6|
|Políticos|Político|14|
|Espías|Markus|5|
|Espías|Ursula|5|
|**Total**||**60**|

Por categoría, el juego dispone de 18 militares, 18 obreros, 14 políticos y 10 espías.

### Fichas según la cantidad de jugadores

La partida admite de dos a cinco jugadores. Antes de comenzar se retiran fichas de la partida según la cantidad de jugadores:

|Jugadores|Espías retirados|Políticos retirados|Militares retirados|Obreros retirados|Fichas en la partida|
|-:|-:|-:|-:|-:|-:|
|2|2|2|3|3|50|
|3|4|4|3|3|46|
|4|2|2|3|3|50|
|5|0|0|0|0|60|

Con cinco jugadores se juega con las 60 fichas, sin ningún cambio.

Las cantidades retiradas se indican por categoría (espías, políticos, militares y obreros). Dentro de cada categoría, las fichas se retiran de la siguiente forma:

* **Militares:** se retira una ficha de cada uno de los tres tipos (General, Oficial Militar y Tropa).
* **Obreros:** se retira una ficha de cada uno de los tres tipos (Cabo, Oficial Obrera y Peón).
* **Políticos:** al existir un único tipo, se retira de él la cantidad que corresponda.
* **Espías:** se retiran en partes iguales de cada tipo. Si se retiran dos espías, se retira una Ursula y un Markus; si se retiran cuatro, se retiran dos Ursula y dos Markus.

### Reparto de fichas

Al comienzo de cada ronda, el sistema reparte seis fichas de la bolsa a cada jugador de forma completamente aleatoria.

Pueden salir fichas repetidas sin que ello suponga ningún problema, siempre dentro del límite de fichas de cada tipo indicado en la tabla anterior.

Las fichas que tiene un jugador en la mano solo las puede ver ese jugador.

# 3\. El dado

El dado tiene seis caras. Cada cara representa una regla de colocación o una acción especial.

En el juego físico, un jugador tira el dado y obtiene un resultado. En la versión digital, el jugador que tiene el dado en ese turno lo lanza desde la interfaz y el sistema genera el resultado, lo muestra a todos los jugadores y aplica la regla correspondiente.

El dado afecta a la colocación de ese turno. Una vez lanzado, cada jugador debe elegir, entre las fichas que tiene en la mano, cuál colocar y en qué recinto, respetando la cara obtenida.

## 3.1. Cara 1: bloqueo del lado izquierdo

**Efecto:** no se pueden colocar fichas en los tres recintos del lado izquierdo.

Los recintos afectados son:

* Oeste.
* Fábrica.
* Espías.

Los recintos de la derecha siguen disponibles, siempre que se cumplan sus propias reglas.

El Muro Central tampoco queda bloqueado por esta cara.

## 3.2. Cara 2: bloqueo del lado derecho

**Efecto:** no se pueden colocar fichas en los tres recintos del lado derecho.

Los recintos afectados son:

* Políticos.
* Este.
* Militares.

Los recintos de la izquierda siguen disponibles, siempre que se cumplan sus propias reglas.

El Muro Central tampoco queda bloqueado por esta cara.

## 3.3. Cara 3: solo Militares

**Efecto:** la colocación queda restringida al recinto Militares.

El jugador debe colocar allí una ficha militar válida, respetando la capacidad del recinto y las reglas de puntuación.

No se puede elegir otro recinto principal para colocar la ficha durante ese turno.

El Muro Central tampoco queda bloqueado por esta cara.

## 3.4. Cara 4: bloqueo de Fábrica

**Efecto:** no se pueden colocar fichas en el recinto Fábrica u Obreros.

Los demás recintos continúan disponibles de acuerdo con sus propias reglas y con cualquier otra restricción aplicable.

## 3.5. Cara 5: cambiar de lugar una ficha

Esta cara obliga a realizar una acción especial además de la colocación normal: mover una ficha que ya estaba colocada.

El movimiento no funciona como una colocación normal.

### Cuando el jugador que utiliza el tablero es quien tiró el dado

* Debe mover una ficha propia que ya esté colocada.
* Solo puede mover una ficha durante esa acción.
* Además del movimiento, debe colocar una ficha de su mano, como en cualquier otro turno.
* El turno se completa cuando se han realizado ambas acciones: el cambio de lugar de una ficha y la colocación de una ficha de la mano.
* La ficha movida debe terminar en una ubicación permitida por las reglas aplicables al juego.

El movimiento no sustituye a la colocación: el jugador realiza las dos acciones durante el mismo turno.

### Cuando el dado lo tiró otro jugador

La partida de un jugador que no fue quien tiró el dado, el resultado se registra igualmente para mantener el seguimiento de la partida.

Sin embargo, ese jugador no queda obligado a mover una ficha propia. Continúa con su jugada normal, sin aplicar a su tablero las restricciones que no le corresponden.

## 3.6. Cara 6: solo Espías

**Efecto:** la colocación queda restringida al recinto Espías.

El jugador debe colocar una ficha de espía válida, respetando la capacidad del recinto y las reglas de colocación.

No se puede colocar la ficha en otro recinto principal durante ese turno.

El Muro Central tampoco queda bloqueado por esta cara.

## 3.7. Resumen del dado

|Cara|Efecto|
|-|-|
|Lado izquierdo|Bloquea Oeste, Fábrica y Espías.|
|Lado derecho|Bloquea Políticos, Este y Militares.|
|Solo Militares|Restringe la colocación a Militares.|
|Bloqueo de Fábrica|Impide colocar en Fábrica.|
|Cambiar ficha|El jugador que tiró debe mover una ficha propia ya colocada y, además, colocar una ficha de su mano.|
|Solo Espías|Restringe la colocación a Espías.|

El dado no concede multiplicadores de puntuación ni bonificaciones generales. Su función es modificar las posibilidades de colocación o exigir la acción especial de movimiento, que se realiza además de la colocación.

# 4\. Reglas generales de colocación

## 4.1. Una ficha por turno

La regla general es que el jugador coloca una sola ficha durante su turno.

No puede colocar dos fichas nuevas en la misma jugada.

La excepción no consiste en colocar más fichas, sino en realizar la acción especial de movimiento, además de la colocación, cuando corresponde.

## 4.2. Selección del recinto

Antes de colocar una ficha, el jugador debe determinar en qué recinto quiere jugar.

La elección depende de:

* Los espacios disponibles.
* Los tipos de fichas permitidos.
* Las reglas particulares del recinto.
* La cara del dado.
* Las condiciones especiales que ya se hayan cumplido.

## 4.3. Orden de colocación

Las fichas se colocan en los espacios disponibles de izquierda a derecha.

En Fábrica y Militares, el orden de las fichas y de los tipos diferentes influye en la puntuación.

## 4.4. Fichas repetidas

Se pueden utilizar fichas del mismo tipo en diferentes turnos. No existe una restricción general que prohíba repetir un tipo de ficha en toda la partida. Al repartirse, también pueden salir fichas repetidas en la mano de un jugador, siempre dentro del límite de fichas de ese tipo que existe en la bolsa (ver sección 2.4).

No obstante, la repetición no elimina las restricciones específicas de cada recinto ni crea espacios adicionales.

## 4.5. Corrección de una jugada

En la versión digital existe una función para corregir la ficha que se acaba de colocar durante el turno actual.

La ficha del turno actual muestra una X que permite retirarla y sustituirla por otra ficha o colocarla en el recinto correcto.

Esta posibilidad no permite retirar libremente fichas de turnos anteriores.

Las fichas de turnos anteriores permanecen bloqueadas frente a la corrección normal. Solo pueden cambiar de lugar cuando corresponde la acción especial del dado.

La X es una herramienta para corregir la jugada recién registrada, no una acción adicional del juego físico.

# 5\. Reglas y puntuación de cada recinto

Cada recinto tiene una función propia. La puntuación final se calcula aplicando las reglas correspondientes a cada uno.

## 5.1. Oeste

**Ubicación:** fila superior, columna izquierda.  
**Capacidad:** seis fichas.

Oeste dispone de seis espacios. Cada ficha colocada en el recinto suma un punto, hasta un máximo de seis puntos por ocupación del recinto.

|Fichas colocadas|Puntos del recinto|
|-:|-:|
|0|0|
|1|1|
|2|2|
|3|3|
|4|4|
|5|5|
|6|6|

El recinto no puede superar su capacidad de seis fichas.

Los políticos colocados aquí también aportan su punto político, según la regla general de puntuación de los políticos. En consecuencia, el cálculo total debe distinguir la puntuación básica del recinto y los puntos adicionales de los políticos para evitar confundir ambos conceptos.

## 5.2. Este

**Ubicación:** fila central, columna derecha.  
**Capacidad:** seis fichas.

Este utiliza el mismo principio de puntuación básica que Oeste.

Cada ficha colocada en el recinto suma un punto, hasta un máximo de seis puntos por ocupación del recinto.

|Fichas colocadas|Puntos del recinto|
|-:|-:|
|0|0|
|1|1|
|2|2|
|3|3|
|4|4|
|5|5|
|6|6|

Los políticos colocados aquí también aportan su punto político adicional.

## 5.3. Fábrica u Obreros

**Ubicación:** fila central, columna izquierda.  
**Capacidad:** tres fichas.

Fábrica está asociada a las fichas de obreros:

* Cabo.
* Oficial Obrera.
* Peón.

La puntuación utiliza tres valores: **1, 3 y 7 puntos**.

Estos valores no pertenecen de forma fija a una ficha específica. Se asignan según los tipos diferentes que aparecen en el recinto y el orden de sus posiciones.

### Funcionamiento de la puntuación

* El primer tipo diferente ocupa el valor de un punto.
* El segundo tipo diferente ocupa el valor de tres puntos.
* El tercer tipo diferente ocupa el valor de siete puntos.
* Si se repite un tipo que ya había aparecido, la repetición conserva el valor asignado a ese tipo.

Por ejemplo, si el primer tipo diferente es Cabo, ese tipo queda asociado al valor de un punto. Si después aparece Peón como segundo tipo diferente, Peón queda asociado al valor de tres puntos. Si aparece Oficial Obrera como tercer tipo diferente, queda asociado al valor de siete puntos.

Los políticos colocados en Fábrica suman además un punto cada uno, de acuerdo con su regla general.

La capacidad del recinto continúa siendo de tres fichas. La regla de repetición no crea espacios adicionales.

### Ejemplo de tipos diferentes

|Tipo diferente|Valor asignado|
|-|-:|
|Primer tipo|1 punto|
|Segundo tipo|3 puntos|
|Tercer tipo|7 puntos|

El orden en que aparecen los tipos diferentes determina su valor. No debe programarse el sistema como si Cabo valiera siempre uno, Peón siempre tres y Oficial Obrera siempre siete.

## 5.4. Militares

**Ubicación:** fila inferior, columna derecha.  
**Capacidad:** tres fichas.

Militares utiliza las fichas:

* General.
* Oficial Militar.
* Tropa.

Su sistema de puntuación es equivalente al de Fábrica: utiliza los valores de **1, 3 y 7 puntos** asignados por el orden de aparición de los tipos diferentes.

### Funcionamiento de la puntuación

* El primer tipo diferente recibe un valor de un punto.
* El segundo tipo diferente recibe un valor de tres puntos.
* El tercer tipo diferente recibe un valor de siete puntos.
* Las repeticiones conservan el valor que ya corresponde al tipo repetido.

Por ejemplo, si General aparece primero, su tipo queda asociado a un punto. Si Tropa aparece como segundo tipo diferente, queda asociada a tres puntos. Si Oficial Militar aparece como tercer tipo diferente, queda asociada a siete puntos.

Los políticos colocados en Militares suman también un punto cada uno.

La capacidad del recinto es de tres fichas, por lo que la puntuación debe respetar tanto el orden de los tipos como los espacios realmente disponibles.

## 5.5. Espías

**Ubicación:** fila inferior, columna izquierda.  
**Capacidad:** dos fichas.

El recinto Espías utiliza dos tipos:

* Markus.
* Ursula.

Para obtener los cuatro puntos de la combinación, deben estar presentes los dos tipos de espía.

|Fichas de espía presentes|Puntuación de la combinación|
|-|-:|
|Ninguna|0|
|Solo Markus|0|
|Solo Ursula|0|
|Markus y Ursula|4|

La combinación completa de Markus y Ursula otorga cuatro puntos.

La capacidad del recinto es de dos fichas.

## 5.6. Políticos

**Ubicación:** fila superior, columna derecha.  
**Capacidad:** una ficha.

El recinto Políticos está destinado a colocar la ficha política inicial.

La condición fundamental es que **debe haber al menos un político colocado en el recinto Políticos antes de poder colocar políticos en otros recintos**.

Una vez cumplida esta condición, los políticos pueden colocarse en otros recintos siempre que se respeten las capacidades y las restricciones aplicables.

Cada político suma un punto, independientemente de dónde esté colocado.

Por ejemplo, un político en Políticos suma un punto; un político en Fábrica también suma un punto; y un político en Oeste suma un punto adicional a la puntuación básica que corresponda a ese recinto.

La regla vigente de este manual es que cada político suma un punto individualmente. No se aplica una regla de mayoría política como sustituto de esa puntuación.

## 5.7. Muro Central

**Ubicación:** espacio central entre las dos columnas del tablero.

El Muro Central es un espacio adicional que permite colocar cualquier tipo de ficha.

Su función es ofrecer una alternativa cuando las restricciones del dado o las condiciones de los recintos principales dificultan una colocación.

### Reglas del Muro

* Se puede colocar cualquier tipo de ficha.
* No queda bloqueado por las caras del dado que prohíben el lado izquierdo o derecho.
* No se le aplican las restricciones específicas de Fábrica, Militares o Espías.
* Cada ficha colocada en el Muro suma un punto básico.
* No tiene bonificaciones adicionales.

La capacidad máxima del Muro no está especificada en las reglas recopiladas. Debe definirse antes de implementar una limitación numérica en el juego digital.

# 6\. Puntuación total

La puntuación total se obtiene sumando los puntos de los recintos y las aportaciones adicionales de los políticos.

El sistema debe distinguir entre la puntuación propia del recinto y los puntos adicionales de una ficha política, para no omitir puntos ni contarlos dos veces.

## 6.1. Resumen de puntuación

|Recinto|Puntuación|
|-|-|
|Oeste|1 punto por ficha, hasta seis fichas.|
|Este|1 punto por ficha, hasta seis fichas.|
|Fábrica|Tipos diferentes valorados en 1, 3 y 7 puntos según el orden de aparición; las repeticiones conservan el valor de su tipo.|
|Militares|Tipos diferentes valorados en 1, 3 y 7 puntos según el orden de aparición; las repeticiones conservan el valor de su tipo.|
|Espías|4 puntos al completar Markus y Ursula.|
|Políticos|1 punto por cada político colocado, esté donde esté.|
|Muro Central|1 punto básico por ficha.|

## 6.2. Desglose de puntuación

Además del total, la interfaz debe mostrar un desglose por recinto para que el jugador pueda entender de dónde provienen sus puntos.

El desglose incluye:

* Oeste.
* Fábrica.
* Espías.
* Políticos.
* Este.
* Militares.
* Muro Central.

El resultado debe actualizarse cada vez que se coloca, retira o mueve una ficha, porque estas acciones pueden cambiar la puntuación.

## 6.3. Ganador y desempate

Al finalizar la segunda ronda se cuentan los puntos totales de cada jugador. El jugador que tiene más puntos gana la partida.

En caso de empate, gana el jugador que tenga menos políticos colocados en su tablero.

Si todavía persiste el empate, los jugadores empatados comparten la victoria.

## 6.4. Conservación de los puntos

Los puntos que obtiene cada jugador en la partida se conservan en su registro personal, no solo los del ganador. Esos puntos pueden usarse más adelante como moneda en la tienda de skins (ver sección 9).

Si un jugador abandona la partida, los puntos de esa partida se pierden (ver sección 8.4).

# 7\. Turnos y rondas

La partida contempla dos rondas de seis turnos cada una.

Por tanto, utiliza doce turnos distribuidos de la siguiente forma:

|Ronda|Turnos|
|-|-|
|Primera ronda|Turnos 1 a 6|
|Segunda ronda|Turnos 1 a 6|

Cada jugador recibe seis fichas por ronda y coloca una por turno, por lo que sus seis fichas se agotan al finalizar cada ronda.

El indicador de ronda y turno permite saber en qué momento de la partida se encuentra el jugador.

## 7.1. Inicio de la partida y de cada ronda

Al empezar la partida, uno de los jugadores recibe el dado.

Al comienzo de cada ronda, el sistema reparte seis fichas de la bolsa a cada jugador (ver sección 2.4). En la segunda ronda se reparten fichas nuevas, porque los jugadores ya han colocado todas las de la ronda anterior.

Las fichas colocadas en la primera ronda no se pierden al cambiar de ronda: permanecen en el tablero. Al finalizar la segunda ronda, cada jugador debe tener doce fichas en su tablero, seis de cada ronda.

La segunda ronda se juega exactamente igual que la primera.

## 7.2. Flujo general de un turno

Un turno normal se desarrolla de la siguiente manera:

1. Se determina qué jugador tiene el dado en este turno.
2. Ese jugador lanza el dado y el sistema muestra la cara obtenida a todos los jugadores.
3. Se comprueba qué restricciones corresponden a cada jugador.
4. Se selecciona el recinto.
5. Se elige una ficha permitida.
6. Se coloca la ficha en el siguiente espacio disponible.
7. Se actualiza la puntuación.
8. Se registra el turno como completado.
9. Cuando todos los jugadores han completado el turno, se revelan las fichas colocadas, se actualiza el historial de movimientos y se pasan las fichas y el dado al jugador de la izquierda.

La acción especial de movimiento no sustituye los pasos de colocación: cuando el jugador correspondiente debe mover una ficha, mueve una sola ficha y, además, coloca una ficha de su mano durante ese turno.

## 7.3. Paso de las fichas y del dado

Cuando todos los jugadores han colocado su ficha, el turno termina. Cada jugador queda con una ficha menos en la mano: al colocar una ficha en el tablero, los seis iniciales pasan a cinco, porque una queda en el tablero.

Al terminar cada turno:

* Las fichas que quedan en la mano de cada jugador se pasan al jugador de la izquierda.
* El dado también se pasa al jugador de la izquierda, por lo que quien lo lanza cambia en cada turno.

Comienza entonces el nuevo turno: se lanza el dado, cada jugador coloca una ficha en el tablero y se repite el paso de las fichas y del dado.

Esto continúa hasta que todos los jugadores hayan colocado sus seis fichas. Los jugadores están obligados a usar todas las fichas que reciben. En el sexto turno de cada ronda cada jugador dispone de una sola ficha, que debe colocar, y ya no quedan fichas para pasar.

## 7.4. Avance del turno

No debe permitir avanzar libremente al siguiente turno si todavía falta completar la acción requerida. El turno avanza cuando todos los jugadores han completado su acción.

En un turno normal debe registrarse una ficha colocada. En un turno de movimiento obligatorio deben registrarse ambas acciones: el movimiento de una ficha y la colocación de una ficha de la mano.

Cuando se completa el sexto turno de una ronda, se avanza a la siguiente ronda. Al finalizar los seis turnos de la segunda ronda, se muestra el resumen de puntuación final.

## 7.5. Corrección de la ficha del turno actual

La X de corrección permite resolver un error de registro antes de dar por definitiva la jugada.

La ficha colocada en el turno actual puede retirarse y sustituirse. La corrección no debe permitir eliminar fichas antiguas ni convertir el movimiento obligatorio en una colocación adicional.

Una vez que el turno queda cerrado y se avanza, la ficha pasa a considerarse parte de un turno anterior.

## 7.6. Fin de la partida

El juego termina después de la segunda ronda. En ese momento se cuentan los puntos totales de los jugadores y se determina el ganador, aplicando el desempate cuando corresponde (ver sección 6.3).

Después, la partida se guarda en el historial del jugador y los puntos obtenidos pasan a su registro (ver sección 9).

# 8\. Partidas en línea

## 8.1. Creación de la partida

Cada jugador puede crear su propia partida mediante la opción de crear partida.

Al crearla, el jugador elige con cuántos jugadores se jugará: dos, tres, cuatro o cinco.

La partida comienza automáticamente cuando se alcanza la cantidad de jugadores elegida por quien la creó. Al comenzar, se reparten las fichas a cada jugador de forma completamente aleatoria y empieza el juego.

## 8.2. Información visible y oculta

Durante la partida, cada jugador solo conoce la información que le corresponde:

* **Fichas en la mano:** cada jugador ve únicamente las fichas que tiene, ya sean las que salieron de la bolsa o las que le pasó el jugador de su derecha. No ve las fichas que tienen los demás. Por ejemplo, un jugador conoce sus propias cinco fichas, pero no las cinco que tiene otro jugador.
* **Fichas en el tablero:** un jugador no ve la ficha que otro coloca hasta que el turno termina. Mientras un turno está activo solo se ven las fichas de turnos anteriores.

|Turno|Mi tablero|Tablero de los demás jugadores|
|-|-|-|
|1|1 ficha (la que acabo de colocar)|Vacío|
|2|2 fichas|1 ficha|
|3|3 fichas|2 fichas|
|n|n fichas|n − 1 fichas|
|Final de la partida|12 fichas|12 fichas|

La numeración de turnos de la tabla es acumulada: los turnos de la segunda ronda equivalen a los turnos 7 a 12 de la partida.

## 8.3. Historial de movimientos de la partida

La partida en tiempo real tiene un historial de movimientos que indica qué jugador colocó qué ficha en qué recinto del tablero.

El historial se actualiza cuando termina cada turno. Mientras un turno está activo, sus movimientos todavía no aparecen.

|Momento de la partida|Movimientos que aparecen en el historial|
|-|-|
|Ronda 1, turno 1|Ninguno (historial vacío).|
|Ronda 1, turno 2|Movimientos del turno 1 de la ronda 1.|
|Ronda 1, turno 6|Movimientos de los turnos 1 a 5 de la ronda 1.|
|Ronda 2, turno 3|Movimientos de los turnos 1 a 6 de la ronda 1 y de los turnos 1 y 2 de la ronda 2.|

## 8.4. Abandono de un jugador

Un jugador abandona la partida cuando se desconecta, cuando decide salir por voluntad propia o cuando es suspendido o baneado durante la partida.

### Efectos sobre el jugador que abandona

* Pierde todos los puntos que llevaba en la partida y no los recibe.
* Sale de la partida.

### Efectos sobre la partida en curso

La partida se adapta y continúa con los jugadores restantes:

* Cada jugador conserva las fichas que tiene en la mano.
* Si el jugador que abandonó tenía el dado, este pasa automáticamente al jugador de su izquierda.
* Las fichas que tenía el jugador que abandonó vuelven a la bolsa y no siguen en la ronda.
* El orden de paso de las fichas se mantiene igual: hacia la izquierda, entre los jugadores restantes.

### Cuando queda un solo jugador

Si un jugador queda solo porque los demás abandonaron, la partida termina automáticamente, ya que no se puede jugar de a uno.

A ese jugador se le otorgan los puntos que tiene en ese momento. Por ejemplo, si está en la ronda 2, turno 2, y lleva 20 puntos cuando abandonan todos los demás, la partida termina y recibe esos 20 puntos.

Los jugadores que abandonaron no reciben ningún punto. Los puntos otorgados al jugador restante valen igual que cualquier otro punto que almacena un jugador.

# 9\. Historial, puntos y tienda

## 9.1. Historial de partidas

Cuando termina una partida, esta se guarda en el historial del jugador. Cada registro indica:

* El puesto en el que quedó el jugador: primero, segundo, tercero, cuarto o quinto, según la cantidad de jugadores de la partida.
* Los puntos que obtuvo en esa partida.

## 9.2. Puntos como moneda

Los puntos obtenidos en cada partida se conservan en el registro del jugador (ver sección 6.4) y se usan como moneda en la tienda.

## 9.3. Tienda de skins

La tienda ofrece skins para cada tipo de ficha. Cada tipo tiene sus propias skins, que no pueden ser iguales a las de otro tipo, porque las fichas son diferentes.

|Categoría|Tipos de ficha con skins propias|
|-|-|
|Militares|General, Oficial Militar, Tropa|
|Obreros|Cabo, Oficial Obrera, Peón|
|Políticos|Político|
|Espías|Markus, Ursula|

Las skins compradas pueden usarse luego para las fichas y, de esa forma, verse en el tablero.

# 10\. Funcionamiento de la versión digital

## 10.1. Panel de puntuación

El panel de puntuación muestra el total de puntos y el desglose por recinto.

Su función es permitir al jugador consultar rápidamente el estado de su partida.

Cada cambio en el tablero debe reflejarse en la puntuación correspondiente.

## 10.2. Tablero interactivo

Cada recinto del tablero debe poder seleccionarse.

Al seleccionar un recinto:

* Se identifica la casilla elegida.
* Se comprueba si está disponible para la jugada actual.
* Se muestran las fichas que se pueden colocar.
* Las fichas no permitidas se muestran apagadas o deshabilitadas.
* Las fichas válidas permanecen visibles y seleccionables.
* La ficha elegida se coloca en el recinto seleccionado si la jugada cumple las reglas.

El jugador debe poder identificar claramente por qué una ficha no está disponible cuando una restricción del dado o del recinto impide utilizarla.

## 10.3. Selección de fichas

Cada ficha aparece representada mediante una imagen y un nombre. El jugador solo puede seleccionar las fichas que tiene en la mano durante el turno.

Los tipos de ficha que pueden aparecer son:

* General.
* Oficial Militar.
* Tropa.
* Cabo.
* Oficial Obrera.
* Peón.
* Político.
* Markus.
* Ursula.

La disponibilidad de una ficha depende de que esté en la mano del jugador, del recinto seleccionado, de las condiciones previas y de la cara del dado.

La cantidad de fichas de cada tipo está limitada por las fichas de la partida (ver sección 2.4) y, además, los espacios del tablero también son limitados.

## 10.4. El dado

El dado lo lanza el jugador que lo tiene en el turno y el sistema genera el resultado. La cara obtenida se muestra a todos los jugadores y determina las restricciones o la acción especial que se aplican en ese turno.

Las caras posibles y sus efectos son los descritos en la sección 3.

## 10.5. Turno de tirar el dado

Al jugador que tiene el dado en el turno se le muestra la indicación **Yo tiro** y la opción de lanzarlo. Los demás jugadores ven qué jugador tiene el dado y esperan a que se muestre el resultado.

Al terminar el turno, el dado pasa al jugador de la izquierda (ver sección 7.3).

## 10.6. Colocación visual de las fichas

Las fichas colocadas deben aparecer en el recinto correspondiente, mostrando su imagen y, cuando corresponda, su nombre.

La interfaz debe conservar el orden de colocación de izquierda a derecha.

La representación visual debe permitir distinguir:

* Espacios vacíos.
* Espacios ocupados.
* Fichas del turno actual que pueden corregirse.
* Fichas de turnos anteriores que ya no pueden retirarse mediante la corrección normal.

## 10.7. Corrección con la X

La ficha del turno actual dispone de una X en su esquina superior derecha.

Al utilizarla, el sistema retira esa ficha y permite realizar una colocación corregida.

Las fichas de turnos anteriores no muestran la X de corrección.

El movimiento de fichas antiguas se gestiona mediante la acción específica del dado, no mediante la corrección ordinaria.

## 10.8. Progreso de la partida

La interfaz muestra:

* La ronda y el turno actuales.
* El jugador que tiene el dado y la cara obtenida.
* Las fichas que el jugador tiene en la mano.
* El historial de movimientos de la partida (ver sección 8.3).

## 10.9. Resumen de puntuación final

Al finalizar la segunda ronda, se muestra una ventana con la puntuación final.

Esta ventana debe incluir:

* El total de puntos, individual y por jugador
* La puntuación de cada recinto. individual
* El desglose de los puntos.
* La puntuación relacionada con las fichas colocadas.

El resumen debe corresponder al estado final del tablero, incluidos los movimientos realizados durante la partida.

# 11\. Validaciones y restricciones que debe aplicar el sistema

La versión digital debe validar cada acción antes de modificar el tablero.

## 11.1. Validación del recinto

Antes de colocar una ficha, el sistema debe comprobar:

* Que el recinto tenga un espacio libre.
* Que la ficha elegida esté en la mano del jugador.
* Que el tipo de ficha esté permitido.
* Que se hayan cumplido las condiciones previas.
* Que la cara del dado permita la acción.
* Que el turno no esté ya completado.

## 11.2. Validación de políticos

Si no hay ningún político en el recinto Políticos, la colocación de políticos en otros recintos debe estar deshabilitada.

Cuando se coloca el político inicial, la condición queda cumplida y los políticos pueden colocarse en otros recintos, respetando las demás reglas.

## 11.3. Validación de movimientos

Cuando corresponde cambiar una ficha de lugar:

* Debe seleccionarse una ficha propia ya colocada.
* Solo puede moverse una ficha.
* Debe comprobarse que el destino sea válido.
* El jugador también debe colocar una ficha de su mano en ese turno, pero no más de una.
* El turno se completa cuando se han realizado el movimiento y la colocación.

## 11.4. Validación de espacios

Un recinto no puede superar su capacidad.

Que todavía queden fichas en la bolsa no elimina el límite de espacios del tablero.

## 11.5. Validación de la puntuación

El sistema debe calcular los puntos a partir de las fichas realmente colocadas y de las reglas del recinto.

La puntuación no debe depender de una cifra escrita manualmente, sino del estado del tablero.

Los cambios de posición deben provocar un nuevo cálculo de los puntos afectados.

# 12\. Reglas que no deben confundirse

Para mantener la coherencia  deben respetarse estas distinciones:

1. **Las fichas no son ilimitadas.** El juego tiene 60 fichas en total y la cantidad en uso depende del número de jugadores.
2. **Cada jugador solo ve sus propias fichas en la mano.** La ficha que otro jugador coloca durante el turno activo no se ve hasta que el turno termina.
3. **La X corrige la ficha del turno actual.** No sirve para retirar fichas antiguas.
4. **Mover una ficha no equivale a colocar una nueva.** Con la cara de cambiar ficha, el jugador que tiró debe realizar las dos acciones en el mismo turno: mover una ficha y colocar una de su mano.
5. **Fábrica y Militares utilizan valores de 1, 3 y 7 según los tipos diferentes y su orden de aparición.** No se asigna un valor fijo a cada nombre de ficha.
6. **Los políticos aportan un punto cada uno.** Para colocarlos fuera del recinto Políticos, primero debe haber un político allí.
7. **Markus y Ursula deben estar presentes para obtener los cuatro puntos de la combinación de Espías.**
8. **El Muro Central suma un punto básico por ficha.** No tiene bonificaciones.
9. **Las caras del dado restringen acciones o recintos.** No multiplican la puntuación.
10. **Abandonar la partida implica perder los puntos de esa partida.** La excepción es el jugador que queda solo: recibe los puntos que tenía en ese momento.
11. **El desempate se resuelve a favor de quien tenga menos políticos en su tablero.** Si persiste, la victoria se comparte.
