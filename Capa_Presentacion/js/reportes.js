/* Formulario de reportes: muestra el nombre del jugador solo cuando hace falta y cuenta caracteres. */
(function () {
    'use strict';
    var form = document.getElementById('form-reporte');
    if (!form) return;

    var campoJugador = document.getElementById('campo-jugador');
    var inputJugador = campoJugador.querySelector('input');
    var descripcion = document.getElementById('descripcion');
    var contador = document.getElementById('contador-descripcion');
    var idioma = document.body.dataset.idioma || 'es';
    var ingles = idioma === 'en', aleman = idioma === 'de';

    function actualizarTipo() {
        var tipo = form.querySelector('input[name="tipo"]:checked');
        var esJugador = tipo && tipo.value === 'jugador';
        campoJugador.hidden = !esJugador;
        inputJugador.required = esJugador;
    }

    function actualizarContador() {
        var largo = descripcion.value.length;
        contador.textContent = largo + ' / 1000';
        contador.classList.toggle('corto', largo > 0 && largo < 10);
    }

    form.querySelectorAll('input[name="tipo"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            actualizarTipo();
            if (radio.value === 'jugador' && radio.checked) inputJugador.focus();
        });
    });
    descripcion.addEventListener('input', actualizarContador);

    form.addEventListener('submit', function (ev) {
        var motivo = form.querySelector('input[name="motivo"]').value.trim();
        var texto = descripcion.value.trim();
        var aviso = '';
        if (!campoJugador.hidden && inputJugador.value.trim() === '') {
            aviso = ingles ? 'Write the name of the player you want to report.' : aleman ? 'Schreib den Namen des Spielers, den du melden willst.' : 'Escribí el nombre del jugador que querés denunciar.';
        } else if (motivo.length < 5) {
            aviso = ingles ? 'The subject needs at least 5 characters.' : aleman ? 'Der Betreff braucht mindestens 5 Zeichen.' : 'El asunto tiene que tener al menos 5 caracteres.';
        } else if (texto.length < 10) {
            aviso = ingles ? 'Tell us what happened in at least 10 characters.' : aleman ? 'Erzähl uns in mindestens 10 Zeichen, was passiert ist.' : 'Contanos qué pasó en al menos 10 caracteres.';
        }
        if (aviso !== '') {
            ev.preventDefault();
            var previo = form.querySelector('.mensaje-error');
            if (!previo) {
                previo = document.createElement('p');
                previo.className = 'mensaje mensaje-error';
                previo.setAttribute('role', 'alert');
                form.prepend(previo);
            }
            previo.textContent = aviso;
            previo.scrollIntoView({ block: 'center' });
        }
    });

    actualizarTipo();
    actualizarContador();
})();
