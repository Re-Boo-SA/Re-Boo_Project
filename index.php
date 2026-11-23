<?php
if (PHP_SAPI === 'cli-server') {
    $rutaSolicitada = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

    if ($rutaSolicitada === '/') {
        header('Location: /DraftDerMauer/', true, 302);
        exit();
    }

    if ($rutaSolicitada === '/DraftDerMauer') {
        header('Location: /DraftDerMauer/', true, 301);
        exit();
    }

    if (preg_match('#^/DraftDerMauer/(css|fonts|img|js|lang|Music)/(.+)$#', $rutaSolicitada, $coincidencias)) {
        $directorioRecursos = realpath(__DIR__ . '/Capa_Presentacion/' . $coincidencias[1]);
        $recurso = $directorioRecursos === false
            ? false
            : realpath($directorioRecursos . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $coincidencias[2]));

        if (
            $recurso === false
            || !is_file($recurso)
            || !is_readable($recurso)
            || !str_starts_with(
                strtolower($recurso),
                strtolower($directorioRecursos . DIRECTORY_SEPARATOR)
            )
        ) {
            http_response_code(404);
            exit();
        }

        $tiposMime = [
            'css' => 'text/css; charset=UTF-8',
            'js' => 'application/javascript; charset=UTF-8',
            'json' => 'application/json; charset=UTF-8',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'otf' => 'font/otf',
            'mp3' => 'audio/mpeg',
        ];
        $extension = strtolower(pathinfo($recurso, PATHINFO_EXTENSION));
        if (!isset($tiposMime[$extension])) {
            http_response_code(404);
            exit();
        }

        header('Content-Type: ' . $tiposMime[$extension]);
        readfile($recurso);
        exit();
    }

    $rutasPublicas = [
        '/DraftDerMauer/' => 'index.php',
        '/DraftDerMauer/login' => 'login.php',
        '/DraftDerMauer/registro' => 'registro.php',
        '/DraftDerMauer/error' => 'error.php',
        '/DraftDerMauer/estado' => 'estado.php',
    ];
    $rutasProtegidas = [
        '/DraftDerMauer/inicio' => 'inicioHome.php',
        '/DraftDerMauer/calculadora' => 'CalculadoraT.php',
        '/DraftDerMauer/auditoria' => 'auditoria.php',
        '/DraftDerMauer/panel-admin' => 'panel_admin.php',
        '/DraftDerMauer/unirse' => 'unirse_partida.php',
        '/DraftDerMauer/tienda' => 'tienda.php',
        '/DraftDerMauer/cerrar-sesion' => 'logout.php',
        '/DraftDerMauer/perfil' => 'perfil.php',
        '/DraftDerMauer/crear-partida' => 'procesar_crear_partida.php',
        '/DraftDerMauer/unirse-partida' => 'procesar_unirse_partida.php',
        '/DraftDerMauer/partida' => 'partida.php',
        '/DraftDerMauer/api-partida' => 'api_partida.php',
        '/DraftDerMauer/ranking' => 'ranking.php',
        '/DraftDerMauer/competitivo' => 'competitivo.php',
        '/DraftDerMauer/api-cola' => 'api_cola.php',
        '/DraftDerMauer/mundo' => 'mundo.php',
        '/DraftDerMauer/api-mundo' => 'api_mundo.php',
        '/DraftDerMauer/api-calcular' => 'api_calcular.php',
        '/DraftDerMauer/galeria' => 'galeria.php',
        '/DraftDerMauer/tutorial' => 'tutorial.php',
        '/DraftDerMauer/amigos' => 'amigos.php',
        '/DraftDerMauer/api-social' => 'api_social.php',
        '/DraftDerMauer/torneos' => 'torneos.php',
        '/DraftDerMauer/personalizar' => 'personalizar.php',
        '/DraftDerMauer/api-torneos' => 'api_torneos.php',
        '/DraftDerMauer/campana' => 'campana.php',
        '/DraftDerMauer/api-campana' => 'api_campana.php',
        '/DraftDerMauer/reportes' => 'reportes.php',
    ];
    $rutas = $rutasPublicas + $rutasProtegidas;

    $rutaNormalizada = $rutaSolicitada === '/DraftDerMauer/'
        ? $rutaSolicitada
        : rtrim($rutaSolicitada, '/');

    if (isset($rutas[$rutaNormalizada])) {
        $archivo = $rutas[$rutaNormalizada];
        $_SERVER['SCRIPT_NAME'] = '/Capa_Presentacion/' . $archivo;
        $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/Capa_Presentacion/' . $archivo;
        require $_SERVER['SCRIPT_FILENAME'];
        exit();
    }

    if (str_starts_with($rutaSolicitada, '/DraftDerMauer/')) {
        http_response_code(404);
        exit();
    }
}

// Entrada desde la raíz del proyecto (XAMPP).
header('Location: DraftDerMauer/');
exit();
