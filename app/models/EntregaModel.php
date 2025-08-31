<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/DbConfig.php');

class EntregaModel {
    private $db;

    public function __construct() {
        $this->db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $this->db->set_charset("utf8mb4");

        if ($this->db->connect_error) {
            die("Error de conexión: " . $this->db->connect_error);
        }
    }

    public function registrarEntrega($tareaId, $estudianteId, $archivoAdjunto) {
        $sql = "INSERT INTO entregas_tareas (tarea_id, estudiante_id, estado_id, archivo_adjunto) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $estadoId = 2; // 2 = "Entregado" (ajústalo según tu BD)
        $stmt->bind_param("iiis", $tareaId, $estudianteId, $estadoId, $archivoAdjunto);
        $resultado = $stmt->execute();

        if ($resultado) {
            $this->actualizarEstadoTarea($tareaId);
        }

        return $resultado;
    }

    private function actualizarEstadoTarea($tareaId) {
        $sql = "UPDATE tareas SET estado_id = 2 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $tareaId);
        return $stmt->execute();
    }

    // Añadir métodos para estadísticas

    /**
     * Cuenta el número total de entregas en el sistema
     */
    public function contarTodasLasEntregas() {
        $query = "SELECT COUNT(*) as total FROM entregas_tarea"; // Ajustar nombre de tabla según tu BD
        $result = $this->db->query($query);
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }

    /**
     * Cuenta el número de entregas que ya han sido calificadas
     */
    public function contarEntregasCalificadas() {
        // Revisar en la base de datos si la columna se llama calificacion o nota
        $sql = "DESCRIBE entregas_tarea";
        $result = $this->db->query($sql);
        $columnName = "calificacion"; // Nombre por defecto
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if ($row['Field'] === 'nota') {
                    $columnName = "nota";
                    break;
                }
            }
        }
        
        $query = "SELECT COUNT(*) as total FROM entregas_tarea WHERE $columnName IS NOT NULL";
        $result = $this->db->query($query);
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }
}
?>
