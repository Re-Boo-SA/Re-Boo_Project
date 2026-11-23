/*
 * ======================================================================
 *  BERLÍN EN PRIMERA PERSONA
 *  Otra forma de DIBUJAR el mismo mundo: la posición, las colisiones, el envío al
 *  servidor, los NPCs y los demás jugadores son los de siempre (mundo.js y mundo_vivo.js).
 *  Técnica: raycasting sobre la grilla del mapa (como los juegos de los 90):
 *   - paredes: edificios, el Muro y la caseta del control (con textura y altura propias);
 *   - figuras de frente: personas, NPCs, tranvía, puestos y la decoración alta
 *     (árboles, faroles, torres, barreras), con los mismos dibujos de la vista normal;
 *   - cielo y piso según el día, la noche y el clima; niebla por distancia.
 *  Se dibuja en un canvas encima del de Phaser, a baja resolución y agrandado (pixel art).
 * ======================================================================
 */
window.MundoPrimera = (function () {
    'use strict';
    const FOV = Math.PI / 180 * 66, OJOS = 30;   // altura de los ojos en píxeles del mundo
    const DECO_ALTA = { 15: 'arbol', 16: 'farol', 17: 'barrera', 18: 'valla', 19: 'torre' };   // cuadros del tileset (gid - 1)
    const COLORES = {   // color base de la pared según el tipo de edificio
        cafe: '#7c3a2c', tienda: '#7a6a52', ranking: '#c9bf9f', competitivo: '#4f5b4b', torneos: '#8f959c', museo: '#3f5466', tablero: '#6b4a3a',
    };

    function crear(op) {
        const MAPA = op.MAPA, T = MAPA.tile, COLS = MAPA.ancho / T, FILAS = MAPA.alto / T;
        const canvas = document.createElement('canvas');
        canvas.className = 'vista-primera';
        canvas.hidden = true;
        op.contenedor.appendChild(canvas);
        const ctx = canvas.getContext('2d');
        let activo = false, ang = 0, W = 0, H = 0, zbuf = null;

        /* ---------- qué hay en cada celda ---------- */
        const suelo = MAPA.tiled.layers.find(function (l) { return l.name === 'suelo'; }).data;
        const pared = new Array(COLS * FILAS).fill(null);   // null = se ve a través (aunque no se pueda pasar)
        for (let i = 0; i < pared.length; i++) {
            if (!op.COLISION[i]) continue;
            if (suelo[i] === 11) pared[i] = { tipo: 'muro', alto: 58 };
            else if (suelo[i] === 13) pared[i] = { tipo: 'caseta', alto: 46 };
        }
        MAPA.edificios.forEach(function (b) {
            const alto = b.tipo === 'torneos' ? 260 : (b.tipo === 'ranking' ? 150 : 110);
            for (let f = Math.floor(b.y / T); f < Math.ceil((b.y + b.h) / T); f++) {
                for (let c = Math.floor(b.x / T); c < Math.ceil((b.x + b.w) / T); c++) {
                    if (c >= 0 && f >= 0 && c < COLS && f < FILAS && op.COLISION[f * COLS + c]) pared[f * COLS + c] = { tipo: 'edificio', alto: alto, edificio: b };
                }
            }
        });

        /* ---------- texturas (32 x 32, hechas por código) ---------- */
        function textura(dibujar) {
            const c = document.createElement('canvas'); c.width = 32; c.height = 32;
            dibujar(c.getContext('2d')); return c;
        }
        function variar(hex, k) {
            const n = parseInt(hex.slice(1), 16), f = function (v) { return Math.max(0, Math.min(255, Math.round(v * k))); };
            return 'rgb(' + f(n >> 16) + ',' + f((n >> 8) & 255) + ',' + f(n & 255) + ')';
        }
        const texturas = {};
        function texturaEdificio(b) {
            const clave = b.id;
            if (texturas[clave]) return texturas[clave];
            const base = COLORES[b.tipo] || (b.x + b.w / 2 < 29 * T ? '#6d6a66' : '#77766d');
            return (texturas[clave] = {
                pared: textura(function (g) {
                    g.fillStyle = base; g.fillRect(0, 0, 32, 32);
                    if (b.tipo === 'cafe') { g.fillStyle = variar(base, 0.75); for (let y = 0; y < 32; y += 6) g.fillRect(0, y, 32, 1); }
                    g.fillStyle = '#1b2440'; g.fillRect(6, 6, 8, 10); g.fillRect(18, 6, 8, 10);   // ventanas
                    g.fillStyle = variar(base, 1.25); g.fillRect(6, 16, 8, 2); g.fillRect(18, 16, 8, 2);
                }),
                ventanaLuz: textura(function (g) {
                    g.fillStyle = base; g.fillRect(0, 0, 32, 32);
                    g.fillStyle = '#f4d878'; g.fillRect(6, 6, 8, 10); g.fillRect(18, 6, 8, 10);
                }),
                puerta: textura(function (g) {
                    g.fillStyle = base; g.fillRect(0, 0, 32, 32);
                    g.fillStyle = '#3a2818'; g.fillRect(9, 8, 14, 24); g.fillStyle = '#c9a05a'; g.fillRect(20, 20, 2, 2);
                    if (b.tipo === 'cafe') { g.fillStyle = '#b3262b'; g.fillRect(4, 3, 24, 4); g.fillStyle = '#e9e4cc'; for (let x = 4; x < 28; x += 4) g.fillRect(x, 3, 2, 4); }
                }),
            });
        }
        const texMuro = textura(function (g) {
            g.fillStyle = '#b9b7ae'; g.fillRect(0, 0, 32, 32);
            g.fillStyle = '#9a988f'; g.fillRect(0, 0, 1, 32); g.fillRect(16, 0, 1, 32);
            g.fillStyle = '#d6d4cb'; g.fillRect(0, 0, 32, 3);   // el caño de arriba
        });
        const texCaseta = textura(function (g) { g.fillStyle = '#e9e4cc'; g.fillRect(0, 0, 32, 32); g.fillStyle = '#4d6272'; g.fillRect(6, 6, 20, 10); g.fillStyle = '#3b3f47'; g.fillRect(0, 0, 32, 3); });

        /* ---------- tamaño ---------- */
        function ajustar() {
            const ancho = op.contenedor.clientWidth || window.innerWidth, alto = op.contenedor.clientHeight || window.innerHeight;
            W = Math.max(240, Math.min(480, Math.round(ancho / 2.5)));
            H = Math.round(W * alto / ancho);
            canvas.width = W; canvas.height = H; zbuf = new Float32Array(W);
        }
        window.addEventListener('resize', function () { if (activo) ajustar(); });

        /* ---------- mirar con el mouse o el dedo ---------- */
        let arrastre = null;
        canvas.addEventListener('pointerdown', function (ev) { arrastre = ev.clientX; canvas.setPointerCapture(ev.pointerId); });
        canvas.addEventListener('pointermove', function (ev) { if (arrastre !== null) { ang += (ev.clientX - arrastre) * 0.006; arrastre = ev.clientX; } });
        canvas.addEventListener('pointerup', function () { arrastre = null; });
        canvas.addEventListener('pointercancel', function () { arrastre = null; });

        /* ---------- dibujo ---------- */
        function mezclar(c1, c2, f) {
            const a = parseInt(c1.slice(1), 16), b = parseInt(c2.slice(1), 16), m = function (s) { return Math.round(((a >> s) & 255) * (1 - f) + ((b >> s) & 255) * f); };
            return 'rgb(' + m(16) + ',' + m(8) + ',' + m(0) + ')';
        }
        let msTotal = 0, cuadros = 0;
        function dibujar(e) {
            if (!activo || e.yo.x === null) return;
            const t0 = performance.now();
            dibujarCuadro(e);
            msTotal += performance.now() - t0; cuadros++;
        }
        function dibujarCuadro(e) {
            const px = e.yo.x, py = e.yo.y - 6, noche = e.noche || 0, clima = e.clima;
            const niebla = clima === 'niebla' ? 260 : (clima === 'lluvia' || clima === 'nieve' ? 520 : 900);
            const proj = (W / 2) / Math.tan(FOV / 2), horizonte = Math.round(H * 0.52);
            // cielo y piso
            const cielo = ctx.createLinearGradient(0, 0, 0, horizonte);
            const arriba = clima === 'niebla' ? '#9aa1a8' : (clima === 'despejado' ? '#6f9fd6' : '#8a94a3');
            cielo.addColorStop(0, mezclar(arriba, '#050816', noche)); cielo.addColorStop(1, mezclar('#c9d3dc', '#141a2c', noche));
            ctx.fillStyle = cielo; ctx.fillRect(0, 0, W, horizonte);
            const piso = ctx.createLinearGradient(0, horizonte, 0, H);
            piso.addColorStop(0, mezclar('#7d7c78', '#15161c', noche)); piso.addColorStop(1, mezclar('#5b5a57', '#0c0d12', noche));
            ctx.fillStyle = piso; ctx.fillRect(0, horizonte, W, H - horizonte);
            // La noche oscurece sobre todo lo lejano (de cerca se ve, como en una calle con faroles)
            const oscuro = function (dist) { return Math.min(0.9, noche * 0.4 + dist / niebla * (0.5 + noche * 0.35)); };
            // paredes (una columna por rayo, búsqueda por la grilla)
            for (let x = 0; x < W; x++) {
                const a = ang + Math.atan((x - W / 2) / proj);
                const dx = Math.cos(a), dy = Math.sin(a);
                let c = Math.floor(px / T), f = Math.floor(py / T);
                const pasoC = dx > 0 ? 1 : -1, pasoF = dy > 0 ? 1 : -1;
                const dDx = Math.abs(T / (dx || 1e-9)), dDy = Math.abs(T / (dy || 1e-9));
                let sx = dx > 0 ? ((c + 1) * T - px) / dx : (px - c * T) / -dx, sy = dy > 0 ? ((f + 1) * T - py) / dy : (py - f * T) / -dy;
                if (!isFinite(sx)) sx = 1e9; if (!isFinite(sy)) sy = 1e9;
                let lado = 0, celda = null, d = 0;
                for (let n = 0; n < 64; n++) {
                    if (sx < sy) { d = sx; sx += dDx; c += pasoC; lado = 0; } else { d = sy; sy += dDy; f += pasoF; lado = 1; }
                    if (c < 0 || f < 0 || c >= COLS || f >= FILAS || d > 1400) { celda = null; break; }
                    celda = pared[f * COLS + c]; if (celda) break;
                }
                const perp = d * Math.cos(a - ang);
                zbuf[x] = celda ? perp : 1e9;
                if (!celda || perp <= 0) continue;
                const hit = lado === 0 ? py + d * dy : px + d * dx;
                const u = ((hit % T) + T) % T / T;
                const abajo = horizonte + OJOS / perp * proj, alto = celda.alto / perp * proj;
                let tex, rep = Math.max(1, Math.round(celda.alto / 40));
                if (celda.tipo === 'muro') { tex = texMuro; rep = 1; }
                else if (celda.tipo === 'caseta') { tex = texCaseta; rep = 1; }
                else {
                    const b = celda.edificio, t = texturaEdificio(b);
                    const puerta = Math.floor(b.puertaX / T) === c && Math.abs(Math.floor((b.puertaY - 20) / T) - f) <= 1;
                    tex = puerta ? t.puerta : (noche > 0.5 && (c + f) % 3 === 0 ? t.ventanaLuz : t.pared);
                }
                const tx = Math.min(31, Math.floor(u * 32));
                // La textura se repite hacia arriba (pisos del edificio); la puerta va solo en la planta baja
                const tramo = alto / rep;
                for (let k = 0; k < rep; k++) {
                    const tt = (k > 0 && celda.edificio) ? texturaEdificio(celda.edificio).pared : tex;
                    ctx.drawImage(tt, tx, 0, 1, 32, x, abajo - tramo * (k + 1), 1, tramo + 1);
                }
                ctx.fillStyle = 'rgba(5,8,22,' + (oscuro(perp) + (lado ? 0.12 : 0)).toFixed(3) + ')';
                ctx.fillRect(x, abajo - alto, 1, alto + 1);
            }
            // figuras (de la más lejana a la más cercana), recortadas contra las paredes
            const figuras = [];
            e.escena.children.list.forEach(function (o) {
                if (!o.visible || !o.frame || o === e.mio || o.scrollFactorX !== 1) return;
                const clave = o.texture.key;
                let alto = o.displayHeight, ancho = o.displayWidth, bx, by;
                if (clave === 'tiles') {
                    const tipo = DECO_ALTA[o.frame.name]; if (!tipo) return;
                    bx = o.x + T / 2; by = o.y + T; alto = tipo === 'torre' ? 90 : (tipo === 'arbol' ? 64 : (tipo === 'farol' ? 56 : 30)); ancho = tipo === 'torre' ? 40 : 36;
                } else if (/^(pj_|npc_|tranvia|suceso_)/.test(clave)) {
                    const pie = o.getBottomCenter(); bx = pie.x; by = pie.y;
                } else return;
                const vx = bx - px, vy = by - py, dist = Math.hypot(vx, vy);
                if (dist < 8 || dist > niebla * 1.3) return;
                let rel = Math.atan2(vy, vx) - ang; while (rel > Math.PI) rel -= 2 * Math.PI; while (rel < -Math.PI) rel += 2 * Math.PI;
                if (Math.abs(rel) > FOV / 2 + 0.3) return;
                figuras.push({ o: o, dist: dist * Math.cos(rel), rel: rel, alto: alto, ancho: ancho, nombre: o.getData && o.getData('jugador') });
            });
            figuras.sort(function (a, b) { return b.dist - a.dist; });
            figuras.forEach(function (s) {
                if (s.dist <= 4) return;
                const fr = s.o.frame, img = fr.source.image;
                const cx = W / 2 + Math.tan(s.rel) * proj, escala = proj / s.dist;
                const hPx = s.alto * escala, wPx = s.ancho * escala, abajo = horizonte + OJOS * escala, izq = Math.round(cx - wPx / 2);
                for (let x = Math.max(0, izq); x < Math.min(W, izq + wPx); x++) {
                    if (zbuf[x] < s.dist) continue;
                    const sx = fr.cutX + Math.floor((x - izq) / wPx * fr.cutWidth);
                    ctx.drawImage(img, s.o.flipX ? fr.cutX + fr.cutWidth - 1 - (sx - fr.cutX) : sx, fr.cutY + 1, 1, fr.cutHeight - 1, x, abajo - hPx + hPx / fr.cutHeight, 1, hPx - hPx / fr.cutHeight);
                }
                const sombra = oscuro(s.dist);
                if (sombra > 0.05) { ctx.fillStyle = 'rgba(5,8,22,' + (sombra * 0.8).toFixed(3) + ')'; }
            });
            // lluvia o nieve
            if (clima === 'lluvia' || clima === 'nieve') {
                ctx.strokeStyle = clima === 'nieve' ? 'rgba(255,255,255,0.7)' : 'rgba(190,210,255,0.35)';
                ctx.beginPath();
                const t = performance.now() / (clima === 'nieve' ? 40 : 6);
                for (let i = 0; i < 70; i++) {
                    const x = (i * 97 + t * (clima === 'nieve' ? 0.3 : 1.2)) % W, y = (i * 53 + t) % H;
                    ctx.moveTo(x, y); ctx.lineTo(x - (clima === 'nieve' ? 0 : 2), y + (clima === 'nieve' ? 1 : 7));
                }
                ctx.stroke();
            }
        }

        function activar(si, dir) {
            activo = !!si;
            canvas.hidden = !activo;
            if (activo) { ajustar(); ang = [-Math.PI / 2, 0, Math.PI / 2, Math.PI][dir || 0]; }
        }
        window.__primeraMirar = function (a) { ang = a; };   // pruebas: es lo mismo que girar arrastrando el mouse
        window.__primeraDiag = function () {   // diagnóstico (solo lectura)
            const cuenta = {}; pared.forEach(function (p) { if (p) cuenta[p.tipo] = (cuenta[p.tipo] || 0) + 1; });
            return { msPorCuadro: cuadros ? +(msTotal / cuadros).toFixed(2) : null, paredes: cuenta, solidas: op.COLISION.filter(function (v) { return v; }).length, edificios: MAPA.edificios.length, ejemplo: MAPA.edificios[0], W: W, ang: ang };
        };
        return {
            activar: activar, dibujar: dibujar,
            activo: function () { return activo; },
            angulo: function () { return ang; },
            girar: function (d) { ang += d; },
        };
    }
    return { crear: crear };
})();
