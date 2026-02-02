<?php

    // Configuración de la aplicación
    define('APP_NAME', 'FitCircle');
    define('APP_VERSION', '1.0');

    // Definir URL base
    define('URL', 'http://localhost/FitCircle/');
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
    define('HOST', 'localhost');
    define('DB', 'fitcircle');
    define('USER', 'root');
    define('PASSWORD', '');
    define('CHARSET', 'utf8');

?>
