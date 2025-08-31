<?php
if (!defined('ROOT_PATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/dirs.php');
}

// Verificar que el usuario sea un profesor
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'profesor') {
    echo "<div class='alert alert-danger'>Acceso no autorizado</div>";
    exit;
}

// Cargar controladores necesarios
require_once(CONTROLLERS_PATH . '/TareaController.php');
require_once(CONTROLLERS_PATH . '/MateriaController.php');
require_once(CONTROLLERS_PATH . '/GrupoController.php');

// Inicializar controladores
$tareaController = new TareaController();
$materiaController = new MateriaController();
$grupoController = new GrupoController();

// Obtener datos para formularios
$profesorId = $_SESSION['user_id'];
$grupos = $grupoController->obtenerGruposPorProfesor($profesorId);
$materias = $materiaController->obtenerMateriasPorProfesor($profesorId);

// Manejar acciones de tareas
$mensaje = null;
$tipoMensaje = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {

    if ($_POST['accion'] === 'crear') {
        // Añadir el ID del profesor actual
        $_POST['profesor_id'] = $profesorId;

        // Crear la tarea
        $resultado = $tareaController->crearTarea($_POST);

        if (isset($resultado['success'])) {
            $mensaje = "Tarea creada exitosamente.";
            $tipoMensaje = "success";
        } else {
            $mensaje = "Error: " . ($resultado['error'] ?? "No se pudo crear la tarea");
            $tipoMensaje = "danger";
        }
    } elseif ($_POST['accion'] === 'actualizar') {
        // Verificar que el ID de la tarea esté presente
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Actualizar la tarea
            $resultado = $tareaController->actualizarTarea($_POST);

            if (isset($resultado['success'])) {
                $mensaje = "Tarea actualizada exitosamente.";
                $tipoMensaje = "success";
            } else {
                $mensaje = "success: " . ($resultado['success'] ?? "Se elimino la tarea");
                $tipoMensaje = "success";
            }
        } else {
            $mensaje = "Error: ID de tarea no válido.";
            $tipoMensaje = "danger";
        }
    } elseif ($_POST['accion'] === 'eliminar') {
        // Verificar que el ID de la tarea esté presente
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Eliminar la tarea
            $resultado = $tareaController->eliminarTarea($_POST['id']);

            // Para AJAX, devolver JSON
            if (
                !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
            ) {
                header('Content-Type: application/json');
                echo json_encode($resultado);
                exit;
            }

            // Para peticiones normales
            if (isset($resultado['success'])) {
                $mensaje = "Tarea eliminada exitosamente.";
                $tipoMensaje = "success";
            } else {
                $mensaje = "Error: " . ($resultado['error'] ?? "No se pudo eliminar la tarea");
                $tipoMensaje = "danger";
            }
        } else {
            $resultado = ['error' => 'ID de tarea no válido.'];

            // Para AJAX
            if (
                !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
            ) {
                header('Content-Type: application/json');
                echo json_encode($resultado);
                exit;
            }

            $mensaje = "Error: ID de tarea no válido.";
            $tipoMensaje = "danger";
        }
    }
}

// Obtener todas las tareas del profesor
$tareas = $tareaController->obtenerTareasAsignadasConEntregas($profesorId);
?>

<div class="container-fluid py-4">
    <h1 class="position-relative header-page">Gestión de Tareas</h1>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipoMensaje ?> alert-dismissible fade show" role="alert">
            <?= $mensaje ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Crear Nueva Tarea</h5>
                </div>
                <div class="card-body">
                    <form method="post" id="formCrearTarea">
                        <input type="hidden" name="accion" value="crear">
                        <input type="hidden" name="profesor_id" value="<?= $profesorId ?>">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="titulo" class="form-label">Título de la Tarea <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="titulo" name="titulo" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="fecha_entrega" class="form-label">Fecha de Entrega <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="fecha_entrega" name="fecha_entrega" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="grupo_id" class="form-label">Grupo <span class="text-danger">*</span></label>
                                <select class="form-select" id="grupo_id" name="grupo_id" required>
                                    <option value="">Seleccione un grupo</option>
                                    <?php foreach ($grupos as $grupo): ?>
                                        <option value="<?= $grupo['id'] ?>"><?= htmlspecialchars($grupo['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="materia_id" class="form-label">Materia <span class="text-danger">*</span></label>
                                <select class="form-select" id="materia_id" name="materia_id" required>
                                    <option value="">Seleccione una materia</option>
                                    <?php foreach ($materias as $materia): ?>
                                        <option value="<?= $materia['id'] ?>"><?= htmlspecialchars($materia['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-2"></i>Crear Tarea
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Mis Tareas Asignadas</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($tareas)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> No tiene tareas asignadas.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Título</th>
                                        <th>Grupo</th>
                                        <th>Materia</th>
                                        <th>Fecha Entrega</th>
                                        <th>Entregas</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tareas as $tarea):
                                        // Determinar clase de badge según estado
                                        $estadoClase = match ($tarea['estado']) {
                                            'pendiente' => 'bg-warning',
                                            'completada' => 'bg-success',
                                            'calificada' => 'bg-info',
                                            'vencida' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($tarea['titulo']) ?></td>
                                            <td><?= htmlspecialchars($tarea['grupo_nombre']) ?></td>
                                            <td><?= htmlspecialchars($tarea['materia_nombre']) ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($tarea['fecha_entrega'])) ?></td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?= $tarea['entregadas'] ?> / <?= $tarea['total_estudiantes'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?= $estadoClase ?>">
                                                    <?= ucfirst($tarea['estado']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?= BASE_URL ?>?page=task_submissions&tarea_id=<?= $tarea['id'] ?>" class="btn btn-sm btn-info me-2">
                                                    <i class="fas fa-eye"></i> Ver entregas
                                                </a>
                                                <button type="button" class="btn btn-sm btn-warning edit-task-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editTaskModal"
                                                    data-id="<?= $tarea['id'] ?>"
                                                    data-titulo="<?= htmlspecialchars($tarea['titulo']) ?>"
                                                    data-descripcion="<?= htmlspecialchars($tarea['descripcion']) ?>"
                                                    data-fecha_entrega="<?= date('Y-m-d\TH:i', strtotime($tarea['fecha_entrega'])) ?>"
                                                    data-materia_id="<?= $tarea['materia_id'] ?? $tarea['materiaId'] ?? '' ?>"
                                                    data-grupo_id="<?= $tarea['grupo_id'] ?? $tarea['grupoId'] ?? '' ?>">
                                                    <i class="fas fa-edit"></i> Editar
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger delete-task-btn" data-id="<?= $tarea['id'] ?>">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </button>
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

<!-- Modal de Edición -->
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="editTaskModalLabel">Editar Tarea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" id="formEditarTarea">
                    <input type="hidden" name="accion" value="actualizar">
                    <input type="hidden" name="id" id="edit_tarea_id">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_titulo" class="form-label">Título de la Tarea <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_titulo" name="titulo" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="edit_fecha_entrega" class="form-label">Fecha de Entrega <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="edit_fecha_entrega" name="fecha_entrega" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_grupo_id" class="form-label">Grupo <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_grupo_id" name="grupo_id" required>
                                <option value="">Seleccione un grupo</option>
                                <?php foreach ($grupos as $grupo): ?>
                                    <option value="<?= $grupo['id'] ?>"><?= htmlspecialchars($grupo['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="edit_materia_id" class="form-label">Materia <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_materia_id" name="materia_id" required>
                                <option value="">Seleccione una materia</option>
                                <?php foreach ($materias as $materia): ?>
                                    <option value="<?= $materia['id'] ?>"><?= htmlspecialchars($materia['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="3"></textarea>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Establecer fecha mínima como hoy
        const today = new Date();
        const fechaEntregaInput = document.getElementById('fecha_entrega');

        // Formatear fecha para el input datetime-local (YYYY-MM-DDThh:mm)
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        const hours = String(today.getHours()).padStart(2, '0');
        const minutes = String(today.getMinutes()).padStart(2, '0');

        const formattedDate = `${year}-${month}-${day}T${hours}:${minutes}`;
        fechaEntregaInput.min = formattedDate;

        // Validación del formulario de crear tarea
        document.getElementById('formCrearTarea').addEventListener('submit', function(e) {
            const titulo = document.getElementById('titulo').value.trim();
            const fechaEntrega = document.getElementById('fecha_entrega').value;
            const grupoId = document.getElementById('grupo_id').value;
            const materiaId = document.getElementById('materia_id').value;

            if (!titulo || !fechaEntrega || !grupoId || !materiaId) {
                e.preventDefault();
                alert('Por favor, complete todos los campos requeridos.');
            }
        });

        // Validación del formulario de editar tarea
        document.getElementById('formEditarTarea').addEventListener('submit', function(e) {
            const titulo = document.getElementById('edit_titulo').value.trim();
            const fechaEntrega = document.getElementById('edit_fecha_entrega').value;
            const grupoId = document.getElementById('edit_grupo_id').value;
            const materiaId = document.getElementById('edit_materia_id').value;

            if (!titulo || !fechaEntrega || !grupoId || !materiaId) {
                e.preventDefault();
                alert('Por favor, complete todos los campos requeridos.');
                return false;
            }

            return true;
        });
    });

    // Lógica para el modal de edición de tareas
    const editTaskModal = document.getElementById('editTaskModal');
    editTaskModal.addEventListener('show.bs.modal', function(event) {
        // Botón que disparó el modal
        const button = event.relatedTarget;

        // Extraer información de los atributos data-*
        const id = button.getAttribute('data-id');
        const titulo = button.getAttribute('data-titulo');
        const descripcion = button.getAttribute('data-descripcion');
        const fechaEntrega = button.getAttribute('data-fecha_entrega');
        const materiaId = button.getAttribute('data-materia_id');
        const grupoId = button.getAttribute('data-grupo_id');

        // Actualizar los campos del formulario del modal
        const modalTitle = editTaskModal.querySelector('.modal-title');
        const form = editTaskModal.querySelector('#formEditarTarea');
        const inputId = form.querySelector('#edit_tarea_id');
        const inputTitulo = form.querySelector('#edit_titulo');
        const inputDescripcion = form.querySelector('#edit_descripcion');
        const inputFechaEntrega = form.querySelector('#edit_fecha_entrega');
        const selectMateria = form.querySelector('#edit_materia_id');
        const selectGrupo = form.querySelector('#edit_grupo_id');

        modalTitle.textContent = `Editar Tarea: ${titulo}`;
        inputId.value = id;
        inputTitulo.value = titulo;
        inputDescripcion.value = descripcion;
        inputFechaEntrega.value = fechaEntrega;
        selectMateria.value = materiaId;
        selectGrupo.value = grupoId;
    });

    // Manejar la eliminación de tareas
    document.querySelectorAll('.delete-task-btn').forEach(button => {
        button.addEventListener('click', function() {
            const tareaId = this.getAttribute('data-id');

            if (confirm('¿Está seguro de que desea eliminar esta tarea? Esta acción es irreversible y eliminará también las entregas asociadas.')) {
                const formData = new FormData();
                formData.append('accion', 'eliminar');
                formData.append('id', tareaId);

                fetch(window.location.href, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        const contentType = response.headers.get('content-type');
                        if (contentType && contentType.indexOf('application/json') !== -1) {
                            return response.json();
                        } else {
                            // Si no es JSON, recargar la página
                            location.reload();
                            throw new Error('Respuesta no JSON esperada, recargando página.');
                        }
                    })
                    .then(data => {
                        if (data.success) {
                            alert(data.success);
                            location.reload();
                        } else {
                            alert('Error: ' + data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        if (error.message !== 'Respuesta no JSON esperada, recargando página.') {
                            alert('Ocurrió un error al procesar la solicitud.');
                        }
                    });
            }
        });
    });
</script>