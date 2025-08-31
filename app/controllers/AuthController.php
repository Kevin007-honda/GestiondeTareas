<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/dirs.php');
require_once(MODELS_PATH . '/Usuario.php');

class AuthController {
    private $usuario;

    public function __construct() {
        try {
            $this->usuario = new Usuario();
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
        } catch (Exception $e) {
            error_log("Error al inicializar AuthController: " . $e->getMessage());
            throw $e;
        }
    }

    public function login($email, $password) {
        try {
            $usuario = $this->usuario->getUserByEmail($email);
            
            if (!$usuario) {
                return ['success' => false, 'message' => 'Correo electrónico no encontrado'];
            }

            if (!$usuario['activo']) {
                return ['success' => false, 'message' => 'Usuario inactivo'];
            }

            // Comparamos las contraseñas sin hashear
            if ($password === $usuario['password']) {
                $_SESSION['user_id'] = $usuario['id'];
                $_SESSION['username'] = $usuario['username'];
                $_SESSION['nombre_completo'] = $usuario['nombre'] . ' ' . $usuario['apellidos'];
                $_SESSION['email'] = $usuario['email'];
                $_SESSION['rol'] = $usuario['rol_nombre'];
                $_SESSION['rol_id'] = $usuario['rol_id'];
                
                return ['success' => true, 'rol' => $usuario['rol_nombre']];
            }

            return ['success' => false, 'message' => 'Contraseña incorrecta'];
        } catch (Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al intentar iniciar sesión'];
        }
    }

    public function logout() {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            session_destroy();
            header('Location: ' . BASE_URL . '/app/views/auth/login.php');
            exit();
        } catch (Exception $e) {
            error_log("Error en logout: " . $e->getMessage());
            // Redireccionar de todas formas para salir
            header('Location: ' . BASE_URL . '/app/views/auth/login.php');
            exit();
        }
    }

    public function checkAuth() {
        try {
            if (!isset($_SESSION['user_id'])) {
                header('Location: ' . BASE_URL . '/app/views/auth/login.php');
                exit();
            }
            return true;
        } catch (Exception $e) {
            error_log("Error en checkAuth: " . $e->getMessage());
            header('Location: ' . BASE_URL . '/app/views/auth/login.php');
            exit();
        }
    }

    public function getCurrentUser() {
        try {
            return [
                'id' => $_SESSION['user_id'] ?? null,
                'nombre_completo' => $_SESSION['nombre_completo'] ?? null,
                'email' => $_SESSION['email'] ?? null,
                'rol' => $_SESSION['rol'] ?? null
            ];
        } catch (Exception $e) {
            error_log("Error en getCurrentUser: " . $e->getMessage());
            return [
                'id' => null,
                'nombre_completo' => null,
                'email' => null,
                'rol' => null
            ];
        }
    }
}
