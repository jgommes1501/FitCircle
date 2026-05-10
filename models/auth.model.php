<?php

/**
 * ============================================================
 * MODELO DE AUTENTICACIÓN — models/auth.model.php
 * ============================================================
 * Consultas a BD relacionadas con el registro e inicio de sesión.
 * Trabaja sobre la tabla 'users'.
 *
 * Métodos:
 *   get_user_email($email) → Busca un usuario por email
 *   email_exists($email)   → Comprueba si un email ya está registrado
 *   create_user(...)       → Inserta un nuevo usuario en la BD
 * ============================================================
 */

class authModel extends Model {

    /**
     * Busca un usuario por su dirección de email.
     * Usado en el proceso de login para verificar credenciales.
     * Devuelve un objeto con (id, name, email, password) o false si no existe.
     */
    public function get_user_email($email) {
        try {
            // Selecciona solo los campos necesarios para el login
            $sql = "SELECT id, name, email, password FROM users WHERE email = :email LIMIT 1";
            // Obtiene la conexión PDO
            $fp = $this->db->connect();
            // Prepara la consulta (evita inyección SQL)
            $stmt = $fp->prepare($sql);
            // Configura el modo de fetch para obtener un objeto
            $stmt->setFetchMode(PDO::FETCH_OBJ);
            // Vincula el parámetro email de forma segura
            $stmt->bindParam(':email', $email, PDO::PARAM_STR, 50);
            $stmt->execute();
            // Devuelve el objeto usuario o false si no se encontró
            return $stmt->fetch();

        } catch (PDOException $e) {
            die("Error en modelo auth: " . $e->getMessage());
        }
    }

    /**
     * Comprueba si ya existe una cuenta con ese email.
     * Usado en el registro para evitar duplicados.
     * Devuelve true si el email ya está registrado, false si está disponible.
     */
    public function email_exists($email) {
        try {
            $sql = "SELECT id FROM users WHERE email = :email LIMIT 1";
            $fp = $this->db->connect();
            $stmt = $fp->prepare($sql);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR, 100);
            $stmt->execute();
            // fetchColumn devuelve el primer campo; el cast a bool da true/false
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            die("Error en modelo auth (email_exists): " . $e->getMessage());
        }
    }

    /**
     * Inserta un nuevo usuario en la base de datos.
     * La contraseña debe llegar ya hasheada con bcrypt (password_hash).
     * Devuelve el ID del nuevo usuario.
     */
    public function create_user($name, $email, $passwordHash) {
        try {
            $sql = "INSERT INTO users (name, email, password) VALUES (:name, :email, :password)";
            $fp = $this->db->connect();
            $stmt = $fp->prepare($sql);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR, 100);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR, 100);
            $stmt->bindParam(':password', $passwordHash, PDO::PARAM_STR, 60);

            return $stmt->execute();
        } catch (PDOException $e) {
            die("Error en modelo auth (create_user): " . $e->getMessage());
        }
    }

}

?>
