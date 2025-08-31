<?php
if (!defined('ROOT_PATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/dirs.php');
}

require_once(MODELS_PATH . '/Materia.php');

class MateriaController {
    private $modelo;

    public function __construct() {
        try {
            $this->modelo = new Materia();
        } catch (Exception $e) {
            error_log("Error al inicializar MateriaController: " . $e->getMessage());
            throw $e;
        }
    }

    public function obtenerEstadisticas() {
        try {
            $stats = [];
            
            // Obtener todas las materias
            $materias = $this->modelo->obtenerTodas();
            
            // Contar total de materias
            $stats['total_materias'] = count($materias);
            
            // Contar materias activas
            $materiasActivas = array_filter($materias, function($materia) {
                return $materia['activo'] == 1;
            });
            $stats['materias_activas'] = count($materiasActivas);
            
            // Obtener total de grupos con materias asignadas
            $stats['total_grupos'] = $this->modelo->contarGruposConMaterias();
            
            // Obtener profesores asignados a materias
            $stats['total_profesores'] = $this->modelo->contarProfesoresConMaterias();
            
            // Obtener tareas asignadas a materias
            $stats['total_tareas'] = $this->modelo->contarTareas();
            
            return $stats;
        } catch (Exception $e) {
            error_log("Error al obtener estadísticas: " . $e->getMessage());
            return ['error' => 'Error al obtener estadísticas'];
        }
    }

    public function obtenerTodas() {
        try {
            return $this->modelo->obtenerTodas();
        } catch (Exception $e) {
            error_log("Error al obtener todas las materias: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerPorId($id) {
        try {
            return $this->modelo->obtenerPorId($id);
        } catch (Exception $e) {
            error_log("Error al obtener materia por ID: " . $e->getMessage());
            return null;
        }
    }

    public function obtenerGrupos($materia_id) {
        try {
            return $this->modelo->obtenerGrupos($materia_id);
        } catch (Exception $e) {
            error_log("Error al obtener grupos: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    public function obtenerMateriasPorProfesor($profesorId) {
        try {
            return $this->modelo->obtenerMateriasPorProfesor($profesorId);
        } catch (Exception $e) {
            error_log("Error obteniendo materias por profesor: " . $e->getMessage());
            return [];
        }
    }

    public function procesarAccion() {
        try {
            if (!isset($_POST['accion'])) {
                return ['error' => 'Acción no especificada'];
            }

            switch ($_POST['accion']) {
                case 'crear':
                    return $this->crear($_POST);
                case 'actualizar':
                    return $this->actualizar($_POST);
                case 'cambiarEstado':
                    return $this->cambiarEstado($_POST);
                default:
                    return ['error' => 'Acción no válida'];
            }
        } catch (Exception $e) {
            error_log("Error al procesar acción: " . $e->getMessage());
            return ['error' => 'Error al procesar la solicitud'];
        }
    }

    private function crear($datos) {
        try {
            if (empty($datos['nombre']) || empty($datos['codigo'])) {
                return ['error' => 'El nombre y código son obligatorios'];
            }

            $resultado = $this->modelo->crear([
                'nombre' => $datos['nombre'],
                'codigo' => $datos['codigo'],
                'descripcion' => $datos['descripcion'] ?? ''
            ]);

            if (isset($resultado['success'])) {
                return ['success' => 'Materia creada exitosamente'];
            }
            return ['error' => $resultado['error'] ?? 'Error al crear la materia'];
        } catch (Exception $e) {
            error_log("Error al crear materia: " . $e->getMessage());
            return ['error' => 'Error al crear la materia'];
        }
    }

    private function actualizar($datos) {
        try {
            if (empty($datos['id']) || empty($datos['nombre']) || empty($datos['codigo'])) {
                return ['error' => 'El nombre y código son obligatorios'];
            }

            $resultado = $this->modelo->actualizar([
                'id' => (int)$datos['id'],
                'nombre' => $datos['nombre'],
                'codigo' => $datos['codigo'],
                'descripcion' => $datos['descripcion'] ?? ''
            ]);

            if ($resultado) {
                return ['success' => 'Materia actualizada exitosamente'];
            }
            return ['error' => 'Error al actualizar la materia'];
        } catch (Exception $e) {
            error_log("Error al actualizar materia: " . $e->getMessage());
            return ['error' => 'Error al actualizar la materia'];
        }
    }

    private function cambiarEstado($datos) {
        try {
            if (empty($datos['id']) || !isset($datos['activo'])) {
                return ['error' => 'Datos incompletos'];
            }

            $resultado = $this->modelo->cambiarEstado(
                (int)$datos['id'],
                (bool)$datos['activo']
            );

            if ($resultado) {
                return ['success' => 'Estado de la materia actualizado exitosamente'];
            }
            return ['error' => 'Error al actualizar el estado de la materia'];
        } catch (Exception $e) {
            error_log("Error al cambiar estado de materia: " . $e->getMessage());
            return ['error' => 'Error al actualizar el estado de la materia'];
        }
    }

    /**
     * Obtiene las materias asignadas a un estudiante específico
     * @param int $estudianteId ID del estudiante
     * @return array Lista de materias
     */
    public function obtenerMateriasEstudiante($estudianteId) {
        // Intentar usar método del modelo si existe
        try {
            if (method_exists($this->modelo, 'obtenerMateriasEstudiante')) {
                return $this->modelo->obtenerMateriasEstudiante($estudianteId);
            }
            
            // Implementación alternativa
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                throw new Exception("Error de conexión: " . $conn->connect_error);
            }
            
            $sql = "SELECT DISTINCT m.* 
                    FROM materias m
                    INNER JOIN grupo_materia gm ON m.id = gm.materia_id
                    INNER JOIN estudiante_grupo eg ON gm.grupo_id = eg.grupo_id
                    WHERE eg.estudiante_id = ? AND m.activo = 1";
                    
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error preparando la consulta: " . $conn->error);
            }
            
            $stmt->bind_param("i", $estudianteId);
            $stmt->execute();
            $result = $stmt->get_result();
            $materias = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            $conn->close();
            
            return $materias;
        } catch (Exception $e) {
            error_log("Error obteniendo materias del estudiante: " . $e->getMessage());
            return [];
        }
    }
}

