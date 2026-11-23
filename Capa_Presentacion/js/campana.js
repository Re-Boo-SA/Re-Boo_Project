/*
 * ======================================================================
 *  MOTOR DE ESCENAS DE LA CAMPAÑA (etapa D)
 *  Ejecuta el guion de datos/campana/<capitulo>.json paso a paso: escenas (mapas
 *  Tiled), diálogos con retrato, decisiones, movimientos, cinemáticas (franjas,
 *  fundidos, cámara), sigilo con conos de visión, persecuciones y efectos.
 *  El servidor guarda los puntos de "guardar" (en orden), las decisiones y el final.
 *  Sin archivos de audio: los sonidos se sintetizan con WebAudio.
 * ======================================================================
 */
(function () {
    'use strict';
    const B = document.body, API = B.dataset.api, CSRF = B.dataset.csrf, CAP = B.dataset.capitulo;
    const $ = function (id) { return document.getElementById(id); };
    const T = 40, VEL = 150, FILA = [3, 2, 0, 1];   // dir 0 arriba, 1 derecha, 2 abajo, 3 izquierda -> fila de la hoja
    let dic = {};
    function t(texto, v) {
        let s = dic[texto] || texto;
        if (v) Object.keys(v).forEach(function (k) { s = s.split('{' + k + '}').join(v[k]); });
        return s;
    }
    const AUTO = function () { return window.__campanaAuto === true; };   // solo para pruebas automáticas
    const dormir = function (seg) { return new Promise(function (r) { setTimeout(r, Math.max(0, seg) * 1000 / (AUTO() ? 6 : 1)); }); };

    async function post(datos) {
        try {
            const r = await fetch(API, { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-Token': CSRF, 'Accept': 'application/json' }, body: new URLSearchParams(Object.assign({ id: CAP }, datos)) });
            return await r.json();
        } catch (e) { return { exito: false }; }
    }

    /* ---------- sonido sintetizado ---------- */
    const Sonido = (function () {
        let ctx = null, lluviaNodo = null, tensionNodos = null;
        function iniciar() { if (!ctx) { try { ctx = new (window.AudioContext || window.webkitAudioContext)(); } catch (e) { ctx = null; } } }
        function ruido(seg) {
            const b = ctx.createBuffer(1, Math.floor(ctx.sampleRate * seg), ctx.sampleRate), d = b.getChannelData(0);
            for (let i = 0; i < d.length; i++) d[i] = Math.random() * 2 - 1;
            const s = ctx.createBufferSource(); s.buffer = b; return s;
        }
        function golpeSeco(frec, seg, vol) {
            if (!ctx) return;
            const s = ruido(seg), f = ctx.createBiquadFilter(), g = ctx.createGain();
            f.type = 'lowpass'; f.frequency.value = frec; g.gain.setValueAtTime(vol, ctx.currentTime); g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + seg);
            s.connect(f); f.connect(g); g.connect(ctx.destination); s.start();
        }
        return {
            iniciar: iniciar,
            disparo: function () { golpeSeco(2200, 0.35, 0.7); },
            golpe: function () { golpeSeco(300, 0.25, 0.9); },
            puerta: function () { golpeSeco(600, 0.4, 0.4); },
            motores: function (seg) {   // camiones en la calle: ruido grave, sin silbato
                if (!ctx) return;
                const s = ruido(seg), f = ctx.createBiquadFilter(), g = ctx.createGain();
                f.type = 'lowpass'; f.frequency.value = 120; g.gain.setValueAtTime(0.0, ctx.currentTime); g.gain.linearRampToValueAtTime(0.45, ctx.currentTime + seg * 0.5); g.gain.linearRampToValueAtTime(0.0, ctx.currentTime + seg);
                s.connect(f); f.connect(g); g.connect(ctx.destination); s.start();
            },
            tren: function (seg) {
                if (!ctx) return;
                const s = ruido(seg), f = ctx.createBiquadFilter(), g = ctx.createGain();
                f.type = 'lowpass'; f.frequency.value = 180; g.gain.setValueAtTime(0.0, ctx.currentTime); g.gain.linearRampToValueAtTime(0.5, ctx.currentTime + seg * 0.6); g.gain.linearRampToValueAtTime(0.05, ctx.currentTime + seg);
                s.connect(f); f.connect(g); g.connect(ctx.destination); s.start();
                const o = ctx.createOscillator(), og = ctx.createGain(); o.type = 'triangle'; o.frequency.value = 523;
                og.gain.setValueAtTime(0, ctx.currentTime); og.gain.linearRampToValueAtTime(0.12, ctx.currentTime + 0.1); og.gain.setValueAtTime(0.12, ctx.currentTime + 0.9); og.gain.linearRampToValueAtTime(0, ctx.currentTime + 1.1);
                o.connect(og); og.connect(ctx.destination); o.start(); o.stop(ctx.currentTime + 1.2);   // el silbato
            },
            telefono: function (seg) {
                if (!ctx) return;
                for (let k = 0; k < Math.floor(seg / 1.5); k++) {
                    const o = ctx.createOscillator(), g = ctx.createGain(), t0 = ctx.currentTime + k * 1.5;
                    o.frequency.value = 440; g.gain.setValueAtTime(0, t0); g.gain.linearRampToValueAtTime(0.18, t0 + 0.02);
                    for (let j = 0; j < 8; j++) g.gain.setValueAtTime(j % 2 ? 0.02 : 0.18, t0 + j * 0.05);
                    g.gain.setValueAtTime(0, t0 + 0.8); o.connect(g); g.connect(ctx.destination); o.start(t0); o.stop(t0 + 0.85);
                }
            },
            lluvia: function (si) {
                if (!ctx) return;
                if (si && !lluviaNodo) {
                    const s = ruido(2), f = ctx.createBiquadFilter(), g = ctx.createGain();
                    s.loop = true; f.type = 'highpass'; f.frequency.value = 1800; g.gain.value = 0.06; s.connect(f); f.connect(g); g.connect(ctx.destination); s.start(); lluviaNodo = s;
                } else if (!si && lluviaNodo) { lluviaNodo.stop(); lluviaNodo = null; }
            },
            tension: function (si) {
                if (!ctx) return;
                if (si && !tensionNodos) {
                    const o = ctx.createOscillator(), o2 = ctx.createOscillator(), g = ctx.createGain(), lfo = ctx.createOscillator(), lg = ctx.createGain();
                    o.type = 'sawtooth'; o.frequency.value = 55; o2.type = 'sine'; o2.frequency.value = 82.4; g.gain.value = 0.05;
                    lfo.frequency.value = 2; lg.gain.value = 0.03; lfo.connect(lg); lg.connect(g.gain);
                    const f = ctx.createBiquadFilter(); f.type = 'lowpass'; f.frequency.value = 400;
                    o.connect(f); o2.connect(f); f.connect(g); g.connect(ctx.destination); o.start(); o2.start(); lfo.start(); tensionNodos = [o, o2, lfo];
                } else if (!si && tensionNodos) { tensionNodos.forEach(function (n) { n.stop(); }); tensionNodos = null; }
            },
        };
    })();

    /* ---------- estado ---------- */
    let DATOS = null, GUION = null, DECISIONES = {}, escena = null;
    let actores = {}, props = {}, marcas = {}, rutas = {}, colision = [], cols = 24, filas = 16, propiedades = {};
    let jugador = null, control = false, accionPendiente = null, destino = null;
    const teclas = {};
    let oscuridad = null, lluvia = null, conos = null, noche = false, movimientos = {};

    /* ---------- utilidades del mapa ---------- */
    function libreCelda(c, f) { return c >= 0 && f >= 0 && c < cols && f < filas && !colision[f * cols + c]; }
    function libre(x, y) {
        return [[-10, 0], [10, 0], [-10, -8], [10, -8]].every(function (d) { return libreCelda(Math.floor((x + d[0]) / T), Math.floor((y + d[1]) / T)); });
    }
    function lugar(nombre) {
        if (actores[nombre]) return { x: actores[nombre].x, y: actores[nombre].y };
        if (props[nombre]) return { x: props[nombre].x, y: props[nombre].y };
        return marcas[nombre];
    }
    function hayVista(x0, y0, x1, y1) {
        const pasos = Math.ceil(Math.hypot(x1 - x0, y1 - y0) / 10);
        for (let i = 1; i < pasos; i++) {
            const x = x0 + (x1 - x0) * i / pasos, y = y0 + (y1 - y0) * i / pasos;
            if (!libreCelda(Math.floor(x / T), Math.floor((y - 10) / T))) return false;
        }
        return true;
    }
    function caminoBFS(desde, hasta) {
        const ini = [Math.floor(desde.x / T), Math.floor(desde.y / T)], fin = [Math.floor(hasta.x / T), Math.floor(hasta.y / T)];
        const prev = {}, cola = [ini], clave = function (p) { return p[0] + ',' + p[1]; };
        prev[clave(ini)] = null;
        while (cola.length) {
            const p = cola.shift();
            if (p[0] === fin[0] && p[1] === fin[1]) break;
            [[1, 0], [-1, 0], [0, 1], [0, -1]].forEach(function (d) {
                const q = [p[0] + d[0], p[1] + d[1]];
                if (libreCelda(q[0], q[1]) && !(clave(q) in prev)) { prev[clave(q)] = p; cola.push(q); }
            });
        }
        if (!(clave(fin) in prev)) return null;
        const camino = []; let p = fin;
        while (p && !(p[0] === ini[0] && p[1] === ini[1])) { camino.unshift(p); p = prev[clave(p)]; }
        return camino.map(function (c) { return { x: c[0] * T + T / 2, y: c[1] * T + T / 2 + 10 }; });
    }

    /* ---------- actores ---------- */
    function crearActor(id, pos) {
        if (actores[id]) actores[id].sprite.destroy();
        const personaje = id.split('_')[0];
        const a = { id: id, personaje: personaje, x: pos.x, y: pos.y, dir: 2, andando: false,
            sprite: escena.add.sprite(pos.x, pos.y, 'pj_' + personaje, 0).setOrigin(0.5, 1).setDepth(pos.y) };
        actores[id] = a;
        return a;
    }
    function dirHacia(dx, dy) { return Math.abs(dx) > Math.abs(dy) ? (dx > 0 ? 1 : 3) : (dy > 0 ? 2 : 0); }
    function dibujarActor(a, tiempo) {
        const paso = a.andando ? 1 + Math.floor(tiempo / 150) % 2 : 0;
        a.sprite.setPosition(Math.round(a.x), Math.round(a.y)).setDepth(a.y).setFrame(FILA[a.dir] * 3 + paso);
    }
    function moverHacia(obj, x, y, seg) {
        return new Promise(function (resolver) {
            const x0 = obj.x, y0 = obj.y, t0 = performance.now(), dur = Math.max(0.01, seg) * 1000 / (AUTO() ? 6 : 1);
            if (obj.sprite && obj.personaje) { obj.dir = dirHacia(x - x0, y - y0); obj.andando = true; }
            if (obj.imagen) obj.imagen.setFlipX(x < x0);
            (function cuadro() {
                const f = Math.min(1, (performance.now() - t0) / dur);
                obj.x = x0 + (x - x0) * f; obj.y = y0 + (y - y0) * f;
                if (obj.imagen) obj.imagen.setPosition(Math.round(obj.x), Math.round(obj.y)).setDepth(obj.y);
                if (f < 1) requestAnimationFrame(cuadro); else { obj.andando = false; resolver(); }
            })();
        });
    }

    /* ---------- escenas ---------- */
    function limpiar() {
        Object.values(actores).forEach(function (a) { a.sprite.destroy(); });
        Object.values(props).forEach(function (p) { p.imagen.destroy(); });
        actores = {}; props = {}; marcas = {}; rutas = {}; movimientos = {};
        if (escena.capas) escena.capas.forEach(function (c) { c.destroy(); });
        if (lluvia) { lluvia.destroy(); lluvia = null; }
        if (conos) conos.clear();
        jugador = null; control = false; destino = null;
        Sonido.lluvia(false);
    }
    function cargarEscena(p) {
        limpiar();
        const datos = DATOS.mapas[p.mapa];
        if (escena.cache.tilemap.exists(p.mapa)) escena.cache.tilemap.remove(p.mapa);
        escena.cache.tilemap.add(p.mapa, { format: Phaser.Tilemaps.Formats.TILED_JSON, data: datos });
        const mapa = escena.make.tilemap({ key: p.mapa });
        const ts = mapa.addTilesetImage('campana', 'tileset_campana');
        escena.capas = [mapa.createLayer('suelo', ts, 0, 0).setDepth(-2), mapa.createLayer('decoracion', ts, 0, 0).setDepth(-1)];
        cols = datos.width; filas = datos.height;
        colision = datos.layers.find(function (l) { return l.name === 'colision'; }).data.map(function (v) { return v ? 1 : 0; });
        propiedades = {}; (datos.properties || []).forEach(function (x) { propiedades[x.name] = x.value; });
        datos.layers.find(function (l) { return l.name === 'objetos'; }).objects.forEach(function (o) {
            const pr = {}; (o.properties || []).forEach(function (x) { pr[x.name] = x.value; });
            if (o.type === 'marca' || o.type === 'actor' || o.type === 'luz') marcas[o.name] = { x: o.x, y: o.y, tipo: o.type };
            if (o.type === 'ruta') rutas[o.name] = { puntos: JSON.parse(pr.puntos), modo: pr.modo };
            if (o.type === 'prop' && (p.props || []).indexOf(o.name) >= 0) {
                props[o.name] = { x: o.x, y: o.y, imagen: escena.add.image(o.x, o.y, pr.imagen).setOrigin(0.5, 1).setDepth(o.y) };
            }
        });
        (p.actores || []).forEach(function (id) { if (marcas[id]) crearActor(id, marcas[id]); });
        if (p.jugador) { jugador = crearActor(p.jugador, marcas[p.jugador]); }
        noche = !!propiedades.noche;
        if (propiedades.lluvia) {
            lluvia = escena.add.particles(0, 0, 'gota', { x: { min: -40, max: 1000 }, y: -20, lifespan: 1100, speedY: { min: 520, max: 700 }, speedX: -70, quantity: 4, frequency: 16, alpha: { start: 0.55, end: 0.2 } })
                .setScrollFactor(0).setDepth(50000);
            Sonido.lluvia(true);
        }
        const cam = escena.cameras.main;
        cam.setBounds(0, 0, cols * T, filas * T); cam.setZoom(1);
        if (jugador) cam.startFollow(jugador.sprite, true, 0.12, 0.12); else { cam.stopFollow(); cam.centerOn(cols * T / 2, filas * T / 2); }
    }

    /* ---------- interfaz ---------- */
    function fundido(a, seg) {
        const f = $('fundido');
        f.style.transitionDuration = (seg * (AUTO() ? 0.15 : 1)) + 's';
        f.style.opacity = a === 'negro' ? '1' : '0';
        return dormir(seg);
    }
    function franjas(si) { B.classList.toggle('franjas-visibles', !!si); }
    function esperarSeguir(minimo) {
        return new Promise(function (resolver) {
            const desde = performance.now();
            function listo(ev) {
                if (ev && ev.type === 'keydown' && [' ', 'Enter', 'e', 'E'].indexOf(ev.key) < 0) return;
                if (performance.now() - desde < minimo) return;
                document.removeEventListener('keydown', listo); $('dialogo').removeEventListener('click', listo); $('tarjeta').removeEventListener('click', listo);
                resolver();
            }
            document.addEventListener('keydown', listo); $('dialogo').addEventListener('click', listo); $('tarjeta').addEventListener('click', listo);
            if (AUTO()) setTimeout(function () { listo(null); }, Math.max(150, minimo + 60));   // respeta la espera mínima
        });
    }
    async function tarjeta(p) {
        $('tarjeta-texto').textContent = t(p.texto);
        $('tarjeta').className = 'tarjeta' + (p.estilo === 'documento' ? ' documento' : '');
        $('tarjeta').hidden = false;
        await Promise.race([dormir(p.segundos || 3), esperarSeguir(800)]);
        $('tarjeta').hidden = true;
    }
    async function dialogo(p) {
        const personaje = p.quien.split('_')[0];
        if (p.sinEsperar) {
            $('dialogo-retrato').src = 'img/campana/retratos/' + personaje + '_' + (p.emocion || 'neutral') + '.png';
            $('dialogo-nombre').textContent = t(GUION.personajes[personaje] || personaje);
            $('dialogo-texto').textContent = t(p.texto); $('dialogo-seguir').hidden = true; $('dialogo').hidden = false;
            return;
        }
        $('dialogo-seguir').hidden = false;
        $('dialogo-retrato').src = 'img/campana/retratos/' + personaje + '_' + (p.emocion || 'neutral') + '.png';
        $('dialogo-retrato').alt = t(GUION.personajes[personaje] || personaje);
        $('dialogo-nombre').textContent = t(GUION.personajes[personaje] || personaje);
        $('dialogo').hidden = false;
        const texto = t(p.texto), caja = $('dialogo-texto');
        let mostrado = 0, saltar = false;
        const saltarFn = function (ev) { if (!ev || ev.type !== 'keydown' || [' ', 'Enter'].indexOf(ev.key) >= 0) saltar = true; };
        document.addEventListener('keydown', saltarFn); $('dialogo').addEventListener('click', saltarFn);
        while (mostrado < texto.length && !saltar && !AUTO()) { mostrado++; caja.textContent = texto.slice(0, mostrado); await new Promise(function (r) { setTimeout(r, 22); }); }
        document.removeEventListener('keydown', saltarFn); $('dialogo').removeEventListener('click', saltarFn);
        caja.textContent = texto;
        await esperarSeguir(250);
        $('dialogo').hidden = true;
    }
    function elegir(p) {
        return new Promise(function (resolver) {
            $('eleccion-pregunta').textContent = t(p.pregunta);
            const caja = $('eleccion-opciones'); caja.textContent = '';
            p.opciones.forEach(function (o, i) {
                const b = document.createElement('button'); b.type = 'button'; b.textContent = t(o.texto);
                b.addEventListener('click', async function () {
                    $('eleccion').hidden = true; DECISIONES[p.clave] = o.valor;
                    await post({ accion: 'decidir', clave: p.clave, valor: o.valor });
                    resolver();
                });
                caja.appendChild(b);
                if (i === 0) setTimeout(function () { b.focus(); }, 50);
            });
            $('eleccion').hidden = false;
            if (AUTO()) setTimeout(function () {
                // pruebas: window.__campanaOpciones = { clave: valor } elige esa opción (si no, la primera)
                const quiero = window.__campanaOpciones && window.__campanaOpciones[p.clave];
                const i = quiero ? Math.max(0, p.opciones.findIndex(function (o) { return o.valor === quiero; })) : 0;
                caja.querySelectorAll('button')[i].click();
            }, 150);
        });
    }
    function objetivo(texto) { $('objetivo').textContent = texto ? '◆ ' + t(texto) : ''; $('objetivo').hidden = !texto; }
    async function mensaje(texto) { $('mensaje').textContent = t(texto); $('mensaje').hidden = false; await dormir(1.4); $('mensaje').hidden = true; }

    /* ---------- control del jugador ---------- */
    function esperarCondicion(cond) {
        return new Promise(function (resolver) {
            (function revisar() { if (cond()) resolver(); else requestAnimationFrame(revisar); })();
        });
    }
    function moverJugador(dt) {
        if (!jugador || !control) { if (jugador) jugador.andando = false; return; }
        let dx = (teclas.ArrowRight || teclas.d ? 1 : 0) - (teclas.ArrowLeft || teclas.a ? 1 : 0);
        let dy = (teclas.ArrowDown || teclas.s ? 1 : 0) - (teclas.ArrowUp || teclas.w ? 1 : 0);
        if (!dx && !dy && destino) {
            const ddx = destino.x - jugador.x, ddy = destino.y - jugador.y, d = Math.hypot(ddx, ddy);
            if (d < 6) destino = null; else { dx = ddx / d; dy = ddy / d; }
        }
        if (!dx && !dy) { jugador.andando = false; return; }
        const n = Math.hypot(dx, dy), paso = VEL * dt;
        const nx = jugador.x + dx / n * paso, ny = jugador.y + dy / n * paso;
        let movio = false;
        if (libre(nx, jugador.y)) { jugador.x = nx; movio = true; }
        if (libre(jugador.x, ny)) { jugador.y = ny; movio = true; }
        if (!movio) destino = null;
        jugador.dir = dirHacia(dx, dy); jugador.andando = movio;
    }
    function cerca(nombre, radio) { const l = lugar(nombre); return jugador && l && Math.hypot(jugador.x - l.x, jugador.y - l.y) <= radio; }

    /* Cartelito «E ...»: se muestra solo mientras estás cerca (como «Hablar con...» en Berlín). */
    let cartel = null;   // { nombre, radio, accion }
    function revisarCartel() {
        const visible = !!(cartel && control && cerca(cartel.nombre, cartel.radio));
        if (visible) $('indicacion-texto').textContent = t(cartel.accion);
        if ($('indicacion').hidden === visible) $('indicacion').hidden = !visible;
    }
    $('indicacion').addEventListener('click', function () { if (accionPendiente) accionPendiente(); });
    /** Espera a que el jugador, estando cerca, apriete E (o toque el cartelito). */
    function esperarAccion(nombre, radio, accion) {
        cartel = { nombre: nombre, radio: radio, accion: accion };
        return new Promise(function (resolver) {
            accionPendiente = function () { if (cerca(nombre, radio)) { accionPendiente = null; cartel = null; $('indicacion').hidden = true; resolver(); } };
            if (AUTO()) setTimeout(function () { accionPendiente && accionPendiente(); }, 120);
        });
    }

    async function pasoObjetivo(p) {
        control = true; objetivo(p.texto);
        if (AUTO()) { const l = lugar(p.zona); jugador.x = l.x + 30; jugador.y = l.y + 10; }
        if (p.accion) await esperarAccion(p.zona, p.radio || 70, p.accion);   // con persona: hay que apretar E, como en Berlín
        else await esperarCondicion(function () { return cerca(p.zona, p.radio || 70); });
        control = false; objetivo(null);
    }
    async function pasoInteractuar(p) {
        control = true; objetivo(p.texto);
        if (AUTO()) { const l = lugar(p.objeto); jugador.x = l.x; jugador.y = l.y + 40; }
        await esperarAccion(p.objeto, 70, p.accion || p.texto);
        if (!p.quedarse && props[p.objeto]) { props[p.objeto].imagen.destroy(); delete props[p.objeto]; }
        Sonido.puerta();
        control = false; objetivo(null);
    }

    /* Sigilo: los guardias patrullan con un cono de visión; si te ven, volvés al principio del tramo. */
    function conoDe(g) { const ang = [-Math.PI / 2, 0, Math.PI / 2, Math.PI][g.dir]; return { ang: ang, alcance: 150, apertura: Math.PI / 5 }; }
    function teVe(g) {
        const c = conoDe(g), dx = jugador.x - g.x, dy = jugador.y - g.y, d = Math.hypot(dx, dy);
        if (d > c.alcance) return false;
        let dif = Math.atan2(dy, dx) - c.ang; while (dif > Math.PI) dif -= 2 * Math.PI; while (dif < -Math.PI) dif += 2 * Math.PI;
        return Math.abs(dif) <= c.apertura && hayVista(g.x, g.y, jugador.x, jugador.y);
    }
    function patrullar(g, dt) {
        const r = rutas['ruta_' + g.id]; if (!r) return;
        g.objetivo = g.objetivo || 1; g.sentido = g.sentido || 1;
        const meta = r.puntos[g.objetivo], dx = meta[0] - g.x, dy = meta[1] - g.y, d = Math.hypot(dx, dy), paso = 70 * dt;
        if (d <= paso) {
            g.x = meta[0]; g.y = meta[1];
            if (r.modo === 'ida_vuelta') { if (g.objetivo + g.sentido < 0 || g.objetivo + g.sentido >= r.puntos.length) g.sentido *= -1; g.objetivo += g.sentido; }
            else g.objetivo = (g.objetivo + 1) % r.puntos.length;
        } else { g.x += dx / d * paso; g.y += dy / d * paso; g.dir = dirHacia(dx, dy); }
        g.andando = true;
    }
    function dibujarConos(guardias) {
        conos.clear();
        guardias.forEach(function (g) {
            const c = conoDe(g);
            conos.fillStyle(0xffe08a, 0.16); conos.slice(g.x, g.y - 20, c.alcance, c.ang - c.apertura, c.ang + c.apertura, false); conos.fillPath();
        });
    }
    async function pasoSigilo(p) {
        const guardias = p.guardias.map(function (id) { return actores[id]; });
        const inicio = { x: jugador.x, y: jugador.y }, inicioG = guardias.map(function (g) { return { x: g.x, y: g.y, dir: g.dir }; });
        Sonido.tension(true); objetivo(p.texto); control = true;
        let ultimo = performance.now(), visto = false;
        await new Promise(function (resolver) {
            (function cuadro() {
                const ahora = performance.now(), dt = Math.min(0.05, (ahora - ultimo) / 1000); ultimo = ahora;
                if (!visto) {
                    guardias.forEach(function (g) { patrullar(g, dt); });
                    dibujarConos(guardias);
                    if (AUTO()) { const l = lugar(p.meta); jugador.x = l.x; jugador.y = l.y; }
                    if (cerca(p.meta, p.radio || 40)) {
                        conos.clear();
                        if (p.registra) { DECISIONES[p.registra] = 'no'; post({ accion: 'decidir', clave: p.registra, valor: 'no' }).then(resolver); return; }
                        resolver(); return;
                    }
                    if (guardias.some(teVe) && p.registra) {
                        visto = true; control = false; conos.clear();
                        escena.cameras.main.flash(300, 160, 20, 30);
                        DECISIONES[p.registra] = 'si';
                        post({ accion: 'decidir', clave: p.registra, valor: 'si' }).then(function () { return mensaje('¡Te descubrieron!'); }).then(resolver);
                        return;
                    }
                    if (guardias.some(teVe)) {
                        visto = true; control = false;
                        escena.cameras.main.flash(300, 160, 20, 30);
                        mensaje('¡Te vieron!').then(function () {
                            jugador.x = inicio.x; jugador.y = inicio.y;
                            guardias.forEach(function (g, i) { g.x = inicioG[i].x; g.y = inicioG[i].y; g.dir = inicioG[i].dir; g.objetivo = 1; g.sentido = 1; });
                            visto = false; control = true;
                        });
                    }
                }
                requestAnimationFrame(cuadro);
            })();
        });
        guardias.forEach(function (g) { g.andando = false; });
        Sonido.tension(false); objetivo(null); control = false;
    }
    /* Persecución: los perseguidores te buscan esquivando obstáculos; si te alcanzan, volvés al principio del tramo. */
    async function pasoPersecucion(p) {
        const perseguidores = p.perseguidores.map(function (id) { return actores[id]; });
        const inicio = { x: jugador.x, y: jugador.y }, inicioP = perseguidores.map(function (g) { return { x: g.x, y: g.y }; });
        Sonido.tension(true); objetivo(p.texto); control = true;
        let ultimo = performance.now(), empezo = performance.now(), atrapado = false, recalculo = 0;
        await new Promise(function (resolver) {
            (function cuadro() {
                const ahora = performance.now(), dt = Math.min(0.05, (ahora - ultimo) / 1000); ultimo = ahora;
                if (!atrapado) {
                    if (AUTO()) { const l = lugar(p.meta); jugador.x = l.x; jugador.y = l.y; }
                    if (cerca(p.meta, p.radio || 50)) { resolver(); return; }
                    if ((ahora - empezo) / 1000 > (p.retraso || 1)) {
                        recalculo -= dt;
                        perseguidores.forEach(function (g) {
                            if (recalculo <= 0 || !g.camino || !g.camino.length) g.camino = caminoBFS(g, jugador) || [];
                            const sig = g.camino[0] || jugador, dx = sig.x - g.x, dy = sig.y - g.y, d = Math.hypot(dx, dy), paso = VEL * (p.velocidad || 0.85) * dt;
                            if (d <= paso) { g.x = sig.x; g.y = sig.y; g.camino.shift(); } else { g.x += dx / d * paso; g.y += dy / d * paso; g.dir = dirHacia(dx, dy); }
                            g.andando = true;
                        });
                        if (recalculo <= 0) recalculo = 0.3;
                        if (perseguidores.some(function (g) { return Math.hypot(g.x - jugador.x, g.y - jugador.y) < 20; })) {
                            atrapado = true; control = false;
                            escena.cameras.main.flash(300, 160, 20, 30);
                            mensaje('¡Te alcanzaron!').then(function () {
                                jugador.x = inicio.x; jugador.y = inicio.y;
                                perseguidores.forEach(function (g, i) { g.x = inicioP[i].x; g.y = inicioP[i].y; g.camino = null; });
                                empezo = performance.now(); atrapado = false; control = true;
                            });
                        }
                    }
                }
                requestAnimationFrame(cuadro);
            })();
        });
        perseguidores.forEach(function (g) { g.andando = false; });
        Sonido.tension(false); objetivo(null); control = false;
    }

    async function efecto(p) {
        const cam = escena.cameras.main;
        if (p.nombre === 'golpe') { Sonido.golpe(); cam.shake(220, 0.012); await dormir(0.4); }
        else if (p.nombre === 'puerta') { Sonido.puerta(); await dormir(0.5); }
        else if (p.nombre === 'telefono') { Sonido.telefono(p.segundos || 3); await dormir(p.segundos || 3); }
        else if (p.nombre === 'motores') { Sonido.motores(p.segundos || 2); await dormir(p.segundos || 2); }
        else if (p.nombre === 'tren') { Sonido.tren(p.segundos || 3); await dormir(Math.min(1.5, p.segundos || 3)); }
        else if (p.nombre === 'apagon') {
            const f = $('fundido'); f.style.transitionDuration = '0.08s'; f.style.opacity = '1';
            await dormir(0.6);
            for (let k = 0; k < 4; k++) { f.style.opacity = '0.82'; Sonido.disparo(); await dormir(0.06); f.style.opacity = '1'; await dormir(0.3 + Math.random() * 0.4); }
            await dormir(Math.max(0, (p.segundos || 3) - 2.4));
        }
        else if (p.nombre === 'disparos') {
            const fin = performance.now() + (p.segundos || 3) * 1000 / (AUTO() ? 6 : 1);
            while (performance.now() < fin) {
                const a = actores[p.desde[Math.floor(Math.random() * p.desde.length)]];
                if (a) {
                    const fogonazo = escena.add.circle(a.x + (a.dir === 3 ? -14 : 14), a.y - 28, 7, 0xfff1a8).setDepth(60000);
                    escena.time.delayedCall(70, function () { fogonazo.destroy(); });
                }
                Sonido.disparo(); cam.shake(90, 0.006);
                await dormir(0.18 + Math.random() * 0.3);
            }
        }
    }

    /* ---------- combate táctico por turnos (lo resuelve el servidor; acá solo se pide y se anima) ---------- */
    const px = function (c, f) { return { x: c * T + 20, y: f * T + 30 }; };
    let combate = null, elegido = null, ocupado = false, graficoCombate = null, rotulos = [];
    function tumbar(a, si) { a.caido = si; a.sprite.setAngle(si ? 90 : 0).setTint(si ? 0x777777 : 0xffffff); }
    function dibujarCombate() {
        rotulos.forEach(function (r) { r.destroy(); }); rotulos = [];
        graficoCombate.clear();
        if (!combate) return;
        const v = combate, acc = elegido && v.acciones[elegido];
        if (acc && !ocupado && v.turno === 'aliado') {
            graficoCombate.fillStyle(0x4d8dff, 0.28);
            acc.alcanzables.forEach(function (c) { graficoCombate.fillRect(c[0] * T + 2, c[1] * T + 2, T - 4, T - 4); });
            Object.keys(acc.tiros).forEach(function (b) {
                const u = v.unidades[b], p = px(u.x, u.y), pr = acc.tiros[b];
                graficoCombate.lineStyle(2, pr > 0 ? 0xff5a5a : 0x777777, 0.9); graficoCombate.strokeCircle(p.x, p.y - 22, 22);
                rotulos.push(escena.add.text(p.x, p.y - 58, pr > 0 ? pr + '%' : '—', { fontFamily: 'Verdana, sans-serif', fontSize: '13px', color: pr > 0 ? '#ffd0d0' : '#aaaaaa', backgroundColor: 'rgba(0,0,0,0.6)', padding: { x: 4, y: 1 } }).setOrigin(0.5).setDepth(45000));
            });
        }
        Object.keys(v.unidades).forEach(function (id) {
            const u = v.unidades[id], a = actores[id]; if (!a || u.caido) return;
            const ancho = 28, x = a.x - ancho / 2, y = a.y - 56;
            graficoCombate.fillStyle(0x000000, 0.7).fillRect(x - 1, y - 1, ancho + 2, 6);
            graficoCombate.fillStyle(u.lado === 'aliado' ? 0x6fd36f : 0xe05555, 1).fillRect(x, y, ancho * u.vida / u.vidaMax, 4);
            if (id === elegido) { graficoCombate.lineStyle(2, 0xf4c430, 1); graficoCombate.strokeEllipse(a.x, a.y - 3, 34, 14); }
            if (u.cubierto) rotulos.push(escena.add.text(a.x + 16, a.y - 62, '🛡', { fontSize: '12px' }).setOrigin(0.5).setDepth(45000));
        });
        const lista = $('combate-unidades'); lista.textContent = '';
        Object.keys(v.unidades).forEach(function (id) {
            const u = v.unidades[id], li = document.createElement('li');
            li.className = (u.lado === 'enemigo' ? 'enemigo' : '') + (u.caido ? ' caido' : '') + (id === elegido ? ' elegido' : '');
            const nombre = document.createElement('span'); nombre.textContent = t(GUION.personajes[id.split('_')[0]] || id) + (id.indexOf('_') > 0 ? ' ' + id.split('_')[1] : '');
            const vida = document.createElement('span'); vida.textContent = u.caido ? t('Fuera de combate') : '♥'.repeat(u.vida) + '♡'.repeat(u.vidaMax - u.vida);
            li.appendChild(nombre); li.appendChild(vida);
            if (u.lado === 'aliado' && !u.caido) li.addEventListener('click', function () { elegir_(id); });
            lista.appendChild(li);
        });
        $('combate-ronda').textContent = t('Ronda {n} de {m}', { n: Math.min(v.ronda, v.objetivo.rondas || v.ronda), m: v.objetivo.rondas || '∞' }) + ' · ' + (v.turno === 'aliado' ? t('Tu turno') : t('Turno de los rivales'));
        const puedeActuar = acc && !v.unidades[elegido].actuo && !ocupado && v.turno === 'aliado' && !v.resultado;
        $('combate-cubrirse').disabled = !puedeActuar;
        $('combate-terminar').disabled = ocupado || v.turno !== 'aliado' || !!v.resultado;
    }
    function elegir_(id) { if (combate && combate.acciones[id]) { elegido = id; dibujarCombate(); } }
    function siguienteElegible() {
        const ids = Object.keys(combate.acciones).filter(function (id) { const u = combate.unidades[id]; return !u.caido && !(u.movio && u.actuo) && !u.actuo; });
        return ids.length ? ids[(ids.indexOf(elegido) + 1) % ids.length] : (combate.acciones[elegido] ? elegido : Object.keys(combate.acciones)[0] || null);
    }
    async function animarEventos(eventos) {
        for (const ev of eventos) {
            if (ev.tipo === 'mover') {
                const a = actores[ev.unidad], d = px(ev.a[0], ev.a[1]);
                const pasos = Math.abs(ev.a[0] - ev.desde[0]) + Math.abs(ev.a[1] - ev.desde[1]);
                await moverHacia(a, d.x, d.y, 0.12 * pasos + 0.1);
            } else if (ev.tipo === 'disparo') {
                const a = actores[ev.unidad], b = actores[ev.blanco];
                a.dir = dirHacia(b.x - a.x, b.y - a.y);
                const linea = escena.add.line(0, 0, a.x, a.y - 28, b.x + (ev.acierto ? 0 : 18), b.y - (ev.acierto ? 26 : 40), 0xfff1a8, 0.85).setOrigin(0, 0).setDepth(46000);
                const fogonazo = escena.add.circle(a.x + (a.dir === 3 ? -14 : 14), a.y - 28, 7, 0xfff1a8).setDepth(46000);
                Sonido.disparo(); escena.cameras.main.shake(90, 0.004);
                await dormir(0.12); linea.destroy(); fogonazo.destroy();
                const txt = escena.add.text(b.x, b.y - 66, ev.acierto ? '−1' : t('Falló'), { fontFamily: 'Verdana, sans-serif', fontSize: '15px', fontStyle: 'bold', color: ev.acierto ? '#ff6b6b' : '#d0d6e0', stroke: '#000', strokeThickness: 4 }).setOrigin(0.5).setDepth(46000);
                if (ev.acierto) b.sprite.setTintFill(0xff4444);
                await dormir(0.18); if (ev.acierto) b.sprite.clearTint();
                if (ev.caido) tumbar(b, true);
                await dormir(0.35); txt.destroy();
            } else if (ev.tipo === 'cubrirse') {
                const a = actores[ev.unidad];
                const txt = escena.add.text(a.x, a.y - 66, '🛡 ' + t('Cubrirse'), { fontFamily: 'Verdana, sans-serif', fontSize: '12px', color: '#d0d6e0', stroke: '#000', strokeThickness: 3 }).setOrigin(0.5).setDepth(46000);
                await dormir(0.4); txt.destroy();
            } else if (ev.tipo === 'turno' && ev.lado === 'enemigo') {
                await mensaje('Turno de los rivales');
            }
        }
    }
    async function pedirCombate(datos) {
        ocupado = true; dibujarCombate();
        const r = await post(Object.assign({ combate: combate.id }, datos));
        if (!r.exito) { ocupado = false; dibujarCombate(); if (r.mensaje) await mensaje(r.mensaje); return; }
        await animarEventos(r.combate.eventos || []);
        combate = r.combate; ocupado = false;
        if (!combate.acciones[elegido] || combate.unidades[elegido].actuo) elegido = siguienteElegible();
        dibujarCombate();
    }
    async function pasoCombate(p) {
        control = false; objetivo(p.texto);
        graficoCombate = graficoCombate || escena.add.graphics().setDepth(44000);
        const empezar = async function () {
            const r = await post({ accion: 'combate_iniciar', combate: p.id });
            if (!r.exito) { await mensaje(r.mensaje || 'No se pudo empezar el combate.'); return false; }
            combate = r.combate;
            await Promise.all(Object.keys(combate.unidades).map(function (id) {
                const u = combate.unidades[id], d = px(u.x, u.y);
                if (!actores[id]) crearActor(id, d);
                tumbar(actores[id], false);
                return moverHacia(actores[id], d.x, d.y, 0.4);
            }));
            elegido = combate.lider;
            $('combate-objetivo').textContent = t(p.texto);
            $('combate-reintentar').hidden = true; $('combate-terminar').hidden = false; $('combate-cubrirse').hidden = false;
            $('combate').hidden = false; dibujarCombate();
            return true;
        };
        if (!(await empezar())) return;
        await new Promise(function (resolver) {
            const alClic = function (ptr) {
                if (!combate || ocupado || combate.turno !== 'aliado' || combate.resultado) return;
                const c = Math.floor(ptr.worldX / T), f = Math.floor(ptr.worldY / T);
                const enCelda = Object.keys(combate.unidades).find(function (id) { const u = combate.unidades[id]; return !u.caido && u.x === c && u.y === f; });
                const acc = combate.acciones[elegido];
                if (enCelda && combate.unidades[enCelda].lado === 'aliado') { elegir_(enCelda); return; }
                if (acc && enCelda && combate.unidades[enCelda].lado === 'enemigo' && (acc.tiros[enCelda] || 0) > 0) { pedirCombate({ accion: 'combate_accion', unidad: elegido, tipo: 'disparar', blanco: enCelda }); return; }
                if (acc && acc.alcanzables.some(function (x) { return x[0] === c && x[1] === f; })) pedirCombate({ accion: 'combate_accion', unidad: elegido, tipo: 'mover', x: c, y: f });
            };
            const cubrirse = function () { if (!$('combate-cubrirse').disabled) pedirCombate({ accion: 'combate_accion', unidad: elegido, tipo: 'cubrirse' }); };
            const terminar = function () { if (!$('combate-terminar').disabled) pedirCombate({ accion: 'combate_turno' }); };
            const teclasCombate = function (ev) {
                if (ev.key === 'c' || ev.key === 'C') cubrirse();
                else if (ev.key === 't' || ev.key === 'T') terminar();
                else if (ev.key === 'Tab') { ev.preventDefault(); elegir_(siguienteElegible()); }
            };
            const reintentar = async function () { $('combate-reintentar').hidden = true; await empezar(); revisar(); };
            escena.input.on('pointerdown', alClic);
            document.addEventListener('keydown', teclasCombate);
            $('combate-cubrirse').onclick = cubrirse; $('combate-terminar').onclick = terminar; $('combate-reintentar').onclick = reintentar;
            async function revisar() {
                if (!combate) return;
                if (combate.resultado === 'victoria') {
                    escena.input.off('pointerdown', alClic); document.removeEventListener('keydown', teclasCombate);
                    await mensaje('¡Lo lograste!');
                    $('combate').hidden = true; combate = null; dibujarCombate(); objetivo(null);
                    resolver(); return;
                }
                if (combate.resultado === 'derrota') {
                    await mensaje('Viktor cayó');
                    $('combate-reintentar').hidden = false; $('combate-terminar').hidden = true; $('combate-cubrirse').hidden = true;
                    if (AUTO()) reintentar();
                    return;
                }
                if (AUTO() && !ocupado && combate.turno === 'aliado') {   // pruebas: dispara al mejor tiro o se cubre, y termina el turno
                    const id = Object.keys(combate.acciones).find(function (k) { return !combate.unidades[k].actuo && !combate.unidades[k].caido; });
                    if (id) {
                        const u = combate.unidades[id], acc = combate.acciones[id], tiros = acc.tiros;
                        const mejor = Object.keys(tiros).sort(function (a, b) { return tiros[b] - tiros[a]; })[0];
                        elegido = id;
                        if (!u.movio && acc.alcanzables.length && !(mejor && tiros[mejor] >= 45)) {
                            // se acerca al rival más cercano antes de disparar (como haría una persona)
                            const rivales = Object.keys(combate.unidades).filter(function (k) { return combate.unidades[k].lado === 'enemigo' && !combate.unidades[k].caido; });
                            const r = combate.unidades[rivales.sort(function (a, b) { const A = combate.unidades[a], B = combate.unidades[b]; return (Math.abs(A.x - u.x) + Math.abs(A.y - u.y)) - (Math.abs(B.x - u.x) + Math.abs(B.y - u.y)); })[0]];
                            const destino = acc.alcanzables.slice().sort(function (a, b) { return (Math.abs(a[0] - r.x) + Math.abs(a[1] - r.y)) - (Math.abs(b[0] - r.x) + Math.abs(b[1] - r.y)); })[0];
                            await pedirCombate({ accion: 'combate_accion', unidad: id, tipo: 'mover', x: destino[0], y: destino[1] });
                        } else {
                            await pedirCombate(mejor && tiros[mejor] >= 25 ? { accion: 'combate_accion', unidad: id, tipo: 'disparar', blanco: mejor } : { accion: 'combate_accion', unidad: id, tipo: 'cubrirse' });
                        }
                    } else await pedirCombate({ accion: 'combate_turno' });
                }
                setTimeout(revisar, 120);
            }
            revisar();
        });
    }

    /* ---------- elegir con tiempo (interrogatorio y persecución) ---------- */
    function elegirConTiempo(pregunta, opciones, segundos, medidor) {
        return new Promise(function (resolver) {
            $('eleccion-pregunta').textContent = t(pregunta);
            const caja = $('eleccion-opciones'); caja.textContent = '';
            const fin = performance.now() + segundos * 1000, barra = $('eleccion-cuenta-barra');
            $('eleccion-cuenta').hidden = false;
            $('eleccion-medidor').hidden = !medidor; if (medidor) $('eleccion-medidor').textContent = medidor;
            let listo = false;
            const terminar = function (i) { if (listo) return; listo = true; $('eleccion').hidden = true; $('eleccion-cuenta').hidden = true; resolver(i); };
            opciones.forEach(function (o, i) {
                const b = document.createElement('button'); b.type = 'button'; b.textContent = t(o.texto);
                b.addEventListener('click', function () { terminar(i); });
                caja.appendChild(b);
                if (i === 0) setTimeout(function () { b.focus(); }, 50);
            });
            $('eleccion').hidden = false;
            (function reloj() {
                if (listo) return;
                const queda = Math.max(0, fin - performance.now());
                barra.style.width = (queda / (segundos * 1000) * 100) + '%';
                if (queda <= 0) terminar(-1); else requestAnimationFrame(reloj);
            })();
            if (AUTO()) {   // pruebas: la opción más segura
                setTimeout(function () {
                    let mejor = 0; opciones.forEach(function (o, i) { if ((o.sospecha ?? o.riesgo) < (opciones[mejor].sospecha ?? opciones[mejor].riesgo)) mejor = i; });
                    terminar(mejor);
                }, 150);
            }
        });
    }

    /* Una partida de Draft Der Mauer dentro de la historia: se juega en la página de partidas y se vuelve acá. */
    async function pasoPartida(p) {
        let est = await post({ accion: 'partida_estado', paso: p.id });
        while (est.estado !== 'ganada') {
            await dialogo({ quien: p.quien, emocion: est.estado === 'perdida' ? 'enojado' : 'neutral', texto: est.estado === 'perdida' ? p.perdiste : (est.estado === 'en_juego' ? p.enJuego : p.invitacion) });
            objetivo(p.texto);
            await new Promise(function (resolver) {
                $('eleccion-pregunta').textContent = t(p.texto);
                const caja = $('eleccion-opciones'); caja.textContent = '';
                const b = document.createElement('button'); b.type = 'button';
                b.textContent = '🎲 ' + (est.estado === 'en_juego' ? t('Volver a la partida') : t('Sentarse a jugar'));
                b.addEventListener('click', function () { $('eleccion').hidden = true; resolver(); });
                caja.appendChild(b); $('eleccion').hidden = false; setTimeout(function () { b.focus(); }, 50);
                if (AUTO()) setTimeout(function () { b.click(); }, 150);
            });
            const r = await post({ accion: 'partida_iniciar', paso: p.id });
            if (!r.exito) { await mensaje(r.mensaje); est = await post({ accion: 'partida_estado', paso: p.id }); continue; }
            window.__campanaPartida = r.idPartida;
            if (!AUTO()) { window.location.href = B.dataset.partida + '?id=' + r.idPartida; await new Promise(function () {}); }
            // pruebas: se espera a que la partida termine (la prueba la cierra) sin salir de la página
            do { await dormir(6); est = await post({ accion: 'partida_estado', paso: p.id }); } while (est.estado === 'en_juego');
        }
        objetivo(null);
    }

    /* Interrogatorio: cada respuesta suma sospecha (no contestar a tiempo, también). Lo decide el servidor. */
    async function pasoInterrogatorio(p) {
        objetivo(p.texto);
        for (;;) {
            const respuestas = []; let sospecha = 0;
            for (const q of p.preguntas) {
                await dialogo({ quien: p.quien, emocion: 'neutral', texto: q.texto, sinEsperar: true });
                const i = await elegirConTiempo(q.texto, q.opciones, p.segundos, t(p.medidor || 'Sospecha: {s} de {l}', { s: sospecha, l: p.limite }));
                $('dialogo').hidden = true;
                respuestas.push(i);
                sospecha += i === -1 ? p.sinRespuesta : q.opciones[i].sospecha;
                if (i === -1) await mensaje('Tardaste en contestar');
            }
            const r = await post({ accion: 'interrogatorio', paso: p.id, respuestas: respuestas.join(',') });
            if (r.exito && r.superado) { await dialogo({ quien: p.quien, emocion: 'neutral', texto: p.pase }); break; }
            await dialogo({ quien: p.quien, emocion: 'enojado', texto: p.detenido });
            await mensaje(p.falla || 'Te detuvieron. Probá de nuevo.');
        }
        objetivo(null);
    }

    /* Persecución en auto: en cada tramo, una decisión con tiempo; el servidor resuelve si ganan o pierden terreno. */
    async function pasoPersecucionAuto(p) {
        const mio = props.auto, ellos = props.auto_vopo, carril = mio ? mio.y : 352;
        const ubicar = function (ventaja, seg) {
            const x = 620;
            return Promise.all([mio && moverHacia(mio, x, carril, seg), ellos && moverHacia(ellos, Math.max(60, x - 70 - ventaja * 90), carril, seg)]);
        };
        objetivo(p.texto); Sonido.tension(true);
        for (;;) {
            let e = (await post({ accion: 'persecucion_iniciar', paso: p.id })).persecucion;
            if (mio) { mio.x = -80; mio.imagen.setPosition(-80, carril); } if (ellos) { ellos.x = -300; ellos.imagen.setPosition(-300, carril); }
            await ubicar(e.ventaja, 1.2);
            while (e.resultado === null) {
                const tramo = p.tramos[e.tramo];
                const i = await elegirConTiempo(tramo.texto, tramo.opciones, p.segundos, t('Ventaja: {v}', { v: '●'.repeat(Math.max(0, e.ventaja)) || '—' }));
                const r = await post({ accion: 'persecucion_elegir', paso: p.id, opcion: i });
                if (!r.exito) { await mensaje(r.mensaje); break; }
                e = r.persecucion;
                await ubicar(e.ventaja, 0.8);
                if (!e.acierto) escena.cameras.main.shake(200, 0.008);
                await tarjeta({ texto: e.texto, segundos: 2.5 });
            }
            if (e.resultado === 'victoria') break;
            await mensaje('Los alcanzaron. Probá de nuevo.');
        }
        Sonido.tension(false); objetivo(null);
    }

    /* Cerradura: la marca va y viene; hay que apretar (E, Espacio, clic o el botón) cuando está en la zona clara. */
    async function pasoCerradura(p) {
        objetivo(p.texto);
        $('cerradura-texto').textContent = t(p.texto);
        $('cerradura').hidden = false;
        let pernos = 0, zona = 0, ancho = 22, vel = 1.6, t0 = performance.now(), pos = 0;
        const nuevaZona = function () { zona = 8 + Math.random() * (84 - ancho); $('cerradura-zona').style.left = zona + '%'; $('cerradura-zona').style.width = ancho + '%'; };
        const pintarPernos = function () { $('cerradura-pernos').textContent = t('Perno {n} de {m}', { n: pernos, m: p.pernos }); };
        nuevaZona(); pintarPernos();
        await new Promise(function (resolver) {
            let listo = false;
            const intentar = function () {
                if (listo) return;
                if (pos >= zona && pos <= zona + ancho) {
                    pernos++; Sonido.puerta(); pintarPernos();
                    if (pernos >= p.pernos) { listo = true; resolver(); return; }
                    ancho = Math.max(12, ancho - 4); vel += 0.5; nuevaZona();
                } else {
                    Sonido.golpe(); escena.cameras.main.shake(120, 0.004); mensaje('Se resbaló la ganzúa');
                }
            };
            const tecla = function (ev) { if (ev.key === 'e' || ev.key === 'E' || ev.key === ' ') { ev.preventDefault(); intentar(); } };
            document.addEventListener('keydown', tecla);
            $('cerradura-boton').onclick = intentar;
            (function mover() {
                if (listo) { document.removeEventListener('keydown', tecla); return; }
                pos = (Math.sin((performance.now() - t0) / 1000 * vel * Math.PI) + 1) / 2 * 100;
                $('cerradura-marca').style.left = pos + '%';
                if (AUTO() && pos >= zona + 2 && pos <= zona + ancho - 2) intentar();   // pruebas
                requestAnimationFrame(mover);
            })();
        });
        $('cerradura').hidden = true; objetivo(null);
    }

    /* Vestir: el personaje se cambia de ropa (pasa a ser otro actor, en el mismo lugar). */
    function vestir(p) {
        const viejo = actores[p.actor]; if (!viejo) return;
        const era = jugador === viejo;
        const nuevo = crearActor(p.como, { x: viejo.x, y: viejo.y }); nuevo.dir = viejo.dir;
        viejo.sprite.destroy(); delete actores[p.actor];
        if (era) { jugador = nuevo; escena.cameras.main.startFollow(nuevo.sprite, true, 0.12, 0.12); }
    }

    /* Agujas: el plano dibuja por dónde iría el tren; las palancas se mueven con E; la señal se da desde la garita. */
    function destinoAgujas(p, estados) {
        let nodo = p.inicio; const camino = [nodo];
        for (let i = 0; i < 20 && p.agujas[nodo]; i++) { nodo = p.agujas[nodo][estados[nodo]]; camino.push(nodo); }
        return { destino: nodo, camino: camino };
    }
    function dibujarAgujas(p, estados) {
        const svg = $('agujas-plano'), NS = 'http://www.w3.org/2000/svg', P = p.plano;
        svg.textContent = '';
        const linea = function (a, b, clase) { const l = document.createElementNS(NS, 'line'); l.setAttribute('x1', a[0]); l.setAttribute('y1', a[1]); l.setAttribute('x2', b[0]); l.setAttribute('y2', b[1]); l.setAttribute('class', clase); svg.appendChild(l); };
        const texto = function (pt, s, dx) { const tx = document.createElementNS(NS, 'text'); tx.setAttribute('x', pt[0] + (dx || 0)); tx.setAttribute('y', pt[1] + 4); tx.textContent = s; svg.appendChild(tx); };
        linea([5, P[p.inicio][1]], P[p.inicio], 'via');
        Object.keys(p.agujas).forEach(function (k) { ['recta', 'desvio'].forEach(function (lado) { linea(P[k], P[p.agujas[k][lado]], 'via'); }); });
        const r = destinoAgujas(p, estados);
        linea([5, P[p.inicio][1]], P[p.inicio], 'ruta');
        for (let i = 1; i < r.camino.length; i++) linea(P[r.camino[i - 1]], P[r.camino[i]], 'ruta');
        Object.keys(p.agujas).forEach(function (k) {
            const c = document.createElementNS(NS, 'circle'); c.setAttribute('cx', P[k][0]); c.setAttribute('cy', P[k][1]); c.setAttribute('r', 11); c.setAttribute('class', 'aguja'); svg.appendChild(c);
            texto(P[k], k, -4);
            texto([P[k][0] - 18, P[k][1] + 24], estados[k] === 'recta' ? t('recta') : t('desvío'));
        });
        Object.keys(p.vias).forEach(function (v) {
            const c = document.createElementNS(NS, 'circle'); c.setAttribute('cx', P[v][0]); c.setAttribute('cy', P[v][1]); c.setAttribute('r', 5); c.setAttribute('class', v === p.meta ? 'meta' : 'aguja'); svg.appendChild(c);
            texto(P[v], t(p.vias[v]), 10);
        });
        $('agujas-destino').textContent = t('El tren va a: {v}', { v: t(p.vias[r.destino] || r.destino) });
        return r.destino;
    }
    async function pasoAgujas(p) {
        objetivo(p.texto);
        $('agujas-titulo').textContent = t(p.texto); $('agujas-acoplada').textContent = t(p.acoplada || '');
        for (;;) {
            const estados = {}; Object.keys(p.agujas).forEach(function (k) { estados[k] = p.agujas[k].estado; });
            dibujarAgujas(p, estados);
            $('agujas').hidden = false; control = true;
            const fin = performance.now() + p.segundos * 1000 / (AUTO() ? 6 : 1);
            const resultado = await new Promise(function (resolver) {
                let listo = false;
                const mover = function (k) {
                    estados[k] = estados[k] === 'recta' ? 'desvio' : 'recta';
                    if (p.agujas[k].acopla) { const a = p.agujas[k].acopla; estados[a] = estados[a] === 'recta' ? 'desvio' : 'recta'; }
                    Sonido.golpe(); dibujarAgujas(p, estados);
                };
                (function cuadro() {
                    if (listo) return;
                    const queda = Math.max(0, Math.ceil((fin - performance.now()) / 1000));
                    $('agujas-tiempo').textContent = t('Tiempo: {s} s', { s: queda });
                    if (queda <= 0) { listo = true; cartel = null; accionPendiente = null; resolver('tiempo'); return; }
                    if (AUTO()) {   // pruebas: busca la combinación que lleva a la meta y da la señal
                        const ks = Object.keys(p.agujas);
                        for (let m = 0; m < (1 << ks.length); m++) {
                            const prueba = {}; ks.forEach(function (k, i) { prueba[k] = (m >> i) & 1 ? 'desvio' : 'recta'; });
                            if (destinoAgujas(p, prueba).destino === p.meta) { Object.assign(estados, prueba); break; }
                        }
                        dibujarAgujas(p, estados); listo = true; cartel = null; accionPendiente = null; resolver('senal'); return;
                    }
                    // la palanca (o la garita) más cercana define qué hace la E
                    let objetivoE = null;
                    Object.keys(p.agujas).forEach(function (k) { if (cerca(p.agujas[k].palanca, 60)) objetivoE = { tipo: 'aguja', k: k }; });
                    if (!objetivoE && cerca(p.garita, 70)) objetivoE = { tipo: 'senal' };
                    cartel = objetivoE ? { nombre: objetivoE.tipo === 'aguja' ? p.agujas[objetivoE.k].palanca : p.garita, radio: 70,
                        accion: objetivoE.tipo === 'aguja' ? t('Mover la aguja {a}', { a: objetivoE.k }) : t('Dar la señal') } : null;
                    accionPendiente = objetivoE ? function () {
                        if (objetivoE.tipo === 'aguja') mover(objetivoE.k);
                        else { listo = true; cartel = null; accionPendiente = null; resolver('senal'); }
                    } : null;
                    requestAnimationFrame(cuadro);
                })();
            });
            control = false;
            if (resultado === 'tiempo') { await mensaje('El tren pasó de largo. Probá de nuevo.'); continue; }
            const r = await post({ accion: 'agujas', paso: p.id, estados: Object.keys(estados).map(function (k) { return k + ':' + estados[k]; }).join(',') });
            if (r.exito && r.superado) break;
            await mensaje('El tren va a ir a la vía equivocada.');
        }
        $('agujas').hidden = true; objetivo(null);
    }

    /* Paseo: caminata libre, sin objetivos. Cerca de cada punto aparece «E · Mirar» y se juega su escena.
       Después de ver 'minimo' puntos aparece quien cierra el paseo (fin.actor) y se termina hablándole. */
    async function pasoPaseo(p) {
        objetivo(p.texto);
        const vistos = new Set(), minimo = Math.min(p.minimo || 1, p.puntos.length);
        for (;;) {
            control = true;
            const r = await new Promise(function (resolver) {
                let listo = false;
                const elegir = function (x) { listo = true; cartel = null; accionPendiente = null; resolver(x); };
                (function cuadro() {
                    if (listo) return;
                    const terminable = vistos.size >= minimo;
                    if (terminable && !actores[p.fin.actor]) { crearActor(p.fin.actor, marcas[p.fin.actor]); Sonido.puerta(); }
                    if (AUTO()) {   // pruebas: mira los puntos en orden y después termina
                        const i = p.puntos.findIndex(function (pt, k) { return !vistos.has(k); });
                        elegir(terminable && (i < 0 || vistos.size >= minimo) ? { fin: true } : { punto: i }); return;
                    }
                    let cercano = -1;
                    p.puntos.forEach(function (pt, k) { if (!vistos.has(k) && cerca(pt.objeto, 70)) cercano = k; });
                    if (cercano >= 0) {
                        const k = cercano;
                        cartel = { nombre: p.puntos[k].objeto, radio: 70, accion: t(p.puntos[k].accion) };
                        accionPendiente = function () { elegir({ punto: k }); };
                    } else if (terminable && cerca(p.fin.actor, 70)) {
                        cartel = { nombre: p.fin.actor, radio: 70, accion: t(p.fin.accion) };
                        accionPendiente = function () { elegir({ fin: true }); };
                    } else { cartel = null; accionPendiente = null; }
                    requestAnimationFrame(cuadro);
                })();
            });
            control = false;
            if (r.fin) break;
            vistos.add(r.punto);
            objetivo(null);
            for (const q of p.puntos[r.punto].pasos) await paso(q, false);
            objetivo(p.texto);
        }
        objetivo(null);
    }

    /* Seguir: el objetivo camina por su ruta y cada tanto mira hacia atrás (con el mismo cono de visión del sigilo).
       Si te ve, o si te quedás demasiado lejos, empezás de nuevo. */
    // pruebas: dónde están el jugador y los personajes
    window.__campanaPos = function () {
        const r = { jugador: jugador ? { x: jugador.x, y: jugador.y } : null, actores: {} };
        Object.keys(actores).forEach(function (k) { r.actores[k] = { x: actores[k].x, y: actores[k].y, dir: actores[k].dir }; });
        return r;
    };
    async function pasoSeguir(p) {
        const o = actores[p.objetivo], r = rutas[p.ruta];
        const ini = { x: o.x, y: o.y, dir: o.dir }, jIni = { x: jugador.x, y: jugador.y };
        for (;;) {
            o.x = ini.x; o.y = ini.y; o.dir = ini.dir; jugador.x = jIni.x; jugador.y = jIni.y;
            objetivo(p.texto); control = true;
            const res = await new Promise(function (resolver) {
                let k = 1, ultimo = performance.now(), lejosDesde = 0, mirandoHasta = 0, dirCaminando = o.dir;
                let proxMirar = ultimo + 2500 + Math.random() * 2000;
                (function cuadro() {
                    const ahora = performance.now(), dt = Math.min(0.05, (ahora - ultimo) / 1000); ultimo = ahora;
                    if (AUTO()) { const f = r.puntos[r.puntos.length - 1]; o.x = f[0]; o.y = f[1]; conos.clear(); resolver('llego'); return; }   // pruebas
                    if (ahora < mirandoHasta) {
                        o.andando = false; dibujarConos([o]);
                        if (teVe(o)) { conos.clear(); resolver('visto'); return; }
                    } else {
                        conos.clear();
                        if (mirandoHasta && o.dir !== dirCaminando) o.dir = dirCaminando;
                        if (ahora >= proxMirar) {
                            dirCaminando = o.dir; o.dir = (o.dir + 2) % 4;   // se da vuelta
                            mirandoHasta = ahora + (p.mirarSegundos || 1.5) * 1000; proxMirar = mirandoHasta + 2500 + Math.random() * 2500;
                        } else {
                            const m = r.puntos[k], dx = m[0] - o.x, dy = m[1] - o.y, d = Math.hypot(dx, dy), paso = (p.velocidad || 55) * dt;
                            if (d <= paso) { o.x = m[0]; o.y = m[1]; k++; if (k >= r.puntos.length) { resolver('llego'); return; } }
                            else { o.x += dx / d * paso; o.y += dy / d * paso; o.dir = dirHacia(dx, dy); dirCaminando = o.dir; }
                            o.andando = true;
                        }
                    }
                    if (Math.hypot(jugador.x - o.x, jugador.y - o.y) > (p.lejos || 320)) { lejosDesde = lejosDesde || ahora; if (ahora - lejosDesde > 2500) { resolver('perdido'); return; } }
                    else lejosDesde = 0;
                    requestAnimationFrame(cuadro);
                })();
            });
            control = false; o.andando = false;
            if (res === 'llego') break;
            await mensaje(res === 'visto' ? 'Te vio. Probá de nuevo.' : 'Lo perdiste. Probá de nuevo.');
        }
        objetivo(null);
    }

    /* ---------- el guion ---------- */
    async function paso(p, silencioso) {
        if (silencioso) {
            if (p.tipo === 'escena') cargarEscena(p);
            else if (p.tipo === 'franjas') franjas(p.visibles);
            else if (p.tipo === 'mover') { const l = lugar(p.a), o = actores[p.actor] || props[p.actor]; if (o && l) { o.x = l.x; o.y = l.y; if (o.imagen) o.imagen.setPosition(l.x, l.y); } }
            else if (p.tipo === 'aparecer') p.actores.forEach(function (id) { crearActor(id, marcas[p.en] || marcas[id]); });
            else if (p.tipo === 'interactuar' && !p.quedarse && props[p.objeto]) { props[p.objeto].imagen.destroy(); delete props[p.objeto]; }
            else if (p.tipo === 'si' && DECISIONES[p.clave] === p.valor) for (const q of p.pasos) await paso(q, true);
            else if (p.tipo === 'tumbar' && actores[p.actor]) tumbar(actores[p.actor], true);
            else if (p.tipo === 'vestir') vestir(p);
            else if (p.tipo === 'combate') Object.keys(p.unidades).forEach(function (id) { if (p.unidades[id].lado === 'enemigo' && actores[id]) tumbar(actores[id], true); });
            return;
        }
        switch (p.tipo) {
            case 'escena': cargarEscena(p); break;
            case 'fundido': await fundido(p.a, p.segundos || 1); break;
            case 'tarjeta': await tarjeta(p); break;
            case 'franjas': franjas(p.visibles); break;
            case 'dialogo': await dialogo(p); break;
            case 'elegir': await elegir(p); break;
            case 'si': if (DECISIONES[p.clave] === p.valor) for (const q of p.pasos) await paso(q, false); break;
            case 'mover': {
                const l = lugar(p.a), o = actores[p.actor] || props[p.actor];
                if (o && l) { const pr = moverHacia(o, l.x, l.y, p.segundos || 1); movimientos[p.actor] = pr; if (p.esperar !== false) await pr; }
                break;
            }
            case 'esperar_mover': await (movimientos[p.actor] || Promise.resolve()); break;
            case 'aparecer': p.actores.forEach(function (id) { crearActor(id, marcas[p.en] || marcas[id]); }); break;
            case 'objetivo': await pasoObjetivo(p); break;
            case 'interactuar': await pasoInteractuar(p); break;
            case 'sigilo': await pasoSigilo(p); break;
            case 'persecucion': await pasoPersecucion(p); break;
            case 'combate': await pasoCombate(p); break;
            case 'partida': await pasoPartida(p); break;
            case 'interrogatorio': await pasoInterrogatorio(p); break;
            case 'persecucion_auto': await pasoPersecucionAuto(p); break;
            case 'tumbar': if (actores[p.actor]) tumbar(actores[p.actor], true); break;
            case 'cerradura': await pasoCerradura(p); break;
            case 'paseo': await pasoPaseo(p); break;
            case 'seguir': await pasoSeguir(p); break;
            case 'desaparecer': p.actores.forEach(function (id) { if (actores[id]) { actores[id].sprite.destroy(); delete actores[id]; } }); break;
            case 'vestir': vestir(p); break;
            case 'agujas': await pasoAgujas(p); break;
            case 'efecto': await efecto(p); break;
            case 'camara': {
                const a = actores[p.actor], cam = escena.cameras.main, ms = (p.segundos || 1) * 1000 / (AUTO() ? 6 : 1);
                if (a) cam.pan(a.x, a.y - 20, ms, 'Sine.easeInOut');
                cam.zoomTo(p.zoom || 1, ms, 'Sine.easeInOut');
                await dormir(p.segundos || 1);
                break;
            }
            case 'guardar': await post({ accion: 'guardar', punto: p.punto }); break;
            case 'fin': await terminar(); break;
        }
    }

    async function jugar(desdePunto) {
        const pasos = GUION.pasos;
        let desde = 0;
        if (desdePunto) {
            const i = pasos.findIndex(function (p) { return p.tipo === 'guardar' && p.punto === desdePunto; });
            if (i >= 0) {
                for (let k = 0; k < i; k++) await paso(pasos[k], true);   // reconstruye la escena sin repetir lo ya visto
                franjas(false); await fundido('escena', 1);
                desde = i + 1;
            }
        }
        for (let k = desde; k < pasos.length; k++) {
            window.__campanaPaso = k;
            await paso(pasos[k], false);
        }
    }

    async function terminar() {
        const r = await post({ accion: 'completar' });
        $('final-titulo').textContent = t(GUION.titulo);
        $('final-texto').textContent = r.exito
            ? (r.yaCompletado ? t('Ya habías terminado este capítulo.') : t('Ganaste {xp} XP y el logro «{logro}».', { xp: r.xp, logro: t(r.logro || '') }))
            : t(r.mensaje || 'No se pudo guardar el final.');
        if (r.exito && r.siguiente) {
            $('final-siguiente').textContent = t('Seguir: {titulo}', { titulo: t(r.siguiente.titulo) });
            $('final-siguiente').href = B.dataset.campana + '?capitulo=' + encodeURIComponent(r.siguiente.id);
            $('final-siguiente').hidden = false;
        } else if (r.exito && r.ultimo) { $('final-texto').textContent += ' ' + t('Terminaste Nacht über Berlin. Gracias por jugar.'); }
        else if (r.exito) { $('final-texto').textContent += ' ' + t('La historia sigue pronto.'); }
        if (r.exito && r.logrosDecision && r.logrosDecision.length) {
            $('final-texto').textContent += ' ' + r.logrosDecision.map(function (n) { return t('Logro: «{logro}».', { logro: t(n) }); }).join(' ');
        }
        $('final').hidden = false;
        window.__campanaTerminada = true;
    }

    /* ---------- arranque ---------- */
    document.addEventListener('keydown', function (ev) {
        if (ev.target && ev.target.tagName === 'BUTTON' && (ev.key === ' ' || ev.key === 'Enter')) return;
        teclas[ev.key.length === 1 ? ev.key.toLowerCase() : ev.key] = true;
        if ((ev.key === 'e' || ev.key === 'E') && accionPendiente) accionPendiente();
        if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', ' '].indexOf(ev.key) >= 0) ev.preventDefault();
    });
    document.addEventListener('keyup', function (ev) { teclas[ev.key.length === 1 ? ev.key.toLowerCase() : ev.key] = false; });

    const Escena = {
        key: 'campana',
        preload: function () {
            this.load.setPath('img/campana/');
            this.load.image('tileset_campana', 'tileset.png');
            // Personajes y objetos: los que usan el guion y los mapas de ESTE capítulo (así ninguno queda sin cargar)
            Object.keys(GUION.personajes).forEach((p) => this.load.spritesheet('pj_' + p, 'pj_' + p + '.png', { frameWidth: 32, frameHeight: 48 }));
            const imagenes = {};
            Object.values(DATOS.mapas).forEach(function (m) {
                m.layers.filter(function (l) { return l.name === 'objetos'; }).forEach(function (l) {
                    l.objects.forEach(function (o) { (o.properties || []).forEach(function (x) { if (x.name === 'imagen') imagenes[x.value] = true; }); });
                });
            });
            Object.keys(imagenes).forEach((k) => this.load.image(k, k + '.png'));
        },
        create: function () {
            escena = this;
            const g = this.add.graphics(); g.fillStyle(0xbfd4ff, 1); g.fillRect(0, 0, 2, 12); g.generateTexture('gota', 2, 12); g.destroy();
            const l = this.add.graphics();
            for (let r = 110; r > 0; r -= 10) { l.fillStyle(0xffffff, 0.12); l.fillCircle(110, 110, r); }
            l.generateTexture('luz', 220, 220); l.destroy();
            oscuridad = this.add.renderTexture(0, 0, 960, 640).setOrigin(0, 0).setDepth(40000);
            conos = this.add.graphics().setDepth(39000);
            this.input.on('pointerdown', (ptr) => { if (control) destino = { x: ptr.worldX, y: ptr.worldY }; });
            listoPhaser();
        },
        update: function (tiempo, delta) {
            moverJugador(delta / 1000);
            revisarCartel();
            Object.values(actores).forEach(function (a) { dibujarActor(a, tiempo); });
            oscuridad.clear();
            if (noche) {
                oscuridad.fill(0x050816, 0.6);
                Object.values(marcas).forEach(function (m) { if (m.tipo === 'luz') oscuridad.erase('luz', m.x - 110, m.y - 110); });
                if (jugador) oscuridad.erase('luz', jugador.x - 110, jugador.y - 130);
            }
        },
    };

    let listoPhaser;
    const phaserListo = new Promise(function (r) { listoPhaser = r; });

    async function iniciar() {
        if ((B.dataset.idioma || 'es') !== 'es') { try { const r = await fetch('./lang/' + B.dataset.idioma + '.json'); if (r.ok) dic = await r.json(); } catch (e) { } }
        const r = await fetch(API + '?id=' + encodeURIComponent(CAP), { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
        DATOS = await r.json();
        if (!DATOS.exito) { $('portada-titulo').textContent = t(DATOS.mensaje || 'Ese capítulo todavía no está disponible.'); return; }
        GUION = DATOS.guion; DECISIONES = DATOS.decisiones || {};
        new Phaser.Game({
            type: Phaser.AUTO, parent: 'juego', backgroundColor: '#000000', width: 960, height: 640,
            scale: { mode: Phaser.Scale.FIT, autoCenter: Phaser.Scale.CENTER_BOTH },
            render: { pixelArt: true, roundPixels: true, antialias: false },
            loader: { imageLoadType: 'HTMLImageElement' },   // la política de seguridad no permite blob:
            audio: { noAudio: true }, input: { keyboard: false, gamepad: false }, banner: false, disableContextMenu: true,
            scene: Escena,
        });
        $('portada-titulo').textContent = t(GUION.titulo);
        $('dialogo-seguir').textContent = t('Espacio, Enter o tocá para seguir');
        document.querySelector('.ayuda-campana').textContent = t('WASD o flechas para caminar · E para usar · Espacio para seguir los diálogos');
        $('salir').textContent = t('Salir'); $('final-volver').textContent = t('Volver a Berlín');
        $('combate-ayuda').textContent = t($('combate-ayuda').textContent);
        $('cerradura-boton').textContent = t('Ahora');
        ['combate-cubrirse', 'combate-terminar', 'combate-reintentar'].forEach(function (id) { $(id).textContent = t($(id).textContent); });
        $('portada-aviso').textContent = t(GUION.aviso);
        const botones = $('portada-botones');
        const boton = function (texto, clase, accion) {
            const b = document.createElement('button'); b.type = 'button'; b.className = 'boton-campana ' + clase; b.textContent = t(texto);
            b.addEventListener('click', accion); botones.appendChild(b); return b;
        };
        const p = DATOS.progreso;
        const empezar = async function (desde) {
            Sonido.iniciar();
            await phaserListo;
            const r = await post({ accion: 'empezar' });
            if (!r.exito) { $('portada-aviso').textContent = t(r.mensaje || 'No se pudo empezar.'); return; }
            $('portada').hidden = true;
            jugar(desde);
        };
        if (p && p.estado === 'en_curso' && p.punto) {
            boton('Retomar', '', function () { empezar(p.punto); });
            boton('Empezar de nuevo', 'secundario', function () { empezar(null); });
        } else {
            boton(p && p.estado === 'completado' ? 'Volver a jugar' : 'Empezar', '', function () { empezar(null); });
        }
        window.__campanaEmpezar = empezar;   // para pruebas automáticas
        window.__campanaFaltantes = function () { return escena ? escena.children.list.filter(function (o) { return o.texture && o.texture.key === '__MISSING'; }).length : null; };   // diagnóstico
        window.__campanaCombate = function () { return combate ? JSON.parse(JSON.stringify({ c: combate, elegido: elegido, ocupado: ocupado })) : null; };   // diagnóstico (solo lectura)
        window.__campanaCamara = function () { const c = escena.cameras.main; return { zoom: c.zoom, x: Math.round(c.midPoint.x), y: Math.round(c.midPoint.y) }; };   // diagnóstico (solo lectura)
    }
    iniciar();
})();
