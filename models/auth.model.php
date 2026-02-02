<?php

class authModel extends Model {

    /*
        Método: get_user_email($email)
        Descripción: obtiene los detalles de un usuario a partir del email
        Parámetros: 
            - email
        Devuelve:
            - Objeto de la clase user
                - id
                - name
                - email
                - password
            - False. Si el email no corresponde a ningún usuario
    */
    public function get_user_email($email) {
        try {
        // Generamos select 
        $sql = "SELECT id, name, email, password FROM users WHERE email = :email LIMIT 1";
        // Conectar con la base de datos
        $fp = $this->db->connect();
        // Preparar la consulta obteniendo el objeto PDOStatement
        $stmt = $fp->prepare($sql);
        // Tipo fetch
         $stmt->setFetchMode(PDO::FETCH_OBJ);
        // Vincular los parámetros
        $stmt->bindParam(':email', $email, PDO::PARAM_STR, 50);
        // Ejecutamos sql
        $stmt->execute();
        // Devolvemos el objeto o falso
        return $stmt->fetch();
        
        } catch (PDOException $e) {
            // Manejo del error
            die("Error en modelo auth: " . $e->getMessage());
        }
    }

}

?>
