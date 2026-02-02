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

            die("Error de conexión a Base de Datos: " . $e->getMessage());
         
        }

    }

}

?>
