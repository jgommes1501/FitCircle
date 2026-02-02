<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->title ?> - FitCircle</title>
    <link rel="stylesheet" href="<?= URL ?>paginas/css/index.css">
</head>
<body>

    <header>
        <h1>FitCircle</h1>
        <nav class="top-nav">
            <a href="<?= URL ?>">Inicio</a>
            <a href="<?= URL ?>paginas/ruta.html">Rutas</a>
            <a href="<?= URL ?>paginas/retos.html">Retos</a>
            <a href="<?= URL ?>perfil/index" class="profile-icon active" aria-label="Perfil">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="8" r="4" stroke="white" stroke-width="2"/>
                    <path d="M4 20c0-4 4-6 8-6s8 2 8 6" stroke="white" stroke-width="2"/>
                </svg>
            </a>
        </nav>
    </header>

    <main class="container">
        <div class="profile-header" style="background: linear-gradient(135deg, #c62828, #e53935); color: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">👤</div>
            <h1><?= htmlspecialchars($this->user_name) ?></h1>
            <p><?= htmlspecialchars($this->user_email) ?></p>
        </div>

        <section style="background: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);">
            <h2 style="margin-bottom: 1.5rem; color: #2e2e2e;">Información Personal</h2>
            
            <div style="display: flex; justify-content: space-between; padding: 1rem 0; border-bottom: 1px solid #f2f2f2;">
                <span style="color: #6b6b6b;">Nombre:</span>
                <strong><?= htmlspecialchars($this->user_name) ?></strong>
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 1rem 0; border-bottom: 1px solid #f2f2f2;">
                <span style="color: #6b6b6b;">Email:</span>
                <strong><?= htmlspecialchars($this->user_email) ?></strong>
            </div>

            <div style="padding: 1rem 0;">
                <span style="color: #6b6b6b;">Miembro desde:</span>
                <strong><?= date('d/m/Y') ?></strong>
            </div>
        </section>

        <section style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);">
            <h2 style="margin-bottom: 1.5rem; color: #2e2e2e;">Estadísticas</h2>
            
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                <div style="text-align: center;">
                    <div style="font-size: 1.8rem; color: #c62828; font-weight: bold;">0</div>
                    <div style="color: #6b6b6b; font-size: 0.9rem;">Rutas completadas</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 1.8rem; color: #c62828; font-weight: bold;">0</div>
                    <div style="color: #6b6b6b; font-size: 0.9rem;">Retos ganados</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 1.8rem; color: #c62828; font-weight: bold;">0</div>
                    <div style="color: #6b6b6b; font-size: 0.9rem;">km recorridos</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 1.8rem; color: #c62828; font-weight: bold;">0</div>
                    <div style="color: #6b6b6b; font-size: 0.9rem;">Calorías quemadas</div>
                </div>
            </div>
        </section>

        <div style="margin-top: 2rem; text-align: center;">
            <a href="<?= URL ?>auth/logout" style="display: inline-block; background: #c62828; color: white; padding: 0.75rem 2rem; border-radius: 6px; text-decoration: none; font-weight: 600; transition: background 0.3s;">
                Cerrar Sesión
            </a>
        </div>
    </main>

    <nav class="bottom-nav">
        <a href="<?= URL ?>">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7"/><path d="M9 22V12h6v10"/></svg>
            Inicio
        </a>
        <a href="<?= URL ?>paginas/ruta.html">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z"/></svg>
            Rutas
        </a>
        <a href="<?= URL ?>paginas/retos.html">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            Retos
        </a>
        <a href="<?= URL ?>perfil/index" class="active">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>
            Perfil
        </a>
    </nav>

</body>
</html>
