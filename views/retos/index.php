<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->title ?> - FitCircle</title>
    <link rel="stylesheet" href="<?= URL ?>paginas/css/retos.css">
</head>
<body>

    <header>
        <h1>FitCircle</h1>
        <nav class="top-nav">
            <a href="<?= ROUTE_URL ?>main/index">Inicio</a>
            <a href="<?= ROUTE_URL ?>ruta/index">Rutas</a>
            <a href="<?= ROUTE_URL ?>retos/index" class="active">Retos</a>
            <a href="<?= ROUTE_URL ?>perfil/index">Perfil</a>
            <a href="<?= ROUTE_URL ?>auth/logout" class="logout-btn">Salir</a>
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

    <nav class="bottom-nav">
        <a href="<?= ROUTE_URL ?>main/index">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7"/><path d="M9 22V12h6v10"/></svg>
            Inicio
        </a>
        <a href="<?= ROUTE_URL ?>ruta/index">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z"/></svg>
            Rutas
        </a>
        <a href="<?= ROUTE_URL ?>retos/index" class="active">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            Retos
        </a>
        <a href="<?= ROUTE_URL ?>perfil/index">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>
            Perfil
        </a>
    </nav>

    <footer>
        <p>© 2025 · FitCircle</p>
    </footer>

</body>
</html>