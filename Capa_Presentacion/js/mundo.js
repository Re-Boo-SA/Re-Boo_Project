/*
 * MUNDO ABIERTO · BERLÍN (cliente, con Phaser 3)
 *
 * El mapa es el archivo datos/mundo/berlin.json (formato Tiled), el MISMO que usa el
 * servidor para validar cada paso. Acá se dibuja, se mueve tu personaje en el
 * navegador (para que se sienta inmediato) y se le manda la posición al servidor
 * ~5 veces por segundo. Si el servidor rechaza un paso, te devuelve a donde corresponde.
 *
 * La interfaz (HUD, panel de edificios, chat, bando) sigue siendo HTML normal.
 * Todo el texto del juego se arma con textContent / objetos de Phaser: nunca innerHTML.
 */
(function () {
    'use strict';

    const cuerpo = document.body;
    const API = cuerpo.dataset.api;
    const CSRF = cuerpo.dataset.csrf;
    const ENLACES = JSON.parse(cuerpo.dataset.enlaces);

    const TINTE = { oeste: 0x4a8cff, este: 0xe32636 };
    const DESCRIPCIONES = {
        ranking: 'Acá se lleva la cuenta de quiénes son los mejores de Berlín.',
        tienda: 'Skins para tus fichas. Se pagan con los puntos que ganás jugando.',
        galeria: 'Las fichas del juego y la historia que hay detrás de cada una.',
        competitivo: 'La cola competitiva: rivales de tu nivel, reglas oficiales y MMR en juego.',
        torneos: 'Desde la Torre de TV se transmiten los torneos relámpago de Berlín. Armá uno o anotate.'
    };
    const CAPA_NOMBRES = 300000, CAPA_BURBUJAS = 300001;   // por encima de la oscuridad de la noche
    // En celulares y tablets se camina tocando el mapa: los textos no hablan de teclas.
    const TACTIL = window.matchMedia('(pointer: coarse)').matches;

    let MAPA = null;            // lo que devuelve api-mundo?accion=mapa
    let COLISION = null;        // capa de colisión (arreglo plano)
    let diccionario = {};
    let escena = null;

    const yo = { x: null, y: null, dir: 2, bando: null, puedeCambiarBando: true, id: 0, ap: 0, per: null, apariencia: { piel: 0, pelo: 0, gorro: 0 }, gesto: 0, gestoHasta: 0 };
    const otros = new Map();    // id -> { x, y, tx, ty, nombre, bando, rango, dir, sprite, sombra, etiqueta }
    const burbujas = new Map(); // id de jugador (o 'yo') -> { texto, hasta, idMensaje, objeto }
    let dominio = {};
    let temporada = { numero: 1, segundosRestantes: 0 };
    const teclas = new Set();
    let primera = null;          // vista en primera persona (mundo_primera.js); la normal es la de arriba
    let destino = null;
    let ultimoEnvio = 0;
    let ultimoCompleto = 0;
    let enviando = false;
    let movido = false;
    let ultimoMensaje = 0;
    let edificioCerca = null;
    let contactoCerca = null;
    let MISIONES = null;         // lo que devuelve api-mundo?accion=misiones
    let grupoIds = new Set();    // miembros de mi grupo (Fase 4)
    let SUCESOS = { actual: null, proximo: null };   // etapa B
    let cercaDelMercado = false;
    let invitacionesVistas = null;
    let ultimasMisiones = 0;

    /* ---------- utilidades ---------- */

    function t(texto, valores) {
        let salida = diccionario[texto] || texto;
        if (valores) Object.keys(valores).forEach(function (k) { salida = salida.split('{' + k + '}').join(valores[k]); });
        return salida;
    }
    function $(id) { return document.getElementById(id); }
    function nodo(etiqueta, clase, texto) {
        const e = document.createElement(etiqueta);
        if (clase) e.className = clase;
        if (texto !== undefined) e.textContent = texto;
        return e;
    }
    let temporizadorAviso = null;
    function avisar(texto, esError) {
        const caja = $('aviso');
        caja.textContent = texto;
        caja.classList.toggle('error', !!esError);
        caja.hidden = false;
        clearTimeout(temporizadorAviso);
        temporizadorAviso = setTimeout(function () { caja.hidden = true; }, 3500);
    }

    /* ---------- servidor ---------- */

    async function post(datos) {
        const r = await fetch(API, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'X-CSRF-Token': CSRF, 'Accept': 'application/json' },
            body: new URLSearchParams(datos)
        });
        if (r.status === 401) { window.location.href = ENLACES.inicio; }
        return r.json();
    }
    async function pedir(parametros) {
        const r = await fetch(API + (parametros ? '?' + parametros : ''), { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
        if (r.status === 401) { window.location.href = ENLACES.inicio; }
        return r.json();
    }

    /** Lo que cambia varias veces por segundo: quién está dónde y mensajes nuevos. */
    function aplicarLiviano(e) {
        const vistos = new Set();
        (e.jugadores || []).forEach(function (j) {
            if (j.id === yo.id) return;
            vistos.add(j.id);
            const previo = otros.get(j.id);
            if (previo) {
                previo.tx = j.x; previo.ty = j.y; previo.bando = j.bando; previo.dir = j.dir; previo.ap = j.ap || 0; previo.per = j.per || null; previo.nivel = j.nivel || 1; previo.gesto = j.gesto || 0;
            } else {
                otros.set(j.id, { x: j.x, y: j.y, tx: j.x, ty: j.y, nombre: j.nombre, bando: j.bando, rango: j.rango, dir: j.dir, ap: j.ap || 0, per: j.per || null, semilla: j.id % 4, nivel: j.nivel || 1, gesto: j.gesto || 0 });
            }
        });
        Array.from(otros.keys()).forEach(function (id) {
            if (!vistos.has(id)) {
                if (escena) escena.destruirPersonaje(otros.get(id));
                const b = burbujas.get(id);
                if (b && b.objeto) b.objeto.destroy();
                burbujas.delete(id);
                otros.delete(id);
            }
        });
        const ahora = performance.now();
        (e.mensajes || []).forEach(function (m) {
            ultimoMensaje = Math.max(ultimoMensaje, m.id);
            if (m.jugador === yo.id) return;
            const previa = burbujas.get(m.jugador);
            if (previa && previa.objeto) previa.objeto.destroy();
            burbujas.set(m.jugador, { texto: m.texto, hasta: ahora + 6000, idMensaje: m.id, objeto: null });
        });
    }

    /** Estado completo (al entrar y cada ~10 s): temporada, dominio y mi bando. */
    function aplicarCompleto(e) {
        yo.id = e.yo.id;
        yo.bando = e.yo.bando;
        yo.puedeCambiarBando = e.yo.puedeCambiarBando;
        if (yo.x === null && e.yo.x !== null) { yo.x = e.yo.x; yo.y = e.yo.y; }
        dominio = e.dominio;
        temporada = e.temporada;
        if (e.reloj) MundoVivo.sincronizar(e.reloj);
        if (e.yo.apariencia) { yo.apariencia = e.yo.apariencia; yo.ap = e.yo.apariencia.indice; }
        yo.per = e.yo.personal || null;
        if (e.social) aplicarSocial(e.social);
        if (e.sucesos) { SUCESOS = e.sucesos; if (escena && escena.vivo) escena.vivo.ponerSuceso(SUCESOS.actual); armarRadio(); }
        const avisoTorneo = $('aviso-torneo');
        avisoTorneo.hidden = !e.torneo;
        if (e.torneo) avisoTorneo.href = ENLACES.partida + '?id=' + e.torneo.idPartida;
        aplicarLiviano(e);
        if (!e.yo.bando) $('capa-bando').hidden = false;
        dibujarHud();
        if (escena) escena.pintarDominio();
    }

    async function sincronizar(ahora) {
        if (enviando || yo.x === null) return;
        if (ahora - ultimoCompleto > 10000) {
            ultimoCompleto = ahora;
            pedir().then(function (d) { if (d.exito) aplicarCompleto(d); }).catch(function () { });
        }
        if (ahora - ultimasMisiones > 8000) cargarMisiones();
        const intervalo = movido ? 200 : 1000;
        if (ahora - ultimoEnvio < intervalo) return;
        enviando = true;
        ultimoEnvio = ahora;
        movido = false;
        try {
            const datos = await post({ accion: 'mover', x: Math.round(yo.x), y: Math.round(yo.y), dir: yo.dir, desde: ultimoMensaje });
            if (datos.exito) {
                if (datos.posicion && datos.posicion.corregido) {
                    yo.x = datos.posicion.x;
                    yo.y = datos.posicion.y;
                    destino = null;
                }
                aplicarLiviano(datos);
            }
        } catch (error) {
            console.error(error);
        } finally {
            enviando = false;
        }
    }

    /* ---------- movimiento y colisiones (la misma regla que el servidor) ---------- */

    function libre(x, y) {
        const c = MAPA.cuerpo, tile = MAPA.tile, cols = MAPA.ancho / tile;
        const puntos = [[c.izq, c.arriba], [c.der, c.arriba], [c.izq, c.abajo], [c.der, c.abajo]];
        for (let i = 0; i < 4; i++) {
            const px = Math.floor(x + puntos[i][0]), py = Math.floor(y + puntos[i][1]);
            if (px < 0 || py < 0 || px >= MAPA.ancho || py >= MAPA.alto) return false;
            if (COLISION[Math.floor(py / tile) * cols + Math.floor(px / tile)] !== 0) return false;
        }
        return true;
    }

    function mover(dt) {
        if (yo.x === null) return false;
        let dx = 0, dy = 0;
        if (primera && primera.activo()) {
            // Primera persona: los costados giran y adelante/atrás avanzan hacia donde mirás.
            primera.girar(((teclas.has('derecha') ? 1 : 0) - (teclas.has('izquierda') ? 1 : 0)) * 2.4 * dt);
            const avance = (teclas.has('arriba') ? 1 : 0) - (teclas.has('abajo') ? 1 : 0), a = primera.angulo();
            destino = null;
            if (!avance) { const c = Math.cos(a), s = Math.sin(a); yo.dir = Math.abs(c) > Math.abs(s) ? (c > 0 ? 1 : 3) : (s > 0 ? 2 : 0); return false; }
            dx = Math.cos(a) * avance; dy = Math.sin(a) * avance;
        } else {
            if (teclas.has('arriba')) dy -= 1;
            if (teclas.has('abajo')) dy += 1;
            if (teclas.has('izquierda')) dx -= 1;
            if (teclas.has('derecha')) dx += 1;
        }
        if (dx || dy) {
            destino = null;
        } else if (destino) {
            dx = destino.x - yo.x;
            dy = destino.y - yo.y;
            if (Math.hypot(dx, dy) < 5) { destino = null; return false; }
        } else {
            return false;
        }
        const largo = Math.hypot(dx, dy);
        // Control sorpresa en Checkpoint Charlie: dentro del paso se avanza más lento (lo mismo que valida el servidor)
        const enPaso = yo.x >= 1040 && yo.x <= 1440 && yo.y >= 860 && yo.y <= 1020;
        const control = SUCESOS.actual && SUCESOS.actual.tipo === 'control_checkpoint' && enPaso;
        const paso = MAPA.velocidad * dt * (control ? SUCESOS.actual.efecto.velocidadPaso : 1);
        const nx = yo.x + dx / largo * paso;
        const ny = yo.y + dy / largo * paso;
        let avanzo = false;
        // Cada eje por separado: así se "desliza" contra las paredes en vez de frenarse.
        if (libre(nx, yo.y) && !chocaContacto(nx, yo.y)) { yo.x = nx; avanzo = true; }
        if (libre(yo.x, ny) && !chocaContacto(yo.x, ny)) { yo.y = ny; avanzo = true; }
        if (!avanzo) destino = null;
        if (avanzo) yo.gesto = 0;
        yo.dir = Math.abs(dx) > Math.abs(dy) ? (dx > 0 ? 1 : 3) : (dy > 0 ? 2 : 0);
        movido = movido || avanzo;
        return avanzo;
    }

    function buscarEdificioCerca() {
        if (yo.x === null) return null;
        let mejor = null, distancia = MAPA.distanciaPuerta;
        MAPA.edificios.forEach(function (b) {
            const d = Math.hypot(b.puertaX - yo.x, b.puertaY - yo.y);
            if (d < distancia) { distancia = d; mejor = b; }
        });
        return mejor;
    }

    /* ---------- escena de Phaser ---------- */

    function claveSprite(b) { return b.tipo === 'cafe' ? 'cafe_' + (b.x >= 30 * MAPA.tile ? 'este' : 'oeste') : b.id; }

    class Berlin extends Phaser.Scene {
        constructor() { super('berlin'); }

        preload() {
            this.cache.tilemap.add('berlin', { format: Phaser.Tilemaps.Formats.TILED_JSON, data: MAPA.tiled });
            this.load.setPath('img/mundo/');
            this.load.image('tileset', 'tileset.png');
            this.load.spritesheet('tiles', 'tileset.png', { frameWidth: MAPA.tile, frameHeight: MAPA.tile });
            ['oeste', 'este', 'neutro'].forEach((b) => this.load.spritesheet('pj_' + b, 'personaje_' + b + '.png', { frameWidth: 32, frameHeight: 48 }));
            this.load.spritesheet('npc_civiles', 'npc_civiles.png', { frameWidth: 32, frameHeight: 48 });
            this.load.spritesheet('npc_soldado', 'npc_soldado.png', { frameWidth: 32, frameHeight: 48 });
            this.load.spritesheet('npc_mp', 'npc_mp.png', { frameWidth: 32, frameHeight: 48 });
            this.load.image('tranvia', 'tranvia.png');
            ['suceso_puesto', 'suceso_escenario', 'suceso_auto', 'suceso_cartel'].forEach((k) => this.load.image(k, k + '.png'));
            const vistos = {};
            MAPA.edificios.forEach((b) => {
                const k = claveSprite(b);
                if (!vistos[k]) { vistos[k] = true; this.load.image('ed_' + k, 'edificios/' + k + '.png'); }
            });
        }

        create() {
            escena = this;
            const T = MAPA.tile;
            const mapa = this.make.tilemap({ key: 'berlin' });
            const ts = mapa.addTilesetImage('berlin', 'tileset');
            mapa.createLayer('suelo', ts, 0, 0).setDepth(0);

            // Tinte por distrito según quién domina (se repinta con cada estado completo)
            this.dominios = this.add.graphics().setDepth(1);
            this.nombresDistritos = MAPA.distritos.map((d) => this.add.text(d.x + d.w / 2, d.y + 34, d.nombre, {
                fontFamily: '"Saira Stencil One", Verdana, sans-serif', fontSize: '30px', color: '#eef1f7'
            }).setOrigin(0.5).setAlpha(0.2).setDepth(2));

            // Decoración (árboles, faroles, vallas, torres): un sprite por mosaico, ordenado por altura
            const deco = MAPA.tiled.layers.find((l) => l.name === 'decoracion').data;
            const cols = MAPA.ancho / T;
            deco.forEach((gid, i) => {
                if (gid) this.add.image((i % cols) * T, Math.floor(i / cols) * T, 'tiles', gid - 1).setOrigin(0, 0).setDepth((Math.floor(i / cols) + 1) * T);
            });

            // Edificios: el sprite se apoya en su base, así quien camina por delante los tapa y por detrás se oculta
            MAPA.edificios.forEach((b) => {
                const img = this.add.image(b.x, b.y + b.h, 'ed_' + claveSprite(b)).setOrigin(0, 1).setDepth(b.y + b.h);
                this.add.text(b.x + b.w / 2, b.y + b.h - img.height - 4, t(b.nombre), {
                    fontFamily: 'Verdana, sans-serif', fontSize: '12px', color: '#eef1f7', stroke: '#0b132b', strokeThickness: 4
                }).setOrigin(0.5, 1).setDepth(CAPA_NOMBRES);
            });

            this.mio = { sprite: null };
            this.miSombra = this.add.ellipse(0, 0, 22, 9, 0x000000, 0.35).setDepth(1);
            this.miEtiqueta = this.add.text(0, 0, '', this.estiloEtiqueta()).setOrigin(0.5, 1).setDepth(CAPA_NOMBRES);

            this.cameras.main.setBounds(0, 0, MAPA.ancho, MAPA.alto);
            this.cameras.main.setRoundPixels(true);
            this.cameras.main.setBackgroundColor('#070c1c');

            this.input.on('pointerdown', (p) => this.alHacerClic(p));
            this.vivo = MundoVivo.crear(this, MAPA);   // día y noche, clima, luces y NPCs
            primera = MundoPrimera.crear({ MAPA: MAPA, COLISION: COLISION, contenedor: $('juego') });
            this.vivo.ponerSuceso(SUCESOS.actual);
            this.ponerContactos();
            this.ultimoSonido = 0;
            this.pintarDominio();
            this.minimapaBase = this.crearMinimapaBase();
            this.ultimoMinimapa = 0;
            document.body.dataset.listo = '1';
        }

        estiloEtiqueta() {
            return { fontFamily: 'Verdana, sans-serif', fontSize: '12px', color: '#eef1f7', stroke: '#0b132b', strokeThickness: 4 };
        }

        pintarDominio() {
            if (!this.dominios) return;
            this.dominios.clear();
            MAPA.distritos.forEach((d) => {
                const dom = dominio[d.id];
                if (dom && dom.domina) {
                    this.dominios.fillStyle(TINTE[dom.domina], 0.16);
                    this.dominios.fillRect(d.x, d.y, d.w, d.h);
                }
            });
        }

        /* --- personajes --- */

        ponerPersonaje(entidad, mueve) {
            // Personaje personalizado (js/apariencia.js); si todavía no cargó, el de siempre
            const propia = entidad.per && window.Apariencia ? Apariencia.textura(this, entidad.per) : null;
            const clave = propia || 'pj_' + (entidad.bando || 'neutro');
            if (!entidad.sprite || entidad.sprite.texture.key !== clave) {
                if (entidad.sprite) entidad.sprite.destroy();
                entidad.sprite = this.add.sprite(entidad.x, entidad.y, clave, 0).setOrigin(0.5, 1);
            }
            entidad.sprite.setPosition(Math.round(entidad.x), Math.round(entidad.y)).setDepth(entidad.y)
                .setFrame(entidad.gesto && !mueve ? MundoVivo.cuadroGesto(propia ? 0 : entidad.ap || 0, entidad.gesto, !propia)
                    : MundoVivo.cuadro(propia ? 0 : entidad.ap || 0, entidad.dir || 0, MundoVivo.pasoAnimado(mueve, entidad.semilla), !propia));
        }

        destruirPersonaje(o) {
            if (!o) return;
            if (o.sprite) o.sprite.destroy();
            if (o.sombra) o.sombra.destroy();
            if (o.etiqueta) o.etiqueta.destroy();
        }

        /* --- burbujas de chat --- */

        ponerBurbuja(clave, x, y, b) {
            if (b.hasta < performance.now()) {
                if (b.objeto) { b.objeto.destroy(); b.objeto = null; }
                return;
            }
            if (!b.objeto) {
                b.objeto = this.add.text(0, 0, b.texto, {
                    fontFamily: 'Verdana, sans-serif', fontSize: '13px', color: '#0b132b', backgroundColor: '#eef1f7',
                    padding: { x: 7, y: 4 }, wordWrap: { width: 220 }
                }).setOrigin(0.5, 1).setDepth(CAPA_BURBUJAS);
                if (clave !== 'yo') {
                    b.objeto.setInteractive({ useHandCursor: true });
                    b.objeto.on('pointerdown', (p, lx, ly, ev) => {
                        ev.stopPropagation();
                        if (window.confirm(t('¿Reportar este mensaje a un administrador?'))) {
                            post({ accion: 'reportar', id: b.idMensaje }).then(function (r) { avisar(t(r.mensaje || 'Listo.')); });
                        }
                    });
                }
            }
            b.objeto.setPosition(Math.round(x), Math.round(y - 74));
        }

        /* --- entrada --- */

        alHacerClic(p) {
            window.__ultimoClic = { x: p.worldX, y: p.worldY, tocados: this.input.hitTestPointer(p).length };
            const tocado = this.input.hitTestPointer(p).find(function (o) { return o.getData && o.getData('jugador'); });
            if (tocado) { Social.mostrarPerfil(tocado.getData('jugador')); return; }
            const x = p.worldX, y = p.worldY;
            const edificio = MAPA.edificios.find(function (b) { return x >= b.x && x <= b.x + b.w && y >= b.y - 40 && y <= b.y + b.h; });
            destino = edificio ? { x: edificio.puertaX, y: edificio.puertaY } : { x: x, y: y };
        }

        /* --- minimapa --- */

        crearMinimapaBase() {
            const mini = $('minimapa'), fx = mini.width / MAPA.ancho, fy = mini.height / MAPA.alto;
            const cv = document.createElement('canvas');
            cv.width = mini.width; cv.height = mini.height;
            const c = cv.getContext('2d'), T = MAPA.tile, cols = MAPA.ancho / T;
            c.fillStyle = '#16203a'; c.fillRect(0, 0, cv.width, cv.height);
            c.fillStyle = '#2a1d2c'; c.fillRect(MAPA.ancho / 2 * fx, 0, cv.width, cv.height);
            COLISION.forEach(function (v, i) {
                if (!v) return;
                const x = (i % cols) * T, y = Math.floor(i / cols) * T;
                c.fillStyle = x >= 29 * T && x < 30 * T ? '#c9ccd3' : '#4a4f5c';
                c.fillRect(x * fx, y * fy, Math.max(1, T * fx), Math.max(1, T * fy));
            });
            return cv;
        }

        dibujarMinimapa() {
            const mini = $('minimapa'), c = mini.getContext('2d'), fx = mini.width / MAPA.ancho, fy = mini.height / MAPA.alto;
            c.clearRect(0, 0, mini.width, mini.height);
            c.drawImage(this.minimapaBase, 0, 0);
            MAPA.distritos.forEach(function (d) {
                const dom = dominio[d.id];
                if (dom && dom.domina) {
                    c.fillStyle = dom.domina === 'oeste' ? 'rgba(74,140,255,0.35)' : 'rgba(227,38,54,0.35)';
                    c.fillRect(d.x * fx, d.y * fy, d.w * fx, d.h * fy);
                }
            });
            otros.forEach(function (p, id) {
                const delGrupo = grupoIds.has(id);
                c.fillStyle = delGrupo ? '#7be38a' : '#eef1f7';
                const r = delGrupo ? 2 : 1;
                c.fillRect(p.x * fx - r, p.y * fy - r, r * 2, r * 2);
            });
            if (MISIONES) {
                c.strokeStyle = '#f4c430'; c.lineWidth = 1.5;
                MISIONES.activas.forEach(function (a) { if (a.objetivo) { c.beginPath(); c.arc(a.objetivo.x * fx, a.objetivo.y * fy, 5, 0, Math.PI * 2); c.stroke(); } });
            }
            if (SUCESOS.actual) { c.font = '11px sans-serif'; c.textAlign = 'center'; c.fillText(SUCESOS.actual.icono || '!', SUCESOS.actual.x * fx, SUCESOS.actual.y * fy + 4); }
            if (yo.x !== null && primera && primera.activo()) {   // hacia dónde mirás
                const a = primera.angulo(); c.fillStyle = 'rgba(244,196,48,0.35)'; c.beginPath(); c.moveTo(yo.x * fx, yo.y * fy);
                c.arc(yo.x * fx, yo.y * fy, 16, a - 0.58, a + 0.58); c.closePath(); c.fill();
            }
            if (yo.x !== null) { c.fillStyle = '#f4c430'; c.beginPath(); c.arc(yo.x * fx, yo.y * fy, 3, 0, Math.PI * 2); c.fill(); }
        }

        /* --- misiones: contactos y objetivos en el mapa --- */

        ponerContactos() {
            if (!MISIONES) return;
            this.contactos = this.contactos || {};
            MISIONES.contactos.forEach((c) => {
                let o = this.contactos[c.id];
                if (!o) {
                    o = this.contactos[c.id] = {
                        sombra: this.add.ellipse(c.x, c.y - 3, 22, 9, 0x000000, 0.35).setDepth(1),
                        sprite: this.add.sprite(c.x, c.y, 'npc_civiles', MundoVivo.cuadro(c.variante, c.dir, 0, true)).setOrigin(0.5, 1).setDepth(c.y),
                        nombre: this.add.text(c.x, c.y - 52, c.nombre, Object.assign(this.estiloEtiqueta(), { color: '#f4c430' })).setOrigin(0.5, 1).setDepth(CAPA_NOMBRES),
                        marca: this.add.text(c.x, c.y - 70, '', { fontFamily: '"Saira Stencil One", Verdana, sans-serif', fontSize: '26px', color: '#f4c430', stroke: '#0b132b', strokeThickness: 5 })
                            .setOrigin(0.5, 1).setDepth(CAPA_NOMBRES)
                    };
                }
                o.marca.setText(c.marca || '');
            });
        }

        actualizarMisionesEnMapa(tiempo) {
            if (this.contactos) {
                contactosAhora().forEach((c) => {
                    const o = this.contactos[c.id];
                    if (!o) return;
                    const visible = c.estado !== 'ausente';
                    [o.sprite, o.sombra, o.nombre, o.marca].forEach(function (x) { x.setVisible(visible); });
                    if (!visible) return;
                    let dir = c.dir;
                    const cerca = yo.x !== null && Math.hypot(yo.x - c.x, yo.y - c.y) < 120;
                    if (c.estado === 'quieto' && cerca) {   // te mira
                        const dx = yo.x - c.x, dy = yo.y - c.y;
                        dir = Math.abs(dx) > Math.abs(dy) ? (dx > 0 ? 1 : 3) : (dy > 0 ? 2 : 0);
                    }
                    o.sprite.setPosition(Math.round(c.x), Math.round(c.y)).setDepth(c.y)
                        .setFrame(MundoVivo.cuadro(c.variante, dir, MundoVivo.pasoAnimado(c.estado === 'caminando', c.variante), true));
                    o.sombra.setPosition(Math.round(c.x), Math.round(c.y) - 3);
                    o.nombre.setPosition(Math.round(c.x), Math.round(c.y) - 52);
                    o.marca.setPosition(Math.round(c.x), Math.round(c.y) - 70 + Math.sin(tiempo / 250) * 3);
                });
            }
            if (!this.objetivos) this.objetivos = this.add.graphics().setDepth(2);
            this.objetivos.clear();
            if (!MISIONES) return;
            const pulso = 0.5 + Math.sin(tiempo / 300) * 0.25;
            const ahora = contactosAhora();
            MISIONES.activas.forEach((a) => {
                if (!a.objetivo) return;
                if (a.objetivo.contacto) {
                    const c = ahora.find(function (x) { return x.id === a.objetivo.contacto; });
                    if (!c || c.estado === 'ausente') return;
                    a.objetivo.x = c.x; a.objetivo.y = c.y;
                }
                this.objetivos.lineStyle(3, 0xf4c430, pulso).strokeCircle(a.objetivo.x, a.objetivo.y, a.objetivo.radio);
                this.objetivos.fillStyle(0xf4c430, pulso * 0.15).fillCircle(a.objetivo.x, a.objetivo.y, a.objetivo.radio);
                // Si entraste a la zona de un objetivo "ir", se le pregunta al servidor enseguida
                if (yo.x !== null && Math.hypot(yo.x - a.objetivo.x, yo.y - a.objetivo.y) <= a.objetivo.radio && performance.now() - ultimasMisiones > 1500) {
                    cargarMisiones();
                }
            });
        }

        /* --- cada cuadro --- */

        update(tiempo, delta) {
            const dt = Math.min(0.05, delta / 1000);
            const avanzo = mover(dt);
            const cerca = buscarEdificioCerca();
            const contacto = buscarContactoCerca();
            const mercado = !!(SUCESOS.actual && SUCESOS.actual.tipo === 'mercado' && yo.x !== null && Math.hypot(yo.x - SUCESOS.actual.x, yo.y - SUCESOS.actual.y) < 100);
            if (mercado !== cercaDelMercado) { cercaDelMercado = mercado; dibujarIndicacion(); }
            if (cerca !== edificioCerca || contacto !== contactoCerca) { edificioCerca = cerca; contactoCerca = contacto; dibujarIndicacion(); }
            this.actualizarMisionesEnMapa(tiempo);

            if (yo.x !== null) {
                if (yo.gesto && yo.gesto !== 4 && performance.now() > yo.gestoHasta) yo.gesto = 0;
                this.mio.x = yo.x; this.mio.y = yo.y; this.mio.dir = yo.dir; this.mio.bando = yo.bando; this.mio.ap = yo.ap; this.mio.per = yo.per; this.mio.gesto = yo.gesto;
                this.ponerPersonaje(this.mio, avanzo);
                this.miSombra.setPosition(Math.round(yo.x), Math.round(yo.y) - 3).setDepth(yo.y - 0.5);
                this.miEtiqueta.setText(t('vos')).setPosition(Math.round(yo.x), Math.round(yo.y) - 52);
                if (!this.camaraSigue && this.mio.sprite) { this.cameras.main.startFollow(this.mio.sprite, true, 0.15, 0.15); this.camaraSigue = true; }
                const bY = burbujas.get('yo');
                if (bY) this.ponerBurbuja('yo', yo.x, yo.y, bY);
            }

            otros.forEach((p, id) => {
                p.x += (p.tx - p.x) * Math.min(1, dt * 9);
                p.y += (p.ty - p.y) * Math.min(1, dt * 9);
                const mueve = Math.abs(p.tx - p.x) + Math.abs(p.ty - p.y) > 1.5;
                if (!p.sombra) p.sombra = this.add.ellipse(0, 0, 22, 9, 0x000000, 0.35).setDepth(1);
                if (!p.etiqueta) p.etiqueta = this.add.text(0, 0, '', this.estiloEtiqueta()).setOrigin(0.5, 1).setDepth(CAPA_NOMBRES);
                this.ponerPersonaje(p, mueve);
                if (!p.sprite.input) p.sprite.setInteractive({ useHandCursor: true }).setData('jugador', id);
                p.etiqueta.setColor(grupoIds.has(id) ? '#7be38a' : '#eef1f7');
                p.sombra.setPosition(Math.round(p.x), Math.round(p.y) - 3).setDepth(p.y - 0.5);
                p.etiqueta.setText((p.rango ? p.rango + ' ' : '') + p.nombre + ' · ' + t('Nv {n}', { n: p.nivel || 1 })).setPosition(Math.round(p.x), Math.round(p.y) - 52);
                const b = burbujas.get(id);
                if (b) this.ponerBurbuja(id, p.x, p.y, b);
            });

            const vivo = this.vivo.actualizar(dt, yo);
            if (primera && primera.activo()) primera.dibujar({ yo: yo, noche: vivo.noche, clima: vivo.clima, escena: this, mio: this.mio.sprite });
            const ahora = performance.now();
            if (ahora - this.ultimoSonido > 400) {
                this.ultimoSonido = ahora;
                SonidoMundo.actualizar({ x: yo.x, muroX: 29 * MAPA.tile + MAPA.tile / 2, clima: vivo.clima, noche: vivo.noche });
                dibujarReloj(vivo);
            }
            if (ahora - this.ultimoMinimapa > 200) { this.ultimoMinimapa = ahora; this.dibujarMinimapa(); }
            sincronizar(ahora);
        }
    }

    // Para pruebas automáticas: quiénes están en el mapa de este navegador
    window.__mundoDiagnostico = function () {
        const lista = [];
        otros.forEach(function (p, id) { lista.push({ id: id, x: Math.round(p.x), y: Math.round(p.y), interactivo: !!(p.sprite && p.sprite.input) }); });
        return { yo: { x: yo.x, y: yo.y }, otros: lista, clic: window.__ultimoClic || null };
    };

    /* ---------- HUD ---------- */

    function dibujarHud() {
        let oeste = 0, este = 0;
        Object.keys(dominio).forEach(function (id) {
            if (dominio[id].domina === 'oeste') oeste++;
            if (dominio[id].domina === 'este') este++;
        });
        const total = MAPA.distritos.length;
        $('marcador-oeste').textContent = oeste;
        $('marcador-este').textContent = este;
        $('barra-oeste').style.width = (oeste / total * 100) + '%';
        $('barra-este').style.width = (este / total * 100) + '%';
        const dias = Math.floor(temporada.segundosRestantes / 86400);
        const horas = Math.floor(temporada.segundosRestantes % 86400 / 3600);
        $('temporada').textContent = t('Temporada {n} · quedan {d} días y {h} h', { n: temporada.numero, d: dias, h: horas });
        const insignia = $('mi-bando');
        insignia.className = 'hud-boton mi-bando ' + (yo.bando || '');
        insignia.textContent = yo.bando === 'oeste' ? t('Bando: Oeste') : yo.bando === 'este' ? t('Bando: Este') : t('Sin bando');
        insignia.title = yo.puedeCambiarBando ? t('Tocá para cambiar de bando') : t('Podés cambiar de bando en la próxima temporada');
    }

    const ICONOS_CLIMA = { despejado: '☀', nublado: '☁', lluvia: '🌧', niebla: '🌫', nieve: '❄' };
    const NOMBRES_CLIMA = { despejado: 'Despejado', nublado: 'Nublado', lluvia: 'Lluvia', niebla: 'Niebla', nieve: 'Nieve' };
    function dibujarReloj(vivo) {
        const icono = vivo.clima === 'despejado' && vivo.noche > 0.6 ? '☾' : ICONOS_CLIMA[vivo.clima];
        $('reloj-mundo').textContent = icono + ' ' + MundoVivo.textoReloj() + ' · ' + t(NOMBRES_CLIMA[vivo.clima]);
    }

    function dibujarIndicacion() {
        const caja = $('indicacion');
        const ocupado = !$('panel').hidden || !$('panel-dialogo').hidden;
        if ((!edificioCerca && !contactoCerca && !cercaDelMercado) || ocupado) { caja.hidden = true; return; }
        caja.hidden = false;
        caja.textContent = '';
        if (cercaDelMercado && !contactoCerca) {
            if (!TACTIL) caja.appendChild(nodo('kbd', null, 'E'));
            caja.appendChild(document.createTextNode(t('Dar una vuelta por el mercado')));
            return;
        }
        if (contactoCerca) {
            if (!TACTIL) caja.appendChild(nodo('kbd', null, 'E'));
            caja.appendChild(document.createTextNode(TACTIL ? t('Tocá acá para hablar con {nombre}', { nombre: contactoCerca.nombre }) : t('Hablar con {nombre}', { nombre: contactoCerca.nombre })));
            return;
        }
        if (TACTIL) {
            caja.appendChild(document.createTextNode(t('Tocá acá para entrar a {lugar}', { lugar: t(edificioCerca.nombre) })));
            return;
        }
        caja.appendChild(nodo('kbd', null, 'E'));
        caja.appendChild(document.createTextNode(t('Entrar a {lugar}', { lugar: t(edificioCerca.nombre) })));
    }

    /* ---------- edificios ---------- */

    async function abrirEdificio(b) {
        destino = null;
        teclas.clear();
        $('panel-titulo').textContent = t(b.nombre);
        $('panel-cafe').hidden = b.tipo !== 'cafe';
        const enlace = $('panel-enlace');
        enlace.hidden = b.tipo === 'cafe';
        $('panel').hidden = false;
        dibujarIndicacion();
        if (b.tipo !== 'cafe') {
            $('panel-texto').textContent = t(DESCRIPCIONES[b.tipo]);
            enlace.href = ENLACES[b.tipo];
            enlace.textContent = t('Entrar');
            return;
        }
        const distrito = MAPA.distritos.find(function (d) { return d.id === b.distrito; });
        $('panel-texto').textContent = t('Las partidas que se juegan acá suman influencia en {d} para el bando de cada jugador.', { d: distrito.nombre });
        const dom = dominio[b.distrito];
        $('nota-influencia').textContent = dom ? t('Influencia esta temporada: Oeste {o}, Este {e}.', { o: dom.oeste, e: dom.este })
            + (dom.domina && dom.domina === yo.bando ? ' ' + t('Tu bando domina este distrito: ganás un 10 % más de puntos de tienda.') : '') : '';
        if (SUCESOS.actual && SUCESOS.actual.tipo === 'torneo_cafe' && SUCESOS.actual.distrito === b.distrito) {
            $('nota-influencia').textContent = '🏆 ' + t('Noche de torneo: las partidas de este café valen doble influencia.') + ' ' + $('nota-influencia').textContent;
        }
        $('btn-crear-mesa').dataset.distrito = b.distrito;
        await cargarMesas(b.distrito);
    }

    async function cargarMesas(idDistrito) {
        const lista = $('lista-mesas');
        lista.textContent = '';
        const datos = await pedir('accion=salas&distrito=' + encodeURIComponent(idDistrito));
        if ((!datos.salas || datos.salas.length === 0) && !(datos.enCurso || []).length) {
            lista.appendChild(nodo('li', 'vacio', t('No hay mesas esperando. Armá una vos.')));
            return;
        }
        (datos.enCurso || []).forEach(function (s) {
            const fila = nodo('li', 'en-curso');
            const info = nodo('div');
            info.appendChild(document.createTextNode('▶ ' + s.nombre));
            info.appendChild(nodo('small', null, t('En juego · {n} jugadores', { n: s.jugadores })));
            fila.appendChild(info);
            const mirar = nodo('a', 'boton-mirar', t('Mirar'));
            mirar.href = ENLACES.partida + '?id=' + s.id + '&espectar=1';
            fila.appendChild(mirar);
            lista.appendChild(fila);
        });
        datos.salas.forEach(function (s) {
            const fila = nodo('li');
            const info = nodo('div');
            info.appendChild(document.createTextNode((s.tipo === 'privada' ? '🔒 ' : '') + s.nombre));
            info.appendChild(nodo('small', null, t('{n} de {max} jugadores', { n: s.jugadores, max: s.max })));
            fila.appendChild(info);
            const boton = nodo('button', null, t('Sentarse'));
            boton.type = 'button';
            boton.addEventListener('click', async function () {
                let password = '';
                if (s.tipo === 'privada') password = window.prompt(t('Contraseña de la mesa:')) || '';
                const res = await post({ accion: 'unirse', id: s.id, password: password });
                if (res.exito) window.location.href = ENLACES.partida + '?id=' + res.idPartida;
                else avisar(t(res.mensaje), true);
            });
            fila.appendChild(boton);
            lista.appendChild(fila);
        });
    }

    $('btn-crear-mesa').addEventListener('click', async function () {
        const res = await post({ accion: 'crear_mesa', distrito: this.dataset.distrito, jugadores: $('jugadores-mesa').value });
        if (res.exito) window.location.href = ENLACES.partida + '?id=' + res.idPartida;
        else avisar(t(res.mensaje), true);
    });
    $('panel-cerrar').addEventListener('click', function () { $('panel').hidden = true; dibujarIndicacion(); });

    /* ---------- bando ---------- */

    document.querySelectorAll('.opcion-bando').forEach(function (boton) {
        boton.addEventListener('click', async function () {
            const res = await post({ accion: 'bando', bando: boton.dataset.bando });
            if (!res.exito) { avisar(t(res.mensaje), true); return; }
            $('capa-bando').hidden = true;
            yo.x = null;           // el servidor te pone en tu lado del Muro
            const estado = await pedir();
            aplicarCompleto(estado);
            if (escena && escena.mio && escena.mio.sprite) { escena.mio.sprite.destroy(); escena.mio.sprite = null; escena.camaraSigue = false; }
        });
    });
    $('mi-bando').addEventListener('click', function () {
        if (yo.puedeCambiarBando) $('capa-bando').hidden = false;
        else avisar(t('Podés cambiar de bando en la próxima temporada.'));
    });

    /* ---------- contactos con rutina (Berlín vivo, etapa A) ---------- */

    function largoRuta(r) { let l = 0; for (let i = 1; i < r.length; i++) l += Math.hypot(r[i][0] - r[i - 1][0], r[i][1] - r[i - 1][1]); return l; }
    function posicionContacto(c, hora) {
        for (let k = 0; k < c.rutina.length; k++) {
            const tr = c.rutina[k];
            const dentro = ((hora - tr.desde) % 24 + 24) % 24;
            if (dentro >= tr.dur) continue;
            if (tr.tipo !== 'caminar') return { x: tr.x, y: tr.y, dir: tr.dir, estado: tr.tipo };
            let objetivo = largoRuta(tr.ruta) * (dentro / tr.dur);
            for (let i = 1; i < tr.ruta.length; i++) {
                const p = tr.ruta[i - 1], q = tr.ruta[i], l = Math.hypot(q[0] - p[0], q[1] - p[1]);
                if (objetivo <= l || i === tr.ruta.length - 1) {
                    const f = l > 0 ? Math.min(1, objetivo / l) : 0, dx = q[0] - p[0], dy = q[1] - p[1];
                    return { x: p[0] + dx * f, y: p[1] + dy * f, dir: Math.abs(dx) > Math.abs(dy) ? (dx > 0 ? 1 : 3) : (dy > 0 ? 2 : 0), estado: 'caminando' };
                }
                objetivo -= l;
            }
        }
        return { x: 0, y: 0, dir: 2, estado: 'ausente' };
    }
    function contactosAhora() {
        if (!MISIONES) return [];
        const hora = MundoVivo.hora();
        return MISIONES.contactos.map(function (c) { return Object.assign({}, c, c.rutina ? posicionContacto(c, hora) : {}); });
    }
    /** No se atraviesa a un contacto que está quieto (choque solo en tu pantalla: no afecta nada importante). */
    function chocaContacto(x, y) {
        return contactosAhora().some(function (c) { return c.estado === 'quieto' && Math.hypot(c.x - x, c.y - y) < 16; });
    }

    /* ---------- misiones (Fase 2) ---------- */

    function buscarContactoCerca() {
        if (yo.x === null || !MISIONES) return null;
        let mejor = null, distancia = MISIONES.distanciaHablar - 10;
        contactosAhora().filter(function (c) { return c.estado !== 'ausente'; }).forEach(function (c) {
            const d = Math.hypot(c.x - yo.x, c.y - yo.y);
            if (d < distancia) { distancia = d; mejor = c; }
        });
        return mejor;
    }

    const colaAvisos = [];
    function mostrarAvisos(lista) {
        (lista || []).forEach(function (a) { colaAvisos.push(a); });
        if (colaAvisos.length === (lista || []).length) siguienteAviso();
    }
    function siguienteAviso() {
        if (!colaAvisos.length) return;
        avisar(traducirAviso(colaAvisos[0]));
        setTimeout(function () { colaAvisos.shift(); siguienteAviso(); }, 3000);
    }

    async function cargarMisiones() {
        ultimasMisiones = performance.now();
        try {
            const d = await pedir('accion=misiones');
            if (!d.exito) return;
            MISIONES = d;
            mostrarAvisos(d.avisos);
            dibujarProgreso();
            if (escena) escena.ponerContactos();
            if (!$('panel-misiones').hidden) dibujarPanelMisiones();
        } catch (error) { console.error(error); }
    }

    function dibujarProgreso() {
        const total = Math.max(1, MISIONES.xpHasta - MISIONES.xpDesde);
        $('nivel-texto').textContent = t('Nv {n}', { n: MISIONES.nivel });
        $('barra-xp').style.width = Math.min(100, (MISIONES.xp - MISIONES.xpDesde) / total * 100) + '%';
        $('btn-misiones').title = t('{xp} de {meta} XP para el próximo nivel', { xp: MISIONES.xp, meta: MISIONES.xpHasta });
    }

    function dibujarPanelMisiones() {
        const lista = $('lista-misiones');
        lista.textContent = '';
        if (!MISIONES.activas.length) lista.appendChild(nodo('li', 'vacio', t('No tenés misiones activas. Buscá a alguien con un ! sobre la cabeza.')));
        MISIONES.activas.forEach(function (a) {
            const li = nodo('li');
            li.appendChild(nodo('strong', null, t(a.titulo)));
            li.appendChild(nodo('small', null, t('Paso {p} de {n}', { p: a.paso, n: a.pasos })));
            li.appendChild(nodo('p', null, t(a.texto)));
            lista.appendChild(li);
        });
        const logros = $('lista-logros');
        logros.textContent = '';
        MISIONES.logros.forEach(function (l) {
            const li = nodo('li', l.obtenido ? 'obtenido' : '');
            li.appendChild(nodo('span', 'icono-logro', l.obtenido ? '★' : '☆'));
            const txt = nodo('div');
            txt.appendChild(nodo('strong', null, t(l.nombre)));
            txt.appendChild(nodo('small', null, t(l.descripcion)));
            li.appendChild(txt);
            logros.appendChild(li);
        });
        $('resumen-misiones').textContent = t('Nivel {n} · {xp} XP · {c} misiones completadas', { n: MISIONES.nivel, xp: MISIONES.xp, c: MISIONES.completadas });
        const lh = $('lista-historia');
        lh.textContent = '';
        (MISIONES.campana || []).forEach(function (c) {
            const li = nodo('li');
            li.appendChild(nodo('strong', null, t(c.titulo)));
            if (!c.disponible) { li.appendChild(nodo('small', null, t('próximamente'))); }
            else if (!c.habilitado) { li.appendChild(nodo('small', null, t('Terminá el anterior'))); }
            else {
                const a = nodo('a', 'boton-historia', c.estado === 'completado' ? t('Volver a jugar') : (c.punto ? t('Retomar') : t('Jugar')));
                a.href = ENLACES.campana + '?capitulo=' + encodeURIComponent(c.id);
                li.appendChild(a);
            }
            lh.appendChild(li);
        });
        const lc = $('lista-contactos');
        lc.textContent = '';
        MISIONES.contactos.forEach(function (c) {
            const li = nodo('li');
            li.appendChild(nodo('strong', null, c.nombre));
            const vuelve = c.vuelve === null || c.vuelve === undefined ? '' : ' · ' + t('vuelve a las {h}', { h: String(Math.floor(c.vuelve)).padStart(2, '0') + ':' + String(Math.round((c.vuelve % 1) * 60)).padStart(2, '0') });
            li.appendChild(nodo('small', null, t(c.actividad) + vuelve));
            li.appendChild(nodo('small', null, t('Ánimo: {a} · Confianza: {c}', { a: t(c.animo), c: c.confianza })));
            lc.appendChild(li);
        });
    }

    async function hablarCon(c) {
        destino = null;
        teclas.clear();
        const r = await post({ accion: 'hablar', contacto: c.id });
        if (!r.exito) { avisar(traducirAviso(r.mensaje), true); return; }
        mostrarDialogo(c, r);
        mostrarAvisos(r.avisos);
        cargarMisiones();
    }

    function pintarCabecera(contacto, emocion, confianza, animo) {
        $('dialogo-nombre').textContent = contacto.nombre;
        $('dialogo-rol').textContent = t(contacto.rol);
        $('dialogo-retrato').src = 'img/mundo/retratos/' + contacto.id + '_' + (emocion || 'neutral') + '.png';
        $('dialogo-retrato').alt = contacto.nombre;
        $('dialogo-estado').textContent = t('Ánimo: {a} · Confianza: {c}', { a: t(animo), c: confianza });
    }

    function botonDialogo(texto, clase, accion) { const b = nodo('button', clase, texto); b.type = 'button'; b.addEventListener('click', accion); return b; }

    /** Pinta una respuesta de hablar(): una misión, una oferta o una charla con opciones. */
    function mostrarDialogo(c, r) {
        const d = r.dialogo;
        pintarCabecera(r.contacto, d ? d.emocion : r.contacto.emocion, d ? d.confianza : r.contacto.confianza, d ? d.animo : r.contacto.animo);
        const textoMision = r.partes ? r.partes.map(function (x) { return t(x); }).join(' ') : t(r.texto);
        $('dialogo-texto').textContent = '«' + (d ? t(d.texto) : textoMision) + '»';
        const acciones = $('dialogo-acciones');
        acciones.textContent = '';
        if (d) {
            pintarOpciones(c, r.contacto, d);
        } else {
            mostrarOfertaOMision(c, r, acciones);
        }
        if (r.contacto.id === 'periodista') {   // la caja de Viktor: la campaña
            const caja = nodo('a', 'boton-secundario boton-caja', '📦 ' + t('La caja de Viktor'));
            caja.href = ENLACES.campana + '?capitulo=prologo';
            acciones.insertBefore(caja, acciones.lastChild);
        }
        $('panel-dialogo').hidden = false;
        dibujarIndicacion();
    }

    function pintarOpciones(c, contacto, d) {
        const acciones = $('dialogo-acciones');
        acciones.textContent = '';
        d.opciones.forEach(function (o) {
            acciones.appendChild(botonDialogo(t(o.texto), 'opcion-dialogo', async function () {
                const r = await post({ accion: 'charlar', contacto: c.id, nodo: d.nodo, opcion: o.i });
                if (!r.exito) { avisar(t(r.mensaje), true); return; }
                pintarCabecera(contacto, r.dialogo.emocion, r.dialogo.confianza, r.dialogo.animo);
                $('dialogo-texto').textContent = '«' + t(r.dialogo.texto) + '»';
                pintarOpciones(c, contacto, r.dialogo);
                cargarMisiones();
            }));
        });
        acciones.appendChild(botonDialogo(d.opciones.length ? t('Chau') : t('Cerrar'), 'boton-secundario', cerrarDialogo));
    }

    function mostrarOfertaOMision(c, r, acciones) {
        const charlar = botonDialogo(t('Charlar'), 'boton-secundario', async function () {
            const x = await post({ accion: 'charlar', contacto: c.id, nodo: '', opcion: 0 });
            if (!x.exito) { avisar(t(x.mensaje), true); return; }
            mostrarDialogo(c, { contacto: r.contacto, dialogo: x.dialogo });
        });
        if (r.desafio) {   // misión de tablero (etapa C): partida contra el bot del contacto
            acciones.appendChild(nodo('p', 'recompensa', '🎲 ' + t(r.desafio.reto)));
            if (r.desafio.secundario) acciones.appendChild(nodo('p', 'recompensa', t('Objetivo extra: {o}', { o: t(r.desafio.secundario) })));
            acciones.appendChild(botonDialogo(r.desafio.enJuego ? t('Volver a la partida') : t('Jugar la partida'), 'boton-principal', async function () {
                const x = await post({ accion: 'desafiar', mision: r.desafio.mision });
                if (!x.exito) { avisar(traducirAviso(x.mensaje), true); return; }
                window.location.href = ENLACES.partida + '?id=' + x.idPartida;
            }));
        }
        if (r.oferta) {
            acciones.appendChild(nodo('p', 'recompensa', t('{titulo} · recompensa: {xp} XP y {puntos} puntos de tienda', { titulo: t(r.oferta.titulo), xp: r.oferta.xp, puntos: r.oferta.puntos })));
            const si = nodo('button', 'boton-principal', t('Aceptar'));
            si.type = 'button';
            si.addEventListener('click', async function () {
                const a = await post({ accion: 'aceptar', mision: r.oferta.id });
                cerrarDialogo();
                if (!a.exito) { avisar(t(a.mensaje), true); return; }
                mostrarAvisos([a.mensaje].concat(a.avisos || []));
                cargarMisiones();
            });
            acciones.appendChild(si);
        }
        acciones.appendChild(charlar);
        const no = nodo('button', 'boton-secundario', r.oferta ? t('Ahora no') : t('Cerrar'));
        no.type = 'button';
        no.addEventListener('click', cerrarDialogo);
        acciones.appendChild(no);
    }
    function cerrarDialogo() { $('panel-dialogo').hidden = true; dibujarIndicacion(); }

    $('btn-misiones').addEventListener('click', function () {
        if (!MISIONES) return;
        dibujarPanelMisiones();
        $('panel-misiones').hidden = false;
    });
    $('cerrar-misiones').addEventListener('click', function () { $('panel-misiones').hidden = true; });

    /* ---------- social (Fase 4) ---------- */

    function aplicarSocial(s) {
        grupoIds = new Set(s.grupo ? s.grupo.miembros.filter(function (id) { return id !== yo.id; }) : []);
        const pendientes = s.solicitudes + s.invitaciones.length;
        const insignia = $('insignia-social');
        insignia.textContent = pendientes ? String(pendientes) : '';
        insignia.hidden = !pendientes;
        const ids = s.invitaciones.map(function (i) { return i.id; });
        if (invitacionesVistas !== null) {
            s.invitaciones.filter(function (i) { return invitacionesVistas.indexOf(i.id) < 0; }).forEach(function (i) {
                avisar(i.tipo === 'mesa' ? t('{de} te invita a su mesa. Abrí Social para entrar.', { de: i.de }) : t('{de} te invita a su grupo. Abrí Social para sumarte.', { de: i.de }));
            });
        }
        invitacionesVistas = ids;
    }

    let socialMontado = false;
    $('btn-social').addEventListener('click', function () {
        $('panel-social').hidden = false;
        if (!socialMontado) {
            socialMontado = true;
            Social.montar($('social-mundo'), { api: cuerpo.dataset.apiSocial, csrf: CSRF, partida: ENLACES.partida, diccionario: diccionario, alAvisar: avisar });
        } else {
            Social.cargar();
        }
    });
    $('cerrar-social').addEventListener('click', function () { $('panel-social').hidden = true; });
    Social.configurar({ api: cuerpo.dataset.apiSocial, csrf: CSRF, partida: ENLACES.partida, diccionario: diccionario, alAvisar: avisar });

    async function hacerGesto(n) {
        if (yo.x === null) return;
        destino = null;
        yo.gesto = n;
        yo.gestoHasta = performance.now() + 3000;
        $('menu-gestos').hidden = true;
        post({ accion: 'gesto', gesto: n });
    }
    $('btn-gestos').addEventListener('click', function () { $('menu-gestos').hidden = !$('menu-gestos').hidden; });
    document.querySelectorAll('.opcion-gesto').forEach(function (b) {
        b.addEventListener('click', function () { hacerGesto(Number(b.dataset.gesto)); });
    });

    /* ---------- sucesos: radio, diario y mercado (etapa B) ---------- */

    function tituloSuceso(s) { return t(s.titulo, { distrito: s.nombreDistrito || '' }); }
    let mensajesRadio = [], indiceRadio = 0;
    function armarRadio() {
        const emisora = yo.bando === 'este' ? 'Berliner Rundfunk' : 'RIAS';
        mensajesRadio = [];
        if (SUCESOS.actual) mensajesRadio.push(SUCESOS.actual.icono + ' ' + tituloSuceso(SUCESOS.actual) + '. ' + t(SUCESOS.actual.descripcion));
        if (SUCESOS.proximo) mensajesRadio.push(t('Más tarde: {suceso}', { suceso: tituloSuceso(SUCESOS.proximo) }));
        let oeste = 0, este = 0;
        Object.keys(dominio).forEach(function (id) { if (dominio[id].domina === 'oeste') oeste++; if (dominio[id].domina === 'este') este++; });
        mensajesRadio.push(t('La ciudad: el Oeste domina {o} distritos y el Este {e}.', { o: oeste, e: este }));
        mensajesRadio = mensajesRadio.map(function (m) { return '📻 ' + emisora + ' · ' + m; });
        $('radio').textContent = mensajesRadio[indiceRadio % mensajesRadio.length] || '';
    }
    setInterval(function () { if (mensajesRadio.length) { indiceRadio++; $('radio').textContent = mensajesRadio[indiceRadio % mensajesRadio.length]; } }, 7000);

    async function visitarMercado() {
        const r = await post({ accion: 'visitar_suceso' });
        avisar(traducirAviso(r.mensaje) + (r.exito ? ' (+' + r.xp + ' XP)' : ''), !r.exito);
        if (r.exito) cargarMisiones();
    }

    const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    function textoTitular(plantilla, valores, traducirClaves) {
        const v = {};
        Object.keys(valores || {}).forEach(function (k) { v[k] = (traducirClaves || []).indexOf(k) >= 0 ? t(valores[k]) : valores[k]; });
        return t(plantilla, v);
    }
    let ladoDiario = null;
    async function abrirDiario(lado) {
        const d = await pedir('accion=diario' + (lado ? '&lado=' + lado : ''));
        if (!d.exito) return;
        ladoDiario = d.lado;
        $('diario-nombre').textContent = d.nombre;
        $('diario-fecha').textContent = t('Berlín, {d} de {m} de {a}', { d: d.fecha.dia, m: t(MESES[d.fecha.mes - 1]), a: d.fecha.anio });
        const lista = $('diario-titulares');
        lista.textContent = '';
        d.titulares.forEach(function (x, i) {
            const art = nodo('article', i === 0 ? 'principal' : '');
            art.appendChild(nodo(i === 0 ? 'h3' : 'h4', null, (x.prefijo ? t(x.prefijo) + ': ' : '') + textoTitular(x.plantilla, x.valores, x.traducir)));
            if (x.bajada) art.appendChild(nodo('p', null, textoTitular(x.bajada, x.valoresBajada, x.traducirBajada)));
            lista.appendChild(art);
        });
        $('diario-otro').textContent = d.lado === 'oeste' ? t('Leer el diario del Este') : t('Leer el diario del Oeste');
        $('panel-diario').hidden = false;
    }
    $('btn-diario').addEventListener('click', function () { abrirDiario(''); });
    $('diario-otro').addEventListener('click', function () { abrirDiario(ladoDiario === 'oeste' ? 'este' : 'oeste'); });
    $('cerrar-diario').addEventListener('click', function () { $('panel-diario').hidden = true; });

    /* Mensajes del servidor que llevan datos adentro: se traducen por partes. */
    const AVISOS = [
        [/^Misión completada: (.+) \(\+(\d+) XP, \+(\d+) puntos de tienda\)$/, function (m) { return t('Misión completada: {m} (+{xp} XP, +{p} puntos de tienda)', { m: t(m[1]), xp: m[2], p: m[3] }); }],
        [/^Objetivo cumplido: (.+)$/, function (m) { return t('Objetivo cumplido: {m}', { m: t(m[1]) }); }],
        [/^Misión aceptada: (.+)$/, function (m) { return t('Misión aceptada: {m}', { m: t(m[1]) }); }],
        [/^Logro: (.+)$/, function (m) { return t('Logro: {l}', { l: t(m[1]) }); }],
        [/^¡Subiste al nivel (\d+)!$/, function (m) { return t('¡Subiste al nivel {n}!', { n: m[1] }); }],
        [/^(.+) no está: (.+)\. Vuelve a las (\d\d:\d\d)\.$/, function (m) { return t('{n} no está: {a}. Vuelve a las {h}.', { n: m[1], a: t(m[2]), h: m[3] }); }],
        [/^(.+) no está: (.+)\.$/, function (m) { return t('{n} no está: {a}.', { n: m[1], a: t(m[2]) }); }],
    ];
    function traducirAviso(texto) {
        if (!texto) return '';
        for (let i = 0; i < AVISOS.length; i++) { const m = texto.match(AVISOS[i][0]); if (m) return AVISOS[i][1](m); }
        return t(texto);
    }
    window.MundoTraducir = function (texto) { return t(texto); };

    /* ---------- sonido ---------- */

    function botonSonido() {
        const si = SonidoMundo.encendido();
        $('btn-sonido').textContent = si ? '🔊' : '🔇';
        $('btn-sonido').setAttribute('aria-label', si ? t('Silenciar el sonido') : t('Activar el sonido'));
    }
    $('btn-sonido').addEventListener('click', function () { SonidoMundo.alternar(); botonSonido(); });
    // Los navegadores solo dejan sonar audio después de un gesto del jugador
    ['keydown', 'pointerdown'].forEach(function (ev) {
        document.addEventListener(ev, function () { if (SonidoMundo.encendido()) SonidoMundo.iniciar(); }, { once: true });
    });
    botonSonido();

    /* ---------- apariencia ---------- */

    const elegida = { piel: 0, pelo: 0, gorro: 0 };
    function dibujarVistaPrevia() {
        if (!escena) return;
        const lienzo = $('vista-apariencia'), c = lienzo.getContext('2d');
        const fuente = escena.textures.get('pj_' + (yo.bando || 'neutro')).getSourceImage();
        const v = elegida.piel * 12 + elegida.pelo * 3 + elegida.gorro;
        const cuadro = MundoVivo.cuadro(v, 2, 0, true);
        c.imageSmoothingEnabled = false;
        c.clearRect(0, 0, lienzo.width, lienzo.height);
        c.drawImage(fuente, (cuadro % 18) * 32, Math.floor(cuadro / 18) * 48, 32, 48, 0, 0, lienzo.width, lienzo.height);
        document.querySelectorAll('.opcion-apariencia').forEach(function (b) {
            b.setAttribute('aria-pressed', String(elegida[b.dataset.campo] === Number(b.dataset.valor)));
        });
    }
    $('btn-apariencia').addEventListener('click', function () {
        if (ENLACES.personalizar) { window.location.href = ENLACES.personalizar + '#personaje'; return; }   // el editor completo
        Object.assign(elegida, yo.apariencia);
        $('panel-apariencia').hidden = false;
        dibujarVistaPrevia();
    });
    $('cerrar-apariencia').addEventListener('click', function () { $('panel-apariencia').hidden = true; });
    document.querySelectorAll('.opcion-apariencia').forEach(function (b) {
        b.addEventListener('click', function () { elegida[b.dataset.campo] = Number(b.dataset.valor); dibujarVistaPrevia(); });
    });
    $('guardar-apariencia').addEventListener('click', async function () {
        const res = await post({ accion: 'apariencia', piel: elegida.piel, pelo: elegida.pelo, gorro: elegida.gorro });
        if (!res.exito) { avisar(t(res.mensaje), true); return; }
        yo.apariencia = Object.assign({}, elegida);
        yo.ap = res.indice;
        $('panel-apariencia').hidden = true;
        avisar(t('Listo: así te ven los demás.'));
    });

    /* ---------- teclado y chat ---------- */

    const TECLAS = { ArrowUp: 'arriba', KeyW: 'arriba', ArrowDown: 'abajo', KeyS: 'abajo', ArrowLeft: 'izquierda', KeyA: 'izquierda', ArrowRight: 'derecha', KeyD: 'derecha' };
    const entradaChat = $('texto-chat');

    document.addEventListener('keydown', function (ev) {
        if (document.activeElement === entradaChat) {
            if (ev.key === 'Escape') entradaChat.blur();
            return;
        }
        if (!$('capa-bando').hidden || !$('panel-apariencia').hidden || !$('panel-dialogo').hidden || document.getElementById('tarjeta-perfil')) return;
        if (document.activeElement && document.activeElement.tagName === 'INPUT') return;
        if (TECLAS[ev.code]) { teclas.add(TECLAS[ev.code]); ev.preventDefault(); }
        if (ev.code === 'KeyE' && $('panel').hidden && $('panel-dialogo').hidden) {
            if (contactoCerca) hablarCon(contactoCerca); else if (cercaDelMercado) visitarMercado(); else if (edificioCerca) abrirEdificio(edificioCerca);
        }
        if (ev.key === 'Escape') { $('panel').hidden = true; dibujarIndicacion(); }
        if (ev.key === 'Enter') { ev.preventDefault(); entradaChat.focus(); }
        if (['Digit1', 'Digit2', 'Digit3', 'Digit4'].indexOf(ev.code) >= 0) hacerGesto(Number(ev.code.slice(-1)));
        if (ev.code === 'KeyV') cambiarVista();
    });
    document.addEventListener('keyup', function (ev) { if (TECLAS[ev.code]) teclas.delete(TECLAS[ev.code]); });
    window.addEventListener('blur', function () { teclas.clear(); });
    /* Primera persona: el mismo botón la activa y vuelve a la vista normal (que es la predeterminada). */
    function cambiarVista() {
        if (!primera) return;
        const activar = !primera.activo();
        primera.activar(activar, yo.dir);
        destino = null;
        const b = $('btn-vista');
        b.setAttribute('aria-pressed', activar ? 'true' : 'false');
        b.textContent = activar ? '🗺' : '👁';
        b.title = activar ? t('Vista normal') : t('Primera persona');
        b.setAttribute('aria-label', b.title);
        document.body.classList.toggle('en-primera-persona', activar);
    }
    $('btn-vista').addEventListener('click', cambiarVista);
    $('indicacion').addEventListener('click', function () { if (contactoCerca) hablarCon(contactoCerca); else if (cercaDelMercado) visitarMercado(); else if (edificioCerca) abrirEdificio(edificioCerca); });

    $('form-chat').addEventListener('submit', async function (ev) {
        ev.preventDefault();
        const texto = entradaChat.value.trim();
        if (!texto) { entradaChat.blur(); return; }
        entradaChat.value = '';
        const res = await post({ accion: 'decir', texto: texto });
        if (!res.exito) avisar(t(res.mensaje), true);
        else {
            const previa = burbujas.get('yo');
            if (previa && previa.objeto) previa.objeto.destroy();
            burbujas.set('yo', { texto: texto, hasta: performance.now() + 6000, idMensaje: 0, objeto: null });
        }
        entradaChat.blur();
    });

    /* ---------- arranque ---------- */

    async function iniciar() {
        if (TACTIL) {
            $('ayuda').textContent = t('Tocá el mapa para caminar · tocá el cartel para entrar');
            entradaChat.placeholder = t('Escribí a los que están cerca…');
            $('juego').setAttribute('aria-label', t('Mapa de Berlín. Tocá un lugar para caminar hasta ahí.'));
        }
        if ((cuerpo.dataset.idioma || 'es') !== 'es') {
            try {
                const r = await fetch('./lang/' + cuerpo.dataset.idioma + '.json');
                if (r.ok) diccionario = await r.json();
                Social.configurar({ api: cuerpo.dataset.apiSocial, csrf: CSRF, partida: ENLACES.partida, diccionario: diccionario, alAvisar: avisar });
            } catch (error) { console.error(error); }
        }
        const datosMapa = await pedir('accion=mapa');
        if (!datosMapa.exito) { avisar(t('No se pudo cargar el mapa.'), true); return; }
        MAPA = datosMapa;
        COLISION = MAPA.tiled.layers.find(function (l) { return l.name === 'colision'; }).data;

        const estado = await pedir();
        aplicarCompleto(estado);
        ultimoCompleto = performance.now();
        if (yo.bando && yo.x === null) {
            // Tiene bando pero no está en el mapa (primera vez o MySQL se reinició): el servidor lo hace aparecer.
            const datos = await post({ accion: 'mover', x: 0, y: 0, dir: 2 });
            if (datos.posicion) { yo.x = datos.posicion.x; yo.y = datos.posicion.y; }
        }

        new Phaser.Game({
            type: Phaser.AUTO,
            parent: 'juego',
            backgroundColor: '#070c1c',
            scale: { mode: Phaser.Scale.RESIZE, width: window.innerWidth, height: window.innerHeight },
            render: { pixelArt: true, roundPixels: true, antialias: false },
            loader: { imageLoadType: 'HTMLImageElement' },   // la política de seguridad no permite blob:
            audio: { noAudio: true },
            input: { keyboard: false, gamepad: false },
            banner: false,
            disableContextMenu: true,
            scene: Berlin
        });
        cargarMisiones();
    }
    iniciar();
})();
