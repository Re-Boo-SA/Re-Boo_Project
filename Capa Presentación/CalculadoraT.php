<?php
session_start();

require_once(__DIR__ . '/../Capa Lógica/FachadaLogica.php');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'jugador') {
    $_SESSION['flash_error'] = 'Acceso restringido: Inicia sesión con tu cuenta de Jugador.';
    header('Location: login.php');
    exit();
} 

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Draft Der Mauer - Calculadora</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Saira+Stencil+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/CalculadoraEstilo.css" />
</head>

<body>

    <header class="encabezado-principal">
        <div class="titulo">
            <h1>DRAFT DER MAUER</h1>
            <p class="subtitulo">CALCULADORA OFICIAL DE PUNTOS</p>
        </div> 
        <div class ="boton-reglamento">
            <a href="reglamento.php" class="boton-reglamento">Reglamento</a>
        </div>
    </header>

    <main class="calculadora-maxima-anashe">

        <aside class="columna-panel-izquierdo">
            <section class="caja-puntuacion-total">
                <div class="titulo-seccion">
                    <h2>PUNTUACIÓN TOTAL</h2>
                </div>
                <div class="marcador-gigante"><?php echo $_SESSION['total']; ?></div>
                <p class="leyenda">PUNTUACIÓN TOTAL</p>
            </section>
            
            <section class="panel-izquierdo-categorias">
                <h3>DESGLOSE POR RECINTO</h3>
                <ul class="lista-sectores">
                    <li><span class="nombre-sector">Oeste</span> <strong id="pts-oeste">0 pts.</strong></li>
                    <li><span class="nombre-sector">Fábrica</span> <strong id="pts-fabrica">0 pts.</strong></li>
                    <li><span class="nombre-sector">Espias</span> <strong id="pts-espias">0 pts.</strong></li>

                    <li><span class="nombre-sector">Politicos</span> <strong id="pts-politicos">0 pts.</strong></li>
                    <li><span class="nombre-sector">Este</span> <strong id="pts-este">0 pts.</strong></li>
                    <li><span class="nombre-sector">Militares</span> <strong id="pts-militares">0 pts.</strong></li>

                    <li class="item-muro"><span class="nombre-sector">El Muro</span> <strong id="pts-muro">0 pts.</strong></li>
                </ul>
            </section>
            <button type="button" class="btn-salir" id="btn-salir">CERRAR CALCULADORA</button>
        </aside>


        <section class="columna-panel-central">
            <div class="tablero-juego">

                <div class="columna-tablero-izq">
                    <article class="recinto-juego" data-sector="OesteR" data-capacidad="6" data-reglas="libre">
                       <header><h4>Oeste</h4><span>Max: 6</span></header>
                       <div class="fichas-colocadas" id="fichas-oeste">
                            <div class="sector-recinto" data-posicion="1"></div>
                            <div class="sector-recinto" data-posicion="2"></div>
                            <div class="sector-recinto" data-posicion="3"></div>
                            <div class="sector-recinto" data-posicion="4"></div>
                            <div class="sector-recinto" data-posicion="5"></div>
                            <div class="sector-recinto" data-posicion="6"></div>
                        </div> 
                    </article>

                    <article class="recinto-juego" data-sector="FabricaR" data-capacidad="3" data-reglas="solo-obreros">
                        <header><h4>Fábrica</h4><span>Max: 3</span></header>
                        <div class="fichas-colocadas" id="fichas-fabrica">
                            <div class="sector-recinto" data-posicion="1"></div>
                            <div class="sector-recinto" data-posicion="2"></div>
                            <div class="sector-recinto" data-posicion="3"></div>
                        </div>
                    </article>
                    
                    <article class="recinto-juego" data-sector="EspiasR" data-capacidad="2" data-reglas="solo-espias">
                        <header><h4>Espias</h4><span>Max: 2</span></header>            
                        <div class="fichas-colocadas" id="fichas-espias">
                            <div class="sector-recinto" data-posicion="1"></div>
                            <div class="sector-recinto" data-posicion="2"></div>
                        </div>
                    </article>
                </div>

                <div class="columna-tablero-muro">
                    <article class="recinto-muro" data-sector="MuroR" data-regla="DiMuro">
                        <h4>Muro Central</h4>
                        <p>Cualquier ficha</p>
                        <div class="fichas-colocadas" id="fichas-muro"></div>
                    </article>
                </div>

                <div class="columna-tablero-der">
                    <article class="recinto-juego" data-sector="PoliticosR" data-capacidad="1" data-reglas="solo-politicos">
                       <header><h4>Politicos</h4><span>Max: 1</span></header>                 
                        <div class="fichas-colocadas" id="fichas-politicos">
                            <div class="sector-recinto" data-posicion="1"></div>
                        </div> 
                    </article>

                    <article class="recinto-juego" data-sector="EsteR" data-capacidad="6" data-reglas="libre">
                        <header><h4>Este</h4><span>Max: 6</span></header>      
                        <div class="fichas-colocadas" id="fichas-este">
                            <div class="sector-recinto" data-posicion="1"></div>
                            <div class="sector-recinto" data-posicion="2"></div>
                            <div class="sector-recinto" data-posicion="3"></div>
                            <div class="sector-recinto" data-posicion="4"></div>
                            <div class="sector-recinto" data-posicion="5"></div>
                            <div class="sector-recinto" data-posicion="6"></div>
                        </div>
                    </article>
                    
                    <article class="recinto-juego" data-sector="MilitaresR" data-capacidad="3" data-reglas="solo-militares">
                        <header><h4>Militares</h4><span>Max: 3</span></header>                  
                        <div class="fichas-colocadas" id="fichas-militares">
                            <div class="sector-recinto" data-posicion="1"></div>
                            <div class="sector-recinto" data-posicion="2"></div>
                            <div class="sector-recinto" data-posicion="3"></div>
                        </div>
                    </article>
                </div>
            </div>

            <section class ="panel-fichas">
                <div class="titulo-seccion">
                    <h3>FICHAS DISPONIBLES</h3>
                    <span class="tag-estado" id="recinto-seleccionado">Selecciona un recinto del tablero</span>
                </div>

                <div class="espacio-fichas">
                    <button type="button" class="btn-ficha" data-ficha="tropa" disabled>
                        <img src="../Imagenes/TropaDraftDerMauer.png" alt="Tropa">
                        <span>Tropa</span>
                    </button>

                    <button type="button" class="btn-ficha" data-ficha="Oficial" disabled>
                        <img src="../Imagenes/OficialDraftDerMauer.png" alt="Oficial Militar">
                        <span>Oficial Militar</span>
                    </button>

                    <button type="button" class="btn-ficha" data-ficha="general" disabled>
                        <img src="../Imagenes/GeneralDraftDerMauer.png" alt="General">
                        <span>General</span>
                    </button>

                    <button type="button" class="btn-ficha" data-ficha="peon" disabled>
                        <img src="../Imagenes/PeonDraftDerMauer.png" alt="Peon">
                        <span>Peon</span>
                    </button>

                    <button type="button" class="btn-ficha" data-ficha="OficialMujer" disabled>
                        <img src="../Imagenes/OficialMujerDraftDerMauer.png" alt="Oficial Obrera">
                        <span>Oficial Obrera</span>
                    </button>

                    <button type="button" class="btn-ficha" data-ficha="cabo" disabled>
                        <img src="../Imagenes/ACaboDraftDerMauer.png" alt="Cabo">
                        <span>Cabo</span>
                    </button>

                    <button type="button" class="btn-ficha" data-ficha="Ursula" disabled>
                        <img src="../Imagenes/UrsulaEspiaDraftDerMauer.png" alt="Ursula Espía">
                        <span>Ursula</span>
                    </button>

                    <button type="button" class="btn-ficha" data-ficha="Markus" disabled>
                        <img src="../Imagenes/MarkusEspiaDraftDerMauer.png" alt="Markus Espía">
                        <span>Markus</span>
                    </button>

                    <button type="button" class="btn-ficha" data-ficha="Politico" disabled>
                        <img src="../Imagenes/PoliticoDraftDerMauer.png" alt="Politico">
                        <span>Politico</span>
                    </button>
                </div>
            </section>
        </section>

        <aside class="columna-panel-derecho">

            <section class="caja-dado">
            <div class="titulo-seccion">
                <h2>DADO</h2>
            </div>

            <button type="button" class="btn-selector-dado" id="btn-abrir-dado">
                <span class="imagen-cara-activa"> 
                    <img src="../Imagenes/CaraVaciaDadoDraftDerMauer.png" alt="Dado sin cara seleccionada" id="imagen-cara-activa"> 
                </span>
                <span class="info-dado">
                    <strong id="nombre-cara-activa">
                     SELECCIONAR LA CARA QUE SALIÓ EN EL DADO
                    </strong> 
                    <small id="sub-cara-activa">Elegí el resultado del dado</small>
                </span>
            </button>

            <div class="opciones-cara" id="menu-caras-dado" hidden>
                <button
                    type="button"
                    class="btn-opcion-cara"
                    data-cara="lado-izquierdo"
                    data-nombre="Bloqueo del lado izquierdo"
                    data-descripcion="No podés colocar fichas en los tres recintos del lado izquierdo."
                    data-imagen="../Imagenes/NoIzquierdoDadoDraftDerMauer.png"
                >
                    <img
                        src="../Imagenes/NoIzquierdoDadoDraftDerMauer.png"
                        alt="Cara del dado: bloqueo del lado izquierdo"
                    >

                    <span class="datos-opcion-cara">
                        <strong>Lado izquierdo</strong>
                        <small>No se puede colocar allí</small>
                    </span>
                </button>

                <button
                    type="button"
                    class="btn-opcion-cara"
                    data-cara="lado-derecho"
                    data-nombre="Bloqueo del lado derecho"
                    data-descripcion="No podés colocar fichas en los tres recintos del lado derecho."
                    data-imagen="../Imagenes/NoDerechoDadoDraftDerMauer.png"
                >
                    <img
                        src="../Imagenes/NoDerechoDadoDraftDerMauer.png"
                        alt="Cara del dado: bloqueo del lado derecho"
                    >

                    <span class="datos-opcion-cara">
                        <strong>Lado derecho</strong>
                        <small>No se puede colocar allí</small>
                    </span>
                </button>

                <button
                    type="button"
                    class="btn-opcion-cara"
                    data-cara="solo-militares"
                    data-nombre="Solo Militares"
                    data-descripcion="Solo se puede colocar en el recinto de Militares, abajo a la derecha."
                    data-imagen="../Imagenes/SoloRecintoMilitaresDadoDraftDerMauer.png"
                >
                    <img
                        src="../Imagenes/SoloRecintoMilitaresDadoDraftDerMauer.png"
                        alt="Cara del dado: solo Militares"
                    >

                    <span class="datos-opcion-cara">
                        <strong>Solo Militares</strong>
                        <small>Recinto inferior derecho</small>
                    </span>
                </button>

                <button
                    type="button"
                    class="btn-opcion-cara"
                    data-cara="bloquea-fabrica"
                    data-nombre="Bloqueo de Fábrica"
                    data-descripcion="No podés colocar fichas en el recinto de Fábrica, ubicado en el medio a la izquierda."
                    data-imagen="../Imagenes/NoFabricaDadoDraftDerMauer.png"
                >
                    <img
                        src="../Imagenes/NoFabricaDadoDraftDerMauer.png"
                        alt="Cara del dado: bloqueo de Fábrica"
                    >

                    <span class="datos-opcion-cara">
                        <strong>Bloqueo de Fábrica</strong>
                        <small>Recinto central izquierdo</small>
                    </span>
                </button>

                <button
                    type="button"
                    class="btn-opcion-cara"
                    data-cara="cambiar-ficha"
                    data-nombre="Cambio de ficha"
                    data-descripcion="Permite cambiar de lugar una ficha del jugador que tiró el dado. La acción corresponde a ese jugador."
                    data-imagen="../Imagenes/ReacomodarFichaDadoDraftDerMauer.png"
                >
                    <img
                        src="../Imagenes/ReacomodarFichaDadoDraftDerMauer.png"
                        alt="Cara del dado: cambiar ficha"
                    >

                    <span class="datos-opcion-cara">
                        <strong>Cambiar ficha</strong>
                        <small>Mover una ficha del jugador</small>
                    </span>
                </button>

                <button
                    type="button"
                    class="btn-opcion-cara"
                    data-cara="solo-espias"
                    data-nombre="Solo Espías"
                    data-descripcion="La colocación queda restringida al recinto de Espías, abajo a la izquierda."
                    data-imagen="../Imagenes/SoloEspiasDadoDraftDerMauer.png"
                >
                    <img
                        src="../Imagenes/SoloEspiasDadoDraftDerMauer.png"
                        alt="Cara del dado: solo Espías"
                    >

                    <span class="datos-opcion-cara">
                        <strong>Solo Espías</strong>
                        <small>Recinto inferior izquierdo</small>
                    </span>
                </button>
            </div>

            <div class="control-turno-dado">

                <input
                    type="checkbox"
                    id="es-mi-turno-dado"
                >

                <label for="es-mi-turno-dado">
                    Es mi turno de tirar el dado
                </label>

            </div>

            </section>

            <section class="caja-explicacion">

                <h3>ⓘ EFECTO Y EXPLICACIÓN</h3>

                <div class="caja-texto-efecto" id="texto-efecto-dado">
                    <h4>Sin cara seleccionada</h4>
                    <p>
                        Seleccioná la cara que salió en el dado físico para consultar su efecto y las restricciones correspondientes.
                    </p>
                </div>
            </section>

            <section class="caja-turnos">
                <h3>PROGRESO DE LA PARTIDA</h3>
                <div class="control-ronda">
                    <span>RONDA ACTUAL</span>
                    <strong id="ronda-val">
                        Ronda: 1 / 2
                    </strong>
                </div>

                <div class="control-turno">
                    <span>TURNO ACTUAL</span>
                    <strong id="turno-val">
                        Turno: 1 / 6
                    </strong>
                </div> 

                <div id="barra-turno">
                    <div id="barra-turno-fill" style="width: 0%;"></div>
                </div>

                <button type="button" class="btn-siguiente-turno" id="btn-siguiente-turno">SIGUIENTE TURNO</button>
            </section>
        </aside>
    </main>

    <div id="modal-puntuacion-final" class="modal-puntuacion" hidden>

        <section class="contenedor-modal-puntuacion">

            <header class="encabezado-puntuacion-final">
                <h2 id="titulo-puntuacion-final">PUNTUACIÓN FINAL</h2>
                <p>Resumen de tu partida</p>
            </header>

            <section class="resumen-puntuacion-final">
                <h3>PUNTUACIÓN TOTAL FINAL</h3>
                <p>
                    <strong id="total-puntuacion-final">0</strong>
                    <span>puntos</span>
                </p>
            </section>

            <section class="detalle-puntuacion-final">
                <h3>POR RECINTO</h3>

                <ul id="lista-puntos-por-recinto">
                    <li>
                        <span>Oeste</span>
                        <strong id="final-pts-oeste">0 pts</strong>
                    </li>
                    <li>
                        <span>Fábrica</span>
                        <strong id="final-pts-fabrica">0 pts</strong>
                    </li>
                    <li>
                        <span>Espías</span>
                        <strong id="final-pts-espias">0 pts</strong>
                    </li>
                    <li>
                        <span>Políticos</span>
                        <strong id="final-pts-politicos">0 pts</strong>
                    </li>
                    <li>
                        <span>Este</span>
                        <strong id="final-pts-este">0 pts</strong>
                    </li>
                    <li>
                        <span>Militares</span>
                        <strong id="final-pts-militares">0 pts</strong>
                    </li>
                    <li>
                        <span>Muro central</span>
                        <strong id="final-pts-muro">0 pts</strong>
                    </li>
                </ul>
            </section>

            <section class="detalle-fichas-final">
                <h3>POR FICHA EN RECINTO</h3>

                <div id="lista-puntos-por-ficha">
                    <p class="mensaje-sin-puntuacion">
                        El detalle de las fichas aparecerá al finalizar la partida.
                    </p>
                </div>
            </section>

            <footer class="pie-modal-puntuacion">
                <button type="button" id="btn-cerrar-calculadora">CERRAR CALCULADORA</button>
            </footer>

        </section>
    </div>
    <script src="js/app.js"></script>
</body>
</html>
