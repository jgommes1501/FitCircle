<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->title ?> - FitCircle</title>
    <link rel="stylesheet" href="<?= URL ?>paginas/css/ruta.css">
    <link rel="stylesheet" href="<?= URL ?>paginas/css/theme.css?v=2">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
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
            <a href="<?= ROUTE_URL ?>main/index">Inicio</a>
            <a href="<?= ROUTE_URL ?>ruta/index" class="active">Rutas</a>
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
                <a href="<?= ROUTE_URL ?>auth/login" class="login-btn">Inicia sesion</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="container">
        <?php if (!empty($this->notify)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($this->notify) ?></div>
        <?php endif; ?>

        <?php if (!empty($this->errors)): ?>
            <?php foreach ($this->errors as $msg): ?>
                <div class="alert alert-error"><?= htmlspecialchars($msg) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <section class="hero-banner">
            <h2>Descubre y comparte rutas deportivas</h2>
            <p>Localiza rutas de cualquier parte del mundo, comparte tus actividades con la comunidad</p>
            <button class="start-route" type="button">&#9654; Iniciar nueva ruta</button>
            <?php if (!$this->logged_in): ?>
                <p class="guest-note">Puedes iniciar una ruta sin sesion, pero para guardarla debes iniciar sesion.</p>
            <?php endif; ?>
        </section>

        <section class="live-route-section">
            <h3>Tu actividad en tiempo real</h3>
            <div class="route-tracking">
                <div class="route-stats" aria-label="Estadisticas de ruta en tiempo real">
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
                        <span class="label">Calorias</span>
                        <strong id="route-calories">0 kcal</strong>
                    </div>
                </div>
                <p id="live-location-status" class="live-location-status">Esperando permiso de ubicacion...</p>
                <div id="live-location-map" class="live-location-map" aria-label="Mapa GPS de rutas"></div>
                <p class="gps-help">Si no carga, revisa que estes en HTTPS y con permiso de ubicacion activado.</p>
            </div>
        </section>

        <section id="route-finish-panel" class="save-route-panel" hidden>
            <div class="finish-modal-box">
            <h2>&#9989; Ruta completada</h2>
            <?php if ($this->logged_in): ?>
                <p>Guarda esta ruta para verla en tu historial y compartirla con la comunidad</p>
                <div class="save-row">
                    <input type="text" id="route-title" maxlength="120" placeholder="Nombre de la ruta (opcional)">
                    <label class="inline-check">
                        <input type="checkbox" id="route-public" checked>
                        Compartir publicamente
                    </label>
                </div>
                <div class="finish-actions">
                    <button id="save-route-btn" class="save-route-btn">Guardar ruta</button>
                    <button type="button" class="finish-dismiss-btn btn-ghost">No, descartar</button>
                </div>
                <p id="save-route-status" class="save-route-status"></p>
            <?php else: ?>
                <p>Has terminado tu ruta. ¿Quieres iniciar sesion para guardarla?</p>
                <div class="finish-actions">
                    <a id="route-login-link" class="save-route-btn" href="<?= ROUTE_URL ?>auth/login">Si, iniciar sesion</a>
                    <button id="route-dismiss-login" type="button" class="btn-ghost">No, solo cerrar</button>
                </div>
            <?php endif; ?>
            </div>
        </section>

        <section class="routes-section">
            <div class="section-tabs">
                <button class="tab-btn active" data-tab="tab-personalizada">
                    <span class="tab-icon">&#128221;</span>
                    <span>Personalizada</span>
                </button>
                <button class="tab-btn" data-tab="tab-compartidas">
                    <span class="tab-icon">&#127757;</span>
                    <span>Comunidad</span>
                </button>
                <button class="tab-btn" data-tab="tab-historial">
                    <span class="tab-icon">&#128203;</span>
                    <span>Historial</span>
                </button>
            </div>

            <div id="tab-personalizada" class="tab-content active">
                <div class="tab-shell">
                    <?php if ($this->logged_in): ?>
                        <div class="form-card">
                            <h3>Crear ruta manualmente</h3>
                            <p class="form-subtitle">Registra una ruta que ya completaste o quieres agregar manualmente</p>
                            <form method="POST" action="<?= ROUTE_URL ?>ruta/create_manual" class="manual-route-form">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrf_token) ?>">
                                
                                <div class="form-row">
                                    <label class="form-group">
                                        <span class="form-label">Nombre de la ruta</span>
                                        <input type="text" name="title" required maxlength="120" placeholder="Ej. Ruta por el parque central">
                                    </label>
                                </div>

                                <div class="form-grid">
                                    <label class="form-group">
                                        <span class="form-label">Distancia (km)</span>
                                        <input type="number" name="distance_km" required min="0.1" step="0.01" placeholder="5.2">
                                    </label>
                                    <label class="form-group">
                                        <span class="form-label">Tiempo (minutos)</span>
                                        <input type="number" name="duration_min" required min="1" step="1" placeholder="45">
                                    </label>
                                    <label class="form-group">
                                        <span class="form-label">Pasos</span>
                                        <input type="number" name="steps" min="0" step="1" placeholder="7000">
                                    </label>
                                    <label class="form-group">
                                        <span class="form-label">Calorias</span>
                                        <input type="number" name="calories" min="0" step="1" placeholder="340">
                                    </label>
                                </div>

                                <label class="form-check">
                                    <input type="checkbox" name="is_public" checked>
                                    <span>Compartir en la comunidad para que otros descubran esta ruta</span>
                                </label>

                                <button type="submit" class="btn-primary">Guardar ruta</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">&#128221;</div>
                            <h3>Crea rutas personalizadas</h3>
                            <p>Inicia sesion para registrar tus rutas completadas y compartirlas con la comunidad</p>
                            <a href="<?= ROUTE_URL ?>auth/login" class="btn-primary">Iniciar sesion</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="tab-compartidas" class="tab-content">
                <div class="tab-shell tab-shell-wide">
                    <h3 class="section-title">Rutas de la comunidad</h3>
                    <?php if (empty($this->community_routes)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">&#127757;</div>
                            <h3>No hay rutas disponibles</h3>
                            <p>Se el primero en compartir una ruta y ayuda a otros a descubrir nuevos lugares</p>
                        </div>
                    <?php else: ?>
                        <div class="routes-grid">
                            <?php foreach ($this->community_routes as $route): ?>
                                <article class="route-card">
                                    <div class="route-card-top">
                                        <h4 class="route-title"><?= htmlspecialchars($route->title) ?></h4>
                                        <span class="route-distance"><?= number_format(((float) $route->distance_m) / 1000, 2, ',', '.') ?> km</span>
                                    </div>
                                    
                                    <div class="route-metrics">
                                        <span class="metric-item">
                                            <span class="metric-icon">&#9201;</span>
                                            <span><?= (int) round($route->duration_s / 60) ?> min</span>
                                        </span>
                                        <span class="metric-item">
                                            <span class="metric-icon">&#128099;</span>
                                            <span><?= number_format((int) $route->steps, 0, ',', '.') ?> pasos</span>
                                        </span>
                                        <span class="metric-item">
                                            <span class="metric-icon">&#128293;</span>
                                            <span><?= (int) $route->calories ?> kcal</span>
                                        </span>
                                    </div>

                                    <div class="route-footer">
                                        <div class="route-author">
                                            <?php if (!empty($route->avatar_path)): ?>
                                                <img src="<?= htmlspecialchars(URL . ltrim($route->avatar_path, '/')) ?>" alt="" class="card-avatar" loading="lazy" width="28" height="28" style="width:28px;height:28px;min-width:28px;max-width:28px;min-height:28px;max-height:28px;display:block;object-fit:cover;border-radius:50%;">
                                            <?php else: ?>
                                                <span class="card-avatar-fallback">&#128100;</span>
                                            <?php endif; ?>
                                            <span class="author-name"><?= htmlspecialchars($route->user_name) ?></span>
                                            <span class="author-separator">&bull;</span>
                                            <span class="like-count">&#10084; <?= (int) $route->likes_count ?></span>
                                        </div>
                                        <div class="route-actions">
                                            <?php if ($this->logged_in): ?>
                                                <form method="POST" action="<?= ROUTE_URL ?>ruta/toggle_like/<?= (int) $route->id ?>" class="like-form">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrf_token) ?>">
                                                    <button type="submit" class="action-btn <?= ((int) ($route->liked_by_me ?? 0) > 0) ? 'liked' : '' ?>" title="Me gusta">
                                                        &#10084;
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <a href="<?= ROUTE_URL ?>auth/login" class="action-btn" title="Me gusta">&#10084;</a>
                                            <?php endif; ?>
                                            
                                            <a href="https://wa.me/?text=<?= urlencode('Mira esta ruta en FitCircle: ' . $route->title . ' - ' . number_format(((float) $route->distance_m) / 1000, 2, ',', '.') . ' km') ?>"
                                               target="_blank" rel="noopener" class="action-btn" title="Compartir">
                                                &#128228;
                                            </a>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="tab-historial" class="tab-content">
                <div class="tab-shell tab-shell-wide">
                    <h3 class="section-title">Mis rutas guardadas</h3>
                    <?php if (!$this->logged_in): ?>
                        <div class="empty-state">
                            <div class="empty-icon">&#128203;</div>
                            <h3>Ver tu historial</h3>
                            <p>Inicia sesion para ver todas tus rutas completadas y tu progreso</p>
                            <a href="<?= ROUTE_URL ?>auth/login" class="btn-primary">Iniciar sesion</a>
                        </div>
                    <?php elseif (empty($this->my_routes)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">&#128203;</div>
                            <h3>Sin rutas aun</h3>
                            <p>Comienza a registrar tus rutas para verlas aqui</p>
                        </div>
                    <?php else: ?>
                        <div class="routes-grid">
                            <?php foreach ($this->my_routes as $route): ?>
                                <article class="route-card">
                                    <div class="route-card-top">
                                        <h4 class="route-title"><?= htmlspecialchars($route->title) ?></h4>
                                        <span class="route-distance"><?= number_format(((float) $route->distance_m) / 1000, 2, ',', '.') ?> km</span>
                                    </div>
                                    
                                    <div class="route-metrics">
                                        <span class="metric-item">
                                            <span class="metric-icon">&#9201;</span>
                                            <span><?= (int) round($route->duration_s / 60) ?> min</span>
                                        </span>
                                        <span class="metric-item">
                                            <span class="metric-icon">&#128099;</span>
                                            <span><?= number_format((int) $route->steps, 0, ',', '.') ?> pasos</span>
                                        </span>
                                        <span class="metric-item">
                                            <span class="metric-icon">&#128293;</span>
                                            <span><?= (int) $route->calories ?> kcal</span>
                                        </span>
                                    </div>

                                    <div class="route-footer">
                                        <div class="route-date">
                                            <span class="date-icon">&#128197;</span>
                                            <span><?= date('d/m/Y H:i', strtotime($route->created_at)) ?></span>
                                        </div>
                                        <span class="like-count">&#10084; <?= (int) $route->likes_count ?></span>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2026 · FitCircle - Rastreo de rutas y actividades deportivas</p>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="<?= URL ?>paginas/js/ruta-gps.js"></script>
    <script src="<?= URL ?>paginas/js/theme-mode.js?v=2"></script>
    <script>
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const tabId = btn.dataset.tab;
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.getElementById(tabId)?.classList.add('active');
                btn.classList.add('active');
            });
        });

        // Activate tab from URL param (?tab=historial)
        const _urlTab = new URLSearchParams(window.location.search).get('tab');
        if (_urlTab) {
            const _targetBtn = document.querySelector(`.tab-btn[data-tab="tab-${_urlTab}"]`);
            if (_targetBtn) _targetBtn.click();
        }

        // AJAX like buttons
        document.querySelectorAll('.like-form').forEach(form => {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                const btn = form.querySelector('button[type="submit"]');
                const countEl = form.closest('.route-footer')?.querySelector('.like-count');
                try {
                    const resp = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await resp.json();
                    if (data.ok) {
                        btn.classList.toggle('liked', data.liked);
                        if (countEl) countEl.textContent = '\u2764 ' + data.likes_count;
                    }
                } catch (err) { /* fail silently */ }
            });
        });
    </script>

</body>
</html>