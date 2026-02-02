<?php

/**
 * Iniciar o continuar sesión segura
 * Previene ataques de fijación de sesión
 */
function sec_session_start() {
    $secure = false; // Cambiar a true si usas HTTPS
    $httponly = true;
    $samesite = 'Strict';
    
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite
        ]);
    } else {
        session_set_cookie_params(0, '/', $_SERVER['HTTP_HOST'], $secure, $httponly);
        header('SameSite=Strict', false);
    }
    
    session_start();
    
    // Regenerar ID de sesión para prevenir fijación
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }
}

/**
 * Verificar si el usuario está autenticado
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Obtener ID del usuario actual
 */
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Obtener nombre del usuario actual
 */
function get_user_name() {
    return $_SESSION['user_name'] ?? null;
}

/**
 * Obtener email del usuario actual
 */
function get_user_email() {
    return $_SESSION['user_email'] ?? null;
}

/**
 * Redirigir a login si no está autenticado
 */
function require_login() {
    sec_session_start();
    if (!is_logged_in()) {
        header("Location: " . URL . "auth/login");
        exit();
    }
}

?>
