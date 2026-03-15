<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->title ?> - FitCircle</title>
    <link rel="stylesheet" href="<?= URL ?>paginas/css/ruta.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
</head>
<body>

    <header>
        <h1>FitCircle</h1>
        <nav class="top-nav">
            <a href="<?= ROUTE_URL ?>main/index">Inicio</a>
            <a href="<?= ROUTE_URL ?>ruta/index" class="active">Rutas</a>
            <a href="<?= ROUTE_URL ?>retos/index">Retos</a>
            <a href="<?= ROUTE_URL ?>perfil/index">Perfil</a>
            <a href="<?= ROUTE_URL ?>auth/logout" class="logout-btn">Salir</a>
        </nav>
    </header>

    <main class="container">

        <button class="start-route">▶ Iniciar nueva ruta</button>

        <section class="route-stats" aria-label="Estadísticas de ruta en tiempo real">
            <div class="route-stat">
                <span class="label">Pasos</span>
                <strong id="route-steps">0</strong>
            </div>
            <div class="route-stat">
                <span class="label">Distancia</span>
                <strong id="route-distance">0.00 km</strong>
            </div>
            <div class="route-stat">
                <span class="label">Tiempo</span>
                <strong id="route-time">00:00</strong>
            </div>
        </section>

        <section class="live-location">
            <h2>Mapa GPS en tiempo real</h2>
            <p id="live-location-status" class="live-location-status">Esperando permiso de ubicación…</p>
            <div id="live-location-map" class="live-location-map" aria-label="Mapa GPS de rutas"></div>
            <p class="gps-help">Si no carga, revisa que estés en HTTPS y con permiso de ubicación activado.</p>
        </section>

        <div class="card">
            <h3>Parque Central</h3>
            <p>5,4 km · Running · Dificultad media</p>
        </div>

        <div class="card">
            <h3>Río Verde</h3>
            <p>12 km · Ciclismo · Dificultad alta</p>
        </div>

        <div class="card">
            <h3>Paseo Urbano</h3>
            <p>3 km · Caminata · Dificultad baja</p>
        </div>

    </main>

    <nav class="bottom-nav">
        <a href="<?= ROUTE_URL ?>main/index">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7"/><path d="M9 22V12h6v10"/></svg>
            Inicio
        </a>
        <a href="<?= ROUTE_URL ?>ruta/index" class="active">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z"/></svg>
            Rutas
        </a>
        <a href="<?= ROUTE_URL ?>retos/index">
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

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="<?= URL ?>paginas/js/ruta-gps.js"></script>

</body>
</html>