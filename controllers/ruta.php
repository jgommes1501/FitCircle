<?php

require_once MODEL_PATH . 'ruta.model.php';

class Ruta extends Controller {

    private $rutaModel;

    function __construct() {
        parent::__construct();
        $this->rutaModel = new rutaModel();
        $this->rutaModel->ensureSocialSchema();
    }

    function index() {
        sec_session_start();

        $userId = get_user_id();
        $loggedIn = is_logged_in();

        $this->view->logged_in = is_logged_in();
        $this->view->user_name = get_user_name();
        $this->view->title = "Rutas";
        $this->view->my_routes = $loggedIn ? $this->rutaModel->getUserRoutes($userId) : [];
        $this->view->community_routes = $this->rutaModel->getCommunityRoutes($loggedIn ? $userId : null);
        $this->view->csrf_token = $_SESSION['csrf_token'] ?? null;

        if (empty($this->view->csrf_token)) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $this->view->csrf_token = $_SESSION['csrf_token'];
        }

        if (isset($_SESSION['notify'])) {
            $this->view->notify = $_SESSION['notify'];
            unset($_SESSION['notify']);
        }

        if (isset($_SESSION['errors'])) {
            $this->view->errors = $_SESSION['errors'];
            unset($_SESSION['errors']);
        }

        $this->view->render('ruta/index');
    }

    public function save() {
        sec_session_start();

        header('Content-Type: application/json; charset=utf-8');

        if (!is_logged_in()) {
            http_response_code(401);
            echo json_encode([
                'ok' => false,
                'message' => 'Debes iniciar sesión para guardar rutas.'
            ]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'message' => 'Método no permitido']);
            return;
        }

        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Datos inválidos']);
            return;
        }

        $title = trim((string) ($payload['title'] ?? ''));
        $distanceMeters = (float) ($payload['distance_m'] ?? 0);
        $durationSeconds = (int) ($payload['duration_s'] ?? 0);
        $steps = (int) ($payload['steps'] ?? 0);
        $calories = (int) ($payload['calories'] ?? 0);
        $points = $payload['points'] ?? [];
        $isPublic = !empty($payload['is_public']) ? 1 : 0;

        if ($title === '') {
            $title = 'Ruta ' . date('d/m/Y H:i');
        }

        $title = mb_substr($title, 0, 120);

        if ($distanceMeters < 0 || $durationSeconds < 0 || $steps < 0 || $calories < 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Métricas no válidas']);
            return;
        }

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

        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            $_SESSION['errors'] = ['Token CSRF inválido'];
            header('Location: ' . ROUTE_URL . 'ruta/index');
            exit();
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $distanceKm = (float) ($_POST['distance_km'] ?? 0);
        $durationMinutes = (int) ($_POST['duration_min'] ?? 0);
        $steps = (int) ($_POST['steps'] ?? 0);
        $calories = (int) ($_POST['calories'] ?? 0);
        $isPublic = !empty($_POST['is_public']) ? 1 : 0;

        $errors = [];

        if ($title === '') {
            $errors[] = 'El nombre de la ruta es obligatorio.';
        }

        if ($distanceKm <= 0) {
            $errors[] = 'La distancia debe ser mayor que 0.';
        }

        if ($durationMinutes <= 0) {
            $errors[] = 'El tiempo debe ser mayor que 0.';
        }

        if ($steps < 0 || $calories < 0) {
            $errors[] = 'Pasos y calorías no pueden ser negativos.';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: ' . ROUTE_URL . 'ruta/index');
            exit();
        }

        $distanceMeters = round($distanceKm * 1000, 2);
        $durationSeconds = $durationMinutes * 60;

        $this->rutaModel->saveRoute(
            get_user_id(),
            mb_substr($title, 0, 120),
            $distanceMeters,
            $durationSeconds,
            $steps,
            $calories,
            null,
            $isPublic
        );

        $_SESSION['notify'] = 'Ruta añadida correctamente.';
        header('Location: ' . ROUTE_URL . 'ruta/index');
        exit();
    }

    public function historial() {
        sec_session_start();

        if (!is_logged_in()) {
            header('Location: ' . ROUTE_URL . 'auth/login');
            exit();
        }

        $userId = get_user_id();
        $this->view->title = 'Mi historial';
        $this->view->logged_in = true;
        $this->view->user_name = get_user_name();
        $this->view->my_routes = $this->rutaModel->getUserRoutes($userId, 1000);

        if (isset($_SESSION['notify'])) {
            $this->view->notify = $_SESSION['notify'];
            unset($_SESSION['notify']);
        }

        $this->view->render('ruta/historial');
    }

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

        $result = $this->rutaModel->toggleLike($routeId, get_user_id());

        $isXhr = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($isXhr) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok'          => (bool) $result,
                'liked'       => $result === 'liked',
                'likes_count' => $this->rutaModel->getLikesCount($routeId)
            ]);
            exit();
        }

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