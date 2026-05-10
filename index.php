<?php

/**
 * ============================================================
 * PUNTO DE ENTRADA PRINCIPAL — index.php
 * ============================================================
 * Este es el único archivo PHP que recibe todas las peticiones
 * del navegador (Front Controller). Lee la URL (?url=...) y
 * delega el trabajo al enrutador (App) que decide qué
 * controlador y método ejecutar.
 * ============================================================
 */

// Carga la configuración global: rutas, BD, constantes de la app
require_once 'config/config.php';

// Carga las clases base del framework MVC propio
require_once LIB_PATH . 'database.php';   // Conexión a MySQL via PDO
require_once LIB_PATH . 'model.php';      // Clase base Model
require_once LIB_PATH . 'view.php';       // Clase base View (renderiza vistas PHP)
require_once LIB_PATH . 'controller.php'; // Clase base Controller
require_once LIB_PATH . 'app.php';        // Enrutador principal

// Carga las funciones de sesión segura (sec_session_start, is_logged_in, etc.)
require_once 'functions/session_seg.php';

// Arranca el enrutador: parsea la URL y llama al controlador correcto
$app = new App();

?>
