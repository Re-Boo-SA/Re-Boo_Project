// Música de fondo
const botonMusica = document.getElementById('btn-toggle-musica');
const textoBotonMusica = document.getElementById('texto-toggle-musica');
const controlVolumen = document.getElementById('volumen-musica');
const estadoAudio = document.getElementById('estado-audio');
const musica = new Audio('Music/ReBoo%20Song.mp3');
musica.loop = true;
musica.preload = 'auto';

let musicaActiva = false;
let traducciones = {};
let textoEstadoActual = '';
const volumenGuardado = localStorage.getItem('reb_music_volume');
let volumenMusica = volumenGuardado === null ? 1 : Number(volumenGuardado);

if (!Number.isFinite(volumenMusica)) {
    volumenMusica = 1;
}
volumenMusica = Math.max(0, Math.min(1, volumenMusica));

musica.volume = volumenMusica;
controlVolumen.value = String(Math.round(volumenMusica * 100));

// Carga los textos en inglés desde el diccionario del proyecto.
if (document.body.dataset.idioma === 'en') {
    fetch('./lang/en.json')
        .then(respuesta => {
            if (!respuesta.ok) {
                throw new Error('No se pudo cargar lang/en.json');
            }
            return respuesta.json();
        })
        .then(diccionario => {
            traducciones = diccionario;
            actualizarBotonMusica();
            actualizarEstadoEfectos();
            controlVolumen.setAttribute('aria-label', traducir('Volumen de la música'));
            if (textoEstadoActual) {
                mostrarEstado(textoEstadoActual);
            }
        })
        .catch(error => console.error('Error al cargar las traducciones del audio:', error));
}

function traducir(texto) {
    return traducciones[texto] || texto;
}

function actualizarBotonMusica() {
    botonMusica.setAttribute('aria-pressed', String(musicaActiva));
    botonMusica.querySelector('[aria-hidden="true"]').textContent = musicaActiva ? '❚❚' : '▶';
    textoBotonMusica.textContent = traducir(musicaActiva ? 'Pausar música' : 'Reproducir música');
}

function mostrarEstado(texto) {
    textoEstadoActual = texto;
    estadoAudio.textContent = traducir(texto);
}

botonMusica.addEventListener('click', async () => {
    try {
        if (musicaActiva) {
            musica.pause();
            musicaActiva = false;
        } else {
            await musica.play();
            musicaActiva = true;
            mostrarEstado('Audio preparado.');
        }
        actualizarBotonMusica();
    } catch (error) {
        console.error('No se pudo reproducir la música:', error);
        musicaActiva = false;
        actualizarBotonMusica();
        mostrarEstado('No se pudo cargar la música de fondo.');
    }
});

controlVolumen.addEventListener('input', () => {
    volumenMusica = Number(controlVolumen.value) / 100;
    musica.volume = volumenMusica;
    localStorage.setItem('reb_music_volume', String(volumenMusica));
});

musica.addEventListener('error', () => {
    musicaActiva = false;
    actualizarBotonMusica();
    mostrarEstado('No se pudo cargar la música de fondo.');
});

// Sonido de los botones
const botonEfectos = document.getElementById('btn-toggle-efectos');
const estadoEfectos = document.getElementById('estado-efectos');
let efectosActivos = localStorage.getItem('reb_button_sounds') !== 'false';
let contextoAudio;

function actualizarEstadoEfectos() {
    botonEfectos.setAttribute('aria-pressed', String(efectosActivos));
    estadoEfectos.textContent = traducir(efectosActivos ? 'Activados' : 'Desactivados');
}

botonEfectos.addEventListener('click', () => {
    efectosActivos = !efectosActivos;
    localStorage.setItem('reb_button_sounds', String(efectosActivos));
    actualizarEstadoEfectos();
});

document.addEventListener('click', evento => {
    if (!(evento.target instanceof Element)) {
        return;
    }

    const boton = evento.target.closest('button, a[href]');
    if (!boton || boton.disabled || !efectosActivos) {
        return;
    }

    try {
        const ConstructorAudio = window.AudioContext || window.webkitAudioContext;
        if (!ConstructorAudio) {
            throw new Error('Este navegador no es compatible con el audio.');
        }

        contextoAudio = contextoAudio || new ConstructorAudio();
        if (contextoAudio.state === 'suspended') {
            contextoAudio.resume().catch(error => console.error('No se pudo activar el sonido:', error));
        }

        const tono = contextoAudio.createOscillator();
        const volumen = contextoAudio.createGain();
        tono.frequency.value = 780;
        volumen.gain.setValueAtTime(0.0001, contextoAudio.currentTime);
        volumen.gain.exponentialRampToValueAtTime(0.4, contextoAudio.currentTime + 0.01);
        volumen.gain.exponentialRampToValueAtTime(0.0001, contextoAudio.currentTime + 0.1);
        tono.connect(volumen);
        volumen.connect(contextoAudio.destination);
        tono.start();
        tono.stop(contextoAudio.currentTime + 0.1);
    } catch (error) {
        console.error('No se pudo reproducir el sonido del botón:', error);
        mostrarEstado('Este navegador no es compatible con el audio.');
    }
});

actualizarBotonMusica();
actualizarEstadoEfectos();
