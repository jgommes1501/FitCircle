<?php

/**
 * ============================================================
 * CONTROLADOR DE PERFIL — controllers/perfil.php
 * ============================================================
 * Gestiona la página de perfil del usuario autenticado.
 * Requiere sesión iniciada en todos sus métodos.
 * Métodos:
 *   index()  → Muestra el perfil, estadísticas y rutas recientes
 *   update() → Procesa el formulario de edición de perfil (nombre + avatar)
 * ============================================================
 */

// Carga el modelo de perfil (consultas a 'users' y 'routes')
require_once MODEL_PATH . 'perfil.model.php';

class Perfil extends Controller {

    // Instancia del modelo de perfil
    private $perfilModel;

    function __construct() {
        parent::__construct();
        $this->perfilModel = new perfilModel();
        // Crea las tablas necesarias si no existen (routes, route_likes, columna avatar_path)
        $this->perfilModel->ensureProfileSchema();
    }

    /**
     * Método: index()
     * Muestra la página de perfil del usuario:
     *   - Datos personales (nombre, email, avatar)
     *   - Estadísticas globales (rutas totales, km, pasos, calorías)
     *   - Últimas 5 rutas registradas
     * URL: /perfil/index  (requiere sesión)
     */
    function index() {
        sec_session_start();

        // Redirige al login si no hay sesión activa
        if (!is_logged_in()) {
            header("Location: " . ROUTE_URL . "auth/login");
            exit();
        }

        // Genera token CSRF para el formulario de edición de perfil
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $userId = get_user_id();

        // Obtiene los datos del usuario desde BD
        $profile = $this->perfilModel->getUserProfile($userId);

        // Si el usuario no existe en BD (cuenta borrada), cierra sesión y redirige
        if (!$profile) {
            session_unset();
            session_destroy();
            header("Location: " . ROUTE_URL . "auth/login");
            exit();
        }

        // Obtiene el resumen de actividad (número de rutas, km totales, pasos, calorías)
        $stats = $this->perfilModel->getUserStats($userId);

        // Obtiene las últimas 5 rutas del usuario para el historial reciente
        $recentRoutes = $this->perfilModel->getRecentRoutes($userId);

        // Pasa todos los datos a la vista
        $this->view->title        = "Mi Perfil";
        $this->view->csrf_token   = $_SESSION['csrf_token'];
        $this->view->profile      = $profile;
        $this->view->stats        = $stats;
        $this->view->recent_routes = $recentRoutes;

        // Recoge notificaciones y errores flash
        if (isset($_SESSION['notify'])) {
            $this->view->notify = $_SESSION['notify'];
            unset($_SESSION['notify']);
        }
        if (isset($_SESSION['errors'])) {
            $this->view->errors = $_SESSION['errors'];
            unset($_SESSION['errors']);
        }

        $this->view->render('perfil/index'); // views/perfil/index.php
    }

    /**
     * Método: update()
     * Procesa el formulario de edición del perfil (POST).
     * Permite cambiar el nombre y/o subir una nueva foto de perfil.
     * El avatar se valida por tipo MIME real (no por extensión),
     * se renombra con un nombre único y se guarda en /uploads/avatars/.
     * URL: /perfil/update  (requiere sesión, solo POST)
     */
    public function update() {
        sec_session_start();

        // Solo usuarios autenticados pueden editar su perfil
        if (!is_logged_in()) {
            header('Location: ' . ROUTE_URL . 'auth/login');
            exit();
        }

        // Solo acepta peticiones POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ROUTE_URL . 'perfil/index');
            exit();
        }

        // Comprueba el token CSRF del formulario
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            $_SESSION['errors'] = ['Token de seguridad inválido'];
            header('Location: ' . ROUTE_URL . 'perfil/index');
            exit();
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $errors = [];

        if ($name === '' || mb_strlen($name) < 2) {
            $errors[] = 'El nombre debe tener al menos 2 caracteres.';
        }

        // --- Procesamiento del avatar (foto de perfil) ---
        $avatarPath = null;
        if (isset($_FILES['avatar']) && is_array($_FILES['avatar']) && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'No se pudo subir la imagen de perfil.';
            } elseif (($_FILES['avatar']['size'] ?? 0) > 2 * 1024 * 1024) {
                // Tamaño máximo: 2 MB
                $errors[] = 'La imagen no puede superar los 2 MB.';
            } else {
                // Verifica el tipo real del archivo por MIME (no por extensión, más seguro)
                $tmpFile = $_FILES['avatar']['tmp_name'];
                $mime = mime_content_type($tmpFile);
                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                    'image/gif'  => 'gif'
                ];

                if (!isset($allowed[$mime])) {
                    $errors[] = 'Formato de imagen no válido (usa JPG, PNG, WEBP o GIF).';
                } else {
                    $ext = $allowed[$mime];
                    // Carpeta de destino de avatares
                    $uploadDir = ROOT_PATH . '/uploads/avatars';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    // Nombre único: avatar_{userId}_{timestamp}.{ext}
                    $fileName = 'avatar_' . get_user_id() . '_' . time() . '.' . $ext;
                    $destPath = $uploadDir . '/' . $fileName;

                    // Mueve el archivo temporal al directorio definitivo
                    if (!move_uploaded_file($tmpFile, $destPath)) {
                        $errors[] = 'No se pudo guardar la imagen en el servidor.';
                    } else {
                        $avatarPath = 'uploads/avatars/' . $fileName;
                    }
                }
            }
        }

        // Si hay errores, vuelve al perfil con los mensajes
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: ' . ROUTE_URL . 'perfil/index');
            exit();
        }

        // Actualiza en BD el nombre y, si se subió imagen, la ruta del avatar
        $this->perfilModel->updateProfile(get_user_id(), mb_substr($name, 0, 100), $avatarPath);

        // Sincroniza los datos de sesión con los nuevos valores
        $_SESSION['user_name'] = mb_substr($name, 0, 100);
        if ($avatarPath !== null) {
            $_SESSION['user_avatar'] = $avatarPath;
        }

        $_SESSION['notify'] = 'Perfil actualizado correctamente.';
        header('Location: ' . ROUTE_URL . 'perfil/index');
        exit();
    }

}

?>
