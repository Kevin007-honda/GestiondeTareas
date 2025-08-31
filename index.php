<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/dirs.php');
require_once(CONTROLLERS_PATH . '/AuthController.php');

// Asegurarse de cargar TareaController antes de usarlo
if (file_exists(CONTROLLERS_PATH . '/TareaController.php')) {
    require_once(CONTROLLERS_PATH . '/TareaController.php');
} else {
    die('Error: No se encuentra el archivo TareaController.php');
}

$auth = new AuthController();

// Verificar si hay sesión activa
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/app/views/auth/login.php');
    exit();
}

$currentUser = $auth->getCurrentUser();

// Procesar acciones específicas
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'markAllAsRead':
            if (isset($_SESSION['user_id'])) {
                $tareaController = new TareaController();
                $tareaController->marcarTodasComoLeidas($_SESSION['user_id']);
                // Redirigir a la página anterior o a la de notificaciones
                header('Location: ' . $_SERVER['HTTP_REFERER'] ?? BASE_URL . '?page=notifications');
                exit();
            }
            break;

            // Más acciones según sea necesario
    }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión Escolar - Colegio San Francisco de Asís</title>
    <!-- ! Page Title Icon -->
    <link rel="icon" href="<?= BASE_URL ?>/public/assets/images/favicon.webp">
    <!-- ! Bootstrap -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/bootstrap.min.css">
    <!-- ! Main Template CSS File -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/style.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/framework.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/darkMode.min.css">
    <!-- ! File Of Animations -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/aos.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.1/dist/aos.css">
    <!-- ! Render All Elements Normally -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/normalize.min.css">
    <!-- ! Font Awesome Library -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/all.min.css">
    <!-- ! Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body>
    <div class="dashboard d-flex">
        <!-- Sidebar dinámico basado en el rol -->
        <?php
        switch (strtolower($_SESSION['rol'])) {
            case 'estudiante':
                require_once(LAYOUTS_PATH . '/sidebar_student.php');
                break;
            case 'administrador':
                require_once(LAYOUTS_PATH . '/sidebar_admin.php');
                break;
            default:
                require_once(LAYOUTS_PATH . '/sidebar_teacher.php');
                break;
        }
        ?>

        <div class="content">
            <!-- Include Header -->
            <?php require_once(LAYOUTS_PATH . '/header.php'); ?>

            <!-- Content basado en el rol y la página -->
            <?php
            $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

            // Ajustar la página según el rol del usuario
            if ($_SESSION['rol'] === 'estudiante' && $page === 'task_management') {
                $page = 'task_visualization';
            }

            switch ($page) {
                // Vistas de Profesor
                case 'dashboard':
                    if ($_SESSION['rol'] === 'administrador') {
                        require_once(VIEWS_PATH . '/admin/dashboard.php');
                    } elseif ($_SESSION['rol'] === 'profesor') {
                        require_once(VIEWS_PATH . '/teacher/dashboard.php');
                    } else {
                        // Mensaje de bienvenida para estudiantes
                        echo "<h1 class='position-relative header-page'>BIENVENIDO AL SISTEMA INTERACTIVO PARA ESTUDIANTES DEL COLEGIO SAN FRANCISCO DE ASÍS</h1>";
                    }
                    break;
                case 'task_management':
                    require_once(VIEWS_PATH . '/teacher/task_management.php');
                    break;
                case 'assigned_tasks':
                    require_once(VIEWS_PATH . '/teacher/assigned_tasks.php');
                    break;
                case 'task_submissions':
                    // Si no hay ID específico, mostrar todas las entregas
                    if (!isset($_GET['tarea_id'])) {
                        require_once(VIEWS_PATH . '/teacher/all_task_submissions.php');
                    } else {
                        require_once(VIEWS_PATH . '/teacher/task_submissions.php');
                    }
                    break;
                case 'notifications':
                    require_once(VIEWS_PATH . '/teacher/notifications.php');
                    break;

                // Vistas de Estudiante
                case 'task_visualization':
                    require_once(VIEWS_PATH . '/student/task_visualization.php');
                    break;
                case 'completed_tasks':
                    require_once(VIEWS_PATH . '/student/completed_tasks.php');
                    break;
                case 'grades':
                    require_once(VIEWS_PATH . '/student/grades.php');
                    break;
                case 'notificationss':
                    require_once(VIEWS_PATH . '/student/notifications_estu.php');
                    break;
                case 'resources':
                    require_once(VIEWS_PATH . '/student/resources.php');
                    break;

                // Vistas de Administrador
                case 'user_management':
                    require_once(VIEWS_PATH . '/admin/user_management.php');
                    break;
                case 'group_management':
                    require_once(VIEWS_PATH . '/admin/group_management.php');
                    break;
                case 'subject_management':
                    require_once(VIEWS_PATH . '/admin/subject_management.php');
                    break;
                case 'system_reports':
                    require_once(VIEWS_PATH . '/admin/system_reports.php');
                    break;
                case 'dashboard':
                    if ($_SESSION['rol'] === 'administrador') {
                        require_once(VIEWS_PATH . '/admin/dashboard.php');
                    }
                    break;

                default:
                    $welcomeMessage = match ($_SESSION['rol']) {
                        'estudiante' => 'BIENVENIDO AL SISTEMA INTERACTIVO PARA ESTUDIANTES',
                        'administrador' => 'BIENVENIDO AL PANEL DE ADMINISTRACIÓN',
                        'profesor' => 'BIENVENIDO AL SISTEMA INTERACTIVO PARA PROFESORES',
                        default => 'BIENVENIDO AL SISTEMA'
                    };
                    echo "<h1 class='position-relative header-page'>$welcomeMessage DEL COLEGIO SAN FRANCISCO DE ASÍS</h1>";
                    break;
            }
            ?>
        </div>
    </div>

    <!---------------------------------------------------------------------------->
    <!-- Mover los scripts justo antes de cerrar el body -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= BASE_URL ?>/public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>/public/assets/js/all.min.js"></script>
    <script src="<?= BASE_URL ?>/public/assets/js/main.js"></script>
    <script src="<?= BASE_URL ?>/public/assets/js/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.1/dist/aos.js"></script>

    <script>
        // Inicializar todos los tooltips y popovers de Bootstrap
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
        var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl);
        });

        AOS.init({
            duration: 800,
            once: true
        });
    </script>
    <!-- Custom SweetAlert Functions -->
    <script>
        function showAlert(title, text, icon) {
            Swal.fire({
                title: title,
                text: text,
                icon: icon,
                confirmButtonColor: '#0075ff'
            });
        }

        function confirmAction(title, text, icon) {
            return Swal.fire({
                title: title,
                text: text,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: '#0075ff',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Cancelar'
            });
        }
    </script>
</body>

</html>