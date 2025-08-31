<?php
if (!defined('ROOT_PATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/dirs.php');
}

// Cargar controladores necesarios
require_once(CONTROLLERS_PATH . '/TareaController.php');
require_once(CONTROLLERS_PATH . '/GrupoController.php');
require_once(CONTROLLERS_PATH . '/MateriaController.php');


// Inicializar controladores
$tareaController = new TareaController();
$grupoController = new GrupoController();
$materiaController = new MateriaController();

// Obtener ID del profesor actual
$profesorId = $_SESSION['user_id'];

// Obtener estadísticas de tareas para el profesor
$tareasProfesor = $tareaController->obtenerTareasAsignadasConEntregas($profesorId);

// Calcular datos relevantes
$totalTareas = count($tareasProfesor);
$tareasPendientes = 0;
$tareasCompletadas = 0;
$tareasPorGrupo = [];
$tareasProximas = [];

// Procesar las tareas para obtener estadísticas
foreach ($tareasProfesor as $tarea) {
    // Conteo por estado
    if (strtolower($tarea['estado_nombre']) == 'completada' || strtolower($tarea['estado']) == 'completada') {
        $tareasCompletadas++;
    } else {
        $tareasPendientes++;
    }
    
    // Agrupar tareas por grupo
    if (!isset($tareasPorGrupo[$tarea['grupo_id']])) {
        $tareasPorGrupo[$tarea['grupo_id']] = [
            'nombre' => $tarea['grupo_nombre'],
            'tareas' => 0,
            'estudiantes' => $tarea['total_estudiantes'] ?? 0,
            'entregas' => 0
        ];
    }
    $tareasPorGrupo[$tarea['grupo_id']]['tareas']++;
    $tareasPorGrupo[$tarea['grupo_id']]['entregas'] += $tarea['entregadas'] ?? 0;
    
    // Tareas próximas a vencer (hoy o en los próximos 3 días)
    $fechaEntrega = new DateTime($tarea['fecha_entrega']);
    $hoy = new DateTime();
    $diferencia = $hoy->diff($fechaEntrega);
    
    if ($diferencia->days <= 3 && $fechaEntrega >= $hoy) {
        $tareasProximas[] = $tarea;
    }
}

// Calcular porcentajes
$porcentajePendientes = ($totalTareas > 0) ? ($tareasPendientes / $totalTareas) * 100 : 0;
$porcentajeCompletadas = ($totalTareas > 0) ? ($tareasCompletadas / $totalTareas) * 100 : 0;

// Obtener grupos asignados al profesor
$gruposProfesor = $tareaController->obtenerGruposPorProfesor($profesorId);
$totalGrupos = count($gruposProfesor);

// Calcular el total de estudiantes asignados
$totalEstudiantes = 0;
foreach ($gruposProfesor as $grupo) {
    // Usar el método público del controlador en lugar de intentar acceder a la propiedad privada
    $totalEstudiantes += $tareaController->contarEstudiantesPorGrupo($grupo['id']);
}

// Ordenar tareas próximas por fecha de entrega
if (!empty($tareasProximas)) {
    usort($tareasProximas, function($a, $b) {
        return strtotime($a['fecha_entrega']) - strtotime($b['fecha_entrega']);
    });

    // Limitar a las 4 primeras tareas próximas
    $tareasProximas = array_slice($tareasProximas, 0, 4);
}
?>

<!-- Dashboard del profesor -->
<h1 class="position-relative header-page">Panel del Profesor</h1>

<div class="dashboard-page">
    <!-- Resumen General -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-4">
                    <h2 class="fs-4 fw-bold text-secondary mb-0">Bienvenido al Panel de Control, <?= $_SESSION['nombre'] ?? 'Profesor' ?></h2>
                    <p class="text-muted mb-0">Gestiona tus tareas, grupos y calificaciones desde este panel centralizado</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjetas Estadísticas Principales -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
        <!-- Tarjeta de Tareas Totales -->
        <div class="col">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-primary bg-opacity-10 text-primary rounded-3 me-3">
                            <i class="fas fa-tasks fa-fw fa-lg"></i>
                        </div>
                        <h5 class="card-title mb-0">Tareas</h5>
                    </div>
                    <h2 class="stats-number mb-2"><?= $totalTareas ?></h2>
                    <p class="text-muted mb-0">Tareas asignadas</p>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="<?= BASE_URL ?>?page=assigned_tasks&role=profesor" class="btn btn-sm btn-light w-100">
                        Gestionar Tareas <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Tarjeta de Tareas Pendientes -->
        <div class="col">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-warning bg-opacity-10 text-warning rounded-3 me-3">
                            <i class="fas fa-hourglass-half fa-fw fa-lg"></i>
                        </div>
                        <h5 class="card-title mb-0">Pendientes</h5>
                    </div>
                    <h2 class="stats-number mb-2"><?= $tareasPendientes ?></h2>
                    <p class="text-muted mb-0">Pendientes de revisión</p>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar bg-warning" style="width: <?= $porcentajePendientes ?>%"></div>
                    </div>
                    <div class="mt-1 small text-muted"><?= number_format($porcentajePendientes, 1) ?>% del total</div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="<?= BASE_URL ?>?page=assigned_tasks&role=profesor&filter=pendiente" class="btn btn-sm btn-light w-100">
                        Ver Pendientes <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Tarjeta de Tareas Completadas -->
        <div class="col">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-success bg-opacity-10 text-success rounded-3 me-3">
                            <i class="fas fa-check-circle fa-fw fa-lg"></i>
                        </div>
                        <h5 class="card-title mb-0">Completadas</h5>
                    </div>
                    <h2 class="stats-number mb-2"><?= $tareasCompletadas ?></h2>
                    <p class="text-muted mb-0">Tareas entregadas</p>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar bg-success" style="width: <?= $porcentajeCompletadas ?>%"></div>
                    </div>
                    <div class="mt-1 small text-muted"><?= number_format($porcentajeCompletadas, 1) ?>% del total</div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="<?= BASE_URL ?>?page=assigned_tasks&role=profesor&filter=completada" class="btn btn-sm btn-light w-100">
                        Ver Completadas <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Tarjeta de Grupos -->
        <div class="col">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-info bg-opacity-10 text-info rounded-3 me-3">
                            <i class="fas fa-users fa-fw fa-lg"></i>
                        </div>
                        <h5 class="card-title mb-0">Grupos</h5>
                    </div>
                    <h2 class="stats-number mb-2"><?= $totalGrupos ?></h2>
                    <p class="text-muted mb-0"><?= $totalEstudiantes ?> estudiantes</p>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="<?= BASE_URL ?>?page=assigned_tasks&role=profesor&view=groups" class="btn btn-sm btn-light w-100">
                        Ver Grupos <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Rendimiento por Grupo y Tareas Próximas -->
    <div class="row g-4">
        <!-- Rendimiento por Grupo -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold">Rendimiento por Grupo</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($tareasPorGrupo)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-info-circle text-muted mb-2 fa-2x"></i>
                            <p class="text-muted">No hay datos de rendimiento disponibles.</p>
                            <a href="<?= BASE_URL ?>?page=task_management&role=profesor" class="btn btn-sm btn-primary">
                                Crear nueva tarea
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tareasPorGrupo as $grupoId => $grupo): ?>
                            <div class="mb-4">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fw-medium"><?= $grupo['nombre'] ?></span>
                                    <span class="text-muted small">
                                        <?= $grupo['entregas'] ?>/<?= $grupo['estudiantes'] * $grupo['tareas'] ?> entregas
                                    </span>
                                </div>
                                <?php 
                                $totalPosibles = $grupo['estudiantes'] * $grupo['tareas'];
                                $porcentaje = ($totalPosibles > 0) ? ($grupo['entregas'] / $totalPosibles) * 100 : 0;
                                
                                $colorClass = 'bg-danger';
                                if ($porcentaje >= 70) {
                                    $colorClass = 'bg-success';
                                } elseif ($porcentaje >= 40) {
                                    $colorClass = 'bg-warning';
                                }
                                ?>
                                <div class="progress" style="height: 10px">
                                    <div class="progress-bar <?= $colorClass ?>" role="progressbar" 
                                         style="width: <?= $porcentaje ?>%" 
                                         aria-valuenow="<?= $porcentaje ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Tareas Próximas -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold">Tareas Próximas</h5>
                    <a href="<?= BASE_URL ?>?page=assigned_tasks&role=profesor" class="btn btn-sm btn-outline-primary">Ver Todas</a>
                </div>
                <div class="card-body">
                    <?php if (empty($tareasProximas)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-check text-muted mb-2 fa-2x"></i>
                            <p class="text-muted">No hay tareas próximas a vencer.</p>
                        </div>
                    <?php else: ?>
                        <div class="row row-cols-1 row-cols-md-2 g-3">
                            <?php foreach ($tareasProximas as $tarea): ?>
                                <div class="col">
                                    <div class="p-3 border rounded h-100">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1 fw-semibold"><?= $tarea['titulo'] ?></h6>
                                                <?php
                                                $fecha = new DateTime($tarea['fecha_entrega']);
                                                $hoy = new DateTime();
                                                
                                                if ($fecha->format('Y-m-d') == $hoy->format('Y-m-d')) {
                                                    echo '<span class="badge bg-danger">¡Hoy vence!</span>';
                                                } else {
                                                    $diff = $hoy->diff($fecha)->days;
                                                    echo '<span class="badge bg-warning">Vence en ' . $diff . ' días</span>';
                                                }
                                                ?>
                                            </div>
                                            <div class="text-end">
                                                <a href="<?= BASE_URL ?>?page=assigned_tasks&role=profesor&task=<?= $tarea['id'] ?>" class="btn btn-sm btn-light">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="text-muted small mt-2"><?= $tarea['grupo_nombre'] ?> - <?= $tarea['materia_nombre'] ?></div>
                                        <div class="d-flex align-items-center mt-2">
                                            <div class="progress flex-grow-1" style="height: 5px;">
                                                <?php $porcentajeEntregas = ($tarea['total_estudiantes'] > 0) ? ($tarea['entregadas'] / $tarea['total_estudiantes']) * 100 : 0; ?>
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: <?= $porcentajeEntregas ?>%" 
                                                     aria-valuenow="<?= $porcentajeEntregas ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100"></div>
                                            </div>
                                            <span class="ms-2 small"><?= $tarea['entregadas'] ?>/<?= $tarea['total_estudiantes'] ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cualquier inicialización de JavaScript para el dashboard del profesor
});
</script>