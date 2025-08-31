<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/models/NotificationModel.php');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado']);
    exit();
}

try {
    $notificacionModel = new NotificationModel();
    $success = $notificacionModel->marcarComoLeida($_GET['id'], $_SESSION['user_id']);
    echo json_encode(['success' => $success]);
} catch (Exception $e) {
    error_log("Error al marcar notificación como leída: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al procesar la solicitud']);
}
?>
