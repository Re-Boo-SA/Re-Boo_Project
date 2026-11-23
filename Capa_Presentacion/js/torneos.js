/* Torneos relámpago: lista, armar, inscribirse y el cuadro (se actualiza solo cada 5 segundos). */
(function () {
    'use strict';
    const b = document.body;
    const API = b.dataset.api, CSRF = b.dataset.csrf, ID = Number(b.dataset.torneo);
    const raiz = document.getElementById('torneos');
    let dic = {}, temporizador = null;

    function t(texto, v) {
        let s = dic[texto] || texto;
        if (v) Object.keys(v).forEach(function (k) { s = s.split('{' + k + '}').join(v[k]); });
        return s;
    }
    function nodo(etiqueta, clase, texto) {
        const e = document.createElement(etiqueta);
        if (clase) e.className = clase;
        if (texto !== undefined && texto !== null) e.textContent = texto;
        return e;
    }
    function boton(texto, clase, accion) {
        const x = nodo('button', 'boton-social ' + (clase || ''), texto);
        x.type = 'button';
        x.addEventListener('click', accion);
        return x;
    }
    function avisar(texto, error) {
        const a = document.getElementById('aviso-torneos');
        a.textContent = t(texto); a.classList.toggle('error', !!error); a.hidden = false;
        clearTimeout(temporizador); temporizador = setTimeout(function () { a.hidden = true; }, 3500);
    }
    async function pedir(params) {
        const r = await fetch(API + (params ? '?' + params : ''), { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
        return r.json();
    }
    async function enviar(datos) {
        const r = await fetch(API, { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-Token': CSRF, 'Accept': 'application/json' }, body: new URLSearchParams(datos) });
        const j = await r.json();
        if (j.mensaje) avisar(j.mensaje, !j.exito);
        if (j.exito && j.idTorneo) { window.location.href = b.dataset.pagina + '?id=' + j.idTorneo; return j; }
        cargar();
        return j;
    }
    function cuando(seg) {
        const m = Math.floor(seg / 60), s = seg % 60;
        return seg <= 0 ? t('empieza en cuanto alguien lo mire') : t('empieza en {m}:{s} o al llenarse', { m: m, s: String(s).padStart(2, '0') });
    }
    const ESTADOS = { inscripcion: 'Inscripción abierta', en_curso: 'En juego', finalizado: 'Terminado', cancelado: 'Cancelado' };

    /* ---------- lista ---------- */

    function dibujarLista(d) {
        raiz.textContent = '';
        if (d.puedoCrear) {
            const f = nodo('form', 'form-torneo');
            f.appendChild(nodo('h2', null, t('Armar un torneo')));
            const nombre = nodo('input'); nombre.maxLength = 30; nombre.placeholder = t('Nombre (por ejemplo: Copa Kreuzberg)'); nombre.required = true;
            nombre.setAttribute('aria-label', t('Nombre del torneo'));
            const tam = nodo('select'); [4, 8].forEach(function (n) { const o = nodo('option', null, t('{n} jugadores', { n: n })); o.value = n; tam.appendChild(o); });
            tam.setAttribute('aria-label', t('Cantidad de jugadores'));
            const min = nodo('select'); [15, 30, 60].forEach(function (n) { const o = nodo('option', null, t('empieza en {n} minutos', { n: n })); o.value = n; min.appendChild(o); });
            min.setAttribute('aria-label', t('Cuándo empieza'));
            [nombre, tam, min].forEach(function (x) { f.appendChild(x); });
            const ir = nodo('button', 'boton-social principal', t('Armar')); ir.type = 'submit';
            f.appendChild(ir);
            f.addEventListener('submit', function (ev) { ev.preventDefault(); enviar({ accion: 'crear', nombre: nombre.value, tamano: tam.value, minutos: min.value }); });
            raiz.appendChild(f);
        }
        const ul = nodo('ul', 'lista-social lista-torneos');
        if (!d.torneos.length) ul.appendChild(nodo('li', 'vacio', t('No hay torneos por ahora. ¡Armá el primero!')));
        d.torneos.forEach(function (x) {
            const li = nodo('li', 'torneo ' + x.estado);
            const info = nodo('div');
            const titulo = nodo('a', null, '🏆 ' + x.nombre); titulo.href = b.dataset.pagina + '?id=' + x.id;
            info.appendChild(titulo);
            const detalle = t(ESTADOS[x.estado]) + ' · ' + t('{n} de {max} jugadores', { n: x.inscriptos, max: x.tamano }) + ' · ' + t('organiza {nombre}', { nombre: x.creador })
                + (x.estado === 'inscripcion' ? ' · ' + cuando(x.segundosParaEmpezar) : '') + (x.ganador ? ' · ' + t('campeón: {nombre}', { nombre: x.ganador }) : '');
            info.appendChild(nodo('small', null, detalle));
            li.appendChild(info);
            const acc = nodo('div', 'acciones-social');
            if (x.estado === 'inscripcion' && !x.inscripto && x.inscriptos < x.tamano) acc.appendChild(boton(t('Inscribirme'), 'principal', function () { enviar({ accion: 'inscribir', id: x.id }); }));
            if (x.estado === 'inscripcion' && x.inscripto) acc.appendChild(boton(t('Bajarme'), '', function () { enviar({ accion: 'salir', id: x.id }); }));
            acc.appendChild(boton(t('Ver cuadro'), '', function () { window.location.href = b.dataset.pagina + '?id=' + x.id; }));
            li.appendChild(acc);
            ul.appendChild(li);
        });
        raiz.appendChild(ul);
    }

    /* ---------- detalle y cuadro ---------- */

    function dibujarDetalle(d) {
        const x = d.torneo;
        document.getElementById('titulo-torneos').textContent = '🏆 ' + x.nombre;
        document.getElementById('nota-torneos').textContent = t(ESTADOS[x.estado]) + ' · ' + t('{n} de {max} jugadores', { n: x.inscriptos, max: x.tamano })
            + ' · ' + t('organiza {nombre}', { nombre: x.creador }) + (x.estado === 'inscripcion' ? ' · ' + cuando(x.segundosParaEmpezar) : '')
            + (x.ganador ? ' · ' + t('campeón: {nombre}', { nombre: x.ganador }) : '');
        const aviso = document.getElementById('aviso-mi-partida');
        aviso.hidden = !d.miPartida;
        if (d.miPartida) aviso.href = b.dataset.partida + '?id=' + d.miPartida;
        raiz.textContent = '';
        const acc = nodo('div', 'acciones-social izquierda');
        if (x.estado === 'inscripcion' && !x.inscripto && x.inscriptos < x.tamano) acc.appendChild(boton(t('Inscribirme'), 'principal', function () { enviar({ accion: 'inscribir', id: x.id }); }));
        if (x.estado === 'inscripcion' && x.inscripto) acc.appendChild(boton(t('Bajarme'), '', function () { enviar({ accion: 'salir', id: x.id }); }));
        raiz.appendChild(acc);
        if (d.rondas.length) {
            const cuadro = nodo('div', 'cuadro');
            const nombresRonda = function (i) {
                const faltan = x.totalRondas - i + 1;   // i empieza en 1
                return faltan === 1 ? t('Final') : faltan === 2 ? t('Semifinales') : faltan === 3 ? t('Cuartos de final') : t('Ronda {n}', { n: i });
            };
            d.rondas.forEach(function (cruces, i) {
                const col = nodo('div', 'ronda');
                col.appendChild(nodo('h3', null, nombresRonda(i + 1)));
                cruces.forEach(function (c) {
                    const caja = nodo('div', 'cruce' + (c.enJuego ? ' en-juego' : ''));
                    [[c.a, c.idA], [c.b, c.idB]].forEach(function (j) {
                        const fila = nodo('div', 'lado' + (c.ganador === null ? '' : c.ganador === j[1] ? ' gano' : ' perdio'), j[0] || t('(pasa sin rival)'));
                        caja.appendChild(fila);
                    });
                    const pie = nodo('div', 'pie-cruce');
                    if (c.enJuego) {
                        const ver = nodo('a', null, t('Mirar')); ver.href = b.dataset.partida + '?id=' + c.idPartida + '&espectar=1';
                        pie.appendChild(ver);
                    } else if (c.resultado) {
                        pie.textContent = t({ partida: 'Se jugó', sin_rival: 'Pasa sin jugar', no_se_presento: 'No se presentó', desempate: 'Pasó por desempate' }[c.resultado]);
                    }
                    caja.appendChild(pie);
                    col.appendChild(caja);
                });
                cuadro.appendChild(col);
            });
            raiz.appendChild(cuadro);
        }
        raiz.appendChild(nodo('h2', null, t('Jugadores')));
        const ul = nodo('ul', 'lista-social');
        d.jugadores.forEach(function (j) {
            const li = nodo('li', j.eliminado ? 'eliminado' : '');
            li.appendChild(nodo('strong', null, (j.semilla ? '#' + j.semilla + ' ' : '') + j.nombre));
            li.appendChild(nodo('small', null, 'MMR ' + j.mmr + (j.eliminado ? ' · ' + t('eliminado') : '')));
            ul.appendChild(li);
        });
        raiz.appendChild(ul);
    }

    async function cargar() {
        try {
            const d = await pedir(ID ? 'id=' + ID : '');
            if (!d.exito) { avisar(d.mensaje, true); return; }
            if (ID) dibujarDetalle(d); else dibujarLista(d);
        } catch (e) { console.error(e); }
    }

    (async function () {
        if ((b.dataset.idioma || 'es') !== 'es') { try { const r = await fetch('./lang/' + b.dataset.idioma + '.json'); if (r.ok) dic = await r.json(); } catch (e) { } }
        cargar();
        setInterval(function () { if (!document.querySelector('.form-torneo input:focus')) cargar(); }, 5000);
    })();
})();
