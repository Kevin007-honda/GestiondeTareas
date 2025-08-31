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
        <div class="latest mega" data-aos="fade-up">
            <h2 class="section-header">Notificaciones Recientes</h2>
            <div class="data" id="notificaciones-container">
                <?php if (empty($notificaciones)): ?>
                    <p>No tienes nuevas notificaciones.</p>
                <?php else: ?>                    <?php foreach ($notificaciones as $notif): ?>                        <?php 
                            $colorIcono = $notif['leida'] == 0 ? "c-orange" : "c-blue";
                            $icono = $notif['leida'] == 0 ? "fa-bell" : "fa-check-circle";
                            
                            // Calcular tiempo transcurrido de forma más simple
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
                        ?>
                        <div class="d-flex align-items-center item">
                            <i class="fa-solid <?= $icono ?> fa-2x <?= $colorIcono ?> me-3"></i>
                            <div class="info">
                                <h3><?= htmlspecialchars($notif['titulo']) ?></h3>
                                <p><?= htmlspecialchars($notif['mensaje']) ?> - <?= $tiempo_transcurrido ?></p>
                            </div>
                            <button class="btn btn-sm btn-primary" 
                                        onclick="procesarNotificacion(<?= $notif['id'] ?>, <?= $notif['tarea_id'] ? $notif['tarea_id'] : 'null' ?>);">
                                        <?= $notif['leida'] == 0 ? "Ver tarea" : "Ir a tarea" ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function marcarComoLeida(id, callback = null) {
        fetch(`<?= BASE_URL ?>/app/views/student/notificacion_leida.php?id=${id}`)
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

    function procesarNotificacion(notificacionId, tareaId) {
        // Marcar la notificación como leída y luego redirigir
        marcarComoLeida(notificacionId, () => {
            // Redirigir según si tiene tarea asociada o no
            if (tareaId && tareaId !== null) {
                // Redirigir a la tarea específica
                window.location.href = "<?= BASE_URL ?>?page=task_visualization&role=student&tarea_id=" + tareaId;
            } else {
                // Redirigir a la página general de tareas
                window.location.href = "<?= BASE_URL ?>?page=task_visualization&role=student";
            }
        });
    }
</script>