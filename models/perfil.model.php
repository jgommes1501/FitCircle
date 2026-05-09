<?php

class perfilModel extends Model {

    public function ensureProfileSchema() {
        try {
            $db = $this->db->connect();

            $avatarColumn = $db->query("SHOW COLUMNS FROM users LIKE 'avatar_path'")->fetch(PDO::FETCH_ASSOC);
            if (!$avatarColumn) {
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