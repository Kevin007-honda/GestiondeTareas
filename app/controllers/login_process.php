<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/dirs.php');
require_once(CONTROLLERS_PATH . '/AuthController.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Por favor, complete todos los campos']);
    exit;
}

try {
    $auth = new AuthController();
    $result = $auth->login($email, $password);
    echo json_encode($result);
} catch (Exception $e) {
    error_log("Error en el proceso de login: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al procesar la solicitud']);
}
