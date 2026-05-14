<?php

/**
 * ============================================================
 * CONTROLADOR DE RETOS — controllers/retos.php
 * ============================================================
 * Gestiona todo el sistema de retos de la aplicación.
 * Los visitantes sin sesión pueden ver retos públicos.
 * Para crear, unirse, actualizar progreso o eliminar
 * es necesario tener sesión iniciada.
 *
 * Métodos:
 *   index()          → Muestra la página de retos
 *   create()         → Crea un nuevo reto
 *   join()           → Une al usuario a un reto existente
 *   leave()          → Abandona un reto (no puede el creador)
 *   updateProgress() → Actualiza los km/pasos del usuario en un reto
 *   delete()         → Elimina un reto (solo el creador)
 * -  Crear retos, unirse, actualizar progreso.
 * ============================================================
 */

// Carga el modelo de retos (tablas 'challenges' y 'challenge_participants')
require_once MODEL_PATH . 'retos.model.php';

class Retos extends Controller {

    // Instancia del modelo de retos
    private $retosModel;

    function __construct() {
        parent::__construct();
        $this->retosModel = new retosModel();
        // Crea las tablas 'challenges' y 'challenge_participants' si no existen
        $this->retosModel->ensureSchema();
    }

    /**
     * Método: index()
     * Carga y muestra la página principal de Retos.
     * Pasa a la vista:
     *   - Retos en los que participa el usuario (my_challenges)
     *   - Todos los retos públicos con info de participación (public_challenges)
     *   - Token CSRF para los formularios de acción
     * URL: /retos/index
     */
    function index() {
        sec_session_start();

        $userId   = get_user_id();
        $loggedIn = is_logged_in();

        // Datos básicos para la vista
        $this->view->logged_in  = $loggedIn;
        $this->view->user_name  = get_user_name();
        $this->view->title      = "Retos";

        // Solo carga los retos personales si hay sesión; la lista pública siempre se carga
        $this->view->my_challenges     = $loggedIn ? $this->retosModel->getMyChallenges($userId) : [];
        $this->view->public_challenges = $this->retosModel->getPublicChallenges($loggedIn ? $userId : null);

        // Genera el token CSRF si aún no existe en sesión
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $this->view->csrf_token = $_SESSION['csrf_token'];

        // Recoge mensajes flash (notificaciones y errores de acciones anteriores)
        if (isset($_SESSION['notify'])) {
            $this->view->notify = $_SESSION['notify'];
            unset($_SESSION['notify']);
        }
        if (isset($_SESSION['errors'])) {
            $this->view->errors = $_SESSION['errors'];
            unset($_SESSION['errors']);
        }

        $this->view->render('retos/index'); // views/retos/index.php
    }

    /**
     * Método: create()
     * Procesa el formulario de creación de un nuevo reto (POST).
     * Calcula automáticamente las fechas de inicio y fin según
     * el período elegido (semanal = 7 días, mensual = 30 días).
     * El creador queda automáticamente inscrito como participante.
     * URL: /retos/create  (requiere sesión, solo POST)
     */
    public function create() {
        sec_session_start();

        // Solo usuarios autenticados pueden crear retos
        if (!is_logged_in()) {
            $_SESSION['notify'] = ['type' => 'error', 'msg' => 'Debes iniciar sesión para crear retos.'];
            header('Location: ' . ROUTE_URL . 'auth/login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ROUTE_URL . 'retos/index');
            exit();
        }

        // Verifica el token CSRF del formulario modal
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $_SESSION['notify'] = ['type' => 'error', 'msg' => 'Token de seguridad inválido.'];
            header('Location: ' . ROUTE_URL . 'retos/index');
            exit();
        }

        $userId      = get_user_id();
        // Sanea y valida cada campo del formulario
        $title       = trim(htmlspecialchars(strip_tags($_POST['title']       ?? ''), ENT_QUOTES, 'UTF-8'));
        $description = trim(htmlspecialchars(strip_tags($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8'));
        $type        = in_array($_POST['type']   ?? '', ['km', 'pasos']) ? $_POST['type']   : 'km';       // Tipo: km o pasos
        $period      = in_array($_POST['period'] ?? '', ['semanal', 'mensual']) ? $_POST['period'] : 'mensual'; // Duración
        $goal        = (float) ($_POST['goal']  ?? 0);
        $isPublic    = !empty($_POST['is_public']) ? 1 : 0;

        // Calcula la fecha de inicio (hoy) y fin según el período
        $today    = new DateTime();
        $startsAt = $today->format('Y-m-d');
        if ($period === 'semanal') {
            $endsAt = (clone $today)->modify('+7 days')->format('Y-m-d');  // Semanal: 7 días
        } else {
            $endsAt = (clone $today)->modify('+1 month')->format('Y-m-d'); // Mensual: 30 días aprox.
        }

        $errors = [];
        if ($title === '') $errors[] = 'El título es obligatorio.';
        if ($goal <= 0)    $errors[] = 'El objetivo debe ser mayor que 0.';

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: ' . ROUTE_URL . 'retos/index');
            exit();
        }

        // Inserta el reto en BD e inscribe al creador
        $this->retosModel->createChallenge($userId, $title, $description, $type, $period, $goal, $isPublic, $startsAt, $endsAt);
        $_SESSION['notify'] = ['type' => 'success', 'msg' => '¡Reto creado correctamente!'];
        header('Location: ' . ROUTE_URL . 'retos/index');
        exit();
    }

    /**
     * Método: join()
     * Inscribe al usuario actual en un reto público.
     * Si ya estaba inscrito, el modelo ignora la acción (INSERT IGNORE).
     * URL: /retos/join  (requiere sesión, solo POST)
     */
    public function join() {
        sec_session_start();

        if (!is_logged_in()) {
            $_SESSION['notify'] = ['type' => 'error', 'msg' => 'Debes iniciar sesión para unirte a retos.'];
            header('Location: ' . ROUTE_URL . 'auth/login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ROUTE_URL . 'retos/index');
            exit();
        }

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $_SESSION['notify'] = ['type' => 'error', 'msg' => 'Token de seguridad inválido.'];
            header('Location: ' . ROUTE_URL . 'retos/index');
            exit();
        }

        $challengeId = (int) ($_POST['challenge_id'] ?? 0);
        $userId      = get_user_id();

        if ($challengeId > 0) {
            $this->retosModel->joinChallenge($challengeId, $userId);
            $_SESSION['notify'] = ['type' => 'success', 'msg' => '¡Te has unido al reto!'];
        }

        header('Location: ' . ROUTE_URL . 'retos/index');
        exit();
    }

    /**
     * Método: leave()
     * Elimina al usuario de un reto.
     * El creador del reto no puede abandonarlo (para ello debe eliminarlo).
     * URL: /retos/leave  (requiere sesión, solo POST)
     */
    public function leave() {
        sec_session_start();

        if (!is_logged_in()) {
            header('Location: ' . ROUTE_URL . 'auth/login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ROUTE_URL . 'retos/index');
            exit();
        }

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $_SESSION['notify'] = ['type' => 'error', 'msg' => 'Token de seguridad inválido.'];
            header('Location: ' . ROUTE_URL . 'retos/index');
            exit();
        }

        $challengeId = (int) ($_POST['challenge_id'] ?? 0);
        $userId      = get_user_id();

        if ($challengeId > 0) {
            $result = $this->retosModel->leaveChallenge($challengeId, $userId);
            if (!$result) {
                // El modelo devuelve false si el usuario es el creador
                $_SESSION['notify'] = ['type' => 'error', 'msg' => 'El creador no puede abandonar su propio reto.'];
            } else {
                $_SESSION['notify'] = ['type' => 'success', 'msg' => 'Has abandonado el reto.'];
            }
        }

        header('Location: ' . ROUTE_URL . 'retos/index');
        exit();
    }

    /**
     * Método: updateProgress()
     * Actualiza el progreso (km o pasos acumulados) de un participante en un reto.
     * El valor se reemplaza (no se suma), así el usuario introduce su total actual.
     * URL: /retos/updateProgress  (requiere sesión, solo POST)
     */
    public function updateProgress() {
        sec_session_start();

        if (!is_logged_in()) {
            header('Location: ' . ROUTE_URL . 'auth/login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ROUTE_URL . 'retos/index');
            exit();
        }

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $_SESSION['notify'] = ['type' => 'error', 'msg' => 'Token de seguridad inválido.'];
            header('Location: ' . ROUTE_URL . 'retos/index');
            exit();
        }

        $challengeId = (int)   ($_POST['challenge_id'] ?? 0);
        $progress    = (float) ($_POST['progress']     ?? 0);
        $userId      = get_user_id();

        if ($challengeId > 0 && $progress >= 0) {
            $this->retosModel->updateProgress($challengeId, $userId, $progress);
            $_SESSION['notify'] = ['type' => 'success', 'msg' => '¡Progreso actualizado!'];
        }

        header('Location: ' . ROUTE_URL . 'retos/index');
        exit();
    }

    /**
     * Método: delete()
     * Elimina un reto completo junto con todos sus participantes.
     * Solo puede hacerlo el creador del reto (el modelo comprueba user_id).
     * URL: /retos/delete  (requiere sesión, solo POST)
     */
    public function delete() {
        sec_session_start();

        if (!is_logged_in()) {
            header('Location: ' . ROUTE_URL . 'auth/login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ROUTE_URL . 'retos/index');
            exit();
        }

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $_SESSION['notify'] = ['type' => 'error', 'msg' => 'Token de seguridad inválido.'];
            header('Location: ' . ROUTE_URL . 'retos/index');
            exit();
        }

        $challengeId = (int) ($_POST['challenge_id'] ?? 0);
        $userId      = get_user_id();

        if ($challengeId > 0) {
            // El modelo solo borra el reto si coincide el user_id del creador
            $this->retosModel->deleteChallenge($challengeId, $userId);
            $_SESSION['notify'] = ['type' => 'success', 'msg' => 'Reto eliminado.'];
        }

        header('Location: ' . ROUTE_URL . 'retos/index');
        exit();
    }
}

?>
