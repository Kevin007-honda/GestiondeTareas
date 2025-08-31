<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/DbConfig.php');

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Usuario no autenticado"]);
    exit;
}

try {
    $cdb = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $cdb->set_charset("utf8mb4");

    if ($cdb->connect_error) {
        throw new Exception("Error de conexión: " . $cdb->connect_error);
    }

    $query = "SELECT et.nombre AS estado, COUNT(t.id) as total 
            FROM tareas t
            JOIN estados_tarea et ON t.estado_id = et.id
            GROUP BY et.nombre";

    $stmt = $cdb->prepare($query);
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $cdb->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    $estadisticas = [
        "Pendiente" => 0,
        "En Progreso" => 0,
        "Completada" => 0,
        "Vencida" => 0,
        "Calificada" => 0,
    ];

    while ($row = $result->fetch_assoc()) {
        $estado = strtolower(trim($row['estado'])); // Normalizar el estado eliminando espacios y convirtiendo a minúsculas
        
        switch ($estado) {
            case "pendiente":
                $estadisticas["Pendiente"] = (int)$row['total'];
                break;
            case "en_progreso": // Ajustar a lo que espera el frontend
                $estadisticas["En Progreso"] = (int)$row['total'];
                break;
            case "completada":
                $estadisticas["Completada"] = (int)$row['total'];
                break;
            case "vencida":
                $estadisticas["Vencida"] = (int)$row['total'];
                break;
            case "calificada":
                $estadisticas["Calificada"] = (int)$row['total'];
                break;
        }
    }

    echo json_encode($estadisticas);
    $stmt->close();
    $cdb->close();
} catch (Exception $e) {
    error_log("Error al contar tareas: " . $e->getMessage());
    echo json_encode([
        "error" => "Error al procesar la solicitud",
        "Pendiente" => 0,
        "En Progreso" => 0,
        "Completada" => 0,
        "Vencida" => 0,
        "Calificada" => 0
    ]);
}
?>
