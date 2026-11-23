<?php
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa_Logica/FachadaLogica.php');

SesionSegura::exigirRol('jugador');

/*
 * RF06: tutorial de las reglas. Se muestra solo la primera vez (desde el inicio);
 * se puede saltear en cualquier momento y volver a ver cuando se quiera.
 * Al terminarlo o saltearlo queda guardado en JUGADORES.TutorialVisto.
 */
$idUsuario = (int) $_SESSION['usuario_id'];
$jugadores = (new FachadaLogica())->retornoIJugadorLogica();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $accion = ($_POST['accion'] ?? '') === 'terminar' ? 'terminado' : 'salteado';
    $jugadores->marcarTutorialVisto($idUsuario);
    (new AuditoriaLogica())->registrar('tutorial', 'info', $idUsuario, null, 'Tutorial ' . $accion);
    header('Location: ' . urlAplicacion('inicio'));
    exit();
}

$primeraVez = !$jugadores->tutorialVisto($idUsuario);
$idioma = $_SESSION['idioma'] ?? 'es';

$fichas = [
    'Militares' => [['General', 'GeneralDraftDerMauer.png'], ['Oficial Militar', 'OficialDraftDerMauer.png'], ['Tropa', 'TropaDraftDerMauer.png']],
    'Obreros' => [['Cabo', 'ACaboDraftDerMauer.png'], ['Oficial Obrera', 'OficialMujerDraftDerMauer.png'], ['Peón', 'PeonDraftDerMauer.png']],
    'Políticos' => [['Político', 'PoliticoDraftDerMauer.png']],
    'Espías' => [['Markus', 'MarkusEspiaDraftDerMauer.png'], ['Ursula', 'UrsulaEspiaDraftDerMauer.png']],
];
$caras = [
    ['lado_izquierdo', 'NoIzquierdoDadoDraftDerMauer.png', 'Lado izquierdo', 'No se puede colocar en Oeste, Fábrica ni Espías.'],
    ['lado_derecho', 'NoDerechoDadoDraftDerMauer.png', 'Lado derecho', 'No se puede colocar en Políticos, Este ni Militares.'],
    ['solo_militares', 'SoloRecintoMilitaresDadoDraftDerMauer.png', 'Solo Militares', 'Solo se puede colocar en Militares (o en el Muro).'],
    ['bloquea_fabrica', 'NoFabricaDadoDraftDerMauer.png', 'Bloqueo de Fábrica', 'No se puede colocar en Fábrica.'],
    ['cambiar_ficha', 'ReacomodarFichaDadoDraftDerMauer.png', 'Cambiar ficha', 'Quien tiró mueve una ficha suya que ya estaba en el tablero y además coloca una de la mano.'],
    ['solo_espias', 'SoloEspiasDadoDraftDerMauer.png', 'Solo Espías', 'Solo se puede colocar en Espías (o en el Muro).'],
];

/** Mini tablero con la misma distribución que el de la partida. */
function miniTablero(string $id): string
{
    $recintos = [
        ['oeste', 'Oeste', '6'], ['politicos', 'Políticos', '1'],
        ['fabrica', 'Fábrica', '3'], ['este', 'Este', '6'],
        ['espias', 'Espías', '2'], ['militares', 'Militares', '3'],
    ];
    $html = '<div class="mini-tablero" id="' . e($id) . '">';
    foreach ($recintos as $i => [$codigo, $nombre, $capacidad]) {
        $lugares = $capacidad === '1' ? '1 lugar' : $capacidad . ' lugares';
        $html .= '<div class="mini-recinto r-' . e($codigo) . '" data-recinto="' . e($codigo) . '"><strong>' . e($nombre) . '</strong><small>' . e($lugares) . '</small></div>';
        if ($i === 1) {
            $html .= '<div class="mini-recinto r-muro" data-recinto="muro"><strong>Muro</strong><small>12 lugares</small></div>';
        }
    }
    return $html . '</div>';
}
?>
<!DOCTYPE html>
<html lang="<?= e($idioma) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draft Der Mauer - Tutorial</title>
    <link rel="icon" type="image/png" href="./img/imgPestania/LogoBabyDraftDerMauer.png">
    <link rel="stylesheet" href="css/fuentes.css">
    <link rel="stylesheet" href="css/paginas.css">
</head>
<body class="cuerpo-pagina cuerpo-tutorial" data-idioma="<?= e($idioma) ?>">
    <header class="barra-pagina">
        <span class="marca">DRAFT DER MAUER</span>
        <form method="POST" class="form-en-linea">
            <?= SesionSegura::campoCsrf() ?>
            <input type="hidden" name="accion" value="saltear">
            <button type="submit" class="volver boton-enlace" id="btn-saltear"><?= $primeraVez ? 'Saltear tutorial' : 'Salir del tutorial' ?></button>
        </form>
    </header>

    <main class="contenido tutorial" id="tutorial">
        <div class="progreso-tutorial" id="progreso-tutorial" hidden>
            <span id="texto-paso">Paso 1 de 8</span>
            <div class="barra-progreso"><span id="relleno-progreso"></span></div>
        </div>

        <!-- 1 -->
        <section class="paso caja" data-paso aria-labelledby="t1">
            <p class="etiqueta-paso">Bienvenida</p>
            <h1 id="t1">Cómo se juega Draft Der Mauer</h1>
            <p>Es un juego de <strong>drafting</strong> ambientado en el Berlín dividido: en cada turno elegís una ficha de tu mano, la ponés en tu tablero y le pasás el resto al jugador de al lado.</p>
            <ul class="lista-clave">
                <li><strong>2 a 5 jugadores</strong>, cada uno con su propio tablero.</li>
                <li><strong>2 rondas de 6 turnos</strong>: al final tenés 12 fichas en el tablero.</li>
                <li>Gana quien tenga <strong>más puntos</strong>. Si hay empate, gana quien tenga <strong>menos políticos</strong>; si sigue el empate, comparten la victoria.</li>
            </ul>
            <p class="nota">Este tutorial dura un par de minutos. Lo podés saltear cuando quieras y volver a verlo desde el inicio.</p>
        </section>

        <!-- 2 -->
        <section class="paso caja" data-paso aria-labelledby="t2">
            <p class="etiqueta-paso">El tablero</p>
            <h2 id="t2">Seis recintos y el Muro</h2>
            <div class="paso-dos-columnas">
                <?= miniTablero('tablero-recintos') ?>
                <div>
                    <p>El tablero tiene dos columnas. La de la <strong>izquierda</strong> es Oeste, Fábrica y Espías; la de la <strong>derecha</strong>, Políticos, Este y Militares. En el medio está el <strong>Muro</strong>.</p>
                    <p>Cada recinto tiene lugares limitados y las fichas se ponen de izquierda a derecha. Algunos solo aceptan un tipo de ficha:</p>
                    <ul class="lista-clave">
                        <li><strong>Fábrica</strong>: solo obreros.</li>
                        <li><strong>Militares</strong>: solo militares.</li>
                        <li><strong>Espías</strong>: solo espías.</li>
                        <li><strong>Políticos</strong>: un político.</li>
                        <li><strong>Oeste, Este y el Muro</strong>: cualquier ficha.</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- 3 -->
        <section class="paso caja" data-paso aria-labelledby="t3">
            <p class="etiqueta-paso">Las fichas</p>
            <h2 id="t3">Nueve fichas en cuatro grupos</h2>
            <div class="grupos-fichas">
                <?php foreach ($fichas as $grupo => $lista): ?>
                    <div class="grupo-fichas">
                        <h3><?= e($grupo) ?></h3>
                        <div class="fichas-fila">
                            <?php foreach ($lista as [$nombre, $imagen]): ?>
                                <figure><img src="img/fichas/<?= e($imagen) ?>" alt=""><figcaption><?= e($nombre) ?></figcaption></figure>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <p>Las fichas salen de una bolsa con cantidades limitadas (60 en total; con menos de 5 jugadores se retiran algunas). Al empezar cada ronda recibís <strong>6 fichas al azar</strong>. Tu mano solo la ves vos.</p>
        </section>

        <!-- 4 -->
        <section class="paso caja" data-paso aria-labelledby="t4">
            <p class="etiqueta-paso">Un turno</p>
            <h2 id="t4">Elegí, colocá y pasá</h2>
            <ol class="pasos-turno">
                <li><strong>Se tira el dado.</strong> Lo tira quien lo tiene en ese turno (aparece <em>Yo tiro</em>). Si no lo tira en 15 segundos, se tira solo.</li>
                <li><strong>Todos colocan a la vez.</strong> Tocá una ficha de tu mano y después uno de los recintos marcados en amarillo.</li>
                <li><strong>Confirmás.</strong> Hasta confirmar podés corregir la ficha del turno con la <strong>X</strong>. Las de turnos anteriores no se pueden sacar.</li>
                <li><strong>Se pasa todo a la izquierda.</strong> Cuando todos confirmaron, se muestran las fichas nuevas de cada tablero y le pasás tu mano y el dado al jugador de tu izquierda.</li>
            </ol>
            <p>Lo que ponen los demás no se ve hasta que termina el turno. Tenés <strong>60 segundos</strong> por turno: si no jugás, el juego coloca una ficha por vos.</p>
        </section>

        <!-- 5 -->
        <section class="paso caja" data-paso aria-labelledby="t5">
            <p class="etiqueta-paso">El dado</p>
            <h2 id="t5">Cada cara pone una restricción</h2>
            <div class="paso-dos-columnas">
                <div class="demo-dado">
                    <?= miniTablero('tablero-dado') ?>
                    <div class="resultado-dado" aria-live="polite">
                        <img id="img-dado" src="img/juego/CaraVaciaDadoDraftDerMauer.png" alt="">
                        <div>
                            <strong id="nombre-cara">Probá tirar el dado</strong>
                            <p id="efecto-cara">Los recintos en gris son los que no podés usar este turno.</p>
                        </div>
                    </div>
                    <button type="button" class="boton boton-primario" id="btn-tirar-demo" hidden>Tirar el dado</button>
                </div>
                <ul class="caras-dado">
                    <?php foreach ($caras as [$codigo, $imagen, $nombre, $efecto]): ?>
                        <li data-cara="<?= e($codigo) ?>" data-imagen="img/juego/<?= e($imagen) ?>" data-nombre="<?= e($nombre) ?>" data-efecto="<?= e($efecto) ?>">
                            <img src="img/juego/<?= e($imagen) ?>" alt="">
                            <span><strong><?= e($nombre) ?></strong><?= e($efecto) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <p class="nota">El Muro nunca queda bloqueado. Y <strong>quien tira el dado no sufre su restricción</strong>: solo la sufren los demás.</p>
        </section>

        <!-- 6 -->
        <section class="paso caja" data-paso aria-labelledby="t6">
            <p class="etiqueta-paso">Los puntos</p>
            <h2 id="t6">Cómo suma cada recinto</h2>
            <div class="tabla-puntos">
                <div><strong>Oeste y Este</strong><span>1 punto por ficha (hasta 6).</span></div>
                <div><strong>Fábrica y Militares</strong><span>1, 3 y 7 puntos según el orden en que aparece cada tipo distinto. Probalo abajo.</span></div>
                <div><strong>Espías</strong><span>4 puntos si están Markus y Ursula juntos. Uno solo no suma.</span></div>
                <div><strong>Políticos</strong><span>Cada político suma 1 punto esté donde esté. Para ponerlo en otro recinto primero tiene que haber uno en Políticos.</span></div>
                <div><strong>Muro</strong><span>1 punto por ficha. Sirve cuando no te conviene (o no podés) jugar en otro lado.</span></div>
            </div>

            <div class="demo-fabrica caja-interna">
                <h3>Probá la Fábrica</h3>
                <p>Tocá obreros para ponerlos en la Fábrica (3 lugares). El primer tipo que aparece vale 1, el segundo distinto 3 y el tercero 7; si repetís un tipo, vale lo mismo que la primera vez.</p>
                <div class="botones-demo" id="botones-fabrica">
                    <button type="button" class="ficha-demo" data-tipo="Cabo"><img src="img/fichas/ACaboDraftDerMauer.png" alt="">Cabo</button>
                    <button type="button" class="ficha-demo" data-tipo="Oficial Obrera"><img src="img/fichas/OficialMujerDraftDerMauer.png" alt="">Oficial Obrera</button>
                    <button type="button" class="ficha-demo" data-tipo="Peón"><img src="img/fichas/PeonDraftDerMauer.png" alt="">Peón</button>
                </div>
                <ol class="lugares-fabrica" id="lugares-fabrica" aria-live="polite">
                    <li>Vacío</li><li>Vacío</li><li>Vacío</li>
                </ol>
                <p class="total-demo"><span id="total-fabrica">0</span> puntos <button type="button" class="boton boton-chico" id="btn-vaciar-fabrica">Vaciar</button></p>
            </div>
        </section>

        <!-- 7 -->
        <section class="paso caja" data-paso aria-labelledby="t7">
            <p class="etiqueta-paso">Reglas que conviene saber</p>
            <h2 id="t7">Casos especiales</h2>
            <ul class="lista-clave">
                <li>Si <strong>ninguna ficha de tu mano entra</strong> en un recinto permitido, va al Muro.</li>
                <li>Con <strong>Cambiar ficha</strong>, si todavía no tenés fichas en el tablero, solo colocás.</li>
                <li>No podés sacar el <strong>único político de Políticos</strong> si tenés otros políticos afuera.</li>
                <li>El <strong>Muro</strong> tiene 12 lugares y el dado nunca lo bloquea.</li>
                <li>Si te quedás <strong>45 segundos sin conexión</strong> o salís de la partida, cuenta como abandono: <strong>perdés los puntos</strong> de esa partida. Si todos los demás abandonan, ganás con lo que tenías.</li>
            </ul>
        </section>

        <!-- 8 -->
        <section class="paso caja" data-paso aria-labelledby="t8">
            <p class="etiqueta-paso">Listo</p>
            <h2 id="t8">Formas de jugar</h2>
            <div class="modos-juego">
                <div><strong>Partida normal</strong><span>Creá una sala o unite a una. Da puntos de tienda y de ranking.</span></div>
                <div><strong>Con bots</strong><span>Completá la sala con bots para practicar. No da puntos.</span></div>
                <div><strong>Personalizada</strong><span>Cambiá rondas, tiempos y puntajes. Da puntos de tienda, no de ranking.</span></div>
                <div><strong>Competitivo</strong><span>Rivales de tu nivel y reglas oficiales. Sube o baja tu MMR.</span></div>
                <div><strong>Berlín</strong><span>Caminá por la ciudad, elegí bando y conquistá distritos jugando en los cafés.</span></div>
                <div><strong>Tienda y galería</strong><span>Gastá tus puntos en skins y conocé la historia de cada ficha.</span></div>
            </div>
            <p>Si algo no funciona o alguien se porta mal, mandá un reporte desde el inicio o desde la partida.</p>
            <form method="POST">
                <?= SesionSegura::campoCsrf() ?>
                <input type="hidden" name="accion" value="terminar">
                <button type="submit" class="boton boton-primario boton-grande">¡A jugar!</button>
            </form>
        </section>

        <nav class="navegacion-tutorial" id="navegacion-tutorial" aria-label="Pasos del tutorial" hidden>
            <button type="button" class="boton" id="btn-anterior">Anterior</button>
            <div class="puntos-pasos" id="puntos-pasos"></div>
            <button type="button" class="boton boton-primario" id="btn-siguiente">Siguiente</button>
        </nav>
    </main>
    <script src="js/comun.js"></script>
    <script src="js/tutorial.js"></script>
    <script src="js/traduccion.js"></script>
</body>
</html>
