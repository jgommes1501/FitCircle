<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->title ?></title>
    <link rel="stylesheet" href="<?= URL ?>paginas/css/index.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
</head>
<body>

    <header>
        <h1>FitCircle</h1>
        <nav class="top-nav">
            <a href="<?= URL ?>main/index">Inicio</a>
            <a href="<?= URL ?>paginas/ruta.html">Rutas</a>
            <a href="<?= URL ?>paginas/retos.html">Retos</a>
            <a href="<?= URL ?>auth/logout" class="logout-btn">Salir</a>
        </nav>
    </header>

    <main class="container">
        <?php if (isset($this->notify)): ?>
            <div style="background: #4caf50; color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                <?= htmlspecialchars($this->notify) ?>
            </div>
        <?php endif; ?>

        <section class="live-location">
            <h2>Tu ubicación en tiempo real</h2>
            <p id="live-location-status" class="live-location-status">Esperando permiso de ubicación…</p>
            <div id="live-location-map" class="live-location-map" aria-label="Mapa de ubicación en tiempo real"></div>
        </section>

        <button class="start-route">▶ Iniciar ruta</button>

        <!-- Monitorización física -->
        <section class="cards">
            <div class="card">
                <h3>Pasos</h3>
                <p class="metric">8.420</p>
            </div>
            <div class="card">
                <h3>Calorías</h3>
                <p class="metric">560 kcal</p>
            </div>
            <div class="card">
                <h3>Distancia</h3>
                <p class="metric">6,2 km</p>
            </div>
            <div class="card">
                <h3>Pulso</h3>
                <p class="metric">72 bpm</p>
            </div>
        </section>

        <!-- Rutas destacadas -->
        <section class="featured-routes">
            <h2>Rutas Destacadas</h2>
            <div class="route-cards">
                <div class="route-card">
                    <h4>Ruta Parque Central</h4>
                    <p>3.5 km • 45 min</p>
                </div>
                <div class="route-card">
                    <h4>Camino al Mirador</h4>
                    <p>5 km • 60 min</p>
                </div>
            </div>
        </section>

    </main>

    <footer>
        <p>© 2025 · FitCircle</p>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="<?= URL ?>paginas/js/live-location.js"></script>
</body>
</html>
