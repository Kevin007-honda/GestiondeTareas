<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/dirs.php');
require_once(CONTROLLERS_PATH . '/TareaController.php');
require_once(CONTROLLERS_PATH . '/MateriaController.php');
require_once(CONTROLLERS_PATH . '/GrupoController.php');

class GestionTareaController {
    private $tareaController;
    private $materiaController;
    private $grupoController;
    private $tareaModel;
    
    public function __construct() {
        $this->tareaController = new TareaController();
        $this->materiaController = new MateriaController();
        $this->grupoController = new GrupoController();
        $this->tareaModel = new TareaModel();
    }
      /**
     * Procesa la creación de una tarea desde AJAX
     */
    public function procesarCreacionTarea($datos) {
        if (!isset($_SESSION['user_id'])) {
            return ['error' => 'Usuario no autenticado'];
        }
        
        // Añadir el ID del profesor actual
        $datos['profesor_id'] = $_SESSION['user_id'];
        
        // Utilizar el método de TareaController para crear la tarea y enviar notificaciones
        return $this->tareaController->crearTarea($datos);
    }

    /**
     * Procesa la acción solicitada
     */
    public function procesarAccion() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return null;
        }
        
        $accion = $_POST['accion'] ?? '';
        $resultado = ['error' => 'Acción no válida'];
        
        switch($accion) {
            case 'crear':
                $resultado = $this->crearTarea($_POST);
                break;
            case 'calificar':
                $resultado = $this->calificarEntrega($_POST);
                break;
            case 'cambiarEstado':
                $resultado = $this->cambiarEstadoTarea($_POST);
                break;
            case 'actualizar':
                $resultado = $this->actualizarTarea($_POST);
                break;
            case 'eliminar':
                $resultado = $this->eliminarTarea($_POST['id'] ?? null); // Pasar el ID de la tarea
                break;
            // Puedes añadir más casos según sea necesario
        }
        
        return $resultado;
    }

    /**
     * Elimina una tarea existente
     */
    private function eliminarTarea($tareaId) {
        if (!isset($_SESSION['user_id'])) {
            return ['error' => 'Usuario no autenticado'];
        }
        
        return $this->tareaController->eliminarTarea($tareaId);
    }

    /**
     * Actualiza una tarea existente
     */
    private function actualizarTarea($datos) {
        if (!isset($_SESSION['user_id'])) {
            return ['error' => 'Usuario no autenticado'];
        }
        
        // No es necesario añadir profesor_id aquí, ya que el método de TareaController lo recibe directamente
        return $this->tareaController->actualizarTarea($datos);
    }
    
    /**
     * Crea una nueva tarea
     */
    private function crearTarea($datos) {
        if (!isset($_SESSION['user_id'])) {
            return ['error' => 'Usuario no autenticado'];
        }
        
        // Añadir el ID del profesor actual
        $datos['profesor_id'] = $_SESSION['user_id'];
        
        // Utilizar el método de TareaController para crear la tarea y enviar notificaciones
        return $this->tareaController->crearTarea($datos);
    }
    
    /**
     * Califica una entrega de tarea
     */
    private function calificarEntrega($datos) {
        return $this->tareaController->calificarEntrega($datos);
    }
    
    /**
     * Cambia el estado de una tarea
     */
    private function cambiarEstadoTarea($datos) {
        return $this->tareaController->cambiarEstadoTarea($datos);
    }
    
    /**
     * Obtiene datos para el formulario de creación de tareas
     */
    public function getDatosFormulario() {
        $profesorId = $_SESSION['user_id'] ?? null;
        
        if (!$profesorId) {
            return [
                'error' => 'Usuario no autenticado',
                'grupos' => [],
                'materias' => []
            ];
        }
        
        $grupos = $this->grupoController->obtenerGruposPorProfesor($profesorId);
        $materias = $this->materiaController->obtenerMateriasPorProfesor($profesorId);
        
        return [
            'grupos' => $grupos,
            'materias' => $materias
        ];
    }

    /**
     * Obtiene las tareas activas (implementación necesaria para task_management.php)
     * @return array Lista de tareas activas
     */
    public function obtenerTareasActivas() {
        return $this->tareaModel->getTareasActivas();
    }
    
    /**
     * Obtiene todas las tareas con sus detalles (implementación necesaria para task_management.php)
     * @return array Lista completa de tareas con detalles
     */
    public function obtenerTodasLasTareas() {
        return $this->tareaModel->getTodasLasTareasConDetalles();
    }
}

// Procesar solicitudes AJAX directas a este controlador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    $gestionTareaController = new GestionTareaController();
    $resultado = $gestionTareaController->procesarAccion();
    echo json_encode($resultado);
    exit;
}
?>