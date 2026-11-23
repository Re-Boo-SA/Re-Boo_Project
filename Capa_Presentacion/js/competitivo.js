/* Cola competitiva: entra, consulta cada 1,5 s y lleva a la partida cuando la arma. */
(function () {
    'use strict';
    const cuerpo = document.body;
    const API = cuerpo.dataset.api;
    const CSRF = cuerpo.dataset.csrf;
    const URL_PARTIDA = cuerpo.dataset.partida;
    const btnBuscar = document.getElementById('btn-buscar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const panel = document.getElementById('buscando');
    let consultando = null;

    function mostrarBuscando(si) {
        panel.hidden = !si;
        btnBuscar.hidden = si;
        btnCancelar.hidden = !si;
    }

    async function enviar(accion) {
        const respuesta = await fetch(API, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'X-CSRF-Token': CSRF, 'Accept': 'application/json' },
            body: new URLSearchParams({ accion: accion })
        });
        return respuesta.json();
    }

    async function consultar() {
        try {
            const respuesta = await fetch(API, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
            const datos = await respuesta.json();
            if (datos.idPartida) {
                window.location.href = URL_PARTIDA + '?id=' + encodeURIComponent(datos.idPartida);
                return;
            }
            if (!datos.enCola) {
                mostrarBuscando(false);
                clearInterval(consultando);
                return;
            }
            mostrarBuscando(true);
            const s = datos.segundos;
            document.getElementById('dato-tiempo').textContent = 'Buscando rivales… ' + Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
            document.getElementById('dato-cola').textContent = datos.esperando + ' en la cola · margen de ±' + datos.ventana + ' MMR';
        } catch (error) {
            console.error(error);
        }
    }

    btnBuscar.addEventListener('click', async function () {
        btnBuscar.disabled = true;
        const datos = await enviar('entrar');
        btnBuscar.disabled = false;
        if (!datos.exito) {
            if (datos.idPartida) window.location.href = URL_PARTIDA + '?id=' + datos.idPartida;
            else alert(datos.mensaje);
            return;
        }
        mostrarBuscando(true);
        clearInterval(consultando);
        consultando = setInterval(consultar, 1500);
        consultar();
    });

    btnCancelar.addEventListener('click', async function () {
        await enviar('salir');
        clearInterval(consultando);
        mostrarBuscando(false);
    });

    // Si recargó la página estando en la cola, retoma la búsqueda.
    consultar().then(function () {
        if (!panel.hidden) consultando = setInterval(consultar, 1500);
    });
})();
