<?php

/**
 * ============================================================
 * CLASE BASE VIEW — libs/view.php
 * ============================================================
 * Gestiona los datos que el controlador quiere mostrar en
 * la plantilla HTML/PHP. Los datos se guardan internamente
 * en un array $data y se acceden con la sintaxis mágica
 * $this->view->titulo = 'Hola' (escritura) y
 * $this->titulo (lectura desde dentro de la vista).
 *
 * El método render() carga el archivo PHP de la vista
 * ubicado en /views/controlador/metodo.php.
 * ============================================================
 */
class View {

    // Array interno que almacena todas las variables pasadas desde el controlador
    private $data = [];

    // Magia PHP: permite escribir $this->view->nombre = valor desde el controlador
    public function __set($name, $value) {
        $this->data[$name] = $value;
    }

    // Magia PHP: permite leer $this->nombre desde dentro del archivo de vista
    public function __get($name) {
        return $this->data[$name] ?? null;
    }

    // Magia PHP: permite usar isset($this->nombre) en las vistas
    public function __isset($name) {
        return isset($this->data[$name]);
    }

    /**
     * Carga y ejecuta el archivo de vista PHP.
     * @param string $viewName  Ruta relativa dentro de /views/ sin extensión
     *                          Ejemplo: 'retos/index' → /views/retos/index.php
     */
    public function render($viewName) {

        $viewFile = VIEW_PATH . $viewName . ".php";
        
        if (file_exists($viewFile)) {
            // Incluye la vista. Dentro del archivo, $this hace referencia a este objeto View,
            // así que las vistas pueden leer los datos con $this->variable.
            require_once $viewFile;
        } else {
            die("La vista: " . $viewName . " no existe");
        }

    }

}

?>
