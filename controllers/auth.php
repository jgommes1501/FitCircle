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

        // Crear un token CSRF para los formularios
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // Inicializo los campos del formulario
        $this->view->email = null;
        $this->view->pass = null;

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
            $this->view->pass = $_SESSION['pass'];

            unset($_SESSION['email']);
            unset($_SESSION['pass']);
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
            header("Location: " . URL . "auth/login");
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
            $_SESSION['pass'] = $pass;
            header("Location: " . URL . "auth/login");
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
            unset($_SESSION['pass']);

            // Inicializar sesión segura
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_name'] = $user->name;
            $_SESSION['user_email'] = $user->email;
            $_SESSION['login_time'] = time();

            // Redirigir a página de inicio
            $_SESSION['notify'] = "Bienvenido " . $user->name;
            header("Location: " . URL . "main/index");
            exit();

        } else {

            // Usuario o contraseña inválidos
            $_SESSION['errors'] = ['general' => 'Email o contraseña inválidos'];
            $_SESSION['email'] = $email;
            $_SESSION['pass'] = $pass;
            header("Location: " . URL . "auth/login");
            exit();
        }
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
        header("Location: " . URL . "auth/login");
        exit();
    }

}

?>
