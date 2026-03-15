<?php

class Perfil extends Controller {

    function __construct() {
        parent::__construct();
    }

    function index() {
        // Iniciar sesión
        sec_session_start();

        // Verificar si el usuario está autenticado
        if (!is_logged_in()) {
            // Redirigir al login si no está autenticado
            header("Location: " . ROUTE_URL . "auth/login");
            exit();
        }

        // Creo la propiedad title para la vista
        $this->view->title = "Mi Perfil";
        
        // Obtener datos del usuario
        $this->view->user_name = get_user_name();
        $this->view->user_email = get_user_email();

        // Llama a la vista para renderizar la página
        $this->view->render('perfil/index');
    }

}

?>
