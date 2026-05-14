<?php

/**
 * ============================================================
 * ENRUTADOR — libs/app.php
 * ============================================================
 * La clase App es el núcleo del sistema MVC.
 * Recibe la URL del navegador con el formato:
 *  index.php?url=controlador/metodo/param1/param2
 * Busca el archivo del controlador en /controllers/,
 * crea una instancia y llama al método correspondiente.
 * Si no se indica controlador/método, usa los valores
 * por defecto definidos en config.php (main / index).
 * - Router. Analiza la URL y decide qué controlador ejecutar
 * libs/ (Motor MVC - el cerebro)
 * ============================================================
 */
class App {

    protected $controller = DEFAULT_CONTROLLER;
    protected $method = DEFAULT_METHOD;
    protected $params = [];

    public function __construct() {

        // Analiza la URL recibida y la divide en segmentos
        $url = $this->parseUrl();

        // Segmento [0] = nombre del controlador (ej: 'ruta', 'auth', 'retos')
        // Si existe el archivo PHP del controlador, lo usamos; si no, usamos el por defecto
        if ($url && isset($url[0]) && file_exists(CONTROLLER_PATH . $url[0] . '.php')) {
            $this->controller = $url[0];
            unset($url[0]); // Elimina el segmento del controlador para no pasarlo como parámetro
        }

        // Carga el archivo del controlador (ej: controllers/ruta.php)
        require_once CONTROLLER_PATH . $this->controller . '.php';

        // Crea la instancia del controlador (ej: new Ruta())
        $this->controller = new $this->controller;

        // Segmento [1] = nombre del método (ej: 'index', 'save', 'create')
        // Si el método existe en el controlador lo usamos; si no, usamos 'index'
        if ($url && isset($url[1])) {
            if (method_exists($this->controller, $url[1])) {
                $this->method = $url[1];
                unset($url[1]); // Elimina el segmento del método
            }
        }

        // El resto de segmentos son parámetros adicionales (ej: /ruta/toggle_like/42 → param = 42)
        $this->params = $url ? array_values($url) : [];

        // Ejecuta el método del controlador pasándole los parámetros extra
        call_user_func_array([$this->controller, $this->method], $this->params);

    }

    /**
     * Analiza el parámetro GET 'url' y lo divide en segmentos limpios.
     * Ejemplo: ?url=ruta/save → ['ruta', 'save']
     * Sanea la URL para evitar caracteres peligrosos.
     */
    public function parseUrl() {

        if (isset($_GET['url'])) {
            // Elimina la barra final y sanea la URL
            $cleanUrl = filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL);
            if ($cleanUrl === '' || $cleanUrl === false) {
                return [];
            }

            // Divide por '/' y filtra segmentos vacíos
            return array_values(array_filter(explode('/', $cleanUrl), function ($segment) {
                return $segment !== '';
            }));
        }
        return null; // Sin URL → se usarán controlador/método por defecto
    }

}

?>
