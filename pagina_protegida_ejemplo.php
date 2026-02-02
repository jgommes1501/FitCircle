<?php

/**
 * Ejemplo de página protegida
 * Este archivo muestra cómo proteger una página requiriendo autenticación
 * 
 * Uso: Copia este patrón en cualquier controlador que quieras proteger
 */

// Requerir la configuración y las librerías
require_once dirname(__FILE__) . '/config/config.php';
require_once LIB_PATH . 'database.php';
require_once LIB_PATH . 'model.php';
require_once LIB_PATH . 'view.php';
require_once LIB_PATH . 'controller.php';
require_once 'functions/session_seg.php';

// ⭐ LÍNEA IMPORTANTE: Verifica si el usuario está autenticado
// Si no está autenticado, redirige al login
require_login();

// A partir de aquí el código es seguro: el usuario está autenticado

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página Protegida - FitCircle</title>
    <link rel="stylesheet" href="<?= URL ?>paginas/css/index.css">
    <style>
        .profile-info {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            margin: 1rem 0;
        }

        .profile-info h2 {
            color: #c62828;
            margin-bottom: 1rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .label {
            font-weight: 600;
            color: #6b6b6b;
        }

        .value {
            color: #2e2e2e;
        }
    </style>
</head>
<body>

    <header>
        <h1>FitCircle</h1>
        <nav class="top-nav">
            <a href="<?= URL ?>main/index">Inicio</a>
            <a href="<?= URL ?>paginas/ruta.html">Rutas</a>
            <a href="<?= URL ?>paginas/retos.html">Retos</a>
            <span style="color: white; padding: 0.4rem 0.8rem;">
                👤 <?= htmlspecialchars(get_user_name()) ?>
            </span>
            <a href="<?= URL ?>auth/logout" class="logout-btn">Salir</a>
        </nav>
    </header>

    <main class="container">
        <div class="profile-info">
            <h2>🔐 Información del Usuario</h2>
            
            <div class="info-row">
                <span class="label">ID de Usuario:</span>
                <span class="value"><?= get_user_id() ?></span>
            </div>

            <div class="info-row">
                <span class="label">Nombre:</span>
                <span class="value"><?= htmlspecialchars(get_user_name()) ?></span>
            </div>

            <div class="info-row">
                <span class="label">Email:</span>
                <span class="value"><?= htmlspecialchars(get_user_email()) ?></span>
            </div>

            <div class="info-row">
                <span class="label">Estado:</span>
                <span class="value" style="color: #388e3c; font-weight: 600;">✓ Autenticado</span>
            </div>
        </div>

        <div class="profile-info">
            <h2>📖 Cómo Proteger una Página</h2>
            
            <p style="margin-bottom: 1rem;">
                Para proteger una página y asegurarte de que solo usuarios autenticados puedan acceder:
            </p>

            <pre style="background: #f5f5f5; padding: 1rem; border-radius: 6px; overflow-x: auto;"><code>&lt;?php
// 1. Requerir la configuración
require_once 'config/config.php';
require_once LIB_PATH . 'database.php';
require_once LIB_PATH . 'model.php';
require_once LIB_PATH . 'view.php';
require_once LIB_PATH . 'controller.php';
require_once 'functions/session_seg.php';

// 2. ⭐ VERIFICAR AUTENTICACIÓN
require_login();  // Redirige al login si no está autenticado

// 3. Ahora puedes usar el código de forma segura
echo "Hola " . get_user_name();
?&gt;</code></pre>
        </div>

        <div class="profile-info">
            <h2>🛠️ Funciones Disponibles</h2>
            
            <p><strong>sec_session_start()</strong></p>
            <p style="color: #666; margin-bottom: 1rem;">
                Inicia una sesión segura con regeneración de ID y protección contra fijación de sesión.
            </p>

            <p><strong>is_logged_in()</strong></p>
            <p style="color: #666; margin-bottom: 1rem;">
                Retorna true si el usuario está autenticado, false si no.
            </p>

            <p><strong>get_user_id()</strong></p>
            <p style="color: #666; margin-bottom: 1rem;">
                Retorna el ID del usuario autenticado.
            </p>

            <p><strong>get_user_name()</strong></p>
            <p style="color: #666; margin-bottom: 1rem;">
                Retorna el nombre del usuario autenticado.
            </p>

            <p><strong>get_user_email()</strong></p>
            <p style="color: #666; margin-bottom: 1rem;">
                Retorna el email del usuario autenticado.
            </p>

            <p><strong>require_login()</strong></p>
            <p style="color: #666; margin-bottom: 1rem;">
                Redirige al login si el usuario no está autenticado. Detiene la ejecución.
            </p>
        </div>

    </main>

</body>
</html>
