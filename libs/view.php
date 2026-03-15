<?php

class View {

    private $data = [];

    public function __set($name, $value) {
        $this->data[$name] = $value;
    }

    public function __get($name) {
        return $this->data[$name] ?? null;
    }

    public function __isset($name) {
        return isset($this->data[$name]);
    }

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
