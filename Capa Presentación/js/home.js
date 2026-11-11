// Modal Perfil
document.getElementById('btn-abrir-perfil').addEventListener('click', () => document.getElementById('modal-perfil').classList.add('mostrar'));
document.getElementById('btn-cerrar-perfil').addEventListener('click', () => document.getElementById('modal-perfil').classList.remove('mostrar'));
document.getElementById('modal-perfil').addEventListener('click', (e) => { if (e.target === document.getElementById('modal-perfil')) document.getElementById('modal-perfil').classList.remove('mostrar'); });

document.getElementById('btn-cerrar-sesion').addEventListener('click', () => {
    if (confirm('¿Seguro que quieres cerrar sesión pa?')) document.getElementById('form-cerrar-sesion').submit();
});

const modalInfo = document.getElementById('modal-info');
const cerrarModalInfo = () => {
    modalInfo.classList.remove('mostrar');
    modalInfo.setAttribute('aria-hidden', 'true');
    modalInfo.hidden = true;
};
document.querySelector('.btn-info').addEventListener('click', () => {
    modalInfo.hidden = false;
    modalInfo.classList.add('mostrar');
    modalInfo.setAttribute('aria-hidden', 'false');
});
document.getElementById('btn-cerrar-info').addEventListener('click', cerrarModalInfo);
modalInfo.addEventListener('click', event => {
    if (event.target === modalInfo) cerrarModalInfo();
});

document.getElementById('subir-imagen').addEventListener('change', function () { previewFoto(this); });

// Modal Crear Partida
const modalCrearPartida = document.getElementById('modal-crear-partida');
document.getElementById('btn-abrir-crear-partida').addEventListener('click', () => modalCrearPartida.classList.add('mostrar'));
document.getElementById('btn-cerrar-crear-partida').addEventListener('click', () => modalCrearPartida.classList.remove('mostrar'));
modalCrearPartida.addEventListener('click', (e) => { if (e.target === modalCrearPartida) modalCrearPartida.classList.remove('mostrar'); });

const btnTipoPublica = document.getElementById('btn-tipo-publica');
const btnTipoPrivada = document.getElementById('btn-tipo-privada');
const inputTipoPartida = document.getElementById('input-tipo-partida');
const inputPassPartida = document.getElementById('input-pass-partida');
const msjErrorPass = document.getElementById('msj-error-pass');

function seleccionarTipoPartida(tipo) {
    inputTipoPartida.value = tipo;
    btnTipoPublica.classList.toggle('activo', tipo === 'publica');
    btnTipoPrivada.classList.toggle('activo', tipo === 'privada');
    if (tipo === 'publica') {
        inputPassPartida.classList.remove('error-borde');
        msjErrorPass.classList.remove('visible');
    }
}

btnTipoPublica.addEventListener('click', () => seleccionarTipoPartida('publica'));
btnTipoPrivada.addEventListener('click', () => seleccionarTipoPartida('privada'));

inputPassPartida.addEventListener('input', () => {
    if (inputPassPartida.value.trim().length > 0) seleccionarTipoPartida('privada');
    if (inputPassPartida.value.trim().length > 0 || inputTipoPartida.value === 'publica') {
        inputPassPartida.classList.remove('error-borde');
        msjErrorPass.classList.remove('visible');
    }
});

const tarjetasJugadores = document.querySelectorAll('.tarjeta-num-jugadores');
tarjetasJugadores.forEach(tarjeta => {
    tarjeta.addEventListener('click', () => {
        tarjetasJugadores.forEach(t => t.classList.remove('activo'));
        tarjeta.classList.add('activo');
        document.getElementById('input-num-jugadores').value = tarjeta.getAttribute('data-num');
    });
});

document.getElementById('form-crear-partida').addEventListener('submit', (e) => {
    if (inputTipoPartida.value === 'privada' && inputPassPartida.value.trim() === '') {
        e.preventDefault();
        inputPassPartida.classList.add('error-borde');
        msjErrorPass.classList.add('visible');
        inputPassPartida.focus();
    }
});

// Sidebar + Modo claro
const sidebarMenu = document.getElementById('sidebar-menu');
const overlaySidebar = document.getElementById('overlay-sidebar');
const toggleSidebar = () => { sidebarMenu.classList.toggle('activo'); overlaySidebar.classList.toggle('activo'); };

document.getElementById('btn-abrir-sidebar').addEventListener('click', toggleSidebar);
document.getElementById('btn-cerrar-sidebar').addEventListener('click', toggleSidebar);
overlaySidebar.addEventListener('click', toggleSidebar);

const controlesVolumen = document.querySelectorAll('.configuracion-control input[type="range"]');
controlesVolumen.forEach(control => {
    const salida = document.querySelector(`output[for="${control.id}"]`);
    const clave = `config_${control.id}`;
    const guardado = localStorage.getItem(clave);
    if (guardado !== null) control.value = guardado;
    salida.value = `${control.value}%`;
    control.addEventListener('input', () => {
        salida.value = `${control.value}%`;
        localStorage.setItem(clave, control.value);
    });
});

const calidadGuardada = localStorage.getItem('config_calidad') || 'media';
const botonesCalidad = document.querySelectorAll('[data-calidad]');
botonesCalidad.forEach(boton => {
    boton.classList.toggle('activo', boton.dataset.calidad === calidadGuardada);
    boton.addEventListener('click', () => {
        botonesCalidad.forEach(opcion => opcion.classList.toggle('activo', opcion === boton));
        localStorage.setItem('config_calidad', boton.dataset.calidad);
    });
});

const animaciones = document.getElementById('config-animaciones');
animaciones.checked = localStorage.getItem('config_animaciones') !== 'false';
document.body.classList.toggle('sin-animaciones', !animaciones.checked);
animaciones.addEventListener('change', () => {
    document.body.classList.toggle('sin-animaciones', !animaciones.checked);
    localStorage.setItem('config_animaciones', animaciones.checked ? 'true' : 'false');
});

const opcionesEstado = document.querySelectorAll('.opcion-estado');
const indicadorPreview = document.getElementById('indicador-preview');

opcionesEstado.forEach(opcion => {
    opcion.addEventListener('click', () => {
        opcionesEstado.forEach(o => o.classList.remove('activo'));
        opcion.classList.add('activo');
        const radio = opcion.querySelector('input[type="radio"]');
        radio.checked = true;
        indicadorPreview.className = 'indicador-estado ' + radio.value;
    });
});

function previewFoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            let img = document.getElementById('img-preview');
            const svg = document.getElementById('svg-preview');
            if (svg) svg.style.display = 'none';
            if (!img) {
                img = document.createElement('img');
                img.id = 'img-preview';
                img.className = 'imagen-avatar-circulo';
                document.querySelector('.preview-avatar').insertBefore(img, indicadorPreview);
            }
            img.src = e.target.result;
            img.style.display = 'block';
            document.getElementById('lbl-subir').innerText = input.files[0].name;
        }
        reader.readAsDataURL(input.files[0]);
    }
}