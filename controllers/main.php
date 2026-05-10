<?php

/**
 * ============================================================
 * CONTROLADOR PRINCIPAL — controllers/main.php
 * ============================================================
 * Gestiona la página de inicio (Inicio) de FitCircle.
 * URL: /main/index  (también es la página por defecto)
 * No requiere sesión iniciada; cualquier visitante puede verla.
 * ============================================================
 */
class Main extends Controller {

    function __construct() {
        parent::__construct(); // Llama al constructor de Controller (crea $this->view)
    }

    /**
     * Método: index()
     * Muestra la página de inicio con el rastreador GPS en vivo,
     * el hero, las tarjetas de características y las secciones estáticas.
     * URL: /main/index
     */
    function index() {
        // Inicia o reanuda la sesión segura sin forzar autenticación
        sec_session_start();

        // Recoge y borra cualquier notificación flash guardada en sesión
        // (ej: "¡Ruta guardada!" tras redirigir desde otra página)
        if (isset($_SESSION['notify'])) {
            $this->view->notify = $_SESSION['notify'];
            unset($_SESSION['notify']);
        }

        // Informa a la vista si el usuario tiene sesión iniciada
        $this->view->logged_in = is_logged_in();
        $this->view->user_name = get_user_name();

        // Título que aparece en la pestaña del navegador
        $this->view->title = "FitCircle - Inicio";

        // Carga la vista: views/main/index.php
        $this->view->render('main/index');
    }

}

?>
