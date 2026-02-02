<?php
$email = $this->email ?? '';
$pass = $this->pass ?? '';
$errors = $this->errors ?? [];
$notify = $this->notify ?? '';
$error = $this->error ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->title ?> - FitCircle</title>
    <link rel="stylesheet" href="<?= URL ?>paginas/css/login.css">
</head>
<body class="login-page">

    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>FitCircle</h1>
                <p>Inicia sesión para continuar</p>
            </div>

            <?php if (!empty($notify)): ?>
                <div class="alert alert-success">
                    <span class="close-alert" onclick="this.parentElement.style.display='none';">&times;</span>
                    <?= htmlspecialchars($notify) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <span class="close-alert" onclick="this.parentElement.style.display='none';">&times;</span>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= URL ?>auth/validate_login" class="login-form">
                
                <!-- Token CSRF -->
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <!-- Email -->
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?= htmlspecialchars($email) ?>" 
                        required 
                        autofocus
                        class="form-control <?= isset($errors['email']) ? 'input-error' : '' ?>"
                        placeholder="tu@email.com"
                    >
                    <?php if (isset($errors['email'])): ?>
                        <span class="error-message"><?= htmlspecialchars($errors['email']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="pass" 
                        value="<?= htmlspecialchars($pass) ?>" 
                        required
                        class="form-control <?= isset($errors['pass']) ? 'input-error' : '' ?>"
                        placeholder="••••••••"
                    >
                    <?php if (isset($errors['pass'])): ?>
                        <span class="error-message"><?= htmlspecialchars($errors['pass']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Errores generales -->
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($errors['general']) ?>
                    </div>
                <?php endif; ?>

                <!-- Checkbox Recordar -->
                <div class="form-group checkbox">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Recuérdame en este dispositivo</label>
                </div>

                <!-- Botón Submit -->
                <button type="submit" class="btn-login">Iniciar Sesión</button>

                <!-- Enlaces adicionales -->
                <div class="login-links">
                    <a href="#" class="forgot-password">¿Olvidaste tu contraseña?</a>
                </div>

            </form>

        </div>
    </div>

</body>
</html>
