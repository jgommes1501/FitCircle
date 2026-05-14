<!--
    Vista de perfil del usuario.
    Presenta datos personales, estadisticas acumuladas y rutas recientes.
    Incluye formulario para actualizar nombre y avatar.
-->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->title ?> - FitCircle</title>
    <link rel="stylesheet" href="<?= URL ?>paginas/css/perfil.css">
    <link rel="stylesheet" href="<?= URL ?>paginas/css/theme.css?v=2">
</head>
<body>

    <?php
    $profile = $this->profile;
    $stats = $this->stats;
    $recentRoutes = $this->recent_routes ?? [];
    $avatar = !empty($profile->avatar_path) ? (URL . ltrim($profile->avatar_path, '/')) : null;
    $navAvatar = !empty($_SESSION['user_avatar']) ? (URL . ltrim($_SESSION['user_avatar'], '/')) : null;
    ?>

    <header>
        <h1 class="brand-logo-wrap">
            <a href="<?= ROUTE_URL ?>main/index" class="brand-logo-link" aria-label="FitCircle inicio">
                <img src="<?= URL ?>paginas/img/FitCircle.png" alt="FitCircle" class="brand-logo">
            </a>
        </h1>
        <nav class="top-nav">
            <a href="<?= ROUTE_URL ?>main/index">Inicio</a>
            <a href="<?= ROUTE_URL ?>ruta/index">Rutas</a>
            <a href="<?= ROUTE_URL ?>retos/index">Retos</a>
            <a href="<?= ROUTE_URL ?>perfil/index" class="profile-icon active" aria-label="Perfil">
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

        <section class="card theme-card">
            <div>
                <h3>Apariencia</h3>
                <p>Activa el modo oscuro para ver FitCircle con la estética actual oscura.</p>
            </div>
            <label class="theme-switch" for="theme-toggle">
                <input type="checkbox" id="theme-toggle" aria-label="Activar modo oscuro">
                <span id="theme-toggle-label">Modo claro</span>
            </label>
        </section>

        <section class="profile-header">
            <div class="avatar-wrap">
                <?php if ($avatar): ?>
                    <img src="<?= htmlspecialchars($avatar) ?>" alt="Foto de perfil" class="avatar-img" width="82" height="82" style="width:82px;height:82px;min-width:82px;max-width:82px;min-height:82px;max-height:82px;display:block;object-fit:cover;border-radius:50%;">
                <?php else: ?>
                    <div class="avatar-fallback">👤</div>
                <?php endif; ?>
            </div>
            <div>
                <h2><?= htmlspecialchars($profile->name) ?></h2>
                <p><?= htmlspecialchars($profile->email) ?></p>
                <small>Miembro desde <?= date('d/m/Y', strtotime($profile->created_at)) ?></small>
            </div>
        </section>

        <section class="card">
            <h3>Editar perfil</h3>
            <form method="POST" action="<?= ROUTE_URL ?>perfil/update" enctype="multipart/form-data" class="edit-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrf_token) ?>">

                <label>
                    Nombre
                    <input type="text" name="name" required maxlength="100" value="<?= htmlspecialchars($profile->name) ?>">
                </label>

                <label>
                    Foto de perfil (JPG, PNG, WEBP o GIF · máx 2MB)
                    <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp,image/gif">
                </label>

                <button type="submit">Guardar cambios</button>
            </form>
        </section>

        <section class="card">
            <h3>Resumen de actividad</h3>
            <div class="stats-grid">
                <div>
                    <strong><?= (int) ($stats->routes_count ?? 0) ?></strong>
                    <span>Rutas completadas</span>
                </div>
                <div>
                    <strong><?= number_format(((float) ($stats->total_distance_m ?? 0)) / 1000, 2, ',', '.') ?> km</strong>
                    <span>Distancia total</span>
                </div>
                <div>
                    <strong><?= number_format((int) ($stats->total_steps ?? 0), 0, ',', '.') ?></strong>
                    <span>Pasos totales</span>
                </div>
                <div>
                    <strong><?= number_format((int) ($stats->total_calories ?? 0), 0, ',', '.') ?> kcal</strong>
                    <span>Calorías</span>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card-title-row">
                <h3>Historial reciente</h3>
                <a href="<?= ROUTE_URL ?>ruta/historial">Ver historial completo</a>
            </div>

            <?php if (empty($recentRoutes)): ?>
                <p class="empty">Todavía no has guardado rutas.</p>
            <?php else: ?>
                <div class="history-list">
                    <?php foreach ($recentRoutes as $route): ?>
                        <article class="history-item">
                            <h4><?= htmlspecialchars($route->title) ?></h4>
                            <p>
                                <?= number_format(((float) $route->distance_m) / 1000, 2, ',', '.') ?> km ·
                                <?= (int) round($route->duration_s / 60) ?> min ·
                                <?= number_format((int) $route->steps, 0, ',', '.') ?> pasos
                            </p>
                            <small><?= date('d/m/Y H:i', strtotime($route->created_at)) ?></small>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <div class="action-row">
            <a href="<?= ROUTE_URL ?>ruta/index" class="btn-secondary">Ir a Rutas</a>
        </div>

        <div class="action-row">
            <a href="<?= ROUTE_URL ?>auth/logout" class="btn-logout">Cerrar sesión</a>
        </div>
    </main>

    <footer>
        <p>© 2026 · FitCircle</p>
    </footer>

    <script src="<?= URL ?>paginas/js/theme-mode.js?v=2"></script>

</body>
</html>
