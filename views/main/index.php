<!--
    Vista principal (home) de FitCircle.
    Muestra propuesta de valor, accesos rapidos y secciones de informacion del producto.
    Consume estado de sesion para adaptar la navegacion y los CTA.
-->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->title ?></title>
    <link rel="stylesheet" href="<?= URL ?>paginas/css/index.css?v=4">
    <link rel="stylesheet" href="<?= URL ?>paginas/css/theme.css?v=2">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9T/miZyoHS5obTRR9BMY=" crossorigin="" />
</head>
<body data-route-url="<?= ROUTE_URL ?>" data-logged-in="<?= $this->logged_in ? '1' : '0' ?>">
    <?php $navAvatar = !empty($_SESSION['user_avatar']) ? (URL . ltrim($_SESSION['user_avatar'], '/')) : null; ?>

    <header>
        <h1 class="brand-logo-wrap">
            <a href="<?= ROUTE_URL ?>main/index" class="brand-logo-link" aria-label="FitCircle inicio">
                <img src="<?= URL ?>paginas/img/FitCircle.png" alt="FitCircle" class="brand-logo">
            </a>
        </h1>
        <nav class="top-nav">
            <a href="<?= ROUTE_URL ?>main/index" class="active">Inicio</a>
            <a href="<?= ROUTE_URL ?>ruta/index">Rutas</a>
            <a href="<?= ROUTE_URL ?>retos/index">Retos</a>
            <?php if ($this->logged_in): ?>
                <a href="<?= ROUTE_URL ?>perfil/index" class="profile-icon" aria-label="Perfil">
                    <?php if ($navAvatar): ?>
                        <img src="<?= htmlspecialchars($navAvatar) ?>" alt="Perfil" class="nav-avatar-img" width="24" height="24" style="width:24px;height:24px;min-width:24px;max-width:24px;min-height:24px;max-height:24px;display:block;object-fit:cover;border-radius:50%;">
                    <?php else: ?>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="8" r="4" stroke="white" stroke-width="2"/>
                            <path d="M4 20c0-4 4-6 8-6s8 2 8 6" stroke="white" stroke-width="2"/>
                        </svg>
                    <?php endif; ?>
                </a>
                <a href="<?= ROUTE_URL ?>auth/logout" class="logout-btn">Salir</a>
            <?php else: ?>
                <a href="<?= ROUTE_URL ?>auth/login" class="login-btn">Inicia sesión</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="container">
        <section class="top-circle-launch reveal" aria-label="Inicio rápido de ruta">
            <a href="<?= ROUTE_URL ?>ruta/index" class="circle-start-btn">Iniciar ruta</a>
        </section>

        <?php if (isset($this->notify)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($this->notify) ?></div>
        <?php endif; ?>



        <section class="hero-main reveal">
            <div class="hero-overlay"></div>
            <div class="hero-inner">
                <span class="hero-tag">Seguimiento deportivo inteligente</span>
                <h2>Tu entrenamiento, tu progreso,<br>tu comunidad</h2>
                <p>Registra rutas con GPS en vivo, visualiza métricas claras y comparte tus actividades con una comunidad activa de deportistas.</p>
                <div class="hero-actions">
                    <a href="<?= ROUTE_URL ?>ruta/index" class="btn-primary">Explorar Rutas</a>
                    <?php if ($this->logged_in): ?>
                        <a href="<?= ROUTE_URL ?>perfil/index" class="btn-secondary">Ver mi perfil</a>
                    <?php else: ?>
                        <a href="<?= ROUTE_URL ?>auth/register" class="btn-secondary">Crear cuenta gratis</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="feature-grid reveal">
            <article class="feature-card">
                <h3>GPS en vivo</h3>
                <p>Seguimiento continuo con una vista clara y rápida.</p>
            </article>
            <article class="feature-card">
                <h3>Datos claros</h3>
                <p>Pasos, distancia, tiempo y calorías sin ruido visual.</p>
            </article>
            <article class="feature-card">
                <h3>Comparte y descubre</h3>
                <p>Rutas de la comunidad para entrenar con más ideas.</p>
            </article>
        </section>

        <section class="story-panel reveal" style="background-image:url('https://images.unsplash.com/photo-1571008887538-b36bb32f4571?w=1400&q=80')">
            <div class="story-panel-overlay"></div>
            <div class="story-panel-inner">
                <h3>Diseñado para deportistas reales</h3>
                <p>FitCircle está pensado para quienes entrenan de verdad: menos ruido, más claridad, y funciones que te ayudan a mejorar de forma constante.</p>
            </div>
        </section>

        <section class="story-panel reveal" style="background-image:url('https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=1400&q=80')">
            <div class="story-panel-overlay"></div>
            <div class="story-panel-inner">
                <h3>Visualiza todo de forma simple</h3>
                <p>Una interfaz limpia para que te centres en tus datos: progreso, constancia y resultados.</p>
            </div>
        </section>

        <section class="live-route-section reveal">
            <h3>Tu actividad en tiempo real</h3>
            <div class="route-tracking">
                <div class="route-stats" aria-label="Estadísticas de ruta en tiempo real">
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
                    <div class="route-stat">
                        <span class="label">Calorías</span>
                        <strong id="route-calories">0 kcal</strong>
                    </div>
                </div>
                <button class="start-route" type="button">▶ Iniciar nueva ruta</button>
                <p id="live-location-status" class="live-location-status">Pulsa iniciar para comenzar el seguimiento.</p>
                <div id="live-location-map" class="live-location-map" aria-label="Mapa GPS de rutas"></div>
                <p class="gps-help">Si no carga, revisa que estés en HTTPS y con permiso de ubicación activado.</p>
            </div>
        </section>

        <section id="route-finish-panel" class="save-route-panel" hidden aria-hidden="true">
            <div class="finish-modal-box">
                <h2>Ruta finalizada</h2>
                <?php if ($this->logged_in): ?>
                    <p>Guarda esta ruta para verla en tu historial y compartirla.</p>
                    <div class="save-row">
                        <input type="text" id="route-title" maxlength="120" placeholder="Nombre de la ruta (opcional)">
                        <label class="inline-check">
                            <input type="checkbox" id="route-public" checked>
                            Pública
                        </label>
                    </div>
                    <div class="finish-actions">
                        <button id="save-route-btn" class="save-route-btn">Guardar ruta</button>
                        <button type="button" class="finish-dismiss-btn btn-ghost">Descartar</button>
                    </div>
                    <p id="save-route-status" class="save-route-status"></p>
                <?php else: ?>
                    <p>¿Quieres iniciar sesión para guardar esta ruta?</p>
                    <div class="finish-actions">
                        <a id="route-login-link" href="<?= ROUTE_URL ?>auth/login" class="save-route-btn">Sí, iniciar sesión</a>
                        <button id="route-dismiss-login" type="button" class="btn-ghost">No, descartar</button>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </main>

    <footer>
        <p>© 2026 · FitCircle - Tu aplicación de rastreo de rutas</p>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="<?= URL ?>paginas/js/ruta-gps.js"></script>
    <script src="<?= URL ?>paginas/js/theme-mode.js?v=2"></script>
    <script>
        (function () {
            const revealItems = document.querySelectorAll('.reveal');
            const io = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('in-view');
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.16 });

            revealItems.forEach((el) => io.observe(el));

        })();
    </script>
</body>
</html>
