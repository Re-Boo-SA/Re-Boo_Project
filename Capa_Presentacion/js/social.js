/*
 * SOCIAL (Fase 4): amigos, solicitudes, invitaciones, grupo con chat, búsqueda,
 * bloqueados y la tarjeta de perfil. Se usa en la página "Amigos" y dentro de Berlín.
 * Todo el texto se arma con textContent (nunca innerHTML con datos de jugadores).
 *
 *   Social.montar(contenedor, { api, csrf, partida, diccionario, alAvisar })
 *   Social.cargar()              -> vuelve a pedir el panel
 *   Social.mostrarPerfil(id)     -> tarjeta de perfil de un jugador
 */
window.Social = (function () {
    'use strict';

    let cfg = null, raiz = null, pestana = 'amigos', datos = null, ultimoMensaje = 0, mensajes = [], temporizador = null;

    function t(texto, valores) {
        let s = (cfg && cfg.diccionario && cfg.diccionario[texto]) || texto;
        if (valores) Object.keys(valores).forEach(function (k) { s = s.split('{' + k + '}').join(valores[k]); });
        return s;
    }
    function nodo(etiqueta, clase, texto) {
        const e = document.createElement(etiqueta);
        if (clase) e.className = clase;
        if (texto !== undefined && texto !== null) e.textContent = texto;
        return e;
    }
    function boton(texto, clase, accion) {
        const b = nodo('button', 'boton-social ' + (clase || ''), texto);
        b.type = 'button';
        b.addEventListener('click', accion);
        return b;
    }
    function avisar(texto, error) { if (cfg.alAvisar) cfg.alAvisar(t(texto), error); }

    async function pedir(params) {
        const r = await fetch(cfg.api + '?' + params, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
        return r.json();
    }
    async function enviar(datosPost) {
        const r = await fetch(cfg.api, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'X-CSRF-Token': cfg.csrf, 'Accept': 'application/json' },
            body: new URLSearchParams(datosPost)
        });
        const j = await r.json();
        if (j.mensaje) avisar(j.mensaje, !j.exito);
        return j;
    }
    async function hacer(datosPost) {
        const r = await enviar(datosPost);
        if (r.exito && r.idPartida) { window.location.href = cfg.partida + '?id=' + r.idPartida; return r; }
        await cargar();
        return r;
    }

    function textoPresencia(a) {
        if (a.estado === 'berlin') return t('En Berlín') + (a.distrito ? ' · ' + a.distrito : '');
        if (a.estado === 'jugando') return t('Jugando una partida');
        if (a.estado === 'esperando') return t('Esperando en una mesa');
        return t('Desconectado');
    }

    /* ---------- pestañas ---------- */

    function dibujar() {
        if (!raiz || !datos) return;
        raiz.textContent = '';
        const tabs = nodo('div', 'pestanas-social');
        const badge = function (n) { return n ? ' (' + n + ')' : ''; };
        [['amigos', t('Amigos') + badge(datos.amigos.length)], ['solicitudes', t('Solicitudes') + badge(datos.recibidas.length)],
         ['invitaciones', t('Invitaciones') + badge(datos.invitaciones.length)], ['grupo', t('Grupo')], ['buscar', t('Buscar')],
         ['bloqueados', t('Bloqueados')]].forEach(function (p) {
            const b = boton(p[1], pestana === p[0] ? 'activa' : '', function () { pestana = p[0]; dibujar(); });
            b.setAttribute('aria-pressed', String(pestana === p[0]));
            tabs.appendChild(b);
        });
        raiz.appendChild(tabs);
        const cuerpo = nodo('div', 'cuerpo-social');
        raiz.appendChild(cuerpo);
        ({ amigos: dibujarAmigos, solicitudes: dibujarSolicitudes, invitaciones: dibujarInvitaciones, grupo: dibujarGrupo,
           buscar: dibujarBuscar, bloqueados: dibujarBloqueados })[pestana](cuerpo);
        clearInterval(temporizador);
        if (pestana === 'grupo' && datos.grupo) temporizador = setInterval(actualizarChat, 2000);
    }

    function lista(cuerpo, items, vacio, fila) {
        const ul = nodo('ul', 'lista-social');
        if (!items.length) ul.appendChild(nodo('li', 'vacio', t(vacio)));
        items.forEach(function (i) { ul.appendChild(fila(i)); });
        cuerpo.appendChild(ul);
    }

    function dibujarAmigos(cuerpo) {
        lista(cuerpo, datos.amigos, 'Todavía no tenés amigos. Buscalos por nombre o tocá a alguien en Berlín.', function (a) {
            const li = nodo('li', 'amigo ' + a.estado);
            const info = nodo('div');
            const nombre = nodo('strong', null, (a.rango ? a.rango + ' ' : '') + a.nombre);
            nombre.addEventListener('click', function () { mostrarPerfil(a.id); });
            info.appendChild(nombre);
            info.appendChild(nodo('small', null, t('Nv {n}', { n: a.nivel || 1 }) + ' · ' + textoPresencia(a)));
            li.appendChild(info);
            const acc = nodo('div', 'acciones-social');
            if (a.estado === 'jugando') acc.appendChild(boton(t('Mirar'), '', function () { window.location.href = cfg.partida + '?id=' + a.idPartida + '&espectar=1'; }));
            acc.appendChild(boton(t('A mi mesa'), '', function () { hacer({ accion: 'invitar_mesa', id: a.id }); }));
            acc.appendChild(boton(t('Al grupo'), '', function () { hacer({ accion: 'invitar_grupo', id: a.id }); }));
            li.appendChild(acc);
            return li;
        });
    }

    function dibujarSolicitudes(cuerpo) {
        cuerpo.appendChild(nodo('h3', null, t('Recibidas')));
        lista(cuerpo, datos.recibidas, 'No tenés solicitudes nuevas.', function (s) {
            const li = nodo('li');
            li.appendChild(nodo('strong', null, s.nombre));
            const acc = nodo('div', 'acciones-social');
            acc.appendChild(boton(t('Aceptar'), 'principal', function () { hacer({ accion: 'responder', id: s.id, aceptar: 1 }); }));
            acc.appendChild(boton(t('Rechazar'), '', function () { hacer({ accion: 'responder', id: s.id, aceptar: 0 }); }));
            li.appendChild(acc);
            return li;
        });
        cuerpo.appendChild(nodo('h3', null, t('Enviadas')));
        lista(cuerpo, datos.enviadas, 'No mandaste solicitudes.', function (s) {
            const li = nodo('li');
            li.appendChild(nodo('strong', null, s.nombre));
            li.appendChild(nodo('small', null, t('Esperando respuesta')));
            return li;
        });
    }

    function dibujarInvitaciones(cuerpo) {
        lista(cuerpo, datos.invitaciones, 'No tenés invitaciones.', function (i) {
            const li = nodo('li');
            const info = nodo('div');
            info.appendChild(nodo('strong', null, i.tipo === 'mesa' ? t('{de} te invita a su mesa', { de: i.de }) : t('{de} te invita a su grupo', { de: i.de })));
            if (i.partida) info.appendChild(nodo('small', null, i.partida));
            li.appendChild(info);
            const acc = nodo('div', 'acciones-social');
            acc.appendChild(boton(t('Aceptar'), 'principal', function () { hacer({ accion: 'aceptar_invitacion', id: i.id }); }));
            acc.appendChild(boton(t('Rechazar'), '', function () { hacer({ accion: 'rechazar_invitacion', id: i.id }); }));
            li.appendChild(acc);
            return li;
        });
    }

    function dibujarGrupo(cuerpo) {
        const g = datos.grupo;
        if (!g) {
            cuerpo.appendChild(nodo('p', 'nota', t('No estás en un grupo. Invitá a alguien desde la lista de amigos o desde su perfil (hasta 4).')));
            return;
        }
        lista(cuerpo, g.miembros, '', function (m) {
            const li = nodo('li', 'amigo ' + m.estado);
            const info = nodo('div');
            info.appendChild(nodo('strong', null, (m.lider ? '★ ' : '') + m.nombre));
            info.appendChild(nodo('small', null, textoPresencia(m)));
            li.appendChild(info);
            if (g.soyLider && !m.lider) li.appendChild(boton(t('Sacar'), '', function () { hacer({ accion: 'expulsar', id: m.id }); }));
            return li;
        });
        cuerpo.appendChild(nodo('p', 'nota', g.soyLider ? t('Si armás una mesa en un café, tu grupo recibe la invitación.') : t('Cuando el líder arme una mesa en un café, te llega la invitación.')));
        const chat = nodo('div', 'chat-grupo');
        const caja = nodo('div', 'mensajes-grupo');
        caja.id = 'mensajes-grupo';
        chat.appendChild(caja);
        const form = nodo('form', 'form-grupo');
        const entrada = nodo('input');
        entrada.maxLength = 200;
        entrada.placeholder = t('Escribile a tu grupo…');
        entrada.setAttribute('aria-label', t('Mensaje al grupo'));
        form.appendChild(entrada);
        form.appendChild(boton(t('Enviar'), 'principal', function () { form.requestSubmit(); }));
        form.addEventListener('submit', async function (ev) {
            ev.preventDefault();
            const texto = entrada.value.trim();
            if (!texto) return;
            entrada.value = '';
            await enviar({ accion: 'decir_grupo', texto: texto });
            actualizarChat();
        });
        chat.appendChild(form);
        cuerpo.appendChild(chat);
        cuerpo.appendChild(boton(t('Salir del grupo'), 'peligro', function () { hacer({ accion: 'salir_grupo' }); }));
        pintarMensajes();
        actualizarChat();
    }

    function pintarMensajes() {
        const caja = document.getElementById('mensajes-grupo');
        if (!caja) return;
        caja.textContent = '';
        mensajes.slice(-30).forEach(function (m) {
            const p = nodo('p');
            p.appendChild(nodo('strong', null, m.de + ': '));
            p.appendChild(document.createTextNode(m.texto));
            caja.appendChild(p);
        });
        caja.scrollTop = caja.scrollHeight;
    }

    async function actualizarChat() {
        try {
            const r = await pedir('accion=grupo&desde=' + ultimoMensaje);
            if (!r.exito) return;
            if (!r.grupo) { clearInterval(temporizador); return; }
            (r.mensajes || []).forEach(function (m) { ultimoMensaje = Math.max(ultimoMensaje, m.id); mensajes.push(m); });
            if (r.mensajes && r.mensajes.length) pintarMensajes();
        } catch (e) { console.error(e); }
    }

    function dibujarBuscar(cuerpo) {
        const entrada = nodo('input', 'buscar-social');
        entrada.placeholder = t('Nombre de usuario (al menos 2 letras)');
        entrada.setAttribute('aria-label', t('Buscar jugador'));
        cuerpo.appendChild(entrada);
        const res = nodo('div');
        cuerpo.appendChild(res);
        let espera = null;
        entrada.addEventListener('input', function () {
            clearTimeout(espera);
            espera = setTimeout(async function () {
                const r = await pedir('accion=buscar&q=' + encodeURIComponent(entrada.value));
                res.textContent = '';
                lista(res, r.jugadores || [], 'Sin resultados.', function (j) {
                    const li = nodo('li');
                    const n = nodo('strong', null, (j.rango ? j.rango + ' ' : '') + j.nombre + ' · ' + t('Nv {n}', { n: j.nivel }));
                    n.addEventListener('click', function () { mostrarPerfil(j.id); });
                    li.appendChild(n);
                    if (j.relacion === 'ninguna') li.appendChild(boton(t('Agregar'), 'principal', function () { hacer({ accion: 'solicitar', id: j.id }); }));
                    else li.appendChild(nodo('small', null, t({ amigos: 'Ya son amigos', enviada: 'Solicitud enviada', recibida: 'Te mandó solicitud' }[j.relacion] || '')));
                    return li;
                });
            }, 300);
        });
        entrada.focus();
    }

    function dibujarBloqueados(cuerpo) {
        lista(cuerpo, datos.bloqueados, 'No bloqueaste a nadie.', function (b) {
            const li = nodo('li');
            li.appendChild(nodo('strong', null, b.nombre));
            li.appendChild(boton(t('Desbloquear'), '', function () { hacer({ accion: 'desbloquear', id: b.id }); }));
            return li;
        });
    }

    /* ---------- tarjeta de perfil ---------- */

    function cerrarPerfil() { const v = document.getElementById('tarjeta-perfil'); if (v) v.remove(); }

    /** Banner, avatar (el personaje) con su marco, nombre con el color de acento, pronombres, estado, «sobre mí» y logros destacados. */
    function cabeceraPersonal(tarjeta, p) {
        const tj = p.tarjeta || {};
        const valido = function (c, d) { return /^#[0-9a-f]{6}$/.test(c || '') ? c : d; };
        const acento = valido(tj.acento, '#f4c430');
        tarjeta.style.setProperty('--acento-perfil', acento);
        const banner = nodo('div', 'banner-perfil');
        banner.style.background = 'linear-gradient(135deg, ' + valido(tj.banner1, '#1b2440') + ', ' + valido(tj.banner2, '#3a2a4a') + ')';
        tarjeta.appendChild(banner);
        const fila = nodo('div', 'cabecera-perfil');
        const avatar = nodo('div', 'avatar-perfil marco-' + (tj.marco || 'ninguno'));
        const lienzo = document.createElement('canvas'); lienzo.width = 72; lienzo.height = 72;
        avatar.appendChild(lienzo);
        if (p.personal && window.Apariencia) Apariencia.cargar().then(function () { Apariencia.dibujar(lienzo, p.personal, 0, 'cara'); });
        fila.appendChild(avatar);
        const nombres = nodo('div', 'nombres-perfil');
        const h = nodo('h2', null, p.nombre); h.style.color = acento;
        nombres.appendChild(h);
        if (tj.pronombres) nombres.appendChild(nodo('small', 'pronombres', tj.pronombres));
        fila.appendChild(nombres);
        tarjeta.appendChild(fila);
        if (tj.estadoEmoji || tj.estadoTexto) tarjeta.appendChild(nodo('p', 'estado-perfil', ((tj.estadoEmoji || '') + ' ' + (tj.estadoTexto || '')).trim()));
        if (tj.sobreMi) {
            tarjeta.appendChild(nodo('h3', null, t('Sobre mí')));
            tarjeta.appendChild(nodo('p', 'sobre-mi', tj.sobreMi));
        }
        if (tj.insignias && tj.insignias.length) {
            const ul = nodo('ul', 'insignias-perfil');
            tj.insignias.forEach(function (l) { const li = nodo('li', null, '★ ' + t(l.nombre)); li.title = t(l.descripcion); ul.appendChild(li); });
            tarjeta.appendChild(ul);
        }
    }

    async function mostrarPerfil(id) {
        const r = await pedir('accion=perfil&id=' + encodeURIComponent(id));
        cerrarPerfil();
        if (!r.exito) { avisar(r.mensaje, true); return; }
        const p = r.perfil;
        const fondo = nodo('div', 'tarjeta-perfil-fondo');
        fondo.id = 'tarjeta-perfil';
        fondo.addEventListener('click', function (ev) { if (ev.target === fondo) cerrarPerfil(); });
        const tarjeta = nodo('section', 'tarjeta-perfil');
        tarjeta.setAttribute('role', 'dialog');
        tarjeta.setAttribute('aria-label', t('Perfil de {nombre}', { nombre: p.nombre }));
        const cerrar = boton('×', 'cerrar', cerrarPerfil);
        cerrar.setAttribute('aria-label', t('Cerrar'));
        tarjeta.appendChild(cerrar);
        cabeceraPersonal(tarjeta, p);
        tarjeta.appendChild(nodo('p', 'subtitulo', p.rango.icono + ' ' + t(p.rango.nombre) + ' · MMR ' + p.mmr + ' · ' + t('Nivel {n}', { n: p.nivel })
            + (p.bando ? ' · ' + t(p.bando === 'oeste' ? 'Bando Oeste' : 'Bando Este') : '')));
        const dl = nodo('dl', 'datos-perfil');
        [[t('Partidas'), p.partidas], [t('Victorias'), p.victorias], [t('Puntos históricos'), p.puntosHistoricos], [t('Torneos ganados'), p.torneosGanados || 0]].forEach(function (d) {
            dl.appendChild(nodo('dt', null, d[0])); dl.appendChild(nodo('dd', null, String(d[1])));
        });
        tarjeta.appendChild(dl);
        if (p.presencia) tarjeta.appendChild(nodo('p', 'nota', textoPresencia(p.presencia)));
        if (p.logros.length) {
            tarjeta.appendChild(nodo('h3', null, t('Logros')));
            const ul = nodo('ul', 'logros-perfil');
            p.logros.forEach(function (l) { const li = nodo('li', null, '★ ' + t(l.nombre)); li.title = t(l.descripcion); ul.appendChild(li); });
            tarjeta.appendChild(ul);
        }
        const acc = nodo('div', 'acciones-social');
        const y = async function (datosPost) { await hacer(datosPost); mostrarPerfil(p.id); };
        if (p.relacion !== 'yo' && !p.esBot) {
            if (p.relacion === 'ninguna') acc.appendChild(boton(t('Agregar amigo'), 'principal', function () { y({ accion: 'solicitar', id: p.id }); }));
            if (p.relacion === 'recibida') acc.appendChild(boton(t('Aceptar solicitud'), 'principal', function () { y({ accion: 'responder', id: p.id, aceptar: 1 }); }));
            if (p.relacion === 'enviada') acc.appendChild(nodo('small', null, t('Solicitud enviada')));
            if (p.relacion === 'amigos') {
                if (p.presencia && p.presencia.estado === 'jugando') acc.appendChild(boton(t('Mirar su partida'), '', function () { window.location.href = cfg.partida + '?id=' + p.presencia.idPartida + '&espectar=1'; }));
                acc.appendChild(boton(t('Invitar a mi mesa'), '', function () { y({ accion: 'invitar_mesa', id: p.id }); }));
            }
            if (p.relacion !== 'bloqueado' && !p.enMiGrupo) acc.appendChild(boton(t('Invitar al grupo'), '', function () { y({ accion: 'invitar_grupo', id: p.id }); }));
            if (p.relacion === 'amigos') acc.appendChild(boton(t('Eliminar amigo'), '', function () { if (window.confirm(t('¿Eliminar a {nombre} de tus amigos?', { nombre: p.nombre }))) y({ accion: 'eliminar', id: p.id }); }));
            if (p.relacion === 'bloqueado') acc.appendChild(boton(t('Desbloquear'), '', function () { y({ accion: 'desbloquear', id: p.id }); }));
            else acc.appendChild(boton(t('Bloquear'), 'peligro', function () {
                if (window.confirm(t('¿Bloquear a {nombre}? No se van a ver ni a escribir.', { nombre: p.nombre }))) { y({ accion: 'bloquear', id: p.id }); cerrarPerfil(); }
            }));
        }
        tarjeta.appendChild(acc);
        fondo.appendChild(tarjeta);
        document.body.appendChild(fondo);
        cerrar.focus();
    }
    document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') cerrarPerfil(); });

    async function cargar() {
        try {
            const r = await pedir('accion=panel');
            if (!r.exito) return;
            datos = r;
            dibujar();
        } catch (e) { console.error(e); }
    }

    function montar(contenedor, opciones) {
        cfg = opciones;
        raiz = contenedor;
        cargar();
    }

    return { montar: montar, cargar: cargar, mostrarPerfil: mostrarPerfil, configurar: function (o) { cfg = o; } };
})();
