<?php
/*
 * ======================================================================
 *  CONFIGURACIÓN DE RE-BOO (plantilla)
 *
 *  1. Copiá este archivo como "config.php" en la misma carpeta, o corré:
 *        php herramientas/instalar.php
 *     que lo crea solo con una pimienta aleatoria.
 *  2. config.php NO se sube a GitHub (está en .gitignore).
 *  3. Cualquier valor puede pisarse con una variable de entorno
 *     REBOO_<NOMBRE> (por ejemplo REBOO_BD_CONTRA).
 * ======================================================================
 */

// 'desarrollo' muestra errores y saltea reCAPTCHA si no hay clave.
// En el servidor real poné 'produccion'.
define('APP_MODO', 'desarrollo');

// ---------- Base de datos ----------
define('BD_HOST', 'localhost');
define('BD_PUERTO', '3306');
define('BD_NOMBRE', 'proyecto2026');
define('BD_USUARIO', 'reboo_app');
define('BD_CONTRA', 'CambiarEstaClave_2026!'); // la misma del CREATE USER del SQL

// '1' reutiliza la conexión a MySQL entre pedidos. Conviene con Apache y mucha gente
// en el mundo abierto; con 'php -S' no hace falta.
define('BD_PERSISTENTE', '0');

// ---------- Contraseñas ----------
// Pimienta secreta que se agrega a cada contraseña antes del hash Argon2id.
// Si la cambiás, las contraseñas existentes dejan de funcionar.
define('PEPPER_CONTRASENA', 'CAMBIAR_POR_UNA_CADENA_LARGA_Y_ALEATORIA');

// ---------- reCAPTCHA v3 (opcional en desarrollo) ----------
define('RECAPTCHA_CLAVE_SITIO', '');
define('RECAPTCHA_CLAVE_SECRETA', '');
