<?php

class View {

    public function render($viewName) {

        $viewFile = VIEW_PATH . $viewName . ".php";
        
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            die("La vista: " . $viewName . " no existe");
        }

    }

}

?>
