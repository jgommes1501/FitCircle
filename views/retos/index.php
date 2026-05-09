<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->title ?> - FitCircle</title>
    <link rel="stylesheet" href="<?= URL ?>paginas/css/retos.css">
    <link rel="stylesheet" href="<?= URL ?>paginas/css/theme.css?v=2">
</head>
<body>

    <header>
        <h1 class="brand-logo-wrap">
            <a href="<?= ROUTE_URL ?>main/index" class="brand-logo-link" aria-label="FitCircle inicio">
                <img src="<?= URL ?>paginas/img/FitCircle.png" alt="FitCircle" class="brand-logo">
            </a>
        </h1>
        <nav class="top-nav">
            <a href="<?= ROUTE_URL ?>main/index">Inicio</a>
            <a href="<?= ROUTE_URL ?>ruta/index">Rutas</a>
            <a href="<?= ROUTE_URL ?>retos/index" class="active">Retos</a>
            <a href="<?= ROUTE_URL ?>perfil/index" class="profile-icon" aria-label="Perfil">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="8" r="4" stroke="white" stroke-width="2"/>
                    <path d="M4 20c0-4 4-6 8-6s8 2 8 6" stroke="white" stroke-width="2"/>
                </svg>
            </a>
            <?php if ($this->logged_in): ?>
                <a href="<?= ROUTE_URL ?>auth/logout" class="logout-btn">Salir</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="container">

        <div class="challenge">
            <h3>Reto semanal</h3>
            <p>Corre 20 km esta semana</p>
            <div class="progress">
                <div class="progress-bar" style="width: 45%"></div>
            </div>
        </div>

        <div class="challenge">
            <h3>Reto mensual</h3>
            <p>Acumula 80 km en 30 días</p>
            <div class="progress">
                <div class="progress-bar" style="width: 70%"></div>
            </div>
        </div>

        <div class="challenge completed">
            <h3>Reto comunitario</h3>
            <p>Camina 50 km con la comunidad</p>
            <div class="progress">
                <div class="progress-bar" style="width: 100%"></div>
            </div>
        </div>

    </main>

    <footer>
        <p>© 2026 · FitCircle</p>
    </footer>

    <script src="<?= URL ?>paginas/js/theme-mode.js?v=2"></script>

</body>
</html>