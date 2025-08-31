<?php
if (!defined('ROOT_PATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/dirs.php');
}

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'profesor') {
    echo "<div class='alert alert-danger'>Acceso no autorizado. Debe ser profesor para ver esta página.</div>";
    exit;
}

// Cargar controlador
require_once(CONTROLLERS_PATH . '/TareaController.php');
$tareaController = new TareaController();

// Obtener todas las tareas del profesor con sus entregas
$profesorId = $_SESSION['user_id'];
$tareas = $tareaController->obtenerTareasAsignadasConEntregas($profesorId);

// Filtrar solo las tareas con entregas pendientes si se solicita
$mostrarSoloPendientes = isset($_GET['pendientes']) && $_GET['pendientes'] === '1';
if ($mostrarSoloPendientes) {
    $tareas = array_filter($tareas, function($tarea) {
        return $tarea['entregadas'] > 0 && isset($tarea['estado']) && $tarea['estado'] === 'entregada';
    });
}
?>

<div class="container-fluid py-4">
    <h1 class="position-relative header-page">Gestión de Entregas</h1>
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Tareas con Entregas</h5>
                    <div>
                        <?php if ($mostrarSoloPendientes): ?>
                            <a href="<?= BASE_URL ?>?page=task_submissions&role=profesor" class="btn btn-outline-primary">
                                <i class="fas fa-list me-2"></i> Ver todas
                            </a>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>?page=task_submissions&pendientes=1&role=profesor" class="btn btn-outline-warning">
                                <i class="fas fa-clock me-2"></i> Ver solo pendientes
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if (empty($tareas)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> 
                            <?= $mostrarSoloPendientes ? 'No hay tareas con entregas pendientes.' : 'No hay tareas con entregas.' ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Título de la Tarea</th>
                                        <th>Materia</th>
                                        <th>Grupo</th>
                                        <th>Fecha Límite</th>
                                        <th>Estado</th>
                                        <th>Entregas</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($tareas as $tarea): 
                                        // Si está filtrado por pendientes y la tarea no tiene entregas, saltarla
                                        if ($mostrarSoloPendientes && $tarea['entregadas'] == 0) continue;
                                        
                                        // Determinar clase de estado
                                        $estadoClass = match($tarea['estado']) {
                                            'pendiente' => 'bg-warning',
                                            'completada' => 'bg-success',
                                            'calificada' => 'bg-info',
                                            'vencida' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        
                                        // Calcular el progreso
                                        $porcentajeEntregas = $tarea['total_estudiantes'] > 0 
                                            ? round(($tarea['entregadas'] / $tarea['total_estudiantes']) * 100) 
                                            : 0;
                                            
                                        // Determinar clase del progreso
                                        $progressClass = match(true) {
                                            ($porcentajeEntregas >= 75) => 'bg-success',
                                            ($porcentajeEntregas >= 50) => 'bg-info',
                                            ($porcentajeEntregas >= 25) => 'bg-warning',
                                            default => 'bg-danger'
                                        };
                                    ?>
                                        <tr>
                                            <td class="fw-medium"><?= htmlspecialchars($tarea['titulo']) ?></td>
                                            <td><?= htmlspecialchars($tarea['materia_nombre']) ?></td>
                                            <td><?= htmlspecialchars($tarea['grupo_nombre']) ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($tarea['fecha_entrega'])) ?></td>
                                            <td><span class="badge <?= $estadoClass ?>"><?= ucfirst($tarea['estado']) ?></span></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <?= $tarea['entregadas'] ?> / <?= $tarea['total_estudiantes'] ?>
                                                    </div>
                                                    <div class="progress flex-grow-1" style="height: 5px;">
                                                        <div class="progress-bar <?= $progressClass ?>" 
                                                            role="progressbar" 
                                                            style="width: <?= $porcentajeEntregas ?>%" 
                                                            aria-valuenow="<?= $porcentajeEntregas ?>" 
                                                            aria-valuemin="0" 
                                                            aria-valuemax="100"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="<?= BASE_URL ?>?page=task_submissions&tarea_id=<?= $tarea['id'] ?>&role=profesor" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye me-1"></i> Ver entregas
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
