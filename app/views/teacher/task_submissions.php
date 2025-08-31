<?php
if (!defined('ROOT_PATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/dirs.php');
}

require_once(CONTROLLERS_PATH . '/TareaController.php');

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'profesor') {
    echo "<div class='alert alert-danger'>Acceso no autorizado. Debe ser profesor para ver esta página.</div>";
    exit;
}

// Verificar que se proporcionó el ID de la tarea
$tareaId = isset($_GET['tarea_id']) ? (int)$_GET['tarea_id'] : 0;
if (!$tareaId) {
    echo "<div class='alert alert-danger'>No se especificó una tarea válida.</div>";
    exit;
}

// Instanciar controlador
$tareaController = new TareaController();

// Obtener detalles de la tarea
$tarea = null;
try {
    $tarea = $tareaController->obtenerTareaPorId($tareaId);
    if (!$tarea) {
        echo "<div class='alert alert-danger'>La tarea solicitada no existe.</div>";
        exit;
    }
    
    // Verificar que la tarea pertenece al profesor actual
    if ($tarea['profesor_id'] != $_SESSION['user_id']) {
        echo "<div class='alert alert-danger'>No tiene permiso para ver esta tarea.</div>";
        exit;
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error al cargar la tarea: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}

// Obtener todas las entregas de esta tarea
$entregas = [];
try {
    $entregas = $tareaController->obtenerEntregasPorTarea($tareaId);
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error al cargar las entregas: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Si se envió un formulario para calificar
$mensajeCalificacion = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'calificar') {
    try {
        $resultado = $tareaController->calificarEntrega($_POST);
        if (isset($resultado['success'])) {
            $mensajeCalificacion = [
                'tipo' => 'success',
                'texto' => $resultado['success']
            ];
            // Recargar entregas después de calificar
            $entregas = $tareaController->obtenerEntregasPorTarea($tareaId);
        } else {
            $mensajeCalificacion = [
                'tipo' => 'danger',
                'texto' => $resultado['error'] ?? 'Error al calificar la entrega'
            ];
        }
    } catch (Exception $e) {
        $mensajeCalificacion = [
            'tipo' => 'danger',
            'texto' => 'Error del sistema: ' . $e->getMessage()
        ];
    }
}
?>

<div class="container-fluid py-4">
    <!-- Título de la página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Entregas de Tarea</h1>
        <a href="<?= BASE_URL ?>?page=assigned_tasks&role=profesor" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i> Volver a tareas
        </a>
    </div>

    <?php if ($mensajeCalificacion): ?>
        <div class="alert alert-<?= $mensajeCalificacion['tipo'] ?> alert-dismissible fade show" role="alert">
            <?= $mensajeCalificacion['texto'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Detalles de la tarea -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h2 class="h5 mb-0 text-primary">
                <i class="fas fa-clipboard-list me-2"></i><?= htmlspecialchars($tarea['titulo']) ?>
            </h2>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Materia:</strong> <?= htmlspecialchars($tarea['materia_nombre']) ?></p>
                    <p><strong>Grupo:</strong> <?= htmlspecialchars($tarea['grupo_nombre']) ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Fecha de entrega:</strong> <?= date('d/m/Y H:i', strtotime($tarea['fecha_entrega'])) ?></p>
                    <p><strong>Estado:</strong> <span class="badge bg-primary"><?= htmlspecialchars(ucfirst($tarea['estado_nombre'])) ?></span></p>
                </div>
                <?php if (!empty($tarea['descripcion'])): ?>
                <div class="col-12 mt-3">
                    <hr>
                    <h6 class="fw-bold">Descripción:</h6>
                    <p><?= nl2br(htmlspecialchars($tarea['descripcion'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Lista de entregas -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h3 class="h5 mb-0 text-success">
                <i class="fas fa-file-alt me-2"></i>Entregas de estudiantes
            </h3>
        </div>
        <div class="card-body p-0">
            <?php if (empty($entregas)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                    <h5 class="text-muted">No hay entregas aún</h5>
                    <p class="mb-0 text-muted">Las entregas de los estudiantes aparecerán aquí.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Estudiante</th>
                                <th>Fecha de entrega</th>
                                <th>Comentarios</th>
                                <th>Archivo</th>
                                <th>Estado</th>
                                <th>Calificación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entregas as $entrega): 
                                $badgeClass = match($entrega['estado']) {
                                    'entregada' => 'bg-info',
                                    'calificada' => 'bg-success',
                                    default => 'bg-secondary'
                                };
                            ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm rounded-circle bg-primary bg-opacity-10 text-primary me-2">
                                                <?= substr($entrega['estudiante_nombre'], 0, 1) ?>
                                            </div>
                                            <div>
                                                <?= htmlspecialchars($entrega['estudiante_nombre'] . ' ' . $entrega['estudiante_apellidos']) ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($entrega['fecha_entrega'])) ?></td>
                                    <td>
                                        <?php if (!empty($entrega['comentarios'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= htmlspecialchars($entrega['comentarios']) ?>">
                                                Ver comentario
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">Sin comentarios</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($entrega['archivo_adjunto'])): ?>
                                            <a href="<?= BASE_URL ?>/public/uploads/entregas/<?= $entrega['archivo_adjunto'] ?>" class="btn btn-sm btn-outline-primary" download>
                                                <i class="fas fa-download me-1"></i> Descargar
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">Sin archivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $badgeClass ?>"><?= ucfirst($entrega['estado']) ?></span>
                                    </td>
                                    <td>
                                        <?php if (isset($entrega['calificacion']) && $entrega['calificacion'] !== null): ?>
                                            <span class="badge bg-success"><?= $entrega['calificacion'] ?>/10</span>
                                        <?php elseif ($entrega['estado'] === 'calificada' && isset($entrega['nota'])): ?>
                                            <span class="badge bg-success"><?= $entrega['nota'] ?>/10</span>
                                        <?php else: ?>
                                            <span class="text-muted">Sin calificar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#calificarModal<?= $entrega['id'] ?>">
                                            <i class="fas fa-star me-1"></i> Calificar
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Modal para calificar -->
                                <div class="modal fade" id="calificarModal<?= $entrega['id'] ?>" tabindex="-1" aria-labelledby="calificarModalLabel<?= $entrega['id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form method="post">
                                                <input type="hidden" name="accion" value="calificar">
                                                <input type="hidden" name="entrega_id" value="<?= $entrega['id'] ?>">
                                                
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="calificarModalLabel<?= $entrega['id'] ?>">
                                                        Calificar Entrega de <?= htmlspecialchars($entrega['estudiante_nombre']) ?>
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                
                                                <div class="modal-body">
                                                    <!-- Mostrar archivo y comentario del estudiante si existen -->
                                                    <?php if (!empty($entrega['archivo_adjunto']) || !empty($entrega['comentarios'])): ?>
                                                        <div class="mb-4">
                                                            <h6 class="fw-bold">Entrega del estudiante:</h6>
                                                            
                                                            <?php if (!empty($entrega['archivo_adjunto'])): ?>
                                                                <div class="d-flex align-items-center mb-2">
                                                                    <i class="fas fa-file-alt me-2 text-primary"></i>
                                                                    <a href="<?= BASE_URL ?>/public/uploads/entregas/<?= $entrega['archivo_adjunto'] ?>" download>
                                                                        <?= htmlspecialchars(basename($entrega['archivo_adjunto'])) ?>
                                                                    </a>
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if (!empty($entrega['comentarios'])): ?>
                                                                <div class="card p-3 bg-light">
                                                                    <p class="mb-0"><strong>Comentarios:</strong> <?= nl2br(htmlspecialchars($entrega['comentarios'])) ?></p>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Formulario de calificación -->
                                                    <div class="mb-3">
                                                        <label for="calificacion<?= $entrega['id'] ?>" class="form-label fw-bold">Calificación (0-10):</label>
                                                        <input type="number" class="form-control" id="calificacion<?= $entrega['id'] ?>" name="calificacion" 
                                                               min="0" max="10" step="0.1" required
                                                               value="<?= $entrega['calificacion'] ?? $entrega['nota'] ?? '' ?>">
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="comentarios<?= $entrega['id'] ?>" class="form-label fw-bold">Retroalimentación:</label>
                                                        <textarea class="form-control" id="comentarios<?= $entrega['id'] ?>" name="comentarios" rows="3"><?= htmlspecialchars($entrega['comentarios_profesor'] ?? '') ?></textarea>
                                                    </div>
                                                </div>
                                                
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-primary">Guardar calificación</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<style>
.avatar-sm {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
