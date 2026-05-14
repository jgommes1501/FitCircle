# FitCircle

Aplicacion web para deportistas construida con PHP (MVC propio), MySQL y frontend HTML/CSS/JS.

Sitio de referencia: fitcircle.infinityfreeapp.com

## Resumen funcional

FitCircle centraliza tres areas principales:

- Rutas: rastreo GPS en vivo, calculo de metricas y guardado de actividades.
- Retos: creacion de retos personales/comunitarios y seguimiento de progreso.
- Perfil: estadisticas acumuladas, edicion de datos y avatar.

## Arquitectura tecnica

Flujo principal:

Navegador -> index.php -> Router (libs/app.php) -> Controlador -> Modelo -> MySQL -> Vista

Capas:

- Front controller: index.php
- Configuracion global: config/config.php
- Nucleo MVC: libs/
- Logica HTTP: controllers/
- Acceso a datos: models/
- Renderizado UI: views/
- Recursos estaticos: paginas/css, paginas/js, paginas/img

## Estructura del proyecto

```
FitCircle/
    index.php
    config/
        config.php
    controllers/
        auth.php
        main.php
        perfil.php
        retos.php
        ruta.php
    models/
        auth.model.php
        perfil.model.php
        retos.model.php
        ruta.model.php
    libs/
        app.php
        controller.php
        model.php
        view.php
        database.php
    functions/
        session_seg.php
    views/
        auth/login/index.php
        auth/register/index.php
        main/index.php
        perfil/index.php
        retos/index.php
        ruta/index.php
        ruta/historial.php
    paginas/
        css/
        js/
        img/
    bd/
        fitcircle.sql
    uploads/
        avatars/
```

## Modulos principales

### Autenticacion

- Login y registro con validacion de formularios.
- Password hashing con bcrypt (password_hash / password_verify).
- Proteccion CSRF en formularios.
- Sesiones seguras con cookie httponly y regeneracion de ID.

### Rutas GPS

- Captura en vivo con Geolocation API.
- Mapa con Leaflet + OpenStreetMap.
- Metricas: distancia, tiempo, pasos y calorias.
- Guardado en BD y opcion de compartir en comunidad.

### Retos

- Retos por distancia o pasos.
- Retos propios y de comunidad.
- Progreso individual y participacion de usuarios.

### Perfil

- Datos del usuario.
- Carga de avatar.
- Estadisticas y rutas recientes.

## Base de datos

Script principal: bd/fitcircle.sql

Incluye, entre otras, estas tablas:

- users
- routes
- route_likes
- challenges
- challenge_participants

## Requisitos

- PHP 8.x (compatible con 7.4+ en varios entornos)
- MySQL o MariaDB
- Apache (XAMPP recomendado para local)
- Navegador con soporte de Geolocation para modulo de rutas

## Instalacion local (XAMPP)

1. Clonar o copiar el proyecto en htdocs/FitCircle.
2. Iniciar Apache y MySQL desde XAMPP.
3. Importar bd/fitcircle.sql en MySQL.
4. Revisar config/config.php (URL base y credenciales BD).
5. Abrir en navegador: http://localhost/FitCircle/

## Seguridad implementada

- Consultas preparadas con PDO.
- Hash de contrasenas con bcrypt.
- Tokens CSRF en formularios criticos.
- Sesion segura con regeneracion de identificador.
- Validacion/sanitizacion de entradas en controladores.

## Notas

- Para funcionamiento GPS en moviles, usar HTTPS en produccion.
- El sistema permite rutas publicas y privadas.
- El modo de tema se gestiona desde paginas/js/theme-mode.js.

---

Ultima actualizacion: 14 de mayo de 2026
