<?php

/**
 * ============================================================
 * MODELO DE RUTAS — models/ruta.model.php
 * ============================================================
 * Consultas a BD relacionadas con las rutas deportivas y me gustas.
 * Trabaja sobre las tablas 'routes' y 'route_likes'.
 *
 * Métodos:
 *   ensureSocialSchema()        → Crea tablas y columnas si no existen
 *   saveRoute(...)              → Guarda una ruta en BD
 *   getUserRoutes($id, $limit)  → Rutas de un usuario concreto
 *   getCommunityRoutes($uid)    → Rutas públicas de la comunidad
 *   toggleLike($routeId, $uid)  → Alterna el me gusta de una ruta
 *   getLikesCount($routeId)     → Cuenta los me gustas de una ruta
 * ============================================================
 */

class rutaModel extends Model {

    /**
     * Crea las tablas y columnas necesarias si no existen:
     *   1. Tabla 'routes' — actividades de todos los usuarios
     *   2. Tabla 'route_likes' — me gustas (clave única ruta+usuario)
     *   3. Columna 'avatar_path' en 'users' si no existía aún
     * Se ejecuta en el constructor del controlador de rutas.
     */
    public function ensureSocialSchema() {
        try {
            $db = $this->db->connect();

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

            $avatarColumn = $db->query("SHOW COLUMNS FROM users LIKE 'avatar_path'")->fetch(PDO::FETCH_ASSOC);
            if (!$avatarColumn) {
                $db->exec("ALTER TABLE users ADD avatar_path VARCHAR(255) NULL AFTER password");
            }
        } catch (PDOException $e) {
            die('Error en modelo ruta (ensureSocialSchema): ' . $e->getMessage());
        }
    }

    /**
     * Guarda una ruta en la tabla 'routes'.
     * path_json: array de coordenadas GPS serializado como JSON
     * (null si es una ruta creada manualmente sin traza GPS).
     * Devuelve el ID de la nueva ruta.
     */
    public function saveRoute($userId, $title, $distanceM, $durationS, $steps, $calories, $pathJson = null, $isPublic = 1) {
        try {
            $sql = "INSERT INTO routes
                    (user_id, title, distance_m, duration_s, steps, calories, path_json, is_public)
                    VALUES
                    (:user_id, :title, :distance_m, :duration_s, :steps, :calories, :path_json, :is_public)";

            $db = $this->db->connect();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':title', $title, PDO::PARAM_STR, 120);
            $stmt->bindParam(':distance_m', $distanceM);
            $stmt->bindParam(':duration_s', $durationS, PDO::PARAM_INT);
            $stmt->bindParam(':steps', $steps, PDO::PARAM_INT);
            $stmt->bindParam(':calories', $calories, PDO::PARAM_INT);
            $stmt->bindParam(':path_json', $pathJson, PDO::PARAM_STR);
            $stmt->bindParam(':is_public', $isPublic, PDO::PARAM_INT);
            $stmt->execute();

            return (int) $db->lastInsertId();
        } catch (PDOException $e) {
            die('Error en modelo ruta (saveRoute): ' . $e->getMessage());
        }
    }

    /**
     * Devuelve las rutas de un usuario ordenadas por fecha descendente.
     * Incluye nombre de usuario, avatar y recuento de me gustas.
     * $limit: 30 por defecto, hasta 1000 para la vista de historial.
     */
    public function getUserRoutes($userId, $limit = 30) {
        try {
            $sql = "SELECT r.*,
                           u.name AS user_name,
                           u.avatar_path,
                           (SELECT COUNT(*) FROM route_likes rl WHERE rl.route_id = r.id) AS likes_count
                    FROM routes r
                    INNER JOIN users u ON u.id = r.user_id
                    WHERE r.user_id = :user_id
                    ORDER BY r.created_at DESC
                    LIMIT :limit";

            $db = $this->db->connect();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            die('Error en modelo ruta (getUserRoutes): ' . $e->getMessage());
        }
    }

    /**
     * Devuelve las rutas públicas de toda la comunidad.
     * Si se pasa $currentUserId incluye 'liked_by_me' (1/0)
     * para saber si el usuario ya dió me gusta a cada ruta.
     * Máximo $limit rutas, ordenadas por fecha descendente.
     */
    public function getCommunityRoutes($currentUserId = null, $limit = 40) {
        try {
            $likedSelect = '0 AS liked_by_me';
            if ($currentUserId) {
                $likedSelect = '(SELECT COUNT(*) FROM route_likes x WHERE x.route_id = r.id AND x.user_id = :current_user_id) AS liked_by_me';
            }

            $sql = "SELECT r.*,
                           u.name AS user_name,
                           u.avatar_path,
                           (SELECT COUNT(*) FROM route_likes rl WHERE rl.route_id = r.id) AS likes_count,
                           {$likedSelect}
                    FROM routes r
                    INNER JOIN users u ON u.id = r.user_id
                    WHERE r.is_public = 1
                    ORDER BY r.created_at DESC
                    LIMIT :limit";

            $db = $this->db->connect();
            $stmt = $db->prepare($sql);
            if ($currentUserId) {
                $stmt->bindParam(':current_user_id', $currentUserId, PDO::PARAM_INT);
            }
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            die('Error en modelo ruta (getCommunityRoutes): ' . $e->getMessage());
        }
    }

    /**
     * Alterna el me gusta del usuario en una ruta:
     *   - Si ya existe → lo elimina y devuelve 'unliked'
     *   - Si no existe → lo inserta y devuelve 'liked'
     *   - Si la ruta no existe → devuelve false
     */
    public function toggleLike($routeId, $userId) {
        try {
            $db = $this->db->connect();

            $routeStmt = $db->prepare('SELECT id FROM routes WHERE id = :route_id LIMIT 1');
            $routeStmt->bindParam(':route_id', $routeId, PDO::PARAM_INT);
            $routeStmt->execute();

            if (!$routeStmt->fetch(PDO::FETCH_ASSOC)) {
                return false;
            }

            $checkStmt = $db->prepare('SELECT id FROM route_likes WHERE route_id = :route_id AND user_id = :user_id LIMIT 1');
            $checkStmt->bindParam(':route_id', $routeId, PDO::PARAM_INT);
            $checkStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $checkStmt->execute();

            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                $deleteStmt = $db->prepare('DELETE FROM route_likes WHERE id = :id');
                $deleteStmt->bindParam(':id', $existing['id'], PDO::PARAM_INT);
                $deleteStmt->execute();
                return 'unliked';
            }

            $insertStmt = $db->prepare('INSERT INTO route_likes (route_id, user_id) VALUES (:route_id, :user_id)');
            $insertStmt->bindParam(':route_id', $routeId, PDO::PARAM_INT);
            $insertStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $insertStmt->execute();

            return 'liked';
        } catch (PDOException $e) {
            die('Error en modelo ruta (toggleLike): ' . $e->getMessage());
        }
    }

    /**
     * Devuelve el número total de me gustas de una ruta.
     * Usado en la respuesta JSON de toggle_like para actualizar el contador.
     */
    public function getLikesCount($routeId) {
        try {
            $db = $this->db->connect();
            $stmt = $db->prepare('SELECT COUNT(*) FROM route_likes WHERE route_id = :route_id');
            $stmt->bindParam(':route_id', $routeId, PDO::PARAM_INT);
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }
}

?>