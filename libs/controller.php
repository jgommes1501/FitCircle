<?php

/**
 * ============================================================
 * CLASE BASE CONTROLLER — libs/controller.php
 * ============================================================
 * Todos los controladores de la aplicación heredan de esta
 * clase. Al crearse, instancia automáticamente la clase View
 * para que cada controlador pueda pasarle datos a la vista
 * con $this->view->propiedad = valor y renderizarla con
 * $this->view->render('carpeta/vista').
 * - Clase base para todos los controladores. Inyecta la vista.
 * ============================================================
 */
class Controller {

    // Objeto View accesible desde cualquier controlador hijo
    public $view;

    public function __construct() {
        // Crea la vista al instanciar cualquier controlador
        $this->view = new View();
    }

}

?>
