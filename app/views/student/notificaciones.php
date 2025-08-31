<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/controllers/NotificationController.php');

try {
    $notificacionController = new NotificationController();

    // Verificar si se envían los parámetros correctos
    if (isset($_GET['id']) && isset($_GET['usuario_id'])) {
        $id = intval($_GET['id']); // Convertir a entero por seguridad
        $usuario_id = intval($_GET['usuario_id']); // Convertir a entero

        $resultado = $notificacionController->marcarNotificacionLeida($id, $usuario_id);
        
        if ($resultado) {
            echo "Notificación marcada como leída correctamente.";
        } else {
            echo "No se pudo marcar la notificación como leída.";
        }
    } else {
        echo "Error: Falta el ID de la notificación o el usuario.";
    }
} catch (Exception $e) {
    error_log("Error al marcar notificación como leída: " . $e->getMessage());
    echo "Error al procesar la solicitud.";
}
?>
