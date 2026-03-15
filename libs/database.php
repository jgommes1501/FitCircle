<?php

class Database {

    private $host;
    private $db;
    private $user;
    private $password;
    private $charset;
    
    public function __construct() {

        $this->host = HOST;
        $this->db = DB;
        $this->user = USER;
        $this->password = PASSWORD;
        $this->charset = CHARSET;

    }

    public function connect() {

        try {
            
            $dbh = "mysql:host=".$this->host.";dbname=".$this->db;
            $charset = $this->charset;
            $opciones = [

                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => FALSE,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"
            ];

            $pdo = new PDO($dbh, $this->user, $this->password, $opciones);
            
            return $pdo;
        
        } catch(PDOException $e) {
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
