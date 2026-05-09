<?php

class Retos extends Controller {

    function __construct() {
        parent::__construct();
    }

    function index() {
        // Iniciar sesión segura (sin forzar autenticación)
        sec_session_start();

        // Pasar estado de autenticación a la vista
        $this->view->logged_in = is_logged_in();
        $this->view->user_name = get_user_name();

        // Propiedades de la vista
        $this->view->title = "Retos";

        // Renderizar vista
        $this->view->render('retos/index');
    }

}

?>