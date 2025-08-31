<?php
if (!defined('ROOT_PATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/dirs.php');
}
require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/DbConfig.php');

class Materia {
    private $conn;

    public function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $this->conn->set_charset("utf8mb4");
    }

    public function contarMaterias($soloActivas = false) {
        $sql = "SELECT COUNT(*) as total FROM materias";
        if ($soloActivas) {
            $sql .= " WHERE activo = TRUE";
        }
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return $row['total'];
    }

    public function contarGruposAsignados() {
        $sql = "SELECT COUNT(*) as total FROM profesor_materia";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return $row['total'];
    }

    public function contarProfesoresAsignados() {
        $sql = "SELECT COUNT(DISTINCT profesor_id) as total FROM profesor_materia";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return $row['total'];
    }

    public function contarTareasAsignadas() {
        $sql = "SELECT COUNT(*) as total FROM tareas";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return $row['total'];
    }

    public function obtenerTodas() {
        $sql = "SELECT m.*, 
                   COUNT(DISTINCT gm.grupo_id) as total_grupos
                FROM materias m
                LEFT JOIN grupo_materia gm ON m.id = gm.materia_id
                GROUP BY m.id
                ORDER BY m.nombre";
                
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function obtenerPorId($id) {
        $stmt = $this->conn->prepare("SELECT * FROM materias WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function crear($datos) {
        try {
            // Verificar si el código ya existe
            $stmt = $this->conn->prepare("SELECT id FROM materias WHERE codigo = ?");
            $stmt->bind_param("s", $datos['codigo']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                return ['error' => 'Ya existe una materia con ese código'];
            }
            
            // Insertar la nueva materia
            $stmt = $this->conn->prepare("INSERT INTO materias (nombre, codigo, descripcion) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $datos['nombre'], $datos['codigo'], $datos['descripcion']);
            
            if ($stmt->execute()) {
                return ['success' => true, 'id' => $this->conn->insert_id];
            } else {
                return ['error' => 'Error al insertar en la base de datos: ' . $this->conn->error];
            }
        } catch (Exception $e) {
            return ['error' => 'Error al crear la materia: ' . $e->getMessage()];
        }
    }

    public function actualizar($datos) {
        try {
            // Verificar que no exista otra materia con el mismo código
            $stmt = $this->conn->prepare("SELECT id FROM materias WHERE codigo = ? AND id != ?");
            $stmt->bind_param("si", $datos['codigo'], $datos['id']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                return false;
            }
            
            $stmt = $this->conn->prepare("UPDATE materias SET nombre = ?, codigo = ?, descripcion = ? WHERE id = ?");
            $stmt->bind_param("sssi", $datos['nombre'], $datos['codigo'], $datos['descripcion'], $datos['id']);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error actualizando materia: " . $e->getMessage());
            return false;
        }
    }

    public function cambiarEstado($id, $activo) {
        try {
            $stmt = $this->conn->prepare("UPDATE materias SET activo = ? WHERE id = ?");
            $stmt->bind_param("ii", $activo, $id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error cambiando estado de materia: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerGrupos($materia_id) {
        $sql = "SELECT g.*, u.nombre as profesor_nombre, u.apellidos as profesor_apellidos,
                gm.activo as asignacion_activa, gm.profesor_id
                FROM grupos g
                LEFT JOIN grupo_materia gm ON g.id = gm.grupo_id AND gm.materia_id = ?
                LEFT JOIN usuarios u ON gm.profesor_id = u.id
                WHERE g.activo = TRUE
                ORDER BY g.nombre";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $materia_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function obtenerProfesoresDisponibles() {
        $sql = "SELECT u.* FROM usuarios u 
                INNER JOIN roles r ON u.rol_id = r.id 
                WHERE r.nombre = 'profesor' AND u.activo = TRUE
                ORDER BY u.nombre, u.apellidos";
        return $this->conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function contarGruposConMaterias() {
        $sql = "SELECT COUNT(DISTINCT grupo_id) as total FROM grupo_materia WHERE activo = 1";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return $row['total'];
    }
    
    public function contarProfesoresConMaterias() {
        $sql = "SELECT COUNT(DISTINCT profesor_id) as total FROM grupo_materia WHERE activo = 1";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return $row['total'];
    }
    
    public function contarTareas() {
        $sql = "SELECT COUNT(*) as total FROM tareas";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return $row['total'];
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
     * Obtiene las materias asignadas a un estudiante específico
     * @param int $estudianteId ID del estudiante
     * @return array Lista de materias
     */
    public function obtenerMateriasEstudiante($estudianteId) {
        $sql = "SELECT DISTINCT m.* 
                FROM materias m
                INNER JOIN grupo_materia gm ON m.id = gm.materia_id
                INNER JOIN estudiante_grupo eg ON gm.grupo_id = eg.grupo_id
                WHERE eg.estudiante_id = ? AND m.activo = 1
                ORDER BY m.nombre";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $estudianteId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
