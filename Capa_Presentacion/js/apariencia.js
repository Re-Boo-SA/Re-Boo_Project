/*
 * Personaje personalizable (mundo abierto, perfil y página «Personalizar»).
 *
 * Código (lo guarda el servidor, ver MundoLogica::PERSONAJE):
 *   p<pelo 0-4>s<sombrero 0-4>a<abrigo 0-2>x<accesorios, bits en hex>-<7 colores hex>
 *   accesorios: 1 anteojos, 2 bigote, 4 barba, 8 bufanda · colores: piel pelo abrigo detalle pantalón sombrero bufanda
 *
 * Las formas están dibujadas con colores «marcadores» (img/mundo/apariencia, ver herramientas/arte/generar_apariencia.py):
 * rojo puro = piel, verde = pelo, azul = abrigo, cian = detalle, amarillo = pantalón, magenta = sombrero, naranja = bufanda.
 * Acá se reemplaza cada marcador (y sus sombras) por el color elegido. El resultado es una hoja de 3 x 6 cuadros de 32 x 48,
 * igual a las de siempre: sirve para caminar, para los gestos y para la primera persona.
 */
window.Apariencia = (function () {
    'use strict';

    const OPCIONES = {
        pelo: ['corto', 'largo', 'rapado', 'peinado', 'rodete'],
        sombrero: ['ninguno', 'gorra', 'sombrero', 'boina', 'panuelo'],
        abrigo: ['sobretodo', 'largo', 'campera'],
        accesorios: ['anteojos', 'bigote', 'barba', 'bufanda'],
    };
    const COLORES = ['piel', 'pelo', 'abrigo', 'detalle', 'pantalon', 'sombrero', 'bufanda'];
    const PALETAS = {
        piel: ['#ffe0c4', '#f1c9a5', '#e0b48a', '#d6a77a', '#a8734f', '#8d5a3b', '#5e3a26'],
        pelo: ['#14110f', '#2b1d14', '#6b4a2b', '#a0522d', '#c9a05a', '#e6cf8f', '#8a8a8a', '#d9d9d9', '#aa281e'],
        abrigo: ['#3a3f4a', '#1f2a40', '#3d4a5c', '#7a5a3a', '#5c3d4a', '#7a1e28', '#2f5a3c', '#c8bea8', '#4a3b2e', '#14161b', '#2a5d8a', '#6b4a7a'],
        detalle: ['#2a2e37', '#172033', '#2e3846', '#5e452c', '#472f39', '#501419', '#1e3c28', '#968c78', '#2b1d14', '#b3262b', '#e9e4cc'],
        pantalon: ['#2b2f3a', '#22252c', '#3a3a3a', '#4a3b2e', '#28283c', '#46413a', '#596070'],
        sombrero: ['#3b3f47', '#4a3b2e', '#14161b', '#7a1e28', '#2a5d8a', '#c8bea8', '#2f5a3c'],
        bufanda: ['#b42828', '#e9e4cc', '#f4c430', '#2a5d8a', '#2f5a3c', '#6b4a7a', '#14161b'],
    };
    const BASE = 'img/mundo/apariencia/';
    const imgs = {};
    let listas = 0, promesa = null;

    function cargar() {
        if (promesa) return promesa;
        promesa = Promise.all(['plantilla'].concat(OPCIONES.accesorios).map(function (n) {
            return new Promise(function (ok) {
                const im = new Image();
                im.onload = function () { imgs[n] = im; listas++; ok(); };
                im.onerror = function () { ok(); };
                im.src = BASE + n + '.png';
            });
        }));
        return promesa;
    }
    function lista() { return listas === 1 + OPCIONES.accesorios.length; }

    function porDefecto(bando) {
        return {
            pelo: 0, sombrero: 0, abrigo: 0, accesorios: { anteojos: false, bigote: false, barba: false, bufanda: false },
            colores: { piel: '#f1c9a5', pelo: '#2b1d14', abrigo: bando === 'este' ? '#7a1e28' : (bando === 'oeste' ? '#1f2a40' : '#3a3f4a'),
                detalle: bando === 'este' ? '#501419' : (bando === 'oeste' ? '#172033' : '#2a2e37'), pantalon: '#2b2f3a', sombrero: '#3b3f47', bufanda: '#b42828' },
        };
    }

    function leer(codigo) {
        const m = /^p([0-4])s([0-4])a([0-2])x([0-9a-f])-([0-9a-f]{42})$/.exec(String(codigo || ''));
        if (!m) return null;
        const bits = parseInt(m[4], 16), colores = {};
        COLORES.forEach(function (c, i) { colores[c] = '#' + m[5].substr(i * 6, 6); });
        const accesorios = {};
        OPCIONES.accesorios.forEach(function (a, i) { accesorios[a] = !!(bits & (1 << i)); });
        return { pelo: +m[1], sombrero: +m[2], abrigo: +m[3], accesorios: accesorios, colores: colores };
    }

    function escribir(o) {
        let bits = 0;
        OPCIONES.accesorios.forEach(function (a, i) { if (o.accesorios[a]) bits |= (1 << i); });
        return 'p' + o.pelo + 's' + o.sombrero + 'a' + o.abrigo + 'x' + bits.toString(16) + '-' + COLORES.map(function (c) { return o.colores[c].slice(1).toLowerCase(); }).join('');
    }

    function rgb(h) { return [parseInt(h.substr(1, 2), 16), parseInt(h.substr(3, 2), 16), parseInt(h.substr(5, 2), 16)]; }

    /** Qué parte es un píxel marcador y con qué sombra (factor 0..1), o null si es de color fijo (ojos, zapatos, camisa). */
    function parte(r, g, b) {
        if (g === 0 && b === 0 && r > 0) return ['piel', r / 255];
        if (r === 0 && b === 0 && g > 0) return ['pelo', g / 255];
        if (r === 0 && g === 0 && b > 0) return ['abrigo', b / 255];
        if (r === 0 && g > 0 && g === b) return ['detalle', g / 255];
        if (b === 0 && r > 0 && r === g) return ['pantalon', r / 255];
        if (g === 0 && r > 0 && r === b) return ['sombrero', r / 255];
        if (b === 0 && r > 0 && Math.abs(g - r / 2) <= 2) return ['bufanda', r / 254];
        return null;
    }

    const cache = new Map();
    /** La hoja de 96 x 288 del personaje, como canvas. */
    function hoja(codigo) {
        if (cache.has(codigo)) return cache.get(codigo);
        const o = leer(codigo);
        if (!o || !lista()) return null;
        const c = document.createElement('canvas'); c.width = 96; c.height = 288;
        const x = c.getContext('2d', { willReadFrequently: true });
        const v = (o.pelo * 5 + o.sombrero) * 3 + o.abrigo;
        x.drawImage(imgs.plantilla, (v % 15) * 96, Math.floor(v / 15) * 288, 96, 288, 0, 0, 96, 288);
        OPCIONES.accesorios.forEach(function (a) { if (o.accesorios[a]) x.drawImage(imgs[a], 0, 0); });
        const datos = x.getImageData(0, 0, 96, 288), px = datos.data, col = {};
        COLORES.forEach(function (k) { col[k] = rgb(o.colores[k]); });
        for (let i = 0; i < px.length; i += 4) {
            if (!px[i + 3]) continue;
            const pa = parte(px[i], px[i + 1], px[i + 2]);
            if (!pa) continue;
            const cc = col[pa[0]];
            px[i] = Math.min(255, Math.round(cc[0] * pa[1])); px[i + 1] = Math.min(255, Math.round(cc[1] * pa[1])); px[i + 2] = Math.min(255, Math.round(cc[2] * pa[1]));
        }
        x.putImageData(datos, 0, 0);
        if (cache.size > 200) cache.delete(cache.keys().next().value);
        cache.set(codigo, c);
        return c;
    }

    /** Textura de Phaser con los 18 cuadros (fila * 3 + paso, igual que MundoVivo.cuadro(…, false)). */
    function textura(escena, codigo) {
        const clave = 'per_' + codigo;
        if (escena.textures.exists(clave)) return clave;
        const c = hoja(codigo);
        if (!c) return null;
        const tex = escena.textures.addCanvas(clave, c);
        for (let i = 0; i < 18; i++) tex.add(i, 0, (i % 3) * 32, Math.floor(i / 3) * 48, 32, 48);
        return clave;
    }

    /** Dibuja un cuadro del personaje en un canvas (avatar, vista previa). cuadro: 0 de frente, 3 izquierda, 6 derecha, 9 de espaldas. */
    function dibujar(destino, codigo, cuadro, recorte) {
        const ctx = destino.getContext('2d');
        ctx.clearRect(0, 0, destino.width, destino.height);
        ctx.imageSmoothingEnabled = false;
        const h = hoja(codigo);
        if (!h) return false;
        const i = cuadro || 0, sx = (i % 3) * 32, sy = Math.floor(i / 3) * 48;
        if (recorte === 'cara') {   // para el avatar redondo: cabeza y hombros
            ctx.drawImage(h, sx + 4, sy, 24, 24, 0, 0, destino.width, destino.height);
        } else {
            const esc = Math.min(destino.width / 32, destino.height / 48);
            ctx.drawImage(h, sx, sy, 32, 48, (destino.width - 32 * esc) / 2, (destino.height - 48 * esc) / 2, 32 * esc, 48 * esc);
        }
        return true;
    }

    function azar() {
        const o = porDefecto(null), elegir = function (l) { return l[Math.floor(Math.random() * l.length)]; };
        o.pelo = Math.floor(Math.random() * 5); o.sombrero = Math.floor(Math.random() * 5); o.abrigo = Math.floor(Math.random() * 3);
        OPCIONES.accesorios.forEach(function (a) { o.accesorios[a] = Math.random() < 0.25; });
        COLORES.forEach(function (c) { o.colores[c] = elegir(PALETAS[c]); });
        return o;
    }

    cargar();
    return { OPCIONES: OPCIONES, COLORES: COLORES, PALETAS: PALETAS, cargar: cargar, lista: lista, porDefecto: porDefecto, leer: leer, escribir: escribir,
        hoja: hoja, textura: textura, dibujar: dibujar, azar: azar };
})();
