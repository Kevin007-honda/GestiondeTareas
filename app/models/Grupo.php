<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/DbConfig.php');

class Grupo {
    private $conn;

    public function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $this->conn->set_charset("utf8mb4");
    }

    public function obtenerTodos() {
        $sql = "SELECT g.*, 
                   COUNT(DISTINCT eg.estudiante_id) as total_estudiantes,
                   u.nombre as profesor_nombre, 
                   u.apellidos as profesor_apellidos
                FROM grupos g
                LEFT JOIN profesor_grupo pg ON g.id = pg.grupo_id
                LEFT JOIN usuarios u ON pg.profesor_id = u.id AND u.activo = 1
                LEFT JOIN estudiante_grupo eg ON g.id = eg.grupo_id
                GROUP BY g.id
                ORDER BY g.nombre";
                
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function crearGrupo($nombre, $descripcion, $profesor_id) {
        try {
            $this->conn->begin_transaction();

            $stmt = $this->conn->prepare("INSERT INTO grupos (nombre, descripcion) VALUES (?, ?)");
            $stmt->bind_param("ss", $nombre, $descripcion);
            $stmt->execute();
            $grupo_id = $this->conn->insert_id;

            if ($profesor_id) {
                $stmt = $this->conn->prepare("INSERT INTO profesor_grupo (profesor_id, grupo_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $profesor_id, $grupo_id);
                $stmt->execute();
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function actualizarGrupo($id, $nombre, $descripcion, $profesor_id) {
        try {
            $this->conn->begin_transaction();

            $stmt = $this->conn->prepare("UPDATE grupos SET nombre = ?, descripcion = ? WHERE id = ?");
            $stmt->bind_param("ssi", $nombre, $descripcion, $id);
            $stmt->execute();

            // Actualizar profesor asignado
            $stmt = $this->conn->prepare("DELETE FROM profesor_grupo WHERE grupo_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            if ($profesor_id) {
                $stmt = $this->conn->prepare("INSERT INTO profesor_grupo (profesor_id, grupo_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $profesor_id, $id);
                $stmt->execute();
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function cambiarEstado($id, $activo) {
        $stmt = $this->conn->prepare("UPDATE grupos SET activo = ? WHERE id = ?");
        $stmt->bind_param("ii", $activo, $id);
        return $stmt->execute();
    }

    public function obtenerEstadisticas() {
        $stats = [];
        
        // Total de grupos
        $result = $this->conn->query("SELECT COUNT(*) as total FROM grupos WHERE activo = 1");
        $stats['total_grupos'] = $result->fetch_assoc()['total'];

        // Total de estudiantes
        $result = $this->conn->query("SELECT COUNT(DISTINCT estudiante_id) as total FROM estudiante_grupo");
        $stats['total_estudiantes'] = $result->fetch_assoc()['total'];

        // Total de profesores
        $result = $this->conn->query("SELECT COUNT(DISTINCT profesor_id) as total FROM profesor_grupo");
        $stats['total_profesores'] = $result->fetch_assoc()['total'];

        // Total de materias activas
        $result = $this->conn->query("SELECT COUNT(*) as total FROM materias");
        $stats['total_materias'] = $result->fetch_assoc()['total'];

        return $stats;
    }

    public function obtenerEstudiantesNoMatriculados($grupo_id) {
        $sql = "SELECT u.* FROM usuarios u 
                INNER JOIN roles r ON u.rol_id = r.id 
                WHERE r.nombre = 'estudiante' 
                AND u.activo = 1 
                AND u.id NOT IN (
                    SELECT estudiante_id 
                    FROM estudiante_grupo 
                    WHERE grupo_id = ?
                )
                ORDER BY u.nombre, u.apellidos";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $grupo_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function obtenerEstudiantesMatriculados($grupo_id) {
        $sql = "SELECT u.* FROM usuarios u 
                INNER JOIN estudiante_grupo eg ON u.id = eg.estudiante_id 
                WHERE eg.grupo_id = ? 
                ORDER BY u.nombre, u.apellidos";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $grupo_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function matricularEstudiante($grupo_id, $estudiante_id) {
        try {
            // Verificar que el grupo exista y esté activo
            $stmt = $this->conn->prepare("SELECT activo FROM grupos WHERE id = ?");
            $stmt->bind_param("i", $grupo_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("El grupo no existe");
            }
            
            $grupo = $result->fetch_assoc();
            if (!$grupo['activo']) {
                throw new Exception("El grupo no está activo");
            }

            // Verificar si ya está matriculado
            $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM estudiante_grupo 
                WHERE grupo_id = ? AND estudiante_id = ?");
            $stmt->bind_param("ii", $grupo_id, $estudiante_id);
            $stmt->execute();
            $count = $stmt->get_result()->fetch_assoc()['total'];
            
            if ($count > 0) {
                return true; // Ya está matriculado
            }

            // Matricular estudiante
            $stmt = $this->conn->prepare("INSERT INTO estudiante_grupo (grupo_id, estudiante_id) 
                VALUES (?, ?)");
            $stmt->bind_param("ii", $grupo_id, $estudiante_id);
            $result = $stmt->execute();

            if (!$result) {
                throw new Exception("Error al insertar en la base de datos");
            }

            return true;
        } catch (Exception $e) {
            error_log("Error en matriculación: " . $e->getMessage());
            throw new Exception("Error al matricular estudiante: " . $e->getMessage());
        }
    }

    public function desmatricularEstudiante($grupo_id, $estudiante_id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM estudiante_grupo WHERE estudiante_id = ? AND grupo_id = ?");
            $stmt->bind_param("ii", $estudiante_id, $grupo_id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error desmatriculando estudiante: " . $e->getMessage());
            throw $e;
        }
    }

    public function obtenerPorId($id) {
        $stmt = $this->conn->prepare("
            SELECT g.*, pg.profesor_id 
            FROM grupos g
            LEFT JOIN profesor_grupo pg ON g.id = pg.grupo_id
            WHERE g.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function obtenerMaterias($grupo_id) {
        $sql = "SELECT m.*, u.nombre as profesor_nombre, u.apellidos as profesor_apellidos, 
                gm.activo as asignacion_activa, gm.profesor_id
                FROM materias m
                LEFT JOIN grupo_materia gm ON m.id = gm.materia_id AND gm.grupo_id = ?
                LEFT JOIN usuarios u ON gm.profesor_id = u.id
                ORDER BY m.nombre";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $grupo_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function asignarMateria($grupo_id, $materia_id, $profesor_id) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO grupo_materia (grupo_id, materia_id, profesor_id) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE profesor_id = ?, activo = TRUE");
            $stmt->bind_param("iiii", $grupo_id, $materia_id, $profesor_id, $profesor_id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error asignando materia: " . $e->getMessage());
            throw $e;
        }
    }

    public function desasignarMateria($grupo_id, $materia_id) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE grupo_materia 
                SET activo = FALSE 
                WHERE grupo_id = ? AND materia_id = ?");
            $stmt->bind_param("ii", $grupo_id, $materia_id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error desasignando materia: " . $e->getMessage());
            throw $e;
        }
    }

    // Asegurar que estos métodos devuelven valores correctos
    public function contarEstudiantes() {
        $sql = "SELECT COUNT(DISTINCT estudiante_id) as total FROM estudiante_grupo";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return $row['total'];
    }
    
    public function contarProfesores() {
        $sql = "SELECT COUNT(DISTINCT profesor_id) as total FROM profesor_grupo WHERE activo = 1";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return $row['total'];
    }
    
    public function contarMaterias() {
        $sql = "SELECT COUNT(DISTINCT materia_id) as total FROM grupo_materia WHERE activo = 1";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return $row['total'];
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

    /**
     * Cuenta el número de estudiantes en un grupo específico
     * @param int $grupoId ID del grupo
     * @return int Número de estudiantes en el grupo
     */
    public function contarEstudiantesPorGrupo($grupoId) {
        $sql = "SELECT COUNT(*) AS total FROM estudiante_grupo WHERE grupo_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $grupoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['total'] ?? 0;
    }
}
