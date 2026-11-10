const modoOscuro = document.getElementById('config-modo-oscuro');
const botonesModo = document.querySelectorAll('.btn-modo-preferencia');

function aplicarModoClaro(activar) {
    document.body.classList.toggle('modo-claro', activar);
    if (modoOscuro) modoOscuro.checked = !activar;
    botonesModo.forEach(boton => {
        boton.querySelector('.luna').classList.toggle('activo', !activar);
        boton.querySelector('.sol').classList.toggle('activo', activar);
    });
    localStorage.setItem('modo_claro', activar ? 'true' : 'false');
}

aplicarModoClaro(localStorage.getItem('modo_claro') === 'true');

botonesModo.forEach(boton => {
    boton.addEventListener('click', () => {
        aplicarModoClaro(!document.body.classList.contains('modo-claro'));
    });
});

if (modoOscuro) {
    modoOscuro.addEventListener('change', () => aplicarModoClaro(!modoOscuro.checked));
}

window.addEventListener('storage', event => {
    if (event.key === 'modo_claro') {
        aplicarModoClaro(event.newValue === 'true');
    }
});
