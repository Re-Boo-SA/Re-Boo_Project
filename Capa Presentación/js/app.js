/* ======================================================================
   DRAFT DER MAUER - CALCULADORA
   Versión para principiantes: variables claras, arrays, if/else y for.

   IMPORTANTE:
   - El dado se tira físicamente. Aquí se elige la cara que salió.
   - Se coloca una ficha por turno, o se mueve una ficha si corresponde.
   - El progreso se guarda en localStorage.
   ====================================================================== */

"use strict";

/* ----------------------------------------------------------------------
   1. DATOS GENERALES DEL JUEGO
   ---------------------------------------------------------------------- */

var CLAVE_GUARDADO = "draftDerMauer.calculadora.v1";
var TOTAL_RONDAS = 2;
var TURNOS_POR_RONDA = 6;
var CAPACIDAD_MURO = 12; // Capacidad de reserva para el muro central.

/* Cada ficha tiene nombre, familia e imagen. Los identificadores coinciden
   con los valores data-ficha escritos en el HTML. */
var FICHAS = {
    tropa: { nombre: "Tropa", familia: "militares", imagen: "../Imagenes/TropaDraftDerMauer.png" },
    Oficial: { nombre: "Oficial Militar", familia: "militares", imagen: "../Imagenes/OficialDraftDerMauer.png" },
    general: { nombre: "General", familia: "militares", imagen: "../Imagenes/GeneralDraftDerMauer.png" },
    peon: { nombre: "Peón", familia: "obreros", imagen: "../Imagenes/PeonDraftDerMauer.png" },
    OficialMujer: { nombre: "Oficial Obrera", familia: "obreros", imagen: "../Imagenes/OficialMujerDraftDerMauer.png" },
    cabo: { nombre: "Cabo", familia: "obreros", imagen: "../Imagenes/ACaboDraftDerMauer.png" },
    Ursula: { nombre: "Ursula", familia: "espias", imagen: "../Imagenes/UrsulaEspiaDraftDerMauer.png" },
    Markus: { nombre: "Markus", familia: "espias", imagen: "../Imagenes/MarkusEspiaDraftDerMauer.png" },
    Politico: { nombre: "Político", familia: "politicos", imagen: "../Imagenes/PoliticoDraftDerMauer.png" }
};

/* Los recintos tienen un identificador, un nombre, el ID de su contenedor,
   una capacidad y el lado del tablero al que pertenecen. */
var RECINTOS = {
    OesteR: { nombre: "Oeste", contenedor: "fichas-oeste", capacidad: 6, lado: "izquierdo", regla: "libre" },
    FabricaR: { nombre: "Fábrica", contenedor: "fichas-fabrica", capacidad: 3, lado: "izquierdo", regla: "solo-obreros" },
    EspiasR: { nombre: "Espías", contenedor: "fichas-espias", capacidad: 2, lado: "izquierdo", regla: "solo-espias" },
    PoliticosR: { nombre: "Políticos", contenedor: "fichas-politicos", capacidad: 1, lado: "derecho", regla: "solo-politicos" },
    EsteR: { nombre: "Este", contenedor: "fichas-este", capacidad: 6, lado: "derecho", regla: "libre" },
    MilitaresR: { nombre: "Militares", contenedor: "fichas-militares", capacidad: 3, lado: "derecho", regla: "solo-militares" },
    MuroR: { nombre: "Muro central", contenedor: "fichas-muro", capacidad: CAPACIDAD_MURO, lado: "centro", regla: "libre" }
};

/* Información de las caras del dado. Las imágenes son las que usa el HTML. */
var CARAS_DADO = {
    "lado-izquierdo": {
        nombre: "Bloqueo del lado izquierdo",
        descripcion: "Si otra persona tiró el dado, no podés colocar en Oeste, Fábrica ni Espías.",
        imagen: "../Imagenes/NoIzquierdoDadoDraftDerMauer.png"
    },
    "lado-derecho": {
        nombre: "Bloqueo del lado derecho",
        descripcion: "Si otra persona tiró el dado, no podés colocar en Políticos, Este ni Militares.",
        imagen: "../Imagenes/NoDerechoDadoDraftDerMauer.png"
    },
    "solo-militares": {
        nombre: "Solo Militares",
        descripcion: "Si otra persona tiró el dado, solo podés colocar en Militares.",
        imagen: "../Imagenes/SoloRecintoMilitaresDadoDraftDerMauer.png"
    },
    "bloquea-fabrica": {
        nombre: "Bloqueo de Fábrica",
        descripcion: "Si otra persona tiró el dado, no podés colocar en Fábrica.",
        imagen: "../Imagenes/NoFabricaDadoDraftDerMauer.png"
    },
    "cambiar-ficha": {
        nombre: "Cambio de ficha",
        descripcion: "Si vos tiraste el dado, tenés que mover una ficha ya colocada. Si tiró otra persona, jugás normalmente.",
        imagen: "../Imagenes/ReacomodarFichaDadoDraftDerMauer.png"
    },
    "solo-espias": {
        nombre: "Solo Espías",
        descripcion: "Si otra persona tiró el dado, solo podés colocar en Espías.",
        imagen: "../Imagenes/SoloEspiasDadoDraftDerMauer.png"
    }
};

/* Estado de la partida. Cada ficha colocada tiene también ronda y turno,
   para saber si todavía se puede corregir con la X. */
function crearPartidaNueva() {
    return {
        ronda: 1,
        turno: 1,
        caraDado: "",
        esMiTurnoDado: false,
        accionRealizada: false,
        movioFicha: false,
        colocoFichaNueva: false,
        recintoSeleccionado: "",
        fichaAMover: "",
        partidaFinalizada: false,
        politicoYaColocadoEnPoliticos: false,
        colocaciones: []
    };
}

var estado = crearPartidaNueva();

/* ----------------------------------------------------------------------
   2. ELEMENTOS DE LA PÁGINA
   ---------------------------------------------------------------------- */

var recintosHTML = document.querySelectorAll(".recinto-juego, .recinto-muro");
var botonesFicha = document.querySelectorAll(".btn-ficha");
var botonesCaras = document.querySelectorAll(".btn-opcion-cara");
var botonAbrirDado = document.getElementById("btn-abrir-dado");
var menuCaras = document.getElementById("menu-caras-dado");
var imagenCaraActiva = document.getElementById("imagen-cara-activa");
var nombreCaraActiva = document.getElementById("nombre-cara-activa");
var subCaraActiva = document.getElementById("sub-cara-activa");
var textoEfectoDado = document.getElementById("texto-efecto-dado");
var casillaMiTurnoDado = document.getElementById("es-mi-turno-dado");
var textoRonda = document.getElementById("ronda-val");
var textoTurno = document.getElementById("turno-val");
var barraTurno = document.getElementById("barra-turno");
var rellenoBarra = document.getElementById("barra-turno-fill");
var botonSiguiente = document.getElementById("btn-siguiente-turno");
var botonSalir = document.getElementById("btn-salir");
var modalFinal = document.getElementById("modal-puntuacion-final");
var botonCerrarCalculadora = document.getElementById("btn-cerrar-calculadora");
var etiquetaRecinto = document.getElementById("recinto-seleccionado");
var marcadorGigante = document.querySelector(".marcador-gigante");

/* ----------------------------------------------------------------------
   3. FUNCIONES BÁSICAS PARA BUSCAR Y GUARDAR FICHAS
   ---------------------------------------------------------------------- */

function obtenerRecinto(idRecinto) {
    return RECINTOS[idRecinto];
}

function buscarColocacionPorId(idColocacion) {
    var i;
    for (i = 0; i < estado.colocaciones.length; i++) {
        if (estado.colocaciones[i].id === idColocacion) {
            return estado.colocaciones[i];
        }
    }
    return null;
}

function buscarFichaEnEspacio(idRecinto, posicion) {
    var i;
    for (i = 0; i < estado.colocaciones.length; i++) {
        if (estado.colocaciones[i].recinto === idRecinto && estado.colocaciones[i].posicion === posicion) {
            return estado.colocaciones[i];
        }
    }
    return null;
}

function contarFichasEnRecinto(idRecinto) {
    var cantidad = 0;
    var i;
    for (i = 0; i < estado.colocaciones.length; i++) {
        if (estado.colocaciones[i].recinto === idRecinto) {
            cantidad++;
        }
    }
    return cantidad;
}

function hayPoliticoEnSuRecinto() {
    /* La regla se cumple una vez que ya se colocó allí un político.
       Aunque después se mueva, el requisito histórico sigue cumplido. */
    return estado.politicoYaColocadoEnPoliticos;
}

function mostrarAviso(mensaje) {
    if (etiquetaRecinto) {
        etiquetaRecinto.textContent = mensaje;
    }
    console.log(mensaje);
}

/* Guarda toda la partida como texto. Si el navegador no permite guardar,
   la calculadora sigue funcionando mientras la página permanezca abierta. */
function guardarPartida() {
    try {
        localStorage.setItem(CLAVE_GUARDADO, JSON.stringify(estado));
    } catch (error) {
        console.log("No se pudo guardar la partida en este navegador.");
    }
}

/* Recupera una partida guardada anteriormente. */
function cargarPartida() {
    var textoGuardado;
    var partidaGuardada;
    var i;

    try {
        textoGuardado = localStorage.getItem(CLAVE_GUARDADO);
        if (textoGuardado === null) {
            return;
        }

        partidaGuardada = JSON.parse(textoGuardado);
        if (!partidaGuardada || !Array.isArray(partidaGuardada.colocaciones)) {
            return;
        }

        estado = crearPartidaNueva();
        estado.ronda = partidaGuardada.ronda || 1;
        estado.turno = partidaGuardada.turno || 1;
        estado.caraDado = partidaGuardada.caraDado || "";
        estado.esMiTurnoDado = partidaGuardada.esMiTurnoDado === true;
        estado.accionRealizada = partidaGuardada.accionRealizada === true;
        estado.recintoSeleccionado = partidaGuardada.recintoSeleccionado || "";
        estado.fichaAMover = partidaGuardada.fichaAMover || "";
        estado.partidaFinalizada = partidaGuardada.partidaFinalizada === true;
        estado.politicoYaColocadoEnPoliticos = partidaGuardada.politicoYaColocadoEnPoliticos === true;
        estado.colocaciones = partidaGuardada.colocaciones;

        /* Añade un ID y turno seguro a fichas de partidas antiguas si faltaban. */
        for (i = 0; i < estado.colocaciones.length; i++) {
            if (!estado.colocaciones[i].id) {
                estado.colocaciones[i].id = "ficha-recuperada-" + i + "-" + new Date().getTime();
            }
            if (typeof estado.colocaciones[i].ronda !== "number") {
                estado.colocaciones[i].ronda = 0;
            }
            if (typeof estado.colocaciones[i].turno !== "number") {
                estado.colocaciones[i].turno = 0;
            }
        }

        /* En partidas guardadas antiguas, reconstruimos este dato desde las fichas. */
        for (i = 0; i < estado.colocaciones.length; i++) {
            if (estado.colocaciones[i].recinto === "PoliticosR" && estado.colocaciones[i].ficha === "Politico") {
                estado.politicoYaColocadoEnPoliticos = true;
            }
        }

        /* Si la ficha que se iba a mover ya no existe, cancelamos ese movimiento. */
        if (estado.fichaAMover && !buscarColocacionPorId(estado.fichaAMover)) {
            estado.fichaAMover = "";
        }
    } catch (error) {
        estado = crearPartidaNueva();
        console.log("El guardado estaba dañado. Se inició una partida nueva.");
    }
}

/* ----------------------------------------------------------------------
   4. CREAR LOS ESPACIOS DEL TABLERO
   ---------------------------------------------------------------------- */

function prepararEspacios() {
    var idRecinto;
    var recinto;
    var contenedor;
    var espacios;
    var i;
    var boton;

    for (idRecinto in RECINTOS) {
        recinto = RECINTOS[idRecinto];
        contenedor = document.getElementById(recinto.contenedor);
        if (!contenedor) {
            continue;
        }

        espacios = contenedor.querySelectorAll(".sector-recinto");

        /* Si el HTML no tiene todos los espacios, los creamos. */
        for (i = espacios.length + 1; i <= recinto.capacidad; i++) {
            boton = document.createElement("button");
            boton.type = "button";
            boton.className = "sector-recinto";
            boton.dataset.posicion = String(i);
            boton.dataset.recinto = idRecinto;
            boton.setAttribute("aria-label", recinto.nombre + ", espacio " + i);
            contenedor.appendChild(boton);
        }

        /* Añadimos los datos a espacios que ya estaban escritos en el HTML. */
        espacios = contenedor.querySelectorAll(".sector-recinto");
        for (i = 0; i < espacios.length; i++) {
            espacios[i].dataset.recinto = idRecinto;
            if (!espacios[i].dataset.posicion) {
                espacios[i].dataset.posicion = String(i + 1);
            }
        }
    }
}

/* ----------------------------------------------------------------------
   5. REGLAS DE RECINTOS Y DADO
   ---------------------------------------------------------------------- */

function dadoPermiteRecinto(idRecinto) {
    var recinto = obtenerRecinto(idRecinto);

    if (!estado.caraDado || !recinto) {
        return false;
    }

    /* Las restricciones del dado solo afectan al jugador si tiró otra persona. */
    if (estado.esMiTurnoDado) {
        return true;
    }

    if (estado.caraDado === "lado-izquierdo" && recinto.lado === "izquierdo") {
        return false;
    }
    if (estado.caraDado === "lado-derecho" && recinto.lado === "derecho") {
        return false;
    }
    if (estado.caraDado === "solo-militares" && idRecinto !== "MilitaresR" && idRecinto !== "MuroR") {
        return false;
    }
    if (estado.caraDado === "bloquea-fabrica" && idRecinto === "FabricaR" && idRecinto !== "MuroR") {
        return false;
    }
    if (estado.caraDado === "solo-espias" && idRecinto !== "EspiasR" && idRecinto !== "MuroR") {
        return false;
    }

    return true;
}

function fichaPuedeIrAlRecinto(idFicha, idRecinto) {
    var ficha = FICHAS[idFicha];
    var recinto = RECINTOS[idRecinto];

    if (!ficha || !recinto) {
        return false;
    }

    /* Un político solo puede salir del recinto Políticos si ya hay uno allí. */
    if (ficha.familia === "politicos") {
        if (idRecinto !== "PoliticosR" && !hayPoliticoEnSuRecinto()) {
            return false;
        }
        return true;
    }

    /* Los demás tipos respetan la familia normal del recinto. */
    if (recinto.regla === "solo-obreros" && ficha.familia !== "obreros") {
        return false;
    }
    if (recinto.regla === "solo-militares" && ficha.familia !== "militares") {
        return false;
    }
    if (recinto.regla === "solo-espias" && ficha.familia !== "espias") {
        return false;
    }
    if (recinto.regla === "solo-politicos" && ficha.familia !== "politicos") {
        return false;
    }

    return true;
}

function buscarEspacioLibre(idRecinto, idFichaQueSeMueve) {
    var recinto = RECINTOS[idRecinto];
    var contenedor = document.getElementById(recinto.contenedor);
    var espacios = contenedor.querySelectorAll(".sector-recinto");
    var i;
    var posicion;
    var ocupacion;

    for (i = 0; i < espacios.length; i++) {
        posicion = Number(espacios[i].dataset.posicion);
        ocupacion = buscarFichaEnEspacio(idRecinto, posicion);

        /* Al mover una ficha, su propio espacio no cuenta como ocupado por otra. */
        if (!ocupacion || (idFichaQueSeMueve && ocupacion.id === idFichaQueSeMueve)) {
            return posicion;
        }
    }

    return 0;
}

/* ----------------------------------------------------------------------
   6. DIBUJAR EL TABLERO Y LAS FICHAS DISPONIBLES
   ---------------------------------------------------------------------- */

function dibujarTablero() {
    var i;
    var j;
    var recintoHTML;
    var espacios;
    var espacio;
    var idRecinto;
    var posicion;
    var colocacion;
    var ficha;
    var imagen;
    var nombre;
    var botonQuitar;
    var fichaEsDelTurno;

    for (i = 0; i < recintosHTML.length; i++) {
        recintoHTML = recintosHTML[i];
        recintoHTML.classList.remove("seleccionado");
        if (recintoHTML.dataset.sector === estado.recintoSeleccionado) {
            recintoHTML.classList.add("seleccionado");
        }
    }

    espacios = document.querySelectorAll(".sector-recinto");
    for (i = 0; i < espacios.length; i++) {
        espacio = espacios[i];
        espacio.innerHTML = "";
        espacio.classList.remove("ocupado", "seleccionado", "ficha-seleccionada");

        idRecinto = espacio.dataset.recinto;
        posicion = Number(espacio.dataset.posicion);
        colocacion = buscarFichaEnEspacio(idRecinto, posicion);

        if (colocacion) {
            ficha = FICHAS[colocacion.ficha];
            imagen = document.createElement("img");
            imagen.src = ficha.imagen;
            imagen.alt = ficha.nombre;
            imagen.draggable = false;

            nombre = document.createElement("span");
            nombre.className = "nombre-ficha-colocada";
            nombre.textContent = ficha.nombre;

            espacio.appendChild(imagen);
            espacio.appendChild(nombre);
            espacio.classList.add("ocupado");
            espacio.dataset.ficha = colocacion.ficha;
            espacio.dataset.colocacionId = colocacion.id;

            if (estado.fichaAMover === colocacion.id) {
                espacio.classList.add("ficha-seleccionada");
            }

            /* La X aparece únicamente en la ficha del turno actual. */
            fichaEsDelTurno = colocacion.ronda === estado.ronda && colocacion.turno === estado.turno;
            if (fichaEsDelTurno && !estado.partidaFinalizada) {
                botonQuitar = document.createElement("span");
                botonQuitar.className = "btn-quitar-ficha";
                botonQuitar.textContent = "×";
                botonQuitar.setAttribute("role", "button");
                botonQuitar.setAttribute("tabindex", "0");
                botonQuitar.setAttribute("aria-label", "Quitar " + ficha.nombre + " del turno actual");
                botonQuitar.dataset.quitarColocacion = colocacion.id;
                espacio.appendChild(botonQuitar);
            }
        } else {
            delete espacio.dataset.ficha;
            delete espacio.dataset.colocacionId;
            espacio.textContent = String(posicion);
        }

        if (estado.recintoSeleccionado === idRecinto) {
            espacio.classList.add("seleccionado");
        }
    }
}

function actualizarFichasDisponibles() {
    var i;
    var boton;
    var idFicha;
    var permitida;

    for (i = 0; i < botonesFicha.length; i++) {
        boton = botonesFicha[i];
        idFicha = boton.dataset.ficha;
        permitida = false;

        if (estado.recintoSeleccionado && !estado.accionRealizada && !estado.fichaAMover && !estado.partidaFinalizada) {
            permitida = fichaPuedeIrAlRecinto(idFicha, estado.recintoSeleccionado);
            if (!dadoPermiteRecinto(estado.recintoSeleccionado)) {
                permitida = false;
            }
            if (estado.caraDado === "cambiar-ficha" && estado.esMiTurnoDado) {
                permitida = false;
            }
        }

        boton.disabled = !permitida;
        boton.classList.toggle("ficha-permitida", permitida);
        boton.classList.toggle("ficha-no-permitida", !permitida);
    }
}

/* ----------------------------------------------------------------------
   7. SELECCIONAR RECINTO, COLOCAR, QUITAR Y MOVER FICHAS
   ---------------------------------------------------------------------- */

function seleccionarRecinto(idRecinto) {
    var recinto = RECINTOS[idRecinto];

    if (!recinto) {
        return;
    }
    if (!estado.caraDado) {
        mostrarAviso("Primero seleccioná la cara que salió en el dado físico.");
        return;
    }
    if (!dadoPermiteRecinto(idRecinto)) {
        mostrarAviso("El dado no permite colocar en " + recinto.nombre + ".");
        return;
    }

    /* Si ya elegimos una ficha para mover, este recinto será su destino. */
    if (estado.fichaAMover) {
        moverFichaAlRecinto(idRecinto);
        return;
    }

    estado.recintoSeleccionado = idRecinto;
    if (etiquetaRecinto) {
        etiquetaRecinto.textContent = "Recinto seleccionado: " + recinto.nombre;
    }
    actualizarPantalla();
    guardarPartida();
}

function colocarFicha(idFicha) {
    var idRecinto = estado.recintoSeleccionado;
    var posicion;
    var nuevaFicha;

    if (!idRecinto) {
        mostrarAviso("Seleccioná primero un recinto del tablero.");
        return;
    }
    if (!estado.caraDado) {
        mostrarAviso("Primero seleccioná la cara del dado.");
        return;
    }
    if (estado.partidaFinalizada) {
        mostrarAviso("La partida ya terminó.");
        return;
    }
    if (estado.accionRealizada) {
        mostrarAviso("Ya hiciste la acción de este turno. Podés corregirla con la X.");
        return;
    }
    if (estado.caraDado === "cambiar-ficha" && estado.esMiTurnoDado) {
        if (!estado.movioFicha) {
            mostrarAviso("Primero tenés que mover una ficha que ya esté colocada.");
            return;
        }

        if (estado.colocoFichaNueva) {
            mostrarAviso("Ya moviste una ficha y colocaste una nueva durante este turno.");
            return;
        }
    }
    if (!dadoPermiteRecinto(idRecinto)) {
        mostrarAviso("El dado no permite colocar en ese recinto.");
        return;
    }
    if (!fichaPuedeIrAlRecinto(idFicha, idRecinto)) {
        if (idFicha === "Politico" && !hayPoliticoEnSuRecinto() && idRecinto !== "PoliticosR") {
            mostrarAviso("Primero tenés que colocar un político en el recinto Políticos.");
        } else {
            mostrarAviso("Esa ficha no puede colocarse en ese recinto.");
        }
        return;
    }
    if (contarFichasEnRecinto(idRecinto) >= RECINTOS[idRecinto].capacidad) {
        mostrarAviso(RECINTOS[idRecinto].nombre + " está completo.");
        return;
    }

    posicion = buscarEspacioLibre(idRecinto, "");
    if (posicion === 0) {
        mostrarAviso("No hay un espacio libre en ese recinto.");
        return;
    }

    /* Registramos una nueva colocación. Se pueden repetir tipos de ficha. */
    nuevaFicha = {
        id: "ficha-" + new Date().getTime() + "-" + Math.floor(Math.random() * 1000000),
        ficha: idFicha,
        recinto: idRecinto,
        posicion: posicion,
        ronda: estado.ronda,
        turno: estado.turno
    };
    estado.colocaciones.push(nuevaFicha);

    /* Recordamos que ya se cumplió el requisito de colocar un político
       en su recinto, incluso si más adelante se mueve esa ficha. */
    if (idFicha === "Politico" && idRecinto === "PoliticosR") {
        estado.politicoYaColocadoEnPoliticos = true;
    }

    estado.accionRealizada = true;
    estado.fichaAMover = "";

    if (estado.caraDado === "cambiar-ficha" && estado.esMiTurnoDado) {
        estado.colocoFichaNueva = true;
    }

    actualizarPantalla();
    guardarPartida();
}

function quitarFichaDelTurno(idColocacion) {
    var i;
    var ficha;

    if (estado.partidaFinalizada) {
        mostrarAviso("La partida ya terminó.");
        return;
    }

    for (i = 0; i < estado.colocaciones.length; i++) {
        if (estado.colocaciones[i].id === idColocacion) {
            ficha = estado.colocaciones[i];
            break;
        }
    }

    if (!ficha) {
        return;
    }
    if (ficha.ronda !== estado.ronda || ficha.turno !== estado.turno) {
        mostrarAviso("Solo podés corregir la ficha colocada durante el turno actual.");
        return;
    }

    estado.colocaciones.splice(i, 1);
    estado.accionRealizada = false;
    estado.recintoSeleccionado = ficha.recinto;
    if (etiquetaRecinto) {
        etiquetaRecinto.textContent = "Elegí la ficha correcta para " + RECINTOS[ficha.recinto].nombre;
    }

    actualizarPantalla();
    guardarPartida();
}

function comenzarMovimiento(idColocacion) {
    var ficha = buscarColocacionPorId(idColocacion);

    if (estado.caraDado !== "cambiar-ficha" || !estado.esMiTurnoDado) {
        mostrarAviso("Solo podés mover una ficha cuando salió Cambio de ficha y vos tiraste el dado.");
        return;
    }
    if (estado.movioFicha) {
        mostrarAviso("Ya moviste una ficha durante este turno.");
        return;
    }
    if (!ficha) {
        mostrarAviso("No se encontró esa ficha.");
        return;
    }

    estado.fichaAMover = idColocacion;
    estado.recintoSeleccionado = "";
    if (etiquetaRecinto) {
        etiquetaRecinto.textContent = "Elegiste " + FICHAS[ficha.ficha].nombre + ". Ahora seleccioná el recinto de destino.";
    }
    actualizarPantalla();
    guardarPartida();
}

function moverFichaAlRecinto(idRecintoDestino) {
    var ficha = buscarColocacionPorId(estado.fichaAMover);
    var nuevaPosicion;

    if (!ficha) {
        estado.fichaAMover = "";
        mostrarAviso("No se encontró la ficha que querías mover.");
        return;
    }
    if (ficha.recinto === idRecintoDestino) {
        mostrarAviso("Elegí un recinto diferente para mover la ficha.");
        return;
    }
    if (!dadoPermiteRecinto(idRecintoDestino)) {
        mostrarAviso("El dado no permite mover la ficha a ese recinto.");
        return;
    }
    if (!fichaPuedeIrAlRecinto(ficha.ficha, idRecintoDestino)) {
        mostrarAviso("Esa ficha no puede colocarse en " + RECINTOS[idRecintoDestino].nombre + ".");
        return;
    }
    if (contarFichasEnRecinto(idRecintoDestino) >= RECINTOS[idRecintoDestino].capacidad && ficha.recinto !== idRecintoDestino) {
        mostrarAviso(RECINTOS[idRecintoDestino].nombre + " está completo.");
        return;
    }

    nuevaPosicion = buscarEspacioLibre(idRecintoDestino, ficha.id);
    if (nuevaPosicion === 0) {
        mostrarAviso("No hay espacio libre en el recinto de destino.");
        return;
    }

    ficha.recinto = idRecintoDestino;
    ficha.posicion = nuevaPosicion;
    estado.fichaAMover = "";
    estado.recintoSeleccionado = idRecintoDestino;
    estado.movioFicha = true;
    estado.accionRealizada = false;

    if (etiquetaRecinto) {
        etiquetaRecinto.textContent = "Ficha movida a " + RECINTOS[idRecintoDestino].nombre + ". Ahora elegí una ficha nueva para colocar.";
    }
    actualizarPantalla();
    guardarPartida();
}

/* ----------------------------------------------------------------------
   8. SELECCIÓN DEL DADO Y CONTROL DE TURNOS
   ---------------------------------------------------------------------- */

function seleccionarCara(idCara) {
    var cara = CARAS_DADO[idCara];
    var titulo;
    var descripcion;

    if (!cara || estado.partidaFinalizada) {
        return;
    }

    estado.caraDado = idCara;
    estado.recintoSeleccionado = "";
    estado.fichaAMover = "";

    if (imagenCaraActiva) {
        imagenCaraActiva.src = cara.imagen;
        imagenCaraActiva.alt = "Cara del dado: " + cara.nombre;
    }
    if (nombreCaraActiva) {
        nombreCaraActiva.textContent = cara.nombre;
    }
    if (subCaraActiva) {
        subCaraActiva.textContent = "Cara seleccionada del dado físico";
    }
    if (textoEfectoDado) {
        textoEfectoDado.innerHTML = "";
        titulo = document.createElement("h4");
        titulo.textContent = cara.nombre;
        descripcion = document.createElement("p");
        descripcion.textContent = cara.descripcion;
        textoEfectoDado.appendChild(titulo);
        textoEfectoDado.appendChild(descripcion);
    }
    if (menuCaras) {
        menuCaras.hidden = true;
    }
    if (etiquetaRecinto) {
        etiquetaRecinto.textContent = "Ahora seleccioná un recinto permitido.";
    }

    actualizarPantalla();
    guardarPartida();
}

function actualizarProgreso() {
    var turnosTerminados;
    var porcentaje;

    if (textoRonda) {
        textoRonda.textContent = "Ronda: " + estado.ronda + " / " + TOTAL_RONDAS;
    }
    if (textoTurno) {
        textoTurno.textContent = "Turno: " + estado.turno + " / " + TURNOS_POR_RONDA;
    }

    turnosTerminados = (estado.ronda - 1) * TURNOS_POR_RONDA + (estado.turno - 1);
    porcentaje = Math.round(turnosTerminados * 100 / (TOTAL_RONDAS * TURNOS_POR_RONDA));

    if (rellenoBarra) {
        if (estado.partidaFinalizada) {
            rellenoBarra.style.width = "100%";
        } else {
            rellenoBarra.style.width = porcentaje + "%";
        }
    }

    if (barraTurno && rellenoBarra && rellenoBarra.parentNode !== barraTurno) {
        barraTurno.appendChild(rellenoBarra);
    }

    if (botonSiguiente) {
        if (estado.ronda === TOTAL_RONDAS && estado.turno === TURNOS_POR_RONDA) {
            botonSiguiente.textContent = "PUNTUACIÓN FINAL";
        } else if (estado.turno === TURNOS_POR_RONDA) {
            botonSiguiente.textContent = "SIGUIENTE RONDA";
        } else {
            botonSiguiente.textContent = "SIGUIENTE TURNO";
        }
        botonSiguiente.disabled = estado.partidaFinalizada || !estado.caraDado || !estado.accionRealizada || estado.fichaAMover !== "";
    }
}

function avanzarTurno() {
    if (estado.partidaFinalizada) {
        return;
    }
    if (!estado.caraDado) {
        mostrarAviso("Elegí la cara del dado antes de avanzar.");
        return;
    }
    if (!estado.accionRealizada) {
        mostrarAviso("Primero tenés que colocar o mover una ficha.");
        return;
    }
    if (estado.fichaAMover) {
        mostrarAviso("Primero elegí el destino de la ficha que estás moviendo.");
        return;
    }

    if (estado.turno < TURNOS_POR_RONDA) {
        estado.turno++;
    } else if (estado.ronda < TOTAL_RONDAS) {
        estado.ronda++;
        estado.turno = 1;
    } else {
        estado.partidaFinalizada = true;
        mostrarPuntuacionFinal();
    }

    estado.caraDado = "";
    estado.esMiTurnoDado = false;
    estado.accionRealizada = false;
    estado.recintoSeleccionado = "";
    estado.fichaAMover = "";

    if (casillaMiTurnoDado) {
        casillaMiTurnoDado.checked = false;
    }
    if (imagenCaraActiva) {
        imagenCaraActiva.src = "../Imagenes/CaraVaciaDadoDraftDerMauer.png";
        imagenCaraActiva.alt = "Dado sin cara seleccionada";
    }
    if (nombreCaraActiva) {
        nombreCaraActiva.textContent = "Seleccionar la cara que salió en el dado";
    }
    if (subCaraActiva) {
        subCaraActiva.textContent = "Elegí el resultado del dado físico";
    }
    if (textoEfectoDado) {
        textoEfectoDado.innerHTML = "<h4>Sin cara seleccionada</h4><p>Seleccioná la cara que salió en el dado físico para consultar su efecto.</p>";
    }
    if (etiquetaRecinto) {
        etiquetaRecinto.textContent = "Seleccioná un recinto del tablero";
    }

    actualizarPantalla();
    guardarPartida();
}

/* ----------------------------------------------------------------------
   9. PUNTUACIÓN
   ---------------------------------------------------------------------- */

/* Calcula qué valor recibe cada tipo diferente en Fábrica o Militares.
   El orden es el de los espacios: izquierda a derecha. Las repeticiones
   conservan el valor asignado la primera vez que apareció su tipo. */
function obtenerPuntosPorTipo(idRecinto, tiposPermitidos) {
    var valores = {};
    var valoresPosibles = [1, 3, 7];
    var cantidadTipos = 0;
    var fichasRecinto = [];
    var i;
    var j;
    var temporal;
    var tipo;
    var encontrado;

    for (i = 0; i < estado.colocaciones.length; i++) {
        if (estado.colocaciones[i].recinto === idRecinto) {
            fichasRecinto.push(estado.colocaciones[i]);
        }
    }

    /* Ordenamos la lista por posición usando un método simple de comparación. */
    for (i = 0; i < fichasRecinto.length; i++) {
        for (j = i + 1; j < fichasRecinto.length; j++) {
            if (fichasRecinto[j].posicion < fichasRecinto[i].posicion) {
                temporal = fichasRecinto[i];
                fichasRecinto[i] = fichasRecinto[j];
                fichasRecinto[j] = temporal;
            }
        }
    }

    for (i = 0; i < fichasRecinto.length; i++) {
        tipo = fichasRecinto[i].ficha;
        encontrado = false;

        for (j = 0; j < tiposPermitidos.length; j++) {
            if (tiposPermitidos[j] === tipo) {
                encontrado = true;
            }
        }

        if (encontrado && typeof valores[tipo] === "undefined" && cantidadTipos < 3) {
            valores[tipo] = valoresPosibles[cantidadTipos];
            cantidadTipos++;
        }
    }

    return valores;
}

function calcularPuntuacion() {
    var puntos = {
        OesteR: 0,
        FabricaR: 0,
        EspiasR: 0,
        PoliticosR: 0,
        EsteR: 0,
        MilitaresR: 0,
        MuroR: 0
    };
    var detalle = [];
    var valoresObreros = obtenerPuntosPorTipo("FabricaR", ["cabo", "peon", "OficialMujer"]);
    var valoresMilitares = obtenerPuntosPorTipo("MilitaresR", ["tropa", "Oficial", "general"]);
    var hayMarkus = false;
    var hayUrsula = false;
    var i;
    var fichaColocada;
    var fichaDatos;
    var nombreRecinto;
    var puntosFicha;

    /* Comprueba si están los dos espías. */
    for (i = 0; i < estado.colocaciones.length; i++) {
        if (estado.colocaciones[i].recinto === "EspiasR" && estado.colocaciones[i].ficha === "Markus") {
            hayMarkus = true;
        }
        if (estado.colocaciones[i].recinto === "EspiasR" && estado.colocaciones[i].ficha === "Ursula") {
            hayUrsula = true;
        }
    }

    for (i = 0; i < estado.colocaciones.length; i++) {
        fichaColocada = estado.colocaciones[i];
        fichaDatos = FICHAS[fichaColocada.ficha];
        nombreRecinto = RECINTOS[fichaColocada.recinto].nombre;
        puntosFicha = 0;

        if (fichaColocada.recinto === "OesteR" || fichaColocada.recinto === "EsteR") {
            puntosFicha = 1;
        } else if (fichaColocada.recinto === "MuroR") {
            puntosFicha = 1;
        } else if (fichaColocada.recinto === "FabricaR") {
            if (typeof valoresObreros[fichaColocada.ficha] !== "undefined") {
                puntosFicha = valoresObreros[fichaColocada.ficha];
            }
        } else if (fichaColocada.recinto === "MilitaresR") {
            if (typeof valoresMilitares[fichaColocada.ficha] !== "undefined") {
                puntosFicha = valoresMilitares[fichaColocada.ficha];
            }
        } else if (fichaColocada.recinto === "EspiasR") {
            if (hayMarkus && hayUrsula && (fichaColocada.ficha === "Markus" || fichaColocada.ficha === "Ursula")) {
                puntosFicha = 2;
            }
        }

        /* Cada político agrega un punto en cualquier recinto. */
        if (fichaDatos.familia === "politicos") {
            puntosFicha++;
        }

        puntos[fichaColocada.recinto] = puntos[fichaColocada.recinto] + puntosFicha;
        detalle.push({
            ficha: fichaDatos.nombre,
            recinto: nombreRecinto,
            posicion: fichaColocada.posicion,
            puntos: puntosFicha
        });
    }

    var total = 0;
    for (var idRecinto in puntos) {
        total = total + puntos[idRecinto];
    }

    return { puntos: puntos, detalle: detalle, total: total };
}

function actualizarPuntuacion() {
    var resultado = calcularPuntuacion();
    var ids = {
        OesteR: "pts-oeste",
        FabricaR: "pts-fabrica",
        EspiasR: "pts-espias",
        PoliticosR: "pts-politicos",
        EsteR: "pts-este",
        MilitaresR: "pts-militares",
        MuroR: "pts-muro"
    };
    var idRecinto;
    var elemento;

    for (idRecinto in ids) {
        elemento = document.getElementById(ids[idRecinto]);
        if (elemento) {
            elemento.textContent = resultado.puntos[idRecinto] + " pts";
        }
    }
    if (marcadorGigante) {
        marcadorGigante.textContent = String(resultado.total);
    }
}

function mostrarPuntuacionFinal() {
    var resultado = calcularPuntuacion();
    var total = document.getElementById("total-puntuacion-final");
    var idsFinales = {
        OesteR: "final-pts-oeste",
        FabricaR: "final-pts-fabrica",
        EspiasR: "final-pts-espias",
        PoliticosR: "final-pts-politicos",
        EsteR: "final-pts-este",
        MilitaresR: "final-pts-militares",
        MuroR: "final-pts-muro"
    };
    var idRecinto;
    var elemento;
    var lista;
    var i;
    var linea;

    if (total) {
        total.textContent = String(resultado.total);
    }
    for (idRecinto in idsFinales) {
        elemento = document.getElementById(idsFinales[idRecinto]);
        if (elemento) {
            elemento.textContent = resultado.puntos[idRecinto] + " pts";
        }
    }

    lista = document.getElementById("lista-puntos-por-ficha");
    if (lista) {
        lista.innerHTML = "";
        if (resultado.detalle.length === 0) {
            lista.textContent = "No se colocaron fichas.";
        } else {
            for (i = 0; i < resultado.detalle.length; i++) {
                linea = document.createElement("p");
                linea.className = "detalle-puntuacion-ficha";
                linea.textContent = resultado.detalle[i].ficha + " — " + resultado.detalle[i].recinto + ", espacio " + resultado.detalle[i].posicion + ": " + resultado.detalle[i].puntos + " pts";
                lista.appendChild(linea);
            }
        }
    }

    if (modalFinal) {
        modalFinal.hidden = false;
    }
}

/* ----------------------------------------------------------------------
   10. SALIR Y BORRAR EL GUARDADO
   ---------------------------------------------------------------------- */

function salirDeCalculadora() {
    var confirmar = window.confirm("¿Querés cerrar la calculadora y borrar la partida guardada en este dispositivo?");
    if (!confirmar) {
        return;
    }
    localStorage.removeItem(CLAVE_GUARDADO);
    window.location.href = "inicioHome.php";
}

/* ----------------------------------------------------------------------
   11. ACTUALIZAR TODA LA INTERFAZ
   ---------------------------------------------------------------------- */

function actualizarPantalla() {
    dibujarTablero();
    actualizarFichasDisponibles();
    actualizarPuntuacion();
    actualizarProgreso();

    if (casillaMiTurnoDado) {
        casillaMiTurnoDado.checked = estado.esMiTurnoDado;
    }
}

/* ----------------------------------------------------------------------
   12. CONECTAR LOS CLICS Y CONTROLES
   ---------------------------------------------------------------------- */

function conectarEventos() {
    var i;

    /* Clics en recintos y fichas ya colocadas. */
    for (i = 0; i < recintosHTML.length; i++) {
        recintosHTML[i].addEventListener("click", function (evento) {
            var botonQuitar = evento.target.closest("[data-quitar-colocacion]");
            var espacio = evento.target.closest(".sector-recinto");
            var idRecinto = this.dataset.sector;

            if (botonQuitar) {
                evento.preventDefault();
                evento.stopPropagation();
                quitarFichaDelTurno(botonQuitar.dataset.quitarColocacion);
                return;
            }

            /* En la cara especial, tocar una ficha inicia el movimiento. */
            if (espacio && espacio.classList.contains("ocupado") && estado.caraDado === "cambiar-ficha" && estado.esMiTurnoDado) {
                comenzarMovimiento(espacio.dataset.colocacionId);
                return;
            }

            seleccionarRecinto(idRecinto);
        });

        recintosHTML[i].addEventListener("keydown", function (evento) {
            if (evento.key === "Enter" || evento.key === " ") {
                evento.preventDefault();
                seleccionarRecinto(this.dataset.sector);
            }
        });
    }

    /* Clics en los botones de fichas disponibles. */
    for (i = 0; i < botonesFicha.length; i++) {
        botonesFicha[i].addEventListener("click", function () {
            if (estado.fichaAMover) {
                mostrarAviso("Elegí un recinto de destino para la ficha que estás moviendo.");
                return;
            }
            colocarFicha(this.dataset.ficha);
        });
    }

    /* Abrir o cerrar el menú del dado. */
    if (botonAbrirDado && menuCaras) {
        botonAbrirDado.addEventListener("click", function () {
            menuCaras.hidden = !menuCaras.hidden;
        });
    }

    /* Elegir una cara del dado físico. */
    for (i = 0; i < botonesCaras.length; i++) {
        botonesCaras[i].addEventListener("click", function () {
            seleccionarCara(this.dataset.cara);
        });
    }

    /* Indicar si el dado lo tiró el jugador que está usando la calculadora. */
    if (casillaMiTurnoDado) {
        casillaMiTurnoDado.addEventListener("change", function () {
            estado.esMiTurnoDado = casillaMiTurnoDado.checked;
            estado.recintoSeleccionado = "";
            actualizarPantalla();
            guardarPartida();
        });
    }

    if (botonSiguiente) {
        botonSiguiente.addEventListener("click", avanzarTurno);
    }
    if (botonSalir) {
        botonSalir.addEventListener("click", salirDeCalculadora);
    }
    if (botonCerrarCalculadora) {
        botonCerrarCalculadora.addEventListener("click", salirDeCalculadora);
    }

    /* Cerrar el menú del dado si se hace clic fuera de él. */
    document.addEventListener("click", function (evento) {
        if (menuCaras && botonAbrirDado && !menuCaras.contains(evento.target) && !botonAbrirDado.contains(evento.target)) {
            menuCaras.hidden = true;
        }
    });
}

/* ----------------------------------------------------------------------
   13. INICIAR LA CALCULADORA
   ---------------------------------------------------------------------- */

function iniciarCalculadora() {
    prepararEspacios();
    cargarPartida();
    conectarEventos();
    actualizarPantalla();

    if (estado.caraDado && CARAS_DADO[estado.caraDado]) {
        imagenCaraActiva.src = CARAS_DADO[estado.caraDado].imagen;
        nombreCaraActiva.textContent = CARAS_DADO[estado.caraDado].nombre;
        subCaraActiva.textContent = "Cara seleccionada del dado físico";
    }
    if (estado.partidaFinalizada) {
        mostrarPuntuacionFinal();
    }

    console.log("Calculadora Draft Der Mauer lista.");
}

document.addEventListener("DOMContentLoaded", iniciarCalculadora);
