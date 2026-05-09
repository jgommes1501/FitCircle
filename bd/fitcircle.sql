-- Script para crear la base de datos y tabla de usuarios para FitCircle

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password CHAR(60) NOT NULL,
    avatar_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de roles (opcional para futuras mejoras)
CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de relación usuarios-roles (opcional)
CREATE TABLE IF NOT EXISTS user_roles (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_user_role (user_id, role_id)
);

-- Insertar roles por defecto
INSERT IGNORE INTO roles (id, name, description) VALUES
(1, 'Admin', 'Administrador del sistema'),
(2, 'Usuario', 'Usuario registrado normal'),
(3, 'Moderador', 'Moderador de contenidos');

-- Ejemplo de usuario para pruebas (contraseña: password123)
INSERT IGNORE INTO users (name, email, password) VALUES
('Admin User', 'admin@fitcircle.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36gZvWFm'),
('Test User', 'test@fitcircle.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36gZvWFm');

-- Asignar roles a usuarios
INSERT IGNORE INTO user_roles (user_id, role_id) VALUES
(1, 1),
(2, 2);

-- Rutas guardadas por los usuarios
CREATE TABLE IF NOT EXISTS routes (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(120) NOT NULL,
    distance_m DECIMAL(10,2) NOT NULL DEFAULT 0,
    duration_s INT UNSIGNED NOT NULL DEFAULT 0,
    steps INT UNSIGNED NOT NULL DEFAULT 0,
    calories INT UNSIGNED NOT NULL DEFAULT 0,
    path_json MEDIUMTEXT NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_routes_user_id (user_id)
);

-- Reacciones/me gustas de rutas
CREATE TABLE IF NOT EXISTS route_likes (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    route_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_route_likes_route_id (route_id),
    INDEX idx_route_likes_user_id (user_id),
    UNIQUE KEY unique_route_like (route_id, user_id)
);
