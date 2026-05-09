<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->title ?> - FitCircle</title>
    <link rel="stylesheet" href="<?= URL ?>paginas/css/perfil.css">
    <link rel="stylesheet" href="<?= URL ?>paginas/css/theme.css?v=2">
    <style>
        .historial-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .historial-header h2 { font-size: 1.6rem; color: var(--rojo-principal, #c62828); margin: 0; }
        .historial-count { font-size: 0.9rem; color: #777; }
        .historial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .historial-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.2rem 1.4rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid #c62828;
        }
        .historial-card h4 {
            font-size: 1rem;
            font-weight: 700;
            margin: 0 0 0.6rem;
            color: #1a1a1a;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .historial-card .metrics {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem 1.2rem;
            font-size: 0.85rem;
            color: #555;
            margin-bottom: 0.6rem;
        }
        .hist-author {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.82rem;
            color: #666;
            margin-bottom: 0.55rem;
        }
        .hist-author img {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
        }
        .historial-card .metrics span strong { color: #1a1a1a; }
        .historial-card .date { font-size: 0.78rem; color: #999; }
        .empty-hist {
            text-align: center;
            padding: 4rem 1rem;
            color: #777;
        }
        .empty-hist .empty-icon { font-size: 3rem; margin-bottom: 1rem; }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: #c62828;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        .back-link:hover { text-decoration: underline; }
        .hist-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .hist-sum-card {
            background: #fff;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        .hist-sum-card strong { display: block; font-size: 1.4rem; color: #c62828; }
        .hist-sum-card span { font-size: 0.8rem; color: #777; }
    </style>
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
            <a href="<?= ROUTE_URL ?>retos/index">Retos</a>
            <a href="<?= ROUTE_URL ?>perfil/index" class="profile-icon" aria-label="Perfil">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="8" r="4" stroke="white" stroke-width="2"/>
                    <path d="M4 20c0-4 4-6 8-6s8 2 8 6" stroke="white" stroke-width="2"/>
                </svg>
            </a>
            <a href="<?= ROUTE_URL ?>auth/logout" class="logout-btn">Salir</a>
        </nav>
    </header>

    <main class="container">

        <?php if (!empty($this->notify)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($this->notify) ?></div>
        <?php endif; ?>

        <a href="<?= ROUTE_URL ?>perfil/index" class="back-link">&#8592; Volver a mi perfil</a>

        <div class="historial-header">
            <h2>&#128203; Mi historial de rutas</h2>
            <span class="historial-count"><?= count($this->my_routes) ?> ruta<?= count($this->my_routes) !== 1 ? 's' : '' ?> guardada<?= count($this->my_routes) !== 1 ? 's' : '' ?></span>
        </div>

        <?php if (!empty($this->my_routes)): ?>
            <?php
            $totalDist = array_sum(array_column((array) $this->my_routes, 'distance_m'));
            $totalSteps = array_sum(array_column((array) $this->my_routes, 'steps'));
            $totalCal = array_sum(array_column((array) $this->my_routes, 'calories'));
            // For objects
            $totalDistM = 0; $totalStepsN = 0; $totalCalN = 0;
            foreach ($this->my_routes as $r) {
                $totalDistM += (float) $r->distance_m;
                $totalStepsN += (int) $r->steps;
                $totalCalN += (int) $r->calories;
            }
            ?>
            <div class="hist-summary">
                <div class="hist-sum-card">
                    <strong><?= number_format($totalDistM / 1000, 2, ',', '.') ?> km</strong>
                    <span>Distancia total</span>
                </div>
                <div class="hist-sum-card">
                    <strong><?= number_format($totalStepsN, 0, ',', '.') ?></strong>
                    <span>Pasos totales</span>
                </div>
                <div class="hist-sum-card">
                    <strong><?= number_format($totalCalN, 0, ',', '.') ?> kcal</strong>
                    <span>Calor&iacute;as totales</span>
                </div>
                <div class="hist-sum-card">
                    <strong><?= count($this->my_routes) ?></strong>
                    <span>Rutas completadas</span>
                </div>
            </div>

            <div class="historial-grid">
                <?php foreach ($this->my_routes as $route): ?>
                    <article class="historial-card">
                        <h4><?= htmlspecialchars($route->title) ?></h4>
                        <div class="hist-author">
                            <?php if (!empty($route->avatar_path)): ?>
                                <img src="<?= htmlspecialchars(URL . ltrim($route->avatar_path, '/')) ?>" alt="" width="24" height="24" style="width:24px;height:24px;min-width:24px;max-width:24px;min-height:24px;max-height:24px;display:block;object-fit:cover;border-radius:50%;">
                            <?php else: ?>
                                <span>&#128100;</span>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($route->user_name ?: $this->user_name) ?></span>
                        </div>
                        <div class="metrics">
                            <span><strong><?= number_format(((float) $route->distance_m) / 1000, 2, ',', '.') ?> km</strong> distancia</span>
                            <span><strong><?= (int) round($route->duration_s / 60) ?> min</strong> tiempo</span>
                            <span><strong><?= number_format((int) $route->steps, 0, ',', '.') ?></strong> pasos</span>
                            <span><strong><?= (int) $route->calories ?> kcal</strong></span>
                        </div>
                        <p class="date">&#128197; <?= date('d/m/Y H:i', strtotime($route->created_at)) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-hist">
                <div class="empty-icon">&#127939;</div>
                <h3>Todav&iacute;a no tienes rutas guardadas</h3>
                <p>Inicia una ruta y gu&aacute;rdala para verla aqu&iacute;.</p>
                <a href="<?= ROUTE_URL ?>ruta/index" class="btn-primary" style="display:inline-block;margin-top:1rem;padding:0.7rem 1.5rem;background:#c62828;color:#fff;border-radius:999px;text-decoration:none;font-weight:700;">Ir a Rutas</a>
            </div>
        <?php endif; ?>

    </main>

    <footer>
        <p>&copy; 2026 &middot; FitCircle</p>
    </footer>

    <script src="<?= URL ?>paginas/js/theme-mode.js?v=2"></script>

</body>
</html>
