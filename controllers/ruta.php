<?php

/**
 * ============================================================
 * CONTROLADOR DE RUTAS — controllers/ruta.php
 * ============================================================
 * Gestiona todo lo relacionado con las rutas deportivas:
 * rastreo GPS en vivo, guardado, creación manual, historial
 * y sistema de me gustas.
 *
 * Métodos:
 *   index()         → Página de rutas (requiere sesión para guardar)
 *   save()          → Guarda una ruta GPS via AJAX/JSON (API)
 *   create_manual() → Guarda una ruta introducida manualmente por formulario
 *   historial()     → Muestra todas las rutas del usuario (requiere sesión)
 *   toggle_like()   → Da/quita me gusta a una ruta (soporta AJAX y redirect)
 * ============================================================
 */

// Carga el modelo de rutas (tablas 'routes' y 'route_likes')
require_once MODEL_PATH . 'ruta.model.php';

class Ruta extends Controller {

    // Instancia del modelo de rutas
    private $rutaModel;

    function __construct() {
        parent::__construct();
        $this->rutaModel = new rutaModel();
        // Crea las tablas 'routes', 'route_likes' y la columna 'avatar_path' si no existen
        $this->rutaModel->ensureSocialSchema();
    }

    /**
     * Método: index()
     * Carga la página de Rutas con:
     *   - Rutas propias del usuario (si tiene sesión)
     *   - Rutas públicas de la comunidad
     *   - Token CSRF para el formulario de guardado y me gustas
     * URL: /ruta/index
     */
    function index() {
        sec_session_start();

        $userId   = get_user_id();
        $loggedIn = is_logged_in();

        $this->view->logged_in       = is_logged_in();
        $this->view->user_name       = get_user_name();
        $this->view->title           = "Rutas";
        // Solo carga rutas propias si hay sesión
        $this->view->my_routes       = $loggedIn ? $this->rutaModel->getUserRoutes($userId) : [];
        // Las rutas públicas se muestran siempre; si hay sesión también marca cuáles le gustan
        $this->view->community_routes = $this->rutaModel->getCommunityRoutes($loggedIn ? $userId : null);
        $this->view->csrf_token      = $_SESSION['csrf_token'] ?? null;

        // Genera el token CSRF si aún no existe
        if (empty($this->view->csrf_token)) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $this->view->csrf_token = $_SESSION['csrf_token'];
        }

        // Recoge mensajes flash de acciones anteriores
        if (isset($_SESSION['notify'])) {
            $this->view->notify = $_SESSION['notify'];
            unset($_SESSION['notify']);
        }
        if (isset($_SESSION['errors'])) {
            $this->view->errors = $_SESSION['errors'];
            unset($_SESSION['errors']);
        }

        $this->view->render('ruta/index'); // views/ruta/index.php
    }

    /**
     * Método: save()
     * Endpoint API que recibe los datos de una ruta GPS en formato JSON
     * y la guarda en la base de datos.
     * Solo acepta peticiones POST autenticadas.
     * Responde siempre con JSON {ok: bool, message: string}.
     * URL: /ruta/save  (requiere sesión, solo POST, consume JSON)
     */
    public function save() {
        sec_session_start();

        // La respuesta siempre será JSON para que el JS del frontend la procese
        header('Content-Type: application/json; charset=utf-8');

        // Solo usuarios con sesión pueden guardar rutas
        if (!is_logged_in()) {
            http_response_code(401);
            echo json_encode([
                'ok'      => false,
                'message' => 'Debes iniciar sesión para guardar rutas.'
            ]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'message' => 'Método no permitido']);
            return;
        }

        // Lee el cuerpo JSON de la petición (enviado por ruta-gps.js)
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Datos inválidos']);
            return;
        }

        // Extrae y convierte cada campo del JSON al tipo correcto
        $title           = trim((string) ($payload['title']       ?? ''));
        $distanceMeters  = (float) ($payload['distance_m']  ?? 0);
        $durationSeconds = (int)   ($payload['duration_s']  ?? 0);
        $steps           = (int)   ($payload['steps']        ?? 0);
        $calories        = (int)   ($payload['calories']     ?? 0);
        $points          = $payload['points'] ?? [];    // Array de coordenadas [lat, lng]
        $isPublic        = !empty($payload['is_public']) ? 1 : 0;

        // Nombre por defecto si el usuario no escribió ninguno
        if ($title === '') {
            $title = 'Ruta ' . date('d/m/Y H:i');
        }

        $title = mb_substr($title, 0, 120); // Máximo 120 caracteres

        // Las métricas no pueden ser negativas
        if ($distanceMeters < 0 || $durationSeconds < 0 || $steps < 0 || $calories < 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Métricas no válidas']);
            return;
        }

        // Sanea los puntos GPS: valida que cada coordenada esté en rango y limita a 5000 puntos
        $cleanPoints = [];
        if (is_array($points)) {
            foreach ($points as $point) {
                if (!is_array($point) || count($point) !== 2) {
                    continue;
                }

                $lat = (float) $point[0];
                $lng = (float) $point[1];
                if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                    continue;
                }

                $cleanPoints[] = [$lat, $lng];
                if (count($cleanPoints) >= 5000) {
                    break;
                }
            }
        }

        $pathJson = !empty($cleanPoints) ? json_encode($cleanPoints) : null;
        $routeId = $this->rutaModel->saveRoute(
            get_user_id(),
            $title,
            round($distanceMeters, 2),
            $durationSeconds,
            $steps,
            $calories,
            $pathJson,
            $isPublic
        );

        echo json_encode([
            'ok' => true,
            'route_id' => $routeId,
            'message' => 'Ruta guardada correctamente'
        ]);
    }

    /**
     * Método: create_manual()
     * Procesa el formulario de creación manual de ruta (POST).
     * Permite registrar una actividad pasada sin GPS introduciendo
     * los datos a mano (distancia, tiempo, pasos, calorías).
     * URL: /ruta/create_manual  (requiere sesión, solo POST)
     */
    public function create_manual() {
        sec_session_start();

        if (!is_logged_in()) {
            header('Location: ' . ROUTE_URL . 'auth/login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ROUTE_URL . 'ruta/index');
            exit();
        }

        // Verifica el token CSRF del formulario
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            $_SESSION['errors'] = ['Token CSRF inválido'];
            header('Location: ' . ROUTE_URL . 'ruta/index');
            exit();
        }

        // Lee y convierte los campos del formulario
        $title          = trim((string) ($_POST['title']        ?? ''));
        $distanceKm     = (float) ($_POST['distance_km']  ?? 0);
        $durationMinutes = (int)  ($_POST['duration_min'] ?? 0);
        $steps          = (int)   ($_POST['steps']         ?? 0);
        $calories       = (int)   ($_POST['calories']      ?? 0);
        $isPublic       = !empty($_POST['is_public']) ? 1 : 0;

        $errors = [];

        if ($title === '')        $errors[] = 'El nombre de la ruta es obligatorio.';
        if ($distanceKm <= 0)    $errors[] = 'La distancia debe ser mayor que 0.';
        if ($durationMinutes <= 0) $errors[] = 'El tiempo debe ser mayor que 0.';
        if ($steps < 0 || $calories < 0) $errors[] = 'Pasos y calorías no pueden ser negativos.';

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: ' . ROUTE_URL . 'ruta/index');
            exit();
        }

        // Convierte las unidades del formulario a las unidades internas de la BD
        $distanceMeters  = round($distanceKm * 1000, 2); // km → metros
        $durationSeconds = $durationMinutes * 60;          // minutos → segundos

        // Guarda la ruta en BD (sin path_json porque es manual, sin traza GPS)
        $this->rutaModel->saveRoute(
            get_user_id(),
            mb_substr($title, 0, 120),
            $distanceMeters,
            $durationSeconds,
            $steps,
            $calories,
            null,      // Sin traza GPS
            $isPublic
        );

        $_SESSION['notify'] = 'Ruta añadida correctamente.';
        header('Location: ' . ROUTE_URL . 'ruta/index');
        exit();
    }

    /**
     * Método: historial()
     * Muestra la página de historial completo del usuario
     * con todas sus rutas guardadas (hasta 1000).
     * URL: /ruta/historial  (requiere sesión)
     */
    public function historial() {
        sec_session_start();

        // Solo usuarios autenticados pueden ver su historial
        if (!is_logged_in()) {
            header('Location: ' . ROUTE_URL . 'auth/login');
            exit();
        }

        $userId = get_user_id();
        $this->view->title     = 'Mi historial';
        $this->view->logged_in = true;
        $this->view->user_name = get_user_name();
        // Carga hasta 1000 rutas del usuario ordenadas por fecha descendente
        $this->view->my_routes = $this->rutaModel->getUserRoutes($userId, 1000);

        if (isset($_SESSION['notify'])) {
            $this->view->notify = $_SESSION['notify'];
            unset($_SESSION['notify']);
        }

        $this->view->render('ruta/historial'); // views/ruta/historial.php
    }

    /**
     * Método: toggle_like()
     * Da o quita un me gusta a una ruta.
     * Soporta dos modos:
     *   - AJAX (XMLHttpRequest): responde con JSON {ok, liked, likes_count}
     *   - Formulario normal: redirige de vuelta a la página de rutas
     * URL: /ruta/toggle_like/{routeId}  (requiere sesión, solo POST)
     */
    public function toggle_like($routeId = null) {
        sec_session_start();

        if (!is_logged_in()) {
            header('Location: ' . ROUTE_URL . 'auth/login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ROUTE_URL . 'ruta/index');
            exit();
        }

        // Verifica el token CSRF del formulario
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            $_SESSION['errors'] = ['Token CSRF inválido'];
            header('Location: ' . ROUTE_URL . 'ruta/index');
            exit();
        }

        $routeId = (int) $routeId;
        if ($routeId <= 0) {
            header('Location: ' . ROUTE_URL . 'ruta/index');
            exit();
        }

        // Alterna el me gusta: si ya existía lo quita, si no existía lo añade
        $result = $this->rutaModel->toggleLike($routeId, get_user_id());

        // Detecta si la petición viene de JavaScript (AJAX) para responder con JSON
        $isXhr = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($isXhr) {
            // Respuesta JSON para el script AJAX de la vista de rutas
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok'          => (bool) $result,
                'liked'       => $result === 'liked',
                'likes_count' => $this->rutaModel->getLikesCount($routeId)
            ]);
            exit();
        }

        // Si es un formulario normal, guarda notificación y redirige
        if ($result === 'liked') {
            $_SESSION['notify'] = 'Te gusta esta ruta.';
        } elseif ($result === 'unliked') {
            $_SESSION['notify'] = 'Has quitado tu me gusta.';
        }

        header('Location: ' . ROUTE_URL . 'ruta/index');
        exit();
    }

}

?>