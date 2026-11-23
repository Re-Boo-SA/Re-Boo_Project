/* Página "Amigos": monta el módulo social (sin scripts en línea, por la política de seguridad). */
(function () {
    'use strict';
    const b = document.body;
    let temporizador = null;
    async function diccionario() {
        const idioma = b.dataset.idioma || 'es';
        if (idioma === 'es') return {};
        try { const r = await fetch('./lang/' + idioma + '.json'); return r.ok ? r.json() : {}; } catch (e) { return {}; }
    }
    diccionario().then(function (dic) {
        Social.montar(document.getElementById('social'), {
            api: b.dataset.apiSocial, csrf: b.dataset.csrf, partida: b.dataset.partida, diccionario: dic,
            alAvisar: function (texto, error) {
                const a = document.getElementById('aviso-social');
                a.textContent = texto; a.classList.toggle('error', !!error); a.hidden = false;
                clearTimeout(temporizador); temporizador = setTimeout(function () { a.hidden = true; }, 3500);
            }
        });
        setInterval(Social.cargar, 15000);   // dónde están los amigos cambia
    });
})();
