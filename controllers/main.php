<?php

class Main extends Controller {

    function __construct() {
        parent::__construct();
    }

    function index() {
        // Iniciar sesión
        sec_session_start();

        // Comprobar si existe alguna notificación
        if (isset($_SESSION['notify'])) {
            $this->view->notify = $_SESSION['notify'];
            unset($_SESSION['notify']);
        }

        // Creo la propiedad title para la vista
        $this->view->title = "FitCircle - Inicio";

        // Llama a la vista para renderizar la página
        $this->view->render('main/index');
    }

}

?>
