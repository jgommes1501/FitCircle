<?php

/**
 * ============================================================
 * CONEXIÓN A BASE DE DATOS — libs/database.php
 * ============================================================
 * Esta clase gestiona la conexión a MySQL mediante PDO.
 * Las credenciales se leen de las constantes definidas en
 * config/config.php (HOST, DB, USER, PASSWORD, CHARSET).
 * Cada vez que un modelo necesita consultar la BD llama a
 * $this->db->connect() y obtiene un objeto PDO listo.
 * ============================================================
 */
class Database {

    // Datos de conexión leídos de las constantes de configuración
    private $host;
    private $db;
    private $user;
    private $password;
    private $charset;
    
    public function __construct() {
        // Asigna las constantes de config.php a propiedades privadas
        $this->host     = HOST;
        $this->db       = DB;
        $this->user     = USER;
        $this->password = PASSWORD;
        $this->charset  = CHARSET;
    }

    /**
     * Abre y devuelve una conexión PDO a MySQL.
     * Lanza un error detallado si la conexión falla.
     * Opciones importantes:
     *   - ERRMODE_EXCEPTION: los errores SQL lanzan excepciones (no avisos silenciosos)
     *   - EMULATE_PREPARES FALSE: usa prepared statements reales del servidor MySQL
     *   - SET NAMES utf8: garantiza que los textos con tildes y ñ se guardan bien
     */
    public function connect() {

        try {
            
            $dbh = "mysql:host=".$this->host.";dbname=".$this->db;
            $charset = $this->charset;
            $opciones = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Errores como excepciones
                PDO::ATTR_EMULATE_PREPARES   => FALSE,                   // Prepared statements reales
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"       // Codificación UTF-8
            ];

            $pdo = new PDO($dbh, $this->user, $this->password, $opciones);
            
            return $pdo;
        
        } catch(PDOException $e) {
            // Mensaje especial para hosting InfinityFree donde la configuración puede diferir
            $isInfinityFree = isset($_SERVER['HTTP_HOST']) && stripos($_SERVER['HTTP_HOST'], 'infinityfreeapp.com') !== false;
            if ($isInfinityFree) {
                die(
                    "Error de conexión a Base de Datos: " . $e->getMessage() .
                    " | Revisa DB_HOST, DB_NAME, DB_USER y DB_PASSWORD en config/config.php con los datos de MySQL de InfinityFree."
                );
            }

            die("Error de conexión a Base de Datos: " . $e->getMessage());
         
        }

    }

}

?>
