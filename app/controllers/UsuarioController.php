<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/models/Usuario.php');

class UsuarioController
{
    private $usuarioModel;

    public function __construct()
    {
        try {
            $this->usuarioModel = new Usuario();
        } catch (Exception $e) {
            error_log("Error al inicializar UsuarioController: " . $e->getMessage());
            throw $e;
        }
    }

    public function procesarAccion()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') return null;

            $accion = $_POST['accion'] ?? '';
            $resultado = match ($accion) {
                'crear' => $this->crearUsuario($_POST),
                'actualizar' => $this->actualizarUsuario($_POST),
                'eliminar' => $this->eliminarUsuario($_POST['id']),
                'desactivar' => $this->eliminarUsuario($_POST['id']),
                'activar' => $this->activarUsuario($_POST['id']),
                default => ['error' => 'Acción no válida']
            };

            // CORREGIDO: Si es una petición AJAX, devolver JSON
            if (
                !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
            ) {
                header('Content-Type: application/json');
                echo json_encode($resultado);
                exit;
            }

            // Para peticiones normales, usar sessionStorage
            if ($resultado) {
                echo "<script>sessionStorage.setItem('operationResult', '" . json_encode($resultado) . "');</script>";
            }

            return $resultado;
        } catch (Exception $e) {
            error_log("Error al procesar acción de usuario: " . $e->getMessage());
            $resultado = ['error' => 'Error al procesar la solicitud: ' . $e->getMessage()];

            // Si es AJAX, devolver JSON
            if (
                !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
            ) {
                header('Content-Type: application/json');
                echo json_encode($resultado);
                exit;
            }

            echo "<script>sessionStorage.setItem('operationResult', '" . json_encode($resultado) . "');</script>";
            return $resultado;
        }
    }

    // ...resto de métodos igual...
    private function crearUsuario($datos)
    {
        // Validaciones básicas
        $camposRequeridos = ['username', 'password', 'email', 'nombre', 'apellidos', 'rol_id'];
        foreach ($camposRequeridos as $campo) {
            if (empty($datos[$campo])) {
                return ['error' => 'El campo ' . $campo . ' es requerido'];
            }
        }

        // Validar email
        if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'Email no válido'];
        }

        // Validar longitud del username
        if (strlen($datos['username']) < 4) {
            return ['error' => 'El nombre de usuario debe tener al menos 4 caracteres'];
        }

        // Validar contraseña
        if (strlen($datos['password']) < 6) {
            return ['error' => 'La contraseña debe tener al menos 6 caracteres'];
        }

        try {
            $resultado = $this->usuarioModel->crearUsuario($datos);
            if ($resultado) {
                return ['success' => 'Usuario creado correctamente'];
            }
            return ['error' => 'No se pudo crear el usuario'];
        } catch (Exception $e) {
            return ['error' => 'Error al crear usuario: ' . $e->getMessage()];
        }
    }

    private function actualizarUsuario($datos)
    {
        if (empty($datos['id'])) {
            return ['error' => 'ID de usuario no proporcionado'];
        }

        $camposRequeridos = ['username', 'email', 'nombre', 'apellidos', 'rol_id'];
        foreach ($camposRequeridos as $campo) {
            if (empty($datos[$campo])) {
                return ['error' => 'El campo ' . $campo . ' es requerido'];
            }
        }

        // Validar email
        if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'Email no válido'];
        }

        try {
            $resultado = $this->usuarioModel->actualizarUsuario($datos['id'], $datos);
            if ($resultado) {
                return ['success' => 'Usuario actualizado correctamente'];
            }
            return ['error' => 'No se pudo actualizar el usuario'];
        } catch (Exception $e) {
            return ['error' => 'Error al actualizar usuario: ' . $e->getMessage()];
        }
    }

    private function eliminarUsuario($id)
    {
        if (empty($id)) {
            return ['error' => 'ID de usuario no proporcionado'];
        }

        try {
            $resultado = $this->usuarioModel->eliminarUsuario($id);
            if ($resultado) {
                return ['success' => 'Usuario desactivado correctamente'];
            }
            return ['error' => 'No se pudo desactivar el usuario'];
        } catch (Exception $e) {
            return ['error' => 'Error al desactivar usuario: ' . $e->getMessage()];
        }
    }

    private function activarUsuario($id)
    {
        if (empty($id)) {
            return ['error' => 'ID de usuario no proporcionado'];
        }

        try {
            $resultado = $this->usuarioModel->activarUsuario($id);
            if ($resultado) {
                return ['success' => 'Usuario activado correctamente'];
            }
            return ['error' => 'No se pudo activar el usuario'];
        } catch (Exception $e) {
            return ['error' => 'Error al activar usuario: ' . $e->getMessage()];
        }
    }

    public function getConteoUsuariosPorRol()
    {
        try {
            return $this->usuarioModel->getConteoUsuariosPorRol();
        } catch (Exception $e) {
            return ['error' => 'Error al obtener estadísticas: ' . $e->getMessage()];
        }
    }
}
