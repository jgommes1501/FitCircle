<?php

class Retos extends Controller {

    function __construct() {
        parent::__construct();
    }

    function index() {
        // Iniciar sesión segura y exigir autenticación
        require_login();

        // Propiedades de la vista
        $this->view->title = "Retos";

        // Renderizar vista
        $this->view->render('retos/index');
    }

}

?>