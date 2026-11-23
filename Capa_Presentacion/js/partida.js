/*
 * PARTIDA EN LÍNEA (navegador)
 *
 * El navegador no decide nada: consulta el estado al servidor cada
 * segundo (polling) y le manda las acciones del jugador. Todo se arma con
 * funciones del DOM (nunca innerHTML con datos de usuarios), así un nombre
 * de usuario malicioso no puede inyectar código.
 */
(function () {
    'use strict';

    const cuerpo = document.body;
    const ID = cuerpo.dataset.id;
    const CSRF = cuerpo.dataset.csrf;
    const API = cuerpo.dataset.api;
    const URL_INICIO = cuerpo.dataset.inicio;
    const URL_LOGIN = cuerpo.dataset.login;
    const INTERVALO = 1000;

    const NOMBRE_FICHA = {
        general: 'General', oficial_militar: 'Oficial Militar', tropa: 'Tropa',
        cabo: 'Cabo', oficial_obrera: 'Oficial Obrera', peon: 'Peón',
        politico: 'Político', markus: 'Markus', ursula: 'Ursula'
    };
    const NOMBRE_RECINTO = {
        oeste: 'Oeste', fabrica: 'Fábrica', espias: 'Espías', politicos: 'Políticos',
        este: 'Este', militares: 'Militares', muro: 'Muro'
    };
    const CARAS = {
        lado_izquierdo: ['Bloqueo del lado izquierdo', 'No se puede colocar en Oeste, Fábrica ni Espías.', 'NoIzquierdoDadoDraftDerMauer.png'],
        lado_derecho: ['Bloqueo del lado derecho', 'No se puede colocar en Políticos, Este ni Militares.', 'NoDerechoDadoDraftDerMauer.png'],
        solo_militares: ['Solo Militares', 'Solo se puede colocar en Militares (o en el Muro).', 'SoloRecintoMilitaresDadoDraftDerMauer.png'],
        bloquea_fabrica: ['Bloqueo de Fábrica', 'No se puede colocar en Fábrica.', 'NoFabricaDadoDraftDerMauer.png'],
        cambiar_ficha: ['Cambiar ficha', 'Quien tiró mueve una ficha propia y además coloca una.', 'ReacomodarFichaDadoDraftDerMauer.png'],
        solo_espias: ['Solo Espías', 'Solo se puede colocar en Espías (o en el Muro).', 'SoloEspiasDadoDraftDerMauer.png']
    };
    const ORDEN_RECINTOS = ['oeste', 'fabrica', 'espias', 'politicos', 'este', 'militares', 'muro'];

    let diccionario = {};
    let estado = null;
    let version = -1;
    let seleccion = null;     // nro de la ficha elegida en la mano
    let moviendo = null;      // nro de la ficha del tablero que se va a mover
    let ocupado = false;      // hay una acción en camino
    let segundosRestantes = null;
    let segundosLimite = null;
    let finalMostrado = false;
    let turnoVisto = '';

    /* ---------- utilidades ---------- */

    function t(texto, valores) {
        let salida = diccionario[texto] || texto;
        if (valores) {
            Object.keys(valores).forEach(function (clave) {
                salida = salida.split('{' + clave + '}').join(valores[clave]);
            });
        }
        return salida;
    }

    function nodo(etiqueta, clase, texto) {
        const elemento = document.createElement(etiqueta);
        if (clase) elemento.className = clase;
        if (texto !== undefined && texto !== null) elemento.textContent = texto;
        return elemento;
    }

    function imagen(src, alt) {
        const img = document.createElement('img');
        img.src = src;
        img.alt = alt;
        img.loading = 'lazy';
        return img;
    }

    function vaciar(elemento) {
        while (elemento.firstChild) elemento.removeChild(elemento.firstChild);
    }

    function $(id) {
        return document.getElementById(id);
    }

    let temporizadorAviso = null;
    function avisar(mensaje, esError) {
        const caja = $('aviso');
        caja.textContent = mensaje;
        caja.classList.toggle('error', !!esError);
        caja.hidden = false;
        clearTimeout(temporizadorAviso);
        temporizadorAviso = setTimeout(function () { caja.hidden = true; }, 3500);
    }

    /* ---------- comunicación con el servidor ---------- */

    const ESPECTAR = document.body.dataset.espectar === '1';   // mirar sin jugar (Fase 4)

    async function consultar() {
        try {
            const respuesta = await fetch(API + '?id=' + encodeURIComponent(ID) + '&v=' + version + (ESPECTAR ? '&espectar=1' : ''), {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });
            if (respuesta.status === 401) {
                window.location.href = URL_LOGIN;
                return;
            }
            const datos = await respuesta.json();
            if (!datos.exito) {
                avisar(t(datos.mensaje), true);
                if (respuesta.status === 404) {
                    setTimeout(function () { window.location.href = URL_INICIO; }, 2500);
                }
                return;
            }
            segundosRestantes = datos.segundosRestantes;
            segundosLimite = datos.segundosLimite;
            if (!datos.sinCambios) {
                estado = datos;
                version = datos.version;
                dibujar();
            }
            dibujarReloj();
        } catch (error) {
            console.error(error);
        }
    }

    async function accion(nombre, extra) {
        if (ocupado) return;
        ocupado = true;
        const cuerpoPedido = new URLSearchParams(Object.assign({ id: ID, accion: nombre }, extra || {}));
        try {
            const respuesta = await fetch(API, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-CSRF-Token': CSRF, 'Accept': 'application/json' },
                body: cuerpoPedido
            });
            const datos = await respuesta.json();
            if (!datos.exito) avisar(t(datos.mensaje), true);
        } catch (error) {
            avisar(t('No se pudo hablar con el servidor. Revisá tu conexión.'), true);
        } finally {
            ocupado = false;
            seleccion = null;
            moviendo = null;
            version = -1;
            await consultar();
        }
    }

    function ciclo() {
        consultar().finally(function () { setTimeout(ciclo, INTERVALO); });
    }

    /* ---------- dibujo general ---------- */

    function yo() {
        return estado.jugadores.find(function (j) { return j.esYo; });
    }

    function dibujar() {
        if (estado.espectador) {
            document.body.classList.add('modo-espectador');
            $('aviso-espectador').hidden = false;
            const cajaPuntos = $('mi-total').closest('.caja');
            $('btn-salir').textContent = t('Volver');
            if (cajaPuntos) cajaPuntos.hidden = true;
        }
        $('nombre-partida').textContent = estado.nombre + (estado.modo === 'competitiva' ? ' · ' + t('Competitiva') : estado.modo === 'personalizada' ? ' · ' + t('Personalizada') : '');
        const enEspera = estado.estado === 'esperando';
        $('vista-espera').hidden = !enEspera;
        $('vista-juego').hidden = enEspera;
        if (enEspera) {
            $('progreso-partida').textContent = t('{n} de {max} jugadores', { n: estado.jugadores.length, max: estado.maxJugadores });
            dibujarEspera();
            return;
        }
        $('progreso-partida').textContent = t('Ronda {r} de {rs}, turno {t} de {ts}', {
            r: estado.ronda, rs: estado.config.rondas, t: estado.turno, ts: estado.config.fichasPorRonda
        });

        // Si cambió el turno, se olvida cualquier selección vieja.
        const claveTurno = estado.ronda + '-' + estado.turno + '-' + estado.fase;
        if (claveTurno !== turnoVisto) {
            seleccion = null;
            moviendo = null;
            turnoVisto = claveTurno;
        }

        dibujarDado();
        dibujarEstadoTurno();
        if (!estado.espectador) {   // quien mira no tiene tablero, mano ni puntos propios
            dibujarMiTablero();
            dibujarMano();
            dibujarPuntos();
        }
        dibujarJugadores();
        dibujarHistorial();
        dibujarRivales();
        if ((estado.estado === 'finalizada' || estado.estado === 'cancelada') && !finalMostrado) {
            finalMostrado = true;
            dibujarFinal();
        }
    }

    function dibujarReloj() {
        const reloj = $('reloj');
        if (segundosRestantes === null || segundosRestantes === undefined || !estado || estado.estado !== 'en_curso') {
            reloj.hidden = true;
            return;
        }
        reloj.hidden = false;
        $('reloj-numero').textContent = segundosRestantes;
        const proporcion = segundosLimite ? segundosRestantes / segundosLimite : 0;
        $('reloj-arco').style.strokeDashoffset = String(97.4 * (1 - proporcion));
        reloj.classList.toggle('urgente', segundosRestantes <= 10);
    }

    // Cuenta regresiva local entre consulta y consulta (el servidor la corrige cada segundo).
    setInterval(function () {
        if (typeof segundosRestantes === 'number' && segundosRestantes > 0) {
            segundosRestantes--;
            dibujarReloj();
        }
    }, 1000);

    /* ---------- sala de espera ---------- */

    function dibujarEspera() {
        $('espera-detalle').textContent = t('{tipo} · hasta {max} jugadores', {
            tipo: estado.tipo === 'privada' ? t('Sala privada') : t('Sala pública'), max: estado.maxJugadores
        });
        const lista = $('asientos');
        vaciar(lista);
        estado.jugadores.forEach(function (j) {
            const fila = nodo('li');
            fila.appendChild(nodo('span', null, (j.esBot ? '🤖 ' : '') + j.nombre));
            fila.appendChild(nodo('small', null, j.esCreador ? t('creó la sala') : j.esYo ? t('vos') : ''));
            lista.appendChild(fila);
        });
        for (let i = estado.jugadores.length; i < estado.maxJugadores; i++) {
            lista.appendChild(nodo('li', 'libre', t('Lugar libre')));
        }
        $('acciones-espera').hidden = !estado.puedeAgregarBot;
    }

    /* ---------- dado y estado del turno ---------- */

    function dibujarDado() {
        const caja = $('caja-dado');
        const cara = estado.cara;
        const img = $('cara-dado');
        const tirador = estado.jugadores.find(function (j) { return j.tieneDado; });
        if (cara && CARAS[cara]) {
            const nuevaSrc = 'img/juego/' + CARAS[cara][2];
            if (!img.src.endsWith(CARAS[cara][2])) {
                img.src = nuevaSrc;
                caja.classList.remove('girando');
                void caja.offsetWidth;
                caja.classList.add('girando');
            }
            img.alt = t(CARAS[cara][0]);
            $('nombre-cara').textContent = t(CARAS[cara][0]);
            let detalle = t(CARAS[cara][1]);
            if (estado.yo.esTirador && estado.config.tiradorExento && cara !== 'cambiar_ficha') {
                detalle += ' ' + t('Vos tiraste: a vos no te afecta.');
            }
            $('detalle-cara').textContent = detalle;
        } else {
            img.src = 'img/juego/CaraVaciaDadoDraftDerMauer.png';
            img.alt = t('Dado sin tirar');
            $('nombre-cara').textContent = t('Dado');
            $('detalle-cara').textContent = tirador ? t('Lo tiene {nombre}.', { nombre: tirador.esYo ? t('vos') : tirador.nombre }) : '';
        }
        $('btn-tirar').hidden = !(estado.estado === 'en_curso' && estado.fase === 'dado' && estado.yo.esTirador && estado.yo.activo);
    }

    function dibujarEstadoTurno() {
        const caja = $('estado-turno');
        vaciar(caja);
        const mio = estado.yo;
        let fuerte = '';
        let texto = '';
        let miAccion = false;
        if (estado.estado !== 'en_curso') {
            texto = estado.estado === 'finalizada' ? t('La partida terminó.') : t('La partida se canceló.');
        } else if (estado.espectador) {
            texto = t('Estás mirando esta partida.');
        } else if (!mio.activo) {
            texto = t('Saliste de esta partida. Podés seguir mirando o volver al inicio.');
        } else if (estado.fase === 'dado') {
            if (mio.esTirador) {
                fuerte = t('Te toca tirar el dado.');
                miAccion = true;
            } else {
                const tirador = estado.jugadores.find(function (j) { return j.tieneDado; });
                texto = t('Esperando que {nombre} tire el dado.', { nombre: tirador ? tirador.nombre : '…' });
            }
        } else if (mio.confirmado) {
            texto = t('Listo. Esperando a los demás jugadores…');
        } else if (mio.debeMover) {
            fuerte = t('Mové una ficha.');
            texto = ' ' + t('Tocá una de tus fichas de turnos anteriores (las de borde celeste) y después el recinto adonde va. Además colocá una de tu mano.');
            miAccion = true;
        } else if (mio.sinLugar) {
            fuerte = t('Ninguna de tus fichas entra en ningún lado.');
            texto = ' ' + t('Confirmá y se descarta una.');
            miAccion = true;
        } else if (mio.colocada === null) {
            fuerte = t('Tu turno.');
            texto = ' ' + t('Elegí una ficha de tu mano y después el recinto donde va.');
            miAccion = true;
        } else {
            fuerte = t('Ficha colocada.');
            texto = ' ' + t('Podés corregirla con la X o confirmar la jugada.');
            miAccion = true;
        }
        if (fuerte) caja.appendChild(nodo('strong', null, fuerte));
        if (texto) caja.appendChild(document.createTextNode(texto));
        caja.classList.toggle('mi-accion', miAccion);
    }

    /* ---------- mi tablero ---------- */

    function recintosValidos() {
        const mio = estado.yo;
        if (moviendo !== null) {
            const movible = mio.movibles.find(function (m) { return m.nro === moviendo; });
            return movible ? movible.destinos : [];
        }
        if (seleccion === null) return [];
        const ficha = mio.mano.find(function (f) { return f.nro === seleccion; });
        return ficha && mio.opciones[ficha.tipo] ? mio.opciones[ficha.tipo] : [];
    }

    function dibujarMiTablero() {
        const tablero = $('mi-tablero');
        const propio = yo();
        const validos = recintosValidos();
        const movibles = (estado.yo.movibles || []).map(function (m) { return m.nro; });
        tablero.classList.toggle('eligiendo', seleccion !== null || moviendo !== null);

        tablero.querySelectorAll('.recinto').forEach(function (recinto) {
            const codigo = recinto.dataset.recinto;
            const capacidad = estado.capacidades[codigo];
            const fichas = propio.tablero.filter(function (f) { return f.recinto === codigo; })
                .sort(function (a, b) { return a.posicion - b.posicion; });
            recinto.classList.toggle('valido', validos.indexOf(codigo) !== -1);
            recinto.querySelector('h3').textContent = t(NOMBRE_RECINTO[codigo]);
            recinto.querySelector('.cupo').textContent = fichas.length + '/' + capacidad;

            const casillas = recinto.querySelector('.casillas');
            vaciar(casillas);
            fichas.forEach(function (f) {
                const casilla = nodo('div', 'casilla ocupada' + (f.actual ? ' actual' : ''));
                casilla.appendChild(imagen(f.img, t(NOMBRE_FICHA[f.tipo])));
                casilla.title = t(NOMBRE_FICHA[f.tipo]) + ' · ' + t('{n} pts', { n: f.puntos });
                casilla.appendChild(nodo('span', 'pts', String(f.puntos)));
                if (f.actual && f.nro === estado.yo.colocada && !estado.yo.confirmado) {
                    const x = nodo('button', 'quitar', '×');
                    x.type = 'button';
                    x.setAttribute('aria-label', t('Quitar esta ficha y elegir otra'));
                    x.addEventListener('click', function (ev) {
                        ev.stopPropagation();
                        accion('quitar', { nro: f.nro });
                    });
                    casilla.appendChild(x);
                }
                if (estado.yo.debeMover && movibles.indexOf(f.nro) !== -1) {
                    casilla.classList.add('movible');
                    casilla.tabIndex = 0;
                    casilla.setAttribute('role', 'button');
                    if (moviendo === f.nro) casilla.classList.add('elegida');
                    const elegir = function (ev) {
                        ev.stopPropagation();
                        moviendo = moviendo === f.nro ? null : f.nro;
                        seleccion = null;
                        dibujar();
                    };
                    casilla.addEventListener('click', elegir);
                    casilla.addEventListener('keydown', function (ev) { if (ev.key === 'Enter') elegir(ev); });
                }
                casillas.appendChild(casilla);
            });
            // Casillas vacías (el Muro muestra solo algunas para no llenar la pantalla).
            const vacias = codigo === 'muro' ? Math.min(2, capacidad - fichas.length) : capacidad - fichas.length;
            for (let i = 0; i < vacias; i++) casillas.appendChild(nodo('div', 'casilla'));
        });
    }

    // Un solo manejador para los recintos (se engancha una vez).
    document.querySelectorAll('#mi-tablero .recinto').forEach(function (recinto) {
        recinto.addEventListener('click', function () {
            if (!estado || !recinto.classList.contains('valido')) return;
            const codigo = recinto.dataset.recinto;
            if (moviendo !== null) {
                accion('mover', { nro: moviendo, recinto: codigo });
            } else if (seleccion !== null) {
                accion('colocar', { nro: seleccion, recinto: codigo });
            }
        });
    });

    /* ---------- la mano ---------- */

    function dibujarMano() {
        const mio = estado.yo;
        const contenedor = $('fichas-mano');
        vaciar(contenedor);
        const puedeElegir = estado.estado === 'en_curso' && estado.fase === 'colocar' && mio.activo && !mio.confirmado && mio.colocada === null;
        mio.mano.forEach(function (f) {
            const boton = nodo('button', 'ficha-mano' + (seleccion === f.nro ? ' elegida' : ''));
            boton.type = 'button';
            boton.appendChild(imagen(f.img, ''));
            boton.appendChild(nodo('span', null, t(NOMBRE_FICHA[f.tipo])));
            const destinos = mio.opciones[f.tipo] || [];
            boton.disabled = !puedeElegir || destinos.length === 0;
            if (puedeElegir && destinos.length === 0) {
                boton.title = t('Esta ficha no entra en ningún recinto este turno.');
            } else if (puedeElegir) {
                boton.title = t('Puede ir a: {lista}', { lista: destinos.map(function (r) { return t(NOMBRE_RECINTO[r]); }).join(', ') });
            }
            boton.addEventListener('click', function () {
                seleccion = seleccion === f.nro ? null : f.nro;
                moviendo = null;
                dibujar();
            });
            contenedor.appendChild(boton);
        });
        if (mio.mano.length === 0) {
            contenedor.appendChild(nodo('p', 'pista', estado.estado === 'en_curso' ? t('No te quedan fichas en la mano.') : ''));
        }

        let pista = '';
        if (seleccion !== null) pista = t('Ahora tocá un recinto marcado en amarillo.');
        else if (moviendo !== null) pista = t('Ahora tocá el recinto adonde querés mover la ficha.');
        else if (puedeElegir) pista = t('Tocá una ficha para ver dónde puede ir.');
        $('pista-mano').textContent = pista;

        $('btn-confirmar').disabled = !(estado.estado === 'en_curso' && mio.activo && !mio.confirmado && mio.puedeConfirmar);
    }

    /* ---------- panel lateral ---------- */

    function dibujarPuntos() {
        const propio = yo();
        $('mi-total').textContent = propio.puntos.total;
        const lista = $('desglose');
        vaciar(lista);
        ORDEN_RECINTOS.forEach(function (codigo) {
            lista.appendChild(nodo('dt', null, t(NOMBRE_RECINTO[codigo])));
            lista.appendChild(nodo('dd', null, String(propio.puntos.recintos[codigo])));
        });
        lista.appendChild(nodo('dt', null, t('Políticos (1 c/u)')));
        lista.appendChild(nodo('dd', null, String(propio.puntos.politicos)));
    }

    function dibujarJugadores() {
        const lista = $('lista-jugadores');
        vaciar(lista);
        estado.jugadores.forEach(function (j) {
            const fila = nodo('li');
            let icono = '';
            let clase = '';
            if (j.estado === 'abandono') { icono = '✕'; clase = 'fuera'; }
            else if (j.tieneDado) { icono = '🎲'; }
            else if (estado.fase === 'colocar' && j.confirmado) { icono = '✓'; clase = 'listo'; }
            else if (estado.fase === 'colocar') { icono = '…'; clase = 'pensando'; }
            fila.appendChild(nodo('span', 'icono ' + clase, icono));
            fila.appendChild(nodo('span', (j.esYo ? 'yo ' : '') + (j.estado === 'abandono' ? 'fuera' : ''), (j.esBot ? '🤖 ' : '') + j.nombre + (j.esYo ? ' (' + t('vos') + ')' : '')));
            fila.appendChild(nodo('span', null, String(j.puntos.total)));
            fila.title = t('{n} fichas en la mano', { n: j.fichasEnMano });
            lista.appendChild(fila);
        });
    }

    function dibujarHistorial() {
        const lista = $('historial');
        vaciar(lista);
        if (estado.historial.length === 0) {
            lista.appendChild(nodo('li', null, t('Las jugadas aparecen cuando termina cada turno.')));
            return;
        }
        estado.historial.slice(0, 60).forEach(function (h) {
            const fila = nodo('li');
            fila.appendChild(document.createTextNode(t('R{r} T{t}: ', { r: h.ronda, t: h.turno })));
            fila.appendChild(nodo('strong', null, h.jugador));
            let texto;
            if (h.tipo === 'mover') {
                texto = ' ' + t('movió {ficha} de {origen} a {destino}', { ficha: t(NOMBRE_FICHA[h.ficha]), origen: t(NOMBRE_RECINTO[h.origen]), destino: t(NOMBRE_RECINTO[h.recinto]) });
            } else {
                texto = ' ' + t('puso {ficha} en {destino}', { ficha: t(NOMBRE_FICHA[h.ficha]), destino: t(NOMBRE_RECINTO[h.recinto]) });
                if (h.tipo === 'auto') texto += ' ' + t('(automático)');
            }
            fila.appendChild(document.createTextNode(texto));
            lista.appendChild(fila);
        });
    }

    function dibujarRivales() {
        const zona = $('rivales');
        vaciar(zona);
        estado.jugadores.filter(function (j) { return !j.esYo; }).forEach(function (j) {
            const tarjeta = nodo('article', 'rival');
            const cabecera = nodo('header');
            cabecera.appendChild(nodo('h3', null, (j.esBot ? '🤖 ' : '') + j.nombre + (j.estado === 'abandono' ? ' (' + t('abandonó') + ')' : '')));
            cabecera.appendChild(nodo('span', 'puntos-rival', t('{n} pts', { n: j.puntos.total })));
            tarjeta.appendChild(cabecera);

            const mini = nodo('div', 'mini-tablero');
            const lados = [['oeste', 'fabrica', 'espias'], null, ['politicos', 'este', 'militares']];
            lados.forEach(function (lado) {
                if (lado === null) {
                    const muro = nodo('div', 'mini-muro');
                    j.tablero.filter(function (f) { return f.recinto === 'muro'; }).forEach(function (f) {
                        muro.appendChild(imagen(f.img, t(NOMBRE_FICHA[f.tipo])));
                    });
                    mini.appendChild(muro);
                    return;
                }
                const columna = nodo('div', 'mini-lado');
                lado.forEach(function (codigo) {
                    const caja = nodo('div', 'mini-recinto');
                    caja.appendChild(nodo('small', null, t(NOMBRE_RECINTO[codigo])));
                    j.tablero.filter(function (f) { return f.recinto === codigo; })
                        .sort(function (a, b) { return a.posicion - b.posicion; })
                        .forEach(function (f) { caja.appendChild(imagen(f.img, t(NOMBRE_FICHA[f.tipo]))); });
                    columna.appendChild(caja);
                });
                mini.appendChild(columna);
            });
            tarjeta.appendChild(mini);
            zona.appendChild(tarjeta);
        });
    }

    /* ---------- final ---------- */

    function dibujarFinal() {
        if (estado.estado === 'cancelada') {
            $('titulo-final').textContent = t('Partida cancelada');
            $('subtitulo-final').textContent = t('Todos salieron antes de empezar.');
        }
        const filas = estado.jugadores.slice().sort(function (a, b) {
            const pa = a.resultado && a.resultado.posicion !== null ? a.resultado.posicion : 99;
            const pb = b.resultado && b.resultado.posicion !== null ? b.resultado.posicion : 99;
            return pa - pb;
        });
        const ganadores = filas.filter(function (j) { return j.resultado && j.resultado.posicion === 1; });
        if (estado.estado === 'finalizada') {
            const nombres = ganadores.map(function (j) { return j.esYo ? t('vos') : j.nombre; });
            $('subtitulo-final').textContent = ganadores.length > 1
                ? t('Empate: comparten la victoria {nombres}.', { nombres: nombres.join(' y ') })
                : ganadores.length === 1 && ganadores[0].esYo ? t('¡Ganaste!') : t('Ganó {nombre}.', { nombre: nombres[0] || '' });
        }
        const cuerpoTabla = $('cuerpo-final');
        vaciar(cuerpoTabla);
        filas.forEach(function (j) {
            const fila = nodo('tr', (j.resultado && j.resultado.posicion === 1 ? 'ganador ' : '') + (j.esYo ? 'yo' : ''));
            const posicion = j.resultado && j.resultado.posicion !== null ? j.resultado.posicion + '°' : t('abandonó');
            fila.appendChild(nodo('td', null, posicion));
            fila.appendChild(nodo('td', null, (j.esBot ? '🤖 ' : '') + j.nombre));
            fila.appendChild(nodo('td', 'total', String(j.puntos.total)));
            ['oeste', 'fabrica', 'espias'].forEach(function (r) { fila.appendChild(nodo('td', null, String(j.puntos.recintos[r]))); });
            fila.appendChild(nodo('td', null, String(j.puntos.politicos + j.puntos.recintos.politicos)));
            ['este', 'militares', 'muro'].forEach(function (r) { fila.appendChild(nodo('td', null, String(j.puntos.recintos[r]))); });
            let recibe = j.resultado ? '+' + j.resultado.otorgados : '';
            if (j.resultado && j.resultado.mmrDespues !== null) {
                const delta = j.resultado.mmrDespues - j.resultado.mmrAntes;
                recibe += ' · MMR ' + (delta >= 0 ? '+' : '') + delta;
            }
            fila.appendChild(nodo('td', null, j.esBot ? '' : recibe));
            cuerpoTabla.appendChild(fila);
        });
        const notas = [];
        if (estado.hayBots) notas.push(t('Hubo bots en la mesa: esta partida no da puntos de tienda.'));
        if (estado.modo === 'personalizada') notas.push(t('Las partidas personalizadas dan puntos de tienda pero no suman al ranking.'));
        if (estado.modo === 'competitiva') notas.push(t('Partida competitiva: se actualizó tu MMR.'));
        notas.push(t('Quien abandona no recibe puntos.'));
        $('nota-final').textContent = notas.join(' ');
        $('capa-final').hidden = false;
    }

    /* ---------- botones fijos ---------- */

    $('btn-tirar').addEventListener('click', function () { accion('tirar'); });
    $('btn-confirmar').addEventListener('click', function () { accion('confirmar'); });
    $('btn-agregar-bot').addEventListener('click', function () { accion('agregar_bot'); });
    $('btn-completar-bots').addEventListener('click', function () { accion('completar_bots'); });
    $('btn-ver-tableros').addEventListener('click', function () { $('capa-final').hidden = true; });
    $('btn-salir').addEventListener('click', async function () {
        if (ESPECTAR) { window.location.href = document.body.dataset.mundo; return; }
        if (estado && estado.estado === 'en_curso' && estado.yo.activo) {
            if (!window.confirm(t('Si salís ahora perdés los puntos de esta partida. ¿Salir igual?'))) return;
            await accion('salir');
        } else if (estado && estado.estado === 'esperando') {
            await accion('salir');
        }
        // Después de un desafío se vuelve a Berlín, donde está el contacto para reintentar
        window.location.href = document.body.dataset.desafio === '1' ? document.body.dataset.volver : URL_INICIO;
    });
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape') {
            seleccion = null;
            moviendo = null;
            if (estado) dibujar();
        }
    });

    /* ---------- arranque ---------- */

    async function iniciar() {
        if ((cuerpo.dataset.idioma || 'es') !== 'es') {
            try {
                const respuesta = await fetch('./lang/' + cuerpo.dataset.idioma + '.json');
                if (respuesta.ok) diccionario = await respuesta.json();
            } catch (error) {
                console.error(error);
            }
        }
        ciclo();
    }
    iniciar();
})();
