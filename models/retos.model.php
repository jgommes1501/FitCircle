<?php

/**
 * ============================================================
 * MODELO DE RETOS — models/retos.model.php
 * ============================================================
 * Consultas a BD del sistema de retos.
 * Trabaja sobre las tablas 'challenges' y 'challenge_participants'.
 *
 * Esquema de tablas:
 *   challenges:
 *     id, user_id (creador), title, description,
 *     type ENUM('km','pasos'), period ENUM('semanal','mensual'),
 *     goal (objetivo numérico), is_public, starts_at, ends_at
 *
 *   challenge_participants:
 *     challenge_id, user_id, progress
 *     UNIQUE (challenge_id, user_id) — un usuario no puede estar dos veces
 *
 * Métodos:
 *   ensureSchema()          → Crea las tablas si no existen
 *   getPublicChallenges($u) → Retos públicos con info de participación
 *   getMyChallenges($u)     → Retos en los que participa el usuario
 *   createChallenge(...)    → Crea reto e inscribe al creador
 *   joinChallenge(...)      → Inscribe al usuario en un reto
 *   leaveChallenge(...)     → Elimina al usuario del reto (no al creador)
 *   updateProgress(...)     → Actualiza el progreso del participante
 *   deleteChallenge(...)    → Elimina el reto (solo el creador)
 * ============================================================
 *  -  Consultas de retos, participantes.
 */

class retosModel extends Model {

    /**
     * Crea las tablas 'challenges' y 'challenge_participants' si no existen.
     * Se ejecuta en el constructor del controlador de retos.
     */
    public function ensureSchema() {
        try {
            $db = $this->db->connect();

            $db->exec("CREATE TABLE IF NOT EXISTS challenges (
                id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                user_id INT UNSIGNED NOT NULL,
                title VARCHAR(150) NOT NULL,
                description TEXT NULL,
                type ENUM('pasos','km') NOT NULL DEFAULT 'km',
                period ENUM('semanal','mensual') NOT NULL DEFAULT 'mensual',
                goal DECIMAL(10,2) NOT NULL,
                is_public TINYINT(1) NOT NULL DEFAULT 1,
                starts_at DATE NOT NULL,
                ends_at DATE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_challenges_user_id (user_id),
                INDEX idx_challenges_public (is_public)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

            $db->exec("CREATE TABLE IF NOT EXISTS challenge_participants (
                id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                challenge_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                progress DECIMAL(10,2) NOT NULL DEFAULT 0,
                joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_participant (challenge_id, user_id),
                INDEX idx_cp_challenge (challenge_id),
                INDEX idx_cp_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        } catch (PDOException $e) {
            die('Error en modelo retos (ensureSchema): ' . $e->getMessage());
        }
    }

    /**
     * Obtiene todos los retos públicos con:
     *   - Nombre del creador, número de participantes
     *   - joined: si el usuario actual ya está apuntado
     *   - my_progress: el progreso del usuario en ese reto
     * Si $userId es null (invitado), joined=0 y my_progress=NULL.
     */
    public function getPublicChallenges($userId = null) {
        try {
            $db = $this->db->connect();
            $sql = "SELECT c.*,
                        u.name AS creator_name,
                        COUNT(DISTINCT cp.user_id) AS participant_count,
                        " . ($userId ? "MAX(CASE WHEN cp.user_id = :uid THEN 1 ELSE 0 END) AS joined" : "0 AS joined") . ",
                        " . ($userId ? "MAX(CASE WHEN cp.user_id = :uid2 THEN cp.progress ELSE NULL END) AS my_progress" : "NULL AS my_progress") . "
                    FROM challenges c
                    JOIN users u ON u.id = c.user_id
                    LEFT JOIN challenge_participants cp ON cp.challenge_id = c.id
                    WHERE c.is_public = 1
                    GROUP BY c.id
                    ORDER BY c.created_at DESC";

            $stmt = $db->prepare($sql);
            if ($userId) {
                $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
                $stmt->bindValue(':uid2', $userId, PDO::PARAM_INT);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            die('Error en modelo retos (getPublicChallenges): ' . $e->getMessage());
        }
    }

    /**
     * Obtiene todos los retos en los que participa el usuario:
     * tanto los creados por él como los que se unió.
     * Ordenados por fecha de fin más próxima.
     */
    public function getMyChallenges($userId) {
        try {
            $db = $this->db->connect();
            $sql = "SELECT c.*,
                        u.name AS creator_name,
                        COUNT(DISTINCT cp_all.user_id) AS participant_count,
                        cp_me.progress AS my_progress
                    FROM challenges c
                    JOIN users u ON u.id = c.user_id
                    JOIN challenge_participants cp_me ON cp_me.challenge_id = c.id AND cp_me.user_id = :uid
                    LEFT JOIN challenge_participants cp_all ON cp_all.challenge_id = c.id
                    GROUP BY c.id, cp_me.progress
                    ORDER BY c.ends_at ASC";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            die('Error en modelo retos (getMyChallenges): ' . $e->getMessage());
        }
    }

    /**
     * Crea un nuevo reto en BD e inscribe automáticamente al creador.
     * Usa transacción para garantizar que ambas inserciones se completan.
     * Devuelve el ID del nuevo reto.
     */
    public function createChallenge($userId, $title, $description, $type, $period, $goal, $isPublic, $startsAt, $endsAt) {
        try {
            $db = $this->db->connect();
            $db->beginTransaction();

            $sql = "INSERT INTO challenges (user_id, title, description, type, period, goal, is_public, starts_at, ends_at)
                    VALUES (:uid, :title, :desc, :type, :period, :goal, :public, :starts, :ends)";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':title', $title, PDO::PARAM_STR);
            $stmt->bindValue(':desc', $description, PDO::PARAM_STR);
            $stmt->bindValue(':type', $type, PDO::PARAM_STR);
            $stmt->bindValue(':period', $period, PDO::PARAM_STR);
            $stmt->bindValue(':goal', $goal);
            $stmt->bindValue(':public', $isPublic, PDO::PARAM_INT);
            $stmt->bindValue(':starts', $startsAt, PDO::PARAM_STR);
            $stmt->bindValue(':ends', $endsAt, PDO::PARAM_STR);
            $stmt->execute();

            $challengeId = $db->lastInsertId();

            // Auto-inscribir al creador
            $db->prepare("INSERT INTO challenge_participants (challenge_id, user_id) VALUES (:cid, :uid)")
               ->execute([':cid' => $challengeId, ':uid' => $userId]);

            $db->commit();
            return $challengeId;
        } catch (PDOException $e) {
            if (isset($db)) $db->rollBack();
            die('Error en modelo retos (createChallenge): ' . $e->getMessage());
        }
    }

    /**
     * Inscribe al usuario en un reto.
     * INSERT IGNORE: si ya estaba inscrito no hace nada (no da error).
     * Devuelve true si se insertó, false si ya existía.
     */
    public function joinChallenge($challengeId, $userId) {
        try {
            $db = $this->db->connect();
            $stmt = $db->prepare("INSERT IGNORE INTO challenge_participants (challenge_id, user_id) VALUES (:cid, :uid)");
            $stmt->bindValue(':cid', $challengeId, PDO::PARAM_INT);
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            die('Error en modelo retos (joinChallenge): ' . $e->getMessage());
        }
    }

    /**
     * Elimina al usuario de un reto.
     * Si el usuario es el creador devuelve false (debe eliminar el reto).
     */
    public function leaveChallenge($challengeId, $userId) {
        try {
            $db = $this->db->connect();
            // Verifica si el usuario es el creador del reto
            $check = $db->prepare("SELECT id FROM challenges WHERE id = :cid AND user_id = :uid LIMIT 1");
            $check->execute([':cid' => $challengeId, ':uid' => $userId]);
            if ($check->fetch()) {
                return false; // Es el creador, no puede salir
            }

            $stmt = $db->prepare("DELETE FROM challenge_participants WHERE challenge_id = :cid AND user_id = :uid");
            $stmt->execute([':cid' => $challengeId, ':uid' => $userId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            die('Error en modelo retos (leaveChallenge): ' . $e->getMessage());
        }
    }

    /**
     * Actualiza el campo 'progress' del participante.
     * El valor es el total acumulado (no un incremento).
     * Devuelve true si se actualizó alguna fila.
     */
    public function updateProgress($challengeId, $userId, $progress) {
        try {
            $db = $this->db->connect();
            $stmt = $db->prepare("UPDATE challenge_participants SET progress = :progress
                                  WHERE challenge_id = :cid AND user_id = :uid");
            $stmt->bindValue(':progress', $progress);
            $stmt->bindValue(':cid', $challengeId, PDO::PARAM_INT);
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            die('Error en modelo retos (updateProgress): ' . $e->getMessage());
        }
    }

    /**
     * Elimina el reto de la BD.
     * Solo funciona si el user_id coincide con el creador del reto.
     * Devuelve true si se eliminó.
     */
    public function deleteChallenge($challengeId, $userId) {
        try {
            $db = $this->db->connect();
            $stmt = $db->prepare("DELETE FROM challenges WHERE id = :cid AND user_id = :uid");
            $stmt->execute([':cid' => $challengeId, ':uid' => $userId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            die('Error en modelo retos (deleteChallenge): ' . $e->getMessage());
        }
    }
}

?>
