/*
 * MUNDO VIVO (Fase 1): día y noche, luces, clima y NPCs ambientales.
 *
 * Todo depende del RELOJ DEL SERVIDOR: la hora, el clima y la posición de cada
 * peatón, patrulla y tranvía se calculan con la misma fórmula en todos los
 * navegadores, así todos ven lo mismo sin cargar al servidor.
 *
 * Para pruebas: window.__mundoForzar = { hora: 22, clima: 'lluvia' } fuerza lo que se ve
 * en ESE navegador (no cambia nada del servidor ni de los demás).
 */
window.MundoVivo = (function () {
    'use strict';

    const CAPA_OSCURIDAD = 200000, CAPA_HALOS = 200001, CAPA_CLIMA = 200002, CAPA_NIEBLA = 200003;
    // dirección del servidor (0 arriba, 1 derecha, 2 abajo, 3 izquierda) -> fila de la hoja
    const FILA = [3, 2, 0, 1];

    let desfase = null;          // segundos: reloj del servidor - reloj local
    let reloj = { segundosPorDia: 1800, clima: 'despejado', climaSiguiente: 'despejado', cambioClima: 0 };

    /* ---------- reloj compartido ---------- */

    function sincronizar(r) {
        const local = Date.now() / 1000;
        const nuevo = r.ahora - local;
        desfase = desfase === null ? nuevo : desfase * 0.7 + nuevo * 0.3;
        reloj.segundosPorDia = r.segundosPorDia;
        reloj.clima = r.clima;
        reloj.climaSiguiente = r.climaSiguiente;
        reloj.cambioClima = r.ahora + r.segundosClima;
    }

    function ahora() { return Date.now() / 1000 + (desfase || 0); }

    function hora() {
        const f = window.__mundoForzar;
        if (f && typeof f.hora === 'number') return f.hora;
        return (ahora() % reloj.segundosPorDia) / reloj.segundosPorDia * 24;
    }

    function clima() {
        const f = window.__mundoForzar;
        if (f && f.clima) return f.clima;
        return ahora() >= reloj.cambioClima ? reloj.climaSiguiente : reloj.clima;
    }

    /** 0 = pleno día, 1 = noche cerrada. Atardecer de 18 a 20:30, amanecer de 5:30 a 7:30. */
    function nocturnidad(h) {
        if (h >= 20.5 || h < 5.5) return 1;
        if (h >= 18) return (h - 18) / 2.5;
        if (h < 7.5) return 1 - (h - 5.5) / 2;
        return 0;
    }

    /** Cuadro de una hoja de personaje. Atlas de 36 variantes (6 x 6, cada una de 3 x 6 cuadros) o una hoja suelta de 3 x 6. */
    function indice(variante, fila, paso, esAtlas) {
        if (!esAtlas) return fila * 3 + paso;
        return (Math.floor(variante / 6) * 6 + fila) * 18 + (variante % 6) * 3 + paso;
    }
    function cuadro(variante, dirServidor, paso, esAtlas) {
        return indice(variante, FILA[dirServidor || 0], paso, esAtlas);
    }
    /** Gestos (filas 4 y 5 de cada hoja): 1 saludar, 2 aplaudir, 3 encogerse, 4 sentarse. */
    function cuadroGesto(variante, gesto, esAtlas) {
        const t = performance.now();
        let fila = 5, paso = 0;
        if (gesto === 1) { fila = 4; paso = Math.floor(t / 260) % 2; }
        else if (gesto === 2) { fila = Math.floor(t / 180) % 2 ? 5 : 4; paso = 2; }
        else if (gesto === 3) { fila = 5; paso = 1; }
        return indice(variante, fila, paso, esAtlas);
    }

    function pasoAnimado(mueve, semilla) {
        if (!mueve) return 0;
        return [1, 0, 2, 0][Math.floor(performance.now() / 125 + (semilla || 0)) % 4];
    }

    /* ---------- texturas que se dibujan al vuelo (no hay archivos) ---------- */

    function crearTexturas(escena) {
        if (escena.textures.exists('luz')) return;
        const luz = escena.textures.createCanvas('luz', 256, 256);
        let c = luz.getContext();
        let g = c.createRadialGradient(128, 128, 0, 128, 128, 128);
        g.addColorStop(0, 'rgba(255,255,255,1)'); g.addColorStop(0.45, 'rgba(255,255,255,0.55)'); g.addColorStop(1, 'rgba(255,255,255,0)');
        c.fillStyle = g; c.fillRect(0, 0, 256, 256); luz.refresh();

        // Cono del reflector: apunta a la derecha desde el borde izquierdo (origen 0, 0.5)
        const cono = escena.textures.createCanvas('cono', 512, 256);
        c = cono.getContext();
        g = c.createLinearGradient(0, 0, 512, 0);
        g.addColorStop(0, 'rgba(255,255,255,0.9)'); g.addColorStop(1, 'rgba(255,255,255,0)');
        c.fillStyle = g;
        c.beginPath(); c.moveTo(0, 120); c.lineTo(512, 0); c.lineTo(512, 256); c.lineTo(0, 136); c.closePath(); c.fill();
        cono.refresh();

        const gota = escena.textures.createCanvas('gota', 2, 14);
        c = gota.getContext(); c.fillStyle = 'rgba(190,210,235,0.9)'; c.fillRect(0, 0, 2, 14); gota.refresh();
        const copo = escena.textures.createCanvas('copo', 6, 6);
        c = copo.getContext(); c.fillStyle = 'white'; c.beginPath(); c.arc(3, 3, 3, 0, Math.PI * 2); c.fill(); copo.refresh();

        const niebla = escena.textures.createCanvas('niebla', 512, 512);
        c = niebla.getContext();
        for (let i = 0; i < 70; i++) {
            const x = (i * 97) % 512, y = (i * 173) % 512, r = 60 + (i * 37) % 90;
            const gg = c.createRadialGradient(x, y, 0, x, y, r);
            gg.addColorStop(0, 'rgba(200,205,215,0.35)'); gg.addColorStop(1, 'rgba(200,205,215,0)');
            c.fillStyle = gg;
            for (const [ox, oy] of [[0, 0], [512, 0], [-512, 0], [0, 512], [0, -512]]) { c.fillRect(x - r + ox, y - r + oy, r * 2, r * 2); }
        }
        niebla.refresh();
    }

    /* ---------- creación ---------- */

    function crear(escena, MAPA) {
        crearTexturas(escena);
        const T = MAPA.tile, cols = MAPA.ancho / T;
        const tileset = MAPA.tiled.tilesets[0];
        const gidDe = {};
        (tileset.tiles || []).forEach(function (t) {
            const p = (t.properties || []).find(function (q) { return q.name === 'nombre'; });
            if (p) gidDe[p.value] = tileset.firstgid + t.id;
        });
        const deco = MAPA.tiled.layers.find(function (l) { return l.name === 'decoracion'; }).data;
        const objetos = MAPA.tiled.layers.find(function (l) { return l.name === 'objetos'; }).objects;

        // Luces: faroles, ventanas de edificios y reflectores de las torres
        const luces = [];
        const reflectores = [];
        deco.forEach(function (gid, i) {
            const x = (i % cols) * T + T / 2, y = Math.floor(i / cols) * T;
            if (gid === gidDe.farol) luces.push({ x: x, y: y + 8, r: 150, fuerza: 1, color: 0xffd27a });
            if (gid === gidDe.torre) reflectores.push({ x: x, y: y + 10, fase: (i * 0.37) % 6.28 });
        });
        MAPA.edificios.forEach(function (b) {
            (b.luces || []).forEach(function (l, k) {
                // Algunas ventanas quedan apagadas (siempre las mismas): más creíble que todas prendidas
                if ((k * 7 + b.x) % 5 === 0) return;
                luces.push({ x: b.x + l[0], y: b.y + b.h - l[1], r: 46, fuerza: 0.75, color: b.id === 'fernsehturm' && l[1] > 200 ? 0xff3030 : 0xffc870 });
            });
        });

        // Halos aditivos (se ven "encendidas" por encima de la oscuridad)
        const halos = luces.map(function (l) {
            return escena.add.image(l.x, l.y, 'luz').setScale(l.r / 128 * 0.7).setTint(l.color)
                .setBlendMode(Phaser.BlendModes.ADD).setDepth(CAPA_HALOS).setAlpha(0);
        });
        const conos = reflectores.map(function (r) {
            return escena.add.image(r.x, r.y, 'cono').setOrigin(0, 0.5).setScale(0.75, 0.55)
                .setBlendMode(Phaser.BlendModes.ADD).setTint(0xfff6d6).setDepth(CAPA_HALOS).setAlpha(0);
        });

        // Capa de oscuridad: se pinta entera y se "borran" las zonas iluminadas
        const cam = escena.cameras.main;
        let oscuridad = escena.add.renderTexture(0, 0, cam.width, cam.height).setOrigin(0, 0).setScrollFactor(0).setDepth(CAPA_OSCURIDAD);
        const pincel = escena.make.image({ key: 'luz', add: false }).setOrigin(0.5);
        const pincelCono = escena.make.image({ key: 'cono', add: false }).setOrigin(0, 0.5);

        // Clima
        let lluvia = null, nieve = null, intensidadLluvia = 0, intensidadNieve = 0, intensidadNiebla = 0;
        const niebla = escena.add.tileSprite(0, 0, cam.width, cam.height, 'niebla').setOrigin(0, 0).setScrollFactor(0).setDepth(CAPA_NIEBLA).setAlpha(0);

        function crearEmisores() {
            if (lluvia) lluvia.destroy();
            if (nieve) nieve.destroy();
            const zona = { source: new Phaser.Geom.Rectangle(-100, -30, cam.width + 300, 10), type: 'random' };
            lluvia = escena.add.particles(0, 0, 'gota', {
                emitZone: zona, lifespan: 900, speedY: { min: 720, max: 900 }, speedX: { min: -170, max: -130 },
                rotate: 10, alpha: { start: 0.55, end: 0.25 }, quantity: 5, frequency: 16, emitting: false
            }).setScrollFactor(0).setDepth(CAPA_CLIMA);
            lluvia.stop();
            nieve = escena.add.particles(0, 0, 'copo', {
                emitZone: zona, lifespan: 14000, speedY: { min: 35, max: 80 }, speedX: { min: -25, max: 25 },
                scale: { min: 0.35, max: 0.9 }, alpha: { min: 0.5, max: 0.9 }, quantity: 1, frequency: 70, emitting: false
            }).setScrollFactor(0).setDepth(CAPA_CLIMA);
            nieve.stop();
        }
        crearEmisores();
        escena.scale.on('resize', function (tam) {
            oscuridad.destroy();
            oscuridad = escena.add.renderTexture(0, 0, tam.width, tam.height).setOrigin(0, 0).setScrollFactor(0).setDepth(CAPA_OSCURIDAD);
            niebla.setSize(tam.width, tam.height);
            crearEmisores();
        });

        // NPCs: rutas del mapa recorridas según el reloj compartido
        const npcs = [];
        objetos.filter(function (o) { return o.type === 'ruta'; }).forEach(function (o, iRuta) {
            const prop = {};
            (o.properties || []).forEach(function (p) { prop[p.name] = p.value; });
            const puntos = o.polyline.map(function (p) { return { x: o.x + p.x, y: o.y + p.y }; });
            const tramos = [];
            let largo = 0;
            for (let i = 1; i < puntos.length; i++) {
                const d = Math.hypot(puntos[i].x - puntos[i - 1].x, puntos[i].y - puntos[i - 1].y);
                tramos.push({ a: puntos[i - 1], b: puntos[i], desde: largo, largo: d });
                largo += d;
            }
            for (let k = 0; k < (prop.cantidad || 1); k++) {
                const n = {
                    clase: prop.clase, modo: prop.modo, velocidad: prop.velocidad || 40, tramos: tramos, largo: largo,
                    desfase: largo * k / (prop.cantidad || 1) + iRuta * 53, variante: (iRuta * 7 + k * 11) % 36, semilla: iRuta + k
                };
                if (n.clase === 'tranvia') {
                    n.sprite = escena.add.image(0, 0, 'tranvia').setOrigin(0.5, 1);
                } else {
                    n.sombra = escena.add.ellipse(0, 0, 20, 8, 0x000000, 0.3).setDepth(1);
                    n.sprite = escena.add.sprite(0, 0, n.clase === 'patrulla' ? 'npc_soldado' : 'npc_civiles', 0).setOrigin(0.5, 1);
                }
                npcs.push(n);
            }
        });
        objetos.filter(function (o) { return o.type === 'guardia'; }).forEach(function (o) {
            const prop = {};
            (o.properties || []).forEach(function (p) { prop[p.name] = p.value; });
            escena.add.ellipse(o.x, o.y - 3, 20, 8, 0x000000, 0.3).setDepth(1);
            escena.add.sprite(o.x, o.y, prop.clase === 'mp' ? 'npc_mp' : 'npc_soldado', cuadro(0, prop.dir || 2, 0, false)).setOrigin(0.5, 1).setDepth(o.y);
        });

        function posicionEnRuta(n, t) {
            let s = t * n.velocidad + n.desfase;
            let adelante = true;
            if (n.modo === 'ida_vuelta') {
                s = s % (2 * n.largo);
                if (s > n.largo) { s = 2 * n.largo - s; adelante = false; }
            } else {
                s = s % n.largo;
            }
            const tr = n.tramos.find(function (x) { return s <= x.desde + x.largo; }) || n.tramos[n.tramos.length - 1];
            const f = tr.largo ? (s - tr.desde) / tr.largo : 0;
            let dx = tr.b.x - tr.a.x, dy = tr.b.y - tr.a.y;
            if (!adelante) { dx = -dx; dy = -dy; }
            const dir = Math.abs(dx) > Math.abs(dy) ? (dx > 0 ? 1 : 3) : (dy > 0 ? 2 : 0);
            return { x: tr.a.x + (tr.b.x - tr.a.x) * f, y: tr.a.y + (tr.b.y - tr.a.y) * f, dir: dir };
        }

        /* ---------- sucesos (etapa B): cada uno arma su escena ---------- */

        let suceso = null, decorado = [], caravana = [], multitud = [], burbujaSuceso = null, apagon = null;
        const CONSIGNAS = ['¡Abran el paso!', '¡Libertad de prensa!', '¡Que vuelvan las familias!', '¡Basta de controles!'];
        function limpiarSuceso() {
            decorado.concat(caravana).forEach(function (o) { o.destroy(); });
            multitud.forEach(function (m) { m.sprite.destroy(); m.sombra.destroy(); if (m.cartel) m.cartel.destroy(); });
            if (burbujaSuceso) burbujaSuceso.destroy();
            decorado = []; caravana = []; multitud = []; burbujaSuceso = null; apagon = null;
        }
        /** Un lugar donde puede pararse gente: libre en el mapa y nunca en el Muro ni en la franja de la muerte. */
        const capaColision = MAPA.tiled.layers.find(function (l) { return l.name === 'colision'; }).data;
        function lugarValido(x, y) {
            if (x >= 1150 && x <= 1300) return false;
            const cols = MAPA.ancho / T, c0 = Math.floor(x / T), f0 = Math.floor(y / T);
            return x > 0 && y > 0 && x < MAPA.ancho && y < MAPA.alto && capaColision[f0 * cols + c0] === 0;
        }
        function persona(x, y, clave, variante, dir, conCartel, aMano) {
            if (!aMano && !lugarValido(x, y)) return null;   // aMano: guardias puestos en un lugar elegido
            const atlas = clave === 'npc_civiles';
            const m = { x: x, y: y, dir: dir, variante: variante, atlas: atlas,
                sombra: escena.add.ellipse(x, y - 3, 20, 8, 0x000000, 0.3).setDepth(1),
                sprite: escena.add.sprite(x, y, clave, cuadro(variante, dir, 0, atlas)).setOrigin(0.5, 1).setDepth(y) };
            if (conCartel) m.cartel = escena.add.image(x + 6, y - 30, 'suceso_cartel').setOrigin(0.5, 1).setDepth(y + 1);
            multitud.push(m);
            return m;
        }
        /** Prueba lugares hasta encontrar uno válido (siempre los mismos: el azar es determinista). */
        function enLugarValido(intentos, generar) {
            for (let k = 0; k < intentos; k++) { const p = generar(); if (lugarValido(p[0], p[1])) return p; }
            return null;
        }
        function ponerSuceso(s) {
            if ((s && suceso && s.tramo === suceso.tramo) || (!s && !suceso)) return;
            limpiarSuceso();
            suceso = s;
            if (!s) return;
            let azar = s.tramo % 9973;
            const rnd = function () { azar = (azar * 16807 + 7) % 2147483647; return (azar % 1000) / 1000; };
            if (s.tipo === 'control_checkpoint') {
                [[1100, 905, 'npc_mp', 1], [1100, 985, 'npc_mp', 1], [1270, 905, 'npc_soldado', 3], [1270, 985, 'npc_soldado', 3]].forEach(function (g) { persona(g[0], g[1], g[2], 0, g[3], false, true); });
            } else if (s.tipo === 'torneo_cafe') {
                decorado.push(escena.add.text(s.x, s.y - 150, '🏆 x2', { fontFamily: '"Saira Stencil One", Verdana, sans-serif', fontSize: '26px', color: '#f4c430', stroke: '#0b132b', strokeThickness: 6 }).setOrigin(0.5).setDepth(300000));
            } else if (s.tipo === 'mercado') {
                [-80, 0, 80].forEach(function (dx) { decorado.push(escena.add.image(s.x + dx, s.y + 20, 'suceso_puesto').setOrigin(0.5, 1).setDepth(s.y + 20)); });
                persona(s.x + 10, s.y + 34, 'npc_civiles', 5, 2, false);
                for (let i = 0; i < 5; i++) {
                    const p = enLugarValido(6, function () { return [s.x - 110 + i * 55 + rnd() * 20, s.y + 60 + rnd() * 30]; });
                    if (p) persona(p[0], p[1], 'npc_civiles', Math.floor(rnd() * 36), 0, false);
                }
            } else if (s.tipo === 'apagon') {
                apagon = MAPA.distritos.find(function (d) { return d.id === s.distrito; }) || null;
            } else if (s.tipo === 'manifestacion') {
                for (let i = 0; i < 16; i++) {
                    const p = enLugarValido(8, function () { const a = rnd() * Math.PI * 2, r = 30 + rnd() * 90; return [s.x + Math.cos(a) * r * 1.4, s.y + Math.sin(a) * r * 0.6]; });
                    if (p) persona(p[0], p[1], 'npc_civiles', Math.floor(rnd() * 36), Math.floor(rnd() * 4), rnd() < 0.4);
                }
            } else if (s.tipo === 'concierto') {
                decorado.push(escena.add.image(s.x, s.y - 30, 'suceso_escenario').setOrigin(0.5, 1).setDepth(s.y - 30));
                for (let i = 0; i < 18; i++) {
                    const p = enLugarValido(8, function () { return [s.x - 120 + rnd() * 240, s.y + 10 + rnd() * 80]; });
                    if (p) persona(p[0], p[1], 'npc_civiles', Math.floor(rnd() * 36), 0, false);
                }
            } else if (s.tipo === 'visita_oficial' && s.ruta) {
                for (let i = 0; i < 3; i++) caravana.push(escena.add.image(s.ruta[0][0], s.ruta[0][1], 'suceso_auto').setOrigin(0.5, 1));
                const guardia = (MAPA.distritos.find(function (d) { return d.id === s.distrito; }) || {}).lado === 'este' ? 'npc_soldado' : 'npc_mp';
                [s.ruta[0], s.ruta[1]].forEach(function (p, k) { persona(p[0] + (k ? -30 : 30), p[1] - 30, guardia, 0, 2, false, true); persona(p[0] + (k ? -30 : 30), p[1] + 34, guardia, 0, 0, false, true); });
            }
        }
        function animarSuceso(t, yo) {
            if (!suceso) return;
            if (caravana.length && suceso.ruta) {
                const a = suceso.ruta[0], b = suceso.ruta[1], largo = Math.abs(b[0] - a[0]);
                caravana.forEach(function (auto, i) {
                    let s = (t * 70 + i * 110) % (2 * largo);
                    const ida = s <= largo; if (!ida) s = 2 * largo - s;
                    auto.setPosition(Math.round(a[0] + Math.sign(b[0] - a[0]) * s), a[1] + 14).setDepth(a[1] + 14).setFlipX(!ida);
                });
            }
            multitud.forEach(function (m, i) {
                const salto = (suceso.tipo === 'manifestacion' || suceso.tipo === 'concierto') ? Math.abs(Math.sin(t * 3 + i)) * 3 : 0;
                m.sprite.y = m.y - salto;
                if (m.cartel) m.cartel.y = m.y - 30 - salto;
            });
            if ((suceso.tipo === 'manifestacion' || suceso.tipo === 'concierto') && multitud.length) {
                // (multitud solo tiene a quienes encontraron un lugar válido)
                const turno = Math.floor(t / 4);
                const quien = multitud[turno % multitud.length];
                const consigna = CONSIGNAS[turno % CONSIGNAS.length];
                const texto = suceso.tipo === 'concierto' ? '♪ ♫ ♪' : (window.MundoTraducir ? window.MundoTraducir(consigna) : consigna);
                if (!burbujaSuceso || burbujaSuceso.getData('turno') !== turno) {
                    if (burbujaSuceso) burbujaSuceso.destroy();
                    burbujaSuceso = escena.add.text(quien.x, quien.y - 70, texto, { fontFamily: 'Verdana, sans-serif', fontSize: '13px', color: '#0b132b', backgroundColor: '#eef1f7', padding: { x: 6, y: 3 } })
                        .setOrigin(0.5, 1).setDepth(300001).setData('turno', turno);
                }
            }
        }

        /* ---------- cada cuadro ---------- */

        /** En Phaser 3.6+ un emisor detenido se reactiva con start(), no cambiando 'emitting'. */
        function prender(emisor, si) {
            if (si && !emisor.emitting) emisor.start();
            if (!si && emisor.emitting) emisor.stop();
        }

        let climaVisible = clima();
        let vueltas = 0, ultimoMs = 0;
        function actualizar(dt, yo) {
            vueltas++;
            const t = ahora();
            const h = hora();
            const c = clima();
            climaVisible = c;

            // Clima: cada efecto entra y sale de a poco, según el tiempo real (no según los cuadros por segundo)
            const ms = performance.now();
            const segundos = Math.min(3, (ms - (ultimoMs || ms)) / 1000);
            ultimoMs = ms;
            const factor = 1 - Math.exp(-segundos * 0.8);
            const acercar = function (v, meta) { return v + (meta - v) * factor; };
            intensidadLluvia = acercar(intensidadLluvia, c === 'lluvia' ? 1 : 0);
            intensidadNieve = acercar(intensidadNieve, c === 'nieve' ? 1 : 0);
            intensidadNiebla = acercar(intensidadNiebla, c === 'niebla' ? 1 : 0);
            prender(lluvia, intensidadLluvia > 0.2);
            prender(nieve, intensidadNieve > 0.2);
            niebla.setAlpha(intensidadNiebla * 0.75);
            niebla.tilePositionX = cam.scrollX * 0.6 + t * 6;
            niebla.tilePositionY = cam.scrollY * 0.6;

            // Oscuridad según la hora, un poco más con mal tiempo
            const noche = nocturnidad(h);
            const nubes = { nublado: 0.10, lluvia: 0.18, niebla: 0.06, nieve: 0.08 }[c] || 0;
            const alfa = Math.min(0.8, noche * 0.66 + nubes * (1 - noche * 0.5));
            const atardecer = (h >= 17 && h < 21) || (h >= 5 && h < 8) ? 1 - Math.abs(noche - 0.5) * 2 : 0;
            const color = Phaser.Display.Color.Interpolate.ColorWithColor(
                Phaser.Display.Color.ValueToColor(0x0a1236), Phaser.Display.Color.ValueToColor(0x6a2e3a), 100, Math.round(atardecer * 45));
            oscuridad.clear();
            if (alfa > 0.01) {
                oscuridad.fill(Phaser.Display.Color.GetColor(color.r, color.g, color.b), alfa);
                const encendido = Math.max(0, Math.min(1, (noche - 0.15) / 0.5));
                const vx0 = cam.scrollX - 200, vx1 = cam.scrollX + cam.width + 200, vy0 = cam.scrollY - 200, vy1 = cam.scrollY + cam.height + 200;
                if (encendido > 0) {
                    luces.forEach(function (l) {
                        if (l.x < vx0 || l.x > vx1 || l.y < vy0 || l.y > vy1) return;
                        if (apagon && l.x >= apagon.x && l.x < apagon.x + apagon.w && l.y >= apagon.y && l.y < apagon.y + apagon.h) return;
                        pincel.setScale(l.r / 128).setAlpha(encendido * l.fuerza);
                        oscuridad.erase(pincel, l.x - cam.scrollX, l.y - cam.scrollY);
                    });
                    reflectores.forEach(function (r, i) {
                        // Barren la franja de la muerte a lo largo (hacia el norte o el sur, alternando torres)
                        const angulo = (i % 2 ? -Math.PI / 2 : Math.PI / 2) + Math.sin(t * 0.35 + r.fase) * 0.55;
                        conos[i].setRotation(angulo).setAlpha(encendido * 0.18);
                        if (r.x < vx0 - 400 || r.x > vx1 + 400 || r.y < vy0 - 400 || r.y > vy1 + 400) return;
                        pincelCono.setRotation(angulo).setScale(0.75, 0.55).setAlpha(encendido * 0.85);
                        oscuridad.erase(pincelCono, r.x - cam.scrollX, r.y - cam.scrollY);
                    });
                }
                if (yo && yo.x !== null) {   // un poco de luz alrededor tuyo para que te veas
                    pincel.setScale(0.8).setAlpha(0.45);
                    oscuridad.erase(pincel, yo.x - cam.scrollX, yo.y - 20 - cam.scrollY);
                }
                halos.forEach(function (o, i) {
                    const l = luces[i], sinLuz = apagon && l.x >= apagon.x && l.x < apagon.x + apagon.w && l.y >= apagon.y && l.y < apagon.y + apagon.h;
                    o.setAlpha(sinLuz ? 0 : encendido * 0.32 * l.fuerza);
                });
                if (encendido === 0) conos.forEach(function (o) { o.setAlpha(0); });
            } else {
                halos.forEach(function (o) { o.setAlpha(0); });
                conos.forEach(function (o) { o.setAlpha(0); });
            }

            // NPCs. De noche queda un tercio de los peatones y con lluvia la mitad (siempre los mismos: es determinista).
            npcs.forEach(function (n) {
                const p = posicionEnRuta(n, t);
                if (n.clase === 'civil') {
                    const visible = !((noche > 0.6 && n.semilla % 3 !== 0) || (c === 'lluvia' && n.semilla % 2 !== 0));
                    n.sprite.setVisible(visible); n.sombra.setVisible(visible);
                    if (!visible) return;
                    // Esquiva: si pasás cerca, se corre un poco hacia el costado (solo en tu pantalla)
                    let meta = 0;
                    if (yo && yo.x !== null && Math.hypot(yo.x - p.x, yo.y - p.y) < 34) {
                        const lado = (p.dir === 0 || p.dir === 2) ? (yo.x > p.x ? -1 : 1) : (yo.y > p.y ? -1 : 1);
                        meta = 22 * lado;
                    }
                    n.desvio = (n.desvio || 0) + (meta - (n.desvio || 0)) * Math.min(1, dt * 6);
                    if (p.dir === 0 || p.dir === 2) p.x += n.desvio; else p.y += n.desvio;
                }
                if (n.clase === 'tranvia') {
                    n.sprite.setPosition(Math.round(p.x), Math.round(p.y)).setDepth(p.y).setFlipX(p.dir === 3);
                } else {
                    const atlas = n.clase !== 'patrulla';
                    n.sprite.setPosition(Math.round(p.x), Math.round(p.y)).setDepth(p.y)
                        .setFrame(cuadro(n.variante, p.dir, pasoAnimado(true, n.semilla), atlas));
                    n.sombra.setPosition(Math.round(p.x), Math.round(p.y) - 3);
                }
            });
            animarSuceso(t, yo);
            return { noche: noche, clima: c };
        }

        // Para pruebas automáticas: cuántas gotas y copos hay en pantalla
        window.__mundoVivoDiagnostico = function () {
            return { intensidad: intensidadLluvia, vueltas: vueltas, climaActual: clima(), lluvia: lluvia.getAliveParticleCount(), nieve: nieve.getAliveParticleCount(), emiteLluvia: lluvia.emitting,
                     visible: lluvia.visible, alfa: lluvia.alpha, x: lluvia.x, y: lluvia.y, sf: lluvia.scrollFactorX,
                     camX: cam.scrollX, camY: cam.scrollY };
        };
        return { actualizar: actualizar, ponerSuceso: ponerSuceso };
    }

    function textoReloj() {
        const h = hora();
        const hh = Math.floor(h), mm = Math.floor((h - hh) * 60);
        return String(hh).padStart(2, '0') + ':' + String(mm).padStart(2, '0');
    }

    return {
        sincronizar: sincronizar, ahora: ahora, hora: hora, clima: clima, nocturnidad: nocturnidad,
        cuadro: cuadro, cuadroGesto: cuadroGesto, pasoAnimado: pasoAnimado, crear: crear, textoReloj: textoReloj
    };
})();
