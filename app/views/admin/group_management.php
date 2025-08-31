<?php
if (!defined('ROOT_PATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/dirs.php');
}
require_once(CONTROLLERS_PATH . '/GrupoController.php');

$grupoController = new GrupoController();
$stats = $grupoController->obtenerEstadisticas();
$profesores = $grupoController->obtenerProfesores();

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $grupoController->procesarAccion();
    if ($resultado) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                showAlert('" . 
                ($resultado['success'] ?? $resultado['error']) . "', '', '" . 
                (isset($resultado['success']) ? 'success' : 'error') . 
                "');
            });
        </script>";
    }
}
?>

<!-- jQuery y DataTables JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<div class="container-fluid px-4">
    <h1 class="mt-4">Gestión de Grupos</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Inicio</a></li>
        <li class="breadcrumb-item active">Gestión de Grupos</li>
    </ol>

    <!-- Estadísticas -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $stats['total_grupos'] ?></h4>
                            <div class="small">Grupos Activos</div>
                        </div>
                        <i class="fas fa-users fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $stats['total_estudiantes'] ?></h4>
                            <div class="small">Estudiantes</div>
                        </div>
                        <i class="fas fa-user-graduate fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $stats['total_profesores'] ?></h4>
                            <div class="small">Profesores</div>
                        </div>
                        <i class="fas fa-chalkboard-teacher fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $stats['total_materias'] ?></h4>
                            <div class="small">Materias Activas</div>
                        </div>
                        <i class="fas fa-book fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario y Lista -->
    <div class="row">
        <!-- Formulario Nuevo Grupo -->
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-users-class me-1"></i>
                    Nuevo Grupo
                </div>
                <div class="card-body">
                    <form id="grupoForm" method="POST">
                        <input type="hidden" name="accion" value="crear">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del Grupo</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="profesor_id" class="form-label">Profesor Titular</label>
                            <select class="form-select" id="profesor_id" name="profesor_id">
                                <option value="">Seleccione un profesor...</option>
                                <?php foreach ($profesores as $profesor): ?>
                                <option value="<?= $profesor['id'] ?>">
                                    <?= htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellidos']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Crear Grupo</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Lista de Grupos -->
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-table me-1"></i>
                    Grupos Registrados
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="gruposTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Grupo</th>
                                    <th>Profesor Titular</th>
                                    <th>Estudiantes</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grupoController->obtenerTodos() as $grupo): ?>
                                <tr>
                                    <td><?= htmlspecialchars($grupo['nombre']) ?></td>
                                    <td><?= htmlspecialchars($grupo['profesor_nombre'] . ' ' . $grupo['profesor_apellidos']) ?></td>
                                    <td class="text-center"><?= $grupo['total_estudiantes'] ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $grupo['activo'] ? 'success' : 'danger' ?>">
                                            <?= $grupo['activo'] ? 'Activo' : 'Inactivo' ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-primary" onclick="editarGrupo(<?= $grupo['id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-<?= $grupo['activo'] ? 'danger' : 'success' ?>" 
                                                onclick="cambiarEstado(<?= $grupo['id'] ?>, <?= $grupo['activo'] ? 0 : 1 ?>)">
                                            <i class="fas fa-<?= $grupo['activo'] ? 'times' : 'check' ?>"></i>
                                        </button>
                                        <button class="btn btn-sm btn-info" onclick="abrirMatriculacion(<?= $grupo['id'] ?>)">
                                            <i class="fas fa-users"></i>
                                        </button>
                                        <button class="btn btn-sm btn-warning" onclick="gestionarMaterias(<?= $grupo['id'] ?>)">
                                            <i class="fas fa-book"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sección de matriculación -->
<div class="container-fluid px-4 matriculacion-section" id="matriculacionSection" style="display:none;">
    <div class="row">
        <div class="col-12">
            <button class="btn btn-secondary mb-3" onclick="volverAGrupos()">
                <i class="fas fa-arrow-left"></i> Volver a Grupos
            </button>
            <h2 class="mb-4">Gestión de Estudiantes del Grupo: <span id="nombreGrupo"></span></h2>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Estudiantes Disponibles</h5>
                </div>
                <div class="card-body">
                    <input type="hidden" id="grupo_id_matricula">
                    <select multiple class="form-select" id="estudiantes_disponibles" 
                            style="height: 400px" size="15">
                    </select>
                </div>
                <div class="card-footer">
                    <button type="button" class="btn btn-success w-100" onclick="matricularSeleccionados()">
                        <i class="fas fa-user-plus"></i> Matricular Seleccionados
                    </button>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Estudiantes Matriculados</h5>
                </div>
                <div class="card-body">
                    <select multiple class="form-select" id="estudiantes_matriculados" 
                            style="height: 400px" size="15">
                    </select>
                </div>
                <div class="card-footer">
                    <button type="button" class="btn btn-danger w-100" onclick="desmatricularSeleccionados()">
                        <i class="fas fa-user-minus"></i> Desmatricular Seleccionados
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sección de Gestión de Materias -->
<div class="container-fluid px-4 gestion-materias-section" id="gestionMateriasSection" style="display:none;">
    <div class="row">
        <div class="col-12">
            <button class="btn btn-secondary mb-3" onclick="volverAGrupos()">
                <i class="fas fa-arrow-left"></i> Volver a Grupos
            </button>
            <h2 class="mb-4">Gestión de Materias del Grupo: <span id="nombreGrupoMaterias"></span></h2>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-book me-1"></i>
                    Materias Disponibles
                </div>
                <div class="card-body">
                    <input type="hidden" id="grupo_id_materias">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="materiasDisponiblesTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Profesor</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Se llenará dinámicamente con AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Edición -->
<div class="modal fade" id="editarGrupoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Grupo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editarGrupoForm" method="POST">
                    <input type="hidden" name="accion" value="actualizar">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label for="edit_nombre" class="form-label">Nombre del Grupo</label>
                        <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_profesor_id" class="form-label">Profesor Titular</label>
                        <select class="form-select" id="edit_profesor_id" name="profesor_id">
                            <option value="">Seleccione un profesor...</option>
                            <?php foreach ($profesores as $profesor): ?>
                            <option value="<?= $profesor['id'] ?>">
                                <?= htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellidos']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="editarGrupoForm" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

<script>
// Definir todas las funciones en el ámbito global
const initFunctions = {
    editarGrupo: function(id) {
        console.log('Editando grupo con ID:', id); 
    
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Cargando...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }
    
    $.ajax({
        url: '<?= BASE_URL ?>/app/ajax/get_grupo.php',
        method: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            console.log('Respuesta del servidor:', response); 
            
            if (typeof Swal !== 'undefined') {
                Swal.close();
            }
            
            if (response.success) {
                const grupo = response.data;
                
                // Llenar el formulario
                $('#edit_id').val(grupo.id);
                $('#edit_nombre').val(grupo.nombre);
                $('#edit_descripcion').val(grupo.descripcion || '');
                $('#edit_profesor_id').val(grupo.profesor_id || '');
                
                // Mostrar el modal
                var modal = new bootstrap.Modal(document.getElementById('editarGrupoModal'));
                modal.show();
            } else {
                showAlert('Error', response.error || 'No se pudieron cargar los datos del grupo', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', {xhr, status, error}); 
            
            if (typeof Swal !== 'undefined') {
                Swal.close();
            }
            
            showAlert('Error', 'Error en la conexión: ' + error, 'error');
            }
        });
    },

    cambiarEstado: function(id, estado) {
        confirmAction(
            '¿Estás seguro?',
            'Esta acción cambiará el estado del grupo',
            'warning'
        ).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="accion" value="cambiarEstado">
                    <input type="hidden" name="id" value="${id}">
                    <input type="hidden" name="activo" value="${estado}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    },

    abrirMatriculacion: function(grupo_id) {
        console.log('Abriendo matriculación para grupo:', grupo_id);
    
    // Ocultar la sección principal
    $('.container-fluid').first().hide();
    $('#matriculacionSection').show();
    $('#grupo_id_matricula').val(grupo_id);
    
    // Obtener información del grupo
    $.ajax({
        url: '<?= BASE_URL ?>/app/ajax/get_grupo.php',
        method: 'GET',
        dataType: 'json',
        data: { id: grupo_id },
        success: function(response) {
            if (response.success) {
                $('#nombreGrupo').text(response.data.nombre);
                cargarEstudiantes(grupo_id);
            } else {
                showAlert('Error', response.error || 'Error al cargar información del grupo', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al obtener grupo:', error);
            showAlert('Error', 'Error en la conexión', 'error');
        }
    });
    },

    cargarMateriasDisponibles: function(grupoId) {
        console.log('Cargando materias para grupo:', grupoId);

        if (!grupoId || grupoId <= 0) {
        console.error('ID de grupo no válido:', grupoId);
        showAlert('Error', 'ID de grupo no válido', 'error');
        return;
    }
        
        $.ajax({
            url: '<?= BASE_URL ?>/app/ajax/get_materias_grupo.php',
            method: 'GET',
            data: { grupo_id: grupoId },
            dataType: 'json',
            success: function(response) {
                console.log('Materias obtenidas:', response);
                
                if (response.success) {
                    initFunctions.actualizarTablaMaterias(response.data || []);
                } else {
                    showAlert('Error', response.error || 'Error al cargar materias', 'error');
                    initFunctions.actualizarTablaMaterias([]);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX al cargar materias:', error);
                showAlert('Error', 'Error de conexión al cargar materias', 'error');
                initFunctions.actualizarTablaMaterias([]);
            }
        });
    },

    actualizarTablaMaterias: function(materias) {
        const tabla = $('#materiasDisponiblesTable tbody');
        tabla.empty();
    
    if (!Array.isArray(materias) || materias.length === 0) {
        tabla.append(`
            <tr>
                <td colspan="6" class="text-center">No hay materias disponibles</td>
            </tr>
        `);
        return;
    }
    
    materias.forEach(function(materia) {
        const asignada = parseInt(materia.asignada) === 1 || parseInt(materia.asignacion_activa) === 1;
        const profesorNombre = (materia.profesor_nombre && materia.profesor_apellidos) ? 
        `${materia.profesor_nombre} ${materia.profesor_apellidos}` : 
        'No asignado';
        
        // Escapar caracteres especiales en el nombre para evitar errores de JS
        const nombreEscapado = (materia.nombre || 'N/A').replace(/'/g, "\\'");
        
        const row = `
            <tr>
                <td>${materia.codigo || 'N/A'}</td>
                <td>${materia.nombre || 'N/A'}</td>
                <td>${materia.descripcion || '-'}</td>
                <td class="text-center">${profesorNombre}</td>
                <td class="text-center">
                    <span class="badge bg-${asignada ? 'success' : 'secondary'}">
                        ${asignada ? 'Activo' : 'Desactivado'}
                    </span>
                </td>
                
            </tr>
        `;
        tabla.append(row);
    });
    },

    mostrarModalAsignarMateria: function(materiaId, nombreMateria) {
        $('#materia_id_asignacion').val(materiaId);
        $('#nombre_materia_asignacion').text(nombreMateria);
        $('#profesor_id_asignacion').val('');
        
        const modal = new bootstrap.Modal(document.getElementById('asignarProfesorModal'));
        modal.show();
    },

     desasignarMateria: function(materiaId) {
        const grupoId = $('#grupo_id_materias').val();
        
        console.log('Desasignando materia:', { materiaId, grupoId });
        
        if (!grupoId || !materiaId) {
            showAlert('Error', 'Datos incompletos para desasignar materia', 'error');
            return;
        }
        
        confirmAction(
            '¿Estás seguro?',
            'Esta acción desasignará el profesor',
            'warning'
        ).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Desasignando materia...',
                        text: 'Por favor espere...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                }
                
                $.ajax({
                    url: '<?= BASE_URL ?>/app/ajax/gestionar_materia_grupo.php',
                    method: 'POST',
                    dataType: 'json',
                    timeout: 15000,
                    data: {
                        accion: 'desasignar',
                        grupo_id: parseInt(grupoId),
                        materia_id: parseInt(materiaId)
                    },
                    success: function(response) {
                        console.log('Respuesta desasignación:', response);
                        
                        if (typeof Swal !== 'undefined') {
                            Swal.close();
                        }
                        
                        if (response && response.success) {
                            showAlert('Éxito', response.message || 'Materia desasignada correctamente', 'success');
                    
                            setTimeout(() => {
                                initFunctions.cargarMateriasDisponibles(grupoId);
                            }, 800);
                        } else {
                            const errorMessage = response?.error || 'Error al desasignar materia';
                            showAlert('Error', errorMessage, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error al desasignar materia:', {
                            status: xhr.status,
                            responseText: xhr.responseText,
                            error: error
                        });
                        
                        if (typeof Swal !== 'undefined') {
                            Swal.close();
                        }
                        
                        let errorMsg = 'Error de conexión al desasignar materia';
                        if (xhr.status === 404) {
                            errorMsg = 'Archivo no encontrado. Verifique la ruta del archivo.';
                        } else if (xhr.status === 500) {
                            errorMsg = 'Error interno del servidor';
                        } else if (status === 'timeout') {
                            errorMsg = 'Tiempo de espera agotado';
                        }
                        
                        showAlert('Error', errorMsg, 'error');
                    }
                });
            }
        });
    },

    volverAGrupos: function() {
        $('#matriculacionSection').hide();
        $('#gestionMateriasSection').hide();
        $('.container-fluid').first().show();
    },

    cargarEstudiantes: function(grupo_id) {
        // Cargar estudiantes no matriculados
    $.ajax({
        url: '<?= BASE_URL ?>/app/ajax/get_estudiantes.php',
        method: 'GET',
        dataType: 'json',
        data: {
            accion: 'no_matriculados',
            grupo_id: grupo_id
        },
        success: function(data) {
            $('#estudiantes_disponibles').empty();
            if (Array.isArray(data) && data.length > 0) {
                data.forEach(function(estudiante) {
                    $('#estudiantes_disponibles').append(new Option(
                        estudiante.nombre + ' ' + estudiante.apellidos,
                        estudiante.id
                    ));
                });
            } else {
                $('#estudiantes_disponibles').append(new Option('No hay estudiantes disponibles', ''));
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar estudiantes disponibles:', error);
            $('#estudiantes_disponibles').empty();
            $('#estudiantes_disponibles').append(new Option('Error al cargar datos', ''));
        }
    });

    // Cargar estudiantes matriculados
    $.ajax({
        url: '<?= BASE_URL ?>/app/ajax/get_estudiantes.php',
        method: 'GET',
        dataType: 'json',
        data: {
            accion: 'matriculados',
            grupo_id: grupo_id
        },
        success: function(data) {
            $('#estudiantes_matriculados').empty();
            if (Array.isArray(data) && data.length > 0) {
                data.forEach(function(estudiante) {
                    $('#estudiantes_matriculados').append(new Option(
                        estudiante.nombre + ' ' + estudiante.apellidos,
                        estudiante.id
                    ));
                });
            } else {
                $('#estudiantes_matriculados').append(new Option('No hay estudiantes matriculados', ''));
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar estudiantes matriculados:', error);
            $('#estudiantes_matriculados').empty();
            $('#estudiantes_matriculados').append(new Option('Error al cargar datos', ''));
        }
    });
    },

    matricularSeleccionados: function() {
        const grupo_id = $('#grupo_id_matricula').val();
    const estudiantes = $('#estudiantes_disponibles').val();
    
    if (!estudiantes || !estudiantes.length) {
        showAlert('Error', 'Selecciona al menos un estudiante', 'error');
        return;
    }

    // Mostrar loading
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Matriculando estudiantes...',
            text: 'Por favor espere...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }

    $.ajax({
        url: '<?= BASE_URL ?>/app/ajax/matricular.php',
        method: 'POST',
        dataType: 'json',
        data: {
            accion: 'matricular',
            grupo_id: grupo_id,
            estudiantes: estudiantes
        },
        success: function(response) {
            if (typeof Swal !== 'undefined') {
                Swal.close();
            }
            
            if (response.success) {
                showAlert('Éxito', response.message, 'success');
                cargarEstudiantes(grupo_id);
            } else {
                showAlert('Error', response.error || 'Error al matricular estudiantes', 'error');
            }
        },
        error: function(xhr, status, error) {
            if (typeof Swal !== 'undefined') {
                Swal.close();
            }
            showAlert('Error', 'Error en la conexión: ' + error, 'error');
        }
    });
    },

    desmatricularSeleccionados: function() {
        const grupo_id = $('#grupo_id_matricula').val();
    const estudiantes = $('#estudiantes_matriculados').val();
    
    if (!estudiantes || !estudiantes.length) {
        showAlert('Error', 'Selecciona al menos un estudiante', 'error');
        return;
    }

    confirmAction('¿Estás seguro?', 'Esta acción desmatriculará a los estudiantes seleccionados', 'warning')
    .then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Desmatriculando estudiantes...',
                    text: 'Por favor espere...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            }
            
            let completados = 0;
            let errores = 0;
            const total = estudiantes.length;
            
            estudiantes.forEach(function(estudiante_id) {
                $.ajax({
                    url: '<?= BASE_URL ?>/app/ajax/matricular.php',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        accion: 'desmatricular',
                        grupo_id: grupo_id,
                        estudiante_id: estudiante_id
                    },
                    success: function(response) {
                        completados++;
                        if (!response.success) {
                            errores++;
                        }
                        
                        // Si es el último
                        if (completados === total) {
                            if (typeof Swal !== 'undefined') {
                                Swal.close();
                            }
                            
                            if (errores === 0) {
                                showAlert('Éxito', 'Estudiantes desmatriculados correctamente', 'success');
                            } else {
                                showAlert('Advertencia', `Se desmatricularon ${total - errores} de ${total} estudiantes`, 'warning');
                            }
                            cargarEstudiantes(grupo_id);
                        }
                    },
                    error: function() {
                        completados++;
                        errores++;
                        
                        if (completados === total) {
                            if (typeof Swal !== 'undefined') {
                                Swal.close();
                            }
                            showAlert('Error', 'Error al desmatricular algunos estudiantes', 'error');
                            cargarEstudiantes(grupo_id);
                        }
                    }
                });
            });
        }
    });
    },

    gestionarMaterias: function(grupo_id) {
    console.log('Gestionando materias para grupo:', grupo_id);
    
    // Ocultar la sección principal
    $('.container-fluid').first().hide();
    $('#gestionMateriasSection').show();
    $('#grupo_id_materias').val(grupo_id);
    
    // Obtener información del grupo
    $.ajax({
        url: '<?= BASE_URL ?>/app/ajax/get_grupo.php',
        method: 'GET',
        dataType: 'json',
        data: { id: grupo_id },
        success: function(response) {
            if (response.success) {
                $('#nombreGrupoMaterias').text(response.data.nombre);
                initFunctions.cargarMateriasDisponibles(grupo_id);
            } else {
                showAlert('Error', response.error || 'Error al cargar información del grupo', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al obtener grupo:', error);
            showAlert('Error', 'Error en la conexión', 'error');
        }
    });
},

    asignarMateriaConProfesor: function() {
        const grupoId = $('#grupo_id_materias').val();
        const materiaId = $('#materia_id_asignacion').val();
        const profesorId = $('#profesor_id_asignacion').val();
        
        console.log('Datos de asignación:', { grupoId, materiaId, profesorId });
    
        if (!profesorId) {
            showAlert('Error', 'Debe seleccionar un profesor', 'error');
            return;
        }
        
        // Mostrar loading
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Asignando materia...',
                text: 'Por favor espere...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }
        
        $.ajax({
            url: '<?= BASE_URL ?>/app/ajax/asignar_materia.php',
            method: 'POST',
            dataType: 'json',
            timeout: 10000,
            data: {
                accion: 'asignar',
                grupo_id: parseInt(grupoId),
                materia_id: parseInt(materiaId),
                profesor_id: parseInt(profesorId)
            },
            success: function(response) {
                console.log('Respuesta asignación:', response);
                
                if (typeof Swal !== 'undefined') {
                    Swal.close();
                }
                
                // Cerrar el modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('asignarProfesorModal'));
                if (modal) {
                    modal.hide();
                }
                
                if (response && response.success) {
                    showAlert('Éxito', response.message || 'Materia asignada correctamente', 'success');
                    // Recargar la tabla después de un pequeño delay
                    setTimeout(() => {
                        initFunctions.cargarMateriasDisponibles(grupoId);
                    }, 500);
                } else {
                    const errorMessage = response?.error || 'Error al asignar materia';
                    showAlert('Error', errorMessage, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al asignar materia:', {
                    status: xhr.status,
                    responseText: xhr.responseText,
                    error: error
                });
                
                if (typeof Swal !== 'undefined') {
                    Swal.close();
                }
                
                // Cerrar el modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('asignarProfesorModal'));
                if (modal) {
                    modal.hide();
                }
                
                let errorMsg = 'Error de conexión al asignar materia';
                if (xhr.status === 404) {
                    errorMsg = 'Archivo PHP no encontrado';
                } else if (xhr.status === 500) {
                    errorMsg = 'Error interno del servidor';
                }
                
                showAlert('Error', errorMsg, 'error');
            }
        });
    }
};

// Asignar todas las funciones al objeto window
Object.keys(initFunctions).forEach(key => {
    window[key] = initFunctions[key];
});

$(document).ready(function() {
    // Inicialización de DataTables
    $('#gruposTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        order: [[0, 'asc']]
    });
});
</script>
