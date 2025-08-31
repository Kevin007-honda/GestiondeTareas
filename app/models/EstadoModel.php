<?php
if (!defined('ROOT_PATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/dirs.php');
}
require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/DbConfig.php');

class EstadoModel {
    private $conn;

    public function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $this->conn->set_charset("utf8mb4");
    }

    public function obtenerTodos() {
        $sql = "SELECT * FROM estados_tarea ORDER BY id";
        $result = $this->conn->query($sql);
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function obtenerPorNombre($nombre) {
        $sql = "SELECT * FROM estados_tarea WHERE nombre = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    public function obtenerPorId($id) {
        $sql = "SELECT * FROM estados_tarea WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    /**
     * Obtiene los estados con el conteo de tareas en cada uno
     */
    public function obtenerEstadosConConteo() {
        $query = "SELECT e.nombre as estado, COUNT(t.id) as conteo 
                  FROM estados_tarea e 
                  LEFT JOIN tareas t ON e.id = t.estado_id 
                  GROUP BY e.id, e.nombre
                  ORDER BY conteo DESC";
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>
