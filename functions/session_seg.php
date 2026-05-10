<?php

/**
 * ============================================================
 * SESIÓN SEGURA — functions/session_seg.php
 * ============================================================
 * Define funciones de gestión de sesión y autenticación.
 * Cargado automáticamente desde index.php (front controller).
 *
 * Funciones:
 *   sec_session_start() → Inicia sesión con cookies seguras y
 *                          previene ataques de fijación de sesión
 *   is_logged_in()      → Comprueba si hay sesión activa
 *   get_user_id()       → Devuelve el ID del usuario en sesión
 *   get_user_name()     → Devuelve el nombre del usuario en sesión
 *   get_user_email()    → Devuelve el email del usuario en sesión
 *   require_login()     → Redirige al login si no hay sesión activa
 * ============================================================
 */

/**
 * Inicia o continúa una sesión PHP de forma segura.
 * Previene el ataque de fijación de sesión (session fixation)
 * regenerando el ID en la primera llamada.
 * La cookie de sesión se configura con httponly y samesite=Lax.
 */
function sec_session_start() {
    $secure     = false;  // Cambiar a true si usas HTTPS
    $httponly   = true;   // La cookie no es accesible desde JavaScript
    $samesite   = 'Lax';  // Evita el envío en peticiones de terceros
    $host       = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), PHP_URL_HOST);
    // Dominio vacío evita conflictos en hostings compartidos con subdominios
    $cookieDomain = '';
    // Calcula el path de la cookie según el directorio del script actual
    $scriptDir  = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $cookiePath = rtrim($scriptDir, '/');
    $cookiePath = ($cookiePath === '') ? '/' : $cookiePath . '/';

    if (PHP_VERSION_ID >= 70300) {
        // PHP 7.3+: soporta samesite como parámetro del array
        session_set_cookie_params([
            'lifetime' => 0,       // La cookie dura solo la sesión del navegador
            'path'     => $cookiePath,
            'domain'   => $cookieDomain,
            'secure'   => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite
        ]);
    } else {
        // PHP < 7.3: samesite se añade como cabecera extra
        session_set_cookie_params(0, $cookiePath, $cookieDomain, $secure, $httponly);
        header('SameSite=Strict', false);
    }

    session_start();

    // Regenera el ID de sesión en la primera llamada para prevenir fijación
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }
}

/**
 * Comprueba si hay un usuario autenticado en la sesión actual.
 * Devuelve true si user_id está definido y no está vacío.
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Devuelve el ID del usuario actualmente en sesión.
 * Devuelve null si no hay sesión iniciada.
 */
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Devuelve el nombre del usuario actualmente en sesión.
 * Devuelve null si no hay sesión iniciada.
 */
function get_user_name() {
    return $_SESSION['user_name'] ?? null;
}

/**
 * Devuelve el email del usuario actualmente en sesión.
 * Devuelve null si no hay sesión iniciada.
 */
function get_user_email() {
    return $_SESSION['user_email'] ?? null;
}

/**
 * Redirige al login si el usuario no está autenticado.
 * Llama a sec_session_start() para asegurarse de que la sesión está activa
 * antes de comprobar la autenticación.
 */
function require_login() {
    sec_session_start();
    if (!is_logged_in()) {
        header("Location: " . ROUTE_URL . "auth/login");
        exit();
    }
}

?>
