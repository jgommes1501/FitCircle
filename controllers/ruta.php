<?php

class Ruta extends Controller {

    function __construct() {
        parent::__construct();
    }

    function index() {
        // Iniciar sesión segura y exigir autenticación
        require_login();

        // Propiedades de la vista
        $this->view->title = "Rutas";

        // Renderizar vista
        $this->view->render('ruta/index');
    }

}

?>