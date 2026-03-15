<?php
$name = $this->name ?? '';
$email = $this->email ?? '';
$errors = $this->errors ?? [];
$notify = $this->notify ?? '';
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
                <p>Crea tu cuenta</p>
            </div>

            <?php if (!empty($notify)): ?>
                <div class="alert alert-success">
                    <span class="close-alert" onclick="this.parentElement.style.display='none';">&times;</span>
                    <?= htmlspecialchars($notify) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($errors['general']) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($errors['csrf'])): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($errors['csrf']) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= ROUTE_URL ?>auth/validate_register" class="login-form">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="form-group">
                    <label for="name">Nombre</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= htmlspecialchars($name) ?>"
                        required
                        class="form-control <?= isset($errors['name']) ? 'input-error' : '' ?>"
                        placeholder="Tu nombre"
                    >
                    <?php if (isset($errors['name'])): ?>
                        <span class="error-message"><?= htmlspecialchars($errors['name']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($email) ?>"
                        required
                        class="form-control <?= isset($errors['email']) ? 'input-error' : '' ?>"
                        placeholder="tu@email.com"
                    >
                    <?php if (isset($errors['email'])): ?>
                        <span class="error-message"><?= htmlspecialchars($errors['email']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="pass">Contraseña</label>
                    <input
                        type="password"
                        id="pass"
                        name="pass"
                        required
                        class="form-control <?= isset($errors['pass']) ? 'input-error' : '' ?>"
                        placeholder="Mínimo 6 caracteres"
                    >
                    <?php if (isset($errors['pass'])): ?>
                        <span class="error-message"><?= htmlspecialchars($errors['pass']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="pass_confirm">Confirmar contraseña</label>
                    <input
                        type="password"
                        id="pass_confirm"
                        name="pass_confirm"
                        required
                        class="form-control <?= isset($errors['pass_confirm']) ? 'input-error' : '' ?>"
                        placeholder="Repite tu contraseña"
                    >
                    <?php if (isset($errors['pass_confirm'])): ?>
                        <span class="error-message"><?= htmlspecialchars($errors['pass_confirm']) ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-login">Crear Cuenta</button>

                <div class="login-links">
                    <a href="<?= ROUTE_URL ?>auth/login">Ya tengo cuenta, iniciar sesión</a>
                </div>
            </form>

        </div>
    </div>

</body>
</html>
