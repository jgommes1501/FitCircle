<?php

// Cargar el modelo
require_once MODEL_PATH . 'auth.model.php';

class Auth extends Controller {

    function __construct() {
        parent::__construct();
    }

    /**
     * Método: login()
     * Descripción: Muestra el formulario de login 
     * URL: /auth/login
     */
    function login() {

        // iniciar o continuar sesión
        sec_session_start();

        // Si ya está autenticado, llevar al inicio
        if (is_logged_in()) {
            header("Location: " . ROUTE_URL . "main/index");
            exit();
        }

        // Crear token CSRF una sola vez por sesión para evitar invalidación accidental
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Inicializo los campos del formulario
        $this->view->email = null;

        // Comprobar si existe alguna notificación o mensaje
        if (isset($_SESSION['notify'])) {
            $this->view->notify = $_SESSION['notify'];
            unset($_SESSION['notify']);
        }

        // Verificar si existe algún error
        if (isset($_SESSION['errors'])) {

            // detalles del error
            $this->view->errors = $_SESSION['errors'];
            unset($_SESSION['errors']);

            // Creo la propiedad error
            $this->view->error = "Error de autenticación, revise el formulario";

            // Retroalimento los detalles del formulario
            $this->view->email = $_SESSION['email'];

            unset($_SESSION['email']);
        }

        // Creo la propiedad title para la vista
        $this->view->title = "Iniciar Sesión";

        // Llama a la vista para renderizar la página
        $this->view->render('auth/login/index');
    }

    /**
     * Método: validate_login()
     * Descripción: Valida los datos de autenticación (email, password)
     * POST:
     *     - email
     *     - pass
     *     - csrf_token
     */
    public function validate_login() {

        // inicio o continúo sesión
        sec_session_start();

        // Verificar el token CSRF
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            $_SESSION['errors']['csrf'] = 'Token de seguridad inválido';
            header("Location: " . ROUTE_URL . "auth/login");
            exit();
        }

        // Recogemos los datos del formulario saneados
        $email = filter_var($_POST['email'] ??= '', FILTER_SANITIZE_EMAIL);
        $pass = filter_var($_POST['pass'] ??= '', FILTER_SANITIZE_SPECIAL_CHARS);

        // Array de errores
        $errors = [];

        // Validar email
        if (empty($email)) {
            $errors['email'] = 'El email es requerido';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El email no es válido';
        }

        // Validar contraseña
        if (empty($pass)) {
            $errors['pass'] = 'La contraseña es requerida';
        } elseif (strlen($pass) < 6) {
            $errors['pass'] = 'La contraseña debe tener al menos 6 caracteres';
        }

        // Si hay errores, volvemos al formulario
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['email'] = $email;
            header("Location: " . ROUTE_URL . "auth/login");
            exit();
        }

        // Crear instancia del modelo
        $authModel = new authModel();

        // Obtener usuario por email
        $user = $authModel->get_user_email($email);

        // Si el usuario existe y la contraseña es válida
        if ($user && password_verify($pass, $user->password)) {

            // Limpiar sesión
            unset($_SESSION['errors']);
            unset($_SESSION['email']);

            // Inicializar sesión segura
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_name'] = $user->name;
            $_SESSION['user_email'] = $user->email;
            $_SESSION['login_time'] = time();

            // Redirigir a página de inicio
            $_SESSION['notify'] = "Bienvenido " . $user->name;
            header("Location: " . ROUTE_URL . "main/index");
            exit();

        } else {

            // Usuario o contraseña inválidos
            $_SESSION['errors'] = ['general' => 'Email o contraseña inválidos'];
            $_SESSION['email'] = $email;
            header("Location: " . ROUTE_URL . "auth/login");
            exit();
        }
    }

    public function register() {

        sec_session_start();

        if (is_logged_in()) {
            header("Location: " . ROUTE_URL . "main/index");
            exit();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->view->name = null;
        $this->view->email = null;

        if (isset($_SESSION['notify'])) {
            $this->view->notify = $_SESSION['notify'];
            unset($_SESSION['notify']);
        }

        if (isset($_SESSION['errors'])) {
            $this->view->errors = $_SESSION['errors'];
            unset($_SESSION['errors']);

            $this->view->name = $_SESSION['name'] ?? null;
            $this->view->email = $_SESSION['email'] ?? null;

            unset($_SESSION['name']);
            unset($_SESSION['email']);
        }

        $this->view->title = "Registro";
        $this->view->render('auth/register/index');
    }

    public function validate_register() {

        sec_session_start();

        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            $_SESSION['errors']['csrf'] = 'Token de seguridad inválido';
            header("Location: " . ROUTE_URL . "auth/register");
            exit();
        }

        $name = trim(filter_var($_POST['name'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS));
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $pass = $_POST['pass'] ?? '';
        $pass2 = $_POST['pass_confirm'] ?? '';

        $errors = [];

        if ($name === '') {
            $errors['name'] = 'El nombre es requerido';
        } elseif (mb_strlen($name) < 2) {
            $errors['name'] = 'El nombre debe tener al menos 2 caracteres';
        }

        if (empty($email)) {
            $errors['email'] = 'El email es requerido';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El email no es válido';
        }

        if (empty($pass)) {
            $errors['pass'] = 'La contraseña es requerida';
        } elseif (strlen($pass) < 6) {
            $errors['pass'] = 'La contraseña debe tener al menos 6 caracteres';
        }

        if ($pass !== $pass2) {
            $errors['pass_confirm'] = 'Las contraseñas no coinciden';
        }

        $authModel = new authModel();
        if (!isset($errors['email']) && $authModel->email_exists($email)) {
            $errors['email'] = 'Ya existe una cuenta con ese email';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            header("Location: " . ROUTE_URL . "auth/register");
            exit();
        }

        $passwordHash = password_hash($pass, PASSWORD_BCRYPT);
        $created = $authModel->create_user($name, $email, $passwordHash);

        if (!$created) {
            $_SESSION['errors'] = ['general' => 'No se pudo crear la cuenta'];
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            header("Location: " . ROUTE_URL . "auth/register");
            exit();
        }

        $_SESSION['notify'] = 'Cuenta creada correctamente. Ya puedes iniciar sesión.';
        header("Location: " . ROUTE_URL . "auth/login");
        exit();
    }

    /**
     * Método: logout()
     * Descripción: Cierra la sesión del usuario
     */
    public function logout() {

        sec_session_start();

        // Destruir la sesión
        session_unset();
        session_destroy();

        // Redirigir a la página de login
        header("Location: " . ROUTE_URL . "auth/login");
        exit();
    }

}

?>
