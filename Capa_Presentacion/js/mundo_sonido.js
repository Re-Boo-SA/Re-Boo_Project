/*
 * SONIDO DEL MUNDO (Fase 1): ambiente y música sintetizados en el navegador con
 * WebAudio. No hay archivos de audio: todo son osciladores y ruido filtrado.
 *
 *  - Ambiente: rumor de ciudad, lluvia y viento según el clima.
 *  - Música: una para el Oeste (jazz suave, Do mayor) y otra para el Este (marcha
 *    lenta, Re menor). Se mezclan según de qué lado del Muro estés.
 *
 * Usa el mismo volumen de música que el resto del sitio (localStorage 'reb_music_volume',
 * de 0 a 1) y se puede silenciar con el botón del HUD ('reb_mundo_sonido').
 * Los navegadores no dejan sonar nada hasta que el jugador toca una tecla o hace clic.
 */
window.SonidoMundo = (function () {
    'use strict';

    let ctx = null, maestro, musicaOeste, musicaEste, ciudad, lluvia, viento, ruido;
    let encendido = localStorage.getItem('reb_mundo_sonido') !== 'no';
    let lado = 0.0;            // 0 = Oeste, 1 = Este
    let siguienteO = 0, siguienteE = 0, pasoO = 0, pasoE = 0;
    let azar = 1961;

    function aleatorio() { azar = (azar * 16807) % 2147483647; return azar / 2147483647; }
    function volumen() {
        const v = Number(localStorage.getItem('reb_music_volume'));
        return localStorage.getItem('reb_music_volume') === null || isNaN(v) ? 0.6 : Math.max(0, Math.min(1, v));
    }
    function nota(semitonosDesdeLa4) { return 440 * Math.pow(2, semitonosDesdeLa4 / 12); }

    function iniciar() {
        if (ctx) { if (ctx.state === 'suspended') ctx.resume(); return; }
        const Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) return;
        ctx = new Ctx();
        maestro = ctx.createGain(); maestro.gain.value = encendido ? volumen() : 0; maestro.connect(ctx.destination);
        musicaOeste = ctx.createGain(); musicaOeste.gain.value = 0; musicaOeste.connect(maestro);
        musicaEste = ctx.createGain(); musicaEste.gain.value = 0; musicaEste.connect(maestro);

        // Ruido blanco en bucle: base de la ciudad, la lluvia y el viento
        const b = ctx.createBuffer(1, ctx.sampleRate * 2, ctx.sampleRate), d = b.getChannelData(0);
        for (let i = 0; i < d.length; i++) d[i] = Math.random() * 2 - 1;
        ruido = b;
        ciudad = capaDeRuido('lowpass', 320, 0.0);
        lluvia = capaDeRuido('highpass', 1800, 0.0);
        viento = capaDeRuido('bandpass', 420, 0.0);
        const lfo = ctx.createOscillator(), prof = ctx.createGain();
        lfo.frequency.value = 0.13; prof.gain.value = 180; lfo.connect(prof); prof.connect(viento.filtro.frequency); lfo.start();

        siguienteO = siguienteE = ctx.currentTime + 0.2;
        setInterval(programar, 100);
    }

    function capaDeRuido(tipo, frecuencia, ganancia) {
        const fuente = ctx.createBufferSource(); fuente.buffer = ruido; fuente.loop = true;
        const filtro = ctx.createBiquadFilter(); filtro.type = tipo; filtro.frequency.value = frecuencia;
        const g = ctx.createGain(); g.gain.value = ganancia;
        fuente.connect(filtro); filtro.connect(g); g.connect(maestro); fuente.start();
        return { filtro: filtro, g: g };
    }

    /* ---------- instrumentos ---------- */

    function piano(destino, t, f, dur, vol) {      // piano eléctrico: dos senos con caída
        [1, 2].forEach(function (mult, i) {
            const o = ctx.createOscillator(), g = ctx.createGain();
            o.type = 'sine'; o.frequency.value = f * mult;
            g.gain.setValueAtTime(0, t); g.gain.linearRampToValueAtTime(vol * (i ? 0.25 : 1), t + 0.01);
            g.gain.exponentialRampToValueAtTime(0.0005, t + dur);
            o.connect(g); g.connect(destino); o.start(t); o.stop(t + dur + 0.05);
        });
    }
    function tono(destino, t, f, dur, vol, tipo, corte) {
        const o = ctx.createOscillator(), g = ctx.createGain(), fl = ctx.createBiquadFilter();
        o.type = tipo; o.frequency.value = f; fl.type = 'lowpass'; fl.frequency.value = corte || 1200;
        g.gain.setValueAtTime(0, t); g.gain.linearRampToValueAtTime(vol, t + 0.02);
        g.gain.setValueAtTime(vol, t + dur * 0.7); g.gain.linearRampToValueAtTime(0, t + dur);
        o.connect(fl); fl.connect(g); g.connect(destino); o.start(t); o.stop(t + dur + 0.05);
    }
    function golpe(destino, t, frecuencia, dur, vol, tipo) {     // platillo o redoblante con ruido
        const f = ctx.createBufferSource(), fl = ctx.createBiquadFilter(), g = ctx.createGain();
        f.buffer = ruido; fl.type = tipo; fl.frequency.value = frecuencia;
        g.gain.setValueAtTime(vol, t); g.gain.exponentialRampToValueAtTime(0.0005, t + dur);
        f.connect(fl); fl.connect(g); g.connect(destino); f.start(t, Math.random()); f.stop(t + dur + 0.05);
    }

    /* ---------- partituras ---------- */

    // Oeste: Cmaj7 - Am7 - Dm7 - G7, 92 bpm con swing. Notas en semitonos desde La4.
    const ACORDES_O = [[-9, -5, -2, 2], [-12, -9, -5, -2], [-7, -4, 0, 3], [-14, -10, -7, -4]];
    const BAJOS_O = [-21, -24, -19, -26];
    const ESCALA_O = [3, 5, 7, 10, 12, 15];
    // Este: Dm - Gm - A7 - Dm, 76 bpm, marcha lenta.
    const ACORDES_E = [[-7, -4, 0], [-14, -11, -7], [-12, -8, -5, -2], [-7, -4, 0]];
    const BAJOS_E = [-19, -26, -24, -19];
    const MELODIA_E = [5, 3, 1, 0, -2, 0, 1, -4];

    function programar() {
        if (!ctx) return;
        const limite = ctx.currentTime + 0.25;
        const negraO = 60 / 92, negraE = 60 / 76;
        while (siguienteO < limite) {                 // corcheas con swing
            const compas = Math.floor(pasoO / 8) % 4, corchea = pasoO % 8;
            const t = siguienteO + (corchea % 2 ? negraO * 0.16 : 0);
            if (corchea === 0) ACORDES_O[compas].forEach(function (s) { piano(musicaOeste, t, nota(s), 2.4, 0.045); });
            if (corchea % 4 === 0) tono(musicaOeste, t, nota(BAJOS_O[compas]), negraO * 1.6, 0.11, 'triangle', 600);
            if (corchea % 2 === 1) golpe(musicaOeste, t, 8000, 0.05, 0.03, 'highpass');
            if (corchea % 2 === 1 && aleatorio() < 0.35) piano(musicaOeste, t, nota(ESCALA_O[Math.floor(aleatorio() * ESCALA_O.length)]), 0.6, 0.035);
            pasoO++; siguienteO += negraO / 2;
        }
        while (siguienteE < limite) {                 // negras, ritmo de marcha
            const compas = Math.floor(pasoE / 4) % 4, negra = pasoE % 4;
            const t = siguienteE;
            if (negra === 0) ACORDES_E[compas].forEach(function (s) { tono(musicaEste, t, nota(s - 12), negraE * 4, 0.025, 'sawtooth', 700); });
            tono(musicaEste, t, nota(BAJOS_E[compas]), negraE * 0.8, 0.10, 'triangle', 500);
            if (negra === 1 || negra === 3) golpe(musicaEste, t, 1800, 0.12, 0.05, 'bandpass');
            tono(musicaEste, t, nota(MELODIA_E[pasoE % 8]), negraE * 0.9, 0.03, 'square', 1400);
            pasoE++; siguienteE += negraE;
        }
    }

    /* ---------- control ---------- */

    /** x: posición del jugador; muroX: dónde está el Muro; clima; noche de 0 a 1. */
    function actualizar(estado) {
        if (!ctx) return;
        const t = ctx.currentTime;
        const meta = estado.x === null ? 0 : Math.max(0, Math.min(1, (estado.x - (estado.muroX - 160)) / 320));
        lado += (meta - lado) * 0.2;
        const musica = 0.55 * (1 - estado.noche * 0.4);
        musicaOeste.gain.setTargetAtTime((1 - lado) * musica, t, 0.8);
        musicaEste.gain.setTargetAtTime(lado * musica, t, 0.8);
        ciudad.g.gain.setTargetAtTime(0.12 * (1 - estado.noche * 0.5), t, 1.5);
        lluvia.g.gain.setTargetAtTime(estado.clima === 'lluvia' ? 0.09 : 0, t, 2);
        viento.g.gain.setTargetAtTime(estado.clima === 'niebla' || estado.clima === 'nieve' ? 0.07 : 0.015, t, 2);
        maestro.gain.setTargetAtTime(encendido ? volumen() : 0, t, 0.3);
    }

    function alternar() {
        encendido = !encendido;
        localStorage.setItem('reb_mundo_sonido', encendido ? 'si' : 'no');
        if (encendido) iniciar();
        if (ctx) maestro.gain.setTargetAtTime(encendido ? volumen() : 0, ctx.currentTime, 0.2);
        return encendido;
    }

    return { iniciar: iniciar, actualizar: actualizar, alternar: alternar, encendido: function () { return encendido; } };
})();
