<?php
// Asegurarse de que la sesión está iniciada y ROOT_PATH está definido
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Iniciar sesión solo si no está activa
}

if (!defined('ROOT_PATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/dirs.php');
}

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    // Redirigir a la página de login en lugar de terminar el script
    header('Location: ' . BASE_URL . '/app/views/auth/login.php');
    exit();
}

// Cargar controladores necesarios
require_once(CONTROLLERS_PATH . '/TareaController.php');
require_once(CONTROLLERS_PATH . '/MateriaController.php');

// Inicializar controladores
$tareaController = new TareaController();
$materiaController = new MateriaController();

// Obtener el ID del estudiante actual desde la sesión
$estudiante_id = $_SESSION['user_id'];

// Verificar si se está accediendo a una tarea específica desde una notificación
$tarea_destacada = isset($_GET['tarea_id']) ? intval($_GET['tarea_id']) : null;

// Procesar envío de entregas
$mensajeResultado = null;
$tipoAlerta = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    if ($_POST['accion'] === 'entregar' && isset($_POST['tarea_id'])) {
        $tarea_id = intval($_POST['tarea_id']);
        
        // Procesar archivo adjunto
        $archivo_adjunto = null;
        $targetDir = ROOT_PATH . "/public/uploads/entregas/";
        
        // Crear el directorio si no existe
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        if (isset($_FILES['archivo_adjunto']) && $_FILES['archivo_adjunto']['error'] === UPLOAD_ERR_OK) {
            $nombreArchivo = $estudiante_id . '_' . $tarea_id . '_' . time() . '_' . basename($_FILES['archivo_adjunto']['name']);
            $targetFile = $targetDir . $nombreArchivo;
            
            // Verificar tamaño del archivo (máximo 10MB)
            if ($_FILES['archivo_adjunto']['size'] > 10000000) {
                $mensajeResultado = "El archivo es demasiado grande. Máximo 10MB permitido.";
                $tipoAlerta = "danger";
            } else {
                // Intentar subir el archivo
                if (move_uploaded_file($_FILES['archivo_adjunto']['tmp_name'], $targetFile)) {
                    $archivo_adjunto = $nombreArchivo;
                } else {
                    $mensajeResultado = "Error al subir el archivo.";
                    $tipoAlerta = "danger";
                }
            }
        }
        
        // Si no hay errores previos y se adjuntó un archivo o se envió un comentario
        if ($tipoAlerta !== "danger" && ($archivo_adjunto !== null || !empty($_POST['comentarios']))) {
            try {
                $resultado = $tareaController->entregarTarea([
                    'tarea_id' => $tarea_id,
                    'estudiante_id' => $estudiante_id,
                    'comentarios' => $_POST['comentarios'] ?? '',
                    'archivo_adjunto' => $archivo_adjunto
                ]);
                
                if (isset($resultado['success'])) {
                    $mensajeResultado = $resultado['success'];
                    $tipoAlerta = "success";
                } else {
                    $mensajeResultado = $resultado['error'] ?? "Error al entregar la tarea.";
                    $tipoAlerta = "danger";
                }
            } catch (Exception $e) {
                $mensajeResultado = "Error en el sistema: " . $e->getMessage();
                $tipoAlerta = "danger";
                error_log("Error al entregar tarea: " . $e->getMessage());
            }
        } elseif ($tipoAlerta !== "danger") {
            $mensajeResultado = "Debes adjuntar un archivo o añadir un comentario.";
            $tipoAlerta = "warning";
        }
    }
}

// Obtener las tareas del estudiante
$tareas = [];
try {
    $tareas = $tareaController->obtenerTareasParaEstudiantes();
} catch (Exception $e) {
    $mensajeResultado = "No se pudieron cargar las tareas: " . $e->getMessage();
    $tipoAlerta = "danger";
    error_log("Error al cargar tareas: " . $e->getMessage());
}

// Obtener lista de materias para filtros
$materias = [];
try {
    $materias = $materiaController->obtenerMateriasEstudiante($estudiante_id);
} catch (Exception $e) {
    error_log("Error al cargar materias: " . $e->getMessage());
}

$filtroMateria = $_GET['materia'] ?? null;
$filtroEstado = $_GET['estado'] ?? null;

// Filtrar tareas si se especifica algún filtro
if (($filtroMateria || $filtroEstado) && empty($mensajeResultado)) {
    try {
        $tareas = $tareaController->obtenerTareasFiltradas($filtroMateria, $filtroEstado);
    } catch (Exception $e) {
        $mensajeResultado = "Error al filtrar tareas: " . $e->getMessage();
        $tipoAlerta = "danger";
        error_log("Error al filtrar tareas: " . $e->getMessage());
    }
}

// Organizar las tareas por fecha de entrega
if (!empty($tareas)) {
    usort($tareas, function($a, $b) {
        return strtotime($a['fecha_entrega']) - strtotime($b['fecha_entrega']);
    });
}

// Separar tareas en próximas y pendientes
$tareasPendientes = [];
$tareasVencidas = [];
$fechaActual = time();

foreach ($tareas as $tarea) {
    $fechaEntrega = strtotime($tarea['fecha_entrega']);
    
    if ($fechaEntrega < $fechaActual) {
        $tareasVencidas[] = $tarea;
    } else {
        $tareasPendientes[] = $tarea;
    }
}
?>

<h1 class="position-relative header-page">Mis Tareas Pendientes</h1>

<div class="container-fluid py-4">
    <!-- Mensaje de resultado de operación -->
    <?php if ($mensajeResultado): ?>
        <div class="alert alert-<?= $tipoAlerta ?> alert-dismissible fade show" role="alert">
            <?= $mensajeResultado ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="task_visualization">
                <input type="hidden" name="role" value="student">
                
                <div class="col-md-4">
                    <label for="materia" class="form-label">Filtrar por Materia</label>
                    <select name="materia" id="materia" class="form-select">
                        <option value="">Todas las materias</option>
                        <?php foreach ($materias as $materia): ?>
                            <option value="<?= htmlspecialchars($materia['nombre']) ?>" <?= $filtroMateria === $materia['nombre'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($materia['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="estado" class="form-label">Filtrar por Estado</label>
                    <select name="estado" id="estado" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="pendiente" <?= $filtroEstado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="entregada" <?= $filtroEstado === 'entregada' ? 'selected' : '' ?>>Entregada</option>
                        <option value="calificada" <?= $filtroEstado === 'calificada' ? 'selected' : '' ?>>Calificada</option>
                    </select>
                </div>
                
                <div class="col-md-4 d-flex">
                    <button type="submit" class="btn btn-primary me-2">Filtrar</button>
                    <a href="<?= BASE_URL ?>?page=task_visualization&role=student" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Sección Tareas Pendientes -->
    <h2 class="fs-4 mb-3">Tareas Próximas a Vencer</h2>
    
    <?php if (empty($tareasPendientes)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> No tienes tareas pendientes por entregar.
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-5">
            <?php foreach ($tareasPendientes as $tarea): 
                $fechaEntrega = strtotime($tarea['fecha_entrega']);
                $diferenciaDias = intval(($fechaEntrega - time()) / 86400);
                
                $badgeClase = 'bg-info';
                $badgeTexto = "Faltan $diferenciaDias días";
                
                if ($diferenciaDias <= 1) {
                    $badgeClase = 'bg-danger';
                    $badgeTexto = $diferenciaDias < 1 ? "¡Vence hoy!" : "¡Vence mañana!";
                } elseif ($diferenciaDias <= 3) {
                    $badgeClase = 'bg-warning';
                }
                
                // Verificar si ya fue entregada
                $estadoEntrega = $tareaController->verificarEntrega($tarea['id'], $estudiante_id);
            ?>                <div class="col">
                    <div class="card h-100 border-0 shadow-sm <?= $tarea_destacada == $tarea['id'] ? 'border-primary border-3' : '' ?>">                        <div class="card-header d-flex justify-content-between align-items-center <?= $tarea_destacada == $tarea['id'] ? 'bg-primary bg-opacity-10' : '' ?>">
                            <h5 class="card-title mb-0 <?= $tarea_destacada == $tarea['id'] ? 'text-primary fw-bold' : '' ?>">
                                <?php if ($tarea_destacada == $tarea['id']): ?>
                                    <i class="fas fa-star text-warning me-2"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($tarea['titulo']) ?>
                            </h5>
                            <span class="badge <?= $badgeClase ?>"><?= $badgeTexto ?></span>
                        </div>
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($tarea['materia_nombre']) ?> - <?= htmlspecialchars($tarea['grupo_nombre']) ?></h6>
                            
                            <p class="card-text mb-4">
                                <i class="far fa-calendar-alt text-primary me-2"></i>
                                <strong>Fecha de entrega:</strong> <?= date('d/m/Y H:i', $fechaEntrega) ?>
                            </p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <?php if ($estadoEntrega): ?>
                                    <div class="badge bg-success p-2">
                                        <i class="fas fa-check-circle me-1"></i> Entregada el <?= date('d/m/Y', strtotime($estadoEntrega['fecha_entrega'])) ?>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#entregaModal<?= $tarea['id'] ?>">
                                        Ver entrega
                                    </button>
                                <?php else: ?>
                                    <div class="badge bg-warning p-2">
                                        <i class="fas fa-hourglass-half me-1"></i> Pendiente
                                    </div>
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#entregaModal<?= $tarea['id'] ?>">
                                        Entregar tarea
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modal de Entrega -->
                <div class="modal fade" id="entregaModal<?= $tarea['id'] ?>" tabindex="-1" aria-labelledby="modalLabel<?= $tarea['id'] ?>" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <?php if ($estadoEntrega): ?>
                                <!-- Mostrar detalles de la entrega -->
                                <div class="modal-header bg-light">
                                    <h5 class="modal-title" id="modalLabel<?= $tarea['id'] ?>">Detalles de Entrega: <?= htmlspecialchars($tarea['titulo']) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="fw-bold">Estado:</label>
                                        <span class="badge bg-<?= $estadoEntrega['estado'] === 'calificada' ? 'success' : 'info' ?>"><?= ucfirst($estadoEntrega['estado']) ?></span>
                                    </div>
                                    
                                    <?php if (isset($estadoEntrega['calificacion'])): ?>
                                    <div class="mb-3">
                                        <label class="fw-bold">Calificación:</label>
                                        <div class="d-inline-block px-3 py-1 rounded-pill bg-light border">
                                            <?= $estadoEntrega['calificacion'] ?>/10
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($estadoEntrega['comentarios']): ?>
                                    <div class="mb-3">
                                        <label class="fw-bold">Comentarios del estudiante:</label>
                                        <p><?= nl2br(htmlspecialchars($estadoEntrega['comentarios'])) ?></p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($estadoEntrega['archivo_adjunto']): ?>
                                    <div class="mb-3">
                                        <label class="fw-bold">Archivo adjunto:</label>
                                        <div class="d-flex align-items-center mt-2">
                                            <i class="fas fa-file-alt me-2 text-primary fa-2x"></i>
                                            <div>
                                                <div><?= htmlspecialchars(basename($estadoEntrega['archivo_adjunto'])) ?></div>
                                                <a href="<?= BASE_URL ?>/public/uploads/entregas/<?= $estadoEntrega['archivo_adjunto'] ?>" class="btn btn-sm btn-outline-primary mt-1" download>
                                                    <i class="fas fa-download me-1"></i> Descargar
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                </div>
                            <?php else: ?>
                                <!-- Formulario de entrega -->
                                <form method="post" enctype="multipart/form-data">
                                    <div class="modal-header bg-light">
                                        <h5 class="modal-title" id="modalLabel<?= $tarea['id'] ?>">Entregar Tarea: <?= htmlspecialchars($tarea['titulo']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="accion" value="entregar">
                                        <input type="hidden" name="tarea_id" value="<?= $tarea['id'] ?>">
                                        
                                        <div class="mb-3">
                                            <label for="archivo_adjunto" class="form-label">Archivo (máx. 10MB)</label>
                                            <input type="file" class="form-control" id="archivo_adjunto" name="archivo_adjunto">
                                            <small class="text-muted">Formatos recomendados: PDF, Word, Excel, PowerPoint, ZIP.</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="comentarios" class="form-label">Comentarios (opcional)</label>
                                            <textarea class="form-control" id="comentarios" name="comentarios" rows="3" placeholder="Añade algún comentario o nota para tu profesor..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane me-1"></i> Enviar Entrega
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Sección Tareas Vencidas -->
    <?php if (!empty($tareasVencidas)): ?>
        <h2 class="fs-4 mb-3 mt-4">Tareas Vencidas</h2>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($tareasVencidas as $tarea): 
                $fechaEntrega = strtotime($tarea['fecha_entrega']);
                $diferenciaDias = intval((time() - $fechaEntrega) / 86400);
                
                // Verificar si ya fue entregada
                $estadoEntrega = $tareaController->verificarEntrega($tarea['id'], $estudiante_id);
            ?>                <div class="col">
                    <div class="card h-100 border-0 shadow-sm <?= $tarea_destacada == $tarea['id'] ? 'border-primary border-3' : '' ?>">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center <?= $tarea_destacada == $tarea['id'] ? 'bg-primary bg-opacity-10' : '' ?>">
                            <h5 class="card-title mb-0 <?= $tarea_destacada == $tarea['id'] ? 'text-primary fw-bold' : '' ?>">
                                <?php if ($tarea_destacada == $tarea['id']): ?>
                                    <i class="fas fa-star text-warning me-2"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($tarea['titulo']) ?>
                            </h5>
                            <span class="badge bg-danger">Vencida hace <?= $diferenciaDias ?> días</span>
                        </div>
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($tarea['materia_nombre']) ?> - <?= htmlspecialchars($tarea['grupo_nombre']) ?></h6>
                            
                            <p class="card-text mb-4">
                                <i class="far fa-calendar-times text-danger me-2"></i>
                                <strong>Fecha de entrega:</strong> <?= date('d/m/Y H:i', $fechaEntrega) ?>
                            </p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <?php if ($estadoEntrega): ?>
                                    <div class="badge bg-success p-2">
                                        <i class="fas fa-check-circle me-1"></i> Entregada el <?= date('d/m/Y', strtotime($estadoEntrega['fecha_entrega'])) ?>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#entregaModalVencida<?= $tarea['id'] ?>">
                                        Ver entrega
                                    </button>
                                <?php else: ?>
                                    <div class="badge bg-danger p-2">
                                        <i class="fas fa-times-circle me-1"></i> No entregada
                                    </div>
                                    <span class="text-muted small">No se puede entregar</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modal de Entrega para tareas vencidas -->
                <?php if ($estadoEntrega): ?>
                <div class="modal fade" id="entregaModalVencida<?= $tarea['id'] ?>" tabindex="-1" aria-labelledby="modalLabelVencida<?= $tarea['id'] ?>" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-light">
                                <h5 class="modal-title" id="modalLabelVencida<?= $tarea['id'] ?>">Detalles de Entrega: <?= htmlspecialchars($tarea['titulo']) ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="fw-bold">Estado:</label>
                                    <span class="badge bg-<?= $estadoEntrega['estado'] === 'calificada' ? 'success' : 'info' ?>"><?= ucfirst($estadoEntrega['estado']) ?></span>
                                </div>
                                
                                <?php if (isset($estadoEntrega['calificacion'])): ?>
                                <div class="mb-3">
                                    <label class="fw-bold">Calificación:</label>
                                    <div class="d-inline-block px-3 py-1 rounded-pill bg-light border">
                                        <?= $estadoEntrega['calificacion'] ?>/10
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($estadoEntrega['comentarios']): ?>
                                <div class="mb-3">
                                    <label class="fw-bold">Comentarios del estudiante:</label>
                                    <p><?= nl2br(htmlspecialchars($estadoEntrega['comentarios'])) ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($estadoEntrega['archivo_adjunto']): ?>
                                <div class="mb-3">
                                    <label class="fw-bold">Archivo adjunto:</label>
                                    <div class="d-flex align-items-center mt-2">
                                        <i class="fas fa-file-alt me-2 text-primary fa-2x"></i>
                                        <div>
                                            <div><?= htmlspecialchars(basename($estadoEntrega['archivo_adjunto'])) ?></div>
                                            <a href="<?= BASE_URL ?>/public/uploads/entregas/<?= $estadoEntrega['archivo_adjunto'] ?>" class="btn btn-sm btn-outline-primary mt-1" download>
                                                <i class="fas fa-download me-1"></i> Descargar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validación de formulario de entrega
    const formEntregas = document.querySelectorAll('form[enctype="multipart/form-data"]');
    formEntregas.forEach(form => {
        form.addEventListener('submit', function(e) {
            const archivo = form.querySelector('input[name="archivo_adjunto"]');
            const comentario = form.querySelector('textarea[name="comentarios"]');
            
            if (archivo.files.length === 0 && comentario.value.trim() === '') {
                e.preventDefault();
                alert('Debes adjuntar un archivo o añadir un comentario.');
                return false;
            }
            
            if (archivo.files.length > 0 && archivo.files[0].size > 10 * 1024 * 1024) {
                e.preventDefault();
                alert('El archivo es demasiado grande. Máximo 10MB permitido.');
                return false;
            }
        });
    });
    
    // Desplazarse automáticamente a la tarea destacada desde notificación
    <?php if ($tarea_destacada): ?>
    setTimeout(function() {
        const tareaDestacada = document.querySelector('.border-primary.border-3');
        if (tareaDestacada) {
            tareaDestacada.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
            
            // Mostrar una animación de pulso para llamar la atención
            tareaDestacada.style.animation = 'pulse 1s ease-in-out 3';
        }
    }, 500);
    <?php endif; ?>
});
</script>

<style>
@keyframes pulse {
    0% { 
        transform: scale(1); 
        box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4);
    }
    50% { 
        transform: scale(1.02); 
        box-shadow: 0 0 0 10px rgba(13, 110, 253, 0.1);
    }
    100% { 
        transform: scale(1); 
        box-shadow: 0 0 0 0 rgba(13, 110, 253, 0);
    }
}
</style>