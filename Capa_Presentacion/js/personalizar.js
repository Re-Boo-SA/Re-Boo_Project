/* Página «Personalizar»: tu personaje del mundo abierto (formas, accesorios y colores) y tu perfil (banner, acento, estado,
   pronombres, logros destacados y marco). Todo se valida de nuevo en el servidor. */
(function () {
    'use strict';
    const b = document.body;
    const API_MUNDO = b.dataset.apiMundo, API_SOCIAL = b.dataset.apiSocial, CSRF = b.dataset.csrf;
    const $ = function (id) { return document.getElementById(id); };
    let dic = {}, datos = null, personaje = null, perfil = null;
    let vista = 0, saludando = 0, paso = 0;

    function t(texto, v) {
        let s = dic[texto] || texto;
        if (v) Object.keys(v).forEach(function (k) { s = s.split('{' + k + '}').join(v[k]); });
        return s;
    }
    function nodo(etiqueta, clase, texto) {
        const n = document.createElement(etiqueta);
        if (clase) n.className = clase;
        if (texto !== undefined && texto !== null) n.textContent = texto;
        return n;
    }
    function avisar(texto, error) { const a = $('aviso'); a.textContent = t(texto); a.classList.toggle('error', !!error); a.hidden = false; }
    async function pedir(api, params) {
        const r = await fetch(api + '?' + params, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
        return r.json();
    }
    async function enviar(api, d) {
        const r = await fetch(api, { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-Token': CSRF, 'Accept': 'application/json' }, body: new URLSearchParams(d) });
        return r.json();
    }

    /* ---------- controles reutilizables ---------- */
    function opciones(lista, actual, alElegir, nombres) {
        const div = nodo('div', 'opciones');
        lista.forEach(function (v, i) {
            const bt = nodo('button', null, t(nombres[v] || v)); bt.type = 'button';
            bt.setAttribute('aria-pressed', String(i === actual));
            bt.addEventListener('click', function () { alElegir(i); div.querySelectorAll('button').forEach(function (x, j) { x.setAttribute('aria-pressed', String(j === i)); }); });
            div.appendChild(bt);
        });
        return div;
    }
    function muestras(paleta, actual, alElegir) {
        const div = nodo('div', 'muestras');
        const libre = document.createElement('input'); libre.type = 'color'; libre.value = actual; libre.title = t('Otro color');
        const marcar = function (c) { div.querySelectorAll('button').forEach(function (x) { x.setAttribute('aria-pressed', String(x.dataset.c === c)); }); };
        paleta.forEach(function (c) {
            const bt = nodo('button'); bt.type = 'button'; bt.dataset.c = c; bt.style.background = c; bt.setAttribute('aria-label', c);
            bt.addEventListener('click', function () { libre.value = c; marcar(c); alElegir(c); });
            div.appendChild(bt);
        });
        libre.addEventListener('input', function () { marcar(libre.value); alElegir(libre.value.toLowerCase()); });
        div.appendChild(libre); div.appendChild(nodo('small', null, t('o elegí cualquiera')));
        marcar(actual);
        return div;
    }
    function grupo(titulo, hijos) {
        const f = nodo('fieldset'); f.appendChild(nodo('legend', null, t(titulo)));
        hijos.forEach(function (h) { if (typeof h === 'string') f.appendChild(nodo('small', null, t(h))); else f.appendChild(h); });
        return f;
    }

    /* ---------- tu personaje ---------- */
    const NOMBRES = {
        corto: 'Corto', largo: 'Largo', rapado: 'Rapado', peinado: 'Peinado al costado', rodete: 'Rodete',
        ninguno: 'Ninguno', gorra: 'Gorra', sombrero: 'Sombrero', boina: 'Boina', panuelo: 'Pañuelo',
        sobretodo: 'Sobretodo', campera: 'Campera corta', anteojos: 'Anteojos', bigote: 'Bigote', barba: 'Barba', bufanda: 'Bufanda',
    };
    const NOMBRES_ABRIGO = { sobretodo: 'Sobretodo', largo: 'Abrigo largo', campera: 'Campera corta' };
    function armarPersonaje() {
        const c = $('controles-personaje'); c.textContent = '';
        const A = Apariencia, o = personaje, col = function (k) { return muestras(A.PALETAS[k], o.colores[k], function (v) { o.colores[k] = v; }); };
        c.appendChild(grupo('Pelo', [opciones(A.OPCIONES.pelo, o.pelo, function (i) { o.pelo = i; }, NOMBRES), col('pelo')]));
        c.appendChild(grupo('Piel', [col('piel')]));
        c.appendChild(grupo('En la cabeza', [opciones(A.OPCIONES.sombrero, o.sombrero, function (i) { o.sombrero = i; }, NOMBRES), col('sombrero')]));
        c.appendChild(grupo('Ropa', [opciones(A.OPCIONES.abrigo, o.abrigo, function (i) { o.abrigo = i; }, NOMBRES_ABRIGO),
            'Abrigo', col('abrigo'), 'Mangas y detalles', col('detalle'), 'Pantalón', col('pantalon')]));
        const acc = nodo('div', 'opciones');
        A.OPCIONES.accesorios.forEach(function (a) {
            const bt = nodo('button', null, t(NOMBRES[a])); bt.type = 'button'; bt.setAttribute('aria-pressed', String(!!o.accesorios[a]));
            bt.addEventListener('click', function () { o.accesorios[a] = !o.accesorios[a]; bt.setAttribute('aria-pressed', String(o.accesorios[a])); });
            acc.appendChild(bt);
        });
        c.appendChild(grupo('Accesorios', [acc, 'Bufanda', col('bufanda')]));
    }
    // vista previa animada: camina en el lugar mirando hacia donde la giraste, o saluda
    const VISTAS = [0, 6, 9, 3];   // de frente, derecha, de espaldas, izquierda (fila * 3)
    function dibujarVista() {
        if (personaje && Apariencia.lista()) {
            paso = (paso + 1) % 40;
            let cuadro = VISTAS[vista] + [0, 1, 0, 2][Math.floor(paso / 10)];
            if (saludando > 0) { saludando--; cuadro = 12 + (Math.floor(paso / 8) % 2); }
            Apariencia.dibujar($('vista-personaje'), Apariencia.escribir(personaje), cuadro);
        }
        requestAnimationFrame(dibujarVista);
    }

    /* ---------- tu perfil ---------- */
    const EMOJIS = ['🎲', '♟️', '🦕', '🧱', '🕵️', '📻', '🚋', '☕', '📷', '🌧️', '🌙', '🔥', '😴', '🎉', '💬', '🏆'];
    function armarPerfil() {
        const c = $('controles-perfil'); c.textContent = '';
        const p = perfil;
        c.appendChild(grupo('Banner', ['Color de arriba', muestras(['#1b2440', '#3a2a4a', '#14161b', '#7a1e28', '#1f4a3a', '#2a5d8a', '#b07a43', '#4a3b2e'], p.banner1, function (v) { p.banner1 = v; tarjetaPrevia(); }),
            'Color de abajo', muestras(['#3a2a4a', '#1b2440', '#501419', '#f4c430', '#2f5a3c', '#6b4a7a', '#c8bea8', '#14161b'], p.banner2, function (v) { p.banner2 = v; tarjetaPrevia(); })]));
        c.appendChild(grupo('Color de acento', [muestras(['#f4c430', '#e8664a', '#7be38a', '#5fb3f0', '#c77dff', '#ff7aa8', '#e9e4cc'], p.acento, function (v) { p.acento = v; tarjetaPrevia(); })]));
        const est = nodo('div', 'campo');
        const emoji = document.createElement('input'); emoji.type = 'text'; emoji.maxLength = 8; emoji.value = p.estadoEmoji || ''; emoji.placeholder = '🎲'; emoji.setAttribute('aria-label', t('Emoji del estado'));
        const texto = document.createElement('input'); texto.type = 'text'; texto.maxLength = 80; texto.value = p.estadoTexto || ''; texto.placeholder = t('¿En qué andás?');
        emoji.addEventListener('input', function () { p.estadoEmoji = emoji.value; tarjetaPrevia(); });
        texto.addEventListener('input', function () { p.estadoTexto = texto.value; tarjetaPrevia(); });
        const rapidos = nodo('div', 'emojis');
        EMOJIS.forEach(function (e) { const bt = nodo('button', null, e); bt.type = 'button'; bt.addEventListener('click', function () { emoji.value = e; p.estadoEmoji = e; tarjetaPrevia(); }); rapidos.appendChild(bt); });
        est.appendChild(rapidos); est.appendChild(emoji); est.appendChild(texto);
        c.appendChild(grupo('Estado personalizado', [est]));
        const pr = nodo('div', 'campo');
        const pron = document.createElement('input'); pron.type = 'text'; pron.maxLength = 24; pron.value = p.pronombres || ''; pron.placeholder = t('por ejemplo: él/lo, ella/la, elle');
        pron.addEventListener('input', function () { p.pronombres = pron.value; tarjetaPrevia(); });
        pr.appendChild(pron);
        c.appendChild(grupo('Pronombres', [pr, 'El «Sobre mí» es la biografía de tu perfil (se edita desde el inicio).']));
        const ins = nodo('div', 'insignias-elegir');
        if (!datos.logrosGanados.length) ins.appendChild(nodo('small', null, t('Todavía no ganaste logros.')));
        datos.logrosGanados.forEach(function (l) {
            const lab = nodo('label'); const cb = document.createElement('input'); cb.type = 'checkbox'; cb.checked = p.insignias.indexOf(l.id) >= 0;
            cb.addEventListener('change', function () {
                if (cb.checked && p.insignias.length >= 3) { cb.checked = false; avisar('Se pueden destacar hasta 3 logros.', true); return; }
                p.insignias = cb.checked ? p.insignias.concat([l.id]) : p.insignias.filter(function (x) { return x !== l.id; });
                tarjetaPrevia();
            });
            lab.appendChild(cb); lab.appendChild(document.createTextNode('★ ' + t(l.nombre))); ins.appendChild(lab);
        });
        c.appendChild(grupo('Logros destacados (hasta 3)', [ins]));
        const mar = nodo('div', 'marcos');
        datos.marcos.forEach(function (m) {
            const bt = nodo('button'); bt.type = 'button'; bt.disabled = !m.desbloqueado; bt.setAttribute('aria-pressed', String(p.marco === m.id));
            bt.appendChild(nodo('span', 'anillo marco-' + m.id));
            bt.appendChild(nodo('strong', null, t(NOMBRES_MARCO[m.id])));
            bt.appendChild(nodo('small', null, m.desbloqueado ? t('Desbloqueado') : '🔒 ' + t(m.como)));
            bt.addEventListener('click', function () { p.marco = m.id; mar.querySelectorAll('button').forEach(function (x) { x.setAttribute('aria-pressed', String(x === bt)); }); tarjetaPrevia(); });
            mar.appendChild(bt);
        });
        c.appendChild(grupo('Marco del avatar', [mar]));
        tarjetaPrevia();
    }
    const NOMBRES_MARCO = { ninguno: 'Sin marco', bronce: 'Bronce', plata: 'Plata', oro: 'Oro', muro: 'El Muro', nacht: 'Nacht über Berlin', oeste: 'Oeste', este: 'Este' };
    function tarjetaPrevia() {
        const tj = $('tarjeta-previa'), p = perfil; tj.textContent = '';
        tj.style.setProperty('--acento-perfil', p.acento);
        const banner = nodo('div', 'banner-perfil'); banner.style.background = 'linear-gradient(135deg, ' + p.banner1 + ', ' + p.banner2 + ')';
        tj.appendChild(banner);
        const fila = nodo('div', 'cabecera-perfil');
        const av = nodo('div', 'avatar-perfil marco-' + p.marco);
        const lz = document.createElement('canvas'); lz.width = 72; lz.height = 72; av.appendChild(lz);
        Apariencia.cargar().then(function () { Apariencia.dibujar(lz, Apariencia.escribir(personaje), 0, 'cara'); });
        fila.appendChild(av);
        const nom = nodo('div', 'nombres-perfil'); const h = nodo('h2', null, datos.nombre); h.style.color = p.acento; nom.appendChild(h);
        if (p.pronombres) nom.appendChild(nodo('small', 'pronombres', p.pronombres));
        fila.appendChild(nom); tj.appendChild(fila);
        if (p.estadoEmoji || p.estadoTexto) tj.appendChild(nodo('p', 'estado-perfil', ((p.estadoEmoji || '') + ' ' + (p.estadoTexto || '')).trim()));
        if (datos.tarjeta.sobreMi) { tj.appendChild(nodo('h3', null, t('Sobre mí'))); tj.appendChild(nodo('p', 'sobre-mi', datos.tarjeta.sobreMi)); }
        if (p.insignias.length) {
            const ul = nodo('ul', 'insignias-perfil');
            p.insignias.forEach(function (id) { const l = datos.logrosGanados.find(function (x) { return x.id === id; }); if (l) ul.appendChild(nodo('li', null, '★ ' + t(l.nombre))); });
            tj.appendChild(ul);
        }
    }

    /* ---------- pestañas y guardar ---------- */
    function pestana(cual) {
        $('tab-personaje').setAttribute('aria-selected', String(cual === 'personaje'));
        $('tab-perfil').setAttribute('aria-selected', String(cual === 'perfil'));
        $('editor-personaje').hidden = cual !== 'personaje';
        $('editor-perfil').hidden = cual !== 'perfil';
        if (cual === 'perfil') tarjetaPrevia();
        history.replaceState(null, '', '#' + cual);
    }
    async function guardar() {
        $('guardar').disabled = true;
        try {
            const r1 = await enviar(API_MUNDO, { accion: 'personaje', codigo: Apariencia.escribir(personaje) });
            if (!r1.exito) { avisar(r1.mensaje || 'No se pudo guardar.', true); return; }
            const p = perfil;
            const r2 = await enviar(API_SOCIAL, { accion: 'guardar_personalizacion', banner1: p.banner1, banner2: p.banner2, acento: p.acento,
                estadoEmoji: p.estadoEmoji || '', estadoTexto: p.estadoTexto || '', pronombres: p.pronombres || '', insignias: p.insignias.join(','), marco: p.marco });
            if (!r2.exito) { avisar(r2.mensaje || 'No se pudo guardar.', true); return; }
            avisar('Guardado. Así te van a ver en Berlín y en tu perfil.');
        } finally { $('guardar').disabled = false; }
    }

    async function iniciar() {
        if ((b.dataset.idioma || 'es') !== 'es') {
            try { dic = await (await fetch('lang/' + b.dataset.idioma + '.json', { credentials: 'same-origin' })).json(); } catch (e) { dic = {}; }
            document.querySelectorAll('h1, .pestanas-personalizar button, .botones-vista button, #guardar, .vista-tarjeta .nota, .volver').forEach(function (n) { n.textContent = t(n.textContent.trim()); });
        }
        const r = await pedir(API_SOCIAL, 'accion=personalizacion');
        if (!r.exito) { avisar(r.mensaje || 'No se pudo cargar.', true); return; }
        datos = r;
        personaje = Apariencia.leer(r.personal) || Apariencia.porDefecto(r.bando);
        const tj = r.tarjeta;
        perfil = { banner1: tj.banner1, banner2: tj.banner2, acento: tj.acento, estadoEmoji: tj.estadoEmoji, estadoTexto: tj.estadoTexto,
            pronombres: tj.pronombres, insignias: tj.insignias.map(function (x) { return x.id; }), marco: tj.marco };
        await Apariencia.cargar();
        armarPersonaje(); armarPerfil();
        $('tab-personaje').addEventListener('click', function () { pestana('personaje'); });
        $('tab-perfil').addEventListener('click', function () { pestana('perfil'); });
        $('girar').addEventListener('click', function () { vista = (vista + 1) % 4; });
        $('saludar').addEventListener('click', function () { saludando = 90; });
        $('azar').addEventListener('click', function () { personaje = Apariencia.azar(); armarPersonaje(); tarjetaPrevia(); });
        $('guardar').addEventListener('click', guardar);
        pestana(location.hash === '#perfil' ? 'perfil' : 'personaje');
        window.__personalizar = { personaje: function () { return Apariencia.escribir(personaje); }, perfil: function () { return perfil; } };   // pruebas
        requestAnimationFrame(dibujarVista);
    }
    iniciar();
})();
