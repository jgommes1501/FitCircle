<?php

class Main extends Controller {

    function __construct() {
        parent::__construct();
    }

    function index() {
        // Iniciar sesión segura (sin forzar autenticación)
        sec_session_start();

        // Comprobar si existe alguna notificación
        if (isset($_SESSION['notify'])) {
            $this->view->notify = $_SESSION['notify'];
            unset($_SESSION['notify']);
        }

        // Pasar estado de autenticación a la vista
        $this->view->logged_in = is_logged_in();
        $this->view->user_name = get_user_name();

        // Creo la propiedad title para la vista
        $this->view->title = "FitCircle - Inicio";

        // Llama a la vista para renderizar la página
        $this->view->render('main/index');
    }

}

?>
