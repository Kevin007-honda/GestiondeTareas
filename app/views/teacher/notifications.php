<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/dirs.php');
require_once(CONTROLLERS_PATH . '/NotificationController.php');
require_once(CONTROLLERS_PATH . '/AuthController.php');

// Configurar zona horaria para Colombia
date_default_timezone_set('America/Bogota');

$auth = new AuthController();
$notificationController = new NotificationController();

// Verificar sesión activa
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/app/views/auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$notificaciones = $notificationController->obtenerNotificaciones($user_id);
?>

<h1 class="position-relative header-page">Notificaciones</h1>
<div class="dashboard-page">
    <div class="wrapper">
        <div class="latest mega" data-aos="fade-up">            <h2 class="section-header">Notificaciones Recientes</h2>
            <?php if (!empty($notificaciones)): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted"><?= count($notificaciones) ?> notificación<?= count($notificaciones) > 1 ? 'es' : '' ?></span>
                    <button class="btn btn-sm btn-outline-primary" onclick="marcarTodasComoLeidas()">
                        <i class="fa-solid fa-check-double"></i> Marcar todas como leídas
                    </button>
                </div>
            <?php endif; ?>
            <div class="data" id="notificaciones-container">                <?php if (empty($notificaciones)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-bell-slash"></i>
                        <h4>No tienes notificaciones</h4>
                        <p>Cuando recibas nuevas entregas de estudiantes o haya actividad en tus tareas, aparecerán aquí.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notificaciones as $notif): ?>
                        <?php 
                            $colorIcono = $notif['leida'] == 0 ? "c-orange" : "c-blue";
                            $icono = $notif['leida'] == 0 ? "fa-bell" : "fa-check-circle";
                            
                            // Determinar el icono específico según el tipo de notificación
                            if (strpos($notif['titulo'], 'Nueva entrega') !== false) {
                                $icono = $notif['leida'] == 0 ? "fa-file-upload" : "fa-file-check";
                            } elseif (strpos($notif['titulo'], 'Tarea asignada') !== false) {
                                $icono = $notif['leida'] == 0 ? "fa-tasks" : "fa-check-circle";
                            }
                            
                            // Calcular tiempo transcurrido
                            $timestamp_notificacion = strtotime($notif['created_at']);
                            $timestamp_ahora = time();
                            $diferencia_segundos = $timestamp_ahora - $timestamp_notificacion;
                            
                            $tiempo_transcurrido = '';
                            if ($diferencia_segundos < 60) {
                                $tiempo_transcurrido = 'Hace un momento';
                            } elseif ($diferencia_segundos < 3600) {
                                $minutos = floor($diferencia_segundos / 60);
                                $tiempo_transcurrido = $minutos . ' minuto' . ($minutos > 1 ? 's' : '') . ' atrás';
                            } elseif ($diferencia_segundos < 86400) {
                                $horas = floor($diferencia_segundos / 3600);
                                $tiempo_transcurrido = $horas . ' hora' . ($horas > 1 ? 's' : '') . ' atrás';
                            } else {
                                $dias = floor($diferencia_segundos / 86400);
                                $tiempo_transcurrido = $dias . ' día' . ($dias > 1 ? 's' : '') . ' atrás';
                            }
                            
                            // Agregar clase para notificaciones no leídas
                            $claseItem = $notif['leida'] == 0 ? 'item item-unread' : 'item';
                        ?>
                        <div class="d-flex align-items-center <?= $claseItem ?>">
                            <div class="notification-icon">
                                <i class="fa-solid <?= $icono ?> fa-2x <?= $colorIcono ?>"></i>
                            </div>
                            <div class="notification-content">
                                <h3 class="mb-1"><?= htmlspecialchars($notif['titulo']) ?></h3>
                                <p class="mb-0 text-muted"><?= htmlspecialchars($notif['mensaje']) ?></p>
                                <small class="text-muted"><?= $tiempo_transcurrido ?></small>
                            </div>
                            <div class="notification-action">
                                <button class="btn btn-sm btn-primary" 
                                        onclick="procesarNotificacion(<?= $notif['id'] ?>, <?= $notif['tarea_id'] ? $notif['tarea_id'] : 'null' ?>);">
                                    <?php if (strpos($notif['titulo'], 'Nueva entrega') !== false): ?>
                                        <i class="fa-solid fa-eye me-1"></i><?= $notif['leida'] == 0 ? "Ver entrega" : "Ir a entrega" ?>
                                    <?php else: ?>
                                        <i class="fa-solid fa-tasks me-1"></i><?= $notif['leida'] == 0 ? "Ver tarea" : "Ir a tarea" ?>
                                    <?php endif; ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>    function marcarComoLeida(id, callback = null) {
        fetch(`<?= BASE_URL ?>/app/views/teacher/notificacion_leida.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (callback) {
                        callback(); // Ejecutar callback si se proporciona
                    } else {
                        location.reload(); // Solo recargar si no hay callback
                    }
                }
            })
            .catch(error => console.error("Error al marcar como leída:", error));
    }

    function marcarTodasComoLeidas() {
        if (confirm('¿Estás seguro de que quieres marcar todas las notificaciones como leídas?')) {
            fetch(`<?= BASE_URL ?>/app/views/teacher/marcar_todas_leidas.php`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error al marcar las notificaciones como leídas');
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert('Error al procesar la solicitud');
                });
        }
    }

    function procesarNotificacion(notificacionId, tareaId) {
        // Marcar la notificación como leída y luego redirigir
        marcarComoLeida(notificacionId, () => {
            // Redirigir según si tiene tarea asociada o no
            if (tareaId && tareaId !== null) {
                // Redirigir a la gestión de entregas de la tarea específica
                window.location.href = "<?= BASE_URL ?>?page=task_management&role=teacher&tarea_id=" + tareaId;
            } else {
                // Redirigir a la página general de gestión de tareas
                window.location.href = "<?= BASE_URL ?>?page=task_management&role=teacher";
            }
        });
    }
</script>

<style>
    .item-unread {
        background-color: #f8f9fa;
        border-left: 4px solid #ff6b35;
        padding-left: 1rem;
        border-radius: 0 5px 5px 0;
        animation: fadeIn 0.3s ease-in;
    }
    
    .item-unread h3 {
        font-weight: bold;
    }
    
    .c-orange {
        color: #ff6b35;
    }
    
    .c-blue {
        color: #007bff;
    }
    
    .text-muted {
        color: #6c757d !important;
    }
    
    .item {
        padding: 1rem;
        margin-bottom: 0.5rem;
        border-radius: 5px;
        transition: background-color 0.3s ease, transform 0.2s ease;
        border: 1px solid #e9ecef;
    }
    
    .item:hover {
        background-color: #f8f9fa;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .notification-icon {
        min-width: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .notification-content {
        flex-grow: 1;
        margin: 0 1rem;
    }
    
    .notification-action {
        min-width: 100px;
        display: flex;
        align-items: center;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .btn-outline-primary:hover {
        background-color: #007bff;
        border-color: #007bff;
        color: white;
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
</style>
