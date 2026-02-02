-- Script para crear la base de datos y tabla de usuarios para FitCircle

CREATE DATABASE IF NOT EXISTS fitcircle;
USE fitcircle;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password CHAR(60) NOT NULL,
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
