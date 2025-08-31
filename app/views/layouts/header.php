<?php
if (!defined('ROOT_PATH')) {
    include_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/dirs.php');
}

// Configurar zona horaria para Colombia
date_default_timezone_set('America/Bogota');

// Cargamos los controladores necesarios
require_once(CONTROLLERS_PATH . '/TareaController.php');

// Función de ayuda para cargar assets
function asset($path) {
    return '/GestiondeTareas/public/assets/' . ltrim($path, '/');
}

// Función para calcular tiempo transcurrido
function calcularTiempoTranscurrido($fechaCreacion) {
    $timestamp_notificacion = strtotime($fechaCreacion);
    $timestamp_ahora = time();
    $diferencia_segundos = $timestamp_ahora - $timestamp_notificacion;
    
    if ($diferencia_segundos < 60) {
        return 'Hace un momento';
    } elseif ($diferencia_segundos < 3600) {
        $minutos = floor($diferencia_segundos / 60);
        return $minutos . ' minuto' . ($minutos > 1 ? 's' : '') . ' atrás';
    } elseif ($diferencia_segundos < 86400) {
        $horas = floor($diferencia_segundos / 3600);
        return $horas . ' hora' . ($horas > 1 ? 's' : '') . ' atrás';
    } else {
        $dias = floor($diferencia_segundos / 86400);
        return $dias . ' día' . ($dias > 1 ? 's' : '') . ' atrás';
    }
}

// Obtener las notificaciones para el usuario actual
$notificaciones = [];
$notificacionesNoLeidas = 0;

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
    // Inicializar controlador de tareas
    $tareaController = new TareaController();
    
    // Obtener notificaciones y conteo
    try {
        $notificaciones = $tareaController->obtenerNotificaciones($userId, 5); // Obtenemos las 5 más recientes
        $notificacionesNoLeidas = $tareaController->contarNotificacionesNoLeidas($userId);
    } catch (Exception $e) {
        error_log("Error al obtener notificaciones: " . $e->getMessage());
        
        // Fallback: consultar directamente a la base de datos si hay algún problema
        $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if (!$db->connect_error) {
            // Configurar zona horaria en MySQL
            $db->query("SET time_zone = '-05:00'");
            
            // Contar notificaciones no leídas
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM notificaciones WHERE usuario_id = ? AND leida = 0");
            if ($stmt) {
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $notificacionesNoLeidas = $row['total'];
                }
                $stmt->close();
            }
            
            // Obtener las últimas 5 notificaciones
            $stmt = $db->prepare("SELECT * FROM notificaciones WHERE usuario_id = ? ORDER BY created_at DESC LIMIT 5");
            if ($stmt) {
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $notificaciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            }
            
            $db->close();
        }
    }
}
?>

<nav class="navbar navbar-expand bg-white shadow-sm px-4 py-2">
    <div class="container-fluid">
        <!-- Buscador -->
        <div class="d-flex position-relative me-auto" style="width: 300px;">
            <input type="text" class="form-control form-control-sm ps-4" placeholder="Buscar...">
            <i class="fa-solid fa-search position-absolute top-50 start-0 translate-middle-y ms-2 text-muted"></i>
        </div>

        <!-- Contenedor derecho para notificaciones y perfil -->
        <div class="d-flex align-items-center">
            <!-- Notificaciones -->
            <div class="dropdown me-3 position-relative">
                <a class="nav-link position-relative" href="#" role="button" id="dropdownNotificaciones" 
                   data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell fa-fw fs-5"></i>
                    <?php if ($notificacionesNoLeidas > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= ($notificacionesNoLeidas > 9) ? '9+' : $notificacionesNoLeidas ?>
                        <span class="visually-hidden">notificaciones no leídas</span>
                    </span>
                    <?php endif; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" 
                    aria-labelledby="dropdownNotificaciones" 
                    style="width: 320px; max-height: 400px; overflow-y: auto;">
                    <li class="border-bottom p-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Notificaciones</h6>
                        <?php if ($notificacionesNoLeidas > 0): ?>
                            <a href="<?= BASE_URL ?>?action=markAllAsRead" class="btn btn-sm btn-link text-decoration-none">Marcar como leídas</a>
                        <?php endif; ?>
                    </li>
                    
                    <?php if (empty($notificaciones)): ?>
                        <li class="text-center py-3 text-muted">
                            <i class="fas fa-bell-slash mb-2 fs-4"></i>
                            <p class="mb-0">No tienes notificaciones</p>
                        </li>
                    <?php else: ?>
                        <?php foreach ($notificaciones as $notificacion): ?>
                            <li>
                                <a class="dropdown-item py-2 <?= $notificacion['leida'] ? '' : 'bg-light' ?>" 
                                   href="<?= BASE_URL ?>?page=notifications&id=<?= $notificacion['id'] ?>">
                                    <div class="d-flex">
                                        <div class="me-3 mt-1">
                                            <div class="icon-box-sm rounded-circle <?= $notificacion['leida'] ? 'bg-secondary' : 'bg-primary' ?> bg-opacity-10 
                                            <?= $notificacion['leida'] ? 'text-secondary' : 'text-primary' ?>">
                                                <i class="fas fa-bell fa-fw"></i>
                                            </div>
                                        </div>
                                        <div>                                            <p class="mb-1 text-wrap <?= $notificacion['leida'] ? '' : 'fw-bold' ?>"><?= htmlspecialchars($notificacion['titulo']) ?></p>
                                            <small class="text-muted">
                                                <?= calcularTiempoTranscurrido($notificacion['created_at']) ?>
                                            </small>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <li class="border-top">
                            <a class="dropdown-item text-center py-2" href="<?= BASE_URL ?>?page=notifications&role=<?= strtolower($_SESSION['rol'] ?? '') ?>">
                                Ver todas las notificaciones
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Separador vertical -->
            <div class="vr me-3"></div>

            <!-- Perfil de usuario -->
            <div class="d-flex align-items-center">
                <img src="<?php echo asset('images/avatar.png'); ?>" 
                     class="rounded-circle me-2" 
                     width="32" 
                     height="32">
                <div class="d-none d-sm-block">
                    <p class="mb-0 text-dark"><?= $_SESSION['nombre_completo'] ?? 'Usuario' ?></p>
                    <small class="text-muted"><?= ucfirst($_SESSION['rol'] ?? 'Rol') ?></small>
                </div>
            </div>
        </div>
    </div>
</nav>