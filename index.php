<?php

/**
 * FitCircle - Punto de entrada principal
 * index.php - Router de la aplicación MVC
 */

// Requerir configuración
require_once 'config/config.php';

// Requerir librerías
require_once LIB_PATH . 'database.php';
require_once LIB_PATH . 'model.php';
require_once LIB_PATH . 'view.php';
require_once LIB_PATH . 'controller.php';
require_once LIB_PATH . 'app.php';

// Requerir funciones
require_once 'functions/session_seg.php';

// Inicializar la aplicación
$app = new App();

?>
