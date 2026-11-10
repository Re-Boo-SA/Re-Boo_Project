
console.log('%c¡Pará!', 'color: #ff0565; font-size: 40px; font-weight: bold;');
console.log('%cEsta consola es para sigmas. Si alguien te dijo que pegues algo acá para conseguir fichas, skins, mujeres o entrar a otra cuenta, es una estafa: le estarías dando el control de tu cuenta.', 'font-size: 16px;');

document.querySelectorAll('select[data-autoenviar]').forEach(function (select) {
    select.addEventListener('change', function () {
        select.form.submit();
    });
});

document.addEventListener('DOMContentLoaded', function () {
    if (document.body.dataset.idioma === 'en' && typeof aplicarTraduccionIngles === 'function') {
        aplicarTraduccionIngles();
    }
});
