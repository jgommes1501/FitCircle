<!--
    Vista del modulo de retos.
    Combina retos del usuario y retos comunitarios con estados de participacion.
    Permite crear, unirse y seguir progreso segun autenticacion.
-->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->title ?> - FitCircle</title>
    <link rel="stylesheet" href="<?= URL ?>paginas/css/retos.css?v=2">
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
            <?php if ($this->logged_in): ?>
                <a href="<?= ROUTE_URL ?>perfil/index" class="profile-icon" aria-label="Perfil">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="8" r="4" stroke="white" stroke-width="2"/>
                        <path d="M4 20c0-4 4-6 8-6s8 2 8 6" stroke="white" stroke-width="2"/>
                    </svg>
                </a>
                <a href="<?= ROUTE_URL ?>auth/logout" class="logout-btn">Salir</a>
            <?php else: ?>
                <a href="<?= ROUTE_URL ?>auth/login" class="login-btn">Inicia sesión</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="container">

        <?php if (isset($this->notify)): ?>
            <div class="alert alert-<?= $this->notify['type'] === 'success' ? 'success' : 'error' ?>">
                <?= htmlspecialchars($this->notify['msg'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($this->errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($this->errors as $err): ?>
                        <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!$this->logged_in): ?>
            <!-- Banner informativo para visitantes -->
            <div class="guest-banner">
                <div class="guest-banner-icon">🏆</div>
                <div>
                    <strong>¡Únete a los retos de FitCircle!</strong>
                    <p>Inicia sesión para crear tus propios retos de km o pasos, unirte a retos de la comunidad y hacer seguimiento de tu progreso.</p>
                    <a href="<?= ROUTE_URL ?>auth/login" class="btn btn-primary">Iniciar sesión</a>
                    <a href="<?= ROUTE_URL ?>auth/register" class="btn btn-outline">Registrarse</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- TABS -->
        <div class="tabs" role="tablist">
            <?php if ($this->logged_in): ?>
                <button class="tab-btn active" data-tab="mis-retos" role="tab" aria-selected="true">Mis Retos</button>
            <?php endif; ?>
            <button class="tab-btn <?= !$this->logged_in ? 'active' : '' ?>" data-tab="comunidad" role="tab" aria-selected="<?= !$this->logged_in ? 'true' : 'false' ?>">Comunidad</button>
        </div>

        <!-- TAB: MIS RETOS (solo logged in) -->
        <?php if ($this->logged_in): ?>
        <section id="tab-mis-retos" class="tab-panel active">
            <div class="section-header">
                <h2>Mis retos</h2>
                <button class="btn btn-primary" onclick="toggleModal('modal-crear')">+ Nuevo reto</button>
            </div>

            <?php if (empty($this->my_challenges)): ?>
                <div class="empty-state">
                    <p>Aún no tienes retos activos. ¡Crea uno o únete a uno de la comunidad!</p>
                </div>
            <?php else: ?>
                <?php foreach ($this->my_challenges as $reto): ?>
                    <?php
                        $goal       = (float) $reto->goal;
                        $progress   = (float) $reto->my_progress;
                        $pct        = $goal > 0 ? min(100, round($progress / $goal * 100)) : 0;
                        $unit       = $reto->type === 'km' ? 'km' : 'pasos';
                        $isCreator  = ($reto->user_id == get_user_id());
                        $isComplete = $pct >= 100;
                        $today      = date('Y-m-d');
                        $isExpired  = $reto->ends_at < $today;
                    ?>
                    <div class="challenge <?= $isComplete ? 'completed' : '' ?> <?= $isExpired && !$isComplete ? 'expired' : '' ?>">
                        <div class="challenge-header">
                            <div>
                                <span class="badge badge-<?= $reto->period ?>"><?= $reto->period === 'mensual' ? 'Mensual' : 'Semanal' ?></span>
                                <span class="badge badge-type"><?= strtoupper($reto->type) ?></span>
                                <?php if (!$reto->is_public): ?><span class="badge badge-private">Privado</span><?php endif; ?>
                            </div>
                            <?php if ($isCreator): ?>
                                <form method="post" action="<?= ROUTE_URL ?>retos/delete" class="inline-form"
                                      onsubmit="return confirm('¿Eliminar este reto y todos sus participantes?')">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="challenge_id" value="<?= (int) $reto->id ?>">
                                    <button type="submit" class="btn-icon btn-danger" title="Eliminar reto">✕</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="<?= ROUTE_URL ?>retos/leave" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="challenge_id" value="<?= (int) $reto->id ?>">
                                    <button type="submit" class="btn-icon btn-muted" title="Abandonar reto">↩</button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <h3><?= htmlspecialchars($reto->title, ENT_QUOTES, 'UTF-8') ?></h3>
                        <?php if (!empty($reto->description)): ?>
                            <p class="challenge-desc"><?= htmlspecialchars($reto->description, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>

                        <p class="challenge-meta">
                            Objetivo: <strong><?= number_format($goal, $reto->type === 'km' ? 1 : 0) ?> <?= $unit ?></strong>
                            &nbsp;·&nbsp; Hasta: <strong><?= date('d/m/Y', strtotime($reto->ends_at)) ?></strong>
                            &nbsp;·&nbsp; <?= (int) $reto->participant_count ?> participante<?= $reto->participant_count != 1 ? 's' : '' ?>
                        </p>

                        <div class="progress-wrap">
                            <div class="progress">
                                <div class="progress-bar <?= $isComplete ? 'complete' : '' ?>" style="width: <?= $pct ?>%"></div>
                            </div>
                            <span class="progress-label"><?= number_format($progress, $reto->type === 'km' ? 1 : 0) ?> / <?= number_format($goal, $reto->type === 'km' ? 1 : 0) ?> <?= $unit ?> (<?= $pct ?>%)</span>
                        </div>

                        <?php if (!$isComplete && !$isExpired): ?>
                        <form method="post" action="<?= ROUTE_URL ?>retos/updateProgress" class="progress-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="challenge_id" value="<?= (int) $reto->id ?>">
                            <label for="prog-<?= $reto->id ?>">Actualizar progreso:</label>
                            <div class="progress-input-row">
                                <input type="number" id="prog-<?= $reto->id ?>" name="progress"
                                       min="0" max="<?= $goal * 10 ?>" step="<?= $reto->type === 'km' ? '0.1' : '1' ?>"
                                       value="<?= $progress ?>" class="input-small" required>
                                <span><?= $unit ?></span>
                                <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                            </div>
                        </form>
                        <?php elseif ($isComplete): ?>
                            <p class="badge-complete">✅ ¡Completado!</p>
                        <?php elseif ($isExpired): ?>
                            <p class="badge-expired">⏰ Reto finalizado</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <!-- TAB: COMUNIDAD -->
        <section id="tab-comunidad" class="tab-panel <?= !$this->logged_in ? 'active' : '' ?>">
            <div class="section-header">
                <h2>Retos de la comunidad</h2>
                <?php if ($this->logged_in): ?>
                    <button class="btn btn-primary" onclick="toggleModal('modal-crear')">+ Nuevo reto</button>
                <?php endif; ?>
            </div>

            <?php if (empty($this->public_challenges)): ?>
                <div class="empty-state">
                    <p>Aún no hay retos públicos. ¡Sé el primero en crear uno!</p>
                </div>
            <?php else: ?>
                <?php foreach ($this->public_challenges as $reto): ?>
                    <?php
                        $goal      = (float) $reto->goal;
                        $myProg    = $reto->my_progress !== null ? (float) $reto->my_progress : null;
                        $pct       = ($myProg !== null && $goal > 0) ? min(100, round($myProg / $goal * 100)) : null;
                        $unit      = $reto->type === 'km' ? 'km' : 'pasos';
                        $joined    = (bool) $reto->joined;
                        $isCreator = $this->logged_in && ($reto->user_id == get_user_id());
                        $today     = date('Y-m-d');
                        $isExpired = $reto->ends_at < $today;
                    ?>
                    <div class="challenge <?= ($pct !== null && $pct >= 100) ? 'completed' : '' ?> <?= $isExpired ? 'expired' : '' ?>">
                        <div class="challenge-header">
                            <div>
                                <span class="badge badge-<?= $reto->period ?>"><?= $reto->period === 'mensual' ? 'Mensual' : 'Semanal' ?></span>
                                <span class="badge badge-type"><?= strtoupper($reto->type) ?></span>
                            </div>
                            <span class="creator-tag">por <?= htmlspecialchars($reto->creator_name, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>

                        <h3><?= htmlspecialchars($reto->title, ENT_QUOTES, 'UTF-8') ?></h3>
                        <?php if (!empty($reto->description)): ?>
                            <p class="challenge-desc"><?= htmlspecialchars($reto->description, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>

                        <p class="challenge-meta">
                            Objetivo: <strong><?= number_format($goal, $reto->type === 'km' ? 1 : 0) ?> <?= $unit ?></strong>
                            &nbsp;·&nbsp; Hasta: <strong><?= date('d/m/Y', strtotime($reto->ends_at)) ?></strong>
                            &nbsp;·&nbsp; <?= (int) $reto->participant_count ?> participante<?= $reto->participant_count != 1 ? 's' : '' ?>
                        </p>

                        <?php if ($joined && $myProg !== null): ?>
                            <div class="progress-wrap">
                                <div class="progress">
                                    <div class="progress-bar <?= $pct >= 100 ? 'complete' : '' ?>" style="width: <?= $pct ?>%"></div>
                                </div>
                                <span class="progress-label"><?= number_format($myProg, $reto->type === 'km' ? 1 : 0) ?> / <?= number_format($goal, $reto->type === 'km' ? 1 : 0) ?> <?= $unit ?> (<?= $pct ?>%)</span>
                            </div>
                        <?php endif; ?>

                        <?php if ($this->logged_in && !$isExpired): ?>
                            <?php if (!$joined): ?>
                                <form method="post" action="<?= ROUTE_URL ?>retos/join" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="challenge_id" value="<?= (int) $reto->id ?>">
                                    <button type="submit" class="btn btn-sm btn-primary">Unirse</button>
                                </form>
                            <?php elseif (!$isCreator): ?>
                                <div class="action-row">
                                    <form method="post" action="<?= ROUTE_URL ?>retos/updateProgress" class="progress-form">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="challenge_id" value="<?= (int) $reto->id ?>">
                                        <div class="progress-input-row">
                                            <input type="number" name="progress"
                                                   min="0" step="<?= $reto->type === 'km' ? '0.1' : '1' ?>"
                                                   value="<?= $myProg ?>" class="input-small" required>
                                            <span><?= $unit ?></span>
                                            <button type="submit" class="btn btn-sm btn-primary">Actualizar</button>
                                        </div>
                                    </form>
                                    <form method="post" action="<?= ROUTE_URL ?>retos/leave" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="challenge_id" value="<?= (int) $reto->id ?>">
                                        <button type="submit" class="btn btn-sm btn-outline btn-danger-outline">Abandonar</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <p class="badge-creator">👑 Eres el creador</p>
                            <?php endif; ?>
                        <?php elseif (!$this->logged_in): ?>
                            <a href="<?= ROUTE_URL ?>auth/login" class="btn btn-sm btn-outline">Inicia sesión para unirte</a>
                        <?php elseif ($isExpired): ?>
                            <p class="badge-expired">⏰ Reto finalizado</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

    </main>

    <!-- MODAL: CREAR RETO -->
    <?php if ($this->logged_in): ?>
    <div id="modal-crear" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-crear-title" hidden>
        <div class="modal">
            <div class="modal-header">
                <h2 id="modal-crear-title">Nuevo reto</h2>
                <button class="btn-icon" onclick="toggleModal('modal-crear')" aria-label="Cerrar">✕</button>
            </div>
            <form method="post" action="<?= ROUTE_URL ?>retos/create" class="reto-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrf_token, ENT_QUOTES, 'UTF-8') ?>">

                <div class="form-group">
                    <label for="reto-title">Título del reto *</label>
                    <input type="text" id="reto-title" name="title" maxlength="150" placeholder="Ej: Corre 100 km este mes" required>
                </div>

                <div class="form-group">
                    <label for="reto-desc">Descripción (opcional)</label>
                    <textarea id="reto-desc" name="description" rows="2" maxlength="500" placeholder="Describe el reto..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="reto-type">Tipo de objetivo *</label>
                        <select id="reto-type" name="type" required>
                            <option value="km">Kilómetros (km)</option>
                            <option value="pasos">Pasos</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="reto-period">Duración *</label>
                        <select id="reto-period" name="period" required>
                            <option value="mensual">Mensual (30 días)</option>
                            <option value="semanal">Semanal (7 días)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reto-goal">Objetivo <span id="goal-unit-label">(km)</span> *</label>
                    <input type="number" id="reto-goal" name="goal" min="0.1" step="0.1" placeholder="Ej: 100" required>
                </div>

                <div class="form-group form-check">
                    <input type="checkbox" id="reto-public" name="is_public" value="1" checked>
                    <label for="reto-public">Reto público (visible en comunidad)</label>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="toggleModal('modal-crear')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear reto</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <footer>
        <p>© 2026 · FitCircle</p>
    </footer>

    <script src="<?= URL ?>paginas/js/theme-mode.js?v=2"></script>
    <script>
        // Tabs
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => { b.classList.remove('active'); b.setAttribute('aria-selected', 'false'); });
                document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                btn.setAttribute('aria-selected', 'true');
                document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
            });
        });

        // Modal
        function toggleModal(id) {
            const m = document.getElementById(id);
            if (m.hidden) {
                m.removeAttribute('hidden');
                document.body.style.overflow = 'hidden';
            } else {
                m.setAttribute('hidden', '');
                document.body.style.overflow = '';
            }
        }
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', e => {
                if (e.target === overlay) toggleModal(overlay.id);
            });
        });

        // Actualizar label unidad en formulario
        const typeSelect = document.getElementById('reto-type');
        const unitLabel  = document.getElementById('goal-unit-label');
        const goalInput  = document.getElementById('reto-goal');
        if (typeSelect) {
            typeSelect.addEventListener('change', () => {
                if (typeSelect.value === 'km') {
                    unitLabel.textContent = '(km)';
                    goalInput.step = '0.1';
                    goalInput.placeholder = 'Ej: 100';
                } else {
                    unitLabel.textContent = '(pasos)';
                    goalInput.step = '1000';
                    goalInput.placeholder = 'Ej: 200000';
                }
            });
        }
    </script>

</body>
</html>