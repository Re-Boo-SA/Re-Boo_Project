/*
 * Tutorial (RF06): muestra un paso a la vez, con dos demos interactivas
 * (el dado y la puntuación de la Fábrica). Sin JavaScript se ven todos los pasos seguidos.
 */
(function () {
    'use strict';
    var idioma = document.body.dataset.idioma || 'es';
    function t(es, en, de) { return idioma === 'en' ? en : idioma === 'de' ? de : es; }

    /* ---------- pasos ---------- */
    var pasos = Array.prototype.slice.call(document.querySelectorAll('[data-paso]'));
    var nav = document.getElementById('navegacion-tutorial');
    var progreso = document.getElementById('progreso-tutorial');
    var puntos = document.getElementById('puntos-pasos');
    var btnAnterior = document.getElementById('btn-anterior');
    var btnSiguiente = document.getElementById('btn-siguiente');
    var actual = 0;

    pasos.forEach(function (paso, i) {
        var punto = document.createElement('button');
        punto.type = 'button';
        punto.className = 'punto-paso';
        punto.setAttribute('aria-label', t('Ir al paso ', 'Go to step ', 'Zu Schritt ') + (i + 1));
        punto.addEventListener('click', function () { mostrar(i); });
        puntos.appendChild(punto);
    });

    function mostrar(i) {
        actual = Math.max(0, Math.min(pasos.length - 1, i));
        pasos.forEach(function (paso, j) { paso.hidden = j !== actual; });
        Array.prototype.forEach.call(puntos.children, function (p, j) {
            p.classList.toggle('activo', j === actual);
            if (j === actual) p.setAttribute('aria-current', 'step'); else p.removeAttribute('aria-current');
        });
        btnAnterior.disabled = actual === 0;
        btnSiguiente.hidden = actual === pasos.length - 1;
        document.getElementById('texto-paso').textContent =
            t('Paso ', 'Step ', 'Schritt ') + (actual + 1) + t(' de ', ' of ', ' von ') + pasos.length;
        document.getElementById('relleno-progreso').style.width = ((actual + 1) / pasos.length * 100) + '%';
        var titulo = pasos[actual].querySelector('h1, h2');
        if (titulo) {
            titulo.setAttribute('tabindex', '-1');
            titulo.focus({ preventScroll: true });
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    btnAnterior.addEventListener('click', function () { mostrar(actual - 1); });
    btnSiguiente.addEventListener('click', function () { mostrar(actual + 1); });
    document.addEventListener('keydown', function (ev) {
        var etiqueta = (ev.target.tagName || '').toLowerCase();
        if (etiqueta === 'input' || etiqueta === 'textarea' || etiqueta === 'select') return;
        if (ev.key === 'ArrowRight') mostrar(actual + 1);
        if (ev.key === 'ArrowLeft') mostrar(actual - 1);
    });

    // Deslizar con el dedo en el celular.
    var inicioX = null;
    var inicioY = null;
    document.getElementById('tutorial').addEventListener('touchstart', function (ev) {
        inicioX = ev.touches[0].clientX;
        inicioY = ev.touches[0].clientY;
    }, { passive: true });
    document.getElementById('tutorial').addEventListener('touchend', function (ev) {
        if (inicioX === null) return;
        var dx = ev.changedTouches[0].clientX - inicioX;
        var dy = ev.changedTouches[0].clientY - inicioY;
        inicioX = null;
        if (Math.abs(dx) > 70 && Math.abs(dx) > Math.abs(dy) * 1.5) mostrar(actual + (dx < 0 ? 1 : -1));
    }, { passive: true });

    nav.hidden = false;
    progreso.hidden = false;
    document.body.classList.add('tutorial-activo');
    mostrar(0);

    /* ---------- demo del dado ---------- */
    var BLOQUEOS = {
        lado_izquierdo: ['oeste', 'fabrica', 'espias'],
        lado_derecho: ['politicos', 'este', 'militares'],
        solo_militares: ['oeste', 'fabrica', 'espias', 'politicos', 'este'],
        bloquea_fabrica: ['fabrica'],
        cambiar_ficha: [],
        solo_espias: ['oeste', 'fabrica', 'politicos', 'este', 'militares']
    };
    var caras = Array.prototype.slice.call(document.querySelectorAll('.caras-dado li'));
    var tableroDado = document.getElementById('tablero-dado');
    var btnTirar = document.getElementById('btn-tirar-demo');
    var ultima = null;

    function aplicarCara(li) {
        var codigo = li.dataset.cara;
        caras.forEach(function (c) { c.classList.toggle('elegida', c === li); });
        tableroDado.querySelectorAll('.mini-recinto').forEach(function (r) {
            var bloqueado = BLOQUEOS[codigo].indexOf(r.dataset.recinto) !== -1;
            r.classList.toggle('bloqueado', bloqueado);
            r.classList.toggle('mover', codigo === 'cambiar_ficha' && r.dataset.recinto !== 'muro');
        });
        document.getElementById('img-dado').src = li.dataset.imagen;
        document.getElementById('nombre-cara').textContent = li.querySelector('strong').textContent;
        document.getElementById('efecto-cara').textContent = li.querySelector('strong').nextSibling.textContent;
        ultima = codigo;
    }

    caras.forEach(function (li) {
        li.tabIndex = 0;
        li.setAttribute('role', 'button');
        li.addEventListener('click', function () { aplicarCara(li); });
        li.addEventListener('keydown', function (ev) {
            if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); aplicarCara(li); }
        });
    });
    btnTirar.hidden = false;
    btnTirar.addEventListener('click', function () {
        var opciones = caras.filter(function (c) { return c.dataset.cara !== ultima; });
        var elegida = opciones[Math.floor(Math.random() * opciones.length)];
        tableroDado.classList.remove('girando');
        void tableroDado.offsetWidth;
        tableroDado.classList.add('girando');
        aplicarCara(elegida);
    });

    /* ---------- demo de la Fábrica (1, 3 y 7 por orden de aparición) ---------- */
    var VALORES = [1, 3, 7];
    var lugares = document.getElementById('lugares-fabrica');
    var total = document.getElementById('total-fabrica');
    var fabrica = [];

    function dibujarFabrica() {
        var valorDe = {};
        var distintos = 0;
        fabrica.forEach(function (tipo) {
            if (!(tipo in valorDe)) valorDe[tipo] = VALORES[distintos++];
        });
        var suma = 0;
        Array.prototype.forEach.call(lugares.children, function (li, i) {
            li.textContent = '';
            li.className = '';
            if (i < fabrica.length) {
                var tipo = fabrica[i];
                suma += valorDe[tipo];
                li.className = 'ocupado';
                var nombre = document.createElement('span');
                nombre.textContent = tipo;
                var valor = document.createElement('strong');
                valor.textContent = '+' + valorDe[tipo];
                li.appendChild(nombre);
                li.appendChild(valor);
            } else {
                li.textContent = t('Vacío', 'Empty', 'Leer');
            }
        });
        total.textContent = suma;
        document.querySelectorAll('#botones-fabrica button').forEach(function (b) {
            b.disabled = fabrica.length >= 3;
        });
    }

    document.querySelectorAll('#botones-fabrica button').forEach(function (b) {
        b.addEventListener('click', function () {
            if (fabrica.length < 3) {
                fabrica.push(b.textContent.trim()); // el texto visible ya viene traducido
                dibujarFabrica();
            }
        });
    });
    document.getElementById('btn-vaciar-fabrica').addEventListener('click', function () {
        fabrica = [];
        dibujarFabrica();
    });
})();
