<?php

/**
 * Classe App
 * Enrutador principal de la aplicación
 */
class App {

    protected $controller = DEFAULT_CONTROLLER;
    protected $method = DEFAULT_METHOD;
    protected $params = [];

    public function __construct() {

        // Obtener la URL
        $url = $this->parseUrl();

        // Verificar si la URL no es vacía y existe el controlador
        if ($url && isset($url[0]) && file_exists(CONTROLLER_PATH . $url[0] . '.php')) {
            $this->controller = $url[0];
            unset($url[0]);
        }

        // Requerir el controlador
        require_once CONTROLLER_PATH . $this->controller . '.php';

        // Crear instancia del controlador
        $this->controller = new $this->controller;

        // Verificar si existe el método
        if ($url && isset($url[1])) {
            if (method_exists($this->controller, $url[1])) {
                $this->method = $url[1];
                unset($url[1]);
            }
        }

        // Obtener parámetros
        $this->params = $url ? array_values($url) : [];

        // Llamar al método del controlador con los parámetros
        call_user_func_array([$this->controller, $this->method], $this->params);

    }

    public function parseUrl() {

        if (isset($_GET['url'])) {
            return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }
        return null;
    }

}

?>
