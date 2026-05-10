<?php

/**
 * ============================================================
 * MODELO DE PERFIL — models/perfil.model.php
 * ============================================================
 * Consultas a BD relacionadas con el perfil del usuario:
 * datos personales, estadísticas y rutas recientes.
 * Trabaja sobre las tablas 'users', 'routes' y 'route_likes'.
 *
 * Métodos:
 *   ensureProfileSchema() → Crea columnas/tablas si no existen
 *   getUserProfile($id)   → Datos del usuario (nombre, email, avatar)
 *   updateProfile(...)    → Actualiza nombre y/o avatar
 *   getUserStats($id)     → Estadísticas globales (rutas, km, pasos, cal.)
 *   getRecentRoutes($id)  → Últimas N rutas del usuario
 * ============================================================
 */

class perfilModel extends Model {

    /**
     * Asegura que el esquema de BD esté preparado para el módulo de perfil.
     * Operaciones:
     *   1. Añade la columna 'avatar_path' a 'users' si no existe
     *   2. Crea la tabla 'routes' si no existe
     *   3. Crea la tabla 'route_likes' si no existe
     */
    public function ensureProfileSchema() {
        try {
            $db = $this->db->connect();

            // Comprueba si la columna avatar_path existe en la tabla users
            $avatarColumn = $db->query("SHOW COLUMNS FROM users LIKE 'avatar_path'")->fetch(PDO::FETCH_ASSOC);
            if (!$avatarColumn) {
                // La añade después de la columna password
                $db->exec("ALTER TABLE users ADD avatar_path VARCHAR(255) NULL AFTER password");
            }

            $db->exec("CREATE TABLE IF NOT EXISTS routes (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

            $db->exec("CREATE TABLE IF NOT EXISTS route_likes (
                id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                route_id INT NOT NULL,
                user_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_route_likes_route_id (route_id),
                INDEX idx_route_likes_user_id (user_id),
                UNIQUE KEY unique_route_like (route_id, user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        } catch (PDOException $e) {
            die('Error en modelo perfil (ensureProfileSchema): ' . $e->getMessage());
        }
    }

    /**
     * Obtiene los datos del perfil de un usuario por su ID.
     * Devuelve: id, name, email, avatar_path, created_at (objeto)
     * o false si el usuario no existe.
     */
    public function getUserProfile($userId) {
        try {
            $sql = 'SELECT id, name, email, avatar_path, created_at FROM users WHERE id = :user_id LIMIT 1';
            $db = $this->db->connect();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->setFetchMode(PDO::FETCH_OBJ);
            $stmt->execute();

            return $stmt->fetch();
        } catch (PDOException $e) {
            die('Error en modelo perfil (getUserProfile): ' . $e->getMessage());
        }
    }

    /**
     * Actualiza el nombre y/o avatar del usuario en BD.
     * Si $avatarPath es null solo actualiza el nombre.
     * Si tiene valor, actualiza también la foto de perfil.
     */
    public function updateProfile($userId, $name, $avatarPath = null) {
        try {
            $db = $this->db->connect();

            if ($avatarPath !== null) {
                $sql = 'UPDATE users SET name = :name, avatar_path = :avatar_path WHERE id = :user_id';
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':avatar_path', $avatarPath, PDO::PARAM_STR, 255);
            } else {
                $sql = 'UPDATE users SET name = :name WHERE id = :user_id';
                $stmt = $db->prepare($sql);
            }

            $stmt->bindParam(':name', $name, PDO::PARAM_STR, 100);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            die('Error en modelo perfil (updateProfile): ' . $e->getMessage());
        }
    }

    /**
     * Calcula las estadísticas globales del usuario:
     *   - routes_count: número total de rutas
     *   - total_distance_m: metros totales recorridos
     *   - total_calories: calorías totales quemadas
     *   - total_steps: pasos totales dados
     * COALESCE devuelve 0 en lugar de NULL si no hay rutas.
     */
    public function getUserStats($userId) {
        try {
            $sql = "SELECT
                        COUNT(*) AS routes_count,
                        COALESCE(SUM(distance_m), 0) AS total_distance_m,
                        COALESCE(SUM(calories), 0) AS total_calories,
                        COALESCE(SUM(steps), 0) AS total_steps
                    FROM routes
                    WHERE user_id = :user_id";

            $db = $this->db->connect();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->setFetchMode(PDO::FETCH_OBJ);
            $stmt->execute();

            return $stmt->fetch();
        } catch (PDOException $e) {
            die('Error en modelo perfil (getUserStats): ' . $e->getMessage());
        }
    }

    /**
     * Obtiene las últimas N rutas del usuario ordenadas por fecha descendente.
     * Por defecto devuelve 5 rutas (para el resumen del perfil).
     */
    public function getRecentRoutes($userId, $limit = 5) {
        try {
            $sql = "SELECT id, title, distance_m, duration_s, steps, calories, created_at
                    FROM routes
                    WHERE user_id = :user_id
                    ORDER BY created_at DESC
                    LIMIT :limit";

            $db = $this->db->connect();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            die('Error en modelo perfil (getRecentRoutes): ' . $e->getMessage());
        }
    }
}

?>