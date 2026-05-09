<?php

require_once MODEL_PATH . 'perfil.model.php';

class Perfil extends Controller {

    private $perfilModel;

    function __construct() {
        parent::__construct();
        $this->perfilModel = new perfilModel();
        $this->perfilModel->ensureProfileSchema();
    }

    function index() {
        sec_session_start();

        if (!is_logged_in()) {
            header("Location: " . ROUTE_URL . "auth/login");
            exit();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $userId = get_user_id();
        $profile = $this->perfilModel->getUserProfile($userId);

        if (!$profile) {
            session_unset();
            session_destroy();
            header("Location: " . ROUTE_URL . "auth/login");
            exit();
        }

        $stats = $this->perfilModel->getUserStats($userId);
        $recentRoutes = $this->perfilModel->getRecentRoutes($userId);

        $this->view->title = "Mi Perfil";
        $this->view->csrf_token = $_SESSION['csrf_token'];
        $this->view->profile = $profile;
        $this->view->stats = $stats;
        $this->view->recent_routes = $recentRoutes;

        if (isset($_SESSION['notify'])) {
            $this->view->notify = $_SESSION['notify'];
            unset($_SESSION['notify']);
        }

        if (isset($_SESSION['errors'])) {
            $this->view->errors = $_SESSION['errors'];
            unset($_SESSION['errors']);
        }

        $this->view->render('perfil/index');
    }

    public function update() {
        sec_session_start();

        if (!is_logged_in()) {
            header('Location: ' . ROUTE_URL . 'auth/login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ROUTE_URL . 'perfil/index');
            exit();
        }

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

        $avatarPath = null;
        if (isset($_FILES['avatar']) && is_array($_FILES['avatar']) && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'No se pudo subir la imagen de perfil.';
            } elseif (($_FILES['avatar']['size'] ?? 0) > 2 * 1024 * 1024) {
                $errors[] = 'La imagen no puede superar los 2 MB.';
            } else {
                $tmpFile = $_FILES['avatar']['tmp_name'];
                $mime = mime_content_type($tmpFile);
                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    'image/gif' => 'gif'
                ];

                if (!isset($allowed[$mime])) {
                    $errors[] = 'Formato de imagen no válido (usa JPG, PNG, WEBP o GIF).';
                } else {
                    $ext = $allowed[$mime];
                    $uploadDir = ROOT_PATH . '/uploads/avatars';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $fileName = 'avatar_' . get_user_id() . '_' . time() . '.' . $ext;
                    $destPath = $uploadDir . '/' . $fileName;

                    if (!move_uploaded_file($tmpFile, $destPath)) {
                        $errors[] = 'No se pudo guardar la imagen en el servidor.';
                    } else {
                        $avatarPath = 'uploads/avatars/' . $fileName;
                    }
                }
            }
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: ' . ROUTE_URL . 'perfil/index');
            exit();
        }

        $this->perfilModel->updateProfile(get_user_id(), mb_substr($name, 0, 100), $avatarPath);
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
