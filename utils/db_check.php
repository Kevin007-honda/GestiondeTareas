<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/DbConfig.php');

// Intentar conectarse a la base de datos
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        echo "<h1>Error de conexión</h1>";
        echo "<p>No se pudo conectar a la base de datos: " . $conn->connect_error . "</p>";
    } else {
        echo "<h1>Conexión exitosa</h1>";
        echo "<p>La conexión a la base de datos funciona correctamente.</p>";
        
        // Verificar tabla de tareas
        $result = $conn->query("DESCRIBE tareas");
        if ($result) {
            echo "<h2>Estructura de la tabla 'tareas':</h2>";
            echo "<table border='1'><tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th></tr>";
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr><td>" . $row['Field'] . "</td><td>" . $row['Type'] . "</td>";
                echo "<td>" . $row['Null'] . "</td><td>" . $row['Key'] . "</td>";
                echo "<td>" . $row['Default'] . "</td></tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>Error al consultar la estructura de la tabla 'tareas': " . $conn->error . "</p>";
        }
        
        // Intentar insertar una tarea de prueba
        echo "<h2>Prueba de inserción:</h2>";
        $titulo = "Tarea de prueba";
        $descripcion = "Esta es una tarea de prueba insertada por el script de verificación";
        $fechaEntrega = date('Y-m-d H:i:s', strtotime('+1 day'));
        $materiaId = 1; // Asegúrate de que existe esta ID en tu base de datos
        $grupoId = 1;   // Asegúrate de que existe esta ID en tu base de datos
        $profesorId = 1; // Asegúrate de que existe esta ID en tu base de datos
        $estadoId = 1;
        
        $sql = "INSERT INTO tareas (titulo, descripcion, fecha_entrega, materia_id, grupo_id, profesor_id, estado_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
                
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sssiiii", $titulo, $descripcion, $fechaEntrega, $materiaId, $grupoId, $profesorId, $estadoId);
            
            if ($stmt->execute()) {
                $insertId = $stmt->insert_id;
                echo "<p class='success'>Tarea de prueba insertada correctamente con ID: $insertId</p>";
                
                // Eliminar la tarea de prueba para no dejar datos de prueba
                $conn->query("DELETE FROM tareas WHERE id = $insertId");
                echo "<p>Se eliminó la tarea de prueba para mantener limpia la base de datos.</p>";
            } else {
                echo "<p class='error'>Error al insertar tarea de prueba: " . $stmt->error . "</p>";
            }
            
            $stmt->close();
        } else {
            echo "<p class='error'>Error al preparar la consulta: " . $conn->error . "</p>";
        }
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "<h1>Excepción</h1>";
    echo "<p>Ocurrió un error: " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    line-height: 1.6;
}
h1, h2 {
    color: #333;
}
table {
    border-collapse: collapse;
    width: 100%;
    margin-bottom: 20px;
}
th, td {
    padding: 8px;
    text-align: left;
}
.success {
    color: green;
    font-weight: bold;
}
.error {
    color: red;
    font-weight: bold;
}
</style>
