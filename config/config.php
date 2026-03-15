<?php

    // Configuración de la aplicación
    define('APP_NAME', 'FitCircle');
    define('APP_VERSION', '1.0');

    // Definir URL base (automática para local/hosting)
    // En hosting compartido/proxy (como InfinityFree), HTTPS puede venir en cabeceras.
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
        (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
    );

    $scheme = $isHttps ? 'https' : 'http';
    $httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (!$isHttps && stripos($httpHost, '.infinityfreeapp.com') !== false) {
        // InfinityFree suele forzar HTTPS aunque el backend no lo indique correctamente.
        $scheme = 'https';
    }

    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    $baseUrl = $scheme . '://' . $httpHost . ($basePath !== '' ? $basePath . '/' : '/');
    define('URL', $baseUrl);
    define('ROUTE_URL', $baseUrl . 'index.php?url=');
    define('ROOT_PATH', dirname(dirname(__FILE__)));
    
    // Definir paths de carpetas
    define('CONTROLLER_PATH', ROOT_PATH . "/controllers/");
    define('MODEL_PATH', ROOT_PATH . "/models/");
    define('VIEW_PATH', ROOT_PATH . "/views/");
    define('LIB_PATH', ROOT_PATH . "/libs/");
    define('TEMPLATE_PATH', ROOT_PATH . "/template/");

    // Definir constantes de controlador por defecto
    define('DEFAULT_CONTROLLER', 'main');
    define('DEFAULT_METHOD', 'index');
    define('DEFAULT_LAYOUT', 'main');

    // Definir constantes de errores
    define('ERROR_CONTROLLER', 'error');

    // Configuración de Base de Datos
    // Prioriza variables de entorno para hosting (InfinityFree u otros).
    // Si no existen, usa valores locales por defecto.
    $dbHost = getenv('DB_HOST') ?: 'sql309.infinityfree.com';
    $dbName = getenv('DB_NAME') ?: 'if0_41221485_fitcircle';
    $dbUser = getenv('DB_USER') ?: 'if0_41221485';
    $dbPassword = getenv('DB_PASSWORD') ?: 'POMHoRjjGm';

    define('HOST', $dbHost);
    define('DB', $dbName);
    define('USER', $dbUser);
    define('PASSWORD', $dbPassword);
    define('CHARSET', 'utf8');

?>
