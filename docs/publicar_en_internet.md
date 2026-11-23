# Abrir el juego a internet (para jugar con gente de otras casas y ciudades)

Todo en **cmd**. Cambiá `C:\Users\hojas\Re-Boo\Re-Boo_Project-main` por tu carpeta.

## Qué opción elegir

| Opción | Para qué sirve | Contras |
|---|---|---|
| **A. Túnel de Cloudflare** (la de esta guía) | Probar con amigos desde tu PC. Sin tocar el router, con HTTPS, gratis y sin cuenta. | La dirección cambia cada vez que lo arrancás. Funciona solo mientras tu PC, el servidor y el túnel estén encendidos. |
| **B. Servidor en la nube (VPS) con dominio propio** | Lo permanente: siempre encendido, dirección fija. | Más trabajo: Apache, PHP, MySQL y HTTPS (Let's Encrypt) en un servidor Linux. |
| **C. Abrir puertos del router** | — | No lo recomiendo: muchos proveedores no dan IP pública, expone tu casa y sin HTTPS las contraseñas viajan en claro. |

## Parte 1 · Antes de abrir nada (obligatorio)

**1. Cuentas de prueba.** AURA, pepe, testeo, juan_admin y admin_prueba usan la misma contraseña, que está escrita en el SQL del proyecto. Dos de ellas son administradoras. Cambiá la clave de las que uses y desactivá las demás:

    cd C:\Users\hojas\Re-Boo\Re-Boo_Project-main
    C:\xampp\php\php.exe herramientas\cambiar_clave.php juan_admin "UnaClaveLargaYUnica_2026"
    C:\xampp\php\php.exe herramientas\cambiar_clave.php admin_prueba --desactivar
    C:\xampp\php\php.exe herramientas\cambiar_clave.php pepe --desactivar

La clave tiene que tener al menos 12 caracteres y no puede ser una de las del proyecto. Los bots (Klaus, Greta, etc.) no pueden iniciar sesión: no hay nada que hacer con ellos.

**2. Clave de la base.** El usuario `reboo_app` también tiene una clave escrita en el SQL. Cambiala (te pide la de root):

    "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" -u root -p -e "ALTER USER 'reboo_app'@'localhost' IDENTIFIED BY 'OtraClaveLargaParaLaBase_2026';"

Y poné la misma en `config.php`, en la línea `BD_CONTRA`. **El puerto 3306 de MySQL no se abre nunca.**

**3. Modo de `config.php`.** Hay dos caminos, y conviene saber qué implica cada uno:
- **`APP_MODO = 'desarrollo'`**: se saltea el reCAPTCHA, así que cualquiera con el link puede registrar cuentas en masa, y los errores se muestran en pantalla. Sirve solo para una prueba corta con un link que compartís en privado, junto con el filtro de correos de la Parte 3.
- **`APP_MODO = 'produccion'`**: es lo correcto para algo que dure, pero **exige reCAPTCHA v3** (claves gratuitas de Google, `RECAPTCHA_CLAVE_SITIO` y `RECAPTCHA_CLAVE_SECRETA` en `config.php`). Sin las claves nadie puede registrarse. Las claves se atan a un dominio, y con el túnel rápido el dominio cambia en cada arranque; por eso en producción conviene una dirección fija (túnel con nombre y dominio propio, o un servidor en la nube).

## Parte 2 · Servidor Apache (varios jugadores a la vez)

`php -S` atiende de a un pedido por vez en Windows, y el mundo consulta al servidor todo el tiempo: con varias personas se traba. Para jugar en grupo usá el Apache de XAMPP. (Probé esta configuración en Apache sobre Linux; en XAMPP para Windows no la probé.)

1. En `C:\xampp\apache\conf\httpd.conf` buscá la línea `#Include conf/extra/httpd-vhosts.conf` y sacale el `#` (si ya no lo tiene, está bien).
2. Al final de `C:\xampp\apache\conf\extra\httpd-vhosts.conf` agregá:

        Listen 8081
        <VirtualHost *:8081>
            DocumentRoot "C:/Users/hojas/Re-Boo/Re-Boo_Project-main"
            <Directory "C:/Users/hojas/Re-Boo/Re-Boo_Project-main">
                AllowOverride All
                Require all granted
            </Directory>
        </VirtualHost>

3. En `config.php` poné `define('BD_PERSISTENTE', '1');` (reutiliza la conexión a MySQL).
4. En el panel de XAMPP, **Start** en Apache (y en MySQL si no está andando).
5. Probalo primero en tu PC: `http://127.0.0.1:8081/DraftDerMauer/`. Tiene que cargar el inicio del juego.

## Parte 3 · El túnel

1. Instalá el programa del túnel (una sola vez) y **cerrá y volvé a abrir cmd**:

        winget install --id Cloudflare.cloudflared

2. Con Apache andando, en una ventana de cmd:

        cloudflared tunnel --url http://localhost:8081

3. Te muestra una dirección `https://algo-al-azar.trycloudflare.com`. Tus amigos entran a **esa dirección + `/DraftDerMauer/`** y se registran desde el navegador. No instalan nada.
4. **Dejá esa ventana abierta**: si la cerrás (o la PC se duerme), el link deja de funcionar y la próxima vez tendrás una dirección distinta.

**Para que solo entren los invitados** (recomendado en modo `desarrollo`), agregá sus correos. Cada visitante recibe un código por mail antes de ver el sitio:

    cloudflared tunnel --url http://localhost:8081 --allowed-mail amigo1@correo.com --allowed-mail amigo2@correo.com

(No probé esta opción con el juego; está en la documentación oficial de Cloudflare.)

## Lo que tenés que saber

- **Cookies sin la marca `Secure`.** El juego solo la activa si el pedido llega a Apache por HTTPS. Con el túnel, Apache recibe el pedido por HTTP local, aunque tu amigo entre por HTTPS. Las contraseñas no viajan en claro (el tramo hasta Cloudflare es HTTPS), pero la cookie de sesión es menos estricta de lo ideal. Se arregla con un cambio chico en el código; para una prueba entre amigos es tolerable.
- **Tu PC es el servidor.** Las cuentas de tus amigos (con sus correos) viven en tu base. Hacé respaldos con `mysqldump` (ver `docs/actualizar_version.md`, paso 2) y no compartas el link en lugares públicos.
- **Rendimiento.** Depende de tu PC y de la velocidad de *subida* de tu internet. Con pocos jugadores alcanza; para más, un servidor en la nube.
- **Para dejarlo fijo** (siempre encendido, con dirección propia): servidor en la nube o túnel con nombre sobre un dominio tuyo, `APP_MODO = 'produccion'` y claves de reCAPTCHA.
