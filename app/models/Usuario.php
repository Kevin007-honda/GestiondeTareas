<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/DbConfig.php');

class Usuario {
    private $conn;

    public function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $this->conn->set_charset("utf8mb4");
    }

    public function getRoles() {
        $query = "SELECT id, nombre FROM roles ORDER BY nombre";
        return $this->conn->query($query)->fetch_all(MYSQLI_ASSOC);
    }

    public function getAllUsers() {
        $sql = "SELECT u.*, r.nombre as rol_nombre 
                FROM usuarios u 
                LEFT JOIN roles r ON u.rol_id = r.id 
                ORDER BY u.nombre ASC";
        return $this->conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getConteoUsuariosPorRol() {
        $sql = "SELECT 
                    r.nombre as rol,
                    COUNT(u.id) as total,
                    SUM(CASE WHEN u.activo = 1 THEN 1 ELSE 0 END) as activos,
                    SUM(CASE WHEN u.activo = 0 THEN 1 ELSE 0 END) as inactivos
                FROM roles r 
                LEFT JOIN usuarios u ON r.id = u.rol_id 
                GROUP BY r.id, r.nombre";
        return $this->conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function actualizarUsuario($id, $datos) {
        try {
            $stmt = $this->conn->prepare("SELECT id FROM usuarios WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->bind_param("ssi", $datos['username'], $datos['email'], $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("El usuario o email ya existe");
            }

            $sql = "UPDATE usuarios SET username = ?, email = ?, nombre = ?, apellidos = ?, rol_id = ?";
            $params = [$datos['username'], $datos['email'], $datos['nombre'], $datos['apellidos'], $datos['rol_id']];
            $types = "ssssi";

            if (!empty($datos['password'])) {
                $sql .= ", password = ?";
                $params[] = password_hash($datos['password'], PASSWORD_DEFAULT);
                $types .= "s";
            }

            $sql .= " WHERE id = ?";
            $params[] = $id;
            $types .= "i";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error actualizando usuario: " . $e->getMessage());
            throw $e;
        }
    }

    public function eliminarUsuario($id) {
        try {
            $stmt = $this->conn->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?");
            $stmt->bind_param("i", $id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error eliminando usuario: " . $e->getMessage());
            throw $e;
        }
    }

    public function activarUsuario($id) {
        try {
            $stmt = $this->conn->prepare("UPDATE usuarios SET activo = 1 WHERE id = ?");
            $stmt->bind_param("i", $id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error activando usuario: " . $e->getMessage());
            throw $e;
        }
    }

    public function crearUsuario($datos) {
        try {
            // Verificar duplicados
            $stmt = $this->conn->prepare("SELECT id FROM usuarios WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $datos['username'], $datos['email']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("El usuario o email ya existe");
            }

            $stmt = $this->conn->prepare(
                "INSERT INTO usuarios (username, password, email, nombre, apellidos, rol_id, activo) 
                 VALUES (?, ?, ?, ?, ?, ?, 1)"
            );
            
            $stmt->bind_param("sssssi", 
                $datos['username'],
                $datos['password'], // Ahora usamos la contraseña sin hashear
                $datos['email'],
                $datos['nombre'],
                $datos['apellidos'],
                $datos['rol_id']
            );
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error creando usuario: " . $e->getMessage());
            throw $e;
        }
    }

    public function getUserByEmail($email) {
        try {
            $stmt = $this->conn->prepare(
                "SELECT u.*, r.nombre as rol_nombre 
                FROM usuarios u 
                LEFT JOIN roles r ON u.rol_id = r.id 
                WHERE u.email = ?"
            );
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error obteniendo usuario por email: " . $e->getMessage());
            return null;
        }
    }

    public function getUserById($id) {
        try {
            $stmt = $this->conn->prepare(
                "SELECT u.*, r.nombre as rol_nombre 
                FROM usuarios u 
                LEFT JOIN roles r ON u.rol_id = r.id 
                WHERE u.id = ?"
            );
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error obteniendo usuario por ID: " . $e->getMessage());
            return null;
        }
    }

    public function obtenerProfesores() {
        $sql = "SELECT u.* FROM usuarios u 
                INNER JOIN roles r ON u.rol_id = r.id 
                WHERE r.nombre = 'profesor' AND u.activo = 1
                ORDER BY u.nombre, u.apellidos";
        return $this->conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function query($sql) {
        try {
            $result = $this->conn->query($sql);
            if ($result === false) {
                throw new Exception("Error en la consulta: " . $this->conn->error);
            }
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error en la consulta SQL: " . $e->getMessage());
            throw $e;
        }
    }
}