<?php
if (!defined('ROOT_PATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/dirs.php');
}
require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/DbConfig.php');

class TareaModel {
    private $conn;

    public function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $this->conn->set_charset("utf8mb4");

        if ($this->conn->connect_error) {
            die("Error de conexión: " . $this->conn->connect_error);
        }
    }

    /**
     * Elimina una tarea por su ID.
     *
     * @param int $tareaId El ID de la tarea a eliminar.
     * @return bool True si la eliminación fue exitosa, false en caso contrario.
     */
    public function eliminarTarea($tareaId) {
        try {
            // Antes de eliminar la tarea, debemos eliminar las entregas asociadas
            $sqlDeleteEntregas = "DELETE FROM entregas_tarea WHERE tarea_id = ?";
            $stmtDeleteEntregas = $this->conn->prepare($sqlDeleteEntregas);
            if (!$stmtDeleteEntregas) {
                throw new Exception("Error preparando consulta para eliminar entregas: " . $this->conn->error);
            }
            $stmtDeleteEntregas->bind_param("i", $tareaId);
            $stmtDeleteEntregas->execute();

            // También eliminar notificaciones relacionadas con esta tarea
            $sqlDeleteNotificaciones = "DELETE FROM notificaciones WHERE tarea_id = ?";
            $stmtDeleteNotificaciones = $this->conn->prepare($sqlDeleteNotificaciones);
            if (!$stmtDeleteNotificaciones) {
                throw new Exception("Error preparando consulta para eliminar notificaciones: " . $this->conn->error);
            }
            $stmtDeleteNotificaciones->bind_param("i", $tareaId);
            $stmtDeleteNotificaciones->execute();

            // Ahora eliminar la tarea
            $sql = "DELETE FROM tareas WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error en la consulta SQL para eliminar tarea: " . $this->conn->error);
            }
            
            $stmt->bind_param("i", $tareaId);
            
            if ($stmt->execute()) {
                return $stmt->affected_rows > 0; // Retorna true si se afectó al menos una fila (la tarea fue eliminada)
            } else {
                error_log("Error al ejecutar la eliminación de tarea: " . $stmt->error);
                return false;
            }
        } catch (Exception $e) {
            error_log("Excepción en eliminarTarea: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza los datos de una tarea existente.
     *
     * @param int $tareaId El ID de la tarea a actualizar.
     * @param string $titulo Nuevo título de la tarea.
     * @param string $descripcion Nueva descripción de la tarea.
     * @param string $fechaEntrega Nueva fecha de entrega de la tarea (formato YYYY-MM-DD HH:MM:SS).
     * @param int $materiaId Nuevo ID de la materia.
     * @param int $grupoId Nuevo ID del grupo.
     * @return bool True si la actualización fue exitosa, false en caso contrario.
     */
    public function actualizarTarea($tareaId, $titulo, $descripcion, $fechaEntrega, $materiaId, $grupoId) {
        try {
            $sql = "UPDATE tareas 
                    SET titulo = ?, descripcion = ?, fecha_entrega = ?, materia_id = ?, grupo_id = ? 
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error en la consulta SQL para actualizar tarea: " . $this->conn->error);
            }
            
            // Los tipos de datos deben coincidir con los de la base de datos: (string, string, string, int, int, int)
            $stmt->bind_param("sssiii", $titulo, $descripcion, $fechaEntrega, $materiaId, $grupoId, $tareaId);
            
            if ($stmt->execute()) {
                return $stmt->affected_rows > 0; // Retorna true si se afectó al menos una fila (la tarea fue actualizada)
            } else {
                error_log("Error al ejecutar la actualización de tarea: " . $stmt->error);
                return false;
            }
        } catch (Exception $e) {
            error_log("Excepción en actualizarTarea: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Inserta una nueva tarea y devuelve su ID
     * @return int|bool ID de la tarea insertada o false en caso de error
     */
    public function insertarTarea($titulo, $descripcion, $fechaEntrega, $materiaId, $grupoId, $profesorId) {
        try {
            $estadoId = 1; // Estado "pendiente" por defecto
            $sql = "INSERT INTO tareas (titulo, descripcion, fecha_entrega, materia_id, grupo_id, profesor_id, estado_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            // Registrar datos para debug
            error_log("Insertando tarea: " . json_encode([
                'titulo' => $titulo,
                'fecha' => $fechaEntrega,
                'materia_id' => $materiaId,
                'grupo_id' => $grupoId, 
                'profesor_id' => $profesorId,
                'estado_id' => $estadoId
            ]));
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error en la consulta SQL: " . $this->conn->error);
            }
            
            $stmt->bind_param("sssiiii", $titulo, $descripcion, $fechaEntrega, $materiaId, $grupoId, $profesorId, $estadoId);
            
            if ($stmt->execute()) {
                $insertId = $stmt->insert_id;
                error_log("Tarea insertada con ID: " . $insertId);
                return $insertId; // Devuelve el ID de la tarea recién insertada
            } else {
                error_log("Error al ejecutar la inserción: " . $stmt->error);
                return false;
            }
        } catch (Exception $e) {
            error_log("Excepción en insertarTarea: " . $e->getMessage());
            throw $e;
        }
    }

    public function getTareasConDetalles($estudiante_id) {
        $sql = "SELECT 
                    t.id, t.titulo, t.fecha_creacion, t.fecha_entrega, 
                    et.nombre AS estado_nombre, 
                    m.nombre AS materia_nombre, 
                    g.nombre AS grupo_nombre 
                FROM tareas t
                JOIN materias m ON t.materia_id = m.id
                JOIN grupos g ON t.grupo_id = g.id
                JOIN estudiante_grupo eg ON g.id = eg.grupo_id
                LEFT JOIN estados_tarea et ON t.estado_id = et.id  
                WHERE eg.estudiante_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $estudiante_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getTareasFiltradas($estudiante_id, $materia = null, $estado = null) {
        $sql = "SELECT 
                    t.id, t.titulo, t.fecha_creacion, t.fecha_entrega, 
                    et.nombre AS estado_nombre, 
                    m.nombre AS materia_nombre, 
                    g.nombre AS grupo_nombre 
                FROM tareas t
                JOIN materias m ON t.materia_id = m.id
                JOIN grupos g ON t.grupo_id = g.id
                JOIN estudiante_grupo eg ON g.id = eg.grupo_id
                LEFT JOIN estados_tarea et ON t.estado_id = et.id  
                WHERE eg.estudiante_id = ?";

        $params = [$estudiante_id];
        $types = "i";

        if ($materia) {
            $sql .= " AND m.nombre = ?";
            $params[] = $materia;
            $types .= "s";
        }

        if ($estado) {
            $sql .= " AND et.nombre = ?";
            $params[] = $estado;
            $types .= "s";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getTareasActivas() {
        $sql = "SELECT id, titulo, fecha_entrega FROM tareas WHERE estado_id = 1"; // Asumiendo que 1 es el estado "pendiente"
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getTodasLasTareasConDetalles() {
        $sql = "SELECT t.id, t.titulo, t.fecha_creacion, t.fecha_entrega, et.nombre AS estado_nombre, m.nombre AS materia_nombre, g.nombre AS grupo_nombre FROM tareas t JOIN materias m ON t.materia_id = m.id JOIN grupos g ON t.grupo_id = g.id LEFT JOIN estados_tarea et ON t.estado_id = et.id";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function obtenerTareasPorProfesor($profesorId, $grupoId = null, $materiaId = null, $estadoId = null) {
        $sql = "SELECT t.*, 
                   g.nombre AS grupo_nombre, 
                   m.nombre AS materia_nombre, 
                   et.nombre AS estado_nombre
                FROM tareas t
                INNER JOIN grupos g ON t.grupo_id = g.id
                INNER JOIN materias m ON t.materia_id = m.id
                INNER JOIN estados_tarea et ON t.estado_id = et.id
                WHERE t.profesor_id = ?";
        
        $params = [$profesorId];
        $types = "i";
        
        if ($grupoId) {
            $sql .= " AND t.grupo_id = ?";
            $params[] = $grupoId;
            $types .= "i";
        }
        
        if ($materiaId) {
            $sql .= " AND t.materia_id = ?";
            $params[] = $materiaId;
            $types .= "i";
        }
        
        if ($estadoId) {
            $sql .= " AND t.estado_id = ?";
            $params[] = $estadoId;
            $types .= "i";
        }
        
        $sql .= " ORDER BY t.fecha_entrega ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function obtenerEntregasPorTarea($tareaId) {
        $sql = "SELECT et.*, 
                   u.nombre AS estudiante_nombre, 
                   u.apellidos AS estudiante_apellidos,
                   est.nombre AS estado
                FROM entregas_tarea et
                INNER JOIN usuarios u ON et.estudiante_id = u.id
                INNER JOIN estados_tarea est ON et.estado_id = est.id
                WHERE et.tarea_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $tareaId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function contarEstudiantesPorGrupo($grupoId) {
        $sql = "SELECT COUNT(*) AS total FROM estudiante_grupo WHERE grupo_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $grupoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['total'];
    }
    
    public function obtenerEstadoTarea($tareaId) {
        $sql = "SELECT et.nombre 
                FROM tareas t
                INNER JOIN estados_tarea et ON t.estado_id = et.id
                WHERE t.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $tareaId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    public function obtenerGruposPorProfesor($profesorId) {
        $sql = "SELECT g.* 
                FROM grupos g
                INNER JOIN profesor_grupo pg ON g.id = pg.grupo_id
                WHERE pg.profesor_id = ? AND g.activo = 1
                ORDER BY g.nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $profesorId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function obtenerMateriasPorProfesor($profesorId) {
        $sql = "SELECT DISTINCT m.* 
                FROM materias m
                INNER JOIN grupo_materia gm ON m.id = gm.materia_id
                WHERE gm.profesor_id = ? AND m.activo = 1
                ORDER BY m.nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $profesorId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Actualiza la información de una entrega tras la calificación
     */
    public function actualizarEntrega($datos) {
        // Revisar en la base de datos si la columna se llama calificacion o nota
        $sql = "DESCRIBE entregas_tarea";
        $result = $this->conn->query($sql);
        $columnExists = false;
        $columnName = "calificacion"; // Nombre por defecto
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if ($row['Field'] === 'calificacion') {
                    $columnExists = true;
                    break;
                } else if ($row['Field'] === 'nota') {
                    $columnName = "nota";
                    $columnExists = true;
                    break;
                }
            }
        }
        
        // Si no existe la columna, intentamos crearla
        if (!$columnExists) {
            $alterSql = "ALTER TABLE entregas_tarea ADD COLUMN $columnName DECIMAL(5,2) NULL";
            $this->conn->query($alterSql);
        }
        
        // Ahora procedemos a actualizar usando el nombre de columna correcto
        $sql = "UPDATE entregas_tarea 
                SET estado_id = ?, 
                    $columnName = ?, 
                    comentarios = ? 
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("idsi", $datos['estado_id'], $datos['calificacion'], $datos['comentarios'], $datos['id']);
            return $stmt->execute();
        }
        
        return false;
    }
    
    public function actualizarEstadoTarea($tareaId, $estadoId) {
        $sql = "UPDATE tareas SET estado_id = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $estadoId, $tareaId);
        
        return $stmt->execute();
    }

    // Añadir métodos para estadísticas

    /**
     * Cuenta el número total de tareas en el sistema
     */
    public function contarTodasLasTareas() {
        $query = "SELECT COUNT(*) as total FROM tareas";
        $result = $this->conn->query($query);
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }

    /**
     * Cuenta el número de tareas activas
     */
    public function contarTareasActivas() {
        // Ajusta esta consulta según la estructura de tu tabla de tareas
        $query = "SELECT COUNT(*) as total FROM tareas WHERE estado_id != 4"; // Asumiendo que estado_id=4 es para tareas archivadas o eliminadas
        $result = $this->conn->query($query);
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }

    /**
     * Cuenta el número de tareas según su estado
     * @param string $estado Nombre del estado a contar
     */
    public function contarTareasPorEstado($estado) {
        $query = "SELECT COUNT(t.id) as total FROM tareas t 
                  JOIN estados_tarea e ON t.estado_id = e.id 
                  WHERE e.nombre = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $estado);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }

    /**
     * Obtiene los estudiantes de un grupo específico
     * @param int $grupoId ID del grupo
     * @return array Lista de IDs de estudiantes
     */
    public function obtenerEstudiantesPorGrupo($grupoId) {
        $sql = "SELECT estudiante_id FROM estudiante_grupo WHERE grupo_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $grupoId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $estudiantes = [];
        while ($row = $result->fetch_assoc()) {
            $estudiantes[] = $row['estudiante_id'];
        }
        
        return $estudiantes;
    }

    /**
     * Obtiene una tarea específica por su ID
     * @param int $tareaId ID de la tarea
     * @return array|null Datos de la tarea o null si no existe
     */
    public function obtenerTareaPorId($tareaId) {
        $sql = "SELECT t.*, m.nombre AS materia_nombre, g.nombre AS grupo_nombre, e.nombre AS estado_nombre
                FROM tareas t
                INNER JOIN materias m ON t.materia_id = m.id
                INNER JOIN grupos g ON t.grupo_id = g.id
                INNER JOIN estados_tarea e ON t.estado_id = e.id
                WHERE t.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $tareaId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    /**
     * Registra una nueva entrega de tarea
     * @param array $datos Datos de la entrega
     * @return bool Éxito de la operación
     */
    public function registrarEntrega($datos) {
        // Establecer estado inicial (Entregada)
        $estadoEntregada = 2; // Asumiendo que 2 es el estado "entregada" o "en_progreso"
        
        $sql = "INSERT INTO entregas_tarea (tarea_id, estudiante_id, estado_id, comentarios, archivo_adjunto, fecha_entrega) 
                VALUES (?, ?, ?, ?, ?, NOW())";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiiss", 
            $datos['tarea_id'], 
            $datos['estudiante_id'],
            $estadoEntregada,
            $datos['comentarios'],
            $datos['archivo_adjunto']
        );
        
        return $stmt->execute();
    }

    /**
     * Verifica si un estudiante ya ha entregado una tarea y devuelve los detalles
     * @param int $tareaId ID de la tarea
     * @param int $estudianteId ID del estudiante
     * @return array|null Datos de la entrega o null si no existe
     */
    public function obtenerEntregaPorEstudiante($tareaId, $estudianteId) {
        $sql = "SELECT et.*, est.nombre AS estado 
                FROM entregas_tarea et
                INNER JOIN estados_tarea est ON et.estado_id = est.id
                WHERE et.tarea_id = ? AND et.estudiante_id = ?";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $tareaId, $estudianteId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
}
?>
