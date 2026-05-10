<?php

/**
 * ============================================================
 * CLASE BASE MODEL — libs/model.php
 * ============================================================
 * Todos los modelos heredan de esta clase. Al crearse,
 * instancia automáticamente la clase Database para poder
 * conectarse a MySQL. Los modelos usan $this->db->connect()
 * para obtener un objeto PDO y ejecutar consultas SQL.
 * ============================================================
 */
class Model {

    // Objeto Database disponible en todos los modelos hijos
    public $db;

    public function __construct() {
        // Crea el gestor de conexión a la base de datos
        $this->db = new Database();
    }

}

?>
