<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/models/NotificationModel.php');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado']);
    exit();
}

try {
    $notificacionModel = new NotificationModel();
    $usuario_id = $_SESSION['user_id'];
    
    // Marcar todas las notificaciones del usuario como leídas
    $success = $notificacionModel->marcarTodasComoLeidas($usuario_id);
    
    echo json_encode(['success' => $success]);
} catch (Exception $e) {
    error_log("Error al marcar todas las notificaciones como leídas: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al procesar la solicitud']);
}
?>
