<?php

/**
 * ============================================================
 * CONTROLADOR DE AUTENTICACIÓN — controllers/auth.php
 * ============================================================
 * Gestiona todo el flujo de login, registro y cierre de sesión.
 * Métodos disponibles:
 *   login()            → Muestra el formulario de acceso
 *   validate_login()   → Valida credenciales y abre sesión
 *   register()         → Muestra el formulario de registro
 *   validate_register()→ Valida datos y crea la cuenta
 *   logout()           → Destruye la sesión y redirige
 * - Login, registro, logout. Valida formularios y contraseñas.
 * ============================================================
 */

// Carga el modelo de autenticación (consultas a la tabla 'users')
require_once MODEL_PATH . 'auth.model.php';

class Auth extends Controller {

    function __construct() {
        parent::__construct(); // Inicia $this->view
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
     * Valida los datos del formulario de acceso enviados por POST.
     * Comprueba el token CSRF, sanea los campos, verifica el
     * email en BD y compara la contraseña con bcrypt.
     * POST: email, pass, csrf_token
     */
    public function validate_login() {

        // Inicia o reanuda la sesión segura
        sec_session_start();

        // Protege contra ataques CSRF: el token del formulario debe coincidir con el de sesión
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            $_SESSION['errors']['csrf'] = 'Token de seguridad inválido';
            header("Location: " . ROUTE_URL . "auth/login");
            exit();
        }

        // Recogemos los datos del formulario saneados para evitar inyecciones
        $email = filter_var($_POST['email'] ??= '', FILTER_SANITIZE_EMAIL);
        $pass  = filter_var($_POST['pass']  ??= '', FILTER_SANITIZE_SPECIAL_CHARS);

        // Array donde acumulamos los errores de validación
        $errors = [];

        // Validación del campo email
        if (empty($email)) {
            $errors['email'] = 'El email es requerido';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El email no es válido';
        }

        // Validación del campo contraseña
        if (empty($pass)) {
            $errors['pass'] = 'La contraseña es requerida';
        } elseif (strlen($pass) < 6) {
            $errors['pass'] = 'La contraseña debe tener al menos 6 caracteres';
        }

        // Si hay errores de validación, volvemos al formulario con los mensajes
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['email'] = $email;
            header("Location: " . ROUTE_URL . "auth/login");
            exit();
        }

        // Consulta el usuario en la base de datos por su email
        $authModel = new authModel();
        $user = $authModel->get_user_email($email);

        // Comprueba si el usuario existe Y si la contraseña coincide con el hash bcrypt guardado
        if ($user && password_verify($pass, $user->password)) {

            // Limpia errores previos de sesión
            unset($_SESSION['errors']);
            unset($_SESSION['email']);

            // Guarda los datos del usuario en sesión para tenerlos disponibles en toda la app
            $_SESSION['user_id']     = $user->id;
            $_SESSION['user_name']   = $user->name;
            $_SESSION['user_email']  = $user->email;
            $_SESSION['user_avatar'] = $user->avatar_path ?? null;
            $_SESSION['login_time']  = time();

            // Muestra bienvenida y redirige a la página de inicio
            $_SESSION['notify'] = "Bienvenido " . $user->name;
            header("Location: " . ROUTE_URL . "main/index");
            exit();

        } else {

            // Credenciales incorrectas — mensaje genérico para no revelar si el email existe
            $_SESSION['errors'] = ['general' => 'Email o contraseña inválidos'];
            $_SESSION['email'] = $email;
            header("Location: " . ROUTE_URL . "auth/login");
            exit();
        }
    }

    /**
     * Método: register()
     * Muestra el formulario de registro de nueva cuenta.
     * Si el usuario ya tiene sesión iniciada, le redirige al inicio.
     * URL: /auth/register
     */
    public function register() {

        sec_session_start();

        // Si ya está autenticado no tiene sentido mostrar el registro
        if (is_logged_in()) {
            header("Location: " . ROUTE_URL . "main/index");
            exit();
        }

        // Genera el token CSRF la primera vez (protege el formulario contra envíos falsos)
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Inicializa los campos para que la vista no dé avisos de variable indefinida
        $this->view->name  = null;
        $this->view->email = null;

        // Recoge notificaciones flash (ej: redirigido desde otro sitio)
        if (isset($_SESSION['notify'])) {
            $this->view->notify = $_SESSION['notify'];
            unset($_SESSION['notify']);
        }

        // Si hubo errores en un intento previo, repobla el formulario con los valores escritos
        if (isset($_SESSION['errors'])) {
            $this->view->errors = $_SESSION['errors'];
            unset($_SESSION['errors']);

            $this->view->name  = $_SESSION['name']  ?? null;
            $this->view->email = $_SESSION['email'] ?? null;

            unset($_SESSION['name']);
            unset($_SESSION['email']);
        }

        $this->view->title = "Registro";
        $this->view->render('auth/register/index'); // views/auth/register/index.php
    }

    /**
     * Método: validate_register()
     * Procesa el formulario de registro enviado por POST.
     * Valida CSRF, sanea campos, comprueba si el email ya existe,
     * crea el hash bcrypt de la contraseña e inserta el usuario en BD.
     * POST: name, email, pass, pass_confirm, csrf_token
     */
    public function validate_register() {

        sec_session_start();

        // Validación del token CSRF
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            $_SESSION['errors']['csrf'] = 'Token de seguridad inválido';
            header("Location: " . ROUTE_URL . "auth/register");
            exit();
        }

        // Saneamiento de los campos recibidos
        $name  = trim(filter_var($_POST['name']  ?? '', FILTER_SANITIZE_SPECIAL_CHARS));
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $pass  = $_POST['pass']         ?? '';
        $pass2 = $_POST['pass_confirm'] ?? '';

        $errors = [];

        // Validaciones de cada campo
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

        // Las dos contraseñas deben coincidir
        if ($pass !== $pass2) {
            $errors['pass_confirm'] = 'Las contraseñas no coinciden';
        }

        // Comprueba en BD que el email no esté ya registrado
        $authModel = new authModel();
        if (!isset($errors['email']) && $authModel->email_exists($email)) {
            $errors['email'] = 'Ya existe una cuenta con ese email';
        }

        // Si hay errores, regresa al formulario conservando los valores escritos
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['name']   = $name;
            $_SESSION['email']  = $email;
            header("Location: " . ROUTE_URL . "auth/register");
            exit();
        }

        // Genera el hash bcrypt de la contraseña (nunca se guarda en texto plano)
        $passwordHash = password_hash($pass, PASSWORD_BCRYPT);
        $created = $authModel->create_user($name, $email, $passwordHash);

        if (!$created) {
            $_SESSION['errors'] = ['general' => 'No se pudo crear la cuenta'];
            $_SESSION['name']   = $name;
            $_SESSION['email']  = $email;
            header("Location: " . ROUTE_URL . "auth/register");
            exit();
        }

        // Cuenta creada — redirige al login con mensaje de éxito
        $_SESSION['notify'] = 'Cuenta creada correctamente. Ya puedes iniciar sesión.';
        header("Location: " . ROUTE_URL . "auth/login");
        exit();
    }

    /**
     * Método: logout()
     * Destruye completamente la sesión del usuario y redirige al login.
     * URL: /auth/logout
     */
    public function logout() {

        sec_session_start();

        // Elimina todas las variables de sesión y destruye el fichero de sesión en el servidor
        session_unset();
        session_destroy();

        // Redirige a la pantalla de inicio de sesión
        header("Location: " . ROUTE_URL . "auth/login");
        exit();
    }

}

?>
